<?php

/**
 * Created by PhpStorm.
 * User: 001181
 * Date: 2015/5/7
 * Time: 9:22
 * Desc: 维护所有的我的临时红包信息
 */
class BonusRedisService extends BaseService
{
    protected $redis = null;
    protected $new_redis = null;
    protected $redis_key = "my_bonus";
    protected $redis_key_prifx = 'mobile_';
    protected $redis_key_prifx_money = 'money_sum_';
    protected $redis_key_prifx_order = 'order_bonus_';
    protected $redis_key_prifx_from = 'first_invest_reward_back_';

    public function __construct()
    {
        parent::__construct();
        $this->redis_expire_time = 3600 * 24 * 90;//有效期三个月;
        try {
            $this->redis = $this->getRedisHandel();
            $this->new_redis = $this->getNewRedisHandel();
        } catch (Exception $e) {
            throw_exception($e->getMessage());
        }
    }

    /**
     * 设置我的金额
     */
    protected function addMoney($mobile, $money)
    {
        //先获取金额
        if (!$money) {
            return false;
        }
        $old_money = $this->getMoney($mobile);
        $key = $this->redis_key_prifx_money . $mobile;
        $ret = $this->redis->handler->set($key, $old_money + $money);
        $this->redis->expire($key, $this->redis_expire_time);
        return $ret;
    }

    /**
     * 设置我的投资返现的红包金额
     */
    public function setMyOrderBonus($order_id,$money)
    {
        //先获取金额
        if (!$money) {
            return false;
        }
        $key = $this->redis_key_prifx_order . $order_id;
        $ret = $this->new_redis->set($key, $money,$this->redis_expire_time);
        if($ret){
            return $money;
        }else{
            return false;
        }
    }

    public function getMyOrderBonus($order_id)
    {
        $key = $this->redis_key_prifx_order . $order_id;
        $money = $this->new_redis->get($key);
        $money = $money > 0 ? $money : 0;
        return $money;
    }

    /**
     * 根据手机号码获取金额
     * @param $mobile
     */
    public function getMoney($mobile)
    {
        $key = $this->redis_key_prifx_money . $mobile;
        $money = $this->redis->handler->get($key);
        $money = $money > 0 ? $money : 0;
        return $money;
    }

    /**
     * 获取记录的个数
     */
    public function getPushBehaviorCount($mobile)
    {
        try {
            $key = $this->redis_key_prifx . $mobile;
            return $this->redis->handler->zCard($key);
        } catch (Exception $e) {
            throw_exception($e->getMessage());
        }

    }

    public function pushBehaviorByMobile($mobile, $behavior_id)
    {
        try {
            $behavior_list = $this->getPushBehaviorlist($mobile);
            $key = $this->redis_key_prifx . $mobile;
            if (in_array($behavior_id, $behavior_list)) {
                return false;
            } else {
                $money = D("Account/UserBonusBehavior")->where(array('id' => $behavior_id))->getField("money");
                $this->addMoney($mobile, $money);
                $ret = $this->redis->handler->zAdd($key, time(), $behavior_id);
                $this->redis->expire($key, $this->redis_expire_time);
                return $ret;
            }
        } catch (Exception $e) {
            throw_exception($e->getMessage());
        }
    }

    public function isExistPush($mobile, $behavior_id)
    {
        try {
            $behavior_list = $this->getPushBehaviorlist($mobile);
            if (in_array($behavior_id, $behavior_list)) {
                return $behavior_id;
            } else {
                return false;
            }
        } catch (Exception $e) {
            throw_exception($e->getMessage());
        }
    }

    public function getPushBehaviorlist($mobile)
    {
        try {
            $key = $this->redis_key_prifx . $mobile;
            $behavior_list = $this->redis->handler->zRange($key, 0, -1);
            return $behavior_list;
        } catch (Exception $e) {
            throw_exception($e->getMessage());
        }

    }

    public function getPushUserlist($auid)
    {
        try {
            $key = $this->redis_key_prifx_from . $auid;
            $user_list = $this->new_redis->zRange($key, 0, -1);
            $this->new_redis->expire($key,$this->redis_expire_time);
            return $user_list;
        } catch (Exception $e) {
            throw_exception($e->getMessage());
        }

    }

    public function isExistPushFrom($auid, $buid)
    {
        try {
            $user_list = $this->getPushUserlist($auid);
            if (in_array($buid, $user_list)) {
                return $buid;
            } else {
                return false;
            }
        } catch (Exception $e) {
            throw_exception($e->getMessage());
        }


    }

    public function pushUidToInvest($auid, $buid)
    {
        try {
            $user_list = $this->getPushUserlist($auid);
            $key = $this->redis_key_prifx_from . $auid;
            if (in_array($buid, $user_list)) {
                return false;
            } else {
                $res = $this->new_redis->zAdd($key, time(), $buid);
                $this->new_redis->expire($key,$this->redis_expire_time);
                return $res;
            }
        } catch (Exception $e) {
            throw_exception($e->getMessage());
        }
    }


    /**
     * 利用新的方法统一管理redis
     * 为了区分老的不变，但是以后都用这个方法
     */
    public function getNewRedisHandel()
    {
        $this->new_redis = \Addons\Libs\Cache\Redis::getInstance('activity');
        return $this->new_redis;
    }

    /*
    * 初始化redis对象
    */
    public function getRedisHandel()
    {
        import("libs/Cache/RedisSimply", ADDON_PATH);
        try {
            $expire = $this->redis_expire_time;
            $this->redis = new RedisSimply($this->redis_key, $expire);
            return $this->redis;
        } catch (Exception $e) {
            throw_exception($e->getMessage());
        }

    }
} 
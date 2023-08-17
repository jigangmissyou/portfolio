<?php

/**
 * User: 001181
 * Date: 2015-01-07 13:39
 * $Id$
 *
 * Type: Action
 * Group: Fanancing
 * Description: 用户红包(新的概念的红包) 2015-01-22新加入
 */
class UserBonusModel extends BaseModel {

    protected $tableName = 'user_bonus';
    protected $msg = "";

    //from_type的值(其他的待定)
    const BONUS_FROM_TYPE_YAOYIYAO = 1; //通过摇一摇分享形式获取
    const BONUS_FROM_ID_YAOYIYAO = 0; //通过摇一摇分享形式id(由于一个用户只有一条记录，在摇一摇红包中)
    const BONUS_FROM_ID_CASH = 7;//和active_type保持一致
    const BONUS_FROM_ID_QIUSHOU_REG = 8;//新定义类型.注册送红包(秋收起义开始的)

    const DEFAULT_TIME = "2015-3-5 23:59:59";//摇一摇红包的默认过期时间(暂时做显示用)
    
    //状态，一般是根据时间的有效期跑批量update的
    const STATUS_DISENABLE = 0;//禁用
    const STATUS_ACTIVE = 1;//激活
    const STATUS_EXPIRE = 2;//过期

    protected $_auto = array(
        array('ctime', 'time', self::MODEL_INSERT, 'function'),
        array('mtime', 'time', self::MODEL_BOTH, 'function'),
    );

    /**
     * 红包金额数据初始化
     * @param type $data
     * @return type
     */
    public function incBonus($data) {
        if (!isset($data['uid'])) {
            throw_exception("用户UID必须填写!");
        }
        if (!isset($data['from_id'])) {
            throw_exception("来源必须填写!");
        }
        if (!isset($data['from_type'])) {
            throw_exception("来源类型必须填写!");
        }
        if (!$data['amount']) {
            throw_exception("金额必须大于0!");
        }
        //如果有数据，就更新，没有的话就添加数据
        $uid = $data['uid'];
        $from_type = $data['from_type'];
        $from_id = $data['from_id'];
        try {
            $res = $this->initBonusRecord($uid, $from_type, $from_id);
            if ($res) {
                $rs = $this->where(array('uid' => $uid, 'from_type' => $from_type, 'from_id' => $from_id))->setInc("amount", $data['amount']);
                if ($rs) {
                    //返回字段写给user_bonus_id
                    return $this->where(array('uid' => $uid, 'from_type' => $from_type, 'from_id' => $from_id))->getField("id");
                }
                return $rs;
            } else {
                throw_exception($this->getError());
            }
        } catch (Exception $e) {
            throw_exception($e->getMessage());
        }

    }
    
    /**
     * 获取我的红包的数量,为了减少改变.在这里修改
     * @param type $uid
     */
    public function getMyBonus($uid)
    {
        try {
            $java_bonus_service = new \App\Modules\JavaApi\Service\JavaBonusService();

            return $java_bonus_service->getMyRestAmounts($uid);
        } catch (Exception $e) {
            return 0;
        }

    }

    /**
     * 获取我的红包数量
     * @param $uid
     * @return int
     */
    public function getMyBonusOld($uid)
    {
        $where = array(
            'uid' => $uid,
            'status' => self::STATUS_ACTIVE,
        );
        $totle = $this->where($where)->sum("amount");

        return $totle ? (int)$totle : 0;
    }
    
    public function getMyBonusByFromId($uid,$from_type,$from_id){
        $where = array(
            'uid'=>$uid,
            'from_type'=>$from_type,
            'from_id'=>$from_id,
            'status'=>self::STATUS_ACTIVE,
        );
        $totle = $this->where($where)->sum("amount");
        return $totle?(int)$totle:0;
    }
    
     /**
     * 获取我的红包的数量(过期的)
     * @param type $uid
     */
    public function getMyExpireBonus($uid) {
        $where = array(
            'uid'=>$uid,
            'status'=>self::STATUS_EXPIRE,
        );
        $totle = $this->where($where)->sum("amount");
        return $totle?(int)$totle:0;
    }

    /**
     * 初始化记录
     */
    public function initBonusRecord($uid, $from_type, $from_id) {
        $where = array(
            'uid' => $uid,
            'from_type' => $from_type,
            'from_id' => $from_id,
            'status' => self::STATUS_ACTIVE,
        );
        $count = $this->getCount($where);
        if (!$count) {
            $data = $where;
            $data['amount'] = 0; //初始化金额
            $data['ctime'] = $data['mtime'] = time();
            $data['expire_time'] = strtotime(self::DEFAULT_TIME); 
            $count = $this->add($data);
        }
        return $count;
    }

    /**
     * 注册后用户check是否有临时红包，如果有临时红包，把临时红包给他
     */
    public function initRegUser($id,$share_log_id) {
        $mod = D("Account/User");
        //获取行为信息
        $behavior_where = array(
            'id'=>$id,
            'status'=>3,
            'money'=>array('GT',0),
        );
        $behavior_mod = D("Account/UserBonusBehavior");
        $behavior_info = $behavior_mod->where($behavior_where)->find();
        if (!$behavior_info) {
            $this->error = "此用户不存在!";
            return false;
        }
        $mobile = $behavior_info['mobile'];
        $info = $mod->getUserByMobile($mobile);
        if(!$info){
            $this->error = "此用户不存在!";
            return false;
        }
        //求出所有符合条件的行为中的金额
        $amount = $behavior_info['money'];
        //开启事务
        $this->startTrans();
        //查找到了消息,初始化数据
        $data = array(
                'uid'=>$info['uid'],
                'from_type'=>UserBonusModel::BONUS_FROM_TYPE_YAOYIYAO,
                'from_id' => UserBonusModel::BONUS_FROM_ID_YAOYIYAO,
                'amount'=>$amount,//拆开红包
        );
        echo "待插入的金额".PHP_EOL;
        var_dump($data);
        $res = $this->incBonus($data);
        //更新流水和插入日志
        //添加流水和更新行为,更新流水的状态
        D("Account/UserBonusBehavior")->initUserBonusLogs($info,$behavior_info);
        D("Account/UserBonusShareLog")->updateProStatus($share_log_id, 1, 'ok');
        //状态修改在另外一个地方
        if ($res) {
            $this->commit();
            return true;
        } else {
            $this->rollback();
            $this->error = "失败!";
            return false;
        }
    }
    
     /**
     * 获取信息单条
     */
    public function getUserBonusByWhere($where, $field = "*") {
        return $this->where($where)->field($field)->find();
    }

    public function getMsg() {
        return $this->msg;
    }

    public function getCount($where) {
        $count = $this->where($where)->count();
        return $count;
    }

    /**
     * 2016411
     * 获取我的满减券数量
     * @param type $uid
     */
    public function getMyCouponRestAmounts($uid)
    {
        try {
            $java_bonus_service = new \App\Modules\JavaApi\Service\JavaBonusService();

            return $java_bonus_service->getMyCouponRestAmounts($uid);
        } catch (Exception $e) {
            return 0;
        }

    }

    /**
     * 2016411
     * 获取我的加息券数量
     * @param type $uid
     */
    public function getMyVoucherRestAmounts($uid)
    {
        try {
            $java_bonus_service = new \App\Modules\JavaApi\Service\JavaBonusService();

            return $java_bonus_service->getMyVoucherRestAmounts($uid);
        } catch (Exception $e) {
            return 0;
        }

    }

}

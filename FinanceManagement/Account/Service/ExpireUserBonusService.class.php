<?php

/**
 * Created by PhpStorm.
 * User: will
 * Date: 15/6/18
 * Time: 15:07
 */
class ExpireUserBonusService extends BaseService
{

    const FLAG_RUN_TIME = '2015-07-05';//跑了的把时间改成7月5日。防止重复跑
    const EXPIRE_TIME = "2015-06-30 23:59:59";//本可以读取数据,但是为了效率.直接固定
    const PUSH_EXPIRE_LOG = "push_expire_log";

    public function __construct()
    {
        parent::__construct();
    }

    public function twoApplyQueue($params)
    {
        $uid = $params['uid'];
        $id = $params['id'];
        $mod = D("Account/UserBonus");
        $where = array(
            'id' => $id,
        );
        try {
            $v = $mod->where($where)->field(array('amount'))->find();
            $amount = $v['amount'];
            $left_amount = $this->getLeftAmount($uid, $amount);//最后保留的金额
            $expire_amount = $amount - $left_amount;//一共回收的红包金额

            if (!$expire_amount) {
                echo("没有任何红包回收,余额为{$left_amount},处理uid;{$uid}[OK]\r\n");
                return true;
            }
            ////如果有回收,才执行回收.流水状态改变
            $mod->startTrans();
            $set_status = $mod->where(array('id' => $id))->save(array('amount' => $left_amount, 'mtime' => strtotime(self::FLAG_RUN_TIME)));
            if (!$set_status) {
                throw_exception("原因" . $mod->getError() . ",处理uid;{$uid}[ERROR]");
            }
            $commit = $mod->commit();
            if (!$commit) {
                throw_exception("原因" . $mod->getError() . ",处理uid;{$uid}[ERROR]");
            }
            $active_type = 2;//市场部红包种类
            $this->pushExpireLog($uid, $expire_amount, $active_type);
            echo "回收金额{$expire_amount},余额为{$left_amount},处理uid;{$uid}[OK]\r\n";
        } catch (Exception $e) {
            $mod->rollback();
            throw_exception($e->getMessage());
        }
        return true;
    }


    public function threeApplyQueue($params)
    {
        $uid = $params['uid'];
        $id = $params['id'];
        $mod = D("Account/UserBonus");
        $where = array(
            'id' => $id,
        );
        try {
            $v = $mod->where($where)->field(array('amount'))->find();
            $amount = $v['amount'];
            //流水状态改变
            $set_status = $mod->where(array('id' => $id))->save(array('amount' => 0));
            if (!$set_status) {
                throw_exception("处理uid:{$uid}[ERROR],原因" . $mod->getError());
            }
            if (!$amount) {
                echo "没有任何红包回收,余额为{$amount},处理uid;{$uid}[OK]\r\n";
                return true;
            }
            $active_type = 3;//企福鑫红包处理
            $this->pushExpireLog($uid, $amount, $active_type);
            $commit = $mod->commit();
            if (!$commit) {
                throw_exception("处理uid:{$uid}[ERROR],原因" . $mod->getError());
            }
            echo "处理uid:{$uid}[OK],回收金额为{$amount}\r\n";

        } catch (Exception $e) {
            $mod->rollback();
            throw_exception($e->getMessage());
        }
        return true;
    }


    /**
     * @param $uid
     * @param $expire_amount
     * @param $active_type
     * 插入队列
     */
    public function pushExpireLog($uid, $expire_amount, $active_type)
    {
        try {
            $data = array(
                'uid' => $uid,
                'amount' => $expire_amount,
                'active_type' => $active_type,
            );
            $queue = queue(self::PUSH_EXPIRE_LOG, $uid . "_" . $active_type, $data);
            if (!$queue) {
                throw_exception("uid:{$uid}处理流水入队列失败");
            }
        } catch (Exception $e) {
            throw_exception($e->getMessage());
        }
        return true;
    }

    /**
     * 处理流水的
     * @param $uid
     * @param $amount
     * @param $active_type
     * @return bool
     */
    public function addExpireLog($params)
    {
        $uid = $params['uid'];
        $amount = $params['amount'];
        $active_type = $params['active_type'];
        $mod = D('Account/UserBonusLog');
        $expire_time = strtotime(self::EXPIRE_TIME);
        try {
            //如果是过期的，则要特殊处理
            D("Account/UserBonusPackage");
            $mod->startTrans();
            //添加一条流水
            $row['amount'] = $amount;
            $row['uid'] = $uid;
            $row['status'] = 2;
            $row['type'] = 1;
            $row['ctime'] = $expire_time;
            $row['mtime'] = $expire_time;
            if ($active_type == 2) {
                $memo = "活动过期,【活动红包】失效";
            } elseif ($active_type == 3) {
                $memo = "活动过期,【企福鑫红包】失效";
            }
            $row['memo'] = $memo;
            $row['activity_type'] = $active_type;
            $add = $mod->addData($row);
            if (!$add) {
                throw_exception("原因" . $mod->getError() . ",处理uid:{$uid}add流水记录[ERROR]");
            }
            $commit = $mod->commit();
            if (!$commit) {
                throw_exception("原因" . $mod->getError() . ",处理uid:{$uid}add流水记录[ERROR]");
            }
            return true;
        } catch (Exception $e) {
            $mod->rollback();
            throw_exception($e->getMessage() . "[ERROR]");
        }
    }

    /**
     * 获取用户的参加6月28日活动的金额
     */
    protected function getMeetAmount($uid)
    {
        $where = array(
            'uid' => $uid,
            'activity_type' => 4,
            'type' => 1,
        );
        $amount = D("Account/UserBonusLog")->where($where)->getField("amount");
        return $amount > 0 ? (int)$amount : 0;


    }

    public function getLeftAmount($uid, $amount)
    {
        $meet_amount = $this->getMeetAmount($uid);
        //没有参加6.28活动。保留金额就是0
        if (!$meet_amount) {
            return 0;
        }
        //如果红包金额有剩余的话。就是参加活动以后的红包全部保留
        if (($amount - $meet_amount) >= 0) {
            return $meet_amount;
        }
        //使用了部分的参与活动获取的红包，剩余多少保留多少
        if (($amount - $meet_amount) < 0) {
            return $amount;
        }
    }
}
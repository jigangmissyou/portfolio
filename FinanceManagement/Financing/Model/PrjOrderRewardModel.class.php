<?php

/**
 * 红包利率奖励的记录
 * 涉及个人还款计划、个人账户资产统计
 *
 * User: channing
 * Date: 7/14/15
 * Time: 9:13 PM
 */
class PrjOrderRewardModel extends BaseModel
{
    protected $tableName = 'prj_order_reward';

    const STATUS_WAIT = 1; // 未到账
    const STATUS_SUCCESS = 2; // 已到账
    const STATUS_FAIL = 3; // 作废
    const STATUS_PROCESSING = 4;

    const REWARD_TYPE_RATE = 1; // 奖励类型(红包利率)
    const REWARD_TYPE_RATE_ADD = 3; // 加息券

    const ACCOUNT_SUMMARY_TYPE = 'order_reward';

    private $order_id;
    private $order_info;
    private $project;
    private $kwargs;

    protected function _initialize()
    {
        parent::_initialize();
    }


    // 初始化
    public function init($order_id, $project = null, $kwargs = array())
    {
        $this->kwargs = $kwargs;

        $mdPrjOrder = D('Financing/PrjOrder');
        $where_order = array(
            'id' => $order_id,
            'is_regist' => 0,
            'status' => array('NEQ', PrjOrderModel::STATUS_NOT_PAY),
//            'repay_status' => array('NEQ', PrjOrderModel::REPAY_STATUS_ALL_REPAYMENT),
            'is_have_repayplan' => 1,
        );
        $order_info = $mdPrjOrder->where($where_order)->field('uid,prj_id,money,freeze_time,xprj_order_id')->find();
        if (!$order_info) {
            throw_exception('初始化错误: 符合条件的订单信息不存在');
        }
        if (!$project) {
            $project = M('prj')->where(array('id' => $order_info['prj_id']))->field('id,activity_id,status,bid_status,year_rate,end_bid_time,last_repay_date,prj_type')->find();
        }
        if (!$project) {
            throw_exception('初始化错误: 项目信息不存在');
        }

        $this->order_id = $order_id;
        $this->order_info = $order_info;
        $this->project = $project;

        return $this;
    }


    // 初始化检测
    private function checkInit()
    {
        if (!$this->order_id || !$this->order_info || !$this->project) {
            throw_exception('PrjOrderRewardModel类必须先调用init方法进行初始化');
        }
    }


    // 红包利率
    private function getAmountRate()
    {
        if (!$this->project['activity_id']) {
            return 0;
        }

        $mdRewardRule = D('Application/RewardRule');
        $rule = $mdRewardRule->where(array('id' => $this->project['activity_id']))->find();
        if ($rule['action'] == RewardRuleModel::ACTION_PROJECT
            && $rule['status'] == 1
            && $rule['is_reward_money']
            && in_array(RewardRuleModel::ACTION_PROJECT_REPAYMENT, explode(',', $rule['reward_money_action']))
            && !$rule['reward_money_expired']
            && ($rule['reward_money_value'] > 0 || $rule['reward_money_rate'] > 0)
        ) {
            $amount = $rule['reward_money_value'];
            if ($rule['reward_money_rate'] > 0) {
                $start_time = strtotime(date('Y-m-d', $this->project['end_bid_time']));
                if($this->kwargs['last_repay_date']) {
                    $end_time = $this->kwargs['last_repay_date'];
                } else {
                    $end_time = $this->project['last_repay_date'];
                }
                $money = $this->order_info['money'];
                $amount = service('Financing/Project')->profitComputeByDate($rule['reward_money_rate_type'], $rule['reward_money_rate'], $start_time, $end_time, $money);
            }
            return $amount;
        }

        return false;
    }

    // 加息券
    private function getAmountRateAdd()
    {
//        $bonus_rule = M()->table('fi_user_market_ticket_order mto')->where(array(
//            'mto.order_id' => $this->order_id,
//            'mto.freeze_status' => 4, // AddRateModel::FREEZE_STATUS
//            'mto.type' => 2, // AddRateModel::JIAXI
//        ))->join(array(
//            'LEFT JOIN fi_user_market_ticket mt ON mt.id=mto.user_bonus_id',
//            'LEFT JOIN fi_bonus_rule bl ON bl.id=mt.bonus_rule_id'
//        ))->field('bl.rate')->find();
        $bonus_rule=M("user_bonus_freeze")->where(array('order_id' => $this->order_id, 'type' => 3,
        ))->field('rate')->find();
        if(!$bonus_rule) {
            return 0;
        }

        $year_rate = $bonus_rule['rate'];
        $start_time = strtotime(date('Y-m-d', $this->project['end_bid_time']));
        $end_time = $this->project['last_repay_date'];
        if($this->project['prj_type'] == 'H') {
            $start_time = strtotime(date('Y-m-d', $this->order_info['freeze_time']));
            $end_time += 86400;
        }
        $money = $this->order_info['money'];

        $amount = service('Financing/Project')->profitComputeByDate('year', $year_rate, $start_time, $end_time, $money);
        return $amount;
    }

    private function _addRecord($reward_type, $first_check = false)
    {
        if ($reward_type == self::REWARD_TYPE_RATE) {
            $amount = $this->getAmountRate();
        } elseif ($reward_type == self::REWARD_TYPE_RATE_ADD) {
            $amount = $this->getAmountRateAdd();
        } else {
            $amount = 0;
        }

        if (!$amount) {
            return 0;
        }

        if ($first_check) {
            $row = $this->where(array(
                'order_id' => $this->order_id,
                'reward_type' => $reward_type,
            ))->field('id')->find();
            if ($row) {
                return 0;
            }
        }


        if (!$first_check) {
            $this->delRecord($reward_type);
        }

        $now = time();
        if($this->kwargs['last_repay_date']) {
            $repay_date = $this->kwargs['last_repay_date'];
        } else {
            $repay_date = $this->project['last_repay_date'];
        }
        $data = array(
            'uid' => $this->order_info['uid'],
            'prj_id' => $this->order_info['prj_id'],
            'order_id' => $this->order_id,
            'amount' => $amount,
            'reward_type' => $reward_type,
            'repay_date' => strtotime(date('Y-m-d', $repay_date)),
            'status' => self::STATUS_WAIT,
            'ctime' => $now,
            'mtime' => $now,
        );
        $this->add($data);
        if($reward_type == self::REWARD_TYPE_RATE_ADD) {
            M('user_bonus_freeze')->where(array(
                'order_id' => $this->order_id,
                'type' => self::REWARD_TYPE_RATE_ADD,
                'amount' => array('NEQ', $amount),
            ))->save(array(
                'amount' => $amount,
            ));
        }
//        service('Financing/Invest')->addUserRepayPlan($this->order_id, date('Y-m-d', $repay_date), $amount);
        $this->changeAccountSummary($amount);
    }


    // 新增
    public function addRecord($reward_type=null, $first_check = false)
    {
        $this->checkInit();

        $this->startTrans();
        try {
            if (is_null($reward_type)) {
                $this->_addRecord(self::REWARD_TYPE_RATE, $first_check);
                $this->_addRecord(self::REWARD_TYPE_RATE_ADD, $first_check);
            } else {
                $this->_addRecord($reward_type, $first_check);
            }

            $this->commit();
            return true;
        } catch (Exception $e) {
            $this->rollback();
            throw $e;
        }
    }


    // 废除
    private function delRecord($reward_type = self::REWARD_TYPE_RATE)
    {
        $row = $this->where(array(
            'order_id' => $this->order_id,
            'reward_type' => $reward_type,
            'status' => self::STATUS_WAIT
        ))->find();
        if (!$row) {
            return;
        }

//        service('Financing/Invest')->cutUserRepayPlan($row['order_id'], date('Y-m-d', $row['repay_date']), $row['amount']);
        $this->changeAccountSummary(-$row['amount']);
        $this->where(array('id' => $row['id']))->save(array('status' => self::STATUS_FAIL));
    }


    // 已还
    public function okaRecord($reward_type = self::REWARD_TYPE_RATE)
    {
        $this->checkInit();

        $row = $this->where(array(
            'order_id' => $this->order_id,
            'reward_type' => $reward_type,
            'status' => self::STATUS_WAIT
        ))->find();
        if (!$row) {
            return;
        }

        $this->startTrans();
        try {
//            service('Financing/Invest')->cutUserRepayPlan($row['order_id'], date('Y-m-d', $row['repay_date']), $row['amount']);
            $this->changeAccountSummary(-$row['amount']);
            $this->where(array('id' => $row['id']))->save(array('status' => self::STATUS_SUCCESS));

            $this->commit();
        } catch (Exception $e) {
            $this->rollback();
            throw $e;
        }
    }


    // 变更账户统计
    private function changeAccountSummary($amount)
    {
        if ($this->order_info['xprj_order_id']) {
            return true;
        }
        service('Payment/UserAccountSummary')->changeAcountSummaryByItem($this->order_info['uid'], self::ACCOUNT_SUMMARY_TYPE, $this->order_id, $amount, 'will_profit');
        service('Payment/UserAccountSummary')->changeAcountSummaryByItem($this->order_info['uid'], self::ACCOUNT_SUMMARY_TYPE, $this->order_id, $amount, 'will_order_reward');

        return true;
    }


    public function repayRecord($order_id, $repay_date)
    {
        if (!is_numeric($repay_date)) {
            $repay_date = strtotime(date('Y-m-d', strtotime($repay_date)));
        }

        $row = $this->where(array(
            'order_id' => $order_id,
            'reward_type' => self::REWARD_TYPE_RATE_ADD,
            'repay_date' => $repay_date,
            'status' => array('IN', array(self::STATUS_WAIT, self::STATUS_FAIL)),
        ))->find();
        if (!$row) {
            return false;
        }

        // 用了哪个加息券
//        $user_market_ticket = M()->table('fi_user_market_ticket_order mto')->where(array(
//            'mto.order_id' => $order_id,
//            'mto.freeze_status' => 4, // AddRateModel::FREEZE_STATUS
//            'mto.type' => 2, // AddRateModel::JIAXI
//        ))->join('LEFT JOIN fi_user_market_ticket mt ON mt.id=mto.user_bonus_id')->field('mt.use_tip,mt.disp_name,mt.remark')->find();
        $user_market_ticket =M("user_bonus_freeze")->where(array('order_id' => $order_id, 'type' => 3,
        ))->field('rate')->find();
        if(!$user_market_ticket) {
            return false;
        }
        //201646 start 账户类型
        $account_type=M('user_bonus_freeze')->where(array('order_id' => $order_id))->getField('account_type');
        if($account_type){
            $account_uid=service('Public/BonusAccount')->getFromUidByAccountType($account_type);//end
        }

        D('Payment/YunyingRechargeMachine');
        $srvAward = service('Payment/Award');
        $cashout_type = YunyingRechargeMachineModel::TYPE_CASHOUT;

        //$bak = '投资项目用了' . $user_market_ticket['use_tip'] . $user_market_ticket['remark'];
        $bak = '投资项目用了' .number_format($user_market_ticket['rate']/10,2).'%加息券';
        $reward_rule_log_id = 'jiaxi_' . $row['id'];
        $expired = 0;
        $uid = $row['uid'];
        $amount = $row['amount'];
        $extraInfo = array(
            'order_reward_id' => $row['id'],
            //从user_bonus_freeze表里去拿的accountType(1,2,3,4)对应的accountid
            'account_uid' => $account_uid,
            'order_id' => $order_id,
        );
        $srvAward->giveRedEnvelope($uid, $amount, $cashout_type, $bak, $expired, $reward_rule_log_id, $extraInfo);
        return true;
    }


    private function setStatus($id, $status)
    {
        if (!$id) {
            return false;
        }
        $row = $this->where(array('id' => $id))->find();
        if (!$row) {
            throw_exception('奖励回款记录不存在');
        }
        if ($row['status'] == $status) {
            throw_exception('奖励回款记录状态有误' . $status);
        }

        $this->where(array('id' => $id))->save(array('status' => $status));

        return $row;
    }


    public function setStatusProcessing($id)
    {
        return $this->setStatus($id, self::STATUS_PROCESSING);
    }


    public function setStatusSuccess($id)
    {
        try {
            $row = $this->setStatus($id, self::STATUS_SUCCESS);

            M('user_market_ticket_order')->where(array(
                'order_id' => $row['id'],
            ))->save(array(
                'freeze_status' => 2, // AddRateModel::USED_STATUS
            ));

            if($row) {
//                service('Financing/Invest')->cutUserRepayPlan($row['order_id'], date('Y-m-d', $row['repay_date']), $row['amount']);

                $this->init($row['order_id']);
                $this->changeAccountSummary(-$row['amount']);
            }

            $this->commit();
        } catch (Exception $e) {
            $this->rollback();
            throw $e;
        }
        return true;
    }


    public function setStatusFailed($id)
    {
        return $this->setStatus($id, self::STATUS_FAIL);
    }

    public function getPrjOrderRewardMoney($prj_order_id)
    {
        $prj_order_reward_model = new PrjOrderRewardModel();
        $result = $prj_order_reward_model
            ->field('sum(amount) amount')
            ->where([
                'order_id' => $prj_order_id,
                'status' => PrjOrderRewardModel::STATUS_WAIT,
                'reward_type' => ['in', [
                    PrjOrderRewardModel::REWARD_TYPE_RATE,
                    PrjOrderRewardModel::REWARD_TYPE_RATE_ADD
                ]],
            ])
            ->find();

        return (int) $result['amount'];
    }
}

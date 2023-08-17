<?php

import_app("Financing.Model.PrjModel");
import_app("Financing.Model.PrjOrderModel");
import_app("Financing.Model.PrjOrderRepayPlanModel");

class UserAccountSummaryService extends BaseService
{

    //账号统计表修改类型
    public $user_account_summary_type = [
        'buy' => 1,//投资
        'cancel_buy' => 2,//退标，撤资，流标
        'pay' => 3,//支付
        'repay' => 4,//还款
        'xprj_join' => 5,//司马小鑫加入
        'xprj_quit' => 6,//司马小鑫退出
    ];

    /**
     * 根据uid更改用户的统计信息
     * @param int $uid 用户Id
     * @param string $opt 对应的操作 buy:购买，cancel_buy:退标，撤资，流标， pay：财务支付, repay:还款；
     * @param int $obj_id 发生操作的对应ID
     * @param int $change_money 变更的钱
     * @param null $item
     * @return bool;
     */
    public function changeAcountSummaryByItem($uid, $opt, $obj_id, $change_money = 0, $item = null)
    {
        $this->checkType($opt);

        $summary_model = M("user_account_summary");
        $account_summary = $summary_model->find($uid);
        if (!$account_summary) {
            throw_exception('不存在当前账号统计数据,请检查账号ID' . $uid);
        }

        // 转换生成流水记录，记录下历史的数据
        $summary_record = $this->transferSummaryRecord($account_summary);

        $abs_money = abs($change_money);

        $summary_record['opt_type'] = $opt;
        $summary_record['obj_id'] = $obj_id;

        // 收益类字段
        if ($item === 'will_profit' || $item === 'fastcash_profit') {
            $summary_record['change_profit'] = $abs_money;
        } else {
            $summary_record['change_money'] = $abs_money;
        }

        $account_summary[$item] += $change_money;

        return $this->submitRecordSummary($summary_record, $account_summary);
    }

    /**
     * 根据order_id更改用户的统计信息
     * @param string $opt 对应的操作 buy:购买，backbuy:退标，撤资，流标，pay：财务支付, repay:还款；
     * @param $order_id
     * @param int $money 变换本金
     * @param int $profit 变换利息
     * @param null $order_repay_plan
     * @return bool
     */
    public function changeAcountSummary($opt, $order_id, $money = 0, $profit = 0, $order_repay_plan = null)
    {
        $this->checkType($opt);

        if (empty($order_id)) {
            throw_exception("修改账户统计记录失败,请传入参数 order_id的值 ");
        }

        if ($opt === 'repay' && ((empty($money) && empty($profit)) || empty($order_repay_plan))) {
            throw_exception("修改账户统计记录失败,还款操作时必须传入 还款金额 ");
        }
        $order_model = M("prj_order");
        $order = $order_model->find($order_id);

        if ($order['is_mo']) {//外部销售 渠道的订单，不处理直接返回；
            return true;
        }
        //司马小鑫订单投资支付还款不做处理
        if ($order['xprj_order_id'] && in_array($opt, ['buy', 'pay', 'repay'])) {
            return true;
        }

        $uid = (int)$order['uid'];
        if (empty($money) && empty($profit) && $opt != 'repay') {
            $money = $order['money'];
            $profit = $order['possible_yield'];
        }
        $prjInfo = M("prj")->find($order['prj_id']);

        //基金理财 支付的时候不增加利息 同意放在生成还款计划的时候
        if ($opt == 'pay' && $prjInfo['prj_class'] == PrjModel::PRJ_CLASS_LC) {
            $profit = 0;
        }

        $summary_model = M("user_account_summary");
        $account_summary = $summary_model->find($uid);
        if (!$account_summary) {
            throw_exception('不存在当前账号统计数据,请检查账号ID' . $uid);
        }

        // 转换生成流水记录，记录下历史的数据
        $summary_record = $this->transferSummaryRecord($account_summary);

        switch ($opt) {
            // 退标、撤标
            case 'backbuy':
                $account_summary['investing_prj_count'] -= 1;  // 投资中笔数
                $account_summary['investing_prj_money'] -= $money; //// 投资中本金
                break;
            //投资
            case 'buy':
                $account_summary['investing_prj_count'] += 1;  // 投资中笔数
                $account_summary['investing_prj_money'] += $money; //// 投资中本金
                break;
            //支付
            case 'pay':
                $account_summary['investing_prj_count'] -= 1;  // 投资中笔数
                $account_summary['investing_prj_money'] -= $money; //// 投资中本金
                $account_summary['willrepay_prj_count'] += 1;  // 待还款笔数
                $account_summary['willrepay_prj_money'] += $money; //// 待还款本金
                $account_summary['will_principal'] += $money;  // 待收本金
                $account_summary['will_profit'] += $profit; //// 待收利息
                break;
            //还款
            case 'repay':
                $repay_way = $prjInfo['repay_way'];

                //订单还款状态
                $repayStatus = PrjOrderModel::REPAY_STATUS_ALL_REPAYMENT;   // 初始化 默认为全部还款
                $is_delay = in_array($order_repay_plan['ptype'], array(3, 4));

                // 订单当前剩余金额> 本次还款 本金，认为是部分还款。
                if ($order_repay_plan['rest_principal'] > 0) {
                    $repayStatus = PrjOrderModel::REPAY_STATUS_PART_REPAYMENT;
                }

                // 根据新还款付息方式进行逻辑处理
                //一次性还本付息(含老项目) ，直接处理
                if ($repay_way == PrjModel::REPAY_WAY_ENDATE || $repay_way == PrjModel::REPAY_WAY_E) {
                    if ($repayStatus == PrjOrderModel::REPAY_STATUS_ALL_REPAYMENT && !$is_delay) {
                        $account_summary['willrepay_prj_count'] -= 1;  // 待还款笔数
                    }
                    $account_summary['willrepay_prj_money'] -= $money;  // 待还款本金

                } else {   //按月多次还款
                    // 处理 首次还款的逻辑
                    $order_repay_plan_where ['prj_id'] = $order['prj_id'];
                    $order_repay_plan_where ['prj_order_id'] = $order['id'];
                    $order_repay_plan_where ['status'] = PrjOrderRepayPlanModel::ORDER_REPAYMENT_STATUS_SUCCESS;//   PrjRepayPlanModel::PRJ_REPAYMENT_STATUS_SUCCESS ;
                    //$repay_plan_where ['repay_periods'] = array("EGT",2);   // 大于等于 第二期还款

                    $orderRepayPlan = M("prj_order_repay_plan")->field('count(1) as cnt ')->where($order_repay_plan_where)->find();
                    $repay_success_cnt = (int)$orderRepayPlan['cnt'];

                    // 订单还款计划 已经成功还款的数量为1，认为是首次还款
                    if ($repay_success_cnt == 1){
                        $account_summary['repayIn_count'] += 1;  // 还款中笔数
                        $account_summary['repayIn_money'] += $order['rest_money'];  // 还款中金额
                        $account_summary['willrepay_prj_count'] -= 1;  // 待还款笔数
                        $account_summary['willrepay_prj_money'] -= $order['money'];  // 待还款本金   减少的本金一部分进入了还款中rest_money，一部分进入了已还款
                    } else {  // 非首次还款
                        if ($repayStatus == PrjOrderModel::REPAY_STATUS_ALL_REPAYMENT) {
                            $account_summary['repayIn_count'] -= 1;  // 还款中笔数
                        }
                        $account_summary['repayIn_money'] -= $money;  // 还款中本金
                    }
                }

                // 处理已还款的数据
                if ($repayStatus == PrjOrderModel::REPAY_STATUS_ALL_REPAYMENT && !$is_delay) {
                    $account_summary['repayed_prj_count'] += 1;  // 已经还款笔数
                }
                $account_summary['repayed_prj_money'] += $money;  // 已经还款项目金额
                //待收利息，待收本金减少  影响 总资产
                $account_summary['will_principal'] -= $money;
                $account_summary['will_profit'] -= $profit;
                break;
            default:
                return false;
        }

        $summary_record['opt_type'] = $opt;
        $summary_record['obj_id'] = $order_id;
        if ($opt === "repay") {
            $summary_record['obj_id'] = $order_repay_plan['id'];
        }
        $summary_record['change_money'] = $money;
        $summary_record['change_profit'] = $profit;

        $this->submitRecordSummary($summary_record, $account_summary);


        // 速兑通业务, 处理变现申请人的账户概况
        if ($prjInfo['prj_type'] == PrjModel::PRJ_TYPE_H) {

            $fast_uid = $prjInfo['uid'];
            $fast_account_summary = $summary_model->find($fast_uid);
            if (!$fast_account_summary) {
                throw_exception('速兑通:不存在当前账号统计数据,请检查账号ID' . $uid);
            }

            //变现原始订单信息
            $fast_original_order = $order_model->join('fi_prj_fast_cash on fi_prj_order.id=fi_prj_fast_cash.prj_order_id')
                ->where(array("fi_prj_fast_cash.prj_id" => $order['prj_id']))
                ->field('fi_prj_order.money,fi_prj_order.total_amount,fi_prj_order.possible_yield')
                ->find();

            // 转换生成流水记录，记录下历史的数据
            $fast_summary_record = $this->transferSummaryRecord($fast_account_summary);
            //变现原始本金
            $fastcash_original_principal = ($money + $profit) / $fast_original_order['total_amount'] * $fast_original_order['money'];

            switch ($opt) {
                case 'pay':
                    $fast_account_summary['fastcash_principal'] += $money;  // 待还变现本金
                    $fast_account_summary['fastcash_profit'] += $profit; //待还变现利息
                    $redis = \Addons\Libs\Cache\Redis::getInstance();
                    $fastcash_in_count_key = 'fastcash_in_count_pay_key_' . $order['prj_id'];
                    $fastcash_in_count = $redis->incr_expire($fastcash_in_count_key);
                    if ($fastcash_in_count == 1) {
                        $fast_account_summary['fastcash_in_count'] += 1; //待还变现笔数
                    }
                    $fast_account_summary['fastcash_will_original_principal'] += $fastcash_original_principal; //待还已变现原始本金
                    break;
                case 'repay':
                    $fast_account_summary['fastcash_principal'] -= $money;  // 待还 本金
                    $fast_account_summary['fastcash_profit'] -= $profit; //待还 利息
                    $redis = \Addons\Libs\Cache\Redis::getInstance();
                    $fastcash_in_count_key = 'fastcash_in_count_repay_key_' . $order['prj_id'];
                    $fastcash_in_count = $redis->incr_expire($fastcash_in_count_key);
                    if ($fastcash_in_count == 1) {
                        $fast_account_summary['fastcash_in_count'] -= 1; //待还变现笔数
                        $fast_account_summary['fastcash_repay_count'] += 1; //已还变现笔数
                    }
                    $fast_account_summary['fastcash_will_original_principal'] -= $fastcash_original_principal; //待还已变现原始本金

                    $fast_account_summary['fastcash_repay_principal'] += $money;  // 已还 本金
                    $fast_account_summary['fastcash_repay_profit'] += $profit; //已还 利息
                    $fast_account_summary['fastcash_repayed_original_principal'] += $fastcash_original_principal; //已还已变现原始本金
                    $fastcash_repayed_original_profit = ($money + $profit) / $fast_original_order['total_amount'] * $fast_original_order['possible_yield'];
                    $fast_account_summary['fastcash_repayed_original_profit'] += $fastcash_repayed_original_profit; //已还已变现原始收益
                    break;
                default:
                    return false;
            }

            $fast_summary_record['opt_type'] = $opt;

            $fast_summary_record['obj_id'] = $order_id;
            if ($opt === "repay") {
                $fast_summary_record['obj_id'] = $order_repay_plan['id'];
            }

            $fast_summary_record['change_money'] = $money;
            $fast_summary_record['change_profit'] = $profit;

            return $this->submitRecordSummary($fast_summary_record, $fast_account_summary);
        }
        return true;
    }

    /**
     * 检查是否支持当前类型
     * @param $type
     */
    private function checkType($type)
    {
        if (!array_key_exists($type, $this->user_account_summary_type)) {
//            throw_exception('账户流水变更不支持当前类型 ' . $type);
        }
    }


    /**
     * 生成流水记录from数据
     * @param array $summary_data
     * @return array
     */
    private function transferSummaryRecord($summary_data)
    {
        if (!$summary_data) {
            throw_exception('$summary_data参数不能为空');
        }
        $summaryRecord = array();
        $summaryRecord['uid'] = $summary_data['uid'];
        $summaryRecord['from_will_principal'] = $summary_data['will_principal'];
        $summaryRecord['from_will_profit'] = $summary_data['will_profit'];
        $summaryRecord['from_investing_prj_count'] = $summary_data['investing_prj_count'];
        $summaryRecord['from_investing_prj_money'] = $summary_data['investing_prj_money'];
        $summaryRecord['from_willrepay_prj_count'] = $summary_data['willrepay_prj_count'];
        $summaryRecord['from_willrepay_prj_money'] = $summary_data['willrepay_prj_money'];
        $summaryRecord['from_repayIn_count'] = $summary_data['repayIn_count'];
        $summaryRecord['from_repayIn_money'] = $summary_data['repayIn_money'];
        $summaryRecord['from_repayed_prj_count'] = $summary_data['repayed_prj_count'];
        $summaryRecord['from_repayed_prj_money'] = $summary_data['repayed_prj_money'];
        $summaryRecord['from_fastcash_principal'] = $summary_data['fastcash_principal'];
        $summaryRecord['from_fastcash_profit'] = $summary_data['fastcash_profit'];
        $summaryRecord['from_fastcash_repay_principal'] = $summary_data['fastcash_repay_principal'];
        $summaryRecord['from_fastcash_repay_profit'] = $summary_data['fastcash_repay_profit'];
        $summaryRecord['from_fastcash_in_count'] = $summary_data['fastcash_in_count'];
        $summaryRecord['from_fastcash_repay_count'] = $summary_data['fastcash_repay_count'];
        $summaryRecord['from_fastcash_will_original_principal'] = $summary_data['fastcash_will_original_principal'];
        $summaryRecord['from_fastcash_repayed_original_profit'] = $summary_data['fastcash_repayed_original_profit'];
        $summaryRecord['from_fastcash_repayed_original_principal'] = $summary_data['fastcash_repayed_original_principal'];
        $summaryRecord['from_will_order_reward'] = $summary_data['will_order_reward'];
        return $summaryRecord;
    }


    /**
     * 生成流水to数据并且写入数据库
     * @param $summaryRecord
     * @param $toSummary
     * @return bool
     */
    private function submitRecordSummary($summaryRecord, $toSummary)
    {
        if (!$summaryRecord) {
            throw_exception("请传入参数 summaryRecord的值 ");
        }
        $summaryRecord['to_will_principal'] = $toSummary['will_principal'];
        $summaryRecord['to_will_profit'] = $toSummary['will_profit'];
        $summaryRecord['to_investing_prj_count'] = $toSummary['investing_prj_count'];
        $summaryRecord['to_investing_prj_money'] = $toSummary['investing_prj_money'];
        $summaryRecord['to_willrepay_prj_count'] = $toSummary['willrepay_prj_count'];
        $summaryRecord['to_willrepay_prj_money'] = $toSummary['willrepay_prj_money'];
        $summaryRecord['to_repayIn_count'] = $toSummary['repayIn_count'];
        $summaryRecord['to_repayIn_money'] = $toSummary['repayIn_money'];
        $summaryRecord['to_repayed_prj_count'] = $toSummary['repayed_prj_count'];
        $summaryRecord['to_repayed_prj_money'] = $toSummary['repayed_prj_money'];
        $summaryRecord['to_fastcash_principal'] = $toSummary['fastcash_principal'];
        $summaryRecord['to_fastcash_profit'] = $toSummary['fastcash_profit'];
        $summaryRecord['to_fastcash_repay_principal'] = $toSummary['fastcash_repay_principal'];
        $summaryRecord['to_fastcash_repay_profit'] = $toSummary['fastcash_repay_profit'];
        $summaryRecord['to_fastcash_in_count'] = $toSummary['fastcash_in_count'];
        $summaryRecord['to_fastcash_repay_count'] = $toSummary['fastcash_repay_count'];
        $summaryRecord['to_fastcash_will_original_principal'] = $toSummary['fastcash_will_original_principal'];
        $summaryRecord['to_fastcash_repayed_original_profit'] = $toSummary['fastcash_repayed_original_profit'];
        $summaryRecord['to_fastcash_repayed_original_principal'] = $toSummary['fastcash_repayed_original_principal'];
        $summaryRecord['to_will_order_reward'] = $toSummary['will_order_reward'];
        $summaryRecord['ctime'] = time();

        $version = $toSummary['ver'];
        $toSummary['ver'] = $toSummary['ver']+1;
        $toSummary['mtime'] = time();

        $model = M('user_account_summary_record');
        try {
            $model->startTrans();
            $res = $model->add($summaryRecord);
            if (!$res) {
                throw_exception('summary_record增加失败');
            }
            $res = M("user_account_summary")->where(array(
                "uid" => $summaryRecord['uid'],
                "ver" => $version
            ))->save($toSummary);
            if (!$res) {
                throw_exception("user_account_summary 版本不一致，更新失败，请重试");
            }
            $model->commit();
        } catch (Exception $exc) {
            $model->rollback();
            throw_exception($exc->getMessage());
        }
        return true;
    }

}

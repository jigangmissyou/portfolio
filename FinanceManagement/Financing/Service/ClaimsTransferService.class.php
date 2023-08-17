<?php
/**
 * User: 001424
 * Date: 2013-10-10 14:05
 * $Id$
 *
 * Type: Model
 * Group: Fanancing
 * Module: 司马小鑫债权转让
 * Description: 司马小鑫债权转让
 */

import_app('Financing.Service.ProjectService');
class ClaimsTransferService extends ProjectService
{

    function __construct()
    {
        parent::__construct();
    }

    public function bookinClaimsTransfer($order_id)
    {
        $order_info = D('Financing/PrjOrder')->where(array('id'=>$order_id))->find();
        $prj_info = D('Financing/Prj')->where(array('id'=>$order_info['prj_id']))->find();

        if (!$order_info || !$prj_info) {
            $err_msg = "订单信息或项目信息未找到";
            \Addons\Libs\Log\Logger::err("债权登记失败, " . $err_msg, "transfer", array('order_id'=>$order_id));
            throw_exception("$err_msg");
        }

        if ($order_info['prj_type'] == PrjModel::PRJ_TYPE_J && $order_info['root_prj_id']) {
            $root_order_info = D('Financing/PrjOrder')->where(array('id'=>$order_info['root_order_id']))->find();
            $root_prj_info = D('Financing/Prj')->where(array('id'=>$order_info['root_prj_id']))->find();
        } else {
            $root_order_info = $order_info;
            $root_prj_info = $prj_info;
        }

        /**
         * 计算出售日期
         */
        $xprj_order_info = D('Financing/XprjOrder')->where(array('id'=>$order_info['xprj_order_id']))->find();
        if (D('Account/UserExt')->isPlatformUid($order_info['uid'])) {
            $invest_from_type = 1;
        } elseif ($order_info['xprj_order_id']) {
            $invest_from_type = 2;
        } else {
            $invest_from_type = 3;
        }
        $sell_date = $this->calSellDate($invest_from_type, $root_prj_info['last_repay_date'], $xprj_order_info['repay_date']);
        if ($sell_date == null) return true;

        /**
         * 计算债权相关金额
         */
        $data = $this->calClaimsTransfer($sell_date, $root_prj_info, $root_order_info, $prj_info, $order_info);

//print_r($data);exit;
        $mod = D('Financing/PrjOrder');
        $mod->startTrans();
        try {
            D('Financing/XprjClaimsTransfer')->addClaimsTransfer($data);

            // 设置转让订单的转让状态
            D('Financing/PrjOrder')->setTransStatusWait($order_info['id']);

            $mod->commit();
            return true;
        } catch (Exception $e) {
            $mod->rollback();
            \Addons\Libs\Log\Logger::err("债权登记失败, " . $e->getMessage(), "transfer", array('sell_date'=>$sell_date, 'order_id'=>$order_id));
            throw $e;
        }
    }

    public function rectifyClaimsTransfer($claims_transfer_id)
    {
        $claims_transfer_info = D('Financing/XprjClaimsTransfer')->getClaimsTransferById($claims_transfer_id);

        if ($claims_transfer_info['status'] == XprjClaimsTransferModel::STATUS_RELEASED) {
            \Addons\Libs\Log\Logger::err("已经发布的债权不可纠正, ", "transfer", array('claims_transfer_id'=>$claims_transfer_id));
            throw_exception("已经发布的债权不可纠正");
        }

        $order_info = D('Financing/PrjOrder')->where(array('id'=>$claims_transfer_info['from_order_id']))->find();
        $prj_info = D('Financing/Prj')->where(array('id'=>$order_info['prj_id']))->find();

        if (!$order_info || !$prj_info) {
            $err_msg = "订单信息或项目信息未找到";
            \Addons\Libs\Log\Logger::err("债权纠正失败, " . $err_msg, "transfer", array('claims_transfer_id'=>$claims_transfer_id));
            throw_exception("$err_msg");
        }

        if ($order_info['prj_type'] == PrjModel::PRJ_TYPE_J && $order_info['root_prj_id']) {
            $root_order_info = D('Financing/PrjOrder')->where(array('id'=>$order_info['root_order_id']))->find();
            $root_prj_info = D('Financing/Prj')->where(array('id'=>$order_info['root_prj_id']))->find();
        } else {
            $root_order_info = $order_info;
            $root_prj_info = $prj_info;
        }

        /**
         * 计算出售日期
         */
        $xprj_order_info = D('Financing/XprjOrder')->where(array('id'=>$order_info['xprj_order_id']))->find();
        if (D('Account/UserExt')->isPlatformUid($order_info['uid'])) {
            $invest_from_type = 1;
        } elseif ($order_info['xprj_order_id']) {
            $invest_from_type = 2;
        } else {
            $invest_from_type = 3;
        }
        $sell_date = $this->calSellDate($invest_from_type, $root_prj_info['last_repay_date'], $xprj_order_info['repay_date']);
        if ($sell_date == null) {
            D('Financing/XprjClaimsTransfer')->where(array('claims_transfer_id'=>$claims_transfer_id))->delete();
            return true;
        }

        /**
         * 计算债权相关金额
         */
        $data = $this->calClaimsTransfer($sell_date, $root_prj_info, $root_order_info, $prj_info, $order_info);

        if (empty($data)) {
            throw_exception("纠正失败");
        }

//print_r($data);exit;
        $mod = D('Financing/PrjOrder');
        $mod->startTrans();
        try {
            $data['two_confirm'] = 1;
            $data['surplus_amount'] = $data['transfer_price'];
            D('Financing/XprjClaimsTransfer')->where(array('claims_transfer_id'=>$claims_transfer_id))->save($data);
            $mod->commit();
            return true;
        } catch (Exception $e) {
            $mod->rollback();
            \Addons\Libs\Log\Logger::err("债权纠正失败, " . $e->getMessage(), "transfer", array('claims_transfer_id'=>$claims_transfer_id));
            throw $e;
        }
    }

    public function bookinMatchResult($claims_transfer_id, $transferee_amount)
    {
        $mod = D('Financing/PrjOrder');
        $mod->startTrans();
        try {
            D('Financing/XprjClaimsTransfer')->matchSurplusAmount($claims_transfer_id, $transferee_amount);
            $mod->commit();
        } catch (ThinkException $e) {
            $mod->rollback();
            \Addons\Libs\Log\Logger::err("债权匹配结果登记失败, " . $e->getMessage(), "transfer", func_get_args());
            throw $e;
        }
    }

    public function listClaimsTransfer($time_limt, $date = '')
    {
        if (empty($date)) {
            $date = date('Y-m-d');
        } else {
            $date = date('Y-m-d', strtotime($date));
        }

        return D('Financing/XprjClaimsTransfer')->listReleasedClaimsTransfers($time_limt, $date);
    }

    public function listWaitingClaimsTransfer($date = TODAY)
    {
        $date = date('Y-m-d', strtotime($date));

        return D('Financing/XprjClaimsTransfer')->listWaitingClaimsTransfers($date);
    }

    public function releaseClaimsTransfer($claims_transfer_id)
    {
        $lock_key = 'release_claims_transfer_' . $claims_transfer_id;
        $redis = \Addons\Libs\Cache\Redis::getInstance();
        if (!$redis->lock($lock_key)) {
            throw_exception('不能重复处理');
        }

        $mod = D('Financing/PrjOrder');
        try{
            $mod->startTrans();
            $claims_transfer_info = D('Financing/XprjClaimsTransfer')->getClaimsTransferById($claims_transfer_id);
            if ($claims_transfer_info['prj_id']) {
                throw_exception('债权不能重复发布');
            }

            $prj_info = D('Financing/Prj')->where(array('id'=>$claims_transfer_info['from_prj_id']))->find();
            $prj_ext_info = D('Financing/PrjExt')->where(array('prj_id'=>$claims_transfer_info['from_prj_id']))->find();
            $user_info = D('Account/User')->getByUid($claims_transfer_info['from_uid']);
            $prj_order_info = D('Financing/PrjOrder')->where( array('id'=>$claims_transfer_info['from_order_id']) )->find();
            $time_limit_day = day_diff((strtotime($claims_transfer_info['begin_date'])),$prj_order_info['last_repay_date']);


            //要生成的债权转让项目要自定义的信息
            $zqzr_prj_name = D('Prj')->get_show_prj_name(PrjModel::PRJ_TYPE_J);
            $n = 0;
            while ($n++ < 10 && !D('Prj')->where(array('prj_name'=>$zqzr_prj_name))->getField('prj_name')) {
                $zqzr_prj_name = D('Prj')->get_show_prj_name(PrjModel::PRJ_TYPE_J);
            }

            $time = time();
            $zqzr_prj_data_tmp = array(
                'uid' => $claims_transfer_info['from_uid'],
                'tenant_id' => $claims_transfer_info['from_uid'],//商户ID
                'p_prj_id' => $prj_info['id'],//维护父节点ID
                'prj_name' => $zqzr_prj_name,
                'prj_no' => $zqzr_prj_name.rand(0,9),
                'repay_way' => $prj_info['repay_way'],
                'corp_name' => $prj_info['corp_name'],
                'person_name' => $prj_info['person_name'],
                'person_no' => $prj_info['person_no'],
                'corp_register_no' => $prj_info['corp_register_no'],
                'prj_type' => PrjModel::PRJ_TYPE_J,
                'demand_amount' => $claims_transfer_info['transfer_price'],
                'remaining_amount' => $claims_transfer_info['transfer_price'],
                'time_limit' => $time_limit_day,
                'time_limit_unit' => 'day',
                'time_limit_day' => $time_limit_day,
                'rate_type' => 'year',
                'rate' => $claims_transfer_info['transfer_interest_rate'],
                'year_rate' => $claims_transfer_info['transfer_interest_rate'],
                'total_year_rate' => $claims_transfer_info['transfer_interest_rate'],
                'step_bid_amount' => 1,
                'borrower_type' => 2,//借款人类型
                'value_date_shadow' => 1, //T+1
                'min_bid_amount' => 1,//最小投资金额
                'max_bid_amount' => 0,
                'mi_no' => $user_info['mi_no'],
                'is_new' => 0,
                'invest_count' => 0,
                'deposit' => 0,
                'invest_users' => 0,
                'invest_users_alert' => 0,
                'ctime' => $time,
                'version' => 2,
                'activity_id' => 0,
                'guarantor_id' => 0,
                'is_multi_buy' => 1,
                'mtime' => $time,
                'bid_status' => PrjModel::BSTATUS_WATING,
                'status' => PrjModel::STATUS_PASS,
                'start_bid_time' => $time,
                'end_bid_time' => strtotime(date('Y-m-d 23:00:00', $time)),
                'spid' => 0
            );

            //其它字段值都用父项目的
            $zqzr_prj_data = array_merge($prj_info, $zqzr_prj_data_tmp);
            unset($zqzr_prj_data['id']);
            if (D('Financing/Prj')->add($zqzr_prj_data) === false) {
                throw_exception("项目信息添加失败" . D('Financing/Prj')->getError() . "|" . D('Financing/Prj')->getDbError() . "|" . D('Financing/Prj')->getLastSql());
            }

            // 设置转让订单的转让状态
            D('Financing/PrjOrder')->setTransStatusBiding($claims_transfer_info['from_order_id']);

            //生成的债权转让项目ID
            $zqzr_prj_id = D('Financing/Prj')->db()->getLastInsID();

            //prj_ext信息 处理办法和项目类似
            //$protocol = service('Financing/Project')->getProtocol(PrjModel::PRJ_TYPE_J, 0, 0,'1234567890');
            //$protocol_id = service('Financing/Protocol')->getLastProtocolId($protocol['key']);

            $prj_ext_data_tmp = array(
                'prj_id' => $zqzr_prj_id,
                'cash_level' => $prj_ext_info['cash_level']+1,//新生成项目的层级+1
                'ctime' => $time,
                'is_qfx' => 0,
                'is_xzd' => 0,//鑫整点项目转让后非鑫整点
                'company_id' => 0,
                'order_protocol_id' => 0,
                'root_prj_id' => $prj_ext_info['root_prj_id'] ? $prj_ext_info['root_prj_id'] : $claims_transfer_info['from_prj_id'],
                'from_order_id' => $claims_transfer_info['from_order_id'],
                'root_order_id' => $prj_ext_info['root_order_id'] ? $prj_ext_info['root_order_id'] : $claims_transfer_info['from_order_id'],
                'from_xprj_order_id' => $prj_order_info['xprj_order_id'],
            );
            $prj_ext_data = array_merge($prj_ext_info, $prj_ext_data_tmp);
            if (D('Financing/PrjExt')->add($prj_ext_data) === false) {
                throw_exception("项目附加表信息添加失败");
            }
            D('Financing/XPrjMatchingModel');
            $result = D('Financing/XprjClaimsTransfer')->setClaimsTransferStatus($claims_transfer_id, XprjClaimsTransferModel::STATUS_RELEASED);
            $result = $result && D('Financing/XprjClaimsTransfer')->setPrjId($claims_transfer_id, $zqzr_prj_id);
            $row = D('Financing/XPrjMatching')->where(array(
                'match_type'    => XPrjMatchingModel::MATCH_TYPE_A,
                'match_id'      => $claims_transfer_id,
            ))->find();
            if ($row) {
                $result = $result && D('Financing/XPrjMatching')->where(array(
                        'match_type'    => XPrjMatchingModel::MATCH_TYPE_A,
                        'match_id'      => $claims_transfer_id,
                    ))->setField('prj_id', $zqzr_prj_id);
            }
            if (!$result) {
                throw_exception("债权转让发生错误");
            }

            $mod->commit();
            $redis->unlock($lock_key);
        } catch (\Exception $e) {
            $mod->rollback();
            $redis->unlock($lock_key);
            \Addons\Libs\Log\Logger::err("债权发布失败, " . $e->getMessage(), "transfer", func_get_args());
            throw $e;
        }
    }

    public function addOrderRepayPlan($order_id)
    {
        D('Financing/PrjOrder');
        $where = array(
            'id'  => $order_id,
            'status'    => array('IN', array(PrjOrderModel::STATUS_FREEZE, PrjOrderModel::STATUS_PAY_SUCCESS)),
        );
        $order_info = D('Financing/PrjOrder')->where($where)->find();

        if (!$order_info) {
            \Addons\Libs\Log\Logger::err("新增债权还款计划失败，未找到订单", "transfer_repay_plan", func_get_args());
            throw_exception('未找到订单');
        }

        $prj_ext = D('Financing/PrjExt')->where(array('prj_id' => $order_info['prj_id']))->find();

        if ($order_info['prj_type'] != PrjModel::PRJ_TYPE_J) {
            \Addons\Libs\Log\Logger::err("新增债权还款计划失败，只支持债权转让", "transfer_repay_plan", func_get_args());
            throw_exception('只支持债权转让');
        }

        $where = array(
            'order_id'  => $order_id
        );
        $order_transfer = D('Financing/PrjOrderTransferee')->where($where)->find();

        //募集期利息还款计划
        D('Financing/PrjOrderRepayPlan');
        if ($order_transfer['fundraise_interest']>0) {

            $where = array(
                'prj_order_id'  => $prj_ext['from_order_id'],
                'ptype' => PrjOrderRepayPlanModel::PTYPE_BIDING,
                'status'   => PrjOrderRepayPlanModel::ORDER_REPAYMENT_STATUS_WAIT,
            );
            $old_plan_biding = D('Financing/PrjOrderRepayPlan')->where($where)->find();

            if (!$old_plan_biding) {
                \Addons\Libs\Log\Logger::err("新增债权还款计划失败，系统错误，E001", "transfer_repay_plan", func_get_args());
                throw_exception('系统错误，E001');
            }

            $arr_order_repay_plan[] = array(
                'ptype'             => PrjOrderRepayPlanModel::PTYPE_BIDING,
                'prj_id'            => $old_plan_biding['prj_id'],
                'prj_order_id'      => $order_info['id'],
                'repay_periods'     => $old_plan_biding['repay_periods'],
                'repay_date'        => $old_plan_biding['repay_date'],
                'pri_interest'      => $order_transfer['fundraise_interest'],
                'principal'         => 0,
                'yield'             => $order_transfer['fundraise_interest'],
                'rest_principal'    => $order_transfer['transferee_corpus_amount'],
                'status'            => PrjOrderRepayPlanModel::ORDER_REPAYMENT_STATUS_WAIT,
                'prj_repay_plan_id' => $old_plan_biding['prj_repay_plan_id'],
                'ctime'             => time(),
                'mtime'             => time()
            );
        }

        //本金及用款期利息
        $where = array(
            'prj_order_id'  => $prj_ext['from_order_id'],
            'ptype' => PrjOrderRepayPlanModel::PTYPE_ORG,
            'status'   => PrjOrderRepayPlanModel::ORDER_REPAYMENT_STATUS_WAIT,
        );
        $old_plan_org = D('Financing/PrjOrderRepayPlan')->where($where)->find();

        if (!$old_plan_org) {
            \Addons\Libs\Log\Logger::err("新增债权还款计划失败，系统错误，E002", "transfer_repay_plan", func_get_args());
            throw_exception('系统错误，E002');
        }

        $arr_order_repay_plan[] = array(
            'ptype'             => PrjOrderRepayPlanModel::PTYPE_ORG,
            'prj_id'            => $old_plan_org['prj_id'],
            'prj_order_id'      => $order_info['id'],
            'repay_periods'     => $old_plan_org['repay_periods'],
            'repay_date'        => $old_plan_org['repay_date'],
            'pri_interest'      => $order_transfer['transferee_corpus_amount'] + $order_transfer['receivable_interest'], //应收本息
            'principal'         => $order_transfer['transferee_corpus_amount'],//应收本金
            'yield'             => $order_transfer['receivable_interest'],//应收利息
            'rest_principal'    => 0,//剩余本金
            'status'            => PrjOrderRepayPlanModel::ORDER_REPAYMENT_STATUS_WAIT,
            'prj_repay_plan_id' => $old_plan_org['prj_repay_plan_id'],
            'ctime'             => time(),
            'mtime'             => time()
        );

        //奖励还款
        D('Financing/PrjOrderReward');
        if ($order_transfer['reward_interest']) {
            $where = array(
                'order_id'  => $prj_ext['from_order_id'],
                'status'   => PrjOrderRewardModel::STATUS_WAIT,
            );
            $old_plan_reward = D('Financing/PrjOrderReward')->where($where)->find();

            if (!$old_plan_reward) {
                \Addons\Libs\Log\Logger::err("新增债权还款计划失败，系统错误，E003", "transfer_repay_plan", func_get_args());
                throw_exception('系统错误，E003' . D('Financing/PrjOrderReward')->getLastSql());
            }

            $prj_order_reward = array(
                'uid'       => $order_info['uid'],
                'prj_id'    => $old_plan_reward['prj_id'],
                'order_id'  => $order_info['id'],
                'amount'	=> $order_transfer['reward_interest'],
                'reward_type'   => $old_plan_reward['reward_type'],
                'repay_date'    => $old_plan_reward['repay_date'],
                'status'        => PrjOrderRewardModel::STATUS_WAIT,
                'ctime'             => time(),
                'mtime'             => time()
            );
        }

        $mod = D('Financing/PrjOrder');
        $mod->startTrans();
        try{
            $result = true;
            foreach ($arr_order_repay_plan as $order_repay_plan) {
                $result && $result = D('Financing/PrjOrderRepayPlan')->add($order_repay_plan);

            }

            if ($prj_order_reward) {
                $result && $result = D('Financing/PrjOrderReward')->add($prj_order_reward);
            }

            $data = array(
                'possible_yield' => $order_transfer['receivable_interest']
                    + $order_transfer['fundraise_interest']
                    + $order_transfer['reward_interest'],
                'is_have_repayplan' => 1,
            );
            $result && $result = D('Financing/PrjOrder')->where(array('id'=>$order_id))->save($data);

            if (!$result) {
                throw_exception('还款计划生成失败[order:'.$order_id.']');
            }

            $mod->commit();
        } catch (Exception $e) {
            $mod->rollback();
            \Addons\Libs\Log\Logger::err("新增债权还款计划失败，".$e->getMessage(), "transfer_repay_plan", func_get_args());
            throw $e;
        }
    }

    public function changeOldOrderRepayPlan($prj_id)
    {
        //由订单发出的债权
        $transfer_prj_info = D('Financing/Prj')->where(array('id' => $prj_id))->find();
        $transfer_prj_ext = D('Financing/PrjExt')->where(array('prj_id' => $prj_id))->find();
        if (!$transfer_prj_info
            || !in_array($transfer_prj_info['bid_status'], array(PrjModel::BSTATUS_FULL, PrjModel::BSTATUS_END))) {
            \Addons\Libs\Log\Logger::err("更改原有订单还款计划失败，只支持出售债权已满标或截标下修改还款计划",
                "transfer_repay_plan", func_get_args());
            throw_exception('只支持出售债权已满标或截标下修改还款计划');
        }

        //父订单还款计划
        D('Financing/PrjOrderRepayPlan');
        D('Financing/PrjOrderReward');
        $where = array(
            'prj_order_id' => $transfer_prj_ext['from_order_id'],
            'ptype' => PrjOrderRepayPlanModel::PTYPE_BIDING,
            'status'   => PrjOrderRepayPlanModel::ORDER_REPAYMENT_STATUS_WAIT,
        );
        $plan_biding = D('Financing/PrjOrderRepayPlan')->where($where)->find();
        $plan_biding_yield = $plan_biding ? $plan_biding['yield'] : 0;

        $where = array(
            'prj_order_id' => $transfer_prj_ext['from_order_id'],
            'ptype' => PrjOrderRepayPlanModel::PTYPE_ORG,
            'status'   => PrjOrderRepayPlanModel::ORDER_REPAYMENT_STATUS_WAIT,
        );
        $plan_org = D('Financing/PrjOrderRepayPlan')->where($where)->find();
        $plan_org_principal = $plan_org['principal'];
        $plan_org_yield = $plan_org['yield'];

        $where = array(
            'order_id' => $transfer_prj_ext['from_order_id'],
            'status' => PrjOrderRewardModel::STATUS_WAIT
        );
        $plan_reward = D('Financing/PrjOrderReward')->where($where)->find();
        $plan_reward_amount = $plan_reward ? $plan_reward['amount'] : 0;

        if ($plan_org_principal == 0) {
            \Addons\Libs\Log\Logger::err("更改原有订单还款计划失败，未找到原有还款计划",
                "transfer_repay_plan", func_get_args());
            throw_exception('未找到原有还款计划');
        }

        /**
        D('Financing/PrjOrder');
        $where = array(
        'order_id'  => $order_id,
        'status'    => array('IN', array(PrjOrderModel::STATUS_FREEZE, PrjOrderModel::STATUS_PAY_SUCCESS)),
        );

        $order_info = D('Financing/PrjOrder')->where($where)->find();
         */

        //因购买债权而产生的子订单
        $transfer_orders = D('Financing/PrjOrder')->field('id')->where(array(
            'prj_id' => $transfer_prj_info['id'],
            'status'    => array('IN', array(PrjOrderModel::STATUS_FREEZE, PrjOrderModel::STATUS_PAY_SUCCESS))
        ))->select();

        $transfer_order_ids = $transfer_orders ? array_column($transfer_orders, 'id', 'id') : array();

        if (empty($transfer_order_ids)) {
            \Addons\Libs\Log\Logger::err("更改原有订单还款计划失败，系统错误，[E001]",
                "transfer_repay_plan", func_get_args());
            throw_exception('系统错误，[E001]');
        }

        //子订单的所有还款计划 用款期本金总和于利息总和
        $where = array(
            'prj_order_id' => array('IN', $transfer_order_ids),
            'ptype' => PrjOrderRepayPlanModel::PTYPE_ORG,
            'status' => array('IN', array(PrjOrderRepayPlanModel::ORDER_REPAYMENT_STATUS_WAIT, PrjOrderRepayPlanModel::ORDER_REPAYMENT_STATUS_SUCCESS))
        );
        $org_principal_count = D('Financing/PrjOrderRepayPlan')->where($where)->count();

        if (count($transfer_order_ids) != $org_principal_count) {
            \Addons\Libs\Log\Logger::err("更改原有订单还款计划失败，还款记录总数不对，[E002]",
                "transfer_repay_plan", func_get_args());
            throw_exception('系统错误，[E002]');
        }

        $mod = D('Financing/PrjOrder');
        $mod->startTrans();
        try {
            //作废原有还款计划
            if ($plan_org) {
                $where = array(
                    'prj_order_id'  => $transfer_prj_ext['from_order_id'],
                    'ptype' => PrjOrderRepayPlanModel::PTYPE_ORG,
                    'status'    => PrjOrderRepayPlanModel::ORDER_REPAYMENT_STATUS_WAIT,
                );

                $data = array(
                    'version'   => $plan_org['version'],
                    'status'    => PrjOrderRepayPlanModel::ORDER_REPAYMENT_STATUS_FAIL,
                    'mtime'     => time(),
                );
                $result = D('Financing/PrjOrderRepayPlan')->save($data, $where);
                if (!$result) {
                    throw_exception('错误1-1 where: ' . json_encode($where));
                }
            }

            if ($plan_biding) {
                $where = array(
                    'prj_order_id'  => $transfer_prj_ext['from_order_id'],
                    'ptype' => PrjOrderRepayPlanModel::PTYPE_BIDING,
                    'status'    => PrjOrderRepayPlanModel::ORDER_REPAYMENT_STATUS_WAIT,
                );

                $data = array(
                    'version'   => $plan_biding['version'],
                    'status'    => PrjOrderRepayPlanModel::ORDER_REPAYMENT_STATUS_FAIL,
                    'mtime'     => time(),
                );
                $result = D('Financing/PrjOrderRepayPlan')->save($data, $where);
                if (!$result) {
                    throw_exception('错误1-2 where: ' . json_encode($where));
                }
            }

            if ($plan_reward) {
                $where = array(
                    'order_id'  => $transfer_prj_ext['from_order_id'],
                    'status'    => PrjOrderRewardModel::STATUS_WAIT,
                );

                $data = array(
                    'status'    => PrjOrderRewardModel::STATUS_FAIL,
                    'mtime'     => time(),
                );
                $result = D('Financing/PrjOrderReward')->where($where)->save($data);

                if (!$result) {
                    throw_exception('错误3 where: ' . json_encode($where));
                }
            }

            if ($transfer_prj_info['bid_status'] == PrjModel::BSTATUS_END) { //截标情况下, 新增还款计划

                //子订单的所有还款计划 募集期利息总和
                $where = array(
                    'prj_order_id' => array('IN', $transfer_order_ids),
                    'ptype' => PrjOrderRepayPlanModel::PTYPE_BIDING,
                    'status' => array('IN', array(PrjOrderRepayPlanModel::ORDER_REPAYMENT_STATUS_WAIT, PrjOrderRepayPlanModel::ORDER_REPAYMENT_STATUS_SUCCESS))
                );

                $sum_biding_yield = D('Financing/PrjOrderRepayPlan')->where($where)->sum('yield');

                //子订单的所有还款计划 用款期本金总和于利息总和
                $where = array(
                    'prj_order_id' => array('IN', $transfer_order_ids),
                    'ptype' => PrjOrderRepayPlanModel::PTYPE_ORG,
                    'status' => array('IN', array(PrjOrderRepayPlanModel::ORDER_REPAYMENT_STATUS_WAIT, PrjOrderRepayPlanModel::ORDER_REPAYMENT_STATUS_SUCCESS))
                );
                $sum_org_principal = D('Financing/PrjOrderRepayPlan')->where($where)->sum('principal');
                $sum_org_yield = D('Financing/PrjOrderRepayPlan')->where($where)->sum('yield');

                //子订单的所有还款计划 奖励利息总和
                $where = array(
                    'order_id' => array('IN', $transfer_order_ids),
                    'status' => array('IN', array(PrjOrderRewardModel::STATUS_WAIT, PrjOrderRewardModel::STATUS_PROCESSING, PrjOrderRewardModel::STATUS_SUCCESS))
                );
                $sum_reward_amount = D('Financing/PrjOrderReward')->where($where)->sum('amount');

                if ($plan_biding_yield - $sum_biding_yield > 0) {
                    $order_repay_plan = array(
                        'ptype'             => PrjOrderRepayPlanModel::PTYPE_BIDING,
                        'prj_id'            => $plan_biding['prj_id'],
                        'prj_order_id'      => $plan_biding['prj_order_id'],
                        'repay_periods'     => $plan_biding['repay_periods'],
                        'repay_date'        => $plan_biding['repay_date'],
                        'pri_interest'      => $plan_biding_yield - $sum_biding_yield,
                        'principal'         => 0,
                        'yield'             => $plan_biding_yield - $sum_biding_yield,
                        'rest_principal'    => $plan_org_principal - $sum_org_principal,
                        'status'            => PrjOrderRepayPlanModel::ORDER_REPAYMENT_STATUS_WAIT,
                        'prj_repay_plan_id' => $plan_biding['prj_repay_plan_id'],
                        'ctime'             => time(),
                        'mtime'             => time()
                    );

                    $result = D('Financing/PrjOrderRepayPlan')->add($order_repay_plan);

                    if (!$result) {
                        throw_exception('错误4 data: ' . json_encode($order_repay_plan));
                    }
                }

                if ($plan_org_principal - $sum_org_principal > 0) {
                    $order_repay_plan = array(
                        'ptype'             => PrjOrderRepayPlanModel::PTYPE_ORG,
                        'prj_id'            => $plan_org['prj_id'],
                        'prj_order_id'      => $plan_org['prj_order_id'],
                        'repay_periods'     => $plan_org['repay_periods'],
                        'repay_date'        => $plan_org['repay_date'],
                        'pri_interest'      => $plan_org_principal - $sum_org_principal
                            + $plan_org_yield - $sum_org_yield, //本金 + 利息
                        'principal'         => $plan_org_principal - $sum_org_principal,
                        'yield'             => $plan_org_yield - $sum_org_yield,
                        'rest_principal'    => 0,
                        'status'            => PrjOrderRepayPlanModel::ORDER_REPAYMENT_STATUS_WAIT,
                        'prj_repay_plan_id' => $plan_org['prj_repay_plan_id'],
                        'ctime'             => time(),
                        'mtime'             => time()
                    );

                    $result = D('Financing/PrjOrderRepayPlan')->add($order_repay_plan);

                    if (!$result) {
                        throw_exception('错误5 data: ' . json_encode($order_repay_plan));
                    }
                }

                if ($plan_reward_amount - $sum_reward_amount > 0) {
                    $prj_order_reward = array(
                        'uid'               => $plan_reward['uid'],
                        'prj_id'            => $plan_reward['prj_id'],
                        'order_id'          => $plan_reward['order_id'],
                        'amount'	        => $plan_reward_amount - $sum_reward_amount,
                        'reward_type'       => $plan_reward['reward_type'],
                        'repay_date'        => $plan_reward['repay_date'],
                        'status'            => PrjOrderRewardModel::STATUS_WAIT,
                        'ctime'             => time(),
                        'mtime'             => time()
                    );
                    $result = D('Financing/PrjOrderReward')->add($prj_order_reward);

                    if (!$result) {
                        throw_exception('错误6 data: ' . json_encode($prj_order_reward));
                    }
                }
            }
            $mod->commit();
        } catch (Exception $e) {
            $mod->rollback();
            \Addons\Libs\Log\Logger::err("更改原有订单还款计划失败，" . $e->getMessage(),
                "transfer_repay_plan", func_get_args());
            throw $e;
        }
    }

    public function complete($prj_id)
    {
        queue('claims_transfer_complete', $prj_id, array('prj_id' => $prj_id));

    }

    public function doCompleteTask($prj_id)
    {
        $mdPrj = D('Financing/Prj');
        $mdPrjOrder = D('Financing/PrjOrder');
        
        $prj_info = $mdPrj->where(array('id' => $prj_id))->field('prj_type,bid_status')->find();
        if ($prj_info['prj_type'] != PrjModel::PRJ_TYPE_J) {
            throw_exception('该项目不属于债券转让');
        }

        if ($prj_info['bid_status'] < PrjModel::BSTATUS_FULL) {
            \Addons\Libs\Log\Logger::err("债权转让非满标状态下调用complete", "transfer_complete", $prj_info);
            throw_exception("该项目状态不对，无法进行complete处理");
        }

        $lock_key = 'do_complete_task_' . $prj_id;
        $redis = \Addons\Libs\Cache\Redis::getInstance();
        if (!$redis->lock($lock_key)) {
            throw_exception('不能重复处理');
        }

        // 生成还款计划
        $orders = $mdPrjOrder->field('id, is_have_repayplan')->where(array(
            'prj_id' => $prj_id,
            'is_have_repayplan' => 0,
            'status' => array('IN', array(PrjOrderModel::STATUS_FREEZE, PrjOrderModel::STATUS_PAY_SUCCESS)),
        ))->select();

        if (($prj_info['bid_status'] == PrjModel::BSTATUS_FULL || $prj_info['bid_status'] == PrjModel::BSTATUS_END) && count($orders) > 0) {

            $mdPrjOrder->startTrans();
            try {
                foreach ($orders as $each) {
                    $this->addOrderRepayPlan($each['id']);
                }
                $this->changeOldOrderRepayPlan($prj_id);

                // 司马小鑫下转出债权，全部售卖后，进行收益计算
                $prj_ext_info = D('Financing/PrjExt')->field('from_xprj_order_id, from_order_id')->where(array('prj_id' => $prj_id))->find();

                if ($prj_ext_info['from_xprj_order_id'] > 0 && $prj_ext_info['from_order_id'] > 0) { // 司马小鑫下转出债权

                    if ($prj_info['bid_status'] == PrjModel::BSTATUS_FULL) { // 全部售卖
                        // 买入价格
                        $money1 = $mdPrjOrder->where(array('id' => $prj_ext_info['from_order_id']))->getField('money');

                        // 卖出价格
                        $where = array(
                            'from_order_id' => $prj_ext_info['from_order_id'],
                            'status' => array('NEQ', PrjOrderModel::STATUS_NOT_PAY)
                        );
                        $money2 = $mdPrjOrder->where($where)->sum('money');

                        $yield = $money2 - $money1;
                        D('Financing/XPrjOrder')->where(array('id' => $prj_ext_info['from_xprj_order_id']))->setInc('actual_yield',
                            $yield);
                        \Addons\Libs\Log\Logger::info("实际收益，项目：{$prj_ext_info['from_xprj_order_id']}，收益：{$yield}", 'transfer_complete');
                    }
                }

                // 发送债权通知
                $this->sendTransferNotice($prj_id);

                $mdPrjOrder->commit();
            } catch (Exception $e) {
                $mdPrjOrder->rollback();
                $redis->unlock($lock_key);
                throw $e;
            }
        }

        // 支付
        $payno = 'ZQ' . $prj_id;
        $claimsTransferApi = new App\Modules\JavaApi\Service\ClaimsTransferApiService();
        $response = $claimsTransferApi->doPrjPay($prj_id, $payno, '债权转让支付');
        if(!is_array($response)) {
            $redis->unlock($lock_key);
            \Addons\Libs\Log\Logger::err('支付接口返回结果异常: ' . $response, "transfer_complete", $prj_id);
            throw_exception('支付接口返回结果异常: ' . $response);
        }

        $redis->unlock($lock_key);
    }

    public function buy($money,$uid,$prjId,$params,$ismobile=0, $is_mo=0, $mo_order_no='')
    {
        $info = D("Financing/Prj")->where(array("id"=>$prjId))->find();
        if(!$info){
            MyError::add("异常,项目不存在!");
            return false;
        }
        try{
            $bankId = service("Financing/Project")->getTenantIdByPrj($info);
            return parent::buy($money, $uid, $prjId,"购买债权产品", $bankId,$params,$ismobile, $is_mo,$mo_order_no);
        }catch (Exception $e){
            MyError::add($e->getMessage());
            return false;
        }
    }


    /**
     * 合作方账号自动购买当天债权
     * @param array $platform_uids 指定合作方账号，为空则不限定
     */
    public function autoBuyTodayClaimsByPaltformUid($platform_uids = array())
    {
        $lock_key = 'auto_buy_today_claims_by_paltform_uid';
        $redis = \Addons\Libs\Cache\Redis::getInstance();
        if (!$redis->lock($lock_key)) {
            throw_exception('不能重复处理');
        }

        static $all_platform_uids;
        if (!$all_platform_uids) {
            $all_platform_uids = D('Account/UserExt')->getPlatformUids();
        }

        if (!$all_platform_uids) {
            $redis->unlock($lock_key);
            throw_exception("未设置合作方账号");
        }

        if ($platform_uids) {
            $platform_uids = array_intersect($platform_uids, $all_platform_uids);
        } else {
            $platform_uids = $all_platform_uids;
        }

        $user_accounts = D('Account/UserAccount')->getMultiUserAccountByUids($platform_uids);
        $claims_transfers = $this->listClaimsTransfer(array());

        $claims_transfers2 = array();
        foreach ($claims_transfers as $each) {
            if (in_array($each['from_uid'], $all_platform_uids)) continue;
            $claims_transfers2[$each['claims_transfer_id']] = $each;
        }

        $match_result = $this->match($user_accounts, $claims_transfers2);

        if (!$match_result){
            $redis->unlock($lock_key);
            return true;
        }

        foreach ($match_result as $uid => $transfers) {
            foreach ($transfers as $claims_transfer_id) {

                $prj_id = $claims_transfers2[$claims_transfer_id]['prj_id'];
                $money = $claims_transfers2[$claims_transfer_id]['surplus_amount'];
                $params = array(
                    'uid' => $uid,
                    'prj_id' => $prj_id,
                    'money' => $money,
                    'is_pre_buy' => 0,
                    'skip_time_interval' => 1,
                    'reward_type' => 0,
                    'reward_id' => 0,
                    'is_platform_user' => 1
                );

                $result = service('Financing/Project')->newBuy($params);
                $i = 0;
                while ($result['status']!=1 && $i++ < 5 ) {
                    usleep(500000);
                    $result = service('Financing/Project')->newBuy($params);
                }

                if ($result['status']==1) {
                    $claims_transfer_info = D('Financing/XprjClaimsTransfer')->getClaimsTransferByPrjId($prj_id);
                    if($claims_transfer_info) {
                        D('Financing/XprjClaimsTransfer')->matchSurplusAmount($claims_transfer_info['claims_transfer_id'], $money);
                    }
                } else {
                    Addons\Libs\Log\Logger::err('债权自动购买失败' . $result['msg'], "transfer", $params);
                    throw_exception('债权自动购买失败' . $result['msg']);
                }
            }
        }
        $redis->unlock($lock_key);
        return true;
    }

    public function sendTransferNotice($prj_id)
    {

        $prj_info = D('Financing/Prj')->find($prj_id);

        if (!$prj_info || $prj_info['prj_type'] != PrjModel::PRJ_TYPE_J) {
            throw_exception("发送债权转让通知书失败，项目未找到或非债权项目");
        }

//        if ($prj_info['bid_status'] != PrjModel::BSTATUS_FULL && $prj_info['bid_status'] != PrjModel::BSTATUS_END) {
//            throw_exception("债权未满标或截标");
//        }

        if ($prj_info['demand_amount'] == $prj_info['remaining_amount']) return true; //未卖出债权

        $prj_ext_info = D('Financing/PrjExt')->find($prj_id);

        $root_prj_info = D('Financing/Prj')->find($prj_ext_info['root_prj_id']);
        $root_prj_ext_info = D('Financing/PrjExt')->field('order_protocol_id')->find($prj_ext_info['root_prj_id']);

        $claims_transfer_info = D('Financing/XprjClaimsTransfer')->getClaimsTransferByPrjId($prj_id);

        $sold_amount = D('Financing/PrjOrderTransferee')->getSoldAmount($prj_id);
        $user_info = D('Financing/User')->where(array('uid' => $prj_info['uid']))->find();

        $protocol_info = M("protocol")->field('name_en, name_cn')->where(array('id'=>$root_prj_ext_info['order_protocol_id']))->find();

        $parent_prj_id = D('Financing/PrjSplit')->getParentPrjId($root_prj_info['id']);
        $where_1 = array(
            'prj_id' => $parent_prj_id,
            'protocol_type' => $protocol_info['name_en'],
            'param_name' => 'contract_no'
        );
        $contract_no = M('prj_protocol_param')->where($where_1)->getField('param_value');

        switch ($protocol_info['name_en']) {
            case 15:
                $root_protocol_name = "借款合同";
                break;
            case 17:
                $root_protocol_name = "应收账款转让及回购合同";
                break;
        }

        /**
         * 受让列表
         */
        $sold_orders = D('Financing/PrjOrder')->field('id,uid')->where(array(
            'prj_id' => $prj_id,
            'status'    => array('NEQ', PrjOrderModel::STATUS_NOT_PAY)
        ))->select();
        $sold_order_ids = $sold_orders ? array_column($sold_orders, 'id', 'id') : array();
        $sold_order_uids = $sold_orders ? array_column($sold_orders, 'uid', 'uid') : array();

        $order_transferee = D('Financing/PrjOrderTransferee')->where(array('order_id' => array('in', $sold_order_ids)))->select();
        $order_transferee = $order_transferee ? array_column($order_transferee, null, 'order_id') : array();

        $order_users = D('Account/User')->where(array('uid' => array('IN', $sold_order_uids)))->select();
        $order_users = $order_users ? array_column($order_users, null, 'uid') : array();

        $list = '<table width="100%" border="1px" cellspacing="0"><tr><td>受让方姓名</td><td>身份证号</td><td>平台账号</td><td>受让债权金额</td></tr>';
        $tr_tpl = "<tr><td>{real_name}</td><td>{license_no}</td><td>{uname}</td><td>{transferee_corpus_amount}</td></tr>";
        foreach($sold_orders as $each) {
            $one = array();
            //(受让人)
            $user = $order_users[$each['uid']];
            if ($user['uid_type'] == UserModel::USER_TYPE_CORP) {
                $finance_corp = M('Zhr/FinanceCorp')->where(array('uid'=>$user['id']))->find();
                //$one['license_type'] = $finance_corp['license_type_b'];
                $one['license_no'] = $finance_corp['license_no_b'];
            } else {
                //$one['license_type'] = "身份证";
                $one['license_no'] = $user['person_id'];
            }

            $one['real_name'] = $user['real_name'];
            $one['uname'] = $user['uname'];
            $one['transferee_corpus_amount'] = floatval($order_transferee[$each['id']]['transferee_corpus_amount'] / 100);

            $list .= str_replace(
                array('{real_name}', '{license_no}', '{uname}', '{transferee_corpus_amount}'),
                array($one['real_name'], $one['license_no'], $one['uname'], $one['transferee_corpus_amount']),
                $tr_tpl);
        }
        $list .= "</table>";

        service('Financing/ProtocolData');
        $convert = new RMBConverter();
        $sum_transferee_corpus_amount = floatval($sold_amount['sum_transferee_corpus_amount'] / 100);
        $data = array(
            0 => $root_prj_info['corp_name'],
            1 => $user_info['real_name'],
            2 => $root_protocol_name,
            3 => $contract_no,
            //'money'                     => $claims_transfer_info['transfer_corpus_amount'],
            4 => $convert->toUpper(floatval($claims_transfer_info['transfer_corpus_amount'] / 100)),
            5 => $root_prj_info['time_limit_day'],
            6 => floatval($root_prj_info['year_rate'] / 10 ) . "%",
            7 => $sum_transferee_corpus_amount,
            8 => $convert->toUpper($sum_transferee_corpus_amount),
            9 => date('Y-m-d'),
            10 => $list,
        );

        return service('Message/Message')->sendMessage($root_prj_info['uid'],1,64,$data,0,0,array(1));
    }

    private function match($user_account, $claims_transfers)
    {
        $accounts = array();
        foreach ($user_account as $each) {
            $accounts[$each['uid']] = $each['amount'];
        }
        asort($accounts);


        $transfers = array();
        foreach($claims_transfers as $each) {
            $transfers[$each['claims_transfer_id']] = $each['surplus_amount'];
        }
        arsort($transfers);


        $result = array();
        foreach($accounts as $uid => $amount) {
            $result[$uid] = array();
            foreach ($transfers as $claims_transfer_id => $surplus_amount) {
                if (empty($claims_transfers[$claims_transfer_id]['prj_id'])) continue;

                if ($surplus_amount > 0 && $amount > 0 && $amount > $surplus_amount) {
                    $result[$uid][] = $claims_transfer_id;
                    $amount -= $surplus_amount;
                    $transfers[$claims_transfer_id] = 0;
                }
            }
        }

        return $result;
    }

    /**
     * @param $invest_from_type 投资来源类型， 1司马小鑫合作方购买， 2普通用户司马小鑫自动购买  3其他
     * @param $prj_repay_date 项目的最后还款时间
     * @param $xjh_repay_date 司马小鑫到期时间
     * @return bool
     */
    private function calSellDate($invest_from_type, $prj_repay_date, $xjh_repay_date)
    {
        if ($invest_from_type == 1) { //司马小鑫合作账号购买
            $sell_date = strtotime("+1 days");
        } elseif ($invest_from_type == 2) { //司马小鑫下普通用户购买

            if ( time() < $xjh_repay_date) {
                if ($prj_repay_date <= $xjh_repay_date) {
                    return null;
                } else {
                    $sell_date = $xjh_repay_date;
                }
            } else { //司马小鑫下的订单未在司马小鑫期内抛出
                //return null;
                $sell_date = strtotime("+1 days");
            }

        } else {
            //throw_exception('债权登记错误，只支持平台用户或非平台用户司马小鑫下购买的订单登记债权');
            return null;
        }

        //项目到期，无法再登记债权
        if ($sell_date >= $prj_repay_date) {
            return null;
        }

        return $sell_date;
    }

    private function calClaimsTransfer($sell_date, $root_prj_info, $root_order_info, $prj_info, $order_info) {
        if ($root_prj_info['value_date_shadow'] == 0) { //T+0
            //募集期天数 = 0
            $fundraise_day_num = 0;

            //已持有天数 = 债权出售日期 - 购买日期 + 1
            $freeze_datetime = new \DateTime(date('Y-m-d', $root_order_info['freeze_time']));
            $sell_date = new \Datetime(date('Y-m-d', $sell_date));
            $hode_day_num = date_diff($freeze_datetime, $sell_date)->days;

            //持有期开始
            $stime = $freeze_datetime->getTimestamp();
        } elseif ($root_prj_info['value_date_shadow'] == 1) { //T+1
            //募集期天数 = 截标日期 - 购买日期
            $freeze_datetime = new \DateTime(date('Y-m-d', $root_order_info['freeze_time']));
            $end_bid_datetime = new \DateTime(date('Y-m-d', $root_prj_info['end_bid_time']));
            $fundraise_day_num = date_diff($freeze_datetime, $end_bid_datetime)->days;

            //已持有天数 = 债权出售日期 - 截标日期
            $sell_date = new \Datetime(date('Y-m-d', $sell_date));
            $hode_day_num = date_diff($end_bid_datetime, $sell_date)->days;

            //持有期开始
            $stime = $end_bid_datetime->getTimestamp();
        } else {
            throw_exception('系统错误');
        }

        if ($order_info['prj_type'] == PrjModel::PRJ_TYPE_J && $order_info['root_prj_id']) { //债权=》债权
            $result = $this->calClaimsTransferToClaimsTransfer($order_info, $prj_info, $root_prj_info, $fundraise_day_num, $hode_day_num, $stime);
        } else { //新资产=》债权
            $result = $this->calNewPrjToClaimsTransfer($order_info, $prj_info, $root_prj_info, $fundraise_day_num, $hode_day_num, $stime);
        }

        $data = array(
            'transfer_corpus_amount'    => $result['transfer_corpus_amount'],
            'transfer_interest_rate'    => $root_prj_info['year_rate'],
            'transfer_price'            => intval($result['transfer_price']),
            'fair_price'                => intval($result['fair_price']),
            'receivable_interest'       => intval($result['receivable_interest']),
            'fundraise_interest'        => intval($result['fundraise_interest']),
            'reward_interest'           => intval($result['reward_interest']),
            'prj_start_bid_time'        => $root_prj_info['start_bid_time'],
            'prj_time_limit'            => $root_prj_info['time_limit_day'],
            'rest_time_limit'           => $root_prj_info['time_limit_day'] - $hode_day_num,
            'from_order_id'             => $order_info['id'],
            'from_prj_id'               => $prj_info['id'],
            'from_uid'                  => $order_info['uid'],
            'begin_date'                => $sell_date->format('Y-m-d')
        );

        return $data;
    }

    private function calClaimsTransferToClaimsTransfer($order_info, $prj_info, $root_prj_info, $fundraise_day_num, $hode_day_num, $stime)
    {
        $order_id = $order_info['id'];
        $order_transferee = D('Financing/PrjOrderTransferee')->where(array('order_id'=>$order_id))->find();

        //已售本金
        $children_order_ids = D('Financing/PrjOrder')->field('id')->where(array(
            'prj_type'      => PrjModel::PRJ_TYPE_J,
            'from_order_id' => $order_id,
            'status'        => array('NEQ', PrjOrderModel::STATUS_NOT_PAY)
        ))->select();

        if ($children_order_ids) {
            $sold_money = D('Financing/PrjOrderTransferee')
                ->field('sum(transferee_corpus_amount) as transferee_corpus_amount
                    , sum(fundraise_interest) as fundraise_interest
                    , sum(receivable_interest) as receivable_interest
                    , sum(reward_interest) as reward_interest')
                ->where(array(
                    'order_id' => array('IN', array_column($children_order_ids, 'id', 'id'))
                ))->find();
        } else {
            $sold_money = array(
                'transferee_corpus_amount' => 0,
                'fundraise_interest' => 0,
                'receivable_interest' => 0,
                'reward_interest' => 0
            );
        }

        $transfer_corpus_amount = $order_transferee['transferee_corpus_amount'] - $sold_money['transferee_corpus_amount'];

        //转让本金募集期利息
        $fundraise_interest = $order_transferee['fundraise_interest'] - $sold_money['fundraise_interest'];
        $fundraise_interest2 = $transfer_corpus_amount * $fundraise_day_num * $root_prj_info['year_rate'] / 1000 / 365;

        //转让本金应收利息 = 受让时的应收利息
        $receivable_interest = $order_transferee['receivable_interest'] - $sold_money['receivable_interest'];

        //转让本金活动奖励利息 = 受让时的活动奖励利息
        $reward_interest = $order_transferee['reward_interest'] - $sold_money['reward_interest'];

        //转让本金持有期应收利息 = 转让本金 * 已持有天数 * 项目利率 / 365
        $hode_receivable_interest = $transfer_corpus_amount * $hode_day_num * $root_prj_info['year_rate'] / 1000 / 365;

        //转让本金持有期活动奖励利息 = 持有天数 / 项目期限 * 转让本金活动奖励利息
        $hode_reward_interest = service('Financing/Invest')->getRedBaoProfit($root_prj_info['activity_id'], $transfer_corpus_amount
            , $stime, $stime + $hode_day_num * 86400, 6);

        //公允价格 = 转让本金 + 转让本金持有期应收利息 + 转让本金募集期利息 + 转让本金持有期活动奖励利息
        //转让价格 = 公允价格
        $transfer_price = $fair_price = $transfer_corpus_amount + $hode_receivable_interest + $fundraise_interest2 + $hode_reward_interest;
//echo implode("|", array($transfer_price,$transfer_corpus_amount, $hode_receivable_interest, $fundraise_interest, $hode_reward_interest));
//            exit;
        return array(
            'transfer_corpus_amount' => $transfer_corpus_amount,
            'transfer_price'        => $transfer_price,
            'fair_price'            => $fair_price,
            'receivable_interest'   => $receivable_interest,
            'fundraise_interest'    => $fundraise_interest,
            'reward_interest'       => $reward_interest,
        );
    }

    private function calNewPrjToClaimsTransfer($order_info, $prj_info, $root_prj_info, $fundraise_day_num, $hode_day_num, $stime)
    {
        $transfer_corpus_amount = $order_info['money'];
        //echo $fundraise_day_num . "|";
        //echo $hode_day_num . "|";
        //转让本金募集期利息 = 转让本金 * 募集期期限 * 项目利率 / 365
        $fundraise_interest = $transfer_corpus_amount * $fundraise_day_num * $root_prj_info['year_rate'] / 1000 / 365;

        //转让本金应收利息 = 转让本金 * 项目期限 * 项目利率 / 365
        $receivable_interest = $transfer_corpus_amount * $prj_info['time_limit_day'] * $root_prj_info['year_rate'] / 1000 / 365;

        //转让本金活动奖励利息 = 转让本金 * 项目期限 * 活动奖励利率 / 365
        $reward_interest = service('Financing/Invest')->getRedBaoProfit($root_prj_info['activity_id'], $transfer_corpus_amount
            , $stime, $stime + $root_prj_info['time_limit_day'] * 86400, 6);

        //转让本金持有期应收利息 = 已持有天数 / 项目期限 * 转让本金应收利息
        $hode_receivable_interest = $order_info['money'] * $hode_day_num * $root_prj_info['year_rate'] / 1000 / 365;

        //转让本金持有期活动奖励利息 = 持有天数 / 项目期限 * 转让本金活动奖励利息
        $hode_reward_interest = service('Financing/Invest')->getRedBaoProfit($root_prj_info['activity_id'], $transfer_corpus_amount
            , $stime, $stime + $hode_day_num * 86400, 6);

        //公允价格 = 转让本金 + 转让本金持有期应收利息 + 转让本金募集期利息 + 转让本金持有期活动奖励利息
        //转让价格 = 公允价格
        $transfer_price = $fair_price = $transfer_corpus_amount + $hode_receivable_interest + $fundraise_interest + $hode_reward_interest;
        //echo implode("|", array($transfer_price,$transfer_corpus_amount, $hode_receivable_interest, $fundraise_interest, $hode_reward_interest));
        //exit;

        return array(
            'transfer_corpus_amount' => $transfer_corpus_amount,
            'transfer_price'        => $transfer_price,
            'fair_price'            => $fair_price,
            'receivable_interest'   => $receivable_interest,
            'fundraise_interest'    => $fundraise_interest,
            'reward_interest'       => $reward_interest,
        );
    }
}

<?php
/**
 * User: 000802
 * Date: 2013-10-23 11:00
 * $Id$
 *
 * Type: Service
 * Group: Fanancing
 * Module: 我要融资
 * Description: 我要融资相关的服务
 */

import_app("Financing.Model.PrjOrderRepayPlanModel");
import_app("Financing.Model.PrjModel");
import_app("Financing.Model.PrjRepayPlanModel");
import_app("Financing.Model.AssetTransferModel");

class FinancingService extends BaseService
{
    const API_UBSP_PAGESIZE = 5;


    /**
     * 添加项目及附加信息
     *
     * @param $uid
     * @param array $input
     * @return array
     * @throws Exception
     */
    public function addPrj($uid, $input = array())
    {
        try {
            return D('Financing/Prj')->addPrj($uid, $input);
        } catch (Exception $e) {
            return array(0, $e->getMessage());
        }
    }


    /**
     * 修改项目及附加信息
     *
     * @param $prj_id
     * @param $uid
     * @param array $input
     * @return array
     */
    public function updatePrj($prj_id, $uid, $input = array())
    {
        try {
            return D('Financing/Prj')->updatePrj($prj_id, $uid, $input);
        } catch (Exception $e) {
            return array(0, $e->getMessage());
        }
    }


    /**
     * 删除项目
     *
     * @param int $uid 删除者Id
     * @param int $prj_id 项目Id
     * @return array
     */
    public function delPrj($uid = 0, $prj_id = 0)
    {
        try {
            return D('Financing/Prj')->delPrj($uid, $prj_id);
        } catch (Exception $e) {
            return array(0, $e->getMessage());
        }
    }


    /**
     * 审核产品
     *
     * @param $uid
     * @param $prj_id
     * @param bool $is_pass 是否通过
     * @param string $desc
     * @param int $activity_id
     * @param int $client_type
     * @param array $mi_array
     * @return array
     */
    public function verifyPrj($uid=0, $prj_id=0, $is_pass=TRUE, $desc='', $activity_id=0, $client_type=0, $mi_array = array(), $prjExt=array()) {
        try {
            $return=D('Financing/Prj')->verifyPrj($uid, $prj_id, $is_pass, $desc, $activity_id,$client_type, $mi_array, $prjExt);
            return $return;
        } catch (Exception $e) {
            return array(0, $e->getMessage());
        }
    }


    /**
     * 撤标
     * @param int $prj_id
     * @param string $desc
     * @return array
     */
    public function cancelPrj($prj_id = 0, $desc = '')
    {
        try {
            return D('Financing/Prj')->cancelPrj($prj_id, $desc);
        } catch (Exception $e) {
            return array(0, $e->getMessage());
        }
    }


    /**
     * 修改平台管理费
     *
     * @param $uid
     * @param $prj_id
     * @param $platform_fee
     * @param $platform_rate
     * @param string $expression
     * @return array
     */
    public function editPrjFee($uid, $prj_id, $platform_fee, $platform_rate, $expression = '')
    {
        try {
            return D('Financing/Prj')->editPrjFee($uid, $prj_id, $platform_fee, $platform_rate, $expression);
        } catch (Exception $e) {
            return array(0, $e->getMessage());
        }
    }


    /**
     * 可转让产品列表
     *
     * @param int $uid 发布者uid，通常是当前用户uid
     * @param int $page 第几页
     * @param int $page_size 每页条数
     * @param bool $return_total 是否返回符合条件数据总数
     * @return array
     */
    public function listTranfer($uid, $page = 1, $page_size = 10, $return_total = TRUE)
    {
        $srvProject = service('Financing/Project');
        D('Fanancing/Prj');
        D('Financing/PrjOrder');
        D('Financing/AssetTransfer');
        $now = time();

        $ret = array(
            'data' => NULL,
            'total' => 0,
        );

        $where = $this->getCanTransWhere(0, $uid);

        $join = array(
            'LEFT JOIN fi_prj pj ON pj.id = po.prj_id',
        );
        $fields = array(
            'pj.end_bid_time',
            'pj.prj_name',
            'pj.prj_no',
            'pj.year_rate',
            'pj.prj_type',
            'pj.safeguards',
            'pj.rate',
            'pj.repay_way',
            'pj.rate_type',
            'pj.is_new',
            'pj.time_limit',
            'pj.time_limit_unit',
            'pj.repay_way',
            'pj.start_bid_time',
            'pj.end_bid_time',
            'po.id',
            'po.prj_id',
            'po.money', // 持有份额
            'po.rest_money', // 剩余份额
            'po.tran_freeze_money', // 待转让份额
            'po.repay_principal',
            'po.yield',
            'po.ctime',
            'po.freeze_time',
            'po.possible_yield',
            'po.is_transfer',
            'po.from_order_id',
        );

        $oModel = M();
        $list = $oModel->table('fi_prj_order po')
            ->where($where)
            ->join($join)
            ->field($fields)
            ->order('po.id DESC')
            ->page($page)
            ->limit($page_size)
            ->select();
// 		echo $oModel->getLastSql();
        foreach ($list as $key => $value) {
            $prjInfo = service("Financing/Project")->getByid($value['prj_id']);
            $list[$key]['value_date'] = $prjInfo['ext']['value_date'];
            $list[$key]['end_bid_time_display'] = date('Y-m-d', $value['end_bid_time']);
            $list[$key]['ctime_display'] = date('Y-m-d', $value['ctime']);
            $list[$key]['rate_type_display'] = $srvProject->getRateType($value['rate_type']);
            $list[$key]['rate_symbol'] = $value['rate_type'] == 'day' ? '‰' : '%';
            $list[$key]['money_display'] = humanMoney($value['money'], 2, FALSE);
//            $list[$key]['rest_money_display'] = humanMoney($value['rest_money']);
//            $list[$key]['tran_freeze_money_display'] = humanMoney($value['tran_freeze_money']);
            if ($value['rate_type'] != PrjModel::RATE_TYPE_DAY) {
                $list[$key]['rate'] = number_format($list[$key]['rate'] / 10, 2);
            } else {
                $list[$key]['rate'] = number_format($list[$key]['rate'], 2);
            }
            // 待转让>剩余份额取消转让
            $list[$key]['disabled'] = $value['tran_freeze_money'] >= $value['rest_money'];
            $list[$key]['second_trans'] = $value['from_order_id'] ? 1 : 0;
            $list[$key]['project'] = $prjInfo;
            // 受益权价值
            $list[$key]['gain'] = humanMoney($value['possible_yield'] + $value['money'], 2, false);

            //转让价格
            $transMoney = $this->getTransMoney($value['id']);
            $list[$key]['money_view'] = humanMoney($transMoney, 2, false) . "元";
            $list[$key]['money_view_raw'] = $transMoney;

            $list[$key]['rest_money_view'] = humanMoney($value['rest_money'], 2, false) . "元";

            //持有期数信息
            $isEnd = service("Financing/Invest")->isEndDate($value['repay_way']);
            $list[$key]['is_end'] = $isEnd;
            if ($isEnd) {
                $list[$key]['no_repay_count'] = 1;
                $list[$key]['repay_count'] = 1;
                $list[$key]['total_count'] = 1;
            } else {
                //富二代
                if ($value['from_order_id']) {
                    $repayedWhere = array();
                    $repayedWhere['prj_order_id'] = $value['id'];
                    $repayedWhere['repay_periods'] = array('NEQ', 0);
                    $repayedWhere['status'] = PrjOrderRepayPlanModel::ORDER_REPAYMENT_STATUS_SUCCESS;
                    $repayedResult = M("prj_order_repay_plan")->field("count(*) as cnt")->where($repayedWhere)->find();
                    $repayedCount = (int)$repayedResult['cnt'];

                    $totalRepayWhere = array();
                    $totalRepayWhere['prj_order_id'] = $value['id'];
                    $totalRepayWhere['status'] = array("NEQ", PrjOrderRepayPlanModel::ORDER_REPAYMENT_STATUS_FAIL);
                    $totalRepayResult = M("prj_order_repay_plan")->field("count(*) as cnt")->where($totalRepayWhere)->find();
                    $totalRepayCount = $totalRepayResult['cnt'];
                } else {
                    list($repayedCount, $totalRepayCount) = service("Application/Repayment")->getPrjRepayPro($value['prj_id']);
                }
                $list[$key]['no_repay_count'] = $totalRepayCount - $repayedCount;
                $list[$key]['repay_count'] = $repayedCount;
                $list[$key]['total_count'] = $totalRepayCount;
            }
            $list[$key]['title'] = service("Financing/Financing")->getTitle($list[$key]['project'],$list[$key]['second_trans']);
            $list[$key]['ico'] = service("Financing/Financing")->getIco($list[$key]['project'],$list[$key]['second_trans']);
            $list[$key]['rate_show'] = service("Financing/Financing")->getRateShow($list[$key]['project'],$list[$key]['second_trans'],0,0,0);
            //到期本息
            $allIncomeMoney = $value['possible_yield'] + $value['money'] - $value['repay_principal'] - $value['yield'];
            $list[$key]['all_income_money_view'] = humanMoney($allIncomeMoney, 2, false) . "元";
            $list[$key]['repay_way_view'] = getCodeItemName('E004',$list[$key]['repay_way']);
            $list[$key]['expire_time_limit'] = service("Financing/Project")->parseExpireTime(0, $value['prj_id'], time());

        }

        if ($return_total) {
            $ret['total'] = $oModel->table('fi_prj_order po')
                ->where($where)
                ->join($join)
                ->count();
        }

        $ret['data'] = $list;
        return $ret;
    }

    function getListTransTotalCount($uid)
    {
        $where = $this->getCanTransWhere(0, $uid);

        $join = array(
            'LEFT JOIN fi_prj pj ON pj.id = po.prj_id',
        );
        $fields = array(
            'pj.end_bid_time',
            'pj.prj_name',
            'pj.prj_no',
            'pj.year_rate',
            'pj.prj_type',
            'pj.safeguards',
            'pj.rate',
            'pj.repay_way',
            'pj.rate_type',
            'pj.time_limit',
            'pj.time_limit_unit',
            'pj.repay_way',
            'po.id',
            'po.prj_id',
            'po.money', // 持有份额
            'po.rest_money', // 剩余份额
            'po.tran_freeze_money', // 待转让份额
            'po.ctime',
            'po.freeze_time',
            'po.possible_yield',
            'po.is_transfer',
            'po.from_order_id',
        );

        $oModel = M();
        return $oModel->table('fi_prj_order po')
            ->where($where)
            ->join($join)
            ->count();
    }


    function getCanTransWhere($ord_id = 0, $uid = 0, $preDayTime = 0)
    {
        $date = strtotime(date('Y-m-d'));
        $now = time();
        $where['po.rest_money'] = array('GT', 0);
        $where['po.is_transfer'] = 1;
        $where['po.status'] = PrjOrderModel::STATUS_PAY_SUCCESS;
        $where['po.repay_status'] = array('NEQ', PrjOrderModel::REPAY_STATUS_ALL_REPAYMENT);
        $where['pj.bid_status'] = array(array("EQ", PrjModel::BSTATUS_REPAYING), array("EQ", PrjModel::BSTATUS_REPAY_IN), "or");
        $where['po.tran_freeze_money'] = 0;

        if ($preDayTime) {
            $poctime = AssetTransferModel::POCTIME_OFFSET - $preDayTime;
        } else {
            $poctime = AssetTransferModel::POCTIME_OFFSET;
        }
        if ($preDayTime) {
            $transOffsetTime = AssetTransferModel::TRANS_OFFSET - $preDayTime;
        } else {
            $transOffsetTime = AssetTransferModel::TRANS_OFFSET;
        }

        $where['_string'] = "
    					last_repay_date > " . $date . "+" . AssetTransferModel::BIDTIME_OFFSET . " AND
    					((UNIX_TIMESTAMP(FROM_UNIXTIME(pj.end_bid_time,'%Y-%m-%d')) < " . $now . " - " . $poctime . " AND po.from_order_id IS NULL )
        				OR (UNIX_TIMESTAMP(FROM_UNIXTIME(po.ctime,'%Y-%m-%d')) < " . $now . " - " . $transOffsetTime . " AND po.from_order_id IS NOT NULL))
        				AND ((pj.time_limit_unit = 'month' AND pj.time_limit >= 6) OR (pj.time_limit_unit = 'day' AND pj.time_limit >= 180))
        						";

        if ($ord_id) $where['po.id'] = $ord_id;
        if ($uid) $where['po.uid'] = $uid;
        return $where;
    }

    function getTransNoticeWhereAll($preDayTime = 0)
    {
        return $this->getCanTransWhere(0, 0, $preDayTime);
    }


    /**
     * 可转让产品详情
     *
     * @param int $ord_id 购买订单Id
     * @return mixed
     */
    public function detailTransfer($ord_id = 0)
    {
        D('Financing/PrjOrder');
        D('Financing/AssetTransfer');
        $now = time();
        $where = $this->getCanTransWhere($ord_id);
        $join = array(
            'LEFT JOIN fi_prj pj ON pj.id = po.prj_id',
            'LEFT JOIN fi_manage_fund jj ON jj.prj_id = po.prj_id',
        );
        $fields = array(
            'pj.id AS prj_id',
            'pj.end_bid_time',
            'pj.prj_name',
            'pj.prj_no',
            'pj.prj_type',
            'pj.rate',
            'pj.rate_type',
            'pj.time_limit',
            'pj.time_limit_unit',
            'pj.repay_way',
            'po.id',
            'po.status',
            'po.out_order_no',
            'po.prj_id',
            'po.prj_type',
            'po.money', // 持有份额
            'po.rest_money', // 剩余份额
            'po.tran_freeze_money', // 待转让份额
            'po.ctime',
            'po.repay_principal',
            'po.possible_yield',
            'po.freeze_time',
            'jj.value_date',
        );

        $oModel = M();
        $detail = $oModel->table('fi_prj_order po')
            ->where($where)
            ->join($join)
            ->field($fields)
            ->find();
// 		echo $oModel->getLastSql();
        if ($detail) {
            $detail['ord_id'] = $detail['id'];
            $detail['rest_money_display'] = humanMoney($detail['rest_money']);
            $detail['tran_freeze_money_display'] = humanMoney($detail['tran_freeze_money']);
            $detail['money_trans'] = $this->getTransMoney($detail['id']); // 转让价格
        }
        return $detail;
    }

    /**
     * 获取转让价格
     */
    function getTransMoney($id, $isTransferId = 0)
    {
        if ($isTransferId) {
            $transferInfo = M("asset_transfer")->where(array("id" => $id))->find();
            if ($transferInfo['status'] == AssetTransferModel::STATUS_PROCESSED) {
                return $transferInfo['money'];
            } else {
                $orderId = $transferInfo['from_order_id'];
            }
        } else {
            $orderId = $id;
        }
        $orderInfo = M("prj_order")->where(array("id" => $orderId))->find();
        $prjId = $orderInfo['prj_id'];
        $prjInfo = M("prj")->where(array("id" => $prjId))->find();
        $money = 0;

        $erpire = $this->isTransExpire($orderInfo['prj_id']);
        if ($erpire) return 0;
        if ($prjInfo['repay_way'] == PrjModel::REPAY_WAY_ENDATE || $prjInfo['repay_way'] == PrjModel::REPAY_WAY_E) {

            $moneyTmp = $orderInfo['rest_money'];
            $lastIncome = service("Financing/Project")->getIncomeById($prjId, $moneyTmp, time());
            $money = $moneyTmp - $lastIncome + $orderInfo['possible_yield'];
        }

        if ($prjInfo['repay_way'] == PrjModel::REPAY_WAY_D) {
            //获取当前期利息
            $income = $this->getTransIncome($orderId);
            $money = $orderInfo['rest_money'] + $income;
        }

        if ($prjInfo['repay_way'] == PrjModel::REPAY_WAY_PERMONTH) {
            $where['prj_order_id'] = $orderId;
            $where['status'] = PrjOrderRepayPlanModel::ORDER_REPAYMENT_STATUS_WAIT;
            $moneyResult = M("prj_order_repay_plan")->field(" SUM(principal) as principal")->where($where)->find();
            //获取当前期利息
            $income = $this->getTransIncome($orderId);
            $money = $moneyResult['principal'] + $income;
        }

        return $money;
    }

    function getTransIncome($orderId)
    {
        $orderPlan = service("Financing/Invest")->getLastOrderWillPlan($orderId, 0);
        if (!$orderPlan) return 0;
        $yield = $orderPlan['yield'];
        $repay_time = strtotime(date('Y-m-d',$orderPlan['repay_date']));
        $preRepay_time = strtotimeX("-1 months",$repay_time);
        $day = ($repay_time-$preRepay_time)/(3600*24);
        $avgYield = $yield/$day;
        $diffDay = (strtotime(date('Y-m-d'))-$preRepay_time)/(3600*24);
        $endTimeTmp = strtotime(date('Y-m-d'));
        //4点后天数加1
        $endbalanceTime = service("Financing/Project")->getBalanceTime($endTimeTmp,$orderPlan['prj_id']);
        if($endbalanceTime < $endTimeTmp)  $diffDay +=1;
        $income = floor($diffDay*$avgYield);
        return $income;
    }


    /**
     * 成功者的总收益
     * @param $orderId
     */
    function getSuccessIncome($orderId, $activitRate, $activitRateType)
    {
        $orderInfo = M("prj_order")->where(array("id" => $orderId))->find();
        $money = $orderInfo['money'];
        $endTime = M("prj")->where(array("id" => $orderInfo['prj_id']))->getField("end_bid_time");
        $curDate = strtotime(date('Y-m-d'));
        $endTime = strtotime(date('Y-m-d', $endTime));
        $income = service("Financing/Project")->profitComputeByDate($activitRateType, $activitRate, $endTime, $curDate, $money);
        return $income;
    }
    
    /**
     * 投资送红包
     */
    public function getRewardHongBao($orderId,$activitRate,$activitRateType)
    {
        $orderInfo = M("prj_order")->where(array("id" => $orderId))->find();
        $money = $orderInfo['money'];
        $field = array(
            'last_repay_date',
            'end_bid_time',
        );
        $prj_info =  M("prj")->where(array("id" => $orderInfo['prj_id']))->field($field)->find();
        if(!$prj_info){
            throw_exception("项目异常!");
        }
        $curDate = strtotime(date('Y-m-d', $prj_info['last_repay_date']));//最后还款时间
        $endTime = strtotime(date('Y-m-d', $prj_info['end_bid_time']));//截标时间

        $income = service("Financing/Project")->profitComputeByDate($activitRateType, $activitRate, $endTime, $curDate, $money);
        return $income;
    }

    /**
     * 成功者的总收益
     * @param $orderId
     */
    function getTransSuccessIncome($orderId,$activitRate,$activitRateType,$transferInfo){
        $orderInfo = M("prj_order")->where(array("id"=>$orderId))->find();
        $money = $orderInfo['money'];
        $endTime = M("prj")->where(array("id"=>$orderInfo['prj_id']))->getField("end_bid_time");
        $curDate = strtotime(date('Y-m-d',$transferInfo['buy_time']));
        $endTime = strtotime(date('Y-m-d',$endTime));
        $income = service("Financing/Project")->profitComputeByDate($activitRateType,$activitRate,$endTime,$curDate,$money);
        return $income;
    }

    //获取项目id
    function getPrjActivityMoney($prjId, $activityId)
    {
        $activityInfo = D("Application/RewardRule")->getRewardRuleInfo($activityId);
        if ($activityInfo['reward_money_rate'] <= 0) return 0;
        $prjInfo = service("Financing/Project")->getDataById($prjId);
        $moneyArr = M("prj_order")->where(array("prj_id" => $prjId, "status" => array("NEQ", PrjOrderModel::STATUS_NOT_PAY), "is_regist" => 0))->field("SUM(rest_money) as tmoney")->find();
        $money = $moneyArr['tmoney'];
        $endTime = $prjInfo["end_bid_time"];
        $lastRepayDate = $prjInfo["last_repay_date"];
        $curDate = strtotime(date('Y-m-d', $lastRepayDate));
        $endTime = strtotime(date('Y-m-d', $endTime));
        $income = service("Financing/Project")->profitComputeByDate($activityInfo['reward_money_rate_type'], $activityInfo['reward_money_rate'] * 10, $endTime, $curDate, $money);
        return $income;
    }

    /**
     * 获取活动奖励
     * @param $prjId
     * @param $money
     * @return int
     */
    function getActivityMoney($prjId,$money,$prjInfo=array()){
        if(!$prjInfo) $prjInfo = service("Financing/Project")->getDataById($prjId);
        $activityId = $prjInfo['activity_id'];
        $activityInfo = D("Application/RewardRule")->getRewardRuleInfo($activityId);
        if($activityInfo['reward_money_rate'] <=0) return 0;
        $endTime = $prjInfo["end_bid_time"];
        $lastRepayDate = $prjInfo["last_repay_date"];
        $curDate = strtotime(date('Y-m-d',$lastRepayDate));
        $endTime = strtotime(date('Y-m-d',$endTime));
        $income = service("Financing/Project")->profitComputeByDate($activityInfo['reward_money_rate_type'],$activityInfo['reward_money_rate']*10,$endTime,$curDate,$money);
        return $income;
    }


    function getActivityMoneyNow($prjId,$money,$prjInfo=array()){
        if(!$prjInfo) $prjInfo = service("Financing/Project")->getDataById($prjId);
        $activityId = $prjInfo['activity_id'];
        $activityInfo = D("Application/RewardRule")->getRewardRuleInfo($activityId);
        if($activityInfo['reward_money_rate'] <=0) return 0;
        
        //如果是不满足奖励条件,收益就要去掉金额(因为没有拿到收益)
        if($activityInfo['min_invest_money']>$money){
            return 0;
        }
        $endTime = $prjInfo["end_bid_time"];
        $lastRepayDate = $prjInfo["last_repay_date"];
//        $curDate = strtotime(date('Y-m-d'));
//        $curDate = $curDate>$lastRepayDate?$lastRepayDate:$curDate;
        $curDate = $lastRepayDate;
        $endTime = strtotime(date('Y-m-d',$endTime));
        $income = service("Financing/Project")->profitComputeByDate(
            $activityInfo['reward_money_rate_type'],
            $activityInfo['reward_money_rate']*10,
            $endTime,
            $curDate,
            $money);
        return $income >0 ? $income:0;
    }
    
    /**
     * 计算某项目订单的当前收益
     * @param type $prj_order_id
     */
    public function getPrjOrderCurrentYield($orderId, $activitRate, $activitRateType)
    {
        return $this->getSuccessIncome($orderId, $activitRate, $activitRateType);
    }
            
    function isTransExpire($prjId, $is_transfer_id=FALSE){
        $endTime = D("Financing/AssetTransfer")->getTransEndTime($prjId, FALSE, $is_transfer_id);
//        echo date('Y-m-d',$endTime)."<br>";
        return $endTime < time() ? 1 : 0;
    }

    function isTransExpireOld($prjId){
        $endTime = D("Financing/AssetTransfer")->getTransEndTimeOld($prjId);
//        echo date('Y-m-d',$endTime)."<br>";
        return $endTime<time()?1:0;
    }
    
    
    /**
     * 可转让产品详情
     *
     * @param int $ord_id 购买订单Id
     * @return mixed
     */
    public function getOrderDetail($ord_id = 0)
    {
        D('Financing/PrjOrder');
        D('Financing/AssetTransfer');
        $now = time();
        $where = array(
            'po.id' => $ord_id,
        );
        $join = array(
            'LEFT JOIN fi_prj pj ON pj.id = po.prj_id',
            'LEFT JOIN fi_manage_fund jj ON jj.prj_id = po.prj_id',
        );
        $fields = array(
            'pj.end_bid_time',
            'pj.prj_name',
            'pj.rate',
            'pj.rate_type',
            'pj.time_limit',
            'pj.time_limit_unit',
            'po.id',
            'po.status',
            'po.out_order_no',
            'po.prj_id',
            'po.prj_type',
            'po.money', // 持有份额
            'po.rest_money', // 剩余份额
            'po.tran_freeze_money', // 待转让份额
            'po.ctime',
            'po.possible_yield',
            'po.repay_principal',
            'po.yield',
            'po.freeze_time',
            'jj.value_date',
        );

        $oModel = M();
        $detail = $oModel->table('fi_prj_order po')
            ->where($where)
            ->join($join)
            ->field($fields)
            ->find();
        // 		echo $oModel->getLastSql();
        if ($detail) {
            $detail['ord_id'] = $detail['id'];
            $detail['rest_money_display'] = humanMoney($detail['rest_money']);
            $detail['tran_freeze_money_display'] = humanMoney($detail['tran_freeze_money']);
        }
        return $detail;
    }


    /**
     * 执行产品转让
     *
     * @param int $uid 转让者uid
     * @param int $ord_id 购买订单Id
     * @param int $property 转让持有份额
     * @param int $money 转让价格
     * @param string $transfer_no 转让编号
     * @return array
     */
    public function doTransfer($uid = 0, $ord_id = 0, $property = 0, $money = 0, $transfer_no = '')
    {
        try {
            return D('Financing/AssetTransfer')->doTransfer($uid, $ord_id, $property, $money, $transfer_no);
        } catch (Exception $e) {
            return array(0, $e->getMessage());
        }
    }


    /**
     * 取消转让
     *
     * @param $uid
     * @param $transfer_id
     * @return array
     */
    public function cancelTransfer($uid, $transfer_id)
    {
        try {
            return D('Financing/AssetTransfer')->cancelTransfer($uid, $transfer_id);
        } catch (Exception $e) {
            return array(0, $e->getMessage());
        }
    }


    /**
     * 删除过期转让
     *
     * @param $uid
     * @param $transfer_id
     * @return array
     */
    public function delTransfer($uid, $transfer_id)
    {
        try {
            return D('Financing/AssetTransfer')->delTransfer($uid, $transfer_id);
        } catch (Exception $e) {
            return array(0, $e->getMessage());
        }
    }


    /**
     * 计算受益权价值
     *
     * @param $ord_ctime                购买订单创建时间
     * @param int $end_bid_time 结束投标时间
     * @param int $value_date 起息日（基金）
     * @param int $time_limit 期限
     * @param string $time_limit_unit 期限单位
     * @param string $rate_type 利率类型
     * @param int $rate 利率
     * @param int $money 持有份额
     * @return string
     */
    public function calGain($ord_ctime, $end_bid_time, $value_date, $time_limit=0, $time_limit_unit='', $rate_type='', $rate=0, $money=0,$prjId=0) {
        $srvProject = service('Financing/Project');

        $gain = $gain = $srvProject->getIncome($ord_ctime, $end_bid_time, $value_date, $time_limit, $time_limit_unit, $rate_type, $rate, $money,$prjId);
        $gain += $money;
        $gain = humanMoney($gain, 2, FALSE);

        return $gain;
    }


    /**
     * UBSP借款人列表API
     *
     * @param int $type 1: 企业, 2: 个人
     * @param int $p 第几页
     * @param string $keyword 搜索词
     * @return mixed    成功就返回全部数据，包括分页数据
     */
    public function UBSPBorrower($type = 1, $p = 1, $keyword = '')
    {
        $row = self::API_UBSP_PAGESIZE;

        $data = array(
            'searchBean.type' => $type,
            'searchBean.name' => $keyword,
            'page' => $p,
            'rows' => $row
        );

        $url = C('UBSP_API_URL') . 'investFinance/investFinance_listBorrowerInfo.jhtml';
        $res = postData($url, $data);
        $res = json_decode($res, TRUE);
        if (!$res || !$res['isSuccess']) {
            $error_message = '获取借款人列表出错，请稍后再试！';
            if ($res['error']) {
                $error_message .= '<br>错误信息：' . $res['error'];
            }
            throw_exception($error_message);
        }

        return $res;
    }


    /**
     * UBSP个人客户信息
     *
     * @param $id       即PROJECT_ID
     * @return mixed    成功就返回data部分
     */
    public function UBSPPerson($id)
    {
        $data = array(
            'custId' => $id,
        );
        $url = C('UBSP_API_URL') . 'investFinance/investFinance_getPersonInfo.jhtml';
        $res = postData($url, $data);
        $res = json_decode($res, TRUE);
        if (!$res || !$res['isSuccess']) {
            $error_message = '获取个人客户信息出错，请稍后再试！';
            if ($res['error']) {
                $error_message .= '<br>错误信息：' . $res['error'];
            }
            throw_exception($error_message);
        }

        return $res['data'];
    }


    /**
     * UBSP项目信息
     * 2013/11/21 + COUNTER_GUARANTEE：反担保措施
     *
     * @param $id       即CUST_ID
     * @return mixed    成功就返回data部分
     */
    public function UBSPProject($id)
    {
        $data = array(
            'projectId' => $id,
        );
        $url = C('UBSP_API_URL') . 'investFinance/investFinance_getProjectInfo.jhtml';
        $res = postData($url, $data);
        $res = json_decode($res, TRUE);
        if (!$res || !$res['isSuccess']) {
            $error_message = '获取项目信息出错，请稍后再试！';
            if ($res['error']) {
                $error_message .= '<br>错误信息：' . $res['error'];
            }
            throw_exception($error_message);
        }

        return $res['data'];
    }


    /**
     * 根据uid获取用户单位Id和发布机构，供产品发布/修改使用
     * 发布机构=单位名称+部门名称
     *
     * @param $uid
     * @return array
     */
    public function getPublishInst($uid)
    {
        $dept_id = 0;
        $dept_name = '';
        $member_name = '';

        // 部门Id
        $user = M('user')->find($uid);
        if ($user) {
            $dept_id = $user['dept_id'];
        }

        // 部门名称
        $dept = M('org_dept')->find($dept_id);
        if ($dept) {
            $dept_name = $dept['dept_name'];
        }

        // 单位名称
//         $member = M('member')->find($user['mi_id']);
//         if($member) {
//             $member_name = $member['mi_name'];
//         }
        $member_name = "中新力合股份有限公司";

        return array(
            'dept_id' => $dept_id,
            'dep_name' => $dept_name,
            'member_name' => $member_name,
            'publish_inst' => $member_name . $dept_name,
        );
    }


    /**
     * 根据工商注册号获取YRZDB那边的证据条目
     *
     * @param $corp_register_no 工商注册号
     * @param int $prj_id 有则直接入库
     * @return bool
     */
    public function getDPCorpData($corp_register_no, $prj_id = 0)
    {
        $url = C('YRZDB_API_URL') . 'index.php?app=api&mod=DPCorpData&act=getIFCorpEvList&register_no=' . $corp_register_no;
        $res = postData($url);
        $res = json_decode($res, TRUE);
        if (!$res || !$res['boolen']) {
            return FALSE;
        }

        if ($prj_id) {
            $srvProjectAudit = service('Application/ProjectAudit');
            foreach ($res['data'] as $name) {
                try {
                    $srvProjectAudit->addRecord($prj_id, $name);
                } catch (Exception $e) {
                }
            }
        }
        return $res['data'];
    }

    // 项目基本信息
    function getYrzdbPrjInfo($corp_register_no, $corp_id=0)
    {
        $url = C('YRZDB_API_URL') . 'index.php?app=api&mod=DPCorpData&act=getIFCorpInfoByRegNo&register_no=' . $corp_register_no . '&corp_id=' . $corp_id;
        $result = postData($url);
        $result = preg_replace('/^.*?\{/', '{', $result);
        $result = json_decode($result, true);
        if ($result['boolen'] != 1) {
            MyError::add('获取项目信息出错，请稍后再试！');
            return false;
        }
        $data = $result['data'];
        $survey = array();
        foreach ($data['survey_info'] as $v) {
            $survey[] = $v['demand_type_name'];
        }
        $data['survey'] = $survey;
        return $data;
    }

    // 贷后调查情况
    function getYrzdbSurvey($ubspId, $ubspNo)
    {
        $url = C('YRZDB_API_URL') . "index.php?app=api&mod=DPCorpData&act=getLoanedTaskByUbspPID&ubspId=" . $ubspId . "&project_no=" . $ubspNo;
        $result = postData($url);
        $result = json_decode($result, true);
        if ($result['boolen'] != 1) {
            MyError::add('获取项目信息出错，请稍后再试！');
            return false;
        }
        $data = $result['data'];
        return $data;
    }

    // /investFinance/investFinance_listCustFinanceInfo.jhtml
    function listCustFinanceInfo($custId, $page, $rows)
    {
        $data = array(
            'custId' => $custId,
            'pageNum' => $page,
        );
        if (!$custId) $data = '';

        $url = C('UBSP_API_URL') . 'investFinance/investFinance_listCustFinanceInfo.jhtml';
        $res = postData($url, $data);
        $res = json_decode($res, TRUE);
        if (!$res || !$res['isSuccess']) {
            $error_message = '获取信息出错，请稍后再试！';
            if ($res['error']) {
                $error_message .= '<br>错误信息：' . $res['error'];
            }
            MyError::add($error_message);
            return false;
        }

        return $res['data'];
    }


    // 实际线上/线下融资
    public function getOnline($prj_id = 0, $project = NULL, $is_regist = 0)
    {
        $mdPrjOrder = D('Financing/PrjOrder');
        $mdPrj = D('Financing/Prj');
        $srvProject = service('Financing/Project');

        if (is_null($project)) $project = $mdPrj->where(array('id' => $prj_id))->find();
        if (!$project) return 0;

        if($project['have_children']) $ids = $srvProject->getChildrenId($prj_id);
        else $ids = array($prj_id);
        $where_online = array(
            'prj_id' => array('IN', $ids),
            'is_regist' => $is_regist,
            'status' => array("NEQ", PrjOrderModel::STATUS_NOT_PAY),
        );
        $result = $mdPrjOrder->where($where_online)->field('(sum(rest_money)+sum(repay_principal)) AS total')->find();
        if (!$result) return 0;
        $total_online = $result['total'];
        return $total_online;
    }

    // 实际线上/线下融资
    public function getOnline2($prj_id = 0, $project = NULL, $is_regist = 0)
    {
        $mdPrjOrder = D('Financing/PrjOrder');
        $mdPrj = D('Financing/Prj');

        if (is_null($project)) $project = $mdPrj->where(array('id' => $prj_id))->find();
        if (!$project) return 0;

        $sql = "SELECT (sum(rest_money)+sum(repay_principal)) AS total FROM fi_prj_order
              where prj_id = '" . $project['id'] . "' AND is_regist = " . $is_regist . " AND
               ((status != " . PrjOrderModel::STATUS_NOT_PAY . " AND transfer=0) OR (status = " . PrjOrderModel::STATUS_NOT_PAY . " AND transfer>0))";
        $result = M()->query($sql);
        if (!$result) return 0;
        $total_online = $result[0]['total'];
        return $total_online;
    }

    // 平台管理费
    public function getFee($prj_id = 0, $project = NULL, $total_online = NULL, $is_actual_fee = FALSE)
    {
        if((int)M('prj_ext')->where(array('prj_id' => $prj_id))->getField('prj_deposit_status') > 0) return 0; // 车贷

        $mdPrj = D('Financing/Prj');

        if (is_null($project)) $project = $mdPrj->where(array('id' => $prj_id))->find();
        if (!$project) return 0;
//        if($project['business_type'] == 2) return 0;

        // 判断是否支付完了
        $mdPrjOrder = D('Financing/PrjOrder');
        $order_not_payed = $mdPrjOrder->where(array('prj_id' => $project['id'], 'status' => PrjOrderModel::STATUS_FREEZE))->find();
        if ($project['end_bid_time'] < time() && !$order_not_payed && !$is_actual_fee) return 0;

        if ($project['pay_way'] != PrjModel::PAY_WAY_EVERY_DAY_IN_BIDING && $project['platform_fee']) {
            $fee = $project['platform_fee'];
        } else {
            if (is_null($total_online)) $total_online = $this->getOnline($prj_id);
            $Pay = service('Financing/Project')->getTradeObj($prj_id, 'Pay');
            $Pay->debug = true;
            if (!$Pay) {
                throw_exception(MyError::lastError());
            }
            $fee = $Pay->getfee(array('prj_id' => $prj_id, 'project' => $project, 'total_online' => $total_online));
        }

        return $fee;
    }

    // 已支付总金额，包括总费用
    public function getPrjPayed($prj_id = 0, $project = NULL)
    {
        $mdPrj = D('Financing/Prj');
        $mdCashoutApply = D('Payment/CashoutApply');

        $ret = array(
            'total' => 0,
            'total_fee' => 0,
        );

        if (is_null($project)) $project = $mdPrj->where(array('id' => $prj_id))->find();
        if (!$project) return $ret;

        $result = $mdCashoutApply->where(array('prj_id' => $prj_id, 'status' => CashoutApplyModel::STATUS_SUCCESS))->field('SUM(money) AS total, SUM(fee) as total_fee')->find();
        if ($result) {
            $ret['total'] = $result['total'] + 0;
            $ret['total_fee'] = $result['total_fee'] + 0;
        }
        return $ret;
    }

    // 需支付额度
    public function getWillPay($prj_id = 0, $project = NULL, $total_online = NULL, $fee = NULL, $payed = NULL, $income_biding = NULL)
    {
        $mdPrj = D('Financing/Prj');

        if (is_null($project)) $project = $mdPrj->where(array('id' => $prj_id))->find();
        if (!$project) return 0;

        $mdPrjOrder = D('Financing/PrjOrder');

        if (is_null($total_online)) $total_online = $this->getOnline($prj_id, $project);
//        if ($project['business_type'] == 2) {  //票据业务财务支付，募集多少就支付多少不计平台服务费
//                $will_pay = $total_online;
//                return $will_pay;
//        }

        if ($project['remaining_amount'] > 0) { // 未满标, 计算平台管理费的时候要减去16点后投资的钱
            $where_online = array(
                'prj_id' => $prj_id,
                'is_regist' => 0,
                'status' => PrjOrderModel::STATUS_FREEZE,
                'ctime'=>array('GT', service('Financing/Project')->getBalanceTime(date('Y-m-d'), $prj_id)),
            );
            $result = $mdPrjOrder->where($where_online)->field('SUM(rest_money) AS total')->find();
            if ($result['total']) $total_online -= $result['total']; //如果16：00以后有费用 需要把这个也减掉
        }

        /*if(is_null($fee)) */
        $fee = $this->getFee($prj_id, $project, $total_online, TRUE);
        if (is_null($payed)) $payed = $this->getPrjPayed($prj_id, $project);

        // 一次性支付且已支付，则直接返回0
        if ($project['pay_way'] == PrjModel::PAY_WAY_AFTER_FULL && $payed['total'] > 0) return 0;

        $will_pay = $total_online - $fee - $payed['total'] /* - $payed['total_fee']*/
        ;
//        echo "［id:{$prj_id}, total_online: {$total_online}, fee: {$fee}, total_payed: {$payed['total']}, total_fee_payed: {$payed['total_fee']}］<br>";
        if ($project['end_bid_time'] < time() || $project['pay_way'] == PrjModel::PAY_WAY_AFTER_FULL || $project['bid_status'] == PrjModel::BSTATUS_FULL) {
            if (is_null($income_biding)) {
                $income_detail = $this->getIncomeDetail($prj_id, FALSE, $project);
                $income_biding = $income_detail['total_biding'];
            }
            $will_pay = $will_pay + $project['deposit'] - $income_biding;
        }
        if ($will_pay < 0) $will_pay = 0;
        return $will_pay;
    }

    // 收益详情
    public function getIncomeDetail($prj_id = 0, $only_total = FALSE, $project = NULL)
    {
        // 到期还本付息
        if (is_null($project)) $project = M('Prj')->where(array('id' => $prj_id))->find();
//        if(service("Financing/Invest")->isEndDate($project['repay_way'])) { // TODO: 下面已经分别实现针对此情况做了实现，可以废除
//            $result = service("Application/ProjectPay")->getPrjIncome($prj_id);
//            if($only_total) return $result['totalIncome'];
//            return array(
//                'total_income' => $result['totalIncome'],
//                'biding_list' => NULL,
//                'total_biding' => $result['totalBidTimeIncome'],
//                'total_other' => $result['totalOtherIncome'],
//            );
//        }

        $mdPrjOrder = D('Financing/PrjOrder');
        $mdPrjOrderRepayPlan = D('Financing/PrjOrderRepayPlan');
        $mdPrjRepayPlan = D('Financing/PrjRepayPlan');
        $srvProject = service('Financing/Project');
        $srvInvest = service('Financing/Invest');

        $is_enddate = $srvInvest->isEndDate($project['repay_way']);
        // 总收益
        $total_income = 0;
        if ($is_enddate) {
            $where_online = array(
                'prj_id' => $prj_id,
                'is_regist' => 0,
                'status' => array("NEQ", PrjOrderModel::STATUS_NOT_PAY),
            );
            $result = $mdPrjOrder->where($where_online)->field('SUM(possible_yield) AS total')->find();
            $total_income = $result['total'];
        } else {
            $where = array(
                'prj_id' => $prj_id,
                'status' => array('NEQ', PrjOrderRepayPlanModel::ORDER_REPAYMENT_STATUS_FAIL),
            );
            $result = $mdPrjOrderRepayPlan->where($where)
                ->field('SUM(yield) AS total')
                ->order('repay_date ASC')
                ->find();
            $total_income = (int)$result['total'];
        }
        if ($only_total) return $total_income;

        // 募集期收益
        $total_biding = 0;
        if ($is_enddate) {
            $where_online = array(
                'prj_id' => $prj_id,
                'is_regist' => 0,
                'status' => array("NEQ", PrjOrderModel::STATUS_NOT_PAY),
            );
            $list = $mdPrjOrder->where($where_online)->select();
            if ($list) {
                foreach ($list as $row) {
                    $total_biding += max(0, $srvProject->getIncomeByTime($prj_id, $row['rest_money'], $row['freeze_time'], $project['end_bid_time']));
                }
            }
        } else {
            $where = array(
                'repay_periods' => 0,
                'prj_id' => $prj_id,
                'status' => array('NEQ', PrjOrderRepayPlanModel::ORDER_REPAYMENT_STATUS_FAIL),
            );
            $result = $mdPrjOrderRepayPlan->where($where)
                ->field('SUM(yield) AS total')
                ->order('repay_date ASC')
                ->find();
            $total_biding = (int)$result['total'];
        }

        // 募集期详细
        $biding_list = NULL;
        if ($total_biding && !$is_enddate) {
            $project = M('Prj')->where(array('id' => $prj_id))->find();
            $where = array(
                'rp.repay_periods' => 0,
                'rp.prj_id' => $prj_id,
                'rp.status' => array('NEQ', PrjOrderRepayPlanModel::ORDER_REPAYMENT_STATUS_FAIL),
//                'po.ctime' => array('ELT', $project['end_bid_time']),
//                'po.status' => PrjOrderModel::STATUS_PAY_SUCCESS,
            );
            $orderby = 'rp.repay_date ASC';
            $fields = array(
                'rp.repay_date',
                'SUM(rp.yield) AS yield',
                'po.ctime',
                "FROM_UNIXTIME(po.ctime, '%Y-%m-%d') AS otime",
            );
            $join = array(
                'LEFT JOIN fi_prj_order AS po ON rp.prj_order_id = po.id',
            );
            $oModel = M();
            $biding_list = $oModel->table('fi_prj_order_repay_plan AS rp')
                ->where($where)
                ->join($join)
                ->field($fields)
                ->order($orderby)
                ->group('otime')
                ->select();
//            echo $oModel->getLastSql();
        }

        // 其他
        $total_other = $total_income - $total_biding;
        return array(
            'total_income' => $total_income,
            'biding_list' => $biding_list,
            'total_biding' => $total_biding,
            'total_other' => $total_other,
        );
    }

    // 算订单本息收益(每月等额本息还款的项目，本息收益显示每月收款金额 还款结束后，本息收益一栏显示总项目的收益)
    public function getOrderIncome($order_id = 0, $order = NULL)
    {
        $mdPrjOrder = D('Financing/PrjOrder');
        $mdPrj = D('Financing/Prj');

        if (is_null($order)) $order = $mdPrjOrder->where(array('id' => $order_id))->find();
        if (!$order) return 0;
        $project = $mdPrj->where(array('id' => $order['prj_id']))->find();
        if (!$project) return 0;

        // 默认情况下
        $income = $order['rest_money'] + $order['possible_yield'];

        // 每月等额本息还款的情况
        if ($project['repay_way'] == PrjModel::REPAY_WAY_PERMONTH) {
            if ($project['bid_status'] != PrjModel::BSTATUS_REPAID) {
                $order_repay_plan = service("Financing/Invest")->getLastOrderPlan($order['id']); // 最后一期还款成功的订单
                $income = $order_repay_plan['pri_interest'] + 0;
            } else {
                $repayment = M('person_repayment')->where(array('order_id' => $order['id']));
                if (!$repayment) return 0;
                $income = $repayment['capital'] + $repayment['profit'];
            }
        }

        return $income;
    }

    // 获取订单还款进度
    public function getOrderRepayProgress($order_id = 0, $order = NULL, $project = NULL)
    {
        $mdPrjOrder = D('Financing/PrjOrder');
        $mdPrj = D('Financing/Prj');
        $mdPrjOrderRepayPlan = D('Financing/PrjOrderRepayPlan');
        $ret = array(
            'repayed' => 0,
            'total' => 0,
        );

        if (is_null($project)) {
            if (is_null($order)) $order = $mdPrjOrder->where(array('id' => $order_id))->find();
            if (!$order) return FALSE;
            $project = $mdPrj->where(array('id' => $order['prj_id']))->find();
            if (!$project) return FALSE;
        }
//        if($project['bid_status'] != PrjModel::BSTATUS_REPAY_IN) return FALSE;

        // !M 2014/03/19 斜杠前后都不包括募集期
        $where = array(
            'prj_order_id' => $order['id'],
            'repay_periods' => array('NEQ', 0),
            'status' => PrjOrderRepayPlanModel::ORDER_REPAYMENT_STATUS_SUCCESS,
        );
        $result = $mdPrjOrderRepayPlan->where($where)->field('COUNT(*) AS total')->find();
        $ret['repayed'] = (int)$result['total'];

        $where = array(
            'prj_order_id' => $order['id'],
            'repay_periods' => array('NEQ', 0),
        );
        $result = $mdPrjOrderRepayPlan->where($where)->order('repay_periods DESC')->find();
        $ret['total'] = (int)$result['repay_periods'];

        return $ret;
    }

    // 项目是否支付过
    public function isPrjPayed($prj_id) {
        $mdCashoutApply = D('Payment/CashoutApply');
        return !!$mdCashoutApply->where(array('prj_id' => $prj_id))->find();
    }

    // 间隔天数
    public function diffDays($start_time, $end_time) {
        $t1 = strtotime(date('Y-m-d', $start_time));
        $t2 = strtotime(date('Y-m-d', $end_time));
        return ($t2 - $t1)/86400 + 1;
    }

    public function getDeposit($prj_id, $project=NULL) {
        $mdPrj = D('Financing/Prj');
        if (is_null($project)) $project = $mdPrj->where(array('id' => $prj_id))->find();
        if (!$project) return 0;

//        $deposit = max(0, service('Financing/Project')->getIncomeByTime($prj_id, $project['demand_amount'], $project['start_bid_time'], $project['end_bid_time']));
//        $deposit = $project['demand_amount'] * 0.001;
        if($project['value_date_shadow'] == 1) {
            $deposit = $project['demand_amount'] * ($this->diffDays($project['start_bid_time'], $project['end_bid_time']) - 1) * ($project['year_rate'] / 1000) / 365;
        } else {
            $deposit = $project['demand_amount'] * ($this->diffDays($project['start_bid_time'], $project['end_bid_time'])) * ($project['year_rate'] / 1000) / 365;
        }

        return $deposit;
    }


    // 流标
    public function rebackPrj($prj_id, $desc = '')
    {
        try {
            return D('Financing/Prj')->rebackPrj($prj_id, $desc);
        } catch (Exception $e) {
            return array(0, $e->getMessage());
        }
    }

    //线上实际融资
    public function getOnlineActual($prj_id)
    {
        $ret = array();
        $mdPrj = D('Financing/Prj');
        $project = $mdPrj->where(array('id' => $prj_id))->find();
        if (!$project) return $ret;

        $mdPrjOrder = D('Financing/PrjOrder');

        $where_online = array(
            'prj_id' => $prj_id,
            'is_regist' => 0,
            'status' => array("NEQ", PrjOrderModel::STATUS_NOT_PAY),
        );
        if ($project['pay_way'] == PrjModel::PAY_WAY_AFTER_FULL) {
            $orders = $mdPrjOrder->where($where_online)->field('sum(rest_money) AS total, FROM_UNIXTIME(ctime, "%Y-%m-%d") as dateline')->group('FROM_UNIXTIME(ctime, "%Y-%m-%d")')->select();
            foreach ($orders as $order) {
                $ret[] = array(
                    'date' => $order['dateline'],
                    'total' => $order['total'],
                );
            }
            return $ret;
        }


        // 募集期每天支付
        $orders = $mdPrjOrder->where($where_online)->order('ctime ASC')->select();
        if (!$orders) return $ret;

        $mdCashoutApply = D('Payment/CashoutApply');
        $where = array(
            'prj_id' => $prj_id,
            'status' => CashoutApplyModel::STATUS_SUCCESS,
        );
        $_paylist = $mdCashoutApply->where($where)->order('ctime ASC')->select();
        $paylist = array();
        foreach ($_paylist as $pay) {
            $key = date('Y-m-d', $pay['ctime']);
            $paylist[$key] = $pay;
        }

        $srvProject = service('Financing/Project');
        foreach ($orders as $order) {
            $ymd = date('Y-m-d', $order['ctime']);
            $today =  $srvProject->getBalanceTime($ymd, $prj_id);
            $yesterday = $today - 86400;
            $tomorrow = $today + 86400;

            if ($order['ctime'] > $yesterday && $order['ctime'] <= $today) $key = $ymd;
            else $key = date('Y-m-d', $tomorrow);

            $payed = $paylist[$key];
            $pay_time = $srvProject->getBalanceTime($key, $prj_id);

            $key_left = date('Y-m-d', $pay_time - 86400);
            $payed_left = $paylist[$key_left];
            $pay_time_left = $srvProject->getBalanceTime($key_left, $prj_id);

            if (!isset($ret[$key])) {
                $ret[$key]['date'] = $key;
                $ret[$key]['total'] = 0;
                if ($payed) {
                    $ret[$key]['pay_before'] = 0;
                    $ret[$key]['pay_after'] = 0;
                }
            }
            $ret[$key]['total'] += $order['rest_money'];

            if ($payed) {
                $ret[$key]['pay_before'] += $order['rest_money'];
                if ($order['ctime'] > $pay_time && $order['ctime'] <= $payed['ctime']) $ret[$key]['pay_after'] += $order['rest_money'];
            }
            if ($payed_left) {
                if ($order['ctime'] > $pay_time_left && $order['ctime'] <= $payed_left['ctime']) $ret[$key_left]['pay_after'] += $order['rest_money'];
            }
        }

        return $ret;
    }

    function getTitle($prjInfo, $isFed = 0,$is_show_title=true)
    {
        if (!$prjInfo) return "";

        if(!$prjInfo['prj_type']||!$prjInfo['rate']||!$prjInfo['rate_type']) return "";
        $title = "";

        if($is_show_title){
            $title = "title=\"";
        }

        $title .= "按" . $prjInfo['rate_type_view'] . "计息&#13;预期" . $prjInfo['rate_type_view'] . "利率：" . $prjInfo['rate_view'] . " " . $prjInfo['rate_symbol'];

        if ($prjInfo['prj_type'] == PrjModel::PRJ_TYPE_A):
            if (($prjInfo['huodong'] == 1) && (!$prjInfo['transfer_id'])):
                return "年化收益率" . number_format($prjInfo['year_rate'] / 10, 2) . "% +奖励年化收益6%";
            endif;
        endif;
        //活动
        if($prjInfo['activity_id'] && (!$prjInfo['transfer_id']) && !$isFed){
			$activityInfo = D("Application/RewardRule")->getRewardRuleInfo($prjInfo['activity_id']);
            if($activityInfo['status']==2) return $title;
            if($is_show_title){
                $title = "title=\"";
            }
            $title .= "年化收益率".number_format($prjInfo['year_rate']/10,2)."%";
            if($activityInfo['reward_money_rate_number']>0){
                $reward_money_rate = $activityInfo['reward_money_rate_number'];
                if ($activityInfo['reward_money_rate_type'] == 'month') {
                    $reward_money_rate = $reward_money_rate * 12;
                }

                if ($activityInfo['reward_money_rate_type'] == 'day') {
                    $reward_money_rate = $reward_money_rate * 365 / 10;
                }

                $title .= " +奖励年化收益".number_format($reward_money_rate/10,2,".","")."%";
            }
            if ($activityInfo['reward_free_money_rate'] > 0) {
                $title .= " +免费提现额度" . ($activityInfo['reward_free_money_rate'] / 10) . "%";
            }

            if ($activityInfo['reward_free_money_value'] > 0) {
                $title .= " +免费提现额度" . humanMoney($activityInfo['reward_free_money_value'], 2, false) . "元";
            }

            if ($activityInfo['reward_cashout_times'] > 0) {
                $title .= " +提现次数" . $activityInfo['reward_cashout_times'] . "次";
            }

        }
        if (!isset($prjInfo['rate_type_view'])) $prjInfo['rate_type_view'] = service("Financing/Project")->getRateType($prjInfo['rate_type']);
        if (!isset($prjInfo['rate_symbol'])) $prjInfo['rate_symbol'] = $prjInfo['rate_type'] == 'day' ? '‰' : '%';
        import_app("Financing.Model.PrjModel");
        if (!isset($prjInfo['rate_view'])) {
            if ($prjInfo['rate_type'] != PrjModel::RATE_TYPE_DAY) {
                $prjInfo['rate_view'] = number_format($prjInfo['rate'] / 10, 2);
            } else {
                $prjInfo['rate_view'] = number_format($prjInfo['rate'], 2);
            }
        }
        if($is_show_title){
            $title .= "\"";
        }

        return $title;
    }

    function getIco($info,$isFed=0, $prj_id=0){
        //兼容已存在的图标获取
        $ret = $this->getIcoNew($info, $isFed, $prj_id);
        return $ret;
    }

    /**
     * 合法合规图标获取
     * @param $info
     * @param int $isFed
     * @param int $prj_id
     * @return string
     */
    function getIcoNew($info,$isFed=0, $prj_id=0){
        if(!$info) return "";
        if(isset($info['transfer_id'])||!$isFed){
            $isFed = $info['transfer_id'] ? 1:0;
        }

        $str = array();
        $url = U('Help/Help/viewHelpIndex',array('tpl'=>'AboutUs-partners'));
        if($info['prj_class'] != PrjModel::PRJ_CLASS_LC){
        	if($info['safeguards']==0){
        		$str[] = " <a class=\"baoSIcn\" href=\"".$url."\" title=\"100%担保本金\"><em class='icn_s'></em></a>";
        	}elseif($info['prj_business_type'] == prjModel::PRJ_BUSINESS_TYPE_G || $info['project']['prj_business_type'] == prjModel::PRJ_BUSINESS_TYPE_G) {//2015/11/11央企融
                $title = "央企商票质押";
                $str[] = "<a class=\"baoSIcn\" title=\"$title\"><em class='icn_s'></em></a>";
            } elseif($info['prj_business_type'] == prjModel::PRJ_BUSINESS_TYPE_H) {
                $title = "风险准备金";
                $str[] = "<a class=\"baoSIcn\" title=\"$title\"><em class='icn_s'></em></a>";
            }elseif($info['safeguards_name2']){
                $title =  $info['safeguards_name2'];
                if($info['prj_type'] == PrjModel::PRJ_TYPE_H){
                    $title = $info['safeguards_name2'];
                }
                $str[] = "<a class=\"baoSIcn\" href=\"".$url."\" title=\"".$title."\"><em class='icn_s'></em></a>";
            }else{
                $title =  "100%担保本金和利息";
                if($info['prj_type'] == PrjModel::PRJ_TYPE_H){
                    $title = "100%本息权益保障";;
                }
                $str[] = "<a class=\"baoSIcn\" href=\"".$url."\" title=\"".$title."\"><em class='icn_s'></em></a>";
            }

        }
        // 可变现图标是否显示
        if (service("Financing/FastCash")->is_prj_cash($prj_id)) {
            $str[] = "<a href='javascript:;' class=\"zhuanchuIcn\" title=\"60天后可变现\"><em class='icn_s'></em></a>";
        }

        //鑫整点
        if($info['act_prj_ext']['is_xzd']==1){
            $str[] = "<a href='javascript:;' class=\"xinzhengdianIcon\" title=\"鑫整点\"><em class='icn_s'></em></a>";
        }

        if(($info['max_bid_amount']>0) && (!$info['transfer_id'])){
            $show = humanMoney($info['max_bid_amount'],2,false)."元";
            $str[] = "<a href='javascript:;' class=\"xianIcn\" title=\"该标的最高投资金额".$show."\"><em class='icn_s'></em></a>";
        }
        if($info['is_new']){
            $str[] = "<a href='javascript:;' class=\"newGuestIcn\" title=\"新注册用户享有一次投资机会\">新客</a>";
        }
        if ($info['huodong'] == 1 && (!$info['transfer_id']) && !$isFed) {
            $str[] ="<a href='javascript:;' class=\"activeIcn\" title=''>活动</a>";
        }

        //活动
        if ($info['activity_id'] && (!$info['transfer_id']) && !$isFed) {
            $activityInfo = D("Application/RewardRule")->getRewardRuleInfo($info['activity_id']);
            $default_icon = true;
            if ($activityInfo['icon']) {
                $attach = $this->getActivityIcon($activityInfo['icon']);
                $url  = $attach['origin'];
                $title = $this->getActivityIconTitle($info['activity_id'], $info['order_id']);
                $title = $title ? $title : $activityInfo['name'];
                if ($url) {
                    $str[] = "<a href='javascript:;' class=\"a-act-icn\"><img height=\"20px\" src=\"" . $url . "\" alt=\"" . $activityInfo['name'] . "\" title=\"" . $title . "\"></a>";
                    $default_icon = false;
                }else{
                    $default_icon = false;
                    $style = "";
                    $str[] = "<a href='javascript:;' class=\"activeIcn\" {$style}  title=\"" . $activityInfo['name'] . "\">活动</a>";
                }
            }
            if ($default_icon) {
                $style = $activityInfo['icon'] ? "style=\"background: url(" . $activityInfo['icon'] . ") no-repeat 0 0;\"" : "";
                $str[] = "<a href='javascript:;' class=\"activeIcn\" {$style}  title=\"" . $activityInfo['name'] . "\">活动</a>";
            }
        }

        //渠道专享投放客户端
        if($prj_id>0){
            $client_type_prj_id = $prj_id;
        }else{
            $client_type_prj_id = $info['id'];
        }
        if(!$isFed) {
            $client_types = service("Index/Index")->getClientTypeArr($client_type_prj_id);
            $icon_array = array(
                '1'=>"<a href='javascript:;' class='computerIcn' title='网站端专享项目，仅在网站端可以投资'><em class='icn_s'></em></a>",
                '2'=>"<a href='javascript:;' class='weiIcn' title='微信专享项目，仅在微信端WAP端可以投资'><em class='icn_s'></em></a>",
                '3'=>"<a href='javascript:;' class='phonesIcn' title='手机端专享项目，仅在手机端可以投资'><em class='icn_s'></em></a>"
            );
            foreach ($client_types as $client_type) {
                if(array_key_exists($client_type, $icon_array)) $str[] = $icon_array[$client_type];
            }
        }

        if ($info['act_prj_ext']['is_deposit'] || $info['is_deposit']) {
            $str[] = "<a href='javascript:;' class=\"zsIcn\" title=\"存管标\"><em class='icn_s'></em></a>";
        }
//        $x_prj_match_model = D('Financing/XPrjMatching');
//        if ($x_prj_match_model->getPrjMatchedRecordsCnt($info['id'])) {
//            $str[] = "<a href='javascript:;' class=\"smxx\" title=\"司马小鑫\" style=\"cursor: inherit;\"><em class='icn_s'></em></a>";
//        }

        // $temp = implode('<i class="lineIcons">|</i>', $str);
        $temp = implode($str);
        return '<span class="classIcons">'.$temp.'</span>';
    }


    /**
     * 获取活动相关
     */
    function getActivityView($activityId)
    {
        $activityInfo = D("Application/RewardRule")->getRewardRuleInfo($activityId);
        if (!$activityInfo) return false;
        if (!$activityInfo['status'] == 2) return false;

        $result = $activityInfo;
        if ($activityInfo['reward_money_percent'] > 0) {
            $result['hongbao'] = ($activityInfo['reward_money_percent'] / 10) . "%";
        }

        if($activityInfo['reward_money_rate_number']>0){
            $reward_money_rate = $activityInfo['reward_money_rate_number'];
            if ($activityInfo['reward_money_rate_type'] == 'month') {
                $reward_money_rate = $reward_money_rate * 12;
            }

            if ($activityInfo['reward_money_rate_type'] == 'day') {
                $reward_money_rate = $reward_money_rate * 365 / 10;
            }

            $result['hongbao'] = number_format($reward_money_rate/10,10,2,".","")."%";

        }


        $jiangli = "";
//        $jiangli = "<span  class=\"org\">";
        if ($activityInfo['reward_free_money_rate'] > 0) {
            $jiangli .= " 免费提现额度<em class=\"f14\">" . ($activityInfo['reward_free_money_rate']) . "</em>%";
        }

        if ($activityInfo['reward_free_money_value'] > 0) {
            $jiangli .= " 免费提现额度<em class=\"f14\">" . humanMoney($activityInfo['reward_free_money_value'], 2, false) . "</em>元";
        }

        if ($activityInfo['reward_cashout_times'] > 0) {
            $jiangli .= " 提现次数<em class=\"f14\">" . $activityInfo['reward_cashout_times'] . "</em>次";
        }
//        $jiangli .= "</span>";
        $result['jiangli'] = $jiangli;
        $result['url'] = $activityInfo['url'];
        
        
        $result['tips_show'] = service('Account/Active')->getActiveTips($activityInfo);
        
        return $result;
    }

    function getRateShow($prjInfo, $isFed = 0, $hasStyle = 0, $isg = 0,$is_echo=true, $extraParams = array())
    {	
    	if(array_key_exists('r_year_rate', $extraParams)){
    		$prjInfo['year_rate'] = $extraParams['r_year_rate'];
    	}
    	
        $ret = $this->getRateData($prjInfo, $isFed);
//        $yearRate = $ret['year_rate'];
        $activityYearRate = $ret['activity_year_rate'];
        $totalYear = $ret['total_year_rate'];

        if (!$hasStyle) {
            $str = $totalYear;
            if($is_echo){
                echo $str;
                return '';
            }
            return $str;
        } else {
            $rateCss = "rate";
            $style = "envelopes";
            if ($isFed) {
                if ($prjInfo['transfer_status'] == AssetTransferModel::PRJ_TRANSFER_BIDED) {
                    $rateCss = 'rate rate_g';
                }
            } else {
                if (($prjInfo['bid_status'] >= PrjModel::BSTATUS_FULL) && $isg) {
                    $style = "envelopes envelopes_end";
                    $rateCss = 'rate rate_g';
                }
            }
            $str = "<p class=\"" . $rateCss . "\">" . $totalYear . "</p>";

            if ($activityYearRate) $str .= "<span class=\"" . $style . "\"><em class=\"em\"></em>含 " . $activityYearRate . "红包</span>";
            if($is_echo){
                echo $str;
                return '';
            }
            return $str;
        }
    }

    /**
     * 获取利率数据
     * @param $prjInfo
     * @param int $isFed
     */
    function getRateData($prjInfo, $isFed = 0)
    {
        $yearRate = $prjInfo['year_rate'];
        if ($isFed) {
            $activityYearRate = 0;
        } else {
            if (!$prjInfo['transfer_id'] && $prjInfo['activity_id']) {
                $activityYearRate = service("Account/Active")->getOnlyActiveYearRate($prjInfo);
                $reward_rule  = D("Application/RewardRule")->where(array('id'=>$prjInfo['activity_id']))->field(array('reward_money_action'))->find();
            } else {
                $activityYearRate = 0;
            }
        }
        $ret = array();
        $totalYear = $activityYearRate + $yearRate;
        $ret['year_rate'] = number_format($yearRate / 10, 2) . "%";
        $ret['activity_year_rate'] = $activityYearRate ? number_format($activityYearRate / 10, 2) . "%" : 0;
        if($prjInfo['add_rate']){
            $totalYear+=$prjInfo['add_rate'];
        }
        $ret['total_year_rate'] = number_format($totalYear / 10, 2) . "%";
        $ret['reward_money_action'] = $reward_rule['reward_money_action'];
        return $ret;
    }


    public function getOrderIncomeTotal($order_id = 0, $order = NULL, $project = NULL, $ymd = NULL)
    {
        $mdPrjOrder = D('Financing/PrjOrder');
        $mdPrj = D('Financing/Prj');
        $mdPrjOrderRepayPlan = D('Financing/PrjOrderRepayPlan');
        $srvInvest = service('Financing/Invest');

        if (is_null($order)) $order = $mdPrjOrder->where(array('id' => $order_id))->find();
        if (!$order) return 0;
        if (is_null($project)) $project = $mdPrj->where(array('id' => $order['prj_id']))->find();
        if (!$project) return 0;

//        if($ymd && $project['bid_status'] == PrjModel::BSTATUS_REPAID) return 0; // 还款结束的，当期本息＝0
        if (!is_numeric($ymd)) $ymd = strtotime($ymd);

        // 默认情况下
        $income = $order['rest_money'] + $order['possible_yield'];

        // 每月等额本息还款的情况
        if (!$srvInvest->isEndDate($project['repay_way'])) {
            $where = array(
                'prj_order_id' => $order_id,
                'status' => array('NEQ', PrjOrderRepayPlanModel::ORDER_REPAYMENT_STATUS_FAIL),
            );
            if ($ymd) $where['repay_date'] = $ymd;

            $order_repay_plan = $mdPrjOrderRepayPlan->where($where)->field('SUM(pri_interest) as TOTAL')->find();
            if (!$order_repay_plan) return 0;
            $income = $order_repay_plan['TOTAL'];
        }

        return $income;
    }

    public function isPrjLimitAmount($prj_id, $project = NULL)
    {
        if (is_null($project)) {
            $mdPrj = D('Financing/Prj');
            $project = $mdPrj->where(array('id' => $prj_id))->find();
        }

        return $project['max_bid_amount'] > 0 && !$project['transfer_id'];
    }

    public function isHaveRepayPlan($id, $is_order_id = FALSE, $project = NULL)
    {
        if ($project) {
            D('Financing/Prj');
            return !(($project['prj_type'] == PrjModel::PRJ_TYPE_A) && ($project['repay_way'] == PrjModel::REPAY_WAY_E) && (date('Y-m-d', $project['start_bid_time']) == date('Y-m-d', $project['end_bid_time'])));
            //if ($project['repay_way'] == PrjModel::REPAY_WAY_ENDATE || $project['prj_type'] == PrjModel::PRJ_TYPE_A && date('Ymd', $project['start_bid_time']) == date('Ymd', $project['end_bid_time'])) return FALSE;
        }
        return true;
        $mdPrjOrderRepayPlan = D('Financing/PrjOrderRepayPlan');
        $where = array(
            'status' => array('NEQ', PrjOrderRepayPlanModel::ORDER_REPAYMENT_STATUS_FAIL),
        );
        if ($is_order_id) $where['prj_order_id'] = $id;
        else $where['prj_id'] = $id;

        return !!$mdPrjOrderRepayPlan->where($where)->find();
    }

    // 活动信息
    function getActivity($activity_id)
    {
        $mdRewardRule = D('Application/RewardRule');
        $activity = $mdRewardRule->getRewardRuleInfo($activity_id);
        if (!$activity) return FALSE;

        $tips = array();
        $rate_type_mapping = array(
            'day' => '日',
            'month' => '月',
            'year' => '年',
        );
        $detail = array(
            'title' => '',
            'value' => '',
            'formula' => '',
            'desc' => '',
        );
        if ($activity['is_reward_free_money']) {
            $title = '免费提现额度';
            $desc = '项目待还款，免费提现额度于1个工作日内打入用户账户内';
            $_detail = $detail;
            $_detail['title'] = $title;
            $_detail['desc'] = $desc;
            if ($activity['reward_free_money_percent']) {
                $rate = $activity['reward_free_money_rate'];
                $rate_symbol = '‰';
                if ($activity['reward_free_money_rate_type'] != 'day') {
                    $rate /= 10;
                    $rate_symbol = '%';
                }
                $vaule = "{$rate}{$rate_symbol}";
                $_detail['value'] = $vaule;
                $title .= $vaule;
            } else {
                $vaule = humanMoney($activity['reward_free_money_value'], 2, FALSE) . '元';
                $_detail['value'] = $vaule;
                $title .= $vaule;
            }

            $tip = array($title, $desc);
            if (GROUP_NAME == 'Weixin') $tip[] = $_detail;
            $tips[] = $tip;
        }

        if ($activity['is_reward_cashout_times']) {
            $title = "提现次数{$activity['reward_cashout_times']}次";
            $desc = '项目待还款，提现次数于1个工作日内打入用户账户内';
            $_detail = $detail;
            $_detail['title'] = $title;
            $_detail['desc'] = $desc;

            $tip = array($title, $desc);
            if (GROUP_NAME == 'Weixin') $tip[] = $_detail;
            $tips[] = $tip;
        }

        if ($activity['is_reward_money']) {
        	if($activity['min_invest_money']){
        		
        		$rate = $activity['reward_money_rate'];
        		$rate_symbol = '‰';
        		if ($activity['reward_money_rate_type'] != 'day') {
        			$rate_symbol = '%';
        		}
        		$vaule = "{$rate}{$rate_symbol}";
        		
        		$title = "奖励年化收益";
        		$desc = "单笔投资≥".($activity['min_invest_money'] / 100)."元奖励年化利率{$vaule}";
        		$_detail = $detail;
        		$_detail['title'] = $title;
        		$_detail['desc'] = $desc;
        		$_detail['value'] = $vaule;
        		$title .= $vaule;
        		
        	}else{
        		$title = '奖励年化收益';
        		$desc = '投资到期后，红包于24小时内打入用户账户';
        		$_detail = $detail;
        		$_detail['title'] = $title;
        		$_detail['desc'] = $desc;
        		if ($activity['reward_money_rate']) {
        			$rate = $activity['reward_money_rate'];
        			$rate_symbol = '‰';
        			$rate_type = $rate_type_mapping[$activity['reward_money_rate_type']];
        			if ($activity['reward_money_rate_type'] != 'day') {
        				$rate_symbol = '%';
        			}
        			$vaule = "{$rate}{$rate_symbol}";
        			$formula = "本金*{$rate}{$rate_symbol}/{$rate_type}*投资期限";
        			$_detail['value'] = $vaule;
        			$_detail['formula'] = $formula;
        			$title .= "{$vaule} ({$formula})";
        		} else {
        			$vaule = humanMoney($activity['reward_money_value'], 2, FALSE) . '元';
        			$_detail['value'] = $vaule;
        			$title .= $vaule;
        		}
        	}
            
            $tip = array($title, $desc);
            if (GROUP_NAME == 'Weixin') $tip[] = $_detail;
            $tips[] = $tip;
        }
        $attact_info = $this->getActivityIcon($activity['icon']);
        $icon_url = "";
        $big_icon_url = "";
        if ($attact_info) {
            $icon_url = $attact_info['origin'];
            $big_icon_url = $attact_info['big'];
        }
        $activity['icon_url'] = $icon_url;
        $activity['big_icon_url'] = $big_icon_url;
        $activity['tips'] = $tips;
        return $activity;
    }


    public function getActivityIcon($icon,$size = 'origin')
    {
        if (empty($icon) || (int)$icon == 0) {
            return "";
        }
        $ret = D("Public/Attach")->getAttachUrlById($icon);
        return $ret;
    }

    public function getActivityIconTitle($activity_id = 0, $order_id = 0)
    {
        //2015双十一活动
        $service = service('Activity/NovEleventh');
        if ($activity_id == $service->getActivityId()) {
            list($rid, $gift) = $service->getReceiverByOrderId($order_id);
            if ($gift) {
                return '参与0元趴收获' . $gift['full_name'];
            }
        }

        return '';
    }


    public function getUserAmount($uid, $is_deposit = 0) {
        $srvPayAccount = service('Payment/PayAccount');
        $account = $srvPayAccount->getBaseInfo($uid);

        $real_amount = $is_deposit ? 'zs_amount' : 'amount';
        $real_reward_money = $is_deposit ? 'zs_reward_money' : 'reward_money';
        $amount = $account[$real_amount] + $account[$real_reward_money] + $account['invest_reward_money'] + $account['coupon_money'];

        return $amount;
    }


    /**
     * 是否余额不足
     *
     * @param $uid
     * @param int $id   项目或转让Id
     * @param bool $is_transfer_id FALSE表示项目id, TRUE表示转让id
     * @param null $obj
     * @return bool     TRUE表示余额不足
     */
    public function isBlanceLess($uid, $id, $is_transfer_id=FALSE, $obj=NULL, $a_coupon_money=-1,$pre_coupon_money=-1,$rewardAmount=0) {

        if (!isset($obj['act_prj_ext']['is_deposit'])) {
            $is_deposit = M('prj_ext')->where(['prj_id' => $id])->getField('is_deposit');
        } else {
            $is_deposit = $obj['act_prj_ext']['is_deposit'];
        }

        $amount = $this->getUserAmount($uid, $is_deposit);
        if($is_transfer_id) {
            if(is_null($obj)) $transfer = D('Financing/AssetTransfer')->where(array('id' => $id))->find();
            else $transfer = $obj;
            $money = $transfer['money'];
        } else {
            if(is_null($obj)) $project = M('prj')->where(array('id' => $id))->find();
            else $project = $obj;
            
            if($a_coupon_money>=0) {
	            $srvPayAccount = service('Payment/PayAccount');
	            $account = $srvPayAccount->getBaseInfo($uid);
	            $amount = $amount - $account['coupon_money'] + $a_coupon_money;
            }
            //如果有传值，就用  否则重新计算
            if ($pre_coupon_money == -1) {
                try {
                    $pre_coupon_money = 0;
//                    $max_invest_money = $this->getMaxInvestMoney($uid, $id, $project);
                    if($rewardAmount){
                        $pre_coupon_money = $rewardAmount;
                    }
//                    $defaultReward = service('Financing/ReduceTicket')->getDefaultReward($uid, $id,
//                        $project['time_limit_day'], $max_invest_money, 0);
//                    if ($defaultReward['recommend']['selected']['original_num']) {
//                        $pre_coupon_money = $defaultReward['recommend']['selected']['original_num'];
//                    }
                } catch (Exception $e) {
                    $pre_coupon_money = 0;
                }
            }
            //其他的红包系统支持(营销1.0的新奖励)
            if ($pre_coupon_money > 0) {
                $amount = $amount + $pre_coupon_money;
            }

            if($project['remaining_amount'] < $project['min_bid_amount']){
                    $money = $project['remaining_amount'];
            }
            else $money = $project['min_bid_amount'];
        }
        return (int)$amount < (int)$money;
    }


    /**
     * 最大可投金额
     *
     * @param $uid
     * @param $prj_id
     * @param null $project
     * @return float|int
     */
    public function getMaxInvestMoney($uid, $prj_id, $project=NULL, $a_coupon_money=-1, $state=0,$rewardMoney=0) {
        if(is_null($project)) $project = M('prj')->where(array('id' => $prj_id))->find();
        if(!$project) return 0;

        $balance = $this->getUserAmount($uid, (int) $project['act_prj_ext']['is_deposit']);
        if($a_coupon_money == -1){
            $coulist = service("Financing/Project")->getPrjCouponList($uid, 0, $project);
            $a_coupon_money = 0;
            foreach ($coulist as $couEle) {
                $a_coupon_money += $couEle['amount'];
            }
        }
        if($a_coupon_money>=0) {
        	$srvPayAccount = service('Payment/PayAccount');
        	$account = $srvPayAccount->getBaseInfo($uid);
        	$balance = $balance - $account['coupon_money'] + $a_coupon_money;
        }
        if($state){
            $pre_coupon_money = $rewardMoney;
        }else{
            $pre_coupon_money = service("Financing/BaseInvestRule")->getAllBonusAmount($uid, $prj_id);
        }
        if ($pre_coupon_money) {
            $balance = $balance + $pre_coupon_money;
        }
        if(!$balance) {
            return 0;
        }

        if($project['prj_type'] == PrjModel::PRJ_TYPE_H){
            $serFastCash = service("Financing/FastCash");
            $money = $serFastCash->getMaxInvestMoney($project['min_bid_amount'],$project['step_bid_amount'],$project['remaining_amount'],$balance);
            if($money === FALSE){
               $money = 0;
            }
            return $money;
        } else {
            $min_bid_amount = $project['min_bid_amount'];
            $prj_ext = M("prj_ext")->field("is_pre_sale,demand_amount_pre")->where("prj_id=".$prj_id)->find();
            if($prj_ext['is_pre_sale'] == 1 && $project['bid_status']==PrjModel::BSTATUS_WATING){
                $total = M("prj_order_pre")->field("sum(money) as total")->where(array('prj_id' => $prj_id, 'uid' => $uid))->find();
                $total_bid_money = $total['total'];
                $max_bid_amount = $project['max_bid_amount'];
                $remaining_amount = $project['demand_amount'] - $prj_ext['demand_amount_pre'];
                if (!$max_bid_amount) {
                    $max_bid_amount = $project['demand_amount'] - $prj_ext['demand_amount_pre'];
                } else {
                    $max_bid_amount = $project['max_bid_amount'] - $total_bid_money;
                };
            }else {
                $total_bid_money = 0;
                $result_total_money = M('prj_order')->field('money')->where(array('prj_id' => $prj_id, 'uid' => $uid, '_string' => 'xprj_order_id is null or xprj_order_id = 0'))->select();
                if ($result_total_money) {
                    foreach ($result_total_money as $key => $money_val) {
                        $total_bid_money += $money_val['money'];
                    }
                }

                $max_bid_amount = $project['max_bid_amount'];
                if (!$max_bid_amount) {
                    $max_bid_amount = $project['remaining_amount'];
                } else {
                    $max_bid_amount = $project['max_bid_amount'] - $total_bid_money;
                };
                $remaining_amount = $project['remaining_amount'];
            }
            if($remaining_amount <= $min_bid_amount){
                $money = $remaining_amount;
            }elseif($max_bid_amount <= min($balance,$remaining_amount)){
                $money = $max_bid_amount;
            }else {
                $money = floor((min($balance, $max_bid_amount, $remaining_amount) - $min_bid_amount) / $project['step_bid_amount']) * $project['step_bid_amount'] + $min_bid_amount;
//            $money = floor(min($balance,$max_bid_amount,$remaining_amount));
            }

            return min($money, $balance);
        }
    }


    /**
     * 获取项目还款计划
     *
     * @param $prj_id
     * @param null $project
     * @param bool $real_time_force 强制实时计算
     * @return bool|mixed
     */
    public function getReplayProjectRepayPlan($prj_id, $project=NULL, $real_time_force=FALSE) {
        $mdPrj = D('Financing/Prj');
        $mdPrjOrder = D('Financing/PrjOrder');
        $mdPrjRepayPlan = D('Financing/PrjRepayPlan');
        $srvProject = service('Financing/Project');
        if(is_null($project)) $project = $mdPrj->where(array('id' => $prj_id))->find();
        if(!$project) return FALSE;

        if($real_time_force || $project['bid_status'] < PrjModel::BSTATUS_REPAYING) {
            $obj = service("Financing/Project")->getTradeObj($prj_id, "Bid");
            if(!$obj) return FALSE;

            $where_online = array(
                'prj_id' => $prj_id,
                'is_regist' => 0,
                'status' => array('NEQ', PrjOrderModel::STATUS_NOT_PAY),
            );
            $result = $mdPrjOrder->where($where_online)->field('sum(rest_money) AS total')->find();
            $total_online = (int)$result['total'];

            if(is_numeric($real_time_force) && $real_time_force > 852048000) $end_bid_time = $real_time_force;
            else $end_bid_time = $srvProject->getAutoEndBidTime();

            $t = strtotime(date('Y-m-d', $end_bid_time));
            $balanceTime = $srvProject->getBalanceTime($end_bid_time, $prj_id);
            if($end_bid_time > $balanceTime) $t = strtotime('+1 days', $t); // T向后延迟一天
            $freeze_time = $srvProject->getPrjValueDate($prj_id, $t); // 计息日

            $params = array();
            $params['freeze_date'] = date('Y-m-d', $freeze_time);
            $params['capital'] = $total_online;
            $params['prj_id'] = $prj_id;
            $plans = $obj->getPersonRepaymentPlan($params);
            $now = time();
            $repay_plans = array();

            // 募集期
            $biding_income = service("Financing/Project")->getIncomeByTime($prj_id, $total_online, $project['start_bid_time'], $end_bid_time);
            $biding_repay_date = date('Y-m-d', strtotime('+1 days', $t)); // 募集期还款日
            $repay_plans[] = array(
                'prj_id' => $prj_id,
                'is_auto' => 1,
                'prj_repay_id' => 0,
                'repay_periods' => 0,
                'repay_date' => strtotime($biding_repay_date),
                'pri_interest' => $biding_income,
                'principal' => 0,
                'yield' => $biding_income,
                'rest_principal' => $total_online,
                'status' => PrjRepayPlanModel::PRJ_REPAYMENT_STATUS_WAIT,
                'ctime' => $now,
                'mtime' => $now,
            );
            foreach($plans as $plan) {
                $repay_plans[] = array(
                    'prj_id' => $prj_id,
                    'is_auto' => 0,
                    'prj_repay_id' => 0,
                    'repay_periods' => $plan['no'],
                    'repay_date' => strtotime($plan['repay_date']),
                    'pri_interest' => $plan['repay_money'],
                    'principal' => $plan['capital'],
                    'yield' => $plan['profit'],
                    'rest_principal' => $plan['last_capital'],
                    'status' => PrjRepayPlanModel::PRJ_REPAYMENT_STATUS_WAIT,
                    'ctime' => $now,
                    'mtime' => $now,
                );
            }
        } else {
            $where_prj_plan = array(
                'prj_id' => $prj_id,
                'status' => array('NEQ', PrjRepayPlanModel::PRJ_REPAYMENT_STATUS_FAIL),
            );
            $repay_plans = $mdPrjRepayPlan->where($where_prj_plan)->select();
        }

        return $repay_plans;
    }


    public function getPrjTimeLimitDays($prj_id, $project=NULL) {
        $mdPrj = D('Financing/Prj');
        if(is_null($project)) $project = $mdPrj->where(array('id' => $prj_id))->find();
        if(!$project) return 0;

        $t1 = $project['end_bid_time'];
        $t2 = strtotime($project['time_limit'] . $project['time_limit_unit'] . 's', $project['end_bid_time']);
        return $this->diffDays($t1, $t2) - 1;
    }


    public function isCanTransfer($prj_id, $project=NULL, $is_fed=FALSE){
        $mdPrj = D('Financing/Prj');
        if(is_null($project)) $project = $mdPrj->where(array('id' => $prj_id))->find();
        if(!$project) return FALSE;

        if(!$is_fed) $is_fed = !!$project['transfer_id'];
        if(!$is_fed) $is_can_trafser = isset($project['ext']['is_transfer']) && $project['ext']['is_transfer'];
        else $is_can_trafser = service('Financing/Transfer')->fedCanTransfer($project['transfer_id']);

        return $is_can_trafser;
    }


    // 计算某个订单的募集期利息
    public function getOrderIncomeBiding($order_id) {
        $order = M('order')->where(array('id' => $order_id))->find();
        if(!$order) return 0;
        $project = M('prj')->where(array('id' => $order['prj_id']))->find();
        if(!$project) return 0;

        $srvProject = service('Financing/Project');
        $income = $srvProject->getIncomeByTime($project['id'], $order['rest_money'], $order['freeze_time'], $project['end_bid_time']);
        return max(0, $income);
    }


    /**
     * 获取资金转入账户(以后都走这里)
     *
     * @param $prj_id
     * @param $fund_account, 通常是通过ProjectService->getFundAccount获取的
     * @return mixed
     */
    public function getFundAccount($prj_id, $fund_account=null) {
        $account = M('prj_ext')->where(array('prj_id' => $prj_id))->field('acount_name,account_no,bank,bank_name,sub_bank,sub_bank_id')->find();
        if($account['bank'] && $account['bank_name'] && $account['account_no']) {
            $account['is_custom'] = true;
            return $account;
        }
        if(!$fund_account) {
            $srvProject = service('Financing/Project');
            $project = $srvProject->getPrjInfo($prj_id);
            $account_id = $project['ext']['fund_account']['id'];

            if($project['zhr_apply_id']){
                if (!$account_id) {
                    return array();
                }
                $fund_account = M("loanbank_account")->where(array('id' => $account_id))->find();
            }else{
                $service = service("Financing/BindAccount");
                $fund_account = $service->getBindAccountById($account_id, $project['uid']);
            }
//            if($project['tenant_id']){
//                $fund_account = M('loanbank_account')->find($account_id);
//            } else {
//                $fund_account = M('fund_account')->find($account_id);
//            }
        }

        return $fund_account;
    }
}

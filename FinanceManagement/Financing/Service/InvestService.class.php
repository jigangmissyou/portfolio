<?php

import_app("Financing.Model.PrjModel");
import_app("Financing.Model.PrjOrderModel");
import_app("Financing.Model.PrjOrderRepayPlanModel");
import_app("Financing.Model.PrjRepayPlanModel");
class InvestService extends BaseService{
	
	function getPrjMoneyNote($prjId,$money, $prj_info=null){
		if($prj_info) $prjInfo = $prj_info;
		else $prjInfo = M("prj")->where(array("id"=>$prjId))->find();
		$yearRate = number_format($prjInfo['year_rate']/10,2)."%";
		$incomeMoney = service("Financing/Project")->getIncomeByTime($prjId,$money,time(),$prjInfo['end_bid_time'], $prjInfo);
		$moneyView = humanMoney($money,0)."元";
		$incomeMoneyView = humanMoney($incomeMoney,2,false)."元";
		return array("rateView"=>$yearRate,"incomeView"=>$incomeMoneyView,"moneyView"=>$moneyView);
	}
	
	/**
	 * 是否到期付款 
	 * @param unknown $repay_way
	 * @return boolean
	 */
	function isEndDate($repay_way){
		return $repay_way == PrjModel::REPAY_WAY_ENDATE;
	}
	
	//获取募集期数据
	function getBidTimeInfo($prjId){
        $srvProject = service("Financing/Project");
		$prjInfo = M("prj")->where(array("id"=>$prjId))->find();
		
		if($prjInfo['bid_status'] == PrjModel::BSTATUS_REPAID || $prjInfo['bid_status'] == PrjModel::BSTATUS_REPAY_IN || $prjInfo['bid_status'] == PrjModel::BSTATUS_REPAYING){
			$incomeWhere = array();
			$incomeWhere['repay_periods'] = 0;
            $incomeWhere['status'] = array("NEQ",PrjOrderRepayPlanModel::ORDER_REPAYMENT_STATUS_FAIL);
			$incomeWhere['prj_id'] = $prjId;
			$incomeInfo = M("prj_order_repay_plan")->field("SUM(yield) as yield")->where($incomeWhere)->find();	
// 			echo M("prj_order_repay_plan")->getLastSql();
			$incomeMoney = $incomeInfo['yield'];
            $_row = M('prj_order')->where(array('prj_id' => $prjId, 'status'=>array('NEQ', 4), 'is_regist' => 0))->field('SUM(money) AS TT')->find();
            $amounView = humanMoney($_row['TT'],2,false);
        }else{
			$incomeMoney = $srvProject->getIncomeByTime($prjId,$prjInfo['demand_amount'],$prjInfo['start_bid_time'],$prjInfo['end_bid_time']);
            $amounView = humanMoney($prjInfo['demand_amount'],2,false);
        }
        if(!$srvProject->isPrjHaveBidingIncoming($prjId)) $incomeMoney = 0; // 项目扩展配置是否有募集期利息

		$incomeMoneyView = humanMoney($incomeMoney,2,false);
		

        $totalAmount =  0+$incomeMoney;
		$totalAmountView =  humanMoney($totalAmount,2,false);


//		$t = strtotime(date('Y-m-d',$prjInfo['end_bid_time']));
//		$balanceTime = service("Financing/Project")->getBalanceTime($prjInfo['end_bid_time'],$prjInfo['id']);
//		if($prjInfo['end_bid_time']>$balanceTime) $t = strtotime("+1 days",$t);//T向后延迟一天
		
//		$repayDate = date('Y-m-d',strtotime("+1 days",$t));
        $repayDate = $prjInfo['next_repay_date'];
        if(in_array($prjInfo['repay_way'], array(PrjModel::REPAY_WAY_D, PrjModel::REPAY_WAY_PERMONTH, PrjModel::REPAY_WAY_PERMONTHFIXN, PrjModel::REPAY_WAY_HALFYEAR)) || !$repayDate || !$srvProject->isPrjVersionRepayPlanDelay($prjInfo['version'])) $repayDate = $srvProject->getPrjFirstRepayDate($prjId, $prjInfo);
        else $repayDate = date('Y-m-d', $repayDate);

        return array(
            "incomeView" => $incomeMoneyView,
            "income" => $incomeMoney,
            "amountView" => $amounView,
            'amount' => $prjInfo['demand_amount'],
            "repayDate" => $repayDate,
            "totalAmount" => $totalAmount,
            "totalAmountView" => $totalAmountView,
        );
    }
	
	/**
	 * 获取下一期需要还款的计划
	 * @param int $orderId
	 * @return mixed
	 */
	function getLastWillRepayOrderPlan($orderId){
		$now = strtotime(date('Y-m-d'));
		$where['pri_interest'] = array('gt', '0');
		$where['prj_order_id'] = $orderId;
		$where['status'] = PrjOrderRepayPlanModel::ORDER_REPAYMENT_STATUS_WAIT;
		$obj = M("prj_order_repay_plan");
		$info = $obj->where($where)->order("repay_date ASC")->find();
		// 		echo $obj->getLastSql();
		if(!$info) return array();
		return $info;
	}
	
	/**
	 * 获取最后一期
	 * @param unknown $orderId
	 * @return mixed
	 */
	function getLastOrderPlan($orderId){
		$now = strtotime(date('Y-m-d'));
		$where['prj_order_id'] = $orderId;
		$where['status'] = PrjOrderRepayPlanModel::ORDER_REPAYMENT_STATUS_SUCCESS;
		$obj = M("prj_order_repay_plan");
		$info = $obj->where($where)->order("repay_date DESC")->find();
		// 		echo $obj->getLastSql();
		if(!$info) return array();
		return $info;
	}

    /**
     * 获取最后一期
     * @param unknown $orderId
     * @return mixed
     */
    function getLastOrderWillPlan($orderId,$includeZero=1){
        $now = strtotime(date('Y-m-d'));
        $where['prj_order_id'] = $orderId;
        if(!$includeZero) $where['repay_periods'] = array("NEQ",0);
        $where['status'] = PrjOrderRepayPlanModel::ORDER_REPAYMENT_STATUS_WAIT;
        $obj = M("prj_order_repay_plan");
        $info = $obj->where($where)->order("repay_date ASC")->find();
        // 		echo $obj->getLastSql();
        if(!$info) return array();
        return $info;
    }

    function getLastPrjWillPlan($prjId){
        $now = strtotime(date('Y-m-d'));
        $where['prj_id'] = $prjId;
        $where['status'] = PrjRepayPlanModel::PRJ_REPAYMENT_STATUS_WAIT;
        $obj = M("prj_repay_plan");
        $info = $obj->where($where)->order("repay_date ASC")->find();
        // 		echo $obj->getLastSql();
        if(!$info) return array();
        return $info;
    }

    // 获取预估收益
    function getSpecialIncome($prj_id, $money, $freeze_time, $kwargs = array())
    {
        $is_have_biding = K($kwargs, 'is_have_biding', true); // 是否包含募集期利息
        $is_have_reward = K($kwargs, 'is_have_reward', false); // 是否包含红包利息
        $rate = K($kwargs, 'rate', 0); // 加息券id
        $user_ticket_only_unused = K($kwargs, 'user_ticket_only_unused', true); // 加息券计算默认只计算未使用的(如果是投资后就要传false)
        $order_id = K($kwargs, 'order_id', 0); // 订单Id
        $is_split = K($kwargs, 'is_split', false); // 是否分开返回
        $return_plans = K($kwargs, 'return_plans', false); // 是否返回还款计划列表

        $income_biding = 0; // 募集期利息
        $income_normal = 0; // 用款期利息
        $income_reward = 0; // 红包利息
        $income_jiaxi = 0; // 加息券利息
        $plans = array(); // 还款计划

        $is_repay_plan_created = false; // 是否已经生成还款计划了
        $prj_info = M('prj')->where(array('id' => $prj_id))->find();

        // 募集期
        if ($order_id && $prj_info['bid_status'] >= 2/*PrjModel::BSTATUS_BIDING*/ && service('Financing/Project')->isOrderRepayPlanAllCreated($prj_id)) {
            $is_repay_plan_created = true;
            $where = array(
                'prj_order_id' => $order_id,
                'status' => array('NEQ', PrjOrderRepayPlanModel::ORDER_REPAYMENT_STATUS_FAIL),
                'ptype' => PrjOrderRepayPlanModel::PTYPE_BIDING,
            );

            $mdPrjOrderRepayPlan = D('Financing/PrjOrderRepayPlan');
            if ($return_plans) {
                $_rows = $mdPrjOrderRepayPlan->where($where)->select();

                $income_biding = (int)$_rows[0]['yield'];
                if ($_rows) {
                    $plans[] = $_rows[0];
                }
            } else {
                $_row = $mdPrjOrderRepayPlan->where($where)->field('SUM(yield) AS CNT')->find();

                $income_biding = (int)$_row['CNT'];
            }
        } else {
            if ($is_have_biding && service('Financing/Project')->isPrjHaveBidingIncoming($prj_id)) {
                if (strtotime(date('Y-m-d', $freeze_time)) < strtotime(date('Y-m-d',
                        $prj_info['start_bid_time']))
                ) { // 预售才会有这情况
                    $freeze_time = $prj_info['start_bid_time'];
                }

                $income_biding = service('Financing/Project')->getIncomeByTime($prj_id, $money, $freeze_time,
                    $prj_info['end_bid_time']);
                $income_biding = $income_biding < 0 ? 0 : $income_biding;
            }
            if ($return_plans && $income_biding) {
                $plans[] = array(
                    'no' => 0,
                    'ptype' => 2,
                    'repay_date' => null, // 还款日期
                    'repay_money' => $income_biding, // 应收本息
                    'profit' => $income_biding, // 应收利息
                    'capital' => 0, // 应收本金
                    'last_capital' => $money, // 剩余本金
                );
            }
        }

        // 用款期
        if ($this->isEndDate($prj_info['repay_way'])) {
            $income_normal = service('Financing/Project')->getIncomeById($prj_id, $money);
        } else {
            if ($is_repay_plan_created) {
                $where = array(
                    'prj_order_id' => $order_id,
                    'status' => array('NEQ', PrjOrderRepayPlanModel::ORDER_REPAYMENT_STATUS_FAIL),
                    'ptype' => array('NEQ', PrjOrderRepayPlanModel::PTYPE_BIDING),
                );
                $mdPrjOrderRepayPlan = D('Financing/PrjOrderRepayPlan');
                if ($return_plans) {
                    $_rows = $mdPrjOrderRepayPlan->where($where)->select();

                    foreach ($_rows as $_row) {
                        $plans[] = $_row;
                        $income_normal += (int)$_row['yield'];
                    }
                } else {
                    $_row = $mdPrjOrderRepayPlan->where($where)->field('SUM(yield) AS CNT')->find();

                    $income_normal = (int)$_row['CNT'];
                }
            } else {
                $t = strtotime(date('Y-m-d', $prj_info['end_bid_time']));
                $balanceTime = service('Financing/Project')->getBalanceTime($prj_info['end_bid_time'], $prj_info['id']);
                if ($prj_info['end_bid_time'] > $balanceTime) {
                    $t = strtotime('+1 days', $t);
                }

                $obj = service('Financing/Project')->getTradeObj($prj_id, 'Bid');
                if (is_object($obj)) {
                    $freeze_date = date('Y-m-d', service('Financing/Project')->getPrjValueDate($prj_id, $t));
                    $period_params['freeze_date'] = $freeze_date;
                    $period_params['capital'] = $money;
                    $period_params['prj_id'] = $prj_id;
                    $period_result = $obj->getPersonRepaymentPlan($period_params);
                    if ($period_result) {
                        foreach ($period_result as $_row) {
                            $income_normal += $_row['profit'];
                            $plans[] = $_row;
                        }
                    }
                }
            }
        }

        // 红包奖励
        if ($is_have_reward && $prj_info['activity_id']) {
            if($order_id && $prj_info['bid_status'] >2  && service('Financing/Project')->isOrderRepayPlanAllCreated($prj_id)){
               $income_reward=M("prj_order_reward")->where(array("order_id"=>$order_id,"reward_type"=>PrjOrderRewardModel::REWARD_TYPE_RATE))->getField("amount");//回款后从数据库里取这个年化奖励的红包金额
            }else{
                $income_reward = service("Financing/Invest")->getRedBaoProfit($prj_info['activity_id'], $money,
                    strtotime(date('Y-m-d', $prj_info['end_bid_time'])),
                    strtotime(date('Y-m-d', $prj_info['last_repay_date'])));
            }

        }

        // 加息券收益
        if ($rate) {
            $income_jiaxi = D('Financing/AddRate')->getAmountRateAdd($money, $rate, $prj_id,
                $user_ticket_only_unused, $order_id);
        }

        // 修正还款计划生产前的募集期还款计划的还款日
        if (!$is_repay_plan_created && $income_biding && $plans) {
            $plans[0]['repay_date'] = $plans[1]['repay_date'];
        }

        $total = $income_normal + $income_biding + $income_reward + $income_jiaxi;
        if ($is_split) {
            return array(
                'income_normal' => $income_normal,
                'income_biding' => $income_biding,
                'income_reward' => $income_reward,
                'income_jiaxi' => $income_jiaxi,
                'total' => $total,
                'plans' => $plans,
                'is_repay_plan_created' => $is_repay_plan_created,
            );
        }

        return $total;
    }
	
	//一次性还付本息，添加还款数据 
	function addUserRepayPlan($orderId,$repayDate='',$money=''){
		$orderInfo = M("prj_order")->where(array("id"=>$orderId))->find();
        
        //司马小鑫匹配的直接不出理
        if ($orderInfo['xprj_order_id']) {
            return true;
        }
        
		$prjInfo = M("prj")->where(array("id"=>$orderInfo['prj_id']))->find();
        if($this->isEndDate($prjInfo['repay_way'])){
            $repayDate = date('Y-m-d',$prjInfo['last_repay_date']);
            $money = $orderInfo['money']+$orderInfo['possible_yield'];
        }else{
            $repayDate = is_numeric($repayDate)? date('Y-m-d',$repayDate):$repayDate;
        }

        if(!strtotime($repayDate) ) return true;

        $mdUserRepayPlan = M('user_repay_plan');
		$uid = $orderInfo['uid'];

        $cache_key = 'USER_REPAY_PLAN' . $uid. '_' . $repayDate;

        $info_id =S($cache_key);
        if($info_id == -100) {
            usleep(30000);
        }
        if(!$info_id) {
            $where=array();
            $where['uid'] = $uid;
            $where['repay_date'] = $repayDate;
            $where['type'] = 1;
            $info =$mdUserRepayPlan->where($where)->find();
            if($info) {
                $info_id = $info['id'];
                S($cache_key, $info_id, 86400);
            }
        }

		$now = time();
		if(!$info_id){
            S($cache_key, -100, 86400);
            $idata=array();
			$idata['uid'] = $uid;
			$idata['total_amount'] = $money;
			$idata['repay_date'] = $repayDate;
			$idata['prj_number'] = 1;
            $idata['type'] = 1;
			$idata['ctime'] = $now;
			$idata['mtime'] = $now;
            $info_id = $mdUserRepayPlan->add($idata);
            S($cache_key, $info_id, 86400);
		}else{
            $_where = array('id' => $info_id);
            $mdUserRepayPlan->where($_where)->setInc("total_amount",$money);

            //同一天有多少项目要还款
            $totalCount = $this->getTotalPrjRepayCount($repayDate,$uid);
            $mdUserRepayPlan->where($_where)->save(array("mtime"=>time(),"prj_number"=>$totalCount));
		}
		return true;
	}

    //已回款则减去
    function cutUserRepayPlan($orderId,$repayDate='',$money=''){
        $orderInfo = M("prj_order")->where(array("id"=>$orderId))->find();
        $uid = $orderInfo['uid'];
        $prjInfo = M("prj")->where(array("id"=>$orderInfo['prj_id']))->find();
        if($this->isEndDate($prjInfo['repay_way'])){
            $repayDate = date('Y-m-d',$prjInfo['last_repay_date']);
            $money = $orderInfo['money']+$orderInfo['possible_yield'];
        }else{
            $repayDate = is_numeric($repayDate)? date('Y-m-d',$repayDate):$repayDate;
        }

        $mdUserRepayPlan = M('user_repay_plan');
        $where=array();
        $where['uid'] = $uid;
        $where['repay_date'] = $repayDate;
        $where['type'] = 1;
        $info = $mdUserRepayPlan->where($where)->find();
        if(!$info){
            return true;
        }else{
            $_where = array('id' => $info['id']);
            //同一天有多少项目要还款
            //检查全部还完的情况
            $totalCount = $this->getTotalPrjRepayCount($repayDate,$uid);
            $mdUserRepayPlan->where($_where)->setDec("total_amount",$money);

            if($totalCount >0){
                $mdUserRepayPlan->where($_where)->save(array("mtime"=>time(),"prj_number"=>$totalCount));
            }else{
                $mdUserRepayPlan->where($_where)->delete();
            }

            // 如果回款是0就删掉
            $info = $mdUserRepayPlan->where($_where)->find();
            if($info && $info['total_amount'] <= 0) $mdUserRepayPlan->where($_where)->delete();
        }
        return true;
    }


    //已回款则减去
    function cutUserRepayPlanAmount($orderId,$repayDate='',$money=''){
        $orderInfo = M("prj_order")->where(array("id"=>$orderId))->find();
        $uid = $orderInfo['uid'];
        $prjInfo = M("prj")->where(array("id"=>$orderInfo['prj_id']))->find();
        if($this->isEndDate($prjInfo['repay_way'])){
            $repayDate = date('Y-m-d',$prjInfo['last_repay_date']);
            $money = $orderInfo['money']+$orderInfo['possible_yield'];
        }else{
            $repayDate = is_numeric($repayDate)? date('Y-m-d',$repayDate):$repayDate;
        }

        $mdUserRepayPlan = M('user_repay_plan');
        $where=array();
        $where['uid'] = $uid;
        $where['repay_date'] = $repayDate;
        $where['type'] = 1;
        $info = $mdUserRepayPlan->where($where)->find();
        if(!$info){
            return true;
        }else{
            $_where = array('id' => $info['id']);
            $mdUserRepayPlan->where($_where)->setDec("total_amount",$money);
        }
        return true;
    }

    function saveUserRepayPrjCount($orderId,$repayDate=''){
        $orderInfo = M("prj_order")->where(array("id"=>$orderId))->find();
        $uid = $orderInfo['uid'];
        $prjInfo = M("prj")->where(array("id"=>$orderInfo['prj_id']))->find();
        if($this->isEndDate($prjInfo['repay_way'])){
            $repayDate = date('Y-m-d',$prjInfo['last_repay_date']);
        }else{
            $repayDate = is_numeric($repayDate)? date('Y-m-d',$repayDate):$repayDate;
        }

        $where=array();
        $where['uid'] = $uid;
        $where['repay_date'] = $repayDate;
        $where['type'] = 1;
        $totalCount = $this->getTotalPrjRepayCount($repayDate,$uid);

        M('user_repay_plan')->where($where)->save(array("mtime"=>time(),"prj_number"=>$totalCount));
        return true;
    }

    //待还款数目
    function getTotalPrjRepayCount($repayDate,$uid){
        $repayDate = is_numeric($repayDate)? date('Y-m-d',$repayDate):$repayDate;
        $repayDate = strtotime($repayDate);
        $sqlEnddate = "SELECT COUNT(*) AS cnt FROM fi_prj_order WHERE prj_id IN(
                             SELECT id FROM fi_prj WHERE last_repay_date = '".$repayDate."'
                             AND repay_way ='".PrjModel::REPAY_WAY_ENDATE."' AND `status` = '".PrjModel::STATUS_PASS."'
                             AND ( bid_status != '".PrjModel::BSTATUS_REPAID."')
                             )
                     AND `status` !=".PrjOrderModel::STATUS_NOT_PAY." AND uid=".$uid." AND xprj_order_id = 0";
        $rs = M()->query($sqlEnddate);
        $endateCount = (int) $rs[0]['cnt'];
        //
//        $sqlFenqi = "select count(DISTINCT prj_id) as cnt FROM fi_prj_order_repay_plan
//                  WHERE repay_date='".$repayDate."'AND `status` = '".PrjOrderRepayPlanModel::ORDER_REPAYMENT_STATUS_WAIT."'
//                  AND exists(select 1 from fi_prj_order where fi_prj_order_repay_plan.prj_order_id=fi_prj_order.id and fi_prj_order.uid=".$uid.")";
        $sqlFenqi = "SELECT count(distinct rp.prj_order_id) AS cnt
FROM fi_prj_order_repay_plan rp
  LEFT JOIN fi_prj_order po ON po.id=rp.prj_order_id
WHERE rp.repay_date = {$repayDate} AND rp.`status` = " . PrjOrderRepayPlanModel::ORDER_REPAYMENT_STATUS_WAIT . "
  AND po.uid={$uid}
  AND po.status<=" . PrjOrderModel::STATUS_PAY_SUCCESS . " AND po.xprj_order_id = 0";
        $rs = M()->query($sqlFenqi);
        $fenqiCount =(int) $rs[0]['cnt'];
        $totalCount = $endateCount+$fenqiCount;
        return $totalCount;
    }

    /**
     * @param $prj
     * @param $actionType  1-部分还款，2-支付，3-募集期还款,4-全部还完
     */
    function updatePrjBstatus($prjId,$actionType){
        if(!$actionType) return true;
        $bstatus = 0;
        if($actionType == 1){
            $bstatus = PrjModel::BSTATUS_REPAY_IN;
        }elseif($actionType == 2){
            $bstatus = PrjModel::BSTATUS_REPAYING;
        }elseif($actionType == 3){
            $bstatus = PrjModel::BSTATUS_REPAYING;
        }elseif($actionType == 4){
            $bstatus = PrjModel::BSTATUS_REPAID;
        }
        if(!$bstatus) return true;
        return M("prj")->where(array("id"=>$prjId))->save(array("bid_status"=>$bstatus));
    }

    //还款计划
    function getRepayList($where='',$start=0,$limit=10,$getTotal=1, $where_prj_status=NULL){
            /*
            $sql = "SELECT *,
                 if(next_repay_date IS NULL,last_repay_date,next_repay_date) as new_next_repay_date
                FROM fi_prj WHERE bid_status in('".PrjModel::BSTATUS_REPAYING."','".PrjModel::BSTATUS_REPAY_IN."')
                AND transfer_id IS NULL
                ".$where."
                ORDER BY new_next_repay_date ASC
                LIMIT {$start},{$limit} ";
            */
        if(is_null($where_prj_status)) $where_prj_status = "prj.bid_status in('".PrjModel::BSTATUS_REPAYING."','".PrjModel::BSTATUS_REPAY_IN."')";
        $sql = "SELECT prj.max_bid_amount,prj.prj_type,prj.repay_way,prj.safeguards,prj.rate_type,prj.id,prj.spid,prj.prj_name,prj.bid_status,prj.huodong,prj.time_limit,prj.value_date_shadow,prj.transfer_id,prj.mi_no,prj.rate,prj.time_limit_unit,prj.have_children,prj.year_rate,prj.activity_id,
             if(prj_repay_plan.repay_date IS NULL,prj.last_repay_date,prj_repay_plan.repay_date) as new_next_repay_date
            FROM fi_prj prj
            LEFT JOIN fi_prj_repay_plan prj_repay_plan ON prj.id=prj_repay_plan.prj_id
            WHERE
            prj.transfer_id IS NULL
            AND
            (
                (
                   prj.repay_way = '".PrjModel::REPAY_WAY_ENDATE."'
                ) OR (
                    prj_repay_plan.status='".PrjOrderRepayPlanModel::ORDER_REPAYMENT_STATUS_WAIT."' AND prj.repay_way != '".PrjModel::REPAY_WAY_ENDATE."'
                    AND prj_repay_plan.repay_periods !=0
                 ) OR prj_repay_plan.status is NULL
            ) {$where} AND {$where_prj_status} ORDER BY new_next_repay_date ASC,prj.ctime ASC";
            if($start>0 || $limit >0) $sql .="  LIMIT {$start},{$limit}";
//        echo $sql;
           $list = M()->query($sql);
            if(!$list) return array();
            $data = array();
            $allTotalAmount=0;//本金
            $allTotalIncome=0;//利息
            $allActivity_money=0;//年化红包
            $allTotalOtherIncome=0;//用款期利息
            $allTotalBidTimeIncome=0;//募集期利息

            //发布站点
            $mi_list = D('Application/Member')->MemberListNokey();

            foreach($list as $k=>$v){
                $data[$k]['id'] = $v['id'];
                $data[$k]['have_children'] = $v['have_children'];
                $data[$k]['spid'] = $v['spid'];

                //投放站点
                $data[$k]['mi_name'] = $mi_list[$v['mi_no']]['mi_name'];

                $data[$k]['activity_id'] = $v['activity_id'];
                $data[$k]['prj_id'] = $v['id'];
                $time_limit_unit_name = service("Financing/Project")->getTimeLimitUnit($v['time_limit_unit']);
                $data[$k]['time_limit_unit_view'] = $time_limit_unit_name== "月" ? $v['time_limit']."个月" :  $v['time_limit'].$time_limit_unit_name;
                $data[$k]['rate_type_view'] = service("Financing/Project")->getRateType($v['rate_type']);
                $data[$k]['rate_symbol'] = $v['rate_type'] == PrjModel::RATE_TYPE_DAY ? '‰' : '%';
                $data[$k]['repay_way_view'] = service("Financing/Project")->getRepayWay($v['repay_way']);
                $data[$k]['year_rate'] = $v['year_rate'];
                $data[$k]['prj_type_view'] = service("Financing/Project")->getPrjTypeName($v['prj_type']);
                $data[$k]['rate_view'] = $v['rate_type'] != PrjModel::RATE_TYPE_DAY ? number_format($v['rate']/10,2):number_format($v['rate'],2);
                $data[$k]['new_next_repay_date'] = date('Y-m-d',$v['new_next_repay_date']);
                $data[$k]['prj_name'] = $v['prj_name'];
                $data[$k]['prj_type'] = $v['prj_type'];
                $data[$k]['huodong'] = $v['huodong'];
                $data[$k]['bid_status'] = $v['bid_status'];
				$data[$k]['transfer_id'] = $v['transfer_id'];
				$data[$k]['activity_id'] = $v['activity_id'];
				$data[$k]['max_bid_amount'] = $v['max_bid_amount'];
				$data[$k]['transfer_id'] = $v['transfer_id'];
                $data[$k]['safeguards'] = $v['safeguards'];
                $data[$k]['ext'] = service("Financing/Project")->getExt($v['id']);

                $data[$k]['activity_money'] = 0 ;
                $data[$k]['activity_money_view'] = humanMoney($data[$k]['activity_money'],2,false);

                //判断还款金额
                if($v['repay_way'] == PrjModel::REPAY_WAY_ENDATE){
                    //总金额
                    $totalAmountTmp = M("prj_order")->field("SUM(rest_money) as rest_money")
                                      ->where(array("prj_id"=>$v['id'],"is_regist"=>0,"status"=>array("NEQ",PrjOrderModel::STATUS_NOT_PAY)))->find();
//                    echo M("prj_order")->getLastSql()."<br>";
                    $totalAmount = $totalAmountTmp['rest_money'];
                    $totalIncomeInfo = service("Application/ProjectPay")->getPrjIncome($v['id']);
                    //总收益
                    $totalIncome = $totalIncomeInfo['totalIncome'];
                    //可用期收益
                    $totalOtherIncome =$totalIncomeInfo['totalOtherIncome'];
                    //募集期收益
                    $totalBidTimeIncome = $totalIncomeInfo['totalBidTimeIncome'];
                    //需还款金额
                    $totalMoney = $totalAmount+$totalIncomeInfo['totalIncome'];

                }else{
                        $repayDate = $v['new_next_repay_date'];
                        $repayInfo = M("prj_repay_plan")->where(array("prj_id"=>$v['id'],"repay_date"=>$repayDate, 'status' => 1))->find();

                    if($repayInfo['rest_principal']==0){ // 最后一次还款的时候
                        //活动
                        $data[$k]['activity_money'] = service("Financing/Financing")->getPrjActivityMoney($v['id'],$v['activity_id']);
                        $data[$k]['activity_money_view'] = humanMoney($data[$k]['activity_money'],2,false);
                    }

                     $totalAmount = $repayInfo['principal'];
                        //总收益
                      $totalIncome = $repayInfo['yield'];
                       //募集期利息
//                      $totalBidTimeInfo = M("prj_order_repay_plan")->where(array("repay_periods"=>0,
//                                                  "prj_id"=>$v['id'],
//                                                  "status"=>array("NEQ",PrjRepayPlanModel::PRJ_REPAYMENT_STATUS_FAIL))
//                                                )->find();
                    $totalBidTimeIncome = $repayInfo['repay_periods'] ==0 ? $totalIncome:0;
                      //可用期收益
                    $totalOtherIncome = 0;
                    //需还款金额
                    $totalMoney = $repayInfo['pri_interest'];
                }
                $allTotalAmount +=$totalAmount; //本金
                $allTotalIncome +=$totalIncome;//利息
                $allActivity_money +=$data[$k]['activity_money'];//年化红包
                $allTotalOtherIncome +=$totalOtherIncome;//用款期利息
                $allTotalBidTimeIncome +=$totalBidTimeIncome;//募集期利息


                $totalMoney = $totalMoney+$data[$k]['activity_money'];
                $totalIncome = humanMoney($totalIncome,2,false);
                $totalBidTimeIncome = humanMoney($totalBidTimeIncome,2,false);
                $totalOtherIncome = humanMoney($totalOtherIncome,2,false);
                $totalMoney = humanMoney($totalMoney,2,false);
                $totalAmount = humanMoney($totalAmount,2,false);

                $data[$k]['totalAmount'] = $totalAmount;
                $data[$k]['totalIncome'] = $totalIncome;
                $data[$k]['totalBidTimeIncome'] = $totalBidTimeIncome;
                $data[$k]['totalOtherIncome'] = $totalOtherIncome;
                $data[$k]['totalMoney'] = $totalMoney;
                $data[$k]['value_date_shadow'] = $v['value_date_shadow'];
            }

        $count = 0;
        if($getTotal){
            /*
            $sql = "SELECT count(*) as cnt,
                 if(next_repay_date IS NULL,last_repay_date,next_repay_date) as new_next_repay_date
                FROM fi_prj WHERE bid_status in('".PrjModel::BSTATUS_REPAYING."','".PrjModel::BSTATUS_REPAY_IN."')
                AND transfer_id IS NULL
                ".$where."";
            */
            $sql = "SELECT SUM(1) AS cnt,
                 if(prj_repay_plan.repay_date IS NULL,prj.last_repay_date,prj_repay_plan.repay_date) as new_next_repay_date
                FROM fi_prj prj
                LEFT JOIN fi_prj_repay_plan prj_repay_plan ON prj.id=prj_repay_plan.prj_id
                WHERE
                prj.bid_status in('".PrjModel::BSTATUS_REPAYING."','".PrjModel::BSTATUS_REPAY_IN."')
                AND prj.transfer_id IS NULL
                AND
                (
                    (
                       prj.repay_way = '".PrjModel::REPAY_WAY_ENDATE."'
                    ) OR (
                        prj_repay_plan.status='".PrjOrderRepayPlanModel::ORDER_REPAYMENT_STATUS_WAIT."' AND prj.repay_way != '".PrjModel::REPAY_WAY_ENDATE."'
                        AND prj_repay_plan.repay_periods !=0
                     )
                )
                ".$where."";
             $result = M()->query($sql);
             $count = $result[0]['cnt'];
        }

        
        $all = array("allTotalAmount_view"=>humanMoney($allTotalAmount,2,false),
                    "allTotalIncome_view"=>humanMoney($allTotalIncome,2,false),
                    "allActivity_money_view"=>humanMoney($allActivity_money,2,false),
                    "allTotalOtherIncome_view"=>humanMoney($allTotalOtherIncome,2,false),
                    "allTotalBidTimeIncome_view"=>humanMoney($allTotalBidTimeIncome,2,false),

            "allTotalAmount"=>$allTotalAmount,
            "allTotalIncome"=>$allTotalIncome,
            "allActivity_money"=>$allActivity_money,
            "allTotalOtherIncome"=>$allTotalOtherIncome,
            "allTotalBidTimeIncome"=>$allTotalBidTimeIncome,
                    );

        return array("list"=>$data,"count"=>$count,"all"=>$all);
    }


    function RepayNotice4Email($result,$type,$isT0=0, $mail_group=FALSE){
        $list = $result['list'];
        $all = $result['all'];
        if($isT0){
            $title = date('Y-m-d')."还款提醒(T+0)";
        }else{
            $title = date('Y-m-d')."还款提醒(T+1)";
        }
        if(!$list) return false;

        $tostr = '<th width="6%" class="tac">年化红包利息(元)</th>';

        $str = '
            <html>
            <header>
            <style>
            .ui-record-table {
                border-collapse: collapse;
                border-spacing: 0;
                width: 100%;
                font-size:12px;
                font-family: microsoft yahei;
            }
            .ui-record-table th {
                color: #555;
                background-color: #EFEFEF;
                border-bottom: 1px solid #FFF;
                height: 25px;
                text-align: center;
            }
            .ui-record-table td {
                height: 25px;
                border-bottom: 1px solid #DDD;
                word-break: break-all;
                word-wrap: break-word;
                vertical-align: middle;
            }
            </style>
            </header>
            <body>
            <h4>'.$title.'</h4>
            <table class="ui-record-table">
            <thead>
            <tr>
                <th>还款日期</th>
                <th  width="8%">项目名称</th>
                <th  width="8%">类型</th>
                <th width="8%" class="tac">期限</th>
                <th width="8%" class="tac">年化利率</th>
                <th class= "tc" width="8%">还款方式</th>
                <th width="8%" class="tac">需还款金额(元)</th>
                <th width="8%" class="tac">还款日 T+(n)</th>
                <th width="8%" class="tac">本金(元)</th>
                <th width="6%" class="tac">利息(元)</th>
                '.$tostr.'
                <th width="6%" class="tac">用款期利息(元)</th>
                <th width="6%" class="tac">募集期资金占用费(元)</th>
            </tr>
            </thead>
             <tbody>
            ';
        foreach($list as $v){
            $tstr0= '<td  align="center">
                '.$v['activity_money_view'].'
                </td>';
            if($isT0){
                if($v['value_date_shadow'] == 1) continue;
            }else{
                if($v['value_date_shadow'] == 0) continue;
            }

             if($isT0){
                 $repayDate = date('Y-m-d',strtotime("-1 day",strtotime($v['new_next_repay_date'])));
             }else{
                 $repayDate = $v['new_next_repay_date'];
             }

            $str .= '
             <tr>
                <td align="center">
                '.$repayDate.'
                </td>
                <td align="center">
                    <p class="pro_name">'.$v['prj_name'].'</p>
                </td>
                   <td align="center">
                    <p class="pro_name">'.$v['prj_type_view'].'</p>
                </td>
                <td   align="center">'.$v['time_limit_unit_view'].'</td>
                <td    align="center">
                <p class="blue">
                '.number_format($v['year_rate']/10,2).'%
                </p>
                </td>
                <td  align="center">'.$v['repay_way_view'].'</td>
                <td    align="center"  >'.$v['totalMoney'].'</td>
                <td    align="center"  >T+'.$v['value_date_shadow'].'</td>
                <td  align="center"   >
                '.$v['totalAmount'].'
                </td>
                <td  align="center">
                '.$v['totalIncome'].'
                </td>
                '.$tstr0.'
                <td  align="center">'.$v['totalOtherIncome'].'</td>
                <td  align="center">
                '.$v['totalBidTimeIncome'].'
                </td>
            </tr>';
        }

        $str .='
          <tr>
                <td align="right" colspan="8" >
                合计金额(元)
                </td>
                <td  align="center"   >
                '.$all['allTotalAmount_view'].'
                </td>
                <td  align="center">
                '.$all['allTotalIncome_view'].'
                </td>
              <td  align="center">
                '.$all['allActivity_money_view'].'
                </td>
                <td  align="center">'.$all['allTotalOtherIncome_view'].'</td>
                <td  align="center">
                '.$all['allTotalBidTimeIncome_view'].'
                </td>
            </tr>
        ';
        $str .='</tbody></table></body></html>';
        //发送邮件
//        $_config = C('EMAIL_NOTICE');
//        $emailConfig = $_config['REPAY_NOTICE_EMAIL'];
        $emailConfig = C('REPAY_NOTICE_EMAIL');

        if(C('APP_STATUS') == "product"){
            if($mail_group !== FALSE && is_array($emailConfig[$type][$mail_group])) $emailArr = $emailConfig[$type][$mail_group];
            else $emailArr = $emailConfig[$type];
        }else{
            $title .= "---测试环境";
            $emailArr = $emailConfig['test'];
        }
        import_addon("libs.Email.Email");
        Email::send($emailArr, $title,$str,null,0);
        return true;
    }

    //是否显示还款计划
    function isShowPlan($prjId, $prj_info=null){
    	if($prj_info) $info = $prj_info;
        else $info = M("prj")->find($prjId);
        return !(($info['prj_type'] == PrjModel::PRJ_TYPE_A) && ($info['repay_way'] == PrjModel::REPAY_WAY_E) && (date('Y-m-d',$info['start_bid_time']) == date('Y-m-d',$info['end_bid_time'])));
    }

    //获取项目信息  $id  项目id
	function getPrjView($id){
        $projectService = service("Financing/Project");
        $cache_key = "if_prj_view_".$id;
        $result_info = cache($cache_key);
        if($result_info){
            $info = $result_info;
            $prj = M('prj')->find($id);
            $info['remaining_amount'] = $prj['remaining_amount'];
            $info['status'] = $prj['status'];
            $info['bid_status'] = $prj['bid_status'];
            $info = $projectService->getById($id, $info);
            $info['act_prj_ext'] = M('prj_ext')->find($id);
        }else{
            $info = $projectService->getById($id);
            cache($cache_key, $info, array('expire'=>600));
        }
        if(!$info){
           throw_exception("异常,项目不存在!");
        }
        //显示还款日期
//        if (!service("Financing/Invest")->isShowPlan($id, $info)) {
//            $endTime = service('Financing/Project')->getPrjEndTime($id, $info);
//            $info['last_repay_date_view'] = date('Y-m-d', $endTime);
//            $info['next_repay_date_view'] = date('Y-m-d', $endTime);
//        }
        $info['last_repay_date_view'] = date('Y-m-d', $info['last_repay_date']);
        $info['next_repay_date_view'] = date('Y-m-d', $info['next_repay_date']);
        $info['is_can_transfer'] = service('Financing/Financing')->isCanTransfer($info['id'], $info);
        $info['time_show'] = service("Index/Index")->showTimeLimit($info,0);
        service("Financing/PrjRepayExtend");
        $info['time_limit_comment'] = preg_replace('/A/',$info['time_show'][2]['time_limit_extend'],PrjRepayExtendService::TIME_LIMIT_COMMENT);
        $info['rateShow'] = service("Index/Index")->showRate2($info);
        if($info['prj_type'] == PrjModel::PRJ_TYPE_H) {
//            $sudui_config = C('FAST_CASH');
            $info['early_close'] = $info['act_prj_ext']['is_early_close'] ? '是' : '否';
//            $info['can_cash'] = $info['act_prj_ext']['cash_level'] < $sudui_config['cash_level_limit'] ? "支持" : "不支持";
            $info['can_cash'] = service('Financing/FastCash')->is_prj_cash($id) ? '支持' : '不支持';
        }
        //项目名称前图标
        if($info["prj_type"] == "F") $info['prjTypeIcon'] = "jubao";
        if($info["prj_type"] == "B") $info['prjTypeIcon'] = "qiyirong";
        if($info['transfer_id']>0) $info['prjTypeIcon'] = "zhuanjubao";
        //项目属性图标
        $info['icon'] = service('Financing/Financing')->getIcoNew($info,0,$id);
        $info['corp_id'] = $this->getCorpId($info['id']);

        $mdCreditorTransfer = D('Financing/CreditorTransfer');
        $srvPrjRepayExtend = service('Financing/PrjRepayExtend');
        $info['is_creditor_transfered'] = $mdCreditorTransfer->isPrjTransfered($id); // 债权已转让
        $time_limit_extend = $srvPrjRepayExtend->getTimeLimitShow($id);
        $info['time_limit_extend'] = $time_limit_extend;
        $info['time_limit_extend_unit'] = implode('', array_values($time_limit_extend));
        return $info;
    }

    /**
     * 根据项目id获取对应的企业 云融资corp_id
     * @param $prj_id
     * @return mixed
     */
    public function getCorpId($prj_id){
        $zhr_id = M("prj")->where("id=".$prj_id)->getField("zhr_apply_id");
        if($zhr_id){  //直融
            $corp_id = M("finance_corp as fc")
                ->join("fi_finance_apply as fa ON fc.id=fa.borrower_id")
                ->where(array("fa.id"=>$zhr_id, "fa.borrower_type"=>1))
                ->getField("fc.db_corp_id");
        }else{   //非直融
            $corp_id = M("prj_corp")->where("prj_id=".$prj_id)->getField("db_corp_id");
        }
        return $corp_id;
    }


    
   /**
    * 根据订单获取投资金额
    * @param type $prj_id
    */
    public function getPrjMoney($prj_id,$uid){
        if(!$prj_id){
            return 0;
        }
        $where = array(
            'prj_id'=>$prj_id,
            'uid'=>$uid,
        );
        $money = D("PrjOrder")->where($where)->getField("money");
        return (int)$money;
    }
    
    /**
     * 获取速兑通项目信息的基本信息
     */
    public function getBaseInfoSDT($prjinfo){
        $p_prj_id = $prjinfo['p_prj_id'];
        if(!$p_prj_id){
            throw_exception("数据异常,无法获取原始项目的数据!");
        }
        //获取父几点项目信息
        try{
            $info = service("Financing/Invest")->getPrjView($p_prj_id);
            $srvPrj = service("Financing/Prj");
            $info['prj_name'] = $srvPrj->getPrjTitleShow($info['id'], $info);//调整项目名称
        }catch (Exception $e){
            throw_exception($e->getMessage());
        }
        $prjinfo['money'] = D("PrjOrder")
            ->join('fi_prj_fast_cash as fast on fast.prj_order_id=fi_prj_order.id')
            ->where(['fast.prj_id' => $prjinfo['id']])->getField("fi_prj_order.money");
        $userinfo =  service("Account/Account")->getSimpleUserInfo($prjinfo['uid']);
        $return = array('sub_prj_info'=>$info,'p_prj_info'=>$prjinfo,'p_prj_userinfo'=>$userinfo);
        return $return;
    }

    /**
     * 获取红包收益
     * @param $activity_id
     * @param $money
     * @param $start_time
     * @param $end_time
     * @return mixed
     */
    public function getRedBaoProfit($activity_id,$money,$start_time,$end_time, $precision=0){
//        $activity = service("Financing/Financing")->getActivityView($activity_id);
        $activity = D("Application/RewardRule")->getRewardRuleInfo($activity_id);

        $redBao = service("Financing/Project")->profitComputeByDate($activity['reward_money_rate_type'], $activity['reward_money_rate_number'], $start_time, $end_time, $money, $precision);
        return $redBao;
    }
    

    // 待还奖励(加息\满减)
    public function getRepayingReward($uid) {
        return 0; // TODO: Q4二期暂时还原

        $sql = "select sum(bf.amount) as am from fi_user_bonus_freeze bf
LEFT JOIN fi_prj_order po on bf.order_id=po.id
WHERE bf.type IN (1,2) AND po.status=3 AND po.repay_status=1 AND po.uid=$uid";

        $res = M()->query($sql);
        return (int)$res[0]['am'];
    }


}

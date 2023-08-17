<?php
/**
 * User: 000802
 * Date: 2013-10-21 16:08
 * $Id$
 *
 * 产品转让
 */
import_app("Financing.Model.PrjModel");
import_app("Financing.Model.AssetTransferModel");
class TransferService extends BaseService{
    CONST TRANSFER_STATUS_WAIT = 1;//待转让
    CONST TRANSFER_STATUS_CANCEL = 2;//取消转让
    CONST TRANSFER_STATUS_SUCESS = 3;//已转让
    const MSG_TYPE_TRANSFER_EXPIRE = 103; // 转让下架消息

    /**
     * 获取转让列表
     * @param unknown $where
     * @param string $orderBy
     * @param number $pageNumber
     * @param number $pageSize
     * @param string $groupBy
     * @param string $getTotal
     * @return Ambigous <number, unknown>
     */
    function getList($where=array(),$orderBy="",$pageNumber=1,$pageSize=15,$groupBy='',$getTotal=true){
        $model = M("asset_transfer");
        $model = $model->page($pageNumber.",".$pageSize);
        if($orderBy) $model = $model->order($orderBy);
        if($groupBy) $model = $model->group($groupBy);
        if($where){
            $where['asset_transfer.mi_no'] = BaseModel::getApiKey('api_key');
            $model = $model->where($where);
        }
        $model = $model->Table(array("fi_asset_transfer"=>"asset_transfer"));
        $model = $model->field("prj.id as prjid,prj.p_prj_id, asset_transfer.id as id,prj.prj_name ,asset_transfer.from_order_id,prj.transfer_id,
                                prj.prj_no,prj.prj_type,prj.demand_amount,time_limit,time_limit_unit,
                                prj.rate_type,prj.rate,prj.safeguards,prj.repay_way,asset_transfer.prj_revenue,prj.start_bid_time,prj.end_bid_time,
                                asset_transfer.prj_will_income,prj.year_rate,asset_transfer.status,
                                asset_transfer.property,asset_transfer.money");
        $model = $model->join("INNER JOIN fi_prj prj ON prj.transfer_id=asset_transfer.id");

        $data = $model->select();
        
//        echo $model->getLastSql();
        if($data){
            $projectService = service("Financing/Project");
			import_app("Financing.Model.PrjModel");
        	foreach ($data as $k=>$v){
        		$pprjInfo = M("prj")->find($v['p_prj_id']);
	        	$data[$k]['id_view'] = $projectService->getIdView($v['id']);
	    	    $data[$k]['property_view'] = humanMoney($v['property']);
	            //实时变化的转让价格
	            $synMoney = service('Financing/Financing')->getTransMoney($v['id'],1);
	            $v['money'] = $synMoney;
	    	    $data[$k]['money_view'] = humanMoney($synMoney,2,false);
	
	            //期限
	//             $data[$k]['expire_time_limit'] = $this->getTimeLimit($v['id']);
	            $data[$k]['expire_time_limit'] = service("Financing/Project")->parseExpireTime($v['id']);
	            //过期
	            $data[$k]['is_exipre'] = service("Financing/Financing")->isTransExpire($v['id'], TRUE);
	            $data[$k]['prj_revenue_view'] = humanMoney($v['prj_revenue']+$v['property'],2,false);
	            $data[$k]['financing_will_income'] = humanMoney($v['prj_revenue']+$v['property']-$v['money'],2,false);
	    		$data[$k]['rate_type_view'] = $projectService->getRateType($v['rate_type']);
	            $data[$k]['prj_type_name'] = $projectService->getPrjTypeName($v['prj_type']);
	    		$data[$k]['rate_symbol'] = $v['rate_type'] == PrjModel::RATE_TYPE_DAY ? '‰': '%';
	            $data[$k]['safeguards_view'] = $v['safeguards'] == 1 ? "保":"本";
	    		$data[$k]['repay_way_view'] = $projectService->getRepayWay($v['repay_way']);
	    		$data[$k]['rate'] = number_format($v['rate'],2);
	        		// 利率统一转换成千分
	        		if($data[$k]['rate_type'] != PrjModel::RATE_TYPE_DAY) {
	        		    $data[$k]['rate_view'] = number_format($v['rate']/10,2);
	        		}else{
						$data[$k]['rate_view'] = number_format($v['rate'],2);
					}
					
				$data[$k]['can_transfer'] =  service("Financing/Transfer")->fedCanTransfer($v['transfer_id']);
	      }
        }
//         echo $model->getLastSql();
//         print_r($data);
        $output['data'] = $data;
        if($getTotal){
            $modelCount = M("asset_transfer");
            if($where) $modelCount = $modelCount->where($where);
            $modelCount = $modelCount->Table(array("fi_asset_transfer"=>"asset_transfer"));
            $modelCount = $modelCount->join("INNER JOIN fi_prj prj ON prj.transfer_id=asset_transfer.id");
            $result = $modelCount->field("COUNT(*) AS CNT")->find();   // TODO: InnoDB的COUN(*)问题
            $totalRow = (int) $result['CNT'];
            $output['total_row'] = $totalRow;
        }

        return $output;
    }

    function fedCanTransfer($transferId){
        $transferInfo = M("asset_transfer")->find($transferId);
        $srvProject = service('Financing/Project');

        if($transferInfo['status'] == AssetTransferModel::STATUS_PROCESSED){
            $checkTime = strtotime(date('Y-m-d',$transferInfo['buy_time']));
        }else{
            $checkTime = strtotime(date('Y-m-d'));
        }

        $canTransfer = 1;
        $extInfo = $srvProject->getExt($transferInfo['prj_id']);

        if(!$extInfo['is_transfer']) $canTransfer  = 0;

        //是否大于91天
        $diffTimeTmp = AssetTransferModel::POCTIME_OFFSET+AssetTransferModel::BIDTIME_OFFSET;
        $diffTime = $diffTimeTmp+$checkTime;
        $prjEndDate = $srvProject->getPrjEndTime($transferInfo['prj_id']);

        if($diffTime > $prjEndDate){
            $canTransfer  = 0;
        }
        return $canTransfer;
    }


    function getTimeLimit($transferId,$prjId=0,$time=0){
    	if(!$prjId){
	        $transferInfo = M("asset_transfer")->find($transferId);
	        $prjId = $transferInfo['prj_id'];
    	}
        //已转让
        import_app("Financing.Model.AssetTransferModel");
        $lastRepayDate = service('Financing/Project')->getPrjEndTime($prjId);
        if(!$time){
	        if($transferInfo['status'] == AssetTransferModel::STATUS_PROCESSED){
	             $to_order_id = $transferInfo['to_order_id'];
	            $startTime = M("prj_order")->where(array("id"=>$to_order_id))->getField("ctime");
	        }else{
	            $startTime = time();
	        }
        }else{
        	$startTime = is_numeric($time) ? $time :strtotime($time);
        }
//         echo date('Y-m-d',$startTime)."|".date('Y-m-d',$lastRepayDate)."<br>";
        if($startTime > $lastRepayDate) return array();
        //月
        $endDate = date('Y-m-d',$lastRepayDate);
        $startDate = date('Y-m-d',$startTime);
//         echo $endDate."|".$startDate."<br>";
        $datetime1 = new DateTime($startDate);
        $datetime2 = new DateTime($endDate);
        $interval = $datetime1->diff($datetime2);
        $allDay = (strtotime($endDate)-strtotime($startDate))/86400;
        $year = $interval->format('%y');
        
        $endTimeTmp = strtotime("+".$year.' years',strtotime($startDate));
        $startDate1 = date('Y-m-d',$endTimeTmp);
        
        $datetime3 = new DateTime($startDate1);
        
        $interval1 = $datetime3->diff($datetime2);
        $month = $interval1->format('%m');
		
        $month = $year*12+$month;
        
        $endTimeTmp = strtotimeX("+".$month.' months',strtotime($startDate));
        $dayNumber = ($lastRepayDate-$endTimeTmp)/86400;//多少天
        $dayNumber = $dayNumber <0 ?0:$dayNumber;
        
        return array($month,$dayNumber,$allDay);
    }

    /**
     * 获取转让收益
     * @param unknown $transferId
     */
    function getIncome($transferId){
        $projectService = service("Financing/Project");
        $info = $this->getById($transferId);
        if(!$info){
            MyError::add("异常,转让信息不存在");
            return false;
        }
        $prjId = $info['prj_id'];
        $project = $projectService->getPrjInfo($prjId);

        $fromOrderId = $info['from_order_id'];
        $orderInfo = M("prj_order")->where(array("id"=>$fromOrderId))->find();
        //起息日
        return $projectService->getIncome($orderInfo['freeze_time'],$project['end_bid_time'],$project['ext']['value_date'],
               $project['time_limit'],$project['time_limit_unit'],$project['rate_type'],
                $project['rate'],$info['property'],$prjId);
    }


    /**
     * 获取转让数据
     * @param unknown $id
     * @return mixed
     */
    function getDataById($id){
        $id = (int) $id;
        return $this->getById($id);
    }

    /**
     * 获取转让信息
     */
    function getById($id){
        $id = (int) $id;
        $model = M("asset_transfer");
        $info = $model->where(array("id"=>$id))->find();
        if(!$info){
        	MyError::add("异常,转让信息不存在");
        	return false;
        }
        $info['property_view'] = humanMoney($info['property'],2,false)."元";
        //todo 实时价格
//        $synMoney = service('Financing/Financing')->getTransMoney($id,1);
        $synMoney = $info['money'];
        $info['money'] = $synMoney;
        $info['money_view'] = humanMoney($info['money'],2,false)."元";
        $income = $info['prj_revenue'];
        //剩余时间
        $info['diff_end_time'] = $info['end_time']-time();
        //份额价值
        $info['income_tmp'] = $info['property']+$income;
        $info['income'] = humanMoney($info['property']+$income,2,false)."元";
        $prjId = $info['prj_id'];
        $projectService = service("Financing/Project");
        $project = $projectService->getById($prjId);
        $project['min_bid_amount_name'] = humanMoney($project['min_bid_amount'],2,false)."元";
        $project['step_bid_amount_name'] = humanMoney($project['step_bid_amount'],2,false)."元";
        $info['project'] = $project;
        $info["will_income_view"] = humanMoney($info['property']+$income-$info['money'],2,false)."元";
        $info['year_rate'] = $project['year_rate'];

        $selfPrjInfo = M("prj")->where(array("transfer_id"=>$id))->find();
        
//        $info['expire_time_limit'] = service("Financing/Project")->parseExpireTime($id);
        $info['expire_time_limit'] = service("Financing/Project")->parseNewExpireTime($id);

        $info['transfer_status'] =$selfPrjInfo['transfer_status'];

		$info['can_transfer'] =  service("Financing/Transfer")->fedCanTransfer($info['id']);
        
        $info['prj_id_view'] = service("Financing/Project")->getIdView($info['prj_id']);
        $info['project'] = $project;
        
        return $info;
    }
    
    //购买检查
    function buyCheck($buyUid,$transferId,$award=0,$isMobile=0){
    	$award = number_format($award,2,'.','');
    	
//    	$transferModel = M("asset_transfer");
//    	$transferInfo = $transferModel->where(array("id"=>$transferId))->find();
        $transferInfo = $this->getById($transferId);
    	if(!$transferInfo){
    		return errorReturn("异常,转让信息为空");
    	}
    	
    	if($transferInfo['status'] != self::TRANSFER_STATUS_WAIT){
    		return errorReturn("异常,转让订单不是待转让状态，不能转让");
    	}
    	
    	if($transferInfo['uid'] == $buyUid){
    		return errorReturn('不能购买自己转让的产品！');
    	}
    	
    	
    	$userModel = service("Account/Account");
    	$userinfo = $userModel->getByUid($buyUid);
    	if((!$userinfo['is_id_auth']) || (!$userinfo['person_id'])){
    		if($isMobile){
    			return errorReturn("投资前请先在账户中进行实名认证");
    		}
    		$renzhengUrl = U("Account/Bank/identify");
    		return errorReturn("投资前需先进行实名认证,去<a href='{$renzhengUrl}' class='blue' target='_blank'>认证</a>");
    	}
    	
    	if((!$userinfo['is_mobile_auth']) || (!$userinfo['mobile'])){
    		if($isMobile){
    			return errorReturn("投资前需先进行手机认证");
    		}
    		$renzhengUrl = U("Account/Bank/identify");
    		return errorReturn("投资前需先进行手机认证,去<a href='{$renzhengUrl}' class='blue' target='_blank'>认证</a>");
    	}
    	
    	
    	$prjId = $transferInfo['prj_id'];
    	$projectService = service("Financing/Project");
    	$project = $projectService->getById($prjId);
    	if(!$project){
    		return errorReturn('异常，项目信息不存在！');
    	}
    	import_app("Financing.Model.PrjModel");
    	//判断是否能转让
    	if($project['ext']['is_transfer'] != 1){
    		return errorReturn('异常，该项目不能转让！');
    	}
    	 
    	if($award > $transferInfo['money']){
    		return errorReturn("奖励金额不能大于转让金额");
    	}
    	
    	$user = service("Payment/PayAccount")->getBaseInfo($buyUid);
    	$tmpMoney = $user['amount']-$transferInfo['money']+$award;
    	if( $tmpMoney < 0){
			if($isMobile){
				return errorReturn("账户余额不足");
			}
    		$renzhengUrl = U("Payment/PayAccount/rechargePage");
    		return errorReturn("账户余额不足，请先<a href='".$renzhengUrl."'  class='blue' target='_blank'>充值</a>");
    	}
    	
    	return true;
    }
    
    //获取剩余可用额度
    function moneyCompute($id,$uid,$award=0,$totalAward=0){
    	$transferModel = M("asset_transfer");
    	$transferInfo = $transferModel->where(array("id"=>$id))->find();

    	$money = $transferInfo['money'];
//        $money = service("Financing/Financing")->getTransMoney($id,1);
    	 
    	$realMoney = $money-$award;
    	$realMoney = $realMoney >0 ? $realMoney :0;
    	 
    	$user = service("Payment/PayAccount")->getBaseInfo($uid);
    	return $user['amount']+$totalAward-$realMoney;
    }

    public function buy($buyUid, $transferId, $award, $isMobile = 0) {
        $key = 'TRANFSER_BUY' . $transferId;
        if(S($key)) {
            MyError::add('购买转让已在处理中');
            return FALSE;
        }

        S($key, 1, 10);
        $ret = $this->buy_ord($buyUid, $transferId, $award, $isMobile);
        if(MyError::hasError()) {
            S($key, NULL);
            return errorReturn(MyError::lastError());
        }
        S($key, NULL);
        return $ret;
    }

    /**
     * 购买转让  (使用事务) (待完善)
     */
    function buy_ord($buyUid,$transferId,$award,$isMobile=0){
    	$award = number_format($award,2,'.','');
        $transferId = (int) $transferId;
        $buyUid = (int) $buyUid;

        //获取转让信息
//        $transferModel = M("asset_transfer");
//        $transferInfo = $transferModel->where(array("id"=>$transferId))->find();
        $transferInfo = $this->getById($transferId);
        if(!$transferInfo){
        	MyError::add("异常,转让信息为空");
        	return false;
        }
        if(M('asset_transfer')->where(array('from_order_id' => $transferInfo['from_order_id'], 'status' => 3))->find()) throw_exception('该订单的转让已经被购买了！');


        $sellUid = $transferInfo['uid'];
        $prjType = $transferInfo['prj_type'];
        $prjId = $transferInfo['prj_id'];
        $property = $transferInfo['property'];
        $money = $transferInfo['money'];
        $fromOrderId =  $transferInfo['from_order_id'];
        
        $prjInfo = M("prj")->where(array("id"=>$prjId))->find();
        
        if(!service("Financing/Invest")->isEndDate($prjInfo['repay_way'])){
        	$obj = service("Financing/Project")->getTradeObj($prjId,"Transfer");
        	if(!$obj) return errorReturn(MyError::lastError());
        	$params = array();
        	$params['transfer_id'] = $transferId;
        	$params['award'] = $award;
        	$params['is_mobile'] = $isMobile;
        	$params['uid'] = $buyUid;
        	$obj->buy($params);
        	if(MyError::hasError()){
        		return errorReturn(MyError::lastError());
        	}
            //推荐是否投资
            M("user_recommed")->where(array("uid"=>$buyUid))->save(array("is_invest"=>1,"mtime"=>time()));

            D("Financing/AssetTransfer")->addDealTransferShow();
        	return $transferId;
        }
        
        $checkReturn = $this->buyCheck($buyUid, $transferId,$award,$isMobile);
        if(!$checkReturn) return errorReturn(MyError::lastError());

       

		$title = service("Financing/Project")->getPrjTypeName($prjType);

		$reward_money = 0;
		$invest_reward_money =0;
		$params['award'] = $award;
		
		$awardCheck = service("Financing/Project")->parseAward($params, $buyUid);
			
		if($awardCheck === false){
			throw_exception(MyError::lastError());
		}else{
			list($reward_money, $invest_reward_money, $coupon_money) = $awardCheck;
		}
			
        try{
        	if($coupon_money){
        		$coulist = D('Payment/Coupon')->getCouponList($buyUid, $coupon_money);
                $a_coupon_money = 0;
                foreach($coulist as $couEle){
                    $a_coupon_money += $couEle['amount'];
                }
                $more_coupon_money = 0;
                if($a_coupon_money > $coupon_money){
                    $more_coupon_money = $a_coupon_money - $coupon_money;
                }

                $couReward = 0;
        		$couInvestReward = 0;
        		foreach($coulist as $couEle){
        			if($couEle['cashout_type'] == 1) $couReward += $couEle['amount'];
        			if($couEle['cashout_type'] == 2) $couInvestReward += $couEle['amount'];
        			D('Payment/Coupon')->useCoupon($couEle['id']);
        		}
        		$reward_money += $couReward;
        		$invest_reward_money += $couInvestReward;
                if($more_coupon_money){
                    if($invest_reward_money >= $more_coupon_money) $invest_reward_money = $invest_reward_money - $more_coupon_money;
                    else {
                        $invest_reward_money = 0;
                        $reward_money = $reward_money + $invest_reward_money - $more_coupon_money;
                        if($reward_money < 0) throw_exception("奖励计算异常，请重试");
                    }
                }
        	}
            $db = new BaseModel();
            $db->startTrans();

            //写入订单
            $orderObj = D("Financing/PrjOrder");
            $result = $orderObj->addOrder($buyUid,$prjId,$property,$fromOrderId);
            if(!$result){
                throw_exception(MyError::lastError());
            }
            list($orderNo,$buyOrderId)= $result;
            $orderObj->addOrderAfterCount($buyUid, $prjId, $fromOrderId);
            
            import_app("Payment.Model.PayAccountModel");
            $objType = PayAccountModel::OBJ_TYPE_TRANSFER;

            $tmpMoney = $money-$award;
            $fee = $transferInfo['fee'];
            $profit = $tmpMoney + $reward_money + $invest_reward_money - $transferInfo['property'];
            if($profit <= 0) $profit = 0;
            
            $free_money = floor(($tmpMoney+$reward_money+$invest_reward_money)/10);
            $free_money = service("Payment/PayAccount")->giveFreeMoney($buyUid, $free_money, 0);
            
            //直接转账给卖家
            $payAccount = service("Payment/PayAccount");
            if($transferInfo['property'] >= 100000){
            	$free_tixian = M("user_account")->field("free_tixian_times")->find($buyUid);
            	$payAccount->incrFreeTixianTimes($buyUid);
            	D("Admin/ActionLog")->insertLog($buyUid, '增加免费提现次数', $free_tixian['free_tixian_times']+1, "transfer".$transferInfo['id']."_".$buyUid."_".date("Ymd"));
            }
            $payResult = $payAccount->directPay($buyUid,$orderNo,$tmpMoney,$reward_money, $invest_reward_money
            		,$fee,$title,"",$sellUid,$objType,$transferId,0, $free_money, $profit);
            if(!$payResult['boolen']){
                throw_exception($payResult['message']);
            }
            $payOrderNo = $payResult['payorderno'];
            //更新项目状态,已转让
            M("prj")->where(array("transfer_id"=>$transferId))->save(array("transfer_status"=>AssetTransferModel::PRJ_TRANSFER_BIDED,"transfer_time"=>time()));
            //更新
            M("prj_order")->where(array("id"=>$buyOrderId))->save(
				array("out_order_no"=>$payOrderNo,"status"=>PrjOrderModel::STATUS_PAY_SUCCESS,"protocol_active"=>1,"mtime"=>time())
				);

            //转让
            M("prj_order")->where(array("id"=>$fromOrderId))->setDec("rest_money",$property);
            M("prj_order")->where(array("id"=>$fromOrderId))->setDec("tran_freeze_money",$property);
            M("prj_order")->where(array("id"=>$fromOrderId))->setInc("transfer",$property);
            //待回款
            service("Financing/Invest")->cutUserRepayPlan($fromOrderId);
            //预计收益减少
            $buyPossibleYield = M("prj_order")->where(array("id"=>$fromOrderId))->getField("possible_yield");
            M("prj_order")->where(array("id"=>$fromOrderId))->setDec("possible_yield",$buyPossibleYield);
            
            //更新用户统计
            //购买者待收本金, 待收利息增加
            service("Payment/UserAccountSummary")->changeAcountSummaryByItem($buyUid,'buy_transfer', $buyOrderId,$property,'will_principal');
            service("Payment/UserAccountSummary")->changeAcountSummaryByItem($buyUid,'buy_transfer', $buyOrderId,$buyPossibleYield,'will_profit');
            
//             M("user_account_summary")->where(array("uid"=>$buyUid))->setInc("will_principal",$property);
//             M("user_account_summary")->where(array("uid"=>$buyUid))->setInc("will_profit",$buyPossibleYield);

            //购买者待还款项目个数,金额增加
            service("Payment/UserAccountSummary")->changeAcountSummaryByItem($buyUid,'buy_transfer', $buyOrderId,1,'willrepay_prj_count');
            service("Payment/UserAccountSummary")->changeAcountSummaryByItem($buyUid,'buy_transfer', $buyOrderId,$property,'willrepay_prj_money');
            
//             M("user_account_summary")->where(array("uid"=>$buyUid))->setInc("willrepay_prj_count",1);
//             M("user_account_summary")->where(array("uid"=>$buyUid))->setInc("willrepay_prj_money",$property);

            //出售者待收本金, 待收利息减少
            service("Payment/UserAccountSummary")->changeAcountSummaryByItem($sellUid,'buy_transfer', $buyOrderId,-$property,'will_principal');
            service("Payment/UserAccountSummary")->changeAcountSummaryByItem($sellUid,'buy_transfer', $buyOrderId,-$buyPossibleYield,'will_profit');
            
//             M("user_account_summary")->where(array("uid"=>$sellUid))->setDec("will_principal",$property);
//             M("user_account_summary")->where(array("uid"=>$sellUid))->setDec("will_profit",$buyPossibleYield);
            
            //出售者待还款项目个数,金额减少
            
            service("Payment/UserAccountSummary")->changeAcountSummaryByItem($sellUid,'buy_transfer', $buyOrderId,-$property,'willrepay_prj_money');
            service("Payment/UserAccountSummary")->changeAcountSummaryByItem($sellUid,'buy_transfer', $buyOrderId,-1,'willrepay_prj_count');
            
            
//             M("user_account_summary")->where(array("uid"=>$sellUid))->setDec("willrepay_prj_money",$property);
//             M("user_account_summary")->where(array("uid"=>$sellUid))->setDec("willrepay_prj_count",1);
            
            
//             M("user_account_summary")->where(array("uid"=>$buyUid))->save(array("mtime"=>time()));
//             M("user_account_summary")->where(array("uid"=>$sellUid))->save(array("mtime"=>time()));

            //更新转让信息
            M("asset_transfer")->where(array("id"=>$transferId))->save(array("out_order_no"=>$payOrderNo,
                                "to_order_id"=>$buyOrderId,"status"=>self::TRANSFER_STATUS_SUCESS,
                                "free_money"=>$free_money,
                                "free_tixian_times"=>1,
                                "money"=>$money,
                                "buy_uid"=>$buyUid,"buy_time"=>time()));
            //关闭老订单
            M("prj_order")->where(array("id"=>$fromOrderId))->save(array("status"=>PrjOrderModel::STATUS_NOT_PAY));
            
            //份额转让协议
              $protocolKey = 10;
			  $protocolId = service("Financing/Protocol")->savePdf($protocolKey,$buyOrderId,$prjId);
			   if(!$protocolId){
    	        throw_exception(MyError::lastError());
    	       }
			
             M("prj_order")->where(array("id"=>$buyOrderId))->save(array("protocol_id"=>$protocolId));
             
             //回款数据记录生成
             service("Financing/Invest")->addUserRepayPlan($buyOrderId);
             //保存奖励数据
             $rewardData=array();
             $rewardData['reward_money'] = $reward_money;
             $rewardData['invest_reward_money'] = $invest_reward_money;
             if($rewardData){
             	$now = time();
             	$rewardData['prj_order_id'] = $buyOrderId;
             	$rewardData['ctime'] = $now;
             	$rewardData['mtime'] = $now;
             	M("prj_order_ext")->add($rewardData);
             }
            //更改推送
            $this->changeMobilePush($fromOrderId,$buyOrderId);

            D("Financing/AssetTransfer")->addDealTransferShow();
            //推荐是否投资
            M("user_recommed")->where(array("uid"=>$buyUid))->save(array("is_invest"=>1,"mtime"=>time()));

            $db->commit();
            $this->sendMsg($buyOrderId,$fromOrderId);
        }catch (Exception $e){
        	MyError::add($e->getMessage());
        	if($db) $db->rollback();
        	return false;
        }
        return $transferId;
    }

    //修改推送人
    function changeMobilePush($fromOrderId,$buyOrderId){
        $orderInfo = M("prj_order")->where(array("id"=>$fromOrderId))->find();
        $buyOrderInfo = M("prj_order")->where(array("id"=>$buyOrderId))->find();
        $repayWay = M("prj")->where(array("id"=>$orderInfo['prj_id']))->getField("repay_way");
        if($repayWay == PrjModel::REPAY_WAY_ENDATE){
            M("remind")->where(array("uid"=>$orderInfo['uid'],"obj_id"=>$orderInfo['prj_id'],"remind_type"=>33,"status"=>1,"is_success"=>0))->save(array("uid"=>$buyOrderInfo['uid']));
        }else{
            M("remind")->where(array("uid"=>$orderInfo['uid'],"obj_id"=>$fromOrderId,"remind_type"=>33,"status"=>1,"is_success"=>0))
                ->delete();
        }
        return true;
    }

    function sendMsg($buyOrderId,$fromOrderId){
        $orderInfo = M("prj_order")->where(array("id"=>$fromOrderId))->find();
        $buyOrderInfo = M("prj_order")->where(array("id"=>$buyOrderId))->find();
        //
        $money = M("asset_transfer")->where(array("from_order_id"=>$fromOrderId,"to_order_id"=>$buyOrderId,"uid"=>$orderInfo['uid']))->getField("money");
        //买方
        $messageData = array();
        $uname = M("user")->where(array("uid"=>$buyOrderInfo['uid']))->getField("uname");
        $messageData[]=$uname;
        $prjName = M("prj")->where(array("id"=>$buyOrderInfo['prj_id']))->getField("prj_name");
        $messageData[]=$prjName;
        $messageData[] = humanMoney($money,2,false)."元";
        if($buyOrderInfo['free_money']){
            $messageData[]= "，直到投标结束您账户内的这部分资金将被冻结并且将获得".humanMoney($buyOrderInfo['free_money'],2,false)."元的免费提现额度";
        } else{
            $messageData[]= "";
        }

        service("Message/Message")->sendMessage($buyOrderInfo['uid'],1,82,$messageData,$buyOrderId,0);
        //卖方
        $messageData = array();
        $uname = M("user")->where(array("uid"=>$orderInfo['uid']))->getField("uname");
        $messageData[]=$uname;
        $prjName = M("prj")->where(array("id"=>$orderInfo['prj_id']))->getField("prj_name");
        $messageData[]=$prjName;
        $uname = M("user")->where(array("uid"=>$buyOrderInfo['uid']))->getField("uname");
        $messageData[]=$uname;
        service("Message/Message")->sendMessage($orderInfo['uid'],1,83,$messageData,$fromOrderId,0);
    }


    // 转让过期下架
    function cancel($transfer_id){
        $transfer_id = (int)$transfer_id;
        $transfer = $this->getById($transfer_id);
        if(!$transfer) throw_exception('异常,转让信息为空');
        if($transfer['status'] != self::TRANSFER_STATUS_WAIT) throw_exception('异常,转让订单不是待转让状态，不能进行此操作');

        $from_order_id = $transfer['from_order_id'];
        $order = M('prj_order')->where(array('id' => $from_order_id))->find();
        if(!$order) throw_exception('原始订单不存在');

        $now = time();
        $db = new BaseModel();
        try {
            $db->startTrans();

            //修改购买订单表
            $data = array(
                'tran_freeze_money' => 0, // $order['tran_freeze_money'] - $transfer['property'],
                'mtime' => $now,
            );
            if(FALSE === M('prj_order')->where(array('id' => $from_order_id))->save($data)) throw_exception('更新购买订单出错');

            // 修改转让表
            $data = array(
                'status' => self::TRANSFER_STATUS_CANCEL,
                'mtime' => $now,
            );
            if(FALSE === M('asset_transfer')->where(array('id' => $transfer_id))->save($data)) throw_exception('修改转让状态出错');

            //删除项目表
            if($transfer_id) {
                if(FALSE === M('prj')->where(array('transfer_id' => $transfer_id))->delete()) throw_exception('删除转让虚拟项目出错');
            }

            $db->commit();
            $this->sendMassage($transfer);
        } catch(Exception $e) {
            $db->rollback();
            throw $e;
        }
        return TRUE;
    }

    //更新转让数据
    function transferDeal($transferId){
        $v = M("asset_transfer")->find($transferId);
        $prjInfo = M("prj")->find($v['prj_id']);
        $transId = $v['id'];
        $udata=array();
        $transMoney = service("Financing/Financing")->getTransMoney($transId,1);
        $udata['demand_amount'] = $transMoney;
        $udata['remaining_amount'] = $transMoney;
        $udata['time_limit_unit'] = PrjModel::LIMIT_UNIT_DAY;
        $expireData = service("Financing/Transfer")->getTimeLimit($transId);
        $allDay = isset($expireData[2]) ? $expireData[2]:0;
        $udata['time_limit'] = $allDay;
        $udata['time_limit_day'] = $allDay;
        $udata['last_repay_date'] = $prjInfo['last_repay_date'];
        $udata['next_repay_date'] = $prjInfo['next_repay_date'];
        $udata['bid_status'] = $prjInfo['bid_status'];
        $udata['mtime'] = time();

        M("prj")->where(array("transfer_id"=>$transId))->save($udata);

        $transData = array();
        $transData['money'] = service("Financing/Financing")->getTransMoney($transId,1);
        //获取最新转让本金
        $orderInfo = M("prj_order")->find($v['from_order_id']);
        $property = $orderInfo['rest_money'];
        // 预计收益
        $transData['prj_revenue'] = $orderInfo['possible_yield']-$orderInfo['yield'];

        $transData['prj_will_income'] = $transData['prj_revenue'];
        $transData['property'] = $property;
        $transData['mtime'] = time();
		$transData['fee'] = D("Financing/AssetTransfer")->getFee($transData['money']);
        M("asset_transfer")->where(array("id"=>$transId))->save($transData);

        M("prj_order")->where(array("id"=>$v['from_order_id']))->save(array("tran_freeze_money"=>$property));
        return true;
    }


    public function sendMassage($transfer) {
        $uname = M('user')->where(array('uid' => $transfer['uid']))->getField('uname');
        $prj_name = M('prj')->where(array('id' => $transfer['prj_id']))->getField('prj_name');
        $message = array(
            $uname,
            $prj_name,
            humanMoney($transfer['money'], 2, FALSE) . '元',
        );
        D('Message/Message')->simpleSend($transfer['uid'], 1, self::MSG_TYPE_TRANSFER_EXPIRE, $message, array(1, 2, 3), TRUE);
    }

}
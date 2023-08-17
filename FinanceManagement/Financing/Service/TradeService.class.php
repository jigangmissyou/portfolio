<?php
/**
 * // +----------------------------------------------------------------------
// | UPG
// +----------------------------------------------------------------------
// | Copyright (c) 2012 http://upg.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: kaihui.wang <wangkaihui@upg.cn>
// +----------------------------------------------------------------------
//
 */
import_app("Financing.Model.PrjOrderModel");
import_app("Financing.Model.PrjModel");
import_addon("libs.Cache.RedisList");
class TradeService extends BaseService{
    const PAY_LOCK_KEY = "PAY_LOCK_KEY";
    const PRJ_PAY_QUEUE_KEY = "PRJ_PAY_QUEUE_KEY";

    /**
     * 获取队列key
     * @param unknown $prjId
     */
    function getProjectQueueKey(){
        return self::PRJ_PAY_QUEUE_KEY;
    }

    //支付锁定

    function isPayLock($id,$type){
        $key = self::PAY_LOCK_KEY.$id.$type;
        return cache($key) ? true:false;
    }

    function setPayLock($id,$type){
        $key = self::PAY_LOCK_KEY.$id.$type;
        cache($key,1,array('expire'=>86400));
        return true;
    }

    function unsetPayLock($id,$type){
        $key = self::PAY_LOCK_KEY.$id.$type;
        cache($key,null);
        return true;
    }

    //交易前检查
    function tradeCheck($prjId){
    	//检查支付用户条件
    	$checksql = "SELECT user.uname FROM fi_prj_order prj_order LEFT JOIN fi_user user ON
            			 prj_order.uid = user.uid
            			 LEFT JOIN fi_user_account user_account ON prj_order.uid = user_account.uid
            			 WHERE (user.mobile IS NULL OR user.is_mobile_auth =0
            			 OR user.person_id  IS NULL OR user.is_id_auth=0 OR user_account.status != 1 OR
        				 user.is_active = 0 OR user.is_del = 1)
            			 AND prj_order.prj_id = ".$prjId." AND prj_order.is_regist = 0
            			";
    	$checkUser = M()->query($checksql);
    	if($checkUser && $checkUser[0]['uname']){
    		$checkStr = "";
    		foreach ($checkUser as $cv){
    			$checkStr .= $cv['uname'].",";
    		}
    		$checkStr = rtrim($checkStr,",");
    		return errorReturn("以下用户数据异常，请与开发人员联系,用户如下:".$checkStr);
    	}
    	return true;
    }
    
    /**
     * 保证金充值
     * $prj_id 项目id
     * $amount 金额 精确到分 
     */
    public function depositRecharge($prj_id, $amount){
    	$prj_id = (int) $prj_id;
//    	$prj['id'] = $prj_id;
//		$prj['deposit'] = $amount;//保证金金额
//        $prj['mtime']=time();
//    	$res = M('prj')->save($prj);

        $res = M("prj")->where(array("id"=>$prj_id))->setInc("deposit",$amount);
        M("prj")->where(array("id"=>$prj_id))->save(array("id"=>$prj_id, 'mtime'=>time()));

    	if(!$res) throw_exception("充值失败，请再试");
    	return true;
    }
    
    /**
     * 直融保证金充值
     * $prj_id 项目id
     * $amount 金额 精确到分
     */
    public function zrRecharge($prj_id, $amount){
    	$prj_data = M('prj')->find($prj_id);
    	$prj_id = (int) $prj_id;
    	$prj['id'] = $prj_id;
    	$prj['zhr_recharge'] = $amount + $prj_data['zhr_recharge'];//保证金金额
    	$prj['mtime']=time();
    	$res = M('prj')->save($prj);
    	if(!$res) throw_exception("充值失败，请再试");
    	return true;
    }
    
    /**
     * 支付项目  (使用事务)
     * @param unknown $projectId
     */
    function payProject($projectId,$outNo,$note){
        $projectId = (int) $projectId;
        try{
            $cacheKey = "tradeService_payProject_".$projectId;
            $db = new BaseModel();
            $db->startTrans($cacheKey);
            //检查支付用户条件
            $tradeCheck = $this->tradeCheck($projectId);
            if(!$tradeCheck){
            	throw_exception(MyError::lastError());
            }

            $prjOrderObj = D("Financing/PrjOrder");
            $orders = $prjOrderObj->where(array("prj_id"=>$projectId,"status"=>PrjOrderModel::STATUS_FREEZE))
                      ->select();

            $projectService = service("Financing/Project");
            $info = $projectService->getPrjInfo($projectId);
            
            if($info['bid_status'] == PrjModel::BSTATUS_REPAYING){
            	throw_exception("异常~,项目已支付，请刷新页面查看!");
            }
            
            $ext = $info['ext'];
            if(!$ext){
            	throw_exception("异常,未知的投资类型");
            }
            $fundAccount = isset($ext['fund_account']['id']) ? $ext['fund_account']['id']:"";
            if(!$fundAccount){
            	throw_exception("资金转入账户未知，支付失败!");
            }


            $onlineNumber = 0;

            if($orders){
            	foreach ($orders as $k=>$v){
                    //如果是线下
                    if($v['is_regist']){
        M('prj_order')->where(array("id"=>$v['id']))->save(array("status"=>PrjOrderModel::STATUS_PAY_SUCCESS));
                        continue;
                    }
                    //取消的订单
                    if($v['status'] == PrjOrderModel::STATUS_NOT_PAY){
                        continue;
                    }

                    $onlineNumber ++;
            		 $rs = $this->payPrjOrder($v['order_no'],$outNo,$note,0);
            		 if(!$rs){
            		     throw_exception(MyError::lastError());
            		 }else{
            		     $db->commit($cacheKey);
            		 }
            	}
            }
            #补丁，全部都是线下投资的情况
            if(!$onlineNumber) $db->commit($cacheKey);

            $checkFreezeWhere = array();
            $checkFreezeWhere['prj_id'] = $projectId;
            $checkFreezeWhere['is_regist'] = 0;
            $checkFreezeWhere['status'] = PrjOrderModel::STATUS_FREEZE;
            $checkFreeze = M("prj_order")->where($checkFreezeWhere)->find();
            //如果都支付成功
            if(!$checkFreeze){
            	//开始提现
	            $accountService = service("Account/Account");
	            $loginInfo = $accountService->getLoginedUserInfo();
	            $dealUid = $loginInfo['uid'];

	            //获取支付总金额
	            $totalMoney = 0;
	            $totalMoneyWhere = array();
	            $totalMoneyWhere['is_regist'] = 0;
	            $totalMoneyWhere['prj_id'] = $projectId;
	            $totalMoneyWhere['status'] = PrjOrderModel::STATUS_PAY_SUCCESS;
	            $totalMoneyResult = M("prj_order")->field("SUM(rest_money) as totalmoney ")->where($totalMoneyWhere)->find();
				$totalMoney = $totalMoneyResult['totalmoney'];

	            $service  = service("Payment/Cashout");
	            

	            if($info['is_free']){
	            	$fee = 0;
	            }else{
					$fee = $info['platform_fee'];
					$fee = $fee ? $fee : service("Financing/Trade")->getFee($totalMoney,$info['time_limit'],$info['time_limit_unit']);
				}

	            $payMoney = $totalMoney-$fee;

	            if($payMoney > 0){
		            $cashout = $service->prjPay($dealUid,$projectId,$payMoney,$fee,$fundAccount,$outNo,$note);
		            if(!$cashout['boolen']){
		                throw_exception($cashout['message']);
		            }
	            }else{
	            	throw_exception("需支付额度为0元,不能进行支付");
	            }
	            //提现结束
	            //其他
                if($info['bid_status'] != PrjModel::BSTATUS_REPAY_IN) {
	                M("prj")->where(array("id"=>$projectId))->save(array("bid_status"=>PrjModel::BSTATUS_REPAYING, "platform_fee"=>$fee,"mtime"=>time()));
                }
	             //生成待还款初始化数据
	            //service("Application/Repayment")->createPrjRepayment($projectId);
				D('Financing/PrjRepayment')->getId($projectId);
	            //借贷更新支付时间
				service("Financing/Project")->updateActualPayTime($projectId,$info['prj_type']);
            }
			$db->commit($cacheKey);
        }catch (Exception $e){
            $db->rollback($cacheKey);
            MyError::add($e->getMessage());
            return false;
        }
        return true;
    }

    /**
     * 基金或投资成立时支付  (使用事务)
     * @param $prjOrder 购买订单号
     */
    function payPrjOrder($prjOrderNo,$outNo,$note,$doCashout=1){
        if(!$prjOrderNo){
            MyError::add("购买订单号为空");
            return false;
        }
        try{
            $cacheKey = "trade_payPrjOrder".$prjOrderNo;
            $m = new BaseModel();
            $m->startTrans($cacheKey);
            //检查状态
            $prjOrderObj = D("Financing/PrjOrder");
            $prjOrderInfo = $prjOrderObj->where(array("order_no"=>$prjOrderNo))->find();

            if(!$prjOrderInfo){
                throw_exception("购买订单不存在");
            }

            if($prjOrderInfo['status'] != PrjOrderModel::STATUS_FREEZE){
                if($prjOrderInfo['status'] == PrjOrderModel::STATUS_PAY_SUCCESS){
                    throw_exception("并发错误，该项目已支付成功,请刷新页面!");
                }else{
                    throw_exception("异常,购买订单不是冻结状态不能处理!");
                }

            }
            $prjId = $prjOrderInfo['prj_id'];
            $projectService = service("Financing/Project");
            $info = $projectService->getPrjInfo($prjId);
            $ext = $info['ext'];

            if(!$ext){
            	throw_exception("异常,未知的投资类型");
            }
            $fundAccount = isset($ext['fund_account']['id']) ? $ext['fund_account']['id']:"";

            if(!$fundAccount){
            	throw_exception("资金转入账户未知，支付失败!");
            }

            $service  = service("Payment/PayAccount");
//             if($info['prj_type'] == PrjModel::PRJ_TYPE_A){
//             	$free_money = floor($prjOrderInfo['rest_money']*0.2);
//             } else if($info['prj_type'] == PrjModel::PRJ_TYPE_B || $info['prj_type'] == PrjModel::PRJ_TYPE_D 
//             		|| $info['prj_type'] == PrjModel::PRJ_TYPE_F){
//             	$free_money = $prjOrderInfo['rest_money'];
//             }
            
            $result = $service->pay($prjOrderNo, $prjOrderInfo['free_money']);

            //更新购买订单状态
            if($result['boolen']){
                $prjOrderObj->where(array("order_no"=>$prjOrderNo))
                ->save(array("status"=>PrjOrderModel::STATUS_PAY_SUCCESS,"protocol_active"=>1,"mtime"=>time(),"pay_time"=>time()));
            }else{
                throw_exception("异常,code:PSPAY");
            }
            
            if( ($info['time_limit_unit']=='day' && $info['time_limit']>=30)
            		|| ($info['time_limit_unit']=='month' && $info['time_limit']>=1) || ($info['time_limit_unit']=='year')){
            	if($info['money'] >= 100000){
//             		$prj_tixian_times_do_counter = MultiCounter::init(Counter::GROUP_PRJ_COUNTER."_free_tixian_times_do", Counter::LIFETIME_TODAY);
//             		$tixian_con = $prj_tixian_times_do_counter->incr("free_tixian_".$prjId."_".$prjOrderInfo['uid'], 1);
//             		if($tixian_con == 1){
            		if($prjOrderInfo['free_tixian_times']){
            			$free_tixian = M("user_account")->field("free_tixian_times")->find($prjOrderInfo['uid']);
            			service("Payment/PayAccount")->incrFreeTixianTimes($prjOrderInfo['uid']);
            			D("Admin/ActionLog")->insertLog($prjOrderInfo['uid'], '增加免费提现次数', $free_tixian['free_tixian_times']+1, $prjId."_".$prjOrderInfo['uid']."_".date("Ymd", $prjOrderInfo['freeze_time']));
            		}
//             		}
            	}
            }

            import_app("Financing.Model.PrjModel");
            if($info['bid_status'] != PrjModel::BSTATUS_REPAY_IN) {
            	
            	service("Payment/UserAccountSummary")->changeAcountSummary('pay', $prjOrderInfo['id'], 0,0);
//                 // 投标中减少
//                 M("user_account_summary")->where(array("uid" => $prjOrderInfo['uid']))->setDec("investing_prj_count", 1);
//                 M("user_account_summary")->where(array("uid" => $prjOrderInfo['uid']))->setDec("investing_prj_money", $prjOrderInfo['money']);

//                 // 待还款增加
//                 M("user_account_summary")->where(array("uid" => $prjOrderInfo['uid']))->setInc("willrepay_prj_count", 1);
//                 M("user_account_summary")->where(array("uid" => $prjOrderInfo['uid']))->setInc("willrepay_prj_money", $prjOrderInfo['money']);
            }

//             //待收利息，本金增加
//              M("user_account_summary")->where(array("uid"=>$prjOrderInfo['uid']))->setInc("will_principal",$prjOrderInfo['money']);
//              M("user_account_summary")->where(array("uid"=>$prjOrderInfo['uid']))->setInc("will_profit",$prjOrderInfo['possible_yield']);

//              M("user_account_summary")->where(array("uid"=>$prjOrderInfo['uid']))->save(array("mtime"=>time()));

         if($doCashout){
            //开始提现

            $accountService = service("Account/Account");
            $loginInfo = $accountService->getLoginedUserInfo();
            $dealUid = $loginInfo['uid'];

                //提现
                $service  = service("Payment/Cashout");
                if($info['prj_type'] == PrjModel::PRJ_TYPE_C){
                	$fee = $info['is_free'] ? 0 : $this->getFee($prjOrderInfo['rest_money'],$info['time_limit'],$info['time_limit_unit']);
                }else{
					 if($info['is_free']){
			    		$free = 0;
					}else{
						$free = $info['platform_fee'];
						$free = $free ? $free : $this->getFee($prjOrderInfo['rest_money'],$info['time_limit'],$info['time_limit_unit']);
					}

                }
                $payMoney = $prjOrderInfo['rest_money']-$fee;
                // echo $dealUid."|".$prjId."|".$payMoney."|".$fee."|".$fundAccount."|".$outNo."|".$note;
                $cashout = $service->prjPay($dealUid,$prjId,$payMoney,$fee,$fundAccount,$outNo,$note);
                if(!$cashout['boolen']){
                	throw_exception($cashout['message']);
                }
            }

            //基金处理
            if($info['prj_type'] == PrjModel::PRJ_TYPE_C){
                //判断是否是最后一笔,如果是则更新状态为待还款
                $totalPay = $prjOrderObj->field("SUM(rest_money) as Total")->
                            where(array("prj_id"=>$prjOrderInfo['prj_id'],"status"=>PrjOrderModel::STATUS_PAY_SUCCESS))
                            ->find();
                $totalPay = $totalPay['Total'];

                 if($totalPay >= $info['demand_amount']){
                   //更新状态待还款,用大于号容错
                    M("prj")->where(array("id"=>$prjOrderInfo['prj_id']))->save(array("bid_status"=>PrjModel::BSTATUS_REPAYING,"mtime"=>time()));
                   //生成待还款初始化数据
				   D('Financing/PrjRepayment')->getId($prjOrderInfo['prj_id']);
                   //service("Application/Repayment")->createPrjRepayment($prjOrderInfo['prj_id']);
                 }
             }

              $prjOrderUid = $prjOrderInfo['uid'];
             //消息推送
             $pushDay = (strtotime(date('Y-m-d',$info['repay_time']))-strtotime(date('Y-m-d')))/86400;
             $pushDay = $pushDay-1;
			 $pushDayTmp = $pushDay < 3 ? $pushDay : 3;
             $pushData = array();
             $pushData[] = $info['prj_name'];
             $pushData[] = date('Y-m-d',$info['repay_time']);
             $pushData[] = $pushDayTmp;
             //3天后，第4天发
            // $remind_time = $info['repay_time']-345600;
			 $remind_time = $info['repay_time']-($pushDayTmp+1)*86400;
             //大于当前则添加
             $pushResult = $this->pushPaymentDateExpire($prjOrderUid,$remind_time,$prjOrderInfo['prj_id'],$pushData);
             if(!$pushResult){
             	throw_exception(MyError::lastError());
             }

             $m->commit($cacheKey);
			if($prjOrderInfo['prj_type'] != PrjModel::PRJ_TYPE_C){
			 //合同消息
			  $userInfo = M("user")->where(array("uid"=>$prjOrderUid))->find();
			  $protocol = service('Financing/Project')->getProtocol($prjOrderInfo['prj_type'],$info['zhr_apply_id'],$info['id']);
			  $messageData = array();
			  $messageData[] = $userInfo['uname'];
			  $messageData[] = date('Y-m-d');
			  $href = "http://".C('SITE_DOMAIN')."/Index/Protocol/dpdf?id=".$prjOrderInfo['id'];
			  $protocolHref = "<a href=\"".$href."\" target=\"_blank\">".$protocol['name']."</a>";
			  $messageData[] = $protocolHref;
			  $messageData[] = $info['prj_name'];
			  service("Message/Message")->sendMessage($prjOrderUid,1,11,$messageData,$prjId, 1, array(1,2,3), true);
		  }
            return $prjOrderInfo['rest_money'];
        }catch (exception $e){
            $exception = array();
            $exception['error'] = $e->getMessage();
            $exception['data'] = array($prjOrderNo,$outNo,$note,$doCashout);
            D('Public/Exception')->save($exception,"pay_order");

            $m->rollback($cacheKey);
            MyError::add($e->getMessage());
            return false;
        }
    }

    function pushPaymentDateExpire($prjOrderUid,$remind_time,$prjId,$pushData){
    	$where['obj_id'] = $prjId;
    	$where['uid'] = $prjOrderUid;
    	$where['remind_type'] = 33;
    	$info = M("remind")->where($where)->find();

    	if(!$info){
			$memo = array();
			$memo['prj_id'] = $prjId;
    		service("Mobile/Remine")->pushType33($prjOrderUid,$remind_time,$prjId,$pushData,$memo);
    	}
    	if(MyError::hasError()){
    		return false;
    	}
    	return true;
    }

    /**
     * 获取费用
     * @param unknown $amount
     * @param unknown $timeLimit
     * @param unknown $timeLimitUnit
     */
    function getFee($amount,$timeLimit,$timeLimitUnit){
        if($timeLimitUnit == PrjModel::LIMIT_UNIT_DAY){
            return ($amount*0.02*$timeLimit)/365;
        }else{
            return ($amount*0.02*$timeLimit)/12;
        }
    }

    /**
     * 费用处理
     * @param unknown $money
     * @param unknown $prjId
     */
    function dealFee($prjId){
    	$fee = M("prj")->where(array("id"=>$prjId))->getField("platform_fee");
    	return $fee?$fee:0;
    }

    /**
     * 通过id查找订单
     * @param unknown $prjOrderId
     */
    function getPrjOrderById($prjOrderId){
        $prjOrderId = (int) $prjOrderId;
        $prjOrderObj = D("Financing/PrjOrder");
        return $prjOrderObj->where(array("id"=>$prjOrderId))->find();
    }

    /**
     * 还款(队列方式)
     * @param unknown $prjId
     * @param unknown $repaymentDate
     * @return boolean
     */
    function repayment($prjRepaymentId,$repaymentDate,$uid){

        $prjRepaymentInfo = M("prj_repayment")->where(array("id"=>$prjRepaymentId))->find();

        if(!$prjRepaymentInfo){
            MyError::add("还款数据未生成!");
            return false;
        }

        $prjId = $prjRepaymentInfo['prj_id'];
        $repaymentTime = is_numeric($repaymentDate) ? $repaymentDate : strtotime($repaymentDate);
        $repaymentDate = date('Y-m-d',$repaymentTime);

        $projectObj = service("Financing/Project");
        $info = $projectObj->getDataById($prjId);

        if(!$info){
            MyError::add("异常，项目不存在");
            return false;
        }

        if($info['bid_status'] == PrjModel::BSTATUS_REPAID){
            MyError::add("异常，项目已经还款，不能重复还款");
            return false;
        }

        if($info['bid_status'] != PrjModel::BSTATUS_REPAYING){
            MyError::add("异常，项目不是待还款状态，不能还款");
            return false;
        }
        //检查还款用户条件
        $tradeUserCheck = $this->tradeCheck($prjId);
        if(!$tradeUserCheck){
        	return errorReturn(MyError::lastError());
        }

        if($info['repay_way'] == PrjModel::REPAY_WAY_ENDATE){
            return $this->repayment_enddate_queue($prjRepaymentId,$prjId,$repaymentDate,$uid);
        }else{
            MyError::add("异常，项目还款方式本站还不支持!");
            return false;
        }


    }

    /**
     * 到期一次性还款(队列方式)
     * @param unknown $prjId
     * @param unknown $repaymentDate
     */
    function repayment_enddate_queue($prjRepaymentId,$prjId,$repaymentDate,$uid){

        $investOder = D("Financing/prjOrder");

        $repaymentTime = strtotime($repaymentDate);
        //生成项目数据,状态为待处理
        M("prj_repayment")->where(array("id"=>$prjRepaymentId))->save(array("repay_time"=>$repaymentTime));
        $data["prj_repayment_id"] = $prjRepaymentId;
        $data["capital"] = 0;
        $data["profit"] = 0;
        $data["last_id"] = 0;
        $data['error_number'] =0;
        $data['repayment_uid'] = $uid;
        $redisObj = RedisList::getInstance(REPAYMENT_QUEUE_KEY);
        $redisObj->persist();//永不过期
        $redisObj->rPush($data);
        return true;
    }

    /**
     * 处理队列
     */
    function repayment_do_queue(){
        $redisObj = RedisList::getInstance(REPAYMENT_QUEUE_KEY);
        if(!$redisObj->lSize()) return true;
        $infoTmp = $redisObj->rPop();
        $info = unserialize($infoTmp);
//         print_r($info);
        $prjRepaymentId = $info['prj_repayment_id'];
        $prjRepaymentInfo = M("prj_repayment")->where(array("id"=>$prjRepaymentId))->find();
        $investOder = D("Financing/prjOrder");
        $prjId = $prjRepaymentInfo['prj_id'];
        $repaymentDateTmp = $prjRepaymentInfo['repay_time'];
        $lastId = $info['last_id'];

        $where['prj_id'] = $prjId;
        $where['status'] = PrjOrderModel::STATUS_PAY_SUCCESS;
        $where['is_regist']= 0;
        $where['repay_status'] = array("neq",PrjOrderModel::REPAY_STATUS_ALL_REPAYMENT);
        $where['id'] = array("GT",$lastId);
        $where['rest_money'] = array("GT",0);
        $model = M("prj_order");

        $list = $model->where($where)->limit(0,50)->order(array("id"=>"ASC"))->select();
        $totalIncome = $info['profit'];
        $totalCapital = $info['capital'];
        $repaymentUid = $info['repayment_uid'];
        if(!$list){
            //线下状态更改
            M("prj_order")->where(array("prj_id"=>$prjId,"is_regist"=>1))->save(array('mtime'=>time(),"repay_status"=>PrjOrderModel::REPAY_STATUS_ALL_REPAYMENT));
            //更新收益和状态
            D("Financing/PrjRepayment")->where(array("id"=>$prjRepaymentId))
            ->save(array("profit"=>$totalIncome,"capital"=>$totalCapital,
            "repayed_capital"=>$totalCapital,"repayed_profit"=>$totalIncome,
            "status"=>PrjRepaymentModel::STATUS_DEAL_WITH_SUCCESS,"mtime"=>time()));
            //更新项目状态
            service("Financing/Project")->updatePrjBidStatus($prjId,PrjModel::BSTATUS_REPAID);
            //更新项目信息
            $totalRepayment = $totalIncome+$totalCapital;
            M("prj")->where(array("id"=>$prjId))->save(array("actual_repay_time"=>time(),"actual_repay_amount"=>$totalRepayment,"mtime"=>time()));
        }else{
            $info = service("Financing/Project")->getPrjInfo($prjId);
            $rateType = $info['rate_type'];
            $rate = $info['rate'];
            $prjType = $info['prj_type'];
            // print_r($list);
            //循环创建
            $errorArray = $info['error_number'];

            foreach ($list as $v){
                $orderId = $v['id'];
                $errorNum = isset($errorArray[$orderId]) ? $errorArray[$orderId]:0;
                if($errorNum > 10){
                    //清空缓存,重新还款
                    $redisObj = RedisList::getInstance(REPAYMENT_QUEUE_KEY);
                    $redisObj->delete();
                    $sql = "UPDATE `fi_prj_repayment` SET repay_time = NULL WHERE prj_id=".$prjId;
                    M()->query($sql);
                    return true;
                }
                //如果是线下登记
                if($v['is_regist']){
                    $repayStatus = PrjOrderModel::REPAY_STATUS_ALL_REPAYMENT;
                    $udata['repay_status'] = $repayStatus;
                    $udata['mtime'] = time();
                    M("prj_order")->where(array("id"=>$orderId))->save($udata);
                    continue;
                }
                $uid = $v['uid'];
                $restMoney = $v['rest_money'];

                $newctime = $v['freeze_time'];
                $valueDate = $info['ext']['value_date'];
                $cDate = date('Y-m-d',$newctime);

                $balanceTime = service("Financing/Project")->getBalanceTime($newctime,$prjId);
                if($balanceTime < $newctime) $valueDate +=1;

                $startDate= strtotime("+".$valueDate." days ",strtotime($cDate));
                $repaymentDate= strtotime("+1 days ",$repaymentDateTmp);

                // echo $prjId."|".$prjType."|".$prjRepaymentId."|".$orderId."|".$rateType."|".$rate."|".date('Y-m-d',$startDate)."|".date('Y-m-d',$repaymentDate)."|".$uid."|".$restMoney."\r\n";
                $income = $this->createRepayment($prjId,$prjType,$prjRepaymentId, $orderId, $rateType,
                           $rate, $startDate, $repaymentDate, $uid, $restMoney,$v['possible_yield'],$repaymentUid);

                if($income !== false){
                    $totalCapital += $restMoney;
                    $totalIncome += $income;
                    $lastId = $v['id'];
                    //发送消息
                    $totalMoney = $income+$restMoney;
                    $userInfo = M("user")->where(array("uid"=>$uid))->find();
                    $messageData = array();
                    $messageData[] = $userInfo['uname'];
                    $messageData[] = $info['prj_name'];
                    $messageData[] = humanMoney($totalMoney,2,false)."元";
                    if($info['borrower_type'] == 1){
                        $messageData[] = $info['corp_name'];
                    }else{
                        $messageData[] = $info['person_name'];
                    }
                    $messageData[] = 1;
                    $messageData[6] = $restMoney;
                    $messageData[7] = $income;
                    service("Message/Message")->sendMessage($uid,1,51,$messageData,$prjId, 1, array(1,2,3), true);
                    //发送还款提醒
                    $pushData = array();
                    $pushData[] = $info['prj_name'];
                    $pushData[] = date('Y-m-d H:i:s');
                    //$remind_time = strtotime((date('Y-m-d')." 12:00:00"))-100;
					$remind_time = time()-100;
                    $this->repaymentPush($uid,$remind_time,$prjId,$pushData);

                }else{
                	$errorNum ++;
                	$errorArray[$orderId] = $errorNum;
                }
            }
            $data["prj_repayment_id"] = $prjRepaymentId;
            $data["capital"] = $totalCapital;
            $data["profit"] = $totalIncome;
            $data["last_id"] = $lastId;
            $data["error_number"] = $errorArray;
            $data['repayment_uid'] = $repaymentUid;
            $redisObj->lPush($data);
        }
        return true;
    }


    /**
     * 创建单个还款数据
     * @param unknown $prjOrderId
     */
    function createRepayment($prjId,$prjType,$prjRepaymentId,$orderId,$rateType,$rate,$startDate,$repaymentDate,$uid,$money,$possibleYield,$repaymentUid, $need_income=TRUE/*是否计算利息*/){
        try{
            $key = "trade_createRepayment_".$orderId;
            $db = new BaseModel();
            $db->startTrans($key);
            $projectObj = service("Financing/Project");

            $income = $need_income ? $projectObj->profitComputeByDate($rateType,$rate,$startDate,$repaymentDate,$money) : 0;
            $repayment = D("Financing/PersonRepayment");
            $id = $repayment->addRepayment($prjId,$money,$income,$uid,$prjRepaymentId,$orderId);
            if(!$id)  throw_exception("添加还款数据失败!");
            $repayNo = $repayment->where(array("id"=>$id))->getField("repay_no");
            //开始还款，，账户处理
// 			$prjInfo = M('prj')->where(array("id"=>$prjId))->find();
			$myUser = M("user")->find($repaymentUid);
			$merchantId = service("Account/Account")->getTenantIdByUser($myUser);
// 			$merchantId = service("Payment/PayAccount")->getTenantAccountId($myUser['dept_id']);
            import_app("Payment.Model.PayAccountModel");
            $objType = PayAccountModel::OBJ_TYPE_REPAY;

            $totalMoney = $money+$income;

            //还款成功,状态更新
            $repayment->where(array("id"=>$id))->save(array("status"=>PersonRepaymentModel::STATUS_REPAYMENT_SUCCESS));
            //判断是否还完
            $orderInfo = M('prj_order')->where(array("id"=>$orderId))->find();
            $repayStatus = PrjOrderModel::REPAY_STATUS_ALL_REPAYMENT;
            if($orderInfo['rest_money'] > $money){//部分还款
                $repayStatus = PrjOrderModel::REPAY_STATUS_PART_REPAYMENT;
            }
            $udata['repay_status'] = $repayStatus;
            $udata['expect_repay_time'] = time();
			$udata['mtime'] = time();
            M("prj_order")->where(array("id"=>$orderId))->save($udata);
            //更新订单信息
            M("prj_order")->where(array("id"=>$orderId))->setDec("rest_money",$money);
            M("prj_order")->where(array("id"=>$orderId))->setInc("repay_principal",$money);
            M("prj_order")->where(array("id"=>$orderId))->setInc("yield",$income);
            //expect_repay_time
            
            // 老项目还款修改统计信息，由于老项目 没有 还款计划，这里初始化一个对象
            $order_repay_plan['rest_principal']=0;
            $order_repay_plan['id']=0;
            if($money > 0 || $income > 0) service("Payment/UserAccountSummary")->changeAcountSummary('repay', $orderId, $money,$income,$order_repay_plan);
            
            
//             //已还款增加,加上利息
//             M("user_account_summary")->where(array("uid"=>$uid))->setInc("repayed_prj_count",1);
//             M("user_account_summary")->where(array("uid"=>$uid))->setInc("repayed_prj_money",$totalMoney);
//             //待还款减少
//             M("user_account_summary")->where(array("uid"=>$uid))->setDec("willrepay_prj_count",1);
//             M("user_account_summary")->where(array("uid"=>$uid))->setDec("willrepay_prj_money",$money);
//             //待收利息，本金减少
//              M("user_account_summary")->where(array("uid"=>$uid))->setDec("will_principal",$money);
//              M("user_account_summary")->where(array("uid"=>$uid))->setDec("will_profit",$possibleYield);

//              M("user_account_summary")->where(array("uid"=>$uid))->save(array("mtime"=>time()));

             $result = service("Payment/PayAccount")->repay($merchantId, $repayNo, $totalMoney, $income, "还款", "", $uid, $objType, $id);
             if(!$result['boolen']) throw_exception("还款操作失败!:".$result['message']);

            $cashout_amount = $income;
            if($cashout_amount) {
                service("Payment/PayAccount")->addCashoutAmount($uid, $cashout_amount);
            }

            //个人回款数据处理
            service("Financing/Invest")->cutUserRepayPlan($orderId);

            $project = service("Financing/Project")->getDataById($prjId);
            //奖励
 $rs= service("Index/ActivitySet")->projectSet($orderInfo['uid'],ActivitySetService::PROJECT_TYPE_REPAYMENT,$project['activity_id'],$orderInfo['id']);
			if(!$rs) throw_exception(MyError::lastError());
            $db->commit($key);
            return $income;
        }catch (Exception $e){
            $exception = array();
            $exception['error'] = $e->getMessage();
            $exception['data'] = array($prjId,$prjType,$prjRepaymentId,$orderId,$rateType,$rate,$startDate,$repaymentDate,$uid,$money,$possibleYield,$repaymentUid);
            D('Public/Exception')->save($exception);
            MyError::add($e->getMessage());
            $db->rollback($key);
            return false;
        }
    }

    //还款推送
    function repaymentPush($uid,$remind_time,$prjId,$pushData){
    	$where['obj_id'] = $prjId;
    	$where['uid'] = $uid;
    	$where['remind_type'] = 34;
    	$info = M("remind")->where($where)->find();

    	if(!$info){
			$memo = array();
			$memo['prj_id'] = $prjId;
    		service("Mobile/Remine")->pushType34($uid,$remind_time,$prjId,$pushData,$memo);
    	}
    	if(MyError::hasError()){
    		return false;
    	}
    	return true;
    }



}
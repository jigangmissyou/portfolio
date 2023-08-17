<?php
import_app("Payment.Model.PayAccountModel");
//支付账户
class PayAccountNewModel extends PayAccountModel{

    protected function _initialize() {
        parent::_initialize();
    }

	protected function formatRecord($ele){
		$ele['amount'] = $ele['to_zs_amount'] + $ele['to_zs_reward_money'] + $ele['to_amount'] + $ele['to_reward_money'] + $ele['to_invest_reward_money']+$ele['to_coupon_money'];// 可用余额
		$ele['give_free_money'] =  $ele['to_free_money'] - $ele['from_free_money']; //免费额度
		$ele['freeze_money'] = '';
		$ele['is_repay'] = 0;
		if(in_array($ele['obj_type'], array(self::OBJ_TYPE_CASHOUT, self::OBJ_TYPE_PAYFREEZE, self::OBJ_TYPE_REPAY_FREEZE))){
			$ele['freeze_money'] = $ele['change_amount'];//冻结
			if($ele['from_cash_freeze_money'] > $ele['to_cash_freeze_money']) $ele['in_or_out'] = TicketModel::INCOMING_FLAG;
		} else {
			$ele['mod_money'] = $ele['change_amount'];//收入 - 支出
		}
        if($ele['obj_type']==self::OBJ_TYPE_CASHOUT){
            $ele['mod_money']=$ele['change_amount'];
        }
        if($ele['obj_type']==self::OBJ_TYPE_REPAY_FREEZE && $ele['in_or_out'] = TicketModel::OUTGOING_FLAG){
            $ele['mod_money']=$ele['change_amount'];
        }
        $prj_id = '';
        $minxi='';
		if($ele['cash_id']){
			//$ele['type'] = '提现';
            //2015414显示类型的优化
            if($ele['obj_type']==self::OBJ_TYPE_CASHOUT)  $ele['type'] = '提现'; //提现冻结 -> 提现
            if($ele['obj_type'] == self::OBJ_TYPE_CASHOUTFAIL) $ele['type'] = '退款';
            if($ele['obj_type'] == self::OBJ_TYPE_CASHOUTDONE || $ele['obj_type'] == self::OBJ_TYPE_CASHOUTPRJDONE) $ele['type'] = '提现';
			if($ele['obj_type'] == self::OBJ_TYPE_OUTSHOUXU){
				$ele['type'] = '手续费';
			}
			$cashout = D('Payment/CashoutApply')->field("id, prj_id, free_money")->find((int)$ele['cash_id']);
			if($cashout['prj_id']){
				$cprj = M('prj')->where(array('id' => $cashout['prj_id']))->find();;
                $ele['ext'] = $cprj['ext'];
				$ele['prj_series'] = $cprj['prj_series'];// 1-高富帅 2-白富美
				$ele['prj_name'] = $cprj['prj_name'];// 项目名称
				$ele['max_bid_amount'] = $cprj['max_bid_amount'];// 项目类型
				$ele['prj_class'] = $cprj['prj_class'];// 项目类型
				$ele['prj_type'] = $cprj['prj_type'];// 项目类型
				$ele['prj_no'] = $cprj['prj_no'];// 项目no
				$ele['safeguards'] = $cprj['safeguards'];
				$ele['type'] = "项目支付";
			}
			$ele['reward_money'] =  $ele['from_reward_money'] - $ele['to_reward_money'];
			if($ele['mod_money'] && $ele['in_or_out'] == 2 && $ele['cash_id']){
				// 						$cashout_apply = M('cashout_apply')->field("reward_money")->where(array("id"=>$ele['cash_id']))->find();
				$ele['reward_money'] = $cashout['reward_money'];
				$ele['use_free_money'] = $cashout['free_money'];
			}
		} else if($ele['ticket_id']){ 
			//$ele['type'] = '充值';
            // if($ele['obj_type'] == self::OBJ_TYPE_RECHARGE_CZ) $ele['type'] = '充值冲正';
            // if($ele['obj_type'] == self::OBJ_TYPE_QFXRECHARGE_CZ) $ele['type'] = '企福鑫充值冲正';
            // if($ele['obj_type'] == self::OBJ_TYPE_REPAYRECHARGE_CZ) $ele['type'] = '还款充值冲正';
            // if($ele['obj_type'] == self::OBJ_TYPE_DEPOSITRECHARGE_CZ) $ele['type'] = '保证金充值冲正';
            // if($ele['obj_type'] == self::OBJ_TYPE_ZRRECHARGE_CZ) $ele['type'] = '直融保证金充值冲正';
            //2015417显示类型的优化
            // if($ele['obj_type']==self::OBJ_TYPE_RECHARGE)  $ele['type'] = '充值';
            // if($ele['obj_type'] == self::OBJ_TYPE_RECHARGE_CZ) $ele['type'] = '冲正';
            // if($ele['obj_type'] == self::OBJ_TYPE_QFXRECHARGE) $ele['type'] = '充值';//$ele['type'] = '企福鑫充值';
            // if($ele['obj_type'] == self::OBJ_TYPE_QFXRECHARGE_CZ) $ele['type'] = '冲正';//$ele['type'] = '企福鑫充值冲正';
            // if($ele['obj_type'] == self::OBJ_TYPE_REPAYRECHARGE)  $ele['type'] = '充值';//$ele['type'] = '还款充值';
            // if($ele['obj_type'] == self::OBJ_TYPE_REPAYRECHARGE_CZ)  $ele['type'] = '冲正';//$ele['type'] = '还款充值冲正';
            // if($ele['obj_type']==self::OBJ_TYPE_DEPOSITRECHARGE) $ele['type'] = '充值';//$ele['type'] = '保证金充值';
            // if($ele['obj_type'] == self::OBJ_TYPE_DEPOSITRECHARGE_CZ) $ele['type'] = '冲正';//$ele['type'] = '保证金充值冲正';
            // if($ele['obj_type'] == self::OBJ_TYPE_ZRRECHARGE) $ele['type'] = '充值';//$ele['type'] = '直融保证金充值';
            // if($ele['obj_type'] == self::OBJ_TYPE_ZRRECHARGE_CZ) $ele['type'] = '冲正';//$ele['type'] = '直融保证金充值冲正';
            if(self::OBJ_TYPE_REVISION == $ele['obj_type']) $ele['type'] = '冲正';
            if($ele['obj_type']==self::OBJ_TYPE_RECHARGE || $ele['obj_type'] == self::OBJ_TYPE_QFXRECHARGE ||
               $ele['obj_type'] == self::OBJ_TYPE_REPAYRECHARGE || $ele['obj_type']==self::OBJ_TYPE_DEPOSITRECHARGE ||
               $ele['obj_type'] == self::OBJ_TYPE_ZRRECHARGE || $ele['obj_type'] == self::OBJ_TYPE_ZS_RECHARGING){
            	$ele['type'] = '充值';

            }
            if($ele['obj_type'] == self::OBJ_TYPE_RECHARGE_CZ || $ele['obj_type'] == self::OBJ_TYPE_QFXRECHARGE_CZ ||
               $ele['obj_type'] == self::OBJ_TYPE_REPAYRECHARGE_CZ || $ele['obj_type'] == self::OBJ_TYPE_DEPOSITRECHARGE_CZ ||
               $ele['obj_type'] == self::OBJ_TYPE_ZRRECHARGE_CZ){
               $ele['type'] = '冲正';
            }
        } else if($ele['obj_type'] == self::OBJ_TYPE_REPAY || $ele['obj_type'] == self::OBJ_TYPE_ZS_RRPAIED  || $ele['obj_type'] == self::OBJ_TYPE_RAISINGINCOME  || self::OBJ_TYPE_REPAY_FREEZE == $ele['obj_type']){
            $ele['is_repay'] = 1;
            //2015415显示类型的优化
            if($ele['obj_type'] == self::OBJ_TYPE_REPAY || $ele['obj_type'] == self::OBJ_TYPE_ZS_RRPAIED || $ele['obj_type'] == self::OBJ_TYPE_RAISINGINCOME){
                $ele['type'] = '回款';
            }
            if(self::OBJ_TYPE_REPAY_FREEZE == $ele['obj_type']){
                if($ele['role']){
                    $ele['type'] = '回款冻结';// 回款冻结 ->(变现)偿还借款本息 Q4
                }else{
                    $ele['type'] = '(变现)偿还借款本息';// 回款冻结 ->(变现)偿还借款本息 Q4
                }

            }
			$person_repayment = M('person_repayment')->where(array("id"=>$ele['obj_id']))->find();

            $prj_id=$person_repayment['prj_id'];

			if($ele['obj_type'] == self::OBJ_TYPE_RAISINGINCOME) {
                $minxi = '(募集期资金占用费)';
            } elseif ($ele['obj_type'] == self::OBJ_TYPE_ZS_RRPAIED) {
                $order_plan = M('prj_order_repay_plan')->where(array("id"=>$ele['obj_id']))->find();
                $prj_id = $order_plan['prj_id'];
                $ele['is_repay'] = 0;

			} else {
				$order_plan = M('prj_order_repay_plan')->where(array("person_repayment_id"=>$person_repayment['id']))->find();
				if(!$order_plan){
					$minxi = '(本息)';
//				} else if($order_plan['repay_periods'] == 0){
//					$minxi = '(募集期资金占用费)';
				} else if($order_plan['principal'] > 0 && $order_plan['yield'] > 0){
					$minxi = '(本息)';
				} else if($order_plan['principal'] > 0){
					$minxi = '(本金)';
				} else if($order_plan['yield'] > 0){
                    if($order_plan['ptype'] == 1) $minxi = '(利息)';
                    if($order_plan['ptype'] == 2) $minxi = '(募集期资金占用费)';
                    if($order_plan['ptype'] == 3) $minxi = '(利息补偿)';
				}
                $order = M('prj_order')->find($order_plan['prj_order_id']);
                $creditor_rights_status = $order['creditor_rights_status'];
			}
            // if(self::OBJ_TYPE_REPAY_FREEZE == $ele['obj_type']){
            //     if($creditor_rights_status == 2) $ele['type'] = '债权转让回款冻结'.$minxi;
            //     else $ele['type'] = '冻结回款'.$minxi; 
            // } else {
            //     if($creditor_rights_status == 2) $ele['type'] = '债权转让回款'.$minxi;
            //     else $ele['type'] = '回款'.$minxi;
            // }
		} else if($ele['obj_type'] == self::OBJ_TYPE_SHOUXU || $ele['obj_type'] == self::OBJ_TYPE_REPAY_SHOUXU || $ele['obj_type'] == self::OBJ_TYPE_OUTSHOUXU){
			$ele['type'] = '手续费';
		}else if($ele['obj_type'] == self::OBJ_TYPE_REWARD || $ele['obj_type'] == self::OBJ_TYPE_REWARD_ZS){
            $ele['type'] = '奖励';//2015415显示类型的优化
        } 
         else if($ele['obj_type'] == self::OBJ_TYPE_REWARD_CASHOUT){
			$ele['type'] = '奖励';
		} else if($ele['obj_type'] == self::OBJ_TYPE_REWARD_INVEST){
			$ele['type'] = '奖励';
		}else if($ele['obj_type'] == self::OBJ_TYPE_USER_BONUS){
            $ele['type'] = '奖励';//2015415显示类型的优化
        }
         else if($ele['obj_type'] == self::OBJ_TYPE_USER_CASH_BONUS){
             $ele['type'] = '奖励';//2015415显示类型的优化
         }else if($ele['obj_type'] == self::OBJ_TYPE_USER_TYJ_CASH_BONUS){
             $ele['type'] = '奖励';
         }
         else if($ele['obj_type'] == self::OBJ_TYPE_CANEL_BONUS){
            $ele['type'] = '奖励过期';//2015415显示类型的优化
        } 
        else if($ele['obj_type'] == self::OBJ_TYPE_USE_COUPON){
			//$ele['type'] = '奖励抵用券';
            $ele['type'] = '奖励';//2015415显示类型的优化
		} else if($ele['obj_type'] == self::OBJ_TYPE_ADD_COUPON){
			$ele['type'] = '奖励';
		} else if($ele['obj_type'] == self::OBJ_TYPE_XPRJ_JIAXI_ADDON){ //20161107 司马小鑫加息券奖励
            $ele['type'] = '奖励';
        } else if($ele['obj_type'] == self::OBJ_TYPE_GIVEXREWARD){ //20161107 司马小鑫加息券奖励
            $ele['type'] = '奖励';
        } else if($ele['obj_type'] == self::OBJ_TYPE_EXPIRE_COUPON){
			//$ele['type'] = '奖励过期回收';
            $ele['type'] = '奖励过期';//2015415显示类型的优化
		} else if($ele['obj_type'] == self::OBJ_TYPE_REWARD_CBACK || $ele['obj_type'] == self::OBJ_TYPE_REWARD_IBACK){
			//$ele['type'] = '奖励过期回收';
            $ele['type'] = '奖励过期';//2015415显示类型的优化
		} else if(self::OBJ_TYPE_ALLOT == $ele['obj_type']){
			$ele['type'] = '划拨';
		}else if(self::OBJ_TYPE_TRANSFERMONEY == $ele['obj_type']){
            $ele['type'] = '转账';
        }else if(self::OBJ_TYPE_JJS_TRANSFERMONEY == $ele['obj_type']) {
            $ele['type'] = '划转';//2017.2.13增加 金交所转账到SPV机构账户
        }else if(self::OBJ_TYPE_ROLLOUT == $ele['obj_type']){
            $ele['type']='回款';//2015415显示类型的优化
            //$ele['type'] = '冻结资金划出到余额';
            $minxi = '冻结资金划出到余额';
            $hasprjA = strpos($ele['adesc'], 'prj_account_rollout_');
            if($hasprjA !== false) { 
                //$ele['type'] = '还款完成冻结资金划出';
                //$minxi='还款完成冻结资金划出';
                $minxi='还款完成';
                $prjAccountUid = substr($ele['adesc'], strlen('prj_account_rollout_'));
                $prjAccount = M('user_prj_account')->find($prjAccountUid);
                $prj_id = $prjAccount['prj_id'];
                if($prjAccount['base_order_id'] && $prjAccount['base_prj_id'] == $prjAccount['prj_id']){
                    $order = M('prj_order')->find($prjAccount['base_order_id']);
                    if($order['creditor_rights_status'] == 2)  $minxi='债权转让资金划出';//$ele['type'] = '债权转让资金划出';
                }
            }
		} 
		 else if(self::OBJ_TYPE_TRANSFER == $ele['obj_type']){
                if($ele['in_or_out'] == 1) $ele['type'] = '转让';
                if($ele['in_or_out'] == 2) $ele['type'] = '转让';
		// 	if($ele['in_or_out'] == 1) $ele['type'] = '卖出债权';
		// 	if($ele['in_or_out'] == 2) $ele['type'] = '买入债权';
		 	$ele['reward_money'] =  $ele['from_reward_money'] - $ele['to_reward_money'];
		 	$ele['invest_reward_money'] =  $ele['from_invest_reward_money'] - $ele['to_invest_reward_money'];
		 }  
		else if($ele['obj_type'] == '90001' || $ele['obj_type'] == self::OBJ_TYPE_REWARDFREEMONEY){
			//$ele['type'] = '奖励免费提现额度';
            $ele['type'] = '奖励';
			$ele['mod_money'] = 0;
		} else if($ele['obj_type'] == self::OBJ_TYPE_REBACK){
			$ele['type'] = '退款';
		} else if($ele['obj_type'] == self::OBJ_TYPE_SHOUXU_SDT){
            //$ele['type'] = '速兑通服务费';
            //2015414显示类型的优化
            $ele['type'] = '手续费';
            $prj_id = $ele['obj_id'];
        } else if($ele['obj_type'] == self::OBJ_PT_SERVICE_FEE){
			//$ele['type'] = '服务费';//2015415显示类型的优化去除
            $prj_id = $ele['obj_id'];
		}else if($ele['obj_type'] == self::OBJ_TYPE_CUTINREPAY){
            //$ele['type'] = '划转到还款账户';
            $ele['type'] = '划拨';
            $ele['in_or_out'] = TicketModel::INCOMING_FLAG;
            $ele['freeze_money'] = $ele['change_amount'];
        }else if($ele['obj_type'] == self::OBJ_TYPE_ZS_MOVING){
            $ele['type'] = '余额转移';
            $ele['in_or_out'] = TicketModel::OUTGOING_FLAG;
        }else if($ele['obj_type'] == self::OBJ_TYPE_ZS_MOVED){
            $ele['type'] = '余额转移';
            $ele['in_or_out'] = TicketModel::INCOMING_FLAG;
        }else {
			//$ele['type'] = '投资';
            //2015415显示类型的优化
            //20151221 Q4 去除投资 投资冻结 -> 投资
            if($ele['obj_type'] == self::OBJ_TYPE_PAYFREEZE) $ele['type'] = '投资';
            if($ele['obj_type'] == self::OBJ_TYPE_PAY)$ele['type'] = '投资';
            if($ele['obj_type'] == self::OBJ_TYPE_ZS_BUY_FREEZE)$ele['type'] = '投资';

			$ele['reward_money'] =  $ele['from_reward_money'] - $ele['to_reward_money'];
			$ele['invest_reward_money'] =  $ele['from_invest_reward_money'] - $ele['to_invest_reward_money'];
			if($ele['mod_money'] && $ele['in_or_out'] == 2 && $ele['pay_order_id']){
				$pay_order = M('pay_order')->field("reward_money, invest_reward_money")->where(array("id"=>$ele['pay_order_id']))->find();
				$ele['reward_money'] = $pay_order['reward_money'];
				$ele['invest_reward_money'] = $pay_order['invest_reward_money'];
			}
		}
		//if($ele['obj_type'] == self::OBJ_TYPE_USER_BONUS) $ele['type'] .= '(红包入账)';
		// 				$ele['ctime'] = date("Y-m-d H:i:s", $ele['ctime']);
		$ele['is_transfer'] = 0;
		if(in_array($ele['obj_type'], array(self::OBJ_TYPE_REBACK, self::OBJ_TYPE_PAYFREEZE, self::OBJ_TYPE_PAY, self::OBJ_TYPE_ZS_BUY_FREEZE))){
			$prj_order = M('prj_order')->where(array("id"=>$ele['obj_id']))->find();
            if($ele['obj_type']==self::OBJ_TYPE_PAY && $ele['in_or_out'] == 1) $ele['type'] = '变现';
			$prj_id = $prj_order['prj_id'];
			$ele['from_order_id'] = $prj_order['from_order_id'];
		} else if($ele['obj_type'] == self::OBJ_TYPE_REPAY || $ele['obj_type'] == self::OBJ_TYPE_RAISINGINCOME || self::OBJ_TYPE_REPAY_FREEZE == $ele['obj_type']){
			 $person_repayment = M('person_repayment')->where(array("id"=>$ele['obj_id']))->find();
			// $prj_id = $person_repayment['prj_id'];
            $repay_order_plan = M('prj_order_repay_plan')->where(array("person_repayment_id"=>$person_repayment['id']))->find();
			$to_order_id = $person_repayment['order_id'];
			$fedTransfer = M("asset_transfer")->where(array("to_order_id"=>$to_order_id))->find();
			if($fedTransfer){
				$ele["from_order_id"] = $fedTransfer["from_order_id"];
				$ele['transfer_id'] = $fedTransfer['id'];
			}
		} else if($ele['obj_type'] == self::OBJ_TYPE_TRANSFER){
			$asset_transfer = M('asset_transfer')->where(array("id"=>$ele['obj_id']))->find();
			$prj_id = $asset_transfer['prj_id'];
			$ele['transfer_id'] = $asset_transfer['id'];
			$ele['is_transfer'] = 1;
			$ele['transfer_fee'] = $asset_transfer['fee'];
			$ele["from_order_id"] = $asset_transfer["from_order_id"];
		} elseif (in_array($ele['obj_type'], array(self::OBJ_TYPE_USER_BONUS, self::OBJ_TYPE_ZC_ZHINAJIN, self::OBJ_TYPE_ZC_PRI_INTEREST, self::OBJ_TYPE_ZC_FAXI))) {
            $prj_id = $ele['obj_id'];
        }

		if($prj_id){
			$prj = M('prj')->where(array('id' => $prj_id))->find();;
            if($ele['type'] == '变现' && $prj['prj_type'] != 'H'){
                $ele['type'] = '项目融资';
            }
            $is_deposit = (int) M('prj_ext')->where(array('prj_id' => $prj_id))->getField('is_deposit');
			$ele['prj_series'] = $prj['prj_series'];// 1-高富帅 2-白富美
			$ele['repay_way'] = $prj['repay_way'];
			if($ele['from_order_id']){
				$ele['prj_series_name'] =  "富二代";
			}else if($ele['prj_series'] == 1)	$ele['prj_series_name'] =  "高富帅";
			else if($ele['prj_series'] == 2)	$ele['prj_series_name'] =  "白富美";
			$ele['repay_way'] = $prj['repay_way'];
			$ele['ext'] = $prj['ext'];// 项目名称
			$ele['prj_name'] = $prj['prj_name'];// 项目名称
			$ele['is_new'] = $prj['is_new'];// 是否新客项目
			$ele['prj_no'] = $prj['prj_no'];// 项目no
			$ele['prj_id'] = $prj_id;
			$ele['prj_class'] = $prj['prj_class'];
			$ele['prj_type'] = $prj['prj_type'];// 项目类型
			$ele['safeguards'] = $prj['safeguards'];
            $ele['huodong'] = $prj['huodong'];
            $ele['bid_status'] = $prj['bid_status'];
			$ele['activity_id'] = $prj['activity_id'];
			$ele['transfer_id'] = $prj['transfer_id'];
			$ele['max_bid_amount'] = $prj['max_bid_amount'];
            if($ele['obj_type'] == self::OBJ_TYPE_REBACK){
                $ele['remark']="“${ele['prj_name']}” 项目流标，退回本金";
            }else if($ele['obj_type'] == self::OBJ_TYPE_REPAY || $ele['obj_type'] == self::OBJ_TYPE_ZS_RRPAIED || self::OBJ_TYPE_ROLLOUT == $ele['obj_type']){
                $ele['remark']="项目“${ele['prj_name']}” 回款";//回款提示调整 去除mixi Q4
            }else if($ele['obj_type'] == self::OBJ_TYPE_RAISINGINCOME){
                $ele['remark']="“${ele['prj_name']}” 项目回款募集期资金占用费";
            }else if($ele['obj_type'] == self::OBJ_TYPE_CASHOUTPRJDONE){
                $ele['remark']="提现用于 “${ele['prj_name']}” 项目支出";
            }else if($ele['obj_type'] == self::OBJ_TYPE_USER_BONUS || $ele['obj_type'] == self::OBJ_TYPE_USE_COUPON){
                $ele['remark']="“${ele['prj_name']}” 项目所使用红包";
            }else if($ele['obj_type'] == self::OBJ_TYPE_USER_CASH_BONUS){
                //如果是转换。
                $ele['remark'] = "“${ele['prj_name']}” 项目所使用红包";
            }else if(($ele['obj_type'] == self::OBJ_TYPE_PAY || $ele['obj_type'] == self::OBJ_TYPE_ZS_BUY_FREEZE) and $ele['in_or_out']==2){
                $ele['remark']="投资项目 “${ele['prj_name']}” ";
            }else if($ele['obj_type'] == self::OBJ_TYPE_PAYFREEZE){
                $ele['remark']="投资项目 “${ele['prj_name']}” ";//投资冻结提示改为投资成功提示 Q4
            }else if($ele['obj_type'] == self::OBJ_TYPE_SHOUXU_SDT){
                $ele['remark']="速兑通 “${ele['prj_name']}” 手续费";
            }else if($ele['obj_type'] == self::OBJ_TYPE_PAY && $ele['in_or_out'] == 1){
                if($ele['role']) {
                    $ele['remark']="项目 “${ele['prj_name']}” 融资";
                } else {
                    $ele['remark']="速兑通 “${ele['prj_name']}” 变现";
                }
            }
        }else{
            if($ele['obj_type'] == self::OBJ_TYPE_RECHARGE_CZ || $ele['obj_type'] == self::OBJ_TYPE_QFXRECHARGE_CZ){
                $ele['remark']="充值冲正";
            }else if($ele['obj_type'] == self::OBJ_TYPE_REPAYRECHARGE){
                $ele['remark']="还款充值";
            }else if($ele['obj_type'] == self::OBJ_TYPE_REPAYRECHARGE_CZ){
                $ele['remark']="还款充值冲正";
            }else if($ele['obj_type'] == self::OBJ_TYPE_DEPOSITRECHARGE || $ele['obj_type'] == self::OBJ_TYPE_ZRRECHARGE){
                $ele['remark']="保证金充值";
            }else if($ele['obj_type'] == self::OBJ_TYPE_DEPOSITRECHARGE_CZ || $ele['obj_type'] == self::OBJ_TYPE_ZRRECHARGE_CZ){
                $ele['remark']="保证金充值冲正";
            }else if($ele['obj_type'] == self::OBJ_TYPE_CASHOUTFAIL){
                $ele['remark']="提现失败";
            }else if($ele['obj_type'] == self::OBJ_TYPE_REPAY_SHOUXU && !$ele['remark']){
                $ele['remark']="转账手续费";
            }else if($ele['obj_type'] == self::OBJ_TYPE_OUTSHOUXU){
                $ele['remark']="提现手续费";
            }else if($ele['obj_type'] == self::OBJ_TYPE_USER_CASH_BONUS){
                $ele['remark'] = "现金红包兑款账户余额";
            }else if($ele['obj_type'] == self::OBJ_TYPE_ZS_MOVING){
                $ele['remark'] = "原账户余额正转移至投资人存管户";
            }else if($ele['obj_type'] == self::OBJ_TYPE_ZS_MOVED){
                $ele['remark'] = "原账户余额已转移至投资人存管户";
            }else if($ele['adesc']=='理财运动会现金奖励入账') {
				$ele['remark'] = "理财运动会现金奖励入账";
            }else if($ele['obj_type'] == self::OBJ_TYPE_CASHOUT){//提现申请
                $_row = M('cashout_apply')->where(['id' => $ele['obj_id']])->field('bank,money,out_account_no')->find();
                $ele['remark'] = "提现" . humanMoney($_row['money'], 2, false) . "元至" . getCodeItemName('E009', $_row['bank']) . "，尾号" . substr($_row['out_account_no'], -4);
            }else if($ele['obj_type'] == self::OBJ_TYPE_RECHARGE){
//                $xianxian_id = M('out_ticket')->where(['id' => $ele['obj_id']])->getField('xianxia_id');
//                $xianxia_data = M('xianxia_recharge')->where(['id' => $xianxian_id])->getField('id,bank_name,apply_amount,account_no');
//                if($xianxia_data){
//                    $ele['remark'] = $xianxia_data[$xianxian_id]['bank_name'].' 尾号'.substr($xianxia_data[$xianxian_id]['account_no'],-4).' 充值'.number_format($ele['mod_money']/100, 2);
//                }
                $_row = M('out_ticket')->where(['id' => $ele['ticket_id']])->field('sub_bank,amount,out_account_no')->find();
                $ele['remark'] = "尾号" . substr($_row['out_account_no'], -4) . "充值" . humanMoney($_row['amount'], 2, false) . "元" ;
                if($_row['sub_bank']) { // 有时候没有这个字段值
                    $ele['remark'] = getCodeItemName('E009', $_row['sub_bank']). "，" . $ele['remark'];
                }
            }


			if(!$ele['remark'] && $ele['adesc'] == '”双11“活动抽奖获得5元现金')
			{
				$ele['remark'] = '”双11“活动抽奖获得5元现金';
			}

			if(!$ele['remark'] && $ele['adesc'] == '邀请好友完成首投满5000元')
			{
				$ele['remark'] = $ele['adesc'];
			}

			if(!$ele['remark'] && $ele['adesc'] == '双旦活动抽奖获得1元现金')
			{
				$ele['remark'] = $ele['adesc'];
			}

			if(!$ele['remark'] && $ele['adesc'] == '双旦活动抽奖获得2元现金')
			{
				$ele['remark'] = $ele['adesc'];
			}
            if(!$ele['remark'] && substr($ele['adesc'], 0, 33) == '参加阵营挑战活动获得第')
            {
                $ele['remark'] = $ele['adesc'];
            }
            if(!$ele['remark'] && $ele['type'] == "项目支付") {
                $ele['remark'] = "项目 “${ele['prj_name']}” 支付";
            }
            if(in_array($ele['obj_type'], array(self::OBJ_TYPE_REWARD, self::OBJ_TYPE_REWARD_CASHOUT, self::OBJ_TYPE_REWARD_INVEST))) {
                if($ele['adesc']) {
                    $ele['remark'] = $ele['adesc'];
                }
            }
        }
                // $ele['remark']='"'.$ele['prj_name'].'"'.'项目流标，退回本金'.$prj_id.'|'.$ele['obj_id'].'-'.$ele['obj_type'];
        $typeViewList = $this->getTypeView();
        if (in_array($ele['obj_type'], array_keys($typeViewList))) {
            $ele['type'] = $typeViewList[$ele['obj_type']];
            if(!$ele['remark']) $ele['remark'] = $ele['type'];
        }
        $ele['is_deposit'] = $is_deposit || $ele['is_zs'];
        //司马小鑫相关流水类型的文字描述 begin
        switch ($ele['obj_type'])
        {
            case self::OBJ_TYPE_JOIN_IN_X:
                $ele['type'] = "投资";
                break;
            case self::OBJ_TYPE_XRPJ_SETTLEMENT:
                $ele['type'] = "回款";
                break;
            case self::OBJ_TYPE_XRPJ_RATE_ADDON:
                $ele['type'] = "奖励";
                break;
            case self::OBJ_TYPE_XRPJ_ACTIVITY:
                $ele['type'] = "奖励";
                break;
        }
        //司马小鑫相关流水类型的文字描述 end

		return $ele;
	}
    public function showFiveMoneyList($uid){
        $map['obj_type'] = PayAccountModel::OBJ_TYPE_PAY;
        $map['in_or_out'] = 1;
        $map2['_complex'] = $map;
        $map2['obj_type'] = array('not in',PayAccountModel::OBJ_TYPE_REPAY_FREEZE.",".PayAccountModel::OBJ_TYPE_PENALTY.",".
            PayAccountModel::OBJ_TYPE_CANEL_BONUS.",".PayAccountModel::OBJ_TYPE_ADD_COUPON.",".
            PayAccountModel::OBJ_TYPE_EXPIRE_COUPON.",90001".",".PayAccountModel::OBJ_TYPE_REWARDFREEMONEY.",".PayAccountModel::OBJ_TYPE_PAY.",".PayAccountModel::OBJ_TYPE_CASHOUTDONE.','.
            PayAccountModel::OBJ_TYPE_XRPJ_BALANCE_INCOME.','. PayAccountModel::OBJ_TYPE_XRPJ_BALANCE_OUT.','. PayAccountModel::OBJ_TYPE_PAYFREEZE_X.','.
            PayAccountModel::OBJ_TYPE_PAY_X.',' . PayAccountModel::OBJ_TYPE_PAY_X_TRANSFER.','.PayAccountModel::OBJ_TYPE_ROLLOUTX.','.PayAccountModel::OBJ_TYPE_REWARD_CASHOUTX.','.PayAccountModel::OBJ_TYPE_GIVEXREWARD
            .','.PayAccountModel::OBJ_TYPE_ZS_MOVING.','.PayAccountModel::OBJ_TYPE_ZS_MOVED.','.PayAccountModel::OBJ_TYPE_ZS_REPAYING);//这些类型不展示
        $map2['_logic'] = 'or';
        $condition['_complex'] = $map2;
        $condition['uid'] = $uid;
        $data = M('user_account_record')->where($condition)->order('id desc')->limit(5)->select();
        if($data){
            $list = array();
            foreach($data as $ele){
//                if($ele['obj_type'] == PayAccountModel::OBJ_TYPE_PAY) continue;//投资的记录不再显示 投资冻结 -> 投资 Q4
//                if($ele['obj_type'] == PayAccountModel::OBJ_TYPE_CASHOUT) continue;//提现的记录不再显示 提现冻结 -> 提现 Q4
                $ele = $this->formatRecord($ele);
                $list[] = $ele;
            }
        }
        return $list;
}

}

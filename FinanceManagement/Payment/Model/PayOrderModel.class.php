<?php
//支付订单
class PayOrderModel extends BaseModel{
	protected $tableName = 'pay_order';
	
	const STATUS_INIT = 1;//待处理
	const STATUS_FREEZE = 2;//冻结
	const STATUS_PAID = 3;//已支付
	const STATUS_NOPAID = 4;//不支付
	const STATUS_REPAID = 5;//已还款结束
	const STATUS_REBACK = 6;//退款
	const STATUS_DIRECTPAY = 7;//直接支付
	
	const STATUS_REWARD_CASHOUT = 8;//可以提现奖励
	const STATUS_REWARD_INVEST = 9;//投资后可提现奖励
	
	const STATUS_REWARD_CBACK = 81;//可以提现奖励过期
	const STATUS_REWARD_IBACK = 91;//投资后可提现奖励过期
	
	const STATUS_ALLOT = 10;//资金划拨
	const STATUS_ROLLOUT = 11;//转出支付余额资金

    const STATUS_CUTINREPAY = 1002;//资金划拨到还款冻结账户
	
	const STATUS_COUPON_USE = 172;//使用券

    const STATUS_BONUS_USER= 162;//新红包使用
    const STATUS_BONUS_CANEL= 163;//新红包核銷
	
	const API_ID = 1;// 内部使用的api_id

    protected $yunying_account_adesc = '';
	
	//支付冻结, 外面请加事物 $money是需要支付的总额含fee
	public function freezeInit($uid, $out_order_no, $money, $reward_money, $invest_reward_money, $fee, $title, $desr, $to_user, $obj_type, $obj_id, $free_money, $api_id=self::API_ID){
		$key = "freeze_{$out_order_no}_{$api_id}_" . date("Ymdhi");
		if(cache($key)) {
			throw_exception("该订单已经处理过了");
		}
		
		$has = $this->where(array('out_order_no'=>$out_order_no, 'api_id'=>$api_id))->find();
		if($has) throw_exception("该订单已经处理过了");
		$uid = (int)$uid;
		$to_user = (int)$to_user;
		$account = D('Payment/PayAccount')->find($to_user);
		if(!$account) throw_exception("支付的商户账户异常");
		$this->startTrans();
		try{
			$data['uid'] = (int)$uid;
			$data['order_no'] = $this->createNo();
			$data['api_id'] = $api_id;
			$data['out_order_no'] = $out_order_no;
			$data['money'] = $money;
			$data['freeze_free_money'] = $free_money;
			$data['reward_money'] = $reward_money;
			$data['invest_reward_money'] = $invest_reward_money;
			$data['fee'] = $fee;
			$data['title'] = $title;
			$data['desr'] = $desr;
			$data['to_user'] = $to_user;
			$data['merchant_id'] = $to_user;
			$data['obj_type'] = $obj_type;
			$data['obj_id'] = $obj_id;
			$data['status'] = self::STATUS_FREEZE;
			$data['ctime'] = time();
			$data['mtime'] = time();
			$orderId = $this->add($data);
			if(!$orderId){
				throw_exception("系统数据异常ORDFI");
			}

			$pay_amount_model = D('Payment/PayAccount');
			if ($obj_type == PayAccountModel::OBJ_TYPE_PAYFREEZE_X) {
				//司马小鑫购买的
				$pay_amount_model->freezePayX($uid, $money, $to_user, $obj_type, $obj_id, $orderId, $free_money);
			} elseif ($obj_type == PayAccountModel::OBJ_TYPE_ZS_BUY_FREEZE) {
				$pay_amount_model->freezePayZs($uid, $money, $reward_money, $invest_reward_money, $to_user, $obj_type, $obj_id, $orderId, $free_money);
			} else {
				$pay_amount_model->freezePay($uid, $money, $reward_money, $invest_reward_money, $to_user, $obj_type, $obj_id, $orderId, $free_money);
			}

			$this->commit();
			cache($key, $orderId, array('expire'=>60));
			return $data;
		}catch (Exception $e){
			$this->rollback();
			throw $e;
		}
	}
	//冻结后支付
	public function pay($out_order_no, $free_money=0, $to_user='', $api_id=self::API_ID){
		$key = "pay_{$out_order_no}_{$api_id}_pay_" . date("Ymdhi");
		if(cache($key)) {
			throw_exception("该订单已经处理过了");
		}
		$where['api_id'] = $api_id;
		$where['out_order_no'] = $out_order_no;
		$order = $this->where($where)->find();
		if(!$order) throw_exception("订单不存在");
		if($order['status'] != self::STATUS_FREEZE) throw_exception("该订单不是冻结状态");
		$this->startTrans();
		try{
			$orderData['id'] = $order['id'];
			$orderData['free_money'] = $free_money;
			if($to_user){
				$orderData['to_user'] = $to_user;
				$order['to_user'] = $to_user;
			}
			$this->save($orderData);
			
			$money = $order['money'] + $order['reward_money'];
			D('Payment/PayAccount')->pay($order['uid'], $money, $order['fee'], $order['to_user'], PayAccountModel::OBJ_TYPE_PAY, $order['obj_id'], $order['id'], $free_money);
			$data['id'] = $order['id'];
			$order['status'] = $data['status'] = self::STATUS_PAID;
			$order['mtime'] = $data['mtime'] = time();
			$res = $this->save($data);
            //支付的时候 如果包含红包,就更新用户的收益

			if(!$res){
				throw_exception("系统数据异常ORDP1");
			}
			$this->commit();
			cache($key, $order['id'], array('expire'=>10));
			return $order;
		}catch (Exception $e){
			$this->rollback();
			throw $e;
		}
	}
	
	//对冻结的金额 退款
	public function reback($out_order_no, $api_id=self::API_ID){
		$key = "reback_{$out_order_no}_{$api_id}_reback_" . date("Ymdhi");
		if(cache($key)) {
			throw_exception("该订单已经处理过了");
		}
		$where['api_id'] = $api_id;
		$where['out_order_no'] = $out_order_no;
		$order = $this->where($where)->find();
		if(!$order) throw_exception("订单不存在");
		if($order['status'] != self::STATUS_FREEZE) throw_exception("该订单不是冻结状态");
		$this->startTrans();
		try{
			D('Payment/PayAccount')->reback($order['uid'], $order['money'], $order['reward_money'], $order['invest_reward_money'], $order['to_user'], PayAccountModel::OBJ_TYPE_REBACK, $order['obj_id'], $order['id'], $order['freeze_free_money']);
			$data['id'] = $order['id'];
			$order['status'] = $data['status'] = self::STATUS_REBACK;
			$order['mtime'] = $data['mtime'] = time();
			$res = $this->save($data);
			if(!$res){
				throw_exception("系统异常ORDR1");
			}
			$this->commit();
			cache($key, $order['id'], array('expire'=>10));
			return $order;
		}catch (Exception $e){
			$this->rollback();
			throw $e;
		}
	}
	
	//直接支付（转账用） 转让购买/给募集期利（从募集账户转让到用户） is_merchant=1
	public function directPay($uid, $out_order_no, $money, $reward_money, $invest_reward_money, $fee, $title, $desr, $to_user, $obj_type, $obj_id, $is_merchant, $free_money=0, $profit=0, $api_id=self::API_ID){
		$key = "direct_{$out_order_no}_{$api_id}_" . date("Ymdhi");
		if(cache($key)) {
			throw_exception("该订单已经处理过了");
		}
		
		$has = $this->where(array('out_order_no'=>$out_order_no, 'api_id'=>$api_id))->find();
		if($has) throw_exception("该订单已经处理过了");
		$uid = (int)$uid;
		$to_user = (int)$to_user;
		$account = D('Payment/PayAccount')->find($to_user);
		if(!$account) throw_exception("支付的商户账户不存在");
		if(!$account['is_merchant'] && $is_merchant) throw_exception("支付的商户账户异常");
		$this->startTrans();
		try{
			$data['uid'] = (int)$uid;
			$data['order_no'] = $this->createNo();
			$data['api_id'] = $api_id;
			$data['out_order_no'] = $out_order_no;
			$data['money'] = $money;
			$data['reward_money'] = $reward_money;
			$data['invest_reward_money'] = $invest_reward_money;
			$data['free_money'] = $free_money;
			$data['fee'] = $fee;
			$data['profit'] = $profit;
			$data['title'] = $title;
			$data['desr'] = $desr;
			$data['to_user'] = $to_user;
			if($is_merchant) $data['merchant_id'] = $to_user;
			$data['obj_type'] = $obj_type;
			$data['obj_id'] = $obj_id;
			$data['status'] = self::STATUS_DIRECTPAY;
			$data['ctime'] = time();
			$data['mtime'] = time();
			$data['id'] = $orderId = $this->add($data);
			if(!$orderId){
				throw_exception("系统数据异常ORDDP1");
			}
			
			D('Payment/PayAccount')->setAdesc($desr)->setDescEqRemark(true)->outcoming($uid, $money, $to_user, $obj_type, $obj_id, $orderId, $is_merchant, $reward_money, $invest_reward_money, $free_money);
			if($obj_type == PayAccountModel::OBJ_TYPE_RAISINGINCOME){
				D('Payment/PayAccount')->incoming($to_user, ($money + $reward_money + $invest_reward_money - $fee), $uid, $obj_type
						, $obj_id, $orderId, ($money + $reward_money + $invest_reward_money - $fee));
			} else {
				D('Payment/PayAccount')->incoming($to_user, ($money + $reward_money + $invest_reward_money - $fee), $uid, $obj_type, $obj_id, $orderId, $profit);
			}
			if($fee){
				D('Payment/PayAccount')->incoming(PLAT_ACCOUNTID, $fee, $uid, PayAccountModel::OBJ_TYPE_SHOUXU, $orderId, '');
			}
			$this->commit();
			cache($key, $orderId, array('expire'=>60));
			return $data;
		}catch (Exception $e){
			$this->rollback();
			throw $e;
		}
	}

    public function repayDirectPay($merchant_id, $out_order_no, $money, $title, $desr, $to_user, $obj_type, $obj_id, $desc='', $is_merchant=false, $api_id=self::API_ID){
        $key = "repay_{$out_order_no}_{$api_id}_" . date("Ymdhi");
        if(cache($key)) {
            throw_exception("该订单已经处理过了");
        }

        $has = $this->where(array('out_order_no'=>$out_order_no, 'api_id'=>$api_id))->find();
        if($has) throw_exception("该订单已经处理过了");
        $merchant_id = (int)$merchant_id;
        $merchant = D('Payment/PayAccount')->find($merchant_id);
        if(!$merchant) throw_exception("支付的商户账户不存在");

        $to_user = (int)$to_user;
        $account = D('Payment/PayAccount')->find($to_user);
        if(!$account) throw_exception("收款的账户不存在");
        if($is_merchant && !$account['is_merchant']) throw_exception("收款的代扣账户必须是商户账户");
        $this->startTrans();
        try{
            $data['uid'] = $merchant_id;
            $data['order_no'] = $this->createNo();
            $data['api_id'] = $api_id;
            $data['out_order_no'] = $out_order_no;
            $data['repay_money'] = $money;
            $data['profit'] = 0;
            $data['title'] = $title;
            $data['desr'] = $desr;
            $data['to_user'] = $to_user;
            $data['obj_type'] = $obj_type;
            $data['obj_id'] = $obj_id;
            if($is_merchant) $data['merchant_id'] = $to_user;
            $data['status'] = self::STATUS_REPAID;
            $data['ctime'] = time();
            $data['mtime'] = time();
            $data['id'] = $orderId = $this->add($data);
            if(!$orderId){
                throw_exception("系统数据异常ORDDP2");
            }
            D('Payment/PayAccount')->repayDirectPay($merchant_id, $money, $to_user, $obj_type, $obj_id, $orderId, $desc, $is_merchant);
            $this->commit();
            cache($key, $orderId, array('expire'=>60));
            return $data;
        }catch (Exception $e){
            $this->rollback();
            throw $e;
        }
    }
    //项目还款冻结(处理还款来源不同，逻辑跟直接支付一样)
    //$merchant_id是商户用户id, $to_user用户id ,$is_merchant 是否代扣（指给募集期利息代扣调用）
    public function repayFreeze($merchant_id, $out_order_no, $money, $profit, $title, $desr, $to_user, $obj_type, $obj_id, $desc='', $is_merchant=false, $api_id=self::API_ID){
        $key = "repay_{$out_order_no}_{$api_id}_" . date("Ymdhi");
        if(cache($key)) {
            throw_exception("该订单已经处理过了");
        }

        $has = $this->where(array('out_order_no'=>$out_order_no, 'api_id'=>$api_id))->find();
        if($has) throw_exception("该订单已经处理过了");
        $merchant_id = (int)$merchant_id;
        $merchant = D('Payment/PayAccount')->find($merchant_id);
        if(!$merchant) throw_exception("支付的商户账户不存在");

        $to_user = (int)$to_user;
        $account = D('Payment/PayAccount')->find($to_user);
        if(!$account) throw_exception("收款的账户不存在");
        if($is_merchant && !$account['is_merchant']) throw_exception("收款的代扣账户必须是商户账户");
        $this->startTrans();
        try{
            $data['uid'] = $merchant_id;
            $data['order_no'] = $this->createNo();
            $data['api_id'] = $api_id;
            $data['out_order_no'] = $out_order_no;
            $data['repay_money'] = $money;
            $data['profit'] = $profit;
            $data['title'] = $title;
            $data['desr'] = $desr;
            $data['to_user'] = $to_user;
            $data['obj_type'] = $obj_type;
            $data['obj_id'] = $obj_id;
            if($is_merchant) $data['merchant_id'] = $to_user;
            $data['status'] = self::STATUS_REPAID;
            $data['ctime'] = time();
            $data['mtime'] = time();
            $data['id'] = $orderId = $this->add($data);
            if(!$orderId){
                throw_exception("系统数据异常ORDDP2");
            }
            D('Payment/PayAccount')->repayFreeze($merchant_id, $money, $profit, $to_user, $obj_type, $obj_id, $orderId, $desc, $is_merchant);
            $this->commit();
            cache($key, $orderId, array('expire'=>60));
            return $data;
        }catch (Exception $e){
            $this->rollback();
            throw $e;
        }
    }

	//项目还款(处理还款来源不同，逻辑跟直接支付一样)
	//$merchant_id是商户用户id, $to_user用户id ,$is_merchant 是否代扣（指给募集期利息代扣调用）
	public function repay($merchant_id, $out_order_no, $money, $profit, $title, $desr, $to_user, $obj_type, $obj_id, $is_merchant=false, $api_id=self::API_ID){
		$key = "repay_{$out_order_no}_{$api_id}_" . date("Ymdhi");
		if(cache($key)) {
			throw_exception("该订单已经处理过了");
		}
		
		$has = $this->where(array('out_order_no'=>$out_order_no, 'api_id'=>$api_id))->find();
		if($has) throw_exception("该订单已经处理过了");
		$merchant_id = (int)$merchant_id;
		$merchant = D('Payment/PayAccount')->find($merchant_id);
		if(!$merchant) throw_exception("支付的商户账户不存在");
		
		$to_user = (int)$to_user;
		$account = D('Payment/PayAccount')->find($to_user);
		if(!$account) throw_exception("收款的账户不存在");
		if($is_merchant && !$account['is_merchant']) throw_exception("收款的代扣账户必须是商户账户");
		$this->startTrans();
		try{
			$data['uid'] = $merchant_id;
			$data['order_no'] = $this->createNo();
			$data['api_id'] = $api_id;
			$data['out_order_no'] = $out_order_no;
			$data['repay_money'] = $money;
			$data['profit'] = $profit;
			$data['title'] = $title;
			$data['desr'] = $desr;
			$data['to_user'] = $to_user;
			$data['obj_type'] = $obj_type;
			$data['obj_id'] = $obj_id;
			if($is_merchant) $data['merchant_id'] = $to_user;
			$data['status'] = self::STATUS_REPAID;
			$data['ctime'] = time();
			$data['mtime'] = time();
			$data['id'] = $orderId = $this->add($data);
			if(!$orderId){
				throw_exception("系统数据异常ORDDP2");
			}
			D('Payment/PayAccount')->repay($merchant_id, $money, $profit, $to_user, $obj_type, $obj_id, $orderId, $is_merchant);
			$this->commit();
			cache($key, $orderId, array('expire'=>60));
			return $data;
		}catch (Exception $e){
			$this->rollback();
			throw $e;
		}
	}

    //使用摇一摇红包 $cashout_type只支持1 和 2
    public function userBonus($uid, $out_order_no, $cashout_type, $money, $title, $desr, $from_user, $obj_type, $obj_id, $api_id=self::API_ID){
        $key = "userBonus_{$out_order_no}_{$api_id}_" . date("Ymdhi");
        if(cache($key)) {
            throw_exception("该订单已经处理过了");
        }
        $has = $this->where(array('out_order_no'=>$out_order_no, 'api_id'=>$api_id))->find();
        if($has) throw_exception("该订单已经处理过了");
        $uid = (int)$uid;
        $from_user = (int)$from_user;
        $account = D('Payment/PayAccount')->find($uid);
        if(!$account) throw_exception("奖励账户不存在");
        $this->startTrans();
        try{
            $data['uid'] = (int)$uid;
            $data['order_no'] = $this->createNo();
            $data['api_id'] = $api_id;
            $data['out_order_no'] = $out_order_no;
            $data['money'] = $money;
            $data['title'] = $title;
            $data['desr'] = $desr;
            $data['from_user'] = $from_user;
            $data['obj_type'] = $obj_type;
            $data['obj_id'] = $obj_id;
            $data['status'] = self::STATUS_BONUS_USER;
            $data['canel_status'] = 1;
            $data['ctime'] = time();
            $data['mtime'] = time();
            $data['id'] = $orderId = $this->add($data);
            if(!$orderId){
                throw_exception("系统数据异常ORDDP3");
            }

            if($cashout_type == 1){
                D('Payment/PayAccount')->rewardCashout($uid, $money, $from_user, $obj_type, $obj_id, $orderId, 0);
            } else if($cashout_type == 2){
                D('Payment/PayAccount')->rewardInvest($uid, $money, $from_user, $obj_type, $obj_id, $orderId, 0);
            }
            $this->commit();

            //异步核销红包
            queue('payOrder_cannelBonus', $orderId, array('orderId'=>$orderId));

            cache($key, $orderId, array('expire'=>60));
            return $data;
        }catch (Exception $e){
            $this->rollback();
            throw $e;
        }
    }

    public function cannelBonus($pay_order_id){
        $data = $this->find($pay_order_id);
        if(!$data) throw_exception("数据不存在");
        if($data['canel_status'] != 1) throw_exception("该数据不是待核销状态");
        $this->startTrans();
        try{
            D('Payment/PayAccount')->outcoming($data['from_user'], $data['money'], $data['uid']
                , PayAccountModel::OBJ_TYPE_CANEL_BONUS, $data['obj_id'], $data['id']);

            $adata['id'] = (int)$data['id'];
//            $adata['status'] = self::STATUS_BONUS_CANEL;
            $adata['canel_status'] = 2;
            $adata['mtime'] = time();
            $res = $this->save($adata);
            if(!$res) throw_exception("保存失败");
            $this->commit();
        }catch (Exception $e){
            $this->rollback();
            throw $e;
        }
        return true;
    }
	
	//使用券 $cashout_type只支持1 和 2
	public function useConpon($uid, $out_order_no, $cashout_type, $money, $title, $desr, $to_user, $obj_type, $obj_id, $api_id=self::API_ID){
		$key = "useConpon_{$out_order_no}_{$api_id}_" . date("Ymdhi");
		if(cache($key)) {
			throw_exception("该订单已经处理过了");
		}
		$has = $this->where(array('out_order_no'=>$out_order_no, 'api_id'=>$api_id))->find();
		if($has) throw_exception("该订单已经处理过了");
		$uid = (int)$uid;
		$to_user = (int)$to_user;
		$account = D('Payment/PayAccount')->find($to_user);
		if(!$account) throw_exception("支付的商户账户不存在");
		$this->startTrans();
		try{
			$data['uid'] = (int)$uid;
			$data['order_no'] = $this->createNo();
			$data['api_id'] = $api_id;
			$data['out_order_no'] = $out_order_no;
			$data['money'] = $money;
			$data['title'] = $title;
			$data['desr'] = $desr;
			$data['to_user'] = $to_user;
			$data['obj_type'] = $obj_type;
			$data['obj_id'] = $obj_id;
			$data['status'] = self::STATUS_COUPON_USE;
			$data['ctime'] = time();
			$data['mtime'] = time();
			$data['id'] = $orderId = $this->add($data);
			if(!$orderId){
				throw_exception("系统数据异常ORDDP3");
			}
		
			D('Payment/PayAccount')->outcoming($uid, $money, $to_user, $obj_type, $obj_id, $orderId);
			
			if($cashout_type == 1){
				D('Payment/PayAccount')->rewardCashout($to_user, $money, $uid, $obj_type, $obj_id, $orderId, $money);
			} else if($cashout_type == 2){
				D('Payment/PayAccount')->rewardInvest($to_user, $money, $uid, $obj_type, $obj_id, $orderId, $money);
			}
			$this->commit();
			cache($key, $orderId, array('expire'=>60));
			return $data;
		}catch (Exception $e){
			$this->rollback();
			throw $e;
		}
	}
	
	//从某个运营账户奖励可以提现金额给用户 $obj_type=101
	public function rewardCashout($uid, $out_order_no, $money, $title, $desr, $to_user, $obj_type, $obj_id, $adesc='', $api_id=self::API_ID){
		$key = "reward_{$out_order_no}_{$api_id}_" . date("Ymdhi");
		if(cache($key)) {
            $key_arr = explode("_",$key);
            if($key_arr){
                $uid = $key_arr[1];
                $reward_rule_log_id = $key_arr[2];
            }
			throw_exception("该订单已经处理过了,请稍后重试!uid:".$uid.",reward_rule_log_id:".$reward_rule_log_id);
		}
		
		$has = $this->where(array('out_order_no'=>$out_order_no, 'api_id'=>$api_id))->find();
		if($has) throw_exception("该订单已经处理过了");
		$uid = (int)$uid;
		$to_user = (int)$to_user;
		$account = D('Payment/PayAccount')->find($to_user);
		if(!$account) throw_exception("支付的商户账户不存在");
		$this->startTrans();
		try{
			$data['uid'] = (int)$uid;
			$data['order_no'] = $this->createNo();
			$data['api_id'] = $api_id;
			$data['out_order_no'] = $out_order_no;
			$data['money'] = $money;
			$data['title'] = $title;
			$data['desr'] = $desr;
			$data['to_user'] = $to_user;
			$data['obj_type'] = $obj_type;
			$data['obj_id'] = $obj_id;
			$data['status'] = self::STATUS_REWARD_CASHOUT;
			$data['ctime'] = time();
			$data['mtime'] = time();
			$data['id'] = $orderId = $this->add($data);
			if(!$orderId){
				throw_exception("系统数据异常ORDDP3");
			}
				
			D('Payment/PayAccount')->setAdesc($this->yunying_account_adesc)->outcoming($uid, $money, $to_user, $obj_type, $obj_id, $orderId);
			D('Payment/PayAccount')->rewardCashout($to_user, $money, $uid, $obj_type, $obj_id, $orderId, 0, $adesc,$title);
			$this->commit();
			cache($key, $orderId, array('expire'=>60));
			return $data;
		}catch (Exception $e){
			$this->rollback();
			throw $e;
		}
	}
	
	//给用户可以提现奖励金额过期 $obj_type=1013
	public function rewardCBack($uid, $out_order_no, $money, $title, $desr, $to_user, $obj_type, $obj_id, $api_id=self::API_ID){
		$key = "rewardBc_{$out_order_no}_{$api_id}_" . date("Ymdhi");
		if(cache($key)) {
			throw_exception("该订单已经处理过了");
		}
	
		$has = $this->where(array('out_order_no'=>$out_order_no, 'api_id'=>$api_id))->find();
		if($has) throw_exception("该订单已经处理过了");
		$uid = (int)$uid;
		$to_user = (int)$to_user;
		$account = D('Payment/PayAccount')->find($to_user);
		if(!$account) throw_exception("支付的商户账户不存在");
		$this->startTrans();
		try{
			$data['uid'] = (int)$uid;
			$data['order_no'] = $this->createNo();
			$data['api_id'] = $api_id;
			$data['out_order_no'] = $out_order_no;
			$data['money'] = $money;
			$data['title'] = $title;
			$data['desr'] = $desr;
			$data['to_user'] = $to_user;
			$data['obj_type'] = $obj_type;
			$data['obj_id'] = $obj_id;
			$data['status'] = self::STATUS_REWARD_CBACK;
			$data['ctime'] = time();
			$data['mtime'] = time();
			$data['id'] = $orderId = $this->add($data);
			if(!$orderId){
				throw_exception("系统数据异常ORDDP3");
			}
	
			D('Payment/PayAccount')->incoming($uid, $money, $to_user, $obj_type, $obj_id, $orderId);
			D('Payment/PayAccount')->rewardCBack($to_user, $money, $uid, $obj_type, $obj_id, $orderId);
			$this->commit();
			cache($key, $orderId, array('expire'=>60));
			return $data;
		}catch (Exception $e){
			$this->rollback();
			throw $e;
		}
	}
	
	//从某个运营账户奖励投资后可以提现金额给用户 $obj_type=102
	public function rewardInvest($uid, $out_order_no, $money, $title, $desr, $to_user, $obj_type, $obj_id, $adesc='', $api_id=self::API_ID){

		$key = "reward_{$out_order_no}_{$api_id}_" . date("Ymdhi");
		if(cache($key)) {
			throw_exception("该订单已经处理过了");
		}
		
		$has = $this->where(array('out_order_no'=>$out_order_no, 'api_id'=>$api_id))->find();
		if($has) throw_exception("该订单已经处理过了");
		$uid = (int)$uid;
		$to_user = (int)$to_user;
		$account = D('Payment/PayAccount')->find($to_user);
		if(!$account) throw_exception("支付的商户账户不存在");
		$this->startTrans();
		try{
			$data['uid'] = (int)$uid;
			$data['order_no'] = $this->createNo();
			$data['api_id'] = $api_id;
			$data['out_order_no'] = $out_order_no;
			$data['money'] = $money;
			$data['title'] = $title;
			$data['desr'] = $desr;
			$data['to_user'] = $to_user;
			$data['obj_type'] = $obj_type;
			$data['obj_id'] = $obj_id;
			$data['status'] = self::STATUS_REWARD_INVEST;
			$data['ctime'] = time();
			$data['mtime'] = time();
			$data['id'] = $orderId = $this->add($data);
			if(!$orderId){
				throw_exception("系统数据异常ORDDP4");
			}
		
			D('Payment/PayAccount')->setAdesc($this->yunying_account_adesc)->outcoming($uid, $money, $to_user, $obj_type, $obj_id, $orderId);
			D('Payment/PayAccount')->rewardInvest($to_user, $money, $uid, $obj_type, $obj_id, $orderId, 0, $adesc);
			$this->commit();
			cache($key, $orderId, array('expire'=>60));
			return $data;
		}catch (Exception $e){
			$this->rollback();
			throw $e;
		}
	}
	
	//给用户投资后可以提现奖励金额过期 $obj_type=1023
	public function rewardIBack($uid, $out_order_no, $money, $title, $desr, $to_user, $obj_type, $obj_id, $api_id=self::API_ID){
		$key = "rewardCi_{$out_order_no}_{$api_id}_" . date("Ymdhi");
		if(cache($key)) {
			throw_exception("该订单已经处理过了");
		}
	
		$has = $this->where(array('out_order_no'=>$out_order_no, 'api_id'=>$api_id))->find();
		if($has) throw_exception("该订单已经处理过了");
		$uid = (int)$uid;
		$to_user = (int)$to_user;
		$account = D('Payment/PayAccount')->find($to_user);
		if(!$account) throw_exception("支付的商户账户不存在");
		$this->startTrans();
		try{
			$data['uid'] = (int)$uid;
			$data['order_no'] = $this->createNo();
			$data['api_id'] = $api_id;
			$data['out_order_no'] = $out_order_no;
			$data['money'] = $money;
			$data['title'] = $title;
			$data['desr'] = $desr;
			$data['to_user'] = $to_user;
			$data['obj_type'] = $obj_type;
			$data['obj_id'] = $obj_id;
			$data['status'] = self::STATUS_REWARD_IBACK;
			$data['ctime'] = time();
			$data['mtime'] = time();
			$data['id'] = $orderId = $this->add($data);
			if(!$orderId){
				throw_exception("系统数据异常ORDDP4");
			}
	
			D('Payment/PayAccount')->incoming($uid, $money, $to_user, $obj_type, $obj_id, $orderId);
			D('Payment/PayAccount')->rewardIBack($to_user, $money, $uid, $obj_type, $obj_id, $orderId);
			$this->commit();
			cache($key, $orderId, array('expire'=>60));
			return $data;
		}catch (Exception $e){
			$this->rollback();
			throw $e;
		}
	}
	
	//划拨到商户号 还款充值余额里 $obj_type=14 PayAccountModel::OBJ_TYPE_ALLOT
	public function allotPay($uid, $out_order_no, $money, $fee, $title, $desr, $to_user, $obj_type, $obj_id, $api_id=self::API_ID){
		$key = "allotPay_{$out_order_no}_{$api_id}_" . date("Ymdhi");
		if(cache($key)) {
			throw_exception("该订单已经处理过了");
		}
	
		$has = $this->where(array('out_order_no'=>$out_order_no, 'api_id'=>$api_id))->find();
		if($has) throw_exception("该订单已经处理过了");
		$uid = (int)$uid;
		$to_user = (int)$to_user;
		$account = D('Payment/PayAccount')->find($to_user);
		if(!$account) throw_exception("支付的商户账户不存在");
//		if(!$account['is_merchant']) throw_exception("支付的账户必须是商户账户");
		$this->startTrans();
		try{
			$data['uid'] = (int)$uid;
			$data['order_no'] = $this->createNo();
			$data['api_id'] = $api_id;
			$data['out_order_no'] = $out_order_no;
			$data['money'] = $money;
			$data['fee'] = $fee;
			$data['title'] = $title;
			$data['desr'] = $desr;
			$data['to_user'] = $to_user;
			$data['merchant_id'] = $to_user;
			$data['obj_type'] = $obj_type;
			$data['obj_id'] = $obj_id;
			$data['status'] = self::STATUS_ALLOT;
			$data['ctime'] = time();
			$data['mtime'] = time();
			$data['id'] = $orderId = $this->add($data);
			if(!$orderId){
				throw_exception("系统数据异常ORDDP5");
			}
				
			D('Payment/PayAccount')->outcoming($uid, $money, $to_user, $obj_type, $obj_id, $orderId);
			D('Payment/PayAccount')->allotIncoming($to_user, $money - $fee, $uid, $obj_type, $obj_id, $orderId);
			if($fee){
				D('Payment/PayAccount')->incoming(PLAT_ACCOUNTID, $fee, $uid, PayAccountModel::OBJ_TYPE_SHOUXU, $orderId, '');
			}
			$this->commit();
			cache($key, $orderId, array('expire'=>60));
			return $data;
		}catch (Exception $e){
			$this->rollback();
			throw $e;
		}
	}

    //只有商户账户可用，划款到自己的还款账户
    public function cutInRepayAccount($uid, $out_order_no, $money, $has_cache, $desc='', $api_id=self::API_ID){
        //	echo $uid."|".$out_order_no."|".$money."|".$title."|".$desr."|".$api_id;
        $key = "rollout_{$out_order_no}_{$api_id}_" . date("Ymdhi");
        if(cache($key)) {
            throw_exception("该订单已经处理过了");
        }

        $has = $this->where(array('out_order_no'=>$out_order_no, 'api_id'=>$api_id))->find();
        if($has) throw_exception("该订单已经处理过了");
        $uid = (int)$uid;
        $user = M('user')->find($uid);
        $account = D('Payment/PayAccount')->find($uid);
        if(!$account) throw_exception("支付的商户账户不存在");
//		if(!$account['is_merchant']) throw_exception("支付的账户必须是商户账户");
        $this->startTrans();
        try{
            $data['uid'] = (int)$uid;
            $data['order_no'] = $this->createNo();
            $data['api_id'] = $api_id;
            $data['out_order_no'] = $out_order_no;
            $data['money'] = $money;
            $data['fee'] = 0;
            $data['title'] = $user["real_name"]."转出余额还款冻结账户".($money/100)."元";
            $data['desr'] = $user["real_name"]."转出余额还款冻结账户".($money/100)."元";
            $data['to_user'] = $uid;
            $data['merchant_id'] = $uid;
            $data['obj_type'] = PayAccountModel::OBJ_TYPE_CUTINREPAY;
            $data['obj_id'] = $uid;
            $data['status'] = self::STATUS_CUTINREPAY;
            $data['ctime'] = time();
            $data['mtime'] = time();
            $data['id'] = $orderId = $this->add($data);
            if(!$orderId){
                throw_exception("系统数据异常ORDDP6");
            }
            D('Payment/PayAccount')->cutInRepayAccount($uid, $money, $data['obj_type'], $data['obj_id'], $orderId, $has_cache, $desc, $desc);
            $this->commit();
            cache($key, $orderId, array('expire'=>60));
            return $data;
        }catch (Exception $e){
            $this->rollback();
            throw $e;
        }
    }
	
	//只有商户账户可用，转出支付余额资金 $obj_type=15 PayAccountModel::OBJ_TYPE_ALLOT
	public function rollout($uid, $out_order_no, $money, $has_cache, $desc='', $api_id=self::API_ID){
	//	echo $uid."|".$out_order_no."|".$money."|".$title."|".$desr."|".$api_id;
		$key = "rollout_{$out_order_no}_{$api_id}_" . date("Ymdhi");
		if(cache($key)) {
			throw_exception($out_order_no."该订单已经处理过了");
		}
	
		$has = $this->where(array('out_order_no'=>$out_order_no, 'api_id'=>$api_id))->find();
		if($has) throw_exception($out_order_no."该订单已经处理过了");
		$uid = (int)$uid;
		$user = M('user')->find($uid);
		$account = D('Payment/PayAccount')->find($uid);
		if(!$account) throw_exception("支付的商户账户不存在");
//		if(!$account['is_merchant']) throw_exception("支付的账户必须是商户账户");
		$this->startTrans();
		try{
			$data['uid'] = (int)$uid;
			$data['order_no'] = $this->createNo();
			$data['api_id'] = $api_id;
			$data['out_order_no'] = $out_order_no;
			$data['money'] = $money;
			$data['fee'] = 0;
			$data['title'] = $user["real_name"]."转出支付余额".($money/100)."元";
			$data['desr'] = $user["real_name"]."转出支付余额".($money/100)."元";
			$data['to_user'] = $uid;
			$data['merchant_id'] = $uid;
			$data['obj_type'] = PayAccountModel::OBJ_TYPE_ROLLOUT;
			$data['obj_id'] = $uid;
			$data['status'] = self::STATUS_ALLOT;
			$data['ctime'] = time();
			$data['mtime'] = time();
			$data['id'] = $orderId = $this->add($data);
			if(!$orderId){
				throw_exception("系统数据异常ORDDP6");
			}
			D('Payment/PayAccount')->rollout($uid, $money, $data['obj_type'], $data['obj_id'], $orderId, $has_cache, $desc);
			$this->commit();
			cache($key, $orderId, array('expire'=>60));
			return $data;
		}catch (Exception $e){
			$this->rollback();
			throw $e;
		}
	}


    /**
     * 司马小鑫还款转账用 转账到amountx
     * @param $uid
     * @param $out_order_no
     * @param $money
     * @param $has_cache
     * @param string $desc
     * @param int $api_id
     * @return mixed
     * @throws Exception
     */
    public function rollOutX($uid, $out_order_no, $money, $has_cache, $desc = '', $api_id = self::API_ID)
    {
        $key = "rolloutx_{$out_order_no}_{$api_id}_" . date("Ymdhi");
        if (cache($key)) {
            throw_exception($out_order_no . "该订单已经处理过了rollOutX1");
        }

        $has = $this->where(array('out_order_no' => $out_order_no, 'api_id' => $api_id))->find();
        if ($has) {
            throw_exception($out_order_no . "该订单已经处理过了rollOutX2");
        }
        $uid = (int)$uid;
        $user = M('user')->find($uid);
        $account = D('Payment/PayAccount')->find($uid);
        if (!$account) {
            throw_exception("支付的商户账户不存在");
        }
        $this->startTrans();
        try {
            $data['uid'] = (int)$uid;
            $data['order_no'] = $this->createNo();
            $data['api_id'] = $api_id;
            $data['out_order_no'] = $out_order_no;
            $data['money'] = $money;
            $data['fee'] = 0;
            $data['title'] = $user["real_name"] . "转出司马小鑫余额" . ($money / 100) . "元";
            $data['desr'] = $user["real_name"] . "转出司马小鑫余额" . ($money / 100) . "元";
            $data['to_user'] = $uid;
            $data['merchant_id'] = $uid;
            $data['obj_type'] = PayAccountModel::OBJ_TYPE_ROLLOUTX;
            $data['obj_id'] = $uid;
            $data['status'] = self::STATUS_ALLOT;
            $data['ctime'] = time();
            $data['mtime'] = time();
            $data['id'] = $orderId = $this->add($data);
            if (!$orderId) {
                throw_exception("系统数据异常ORDDP6");
            }
            D('Payment/PayAccount')->rollOutX($uid, $money, $data['obj_type'], $data['obj_id'], $orderId, $has_cache, $desc);
            $this->commit();
            cache($key, $orderId, array('expire' => 60));
            return $data;
        } catch (Exception $e) {
            $this->rollback();
            throw $e;
        }
    }

    /**
     * 托管银行划款到余额
     * @param $uid
     * @param $out_order_no
     * @param $money
     * @param $has_cache
     * @param $desc
     * @param $api_id
     * @return mixed
     * @throws Exception
     */
    public function rollOutZs($uid, $out_order_no, $money, $has_cache, $desc, $api_id)
    {
        $key = "rolloutzs_{$out_order_no}_{$api_id}_" . date("Ymdhi");
        if (cache($key)) {
            throw_exception($out_order_no . "该订单已经处理过了rollOutZs1");
        }

        $has = $this->where(array('out_order_no' => $out_order_no, 'api_id' => $api_id))->find();
        if ($has) {
            throw_exception($out_order_no . "该订单已经处理过了rollOutZs2");
        }
        $uid = (int)$uid;
        $user = M('user')->find($uid);
        $account = D('Payment/PayAccount')->find($uid);
        if (!$account) {
            throw_exception("支付的商户账户不存在");
        }
        $this->startTrans();
        try {
            $data['uid'] = (int)$uid;
            $data['order_no'] = $this->createNo();
            $data['api_id'] = $api_id;
            $data['out_order_no'] = $out_order_no;
            $data['money'] = $money;
            $data['fee'] = 0;
            $data['title'] = $user["real_name"] . "转出支付余额[托管]" . ($money / 100) . "元";
            $data['desr'] = $user["real_name"] . "转出支付余额[托管]" . ($money / 100) . "元";
            $data['to_user'] = $uid;
            $data['merchant_id'] = $uid;
            $data['obj_type'] = PayAccountModel::OBJ_TYPE_ZS_REPAYING;
            $data['obj_id'] = $uid;
            $data['status'] = self::STATUS_ALLOT;
            $data['ctime'] = time();
            $data['mtime'] = time();
            $data['id'] = $orderId = $this->add($data);
            if (!$orderId) {
                throw_exception("系统数据异常ORDDP8");
            }
            D('Payment/PayAccount')->rollOutZs($uid, $money, $data['obj_type'], $data['obj_id'], $orderId, $has_cache, $desc);
            $this->commit();
            cache($key, $orderId, array('expire' => 60));
            return $data;
        } catch (Exception $e) {
            $this->rollback();
            throw $e;
        }
    }

	/**
	 * 托管银行划款到在途资金
	 * @throws Exception
	 */
	public function transferOutZs($uid, $out_order_no, $money, $reward_money, $fee, $title, $desr, $to_user, $obj_type, $obj_id, $api_id=self::API_ID, $move_type = 1, $move_id = 0){
		$key = "transferOutZs_{$out_order_no}_{$api_id}_" . date("Ymdhi");
		if(cache($key)) {
			throw_exception("该订单已经处理过了");
		}
		$total_moving_money = $money + $reward_money;
		$has = $this->where(array('out_order_no'=>$out_order_no, 'api_id'=>$api_id))->find();
		if($has) throw_exception("该订单已经处理过了");
		$uid = (int)$uid;
		$to_user = (int)$to_user;
		$account = D('Payment/PayAccount')->find($to_user);
		if(!$account) throw_exception("支付的商户账户不存在");
		$this->startTrans();
		try{
			$data['uid'] = (int)$uid;
			$data['order_no'] = $this->createNo();
			$data['api_id'] = $api_id;
			$data['out_order_no'] = $out_order_no;
			$data['money'] = $money;
			$data['reward_money'] = $reward_money;
			$data['fee'] = $fee;
			$data['title'] = $title;
			$data['desr'] = $desr;
			$data['to_user'] = $to_user;
			$data['merchant_id'] = $to_user;
			$data['obj_type'] = $obj_type;
			$data['obj_id'] = $obj_id;
			$data['status'] = PayAccountModel::OBJ_TYPE_ZS_MOVING;
			$data['ctime'] = time();
			$data['mtime'] = time();
			$data['id'] = $orderId = $this->add($data);
			if(!$orderId){
				throw_exception("系统数据异常transferOutZs");
			}

			D('Payment/PayAccount')->outcoming($uid, $total_moving_money, $to_user, $obj_type, $obj_id, $orderId);
			$this->commit();
			cache($key, $orderId, array('expire'=>60));
			return $data;
		}catch (Exception $e){
			$this->rollback();
			throw $e;
		}
	}

	/**
	 * 托管银行在途资金转移到浙商
	 * @throws Exception
	 */
	public function transferInZs($uid, $out_order_no, $money,$reward_money, $fee, $title, $desr, $to_user, $obj_type, $obj_id, $api_id=self::API_ID, $move_type = 1){
		$key = "transferOutZs_{$out_order_no}_{$api_id}_" . date("Ymdhi");
		if(cache($key)) {
			throw_exception("该订单已经处理过了");
		}
		$uid = (int)$uid;
		$account = D('Payment/PayAccount')->find($to_user);
		if(!$account) throw_exception("支付的商户账户不存在");
		$this->startTrans();
		try{
			D('Payment/PayAccount')->transfer(
				array(
					'to_user'=>$uid,
					'money' => $money,
					'profit' => $reward_money,
					'from_user'=>$to_user,
					'obj_type'=>$obj_type,
					'obj_id'=>$obj_id,
					'pay_order_id'=>$out_order_no,
					'out_order_no'=>$out_order_no,
					'desr'=>'',
					'remark'=>'',
				)
			);
			$this->commit();
			cache($key, '1', array('expire'=>60));
			return true;
		}catch (Exception $e){
			$this->rollback();
			throw $e;
		}
	}
	
    public function transfer(array $params)
    {
        $from_user = K($params,'from_user');
        $to_user = K($params,'to_user');
        $money = (int)abs(K($params,'money'));
        $out_order_no = K($params,'out_order_no');
        $obj_type = K($params,'obj_type');
        $obj_id = K($params,'obj_id');
        $status = K($params,'status');

        $api_id = K($params,'api_id',self::API_ID);
        $repay_money = K($params,'repay_money',0);
        $profit = K($params,'profit',0);
        $reward_money = K($params,'reward_money',0);
        $invest_reward_money = K($params,'invest_reward_money',0);
        $fee = K($params,'fee',0);
        $free_money = K($params,'free_money',0);
        $freeze_free_money = K($params,'freeze_free_money',0);
        $desr = K($params,'desr','');
        $remark = K($params,'remark','');
        $canel_status = K($params,'canel_status',0);
        $merchant_id = K($params,'merchant_id',0);


        $this->hasOutOrderNo($out_order_no, $api_id);
        $this->hasUserAccount($to_user);
        
        $trans_key = 'pay_order_'.$out_order_no;

        try {
            $this->startTrans($trans_key);
            $time = time();
            $data = [
                'api_id'=>$api_id,
                'uid'=>$to_user,
                'order_no'=>$this->createNo(),
                'out_order_no'=>$out_order_no,
                'repay_money'=>$repay_money,
                'profit'=>$profit,
                'money'=>$money,
                'reward_money'=>$reward_money,
                'invest_reward_money'=>$invest_reward_money,
                'fee'=>$fee,
                'free_money'=>$free_money,
                'freeze_free_money'=>$freeze_free_money,
                'title'=>$remark,
                'desr'=>$desr,
                'merchant_id'=>$merchant_id,
                'obj_type'=>$obj_type,
                'obj_id'=>$obj_id,
                'to_user'=>$to_user,
                'from_user'=>$from_user,
                'status'=>$status,
                'canel_status'=>$canel_status,
                'ctime'=>$time,
                'mtime'=>$time,
            ];
            $this->create($data);
            $pay_order_id = $this->add();
            if (!$pay_order_id) {
                throw_exception("pay_order数据保存失败");
            }
            $pay_account_model = new PayAccountModel();
            $params['pay_order_id'] = $pay_order_id;
            $pay_account_model->transfer($params);
            $this->commit($trans_key);
            return $data['order_no'];

        } catch (Exception $exc) {
            $this->rollback($trans_key);
            throw_exception($exc->getMessage());
        }
        return false;
    }

    private function hasOutOrderNo($out_order_no,$api_id)
    {
        $has = $this->where([
            'out_order_no' => $out_order_no,
            'api_id' => $api_id
        ])->find();
        if ($has) {
            throw_exception('订单号:'.$out_order_no . "的订单已经处理过,不能重复处理。");
        }
        return true;
    }

    private function hasUserAccount($uid)
    {
        $account = D('Payment/PayAccount')->find($uid);
        if (!$account) {
            throw_exception('用户ID='.$uid."的账户不存在");
        }
    }

    private function getUserRealName($uid)
    {
        $real_name = M('user')->where(['uid'=>$uid])->getField('real_name');
        if (!$real_name) {
            throw_exception('用户ID='.$uid.'的真实姓名不存在,请检查后重试');
        }
        return $real_name;
    }



	private function createNo(){
        $id_gen_instance = new \Addons\Libs\IdGen\IdGen();
        return $id_gen_instance->get(\Addons\Libs\IdGen\IdGen::WORK_TYPE_PRJ_ORDER);
	}

    /**
     * 设置运营平台的备注
     */
    public function setYunyingAdesc($yunying_adesc = '')
    {
        $this->yunying_account_adesc = $yunying_adesc;
        return $this;
    }
}

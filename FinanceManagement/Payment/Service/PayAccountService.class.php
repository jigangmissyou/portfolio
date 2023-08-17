<?php
//支付账户服务 下面的方法不要放到事物里，因为里面很多都已经有事物了
//$obj_type在PayAccountModel里有常量定义
class PayAccountService extends BaseService{
    protected $yunying_adesc = '';
	public function getFundAccountList($uid){
		return D('Payment/PayAccount')->getFundAccountList($uid);
	}
	public function getFundAccount($uid, $id){
		return D('Payment/PayAccount')->getFundAccount($uid, $id);
	}
	//增/改三方账户信息
	public function saveFundAccount($uid, $account_no, $channel, $acount_name, $bank, $bank_name, $bank_province, $bank_city, $sub_bank_id, $is_default = 0, $sub_bank, $id=0, $is_init=0){
		$special_user = M('user_ext')->where(array('uid'=>$uid))->getField('special_user');
        if($special_user == 1){
            return array('boolen'=>0, 'message'=>'盖标用户不能添加和修改绑定银行卡');
        }else{
            return D('Payment/PayAccount')->saveFundAccount($uid, $account_no, $channel, $acount_name, $bank, $bank_name, $bank_province, $bank_city, $sub_bank_id, $is_default, $sub_bank, $id, $is_init);
        }
	}
	//删除三方账户信息
	public function delFundAccount($uid, $id){
        $special_user = M('user_ext')->where(array('uid'=>$uid))->getField('special_user');
        if($special_user == 1) {
            return array('boolen'=>0, 'message'=>'盖标用户不能解绑银行卡');
        }else{
            return D('Payment/PayAccount')->delFundAccount($uid, $id);
        }
	}
	//激活三方账户信息
	public function activeFundAccount($uid, $id){
		return D('Payment/PayAccount')->activeFundAccount($uid, $id);
	}
	//禁用三方账户信息
	public function unactiveFundAccount($uid, $id){
		return D('Payment/PayAccount')->unactiveFundAccount($uid, $id);
	}
	
	//申请提现 $money实际到账的金额 $fee费用
	public function applyCashout($uid, $money, $fee, $reward_money, $channel, $bank, $sub_bank, $bak, $free_times, $free_money, $cost_saving, $out_account_no, $out_account_id = '', $free_tixian_times = 0, $source = 1, $no_auto = 0, $no_role = true,$zs_code='')
	{//本身有事物了
        if(!is_numeric($money)){
			return array('boolen'=>0, 'message'=>'操作失败，参数异常1');
		}
		if($money <= 0){
			return array('boolen'=>0, 'message'=>'操作失败，金额不能为负数1');
		}
		if(!is_numeric($fee)){
			return array('boolen'=>0, 'message'=>'操作失败，参数异常2');
		}
		if($fee < 0){
			return array('boolen'=>0, 'message'=>'操作失败，金额不能为为负数2');
		}
		if(!is_numeric($reward_money)){
			return array('boolen'=>0, 'message'=>'操作失败，参数异常3');
		}
		if($reward_money < 0){
			return array('boolen'=>0, 'message'=>'操作失败，金额不能为为负数3');
		}
		return D('Payment/PayAccount')->applyCashout($uid, $money, $fee, $reward_money, $channel, $bank, $sub_bank, $bak, $free_times, $free_money, $cost_saving, $out_account_no, $out_account_id, $free_tixian_times, $source, $no_auto, $no_role,$zs_code);
	}
	//提交充值 $channel来自字典项E001 $is_repay_in=1说明是还款充值 $out_account_no 和 $out_account_id只有线下类型用 $paymentParams线下用
	public function submitRecharge($amount, $uid, $channel, $sub_bank, $is_repay_in=1, $out_account_no='', $out_account_id='', $paymentParams=array()){//没有事物
		if(!is_numeric($amount)){
			return array('boolen'=>0, 'message'=>'操作失败，参数异常');
		}
		if($amount <= 0){
			return array('boolen'=>0, 'message'=>'操作失败，金额不能为为负数');
		}
		$Payment = service('Payment/Payment')->init($channel);
		$result = $Payment->submit($amount, $uid, $sub_bank, $is_repay_in, $out_account_no, $out_account_id, $paymentParams);
		return $result;
	}

    //提交充值充值
    public function czRecharge($record){
        D("Payment/PayAccount")->czRecharge($record);
        return true;
    }

	//支付冻结
	public function freezePay($uid, $out_order_no, $money, $reward_money, $invest_reward_money, $fee, $title, $desr, $to_user, $obj_type, $obj_id, $free_money=0, $api_id=''){//没有事物了 需要外面加事物
		if(!is_numeric($money)){
			return array('boolen'=>0, 'message'=>'操作失败，参数异常');
		}
		if($money < 0){
			return array('boolen'=>0, 'message'=>'操作失败，金额不能为负数');
		}
		if(!is_numeric($fee)){
			return array('boolen'=>0, 'message'=>'操作失败，参数异常');
		}
		if($fee < 0){
			return array('boolen'=>0, 'message'=>'操作失败，金额不能为为负数');
		}
		if(!is_numeric($reward_money)){
			return array('boolen'=>0, 'message'=>'操作失败，参数异常');
		}
		if($reward_money < 0){
			return array('boolen'=>0, 'message'=>'操作失败，金额不能为负数');
		}
		if(!is_numeric($invest_reward_money)){
			return array('boolen'=>0, 'message'=>'操作失败，参数异常');
		}
		if($invest_reward_money < 0){
			return array('boolen'=>0, 'message'=>'操作失败，金额不能为负数');
		}
		if($api_id){
			$data = D('Payment/PayOrder')->freezeInit($uid, $out_order_no, $money, $reward_money, $invest_reward_money, $fee, $title, $desr, $to_user, $obj_type, $obj_id, $free_money, $api_id);
		} else {
			$data = D('Payment/PayOrder')->freezeInit($uid, $out_order_no, $money, $reward_money, $invest_reward_money, $fee, $title, $desr, $to_user, $obj_type, $obj_id, $free_money);
		}
		return array('boolen'=>1, 'message'=>'支付成功', 'payorderno'=>$data['order_no']);
	}
	//实际支付
	public function pay($out_order_no, $free_money, $to_user='', $api_id=''){ //需要外面加事物
		if($api_id){
			$data = D('Payment/PayOrder')->pay($out_order_no, $free_money, $to_user, $api_id);
		} else {
			$data = D('Payment/PayOrder')->pay($out_order_no, $free_money, $to_user);
		}
		return array('boolen'=>1, 'message'=>'付款成功', 'payorderno'=>$data['order_no']);
	}
	//对冻结的金额 退款
	public function reback($out_order_no, $api_id=''){//本身有事物了
		if($api_id){
			$data = D('Payment/PayOrder')->reback($out_order_no, $api_id);
		} else {
			$data = D('Payment/PayOrder')->reback($out_order_no);
		}
		return array('boolen'=>1, 'message'=>'退款成功', 'payorderno'=>$data['order_no']);
	}
	
	//直接支付（转账 转让用） 转让购买/给募集期利（从募集账户转让到用户） is_merchant=1
	public function directPay($uid, $out_order_no, $money, $reward_money, $invest_reward_money, $fee, $title, $desr, $to_user, $obj_type, $obj_id, $is_merchant, $free_money=0, $profit=0, $api_id=''){//本身有事物了
		if(!is_numeric($money)){
			return array('boolen'=>0, 'message'=>'操作失败，参数异常');
		}
		if($money < 0){
			return array('boolen'=>0, 'message'=>'操作失败，金额不能为负数');
		}
		if(!is_numeric($reward_money)){
			return array('boolen'=>0, 'message'=>'操作失败，参数异常');
		}
		if($reward_money < 0){
			return array('boolen'=>0, 'message'=>'操作失败，金额不能为负数');
		}
		if(!is_numeric($invest_reward_money)){
			return array('boolen'=>0, 'message'=>'操作失败，参数异常');
		}
		if($invest_reward_money < 0){
			return array('boolen'=>0, 'message'=>'操作失败，金额不能为负数');
		}
		if(!is_numeric($fee)){
			return array('boolen'=>0, 'message'=>'操作失败，参数异常');
		}
		if($fee < 0){
			return array('boolen'=>0, 'message'=>'操作失败，金额不能为为负数');
		}
		if($api_id){
			$data = D('Payment/PayOrder')->directPay($uid, $out_order_no, $money, $reward_money, $invest_reward_money, $fee, $title, $desr, $to_user, $obj_type, $obj_id, $is_merchant, $free_money, $profit, $api_id);
		} else {
			$data = D('Payment/PayOrder')->directPay($uid, $out_order_no, $money, $reward_money, $invest_reward_money, $fee, $title, $desr, $to_user, $obj_type, $obj_id, $is_merchant, $free_money, $profit);
		}
		return array('boolen'=>1, 'message'=>'付款成功', 'payorderno'=>$data['order_no']);
	}

    //从还款冻结账户里扣到对方账户余额
    //$merchant_id是商户用户id, $to_user用户id $money是还款的总额含收益, $is_merchant 是否代扣（指给募集期利息调用）
    public function repayDirectPay($merchant_id, $out_order_no, $money, $title, $desr, $to_user, $obj_type, $obj_id, $desc='',$is_merchant=false, $api_id=''){//本身有事物了
        if(!is_numeric($money)){
            return array('boolen'=>0, 'message'=>'操作失败，参数异常');
        }
        if($money <= 0){
            return array('boolen'=>0, 'message'=>'操作失败，金额不能为为负数');
        }
        if($api_id){
            $data = D('Payment/PayOrder')->repayDirectPay($merchant_id, $out_order_no, $money, $title, $desr, $to_user, $obj_type, $obj_id, $desc, $is_merchant, $api_id);
        } else {
            $data = D('Payment/PayOrder')->repayDirectPay($merchant_id, $out_order_no, $money, $title, $desr, $to_user, $obj_type, $obj_id, $desc, $is_merchant);
        }
        return array('boolen'=>1, 'message'=>'扣款成功', 'payorderno'=>$data['order_no']);
    }

    //项目还款冻结，还款到对应的冻结还款账户里(处理还款来源不同，逻辑跟直接支付一样)
    //$merchant_id是商户用户id, $to_user用户id $money是还款的总额含收益, $is_merchant 是否代扣（指给募集期利息调用）
    public function repayFreeze($merchant_id, $out_order_no, $money, $profit, $title, $desr, $to_user, $obj_type, $obj_id, $desc='',$is_merchant=false, $api_id=''){//本身有事物了
        if(!is_numeric($money)){
            return array('boolen'=>0, 'message'=>'操作失败，参数异常');
        }
        if($money <= 0){
            return array('boolen'=>0, 'message'=>'操作失败，金额不能为为负数');
        }
        if($api_id){
            $data = D('Payment/PayOrder')->repayFreeze($merchant_id, $out_order_no, $money, $profit, $title, $desr, $to_user, $obj_type, $obj_id, $desc, $is_merchant, $api_id);
        } else {
            $data = D('Payment/PayOrder')->repayFreeze($merchant_id, $out_order_no, $money, $profit, $title, $desr, $to_user, $obj_type, $obj_id, $desc, $is_merchant);
        }
        return array('boolen'=>1, 'message'=>'还款成功', 'payorderno'=>$data['order_no']);
    }
	
	//项目还款(处理还款来源不同，逻辑跟直接支付一样)
	//$merchant_id是商户用户id, $to_user用户id $money是还款的总额含收益, $is_merchant 是否代扣（指给募集期利息调用）
	public function repay($merchant_id, $out_order_no, $money, $profit, $title, $desr, $to_user, $obj_type, $obj_id, $is_merchant=false, $api_id=''){//本身有事物了
		if(!is_numeric($money)){
			return array('boolen'=>0, 'message'=>'操作失败，参数异常');
		}
		if($money <= 0){
			return array('boolen'=>0, 'message'=>'操作失败，金额不能为为负数');
		}
		if($api_id){
			$data = D('Payment/PayOrder')->repay($merchant_id, $out_order_no, $money, $profit, $title, $desr, $to_user, $obj_type, $obj_id, $is_merchant, $api_id);
		} else {
			$data = D('Payment/PayOrder')->repay($merchant_id, $out_order_no, $money, $profit, $title, $desr, $to_user, $obj_type, $obj_id, $is_merchant);
		}
		return array('boolen'=>1, 'message'=>'还款成功', 'payorderno'=>$data['order_no']);
	}
	
	//从某个运营账户奖励可以提现金额给用户 $obj_type=101
	public function rewardCashout($uid, $out_order_no, $money, $title, $desr, $to_user, $obj_type, $obj_id, $adesc='', $api_id=''){
		if(!is_numeric($money)){
			return array('boolen'=>0, 'message'=>'操作失败，参数异常');
		}
		if($money <= 0){
			return array('boolen'=>0, 'message'=>'操作失败，金额不能为为负数');
		}
		if($api_id){
			$data = D('Payment/PayOrder')->setYunyingAdesc($this->yunying_adesc)->rewardCashout($uid, $out_order_no, $money, $title, $desr, $to_user, $obj_type, $obj_id, $adesc, $api_id);
		} else {
			$data = D('Payment/PayOrder')->setYunyingAdesc($this->yunying_adesc)->rewardCashout($uid, $out_order_no, $money, $title, $desr, $to_user, $obj_type, $obj_id, $adesc);
		}
		return array('boolen'=>1, 'message'=>'奖励付款成功', 'payorderno'=>$data['order_no']);
	}
	
	//给用户可以提现奖励金额过期 $obj_type=1013
	public function rewardCBack($uid, $out_order_no, $money, $title, $desr, $to_user, $obj_type, $obj_id, $api_id=''){
		if(!is_numeric($money)){
			return array('boolen'=>0, 'message'=>'操作失败，参数异常');
		}
		if($money <= 0){
			return array('boolen'=>0, 'message'=>'操作失败，金额不能为为负数');
		}
		if($api_id){
			$data = D('Payment/PayOrder')->rewardCBack($uid, $out_order_no, $money, $title, $desr, $to_user, $obj_type, $obj_id, $api_id);
		} else {
			$data = D('Payment/PayOrder')->rewardCBack($uid, $out_order_no, $money, $title, $desr, $to_user, $obj_type, $obj_id);
		}
		return array('boolen'=>1, 'message'=>'奖励过期成功', 'payorderno'=>$data['order_no']);
	}
	
	//从某个运营账户奖励投资后可以提现金额给用户 $obj_type=102
	public function rewardInvest($uid, $out_order_no, $money, $title, $desr, $to_user, $obj_type, $obj_id, $adesc='', $api_id=''){
		if(!is_numeric($money)){
			return array('boolen'=>0, 'message'=>'操作失败，参数异常');
		}
		if($money <= 0){
			return array('boolen'=>0, 'message'=>'操作失败，金额不能为为负数');
		}
		if($api_id){
			$data = D('Payment/PayOrder')->rewardInvest($uid, $out_order_no, $money, $title, $desr, $to_user, $obj_type, $obj_id, $adesc, $api_id);
		} else {
			$data = D('Payment/PayOrder')->rewardInvest($uid, $out_order_no, $money, $title, $desr, $to_user, $obj_type, $obj_id, $adesc);
		}
		return array('boolen'=>1, 'message'=>'奖励付款成功', 'payorderno'=>$data['order_no']);
	}
	
	//给用户投资后可以提现奖励金额过期 $obj_type=1023
	public function rewardIBack($uid, $out_order_no, $money, $title, $desr, $to_user, $obj_type, $obj_id, $api_id=''){
		if(!is_numeric($money)){
			return array('boolen'=>0, 'message'=>'操作失败，参数异常');
		}
		if($money <= 0){
			return array('boolen'=>0, 'message'=>'操作失败，金额不能为为负数');
		}
		if($api_id){
			$data = D('Payment/PayOrder')->rewardIBack($uid, $out_order_no, $money, $title, $desr, $to_user, $obj_type, $obj_id, $api_id);
		} else {
			$data = D('Payment/PayOrder')->rewardIBack($uid, $out_order_no, $money, $title, $desr, $to_user, $obj_type, $obj_id);
		}
		return array('boolen'=>1, 'message'=>'奖励过期成功', 'payorderno'=>$data['order_no']);
	}
	
	//从某个运营账户划拨资金到商户账户里(目前只有脚临时调用)
	public function allotPay($uid, $out_order_no, $money, $fee, $title, $desr, $to_user, $obj_type, $obj_id, $api_id=''){
		if(!is_numeric($money)){
			return array('boolen'=>0, 'message'=>'操作失败，参数异常');
		}
		if($money <= 0){
			return array('boolen'=>0, 'message'=>'操作失败，金额不能为为负数');
		}
		if(!is_numeric($fee)){
			return array('boolen'=>0, 'message'=>'操作失败，参数异常');
		}
		if($fee < 0){
			return array('boolen'=>0, 'message'=>'操作失败，金额不能为为负数');
		}
		if($api_id){
			$data = D('Payment/PayOrder')->allotPay($uid, $out_order_no, $money, $fee, $title, $desr, $to_user, $obj_type, $obj_id, $api_id);
		} else {
			$data = D('Payment/PayOrder')->allotPay($uid, $out_order_no, $money, $fee, $title, $desr, $to_user, $obj_type, $obj_id);
		}
		return array('boolen'=>1, 'message'=>'奖励付款成功', 'payorderno'=>$data['order_no']);
	}

    //只有商户账户可用，划款到自己的还款账户
    public function cutInRepayAccount($uid, $out_order_no, $money, $has_cache=true, $desc='', $api_id=''){
        if(!is_numeric($money)){
            return array('boolen'=>0, 'message'=>'操作失败，参数异常');
        }
        if($money <= 0){
            return array('boolen'=>0, 'message'=>'操作失败，金额不能为为负数');
        }
        if($api_id){
            $data = D('Payment/PayOrder')->cutInRepayAccount($uid, $out_order_no, $money, $has_cache, $desc, $api_id);
        } else {
            $data = D('Payment/PayOrder')->cutInRepayAccount($uid, $out_order_no, $money, $has_cache, $desc);
        }
        return array('boolen'=>1, 'message'=>'划款到自己的还款账户', 'payorderno'=>$data['order_no']);
    }
	
	//只有商户账户可用，转出支付余额资金
	public function rollout($uid, $out_order_no, $money, $has_cache=true, $desc='', $api_id=''){
		if(!is_numeric($money)){
			return array('boolen'=>0, 'message'=>'操作失败，参数异常');
		}
		if($money <= 0){
			return array('boolen'=>0, 'message'=>'操作失败，金额不能为为负数');
		}
		if($api_id){
			$data = D('Payment/PayOrder')->rollout($uid, $out_order_no, $money, $has_cache, $desc, $api_id);
		} else {
			$data = D('Payment/PayOrder')->rollout($uid, $out_order_no, $money, $has_cache, $desc);
		}
		return array('boolen'=>1, 'message'=>'成功转出到余额', 'payorderno'=>$data['order_no']);
	}

    //司马小鑫还款转账
    public function rollOutX($uid, $out_order_no, $money, $has_cache = true, $desc = '', $api_id = '')
    {
        if (!is_numeric($money)) {
            return array('boolen' => 0, 'message' => '操作失败，参数异常');
        }
        if ($money <= 0) {
            return array('boolen' => 0, 'message' => '操作失败，金额不能为为负数');
        }
        $pay_order_model = D('Payment/PayOrder');
        if (!$api_id) {
            $api_id = PayOrderModel::API_ID;
        }
        $data = $pay_order_model->rollOutX($uid, $out_order_no, $money, $has_cache, $desc, $api_id);
        return array('boolen' => 1, 'message' => '成功转出到余额', 'payorderno' => $data['order_no']);
    }

    //托管银行划账
    public function rollOutZs($uid, $out_order_no, $money, $has_cache = true, $desc = '', $api_id = '')
    {
        if (!is_numeric($money)) {
            return array('boolen' => 0, 'message' => '操作失败，参数异常');
        }
        if ($money <= 0) {
            return array('boolen' => 0, 'message' => '操作失败，金额不能为为负数');
        }

        /* @var $pay_order_model PayOrderModel*/
        $pay_order_model = D('Payment/PayOrder');
        if (!$api_id) {
            $api_id = PayOrderModel::API_ID;
        }
        $data = $pay_order_model->rollOutZs($uid, $out_order_no, $money, $has_cache, $desc, $api_id);
        return array('boolen' => 1, 'message' => '成功转出到余额', 'payorderno' => $data['order_no']);
    }

    /**
     * 浙商还款在途金融滑到余额账户
     * @param array $params
     * @return array
     */
    public function transferMoneyFromZsRepayingToAmount(array $params)
    {
        return $this->transfer($params);
    }

    /**
     * 账号转账
     * @param array $params
     * 必填
    $from_user = K($params,'from_user');
    $to_user = K($params,'to_user');
    $money = K($params,'money');
    $out_order_no = K($params,'out_order_no');
    $obj_type = K($params,'obj_type');
    $merchant_id = K($params,'merchant_id');
    $obj_id = K($params,'obj_id');
    $status = K($params,'status');

     * 下面是选填
    $api_id = K($params,'api_id',self::API_ID);
    $repay_money = K($params,'repay_money',0);
    $profit = K($params,'profit',0);
    $reward_money = K($params,'reward_money',0);
    $invest_reward_money = K($params,'invest_reward_money',0);
    $fee = K($params,'fee',0);
    $free_money = K($params,'free_money',0);
    $freeze_free_money = K($params,'freeze_free_money',0);
    $title = K($params,'title','');
    $desr = K($params,'desr','');
    $canel_status = K($params,'canel_status',0);
     * @return array
     */
    public function transfer(array $params)
    {
        $return_data = [
            'bool' => 0,
            'message' => '转账失败',
        ];
        $pay_order_model = new PayOrderModel();
        $pay_order_no = $pay_order_model->transfer($params);
        if ($pay_order_no) {
            $return_data['bool'] = 1;
            $return_data['message'] = '转账成功';
            $return_data['pay_order_no'] = $pay_order_no;
        }

        return $return_data;
    }

    //新红包充值
    public function userBonus($uid, $out_order_no, $cashout_type, $money, $title, $desr, $from_user, $obj_type, $obj_id, $api_id=''){
        if(!is_numeric($money)){
            return array('boolen'=>0, 'message'=>'操作失败，参数异常Bonus');
        }
        if($money <= 0){
            return array('boolen'=>0, 'message'=>'操作失败，金额不能为为负数Bonus');
        }
        if($api_id){
            $data = D('Payment/PayOrder')->userBonus($uid, $out_order_no, $cashout_type, $money, $title, $desr, $from_user, $obj_type, $obj_id, $api_id);
        } else {
            $data = D('Payment/PayOrder')->userBonus($uid, $out_order_no, $cashout_type, $money, $title, $desr, $from_user, $obj_type, $obj_id);
        }
        return array('boolen'=>1, 'message'=>'红包奖励成功', 'payorderno'=>$data['order_no']);
    }

    public function cannelBonus($pay_order_id){
        return D('Payment/PayOrder')->cannelBonus($pay_order_id);
    }
	
	//添加券
	public function addConpon($uid, $money, $objType, $objId){
		if(!is_numeric($money)){
			return array('boolen'=>0, 'message'=>'操作失败，参数异常');
		}
		if($money <= 0){
			return array('boolen'=>0, 'message'=>'操作失败，金额不能为为负数');
		}
		$data = D('Payment/PayAccount')->addConpon($uid, $money, $objType, $objId);
		return array('boolen'=>1, 'message'=>'成功添加券');
	}
	
	//过期券
	public function expireConpon($uid, $money, $objType, $objId){
		if(!is_numeric($money)){
			return array('boolen'=>0, 'message'=>'操作失败，参数异常');
		}
		if($money <= 0){
			return array('boolen'=>0, 'message'=>'操作失败，金额不能为为负数');
		}
		$data = D('Payment/PayAccount')->expireConpon($uid, $money, $objType, $objId);
		return array('boolen'=>1, 'message'=>'成功过期券');
	}
	
	//使用券
	public function useConpon($uid, $out_order_no, $cashout_type, $money, $title, $desr, $to_user, $obj_type, $obj_id, $api_id=''){
		if(!is_numeric($money)){
			return array('boolen'=>0, 'message'=>'操作失败，参数异常');
		}
		if($money <= 0){
			return array('boolen'=>0, 'message'=>'操作失败，金额不能为为负数');
		}
		if($api_id){
			$data = D('Payment/PayOrder')->useConpon($uid, $out_order_no, $cashout_type, $money, $title, $desr, $to_user, $obj_type, $obj_id, $api_id);
		} else {
			$data = D('Payment/PayOrder')->useConpon($uid, $out_order_no, $cashout_type, $money, $title, $desr, $to_user, $obj_type, $obj_id);
		}
		return array('boolen'=>1, 'message'=>'券充值奖励成功', 'payorderno'=>$data['order_no']);
	}
	
	//记录账户流水
	public function getBaseInfo($uid){
		return D('Payment/PayAccount')->find((int)$uid);
	}
	//记录转移流水
	public function getMoveApply($uid){
		return
			M('user_account_move')->where( array('uid'=>$uid) )->order( ' id desc')->field('move_amount, ctime, rtime')->select();
	}
	//记录账户流水
	public function getRecordByUid($cond=array(), $parameter=array(), $all = false){
		return D('Payment/PayAccount')->getRecordsByUid($cond, $parameter, $all);
	}

	//记录账户流水
	public function getRecordByUidNew($cond=array(), $parameter=array(), $all = false){
		return D('Payment/PayAccountNew')->getRecordsByUid($cond, $parameter, $all);
	}

	//提现申请记录
	public function getCashoutApplys($cond=array(), $parameter=array()){
		return D('Payment/PayAccount')->getCashoutApplys($cond, $parameter);
	}
	
	//获取最低充值金额 单位元
	public function getMinMoney(){
		$minMoney = getSysData("account", "min_recharge_money");
		$minMoney = $minMoney / 100;
		return $minMoney;
	}
	
	//设置最低充值金额 单位元
	public function setMinMoney($minMoney){
		if($minMoney) $minMoney = $minMoney * 100; //金额从元转成分
		$minMoney = (int)$minMoney;
		setSysData("account", "min_recharge_money", $minMoney);
		return true;
	}
	
// 	/**
// 	 * 减少预计收益
// 	 */
// 	function incWillProfit($uid,$money){
// 	    $uid = (int) $uid;
// 		 $res = M("user_account_summary")->where(array("uid"=>$uid))->setInc("will_profit",$money);
// 		 M("user_account_summary")->where(array("uid"=>$uid))->save(array("mtime"=>time()));
// 		 return $res;
// 	}
	
// 	/**
// 	 * 增加预计收益 
// 	 * @param unknown $uid
// 	 * @param unknown $money
// 	 * @return Ambigous <boolean, unknown, false, number>
// 	 */
// 	function decWillProfit($uid,$money){
// 	    $uid = (int) $uid;
// 	    $res = M("user_account_summary")->where(array("uid"=>$uid))->setDec("will_profit",$money);
// 	    M("user_account_summary")->where(array("uid"=>$uid))->save(array("mtime"=>time()));
// 	    return $res;
// 	}
	
	
	
	//获取提现次数
	public function getCashoutTimes($user_account){
		return D('Payment/PayAccount')->getCashoutTimes($user_account);
	}
	
	//根据所属部门获取商户账户id
	public function getTenantAccountId($dept_id){
		$dept_account = M('dept_account')->find((int)$dept_id);
		if(!$dept_account) throw_exception("还没开通商户账户，请先找运营人员开通，谢谢合作");
		return $dept_account['tenant_account_id'];
	}
	//根据所属部门获取商户账户的银行账户
	public function getDeptFundAccountList($dept_id){
		$dept_account = M('dept_account')->find((int)$dept_id);
		if(!$dept_account) throw_exception("还没开通商户账户，请先找运营人员开通，谢谢合作");
		$fund_account_list = M('loanbank_account')->where(array("uid"=>$dept_account['tenant_account_id'], "is_active"=>1))->select();
		return $fund_account_list;
	}
	
	//给账户充值，$user_account 某个用户的fi_user_account数据， $money 充值金额，单位是分，可能抛异常，请try catch处理
	public function stockRecharge($user_account, $money){
		if(!is_numeric($money)){
			return array('boolen'=>0, 'message'=>'操作失败，参数异常');
		}
		if($money <= 0){
			return array('boolen'=>0, 'message'=>'操作失败，金额不能为为负数');
		}
		return D('Payment/PayAccount')->stockRecharge($user_account, $money);
	}
	
	public function freeTest(){
		echo "freeTest";
	}
	
	public function getRecord($id){
		return D('Payment/PayAccount')->getRecord($id);
	}
	
	//奖励免提现费次数，$times提现次数，可能抛异常，请try catch处理
	public function freeTixianTimes($uid, $times){
		return D('Payment/PayAccount')->freeTixianTimes($uid, $times);
	}
	
	//奖励提现次数，$times提现次数，可能抛异常，请try catch处理
	public function rewardCashoutTimes($uid, $times){
		return D('Payment/PayAccount')->rewardCashoutTimes($uid, $times);
	}
	
	//获取repay_freeeze_cache
	public function getRepayFreeezeCache($uid){
		return D('Payment/PayAccount')->getRepayFreeezeCache($uid);
	}
	
	//减少repay_freeeze_cache金额 $money 单位是分
	public function delRepayFreeezeCache($uid, $money){
		return D('Payment/PayAccount')->delRepayFreeezeCache($uid, $money);
	}
	
	//用户当前投资额度
	public function getCurrentInvest($uid){
		$account_summary = M('user_account_summary')->find($uid);
		return $account_summary['investing_prj_money'] + $account_summary['willrepay_prj_money'] + $account_summary['repayIn_money'];
	}
	
	public function addFreeMoney($uid, $free_money){
		return D("Payment/PayAccount")->addFreeMoney($uid, $free_money);
	}

	public function giveFreeMoney($uid, $free_money, $reward_money = 0, $x_order_id = 0)
	{
		//司马小鑫的购买的时候暂时不变动　清算的时候一起处理了
		if ($x_order_id) {
			return 0;
		}

		$account = M('user_account')->find($uid);
		$sumary = M('user_account_summary')->find($uid);

		$fixMoney = $account['amount'] + $account['amountx'] + $account['reward_money'] + $account['invest_reward_money']
			+ $account['buy_freeze_money'] + $account['buy_freeze_moneyx'] + $sumary['will_principal']
            + $account['zs_amount'] + $account['zs_reward_money'] + $account['zs_buy_freeze_money']
			- $sumary['fastcash_will_original_principal'];
		if ($free_money + $account['free_money'] + $account['freeze_free_money'] <= $fixMoney) {
			return $free_money + $reward_money;
		} elseif ($account['free_money'] + $account['freeze_free_money'] >= $fixMoney) {
			return $reward_money;
		}else{
            return $free_money;
        }
	}
	
	public function incrFreeTixianTimes($uid){
		$where = " where uid=".(int)$uid;
		$sql = "update fi_user_account set free_tixian_times=free_tixian_times+1".$where;
		$res = D('Payment/PayAccount')->execute($sql);
		if(!$res) throw_exception("数据更新异常，请重新再试");
		return true;
	}
	
	public function descFreeTixianTimes($uid){
		$where = " where uid=".(int)$uid;
		$sql = "update fi_user_account set free_tixian_times=free_tixian_times-1".$where;
		$res = D('Payment/PayAccount')->execute($sql);
		if(!$res) throw_exception("数据更新异常，请重新再试");
		return true;
	}
	
	public function getFreeTixianTimes($uid){
		$user_account = M('user_account')->find($uid);
		return $user_account['free_tixian_times'];
	}
	
	public function bfbankList( $payType ){
        $payType = !$payType ? PaymentService::TYPE_YILIAN : $payType;
		$yilian = service("Payment/Payment")->init( $payType );
		$bankList = $yilian->getBankList();
		$list = array();
		
		foreach($bankList as $bank){
			$list[$bank['myCode']] = $bank['name'];
		}
		return $list;
	}
	
	//获取充值银行列表
	public function getRechaBankList($uid){
		$bank_list = array();
		$recharge_list = M('recharge_bank_no')->where(array('uid'=>$uid))->select();
		foreach($recharge_list as $recharge){
			$recharge['is_re'] = 1;// 是充值列表
			$recharge['last4'] = substr($recharge['account_no'],-4,4);
			$bank_list[$recharge['account_no']."_".$recharge['bank']] = $recharge;
		}
		$cashout_list = M('fund_account')->where(array('uid'=>$uid))->select();
		$banList = $this->bfbankList();
		$banKeyList = array_keys($banList);
		foreach($cashout_list as $cashout){
			if(!in_array($cashout['bank'], $banKeyList)) continue;
			$cashout['is_re'] = 0;// 是充值列表
			$cashout['last4'] = substr($cashout['account_no'],-4,4);
			if(!$bank_list[$cashout['account_no']."_".$cashout['bank']]){
				$bank_list[$cashout['account_no']."_".$cashout['bank']] = $cashout;
			}
		}
		return $bank_list;
	}


    //保存绑定手机的银行卡
    public function saveRechargeBank($recharge_bank){
        $version = $recharge_bank['version'];
        $recharge_bank['version'] = $version+1;
        $con = M('recharge_bank_no')->where(array('id'=>$recharge_bank['id'],'version'=>$version))->save($recharge_bank);
        return $con;
    }

    //添加绑定手机的银行卡
    public function addRechargeBank($recharge_bank, $cash_bank_id=0){
        $db = M('recharge_bank_no');
        $db->startTrans();
        try {
            if($recharge_bank['is_default']){
                M('recharge_bank_no')->where( array('uid'=>$recharge_bank['uid']) )->save( array('is_default'=>0) );
                M('fund_account')->where( array('uid'=>$recharge_bank['uid']) )->save( array('is_default'=>0) );
            }
            $id = M('recharge_bank_no')->add($recharge_bank);
            if (!$id) {
                usleep(1000);
                $id = M('recharge_bank_no')->add($recharge_bank);
            }
            if(!$id) throw_exception($db->getDbError().":数据库错误");
            if($cash_bank_id){
                $cash_data['id'] = $cash_bank_id;
                $cash_data['recharge_bank_id'] = $id;
                $cash_data['real_name'] = $recharge_bank['real_name'];
                $cash_data['person_id'] = $recharge_bank['person_id'];
                $cash_data['mobile'] = $recharge_bank['mobile'];
                $cash_data['is_bankck'] = $recharge_bank['is_bankck'];
                $con = M('fund_account')->save($cash_data);
            } else {
                $cash_data['uid'] = $recharge_bank['uid'];
                $cash_data['account_no'] = $recharge_bank['account_no'];
                $cash_data['channel'] = $recharge_bank['channel'];
                $cash_data['acount_name'] = $recharge_bank['account_no'];
                isset($recharge_bank['bank']) && $cash_data['bank'] = $recharge_bank['bank'];
                isset($recharge_bank['bank_name']) && $cash_data['bank_name'] = $recharge_bank['bank_name'];
                isset($recharge_bank['sub_bank']) && $cash_data['sub_bank'] = $recharge_bank['sub_bank'];
                isset($recharge_bank['sub_bank_id']) && $cash_data['sub_bank_id'] = $recharge_bank['sub_bank_id'];
                isset($recharge_bank['bank_province']) && $cash_data['bank_province'] = $recharge_bank['bank_province'];
                isset($recharge_bank['bank_city']) && $cash_data['bank_city'] = $recharge_bank['bank_city'];
                isset($recharge_bank['is_active']) && $cash_data['is_active'] = $recharge_bank['is_active'];
                isset($recharge_bank['is_init']) && $cash_data['is_init'] = $recharge_bank['is_init'];
                isset($recharge_bank['is_default']) && $cash_data['is_default'] = $recharge_bank['is_default'];
                $cash_data['real_name'] = $recharge_bank['real_name'];
                $cash_data['person_id'] = $recharge_bank['person_id'];
                $cash_data['mobile'] = $recharge_bank['mobile'];
                $cash_data['is_bankck'] = $recharge_bank['is_bankck'];
                $cash_data['ctime'] = $recharge_bank['ctime'];
                $cash_data['mtime'] = $recharge_bank['mtime'];
                $cash_data['recharge_bank_id'] = $id;
                $con = M('fund_account')->add($cash_data);
            }
            if(!$con) throw_exception($db->getDbError().":数据库fund_account错误");

            $db->commit();
        }catch (Exception $e){
            $db->rollback();
            throw_exception("银行卡保存失败,".$e->getMessage());
        }
        return $id;
    }

    //更新绑定手机的银行卡
    public function saveBank($arrs, $where){
        $db = M('recharge_bank_no');
        $db->startTrans();
        try {
            if($arrs['is_default']){
                M('recharge_bank_no')->where( array('uid'=>$where['uid']) )->save( array('is_default'=>0) );
                M('fund_account')->where( array('uid'=>$where['uid']) )->save( array('is_default'=>0) );
            }
            $ret = M('recharge_bank_no')->where( $where )->save($arrs);
            if($ret){
                $where_new['uid'] = $where['uid'];
                $where_new['recharge_bank_id'] = $where['id'];
                $con = M('fund_account')->where( $where_new )->save($arrs);
            }
            if(!$con) throw_exception($db->getDbError().":数据库fund_account错误");

            $db->commit();
        }catch (Exception $e){
            $db->rollback();
            throw_exception("银行卡保存失败,".$e->getMessage());
        }
        return $con;
    }

	public function addCashoutAmount($uid, $amount)
	{
		$has_cashout_tamount = M('user_cashout_tamount')->find($uid);
		if ($has_cashout_tamount) {
			$user_cashout_tamount['uid'] = $uid;
			$real_cashout_amount = $has_cashout_tamount['cashout_amount'] + $amount;
			if ($real_cashout_amount < 0) {
				$real_cashout_amount = 0;
			}
			$user_cashout_tamount['cashout_amount'] = $real_cashout_amount;
			$user_cashout_tamount['mtime'] = time();
			$user_cashout_tamount['version'] = $has_cashout_tamount['version'] + 1;
			$con = M('user_cashout_tamount')->where(array(
				'uid' => $has_cashout_tamount['uid'],
				'version' => $has_cashout_tamount['version']
			))->save($user_cashout_tamount);
			if (!$con) {
				$has_cashout_tamount = M('user_cashout_tamount')->find($uid);
				if (!$has_cashout_tamount) {
					throw_exception('异常，查询失败cashout_tamount:' . M('user_cashout_tamount')->getDbError());
				}
				$user_cashout_tamount['uid'] = $uid;
				$real_cashout_amount = $has_cashout_tamount['cashout_amount'] + $amount;
				if ($real_cashout_amount < 0) {
					$real_cashout_amount = 0;
				}
				$user_cashout_tamount['cashout_amount'] = $real_cashout_amount;
				$user_cashout_tamount['mtime'] = time();
				$user_cashout_tamount['version'] = $has_cashout_tamount['version'] + 1;
				$con = M('user_cashout_tamount')->where(array(
					'uid' => $has_cashout_tamount['uid']
				,
					'version' => $has_cashout_tamount['version']
				))->save($user_cashout_tamount);
				if (!$con) {
					throw_exception("并发问题，保存失败cashout_tamount:" . M('user_cashout_tamount')->getDbError());
				}
			}
		} else {
			if ($amount >= 0) {
				$user_cashout_tamount['uid'] = $uid;
				$user_cashout_tamount['cashout_amount'] = $amount;
				$user_cashout_tamount['ctime'] = time();
				$user_cashout_tamount['mtime'] = time();
				$user_cashout_tamount['version'] = 0;
				$id = M('user_cashout_tamount')->add($user_cashout_tamount);
				if (!$id) {
					throw_exception("user_cashout_tamount添加失败:" . M('user_cashout_tamount')->getDbError());
				}
			}
		}
	}

    //获取这个人的自由额度
    public function getTCashoutAmount($uid){
        $user_cashout_tamount = M('user_cashout_tamount')->find($uid);
        return $user_cashout_tamount['cashout_amount'];
    }

    //获取这个银行卡的总提现额度
    public function getCashoutAmountByNo($uid, $fund_account_id){
        $user_cashout_tamount = M('user_cashout_tamount')->find($uid);
        $cashout_amount = $user_cashout_tamount['cashout_amount'];
        $fund_account = M('fund_account')->find($fund_account_id);
        if($fund_account && $fund_account['recharge_bank_id']){
            $recharge_bank_no = M('recharge_bank_no')->find($fund_account['recharge_bank_id']);
            $cashout_amount = $cashout_amount + $recharge_bank_no['cashout_amount'];
        }
        return $cashout_amount;
    }

    //获取这个银行卡的手机提现额度
    public function getMCashoutAmountByNo($fund_account_id){
        $cashout_amount = 0;
        $fund_account = M('fund_account')->find($fund_account_id);
        if($fund_account && $fund_account['recharge_bank_id']){
            $recharge_bank_no = M('recharge_bank_no')->find($fund_account['recharge_bank_id']);
            $cashout_amount = $recharge_bank_no['cashout_amount'];
        }
        return $cashout_amount;
    }


    //直接反现金
    public function addInvestRewardRecord($uid,$prj_id,$prj_type,$prj_days,$order_id,$reward_type,$business_type,$pay_order_no,$invest_money,$remark=''){
    	$now=time();
    	$data=array(
    		'uid'=>$uid,
    		'prj_id'=>$prj_id,
    		'prj_type'=>$prj_type,
    		'prj_days'=>$prj_days,
    		'order_id'=>$order_id,
    		'reward_type'=>$reward_type,
    		'business_type'=>$business_type,
    		'pay_order_no'=>$pay_order_no,
    		'invest_money'=>intval($invest_money),
    		'status'=>1,
    		'remark'=>$remark,
    		'ctime'=>$now,
    		'mtime'=>$now,
    	);
    	$id=M('invest_reward_record')->add($data);
    	if(empty($id)) throw_exception('投资返现提交失败！');
    }


	// 单笔投资金额以5万元为单位  投资越多现金累计越多 上不封顶  
	// 投资期限    		奖励可提现现金
	// ≤30天    				2元
	// 30＜期限≤3个月   		10元
	// 3个月＜期限≤6个月  		20元
	// 6个月＜期限≤12个月 		30元
    /**
     * 亿元红包 感恩回馈 成交额破88亿
	 * 活动时间：4月20日-5月15日 （暂定）
	 * 活动对象：全网用户
     */
    public function processInvestReward(){
    	D('Payment/PayAccount');

    	$map=array(
    		'status'=>1,	//未处理的
    		'business_type'=>1,		//成交额破88亿,感恩回馈
    		'reward_type'=>PayAccountModel::OBJ_TYPE_REWARD_CASHOUT,		//可提现的返现
    	);

    	//一次处理100个，每2分钟处理一次
    	$record=M('invest_reward_record')
    				->where($map)
    				->limit(100)
    				->select();


    	foreach ($record as $item) {
    		$days=intval($item['prj_days']);

    		$unit_reward_money=0;
    		if($days > 0 && $days <= 30){
    			$unit_reward_money=2*100;
    		}else if($days <= 3*30 ){
    			$unit_reward_money=10*100;
    		}else if($days <= 6*30){
    			$unit_reward_money=20*100;
    		}else if($days > 6*30){
    			$unit_reward_money=30*100;
    		}

    		$invest_money=intval($item['invest_money']);	//投资金额
    		$multiple=floor($invest_money/(50000*100));		//五万的倍数 最小整数

    		// echo $invest_money.'-'.$multiple.'x'.$unit_reward_money."\n";
    		// continue;

    		$reward_money=$multiple*$unit_reward_money;		//返现金额

    		$title=$item['remark'];

    		$new_version=intval($item['version'])+1;

    		$umap=array(
    			'id'=>$item['id'],
    			'version'=>$item['version'],
    		);

    		$db = new BaseModel();
    		$db->startTrans();
    		try {
	    		$data=array(
	    			'version'=>$new_version,
	    			'reward_money'=>$reward_money,
	    			'mtime'=>time(),
	    		);
    			
		    	$data['status']= $reward_money > 0 ? 3 : 2;	

				$r=M('invest_reward_record')->where($umap)->save($data);
				if(!$r) throw_exception('更新投资返现信息出现并发问题!');


				//账户返现操作
    			if($reward_money>0){
		    		service('Payment/PayAccount')->rewardCashout(YUNYING_ACCOUNTID, "TRADE88_".$item['id'], $reward_money, $title, '', $item['uid'], PayAccountModel::OBJ_TYPE_REWARD_CASHOUT);
                    $userinfo=M('user')->field('uid,uname')->where(array('uid'=>intval($item['uid'])))->find();
                    D("Message/Message")->simpleSend($item['uid'],1,138,array($userinfo['uname'],intval($reward_money/100)),array(1,3),true);
    			}
				$db->commit();

				echo "id:${item['id']},order_id:${item['order_id']} ";

				if($data['status']==3){
					echo "已处理并返现 \n";
				}else{
					echo "已处理未返现 \n";
				}
    		} catch (Exception $e) {
				$db->rollback();
				$error_msg=$e->getMessage();
				$data=array(
					'error_msg'=>$error_msg,
					'status'=>4,	//处理失败
					'mtime'=>time(),
					'version'=>$new_version,
				);
				M('invest_reward_record')->where($umap)->save($data);
				echo "id:${item['id']},order_id:${item['order_id']} 处理失败，${error_msg}\n";
    		}

    	}

    }

    /**
     * 设置运营备注
     */
    public function setYunyingAdesc($yunying_adesc)
    {
        $this->yunying_adesc = $yunying_adesc;
        return $this;
    }
	//浙商余额转移资金划拨出账
	public function transferOutZs($uid, $out_order_no, $money, $reward_money, $fee, $title, $desr, $to_user, $obj_type, $obj_id, $api_id='',$move_type = 1,$move_id){
		if(!is_numeric($money)){
			return array('boolen'=>0, 'message'=>'操作失败，参数异常');
		}
		if($money <= 0){
			return array('boolen'=>0, 'message'=>'操作失败，金额不能为为负数');
		}
		if(!is_numeric($fee)){
			return array('boolen'=>0, 'message'=>'操作失败，参数异常');
		}
		if($fee < 0){
			return array('boolen'=>0, 'message'=>'操作失败，金额不能为为负数');
		}
		$data = D('Payment/PayOrder')->transferOutZs($uid, $out_order_no, $money,$reward_money, $fee, $title, $desr, $to_user, $obj_type, $obj_id, $api_id,$move_type,$move_id);

		return array('boolen'=>1, 'message'=>'浙商余额转移资金划拨成功', 'payorderno'=>$data['order_no']);
	}

	//浙商余额转移资金划拨进账
	public function transferInZs($uid, $out_order_no, $money, $fee, $title, $desr, $to_user, $obj_type, $obj_id, $api_id='',$move_type = 1){
		if(!is_numeric($money)){
			return array('boolen'=>0, 'message'=>'操作失败，参数异常');
		}
		if($money <= 0){
			return array('boolen'=>0, 'message'=>'操作失败，金额不能为为负数');
		}
		if(!is_numeric($fee)){
			return array('boolen'=>0, 'message'=>'操作失败，参数异常');
		}
		if($fee < 0){
			return array('boolen'=>0, 'message'=>'操作失败，金额不能为为负数');
		}
		$data = D('Payment/PayOrder')->transferInZs($uid, $out_order_no, $money, $fee, $title, $desr, $to_user, $obj_type, $obj_id, $api_id,$move_type);

		return array('boolen'=>1, 'message'=>'浙商余额转移资金划拨成功', 'payorderno'=>$data['order_no']);
	}
	/*
	 * 浙商账户转移申请
	 * @param int $uid
	 * @param int $money -1|全部转移 >0|部分转移 转移金额
	 * @param int $type 1|存量账户转移 2|回款转移
	 * @param $order_on 订单号,提供给java由于资金不足导致的再次发起申请
	 */
	public function zsAccountTransferApply($uid, $money = -1, $type = 1, $order_no = ''){
		try{
			$db = M('user_account_move');
			$db->startTrans();
			$user = M('user')->find($uid);
			if( $user['zs_status'] != 2 ) throw_exception( '非法操作,未开通存管户' );
			$account_id = service("Public/BonusAccount")->getFromUidByAccountType( BonusAccountService::ACCOUNT_TYPE_ZS_TRANS );
			$pay_account = D('Payment/PayAccount')->where(array('uid' => $account_id))->find();
			if( $order_no ){
				$move = M('user_account_move')->where( array('uid'=>$uid, 'order_no'=>$order_no) )->find();
				if( $move['status'] != 1 ) throw_exception('重复申请状态不支持, order_no = ' .$order_no . ', status = ' . $move['status']);
				$move_id = $move['id'];
				$amount =  $move['amount'];
				$reward_money = $move['reward_money'];
				$total_move_money = $move['move_amount'];
			}else{
				$user_account = D('Payment/PayAccount')->where(array('uid' => $uid))->find();
				$amount =  $user_account['amount'];
				$reward_money = $user_account['reward_money'];
				$total_move_money = $amount + $reward_money;
				if ($total_move_money <= 0) {
					throw_exception("账户金额不足,无需转移");
				}
				if($total_move_money < $money) throw_exception( '转移金额大于账户余额' );
				if( $money > 0 ){
					$type = 3;//部分转移
					$total_move_money = $amount = $money;
					$reward_money = 0;
					if( $user_account['amount'] < $money ){
						$amount =  $user_account['amount'];
						$reward_money = $money - $amount;
					}
				}
				$move = D('Payment/PayAccount')->zsAccountTransferApply($uid, $type, $total_move_money, $pay_account['amount']);
				$move_id = $move['move_id'];
				$order_no = $move['order_no'];
				D('Payment/PayAccount')->transfer(
					array(
						'to_user'=>$uid,
						'money' => $total_move_money,
						'profit' => $reward_money,
						'from_user'=>$uid,
						'obj_type'=>PayAccountModel::OBJ_TYPE_ZS_MOVING,
						'obj_id'=>$move_id,
						'pay_order_id'=>$move_id,
						'out_order_no'=>$move_id,
						'desr'=>'',
						'remark'=>'原账户余额正转移至投资人存管户',
					)
				);
			}
			if( !$move_id ) {
				throw_exception("申请失败");
			}
			if ($pay_account['amount'] < $total_move_money) {
				//发送短信,通知财务充钱
				$content = array(
					date('Y-m-d'),
					date('Y-m-d', strtotime('+1 day')),
					date("Y-m-d H:i")
				);
//				service("Message/Message")->sendMessage($uid, 1, $type, $content, 0, 0, array(3), 1);
				throw_exception("支付账户" . $account_id . "余额不足");
			}

			$desc = $type == 1 ? '存量用户-' : '回款-';
			$res = $this->transferOutZs($account_id, time()."_".$total_move_money."_".$uid."_".$type, $amount,$reward_money, 0,
				$desc . "余额转移专用账户(".$account_id.")划拨".$total_move_money."元给用户(".$uid.")",
				$desc . "余额转移专用账户(".$account_id.")划拨".$total_move_money."元给用户(".$uid.")", $uid
				, PayAccountModel::OBJ_TYPE_ZS_MOVING, $uid, $type, $move_id);
			if( $res['boolen'] == 1 ){
				$param['count'] = 1;
				$param['amount'] =  $amount;
				$param['order_no'] = $order_no;
				$param['bindSerialNo'] = $user['zs_bind_serial_no'];

//				$this->zsTransferApi( $param );
				import_app('Payment.Service.Payment.ZheShangPayment');
				$zheshang = new ZheShangPayment();
				$ret = $zheshang->platTransfer( $param );
				if( $ret['boolen'] != 1 ){
					throw_exception($ret['message'] || "网络异常");
				}
			}
			$db->commit();
			if( $ret['boolen'] == 1){
				$param['dataId'] = $param['order_no'];
				$res = $this->zsTransferApplyCall($ret['data']['value'], $param);
				if( $res !== true ) throw_exception( $res );
			}
			return true;
		}catch (Exception $e){
			$db->rollback();
			return $e->getMessage();
		}

	}

	public function zsTransferApi( $param , $remark = 'transfer money', $type = 1, $acctType = 1){

		$params = array(
			'serviceId' => 'czb_platfundTransfer',
			'count' => $param['count'],
			'amount' => $param['amount'],
			'remark' => $remark,
			'dataId' => $param['order_no'],
			'params'=>json_encode(array(
					'details'=>array(
						array(
							'bindSerialNo' => $param['bindSerialNo'],
							'orderNo' => $param['order_no'],
							'amount' => $param['amount'],
							'currency' => '156',
							'transferType' => $type,//1存管e户 2主账户
							'acctType' => $acctType,//1对私 2对公
							'branchNo' => isset($param['bank_no']) ? $param['bank_no'] : '',//开户行人行联行号-分给他行对公账户时上送
							'branchName' => isset($param['bank_name']) ? $param['bank_name'] : '',//开户行人行联行名-分给他行对公账户时上送
							'memo' => '',//备注
						)
					)
				)
			),
		);
		$res = async_call_czb_api('czb_platfundTransfer', $params, $param['order_no'], 'PayAccountService::zsTransferApplyCall', ['Payment','Service','PayAccountService']);
		if( !$res ) throw_exception( '平台分账入队失败' );
	}

	//平台分账申请,因为当前抛异常只会引起前面队列重复发起,所以为防止本地业务失败回滚入队,可重复处理
	public function zsTransferApplyCall(array $response, array $request){
		$move = M('user_account_move')->field('id,status')->where(array('order_no' => $request['dataId']))->find();
		if( $move['status'] > 1 ){
			return '已经处理成功,请勿重复处理,状态值:'.$move['status'];
//			return true;
		}
		$response = json_decode($response['details'], true);
		$response = $response[0];
		if ($response['status'] == 20) {
				$status = 4;
		}
		if ($response['status'] == 30) {
			$status = 3;
		}
		$data = array('status'=>$status, 'out_id'=>$response['bankSerialNo'], 'rtime'=>time());
		$ret = M('user_account_move')->where(array('order_no' => $request['dataId']))->save( $data );
		if( !$ret ) {
			return '平台分账中间状态保存失败';
//			return false;
		}
		return true;
//		$res = queue('zs_transfer', $request['dataId']. '_'.time(), array('request'=>$request, 'response'=>$response));
//		if( !$res ) throw_exception( '平台分账业务处理入队失败' );

	}

	//浙商存管余额转移资金到账处理
	public function zsAccountTransferDeal($order_no = ''){
		$status_info = array(
			'00' => '不存在或未处理',
			'10' => '处理中',
			'20' => '到账成功',
			'30' => '失败',
		);
		try{
			import_app('Payment.Service.Payment.ZheShangPayment');
			$db = M('user_account_move');
			$db->startTrans();
			$zheshang = new ZheShangPayment();
			$arr['order_no'] = $order_no;
			$arr['trade_type'] = 8;//分账
			$ret = $zheshang->queryResult( $arr );
			if( $ret['boolen'] == 1){
				$status = $ret['data']['value']['status'];
				if($status == '20'){
					$move = M('user_account_move')->where( array('order_no'=>$order_no) )->find();
					if( $move['status'] != 4 ) throw_exception('申请状态不支持, order_no = ' .$order_no . ', status = ' . $move['status']);
					$data = array('status'=>2, 'atime'=>time());
					$ret = M('user_account_move')->where(array('order_no' => $order_no))->save( $data );
					if( !$ret ) throw_exception('到账状态更新失败');
					$move_id = $move['id'];
					$reward_money = $move['reward_money'];
					$total_move_money = $move['move_amount'];
					D('Payment/PayAccount')->transfer(
						array(
							'to_user'=>$move['uid'],
							'money' => $total_move_money,
							'profit' => $reward_money,
							'from_user'=>$move['uid'],
							'obj_type'=>PayAccountModel::OBJ_TYPE_ZS_MOVED,
							'obj_id'=>$move_id,
							'pay_order_id'=>$move_id,
							'out_order_no'=>$move_id,
							'desr'=>'',
							'remark'=>'原账户余额已转移至投资人存管户',
						)
					);
					$db->commit();
					return true;
				}
				throw_exception('订单'.$status_info[$status]);
			}
			throw_exception($ret['message'] ? $ret['message'] : '网络异常');

		}catch (Exception $e){
			$db->rollback();
			return $e->getMessage();
		}
	}

	//已还款但资金未到账项目
	function noArriveMoneyPrj( $uid ){
		$where['fi_prj_order.uid'] = $uid;
		$where['zs_repay_status'] = array('in', array(PrjRepayPlanModel::ZS_REPAY_STATUS_BEFORE_CALLBACK, PrjRepayPlanModel::ZS_REPAY_STATUS_ING));
		$res = M('prj_order')
			->field('fi_prj_order.freeze_time,fi_prj.id prj_id,prj_name,expect_repay_time,fi_prj_order.money money,fi_prj_order.id prj_order_id,is_have_repayplan')
			->join('right join fi_prj_repay_plan on fi_prj_order.prj_id=fi_prj_repay_plan.prj_id ')
			->join('inner join fi_prj on fi_prj_order.prj_id=fi_prj.id')
			->where($where)
			->select();
		foreach($res as &$v){
			$ticketInfo=M('user_bonus_freeze')->where(array('order_id'=>$v['prj_order_id']))->find();
			$income = 0;
			if($v['is_have_repayplan']) {
				$income = service('Financing/Invest')->getSpecialIncome($v['prj_id'], $v['money'], $v['freeze_time'], array(
					'is_have_biding' => true,
					'is_have_reward' => true,
					//'user_ticket_id' => $orderExt['reward_id'],
					'rate'=>$ticketInfo['rate'],
					'user_ticket_only_unused' => false,
					'order_id' => $v['prj_order_id'],
				));
			}
			$v['income'] = humanMoney($income, 2, false);
			$v['money'] = humanMoney($v['money'], 2, false);
			$v['expect_repay_time'] = date('Y-m-d');
			$v['financing_url'] = U("Account/Financing/financinginfo",array('id' => $v['prj_order_id']));
		}

		return $res;
	}
	
	//封装账户信息相关,默认pc
	function accountInfo( $uid, $source = 1 ){
		$account = $this->getBaseInfo( $uid );

		//在途资金
		$moving_amount = ($account['zs_moving_money'] + $account['zs_moving_reward_money'] + $account['zs_repaying_money']);
		$moving['moving_view']=humanMoney( $moving_amount,2,false);

		//原账户余额转移至存管户资金
		$moving['moving_list'] = $this->getMoveApply($uid);
		foreach($moving['moving_list'] as &$value){
			$value['ctime'] = date('Y-m-d H:i', $value['ctime']);
			$value['move_amount'] = humanMoney($value['move_amount'], 2, false);
		}

		//已还款未到账资金
		$moving['repaying_view'] = humanMoney( $account['zs_repaying_money'], 2, false );
		$moving['repaying_100_view'] = round( $account['zs_repaying_money'] / $moving_amount, 2 ) * 100;

		//余额转移未到账资金
		$moving['transfer_view'] = humanMoney( $account['zs_moving_money'] + $account['zs_moving_reward_money'], 2, false );
		$moving['transfer_100_view'] = round( ($account['zs_moving_money'] + $account['zs_moving_reward_money']) / $moving_amount, 2 ) * 100;
		
		//已还款但资金未到账项目
		$source == 1 && $account['noArrive_list'] = $this->noArriveMoneyPrj( $uid );
		
		//可用余额
		$account['top_amount_view']=humanMoney(($account['amount'] + $account['zs_amount'] + $account['zs_reward_money'] + $account['reward_money']),2,false);
		$account['amount_view']=$account['top_amount_view'];

		$account['freeze_money'] = humanMoney(($account['buy_freeze_money'] + $account['cash_freeze_money']), 2,
			false);
		$sumary = service('Account/Account')->getSummaryById($uid);
		$repaying_reward = service('Financing/Invest')->getRepayingReward($uid);

		//账户净资产=可用余额+投资资产+回款冻结-我的变现
		$account['net_asse'] = humanMoney(
			(
				$account['amount']
				+ $account['zs_amount']
				+ $account['zs_reward_money']
				+ $account['reward_money']
				+ $account['buy_freeze_money']
				+ $account['zs_buy_freeze_money']
				+ $sumary['will_principal']
				- $sumary['fastcash_will_original_principal']
				- $repaying_reward
			),
			2,
			false
		);
		//普通账户总额
		$account['xhh_amount_view'] = humanMoney($account['amount'] + $account['reward_money'], 2, false);
		//存管账户总额
		$account['zs_amount_view'] = humanMoney($account['zs_amount'] + $account['zs_reward_money'], 2, false);

		//我的投资
		$repaying_reward = service('Financing/Invest')->getRepayingReward($uid);
		$account['money_invest'] = humanMoney(
			(
				$account['buy_freeze_money']
				+ $account['zs_buy_freeze_money']
				+ $sumary['will_principal']
				- $repaying_reward
			),
			2,
			false
		);

		//我的变现
		$account['money_debt'] = humanMoney($sumary['fastcash_will_original_principal'],2,false);
		
		//待收收益
		$will_profit_no_pay = service('Account/Account')->getRewardingAmount($uid);//2016.3.18 未支付包含的金额
		$waiting_freeze=service('Account/Account')->getFreezeAmount($uid,2);//2016.3.18 冻结的amount
		$sumary['will_profit_view'] = humanMoney($sumary['will_profit'] + $sumary['will_reward'] - ($sumary['fastcash_principal'] - $sumary['fastcash_will_original_principal']) - $sumary['fastcash_profit']-$waiting_freeze-$will_profit_no_pay + $repaying_reward,
			2, false);
		//已赚收益
		$waited_freeze=service('Account/Account')->getFreezeAmount($uid,3);//2016.3.18 冻结的amount
		$objservice = service('Payment/AccountSearchNew');
		$record_sum = $objservice->getRewardSum($uid);//201556增加取奖励的总数
		$account['profit_view'] = humanMoney(
			$account['profit']
			+ ($sumary['fastcash_principal']
				+ $sumary['fastcash_repay_principal']
				- $sumary['fastcash_will_original_principal']
				- $sumary['fastcash_repayed_original_principal']
			)
			- $sumary['fastcash_repayed_original_profit']
			+ $record_sum['reward_sum']
			- $record_sum['fee_sum']
			- $record_sum['transfer_surplus_principal']
			- $record_sum['surplus_reward']
			-$record_sum['flbt_reward_sum']
			-$waited_freeze,
			2, false);//201556已赚收益新的计算公式
		//预期总收益
		$sumary['total_profit'] = humanMoney($sumary['will_profit'] + $sumary['will_reward'] - ($sumary['fastcash_principal'] - $sumary['fastcash_will_original_principal']) - $sumary['fastcash_profit']-$waiting_freeze+
			+ $account['profit'] + ($sumary['fastcash_principal'] + $sumary['fastcash_repay_principal'] - $sumary['fastcash_will_original_principal'] - $sumary['fastcash_repayed_original_principal']) - $sumary['fastcash_repayed_original_profit'] + $record_sum['reward_sum'] - $record_sum['fee_sum'] - $record_sum['transfer_surplus_principal'] - $record_sum['surplus_reward']-$record_sum['flbt_reward_sum']-$waited_freeze-$will_profit_no_pay + $repaying_reward,
			2, false);//201556预期总收益

		$account['buy_freeze_money_view'] = humanMoney($account['buy_freeze_money']+$account['zs_buy_freeze_money'], 2, false);
		$account['cash_freeze_money_view'] = humanMoney($account['cash_freeze_money'], 2, false);
		$account['reward_money_view'] = humanMoney($account['reward_money'], 2, false);
		$account['invest_reward_money_view'] = humanMoney($account['invest_reward_money'] + $account['coupon_money'],
			2, false);

		$data['moving'] = $moving;
		$data['account'] = $account;
		$data['sumary'] = $sumary;
		return $data;
	}

	public function getPayment( $recharge ){
		$payment_config = C('PAYMENT');
		$zs_config = $payment_config['zheshang'];
		$zs = $zs_config['config'];
		if (C('APP_STATUS') != 'product') {
			$zs = $zs_config['config_test'];
		}
		$payment_config_recharge = $zs[$recharge];
		if( !$payment_config_recharge ) throw_exception('请检查payment配置');
		return $payment_config_recharge;
	}

	//浙商充值频次控制
	public function zsRechargeFreq( $uid, $amount ){
		$flag = 0;
		$payment_config_recharge = $this->getPayment( 'recharge' );
		if($amount >= $payment_config_recharge['per_limit']) return true;
		if($amount < $payment_config_recharge['min_money']){
			throw_exception('充值金额最小不得低于'.$payment_config_recharge['min_money'].'元');
		}
		$cacheKey = $uid.'_recharge'.'_'.date('Y-m-d').'_'.$payment_config_recharge['per_limit'];
		$date_end = date('Y-m-d', strtotime('+1 day'));
		$expire = strtotime( $date_end ) - time();
		$all = cache($cacheKey) ? cache($cacheKey) : 0;
		if( $all >= $payment_config_recharge['times'] ){
			throw_exception('您今日小额充值操作过于频繁，请提高单次充值金额再试。');
		}
		if( $amount < $payment_config_recharge['per_limit'] ){
			$all += 1;
			cache($cacheKey, $all, array('expire'=> $expire));
		}
		return true;
	}
}






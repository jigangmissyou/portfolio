<?php
class PayAccountAction extends BaseAction {



	public function _initialize()
	{
		parent::_initialize();
        $this->assign('basicUserInfo',service('Account/Account')->basicUserInfo($this->loginedUserInfo));
	}


	//模拟充值
	public function justRecharge(){
		$payment = service('Payment/Payment');
		$type = PaymentService::TYPE_SHENFUT;
		$payment = $payment->init($type);
		$list = $payment->getBankList();
		
		$this->assign("type", $type);
		$this->assign("list", $list);
		$this->display();
	}
	//模拟充值
	public function justDoRecharge(){
		exit;
		$amount = $this->_post('amount'); //金额
		$bankCode = $this->_post('bankCode'); //如果是银行支付，银行code
		$out_account_no = $this->_post('out_account_no'); //银行账号, 一般不传
		$out_account_id = $this->_post('out_account_id'); //银行账号, 一般不传
		if(!$amount) print_r("请输入>0金额");
		$paymentParams = array();
		$payType = 'shenfut';
		$payment = service('Payment/Payment')->init($payType);
		$myBankCode = $payment->getMyCode($bankCode);
		
		if($out_account_id){
			$fund_account = M('fund_account')->where("id=".$out_account_id)->find();
			if($fund_account){
				$myBankCode = $fund_account['bank'];//线下里的银行list code
				$out_account_no = $fund_account['out_account_no'];
			} else {
				$out_account_id = '';
			}
		}
		
		if($bankCode){
			$paymentParams['bankCode'] = $bankCode;
			$list = $payment->getBankList();
			foreach($list as $bankParam){
				if($bankParam['code'] == $bankCode){
					$paymentParams['bankName'] = $bankParam['name'];
				}
			}
		}

        $amount_100 = bcmul($amount_100, '100', 0);
		$ticket = D('Payment/Ticket')->init($amount_100, 0, $this->loginedUserInfo['uid']
				, $payType, $myBankCode, $paymentParams, 0, $out_account_no, $out_account_id);
		
		$_param['Name'] = 'REP_B2CPAYMENT';
		$_param['Version'] = 'V4.1.2.1.1';
		$_param['Charset'] = 'UTF-8';
		$_param['TraceNo'] = 'db7ecb23-0f20-42ed-8d4c-f4dfb23ac64d';
		$_param['MsgSender'] = 'SFT';
		$_param['SendTime'] = date('YmdHis');
		$_param['InstCode'] = 'SNDA';
		if($myBankCode) $_param['InstCode'] = $myBankCode;
		$_param['OrderNo'] = $ticket['ticket_no'];
		$_param['OrderAmount'] = $amount;
		$_param['TransNo'] = date('YmdHis').'454751';
		$_param['TransAmount'] = $amount;
		$_param['TransStatus'] = '01';
		$_param['TransType'] = 'PT002';
		if($myBankCode) $_param['TransType'] = 'PT001';
		$_param['TransTime'] = date('YmdHis');
		$_param['MerchantNo'] = '100894';
		$_param['ErrorMsg'] = '';
		$_param['ErrorCode'] = '';
		$_param['Ext1'] = '';
		$_param['SignType'] = 'MD5';
		
		$sign_str_array = array();
		foreach ($_param as $key => $val) {
			if($val) $sign_str_array[] = sprintf("%s", $val);
		}
		$sign_str_array[] = 'shengfutongSHENGFUTONGtest';
		$sign_str = implode("", $sign_str_array);
		$signature =  strtoupper(md5($sign_str));
		
		$_param['SignMsg'] = $signature;
		$_param['PaymentNo'] = date('YmdHis').'194914';
		$_param['PayableFee'] = '0.00';
		$_param['Ext2'] = '';
		$_param['PayChannel'] = '14';
		$_param['ReceivableFee'] = '0.00';
		$param = $payment->parseNotifyParam($_param);
		$result = $payment->callBackNotify($param);
		print_r($result);
		
	}
	
	public function getMyPrjProfitInfo(){
		$recordId = (int)$this->_request('recordId'); //记录id
		if(!$recordId) throw_exception("请传入recordId");
		$data = D("Payment/PayAccount")->getMyPrjProfitInfo($recordId);
        $this->assign('data', $data);
        $this->display();
	}
	
	public function rePayRechargePage(){
		$user = M("User")->find($this->loginedUserInfo['uid']);
		if(!$user) throw_exception("请先登入");
		$tenantId = service("Account/Account")->getTenantIdByUser($user);
// 		$tenantId = service("Payment/PayAccount")->getTenantAccountId($user['dept_id']);
		
		$userAccount = D("Payment/PayAccount")->find($tenantId);
		if($userAccount['is_merchant'] == '1'){
			$this->minMoney = 0;
		} else {
			$this->minMoney = service('Payment/PayAccount')->getMinMoney();
		}
        $this->assign('role',$this->loginedUserInfo['role']);
        $this->assign('amount', (int)I('request.amount'));
		$this->display();
	}
	

	//还款提交充值
	public function repaysubmit(){
		$payType = $this->_post('payType');//选择支付类型
		$amount = $this->_post('amount'); //金额 元
		$bankCode = $this->_post('bankCode'); //如果是银行支付，银行code
		$bankName = $this->_post('bankName'); //如果是银行名称
		$out_account_no = $this->_post('out_account_no'); //银行账号, 一般不传
		$out_account_id = $this->_post('out_account_id'); //银行账号, 一般不传
		$bak = $this->_post('bak'); //备注
		$authCode = $this->_post('authCode');
		$deposit_prj_id = $this->_post('deposit_prj_id'); //保证金充值项目id
		$zhr_re_prj_id = $this->_post('zhr_re_prj_id'); //直融保证金充值项目id
		$zr_type = $this->_post("zr_type");//直融金额类型
		$xhh_bank_name = $this->_post("xhh_bank_name");//鑫合汇或存管,"招商银行杭州分行"


		$paymentParams = array();
		$payment = service('Payment/Payment')->init($payType);
		if($amount<=0) {
			ajaxReturn(0,"请输入大于0的金额",0);
		}

		if($deposit_prj_id){
			$deposit_prj = M('prj')->find($deposit_prj_id);
			if(!$deposit_prj) ajaxReturn(0,"保证金的项目不存在",0);
//			if($deposit_prj['deposit']) ajaxReturn(0,"该项目已经充值过保证金了",0);
		}

		if($zhr_re_prj_id){
			$zhr_re_prj = M('prj')->find($zhr_re_prj_id);
			if(!$zhr_re_prj) ajaxReturn(0,"保证金的项目不存在",0);
            if($zhr_re_prj['zhr_recharge'])  $this->error("该项目已经充值过保证金了");
            $fee_arr = D('Zhr/FinanceApply')->getFee($zhr_re_prj_id);
            $total_fee = $fee_arr['9999'];//总金额
            
            if(round($total_fee)!=bcmul($amount, '100', 0)){
                ajaxReturn(0,"保证金金额必须是".humanMoney($total_fee,2,false)."元",0);
            }
// 			if($zhr_re_prj['zhr_recharge']) throw_exception("该项目已经充值过保证金了");
		}
		$is_zs = 0;
		$prj_id = $zhr_re_prj_id > 0 ? $zhr_re_prj_id : $deposit_prj_id;
		$prj_id > 0 && $is_zs = M('prj_ext')->where( array('prj_id'=>$prj_id) )->getField('is_deposit');
		/* 检测验证码 */
		if($payType == PaymentService::TYPE_XIANXIA && !check_verify($authCode)){
            ajaxReturn(0,'验证码输入错误！',0);
		}

		$myBankCode = $payment->getMyCode($bankCode);

		if($out_account_id){
			$fund_account = M('fund_account')->where("id=".$out_account_id)->find();
			if($fund_account){
				$myBankCode = $fund_account['bank'];//线下里的银行list code
				$out_account_no = $fund_account['out_account_no'];
			} else {
				$out_account_id = '';
			}
		}

		$user = M("user"); // 实例化User对象

		$cacheKey = "payaccountAction_submit_".$this->loginedUserInfo['uid'];
		if(cache($cacheKey) == time()){
			$result = array('boolen'=>0, 'message'=>'异常操作, 刷频太频了');
			showJson($result);
			exit;
		}
		cache($cacheKey, time(), array('expire'=>10));
		// 手动进行令牌验证
		// 		if (!$user->autoCheckToken($_POST)){
		// 			$result = array('boolen'=>0, 'message'=>'异常操作');
		// 			showJson($result);
		// 		}

		if($bankCode){
			$paymentParams['bankCode'] = $bankCode;
			$list = $payment->getBankList();
			foreach($list as $bankParam){
				if($bankParam['code'] == $bankCode){
					$paymentParams['bankName'] = $bankParam['name'];
				}
			}
		} else if($bankName){
			$paymentParams['bankName'] = $bankName;
		}
		if($bak) $paymentParams['bak'] = $bak;
		if($deposit_prj_id) $paymentParams['deposit_prj_id'] = $deposit_prj_id;
		if($zhr_re_prj_id) $paymentParams['zhr_re_prj_id'] = $zhr_re_prj_id;
		if($zr_type) $paymentParams['zr_type'] = $zr_type;
        if($zr_type) $paymentParams['zhr_re_type'] = $zr_type;
		$paymentParams['is_zs'] = $is_zs;
		$paymentParams['xhh_bank_name'] = $xhh_bank_name;
		try{
			$myUser = M("user")->find($this->loginedUserInfo['uid']);
			if($deposit_prj_id){
				$tenantId = service("Financing/Project")->getTenantIdByPrj($deposit_prj);
// 				$tenantId = service("Payment/PayAccount")->getTenantAccountId($deposit_prj['dept_id']);
			} else if($zhr_re_prj_id){
				$tenantId = service("Financing/Project")->getTenantIdByPrj($zhr_re_prj);
			} else {
				$tenantId = service("Account/Account")->getTenantIdByUser($myUser);
// 				$tenantId = service("Payment/PayAccount")->getTenantAccountId($myUser['dept_id']);
			}
            if(!$tenantId){
                $tenantId = service("Application/ProjectManage")->getTenantByUid($this->loginedUserInfo['uid']);
            }
			$result = $payment->submit(bcmul($amount, '100', 0), $tenantId, $myBankCode, 1, $out_account_no, $out_account_id, $paymentParams);
		} catch(Exception $e){
			D("Admin/AccountExceptLog")->insertLog($this->loginedUserInfo['uid'], "PayAccount/repaysubmit", "Payment/Payment", "submit", $e->getMessage());
			$result = array('boolen'=>0, 'message'=>$e->getMessage());
		}
		if($result){
			showJson($result);
		}
	}
	
	//线下申请初始化页面
	public function repayxianxia(){
		$deposit_prj_id = I('request.deposit_prj_id'); //保证金充值项目id
		$zhr_re_prj_id = I('request.zhr_re_prj_id'); //保证金充值项目id
		$amount = I("request.amount");//金额 元

		$zr_type = I("request.zr_type");//直融金额类型
        if(!$zr_type) $zr_type = "-1";
        $this->zr_type = $zr_type;
		if($amount && !is_numeric($amount)) $this->error("参数异常，金额不是数字");
		if($deposit_prj_id){
            $prj = M('prj')->find($deposit_prj_id);
            if(!$prj) throw_exception("保证金的项目不存在");
//            if($prj['deposit']) throw_exception("该项目已经充值过保证金了");
            $this->prj = $prj;
		} else if($zhr_re_prj_id){
            $prj = M('finance_apply')->where(array("prj_id"=>$zhr_re_prj_id))->find();
			if(!$prj)  $this->error("保证金的项目不存在");
			if($prj['zhr_recharge'])  $this->error("该项目已经充值过保证金了");
			$this->prj = $prj;

			if($zr_type<0){
				$fee_arr = D('Zhr/FinanceApply')->getFee($zhr_re_prj_id);
				$total_fee = 0;
				foreach($fee_arr as $fee_key=>$fee_a){
					if($fee_key == 9999) continue;
					$total_fee += $fee_a;
				}
				$amount = number_format($total_fee/100,2,".",""); //元 ;

			}



		}
		$myUser = M("user")->find($this->loginedUserInfo['uid']);
        $prjInfo = M("prj")->find($prj['prj_id']);
		if($deposit_prj_id){
			$tenantId = service("Financing/Project")->getTenantIdByPrj($prjInfo);
// 			$tenantId = service("Payment/PayAccount")->getTenantAccountId($prj['dept_id']);
		} else if($zhr_re_prj_id){
			$tenantId = service("Financing/Project")->getTenantIdByPrj($prjInfo);
		} else {
			$tenantId = service("Account/Account")->getTenantIdByUser($myUser);
// 			$tenantId = service("Payment/PayAccount")->getTenantAccountId($myUser['dept_id']);
		}
        if(!$tenantId){
            $tenantId = service("Application/ProjectManage")->getTenantByUid($this->loginedUserInfo['uid']);
        }
		$userAccount = D("Payment/PayAccount")->find($tenantId);
		if(!$userAccount)  $this->error("请先登入");
		if($userAccount['is_merchant'] == '1'){
//			if($amount <= 0){
//				throw_exception("参数异常，充值金额必须大于0元");
//			}
		} else if($amount < service('Payment/PayAccount')->getMinMoney()){
            $this->error("参数异常，充值金额低于最低充值金额".service('Payment/PayAccount')->getMinMoney()."元");
		}
		$is_zs = 0;
		$prj_id = $zhr_re_prj_id > 0 ? $zhr_re_prj_id : $deposit_prj_id;
		$prj_id > 0 && $is_zs = M('prj_ext')->where( array('prj_id'=>$prj_id) )->getField('is_deposit');
		$checkMoney = bcmul($amount, '100', 0);
		$payment = service('Payment/Payment');
		$type = PaymentService::TYPE_XIANXIA;
		$payment = $payment->init($type);
		$this->bankList = $payment->getBankList();
		$this->amount = $amount; //元
		$this->ptAccountNo = ($zhr_re_prj_id > 0 ||  $deposit_prj_id > 0) ? '571908130810602' : '33001613535053020478';
		$this->subBank = ($zhr_re_prj_id > 0 ||  $deposit_prj_id > 0) ? '招商银行杭州分行' : '中国建设银行浙江省分行营业部';
		$this->ptAccountName = '杭州鑫合汇互联网金融服务有限公司';
		$this->deposit_prj_id = $deposit_prj_id;
		$this->zhr_re_prj_id = $zhr_re_prj_id;
		$this->is_zs = $is_zs;
		if($userAccount['is_merchant'] == '1'){
			$this->minMoney = 0;
		} else {
			$this->minMoney = service('Payment/PayAccount')->getMinMoney();
		}
		$this->display();
	}
	
	// --------------
	public function rechargePage(){
		$user = M("user")->find($this->loginedUserInfo['uid']);
		$userAccount = D("Payment/PayAccount")->find($this->loginedUserInfo['uid']);
		if(!$userAccount) throw_exception("请先登入");
		if($userAccount['is_merchant'] == '1'){
			$this->minMoney = 0;
		} else {
			$this->minMoney = service('Payment/PayAccount')->getMinMoney();
		}
		
		$this->is_id_auth = 0;
		if($user['is_id_auth'] && $user['person_id']) $this->is_id_auth = 1;
		
		$this->has_recharge = 0;
		$has_recharge = M("out_ticket")->where(array("uid"=>$this->loginedUserInfo['uid'], "in_or_out"=>1, "status"=>2))->find();
        $recharge_bank = M('recharge_bank_no')
            ->field('account_no,bank, bank_name')->where(array('uid'=>(int)$this->loginedUserInfo['uid']))->order('mtime desc')->find();
		if( $has_recharge ){
            $payment = service('Payment/Payment')->init( 'yibatong' );
            $ybt_banklist = $payment->payment->bankList;
            foreach($ybt_banklist as $v){
                if($v['myCode'] == $recharge_bank['bank'] ){
                    $recharge_bank['myCode'] = $v['myCode'];
                    $recharge_bank['code'] = $v['code'];
                }
            }
        }
        $pc_bank = M('recharge_bank_pc_channel')->where( array('recharge_status'=>2) )->order('`myorder` desc')->select();
        $bankList = array();
        foreach($pc_bank as $pc){
            $v['bank'] = $pc['code'];
            $v['bank_name'] = $pc['bank_name'];
            $v['abbr'] = $pc['channel'];
            $v['logo'] = $pc['logo'];
            $v['list2'] = service('Mobile2/Bank')->getPcBankExtListById( $pc['id'] );
            $v['num'] = count($v['list2']);
            $v['list1'] = array_shift($v['list2']);
            $v['num2'] = count($v['list2']);
			if($pc['mycode'] == $recharge_bank['myCode']){
				$recharge_bank['channel'] = $pc['channel'];
			}
            $bankList[] = $v;
        }
        $this->bankList = $bankList;
        $this->recharge_bank = $recharge_bank;
        if($has_recharge){
			$this->has_recharge = 1;
		}
		$this->is_xia_recharge = $user['is_xia_recharge'];
        $this->assign('role',$this->loginedUserInfo['role']);
		$this->assign('uid_type',$this->loginedUserInfo['uid_type']);
		$user_status = service("Account/Account")->userStatus($this->loginedUserInfo);
		$this->assign("userStatus",$user_status);
        if($user['uid_type'] == 2) $this->is_xia_recharge = 1;
        if($user['uid_type'] != 2){
            $this->assign('remain_money',humanMoney(($userAccount['amount'] + $userAccount['reward_money']),2,false));
            $this->display();
        }
        else $this->display("rechargeCorp");
	}
	
	//提交充值
	public function submit()
    {

        try {
            $payType = $this->_post('payType');//选择支付类型
            $amount = $this->_post('amount'); //金额 元
            $bankCode = $this->_post('bankCode'); //如果是银行支付，银行code
            $bankName = $this->_post('bankName'); //如果是银行名称
            $out_account_no = $this->_post('out_account_no'); //银行账号, 一般不传
            $out_account_id = $this->_post('out_account_id'); //银行账号, 一般不传
            $bak = $this->_post('bak'); //备注
            $remark = $this->_post('remark');//流水备注
            $authCode = $this->_post('authCode');
            $smsCode = $this->_post('smsCode');//银行短信
            $is_zs = $this->_post('is_zs');//是否浙商

            if(empty($payType)){
                throw_exception("支付类型选择错误，请返回刷新页面后重试");
            }
            $paymentParams = array();
            $payment = service('Payment/Payment')->init($payType);
			$paymentParams['is_zs'] = $is_zs ? 1 : 0;
            if(!is_numeric($amount)) {
                throw_exception("金额请输入数字格式");
            }

            if(!$amount) {
                throw_exception("请输入大于0的金额");
            }
            $user_account = M('user_account')->find((int)$this->loginedUserInfo['uid']);

            $user = M('user')->find((int)$this->loginedUserInfo['uid']);
            if($user_account['is_merchant'] != 1) {
                if (!$user['real_name'] || !$user['person_id'] || !$user['is_id_auth']) {
                    $result = array('boolen' => 0, 'message' => '请先实名认证');
                    throw_exception('请先实名认证');
                }
            }


            if(bcmul($amount, '100', 0) > PaymentService::$maxCashoutAmount) {
                throw_exception('充入的金额过大不支持');
            }

			if ($payType != 'zheshang') {
			    if ($payType == PaymentService::TYPE_XIANXIA && !check_verify($authCode)) {
				    throw_exception('验证码输入错误');
			    }
			}
			if ($payType == 'zheshang') {
				if ($this->loginedUserInfo['user_limit'] < $amount ) {
					throw_exception('超出当日可充值金额');
				}
				if ($this->loginedUserInfo['money_single'] < $amount) {
					throw_exception('超出单笔可充值金额');
				}
			}
			//加入浙商充值逻辑
			if ($payType == 'zheshang' && $user['zs_status'] == 2) {
				//浙商频次控制
				$out_account_id = M('recharge_bank_no')->where(array('uid'=>$this->loginedUserInfo['uid'], 'zs_bankck'=>1))->getField('id');
				service('Payment/PayAccount')->zsRechargeFreq($this->loginedUserInfo['uid'], $amount );
				$pay_account_model = D("Payment/PayAccount");
				$userAccount = $pay_account_model->find($this->loginedUserInfo['uid']);
				$paymentParams['bindSerialNo'] = $user['zs_bind_serial_no'];
				$paymentParams['smsCode'] = $smsCode;
			}elseif ($payType == 'zheshang' && $user['zs_status'] == 0) {
				throw_exception('还未开通浙商存管帐号');
			}

            $myBankCode = $payment->getMyCode($bankCode);

            if($out_account_id){
                $fund_account = M('recharge_bank_no')->where("id=".$out_account_id)->find();
                if($fund_account){
                    $myBankCode = $fund_account['bank'];//线下里的银行list code
                    $out_account_no = $fund_account['account_no'];
                } else {
                    $out_account_id = '';
                }
            }

            $user = M("user"); // 实例化User对象

            $cacheKey = "payaccountAction_submit_".$this->loginedUserInfo['uid'];
            if(cache($cacheKey) == time()){
                throw_exception('异常操作, 刷频太频了');
            }
            cache($cacheKey, time(), array('expire'=>10));
            // 手动进行令牌验证
// 		if (!$user->autoCheckToken($_POST)){
// 			$result = array('boolen'=>0, 'message'=>'异常操作');
// 			showJson($result);
// 		}

            if($bankCode){
                $paymentParams['bankCode'] = $bankCode;
                $list = $payment->getBankList();
                foreach($list as $bankParam){
                    if($bankParam['code'] == $bankCode){
                        $paymentParams['bankName'] = $bankParam['name'];
                    }
                }
            } else if($bankName){
                $paymentParams['bankName'] = $bankName;
            }
            if($bak) $paymentParams['bak'] = $bak;
            if($remark) {
				$paymentParams['remark'] = $remark;
			}else{//摘要
				$paymentParams['remark'] = $paymentParams['bankName'].' 尾号'.substr($out_account_no,-4).' 充值'.humanMoney(bcmul($amount, '100', 0),2);
			}

            $result = $payment->submit(bcmul($amount, '100', 0), $this->loginedUserInfo['uid'], $myBankCode, 0, $out_account_no, $out_account_id, $paymentParams);
			//下线充值发送短信
            if($result){
				if ($this->loginedUserInfo['uid_type'] == 2){
				    $comment = "【鑫合汇】您的充值申请已提交，请在24小时内网银转账至以下账户，不支持现金汇款及银行柜台转账。账户名=杭州鑫合汇互联网金融服务有限公司。账号=3300 1613 5350 5302 0478。开户银行=中国建设银行浙江省分行营业部";
				    import_addon('libs.Sms.Sms');
				    Sms::send($this->loginedUserInfo['mobile'], $comment, "XIANXIA", $this->loginedUserInfo['uid']);
				}
                showJson($result);
            }
        } catch (Exception $e) {
            D("Admin/AccountExceptLog")->insertLog($this->loginedUserInfo['uid'], "PayAccount/submit", "Payment/Payment", "submit", $e->getMessage());
            $result = array('boolen'=>0, 'message'=>$e->getMessage());
//            $this->error($e->getMessage());
            showJson($result);
        }
	}

	//浙商转账提交充值
	public function zsZhuanZhangSubmit()
	{

		try {
			$amount = $this->_post('amount');
//			$out_account_no = $this->_post('out_account_no'); //银行账号, 一般不传
			$paymentParams = array();
			$payment = service('Payment/Payment')->init('zheshang');
			
			if(!is_numeric($amount)) {
				throw_exception("金额请输入数字格式");
			}

			if(!$amount) {
				throw_exception("请输入大于0的金额");
			}

			$fund_account = M('recharge_bank_no')->where( array('uid'=>$this->loginedUserInfo['uid'], 'zs_bankck'=>1) )->find();
			if($fund_account){
				$out_account_id = $fund_account['id'];
				$myBankCode = $fund_account['bank'];
				$out_account_no = $fund_account['account_no'];
				$bankName = $fund_account['bank_name'];
			} else {
				throw_exception("未找到对应银行卡");
			}
			$user_account = M('user_account')->find((int)$this->loginedUserInfo['uid']);

			$user = M('user')->find((int)$this->loginedUserInfo['uid']);
			if($user_account['is_merchant'] != 1) {
				if (!$user['real_name'] || !$user['person_id'] || !$user['is_id_auth']) {
					$result = array('boolen' => 0, 'message' => '请先实名认证');
					throw_exception('请先实名认证');
				}
			}


			if(bcmul($amount, '100', 0) > PaymentService::$maxCashoutAmount) {
				throw_exception('充入的金额过大不支持');
			}


			$user = M("user"); // 实例化User对象

			$cacheKey = "payaccountAction_zsZhuanZhangSubmit_".$this->loginedUserInfo['uid'];
			if(cache($cacheKey) == time()){
				throw_exception('异常操作, 刷频太频了');
			}
			cache($cacheKey, time(), array('expire'=>10));
			$paymentParams['bankName'] = $bankName;
			$paymentParams['remark'] = $paymentParams['bankName'].' 尾号'.substr($out_account_no,-4).' 充值'.humanMoney(bcmul($amount, '100', 0),2);
			$paymentParams['zsXianXia'] = 1;//浙商线下转账
			$result = $payment->submit(bcmul($amount, '100', 0), $this->loginedUserInfo['uid'], $myBankCode, 0, $out_account_no, $out_account_id, $paymentParams);
			if($result){
				showJson($result);
			}
		} catch (Exception $e) {
			D("Admin/AccountExceptLog")->insertLog($this->loginedUserInfo['uid'], "PayAccount/zsZhuanZhangSubmit", "Payment/Payment", "zsZhuanZhangSubmit", $e->getMessage());
			$result = array('boolen'=>0, 'message'=>$e->getMessage());
//			$this->error($e->getMessage());
            showJson($result);
		}
	}

	//前端回调
	public function callBackReturn(){
		$request['ticket_no'] = $this->_request('ticket_no');//ticket_no
		$request['is_ajax'] = $this->_request('is_ajax');//is_ajxa
		$ticket = D('Payment/Ticket')->where(array('ticket_no'=>$request['ticket_no']))->find();
		if(!$ticket) throw_exception("参数异常");
		
		if ($ticket['mi_no']=='1234567890') {
    		$payment = service('Payment/Payment');
    		$payment = $payment->init($ticket['channel']);
    		$param = $payment->parseReturnParam($request);
    		$result = $payment->callBackReturn($param['ticket_no'], $ticket['uid']);
			
			if ($result['ticket']['status'] == 2) {
				$lottery_service = service('Mobile/LotteryGehk');
				$ymd = date('Ymd');
				if ($ymd >= LotteryGehkService::START_TIME && $ymd <= LotteryGehkService::END_TIME) {
//					$result['show_pop_layer'] = 1;
					$result['lottery_chance'] = floor($ticket['amount'] / 200000);
					$result['amount_show'] = humanMoney($ticket['amount'], 2, false);
				}
			}
			
    		$this->result = $result;
    		if($request['is_ajax']){
    			exit(json_encode($result));
    		}
    		$this->display();
		}
		else {
		    $member=M('member')->select();
		    foreach ($member as $m)
		    {
		        if ($m['mi_no']==$ticket['mi_no'])
		        {
		            $domain=$m['mi_host'];
		            break;
		        }
		    }
		    if(!$this->is_gov) $domain='';//2015.3.3 二级域名是政府
		    $url='Location: '.$domain.'/Payment/PayAccount/callBackReturn?ticket_no='.$request['ticket_no'];
		    header($url);
		}
	}
	//后端回调
	public function callBackNotify(){
		$payType = $this->_request('payType');//选择支付类型
		$payment = service('Payment/Payment');
		if(!$payType) $payType = PaymentService::TYPE_SHENFUT;
		$payment = $payment->init($payType);
		
// 		$_POST['TransNo'] = '20131023163426454751';
// 		$_POST['PaymentNo'] = '20131023163442194914';
// 		$_POST['OrderAmount'] = '0.01';
// 		$_POST['TransTime'] = '20131023163426';
// 		$_POST['SendTime'] = '20131023163443';
// 		$_POST['TraceNo'] = 'db7ecb23-0f20-42ed-8d4c-f4dfb23ac64d';
// 		$_POST['TransAmount'] = '0.01';
// 		$_POST['Charset'] = 'UTF-8';
// 		$_POST['OrderNo'] = 'OUTTK201310230000012';
// 		$_POST['TransType'] = 'PT002';
// 		$_POST['PayableFee'] = '0.00';
// 		$_POST['Version'] = 'V4.1.2.1.1';
// 		$_POST['Ext1'] = '';
// 		$_POST['Ext2'] = '';
// 		$_POST['TransStatus'] = '01';
// 		$_POST['PayChannel'] = '14';
// 		$_POST['SignType'] = 'MD5';
// 		$_POST['ReceivableFee'] = '0.00';
// 		$_POST['Name'] = 'REP_B2CPAYMENT';
// 		$_POST['InstCode'] = 'SNDA';
// 		$_POST['MerchantNo'] = '100894';
// 		$_POST['MsgSender'] = 'SFT';
// 		$_POST['SignMsg'] = '0A0301B1F4AAE1BDB99C1353C9A2AF18';
// 		$_POST['ErrorMsg'] = '';
// 		$_POST['ErrorCode'] = '';
		
 		$param = $payment->parseNotifyParam($_REQUEST);
 		try{
			$payment->callBackNotify($param);
 		} catch(Exception $e){
 			sleep(2);
 			$payment->callBackNotify($param);
 		}
	}

	/*
	 * 浙商前端回调
	 */

	public function zscallbackReturn() {
		$uid = (int)$this->loginedUserInfo['uid'];
		$SerialNo = $this->_request('serialNo')?$this->_request('serialNo'):'';
		$ticket_id = $this->_request('ticket_id');
		try{
			$res = D('Payment/Ticket')->where(array('id' => $ticket_id, 'uid' => $uid))->find();
			if (!$res) {
				throw_exception("权限异常");
			}
			$result = array('boolen'=>1, 'message'=>'已充值成功','ticket_no'=>$res['ticket_no']);
			if($res['status'] == 2 || $res['status'] == 3 || $res['channel'] == 'zheshangx'){
				showJson($result);
			}
			$payType = "zheshang";
			$payment = service('Payment/Payment')->init($payType);
			$input = array(
				'SerialNo' => $SerialNo,
				'tradeType' => 1,
				'ticket_no' =>$res['ticket_no'],
				'uid'=>$uid
			);
			$payment->callBackNotify($input, $is_mobile=0);
		}catch (Exception $e){
			$result['message'] = '异常';
		}
		showJson($result);

	}
	
	//转出支付余额资金页面
	public function showRollout(){
		$account = M("user_account")->where(array("uid"=>$this->loginedUserInfo['uid']))->find();
		if($account['is_merchant'] != '1'){
			throw_exception("只有商户账户才能使用该页面");
		}
		$this->account = $account;
		$this->repay_freeze_view = humanMoney($account['repay_freeze_money'],2,false);
		$this->display();
	}
	
	//转出支付余额资金
	public function rollout(){
		$money = $this->_post('money'); //金额 元
		if(!is_numeric($money)) {
			$result = array('boolen'=>0, 'message'=>"金额请输入数字格式");
			showJson($result);
		}
		if(!$money) {
			$result = array('boolen'=>0, 'message'=>"请输入大于0的金额");
			showJson($result);
		}
		$account = M("user_account")->where(array("uid"=>$this->loginedUserInfo['uid']))->find();
		if($account['is_merchant'] != '1'){
			$result = array('boolen'=>0, 'message'=>"只有商户账户才能使用该页面");
			showJson($result);
		}
        $money = bcmul($money, '100', 0);
		if($account['repay_freeze_money'] < $money){
			$result = array('boolen'=>0, 'message'=>"冻结还款支付余额不足");
			showJson($result);
		}
		$out_order_no = "rollout".$this->loginedUserInfo['uid']."_".time();
		$result = service('Payment/PayAccount')->rollout($this->loginedUserInfo['uid'], $out_order_no, $money);
		showJson($result);
	}
	
	//提现申请初始化页面
	public function getApplyCashout(){
		$account_list = M('fund_account')->where("uid=".$this->loginedUserInfo['uid']." and zs_bankck=0")->select();
//		if(!$account_list){
//			$this->redirect("Payment/PayAccount/getFundAccount", array("url"=>url_encode(getCurrentUrl()).'&cashout=1'));
//		}

        $cashoutAmount = service("Payment/PayAccount")->getTCashoutAmount((int)$this->loginedUserInfo['uid']);
        // 去除中国邮政储蓄银行
        foreach ($account_list as $k => $v) {
            $account_list[$k]['cashoutAmount'] = 0;
//            if($v['bank'] == 'PSBC') unset($account_list[$k]);
//            else {
                $mcashoutAmount = service("Payment/PayAccount")->getMCashoutAmountByNo($v['id']);
                $account_list[$k]['cashoutAmount'] = $cashoutAmount + $mcashoutAmount;
//            }
        }

		$yb_arrs = service('Account/AccountBank')->ybBankArrs();
		$cashout_arrs = service('Account/AccountBank')->AvailableCashOutCard();
        //判断银行卡是否支持自动提现
		$obj = D('Account/UserAccount');
		foreach ($account_list as $key => $value){
			$account_list[$key]['autocashout'] = $obj->isBankCardInfoFull($this->loginedUserInfo['uid'], array($value['bank'], $value['sub_bank_id'], $value['bank_province'], $value['bank_city']));
            $account_list[$key]['sub_bank_status'] = 2;
            if(!$value['sub_bank'] || !$value['sub_bank_id']){
                $account_list[$key]['sub_bank_status'] = 1;//需要重新维护分支行信息
            }
            $account_list[$key]['bind_status'] = 1;
            if( $account_list[$key]['is_bankck'] == 1 && in_array($account_list[$key]['bank'], $yb_arrs)){
                $account_list[$key]['bind_status'] = 2;
            }
			//不支持提现的银行卡不再显示该卡
			if (!in_array($account_list[$key]['bank'], $cashout_arrs)) {
				unset($account_list[$key]);
			}
        }
		$this->assign("is_paypwd_edit", 1);//20160309 是否设置支付密码

		$not_bind_card = 0;

		if (empty($account_list)) {
			$not_bind_card = 1;
		}
		$this->account_list = $account_list;
		$this->real_name = $this->loginedUserInfo['real_name'];
		$this->usc_token = $this->loginedUserInfo['usc_token'];
        $this->is_init = $this->loginedUserInfo['is_init'];
        $this->uid_type = $this->loginedUserInfo['uid_type'];
        $questions = M("code_item")->where("code_key=E036")->select();
        $this->assign("questions", $questions);

		$user_account = M('user_account')->find((int)$this->loginedUserInfo['uid']);

		$this->free_tixian_times = $user_account['free_tixian_times'];
		$this->amount = $user_account['amount'];
		$this->reward_money = $user_account['reward_money'];
		$this->free_money = $user_account['free_money'];

		$user_college_ext = M('user_college_ext')->find((int)$this->loginedUserInfo['uid']);
        if($user_college_ext && $user_college_ext['coll_amount']){
			$this->amount = $user_account['amount'] - $user_college_ext['coll_amount'];
        }
		$user_status = service("Account/Account")->userStatus($this->loginedUserInfo);
		$this->assign("userStatus",$user_status);
        $questions = M("code_item")->where("code_key='K001'")->select();
        $this->assign("questions", $questions);
		$this->assign('rearch_time', date("Y年n月j日", time()+86400));
		$this->assign("is_paypwd_edit", $this->loginedUserInfo['is_paypwd_edit'] || $user_account['pay_password']);//20160309 是否设置支付密码

		$this->assign('not_bind_card', $not_bind_card);
		$this->display();

	}
	
	//申请提现
	public function applyCashout(){
		$money = $this->_post('money');//申请金额
		$use_reward_money = (int)$this->_post('use_reward_money');//可提现奖励金额
		$bank = $this->_post('bank');//线下里的银行list myCode
		$bank_name = $this->_post('bank_name');
		$bank_no = $this->_post('bank_no');//结尾4位
		$sub_bank = $this->_post('sub_bank');//支行
		$bak = $this->_post('bak');//
		$out_account_no = $this->_post('out_account_no');//银行账户
		$payPwd = $_REQUEST['payPwd'];//支付密码
		$out_account_id = (int)$this->_post('out_account_id');//银行账户id
		$free_times = $this->_post('free_times');//是否使用提现的次数
// 		$authCode = $this->_post('authCode');
		$code = $this->_post('code');
		service('Payment/Payment');
		$channel = PaymentService::TYPE_XIANXIA;//默认提现方式是线下的

		if($money > PaymentService::$maxCashoutAmount) {
			$result = array('boolen'=>0, 'message'=>'金额过大，不支持');
			showJson($result);
		}
		$user = M('user')->find((int)$this->loginedUserInfo['uid']);
		if(!$user['real_name'] || !$user['person_id'] || !$user['is_id_auth']){
			$result = array('boolen'=>0, 'message'=>'请先实名认证');
			showJson($result);
		}
		// 手动进行令牌验证
// 		$userModel = M("user"); // 实例化User对象
// 		if (!$userModel->autoCheckToken($_POST)){
// 			$result = array('boolen'=>0, 'message'=>'异常操作');
// 			showJson($result);
// 		}
		$user_account = M('user_account')->find((int)$this->loginedUserInfo['uid']);
		if(!$user_account['pay_password']){
			$result = array('boolen'=>0, 'message'=>'请先去设置支付密码');
			showJson($result);
		}
		
		if(!D('Account/UserAccount')->checkOldPwd((int)$this->loginedUserInfo['uid'], $payPwd)){
			$result = array('boolen'=>0, 'message'=>'支付密码不正确');
			showJson($result);
		}
		/* 检测验证码 */
// 		if(!check_verify($authCode)){
// 			$this->error('验证码输入错误！');
// 		}

		if(!service("Account/Validate")->validateCashout2MobileCode($this->loginedUserInfo['mobile'],$code)){
			$result = array('boolen'=>0, 'message'=>MyError::lastError());
			showJson($result);
		}
		if(service('Payment/PayAccount')->getFreeTixianTimes((int)$this->loginedUserInfo['uid'])>0){
			$cash_fee = 0;
			$free_tixian_times = 1;
		} else {
			$cash_fee = CASHOUT_CASH_FEE;
			$free_tixian_times = 0;
		}
		if($money < $cash_fee/100){
			$result = array('boolen'=>0, 'message'=>'申請必须大于手续费!');
// 			$result = array('boolen'=>0, 'message'=>'申請必须大于100元!');
			showJson($result);
		}
		
		$money = bcmul($money, '100', 0);
// 		$money = $money * 100;//转成分
// 		$money = (string)$money;
// 		$money = (int)$money;
		$money = (int)$money;
		$reward_money = 0;
		if($use_reward_money){
			if($money >= $user_account['reward_money']){
				$reward_money = $user_account['reward_money'];
			} else {
				$reward_money = $money;
			}
		}

		if($reward_money > $money){
			$result = array('boolen'=>0, 'message'=>'奖励金额大于金额，异常');
			showJson($result);
		}
		if(!(int)$money){
			$result = array('boolen'=>0, 'message'=>'请输入正确的金额');
			showJson($result);
		}
		if(!$out_account_id){
			$result = array('boolen'=>0, 'message'=>'请选择银行');
			showJson($result);
		}
		
        $fund_account = M('fund_account')->where("id=".$out_account_id)->find();
        if( !$fund_account ){
            $result = array('boolen'=>0, 'message'=>'请选择银行');
            $this->showJson($result);
        }
        $kj_card = 0;
        //判断是否快捷卡
        if($fund_account && $fund_account['is_bankck'] == 1 ){
			$ybBankArrs = service('Account/AccountBank')->ybBankArrs();
            if(in_array($fund_account['bank'], $ybBankArrs)){
                $kj_card = 1;
            }
        }
        //非内部账户
        //企业用户单笔限额5000万元，企业用户无需鉴权认证；
        if( $user['is_init'] !=1 && $user['uid_type'] == 2 && $money > CASHOUT_LIMIT_COM_MONEY){
            $result = array('boolen'=>0, 'message'=>'超过该卡单次提现上限'. CASHOUT_LIMIT_COM_MONEY/1000000 .'万元');
            showJson($result);
        }
        //个人用户已鉴权和未鉴权提现上限都设置为单笔50万元
        if( $user['is_init'] !=1 && $user['uid_type'] == 1 && $money > CASHOUT_LIMIT_PER_MONEY){
            $result = array('boolen'=>0, 'message'=>'超过该卡单次提现上限'. CASHOUT_LIMIT_PER_MONEY/1000000 .'万元');
            showJson($result);
        }

//        //个人用户未鉴权提现上限也是单笔50万元
//        if( $user['is_init'] !=1 && $user['uid_type'] == 1 && !$kj_card && $money > 50000000){
//            $result = array('boolen'=>0, 'message'=>'超过该卡单次提现上限50万元');
//            showJson($result);
//        }
        if($fund_account){
            $bank = $fund_account['bank'];//线下里的银行list code
            $sub_bank = $fund_account['sub_bank'];//线下里的银行list code
            $out_account_no = $fund_account['account_no'];
        } else {
            $out_account_id = '';
        }

		$free_times = 0; //已经没有免费次数概念了 todo 
// 		$money = $money - $cash_fee;
		if($free_times){ //计算提现费率等 应该移动到service里的
			$free_money = 0;
			$fee = 0;
			$fee += $cash_fee;
			if($money > $user_account['free_money']){
				$cost_saving = bcmul(($money - $free_money), CASHOUT_FEE_PRE, 0);
			}
			$fee = (int)$fee;
			$money = $money - $fee;
		} else {
			if($user_account['free_money']){
				if($money > $user_account['free_money']){
					$free_money = (int)$user_account['free_money'];
					$fee = bcmul(($money - $free_money), CASHOUT_FEE_PRE, 0);
					$fee = ceil($fee);
					$fee = (int)$fee;
					$fee += $cash_fee;
					$money = $money - $fee;
				} else {
					$free_money = $money;
					$fee = 0;
					$fee += $cash_fee;
					$fee = (int)$fee;
					$money = $money - $fee;
				} 
			} else {
				$free_money = 0;
				$fee = bcmul($money, CASHOUT_FEE_PRE, 0);
				$fee = ceil($fee);
				$fee = (int)$fee;
				$fee += $cash_fee;
				$money = $money - $fee;
			}
		}
		if($money<=0){
			$result = array('boolen'=>0, 'message'=>'申请提现金额不足支付(提现转账费用+手续费)');
			showJson($result);
		}
// 		$fee += $cash_fee;
		try{
			$result = service('Payment/PayAccount')->applyCashout($this->loginedUserInfo['uid'], 
				$money, $fee, $reward_money, $channel, $bank, $sub_bank, $bak, $free_times, $free_money, $cost_saving, $out_account_no, $out_account_id, $free_tixian_times, 1, $user['is_init']);
			if ($result['boolen'] == 1 && $user['is_init'] != 1 && $user['uid_type'] != 2) {
				service('Account/AccountBank')->ybQueue(array('bank' => $bank, 'out_account_no' => $out_account_no, 'uid' => $this->loginedUserInfo['uid'], 'amount' => $money, 'cashout_id' => $result['cashoutId']));
            }
            showJson($result);
		}catch(Exception $e){
			D("Admin/AccountExceptLog")->insertLog($this->loginedUserInfo['uid'], "PayAccount/applyCashout", "Payment/PayAccount", "applyCashout", $e->getMessage());
			showJson(array("boolen"=>0, "message"=>$e->getMessage()));
		}
	}

	//浙商提现申请初始化页面
	public function zsGetApplyCashout(){
		$user_status = service("Account/Account")->userStatus($this->loginedUserInfo);
		$account_money = D('Payment/PayAccount')->accountInfo( $this->loginedUserInfo['uid'] );
		$bank = M('fund_account')->where("uid=".$this->loginedUserInfo['uid']." and zs_bankck=1")->find();
		$cashout_limit = service('Payment/PayAccount')->getPayment( 'cashout_limit' );
		$bank = array(
			'bank_no'=>substr($bank['account_no'], -4),
			'bank_code'=>$bank['bank'],
			'bank_name'=>$bank['bank_name'],
			'mobile'=>substr_replace($bank['mobile'],'****',3,4)
		);
		$bank_channel =  D('Mobile2/Bank')->getzsBankdesc($bank['bank_code']);
		$this->assign("userStatus",$user_status);
		$this->assign('reach_time1', date("Y年n月j日24点"));
		$this->assign('reach_time2', date("Y年n月j日24点", time()+86400));
		$this->assign('account_money', $account_money);
		$this->assign('bank', $bank);
		$this->assign('cashout_limit', $cashout_limit);
		$this->assign('bank_channel', $bank_channel);//包含logo
		$this->display();
	}
	
	//浙商申请提现
	public function zsApplyCashout(){
		$money = $this->_post('money');//申请金额
		$use_reward_money = 1;//(int)$this->_post('use_reward_money');//可提现奖励金额
		$bank = $this->_post('bank');//线下里的银行list myCode
		$bank_name = $this->_post('bank_name');
		$bank_no = $this->_post('bank_no');//结尾4位
		$sub_bank = $this->_post('sub_bank');//支行
		$bak = $this->_post('bak');//
		$out_account_no = $this->_post('out_account_no');//银行账户
		$payPwd = $_REQUEST['payPwd'];//支付密码
		$out_account_id = (int)$this->_post('out_account_id');//银行账户id
		$free_times = $this->_post('free_times');//是否使用提现的次数
		$code = $this->_post('code');//手机验证码
		$cacheKey = $this->loginedUserInfo['uid'] . $code ;
		service('Payment/Payment');
		$channel = PaymentService::TYPE_ZHESHANG;
		if( !$code ){
			$result = array('boolen'=>0, 'message'=>'请输入提现短信验证码');
			showJson($result);
		}
		if(cache($cacheKey) == '1'){
			$result = array('boolen'=>0, 'message'=>'请勿频繁操作');
			showJson($result);
			exit;
		}
		cache($cacheKey, '1', array('expire'=>60));
		if($money > PaymentService::$maxCashoutAmount) {
			$result = array('boolen'=>0, 'message'=>'金额过大，不支持');
			showJson($result);
		}
		$user = M('user')->find((int)$this->loginedUserInfo['uid']);
		if(!$user['real_name'] || !$user['person_id'] || !$user['is_id_auth']){
			$result = array('boolen'=>0, 'message'=>'请先实名认证');
			showJson($result);
		}

		$user_account = M('user_account')->find((int)$this->loginedUserInfo['uid']);
		if(!$user_account['pay_password']){
			$result = array('boolen'=>0, 'message'=>'请先去设置支付密码');
			showJson($result);
		}

//		if(!D('Account/UserAccount')->checkOldPwd((int)$this->loginedUserInfo['uid'], $payPwd)){
//			$result = array('boolen'=>0, 'message'=>'支付密码不正确');
//			showJson($result);
//		}
		if(service('Payment/PayAccount')->getFreeTixianTimes((int)$this->loginedUserInfo['uid'])>0){
			$cash_fee = 0;
			$free_tixian_times = 0;//暂时不收免费次数
		} else {
			$cash_fee = ZS_CASHOUT_CASH_FEE;
			$free_tixian_times = 0;
		}
		if($money < $cash_fee/100){
			$result = array('boolen'=>0, 'message'=>'申請必须大于手续费!');
			showJson($result);
		}

		$money = bcmul($money, '100', 0);
		$money = (int)$money;
		$reward_money = 0;
		if($use_reward_money){
			if($money >= $user_account['zs_reward_money']){
				$reward_money = $user_account['zs_reward_money'];
			} else {
				$reward_money = $money;
			}
		}

		if($reward_money > $money){
			$result = array('boolen'=>0, 'message'=>'奖励金额大于金额，异常');
			showJson($result);
		}
		if(!(int)$money){
			$result = array('boolen'=>0, 'message'=>'请输入正确的金额');
			showJson($result);
		}

		$fund_account = M('fund_account')->where( array('uid'=>$this->loginedUserInfo['uid'],'zs_bankck'=>1) )->find();
		if( !$fund_account ){
			$result = array('boolen'=>0, 'message'=>'存管银行不存在');
			$this->showJson($result);
		}else{
			$bank = $fund_account['bank'];
			$sub_bank = $fund_account['sub_bank'];
			$out_account_no = $fund_account['account_no'];
			$out_account_id = $fund_account['id'];
		}
		$kj_card = 1;
		$cashout_limit = service('Payment/PayAccount')->getPayment( 'cashout_limit' );
		$company_limit = bcmul($cashout_limit['company'], '100', 0);
		$person_limit = bcmul($cashout_limit['person'], '100', 0);
		//企业用户单笔限额5万元，企业用户无需鉴权认证；
		if( $user['is_init'] !=1 && $user['uid_type'] == 2 && $money > $company_limit){
			$result = array('boolen'=>0, 'message'=>'超过该卡单次提现上限'. $company_limit/1000000 .'万元');
			showJson($result);
		}
		//个人用户已鉴权和未鉴权提现上限都设置为单笔50万元
		if( $user['is_init'] !=1 && $user['uid_type'] == 1 && $money > $person_limit){
			$result = array('boolen'=>0, 'message'=>'超过该卡单次提现上限'. $person_limit/1000000 .'万元');
			showJson($result);
		}

		$free_times = 0; //已经没有免费次数概念了 todo
// 		$money = $money - $cash_fee;
		if($free_times){ //计算提现费率等 应该移动到service里的
			$free_money = 0;
			$fee = 0;
			$fee += $cash_fee;
			if($money > $user_account['free_money']){
				$cost_saving = bcmul(($money - $free_money), ZS_CASHOUT_FEE_PRE, 0);
			}
			$fee = (int)$fee;
			$money = $money - $fee;
		} else {
			if($user_account['free_money']){
				if($money > $user_account['free_money']){
					$free_money = (int)$user_account['free_money'];
					$fee = bcmul(($money - $free_money), ZS_CASHOUT_FEE_PRE, 0);
					$fee = ceil($fee);
					$fee = (int)$fee;
					$fee += $cash_fee;
					$money = $money - $fee;
				} else {
					$free_money = $money;
					$fee = 0;
					$fee += $cash_fee;
					$fee = (int)$fee;
					$money = $money - $fee;
				}
			} else {
				$free_money = 0;
				$fee = bcmul($money, ZS_CASHOUT_FEE_PRE, 0);
				$fee = ceil($fee);
				$fee = (int)$fee;
				$fee += $cash_fee;
				$money = $money - $fee;
			}
		}
		if($money<=0){
			$result = array('boolen'=>0, 'message'=>'申请提现金额不足支付(提现转账费用+手续费)');
			showJson($result);
		}

		try{
			$result = service('Payment/PayAccount')->applyCashout($this->loginedUserInfo['uid'],														
				$money, $fee, $reward_money, $channel, $bank, $sub_bank, $bak, $free_times, $free_money, $cost_saving, $out_account_no, $out_account_id, $free_tixian_times, 1, $user['is_init'],true,$code);
			if( $result['boolen'] == 1 ){
				$this->zsCashout(array('id'=>$result['cashoutId'],'money'=>$money, 'code'=>$code,'zs_bind_serial_no'=>$user['zs_bind_serial_no']));
			}
			showJson($result);
		}catch(Exception $e){
			D("Admin/AccountExceptLog")->insertLog($this->loginedUserInfo['uid'], "PayAccount/zsApplyCashout", "Payment/PayAccount", "zsApplyCashout", $e->getMessage());
			showJson(array("boolen"=>0, "message"=>$e->getMessage()));
		}
	}

	public function zsCashout( $data ){
		$cashout = M('cashout_apply')->field('cashout_process_no')->find( $data['id'] );
		$params = array(
			'bindSerialNo' => $data['zs_bind_serial_no'],
			'cashout_process_no' => $cashout['cashout_process_no'],
			'amount' => $data['money'],
			'smsCode' => $data['code'],//验证码
			'remark' => $data['cashout_process_no'] . '_提现'
		);
		import_app('Payment.Service.Payment.ZheShangPayment');
		$zheshang = new ZheShangPayment();
		$ret = $zheshang->cashoutMoney( $params );
		$out_order_id = '';
		if(isset($ret['data']['value']['bankSerialNo'])){
			$out_order_id = $ret['data']['value']['bankSerialNo'];
		}
		M('cashout_apply')->where(array('id' => $data['id']))->save(array('status' => 7, 'mac_channel'=>'zheshang','mac_time'=>time(),'out_order_id'=>$out_order_id));
	}
	
	//获取提现资金管理费
	public function getCashoutFee(){
		$money = $this->_request('money');//申请金额
		$zs = $this->_request('channel');
		$pre = '';
		if( $zs == PaymentService::TYPE_ZHESHANG ) {
			$pre = 'ZS_';
		}
		$money = bcmul($money, 100, 0);//转成分
		if(service('Payment/PayAccount')->getFreeTixianTimes((int)$this->loginedUserInfo['uid'])>0){
			$cash_fee = 0;
		} else {
			$cash_fee = $pre . CASHOUT_CASH_FEE;
		}
		$user_account = M('user_account')->find((int)$this->loginedUserInfo['uid']);
		if(!(int)$money){
			$result = array(array("boolen"=>0, "money"=>0, "cash_fee"=>$cash_fee/100, "fee"=>0
				,"free_money"=>$user_account['free_money']/100,"free_tixian_times"=>$user_account['free_tixian_times']));
			showJson($result);
		}
		if($user_account['free_money']){
			if($money > $user_account['free_money']){
				$free_money = $user_account['free_money'];
				$fee = bcmul(($money - $free_money), $pre . CASHOUT_FEE_PRE, 0);
// 				$last_money = $money - $free_money;
				$fee = (int)ceil($fee);
// 				$money = $money - $fee;
			} else {
				$free_money = $money;
				$fee = 0;
// 				$last_money = $money - $free_money;
			}
		} else {
			$free_money = 0;
			$fee = bcmul($money, $pre . CASHOUT_FEE_PRE, 0);
			$fee = (int)ceil($fee);
// 			$last_money = $money - $free_money;
// 			$money = $money - $fee;
		}
		$money = $money - $fee - $cash_fee;
		showJson(array("boolen"=>1, "money"=>$money/100, "cash_fee"=>$cash_fee/100, "fee"=>$fee/100
			,"free_money"=>$user_account['free_money']/100,"free_tixian_times"=>$user_account['free_tixian_times']));
	}
	
	//处理提现申请
// 	public function dealCashoutApply(){
// 		$cashoutId = (int)$this->_post('cashoutId');//提现申请id
// 		$dealStatus = $this->_post('dealStatus');//处理结果状态
// 		$dealReason = $this->_post('dealReason');//处理结果理由
// 		try{
// 			$result = service('Payment/Cashout')->deal($cashoutId, $this->loginedUserInfo['uid'], $dealStatus, $dealReason);
// 			showJson($result);
// 		}catch(Exception $e){
// 			D("Admin/AccountExceptLog")->insertLog($this->loginedUserInfo['uid'], "PayAccount/dealCashoutApply", "Payment/Cashout", "deal", $e->getMessage());
// 			return showJson(array("boolen"=>0, "message"=>$e->getMessage()));
// 		}
// 	}
	
	//线下申请初始化页面
	public function xianxia(){
		$amount = $this->_post("amount");//金额 元
		if($amount && !is_numeric($amount)) {
			echo "参数异常，金额不是数字";
			exit;
		}
		$uid = $this->loginedUserInfo['uid'];
		$user = M("User")->find($uid);
		if($user['uid_type'] == 1 && $user['is_xia_recharge'] != 1){
			echo "页面不存在";
			exit;
		}
		$userAccount = D("Payment/PayAccount")->find($this->loginedUserInfo['uid']);
		if(!$userAccount) {
			echo "请先登入";
			exit;
		}
		if($userAccount['is_merchant'] == '1'){
// 			if($amount <= 0){
// 				echo "参数异常，充值金额需要大于0元";
// 				exit;
// 			}
		} else if($amount < service('Payment/PayAccount')->getMinMoney()){
			echo "参数异常，充值金额低于最低充值金额".service('Payment/PayAccount')->getMinMoney()."元";
			exit;
		}
		service('Payment/Payment');
		if((string)($amount*100) > PaymentService::$maxCashoutAmount) {
			echo "充入的金额过大不支持";
			exit;
		}
        $checkMoney = bcmul($amount, '100', 0);
//		$checkMoney = $amount * 100;
		$payment = service('Payment/Payment');
		$type = PaymentService::TYPE_XIANXIA;
		$payment = $payment->init($type);
		$this->bankList = $payment->getBankList();
		$this->amount = $amount; //元
		$this->ptAccountNo = '3300 1613 5350 5302 0478';
		$this->subBank = '中国建设银行浙江省分行营业部';
		$this->ptAccountName = '杭州鑫合汇互联网金融服务有限公司';
		$this->is_init = $this->loginedUserInfo['is_init'] ? 1 : 0;
		if($userAccount['is_merchant'] == '1'){
			$this->minMoney = 0;
		} else {
			$this->minMoney = service('Payment/PayAccount')->getMinMoney();
		}
		$bindBank = service("Account/Account")->getBindBanks($uid);
		foreach ($bindBank as &$v) {
			$ret = service("Account/AccountBank")->bindCardType($uid, $v['acount_name']);
			$v['bind_status'] = $ret['bind_status'];
			$v['tx_status'] = $ret['tx_status'];
			//            $v['bank'] = strtolower($v['bank']);
		}
		$this->bindBanks = $bindBank;
        $this->assign('role',$this->loginedUserInfo['role']);
		$this->assign('mobile',$this->loginedUserInfo['mobile']);
		$this->display();
	}
    public function xianxia1(){
        $amount = $this->_post("amount");//金额 元
        if($amount && !is_numeric($amount)) {
            echo "参数异常，金额不是数字";
            exit;
        }
        $uid = $this->loginedUserInfo['uid'];
        $user = M("User")->find($uid);
        if($user['uid_type'] == 1 && $user['is_xia_recharge'] != 1){
            echo "页面不存在";
            exit;
        }
        $userAccount = D("Payment/PayAccount")->find($this->loginedUserInfo['uid']);
        if(!$userAccount) {
            echo "请先登入";
            exit;
        }
        if($userAccount['is_merchant'] == '1'){
// 			if($amount <= 0){
// 				echo "参数异常，充值金额需要大于0元";
// 				exit;
// 			}
        } else if($amount < service('Payment/PayAccount')->getMinMoney()){
            echo "参数异常，充值金额低于最低充值金额".service('Payment/PayAccount')->getMinMoney()."元";
            exit;
        }
        service('Payment/Payment');
        if((string)($amount*100) > PaymentService::$maxCashoutAmount) {
            echo "充入的金额过大不支持";
            exit;
        }
        $checkMoney = bcmul($amount, '100', 0);
//		$checkMoney = $amount * 100;
        $payment = service('Payment/Payment');
        $type = PaymentService::TYPE_XIANXIA;
        $payment = $payment->init($type);
        $this->bankList = $payment->getBankList();
        $this->amount = $amount; //元
        $this->ptAccountNo = '3300 1613 5350 5302 0478';
        $this->subBank = '中国建设银行浙江省分行营业部';
        $this->ptAccountName = '杭州鑫合汇互联网金融服务有限公司';
        $this->is_init = $this->loginedUserInfo['is_init'] ? 1 : 0;
        if($userAccount['is_merchant'] == '1'){
            $this->minMoney = 0;
        } else {
            $this->minMoney = service('Payment/PayAccount')->getMinMoney();
        }
        $bindBank = service("Account/Account")->getBindBanks($uid);
        foreach ($bindBank as &$v) {
            $ret = service("Account/AccountBank")->bindCardType($uid, $v['acount_name']);
            $v['bind_status'] = $ret['bind_status'];
            $v['tx_status'] = $ret['tx_status'];
            //            $v['bank'] = strtolower($v['bank']);
        }
        $this->bindBanks = $bindBank;
        $this->assign('role',$this->loginedUserInfo['role']);
        $this->assign('mobile',$this->loginedUserInfo['mobile']);
        $this->display();
    }
	//获取线下数据
	public function getXianxia(){
		$id = (int)$this->_request("id");//金额 元
		$xianxia = D("Payment/XianxiaRecharge")->find((int)$id);
		if(!$xianxia) showJson(array("boolen"=>0, "message"=>"数据不存在"));
		if($xianxia['uid'] != $this->loginedUserInfo['uid']) showJson(array("boolen"=>0, "message"=>"没有权限"));
		$xianxia['real_amount'] = $xianxia['real_amount']/100;
		$xianxia['apply_amount'] = $xianxia['apply_amount']/100;
		$payment = service('Payment/Payment');
		$type = PaymentService::TYPE_XIANXIA;
		$payment = $payment->init($type);
		$this->bankList = $payment->getBankList();
		$this->data = $xianxia;
		$this->assign('id', $id);
		$this->display();
	}
	
	//修改线下申请
	public function saveXianxia(){
		$id = (int)$this->_post("id");//金额 元
		$bank = $this->_post("bank"); //银行code
		$bank_name = $this->_post("bank_name");//银行名称
		$account_no = $this->_post("account_no");//账户
		$account_id = $this->_post("account_id");//账户id(暂时不用)
		$apply_amount = $this->_post("apply_amount");//申请金额 元
        $apply_amount = bcmul($apply_amount, '100', 0);
		$bak = $this->_post("bak"); //备注
		
		$user = M("User")->find($this->loginedUserInfo['uid']);
		if(!$id && $user['is_xia_recharge'] != 1){
			throw_exception("线下充值功能已经下架");
		}
		
		if(!is_numeric($apply_amount)) {
			throw_exception("金额请输入数字格式");
		}
		if(!$apply_amount) {
			throw_exception("请输入大于0的金额");
		}
		
		$payment = service('Payment/Payment')->init(PaymentService::TYPE_XIANXIA);
		$myBankCode = $payment->getMyCode($bank);
		if($bank){
			$paymentParams['bankCode'] = $bank;
			$list = $payment->getBankList();
			foreach($list as $bankParam){
				if($bankParam['code'] == $bank){
					$bank_name = $bankParam['name'];
				}
			}
		}
		try{
			$res = D("Payment/XianxiaRecharge")->saveXianxia($id, $this->loginedUserInfo['uid'], $bank, $bank_name, $account_no, "", (int)$apply_amount, $bak);
			showJson($res);
		}catch(Exception $e){return showJson(array("boolen"=>0, "message"=>$e->getMessage()));}
	}
	
	//资金记录
	public function getMyMoneyList(){
		$showP = $this->_get("showP");
		if(!$showP) $showP = "getMyRecordList";
		$this->showP = $showP;
		$user_account = M('user_account')->find( $this->loginedUserInfo['uid'] );
		$move_status = 0;
		if($user_account['zs_moving_money'] > 0 || $user_account['zs_amount'] > 0 ){
			$move_status = 0;//是否显示余额转移 1显示 0关闭 这期先关闭
		}
		if(C('ACCOUNT_VIEW_NEW')){
			$this->assign('is_xia_recharge',$this->loginedUserInfo['is_xia_recharge']);//20160307 一般用户仅显示【成功】、【失败】，大额用户通过后台可以设置显示【待处理】、【跟踪处理中】
			$this->assign('move_status', $move_status );
			$this->display();
          }else{
			$this->assign('is_xia_recharge',$this->loginedUserInfo['is_xia_recharge']);//20160307 一般用户仅显示【成功】、【失败】，大额用户通过后台可以设置显示【待处理】、【跟踪处理中】
			$this->display('getMyMoneyList_old');
		}	
		//$this->display();
	}

    /**
     * 资金记录 - 提现收支记录
     */
    public function getMyRecordList()
    {
        $status = $this->_request('status');//状态 充值-1 投资-2 回款-3 提现-4  奖金-5 余额转移-9
        $start_time = $this->_request('start_time');//开始时间
        $end_time = $this->_request('end_time');//结束时间
        $time_type = $this->_request('time_type');//最近一周-week 最近一个月-month 最近六个月-6month

        $record_sum = M('user_account_record_sum')->find($this->loginedUserInfo['uid']);

        if ($this->loginedUserInfo['role']) {
// 		    $userInfo = $this->loginedUserInfo;
            $myUser = M("user")->find($this->loginedUserInfo['uid']);
            $tenantId = service("Account/Account")->getTenantIdByUser($myUser);
            if (!$tenantId) {
                $tenantId = service("Application/ProjectManage")->getTenantByUid($this->loginedUserInfo['uid']);
            }
            $tenant = service("Payment/PayAccount")->getBaseInfo($tenantId);
            $tenant['total_amount_view'] = humanMoney($tenant['amount'] + $tenant['repay_freeze_money'] + $tenant['reward_money'] + $tenant['invest_reward_money'] + $tenant['coupon_money'],
                2, false);
            $tenant['repay_freeze_money_view'] = humanMoney($tenant['repay_freeze_money'], 2, false);
            $this->assign("tenant", $tenant);
        }

        $user_account = M('user_account')->find($this->loginedUserInfo['uid']);
        $this->total_free_money = $user_account['free_money'];
        //2015519为1时新版的用户账户
        if (C('ACCOUNT_VIEW_NEW')) {
            $account_serarch_service = service("Payment/AccountSearchNew");
        } else {
            $account_serarch_service = service("Payment/AccountSearch");
        }
        $result = $account_serarch_service->getMyRecordList(
            $this->loginedUserInfo['uid'],
            $status,
            $start_time,
            $end_time,
            $time_type
        );

        $this->totalrecharge = $result['totalrecharge'];
        $this->totalTouzhi = $result['totalTouzhi'];
        $this->totalHuikuan = $result['totalHuikuan'];
        $this->totalTixian = $result['totalTixian'];
        $this->totalJiangli = $result['totalJiangli'];
        $this->totalZhuangrang = $result['totalZhuangrang'];
        $this->totalFreeMoney = $result['totalFreeMoney'];
        $this->totalPtFee = $result['totalPtFee'];
        $this->totalMove = $result['totalMove'];

        $this->total = (string)(
            (int)$result['totalrecharge']
            + (int)$result['totalTouzhi']
            + (int)$result['totalHuikuan']
            + (int)$result['totalTixian']
            + (int)$result['totalJiangli']
            + (int)$result['totalZhuangrang']
            + (int)$result['totalPtFee']
            + (int)$result['totalMove']
        );
        $this->error = $result['error'];
        $this->list = $result['list'];
        $this->assign('status', $status);
        $this->assign('show_total', $this->loginedUserInfo['role']);
        //投资总金额 ＝ 用户的冻结金额＋实际投资金额(支付以后统计的) Q4改版的要求
        $invest_sum = $record_sum['invest_sum'] + $user_account['buy_freeze_money'] + $user_account['zs_buy_freeze_money'];
        $record_sum['invest_sum'] = $invest_sum;
        $this->record_sum = $record_sum;

        if (C('ACCOUNT_VIEW_NEW') && !in_array($this->loginedUserInfo['role'], array(IdentityConst::ROLE_CORP, IdentityConst::ROLE_PERSON))) {
            $this->display();
        } else {
            $this->display('getMyRecordList_old');
        }
    }
	
	/**
	 * 取消提现
	 */
	public function cancelCashout(){
		$id = (int)$this->_post('id');
		try{
			$rel = service("Payment/Cashout")->cancel($id, $this->loginedUserInfo['uid']);
		} catch(Exception $e){
			D("Admin/AccountExceptLog")->insertLog($this->loginedUserInfo['uid'], "PayAccount/cancelCashout", "Payment/Cashout", "cancel", $e->getMessage());
			showJson( array('boolen'=>0, 'message'=>$e->getMessage()));
		}
		if($rel){
			showJson( array('boolen'=>1, 'message'=>"取消成功"));
		} else {
			showJson( array('boolen'=>0, 'message'=>"取消失败"));
		}
	}
	
	//提现申请记录
	public function getMyCashoutList(){
		$status = $this->_request('status');//状态 1-待处理 2-提现成功 3-提现失败 4-取消提现
		$start_time = $this->_request('start_time');//申请开始时间
		$end_time = $this->_request('end_time');//申请结束时间
		$time_type = $this->_request('time_type');//最近一周-week 最近一个月-month 最近六个月-6month
		
		$result = service("Payment/AccountSearch")->getMyCashoutList($this->loginedUserInfo['uid'], $status, $start_time, $end_time, $time_type);
		
		$this->totalCTodo = $result['totalCTodo'];
		$this->totalCSuccess = $result['totalCSuccess'];
		$this->totalCFailed = $result['totalCFailed'];
		$this->totalCCanel = $result['totalCCanel'];

		$this->assign('role', $this->loginedUserInfo['role']);
		
		$this->error = $result['error'];
		$this->list = $result['list'];
		$this->assign('status',$status);
		$this->display();
	}
	
	//获取我的线下充值的列表
	public function getMyXianxiaList(){
		$status = $this->_request('status');//状态 1-待处理，2-充值成功，3-充值失败,4-待跟踪,5-处理中
		$start_time = $this->_request('start_time');//申请开始时间
		$end_time = $this->_request('end_time');//申请结束时间
		$time_type = $this->_request('time_type');//最近一周-week 最近一个月-month 最近六个月-6month

		$result = service("Payment/AccountSearch")->getMyXianxiaList($this->loginedUserInfo['uid'], $status, $start_time, $end_time, $time_type);

		$this->totalXTodo = $result['totalXTodo'];
		$this->totalXSuccess = $result['totalXSuccess'];
		$this->totalXFailed = $result['totalXFailed'];
		$this->totalXTrack = $result['totalXTrack'];

		$this->error = $result['error'];
		$this->list = $result['list'];
		$this->assign('status',$result['status']);
		$this->display('getMyXianxiaList'.$status);
	}
	
	// -- 审核用 ------------------------------------
	//获取线下充值的列表
	public function getXianxiaIndex(){
	    //发布站点
        $mi_list = D('Application/Member')->MemberListNokey();
        $this->mi_list = $mi_list;
//        p($mi_list);exit;
		$this->display();
	}

	public function getXianxiaList(){
		$parameter['status'] = $status = $this->_request('status');//状态
		$parameter['start_time'] = $start_time = $this->_request('start_time');//申请开始时间
		$parameter['end_time'] = $end_time = $this->_request('end_time');//申请结束时间
		$parameter['time_type'] = $time_type = $this->_request('time_type');//最近一周-week 最近一个月-month 最近六个月-6month
		$parameter['uname'] = $uname = $this->_request('uname'); //用户名
		$parameter['real_name'] = $real_name = $this->_request('real_name'); //真实名称
		$parameter['mi_no'] = $mi_no = $this->_request('mi_no');//接入点编号
		
		$count_cond1['status'] = 1;
		$count_cond2['status'] = 2;
		$count_cond3['status'] = 3;
		$count_cond4['status'] = 4;

        $condition['status']=$status;
		if($start_time || $end_time){
			if($start_time){
				$start_time = strtotime($start_time);
				$condition['ctime'] = $start_where = array('egt', $start_time);
			}
			if($end_time){
				$end_time = strtotime($end_time) + 24 * 3600 -1;
				$condition['ctime'] = $end_where = array('lt', $end_time);
			}
			if($start_time && $end_time) {
				if($end_time < $start_time) $error = "日期格式错误";
				else $condition['ctime'] = array($start_where, $end_where);
			} 
		} else {
			if($time_type == 'week'){
				$condition['ctime'] = array(array('gt',strtotime("-1 week", strtotime(date("Y-m-d")))),array('lt',strtotime("now")));
			}
			if($time_type == 'month'){
				$condition['ctime'] = array(array('gt',strtotimeX("-1 month", strtotime(date("Y-m-d")))),array('lt',strtotime("now")));
			}
			if($time_type == '6month'){
				$condition['ctime'] = array(array('gt',strtotimeX("-6 month", strtotime(date("Y-m-d")))),array('lt',strtotime("now")));
			}
		}
		
		$user_where = "";
		if($uname) $user_where = "and uname like "."'%".$uname."%'"; 
		if($real_name) $user_where = "and real_name like "."'%".$real_name."%'";
		if($mi_no && $mi_no!=1234567889) $user_where .= "and mi_no = '".$mi_no."'";//根据用户接入点搜索
		if($user_where){
			 $condition['_string'] = ' exists(select 1 from fi_user where fi_xianxia_recharge.uid = fi_user.uid '.$user_where.' )  ';
		}
		
		if($status == 1){
			$order = " ctime asc ";
		} else {
			$order = " ctime desc ";
		}
		if(!$error){
			$list = service('Payment/XianxiaRecharge')->getList($status, $condition, $parameter, $order);
		}
		$totalTodo = D("Payment/XianxiaRecharge")->field("COUNT(1) AS CNT")->where($count_cond1)->find();
		$this->totalTodo = $totalTodo['CNT'];
		$totalSuccess = D("Payment/XianxiaRecharge")->field("COUNT(1) AS CNT")->where($count_cond2)->find();
		$this->totalSuccess = $totalSuccess['CNT'];
		$totalFailed = D("Payment/XianxiaRecharge")->field("COUNT(1) AS CNT")->where($count_cond3)->find();
		$this->totalFailed = $totalFailed['CNT'];
		$totalWait = D("Payment/XianxiaRecharge")->field("COUNT(1) AS CNT")->where($count_cond4)->find();
		$this->totalWait = $totalWait['CNT'];

        //2015527统计充值管理的资金总额
        $totalTodo1 = D("Payment/XianxiaRecharge")->field("COUNT(1) AS CNT")->where($condition)->find();
        $this->totalTodo1 = $totalTodo1['CNT'];
        $totalSuccess1 = D("Payment/XianxiaRecharge")->field("COUNT(1) AS CNT")->where($condition)->find();
        $this->totalSuccess1 = $totalSuccess1['CNT'];
        $totalFailed1= D("Payment/XianxiaRecharge")->field("COUNT(1) AS CNT")->where($condition)->find();
        $this->totalFailed1 = $totalFailed1['CNT'];
        $totalWait1 = D("Payment/XianxiaRecharge")->field("COUNT(1) AS CNT")->where($condition)->find();
        $this->totalWait1 = $totalWait1['CNT'];

        $totalTodo_sum = D("Payment/XianxiaRecharge")->field("SUM(apply_amount) AS SUMTOTLE")->where($condition)->find();
        $this->totalTodo_sum = $totalTodo_sum['SUMTOTLE']/100;
        $totalSuccess_sum = D("Payment/XianxiaRecharge")->field("SUM(real_amount) AS SUMTOTLE")->where($condition)->find();
        $this->totalSuccess_sum = $totalSuccess_sum['SUMTOTLE']/100;
        $totalFailed_sum = D("Payment/XianxiaRecharge")->field("SUM(apply_amount) AS SUMTOTLE")->where($condition)->find();
        $this->totalFailed_sum = $totalFailed_sum['SUMTOTLE']/100;
        $totalWait_sum = D("Payment/XianxiaRecharge")->field("SUM(apply_amount) AS SUMTOTLE")->where($condition)->find();
        $this->totalWait_sum = $totalWait_sum['SUMTOTLE']/100;
        //2015527end

        //发布站点
        $mi_list = D('Application/Member')->MemberListNokey();
        $this->mi_list = $mi_list;
        		
		$this->error = $error;
		//p($list);exit;
		$this->list = $list;
		$this->status = $status;
		$this->display();
	}
	
	//excel导出
	public function exportXianxiaList(){
		$parameter['status'] = $status = $this->_request('status');//状态
		$parameter['start_time'] = $start_time = $this->_request('start_time');//申请开始时间
		$parameter['end_time'] = $end_time = $this->_request('end_time');//申请结束时间
		$parameter['time_type'] = $time_type = $this->_request('time_type');//最近一周-week 最近一个月-month 最近六个月-6month
		$parameter['uname'] = $uname = $this->_request('uname'); //用户名
		$parameter['real_name'] = $real_name = $this->_request('real_name'); //真实名称
		
		if($start_time || $end_time){
			if($start_time){
				$start_time = strtotime($start_time);
				$condition['ctime'] = $start_where = array('gt', $start_time);
			}
			if($end_time){
				$end_time = strtotime($end_time) + 24 * 3600 -1;
				$condition['ctime'] = $end_where = array('lt', $end_time);
			}
			if($start_time && $end_time) {
				if($end_time < $start_time) $error = "日期格式错误";
				else $condition['ctime'] = array($start_where, $end_where);
			}
		} else {
			if($time_type == 'week'){
				$condition['ctime'] = array(array('gt',strtotime("-1 week", strtotime(date("Y-m-d")))),array('lt',strtotime("now")));
			}
			if($time_type == 'month'){
				$condition['ctime'] = array(array('gt',strtotimeX("-1 month", strtotime(date("Y-m-d")))),array('lt',strtotime("now")));
			}
			if($time_type == '6month'){
				$condition['ctime'] = array(array('gt',strtotimeX("-6 month", strtotime(date("Y-m-d")))),array('lt',strtotime("now")));
			}
		}
		
		$user_where = "";
		if($uname) $user_where = "and uname like "."'%".$uname."%'";
		if($real_name) $user_where = "and real_name like "."'%".$real_name."%'";
		if($mi_no && $mi_no!=1234567889) $user_where .= "and mi_no = '".$mi_no."'";//根据用户接入点搜索
		if($user_where){
			$condition['_string'] = ' exists(select 1 from fi_user where fi_xianxia_recharge.uid = fi_user.uid '.$user_where.' )  ';
		}
		
		if($status == 1){
			$order = " ctime asc ";
		} else {
			$order = " ctime desc ";
		}
		if($error){
			exit($error);
		}
		
		$where = array();
		if($condition) $where = $condition;
		if($status){
			$where['status'] = $status;
		}
		$list = D("Payment/XianxiaRecharge")->where($where)->order($order)->findPage(10000, $parameter);
		$this->doExportXianxiaList($list, $status);
	}
	
	private function doExportXianxiaList($list, $status=0){
		//1-待处理，2-充值成功，3-充值失败,4-待跟踪
		$statusTitle = '';
		if($status == 1) $statusTitle = '待处理';
		if($status == 2) $statusTitle = '充值成功';
		if($status == 3) $statusTitle = '充值失败';
		if($status == 4) $statusTitle = '待跟踪';
		import_addon("libs.PHPExcel.PHPExcel");
		$title = mb_convert_encoding("鑫合汇投融平台充值明细_".$statusTitle."充值申请", "GBK","UTF-8");
		$objPHPExcel = new PHPExcel();
		$objProps = $objPHPExcel->getProperties();
		$objProps->setTitle("鑫合汇投融平台充值明细_".$statusTitle."充值申请");
		$objPHPExcel->setActiveSheetIndex(0);
		 
		$objPHPExcel->getActiveSheet()->setTitle("充值申请");
		$objPHPExcel->getActiveSheet()->setCellValue('A1', "充值时间");
		$objPHPExcel->getActiveSheet()->setCellValue('B1', "站点");
		$objPHPExcel->getActiveSheet()->setCellValue('C1', "充值用户");
		$objPHPExcel->getActiveSheet()->setCellValue('D1', "真实姓名");
		$objPHPExcel->getActiveSheet()->setCellValue('E1', "申请充值金额");
		$objPHPExcel->getActiveSheet()->setCellValue('F1', "实际充值金额");
		$objPHPExcel->getActiveSheet()->setCellValue('G1', "资金渠道");
		$objPHPExcel->getActiveSheet()->setCellValue('H1', "备注");
		$objPHPExcel->getActiveSheet()->setCellValue('I1', "说明");
		 
		$objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(20);
		$objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(20);
		$objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(20);
		$objPHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth(20);
		$objPHPExcel->getActiveSheet()->getColumnDimension('E')->setWidth(20);
		$objPHPExcel->getActiveSheet()->getColumnDimension('F')->setWidth(20);
		$objPHPExcel->getActiveSheet()->getColumnDimension('G')->setWidth(20);
		$objPHPExcel->getActiveSheet()->getColumnDimension('H')->setWidth(20);
		$objPHPExcel->getActiveSheet()->getColumnDimension('I')->setWidth(20);

		//发布站点
        $mi_list = D('Application/Member')->MemberListNokey();
		if($list['data']){
			$i=1;
			foreach ($list['data'] as $k=>$v){
				$i++;
				$v['user'] = M('user')->field("uname, real_name ,mi_no")->find($v['uid']);
				$objPHPExcel->getActiveSheet()->setCellValue('A'.$i, date("Y-m-d H:i", $v['ctime']));
				$objPHPExcel->getActiveSheet()->setCellValue('B'.$i, $mi_list[$v['user']['mi_no']]['mi_name']);
				$objPHPExcel->getActiveSheet()->setCellValue('C'.$i, $v['user']['uname']);
				$objPHPExcel->getActiveSheet()->setCellValue('D'.$i, $v['user']['real_name']);
				$objPHPExcel->getActiveSheet()->setCellValue('E'.$i, number_format((int)$v['apply_amount']/100, 2)."元");
				$objPHPExcel->getActiveSheet()->setCellValue('F'.$i, number_format((int)$v['real_amount']/100, 2)."元");
				$objPHPExcel->getActiveSheet()->setCellValue('G'.$i, $v['bank_name']);
				$objPHPExcel->getActiveSheet()->setCellValue('H'.$i, $v['bak']);
				$objPHPExcel->getActiveSheet()->setCellValue('I'.$i, $v['explain']);
			}
		}
		 
		header('Content-Type: application/vnd.ms-excel');
		header('Content-Disposition:attachment;filename="' . $title . '.xls"');
		header('Cache-Control: max-age=0');
		 
		$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
		$objWriter->save('php://output');
	}
	
	
	//获取线下数据
	public function getDealXianxia(){
		$id = (int)$this->_request("id");//金额 元
		$status = (int)$this->_request("status");	// 1-通过  2-不通过
		$xianxia = D("Payment/XianxiaRecharge")->find((int)$id);
		if(!$xianxia) showJson(array("boolen"=>0, "message"=>"数据不存在"));
		$xianxia['real_amount'] = $xianxia['real_amount']/100;
		$xianxia['apply_amount'] = $xianxia['apply_amount']/100;
		$payment = service('Payment/Payment');
		$type = PaymentService::TYPE_XIANXIA;
		$payment = $payment->init($type);
		$this->bankList = $payment->getBankList();
		$this->data = $xianxia;
		$this->assign('id', $id);
		$this->assign('status', $status);
		$this->display();
	}
	
	//处理线下充值
	public function dealXianxia(){
		$id = (int)$this->_post('id');//线下充值id
		$real_amount = $this->_post('real_amount');//实际充值金额 元
		$no = $this->_post('no');//编号 流水号和金额，格式"流水号1:金额1,流水号2:金额2,..." 例如"xxxx:20,xx:30" 流水号xxx,金额20分
		$explain = $this->_post('explain');//说明
		$status = (int)$this->_post('status');//处理结果状态
        service('Payment/XianxiaRecharge');
		if($status == XianxiaRechargeModel::STATUS_SUCCESS){
			if(!$real_amount) {return showJson(array("boolen"=>0, "message"=>"请输入实际金额"));}
			if(!is_numeric($real_amount)) {return showJson(array("boolen"=>0, "message"=>"请输入数字格式"));}
		}

		if($status == XianxiaRechargeModel::STATUS_TRACK){
			D("Payment/XianxiaRecharge")->saveTrackDesc($id,$explain);
			ajaxReturn(0,'操作成功',1);
		}

		try{
			$result = service('Payment/XianxiaRecharge')->deal($id, bcmul($real_amount, '100', 0), $no, $explain, $status, $this->loginedUserInfo['uid']);
			showJson($result);
		}catch(Exception $e){return showJson(array("boolen"=>0, "message"=>$e->getMessage()));}
	}

	public function toGetCashoutList(){
	    //发布站点
        $mi_list = D('Application/Member')->MemberListNokey();
        $this->mi_list = $mi_list;
		$this->display();
	}

	//获取提现申请的列表
	public function getCashoutList(){
		$parameter['status'] = $status = $this->_request('status');//状态
		$parameter['start_time'] = $start_time = $this->_request('start_time');//申请开始时间
		$parameter['end_time'] = $end_time = $this->_request('end_time');//申请结束时间
		$parameter['time_type'] = $time_type = $this->_request('time_type');//最近一周-week 最近一个月-month 最近六个月-6month
		$parameter['uname'] = $uname = $this->_request('uname'); //用户名
		$parameter['real_name'] = $real_name = $this->_request('real_name'); //真实名称
		$parameter['mi_no'] = $mi_no = $this->_request('mi_no');//接入点编号
		$parameter['handle_type'] = $handle_type = $this->_request('handle_type');
        
        //用户类别(默认全部)
        $parameter['company_id'] = $company_id = $this->_request("company_id","intval",0);
        
		//成功和失败两种状态下可以按提现时间和处理时间查询
		$timeSType = $this->_request('time_s_type');
		if($status == 2 || $status == 3){
			$parameter['order'] = $orderBy = $this->_request("order");
			if($orderBy){
				list($field,$order) = explode("|", $orderBy);
				$this->assign("order_field",$field);
				$this->assign("order_type",$order);
				$orderBy = "$field $order";
			}else{
				$orderBy = "mtime DESC";
			}
		}
		
		$count_cond1['status'] = 1;
		$count_cond2['status'] = 5;
        $count_cond2['is_dealing']=0;//2015610 is_dealing为了区分提现中和提现处理中 默认为0(为1提现处理中)
		$count_cond3['status'] = 2;
		$count_cond4['status'] = 3;

		$count_cond6['status'] = 6;//机器待处理
		$count_cond7['status'] = 7;//机器处理中
		$count_cond8['status'] = 8;//客服跟踪

        //201569增加处理中start
        $count_cond99['status']=5;
        $count_cond99['is_dealing']=1;

        if($status==99){
            $condition['status'] = 5;
            $condition['is_dealing']=1;
        }else if($status==5){
            $condition['status'] = 5;
            $condition['is_dealing']=0;//end
        }else{
		    $condition['status'] = $status;
        }
		if($start_time || $end_time){
			if($start_time){
				$start_time = strtotime($start_time);
				$condition['ctime'] = $start_where = array('gt', $start_time);
			}
			if($end_time){
				$end_time = strtotime($end_time) + 24 * 3600 -1;
				$condition['ctime'] = $end_where = array('lt', $end_time);
			}
			if($start_time && $end_time) {
				if($end_time < $start_time) $error = "日期格式错误";
				else $condition['ctime'] = array($start_where, $end_where);
			}
		} else {
			if($time_type == 'week'){
				$condition['ctime'] = array(array('gt',strtotime("-1 week", strtotime(date("Y-m-d")))),array('lt',strtotime("now")));
			}
			if($time_type == 'month'){
				$condition['ctime'] = array(array('gt',strtotimeX("-1 month", strtotime(date("Y-m-d")))),array('lt',strtotime("now")));
			}
			if($time_type == '6month'){
				$condition['ctime'] = array(array('gt',strtotimeX("-6 month", strtotime(date("Y-m-d")))),array('lt',strtotime("now")));
			}
		}
		if($timeSType == 'm' && $condition['ctime']){
			$condition['mtime'] = $condition['ctime'];
			$parameter['time_s_type'] = $timeSType;
			unset($condition['ctime']);
		}
		
		//处理方式
		if($handleType = I('request.handle_type')){
		    if($handleType == 2){
//		        $condition['_string'] = 'mtime = mac_time';
                $condition['_string'] = 'mac_time > 0';
		    }elseif($handleType == 1){
//		        $condition['_string'] = 'mtime = deal_time';
                $condition['_string'] = 'deal_time > 0 and deal_time is not null';
		    }
		}
		
		$user_where = "";
		if($uname) $user_where .= " and uname like "."'%".$uname."%'";
		if($real_name) $user_where .= " and real_name like "."'%".$real_name."%'";
		if($mi_no && $mi_no!=1234567889) $user_where .= "and mi_no = '".$mi_no."'";//根据用户接入点搜索
		if($user_where){
			$condition['_string'] = ' exists(select 1 from fi_user where fi_cashout_apply.uid = fi_user.uid '.$user_where.' )  ';
		}
		//增加接入点条件
		if($mi_no  && $mi_no!=1234567889) {
		    $condition['mi_no'] = array('eq', $mi_no);
		}
		
		if($status == 1 || $status == 5 || $status==99){//2015611 增加前端将$status=99固定为提现处理中
			$order = " ctime asc ";
		}elseif($status == 2 || $status == 3){
			$order = " {$orderBy} ";
		} else {
			$order = " ctime desc ";
		}
        
        $pageSize = 20;
        
        
        if ($company_id) {
            //获取where条件来过滤是否是企福鑫用户的会员
            $map = service("Financing/CompanySalary")->getCashFilter($company_id);
            $condition['_logic'] = "AND";
            $condition['_complex'] = $map;
        }

//        p($condition);
		if(!$error){
			$list = service('Payment/Cashout')->getList($condition, $parameter, $order,$pageSize);
		}
		$totalTodo = D("Payment/CashoutApply")->field("COUNT(1) AS CNT")->where($count_cond1)->find();
		$this->totalTodo = $totalTodo['CNT'];
		$totalDoing = D("Payment/CashoutApply")->field("COUNT(1) AS CNT")->where($count_cond2)->find();
		$this->totalDoing = $totalDoing['CNT'];
        //201569增加处理中start
        $totalBeing = D("Payment/CashoutApply")->field("COUNT(1) AS CNT")->where($count_cond99)->find();
        $this->totalBeing= $totalBeing['CNT'];//end

		$totalSuccess = D("Payment/CashoutApply")->field("COUNT(1) AS CNT")->where($count_cond3)->find();
		$this->totalSuccess = $totalSuccess['CNT'];
		$totalFailed = D("Payment/CashoutApply")->field("COUNT(1) AS CNT")->where($count_cond4)->find();
		$this->totalFailed = $totalFailed['CNT'];
		
		$totalMacTodo = D("Payment/CashoutApply")->field("COUNT(1) AS CNT")->where($count_cond6)->find();
		$this->totalMacTodo = $totalMacTodo['CNT'];
		$totalMacDoing = D("Payment/CashoutApply")->field("COUNT(1) AS CNT")->where($count_cond7)->find();
		$this->totalMacDoing = $totalMacDoing['CNT'];
		$totalMacCustom = D("Payment/CashoutApply")->field("COUNT(1) AS CNT")->where($count_cond8)->find();
		$this->totalMacCustom= $totalMacCustom['CNT'];


        //2015527提现管理每个状态总金额 --start
        $totalTodo1 = D("Payment/CashoutApply")->field("COUNT(1) AS CNT")->where($condition)->find();
        $this->totalTodo1 = $totalTodo1['CNT'];
        $totalDoing1 = D("Payment/CashoutApply")->field("COUNT(1) AS CNT")->where($condition)->find();
        $this->totalDoing1 = $totalDoing1['CNT'];
        //201569增加处理中start
        $totalBeing1 = D("Payment/CashoutApply")->field("COUNT(1) AS CNT")->where($condition)->find();
        $this->totalBeing1= $totalBeing1['CNT'];//end
        $totalSuccess1 = D("Payment/CashoutApply")->field("COUNT(1) AS CNT")->where($condition)->find();
        $this->totalSuccess1 = $totalSuccess1['CNT'];
        $totalFailed1 = D("Payment/CashoutApply")->field("COUNT(1) AS CNT")->where($condition)->find();
        $this->totalFailed1 = $totalFailed1['CNT'];

        $totalMacTodo1 = D("Payment/CashoutApply")->field("COUNT(1) AS CNT")->where($condition)->find();
        $this->totalMacTodo1 = $totalMacTodo1['CNT'];
        $totalMacDoing1 = D("Payment/CashoutApply")->field("COUNT(1) AS CNT")->where($condition)->find();
        $this->totalMacDoing1 = $totalMacDoing1['CNT'];
        $totalMacCustom1 = D("Payment/CashoutApply")->field("COUNT(1) AS CNT")->where($condition)->find();
        $this->totalMacCustom1= $totalMacCustom1['CNT'];

        $totalTodo_sum = D("Payment/CashoutApply")->field("SUM(money+fee) AS SUMTOTLE")->where($condition)->find();
        $this->totalTodo_sum = $totalTodo_sum['SUMTOTLE']/100;
        $totalDoing_sum = D("Payment/CashoutApply")->field("SUM(money+fee) AS SUMTOTLE")->where($condition)->find();
        $this->totalDoing_sum = $totalDoing_sum['SUMTOTLE']/100;
        //201569增加处理中start
        $totalBeing_sum = D("Payment/CashoutApply")->field("SUM(money+fee) AS SUMTOTLE")->where($condition)->find();
        $this->totalBeing_sum = $totalBeing_sum['SUMTOTLE']/100;//end
        $totalSuccess_sum = D("Payment/CashoutApply")->field("SUM(money+fee) AS SUMTOTLE")->where($condition)->find();
        $this->totalSuccess_sum = $totalSuccess_sum['SUMTOTLE']/100;
        $totalFailed_sum = D("Payment/CashoutApply")->field("SUM(money+fee) AS SUMTOTLE")->where($condition)->find();
        $this->totalFailed_sum = $totalFailed_sum['SUMTOTLE']/100;

        $totalMacTodo_sum = D("Payment/CashoutApply")->field("SUM(money+fee) AS SUMTOTLE")->where($condition)->find();
        $this->totalMacTodo_sum = $totalMacTodo_sum['SUMTOTLE']/100;
        $totalMacDoing_sum = D("Payment/CashoutApply")->field("SUM(money+fee) AS SUMTOTLE")->where($condition)->find();
        $this->totalMacDoing_sum = $totalMacDoing_sum['SUMTOTLE']/100;
        $totalMacCustom_sum = D("Payment/CashoutApply")->field("SUM(money+fee) AS SUMTOTLE")->where($condition)->find();
        $this->totalMacCustom_sum= $totalMacCustom_sum['SUMTOTLE']/100; //2015527 --end
        
		//发布站点
        $mi_list = D('Application/Member')->MemberListNokey();
        $this->mi_list = $mi_list;		
        
		$this->error = $error;

        //查询退回时间 2015年05月加上
        foreach ($list['data'] as $key=>$value)
        {
            if ($list[$key]['rollback_status']==1)
            {
                $result=M('cashout_rollback')->where(array('apply_id'=>$list[$key]['id']))->find();
                if ($result)
                {
                    $list[$key]['rollback_time'] = date('Y-m-d', $result['rollback_time']);
                    $list[$key]['redo_time'] = date('Y-m-d', $result['redo_time']);
                }
            }
        }
        //

		$this->list = $list;
		//p($list);exit;
		$this->status = $status;
		$this->display();
	}
	
	//excel导出
	public function exportCashoutList(){
		$parameter['status'] = $status = $this->_request('status');//状态
		$parameter['start_time'] = $start_time = $this->_request('start_time');//申请开始时间
		$parameter['end_time'] = $end_time = $this->_request('end_time');//申请结束时间
		$parameter['time_type'] = $time_type = $this->_request('time_type');//最近一周-week 最近一个月-month 最近六个月-6month
		$parameter['uname'] = $uname = $this->_request('uname'); //用户名
		$parameter['real_name'] = $real_name = $this->_request('real_name'); //真实名称
		$parameter['mi_no'] = $mi_no = $this->_request('mi_no');//接入点编号
		$parameter['handle_type'] = $handle_type = $this->_request('handle_type');
		$timeSType = $this->_request('time_s_type');
        $is_dealing=0;

		if($status == 2 || $status == 3){
		    $parameter['order'] = $orderBy = $this->_request("order");
		    if($orderBy){
		        list($field,$order) = explode("|", $orderBy);
		        $this->assign("order_field",$field);
		        $this->assign("order_type",$order);
		        $orderBy = "$field $order";
		    }else{
		        $orderBy = "mtime DESC";
		    }
		}

		$condition['status'] = $status;

        //201569 增加处理中 start
        if($status==99){
            $condition['status'] = 5;
            $condition['is_dealing']=1;
            $is_dealing=1;
        }
        if($status==5){
            $condition['is_dealing']=0;
        }//end

		if($start_time || $end_time){
			if($start_time){
				$start_time = strtotime($start_time);
				$condition['ctime'] = $start_where = array('gt', $start_time);
			}
			if($end_time){
				$end_time = strtotime($end_time) + 24 * 3600 -1;
				$condition['ctime'] = $end_where = array('lt', $end_time);
			}
			if($start_time && $end_time) {
				if($end_time < $start_time) $error = "日期格式错误";
				else $condition['ctime'] = array($start_where, $end_where);
			}
		} else {
			if($time_type == 'week'){
				$condition['ctime'] = array(array('gt',strtotime("-1 week", strtotime(date("Y-m-d")))),array('lt',strtotime("now")));
			}
			if($time_type == 'month'){
				$condition['ctime'] = array(array('gt',strtotimeX("-1 month", strtotime(date("Y-m-d")))),array('lt',strtotime("now")));
			}
			if($time_type == '6month'){
				$condition['ctime'] = array(array('gt',strtotimeX("-6 month", strtotime(date("Y-m-d")))),array('lt',strtotime("now")));
			}
		}
		if($timeSType == 'm' && $condition['ctime']){
			$condition['mtime'] = $condition['ctime'];
			$parameter['time_s_type'] = $timeSType;
			unset($condition['ctime']);
		}
		
		//处理方式
		if($handleType = I('request.handle_type')){
		    if($handleType == 2){
//		        $condition['_string'] = 'mtime = mac_time';
                $condition['_string'] = 'mac_time > 0';
		    }elseif($handleType == 1){
//		        $condition['_string'] = 'mtime = deal_time';
                $condition['_string'] = 'deal_time > 0 and deal_time is not null';
		    }
		}
		
		$user_where = "";
		if($uname) $user_where = "and uname like "."'%".$uname."%'";
		if($real_name) $user_where = "and real_name like "."'%".$real_name."%'";

		if($user_where){
			$condition['_string'] = ' exists(select 1 from fi_user where fi_cashout_apply.uid = fi_user.uid '.$user_where.' )  ';
		}
			//增加接入点条件
		if($mi_no  && $mi_no!=1234567889) {
		    $condition['mi_no'] = array('eq', $mi_no);
		}		
	    if($status == 1 || $status == 5){
			$order = " ctime asc ";
		}elseif($status == 2 || $status == 3){
			$order = " {$orderBy} ";
		} else {
			$order = " ctime desc ";
		}
		if($error) exit($error);
		
		$list = D("Payment/CashoutApply")->where($condition)->order($order)->findPage(10000, $parameter);
// 		echo D("Payment/CashoutApply")->getLastSql();
		$this->doExportCashoutList($list, $status,$is_dealing);
	}
	
	private function doExportCashoutList($list, $status=0,$is_dealing=0){
		$statusStr = '';
		if($status == 1) $statusStr = "待处理";
		if($status == 2) $statusStr = "提现成功";
		if($status == 3) $statusStr = "提现不批准";
		if($status == 4) $statusStr = "取消申请";
		if($status == 5) $statusStr = "提现中";
        if($is_dealing == 1) $statusStr = "提现处理中";
		import_addon("libs.PHPExcel.PHPExcel");
		$title = mb_convert_encoding("云融资投融平台".$statusStr."提现申请明细", "GBK","UTF-8");
		$objPHPExcel = new PHPExcel();
		$objProps = $objPHPExcel->getProperties();
		$objProps->setTitle("鑫合汇投融平台".$statusStr."提现申请明细");
		$objPHPExcel->setActiveSheetIndex(0);
			
		$objPHPExcel->getActiveSheet()->setTitle("提现申请");
		$objPHPExcel->getActiveSheet()->setCellValue('A1', "提现时间");
		$objPHPExcel->getActiveSheet()->setCellValue('B1', "站点");
		$objPHPExcel->getActiveSheet()->setCellValue('C1', "提现用户");
		$objPHPExcel->getActiveSheet()->setCellValue('D1', "真实姓名");
		$objPHPExcel->getActiveSheet()->setCellValue('E1', "申请提现金额（元）");
		$objPHPExcel->getActiveSheet()->setCellValue('F1', "提现转账费用（元）");
		$objPHPExcel->getActiveSheet()->setCellValue('G1', "手续费（元）");
		$objPHPExcel->getActiveSheet()->setCellValue('H1', "卡号");
		$objPHPExcel->getActiveSheet()->setCellValue('I1', "需支付金额（元）");
		$objPHPExcel->getActiveSheet()->setCellValue('J1', "银行");
		$objPHPExcel->getActiveSheet()->setCellValue('K1', "银行简称");
		if($status == 2 || $status == 3){
		    $objPHPExcel->getActiveSheet()->setCellValue('L1', "处理方式");
		    $objPHPExcel->getActiveSheet()->setCellValue('M1', "处理时间");
		    $objPHPExcel->getActiveSheet()->setCellValue('N1', "备注");
            $objPHPExcel->getActiveSheet()->setCellValue('O1', "ID");//201568 增加提现id
            $objPHPExcel->getActiveSheet()->setCellValue('P1', "提现流水号");//201569 增加提现流水号
		}else{
		    $objPHPExcel->getActiveSheet()->setCellValue('L1', "处理时间");
		    $objPHPExcel->getActiveSheet()->setCellValue('M1', "备注");
            $objPHPExcel->getActiveSheet()->setCellValue('N1', "ID");//201568 增加提现id
            $objPHPExcel->getActiveSheet()->setCellValue('O1', "提现流水号");//201569 增加提现流水号
		}
//        if ($status == 5) {//关闭老版201568
//            $objPHPExcel->getActiveSheet()->setCellValue('N1', "ID");
//        }


		$objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(20);
		$objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(20);
		$objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(20);
		$objPHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth(20);
		$objPHPExcel->getActiveSheet()->getColumnDimension('E')->setWidth(20);
		$objPHPExcel->getActiveSheet()->getColumnDimension('F')->setWidth(20);
		$objPHPExcel->getActiveSheet()->getColumnDimension('G')->setWidth(20);
		$objPHPExcel->getActiveSheet()->getColumnDimension('H')->setWidth(30);
		$objPHPExcel->getActiveSheet()->getColumnDimension('I')->setWidth(20);
		$objPHPExcel->getActiveSheet()->getColumnDimension('J')->setWidth(40);
		$objPHPExcel->getActiveSheet()->getColumnDimension('K')->setWidth(20);
		$objPHPExcel->getActiveSheet()->getColumnDimension('L')->setWidth(20);
		$objPHPExcel->getActiveSheet()->getColumnDimension('M')->setWidth(20);
		if($status == 2 || $status == 3 || $status==5){
		    $objPHPExcel->getActiveSheet()->getColumnDimension('N')->setWidth(20);
		}
        $objPHPExcel->getActiveSheet()->getColumnDimension('O')->setWidth(20);
        $objPHPExcel->getActiveSheet()->getColumnDimension('P')->setWidth(30);
		//发布站点
        $mi_list = D('Application/Member')->MemberListNokey();
        
		if($list['data']){
			$i=1;
			foreach ($list['data'] as $k=>$v){
				$i++;
				$v['user'] = M('user')->field("uname, real_name ")->find($v['uid']);
				$v['user_account'] = M('user_account')->field("amount")->find($v['uid']);
// 				$v['fund_account'] = M('fund_account')->field("account_no,bank_name,sub_bank")->find($v['out_account_id']);
// 				if(!$v['fund_account']){
// 					$v['fund_account']['account_no'] = $v['out_account_no'];
// 					$v['fund_account']['bank_name'] = '';
// 					$v['fund_account']['sub_bank'] = '';
// 				}
				$v['fund_account']['account_no'] = $v['out_account_no'];
				$v['fund_account']['bank_name'] = getCodeItemName('E009', $v['bank']);
				$v['fund_account']['sub_bank'] = $v['sub_bank'];
				
				$objPHPExcel->getActiveSheet()->setCellValue('A'.$i, date("Y-m-d H:i", $v['ctime']));
				$objPHPExcel->getActiveSheet()->setCellValue('B'.$i, $mi_list[$v['mi_no']]['mi_name']);
				$objPHPExcel->getActiveSheet()->setCellValue('C'.$i, $v['user']['uname']);
				$objPHPExcel->getActiveSheet()->setCellValue('D'.$i, $v['user']['real_name']);
				$objPHPExcel->getActiveSheet()->setCellValue('E'.$i, sprintf('%0.2f', ($v['money'] + $v['fee'])/100));
				
                $fuwu_fee = $v['fee'] - $v['tixian_fee'];
				$objPHPExcel->getActiveSheet()->setCellValue('F'.$i, sprintf('%0.2f', (int)$fuwu_fee/100));
				$objPHPExcel->getActiveSheet()->setCellValue('G'.$i, sprintf('%0.2f', (int)$v['tixian_fee']/100));
				
				$objPHPExcel->getActiveSheet()->setCellValue('H'.$i, ' '.$v['fund_account']['account_no']);
                
                //需支付金额
				$objPHPExcel->getActiveSheet()->setCellValue('I'.$i, sprintf('%0.2f', $v['money'])/100);
				
				$objPHPExcel->getActiveSheet()->setCellValue('J'.$i, $v['fund_account']['bank_name'].$v['fund_account']['sub_bank']);
				$objPHPExcel->getActiveSheet()->setCellValue('K'.$i, $v['fund_account']['bank_name']);
				
				if($status == 2 || $status == 3){
					$objPHPExcel->getActiveSheet()->setCellValue('L'.$i, $v['mac_time'] == $v['mtime'] ? '自动提现' : '人工审核');
					$objPHPExcel->getActiveSheet()->setCellValue('M'.$i, date("Y-m-d H:i", $v['mtime']));
					$objPHPExcel->getActiveSheet()->setCellValue('N'.$i, $v['deal_reason']);
                    $objPHPExcel->getActiveSheet()->setCellValue('O'.$i, $v['id']);//201568 增加提现id
                    $objPHPExcel->getActiveSheet()->setCellValue('P'.$i, $v['cashout_process_no']);//201569 增加提现序列号
				}else{
					$objPHPExcel->getActiveSheet()->setCellValue('L'.$i, date("Y-m-d H:i", $v['mtime']));
					$objPHPExcel->getActiveSheet()->setCellValue('M'.$i, $v['deal_reason']);
                    $objPHPExcel->getActiveSheet()->setCellValue('N'.$i, $v['id']);//201568 增加提现id
                    $objPHPExcel->getActiveSheet()->setCellValue('O'.$i, $v['cashout_process_no']);//201569 增加提现序列号
				}
//                if ($status==5) {//关闭老版201568
//                    $objPHPExcel->getActiveSheet()->setCellValue('N'.$i, $v['id']);
//                }

			}
		}
			
		header('Content-Type: application/vnd.ms-excel');
		header('Content-Disposition:attachment;filename="' . $title . '.xls"');
		header('Cache-Control: max-age=0');
			
		$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
		$objWriter->save('php://output');
	}
	
	/**
	 * 前台管理页面->财务管理->资金管理
	 */
	function toGetUserAccountList(){
		$this->display();
	}
	
	function getUserAccountList(){
		$parameter['uname'] = $uname = $this->_request('uname'); //用户名
		$parameter['real_name'] = $real_name = $this->_request('real_name'); //姓名
		$parameter['order'] = $orderBy = $this->_request("order");
		if($orderBy){
			list($field,$order) = explode("|", $orderBy);
			$this->assign("order_field",$field);
			$this->assign("order_type",$order);
			$orderBy = "$field $order";
		}
		
		$user_where = "";
		$condition = array();
		if($uname) $user_where = "and uname like "."'%".$uname."%'";
		if($real_name) $user_where = "and real_name like "."'%".$real_name."%'";
		
		$where = " and account.uid IN (1, 6, 7)";
		if($user_where){
			$condition['_string'] = ' exists(select 1 from fi_user where account.uid = fi_user.uid '.$user_where.' )  ';
			$where .= " and ".$condition['_string'];
		}
		if(!$orderBy) $orderBy = "uid desc";
		$orderBy = str_replace("uid", "account.uid", $orderBy);
		$orderBy = str_replace("ctime", "account.ctime", $orderBy);
		$orderBy = str_replace("mtime", "account.mtime", $orderBy);
		$sql = "SELECT account.*, summary.will_principal, summary.will_profit,
				(account.amount + account.reward_money + account.invest_reward_money + account.coupon_money + account.buy_freeze_money + account.cash_freeze_money + summary.will_profit + summary.will_principal) totalAccount
                FROM `fi_user_account` account, fi_user_account_summary summary
					 where account.uid=summary.uid $where ORDER BY ".$orderBy;
		
		$data = D("Payment/PayAccount")->findPageBySql($sql, $parameter, null, 10, "ORDER BY ".$orderBy);
		$list = array();
		foreach($data['data'] as $ele){
			$ele['user'] = M("user")->field("uname, real_name")->find($ele['uid']);
			$list[] = $ele;
		}
		$data['data'] = $list;
		$this->parameter = $parameter;
		$this->list = $data;
		
		$this->display('ajax_getUserAccountList');
	}
	
	//excel导出
	public function exportUserAccountList(){
		$parameter['uname'] = $uname = $this->_request('uname'); //用户名
		$parameter['real_name'] = $real_name = $this->_request('real_name'); //姓名
		$parameter['order'] = $orderBy = $this->_request("order");
		if($orderBy){
			list($field,$order) = explode("|", $orderBy);
			$this->assign("order_field",$field);
			$this->assign("order_type",$order);
			$orderBy = "$field $order";
		}
		$user_where = "";
		$condition = array();
		if($uname) $user_where = "and uname like "."'%".$uname."%'";
		if($real_name) $user_where = "and real_name like "."'%".$real_name."%'";
		if($user_where){
			$condition['_string'] = ' exists(select 1 from fi_user where fi_user_account.uid = fi_user.uid '.$user_where.' )  ';
		}else{
			$condition['uid'] = array('in','1, 6, 7');
		}
		if(!$orderBy) $orderBy = "uid desc";
		$data = D("Payment/PayAccount")->where($condition)->order($orderBy)->findPage(10000, $parameter);
		$list = array();
		foreach($data['data'] as $ele){
			$ele['user'] = M("user")->field("uname, real_name")->find($ele['uid']);
			$account_summary = M("user_account_summary")->find($ele['uid']);
			$ele['account_summary'] = $account_summary;
			$list[] = $ele;
		}
		$data['data'] = $list;
		$this->doExportUserAccountList($data);
	}
	
	private function doExportUserAccountList($list){
		import_addon("libs.PHPExcel.PHPExcel");
		$title = mb_convert_encoding("鑫合汇投融平台资金管理_资金记录", "GBK","UTF-8");
		$objPHPExcel = new PHPExcel();
		$objProps = $objPHPExcel->getProperties();
		$objProps->setTitle("鑫合汇投融平台资金管理_资金记录");
		$objPHPExcel->setActiveSheetIndex(0);
			
		$objPHPExcel->getActiveSheet()->setTitle("资金记录");
		$objPHPExcel->getActiveSheet()->setCellValue('A1', "用户名");
		$objPHPExcel->getActiveSheet()->setCellValue('B1', "真实姓名");
		$objPHPExcel->getActiveSheet()->setCellValue('C1', "账户总资产");
		$objPHPExcel->getActiveSheet()->setCellValue('D1', "账户余额");
		$objPHPExcel->getActiveSheet()->setCellValue('E1', "冻结余额");
		$objPHPExcel->getActiveSheet()->setCellValue('F1', "待收金额");
		$objPHPExcel->getActiveSheet()->setCellValue('G1', "记录时间");
			
		$objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(20);
		$objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(20);
		$objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(20);
		$objPHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth(20);
		$objPHPExcel->getActiveSheet()->getColumnDimension('E')->setWidth(20);
		$objPHPExcel->getActiveSheet()->getColumnDimension('F')->setWidth(20);
		$objPHPExcel->getActiveSheet()->getColumnDimension('G')->setWidth(20);
	
		if($list['data']){
			$i=1;
			foreach ($list['data'] as $k=>$v){
				$i++;
				$totalAccount = $v['amount']+$v['reward_money']+$v['invest_reward_money']+$v['coupon_money']+$v['buy_freeze_money']+$v['cash_freeze_money']
				+$v['account_summary']['will_profit']+$v['account_summary']['will_principal'];
	
				$objPHPExcel->getActiveSheet()->setCellValue('A'.$i, $v['user']['uname']);
				$objPHPExcel->getActiveSheet()->setCellValue('B'.$i, $v['user']['real_name']);
				$objPHPExcel->getActiveSheet()->setCellValue('C'.$i, humanMoney($totalAccount,2,false));
				$objPHPExcel->getActiveSheet()->setCellValue('D'.$i, humanMoney($v['amount'],2,false));
				$objPHPExcel->getActiveSheet()->setCellValue('E'.$i, humanMoney(($v['buy_freeze_money']+$v['cash_freeze_money']),2,false));
				$objPHPExcel->getActiveSheet()->setCellValue('F'.$i, humanMoney(($v['account_summary']['will_profit']+$v['account_summary']['will_principal']),2,false));
				$objPHPExcel->getActiveSheet()->setCellValue('G'.$i, date("Y-m-d H:i", $v['mtime']));
			}
		}
			
		header('Content-Type: application/vnd.ms-excel');
		header('Content-Disposition:attachment;filename="' . $title . '.xls"');
		header('Cache-Control: max-age=0');
			
		$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
		$objWriter->save('php://output');
	}
	
	/**
	 * 获取某个用户的账户流水
	 */
	public function getRecordListByUid(){
		$this->_dealRecordListByUid('show');
	}
	
	/**
	 * 导出指定用户指定时间区间的账户流水
	 */
	function exportRecordListByUid(){
		$this->_dealRecordListByUid('export');
	}
	
	/**
	 * 处理指定用户指定时间区间的账户流水
	 */
	private function _dealRecordListByUid($type){
		$parameter['uid'] = $uid = (int)$this->_get('uid'); //用户id
		$startTime = $this->_get('startTime');
		$endTime = $this->_get('endTime');
        $this->startTime = $startTime;
        $this->endTime = $endTime;
		
// 		$startTime = '20140501';
// 		$endTime = '20140601';
		
		$condition = array('uid'=>$uid);
		$ociWhere = "\"UID\" = '{$uid}'";
		if($startTime || $endTime){
			if($parameter['startTime'] = $startTime){
				$startTime = strtotime($startTime);
				$condition['mtime'] = $startWhere = array('gt', $startTime);
				$ociWhere .= " AND to_char(\"TRANS_DATE\", 'yyyy-mm-dd') > '".date('Y-m-d', $startTime)."'";
			}
			if($parameter['endTime'] = $endTime){
				$endTime = strtotime($endTime) + 24 * 3600 -1;
				$condition['mtime'] = $endWhere = array('elt', $endTime);
				$ociWhere .= " AND to_char(\"TRANS_DATE\", 'yyyy-mm-dd') < '".date('Y-m-d', strtotime('+1 day', $endTime))."'";
			}
			
			if($startTime && $endTime) {
				$condition['mtime'] = array($startWhere, $endWhere);
			}
		}
		
		if($type == 'show'){
			$list = service("Payment/PayAccount")->getRecordByUid($condition, $parameter);
			$ociSql = 'SELECT "SUM"(IN_AMOUNT) inamount, "SUM"(OUT_AMOUNT) outamount FROM FI_REP_DAILY_USER_AMOUNT WHERE '.$ociWhere;
			$statistics = M('rep_daily_user_amount')->db(0, C('YUNYING_DB_CONF'))->query($ociSql);
			$statisticsData = array_change_key_case($statistics[0]);
            $this->uid = $uid;
			$this->statisticsData = $statisticsData;
			$this->list = $list;
			$this->parameter = $parameter;
			$this->display('recordList-ajax');
		}else{
			$list = service("Payment/PayAccount")->getRecordByUid($condition, $parameter, true);
			$uname = M('user')->where("uid = '{$parameter[uid]}'")->getField('uname');
			import_addon("libs.PHPExcel.PHPExcel");
			$title = mb_convert_encoding("鑫合汇投融平台资金管理_{$uname}的资金记录", "GBK","UTF-8");
			$objPHPExcel = new PHPExcel();
			$objProps = $objPHPExcel->getProperties();
			$objProps->setTitle("鑫合汇投融平台资金管理_{$uname}的资金记录");
			$objPHPExcel->setActiveSheetIndex(0);
			$objPHPExcel->getActiveSheet()->setTitle("{$uname}的资金记录");
			$objPHPExcel->getActiveSheet()->setCellValue('A1', "资金流水号");
			$objPHPExcel->getActiveSheet()->setCellValue('B1', "项目类别");
			$objPHPExcel->getActiveSheet()->setCellValue('C1', "项目名称/编号");
			$objPHPExcel->getActiveSheet()->setCellValue('D1', "类型");
			$objPHPExcel->getActiveSheet()->setCellValue('E1', "冻结");
			$objPHPExcel->getActiveSheet()->setCellValue('F1', "存入");
			$objPHPExcel->getActiveSheet()->setCellValue('G1', "支出");
			$objPHPExcel->getActiveSheet()->setCellValue('H1', "可用余额");
			$objPHPExcel->getActiveSheet()->setCellValue('I1', "时间");
			$objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(25);
			$objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(20);
			$objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(20);
			$objPHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth(20);
			$objPHPExcel->getActiveSheet()->getColumnDimension('E')->setWidth(20);
			$objPHPExcel->getActiveSheet()->getColumnDimension('F')->setWidth(20);
			$objPHPExcel->getActiveSheet()->getColumnDimension('G')->setWidth(20);
			$objPHPExcel->getActiveSheet()->getColumnDimension('H')->setWidth(20);
			$objPHPExcel->getActiveSheet()->getColumnDimension('I')->setWidth(20);
			
			if($list['data']){
				$i=1;
				foreach ($list['data'] as $k=>$v){
					$i++;
					$bCellData = $cCellData = $eCellData = $gCellData = '';
					if(!empty($v['prj_name'])){
						$bCellData = $v['prj_type'] == 'A' ? '日益升' : ($v['prj_type'] == 'B' ? '年益升' : ($v['prj_type'] == 'F' ? '月益升' : ''));
						$v['is_transfer'] == 1 && $bCellData .= '-投资转让';
					}
					
					if(!empty($v['prj_name'])){
						$cCellData .= $v['prj_name'];
						if($v['safeguards'] == 1){
							$cCellData .= $v['safeguards'] == 1 ? '（保）' : '（本）';
						}
						$cCellData .= "\n{$v['prj_no']}";
					}
					
					if($v['freeze_money']){
						$eCellData = $v['in_or_out'] == 1 ? '+' : '-';
					}
					$eCellData .= number_format($v['freeze_money']/100, 2)."元";
					if($v['mod_money'] && $v['in_or_out'] == 1){
						$fCellData =  '+'.number_format($v['mod_money'] / 100, 2)."元";
					}
					
					if($v['mod_money'] && $v['in_or_out'] == 2){ 
						$gCellData .= '-'.number_format($v['mod_money'] / 100, 2)."元";
					} 
		            if( ($v['reward_money'] > 0 || $v['invest_reward_money'] > 0) && $v['in_or_out'] == 2){ 
						$gCellData .= "（含活动奖励： ";
						$flag = 0;
						if($v['reward_money'] > 0){
							$gCellData .= "可提现奖励".number_format($v['reward_money']/100, 2)."元";
							$flag = 1;
						}
						if($flag == 1) $gCellData .= "\n";
						if($v['invest_reward_money'] > 0) $gCellData .= "投资后可提现奖励".number_format($v['invest_reward_money']/100, 2)."元";
						$gCellData .= '）';
		            }
					
					$hCellData = number_format(($v['amount'] + $v['reward_money'] + $v['invest_reward_money']) / 100, 2)."元";
					
					$objPHPExcel->getActiveSheet()->setCellValue('A'.$i, $v['record_no']);
					$objPHPExcel->getActiveSheet()->setCellValue('B'.$i, $bCellData);
					
					$objPHPExcel->getActiveSheet()->getStyle('C')->getAlignment()->setWrapText(true);
					$objPHPExcel->getActiveSheet()->setCellValue('C'.$i, $cCellData);
					
					$objPHPExcel->getActiveSheet()->setCellValue('D'.$i, $v['type']);
					$objPHPExcel->getActiveSheet()->setCellValue('E'.$i, $eCellData);
					$objPHPExcel->getActiveSheet()->setCellValue('F'.$i, $fCellData);
					
					$objPHPExcel->getActiveSheet()->getStyle('G')->getAlignment()->setWrapText(true);
					$objPHPExcel->getActiveSheet()->setCellValue('G'.$i, $gCellData);
					
					$objPHPExcel->getActiveSheet()->setCellValue('H'.$i, $hCellData);
					$objPHPExcel->getActiveSheet()->setCellValue('I'.$i, date("Y-m-d H:i", $v['ctime']));
				}
			}
				
			header('Content-Type: application/vnd.ms-excel');
			header('Content-Disposition:attachment;filename="' . $title . '.xls"');
			header('Cache-Control: max-age=0');
				
			$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
			$objWriter->save('php://output');
		}
	}
	
	//人工改到客服
	public function changeToCus(){
		$id = $this->_post('id');// 提现申请id
		$reason = $this->_post('reason', 'trim');
		D("Payment/CashoutApply")->changeToCus($id, $this->loginedUserInfo['uid'], $reason);
		showJson(array('boolen'=>1, 'message'=>"处理成功"));
	}
	
	//提现申请初始化页面
	public function getDealCashout(){
		$cashoutId = (int)$this->_request('cashoutId');//提现id
		$cashoutApply = D("Payment/CashoutApply")->find($cashoutId);
		$this->cashoutApply = $cashoutApply;
		$this->display();
	}
	
	//处理提现申请
	public function dealCashout(){
		$isAjax = $this->_request('isAjax');
		$cashoutId = $this->_request('cashoutId');//提现id
        $dealStatus = $this->_request('dealStatus');//状态

		$dealReason = $this->_request('dealReason');//提现理由
        $channel = $this->_request('channel');//2015.05 增加提现渠道
		if(!empty($isAjax)){
			try{
				$result = service('Payment/Cashout')->deal($cashoutId, $this->loginedUserInfo['uid'], $dealStatus, $dealReason,$channel);
				showJson($result);
			} catch(Exception $e){
				D("Admin/AccountExceptLog")->insertLog($this->loginedUserInfo['uid'], "PayAccount/dealCashout", "Payment/Cashout", "deal", $e->getMessage());
				showJson(array('boolen'=>0, 'message'=>$e->getMessage()));
			}
		} else {
			$this->isAjax = $isAjax;
			$this->cashoutId = $cashoutId;
			$this->dealStatus = $dealStatus;
			$this->display();
		}
	}
    
    /**
     * 批量处理
     */
    public function batchDealCashout(){
        $cashout_ids = $this->_post("cashoutIds","trim","");
        if(empty($cashout_ids)){
             ajaxReturn("","请至少选择一个进行操作!",0);
        }
        //分割数组
        $cashoutArr = explode(",",$cashout_ids);
        $dealStatus = $this->_post('dealStatus',"intval",0);//状态
        $dealReason = $this->_request('dealReason',"trim");//提现理由
        $channel = $this->_request('channel',"trim");//2015.05 增加提现渠道
        $handel = array();
        foreach ($cashoutArr as $k => $cashout_id) {
            try {
                $result = service('Payment/Cashout')->deal($cashout_id, $this->loginedUserInfo['uid'], $dealStatus, $dealReason,$channel);
                array_push($handel, array('id'=>$cashout_id,'tips'=>$result['message'],'status'=>1));
            }
            catch (Exception $e) {
                D("Admin/AccountExceptLog")->insertLog($this->loginedUserInfo['uid'], "PayAccount/dealCashout", "Payment/Cashout", "deal", $e->getMessage());
                array_push($handel, array('id'=>$cashout_id,'tips'=>$e->getMessage(),'status'=>0));
            }
        }
        $this->_formatBatchDealCashout($handel);
    }
    
    /**
     * 格式化输出
     * @param type $handel
     */
    protected function _formatBatchDealCashout($handel){
        $sucess = "成功的：<br />";
        $error ="";
        foreach($handel as $v){
            if($v['status'] == 1){
                $sucess .=$v['id'].":".$v['tips']."<br />"; 
            }
            if($v['status'] == 0){
                $error =$v['id'].":".$v['tips']."<br />"; 
            }
        }
        if(!empty($error)){
             $error = "错误的:<br />{$error}";
             showJson(array('boolen'=>1, 'message'=>$sucess."<br />".$error));
        }else{
             showJson(array('boolen'=>1, 'message'=>$sucess));
        }
        
    }


    public function noBankFundAccount($url){
		if($url){
			$this->url = urldecode($url);
			$this->title = "我的账户-账户概况-提现-未绑定";
			$this->keys = "提现-未绑定";
		} else {
			$this->url = U('Account/User/account');
			$this->title = "我的账户-账户概况-添加银行卡";
			$this->keys = "添加银行卡";
		}
		
		$this->real_name = $this->loginedUserInfo['real_name'];
		$payment = service('Payment/Payment');
		$type = PaymentService::TYPE_XIANXIA;
		$payment = $payment->init($type);
		$this->bankList = $payment->getBankList();
		$this->display();
	}
	public function getFundAccount(){
		$title = (int)$this->_request('title');//id
		$id = (int)$this->_request('id');//id
		$url = (int)$this->_request('url');//url
		$title = ($title == 2)?"编辑":"添加";
		if($url){
			$this->url = urldecode($url);
			$this->title = "我的账户-账户概况-提现-未绑定";
			$this->keys = "提现-未绑定";
		} else {
			$this->url = intval($this->_request('cashout')) == 1 ? U('Payment/PayAccount/getApplyCashout') : U('Account/User/account');
			$this->title = "我的账户-账户概况-添加银行卡";
			$this->keys = "添加银行卡";
		}
		
		$data = D('Payment/PayAccount')->getFundAccount($this->loginedUserInfo['uid'], $id);
		$data['account_no'] = preg_replace('/\s*/', '', $data['account_no']);
		$payment = service('Payment/Payment');
        $bankList = PaymentService::$bankList;
//		foreach ($bankList as $k=>$v){
//            if($v['code'] == 'PSBC') unset($bankList[$k]);
//        }
		$this->bankList = $bankList;
		$this->real_name = $this->loginedUserInfo['real_name'];
		$this->uid_type = $this->loginedUserInfo['uid_type'];
		$this->id = $id;
		$this->title = $title;
		$this->data = $data;
		if(IS_AJAX){
			$this->display();
		}else{
			$this->display('editBankCard');
		}
	}
	
	/**
	 * 获取盛付通的province
	 */
	function getSftCity(){
		$citys = M('dict_city')->where('pid = 1 AND level > 0')->order('display_order')->select();
		$_citys = array();
		if($citys){
			foreach($citys as $key => $value){
				$_key = md5($value['privince_area']);
				if(!array_key_exists(md5($_key), $_citys)){
					$_citys[$_key]['name'] = $value['privince_area'];
				}
				$_citys[$_key]['child'][] = $value;
			}
			$_citys = array_values($_citys);
		}
		$this->ajaxReturn($_citys);
	}
	
	/**
	 * 根据盛付通provinceid获取城市列表
	 */	
	function getSftCitysById(){
		$provinceId = intval($_REQUEST['provinceId']);
		$provinces = array();
		if($provinceId){
			$where['pid']  = array('eq', $provinceId);
			$where['level']  = array('gt', 0);
			$provinces = M('dict_city')->where($where)->order('display_order,code')->select();
		}
		$this->ajaxReturn($provinces);
	}
	
	/**
	 * 根据关键词搜索银行列表
	 */
	function getBankListByKey(){
		$return = array('count' => 0, 'data' => array());
		$bankCode = $this->_request('bankCode', array('trim'));
		if($bankCode){
			$provinceId = (int)$this->_request('bank_province');
			$cityId = (int)$this->_request('bank_city');
			$keyWord = $this->_request('keyWord', array('trim'));
			
			$bankCode && $where['bank_code']  = array('eq', $bankCode);
			$provinceId && $where['province']  = array('eq', $provinceId);
			$cityId && $where['city']  = array('eq', $cityId);
			$keyWord && $where['bank_name'] = array('like', '%'.$keyWord.'%');
			
			$where['is_active'] = array('eq', 1);
			$limit = 100;
			
			$bankList = M('bank_sub_branch')->where($where)->limit($limit)->select();
			if($bankList){
				$return = array(
						'count' => count($bankList),
						'data' => $bankList
				);
			}
		}
		
		$this->ajaxReturn($return);
	}

	//增/改三方账户信息
	public function saveFundAccount(){
		$account_no = $this->_post('account_no');//银行账户
		$payment = service('Payment/Payment');
		$channel = PaymentService::TYPE_XIANXIA;//默认提现方式是线下的
		$bank = $this->_post('bank');//银行code，线下银行list
		$bank_name = $this->_post('bank_name');//银行名称
		$sub_bank = $this->_post('sub_bank');//银行支行名称
		$sub_bank_id = intval($this->_post('sub_bank_id'));//银行支行id
		$id = (int)$this->_post('id');//修改id
		
		$acount_name = $account_no;
		
		$bank_province = (int)$this->_post('bank_province');
		$bank_city = (int)$this->_post('bank_city');
		$is_default = isset($_POST['is_default']);
		
		$account_no = preg_replace("/^( )+|( )+$/",'',$account_no);
		$account_no = preg_replace("/^(　)+|(　)+$/",'',$account_no);
		$user = M("user"); // 实例化User对象
		// 手动进行令牌验证
		if (!$user->autoCheckToken($_POST)){
			$result = array('boolen'=>0, 'message'=>'异常操作');
			showJson($result);
		}
		
		if(!$bank && !$bank_name){
			$result = array('boolen'=>0, 'message'=>'参数异常');
			showJson($result);
		}
		
		if(!$this->loginedUserInfo['is_id_auth']){
			$result = array('boolen'=>0, 'message'=>'您需要先进行实名认证');
			showJson($result);
		}
		
		if(!$account_no){
			$result = array('boolen'=>0, 'message'=>'请先输入账号');
			showJson($result);
		}
		if (!checkPureNumber($account_no)) {
			$result = array('boolen'=>0, 'message'=>'银行卡号非法');
			showJson($result);
		}
		//校验银行卡
		$checkBankNoValid = true;
		if($id){
			$oldBankId = M('fund_account')->where(array('account_no' => $account_no))->getField('id');
			$checkBankNoValid = !($oldBankId == $id);
		}
		$checkObj = service('Account/Check');
		if(!$checkObj->checkBankCardValid($account_no, $this->loginedUserInfo, $checkBankNoValid)){
			$result = array('boolen'=>0, 'message' => $checkObj->getError());
			showJson($result);
		}
		
		
		if(!$bank_province || !$bank_city){
			$result = array('boolen'=>0, 'message'=>'请先输入开户城市');
			showJson($result);
		}
		
		if(!$sub_bank || !$sub_bank_id){
			$result = array('boolen'=>0, 'message'=>'请先输入开户行');
			showJson($result);
		}
		
		$list = PaymentService::$bankList;
		$check = false;
		foreach($list as $value){
			if($value['code'] == $bank){
				$bank_name = $value['name'];
				$check = true;
			}
		}
		if(!$check){
			$result = array('boolen'=>0, 'message'=>'参数异常');
			showJson($result);
		}
		try{
            $user = M('user')->field('uid_type,is_id_auth')->where(array('uid'=>$this->loginedUserInfo['uid']))->find();
			$count = M('fund_account')->field('count(1) as count')->where(array("uid"=>$this->loginedUserInfo['uid']))->find();
			$count = $count['count'];
            if($user['uid_type'] != 2) {
                if (!$id && $count >= C('FUND_ACCOUNT_MAX')) {
                    showJson(array('boolen' => 0, 'message' => "最多只能绑定" . C('FUND_ACCOUNT_MAX') . "张银行卡", 'count' => $count));
                    exit;
                }
            }else {
                if($user['is_id_auth'] != 1){
                    showJson(array('boolen' => 0, 'message' => "企业信息没有审核通过，绑卡失败"));
                    exit;
                }
                if (!$id && $count >= C('CORP_FUND_ACCOUNT_MAX')) {
                    showJson(array('boolen' => 0, 'message' => "最多只能绑定" . C('CORP_FUND_ACCOUNT_MAX') . "张银行卡", 'count' => $count));
                    exit;
                }
            }
			$result = service('Payment/PayAccount')->saveFundAccount($this->loginedUserInfo['uid'], $account_no, $channel, $acount_name, $bank, $bank_name, $bank_province, $bank_city, $sub_bank_id, $is_default, $sub_bank, $id);
			$result['count'] = $count;
			$result['account_no'] = $account_no;
			$reuult['bank_name'] = $bank_name;
            $result['type'] = $bank;

			showJson($result);
			exit;
		} catch(Exception $e){showJson(array('boolen'=>0, 'message'=>$e->getMessage(), 'count'=>$count));}
	}
	
	/**
	 * 手机认证-删除三方账户信息 认证动态码
	 * @access public
	 * @return boolen：1-成功 0-失败 message：消息
	 */
	function sendDelFundAuthCode(){
// 		$mobile = $this->_request('mobile');
		$user = M('user')->find($this->loginedUserInfo['uid']);
		D("Account/MobileValidateCode")->sendMobileCode($user['mobile'], $this->loginedUserInfo['uid'], MobileValidateCodeModel::CODE_TYPE_CHECK_FUND
			, MobileValidateCodeModel::CODE_TYPE_CHECK_FUND_NAME);
		if(MyError::hasError()){
			jsonReturn(0,MyError::lastError(),0);
		}else{
			jsonReturn(0,'动态码发送成功!');
		}
	}
	
	/**
	 * 解除/删除银行卡信息
	 * @param string id id
	 * @param string pwd 支付密码
	 * @param string code 手机动态码
	 * @access public
	 * @return boolen：1-成功 0-失败 message：消息
	 */
	public function delFundAccount(){
		$id = (int)$this->_post('id');//id
		$pwd = $_POST['pwd'];//密码
		$code = $this->_post("code");
		
		$user = M('user')->find($this->loginedUserInfo['uid']);
		$accountData = M('user_account')->find($this->loginedUserInfo['uid']);
		if($accountData['pay_password'] != pwd_md5($pwd)){
			showJson(array("boolen"=>0, "message"=>"支付密码输入错误"));
			exit;
		}
		if(!D("Account/MobileValidateCode")->validateMobileCode($user['mobile'],$code,MobileValidateCodeModel::CODE_TYPE_CHECK_FUND,120)){
			jsonReturn(0,MyError::lastError(),0);
		}
		try{
			$result = service('Payment/PayAccount')->delFundAccount($this->loginedUserInfo['uid'], $id);
            unset($result['data']);
            //删除银行卡后账户概况显示银行卡数量实时更新
            $key_bank_amount = 'bank_amount_'.$this->loginedUserInfo['uid'];
            S($key_bank_amount,null);
			showJson($result);
		}catch(Exception $e){ showJson(array("boolen"=>0, "message"=>$e->getMessage()));}
	}
	
	//激活三方账户信息
	public function activeFundAccount(){
		$id = (int)$this->_post('id');//id
		try{
			$result = D('Payment/PayAccount')->activeFundAccount($this->loginedUserInfo['uid'], $id);
			showJson($result);
		}catch(Exception $e){return showJson(array("boolen"=>0, "message"=>$e->getMessage()));}
	}
	//禁用三方账户信息
	public function unactiveFundAccount(){
		$id = (int)$this->_post('id');//id
		try{
			$result = D('Payment/PayAccount')->unactiveFundAccount($this->loginedUserInfo['uid'], $id);
			showJson($result);
		}catch(Exception $e){return showJson(array("boolen"=>0, "message"=>$e->getMessage()));}
	}
	
	/**
	 * 发送手机验证码
	 * @param unknown $mobile
	 */
	public function sendCashoutMobilecode(){
		$out_account_id = (int)$this->_post("out_account_id");
		$loan_account_id = (int)$this->_post("loan_account_id");
		$money = $this->_post("money");
		if(!$money) ajaxReturn('',"请输入金额",0);
		if(!is_numeric($money)) ajaxReturn('',"金额应该是数字",0);
		if (!$out_account_id && !$loan_account_id) ajaxReturn('', "请先选择银行卡号", 0);
		
		$service = service("Account/Validate");
		$uid = $this->loginedUserInfo['uid'];
		$user = M('user')->find((int)$uid);
		if(!$user) ajaxReturn('',"用户不存在",0);
		if ((!$user['is_mobile_auth'] || !$user['mobile']) && !$this->loginedUserInfo['role']) ajaxReturn('', "请先手机认证", 0);
		$fund_account = M('fund_account')->where(array("uid"=>(int)$uid, "id"=>$out_account_id))->find();
		if (!$fund_account) {
			$fund_account = M('loanbank_account')->where(array("uid" => (int)$uid, "id" => $loan_account_id))->find();
			if (!$fund_account) {
				ajaxReturn('', "参数异常", 0);
			}
		}
		$bank_no = substr($fund_account['account_no'],-4,4);
		
		$service->sendCashout2MobileCode($user['mobile'], $user['uid'], array($bank_no, $money."元"));
		if(MyError::hasError()){
			ajaxReturn('',MyError::lastError(),0);
		}else{
			ajaxReturn('','验证码发送成功!');
		}
	}

	/**
	 * 显示跟踪
	 * @return [type] [description]
	 */
	function shwoTrace(){
		$id = (int) $this->_request('id');
		service('Payment/XianxiaRecharge');
		$info = M('xianxia_recharge')->where(array('id'=>$id))->find();
		$this->info = $info;
		$this->display("showtrace");
	}
	
	/**
	 * 获取提现记录的备注
	 */
	function getCashoutRemark(){
		$id = intval($this->_request('id'));
		$remark = M('cashout_remark')->where('apply_id = '.$id)->order('id DESC')->select();
		$this->cashoutRemark = $remark;
		$this->display();
	}

    /**
     * 提现原因反馈
     * @return bool
     */
    function cashoutSuccess(){
        $data = array();
        $data['uid'] = $this->loginedUserInfo['uid'];
        $data['question_id'] = I("post.code_no");
        $data['comment'] = I("post.comment", '', 'trim');
        $data['ctime'] = time();
        $cid = M("user_cashout_feedback")->add($data);
        if($cid){
            ajaxReturn('', '感谢您的支持，我们会继续努力，不断提升您的投资体验');
        }
        ajaxReturn('','error',0);
    }

    /**
     * 老版提现退回记录
     */
//    function cashoutRollback()
//    {
//        $cashoutId = intval($this->_request('cashoutId'));//提现id
//        if ($cashoutId<=0) ajaxReturn('','请选择一条提现申请记录',0);
//        $rollbackTime = $this->_request('RollbackTime');//退回时间
//        $redoTime = $this->_request('RedoTime');//重新支付时间
//        try{
//            $model=M('cashout_apply');
//            $model->startTrans();
//            $data=array();
//            $data['rollback_status']=1;
//            $data['id']=$cashoutId;
//            $model->save($data);
//
//            $data=array();
//            $data['apply_id']=$cashoutId;
//            $data['rollback_time']=strtotime($rollbackTime);
//            $data['redo_time']=strtotime($redoTime);
//            $data['ctime']=time();
//            M('cashout_rollback')->add($data);
//            $model->commit();
//            ajaxReturn('','退回操作记录成功',1);
//        }
//        catch (Exception $e) {
//            $model->rollback();
//            ajaxReturn('','退回操作记录失败'.$e->getMessage(),0);
//        }
//    }

    /**
     * 新版提现退回记录
     * 2015527 start
     */
    function cashoutRollback()
    {
        $cashoutId = intval($this->_request('cashoutId'));//提现id
        if ($cashoutId<=0) ajaxReturn('','请选择一条提现申请记录',0);
        $rollbackTime = $this->_request('RollbackTime');//退回时间
        $redoTime = $this->_request('RedoTime');//重新支付时间
        $backreason = $this->_request('backreason');//退回原因
        try{
            $model=M('cashout_apply');
            $model->startTrans();
            $data=array();
            $data['rollback_status']=1;
            $data['id']=$cashoutId;
//            $model->save($data);

            $data=array();
            $data['apply_id']=$cashoutId;
            $data['rollback_time']=strtotime($rollbackTime);
            $data['redo_time']=strtotime($redoTime);
            $data['backreason']= $backreason;
            $data['ctime']=time();
            M('cashout_rollback')->add($data);
            $model->commit();
            ajaxReturn(array('cashoutId'=>$cashoutId),'退回操作记录成功',1);
        }
        catch (Exception $e) {
            $model->rollback();
            ajaxReturn('','退回操作记录失败'.$e->getMessage(),0);
        }
    }

    //2015527展示提现优化列表
    function  cashoutRollback_list(){
        $cashoutId = intval($this->_request('cashoutId'));//提现id
        $list=M('cashout_rollback')->where(array('apply_id'=>$cashoutId))->select();
        $this->assign('list',$list);
        $this->assign('cashoutId',$cashoutId);
        $this->display('getRollbackList');
    }

    //2015527处理提现优化修改
    function  modifyRollback(){
        $id=intval($this->_request('id'));
        $rollback=M('cashout_rollback')->where(array('id'=>$id))->find();
        $this->assign('rollback',$rollback);
        $this->display('rollbackEdit');
    }
    //2015527end

  function  cashoutRollback_list_edit(){
    $id=intval($this->_request('id'));
    $cashoutId=intval($this->_request('cashoutId'));
    $data['rollback_time']=strtotime($this->_request('RollbackTime'));//退回时间
    $data['redo_time'] =strtotime($this->_request('RedoTime'));//重新支付时间
    $data['backreason']= $this->_request('backreason');
    $list_edit=M('cashout_rollback')->where(array('id'=>$id))->save($data);
    if($list_edit){
     ajaxReturn(array('cashoutId'=>$cashoutId),'记录成功',1);
    }else{
        ajaxReturn('','未修改',0);
    }

  }

    /**
     * 获取人工提现渠道信息
     */
    function getChannelInfo()
    {
        $cashoutId = intval($this->_request('cashoutId'));//提现id
        if ($cashoutId<=0) ajaxReturn('','请输入提现申请记录ID',0);
        $result=M('cashout_apply')->where(array('id'=>$cashoutId))->field('manual_channel')->find();
        if ($result) {
            echo $result['manual_channel'];
        }else {
            echo '没有人工处理渠道记录';
        }
    }

    /**
     * 获取提现成功后的退回记录
     */
    function getRollbackInfo()
    {
        $cashoutId = intval($this->_request('cashoutId'));//提现id
        if ($cashoutId<=0) ajaxReturn('','请输入提现申请记录ID',0);
        $result=M('cashout_rollback')->where(array('apply_id'=>$cashoutId))->find();
        if ($result) {
            $result['rollback_time']=date('Y-m-d',$result['rollback_time']);
            $result['redo_time']=date('Y-m-d',$result['redo_time']);
            //ajaxReturn($result,'',1);
            echo '<p>退回时间：'.$result['rollback_time'].'</p>';
            echo '<p>重新支付时间：'.$result['redo_time'].'</p>';
        }else {
            //ajaxReturn('','没有退回记录',0);
            echo '没有退回记录';
        }
    }

    /*
     * 退回弹出框
     */
    function rollback(){
        $cashoutId = intval($this->_request('cashoutId'));//提现id
        if ($cashoutId<=0) ajaxReturn('','请输入提现申请记录ID',0);
        $this->assign('cashoutId',$cashoutId);
        $this->display();
    }

    /**
     *  联动优势充值对账单查询,type='cashout',为提现对账单
     */
    public function RechargeCheckAccount(){
        $date = I('day',date('Ymd',strtotime('-1 day')));
        $type = I('type','');
        $payType = 'liandong';
        $token = I('token');
        $payment = service('Payment/Payment')->init($payType);
        try{
            if($token != 'z3brsNjguMi') throw_exception('非法请求');
            $ret = $payment->payment->checkAccount($date,$type);
            echo $ret;
        }catch (Exception $e){
            echo $e->getMessage();
        }
    }

	/*
	 * 浙商充值页面
	 */

	public function zsRechargePage() {
		$uid = $this->loginedUserInfo['uid'];
		$user = M("user")->find($uid);
//		 if ($user['zs_status'] != 2) {
//		 	$this->redirect("Payment/PayAccount/rechargePage");
//		 }
		$pay_account_model = D("Payment/PayAccount");
		$userAccount = $pay_account_model->find($this->loginedUserInfo['uid']);
		$rechare_bank = M("recharge_bank_no")->where(array('uid'=>$uid, 'zs_bankck'=>1))->find();

		$mycode = $rechare_bank['bank'];
		$bank_model = D('Mobile2/Bank');
		$bank_channel = $bank_model->getzsBankdesc($mycode);
		$bank_money_limit = $bank_model->getZsBankLimit($mycode);//充值日限额
		$money_limit = $bank_money_limit[$mycode]['day'];
		$money_single = $bank_money_limit[$mycode]['single'];

		$sum = $pay_account_model->getTodatInSum($uid);
		$sum = bcdiv ($sum ,  100 ,  2 );
		$user_limit = $money_limit - $sum;
		$user_limit = $user_limit > 0 ? $user_limit : 0;
		$remain_money = humanMoney(($userAccount['zs_amount'] + $userAccount['zs_reward_money']),2,false);
		D('Account/User')->addUserloginsession('user_limit',$user_limit);
		D('Account/User')->addUserloginsession('money_single',$money_single);
		$zhuanzhang_url = M('xianxia_recharge_banklist')->where( array('my_code'=>$mycode) )->getField('url');
//		if( $user['is_xia_recharge'] == 1 ){
			$fund = M('fund_account')->where( array('uid'=>$uid, 'zs_bankck'=>1))->field('account_no, bank_name')->find();
			$fund['real_name'] = $user['real_name'];
			$fund['zs_account'] = $user['zs_account_id'];//存管户
			$this->assign('xian_xia', $fund);//线下转账账户信息
//		}
		$recharge_payment = service('Payment/PayAccount')->getPayment( 'recharge' );
		$user_status = service("Account/Account")->userStatus($this->loginedUserInfo);
		$this->assign("userStatus",$user_status);
		$this->assign("zhuanzhang_url",$zhuanzhang_url);
		$this->assign('bank_channel',$bank_channel);//
		$this->assign('user_limit',$user_limit);//用户可用额度
		$this->assign('min_money',$recharge_payment['min_money']);//最小充值额度
		$this->assign('money_single',$money_single);//单笔限额
		$this->assign('bank_last4',substr($rechare_bank['account_no'], -4));
		$this->assign('mobile',substr_replace($rechare_bank['mobile'],'****',3,4));
		if($user['uid_type'] != 2){
			$this->assign('remain_money',$remain_money);//账户余额
		}
		$this->assign('callbacktimes',5);//用户可用额度
		$this->assign('is_xia_recharge',1);//1=支持线下转账 $user['is_xia_recharge']
		$this->display();
	}

	/**
	 * 浙商发送充值短信
     */
	public function zsSmsSend() {
		$uid = $this->loginedUserInfo['uid'];
		$user = M("user")->find($uid);
		if ($user['zs_status'] != 2) {
			$res = array('boolen'=>0,'message'=>'正在开户中','data'=>'');
			showJson($res);
		}
		$mobile = $user['zs_bank_mobile'];
		$payType = "ZheShang";
		$payment = service('Payment/Payment')->init($payType);
		$SmsType = 4;
		$res = $payment->sendSmscode($mobile,$SmsType);
		showJson($res);
	}

	public function zsRechargeResult() {
		$uid = $this->loginedUserInfo['uid'];
		$user = M("user")->find($uid);
		$ticket_no = $this->_request('ticket_no')?$this->_request('ticket_no'):'';
		if (empty($ticket_no)) {
			throw_exception("订单异常1");
		}
		$pay_account_model = D("Payment/PayAccount");
		$userAccount = $pay_account_model->find($uid);
		$ticket = M('out_ticket')->where(array('ticket_no'=>$ticket_no,'uid'=>$uid))->find();
		if (!$ticket) {
			throw_exception("权限异常");
		}
		//取图片
		$baner_model = D('Index/Banner');
		$jpg = $baner_model->getBanner(78);
		$CorpMaterial_model = D('Application/CorpMaterial');
		$jpg_img = $CorpMaterial_model->parseAttach($jpg['img']);
		$jpg_url = $jpg_img['url'];
		if ($jpg_url != ''){
			$this->assign('jpg_url',$jpg_url);
			$this->assign('jump_url',$jpg['href']);
		}
		$remain_money = humanMoney(($userAccount['amount'] + $userAccount['reward_money']+$userAccount['zs_amount'] + $userAccount['zs_reward_money']),2,false);
		if($user['uid_type'] != 2){
			$this->assign('remain_money',$remain_money);//账户余额
		}
		$add_amount = bcdiv($ticket['amount'] , 100 , 2);
		$this->assign('ticket_no',$ticket_no);
		$this->assign('amount',$add_amount);//充值金额
		$this->assign('status',$ticket['status']);
		$this->assign('fail_msg',$ticket['call_back_str'] ? $ticket['call_back_str'] : '银行系统异常');
		$this->display();
	}

	//提现短信
	public function zsCashOutSmsSend() {
		$uid = $this->loginedUserInfo['uid'];
		$user = M("user")->find($uid);
		if ($user['zs_status'] != 2) {
			$res = array('boolen'=>0,'message'=>'未开户完成,无法提现','data'=>'');
			showJson($res);
		}
		$mobile = $user['zs_bank_mobile'];
		$payType = "ZheShang";
		$payment = service('Payment/Payment')->init($payType);
		$SmsType = 5;
		$res = $payment->payment->sendCode($mobile,$SmsType);
		showJson($res);
	}
	
	/*
	 * 获取浙商账户资金信息
	 * @param string $type | 999999 表示通用账户
	 */
	public function zsAccountMoney(){
		$remark = I('type', 'queue account money');//通用还是专用
		$uid = $this->loginedUserInfo['uid'];
		$user = M("user")->find($uid);
		$payType = "ZheShang";
		$payment = service('Payment/Payment')->init($payType);
		$arr['bindSerialNo'] = $this->loginedUserInfo['zs_bind_serial_no'];
		$arr['ecardNo'] = $this->loginedUserInfo['zs_account_id'];
		$res = $payment->payment->queryAccountMoney( $arr, $remark );
		$can_money = 0;
		if( $res['boolen'] == 1 ){
			$can_money = $res['data']['value']['withdrawAmount'];//可提现金额,单位分
			$total_money = $res['data']['value']['totalAmount'];//总金额,单位分
		}
		ajaxReturn(array('money'=>$can_money, 'money_view'=>humanMoney($can_money, 2, false),'total_money'=>$total_money, 'total_money_view'=>humanMoney($total_money, 2, false)));
	}

	//浙商账户转移充值
	function zsTranferApply(){
		$cacheKey = 'zsTranferApply_'.$this->loginedUserInfo['uid'];
		if(cache($cacheKey) == '1'){
			$result = array('boolen'=>0, 'message'=>'请勿频繁操作');
			showJson($result);
			exit;
		}
		cache($cacheKey, '1', array('expire'=>1));
		$money = I('money');
		if( $money <=0 ){
			ajaxReturn('', '请输入正确的充值金额', 0);
		}
		$ret = service('Payment/PayAccount')->zsAccountTransferApply($this->loginedUserInfo['uid'], $money);
		if($ret === true){
			ajaxReturn('', '存管账户充值申请成功');
		}
		ajaxReturn('', $ret, 0);
	}

	//浙商账户转移充值状态查询
	function zsTranferQuery(){
		$order_no = I('order_no');
		$data = M('user_account_move')->where( array('order_no'=>$order_no) )->field('move_amount')->find();
		if( !$order_no  || !$data){
			ajaxReturn('', '请输入正确的订单号', 0);
		}
		$ret = service('Payment/PayAccount')->zsAccountTransferDeal( $order_no );
		if($ret === true){
			$account = D('Payment/PayAccount')->accountInfo( $this->loginedUserInfo['uid'] );
			$account['move_amount'] = humanMoney($data['move_amount'], 2, false);
			$account['order_no'] = $order_no;
			ajaxReturn($account, '存管账户充值到账');
		}
		ajaxReturn('', $ret, 0);
	}

	//存管项目剩余可投金额
	public function zsGetPrjInvestMoney(){
		$ret = service('Financing/Prj')->zsGetPrjInvestMoney();
		ajaxReturn($ret, 'success');
	}
	//取消充值申请
	public function cancelApply(){
		$id = I('GET.id');
		$uid = $this->loginedUserInfo['uid'];
		$ret = D("Payment/XianxiaRecharge")->cancelApply($id,$uid);
		if($ret['boolen']){
			ajaxReturn('','处理成功' );
		}
		ajaxReturn($ret['message'], 0,0);

	}

}

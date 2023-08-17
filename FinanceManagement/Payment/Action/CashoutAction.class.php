<?php
class CashoutAction extends BaseAction {

	/**
	 * 批量提现结果通知接口
	 */
	public function callBackUrl(){
		if(IS_POST){
			Log::write('提现接口回调数据'.  json_encode($_POST), Log::NOTICE);
			$payType = I('payType');
			$paymentService = service('Payment/Payment');
			if(!$payType){
				$payType = PaymentService::TYPE_SHENFUT;//盛付通
			}
			$payment = $paymentService->init($payType)->payment;
			$config = $payment->getConfig();

			$batchNo = I('batchNo');//批次号
			$statusCode = I('statusCode');//流程状态编码
			$statusName = I('statusName');//流程描述
			$fileName = I('fileName');//文件名
			$charset = I('charset','utf-8');//编码一定要小写
			$signType = I('signType');//报文验名类型
			$sign = I('sign');//签名
			$resultCode = I('resultCode');//批次结果编码
			$resultName = I('resultName');//批次结果描述
			$resultMemo = I('resultMemo');//备注
			$sign_org = 'batchNo='.$batchNo.'resultCode='.$resultCode.
						'resultMessage='.$resultCode.$config['MsgPwd'];//签名参数顺序
			$sign_new = strtoupper(md5($sign_org));//大写
			//echo 'sign='.$sign;
			//echo 'sign_org='.$sign_org;
			//echo 'sign new ='.$sign_new;
			
			/**
			 * RESPONSE数据
			 */
			$return_data = array(
				'sign'=>'',
				'signType'=>'MD5',
				'resultCode'=>$resultCode,
				'resultMessage'=>$resultName,
			);
			/**
			 * 验证签名
			 */
			if($sign_new == $sign){
				$return_data = array(
					'resultCode'=>'OK',
					'resultMessage'=>'OK',
				);
				import_addon("libs.Queue.Queue");
				 $queue_array = array(
					 'id'=>$batchNo.time(),
					 'payType'=>$payType,
					 'batchNo'=>$batchNo,
				 );
				Queue::create('autoCashout')->push($queue_array); 
			}
			/**
			 * 输出xml格式数据
			 * 样例：
			 * <result>
			 * <sign></sign>
			 * <signType>MD5</signType>
			 * <resultCode>ok</resultCode>
			 * <resultMessage>ok</resultMessage>
			 * </result>
			 */
			$this->ajaxReturn($return_data,'xml','result');
		}
	}


    //获取这个人的自由额度
    public function getTCashoutAmount(){
        $cashoutAmount = service("Payment/PayAccount")->getTCashoutAmount((int)$this->loginedUserInfo['uid']);
        $result = array('boolen'=>1, 'cashoutAmount'=>$cashoutAmount);
        showJson($result);
    }

    //获取这个银行卡的总提现额度
    public function getCashoutAmountByNo(){
        $fund_account_id = I('fund_account_id');//银行卡id
        $cashoutAmount = service("Payment/PayAccount")->getCashoutAmountByNo((int)$this->loginedUserInfo['uid'], $fund_account_id);
        $result = array('boolen'=>1, 'cashoutAmount'=>$cashoutAmount);
        showJson($result);
    }

    //获取这个银行卡的手机提现额度
    public function getMCashoutAmountByNo(){
        $fund_account_id = I('fund_account_id');//银行卡id
        $cashoutAmount = service("Payment/PayAccount")->getMCashoutAmountByNo($fund_account_id);
        $result = array('boolen'=>1, 'cashoutAmount'=>$cashoutAmount);
        showJson($result);
    }

    //易宝提现回调
    public function callBackNotify(){
        $post = $_REQUEST;
        $post_str = implode('', $post);
        $cacheKey = "cashout_".md5( $post_str );
        if(cache($cacheKey) == time()){
            echo 'Can not frequently refresh';
            exit;
        }
        cache($cacheKey, time(), array('expire'=>10));
        $paymentService = service('Payment/Payment');
        $payment = $paymentService->init($post['payType'])->payment;
        $result = $payment->cashOutMoneyCallBack( $post );
        $res = $this->dealCallBack( $result );
        if( $res ){
            echo $result['result'];
        }else{
            echo 'FAILURE';
        }
    }

    private function dealCallBack( $arr ){
        $cashoutLogModel = M('cashout_log');
        $cashoutModel = D('Payment/CashoutApply');
        $where = array('cid' => $arr['requestid']);
        $loged_status = $cashoutLogModel->where($where)->getField('status');
        $status_arr = array('SUCCESS'=>'付款成功', 'FAILURE'=>'付款失败', 'REFUND'=>'付款退回', 'UNKNOW'=>'未知');
        $remark = '支付状态：' . $status_arr[$arr['status']] . ' 备注：';
        $remark = isset($arr['err_info']) ? $remark . $arr['err_info'] : $remark;
        if($loged_status === null){
            $ret = $cashoutModel->where( array('id'=>$arr['requestid']) )->find();
            $real_name = M('user')->where( array('uid'=>$ret['uid']) )->getField('real_name');
            $data = array(
                'cid' => $arr['requestid'],
                'province' => $ret['bank_province'],
                'city' => $ret['bank_city'],
                'branchName' => $ret['sub_bank'],
//                'bankName' => $value->bankName,
                'bankUserName' => $real_name,
                'bankAccount' => $ret['out_account_no'],
                'amount' => $arr['amount'],
                'orderNo' => $arr['ybdrawflowid'],
                'payStatus' => $status_arr[$arr['status']],
                'ctime' => time(),
                'status' => 9,
                'remark' => $remark,
            );
            $res = $cashoutLogModel->add($data);
            unset($data);
        }
        //该提现付款成功 没处理过 status＝null 或者 没有成功处理的 status=2 需要处理一次
        //status ＝1付款成功 status＝8付款失败 都不需要再次处理
        $no_apply_status = array( 1=>1, 8=>8 );
        if($loged_status === null || (!isset($no_apply_status[$loged_status]))){
            if ($arr['status'] == 'SUCCESS') {
                $status = 1;
                $data = array(
                    'status'=>1,
                    'remark'=>$remark,
                    'payStatus' => $status_arr[$arr['status']],
                );
                $cashoutLogModel->where($where)->save($data);
                unset($data);
                $cashout_apply = $cashoutModel->macRollback($arr['requestid'], $status, $remark, $arr['ybdrawflowid']);
                $data = array(
                    'apply_status' => (int)$cashout_apply['boolen'],
                    'apply_remark' => $cashout_apply['message'],
                    'version' => array('exp', 'version+1'),
                );
                $res = $cashoutLogModel->where($where)->save($data);
                unset($data);
            }elseif ($arr['status'] == 'FAILURE' || $arr['status'] == 'REFUND' ){//包含REFUND：提现退回,
                $cashout_apply = $cashoutModel->macRollback($arr['requestid'], 0, $remark,  $arr['ybdrawflowid']);
                $data = array(
                    'status' => 8,
                    'payStatus' => $status_arr[$arr['status']],
                    'remark' => $remark,
                    'apply_status' => (int)$cashout_apply['boolen'],
                    'apply_remark' => $cashout_apply['message'],
                    'version' => array('exp', 'version+1'),
                );
                $res = $cashoutLogModel->where($where)->save($data);
            }else{//UNKNOW：未知
                $data = array();
                $data['status'] = 2;
                $data['payStatus'] = $status_arr[$arr['status']];
                $data['remark'] = $remark;
                $data['mtime'] = time();
                $data['version'] = array('exp', 'version+1');
                $res = $cashoutLogModel->where($where)->save($data);
            }
        }
        return $res;
    }
}
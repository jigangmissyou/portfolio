<?php

class CashoutService extends BaseService
{
    //处理实际提现
    public function deal($cashoutId, $dealUid, $dealStatus, $dealReason,$channel='')
    { //有事物
        $payment = service('Payment/Payment')->init(PaymentService::TYPE_XIANXIA);
        return $payment->cashout($cashoutId, $dealUid, $dealStatus, $dealReason, $channel);
    }

    /**
     * 获取提现处理的列表
     * @param $cond 数组形式的查询条件 array('status'=>1,'ctime'>12345678)
     * @param $parameter 翻页用参数
     * @param string $order
     * @param int $num 新增 查询个数
     * @return mixed
     */
    public function getList($cond, $parameter, $order = "", $num = 10, $page = '')
    {
        return D('Payment/CashoutApply')->getList($cond, $parameter, $order, $num, $page);
    }

    /**
     * 获取符合条件的提现请求数量
     * @param $cond 查询条件 数组形式
     */
    public function getCount($cond)
    {
        return D('CashoutApply')->getCount($cond);
    }


    //取消申请提现
    public function cancel($id, $uid)
    { //有事物
        return D('Payment/CashoutApply')->cancel($id, $uid);
    }


    /**
	 * $uid 处理人
	 * @param unknown_type $prj_id 项目id
	 * @param unknown_type $amount 实际支付金额 单位是分
	 * @param unknown_type $fee 平台手续费 单位是分
	 * @param unknown_type $account_id //银行账户id
	 * @param unknown_type $out_id // 流水号
	 * @param unknown_type $bak //备注
	 */
	public function prjPay($dealUid, $prj_id, $amount, $fee, $account_id, $out_id, $bak){// 没有事物
		//	echo $prj_id."|".$account_id;exit;
		if(!is_numeric($amount)){
			return array('boolen'=>0, 'message'=>'操作失败，参数异常');
		}
		if($amount < 0){
			return array('boolen'=>0, 'message'=>'操作失败，金额不能为为负数');
		}
		if(!$amount){
			return array('boolen'=>1, 'message'=>'操作成功');
		}
		if(!$prj_id){
			return array('boolen'=>0, 'message'=>'项目id不存在');
		}
		if(!$account_id){
			return array('boolen'=>0, 'message'=>'银行账户不存在，请指定银行账户');
		}
		$prj_id = (int)$prj_id;

		$prj = D('Financing/Prj')->find($prj_id);
		if(!$prj) throw_exception("参数异常，项目不存在");

        $account_custom = false;
        if(is_array($account_id) && $account_id['bank'] && $account_id['bank_name'] && $account_id['account_no']) { // 外部自定义账户
            $account_custom = true;
            $fund_account = $account_id;
        } else {
            $account_id = (int)$account_id;
            if($prj['tenant_id']){
                $fund_account = M('loanbank_account')->find($account_id);
                if(!$fund_account) throw_exception("参数异常，账户不存在");
            } else {
                $fund_account = M('fund_account')->find($account_id);
                if(!$fund_account) throw_exception("参数异常，账户不存在");
            }
        }

        $tenantId = service("Financing/Project")->getTenantIdByPrj($prj);
        // 		$tenantId = service("Payment/PayAccount")->getTenantAccountId($prj['dept_id']);
        $uid = $tenantId;

        if(!$account_custom && $fund_account['uid'] != $uid) throw_exception("参数异常，您没权限操作该账户");

		service('Payment/Payment');
// 		try{
        $CashoutModel = D('Payment/CashoutApply');
        $cashout_apply['uid'] = $uid;
        $cashout_apply['channel'] = PaymentService::TYPE_XIANXIA;
        $cashout_apply['bank'] = $fund_account['bank'];
        $cashout_apply['sub_bank'] = $fund_account['bank_name'];
        $cashout_apply['bak'] = $bak;
        $cashout_apply['free_times'] = 0;
        $cashout_apply['free_money'] = 0;
        $cashout_apply['out_account_no'] = $fund_account['account_no'];
        $cashout_apply['out_account_id'] = $account_id;
        $cashout_apply['money'] = $amount;
        $cashout_apply['fee'] = $fee;
        $cashout_apply['cost_saving'] = 0;
        $cashout_apply['status'] = CashoutApplyModel::STATUS_TODO;
        $cashout_apply['deal_uid'] = '';
        $cashout_apply['deal_reason'] = '';
        $cashout_apply['deal_time'] = '';
        $cashout_apply['ticket_id'] = '';
        $cashout_apply['prj_id'] = $prj_id;
        $cashout_apply['ctime'] = time();
        $cashout_apply['mtime'] = time();
        $cashout_apply['id'] = $CashoutModel->add($cashout_apply);
        if (!$cashout_apply['id']) throw_exception("数据异常PrjPay1");
        $result = D('Payment/CashoutApply')->deal($cashout_apply['id'], $dealUid, CashoutApplyModel::STATUS_SUCCESS, $prj['prj_type'] . "_pay", $out_id, $cashout_apply);
        if (!$result['boolen']) return $result;
        else {
            return array('boolen' => 1, 'message' => '操作成功', 'cashout_id' => $cashout_apply['id']);
        }
// 		} catch(Exception $e){
// 			return array('boolen'=>0, 'message'=>'操作失败, '.$e->getMessage(), 'cashout_id'=>'');
// 		}
    }
}
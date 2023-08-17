<?php
import_app("Payment.Model.CashoutApplyModel");
import_app("Financing.Model.PrjModel");
class AccountSearchService extends BaseService{
	//资金记录首页
	public function getMyRecordIndex($uid){
		$count_account7 = $count_account6 = $count_account5 = $count_account4 = $count_account3 = $count_account2 = $count_account1 = array('uid'=>$uid);
		D("Payment/PayAccount");
		$count_account1['ticket_id'] = array('exp',' is not NULL');
		$count_account1['cash_id'] = array('exp',' is NULL');
		$count_account2['ticket_id'] = array('exp',' is NULL');
		$count_account2['cash_id'] = array('exp',' is NULL');
		$count_account2['obj_type'] = array('not in', PayAccountModel::OBJ_TYPE_REPAY.",".PayAccountModel::OBJ_TYPE_REPAY_FREEZE.",".PayAccountModel::OBJ_TYPE_ROLLOUT
            .",".PayAccountModel::OBJ_TYPE_RAISINGINCOME
        				.",".PayAccountModel::OBJ_TYPE_REWARD_CASHOUT.",".PayAccountModel::OBJ_TYPE_REWARD_INVEST
        				.",".PayAccountModel::OBJ_TYPE_TRANSFER.",".PayAccountModel::OBJ_TYPE_EXPIRE_COUPON.",".PayAccountModel::OBJ_TYPE_ADD_COUPON.",".PayAccountModel::OBJ_TYPE_USE_COUPON
				.",".PayAccountModel::OBJ_TYPE_REWARD_CBACK.",".PayAccountModel::OBJ_TYPE_REWARD_IBACK);
//		$count_account3['obj_type'] = PayAccountModel::OBJ_TYPE_REPAY;
        $count_account3['obj_type'] = array('in', PayAccountModel::OBJ_TYPE_REPAY.",".PayAccountModel::OBJ_TYPE_REPAY_FREEZE.",".PayAccountModel::OBJ_TYPE_ROLLOUT.",".PayAccountModel::OBJ_TYPE_RAISINGINCOME);
		$count_account4['cash_id'] = array('exp',' >0');
		$count_account5['obj_type'] = array('in',PayAccountModel::OBJ_TYPE_REWARD_CASHOUT.",".PayAccountModel::OBJ_TYPE_REWARD_INVEST.",".PayAccountModel::OBJ_TYPE_REWARDFREEMONEY.",90001"
								.",".PayAccountModel::OBJ_TYPE_ADD_COUPON.",".PayAccountModel::OBJ_TYPE_EXPIRE_COUPON.",".PayAccountModel::OBJ_TYPE_USE_COUPON
								.",".PayAccountModel::OBJ_TYPE_REWARD_CBACK.",".PayAccountModel::OBJ_TYPE_REWARD_IBACK);
		$count_account6['obj_type'] = PayAccountModel::OBJ_TYPE_TRANSFER;
		$count_account7['to_free_money'] = array('exp',' <> from_free_money');
		
		$totalrecharge = D('Payment/AccountRecord')->field("COUNT(1) AS CNT")->where($count_account1)->find();
		$result['account']['totalrecharge'] = $totalrecharge['CNT'];
		$totalTouzhi = D('Payment/AccountRecord')->field("COUNT(1) AS CNT")->where($count_account2)->find();
		$result['account']['totalTouzhi'] = $totalTouzhi['CNT'];
		$totalHuikuan = D('Payment/AccountRecord')->field("COUNT(1) AS CNT")->where($count_account3)->find();
		$result['account']['totalHuikuan'] = $totalHuikuan['CNT'];
		$totalTixian = D('Payment/AccountRecord')->field("COUNT(1) AS CNT")->where($count_account4)->find();
		$result['account']['totalTixian'] = $totalTixian['CNT'];
		$totalJiangli = D('Payment/AccountRecord')->field("COUNT(1) AS CNT")->where($count_account5)->find();
		$result['account']['totalJiangli'] = $totalJiangli['CNT'];
		$totalZhuangrang = D('Payment/AccountRecord')->field("COUNT(1) AS CNT")->where($count_account6)->find();
		$result['account']['totalZhuangrang'] = $totalZhuangrang['CNT'];
		$totalFreeMoney = D('Payment/AccountRecord')->field("COUNT(1) AS CNT")->where($count_account7)->find();
		$result['account']['totalFreeMoney'] = $totalFreeMoney['CNT'];
		
		$result['account']['total'] = (int)$result['account']['totalrecharge'] + (int)$result['account']['totalTouzhi']
							 + (int)$result['account']['totalHuikuan'] + (int)$result['account']['totalTixian']
							 + (int)$result['account']['totalJiangli'] + (int)$result['account']['totalZhuangrang'];
		$result['account']['total'] = (string)$result['account']['total'];
		D("Payment/CashoutApply");
		$count_cashout4 = $count_cashout3 = $count_cashout2 = $count_cashout1 = array('uid'=>$uid);
		$count_cashout1['status'] = array("in", CashoutApplyModel::STATUS_TODO.','.CashoutApplyModel::STATUS_DEALING
					.','.CashoutApplyModel::STATUS_MAC_TODO.','.CashoutApplyModel::STATUS_MAC_DEALING.','.CashoutApplyModel::STATUS_MAC_CUSTOM);
		$count_cashout2['status'] = 2;
		$count_cashout3['status'] = 3;
		$count_cashout4['status'] = 4;
		$totalCTodo = D("Payment/CashoutApply")->field("COUNT(1) AS CNT")->where($count_cashout1)->find();
		$result['cashout']['totalCTodo'] = $totalCTodo['CNT'];
		$totalCSuccess = D("Payment/CashoutApply")->field("COUNT(1) AS CNT")->where($count_cashout2)->find();
		$result['cashout']['totalCSuccess'] = $totalCSuccess['CNT'];
		$totalCFailed = D("Payment/CashoutApply")->field("COUNT(1) AS CNT")->where($count_cashout3)->find();
		$result['cashout']['totalCFailed'] = $totalCFailed['CNT'];
		$totalCCanel = D("Payment/CashoutApply")->field("COUNT(1) AS CNT")->where($count_cashout4)->find();
		$result['cashout']['totalCCanel'] = $totalCCanel['CNT'];
		$result['cashout']['total'] = (int)$result['cashout']['totalCTodo'] + (int)$result['cashout']['totalCSuccess']
								+ (int)$result['cashout']['totalCFailed'] + (int)$result['cashout']['totalCCanel'];
		$result['cashout']['total'] = (string)$result['cashout']['total'];
		
		$count_xianxia4 = $count_xianxia3 = $count_xianxia2 = $count_xianxia1 = array('uid'=>$uid);
		$count_xianxia1['status'] = 1;
		$count_xianxia2['status'] = 2;
		$count_xianxia3['status'] = 3;
		$count_xianxia4['status'] = 4;
		$totalXTodo = D("Payment/XianxiaRecharge")->field("COUNT(1) AS CNT")->where($count_xianxia1)->find();
		$result['xianxia']['totalXTodo'] = $totalXTodo['CNT'];
		$totalXSuccess = D("Payment/XianxiaRecharge")->field("COUNT(1) AS CNT")->where($count_xianxia2)->find();
		$result['xianxia']['totalXSuccess'] = $totalXSuccess['CNT'];
		$totalXFailed = D("Payment/XianxiaRecharge")->field("COUNT(1) AS CNT")->where($count_xianxia3)->find();
		$result['xianxia']['totalXFailed'] = $totalXFailed['CNT'];
		$totalXTrack = D("Payment/XianxiaRecharge")->field("COUNT(1) AS CNT")->where($count_xianxia4)->find();
		$result['xianxia']['totalXTrack'] = $totalXTrack['CNT'];
		$result['xianxia']['total'] = (int)$result['xianxia']['totalXTodo'] + (int)$result['xianxia']['totalXSuccess']
							+ (int)$result['xianxia']['totalXFailed']+ (int)$result['xianxia']['totalXTrack'];
		$result['xianxia']['total'] = (string)$result['xianxia']['total'];
		return $result;
	}
	
	//提现收支记录
	public function getMyRecordList($uid, $status, $start_time, $end_time, $time_type){
		$parameter['status'] = $status;//状态 充值-1 投资-2 回款-3 提现-4 奖金-5 变现-6  资金服务抵用券-7 服务费-8
		$parameter['start_time'] = $start_time;//开始时间
		$parameter['end_time'] = $end_time;//结束时间
		$parameter['time_type'] = $time_type;//最近一周-week 最近一个月-month 最近六个月-6month
		
		D("Payment/PayAccount");
		$count_cond8 = $count_cond7 = $count_cond6 = $count_cond5 = $count_cond4 = $count_cond3 = $count_cond2 = $count_cond1 = array('uid'=>$uid);
		$condition = array('uid'=>$uid);
        if(visit_source() != 'pc') $condition['obj_type'] = array('neq',PayAccountModel::OBJ_TYPE_REPAY_FREEZE);
		if($status == 1){
			$condition['ticket_id'] = array('exp',' is not NULL');
			$condition['cash_id'] = array('exp',' is NULL');
		}
		$count_cond1['ticket_id'] = array('exp',' is not NULL');
		$count_cond1['cash_id'] = array('exp',' is NULL');
		
		if($status == 2){
			$condition['ticket_id'] = array('exp',' is NULL');
			$condition['cash_id'] = array('exp',' is NULL');
			$condition['obj_type'] = array('not in', PayAccountModel::OBJ_TYPE_REPAY.",".PayAccountModel::OBJ_TYPE_REPAY_FREEZE.",".PayAccountModel::OBJ_TYPE_ROLLOUT.",".PayAccountModel::OBJ_TYPE_RAISINGINCOME
					.",".PayAccountModel::OBJ_TYPE_REWARD_CASHOUT.",".PayAccountModel::OBJ_TYPE_REWARD_INVEST.",".PayAccountModel::OBJ_TYPE_REWARDFREEMONEY.",90001"
					.",".PayAccountModel::OBJ_TYPE_TRANSFER.",".PayAccountModel::OBJ_TYPE_EXPIRE_COUPON.",".PayAccountModel::OBJ_TYPE_ADD_COUPON.",".PayAccountModel::OBJ_TYPE_USE_COUPON
				.",".PayAccountModel::OBJ_TYPE_REWARD_CBACK.",".PayAccountModel::OBJ_TYPE_REWARD_IBACK.",".PayAccountModel::OBJ_PT_SERVICE_FEE);
		}
		$count_cond2['ticket_id'] = array('exp',' is NULL');
		$count_cond2['cash_id'] = array('exp',' is NULL');
		$count_cond2['obj_type'] = array('not in', PayAccountModel::OBJ_TYPE_REPAY.",".PayAccountModel::OBJ_TYPE_REPAY_FREEZE.",".PayAccountModel::OBJ_TYPE_ROLLOUT.",".PayAccountModel::OBJ_TYPE_RAISINGINCOME
				.",".PayAccountModel::OBJ_TYPE_REWARD_CASHOUT.",".PayAccountModel::OBJ_TYPE_REWARD_INVEST.",".PayAccountModel::OBJ_TYPE_REWARDFREEMONEY.",90001"
				.",".PayAccountModel::OBJ_TYPE_TRANSFER.",".PayAccountModel::OBJ_TYPE_EXPIRE_COUPON.",".PayAccountModel::OBJ_TYPE_ADD_COUPON.",".PayAccountModel::OBJ_TYPE_USE_COUPON
				.",".PayAccountModel::OBJ_TYPE_REWARD_CBACK.",".PayAccountModel::OBJ_TYPE_REWARD_IBACK.",".PayAccountModel::OBJ_PT_SERVICE_FEE);
		
		if($status == 3){
// 			$condition['obj_type'] = PayAccountModel::OBJ_TYPE_REPAY;
			$condition['obj_type'] = array('in', PayAccountModel::OBJ_TYPE_REPAY.",".PayAccountModel::OBJ_TYPE_REPAY_FREEZE.",".PayAccountModel::OBJ_TYPE_ROLLOUT.",".PayAccountModel::OBJ_TYPE_RAISINGINCOME);
		}
// 		$count_cond3['obj_type'] = PayAccountModel::OBJ_TYPE_REPAY;
		$count_cond3['obj_type'] = array('in', PayAccountModel::OBJ_TYPE_REPAY.",".PayAccountModel::OBJ_TYPE_REPAY_FREEZE.",".PayAccountModel::OBJ_TYPE_ROLLOUT.",".PayAccountModel::OBJ_TYPE_RAISINGINCOME);

        //临时加上 手机端不显示冻结回款这一项内容
        if(visit_source() != 'pc'){
            if($status == 3) $condition['obj_type'] = array('in', PayAccountModel::OBJ_TYPE_REPAY.",".PayAccountModel::OBJ_TYPE_ROLLOUT.",".PayAccountModel::OBJ_TYPE_RAISINGINCOME);
            $count_cond3['obj_type'] = array('in', PayAccountModel::OBJ_TYPE_REPAY.",".PayAccountModel::OBJ_TYPE_ROLLOUT.",".PayAccountModel::OBJ_TYPE_RAISINGINCOME);
        }

		if($status == 4){
			$condition['cash_id'] = array('exp',' >0');
		}
		$count_cond4['cash_id'] = array('exp',' >0');
		
		if($status == 5){
			$condition['obj_type'] = array('in',PayAccountModel::OBJ_TYPE_REWARD_CASHOUT.",".PayAccountModel::OBJ_TYPE_REWARD_INVEST.",".PayAccountModel::OBJ_TYPE_REWARDFREEMONEY.",90001"
								.",".PayAccountModel::OBJ_TYPE_ADD_COUPON.",".PayAccountModel::OBJ_TYPE_EXPIRE_COUPON.",".PayAccountModel::OBJ_TYPE_USE_COUPON
								.",".PayAccountModel::OBJ_TYPE_REWARD_CBACK.",".PayAccountModel::OBJ_TYPE_REWARD_IBACK);
		}
		$count_cond5['obj_type'] = array('in',PayAccountModel::OBJ_TYPE_REWARD_CASHOUT.",".PayAccountModel::OBJ_TYPE_REWARD_INVEST.",".PayAccountModel::OBJ_TYPE_REWARDFREEMONEY.",90001"
								.",".PayAccountModel::OBJ_TYPE_ADD_COUPON.",".PayAccountModel::OBJ_TYPE_EXPIRE_COUPON.",".PayAccountModel::OBJ_TYPE_USE_COUPON
								.",".PayAccountModel::OBJ_TYPE_REWARD_CBACK.",".PayAccountModel::OBJ_TYPE_REWARD_IBACK);
		
		if($status == 6){
			$condition['obj_type'] = PayAccountModel::OBJ_TYPE_TRANSFER;
		}
		$count_cond6['obj_type'] = PayAccountModel::OBJ_TYPE_TRANSFER;
		
		if($status == 7){
			$condition['to_free_money'] = array('exp',' <> from_free_money');
		}
		$count_cond7['to_free_money'] = array('exp',' <> from_free_money');
		
		if($status == 8){
			$condition['obj_type'] = PayAccountModel::OBJ_PT_SERVICE_FEE;
		}
		$count_cond8['obj_type'] = PayAccountModel::OBJ_PT_SERVICE_FEE;
		
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
		$result = array();
		if(!$error){
			//amount-余额  freeze_money-冻结 mod_money-收入/支出金额 in_or_out-1是收入2是支出 type-类型 ctime-时间 prj_series-产品系列 prj_name-项目名称 prj_no-项目no
			$list = service("Payment/PayAccount")->getRecordByUid($condition, $parameter);
			$arr = array();
			foreach($list['data'] as $item){
				if($item['ticket_id'] > 0 && !$item['cash_id']){
					$item['status'] = '1';
				}
				if(!$item['ticket_id'] && !$item['cash_id'] && $item['obj_type'] != PayAccountModel::OBJ_TYPE_REPAY
						&& $item['obj_type'] != PayAccountModel::OBJ_TYPE_RAISINGINCOME
                        && $item['obj_type'] != PayAccountModel::OBJ_TYPE_REPAY_FREEZE
                        && $item['obj_type'] != PayAccountModel::OBJ_TYPE_ROLLOUT
						&& $item['obj_type'] != PayAccountModel::OBJ_TYPE_REWARD_CASHOUT && $item['obj_type'] != PayAccountModel::OBJ_TYPE_REWARD_INVEST
						&& $item['obj_type'] != PayAccountModel::OBJ_TYPE_TRANSFER && $item['obj_type'] != PayAccountModel::OBJ_TYPE_ADD_COUPON
						&& $item['obj_type'] != PayAccountModel::OBJ_TYPE_USE_COUPON && $item['obj_type'] != PayAccountModel::OBJ_TYPE_EXPIRE_COUPON
						&& $item['obj_type'] != PayAccountModel::OBJ_TYPE_REWARD_CBACK && $item['obj_type'] != PayAccountModel::OBJ_TYPE_REWARD_IBACK){
					$item['status'] = '2';
				}
				if($item['obj_type'] == PayAccountModel::OBJ_TYPE_REPAY_FREEZE
                    || $item['obj_type'] == PayAccountModel::OBJ_TYPE_ROLLOUT
                    || $item['obj_type'] == PayAccountModel::OBJ_TYPE_RAISINGINCOME){
					$item['status'] = '3';
				}
				if($item['cash_id'] > 0){
					$item['status'] = '4';
				}
				if($item['obj_type'] == PayAccountModel::OBJ_TYPE_REWARD_CASHOUT || $item['obj_type'] == PayAccountModel::OBJ_TYPE_REWARD_INVEST
					 || $item['obj_type'] == PayAccountModel::OBJ_TYPE_ADD_COUPON  || $item['obj_type'] == PayAccountModel::OBJ_TYPE_EXPIRE_COUPON
					|| $item['obj_type'] == PayAccountModel::OBJ_TYPE_REWARD_CBACK  || $item['obj_type'] == PayAccountModel::OBJ_TYPE_REWARD_IBACK){
					$item['status'] = '5';
				}
				if($item['obj_type'] == PayAccountModel::OBJ_TYPE_TRANSFER){
					$item['status'] = '6';
				}
				if($item['to_free_money'] != $item['from_free_money']){
					$item['status'] = '7';
				}
				$arr[] = $item;
			}
			$list['data'] = $arr;
			$result['list'] = $list;
		} else {
			$result['error'] = $error;
		}
		$totalrecharge = D('Payment/AccountRecord')->field("COUNT(1) AS CNT")->where($count_cond1)->find();
		$result['totalrecharge'] = $totalrecharge['CNT'];
		$totalTouzhi = D('Payment/AccountRecord')->field("COUNT(1) AS CNT")->where($count_cond2)->find();
		$result['totalTouzhi'] = $totalTouzhi['CNT'];
		$totalHuikuan = D('Payment/AccountRecord')->field("COUNT(1) AS CNT")->where($count_cond3)->find();
		$result['totalHuikuan'] = $totalHuikuan['CNT'];
		$totalTixian = D('Payment/AccountRecord')->field("COUNT(1) AS CNT")->where($count_cond4)->find();
		$result['totalTixian'] = $totalTixian['CNT'];
		$totalJiangli = D('Payment/AccountRecord')->field("COUNT(1) AS CNT")->where($count_cond5)->find();
		$result['totalJiangli'] = $totalJiangli['CNT'];
		$totalZhuangrang = D('Payment/AccountRecord')->field("COUNT(1) AS CNT")->where($count_cond6)->find();
		$result['totalZhuangrang'] = $totalZhuangrang['CNT'];
		$totalFreeMoney = D('Payment/AccountRecord')->field("COUNT(1) AS CNT")->where($count_cond7)->find();
		$result['totalFreeMoney'] = $totalFreeMoney['CNT'];
		
		$totalPtFee = D('Payment/AccountRecord')->field("COUNT(1) AS CNT")->where($count_cond8)->find();
		$result['totalPtFee'] = $totalPtFee['CNT'];
		
		$result['status'] = $status;
		return $result;
	}
	
	//提现申请记录
	public function getMyCashoutList($uid, $status, $start_time, $end_time, $time_type){
		if($status) $parameter['status'] = $status;//状态 1-待处理 2-提现成功 3-提现失败 4-取消提现
		if($start_time) $parameter['start_time'] = $start_time;//申请开始时间
		if($end_time) $parameter['end_time'] = $end_time;//申请结束时间
		if($time_type) $parameter['time_type'] = $time_type;//最近一周-week 最近一个月-month 最近六个月-6month

		$count_cond4 = $count_cond3 = $count_cond2 = $count_cond1 = array('uid'=>$uid);
		$count_cond1['status'] = array(
			'IN',
			array(
				CashoutApplyModel::STATUS_TODO,
				CashoutApplyModel::STATUS_DEALING,
				CashoutApplyModel::STATUS_MAC_TODO,
				CashoutApplyModel::STATUS_MAC_DEALING,
				CashoutApplyModel::STATUS_MAC_CUSTOM,
				CashoutApplyModel::STATUS_YIBAO_SHENFUT,
			)
		);
		$count_cond2['status'] = 2;
		$count_cond3['status'] = 3;
		$count_cond4['status'] = 4;
		
		$condition = array('uid'=>$uid);
		if($status) $condition['status'] = $status;
		D("Payment/CashoutApply");
		if ($status == CashoutApplyModel::STATUS_TODO) {
			$condition['status'] = $count_cond1['status'];
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
		$result = array();
		if(!$error){
			//out_account-账户信息 (money+fee)-提现金额 fee-手续费 ctime-申请时间
			$list = service("Payment/PayAccount")->getCashoutApplys($condition, $parameter);
			$result['list'] = $list;
		} else {
			$result['error'] = $error;
		}
		
		$totalCTodo = D("Payment/CashoutApply")->field("COUNT(1) AS CNT")->where($count_cond1)->find();
		$result['totalCTodo'] = $totalCTodo['CNT'];
		$totalCSuccess = D("Payment/CashoutApply")->field("COUNT(1) AS CNT")->where($count_cond2)->find();
		$result['totalCSuccess'] = $totalCSuccess['CNT'];
		$totalCFailed = D("Payment/CashoutApply")->field("COUNT(1) AS CNT")->where($count_cond3)->find();
		$result['totalCFailed'] = $totalCFailed['CNT'];
		$totalCCanel = D("Payment/CashoutApply")->field("COUNT(1) AS CNT")->where($count_cond4)->find();
		$result['totalCCanel'] = $totalCCanel['CNT'];
		
		$result['status'] = $status;
		return $result;
	}
	
	//获取我的线下充值的列表
	public function getMyXianxiaList($uid, $status, $start_time, $end_time, $time_type){
		$parameter['status'] = $status;//状态 1-待处理，2-充值成功，3-充值失败,4-待跟踪,5-处理中
		$parameter['start_time'] = $start_time;//申请开始时间
		$parameter['end_time'] = $end_time;//申请结束时间
		$parameter['time_type'] = $time_type;//最近一周-week 最近一个月-month 最近六个月-6month
		
		$count_cond4 = $count_cond3 = $count_cond2 = $count_cond1 = array('uid'=>$uid);
		$count_cond1['status'] = 1;
		$count_cond2['status'] = 2;
		$count_cond3['status'] = 3;
		$count_cond4['status'] = 4;
		$condition = array('uid'=>$uid);
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
		$result = array();
		if(!$error){
			//ctime-申请时间 bank_name-转账银行 bak-申请备注 充值方式-转账（写死） real_amount-充值金额（如果real_amount用real_amount，否则用apply_amount）
			$list = service('Payment/XianxiaRecharge')->getList($status, $condition, $parameter);
			$result['list'] = $list;
		} else {
			$result['error'] = $error;
		}
		$totalXTodo = D("Payment/XianxiaRecharge")->field("COUNT(1) AS CNT")->where($count_cond1)->find();
		$result['totalXTodo'] = $totalXTodo['CNT'];
		$totalXSuccess = D("Payment/XianxiaRecharge")->field("COUNT(1) AS CNT")->where($count_cond2)->find();
		$result['totalXSuccess'] = $totalXSuccess['CNT'];
		$totalXFailed = D("Payment/XianxiaRecharge")->field("COUNT(1) AS CNT")->where($count_cond3)->find();
		$result['totalXFailed'] = $totalXFailed['CNT'];
		$totalXTrack = D("Payment/XianxiaRecharge")->field("COUNT(1) AS CNT")->where($count_cond4)->find();
		$result['totalXTrack'] = $totalXTrack['CNT'];
		
		$result['status'] = $status;
		return $result;
	}
}

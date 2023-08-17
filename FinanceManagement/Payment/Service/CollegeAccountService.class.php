<?php
class CollegeAccountService extends BaseService{
	//投资冻结
	public function cgfreeze($uid, $money, $account_record_id){
		$MCollage = M('user_college_ext');
		$MCollage->startTrans();
		try{
			D('Payment/CollegeAccount')->cgfreeze($uid, $money, $account_record_id);
			$MCollage->commit();
		}catch(Exception $e){
			$this->rollback();
			throw $e;
		}
		return true;
	}

	//支付扣款
	public function cgbuy($uid, $money, $account_record_id){
		$MCollage = M('user_college_ext');
		$MCollage->startTrans();
		try{
			D('Payment/CollegeAccount')->cgbuy($uid, $money, $account_record_id);
			$MCollage->commit();
		}catch(Exception $e){
			$this->rollback();
			throw $e;
		}
		return true;
	}
	
	//还款划账
	public function cgrepay($uid, $money, $account_record_id){
		$MCollage = M('user_college_ext');
		$MCollage->startTrans();
		try{
			D('Payment/CollegeAccount')->cgrepay($uid, $money, $account_record_id);
			$MCollage->commit();
		}catch(Exception $e){
			$this->rollback();
			throw $e;
		}
		return true;
	}
}
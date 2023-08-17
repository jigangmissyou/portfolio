<?php
//奖励券
class CouponModel extends BaseModel{
	protected $tableName = 'user_coupon';
	
	const TODO = 1;//未使用
	const USED = 2;//已使用
	const EXPIRED = 3;//已过期
	
	/**
	 * 奖励券
	 * @param unknown $uid 用户id 
	 * @param unknown $cashout_type 1-可提现 2-投资后提现 4-抵用券
	 * @param unknown $amount 金额 单位分
	 * @param unknown $free_tixian_times 免费提现次数
	 * @param unknown $expired 过期时间
	 * @param unknown $reason 原因
	 * @param unknown $bak 备注
	 * @param int $tplId 发送信息的模板id 默认不指定
	 */
	public function awardCoupon($uid, $cashout_type, $amount, $free_tixian_times, $expired, $reason, $bak, $reward_rule_log_id=0, $extraInfo = array()){
		$user = M('user')->find($uid);
		$this->startTrans();
		try{
			$data['uid'] = $uid;
			$data['cashout_type'] = $cashout_type;
			$data['amount'] = $amount;
			$data['free_tixian_times'] = $free_tixian_times;
			$data['expired'] = $expired;
			$data['reason'] = $reason;
			$data['bak'] = $bak;
			$data['prj_min_day'] = 180;
			$data['prj_min_month'] = 6;
			if($extraInfo){
				if($extraInfo['prj_min_day']) $data['prj_min_day'] = (int)$extraInfo['prj_min_day'];
				if($extraInfo['prj_min_month']) $data['prj_min_month'] = (int)$extraInfo['prj_min_month'];
			}
			$data['status'] = self::TODO;
			$data['ctime'] = time();
			$data['mtime'] = time();
            $data['reward_rule_log_id'] = $reward_rule_log_id;
			$id = $this->add($data);
			if(!$id) throw_exception("数据库异常，添加失败");
			if(in_array($cashout_type, array(1,2))){
				D('Payment/PayAccount');
				$data = service('Payment/PayAccount')->addConpon($uid, $amount, PayAccountModel::OBJ_TYPE_ADD_COUPON, $id);
			}
			$this->commit();
			
			if($cashout_type == 1
				|| $cashout_type == 2){
			    if($extraInfo['tpl_id']){
			        //指定模板的直接调用指定模板
			        $replaceVariables = array_merge(array_values($extraInfo), array($user['uname'],  ($amount/100).'元'));
			        D('Message/Message')->simpleSend($uid, 1, $extraInfo['tpl_id'], $replaceVariables,array(1,3),true);
			    }else{
			        if($reason == '注册推荐送红包') {
                        D('Message/Message')->simpleSend($uid, 1, 91, array($user['uname'], ($amount / 100) . '元鑫手红包'), array(1, 3), true);
                    }else if($reason == "注册直接送20元红包"){
                        D('Message/Message')->simpleSend($uid, 1, 91, array($user['uname'], ($amount / 100) . '元鑫手红包'), array(1, 3), true);
                    }else if($reason == '机构以及分公司员工注册推荐送30元红包'){
                        D('Message/Message')->simpleSend($uid, 1, 91, array($user['uname'], ($amount / 100) . '元鑫手红包'), array(1, 3), true);
                    }else{
						//是否需要发送提醒
						if ($extraInfo['do_not_send_msg']) {
							return true;
						}
			            D('Message/Message')->simpleSend($uid, 1, 81, array($user['uname'],  $bak, ($amount/100).'元的[现金红包]'),array(1,2,3),true);
                        service("Mobile2/Remine")->push_uid($uid, 81, time() ,'' ,array($user['uname'],  $bak, ($amount/100).'元的[现金红包]') ,$memo='' ,$checkUnique=1);
			         }
			    }
			} else if($cashout_type == 4){
				D('Message/Message')->simpleSend($uid, 1, 81, array($user['uname'],  $bak, ($amount/100).'元的[免费提现额度]'),array(1,2,3),true);
                service("Mobile2/Remine")->push_uid($uid, 81, time() ,'' ,array($user['uname'],  $bak, ($amount/100).'元的[免费提现额度]') ,$memo='' ,$checkUnique=1);
            }
			

			if((int)$free_tixian_times){
				D('Message/Message')->simpleSend($uid, 1, 81, array($user['uname'],  $bak, $free_tixian_times.'次的[免费提现次数]'),array(1,2,3),true);
			}
			
			return $data;
		}catch (Exception $e){
			$this->rollback();
			throw $e;
		}
	}
	
	public function getCouponList($uid, $money, $whereExt=array()){
		$where = array('uid'=>$uid, 'status'=>self::TODO);
		if($whereExt){
			if($whereExt['prj_min_day']) $where['prj_min_day'] =  array(array('eq',0),array('elt', $whereExt['prj_min_day']), 'or');
			if($whereExt['prj_min_month']) $where['prj_min_month'] = array(array('eq',0),array('elt', $whereExt['prj_min_month']), 'or');
		}
		$list = $this->where($where)->select();
		$last_money = 0;
		$needList = array();
		foreach($list as $ele){
			if($ele['cashout_type'] != 1 && $ele['cashout_type'] != 2) continue;
			$last_money += $ele['amount'];
			$needList[] = $ele;
			if($money && $last_money >= $money) break;
		}
		return $needList;
	}
	
	//使用券
	public function useCoupon($id, $whereExt=array()){
		$where = array('id'=>$id);
		if($whereExt){
			if($whereExt['prj_min_day']) $where['prj_min_day'] = array(array('eq',0),array('elt', $whereExt['prj_min_day']), 'or');
			if($whereExt['prj_min_month']) $where['prj_min_month'] = array(array('eq',0),array('elt', $whereExt['prj_min_month']), 'or');
		}
		$coupon = $this->where($where)->find();
		if(!$coupon) return false;
		if(!in_array($coupon['cashout_type'], array(1,2))) throw_exception("不支持该券类型");
		if($coupon['status'] != self::TODO) throw_exception("该券已经处理过了");
		$this->startTrans();
		try{
			$data['id'] = $id;
			$data['status'] = self::USED;
			$con = $this->save($data);
			if(!$con) throw_exception("数据库异常，修改失败");
            D('Application/RewardRule')->setRewardLogUsed($coupon['reward_rule_log_id']); // 标示奖励规则纪录里的状态
			D('Payment/PayAccount');
			$data = service('Payment/PayAccount')->useConpon(6, $id."_".$coupon['uid'], $coupon['cashout_type'], $coupon['amount'], $coupon['reason'], $coupon['reason'], $coupon['uid'], PayAccountModel::OBJ_TYPE_USE_COUPON, $id);
			$res = $this->commit();
			if(!$res) throw_exception("数据库提交异常");
			return $data;
		}catch (Exception $e){
			$this->rollback();
			throw $e;
			return false;
		}
	}
	
	//过期券 脚本调用
	public function expireCoupon($id){
		$coupon = $this->find($id);
		if($coupon['status'] != self::TODO) throw_exception("该券已经处理过了");
		$this->startTrans();
		try{
			$data['id'] = $id;
			$data['status'] = self::EXPIRED;
			$con = $this->save($data);
			if(!$con) throw_exception("数据库异常，修改失败");
            D('Application/RewardRule')->setRewardLogExpired($coupon['reward_rule_log_id']); // 标示奖励规则纪录里的状态
			if(in_array($coupon['cashout_type'], array(1,2))){
				D('Payment/PayAccount');
				$data = service('Payment/PayAccount')->expireConpon($coupon['uid'], $coupon['amount'], PayAccountModel::OBJ_TYPE_EXPIRE_COUPON, $id);
			}
			$this->commit();
			return $data;
		}catch (Exception $e){
			$this->rollback();
			throw $e;
		}
	}
	
}

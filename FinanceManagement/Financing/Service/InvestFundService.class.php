<?php
/**
 * 理财服务
 * @author Administrator
 *
 */ 
class InvestFundService extends BaseService{
	
	/**
	 * 用款期还款计划
	 * @param array $prjInfo 项目信息
	 * @param array $orderInfo 订单信息
	 * @return multitype:number
	 */
	public function getYkqRepayPlan($prjInfo, $orderInfo){
		
		$now = time();
		
// 		$money = $orderInfo['money'] / 100;
		$yearRate = sprintf('%0.5f', $orderInfo['year_rate'] / 1000);
		
		//用款期利息
		$ykq = D('Financing/InvestFund')->getOrderYkqLixi($orderInfo['time_limit'], $orderInfo['time_limit_unit'], $orderInfo['money'], $yearRate);
		$ykq = sprintf('%0.2f', $ykq);
		
		//用户选择的期限月+(T+?)
		$months = intval($orderInfo['time_limit_day'] / 30);
		//还款时间
		$repayTime = strtotimeX("+{$months} month", self::getLingChenOfDay($prjInfo['end_bid_time']));
		
		$arr = array();
		$ele['no'] = 1; // 还款期数
		$ele['ptype'] = 1; // 一般还款
		$ele['repay_date'] = date('Y-m-d', $repayTime); // 还款日期
		$ele['repay_money'] = $orderInfo['rest_money'] + $ykq; // 应收本息
		$ele['profit'] = $ykq; // 应收利息
		$ele['capital'] = $orderInfo['rest_money']; // 应收本金
		$ele['last_capital'] = 0; // 剩余本金
		$arr[] = $ele;
		return $arr;
	}
	
	/**
	 * 募集期还款计划
	 * @param int $orderPlanInsert
	 * @param array $prjInfo
	 * @param array $orderInfo
	 * @return string
	 */
	public function getMjqRepayPlan(&$orderPlanInsert, $prjInfo, $orderInfo){
		
// 		$money = $orderInfo['money'] / 100;
		$yearRate = sprintf('%0.5f', $orderInfo['year_rate'] / 1000);
		//募集期利息
		$mjq = D('Financing/InvestFund')->getOrderMjqLixi($orderInfo['freeze_time'], $prjInfo['end_bid_time'], $prjInfo['value_date_shadow'], $orderInfo['money'], $yearRate);
		$mjq = sprintf('%0.2f', $mjq);
		
// 		if($mjq < 0){
// 			file_put_contents('c:\2.txt', var_export(array($orderInfo['freeze_time'], $prjInfo['end_bid_time'], $prjInfo['value_date_shadow'], $money, $yearRate), true), FILE_APPEND);
// 		}
		
		$now = time();
		//用户选择的期限月+(T+?)
		$months = intval($orderInfo['time_limit_day'] / 30);
		//还款时间
		$repayTime = strtotimeX("+{$months} month", self::getLingChenOfDay($prjInfo['end_bid_time']));
		
		$mjq = $mjq > 0 ? $mjq : 0;
		
		$orderPlanInsert['repay_date'] = $repayTime;
		$orderPlanInsert['pri_interest'] = $mjq;
		return $mjq;
	}
	
	/**
	 * 理财标的第一次还款间时
	 * 先从订单表找最新的待支付的 没有的话从利率表找
	 * @param int $prjId
	 */
	public function getPrjFirstRepayDate($project){
		
		$prjOrderModel = D('Financing/PrjOrder');
		$rate = M('prj_order')->where(array(
				'prj_id' => $project['id'], 
				'status' => array('neq', PrjOrderModel::STATUS_NOT_PAY),
				'repay_status' => array('eq', PrjOrderModel::REPAY_STATUS_NOT_REPAYMENT),
		))->order('time_limit_day asc')->field('time_limit, time_limit_unit')->find();
		
		if(!$rate){
			$rate = M('invest_fund_rate')->where(array('prj_id' => $project['id']))->order('time_limit_day asc')->find();
		}
		
		$repayTime = strtotimeX("+{$rate['time_limit']} {$rate['time_limit_unit']}", self::getLingChenOfDay($project['end_bid_time']));
		return $repayTime;
	}
	
	/**
	 * 理财标的最后一次还款期限
	 * 先从订单表找最新的待支付的 没有的话从利率表找
	 * @param int $prjId
	 */
	public function getPrjLastRepayDate($prjId){
	
		$prjOrderModel = D('Financing/PrjOrder');
		$rate = M('prj_order')->where(array(
				'prj_id' => $prjId,
				'status' => array('neq', PrjOrderModel::STATUS_NOT_PAY),
				'repay_status' => array('eq', PrjOrderModel::REPAY_STATUS_NOT_REPAYMENT),
		))->order('time_limit_day desc')->field('time_limit, time_limit_unit')->find();
	
		if(!$rate){
			$rate = M('invest_fund_rate')->where(array('prj_id' => $prjId))->order('time_limit_day desc')->find();
		}
		
		return $rate;
	}
	
	/**
	 * 根据时间戳返回当天凌晨的时间戳
	 * @param number $t
	 */
	static function getLingChenOfDay($t){
		return strtotime(date('Ymd', $t));
	}
	
	/**
	 * 理财项目支付按期限展开
	 * @param int $prj_id
	 * @param string $total
	 * @return multitype:|string
	 */
	public function fundClist($prj_id, $total = false){
		if(!$prj_id){
			return array();
		}
		import_app("Financing.Model.PrjOrderRepayPlanModel");
		import_app("Financing.Model.PrjOrderModel");
		$where['t1.prj_id'] = $prj_id;
		$where['t1.status'] = array('neq', PrjOrderModel::STATUS_NOT_PAY);
		$where['t2.status'] = array('neq', PrjOrderRepayPlanModel::ORDER_REPAYMENT_STATUS_FAIL);
		$field = 'sum(t2.principal) money,
				count(DISTINCT t1.id) invest_nums,
				sum(IF(t2.ptype = 2, t2.yield, 0)) mjqlx,
				sum(IF(t2.ptype = 1, t2.yield, 0)) ykqlx,
				sum(t2.principal) principal,
				sum(t2.pri_interest) pri_interest,
				t1.time_limit,
				t1.time_limit_day,
				t1.time_limit_unit,
				t1.rate,
				t1.year_rate,
				t1.rate_type';
	
		$data = M()->table('fi_prj_order t1')
				->join('LEFT JOIN fi_prj_order_repay_plan t2 ON t1.id = t2.prj_order_id')
				->field($field)->where($where)->group('t1.time_limit_day')->select();
		$sort_column = array();
		$prj_service = service('Financing/Project');
		foreach ($data as $k => $v){
			$sort_column[] = $v['time_limit_day'];
			
			$data[$k]['rate_view'] = $this->getPrjRate($v['rate'], $v['rate_type']);
			$data[$k]['time_view'] = $this->getPrjTime($v['time_limit'], $v['time_limit_unit']);
			
		}
		array_multisort($data, $sort_column, SORT_ASC);
		return $data;
	}
	
	/**
	 * 获取一个项目的利率表示
	 * @param float $rate
	 * @param string $rateType
	 */
	public function getPrjRate($rate, $rateType){
		
		if(empty($rate) || empty($rateType)){
			return '';
		}
		
		import_app("Financing.Model.PrjOrderModel");
		if ($rateType != PrjModel::RATE_TYPE_DAY) {
			$rateView = number_format($rate / 10, 2);
		} else {
			$rateView = number_format($rate, 2);
		}
		
		$rateSymbol = $rateType == PrjModel::RATE_TYPE_DAY ? '‰' : '%';
		$rateView .= $rateSymbol;
		
		return $rateView;
	}
	
	/**
	 * 获取一个项目的期限表示
	 * @param int $timeLimit
	 * @param string $timeLimitUnit
	 */
	public function getPrjTime($timeLimit, $timeLimitUnit){
		if(empty($timeLimit) || empty($timeLimitUnit)){
			return '';
		}
		
		$timeMap = array('day' => '天', 'month' => '个月', 'year' => '年');
		
		return $timeLimit.$timeMap[$timeLimitUnit];
	}
	
	/**
	 * 生成所有订单还款计划
	 *
	 * @param $prj_id
	 */
	public function createOrderRepayPlan($params) {
		
		$prj_id = $params['prj_id'];
		
		$srvProject = service('Financing/Project');
		try {
			return $srvProject->createOrderRepayPlan($prj_id);
		} catch (Exception $e) {
			$message = $e->getMessage();
			if($message != 'CREATING...') $srvProject->createOrderRepayPlanCache($prj_id, NULL);
			else $message = '正在生成中, 不要重复提交';
			throw_exception("PRJ_ID:{$prj_id}, {$message}");
		}
	}
	
	/**
	 * 截标的时候更新订单的expect_repay_date
	 * @param int $prjId
	 */
	public function setOrderNextRepayDate($prjId, $endBidTime = ''){
		if(!$prjId) return;
		
		$condition = array('prj_id' => $prjId);
		$mdOrder = M('prj_order');
		$orders = $mdOrder->where($condition)->field('id, time_limit, time_limit_unit')->select();
		if($orders){
			empty($endBidTime) && $endBidTime = M('prj')->where(array('id' => $prjId))->getField('end_bid_time');
			foreach ($orders as $key => $order){
				
				$last_repay_time =strtotimeX("+{$order['time_limit']} {$order['time_limit_unit']}", $endBidTime);
				$last_repay_date = strtotime(date('Y-m-d', $last_repay_time));
				$result = $mdOrder->where(array('id' => $order['id']))->setField(array(
					'last_repay_date' => $last_repay_date,
					'expect_repay_time' => $last_repay_date,
					'mtime' => time(),
						
				));
				if(FALSE === $result) throw_exception('更新订单最后还款日出错');
			}
		}
	}
	
	/**
	 * 生成基金项目的日常财务支付条目
	 */
	public function createPayStatRecord($prjId = 0){
		$prjModel = D('Financing/Prj');
		$prjOrderModel = D('Financing/PrjOrder');
		$rateObj = M('invest_fund_pay_stat');
		$now = time();
		
		//先将没有支付掉的作废掉重新生成 防止财务某天忘记支付 后来重复支付
		$condition['status'] = array('eq', 0);
		$prjId && $condition['prj_id'] = array('eq', $prjId);
		$rateObj->where($condition)->save(array('status' => -1));
		
		$condition = array(
			'prj_type' => PrjModel::PRJ_TYPE_G,
			'status' => PrjOrderModel::STATUS_FREEZE,
		);
		
		($prjId && $condition['prj_id'] = $prjId) || $condition['ctime'] = array('ELT', strtotime(date('Y-m-d 16:00:00', $now)));
		
		$fields = "prj_id, COUNT(*) c_invest_nums, SUM(money) money, time_limit_day";
		$result = M('prj_order')->field($fields)->where($condition)->group('prj_id, time_limit_day')->select();
		
		foreach ($result as $key => $value){
			$rateId = M('invest_fund_rate')->where(array('prj_id' => $value['prj_id'], 'time_limit_day' => $value['time_limit_day']))->getField('id');
			$data = array(
				'prj_id' => $value['prj_id'],
				'rate_id' => $rateId,
				'c_invetst_nums' => $value['c_invest_nums'],
				'pay_amount' => $value['money'],
				'status' => 0,
				'ctime' => $now,
				'mtime' => $now,
			);
			$insertId = M('invest_fund_pay_stat')->add($data);
			$res = M('prj_order')->where(array('prj_id' => $value['prj_id'], 'time_limit_day' => $value['time_limit_day']))->save(array('stat_id' => $insertId));
		
			if(!$res){
				throw_exception(M('prj_order')->getLastSql());
			}
		}
	}
	
	/**
	 * 保存理财项目订单的协议ID
	 * @param int $orderId 订单id
	 * @return void
	 */
	public function saveOrderProtocolId($orderId){
		!$orderId && throw_exception('协议数据保存失败，却少订单ID');
		
		$fieldMap = array('31' => 'protocol_id', '32' => 'protocol_id2');
		$protocolService = service('Financing/Protocol');
		foreach($fieldMap as $protocolId => $field){
			if(!($protocolId = $protocolService->getLastProtocolId($protocolId))){
				throw_exception('协议数据配置有误');
			}
			if(false == M('prj_order')->where(array('id' => $orderId))->save(array($field => $protocolId))){
				throw_exception('协议数据保存失败');
			}
		}
	}
	
	/**
	 * 理财项目的剩余目击事件
	 * @param int $prjInfo
	 */
	public function getFundPrjRemindDayTime($prjInfo){
		//起始时间
		$now = time();
		if($prjInfo['end_bid_time'] <= $now) return 0;
		
		$timeDiff = strtotime(date('Y-m-d', $prjInfo['end_bid_time'])) - strtotime(date('Y-m-d', $now));
		return $timeDiff > 0 ? ($timeDiff / 86400 + 1) : 1;
	}
	
	/**
	 * 基金理财项目的平台服务费
	 * @param int $prjId
	 * @param float $payAmount 实际统计金额
	 */
	public function getFundPrjFee($prjId, $payAmount){
		$prj = M('prj')->field('platform_fee, platform_rate')->where(array('id' => $prjId))->find();
		return $payAmount * $prj['platform_rate'];
	}
	
	/**
	 * 当前时刻可以支持的订单统计
	 * @param int $prjId
	 */
	public function getPayableStat($prjId){
		if(!$prjId) return array();
		$where['prj_id'] = array('eq', $prjId);
		$where['status'] = array('neq', -1);
		$field = "sum(c_invetst_nums) total_invest_nums, sum( IF ( `status` = 0, c_invetst_nums, 0 )) c_total_invetst_nums, sum( IF ( `status` = 1, pay_amount, 0 )) paied_amount, sum( IF ( `status` = 0, pay_amount, 0 )) should_pay_amount";
		$payTotal = M('invest_fund_pay_stat')->where($where)->field($field)->find();
		return $payTotal;
	}
	
	/**
	 * 基金理财项目最新一期订单还款计划
	 * @param int $prjId
	 */
	public function getPrjNewestRepayPlan($prjId){
		if(!$prjId) return array();
		$prjOrderRepayPlanModel = D('Financing/PrjOrderRepayPlan');
		$condition = array(
			'prj_id' => $prjId,
			'status' => PrjOrderRepayPlanModel::ORDER_REPAYMENT_STATUS_WAIT,
		);
		$result = $prjOrderRepayPlanModel->where($condition)->order('repay_date ASC')->find();
		return $result;
	}
	
	/**
	 * 根据项目ID获取投资者列表
	 * @param int $prjId 项目ID
	 * @param int $page 当前页数
 	 * @param int $perPage 每页显示个数
	 * @return array 投资人列表
	 */
	public function getInvestorsOfPrjByPrjId($prjId, $page, $perPage){
		
		if(!$prjId) return array();
		$prjModel = D('Financing/Prj');
		$prjOrderModel = D('Financing/PrjOrder');
		
		$condition = array(
			't1.prj_id' => $prjId,
			't1.status' => array('neq', PrjOrderModel::STATUS_NOT_PAY),
		);
		
		$total = M()->table('fi_prj_order t1')->where($condition)->count();
		if ($page > ($pages = ceil($total / $perPage))){
			$page = $pages;
		}
		
		if($total){
			$field = 't1.freeze_time, t1.expect_repay_time, t3.uid, t3.uname, t3.real_name, 
					t3.person_id, t1.money, t1.time_limit, t1.time_limit_unit, t1.time_limit_day, 
					t1.rate, t1.year_rate, t1.rate_type, t1.expect_repay_time';
			
			$result = M()->table('fi_prj_order t1')
					->join('LEFT JOIN fi_prj t2 on t1.prj_id = t2.id')
					->join('LEFT JOIN fi_user t3 on t1.uid = t3.uid')->field($field)
					->where($condition)->page($page)->limit($perPage)->select();
			
			foreach ($result as $key => $value){
				$result[$key]['freeze_time_show'] = date('Y-m-d H:i:s', $value['freeze_time']);
				$result[$key]['repay_time_show'] = date('Y-m-d', $value['expect_repay_time']);
				$result[$key]['money_show'] = humanMoney($value['freeze_time'], 2, false);
				//投资期限
				$result[$key]['time_limit_show'] = $this->getPrjTime($value['time_limit'], $value['time_limit_unit']);
				$result[$key]['rate_show'] = $this->getPrjRate($value['rate'], $value['rate_type']);
				
				//银行账号
				$bankCard = M('fund_account')->where(array(
						'uid' => $value['uid'], 
						'is_active' => 1,
				))->order('(case when is_default = 1 then 10000000000 else ctime end) desc')->find();
				
				$result[$key]['bank_card'] = $bankCard;
			}
		}
		
		$return['total'] = $total;
		$return['data'] = (array)$result;
		return $return;
	}
	
	/**
	 * 设置基金项目的下一次和最后一次还款时间
	 * 在截标时和生成还款计划时一起执行 目的是因为理财分了好多期限
	 * 有的期限不一定有用户投 所以要更新下一次和最后一次还款时间
	 * @param int $prjId 项目ID
	 * @return void
	 */
	public function setRelateDate($prjId){
		$srvProject = service('Financing/Project');
		$where_prj = array('id' => $prjId);
		$project = M('prj')->where($where_prj)->find();
		$now = time();
		
		//下一次还款时间next_repay_date
		$repay_time = $srvProject->getPrjFirstRepayDate($prjId, $project, FALSE); // 首次还款日
		$data = array(
			'next_repay_date' => $repay_time,
			'mtime' => $now,
		);
		if (FALSE === M('prj')->where($where_prj)->save($data)) throw_exception('更新截标时间: 修改项目信息失败');
		
		//最后一次还款时间last_repay_date repay_time
		$last_repay_date = $srvProject->getPrjEndTime($prjId);
		$data = array(
			'last_repay_date' => $last_repay_date,
			'repay_time' =>$last_repay_date
		);
		if (FALSE === M('prj')->where($where_prj)->save($data)) throw_exception('更新截标时间: 更新最迟还款日出错');
	}
}
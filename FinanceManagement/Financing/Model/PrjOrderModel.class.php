<?php
/**
 * // +----------------------------------------------------------------------
// | UPG
// +----------------------------------------------------------------------
// | Copyright (c) 2012 http://upg.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: kaihui.wang <wangkaihui@upg.cn>
// +----------------------------------------------------------------------
// 购买订单
 */
class PrjOrderModel extends BaseModel{
    protected $tableName = 'prj_order';

    const ORDER_NO_PER = "GMDD";

    const STATUS_TODO = 1;//待处理
    const STATUS_FREEZE = 2;//冻结
    const STATUS_PAY_SUCCESS = 3;//已支付
    const STATUS_NOT_PAY = 4;//不支付

    const REPAY_STATUS_NOT_REPAYMENT=1;//未回款
    const REPAY_STATUS_PART_REPAYMENT=2;//部分回款
    const REPAY_STATUS_ALL_REPAYMENT=3;//全部回款

    // 老富二代用的
    const CREDITOR_TRANSFERING = 1;
    const CREDITOR_TRANSFERED = 2;

    // 司马小鑫的债权转让状态
    const TRANS_STATUS_NO = 1;          // 未转让
    const TRANS_STATUS_WAIT = 2;        // 待转让
    const TRANS_STATUS_BIDING = 3;      // 转让中
    const TRANS_STATUS_END_PART = 4;    // 部分转让
    const TRANS_STATUS_END_FULL = 5;    // 全部转让

    // 浙商冻结状态
    const BANK_FREEZE_STATUS_ING = 10; //处理中
    const BANK_FREEZE_STATUS_SUCCESS = 20; //成功
    const BANK_FREEZE_STATUS_FAIL = 30; //失败
    const BANK_FREEZE_STATUS_UNSURE = 40; //争议

    protected $_auto = array(
            array('mtime','time',self::MODEL_BOTH,'function'),
    );

    public function getTransStatusName($trans_status)
    {
        $map = array(
            1 => "未转让",
            2 => "待转让",
            3 => "转让中",
            4 => "部分转让",
            5 => "全部转让"
        );
        return $map[$trans_status];
    }

    // 生成产品编号
    public function getOrderNo(){
        $id_gen_instance = new \Addons\Libs\IdGen\IdGen();
        return $id_gen_instance->get(\Addons\Libs\IdGen\IdGen::WORK_TYPE_PRJ_ORDER);
    }

    /**
     * 订单对账完成
     * @param unknown $orderId
     * @return boolean
     */
    function checkMiOrder($orderId){
    	$order = $this->find($orderId);
    	if($order['is_check']) {
    		throw_exception("已经核对完成");
    	}
    	$orderdata['id'] = $orderId;
    	$orderdata['is_check'] = 1;
    	$orderdata['status'] = PrjOrderModel::STATUS_FREEZE;
    	$orderdata['mtime'] = time();
    	$con=$this->save($orderdata);
    	if(!$con){
    		throw_exception("保存失败");
    	}
    	return true;
    }

    /**
     * 添加接口订单购买订单
     * @param unknown $uid
     * @param unknown $prjId  项目idid
     * @param unknown $money  购买份额
     * @param number $transfer 转让
     * @param number $fromOrderId 转让是来源订单
     * @param $mo_order_no 渠道订单no
     */
    function addMiOrder($uid,$prjId,$money,$mo_order_no='',$fromOrderId=0, $prj=null){
    	$data['uid'] = (int) $uid;
    	$data['prj_id'] = (int) $prjId;
    	$data['money'] = $money;
    	$data['rest_money'] = $money;
    	$data['order_no'] = $this->getOrderNo();
    	$data['status'] = self::STATUS_FREEZE;//默认待处理状态
    	$data['repay_status'] = self::REPAY_STATUS_NOT_REPAYMENT;
        $data['trans_status'] = self::TRANS_STATUS_NO;
    	$data['is_regist'] = 0;
    	$data['tran_freeze_money'] = 0;
    	$data['from_client'] = D("Account/User")->getFrom();
    	if($fromOrderId) $data['from_order_id'] = $fromOrderId;
    	$data['is_mo'] = 1;
    	$data['is_check'] = 0;
        $data['mo_order_no'] = $mo_order_no;

    	$prjModel = M("prj");
    	if(!$prj){
    		$prj = $prjModel->where(array("id"=>$prjId))->find();
    		if(!$prj){
    			MyError::add("异常,项目不存在!");
    			return false;
    		}
    	}

    	$projectService = service("Financing/Project");
    	$now = time();

    		$data['freeze_time'] = $now;
    		//预计收益
    		if(service("Financing/Invest")->isEndDate($prj['repay_way'])){
    			$income = $projectService->getIncomeById($prjId, $money, $data['freeze_time']);
    			$data['possible_yield'] = $income;
    			$data['expect_repay_time'] = $projectService->getPrjEndTime($prjId);
    		}else{
    			$data['expect_repay_time'] = $prj['next_repay_date'] ? $prj['next_repay_date'] : $prj['last_repay_date'];
    		}

    	$data['ctime'] = $now;

    	$data['prj_type'] = $prj['prj_type'];

    	$data['is_transfer'] = 0;

    	$data['mi_no'] = self::$api_key;
    	//print_r($data);
    	if($this->create($data)){
    		$id=$this->add();
    		//echo $this->getLastSql();exit;
    		if(!$id){
    			MyError::add("写入订单失败!");
    			return false;
    		}
    		return array($data['order_no'],$id);
    	}else{
    		MyError::add($this->getError());
    		return false;
    	}
    }

    function addOrder($uid,$prjId,$money,$fromOrderId=0, $prj=null, $params = array()){

        $return_data = FALSE;
        //生成预售订单标识
        if($params['pre_sale_id']){
            $return_data = TRUE;
        }

        $data = $this->_addOrder($uid,$prjId,$money,$fromOrderId=0, $prj=null, $params, $return_data);

        //生成临时预售单
        if(!$params['is_pre_sale_create'] && $params['pre_sale_id']){
            $data['status'] = self::STATUS_NOT_PAY;//临时预售单状态为未支付
            //添加预售标识,和订单初始状态
            $data['pre_sale_id'] = $params['pre_sale_id'];
            $data['pre_sale_status'] = 1;//未处理状态
            $id = $this->add($data);
            if(!$id){
                MyError::add("写入订单失败!");
                return false;
            }
            return $id;
        }
        //更新临时预售单为成功预售单
        if($params['is_pre_sale_create'] && $params['pre_sale_id']){
            //根据pre_sale_id从prj_order表中取出之前生成的order数据
            $old_data = $this->where(['pre_sale_id' => $params['pre_sale_id']])->find();
            unset($data['order_no']);
            $data = array_merge($old_data, $data);
            $this->save($data);//prj_order 表 pre_sale_status 在task中更新
            return array($old_data['order_no'], $old_data['id']);
        }

        return $data;//用户前台正常下单返回 array(order_no,order_id)
    }

   /**
    * 添加购买订单
    * @param unknown $uid
    * @param unknown $prjId  项目idid
    * @param unknown $money  购买份额
    * @param number $transfer 转让
    * @param number $fromOrderId 转让是来源订单
    * @param array $params 理财项目对应的利率期限
    */
    function _addOrder($uid,$prjId,$money,$fromOrderId=0, $prj=null, $params = array(), $return_data = false){
    	$data['uid'] = (int) $uid;
    	$data['prj_id'] = (int) $prjId;
    	$data['money'] = $money;
    	$data['rest_money'] = $money;
    	$data['order_no'] = $this->getOrderNo();
    	$data['status'] = self::STATUS_FREEZE;//默认冻结状态
    	$data['repay_status'] = self::REPAY_STATUS_NOT_REPAYMENT;
        $data['trans_status'] = self::TRANS_STATUS_NO;
        $data['is_regist'] = 0;
        $data['tran_freeze_money'] = 0;
        $data['from_client'] =(isset($params['from_client']) && $params['from_client']) ? $params['from_client'] : D("Account/User")->getFrom();
        $data['xprj_match_id'] = (int) $params['xprj_match_id'];
    	if($fromOrderId) $data['from_order_id'] = $fromOrderId;

    	$prjModel = M("prj");
    	if(!$prj){
	    	$prj = $prjModel->where(array("id"=>$prjId))->find();
	    	if(!$prj){
	    		MyError::add("异常,项目不存在!");
	    		return false;
	    	}
    	}

    	$projectService = service("Financing/Project");
    	$now = time();
    	if($fromOrderId){//转让的时候 订单初始化逻辑
    		$fromOrder = $this->where(array("id"=>$fromOrderId))->find();
    		$data['freeze_time'] = $fromOrder['freeze_time'] ? $fromOrder['freeze_time'] : $now;

    		if(service("Financing/Invest")->isEndDate($prj['repay_way'])){
    			$data['possible_yield'] = $projectService->getIncomeById($prjId, $money, $data['freeze_time']);
    		}
    		$data['expect_repay_time'] = $fromOrder['expect_repay_time'];
    	}else{//正常购买的时候
    		$data['freeze_time'] = $now;
    		//预计收益
    		if(service("Financing/Invest")->isEndDate($prj['repay_way'])){
    			$income = $projectService->getIncomeById($prjId, $money, $data['freeze_time']);
    			$data['possible_yield'] = $income;
    			$data['expect_repay_time'] = $projectService->getPrjEndTime($prjId);
    		}else{
    			if($prj['prj_class'] == PrjModel::PRJ_CLASS_LC){
//     				$data['expect_repay_time'] = $projectService->getPrjEndTime($prjId, null, $params);
    				$data['expect_repay_time'] = strtotime(date('Y-m-d', strtotimeX("{$params['time_limit']} {$params['time_limit_unit']}", $prj['end_bid_time'])));
    			}else{
    				$data['expect_repay_time'] = $prj['next_repay_date'] ? $prj['next_repay_date'] : $prj['last_repay_date'];
    			}
    		}

    	}
    	$data['ctime'] = $now;

    	$data['prj_type'] = $prj['prj_type'];
        //如果是速兑通项目,保留订单的层级
        if ($prj['prj_type'] == 'H') {
            //获取项目的扩展表的层级关系
            $cash_level = M("prj_ext")->where(array('prj_id'=>$prjId))->getField("cash_level");
            if (!$cash_level) {
                $cash_level = 0;
            }
            $data['cash_level'] = $cash_level + 1;
        }

    	//预计回款日期
        //是否能转让
		import_app("Financing.Model.PrjModel");
        if(in_array($prj['prj_type'], array(PrjModel::PRJ_TYPE_B, PrjModel::PRJ_TYPE_C, PrjModel::PRJ_TYPE_D,PrjModel::PRJ_TYPE_F))){
//            $extInfo = M("manage_fund")->where(array("prj_id"=>$prjId))->find();
            $project = service('Financing/Project')->getPrjInfo($prjId);
            $extInfo = $project['ext'];
            if(array_key_exists('is_transfer', $extInfo)) {
                $data['is_transfer'] = (int) $extInfo['is_transfer'];
            }

    	}else{
            $data['is_transfer'] = 0;
        }

        $data['mi_no'] = self::$api_key;
        //增加两个奖励字段到prj_order表中。(java支付用，已和刘俊确定方案)
        //加息券不记录奖励金额
        $reward_type_list = D("Financing/UserBonusFreeze")->getRewardTypeList();
        if (in_array($params['reward_type'], $reward_type_list)) {
            $data['invest_reward_money'] = $params['invest_reward_money'];
        }

        //司马小鑫订单ID
        $data['xprj_order_id'] = (int) $params['x_order_id'];
        if($prj['prj_type'] == PrjModel::PRJ_TYPE_J) {
            $prj_ext = M('prj_ext')->where(array('prj_id' => $prjId))->find();
            $data['root_prj_id'] = $prj_ext['root_prj_id'];
            $data['root_order_id'] = $prj_ext['root_order_id'];
            $data['from_order_id'] = $prj_ext['from_order_id'];
        }

    	if($this->create($data)){
            if($return_data){//预售返回数据
                return $data;
            }
    		$id=$this->add();
    		if(!$id){
    		    MyError::add("写入订单失败!");
    		    return false;
    		}

            // 购买债权转让的时候插入prj_order_transfere表
            if ($prj['prj_type'] == PrjModel::PRJ_TYPE_J) {
                D('Financing/PrjOrderTransferee')->addOrderTransferee($id);

                //重新设置rest_money值，存转让本金，非转让价格
                $transferee_info = D('Financing/PrjOrderTransferee')->getOrderTransfereeByOrderId($id);
                $this->where(array('id'=>$id))->save(array('rest_money'=>$transferee_info['transferee_corpus_amount']));
            }

    		return array($data['order_no'],$id);
    	}else{
    	    MyError::add($this->getError());
    	    return false;
    	}
    }

    //添加order之后的项目统计，和addOrder()方法连用
    public function addOrderAfterCount($uid, $prjId, $fromOrderId=0){
    	$prjModel = M("prj");
    	//订单添加成功，,维护invest_newbie_times和is_newbie字段(2015-07-18)修改
    	D("Account/User")->procNewBie($uid,$prjId);
    	// 项目表统计
    	if($uid && !$fromOrderId) {
    		$prjModel->where(array('id' => $prjId))->setInc('invest_count', 1);
    		$prjModel->where(array("id"=>$prjId))->save(array("mtime"=>time()));
    		//前面订单已经添加过，所以这里判断是1，说明是首次投资
    		$have_invested = $this->field("id")->where(array('prj_id' => $prjId, 'uid' => $uid
    				, 'from_order_id' => array('EXP', 'IS NULL')
    				, 'status' => array('NEQ', self::STATUS_NOT_PAY)))->limit(2)->select();
    		if(count($have_invested)==1){
    			$prjModel->where(array('id' => $prjId))->setInc('invest_users', 1);
    		}
    	}
    }


    /**
     * 退标、撤资操作
     *
     * @param int $uid
     * @param int $order_id     购买订单Id
     * @return array
     * @throws Exception
     */
    public function backProjectOrder($uid, $order_id) {
        $order = $this->find($order_id);
        if(!$order) {
            throw_exception('该购买订单不存在！');
        }
        if($order['uid'] == 0) {
            throw_exception('该购买订单是通过线下登记，不能执行此操作！');
        }
        if($order['status'] != PrjOrderModel::STATUS_FREEZE) {
            throw_exception('该购买订单不处于冻结状态，不能执行此操作！');
        }

        $project =M('prj')->find($order['prj_id']);
        if(!$project) {
            throw_exception('该购买订单关联的项目不存在！');
        }
        $publish = service('Financing/Financing')->getPublishInst($uid);
        $dept_id = $publish['dept_id'];
        if(!($dept_id && $dept_id == $project['dept_id'] || $project['uid'] == $uid)) {
            throw_exception('只有本人或同部门的人才可以操作。');
        }
        if($project['end_bid_time'] < time()) {
            throw_exception('融资已截止。');
        }
        if(!in_array($project['bid_status'], array(PrjModel::BSTATUS_BIDING, PrjModel::BSTATUS_FULL))) {
            throw_exception('只有投标中、已满标状态下才能进行撤标操作！');
        }

        $mdPrj = D('Financing/Prj');

        $have_invested_count = $this->where(array('prj_id' => $order['prj_id'], 'uid' => $order['uid'], 'from_order_id' => array('EXP', 'IS NULL'), 'status' => array('NEQ', self::STATUS_NOT_PAY)))->count();
        $this->startTrans();

        try {
            //新客处理
            $xinwhere=array();
            $xinwhere['uid'] = $order['uid'];
            $xinwhere['id'] = array("NEQ",$order_id);
            $xinwhere['status'] = array("NEQ",PrjOrderModel::STATUS_NOT_PAY);
            $check = M("prj_order")->where($xinwhere)->find();

            if(!$check){
                M("user")->where(array("uid"=>$order['uid']))->save(array("is_newbie"=>1));
            }
            // 第一步：改变购买订单状态
            $where = array(
                'id' => $order_id,
            );
            $data = array(
                'status' => self::STATUS_NOT_PAY,
                'mtime' => time(),
            );
            if($this->where($where)->save($data) === FALSE) {
                throw_exception('退标/撤资异常：变更订单状态出错！');
            }

            // 第二步：返回剩余份额 & 更新投标状态
            $where = array(
                'id' => $order['prj_id'],
            );
            $data = array(
                'remaining_amount' => $project['remaining_amount'] + $order['money'],
                'mtime' => time(),
            );
            if($project['bid_status'] ==  PrjModel::BSTATUS_FULL) {
                $data['bid_status'] = PrjModel::BSTATUS_BIDING;
            }
            if($mdPrj->where($where)->save($data) === FALSE) {
                throw_exception('退标/撤资异常：返回剩余份额出错！');
            }

            //补回退标后的缓存里的剩余金额
            $remaining_cachekey = "buyremain_".$order['prj_id'];
            $cache = Cache::getInstance();
            $result = $cache->increment($remaining_cachekey, $order['money']);

            // 第三步：退款
            $svrPayAccount = service('Payment/PayAccount');
            $ret = $svrPayAccount->reback($order['order_no']);

            // 第四步：更新user_account_summary
            if($order['uid']) {

            	service("Payment/UserAccountSummary")->changeAcountSummary('backbuy', $order_id, 0,0);

//                 if(M('UserAccountSummary')->where(array('uid' => $order['uid']))->setDec('investing_prj_count', 1) === FALSE
//                 || M('UserAccountSummary')->where(array('uid' => $order['uid']))->setDec('investing_prj_money', $order['money']) === FALSE
//                 || M('UserAccountSummary')->where(array('uid' => $order['uid']))->setField('mtime', time()) === FALSE) {
//                     throw_exception('更新投资者统计信息出错！');
//                 }
            }

            // 第五步：更新合伙企业中的统计
            if($project['prj_type'] == PrjModel::PRJ_TYPE_C) {
                D('Financing/PrjPartner')->countDec($order['prj_id']);
            }


            if(!service("Financing/Invest")->isEndDate($project['repay_way'])){
                //更改状态
                $orderRepayPlanList = M("prj_order_repay_plan")->where(array('prj_order_id' => $order_id,"status"=>PrjOrderRepayPlanModel::ORDER_REPAYMENT_STATUS_WAIT))->select();

                if($orderRepayPlanList){
                    foreach($orderRepayPlanList as $orderPlanv){
                        M("prj_order_repay_plan")->where(array('id' => $orderPlanv['id']))->save(array('status' => PrjOrderRepayPlanModel::ORDER_REPAYMENT_STATUS_FAIL));
                        service("Financing/Invest")->cutUserRepayPlan($order_id,$orderPlanv['repay_date'],$orderPlanv['pri_interest']);
                        //减去募集期的
                        if($orderPlanv['repay_periods'] == 0){
                            M('UserAccountSummary')->where(array('uid' => $order['uid']))->setDec('will_profit', $orderPlanv['yield']);
                        }
                    }
                }

	            $Pay = service("Financing/Project")->getTradeObj($order['prj_id'],"Pay");
	            if(!$Pay) throw_exception(MyError::lastError());

	            $Pay->savePrjRepaymentPlan(array('prj_id' => $order['prj_id']));
            }else{
                service("Financing/Invest")->cutUserRepayPlan($order_id,$project['last_repay_date'],$order['money']+$order['possible_yield']);
            }

            // 项目表统计
            if($order['uid'] && !$order['from_order_id']) {
                $mdPrj->where(array('id' => $order['prj_id']))->setDec('invest_count', 1);
                if($have_invested_count <= 1) $mdPrj->where(array('id' => $order['prj_id']))->setDec('invest_users', 1);
            }

            $this->commit();

            if(!$ret['boolen']) {
                throw_exception($ret['message']);
            }
            return array(1, '操作成功！');
        } catch (Exception $e) {
            $this->rollback();
            throw $e;
        }
    }

    /**
     * 获取我的投资信息
     */
    public function getMyOrderInfo($order_id,$uid)
    {
        !$uid && throw_exception("用户UID不合法!");
        $where = array(
            //已支付的(正式的时候要打开)
            'fi_prj_order.status' => self::STATUS_PAY_SUCCESS,
            'fi_prj_order.is_regist' => 0,
            'fi_prj_order.id' => $order_id,
            'fi_prj_order.uid' => $uid
        );
        $join = array(
            'fi_user ON fi_prj_order.uid = fi_user.uid',
            'fi_invest_prj ON fi_prj_order.prj_id = fi_invest_prj.prj_id',
        );
        $field = array(
            'uname',
            'real_name',
            'person_id',
            'money',
            'fi_invest_prj.value_date',
            'possible_yield',
            'prj_type',
            'fi_invest_prj.prj_id',
            'time_limit_day'
        );
        $total = $this->join($join)->where($where)->count();
        //$sql = $this->getLastSql();
        !$total && throw_exception("您还没有对该项目投资!");
        $list = $this->join($join)->where($where)->field($field)->find();
        return $list;
    }

    /**
     * 获取第一笔成功支付后的订单
     * @param $uid
     */
    public function getMyFirstOrder($uid){
        !$uid && throw_exception("用户UID不合法!");
        $where = array(
            'is_regist' => 0,
            'uid' => $uid
        );
        $field = array(
            'id',
            'uid',
            'money',
            'ctime',
        );

        $list = $this->where($where)->field($field)->order("pay_time desc")->find();
        return $list;
    }

    public function getMultiMyFirstOrders($uids){
        if( !is_array($uids) ) return false;

        $uids = array_unique(array_filter($uids));
        !$uids && throw_exception("用户UID不合法!");
        $where = array(
            'is_regist' => 0,
            'uid' => array('IN', $uids)
        );
        $field = array(
            'min(id) as id'
        );

        $orders = $this->where($where)->field($field)->group('uid')->select();

        $ret = array();
        if($orders){
            $where2 = array(
                'id'    => array('IN', array_column($orders, 'id'))
            );
            $field2 = array('id','uid','money','ctime',);
            $ret = $this->where($where2)->field($field2)->select();
        }
        return $ret;
    }

    /**
     * 获取某天的投资用户UID列表
     * @param $day
     * @return array
     */
    public function getOrderUIDByDay($day){
        $start_time = strtotime(date('Y-m-d 00:00:00', strtotime($day)));
        $end_time = $start_time + 3600 * 24;

        if($start_time < 1) return array();

        $where['pay_time'] = array('between', array($start_time, $end_time));

        $uids = $this->distinct(true)->where($where)->field('uid')->select();
        return is_array($uids) ? array_filter(array_column($uids, 'uid')) : $uids;
    }

    /**
     * 获取出借人信息列表
     * 取得改项目下的所有的有效订单
     */
    public function getLenderListByPrjId($prj_id,$only_total = false)
    {
        $where = array(
            //已支付的(正式的时候要打开)
            'fi_prj_order.status' => self::STATUS_PAY_SUCCESS,
            'fi_prj_order.is_regist' => 0,
            'fi_prj_order.prj_id' => $prj_id,
            'fi_prj_order.uid' => array("GT",0),
        );
        $join = array(
            'fi_user ON fi_prj_order.uid = fi_user.uid',
            'fi_invest_prj ON fi_prj_order.prj_id = fi_invest_prj.prj_id',
        );
        $field = array(
            'fi_prj_order.id',
            'fi_prj_order.uid',
            'uname',
            'real_name',
            'person_id',
            'money',
            'fi_invest_prj.value_date',
            'possible_yield',
        );
        $total = $this->join($join)->where($where)->count();
        if($only_total){
            return $total;
        }
        !$total && throw_exception("还没有任何人对该项目投资!");
        $list = $this->join($join)->where($where)->field($field)->select();
        return $list;
    }



    /**
     * 根据项目的ID获取订单信息
     */
    public function getOrderListByWhere($where,$field = array())
    {
        return $this->where($where)->field($field)->select();
    }

    public function updateField($id,$field,$value)
    {
        $data = array($field=>$value);
        if($field == 'total_amount'){
            $data['surplus_amount'] = $value;
        }
        return $this->where(array('id'=>$id))->save($data);
    }

    /**
     * 一个项目的第一笔订单信息
     * @param $prj_id
     * @return array
     */
    public function getFirstOrderOfPrj($prj_id, $uid = 0)
    {
        $condition = array(
            'prj_id' => $prj_id,
            'status' => array('neq', self::STATUS_NOT_PAY),
        );
        if ($uid) {
            $condition['uid'] = $uid;
        }
        $order = $this->where($condition)->find();
        return (array) $order;
    }
    /**
     * 投资的项目订单数量
     * @param $prj_id
     * @return
     */
    public function getOrderOfPrjNum($prj_id, $uid = 0)
    {
        $condition = array(
            'prj_id' => $prj_id,
            'status' => array('neq', self::STATUS_NOT_PAY),
        );
        if ($uid) {
            $condition['uid'] = $uid;
        }
        $count = $this->where($condition)->count();
        return $count;
    }

    /**
     * 获取我的投资信息2
     */
    public function getMyOrderInfo2($order_id,$uid)
    {
        !$uid && throw_exception("用户UID不合法!");
        $where = array(
            //已支付的(正式的时候要打开)
            'fi_prj_order.is_regist' => 0,
            'fi_prj_order.id' => $order_id,
            'fi_prj_order.uid' => $uid
        );
        $join = array(
            'fi_user ON fi_prj_order.uid = fi_user.uid',
            'fi_invest_prj ON fi_prj_order.prj_id = fi_invest_prj.prj_id',
        );
        $field = array(
            'uname',
            'real_name',
            'money',
            'prj_type',
            'fi_prj_order.prj_id',
            'time_limit_day',
            'fi_user.ctime'
        );
        $total = $this->join($join)->where($where)->count();
        !$total && throw_exception("您还没有对该项目投资!");
        $list = $this->join($join)->where($where)->field($field)->find();
        return $list;
    }

    public function checkfirst($uid) {
        $where = array('uid' => $uid);
        $total = intval($this->where($where)->count());
        if ($total == 1) {
            return TRUE;
        } else {
            return FALSE;
        }
    }
    
    // 设置订单的债权转让状态(司马小鑫)
    private function setTransStatus($obj_id, $trans_status, $is_prj_id = false)
    {
        if (!$is_prj_id) {
            $order_id = $obj_id;
        } else {
            $order_id = M('prj_ext')->where(array('prj_id' => $obj_id))->getField('from_order_id');
        }
        $this->where(array('id' => $order_id))->save(array('trans_status' => $trans_status));

        return true;
    }

    public function setTransStatusWait($obj_id, $is_prj_id = false)
    {
        return $this->setTransStatus($obj_id, self::TRANS_STATUS_WAIT, $is_prj_id);
    }

    public function setTransStatusBiding($obj_id, $is_prj_id = false)
    {
        return $this->setTransStatus($obj_id, self::TRANS_STATUS_BIDING, $is_prj_id);
    }

    public function setTransStatusEndPart($obj_id, $is_prj_id = false)
    {
        return $this->setTransStatus($obj_id, self::TRANS_STATUS_END_PART, $is_prj_id);
    }

    public function setTransStatusEndFull($obj_id, $is_prj_id = false)
    {
        return $this->setTransStatus($obj_id, self::TRANS_STATUS_END_FULL, $is_prj_id);
    }
}

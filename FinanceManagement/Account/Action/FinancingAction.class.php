<?php
import_app("Financing.Model.PrjModel");
import_app("Financing.Model.PrjOrderPreModel");
import_app("Financing.Model.PrjOrderModel");
import_app("Financing.Model.PrjOrderRepayPlanModel");
import_app("Financing.Model.PrjRepayPlanModel");
import_app("Financing.Model.AssetTransferModel");
class FinancingAction extends BaseAction{
	const PAGESIZE = 10;
	
	private $uid = 0;
	private $project_mapping = array();
	private $transfer_mapping = array();
	private $corpfinancing_mapping = array();
	private $time_limit_display = array();      // 11天、12个月的区别
	private $corpfinancing_status_display = array();
	private $prj_bid_status_display = array();
	//test

	public function _initialize() {
		parent::_initialize();
		$this->uid = $this->loginedUserInfo['uid'];
        $this->assign('basicUserInfo',service('Account/Account')->basicUserInfo($this->loginedUserInfo));
        // 初始化筛选条件
		$now = time();
		$this->time_limit_display = array(
				PrjModel::LIMIT_UNIT_DAY => '天',
				PrjModel::LIMIT_UNIT_MONTH => '个月',
		);
		$this->transfer_mapping = array(
				'STATUS' => array(
						'0'=>array("pt.status"=>array("NEQ",AssetTransferModel::STATUS_DELETE)),
						// 转让中
						'1' => array(
								'pt.status' => AssetTransferModel::STATUS_WATING,
								//                    'pt.end_time' => array('GT', $now),
						),
						// 已转让
						'2' => array(
								'pt.status' => AssetTransferModel::STATUS_PROCESSED,
						),
						// 已过期
						'3' => array(
								'pt.status' => AssetTransferModel::STATUS_CANSEL,
								//                    'pt.end_time' => array('LT', $now),
						),
				),
				'ORDERBY' => array(
						'1' => 'po.expect_repay_time', // 还款日期
						'2' => 'pj.rate', // 预期利率
						'3' => 'pj.time_limit_day', // 期限
						'4' => 'po.money', // 投资金额
						'5' => 'pt.money', // 转让价格
						'6' => 'pt.buy_time', // 转让时间
				),
		);
		
		$this->prj_bid_status_display = array(
				PrjModel::BSTATUS_WATING => '待开标',
				PrjModel::BSTATUS_BIDING => '投标中',
				PrjModel::BSTATUS_FULL => '已满标',
				PrjModel::BSTATUS_REPAYING => '待还款',
				PrjModel::BSTATUS_REPAID => '已还款结束',
				PrjModel::BSTATUS_END => '截止投标',
				PrjModel::BSTATUS_REPAY_IN => '还款中',
				PrjModel::BSTATUS_FAILD => '已流标',
		);
	}
	
    /**
     * 显示页面
     */
    function index(){
    	$this->display("index");
    }
    /**
     * 我的预投
     */
    function preInvest(){
        $this->display("preInvest");
    }

    function ajaxXjhList()
    {
        $time_type = I("type", 1, 'intval');
        $start_date = I("startDate", '');
        $end_date = I("endDate", '');
        $status = I("status", 0, 'intval');
        $page       = I("p",  1, 'intval');
        $page_size  = I('size',  20, 'intval');

        $parameter=array('type'=>$time_type,'startDate'=>$start_date,'endDate'=>$end_date,'status'=>$status,);

        $filter['status'] = $status;

        if ($time_type == 1) {
            $filter['ctime'] = array($start_date, $end_date);
        } elseif($time_type == 2) {
            $filter['repay_date'] = array($start_date, $end_date);
        }

        $orderBy = $this->_request("order");
        $paramer["order"] = $orderBy;
        $orderArr = array();
        if($orderBy){
            list($field,$order) = explode("|", $orderBy);

            if($field == 'order11'){ // 下一还款日
                    $orderArr["repay_date"] = $order;
            }elseif($field == 'order14'){
                $orderArr["money"] = $order;
            }elseif($field == 'order15'){
                $orderArr["possible_yield"] = $order;
            }elseif($field == 'order16') {
                $orderArr["ctime"] = $order;
            }
        }
        $this->assign("order_field",$field);
        $this->assign("order_type",$order);

        $uid=$this->uid;

        $xprj_order_model = new XPrjOrderModel();
        if (empty($orderArr)) {
            if($status == 50) { //委托中
                $orderArr['repay_date'] = 'asc';
            }elseif($status == 3) { //委托结束
                $orderArr['repay_date'] = 'desc';
            }
            $orderArr['ctime'] = 'desc';
        }
        $xprj_orders = $xprj_order_model->getOrdersByUid($uid, $page, $page_size, $filter,$orderArr);
        //p(D('Financing/XPrjOrder')->getLastSql());

        if ($xprj_orders['list']) {
            foreach ($xprj_orders['list'] as $key => $each) {
                $xprj_orders['list'][$key]['expect_repay_time_view'] = date('Y.m.d', $each['repay_date']);
                $xprj_orders['list'][$key]['money_view'] = humanMoney($each['money'],2,false);
                $xprj_orders['list'][$key]['rest_income_view'] = humanMoney($each['possible_yield']+$each['addon_yield'],2,false);
                $xprj_orders['list'][$key]['order_date_view'] = date('Y.m.d',$each['ctime']);
                $xprj_orders['list'][$key]['order_time_view'] = date('H:i:s',$each['ctime']);
                $xprj_orders['list'][$key]['xprj_name'] = '理财计划 -'.$each['time_limit_day'].'天';
                $status = explode('|', D('Financing/XprjOrder')->getStatusShowName($each['status']));
                $xprj_orders['list'][$key]['status'] = $status[0];
                $xprj_orders['list'][$key]['status_show'] = $status[1];

                if (in_array($each['status'], array(1, 5, 41))) {
                    $xprj_orders['list'][$key]['status_show'] = '委托中';
                } elseif ($each['status'] == 4) {
                    $xprj_orders['list'][$key]['status_show'] = '委托结束';
                }

                if ($xprj_orders['list'][$key]['activity_id']) {
                    $activity = D('Application/RewardRule')->getRewardRuleInfo($xprj_orders['list'][$key]['activity_id']);
                    $activity_rate = $activity['reward_money_rate_number'];
                } else {
                    $activity_rate = 0;
                    $activity = null;
                }

                //6.2.0 pc司马小鑫新增使用奖励数据  20161031
                $xreward = M('user_bonus_freeze')->where(array('uid' => $uid, 'prj_id' => $each['xprj_id'], 'project_type' => 1, 'has_freeze' => 0, 'order_id' => $each['id']))->find();
                if ($xreward) {
                    $xprj_orders['list'][$key]['reward_type'] = $xreward['type'];
                    if ($xreward['type'] == 1) { //红包
                        $xprj_orders['list'][$key]['reward_type_tips'] = '红包' . humanMoney($xreward['amount'], 2, true);
                    } elseif ($xreward['type'] == 2) { //满减券
                        $xprj_orders['list'][$key]['reward_type_tips'] = '满减券' . humanMoney($xreward['amount'], 0, true);
                    } elseif ($xreward['type'] == 3) { //加息券
                        $xprj_orders['list'][$key]['reward_type_tips'] = '加息券' . number_format($xreward['rate'] / 10, 2) . '%';
                    }
                }
                //总利率加上加息利率
                $xprj_orders['list'][$key]['rate'] = floatval(($activity_rate + $xprj_orders['list'][$key]['rate'] + $xprj_orders['list'][$key]['addon_rate']+ $xprj_orders['list'][$key]['jiaxi_rate']) / 10) . "%";
                $xprj_orders['list'][$key]['addon_rate'] = floatval($xprj_orders['list'][$key]['addon_rate'] / 10) . "%";
                $xprj_orders['list'][$key]['activity_rate_raw'] = $activity_rate;
                $xprj_orders['list'][$key]['addon_rate_raw'] = $each['addon_rate'];

            }
        }

        $status_dict = array('0' => '全部', '1' => '投标结束', '2' => '待还款', '3' => '已还款结束');
        $pageinfo = array(
            'record_count' => intval($xprj_orders['count']),
            'page_count' => ceil($xprj_orders['count'] / $page_size),
            'page_no' => $page,
            'page_size' => $page_size
        );

        $totalRow = intval($xprj_orders['count']);

        //投资结束
        $where = [
            'uid'=>$uid,
            'status'=>array('IN',array(XPrjOrderModel::STATUS_INIT)),
        ];
        $total1 = $xprj_order_model->where($where)->count();
        //待还款
        $where['status'] = array('IN', array(XPrjOrderModel::STATUS_WAIT_PAY,XPrjOrderModel::STATUS_CLOSING));
        $total2 = $xprj_order_model->where($where)->count();
        //已结算
        $where['status'] = XPrjOrderModel::STATUS_FINISH;
        $total3 = $xprj_order_model->where($where)->count();

        $total0 = $total1+$total2+$total3;
        $this->assign("totalAll",$total0);
        $this->assign("totalBiding",$total1);
        $this->assign("totalRepaying",$total2);
        $this->assign("totalEnd",$total3);

        /**
         * 司马小鑫状态改为委托中和委托结束
         * 原来的已生效、待还款、清算中属于委托中
         * 已结算属于委托结束
         */
        $this->assign("totalCommissioned",$total1+$total2);

        //分页
        $paging  = W("Paging",array("totalRows"=>$pageinfo['record_count'],"pageSize"=>$page_size,"parameter"=>$parameter),true);
        $this->assign("paging",$paging);

        $this->assign('list', $xprj_orders['list']);
        $this->assign('status_dict',$status_dict);
        $this->display();
    }

    function xjhInvestList()
    {
        $xoid    = I('xoid', 0, 'intval');
        $page       = I('p',  1, 'intval');
        $page_size  = I('size',  20, 'intval');

        if ($page<1) {
            $page=1;
        }

        if ($page_size<5) {
            $page_size=5;
        }

        if (empty($xoid)) {
            $this->error('参数值错误');
        }

        $xprj_order=D('Financing/XPrjOrder')->where(array('id'=>$xoid,'uid'=>$this->loginedUserInfo['uid']))->find();
        if (empty($xprj_order)) {
            $this->error('该订单不存在');
        }

        $xprj_name = '理财计划-'.$xprj_order['time_limit_day'].'天';

        $result = service('Financing/XprjOrder')->getMyOrderInXjh($this->loginedUserInfo['uid'], $xoid, $page, $page_size,false);
        //p($result);

        $orders=$result['list'];
        $prjService=service('Financing/Project');
        foreach ($orders as $key=>$value)
        {
            $orders[$key]['have_transfer_protocol']=0;
            $orders[$key]['have_loan_protocol']=0;
            if ($value['is_have_protocol']==1) {
                $orders[$key]['have_loan_protocol']=1;
            }elseif ($value['is_have_protocol']==2) {
                $orders[$key]['have_transfer_protocol']=1;
            }elseif ($value['is_have_protocol']==3) {
                $orders[$key]['have_loan_protocol']=1;
                $orders[$key]['have_transfer_protocol']=1;
            }
            $orders[$key]['ctime_date'] = date('Y.m.d', $value['freeze_time']);
            $orders[$key]['ctime_time'] = date('H:i:s', $value['freeze_time']);
            $orders[$key]['prj_type_view'] = $prjService->getPrjType($value['prj_type']);
        }

        //分页
//        $paging  = W("Paging",array("totalRows"=>$result['page']['total'],"pageSize"=>$result['page']['page_size'],"parameter"=>array(),true));
//        $this->assign("paging",$paging);

        $this->assign('list',$orders);
        $this->assign('xprj_name',$xprj_name);
        $this->assign('xjh_begin',$result['xjh_begin']);
        $this->assign('xjh_result',$result['xjh_result']);
        $this->display();
    }

    function xjhOrder()
    {
        $xoid    = I('xoid', 0, 'intval');

        if (empty($xoid)) {
            $this->error('参数值错误');
        }

        $xprj_order=D('Financing/XPrjOrder')->where(array('id'=>$xoid,'uid'=>$this->loginedUserInfo['uid']))->find();
        if (empty($xprj_order)) {
            $this->error('该订单不存在');
        }

        $xprj_name = '司马小鑫-'.$xprj_order['time_limit_day'].'天';
        $this->assign('xprj_name',$xprj_name);
        $this->display();
    }

    function ajaxXjhOrderList()
    {
        $xoid    = I('xoid', 0, 'intval');
        $page       = I('p',  1, 'intval');
        $page_size  = I('size',  20, 'intval');

        $parameter=array('xoid'=>$xoid);

        if ($page<1) {
            $page=1;
        }

        if ($page_size<5) {
            $page_size=5;
        }

        if (empty($xoid)) {
            $this->error('参数值错误');
        }

        $xprj_order=D('Financing/XPrjOrder')->where(array('id'=>$xoid,'uid'=>$this->loginedUserInfo['uid']))->find();
        if (empty($xprj_order)) {
            $this->error('该订单不存在');
        }

        $xprj_name = '理财计划-'.$xprj_order['time_limit_day'].'天';

        $result = service('Financing/XprjOrder')->getMyOrderInXjh($this->loginedUserInfo['uid'], $xoid, $page, $page_size,false);
        //p($result);

        $orders=$result['list'];
        $prjService=service('Financing/Project');
        foreach ($orders as $key=>$value)
        {
            $orders[$key]['have_transfer_protocol']=0;
            $orders[$key]['have_loan_protocol']=0;
            if ($value['is_have_protocol']==1) {
                $orders[$key]['have_loan_protocol']=1;
            }elseif ($value['is_have_protocol']==2) {
                $orders[$key]['have_transfer_protocol']=1;
            }elseif ($value['is_have_protocol']==3) {
                $orders[$key]['have_loan_protocol']=1;
                $orders[$key]['have_transfer_protocol']=1;
            }
            $orders[$key]['ctime_date'] = date('Y.m.d', $value['freeze_time']);
            $orders[$key]['ctime_time'] = date('H:i:s', $value['freeze_time']);
            $orders[$key]['prj_type_view'] = $prjService->getPrjType($value['prj_type']);
            if ($orders[$key]['root_prj_id']) {
                $orders[$key]['prj_id_view']=$orders[$key]['root_prj_id'];
            }
            else {
                $orders[$key]['prj_id_view']=$orders[$key]['prj_id'];
            }
        }
        //分页
        $paging  = W("Paging",array("totalRows"=>$result['page']['total'],"pageSize"=>$result['page']['page_size'],"parameter"=>$parameter),true);
        $this->assign("paging",$paging);

        $this->assign('list',$orders);
        $this->assign('xprj_name',$xprj_name);
        $this->assign('xjh_begin',$result['xjh_begin']);
        $this->assign('xjh_result',$result['xjh_result']);
        $this->display();
    }

    function getTransferList()
    {
        $oid    = I('oid', 0, 'intval');
        $this->assign('oid',$oid);
        $this->display();
    }

    function getTransferListA()
    {
        $oid    = I('oid', 0, 'intval');
        $page       = I("p",  1, 'intval');
        $page_size  = I('size',  5, 'intval');

        D('Financing/Prj');
        $where = "(id=%d AND uid=%d AND prj_type='%s') OR (from_order_id=%d AND prj_type='%s')";
        $where_values = array($oid, $this->loginedUserInfo['uid'], PrjModel::PRJ_TYPE_J, $oid, PrjModel::PRJ_TYPE_J);
        $count = D('Financing/PrjOrder')->where($where, $where_values)->count();
        $total_page = ceil($count / $page_size);
        //p($count);
        $start = ($page - 1) * $page_size;
        $orders = D('Financing/PrjOrder')->where($where, $where_values)->limit($start, $page_size)->select();
        //p( D('Financing/PrjOrder')->getLastSql());

        if ($orders) {
            $uids = array_filter(array_column($orders, 'uid'));
            $where = array('uid' => array('IN', $uids));
            $user_info = D('Account/User')
                ->where($where)
                ->field('uid, real_name')
                ->select();
            $user_info = $user_info ? array_column($user_info, 'real_name', 'uid') : array();

            $orders = array_distill($orders, 'id,order_no,uid,money,ctime');

            foreach ($orders as $key => $each) {
                $orders[$key]['real_name'] = marskName($user_info[$each['uid']], 1, 0);
                $orders[$key]['ctime'] = date('Y-m-d H:i:s', $each['ctime']);
                $orders[$key]['money_view'] = humanMoney($each['money'],2,false);
            }
        }
        //p($orders);
        $this->assign('oid',$oid);
        $this->assign('order',$orders);
        $this->assign('page',$page);
        $this->assign('total_page',$total_page);
        $this->display();
    }

    //所有系列
    function ajaxlist(){
        try{
            $prj_id = I('request.prj_id');
            $prjName = $this->_request("prjname");
            $paramer["prjname"] = $prjName;
            $timeType = (int) $this->_request("timeType");
            $paramer["timeType"] = $timeType;
            $startDate = $this->_request("startDate");
            $paramer["startDate"] = $startDate;
            $endDate = $this->_request("endDate");
            $paramer["endDate"] = $endDate;
            $prjType =  (int) $this->_request("ptype");
            $paramer["ptype"] = $prjType;

            $st = (int) $this->_request("status");//2016226 投资中、投资结束为统一的投资结束(将前端传来的1或3 统一整合为3)
            $paramer["status"] = $st;
            $st = $st ? $st : 0;

            $this->st = $st;

            $page = (int) $this->_request("p");
            $paramer["p"] = $page;
            $page = $page ? $page :1;
            $pageSize = 10;

            $orderBy = $this->_request("order");
            $paramer["order"] = $orderBy;
            $orderArr = array();
            if($orderBy){
                list($field,$order) = explode("|", $orderBy);
                $this->assign("order_field",$field);
                $this->assign("order_type",$order);

                if($field == 'order11'){ // 下一还款日
                    if($st == 5){ // 已还款结束
                        $orderArr["person_repayment.repay_time"] = $order;
                    }else{
                        $orderArr["prj_order.expect_repay_time"] = $order;
                    }
                }elseif($field == 'order12'){
                    $orderArr["prj.year_rate"] = $order;
                }elseif($field == 'order13'){
                    $orderArr["prj.time_limit_unit"] = $order;
                    $orderArr["prj.time_limit"] = $order;
                }
                elseif($field == 'order14'){
                    if($st == 5){
                        $orderArr["person_repayment.capital"] = $order;
                    }else{
                        $orderArr["prj_order.rest_money"] = $order;
                    }
                }
                elseif($field == 'order15'){
                    if($st == 5){
                        $orderArr["person_repayment.profit"] = $order;
                    }else{
                        $orderArr["prj_order.possible_yield"] = $order;
                    }
                }
                elseif($field == 'order16'){
                    $orderArr["prj_order.ctime"] = $order;
                }elseif($field == 'order27'){
                    $orderArr["prj.end_bid_time"] = $order;
                }
            }else{
                if($st == 0) { // 全部
                    $orderArr = 'bid_status_order ASC,
  (CASE WHEN prj.bid_status = ' . PrjModel::BSTATUS_REPAYING . ' OR prj.bid_status = ' . PrjModel::BSTATUS_REPAY_IN. ' OR prj.bid_status = ' . PrjModel::BSTATUS_ZS_REPAYING . ' THEN prj_order.expect_repay_time END) ASC,
  (CASE WHEN prj.bid_status = ' . PrjModel::BSTATUS_BIDING . ' OR prj.bid_status = ' . PrjModel::BSTATUS_FULL . ' OR prj.bid_status = ' . PrjModel::BSTATUS_END . ' THEN prj_order.ctime END) DESC,
  (CASE WHEN prj.bid_status = ' . PrjModel::BSTATUS_REPAID . ' THEN prj_order.expect_repay_time END) DESC';
                    $order_field = $order_type = '';
                } elseif($st == 1) { // 投标中
                    $orderArr = 'prj_order.ctime DESC';
                    $order_field = 'order16';
                    $order_type = 'desc';
                } elseif($st == 3) { // 投资结束
                    $orderArr = 'prj_order.ctime DESC';
                    $order_field = 'order16';
                    $order_type = 'desc';
                } elseif($st == 4) { // 待还款
                    $orderArr = 'prj_order.expect_repay_time ASC';
                    $order_field = 'order11';
                    $order_type = 'asc';
                } elseif($st == 5) { // 已还款结束
                    $orderArr = 'prj_order.expect_repay_time DESC';
                    $order_field = 'order11';
                    $order_type = 'desc';
                }
                if($order_field) $this->assign('order_field', $order_field);
                if($order_type) $this->assign('order_type', $order_type);
            }

            if($prj_id) {
                $where['prj.id'] = array('IN', explode(',', $prj_id));
            }
            if($prjName) $where['prj.prj_name'] = array("like","%".$prjName."%");

            $timeType = (int) $timeType;
            $endTime = $endDate ? strtotime("+1 days",strtotime($endDate)):0;
            if($timeType == 1){//成交日期
                if($startDate) $where['prj_order.ctime'][] = array("EGT",strtotime($startDate));
                if($endTime) $where['prj_order.ctime'][] = array("LT",$endTime);
            }elseif($timeType == 2){//回款日期
                if($startDate) $where['prj_order.expect_repay_time'][] = array("EGT",strtotime($startDate));
                if($endTime) $where['prj_order.expect_repay_time'][] = array("LT",$endTime);
            }

            $service = service("Account/Record");
            $where['prj_order.status'] = array("NEQ",PrjOrderModel::STATUS_NOT_PAY);
            $where['prj_order.rest_money'] = array("GT",0);
            //不包含待开标
            $where['prj.bid_status'] = array("NEQ",PrjModel::BSTATUS_WATING);

            //不包含司马小鑫下
            $where['prj_order.xprj_order_id'] = array("EQ", 0);

            $where['prj.prj_class'] = PrjModel::PRJ_CLASS_TZ;
            if($prjType == 1){
                $where['prj.prj_type'] = PrjModel::PRJ_TYPE_A;
            }elseif($prjType == 2){
                $where['prj.prj_type'] = PrjModel::PRJ_TYPE_F;
            }elseif($prjType == 3){
                $where['prj.prj_type'] = PrjModel::PRJ_TYPE_B;
            }elseif($prjType == 4){
                $where['prj.prj_type'] = PrjModel::PRJ_TYPE_H;
            }elseif($prjType == 5){
                $where['prj.prj_type'] = PrjModel::PRJ_TYPE_I;
            }

//         print_r($orderArr);
//         echo "<pre>";
//        print_r($where);
            // 债券转让的排后面
            if(is_array($orderArr)) $orderArr = array_merge(array('creditor_rights_status' => 'ASC'), $orderArr);
            else $orderArr = "creditor_rights_status ASC, {$orderArr}";

            // 流标的排后面
            if(is_array($orderArr)) $orderArr = array_merge(array('is_liubiao' => 'ASC'), $orderArr);
            else $orderArr = "is_liubiao ASC, {$orderArr}";

            $where['prj_order.uid'] = $this->loginedUserInfo['uid'];
            $where_count = $where;
            unset($where_count['prj_order.rest_money']);
            $this->totalAll = $service->getFinancingCount($where_count);
            if($st ==0){//	全部
                unset($where['prj_order.rest_money']);

                // 包含流标
                $_where = array($where);
                if($where['prj.prj_type']){
                    $_where['prj_order.is_liubiao'] = array('EXP', '=1 AND prj_order.uid='.$this->loginedUserInfo['uid'].' AND prj.prj_type='."'".$where['prj.prj_type']."'");
                }else{
                    $_where['prj_order.is_liubiao'] = array('EXP', '=1 AND prj_order.uid='.$this->loginedUserInfo['uid']);
                }

                $_where['_logic'] = 'OR';

                // 包含预售失败
                $__where = array($_where);
                if($where['prj.prj_type']){
                    $tp = $where['prj.prj_type'];
                    $__where['prj_order.pre_sale_status'] = array('EXP', 'in ( '.PrjOrderPreModel::STATUS_FAIL.',' .PrjOrderPreModel::STATUS_DEFAULT.' ) AND prj.prj_type='."'$tp'".' AND prj_order.uid='.$this->loginedUserInfo['uid']);
                }else{
                    $__where['prj_order.pre_sale_status'] = array('EXP', 'in ( '.PrjOrderPreModel::STATUS_FAIL.',' .PrjOrderPreModel::STATUS_DEFAULT.' ) AND prj_order.uid='.$this->loginedUserInfo['uid']);
                }
                $__where['_logic'] = 'OR';

                $result = $service->financingList($__where,$orderArr,$page,$pageSize,'',true,1);
                $where['prj_order.rest_money'] = array("GT",0);
            }
            elseif($st == 3){//已满标(2016226 将投标中的部分并入到投资结束) start
                $where['prj.bid_status'] = array("IN",array(PrjModel::BSTATUS_BIDING,PrjModel::BSTATUS_FULL,PrjModel::BSTATUS_END));
                $__where = array($where);
                if($where['prj.prj_type']){
                    $__where['prj_order.pre_sale_status'] = array('EXP', '='.PrjOrderPreModel::STATUS_DEFAULT.' AND prj_order.uid='.$this->loginedUserInfo['uid'].' AND prj.prj_type='."'".$where['prj.prj_type']."'");
                }else{
                    $__where['prj_order.pre_sale_status'] = array('EXP', '='.PrjOrderPreModel::STATUS_DEFAULT.' AND prj_order.uid='.$this->loginedUserInfo['uid']);
                }
                $__where['_logic'] = 'OR';
                $result = $service->financingList($__where,$orderArr,$page,$pageSize,'',true,1);//end
            }elseif($st == 4){//待还款
                unset($where['prj_order.rest_money']);
                $where['prj.bid_status'] = array("IN",array(PrjModel::BSTATUS_REPAYING,PrjModel::BSTATUS_REPAY_IN,PrjModel::BSTATUS_END_PAY,PrjModel::BSTATUS_FULL_PAY,PrjModel::BSTATUS_ZS_REPAYING));
                $result = $service->financingList($where,$orderArr,$page,$pageSize,'',true,1);
            }elseif($st == 5){//已还款结束
//            $where['prj.bid_status'] = PrjModel::BSTATUS_REPAID;
                $where['prj_order.repay_status'] = PrjOrderModel::REPAY_STATUS_ALL_REPAYMENT;
                $where['prj.bid_status'] = PrjModel::BSTATUS_REPAID;
                unset($where['prj_order.rest_money']);
                $result = $service->financingList($where,$orderArr,$page,$pageSize,'',true,1);
            }elseif($st == 6){//已流标
                unset($where['prj_order.rest_money']);
                unset($where['prj_order.status']);
                $where['prj_order.is_liubiao'] = 1;
                $result = $service->financingList($where,$orderArr,$page,$pageSize,'',true,1);
            }elseif($st == 7){//预售投标失败
                unset($where['prj_order.rest_money']);
                $where['prj_order.status'] = PrjOrderModel::STATUS_NOT_PAY;
                $where['prj_order.pre_sale_status'] = array("EQ",PrjOrderPreModel::STATUS_FAIL);
                $result = $service->financingList($where,$orderArr,$page,$pageSize,'',true,1);
            }
            unset($where['prj_order.pre_sale_status']);
            $list = $result['data'];
//         echo "<pre>";
//        print_r($list);
//         print_r($result);
            $totalRow = $result['total_row'];
            $this->assign("list",$list);
            //分页
            $paging  = W("Paging",array("totalRows"=>$totalRow,"pageSize"=>$pageSize,"parameter"=>$paramer),true);
            $this->assign("paging",$paging);

            //数目
            unset($where['prj_order.repay_status']);
            if($timeType == 2){
                unset($where['person_repayment.repay_time']);
                if($startDate) $where['prj_order.expect_repay_time'][] = array("EGT",strtotime($startDate));
                if($endTime) $where['prj_order.expect_repay_time'][] = array("LT",$endTime);
            }
            $where['prj_order.status'] = array("NEQ",PrjOrderModel::STATUS_NOT_PAY);
            unset($where['prj_order.is_liubiao']);
            $where['prj_order.rest_money'] = array("GT",0);
            $where['prj.bid_status'] = PrjModel::BSTATUS_BIDING;
            //包含预售投标中
            $__where = array($where);
            if($where['prj.prj_type']){
                $tp = $where['prj.prj_type'];
                $__where['prj_order.pre_sale_status'] = array('EXP', '='.PrjOrderPreModel::STATUS_DEFAULT.' AND prj.prj_type='."'$tp'".' AND prj_order.uid='.$this->loginedUserInfo['uid']);
            }else{
                $__where['prj_order.pre_sale_status'] = array('EXP', '='.PrjOrderPreModel::STATUS_DEFAULT.' AND prj_order.uid='.$this->loginedUserInfo['uid']);
            }
            $__where['_logic'] = 'OR';
            $this->totalBiding = $service->getFinancingCount($__where);
//        $where['prj.bid_status'] = PrjModel::BSTATUS_END;
//        $this->totalEnd = $service->getFinancingCount($where);
            $where['prj.bid_status'] = array("IN",array(PrjModel::BSTATUS_BIDING,PrjModel::BSTATUS_FULL,PrjModel::BSTATUS_END));//2016226 计算每个状态下的记录总数:将投资中PrjModel::BSTATUS_BIDING合并到投资结束里
            $this->totalFull = $service->getFinancingCount($where);
//        $where['prj.bid_status'] = PrjModel::BSTATUS_REPAY_IN;
//        $this->totalRepayIN = $service->getFinancingCount($where);
            $where['prj.bid_status'] = array("IN",array(PrjModel::BSTATUS_REPAYING,PrjModel::BSTATUS_ZS_REPAYING,PrjModel::BSTATUS_REPAY_IN,PrjModel::BSTATUS_END_PAY,PrjModel::BSTATUS_FULL_PAY));
            unset($where['prj_order.rest_money']);
            $this->totalRepaying = $service->getFinancingCount($where);

//        $where['prj.bid_status'] = PrjModel::BSTATUS_REPAID;
            $where['prj_order.repay_status'] = PrjOrderModel::REPAY_STATUS_ALL_REPAYMENT;
            $where['prj.bid_status'] = ['neq', PrjModel::BSTATUS_ZS_REPAYING];
            $this->totalRepaySuccess = $service->getFinancingCount($where);

            // 流标统计
            unset($where['prj_order.status']);
            unset($where['prj.bid_status']);
            unset($where['prj_order.repay_status']);
//        $where['prj.bid_status'] = PrjModel::BSTATUS_FAILD;
            $where['prj_order.is_liubiao'] = 1;
            $this->totalRebackPrj=$count_liubiao = $service->getFinancingCount($where);

            //预售标失败统计
            unset($where['prj_order.rest_money']);
            unset($where['prj_order.is_liubiao']);
            $where['prj_order.status'] = PrjOrderModel::STATUS_NOT_PAY;
            $where['prj_order.pre_sale_status'] = array("EQ",PrjOrderPreModel::STATUS_FAIL);
            $this->totalPreSaleFailPrj = $totalPreSaleFailPrj = $service->getFinancingCount($where);

            //预售进行中统计
            $where['prj_order.pre_sale_status'] = array("EQ",PrjOrderPreModel::STATUS_DEFAULT);
            $totalPreSaleDefaultPrj = $service->getFinancingCount($where);

            // 全部
//        $this->totalAll = $this->totalBiding+$this->totalFull+$this->totalRepaying+$this->totalRepaySuccess + $count_liubiao;
            $this->totalAll += $count_liubiao + $totalPreSaleDefaultPrj + $totalPreSaleFailPrj;
            $this->transfer_prj_ids = C('CREDITOR_TRANSFER_PRJ_ID');
            $this->assign('current_uid', $this->uid);
            $this->assign('REPAY_WAY_E', PrjModel::REPAY_WAY_E);

        } catch (Exception $e) {
            $this->assign("msg",$e->getMessage());
        }
      
        $this->display("ajaxlist");

    }

    /**
     * 我的预投 ajax加载列表
     * ptype : 0-所有
     * status : 0-全部 1-预约投资中 2-投资成功 3-投资失败
     */
    function ajaxPreList(){
        $where = $paramer = array();
        $prjName = I("request.prjname",'','trim');
        $paramer["prjname"] = $prjName;
        $timeType = (int) I("request.timeType");
        $paramer["timeType"] = $timeType;
        $startDate = I("request.startDate");
        $paramer["startDate"] = $startDate;
        $endDate = I("request.endDate");
        $paramer["endDate"] = $endDate;
        $prjType =  (int) I("request.ptype");
        $paramer["ptype"] = $prjType;

        $st = (int) I("request.status",'0');
        if($st > 0) $where['order_pre.status'] = $st;
        $paramer["status"] = $st;

        $page = (int) I("request.p",'1');
        $paramer["p"] = $page;
        $pageSize = 10;

        $orderBy = $this->_request("order");
        $paramer["order"] = $orderBy;
        $orderArr = array();
        if($orderBy){
            list($field,$order) = explode("|", $orderBy);
            $this->assign("order_field",$field);
            $this->assign("order_type",$order);

            if($field == 'order11'){
                $orderArr["order_pre.ctime"] = $order;
            }elseif($field == 'order12'){
                $orderArr["prj.year_rate"] = $order;
            }elseif($field == 'order13'){
                $orderArr["prj.time_limit_unit"] = $order;
                $orderArr["prj.time_limit"] = $order;
            }elseif($field == 'order14'){
                $orderArr["order_pre.money"] = $order;
            }elseif($field == 'order15'){
                $orderArr["prj.possible_yield"] = $order;
            }elseif($field == 'order16'){
                $orderArr["order_pre.mtime"] = $order;
            }
        }else{
            $orderArr = 'order_pre.ctime desc';
        }

        if($prjName) $where['prj.prj_name'] = array("like",$prjName."%");

        $endTime = $endDate ? strtotime("+1 days",strtotime($endDate)):0;
        if($timeType == 1){//成交日期
            if($startDate) $where['order_pre.ctime'][] = array("EGT",strtotime($startDate));
            if($endTime) $where['order_pre.ctime'][] = array("LT",$endTime);
        }

        $service = service("Account/Record");

        $where['prj.prj_class'] = PrjModel::PRJ_CLASS_TZ;
        if($prjType == 1){
            $where['prj.prj_type'] = PrjModel::PRJ_TYPE_A;
        }elseif($prjType == 2){
            $where['prj.prj_type'] = PrjModel::PRJ_TYPE_F;
        }elseif($prjType == 3){
            $where['prj.prj_type'] = PrjModel::PRJ_TYPE_B;
        }

        $uid = $where['order_pre.uid'] = $this->loginedUserInfo['uid'];

        $result = $service->preInvestList($where,$orderArr,$page,$pageSize,'',true);

        $list = $result['data'];

        $totalRow = $result['total_row'];
        $this->assign("list",$list);
        //分页
        $paging  = W("Paging",array("totalRows"=>$totalRow,"pageSize"=>$pageSize,"parameter"=>$paramer),true);
        $this->assign("paging",$paging);

        //数目
        $ret = $service->getPreInvestNum($uid);
        $this->assign("allNum", $ret);

        $this->display("ajaxPreList");
    }
    /**
     * doing
     */
    function fundList(){
    	
    	$prjName = $this->_request("prjname");
        $paramer["prjname"] = $prjName;
        $timeType = (int) $this->_request("timeType");
        $paramer["timeType"] = $timeType;
        $startDate = $this->_request("startDate");
        $paramer["startDate"] = $startDate;
        $endDate = $this->_request("endDate");
        $paramer["endDate"] = $endDate;
        $prjType =  (int) $this->_request("ptype");
        $paramer["ptype"] = $prjType;
        
        $prjClass = intval(I('request.pclass'));
        $paramer["pclass"] = $prjClass;
        
        $st = (int) $this->_request("status");
        $paramer["status"] = $st;
        $st = $st ? $st : 0;
        
        $this->st = $st;
        	
        $page = (int) $this->_request("p");
        $paramer["p"] = $page;
        $page = $page ? $page :1;
        $pageSize = 10;

        $orderBy = $this->_request("order");
        $paramer["order"] = $orderBy;
        $orderArr = array();
        if($orderBy){
            list($field,$order) = explode("|", $orderBy);
            $this->assign("order_field",$field);
            $this->assign("order_type",$order);

            if($field == 'order11'){ // 下一还款日
                if($st == 5){ // 已还款结束
                	$orderArr["person_repayment.repay_time"] = $order;
                }else{
                	$orderArr["prj_order.expect_repay_time"] = $order;
                }
            }elseif($field == 'order12'){
                $orderArr["prj.year_rate"] = $order;
            }elseif($field == 'order13'){
                $orderArr["prj.time_limit_unit"] = $order;
                $orderArr["prj.time_limit"] = $order;
            }
            elseif($field == 'order14'){
            	if($st == 5){
            		$orderArr["person_repayment.capital"] = $order;
            	}else{
                	$orderArr["prj_order.rest_money"] = $order;
            	}
            }
            elseif($field == 'order15'){
                if($st == 5){
            		$orderArr["person_repayment.profit"] = $order;
            	}else{
            		$orderArr["prj_order.possible_yield"] = $order;
            	}
            }
            elseif($field == 'order16'){
                $orderArr["prj_order.ctime"] = $order;
            }elseif($field == 'order27'){
                $orderArr["prj.end_bid_time"] = $order;
            }
        }else{
            $orderArr['bid_status_order'] = 'asc';
            $field = " prj_order.expect_repay_time";
            $order = "asc";
            $orderArr[$field] = $order;
            $field = " prj_order.ctime";
            $order = "desc";
            $orderArr[$field] = $order;
            $this->assign("order_field","order11");
            $this->assign("order_type","asc");
        }
        
        if($prjName) $where['prj.prj_name'] = array("like","%".$prjName."%");

        $timeType = (int) $timeType;
        $endTime = $endDate ? strtotime("+1 days",strtotime($endDate)):0;
        if($timeType == 1){//成交日期
            if($startDate) $where['prj_order.ctime'][] = array("EGT",strtotime($startDate));
            if($endTime) $where['prj_order.ctime'][] = array("LT",$endTime);
        }elseif($timeType == 2){//回款日期
            if($startDate) $where['prj_order.expect_repay_time'][] = array("EGT",strtotime($startDate));
            if($endTime) $where['prj_order.expect_repay_time'][] = array("LT",$endTime);
        }

        $service = service("Account/Record");
        $where['prj_order.status'] = array("NEQ",PrjOrderModel::STATUS_NOT_PAY);
        $where['prj_order.rest_money'] = array("GT",0);
        //不包含待开标
        $where['prj.bid_status'] = array("NEQ",PrjModel::BSTATUS_WATING);


        if($prjType == 1){
        	$where['prj.prj_type'] = PrjModel::PRJ_TYPE_A;
        }elseif($prjType == 2){
            $where['prj.prj_type'] = PrjModel::PRJ_TYPE_F;
        }elseif($prjType == 3){
            $where['prj.prj_type'] = PrjModel::PRJ_TYPE_B;
        }elseif($prjType == 4){
            $where['prj.prj_type'] = PrjModel::PRJ_TYPE_G;
        }
        
        if($prjClass == 1){
        	$where['prj.prj_class'] = PrjModel::PRJ_CLASS_TZ;
        }elseif($prjClass == 2){
        	$where['prj.prj_class'] = PrjModel::PRJ_CLASS_LC;
        }
        
        $where['prj_order.uid'] = $this->loginedUserInfo['uid'];
        if($st ==0){//	全部
        	unset($where['prj_order.rest_money']);
			
        	$result = $service->financingList($where,$orderArr,$page,$pageSize,'',true,1);
        	$where['prj_order.rest_money'] = array("GT",0);
        }elseif($st == 1){//投标中
            $where['prj_order.status'] = PrjOrderModel::STATUS_FREEZE;
            $result = $service->financingList($where,$orderArr,$page,$pageSize,'',true,1);
        }elseif($st == 3){//已满标
            $where['prj.bid_status'] = array("IN",array(PrjModel::BSTATUS_FULL,PrjModel::BSTATUS_END));
            $result = $service->financingList($where,$orderArr,$page,$pageSize,'',true,1);
        }elseif($st == 4){//待还款
        	$where['prj_order.status'] = PrjOrderModel::STATUS_PAY_SUCCESS;
        	$where['prj_order.repay_status'] = array('neq', PrjOrderModel::REPAY_STATUS_ALL_REPAYMENT);
            $result = $service->financingList($where,$orderArr,$page,$pageSize,'',true,1);
        }elseif($st == 5){//已还款结束
            $where['prj_order.status'] = PrjOrderModel::STATUS_PAY_SUCCESS;
        	$where['prj_order.repay_status'] = PrjOrderModel::REPAY_STATUS_ALL_REPAYMENT;
            unset($where['prj_order.rest_money']);
            $result = $service->financingList($where,$orderArr,$page,$pageSize,'',true,1);
        }
        $list = $result['data'];
        $totalRow = $result['total_row'];
        $this->assign("list",$list);
        //分页
        $paging  = W("Paging",array("totalRows"=>$totalRow,"pageSize"=>$pageSize,"parameter"=>$paramer),true);
        $this->assign("paging",$paging);

        //数目
        if($timeType == 2){
            unset($where['person_repayment.repay_time']);
            if($startDate) $where['prj_order.expect_repay_time'] = array("EGT",strtotime($startDate));
            if($endTime) $where['prj_order.expect_repay_time'] = array("LT",$endTime);
        }
		
        
        unset($where['prj_order.status']);
        unset($where['prj_order.repay_status']);
        $where['prj_order.rest_money'] = array("GT",0);
        $where['prj_order.status'] = PrjOrderModel::STATUS_FREEZE;
        $this->totalBiding = $service->getFinancingCount($where);
        
        $where['prj_order.status'] = PrjOrderModel::STATUS_PAY_SUCCESS;
        $where['prj_order.repay_status'] = array('neq', PrjOrderModel::REPAY_STATUS_ALL_REPAYMENT);
        $this->totalRepaying = $service->getFinancingCount($where);
        
        $where['prj_order.status'] = PrjOrderModel::STATUS_PAY_SUCCESS;
        $where['prj_order.repay_status'] = PrjOrderModel::REPAY_STATUS_ALL_REPAYMENT;
        unset($where['prj_order.rest_money']);
        $this->totalRepaySuccess = $service->getFinancingCount($where);
        
        // 流标统计
        unset($where['prj_order.status']);
        $where['prj.bid_status'] = PrjModel::BSTATUS_FAILD;
        $count_liubiao = $service->getFinancingCount($where);

        $this->totalAll = $this->totalBiding+$this->totalRepaying+$this->totalRepaySuccess;

        $this->assign('REPAY_WAY_E', PrjModel::REPAY_WAY_E);
        
    	$this->display("fundList");
    }
    
    /**
     * 理财显示页面
     */
    function fundIndex(){
    	$this->display("fundIndex");
    }
    
    /**
     * 显示还款情况
     */
    function ajaxShowRepay(){
    	$orderId = $this->_request("id");
    	import_app("Financing.Model.PrjOrderRepayPlanModel");
    	import_app("Financing.Model.PrjRepayPlanModel");
    	$list = M("prj_order_repay_plan")->where(array("prj_order_id"=>$orderId,"status"=>array("NEQ",PrjOrderRepayPlanModel::ORDER_REPAYMENT_STATUS_FAIL)))
    			->order(" repay_periods ASC")->select();
    	$data = array();
    	if($list){
    		foreach($list as $k=>$v){
                $temp = array();
                $temp['repay_date_view'] = date('Y-m-d',$v['repay_date']);
                $temp['pri_interest_view'] = humanMoney($v['pri_interest'],2,false);
                $temp['principal_view'] = humanMoney($v['principal'],2,false);
                $temp['yield_view'] = humanMoney($v['yield'],2,false);
                $temp['pri_interest_view'] = humanMoney($v['pri_interest'],2,false);
                $temp['rest_principal_view'] = humanMoney($v['rest_principal'],2,false);
    			if($v['status'] == PrjOrderRepayPlanModel::ORDER_REPAYMENT_STATUS_SUCCESS){
                    $temp['status_view'] = "√";
                    $temp['status_ok'] = 1;
    			}
    			else{
                    $temp['status_view'] = "待还";
                    $temp['status_ok'] = 0;
    			}
                $data[$v['ptype']][] = $temp;
    		}
    	}
    	
    	$this->list = $data;
    	$this->display("ajaxshowrepay");
    }
    
    //还款计划
    function ajaxShowPrjRepayPlan(){
    	$prjId = (int) $this->_request('id');
    	$prjInfo = service("Financing/Project")->getPrjInfo($prjId);
    	//         echo "<pre>";
    	//         print_r($prjInfo['borrower']);
    	if(!$prjInfo){
    		return false;
    	}

//        $realMoneyResult = M("prj_order")->field("SUM(rest_money+repay_principal) as realmoney")->
//            where(array("prj_id"=>$prjId,"status"=>array("NEQ",PrjOrderModel::STATUS_NOT_PAY),"is_regist"=>0))->find();
        $sql = "SELECT SUM(rest_money+repay_principal) AS realmoney FROM fi_prj_order WHERE prj_id=".$prjId." AND
             is_regist=0  AND ((transfer =0 AND `status`!=4) OR (transfer >0 AND `status` =4))";
        $realMoneyResult = M()->query($sql);
        $realMoney = $realMoneyResult[0]['realmoney'];
    	$obj = service("Financing/Project")->getTradeObj($prjId,"Bid");
    	if(!$obj) return false;

    	$t = strtotime(date('Y-m-d',$prjInfo['end_bid_time']));
    	$balanceTime = service("Financing/Project")->getBalanceTime($prjInfo['end_bid_time'],$prjInfo['id']);
    	if($prjInfo['end_bid_time']>$balanceTime) $t = strtotime("+1 days",$t);//T向后延迟一天
    	$params = array();
    	$params["freeze_date"] = date('Y-m-d',service("Financing/Project")->getPrjValueDate($prjId,$t));
    	$params['capital'] = $realMoney;
    	$params['prj_id'] = $prjId;
    	
    	$tableResult = $obj->getPersonRepaymentPlan($params);
    	if($tableResult){
            $first_repay_status = NULL;
    		foreach ($tableResult as $k=>$v){
    			//本息
    			$tableResult[$k]['repay_money_view'] = humanMoney($v['repay_money'],2,false);
    			//本金
    			$tableResult[$k]['capital_view'] = humanMoney($v['capital'],2,false);
    			//利息
    			$tableResult[$k]['profit_view'] = humanMoney($v['profit'],2,false);
    			
    			$tableResult[$k]['repay_money_view'] = humanMoney($v['repay_money'],2,false);
    			//剩余本金
    			$tableResult[$k]['last_capital_view'] = humanMoney($v['last_capital'],2,false);
    			//判断是否还款
    			$db = M("prj_repay_plan");
    			$checkinfo = $db->where(array("prj_id"=>$prjId,"repay_date"=>strtotime($v['repay_date']),"status"=>array("NEQ",PrjRepayPlanModel::PRJ_REPAYMENT_STATUS_FAIL)))->find();
//     			echo $db->getLastSql()."<br>";
    			if($checkinfo['status'] == PrjRepayPlanModel::PRJ_REPAYMENT_STATUS_SUCCESS){
    				$tableResult[$k]['status_view'] = "<i class='ico_done'></i>";
    			}else{
    				$tableResult[$k]['status_view'] = "待还";
    			}
                if(is_null($first_repay_status)) $first_repay_status = $tableResult[$k]['status_view'];
    			
    		}
    	}
    	$this->tableResult = $tableResult;

        // 上面用款期的相当于是预期的，这里募集期也搞成预期的
        $bidInfo = service("Financing/Invest")->getBidTimeInfo($prjId);
        $bidInfo['status_view'] = $first_repay_status;

//    	$bidInfo = M("prj_repay_plan")->where(array("prj_id"=>$prjId,"is_auto"=>1))->find();
//    	$bidInfo['repayDate'] = date('Y-m-d',$bidInfo['repay_date']);
//    	$bidInfo['incomeView'] = humanMoney($bidInfo['yield'],2,false);
//    	$bidInfo['repay_money_view'] = humanMoney($bidInfo['pri_interest'],2,false);
//    	$bidInfo['amountView'] = humanMoney($bidInfo['rest_principal'],2,false);
//    	$bidInfo['status_view'] = $bidInfo['status'] == PrjRepayPlanModel::PRJ_REPAYMENT_STATUS_SUCCESS ? "<i class='ico_done'></i>":"待还";
    	
    	$this->bidInfo = $bidInfo;
    	$this->display("ajaxshowprjrepayplan");
    }

   
    /**
     * 富二代
     */
    function ajaxfed(){
        import_app("Financing.Model.PrjModel");
        
        $prjName = $this->_request("prjname");
        $paramer["prjname"] = $prjName;
        $timeType = (int) $this->_request("timeType");
        $paramer["timeType"] = $timeType;
        $startDate = $this->_request("startDate");
        $paramer["startDate"] = $startDate;
        $endDate = $this->_request("endDate");
        $paramer["endDate"] = $endDate;
        
        $st = (int) $this->_request("status");
        $paramer["status"] = $st;
        $st = $st ? $st :4;
        $this->st = $st;
        
        $page = (int) $this->_request("p");
        $paramer["p"] = $page;
        $page = $page ? $page :1;
        $pageSize = 10;

        $orderBy = $this->_request("order");
        $paramer["order"] = $orderBy;
        $orderArr = array();
        if($orderBy){
            list($field,$order) = explode("|", $orderBy);
            $this->assign("order_field",$field);
            $this->assign("order_type",$order);

            if($field == 'order21'){
                $orderArr["prj_order.expect_repay_time"] = $order;
            }
            elseif($field == 'order24'){
                $orderArr["prj_order.rest_money"] = $order;
            }
            elseif($field == 'order25'){
                $orderArr["prj_order.possible_yield"] = $order;
            }
            elseif($field == 'order26'){
                $orderArr["prj_order.ctime"] = $order;
            }elseif($field == 'order28'){
                $orderArr["asset_transfer.money"] = $order;
            }
        }else{
            if($st == 4) { // 待还款按还款日排序
                $orderArr["prj_order.expect_repay_time"] = "desc";
            } else {
                $orderArr["prj_order.ctime"] = "desc";
            }
         }


        if($prjName) $where['prj.prj_name'] = array("like","%".$prjName."%");
        $endTime = $endDate ? strtotime("+1 days",strtotime($endDate)):0;
        
        
        $timeType = (int) $timeType;
        if($timeType == 1){//成交日期
        	
            if($startDate) $where['prj_order.ctime'][] = array("EGT",strtotime($startDate));
            if($endTime) $where['prj_order.ctime'][] = array("LT",$endTime);
        }elseif($timeType == 2){//回款日期
            if($startDate) $where['prj_order.expect_repay_time'][] = array("EGT",strtotime($startDate));
            if($endTime) $where['prj_order.expect_repay_time'][] = array("LT",$endTime);
        }

        $service = service("Account/Record");
        
        $where['prj_order.uid'] = $this->loginedUserInfo['uid'];
        if($st == 4){//待还款
            $where['prj.bid_status'] = PrjModel::BSTATUS_REPAYING;
            $where['prj_order.rest_money'] = array("GT",0);
            $result = $service->fedList($where,$orderArr,$page,$pageSize);
            $this->totalRepaying = $result['total_row'];
             if($timeType == 2){
                unset($where['prj_order.expect_repay_time']);
                if($startDate) $where['person_repayment.repay_time'][] = array("EGT",strtotime($startDate));
                if($endTime) $where['person_repayment.repay_time'][] = array("LT",$endTime);
            }
            
            
            $repaymentWhere = array();
            $repaymentWhere['prj.bid_status'] = PrjModel::BSTATUS_REPAID;
            $repaymentWhere['prj_order.uid'] = $this->loginedUserInfo['uid'];;
            $this->totalRepaySuccess = $service->fedRepaymentfinancingCount($repaymentWhere);
            
            $FedCountWhere = array();
            $FedCountWhere['prj_order.uid'] = $this->loginedUserInfo['uid'];
            $FedCountWhere['prj.bid_status'] = PrjModel::BSTATUS_REPAY_IN;
            $FedCountWhere['prj_order.rest_money'] = array("GT",0);
            $this->totalRepayIN = $service->getFedCount($FedCountWhere);

        }elseif($st == 6){
        	
        	$where['prj.bid_status'] = PrjModel::BSTATUS_REPAY_IN;
        	$where['prj_order.rest_money'] = array("GT",0);
        	$result = $service->fedList($where,$orderArr,$page,$pageSize);
        	$this->totalRepayIN = $result['total_row'];
        	if($timeType == 2){
        		unset($where['prj_order.expect_repay_time']);
        		if($startDate) $where['person_repayment.repay_time'][] = array("EGT",strtotime($startDate));
        		if($endTime) $where['person_repayment.repay_time'][] = array("LT",$endTime);
        	}
        	
        	$repaymentWhere = array();
        	$repaymentWhere['prj.bid_status'] = PrjModel::BSTATUS_REPAID;
        	$repaymentWhere['prj_order.uid'] = $this->loginedUserInfo['uid'];;
        	$this->totalRepaySuccess = $service->fedRepaymentfinancingCount($repaymentWhere);
        	
        	$FedCountWhere = array();
        	$FedCountWhere['prj_order.uid'] = $this->loginedUserInfo['uid'];
        	$FedCountWhere['prj.bid_status'] = PrjModel::BSTATUS_REPAYING;
        	$FedCountWhere['prj_order.rest_money'] = array("GT",0);
        	$this->totalRepaying = $service->getFedCount($FedCountWhere);
        	
        }elseif($st == 5){//已还款结束
            if($timeType == 2){
                unset($where['prj_order.expect_repay_time']);
                if($startDate) $where['person_repayment.repay_time'][] = array("EGT",strtotime($startDate));
                if($endTime) $where['person_repayment.repay_time'][] = array("LT",$endTime);
            }
            $where['prj.bid_status'] = PrjModel::BSTATUS_REPAID;
            $result = $service->fedRepaymentfinancingList($where,$orderArr,$page,$pageSize);
            $this->totalRepaySuccess = $result['total_row'];
            if($timeType == 2){
                unset($where['person_repayment.person_repayment']);
                if($startDate) $where['prj_order.expect_repay_time'][] = array("EGT",strtotime($startDate));
                if($endTime) $where['prj_order.expect_repay_time'][] = array("LT",$endTime);
            }
            $FedCountWhere = array();
            $FedCountWhere['prj_order.uid'] = $this->loginedUserInfo['uid'];
            $FedCountWhere['prj.bid_status'] = PrjModel::BSTATUS_REPAYING;
            $FedCountWhere['prj_order.rest_money'] = array("GT",0);
            $this->totalRepaying = $service->getFedCount($FedCountWhere);
            
            $FedCountWhere = array();
            $FedCountWhere['prj_order.uid'] = $this->loginedUserInfo['uid'];
            $FedCountWhere['prj.bid_status'] = PrjModel::BSTATUS_REPAY_IN;
            $FedCountWhere['prj_order.rest_money'] = array("GT",0);
            $this->totalRepayIN = $service->getFedCount($FedCountWhere);
        }
        $list = $result['data'];

        $totalRow = $result['total_row'];
        $this->assign("list",$list);
        //分页
        $paging  = W("Paging",array("totalRows"=>$totalRow,"pageSize"=>$pageSize,"parameter"=>$paramer),true);
        $this->assign("paging",$paging);
        $tplPath = "ajaxfed";
        $this->assign('REPAY_WAY_E', PrjModel::REPAY_WAY_E);
        $this->display($tplPath);
    }
    
    
   function ajaxt(){
	   	$status = (int)I('request.status_transfer', 1);
	   	$page = (int)I('request.p', 1);
	   	$order = I('request.order');
	   	if(!in_array($status, range(0, 3))) $status = 0;
	   	// 条件筛选
	   	$where = array(
	   			'pt.uid' => $this->uid,
	   	);
	   	if(array_key_exists($status, $this->transfer_mapping['STATUS'])) {
	   		$where = array_merge($where, $this->transfer_mapping['STATUS'][$status]);
	   	}
	   		   	
	   	// 字段排序
	   	list($order_field, $order_type) = explode('|', $order);
	   	if(!($order_type && in_array(strtolower($order_type), array('asc', 'desc')))) {
	   		$order_type = 'desc';
	   	}
	   	if (array_key_exists($order_field, $this->transfer_mapping['ORDERBY'])) {
	   		$orderby = $this->transfer_mapping['ORDERBY'][$order_field] . ' ' . $order_type;
	   	} else {
	   		$orderby = 'pt.id DESC';
	   	}
	   	
	   	$join = array(
	   			'LEFT JOIN fi_prj pj ON pj.id = pt.prj_id
	   			 LEFT JOIN fi_prj_order po ON pt.from_order_id = po.id 
	   			',
	   	);
	   	$fields = array(
	   			'pt.*',
	   			'pt.from_order_id as p_from_order_id',
	   			'pj.prj_name',
	   			'pj.id as pjid',
	   			'pj.prj_type',
	   			'pj.year_rate',
	   			'pj.prj_no',
	   			'pj.safeguards',
	   			'pj.rate',
	   			'pj.rate_type',
	   			'pj.time_limit',
	   			'pj.time_limit_unit',
	   			'pj.repay_way', // 还款方式
	   			'pj.bid_status', // 项目状态
	   			'po.from_order_id as o_from_order_id',
                'po.repay_principal'
	   	);
// 	   	print_r($where);
	   	$oModel = M();
	   	$list = $oModel->table('fi_asset_transfer pt')
	   	->where($where)
	   	->join($join)
	   	->field($fields)
	   	->order($orderby)
	   	->page($page)
	   	->limit(self::PAGESIZE)
	   	->select();
	   	
	   	$total = $oModel->table('fi_asset_transfer pt')
	   	->where($where)
	   	->join($join)
	   	->count();
	   	
	   	// 特殊字段处理
	   	$srvProject = service('Financing/Project');
//	   	$srvFinancing = service('Financing/Financing');
	   	foreach ($list as $key => $row) {
//            echo $row['pjid']."<br>";
            //原来项目信息
            $oldPrjInfo = $srvProject->getByid($row['prj_id']);
	   		$list[$key]['project'] = $oldPrjInfo;
	   		
	   		$list[$key]['rate_symbol'] = $row['rate_type'] == 'day' ? '‰' : '%';
	   		
	   		// 利率
	   		$list[$key]['rate_type_display'] = $srvProject->getRateType($row['rate_type']);
	   		$list[$key]['rate_symbol'] = $row['rate_type'] == 'day' ? '‰' : '%';
	   		if ($row['rate_type'] != PrjModel::RATE_TYPE_DAY) {
	   			$list[$key]['rate'] = number_format($list[$key]['rate'] / 10, 2);
	   		} else {
	   			$list[$key]['rate'] = number_format($list[$key]['rate'], 2);
	   		}
	   	
	   		$synMoney = service('Financing/Financing')->getTransMoney($row['id'],1);
	   	
	   		$list[$key]['money_view'] = $synMoney ? humanMoney($synMoney,2,false):"-";
	   	
	   		// 状态
	   		$list[$key]['bid_status_display'] = $this->prj_bid_status_display[$row['bid_status']];
	   	
	   		//持有期数信息
	   		$isEnd = service("Financing/Invest")->isEndDate($oldPrjInfo['repay_way']);
	   		$list[$key]['is_end'] = $isEnd;
	   		if($isEnd){
	   			$list[$key]['no_repay_count'] = 1;
	   			$list[$key]['repay_count'] = 1;
	   			$list[$key]['total_count'] = 1;
	   		}else{
                $repayedWhere = array();
                $repayedWhere['prj_order_id'] = $row['p_from_order_id'];
                $repayedWhere['repay_periods'] = array('NEQ', 0);
                $repayedWhere['status'] = PrjOrderRepayPlanModel::ORDER_REPAYMENT_STATUS_SUCCESS;
                $repayedResult = M("prj_order_repay_plan")->field("count(*) as cnt")->where($repayedWhere)->find();
                $repayedCount = (int)$repayedResult['cnt'];

                $totalRepayWhere = array();
                $totalRepayWhere['prj_order_id'] = $row['p_from_order_id'];
                $totalRepayWhere['repay_periods'] = array('NEQ', 0);
                $totalRepayResult = M("prj_order_repay_plan")->field("count(*) as cnt")->where($totalRepayWhere)->find();
                $totalRepayCount = $totalRepayResult['cnt'];

	   			$list[$key]['no_repay_count'] = $totalRepayCount-$repayedCount;
	   			$list[$key]['repay_count'] = $repayedCount;
	   			$list[$key]['total_count'] = $totalRepayCount;
	   		}
	   		
	   		//是否大于91天
	   		$list[$key]['can_transfer'] = service("Financing/Transfer")->fedCanTransfer($row['id']);
	   		//到期本息
	   		$allIncomeMoney = $row['property']+$row['prj_revenue'];
	   		$list[$key]['all_income_money_view'] = humanMoney($allIncomeMoney,2,false)."元";
	   		
	   		$list[$key]['expire_time_limit'] = service("Financing/Project")->parseExpireTime($row['id']);

	   		$list[$key]['pri_interest_view'] = service("Account/Record")->showTransferActivityMoney($row['id']);
	   	}
	   	$paging  =  W('Paging', array(
	   			'totalRows'=> $total,
	   			'pageSize'=> self::PAGESIZE,
	   			'parameter' => array(
	   					'status_transfer' => $status,
	   					'order' => $order,
	   			),
	   	), TRUE);
	   	
	   	
	   	//数目
	   	$sql = "SELECT COUNT(id) as cnt,status  FROM fi_asset_transfer WHERE  uid= '".$this->uid."' GROUP BY status";
	    $countRs = M()->query($sql);
	    $countInfo = array();
	    if($countRs){
	    	foreach ($countRs as $v){
	    		$countInfo[$v['status']] = $v['cnt'];
	    	}
	    }
       $countInfo[1] = isset($countInfo[1]) ? $countInfo[1] :0;
       $countInfo[2] = isset($countInfo[2]) ? $countInfo[2] :0;
       $countInfo[3] = isset($countInfo[3]) ? $countInfo[3] :0;

	    $this->countInfo = $countInfo;
	    
	    $this->total = service("Financing/Financing")->getListTransTotalCount($this->uid);

	   	$this->assign('status_transfer', $status);
	   	$this->assign('list', $list);
	   	$this->assign('paging', $paging);
	   	
	   	$this->assign('order_field', $order_field);
	   	$this->assign('order_type', $order_type);
	   	$status = !$status ? 1: $status;
	   	$this->display("ajaxt");
   }


    
    public function tlist($p=1) {
    	$this->check_tenant();
    	$tpl = "tlist";
    	
    	$page = (int)$p;
    
    	$srvFinancing = service('Financing/Financing');
    	$result = $srvFinancing->listTranfer($this->uid, $page, 10);
    
    	$list = $result['data'];
    	$paging  =  W('Paging', array(
    			'totalRows'=> $result['total'],
    			'pageSize'=> 10,
    			'parameter' => '',
    	), TRUE);
    	$this->assign('total', $result['total']);
    	$this->assign('list', $list);
    	$this->assign('paging', $paging);
    	
	   if(!is_null($this->_post('ajax'))){
	    	$tpl = 'tlist_ajax';
        }
        	//数目
        $sql = "SELECT COUNT(id) as cnt,status  FROM fi_asset_transfer WHERE  uid= '".$this->uid."' GROUP BY status";
        $countRs = M()->query($sql);
        $countInfo = array();
        if($countRs){
            foreach ($countRs as $v){
                $countInfo[$v['status']] = (int) $v['cnt'];
            }
        }

        $countInfo[1] = isset($countInfo[1]) ? $countInfo[1] :0;
        $countInfo[2] = isset($countInfo[2]) ? $countInfo[2] :0;
        $countInfo[3] = isset($countInfo[3]) ? $countInfo[3] :0;
        $this->countInfo = $countInfo;

    	$this->display($tpl);
    }
    
    /**
     * 产品转让表单
     *
     * @param int $ord_id   欲转让的购买订单编号
     */
    public function transfer($ord_id=0) {
    	$srvFinancing = service('Financing/Financing');
    	$mdAssetTransfer = D('Financing/AssetTransfer');
    
    	$expireTime = $mdAssetTransfer->getTransEndTime($ord_id,true);
    	$this->viewexpireTime = date('Y-m-d',$expireTime);
    	$money = $srvFinancing->getTransMoney($ord_id);
    	$this->moneyView = humanMoney($money,2,false)."元";
    
    	$this->detail = $srvFinancing->detailTransfer($ord_id);
    
    	$this->display("transfer");
    }
    
    
    /**
     * 执行产品转让
     * POST参数：
     *      property: 转让持有份额
     *      money: 转让价格
     *      transfer_no: 转让编号
     *
     * @param int $ord_id   欲转让的购买订单编号
     */
    public function do_transfer($ord_id=0) {
    	$property = 0;
    
    	$this->ajax_check_transfer(FALSE);
    	$this->check_mcode_transfer(FALSE);
    
    	$srvFinancing = service('Financing/Financing');
    
    	$money = $srvFinancing->getTransMoney($ord_id);

    	$result = $srvFinancing->doTransfer($this->uid, $ord_id, $property, $money);
    	ajaxReturn(0, $result[1], $result[0]);
    }
    
    /**
     * 产品转让表单的Ajax验证
     *
     * @param bool $ajax_return 如果为FALSE标识供其他Action调用
     * @return bool
     */
    public function ajax_check_transfer($ajax_return=TRUE) {
    	$agree = I('request.agree', NULL);

    	if((!is_null($agree) || !$ajax_return) && !(int)$agree) ajaxReturn(0, "请同意协议", 0);

    	if(!$ajax_return) return TRUE;
    	ajaxReturn(0, '', 1);
    }
    
    /**
     * 验证产品转让验证码
     *
     * @param bool $ajax_return 如果为FALSE标识供其他Action调用
     * @return bool
     */
    public function check_mcode_transfer($ajax_return=TRUE) {
    	$code = I('request.code'); // 验证码
    	$result = service('Account/Validate')->validateTransferMobileCode($this->user['mobile'], $code);
    
    	if($result[0] && !$ajax_return) return TRUE;
    	ajaxReturn(0, $result[1], $result[0]);
    }
    
    /**
     * 检测用户是否有商户Id
     *
     * @return mixed
     */
    private function check_tenant() {
    	try {
    		$uid = $this->loginedUserInfo['uid'];
    		$user = M('User')->find($uid);
    		$dept_id = (int) $user['dept_id'];
    		
    		$tenant_id = service("Account/Account")->getTenantIdByUser($user);
    		$this->assign('has_tenant_id', $tenant_id);
    		return $tenant_id;
    	} catch (Exception $e) {
    		return FALSE;
    	}
    }
    
    /**
     * 发送产品转让验证码
     */
    public function send_mcode_transfer() {
    	$result = service('Account/Validate')->sendTransferMobileCode($this->uid, $this->user['mobile']);
    	ajaxReturn(0, $result[1], $result[0]);
    }

    /*
    * 理财记录查看详情
     */
    public function financinginfo(){
        $order_id = I('request.id');
        if(!$order_id){
            $this->redirect(U('Account/Financing/index'));
        }
        $order_prj_info = service('Account/Record')->getOrderPrjInfoByOrderId($order_id, $this->loginedUserInfo['uid']);
        if(!$order_prj_info){
            $this->redirect(U('Account/Financing/index'));
        }
//        print_r($order_prj_info);
        //理财投资页面三农贷、电商贷、创客融、央企融、经营贷前端显示统一为经营贷
        if(in_array($order_prj_info['prj_info']['prj_business_type'],array('C', 'D', 'E', 'C0V14', 'C00303'))) {
            $order_prj_info['prj_info']['prj_business_type_name'] = '经营贷';
        }
        $this->data = $order_prj_info;

        $this->display();
    }

}

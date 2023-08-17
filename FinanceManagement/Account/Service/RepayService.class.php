<?php
/**
 * 还款相关
 * User: 000790
 * Date: 14-2-24
 * Time: 下午4:56
 */
import_app("Financing.Model.PrjModel");
import_app("Financing.Model.PrjOrderModel");
class RepayService extends BaseService{

    /**
     * 获取下周数据
     * @param $uid
     * @return array
     */
    function getNextWeekData($date){
        $uinfo = service("Account/Account")->getLoginedUserInfo();
        $uid = $uinfo['uid'];
        $now =  $date ? strtotime($date) : strtotime(date('Y-m-d'));
        $daynum = 6;
        $dateArr[] = $now;
        for($i=1;$i <= $daynum;$i++){
            $dateArr[] = strtotime("+".$i." days",$now);
        }

        $weekarray = array("日","一","二","三","四","五","六");
        $dataTmp = array();
        $moneyTmp = array();
        $countTmp = array();
        foreach($dateArr as $k=>$v){
            $dataTmp[] = date('m',$v)."/".date('d',$v)." ".$weekarray[date("w",$v)];
            $info = M("user_repay_plan")->where(array("uid"=>$uid,'type'=>1,"repay_date"=>date('Y-m-d',$v)))->order('id desc')->find();
            $moneyTmp[] = $info['total_amount']/100;
            $countTmp[] = (int) $info['prj_number'];
        }
        return array(json_encode($dataTmp),json_encode($moneyTmp),json_encode($countTmp));
    }

    /**
     * 获取月份数据
     * @param int $year
     * @param int $month
     */
    function getMonthData($year=0,$month=0){
        $uinfo = service("Account/Account")->getLoginedUserInfo();
        $uid = $uinfo['uid'];
        $year = $year ? $year:date('Y');
        $month = $month ? $month :date('m');
        $month = $month>9 ? $month:"0".$month;

        $dataStr = $year."-".$month;
        $list = M("user_repay_plan")->where(array("uid"=>$uid,"repay_date"=>array("like",$dataStr."%")))->select();

        $result = array();
        if($list){
            foreach ($list as $v) {
                $day = date('d', strtotime($v['repay_date']));
                $result[$day]['day'] = $day;
                if ($v['type'] == 1) {
                    $result[$day]['numbers']['prj'] = $v['prj_number'];

                    $amount = $this->projectAward($v['uid'], $v['repay_date']);
                    $result[$day]['money'] += ($v['total_amount'] + $amount);
                } else {
                    $result[$day]['numbers']['xjh'] = $v['prj_number'];
                    $result[$day]['money'] += $v['total_amount'];
                }
            }
            array_walk($result, create_function('&$v', '$v["money"] = number_format($v["money"] / 100, 2);'));
            $result = array_values($result);
        }

        return $result;
    }
    /**
     * 获取已还款数量
     * @param int $year
     * @param int $month
     */
    function getMonthRepayedNum($year=0,$month=0){
        $uinfo = service("Account/Account")->getLoginedUserInfo();
        $uid = $uinfo['uid'];
        $year = $year ? $year:date('Y');
        $month = $month ? $month :date('m');
        if($month !=12){
            $month2 = $month+1;
            $year2 = $year;
        }else{
            $month2 = 1;
            $year2 = $year+1;
        }
        $month = $month>9 ? $month:"0".$month;
        $month2 = $month2 > 9 ? $month2 : "0".$month2;
        $dataStr = $year."-".$month;
        $dataStr2 = $year2."-".$month2;
        $unixtime = strtotime($dataStr);
        $unixtime2 = strtotime($dataStr2);
        $list = M("person_repayment")->Distinct(true)->field("order_id,FROM_UNIXTIME(repay_time,'%Y-%m-%d') as repay_time")->where(array("uid"=>$uid,"status"=>2, "repay_time"=>array("between",array($unixtime,$unixtime2))))->select();
        $result = array();

        $prj_order_model = M('prj_order');
        foreach($list as &$v){
            //是不是司马小鑫
            $is_x_prj = $prj_order_model->where(['id' => $v['order_id']])->getField('xprj_order_id');
            if ($is_x_prj) {
                continue;
            }
            $unix = strtotime($v['repay_time']);
            $result[] = date('d',$unix);
        }
        $result = array_count_values($result);
        return $result;
    }
    /**
     * 获取已还款总计金额
     * @param int $year
     * @param int $month
     */
    function getMonthRepayedMoney($year=0,$month=0,$arr=array()){
        $uinfo = service("Account/Account")->getLoginedUserInfo();
        $uid = $uinfo['uid'];
        $year = $year ? $year : date('Y');
        $month = $month ? $month : date('m');
        $dataStr = $year."-".$month;
        $result = array();
        foreach($arr as $key=>$val){
            $date = $dataStr."-".$key;
            $unixtime1 = strtotime($date." "."00:00:00");
            $unixtime2 = strtotime($date." "."23:59:59");

            $person_repayment = M('person_repayment')->join('fi_prj_order prj_order on fi_person_repayment.order_id = prj_order.id')
                ->field('sum(fi_person_repayment.capital) capital, sum(fi_person_repayment.profit) profit')
                ->where([
                    "fi_person_repayment.uid"=>$uid,
                    "fi_person_repayment.status"=>2,
                    "fi_person_repayment.repay_time"=>array("between",array($unixtime1,$unixtime2)),
                    'prj_order.xprj_order_id' => 0,
                ])->find();
            $capital = $person_repayment['capital'];
            $profit = $person_repayment['profit'];

            $reward = M("prj_order_reward")->join('fi_prj_order prj_order on fi_prj_order_reward.order_id = prj_order.id')
                ->where([
                    "fi_prj_order_reward.uid"=>$uid,
                    "fi_prj_order_reward.status"=>2,
                    "fi_prj_order_reward.repay_date"=>array("between",array($unixtime1,$unixtime2)),
                    'prj_order.xprj_order_id' => 0,
                ])->sum('fi_prj_order_reward.amount');

            $total = humanMoney(($capital + $profit + $reward),2,false);
            $tmp = array();
            $tmp['day'] = $key;
            $tmp['num'] = $val;
            $tmp['capital'] = $capital;
            $tmp['money'] = $total;
            $tmp['money_no_format'] = $capital + $profit + $reward;
            $result[$key] = $tmp;
        }

        return $result;
    }

    /**
     * 指定月份司马小鑫还款的数据
     * @param int $uid
     * @param int $year
     * @param int $month
     * @return array
     */
    public function getXjhMonthPayedMoney($uid, $year=0, $month=0)
    {
        $x_prj_order = D('Financing/XPrjOrder');
        $year = $year ? $year : date('Y');
        $month = $month ? $month : date('m');
        $this_month_last_day = date('t', mktime(0, 0, 0, $month, 1, $year));

        $start_time = mktime(0, 0, 0, $month, 1, $year);
        $end_time = mktime(0, 0, 0, $month + 1, 1, $year);

        $condition = [
            'uid' => $uid,
            'status' => XPrjOrderModel::STATUS_FINISH,
            'repay_date' => array('between', [$start_time, $end_time]),
        ];

        $orders = $x_prj_order->where($condition)->select();
        $result = [];
        if ($orders) {
            foreach ($orders as $order) {
                $day = date('d', $order['repay_date']);
                if (isset($result[$day])) {
                    $result[$day]['num'] += 1;
                    $result[$day]['money'] += $order['money'];
                    $result[$day]['yield'] += ($order['possible_yield'] + $order['addon_yield']);
                } else {
                    $result[$day] = [
                        'day' => $day,
                        'num' => 1,
                        'money' => $order['money'],
                        'yield' => $order['possible_yield'] + $order['addon_yield'],
                    ];
                }
            }
        }

        return $result;
    }

    function getNextRepay($date="",$isc=0,$uid=NULL/*手机端传uid过来*/){
        if(is_null($uid)) {
            $uinfo = service("Account/Account")->getLoginedUserInfo();
            $uid = $uinfo['uid'];
        }
        $date = $date ? $date :date('Y-m-d');
        $obj =  M("user_repay_plan");
        $c = $isc ? "EGT":"GT";
        $info = $obj->where(array("uid"=>$uid,'type'=>1,"repay_date"=>array($c,$date)))->order("repay_date ASC,id desc")->find();
        if(!$info) return array();
        $info['total_amount'] = humanMoney($info['total_amount'],2,false);
        return $info;
    }

    function getPreRepay($date="",$isc=0,$uid=NULL/*手机端传uid过来*/){
        if(is_null($uid)) {
            $uinfo = service("Account/Account")->getLoginedUserInfo();
            $uid = $uinfo['uid'];
        }
    	$date = $date ? $date :date('Y-m-d');
    	$obj =  M("user_repay_plan");
              $c = $isc ? "ELT":"LT";
    	$info = $obj->where(array("uid"=>$uid,'type'=>1,"repay_date"=>array($c,$date)))->order("repay_date DESC,id desc")->find();
    	if(!$info) return array();
    	$info['total_amount'] = humanMoney($info['total_amount'],2,false);
    	return $info;
    }

    function getNewNextRepay($uid,$date='',$isc=0){
             $date = $date ? $date :date('Y-m-d');
    	$data = $this->getNextRepay($date,$isc,$uid);
    	if(!$data){
                            $data = array();
                            $data = M("user_repay_plan")->where(array("uid"=>$uid,'type'=>1,"repay_date"=>date('Y-m-d')))->order("id desc")->find();
    	               if($data){
                                    $data['total_amount'] = humanMoney($data['total_amount'],2,false);
                                    return $data?$data:array();
    	               }
    	}
    	return $data?$data:array();
    }

    //当天是否有回款
    function todayHasRepay($uid,$date=''){
        $date = $date ? $date : date('Y-m-d');
        $date = is_numeric($date) ? date('Y-m-d',$date):$date;
        //旧的还款方式
        $result = M("user_repay_plan")->where(array("uid"=>$uid,"repay_date"=>$date,'type'=>1))->find();
        // echo M("user_repay_plan")->getLastSql();
        if(!$result) return 1;
        if($result['total_amount'] ==0) return 1;
        return 0;
    }

    //当天之后是否有回款
    function isNullRepay($uid,$date=''){
        $date = $date ? $date : date('Y-m-d');
        $date = is_numeric($date) ? date('Y-m-d',$date):$date;
        $info = M("user_repay_plan")->where(array("uid"=>$uid,"repay_date"=>array("GT",$date),'type'=>1))->order("repay_date ASC")->find();
        return $info ? 0:1;
    }

        //当天之前是否有回款
    function isNullRepayPre($uid,$date=''){
        $date = $date ? $date : date('Y-m-d');
        $date = is_numeric($date) ? date('Y-m-d',$date):$date;
        $info = M("user_repay_plan")->where(array("uid"=>$uid,"repay_date"=>array("LT",$date),'type'=>1))->order("repay_date DESC")->find();
        return $info ? 0:1;
    }

    //账户概况下面回款提醒
    function repaymentRemind(){
        $uinfo = service("Account/Account")->getLoginedUserInfo();
        $uid = $uinfo['uid'];
        $service = service("Account/Record");
        $where['prj_order.status'] = array("NEQ", PrjOrderModel::STATUS_NOT_PAY);

        //不包含待开标
        $where['prj.bid_status'] = array("NEQ", PrjModel::BSTATUS_WATING);

//        $where['prj.prj_class'] = PrjModel::PRJ_CLASS_TZ;
        $where['prj_order.uid'] = $uid;
        $where['prj_order.xprj_order_id'] = ['eq', 0];

        $where['_string'] = '(prj_order.rest_money > 0 && prj.bid_status in ('.PrjModel::BSTATUS_REPAYING.'., .'.PrjModel::BSTATUS_REPAY_IN.')) or 
        (prj_order.rest_money = 0 and prj.bid_status = ' . PrjModel::BSTATUS_ZS_REPAYING.')';

        $orderArr = 'prj_order.expect_repay_time ASC';
        $result = $service->financingList($where, $orderArr, 1, 2, '', true, 1);
        $result = $result['data'];

        $srvPrjRepayExtend = service('Financing/PrjRepayExtend');
        $prj_list = array();
        $prj_order_repay_plan_model = new PrjOrderRepayPlanModel();
        foreach ($result as $row) {
            $element = array();
            $shouyi = $prj_order_repay_plan_model->where(array(
                'prj_order_id' => $row['id'],
                '_string' => 'status = ' . PrjOrderRepayPlanModel::ORDER_REPAYMENT_STATUS_WAIT
                    . ' or (status = ' . PrjOrderRepayPlanModel::ORDER_REPAYMENT_STATUS_SUCCESS . ' and zs_repay_status in('
                    . PrjOrderRepayPlanModel::ZS_REPAY_STATUS_BEFORE_CALLBACK
                    . ', ' . PrjOrderRepayPlanModel::ZS_REPAY_STATUS_ING
                    . ', ' . PrjOrderRepayPlanModel::ZS_REPAY_STATUS_FAIL . '))',
            ))->field('repay_date,SUM(principal) AS principal, SUM(yield) AS yield')->order('repay_date ASC')->group('repay_date')->find();
            $element['money_view'] = humanMoney($shouyi['principal'], 2, false) . '元';
            $amount=$this->projectAward($uid,$shouyi['repay_date'],$row['id']);//2016321 项目已生成还款计划，但未支付，回款日历展示的本息收益缺少项目奖励部分的收益
            $element['rest_income_view'] = humanMoney($shouyi['yield']+$amount, 2, false) . '元';
            $element['expect_repay_time_view'] = date('Y-m-d', $shouyi['repay_date']);
            $prj_id = $row['prjid'];
            if($row['value_date_shadow'] == 0) {
                $notice_tail = '，预计13:00前到账。';
            } else {
                $notice_tail = '，预计16:00-24:00到账。';
            }

            if($srvPrjRepayExtend->isExtend($prj_id)) {
                $old_last_repay_date = M('prj_ext')->where(array('prj_id' => $prj_id))->getField('old_last_repay_date');
                if($row['last_repay_date'] > $old_last_repay_date) { // 发生展期
                    $notice_tail .= '本项目展期还款，最迟';
                } else {
                    $notice_tail .= '本项目可能会展期还款，最迟';
                }
                $notice_tail .= date('Y-m-d', $srvPrjRepayExtend->getMaxExtendTime($prj_id));
                $notice_tail .= '到账。';
            }

            $element['notice_tail'] = $notice_tail;
            $element['prj_name'] = $row['prj_name'];
            $element['order_ext_totle'] = $row['order_ext_totle'];
            $element['type'] = '1';
            $prj_list[] = $element;
        }

        //司马小鑫
        $x_prj_order_wait_list = $this->getXPrjOrderWaitList($uid);
        $return = array_merge($prj_list, $x_prj_order_wait_list);

        $sort_column = [];
        foreach ($return as $value) {
            $sort_column[] = $value['expect_repay_time_view'];
        }
        array_multisort($sort_column, SORT_ASC, $return);

        return array_slice($return, 0, 2);
    }

    /**
     * 司马小鑫订单找两个最近的要还款的
     * @param $uid
     */
    public function getXPrjOrderWaitList($uid)
    {
        $x_prj_order = D('Financing/XPrjOrder');
        $condition = [
            'uid' => $uid,
            'status' => ['in', [XPrjOrderModel::STATUS_INIT, XPrjOrderModel::STATUS_WAIT_PAY]],
        ];
        $x_orders = $x_prj_order->where($condition)->limit(2)->order('repay_date')->select();
        $x_orders_format = [];
        foreach ($x_orders as $x_order) {
            $element = array();
            $element['money_view'] = number_format($x_order['money'] / 100 , 2) . '元';
            $element['rest_income_view'] = number_format(($x_order['possible_yield'] + $x_order['addon_yield']) / 100 , 2) . '元';
            $element['expect_repay_time_view'] = date('Y-m-d', $x_order['repay_date']);
            $element['notice_tail'] = '，预计16:00-24:00到账。';
            $element['type'] = '2';
            $element['time_limit_day'] = $x_order['time_limit'] . ($x_order['time_limit_unit'] == 'day' ? '天' : ($x_order['time_limit_unit'] == 'month' ? '月' : '年'));
            $x_orders_format[] = $element;
        }

        return $x_orders_format;
    }

    //2016321 项目已生成还款计划，但未支付，回款日历展示的本息收益缺少项目奖励部分的收益
    function  projectAward($uid,$repay_date, $order_id=0){
        if(!is_numeric($repay_date)) {
            $repay_date = strtotime($repay_date);
        }
        $where = [
            'fi_prj_order_reward.uid' => $uid,
            'fi_prj_order_reward.status' => 1,
            'fi_prj_order_reward.reward_type' => array('in',[1, 3]),
            'fi_prj_order_reward.repay_date' => $repay_date,
            'prj_order.xprj_order_id' => 0,
        ];
        if($order_id) {
            $where['fi_prj_order_reward.order_id'] = $order_id;
        }

        $result = M('prj_order_reward')->join('fi_prj_order prj_order on fi_prj_order_reward.order_id = prj_order.id')
            ->where($where)
            ->sum('fi_prj_order_reward.amount');

        if($result) {
            return $result;
        }
    }
}

<?php

/**
 * 速兑通相关服务
 * 支持三种还款方式变现的变现金额和利率计算
 * 1，到期一次性还本付息
 * 2，按月付息，到期还本
 * 3，按月等额本息
 * Class FastCashService
 */
class FastCashService extends BaseService
{
    //变现申请三种状态
    const STATUS_PENDING = 1;//待审核
    const STATUS_START = 2;//变现中
    const STATUS_END = 3;//截标
    const STATUS_FULL = 31;//满标
    const STATUS_FAIL = 4;//审核失败
    const STATUS_CANCEL = 5;//取消状态 没人买用户主动取消

    //变现列表三个状态
    const STATUS_CAN_CASH = 1;// 可变现
    const STATUS_CASHING = 2;// 变现中
    const STATUS_CASHED = 3;// 已变现
    
    

    const PRJ_TYPE_NAME = 'FastCash';
    public $_config;
    private $_prj_info = array();
    private $_prj_order_info = array();
    private $_prj_cash_info = array();
    private $_trade = false;
    private $_cash_status_list = array(
        self::STATUS_PENDING=>'可变现',
        self::STATUS_START=>'变现中',
        self::STATUS_END=>'已变现',
        self::STATUS_FULL=>'已变现',
        self::STATUS_CANCEL=>'已变现',
    );
    
    //速兑通排序相关
    const ORDER_PUBLISH = 1;//按发布时间排序
    const ORDER_INCOME = 2;//按利率高低(年化率)
    const ORDER_TIME_LIMIT = 3;//按期限长短
    const ORDER_DEMAND_AMOUNT = 4;//按额度高低
    const ORDER_SCHEDULE = 5;//按投资进度排序(倒序)
    const ORDER_MIN_BID_AMOUNT = 6; //按起投金额
    
    //排序
    const SORT_DESC = "DESC";
    const SORT_ASC = "ASC";
    
    /**
     * 获取排序的列表
     */
    public function getSdtSortList()
    {
        $sort = array(
            1=>array("id"=>self::ORDER_PUBLISH,'title_short'=>'按最新发布时间'),
            2=>array("id"=>self::ORDER_INCOME,'title_short'=>'按收益率高低'),
            3=>array("id"=>self::ORDER_TIME_LIMIT,'title_short'=>'按期限长短'),
            4=>array("id"=>self::ORDER_DEMAND_AMOUNT,'title_short'=>'按额度高低'),
            5=>array("id"=>self::ORDER_SCHEDULE,'title_short'=>'按投资进度'),
            6=>array("id"=>self::ORDER_MIN_BID_AMOUNT,'title_short'=>'按起投金额'),
        );
        return $sort;
    }

    /**
     * 速兑通现在支持的还款方式
     * @var array
     */
    private $_open_repay_way = array(
        'E'
    );


    function __construct()
    {
        parent::__construct();
        /**
         * 速兑通相关可配置参数都放到配置文件fast_cash.php中
         */
        $this->_config = C('fast_cash');
        if(!$this->_config){
            $this->_config = require APP_PATH.'Conf/fast_cash.php';
            if(!$this->_config){
                throw_exception('配置FAST_CASH不存在，请检查后重试');
            }
        }
    }

    /**
     * 获取变现的配置信息
     */
    public function getFastCashConfig($key='')
    {
        if (empty($key)) {
            return $this->_config;
        }
        $config = $this->_config;
        if (!in_array($key, array_keys($config))) {
            throw_exception("key:{$key}值不存在,请检查");
        }

        return $config[$key];

    }

    public function getSupportRepayWay()
    {
        return $this->_open_repay_way;
    }

    /**
     * 变现状态数组
     * @return type
     */
    public function getStatusCash(){
        return $this->_cash_status_list;
    }
    
    /**
     * 获取排序数组
     */
    public function getOrder($order,$sort)
    {
        switch($order){
            case self::ORDER_PUBLISH:
                $order = "ctime";
                break;
            case self::ORDER_INCOME:
                $order = "year_rate";
                break;
            case self::ORDER_TIME_LIMIT:
                $order = "last_repay_date";
                break;
            case self::ORDER_DEMAND_AMOUNT:
                $order = "demand_amount";
                break;
            case self::ORDER_SCHEDULE:
                $order = "order_schedule";//计算出来的字段
                break;
            case self::ORDER_MIN_BID_AMOUNT:
                $order = "min_bid_amount";
                break;
            default:
                $order = "ctime";
        }
        return "{$order} {$sort}";
    }

    /**
     * 检查当前订单是否可以变现
     * 条件：
     * 1，订单所属项目不能是允许提前还款的项目
     * 2，订单持有时间大于等于30天
     * 3，订单已经变现次数小于3次
     * 4，变现层级小于设定值
     * @param $prj_order_id
     * @param $uid
     * @return bool|mixed
     */
    public function is_cash($prj_order_id,$uid)
    {
        $prj_order_info = $this->get_prj_order_info($prj_order_id,$uid);
        $prj_info = $this->get_prj_info($prj_order_info['prj_id']);
        $config = $this->_config;
        $time = time();

        //订单状态不是已支付的不行
        D('Financing/PrjOrder');
        if ($prj_order_info['status'] != PrjOrderModel::STATUS_PAY_SUCCESS){
            throw_exception("该项目还未支付，不能变现!");
        }

        //不能转让的不行
        if(!$prj_order_info['is_transfer']){
            throw_exception("该项目不能变现!");
        }

        //设定提前还款的项目不能变现的限制后检查该项目是否属于提前还款项目
        if($config['cash_limit'] && isset($prj_info['is_early_repay']) && $prj_order_info['is_early_repay']){
            throw_exception("该项目属于提前还款项目不能变现");
        }

        //设定提前还款的项目不能变现的限制后检查该项目是否属于提前还款项目
        if($config['cash_limit'] && isset($prj_info['is_extend']) && $prj_order_info['is_extend']){
            throw_exception("该项目属于展期项目不能变现");
        }

        //订单持有时间小于设定的项目最少持有天数不能变现
        if(day_diff($prj_order_info['end_bid_time']) < (int)$config['min_have_days']){
            throw_exception("订单持有时间小于设定的项目最少持有天数不能变现");
        }

        //订单剩余期限小于设定的项目最小剩余天数不能变现
        if(day_diff($time,$prj_order_info['last_repay_date']) < (int)$config['min_surplus_days']){
            throw_exception("订单剩余时间小于设定的项目最少剩余天数不能变现");
        }

        //该订单已经变现次数大于设定的最多变现次数
        if($prj_order_info['cash_times'] >= $config['max_cash_times']){
            throw_exception("该订单已经变现次数大于设定的最多变现次数");
        }

        //变现层级大于变现层级限制的订单不能再进行变现
        if($prj_order_info['cash_level'] >= $config['cash_level_limit']){
            throw_exception("变现层级大于变现层级限制的订单不能再进行变现");
        }

        //配置中的最小可变现金额
        $min_can_cash_money = (int)numberFormat($config['min_can_cash_money']/100);

        //校验surplus_amount
        if($prj_order_info['surplus_amount'] < $min_can_cash_money){
            throw_exception('可变现金额不能小于系统最小可变现金额');
        };

        //用fastcash中的合计消耗金额和总可变现消耗金额做一次校验，不能只依靠surplus_amount限制
        if($this->get_cashed_consume($prj_order_id,$uid) > $prj_order_info['total_amount']-$min_can_cash_money){
            throw_exception('已变现或变现中的金额不能大于该订单最大可变现金额');
        };

        //如果投资金额小于设定的配置值，也不能变现
        $fast_cash_trade = $this->get_trade_obj($prj_order_info['prj_id'], self::PRJ_TYPE_NAME);
        $max_can_cash = $fast_cash_trade->get_max_cash_money($prj_order_id, $uid);

        if ($max_can_cash < $min_can_cash_money){
            throw_exception("可变现金额不能小于系统最小可变现金额");
        }
    }

    public function is_cash_money($prj_order_id, $uid, $cash_money,$cash_rate)
    {
        $this->is_cash($prj_order_id, $uid);
        $config = $this->_config;
        $prj_order_info = $this->get_prj_order_info($prj_order_id,$uid);
        $prj_info = $this->get_prj_info($prj_order_info['prj_id']);

        $min_can_cash_money = (int)numberFormat($config['min_can_cash_money']/100);

        if ($cash_money < $min_can_cash_money){
            throw_exception("变现金额不能低于系统设定的最小可变现金额");
        }
        if ($cash_money > $prj_order_info['surplus_amount'] || $cash_money > $prj_order_info['total_amount']){
            throw_exception("变现金额不能大于该订单最大可变现金额");
        }
        $fast_cash_trade = service('Financing/FastCash')->get_trade_obj($prj_info['prj_id'],FastCashService::PRJ_TYPE_NAME);
        //校验用户输入的金额是否超过最大的可变现金额
        //利率是%多少。
        $max_can_cash = $fast_cash_trade->get_max_cash_money($prj_order_id, $uid, $cash_rate / 100);

        if($cash_money > $max_can_cash){
            throw_exception('变现金额不能大于该订单最大可变现金额');
        }
    }

    public function getFastCashInfo($id,$uid = 0)
    {
        $where = array(
            'id'=>$id
        );
        if($uid){
            $where['uid'] = $uid;
        }
        $field = array(
            'plan_money',
            'status',
        );
        $msgTips = array(
            self::STATUS_PENDING=>"待审核",
            self::STATUS_START=>"成功申请变现借款[MONEY]元",
            self::STATUS_END=>"已截标",
            self::STATUS_FAIL=>"审核失败",
        );
        $model =  D("Financing/FastCash");
        $count = $model->where($where)->count();
        !$count && throw_exception("信息不存在!请刷新页面重试!");
        $info = $model->where($where)->field($field)->find();
        $msg = str_replace('[MONEY]',  moneyView($info['plan_money']),$msgTips[$info['status']]);
        return array('money' => $info['plan_money'], 'msg' => $msg);
    }

    /**
     * 取消变现的成功页面的
     * 根据fast_id获取fast信息和order信息
     */
    public function getFastCashAndOrderInfoById($fast_id,$field)
    {
        $model = D('PrjFastCash');
        $where = array(
            'fi_prj_fast_cash.mi_no' => self::$api_key,
            'fi_prj_fast_cash.id' => $fast_id,
        );
        $join = array(
            'fi_prj_order on fi_prj_fast_cash.prj_order_id = fi_prj_order.id',
            'fi_prj on fi_prj.id = fi_prj_fast_cash.prj_id',
        );
        $info = $model
                ->field($field)
                ->where($where)
                ->join($join)
                ->limit(1)
                ->find();
        return $info;
    }
    /**
     * 申请变现成功后发消息
     * @return type
     */
    public function applyCashSuccess($fast_cash_id)
    {
        $fun = "_".__FUNCTION__;
        $messageData = $this->$fun($fast_cash_id);
        $config = $this->_config;
        //成功这发送邮件
        try {
            //推送手机提醒
            //$this->addRemindNotify($messageData, $config['ApplyCashSuccess']);
            return service("Message/Message")->sendMessage($messageData[0], 1, $config['ApplyCashSuccess'], $messageData, 600, 1, array(1,2,3), true);
        }
        catch (Exception $e) {
            throw_exception($e->getMessage());
        }
    }

    protected function _applyCashSuccess($fast_cash_id)
    {
        $field = 'fi_prj_fast_cash.plan_money,fi_prj.prj_name,fi_prj_fast_cash.uid,fi_prj.id,fi_prj.prj_type';
        $info = $this->getFastCashAndOrderInfoById($fast_cash_id, $field);
        $uid = $info['uid'];
        $uname = $this->getUserName($uid);
        $messageData = array();
        $messageData[0] = $uid;
        $messageData[1] = $info['prj_name'];
        $messageData[2] = humanMoney($info['plan_money'], 2, false) . "元";
        $messageData[3] = $uname;
        $messageData[7] = $info['id'];
        $messageData[8] = $info['prj_name'];
        $messageData[9] = $info['prj_type'];
        $messageData[] = $uname;
        return $messageData;
    }

    /**
     * 取消变现的发送
     */
    public function cancelCash($fast_cash_id)
    {

        $fun = "_".__FUNCTION__;
        $messageData = $this->$fun($fast_cash_id);
        $config = $this->_config;
        //成功这发送邮件
        try {
            $this->addRemindNotify($messageData, $config['CancelCash']);
            return service("Message/Message")->sendMessage($messageData[0], 1, $config['CancelCash'], $messageData, 600, 1, array(1,2,3), true);
        }
        catch (Exception $e) {
            throw_exception($e->getMessage());
        }
    }

    protected function _cancelCash($fast_cash_id)
    {
        $field = 'fi_prj.prj_name,fi_prj_fast_cash.uid,fi_prj.id,fi_prj.prj_type';
        $info = $this->getFastCashAndOrderInfoById($fast_cash_id, $field);
        $uid = $info['uid'];
        $uname = $this->getUserName($uid);
        $messageData = array();
  
        $messageData[0] = $uid;
        $messageData[1] = $info['prj_name'];
        $messageData[2] = $uname;
        
        $messageData[7] = $info['id'];
        $messageData[8] = $info['prj_name'];
        $messageData[9] = $info['prj_type'];
        return $messageData;
    }

    /**
     * 结束变现的时候发送的站内信和手机短信
     */
    public function endCash($fast_cash_id)
    {
        $fun = "_".__FUNCTION__;
        $messageData = $this->$fun($fast_cash_id);
        $config = $this->_config;
        try {
            $this->addRemindNotify($messageData, $config['EndCash']);
            return service("Message/Message")->sendMessage($messageData[0], 1, $config['EndCash'], $messageData, 600, 1, array(1,2,3), true);
        }
        catch (Exception $e) {
            throw_exception($e->getMessage());
        }
    }

    protected function _endCash($fast_cash_id)
    {
        $field = 'fi_prj.prj_name,fi_prj_fast_cash.uid,fi_prj.remaining_amount,fi_prj.demand_amount,fi_prj.id,fi_prj.prj_type';
        $info = $this->getFastCashAndOrderInfoById($fast_cash_id, $field);
        $uid = $info['uid'];
        $uname = $this->getUserName($uid);
        $messageData = array();
        $messageData[0] = $uid;
        $messageData[1] = $info['prj_name'];
        $info['curred_money'] = $info['demand_amount'] - $info['remaining_amount'];
        $messageData[2] = humanMoney($info['curred_money'], 2, false) . "元"; //已募集金额
        $messageData[3] = $uname;
        $messageData[7] = $info['id'];
        $messageData[8] = $info['prj_name'];
        $messageData[9] = $info['prj_type'];
        $messageData[] = $uname;
        return $messageData;
    }
    
    /**
     * 手机端推送信息
     */
    public function addRemindNotify($messageData,$mtype)
    {
        try{
            $remindService = service("Public/Remind");
            $uid = $messageData[0];
           
            $remind_time = time()+20;//提醒时间
            
            //手机推送
            $pass_content_arr = D("Message/Message")->getMessageTpl($mtype, 4, $messageData);
            if (empty($pass_content_arr)) {
                 throw_exception("请先配置好手机端推送的模板!");
            }
            
            $remind_message = htmlspecialchars_decode($pass_content_arr['pass_content']); //内容
            
            $remind_title = htmlspecialchars_decode($pass_content_arr['title']); //内容
            $remind_type = 190;
            $obj_id = $messageData[7];
            $memo = array(
                'prj_id'=>$obj_id,
                'prj_name'=>$messageData[8],
                'prj_type'=>$messageData[9],
            );
            $res = $remindService->addRemind($uid,$remind_time,$remind_title,$remind_message,$remind_type,$obj_id,$memo,0);
            return $res?true:false;
        }catch(Exception $e){
            throw_exception($e->getMessage());
        }
    }

    /**
     * @param $uid
     * @return 获取用户名
     */
    protected function getUserName($uid){
        $data = M('user')->field('uname')->where('uid='.$uid)->find();
        return $data['uname'];
    }

    /**
     * 检查当前项目是否支持变现
     * 条件：
     * 1，项目不能是允许提前还款的项目
     * 2，项目期限大于最短持有期限加上最小剩余期限
     * 4，变现层级小于设定值
     * @param $prj_id
     * @return bool|mixed
     */
    public function is_prj_cash($prj_id)
    {
        //项目是否能变现
        $extInfo = service("Financing/Project")->getExt($prj_id);
        if (!$extInfo['is_transfer']) {
            return false;
        }
        $prj_info = $this->get_prj_info($prj_id);
        $config = $this->_config;

        /**
         * 设定提前还款的项目不能变现的限制后检查该项目是否属于提前还款项目
         */
        if($config['cash_limit'] && ($prj_info['is_early_repay'] ||$prj_info['is_extend'])){
            return false;
        }

        /**
         * 项目期限大于最短持有期限加上最小剩余期限
         */
        $time_limit_day = $prj_info['time_limit_day'];
        if ($prj_info['prj_type'] == 'H'/*PrjModel::PRJ_TYPE_H*/) {
            $start_time = time();
            if ($prj_info['bid_status'] != 2/*PrjModel::BSTATUS_BIDING*/) {
                $start_time = $prj_info['ctime'];
            }
            $time_limit_day = $this->showTimeLimitSdt($start_time - 24 * 3600, $prj_info['last_repay_date'], true);
            $time_limit_day = (int)$time_limit_day;
        }
        if($time_limit_day <= ($config['min_have_days']+$config['min_surplus_days'])){
            return false;
        }

        /**
         * 变现层级大于变现层级限制不能再进行变现,因为订单是小于3次。项目就要小于2
         */
        if(($prj_info['cash_level']+1) >= $config['cash_level_limit']){
            return false;
        }
        if (!in_array($prj_info['repay_way'], $this->_open_repay_way)) {
            return false;
        }

        return true;
    }
    //审核时的判断
    function audit_is_cash($prjInfo){
        $config = $this->_config;
        /**
         * 项目期限大于最短持有期限加上最小剩余期限
         */
        if($prjInfo['time_limit_day'] < ($config['min_have_days']+$config['min_surplus_days'])){
            return false;
        }
        return true;
    }


    /**
     * 计算变现信息包括两种计算
     * 1，给出利率，计算该订单当前最大可变现金额
     * 2，给出计划变现金额，计算最大利率
     * @param $prj_order_id
     * @param $uid
     * @param string $cash_money
     * @return array
     */
    public function calc_cach_info($prj_order_id,$uid,$cash_money='')
    {
        $return_data = array(
            'status' => 0,
            'message' => '',
            'data' => '',
        );
        try {
//            if(!$this->is_cash($prj_order_id,$uid)){
//                throw_exception('当前订单不符合变现要求');
//            }

            $prj_order_info = $this->get_prj_order_info($prj_order_id,$uid);
            $obj = service('Financing/Project')->getTradeObj($prj_order_info['prj_id'], "FastCash");
//            return $obj->calc_cach_amout($prj_order_id,$cash_money,$cash_rate);
//            return $obj->get_total_money($prj_order_id,$uid);
            return $obj->get_prj_order_total_amount($prj_order_id,$uid);

        } catch (Exception $exc) {
            $return_data['message'] = $exc->getMessage();
        }

        return $return_data;
    }

    /**
     * 按月付息，到期还本
     * $original_money 借款人在原始项目的投资金额
     * $total_time 原始项目的投资期限
     * $past_time 原始项目的起息日至变现当日的天数
     * $original_rate 原始资产年化收益率
     * 上面四个数值都可以根据$prj_order_id取到
     * $cash_money 变现金额
     * $curr_rate 借款人申请的借款回款利率，默认是$original_rate
     * 这两个是需要用户输入的变量，输入其中一个计算另一个。
     */
    public function repayment_method_one($prj_order_id,$cash_money='',$curr_rate='')
    {

    }

    /**
     * 到期一次性还本付息
     */
    public function repayment_method_two()
    {

    }

    /**
     * 按月等额本息
     */
    public function repayment_method_three()
    {
    }


    /**
     * 项目期限
     * @param type $prj_id
     * @return type
     */
    public function get_prj_time_limit($prj_id)
    {
        $prj_info = $this->get_prj_info($prj_id);
        $end_bid_time = $prj_info['end_bid_time'];
        $last_repay_date = $prj_info['last_repay_date'];
        if($end_bid_time>=$last_repay_date){
            throw_exception("项目异常!");
        }
        return day_diff($end_bid_time,$last_repay_date);
    }

    public function get_prj_order_money($prj_order_id,$uid)
    {
        $prj_order_info = $this->get_prj_order_info($prj_order_id,$uid);
        return numberFormat($prj_order_info['money']/100);
    }

    public function get_prj_year_rate($prj_id)
    {
        $prj_info = $this->get_prj_info($prj_id);
        return $prj_info['year_rate']/1000;
    }

    /**
     * 获取当前订单生成的变现项目的有效期限
     * @param $prj_order_id
     * @param $uid
     * @return mixed
     */
    public function get_time_limit($prj_order_id,$uid,$time = 0)
    {
        $prj_order_info = $this->get_prj_order_info($prj_order_id,$uid);
        if(!$time){
            $time = time();
        }
        if($prj_order_info['last_repay_date'] > $time){
            return day_diff($time,$prj_order_info['last_repay_date']);
        }
        return $prj_order_info['last_repay_date'];
    }


    public function get_have_time($prj_order_id,$uid)
    {
        $prj_info = $this->get_prj_info_by_order_id($prj_order_id,$uid);
        $srvProject = service('Financing/Project');
        $t = strtotime(date('Y-m-d',$prj_info['end_bid_time']));
        $balanceTime = $srvProject->getBalanceTime($prj_info['end_bid_time'],$prj_info['id']);
        if($prj_info['end_bid_time']>$balanceTime) $t = strtotime("+1 days",$t);//T向后延迟一天
        //起息日
        $freezeDate = $srvProject->getPrjValueDate($prj_info['id'],$t);
        $day_diff = day_diff($freezeDate);
        if($day_diff >= $prj_info['time_limit_day'] || $day_diff < 0){
            $day_diff = $prj_info['time_limit_day'];
        }
        return (int)$day_diff;
    }

    /**
     * 计算起息日
     * 1、计算方法是投资当日+0
     */
    public function getValueDate($prj_order_id, $uid)
    {
        $prj_info = $this->get_prj_order_info($prj_order_id, $uid);
        $t = strtotime(date('Y-m-d', $prj_info['freeze_time']));
        //起息日
        return date("Y年m月d日",$t);
    }

    /**
     * 根据订单ID获取主体投资项目的信息
     * @param $prj_order_id
     * @param $uid
     * @return bool|mixed
     */
    public function get_prj_info_by_order_id($prj_order_id,$uid)
    {
        $prj_order_info = $this->get_prj_order_info($prj_order_id,$uid);
        /**
         * prj_order表中is_cash_prj表示主体标是否是变现标
         */
        $prj_id = $prj_order_info['prj_id'];
        if($prj_order_info['is_cash_prj']){
            $prj_cash_info = $this->get_prj_cash_info($prj_id);
            $prj_id = $prj_cash_info['prj_id'];
        }
        return $this->get_prj_info($prj_id);
    }

    /**
     * 获取某订单已经变现的金额
     * @param $prj_order_id
     * @param $uid
     * @return float
     */
    public function get_cashed_consume($prj_order_id,$uid)
    {
        $where = array(
            'uid'=>$uid,
            'prj_order_id'=>$prj_order_id,
            'status'=>array('IN',array(
                self::STATUS_PENDING,
                self::STATUS_START,
                self::STATUS_END,
                self::STATUS_FULL,
            )),
        );
        $res = M('prj_fast_cash')->where($where)->sum('consume');
        return $res;
    }

    /**
     * 获取某订单已经变现的次数
     * @param $prj_order_id
     * @param $uid
     * @return mixed
     */
    public function get_cashed_times($prj_order_id, $uid)
    {
        $where = array(
            'uid'=>$uid,
            'prj_order_id'=>$prj_order_id,
            'status'=>array('IN',array(self::STATUS_CAN_CASH,self::STATUS_CASHING,self::STATUS_CASHED)),
        );
        return M('prj_fast_cash')->where($where)->count();
    }

    /**
     * 获取项目订单信息
     * @param $prj_order_id
     * @param $uid
     * @return bool|mixed
     */
    public function get_prj_order_info($prj_order_id,$uid)
    {
        if(isset($this->_prj_order_info[$prj_order_id]) && !empty($this->_prj_order_info[$prj_order_id])){
            return $this->_prj_order_info[$prj_order_id];
        }

        $where = array(
            'uid' => $uid,
            'id'  => $prj_order_id,
            'mi_no' => BaseModel::getApiKey('api_key'),
        );
        $this->_prj_order_info[$prj_order_id] = M('prj_order')->where($where)->find();

        if(!$this->_prj_order_info[$prj_order_id]){
            throw_exception('该订单不存在，请刷新后重试');
        }
        return $this->_prj_order_info[$prj_order_id];
    }

    /**
     * 获取项目信息
     * @param $prj_id
     * @return bool|mixed
     */
    public function get_prj_info($prj_id)
    {
        if(isset($this->_prj_info[$prj_id]) && !empty($this->_prj_info[$prj_id])){
            return $this->_prj_info[$prj_id];
        }
        $where = array(
            'id'  => $prj_id,
//            'mi_no' => BaseModel::getApiKey('api_key'),
        );
        $prj_info = M('prj')->where($where)->find();
        if(!$prj_info){
            throw_exception('该项目不存在，请刷新后重试');
        }
        $prj_ext_info = M('prj_ext')->find($prj_id);
        if(!$prj_ext_info){
            $prj_ext_info = array();
        }
        $this->_prj_info[$prj_id] = array_merge($prj_info,$prj_ext_info);
        return $this->_prj_info[$prj_id];
    }

    /**
     * 获取变现项目信息
     * @param $id
     * @return bool|mixed
     */
    public function get_prj_cash_info($id)
    {
        if(isset($this->_prj_cash_info[$id]) && !empty($this->_prj_cash_info[$id])){
            return $this->_prj_cash_info[$id];
        }
        $where = array(
            'id'  => $id,
            'mi_no' => BaseModel::getApiKey('api_key'),
        );
        $prj_cash_info = M('prj_fast_cash')->where($where)->find();
        if(!$prj_cash_info){
            throw_exception('该变现项目不存在，请刷新后重试');
        }
        $this->_prj_cash_info[$id] = $prj_cash_info;
        return $this->_prj_cash_info[$id];
    }

    /**
     * 可变现的订单列表
     * @param array $condition
     * @return mixed
     */
    /**
     * @param array $condition
     * @param bool $only_total
     * @param bool $is_total//是否统计手机端的总金额
     */
    public function get_can_cash_list(array $condition,$only_total=false,$is_total = false)
    {
        try {
            //检查condition的参数是否符合要求
            checkArrayData(
                $condition,
                array('uid','page_number','page_size','order','is_page_info'),
                array(1,2,3,4)
            );
            $uid = $condition['uid'];
            $page_number = (int)$condition['page_number'];
            $page_size = (int)$condition['page_size'];
            $order = empty($condition['order']) ? 'fi_prj_order.id DESC' : $condition['order'];

            $config = $this->_config;
            $time = time();
            $today = strtotime(date('Y-m-d', $time));
            D('Financing/PrjOrder');
            /**
             * 是否可变现的条件，临时屏蔽几条，不然没数据，最后都要打开
             */
            $where = array(
                'fi_prj_order.mi_no' => self::$api_key,
                'fi_prj_order.uid' => $uid,
                'fi_prj_order.is_transfer' => 1,//是否可转让
                'fi_prj_order.status' => PrjOrderModel::STATUS_PAY_SUCCESS,//已支付的订单才可以变现
                'fi_prj_order.surplus_amount' => array('EGT',$config['min_can_cash_money']),//剩余可变现金额大于等于100可变
                'fi_prj_order.cash_times' => array('LT',$config['max_cash_times']),//小于等于一个订单最多变现次数
                'fi_prj_order.cash_level' => array('LT',$config['cash_level_limit']),//小于等于变现层级限制
                'fi_prj.end_bid_time' => array('LT',$today-86400*$config['min_have_days']),//截标时间小于当前时间-项目最少持有天数
                'fi_prj.last_repay_date' => array('GT',$today+86400*$config['min_surplus_days']),//最终还款日小于当前时间+项目最少剩余天数
                'fi_prj.repay_way' => array('IN',implode(',',$this->_open_repay_way)),//当前速兑通支持的还款方式
            );

            //有提现限制的话，提前还款项目的订单不能变现
            if($config['cash_limit']){
                $where['fi_prj_ext.is_early_repay'] = array(
                    array('EXP',' is NULL'),
                    array('NEQ',1),
                    'OR',
                );//不能是提前还款
                //不能是展期项目
                $where['fi_prj_ext.is_extend'] = array(
                    array('EXP',' is NULL'),
                    array('NEQ',1),
                    'OR',
                );
            }

            $prj_oder_model = M('prj_order');

            $join = array(
                'fi_prj ON fi_prj_order.prj_id = fi_prj.id',
                'fi_prj_ext ON fi_prj_order.prj_id = fi_prj_ext.prj_id',
            );

            $total = $prj_oder_model->join($join)->where($where)->count();
            if($only_total){
                return $total;
            }

            if($is_total){
                $field = array("sum(fi_prj_order.total_amount)"=>'total');
            }else{
                $field = 'fi_prj_order.*,'
                            . 'fi_prj_order.ctime as order_mtime,'
                            . 'fi_prj.prj_name,'
                            . 'fi_prj.prj_type,'
                            . 'fi_prj.bid_status,'
                            . 'fi_prj.repay_way,'
                            . 'fi_prj_ext.is_early_repay,'
                            . 'fi_prj.last_repay_date,'
                            . 'fi_prj.end_bid_time,'
                            . 'fi_prj.prj_business_type,'
                            . 'fi_prj.year_rate as year_rate_pj,'
                            . 'fi_prj.activity_id';
            }

            $list =  $prj_oder_model
                    ->field($field)
                    ->join($join)
                    ->where($where)
                    ->page($page_number,$page_size)
                    ->order($order)
                    ->select();

            if($is_total){
                return $list[0]['total'];
            }

//            echo $prj_oder_model->getLastSql();exit;

            $fast_service =  service('Financing/FastCash');
            $srvPrj = service("Financing/Prj");
            foreach ($list as $key => $val) {
                $fast_cash_trade = $fast_service->get_trade_obj($val['prj_id'],FastCashService::PRJ_TYPE_NAME);
                //包含今天，所以在日期中在加上一天
                $list[$key]['least_time'] = day_diff(time()-24*3600,$val['last_repay_date']);
                $list[$key]['repay_way_view'] = service('Financing/Project')->getRepayWay($val['repay_way']);
                $list[$key]['total_money'] = number_format($val['total_amount']/100,2);
                //当前订单的可变现金额的最大值
                try {
                    $max_cash_money = $fast_cash_trade->get_max_cash_money($val['id'], $val['uid']);
                    $list[$key]['can_money'] = number_format(numberFormat($max_cash_money), 2);
                }
                catch (Exception $e) {
                    //如果有异常,直接返回可变现金额为0
                    $list[$key]['can_money'] = 0;
                }
                $list[$key]['rate'] = $val['year_rate_pj']/10 . '%';
                $list[$key]['time_limit'] = formatTimeLimitView($val['time_limit'],$val['time_limit_unit']);
                $list[$key]['prj_name'] = $srvPrj->getPrjTitleShow($val['id'], $val);//调整项目名称
                $list[$key]['prj_number'] = $val['prj_name'];
                //格式化输出
                //期限加上一天
                $list[$key]['curr_time_limit'] = $this->showTimeLimitSdt($time-24*3600,$val['last_repay_date'],true);
                
                $list[$key]['is_view'] = (int)(($val['prj_type'] != PrjModel::PRJ_TYPE_C) && ($val['bid_status'] > PrjModel::BSTATUS_FULL) && !in_array($val['bid_status'], array(PrjModel::BSTATUS_FAILD, PrjModel::BSTATUS_END)));
                $list[$key]['order_id'] = $list[$key]['id'];
                service('Account/Record')->getExtProfit($list[$key]);
            }
            if($condition['is_page_info']){
                $paging  = W(
                    "Paging",
                    array(
                        "totalRows"=>$total,
                        "pageSize"=>$page_size,
                        "parameter"=>array('type'=>1)),
                    true);
            }
            return array(
                'list'=>$list,
                'paging'=>$paging,
            );

        } catch (Exception $exc) {
            jsonReturn(0,$exc->getMessage(),0);
        }
    }

    public function get_cash_list(array $condition,$only_total=false)
    {
         try {
             if($condition['prj_ids']){
                 //检查condition的参数是否符合要求
                 checkArrayData(
                     $condition, array('uid', 'prj_ids', 'status', 'page_number', 'page_size', 'order', 'is_page_info'), array(2, 3, 4, 5)
                 );
             }else{
                 //检查condition的参数是否符合要求
                 checkArrayData(
                     $condition, array('uid', 'status', 'page_number', 'page_size', 'order', 'is_page_info'), array(2, 3, 4, 5)
                 );
             }



            $uid = (int) $condition['uid'];
            $page_number = (int) $condition['page_number'];
            $page_size = (int) $condition['page_size'];
            $type = I("type",2,"intval");//处理页面的类型
            $order = empty($condition['order']) ? 'fi_prj_order.id  DESC' : $condition['order'];

            $model = M('prj_fast_cash');
            $where = array(
                'fi_prj_fast_cash.mi_no' => self::$api_key,
                'fi_prj_fast_cash.uid' => $uid,
                'fi_prj_fast_cash.status' => $condition['status'],
            );
            if($condition['prj_ids']){
                $where['fi_prj_fast_cash.prj_id'] = ['IN', $condition['prj_ids']];
            }

            $field = array("DISTINCT fi_prj_fast_cash.prj_order_id" => 'prj_order_id');
            $org_prjids = $model->field($field)->where($where)->select();
            //总的记录的条数
            $total = count($org_prjids);
            if ($only_total) {
                return $total;
            }
            //如果记录为空
            if (!$total) {
                return array(
                    'list' => array(),
                );
            }
            $join = array(
                'fi_prj on fi_prj.id = fi_prj_order.prj_id',
            );
            foreach($org_prjids as $v){
                $org_prjids_no_keys[] = $v['prj_order_id'];
            }
            $list_where['fi_prj_order.id'] = array('IN',$org_prjids_no_keys);
            $order_model = D("Financing/PrjOrder");
            $list = $order_model
                    ->field('fi_prj.*,fi_prj_order.*')
                    ->where($list_where)
                    ->join($join)
                    ->page($page_number, $page_size)
                    ->order($order)
                    ->select();

//            var_dump($order_model->_sql());exit;
//            var_dump($list);exit;
            $fast_service =  service('Financing/FastCash');
            $srvPrj = service("Financing/Prj");
            foreach ($list as $key => $val) {
                $list[$key]['least_time'] = $this->get_time_limit($val['id'], $uid);
                //echo $val['prj_order_id'];
                $list[$key]['repay_way_view'] = service('Financing/Project')->getRepayWay($val['repay_way']);
                $list[$key]['total_money'] = number_format(numberFormat(($val['possible_yield'] + $val['rest_money']) / 100),2);
                $list[$key]['rate'] = $val['year_rate'] / 10 . '%';
                $fast_cash_trade = $fast_service->get_trade_obj($val['prj_id'],FastCashService::PRJ_TYPE_NAME);
                //当前订单的可变现金额的最大值
                try {
                    $max_cash_money = $fast_cash_trade->get_max_cash_money($val['id'], $val['uid']);
                    $list[$key]['can_money'] = number_format(numberFormat($max_cash_money), 2);
                }
                catch (Exception $e) {
                    //如果有异常,直接返回可变现金额为0
                    $list[$key]['can_money'] = 0;
                }
                $list[$key]['order_id'] = $list[$key]['id'];
                service('Account/Record')->getExtProfit($list[$key]);
                $list[$key]['prj_name'] = $srvPrj->getPrjTitleShow($val['id'], $val);//调整项目名称
                $list[$key]['end_time'] = date('Y-m-d', $val['end_bid_time']);
                $list[$key]['time_limit_unit_view'] = formatTimeLimitView($val['time_limit'],$val['time_limit_unit']);
                $list[$key]['time_limit'] = $fast_service->showTimeLimitSdt(time()-24*3600,$val['last_repay_date'],true);
                //根据不同的订单获取不同的项目
                $prjlist = $this->getPrjListByOrderId($val['id'], $condition['status']);
                if(!empty($prjlist)){
                    foreach($prjlist as $k=>$v){
                        $prjlist[$k]['prj_name'] = $srvPrj->getPrjTitleShow($v['id'], $v);//调整项目名称
                        $prjlist[$k]['total_num'] = $v['invest_count'];
                        $prjlist[$k]['pay_count'] = $order_model->getLenderListByPrjId($v['id'],true);
                        $prjlist[$k]['end_time'] = date('Y-m-d', $v['end_bid_time']);
                        //这里是取得快速兑现的状态
                        $prjlist[$k]['cash_status_text'] = $this->_cash_status_list[$v['fast_status']];
                        //当前已募集金额
                        $curred_money = $v['plan_money']-$v['remaining_amount'];
                        $prjlist[$k]['curred_money'] = moneyView($curred_money);
                        $prjlist[$k]['plan_money'] = moneyView($v['plan_money']);
                        $prjlist[$k]['fee_money'] = moneyView($curred_money  * $this->_config['service_fee']);

                    }
                }
                $list[$key]['prjlist'] = $prjlist;
            }



            if ($condition['is_page_info']) {
//                var_dump($total);
//                var_dump($model->_sql());exit;
                $paging = W(
                        "Paging", array(
                    "totalRows" => $total,
                    "pageSize" => $page_size,
                    "parameter" => array('type' => $type)), true);
            }
            return array(
                'list' => $list,
                'paging' => $paging,
            );
        } catch (Exception $exc) {
            jsonReturn(0, $exc->getMessage(), 0);
        }
    }
    
    public function getCanCashByUid($uid)
    {
        $condition = array(
            'uid'           => $uid,
        );
        $can_cash_total = $this->get_can_cash_list($condition,1);
        return (int)$can_cash_total;
       
    }

    /**
     * 手机的可变现和已变现的列表，只查询订单下面的项目
     * @param array $condition
     * @param type $only_total
     * @return type
     */
    public function getCashListByPhone(array $condition,$only_total=false)
    {
         try {
            //检查condition的参数是否符合要求
            checkArrayData(
                    $condition, array('uid', 'status', 'page_number', 'page_size', 'order', 'is_page_info'),
                    array(2, 3, 4, 5)
            );


            $uid = (int) $condition['uid'];
            $page_number = (int) $condition['page_number'];
            $page_size = (int) $condition['page_size'];
            $type = I("type",2,"intval");//处理页面的类型
            $order = empty($condition['order']) ? 'fi_prj_order.id  DESC' : $condition['order'];

            $model = M('prj_fast_cash');
            $where = array(
                'fi_prj_fast_cash.mi_no' => self::$api_key,
                'fi_prj_fast_cash.uid' => $uid,
                'fi_prj_fast_cash.status' => $condition['status'],
            );

            $field = array("DISTINCT fi_prj_fast_cash.prj_order_id" => 'prj_order_id');
            $org_prjids = $model->field($field)->where($where)->select();
            //总的记录的条数
            $total = count($org_prjids);

            //如果记录为空
            if (!$total)
            {

                if (!$only_total)
                {
                    return array(
                        'list' => array(),
                    );
                }
                else
                {
                    return 0;
                }
            }
            $join = array(
                'fi_prj_order on fi_prj_fast_cash.prj_order_id = fi_prj_order.id',
                'fi_prj on fi_prj.id = fi_prj_fast_cash.prj_id',
            );
            $list = $model
                    ->field(
                        'fi_prj_fast_cash.status as prj_fast_cash_status,'
                        . 'fi_prj_fast_cash.ctime as order_mtime,'
                            . 'fi_prj_fast_cash.prj_id as prj_id,'
                            . 'fi_prj_fast_cash.id as fid,'
                            . 'fi_prj_fast_cash.mtime as fmtime,'
                            . 'fi_prj_order.expect_repay_time,'
                            . 'fi_prj_order.id,'
                            . 'fi_prj_fast_cash.year_rate,'
                            . 'fi_prj.prj_name,'
                            . 'fi_prj.prj_type,'
                            . 'fi_prj.repay_way,'
                            . 'fi_prj.bid_status,'
                            . 'fi_prj.last_repay_date,'
                            . 'fi_prj.repay_way,'
                            . 'fi_prj.demand_amount,'
                            . 'fi_prj.remaining_amount,'
                            . 'fi_prj.year_rate as rate,'
                            . 'fi_prj.prj_business_type'
                            )
                    ->where($where)
                    ->join($join)
                    ->page($page_number, $page_size)
                    ->order($order)
                    ->select();
//            echo $model->getLastSql();exit;
            if ($only_total) {
                return $model->where($where)->count();
            }
            $order_model = D("Financing/PrjOrder");
            $investService = service("Financing/Invest");
            foreach ($list as $k => &$v) {
                  $v['rate'] = number_format($v['rate']/10 , 2) . '%';
                  $v['curr_time_limit'] = $this->showTimeLimitSdt(time()-24*3600,$v['last_repay_date'],true);
                  $v['repay_way_view'] = service('Financing/Project')->getRepayWay($v['repay_way']);
                  $v['plan_cash_money'] = moneyView($v['demand_amount'])."元";
                  $v['real_cash_money'] = moneyView($v['demand_amount'] - $v['remaining_amount'])."元";
                $feeMoney = (int)($v['demand_amount'] - $v['remaining_amount']);
                $v['fee_money'] = moneyView($feeMoney * $this->_config['service_fee']);
                  $v['real_cash'] = $v['demand_amount'] - $v['remaining_amount'];
                  //根据不同的类型，显示不同的合同的内容,TODO，支持多级变现
                  $v['is_view'] = $order_model->getLenderListByPrjId($v['prj_id'],true);
                  
                  $is_endate = 1;
                  $isShowReplay = $investService->isShowPlan($v['prj_id']);
                  if (!$investService->isEndDate($v['repay_way']))
                  {
                        $is_endate = 0;
                  }
                  $v['is_have_repayplan'] = $v['bid_status'] >= PrjModel::BSTATUS_BIDING && !$is_endate && $isShowReplay && $v['bid_status'] != PrjModel::BSTATUS_FAILD;
                
            }
            return array(
                'list'=>$list
            );


           
        } catch (Exception $exc) {
            jsonReturn(0, $exc->getMessage(), 0);
        }
    }

    /**
     * 根据订单的ID获取多个多个变现的速兑通
     * add by 001181
     * @param type $order_id
     */
    public function getPrjListByOrderId($order_id,$status){
        $order_id = (int) $order_id;
        //查询是否有投资
        $model = D("prj_fast_cash");
        $where = array(
            'prj_order_id'=>$order_id,
        );
        $count = $model ->where($where)->count();
        if(!$count){
            return array();
        }
        $sql= "SELECT
                        fi_prj.*, fi_prj_fast_cash.plan_money,
                        fi_prj_fast_cash.money,
                        fi_prj_fast_cash.id as fid,
                        fi_prj_fast_cash.status as fast_status
                FROM
                        (
                                SELECT
                                                        prj_id
                                                FROM
                                                        fi_prj_fast_cash
                                                WHERE
                                                        prj_order_id = {$order_id}
                        group by prj_id
                        ) AS t
                LEFT JOIN fi_prj ON fi_prj.id = t.prj_id
                LEFT JOIN fi_prj_fast_cash ON fi_prj_fast_cash.prj_id = fi_prj.id";
        if(is_array($status)){
            $statuslist = $status[1];
            $statuslists = "in(".implode(",",$statuslist).")";
            $sql.= " WHERE fi_prj_fast_cash.status  {$statuslists}";
        }else{
            $sql.= " WHERE fi_prj_fast_cash.status = {$status}";
        }
        $list = $model->query($sql);
        return $list;

    }

    public function get_cash_list2(array $condition,$only_total=false)
    {
        try {
            //检查condition的参数是否符合要求
            checkArrayData(
                $condition,
                array('uid','status','page_number','page_size','order','is_page_info'),
                array(2,3,4,5)
            );

            $uid = (int)$condition['uid'];
            $page_number = (int)$condition['page_number'];
            $page_size = (int)$condition['page_size'];
            $order = empty($condition['order']) ? 'fi_prj_fast_cash.id DESC' : $condition['order'];

            $model = M('prj_fast_cash');
            $where = array(
                'fi_prj_fast_cash.mi_no'=>self::$api_key,
                'fi_prj_fast_cash.uid'=>$uid,
                'fi_prj_fast_cash.status'=>(int)$condition['status'],
            );
            $join = array(
                'fi_prj ON fi_prj_fast_cash.prj_id = fi_prj.id',
            );
            $total = $model->join($join)->where($where)->count();
            if($only_total){
                return $total;
            }

            $list = $model
                ->field('fi_prj_fast_cash.*,fi_prj.prj_name,fi_prj.prj_type')
                ->where($where)
                ->join($join)
                ->page($page_number,$page_size)
                ->order($order)
                ->select();

//            var_dump($model->_sql());exit;
//            var_dump($list);exit;
            foreach($list as $key=>$val){
                $list[$key]['least_time'] = $this->get_time_limit($val['prj_order_id'],$uid);
                $list[$key]['repay_way_view'] = service('Financing/Project')->getRepayWay($val['repay_way']);
                $list[$key]['total_money'] = numberFormat(($val['possible_yield'] + $val['free_money'])/100);
                $list[$key]['can_money'] = $val['free_money']/100;
                $list[$key]['rate'] = $val['year_rate']/10 . '%';
                $list[$key]['fee_money'] = numberFormat($val['plan_money']/100 * $this->_config['service_fee']);
                $list[$key]['end_time'] = date('Y-m-d',$val['end_bid_time']);
            }

            if($condition['is_page_info']){
//                var_dump($total);
//                var_dump($model->_sql());exit;
                $paging  = W(
                    "Paging",
                    array(
                        "totalRows"=>$total,
                        "pageSize"=>$page_size,
                        "parameter"=>array('type'=>1)),
                    true);
            }
            return array(
                'list'=>$list,
                'paging'=>$paging,
            );

        } catch (Exception $exc) {
            jsonReturn(0,$exc->getMessage(),0);
        }
    }

    public function get_trade_obj($prj_id,$type)
    {
        if(!$this->_trade){
            $this->_trade = service('Financing/Project')->getTradeObj($prj_id, $type);
            if($this->_trade){
                return $this->_trade;
            }
        }
        return $this->_trade;
    }

    /**
     * 审核变现申请
     * @param $fast_cash_id
     * @param $uid
     * @param $prj_id
     * @return array
     * status = 2 审核通过
     * status = 3 审核未通过
     * msg 未通过原因
     */
    public function review_fast_cash_apply($fast_cash_id,$uid,$prj_id)
    {
        $res = array(
            'status'=>0,
            'msg'=>'',
        );
        $db = new BaseModel();
        $trans_key = 'fast_cash_review'.$fast_cash_id.$uid;
        try {
            $time = time();
            $fast_cash_model = M('prj_fast_cash');
            $fast_cash_info = $fast_cash_model->find($fast_cash_id);
            if ($uid != $fast_cash_info['uid'] || $prj_id != $fast_cash_info['prj_id']) {
                throw_exception('uid=' . $uid . 'prj_id=' . $prj_id . 'fast_cash_id=' . $fast_cash_id . '数据不匹配，无法审核通过');
            }

            $db->startTrans($trans_key);
            $fast_cash_update_data = array(
                'status' => self::STATUS_START,
                'mtime' => $time,
            );
            $rs = $fast_cash_model->where(array('id'=>$fast_cash_id))->save($fast_cash_update_data);
            if (!$rs) {
                throw_exception('变现申请审核失败');
            }

            $prj_model = D('Financing/prj');
            $prj_update_data = array(
                'bid_status'=>PrjModel::STATUS_PASS,
                'mtime'=>$time,
            );
            $rs = $prj_model->where(array('id'=>$prj_id))->save($prj_update_data);
            if(!$rs){
                throw_exception('项目审核失败');
            }
            $db->commit();
            $res['status'] = 2;
        } catch (Exception $exc) {
            $db->rollback($trans_key);
            $res['status'] = 3;
            $res['msg'] = $exc->getMessage();
        }
        return $res;

    }

    public function updateRelation($prj_id)
    {
        if(!$prj_id){
            throw_exception("参数错误");
        }
        //更新total_amount和rest_money,possible_yield三者之间的关系

        $fast_cash_trade_obj = service("Financing/Project")->getTradeObj($prj_id, 'FastCash');
        //orderinfo
        $orderModel = D("Financing/PrjOrder");
        $where = array(
            'prj_id' => $prj_id,
        );

        $orderlist = $orderModel->getOrderListByWhere($where);
        if ($orderlist) {
                foreach ($orderlist as  $item){
                    $save = array();
                    $method = "get_curr_buy_prj_order_total_amount";
                    if (!is_callable( array( $fast_cash_trade_obj, $method ))){
                      //如果别的类没有这个方法，抛出异常
                      throw_exception("{$method}不可用!");
                    }
                    $save['total_amount'] = $fast_cash_trade_obj->$method($item);
                    $save['surplus_amount'] = $save['total_amount'];
                    $order_where = array(
                        'id'=>$item['id'],
                    );
                    unset($item);
                    //更新数据
                    $orderModel->where($order_where)->limit(1)->save($save);
                }

        }
        return true;
    }

    public function buy($money,$uid,$prjId,$title,$moneyAccountId,$params,$isMobile)
    {
        $model = M("prj");
        $field = array(
            'tenant_id',
            'dept_id',
            'uid',
            'start_bid_time',
        );
        $info = $model->where(array("id"=>$prjId))->field($field)->find();
        if(!$info){
            throw_exception("异常,项目不存在!");
        }

        // 时间间隔检测开始
        $is_check_time_interval = !$params['skip_time_interval'];
        if($is_check_time_interval) {
            $srvTimerBuy = service('Financing/TimerBuy');
            $srvTimerBuy->init($uid, $prjId, $info);
            $srvTimerBuy->start();
        }

        //自己不能买自己的项目
        if(!$uid){
             throw_exception("请重新登录!");
        }
        if($info['uid'] == $uid){
             throw_exception("自己不能投资自己的项目!");
        }
        //判断是否能投标
        $bank_id = service("Financing/Project")->getTenantIdByPrj($info);
        if(!$moneyAccountId){
            $moneyAccountId = $bank_id;
        }
        //投资金额
//        $money = numberFormat($money);//单位分
        $trans_key = 'fast_cash_buy'.$prjId.$uid;
        $remaining_cachekey = "buyremain_".$prjId;
        $doing_count = false;
        $cache_back = false;

        $db = null;
        $prj_service = service('Financing/project');
        $prj_model = D("Financing/Prj");
        $prj_order_model = D("Financing/PrjOrder");
        $payAccountService = service("Payment/PayAccount");

        Import("libs.Counter.MultiCounter", ADDON_PATH);
        $doing_cachekey = "buydoing_con".$prjId;
        $prj_cachekey_counter = MultiCounter::init(Counter::GROUP_PRJ_COUNTER."_con", Counter::LIFETIME_THISMONTH);
        try{
            //项目剩余可投金额
            $remaining_money = $prj_service->buyFirewall($money,$uid,$prjId,$title,$moneyAccountId,$params,$isMobile);
            $doing_count = true;

            $cache_back = true;// 这个不可移动，需要根据这标来判断是否需要回滚cache计算器
            if($remaining_money <= 0){
                throw_exception("剩余可投资金额不足，投资失败，请下次再来!");
            }

            //奖励金额
            $award = (isset($params['award']) && ($params['award']> 0)) ? $params['award']:0;
            //真实扣款金额
            $realMoney = $money-$award;

            $prjInfo = $prj_model->where(array("id"=>$prjId))->find();
            $act_prj_ext = M('prj_ext')->find($prjId);

            $db = new BaseModel();
            $db->startTrans($trans_key);
            $reward_type = (int)$params['reward_type'];
            //这里是新的使用奖励的调用(红包、满减券、加息券)
            if ($reward_type > 0) {
                $reward_service = service("Financing/UseReward");
                $requestId = $reward_service->createRequestId(array(
                    'uid' => $uid,
                    'prjId' => $prjId,
                    'timeLimitDay' => $prjInfo['time_limit'],
                    'amount' => $money,
                ));
                //项目名称加上项目类型
                $show_prj_name = D("Financing/Prj")->get_show_prj_name($prjInfo['prj_type'], $prjInfo['prj_name']);
                $will_reward_data = $reward_service->freezeReward($uid, $prjId, $prjInfo['time_limit_day'], $money,
                    $requestId, $show_prj_name, $params);
            }
            $a_coupon_money = -1;

            $awardCheck = $prj_service->parseAward($params, $uid, $a_coupon_money);
            $reward_money = 0;
            if ($awardCheck === false) {
                throw_exception(MyError::lastError());
            } else {
                list($reward_money, $invest_reward_money, $coupon_money) = $awardCheck;
            }
            $invest_reward_money = 0;
            $freeze_amount = 0;
            D("Financing/UserBonusFreeze");
            if ($reward_type > 0 && $reward_type != UserBonusFreezeModel::TYPE_RATE) {
                $freeze_amount = $will_reward_data['freezeAmount'];
            }
            $params['invest_reward_money'] = $freeze_amount;
            $realMoney = $money - $freeze_amount - $reward_money;
            //如果全部使用了现金红包 并且使用了奖励
            if ($realMoney < 0) {
                $reward_money = $params['reward_money'] = $money - $freeze_amount;
            }
            $realMoney = $money - $freeze_amount - $reward_money;
            $sync_data_to_order = array(
                'rate' => $prjInfo['rate'],
                'rate_type' => $prjInfo['rate_type'],
                'year_rate' => $prjInfo['year_rate'],
                'time_limit' => $prjInfo['time_limit'],
                'time_limit_unit' => $prjInfo['time_limit_unit'],
                'time_limit_day' => $prjInfo['time_limit_day'],
                'cash_level' => $act_prj_ext['cash_level'],
            );
            //写入订单
            $result = $prj_order_model->addOrder($uid,$prjId,$money,0,$prjInfo,$params);
            if(!$result){
                throw_exception(MyError::lastError());
            }
            list($orderNo,$buyOrderId)= $result;
            $prj_service->syncOrderData($buyOrderId, $sync_data_to_order);



            $objType = PayAccountModel::OBJ_TYPE_PAYFREEZE;

            $free_money_tmp = $realMoney + $reward_money + $freeze_amount;

            //免费提现额度
            $free_money = $payAccountService->giveFreeMoney($uid, $free_money_tmp, $freeze_amount);
            //免费体现额度根据期限来给,之前都是给10%.
            //速兑通项目剩余天数 X≥270 210≤X＜270 150≤X＜210 90≤X＜150 X＜90
            //免费提现额度 100% 75% 50% 25% 20%
            $free_money_rate = $this->giveFreeMoneyRate($prjInfo);
            if(!$free_money_rate){
                throw_exception("免费体现额度计算失败,请跟踪错误~~~");
            }
            $free_money = $free_money*$free_money_rate;
            //冻结金额
            $payResult = $payAccountService->freezePay(
                $uid,
                $orderNo,
                $realMoney,
                $reward_money,
                $invest_reward_money,
                0,
                $title,
                "",
                $moneyAccountId,
                $objType,
                $buyOrderId,
                $free_money
            );
            if(!$payResult['boolen']){
                throw_exception($payResult['message']);
            }
            //支付单号
            $payOrderNo = $payResult['payorderno'];

            //判断是否满标
            if($remaining_money == 1 && cache($remaining_cachekey) == 1){
                $model->where(array("id"=>$prjId))->save(array(
                        "bid_status"=>PrjModel::BSTATUS_FULL,
                        "full_scale_time"=>time(),
                        "mtime"=>time()
                    ));
            }

            $con = $model->where(array(
                    "id"=>$prjId,
                    "remaining_amount"=>array(
                        "egt", $money
                    )
                ))->setDec("remaining_amount",$money);

            if(!$con) {
                throw_exception("剩余可投资金额不足，投资失败，请下次再来!");
            }

            //保存奖励数据
            $rewardData=array();
            $rewardData['reward_type'] = $params['reward_type'];
            $rewardData['reward_money'] = $reward_money;
            $rewardData['invest_reward_money'] = $freeze_amount;
            if ($rewardData['reward_type']) {
                $rewardData['reward_id'] = $params['reward_id'];
                $now = time();
                $rewardData['prj_order_id'] = $buyOrderId;
                $rewardData['ctime'] = $now;
                $rewardData['mtime'] = $now;
                M("prj_order_ext")->add($rewardData);
            }

            if($reward_type > 0){
                $params['accoutType'] = $will_reward_data['accoutType'];
                $params['rate'] = $will_reward_data['rate']*10;
            }
            //同步关联订单状态
            D("Financing/UserBonusFreeze")->updateFreezeOrderId($requestId, $buyOrderId, $params);

            $db->commit($trans_key);
            $prj_service->descDoingCountCache($prjId, $doing_count);
            //推荐是否投资
            M("user_recommed")->where(array("uid"=>$uid))->save(array("is_invest"=>1,"mtime"=>time()));
            //投资完毕以后的通知
            service("Financing/AfterInvestTask")->notice($uid,$buyOrderId);

            //入投资队列 加入报警
            queue(
                'fast_cash_buy',
                $buyOrderId,
                array(
                    'uid'=> $uid,
                    'prj_id'=> $prjId,
                    'money'=> $money,
                    'buy_order_id'=> $buyOrderId,
                    'pay_order_no'=> $payOrderNo,
                    'free_money'=> $free_money,
                ),
                '',
                '',
                '',
                1
            );

            //满标
            if($remaining_money == 1 && cache($remaining_cachekey) == 1){
                $aid = M('prj_fast_cash')->where(array('prj_id'=>$prjId))->getField('id');
                $set_complete_res = $this->set_fast_cash_prj_complete($aid,$uid);
                if(!$set_complete_res['status']){
                    throw_exception($set_complete_res['message']);
                }
            }

            // 时间间隔检查结束
            if($is_check_time_interval && isset($srvTimerBuy)) {
                $srvTimerBuy->end();
            }
        }catch (Exception $e){
            if($db) $db->rollback($trans_key);
            $prj_service->descDoingCountCache($prjId, $doing_count);
            if($cache_back){
                try{
                    $prj_service->buyCacheRollback($prjId, $money);
                }catch (Exception $e){
                    MyError::add($e->getMessage());
                    return false;
                }
            }
            //冻结红包
            if ($requestId && $freeze_amount > 0) {
                //使用队列进行解冻通知
                $params['uid'] = $uid;
                $params['memo'] = $e->getMessage();
                $params['requestId'] = $requestId;
                queue("unfreeze", $requestId, $params);
            }
            MyError::add($e->getMessage());
            return false;
        }

        return $buyOrderId;
    }
    
    /**
     * 根据项目的期限来决定投资项目给的免费体现额度
     * 速兑通项目剩余天数 X≥270 210≤X＜270 150≤X＜210 90≤X＜150 X＜90
       免费提现额度 100% 75% 50% 25% 20%
     */
    public function giveFreeMoneyRate($info){
        return 1;
        $time_limit = (int)$this->showTimeLimitSdt(time()-24*3600,$info['last_repay_date'],true);
        if($time_limit>=270){
            return 1;
        }
        if($time_limit>=210 && $time_limit<270){
            return 0.75;
        }
        if($time_limit>=150 && $time_limit<210){
            return 0.5;
        }
        if($time_limit>=90 && $time_limit<150){
            return 0.25;
        }
        if($time_limit<90){
            return 0.2;
        }
    }

   /**
    * 获取剩余到期资产价值
    * @param type $surplus_amount
    * @param type $cash_money 单位分
    * @param type $payable_interest 单位分
    */
    public function getLeftTimeMoney($prj_order_id,$cash_money,$payable_interest)
    {
        $where = array(
            'id'=>$prj_order_id
        );
        $field = array("surplus_amount");
        $surplus_amount = D("Financing/PrjOrder")->where($where)->getField($field);
        if ($surplus_amount < $this->_config['min_can_cash_money']){
            throw_exception("该订单不能变现");
        }

        return numberFormat($surplus_amount - $cash_money - $payable_interest);
    }

    /**
     * 显示速兑通的期限问题
     * @param type $start_time_stamp
     * @param type $end_time_stamp
     * @param type $is_filter_html
     */
    public function showTimeLimitSdt($start_time_stamp, $end_time_stamp, $is_filter_html = false)
    {
        if($start_time_stamp>=$end_time_stamp){
             //throw_exception("期限已到");
            return "0天";
        }
        $res = day_diff($start_time_stamp, $end_time_stamp);
        $return = "<em class='gred_num'>{$res}</em>天";
        //如果过滤html
        if($is_filter_html){
            return strip_tags($return);
        }
        return $return;

    }

    /**
     * 显示速度兑通的信息
     * 显示几个月几天，暂时先放着
     */
    public function showTimeLimitSdt1($start_time_stamp,$end_time_stamp,$is_filter_html = false)
    {
        if($start_time_stamp>=$end_time_stamp){
             //throw_exception("期限已到");
            return "0天";
        }
        $res = month_diff($start_time_stamp, $end_time_stamp);
        $months = $res[0];
        $days = $res[1];
        if (!$months)
        {
            $return =  "<em class='gred_num'>{$days}</em>天";
        }
        if (!$days)
        {
            $return = "<em class='gred_num'>{$months}</em>个月";
        }
        if ($months && $days)
        {
            $return = "<em class='gred_num'>{$months}</em>个月零<span class='show_days'>{$days}</span>天";
        }
        //如果过滤html
        if($is_filter_html){
            return strip_tags($return);
        }
        return $return;
    }

    /**
     * 设置速兑通项目截标
     * 不处理金额只处理状态，金额放到异步中处理
     * @param $id
     * @param $uid
     * @return array
     */
    public function set_fast_cash_prj_complete($id,$uid)
    {
        $res = array(
            'status'=>0,
            'message'=>'',
        );

        $db = new BaseModel();
        $prj_order_model = D('Financing/PrjOrder');
        $fast_cash_model = D('Financing/FastCash');
        $prj_model =  D('Financing/Prj');
        $time = time();
        $trans_key = 'fast_cash_prj_complete_'.$id.$uid;

        try {
            $fast_cash_info = $this->get_prj_cash_info($id);

            if($fast_cash_info['status'] != self::STATUS_START){
                throw_exception('该变现已经不能进行截标操作');
            }

            $prj_id = $fast_cash_info['prj_id'];
            $prj_info = $this->get_prj_info($prj_id);
//            if($prj_info['bid_status'] != PrjModel::BSTATUS_BIDING){
//                throw_exception('该项目已经不能进行截标操作');
//            }

            //项目状态和变现申请状态
            $status = PrjModel::BSTATUS_CANCEL;//默认的项目状态是取消
            $fast_cash_status = self::STATUS_CANCEL;//变现状态

            //三种不同的截标状态
            if($prj_info['remaining_amount'] == 0){//满标
                $status = PrjModel::BSTATUS_FULL;
                $fast_cash_status = self::STATUS_FULL;
            }elseif($prj_info['demand_amount'] > $prj_info['remaining_amount']){//有人投标的话项目状态改为 截标
                $status = PrjModel::BSTATUS_END;
                $fast_cash_status = self::STATUS_END;
            }

            $db->startTrans($trans_key);

            //变现申请真实变现金额、真实变现消耗金额以及变现申请状态更新
            $fast_cash_data = array(
                'mtime'=>$time,
                'status'=>$fast_cash_status,
            );
            $r = $fast_cash_model->where(array('id'=>$id))->save($fast_cash_data);
            if(!$r){
                throw_exception('变现状态更新失败');
            }
            if($status != $prj_info['bid_status']){
                //项目状态更新
                $prj_data = array(
                    'bid_status' => $status,
                    "full_scale_time"=>$time,
                    "mtime"=>$time
                );
                $r = $prj_model->where(array('id'=>$prj_id))->save($prj_data);
                if(!$r){
                    throw_exception('项目状态更新失败');
                }
            }

            //取消
            if($status == PrjModel::BSTATUS_CANCEL){
                $prj_order_model
                    ->where(array('id' => $fast_cash_info['prj_order_id']))
                    ->setDec('cash_times', 1);
            }

            $db->commit($trans_key);
            $res['status']=1;

            //入截标队列，延迟5秒执行
            queue(
                'fast_cash_prj_complete',
                $id,
                array(
                    'uid'=> $uid,
                    'id'=> $id,
                ),
                '',
                5,
                '',
                1
            );


        } catch (Exception $exc) {
            $db->rollback($trans_key);
            $res['message']=$exc->getMessage();
        }
        return $res;
    }

    /**
     * 获取速兑通基本信息
     */
    public function getSdtInfo($prjId)
    {
        $base_info = array(
            '借款用途' => '借款人资金周转临时需求',
            '还款来源' => '借款人以其已经在平台上持有的债权或其他任何资产权益，作为其履行还款义务的保障。',

        );
        $prjInfo = service("Financing/Project")->getPrjInfo($prjId);
        if(!$prjInfo){
             throw_exception("项目信息不存在,请刷新后重试!");
        }
        //速兑通直接退出，另一个方法处理 add by 001181
        $res = service("Financing/Invest")->getBaseInfoSDT($prjInfo);
        $prjInfo = $res['p_prj_info'];
        $subInfo = $res['sub_prj_info'];
        $subInfo['ratePhone'] = service("Financing/Financing")->getRateData($subInfo);
        $userInfo = $res['p_prj_userinfo'];


        //原始项目信息
        $pinfo['项目名称'] = $subInfo['prj_name'];
        $pinfo['项目编号'] = $subInfo['prj_no'];
        $pinfo['投资期限'] = strip_tags($subInfo['time_show'][0]);
        $pinfo['投资金额'] = moneyView($prjInfo['money']);
        $pinfo['投资收益率'] =strip_tags($subInfo['ratePhone']['total_year_rate']);
        $pinfo['还款方式'] = $subInfo['repay_way_name'];
        //$pinfo['保障措施'] = $subInfo['addcredit_desc'];
        $pinfo['保障措施'] = $subInfo['safeguards_name2'];
        $pinfo['__URL__'] = $prjInfo['p_prj_id'];


        //借款人信息
        $uinfo['用户名'] = $userInfo['uname'];
        $uinfo['注册时间'] = date("Y-m-d",$userInfo['ctime']);
        $uinfo['姓名'] = $userInfo['real_name'];
        $uinfo['身份证'] = person_id_view_prefix($userInfo['person_id']);



         $extension = array(
            '借款人原始投资信息'=>$pinfo,
            '借款人信息'=>$uinfo,
        );
        return array(
            'base_info'=>convertDictToArray($base_info),//基本信息
            'extension'=>convertDictToArray($extension),//原始项目信息
        );
    }

    public function load_trade_permonth_class($name,$debug=false)
    {
        import_addon( "trade.Permonth.".$name );
        $class =  new $name();
        $class->debug = $debug;
        return $class;
    }

    function sdt_prj_order_can_cash($uid, $orderId){
    	$config = $this->_config;
    	$time = time();
    	$today = strtotime(date('Y-m-d', $time));

    	/**
    	 * 是否可变现的条件，临时屏蔽几条，不然没数据，最后都要打开
    	*/
    	$where = array(
    			'fi_prj_order.id' => $orderId,
    			'fi_prj_order.mi_no' => self::$api_key,
    			'fi_prj_order.uid' => $uid,
    			'fi_prj_order.is_transfer' => 1,//是否可转让
    			'fi_prj_order.surplus_amount' => array('EGT',$config['min_can_cash_money']),//剩余可变现金额大于等于100可变
    			'fi_prj_order.cash_times' => array('LT',$config['max_cash_times']),//小于等于一个订单最多变现次数
    			'fi_prj_order.cash_level' => array('LT',$config['cash_level_limit']),//小于等于变现层级限制
    			'fi_prj.end_bid_time' => array('LT',$today-86400*$config['min_have_days']),//截标时间小于当前时间-项目最少持有天数
    			'fi_prj.last_repay_date' => array('GT',$today+86400*$config['min_surplus_days']),//最终还款日小于当前时间+项目最少剩余天数
    			'fi_prj.repay_way' => array('IN',implode(',',$this->_open_repay_way)),//当前速兑通支持的还款方式
    	);

//        var_dump($config);exit;

    	//有提现限制的话，提前还款项目的订单不能变现
    	if($config['cash_limit']){
            //不能是提前还款
    		$where['fi_prj_ext.is_early_repay'] = array(
    				array('EXP',' is NULL'),
    				array('NEQ',1),
    				'OR',
    		);
    		//不能是展期项目
    		$where['fi_prj_ext.is_extend'] = array(
    				array('EXP',' is NULL'),
    				array('NEQ',1),
    				'OR',
    		);
    	}

    	$prj_oder_model = M('prj_order');

    	$join = array(
    			'fi_prj ON fi_prj_order.prj_id = fi_prj.id',
    			'fi_prj_ext ON fi_prj_order.prj_id = fi_prj_ext.prj_id',
    	);

    	$total = $prj_oder_model->join($join)->where($where)->count();
    	return $total;
    }
    
    /**
     * 获取速兑通统计信息
     */
    public function getStatistics() {
        $cache_key = "fastcash_info";
        $info = S($cache_key);
        if(empty($info) || !$info){
            $info = array(
                'count'=>0,
                'sum_money_days'=>0.00,
                'count_days'=>0,
            );
        }
        return array(
                'count' => $info['PRJ_CNT'],
                'sum_money_days' => $info['SUCC_AMT_7DAYS'],
                'count_days' => $info['SUCC_PRJ_CNT_7DAYS'],
            );
    }
    
    /**
     * 获取最大的投资金额
     */
    public function getMaxInvestMoney($min_bid_amount,$step_amount,$remaining_amount,$amount){
        //一、项目剩余可投金额大于起投金额情况:
        //1、账户余额小于起投金额，显示“您当前账户可用余额不足”；
        //2、账户余额大于起投金额小于剩余可投，根据投资递增金额显示；
        //根据：起投金额是X，投资递增金额是Y，账户余额Z
        //X+nY<Z<X+(n+1)Y,则显示X+nY；
        if($remaining_amount>$min_bid_amount){
            if($amount<$min_bid_amount){
                $this->error = "您当前账户可用余额不足";
                return false;
            }
            if($amount>$min_bid_amount && $amount<$remaining_amount){
                $amount_a = floor($amount/$min_bid_amount);
                $amount_b = floor(($amount- ($amount_a*$min_bid_amount))/$step_amount);
                return $min_bid_amount*$amount_a+$amount_b*$step_amount;
            }
            if($amount>$remaining_amount){
                return $remaining_amount;
            }
            return $amount;
        }
        //二、项目剩余可投金额小于起投金额情况:
        //1、账户余额小于项目剩余可投金额，显示“您当前账户可用余额不足”；
        //2、账户余额大于项目剩余可投金额，显示显示项目剩余可投金额。
        if($remaining_amount<=$min_bid_amount){
            if($amount<$remaining_amount){
                $this->error = "您当前账户可用余额不足";
                return false; 
            }
            if($amount>$remaining_amount){
                return $remaining_amount;
            }
            return $amount;
        }
        
    }

    /**
     * 变现申请处理方法
     * @param array $params
     * @param int $cash_check
     * @param int $consume
     * @return array
     */
    public function add_submit($params=array(),$cash_check=1,$consume=0)
    {
        checkArrayData(
            $params,
            array(
                'prj_id',
                'prj_order_id',
                'sdt_prj_name',
                'cashRate',
                'cashAmount',
                'uid',
            )
        );

        $prj_id = $params['prj_id'];
        $prj_order_id = $params['prj_order_id'];
        $sdt_prj_name = $params['sdt_prj_name'];
        $cash_rate = $params['cashRate'];
        $cash_money = $params['cashAmount'];
        $uid = $params['uid'];

        if(
            $prj_id == 0 ||
            $prj_order_id == 0 ||
            $cash_rate == 0 ||
            $cash_money == 0 ||
            empty($sdt_prj_name)
        ){
            throw_exception('提交数据有误，请检查后重试');
        }

        $key = "fastcash_addsubmit_".$prj_order_id;
        import_addon("libs.Counter.SimpleCounter");
        $fastcash_counter = SimpleCounter::init(Counter::GROUP_FASTCASH_COUNTER, Counter::LIFETIME_THISI);

        //事务
        $db = null;
        $trans_key = 'fast_cash_buy'.$prj_order_id.$uid;

        try {
            $fastCount = $fastcash_counter->incr($key);
            if($fastCount > 1) throw_exception("您操作太过频繁了，请等大概一分钟左右再变现");

            if($cash_check){
                //判断是否可以变现
                $this->is_cash_money($prj_order_id, $uid, $cash_money,$cash_rate);
            }

            $fast_cash_trade = $this->get_trade_obj($prj_id,self::PRJ_TYPE_NAME);

            $mi_no = BaseModel::getApiKey('api_key');

            if($consume <= 0){
                //变现消耗金额
                $consume = $fast_cash_trade->get_curr_cash_consume(
                    $prj_order_id,
                    $uid,
                    bcmul($cash_money, '100', 0),
                    $cash_rate/100
                );
            }

            //获取当前的最新的协议的ID
            $protoService = service('Financing/Protocol');
            $conf = $protoService->getProtocolConf();
            $tplid = $conf['sdtview'];
            $protocol_id = $protoService->getLastProtocolId($tplid);

            //单位转换为分
            $post_money = (int) bcmul($cash_money, '100', 0);

            //事务开始
            $db = new BaseModel();
            $db->startTrans($trans_key);

            //变现记录信息
            $fast_cash_data = array(
                'uid' => $uid,
                'ctime' => time(),
                'prj_order_id' => $prj_order_id,
                'plan_money' => $post_money,//单位(分)
                'money' => $post_money,//单位(分)
                'year_rate' => $cash_rate*10,
                'plan_consume' => $consume,
                'consume' => $consume,
                'status' => FastCashService::STATUS_PENDING,
                'mi_no' => $mi_no,
                'protocol_id' => $protocol_id,
                'protocol_active' => 1,
            );
            $fast_cash_id = M('prj_fast_cash')->add($fast_cash_data);
            if(!$fast_cash_id){
                throw_exception('变现申请失败，请稍候重试(1)');
            }
            //变现后要把消耗的钱减掉
            $res_surplus = M('prj_order')->where(array(
                'id'=>$prj_order_id,
                'uid'=>$uid,
            ))->setDec("surplus_amount",$consume);
            if($res_surplus == false){
                throw_exception('数据处理失败，请刷新后重试(2)');
            }

            //事务提交
            $db->commit($trans_key);

            //审核入队列，加入报警
            $queue_res = queue(
                'fast_cash_review',
                $fast_cash_id,
                array(
                    'uid'=> $uid,
                    'prj_order_id'=> $prj_order_id,
                    'fast_cash_id'=> $fast_cash_id,
                    'sdt_prj_name'=> $sdt_prj_name,
                    'cash_check' => $cash_check,
                ),
                '',
                '',
                '',
                1
            );

            if(!$queue_res){
                throw_exception('数据处理失败，请刷新后重试(3)');
            }

            //计数器
            $fastcash_counter->desc($key);

            return array('fast_cash_id'=>$fast_cash_id,'status'=>1);
        } catch (Exception $exc) {
            if($db) $db->rollback($trans_key);
            $fastcash_counter->desc($key);
            return array('info' => $exc->getMessage(),'status'=>0);
        }
    }

}

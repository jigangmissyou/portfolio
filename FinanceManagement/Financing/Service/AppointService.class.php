<?php

class AppointService extends BaseService{

    private $appoint_model;
    
    protected $_config = array();

    const CANCEL_APPOINT_QUEUE_NAME = "appoint_cancel";

    const TYPE_INVEST = 1;//投资触发检查是否取消预约
    const TYPE_CASHOUT = 2;//提现触发检查是否取消预约
    const TYPE_EXPIRE = 3;//时间过期触发检查是否取消预约

    const MAX_APPOINT_MONEY = 10000000;//预约金额最大不能超过金额10万元

    function __construct()
    {
        parent::__construct();
        $this->appoint_model = D('Financing/Appoint');
        try {
            $this->_config = $this->load_config();
        }
        catch (Exception $e) {
           return false;
        }
    }
    
    /**
     * 获取配置文件
     */
    public function load_config(){
        $path =  SITE_PATH."/cli_server/app/config/";
        $file_name = "appoint.php";
        $file = $path.$file_name;
        if(!is_readable($file)){
            throw_exception("请配置{$file_name}或者保持配置{$file_name}可读~");
        }
        return require_once $file;
    }
    /**
     * 返回配置文件的数值 
     * @return type
     */
    public function getConf(){
        return $this->_config;
    }

    //获取oracle 统计数据
    function getAppointOracle($time = 110)
    {
        $cache_key = 'appointinfo';
        $ret = S($cache_key);
        $uid_count = D("Financing/Appoint")->getCurrentEnableCount();
        if (!$ret) {
            $ret = array(
                'prj_type' => '日益升',
                'succ_amount' => 0,
                'succ_count' => 0,
                'remaining_amount' => 0,
                'success_yield' => 0,
                'avg_time' => 0,
                'avg_time_limit_day' => 8,
                'month_rate' => 0,
                'min_avg_rate' => "4.00%",
                'max_avg_rate' => "20.00%",
                'time_limit_min' => 1,
                'time_limit_max' => 24,
            );
        }
        $ret['uid_count'] = $uid_count;
        return $ret;
        
    }

    /**
     * 申请预约
     * @param array $params
     * @param array $check_array
     */
    public function applyAppoint(array $params,$check_array=array())
    {
        if(!empty($check_array)){
            checkArrayData(
                $params,
                $check_array
            );
        }
        try {
            $data = $this->checkData($params);
        } catch (Exception $e) {
            $this->error = $e->getMessage();
            return false;
        }

        $aid = $this->appoint_model->addAppoint($data);

        $job_id = queue('appoint_user', $aid . "_" . time(), array(
            'uid' => $data['uid'],
            'aid' => $aid
        ));

        if($job_id){
            //申请预约消息发送
            $this->appoint_send_message_success($data);
        }
        return $aid;
    }

    public function checkData(array $params,array $data)
    {
        D('Financing/Prj');
        //选择预约的类型
        $serAppoint = service('Financing/Appoint');
        //系统允许预约的
        $prj_type_list = $serAppoint->getAppointProjectTypeList();
        //用户选择的
        $user_select_prj_type = array();
        //选择了月益升
        if ($params['is_xyb'] == 1) {
            array_push($user_select_prj_type, PrjModel::PRJ_TYPE_F);
        }
        //选择了年益升
        if ($params['is_qyr'] == 1) {
            array_push($user_select_prj_type, PrjModel::PRJ_TYPE_B);
        }
        //选择速兑通
        if ($params['is_sdt'] == 1) {
            array_push($user_select_prj_type, PrjModel::PRJ_TYPE_H);
        }
        //选择了日益升
        if ($params['is_rys'] == 1) {
            array_push($user_select_prj_type, PrjModel::PRJ_TYPE_A);
        }

        //有效的选择(取两个数组的交集)
        $select_type = array_intersect($prj_type_list, $user_select_prj_type);

        if (!count($select_type)) {
            throw_exception('必须选择有效的预约项目类型');
        }

        if(!$params['appoint_rate']){
            throw_exception("请填写大于0的利率");
        }

        if(!$params['is_agree_agreement']){
            throw_exception("请同意预约管理规则");
        }
        if($params['appoint_money'] < 1000*100){
            throw_exception('最小预约金额不能少于1000元');
        }
        //并且递增金额是1000的倍数
        if ($params['appoint_money'] % (1000 * 100) > 0) {
            throw_exception('预约金额只能是1000元的倍数');
        }
        //已经预约的金额
        $appointed_money = $data['appoint_money'] - $data['appoint_remaining_money'];
        if ($appointed_money < 0) {
            throw_exception('当前预约异常,请取消预约后重新发起');
        }
        $user_total_amount = $this->getUserTotalAmount($params['uid']);
        if($params['appoint_money'] > $user_total_amount+$appointed_money){
            throw_exception('预约金额不能大于自己的账户余额');
        }
        //预约金额不能超过10万元
        if ($params['appoint_money'] > self::MAX_APPOINT_MONEY) {
            $appoint_money_limit = self::MAX_APPOINT_MONEY/1000000;
            throw_exception("预约投资额度最高可设置{$appoint_money_limit}万元，请重新设置");
        }
        //check appoint_day提交是否合法
        $appoint_day = $this->checkAppointDay((int)$params['appoint_day']);
        if (!$appoint_day) {
            throw_exception('请提交合法的预约天数');
        }

        unset($params['is_agree_agreement']);
        unset($params['id']);
        return $params;
    }

    /**
     * @param $appoint_day
     * 检查数据提交的合法性
     */
    private function checkAppointDay($appoint_day)
    {
        $appoint_day_arr = array(
            30,
            60,
            90,
            180,
            365,
        );
        if (!in_array($appoint_day, $appoint_day_arr)) {
            return false;
        }
        return true;
    }

    private function getUserTotalAmount($uid)
    {
        $serviceObj = service("Payment/PayAccount");
        $account = $serviceObj->getBaseInfo($uid);
        return ($account['amount'] + $account['reward_money'] + $account['invest_reward_money'] + $account['coupon_money']);
    }

    /**
     * @param $uid
     * @param $amount
     * @param $is_ok 是否确认了
     * @param $type
     * @param $bak
     * @return bool
     * 金额减少并且余额不足预约剩余金额的时候,并且还是未匹配的状态
     * 触发时机
     * 1)提现
     * 2)投资－>
     *
     */
    public function amount_reduce($uid, $amount = 0, $is_ok = true, $type,$obj_id)
    {
        //查找用户是否有未匹配的预约
        $mod = D("Financing/Appoint");
        $id = $mod->is_has_appoint($uid);
        if (!$id) {
            return false;
        }
        //查询预约信息
        $info = $mod->getAppointDataById($id,$uid);
        $appoint_money = $info['appoint_money'];//预约金额
        $current_appoint_money = $info['appoint_remaining_money'];//剩余可预约金额
        //有预约，查询预约的条件
        $total_amount = $this->getUserTotalAmount($uid);
        $total_amount = $total_amount - $amount;
        if ($total_amount < $appoint_money) {
            if ($is_ok) {
                //进队列通知
                $queue_data = array(
                    'uid' => $uid,
                    'type' => $type,
                    'aid' => $id,
                    'obj_id' => $obj_id,
                );
                queue(self::CANCEL_APPOINT_QUEUE_NAME, $id, $queue_data);
            }
            return array('total_amount' => $total_amount,'appoint_money' => $appoint_money, 'appoint_remaining_money' => $current_appoint_money, 'is_has_appoint' => $id, 'status' => 1);
        }
        return array('total_amount' => $total_amount,'appoint_money' => $appoint_money, 'appoint_remaining_money' => $current_appoint_money, 'is_has_appoint' => $id, 'status' => 0);
    }

    /**
     * 预约项目端数据入队列
     * @param $prj_id
     * @param $prj_type
     * @return bool|int
     */
    public function appoint_project($prj_id, $prj_type) {
        D('Financing/Prj');
        if ($this->isCanAppoint($prj_type,$prj_id)) {
            return queue('appoint_project', $prj_id, array('prj_id' => $prj_id));
        }
        return false;
    }

    /**
     * 获取预约项目类型列表
     */
    public function getAppointProjectTypeList(){
        //判断是否存在 
        $config = $this->getConf();
        $prj_type_list = array();
        if($config['prj_type']){
            $prj_type_list = explode(",", $config['prj_type']);
        }
        return $prj_type_list;
    }

    /**
     * 项目类型是否能预约(进入预约队列)
     */
    public function isCanAppoint($prj_type, $prj_id)
    {
        //查询当前项目是否能预约
        if ($prj_type == PrjModel::PRJ_TYPE_H) {
            $this->error = "速兑通暂不能预约";
            return false;
        }
        $field = array(
            'prj_id',
            'is_appoint_prj',
        );
        $prj_ext_info = M("prj_ext")->where(array('prj_id' => $prj_id))->field($field)->find();
        if (!$prj_ext_info) {
            $this->error = "信息不存在";
            return false;
        }
        return $prj_ext_info['is_appoint_prj'] == 1 ? true : false;

    }
    
    /**
     * 根据用户提交的类型拼接出查找条件
     */
    public function getTypeListStringByPhalcon($prj_type_string){
        if(empty($prj_type_string)){
            return false;
        }
        $prj_type_list = explode(",",$prj_type_string);
        $str = "(";
        foreach($prj_type_list as $v){
            $str .= "'{$v}',";
        }
        $str = trim($str,",").")";
        
        return $str;
    }

    /**
     * 预约成功发送消息
     * @param array $params
     * @return mixed
     */
    public function appoint_send_message_success(array $params)
    {
        $SendMoney = humanMoney($params['appoint_money'], 2, false) . '元';
        $SendRate = number_format($params['appoint_rate'] / 10, 2) . '%';
        $SendAppointDays = $params['appoint_day']."天";
        $uname = M("user")->where(array("uid"=>$params['uid']))->getField("uname");
        //预约成功的站内信
        return service("Message/Message")->sendMessage(
            $params['uid'],
            1,
            115,
            array($SendMoney,$SendRate,$SendAppointDays,$uname,$this->getAppointType($params)),
            0,
            1,
            array(1,2,3),
            true
        );
    }
    /**
     * 根据用户选择,合并项目名称 
     * @param type $data
     * @param type $sub_fix
     * @param type $last_sub_fix
     * @return string
     */
    public function getAppointType($data,$sub_fix = "、",$last_sub_fix = "和"){
        $arr = array();
        if ($data['is_qyr']) {
            array_push($arr, "B");
        }
        if ($data['is_rys']) {
            array_push($arr, "A");
        }
        if ($data['is_xyb']) {
            array_push($arr, "F");
        }
        if ($data['is_sdt']) {
            array_push($arr, "H");
        }
        //先排个序
        sort($arr);
        $prj_names = array();
        $prj_type_list = D("Financing/Prj")->getPrjNameList();
        foreach($arr as $v){
            if($prj_type_list[$v]){
                array_push($prj_names,$prj_type_list[$v] );
            }
        }
        $last = array_pop($prj_names);
        $prj_type_string = trim(trim(implode($sub_fix,$prj_names),$sub_fix).$last_sub_fix.$last,$last_sub_fix);
        return $prj_type_string;
    }
    
     /**
     * 根据用户选择,合并项目名称 
     * @param type $prj_types
     * @return string
     */
    public function getAppointSelected($record)
    {
        $appointRecord = array(
            'rys' => $record['is_rys'],
            'xyb' => $record['is_xyb'],
            'qyr' => $record['is_qyr'],
            'sdt' => $record['is_sdt'],
        );
        return $appointRecord;
    }

    /**
     * 手动取消预约(废除)
     * @param $aid
     * @param $uid
     * @return bool
     */
    public function appoint_send_message_cancle($aid,$uid)
    {
        if($this->appoint_model->cancleAppoint($aid,$uid)){
            $apply_time = date('Y-m-d');
            $uname = M("user")->where(array("uid"=>$uid))->getField("uname");
            service("Message/Message")->sendMessage($uid,1,116,array($uname,$apply_time),0, 1, array(1,2,3), true);
            return true;
        };
        return false;
    }

    /**
     * 预约匹配成功发送消息
     * @param $prj_id
     * @param $uid
     * @return mixed
     */
    public function appoint_send_message_match_success($prj_id,$uid)
    {
        $prj_name=M("prj")->where(array("id"=>$prj_id))->getField("prj_name");
        $uname = M("user")->where(array("uid"=>$uid))->getField("uname");
        return service("Message/Message")->sendMessage(
            $uid,
            1,
            117,
            array($prj_name,$uname),
            0,
            1,
            array(1,2,3),
            true
        );
    }

    /**
     * 获取当前匹配的项目列表
     */
    public function getAppointMatchList($appoint_id,$uid)
    {
        $model = D('AppointOrder');
        $where = array(
            'fi_appoint_order.appoint_id' => $appoint_id,
            'fi_prj_order.uid' => $uid,
        );
        $join = array(
            'fi_prj_order on fi_appoint_order.prj_order_id = fi_prj_order.id',
            'fi_prj on fi_prj.id = fi_prj_order.prj_id',
        );
        $field = array(
            "fi_prj.prj_name",
            "fi_prj_order.id"=>"prj_order_id",
            "fi_prj_order.prj_id"=>'prj_id',
            "fi_prj_order.money"=>'invest_money',
            "fi_prj_order.ctime"=>'invest_time',
        );
        $list = $model
            ->field($field)
            ->where($where)
            ->join($join)
            ->select();

        if($list){
            foreach ($list as &$v) {
                $v['invest_money'] = humanMoney($v['invest_money'],2,false)."元";
                $v['invest_time'] = date("Y-m-d H:i:s",$v['invest_time']);
            }
            return $list;
        }
        return array();
    }


    /**
     * 修改预约
     */
    public function editAppoint($aid,$uid)
    {
        !$aid && throw_exception("参数有误");
        $mod = D("Financing/Appoint");
        $data = $mod->getAppointDataById($aid,$uid);
        $data['appoint_money'] = $data['appoint_money']/100;
        $data['appoint_rate'] = $data['appoint_rate']/10;
        return $data;
    }

    /**
     * 修改预约
     */
    public function doEditAppoint($post_data, $aid,$uid)
    {
        !$aid && throw_exception("参数有误");
        $mod = D("Financing/Appoint");
        $data = $mod->getAppointDataById($post_data, $aid,$uid);
        return $data;
    }


    public function pushCheckAppointCancelInvest($queue_name, $uid, $obj_id, $type = AppointModel::APPOINT_CANCEL_CODE_3,$is_appoint_invest = 0)
    {
        $params['type'] = $type;
        $params['uid'] = $uid;
        $params['obj_id'] = $obj_id;
        $mod = D("Financing/Appoint");
        $appoint_id = $mod->is_has_appoint($params['uid']);
        if (!$appoint_id) {
            return true;
            //throw_exception("该用户没有任何有效的预约");
        }
        //如果是自动预约购买的 不需要进行检查
        if ($is_appoint_invest) {
            return true;
        }
        //如果用户是在匹配中,而且是待还款,也不需要检查取消
        if ($this->checkCurrentAppointRemindAmount($appoint_id)) {
            return true;
        }
        $info = $mod->getAppointDataById($appoint_id,$uid);
        $appoint_money = $info['appoint_remaining_money'];//剩余可预约金额
        $total_amount = $this->getUserTotalAmount($uid);
        //如果用户的总金额大于当前的金额，不需要check了,直接剔除队列,不进行是否需要取消的操作
        if ($total_amount >= $appoint_money) {
            return true;
        }
        $params['aid'] = $appoint_id;
        $tkey = $type . "_" . $obj_id;
        $ret = queue($queue_name, $tkey, $params);
        if (!$ret) {
            return true;
            //throw_exception($queue_name . " push error");
        }
        return true;
    }


    /**
     * 是否在匹配并且未还款
     */
    public function checkCurrentAppointRemindAmount($appoint_id)
    {
        $where = array(
            'id' => $appoint_id,
            'is_enable' => 1,
        );
        $res = $this->appoint_model->where($where)->field("appoint_remaining_money")->find();
        if(!$res){
            return false;
        }
        return $res['appoint_remaining_money'] == 0 ? true : false;
    }

    /**
     * 子任务的消费
     */
    public function procCheckAppointCancelTask($params)
    {
        $uid = $params['uid'];
        $obj_id = $params['obj_id'];
        $type = $params['type'];
        try {
            $mod = D("Financing/Appoint");
            $id = $mod->is_has_appoint($uid);
            if (!$id) {
                //直接剔除队列
                return true;
            }
            $is_ok = true;
            $money = 0;
            switch ($type) {
                //提现
                case AppointModel::APPOINT_CANCEL_CODE_2:
                    $money=M('cashout_apply')->where(array('id'=>$obj_id))->getField("money");
                    $money > 0 ? $money : 0;
                    break;
                //投资
                case AppointModel::APPOINT_CANCEL_CODE_3:
                    $order_where = array(
                        'uid' => $uid,
                        'id' => $obj_id,
                    );
                    $field = array('id', 'money');
                    $order_info = D("Financing/PrjOrder")->where($order_where)->field($field)->find();
                    $money = $order_info['money'];
                    break;
            }
            $this->amount_reduce($uid, $money, $is_ok, $type, $obj_id);
            return true;
        } catch (Exception $e) {
            return true;
        }

    }

    /**
     * 更新当前可投资的剩余额度
     */
    public function updateAppointLeftAmount($prj_id)
    {
        if (!$prj_id) {
            return false;
        }
        $cache_key = "AppointLeftAmount_" . $prj_id;
        //获取当前的投资额度。然后更新到缓存中
        $current_appoint_prj_ration = $this->_getAppointLeftAmount($prj_id);
        return cache($cache_key, $current_appoint_prj_ration);
    }


    /**
     * 获取当前项目的通过预约投资的进度
     */
    public function checkCanAppointByLeftAmount($prj_id, $invest_money)
    {
        $cache_key = "AppointLeftAmount_" . $prj_id;
        //获取当前的投资额度。然后更新到缓存中
        if (cache($cache_key) === false) {
            return true;
        }
        $left_appoint_money = cache($cache_key);
        //如果投资金额大于限定额度。不能预约
        if ($invest_money > $left_appoint_money) {
            return false;
        }
        return true;

    }

    public function getAppointLeftAmount($prj_id)
    {
        return $this->_getAppointLeftAmount($prj_id);
    }

    /**
     * 通过投资额度。获取预约的总数，然后减掉通过预约成功投标的就是项目剩余可预约金额了
     * @param $prj_id
     * @return int
     */
    protected function _getAppointLeftAmount($prj_id)
    {
        //可预约额度的金额
        $prj_ext_info = D("Financing/Prj")->getPrjExtInfo($prj_id);
        $appoint_prj_ratio = $prj_ext_info['appoint_prj_ratio'];
        //已经预约的金额
        $appoint_sum_money = M('appoint_order')->where(array('prj_id' => $prj_id))->sum("money");
        //项目的总金额
        $demand_amount = M("Prj")->where(array('id' => $prj_id))->getField("demand_amount");
        //预约的额度总金额
        $prj_appoint_demand_amount = ($demand_amount * $appoint_prj_ratio)/100;
        return $prj_appoint_demand_amount - $appoint_sum_money <= 0 ? 0 : $prj_appoint_demand_amount - $appoint_sum_money;
    }

    public function isTiggerAppointTips($uid,$money)
    {
        $appoint_model = D('Financing/Appoint');
        $appoint_id = $appoint_model->is_has_appoint($uid);
        //没有有效的预约
        if (!$appoint_id) {
            return true;
        }

        $is_ok = false;
        $amount = $money * 100;
        $res = $this->amount_reduce($uid, $amount, $is_ok, 1);
        $total_amount = $res['total_amount'];//可用余额
        $appoint_money = $res['appoint_money'];//用户预约的总金额
        $appoint_remaining_money = $res['appoint_remaining_money'];//当前可预约的自由金额
        if ($appoint_remaining_money == 0) {
            return true;
        }
        $appoint_money_view = humanMoney($appoint_money, 2, false);
        $message = "您自动预约投资金额{$appoint_money_view}元，因您本次操作后余额不足，将取消预约";
        //剩余金额
        $leftMoney = ($total_amount - $amount) > 0 ? ($total_amount - $amount) : 0;
        if ($leftMoney < $appoint_remaining_money) {
            $this->error = $message;

            return false;
        }

        return true;




    }


    /**
     * 修改预约追加金额的时候，需要重新入队列(在tkey中加入版本号,tkey=appoin_id+_+time())
     */
    public function rePushUserAppoint($appoint_id, $uid)
    {
        $job_id = queue('appoint_user', $appoint_id . "_" . time(), array(
            'uid' => $uid,
            'aid' => $appoint_id,
        ));
        return $job_id;
    }

    /**
     * 是否需要显示自动投标按钮也链接
     */
    public function isViewAutoIcon($prj_type,$uid)
    {
        return 0;
        if ($prj_type != 'A') {
            return 0;
        }
        if ($this->appoint_model->is_has_appoint($uid)) {
            return 0;
        }
        $set_appoint_limit = $this->appoint_model->getAppointLimit();
        $current_enable_count = $this->appoint_model->getCurrentEnableCount();
        if ($set_appoint_limit <= $current_enable_count) {
            return 0;
        }

        return 1;
    }



    /**
     * 钱不够\有效期到了自动取消
     * @param $uid
     * @return mixed
     */
    public function autoCancleAppoint($uid, $type = AppointModel::APPOINT_CANCEL_CODE_3)
    {
        $uname = M("user")->where(array("uid" => $uid))->getField("uname");
        switch ($type) {
            //手动取消
            case AppointModel::APPOINT_CANCEL_CODE_1:
                $mtype = 116;
                $apply_time = date('Y-m-d');
                $content_data = array($uname, $apply_time);
                $send_conf = array(1, 2, 3);
                break;
            //提现导致资金不足取消
            //投资其他项目导致资金不足取消
            //项目预约时候取消(系统取消)
            case AppointModel::APPOINT_CANCEL_CODE_2:
            case AppointModel::APPOINT_CANCEL_CODE_3:
            case AppointModel::APPOINT_CANCEL_CODE_4:
                $mtype = 119;
                $content_data = array("账户余额不足");
                $send_conf = array(2);
                break;
            case AppointModel::APPOINT_FAIL:
                $mtype = 212;
                $content_data = array($uname);
                $send_conf = array(2);
                break;
        }
        return service("Message/Message")->sendMessage($uid, 1, $mtype, $content_data, 0, 0, $send_conf, true);
    }

}

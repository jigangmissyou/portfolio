<?php

class PaymentService extends BaseService
{
    const TYPE_STOCK_TRADING = 'stock_trad';//股交中心
    const TYPE_XIANXIA = 'xianxia';//线下
    const TYPE_SHENFUT = 'shenfut';//盛付通
    const TYPE_GUOFUBAO = 'guofubao';//国付宝
    const TYPE_YIBATONG = 'yibatong';//易八通
    const TYPE_YILIAN = 'yilian';//易联
    const TYPE_YILIANH5 = 'yilianH5';//易联H5
    const TYPE_LIANLIAN = 'lianlian';//连连
    const TYPE_LIANDOING = 'liandong';//联动优势
    const TYPE_YIBAO = 'yibao';//联动优势
    const TYPE_YIBAOAPI = 'yibaoApi';//联动优势
    const TYPE_ZHESHANG = 'zheshang';//浙商

    private $type;

    public $payment;

    public static $typeText = array(
        self::TYPE_XIANXIA => '线下',
        self::TYPE_SHENFUT => '盛付通',
        self::TYPE_GUOFUBAO => '国付宝',
        self::TYPE_STOCK_TRADING => '股交中心',
        self::TYPE_YIBATONG => '易八通',
        self::TYPE_YILIAN => '易联',
        self::TYPE_YILIANH5 => '易联H5',
        self::TYPE_LIANLIAN => '连连',
        self::TYPE_LIANDOING => '联动优势',
        self::TYPE_YIBAO => '易宝支付',
        self::TYPE_YIBAOAPI => '易宝支付',
        self::TYPE_ZHESHANG => '浙商'
    );

    /**
     * 超过此金额 会自动转到客服跟踪
     * 单位是分 默认1000000
     * @var type
     */
    public static $alarmCashoutAmount = 500000;

    /**
     * 超过此金额 不能提现
     * 单位是分 默认10000000000 一亿
     * @var type
     */
    public static $maxCashoutAmount = 10000000000;

    /**
     * 总行列表
     * @var type
     */
    public static $bankList = array(
        array('code' => 'CCB', 'name' => '建设银行'),
        array('code' => 'CMB', 'name' => '招商银行'),
        array('code' => 'ICBC', 'name' => '工商银行'),
        array('code' => 'BOC', 'name' => '中国银行'),
        array('code' => 'ABC', 'name' => '农业银行'),
        array('code' => 'BOCOM', 'name' => '交通银行'),
        array('code' => 'CMBC', 'name' => '民生银行'),
        array('code' => 'HXBC', 'name' => '华夏银行'),
        array('code' => 'CIB', 'name' => '兴业银行'),
        array('code' => 'SPDB', 'name' => '浦东发展银行'),
        array('code' => 'CGB', 'name' => '广发银行'),
        array('code' => 'CITIC', 'name' => '中信银行'),
        array('code' => 'CEB', 'name' => '光大银行'),
        array('code' => 'PSBC', 'name' => '中国邮储银行'),
        array('code' => 'BOBJ', 'name' => '北京银行'),
//        array('code'=>'SDB',  'name'=>'深圳发展银行'),
        array('code' => 'BOS', 'name' => '上海银行'),
        array('code' => 'SRCB', 'name' => '上海农商银行'),
        array('code' => 'SZPAB', 'name' => '平安银行'),
        array('code' => 'HZB', 'name' => '杭州银行'),
        array('code' => 'URCB', 'name' => '杭州联合银行'),
        array('code' => 'NBCB', 'name' => '宁波银行'),
        array('code' => 'EGBANK', 'name' => '恒丰银行'),
        array('code' => 'CBHB', 'name' => '渤海银行'),
        array('code' => 'HSBANK', 'name' => '徽商银行'),
        array('code' => 'CZBANK', 'name' => '浙商银行'),
        array('code' => 'HKBEA', 'name' => '东亚银行'),
        array('code' => 'CCQTGB', 'name' => '重庆三峡银行'),
        array('code' => 'WOORI', 'name' => '友利银行'),
        array('code' => 'CCFCCB', 'name' => '城市商业银行'),
        array('code' => 'CRCB', 'name' => '农村商业银行'),
        array('code' => 'CRCPB', 'name' => '农村合作银行'),
        array('code' => 'CVBF', 'name' => '村镇银行'),
        array('code' => 'CUCC', 'name' => '城市信用合作社'),
        array('code' => 'CRCC', 'name' => '农村信用合作社'),
    );

    public function init($type) {
        if (empty($type)) {
            throw_exception("没有初始化支付类型");
        }

        $class = ucfirst($type) . "Payment";
        if (!class_exists($class, false)) {
            throw_exception($class . "异常，不存在该支付类型");
        }
        $p = new $class();
        $paymentService = new PaymentService();
        $paymentService->type = $type;
        $paymentService->payment = $p;
        return $paymentService;
    }

    //获取支付类型
    public function typeText() {
        if (!$this->type) {
            throw_exception("还未初始化");
        }
        return isset(self::$typeText[$this->type]) ? self::$typeText[$this->type] : $this->type;
    }

    /**
     * 支付提交跳转
     * @param unknown_type $amount 金额
     * @param unknown_type $uid 充值用户id
     * @param unknown_type $is_repay_in 是否是还款充值（只有融资商户还款时才用）
     * $out_account_no 和 $out_account_id只有线下类型用 $paymentParams线下用
     * @return unknown
     */
    public function submit(
        $amount,
        $uid,
        $sub_bank = '',
        $is_repay_in = 0,
        $out_account_no = '',
        $out_account_id = '',
        $paymentParams = array(),
        $is_mobile = 0,
        $param = array()
    ) {//没有事物
        if (!$this->type) {
            throw_exception("还未初始化");
        }
        if ($this->type == 'shenfut' && $is_mobile) {
            throw_exception("该充值方式已经下架");
        }
        $userAccount = D("Payment/PayAccount")->find($uid);
        $user = M('user')->find($uid);
        if ($user['uid_type'] == 2) {
            //创客融自动扣款即使是企业也不拦截
            if ((($this->type != 'xianxia' && $this->type != 'yibaoapi') || $is_mobile) && !isset($param['ckr_auto_recharge'])) {
                throw_exception("企业的充值暂时还不支持");
            }
            if ($uid != 1395528 && $this->type == 'shenfut' && $amount < 5000000) {
                throw_exception("充值金额低于最低充值金额50000元");
            }
        } else {
            if ($userAccount['is_merchant'] != '1') {
                if ($this->type != self::TYPE_STOCK_TRADING && $amount < (service('Payment/PayAccount')->getMinMoney() * 100)) {
                    throw_exception("充值金额低于最低充值金额" . service('Payment/PayAccount')->getMinMoney() . "元");
                }
            }
        }
        if ($userAccount['status'] != 1) {
            throw_exception("账户并非活跃状态");
        }

        $user = M('user')->find($uid);
        if ($is_mobile && $user['is_id_auth'] && (($param['realName'] && $param['realName'] != $user['real_name'])
                || ($param['personNo'] && $param['personNo'] != $user['person_id']))
        ) {
            throw_exception("传入的实名信息和用户的实名信息不一致");
        }
        if ($is_mobile && !$user['is_id_auth']) {
            if (!$param['realName']) {
                throw_exception("请传入实名信息");
            }
            if (!$param['personNo']) {
                throw_exception("请传入身份证号");
            }
            $hasUser = M('user')->where(array(
                "person_id" => $param['personNo'],
                "uid_type" => 1,
                "is_id_auth" => 1,
                "mi_no" => BaseModel::getApiKey('api_key')
            ))->find();
            if ($hasUser) {
                throw_exception("该身份证号已经被其他用户注册了");
            }
        }
        $special_user = M('user_ext')->where(array("uid" => $uid))->getField("special_user");
        if ($is_mobile && $special_user == 1) {
            throw_exception("盖标用户不能通过手机充值");
        }
        if ($this->type != self::TYPE_XIANXIA && $special_user == 1) {
            throw_exception("盖标用户只能转账充值");
        }
        if ($this->type == self::TYPE_XIANXIA || $this->type == self::TYPE_STOCK_TRADING) {
            $user = M("User")->find($uid);
            if (!$is_repay_in && $user['uid_type']!=2 && $user['is_xia_recharge'] != 1 && $paymentParams['is_xia_recharge'] != 1) {
                throw_exception("线下充值功能已经下架");
            }

            $ticket['uid'] = $uid;
            $ticket['amount'] = $amount;
            $ticket['sub_bank'] = $sub_bank;
            $ticket['out_account_no'] = trim($out_account_no);
            $ticket['out_account_id'] = $out_account_id;
            $ticket['paymentParams'] = $paymentParams;
            $ticket['is_repay_in'] = $is_repay_in;
            $ticket['is_mobile'] = 1;
            $param['device'] = $paymentParams['device'];
            if ($is_mobile) {
                if (C('APP_STATUS') == 'product' && $amount < 100000) {
                    throw_exception("1000元起充");
                }
                $result = $this->payment->requestPayMobile($ticket, $uid, $param);
            } else {
                $result = $this->payment->requestPay($ticket, $uid, $param);
            }
            return $result;
        } else {
            $type_channel = $this->type;
            if(isset($paymentParams['zsXianXia']) && $paymentParams['zsXianXia'] == 1 ){
                $type_channel = self::TYPE_ZHESHANG . 'x';
            }
            $ticket = D('Payment/Ticket')->init($amount, 0, $uid, $type_channel, $sub_bank, $paymentParams, $is_repay_in,
                $out_account_no, $out_account_id);
            if (!$ticket) {
                throw_exception("初始化异常");
            }
            if ($this->type == 'zheshang') {
                $param = array_merge($paymentParams, $param);
            }
            if(!isset($param['zsXianXia'])){
                if ($is_mobile) {
                    $result = $this->payment->requestPayMobile($ticket, $uid, $param);
                    $ticket_data['id'] = $ticket['id'];
                    $ticket_data['is_mobile'] = 1;
                    //联动充值失败记录失败信息
                    if ($this->type == 'liandong' && $result['boolen'] == 0) {
                        $ticket_data['call_back_str'] = $result['message'];
                    }
                    D('Payment/Ticket')->save($ticket_data);
                } else {
                    $result = $this->payment->requestPay($ticket, $uid, $param);
                }
            }else{
                $transfer_params['bindSerialNo'] = $user['zs_bind_serial_no'];
                $transfer_params['ticket_no'] = $ticket['ticket_no'];
                $transfer_params['amount'] = $ticket['amount'];
                $result = $this->payment->moneyTransfer($transfer_params);
                $result['out_id'] = $result['data']['value']['transferSerialNo'];
                $result['boolen'] == 1 && $this->callBackNotifyDeal($ticket, $result);
            }
            if ($this->type == "zheshang") {
                $this->callBackNotifyDeal($ticket, $result);
                $result['ticket_id'] = $ticket['id'];
            }

            return $result;//array('boolen'=>1, 'message'=>'xx',....)
        }
    }

    //解析前端回调的参数
    public function parseReturnParam($request, $is_mobile = 0) {
        if (!$this->type) {
            throw_exception("还未初始化");
        }
        if ($is_mobile) {
            return $this->payment->parseReturnMParam($request);
        }//return $ticket;
        else {
            return $this->payment->parseReturnParam($request);
        }//return $ticket;
    }

    //前端回调
    public function callBackReturn($ticket_no, $uid, $is_mobile = 0) {
        if (!$this->type) {
            throw_exception("还未初始化");
        }
        sleep(1);
        $where['ticket_no'] = $ticket_no;
        $ticket = D('Payment/Ticket')->where($where)->find();
        if (!$ticket) {
            throw_exception("系统异常，数据不存在");
        }
        if ($ticket['uid'] != $uid) {
            throw_exception("权限不够");
        }

        $boolen = 1;
        if ($ticket['status'] == TicketModel::STATUS_FINISHED) {
            $message = '恭喜，您已完成充值，记得立刻去投资哦！';
        } else {
            if ($ticket['status'] == TicketModel::STATUS_FAILED) {
                $boolen = 0;
                $message = '您支付失败';
                $tDesc = D('ticket')->getTDesc($ticket);
                if ($tDesc['RESP_CODE'] != '0000') {
                    $message = $tDesc['RESP_REMARK'];
                }
            } else {
                $message = '还在支付中';
            }
        }
        return array('boolen' => $boolen, 'message' => $message, 'ticket' => $ticket);
    }

    //解析后端回调的参数
    public function parseNotifyParam($request, $is_mobile = 0) {
        if (!$this->type) {
            throw_exception("还未初始化");
        }
        if ($is_mobile) {
            return $this->payment->parseNotifyMParam($request);
        }//return $ticket;
        else {
            return $this->payment->parseNotifyParam($request);
        }
    }

    public function addRechargeBankTicket(
        $ticket,
        $bank_name,
        $sub_bank,
        $sub_bank_id,
        $bank_province,
        $bank_city,
        $dUser,
        $cash_bank_id = null
    ) {
        $recharge_bank_n = M('recharge_bank_no')->where(array(
            'uid' => $ticket['uid'],
            'account_no' => $ticket['out_account_no']
        ))->find();
        if ($recharge_bank_n) {
            $ticket_d['id'] = $ticket['id'];
            $ticket_d['out_account_id'] = $recharge_bank_n['id'];
            $res = M('out_ticket')->save($ticket_d);
            if (!$res) {
                usleep(1000);
                $res = M('out_ticket')->save($ticket_d);
            }
            return $recharge_bank_n;
        }
        if ($cash_bank_id) {
            $cash_bank_no = M('fund_account')->find($cash_bank_id);
            if ($ticket['uid'] != $cash_bank_no['uid']) {
                throw_exception("绑定失败，用户和卡不是同一人");
            }
            $recharge_bank['uid'] = $cash_bank_no['uid'];
            $recharge_bank['account_no'] = $cash_bank_no['account_no'];
            $recharge_bank['channel'] = $cash_bank_no['channel'];
            $recharge_bank['acount_name'] = $cash_bank_no['acount_name'];
            $recharge_bank['bank'] = $cash_bank_no['bank'];
            $recharge_bank['bank_name'] = $cash_bank_no['bank_name'];
            $recharge_bank['sub_bank'] = $cash_bank_no['sub_bank'];
            $recharge_bank['sub_bank_id'] = $cash_bank_no['sub_bank_id'];
            $recharge_bank['bank_province'] = $cash_bank_no['bank_province'];
            $recharge_bank['bank_city'] = $cash_bank_no['bank_city'];
            $recharge_bank['is_active'] = $cash_bank_no['is_active'];
            $recharge_bank['is_init'] = $cash_bank_no['is_init'];
            $recharge_bank['is_default'] = $cash_bank_no['is_default'];
            if ($dUser['real_name'] && $dUser['person_id']) {
                $recharge_bank['real_name'] = $dUser['real_name'];
                $recharge_bank['person_id'] = $dUser['person_id'];
                $recharge_bank['mobile'] = $dUser['mobile'];
                $recharge_bank['is_bankck'] = 1;
            } else {
                $recharge_bank['real_name'] = '';
                $recharge_bank['person_id'] = '';
                $recharge_bank['mobile'] = '';
                $recharge_bank['is_bankck'] = 0;
            }
            $recharge_bank['ctime'] = time();
            $recharge_bank['mtime'] = time();
            $recharge_bank['version'] = 0;
            $recharge_bank['cashout_amount'] = 0;
            $id = service('Payment/PayAccount')->addRechargeBank($recharge_bank, $cash_bank_no['id']);
            $bank_no_id = $recharge_bank['id'] = $id;
        } else {
            $recharge_bank['uid'] = $ticket['uid'];
            $recharge_bank['account_no'] = $ticket['out_account_no'];
            $recharge_bank['channel'] = $ticket['channel'];
            $recharge_bank['acount_name'] = $ticket['out_account_no'];
            $recharge_bank['bank'] = $ticket['sub_bank'];
            $recharge_bank['bank_name'] = $bank_name;
            $recharge_bank['sub_bank'] = $sub_bank;
            $recharge_bank['sub_bank_id'] = $sub_bank_id;
            $recharge_bank['bank_province'] = $bank_province;
            $recharge_bank['bank_city'] = $bank_city;
            $recharge_bank['is_active'] = 1;
            $recharge_bank['is_init'] = 0;
            $recharge_bank['is_default'] = 0;
            if ($dUser['real_name'] && $dUser['person_id']) {
                $recharge_bank['real_name'] = $dUser['real_name'];
                $recharge_bank['person_id'] = $dUser['person_id'];
                $recharge_bank['mobile'] = $dUser['mobile'];
                $recharge_bank['is_bankck'] = 1;
            } else {
                $recharge_bank['real_name'] = '';
                $recharge_bank['person_id'] = '';
                $recharge_bank['mobile'] = '';
                $recharge_bank['is_bankck'] = 0;
            }
            $recharge_bank['ctime'] = time();
            $recharge_bank['mtime'] = time();
            $recharge_bank['cashout_amount'] = 0;
            $recharge_bank['version'] = 0;
            $id = service('Payment/PayAccount')->addRechargeBank($recharge_bank);
            $bank_no_id = $recharge_bank['id'] = $id;
        }
        if (!$bank_no_id) {
            throw_exception("充值卡绑定失败");
        }
        if ($bank_no_id) {
            $ticket_d['id'] = $ticket['id'];
            $ticket_d['out_account_id'] = $bank_no_id;
            $res = M('out_ticket')->save($ticket_d);
            if (!$res) {
                usleep(1000);
                $res = M('out_ticket')->save($ticket_d);
            }
        }
        return $recharge_bank;
    }

    public function callBackNotifyDeal($ticket, $result, $is_mobile = 0) {
        if (!$ticket) {
            throw_exception("该充值流水不存在");
        }

        $key = "Ticket_callBackNotifyDeal_" . $ticket['id'];
        Import("libs.Counter.SimpleCounter", ADDON_PATH);
        $default_counter = SimpleCounter::init(Counter::GROUP_DEFAULT, Counter::LIFETIME_THISI);
        $count = $default_counter->incr($key);
        if ($count > 1) {
            throw_exception("已经调用过，一分钟多次回调");
        }
        $ticket = M('out_ticket')->find((int)$ticket['id']);//外部传入的数据是老的会晚几秒，这样就有问题
        if ($ticket['status'] == TicketModel::STATUS_FINISHED) {
            return $result;
        }

        if ($ticket['status'] != TicketModel::STATUS_TODO) {
            throw_exception("该充值流水已经处理");
        }
        if ($result['boolen'] == 2) {//只更新回调数据
            $ticket_data['id'] = $ticket['id'];
            $ticket_data['call_back_str'] = $result['paymentParams'];
            if (!$ticket['sub_bank']) {
                $ticket_data['sub_bank'] = $result['paymentParams']['bankCode'];
            }
            D('Payment/Ticket')->save($ticket_data);
//            echo $result['result'];
            return $result;
        }

        if ($result['need_id_check']) {
            $user = M('user')->find($ticket['uid']);
            if (!$ticket['sub_bank']) {
                $ticket['sub_bank'] = $result['paymentParams']['bankCode'];
            }
            $desc = D('Payment/Ticket')->getTicketDesc($ticket['id']);
            if ($user['is_id_auth'] && (($desc['realName'] && $desc['realName'] != $user['real_name'])
                    || ($desc['personNo'] && strtoupper($desc['personNo']) != strtoupper($user['person_id'])))
            ) {
                throw_exception("传入的实名信息和用户的实名信息不一致");
            }

            $result['paymentParams']['need_id_check'] = $result['need_id_check'];

            if (!$user['is_id_auth'] && $desc['realName'] && $desc['personNo']) {
                $updata['uid'] = $ticket['uid'];
                $updata['real_name'] = $desc['realName'];
                $updata['person_id'] = $desc['personNo'];
                $updata['is_id_auth'] = 1;
                $updata['id_auth_time'] = time();
                $updata['no_person_ck'] = 1;
                $resc = D("Account/User")->updateUser($updata);
                if (!$resc['boolen']) {
                    throw_exception($resc['message']);
                } else {
                    if ($resc['dbErr']) {
                        D("Admin/AccountExceptLog")->insertLog($ticket['uid'], "Payment/Payment/callBackNotify",
                            "callBackNotify/" . $ticket['id'], "callBackNotify", $resc['dbErr']);
                        throw_exception($resc['dbMes']);
                    }
                }
                service("Account/Account")->resetLogin($ticket['uid']);
            }
        }

        $status = TicketModel::STATUS_FAILED;
        if ($result['boolen'] == 1) {
            $status = TicketModel::STATUS_FINISHED;
            if ($status == TicketModel::STATUS_FINISHED) {//增加银行卡提现额度
                $user = M('user')->find($ticket['uid']);
                if (!$ticket['sub_bank']) {
                    $ticket['sub_bank'] = $result['paymentParams']['bankCode'];
                }
                $desc = D('Payment/Ticket')->getTicketDesc($ticket['id']);
                if ($user['is_id_auth'] && (($desc['realName'] && $desc['realName'] != $user['real_name'])
                        || ($desc['personNo'] && $desc['personNo'] != $user['person_id']))
                ) {
                    throw_exception("传入的实名信息和用户的实名信息不一致");
                }

                $dUser['real_name'] = $desc['realName'];
                $dUser['person_id'] = $desc['personNo'];
                $dUser['mobile'] = $user['mobile'];

                if (!$result['need_id_check']) {
                    if (!$user['is_id_auth'] && $desc['realName'] && $desc['personNo']) {
                        $updata['uid'] = $ticket['uid'];
                        $updata['real_name'] = $desc['realName'];
                        $updata['person_id'] = $desc['personNo'];
                        $updata['is_id_auth'] = 1;
                        $updata['id_auth_time'] = time();
                        $updata['no_person_ck'] = 1;
                        $resc = D("Account/User")->updateUser($updata);
                        if (!$resc['boolen']) {
                            throw_exception($resc['message']);
                        } else {
                            if ($resc['dbErr']) {
                                D("Admin/AccountExceptLog")->insertLog($ticket['uid'], "Payment/Payment/callBackNotify",
                                    "callBackNotify/" . $ticket['id'], "callBackNotify", $resc['dbErr']);
                                throw_exception($resc['dbMes']);
                            }
                        }
                        service("Account/Account")->resetLogin($ticket['uid']);
                    }
                }

                $recharge_bank_c = null;
                if ($desc['add_recharge_bank']) {
                    $recharge_bank_c = $this->addRechargeBankTicket($ticket, $desc['bankName'], $desc['sub_bank'],
                        $desc['sub_bank_id']
                        , $desc['bank_province'], $desc['bank_city'], $dUser, $desc['cash_bank_id']);
                }
                $is_tamout = true;//添加自由额度
                if( substr($ticket['channel'],0, 8) == PaymentService::TYPE_ZHESHANG ){
                    $is_mobile = true;
                }
                if ($is_mobile && !$ticket['is_repay_in']) {
                    if ($ticket['out_account_id'] || $recharge_bank_c) {

                        if ($recharge_bank_c) {
                            $has_re = $recharge_bank_c;
                        } else {
                            $has_re = M('recharge_bank_no')->find($ticket['out_account_id']);
                        }
                        if ($has_re) {
                            $is_tamout = false;
                            $recharge_bank['id'] = $has_re['id'];
                            $recharge_bank['mtime'] = time();
                            $recharge_bank['cashout_amount'] = (int)$has_re['cashout_amount'] + $ticket['amount'];
                            $recharge_bank['version'] = $has_re['version'];
                            if (!$recharge_bank['is_bankck']) {
                                $recharge_bank['real_name'] = $dUser['real_name'];
                                $recharge_bank['person_id'] = $dUser['person_id'];
                                $recharge_bank['mobile'] = $dUser['mobile'];
                                $recharge_bank['is_bankck'] = 1;

                                $fun_bank['real_name'] = $dUser['real_name'];
                                $fun_bank['person_id'] = $dUser['person_id'];
                                $fun_bank['mobile'] = $dUser['mobile'];
                                $fun_bank['is_bankck'] = 1;
                                M('fund_account')->where(array('recharge_bank_id' => $has_re['id']))->save($fun_bank);
                            }
                            $con = service('Payment/PayAccount')->saveRechargeBank($recharge_bank);
                            if (!$con) {
                                if ($recharge_bank_c) {
                                    $has_re = $recharge_bank_c;
                                } else {
                                    $has_re = M('recharge_bank_no')->find($ticket['out_account_id']);
                                }
                                if (!$has_re) {
                                    throw_exception("并发问题，保存失败1." . M('recharge_bank_no')->getDbError());
                                }
                                $recharge_bank['id'] = $has_re['id'];
                                $recharge_bank['mtime'] = time();
                                $recharge_bank['cashout_amount'] = (int)$has_re['cashout_amount'] + $ticket['amount'];
                                $recharge_bank['version'] = $has_re['version'];
                                if (!$recharge_bank['is_bankck']) {
                                    $recharge_bank['real_name'] = $dUser['real_name'];
                                    $recharge_bank['person_id'] = $dUser['person_id'];
                                    $recharge_bank['mobile'] = $dUser['mobile'];
                                    $recharge_bank['is_bankck'] = 1;

                                    $fun_bank['real_name'] = $dUser['real_name'];
                                    $fun_bank['person_id'] = $dUser['person_id'];
                                    $fun_bank['mobile'] = $dUser['mobile'];
                                    $fun_bank['is_bankck'] = 1;
                                    M('fund_account')->where(array('recharge_bank_id' => $has_re['id']))->save($fun_bank);
                                }
                                $con = service('Payment/PayAccount')->saveRechargeBank($recharge_bank);
                                if (!$con) {
                                    throw_exception("并发问题，保存失败" . M('recharge_bank_no')->getDbError());
                                }
                            }
                        }


                    }
                }
                //增加账户提现额度
                if ($is_tamout && !$ticket['is_repay_in']) {
                    service("Payment/PayAccount")->addCashoutAmount($ticket['uid'], $ticket['amount']);
                }
            }

            $ticket_data['id'] = $ticket['id'];
            if (!$ticket['sub_bank']) {
                $ticket_data['sub_bank'] = $result['paymentParams']['bankCode'];
            }
            D('Payment/Ticket')->save($ticket_data);
        }

        D('Payment/Ticket')->recharge($ticket['id'], $status, $result['out_id'], $result['call_back_str'],
            $result['amount'], $result['paymentParams']);
        $ticket['status'] = $status;
        $ticket['out_id'] = $result['out_id'];
        $ticket['call_back_str'] = $result['call_back_str'];
        $ticket['amount'] = $result['amount'];
        $ticket['paymentParams'] = $result['paymentParams'];

        $platFee = '';
        if ($result['platFee']) {//如果有返回平台费率的话
            $platFee = $result['platFee'];
        }
        $this->otherPlatFee($ticket, $platFee);//给平台支付费率（只是把对应平台费率的流水记录下来）

        $default_counter->desc($key);
        return $result;//对应的需要返回的信息
    }

    /**
     * 支付方式后端回调接口
     * @param type $input
     * @param type $is_mobile
     * @return type
     */
    public function callBackNotify($input, $is_mobile = 0) { //有事物
        try {
            if (!$this->type) {
                throw_exception("还未初始化");
            }
            if ($is_mobile) {
                //手机支付回调
                $result = $this->payment->callBackMNotify($input);//如果是异常 需要抛异常
            } else {
                //WEB支付回调
                $result = $this->payment->callBackNotify($input);//如果是异常 需要抛异常
            }
            if ($this->type == self::TYPE_XIANXIA || $this->type == self::TYPE_STOCK_TRADING) {//线下特殊处理
                return $result;
            } else {
                $where['ticket_no'] = $result['ticket_no'];
                $ticket = D('Payment/Ticket')->where($where)->find();
                $res = $this->callBackNotifyDeal($ticket, $result, $is_mobile);

                //创客融
                service('Payment/CkrAutoRecharge')->handleCallBack($ticket, $result);
                if ($this->type == "zheshang") {
                    $user_account_amount = M('user_account')->field('amount')->find($input['uid']);
                    $user_account_amount = bcdiv($user_account_amount['amount'], 100, 2);
                    $res['availbalance'] = $user_account_amount;
                    return $res;
                }
                echo $res['result'];
            }
        } catch (Exception $e) {
            if ($this->type == "zheshang") {
                throw_exception($e->getMessage());
            }
            print_r($e->getMessage());
        }
    }

    /**
     * 处理提现申请 这个要给申请后台管理调用
     * @param unknown_type $cashoutId 提现id
     * @param unknown_type $dealUid 处理人
     * @param unknown_type $dealStatus 处理结果
     * @param unknown_type $dealReason 理由
     * @param string $channel 人工处理-三方通道的相关信息
     */
    public function cashout($cashoutId, $dealUid, $dealStatus, $dealReason, $channel = '') {//有事物
        if (!$this->type) {
            throw_exception("还未初始化");
        }
        $cashoutId = (int)$cashoutId;
        $cashoutApply = D('Payment/CashoutApply')->find($cashoutId);
        if (!$cashoutApply) {
            throw_exception("数据不存在");
        }
        if ($cashoutApply['status'] != CashoutApplyModel::STATUS_TODO && $cashoutApply['status'] != CashoutApplyModel::STATUS_DEALING) {
            throw_exception("数据已经处理" . $cashoutApply['status']);
        }
        $out_id = '';
        $cashoutModel = D('Payment/CashoutApply');
// 		$cashoutModel->startTrans();
        try {
            $result = D('Payment/CashoutApply')->deal($cashoutId, $dealUid, $dealStatus, $dealReason, $out_id, '', 1,
                $channel);
// 			$cashoutModel->commit();
        } catch (Exception $e) {
// 			$cashoutModel->rollback();
            return array('boolen' => 0, 'message' => $e->getMessage());
        }
        if ($result['boolen'] == 1 && $dealStatus == CashoutApplyModel::STATUS_SUCCESS) {
            $out_id = $this->payment->cashout($cashoutApply);//只要提现失败就抛异常
            if (!$out_id) {
                throw_exception("系统钱已经扣除，但实际提现失败，请联系技术人员(刘俊 15000944712)");
            }
        }
        return $result;
    }

    //处理提现回调
    function dealCashOutCallBack($arr, $no_apply_status = array(1 => 1, 8 => 8)) {
        $cashoutLogModel = M('cashout_log');
        $cashoutModel = D('Payment/CashoutApply');
        $where = array('cid' => $arr['requestid']);
        $loged_status = $cashoutLogModel->where($where)->getField('status');
        $status_arr = array('SUCCESS' => '付款成功', 'FAILURE' => '付款失败', 'REFUND' => '付款退回', 'UNKNOW' => '未知');
        $remark = '支付状态：' . $status_arr[$arr['status']] . ' 备注：';
        $remark = isset($arr['err_info']) ? $remark . $arr['err_info'] : $remark;
        $key = "deal_cashout_call_back_{$arr['id']}";
        $cashoutModel->startTrans($key);
        try {
            if ($loged_status === null) {
                $ret = $cashoutModel->where(array('id' => $arr['requestid']))->find();
                $real_name = M('user')->where(array('uid' => $ret['uid']))->getField('real_name');
                $data = array(
                    'cid' => $arr['requestid'],
                    'province' => $ret['bank_province'],
                    'city' => $ret['bank_city'],
                    'branchName' => $ret['sub_bank'],
                    //                'bankName' => $value->bankName,
                    'bankUserName' => $real_name,
                    'bankAccount' => $ret['out_account_no'],
                    'amount' => $arr['amount'],
                    'orderNo' => $arr['ybdrawflowid'],
                    'payStatus' => $status_arr[$arr['status']],
                    'ctime' => time(),
                    'status' => 9,
                    'remark' => $remark,
                );
                $res = $cashoutLogModel->add($data);
                unset($data);
            }
            //该提现付款成功 没处理过 status＝null 或者 没有成功处理的 status=2 需要处理一次
            //status ＝1付款成功 status＝8付款失败 都不需要再次处理

            if ($loged_status === null || (!isset($no_apply_status[$loged_status]))) {
                if ($arr['status'] == 'SUCCESS') {
                    $status = 1;
                    $data = array(
                        'status' => 1,
                        'remark' => $remark,
                        'payStatus' => $status_arr[$arr['status']],
                    );
                    $cashoutLogModel->where($where)->save($data);
                    unset($data);
                    $cashout_apply = $cashoutModel->macRollback($arr['requestid'], $status, $remark,
                        $arr['ybdrawflowid']);
                    $data = array(
                        'apply_status' => (int)$cashout_apply['boolen'],
                        'apply_remark' => $cashout_apply['message'],
                        'version' => array('exp', 'version+1'),
                    );
                    $res = $cashoutLogModel->where($where)->save($data);
                    unset($data);
                } elseif ($arr['status'] == 'FAILURE' || $arr['status'] == 'REFUND') {//包含REFUND：提现退回,
                    $cashout_apply = $cashoutModel->macRollback($arr['requestid'], 0, $remark, $arr['ybdrawflowid']);
                    $data = array(
                        'status' => 8,
                        'payStatus' => $status_arr[$arr['status']],
                        'remark' => $remark,
                        'apply_status' => (int)$cashout_apply['boolen'],
                        'apply_remark' => $cashout_apply['message'],
                        'version' => array('exp', 'version+1'),
                    );
                    $res = $cashoutLogModel->where($where)->save($data);
                } else {//UNKNOW：未知
                    $data = array();
                    $data['status'] = 2;
                    $data['payStatus'] = $status_arr[$arr['status']];
                    $data['remark'] = $remark;
                    $data['mtime'] = time();
                    $data['version'] = array('exp', 'version+1');
                    $res = $cashoutLogModel->where($where)->save($data);
                    $cashout_apply = 1;
                }
            }
            $cashoutModel->commit($key);
            return $cashout_apply;
        } catch (Exception $e) {
            $cashoutModel->rollback($key);
            return array('boolen' => 0, 'message' => $e->getMessage());
        }
    }

    //其他平台收取的费率(充值的时候)
    private function otherPlatFee($ticket, $pfee = '') { //有事物
        if (!$ticket) {
            throw_exception("调用异常，数据不存在");
        }
        D('Ticket');

        if ($pfee) {
            $Platfee = $pfee;
        } else {
            $Platfee = $this->payment->getPlatFee($ticket['amount'] + $ticket['fee']);
        }
        if ($Platfee) {//手续费
            if ($ticket['status'] != TicketModel::STATUS_FINISHED) {
                throw_exception("没有支付成功，不能扣费");
            }
            if ($ticket['in_or_out'] != TicketModel::INCOMING_FLAG) {
                throw_exception("不是充值的费率处理");
            }
            D('Payment/Ticket')->dealPlatFee($ticket['id'], $Platfee);
        }

    }

    public function getBankList() {
        return $this->payment->bankList;
    }

    public function getMyCode($bankCode) {
        return $this->payment->getMyCode($bankCode);
    }

    //查询api，先忽略
    public function findOrder($ticket) {
        return $this->payment->findOrder($ticket);//return $ticket;
    }

    //时间戳
    public function getTimestamp() {
        return $this->payment->getTimestamp();
    }

    /**
     * 检测该银行是否可以自动提现
     * @param type $bankCode 银行code
     * @param type $subBankId 支行ID
     * @param type $bankProvinceId 省ID
     * @param type $bankCityId 城市ID
     * @return boolean
     */
    public function isAutoCashoutBank($bankCode, $subBankId, $bankProvinceId, $bankCityId, $channel = 'xianxia') {
        $inBank = false;
        if (empty($subBankId) || empty($bankProvinceId) || empty($bankCityId)) {
            return $inBank;
        }

        $shenfut = new ShenfutPayment();
        $cashout_config = $shenfut->getConfig('cashout');
        /**
         * 配置参数里设置的是否打开自动提现
         */
        if (!$cashout_config['isOpenAutoCashout']) {
            return $inBank;
        }
        foreach ($shenfut->cashoutBankList as $key => $value) {
            if ($bankCode == $value['myCode']) {
                $inBank = true;
                break;
            }
        }
        if ($channel == 'yibaoApi') {
            $yibao = service('Account/AccountBank')->getCanCashOutBankList('yibaoApi');
            if ($yibao) {
                foreach ($yibao as $key => $value) {
                    if ($bankCode == $value['myCode']) {
                        $inBank = true;
                        break;
                    }
                }
            }
        }
        return $inBank;
    }

    /**
     * * 充值、提现成功数据推送给BFS系统
     * @param int $cd_sign 0充值 1 提现
     * @param string $channel 充值、提现渠道
     * @param array $arr
     */
    public function pushToBFS($cd_sign = 0, $channel = '', $arr = array()) {
        if (!in_array($cd_sign, array(0, 1)) || empty($channel) || empty($arr)) {
            return;
        }
        $real_name = M('user')->where(array('uid' => $arr['uid']))->getField('real_name');
        $bank_info = M('fund_account')->field('bank, bank_name, sub_bank')->where(array(
            'uid' => $arr['uid'],
            'account_no' => $arr['out_account_no']
        ))->find();
        $info = C('PAYMENT');
        $info = $info['bfs'][$channel];
        if ($cd_sign == 0) {
            $queue_key = $arr['ticket_no'];
            $queue_arr['serialId'] = $arr['ticket_no'];//流水号
        } elseif ($cd_sign == 1) {
            $queue_key = $arr['cashout_process_no'];
            $queue_arr['serialId'] = $arr['cashout_process_no'];//流水号
            $arr['amount'] = $arr['money'];
        }
        $queue_arr['bankAcc'] = $info['merchant'];// . '('.$arr['uid'].')';//本方账号'6226095710360000'
        $queue_arr['accName'] = $info['third_name'];//本方账号
        $queue_arr['bankName'] = $info['company'];//本方开户行'兴业银行股份有限公司嘉兴平湖支行';
        $queue_arr['oppAccNo'] = $arr['out_account_no'];//对方账号
        $queue_arr['oppAccName'] = $real_name;//对方户名
        $queue_arr['oppAccBank'] = $bank_info['bank_name'] . '(' . $bank_info['sub_bank'] . ')';//对方开户行
        $queue_arr['cdSign'] = $cd_sign;//收支标志
        $queue_arr['rbSign'] = $cd_sign + 1;//红蓝标志
        $queue_arr['cur'] = 'CNY';//币种
        $queue_arr['transTime'] = date('Y-m-d', $arr['mtime']);//交易日期
        $queue_arr['amt'] = humanMoney($arr['amount'], 2, false);//金额
        $queue_arr['amt'] = floatval($queue_arr['amt']);
        $queue_arr['bal'] = '1';//余额
        $queue_arr['abs'] = '';//摘要
        queue("push_to_bfs", $queue_key, $queue_arr);
//        $api_info = array('api_provider'=>'BFS', 'event'=>'pushToBFS', 'related_user_id'=>$arr['uid'],'other_identity'=>$arr['cashout_process_no']);
//        service('Api/HttpRequest')->request($api_info, C('BFS_API_URL'), $queue_arr, 'post');
    }


    /*
     * 浙商发送验证短信
     */
    public function sendSmscode($mobile, $SmsType) {
        return $this->payment->sendcode($mobile, $SmsType, $remark = "");
    }
    
    //提现回调,因为当前抛异常只会引起前面队列重复发起提现,所以为防止本地业务失败回滚入队,可重复处理
    public function cashOutCallBack(array $response, array $request){
        $cashout = M('cashout_apply')->field('id,status')->where(array('cashout_process_no' => $request['dataId']))->find();
        if(!in_array( $cashout['status'], array(CashoutApplyModel::STATUS_TODO, CashoutApplyModel::STATUS_MAC_DEALING))){
            echo '已经处理成功,请勿重复处理,状态值:'.$cashout['status'];
            return true;
        }
        queue('zs_cashout', $cashout['id']. '_'.time(), array('request'=>$request, 'response'=>$response));
    }

}

Import("Modules.Payment.Service.Payment.Stock_tradPayment", APP_PATH);
Import("Modules.Payment.Service.Payment.XianxiaPayment", APP_PATH);
Import("Modules.Payment.Service.Payment.ShenfutPayment", APP_PATH);
Import("Modules.Payment.Service.Payment.YibatongPayment", APP_PATH);
Import("Modules.Payment.Service.Payment.YilianPayment", APP_PATH);
Import("Modules.Payment.Service.Payment.YilianH5Payment", APP_PATH);
Import("Modules.Payment.Service.Payment.GuofubaoPayment", APP_PATH);
Import("Modules.Payment.Service.Payment.LianlianPayment", APP_PATH);
Import("Modules.Payment.Service.Payment.LiandongPayment", APP_PATH);
Import("Modules.Payment.Service.Payment.YibaoPayment", APP_PATH);
Import("Modules.Payment.Service.Payment.YibaoApiPayment", APP_PATH);
Import("Modules.Payment.Service.Payment.ZheShangPayment", APP_PATH);

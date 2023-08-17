<?php

/**
 * // +----------------------------------------------------------------------
 * // | UPG
 * // +----------------------------------------------------------------------
 * // | Copyright (c) 2012 http://upg.cn All rights reserved.
 * // +----------------------------------------------------------------------
 * // | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
 * // +----------------------------------------------------------------------
 * // | Author: kaihui.wang <wangkaihui@upg.cn>
 * // +----------------------------------------------------------------------
 * // user model
 */
class UserModel extends BaseModel
{
    protected $tableName = 'user';
    protected $loginType = "";
    const ACTIVITY_ALIAS = "ACTIVITY_ALIAS";

    const LAST_LOGIN_HEART_TIME = "LAST_LOGIN_HEART_TIME";
    const USER_LOGIN_KEY = "USER_LOGIN_KEY"; //登录session记录

    const UPG_USER_REMEMBER_LOGIN_NAME_KEY = "USER_REMEMBER_LOGIN_NAME_KEY"; //记住登录session name key
    const REGISTER_SOURCE_KEY = 'REGISTER_SOURCE_KEY';//注册渠道的session key
    const USC_TOKEN = "usc_token"; //usc token

    const USER_TYPE_PERSON = 1; //个人账户
    const USER_TYPE_CORP = 2; //企业账户
    const USER_TYPE_MANAGER = 3; //客户经理

    const FINANCE_UID_TYPE_CORP = 1; //合作方企业
    const FINANCE_UID_TYPE_PERSON = 2; //合作方个人

    const INVEST_FINANCE_TOUZI = 1; //投资身份
    const INVEST_FINANCE_RONGZI = 2; //融资身份

    /*用户审核状态*/
    const AUDIT_STATUS_INIT = 1;
    const AUDIT_STATUS_WAIT = 2;
    const AUDIT_STATUS_PASS = 3;
    const AUDIT_STATUS_FAIL = 5;

    const IS_CHANGE_NEW_BIE_TIMES = 1;
    const REG_B_CLIENT = 9;


    protected $_validate = array(
        #用户名
        array("uname", "require", "用户名不能为空!", self::EXISTS_VALIDATE),
        array("uname", "2,13", "用户名长度必须在2到13个字符!", self::EXISTS_VALIDATE, "length"),
        array('uname', 'checkUnameMake', '用户名须由字母、数字或中文组合!', self::EXISTS_VALIDATE, 'callback'),
        array('uname', 'checkUnameUnique', '用户名被占用！', self::EXISTS_VALIDATE, 'callback'),

        #密码
        array("newpassword", "require", "密码不能不能为空!", self::EXISTS_VALIDATE),
        array("newpassword", "6,16", "密码长度必须为6-16个字符!", self::EXISTS_VALIDATE, "length"),
        array('newpassword', 'checkPwdMake', '密码须由数字、字母(区分大小写)或特殊符号组合!', self::EXISTS_VALIDATE, 'callback'),
        #验证邮箱
        array("email", "require", "邮箱不能为空!", self::EXISTS_VALIDATE),
        array("email", "email", "邮箱格式错误!", self::EXISTS_VALIDATE),
        array("email", "1,99", "邮箱太长了!", self::EXISTS_VALIDATE, "length"),
        array('email', 'checkEmailUnique', '邮箱已经被占用！', self::EXISTS_VALIDATE, 'callback'),
        #验证mobile
        array("mobile", "require", "手机号码不能为空!", self::EXISTS_VALIDATE),
        array("mobile", "checkMobile", "手机号码格式错误!", self::EXISTS_VALIDATE, "function"),
        array('mobile', 'checkMobileUnique', '手机号码已经被注册！', self::EXISTS_VALIDATE, 'callback'),
        #验证身份证
        array("person_id", "require", "身份证号码不能为空!", self::EXISTS_VALIDATE),
        array("person_id", "checkPersonId", "身份证号码格式错误!", self::EXISTS_VALIDATE, "callback"),
    );

    protected $_auto = array(
        //array('password', 'pwd_md5', self::MODEL_BOTH, 'function'),
        array('newpassword', 'pwd_hash', self::MODEL_BOTH, 'function'),
//            array('register_ip','get_client_ip',self::MODEL_INSERT,'function'),
//            array('ctime','time',self::MODEL_INSERT,'function'),
        array('mtime', 'time', self::MODEL_BOTH, 'function'),
    );

    function checkPersonId($person_id) {
        import_addon("libs.My.MyIdcheck");
        $obj = new MyIdcheck($person_id);
        return $obj->isIdNum();
    }

    function checkUnameMake($uname) {
        if (!$this->checkUname($uname)) {
            return false;
        }
        if (I('request.app_version')) {
            return true;
        }
        if (I('request.client') == 'wap') {
            return true;
        }
        if (preg_match("/^[0-9]+$/", $uname)) {
            return false;
        }
        return preg_match('/^[\x{4e00}-\x{9fa5}a-zA-Z0-9]+$/iu', $uname) ? true : false;
    }

    //手机端改用户名使用的判断方法
    function checkUnameForEdit($uname) {
        if (!$this->checkUname($uname)) {
            return false;
        }
        if (preg_match("/^[0-9]+$/", $uname)) {
            return false;
        }
        return preg_match('/^[\x{4e00}-\x{9fa5}a-zA-Z0-9]+$/iu', $uname) ? true : false;
    }

    function checkUnameUnique($uname) {
        if (!$this->checkUname($uname)) {
            return false;
        }
        if (C('USC_REMOTE_SWITCH')) {
            $user = $this->checkUnameUnique_usc($uname);
            if (!$user) {
                return false;
            }
        }
        $info = $this->where(array("uname" => $uname, 'is_del' => array('elt', 10), 'mi_no' => self::$api_key))->find();
        return !$info ? true : false;
    }

    function checkUnameUnique_usc($uname) {
        import_addon("libs.Usc.UscApi");
        $uscapi = new UscApi(C('USC_TYPE'));
        $data['USERNAME'] = $uname;
        $data['PORTALCODE'] = self::$api_key;
        $result = $uscapi->regHasUserByUsername($data);
        return $result['boolen'] != 1;
    }

    function checkMobileUnique($mobile, $uid_type = 1, $is_invest_finance = 1) {
        $key_uid_type = 'check_uid_type';
        $session_uid_type = BaseModel::newSession($key_uid_type);
        if ($session_uid_type) {
            $uid_type = $session_uid_type['uid_type'];
            $is_invest_finance = $session_uid_type['is_invest_finance'];
        }

        if (C('USC_REMOTE_SWITCH')) {
            $result = $this->checkMobileUnique_usc($mobile, $uid_type, $is_invest_finance);
            if (!$result) {
                return false;
            }
        }
        $info = $this->where(array(
            "mobile" => $mobile,
            'uid_type' => $uid_type,
            'is_invest_finance' => $is_invest_finance,
            'is_del' => array('elt', 10),
            'mi_no' => self::$api_key
        ))->find();
        return !$info ? true : false;
    }

    function checkMobileUnique_usc($mobile, $uid_type = 1, $is_invest_finance = 1) {
        import_addon("libs.Usc.UscApi");
        $uscapi = new UscApi(C('USC_TYPE'));
        $data['MOBILE'] = $mobile;
        $data['PORTALCODE'] = self::$api_key;
        $data['USERTYPE'] = $uid_type == 2 ? 'E' : 'P';        //2015.1.28增加类型
        $data['IS_INVEST_FINANCE'] = $is_invest_finance == 2 ? '2' : '1';//2015325增加融资人为2,投资人为(默认为1)

        $result = $uscapi->regHasUserByMobile($data);
        return $result['boolen'] != 1;
    }

    function checkEmailUnique($email) {
        if (C('USC_REMOTE_SWITCH')) {
            return $this->checkEmailUnique_usc($email);
        }
        $info = $this->where(array("email" => $email, 'is_del' => array('elt', 10), 'mi_no' => self::$api_key))->find();
        return !$info ? true : false;
    }

    function checkEmailUnique_usc($email) {
        import_addon("libs.Usc.UscApi");
        $uscapi = new UscApi(C('USC_TYPE'));
        $data['EMAIL'] = $email;
        $data['PORTALCODE'] = self::$api_key;
        $result = $uscapi->regHasUserByEmail($data);
        return $result['boolen'] != 1;
    }

    function checkPwdMake($password) {
        $number = (int)preg_match("/^.*\d.*$/", $password);
        $word = (int)preg_match("/^.*[a-z].*$/i", $password);
        $character = (int)preg_match("/^.*\W.*$/i", $password); //特殊字符 ,非字母数字
        $result = $word + $number + $character;
        return $result > 1;
    }

    /**
     * 通过uid获取 $fields为一个字段时直接返回该字段值 否则返回一个数组
     * @param $uid
     * @param string $fields
     * @return mixed
     */
    function getByUid($uid, $fields = '') {
        $uid = intval($uid);

        $field_array = array();
        if ($fields) {
            $field_array = array_filter(explode(',', $fields));
        } else {
            $fields = '*';
        }

        $data = D('Account/User')->where(array("uid" => $uid))->field($fields)->find();
        if (count($field_array) == 1) {
            return $data[$fields];
        } else {
            return $data;
        }
    }

    /**
     * 通过手机获取
     * @param unknown $mobile
     * @return Ambigous <mixed, boolean, NULL, multitype:, unknown, string>
     */
    function getUserByMobile($mobile, $uid_type = 1, $is_invest_finance = 1) {
        // 臨時兼容客戶經理的問題
        if ($uid_type == 1) {
            $uid_type = array(1, 3);
        } else {
            $uid_type = array($uid_type);
        }

        return $this->where(array(
            "mobile" => $mobile,
            'uid_type' => array('IN', $uid_type),
            'is_invest_finance' => $is_invest_finance,
            'mi_no' => self::$api_key
        ))->find();
    }

    /**
     * 根据email获取
     * @param unknown $email
     * @return boolean
     */
    function getByEmail($email) {
        if (!$email) {
            return false;
        }
        return $this->where(array("email" => $email, 'mi_no' => self::$api_key))->find();
    }

    /**
     * 根据用户名称获取用户信息
     * @param $uname
     * @return bool|mixed
     */
    function getUserInfoByUname($uname) {
        if (!$uname) {
            return false;
        }
        return $this->where(array("uname" => $uname, 'mi_no' => self::$api_key))->find();
    }

    public function checkUname($uname) {
        $space = array(
            0x20,
            0x2000,
            0x2001,
            0x2002,
            0x2003,
            0x2004,
            0x2005,
            0x2006,
            0x2007,
            0x2008,
            0x2009,
            0x200a,
            0x200b,
            0x2028,
            0x2029,
            0x3000
        );
        foreach ($space as $c) {
            if (strpos($uname, unicode2utf8($c)) !== false) {
                return false;
            }
        }

        return true;
    }

    /**
     * 注册
     * @param unknown $uname
     * @param unknown $password
     * @param unknown $mobile
     */
    function register($uname, $password, $mobile, $regClient = '', $uid_type = 1, $is_invest_finance = 1) {
//        if(empty($regClient)){
        $regClient = $this->getFrom();
//        }

        return $this->register_usc($uname, $password, $mobile, $regClient, $uid_type, $is_invest_finance);
    }

    /**
     * 用户信息初始化
     * @param unknown $uid
     * @param unknown $password
     * @param number $isMd5
     * @param number $isXiaRecharge
     * @param string $giveHongBao (发满减劵：false屏蔽，true开启)
     * @param number $uid_type 1-个人注册 2-企业注册 其他-平台账户代理注册
     ** @param number $is_invest_finance 1-投资人 2-融资人
     * @return boolean
     */
    function userInit(
        $uid,
        $password,
        $isMd5 = 1,
        $isXiaRecharge = 2,
        $giveHongBao = true,
        $uid_type = 1,
        $ext_info = array(),
        $is_invest_finance = 1
    ) {
        $now = time();
        $user['uid'] = $uid;
        $user['is_newbie'] = 1;
        $user['is_xia_recharge'] = ($uid_type ==2)?1:2;
        M("User")->save($user);
        //添加user account 信息
        $accountData['uid'] = $uid;
        if ($isMd5) {
            $accountData['pay_password'] = pwd_md5($password);
        } else {
            $accountData['pay_password'] = $password;
        }

        unset($accountData['pay_password']);// 20160304 新用户默认为【空的支付密码】

        if ($is_invest_finance != 1) {//2015325增加如果是融资人，则is_merchant为1
            $accountData['is_merchant'] = 1;
        } else {
            $accountData['is_merchant'] = 0;
        }

        $accountData['amount'] = 0;
        $accountData['buy_freeze_money'] = 0;
        $accountData['cash_freeze_money'] = 0;
        $accountData['profit'] = 0;
        $accountData['capital'] = 0;
        $accountData['reward_cashout_times'] = 3;
        //$accountData['status'] = 1;
//         $accountData['status'] = $uid_type==1?1:3;//账户状态1.正常2.冻结3.封杀(封杀其实表示机构的未激活状态)
//        $accountData['status'] = $uid_type==2?3:1;//账户状态1.正常2.冻结3.封杀(封杀其实表示机构的未激活状态)
        $accountData['status'] = ($uid_type == 2 && $is_invest_finance == 1) ? 3 : 1;

        $accountData['ctime'] = $now;
        $accountData['mtime'] = $now;

        $userAccount = M("user_account");
        $userAccount->add($accountData);

        $user_cashout_tamount['uid'] = $uid;
        $user_cashout_tamount['cashout_amount'] = 0;
        $user_cashout_tamount['ctime'] = $now;
        $user_cashout_tamount['mtime'] = $now;
        $user_cashout_tamount['version'] = 0;
        M('user_cashout_tamount')->add($user_cashout_tamount);

        //添加user_account_summary 信息
        $dataSumary['uid'] = $uid;
        $dataSumary['will_principal'] = 0;
        $dataSumary['will_profit'] = 0;
        $dataSumary['investing_prj_count'] = 0;
        $dataSumary['investing_prj_money'] = 0;
        $dataSumary['willrepay_prj_count'] = 0;
        $dataSumary['willrepay_prj_money'] = 0;
        $dataSumary['repayed_prj_count'] = 0;
        $dataSumary['repayed_prj_money'] = 0;
        $dataSumary['ctime'] = $now;
        $dataSumary['mtime'] = $now;
        M("user_account_summary")->add($dataSumary);

        //记录用户注册时的HTTP_USER_AGENT 20150901 by 001073
        $dataAgent['uid'] = $uid;
        $dataAgent['http_user_agent'] = $_SERVER["HTTP_USER_AGENT"];
        $dataAgent['ctime'] = $now;
        $dataAgent['mtime'] = $now;
        M('register_http_user_agent')->add($dataAgent);


        $regClient = $this->getFrom();
        //B端用户到此直接返回
        if ($regClient == 9) {
            return true;
        }

        //消息数据初始化
        D('Message/Message')->initMessageConfig($uid);

        service("Account/Active")->saveRegSource($uid);

        //ios 直接都给奖励
        $activity_alias = cookie(self::ACTIVITY_ALIAS);
        if ((getFrom() == 2) && $_REQUEST['is_app']) {
            if (date("Ymd") < "20140801") {
                $activity_alias = "reg_2014_07_01";
            }
        }
        if ($activity_alias) {
            M("user_ext")->where(array("uid" => $uid))->save(array("activity_alias" => $activity_alias));
        }
        //生成userCode
        D("Account/Recommend")->addUserCode($uid);

        //企福鑫用户的继承和QRCODE码
        if (!empty($ext_info)) {
            $recommend = $ext_info['recommend'];
            $type = $ext_info['type'];
            $qrcode = $ext_info['qrcode'];
            //企福鑫继承上级的企业ID
            $companySalaryService = service("Financing/CompanySalary")->init($uid);
            if ($recommend) {
                $companySalaryService->userBindCompanyByRecommend($recommend);
            }
            //通过企业二维码加入企业计划
            if ($type && $qrcode) {
                $companySalaryService->userBindByQrcode($type, $qrcode);
            }
        }

        //2015/05 外部渠道推广链接注册
        $source = BaseModel::newSession(UserModel::REGISTER_SOURCE_KEY);
	    if ($source['channel']) {
		    $source_data['uid']=$uid;
		    $source_data['source_name']=$source['channel'];
		    $source_data['source_uid']=$source['uid'];
		    $source_data['ctime']=$now;
		    $source_data['mtime']=$now;
		    M('user_register_source')->add($source_data);
		    if($source['channel'] == 'wps'){
			    M('wps_param_bind')->add(array('uid' => $uid, 'extra' => $source['extra'], 'ctime' => time()));
		    }
	    }


	    //2016/03/08  通过session值判断是否是返利投导流过来的客户,添加到user_register_source表中
        if ($_SESSION['flt_bid_url'] && $_SESSION['flt_bid_id'] && $_SESSION['flt_uid']) {
            $source_data['uid'] = $uid;
            $source_data['source_name'] = 'fanlitou';
            $source_data['source_uid'] = $_SESSION['flt_uid'];
            $source_data['ctime'] = $now;
            $source_data['mtime'] = $now;
            M('user_register_source')->add($source_data);
        }

        //2016/01/05 用户新增奖励数据统计初始化
        service('Weixin/WeixinUser')->setBonusCount($uid);

        //送红包 2015.1.21 增加一个类型判断，如果是为2(企业)，则不送红包，其他则送红包

        $giveHongBao && $uid_type != self::USER_TYPE_CORP && !$this->rewardJucaiFu($uid) && $this->regHongbao($uid); //注册发送满减劵
//      $status = $this->sendTyj($uid);

        setMyCookie('xhh_redcode', null);
        setMyCookie('recommend', null);


        return true;
    }

    /**
     * @param $uid
     * @return bool
     * 聚财富跳出来，根据之前代码改写
     */
    public function rewardJucaiFu($uid) {
        if (self::$api_key == '1234567992') {
            $rModel = D("Application/RewardRule");
            if (I("request.is_vip") == 1) {
                $rModel->applyRewardRule($uid, RewardRuleModel::ACTION_REGISTER, "reg_zhijiesong_30");
            } else {
                $rModel->applyRewardRule($uid, RewardRuleModel::ACTION_REGISTER, "reg_zhijiesong_20");
            }

            return true;
        }

        return false;
    }

    //2014-12-26增加机构分公司员工用红包代码邀请注册的用户，送30元红包用机构代码邀请注册的用户，送30元红包
    function checkOrg() {
        $recommend = I("request.hongbaoCode", '', 'trim');
        if (empty($recommend)) {
            return false;
        }
        return M()->table('fi_recommend_code c')
            ->join('inner join fi_recommend_user u on c.recommend_user_id=u.id')
            ->where(array('c.code' => $recommend))->count()
        || M()->table('fi_user_ext e')
            ->join('inner join fi_recommend_user u on e.recommend_org_id=u.id')
            ->where(array('e.recommend_code' => $recommend))->count();
    }

    //送专享体验金
    function sendTyj($uid) {
        $recommend_red = I("request.hongbaoCode");
        $recommend_red = preg_replace('/-.*$/', '', $recommend_red);
        if ($recommend_red) {
            if ($recommend_red) {
                $fromUid = D("Account/Recommend")->getUidByUserCode($recommend_red);
                if ($fromUid && $fromUid != 1) {
                    //赠送体验金给他人
//                    service("Account/TyjBonus")->sendTyjProfitToUser($fromUid,2,$uid);
                }
            }
        }
    }

    //送红包
    function regHongbao($uid) {
        //鑫合汇端注册
        D("Account/UserBonusLog");//载入类，为后面定义常量
        $recommend_raw = I("request.recommend", "", "trim");
        $hongbaoCode = I("request.hongbaoCode", "", "trim");
        $hc = I("request.hc", "", "trim");//增加通行证对于红包code的处理
        if ($hongbaoCode || $hc) {
            $recommend_raw = $hongbaoCode ? $hongbaoCode : $hc;
        }
        $recommend = preg_replace('/-.*$/', '', $recommend_raw);
        $ext_info['recommend'] = $recommend_raw;   //$ext_info['recommend_raw']改为$ext_info['recommend']
        $ext_info['hongbaoCode'] = $hongbaoCode;
        //如果有推荐或者是红包特殊代码
        if ($recommend) {
            $this->addUserRecommed($uid, $ext_info, 1);
            //企福鑫继承上级的企业ID
            $companySalaryService = service("Financing/CompanySalary")->init($uid);
            $companySalaryService->userBindCompanyByRecommend($recommend);
            $money = (int)D("Account/UserBonusCode")->getBonusMoneyByCode(strtoupper(trim($recommend)));
        }

        //取消新注册用户送满减卷
        $fetchBonusByRegService = new \App\Modules\JavaApi\Service\FetchBonusByRegService();

        $ret = $fetchBonusByRegService->run($uid, $money);

        //写入获得的鑫手福利活动奖励
        $banner_map = array(
            'position' => 68,
            'mi_no' => self::$api_key,
            //'is_active'=>1,
            //'title' => '鑫手福利',
        );
        $activity_id = M('banner')->where($banner_map)->order('sort asc,id desc')->getField('id');

        if ($activity_id) {
            $data = array(
                'uid' => $uid,
                'award_name' => '888元满减券',
                'type' => 10,
                'activity_id' => $activity_id,
                'activity_name' => '鑫手福利',
                'activity_note' => '鑫手福利',
                'status' => 5,
                'ctime' => time(),
            );
            M('hd1_user_award')->add($data);
        }


        return $ret;
    }

    /**
     * 注册(用户中心)
     * @param unknown $uname
     * @param unknown $password
     * @param unknown $mobile
     */
    function register_web($uname, $password, $mobile, $uid_type = 1, $ext_info = array(), $is_invest_finance = 1) {
        if (!$this->checkUname($uname)) {
            return errorReturn('用户名不能有特殊空格');
        }


        if (!$this->create(['newpassword' => $password])) {
            return errorReturn($this->getError());
        }

        if (!$mobile) {
            return errorReturn("手机号码不能为空!");
        }
        if (!checkMobile($mobile)) {
            return errorReturn("手机号码格式错误!");
        }
        if (!$this->checkMobileUnique($mobile, $uid_type, $is_invest_finance)) {
            return errorReturn("手机号码已经被注册！");
        }
        if (!$this->checkUnameUnique($uname)) {
            //个人投资用户使用手机号码注册
            if ($uid_type == self::USER_TYPE_PERSON && $is_invest_finance == self::INVEST_FINANCE_TOUZI) {
                return errorReturn("手机号码已经被注册！");
            } else {
                return errorReturn("用户名已经被注册！");
            }
        }

        $newpwd = pwd_hash($password);

        if (C('USC_REMOTE_SWITCH')) {
            $uname_key = 'register_usc_' . $uname;
            Import("libs.Counter.MultiCounter", ADDON_PATH);
            $default_counter = MultiCounter::init(Counter::GROUP_DEFAULT, Counter::LIFETIME_THISI);
            $count = $default_counter->incr($uname_key);

            if ($count > 1) {
                return errorReturn('已经注册过该用户名，一分钟多次回调');
            }

            //usc 注册
            import_addon("libs.Usc.UscApi");
            $uscapi = new UscApi(C('USC_TYPE'));
            $uscdata["USERNAME"] = $uname;

            $uscdata["USERPWD"] = "";
            $uscdata["NEWUSERPWD"] = $newpwd;
            $uscdata["MOBILE_NO"] = $mobile;
            $uscdata["IDCARD_NO"] = "";
            $uscdata["SEX"] = 0;
            $uscdata['PROVINCE'] = '';
            $uscdata['CITY'] = '';
            $uscdata['AREA'] = '';
            $uscdata['ADDRESS'] = '';
            $uscdata["IF_REAL_AUTH"] = 0;
            $uscdata["IF_EMAIL_AUTH"] = 0;
            $uscdata["IF_MOBILE_AUTH"] = 1;
            $uscdata["REG_TIME"] = date('Y-m-d H:i:s');
            $uscdata['USERTYPE'] = $uid_type==2 ?'E':'P';
            $uscdata["DEPT_ID"] = null;
            $uscdata["PORTALCODE"] = self::$api_key;
            $uscdata['IS_INVEST_FINANCE'] = $is_invest_finance==2 ?2:1;//2015323用户中心增加字段区分投资人(默认为1)
//        p($uscdata);

            $uscResult = $uscapi->regUser($uscdata);

            if ($uscResult['boolen'] != 1) {
                MyError::add($uscResult['message']);

                return false;
            }
        }

        $regClient = $this->getFrom();
        $now = time();
        $data = array(
            "mobile" => $mobile,
            "uname" => $uname,
            "password" => "",
            "newpassword" => $newpwd,
            'spell_name' => $uname ? pinyin($uname) : null,
            "is_mobile_auth" => 1,
            "mobile_auth_time" => time(),
            "is_active" => 1,
            'is_set_uname' => $uid_type == self::USER_TYPE_CORP && !empty($uname) || $ext_info['set_uname'] ? 1 : 0,
            //9为B端注册
            "reg_client" => $regClient,
            'ctime' => $now,
            'mtime' => $now,
            'uid_type' => $uid_type,
            'register_ip' => get_client_ip(),
            'mi_no' => self::$api_key,
            'is_invest_finance' => $is_invest_finance,
            //2015323增加字段区分投资人(默认为1)，融资人为2
        );

        import_app("Index.Model.WebLogModel");
        $source = cookie(WebLogModel::SOURCE_NAME);
        if (!$source) {
            $source = $_REQUEST[WebLogModel::SOURCE_NAME];
        }
        if ($source) {
            $data['source'] = $source;
        }
        $data['is_app'] = I('request.is_app', '', 'int');
        $data['usc_id'] = $uscResult['data']['id'];
        $uid = $this->add($data);

        if ($uid_type == self::USER_TYPE_PERSON && empty($ext_info['set_uname'])) {
            $this->register_after($uid, $uid_type);
        }

        //2015.1.16增加机构注册
        if ($uid_type == self::USER_TYPE_CORP && $is_invest_finance == self::INVEST_FINANCE_TOUZI) {
            $proposer = I('request.proposer');
            //添加fi_user_extends_crop信息
            $datacorp['uid'] = $uid;
            $datacorp['proposer'] = $proposer == 'legal' ? 1 : 2;
            $datacorp['status'] = 1;
            //$datacorp['step'] = 0;
            $datacorp['ctime'] = $now;
            $datacorp['mtime'] = $now;
            M("user_extends_crop")->add($datacorp);

            ////添加fi_user_extends_cropext信息
            $datacorpext['uid'] = $uid;
            $datacorpext['ctime'] = $now;
            M("user_extends_cropext")->add($datacorp);
        }

        if ($uid_type == self::USER_TYPE_CORP) {
            //添加代理机构的关联标记录
            $this->addUserRecommed($uid, $ext_info);
        }

//        p($this->_sql());
//        $this->where(array("uid" => $uid))->save(array("usc_id" => $uscId));
        //用户数据初始化
        $this->userInit($uid, $password, 1, 2, true, $uid_type, $ext_info, $is_invest_finance);
        return $uid;
    }

    /**
     * 新的用户注册完成后更新用户名为'u{$uid}'
     * @param $uid
     * @param $uid_type
     */
    public function register_after($uid, $uid_type) {
        if ($uid_type == self::USER_TYPE_PERSON) {
            $new_uname = 'u' . $uid;
            $this->updateUser(array(
                'uid' => $uid,
                'uname' => $new_uname,
            ));
        }
    }

    /**
     * 注册(用户中心)
     * @param unknown $uname
     * @param unknown $password
     * @param unknown $mobile
     */
    function register_usc($uname, $password, $mobile, $regClient = 1, $uid_type = 1, $is_invest_finance = 1) {
        if (!$this->checkUname($uname)) {
            return errorReturn('用户名不能有特殊空格');
        }

        $regClient = $this->getFrom();
        $now = time();
        $data = array(
            "mobile" => $mobile,
            "uname" => $uname,
            "password" => "",
            "newpassword" => $password,
            'spell_name' => $uname ? pinyin($uname) : null,
            "is_mobile_auth" => 1,
            "mobile_auth_time" => $now,
            "is_active" => 1,
            'is_set_uname' => 0,
            'uid_type' => $uid_type,
            "reg_client" => $regClient,
            "mtime" => $now,
            "ctime" => $now,
            'mi_no' => self::$api_key,
            'is_invest_finance' => $is_invest_finance,//2015323增加字段区分投资人(默认为1)，融资人为2
        );
        import_app("Index.Model.WebLogModel");
        $source = cookie(WebLogModel::SOURCE_NAME);
        if (!$source) {
            $source = $_REQUEST[WebLogModel::SOURCE_NAME];
        }
        if ($source) {
            $data['source'] = $source;
        }
        if (isset($_REQUEST['is_app'])) {
            $data['is_app'] = intval($_REQUEST['is_app']);
        }

        if ($this->create($data)) {
            $uname_key = 'register_usc_' . $uname;
            Import("libs.Counter.MultiCounter", ADDON_PATH);
            $default_counter = MultiCounter::init(Counter::GROUP_DEFAULT, Counter::LIFETIME_THISI);
            $count = $default_counter->incr($uname_key);
            if ($count > 1) {
                MyError::add("已经注册过该用户名，一分钟多次回调");
                return false;
            }
            //usc 注册
            import_addon("libs.Usc.UscApi");
            $uscapi = new UscApi(C('USC_TYPE'));
            $uscdata["USERNAME"] = $uname;
            $uscdata["NEWUSERPWD"] = pwd_hash($password);
            $uscdata["REALNAME"] = "";
            $uscdata["EMAIL"] = "";
            $uscdata["MOBILE_NO"] = $mobile;
            $uscdata["IDCARD_NO"] = "";
            $uscdata["SEX"] = 0;
            $uscdata['PROVINCE'] = '';
            $uscdata['CITY'] = '';
            $uscdata['AREA'] = '';
            $uscdata['ADDRESS'] = '';
            $uscdata["IF_REAL_AUTH"] = 0;
            $uscdata["IF_EMAIL_AUTH"] = 0;
            $uscdata["IF_MOBILE_AUTH"] = 1;
            $uscdata["REG_TIME"] = date('Y-m-d');
            //$uscdata['USERTYPE'] = 'P';
            $uscdata['USERTYPE'] = $uid_type == 2 ? 'E' : 'P';
            $uscdata["DEPT_ID"] = null;
            //$uscdata["UID_TYPE"] = $uid_type;
            $uscdata["PORTALCODE"] = self::$api_key;
            $uscdata['IS_INVEST_FINANCE'] = $is_invest_finance == 2 ? 2 : 1;//2015323用户中心增加字段区分投资人(默认为1)，融资人为2
            $uscResult = $uscapi->regUser($uscdata);
//            print_r($uscResult);exit;
            if ($uscResult['boolen'] != 1) {
                MyError::add($uscResult['message']);
                return false;
            }
            $uscId = $uscResult['data']['id'];
            $uid = $this->add();
            // if(!$uid) {
            //     MyError::add("用户注册信息有误，请重试!");
            //     return false;
            // }
            $this->register_after($uid, $uid_type);
            $this->where(array("uid" => $uid))->save(array("usc_id" => $uscId));
            //用户数据初始化
            $this->userInit($uid, $password, 1, 2, true, $uid_type, array(), $is_invest_finance);
            return $uid;
        } else {
            MyError::add($this->getError());
            return false;
        }
    }

    /**
     * B端登录限制 个人账户暂不能登录
     * @param $account
     * @param $password
     */
    private function _login_check_bclient($account, $password) {
        $regClient = $this->getFrom();
        if ($regClient == 9) {
            return true;
        }

        if (checkMobile($account) && !$password) {
            $where = array('mobile' => $account);
            $user = $this->where($where)->field('uid_type, reg_client')->find();
            if ($user['reg_client'] == 9) {
                MyError::add('登录失败');
                return false;
            }
        }
        return true;
    }

    /**
     * 登录
     * @param unknown $account
     * @param unknown $password
     * @return boolean
     */
    function login($account, $password, $uid_type = 1, $is_invest_finance = 1) {
        $check_return = $this->_login_check_bclient($account, $password, $is_invest_finance);
        if (!$check_return) {
            return false;
        }

        if (C('USC_REMOTE_SWITCH')) {
            if (!$account) {
                MyError::add("帐号为空");
                return false;
            }
            return $this->login_usc($account, $password, '', $uid_type, $is_invest_finance);

        } else {
            //手机短信登录
            if (checkMobile($account) && (!$password)) {
                if (!$account) {
                    MyError::add("帐号或密码为空");
                    return false;
                }
            } else {
                if (!$account || !$password) {
                    MyError::add("帐号或密码为空");
                    return false;
                }
            }
            return $this->login_local($account, $password, $uid_type, $is_invest_finance);
        }
    }


    /**
     * 本地登录
     * @param unknown $account
     * @param unknown $password
     * @return boolean
     */
    function login_local($account, $password, $uid_type = 1, $is_invest_finance = 1) {
        $userInfo = $this->login_check($account, $password, $uid_type, $is_invest_finance);
        if (!$userInfo) {
            return false;
        }

        if (false === $this->checkUserAccountStatus($userInfo['is_del'], $userInfo['is_active'])) {
            return false;
        }

        return $this->doLogin($userInfo['uid']);
    }

    /**
     * * 检查用户账户的状态 字典项E155
     * @param $is_del 1-原始的冻结状态 <=10 冻结 >10 注销
     * @param $is_active
     * @return bool
     */
    private function checkUserAccountStatus($is_del, $is_active) {
        //是否激活账户
        if ($is_active == 0) {
            MyError::add("您的账户暂未激活，如有疑问，请致电400-821-8616");
            return false;
        }

        if ($is_del) {
            if ($is_del == 1) {
                MyError::add("您的账户已经被锁定，请与管理员联系解锁!");
                return false;
            } elseif ($this->checkUserisFreezed($is_del)) {
                $freeze_reason = getCodeItemName('E155', $is_del);
                MyError::add("您的账户已经被冻结，原因是：{$freeze_reason}，请与管理员联系解锁!");
                return false;
            } elseif ($this->checkUserisCanceled($is_del)) {
                MyError::add("您的账户处于注销状态，请与管理员联系!");
                return false;
            }
        }

        return true;
    }

    /**
     * 检查用户是否处于冻结状态
     * @param $is_del
     * @return bool
     */
    public function checkUserisFreezed($is_del) {
        if ($is_del > 1 && $is_del <= 10) {
            return true;
        }
        return false;
    }

    /**
     * 检查用户是否处于注销状态
     * @param $is_del
     * @return bool
     */
    public function checkUserisCanceled($is_del) {
        if ($is_del > 10) {
            return true;
        }
        return false;
    }

    /**
     * 登录处理，session赋值
     * @param unknown $uid
     */
    function doLogin($uid, $isLogin = 1, $token = '') {
        $userInfo = $this->getByUid($uid);
        if (!$userInfo) {
            MyError::add("账户不存在!");
            return false;
        }
        //金交所系统的用户不让其登录鑫合汇
        if($userInfo['special_usertype'] == '2' || $userInfo['special_usertype'] == '3'){
            MyError::add("改账户不能进行登录!");
            return false;
        }
        $key = self::USER_LOGIN_KEY;

        unset($userInfo['password']);

        $identityId = M("user_identity_relation")->where(array("uid" => $uid))->getField("identity_id", true);
        $identityNo = M("identity_lib")->where(array("id" => array('in', $identityId)))->getField('identity_no', true);
        //是否是鑫拍档
        $is_xpd = D('Account/XinPartner')->getIsXpd($uid);
        //重置登录数据时使用
        $loginUser = $this->getLoginedUserInfo();
        $loginType = isset($loginUser['login_type']) ? $loginUser['login_type'] : "";
        //获取头像信息
        $userPhoto = D("Account/UserPhoto");
        $avatar = $userPhoto->getAvatar($userInfo['uid']);
        $userInfo['avatar'] = $avatar;
        $userInfo['identity_no'] = $identityNo;
        $userInfo['usc_token'] = $token;
        $userInfo['is_xpd'] = $is_xpd;
        $userInfo['login_type'] = $this->loginType ? $this->loginType : $loginType;

        //用户身份初始化
        $this->initUserRole($userInfo);

        $domain = parseHost("http://" . $_SERVER['HTTP_HOST']);
        cookie(self::USC_TOKEN, $token, "path=/&domain=." . $domain);

        //2015.1.16号 企业注册
        // if($userInfo['uid_type']==2){
        //     $corp=M('fi_user_extends_crop')->field('org_name','status')->where(array('uid'=>$uid))->find();
        //     $userInfo['org_name']=$corp['org_name'];
        //     $userInfo['is_corp_auth']=$corp['status']==3?1:0;
        // }
        //session 赋值
        session('uid', $userInfo['uid']);
        BaseModel::setApiKey('', '', $userInfo['uid']);
        BaseModel::newSession($key, $userInfo);
        //设置用户在线
        $this->setUserOnline($uid);

        if ($isLogin) {
            //更新记录
            $data['before_logintime'] = $userInfo['last_logintime'];
            $data['last_logintime'] = time();
            $data['before_loginclient'] = $userInfo['last_loginclient'];
            $data['last_loginclient'] = visit_source();

            //写日志文件
//            $path = SITE_DATA_PATH . "/logs/Xhhfuwu/";
//            $logPath = $path . "/" . date('Y-m-d') . ".txt";
//            if (!is_dir($path)) mkdir($path, 0777, true);
//            $log_temp=array();
//            $log_temp[] = '更新登录时间,PC端登录';
//            $log_temp[] = date('Y-m-d H:i:s',$data['before_logintime']);
//            $log_temp[] = date('Y-m-d H:i:s',$data['last_logintime']);
//
//            $logstr = implode('|', $log_temp);
//            $logstr .= ";\r\n";
//            file_put_contents($logPath, $logstr, FILE_APPEND);
            //写日志文件

            $this->where(array("uid" => $uid))->save($data);

            //更新登录记录
            D("Account/LoginLog")->save($uid);

            //登陆后的监听事件 add by 001181
//            $this->listenLoginEvent($userInfo);

            BaseModel::newSession(self::LAST_LOGIN_HEART_TIME, time());
        }
        return true;
    }

    /**
     * 登陆后的监听事件列表
     * 这个是阻塞的，耗时的建议放异步处理
     * @return type
     */
    public function loginEventList() {
        return array(
            'initAllTmpRecode',//亿元红包临时记录的初始化
        );
    }

    /**
     * 登陆以后监听事件
     * add by 001181
     */
    public function listenLoginEvent($userInfo) {
        $loginEventList = $this->loginEventList();
        if (!empty($loginEventList)) {
            $loginEvent = service("Account/LoginEvent")->init($userInfo);
            $path = "data/logs/" . __FUNCTION__ . "/";
            foreach ($loginEventList as $func) {
                $rs = $loginEvent->$func();
                if (!$rs) {
                    $error = $this->getError();
                } else {
                    $error = "ok";
                }
                //不是生产环境写日志
                if (C('APP_STATUS') != 'product') {
                    $message = var_export(array('func=>' . $func, 'error=>' . $error), true);
                    Log::write($message, Log::DEBUG, '', $path . $func . ".txt");
                }
            }
        }
        return true;
    }

    /**
     * 注册送红包 去掉企福鑫注册送红包功能
     * add by 001181
     */
    public function newRegBonus($userInfo, $hook_type = 1, $amount = 0, $active_type = 0) {
        //加上并发控制，防止重复领取红包
        $uid = $userInfo['uid'];
        if ($hook_type == 1 && $active_type == 2) {
            //如果领取了注册红包就不在领取
            $has_given = D("Account/UserBonusLog")->hasGiveRegBonus($uid);
            if ($has_given) {
                //领取过了注册红包不需要在领取了.
                return true;
            }
            $key = "newRegHongBao_" . $userInfo['uid'] . "_" . $hook_type;
            import_addon("libs.Counter.SimpleCounter");
            $key_counter = SimpleCounter::init(Counter::GROUP_NEWREGHONGBAO_COUNTER, Counter::LIFETIME_THISI);
            $kcount = $key_counter->incr($key);//计数器+1
            if ($kcount > 1) {
                //check用户是否获取了红包
                return service("Account/UserBonusBehavior")->setErrorToQueue($uid, $amount, $hook_type);
            }
        }
        //根据不同的参数返回不同的service
        service("Account/RegBonusRule");
        if (!$active_type) {
            $serviceList = array(
                RegBonusRuleService::HOOK_TYPE_REG => '',
                RegBonusRuleService::HOOK_TYPE_YUNXING => '',
                RegBonusRuleService::HOOK_TYPE_LUCK => 'Account/LuckBonusRule',
                RegBonusRuleService::HOOK_TYPE_SHARE => 'Account/ShareBonusRule',
                RegBonusRuleService::HOOK_TYPE_INVEST => 'Account/FirstInvestRule',
            );
            $service_str = $serviceList[$hook_type];
        } else {
            $serviceList = array(
                1 => 'Financing/SharkRule',//摇一摇红包
                2 => 'Account/RegBonusRule',//市场部红包
                3 => 'Account/CompanySalaryRule',//企福鑫红包
                8 => 'Account/QiushouRegBonusRule',//注册红包(秋收起义定义)
            );
            $service_str = $serviceList[$active_type];
        }

        $serUserBonusBehavior = service("Account/UserBonusBehavior")->initUserInfo($userInfo, $service_str);
        //设置获取终端类型和划账
        D("Account/UserBonusLog");
        $serUserBonusBehavior->setFetchAndAccount(UserBonusLogModel::FETCH_WAY_BY_REG,
            UserBonusLogModel::ACCOUNT_TYPE_MARKET);
        $rs = $serUserBonusBehavior->newRegBonus($hook_type, $amount);
        //注册回滚以后记录没获取红包的用户
        if (!$rs && $hook_type == 1) {
            //红包发送失败以后,进入队列进行重跑
            return service("Account/UserBonusBehavior")->setErrorToQueue($uid, $amount, $hook_type);
        }
        return true;
    }

    //本地登录检查
    function login_check($account, $password, $uid_type = 1, $is_invest_finance = 1, $pwd_hash = true) {
        $md5Pwd = $pwd_hash ? pwd_md5($password) : $password;
        $userInfo = array();
        if ($password) {
            //判断是否是手机号码
            if (checkMobile($account)) {
                $userInfo = $this->where(array(
                    "mobile" => $account,
                    "is_mobile_auth" => 1,
                    'uid_type' => $uid_type,
                    'is_invest_finance' => $is_invest_finance,
                    'is_del' => array('elt', 10),
                    'mi_no' => self::$api_key
                ))->find();

                if (!is_null($userInfo['newpassword']) && !pwd_hash_verify($password, $userInfo['newpassword'])) {
                    $userInfo = '';
                } elseif (!empty($userInfo['password']) && $userInfo['password'] != $md5Pwd){
                    $userInfo = '';
                }
                $this->loginType = self::LOGIN_TYPE_MOBILE_PWD;
            } elseif (checkEmail($account)) {
                $userInfo = $this->where(array(
                    "email" => $account,
                    "is_email_auth" => 1,
                    'uid_type' => $uid_type,
                    'is_invest_finance' => $is_invest_finance,
                    'is_del' => array('elt', 10),
                    'mi_no' => self::$api_key
                ))->find();
                if (!is_null($userInfo['newpassword']) && !pwd_hash_verify($password, $userInfo['newpassword'])) {
                    $userInfo = '';
                } elseif (!empty($userInfo['password']) && $userInfo['password'] != $md5Pwd){
                    $userInfo = '';
                }
                $this->loginType = self::LOGIN_TYPE_EMAIL_PWD;
            }

            if (!$userInfo) {
                $userInfo = $this->where(array(
                    "uname" => $account,
                    'is_del' => array('elt', 10),
                    'mi_no' => self::$api_key
                ))->find();
                if (!is_null($userInfo['newpassword']) && !pwd_hash_verify($password, $userInfo['newpassword'])) {
                    $userInfo = '';
                } elseif (!empty($userInfo['password']) && $userInfo['password'] != $md5Pwd){
                    $userInfo = '';
                }
                $this->loginType = self::LOGIN_TYPE_USERNAME_PWD;
                if (!$userInfo) {
                    MyError::add("帐号或密码错误!");
                    return false;
                }

                //更新记录
                $data['before_logintime'] = $userInfo['last_logintime'];
                $data['last_logintime'] = time();
                $uid = $userInfo['uid'];
                D("Account/User")->where(array("uid" => $uid))->save($data);

                //写日志文件
//                $path = SITE_DATA_PATH . "/logs/Xhhfuwu/";
//                $logPath = $path . "/" . date('Y-m-d') . ".txt";
//                if (!is_dir($path)) mkdir($path, 0777, true);
//                $log_temp=array();
//                $log_temp[] = '更新登录时间,移动端登录';
//                $log_temp[] = date('Y-m-d H:i:s',$data['before_logintime']);
//                $log_temp[] = date('Y-m-d H:i:s',$data['last_logintime']);
//
//                $logstr = implode('|', $log_temp);
//                $logstr .= ";\r\n";
//                file_put_contents($logPath, $logstr, FILE_APPEND);
                //写日志文件
            }
        } else {
            //手机号码登录
            if (checkMobile($account)) {
                $userInfo = $this->where(array(
                    "mobile" => $account,
                    "is_mobile_auth" => 1,
                    "uid_type" => $uid_type,
                    'is_invest_finance' => $is_invest_finance,
                    'is_del' => array('elt', 10),
                    'mi_no' => self::$api_key
                ))->find();
                $this->loginType = self::LOGIN_TYPE_MOBILE_SMS;
                if (!$userInfo) {
                    MyError::add("帐号或密码错误!");
                    return false;
                }
            } else {
                MyError::add("密码不能为空!");
                return false;
            }
        }
        return $userInfo;
    }


    /**
     * 同步用户中心的用户信息到本地
     * @param unknown_type $usc_userInfo
     * @param unknown_type $sync_column
     * @return boolean|unknown  如果成功 返回 同步用户的ID,否则 返回false
     */

    function syncUserToLocal($usc_userInfo = array(), $sync_column = "") {
        //更新账户信息
        $tmp = var_export($usc_userInfo,TRUE);
        log_file("[1]".$tmp,$name='usc',$path='usc');
        if(!$usc_userInfo['id']){
            return false;
        }
        $uscId = (string)$usc_userInfo['id']; // 由于 fi_user 表中 usc_id为 varchar类型，为了确保查询时索引起效，强制转换为string.

        if (isset($usc_userInfo['username'])) {
            $data['uname'] = $usc_userInfo['username'];
        }

        if (isset($usc_userInfo['userpwd'])) {
            $data['password'] = $usc_userInfo['userpwd'];
        }


        if (isset($usc_userInfo['realname']) && $usc_userInfo['realname']) {
            $data['real_name'] = $usc_userInfo['realname'];
        }
        if (isset($usc_userInfo['realname']) && $usc_userInfo['realname']) {
            $data['spell_name'] = pinyin($usc_userInfo['realname']);
        }

        if (isset($usc_userInfo['mobileNo']) && $usc_userInfo['mobileNo']) {
            $data['mobile'] = $usc_userInfo['mobileNo'];
        }
        if (isset($usc_userInfo['ifMobileAuth'])) {
            $data['is_mobile_auth'] = (int)$usc_userInfo['ifMobileAuth'];
        }

        if (isset($usc_userInfo['email']) && $usc_userInfo['email']) {
            $data['email'] = $usc_userInfo['email'];
        }
        if (isset($usc_userInfo['ifEmailAuth'])) {
            $data['is_email_auth'] = (int)$usc_userInfo['ifEmailAuth'];
        }

        if (isset($usc_userInfo['sex'])) {
            $data['sex'] = (int)$usc_userInfo['sex'];
        }

        if (isset($usc_userInfo['ifRealAuth'])) {
            $data['is_id_auth'] = (int)$usc_userInfo['ifRealAuth'];
        }
        if (isset($usc_userInfo['idcardNo']) && $usc_userInfo['idcardNo']) {
            $data['person_id'] = $usc_userInfo['idcardNo'];
        }
        if (isset($usc_userInfo['portalCode'])) {
            $data['mi_no'] = $usc_userInfo['portalCode'];
        }
        if (isset($usc_userInfo['delFlag'])) {
            $data['is_del'] = $usc_userInfo['delFlag'];
        }


        if (isset($usc_userInfo['deptNo'])) {
            $dept = M("org_dept")->where(array("usc_dept_no" => $usc_userInfo['deptNo']))->find();
            if ($dept && $dept['id']) {
                $data['dept_id'] = $dept['id'];
            }
            if ($dept && $dept['mi_id']) {
                $data['mi_id'] = $dept['mi_id'];
            }
        }


//     	if(isset($usc_userInfo['ifEmailAuth'])) $data['email_auth_time']=time();
//     	if(isset($usc_userInfo['ifRealAuth'])) $data['id_auth_time'] = time();


        // 针对 用户名、手机、邮箱和 身份证号 处理为 "" 的情况。
        if (isset($data['uname']) && $data['uname'] == "") {
            $data['uname'] = null;
        }

        if (isset($data['mobile']) && $data['mobile'] == "") {
            $data['mobile'] = null;
            $data['is_mobile_auth'] = 0;

        }
        if (isset($data['email']) && $data['email'] == "") {
            $data['email'] = null;
            $data['is_email_auth'] = 0;
        }

        if (isset($data['person_id']) && $data['person_id'] == "") {
            $data['person_id'] = null;
            $data['is_id_auth'] = 0;
        }

        // 同步用户信息到本地,先根据 usc_id查找，如果不存在，再根据 $account对应的字段查找

        $user = $this->where(array("usc_id" => $uscId))->field('uid')->order("uid desc")->find();

        if (!$user) {
            if (empty($sync_column)) {
                $condition = array();

//    			if($data['uname'])  $condition[] = "uname='".$data['uname']."'";
                if ($data['mobile']) {
                    $condition[] = "mobile='" . $data['mobile'] . "'";
                }
                if ($data['email']) {
                    $condition[] = "email='" . $data['email'] . "'";
                }
                if (!$data['uname']) {
                    if (!empty($condition)) {
                        $sync_column = implode(' or ', $condition);
                    }
                }
            }
            if ($sync_column) {
                $user = $this->where($sync_column)->find();
            }
        }


        if (!$user) {
            //添加
            if ($this->getUserInfoByUname($data['uname'])) {
                MyError::add("账号信息同步失败，账号已经存在!");
                return false;
            }
            if ($this->getUserByMobile($data['mobile'], $usc_userInfo['uid_type'],
                $usc_userInfo['is_invest_finance'])
            ) {
                MyError::add("账号信息同步失败，手机号已经存在!");
                return false;
            }
            $data['register_ip'] = get_client_ip(); // 默认关闭
            $data['ctime'] = time();
            $data['usc_id'] = $uscId;
            $data['is_active'] = 1; // 默认激活
            $data['is_xia_recharge'] = 2; // 默认关闭

            //regSysType
            if ($usc_userInfo['regSysType'] != C('USC_TYPE')) {
                $data['is_change_uname'] = 1;
            }

            $uid = $this->add($data);
            $sql = $this->getLastSql();
            $tmp = var_export($usc_userInfo,TRUE);
            log_file("[2]".$tmp.'[sql]:'.$sql,$name='usc',$path='usc');

            if (!$uid) {
                MyError::add("同步用户信息出错!");
                return false;
            }
            //初始化
            $this->userInit($uid, $data['password'], 0);

        } else {
            $uid = $user['uid'];
            $data['usc_id'] = $uscId;
            $this->where(array("uid" => $uid))->save($data);

        }

        // 用户身份信息 不再与用户中心同步  anlw  2014-10-23 注释

//         if (isset($usc_userInfo['identityNo']) && $usc_userInfo['identityNo'] && $uid) {
//             $identityNos = explode(',', trim($usc_userInfo['identityNo'], ','));
//             $identityIds = M("identity_lib")->where(array("identity_no" => array('in', $identityNos)))->getField('id', true);
//             M('user_identity_relation')->where('uid=' . $uid)->delete(); //先删除该用户所有身份
//             //初始化身份
//             foreach ($identityIds as $v) {
//                 $insert_arr = array();
//                 $insert_arr['uid'] = $uid;
//                 $insert_arr['identity_id'] = $v;
//                 $insert_arr['ctime'] = time();
//                 $insert_arr['mtime'] = time();
//                 M('user_identity_relation')->add($insert_arr); //循环添加身份
//             }
//         }
        return $uid;

    }

    const LOGIN_TYPE_MOBILE_PWD = 1;
    const LOGIN_TYPE_EMAIL_PWD = 2;
    const LOGIN_TYPE_USERNAME_PWD = 3;
    const LOGIN_TYPE_MOBILE_SMS = 4;
    const LOGIN_TYPE_USC_TOKEN = 5;

    /**
     * USC登录
     * @param unknown $account
     * @param unknown $password
     * @return boolean
     */
    function login_usc($account, $password, $token = '', $uid_type = 1, $is_invest_finance = 1, $pwd_hash = true) {
        $userInfo = array();

        //判断是否是手机号码
        import_addon("libs.Usc.UscApi");
        $uscapi = new UscApi(C('USC_TYPE'));

        $sync_column = ""; // 后面进行用户信息同步时使用
        if (!$token) { // 根据用户 密码进行登录
            if ($password) {
                $md5Pwd = $pwd_hash ? pwd_md5($password) : $password;
                if (checkMobile($account)) {
                    $uscdata['MOBILE'] = $account;
                    $uscdata['USERPWD'] = $md5Pwd;
                    $uscdata['USERTYPE'] = $uid_type == 2 ? 'E' : 'P';//2015.1.28增加类型
                    $uscdata['IS_INVEST_FINANCE'] = $is_invest_finance == 1 ? '1' : '2';//2015324增加投资人(默认为1)，融资人为2
                    $sync_column = " mobile = '" . $account . "' AND is_mobile_auth = 1 AND uid_type='" . intval($uid_type) . "' AND is_invest_finance='" . intval($is_invest_finance) . "'";
                    $this->loginType = self::LOGIN_TYPE_MOBILE_PWD;
                } elseif (checkEmail($account)) {
                    $uscdata['EMAIL'] = $account;
                    $uscdata['USERPWD'] = $md5Pwd;
                    $sync_column = " email = '" . $account . "' AND is_email_auth = 1 ";
                    $this->loginType = self::LOGIN_TYPE_EMAIL_PWD;
                } else {
                    $uscdata['USERNAME'] = $account;
                    $uscdata['USERPWD'] = $md5Pwd;
//                    $sync_column = "uname = '".$account."'";
                    $sync_column = "";

                    $this->loginType = self::LOGIN_TYPE_USERNAME_PWD;
                }
            } else {
                $uscdata['MOBILE'] = $account;
                $uscdata['USERTYPE'] = $uid_type == 2 ? 'E' : 'P'; //2015.1.28增加类型
                $uscdata['IS_INVEST_FINANCE'] = $is_invest_finance == 1 ? '1' : '2';//2015324增加投资人(默认为1)，融资人为2
                $uscdata['logintype'] = self::LOGIN_TYPE_MOBILE_SMS;
                $this->loginType = self::LOGIN_TYPE_MOBILE_SMS;
            }
            $uscdata['LOGINFROM'] = $this->getFrom();
            $uscdata['PORTALCODE'] = self::$api_key;
            $result = $uscapi->login($uscdata);
            //兼容新密码
            if ($result['boolen'] != 1) {
                $uscdata['USERPWD'] = "-1";
                $uscdata['NEWUSERPWD'] = $password;
                $result = $uscapi->login($uscdata);
            }
        } else { // 根据token 进行登录
            $uscdata['TOKEN'] = $token;
            $uscdata['PORTALCODE'] = self::$api_key;
            $result = $uscapi->logCheckToken($uscdata);

            $this->loginType = self::LOGIN_TYPE_USC_TOKEN;
        }

        if ($result['boolen'] == 1) {   // 登录成功,同步用户到本地
            $userInfo = $result['data'];

            $uid = $this->syncUserToLocal($userInfo, $sync_column);
            if (!$uid) {
                return false;
            }

            $userData = $this->getByUid($uid);

            //是否删除用户
            if (!$userData['is_set_uname']) {
                if ($this->loginType == self::LOGIN_TYPE_USERNAME_PWD) {
                    MyError::add("帐号或密码错误!");
                    return false;
                }
            }

            //是否删除用户
            if (false === $this->checkUserAccountStatus($userData['is_del'], $userData['is_active'])) {
                return false;
            }

            $token = $userInfo['token'];
            if ($this->doLogin_usc($uid, 1, $token, null)) {
                return $uid;
            } else {
                return false;
            }
        } else {
            //init(平台账户),管理用户 登录
            $userInfo = $this->login_check($account, $password, $uid_type, $is_invest_finance);
            if (!$userInfo) {
                MyError::add("帐号或密码错误!");
                return false;
            } else {
                if (!$userInfo['is_init']) {
                    MyError::add("登录用户中心失败：" . $result["message"]);
                    return false;
                } else {
                    if ($this->doLogin($userInfo['uid'], 1, '')) {
                        return $userInfo['uid'];
                    } else {
                        MyError::add("登录用户中心失败：" . $result["message"]);
                        return false;
                    }
                }
            }
        }

    }

    //获取登录来源
    function getFrom() {
        return getFrom();
    }

    /**
     * 登录处理，session赋值
     * @param unknown $uid
     */
    function doLogin_usc($uid, $isLogin = 1, $token = '', $identityNo = array()) {
        $userInfo = $this->getByUid($uid);

//        p($userInfo);

//        p(self::getApiKey());

        if (!$userInfo) {
            MyError::add("账户不存在!");
            return false;
        }

        if (empty($identityNo)) {
            $identityId = M("user_identity_relation")->where(array("uid" => $userInfo['uid']))->getField("identity_id",
                true);
            $identityNo = M("identity_lib")->where(array("id" => array('in', $identityId)))->getField('identity_no',
                true);
        }


        $key = self::USER_LOGIN_KEY;
        if (!is_null($userInfo['newpassword'])) {
            unset($userInfo['newpassword']);
        }
        unset($userInfo['password']);

        //重置登录数据时使用
        $loginUser = $this->getLoginedUserInfo();
        $loginType = isset($loginUser['login_type']) ? $loginUser['login_type'] : "";
        //获取头像信息
        $userPhoto = D("Account/UserPhoto");
        $avatar = $userPhoto->getAvatar($userInfo['uid']);
        $userInfo['avatar'] = $avatar;
        $userInfo['usc_token'] = $token;
        $userInfo['identity_no'] = $identityNo;
        $userInfo['login_type'] = $this->loginType ? $this->loginType : $loginType;

        //用户身份初始化
        $this->initUserRole($userInfo);

        $domain = parseHost("http://" . $_SERVER['HTTP_HOST']);
        cookie(self::USC_TOKEN, $token, "path=/&domain=." . $domain);
        //session 赋值
        session('uid', $userInfo['uid']);
        BaseModel::setApiKey('', '', $userInfo['uid']);

        BaseModel::newSession($key, $userInfo);

        $this->setUserOnline($userInfo['uid']);
        if ($isLogin) {
            //更新记录
            $data['before_logintime'] = $userInfo['last_logintime'];
            $data['last_logintime'] = time();
            $data['before_loginclient'] = $userInfo['last_loginclient'];
            $data['last_loginclient'] = visit_source();

            $this->where(array("uid" => $uid))->save($data);

            //更新登录记录
            D("Account/LoginLog")->save($uid);

            BaseModel::newSession(self::LAST_LOGIN_HEART_TIME, time());
        }
        return true;
    }

    /**
     * 根据平台角色跳转到不同的页面
     * @param unknown $loginUserInfo
     */
    function initUserRole(&$userInfo) {
        $guarantor = D('Zhr/Guarantee')->getGuarantorId($userInfo['uid']);
        $userInfo['guarantor_id'] = empty($guarantor) ? 0 : $guarantor['guarantor_id'];
        $userInfo['role'] = 0;
        if ($userInfo['guarantor_id']) {
            $userInfo['role_id'] = $userInfo['guarantor_id'];
            $orgType = M('invest_guarantor')->where(array('id' => $userInfo['guarantor_id']))->getField('org_type');
            switch ($orgType) {
                case 1:
                    $userInfo['role'] = IdentityConst::ROLE_DANBAO;
                    break;
                case 2:
                    $userInfo['role'] = IdentityConst::ROLE_BAOLI;
                    break;
                case 3:
                    $userInfo['role'] = IdentityConst::ROLE_JIJIN;
                    break;
                case 4:
                    $userInfo['role'] = IdentityConst::ROLE_PIAOJU;
                    break;
                case 91:
                    $userInfo['role'] = IdentityConst::ROLE_OTHER;
                    break;
                default:
                    ;
                    break;
            }
        } else {
            if ($userInfo['finance_uid_type'] == 1 || ($userInfo['uid_type'] == self::USER_TYPE_CORP && $userInfo['is_invest_finance'] == self::INVEST_FINANCE_RONGZI)) {
                //融资企业
                $userInfo['role'] = IdentityConst::ROLE_CORP;
                $userInfo['role_id'] = M('finance_corp')->where(array('uid' => $userInfo['uid']))->getField('id');

            } elseif ($userInfo['finance_uid_type'] == 2 || ($userInfo['uid_type'] == self::USER_TYPE_PERSON && $userInfo['is_invest_finance'] == self::INVEST_FINANCE_RONGZI)) {
                //融资个人
                $userInfo['role'] = IdentityConst::ROLE_PERSON;
                $userInfo['role_id'] = M('finance_person')->where(array('uid' => $userInfo['uid']))->getField('id');
            }
        }
        $userInfo['home_url'] = $this->getHomeUrlByRole($userInfo['role']);
        $signService = service('Signature/Sign');
        import_app("Signature.Service.FaDaDaService");
        if(in_array('I104',$userInfo['identity_no'])) { //账户代表鑫合汇平台的情况另算，为了签章
            $userInfo['sign_user_id'] = $signService->get_sign_user_id(1,IdentityConst::ROLE_PINGTAI);//字典项 E044 56 平台
            $userInfo['fadada_customer_id'] = $signService->get_sign_user_id(1,IdentityConst::ROLE_PINGTAI,FaDaDaService::SIGN_TYPE);
        }else{
            $userInfo['sign_user_id'] = $signService->get_sign_user_id($userInfo['role_id'],$userInfo['role']);
//            $userInfo['fadada_customer_id'] = $signService->get_sign_user_id($userInfo['role_id'],$userInfo['role'],FaDaDaService::SIGN_TYPE);
        }
    }

    /**
     * 根据平台角色跳转到不同的页面
     * @param string $userRole
     */
    function getHomeUrlByRole($userRole) {
        if (empty($userRole)) {
            return;
        }
        switch ($userRole) {
            //担保公司
            case IdentityConst::ROLE_DANBAO:
                $url = U('Zhr/Guarantee/apply');
                break;
            //保理公司
            case IdentityConst::ROLE_BAOLI:
                $url = U('Application/ProjectManage/index');
                break;
            //基金公司
            case IdentityConst::ROLE_JIJIN:
                $url = U('Financing/InvestFund/toFundPrjList');
                break;
            //融资企业
            case IdentityConst::ROLE_CORP:
                $url = U('Account/FinancingManage/index');
                break;
            //融资个人
            case IdentityConst::ROLE_PERSON:
                $url = U('Account/FinancingManage/index');
                break;
            case IdentityConst::ROLE_OTHER:
                $url = U('Application/ProjectManage/index');
                break;
            default:
                $url = '';
                break;
        }
        return $url;
    }

    //判断登录来源
    function visit_source() {
        return visit_source();
    }

    /**
     * 退出
     */
    function logout() {
        if (C('USC_REMOTE_SWITCH')) {
            return $this->logout_usc();
        }
        $key = self::USER_LOGIN_KEY;
        BaseModel::newSession($key, null);
        BaseModel::newSession(UserModel::LAST_LOGIN_HEART_TIME, null);
        session_destroy();
        $domain = parseHost("http://" . $_SERVER['HTTP_HOST']);
        cookie(self::USC_TOKEN, null, "path=/&domain=." . $domain);
        $userInfo = $this->getLoginedUserInfo();
        $this->setUserOffline($userInfo['uid']);
        cookie("visit", null);
        return true;
    }

    function logout_usc() {
        $key = self::USER_LOGIN_KEY;
        $loginUserInfo = BaseModel::newSession($key);
        if (!$loginUserInfo) {
            return true;
        }

        BaseModel::newSession($key, null);
        BaseModel::newSession(UserModel::LAST_LOGIN_HEART_TIME, null);

        $domain = parseHost("http://" . $_SERVER['HTTP_HOST']);
        cookie(self::USC_TOKEN, null, "path=/&domain=." . $domain);

        import_addon("libs.Usc.UscApi");
        $uscapi = new UscApi(C('USC_TYPE'));
        $data['token'] = cookie(self::USC_TOKEN);
        $result = $uscapi->logout($data);

        $userInfo = $this->getLoginedUserInfo();
        $this->setUserOffline($userInfo['uid']);
        cookie("visit", null);
        return true;
    }

    /**
     * 记住登录状态
     */
    function rememberLogin($username) {
        $userInfo = $this->getLoginedUserInfo();
        if (!$userInfo) {
            return false;
        }

        $userInfo = $this->where(array("uid" => $userInfo['uid']))->find();

        if ($username) {
            $uname = $username;
        } else {
            $uname = $userInfo['uname'];
        }
        $uname = think_encrypt($uname);
        //保存3年
        $domain = parseHost("http://" . $_SERVER['HTTP_HOST']);
        cookie(self::UPG_USER_REMEMBER_LOGIN_NAME_KEY, $uname, "expire=94608000&path=/&domain=." . $domain);
    }

    //清空记住用户名
    function unsetRememberLogin() {
        $domain = parseHost("http://" . $_SERVER['HTTP_HOST']);
        cookie(self::UPG_USER_REMEMBER_LOGIN_NAME_KEY, null, "expire=-360&path=/&domain=." . $domain);
        if (isset($_COOKIE[self::UPG_USER_REMEMBER_LOGIN_NAME_KEY])) {
            unset($_COOKIE[self::UPG_USER_REMEMBER_LOGIN_NAME_KEY]);
        }
    }

    //获取登录用户名
    function getLoginName() {
        return think_decrypt(cookie(self::UPG_USER_REMEMBER_LOGIN_NAME_KEY));
    }

    /**
     * 获取登录用户资料
     */
    function getLoginedUserInfo() {
        $key = self::USER_LOGIN_KEY;
        $result = BaseModel::newSession($key);

        $result = $result ? $result : array();
        if (!$result) {
            return array();
        }
//        if (!$result['is_set_uname']) $result['uname'] = marskName($result['uname'], 3, 4);
        $uid = $result['uid'];
        //检查是否在线
        if (!$this->isUserOnline($uid)) {
            //本地退出
            //$this->logout();
            //return array();//此处退出会造成某些情况下 relogin时候丢失状态；暂时去除了！By 001073
        } else {
            //设置在线
            $this->setUserOnline($uid);
        }
        if (!empty($result['person_id']) && strlen($result['person_id']) >= 15) {
            $s = $this->getUserGenderByPersonId($uid, $result);
            $result['sex_name'] = $s == 1 ? '先生' : '女士';
        } else {
            $result['sex_name'] = '';
        }
        return $result;
    }

    /**
     * 解析token
     * @param unknown $token
     * @return multitype:|multitype:string
     */
    function parseToken($token) {
        if (!$token) {
            return array();
        }
        $arr = explode("_", $token);
        $uid = (int)$arr[1];
        $tokenStr = $arr[0];
        return array($tokenStr, $uid);
    }

    /**
     * 修改密码
     * @param unknown $uid
     */
    function updatePwdWithOldPwd($uid, $pwd, $oldPwd) {
        if (C('USC_REMOTE_SWITCH')) {
            return $this->updatePwdWithOldPwd_usc($uid, $pwd, $oldPwd);
        }
        $uid = intval($uid);
        $info = $this->getByUid($uid);
        if (is_null($info['newpassword'])) {
            $oldPwd = pwd_md5($oldPwd);
            if ($info['password'] != $oldPwd) {
                MyError::add("旧密码不匹配!");
                return false;
            }
        } else {
            if (!pwd_hash_verify($oldPwd, $info['newpassword'])) {
                MyError::add("旧密码不匹配!");
                return false;
            };
        }
        $data['newpassword'] = $pwd;
        $data['password'] = '';
        if ($this->create($data)) {
            $data['newpassword'] = pwd_hash($pwd);
            $data['password'] = '';
            $this->where(array("uid" => $uid))->save($data);
            return true;
        } else {
            MyError::add($this->getError());
            return false;
        }
    }

    //usc修改密码
    function updatePwdWithOldPwd_usc($uid, $pwd, $oldPwd) {
        $info = $this->getByUid($uid);
        if (!$info) {
            MyError::add("用户不存在!");
            return false;
        }
        $data['newpassword'] = $pwd;
        $data['password'] = "";
        if (!$this->create($data)) {
            MyError::add($this->getError());
            return false;
        }

        $result = $this->editPwd_usc($info['usc_id'], $oldPwd, $pwd);
        if ($result) {
            $data['newpassword'] = pwd_hash($pwd);
            $data['password'] = "";
            $this->where(array("uid" => $uid))->save($data);
            return true;
        } else {
            MyError::add("验证失败");
            return false;
        }
    }

    /**
     * 检查旧密码
     * @param unknown $uid
     * @param unknown $oldPwd
     * @return boolean
     */
    function checkOldPwd($uid, $oldPwd) {
        $uid = intval($uid);
        $oldPwd = pwd_md5($oldPwd);
        return $this->where(array("uid" => $uid, "password" => $oldPwd))->find() ? true : false;
    }

    //验证身份证是否合法
    public function validatePersonNumber($person_id) {
        import_addon("libs.My.MyIdcheck");
        $idcheckObj = new MyIdcheck($person_id);
        return $idcheckObj->isIdNum();
    }


    /*
     *
     * 编辑用户处理以及身份赋予
     */

    public function updateUser($para, $mid = null) {
        if (!empty($para['identity_arr'])) {
            $identity_arr = $para['identity_arr']; //身份数组
            $identity_arr = explode(',', $identity_arr);
        }
        $uid = (int)$para['uid']; //用户id

        if (!empty($para['vip_group_id'])) {
            $para['vip_group_id'] = is_array($para['vip_group_id']) ? implode(',',
                $para['vip_group_id']) : $para['vip_group_id'];
            $para['vip_group_id'] = trim($para['vip_group_id'], ',');
        } else {
            $para['vip_group_id'] = '';
        }

// 		print_r($para);

        if (empty($uid)) {
            $result = array('boolen' => 0, 'message' => '参数错误');
            return $result;
        }
        $uid_arr = $this->getByUid($uid);

        if (empty($uid_arr)) {
            $result = array('boolen' => 0, 'message' => '记录不存在');
            return $result;
        }
        $data['uid'] = $uid; //用户id
        if (isset($para['email'])) {
            $data['email'] = trim($para['email']); //邮箱
        }
        if (!empty($para['uname'])) {
            $data['uname'] = trim($para['uname']); //昵称
            if (!$this->checkUname($data['uname'])) {
                return array('boolen' => 0, 'message' => '用户名不能有特殊空格');
            }

        }
        if (!empty($para['password'])) {
            $data['password'] = ""; //密码
            $data['newpassword'] = pwd_hash(trim($para['password'])); //密码
        }
        //是否激活 1-激活   2-未激活
        if (isset($para['is_active'])) {
            $data['is_active'] = (int)$para['is_active'];
        }
        //是否线下充值 1-是   2-否
        if (isset($para['is_xia_recharge'])) {
            $data['is_xia_recharge'] = (int)$para['is_xia_recharge'];
        }
        //身份证
        if (!empty($para['person_id'])) {
            $data['person_id'] = strtoupper(trim($para['person_id']));
            if (!isset($para['no_person_ck']) || !$para['no_person_ck']) {//申明不校验身份证
                $valid_person_id = $this->validatePersonNumber($data['person_id']);
                if (!$valid_person_id) {
                    $result = array('boolen' => 0, 'message' => '身份证号码错误');
                    return $result;
                }
            }
        }


        if (isset($para['real_name'])) {
            $data['real_name'] = trim($para['real_name']);
        } //真实姓名

        if (!empty($para['mobile'])) {
            $data['mobile'] = trim($para['mobile']); //联系手机
            if ($uid_arr['mobile'] == $uid_arr['uname']) {
                $data['uname'] = $data['mobile'];//如果用户名和旧手机号相同，则将用户名变成新的手机号
            }

        }

        if (isset($para['mi_id'])) {
            $data['mi_id'] = (int)$para['mi_id'];
        } //工作单位id

        if (isset($para['dept_id'])) {
            $data['dept_id'] = (int)$para['dept_id'];
        } //部门id

        if (isset($para['vip_group_id'])) {
            $data['vip_group_id'] = ',' . $para['vip_group_id'] . ',';
        } //所属高级会员组

        if (isset($para['usc_id'])) {
            $data['usc_id'] = $para['usc_id'];
        }
        if (isset($para['sex'])) {
            $data['sex'] = $para['sex'];
        }
        if (isset($para['person_img'])) {
            $data['person_img'] = $para['person_img'];
        }
        if (isset($para['is_email_auth'])) {
            $data['is_email_auth'] = $para['is_email_auth'];
        }
        if (isset($para['is_email_auth']) && $para['is_email_auth']) {
            $data['email_auth_time'] = time();
        }
        if (isset($para['is_mobile_auth'])) {
            $data['is_mobile_auth'] = $para['is_mobile_auth'];
        }
        if (isset($para['is_mobile_auth']) && $para['is_mobile_auth']) {
            $data['mobile_auth_time'] = time();
        }

        if (isset($para['is_id_auth'])) {
            $data['is_id_auth'] = $para['is_id_auth'];
        }
        if (isset($para['is_id_auth']) && $para['is_id_auth'] && !$data['id_auth_time']) {
            $data['id_auth_time'] = time();
        }

        if (isset($para['is_change_uname'])) $data['is_change_uname'] = $para['is_change_uname'];
        if (isset($para['finance_uid_type'])) $data['finance_uid_type'] = $para['finance_uid_type'];
        if (isset($para['b_role_type'])) $data['b_role_type'] = $para['b_role_type'];
        //增加浙商存管数据
        if (isset($para['zs_bind_serial_no'])) $data['zs_bind_serial_no'] = $para['zs_bind_serial_no'];
        if (isset($para['zs_account_id'])) $data['zs_account_id'] = $para['zs_account_id'];
        if (isset($para['zs_bank_mobile'])) $data['zs_bank_mobile'] = $para['zs_bank_mobile'];
        if (isset($para['zs_status'])) $data['zs_status'] = $para['zs_status'];
        if (isset($para['zs_mtime'])) $data['zs_mtime'] = $para['zs_mtime'];
        if (isset($para['zs_remark'])) $data['zs_remark'] = $para['zs_remark'];
        //if (isset($para['is_invest_finance'])) $data['is_invest_finance'] = $para['is_invest_finance'];//2015324 增加投资人(默认为1)，融资人为2

        $uscId = $uid_arr['usc_id'];
        $resultUsc = array();

        if (C('USC_REMOTE_SWITCH') && !empty($uscId)) {
            $resultUsc = $this->updateUser_usc($data, $uscId);
            if (!$resultUsc) {
                return array('boolen' => 0, 'message' => MyError::lastError());
            }
        }

        $data['mtime'] = time();

        //日志
        $log_old = array();
        $log_old = $uid_arr;
        $log_old['identity_arr'] = M('user_identity_relation')->where('uid=' . $uid)->find();

        $log_new = array();
        $log_new = $data;
        $log_new['identity_arr'] = $identity_arr;
        $con = $this->save($data);

        if (!$con) {
            return array('boolen' => 1, 'dbErr' => '1', 'dbMes' => $this->getDbError());
        }

        if ($data['uid']) {
            //日志记录
            if (!empty($mid)) {
                D('Admin/ActionLog')->insertLog($mid, '用户后台修改' . $uid, $log_old, $log_new);
            }

            if (!empty($identity_arr)) {
                M('user_identity_relation')->where('uid=' . $uid)->delete(); //先删除该用户所有身份
                //初始化身份
                $identityNoStr = "";
                foreach ($identity_arr as $v) {
                    $insert_arr = array();
                    $insert_arr['uid'] = $uid;
                    $insert_arr['identity_id'] = $v;
                    $insert_arr['ctime'] = time();
                    $insert_arr['mtime'] = time();
                    M('user_identity_relation')->add($insert_arr); //循环添加身份
                    $identityNo = M("identity_lib")->where(array("id" => $v))->getField('identity_no');
                    $identityNoStr = $identityNoStr . "," . $identityNo;
                }

                // 用户的身份不再与用户中心同步 -- anlw  2014-10-23 注释

//                $identityNoStr = trim($identityNoStr, ',');

//                 if (C('USC_REMOTE_SWITCH') && !empty($uscId)) {
//                     $idResult = $this->editIdentity($uscId, $identityNoStr);
//                     if (!$idResult) {
//                         return array('boolen' => 0, 'message' => "同步向用户中心更新身份有误！");
//                     }
//                 }

            }

            $result = array('boolen' => 1, 'message' => '操作成功');

        } else {
            $result = array('boolen' => 0, 'message' => '操作失败');
        }


        return $result;
    }

    //更新
    function updateUser_usc($data, $uscId) {
        $param = array(
            'USERID' => $uscId,
            'PORTALCODE' => self::$api_key,
        );
        if (isset($data['person_id'])) {
            $param['IDCARD_NO'] = $data['person_id'];
        }
        if (isset($data['sex'])) {
            $param['SEX'] = $data['sex'];
        }
        if (isset($data['dept_id'])) {
            $uscDeptNo = M("org_dept")->where(array("id" => $data['dept_id']))->getField("usc_dept_no");
            $param['DEPT_NO'] = $uscDeptNo;
        }
        if (isset($data['password'])) {
            $param['USERPWD'] = '';
            $param['NEWUSERPWD'] = $data['newpassword'];
        }
        if (isset($data['is_id_auth'])) {
            $param['IF_REAL_AUTH'] = $data['is_id_auth'];
        }
        //persion_id
//         if(isset($data['persion_id']))  $param['IDCARD'] = $data['persion_id'];
        if (isset($data['real_name'])) {
            $param['REALNAME'] = $data['real_name'];
        }

        if (isset($data['is_email_auth'])) {
            $param['IF_EMAIL_AUTH'] = $data['is_email_auth'];
        }
        if (isset($data['is_mobile_auth'])) {
            $param['IF_MOBILE_AUTH'] = $data['is_mobile_auth'];
        }
        if (isset($data['email'])) {
            $param['EMAIL'] = $data['email'];
        }
        if (isset($data['mobile'])) {
            $param['MOBILE_NO'] = $data['mobile'];
        }

        if (isset($data['uname'])) {
            $param['USERNAME'] = $data['uname'];
        }
        //if (isset($data['uid_type'])) $param['USERTYPE'] = $data['uid_type'] == 1 ? "P" : "B";
        // if (isset($data['uid_type'])) $param['USERTYPE'] = $data['uid_type'] == 1 ? "P" : "E";//2015.1.28 增加用户类型
        // print_r($param);exit;
        // if(isset($data['is_invest_finance'])) $param['IS_INVEST_FINANCE']=$data['is_invest_finance']==1?'1':'2';//2015326 增加投资人(默认为1)，融资人为2
        import_addon("libs.Usc.UscApi");
        $uscapi = new UscApi(C('USC_TYPE'));
        $result = $uscapi->regEditUser($param);
        if ($result['boolen'] == 1) {
            return true;
        }
        MyError::add($result['message']);
        return false;
    }

    //验证
    function doAuth_usc($type, $uscId, $param, $val = 1) {
        $data['USERID'] = $uscId;
        if ($type == 'REAL') {
            $data['TYPE'] = 'REAL';
            $data['ITEM'] = $param;
            $data['VAL'] = $val;
        } elseif ($type == 'EMAIL') {
            $data['TYPE'] = 'EMAIL';
            $data['ITEM'] = $param;
            $data['VAL'] = $val;
        } elseif ($type == 'MOBILE') {
            $data['TYPE'] = 'MOBILE';
            $data['ITEM'] = $param;
            $data['VAL'] = $val;
        }

        import_addon("libs.Usc.UscApi");
        $uscapi = new UscApi(C('USC_TYPE'));
        $result = $uscapi->regDoAuth($data);
        if ($result['boolen'] == 1) {
            return true;
        }
        MyError::add($result['message']);
        return false;
    }

    //修改密码
    function editPwd_usc($uscId, $oldPwd, $newPwd) {
        $data['USERID'] = $uscId;
        $data['OLDPWD'] = $oldPwd;
        $data['NEWPWD'] = $newPwd;
        import_addon("libs.Usc.UscApi");
        $uscapi = new UscApi(C('USC_TYPE'));
        $result = $uscapi->regEditPwd($data);
        if ($result['boolen'] == 1) {
            return true;
        }
        MyError::add($result['message']);
        return false;
    }

    //修改权限
    function editIdentity($uscId, $identityNo) {
        $data['USERID'] = $uscId;
        $data['IDENTITY_STRING'] = $identityNo;
        import_addon("libs.Usc.UscApi");
        $uscapi = new UscApi(C('USC_TYPE'));
        $result = $uscapi->regEditIdentity($data);
        if ($result['boolen'] == 1) {
            return true;
        }
        MyError::add($result['message']);
        return false;
    }

    //更新数据到用户中心
    function update2Usc($userInfo) {
        //usc 注册
        import_addon("libs.Usc.UscApi");
        $uscapi = new UscApi(C('USC_TYPE'));
        $uscdata["USERNAME"] = isset($userInfo['uname']) ? $userInfo['uname'] : "";
        $uscdata["USERPWD"] = isset($userInfo['password']) ? $userInfo['password'] : "";
        $uscdata["REALNAME"] = isset($userInfo['real_name']) ? $userInfo['real_name'] : "";
        $uscdata["EMAIL"] = isset($userInfo['email']) ? $userInfo['email'] : "";
        $uscdata["MOBILE_NO"] = isset($userInfo['mobile']) ? $userInfo['mobile'] : "";
        $uscdata["IDCARD_NO"] = isset($userInfo['person_id']) ? $userInfo['person_id'] : "";
        $uscdata["SEX"] = isset($userInfo['sex']) ? $userInfo['sex'] : 0;
        $uscdata['PROVINCE'] = '';
        $uscdata['CITY'] = '';
        $uscdata['AREA'] = '';
        $uscdata['ADDRESS'] = '';
        $uscdata["IF_REAL_AUTH"] = isset($userInfo['is_id_auth']) ? $userInfo['is_id_auth'] : 0;
        $uscdata["IF_EMAIL_AUTH"] = isset($userInfo['is_email_auth']) ? $userInfo['is_email_auth'] : 0;
        $uscdata["IF_MOBILE_AUTH"] = isset($userInfo['is_mobile_auth']) ? $userInfo['is_mobile_auth'] : 0;
        $uscdata["REG_TIME"] = date('Y-m-d');
        //$uscdata['USERTYPE'] = $userInfo['uid_type'] == 1 ? "P" : "B";
        $uscdata['USERTYPE'] = $userInfo['uid_type'] == 2 ? "E" : "P";
        $uscdata['DEPT_ID'] = null;

        $uscdata['IS_INVEST_FINANCE'] = $userInfo['is_invest_finance'] == 1 ? '1' : '2';//2015324 增加投资人(默认为1)，融资人为2
        /**
         * $data['info_source'] = 2;//信息来源1-正常注册 2-代理注册
         * $data['uid_type'] = $para['uid_type'];//用户类型:1-个人 2-企业
         */
        if (isset($userInfo['dept_id'])) {
            $uscDeptNo = M("org_dept")->where(array("id" => $userInfo['dept_id']))->getField("usc_dept_no");
            $uscdata['DEPT_ID'] = $uscDeptNo;
        }
        $uscResult = $uscapi->regUser($uscdata);
        if ($uscResult['boolen'] != 1) {
            MyError::add($uscResult['message']);
            return false;
        }
        $uscId = $uscResult['data']['id'];
        return $uscId;
    }

    const UPG_ONLINE_USERS = "NEW_UPG_ONLINE_USERS"; //redis用户在线key

    /**
     * 设置用户在线
     * @param unknown $uid
     */
    function setUserOnline($uid) {
        if (!$uid) {
            return false;
        }
        // import_addon("libs.Cache.RedisSet");
        // RedisSet::getInstance(self::UPG_ONLINE_USERS)->sAdd($uid);
        //给xinhehui_site活动存uid，用于记录登入状态
        $redis = \Addons\Libs\Cache\Redis::getInstance('activity');
        $redis->set(session_id().'_uid',$uid);

        import_addon("libs.Cache.RedisHash");
        $hash = new RedisHash(self::UPG_ONLINE_USERS);
        $hash->noExpireSet($uid, time());
        return true;
    }

    /**
     * 设置用户离线
     * @param unknown $uid
     * @return boolean
     */
    function setUserOffline($uid) {
        if (!$uid) {
            return false;
        }
        // import_addon("libs.Cache.RedisSet");
        // RedisSet::getInstance(self::UPG_ONLINE_USERS)->sRemove($uid);
        import_addon("libs.Cache.RedisHash");
        $hash = new RedisHash(self::UPG_ONLINE_USERS);
        $hash->delKey($uid);
        return true;
    }

    /**
     * 用户是否在线
     * @param unknown $uid
     */
    function isUserOnline($uid) {
        if (!$uid) {
            return false;
        }
        // import_addon("libs.Cache.RedisSet");
        // return RedisSet::getInstance(self::UPG_ONLINE_USERS)->sContains($uid);
        import_addon("libs.Cache.RedisHash");
        $hash = new RedisHash(self::UPG_ONLINE_USERS);
        $time = $hash->get($uid);
        if (!$time) {
            return false;
        }
        $diffTime = C('LOGIN_SESSION_EXPIRE_TIME') + 10;
        if ((time() - $time) > $diffTime) {
            return false;
        }
        return true;
    }

    /**
     * 获取在线总数
     * @return [type] [description]
     */
    function getOnlineUserNumber() {
        set_time_limit(0); //大数据执行时间延长
        import_addon("libs.Cache.RedisHash");
        $hash = new RedisHash(self::UPG_ONLINE_USERS);
        $list = $hash->getAll();
        $number = 0;
        if (!$list) {
            return $number;
        }
        $key = 'ONLINE_USER_NUMBER';
        if (cache_with_mi_no($key)) {
            return cache_with_mi_no($key);
        }
        foreach ($list as $key => $value) {
            $diffTime = C('LOGIN_SESSION_EXPIRE_TIME') + 10;
            if ((time() - $value) > $diffTime) {
                continue;
            }
            $number++;
        }
        cache_with_mi_no($key, $number, array('expire' => 60));
        return $number;
    }

    //获取全部用户
    public function getAllUserCount() {
        $data = $this->db(0, C('SLAVE_DB_CONF'))->where('is_del != 1')->count();
        return $data;
    }

    //未实名认证的用户数
    public function getNoRealNameAuthCount() {
        $data = $this->db(0, C('SLAVE_DB_CONF'))->where('is_del != 1 and is_id_auth!=1')->count();
        return $data;
    }

    //未激活的用户数
    public function getNoActiveCount() {
        $data = $this->db(0, C('SLAVE_DB_CONF'))->where('is_del != 1 and is_active!=1')->count();
        return $data;
    }


    // 设置安全保证问题
    public function setSQA($uid, $sqa_key, $sqa_value, $is_check = false) {
        if (GROUP_NAME == 'Mobile') {
            $login_info = D('Mobile/MobileUser')->getMobileLogined();
        } elseif (GROUP_NAME == 'Mobile2') {
            $login_info = D('Mobile2/MobileUser')->getMobileLogined();
        } elseif (GROUP_NAME == 'Weixin') {
            $login_info = D('Weixin/MobileUser')->getMobileLogined();
        } else {
            $login_info = $this->getLoginedUserInfo();
        }
        $login_uid = $login_info['uid'];

        //B端融资人可以设置绑定的投资人安全问题 安全在action中已经校验
        if ($check_permission) {
            if (!$login_uid || $login_uid != $uid) {
                throw_exception('权限不足');
            }
        }

        $sqa_keys = getCodeItemList('E025', true);
        $in_keys = false;
        foreach ($sqa_keys as $row) {
            if ($sqa_key == $row['code_no']) {
                $in_keys = true;
                break;
            }
        }
        if (!$sqa_key || !$in_keys) {
            throw_exception('请选择保障问题');
        }

        $sqa_value = trim($sqa_value); // YRZIF-2797
        if (!$sqa_value) {
            throw_exception('选填写答案');
        }
        if (!preg_match('/^[a-z0-9\x{4e00}-\x{9fa5}]+/ui', $sqa_value)) {
            throw_exception('答案只能由中英文数字组成');
        }

        $where = array('uid' => $uid);
        $sqa_value = pwd_md5($sqa_value);
        $user = $this->getByUid($uid);
        if ($sqa_key == $user['sqa_key'] && $sqa_value == $user['sqa_value']) {
            throw_exception('问题和答案和上次相同，请重新设置！');
        }

        if ($is_check) {
            return array(0, '未进行修改！');
        }
        $data = array(
            'sqa_key' => $sqa_key,
            'sqa_value' => $sqa_value,
        );
        if ($this->where($where)->save($data) === false) {
            throw_exception('设置安全保证问题出错！');
        }
        return array(1, '修改成功！');
    }

    /**
     * 初始化企业用户信息
     * @param $register_no
     * @param $short_name
     * @param $mobile
     * @return bool|mixed
     */
    public function initCorpUser($register_no, $short_name, $mobile) {
        if ($this->checkUnameUnique($register_no)) {
            $uname = $register_no;
        } elseif ($this->checkUnameUnique($short_name)) {
            $uname = $short_name;
        } else {
            $uname = $short_name . time();
        }
        //用户中心注册+用户信息初始化
        $uid = $this->register_usc($uname, $register_no, $mobile);
        if (!$uid) {
            throw_exception("用户信息初始化失败");
        }
        //用户标为商户
        $re = M("user_account")->where("uid=" . $uid)->save(array("is_merchant" => 1));
        if (!$re) {
            throw_exception("商户标识修改错误");
        }

        return $uid;
    }

    /**
     * 取得用户信息
     */
    public function getUserInfoByUid($uid) {
        $where = array(
            'uid' => $uid
        );
        $count = $this->where($where)->count();
        !$count && throw_exception("用户信息不存在!");
        $info = $this->where($where)->find();
        unset($info['password']);
        return $info;
    }

    /**
     * /**
     * 1、更新新客标购买次数
     * 2、更新用户是新客
     * @逻辑：
     * 投资两次新客标 更新字段is_new_bie=0
     * 只要投资不是新客标.更新字段is_new_bie = 0;
     * @param $uid
     * @param $prj_id
     * @param int $is_newbie
     * @return bool
     */

    public function procNewBie($uid,$prj_id,&$is_newbie = 0)
    {

        //查询用户是否是新客。如果是新客就不需要跟新了
        $is_newbie = (int) $this->where(['uid' => $uid])->getField('is_newbie');

        if (!$is_newbie) {
            //不是新客不需要做任何操作
            return true;
        }

        $is_new_prj = D("Financing/Prj")->where(array('id' => $prj_id))->getField("is_new");
        //只要购买普通标 就更新字段
        if (!$is_new_prj) {
            return $this->updateUserNotNewbie($uid);
        }
        //更新购买次数
        $update = D("Account/UserExt")->where(array('uid' => $uid))->setInc("new_bie_invest_times", 1);
        //先添加字段
        $new_bie_invest_times = (int)D("Account/UserExt")->where(array('uid' => $uid))->getField("new_bie_invest_times");
        if ($new_bie_invest_times < self::IS_CHANGE_NEW_BIE_TIMES) {
            return true;
        }
        return $this->updateUserNotNewbie($uid);

    }

    /**
     * 机构注册后如果填入了关联代码的操作
     * @param $uid
     * @param $ext_info key1:hongbaoCode key2:recommend
     */
    public function addUserRecommed($uid, $ext_info, $giveHongBao = 0) {
        $recommend_raw = $ext_info['recommend'];
        $hongbaoCode = $ext_info['hongbaoCode'];
        if ($hongbaoCode) {
            $recommend_raw = $hongbaoCode;
        }
        $recommend = preg_replace('/-.*$/', '', $recommend_raw);
        //如果有推荐或者是红包特殊代码
        if ($recommend) {
            if (preg_match('/\d+t\d+/', $recommend)) {
                $fromUid = D("Account/Recommend")->parse2Uid($recommend);
            } else {
                $fromUid = D("Account/Recommend")->getUidByUserCode($recommend);
            }
            D("Account/Recommend")->save($fromUid, $uid, $giveHongBao, $recommend_raw);
        }
        return true;
    }

    /**
     * 更新用户不在是新客
     */
    public function updateUserNotNewbie($uid) {
        $key = self::USER_LOGIN_KEY;
        $new_session = BaseModel::newSession($key);
        $new_session['is_newbie'] = 0;
        BaseModel::newSession($key, $new_session);

        return $this->where(array('uid' => $uid, 'is_newbie' => 1))->save(array('is_newbie' => 0));
    }

    /**
     * 根据用户身份证获取用户性别
     * @param number $uid
     * @param array $userInfo
     * @return number 1-男 0-女
     */
    function getUserGenderByPersonId($uid, $userInfo = array()) {
        if (!array_key_exists('person_id', $userInfo)) {
            $userInfo = $this->where(array('uid' => $uid))->getField('person_id');
        }
        $genderNum = substr($userInfo['person_id'], strlen($userInfo['person_id']) == 18 ? 16 : 14, 1);
        return intval($genderNum % 2);
    }

    /**
     * 获取客户经理UID
     * @param $manager_name
     * @return int
     */
    public function getUidByManagerName($manager_name) {
        return (int)$this->where(array(
            'real_name' => $manager_name,
            'uid_type' => self::USER_TYPE_MANAGER,
            'audit_status' => self::AUDIT_STATUS_PASS
        ))->getField('uid');
    }

    /**
     * 判断某个渠道下面的渠道用户ID是否被绑定
     * @param string $source_uid
     * @param string $source_name
     * @return number
     */
    public function checkSourceUidIsBind($source_uid, $source_name) {
        $user_register_source = M('user_register_source')->where(array(
            'source_uid' => $source_uid,
            'source_name' => $source_name
        ))->find();
        return $user_register_source ? 1 : 0;
    }

    public function addUserloginsession($key,$value)
    {
        $result = BaseModel::newSession(self::USER_LOGIN_KEY);
        $result[$key] = $value;
        BaseModel::newSession(self::USER_LOGIN_KEY, $result);
    }
    /**
     * 根据用户属性获取用户信息
     * @param array $attributes 用户属性
     * @return bool|mixed
     */
    public function getUserByAttributes(array $attributes) {
        if (empty($attributes)) {
            return false;
        }
        $condition = array();
        foreach ($attributes as $key => $row) {
            if (is_array($row)) {
                $condition[$key] = array('in', $row);
            } else {
                $condition[$key] = $row;
            }
        }
        return $this->where($condition)->find();
    }

    public function makeuname() {
        $uname = generate_username();
        $res = $this->checkUnameUnique($uname);
        while(!$res) {
            $uname = generate_username();
            $res = $this->checkUnameUnique($uname);
        }
        return $uname;
    }

    public function checkuscid($usc_id){
        $res = $this->where(array('usc_id'=>$usc_id))->getField('uid');
        return $res;
    }
}

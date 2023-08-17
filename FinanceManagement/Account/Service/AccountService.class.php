<?php

class AccountService extends BaseService {

    const EMAIL_LOG_TYPE_ACTIVE_EMAIL = "ACTIVE_EMAIL"; //激活邮箱
    const EMAIL_LOG_TYPE_FORGET_PASSWORD_EMAIL = "FORGET_PASSWORD_EMAIL"; //忘记密码
    const UNBIND_EMAIL = 'UNBIND_EMAIL'; //解绑邮箱
    const FIND_PAY_PWD2_ACTIVE_EMAIL = "FIND_PAY_PWD_2"; //第二种方式找回支付密码
    const ACTION_UNBIND_EMAIL = 1;
    const ACTION_FIND_PAYPWD = 2;
    const OPENING_ACCOUNT = 1;
    const OPENED_ACCOUNT = 2;
    /**
     * 增加一个新的emaillog
     * @param unknown $email
     * @param unknown $uid
     * @return boolean|string
     */

    function newEmailLog($email, $uid, $type = '') {
        if (!$email || !$uid)
            return false;
        $type = $type ? $type : self::EMAIL_LOG_TYPE_ACTIVE_EMAIL;
        $mod = M("UserEmailLog");
        $now = time();
        $token = md5($email . $now . "ugp");
        $data['email'] = $email;
        $data['type'] = $type;
        $data['uid'] = $uid;
        $data['status'] = 1;
        $data['token'] = $token;
        $data['ctime'] = date('Y-m-d H:i:s', $now);
        $mod->add($data);
        return $token;
    }

    /**
     * 获取token
     * @param unknown $uid
     * @return boolean|unknown
     */
    function getEmailToken($uid, $token, $type = '') {
        if (!$uid || !$token)
            return false;
        $type = $type ? $type : self::EMAIL_LOG_TYPE_ACTIVE_EMAIL;
        $mod = M("UserEmailLog");
        $return = $mod->where(array("uid" => $uid, "status" => 1, "token" => $token, "type" => $type))->find();
        return $return;
    }

    /**
     * 关闭激活数据
     * @param unknown $token
     * @return boolean
     */
    function closeEmailLog($token, $type = '') {
        if (!$token)
            return false;
        $type = $type ? $type : self::EMAIL_LOG_TYPE_ACTIVE_EMAIL;
        $userObj = D("Account/User");
        $tokenStr = $uid = 0;
        list($tokenStr, $uid) = $userObj->parseToken($token);
        $mod = M("UserEmailLog");
        $data['status'] = 0; //失效
        $data['mtime'] = date("Y-m-d H:i:s");
        $mod->where(array("token" => $tokenStr, "uid" => $uid, "type" => $type))->save($data);
        return true;
    }

    /**
     * 获取账户统计数据信息
     * @param unknown $id
     */
    function getSummaryById($id) {
        $id = (int) $id;
        $model = M("user_account_summary");
        return $model->where(array("uid" => $id))->find();
    }
    

    /**
     * 解析token
     * @param unknown $token
     */
    function parseToken($token) {
        $userObj = D("Account/User");
        return $userObj->parseToken($token);
    }

    /**
     * 关闭激活数据
     * @param unknown $uid
     * @param string $type
     */
    function closeEmailLogByUid($uid, $type = '') {
        $type = $type ? $type : self::EMAIL_LOG_TYPE_ACTIVE_EMAIL;
        $mod = M("UserEmailLog");
        $data['status'] = 0; //失效
        $mod->where(array("uid" => $uid, "type" => $type))->save($data);
        return true;
    }

    //计算安全级别
    function computeSafeLevel($uid) {
        $LEVELS = array('', 'a', 'b', 'c');
        $LEVEL_DIVIDE = 2; // 每几个一个级别
        $level = 0;
        $level_index = 0; // 级别索引

        $uid = (int) $uid;
        $user = $this->getByUid($uid);
        if (!$user)
            return array(0, $user, $LEVELS[$level_index]);

        // 1. 手机认证
        if ($user['is_mobile_auth'])
            $level++;
        // 2. 邮箱认证
        if ($user['is_email_auth'])
            $level++;
        // 3. 身份认证
        if ($user['is_id_auth'])
            $level++;
        // 4. 是否修改支付密码
        if (GROUP_NAME == 'Mobile2') {
            if ($user['is_paypwd_mobile_set'])
                $level++;
        } else {
            if ($user['is_paypwd_edit'])
                $level++;
        }
        // 5. 是否设置安全保护问题
        if ($user['sqa_key'] && $user['sqa_value'])
            $level++;

        // 6. 绑定银行卡
        $isBind = M("fund_account")->where(array("uid" => $uid, "is_active" => 1))->find();
        if ($isBind) {
            $user['is_bank_auth'] = 1;
            $level++;
        }
//        $level = 6;

        $level_index = round(($level - 1) / $LEVEL_DIVIDE);
        $level_index = min(count($LEVELS) - 1, $level_index);
        $level_index = max(0, $level_index);
        return array($level, $user, $LEVELS[$level_index]);
    }

    /**
     * 获取登录用户资料
     */
    function getLoginedUserInfo() {
        $obj = D("Account/User");
        return $obj->getLoginedUserInfo();
    }

    //重置登录数据
    function resetLogin($uid) {
        $obj = D("Account/User");
        $user = $this->getLoginedUserInfo();

        if (C('USC_REMOTE_SWITCH'))
            return $obj->doLogin_usc($uid, 0, $user['usc_token']);
        return $obj->doLogin($uid, 0);
    }

    /**
     * 通过uid获取
     * @param unknown $uid
     */
    function getByUid($uid) {
        $obj = D("Account/User");
        return $obj->getByUid($uid);
    }

    /**
     * 通过用户中心获取
     * @param unknown $uscId
     * @return
     */
    function getByUscId($uscId) {
        $obj = D("Account/User");
        $s_usc_id = (string)$uscId;
        return $obj->where(array("usc_id" => $s_usc_id))->find();
    }

    /**
     * 通过手机号码获取
     * @param unknown $mobile
     */
    function getUserByMobile($mobile, $uid_type=1,$is_invest_finance=1) {
        $obj = D("Account/User");
        return $obj->getUserByMobile($mobile, $uid_type,$is_invest_finance);
    }

    /**
     * 根据昵称获取用户信息
     * @param unknown $uname
     */
    function getUserByUname($uname) {
        $obj = D("Account/User");
        return $obj->where(array("uname" => $uname, "mi_no" => BaseModel::getApiKey('api_key')))->find();
    }

    /**
     * 获取头像
     * @param unknown $uid
     */
    function getAvatar($uid) {
        $obj = D("Account/UserPhoto");
        return $obj->getAvatar($uid);
    }

    /**
     * 获取头像
     * @param unknown $uid
     */
    function getAvatarNew($uid)
    {
        $obj = D("Account/UserPhoto");
        $ava = $obj->getAvatar($uid);
        $data['is_set_photo'] = isset($ava['photo_path']) && $ava['photo_path'] ? 1 : 0;
        if (isset($ava['photo_path']) && $ava['photo_path']) {
            $data['ava']['url'] = "http:" . $ava['photo_path']['attach']['url'];
            $data['ava']['url_s700'] = "http:" . $ava['photo_path']['attach']['url_s700'];
            $data['ava']['url_s300'] = "http:" . $ava['photo_path']['attach']['url_s300'];
            $data['ava']['url_s100'] = "http:" . $ava['photo_path']['attach']['url_s100'];
            $data['ava']['url_s50'] = "http:" . $ava['photo_path']['attach']['url_s50'];
        } else {
            $data['ava']['url'] = "http://" . C('SITE_DOMAIN') . "/public/image/default_02.jpg";
            $data['ava']['url_s700'] = "http://" . C('SITE_DOMAIN') . "/public/image/default_02.jpg";
            $data['ava']['url_s300'] = "http://" . C('SITE_DOMAIN') . "/public/image/default_02.jpg";
            $data['ava']['url_s100'] = "http://" . C('SITE_DOMAIN') . "/public/image/default_02.jpg";
            $data['ava']['url_s50'] = "http://" . C('SITE_DOMAIN') . "/public/image/default_02.jpg";
        }
        return $data;
    }

    /**
     * 获取邮箱登录入口网址
     * @param unknown $email
     */
    function getEmailLoginUrl($email) {
        $dominArr = explode('@', $email);
        $domin = $dominArr[1];
        $loginUrls = C("EMAIL_LOGIN_URL");
        $result = isset($loginUrls[$domin]) ? $loginUrls[$domin] : false;
        return $result ? $result : false;
    }

    /**
     * 注册
     * @param unknown $uname
     * @param unknown $password
     * @param unknown $email
     */
    function register($uname, $password, $mobile, $regClient = 1, $uid_type=1,$is_invest_finance=1) {
        $obj = D("Account/User");
        $result = $obj->register($uname, $password, $mobile, $regClient,$uid_type,$is_invest_finance);
        return $result;
    }

    /**
     * 注册
     * @param unknown $uname
     * @param unknown $password
     * @param unknown $email
     * @param unknown $ext_info
     */
    function register_web($uname, $password, $mobile, $uid_type = 1,$ext_info = array(),$is_invest_finance=1) {
        $obj = D("Account/User");
        $result = $obj->register_web($uname, $password, $mobile, $uid_type,$ext_info,$is_invest_finance);
        return $result;
    }

    /**
     * 注册 20161110，整合pc和移动
     *
     */
    public function register_vOne($uname, $password, $mobile, $uid_type = 1,$ext_info = array(),$is_invest_finance=1){
        /** @var  $obj  UserModel */
        $obj = D("Account/User");
        $uname = $obj->makeuname();

        if (!$obj->checkUname($uname)) {
            return errorReturn('用户名不能有特殊空格');
        }
        if (!$mobile) {
            return errorReturn("手机号码不能为空!");
        }
        if (!checkMobile($mobile)) {
            return errorReturn("手机号码格式错误!");
        }
        if (!$obj->checkMobileUnique($mobile, $uid_type, $is_invest_finance)) {
            return errorReturn("手机号码已经被注册！");
        }

        $uname_key = 'register_usc_' . $uname;
        Import("libs.Counter.MultiCounter", ADDON_PATH);
        $default_counter = MultiCounter::init(Counter::GROUP_DEFAULT, Counter::LIFETIME_THISI);
        $count = $default_counter->incr($uname_key);
        if ($count > 1) {
            MyError::add("已经注册过该用户名，一分钟多次回调");
            return false;
        }

        $newpwd = pwd_hash($password);
        if (C('USC_REMOTE_SWITCH')) {
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
            $uscdata["REG_TIME"] = date('Y-m-d');
            $uscdata['USERTYPE'] = $uid_type == 2 ? 'E' : 'P';
            $uscdata["DEPT_ID"] = null;
            $uscdata["PORTALCODE"] = self::$api_key;
            $uscdata['IS_INVEST_FINANCE'] = $is_invest_finance == 2 ? 2 : 1;//2015323用户中心增加字段区分投资人(默认为1)

            $uscResult = $uscapi->regUser($uscdata);
            if ($uscResult['boolen'] != 1) {
                MyError::add($uscResult['message']);
                return false;
            }
        }

        $regClient = $obj->getFrom();
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
            'is_set_uname' => $uid_type == $obj::USER_TYPE_CORP && !empty($uname) || $ext_info['set_uname'] ? 1 : 0,
            //9为B端注册
            "reg_client" => $regClient,
            'ctime' => $now,
            'mtime' => $now,
            'uid_type' => $uid_type,
            'register_ip' => get_client_ip(),
            'mi_no' => self::$api_key,
            'is_invest_finance' => $is_invest_finance//2015323增加字段区分投资人(默认为1)，融资人为2
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
        $uid = $obj->add($data);

        //2015.1.16增加机构注册
        /** @var $obj UserModel */
        if ($uid_type == $obj::USER_TYPE_CORP && $is_invest_finance == $obj::INVEST_FINANCE_TOUZI) {
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

        if ($uid_type == $obj::USER_TYPE_CORP) {
            //添加代理机构的关联标记录
            $obj->addUserRecommed($uid, $ext_info);
        }

//        p($this->_sql());
//        $this->where(array("uid" => $uid))->save(array("usc_id" => $uscId));
        //用户数据初始化
        $obj->userInit($uid, $password, 1, 2, true, $uid_type, $ext_info, $is_invest_finance);
        return $uid;
    }

    /**
     * 重发发送认证邮件
     * @param unknown $uid
     */
    function resendActiveEmail($uid, $email) {
        $obj = D("Account/User");
        $uid = intval($uid);
        $userInfo = $obj->where(array("uid" => $uid))->find();

        if (!$userInfo) {
            MyError::add("用户不存在!");
            return false;
        }

        if ($userInfo['is_email_auth'] && $email == $userInfo['email']) {
            MyError::add("邮箱已经认证,不能进行此操作");
            return false;
        }

        //关闭之前的激活数据
        $this->closeEmailLogByUid($uid, AccountService::EMAIL_LOG_TYPE_ACTIVE_EMAIL);
//        $uname = $userInfo['uname'];
        //重新发送
        return $this->sendActiveEmail($uid, $email);
    }

    /**
     * 发送认证邮件
     * @param unknown $uid
     * @param unknown $email
     * @param unknown $uname
     */
    function sendActiveEmail($uid, $email, $extras = array(), $action = 0) {
        try {
                if($action == 0){
                    $check = $this->checkEmail($email);
                    if(!$check) {
                        return false;
                    }
                }
                //每隔60秒才能发送邮件
                //BaseModel::newSession("LAST_SEND_ACTIVE_EMAIL",NULL);//调试时清空用
                $lastSendTime = (int) BaseModel::newSession("LAST_SEND_ACTIVE_EMAIL");
                if (((time() - $lastSendTime) < 60)) {
                    MyError::add("60秒后才能重发激活邮件。");
                    return false;
                }
                import_addon("libs.Email.Email");
                $activeEmail = getSysData("account", "active_email_content");
                $api_info = service('ApiServer/Member')->getApiInfo();

                $siteName = $extras['client_name'] ? $extras['client_name'] : $api_info['mi_name'];
                if($action == self::ACTION_UNBIND_EMAIL){ ////解绑邮箱
                    $token = $this->newEmailLog($email, $uid, self::UNBIND_EMAIL);
                }elseif($action == self::ACTION_FIND_PAYPWD){ //找回支付密码
                    $token = $this->newEmailLog($email, $uid, self::FIND_PAY_PWD2_ACTIVE_EMAIL);
                }else{
                    $token = $this->newEmailLog($email, $uid);
                }
                //调试
                //$data=array('email' => $email, 'uid' => $uid, 'activeEmail'=>$activeEmail, 'api_info' => $api_info, 'siteName' => $siteName );
                //ajaxReturn($data);
                //END
                $token = $token . "_" . $uid;
                if($action==self::ACTION_UNBIND_EMAIL){
                    $url = $api_info['mi_host'] . "/Account/User/unbindEmailAuth?token=" . $token;
                    $title ='鑫合汇邮箱解绑认证';
                    $content = '<p>请点击下面的链接解绑您的邮箱:</p>
	                       <p><a href="'.$url.'" target="_blank">'.$url.'</a></p>
	                       <p>(如果不能打开此链接，请将网址复制到浏览器的地址栏，来打开此页面)</p>';

                    Email::send($email, $title, $content, null, 1, '', '');
                    BaseModel::newSession("LAST_SEND_ACTIVE_EMAIL", time());
                    return true;
                }elseif($action==self::ACTION_FIND_PAYPWD){
                    $url = $api_info['mi_host'] . "/Account/User/findPayPwd2Auth?token=" . $token;
                    $title ='鑫合汇邮箱找回支付密码认证';
                    $content = '<p>请点击下面的链接找回您的支付密码:</p>
	                       <p><a href="'.$url.'" target="_blank">'.$url.'</a></p>
	                       <p>(如果不能打开此链接，请将网址复制到浏览器的地址栏，来打开此页面)</p>';

                    Email::send($email, $title, $content, null, 1, '', '');
                    BaseModel::newSession("LAST_SEND_ACTIVE_EMAIL", time());
                    return true;
                }else{
                    $url = $api_info['mi_host'] . "/Account/User/activeEmail?token=" . $token;
                    $extras['from'] && $url .= '&from='.$extras['from'];

                    $title = str_replace("#SITE_NAME#", $siteName, $activeEmail['TITLE']);
                    $content = str_replace("#SITE_NAME#", $siteName, $activeEmail['CONTENT']);
                    $content = str_replace("#URL#", $url, $content);

                    Email::send($email, $title, $content, null, 1, '', $extras['from'] ? $extras['from'] : '');
                    BaseModel::newSession("LAST_SEND_ACTIVE_EMAIL", time());
                    return true;
                }

        } catch (Exception $e) {
            MyError::add($e->getMessage());

            return false;
        }
    }

    /**
     * email是否激活
     * @param unknown $token
     */
    function isEmailActive($token) {
        $userObj = D("Account/User");
        return $userObj->isEmailActive($token);
    }

    /**
     * 激活email
     * @param unknown $token
     */
    function emailActive($token, &$data = array()) {
        $userObj = D("Account/User");
        if (!$token)
            return false;
        list($tokenStr, $uid) = $userObj->parseToken($token);
        $uid = intval($uid);
        $log = $this->getEmailToken($uid, $tokenStr);
        $result = $log ? true : false;
        $email_auth_time = $userObj->where(array('uid' => $uid))->getField('email_auth_time');
        $first_time = $email_auth_time ? false : true;
        if ($result) {
            //更新激活
            $data['uid'] = $uid;
            $data['email'] = $log['email'];
            $data['is_email_auth'] = 1;
            $result = $userObj->updateUser($data);
            if ($result['boolen']) {
                $this->resetLogin($uid);

                //YRZIF-11891 首次绑定邮箱，赠送一次免收手续费次数 20160328
                if ($first_time) {
                    //增加一次免费提现次数
                    try {
                        service('Payment/PayAccount')->freeTixianTimes($uid, 1);
                        //消息通知
                        D('Message/Message')->simpleSend($uid, 1, 4, array('奖励', '您因首次绑定邮箱，获得1次免收手续费次数。感谢您的积极参与，还有更多的精彩活动，请您敬请关注！'), array(1, 3), true);
                    } catch (Exception $e) {
                        MyError::add($e->getMessage());
                    }
                }

                //送奖励红包
                //service("Index/ActivitySet")->regSet($uid, ActivitySetService::TYPE_EMAIL_BIND);// 20160328 修改时发现此处执行不到了就删除

                return true;
            }
            MyError::add($result['message']);
            return false;
        } else {
            MyError::add("数据不存在或者已经被使用!");
        }
        return $result;
    }

    /**
     * 登录
     * @param unknown $account
     * @param unknown $password
     */
    function login($account, $password,$uid_type=1,$is_invest_finance=1) {
        $obj = D("Account/User");
        return $obj->login($account, $password,$uid_type,$is_invest_finance);
    }

    /**
     * 登录
     * @param unknown $uid
     */
    function doLogin($uid) {
        $uid = (int) $uid;
        $obj = D("Account/User");
        return $obj->doLogin($uid);
    }

    /**
     * 记住登录
     */
    function rememberLogin($username) {
        $obj = D("Account/User");
        return $obj->rememberLogin($username);
    }

    //清空记住用户名
    function unsetRememberLogin() {
        $obj = D("Account/User");
        return $obj->unsetRememberLogin();
    }

    /**
     * 检查旧密码
     * @param unknown $uid
     * @param unknown $oldPwd
     */
    function checkOldPwd($uid, $oldPwd) {
        $obj = D("Account/User");
        return $obj->checkOldPwd($uid, $oldPwd);
    }

    /**
     * 更新登录密码
     * @param unknown $uid
     * @param unknown $pwd
     * @param unknown $oldPwd
     */
    function updatePwdWithOldPwd($uid, $pwd, $oldPwd) {
        $obj = D("Account/User");
        return $obj->updatePwdWithOldPwd($uid, $pwd, $oldPwd);
    }

    /**
     * 更新密码
     * @param  [type] $uid [description]
     * @param  [type] $pwd [description]
     * @return [type]      [description]
     */
    function updatePwd($uid, $pwd) {
        $obj = D("Account/User");
        $data['uid'] = $uid;
        $data['password'] = $pwd;
        $result = $obj->updateUser($data);
        if ($result['boolen'])
            return true;
        MyError::add($result['message']);
        return false;
    }

    //更新用户
    function updateUser($data) {
        $obj = D("Account/User");
        $result = $obj->updateUser($data);
        if ($result['boolen'])
            return true;
        MyError::add($result['message']);
        return false;
    }

    /**
     * 更新手机号码
     */
    function updateMobile($uid, $mobile) {
        $uid = (int) $uid;
        $obj = D("Account/User");
        $data['uid'] = $uid;
        $data['mobile'] = $mobile;
        $result = $obj->updateUser($data);
        if ($result['boolen'])
            return true;
        MyError::add($result['message']);
        return false;
    }

    //更改用户名
    function updateUname($uid, $uname) {
        $uid = (int) $uid;
        $obj = D("Account/User");
        $data['uid'] = $uid;
        $data['uname'] = $uname;
        $data['is_change_uname'] = 0;
        $result = $obj->updateUser($data);
        if ($result['boolen']) {
            $this->resetLogin($uid);
            return true;
        }
        MyError::add($result['message']);
        return false;
    }

    /**
     * 手机号码认证
     */
    function authMobile($uid, $mobile) {
        $uid = (int) $uid;
        $obj = D("Account/User");

        $data['uid'] = $uid;
        $data['mobile'] = $mobile;
        $data['is_mobile_auth'] = 1;
        $result = $obj->updateUser($data);
        if ($result['boolen'])
            return true;
        MyError::add($result['message']);
        return false;
    }

    /**
     * 检查旧支付密码
     */
    function checkOldPayPwd($uid, $oldPwd) {
        $obj = D("Account/UserAccount");
        return $obj->checkOldPwd($uid, $oldPwd);
    }

    /**
     * 检查支付密码
     * @param unknown $uid
     * @param unknown $oldPwd
     */
    function checkPayPwd($uid, $oldPwd) {
        return $this->checkOldPayPwd($uid, $oldPwd);
    }

    /**
     * 更新支付密码
     * @param unknown $uid
     * @param unknown $pwd1
     */
    function updatePayPwd($uid, $pwd) {
        $obj = D("Account/UserAccount");
        return $obj->updatePwd($uid, $pwd);
    }

    /**
     * 检查昵称
     * @param unknown $username
     * @return boolean
     */
    function checkUname($username) {
        if (!$username) {
            MyError::add("参数错误");
            return false;
        }

        $userObj = D("Account/User");
        $data['uname'] = $username;
        if ($userObj->create($data)) {
            return true;
        } else {
            MyError::add($userObj->getError());
            return false;
        }
    }

    /**
     * 检查email
     * @param unknown $email
     * @return boolean
     */
    function checkEmail($email) {
        if (!$email) {
            MyError::add("参数错误");
            return false;
        }

        $userObj = D("Account/User");
        $data['email'] = $email;
        if ($userObj->create($data)) {
            return true;
        } else {
            MyError::add($userObj->getError());
            return false;
        }
    }

    /**
     * 检查身份证
     * @param unknown $personId
     */
    function checkPersonId($personId) {
        if (!$personId) {
            MyError::add("参数错误");
            return false;
        }

        $userObj = D("Account/User");
        $data['person_id'] = $personId;
        if ($userObj->create($data)) {
            return true;
        } else {
            MyError::add($userObj->getError());
            return false;
        }
    }

    /**
     * 检查手机号码
     * @param $mobile
     * @return bool
     */
    function checkMobile($mobile,$uid_type=1,$is_invest_finance=1) {
        if (!$mobile) {
            MyError::add("参数错误");
            return false;
        }

        $userObj = D("Account/User");
        $data['mobile'] = $mobile;
        $data['mi_mo'] = self::$api_key;
        
        $d['uid_type']=$uid_type;
        $d['is_invest_finance']=$is_invest_finance;
        $key_uid_type='check_uid_type';
        BaseModel::newSession($key_uid_type,$d);
        //BaseModel::newSession($key_uid_type,$uid_type);

        $result=true;

        //针对聚财富的特殊提示语做的判断提前
        if(self::$api_key == '1234567992' && !$userObj->checkMobileUnique($mobile)){
            MyError::add("该手机号已被注册，初始登录密码为jcf+手机后六位");
            return false;
        }

        if (!$userObj->create($data)) {
            // if(!$userObj->checkMobileUnique($mobile,$uid_type)){
            //     MyError::add('手机号码已经被注册');
            //     return false;
            // }
            MyError::add($userObj->getError());
            $result=false;
        }

            BaseModel::newSession($key_uid_type,null);
            return $result;
    }

    /**
     * 检查密码
     * @param unknown $pwd1
     */
    function checkPwd($pwd1) {
        if (!$pwd1) {
            MyError::add("参数错误");
            return false;
        }

        $userObj = D("Account/User");
        $data['newpassword'] = $pwd1;
        if ($userObj->create($data)) {
            return true;
        } else {
            MyError::add($userObj->getError());
            return false;
        }
    }

    /**
     * 2016229 修改支付密码时,先检查支付密码，支付密码是6位数字
     * @param unknown $pwd1
     */
    function checkPaymentPwd($pwd1) {
        if (!$pwd1) {
            MyError::add("参数错误");
            return false;
        }

        $userObj = D("Account/UserAccount");
        $data['pay_password'] = $pwd1;
        if ($userObj->create($data)) {
            return true;
        } else {
            MyError::add($userObj->getError());
            return false;
        }
    }

    /**
     * 通过email获取
     * @param unknown $email
     */
    function getByEmail($email) {
        $userObj = D("Account/User");
        return $userObj->getByEmail($email);
    }

    /**
     * 退出登录
     */
    function logout() {
        $obj = D("Account/User");
        return $obj->logout();
    }

    /**
     * 自动登录
     */
    function tokenLogin($token = '', $uid_type=1,$is_invest_finance=1) {
        $obj = D("Account/User");
        if ($token && C('USC_REMOTE_SWITCH')) {
            $result = $obj->login_usc("", '', $token,$uid_type,$is_invest_finance);
            MyError::clear();
            return $result;
        } else {
            MyError::add("未接入用户中心，不支持根据token自动登录!");
            return false;
        }

        // return $obj->autoLogin();
    }

    //获取登录用户名
    function getLoginUseName() {
        return D("Account/User")->getLoginName();
    }

    /**
     * 判断是否登录
     */
    function hasLogined() {
        return $this->getLoginedUserInfo() ? true : false;
    }

    /**
     * 上传头像
     */
    function uploadAvatar($path = '') {
        import("ORG.Net.UploadFile");
        $savePath = UPLOAD_PATH . "/image";
        if (!is_dir($savePath))
            mkdir($savePath, 0777, true);
        if (!$path)
            $path = $savePath . "/avatar/";
        //导入上传类
        $upload = new UploadFile();
        //设置上传文件大小
        $upload->maxSize = 3292200;
        //设置上传文件类型
        $upload->allowExts = explode(',', 'jpg,gif,png,jpeg');
        //设置附件上传目录
        $upload->savePath = $path;
        //设置需要生成缩略图，仅对图像文件有效
        $upload->thumb = false;
        $upload->autoSub = true;
        //设置上传文件规则
        $upload->saveRule = 'uniqid';
        $upload->thumbRemoveOrigin = true;
        if (!$upload->upload()) {
            //捕获上传异常
            MyError::add($upload->getErrorMsg());
        } else {
            //取得成功上传的文件信息
            $uploadList = $upload->getUploadFileInfo();
            $uploadInfo = $uploadList[0];
            $imgPath = $uploadInfo['savepath'] . $uploadInfo['savename'];
            return $imgPath;
        }
    }

    /**
     * 裁剪
     * @param unknown $imgPath
     * @param unknown $newPath
     * @param unknown $w
     * @param unknown $h
     * @param unknown $x
     * @param unknown $y
     */
    function crop($imgPath, $newPath, $w, $h, $x, $y) {
        try {
            $pathDir = dirname($newPath);
            if (!is_dir($pathDir))
                mkdir($pathDir, 0777, true);
            $savePath = UPLOAD_PATH . "/image";
            //开始剪裁图片
            import("ORG.Util.Image.ThinkImage");
            $img = new ThinkImage(THINKIMAGE_GD, $imgPath);
            $img->crop($w, $h, $x, $y)->save($newPath);

            $newPath = str_replace($savePath, "", $newPath);
            return $newPath;
        } catch (Exception $e) {
            MyError::add($e->getMessage());
            return false;
        }
    }

    /**
     * 等比压缩
     * @param unknown $imgPath
     * @param unknown $newPath
     * @param unknown $width
     * @param unknown $height
     * @return mixed|boolean
     */
    function thumb($imgPath, $newPath, $width, $height) {
        try {
            $pathDir = dirname($newPath);
            if (!is_dir($pathDir))
                mkdir($pathDir, 0777, true);

            $savePath = UPLOAD_PATH . "/image";
            //开始剪裁图片
            import("ORG.Util.Image.ThinkImage");
            $img = new ThinkImage(THINKIMAGE_GD, $imgPath);
            $img->thumb($width, $height, THINKIMAGE_THUMB_SCALING)->save($newPath);

            $newPath = str_replace($savePath, "", $newPath);
            return $newPath;
        } catch (Exception $e) {
            MyError::add($e->getMessage());
            return false;
        }
    }

    /**
     * 保存或者更新头像
     */
    function saveOrUpdateAvatar($uid, $newPath) {
        //更新头像信息
        $photoObj = D("Account/UserPhoto");
        $photoObj->saveOrUpdateAvatar($uid, $newPath);
        return true;
    }

    /**
     * 绑定的银行
     */
    function getBindBanks($uid) {
        $uid = (int) $uid;
        $list = M("fund_account")->where(array("uid" => $uid,"zs_bankck"=>0))->select();

        $srvPayment = service('Payment/Payment');
        $bankList = PaymentService::$bankList;
        if ($list) {
            $obj = D('Account/UserAccount');
            foreach ($list as $key => $value) {
                $bankCode = $value['bank'];
                foreach ($bankList as $bk => $bv) {
                    if ($bv['myCode'] == $bankCode) {
                        $list[$key]['style'] = $bv['style'];
                        break;
                    }
                }
                $list[$key]['autocashout'] = $obj->isBankCardInfoFull($uid, array($value['bank'], $value['sub_bank_id'], $value['bank_province'], $value['bank_city']));
                $list[$key]['account_no'] = bank_no_view($list[$key]['account_no']);
                $list[$key]['acount_name'] = str_replace(' ', '', $list[$key]['acount_name']);
            }
        }
        return $list;
    }

    //$obj = D("Account/User");
    /**
     * 设置用户离线
     * @param unknown $uid
     */
    function setUserOffline($uid) {
        $obj = D("Account/User");
        return $obj->setUserOffline($uid);
    }

    //是否需要验证支付密码
    function isNeedPayPwd() {
        import_app("Account.Model.UserModel");
        $user = $this->getLoginedUserInfo();
        return $user['login_type'] == UserModel::LOGIN_TYPE_MOBILE_SMS;
    }

    //根据项目获取用户商户账户id
    public function getTenantIdByPrj($prj) {
        if ($prj['tenant_id']) {
            return $prj['tenant_id'];
        } elseif ($prj['dept_id']) {
            $merchant_id = service("Payment/PayAccount")->getTenantAccountId($prj['dept_id']);
            return $merchant_id;
        } else {
            throw_exception("商户账户不存在");
        }
    }

    //获取用户商户账户id
    public function getTenantIdByUser($user) {
        $user_account = M('user_account')->find($user['uid']);
        if ($user_account['is_merchant']) {
            return $user['uid'];
        } elseif ($user['dept_id']) {
            $merchant_id = service("Payment/PayAccount")->getTenantAccountId($user['dept_id']);
            return $merchant_id;
        } else {
            return service("Application/ProjectManage")->getTenantByUid($user['uid']);
        }
    }

    /**
     * 用户中心注册
     * @param string $uname 用户名
     * @param string $password 密码
     */
    public function uscRegister($uname, $password, $apiKey,$uid_type=1) {
        //先注册用户中心
        import_addon('libs.Usc.UscApi');
        $uscapi = new UscApi(C('USC_TYPE'));
        $uscdata['USERNAME'] = $uname;
        $uscdata['USERPWD'] = pwd_md5($password);
        $uscdata['REALNAME'] = '';
        $uscdata['EMAIL'] = '';
        $uscdata['MOBILE_NO'] = '';
        $uscdata['IDCARD_NO'] = '';
        $uscdata['SEX'] = 0;
        $uscdata['PROVINCE'] = '';
        $uscdata['CITY'] = '';
        $uscdata['AREA'] = '';
        $uscdata['ADDRESS'] = '';
        $uscdata['IF_REAL_AUTH'] = 0;
        $uscdata['IF_EMAIL_AUTH'] = 0;
        $uscdata['IF_MOBILE_AUTH'] = 1;
        $uscdata['REG_TIME'] = date('Y-m-d');
        $uscdata['USERTYPE'] = $uid_type==2 ?'E':'P';////2015327 类型 1为个人对应用户中心是P 2为企业对应用户中心是E 
        //$uscdata['USERTYPE'] = 'P';
        $uscdata['DEPT_ID'] = null;
        $uscdata['PORTALCODE'] = $apiKey; //TODO
        $uscdata['IS_INVEST_FINANCE'] = 2;//2015327增加字段区分投资人(默认为1)，融资人为2
        $uscResult = $uscapi->regUser($uscdata);

        return $uscResult;
    }

    /**
     * 获取简单的用户信息
     */
    public function getSimpleUserInfo($uid) {
        $userInfo = $this->getByUid($uid);
        //补齐星号
        $userInfo['person_id'] = person_id_view_prefix($userInfo['person_id']);
        $userInfo['real_name'] = marskName($userInfo['real_name'],1,0);
        return $userInfo;
    }

    //2015.1.16 添加机构基本信息
    public function setCorpinfo($data,$update_ext=1){
        $userInfo = $this->getLoginedUserInfo();

        // $m=M('user_extends_crop');
        // if($m->where('busin_license_No'=>$data['busin_license_No'])->count()>0){
        //     throw_exception('营业执照号已存在');
        // }
        // if($m->where('org_name'=>$data['org_name'])->count()>0){
        //     throw_exception('公司名称已经存在');
        // }
        // if($m->where('org_code'=>$data['org_code'])->count()>0){
        //     throw_exception('组织代码号已经存在');
        // }

        if(!empty($userInfo)){
            M('user_extends_crop')->where(array('uid'=>$userInfo['uid']))->save($data);
            if($update_ext==1) M('user_extends_cropext')->where(array('uid'=>$userInfo['uid']))->save($data);
            return true;
        }
    }

    //2015.1.16 取出机构基本信息(包括附件的)
    public function getCorpinfo(){
        $userInfo = $this->getLoginedUserInfo();
        if(!empty($userInfo)){
            //M('fi_user_extends_crop')->where('uid'=>$userInfo['uid'])->find();
           $result = M()->table('fi_user_extends_crop c')
                     ->join('left join fi_user_extends_cropext u on c.uid=u.uid')
                     ->where(array('c.uid'=>$userInfo['uid']))
                     ->find();
            $result['uid'] = $userInfo['uid'];
            $result['uname'] = $userInfo['uname'];
            $result['ctime'] = $userInfo['ctime'];
            return $result;
        }
        return false;
    }
     
     //2015.1.20 企业撤销后，状态企业基本信息数据清空
     public function editCorpinfo($uid){

            $dataup['org_name']='';
            $dataup['busin_license_No']='';
            $dataup['business_term_date']=NULL;
            $dataup['business_term']=NULL;
            $dataup['province_id']=NULL;
            $dataup['city_id']=NULL;
            $dataup['area_province']='';
            $dataup['area_city']='';
            $dataup['mailing_addr']='';
            $dataup['phone']='';
            $dataup['org_code']='';
            $dataup['legal_representative_land']='';
            $dataup['legal_representative_name']='';
            $dataup['agent_representative_land']='';
            $dataup['agent_representative_name']='';
            $dataup['legal_name']='';
            $dataup['legal_person_id']='';
            $dataup['agent_representative_realname']='';
            $dataup['agent_representative_personcode']='';
            $dataup['status']=1;//更新状态
            $dataup['step']='';//步骤清空
            $dataup['mtime']=time();

            $result=M('user_extends_crop')->where(array('uid'=>$uid))->save($dataup);

            $dataupext['stamp_license_copy']='';
            $dataupext['stamp_organization_code_copy']='';
            $dataupext['stamp_personcode_front_legal']='';
            $dataupext['stamp_personcode_back_legal']='';
            $dataupext['stamp_personcode_front_agent']='';
            $dataupext['stamp_personcode_back_agent']='';
            $resultext=M('user_extends_cropext')->where(array('uid'=>$uid))->save($dataupext);
            // return  $resultext;
            //return $result && $resultext;
            return true;
           

     }

    /**
     * 获取云融资平台的按资产排序百分比v_fi_rep_accnt_stat
     * @return mixed|string
     */
    public function getData($uid)
    {
        $result = array();
        $ret = M('rep_user_account_rank')->where(array('uid' => $uid))->find();

        if ($ret['profit_rank']) {
            $result['rank'] = $ret['profit_rank'];
        }
        return $result;
    }

    /**
     * 获取云融资平台的赚取的收益所有人历史累计FI_PRJ_ST.ALL_PROFIT
     * @return mixed|string
     */
    public function getProfitAllData(){
        openMasterSlave();
        $cacheKey = "ProfitAllData";
        $data = S($cacheKey);
        $result = array();
        
        if(empty($data)){
            $ret = M('rep_prj_st')->order('id desc')->find();
            $result['all_profit'] = $ret['all_profit'];
            S($cacheKey, $result, 86400);
        }else{
            $result = $data;
        }
        return $result;
    }

    /**
     * 发送邮件到指定的用户
     * Exception
     */
    public function pushMessageToCheck($uname,$ctime)
    {
        $email_group = C("CROP_REG_CHECK_TIPS_EMAIL");
        $env = C('APP_STATUS');
        if ($env == "product") {
            $emails = $email_group['prod'];
        } else {
            $emails = $email_group['test'];
        }
        if (!is_array($emails)) {
            return false;
        }
        import_addon("libs.Email.Email");
        $title = "机构注册审核";
        $content_tpl = "机构用户[UNAME]于[TIME]提交注册，请及时审核资料。";
        $time = date("Y年m月d日 H:i:s",$ctime);
        $content = str_replace(array('[UNAME]', '[TIME]'), array($uname, $time), $content_tpl);
        if ($env != 'product') {
            $add_log = D("Public/Exception")->save(array('title' => $title, 'content' => $content), 'crop_reg_check_tips');

        }
        return Email::send($emails, $title, $content, null, 0);
    }

    /**解绑邮箱激活链接
     * @param $token
     * @param array $data
     * @return bool
     */
    function unbindEmailActive($token, &$data = array()) {
        $userObj = D("Account/User");
        if (!$token)
            return false;
        list($tokenStr, $uid) = $userObj->parseToken($token);
        $uid = intval($uid);
        $log = $this->getEmailToken($uid, $tokenStr,self::UNBIND_EMAIL);
        $result = $log ? true : false;
        if ($result) {
            $data['email'] = '';
            $data['is_email_auth'] = 0;
            $ret = M('user')->where(array('uid'=>$uid))->save($data);
            if ($ret) {
                $obj = D("Account/User");
                $data['uid'] = $uid;
                $obj->updateUser($data);
                return true;
            }
            MyError::add("解绑邮箱失败！");
            return false;
        } else {
            MyError::add("数据不存在或者已经被使用!");
        }
        return $result;
    }

    /**采用邮箱找回支付密码
     * @param $token
     * @return bool
     */
    function findPayPwd2Active($token){
        $userObj = D("Account/User");
        if (!$token)
            return false;
        list($tokenStr, $uid) = $userObj->parseToken($token);
        $uid = intval($uid);
        $log = $this->getEmailToken($uid, $tokenStr, self::FIND_PAY_PWD2_ACTIVE_EMAIL);
        $result = $log ? true : false;
        if ($result) {
            return true;
        } else {
            MyError::add("数据不存在或者已经被使用!");
            return false;
        }
        return $result;
    }

    public function basicUserInfo($userInfo){
        if(!$userInfo){
            return;
        }
        //邀请好友数量
        $key_friends_amount = 'friends_amount_'.$userInfo['uid'];
        if(!S($key_friends_amount)){
            $friends_amount = M('user_recommed')->where(array('from_uid'=>$userInfo['uid']))->count();
            S($key_friends_amount,$friends_amount,86400);
        }
        $friends_amount = S($key_friends_amount);
        //银行卡数量
        $key_bank_amount = 'bank_amount_'.$userInfo['uid'];
        if(!S($key_bank_amount)){
            $bank_amount = M('fund_account')->field('count(1) as count')->where(array("uid"=>$userInfo['uid'],"zs_bankck"=>'0'))->find();
            S($key_bank_amount,$bank_amount,86400);
        }
        $bank_amount = S($key_bank_amount);
        //最近一次登录的时间
        $ctime = $userInfo['before_logintime'] ? $userInfo['before_logintime'] : time();
        $uname = $userInfo['uname'];
        $mobile = $userInfo['mobile'];
        $device = $userInfo['before_loginclient'] ? $userInfo['before_loginclient'] : visit_source();
        $message_amount = D('Message/Message')->getMiniUnreadNumber($userInfo['uid']);
        $ava = service("Account/Account")->getAvatar($userInfo['uid']);
        $safeLevel = service("Account/Account")->computeSafeLevel($userInfo['uid']);
        $verifyInfo = $safeLevel[1];
        if($verifyInfo['sqa_key'] && $verifyInfo['sqa_value']){
            $is_sqa_auth = 1;
        }

        return array(
            'uname'=>$uname,
            'mobile'=>$mobile,
            'friends_amount'=>$friends_amount,
            'bank_amount'=>$bank_amount['count'],
            'ctime'=>$ctime,
            'device'=>$device,
            'message_amount'=>$message_amount,
            'ava'=>$ava,
            'isIdAuth'=>$verifyInfo['is_id_auth'],
            'isEmailAuth'=>$verifyInfo['is_email_auth'],
            'isPaypwdEdit'=>$verifyInfo['is_paypwd_edit'],
            'isBankAuth'=>$verifyInfo['is_bank_auth'],
            'isSqaAuth'=>$is_sqa_auth,
            'is_set_uname'=>$userInfo['is_set_uname'],//20160315 是否设置过用户名
            'uid_type'=>$userInfo['uid_type'],
            'isXinPartner'=>$userInfo['is_xpd'] ? 1 : D('Account/XinPartner')->getXpdStatus($userInfo['uid']),
            'principle'=>service('Account/XinPartner')->getyxPrincipal($userInfo['uid'])/100,

        );
    }

    /**首页重要公告
     * @return mixed
     */
    function showImportantNews(){
        $user =  D("Account/User")->getLoginedUserInfo();
        $uid = $user['uid'];
        $redis = \Addons\Libs\Cache\Redis::getInstance('default', false);

        $where = array('news_type'=>1,'announce_type'=>1);
        $ret = M('news')->where($where)->order('id desc')->select();
        $viewed_key = 'newsViewed'.$uid;
        if($ret){
            foreach($ret as $key=>$val){
                if($redis->sIsMember($viewed_key,$val['id'])){
                    unset($ret[$key]);
                }
            }
            return array_values($ret);
        }else{
            return array();
        }
    }


    // 20160301 注册后用户的支付密码是空，用户要设置支付密码，支付密码和手机端统一为6位数字
    public function setPayPassword($uid, $password) {
        if(!$uid) throw_exception('请指定uid');

        if(!preg_match('/^\d{6}$/', $password)) throw_exception('支付密码只能是6位数字');

        $mdUser = M('user');
        $where = array('uid' => $uid);
        $user = $mdUser->where($where)->find();
        if(!$user) throw_exception('用户不存在');

        $mdUserAccount = M('user_account');
        $password = pwd_md5($password);

        $return = $mdUserAccount->where($where)->save(array('pay_password' => $password,'pay_password_mobile' => $password));
        if(!$user['is_paypwd_mobile_set'] || !$user['is_paypwd_edit']) $return = $mdUser->where($where)->save(array('is_paypwd_mobile_set' => 1,'is_paypwd_edit' => 1));
        if($return === FALSE) throw_exception('设置支付密码失败');

        return TRUE;
    }


    // 20160307 用户帐户待收，已赚修改
    public  function  getFreezeAmount($uid,$status){
        return 0; // TODO: Q4二期暂时还原
        $where['g.uid']=$uid;
        $where['g.type']=array('in','1,2');
        $where['p.status']=$status;
        if($status==2) {
            $result = M()->table('fi_prj_order p')->join('inner join fi_user_bonus_freeze g on p.id=g.order_id')->where($where)->sum('amount');
        }else{
//            $result = M()->table('fi_prj_order p')->join('inner join fi_user_bonus_freeze g on p.id=g.order_id')->where($where)->sum('(case p.repay_status when 3 then 0 else g.amount end)'); // TODO: Q4二期暂时还原(到时候要注释掉下面的)
            $result = M()->table('fi_prj_order p')->join('inner join fi_user_bonus_freeze g on p.id=g.order_id')->where($where)->sum('(case p.repay_status when 3 then g.amount*(-1) else g.amount end)');
        }
        return $result;
    }

    //2016321 项目已生成还款计划但未支付，项目奖励以及加息券部分的年化收益不展示为待收收益
    public function getRewardingAmount($uid)
    {
        return 0; // TODO: Q4二期暂时还原

        $model = M("prj_order_reward");
        $where = array(
            'fi_prj_order_reward.uid' => $uid,
            'fi_prj_order_reward.status' => 1,
            'fi_prj_order_reward.reward_type' => array('in','1,3'),//3表示加息券
            'fi_prj_order.status' => 2,
        );

        $join = array(
            'fi_prj_order on fi_prj_order_reward.order_id = fi_prj_order.id',
        );
        $result = $model
            ->where($where)
            ->join($join)
            ->sum('fi_prj_order_reward.amount');
        return $result;
    }

    public function getOnePlatformUserAccount($minAmmount=0)
    {
        $all_platform_uids = D('Account/UserExt')->getPlatformUids();

        if (!$all_platform_uids) {
            return null;
        }

        $user_accounts = D('Account/UserAccount')->getMultiUserAccountByUids($all_platform_uids);

        if ($user_accounts) {
            foreach ($user_accounts as $account) {
                if ($account['amount'] > $minAmmount) return $account;
            }
        }

        return null;
    }
    //处理浙商存管账户信息到鑫合汇
    public function zsAccountToUpg($params){
        $result = D('Account/UserAccount')->zsAccountToUpg($params);
        return $result;
    }
    //是否开通存管户
    public function checkCgAccount(){
        $userInfo = $this->getLoginedUserInfo();
        $uid = $userInfo['uid'];
        if(C('name_list_open')){
            $userData = M('white_name_list')->where(array('uid'=>$uid,'status'=>'2','type'=>'1'))->find();
            if(!$userData){
                return false;
            }
            $ret = M('user')->where(array('uid'=>$uid))->getField('zs_status');
            if($ret == self::OPENED_ACCOUNT){
                return false;
            }
            return true;
        }
        return true;

    }
    //查询注册手机号和银行预留手机号
    public function queryMobile(){
        $userInfo = $this->getLoginedUserInfo();
        $mobiles = array();
        $regMobile = $userInfo['mobile'];
        array_push($mobiles,$regMobile);
        $uid = $userInfo['uid'];
        $where = array();
        $where['uid'] = $uid;
        $where['mobile'] = array('exp','is not null');
        $fundMobiles = M('fund_account')->distinct(true)->field('mobile')->where($where)->select();
        if($fundMobiles){
            foreach($fundMobiles as $key=>$val){
                array_push($mobiles,$val['mobile']);
            }
        }
        return array_unique($mobiles);
    }
    //切换免验证码投资同步用户状态
    public function modifyUserAutoInvestCode($flag=0){
        $userInfo = $this->getLoginedUserInfo();
        $uid = $userInfo['uid'];
        $data = array();
        //flag = 1 代表开启短信验证 =0 代表关闭短信验证; zs_close_invest_code = 1 代表关闭短信验证 = 0 代表开启短信验证;
        $data['zs_close_invest_code'] = $flag ? 1 : 0;
        $ret = M('user')->where(array('uid'=>$uid))->save($data);
        if($ret){
            D("Account/User")->addUserloginsession('zs_close_invest_code',$data['zs_close_invest_code']);
            ajaxReturn('','修改成功');
        }
        ajaxReturn('','操作异常请重试','0');
    }
    //修改浙商银行卡同步数据到upg
    public function bankCardToUpg($params){
        $result = D('Account/UserAccount')->bankCardToUpg($params);
        return $result;
    }

    //普通户还是浙商户或者同时并存
    public function userStatus( $userInfo )
    {
        $fund_account = M("fund_account")->where(array("uid" => $userInfo['uid'], "is_active" => 1, 'zs_bankck' => 0))->find();
        $arr['xhh_status'] = $fund_account ? 1 : 0;
        if($userInfo['is_init'] == 1){
            $arr['xhh_status'] = 1;//内部账户放开
        }
        $arr['zs_status'] = $userInfo['zs_status'] == 2 ? 1 : 0;
        $arr['two_status'] = $arr['xhh_status'] * $arr['zs_status'];
        $if_white_name = service("Account/Account")->checkNameListUser( $userInfo['uid']);
        $arr['if_white_name'] = $if_white_name ? 1 : 0;
        $arr['is_id_auth'] = $userInfo['is_id_auth'] ? 1 : 0;
        $public_test_time = service('Payment/PayAccount')->getPayment( 'public_test_time' );
        $stime = time();
        $arr['public_test_status'] = (strtotime($public_test_time['start_time']) <= $stime && $stime <= strtotime($public_test_time['end_time'])) ? 1 : 0;
        $arr['public_apply_url'] = service('Payment/PayAccount')->getPayment( 'public_apply_url' );
        return $arr;
    }
    /**
     * 检测手机号是否注册投资人
     * @param $phone  手机号
     * @return bool   1-未注册 0- 注册
     */
    public function checkMobileRegister($phone)
    {
        if(empty($phone))
            return $this->setError('参数错误');

        if(checkMobile($phone) == false)
            return $this->setError('请输入正确的手机号码');

        import_addon("libs.Usc.UscApi");
        $uscapi = new UscApi(C('USC_TYPE'));
        $data['MOBILE'] = $phone;
        $data['PORTALCODE'] = self::$api_key;
        $data['USERTYPE'] ='P';
        $data['IS_INVEST_FINANCE']='1';
        $result = $uscapi->regHasUserByMobile($data);
        if($result['boolen'] == 1)
            return $this->setError('手机号已经注册');
        $accountModel = D('Account/User');
        $userInfo = $accountModel->getUserByAttributes(array('mobile'=>$phone,'uid_type'=>UserModel::USER_TYPE_PERSON,'is_invest_finance'=>UserModel::INVEST_FINANCE_TOUZI));

        return (empty($userInfo['mobile']) || $userInfo['mobile'] == 'null') ? true : false;
    }
    /**
     * 更改银行预留手机号
     * @param $uid  用户uid $mobile 手机号
     * @return bool   true 修改成功 false 修改失败
     */
    public function editBankMobile($uid,$mobile){
        $db = D('Account/User');
        $db->startTrans();
        $arr = array();
        $arr['uid'] = $uid;
        $arr['zs_bank_mobile'] = $mobile;
        $result = $db->updateUser($arr);
        if (!$result['boolen']){
            MyError::add($result['message']);
            $db->rollback();
            return false;
        }
        $result = M('fund_account')->where(array('uid'=>$uid,'zs_bankck'=>1))->save(array('mobile'=>$mobile,'mtime'=>time()));
        if(!$result){
            MyError::add('更新预留手机号失败:1');
            $db->rollback();
            return false;
        }
        $result = M('recharge_bank_no')->where(array('uid'=>$uid,'zs_bankck'=>1))->save(array('mobile'=>$mobile,'mtime'=>time()));
        if(!$result){
            MyError::add('更新预留手机号失败:2');
            $db->rollback();
            return false;
        }
        $db->commit();
        return true;
    }
    /**
     * 确保浙商开户身份证号唯一
     * @param $person_id  用户身份证号
     * @return bool   true 未开户 false 已开户
     */
    public function checkIdCardUnique($person_id){
        $ret = M('user')->where(array('person_id'=>$person_id,'zs_status'=>'2'))->find();
        if($ret){
            MyError::add('该身份证已经开户');
            return false;
        }
        return true;
    }
    /**
     * 存管项目是否在白名单
     * @param $uid  用户id
     * @return bool   true 在白名单 false 不在白名单
     */
    public function checkNameListUser($uid){
        $userData = M('white_name_list')->where(array('uid'=>$uid,'status'=>'2','type'=>'1'))->find();
        if(!$userData){
            return false;
        }
        return true;
    }

}

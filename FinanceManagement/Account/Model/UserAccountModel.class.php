<?php

class UserAccountModel extends BaseModel
{
    const OPENING_ACCOUNT = 1;
    const OPENED_ACCOUNT = 2;
    protected $tableName = 'user_account';
    protected $_validate = array(
        #密码
        array("pay_password", "require", "支付密码不能为空!", self::EXISTS_VALIDATE),
//        array("pay_password", "6,16", "支付密码长度必须为6-16个字符!", self::EXISTS_VALIDATE, "length"),
//        array('pay_password', 'checkPwdMake', '支付密码至少包含数字，字母（区分大小写），特殊符号中的2种！', self::EXISTS_VALIDATE, 'callback'),
        array("pay_password", "6", "支付密码长度必须为6位!", self::EXISTS_VALIDATE, "length"),
        array('pay_password', 'number', '支付密码必须是6位数字！', self::EXISTS_VALIDATE),

    );

    protected $_auto = array(
        array('pay_password', 'pwd_md5', self::MODEL_BOTH, 'function'),
        array('ctime', 'time', self::MODEL_INSERT, 'function'),
        array('mtime', 'time', self::MODEL_BOTH, 'function'),
    );

    function checkPwdMake($pay_password)
    {
        $number = (int)preg_match("/^.*\d.*$/", $pay_password);
        $word = (int)preg_match("/^.*[a-z].*$/i", $pay_password);
        $character = (int)preg_match("/^.*\W.*$/i", $pay_password); //特殊字符 ,非字母数字
        $result = $word + $number + $character;
        return $result > 1;
    }



    /**
     * 检查旧密码
     * @param unknown $uid
     * @param unknown $oldPwd
     * @return boolean
     */
    function checkOldPwd($uid, $oldPwd)
    {
        $uid = intval($uid);
        $oldPwd = pwd_md5($oldPwd);
        return $this->where(array("uid" => $uid, "pay_password" => $oldPwd))->find() ? true : false;
    }

    /**
     * 修改密码
     * @param unknown $uid
     * 2016229 增加pay_password_mobile 手机端，使pc支付密码和手机端一样。并且更改is_paywd_mobile_set状态为1.
     */
    function updatePwd($uid, $pwd)
    {
        $uid = intval($uid);
        $data['pay_password'] = $pwd;
        $data['pay_password_mobile'] = pwd_md5($pwd);
        if ($this->create($data)) {
            $this->where(array("uid" => $uid))->save();
            M('user')->where(array('uid' => $uid))->save(array('is_paypwd_edit' => 1,'is_paypwd_mobile_set'=>1));
            return true;
        } else {
            MyError::add($this->getError());
            return false;
        }
    }

    /*
     * 银行卡管理
     * zhangxiaoyan
  	 *
     */

    public function  getFundAccountList($condition, $order, $pagesize)
    {
        $order = empty($order) ? ' a.id desc' : $order;
        $where = '';
        if (!empty($condition['uname'])) { //用户名
            $where .= " and b.uname like '%" . $condition['uname'] . "%'";
        }
        if (!empty($condition['real_name'])) { //真实名称
            $where .= " and b.real_name like '%" . $condition['real_name'] . "%'";
        }
        if (!empty($condition['email'])) { //邮箱
            $where .= " and b.email like '%" . $condition['email'] . "%'";
        }
        $sql = "SELECT a.*,b.uname,b.real_name,b.email,b.person_id FROM  fi_fund_account a
		          LEFT JOIN fi_user b ON a.uid=b.uid AND b.mi_no=".self::$api_key."
		          WHERE a.is_active=1 and b.is_del=0 " . $where . " order by " . $order;
        $data = M('fund_account')->findPageBySql($sql, null, null, $pagesize);
        return $data;
    }

    /*
     * 用户列表
     * zhangxiaoyan
     * 
     */
    public function getUserList($condition, $order = 'uid desc', $pagesize = 10, $parameter = array())
    {
// 		$where = ' is_del=0 AND is_init IS NULL ';
        $where = array(
            'mi_no' => self::$api_key,
        );
        $where['is_del'] = '0';
        $where['is_init'] = array('exp', ' is NULL');;
        if (!empty($condition['uname'])) { //用户名
//     		$where .=" and uname like '%".$condition['uname']."%'";   
            $where['uname'] = array('like', "%" . $condition['uname'] . "%");
        }
        if (!empty($condition['real_name'])) { //真实名称
//     		$where .=" and real_name like '%".$condition['real_name']."%'";   
            $where['real_name'] = array('like', "%" . $condition['real_name'] . "%");
        }
        if (!empty($condition['email'])) { //邮箱
//     		$where .=" and email like '%".$condition['email']."%'";   
            $where['email'] = array('like', "%" . $condition['email'] . "%");
        }
        if (!empty($condition['mobile'])) { //手机号
//             $where .=" and mobile ='".$condition['mobile']."'";
            $where['mobile'] = $condition['mobile'];
        }

        if (!empty($condition['from_uid'])) {
            $where['from_uid'] = $condition['from_uid'];
        }

        if ($condition['is_active'] == 1) { //激活
//     		$where .=" and is_active =1";   
            $where['is_active'] = '1';
        } else if ($condition['is_active'] === '0') { //未激活
            $where['is_active'] = '0';
        }
        if ($condition['is_xia_recharge'] == 1) { //激活
            $where['is_xia_recharge'] = '1';
        } else if ($condition['is_xia_recharge'] == 2) {
            $where['is_xia_recharge'] = '2';
        }

        if ($condition['is_id_auth'] == 1) {
            $where['is_id_auth'] = '1';
        }
        if ($condition['is_mobile_auth'] == 1) {
            $where['is_mobile_auth'] = '1';
        }
        if ($condition['_string']) {
            $where['_string'] = $condition['_string'];
        }
        $data = M('user')->where($where)->order($order)->findPage($pagesize, $parameter);
        $_data = $recomUser = array();
        foreach ($data['data'] as $key => $value) {
        	
//             if (!empty($value['vip_group_id'])) {
//                 $value['vip_group_id'] = explode(',', $value['vip_group_id']);
//                 $vip_group_name = '';
//                 foreach ($value['vip_group_id'] as $v) {
//                     $vip_group_arr = array();
//                     $vip_group_arr = $this->viewVipGroupById($v);
//                     if ($vip_group_arr['cn_name']) {
//                         $vip_group_name .= $vip_group_arr['cn_name'] . ',';
//                     }

//                 }
//                 $data['data'][$key]['vip_group_name'] = substr($vip_group_name, 0, -1);
//             }
            
            if ($value['from_uid']) {
                $recomUids[] = $value['from_uid'];
            }
            $user_account = M('user_account')->field("status")->find($value['uid']);
            $data['data'][$key]['account_status'] = $user_account['status'];
            $_data[$value['uid']] = $data['data'][$key];
        }


        //获取推荐人
        if ($recomUids) {
            $sql = 'SELECT uid, uname FROM fi_user WHERE uid IN(' . implode(',', $recomUids) . ')';
            $recomUser = M()->query($sql);

            foreach ($recomUser as $key => $value) {
                $recomUser[$value['uid']] = $value;
                unset($recomUser[$key]);
            }
        }

// 		p($recomUser);
        $data['data'] = $_data;
        $data['recomuser'] = $recomUser;

        return $data;
    }

    //用户组列表
    public function getVipGroupList($pagesize)
    {
        if (empty($pagesize)) {
            $data = M('vip_group')->order('ctime desc')->where("mi_no=".self::$api_key)->select();
        } else {
            $data = M('vip_group')->order('ctime desc')->where("mi_no=".self::$api_key)->findPage($pagesize);
        }
        return $data;
    }

    //用户组信息查看
    public function viewVipGroupByEnName($en_name)
    {
        $where = array("en_name"=>$en_name,"mi_no"=>self::$api_key);
        $data = M('vip_group')->where($where)->find();
        return $data;
    }

    public function viewVipGroupById($id)
    {
        $data = M('vip_group')->where('id=' . $id)->find();
        return $data;
    }

    //用户组添加、编辑处理
    public function  doVipGroup($para, $uid)
    {
        if (empty($para['en_name']) || empty($para['cn_name'])) {
            $result = array('boolen' => 0, 'message' => '参数错误');
            return $result;
        }
        $group_arr = $this->viewVipGroupByEnName(trim($para['en_name']));
        $data = array();
        $data['en_name'] = trim($para['en_name']); //英文名称 唯一性
        $data['cn_name'] = trim($para['cn_name']); //中文名称
        $data['desr'] = trim($para['desr']); //说明
        $data['mtime'] = time();
        if (!empty($para['id'])) { //修改
            if ($para['id'] != $group_arr['id']) {
                $result = array('boolen' => 0, 'message' => "英文名称[{$group_arr['en_name']}]已存在，请重新填写");
                return $result;
            }
            $is_exist = $this->viewVipGroupById($para['id']);
            if (empty($is_exist)) {
                $result = array('boolen' => 0, 'message' => '记录不存在');
                return $result;
            }
            $data['id'] = $is_exist['id'];
            $data['id'] = M('vip_group')->save($data);
            //日志记录
            D('Admin/ActionLog')->insertLog($uid, '用户组修改' . $is_exist['id'], $group_arr, $data);

        } else { //添加
            if (!empty($group_arr)) {
                $result = array('boolen' => 0, 'message' => "英文名称[{$group_arr['en_name']}]已存在，请重新填写");
                return $result;
            }
            $data['ctime'] = time();
            $data['mi_no'] = self::$api_key;
            $data['id'] = M('vip_group')->add($data);
            //日志记录
            D('Admin/ActionLog')->insertLog($uid, '用户组添加', "", $data);
        }
        if ($data['id']) {
            $result = array('boolen' => 1, 'message' => '操作成功');
        } else {
            $result = array('boolen' => 0, 'message' => '操作失败');
        }
        return $result;
    }

    /*
     * 设置用户是否激活
     *
     * is_active   1：激活   2：未激活
     */
    public function setUserIsActive($para, $uid)
    {
        if (empty($para['uid']) || empty($para['is_active'])) {
            $result = array('boolen' => 0, 'message' => '参数错误');
            return $result;
        }
        $user_arr = D('Account/User')->getByUid($para['uid']);
        if (empty($user_arr)) {
            $result = array('boolen' => 0, 'message' => '该记录不存在');
            return $result;
        }
        $data = array();
        $data['uid'] = $para['uid'];
        $data['mtime'] = time();
        if ($para['is_active'] == 1) { //激活
            if ($user_arr['is_active'] == 1) {
                $result = array('boolen' => 0, 'message' => '该用户已经是激活状态，无需重复');
                return $result;
            }
            $data['is_active'] = 1;
        } else { //未激活
            if (empty($user_arr['is_active'])) {
                $result = array('boolen' => 0, 'message' => '该用户已经是未激活状态，无需重复');
                return $result;
            }
            $data['is_active'] = 0;
        }
        $res = M('user')->save($data);
        if ($res) {
            //日志记录
            D('Admin/ActionLog')->insertLog($uid, '用户激活状态' . $para['uid'], $user_arr, $data);
            $result = array('boolen' => 1, 'message' => '操作成功');
        } else {
            $result = array('boolen' => 0, 'message' => '操作失败');
        }
        return $result;
    }

    /*
     * 设置是否可以线下充值
    *
    * is_xia_recharge   1：是   2：否
    */
    public function setUserIsXiaRecharge($para, $uid)
    {
        if (empty($para['uid']) || empty($para['is_xia_recharge'])) {
            $result = array('boolen' => 0, 'message' => '参数错误');
            return $result;
        }
        $user_arr = D('Account/User')->getByUid($para['uid']);
        if (empty($user_arr)) {
            $result = array('boolen' => 0, 'message' => '该记录不存在');
            return $result;
        }
        $data = array();
        $data['uid'] = $para['uid'];
        $data['mtime'] = time();
        if ($para['is_xia_recharge'] == 1) { //激活
            if ($user_arr['is_xia_recharge'] == 1) {
                $result = array('boolen' => 0, 'message' => '该用户已经是激活状态，无需重复');
                return $result;
            }
            $data['is_xia_recharge'] = 1;
        } else { //未激活
            if (empty($user_arr['is_xia_recharge'])) {
                $result = array('boolen' => 0, 'message' => '该用户已经是未激活状态，无需重复');
                return $result;
            }
            $data['is_xia_recharge'] = 0;
        }
        $res = M('user')->save($data);
        if ($res) {
            //日志记录
            D('Admin/ActionLog')->insertLog($uid, '用户设置是否可以线下充值' . $para['uid'], $user_arr, $data);
            $result = array('boolen' => 1, 'message' => '操作成功');
        } else {
            $result = array('boolen' => 0, 'message' => '操作失败');
        }
        return $result;
    }

    //获取所有单位信息
    public function getAllMember()
    {
        $data = M('member')->order('id desc')->select();
        return $data;
    }

    //获取某单位下的部门
    public function getAllDeptByMemberId($mi_id)
    {
        if (empty($mi_id)) {
            return FALSE;
        }
        $data = M('org_dept')->where('mi_id=' . $mi_id . ' and super_id !=0')->select();
        return $data;
    }

    //获取所有身份列表
    public function getAllIdentity()
    {
        $data = M('identity_lib')->where(array('mi_no' => self::$api_key))->select();
        return $data;
    }

    //获取特定用户下的身份
    public function getIdentityByUid($uid)
    {
        $data = M('user_identity_relation')->where('uid=' . $uid . ' and is_del=0')->select();
        return $data;
    }


    /**
     * 根据身份编码获取身份信息
     * @param unknown_type $identity_no
     */
    public function getIdentityByNo($identity_no)
    {

        return M('identity_lib')->where(array("identity_no" => $identity_no))->find();
    }


    //根据身份id,查详细信息
    public function getIdentityById($id)
    {
        $data = M('identity_lib')->find($id);
        return $data;
    }


    //查看用户信息和所属身份
    public function  editUser($uid)
    {
        if (empty($uid)) {
            return FALSE;
        }
        $uid_arr = D('Account/User')->getByUid($uid);
        if (empty($uid_arr)) {
            return FALSE;
        }
        $user_identity_arr = $this->getIdentityByUid($uid_arr['uid']); //获取用户下的身份
        foreach ($user_identity_arr as $key => $v) {
            $lib_arr = array();
            $lib_arr = $this->getIdentityById($v['identity_id']);
            $user_identity_arr[$key]['identity_name'] = $lib_arr['identity_name'];
        }
        $uid_arr['identity_list'] = $user_identity_arr;
        $uid_arr['vip_group_id'] = explode(',', $uid_arr['vip_group_id']);
        //推荐人
        $uid_arr['from_user'] = M()->query("SELECT t1.from_uid, t2.uname FROM fi_user_recommed t1 LEFT JOIN fi_user t2 ON t1.from_uid = t2.uid WHERE t1.uid = '{$uid}'");
        $uid_arr['from_user'] = $uid_arr['from_user'] ? $uid_arr['from_user'][0]['uname'] : '--';
        return $uid_arr;
    }


    //删除用户
    public function  delUser($ids, $uid)
    {
        if (empty($ids)) {
            $result = array('boolen' => 0, 'message' => '参数错误');
            return $result;
        }
        $uid_arr = D('Account/User')->getByUid($ids);
        if (empty($uid_arr)) {
            $result = array('boolen' => 0, 'message' => '该用户不存在');
            return $result;
        }
        //调用用户中心
        import_addon("libs.Usc.UscApi");
        $uscapi = new UscApi(C('USC_TYPE'));
        $uscdata = array();
        $uscdata['USERID'] = $uid_arr['usc_id'];
        $uscResult = $uscapi->delUser($uscdata);
        if ($uscResult['boolen'] != 1) {
            $result = array('boolen' => 0, 'message' => $uscResult['message']);
            return $result;
        }
        $where = " where  uid in ({$ids})";
        $sql = "update  fi_user set email=CONCAT(email,'--del'), mobile=CONCAT(mobile,'--del'), uname=CONCAT(uname,'--del'), person_id=CONCAT(person_id,'--del'), is_del=1,mtime=" . time();
        $res = M('user')->execute($sql . $where);
        if ($res) {
            //日志记录
            D('Admin/ActionLog')->insertLog($uid, '删除用户', $ids, $ids);
            $result = array('boolen' => 1, 'message' => '操作成功');
        } else {
            $result = array('boolen' => 0, 'message' => '操作失败');
        }
        return $result;

    }


    /*
     * 代理注册用户列表
     * zhangxiaoyan
     * 
     */
    public function getAgentRegistList($condition, $order, $pagesize)
    {
        $where = ' a.is_del=0 AND a.info_source=2 ';
        if (!empty($condition['uname'])) { //用户名
            $where .= " and a.uname like '%" . $condition['uname'] . "%'";
        }
        if (!empty($condition['real_name'])) { //真实名称
            $where .= " and a.real_name like '%" . $condition['real_name'] . "%'";
        }
        if (!empty($condition['email'])) { //邮箱
            $where .= " and a.email like '%" . $condition['email'] . "%'";
        }
        if ($condition['uid_type'] == 1) { //用户类型:1-个人 2-企业
            $where .= " and a.uid_type=" . $condition['uid_type'];
        } elseif ($condition['uid_type'] == 2) {
            $where .= " and a.uid_type=" . $condition['uid_type'];
        }

        $sql = "SELECT a.*,b.org_name,b.busin_license_No,b.org_code,b.mailing_addr,b.legal_name,b.legal_person_id,b.phone FROM  fi_user a
		          LEFT JOIN fi_user_extends_stock b ON a.uid=b.uid
		          WHERE " . $where . " order by a." . $order;
        $data = M('user')->findPageBySql($sql, null, null, $pagesize);
        return $data;
    }

    /*
     * 查看代理注册用户用户信息
     *
     */
    public function  viwAgentRegist($uid)
    {
        $uid_arr = D('Account/User')->getByUid($uid);
        $uid_arr['extends_stock'] = M('user_extends_stock')->where('uid=' . $uid)->find();
        $fund_account_arr = D('Payment/PayAccount')->getFundAccountList($uid);
        foreach ($fund_account_arr as $fund_account) {
            if ($fund_account['is_init'] == 0) {
                $uid_arr['fund_account_arr'] = $fund_account;
                break;
            }
        }
        $uid_arr['cash'] = M('user_account')->where('uid=' . $uid)->find();
        return $uid_arr;
    }


    //检测用户名

    public function checkValidate($type, $name, $usc_id = NULL)
    {
        $name = trim($name);
        if (empty($name) || empty($type)) {
            $result = array('boolen' => 0, 'message' => '参数错误');
            return $result;
        }
        $data = array();
        $data[$type] = trim($name);
        if (empty($usc_id)) { //添加
            //用户名检测

            if ($type == 'uname') {
                if (empty($name)) {
                    $result = array('boolen' => 0, 'message' => '用户名不能为空');
                    return $result;
                }
                $uname_unquie = D('Account/User')->checkUnameUnique($data['uname']);
                if (empty($uname_unquie)) {
                    $result = array('boolen' => 0, 'message' => '用户名被占用！');
                    return $result;
                }
                if (!is_numeric($name)) {
                    $result = array('boolen' => 0, 'message' => '用户名应为纯数字！');
                    return $result;
                }
                if (strlen($name) != 12) {
                    $result = array('boolen' => 0, 'message' => '用户名应长度应为12位！');
                    return $result;
                }
            } else {
                $uscArr = D('Account/User')->create($data); //添加至将用户中心
                if (empty($uscArr)) {
                    $result = array('boolen' => 0, 'message' => D('Account/User')->getError());
                    return $result;
                }
            }

        } else { //修改
            $updateUser = D('Account/User')->updateUser_usc($data, $usc_id); //添加至将用户中心
            if ($updateUser != true) {
                $result = array('boolen' => 0, 'message' => MyError::lastError());
                return $result;
            }

        }

        $result = array('boolen' => 1, 'message' => '可以使用');
        return $result;
    }


    /*
     *
     * 代理注册处理
     *
     */
    public function doAgentRegist($para)
    {
        $this->startTrans();
        try {
            $uid = (int)$para['uid'];
            if ($para['busin_license_No']) {
                if (strlen($para['busin_license_No']) < 6) {
                    $this->rollback();
                    $result = array('boolen' => 0, 'message' => "营业执照号长度应该大于六位");
                    return $result;
                }
                $password_sub = substr(trim($para['busin_license_No']), -6); //营业执照号后6位
                $password = pwd_md5(trim($password_sub));
            } else {
                $password_sub = substr(trim($para['person_id']), -6); //身份证后6位
                $password = pwd_md5(trim($password_sub));
            }
            $data = array();
            $data['info_source'] = 2; //信息来源1-正常注册 2-代理注册
            $data['uid_type'] = $para['uid_type']; //用户类型:1-个人 2-企业
            $data['email'] = $para['email']; //邮箱
            $data['uname'] = $para['uname']; //用户名
            $data['real_name'] = $para['real_name']; //真实名称
            $data['spell_name'] = pinyin($data['real_name']); //拼音
            $data['sex'] = 0; //性别
            $data['person_id'] = strtoupper(trim($para['person_id'])); //联系人身份证
            $data['password'] = $password; //身份证后6位
            $data['mobile'] = $para['mobile'];
            $data['is_email_auth'] = 1;
            $data['email_auth_time'] = time();
            $data['is_mobile_auth'] = 1;
            $data['mobile_auth_time'] = time();
            $data['is_id_auth'] = 1;
            $data['id_auth_time'] = time();
            $data['is_active'] = 1;
            $data['register_ip'] = get_client_ip(); //注册ip
            $data['mtime'] = time();

            if ($data['uid_type'] == 1) {
                $this->rollback();
                $hasUser = M('user')->where(array("person_id" => $data['person_id'], "uid_type" => 1, "is_id_auth" => 1))->find();
                if ($hasUser) return array('boolen' => 0, 'message' => '该身份证号已经被其他用户注册了');
            }

            Import("libs.IdAuth.IdAuth", ADDON_PATH);
            $idAuth = new IdAuth();
            $result = $idAuth->checkIdAuth(array(array('id' => $data['person_id'], 'name' => $data['real_name'])), '平台', '投融平台身份认证');
            if ($result['boolen'] == 1) {
                $data['person_img'] = $result['result'][0]['picture'];
            } else {
                $this->rollback();
                $result = array('boolen' => 0, 'message' => "身份证和姓名不匹配");
                return $result;
            }

            //判断唯一行
            $check_uname = $this->checkValidate('uname', $data['uname'], $para['usc_id']);

            if (empty($check_uname['boolen'])) {
                $this->rollback();
                $result = array('boolen' => 0, 'message' => $check_uname['message']);
                return $result;
            }
            $check_email = $this->checkValidate('email', $data['email'], $para['usc_id']);
            if (empty($check_email['boolen'])) {
                $this->rollback();
                $result = array('boolen' => 0, 'message' => $check_email['message']);
                return $result;
            }
            $check_person_id = $this->checkValidate('person_id', $data['person_id'], $para['usc_id']);
            if (empty($check_person_id['boolen'])) {
                $this->rollback();
                $result = array('boolen' => 0, 'message' => $check_person_id['message']);
                return $result;
            }

            if ($para['uid_type'] == 1) {
                $check_mobile = $this->checkValidate('mobile', $data['mobile'], $para['usc_id']);
                if (empty($check_mobile['boolen'])) {
                    $this->rollback();
                    $result = array('boolen' => 0, 'message' => $check_mobile['message']);
                    return $result;
                }
            }

            if ($uid) {
                $has_stock = M('user_extends_stock')->where(array("phone_zone" => $para['phone_zone'], "phone" => $para['phone'], 'uid' => $uid))->find();
                if ($has_stock) {
                    $this->rollback();
                    $result = array('boolen' => 0, 'message' => "该联系电话已经存在");
                    return $result;
                }
                $has_stock2 = M('user_extends_stock')->where(array("busin_license_No" => $para['busin_license_No'], 'uid' => $uid))->find();
                if ($has_stock2) {
                    $this->rollback();
                    $result = array('boolen' => 0, 'message' => "该营业执照号已经存在");
                    return $result;
                }
            } else {
                $has_stock = M('user_extends_stock')->where(array("phone_zone" => $para['phone_zone'], "phone" => $para['phone']))->find();
                if ($has_stock) {
                    $this->rollback();
                    $result = array('boolen' => 0, 'message' => "该联系电话已经存在");
                    return $result;
                }
                $has_stock2 = M('user_extends_stock')->where(array("busin_license_No" => $para['busin_license_No']))->find();
                if ($has_stock2) {
                    $this->rollback();
                    $result = array('boolen' => 0, 'message' => "该营业执照号已经存在");
                    return $result;
                }
            }

            //扩展表
            $extends_stock_data = array();
            $extends_stock_data['org_name'] = $para['org_name']; //机构名称
            $extends_stock_data['busin_license_No'] = $para['busin_license_No']; //营业执照号
            $extends_stock_data['org_code'] = $para['org_code']; //组织机构代码
            $extends_stock_data['mailing_addr'] = $para['mailing_addr']; //通讯地址
            $extends_stock_data['legal_name'] = $para['legal_name']; //联系人姓名
            $extends_stock_data['legal_person_id'] = $para['legal_person_id']; //联系人身份证号
            $extends_stock_data['real_name'] = $para['real_name']; //法人姓名
            $extends_stock_data['person_id'] = $para['person_id']; //法人身份证号
            $extends_stock_data['phone_zone'] = $para['phone_zone']; //电话zone
            $extends_stock_data['phone'] = $para['phone']; //电话号码
            $extends_stock_data['ctime'] = time(); //
            $extends_stock_data['mtime'] = time();
            //银行：
            $account_no = $para['account_no']; //账户
            //$channel = PaymentService::TYPE_XIANXIA;
            $channel = 'xianxia';
            $bank = $para['bank']; //银行id
            $bank_name = $para['bank_name']; //银行名字
            $sub_bank = $para['sub_bank']; //开会行

            //充值：
            $recharge_amount = $para['recharge_amount']; //充值金额
            $withdrawal_count = $para['withdrawal_count']; //提现次数

            if (!empty($uid)) { //修改
                $data['uid'] = $uid;
                $user_arr = D('Account/User')->getByUid($uid);
                if (empty($user_arr)) {
                    $this->rollback();
                    $result = array('boolen' => 0, 'message' => '该记录不存在');
                    return $result;
                }
                //更新至用户中心
                $updateUser = D('Account/User')->updateUser_usc($data, $user_arr['usc_id']); //添加至将用户中心
                if ($updateUser != true) {
                    $this->rollback();
                    $result = array('boolen' => 0, 'message' => MyError::lastError());
                    return $result;
                }
                M('user')->save($data);

            } else { //添加
                $uscId = D('Account/User')->update2Usc($data); //添加至将用户中心

                if (empty($uscId)) {
                    $this->rollback();
                    $result = array('boolen' => 0, 'message' => MyError::lastError());
                    return $result;
                }
                $data['ctime'] = time();
                $data['usc_id'] = $uscId;
                $data['info_from_uid'] = (int)$para['info_from_uid'];
                $data['uid'] = M('user')->add($data);
                if (!empty($data['uid'])) {
                    D('Account/User')->userInit($data['uid'], $password_sub); //用户信息初始化

                    //给账户充值，$user_account 某个用户的fi_user_account数据， $money 充值金额，单位是分，可能抛异常，请try catch处理
                    $user_account = array();
                    $user_account = M('user_account')->find($data['uid']);
                    if ($recharge_amount) {
                        $user_account['bank_info']['account_no'] = $account_no;
                        $user_account['bank_info']['bank'] = $bank;
                        $user_account['bank_info']['bank_name'] = $bank_name;
                        $user_account['bank_info']['sub_bank'] = $sub_bank;
                        $result = service('Payment/PayAccount')->stockRecharge($user_account, $recharge_amount * 100);
                        if ($result) {
                            $this->rollback();
                            return $result;
                        }
                    }

                }
            }
            if ($data['uid']) {
                //扩展表
                if ($data['uid_type'] == 2) { //企业
                    M('user_extends_stock')->delete($data['uid']);
                    $extends_stock_data['uid'] = $data['uid'];
                    $user_extends_stock_arr = M('user_extends_stock')->add($extends_stock_data);
                    if (empty($user_extends_stock_arr)) {
                        $this->rollback();
                        $result = array('boolen' => 0, 'message' => '扩展表处理有误');
                        return $result;
                    }
                }

                //增/改三方账户信息
                $fund_account_arr = service('Payment/PayAccount')->getFundAccountList($data['uid']);
                $saveFundAccount = service('Payment/PayAccount')->saveFundAccount($data['uid'], $account_no, $channel, NULL, $bank, $bank_name, $sub_bank, $fund_account_arr[0]['id'], 1);
                if (empty($saveFundAccount['boolen'])) {
                    $this->rollback();
                    $result = array('boolen' => 0, 'message' => $saveFundAccount['message']);
                    return $result;

                }

                //奖励提现次数，$times提现次数，可能抛异常，请try catch处理
                if (!empty($withdrawal_count)) {
                    service('Payment/PayAccount')->rewardCashoutTimes($data['uid'], $withdrawal_count);
                }

                $this->commit();
                $result = array('boolen' => 1, 'message' => '操作成功');
                return $result;
            } else {
                $this->rollback();
                $result = array('boolen' => 0, 'message' => '操作失败');
                return $result;
            }
        } catch (Exception $e) {

            $this->rollback();
            $result = array('boolen' => 0, 'message' => $e->getMessage());
            return $result;
        }
    }

    function getMemberOrgDeptByuid($uid)
    {
        if (!intval($uid)) return array();
        $sql = "SELECT t1.uid, t2.dept_name, t3.mi_name
				FROM fi_user t1
				LEFT JOIN fi_org_dept t2 ON t1.dept_id = t2.id
				LEFT JOIN fi_member t3 ON t1.mi_id = t3.id
				WHERE t1.uid = '{$uid}'";
        return M('user')->query($sql);
    }

    /**
     * 获取用户银行卡绑定信息
     * @param int $uid 用户uid
     * @return array $backList
     */
    function getUserBankCardsInfo($uid)
    {
        if (!$uid) return false;

        //用户绑定的所用银行卡
        $bankCards = M('fund_account')->where(array('uid' => $uid, 'is_active' => 1))->order('ctime')->select();
        $backList = array();
        if ($bankCards) {
            $autoable = $default = 0;
            $backList['all']['count'] = count($bankCards);
            $backList['all']['list'] = $bankCards;
            foreach ($bankCards as $key => $value) {
                if ($this->isBankCardInfoFull($uid, array($value['bank'], $value['sub_bank_id'], $value['bank_province'], $value['bank_city']))) {
                    $backList['autoable']['count'] = ++$autoable;
                    $backList['autoable']['list'][$value['id']] = $value;
                }
                if ($value['is_default']) {
                    $backList['default']['count'] = ++$default;
                    $backList['default']['list'][] = $value;
                }
            }
        }
        return $backList;
    }

    /**
     * 验证用户银行卡信息是否完整
     * @param int $bankCode 银行代号
     * @param int $subBankId 支行id
     * @param int $bankProvinceId 省code
     * @param int $bankCityId 市code
     */
    function isBankCardInfoFull($uid, $bankInfo = array())
    {
        if (!M('user_account')->where('uid = ' . $uid)->getField('is_merchant')) {
            foreach ($bankInfo as $v) {
                if (empty($v)) {
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * 获取用户的一条记录
     * @param $uid
     * @param string $fields
     * @return mixed
     */
    public function getUserAccountRecord($uid, $fields = '')
    {
        $fields = $fields ? $fields : '*';
        $record = $this->where(array('uid' => $uid))->field($fields)->find();
        if (count(explode(',', $fields)) == 1) {
            return $record[$fields];
        }
        return $record;
    }

    public function getMultiUserAccountByUids($uids)
    {
        if (!$uids) return array();

        $uids = !is_array($uids) ? array($uids) : $uids;

        $user_account = $this->where(array(
            'uid' => array('IN', $uids)
        ))->select();

        return $user_account ? array_column($user_account, null, $user_account) : $user_account;
    }
    //浙商开户成功后upg平台数据处理
    public function zsAccountToUpg($params){
        $this->startTrans();
        try {
            $uid = $params['uid'];
            $account_no = $params['account_no'];
            $bank = $params['bank'];
            $zs_bind_serial_no = $params['zs_bind_serial_no'];
            $zs_account_id = $params['zs_account_id'];
            $zs_branch_no = $params['zs_branch_no'];
            $remark = $params['remark'];

            $save_data = array();
            $save_data['uid'] = $uid;
            $save_data['zs_status'] = self::OPENED_ACCOUNT;
            $result = M('user')->where(array('uid'=>$uid))->find();
            if(!$result){
                throw_exception('该用户不存在！！！');
            }elseif(!$result['zs_status']){
                throw_exception('该用户不是开户中状态!');
            }elseif($result['zs_status'] == self::OPENED_ACCOUNT){
                throw_exception('该用户已经开户！!');
            }
            $zs_bank_mobile = $result['zs_bank_mobile'];
            $person_name = $result['real_name'];
            $person_id = $result['person_id'];
            //新用户没有实名认证的更新认证信息
            if(!$result['is_id_auth']){
                $save_data['real_name'] = $person_name;
                $save_data['spell_name'] = pinyin($person_name);
                $save_data['person_id'] = $person_id;
                $save_data['is_id_auth'] = 1;
                $save_data['id_auth_time'] = time();

            }
            $save_data['mtime'] = time();
            $save_data['zs_bind_serial_no'] = $zs_bind_serial_no;
            $save_data['zs_account_id'] = $zs_account_id;
            $save_data['zs_mtime'] = time();
            $save_data['zs_remark'] = $remark;
            $ret = D("Account/User")->updateUser($save_data);
            if(!$ret){
                throw_exception('更新user表失败0:'.MyError::lastError());
                $this->rollback();
            }
            //获取对应的开户行字段
            $bank_data = service('Mobile2/Bank')->getZsBankInfo($bank,$account_no);
            $bank = $bank_data['bank'];
            $bank_name = $bank_data['bank_name'];
            $data = array();
            $data['uid'] = $uid;
            $data['account_no'] = $account_no;
            $data['acount_name'] = $account_no;
            $data['channel'] = 'zheshang';
            $data['bank'] = $bank;
            $data['bank_name'] = $bank_name;
            $data['bank_province'] = 0;
            $data['bank_city'] = 0;
            $data['is_active'] = 1;
            $data['is_default'] = 0;
            $data['real_name'] = $person_name;
            $data['person_id'] = $person_id;
            $data['mobile'] = $zs_bank_mobile;
            $data['sub_bank'] = $params['bank'];
            $data['sub_bank_id'] = '-1';
            $data['ctime'] = time();
            $data['mtime'] = time();
            $data['zs_bind_bank_card'] = $account_no;
            $data['zs_bankck'] = 1;
            $data['zs_branch_no'] = $zs_branch_no;
            $ret1 = M('recharge_bank_no')->add($data);
            if(!$ret1){
                throw_exception('添加recharge_bank_no表数据失败1');
                $this->rollback();
            }
            $data['recharge_bank_id'] = $this->getLastInsID();
            $ret2 = M('fund_account')->add($data);
            if(!$ret2){
                throw_exception('添加fund_account表数据失败2');
                $this->rollback();
            }
            $this->commit();
            if (S('zs_'.$uid)){
                $session_id = S('zs_'.$uid);
                $api_key = C('api_info');
                $api_key = $api_key['api_key'];
                BaseModel::setApiKey($api_key, $session_id, $uid);
                $userInfo = BaseModel::newSession('USER_LOGIN_KEY');
                //如果用户这时候在线更新用户的session数据
                if($userInfo){
                    service("Account/Account")->resetLogin($uid);
                }
                S('zs_'.$uid,null);
            }

            $result = array('boolen' => '1', 'message' => '操作成功');
            return $result;
        }catch (Exception $e){
            $this->rollback();
            $result = array('boolen' => '0', 'message' => $e->getMessage());
            return $result;
        }
    }

    /**
     * 账户余额
     * @param $uid
     * @param bool $zs true-浙商存管户 false-鑫合汇账户
     * @return int
     */
    public function getUserAccountByPrj($uid, $zs = false)
    {
        $field = $zs ? 'zs_amount' : 'amount';
        return (int) $this->where(['uid' => $uid])->getField($field);
    }
    //浙商修改的银行卡数据同步到upg
    public function bankCardToUpg($params){
        $this->startTrans();
        try{
            $uid = $params['uid'];
            $account_no = $params['account_no'];
            $bank = $params['bank'];
            $zs_branch_no = $params['zs_branch_no'];
            $result = M('user')->where(array('uid'=>$uid))->find();
            if(!$result){
                throw_exception('该用户不存在！！！');
            }elseif($result['zs_status'] != '2'){
                throw_exception('该用户未开通存管户!');
            }
            //查询下浙商绑定的老卡
            $cardInfo = M('fund_account')->where(array('zs_bankck'=>1))->find();
            if(!$cardInfo){
                throw_exception('没有找到在浙商绑定银行卡!');
            }
            $bank_data = service('Mobile2/Bank')->getZsBankInfo($bank,$account_no);
            $bank = $bank_data['bank'];
            $bank_name = $bank_data['bank_name'];
            $zs_bank_mobile = $result['zs_bank_mobile'];
            $save_data['account_no'] = $account_no;
            $save_data['acount_name'] = $account_no;
            $save_data['bank'] = $bank;
            $save_data['bank_name'] = $bank_name;
            $save_data['mobile'] = $zs_bank_mobile;
            $save_data['sub_bank'] = $params['bank'];
            $save_data['mtime'] = time();
            $save_data['zs_bind_bank_card'] = $account_no;
            $save_data['zs_branch_no'] = $zs_branch_no;
            $ret1 = M('fund_account')->where(array('uid'=>$uid,'zs_bankck'=>1))->save($save_data);
            if(!$ret1){
                    throw_exception('更新新卡fund_account表失败1');
                    $this->rollback();
                }
            $ret2 = M('recharge_bank_no')->where(array('uid'=>$uid,'zs_bankck'=>1))->save($save_data);
            if(!$ret2){
                    throw_exception('更新新卡recharge_bank_no表失败1');
                    $this->rollback();
                }

            $this->commit();
            $result = array('boolen' => '1', 'message' => '操作成功');
            import_addon("libs.Cache.RedisSimply");
            $redis = new RedisSimply('bankCardStatus');
            $key = $uid.'_'.$account_no;
            $redis->del($key);
            return $result;
        }catch (Exception $e){
            $this->rollback();
            $result = array('boolen' => '0', 'message' => $e->getMessage());
            return $result;
        }
    }
}
<?php

/**
 * 预约
 * Class AppointAction
 */
class AppointAction extends BaseAction{

    private $user;
    private $currentModel;
    protected $uid;
    protected $is_limit_apply = false;

    public function _initialize(){
        $this->error('自动投标已升级为司马小鑫');
        parent::_initialize();
        $this->user = $this->loginedUserInfo;
        $this->currentModel = D("Financing/Appoint");
        $this->uid = $this->user['uid'];
        //是否限制发起预约 0能 1不能
        $this->is_limit_apply = $this->currentModel->isLimitApply($this->uid);
        $this->assign('basicUserInfo',service('Account/Account')->basicUserInfo($this->loginedUserInfo));
    }

    /**
     * 预约首页
     */
    public function index(){
        //统计数据
        $stat = service("Financing/Appoint")->getAppointOracle();
//        var_dump($stat);exit;
        $this->assign("stat", $stat);
        //前台统计数据
        $summary = $this->currentModel->getAppointData();
        $this->assign("summary",$summary);
        $this->assign("banners", service("Index/Index")->getBanners(3, 63));
        $this->assign('is_limit_apply',$this->is_limit_apply);
        $this->display();
    }

    /**
     * 预约申请页
     */
    public function appoint(){
        //开启主从分离
        openMasterSlave();
        if ($this->is_limit_apply) {
            redirect(U("Financing/Appoint/index"));
        }
        $uid = $this->user['uid'];
        //用户可预约余额
        $user_total_amount = $this->getUserTotalAmount($uid);
        $this->assign("user_total_amount",humanMoney($user_total_amount,2,false));

        $has_appoint = $this->currentModel->is_has_appoint($uid);
        if($has_appoint){
            $summary = $this->currentModel->getMyAppoint($uid);
            $record = $this->currentModel->where(array('uid'=>$uid))->order("id desc")->find();
            $userSelect = service("Financing/Appoint")->getAppointSelected($record);
            $appointRecord = array_merge($record,$userSelect);
            $appointRecord['appoint_rate'] = $appointRecord['appoint_rate']/10;
            $appointRecord['appoint_money'] = $appointRecord['appoint_money']/100;
            $this->assign('appointRecord',$appointRecord);
        }

        $stat = service("Financing/Appoint")->getAppointOracle();
        $this->assign('stat', $stat);
        $status = $this->currentModel->getCurrentStatus($uid);
        $this->assign("timeLimit",AppointModel::$timeLimit);
        $this->assign("status", $status);
        $this->assign("summary", $summary);
        $this->assign("has_appoint",$has_appoint);//大于0 有预约 等于0 没有预约
        $this->assign('is_limit_apply',$this->is_limit_apply);

        $this->assign("is_paypwd_edit", $this->user['is_paypwd_edit']);//20160314 是否设置支付密码
        $this->display();
    }

    //申请预约
    public function ajaxCommit(){
        if(IS_POST){
            try {
                if($this->is_limit_apply){
                    throw_exception('申请预约人数达到上限');
                }
                if(!$this->currentModel->autoCheckToken($_POST)){
                    throw_exception('令牌验证失败,请刷新后重试');
                }

                if(!$this->loginedUserInfo['is_id_auth'] || !$this->loginedUserInfo['person_id']){
                    throw_exception('预约前请先在账户中进行实名认证');
                }

                $uid = $this->user['uid'];
                $pay_pwd = $_POST['safe_pwd'];

                //20150318 start 用户未设置支付密码，点击确认支付后提示去设置支付密码
                $user_account = M('user_account')->find((int)$uid);
                if((!$this->user['is_paypwd_edit']) && (!$user_account['pay_password'])){
                    ajaxReturn(0, '请先去设置支付密码', 0);
                }//end

                if(!D('Account/UserAccount')->checkOldPwd($uid, $pay_pwd)){
                    throw_exception("支付密码不正确");
                }


                //用户预约类型
                $is_rys = I("post.rys",0,"intval");
                $is_sdt = I("post.sdt",0,"intval");
                $is_xyb = I("post.xyb",0,"intval");
                $is_qyr = I("post.qyr",0,"intval");

                $appoint_rate = I('post.appoint_rate',0,'floatval')*10;
                $appoint_day = I('post.appoint_day',0,"intval");
                $appoint_money = (int)(I('post.appoint_money',0,'floatval')*100);

                $params = array(
                    'uid' => $uid,
                    'is_rys' => $is_rys,
                    'is_sdt' => $is_sdt,
                    'is_xyb' => $is_xyb,
                    'is_qyr' => $is_qyr,
                    'appoint_rate' => $appoint_rate,
                    'appoint_day' => $appoint_day,
                    'appoint_money' => $appoint_money,
                    'is_agree_agreement' => 0,
                    'client_type' => self::CLIENT_TYPE,
                );
                if(I('post.agreeCheck') == 'on'){
                    $params['is_agree_agreement'] = 1;
                }

                if (!$this->user['is_id_auth'] || !$this->user['person_id']) {
                    ajaxReturn('', '预约前请先在账户中进行实名认证', 0);
                }

                //选择预约的类型
                $serAppoint = service('Financing/Appoint');
                if ($serAppoint->applyAppoint($params)) {
                    ajaxReturn(0, '预约成功');
                } else {
                    ajaxReturn('', $serAppoint->getError(),0);
                }

            } catch (Exception $exc) {
                ajaxReturn(0, $exc->getMessage(), 0);
            }
        }
    }

    /**
     * 修改预约(数据准备)
     * @access public
     * @memo
     * 参数 必须POST提交
     * appoint_id 预约记录的appoint_id
     * ---------------------------------------------------------
     * 修改预约(数据准备)
     *
     * 返回数据
     * ---------------------------------------------------------
     * 修改预约
     */
    public function editAppoint($aid)
    {
        $uid = $this->uid;
        try {
            if (!$uid) {
                throw_exception("请登录");
            }
            if (!$aid) {
                throw_exception("参数有误");
            }
            $appoint_service = service('Financing/Appoint');
            $ret = $appoint_service->editAppoint($aid, $uid);
            return $ret;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * 执行修改预约的提交处理
     * @access public
     * @memo
     * 参数 必须POST提交
     * appoint_id 预约记录的appoint_id
     * ---------------------------------------------------------
     * 执行修改预约的提交处理
     *
     * 返回数据
     * ---------------------------------------------------------
     * 修改预约是否成功
     */
    public function doEditAppoint()
    {
        $uid = $this->uid;
        $aid = I("appoint_id",0,"intval");
        try {
            if (!$uid) {
                throw_exception("请登录");
            }
            if (!$aid) {
                throw_exception("参数有误");
            }
            if (!D('Account/UserAccount')->checkOldPwd($uid, $_REQUEST["safe_pwd"])) {
                throw_exception("支付密码不正确");
            }
            $appoint_mod= D('Financing/Appoint');
            $post_data = $_POST;//数据在service中进行校验
            $post_data['is_rys'] = I('rys',0,"intval");
            $post_data['is_agree_agreement'] = 1;
            $post_data['uid'] = $uid;
            $post_data['appoint_money'] = I('post.appoint_money') * 100;
            $post_data['appoint_rate'] = I("post.appoint_rate") * 10;
            $ret = $appoint_mod->modifyAppoint($post_data, $aid,$uid);
            if ($ret) {
                ajaxReturn(0, '修改成功');
            } else {
                throw_exception($appoint_mod->getError());
            }
        } catch (Exception $e) {
            ajaxReturn(0, $e->getMessage(), 0);
        }
    }

    //取消预约
    public function ajaxCancle()
    {
        $uid = $this->user['uid'];
        $aid = I("request.id", 0);
        $appoint_mod = D('Financing/Appoint');
        try {
            $ret = $appoint_mod->cancleAppoint($aid, $uid);
            if ($ret) {
                //同步取消预约
                $appoint_mod->where(array('id'=>$aid,'uid'=>$uid))->save(array('is_enable'=>AppointModel::APPOINT_ENABLE));
                ajaxReturn(0, '操作成功');
            } else {
                ajaxReturn(0, '操作失败', 0);
            }
        } catch (Exception $e) {
            ajaxReturn(0, '取消异常,请联系客服', 0);
        }

    }

    public function ajaxIsTriggerTips()
    {
        openMasterSlave();
        $uid = $this->user['uid'];
        $service = service("Financing/Appoint");
        $type = I("type", 0, "intval");//1投资 2提现
        $is_ok = false;
        $amount = I("amount", 0) * 100;
        $res = $service->amount_reduce($uid, $amount, $is_ok, $type);
        if($res == false){
            ajaxReturn($res, '没有预约', 0);
        }
        ajaxReturn($res, 'ok');


    }

    private function getUserTotalAmount($uid)
    {
        $serviceObj = service("Payment/PayAccount");
        $account = $serviceObj->getBaseInfo($uid);
        return ($account['amount'] + $account['reward_money'] + $account['invest_reward_money'] + $account['coupon_money']);
    }

}

<?php
/*
 * Jigang Guo
 * 用户绑定银行卡 * 
 */
class BankAction extends BaseAction {
    public function _initialize()
    {
        parent::_initialize();
        $this->assign('basicUserInfo',service('Account/Account')->basicUserInfo($this->loginedUserInfo));
    }
    //校验银行卡
    function bankCardCheck(){
        $cardno = I('request.card_no');//卡号
        $code = I('request.bank_code');//银行代码
        $payType = I('request.paytype', 'yibaoApi');
        if(!$cardno || !$payType) ajaxReturn('', '参数错误', 0);
        if(!preg_match('/\d{16,20}/',  $cardno)){
            ajaxReturn('', '请输入正确的银行卡号', 0);
        }

        $yangmd_service = service('Account/YangMaoDang');
        if (false === $yangmd_service->dealWithCardNo(array(
                'uid' => $this->loginedUserInfo['uid'],
                'card_no' => $cardno,
                'uname' => $this->loginedUserInfo['uname'],
            ))) {
            ajaxReturn(0, $yangmd_service->getError(), 0);
        }

        $checkObj = service('Account/Check');
        if(!$checkObj->checkBankCardValid($cardno, $this->loginedUserInfo, true)){
            ajaxReturn(0, $checkObj->getError(), 0);
        }
        $payment = service('Payment/Payment')->init($payType);
        $arr['cardno'] = $cardno;
        $arr['code'] = $code;
        $ret = $payment->payment->bankCardCheck( $arr );
        showJson( $ret );
    }

    //绑定银行卡
    function bindBankCard(){

        $arr['uid'] = $this->loginedUserInfo['uid'];//银行预留手机号
        $arr['cardno'] = preg_replace('/\s+/', '',I('request.card_no'));//卡号
        $arr['idcardno'] = I('request.person_id','trim');//身份证号

        $yangmd_service = service('Account/YangMaoDang');
        if (false === $yangmd_service->dealWithCardNo(array(
                'uid' => $arr['uid'],
                'card_no' => $arr['cardno'],
                'uname' => $this->loginedUserInfo['uname'],
            ))) {
            ajaxReturn('', $yangmd_service->getError(), 0);
        }

        if (false === $yangmd_service->dealWithPersonId(array(
                'uid' => $arr['uid'],
                'idcardno' => $arr['idcardno'],
                'uname' => $this->loginedUserInfo['uname'],
            ))) {
            ajaxReturn('', $yangmd_service->getError(), 0);
        }

        $arr['username'] = I('request.real_name', 'trim');//真实姓名
        $arr['phone'] = I('request.mobile', 'trim');//银行预留手机号

        if($this->loginedUserInfo['is_id_auth'] == 1){
            $arr['idcardno'] = $this->loginedUserInfo['person_id'];
            $arr['username'] = $this->loginedUserInfo['real_name'];
        }
        $payType = I('request.paytype', 'yibaoApi');//'yibao'
        if(!$payType || !$arr['cardno'] || !$arr['idcardno'] || !$arr['username'] || !$arr['phone']) ajaxReturn('', '参数错误', 0);
        $payment = service('Payment/Payment')->init($payType);
        $ret = $payment->payment->bindBankCard( $arr );
        $user = $this->loginedUserInfo;
        $user['is_id_auth'] != 1 && $payment->payment->userIdAuth( $arr );//身份实名认证
        showJson( $ret );
    }

    //确认绑卡
    function confirmBindBankCard(){
        $is_default = isset($_POST['is_default']);
        $arr['bank_city'] = I('request.bank_city');
        $arr['bank_province'] = I('request.bank_province');
        $arr['sub_bank'] = I('request.sub_bank');
        $arr['sub_bank_id'] = I('request.sub_bank_id');
        $arr['cardno'] = I('request.card_no');//卡号
        $arr['bank_name'] = I('request.bank_name'); //银行
        $arr['bank'] = I('request.bank_code'); //银行code
        $cardno = $arr['cardno'];
        $arr['cardno'] = preg_replace('/\s+/', '',$arr['cardno']);//身份证号
        $arr['idcardno'] = I('request.person_id','');//身份证号
        $arr['username'] = I('request.real_name', '');//真实姓名
        if($this->loginedUserInfo['is_id_auth'] == 1){
            $arr['idcardno'] = $this->loginedUserInfo['person_id'];
            $arr['username'] = $this->loginedUserInfo['real_name'];
        }
        $arr['phone'] = I('request.mobile', '');//银行预留手机号

        $arr['uid'] = $this->loginedUserInfo['uid'];//银行预留手机号
        $arr['validatecode'] = I('validate_code');//验证码
        $arr['requestid'] = I('requestid');//验证码
        $payType = I('request.paytype', 'yibaoApi');//'yibao'
        if(!$payType || !$arr['bank'] || !$arr['validatecode'] || !$arr['requestid'] || !$arr['cardno'] || !$arr['idcardno'] || !$arr['username'] || !$arr['phone']) ajaxReturn('', '参数错误', 0);
        $payment = service('Payment/Payment')->init($payType);
        $ret = $payment->payment->confirmBindBankCard( $arr );
        $addbank = I('request.addbank');
        $id = I('request.id');
        $user = M('user')->find($arr['uid']);
        if($id && $addbank == 2){
            $fund_account_arr = M('fund_account')->where(array('mi_no'=>$user['mi_no'], 'account_no'=>$arr['cardno']))->select();
            if( $fund_account_arr ) {
                foreach ($fund_account_arr as $fund_account_x) {
                    if ($fund_account_x['uid'] != $arr['uid']) {
                        $user_x = M('user')->find($fund_account_x['uid']);
                        if ($user_x['person_id'] != $user['person_id']) {
                            ajaxReturn('', "该银行卡已经被其他用户绑定了", 0);
                        }
                    }
                }
            }

//            import_addon("libs.Cache.RedisSimply");
//            $redis = new RedisSimply('bankFundtwo');
//            if($redis->get($arr['cardno']) && $redis->get($arr['cardno']) != $user['person_id']){
//                M('user_account')->save(array('uid'=>$user['uid'],'status'=>2, 'mtime'=>time()));
//                $this->error("绑卡异常，账户被冻结，请联系客服");
//            }

            $where['id'] = $id;
            $where['uid'] = $arr['uid'];
            $arrs['account_no'] = $arr['cardno'];
            $arrs['real_name'] = $arr['username'];
            $arrs['person_id'] = $arr['idcardno'];
            $arrs['mobile'] = $arr['phone'];
            $arrs['bank'] = $arr['bank'];
            $arrs['bank_name'] = $arr['bank_name'];
            $arrs['bank_province'] = $arr['bank_province'];
            $arrs['bank_city'] = $arr['bank_city'];
            $arrs['sub_bank'] = $arr['sub_bank'];
            $arrs['sub_bank_id'] = $arr['sub_bank_id'];
            $arrs['acount_name'] = $cardno;
            $arrs['is_default'] = $is_default;
            $arrs['is_bankck'] = 1;
            $arrs['is_active'] = 1;
            $ret['data']['id'] = service('Payment/PayAccount')->saveBank($arrs, $where);
        }else{
            $mybindcard = M('recharge_bank_no')
                ->field('id, account_no, channel, bank, bank_name')->where(array('account_no'=>$arr['cardno'], 'uid'=>(int)$this->loginedUserInfo['uid']))->find();
            $ret['data']['id'] = $mybindcard['id'];
            if(!$mybindcard){
                $recharge_bank['uid'] = $arr['uid'];
                $recharge_bank['account_no'] = $arr['cardno'];
                $recharge_bank['real_name'] = $arr['username'];
                $recharge_bank['person_id'] = $arr['idcardno'];
                $recharge_bank['mobile'] = $arr['phone'];
                $recharge_bank['bank'] = $arr['bank'];
                $recharge_bank['bank_name'] = $arr['bank_name'];
                $recharge_bank['bank_province'] = $arr['bank_province'];
                $recharge_bank['bank_city'] = $arr['bank_city'];
                $recharge_bank['sub_bank'] = $arr['sub_bank'];
                $recharge_bank['sub_bank_id'] = $arr['sub_bank_id'];
                $recharge_bank['acount_name'] = $cardno;
                $recharge_bank['channel'] = $payType;
                $recharge_bank['is_default'] = $is_default;
                $recharge_bank['is_bankck'] = 1;
                $recharge_bank['is_active'] = 1;
                $recharge_bank['ctime'] = time();
                $recharge_bank['mtime'] = $recharge_bank['ctime'];
                $id = service('Payment/PayAccount')->addRechargeBank($recharge_bank);
                $ret['data']['id'] = $id;
                $ret['data']['fund_id']  = M('fund_account')->where(array('recharge_bank_id'=>$id))->getField('id');
            }
        }
        $key_bank_amount = 'bank_amount_'.$this->loginedUserInfo['uid'];
        //用户概况展示银行卡数量
        S($key_bank_amount,null);
//        $redis->set($arr['cardno'], $user['person_id']);
        service("Account/Account")->resetLogin($this->loginedUserInfo['uid']);
        service("Api/TianYanRequest")->memberUpdate($this->loginedUserInfo['uid'], 'card');//网贷天眼用户绑卡回调
        showJson( $ret );
    }

    //获取银行卡列表
    function getBankList(){
        $bank_list = service('Mobile2/Bank')->getChannelBank( 'yibaoApi' );
        ajaxReturn($bank_list, '银行列表');
    }

    //实名渲染
    function identify(){
        $bank_list = service('Mobile2/Bank')->getChannelBank( 'yibaoApi', 'recharge' );
        $id = (int)I('id');
        $addbank = (int)I('addbank', 0);
        if( $id > 0 ){
            $data = D('Payment/PayAccount')->getFundAccount($this->loginedUserInfo['uid'], $id);
            $data['account_no'] = preg_replace('/\s*/', '', $data['account_no']);
            $addbank = 2;
            $this->assign('data', $data);
            $this->assign('id', $id);
        }
        $this->assign('user', $this->loginedUserInfo);
        $this->assign('real_name', $this->loginedUserInfo['real_name'] ? '*' .mb_substr($this->loginedUserInfo['real_name'], 1, strlen($this->loginedUserInfo['real_name']), 'utf-8') : '');
        $this->assign('person_id', $this->loginedUserInfo['person_id'] ? person_id_view($this->loginedUserInfo['person_id']) : '');
        $this->assign('addbank', $addbank);
        $this->assign('bank_list', $bank_list);
        $this->display();
    }

}

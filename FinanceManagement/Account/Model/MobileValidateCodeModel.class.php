<?php
import_addon('libs.Sms.Sms');

class MobileValidateCodeModel extends BaseModel
{
    const CODE_TYPE_RESET_PAYPWD = 'RESET_PAYPWD';
    const CODE_TYPE_NAME_RESET_PAYPWD = '找回支付密码';// 找回支付宝密码

    const CODE_TYPE_RESET_PAYPWD_MOBILE = 'RESET_PAYPWD_MOBILE';
    const CODE_TYPE_NAME_RESET_PAYPWD_MOBILE = '找回手机支付密码';

    const CODE_TYPE_REGISTER = 'REGISTER';
    const CODE_TYPE_NAME_REGISTER = '注册';// 注册

    const CODE_TYPE_REGISTER_WAP = 'REGISTER_WAP';
    const CODE_TYPE_NAME_REGISTER_WAP = '注册';// 注册

    const CODE_TYPE_LOGIN = 'LOGIN';
    const CODE_TYPE_NAME_LOGIN = '登录';// 登录

    const CODE_TYPE_FORGETPWD2 = 'FORGETPWD2';
    const CODE_TYPE_NAME_FORGETPWD2 = '忘记密码';// 忘记密码第二步

    const CODE_TYPE_CASHOUT = 'CASHOUT';// 
    const CODE_TYPE_NAME_CASHOUT = '提现';// 提现

    const CODE_TYPE_EDIT_MOBILE = 'EDIT_MOBILE';
    const CODE_TYPE_NAME_EDIT_MOBILE = '修改手机号码';// 修改手机号码

    const CODE_TYPE_AUTHOR_MOBILE = 'AUTHOR_MOBILE';// 修改手机号码
    const CODE_TYPE_NAME_AUTHOR_MOBILE = '手机号码认证';// 手机号码认证

    const CODE_TYPE_TRANSFER = 'TRANSFER';// 手机号码认证
    const CODE_TYPE_TRANSFER_NAME = '产品转让';

    const CODE_TYPE_CHECK_OLD_MOBILE = 'CHECK_OLD_MOBILE';
    const CODE_TYPE_CHECK_OLD_MOBILE_NAME = '检查当前手机号码';

    const CODE_TYPE_CHECK_FUND = 'CHECK_FUND';
    const CODE_TYPE_CHECK_FUND_NAME = '解除绑定银行卡';

    const CODE_TYPE_WEALTH_VERIFY = 'WEALTH_VERIFY';
    const CODE_TYPE_NAME_WEALTH_VERIFY = '财富中心手机号码认证';

    const CODE_TYPE_CANCEL_APPLY = 'CANCEL_APPLY';// 机构撤销认证
    const CODE_TYPE_NAME_CANCEL_APPLY = '机构撤销认证';

    const CODE_TYPE_APPLY_SIGNATURE_VERIFY = 'APPLY_SIGNATURE';
    const CODE_TYPE_NAME_APPLY_SIGNATURE_VERIFY = '申请数字证书';

    const CODE_TYPE_LOGIN_TMP_BONUS = 'LOGIN_TMP_BONUS';
    const CODE_TYPE_NAME_LOGIN_TMP_BONUS = '进入红包验证';

    const CODE_TYPE_MOBILE_CHANGE_EMAIL = 'MOBILE_CHANGE_EMAIL';
    const CODE_TYPE_NAME_MOBILE_CHANGE_EMAIL = '手机端更换邮箱';

    protected $tableName = 'mobile_validate_code';


    // 发送手机验证码
    function sendMobileCode(
        $mobile,
        $uid = 0,
        $type = self::CODE_TYPE_RESET_PAYPWD,
        $typeName = self::CODE_TYPE_NAME_RESET_PAYPWD,
        $seed = '',
        $messageParam = array()
    ) {
        $seed = $seed ? $seed : date('Ymd h:i');
        $uid = intval($uid);
        if (!in_array($uid, array(1, 2, 3, 4, 5, 6))) {
            if (!sms::isMobile($mobile)) {
                MyError::add('非法手机号，请输入正确的手机号');
                return false;
            }
        }

//        $key = 'MobileValidateCodeModel_sendMobileCode_'.$mobile;
//        if(cache($key)){
//             MyError::add('短信正在发送中，请稍等!');
//            return false;
//        }else{
//            cache($key,1,array('expire'=>5));
//        }

        $nowDate = date('Y-m-d H:i:s');
        $code = $this->getCode($mobile, $seed);
        $data['code'] = $code;
        $data['type'] = $type;
        $data['uid'] = $uid;
        $data['ctime'] = $nowDate;
        $data['mtime'] = $nowDate;
        $data['mobile'] = $mobile;
        $data['mi_no'] = BaseModel::getApiKey('api_key');

        $check_service = service('Account/Check');
        if (false === $check_service->checkSmsSendable($mobile, $uid, $type)) {
            MyError::add($check_service->getError());
            return false;
        }

        if ($type == self::CODE_TYPE_CASHOUT) {
            $messageParam[] = $code;
            $result = service('Message/Message')->sendMessage($uid, 1, 5, $messageParam);
            $send_result = $result['sms_result'];
        } else {
            $send_result = sms::send($mobile, '您本次操作的校验码是：' . $code . ', 请1分钟内使用。工作人员不会向您索取，请勿外泄。', $typeName, $uid);
        }
        if ($send_result['boolen'] == 1 || ($uid && $uid < 10)) {
            $data['sms_id'] = $send_result['id'];
            if ($uid) {
                $this->where(array('uid' => $uid, 'type' => $type))->save(array('status' => 0,'mtime' => $nowDate));
            }
            $id = $this->add($data);
            if (!$id) {
                MyError::add('发送失败');
                return false;
            }
            return true;
        } else {
            if ($send_result && $send_result['message']) {
                MyError::add($send_result['message']);
            } else {
                MyError::add('系统异常，发送短信失败，请联系系统管理员，稍后再试');
            }
            return false;
        }
    }

    // 获取验证码
    function getCode($mobile, $seed = '')
    {
        $seed = $seed ? $seed : date('Ymd h:i');
        return Sms::getCode($mobile, $seed);
    }

    // 认证
    function validateMobileCode($mobile, $code, $type = self::CODE_TYPE_RESET_PAYPWD, $expireTime = 60)
    {
        // $expireTime = 1200000; // 强制所有验证码有效时间
//      if(!sms::isMobile($mobile)){
//     	    MyError::add('非法手机号，请输入正确的手机号');
//     	    return false;
//     	}

        if (!$code) {
            MyError::add('动态码为空!');
            return false;
        }

        $info = $this->where(array(
            'code' => $code,
            'mobile' => $mobile,
            'type' => $type,
            'mi_no' => BaseModel::getApiKey('api_key'),
            'status' => 1
        ))->order(array('id' => 'DESC'))->find();
//       echo $this->getLastSql();

        if (!$info) {
            MyError::add('动态码不正确!');
            return false;
        }

        $diffTime = time() - strtotime($info['ctime']);

        if ($expireTime) {
            if ($diffTime > $expireTime) {
                MyError::add('动态码已过期!');
                $this->where(array('id' => $info['id']))->save(array('status' => 0, 'mtime' => date('Y-m-d H:i:s')));
                return false;
            }
        }
        return true;
    }
}

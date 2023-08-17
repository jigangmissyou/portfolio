<?php

class ValidateService extends BaseService
{
    // 注册发送验证码
    function sendRegisterMobileCode($mobile, $type = '')
    {
        $obj = D('Account/MobileValidateCode');
        if ($type == 'wap') {
            $code_model = MobileValidateCodeModel::CODE_TYPE_REGISTER_WAP;
            $code_model_name = MobileValidateCodeModel::CODE_TYPE_NAME_REGISTER_WAP;
        } else {
            $code_model = MobileValidateCodeModel::CODE_TYPE_REGISTER;
            $code_model_name = MobileValidateCodeModel::CODE_TYPE_NAME_REGISTER;
        }
        return $obj->sendMobileCode($mobile, 0, $code_model, $code_model_name);
    }

    // 验证注册验证码
    function validateRegisterMobileCode($mobile, $code, $type = '')
    {
        $obj = D('Account/MobileValidateCode');
        if ($type == 'wap') {
            $code_type = MobileValidateCodeModel::CODE_TYPE_REGISTER_WAP;
        } else {
            $code_type = MobileValidateCodeModel::CODE_TYPE_REGISTER;
        }
        return $obj->validateMobileCode($mobile, $code, $code_type, 120);
    }

    // 登录发送验证码
    function sendLoginMobileCode($mobile)
    {
        $obj = D('Account/MobileValidateCode');
        return $obj->sendMobileCode($mobile, 0, MobileValidateCodeModel::CODE_TYPE_LOGIN,
            MobileValidateCodeModel::CODE_TYPE_NAME_LOGIN);
    }

    // 验证登录验证码
    function validateLoginMobileCode($mobile, $code)
    {
        $obj = D('Account/MobileValidateCode');
        return $obj->validateMobileCode($mobile, $code, MobileValidateCodeModel::CODE_TYPE_LOGIN, 120);
    }

    // 忘记密码第二步
    function sendForgetpwd2MobileCode($mobile)
    {
        $obj = D('Account/MobileValidateCode');
        return $obj->sendMobileCode($mobile, 0, MobileValidateCodeModel::CODE_TYPE_FORGETPWD2,
            MobileValidateCodeModel::CODE_TYPE_NAME_FORGETPWD2);
    }

    // 忘记密码第二步验证
    function validateForgetpwd2MobileCode($mobile, $code)
    {
        $obj = D('Account/MobileValidateCode');
        return $obj->validateMobileCode($mobile, $code, MobileValidateCodeModel::CODE_TYPE_FORGETPWD2, 120);
    }

    // 提现发送验证码
    function sendCashout2MobileCode($mobile, $uid, $messageParam)
    {
        $obj = D('Account/MobileValidateCode');
        return $obj->sendMobileCode($mobile, $uid, MobileValidateCodeModel::CODE_TYPE_CASHOUT,
            MobileValidateCodeModel::CODE_TYPE_NAME_CASHOUT, '', $messageParam);
    }

    // 提现验证验证码
    function validateCashout2MobileCode($mobile, $code)
    {
        $obj = D('Account/MobileValidateCode');
        return $obj->validateMobileCode($mobile, $code, MobileValidateCodeModel::CODE_TYPE_CASHOUT, 120);
    }

    // 修改手机发送动态码
    function sendEditMobileCode($mobile)
    {
        $obj = D('Account/MobileValidateCode');
        return $obj->sendMobileCode($mobile, 0, MobileValidateCodeModel::CODE_TYPE_EDIT_MOBILE,
            MobileValidateCodeModel::CODE_TYPE_NAME_EDIT_MOBILE);
    }

    // 修改手机 验证
    function validateEditMobileCode($mobile, $code)
    {
        $obj = D('Account/MobileValidateCode');
        return $obj->validateMobileCode($mobile, $code, MobileValidateCodeModel::CODE_TYPE_EDIT_MOBILE, 120);
    }

    // 发送认证code
    function sendAuthMobileCode($mobile)
    {
        $obj = D('Account/MobileValidateCode');
        return $obj->sendMobileCode($mobile, 0, MobileValidateCodeModel::CODE_TYPE_AUTHOR_MOBILE,
            MobileValidateCodeModel::CODE_TYPE_NAME_AUTHOR_MOBILE);
    }

    // 认证手机验证码
    function validateAuthMobileCode($mobile, $code)
    {
        $obj = D('Account/MobileValidateCode');
        return $obj->validateMobileCode($mobile, $code, MobileValidateCodeModel::CODE_TYPE_AUTHOR_MOBILE, 120);
    }

    // 发送检查当前手机号码code
    function sendCheckOldMobileCode($mobile)
    {
        $obj = D('Account/MobileValidateCode');
        return $obj->sendMobileCode($mobile, 0, MobileValidateCodeModel::CODE_TYPE_CHECK_OLD_MOBILE,
            MobileValidateCodeModel::CODE_TYPE_CHECK_OLD_MOBILE_NAME);
    }

    // 认证
    function validateCheckOldMobileCode($mobile, $code)
    {
        $obj = D('Account/MobileValidateCode');
        return $obj->validateMobileCode($mobile, $code, MobileValidateCodeModel::CODE_TYPE_CHECK_OLD_MOBILE, 120);
    }

    // 2014.1.15撤销的认证申请
    function sendCancelApplyMobileCode($mobile)
    {
        $obj = D('Account/MobileValidateCode');
        return $obj->sendMobileCode($mobile, 0, MobileValidateCodeModel::CODE_TYPE_CANCEL_APPLY,
            MobileValidateCodeModel::CODE_TYPE_NAME_CANCEL_APPLY);
    }

    // 2014.1.15撤销的认证申请 验证验证码
    function validateCancelApplyMobileCode($mobile, $code)
    {
        $obj = D('Account/MobileValidateCode');
        return $obj->validateMobileCode($mobile, $code, MobileValidateCodeModel::CODE_TYPE_CANCEL_APPLY, 120);
    }

    // 发送支付密码找回手机验证码
    function sendResetPayPwdMobileCode($uid)
    {
        $uid = intval($uid);
        $obj = D('Account/MobileValidateCode');
        $userObj = D('Account/User');
        $userInfo = $userObj->getByUid($uid);
        if (!$userInfo) {
            MyError::add('异常,用户不存在!');
            return false;
        }

        if (!$userInfo['mobile'] || !$userInfo['is_mobile_auth']) {
            MyError::add('您的手机号码还没有验证，不能进行此操作!');
            return false;
        }

        $mobile = $userInfo['mobile'];
        return $obj->sendMobileCode($mobile, $uid, MobileValidateCodeModel::CODE_TYPE_RESET_PAYPWD,
            MobileValidateCodeModel::CODE_TYPE_NAME_RESET_PAYPWD);
    }

    // 认证支付密码找回手机验证码
    function validateResetPayPwdMobileCode($uid, $code)
    {
        $uid = intval($uid);
        $userObj = D('Account/User');
        $userInfo = $userObj->getByUid($uid);
        if (!$userInfo) {
            MyError::add('异常,用户不存在!');
            return false;
        }
        $mobile = $userInfo['mobile'];
        $obj = D('Account/MobileValidateCode');
        return $obj->validateMobileCode($mobile, $code, MobileValidateCodeModel::CODE_TYPE_RESET_PAYPWD, 120);
    }


    // 手机支付密码
    function sendCodeMobilePayPassword($uid)
    {
        $uid = intval($uid);
        $obj = D('Account/MobileValidateCode');
        $userObj = D('Account/User');
        $userInfo = $userObj->getByUid($uid);
        if (!$userInfo) {
            MyError::add('异常,用户不存在!');
            return false;
        }

        if (!$userInfo['mobile'] || !$userInfo['is_mobile_auth']) {
            MyError::add('您的手机号码还没有验证，不能进行此操作!');
            return false;
        }

        $mobile = $userInfo['mobile'];
        return $obj->sendMobileCode($mobile, $uid, MobileValidateCodeModel::CODE_TYPE_RESET_PAYPWD_MOBILE,
            MobileValidateCodeModel::CODE_TYPE_NAME_RESET_PAYPWD_MOBILE);
    }


    // 手机支付密码验证
    function validateCodeMobilePayPassword($uid, $code)
    {
        $uid = intval($uid);
        $userObj = D('Account/User');
        $userInfo = $userObj->getByUid($uid);
        if (!$userInfo) {
            MyError::add('异常,用户不存在!');
            return false;
        }
        $mobile = $userInfo['mobile'];
        $obj = D('Account/MobileValidateCode');
        return $obj->validateMobileCode($mobile, $code, MobileValidateCodeModel::CODE_TYPE_RESET_PAYPWD_MOBILE, 120);
    }


    // 发送产品转让手机验证码
    function sendTransferMobileCode($uid, $mobile = '')
    {
        $mdMobilevalidateCode = D('Account/MobileValidateCode');
        $ret = $mdMobilevalidateCode->sendMobileCode($mobile, $uid, MobileValidateCodeModel::CODE_TYPE_TRANSFER,
            MobileValidateCodeModel::CODE_TYPE_TRANSFER_NAME);
        if (!$ret) {
            return array(0, MyError::lastError());
        }

        return array(1, '已发送');
    }


    // 验证产品转让手机验证码
    function validateTransferMobileCode($mobile, $code)
    {
        $mdMobilevalidateCode = D('Account/MobileValidateCode');
        $ret = $mdMobilevalidateCode->validateMobileCode($mobile, $code, MobileValidateCodeModel::CODE_TYPE_TRANSFER,
            120);
        if (!$ret) {
            return array(0, MyError::lastError());
        }

        return array(1, '验证成功');
    }

    // 发送财富中心用户认证手机验证码
    function sendWealthVerifyMobileCode($mobile)
    {
        $obj = D('Account/MobileValidateCode');
        return $obj->sendMobileCode($mobile, 0, MobileValidateCodeModel::CODE_TYPE_WEALTH_VERIFY,
            MobileValidateCodeModel::CODE_TYPE_NAME_WEALTH_VERIFY);
    }

    // 验证财富中心用户认证手机验证码
    function validateWealthVerifyMobileCode($mobile, $code)
    {
        $obj = D('Account/MobileValidateCode');
        return $obj->validateMobileCode($mobile, $code, MobileValidateCodeModel::CODE_TYPE_WEALTH_VERIFY, 120);
    }

    // 发送申请数字证书手机验证码
    function sendApplySignatureMobileCode($mobile,$uid)
    {
        $obj = D('Account/MobileValidateCode');
        return $obj->sendMobileCode($mobile, $uid, MobileValidateCodeModel::CODE_TYPE_APPLY_SIGNATURE_VERIFY,
            MobileValidateCodeModel::CODE_TYPE_NAME_APPLY_SIGNATURE_VERIFY);
    }

    // 验证申请数字证书手机验证码
    function validateApplySignatureMobileCode($mobile, $code)
    {
        $obj = D('Account/MobileValidateCode');
        return $obj->validateMobileCode($mobile, $code, MobileValidateCodeModel::CODE_TYPE_APPLY_SIGNATURE_VERIFY, 120);
    }

    public function sendLoginTmpBonus($mobile)
    {
        $obj = D('Account/MobileValidateCode');
        return $obj->sendMobileCode($mobile, 0, MobileValidateCodeModel::CODE_TYPE_LOGIN_TMP_BONUS,
            MobileValidateCodeModel::CODE_TYPE_NAME_LOGIN_TMP_BONUS);
    }

    public function validateLoginTmpBonus($mobile, $code)
    {
        $obj = D('Account/MobileValidateCode');
        return $obj->validateMobileCode($mobile, $code, MobileValidateCodeModel::CODE_TYPE_LOGIN_TMP_BONUS, 120);
    }

    // 手机支付密码
    function sendCodeGeneric($uid, $type, $type_name)
    {
        $uid = intval($uid);
        $mdUser = D('Account/User');
        $user = $mdUser->getByUid($uid);
        if (!$user) {
            MyError::add('异常,用户不存在!');
            return false;
        }

        if (!$user['mobile'] || !$user['is_mobile_auth']) {
            MyError::add('您的手机号码还没有验证，不能进行此操作!');
            return false;
        }

        $mobile = $user['mobile'];
        $mdMobileValidateCode = D('Account/MobileValidateCode');
        return $mdMobileValidateCode->sendMobileCode($mobile, $uid, $type, $type_name);
    }


    // 手机支付密码验证
    function validateCodeGeneric($uid, $code, $type)
    {
        $uid = intval($uid);
        $mdUser = D('Account/User');
        $user = $mdUser->getByUid($uid);
        if (!$user) {
            MyError::add('异常,用户不存在!');
            return false;
        }
        $mobile = $user['mobile'];
        $mdMobileValidateCode = D('Account/MobileValidateCode');
        return $mdMobileValidateCode->validateMobileCode($mobile, $code, $type, 120);
    }
}

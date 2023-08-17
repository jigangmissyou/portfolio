<?php
class MobileAuthAction extends BaseAction {
	//发送验证码
	public function sendVerifyCode(){
		$mobile = $this->_post('mobile');
		if(!$mobile) return array('boolen'=>0, 'message'=>'请输入手机号');
		$result = service('MobileAuth')->sendVerifyCode($this->loginedUserInfo['uid'], $mobile);
		showJson($result);
	}
	
	//校验验证码
	public function checkVerifyCode(){//用post传入吧
		$code = $this->_post('code');
		$mobile = $this->_post('mobile');
		if(!$code) return array('boolen'=>0, 'message'=>'请输入验证码');
		$result = service('MobileAuth')->checkVerifyCode($this->loginedUserInfo['uid'], $code);
		$result['data']=array("mobile"=>$mobile);
		showJson($result);
	}
}
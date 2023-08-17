<?php

/**
 * 推荐好友
 */
class RecommendAction extends BaseAction
{
    public function _initialize() {
        parent::_initialize();

        $this->assign('basicUserInfo',service('Account/Account')->basicUserInfo($this->loginedUserInfo));
    }

    function index()
    {
        $uid = $this->loginedUserInfo['uid'];
        $url = D("Account/Recommend")->getUidTokenUrl($uid);
        $this->assign("url", $url);
        //推荐码
        $this->userCode = D("Account/Recommend")->getUserCode($uid);

//        $recommend = '1t' . D('Account/Recommend')->encodeUid($uid);
//        $url2 = $url = 'http://' . C('SITE_DOMAIN') . '/Account/Recommend/t?r=';
//        $url2 .= $recommend;
        $url2 = 'http://' . C('SITE_DOMAIN') . '/Mobile2/Share/t?t=1&r=';
        $recommend = '1t' . D('Account/Recommend')->encodeUid($uid);
        $url2 .= $recommend;
        $qrcode = '//' . $_SERVER['HTTP_HOST'] . '/Mobile2/Share/qrCode?decode=b64&code=' . base64_encode($url2);
        $this->assign('qrcode', $qrcode);

		$this->display("index");
	}

    public function t() {
        $recommend = I('request.r');
        $url = C('WAP_API_URL') . '#ac=index.register&recommend=';
        $url .= $recommend;

        redirect($url);
    }

	function recommendList(){
		$page = (int) $this->_request("p");
        $page = $page ? $page :1;
        $pageSize = 10;

        $orderBy = "ctime DESC";

		$where['user_recommed.from_uid'] = $this->loginedUserInfo['uid'];
//		$where['user_recommed.step'] = 1;
		$result = D("Account/Recommend")->recommendList($where,$page,$pageSize,$orderBy);
		$list = $result['data'];

        $totalRow = $result['total_row'];
        $this->assign("list", $list);
        //分页
        $paging = W("Paging", array("totalRows" => $totalRow, "pageSize" => $pageSize, "parameter" => array()), true);
        $this->assign("paging", $paging);
        $this->display("recommendlist");
    }

    function qq()
    {
        $uid = $this->loginedUserInfo['uid'];
        $content = D("Account/Recommend")->getQQContent($uid);
        $this->assign("content", $content);

        $this->display();
    }

    function msn()
    {
        $uid = $this->loginedUserInfo['uid'];
        $content = D("Account/Recommend")->getContent($uid);
        $this->assign("content", $content);

        $this->display();
    }

    function email()
    {
        C('TOKEN_ON', false);
        $uid = $this->loginedUserInfo['uid'];
        $content = D("Account/Recommend")->getContent($uid);
        $this->assign("content", $content);
        $this->assign('time', time());
        $this->display();
    }

    //发送邮件
    function sendEmail()
    {
        $email = trim($this->_request("email"), ',');
        $authCode = $this->_request("authCode");
        if (!check_verify($authCode)) {
            ajaxReturn(0, '验证码错误', 0);
        }
        $emails = explode(',', $email);
        if (!$emails) {
            ajaxReturn(0, "邮箱地址不能为空!", 0);
        }
        $uid = $this->loginedUserInfo['uid'];
        if ($emails) {
            $emails = array_unique($emails);
            foreach ($emails as $k => $v) {
                if (!D("Account/Recommend")->save2Queue($v, $uid)) {
                    ajaxReturn(0, '操作失败，网络连接不成功，请稍候重试', 0);
                }
            }
        }
        ajaxReturn(0, "操作成功!");
    }


    function activeRecommendActive()
    {

        $uid = (int)$this->loginedUserInfo['uid'];
        $url = $uid ? D("Account/Recommend")->getUidTokenUrl($uid) : "";
        $this->assign("url", $url);

        $this->display("activerecommendactive");
    }

    function activeRecommendList()
    {
        $page = (int)$this->_request("p");
        $page = $page ? $page : 1;
        $pageSize = 10;

        $orderBy = "ctime DESC";

        $where['from_uid'] = $this->loginedUserInfo['uid'];
        $result = D("Account/Recommend")->recommendList($where, $page, $pageSize, $orderBy);

        $list = $result['data'];

        $totalRow = $result['total_row'];
        $this->assign("list", $list);
        //分页
        $paging = W("Paging", array("totalRows" => $totalRow, "pageSize" => $pageSize, "parameter" => array()), true);
        $this->assign("paging", $paging);
        $this->assign("uid", $this->loginedUserInfo['uid']);

        $this->display("activerecommendlist");
    }

}

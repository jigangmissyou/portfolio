<?php

//好友推荐
class RecommendModel extends BaseModel
{
    protected $tableName = 'user_recommed';
    const EMAIL_SEND_QUEUE_KEY = "EMAIL_SEND_QUEUE_KEY";
    const CHANNEL_DEFAULT = 1; // 默认推荐渠道
    const CHANNEL_SMS = 2; // 短信推荐渠道

    function recommendList($where, $pageNumber, $pageSize, $orderBy, $getTotal = true)
    {
        $model = $this->where($where);
        if ($pageNumber && $pageSize) $model = $model->page($pageNumber . "," . $pageSize);
        if ($orderBy) $model = $model->order($orderBy);
        $model = $model->Table("fi_user_recommed user_recommed");
        $model = $model->field("user_recommed.is_invest,user_recommed.is_recharge,has_get_hongbao,
                                user.uname,user_recommed.ctime,user_recommed.is_xpd, user.is_id_auth, user.mobile,user.uid,user_recommed.last_send_bonus_time");
        $model = $model->join("INNER JOIN fi_user user ON user_recommed.uid=user.uid ");
        $data = $model->select();
        $output['data'] = $data;

        if ($getTotal) {
            if ($where) $modelCount = $this->where($where);
            $modelCount = $modelCount->Table("fi_user_recommed user_recommed");
            $modelCount = $modelCount->join("INNER JOIN fi_user user ON user_recommed.uid=user.uid ");
            $result = $modelCount->field("COUNT(*) AS CNT")->find(); // TODO: InnoDB的COUN(*)问题
            $totalRow = (int)$result['CNT'];
            $output['total_row'] = $totalRow;
        }
        return $output;
    }

    //检查推荐码
    function checkUserCode($uid, $userCode)
    {
        $recommend_code = $this->getUserCode($uid);
        return $recommend_code == $userCode;
    }


    function getUidByUserCode($userCode){
        if(M('recommend_code')->where(array('code' => $userCode))->find()) return 1;
        //新增加需求，平台红包代码
        if(D("Account/UserBonusCode")->getBonusCodeInfo(array('code'=>$userCode))) return 1;

        $userCode = preg_replace('/-.*$/', '', $userCode);
        if(!preg_match('/^[0-9]+$/',$userCode)) return 0;
        return M("user_ext")->where(array("recommend_code"=>$userCode))->getField("uid");
    }


    /**
     * 获取红包代码
     * @param $uid
     */
    function getUserCode($uid)
    {
        $recommend_code = M("user_ext")->where(array("uid" => $uid))->getField("recommend_code");
        if ($recommend_code) return $recommend_code;
        return $this->addUserCode($uid);
    }


    function addUserCode($uid)
    {
        //没有则生成
        $mobile = M("user")->where(array("uid" => $uid))->getField("mobile");
        if (!$mobile) return errorReturn("手机号码不存在!");
        $mobileTail = substr($mobile, -6);
        //缓存处理并发
        $cachekey = "USER_RECOMMEND_CODE_" . $mobileTail;
        if (cache_with_mi_no($cachekey)) {
            return errorReturn("验证码并发异常!");
        }
        cache_with_mi_no($cachekey, 1, array('expire' => 2));
        $userExt = M("user_ext")->where(array("uid" => $uid))->find();
        $uniqInfo = M("user_ext")->where(array("mobile_tail" => $mobileTail))->order("recommend_incr desc")->find();
        $now = time();
        $idata = array();
        $idata['mobile_tail'] = $mobileTail;
        if ($uniqInfo) {
            $number = intval($uniqInfo['recommend_incr']) + 1;
            $recommend_code = $mobileTail . $number;
            $idata['recommend_incr'] = $number;
            $idata['recommend_code'] = $recommend_code;
        } else {
            $recommend_code = $mobileTail;
            $idata['recommend_incr'] = 0;
            $idata['recommend_code'] = $recommend_code;
        }
        if (!$userExt) {
            $idata['uid'] = $uid;
            $idata['mtime'] = $now;
            $idata['ctime'] = $now;
            M("user_ext")->add($idata);
        } else {
            $idata['mtime'] = $now;
            M("user_ext")->where(array("uid" => $uid))->save($idata);
        }
        cache_with_mi_no($cachekey, null);
        return $recommend_code;
    }

    function getUidTokenUrl($uid, $channel = self::CHANNEL_DEFAULT)
    {
        $recommend = $channel . "t" . $this->encodeUid($uid);
        $api_info = service('ApiServer/Member')->getApiInfo();
        $url = $api_info['mi_host'] .'/Account/User/register?recommend='. $recommend;
        return $url;
    }
    //秋收起义新增的方法
    function getUidTokenUrlFall($recommend, $type=1)
    {
//        $recommend = $channel . "t" . $this->encodeUid($uid);
        $api_info = service('ApiServer/Member')->getApiInfo();
//        $url = $api_info['mi_host'] .'/Mobile/Act/showHere?recommend='. $recommend;
        if(visit_source() == 'weixin'){
            $url = service('Activity/AugustRevolution')->getWeixXinUrl($recommend);
        }else{
            $url = $api_info['mi_host'] .'/Mobile/Act/showHere?recommend='. $recommend;
        }
        return $url;
    }


    function encodeUid($uid)
    {
        if (!$uid) return false;
        $uid = $uid << 1;
        $uid = $uid + 33;
        return $uid;
    }

    function decodeUid($uid)
    {
        if (!$uid) return false;
        $uid = $uid - 33;
        $uid = $uid >> 1;
        return $uid;
    }

    //保存发送信息到队列
    function save2Queue($email, $uid)
    {
        $key = self::EMAIL_SEND_QUEUE_KEY;
        import_addon("libs.Cache.RedisList");
        $redisObj = RedisList::getInstance($key);
        if(empty($api_key)){
            $api_key = BaseModel::getApiKey('api_key');
        }
        $data['email'] = $email;
        $data['uid'] = $uid;
        $data['api_key'] = $api_key; //记录邮件的api_key
        return $redisObj->lPush($data);
    }

    function parse2Uid($recommend)
    {
        $recommend = trim($recommend);
        if (preg_match("/\d+t\d+/", $recommend)) {
            $resultArr = explode('t', $recommend);
            if (!$resultArr) return $resultArr;
            preg_match("/\d+/", $resultArr[1], $math);
            $tmpUidStr = $math[0];
            $fromUid = D("Account/Recommend")->decodeUid($tmpUidStr);
        } else {
            $tokenArr = explode("recommend", caesar_decode($recommend));
            $fromUid = isset($tokenArr[1]) ? $tokenArr[1] : 0;
        }
        return $fromUid;
    }

    function _getContent($uid, $isEmail = 0,$api_key='')
    {
        $url = $this->getUidTokenUrl($uid);
        $userinfo = M("user")->where(array("uid" => $uid))->find();
        if ($isEmail) $url = "<a href=\"" . $url . "\" target=\"_blank\">" . $url . "</a>";
        $contentArr = array();
        $url = str_replace('http://', "", $url);
        $url = str_replace('https://', "", $url);
        $contentArr[] = $userinfo['real_name'];
        $contentArr[] = $url;
        return D('Message/Message')->getMessageTpl(44, 2, $contentArr,$api_key);
    }

    //获取内容
    function getContent($uid, $isEmail = 0,$api_key='')
    {
//        $content = getSysData("recommend","content");
//        $content = $content['content'];
        $result = $this->_getContent($uid, $isEmail,$api_key);
        $content = $result['pass_content'];
        return $content;
    }

    function getQQContent($uid)
    {
        $content = getSysData("recommend", "content");
        $content = $content['content'];
        $content=str_replace("本息全额担保，","",$content);
        $url = $this->getUidTokenUrl($uid);
        $userinfo = M("user")->where(array("uid" => $uid))->find();
        $content = str_replace('{__url__}', $url, $content);
        $content = str_replace('{1}', $userinfo['real_name'], $content);
        return $content;
    }

    function getTitle($api_key='')
    {
        $content = $this->_getContent(0, 0,$api_key);
        return $content['title'];
    }

    //保存
    function save($fromUid,$uid,$hasGetHongbao=0,$hongbaoCode=''){
        if(!$fromUid || !$uid) return false;
        $uname = M('user')->where(array("uid"=>$uid))->getField("uname");
        $is_newxpd = D('Account/XinPartner')->getIshistoryfr($fromUid);//是否鑫拍档之后邀请的用户
        $fromUid2 = 0;
        if($is_newxpd) $fromUid2 = $this->getParentUid($fromUid);//获取推荐人的推荐人id
        $is_xpd = D('Account/XinPartner')->getIsXpd($fromUid);//获取推荐人是否参与了鑫拍档
        if(!$uname) return false;
        $data['uid'] = $uid;
        $data['uname'] = $uname;
        $data['from_uid'] = $fromUid;
        $data['from_uid2'] = $fromUid2;//推荐人的推荐人id，鑫拍档
        $data['is_xpd'] = $is_xpd;//推荐人是否参与了鑫拍档
        $data['is_invest'] = 0;
        $data['is_recharge'] = 0;
        $data['has_get_hongbao'] = $hasGetHongbao;
        $data['ctime'] = time();
        $data['mtime'] = time();
        $data['hongbao_code'] = $hongbaoCode;
        if(strpos($hongbaoCode, '-') !== FALSE) $data['step'] = 2;
        else $data['step'] = 1;
        $recommend_user_id = M('recommend_code')->where(array('code' => $hongbaoCode))->getField('id');
        if($recommend_user_id) $data['recommend_code_id'] = $recommend_user_id;
        $result= $this->add($data);
        return $result;
    }

    /**
     * 获取推荐人的UID
     */
    public function getParentUid($uid){
        $where = array(
            'uid'=>$uid
        );
        $field = array("from_uid");
        $info = $this->where($where)->field($field)->find();
        if($info){
            return $info['from_uid'];
        }
        return 0;
    }

    /*
     * 获取推荐人属性
     */
    public function get_parent($uid) {
        $where = array('uid'=>$uid);
        $field = array("uid", "from_uid", "is_xpd","ctime");
        $info = $this->where($where)->field($field)->find();
        return $info;
    }

    /*
     * 获取推荐人姓名
     */
    public function getRealName($uid) {
        $where = array(
            'uid'=>$uid
        );
        $field = array("real_name");
        $info = M('user')->where($where)->field($field)->find();
        if($info){
            return remainFirst($info['real_name']);
        }
        return null;
    }
}


<?php

/**
 * User: 001181
 * Date: 2015-02-02 17:45
 * $Id$
 *
 * Type: Action
 * Group: Public
 * Description: 登陆以后触发的事件行为的实现(同步阻塞)
 */
class LoginEventService extends BaseService {

    protected $uid = 0;
    protected $userinfo = array();
    protected $is_active = false;//是否激活
    protected $base_mod;
    protected $current_id = 0;

    public function __construct() {
        parent::__construct();
        $this->base_mod = new BaseModel();
    }

    //调用此服务的时候必须初始化改类，否则用户登陆的UID获取不到
    public function init($userinfo) {
        $this->uid = $userinfo['uid'];
        $this->userinfo = $userinfo;
        $this->is_active = service("Account/RegBonusRule")->isActive();
        return $this;
    }

    /**
     * 获取用户信息
     * @param $params
     * @return bool
     */
    public function getUserInfo($params)
    {
        $uid = $params['uid'];
        try {
            if (!$uid) {
                throw_exception("参数错误~");
            }
            $userinfo = D("Account/User")->getByUid($uid);
            if (!$userinfo) {
                throw_exception(D("Account/User")->getError());
            }
            return $userinfo;
        } catch (Exception $e) {
            $this->error = $e->getMessage();
            return false;
        }
    }

    /**
     * 初始化所有的临时信息
     */
    public function initAllTmpRecode()
    {
        //更新行为记录
        //添加日志记录
        //把金额给补充到正式红包中
        if (!$this->is_active) {
            $this->error = "活动已过期";
            return false;
        }
        $user_bonus_mod = D("Account/UserBonus");
        $user_mod = D("Account/User");
        //如果获取用户的身份，分享者还是被分享者。获取提升以后的当前的状态（11,10,01）
        $mobile = $this->userinfo['mobile'];
        $where = array(
            '_string' => "mobile = '{$mobile}' or orginMobile='{$mobile}'",
            'status' => 0,//待处理
        );
        $share_log_list = D("Account/UserBonusShareLog")->where($where)->select();
        if (!$share_log_list) {
            $this->error = "没有要处理的记录";
            return false;
        }
        //获取当前用户是A还是B（分享者还是被分享者）
        //获取当前的身份
        try {
            foreach ($share_log_list as $k => $v) {
                $this->current_id = 0;//重新初始化,当前操作的对象
                $a_mobile = $v['mobile'];
                $b_mobile = $v['orginMobile'];
                $behavior_id = $v['user_behavior_id'];//一切按照行为ID来决定金额变动
                $current_status = $this->currentStatus($a_mobile, $b_mobile);
                //更新状态
                $this->current_id = $v['id'];
                echo "正在处理" . $this->current_id . PHP_EOL;
                $this->base_mod->startTrans();
                echo "当前注册的状态".$current_status.PHP_EOL;
                switch ($current_status) {
                    case "1-1":
                        $user_bonus_mod->initRegUser($behavior_id, $this->current_id);
                        break;
                    case "0-1":
                        //注册用户一定是B，由0-0变化而来
                        $user_bonus_mod->initRegUser($behavior_id, $this->current_id);
                        break;
                }
                $this->base_mod->commit();
            }
            return true;
        } catch (Exception $e) {
            $this->error = $e->getMessage();
            $this->base_mod->rollback();
            D("Account/UserBonusShareLog")->updateProStatus($this->current_id, 1, $e->getMessage());
            return false;
        }
    }

    public function currentStatus($mobile, $orginMobile)
    {
        $user_mod= D("Account/User");
        $a_share_count = $user_mod->where(array('mobile' => $orginMobile))->count();
        $b_share_count = $user_mod->where(array('mobile' => $mobile))->count();
        if($a_share_count){
            $a = 1;
        }else{
            $a = 0;
        }
        if($b_share_count){
            $b = 1;
        }else{
            $b = 0;
        }
        return $a."-".$b;
    }

}

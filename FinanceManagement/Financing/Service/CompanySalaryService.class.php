<?php

/**
 * User: 001181
 * Date: 2015-01-08 09:39
 * $Id$
 *
 * Type: Action
 * Group: Fanancing
 * Description: 企福鑫服务
 */
class CompanySalaryService extends BaseService {

    //变现列表三个状态

    protected $uid = 0; //用户的UID
    public $user;
    public $user_account;
    public $is_login = false; //用户是否登陆
    public $user_company_id = 0; //用户加入的企业的ID
    protected $mod;
    protected $user_mod;
    
    protected $msg='ok';

    const COMPANY_SELECT = "全部";//共享标的概念

    static private $instance = array(); //单例 防止循环中多次查询

    /**
     * 首页的4种状态
     */
    const PAGE_STATUS_NO_LOGIN = 0; //未登陆
    const PAGE_STATUS_NO_PERMISSION = 1; //登陆无权限(普通用户,不是企福鑫用户)
    const PAGE_STATUS_NO_PROJECT = 2; //有权限(登陆了且是企福鑫用户但没有投资列表)
    const PAGE_STATUS_HAS_PROJECT = 3; //有权限(登陆了且是企福鑫用户、有投资列表)

    public function __construct() {
        parent::__construct();
        $this->user_mod = D("Account/UserExt");
        $this->mod = D("Public/OrganizationUserCorp");
    }

    /**
     * 强制登录
     */
    public function forceLogin() {
        if (!$this->is_login) {
            throw_exception("还没登陆,不能查看此页面!");
        }
        return true;
    }

    //调用此服务的时候必须初始化改类，否则用户登陆的UID获取不到
    public function init($uid) {
        $instance_key = md5($uid);
        if (!isset(self::$instance[$instance_key])) {
            $this->uid = $uid;
            $this->uid ? $this->is_login = true : $this->is_login = false;
            if ($this->is_login) {
                $this->user = M('User')->find($this->uid);
                $this->user_account = M('user_account')->find($this->uid);
                $this->user_company_id = $this->user_mod->getUserCompanyId($this->uid);
            }
            self::$instance[$instance_key] = $this;
        }

        return $this;
    }
    
    public function getMsg(){
        return $this->msg;
    }

    /**
     * 状态有四种
     * 1、未登陆
     * 2、登录无权限
     * 3、有权限
     */
    public function pageStatus() {
        if (!$this->is_login) {
            return self::PAGE_STATUS_NO_LOGIN;
        }
        //登陆了 但不是企福鑫用户,不可以投资企福鑫项目
        if (!$this->user_company_id) {
            return self::PAGE_STATUS_NO_PERMISSION;
        }
        //登陆了，显示项目列表
        //查询是否有项目显示
        $has_project = service("Financing/Project")->hasQfxProject($this->uid);
        if (!$has_project) {
            return self::PAGE_STATUS_NO_PROJECT;
        }
        return self::PAGE_STATUS_HAS_PROJECT;
    }

    /**
     * 获取企福鑫的过滤条件
     * 查询企福鑫页面的所有可投资项目的条件
     * $is_zs_user 用于判断是否存管项目
     */
    public function getQfxFilter($bid_status = array(),$is_zs_user = null) {
        //如果没有登录，不能查看此页面
        $this->forceLogin();

        if (!$this->user_company_id) {
            throw_exception("未加入企福鑫计划,无权查看此页面!");
        }
        //投标中的项目
        D("Financing/Prj");
        $where['status'] = PrjModel::STATUS_PASS;//审核通过
//        $where['is_show'] = 1;
        if(empty($bid_status)){
            $bid_status = array(
                PrjModel::BSTATUS_WATING,
                PrjModel::BSTATUS_BIDING,
                PrjModel::BSTATUS_FULL,
                PrjModel::BSTATUS_END,
            );
        }
        $where['bid_status'] = array(
            'IN',
            $bid_status
        );
        if(is_null($is_zs_user)){
            $where['_string'] = "exists(select 1 from fi_prj_ext where fi_prj.id=fi_prj_ext.prj_id and is_qfx=1 and ( company_id=0 or FIND_IN_SET(  '{$this->user_company_id}', company_id)))";//company_id 为0 表示属于所有企业的
        }else{//加上判断是否存管项目
            $where['_string'] = "exists(select 1 from fi_prj_ext where fi_prj.id=fi_prj_ext.prj_id and is_deposit={$is_zs_user} and is_qfx=1 and ( company_id=0 or FIND_IN_SET(  '{$this->user_company_id}', company_id)))";//company_id 为0 表示属于所有企业的
        }
//        $where['_string'] = "exists(select 1 from fi_prj_ext where fi_prj.id=fi_prj_ext.prj_id and is_qfx=1)";
        return $where;
    }

    /**
     * 其他的项目要过滤掉企福鑫项目和判断是否存管项目
     * 这里额外加上过滤的条件(不管当前用户是否是企福鑫用户)，以前的查询条件不变
     * $is_zs_user 用户判断是否存管项目
     */
    public function notListQfx($is_weixin = false,$is_zs_user=null) {
        //由于各个接口之间表alias了，所以这里做标记
        if($is_weixin){
            $table = "prj";
        }else{
            $table = "fi_prj";
        }
        if(is_null($is_zs_user)){
            $where = "exists(select 1 from fi_prj_ext where {$table}.id=fi_prj_ext.prj_id and (is_qfx=0 or (is_qfx =1 and {$table}.bid_status not in(".PrjModel::BSTATUS_WATING.",".PrjModel::BSTATUS_BIDING."))))";
        }else{//加上判断是否存管项目
            $where = "exists(select 1 from fi_prj_ext where {$table}.id=fi_prj_ext.prj_id and is_deposit= {$is_zs_user} and (is_qfx=0 or (is_qfx =1 and {$table}.bid_status not in(".PrjModel::BSTATUS_WATING.",".PrjModel::BSTATUS_BIDING."))))";
        }
        return $where;
    }
    
    /**
     * 获取财务中过滤用户身份的列表
     * 获取UPGer的用户身份
     */
    public function getCashFilter($company_id) {
         $where = "exists(select 1 from fi_user_ext where fi_cashout_apply.uid=fi_user_ext.uid and company_id={$company_id})";
         return $where;
    }

    /**
     * 根据企业的code来绑定用户信息
     * @param type $company_code
     */
    public function userBindCompanyByCode($company_code) {
        try {
            $this->forceLogin();
            $where = array(
                'company_code' => $company_code
            );
            $info = $this->mod->getCompanyInfoByWhere($where);
            if ($info) {
                $res = $this->userBindCompany($info['id']);
                return $res;
            } else {
                throw_exception("您输入的企业代码匹配不到相关企业,请重新输入!");
            }
        }
        catch (Exception $e) {
            throw_exception($e->getMessage());
        }
    }
    
    /**
     * 加入企业计划
     * @param type $type
     * @param type $code
     */
    public function userBindByQrcode($type,$code){
        switch ($type){
            case "company":
                $res = $this->userBindCompanyByCode($code);
                break;
            default:
                $res = true;
                break;
        }
        return $res;
    }
    
    /**
     * 绑定
     */
    public function userBindCompanyByRecommend($recommend) {
        $fromUid = $this->getFormUid($recommend);
        //查找上级用户是否有绑定
        $parentUserCompanyId = $this->user_mod->getUserCompanyId($fromUid);
        $extends_crop = M('user_extends_crop')->find($parentUserCompanyId);
        if ((int)$extends_crop['is_extends'] == 0) {
            return false;
        }
        if(!$parentUserCompanyId){
            return false;
        }
        try{
            return $this->userBindCompany($parentUserCompanyId);
        }catch(Exception $e){
              return false;
        }
    }

    /**
     * 用户加入企业计划
     * @param type $company_id 要绑定的company_id
     */
    public function userBindCompany($company_id) {
        try {
            $this->forceLogin();
            $res = $this->user_mod->userToggleBindCompany($this->uid, $this->user_company_id, $company_id);
            return $res;
        }
        catch (Exception $e) {
            throw_exception($e->getMessage());
        }
    }
    
    /**
     * 检查用户是否有权限来投资项目
     * @param type $info
     * @param type $uid
     */
    public function buyBeforeCheckPermission($info) {
        $is_qfx = $info['is_qfx'];
        $company_id = $info['company_id'];
        //不是企福鑫，不校验
        if(!$is_qfx){
           return true;
        }
        //下面都是项目是企福鑫状态
        //用户不是企福鑫用户
        if(!$this->user_company_id){
            $this->msg = "企福鑫员工才能投资此标";
            return false;
        }
        //是企福鑫 用户也是企福鑫(以及共享标)
        if( in_array($this->user_company_id,explode(",",$company_id))  || !$company_id){
           return true;
        }
        $this->msg = "此标不是您参与企业的标,无法投资!";
        return false;
    }

    /**
     * 获取企业的列表
     */
    public function getCompanyList($show_select = true) {
        $model = $this->mod;
        $list = $model->getCompanyByFetchWhere();
        if($show_select){
            $select_list = array(0 => array('id'=>0,'name'=>self::COMPANY_SELECT));
        }else{
            $select_list = array();
        }
        
        if (!$list) {
            return $select_list;
        }

        foreach ($list as $v) {
            $select_list[$v['id']] = array(
                'id'=>$v['id'],
                'name'=>$v['org_name'],
            );
        }
        return $select_list;
    }
    
    /**
     * 判断企业是否存在
     */
    public function companyIsExist($where) {
        $count = $this->mod->getCount($where);
        return $count?true:false;
    }

    /**
     * 数符合条件的企业数量
     */
    public function companyCount($map) {
        $count = $this->mod->getCount($map);
        return $count;
    }
    
    /**
     * 根据推荐码获取上级的用户ID
     * @param type $recommend
     * @return type
     */
    public function getFormUid($recommend) {
        if (preg_match("/\d+t\d+/", $recommend)) {
            $fromUid = D("Account/Recommend")->parse2Uid($recommend);
        } else {
            $fromUid = D("Account/Recommend")->getUidByUserCode($recommend);
        }
        return $fromUid?$fromUid:0;
    }
    
    /**
     * 参数生成
     */
    public function getQrcodeRecommend($type,$qrcode) {
        $data = "type={$type}&qrcode={$qrcode}";
        return base64_encode($data);
    }
    
    /**
     * 获取统计数据
     */
    public function getTotal(){
        //初始化值
        $total = array(
            'CORP_CNT' => 0,
            'USER_CNT' => 0,
            'PRJ_CNT' => 0,
            'TTL_PROFIT' => 0,
        );
        try {
            $res = M("rep_qfx_stats")->find();
            if ($res) {
                $record = array_change_key_case($res, CASE_UPPER);
                $record['TTL_PROFIT'] = moneyView($record['TTL_PROFIT'], 2); //把钱转为元
                return $record;
            }

            return $total;
        } catch (Exception $e) {
            return $total;
        }
    }

    /**
     * 根据企业统计信息
     * @param int $company_id
     * @return array
     */
    public function getTotalByCompanyId($company_id = 0){

        $total = array(
            'qfx_user_count' => 0,
            'prj_count' => 0,
            'income_count' => 0,
        );
        //还没有加入企福鑫
        if (!$company_id) {
            $company_id = $this->user_company_id;
        }
        if (!$company_id) {
            return $total;
        }
        $cache_key = 'company_total';
        $ret = S($cache_key);
        if (!$ret) {
            return $total;
        }
        //如果统计中有的话
        if (array_key_exists($company_id, array_keys($ret))) {
            return $ret[$company_id];
        }
        return $total;

    }
    
    /**
     * 获取logourl
     */
    public function getLogoUrl($company_id,$client_type = 'pc'){
        $where = array(
            'id'=>$company_id,
        );
        $client_types = array('pc','android','iphone');
        if(!in_array($client_type,$client_types)){
            $client_type = "android";//其他的终端全部用android的图片
        }
        $info = D("Public/OrganizationUserCorp")->getCompanyInfoByWhere($where);
        $logo_url = C("HTTP_HOST");
        if ($info && !empty($info['company_code'])) {
            $company_code = $info['company_code'];
            if (!file_exists("public/images/app/qfx/logo/{$company_code}" . _ . "{$client_type}.png")) {
                $company_code = "default";
            }
        } else {
            $company_code = "default";
        }
        $logo_path = "public/images/app/qfx/logo/{$company_code}"._."{$client_type}.png";
        return $logo_url."/".$logo_path;
    }
    
    
    /***
     * 根据项目ID获取企业名称
     */
    public function getCompanysByPrjId($prj_id) {
         $res = $this->mod->getCompanysByPrjId($prj_id);
        if ($res) {
            return $res;
        } else {
            $this->msg = $this->mod->getError();
            return false;
        }
    }
    
    /**
     * 企福鑫用户
     * 注册送红包
     */
    public function regBonus($hook_type = 1,$amount) {
        $this->forceLogin();
        $serRegBonusRule = service("Account/CompanySalaryRule");
        if ($serRegBonusRule->isActive()) {
            return D("Account/User")->newRegBonus($this->user,$hook_type,$amount);
        }
	    return false;
    }

    /**
     * 获取二维码地址
     */
    public function getQrcode($type,$code) {
        try {
            empty($code) && throw_exception("请输入正确的参数!");
            $url = C("HTTP_HOST") . "/Account/User/register?";

            $qurey_data = array(
                'type' => $type,
                'qrcode' => $code,
            );
            $content = $url . http_build_query($qurey_data);
            return $content;
        }
        catch (Exception $e) {
            echo $e->getMessage();
        }
    }

    /**
     * 根据企业的ID获取应该返现金额
     */
    public function getRewardMoneyByCompanyId($company_id = 0)
    {
        if (!$company_id) {
            $company_id = $this->user_company_id;
        }
        $mod = M("user_extends_crop");
        $reward_money = $mod->where(array('id' => $company_id))->getField("reward_money");
        if (!$reward_money) {
            $this->error = "没有奖励金额";
            return false;
        }
        return $reward_money;
    }

    public function getRewardMaxTimes($company_id = 0)
    {
        if (!$company_id) {
            $company_id = $this->user_company_id;
        }
        if ($company_id) {
            $max_reward_times = M("user_extends_crop")
                ->where(array('id' => $company_id))
                ->getField("max_reward_times");
            if ($max_reward_times) {
                return $max_reward_times;
            }
            return 0;
        }
        return 0;
    }

    /**
     * 根据企业的ID获取应该注册金额
     */
    public function getRegMoneyByCompanyId($company_id = 0)
    {
        if (!$company_id) {
            $company_id = $this->user_company_id;
        }
        $mod = M("user_extends_crop");
        $reg_money = $mod->where(array('id' => $company_id))->getField("reg_money");
        if (!$reg_money) {
            $this->error = "注册金额为0";
            return false;
        }
        return $reg_money;
    }

}

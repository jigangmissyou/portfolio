<?php

/**
 * 我的红包(暂只支持摇一摇红包)
 * User: 001181
 * Date: 2015-01-07 13:39
 * $Id$
 *
 * Type: Action
 * Group: Mobile2
 * Module: 我的红包
 * Description: 我的红包(暂只支持摇一摇红包)
 */
use App\Modules\JavaApi\Service\JavaBonusService as JavaBonusService;
use App\Lib\Service\JavaService as JavaService;


class MyBonusAction extends BaseAction {

    const PAGESIZE = 8;
    const CONPUS_PAGESIZE = 6; //满减券、加息券分页条数

    const BONUS_STATUS_ALL = 0;//全部
    const BONUS_STATUS_NO_USE = 1;//未使用
    const BONUS_STATUS_USED = 2;//已使用
    const BONUS_STATUS_EXPIRED = 3;//已过期

    const COUPONS_STATUS_ALL = 0;//满减券全部
    const COUPONS_STATUS_NO_USE = 1;//未使用
    const COUPONS_STATUS_USED = 2;//已使用
    const COUPONS_STATUS_EXPIRED = 3;//已过期

    const ADDRATE_STATUS_ALL = 0;//加息券全部
    const ADDRATE_STATUS_NO_USE = 1;//未使用
    const ADDRATE_STATUS_USED = 2;//已使用
    const ADDRATE_STATUS_EXPIRED = 3;//已过期

    const LCJ_STATUS_ALL = 0;//理财金全部
    const LCJ_STATUS_NO_USE = 1;//未使用
    const LCJ_STATUS_USED = 2;//已使用(4收益待提取5收益已提取6收益已过期)
    const LCJ_STATUS_EXPIRED = 3;//已过期
    const LCJ_STATUS_UNDRAW = 4;//收益待提取
    const LCJ_STATUS_DRAWED = 5;//收益已提取
    const LCJ_STATUS_PROFITEXPIRED = 6;//收益已过期

    const PLATFORM = "Pc";

    private $uid;
    private $modUserBonusLog;
    private $serUserBonusLog;
    private $_service;

    public function _initialize() {
        parent::_initialize();

        $this->uid = $this->loginedUserInfo['uid'];
        if (!$this->uid) {
           redirect("/login?url=/Account/MyBonus/getMyCouponsList");
        }
        $this->modUserBonusLog = D('Account/UserBonusLog');
        $this->serUserBonusLog = service('Account/UserBonusLog')->setPlatForm(self::PLATFORM);
        $this->_service = new JavaBonusService();
        $this->serUserCoupons = service('Account/UserMarketTicket')->setPlatForm(self::PLATFORM);
        $this->assign('basicUserInfo', service('Account/Account')->basicUserInfo($this->loginedUserInfo));
        $my_prize = service('Account/UserMarketTicket')->getAllCouponsReward($this->uid);
        $this->assign('my_prize', $my_prize);
    }

    /**
     * 我的红包外框
     */
    public function getMyBonusList(){
        //我的红包的金额
        D("Account/UserBonusPackage");
        $service = service("Account/UserBonusLog");
        $ser = service("Account/UserBonus")->init($this->uid);//$uid当前用户UID
        $money = $ser->getMyBonusOld();
        $receive_bonus = $service->getMyBonusAndOther(UserBonusLogService::BONUS_VIEW_TYPE_RECEIVE,$this->uid);
        $use_bonus = $service->getMyBonusAndOther(UserBonusLogService::BONUS_VIEW_TYPE_USE,$this->uid);
        $expire_bonus = $service->getMyBonusAndOther(UserBonusLogService::BONUS_VIEW_TYPE_EXPIRE,$this->uid);
        $this->assign("receive_bonus",  moneyView($receive_bonus,2));
        $this->assign("use_bonus",  moneyView($use_bonus,2));
        $this->assign("expire_bonus",  moneyView($expire_bonus,2));//已过期
        $this->assign("money",  moneyView($money,2));
        $this->assign("bonus_type",2);
        $this->assign("is_tips",$this->_service->getUserBonusTips($this->uid));
        $this->display();
    }

    /**
     * 获取我的红包的外框
     */
    public function getJavaMyBonusList()
    {
        try {
            $uid = $this->uid;
            $data = $this->_service->getMyBonus($uid);
            $this->assign("receive_bonus", moneyView($data['amounts'], 2));
            $this->assign("use_bonus", moneyView($data['usedAmounts'], 2));
            $this->assign("expire_bonus", moneyView($data['expiredAmounts'], 2));//已过期
            $this->assign("money", moneyView($data['restAmounts'], 2));
            $this->assign("useEndAmounts", moneyView($data['useEndAmounts'], 2));
            $this->assign("bonus_type",1);
            $this->assign("has_history_list",$this->_service->getUserHasHistoryRecord($uid));
            $this->assign("is_tips",$this->_service->getUserBonusTips($uid));
            $this->display('getMyBonusList');
        } catch (Exception $e) {
            $this->assign("msg",JavaService::ERROR_MSG);
            $this->display('getMyBonusList');
        }
    }


    /**
     * java对接的我的红包列表(new)
     */
    public function ajaxJavaGetMyBonusList()
    {
        try {
            $usedStatus = I("request.usedStatus", self::BONUS_STATUS_ALL, "intval");
            $uid = $this->uid;
            $pageNo = I("p", 1, "intval");
            $pageSize = I("post.pageSize", self::PAGESIZE, "intval");
            $data = $this->_service->getMyBonusList($uid, $pageNo, $pageSize, $usedStatus, JavaService::PLATFORM_PC);
            //分页
            $page = $data['page'];
            $parameter = array('usedStatus'=>$usedStatus);
            $paging = W(
                "Paging",
                array(
                    "totalRows" => $page['totalCount'],
                    "pageSize" => $page['pageSize'],
                    "parameter" => $parameter
                ),
                true);
            $this->assign("paging", $paging);
            $this->assign("data", $data['list']);
            $this->assign("usedStatus", $usedStatus);
            if($usedStatus == 7)
                $this->display('ajaxJavaGetMyBonusUsedList');
            else
                $this->display();
        } catch (Exception $e) {
            $this->assign("msg",JavaService::ERROR_MSG);
            $this->display();
        }
    }

    /**
     * 我的红包的明细
     */
    public function ajaxGetMyBonusList() {
        $page = I('request.p', 1, "intval");
        $pagesize = I('request.pagesize', self::PAGESIZE, "intval");
        $offset = ($page-1)*$pagesize;
        $where = array(
            'uid' => $this->uid,
        );
        $orderby = 'ctime DESC,status ASC';
        $list = $this->serUserBonusLog->getMyBonusList($where, $field = "*", $orderby,$offset, $pagesize);
        $total = $this->modUserBonusLog->getCount($where);
        $total_page = ceil($total / $pagesize);

        $this->list = $list;
        $this->current_page = $page;
        $this->total = $total;
        $this->total_page = $total_page;

        //分页
        $parameter = array();
        $paging  = W("Paging",array("totalRows"=>$total,"pageSize"=>$pagesize,"parameter"=>$parameter),true);
        $this->assign("paging",$paging);
        $this->assign("history_bonus_day",C("HISTORY_BONUS_DAY"));

        $this->display();
         // ajaxReturn(array(
         //     'list' => $list,
         //     'current_page' => $page,
         //     'total' => $total,
         //     'total_page' => $total_page,
         //     )
         // );
    }

    /**
     * 获取红包使用记录
     */
    public function ajaxGetBonusUsedRecord()
    {
        try {
            $uid = $this->uid;
            $userBonusId = I("request.userBonusId", 0, "intval");
            if (!$userBonusId) {
                throw_exception("userBonusId有误,userBonusId->" . $userBonusId);
            }
            $data = $this->_service->getMyBonusUsedRecord($uid,$userBonusId);
            if($data){
                foreach($data as $k=>&$v){
                    $v['usedAmount'] = humanMoney($v['usedAmount'],2,false);
                }
            }

            $this->assign("data",$data);

            $this->display();
        } catch (Exception $e) {
            $this->assign("msg",JavaService::ERROR_MSG);
            $this->display();
        }
    }

    /**
     * 用户的tips
     */
    public function ajaxBonusTips()
    {
        $this->_service->setUserBonusTips($this->uid);
        ajaxReturn(array(), 'ok', 1);

    }

    /**
     * 获取红包列表中的规则
     * @access public
     * @memo
     * 参数说明:
     * rule_id: 规则ID,在列表中返回的数据
     * obj_type :obj_type
     * obj_id :obj_id
     * 返回数据:
     *
     * data: "规则的内容",
     */
    public function getBonusRule()
    {
        $rule = $this->serUserBonusLog->getPcBonusRule();
        $this->assign("rule",$rule);
        $this->display();
    }

    //满减券 原
    public function getMyCouponsList_old(){
        $uid=$this->uid;
        $money = $this->serUserCoupons->getMyCoupons($uid);//我的满减券的金额
        $result  = $this->serUserCoupons->getMyCouponsAndOther($uid);
        $receive_coupons = array_sum($result) ? moneyView(array_sum($result)) : moneyView(0);
        $unuse_coupons = array_key_exists(UserMarketTicketService::COUPONS_VIEW_TYPE_UNUSED,$result) ? moneyView($result[UserMarketTicketService::COUPONS_VIEW_TYPE_UNUSED]) : moneyView(0);
        $use_coupons = array_key_exists(UserMarketTicketService::COUPONS_VIEW_TYPE_USE,$result) ? moneyView($result[UserMarketTicketService::COUPONS_VIEW_TYPE_USE]) : moneyView(0);
        $expire_coupons = array_key_exists(UserMarketTicketService::COUPONS_VIEW_TYPE_EXPIRE,$result) ? moneyView($result[UserMarketTicketService::COUPONS_VIEW_TYPE_EXPIRE]) : moneyView(0);
        $this->assign("receive_coupons",  $receive_coupons);
        $this->assign("unuse_coupons",  $unuse_coupons);//未使用
        $this->assign("use_bonus",  $use_coupons);//已使用
        $this->assign("expire_bonus",  $expire_coupons);//已过期
        $this->assign("money",  moneyView($money,2));
        $this->assign("bonus_type",2);
        $this->display('getMyBonusList');
    }

    //满减券的外框 新 2016322
    public function getMyCouponsList()
    {
            try {
                $uid = $this->uid;
                $data = $this->_service->getMyCouponBrief($uid);
                $this->assign("unuse_coupons", moneyView($data['amounts'], 2));
                $this->assign("use_bonus", moneyView($data['usedAmounts'], 2));//使用满减券金额
                $this->assign("expire_bonus", moneyView($data['expiredAmounts'], 2));//已过期
                $this->assign("money", moneyView($data['restAmounts'], 2));
                $this->assign("bonus_type",2);
                $this->display('getMyBonusList');
            } catch (Exception $e) {
                $this->assign("msg","满减券数据获取失败,请重试");
                $this->display('getMyBonusList');
            }

    }

    //满减券列表 old
    public function ajaxGetMyCouponsList_old() {
        $page = I('request.p', 1, "intval");
        $pagesize = I('request.pagesize', self::CONPUS_PAGESIZE, "intval");
        $status = I("request.status", 1, "intval");//默认为1是未使用,2,4是已使用,3是已过期
        $offset = ($page-1)*$pagesize;
        $where = array(
            'a.type' => 1,
            'a.uid' => $this->uid,
        );
        if($status == 2){
            $where['a.user_bonus_status'] = array('in',array('2','4'));
        }else{
            $where['a.user_bonus_status'] = $status;
        }

        $orderby = 'a.ctime DESC,a.user_bonus_status ASC';
        $list = $this->serUserCoupons->getMyCouponsList($where, $field = "*", $orderby,$offset, $pagesize);
        $total = $this->serUserCoupons->getCountPc($where);
        $total_page = ceil($total / $pagesize);
        $this->list = $list;
        $this->current_page = $page;
        $this->total = $total;
        $this->total_page = $total_page;

        //分页
        $parameter = array('status'=>$status);
        $paging  = W("Paging",array("totalRows"=>$total,"pageSize"=>$pagesize,"parameter"=>$parameter),true);
        $this->assign("paging",$paging);
        $this->assign("data",$list);
        $this->assign("status",$status);
        $this->display();

    }

    //2016322 调用java数据 满减券列表
    public function ajaxGetMyCouponsList() {
        try {
            $usedStatus = I("request.usedStatus", self::COUPONS_STATUS_ALL, "intval");
            $uid = $this->uid;
            $pageNo = I("p", 1, "intval");
            $pageSize = I("post.pageSize", self::PAGESIZE, "intval");
            $data = $this->_service->getMyCouponList($uid, $pageNo, $pageSize, $usedStatus, JavaService::PLATFORM_PC);
            //分页
            $page = $data['page'];
            $parameter = array('usedStatus'=>$usedStatus);
            $paging = W(
                "Paging",
                array(
                    "totalRows" => $page['totalCount'],
                    "pageSize" => $page['pageSize'],
                    "parameter" => $parameter
                ),
                true);
            $this->assign("paging", $paging);
            $this->assign("data", $data['list']);
            $this->assign("status", $usedStatus);
            $this->display();
        } catch (Exception $e) {
            $this->assign("msg","满减券数据获取失败,请重试");
            $this->display();
        }

    }


    //加息券 老
    public function getRateCouponsList_old(){
        $uid=$this->uid;
        $unuse_rate = $this->serUserCoupons->getInterestNumPc($uid,UserMarketTicketService::COUPONS_VIEW_TYPE_UNUSED);
        $use_rate = $this->serUserCoupons->getInterestNumPc($uid,UserMarketTicketService::COUPONS_VIEW_TYPE_USE);
        $expire_rate = $this->serUserCoupons->getInterestNumPc($uid,UserMarketTicketService::COUPONS_VIEW_TYPE_EXPIRE);
        $this->assign("unuse_rate",  $unuse_rate);//未使用
        $this->assign("use_rate",  $use_rate);//已使用
        $this->assign("expire_rate",  $expire_rate);//已过期
        $this->assign("bonus_type",3);
        $this->display('getMyBonusList');
    }

    //加息券框 2016322新(调用java接口)
    public function getRateCouponsList(){
        try {
            $uid = $this->uid;
            $data = $this->_service->getMyVoucherBrief($uid);
            $this->assign("unuse_rate",  $data['restNum']);//未使用
            $this->assign("use_rate",  $data['usedNum']);//已使用
            $this->assign("expire_rate", $data['expiredNum']);//已过期
            $this->assign("bonus_type",3);
            $this->display('getMyBonusList');
        } catch (Exception $e) {
            $this->assign("msg","加息券数据获取失败,请重试");
            $this->display('getMyBonusList');
        }

    }

    //加息券列表 老
    public function ajaxGetRateCouponsList_old() {
        $page = I('request.p', 1, "intval");
        $pagesize = I('request.pagesize', self::CONPUS_PAGESIZE, "intval");
        $status = I("request.status", 1, "intval");//默认为1是未使用,2,4是已使用,3是已过期
        $offset = ($page-1)*$pagesize;
        $where = array(
            'a.type' => 2,
            'a.uid' => $this->uid,
        );
        if($status == 2){
            $where['a.user_bonus_status'] = array('in',array('2','4'));
        }else{
            $where['a.user_bonus_status'] = $status;
        }
        $orderby = 'a.ctime DESC,a.user_bonus_status ASC';
        $list = $this->serUserCoupons->getMyCouponsList($where, $field = "*", $orderby,$offset, $pagesize);
        $total = $this->serUserCoupons->getCountPc($where);
        $total_page = ceil($total / $pagesize);
        $this->list = $list;
        $this->current_page = $page;
        $this->total = $total;
        $this->total_page = $total_page;

        //分页
        $parameter = array('status'=>$status);
        $paging  = W("Paging",array("totalRows"=>$total,"pageSize"=>$pagesize,"parameter"=>$parameter),true);
        $this->assign("paging",$paging);
        $this->assign("data",$list);
        $this->assign("status",$status);

        $this->display();

    }

    //加息券list 2016322新(调用java接口)
    public function ajaxGetRateCouponsList() {
        try {
            $usedStatus = I("request.usedStatus", self::ADDRATE_STATUS_ALL, "intval");
            $uid = $this->uid;
            $pageNo = I("p", 1, "intval");
            $pageSize = I("post.pageSize", self::PAGESIZE, "intval");
            $data = $this->_service->getMyVoucherList($uid, $pageNo, $pageSize, $usedStatus, JavaService::PLATFORM_PC);
            //分页
            $page = $data['page'];
            $parameter = array('usedStatus'=>$usedStatus);
            $paging = W(
                "Paging",
                array(
                    "totalRows" => $page['totalCount'],
                    "pageSize" => $page['pageSize'],
                    "parameter" => $parameter
                ),
                true);
            $this->assign("paging", $paging);
            $this->assign("data", $data['list']);
            $this->assign("status", $usedStatus);
            $this->display();
        } catch (Exception $e) {
            $this->assign("msg","加息券数据获取失败,请重试");
            $this->display();
        }

    }

    //券的规则
    public function getCouponsRule(){
        $rule = '1.投资项目时，须满足指定的项目期限及投资金额，才可使用满减券。<br>2.使用时直接充当投资金额（比如投资金额为2000元时，可用“满2000元减10元”的满减券，只使用账户余额的1990元）。<br>3.一次投资只可使用一张满减券。<br>4.满减券不可与红包、加息券等其它奖励共同使用。</br>5.最终解释权归鑫合汇所有。';
        $this->assign("rule",$rule);
        $this->display();
    }

    /**
     *2016323 調取java获取红包,满减券，加息券记录
     * 20161123 增加理财金的已使用记录 type=4
     */
    public function getMyRewardUsedRecord()
    {
        try {
            $uid = $this->uid;
            $userRewardId = I("request.userRewardId", 0, "intval");
            $type = I("request.type", 2, "intval");//1:红包,2:满减券,3:加息券 4.理财金
            if (!$userRewardId) {
                throw_exception("userRewardId有误,userRewardId->" . $userRewardId);
            }
            if (!$type) {
                throw_exception("type有误,type->" . $type);
            }
            $data =$this->_service->getMyRewardUsedRecord($uid,$type,$userRewardId);
            $dataLcj=$data;
            if($data){
                foreach($data as $k=>&$v){
                    $v['usedAmount'] = humanMoney($v['usedAmount'],2,false);
                }
            }
            $dataLcj['usedTime']=date('Y-m-d',strtotime($dataLcj['usedTime']));
            $this->assign("data",$data);
            $this->assign("result",$dataLcj);
            $this->assign("type",$type);

            if($type==4){
                $this->display('ajaxGetBonusUsedRecordlcj');
            }else{
                $this->display('ajaxGetBonusUsedRecord');
            }
        } catch (Exception $e) {
            $this->assign("msg","数据获取失败,请重试");
            if($type==4){
                $this->display('ajaxGetBonusUsedRecordlcj');
            }else{
                $this->display('ajaxGetBonusUsedRecord');
            }
        }
    }

    /**
     * 20161118
     * 获取我的理财金外框
     */
    public function getJavaMyLcj()
    {
        try {

            $uid = $this->uid;
            $data = $this->_service->getMyXpfundBrief($uid);
            $this->assign("unuse_lcj", $data['restAmounts']);//未使用的理财金
            $this->assign("use_lcj", $data['usedAmounts']);//使用理财金金额
            $this->assign("expire_lcj", $data['expiredAmounts']);//已过期理财金金额
            $this->assign("profits", $data['profits']);//待提取收益

            $this->assign("bonus_type",4);
            $is_first_order=service("Account/TyjBonus")->isFirstOrder($uid);//是否是首投
            $this->assign("is_first_order",$is_first_order);
            if($is_first_order || $data['profits']=='0.00'){
                $this->assign("status",0);
            }else{
                $this->assign("status",1);
            }
            $this->display('getMyBonusList');
        } catch (Exception $e) {
            $this->assign("msg","理财金数据获取失败,请重试");
            $this->display('getMyBonusList');
        }

    }

    //20161118 调用java数据 理财金列表
    public function ajaxGetMyLcjList() {
        try {
            $usedStatus=$_REQUEST['usedStatus']?$_REQUEST['usedStatus']:self::LCJ_STATUS_ALL;
            $uid = $this->uid;
            $pageNo = I("request.p", 1,"intval");
            $pageSize = I("request.pageSize", self::PAGESIZE, "intval");
            $data = $this->_service->getMyXpfundList($uid, $pageNo, $pageSize, $usedStatus, JavaService::PLATFORM_PC);
            //分页
            $page = $data['page'];
            $parameter = array('usedStatus'=>$usedStatus);
            $paging = W(
                "Paging",
                array(
                    "totalRows" => $page['totalCount'],
                    "pageSize" => $page['pageSize'],
                    "parameter" => $parameter
                ),
                true);
            $this->assign("paging", $paging);
            $this->assign("data", $data['list']);
            $this->assign("status", $usedStatus);
            $this->display();
        } catch (Exception $e) {
            $this->assign("msg","理财金数据获取失败,请重试");
            $this->display();
        }
    }

    //20161118理财金投资成功
    function buySuccessLcj()
    {
        try{
            $uid = $this->uid;
            $xpfundid = I("request.xpfundid");
            if (!$xpfundid) {
                throw_exception("xpfundid有误,xpfundid->" . $xpfundid);
            }
            $data = $this->_service->dealUserXpfund($uid,$xpfundid);
            $result['usedTime'] = date("Y",strtotime($data['xpfundInvestSucInfo']['usedTime'])).年.date("m",strtotime($data['xpfundInvestSucInfo']['usedTime'])).月.date("d",strtotime($data['xpfundInvestSucInfo']['usedTime'])).日;
            $result['repaymentDate'] = date("Y",strtotime($data['xpfundInvestSucInfo']['repaymentDate'])).年.date("m",strtotime($data['xpfundInvestSucInfo']['repaymentDate'])).月.date("d",strtotime($data['xpfundInvestSucInfo']['repaymentDate'])).日;
            $result['prjName']=$data['xpfundInvestSucInfo']['prjName'];
            $this->assign('data', $result);
            $this->display();
        }catch (Exception $e) {
            $this->assign("msg","理财金投资失败,请重试");
            $this->display();
        }
    }

    //20161118 理财金投资记录
    function getJavaMyLcjList()
    {
        try {
            $uid = $this->uid;
            $usedStatus = self::LCJ_STATUS_USED . ',' . self::LCJ_STATUS_UNDRAW . ',' . self::LCJ_STATUS_DRAWED . ',' . self::LCJ_STATUS_PROFITEXPIRED;
            $pageNo = I("request.p", 1);
            $pageSize = I("request.pageSize", self::PAGESIZE, "intval");
            $data = $this->_service->getJavaMyTcjList($uid, $pageNo, $pageSize, $usedStatus, JavaService::PLATFORM_PC);
            //分页
            $page = $data['page'];
            $parameter = array('usedStatus' => $usedStatus);
            $paging = W(
                "Paging",
                array(
                    "totalRows" => $page['totalCount'],
                    "pageSize" => $page['pageSize'],
                    "parameter" => $parameter
                ),
                true);
            $this->assign("paging", $paging);
            $this->assign("data", $data['list']);
            $this->display('TyjBonus/getMyTyjBonus');
        }catch (Exception $e) {
                $this->assign("msg","理财金投资记录获取失败,请重试");
                $this->display('TyjBonus/getMyTyjBonus');
            }
    }

    //20161123提取理财金收益
    public function ReceiveLcj(){
        $uid =$this->uid;
        $yield =  service("Account/TyjBonus")->ReceiveLcj($uid);   //返回领取的收益
        if($yield){
            $this->assign('yield',$yield);
            $this->assign('gotStatus',1);//领取成功
            $this->display();
        }else{
            $this->assign('gotStatus',0);      //领取体验金失败
            $this->display();
        }
    }





}

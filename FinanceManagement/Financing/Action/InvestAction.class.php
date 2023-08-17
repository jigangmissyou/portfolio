<?php
/**
 * // +----------------------------------------------------------------------
// | UPG
// +----------------------------------------------------------------------
// | Copyright (c) 2012 http://upg.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: kaihui.wang <wangkaihui@upg.cn>
// +----------------------------------------------------------------------
// 投资理财
 */
import_app("Financing.Model.PrjModel");
import_app("Financing.Model.PrjFilterModel");
class InvestAction extends BaseAction{

    //鑫利宝
    const RISK_MSG_ONE = "对资金流向及状态全场景全流程监控；";
    const RISK_MSG_TWO = "资金按约定用途使用，还款来源可控。";
    const DIYA_TITLE = "等额的应收账款抵押";

    function index(){
//         $banners = service("Index/Banner")->getBanners(2);
//         $this->assign('banners',$banners);
//         $this->display();
        redirect(U("Financing/Invest/plist"));
    }

    /**
     * 政府项目
     */
    function zfProject(){
        //政府项目的相关信息
        if($recommend = I('get.recommend')){
            setMyCookie('recommend', $recommend, 86400);
        }
        $guarantorId = C('GUARANTOR_ID_CX');
        $prjCnt = D('Financing/Prj')->getPrjCountByGuarantorId($guarantorId);
        $this->assign('haveBid', $prjCnt);
        $this->display();
    }

    /**
     * 统计各标的类型统计当前开标中的标的个数
     * 导航使用
     */
    public function getBidCountByPrjType()
    {
        $is_newbie = (int) $this->loginedUserInfo['is_newbie'] || !$this->loginedUserInfo['uid'];

        $prj_type = I('get.prj_type', '');
        $real_time = (int)I('get.real_time');
        $result = service('Financing/Prj')->getBidCountByPrjType($prj_type, $is_newbie, $real_time);
        ajaxReturn($result, '请求成功', 1);
    }

    //列表
    function plist(){
        openMasterSlave();
        $c = (int) $this->_request("c");

        if(!$c){
            $this->assign("has_transfer",0);
            $asset_transfer = M('asset_transfer')->where(array('status'=>1))->find();
            if($asset_transfer) $this->assign("has_transfer",1);
        }
        $this->assign("uid", $this->loginedUserInfo['uid']);

        $projectService = service("Financing/Project");

        $c = $c ? $c : 1;

        $this->assign("c",$c);


        //开标时间 <em>23</em>时<em>24</em>分<em>23</em>秒
        $bidTimeDiff = $projectService->getNewBidOpenTime($c);
        $this->assign("bidTimeDiff",$bidTimeDiff[0]);
        $this->assign("prj_type_name",$bidTimeDiff[1]);

//        $statInfo = service("Index/Common")->getStat('plist_stats');
//        $this->statInfo = $statInfo;

        //担保公司
//        $this->guarantor = service("Financing/Project")->getGuarantor();

        //担保公司
        $this->assign('guarantor', D("Zhr/Guarantee")->getGuaranteeList(0));
        $this->assign('guarantor_id', intval(I('request.guarantor_id')));
        //如果是速兑通，下拉显示速兑通的的排序信息
        if($c==6){
            $fastService = service("Financing/FastCash");
            $sort = $fastService->getSdtSortList();
            //统计速兑通数据 
            $statistics = service("Financing/FastCash")->getStatistics();
            $this->assign("statistics",$statistics);
            $this->assign("sort",$sort);
        }
        $this->assign("show_new",I("request.show_new",0,"intval"));
        //0元购开关
        $zeroOf = M('system_config')->where(array('status'=>0,'config_key'=>'freePay'))->find();
        $this->assign("zero",$zeroOf);
        $this->assign("zs_status",$this->loginedUserInfo['zs_status']);
        $this->display();
    }


    /**
     * 获取开标时间
     */
    function getNewBidOpenTime(){
        $c = (int) $this->_request("c");
        $projectService = service("Financing/Project");
        $c = $c ? $c : 1;
        service("Financing/Project")->doBidOpen();
        $bidTimeDiff = $projectService->getNewBidOpenTime($c);

        ajaxReturn($bidTimeDiff);
    }

    function checkBidOpen(){
        $id = (int)$this->_request("id");
        service("Financing/Project")->doBidOpen();
        $info = M("prj")->find($id);
        import_app("Financing.Model.PrjModel");
        if($info['bid_status'] >= PrjModel::BSTATUS_BIDING){
            ajaxReturn();
        }else{
            ajaxReturn(0,0,0);
        }
    }

    /**
     * PC端项目列表
     */
    public function ajaxplist()
    {
        openMasterSlave();
        $user = M('user')->find($this->loginedUserInfo['uid']);
        $extraParams = array();
        /* @var $prj_service PrjService */
        $prj_service = service('Financing/Prj');
        /* @var $project_service ProjectService */
        $project_service = service('Financing/Project');

        //标的产品类型 仅留日、月、年益升和速兑通
        $c = (int) I('request.c');
        $c = $parameter['c'] = $c ? $c : 1;

        $order_by = $parameter['sort_id'] = I('request.sort_id');
        $parameter['order'] = $parameter['sort'] = $sort = I('request.sort');
        //添加新客专区参数
        //$show_newbie = (int) I('request.show_new');//值为1表示访问新客专区列表
        $is_newbie = 0;
        //$this->assign('show_new', $show_newbie);
        if ($c == 7) {
            //企福鑫
            $companyService = service("Financing/CompanySalary")->init($user['uid']);
            $this->assign('user_company_id', $companyService->user_company_id);

            $where= $companyService->getQfxFilter();
            $extraParams['is_qfx'] = 1;
        } else {
            //标的状态
            $bid_status = $parameter['bid_st'] = (int) I('request.bid_st');

            //是否新手
            $parameter['show_new'] = (int) I('request.show_new');

            //标的期限
            $time_limit = $parameter['time_limit'] = (int) I('request.time_limit');

            //标的业务类型
            $prj_business_type = $parameter['b_type'] = I('request.b_type');
            if ($c == 1) {
                if (!($order_by || $prj_business_type || $time_limit || $bid_status)) {
                    //所有投资项目 默认情况下展示
                    $this->assign('c', $c);
                    $this->assign('bstatus', $bid_status);
                    $this->prjDefaultRecommend();
                    exit;
                } else {
                    //所有投资项目 选了更多筛选中的任一项
                    $this->assign('show_page', 1);
                }
            }
            // $is_newbie = $user['uid'] && !$user['is_newbie'] ? 0 : 1;//20160301投资项目下不显示新手标，屏蔽这句代码
            //为登录也是新客
//            if(!is_array($user)){
//                $is_newbie = 1;
//            }

            $where = $prj_service->createPlistSearchCondition(
                $bid_status,
                $time_limit,
                $prj_business_type,
                $c,
                $is_newbie
            );

            $this->assign('bstatus', $bid_status);
        }


        $order_sort = $prj_service->createPlistOrderSort($order_by, $sort);
        $page = max(1, (int) I('request.p'));
        if(!($page_size = (int) I('request.page_size'))){
            $page_size = 10;
        }

        $result = $project_service->fetchProjectList($where, $order_sort, $page, $page_size, '', true, $user['uid'], $extraParams);
        $list = $result['data'];
        $total_row = $result['total_row'];

        //分页
        $paging  = W('Paging', array('totalRows' => $total_row, 'pageSize' => $page_size, 'parameter' => $parameter), true);

        $is_login = $user['uid'] ? 1 : 0;
        $this->assign('sort_id', $order_by);
        $this->assign('isLogin', $is_login);
        $this->assign('c', $c);
        $this->assign('list', $list);
        $this->assign('paging', $paging);
        $this->assign('is_newbie', $user['is_newbie']);

        //渠道客户端
        $this->assign('userInfo', $user);
        $this->assign('client_type', self::CLIENT_TYPE);
        $this->assign('client_type_config', D('Financing/PrjFilter')->client_type_config);
        $this->display();
    }

    /*
     * 列表页默认推荐项目
     */
    public function prjDefaultRecommend()
    {
        $uid = $this->loginedUserInfo['uid'];
        $orgPrjService = service('Financing/Project');
        $prj_service = service('Financing/Prj');
        $where['status'] = PrjModel::STATUS_PASS;//审核通过
        $where['bid_status'] = array(
            array("EQ",PrjModel::BSTATUS_BIDING),
            array("EQ",PrjModel::BSTATUS_WATING),
            "or"
        );//投资中和待开标
        //非新客登陆后 此处不能显示新客的标  20160301start原来逻辑关闭
//        if($uid && !$this->loginedUserInfo['is_newbie']){
//            $where['is_new'] = 0;
//        }//end
        $where['is_show'] = 1;

        $orderArr = "combine_order ASC";

        $map = $prj_service->notListQfx();
        $where['_logic'] = 'AND';
        $where['_complex'] = $map;

        $where['prj_type'] = PrjModel::PRJ_TYPE_A;
        $aData = $orgPrjService->fetchProjectList($where,$orderArr,1,2,'',false,$uid);
        $where['prj_type'] = PrjModel::PRJ_TYPE_F;
        $fData = $orgPrjService->fetchProjectList($where,$orderArr,1,2,'',false,$uid);
        $where['prj_type'] = PrjModel::PRJ_TYPE_B;
        $bData = $orgPrjService->fetchProjectList($where,$orderArr,1,2,'',false,$uid);
        $where['prj_type'] = PrjModel::PRJ_TYPE_H;
        $where['order'] = array("combine_order" => 'ASC');
        $hData = $orgPrjService->fetchProjectList($where,$orderArr,1,2,'',false,$uid);

        $result = [
            'r' => $aData['data'],
            'y' => $fData['data'],
            'n' => $bData['data'],
            's' => $hData['data'],
        ];
        unset($aData,$fData,$bData,$hData);

        $this->assign("list",$result);
        $isLogin = $uid?1:0;
        $this->assign("isLogin",$isLogin);

        //渠道客户端
        $user = M('user')->find($this->loginedUserInfo['uid']);
        $this->assign("userInfo",$user);
        $this->assign("client_type",self::CLIENT_TYPE);
        $client_type_config  = D("Financing/PrjFilter")->client_type_config;
        $this->assign("client_type_config",$client_type_config);
        $this->display();
    }

    //担保公司项目列表
    function ajaxglist(){
        openMasterSlave();
        $parameter = $where = $_string = array();
        //担保公司
        $guarantor_id = (int)I('request.guarantor_id','1');
        $parameter['guarantor_id'] = $guarantor_id;
        $where['guarantor_id'] = $guarantor_id;

//        if(!in_array($guarantor_id, array('4','6','7'))) {
//            $where['guarantor_id'] = array('in',array('1','5'));
//        }else{
//            $where['guarantor_id'] = $guarantor_id;
//        }
        $status = (int)I('request.dbstatus');
        $parameter['dbstatus'] =  $status;
        if($status == 1){
            $where['bid_status'] = array("NEQ",PrjModel::BSTATUS_REPAID);
        }elseif($status == 2){
            $where['bid_status'] = PrjModel::BSTATUS_REPAID;
        }

//        $orderArr = " bid_status_order ASC ,
//                      CASE  WHEN bid_status_order=1 THEN prj_sort END ASC,
//                      CASE  WHEN bid_status_order=2 THEN start_bid_time END ASC,
//                      CASE  WHEN bid_status_order=3 THEN start_bid_time END ASC,
//                      CASE  WHEN bid_status_order=4 THEN fi_prj.ctime END ASC,
//                      CASE  WHEN bid_status_order=5 THEN start_bid_time  END ASC,
//                      CASE  WHEN bid_status_order=6 THEN start_bid_time  END DESC,
//                      CASE  WHEN bid_status_order=7 THEN transfer_time  END DESC,
//                      CASE  WHEN bid_status_order=8 THEN start_bid_time  END ASC,
//                      CASE  WHEN bid_status_order=9 THEN actual_repay_time END DESC
//                      ";
        $orderArr = "combine_order ASC";

        //审核通过
        $where['status'] = PrjModel::STATUS_PASS;
        $where['is_show'] = 1;

        //判断会员
        $isVip = $this->loginedUserInfo && (strstr($this->loginedUserInfo['vip_group_id'],','.C('BFM_VIP_GROUP_ID').',')) ? 1:0;
        $uid = $this->loginedUserInfo['uid'];
        $is_newbie = 0;
        //是否新客
        if($uid){
            $userInfo = M("user")->find($uid);
            if(!$userInfo['is_newbie']){
                $_string[] = "(is_new=0) OR (is_new=1 AND  bid_status > ".PrjModel::BSTATUS_BIDING.")";
            }else{
                $is_newbie = 1;
            }
            $user_college_ext = M("user_college_ext")->find($uid);
            $this->user_college_ext = $user_college_ext;
        }
        $this->is_newbie =$is_newbie;

        //投放站点
        $_string[] = sprintf(" CASE WHEN bid_status>%d THEN ( mi_no!='1234567889' OR mi_no!='1234567890' ) ELSE ( mi_no='1234567889' OR mi_no='1234567890' ) END", PrjModel::BSTATUS_BIDING);
        if(!empty($_string)) {
            foreach($_string as $k => $v) {
                $_string[$k] = '(' . $v . ')';
            }
            $where['_string'] = implode(' AND ', $_string);
        }

        $page = (int) $this->_request("p");
        $page = $page ? $page :1;
        $pageSize = 4;

        $serviceObj = service("Financing/Project");
        $result = $serviceObj->fetchProjectList($where,$orderArr,$page,$pageSize,'',true,$uid);

        $list = $result['data'];
        $totalRow = $result['total_row'];
        $this->assign("list",$list);
        //分页
        $paging  = W("Paging",array("totalRows"=>$totalRow,"pageSize"=>$pageSize,"parameter"=>$parameter),true);
        $this->assign("paging",$paging);
        //登录判断
        $isLogin = $this->loginedUserInfo?1:0;
        $this->assign("isVip",$isVip);
        $noname = (!$isVip) || (!$isLogin);
        $this->assign("noname",$noname);
        $this->assign("isLogin",$isLogin);
        //渠道客户端
        $this->assign("client_type",self::CLIENT_TYPE);
        $client_type_config  = D("Financing/PrjFilter")->client_type_config;
        $this->assign("client_type_config",$client_type_config);
        $this->display();
    }


    // 担保函
    private function danbaohan($prj_id) {
        $cache_danbaohan = "prj_danbaohan_".$prj_id;
        $danbaohan = cache($cache_danbaohan);
        if(!$danbaohan || is_array($danbaohan)) {
            $audits = service("Application/ProjectAudit")->getAuditImgData($prj_id);
            $danbaohan = array();
            foreach ($audits as $audit) {
                if($audit['name'] == '担保函' || $audit['name'] == '担保承诺函') {
//                  $danbaohan = $audit;
//                  break;
                    $danbaohan[] = $audit['big'];
                }
            }
            $danbaohan = implode(',', $danbaohan);
            cache($cache_danbaohan, $danbaohan, array('expire'=>600));
        }
        $this->assign('danbaohan', $danbaohan);
    }



    /**
     * 项目详情
     */
    function view()
    {
        openMasterSlave();
        $id = I('request.id',0,'intval');
        if(!$id) redirect(U('Financing/Invest/plist'));

        $this->hashId = $id;
        $id = $prj_id = service("Financing/Project")->parseIdView($id);
        $this->id = $this->prj_id = $id;

        $user = $this->loginedUserInfo;
        $salaryBtn = 1;
        try{
            if($id <= 0){
                throw_exception('项目ID错误，请检查后重试');
            }
            $info = service("Financing/Invest")->getPrjView($id);

            //债权生产环境不让看
            if ($info['prj_type'] == PrjModel::PRJ_TYPE_J && strtolower(C('APP_STATUS')) == 'product') {
                throw_exception('您访问的项目不存在');
            }

            //0元购 特别逻辑  begin
            $activity11_id = C('APP_STATUS') != 'product' ? C('ACTIVITY_ID') : C('ACTIVITY_ID');
            if ($info['activity_id'] == $activity11_id) {
                redirect(U('Financing/Invest/view11?id=' . $id));
            }
            //0元购 特别逻辑 end

            if(!$this->loginedUserInfo['identity_no']) {
                if(/*!$info['is_show'] || */$info['status'] != 2) throw_exception('您访问的项目不存在');
            }

            //预售的项目第一次要设置REDIS里对应的余额
            if((int)$info['act_prj_ext']['is_pre_sale']>0){
                service('Financing/PreSale')->setCacheDemandAmount($prj_id);
            }

            $info['prj_type'] == PrjModel::PRJ_TYPE_G && redirect(U('Financing/InvestFund/view', array('id' => $id)));
            //获取当前用户的user_company_id
            $is_qfx = $info['act_prj_ext']['is_qfx'];
            if ($is_qfx) {
                $user_company_id = D("Account/UserExt")->getUserCompanyId($user['uid']);
                $service = service("Financing/CompanySalary")->init($user['uid']);
                $res = $service->getCompanysByPrjId($id);
                if (!$res) {
                    $res = array();
                }
                $info['companylist'] = $res;
                $this->assign("user_company_id",$user_company_id);
                $salary_info = array(
                    'is_qfx'=>$is_qfx,
                    'company_id'=>$info['act_prj_ext']['company_id'],
                );
                $salary = $service->buyBeforeCheckPermission($salary_info);
                $salaryBtn =$salary?1:0;

            }
            $this->assign("salary_permission",$salaryBtn);//按钮相关
            //
        }catch (Exception $e){
            $this->error($e->getMessage());
        }
        $this->assign('corp_id', $info['corp_id']);
        $corpScore = service("Financing/YrzdbApi")->getScore($info['corp_id']);
        $this->assign("corpScore", $corpScore);
        //add by 001181 如果是速兑通 直接使用新的速兑通的方法的链接
        if ($info['prj_type'] == PrjModel::PRJ_TYPE_H) {
            $this->redirect(U("Financing/Invest/fastCash", array('id' => $prj_id)));

            return;
        }
        // 判断会员剩余金额是否足够
        $srvFinancing = service('Financing/Financing');

        //默认最优化的奖励金额
        $defaultReward = service("Financing/BaseInvestRule")->getAllBonusAmount($user['uid'], $prj_id,0,1,0);
        //最大投資金額
        $max_invest_money = $srvFinancing->getMaxInvestMoney($user['uid'], $id, $info,0,-1,$defaultReward);
        //选中最大投资金额后获取对应的奖励
        $defaultReward = service('Financing/ReduceTicket')->getDefaultReward($user['uid'], $this->id,
            $info['time_limit_day'], $max_invest_money, 0);


        $this->assign('reward_info',$defaultReward);
        $max_invest_money_view =$info['min_bid_amount']>$max_invest_money ? number_format ($max_invest_money/100 ,2 , '.' , '' ) : floor($max_invest_money/100);
        $this->assign("max_invest_money_view", $max_invest_money_view);
        if($max_invest_money) $max_invest_money = humanMoney($max_invest_money, 2, FALSE);
        $this->assign("max_invest_money", $max_invest_money);
        $this->assign("money_notEnough", $srvFinancing->isBlanceLess($user['uid'], $id, FALSE, $info,-1,-1,$defaultReward['BestAmount']*100));

        service("Account/Active")->clickBidLog("prjid_".$id,0);
        $c = service("Financing/Project")->getPrjTypeInt($info['prj_type']);
        $this->assign("c",$c);

        //判断是否是新客项目
        //是否是投标中状态
        $mustDisable=0;
        $zs_close_invest_code = 0;
        if($info['bid_status'] == PrjModel::BSTATUS_BIDING){
            $uid = $user['uid'];
            $userInfo = M("user")->find($uid);
            if($info['is_new']){
                if(!$userInfo['is_newbie']){
                    $mustDisable =1;
                }
            }
            $zs_close_invest_code = $userInfo['zs_close_invest_code'];
        }

        //201547增加判断是否是融资者
        if($user['is_invest_finance']==2) $mustDisable = 1;

        //判断是否是车贷
        if($info['business_type'] == 1){
            $this->assign("car_loan",true);
            //线上的时候改为正式环境
            $bzj_tips=file_get_contents("http://10.50.28.91:8080/prj/httpService_depositRes.jhtml");
            $this->assign("bzj_tips",$bzj_tips);
        }
        //判断是否是银票
        if($info['business_type'] == 2){
            $this->assign("bill",true);
            $bill_fields = array(
                'bank_name',
                'end_time',
                'dead_time'

            );
            $bill_info = M('bill_info')->field($bill_fields)->where(array('prj_id'=>$id))->find();
            $this->assign("bank_name",$bill_info['bank_name']);
            $this->assign("end_time",date('Y-m-d',$bill_info['end_time']));
            $this->assign("dead_time",date('Y-m-d',$bill_info['dead_time']));

        }
        $this->mustDisable =$mustDisable;

        $user_college_ext = M('user_college_ext')->find($user['uid']);
        $this->assign("user_college_ext",$user_college_ext);

        $userBase = service("Payment/PayAccount")->getBaseInfo($user['uid']);
        $totalAward = service("Financing/Project")->getTotalAward($userBase['uid'], $userBase, $id);
        $user['amount_jd'] = humanMoney($userBase['amount']+$totalAward+$userBase['coupon_money'],2,false)."元";
        $user['amount_jj'] = $user['amount_jd'];

        $awardName = ($totalAward+$userBase['coupon_money'])/100;
        $user['award_name'] = $awardName;
        $user['award_view'] = humanMoney($totalAward+$userBase['coupon_money'],2,false)."元";

//        p($user);
        $islogin = $userBase ? 1:0;
        $this->assign("user",$user);
        $this->assign("islogin",$islogin);

        $url1 = getCurrentUrl();
        $url = urlencode($url1);
        $this->assign("url",$url);
        $url2 = get_url().$url1;
        $this->assign("url2",$url2);

        //渠道客户端
        $db_client_type = D("Financing/PrjFilter")->getClientType($id);
        $client_permission = true;
        if( !in_array($db_client_type, array(0,self::CLIENT_TYPE) ) ){
            $client_permission = false;    //非专享客户端，按钮灰色
        }
        $this->assign("client_permission",$client_permission);

        $client_type_config  = D("Financing/PrjFilter")->client_type_config;
        $this->assign("client_type_config",$client_type_config);
        $this->assign("client_type",$db_client_type);

        $cache_protocol = "prj_view_protocol_" . $id;
        $protocolInfoTmp = cache($cache_protocol);
        if(!$protocolInfoTmp) {
            $protocolInfoTmp = service("Financing/Project")->getProtocol($info['prj_type'],$info['zhr_apply_id'],$info['id']);
            cache($cache_protocol, $protocolInfoTmp, array('expire'=>600));
        }
        $protocolInfo['id'] = $protocolInfoTmp['key'];
        $protocolInfo['name'] = $protocolInfoTmp['name'];
        $this->protocolInfo = $protocolInfo;

        import_app("Financing.Model.PrjModel");
        //融资截至
        $isEnd = 0;
        if($info['bid_status'] > PrjModel::BSTATUS_BIDING){
            $isEnd = 1;
        }
        $this->assign("isEnd", $isEnd);

        $attachDownload = "";
        if($info['ext']['attach_filename'] && ($info['prj_type'] = PrjModel::PRJ_TYPE_C)){
            $attachArr = D("Public/Upload")->parseFileParam($info['ext']['attach']);
            $attachDownload = isset($attachArr['attach']['download']) ? $attachArr['attach']['download']:"";
        }
        $this->assign("attachDownload", $attachDownload);

        //最小投资金额是否更改
        $this->isBidAmountChange = service("Financing/Project")->isMinBidAmountCHange($info['remaining_amount'], $info['step_bid_amount'], $info['min_bid_amount']);

        //是否显示还款计划
        $this->isShowPlan = service("Financing/Invest")->isShowPlan($info['id']);
        //到期还款
        $this->isEndate = service("Financing/Invest")->isEndDate($info['repay_way']);
        if (!$this->isEndate) {
            //1万元乘以100
            $noteMoney = 1000000;
            $this->moneyNote = service("Financing/Invest")->getPrjMoneyNote($id, $noteMoney, $info);
        }
        $info['year_rate_show'] = number_format($info['year_rate'] / 10, 2) . "%";
        $this->assign("info", $info);

        $income_key = "invest_view_i_r_" . $id;
        $aincomer = cache($income_key);
        if ($aincomer) {
            $aincomerP = explode("_", $aincomer);
            $this->income = $aincomerP[0];
            $money = $aincomerP[1];
            $amount = $userBase['amount'] + $totalAward + $userBase['coupon_money'];
            $this->remaind = humanMoney($amount - $money, 2, false) . "元";
        } else {
            $money = $info['min_bid_amount'] / 100;
            $money = str_replace(",", "", $money);
            $money = $money * 100;
            if (!service("Financing/Invest")->isEndDate($info['repay_way'])) {
                $income = service("Financing/Invest")->getSpecialIncome($id, $money, time());
            } else {
                $income = service("Financing/Project")->getIncomeById($id, $money);
            }
            $income = humanMoney($income, 2, false);
            $amount = $userBase['amount'] + $totalAward + $userBase['coupon_money'];
            $remaind = humanMoney($amount - $money, 2, false) . "元";
            $this->income = $income;
            $this->remaind = $remaind;
            cache($income_key, $income . "_" . $money, array('expire' => 600));
        }

        $activity = $srvFinancing->getActivityView($info['activity_id']);

        if($activity && ($activity['reward_money_rate'] > 0)){
            //添加投资金额的
            if($activity['reward_money_action'] == RewardRuleModel::ACTION_PROJECT_INVEST){
                $activity['is_invest_icon'] = 1;//投资有奖励
                $activity['tips_show'] = $activity['name'];
            }else{
                $activity['is_invest_icon'] = 0;
            }
            $this->reward_money_rate = $reward_money_rate = $activity['reward_money_rate'];
            if ($activity['reward_money_rate_type'] == 'month') {
                $this->reward_money_rate = $reward_money_rate * 12;
            } elseif ($activity['reward_money_rate_type'] == 'day') {
                $this->reward_money_rate = $reward_money_rate * 365 / 10;
            }

        }
        $this->activity = $activity;
        $this->assign("prj_type", $info['prj_type']);

        $is_login = $this->loginedUserInfo ? true : false;
        $this->assign('is_login', $is_login);
        $muji_money = $info['demand_amount']-$info['remaining_amount'];
        $qixi_day = service('Financing/Prj')->getPrjQixiShow($prj_id);
        $wanyuan_profit = service('Financing/Invest')->getSpecialIncome($info['id'], 1000000, time(), array('is_have_biding' =>0, 'is_have_reward' => true));
        $wanyuan_profit = humanMoney($wanyuan_profit,2,false);
        $this->assign('muji_money',humanMoney($muji_money,2,false));
        $this->assign('qixi_day',$qixi_day);
        $this->assign('wanyuan_profit',$wanyuan_profit);

        //三农贷、电商贷、创客融、央企融、经营贷前端显示统一为经营贷
        if(in_array($info['prj_business_type'],array('C', 'D', 'E', 'C0V14', 'C00303'))) {
            $info['prj_business_type_name'] = '经营贷';
        }
        $this->assign('prj_business_type_name',$info['prj_business_type_name']);
        $user_account = M('user_account')->find((int)$user['uid']);
        $this->assign("is_paypwd_edit", $user['is_paypwd_edit'] ||$user_account['pay_password']);//20160309 是否设置支付密码


        $this->assign('zs_close_invest_code', $zs_close_invest_code);
        $this->assign("zero_buy_show",C(ZERO_BUY_SHOW));//20160301关闭0元购
        //0元购开关
        $zeroOf = M('system_config')->where(array('status'=>0,'config_key'=>'freePay'))->find();
        $this->assign("zero",$zeroOf);

        $xprjIdAccess = service("Financing/Project")->getAccessToken($prj_id,$user['uid']);
        $this->assign("xprjIdAccess",$xprjIdAccess);
        //是否在白名单
        $inWhiteList = service("Account/Account")->checkNameListUser($user['uid']) ? 1 : 0;
        $this->assign("inWhiteList",$inWhiteList);
        $this->display();
    }

    /**
     * 0元趴 详情 2015双11活动 用完删除
     */
    public function ajaxDetail11()
    {
        $prj_id = I('request.id', 0, 'intval');
        $gift_id = I('request.gift_id',0, 'intval');

        if (!$prj_id) {
            ajaxReturn('', '项目ID错误', 0);
        }
        $prj = M('prj')->where(array('id' => $prj_id))->find();
        $activity11_id = C('APP_STATUS') != 'product' ? C('ACTIVITY_ID'): C('ACTIVITY_ID');
        if ($prj['activity_id'] != $activity11_id) {
            ajaxReturn('', '该项目不参加双11活动', 0);
        }
        $giftList = service('Activity/Zero')->getGiftListPrjId($prj_id,$gift_id);
        $this->assign('gift_list', $giftList);
        $this->display();
    }

    /**
     * 项目详情 0元购活动 特别页面
     */
    function view11()
    {
        $id = I('request.id',0,'intval');
        $giftid = I('request.gift_id',0,'intval');
        if(!$id) redirect(U('Financing/Invest/plist'));
        $money11 = I('request.money', 0, 'intval');

        $this->hashId = $id;
        $id = $prj_id = service("Financing/Project")->parseIdView($id);
        $this->id = $this->prj_id = $id;

        $user = $this->loginedUserInfo;
        $salaryBtn = 1;
        try{
            if($id <= 0){
                throw_exception('项目ID错误，请检查后重试');
            }

            $info = service("Financing/Invest")->getPrjView($id);
            //p($info);exit;
            $serviceAN = service('Activity/Zero');
            if ($info['activity_id'] != $serviceAN->getActivityId()) {//是否活动标
                redirect(U('Activity/NovEleventh/index'));
            }

            if (!$gift = $serviceAN->getGiftByMoney($money11, $id)) {//是否可投金额
                $money11 = 0;
            }

            $this->assign('gift', $gift);
            $this->assign('money11', $money11);

            $gift_list = $serviceAN->getGiftListByPrjId($id);
            $this->assign('gift_list', $gift_list);

            $info['prj_type'] == PrjModel::PRJ_TYPE_G && redirect(U('Financing/InvestFund/view', array('id' => $id)));

            $this->assign("salary_permission",$salaryBtn);//按钮相关
            //
        }catch (Exception $e){
            $this->error($e->getMessage());
        }
        $this->assign('corp_id', $info['corp_id']);
        $corpScore = service("Financing/YrzdbApi")->getScore($info['corp_id']);
        $this->assign("corpScore", $corpScore);
        //add by 001181 如果是速兑通 直接使用新的速兑通的方法的链接
        if($info['prj_type'] == PrjModel::PRJ_TYPE_H){
            $this->fastCash();
            return ;
        }
        // 判断会员剩余金额是否足够
        $srvFinancing = service('Financing/Financing');
        $notEnough1 = $srvFinancing->isBlanceLess($user['uid'], $id, FALSE, $info);
        $notEnough = $notEnough1 || ((int)$srvFinancing->getMaxInvestMoney($user['uid'], $id,$project=NULL, $a_coupon_money=-1, $state=1) < $money11 * 100);
        $this->assign("money_notEnough", $notEnough);
        //最大投資金額
        $max_invest_money = $srvFinancing->getMaxInvestMoney($user['uid'], $id, $info,$a_coupon_money=-1, $state=1);
        try {
//            $defaultReward = service('Financing/ReduceTicket')->getDefaultReward($user['uid'], $this->id,
//                $info['time_limit_day'], $money11*100, 0);
            $defaultReward = array('reward_type'=>0);
        } catch (Exception $e) {
            $this->error($e->getMessage());
        }

        $this->assign('reward_info',$defaultReward);
        $this->assign("max_invest_money_view", floor($max_invest_money/100));
        if($max_invest_money) $max_invest_money = humanMoney($max_invest_money, 0, FALSE);
        $this->assign("max_invest_money", $max_invest_money);

        service("Account/Active")->clickBidLog("prjid_".$id,0);
        $c = service("Financing/Project")->getPrjTypeInt($info['prj_type']);
        if($c){
            $c = 99;
        }
        $this->assign("c",$c);

        //判断是否是新客项目
        //是否是投标中状态
        $mustDisable=0;
        if($info['bid_status'] == PrjModel::BSTATUS_BIDING){
            if($info['is_new']){
                $uid = $user['uid'];
                $userInfo = M("user")->find($uid);
                if(!$userInfo['is_newbie']){
                    $mustDisable =1;
                }
            }
        }

        //201547增加判断是否是融资者
        if($user['is_invest_finance']==2) $mustDisable = 1;

        //判断是否是车贷
        if($info['business_type'] == 1){
            $this->assign("car_loan",true);
            //线上的时候改为正式环境
            $bzj_tips=file_get_contents("http://10.50.28.91:8080/prj/httpService_depositRes.jhtml");
            $this->assign("bzj_tips",$bzj_tips);
        }
        //判断是否是银票
        if($info['business_type'] == 2){
            $this->assign("bill",true);
            $bill_fields = array(
                'bank_name',
                'end_time',
                'dead_time'

            );
            $bill_info = M('bill_info')->field($bill_fields)->where(array('prj_id'=>$id))->find();
            $this->assign("bank_name",$bill_info['bank_name']);
            $this->assign("end_time",date('Y-m-d',$bill_info['end_time']));
            $this->assign("dead_time",date('Y-m-d',$bill_info['dead_time']));

        }
        $this->mustDisable =$mustDisable;

        $user_college_ext = M('user_college_ext')->find($user['uid']);
        $this->assign("user_college_ext",$user_college_ext);

        $userBase = service("Payment/PayAccount")->getBaseInfo($user['uid']);
        $totalAward = 0;
//        $totalAward = service("Financing/Project")->getTotalAward($userBase['uid'], $userBase, $id);
        $user['amount_jd'] = humanMoney($userBase['amount']+$totalAward+$userBase['coupon_money'],2,false)."元";
        $user['amount_jj'] = $user['amount_jd'];

        $awardName = ($totalAward+$userBase['coupon_money'])/100;
        $user['award_name'] = $awardName;
        $user['award_view'] = humanMoney($totalAward+$userBase['coupon_money'],2,false)."元";
        $remain_money = ($userBase['amount']+$userBase['reward_money'])/100 >= $money11 ? $money11 : 0;

//        p($user);
        $islogin = $userBase ? 1:0;
        $this->assign("user",$user);
        $this->assign("islogin",$islogin);

        $url = urlencode(getCurrentUrl());
        $this->assign("url",$url);

        //渠道客户端
        $db_client_type = D("Financing/PrjFilter")->getClientType($id);
        $client_permission = true;
        if( !in_array($db_client_type, array(0,self::CLIENT_TYPE) ) ){
            $client_permission = false;    //非专享客户端，按钮灰色
        }
        $this->assign("client_permission",$client_permission);

        $client_type_config  = D("Financing/PrjFilter")->client_type_config;
        $this->assign("client_type_config",$client_type_config);
        $this->assign("client_type",$db_client_type);

        $cache_protocol = "prj_view_protocol_" . $id;
        $protocolInfoTmp = cache($cache_protocol);
        if(!$protocolInfoTmp) {
            $protocolInfoTmp = service("Financing/Project")->getProtocol($info['prj_type'],$info['zhr_apply_id'],$info['id']);
            cache($cache_protocol, $protocolInfoTmp, array('expire'=>600));
        }
        $protocolInfo['id'] = $protocolInfoTmp['key'];
        $protocolInfo['name'] = $protocolInfoTmp['name'];
        $this->protocolInfo = $protocolInfo;

        import_app("Financing.Model.PrjModel");
        //融资截至
        $isEnd = 0;
        if($info['bid_status'] > PrjModel::BSTATUS_BIDING){
            $isEnd = 1;
        }
        $this->assign("isEnd", $isEnd);

        $attachDownload = "";
        if($info['ext']['attach_filename'] && ($info['prj_type'] = PrjModel::PRJ_TYPE_C)){
            $attachArr = D("Public/Upload")->parseFileParam($info['ext']['attach']);
            $attachDownload = isset($attachArr['attach']['download']) ? $attachArr['attach']['download']:"";
        }
        $this->assign("attachDownload", $attachDownload);

        //最小投资金额是否更改
        $this->isBidAmountChange = service("Financing/Project")->isMinBidAmountCHange($info['remaining_amount'], $info['step_bid_amount'], $info['min_bid_amount']);

        //是否显示还款计划
        $this->isShowPlan = service("Financing/Invest")->isShowPlan($info['id']);
        //到期还款
        $this->isEndate = service("Financing/Invest")->isEndDate($info['repay_way']);
        if (!$this->isEndate) {
            //1万元乘以100
            $noteMoney = 1000000;
            $this->moneyNote = service("Financing/Invest")->getPrjMoneyNote($id, $noteMoney, $info);
        }
        $info['year_rate_show'] = number_format($info['year_rate'] / 10, 2) . "%";
        $this->assign("info", $info);

        $income_key = "invest_view_i_r_" . $id;
        $aincomer = cache($income_key);
        if ($aincomer) {
            $aincomerP = explode("_", $aincomer);
            $this->income = $aincomerP[0];
            $money = $aincomerP[1];
            $amount = $userBase['amount'] + $totalAward + $userBase['coupon_money'];
            $this->remaind = humanMoney($amount - $money, 2, false) . "元";
        } else {
            $money = $info['min_bid_amount'] / 100;
            $money = str_replace(",", "", $money);
            $money = $money * 100;
            if (!service("Financing/Invest")->isEndDate($info['repay_way'])) {
                $income = service("Financing/Invest")->getSpecialIncome($id, $money, time());
            } else {
                $income = service("Financing/Project")->getIncomeById($id, $money);
            }
            $income = humanMoney($income, 2, false);
            $amount = $userBase['amount'] + $totalAward + $userBase['coupon_money'];
            $remaind = humanMoney($amount - $money, 2, false) . "元";
            $this->income = $income;
            $this->remaind = $remaind;
            cache($income_key, $income . "_" . $money, array('expire' => 600));
        }

        $imgData = service("Application/ProjectAudit")->getAuditImgData($id);
        $this->assign("imgData",$imgData);
        // 担保函
        $this->danbaohan($id);
        $activity = $srvFinancing->getActivityView($info['activity_id']);

        if($activity && ($activity['reward_money_rate'] > 0)){
            //添加投资金额的
            if($activity['reward_money_action'] == RewardRuleModel::ACTION_PROJECT_INVEST){
                $activity['is_invest_icon'] = 1;//投资有奖励
                $activity['tips_show'] = $activity['name'];
            }else{
                $activity['is_invest_icon'] = 0;
            }
            $this->reward_money_rate = $reward_money_rate = $activity['reward_money_rate'];
            if ($activity['reward_money_rate_type'] == 'month') {
                $this->reward_money_rate = $reward_money_rate * 12;
            } elseif ($activity['reward_money_rate_type'] == 'day') {
                $this->reward_money_rate = $reward_money_rate * 365 / 10;
            }

        }
        $is_login = $this->loginedUserInfo ? true : false;
        $this->assign('is_login', $is_login);
        $muji_money = $info['demand_amount']-$info['remaining_amount'];
        $this->assign('muji_money',humanMoney($muji_money,2,false));
        $qixi_day = service('Financing/Prj')->getPrjQixiShow($prj_id);
        $this->assign('qixi_day',$qixi_day);
        $wanyuan_profit = service('Financing/Invest')->getSpecialIncome($info['id'], 1000000, time(), array('is_have_biding' =>0, 'is_have_reward' => false));
        $wanyuan_profit = humanMoney($wanyuan_profit,2,false);
        $this->assign('wanyuan_profit',$wanyuan_profit);
        $this->activity = $activity;
        $this->assign("prj_type", $info['prj_type']);

        $user_account = M('user_account')->find((int)$user['uid']);
        $this->assign("is_paypwd_edit", $user['is_paypwd_edit'] ||$user_account['pay_password']);//20160309 是否设置支付密码
        //0元购开关
        $zeroOf = M('system_config')->where(array('status'=>0,'config_key'=>'freePay'))->find();
        $this->assign("zero",$zeroOf);
        $this->assign('gift_id',$giftid);
        $this->assign('remain_money',$remain_money);
        $this->display();
    }

    public function view_ajax()
    {
        openMasterSlave();
        if (($id = I('request.id', 0, 'intval')) == 0) {
            ajaxReturn('', '项目不存在', 0);
        }

        /* @var $project_service ProjectService */
        $project_service = service('Financing/Project');
        $id = $project_service->parseIdView($id);
        $this->id = $id;

        $prj = M('prj')->field('id, prj_type, demand_amount, remaining_amount, matched_amount, status,
        bid_status, start_bid_time, end_bid_time, time_limit_unit, time_limit,is_new')->find($id);

        $prj = M('prj')->field("id,prj_type,demand_amount,remaining_amount,matched_amount,status,bid_status,start_bid_time,end_bid_time,time_limit_unit,time_limit,is_new")->find($id);
        //如果余额用完则置为满标状态
        if ($prj['bid_status'] != PrjModel::BSTATUS_FULL) {
            $isFull=service("Financing/Project")->setPrjFull($id,$prj['remaining_amount'],$prj['matched_amount'],$prj['prj_type']);
            if ($isFull) {
                $prj['bid_status'] = PrjModel::BSTATUS_FULL;
                //设置满标状态以后要重新设置数据
            }
        }
        $info = $project_service->getRealTimeData($prj);
        $data['prj'] = $info;

        $uid = $this->loginedUserInfo['uid'];
        $user_account_model = D('Account/UserAccount');
        $user_account = $user_account_model->where(['uid' => $uid])->find();

        $totalAward = $project_service->getTotalAward($uid, $user_account, $id);
        $reward_money = $project_service->getRewardMoney($uid, 0, $id);

        $prj_is_deposit = $project_service->projectIsDeposit($id);
        $user_account_amount = $user_account_model->getUserAccountByPrj($uid, $prj_is_deposit);

        $user_info['amount_jd'] = humanMoney($user_account_amount + $reward_money, 2,
                false) . "<small class='remainMoneyColor'>元</small>";

        $user_info['amount_jj'] = $user_info['amount_jd'];
        $awardName = ($totalAward + $user_account['coupon_money']) / 100;
        $user_info['award_name'] = $awardName;
        $user_info['award_view'] = humanMoney($totalAward + $user_account['coupon_money'], 2, false) . "元";

        $islogin = $user_account ? 1 : 0;

        $data['user'] = $user_info;
        $data['islogin'] = $islogin;

        //判断是否是新客项目
        //是否是投标中状态
        $mustDisable = 1;//不是新客户
        $data['mustDisable'] = $mustDisable;

        ajaxReturn($data);
    }

    //显示还款
    function ajaxShowRepay(){
        $prj_id = (int) $this->_request("id");
        $rest_money = floatval($this->_request("money"));
        $rest_money = $rest_money*100;

        $freezeTime = time();

        $prjInfo = M("prj")->where(array("id"=>$prj_id))->find();
        if($prjInfo['bid_status'] == 1 && M('prj_ext')->where(array('prj_id' => $prj_id))->getField('is_pre_sale')) $freezeTime = $prjInfo['start_bid_time'];

        #计算募集期还款计划
        if(service("Financing/Project")->isPrjHaveBidingIncoming($prj_id)) {
            $income = service("Financing/Project")->getIncomeByTime($prj_id, $rest_money, $freezeTime, $prjInfo['end_bid_time']);
            $income = $income < 0 ? 0 : $income;
        } else {
            $income = 0;
        }

        //todo 时间 ,起息日
        $t = strtotime(date('Y-m-d', $prjInfo['end_bid_time']));
        $balanceTime = service("Financing/Project")->getBalanceTime($prjInfo['end_bid_time'], $prjInfo['id']);
        if ($prjInfo['end_bid_time'] > $balanceTime) $t = strtotime("+1 days", $t);//T向后延迟一天

        $obj = service("Financing/Project")->getTradeObj($prj_id, "Bid");

        $freezeDate = date('Y-m-d', service("Financing/Project")->getPrjValueDate($prj_id, $t));
        $periodParams['freeze_date'] = $freezeDate;
        $periodParams['capital'] = $rest_money;
        $periodParams['prj_id'] = $prj_id;
        $periodResult = $obj->getPersonRepaymentPlan($periodParams);

        $list = array();

        $first['repay_periods'] = 0;
//      $first['repay_date_view'] = date('Y-m-d', strtotime("+1 days",$t));
        $first['repay_date_view'] = service('Financing/Project')->getPrjFirstRepayDate($prj_id, $prjInfo);;
        $first['yield_view'] = humanMoney($income, 2, false);
        $first['rest_principal_view'] = humanMoney($rest_money, 2, false);
        $first['repay_money_view'] = humanMoney($income, 2, false);

        $list[] = $first;

        if ($periodResult) {
            foreach ($periodResult as $v) {
                $second = array();
                $second['repay_periods'] = $v['no'];
                $second['repay_date_view'] = $v['repay_date'];
                $second['principal_view'] = humanMoney($v['capital'], 2, false);
                $second['yield_view'] = humanMoney($v['profit'], 2, false);
                $second['repay_money_view'] = humanMoney($v['repay_money'], 2, false);
                $second['rest_principal_view'] = humanMoney($v['last_capital'], 2, false);
                $second['principal_view'] = humanMoney($v['capital'], 2, false);
                $list[] = $second;
            }
        }

        $this->list = $list;

        $this->display("ajaxshowrepay");
    }

    /**
     * 富二代
     */
    function fed(){
        openMasterSlave();
        $user = $this->loginedUserInfo;
        $this->user = $user;
        $this->isLogin = $user ? '1' : '0';

        //7日数据统计
        $investStat = M("rep_prj_st")->order("id desc")->find();
        $investStat = array_change_key_case($investStat);
        foreach (array('all_amount', 'week_amount', 'week_average_transfer_amount') as $k){
            $investStat[$k] = number_format($investStat[$k], 2);
        }
        $this->investStat = $investStat;

        //是否转让过
        $uid = (int) $user['uid'];
        $checkWhere = "uid=".$uid." OR buy_uid = ".$uid."";
        $this->check = M("asset_transfer")->where($checkWhere)->find();

        //担保公司
//        $this->guarantor = service("Financing/Project")->getGuarantor();

        $this->display();
    }

    function ajaxfed(){
        $c = 4;
        $projectService = service("Financing/Project");
        import_app("Financing.Model.PrjModel");
        //条件搜索
        $parameter = array();

        $bid_st = (int) $this->_request("bid_st");
        $parameter['bid_st'] = $bid_st;
        import_app("Financing.Model.AssetTransferModel");
        if($bid_st == 1){
            $where['transfer_status'] =AssetTransferModel::PRJ_TRANSFER_BIDING;
        }elseif($bid_st == 2){
            $where['transfer_status'] =AssetTransferModel::PRJ_TRANSFER_BIDED;
        }
        //期限
        $timeLimit = (int) $this->_request("time_limit");
        $parameter['time_limit'] = $timeLimit;
        if($timeLimit==5){
            $where['time_limit_day'] = array(array("GT",12*30));
        }elseif($timeLimit == 4){
            $where['time_limit_day'] = array(array("EGT",9*30),array("ELT",12*30));
        }elseif($timeLimit == 3){
            $where['time_limit_day'] = array(array("EGT",6*30),array("ELT",9*30));
        }elseif($timeLimit == 2){
            $where['time_limit_day'] = array(array("EGT",3*30),array("LT",6*30));
        }elseif($timeLimit == 1){
            $where['time_limit_day'] = array(array("EGT",1*30),array("LT",3*30));
        }
        //利率
        $rate = (int) $this->_request("rate");
        $parameter['rate'] = $rate;

        if($rate == 4){
            $where['year_rate'] = array("GT",130);
        }elseif($rate == 3){
            $where['year_rate'] = array(array("EGT",110),array("LT",130));
        }elseif($rate == 2){
            $where['year_rate'] = array(array("EGT",90),array("LT",110));
        }elseif($rate == 1){
            $where['year_rate'] = array(array("EGT",70),array("LT",90));
        }

        //投资起始金额
        $transferAmount = (int) $this->_request("transfer_amount");
        $parameter['transfer_amount'] = $transferAmount;

        if($transferAmount == 5){
            $where['demand_amount'] = array("GT",15000000);
        }elseif($transferAmount == 4){
            $where['demand_amount'] = array(array("EGT",5000000),array("ELT",15000000));
        }elseif($transferAmount == 3){
            $where['demand_amount'] = array(array("EGT",1000000),array("LT",5000000));
        }elseif($transferAmount == 2){
            $where['demand_amount'] = array(array("EGT",100000),array("LT",1000000));
        }elseif($transferAmount == 1){
            $where['demand_amount'] = array("LT",100000);
        }

        //保障性质
        $safeguards = (int) $this->_request("prj_safeguards");
        $parameter['prj_safeguards'] = $safeguards;
        if($safeguards == 2){
            $where['safeguards'] = PrjModel::SAFEGUARDS_CAPITAL;
        }elseif($safeguards == 1){
            $where['safeguards'] = PrjModel::SAFEGUARDS_INTEREST;
        }

        //还款方式
        $repay_way = (int) $this->_request("repay_way");
        $parameter['repay_way'] = $repay_way;
        if($repay_way == 1){
            $where['repay_way'] = array(array("EQ",PrjModel::REPAY_WAY_ENDATE),array("EQ",PrjModel::REPAY_WAY_E),"OR");
        }elseif($repay_way == 2){
            $where['repay_way'] = PrjModel::REPAY_WAY_D;
        }elseif($repay_way == 3){
            $where['repay_way'] = PrjModel::REPAY_WAY_PERMONTH;
        }

        //担保公司
        $guarantor_id = (int)I('request.guarantor_id');
        $parameter['guarantor_id'] = $guarantor_id;
        if($guarantor_id){
            if($guarantor_id == 1){
                $where['guarantor_id'] = array("IN",array(1,5));
            }else{
                $where['guarantor_id'] = $guarantor_id;
            }
        }

        $orderBy = $this->_request("order");
        $parameter['order'] = $orderBy;
        $page = (int) $this->_request("p");
        $page = $page ? $page :1;
        $pageSize = 10;
        $orderArr = array();
        import_app("Financing.Model.AssetTransferModel");
        $this->hasBid = M("prj")->where(array("transfer_id"=>array("EXP","IS NOT NULL"),"transfer_status"=>AssetTransferModel::PRJ_TRANSFER_BIDING))->field("id")->find();
        if(!$this->hasBid) $orderBy=false;
        $isOrderBy=0;
        if($orderBy){
            $isOrderBy=1;
            list($field,$order) = explode("|", $orderBy);
            if(!in_array(strtoupper($order), array('ASC', 'DESC'))) $order = 'ASC';
            $this->assign("order_field",$field);
            $this->assign("order_type",$order);
            $orderArr = " bid_status_order ASC ";
            if($field == 'order1'){
                $orderArr .= ", demand_amount ". $order;
            }elseif($field == 'order2'){
                $orderArr .= ", year_rate ". $order;
            }elseif($field == 'order3'){
                $orderArr .= ", time_limit_day ". $order;
            }elseif($field == 'order4'){
                $orderArr .= ", min_bid_amount ". $order;
            }
        }else{
            $orderArr = "  bid_status_order ASC ,
                            CASE  WHEN bid_status_order=4 THEN fi_prj.ctime END ASC,
                            CASE  WHEN bid_status_order=7 THEN transfer_time END DESC
                        ";
        }

        $where['status'] = PrjModel::STATUS_PASS;//审核通过

        $where['prj_series'] = $projectService->getPrjSeriesFed();
        $serviceObj = service("Financing/Project");

        $uid = $this->loginedUserInfo['uid'];
        // echo "<pre>";
        // print_r($where);
        //投放站点
        $where['_string'] = sprintf(" CASE WHEN bid_status>%d THEN ( mi_no!='1234567889' OR mi_no!='1234567890' ) ELSE ( mi_no='1234567889' OR mi_no='1234567890' ) END", PrjModel::BSTATUS_BIDING);


        $result = $serviceObj->financList140710($where,$orderArr,$page,$pageSize,'',true,$uid,$isOrderBy);
        $list = $result['data'];
        $totalRow = $result['total_row'];
        $this->assign("list",$list);
        //分页
        $parameter['c'] = $c;

        $paging  = W("Paging",array("totalRows"=>$totalRow,"pageSize"=>$pageSize,"parameter"=>$parameter),true);
        $this->assign("paging",$paging);




        $isLogin = $this->loginedUserInfo?1:0;

        $this->assign("isLogin",$isLogin);
        $this->assign("c",$c);

        $this->display("project_zhaiquan");
    }

    /**
     * 检测用户是否有商户Id
     *
     * @return mixed
     */
    private function check_tenant() {
        try {
            $uid = $this->loginedUserInfo['uid'];
            $user = M('User')->find($uid);
            $dept_id = (int) $user['dept_id'];

            $tenant_id = service("Account/Account")->getTenantIdByUser($user);
            $this->assign('has_tenant_id', $tenant_id);
            return $tenant_id;
        } catch (Exception $e) {
            return FALSE;
        }
    }
    //我要转让
    public function tlist($p=1) {
        $this->check_tenant();

        $page = (int)$p;

        $user = $this->loginedUserInfo;
        $uid = $user['uid'];
        $srvFinancing = service('Financing/Financing');
        $result = $srvFinancing->listTranfer($uid, $page, 10);

        $list = $result['data'];
        $paging  =  W('Paging', array(
            'totalRows'=> $result['total'],
            'pageSize'=> 10,
            'parameter' => '',
        ), TRUE);
        $this->assign('total', $result['total']);
        $this->assign('list', $list);
        $this->assign('paging', $paging);

        $this->display("project_my");
    }

    function viewFed(){
        $id = $this->_get("id");

        $id = service("Financing/Project")->parseIdView($id);

        $transferObj = service("Financing/Transfer");
        $fed = $transferObj->getById($id);
        if(!$fed){
            $this->error("异常,转让数据不存在!");
        }
        service("Account/Active")->clickBidLog("fed_".$id,0);
        $c = service("Financing/Project")->getPrjTypeInt($fed['project']['prj_type']);

        $this->assign("c",$c);


        $this->isTrans=0;
        if($fed['status'] == TransferService::TRANSFER_STATUS_SUCESS){
            $this->isTrans=1;
        }

        if(service("Financing/Invest")->isEndDate($fed['project']['repay_way'])){
            $fed['pri_interest'] = $fed['income'];
            $fed['pri_interest_view'] = $fed['income'];
        }else{

            $where = array();
            $where['prj_order_id'] = $fed['from_order_id'];
            $where['status'] = PrjOrderRepayPlanModel::ORDER_REPAYMENT_STATUS_WAIT;
            $pri_interestResult = M("prj_order_repay_plan")->where($where)->field("SUM(pri_interest) as total_pri_interest")->find();
            $fed['pri_interest'] = $pri_interestResult['total_pri_interest'];
            $fed['pri_interest_view'] = humanMoney($pri_interestResult['total_pri_interest'],2,false)."元";
        }

        $rPrjId= M("prj")->where(array("transfer_id"=>$id))->getField("id");
        $this->assign("rPrjId",$rPrjId);
//         print_r($fed);
        $url = urlencode(getCurrentUrl());
        $this->assign("url",$url);
        $fed['project']['is_can_transfer'] = service('Financing/Financing')->isCanTransfer($fed['project']['id'], $fed['project'], 1);
        $this->assign("info",$fed['project']);
        $this->assign("fed",$fed);

//         print_r($fed);

        $user = $this->loginedUserInfo;

        // 判断会员剩余金额是否足够
        //$this->assign("money_notEnough", service("Financing/Financing")->isBlanceLess($user['uid'], $id, true));

        $totalAward = service("Financing/Project")->getTotalAward($user['uid']);

        $user = service("Payment/PayAccount")->getBaseInfo($user['uid']);

        $user['amount_jj'] = humanMoney($user['amount']+$totalAward+$user['coupon_money'],2,false)."元";

        $remain = service("Financing/Transfer")->moneyCompute($id,$user['uid'],0,($totalAward+$user['coupon_money']));
        $user['remain'] = humanMoney($remain,2,false)."元";
        $user['award_view'] = humanMoney($totalAward+$user['coupon_money'],2,false)."元";
        $user['award_name'] = ($totalAward+$user['coupon_money'])/100;
        $this->remain = $remain;

        $attachDownload = "";
        if($fed['project']['ext']['attach_filename'] && ($fed['prj_type'] = PrjModel::PRJ_TYPE_C)){
            $attachArr = D("Public/Upload")->parseFileParam($fed['project']['ext']['attach']);
            $attachDownload = isset($attachArr['attach']['download']) ? $attachArr['attach']['download']:"";
        }

        $this->assign("attachDownload",$attachDownload);


        $islogin = $user ? 1:0;
        $this->assign("user",$user);
        $this->assign("id",$id);
        $this->assign("islogin",$islogin);

        $this->protocolInfo = array("id"=>10,"name"=>"鑫合汇债权再转让协议");

        // 担保函
        $this->danbaohan($fed['project']['id']);
        $this->display("view_fed");
    }

    function ajaxviewFed(){
        $id = (int) $this->_request("id");
//         $award = floatval($this->_request("award"));

        $this->id = $id;
        $transferObj = service("Financing/Transfer");
        $fed = $transferObj->getById($id);
        if(!$fed){
            exit("异常,转让数据不存在!");
        }

        if($fed['status'] == TransferService::TRANSFER_STATUS_SUCESS){
            exit("异常,该产品已转让!");
        }

        $isExpire = service("Financing/Financing")->isTransExpire($fed['id'], TRUE);

        if($isExpire){
            exit("异常,转让已过期!");
        }

        $user = $this->loginedUserInfo;

        $totalAward = service("Financing/Project")->getTotalAward($user['uid']);

        $user = service("Payment/PayAccount")->getBaseInfo($user['uid']);
        $user['amount_jj'] = humanMoney($user['amount']+$totalAward+$user['coupon_money'],2,false)."元";

        if(service("Financing/Invest")->isEndDate($fed['project']['repay_way'])){
            $fed['pri_interest_view'] = $fed['income'];
            $fed['will_income_view'] = humanMoney($fed['income_tmp']-$fed['money'],2,false)."元";
        }else{

            $where = array();
            $where['prj_order_id'] = $fed['from_order_id'];
            $where['status'] = PrjOrderRepayPlanModel::ORDER_REPAYMENT_STATUS_WAIT;
            $pri_interestResult = M("prj_order_repay_plan")->where($where)->field("SUM(pri_interest) as total_pri_interest")->find();
            $total_pri_interest = isset($pri_interestResult['total_pri_interest']) ? $pri_interestResult['total_pri_interest']:0;
            $fed['pri_interest_view'] = humanMoney($total_pri_interest,2,false)."元";
            $fed['will_income_view'] = humanMoney($total_pri_interest-$fed['money'],2,false)."元";
        }

        $this->assign("info",$fed['project']);
        $this->assign("fed",$fed);
        $this->assign("user",$user);

        C("TOKEN_ON",true);
        $this->display("ajaxviewfed");
    }

    //购买检查
    function fedbuycheck(){
        $id = (int) $this->_request("id");
//         $award = floatval($this->_request("award"));

        $authCode = $this->_request("authCode");
        //判断余额是否够用
        $user = $this->loginedUserInfo;
        $uid = $user['uid'];

        /* 检测验证码 */
        if(!check_verify($authCode,'fedbuy')){
            ajaxReturn(0,'验证码错误！',0);
        }

        $transferObj = service("Financing/Transfer");
        $fed = $transferObj->getById($id);
        if(!$fed){
            exit("异常,转让数据不存在!");
        }
        $user_account = M('user_account')->find($uid);
        $params['coupon_money'] = $user_account['coupon_money'];
        $award = service("Financing/Project")->getRealAward($uid,$fed['money'], $params['coupon_money']);

        $checkResult = service("Financing/Transfer")->buyCheck($uid,$id,$award);

        if(!$checkResult){
            ajaxReturn(0,MyError::lastError(),0);
        }
        ajaxReturn();
    }

    /**
     * 富二代购买
     */
    function fedbuy(){
        $id = (int) $this->_request("id");

        //判断余额是否够用
        $user = $this->loginedUserInfo;
        $uid = $user['uid'];

        //是否需要检查支付密码
        $paypwd = $this->_request("paypwd");
        if(!$paypwd) ajaxReturn(0,'支付密码不能为空！',0);
        $userInfo = $this->loginedUserInfo;
        $uid = $userInfo['uid'];
        if(!service("Account/Account")->checkPayPwd($uid,$paypwd)){
            ajaxReturn(0,'支付密码不匹配！',0);
        }

        $transferService = service("Financing/Transfer");
        $info = $transferService->getDataById($id);
        if(!$info){
            ajaxReturn(0,'异常，转让信息不存在！',0);
        }

        $user_account = M('user_account')->find($uid);
        $params['coupon_money'] = $user_account['coupon_money'];
        $award = service("Financing/Project")->getRealAward($uid,$info['money'],$user_account['coupon_money']);
        $checkReturn = $transferService->buyCheck($uid,$id,$award);
        if(!$checkReturn) ajaxReturn(0,MyError::lastError(),0);
        //购买
        C("TOKEN_ON",true);
        $result = $transferService->buy($uid,$id,$award);
        if(!$result){
            $error = MyError::lastError();
            D("Admin/AccountExceptLog")->insertLog($this->loginedUserInfo['uid'], "Invest/fedbuy", "Financing/Transfer/$id", "buy", $error);
            ajaxReturn(0,$error,0);
        }
        $asset_transfer = M("asset_transfer")->find($result);
        if($asset_transfer){
            $free_money = $asset_transfer['free_money'];
            $free_tixian_times = $asset_transfer['free_tixian_times'];
        }
        //交易确认
        service("Account/Active")->clickBidLog("fed_".$id,1);

        $return=array();
        if($free_money){
            $return['free_money'] = $free_money/100;
        }else{
            $return['free_money'] = 0;
        }
        $return['orderInfo'] = array("transfer_id"=>$id,"uid"=>$uid,"time"=>date('Y-m-d H:i:s'));

        ajaxReturn($return);
    }

    //计算剩余额度
    function fmoneyCompute(){
//      $award = $this->_request("award");
        $id = (int) $this->_request("id");
        $user = $this->loginedUserInfo;
        $transferService = service("Financing/Transfer");
        $info = $transferService->getDataById($id);


        $totalAward = service("Financing/Project")->getTotalAward($user['uid']);
        $user_account = M('user_account')->find($user['uid']);
        $params['coupon_money'] = $user_account['coupon_money'];

        $remaind = service("Financing/Transfer")->moneyCompute($id,$user['uid'],0,$totalAward+$params['coupon_money']);
        $remaind = humanMoney($remaind,2,false)."元";

        ajaxReturn(array("remaind"=>$remaind));
    }

    /**
     * 计算借贷收益
     */
    function ajaxJdMoneyCompute(){
        openMasterSlave();
        $money = I('request.money');
        $prj_id = (int)I('request.prjid');
        $init = 0;
        $state = (int)I('request.chooseStatus', 0);
        $reward_type = (int)I('request.reward_type');
        $user_ticket_id = (int)I('request.reward_id',0); // 加息券id
        $money = str_replace(',', '', $money);
        $money = $money * 100;
        $uid = $this->mid;


        $user_bonus_view = 0;
        try {
//            $java_bonus_service = new \App\Modules\JavaApi\Service\JavaBonusService();
//            $use_bonus = $java_bonus_service->getAllBonusAmount($uid, $prj_id, $money, $init);
            $project = M('prj')->where(array('id' => $prj_id))->find();
            //获取默认的最优红包，满减券，加息券
            $default_reward = service('Financing/ReduceTicket')->getDefaultReward($uid, $prj_id, $project['time_limit_day'],$money, $init);

            $user_ticket_id = $default_reward['rewardId'];

//            if($state==0){
//
//            }elseif($state==1){
//                //根据选中的券获取列表
//                $default_reward = service('Financing/ReduceTicket')->getSelectedReward($user_ticket_id,$reward_type,$uid, $prj_id, $project['time_limit_day'],$money, $init);
//            }
//            if($default_reward['reward_type']==1){
//                $user_bonus_view = humanMoney($default_reward['original_num'], 2, false);
//            }
        } catch (Exception $e) {
            ajaxReturn(array(), \App\Lib\Service\JavaService::ERROR_MSG, 0);
        }

        if($default_reward['rewardType']==3 && $default_reward['rewardId']) {
            $rate =$default_reward['rate']*10 ;
        }else{
            $rate=0;
        }
        $income = service('Financing/Invest')->getSpecialIncome($prj_id, $money, time(), array(
            'is_have_biding' => true,
            'is_have_reward' => true,
            // 'user_ticket_id' => $user_ticket_id,
            'rate' => $rate,
            'is_split' => true,
        ));


        $income_arr = array(
            'benjinIncome' => humanMoney($income['income_normal'] + $income['income_biding'], 2, false),
            'redBaoIncome' => humanMoney($income['income_reward'] + $income['income_jiaxi'], 2, false),
            'allIncome' => humanMoney($income['total'], 2, false),
//            'useBonus' => $user_bonus_view,
            'reward_info' => $default_reward,
        );

        $user_account = M('user_account')->find($uid);
        $total_award = service('Financing/Project')->getTotalAward($uid, null, $prj_id);

        $prj_is_deposit = M('prj_ext')->where(['prj_id' => $prj_id])->getField('is_deposit');
        $user_account_amount = $prj_is_deposit ? $user_account['zs_amount'] : $user_account['amount'];
        $amount = $user_account_amount + $user_account['coupon_money'] + $total_award;
        $remaind = humanMoney($amount - $money, 2, false) . '元';

        ajaxReturn(array('income' => $income_arr, 'remaind' => $remaind));
    }

    /**根据选中的券返回使用账户余额和收益
     * @param $prjId
     * @param $money
     * @param $reward_id
     * @param $reward_type
     * @param int $amount
     * @return array
     */
    public function ajaxGetSelectedOne($prjId,$money,$reward_id,$reward_type,$amount=0,$rate=0){
        if($reward_type==3){
            $use_money = $money;
            $income = service('Financing/Invest')->getSpecialIncome($prjId, $money*100, time(), array('is_have_biding' => true, 'is_have_reward' => true,'rate' => $rate*10,'is_split' => true));
            $allIncome = humanMoney($income['total'], 2, false);
            $hongbaoGain = humanMoney($income['income_reward'] + $income['income_jiaxi'], 2, false);
            ajaxReturn(array('use_money'=>$use_money,'profit'=>$allIncome,'hongbaoGain'=>$hongbaoGain));
        }else{
            if($reward_type==2){
                //$amount = M('user_market_ticket')->where(array('id'=>$reward_id,'uid'=>$this->loginedUserInfo['uid']))->getField('amount');
                // $amount = $amount ? $amount/100 : 0;
                $amount = $amount ? $amount : 0;
            }
            $use_money = $money-$amount;
            $income = service('Financing/Invest')->getSpecialIncome($prjId, $money*100, time(),array('is_have_biding' => true, 'is_have_reward' => true,'rate' => 0,'is_split' => true));
            $hongbaoGain = humanMoney($income['income_reward'], 2, false);
            $allIncome = humanMoney($income['total'], 2, false);
            ajaxReturn(array('use_money'=>$use_money,'profit'=>$allIncome,'hongbaoGain'=>$hongbaoGain));
        }
    }

    /**
     * 项目详情--已废弃
     */
    function ajaxview(){
        $id =  (int) $this->_request("id");
        $prjId = $this->id = $id;
        $money =  floatval(str_replace(',', '', $this->_request('money')));
        $authCode = $this->_request('authCode');

        $userInfo = $this->loginedUserInfo;
        $uid = $userInfo['uid'];

        $this->money = $money;
        $this->moneyView = number_format($money,2)."元";

        $projectService = service("Financing/Project");
        $info = $projectService->getPrjInfo($id);
        $info['time_limit_unit_name'] = $projectService->getTimeLimitUnit($info['time_limit_unit']);
        $info['prj_type_name'] = $projectService->getPrjTypeName($info['prj_type']);
        if(!$info){
            exit("异常,项目不存在!");
        }

        $this->assign("info",$info);
        $this->assign("authCode",$authCode);

        C('TOKEN_ON',true);
        $this->display("ajaxview");
    }


    //购买前的检查
    function buyCheck(){
        $money = floatval(str_replace(',', '', $this->_request('money')));
//         $award =  floatval($this->_post("award"));

        $authCode = $this->_post("authCode");
        $prjid = (int) $this->_post('prjid');

        /* 检测验证码 */
        if(!check_verify($authCode)){
            ajaxReturn(0,'验证码错误！',0);
        }

        $userInfo = $this->loginedUserInfo;
        $uid = $userInfo['uid'];




        $money = $money*100;

        $user_account = M('user_account')->find($uid);
        $params['coupon_money'] = $user_account['coupon_money'];

        $award = service("Financing/Project")->getRealAward($uid,$money,$params['coupon_money'], $prjid);
        $params['award'] = $award;
        $checkReturn  = service("Financing/Project")->buyCheck($money,$prjid,$uid,$params);

        if(!$checkReturn){
            ajaxReturn(0,MyError::lastError(),0);
        }

        ajaxReturn();
    }

    /**
     * 投资
     * 所有项目的投资入口
     */
    function buy()
    {
        ignore_user_abort(true);
        set_time_limit(0);
        //投资金额 单位元
        $money = I('post.money', 0, 'floatval');
        //项目ID
        $prjid = I('post.id', 0, 'intval');
        //支付密码
        $paypwd = $_POST['paypwd'];
        //是否预售购买
        $is_pre_buy = I('post.is_pre_buy', 0, 'intval');

        //使用奖励的类型(1红包 2满减券 3加息券)
        $reward_type = I("post.reward_type", 0, "intval");
        //使用满减少券类型id (6.2.0使用红包reward_id传利率，取消取整)
        $reward_id = I("post.reward_id", 0);

        /**
         * 参数校验
         */
        if ($money <= 0) {
            ajaxReturn(0, '投资金额不对哦，亲，请检查后重试', 0);
        }

        if ($prjid <= 0) {
            ajaxReturn(0, '项目ID错误，请检查后重试', 0);
        }

        if (!$paypwd) {
            ajaxReturn(0, '请输入支付密码', 0);
        }

        /* @var $project_service ProjectService */
        $project_service = service("Financing/Project");

        $info = $project_service->getDataById($prjid);
        if (!$info) {
            ajaxReturn(0, '该项目不存在，请检查后重试', 0);
        }
        if ($info['bid_status'] > PrjModel::BSTATUS_BIDING) {
            ajaxReturn(0, '当前不是投资中状态', 0);
        }

        $userInfo = $this->loginedUserInfo;
        $uid = $userInfo['uid'];

        //201547增加判断是否是融资者
        if ($userInfo['is_invest_finance'] == 2) {
            ajaxReturn(0, '你是融资者不能进行投资', 0);
        }

        //20150317 start 用户未设置支付密码，点击确认支付后提示去设置支付密码
        $user_account = M('user_account')->find((int)$userInfo['uid']);
        if ((!$userInfo['is_paypwd_edit']) && (!$user_account['pay_password'])) {
            ajaxReturn(0, '请先去设置支付密码', 0);
        }//end

        if (!service("Account/Account")->checkPayPwd($uid, $paypwd)) {
            ajaxReturn(0, '支付密码错误，请检查后重试', 0);
        }

        //0元购活动逻辑 begin
        if (!service('Activity/Zero')->checkBuy($prjid, $money)) {
            ajaxReturn(0, '双十一活动不接受非特定金额的投标', 0);
        }
        //0元购活动逻辑 end

        if ($userInfo['zs_close_invest_code'] == 0) {
            $userInfo['zs_close_invest_code'] = M('user')->where(['uid' => $uid])->getField('zs_close_invest_code');
            //校验token
            if (C('BUY_TOKEN_ON') && !$project_service->pcTokenCheck($uid, $prjid)) {
                ajaxReturn(0, '太快了，请刷新重试!', 0);
            }
        }

        //鑫利宝（企福鑫壳自己不能投资自己）
        $prj_ext_info = D('Financing/PrjExt')->find($prjid);
        if($prj_ext_info['is_xlb'] == 1 && $prj_ext_info['is_qfx'] == 1 ){
            $financier_uid = $info['uid'];
            $xlb_join = M("xlb_join_user")->where(array("investor_uid" => $uid))->find();
            if($financier_uid == $xlb_join['financier_uid']) ajaxReturn(0, '不可投资该项目!', 0);
        }

            //投资金额单位转化为分
            $my_money = (int)bcmul($money, '100', 0);
            $prj_is_deposit = M('prj_ext')->where(['prj_id' => $prjid])->getField('is_deposit');
            if ($prj_is_deposit && $userInfo['zs_close_invest_code'] == 0) {

                try {
                    $prj_buy_queue_service = new PrjBuyQueueService();
                    $prj_buy_queue_service->buyAmountCheck($my_money, $prjid, $uid);
                } catch (Exception $e) {
                    ajaxReturn(0, $e->getMessage(), 0);
                }

                ajaxReturn(['close_invest_code' => 1], '请先关闭浙商短信验证码', 0);
            }

            $buy_params = array(
                'uid' => $uid,
                'prj_id' => $prjid,
                'money' => $my_money,
                'is_pre_buy' => $is_pre_buy,//是否预售
                'reward_type' => $reward_type,//使用奖励类型
                'reward_id' => $reward_id,//使用奖励类型
            );
            /*$is_qfx = M('prj_ext')->where(array('prj_id'=>$prjid))->getField('is_qfx');*/

            if (in_array($info['prj_type'], [PrjModel::PRJ_TYPE_H, PrjModel::PRJ_TYPE_J]) || $is_pre_buy /*|| $is_qfx*/) {
                $award = $project_service->getRealAward($uid, $my_money, 0, $prjid);
                $buy_params['award'] = $award;
                $result = $project_service->newBuy($buy_params);
                if ($result['status'] == 1) {
                    ajaxReturn($result);
                } else {
                    ajaxReturn(array("type" => $result['type']), $result['msg'], 0);
                }
            } else {
                //加入队列begin
                $PrjBuyQueue = service("Financing/PrjBuyQueue");
                $queueid = md5($prjid . $uid . microtime(true));
                $xprjIdAccess = I('request.xprjIdAccess');
                $buy_params['from_client'] = D("Account/User")->getFrom();
                $inqueuert = $PrjBuyQueue->putInQueue($queueid, $prjid, $uid, $my_money, $paypwd, $xprjIdAccess, $buy_params);
                if ($inqueuert) {
                    $rt = array('uuid' => $queueid, 'status' => 1);
                    ajaxReturn($rt, '加入队列成功，请耐心等待！', 1);
                } else {
                    $rt = array('uuid' => $queueid, 'status' => 2);
                    ajaxReturn($rt, '请过3秒后重试', 1);
                }
            }
    }

    /**
     * 供前端轮询查询用户的投资状态 以返回值中的status为准
     * @param $prj_id
     * @param $uid
     * @param $uuid
     * @return mixed|string status 1-处理中 2-成功(跳转至buySuccess页面) 3-失败
     */
    function getInvestorStatus()
    {
        $prj_id = I('post.prj_id',0,'intval');
        $uid = I('post.uid',0,'intval');
        $uuid = $_POST['uuid'];
        if (empty($prj_id)||empty($uid)||empty($uuid)) {
            ajaxReturn(0, '参数不对', 0);
        }
        try{
            $ret = service("Financing/PrjBuyQueue")->getInvestorStatus($prj_id, $uid, $uuid);
            if($ret){
                $arr = array(1=>'正在处理中',2=>'购买成功',3=>'购买失败');
                $status = array('status'=>$ret['status'],'order_id'=>$ret['prj_order_id']);
                $msg = $arr[$ret['status']];
                if($ret['status']==3 && $ret['msg'])$msg = $ret['msg'];
                $status['message'] = $msg;
                ajaxReturn($status, $msg, 1);
            }else{
                ajaxReturn(0,MyError::lastError(),0);
            }
        }catch(Exception $e){
            ajaxReturn(0,$e->getMessage(),0);
        }

    }

    /**
     * 投资成功页
     * @return [type] [description]
     */
    function buySuccess(){
        $id = I("get.id", 0);
        $is_pre_sale = I("get.is_pre_sale",0);
        $this->is_pre_sale = $is_pre_sale;
        if($is_pre_sale == 1){
            $prjOrderObj = M("prj_order_pre");
        }else{
            $prjOrderObj = D("Financing/PrjOrder");
        }
        $where = array(
            'id'=>$id,
            'uid'=>$this->loginedUserInfo['uid'],
        );
        $prjOrderInfo = $prjOrderObj->where($where)->find();
        !$prjOrderInfo && $this->error("请勿非法访问");
        if($is_pre_sale == 0 && $prjOrderInfo['free_money']) {
            $this->free_money = humanMoney($prjOrderInfo['free_money'], 0, FALSE);
            $this->tixian = $prjOrderInfo['free_tixian_times'];
        }
        $this->money = humanMoney($prjOrderInfo['money'], 2, FALSE);
        $prjInfo = D("Financing/Prj")->where(array("id" => $prjOrderInfo['prj_id']))->find();
        //红包奖励 利率
        $activity = service('Financing/Financing')->getActivityView($prjInfo['activity_id']);
        //排除掉投资奖励红包的
        if($activity && $activity['reward_money_rate']>0 && $activity['reward_money_action'] != RewardRuleModel::ACTION_PROJECT_INVEST){
            $reward_money_rate = $activity['reward_money_rate'];
            if($activity['reward_money_rate_type'] =='month'){
                $reward_money_rate = $reward_money_rate*12;
            }
            if($activity['reward_money_rate_type'] =='day'){
                $reward_money_rate = $reward_money_rate*365/10;
            }
        }
        $this->rewardMoneyRate = $reward_money_rate;

        $this->prjNo = $prjInfo['prj_no'];
        $this->prjName = $prjInfo['prj_name'];
        $this->QixiDay =  service("Financing/Prj")->getPrjIncomeDate($prjOrderInfo['prj_id'],'','Y年m月d日');
        $this->RepayDay = service("Financing/Prj")->formatPrjRepayDate($prjInfo,'','Y年m月d日');
        $this->prjTypeName = service("Financing/Project")->getPrjTypeName($prjInfo['prj_type']);
        //查找时间
        $mod = D("Account/UserBonusPackage");
        $shake = array(
            'bonus_package_rule_id'=>1,
            'uid'=>$this->loginedUserInfo['uid'],
            'obj_id'=>$id,
            'obj_type'=>1,
        );
        $can_shake = $mod->canShake($shake);
        $this->assign("can_shake",$can_shake===true?1:0);//是否可以摇一摇
        $time = time();
        $tstatus = 0;
        $QiuShouEnd = strtotime(C('QIUSHOU'));  //秋收起义结束的时间
        if($time < $QiuShouEnd ){
            $count = $prjOrderObj->where(array('uid'=>$this->loginedUserInfo['uid']))->count();
            if($count <=2 ){
                $tstatus = 1;
            }
        }
        $this->assign('tstatus',$tstatus);

        //start 20151014
        $is_open=service('Mobile/SportAct')->checkPermission( array('order_id'=>$id));
        $this->assign("is_open",$is_open===true?1:0);//是否符合运动理财机会 end


        //2015双旦
        $ar_time = C('ACTIVITY.DOUBLE_FIRST_FESTIVAL');
        $start_time = strtotime($ar_time['start_time']);
        $end_time = strtotime($ar_time['end_time']);
        $time_now = time();
        if ($time_now >= $start_time && $time_now <= $end_time) {
            $chance_num = service('Activity/DoubleFirstFestival')->getLastChance($id);
        }else{
            $chance_num = 0;
        }
        $this->assign('sd1512_chance_num', $chance_num);

        $this->assign("order_id",$id);
        $bonus_money = service("Account/BonusRedis")->getMyOrderBonus($id);
        if ($bonus_money) {
            $bonus_money = humanMoney($bonus_money, 2, false);
        }
        $this->assign("bonus_money", $bonus_money);
        //是否显示自动投标
        $prj_type = $prjInfo['prj_type'];
        $is_view_auto_icon = service("Financing/Appoint")->isViewAutoIcon($prj_type,$this->loginedUserInfo['uid']);
        $is_show_security = ($prj_type === 'A' || $prj_type === 'F')?true:false;
        $this->assign("is_show_security", $is_show_security);
        $this->assign("is_view_auto_icon", $is_view_auto_icon);
        $this->display();
    }

    function buySuccess11()
    {
        $id = I("get.id", 0);
        $prjOrderObj = D("Financing/PrjOrder");
        $uid = $this->loginedUserInfo['uid'];
        $where = array(
            'id' => $id,
            'uid' => $uid,
        );
        $prjOrderInfo = $prjOrderObj->where($where)->find();
        !$prjOrderInfo && $this->error("请勿非法访问");
        $serviceAN = service('Activity/Zero');
        $result = $serviceAN->getReceiverByOrderId($id);
        if (!$result) {
            $this->error("请勿非法访问");
        }

        //原有逻辑begin
        $this->money = humanMoney($prjOrderInfo['money'], 2, FALSE);
        $prjInfo = D("Financing/Prj")->where(array("id" => $prjOrderInfo['prj_id']))->find();
        //红包奖励 利率
        $activity = service('Financing/Financing')->getActivityView($prjInfo['activity_id']);
        //排除掉投资奖励红包的
        if ($activity && $activity['reward_money_rate'] > 0 && $activity['reward_money_action'] != RewardRuleModel::ACTION_PROJECT_INVEST) {
            $reward_money_rate = $activity['reward_money_rate'];
            if ($activity['reward_money_rate_type'] == 'month') {
                $reward_money_rate = $reward_money_rate * 12;
            }
            if ($activity['reward_money_rate_type'] == 'day') {
                $reward_money_rate = $reward_money_rate * 365 / 10;
            }
        }
        $this->rewardMoneyRate = $reward_money_rate;

        $this->prjNo = $prjInfo['prj_no'];
        $this->prjTypeName = service("Financing/Project")->getPrjTypeName($prjInfo['prj_type']);

        $this->assign("order_id", $id);
        $bonus_money = service("Account/BonusRedis")->getMyOrderBonus($id);
        if ($bonus_money) {
            $bonus_money = humanMoney($bonus_money, 2, false);
        }
        $this->assign("bonus_money", $bonus_money);
        //原有逻辑end
        list($receiver_id, $gift) = $result;
        $defaultReceiver = $serviceAN->getDefaultReceiver($uid);
        /****活动分享页改成长期的页面***/
        $share_url = C('HTTP_HOST') . '/Activity/Zero/ajaxPrjList';
        $this->assign('default_receiver', $defaultReceiver);
        $this->assign('rid', $receiver_id);
        $this->assign('gift', $gift);
        $this->assign('share_url', $share_url);
        $this->display();
    }
    //检查步长
    function ajaxCheckStep()
    {
        $money = floatval(str_replace(',', '', I('post.money')));
        $prjId = I('post.id');

        $userInfo = $this->loginedUserInfo;
        $uid = $userInfo['uid'];
        $paypwd = $this->_post("paypwd");
        if(!$paypwd) ajaxReturn(0,'支付密码不能为空！',0);
        if(!service("Account/Account")->checkPayPwd($uid,$paypwd)){
            ajaxReturn(0,'支付密码不匹配！',0);
        }

        $user_account = M('user_account')->find($uid);
        $params['coupon_money'] = $user_account['coupon_money'];

        $money = $money * 100;
        $project_service = service("Financing/Project");
        $award = $project_service->getRealAward($uid, $money, $params['coupon_money'], $prjId);
        $params['award'] = $award;

        $checkReturn  = $project_service->buyCheck($money,$prjId,$uid,$params);
        if (!$checkReturn){
            if(strpos( MyError::lastError(), '余额不足') !== false){
                ajaxReturn(array("type" => 3), MyError::lastError(), 0);
            } else {
                ajaxReturn(array("type" => 1), MyError::lastError(), 0);
            }
        }

        $checkReturn = $project_service->buyAmountCheck($money, $prjId, $uid, $params);

        if (!$checkReturn)
            ajaxReturn(array("type" => 1), MyError::lastError(), 0);

        ajaxReturn(0, 0, 1);
    }

    /**
     * 投资意向
     */
    function buyIntention(){
        $prjId = (int) $this->_post('prjid');
        $money = $this->_post("money");
        $obj = service("Financing/Project");
        $info = $obj->getDataById($prjId);
        if(!$info){
            ajaxReturn(0,'异常，项目不存在！',0);
        }

        $money = $money*100;
        $uid = $this->loginedUserInfo['uid'];

        $result = $obj->buyIntention($money,$uid,$prjId);
        if(!$result){
            ajaxReturn(0,MyError::lastError(),0);
        }

        //多少人有认购意向
        $count = $obj->buyIntentionCount($prjId);

        ajaxReturn(0,$count,1);
    }

    /**
     * 购买记录
     */
    function ajaxBuyLog(){
        openMasterSlave();
        import_app("Financing.Model.PrjOrderModel");

        $prjId = (int) $this->_request('id');

        $page = (int) $this->_request("p");
        $page = $page ? $page :1;
        $pageSize = 10;
        $prjInfo = service("Financing/Project")->getDataById($prjId);
        $info=service("Financing/Project")->getPrjInfo($prjId);
        $prjInfo['is_jjs']=$info['act_prj_ext']['is_jjs'];
        $this->prjInfo = $prjInfo;
        //待开标状态
        if($prjInfo['bid_status'] == PrjModel::BSTATUS_WATING){
            $this->assign("list",array());
            //融资规模
            $demandAmount = humanMoney($prjInfo['demand_amount'],2);
            $this->assign("demandAmount",$demandAmount);
            //剩余金额
            $remainingAmount = humanMoney($prjInfo['demand_amount'],2,false)."元";
            $this->assign("remainingAmount",$remainingAmount);
        }else{
            $obj = service("Financing/Project");
            $where['prj_id'] = $prjId;
            $where['status'] = array("NEQ",PrjOrderModel::STATUS_NOT_PAY);
            $where['is_regist'] = 0;
//            $where['from_order_id'] = array("EXP","IS NULL");

            $result = $obj->getBuyLog($where,array("ctime DESC"),$page,$pageSize);
            $totalRow = $result['total_row'];
            $list = $result['data'];
            //分页
            $paging  = W("Paging",array("totalRows"=>$totalRow,"pageSize"=>$pageSize,"parameter"=>array("id"=>$prjId)),true);
            $this->assign("paging",$paging);
            $this->assign("totalRow",$totalRow);
            $this->assign("list",$list);
            //融资规模
            $demandAmount = humanMoney($prjInfo['demand_amount'],2);
            $this->assign("demandAmount",$demandAmount);
            //剩余金额
            $remainingAmount = humanMoney($prjInfo['remaining_amount'],2,false)."元";
            $this->assign("remainingAmount",$remainingAmount);
        }
        $this->display();
    }

    //项目的产品说明
    function ajaxProDetail(){
        $this -> display();
    }


    //项目基本信息
    function ajaxBaseInfo(){
        $prjbfcache = \Addons\Libs\Cache\Redis::getInstance();
        openMasterSlave();
        $prjId = (int) $this->_request('id');

        $prjbfkey = 'FinancingInvest_ajaxBaseInfo_prjinfo_'.$prjId;
        $prjInfocache = $prjbfcache->get($prjbfkey);
        if ($prjInfocache/* && C('APP_STATUS') == 'product'*/) {
            $prjInfo = json_decode($prjInfocache,true);
        }else{
            $prjInfo = service("Financing/Project")->getPrjInfo($prjId);
            $prjbfcache->setex($prjbfkey,60,json_encode($prjInfo));
        }

        if(!$prjInfo){
            return false;
        }
        //车辆信息
        $car_fields = array(
            'plate_number',
            'car_model',
            'register_date',
            'frame_number',
            'engine_number',
            'invoice_price',
            'mileage',
            'money_using', //借款用途
            'push_shop',
            'assess_price' //评估价
        );
        //借款个人信息
        $person_fields = array(
            'cust_name',
            'gender',
            'age',
            'person_no',
            'native_place',
            'marriage',
            'mobile',
            'emergency_person',
            'emergency_mobile'
        );
        //车辆审核记录
        $audit_fields = array(
            'court_record',
            'net_check',
            'tel_check',
            'face_check',
            'car_check',
            'fingerprint',
            'gps',
            'bank_credit'

        );

        //如果是车贷项目，执行车贷的模板
        if($prjInfo['business_type']==1){
            $carInfo = M('car_info')->field($car_fields)->where("prj_id=".$prjId)->find();
            $personInfo = M('car_person')->field($person_fields)->where("prj_id=".$prjId)->find();
            $auditInfo = M('car_loan')->field($audit_fields)->where("prj_id=".$prjId)->find();
            $this->assign("car_loan",true);
            $this->assign("carInfo",$carInfo);
            $this->assign("personInfo",$personInfo);
            $this->assign("auditInfo",$auditInfo);
            $this->assign("prjInfo",$prjInfo);

            //保理公司信息
            $baoli = M("invest_guarantor")
                ->field("fi_invest_guarantor.*")
                ->join("fi_guarantor_user ON fi_guarantor_user.guarantor_id = fi_invest_guarantor.id")
                ->where("fi_guarantor_user.uid = ".$prjInfo['uid'])
                ->find();
            if($baoli){
                $this->assign("baoli",$baoli);
            }
            $this->display();
            exit;
        }
        //如果是鑫银票业务
        if($prjInfo['business_type']==2){
            $this->assign("bill",true);
            $this->display();
            exit;
        }

        //速兑通直接退出，另一个方法处理 add by 001181
        if($prjInfo['prj_type'] == PrjModel::PRJ_TYPE_H){
            $res = service("Financing/Invest")->getBaseInfoSDT($prjInfo);
            //todo 错误处理
            $this->assign("prjInfo",$res['p_prj_info']);
            $this->assign("subInfo",$res['sub_prj_info']);
            $this->assign("userInfo",$res['p_prj_userinfo']);
            $this->display();
            exit;
        }

        $gtkey = 'FinancingInvest_ajaxBaseInfo_guarantor_'.$prjInfo['guarantor_id'];
        $guarantorcache = $prjbfcache->get($gtkey);
        if ($guarantorcache/* && C('APP_STATUS') == 'product'*/) {
            $guarantor = json_decode($guarantorcache,true);
        }else{
            $guarantor = M("invest_guarantor")->where(array("id"=>$prjInfo['guarantor_id']))->find();
            $prjbfcache->setex($gtkey,600,json_encode($guarantor));
        }

        if($prjInfo['prj_business_type'] == prjModel::PRJ_BUSINESS_TYPE_G){//央企融类型的担保人固定显示
            $this->guarantorName = '接受央企商票质押的第三方公司，具体名称见合同。目的是合法代持央企商票的质押权。';
        }else {
            $this->guarantorName = $guarantor['title'];
        }

        if($prjInfo['transfer_id']){
            $prjId = M("asset_transfer")->where(array("id"=>$prjInfo['transfer_id']))->getField("prj_id");
            $prjInfo = service("Financing/Project")->getPrjInfo($prjId);
        }
        $isZhr = $prjInfo['zhr_apply_id'] ? 1:0;
        if($isZhr){
//            if($prjInfo['prj_business_type'] == prjModel::PRJ_BUSINESS_TYPE_G){//央企融类型的不显示担保人描述
//                $this->applyInfo = '';
//            }else {
//                $this->applyInfo = D("Zhr/FinanceApply")->getById($prjInfo['zhr_apply_id']);
//            }
            $this->applyInfo = D("Zhr/FinanceApply")->getById($prjInfo['zhr_apply_id']);
            $this->dbstat = D("Zhr/FinanceApply")->getDbStat($prjInfo['guarantor_id']);
            $this->corpStat = D("Zhr/FinanceApply")->corpStat($this->applyInfo['borrower_id']);
            $this->corpInfo = D("Zhr/FinanceApply")->getFinanceObj($this->applyInfo['uid'])->getById($this->applyInfo['borrower_id']);
            //详情页需要展示的信息
            $borrower_show_items = service('Financing/Prj')->getBorrowerShowItems($prjInfo, $this->corpInfo);
        }

        $isNewJuyou=0;
        if($prjInfo['prj_type'] == PrjModel::PRJ_TYPE_F){
            if($prjInfo['ext']['custodian'] && $prjInfo['ext']['custodian_url'] && $prjInfo['ext']['announcement_url']){
                $isNewJuyou=1;
            }
        }
        $this->isNewJuyou = $isNewJuyou;
        import_app('Financing.Model.PrjModel');
        $this->assign("prjInfo",$prjInfo);
        if(!$isZhr){
            if($prjInfo['borrower_type'] == PrjModel::BORROWER_TYPE_COMPANY){
                //企业借款人信息
                $cache = S("ajaxBaseInfo_corpInfo".$prjInfo['id']);
                if($cache){
                    $corpInfo = $cache;
                }else{
                    $corpInfo = M("prj_corp")->where("prj_id = ".$prjId)->find();
                    S("ajaxBaseInfo_corpInfo".$prjInfo['id'], $corpInfo);
                }
                $borrower_info = $corpInfo;
            }else{
                //个人借款人信息
                $cache = S("ajaxBaseInfo_personInfo".$prjInfo['id']);
                if($cache){
                    $personInfo = $cache;
                }else{
                    $personInfo = M("prj_person")->where("prj_id = ".$prjId)->find();
                    S("ajaxBaseInfo_personInfo".$prjInfo['id'], $personInfo);
                }
                $borrower_info = $personInfo;
            }

            //鑫利宝
            if($prjInfo['act_prj_ext']['is_xlb'] == 1){
/*                $cache = S("ajaxBaseInfo_personInfo".$prjInfo['id']);
                if($cache){
                    $personInfo = $cache;
                }else{*/
                    $open_info = M("xlb_join_user")->where(array("financier_uid" => $prjInfo['uid']))->order('ctime desc')->find();
                    $user_info = M("user")->where(array('uid'=>$open_info['investor_uid']))->find();
                    $len = mb_strlen($user_info['real_name'],'utf-8');
                    $real_name = $len > 2 ? mb_substr($user_info['real_name'],0,1,'utf-8').'**' : mb_substr($user_info['real_name'],0,2,'utf-8').'*';
                    $personInfo = array(
                        array('k'=>'姓名','v'=>empty($user_info['real_name']) ? '' : $real_name),
                        array('k'=>'身份证','v'=>empty($user_info['person_id']) ? '' : '已核实'),
                    );
                    if($user_info['person_id']){
                        $invest_service = service('Mobile2/Invest');
                        $personInfo[] = array('k'=>'年龄','v'=>$invest_service->getAgeByCardId($user_info['person_id']));
                        $personInfo[] = array('k'=>'性别','v'=>$invest_service->getSexFromCard($user_info['person_id']));
                        import_addon("libs.IDCard");
                        if(IDCard::getDistrict($user_info['person_id'])){
                            $personInfo[] = array('k'=>'地域','v'=>IDCard::getDistrict($user_info['person_id']));
                        }
                    }
/*                    S("ajaxBaseInfo_personInfo".$prjInfo['id'], $personInfo);
                }*/
                $borrower_info = $personInfo;
            }

            $this->assign("borrower", $borrower_info);
            //保理公司信息
            $baolikey = 'FinancingInvest_ajaxBaseInfo_baoli_'.$prjInfo['uid'];
            $baolicache = $prjbfcache->get($baolikey);
            if ($baolicache/* && C('APP_STATUS') == 'product'*/) {
                $baoli = json_decode($baolicache,true);
            }else{
                $baoli = M("invest_guarantor")
                    ->field("fi_invest_guarantor.*")
                    ->join("fi_guarantor_user ON fi_guarantor_user.guarantor_id = fi_invest_guarantor.id")
                    ->where("fi_guarantor_user.uid = ".$prjInfo['uid'])
                    ->find();
                $prjbfcache->setex($baolikey,600,json_encode($baoli));
            }

            if($baoli){
                $this->assign("baoli",$baoli);
            }
            //详情页需要展示的信息
            $borrower_show_items = service('Financing/Prj')->getBorrowerShowItems($prjInfo, $borrower_info);
        }

        $imgDatakey = 'FinancingInvest_ajaxBaseInfo_imgData_'.$prjId;
        $imgDatacache = $prjbfcache->get($imgDatakey);
        if ($imgDatacache/* && C('APP_STATUS') == 'product'*/) {
            $imgData = json_decode($imgDatacache,true);
        }else{
            $imgData = service("Application/ProjectAudit")->getAuditImgData($prjId);
            $prjbfcache->setex($imgDatakey,600,json_encode($imgData));
        }
        $this->assign("imgData",$imgData);
        // 担保函
        $this->danbaohan($prjId);
        $this->assign('borrower_show_items', $borrower_show_items);
        $this->display();
    }

    /**
     * 项目详情-安全保障
     */
    public function safetyControl()
    {
        openMasterSlave();
        $prj_id = (int) I('get.prj_id');
        $prj_info = service('Financing/Project')->getDataById($prj_id);
        $prj_ext_info = M('prj_ext')->where(array('prj_id' => $prj_id))->field('prj_manager_no, guarantee_desc, risk_audit_id,quota_control,capital_control,is_xlb,is_guarantee,is_jjs')->find();

        //风险审核
        $risk_audit = D('PrjRiskAudit')->getPrjRiskAudit($prj_ext_info['risk_audit_id'], $prj_info);
        //鑫利宝显示文字
        $this->assign('xlb_risk_audit', array(self::RISK_MSG_ONE,self::RISK_MSG_TWO));
        $this->assign('xlb_diya_msg', self::DIYA_TITLE);
        $this->assign('is_xlb', $prj_ext_info['is_xlb']);
        $this->assign('is_jjs', $prj_ext_info['is_jjs']);

        //担保措施
        $guarantee_measure = service('Financing/Prj')->getPrjGuaranteeMeasure($prj_id, $prj_info);

        //担保公司
        D('Financing/Prj');
        if (!in_array($prj_info['prj_business_type'], array(PrjModel::PRJ_BUSINESS_TYPE_H, PrjModel::PRJ_BUSINESS_TYPE_G, PrjModel::PRJ_BUSINESS_TYPE_B, PrjModel::PRJ_BUSINESS_TYPE_I, PrjModel::PRJ_BUSINESS_TYPE_F)) && $prj_info['guarantor_id']) {
            $guarantor = M('invest_guarantor')->where(array('id' => $prj_info['guarantor_id']))->field('title, title_short, desr_url')->find();
        }elseif(in_array($prj_info['prj_business_type'], array(PrjModel::PRJ_BUSINESS_TYPE_F, PrjModel::PRJ_BUSINESS_TYPE_B, PrjModel::PRJ_BUSINESS_TYPE_I))){
            $guarantor['title'] = '多重安全保障服务';
            $guarantor['is_no_guarantee'] = 1;
        }
        else {
            //央企融类型担保人为空只显示央企融固定的担保措施
            $guarantor = [];
        }

        //资金控制和额度控制
        if ($prj_ext_info['quota_control']) {
            $prj_ext_info['quota_control'] = explode("\n", strip_tags(str_replace(PHP_EOL, "\n", trim($prj_ext_info['quota_control'], PHP_EOL))));
            $prj_ext_info['quota_control'] = array_filter($prj_ext_info['quota_control']);
        }
        if ($prj_ext_info['capital_control']) {
            $prj_ext_info['capital_control'] = explode("\n", strip_tags(str_replace(PHP_EOL, "\n", trim($prj_ext_info['capital_control'], PHP_EOL))));
            $prj_ext_info['capital_control'] = array_filter($prj_ext_info['capital_control']);
        }

        $this->assign('prj_info', $prj_info);
        $this->assign('risk_audit', $risk_audit);
        $this->assign('guarantee_measure', $guarantee_measure);
        $this->assign('guarantor', $guarantor);
        $this->assign('prj_manager_no', $prj_ext_info['prj_manager_no']);
        $this->assign('quota_control', $prj_ext_info['quota_control']);
        $this->assign('capital_control', $prj_ext_info['capital_control']);
        $this->assign('is_guarantee', $prj_ext_info['is_guarantee']);
        $this->display('ajaxSecurity');
    }



    //还款计划
    function ajaxRepayPlan(){
        openMasterSlave();
        D("Financing/Prj");
        $prjId = (int)$this->_request('id');
        $prjInfo = service("Financing/Project")->getDataById($prjId);
        if(!$prjInfo) {
            return false;
        }
        $isFed = 0;
        if($prjInfo['transfer_id']) {
            $transferInfo = M("asset_transfer")->find($prjInfo['transfer_id']);
            $parentOrderId = service("Financing/Project")->getParentOrderId($transferInfo['from_order_id']);
            $prjOrderInfo = M("prj_order")->find($parentOrderId);
            $demand_amountTmp = $prjOrderInfo['money'];
            $demand_amount = $transferInfo['money'];
            $this->timeLimit = service("Financing/Project")->parseExpireTime($prjInfo['transfer_id']);
            $isFed = 1;
            $prjId = $prjInfo['p_prj_id'];
            $prjInfo = service("Financing/Project")->getDataById($prjId);
        } else {
            $demand_amount = $prjInfo['demand_amount'];
            $demand_amountTmp = $prjInfo['demand_amount'];
        }
        if ($prjInfo['prj_type'] == PrjModel::PRJ_TYPE_H) {

            if ($prjInfo['bid_status'] != PrjModel::BSTATUS_BIDING) {
                $start_time = $prjInfo['ctime'];
            } else {
                $start_time = time();
            }
            $time_limit = service("Financing/FastCash")->showTimeLimitSdt($start_time - 24 * 3600,
                $prjInfo['last_repay_date'], false);
            $prjInfo['time_limit_unit_name'] = $time_limit;
            $prjInfo['time_limit'] = $time_limit;
        } else {
            $prjInfo['time_limit_unit_name'] = service("Financing/Project")->getTimeLimitUnit($prjInfo['time_limit_unit']);
            $prjInfo['time_limit_unit_view'] = $prjInfo['time_limit_unit_name'] == "月" ? "个月" : $prjInfo['time_limit_unit_name'];
        }

        $this->prjInfo = $prjInfo;

        if(service("Financing/Invest")->isEndDate($prjInfo['repay_way'])) {
            return false;
        }

        $obj = service("Financing/Project")->getTradeObj($prjId, "Bid");
        if(!$obj) return false;

        $t = strtotime(date('Y-m-d', $prjInfo['end_bid_time']));
        $balanceTime = service("Financing/Project")->getBalanceTime($prjInfo['end_bid_time'], $prjInfo['id']);
        if($prjInfo['end_bid_time'] > $balanceTime) $t = strtotime("+1 days", $t);//T向后延迟一天


        $params = array();
        $params["freeze_date"] = date('Y-m-d', service("Financing/Project")->getPrjValueDate($prjId, $t));
        $params['capital'] = $demand_amountTmp;
        $params['prj_id'] = $prjId;
        $this->demand_amount_name = humanMoney($demand_amount, 2, false);
        //      print_r($params);

        $totalProfit = 0;//总共的利息
        $perMonth = 0;//每月收款
        $totalMoney = 0; //总共本息
        $totalPrincipal = 0;//总共本金
        $this->assign("prj_type",$prjInfo['prj_type']);
        import_app("Financing.Model.PrjModel");
        $this->isEnd = $prjInfo['bid_status'] > PrjModel::BSTATUS_BIDING;
        $this->isFull = $prjInfo['bid_status'] == PrjModel::BSTATUS_FULL;
        if($prjInfo['bid_status'] == PrjModel::BSTATUS_REPAID || $prjInfo['bid_status'] == PrjModel::BSTATUS_REPAY_IN || $prjInfo['bid_status'] == PrjModel::BSTATUS_REPAYING) {
            $ret = D("Financing/PrjOrderRepayPlan")->getRepayPlanAfter($prjId);
            unset($ret['list'][2]);
            $data = $ret['list'];
            $totalProfit = $ret['totalProfit'];
            $totalMoney = $ret['totalMoney'];
            $totalPrincipal = $ret['totalPrincipal'];
        } else {
            if ($prjInfo['prj_type'] == PrjModel::PRJ_TYPE_H) {
                $params["freeze_date"] = date("Y-m-d");
            }
            $tableResult = $obj->getPersonRepaymentPlan($params);
            $data = array();
            foreach ($tableResult as $k => $v) {
                if((strtotime($v['repay_date']) < strtotime(date('Y-m-d'))) && $isFed) {
                    unset($tableResult[$k]);
                    continue;
                }
                if($perMonth == 0) $perMonth = $v['repay_money'];
                $totalProfit += $v['profit'];
                $totalMoney += $v['repay_money'];
                $totalPrincipal += $v['capital'];
                $temp = array();
                //还款日
                $temp['repay_date'] = $v['repay_date'];
                $temp['no'] = $k + 1;
                //本息
                $temp['repay_money_view'] = humanMoney($v['repay_money'], 2, false);
                //本金
                $temp['capital_view'] = humanMoney($v['capital'], 2, false);
                //利息
                $temp['profit_view'] = humanMoney($v['profit'], 2, false);
                //剩余本金
                $temp['last_capital_view'] = humanMoney($v['last_capital'], 2, false);
                $data[1][] = $temp;
            }
        }
        //        p($data);
        $this->tableResult = $data;
        if(!$isFed) {
            $this->bidInfo = service("Financing/Invest")->getBidTimeInfo($prjId);
            //应收利息
            if($prjInfo['bid_status'] == PrjModel::BSTATUS_END || $prjInfo['bid_status'] == PrjModel::BSTATUS_FULL) {
                //募集期利息
                $totalMoney += $this->bidInfo['income'];
                $totalProfit += $this->bidInfo['income'];
            }
            $this->demand_amount_name = $this->bidInfo['amountView'];
        }
        //总共的利息
        $this->totalProfit = humanMoney($totalProfit, 2, false);
        $this->perMonth = humanMoney($perMonth, 2, false);
        $this->totalMoney = humanMoney($totalMoney, 2, false);
        $this->total_principal = humanMoney($totalPrincipal, 2, false);//应收本金
        $this->isFed = $isFed;


        //      if($prjInfo['repay_way'] == PrjModel::REPAY_WAY_D){
        //            $tplpath = "ajaxrepayplan_d";
        //        }else{
        //          $tplpath = "ajaxrepayplan";
        //      }
        if(service('Financing/Project')->isPrjCanEarlyClose($prjId)) {
            $this->assign('notice_addon', '<br>本项目募集期和用款期不固定，请关注我们的公告，您的收益以实际收益为准');
        }
        $info = service("Financing/Invest")->getPrjView($prjId);
        $this->assign('is_early_close',$info['act_prj_ext']['is_early_close']);
        $this->assign('is_extend',$info['act_prj_ext']['is_extend']);
        if($info['act_prj_ext']['is_extend']){
            if($info['time_limit_extend']['time_limit_extend'] && $info['time_limit_extend']['time_limit_extend_unit']){
                $this->assign('is_extend',1);
                $this->assign('extend_days',$info['time_limit_extend']['time_limit_extend'].$info['time_limit_extend']['time_limit_extend_unit']);
            }else{
                $this->assign('is_extend',0);
            }
        }else{
            $this->assign('is_extend',0);
        }
        if($info['act_prj_ext']['early_repay_days'] > 0){
            $this->assign('is_earlyRepay',1);
            $this->assign('earlyRepay_days',$info['act_prj_ext']['early_repay_days']);
        }else{
            $this->assign('is_earlyRepay',0);
        }

        $this->display("ajaxrepayplan");
    }


    //贷后调查
    function ajaxSurveyList(){
        $prjId = (int) $this->_request('id');
        $prjInfo = service("Financing/Project")->getPrjInfo($prjId);
        if(!$prjInfo){
            $list = array();
        }else{
            $ubspPrjId = 412133;//$prjInfo['ubsp_prj_id'];
            $ubspNo = 'P-20130722000010';//$prjInfo['ubsp_prj_no'];
            $list = service('Financing/Financing')->getYrzdbSurvey($ubspPrjId,$ubspNo);
        }
//         $list = array();
        $this->assign("list",$list);
        $this->display();
    }

    //历史融资记录
    function ajaxHistoryList(){
        $prjId = (int) $this->_request('id');
        $prjInfo = service("Financing/Project")->getPrjInfo($prjId);
        if(!$prjInfo){
            $this->assign("pageStart",0);
            $this->assign("list",array());
            $this->assign("result",array());
            $this->assign("totalRow",0);
            $this->display('ajaxhistorylist');
            return false;
        }
        $page = (int)$this->_request("p");
        $page = $page ? $page :1;
        $pageSize = 10;

        $custId = $prjInfo['ubsp_cust_id'];

        $result = service("Financing/Financing")->listCustFinanceInfo($custId,$page,$pageSize);
//        print_r($result);
        if(!$result){
            $this->assign("pageStart",0);
            $this->assign("list",array());
            $this->assign("result",array());
            $this->assign("totalRow",0);
            $this->display('ajaxhistorylist');
            return false;
        }

        //分页
        $parameter['id'] = $prjId;

        $totalRow = $result['page']['totalRows'];

        $paging  = W("Paging",array("totalRows"=>$totalRow,"pageSize"=>$pageSize,"parameter"=>$parameter),true);
        $this->assign("paging",$paging);

        $pageStart = ($page-1)*$pageSize;

//        $result = array();
        $this->assign("pageStart",$pageStart);
        $this->assign("list",$result['list']);
        $this->assign("result",$result);
        $this->assign("totalRow",$totalRow);

        $this->display('ajaxhistorylist');
    }


    function calculator()
    {
        $type = I('request.t');
        $time_limit = (int)I('request.time_limit');
        $time_limit_unit = I('request.time_limit_unit', 'day');
        $rate = I('request.rate');
        $rate_type = I('request.rate_type', 'year');
        $money = I('request.money') * 100;
        $value_date = I('request.date', date('Y-m-d')); // 起息日

        $value_date = date('Y-m-d', strtotime($value_date));

        // 到期还本付息
        if ($type == 'E') {
            if ($rate_type != 'day') {
                $rate = $rate * 10;
            }

            if ($time_limit_unit == 'day' && $time_limit > 3650
                || $time_limit_unit == 'month' && $time_limit > 120
                || $time_limit_unit == 'year' && $time_limit > 10
            ) {
                ajaxReturn(0, '时长不能超过10年', 0);
            }

            $ctime = strtotime(date('Y-m-d'));
            $end_bid_time = service('Financing/Project')->formatUnitTime($time_limit, $time_limit_unit, $ctime);
            $start_time = strtotime('+1 days ', $ctime);
            $end_time = strtotime('+1 days ', $end_bid_time);

            $income = service('Financing/Project')->profitComputeByDate($rate_type, $rate, $start_time, $end_time, $money);

            $total = humanMoney(($income + $money), 2, false);
            $income = humanMoney($income, 2, false);

            ajaxReturn(array(
                'total' => $total,
                'income' => $income,
            ));
        } elseif ($type == 'permonth') { // 每月等额本息
            $rate = $rate / 100;
            $rate_type = 'year';
            $limit_type = 'month';
            if ($time_limit > 120) {
                ajaxReturn(0, '时长不能超过10年', 0);
            }

            import('libs.RepayPlan.AvgCapInter', ADDON_PATH);
            $avgCapInter = new AvgCapInter();
            $list = $avgCapInter->getRepayTable($value_date, $money, $rate, $rate_type, $time_limit, $limit_type);

            $total = 0; // 总本息
            $total_income = 0; // 总利息
            $total_principal = 0; // 总本金
            $permonth = 0; // 每月收款
            if ($list) {
                foreach ($list as $k => $v) {
                    if (!$permonth) {
                        $permonth = $v['repay_money'];
                    }
                    $total += $v['repay_money'];
                    $total_income += $v['profit'];
                    $total_principal += $v['capital'];

                    $list[$k]['repay_money_view'] = humanMoney($v['repay_money'], 2, false); // 本息
                    $list[$k]['profit_view'] = humanMoney($v['profit'], 2, false); // 利息
                    $list[$k]['capital_view'] = humanMoney($v['capital'], 2, false); // 本金
                    $list[$k]['last_capital_view'] = humanMoney($v['last_capital'], 2, false); // 剩余本金
                }
            }

            $total = humanMoney($total, 2, false);
            $totalIncome = humanMoney($total_income, 2, false);
            $totalPrincipal = humanMoney($total_principal, 2, false);
            $perMonth = humanMoney($permonth, 2, false);
            ajaxReturn(array(
                'list' => $list,
                'total' => $total,
                'income' => $totalIncome,
                'totalPrincipal' => $totalPrincipal,
                'perMonth' => $perMonth
            ));
        } elseif ($type == 'D') { // 按月付息，到期还本
            $rate = $rate / 100;
            $rate_type = 'year';
            $limit_type = 'month';
            if ($time_limit > 120) {
                ajaxReturn(0, '时长不能超过10年', 0);
            }

            import('libs.RepayPlan.MonthlyPay', ADDON_PATH);
            $obj = new MonthlyPay();
            $list = $obj->getRepayTable($value_date, $money, $rate, $rate_type, $time_limit, $limit_type);

            $total = 0; // 本息总共
            $total_income = 0; // 利息总共
            $total_principal = 0; // 本金总共
            if ($list) {
                foreach ($list as $k => $v) {
                    $total += $v['repay_money'];
                    $total_income += $v['profit'];
                    $total_principal += $v['capital'];

                    $list[$k]['repay_money_view'] = humanMoney($v['repay_money'], 2, false); // 本息
                    $list[$k]['profit_view'] = humanMoney($v['profit'], 2, false); // 利息
                    $list[$k]['capital_view'] = humanMoney($v['capital'], 2, false); // 本金
                    $list[$k]['last_capital_view'] = humanMoney($v['last_capital'], 2, false); // 剩余本金
                }
            }

            $total = humanMoney($total, 2, false);
            $totalIncome = humanMoney($total_income, 2, false);
            $totalPrincipal = humanMoney($total_principal, 2, false);
            ajaxReturn(array(
                'list' => $list,
                'total' => $total,
                'income' => $totalIncome,
                'totalPrincipal' => $totalPrincipal
            ));
        }
        elseif ($type == 'PermonthFixN') { // 每月等额本息(固定还款日)
            $rate = $rate / 100;
            $rate_type = 'year';
            $limit_type = 'month';
            if ($time_limit > 120) {
                ajaxReturn(0, '时长不能超过10年', 0);
            }

            import('libs.RepayPlan.AvgCapInterFixN', ADDON_PATH);
            $obj = new AvgCapInterFixN();
            $list = $obj->getRepayTable($money, $rate, $rate_type, $time_limit, $limit_type, 21, $value_date);

            $total = 0; // 本息总共
            $total_income = 0; // 利息总共
            $total_principal = 0; // 本金总共
            if ($list) {
                foreach ($list as $k => $v) {
                    $total += $v['repay_money'];
                    $total_income += $v['profit'];
                    $total_principal += $v['capital'];

                    $list[$k]['repay_money_view'] = humanMoney($v['repay_money'], 2, false); // 本息
                    $list[$k]['profit_view'] = humanMoney($v['profit'], 2, false); // 利息
                    $list[$k]['capital_view'] = humanMoney($v['capital'], 2, false); // 本金
                    $list[$k]['last_capital_view'] = humanMoney($v['last_capital'], 2, false); // 剩余本金
                }
            }

            $total = humanMoney($total, 2, false);
            $totalIncome = humanMoney($total_income, 2, false);
            $totalPrincipal = humanMoney($total_principal, 2, false);
            ajaxReturn(array(
                'list' => $list,
                'total' => $total,
                'income' => $totalIncome,
                'totalPrincipal' => $totalPrincipal
            ));
        }
        elseif ($type == 'halfyear') { // 半年付息
            $rate = $rate / 100;
            $rate_type = 'year';
            $limit_type = 'month';

            if ($time_limit <= 6) {
                ajaxReturn(0, '期限必须大于6个月', 0);
            }
            if ($time_limit > 120) {
                ajaxReturn(0, '时长不能超过10年', 0);
            }

            import('libs.RepayPlan.HalfYear', ADDON_PATH);
            $obj = new HalfYear();
            $list = $obj->getRepayTable($value_date, $money, $rate, $rate_type, $time_limit, $limit_type);

            $total = 0; // 本息总共
            $total_income = 0; // 利息总共
            $total_principal = 0; // 本金总共
            if ($list) {
                foreach ($list as $k => $v) {
                    $total += $v['repay_money'];
                    $total_income += $v['profit'];
                    $total_principal += $v['capital'];

                    $list[$k]['repay_money_view'] = humanMoney($v['repay_money'], 2, false); // 本息
                    $list[$k]['profit_view'] = humanMoney($v['profit'], 2, false); // 利息
                    $list[$k]['capital_view'] = humanMoney($v['capital'], 2, false); // 本金
                    $list[$k]['last_capital_view'] = humanMoney($v['last_capital'], 2, false); // 剩余本金
                }
            }

            $total = humanMoney($total, 2, false);
            $totalIncome = humanMoney($total_income, 2, false);
            $totalPrincipal = humanMoney($total_principal, 2, false);
            ajaxReturn(array(
                'list' => $list,
                'total' => $total,
                'income' => $totalIncome,
                'totalPrincipal' => $totalPrincipal
            ));
        }
    }

    //企业信息
    function ajaxCorpInfo(){
        $id = (int)I("request.corp_id");
        $apiService = service("Financing/YrzdbApi");
//        $apiService->setDebug();
        //企业基本信息
        $this->corpBaseInfo = $apiService->corpBaseInfo($id);
        //产品服务:销售构成，渠道
        $corpMainSale = $apiService->corpMainProductSale($id);
        $this->corpMainSale = $corpMainSale['data']['corp_main_product_sale'];
        $corpChannel = $apiService->corpProductChannel($id);
        $this->corpChannel = $corpChannel['data']['corp_product_channel'];
        //运营状况：运营评价，纳税情况
        $corpOperation = $apiService->yunyingZhuangkuangPingjia($id);
        $this->corpOperation = $corpOperation['data']['yunying_zhuangkuang_pingjia'];
        $corpTaxAffairs = $apiService->corpTaxScale($id);
        $this->corpTaxAffairs = $corpTaxAffairs['data']['corp_tax_scale'];
        //固定资产
        $corpAssets = $apiService->corpGudingZichan($id);
        $this->corpAssets = $corpAssets['data']['corp_guding_zichan'];
        //应付款
        $corpNeedPay = $apiService->corpYingfu($id);
        $this->corpNeedPay = $corpNeedPay['data']['corp_yingfu'];
        // //应收款
        $corpNeedGain = $apiService->corpYingshou($id);
        $this->corpNeedGain = $corpNeedGain['data']['corp_yingshou'];
        // //财务数据-运营状况分析
        $corpOperAnalyse = $apiService->yunyingCaiwu($id);
        $this->corpOperAnalyse = $corpOperAnalyse['data']['yunying_caiwu'];
        // //财务数据-财务数据分析
        $corpDataAnalyse = $apiService->corpZhuyaoCaiwu($id);
        $this->corpDataAnalyse = $corpDataAnalyse['data']['corp_zhuyao_caiwu'];

        //重大事项-银行借款明细
        $bankLoan = $apiService->corpBankLoan($id);
        $this->bankLoan = $bankLoan['data']['corp_bank_loan'];

        //重大事项-其他融资明细
        $qitaRongzi = $apiService->corpQitaRongzi($id);
        $this->qitaRongzi = $qitaRongzi['data']['corp_qita_rongzi'];

        //重大事项-对外担保明细
        $duiwaiDanbao = $apiService->corpDuiwaiDanbao($id);
        $this->duiwaiDanbao = $duiwaiDanbao['data']['corp_duiwai_danbao'];

        $this->display();
    }

    //获取企业调查坐标
    function ajaxSignInfo(){
        $id = (int)I("request.corp_id");
        if($id){
            $this->Sign = service("Financing/YrzdbApi")->getCorpAllSign($id);
        }
        $this->display();
    }

    function ajaxInvestSign(){
        $id = I("request.sign_id");
        $sign = service("Financing/YrzdbApi")->getInvestSign($id);
        ajaxReturn($sign);
    }

    //速兑通，详情页
    function fastCash(){
        openMasterSlave();
        $id =  $this->_get("id");
        $this->hashId = $id;
        $id = $prj_id = service("Financing/Project")->parseIdView($id);
        $this->id = $this->prj_id = $id;
        $user = $this->loginedUserInfo;

        try{
            $is_exist = D("Financing/Prj")->where(array('id' => $id))->count();
            if (!$is_exist) {
                throw_exception("项目不存在,请确认");
            }
            $info = service("Financing/Invest")->getPrjView($id);
        }catch (Exception $e){
            $this->error($e->getMessage());
        }
        if ($info['prj_type'] != PrjModel::PRJ_TYPE_H) {
            $this->error("该项目不是速兑通项目!");
        }
        //渠道客户端(和父类的clientType一致)
        $parent_id = D("Financing/Prj")->getParentPrjId($info['id']);
        $db_client_type = D("Financing/PrjFilter")->getClientType($parent_id);
        $client_permission = true;
        if( !in_array($db_client_type, array(0,self::CLIENT_TYPE) ) ){
            $client_permission = false;    //非专享客户端，按钮灰色
        }
        $this->assign("client_permission",$client_permission);

        $client_type_config  = D("Financing/PrjFilter")->client_type_config;
        $this->assign("client_type_config",$client_type_config);
        $this->assign("client_type",$db_client_type);
        $srvFinancing = service('Financing/Financing');
        //默认最优化的奖励金额
        $defaultReward = service("Financing/BaseInvestRule")->getAllBonusAmount($user['uid'], $prj_id,0,1,0);
        //最大投資金額
        $max_invest_money = $srvFinancing->getMaxInvestMoney($user['uid'], $id, $info,0,-1,$defaultReward);
        //选中最大投资金额后获取对应的奖励
        $defaultReward = service('Financing/ReduceTicket')->getDefaultReward($user['uid'], $this->id,
            $info['time_limit_day'], $max_invest_money, 0);
        $this->assign("money_notEnough", $srvFinancing->isBlanceLess($user['uid'], $id, FALSE, $info,-1,-1,$defaultReward['BestAmount']*100));
        $this->assign('reward_info',$defaultReward);
        $this->assign("max_invest_money_view", $max_invest_money/100);
        if($max_invest_money) $max_invest_money = humanMoney($max_invest_money, 2, FALSE);
        $this->assign("max_invest_money", $max_invest_money);

        service("Account/Active")->clickBidLog("prjid_".$id,0);
        $c = service("Financing/Project")->getPrjTypeInt($info['prj_type']);
        $this->assign("c",$c);

        $userBase = service("Payment/PayAccount")->getBaseInfo($user['uid']);

        $islogin = $userBase ? 1:0;
        $this->assign("user",$user);
        $this->assign("is_login",$islogin);

        $url1 = getCurrentUrl();
        $url = urlencode($url1);
        $this->assign("url",$url);
        $url2 = get_url().$url1;
        $this->assign("url2",$url2);

        $protocolInfo['name'] = "借款合同";
        $this->protocolInfo = $protocolInfo;
        $this->assign("protocolInfo",$protocolInfo);

        //融资截至
        $isEnd = 0;
        if($info['bid_status'] > PrjModel::BSTATUS_BIDING){
            $isEnd = 1;
        }
        $this->assign("isEnd", $isEnd);

        //最小投资金额是否更改
        $this->isBidAmountChange = service("Financing/Project")->isMinBidAmountCHange($info['remaining_amount'], $info['step_bid_amount'], $info['min_bid_amount']);

        $info['year_rate_show'] = number_format($info['year_rate'] / 10, 2) . "%";

        $time = time();
        //如果当前时间在开始时间和结束时间，即可投标
        $prj_model = D("Financing/Prj");
        $btnClass = "orgBigBtn";
        $info['btnText'] = "立即投资";
        if ($info['start_bid_time']>$time){
            $btnClass = "grayBigBtn";
            $info['btnText'] = $prj_model->getPrjStatusText(PrjModel::BSTATUS_WATING);
        }
        if ( $info['end_bid_time']<$time){
            $btnClass = "grayBigBtn";
            $info['btnText'] =  $prj_model->getPrjStatusText(PrjModel::BSTATUS_END);
        }
        $info['btnClass'] = $btnClass;
        if($info['bid_status']!=PrjModel::BSTATUS_BIDING){
            $start_time = $info['ctime'];
        }else{
            $start_time = $time;
        }
        $info['time_limit'] = service("Financing/FastCash")->showTimeLimitSdt($start_time-24*3600,$info['last_repay_date'],false);
        $this->assign("info", $info);

        $income_key = "invest_view_sdt_" . $id;
        $aincomer = cache($income_key);
        if ($aincomer) {
            $aincomerP = explode("_", $aincomer);
            $this->income = $aincomerP[0];
            $money = $aincomerP[1];
            $amount = $userBase['amount'] + $userBase['coupon_money'];
            $this->remaind = humanMoney($amount - $money, 2, false) . "元";
        } else {
            $money = $info['min_bid_amount'] / 100;
            $money = str_replace(",", "", $money);
            $money = $money * 100;
            $income = service("Financing/Invest")->getSpecialIncome($id, $money, time());
            $income = humanMoney($income, 2, false) . "元";
            $amount = $userBase['amount'] + $userBase['coupon_money'];
            $remaind = humanMoney($amount - $money, 2, false) . "元";
            $this->income = $income;
            $this->remaind = $remaind;
            cache($income_key, $income . "_" . $money, array('expire' => 600));
        }
        $muji_money = $info['demand_amount']-$info['remaining_amount'];
        $qixi_day = service('Financing/Prj')->getPrjQixiShow($prj_id);
        $wanyuan_profit = service('Financing/Invest')->getSpecialIncome($info['id'], 1000000, time(), array('is_have_biding' =>0, 'is_have_reward' => true));
        $wanyuan_profit = humanMoney($wanyuan_profit,2,false);
        $this->assign('muji_money',humanMoney($muji_money,2,false));
        $this->assign('qixi_day',$qixi_day);
        $this->assign('wanyuan_profit',$wanyuan_profit);
        //速兑通详情页 三农贷、电商贷、创客融、央企融、经营贷前端显示统一为经营贷
        if(in_array($info['prj_business_type'],array('C', 'D', 'E', 'C0V14', 'C00303'))) {
            $info['prj_business_type_name'] = '经营贷';
        }
        $this->assign('prj_business_type_name',$info['prj_business_type_name']);
        $this->assign("prj_type", $info['prj_type']);


        $user_account = M('user_account')->find((int)$user['uid']);
        $this->assign("is_paypwd_edit", $user['is_paypwd_edit'] ||$user_account['pay_password']);//20160309 是否设置支付密码

        //0元购开关
        $zeroOf = M('system_config')->where(array('status'=>0,'config_key'=>'freePay'))->find();
        $this->assign("zero",$zeroOf);

        $xprjIdAccess = service("Financing/Project")->getAccessToken($prj_id,$user['uid']);
        $this->assign("xprjIdAccess",$xprjIdAccess);

        $this->display('fastcash');
    }

    function ajaxSDTprjInfo(){
        $prjId = (int) $this->_request('id');
        $prjInfo = service("Financing/Project")->getPrjInfo($prjId);
        if(!$prjInfo){
            return false;
        }

    }

    //获取抽奖转盘  xuxin
    function getTurnplateZsx(){
        $this->display('zxsTpl');
    }

    /**
     * 返回系统时间
     */
    function getServerTime(){
        die(json_encode(time()));
    }
    //返回奖字回调，仅用于统计其埋点日志
    function hoverPrize(){
        ajaxReturn(1,1,1);
    }
    //时间轴返回还款时间列表
    function ajaxRepayTimeList($prjId){
        $result = service('Financing/Prj')->showRepayTimeList($prjId);
        $value_date_shadow = M('prj')->where(array('id'=>$prjId))->getField('value_date_shadow');
        $this->assign('value_date_shadow',$value_date_shadow);
        $this->assign('result', $result);
        $this->display();
    }
    function ajaxUserBidTimeList(){
        $uid = $this->loginedUserInfo['uid'];
        $prjId = I('prjId');
        $data = service('Financing/Prj')->showUserBidTimeList($uid,$prjId);
        $this->assign('data', $data);
        $this->display();
    }

    /**
     * 项目详情-时间轴
     */
    public function getPrjTimeLine()
    {
        $uid = (int) $this->loginedUserInfo['uid'];
        $prj_id = (int) I('get.prj_id');
        $prj_info = service('Financing/Project')->getDataById($prj_id);

        $time_line = service('Financing/Prj')->getPrjTimeLine($prj_id, $prj_info, $uid);
        $this->assign('business_type',$prj_info['prj_business_type']);   //三农贷,business_type='D'
        $this->assign('time_line', $time_line);

        $this->assign('prj_id', $prj_id);
        $this->display('ajaxTimeline');
    }
    /**
     * 项目进度项目详情
     */
    public function getPrjProcedure2()
    {
        openMasterSlave();
        $prj_id = (int) I('get.prj_id');
        $prj_info = service('Financing/Project')->getDataById($prj_id);

        $time_line = service('Financing/Prj')->getPrjProcedure($prj_id, $prj_info);

        $this->assign('time_line', $time_line);
        $this->assign('prj_id', $prj_id);
        $this->display('ajaxTimeline');
    }
    /**
     * 项目进度理财记录
     */
    public function getPrjProcedure()
    {
        $uid = (int) $this->loginedUserInfo['uid'];
        $prj_id = (int) I('get.prj_id');
        $prj_info = service('Financing/Project')->getDataById($prj_id);

        $time_line = service('Financing/Prj')->getPrjProcedure($prj_id, $prj_info, $uid);
        $this->assign('time_line', $time_line);
        $this->assign('prj_id', $prj_id);
        $this->display('Timeline');
    }

    /**
     * 发送浙商投资冻结验证码
     */
    public function sendInvestSmsCode()
    {
        $uid = $this->loginedUserInfo['uid'];

        try {
            /* @var $zs_service ZheShangService */
            $zs_service = service('Financing/ZheShang');
            $zs_service->sendMobileCode($uid, '', 'auto_invest_set');
            ajaxReturn('', '验证码已发送至'.marskName($this->loginedUserInfo['mobile'],3,4));
        } catch (Exception $e) {
            ajaxReturn('', $e->getMessage(), 0);
        }
    }
}

<?php
import_app("Zhr.Model.FinanceApplyModel");
import_app("Zhr.Model.FinanceCorpModel");
class FinancingManageAction extends BaseAction {
    const PAGE_SIZE = 10;

    private $user = NULL;
    private $uid = 0;
    private $mdPrj;
    private $mdFinanceApply;
	private $mapping_apply = array();
	private $mapping_project = array();
    private $guarantor_type;

	public function _initialize() {
		parent::_initialize();
		$this->user = $this->loginedUserInfo;
		$this->uid = $this->loginedUserInfo['uid'];
        $borrowerType = M('user')->where(array('uid' => $this->uid))->getField('finance_uid_type');
        $guarantor = D("Zhr/Guarantee")->getGuarantorInfo($this->uid, $borrowerType);
        $this->guarantor_type = $guarantor['org_type'];
        $this->mdPrj = D('Financing/Prj');
        $this->mdFinanceApply = D('Zhr/FinanceApply');
        $this->mapping_apply = array(
            'STATUS' => array(
                // 全部
                '0' => array(),
                // 担保待审核
                '1' => array(
                    'status' => FinanceApplyModel::STATUS_DEFAULT,
                ),
                // 担保审核未通过
                '2' => array(
                    'status' => FinanceApplyModel::STATS_DB_FAIL,
                ),
                // 平台待审核
                '3' => array(
                    'status' => FinanceApplyModel::STATUS_HAS_PAYMENT,
                ),
                // 平台审核未通过
                '4' => array(
                    'status' => FinanceApplyModel::STATUS_PT_FAIL,
                ),
                // 融资中
                '5' => array(
                    'status' => FinanceApplyModel::STATUS_PT_PASS,
                ),
                // 融资完成
                '6' => array(
                    'status' => FinanceApplyModel::STATUS_END,
                ),
                // 需求待处理
                '7' => array(
                    'status' => FinanceApplyModel::STATUS_INIT,
                ),
            ),
        );
        $this->mapping_project = array(
            'STATUS' => array(
                // 全部
                '0' => array(
//                    'prj.status' => array('IN', array(PrjModel::STATUS_WATING, PrjModel::STATUS_PASS)),
                ),
                // 待缴费
                '1' => array(
                    'prj.status' => PrjModel::STATUS_WATING,
                ),
                // 待开标
                '2' => array(
                    'prj.status' => PrjModel::STATUS_PASS,
                    'prj.bid_status' => PrjModel::BSTATUS_WATING,
                ),
                // 投资中
                '3' => array(
                    'prj.status' => PrjModel::STATUS_PASS,
                    'prj.bid_status' => PrjModel::BSTATUS_BIDING,
                ),
                // 投资结束
                '4' => array(
                    'prj.status' => PrjModel::STATUS_PASS,
                    'prj.bid_status' => array('IN', array(PrjModel::BSTATUS_FULL, PrjModel::BSTATUS_END)),
                ),
                // 待还款
                '5' => array(
                    'prj.status' => PrjModel::STATUS_PASS,
                    'prj.bid_status' => array('IN', array(PrjModel::BSTATUS_REPAYING, PrjModel::BSTATUS_ZS_REPAYING)),
                ),
                // 还款中
                '6' => array(
                    'prj.status' => PrjModel::STATUS_PASS,
                    'prj.bid_status' => PrjModel::BSTATUS_REPAY_IN,
                ),
                // 已还款结束
                '7' => array(
                    'prj.status' => PrjModel::STATUS_PASS,
                    'prj.bid_status' => PrjModel::BSTATUS_REPAID,
                ),
                // 已流标
                '8' => array(
                    'prj.status' => PrjModel::STATUS_PASS,
                    'prj.bid_status' => PrjModel::BSTATUS_FAILD,
                ),
            ),
        );
	}

    // 融资管理
    function index(){
        $uid = $this->loginedUserInfo['uid'];
        $this->checkCorpInfo = D("Zhr/FinanceApply")->getFinanceObj($uid, $this->loginedUserInfo)->checkCorpInfo($uid);
        $this->user_reg_client = $this->loginedUserInfo['reg_client'];
        $this->display();
    }
    public function project(){
        $apply_id = I('request.apply_id');
        $uid = $this->loginedUserInfo['uid'];
        $checkCorpInfo = D("Zhr/FinanceApply")->getFinanceObj($uid, $this->loginedUserInfo)->checkCorpInfo($uid);

        $this->assign('apply_id', $apply_id);
        $this->assign('checkCorpInfo', $checkCorpInfo);
        $this->display();
    }
    public function applyView() {
        R('Zhr/FinanceApply/viewApply');
        $this->display('projectView');
    }
    public function projectView() {
        R('Zhr/FinanceApply/viewApply');
        $this->display();
    }

    public function ajaxList(){
        $status = (int)I('request.st', 0);
        $page_number = (int)I('request.p', 1);

        $where = array(
            'uid' => $this->uid,
        );
        if(array_key_exists($status, $this->mapping_apply['STATUS'])) {
            $where = array_merge($where, $this->mapping_apply['STATUS'][$status]);
        }
        $result = $this->mdFinanceApply->getListApply($where, $page_number, self::PAGE_SIZE);
        $list = $result['data'];
        foreach ($list as &$row) {
            ;
        }
        $paging = W('Paging', array(
            'totalRows'=> $result['total_row'],
            'pageSize'=> self::PAGE_SIZE,
            'parameter' => array(
                'st' => $status,
                'p' => $page_number,
            ),
        ), TRUE);
        $this->assign('st', $status);
        $this->assign('guarantor_type', $this->guarantor_type);
        $this->assign('list', $list);
        $this->assign('paging', $paging);

        $this->assign('status_1', FinanceApplyModel::STATUS_DEFAULT);
        $this->assign('status_2', FinanceApplyModel::STATS_DB_FAIL);
        $this->assign('status_3', FinanceApplyModel::STATUS_HAS_PAYMENT);
        $this->assign('status_4', FinanceApplyModel::STATUS_PT_FAIL);
        $this->assign('status_5', FinanceApplyModel::STATUS_PT_PASS);
        $this->assign('status_6', FinanceApplyModel::STATUS_END);

        $this->display('applyFor');
    }
    public function ajaxProjectList() {
        $apply_id = (int)I('request.apply_id', 0);
        $status = (int)I('request.st', 0);
        $page_number = (int)I('request.p', 1);

        $srvProject = service('Financing/Project');
        $where = array(
            'apply.uid' => $this->uid,
        );
        if($apply_id) $where['prj.zhr_apply_id'] = $apply_id;
        if(array_key_exists($status, $this->mapping_project['STATUS'])) {
            $where = array_merge($where, $this->mapping_project['STATUS'][$status]);
        }
        $where = $srvProject->getParentsWhere($where, 'valid');
        $result = $this->mdFinanceApply->getListProject($where, $page_number, self::PAGE_SIZE);
        $list = $result['data'];
        if($list && $parents_id = $srvProject->getParentsId($list, 'prj_id')) {
            $where_children = $srvProject->getChildrenWhere($parents_id, 'prj.', 'valid');
            $result2 = $this->mdFinanceApply->getListProject($where_children, 1, 100);
            $list_children = $result2['data'];
            $list = $srvProject->bindChildren($list, $list_children, 'prj_id');
        }
        $repay_date = strtotime(date('Y-m-d'));
        $mdPrjEarlyRepayApply = D('Financing/PrjEarlyRepayApply');
        foreach ($list as &$row) {
            $prj_ext = M('prj_ext')->find($row['prj_id']);
            if($prj_ext['is_early_repay']) {
                $is_show = $mdPrjEarlyRepayApply->show2EarlyRepay($row['prj_id'], $repay_date);
                $row['earlyRepayShow'] = $is_show;
            } else {
                $row['earlyRepayShow'] = array('is_show' => 0);
            }

            //项目展期天数
            if ($prj_ext['is_extend']) {
                $extend_time_unit = $srvProject->getTimeLimitUnit($prj_ext['extend_time_unit']);
                if($extend_time_unit == '月') $extend_time_unit = '个月';
                $row['extend_time_show'] = $prj_ext['extend_time'].$extend_time_unit;
            }
            
            $earlyRepay = $mdPrjEarlyRepayApply->hasEarlyRepay($row['prj_id']);
            $row['earlyRepay'] = $earlyRepay;
            $row['could_repay'] = in_array($row['bid_status'], array(PrjModel::BSTATUS_REPAYING, PrjModel::BSTATUS_REPAY_IN)) || $row['spid'] && $row['bid_status'] == PrjModel::BSTATUS_REPAID;
        }
        $srvProject->combineBooleanAttr($list, array('could_repay', 'is_early_repay_pass'), 'prj_id');
        $srvProject->combineOrderAttr($list, array('bid_status'), 'prj_id');
        foreach ($list as &$row) {
            $row['status_view'] = $this->mdFinanceApply->getBidStatusView($row['bid_status'], $row['status']);
        }
        
        $paging = W('Paging', array(
            'totalRows'=> $result['total_row'],
            'pageSize'=> self::PAGE_SIZE,
            'parameter' => array(
                'st' => $status,
                'apply_id' => $apply_id,
                'p' => $page_number,
            ),
        ), TRUE);

        $this->assign('st', $status);
        $this->assign('list', $list);
        $this->assign('paging', $paging);

        $this->assign('STATUS_WATING', PrjModel::STATUS_WATING);
        $this->assign('BSTATUS_FULL', PrjModel::BSTATUS_FULL);
        $this->assign('BSTATUS_END', PrjModel::BSTATUS_END);
        $this->assign('BSTATUS_REPAYING', PrjModel::BSTATUS_REPAYING);
        $this->assign('BSTATUS_REPAY_IN', PrjModel::BSTATUS_REPAY_IN);
        $this->assign('BSTATUS_REPAID', PrjModel::BSTATUS_REPAID);

        $this->display('ProjectList');
    }

    function ajaxPay(){
        $prj_id = (int)I('request.prj_id');
        $status = (int)I('request.status');
        $bid_status = (int)I('request.bid_status');

        $mdFinanceApply = D('Zhr/FinanceApply');
        $srvProject = service('Financing/Project');
        $project = $srvProject->getById($prj_id);
        $project['muji_day'] = $mdFinanceApply->getPrjMujiDay($prj_id, $project);
        $info = $mdFinanceApply->getById($project['zhr_apply_id']);
        $info['fee'] = $mdFinanceApply->getFeePrj($prj_id);
        $fee = $mdFinanceApply->getCombineBzMoney($info['prj_id']);

        $this->assign('prj_id', $prj_id);
        $this->assign('project', $project);
        $this->assign('status', $status);
        $this->assign('bid_status', $bid_status);
        $this->assign('info', $info);
        $this->assign('fee', $fee);
        $this->display();
    }

    /**
     * 融资查看
     */
    function appView(){
        $id = intval(I("request.id"));
        $this->applyinfo = D("Zhr/FinanceApply")->getById($id);
        $corpId = M("finance_corp")->where(array("uid"=>$this->applyinfo['uid']))->getField("id");
        $this->corpinfo = D("Zhr/FinanceCorp")->getById($corpId);
    }
    
    
    function authSource(){
        $this->jumpTo = I('request.jumpTo');
        $user = $this->loginedUserInfo;
        $uid = $user['uid'];
        
        //当前用户身份类型 默认是个人
        $this->userInfo = M('user')->where("uid = '{$uid}'")->field('uid, uname, real_name, mobile, person_id, finance_uid_type')->find();
        $borrowerType = $this->userInfo['finance_uid_type'];
        if($borrowerType){
            if($borrowerType == FinanceApplyModel::BORROWER_TYPE_CORP){
                //企业
                $this->codeType = D("Zhr/FinanceCorp")->getCorpType();
                $corpId = M("finance_corp")->where(array("uid"=>$uid))->getField("id");
                $this->corpinfo = D("Zhr/FinanceCorp")->getById($corpId);
                $this->materialList =  D("Zhr/BorrowerMaterial")->getMaterialList();
            }elseif($borrowerType == FinanceApplyModel::BORROWER_TYPE_PERSON){
                //个人
                $corpId = M("finance_person")->where(array("uid"=>$uid))->getField("id");
                $this->corpinfo = D("Zhr/FinancePerson")->getById($corpId);
                $this->materialList =  D("Zhr/BorrowerMaterialPerson")->getMaterialList();
            }
        }else{
            $borrowerType = (int)I('request.borrowerType');
            $borrowerType = in_array($borrowerType, array(FinanceApplyModel::BORROWER_TYPE_CORP, FinanceApplyModel::BORROWER_TYPE_PERSON)) ? $borrowerType : FinanceApplyModel::BORROWER_TYPE_PERSON;
            if($borrowerType == FinanceApplyModel::BORROWER_TYPE_PERSON){
                //检查认证信息
                if(!($user['is_mobile_auth'] && $user['is_id_auth'])){
                    $this->display('no_auth');
                    exit;
                }
                $this->materialList =  D("Zhr/BorrowerMaterialPerson")->getMaterialList();
            }else{
                $this->codeType = D("Zhr/FinanceCorp")->getCorpType();
                $this->materialList =  D("Zhr/BorrowerMaterial")->getMaterialList();
            }
        }
    
        $this->canSave = $this->switchBorrowType = 0;
        if(!$this->corpinfo){
            $this->switchBorrowType = 1;
        }
        if(!$this->corpinfo||$this->corpinfo['status']!=FinanceCorpModel::STATUS_PASS){
            $this->canSave =1;
        }
    
        $user = $this->loginedUserInfo;
        $uid = $user['uid'];
        $zhrbank = M("loanbank_account")->where(array("uid"=>$uid))->select();
        $this->hasBlank = $zhrbank?1:0;
        if($zhrbank){
            foreach($zhrbank as $k=>$v){
                $zhrbank[$k]['account_no_view'] = marskName($v['account_no'],0,4);
            }
        }
        $this->zhrbank = $zhrbank;
        $this->borrowerType = $borrowerType;
        $this->display();
    }
    
    /**
     * 获取企业信息
     */
    function getCorp(){
        $id = intval(I("request.id"));
        $info = D("Zhr/FinanceCorp")->getById($id);
        ajaxReturn($info);
    }
    
    function corpCU(){
        $borrowerType = (int)I("request.borrowerType");
        if($borrowerType == FinanceApplyModel::BORROWER_TYPE_CORP){
            $this->_corpCU();
        }elseif($borrowerType == FinanceApplyModel::BORROWER_TYPE_PERSON){
            $this->_personCU();
        }
    }
    
    /**
     * 企业项目数据处理(创建，更新)
     */
    private function _corpCU(){
        $id = intval(I("request.id"));
        if($id){
            $info= M("finance_corp")->find($id);
            if(!$info) ajaxReturn(0,"数据不存在!",0);
        }
        $corp_name = I("request.corp_name");
        $province = I("request.area_province");
        $city = I("request.area_city");
        $area = I("request.area_dict_area");

        $provinceId = I("request.area_province_id");
        $cityId = I("request.area_city_id");
        $areaId = I("request.area_dict_area_id");

        $corp_address = I("request.corp_address");
        $corp_type = I("request.corp_type");
        $trade_name = I("request.trade_name");
        $trade_id = I("request.trade_id");
        $register_year = I("request.register_year");
        $register_capital = I("request.register_capital");
        $asset_amount = I("request.asset_amount");
        $lastyear_opt_amount = I("request.lastyear_opt_amount");
        $opt_overview = I("request.opt_overview");
        $status = intval(I("request.status"));
        $register_year_unit = I("request.register_year_unit");
        $register_year_currency = I("request.register_year_currency");
        //
        $license_no_b = I("request.license_no_b");
        $license_type_b = I("request.license_type_b");
        //主营业务
        $main_business = I("request.main_business");

        if(!$status) $status = FinanceCorpModel::STATUS_DEFAULT;
        $user = $this->loginedUserInfo;
        $uid = $user['uid'];

        if(!$id){
            $uniquCheck = M("finance_corp")->where(array("uid"=>$uid))->find();
            if($uniquCheck) ajaxReturn(0,"你已经添加企业信息，请不要重复添加",0);
        }

//        if($province){
//            if(C('CORP_PROVINCE')){
//                if(!in_array($province,C('CORP_PROVINCE'))){
//                    ajaxReturn(0,"目前仅支持北京、河北、山东和浙江地区的城市",0);
//                }
//            }
//        }

        $now = time();

        $data=array();
        $data['uid'] = $uid;
        $data['corp_name'] = $corp_name;
        $data['province'] = $province;
        $data['city'] = $city;
        $data['area'] = $area;
        $data['province_id'] = $provinceId;
        $data['city_id'] = $cityId;
        $data['area_id'] = $areaId;
        $data['corp_address'] = $corp_address;
        $data['corp_type'] = $corp_type;
        $data['trade_name'] = $trade_name;
        $data['trade_id'] = $trade_id;
        $data['register_year'] = $register_year;
        $data['register_capital'] = $register_capital;
        $data['asset_amount'] = $asset_amount;
        $data['lastyear_opt_amount'] = $lastyear_opt_amount;
        $data['opt_overview'] = $opt_overview;
        $data['main_business'] = $main_business;
        $data['register_year_unit'] = $register_year_unit;
        $data['register_year_currency'] = $register_year_currency;
        $data['license_no_b'] = $license_no_b;
        $data['license_type_b'] = $license_type_b;
        $data['status'] = $status;
        $data['mtime'] = $now;
        if($id){
            $rs= M("finance_corp")->where(array("id"=>$id))->save($data);

        }else{
            $data['ctime'] = $now;
            $rs= M("finance_corp")->add($data);
            //第一次添加更新用户身份
            M('user')->where(array('uid' => $data['uid']))->save(array('finance_uid_type' => FinanceApplyModel::BORROWER_TYPE_CORP));
        }
        if($rs){
            ajaxReturn($rs);
        }else{
            ajaxReturn(0,"操作失败，请重试!",0);
        }
    }
    
    /**
     * 个人（兴农）项目数据处理(创建，更新)
     */
    private function _personCU(){
        $id = intval(I("request.id"));
        if($id){
            $info= M("finance_person")->find($id);
            if(!$info) ajaxReturn(0,"数据不存在!",0);
        }

        $data['age'] = intval(I("request.age"));
        $data['marital_status'] = intval(I("request.marital_status"));
        $data['prime_assets'] = I("request.prime_assets");
        $data['income_source'] = I("request.income_source");
        $data['uid'] = $this->loginedUserInfo['uid'];

        if(!($data['age'] && $data['prime_assets'] && $data['income_source'])){
            ajaxReturn(0, '请完成必填信息', 0);
        }

        $now = time();
        $data['mtime'] = $now;
        if($id){
            $rs= M("finance_person")->where(array("id"=>$id))->save($data);
        }else{
            $data['ctime'] = $now;
            $rs= M("finance_person")->add($data);
            //第一次添加更新用户身份
            M('user')->where(array('uid' => $data['uid']))->save(array('finance_uid_type' => FinanceApplyModel::BORROWER_TYPE_PERSON));
        }
        if($rs){
            ajaxReturn($rs);
        }else{
            ajaxReturn(0,"操作失败，请重试!",0);
        }
    }

    function bindCard(){
        $id = intval(I("request.id"));
        $uid = $this->loginedUserInfo['uid'];
        $this->data = D("Zhr/LoanbankAccount")->LoanbankAccount($uid,$id);
        $payment = service('Payment/Payment');
        $this->bankList = PaymentService::$bankList;
        
        if($this->loginedUserInfo['role'] == IdentityConst::ROLE_DANBAO){
        	$this->corpinfo = M()->table('fi_guarantor_user t1')
        					->join('inner join fi_invest_guarantor t2 on t1.guarantor_id = t2.id')
        					->where(array('t1.uid' => $uid))
        					->field('t2.title as corp_name')
        					->find();
        }else{
        	$corpId = M("finance_corp")->where(array("uid"=>$uid))->getField("id");
        	
        	$this->corpinfo = D("Zhr/FinanceCorp")->getById($corpId);
        }
        
        $this->display();
    }

    /**
     * Delete bank account
     */
    function deleteBank(){
        $id = intval(I("request.id"));
        //检查下这个账号有没有申请项目，如果有申请项目那么不能删除。
        $loanbank_account = M("finance_apply")->where(array("loanbank_account"=>$id))->find();
        if($loanbank_account)  ajaxReturn(0,"这个账号绑定的有项目，该账号不能被删除",0);
        M('loanbank_account')->where(array("id"=>$id))->delete();
        $uid = $this->loginedUserInfo['uid'];
        $checkCorpInfo = D("Zhr/FinanceCorp")->checkCorpInfo($uid);
        ajaxReturn(array("checkCorpInfo"=>$checkCorpInfo),"操作成功!");
    }

    /**
     * 获取已设置的银行卡
     */
    function getMyBanks(){
        $user = $this->loginedUserInfo;
        $uid = $user['uid'];
        $list = M("fund_account")->where(array("uid"=>$uid))->select();
        ajaxReturn($list);
    }

    /**
     * 还款详情页
     */
    function view(){
        $prjId = intval(I("request.id"));
        $early_repay_id = (int) $this->_request("early_repay_id");
        $is_have_children = M('prj')->where(array('id' => $prjId))->getField('have_children');
        if(!$is_have_children) {
            if($prjId){
                $info = M("prj_repayment")->where(array("prj_id"=>$prjId))->find();
                $prjRepaymentId = $info['id'];

                if(!$info){
                    $this->error("页面不存在");
                }
            } else if($early_repay_id){
                $early_repay_apply = M('prj_early_repay_apply')->where(array('id'=>$early_repay_id, 'status'=>2))->find();
                $info = $early_repay_apply;
                if(!$info){
                    $this->error("页面不存在");
                }
                $prjId = $info['prj_id'];
                $repayment = service("Application/Repayment")->getPrjRepaymentByPrjId($prjId);
                $prjRepaymentId = $repayment['id'];
            } else {
                $this->error("页面不存在");
            }
        }

        $service = service("Financing/Project");
        $prjInfo = $service->getById($prjId);
        if(!$prjInfo){
            $this->error("页面不存在");
        }

        $srvProject = service('Financing/Project');
        $srvFinancing = service('Financing/Financing');
        $children_id = $srvProject->getChildrenId($prjId);
        $total_online = 0;
        foreach ($children_id as $_prj_id) {
            $total_online += $srvFinancing->getOnline($_prj_id);;
        }
        $prjInfo['total_online'] = $total_online;
        $prjInfo['start_bid_time_view'] = date('Y-m-d H:i:s', $srvProject->getChildField($prjId, 'start_bid_time'));


        $typeName = service("Financing/Project")->getPrjTypeName($prjInfo['prj_type']);
        $this->assign("typeName",$typeName);
        $this->assign("id",$prjRepaymentId);
        $this->assign("early_repay_id",$early_repay_id);
        $this->assign('prjInfo', $prjInfo);

        if($prjInfo['repay_way'] == PrjModel::REPAY_WAY_ENDATE){
            $this->display("view");
        }else{
            //实际融资金额
            $realMoneyWhere = array();
            $realMoneyWhere['prj_id'] = $prjId;
            $realMoneyWhere['status'] = array("NEQ",PrjOrderModel::STATUS_NOT_PAY);
            $realMoneyWhere['is_regist'] = 0;
            $realMoneyResult = M("prj_order")->where($realMoneyWhere)->field("SUM(rest_money) AS realmoney")->find();
            $this->realMoneyView = humanMoney($realMoneyResult['realmoney'],2,false)."元";
            //还款进度
            list($repayedCount,$totalRepayCount) = service("Application/Repayment")->getPrjRepayPro($prjId);
            $this->pro = number_format(($repayedCount/$totalRepayCount)*100,0)."%";
            $this->proView = $repayedCount."/".$totalRepayCount;

            $this->display("view_permonth");
        }
    }

    /**
     * 明细
     */
    function repaymentList(){
        import_app("Financing.Model.PrjRepaymentModel");
        $page = (int) $this->_request("p");
        $page = $page ? $page :1;
        $pageSize = 10;

        $early_repay_id = (int) $this->_request("early_repay_id");

        $prjRepaymentId = (int) $this->_request("id");
        $arg_prj_id = (int)I('request.prjId');

        D('Financing/PrjRepayPlan');
        D('Financing/PrjOrderRepayPlan');
        D('Financing/PrjRepayment');
        $is_sp = 0;
        if($arg_prj_id) $is_sp = M('prj')->where(array('id' => $arg_prj_id))->getField('have_children');
        if($is_sp) {
            $prjId = $arg_prj_id;
            $this->assign('spid', $arg_prj_id);
        } else {
            if($prjRepaymentId){

                $obj = service("Application/Repayment");

                $info = $obj->getPrjRepaymentById($prjRepaymentId);

                if(!$info){
                    $this->error("页面不存在");
                }

                $prjId = $info['prj_id'];
                $early_repay_id = M('prj_early_repay_apply')->where(array('prj_id'=>$prjId))->getField('id');
            } else if($early_repay_id){
                    $early_repay_apply = M('prj_early_repay_apply')->where(array('id'=>$early_repay_id, 'status'=>2))->find();
                    $info = $early_repay_apply;
                    if(!$info){
                        $this->error("页面不存在");
                    }
                    $prjId = $info['prj_id'];
                $repayment = service("Application/Repayment")->getPrjRepaymentByPrjId($prjId);
                $prjRepaymentId = $repayment['id'];
            } else {
                $this->error("页面不存在");
            }
        }
        if($is_sp) {
            $srvProject = service('Financing/Project');
            $where_prj_id = array('IN', $srvProject->getChildrenId($prjId));
        } else {
            $where_prj_id = $prjId;
        }

        $this->prjId = $prjId;

    	$prjInfo = service("Financing/Project")->getPrjInfo($prjId);
        $this->prjInfo = $prjInfo;

        // 是否需要生成展期还款计划
        $srvPrjRepayExtend = service('Financing/PrjRepayExtend');
        $is_extend = $srvPrjRepayExtend->isExtend($prjId);
        $is_show_extend = $srvPrjRepayExtend->isShowExtend($prjId);
        $last_repay_date = $srvPrjRepayExtend->getLastRepayDate($prjId);
        $this->assign('is_show_extend', $is_show_extend);
        $toDeal = true;
        if($is_show_extend || $is_extend && strtotime(date('Y-m-d')) > $last_repay_date) $toDeal = false;

        $this->prjRepaymentId = $prjRepaymentId;
        $this->early_repay_id = $early_repay_id;

        // 是否有已发生过还款
        $mdPrj = D('Financing/Prj');
        $parent_bid_status = $mdPrj->getParentBidStatus($prjId, $prjInfo['bid_status']);
        $is_repayed = in_array($parent_bid_status, array(PrjModel::BSTATUS_REPAY_IN, PrjModel::BSTATUS_REPAID));
        $this->assign('is_repayed', $is_repayed);

    	if($prjInfo['repay_way'] == PrjModel::REPAY_WAY_ENDATE){
		        $result = $obj->listRepayment($prjId,$page,$pageSize);
				if($early_repay_id){
					$toDeal = false;
					$btnValue = "还款";
				} else {
			        if(($info['status'] == PrjRepaymentModel::STATUS_WAIT_DEAL_WITH) && ($info['repay_time'] <= 0)){
			            $toDeal = true;
			            $btnValue = "还款";
			        }
	
			        if(($info['status'] == PrjRepaymentModel::STATUS_WAIT_DEAL_WITH) && ($info['repay_time'] > 0)){
			            $toDeal = false;
			            $btnValue = "正在还款中";
			        }
	
			        if($info['status'] != PrjRepaymentModel::STATUS_WAIT_DEAL_WITH){
			            $toDeal = false;
			            $btnValue = "";
			        }
	
			         //获取最迟还款日
			         $lastRepayTime = $prjInfo['last_repay_date'];
			         $todayTime = strtotime(date('Y-m-d'));
	
			         if($lastRepayTime > $todayTime){
			             if(!$prjInfo['ext']['is_early']){
			                  $toDeal = false;
			                  $btnValue = "还款";
			             }
			         }
	
				}

		        $this->assign('toDeal', $toDeal);
		        $this->assign('btnValue', $btnValue);
		        $this->assign('prjId', $prjId);
		        $this->assign('result', $result);
		        //分页
		        $paging  = W("Paging",array("totalRows"=>$result['total_row'],"pageSize"=>$pageSize,"parameter"=>$parameter),true);
		        $this->assign("paging",$paging);

		        $this->display();
    	}else{
            //类型，1-当期还款明细 2-还款情况
            $type = $this->_request("t");
            $parameter = array(
                'early_repay_id' => $early_repay_id,
                'id' => $prjRepaymentId,
                'prjId' => $prjId,
                't' => $type,
            );
            $type = $type ? $type : 1;
            if($type == 1){

                //判断是否已还款
                $checkWhere=array();
                $checkWhere['prj_id'] = $where_prj_id;
                $checkWhere['repay_date'] = array("ELT",strtotime(date('Y-m-d')));
                $checkWhere['status'] = array('IN', array(PrjRepayPlanModel::PRJ_REPAYMENT_STATUS_WAIT, PrjRepayPlanModel::PRJ_REPAYMENT_STATUS_REPAYMENTING));
                $checkWhere['is_auto'] = 0; // 募集期还款日和最终还款日在同一天的时候
                $checkResult = M("prj_repay_plan")->where($checkWhere)->find();

                $this->isRepayed = !$checkResult ? 1:0;

                if($this->isRepayed){
                    $prjPlanStatus = PrjRepayPlanModel::PRJ_REPAYMENT_STATUS_SUCCESS;
                }else{
                    $prjPlanStatus = array('IN', array(PrjRepayPlanModel::PRJ_REPAYMENT_STATUS_WAIT, PrjRepayPlanModel::PRJ_REPAYMENT_STATUS_REPAYMENTING));
                }

                $prj_repay_plan_id_arr = M("prj_repay_plan")
                    ->where(array("prj_id"=>$where_prj_id,"repay_date"=>array("ELT",strtotime(date('Y-m-d'))),"status"=>$prjPlanStatus,"is_auto"=>0))->order("repay_date ASC")
                    ->select();
                $prj_repay_plan_id = array();
                foreach ($prj_repay_plan_id_arr as $row) {
                    $prj_repay_plan_id[] = $row['id'];
                }

                if($this->isRepayed){
                    $listWhere =  array();
                    $listWhere['prj_order_repay_plan.prj_id'] = $where_prj_id;
                    $listWhere['prj_order_repay_plan.prj_repay_plan_id'] = array('IN', $prj_repay_plan_id);
                    $listWhere['prj_order_repay_plan.status'] = array("NEQ",PrjOrderRepayPlanModel::ORDER_REPAYMENT_STATUS_FAIL);
                }else{
                    $listWhere =  array();
                    $listWhere['prj_order_repay_plan.prj_id'] = $where_prj_id;
                    $listWhere['prj_order_repay_plan.prj_repay_plan_id'] = array('IN', $prj_repay_plan_id);
                    $listWhere['prj_order_repay_plan.status'] = PrjOrderRepayPlanModel::ORDER_REPAYMENT_STATUS_WAIT;
                }

                $model = M("prj_order_repay_plan");
                $model = $model->page($page.",".$pageSize);
                $model = $model->Table("fi_prj_order_repay_plan prj_order_repay_plan");
                $model = $model->order("prj_order_id,repay_periods");
                $model = $model->where($listWhere);

                $model = $model->field("prj.prj_name,prj_order.money,user.uname,prj_order_repay_plan.pri_interest,prj_order_repay_plan.repay_date,prj_order_repay_plan.ptype,prj_order_repay_plan.repay_periods");
                $model = $model->join("INNER JOIN fi_prj_order prj_order ON prj_order.id=prj_order_repay_plan.prj_order_id
                                  LEFT JOIN fi_user user ON prj_order.uid = user.uid
                                  left join fi_prj prj on prj.id=prj_order_repay_plan.prj_id
                                  ");
                $data = $model->select();


                if($data){
                    foreach($data as $k=>$v){
                        $data[$k]['money_view'] = humanMoney($v['money'],2,false);
                        $data[$k]['pri_interest_view'] = humanMoney($v['pri_interest'],2,false);
                        $data[$k]['repay_date_view'] = date('Y-m-d',$v['repay_date']);
                    }
                }

                $totalCountModel = M("prj_order_repay_plan");
                $totalCountModel = $totalCountModel->Table("fi_prj_order_repay_plan prj_order_repay_plan");
                $totalCountModel = $totalCountModel->where($listWhere);
                $totalCountResult = $totalCountModel->field("SUM(pri_interest) as total_pri_interest")->find();
//  				echo $totalCountModel->getLastSql();
                $param = service("Financing/Project")->getRepayDeposit($where_prj_id);
                $this->assign("guarant_fee",$param['guarant_fee']);
                $this->assign("deposit_fee",$param['deposit_fee']);
                $totalPriInterest = $totalCountResult['total_pri_interest'];  //鑫银票还款的地方展示本息
                if($param['guarant_fee']) $totalCountResult['total_pri_interest'] += $param['guarant_fee'];
                if($param['deposit_fee']) $totalCountResult['total_pri_interest'] += $param['deposit_fee'];
                $this->totalPriInterest = $totalPriInterest;
                $this->totalMoney = humanMoney($totalCountResult['total_pri_interest'],2,false);

                $this->list = $data;

                $modelCount = M("prj_order");
                $modelCount = $modelCount->where($listWhere);
                $modelCount = $modelCount->Table("fi_prj_order_repay_plan prj_order_repay_plan");
                $modelCount = $modelCount->join("INNER JOIN fi_prj_order prj_order ON prj_order.id=prj_order_repay_plan.prj_order_id
                                           LEFT JOIN fi_user user ON prj_order.uid = user.uid");
                $result = $modelCount->field("COUNT(*) AS CNT")->find();
                $this->totalRow = (int) $result['CNT'];

                $paging  = W("Paging",array("totalRows"=>$this->totalRow,"pageSize"=>$pageSize,"parameter"=>$parameter),true);
                $this->assign("paging",$paging);
                $this->assign('toDeal', $toDeal);

                $this->display("cu_repay_list");
            }else{
                $listWhere =  array();
                $listWhere['prj_id'] = $where_prj_id;
                $listWhere['status'] = array("NEQ",PrjRepayPlanModel::PRJ_REPAYMENT_STATUS_FAIL);

                $model = M("prj_repay_plan");
                $model = $model->order("repay_periods ASC");
                $model = $model->where($listWhere);
                $data = $model->select();
// 				echo $model->getLastSql();
                if($data){
                    foreach($data as $k=>$v){
                        $data[$k]['repay_date_view'] = date('Y-m-d',$v['repay_date']);
                        $data[$k]['pri_interest_view'] = humanMoney($v['pri_interest'],2,false);
                        $data[$k]['principal_view'] = humanMoney($v['principal'],2,false);
                        $data[$k]['yield_view'] = humanMoney($v['yield'],2,false);
                        $data[$k]['rest_principal_view'] = humanMoney($v['rest_principal'],2,false);
                        $data[$k]['status_view'] = $v['status'] == PrjRepayPlanModel::PRJ_REPAYMENT_STATUS_WAIT ? "待还":"√";
                    }
                }

                $this->list = $data;
                $this->display("re_info");
            }
        }
    }

    /**
     * 导出
     */
    function export(){
        $prjId = (int) $this->_request("id");
        $obj = service("Application/Repayment");
        $obj->export($prjId);
    }

    /**
     * 处理还款
     */
    function doRepayment(){
        $spid = (int)I('request.spid');
        $srvProject = service('Financing/Project');
        if($spid) {
            $diff_money = $srvProject->checkRepayMoney($this->uid, $spid, TRUE);
            if(!$diff_money || $diff_money < 0) ajaxReturn(array('prj_id' => $spid, 'amount' => $diff_money/100), '您的还款金额不足', -3000);

            $children_id = $srvProject->getChildrenId($spid);
            $rows = M('prj_repayment')->where(array('prj_id' => array('IN', $children_id), 'status' => 1))->field('id')->select();
            if(!$rows) ajaxReturn(0, '未获取到子标还款Id', 0);
            try {
                foreach ($rows as $row) {
                    $this->do_repayment_one($row['id']);
                }
            } catch (Exception $e) {
                ajaxReturn(0, $e->getMessage(), 0);
            }
            ajaxReturn(0, '操作成功', 1);
        }


        ignore_user_abort(true);
        set_time_limit(0); // 大数据处理，延长时间
        import_app("Financing.Model.PrjModel");
        $prjRepaymentId = (int) $this->_request("id");
        $uid = $this->loginedUserInfo['uid'];
        D("Admin/ActionLog")->insertLog($uid,"还款",array("prj_repayment_id"=>$prjRepaymentId),array());
        $prjRepaymentInfo = service("Application/Repayment")->getPrjRepaymentById($prjRepaymentId);
// 		print_r($prjRepaymentInfo);exit;
        if(!$prjRepaymentInfo){
            ajaxReturn(0,"异常，还款数据未生成",0);
        }

        $prjId = $prjRepaymentInfo['prj_id'];
        $diff_money = $srvProject->checkRepayMoney($this->uid, $prjId, TRUE);
        if(!$diff_money || $diff_money < 0) ajaxReturn(array('prj_id' => $prjId, 'amount' => $diff_money/100), '您的还款金额不足', -3000);

        $projectObj = service("Financing/Project");
        $info = $projectObj->getPrjInfo($prjId);

        if(!$info){
            ajaxReturn(0,"异常，项目不存在",0);
        }

        //处理非到期还款
        if(!service("Financing/Invest")->isEndDate($info['repay_way'])){

            ignore_user_abort(true);
            set_time_limit(0); // 大数据处理，延长时间
            //缓存锁定

            $prj_repay_plan_id_arr = M("prj_repay_plan")
                ->where(array(
                    "prj_id"=>$prjId,
                    "repay_date"=>array("ELT",strtotime(date('Y-m-d'))),
                    "status"=>PrjRepayPlanModel::PRJ_REPAYMENT_STATUS_WAIT))->order("repay_date ASC")
                ->find();
            $prj_repay_plan_id = $prj_repay_plan_id_arr['id'];
            $key = "do_repayment_".date('Y-m-d');
//            cache($key,null);
            if(cache($key)){
                ajaxReturn(0,"已经有项目在还款，请在前面的项目还款后再处理",0);
            }
            cache($key,1,array('expire'=>86400));
            $obj = service("Financing/Project")->getTradeObj($prjId,"Repayment");
            if(!$obj) ajaxReturn(0,MyError::lastError(),0);
            $params = array();
            $params['prj_repay_plan_id'] = $prj_repay_plan_id;
            $params['repayment_uid'] = $uid;
            $obj->doPrjRepayment($params);
            cache($key,null);
            if(MyError::hasError()){
                $myUser = M("user")->find($uid);
                $tenantId = service("Account/Account")->getTenantIdByUser($myUser);
                M()->query("UPDATE fi_user_account SET repay_freeeze_cache=repay_freeze_money WHERE uid=".$tenantId);
                ajaxReturn(0,MyError::lastError(),0);
            }
            ajaxReturn(0,"操作成功",1);
        }

        if($info['bid_status'] == PrjModel::BSTATUS_REPAID){
            ajaxReturn(0,"异常，项目已还款结束",0);
        }

        if($prjRepaymentInfo['repay_time']){
            ajaxReturn(0,"项目已经在处理中...请不要重复提交",0);
        }

        $myUser = M("user")->find($uid);

        $tenantId = service("Account/Account")->getTenantIdByUser($myUser);

        if($uid != $tenantId && $myUser['dept_id'] != $info['dept_id']){
            ajaxReturn(0,"你不属于此项目的发布机构，没有权限进行此操作",0);
        }

        $userAccount = service("Payment/PayAccount")->getBaseInfo($tenantId);
        //还款冻结金额
        $repayFreezeMoney = $userAccount['repay_freeze_money'];


        import_addon("libs.Cache.RedisList");
        import_app("Application.Service.RepaymentService");
        //获取状态
        import_app("Financing.Model.PrjModel");
        $prjStatus = M('prj')->where(array("id"=>$prjId))->getField("bid_status");


        $key = service("Application/Repayment")->getStatistTotalKey($prjId,$prjStatus);

        $redisObj = RedisList::getInstance($key);
        $resultTotalTmp = $redisObj->lGet(0);
        $resultTotal = $resultTotalTmp ? unserialize($resultTotalTmp) : "";
        if(!$resultTotal){
            ajaxReturn(0,"网络忙，请稍候刷新页面重试(queue fail)!",0);
        }
        $checkMoney = service("PayMent/PayAccount")->getRepayFreeezeCache($tenantId);
        if($checkMoney < $resultTotal['total']){
            ajaxReturn(0,"还款金额不够，请先充值",0);
        }
        //获取最迟还款日
        $lastRepayTime = $info['last_repay_date'];
        $todayTime = strtotime(date('Y-m-d'));
        if(($lastRepayTime > $todayTime)){
            if(!$info['ext']['is_early']){
                ajaxReturn(0,"此产品不能提前还款",0);
            }
        }

        $todayTime = strtotime(date('Y-m-d',$info['last_repay_date']));
        $repaymentService = service("Financing/Trade");
        $result = $repaymentService->repayment($prjRepaymentId,$todayTime,$uid);
        if($result !== true){
            ajaxReturn(0,MyError::lastError(),0);
        }

        service("PayMent/PayAccount")->delRepayFreeezeCache($tenantId,$resultTotal['total']);
        ajaxReturn(0,"操作成功，系统将在1小时内将资金转到投资人手中，请随时关注还款消息",1);
    }

    private function do_repayment_one($prj_repayment_id) {
        ignore_user_abort(true);
        set_time_limit(0); // 大数据处理，延长时间
        import_app('Financing.Model.PrjModel');
        $uid = $this->loginedUserInfo['uid'];

        D('Admin/ActionLog')->insertLog($uid, '还款', array('prj_repayment_id' => $prj_repayment_id), array());
        $repayment_info = service('Application/Repayment')->getPrjRepaymentById($prj_repayment_id);
        if(!$repayment_info) throw_exception("还款数据未生成, prj_repayment_id: {$prj_repayment_id}");

        $prj_id = $repayment_info['prj_id'];
        $mdCreditorTransfer = D('Financing/CreditorTransfer');
        if($mdCreditorTransfer->isPrjTransfered($prj_id)) throw_exception('该项目债权已转让，您不能进行还款操作。');
        $srvProject = service('Financing/Project');
        $project = $srvProject->getPrjInfo($prj_id);
        if(!$project) throw_exception("项目不存在, prj_id: {$prj_id}");

        //处理非到期还款
        if(!service('Financing/Invest')->isEndDate($project['repay_way'])) {
            //缓存锁定
            $prj_repay_plan = M('prj_repay_plan')->where(array('prj_id' => $prj_id, 'repay_date' => array('ELT', strtotime(date('Y-m-d'))), 'status' => PrjRepayPlanModel::PRJ_REPAYMENT_STATUS_WAIT))->order('repay_date ASC')->find();
            $prj_repay_plan_id = $prj_repay_plan['id'];
            $key = 'do_repayment_' . date('Y-m-d');
            if(S($key)) throw_exception("已经有项目在还款，请在前面的项目还款后再处理");
            S($key, 1, array('expire' => 86400));
            $oRepayment = service('Financing/Project')->getTradeObj($prj_id, 'Repayment');
            if(!$oRepayment) throw_exception(MyError::lastError());

            $params = array();
            $params['prj_repay_plan_id'] = $prj_repay_plan_id;
            $params['repayment_uid'] = $uid;
            $oRepayment->doPrjRepayment($params);
            S($key, NULL);
            if(MyError::hasError()) {
                $user = M('user')->find($uid);
                $tenant_id = service('Account/Account')->getTenantIdByUser($user);
                M()->query('UPDATE fi_user_account SET repay_freeeze_cache=repay_freeze_money WHERE uid=' . $tenant_id);
                throw_exception(MyError::lastError());
            }
        }

        return TRUE;
    }
    
    public function getEarlyRepay(){
    	$prj_id = intval(I("request.prj_id"));
    
    	$param = D("Financing/PrjEarlyRepayApply")->hasEarlyRepay($prj_id);
    
    	$this->result = $param;
    	$this->display();
    	// ajaxReturn($param, "", 1);
    }
    
    // 是否显示提前回款申请按钮
    public function showEarlyRepay(){
    	$prj_id = intval(I("request.prj_id"));
    	$repay_date = strtotime(date('Y-m-d'));
    
    	$param = D("Financing/PrjEarlyRepayApply")->show2EarlyRepay($prj_id, $repay_date);
    	ajaxReturn($param, "", 1);
    }
    
    //提交提前回款申请
    public function applyEarlyRepay(){
    	$isAjax = intval(I("request.isAjax"));
    	$prj_id = intval(I("request.prj_id"));
    	if(!empty($isAjax)){
    		$repay_date = strtotime(I("post.repay_date"));
    		try{
    			$id = D("Financing/PrjEarlyRepayApply")->applyEarlyRepay($prj_id, $this->loginedUserInfo['uid'], $repay_date, $this->dept_id);
    			if(!$id) ajaxReturn(0, "申请失败", 0);
    			else ajaxReturn(1, "申请成功", 1);
    		} catch(Exception $e){
    			ajaxReturn(0, $e->getMessage(), 0);
    		}
    	} else {
    		$this->prj_id = $prj_id;
    		$this->dateparam = D("Financing/PrjEarlyRepayApply")->getMinMaxTime($prj_id);
    		$this->display();
    	}
    }
    
    //获取提前还款罚息金额
    public function getEarlyMoney(){
    	$prj_id = intval(I("request.prj_id"));
    	$repay_date = strtotime(I("post.repay_date"));
    	try{
    		$interest = D("Financing/PrjEarlyRepayApply")->getEarlyMoney($prj_id, $repay_date);
    		if($interest['ratio']==0 || ($interest['ratio']>0 && $interest['intere']>0)) ajaxReturn($interest, "成功", 1);
    		else ajaxReturn(0, "日期选择太多或太小", 0);
    	} catch(Exception $e){
    		ajaxReturn(0, $e->getMessage(), 0);
    	}
    }
    
    //-----还款处理---------------------------------
    //还款管理里面 是否可以显示提前还款的按钮
    public function showEarlyRepay2(){
    	$prj_id = intval(I("request.prj_id"));
    
    	$casShow = D("Financing/PrjEarlyRepayApply")->showEarlyRepay($prj_id);
    	$status = 0;//不显示
    	if($casShow) $status = 1;//显示
    	ajaxReturn(0,'',$status);
    }
    
    //根据还款日期生成提前还款计划
    public function earlyRepayPlan(){
    	$prj_id = (int)I('request.prj_id');
    	try{
    		$id = D("Financing/PrjEarlyRepayApply")->earlyRepayPlan($prj_id);
    		// ajaxReturn(0,"处理成功",1);
    		if(!$id) ajaxReturn(0, "处理失败", 0);
    		else ajaxReturn(1, "处理成功", 1);
    	}catch(Exception $e){
    		ajaxReturn(0,$e->getMessage(),0);
    	}
    }


    // 展期
    public function extendRepayPlan() {
        $prj_id = (int)I('request.prj_id');
        $srvPrjRepayExtend = service('Financing/PrjRepayExtend');
        try {
            $srvPrjRepayExtend->genRepayPlan($prj_id);
            ajaxReturn('', '处理成功');
        } catch (Exception $e) {
            $srvPrjRepayExtend->clearRepayPlanCache($prj_id);
            ajaxReturn('', $e->getMessage(), 0);
        }
    }


    // 还款计划生成进度
    public function getRepayPlanProgress($prj_id) {
        $srvProject = service('Financing/Project');
        try {
            $oQueueTradeJob = $srvProject->getTradeObj($prj_id, 'QueueTradeJob');
            if(!$oQueueTradeJob) throw_exception(MyError::lastError());
            $ret = $oQueueTradeJob->getProgress($prj_id);
            ajaxReturn('', $ret[1], $ret[0]); // $ret[1] 0 错误, 1: 进度, 2: 完毕
        } catch (Exception $e) {
            $message = $e->getMessage();
            ajaxReturn('', "PRJ_ID:{$prj_id}, {$message}", 0);
        }
    }


    /**
     * 账户设置-企业资料
     */
    function companyInfo(){
        $this->display('fund_companyInfo');
    }

    /**
     * 账户设置-账户信息
     */
    function accountInfo(){
        D('Account/User');
        $zhr_service = service('Zhr/Zhr');
    	//资料
    	if(UserModel::USER_TYPE_CORP == $zhr_service->getBorrowerRole($this->uid, $this->user)){
    		//企业
    		$this->codeType = D("Zhr/FinanceCorp")->getCorpType();
    		$corpId = M("finance_corp")->where(array("uid"=>$this->user['uid']))->getField("id");
    		$this->corpinfo = D("Zhr/FinanceCorp")->getById($corpId);
    		$this->materialList =  D("Zhr/BorrowerMaterial")->getMaterialList();
            $this->assign('title',$this->corpinfo['corp_name']);
            $this->assign('corpinfo',$this->corpinfo);
            $this->assign('is_person',0);
    	}elseif(UserModel::USER_TYPE_PERSON == $zhr_service->getBorrowerRole($this->uid, $this->user)){
    		//个人
    		$corpId = M("finance_person")->where(array("uid"=>$this->user['uid']))->getField("id");
    		$this->corpinfo = D("Zhr/FinancePerson")->getById($corpId);
    		$this->materialList =  D("Zhr/BorrowerMaterialPerson")->getMaterialList();
            $this->assign('is_person',1);
    	}
        $this->assign('user',$this->user);
//        p($this->loginedUserInfo);
//        p($this->corpinfo);
        if (!$this->corpinfo['org_code']) {//融资企业没有组织机构代码证的情况下
            $this->assign('warning',true);
        }
        else {
            $this->assign('warning',false);
        }

        if ($this->loginedUserInfo['sign_user_id']) {
            $eCertInfo = service('Signature/Sign')->get_cert_info($this->loginedUserInfo['sign_user_id']);
            //p($eCertInfo);
            $this->assign('eCertInfo',$eCertInfo);
            $this->assign('noOperator',1);//融资方不给添加操作人
        }
        if($this->loginedUserInfo['fadada_customer_id']){
            //获取法大大证书信息
            $FaDaDaeCertInfo = service('Signature/Sign')->get_cert_info($this->loginedUserInfo['fadada_customer_id']);
            $this->assign('FaDaDaeCertInfo',$FaDaDaeCertInfo);
            $this->assign('noOperator',1);//融资方不给添加操作人
        }

        $this->display('fund_accountInfo');
    }
    
    /**
     * 银行卡列表
     */
    function getLoanBank(){
    	$uid = $this->user['uid'];
    	$zhrbank = M("loanbank_account")->where(array("uid"=>$uid))->select();
    	$this->hasBlank = $zhrbank?1:0;
    	if($zhrbank){
    		foreach($zhrbank as $k=>$v){
    			$zhrbank[$k]['account_no_view'] = marskName($v['account_no'],0,4);
    		}
    	}
    	$this->zhrbank = $zhrbank;
    	$this->display('ajax_bankCardList');
    }

    //资金记录
    public function getMyMoneyList(){
    	$showP = $this->_get("showP");
    	if(!$showP) $showP = "getMyRecordList";
    	$this->showP = $showP;
    	$this->display();
    }

    public function contractManagement()
    {
        $this->display();
    }
}

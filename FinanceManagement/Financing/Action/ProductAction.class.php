<?php
/**
 * User: 000802
 * Date: 2013-10-10 13:39
 * $Id$
 *
 * Type: Action
 * Group: Fanancing
 * Module: 我要融资
 * Description: 产品发布、融资列表、产品转让
 */

class ProductAction extends BaseAction {
    const PAGESIZE = 10;

    private $uid = 0;
    private $user;
    private $dept_id = 0;

    private $mdPrj;


    public function _initialize() {
        parent::_initialize();
        $this->uid = $this->loginedUserInfo['uid'];
        $this->user = M('User')->find($this->uid);
        $this->user_account = M('user_account')->find($this->uid);
        $this->dept_id = (int)$this->user['dept_id'];

        $this->mdPrj = D('Financing/Prj');
    }


    /**
     * 项目发布/修改需要assign的公共变量
     */
    private function assign_project() {
        // 1 ~ 30天，用于下拉列表
        $month_list = array();
        for ($i=1; $i < 11; $i++) {
            $month_list[$i] = $i;
        }

        // 资金转入账户
        $fund_accounts = array();
        $tenant_uid = service('Account/Account')->getTenantIdByUser($this->user);
        try {
        	if($this->user_account['is_merchant']){
//                $_fund_accounts = M('fund_account')->where(array("uid"=>$this->uid, "is_active"=>1))->select();
                $_fund_accounts = M('loanbank_account')->where(array("uid"=>$tenant_uid, "is_active"=>1))->select();
            }else{
                $_fund_accounts = service("Payment/PayAccount")->getDeptFundAccountList($this->dept_id);
            }
            foreach ($_fund_accounts as $item) {
                $fund_accounts[$item['id']] = $item['acount_name'];
            }
        } catch (Exception $e) {
//            $this->error($e->getMessage());
        }
        $this->assign("fund_accounts", $_fund_accounts);
        //产品类型  去掉速兑通
        $temp = getCodeItemList("E005");
        $prjType = array();
        foreach($temp as $k=>$v){
            if($v['code_no'] == 'H') break;
            $prjType[$v['code_no']] = $v['code_name'];
        }
        $this->assign('prjType', $prjType);
        // 保障措施
        $pro_addcredit = array();
        foreach (getModelList('pro_addcredit') as $item) {
            $pro_addcredit[$item['id']] = $item['way'];
        }

        // 担保人
//        $invest_guarantor = array();

        $guarantor_list = D("Zhr/Guarantee")->getGuarantorOfBaoli();
//         foreach ($guarantor_list as $item) {
//             $invest_guarantor[$item['id']] = $item['title'];
//         }

        // 适用产品
        $product = array();
        foreach (getModelList('product') as $item) {
            $product[$item['id']] = $item['title'];
        }


        // 投资梯度金额
        $step_bid_mount_range = array();
        foreach (Prjmodel::$step_bid_mount_range as $amount) {
            $step_bid_mount_range[$amount] = humanMoney($amount * 100, 2, FALSE);
        }


        $this->assign('PRJ_TYPE_A', PrjModel::PRJ_TYPE_A);
        $this->assign('PRJ_TYPE_B', PrjModel::PRJ_TYPE_B);
        $this->assign('PRJ_TYPE_C', PrjModel::PRJ_TYPE_C);
        $this->assign('PRJ_TYPE_D', PrjModel::PRJ_TYPE_D);
        $this->assign('PRJ_TYPE_F', PrjModel::PRJ_TYPE_F);

        $this->assign('REPAY_WAY_ENDATE', PrjModel::REPAY_WAY_ENDATE);      // 到期还本付息
        $this->assign('REPAY_WAY_HALFYEAR', PrjModel::REPAY_WAY_HALFYEAR);  // 半年
        $this->assign('REPAY_WAY_PERMONTH', PrjModel::REPAY_WAY_PERMONTH);  // 每月等额本息还款
        $this->assign('REPAY_WAY_D', PrjModel::REPAY_WAY_D);                // 按月付息，到期还本

        $this->assign('PAY_WAY_AFTER_FULL', PrjModel::PAY_WAY_AFTER_FULL);  // 满标后支付
        $this->assign('PAY_WAY_EVERY_DAY_IN_BIDING', PrjModel::PAY_WAY_EVERY_DAY_IN_BIDING); // 募集期每天支付

        $this->assign('month_list', $month_list);
        $this->assign('fund_accounts', $fund_accounts);
        $this->assign('pro_addcredit', $pro_addcredit);
        $this->assign('invest_guarantor', $guarantor_list);
        $this->assign('product', $product);
        $this->assign('step_bid_mount_range', $step_bid_mount_range);
    }


    /**
     * 检测用户是否有商户Id
     *
     * @return mixed
     */
    public function check_tenant() {
        try {
        	$tenant_id = service("Account/Account")->getTenantIdByUser($this->user);
            if(!$tenant_id){
                $tenant_id = service("Application/ProjectManage")->getTenantByUid($this->loginedUserInfo['uid']);
            }
//             $tenant_id = service("Payment/PayAccount")->getTenantAccountId($this->dept_id);
            $this->assign('has_tenant_id', $tenant_id);
            return $tenant_id;
        } catch (Exception $e) {
            return FALSE;
        }
    }


    /**
     * 发布产品
     */
    public function add() {
        //判断是否需要申请签章用户
        if (!$this->loginedUserInfo['sign_user_id'] && !$this->loginedUserInfo['fadada_customer_id'] && service('Signature/Sign')->signSwitch()) {
            $this->error('请先申请电子签章的数字证书！',U('Application/ProjectManage/accountInfo#eCert'));
        }
        $has_tenant_id = $this->check_tenant();
        if(!$has_tenant_id || !in_array(IdentityConst::PROD_PUBLISH_NO,$this->loginedUserInfo['identity_no'])) {
            $this->redirect('Financing/Product/tlist');
        }
        // GET
        if($this->isget()) {
            $protocol_raw_params=service('Public/ProtocolData')->getProtocolRawParams(17, 'p_');
            foreach ($protocol_raw_params as $key=>$val)
            {
                if(strpos($key,'rate_')===0 || strpos($key,'addon_')===0 || strpos($key,'contract_no')===0 ) {
                    unset($protocol_raw_params[$key]);//去掉关于风险保证金比例和追加时间的参数
                }
            }
            //p($protocol_raw_params);
            $this->assign('protocol_raw_params',$protocol_raw_params);
            $this->assign_project();
            $publish = service('Financing/Financing')->getPublishInst($this->uid);
            if($publish['dept_id']){
                $this->assign('publish_inst', $publish['publish_inst']);
            }else{
                $publishName = service('Application/ProjectManage')->getGuaTitle($this->uid);
                $this->assign('publish_inst', $publishName);
            }


            $this->display();
            return;
        }

        // POST
        C('TOKEN_ON', false);
        $ret = service('Financing/Financing')->addPrj($this->uid, I('post.'));
        if($ret[0]) {
            try {
                $post = I('post.');
                $input=array();
                $protocol_raw_params=service('Public/ProtocolData')->getProtocolRawParams(17, 'p_');
                foreach ($protocol_raw_params as $key=>$val)
                {
                    if(strpos($key,'rate_')===0 || strpos($key,'addon_')===0 || strpos($key,'contract_no')===0 ) {
                        unset($protocol_raw_params[$key]);//去掉关于风险保证金比例和追加时间的参数
                    }
                    if (isset($post['pp_'.$key]) && isset($protocol_raw_params[$key])) {
                        $input[$key]=$post['pp_'.$key];//因为协议内参数和模板上的固定参数可能重名，故在post时候加了前缀pp_
                    }
                }
                service('Public/ProtocolData')->setProtocolParamPrj($ret[0], $input,17);
            }
            catch (Exception $e) {
                $this->error($e->getMessage());
            }
            $this->success($ret[1], U('Account/FinancingRecord/index'));
        } else {
            $this->error($ret[1]);
        }
    }


    /**
     * 修改产品
     *
     * @param int $prj_id
     */
    public function edit($prj_id=0) {
        $has_tenant_id = $this->check_tenant();
        if(!$has_tenant_id) {
            $this->redirect('Financing/Product/tlist');
        }

        $prj_id = (int)$prj_id;

        $srvProject = service('Financing/Project');
        $project = $srvProject->getById($prj_id);
        if(!$project) {
            $this->error('项目不存在。');
        }
        if(!($this->dept_id && $project['dept_id'] == $this->dept_id || $project['uid'] == $this->uid)) {
            $this->error('只有本人或同部门的人才可以操作。');
        }
        if($project['status'] != PrjModel::STATUS_FAILD) {
            $this->error('只能编辑审核未通过的项目。');
        }

        // 特殊字段处理
        $project['demand_amount'] /= 1000000;   // 单位：万
        $project['min_bid_amount'] /= 100;      // 单位：元
        $project['max_bid_amount'] /= 100;      // 单位：元
        $project['step_bid_amount'] /= 100;     // 单位：元

        // GET
        if($this->isget()) {
            $this->assign_project();
            $project['rate_interest'] = number_format($project['rate_interest']/10, 2);
            $protocol_params = service('Public/ProtocolData')->getProtocolParams($prj_id, NULL, 'p_');
            $protocol_params = service('Public/ProtocolData')->getDefaultProtocolParams($prj_id, $protocol_params);
            foreach ($protocol_params as $key=>$val){
                if(strpos($key,'rate_')===0 || strpos($key,'addon_')===0 || strpos($key,'contract_no')===0 ) {
                    unset($protocol_params[$key]);//去掉关于风险保证金比例和追加时间的参数
                }
            }
            //p($protocol_params);
            $this->assign('protocol_raw_params',$protocol_params);

            $this->assign('project', $project);
            $this->display();
            return;
        }

        // POST
        C('TOKEN_ON', false);
        $ret = service('Financing/Financing')->updatePrj($prj_id, $this->uid, I('post.'));
        if($ret[0]) {
            try {
                $post = I('post.');
                $input=array();
                $protocol_raw_params=service('Public/ProtocolData')->getProtocolRawParams(17, 'p_');
                foreach ($protocol_raw_params as $key=>$val)
                {
                    if(strpos($key,'rate_')===0 || strpos($key,'addon_')===0 || strpos($key,'contract_no')===0 ) {
                        unset($protocol_raw_params[$key]);//去掉关于风险保证金比例和追加时间的参数
                    }
                    if (isset($post['pp_'.$key]) && isset($protocol_raw_params[$key])) {
                        $input[$key]=$post['pp_'.$key];//因为协议内参数和模板上的固定参数可能重名，故在post时候加了前缀pp_
                    }
                }
                service('Public/ProtocolData')->setProtocolParamPrj($prj_id, $input,17);
            }
            catch (Exception $e) {
                $this->error($e->getMessage());
            }
            $this->success($ret[1], U('Account/FinancingRecord/index'));
        } else {
            $this->error($ret[1]);
        }
    }


    /**
     * 产品转让列表
     *
     * @param int $p    分页
     */
    public function tlist($p=1) {
        $this->check_tenant();
        $page = (int)$p;

        $srvFinancing = service('Financing/Financing');
        $result = $srvFinancing->listTranfer($this->uid, $page, self::PAGESIZE);

        $list = $result['data'];
        $paging  =  W('Paging', array(
            'totalRows'=> $result['total'],
            'pageSize'=> self::PAGESIZE,
            'parameter' => '',
        ), TRUE);

        $this->assign('list', $list);
        $this->assign('paging', $paging);

        $tpl = 'tlist';
        if(!is_null($this->_post('ajax'))) $tpl .= '_ajax';
        $this->display($tpl);
    }


    /**
     * 产品转让表单
     *
     * @param int $ord_id   欲转让的购买订单编号
     */
    public function transfer($ord_id=0) {
        $srvFinancing = service('Financing/Financing');
        $mdAssetTransfer = D('Financing/AssetTransfer');
                
        $expireTime = $mdAssetTransfer->getTransEndTime($ord_id,true);
        $this->viewexpireTime = date('Y-m-d',$expireTime);
        $money = $srvFinancing->getTransMoney($ord_id);
        $this->moneyView = humanMoney($money,2,false)."元";
		
        $this->detail = $srvFinancing->detailTransfer($ord_id);
        
        $this->display("transfer");
    }


    /**
     * 执行产品转让
     * POST参数：
     *      property: 转让持有份额
     *      money: 转让价格
     *      transfer_no: 转让编号
     *
     * @param int $ord_id   欲转让的购买订单编号
     */
    public function do_transfer($ord_id=0) {
        $property = 0;

        $this->ajax_check_transfer(FALSE);
        $this->check_mcode_transfer(FALSE);

        $srvFinancing = service('Financing/Financing');
        
        $money = $srvFinancing->getTransMoney($ord_id);
        
        $result = $srvFinancing->doTransfer($this->uid, $ord_id, $property, $money);
        ajaxReturn(0, $result[1], $result[0]);
    }


    /**
     * 发送产品转让验证码
     */
    public function send_mcode_transfer() {
        $result = service('Account/Validate')->sendTransferMobileCode($this->uid, $this->user['mobile']);
        ajaxReturn(0, $result[1], $result[0]);
    }


    /**
     * 验证产品转让验证码
     *
     * @param bool $ajax_return 如果为FALSE标识供其他Action调用
     * @return bool
     */
    public function check_mcode_transfer($ajax_return=TRUE) {
        $code = I('request.code'); // 验证码
        $result = service('Account/Validate')->validateTransferMobileCode($this->user['mobile'], $code);

        if($result[0] && !$ajax_return) return TRUE;
        ajaxReturn(0, $result[1], $result[0]);
    }


    /**
     * 数据Ajax验证
     *
     * @param string $model     验证模型，[Prj, InvestPrj, ManageFund]
     * @param string $field     验证字段（前端同时还要POST过来一个$filed=value键值对）
     */
    public function ajax_check($model='Prj', $field='') {
        $value = I($field);
        $available_models = array(
            'Prj',
            'InvestPrj',        // A
            'InvestLongPrj',    // B
            'ManageFund',       // C
            'HouseGuarantee',   // D
            'InvestJuyou',      // F
            'PrjCorp',          // 企业信息
            'PrjPerson',        // 个人信息
        );
        if(!(in_array($model, $available_models))) {
            ajaxReturn(0, '参数错误', 0);
        }

        C('TOKEN_ON', false);

        if(!$field) {
            $data = I('post.');
        } else {
            $data = array(
                $field => $value,
            );
        }

        $oModel = D($model);
        try {
            if($oModel->create($data)) {
                ajaxReturn('', '', 1);  // 验证成功
            } else {
                ajaxReturn('', $oModel->getError(), 0);
            }
        } catch (Exception $e) {
            ajaxReturn('', $e->getMessage(), 0);
        }
    }


    /**
     * 获取产品编号
     *
     * @param int $prj_type             产品类型标识符，如JD（借贷）、JJ（基金）
     * @param string $time_limit_unit   产品期限单位标识符，[day, month]
     * @param string $time_limit        产品期限
     */
    public function ajax_prj_no($prj_type=0, $time_limit_unit='', $time_limit='') {
        $day = (int)$time_limit;
        if($time_limit_unit == PrjModel::LIMIT_UNIT_MONTH) {
            $day *= 30;
        }
        if($day < 30) {
            $prj_series = PrjModel::PRJ_SERIES_GFS;
        } else {
            $prj_series = PrjModel::PRJ_SERIES_BFM;
        }
        $no = $this->mdPrj->genPrjNo($prj_type, $prj_series);
        ajaxReturn(0, $no, 1);
    }


    /**
     * 动态计算受益权价值
     *
     * @param int $ord_id   欲转让的购买订单编号
     * @param int $property    转让持有份额
     */
//    public function ajax_gain($ord_id=0, $property=0) {
//        D('Financing/AssetTransfer');
//        $srvFinancing = service("Financing/Financing");
//
//        $property = (float)$property;
//        if($property <= 0) {
//            ajaxReturn(0, '转让持有份额必须大于0', 0);
//        }
//        if(fmod($property, AssetTransferModel::PROPERTY_UNIT) != 0) {
//            ajaxReturn(0, '转让持有份额必须是5万的整数倍', 0);
//        }
//
//        $property = fMoney($property);
//        $detail = $srvFinancing->detailTransfer($ord_id);
//        if($detail) {
//            // 转让份额+转让冻结份额不能大于剩余份额
//            if(($property + $detail['tran_freeze_money']) > $detail['rest_money']) {
//                ajaxReturn(0, '总转让份额不能大于剩余份额', 0);
//            }
//            $gain = $srvFinancing->calGain($detail['ctime'], $detail['end_bid_time'], $detail['value_date'], $detail['time_limit'], $detail['time_limit_unit'], $detail['rate_type'], $detail['rate'], $property);
//            ajaxReturn(0, $gain, 1);
//        } else {
//            ajaxReturn(0, '获取转让产品出错', 0);
//        }
//    }


    /**
     * 产品转让表单的Ajax验证
     *
     * @param bool $ajax_return 如果为FALSE标识供其他Action调用
     * @return bool
     */
    public function ajax_check_transfer($ajax_return=TRUE) {
        $agree = I('request.agree', NULL);

        if((!is_null($agree) || !$ajax_return) && !(int)$agree) ajaxReturn(0, "请同意协议", 0);
		
        if(!$ajax_return) return TRUE;
        ajaxReturn(0, '', 1);
    }


    /**
     * 借款人API
     *
     * @param int $type 1: 企业, 2: 个人
     * @param int $p
     * @param string $keyword
     */
    public function api_ubsp_borrower($type=1, $p=1, $keyword='') {
        $srvFinancing = service('Financing/Financing');
        $row = FinancingService::API_UBSP_PAGESIZE;
//        $keyword = I('get.keyword');

        $params = array(
            'type' => $type,
            'keyword' => $keyword,
        );
        try {
            $res = $srvFinancing->UBSPBorrower($type, $p, $keyword);
        } catch (Exception $e) {
            echo $e->getMessage();
            return;
        }

        $list = $res['data']['list'];
        $paging  =  W('Paging', array(
            'totalRows'=> $res['data']['page']['totalRows'],
            'pageSize'=> $row,
            'parameter' => $params,
        ), TRUE);
        foreach ($list as $k => $v) {
            $list[$k]['json'] = json_encode($v);
        }


        $this->assign('keyword', $keyword);
        $this->assign('btype', $type);
        $this->assign('borrower_type', $type);
        $this->assign('list', $list);
        $this->assign('paging', $paging);
//        dump($list);
//        exit();
        $this->display();
    }


    /**
     * 个人客户信息
     *
     * @param $id
     */
    public function api_ubsp_person($id) {
        try {
            ajaxReturn(service('Financing/Financing')->UBSPPerson($id), '获取成功', 1);
        } catch (Exception $e) {
            ajaxReturn('', $e->getMessage(), 0);
        }
    }


    /**
     * 项目信息
     *
     * @param $id
     */
    public function api_ubsp_project($id) {
        try {
            ajaxReturn(service('Financing/Financing')->UBSPProject($id), '获取成功', 1);
        } catch (Exception $e) {
            ajaxReturn('', $e->getMessage(), 0);
        }
    }


    /**
     * 企业信息
     */
    public function api_yrzdb_pinfo() {
        $corp_register_no = I('request.id');
        $corp_id = (int)I('request.corp_id');
        $ret = service('Financing/Financing')->getYrzdbPrjInfo($corp_register_no, $corp_id);
        if($ret) {
            ajaxReturn($ret, '获取成功', 1);
        } else {
            ajaxReturn('', MyError::lastError(), 0);
        }
    }

    /** 理财需求 **/
    public function fundAdd(){
        $has_tenant_id = $this->check_tenant();
        if(!$has_tenant_id || !in_array(IdentityConst::PROD_PUBLISH_NO,$this->loginedUserInfo['identity_no'])) {
            $this->redirect('Financing/Product/tlist');
        }

        $this->display();
    }
    
    /**
     * 上传项目扩展资料
     */
    function uploadExtMaterial(){
        $prjId = intval(I('request.prj_id'));
        $itemId = intval(I('request.item_id'));
        if(!$prjId || !$prjId){
            ajaxReturn(0, '参数不正确', 0);
        }
        
        $prjModel = D('Financing/Prj');
        $condition = array(
        	'id' => array('eq', $prjId),
//            'bid_status' => array('gt', PrjModel::BSTATUS_BIDING),
            '_string' => '(CASE WHEN have_children=1 THEN not exists(SELECT id FROM fi_prj qiowdhio WHERE spid=fi_prj.id AND bid_status <=2 and status=2)  ELSE bid_status>2  END)',
        );
        $project = M('prj')->where($condition)->field('uid, guarantor_id')->find();
        if($project === false){
            ajaxReturn(0, '当前状态不能进行此操作', 0);
        }
        
        if($this->loginedUserInfo['uid'] != $project['uid']
                && !in_array(IdentityConst::PLATFORM_OPT_NO, $this->loginedUserInfo['identity_no'])
                && D('Zhr/Guarantee')->getOrgByUid($this->loginedUserInfo['uid']) != $project['guarantor_id'])
        {
            ajaxReturn(0, '你没有权限进行此操作', 0);
        }
        $rs = D('PrjMaterialExt')->uploadExtMaterial($prjId, $itemId);
        if($rs){
            ajaxReturn($rs);
        }else{
            ajaxReturn(0, MyError::lastError(), 0);
        }
    }
    
    /**
     * 删除项目扩展资料
     */
    function delExtMaterial(){
        $id = intval(I('request.id'));
        if(!$id){
            ajaxReturn(0, '参数有误', 0);
        }
        $prjMaterialExtObj = D('PrjMaterialExt');
        if(D('PrjMaterialExt')->delExtMaterial($id)){
            ajaxReturn(1, '删除成功', 1);
        }else{
            ajaxReturn(0, $prjMaterialExtObj->error, 0);
        }
    }
}
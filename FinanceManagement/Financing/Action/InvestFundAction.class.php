<?php
class InvestFundAction extends BaseAction {
    
	public function _initialize() {
		if(C('FUND_ON') === false){
			redirect('/Index/Index/index');
		}
        parent::_initialize();
        $this->user = $this->loginedUserInfo;
	}
	
	/**
	 * 理财项目首页
	 */
	public function index(){
		
		$isFundVip = D('InvestFund')->checkUserIsFundVip($this->user['uid']);
		$isFundVip && redirect(U('Financing/InvestFund/plist'));
		
		//幻灯
		$helpModel = D('Help/Help');
		$bannersTmp = $helpModel->getIndexBannerList(array('position' => 24), 5);
		
		$banners = array();
		foreach ($bannersTmp as $banner) {
			if ($this->user['usc_token']) {
				$yrzkb_url = str_replace("http://", "", C("YRZKB_API_URL"));
				$yrzkb_bbs_url = str_replace("http://", "", C("YRZKB_BBS_URL"));
				$yrzdb_url = str_replace("http://", "", C("YRZDB_API_URL"));
				if (strpos($banner['href'], $yrzkb_bbs_url) !== false) {
					$banner['href'] = C('YRZKB_BBS_URL') . "/usc.php?usc_token=" . $this->user['usc_token'] . "&referer=" . url_encode($banner['href']);
				}
				if (strpos($banner['href'], $yrzkb_url) !== false
						|| strpos($banner['href'], $yrzdb_url) !== false
				) {
					if (strpos($banner['href'], "?")) {
						$banner['href'] = $banner['href'] . "&usc_token=" . $this->user['usc_token'];
					} else {
						$banner['href'] = $banner['href'] . "?usc_token=" . $this->user['usc_token'];
					}
				}
			}
			$banners[] = $banner;
		}
		
		$prjModel = D('InvestFund');
		$where['prj_class'] = PrjModel::PRJ_CLASS_LC;
		$where['prj_type'] = PrjModel::PRJ_TYPE_G;
		$where['status'] = PrjModel::STATUS_PASS;
		$amount = M('prj')->field('demand_amount, remaining_amount')->where($where)->order('id desc')->find();
		$finishRaiseAmount = number_format(($amount['demand_amount'] - $amount['remaining_amount']) / 100);
		
		$this->assign('banners', $banners);
		$this->assign('finish_raise_amount', $finishRaiseAmount);
		$this->assign('is_login', $this->user['uid']);
		$this->assign('is_vip', (int)$isFundVip);
		$this->assign('prj_type', PrjModel::PRJ_TYPE_G);
		$this->display();
	}
	
	/**
	 * 理财会员信息
	 */
	function getFundUserInfo(){
		
// 		/Index/Protocol/view?id=31
		$protocolId = 40;
		$protocolName = getCodeItemName('E012', $protocolId);
		
		$userInfo = D('Account/User')->getByUid($this->user['uid']);
		$this->assign('userInfo', $userInfo);
        $this->assign('prj_id', I('request.prj_id'));
        $this->assign('protocol_id', $protocolId);
        $this->assign('protocol_name', $protocolName);
		$this->display('ajax_vipCheck');
	}
	
	/**
	 * 认证高级理财会员
	 */
	function updateUserToVip(){
		$user = $this->loginedUserInfo;
		if(D('InvestFund')->checkUserIsFundVip($user['uid'], $user)){
			ajaxReturn(0, '您已经完成高级会员认证', 0);
		}
		
		$vipGroupId = rtrim($user['vip_group_id'], ',').',1';
		if((M('user')->where(array('uid' => $this->user['uid']))->save(array('vip_group_id' => $vipGroupId))) !== false){
			ajaxReturn(1, '高级会员认证成功', 1);
		}else{
			ajaxReturn(0, '高级会员认证失败', 0);
		}
	}
	
	/**
	 * 用户列表首页
	 */
    public function toFundPrjList(){
        $hasTenantId = A('Financing/Product')->check_tenant();
        if($hasTenantId && in_array(IdentityConst::PROD_PUBLISH_NO, $this->user['identity_no'])) {
            $this->assign('showProductAddMenu', 1);
        }
        self::_checkManagePermission();
        $this->display();
    }
    
    /**
     * 权限判断
     */
/*     public function checkManagePermission(){
    	if(!D('InvestFund')->checkManagePermission($this->user)){
    		$this->error('你没有权限进行此操作', U('Index/Index/index'));
    	}
    } */
    
	/**
	 * 理财项目列表
	 */
	function fundPrjList(){
		$prjModel = M('Financing/Prj');
		$arr = array(
			'wait_approve_num' => array('t2.status' => PrjModel::STATUS_WATING),
			'un_approve_num' => array('t2.status' => PrjModel::STATUS_FAILD),
			'will_openBid_num' => array('t2.bid_status' => PrjModel::BSTATUS_WATING, 't2.status' => PrjModel::STATUS_PASS),
			'biding_num' => array('t2.bid_status' => PrjModel::BSTATUS_BIDING, 't2.status' => PrjModel::STATUS_PASS),
			'bid_ed_num' => array('t2.bid_status' => array('in', array(PrjModel::BSTATUS_FULL, PrjModel::BSTATUS_END, PrjModel::BSTATUS_END_MANUAL)), 't2.status' => PrjModel::STATUS_PASS),
			'bid_paying_num' => array('t2.bid_status' => array('in', array(PrjModel::BSTATUS_REPAY_IN, PrjModel::BSTATUS_REPAYING)), 't2.status' => PrjModel::STATUS_PASS),
			'bid_payed_num' => array('t2.bid_status' => PrjModel::BSTATUS_REPAID, 't2.status' => PrjModel::STATUS_PASS),
		);
		
		$totalCnt = 0;
		foreach ($arr as $state => $condition){
			$condition['t2.uid'] = $this->user['uid'];
			$condition['t2.prj_class'] = 2;
			$condition['t2.prj_type'] = prjModel::PRJ_TYPE_G;
			
			${$state} = M()->table('fi_invest_fund t1')
						   ->join('inner join fi_prj t2 on t1.prj_id = t2.id')
						   ->where($condition)
						   ->count();
			$this->assign($state, ${$state});
			$totalCnt += ${$state};
		}
		
		//理财列表
		$_arr = array_values($arr);
		$where = $_arr[$status = intval(I('request.status')) - 1];
		
		$where['t2.prj_class'] = 2;
		$where['t2.prj_type'] = prjModel::PRJ_TYPE_G;
		$result = D('InvestFund')->getPrjListByUid($this->user['uid'], intval(I('get.p')), $where, 10);
		
		$paging = W('Paging', array(
				'totalRows'=> $result['total'],
				'pageSize'=> 10,
				'parameter' => '',
		), true);
		
        $this->assign('total_cnt', $totalCnt);
        $this->assign('status', $status + 1);
		$this->assign('total', $result['total']);
		$this->assign('list', $result['data']);
		$this->assign('paging', $paging);
		$this->display('ajax_fundPrjList');
	}
	
	/**
	 * 查看项目信息
	 */
	function viewFundPrj($pid = 0){
		self::_checkManagePermission();
        $prjId = $pid ? $pid : intval(I('get.pid'));
		if(!$prjId){
			$this->error('参数有误');
		}
		$investFundModel = D('InvestFund');
		if(!$data = $investFundModel->detail($prjId)){
			$this->error($investFundModel->error);
		}
		
		//解析附件
		$fundPrjMaterial = D('InvestFundMaterial')->parseMaterial($prjId, 'E041');
		$this->assign('fund_prj_data', $data);
		$this->assign('fund_prj_material', $fundPrjMaterial);
        //tab页的重用
        if(I('request.is_tab') == 1){
            $this->assign('is_tab',1);
        }
        if(I('request.is_view') == 1){
            $this->display('ajax_viewPrj');
        }
	}

    public function toAddFundPrj(){
    	$hasTenantId = A('Financing/Product')->check_tenant();
    	if($hasTenantId && in_array(IdentityConst::PROD_PUBLISH_NO, $this->user['identity_no'])) {
    		$this->assign('showProductAddMenu', 1);
    	}
    	self::_checkManagePermission();
        if($pid = intval(I('get.pid'))){
        	$prjInfo = M('prj')->where(array('id' => $pid))->field('uid, status, bid_status')->find();
        	if(!($prjInfo['status'] == PrjModel::STATUS_FAILD || in_array($prjInfo['bid_status'], array(PrjModel::BSTATUS_REPAYING, PrjModel::BSTATUS_REPAY_IN)))){
        		$this->error('当前状态不能进行此操作');
        	}elseif($prjInfo['uid'] != $this->user['uid']){
        		$this->error('你没有权限进行此操作');
        	}
        	$this->viewFundPrj();
            $this->assign('prj_id', $pid);
        }
        $this->display('fundAdd');
    }

	/**
	 * 添加项目
	 */
	public function addFundPrj(){
		self::_checkManagePermission();
		$fundModel = D('InvestFund');
		if(!IS_POST){
			$this->getInitData();
            if($pid = intval(I('get.prj_id'))){
                $this->viewFundPrj($pid);
                $this->assign('prj_id', $pid);
                $this->assign('is_edit', 1);
            }
            $bankList = D("Zhr/LoanbankAccount")->getBankListByUid($this->user['uid']);
            $this->assign('bank_list', $bankList);
            
			$this->display('fundAdd_step1');
		}else{
			$prjData = I('post.', '', 0);
			if($fundModel->create($prjData)){
				//校验信息
				self::checkRateValid($prjData['expect_rate']);
				if($prjId = intval(I('post.prj_id'))){
					$prjModel = D('PrjModel');
					$prjInfo = M('prj')->field('uid, status, bid_status')->where(array('id' => $prjId))->find();
					if($prjInfo['status'] != PrjModel::STATUS_FAILD){
						ajaxReturn(0, '当前状态不能进行此操作', 0);
					}
					if($prjInfo['uid'] != $this->user['uid']){
						ajaxReturn(0, ' 不能操作别人的项目', 0);
					}
					$prjAction = 'editPrj';
					$fundPrjAction = 'editFundPrj';
					$paramsA = array($this->user['uid'], $prjId, $prjData);
				}else{
					$prjAction = 'addPrj';
					$fundPrjAction = 'addFundPrj';
					$paramsA = array($this->user['uid'], $prjData);
				}
				if($fundModel->checkPrjNameIsUnique($prjData['prj_name'], $prjId ? $prjId : 0)){
					ajaxReturn(0, '项目名称和已有项目重复', 0);
				}
				$fundModel->startTrans();
				try {
					//添加项目信息
					$paramsB = array($this->user['uid'], $prjData);
					if(($prjId = call_user_func_array(array($fundModel, $prjAction), $paramsA)) === false
						|| (call_user_func_array(array($fundModel, $fundPrjAction), array($this->user['uid'], $prjId, $prjData)) === false)){
						throw_exception($fundModel->error);
					}
					//更新附件prjId
					if($prjData['material_id']){
						M('invest_fund_material')->where(array('id' => array('in', array_filter($prjData['material_id']))))->save(array('prj_id' => $prjId));
					}
					$fundModel->commit();
					ajaxReturn(1, '操作成功', 1);
				} catch (Exception $e) {
					$fundModel->rollback();
					ajaxReturn(0, $e->getMessage(), 0);
				}
			}else{
				ajaxReturn(0, $fundModel->getError(), 0);
			}
		}
	}
	
	/**
	 * 添加和编辑页面需要的初始换数据
	 */
	function getInitData(){
		$fundModel = D('InvestFund');
		foreach(array('fund_class' => 'E038', 'investor_class' => 'E039', 'operate_mode' => 'E040', 'payment_way' => 'E043') as $key => $value){
			$codeItems = getCodeItemList($value);
			$this->assign($key, $codeItems);
		}
		
		//需要上传的信息
		$uploadItems = getCodeItemList('E041');
		$notShowMaterial = $fundModel->notShowMaterial;
		foreach($uploadItems as $key => $item){
			if(in_array($item['code_no'], $notShowMaterial)){
				$uploadItems[$key]['not_show'] = 1;
			}
		}
		
		// 基金名称 暂时先取所有的
		$fundNameList = M('invest_fund_name')->field('distinct(fund_name) AS fund_name, id')->select();
		$this->assign('fund_name_list', $fundNameList);
		$this->assign('upload_items', $uploadItems);
	}
	
	/**
	 * 校验利率数据合法性
	 * @param array $expectRate post过来的利率数据
	 */
	public static function checkRateValid($expectRate){
		if(empty($expectRate)){
			ajaxReturn(0, '请填写预期收益率', 0);
		}else{
			$_expectRate = array();
			foreach($expectRate as $key => $rate){
				if(!preg_match('/\d+/', $rate['time_limit']) || !preg_match('/\d+(\.\d+)?/', $rate['year_rate'])){
					ajaxReturn(0, '期限或利率格式不正确', 0);
				}
				$_expectRate[] = $rate['time_limit'].$rate['time_limit_unit'];
			}
			if(count(array_unique($_expectRate)) != count($_expectRate)){
				ajaxReturn(0, '每种期限只能设置一次', 0);
			}
		}
	}
	
	/**
	 * 添加基金名称
	 */
	public function addFundName(){
		self::_checkManagePermission();
		$fundName = I('post.fund_name');
		if(empty($fundName)){
			$this->error('基金名称不能为空');			
		}
		$nowMoment = time();
		$insertData = array(
			'uid' => $this->user['uid'],
			'fund_manager' => I('post.fund_manager', ''),
			'fund_name' => $fundName,
			'fund_custodian' => I('post.fund_custodian', ''),
            'fund_ceo' => I('post.fund_ceo', ''),
			'ctime' => $nowMoment,
			'mtime' => $nowMoment,
		);
		if($id = M('invest_fund_name')->add($insertData)){
			ajaxReturn(array('id' => $id), '添加成功', 1);
		}else{
			ajaxReturn(0, '添加失败', 0);
		}
	}
	
	/**
	 * 上传材料
	 */
	public function uploadMaterial(){
		$prjId = intval(I('request.prj_id'));
		if(!($codeId = intval(I('post.record_id')))){
			ajaxReturn(0, '参数有误', 0);
		}
		$obj = D('InvestFundMaterial');
        $ret = $obj->uploadMaterial($codeId, I('file'), $prjId ? $prjId : 0);
        if($ret === false){
        	ajaxReturn(0, $fundReportsModel->errorMessage, 0);
        }
        ajaxReturn($ret, '上传成功', 1);
	}
	
	/**
	 * 删除材料
	 */
	public function deleteMaterial(){
		$id = intval(I('post.record_id'));
		if($id){
			$result = M('invest_fund_material')->where(array('id' => $id))->delete();
			if($result !== false){
				ajaxReturn($id, '删除成功', 1);
			}
		}
		ajaxReturn(0, '删除失败', 0);
	}
	
	/**
	 * 备案登记信息
	 */
	public function addInvestFundFiling(){
		if(!IS_POST){
			//需要上传的信息
			$prjId = intval(I('get.prj_id'));
			self::_checkManagePermission($prjId);
			if($prjId){
				$fundFilingData = M('invest_fund_filing')->where(array('prj_id' => $prjId))->find();
				$fundFilingData['_fund_establish_time'] = date('Y-m-d', $fundFilingData['fund_establish_time']);
				$fundFilingMaterial = D('Financing/InvestFundMaterial')->parseMaterial($prjId, 'E042');
				
// 				p($fundFilingMaterial);
				
				$this->assign('is_edit', 1);
				$this->assign('fund_filing_material', $fundFilingMaterial);
				$this->assign('fund_filing_data', $fundFilingData);
			}
			
			$uploadItems = getCodeItemList('E042');
			$this->assign('upload_items', $uploadItems);
			$this->assign('prj_id', I('request.prj_id'));
			$this->display('fundAdd_step2');
		}else{
			$post = I('post.');
			if(!$post['prj_id']){
				ajaxReturn(0, '参数有误', 0);
			}
			self::_checkManagePermission($post['prj_id']);
			if(!$this->_checkPermission($post['prj_id'], __FUNCTION__)){
				ajaxReturn(0, '当前状态不能进行此操作', 0);
			}
			$filingModel = M('InvestFundFiling');
			$data = array(
					'fund_establish_time' => $post['fund_establish_time'],
					'fund_invest_num' => $post['fund_invest_num'],
					'fund_filing_no' => $post['fund_filing_no'],
			);
			if($filingModel->create()){
				$data['fund_establish_time'] = strtotime($data['fund_establish_time']);
				$data['ctime'] = $data['mtime'] = time();
				
				if(!$filingModel->where(array('prj_id' => $post['prj_id']))->find()){
					$data['prj_id'] = $post['prj_id'];
					if($filingModel->add($data)){
						D('InvestFund')->clearFundPrjExtraInfoCache($post['prj_id']);
						ajaxReturn(1, '添加成功', 1);
					}else{
						ajaxReturn(0, '添加失败', 0);
					}
				}else{
					if($filingModel->where(array('prj_id' => $post['prj_id']))->save($data)){
						D('InvestFund')->clearFundPrjExtraInfoCache($post['prj_id']);
						ajaxReturn(1, '更新成功', 1);
					}else{
						ajaxReturn(0, '更新失败', 0);
					}
				}
			}else{
				ajaxReturn(0, $filingModel->getError(), 0);
			}
		}
	}
	
	/**
	 * 运营报告首页
	 */
    public function toFundReports(){
    	if(!($prjId = intval(I('get.prj_id')))){
    		throw_exception('参数有误');
    	}
    	self::_checkManagePermission($prjId);
        $this->assign('prj_id', $prjId);
        $this->display('fundAdd_step3');
    }
    
    /**
     * 运营报告列表
     */
    function fundReportsList(){
    	if(!($prjId = intval($_REQUEST['prj_id']))){
    		throw_exception('参数有误');
    	}
    	self::_checkManagePermission($prjId);
    	$result = D('InvestFundReports')->getReportsList($prjId, intval(I('get.p')), 10);
    	$paging = W('Paging', array(
    			'totalRows'=> $result['total'],
    			'pageSize'=> 10,
    			'parameter' => '',
    	), true);
    	
    	$this->assign('total', $result['total']);
    	$this->assign('list', $result['data']);
    	$this->assign('paging', $paging);
    	$this->display('ajax_reportList');
    }

	/**
	 * 上传运营报告
	 */
	public function uploadFundReports(){
		if(!($prjId = intval(I('post.prj_id')))){
			ajaxReturn(0, '参数有误', 0);
		}
		self::_checkManagePermission($prjId);
		if(!$this->_checkPermission($prjId, __FUNCTION__)){
			ajaxReturn(0, '当前状态不能进行此操作', 0);
		}
		$fundReportsModel = D('InvestFundReports');
        $ret = $fundReportsModel->uploadFundReports($prjId, $this->user['uid'], I('file'));
        if($ret === false){
        	ajaxReturn(0, $fundReportsModel->errorMessage, 0);
        }
        D('InvestFund')->clearFundPrjExtraInfoCache($prjId);
        ajaxReturn($ret, '上传成功', 1);
	}
	
	/**
	 * 修改运营报告状态
	 */
	public function toggleFundReportStatus(){
		
		if(!($id = intval(I('get.id')))){
			$this->error('参数有误');
		}
		$prjId = M('invest_fund_reports')->where(array('id' => $id))->getField('prj_id');
		self::_checkManagePermission($prjId);
		if(false !== ($ret = D('InvestFundReports')->toggleFundReportStatus($id, intval(I('get.status'))))){
			$prjId && D('InvestFund')->clearFundPrjExtraInfoCache($prjId);
			ajaxReturn(1, '操作成功', 1);
		}
		ajaxReturn($ret, '操作失败', 0);
	}

    /**
     * 重大事件列表
     */
    public function fundEventList(){
        $prjId = intval(I('request.prj_id'));
        if(!$prjId){
        	throw_exception('参数有误');
        }
        self::_checkManagePermission($prjId);
        $page = max(1, intval(I('get.p')));
        $list = D('InvestFundEvent')->getList($prjId, $page, 10);
        
//         p($list);exit;
        
        $paging = W('Paging', array(
            'totalRows'=> $list['total'],
            'pageSize'=> 10,
            'parameter' => '',
        ), true);
        $this->assign('total', $list['total']);
        $this->assign('list', $list['data']);
        $this->assign('paging', $paging);
        $this->display('ajax_eventList');
    }

    /**
	 * 添加重大事件披露
	 */
	function addFundEvent(){
		if(!IS_POST){
			$prjId = intval(I('get.prj_id'));
			self::_checkManagePermission($prjId);
			$this->assign('prj_id', $prjId);
			$this->display('fundAdd_step4');
		}else{
			$postData = I('post.');
			if(!($prjId = intval($postData['prj_id']))){
				ajaxReturn(0, '参数有误', 0);
			}
			self::_checkManagePermission($prjId);
			if(!$this->_checkPermission($prjId, __FUNCTION__)){
				ajaxReturn(0, '当前状态不能进行此操作');
			}
			$data['title'] = $postData['title'];
			$data['message'] = $postData['message'];
			$fundEventModel = D('InvestFundEvent');

			if($fundEventModel->create($data)){
				$now = time();
				$data = array_merge($data, array(
					'prj_id' => $prjId,
					'cuid' => $this->user['uid'],
					'status' => InvestFundEventModel::NOT_SUBMIT_STATUS,
					'ctime' => $now,
					'mtime' => $now,
				));
				if($fundEventModel->add($data)){
					D('InvestFund')->clearFundPrjExtraInfoCache($prjId);
					ajaxReturn(1, '添加成功', 1);
				}else{
					ajaxReturn(0, '添加失败', 0);
				}
			}else{
				ajaxReturn(0, $fundEventModel->getError(), 0);
			}
		}
	}
	
	/**
	 * 修改披露事件状态
	 */
	function toggleFundEventStatus(){
		if(!($id = intval(I('get.id')))){
			$this->error('参数有误');
		}
		$prjId = M('invest_fund_event')->where(array('id' => $id))->getField('prj_id');
		self::_checkManagePermission($prjId);
		if(false !== ($ret = D('InvestFundEvent')->toggleFundEventStatus($id, intval(I('get.status'))))){
			$prjId && D('InvestFund')->clearFundPrjExtraInfoCache($prjId);
			ajaxReturn(1, '操作成功', 1);
		}
		ajaxReturn($ret, '操作失败', 0);
	}
	
	/**
	 * 打包下载材料
	 */
	function packFinanceApplyMaterial(){
		$prjId = I("request.prj_id");
		D("InvestFundMaterial")->packFundPrjMaterial($prjId);
		if(MyError::hasError()){
			$this->error(MyError::lastError());
		}
		exit;
	}
	
	/**
	 * 详细信息
	 */	
	public function detail(){
		$prjId = intval(I('get.prj_id'));
		if(!$prjId){
			throw_exception('参数有误');
		}
		$investFundModel = D('InvestFund');
		if(($detailInfo = $investFundModel->detail($prjId)) === false){
			throw_exception($investFundModel->error);
		}
		$detailInfo['demand_amount'] = humanMoney($detailInfo['demand_amount']);
		$detailInfo['rate'] = $investFundModel->getPrjRateRange($prjId);
		$detailInfo['payment_way'] = getCodeItemName('E043', $detailInfo['payment_way']);
		$detailInfo['hosting_account'] = preg_replace('/(\w{4})/', '\\1 ', preg_replace('/\s+/', '', $detailInfo['hosting_account']));
		$detailInfo['fund_class'] = getCodeItemName('E038', $detailInfo['fund_class']);
		$detailInfo['investor_class'] = getCodeItemName('E039', $detailInfo['investor_class']);
		$detailInfo['operate_mode'] = getCodeItemName('E040', $detailInfo['operate_mode']);
		foreach(array('min_bid_amount', 'max_bid_amount', 'step_bid_amount') as $k){
			$detailInfo[$k] = $detailInfo[$k] == 0 ? '不限制' : number_format($detailInfo[$k], 2).'元';
		}
		//渠道客户端
		$clientTypeConfig = D('Financing/PrjFilter')->client_type_config;
		$detailInfo['client_type'] = D('Financing/PrjFilter')->getClientType($prjId);
		//投放站点
		$miNo = D("Application/Member")->getMemberList();
		if($detailInfo['status'] != 1){
			foreach ($miNo as $k => $v){
				if($v['mi_no'] == $detailInfo['mi_no']){
					$detailInfo['mi_name'] = $v['mi_name'];
					break;
				}
			}
		}
		
		$mdRewardRule = D('Application/RewardRule');
        $activities = $mdRewardRule->getRewardRuleList();
        $activitie = $mdRewardRule->getRewardRuleInfo($detailInfo['activity_id']);
        
        //上传的附件
        $material = D('InvestFundMaterial')->parseMaterial($prjId, 'E041');
		
//         p($material);
        
        $this->assign('material', $material);
        $this->assign('not_show_material', $investFundModel->notShowMaterial);
        $this->assign('client_type_config', $clientTypeConfig);
        $this->assign('mi_no', $miNo);
        $this->assign('activities', $activities);
		$this->assign('detail_info', $detailInfo);
		$this->display();
	}
	
	/**
	 * 首页列表
	 */
	public function plist(){
		
		if(!$this->user['uid'] || !D('InvestFund')->checkUserIsFundVip($this->user['uid'])){
			redirect(U('Financing/InvestFund/index'));
		}
		$prjModel = D('Financing/Prj');
		//项目状态
		$bidStatus = array(PrjModel::BSTATUS_WATING => '待开标', PrjModel::BSTATUS_BIDING => '投资中', PrjModel::BSTATUS_END => '投资结束', PrjModel::BSTATUS_REPAYING => '待赎回', PrjModel::BSTATUS_REPAID => '已赎回');
		//起投金额
		$minBidAmount = array('1' => '30万以下', '2' => '30万~100万', '3' => '100万以上');
		//项目期限
		$fundPrjDateline = array(
			'1' => '3个月以下',
			'2' => '3~6个月',
			'3' => '6~9个月',
			'4' => '9~12个月',
			'5' => '12~24个月',
			'6' => '24个月~36个月',
			'7' => '36个月以上',
		);
		
		//项目类型
		$c = intval(I('request.c'));
		$cMap = array('1' => PrjModel::PRJ_TYPE_G);
		if($c && array_key_exists($c, $cMap)){
			$condition['t1.prj_type'] = $cMap[$c];
		}
		
		$this->assign('is_id_auth', $this->user['is_id_auth']);
		$this->assign('c', $c);
		$this->assign('bid_status_map', $bidStatus);
		$this->assign('min_bid_amount_map', $minBidAmount);
		$this->assign('fund_prj_dateline_map', $fundPrjDateline);
		$this->display();
	}
	
	/**
	 * 理财项目首页列表
	 */
	public function ajaxList(){
		$prjModel = D('Financing/Prj');
		$condition['t1.status'] = PrjModel::STATUS_PASS;
		$condition['t1.prj_class'] = PrjModel::PRJ_CLASS_LC;
		
		//项目类型
		$c = intval(I('request.c'));
		$cMap = array('1' => PrjModel::PRJ_TYPE_G);
		if($c && array_key_exists($c, $cMap)){
			$condition['t1.prj_type'] = $cMap[$c];
		}
		
		$condition['t1.is_show'] = 1;
		//项目状态
		$bidStatus = intval(I('request.bid_st'));
		switch ($bidStatus) {
			case PrjModel::BSTATUS_WATING:
				$condition['t1.bid_status'] = PrjModel::BSTATUS_WATING;
				break;
			case PrjModel::BSTATUS_BIDING:
				$condition['t1.bid_status'] = PrjModel::BSTATUS_BIDING;
			break;
			case PrjModel::BSTATUS_END:
				$condition['t1.bid_status'] = array('in', array(PrjModel::BSTATUS_FULL, PrjModel::BSTATUS_END, PrjModel::BSTATUS_END_MANUAL));
			break;
			case PrjModel::BSTATUS_REPAYING:
				$condition['t1.bid_status'] = array('in', array(PrjModel::BSTATUS_REPAY_IN, PrjModel::BSTATUS_REPAYING));
			break;
			case PrjModel::BSTATUS_REPAID:
				$condition['t1.bid_status'] = PrjModel::BSTATUS_REPAID;
			break;
			default:
				;
			break;
		}
		//起投金额
		$minBidAmount = intval(I('request.min_bid_amount'));
		if($minBidAmount == 1){
			$condition['t1.min_bid_amount'] = array('lt', 30000000);
		}elseif($minBidAmount == 2){
			$condition['t1.min_bid_amount'] = array('exp', '>= 30000000 AND < 100000000');
		}elseif($minBidAmount == 3){
			$condition['t1.min_bid_amount'] = array('egt', 100000000);
		}
		//期限
		$timeLimit = intval(I('request.time_limit'));
		$timeLimitArray = array(
			'1' => array('_string' => 't2.min_dateline < 90'),
			'2' => array('_string' => '(t2.min_dateline < 90 AND t2.max_dateline >= 90) || (t2.min_dateline >= 90 AND t2.max_dateline <= 180) || (t2.min_dateline <= 180 AND t2.max_dateline > 180)'), 
			'3' => array('_string' => '(t2.min_dateline < 180 AND t2.max_dateline >= 180) || (t2.min_dateline >= 180 AND t2.max_dateline <= 270) || (t2.min_dateline <= 270 AND t2.max_dateline > 270)'), 
			'4' => array('_string' => '(t2.min_dateline < 270 AND t2.max_dateline >= 270) || (t2.min_dateline >= 270 AND t2.max_dateline <= 360) || (t2.min_dateline <= 360 AND t2.max_dateline > 360)'), 
			'5' => array('_string' => '(t2.min_dateline < 360 AND t2.max_dateline >= 360) || (t2.min_dateline >= 360 AND t2.max_dateline <= 720) || (t2.min_dateline <= 720 AND t2.max_dateline > 720)'), 
			'6' => array('_string' => '(t2.min_dateline < 720 AND t2.max_dateline >= 720) || (t2.min_dateline >= 720 AND t2.max_dateline <= 1080) || (t2.min_dateline <= 1080 AND t2.max_dateline > 1080)'), 
			'7' => array('_string' => 't2.max_dateline > 1080'), 
		);
		if(array_key_exists($timeLimit, $timeLimitArray)){
			$condition = array_merge($condition, $timeLimitArray[$timeLimit]);
		}
		$result = D('InvestFund')->getPrjList($condition, intval(I('get.p')), 10, $this->user['uid']);
		$paging = W('Paging', array(
				'totalRows'=> $result['total'],
				'pageSize'=> 10,
				'parameter' => array(
					'c' => $c,
					'bid_st' => $bidStatus,
					'min_bid_amount' => $minBidAmount,
					'time_limit' => $timeLimit
				),
		), true);
		$isFundVip = D('InvestFund')->checkUserIsFundVip($this->user['uid']);
		$this->assign('isFundVip', $isFundVip);
		$this->assign('c', $c);
		$this->assign('total', $result['total']);
		$this->assign('list', $result['data']);
		$this->assign('paging', $paging);
		$this->display();
	}
	
	/**
	 * 理财项目详情
	 */
	function view(){
		$prjId = intval(I('get.id'));
		$uid = $this->user['uid'];
		if(!$uid){
			$url = U("Financing/InvestFund/view",array('id' => $prjId));
			$this->error('请先登录!', U('Account/User/login', array('url' => $url)));
		}
		//不是高级理财会员不允许投资理财
		if(!D('InvestFund')->checkUserIsFundVip($uid)){
			$this->error('请先进行高级理财会员认证', U('Financing/InvestFund/index'));
		}
		$investFundModel = D('InvestFund');
		$prjModel = D('PrjModel');
		$investFundService = service('Financing/InvestFund');
		$projectService = service('Financing/Project');
		if(($detailInfo = $investFundModel->detail($prjId)) === false){
			throw_exception($investFundModel->error);
		}
		
		$detailInfo['rate_range_show'] = preg_replace('/([^%]+)/', '<em class="rog_num">\\1</em>', $detailInfo['rate']['rate_range']);
		$detailInfo['rate_time_show'] = preg_replace('/(\d+(\.\d*)?)/', '<em class="gred_num">\\1</em>', $detailInfo['rate']['time_range']);
		$detailInfo['demand_amount_already'] = preg_replace('/(\d+(\.\d*)?)/', '<em class="gred_num">\\1</em>', $detailInfo['demand_amount_already']);
		$material = D('InvestFundMaterial')->parseMaterial($prjId, 'E041', false);
		$notShowMaterial = array();
		foreach($material as $key => $value){
			if(in_array($value['code_no'], $investFundModel->notShowMaterial)){
				$notShowMaterial[$value['code_no']] = $value;
				unset($material[$key]);
			}
		}
		//附加信息
		$fundPrjExtraInfo = $investFundModel->getFundPrjExtraInfo($prjId);
		$detailInfo['min_bid_amount_name'] = humanMoney($detailInfo['min_bid_amount'], 2, false) . "元";
		$detailInfo['step_bid_amount_show'] = sprintf('%0.2f', $detailInfo['step_bid_amount'] / 100);
		$detailInfo['step_bid_amount_name'] = humanMoney($detailInfo['step_bid_amount'], 2, false) . "元";
		$detailInfo['max_bid_amount_name'] = $detailInfo['max_bid_amount'] > 0 ? humanMoney($detailInfo['max_bid_amount'], 2, false) . "元" : "0";
		$detailInfo['remaining_amount_name'] = $detailInfo['bid_status'] == PrjModel::BSTATUS_WATING ? humanMoney($detailInfo['demand_amount'], 2, false) : humanMoney($detailInfo['remaining_amount'], 2, false);
		$detailInfo['max_invest_money_view'] = 0;
		if($detailInfo['bid_status'] == PrjModel::BSTATUS_WATING){
			//待开标
			$detailInfo['format_start_bid_time_view'] = $detailInfo['start_bid_time'] > time() ? $detailInfo['start_bid_time'] - time() : 0;
		}elseif($detailInfo['bid_status'] == PrjModel::BSTATUS_BIDING){
			//判断会员剩余金额是否足够
			$srvFinancing = service('Financing/Financing');
			$moneyNotEnough = $srvFinancing->isBlanceLess($uid, $prjId, false, $detailInfo);
			//余额充足
			$detailInfo['schedule'] = $projectService->getSchedule($detailInfo['demand_amount'], $detailInfo['remaining_amount']);
			//开标中
			if(!$moneyNotEnough){
				//募集期剩余时间
// 				$detailInfo['remaining_daytime'] = ceil(($detailInfo['raise_dateline'] * 2592000 + $detailInfo['start_bid_time'] - time()) / 86400);
				$detailInfo['remaining_daytime'] = $investFundService->getFundPrjRemindDayTime($detailInfo);
				//最大投资金额
				
				$max_invest_money = $srvFinancing->getMaxInvestMoney($uid, $prjId, $detailInfo);
				$detailInfo['max_invest_money_view'] = floor($max_invest_money/100);
				if($max_invest_money) $max_invest_money = humanMoney($max_invest_money, 0, FALSE);
				$detailInfo['max_invest_money'] = $max_invest_money;
				
			}else{
				//余额不足
				$detailInfo['remaining_amount_avaliable'] = $srvFinancing->getUserAmount($uid);
				$detailInfo['remaining_amount_avaliable'] = number_format($detailInfo['remaining_amount_avaliable'] / 100, 2);
			}
			
			$this->assign('moneyNotEnough', $moneyNotEnough);
		}elseif($detailInfo['bid_status'] >= PrjModel::BSTATUS_FULL){
			//融资截至
			$detailInfo['is_endate'] = service("Financing/Invest")->isEndDate($detailInfo['repay_way']);
			$lastRepayDate = $detailInfo['next_repay_date'] ? $detailInfo['next_repay_date'] : $detailInfo['last_repay_date'];
			if (!$lastRepayDate) {
				$lastRepayDate = service('Financing/Project')->getPrjEndTime($prjId);
			}
			$detailInfo['last_repay_date_view'] = date('Y年m月d日', $lastRepayDate);
			$detailInfo['actual_repay_time_view'] = date('Y年m月d日', $detailInfo['actual_repay_time']);
			$detailInfo['next_repay_date_view'] = date('Y-m-d', $lastRepayDate);
			$detailInfo['bid_status_view'] = $investFundModel->getFundPrjStatus($detailInfo['bid_status']);
		}
		
		
		//协议 权益转让协议 基金合同 认证确认书
		$protocol = getCodeItemList('E012');
		$_protocol = array();
		$__protocol = array('31' => 'jjht', '32' => 'rzqrs');
		foreach ($protocol as $key => $value){
			if(in_array($value['code_no'], array_keys($__protocol))){
				$_protocol[$__protocol[$value['code_no']]] = $value;
			}
		}
		$detailInfo['protocol'] = $_protocol;
		service("Account/Active")->clickBidLog('prjid_'.$prjId, 0);
		$c = service("Financing/Project")->getPrjTypeInt($detailInfo['prj_type']);
		$this->assign('c', $c);
		
		//判断是否是新客项目
		$mustDisable = 0;
		if($detailInfo['bid_status'] == PrjModel::BSTATUS_BIDING){
			if($detailInfo['is_new']){
				$userInfo = M("user")->find($uid);
				if(!$userInfo['is_newbie']){
					$mustDisable = 1;
				}
			}
		}

		//201547增加判断是否是融资者
		if($this->user['is_invest_finance']==2) $mustDisable = 1;


		//用户有没有进行过风险测评
		$completedRiskTest = D('Financing/InvestFundRisk')->hasCompleted($uid);
		$completedRiskTest = !$completedRiskTest ? 0 : 1;
		
// 		p($fundPrjExtraInfo);
		
		$this->assign("completed_risk_test", $completedRiskTest);
		$this->assign("mustDisable", $mustDisable);
		$this->assign('detail_info', $detailInfo);
		$this->assign('material', $material);
		$this->assign('not_show_material', $notShowMaterial);
		$this->assign('fundPrjExtraInfo', $fundPrjExtraInfo);
        $this->display();
	}
	
	/**
	 * 获取预估收益值
	 */
	function getAboutIncomeByMoney(){
		$prjId = intval(I('request.prj_id'));
		//用户投资金额
		$money = str_replace(',' , '', intval(I('request.money')));
		//年利率
		$yearRate = sprintf('%0.5f', I('request.year_rate') / 1000);
		//投资时间
		$timeLimit = intval(I('request.time_limit'));
		//投资时间单位
		$timeLimitUnit = I('request.time_limit_unit');
		$prj = M('prj')->where(array('id' => $prjId))->field('start_bid_time, end_bid_time, value_date_shadow')->find();
		$investFundModel = D('Financing/InvestFund');
		$income = $investFundModel->getOrderMjqLixi(time(), $prj['end_bid_time'], $prj['value_date_shadow'], $money, $yearRate) + $investFundModel->getOrderYkqLixi($timeLimit, $timeLimitUnit, $money, $yearRate);
		$income = number_format($income, 2).'元';
		ajaxReturn(array('income' => $income));
	}
	
	/**
	 * 查看重大事件披露详情
	 */
	function viewEvent(){
		$id = intval(I('get.id'));
		$prjId = intval(I('get.prj_id'));
		$data = D('InvestFund')->getFundPrjExtraInfo($prjId, 'fund_event_data', $id, true);
		$this->assign('data', $data);
		$this->display('ajax_viewEvent');
	}
	
	/**
	 * 下载运营报告附件
	 */
	function downloadFundReport(){
		$id = intval(think_decrypt(I('get.id')));
		$fileInfo = M('invest_fund_reports')->where(array('id' => $id))->find();
		$fileParseInfo = D("Zhr/Guarantee")->parseAttach($fileInfo['path']);
		$fileUrl = 'http://'.ltrim($fileParseInfo['url'], '/');
		$fileName = D('Public/Upload')->copyRomoteFileToLocal($fileUrl, $fileInfo['save_name']);
		if(!$fileName){
			$this->error($fileName);
		}
		//下载给用户
		header('Content-Description: File Transfer');
		header('Content-type: '.$fileInfo['type']);
		header('Content-Length: '.filesize($fileName));
		if(preg_match('/MSIE/', $_SERVER['HTTP_USER_AGENT'])){
			header('Content-Disposition: attachment; filename="'.rawurlencode($fileInfo['file_name']).'"');
		}else{
			header('Content-Disposition: attachment; filename="'.$fileInfo['file_name'].'"');
		}
		readfile($fileName);
	}
	
	/**
	 * 风险测评
	 */
	function fundRiskTest(){
		if(D('Financing/InvestFundRisk')->hasCompleted($this->user['uid'])){
			ajaxReturn(0, '您已经完成风险测评', 0);
		}
		if(!IS_POST){
			$this->display('popBox_fengxian');
		}else{
			
			$isDrop = intval(I('post.is_drop'));
			$ret = I('post.ret');
			$mObj = D('Financing/InvestFundRisk');
			if(!$isDrop){
				if(!$mObj->checkValid($ret)){
					ajaxReturn(0, $mObj->getError(), 0);
				}
			}
			$result = $mObj->submit($this->user['uid'], $ret, $isDrop);
			ajaxReturn(1, $result, 1);
		}
	}

    /**
     * 投资成功
     */
    function buySuccess(){
    	$id = I("get.id", 0);
    	$prjOrderObj = D("Financing/PrjOrder");
    	$prjOrderInfo = $prjOrderObj->where(array("id" => $id))->find();
    	if ($prjOrderInfo['free_money']) {
    		$this->free_money = humanMoney($prjOrderInfo['free_money'],0,FALSE);
    		$this->tixian = $prjOrderInfo['free_tixian_times'];
    	}
    	$this->money = humanMoney($prjOrderInfo['money'],0,FALSE);
    	
    	$prjInfo = D("Financing/Prj")->where(array("id" => $prjOrderInfo['prj_id']))->find();
    	
    	//红包奖励 利率
    	$activity = service('Financing/Financing')->getActivityView($prjInfo['activity_id']);
    	if($activity && $activity['reward_money_rate']>0){
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
    	$this->prjTypeName = D('Financing/InvestFund')->getFundName($prjInfo['id']);
    	$this->orderCtime = date('Y-m-d', $prjOrderInfo['ctime']);
    	
        $this->display();
    }
	
	/**
	 * 理财产品权限判断
	 * @param number $prjId 项目id不为空 验证当前用户有没有操作此项目的权限
	 */
	private function _checkManagePermission($prjId = 0){
		if(!in_array(IdentityConst::FUND_PUBLISH_NO, $this->user['identity_no'])){
// 			$error = '你没有权限进行此操作';
		}elseif($prjId){
			$uid = M('prj')->where(array('id' => $prjId))->getField('uid');
			if($uid != $this->user['uid']){
				$error = '你没有权限进行此操作';
			}
		}
		if(!empty($error)){
			if(IS_AJAX){
				ajaxReturn(0, $error, 0);
			}else{
				$this->error($error);
			}
		}
	}
	
	/**
	 * 检查某个理财是否可以做某项操作
	 * @param int $prjId
	 * @param string $op 何种操作
	 */
	private function _checkPermission($prjId, $op){
		if(!$prjId){
			return false;
		}
		$status = M('prj')->where(array('id' => $prjId))->field('status, bid_status')->find();
		$prjModel = D('PrjModel');
		if(in_array($op, array('uploadFundReports', 'addInvestFundFiling', 'addFundEvent'))){
			if(in_array($status['bid_status'], array(PrjModel::BSTATUS_REPAYING, PrjModel::BSTATUS_REPAY_IN))){
				return true;
			}
		}
		return false;
	}
	
	/**
	 * 手动截标
	 */
	public function closePrjmanual(){
		if(!$prjId = (int)I('request.prj_id')){
			ajaxReturn(0, '参数有误', 0);
		}
		
		$now = time();
		$prjModel = D('Financing/Prj');
		$prjOrderModel = D('Financing/PrjOrder');
		$investFundService = service('Financing/InvestFund');
		$condition = array(
			'id' => $prjId,
		);
		$prj = $prjModel->where($condition)->find();
		if(!($prj['status'] == PrjModel::STATUS_PASS && in_array($prj['bid_status'], array(PrjModel::BSTATUS_BIDING, PrjModel::BSTATUS_FULL, PrjModel::BSTATUS_END)))){
			ajaxReturn(0, '当前状态不能执行此动作', 0);
		}
		
		if(service('Financing/Project')->isOrderRepayPlanAllCreated($prj['id'])){
			ajaxReturn(0, '该项目还款计划已经生成', 0);
		}
		
		$prjModel->startTrans();
		try {
			
			//如果没有冻结中的订单了，直接改为待还款状态，否则为手动截标
			$haveOrder = M('prj_order')->where(array(
				'prj_id' => $prjId,
				'status' => PrjOrderModel::STATUS_FREEZE,
			))->getField('id');
			
			$updateStatus = $haveOrder ? PrjModel::BSTATUS_END_MANUAL : PrjModel::BSTATUS_REPAYING;
			
			M('prj')->where($condition)->save(array(
				'bid_status' => $updateStatus,
			));
			
			($prj['end_bid_time'] > $now && D('Financing/Prj')->setNewEndBidTime($prjId))
			|| $investFundService->setRelateDate($prjId);
			
			//清算财务支付数据
			$investFundService->createPayStatRecord($prjId);
			//向队列推送一条生成还款计划的数据
			queue('create_repay_plan', $prjId, array('prj_id' => $prjId));
			
			$prjModel->commit();
			ajaxReturn(1, '操作成功', 1);
		} catch (Exception $e) {
			$prjModel->rollback();
			ajaxReturn(0, $e->getMessage(), 0);
		}
	}
	
	/**
	 * 根据项目ID获取投资者列表
	 */
	public function getInvestorsOfPrjByPrjId(){
		
		if(!$prjId = intval(I('request.prj_id'))){
			ajaxReturn(0, '参数不正确', 0);
		}
		
		$page = max(1, intval(I('request.p')));
		
		$list = service('Financing/InvestFund')->getInvestorsOfPrjByPrjId($prjId, $page, 10);
		
		$paging = W('Paging', array(
				'totalRows'=> $list['total'],
				'pageSize'=> 10,
				'parameter' => '',
		), true);
		
		$this->assign('total', $list['total']);
		$this->assign('list', $list['data']);
		$this->assign('paging', $paging);
		$this->display();
		
	}

    //查看项目信息，投资页
    public function viewFundPrjInfo($pid = 0){
        self::_checkManagePermission();
        $prjId = $pid ? $pid : intval(I('get.pid'));
        if(!$prjId){
            $this->error('参数有误');
        }
        $this->assign("prj_id",$prjId);
        $this->display(ajax_viewFundPrjInfo);
    }
}
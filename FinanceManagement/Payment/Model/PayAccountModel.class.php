<?php
//支付账户
class PayAccountModel extends BaseModel{
	protected $tableName = 'user_account';

    protected $yunying_adesc = '';
    protected $desc_eq_remark = false;
	
	const STATUS_NORMAL = 1;//正常
	const STATUS_FREEZE = 2;//冻结
	const STATUS_FORCEOUT = 3;//封杀
	
	const OBJ_TYPE_PRJ = 1;//项目
	const OBJ_TYPE_REBACK = 103;//退标
    const OBJ_TYPE_RECHARGE_CZ = -2;//充值冲正
	const OBJ_TYPE_RECHARGE = 2;//充值
    const OBJ_TYPE_QFXRECHARGE = 21;//企福鑫充值
    const OBJ_TYPE_QFXRECHARGE_CZ = -21;//企福鑫充值冲正
	const OBJ_TYPE_REPAYRECHARGE = 3;//还款充值
    const OBJ_TYPE_REPAYRECHARGE_CZ = -3;//还款充值冲正
	const OBJ_TYPE_DEPOSITRECHARGE = 31;//保证金充值
    const OBJ_TYPE_DEPOSITRECHARGE_CZ = -31;//保证金充值冲正
	const OBJ_TYPE_ZRRECHARGE = 32;//直融保证金充值
    const OBJ_TYPE_ZRRECHARGE_CZ = -32;//直融保证金充值冲正
	const OBJ_TYPE_CASHOUT = 4;//提现申请(冻结)
	const OBJ_TYPE_CASHOUTDONE = 401;//提现完成(解冻)
	const OBJ_TYPE_CASHOUTFAIL = 402;//提现失败
	const OBJ_TYPE_CASHOUTPRJDONE = 403;//提现完成(商户)
	const OBJ_TYPE_TRANSFER = 5;//传让
	const OBJ_TYPE_REPAY = 6;//回款 //如果merchant_id有值，那么说明是暂时代扣
    const OBJ_TYPE_REPAY_FREEZE = 61;//回款冻结 //如果merchant_id有值，那么说明是暂时代扣

    const OBJ_TYPE_PAYFREEZE = 7; // 支付冻结(amount -> buy_freeze_money)
    const OBJ_TYPE_PAY = 71; // 支付(buy_freeze_money -> amount)

    const OBJ_TYPE_JOIN_IN_X = 72;          // 加入司马小鑫(self.amount -> self.amountx)
    const OBJ_TYPE_PAYFREEZE_X = 73;        // 支付冻结, 司马小鑫用户(self.amountx -> self.buy_freeze_moneyx)
    const OBJ_TYPE_PAY_X = 74;              // 支付, 司马小鑫用户购买普通项目/平台发布的债权/司马小鑫外发布的债权(buyer.buy_freeze_moneyx -> seller.amount)
    const OBJ_TYPE_PAY_X_TRANSFER = 75;     // 支付, 司马小鑫用户购买司马小鑫用户发布的债权(buyer.buy_freeze_moneyx -> seller.amountx)
    const OBJ_TYPE_GIVEXREWARD = 76;    //司马小鑫奖励(红包、满减券)，从平台账户划账到用户的amount上
    //76X是司马小鑫结算用到的类型
    const OBJ_TYPE_XRPJ_SETTLEMENT = 760;//司马小鑫结算 从用户amountx到用户amount
    const OBJ_TYPE_XRPJ_BALANCE_INCOME = 761;//多退 从用户amountx退到平台多退账号amount
    const OBJ_TYPE_XRPJ_BALANCE_OUT = 762;//少补 从平台少补账号amount转到用户amountx
    const OBJ_TYPE_XRPJ_RATE_ADDON = 763;// 资金加成 从平台奖励用户amount转到用户amount
    const OBJ_TYPE_XRPJ_ACTIVITY = 764;// 活动奖励 从平台加成账号amount转到用户amount
    const OBJ_TYPE_XPRJ_JIAXI_ADDON = 765; //加息奖励 从平台加成账号amount转到用户amount

    const OBJ_TYPE_TRANSFERMONEY = 8;//转账
	const OBJ_TYPE_RAISINGINCOME = 81;//给募集期利（从募集账户转让到用户）
	const OBJ_TYPE_REVISION = 9;//冲正
	
	const OBJ_TYPE_REWARDFREEMONEY = 90002;//奖励抵用券
	
	const OBJ_TYPE_REWARD = 10;//奖励
	const OBJ_TYPE_REWARD_CASHOUT = 101;//可以提现奖励
	const OBJ_TYPE_REWARD_CASHOUTX = 1011;//司马小鑫奖励到amountx
    const OBJ_TYPE_REWARD_ZS = 1018;//托管项目奖励到zs_amount
	const OBJ_TYPE_REWARD_INVEST = 102;//投资后可提现奖励
	const OBJ_TYPE_REWARD_CBACK = 1013;//可以提现奖励过期
	const OBJ_TYPE_REWARD_IBACK = 1023;//投资后可提现奖励过期
	const OBJ_TYPE_PENALTY = 11;//罚款
	
	const OBJ_TYPE_SHOUXU = 12;//内部手续费
    const OBJ_TYPE_REPAY_SHOUXU = 1201;//转账
	const OBJ_TYPE_OUTSHOUXU = 13;//外部手续费

    const OBJ_TYPE_SHOUXU_SDT = 12002;//速兑通手续费
	
	const OBJ_TYPE_ALLOT = 14;//划拨
    const OBJ_TYPE_CUTINREPAY = 1401;//划拨
	const OBJ_TYPE_ROLLOUT = 15;//转出支付余额资金 ,现在只给还款用，其他用途用其他字段
	const OBJ_TYPE_ROLLOUTX = 1501;//转出司马小鑫余额资金 ,现在只给还款用，其他用途用其他字段
	const OBJ_TYPE_ROLLOUT_ZS = 1502;//转出支付余额资金 浙商托管

    const OBJ_TYPE_USER_BONUS = 16002;//新红包使用
    const OBJ_TYPE_CANEL_BONUS = 16003;//新红包核销
    const OBJ_TYPE_USER_CASH_BONUS = 16004;//使用现金红包

    const OBJ_TYPE_USER_TYJ_CASH_BONUS = 16005;//使用体验金红包
	
	const OBJ_TYPE_ADD_COUPON = 17001;//奖励券
	const OBJ_TYPE_USE_COUPON = 17002;//使用券
	const OBJ_TYPE_EXPIRE_COUPON = 17003;//过期券

    const OBJ_TYPE_STF_BACK = 18001;//速兑通变现错误，流标特殊处理

	const OBJ_TYPE_MI_PAY = 20001;//渠道商户支付

	const OBJ_PT_SERVICE_FEE = 20002; //平台服务费

    const OBJ_TYPE_JAVA_BONUS_IN = 30001; //红包系统1.0 (奖励入账(红包)－>融资者)
    const OBJ_TYPE_JAVA_BONUS_OUT = 30002;//红包系统1.0 (奖励出账(红包)－>平台用户)

    const OBJ_TYPE_REDUCE_IN = 40001;//满减券 (奖励入账入账(满减券)－>融资者)
    const OBJ_TYPE_REDUCE_OUT = 40002;//满减券 (奖励出账(满减券)－>平台用户)

    const OBJ_TYPE_RATE_IN = 50001;//加息券 (奖励入账入账(加息券)－>融资者)
    const OBJ_TYPE_RATE_OUT = 50002;//加息券 (奖励出账(加息券)－>平台用户)

    const OBJ_TYPE_FINENCE_MANAGE_FEE = 20003; //融资管理费
    const OBJ_TYPE_GUARANTEE_FEE = 20004; //保证金
    const OBJ_TYPE_GUWEN_FEE = 20005; //融资顾问费
    const OBJ_TYPE_ZC_ZHINAJIN = 20006; //追偿滞纳金
    const OBJ_TYPE_ZC_PRI_INTEREST = 20007; //追偿回款本息
    const OBJ_TYPE_ZC_FAXI = 20008; //追偿罚息

    //浙商存管
    const OBJ_TYPE_ZS_BUY_FREEZE = 770; //存管户投资冻结
    const OBJ_TYPE_ZS_RECHARGING = 771; //充值在途
    const OBJ_TYPE_ZS_REPAYING = 772; //托管项目回款在途 repay_freeze_money => zs_repaying_money
    const OBJ_TYPE_ZS_RRPAIED = 773; //托管项目回款到余额账户 zs_repaying_money => zs_amount(暂不司马小鑫)
    const OBJ_TYPE_ZS_MOVING = 774; //托管项目存量转移在途 amount => zs_moving_money
    const OBJ_TYPE_ZS_MOVED = 775; //托管项目存量到余额账户 zs_moving_money => zs_amount(暂不司马小鑫)

    //金交所
    const OBJ_TYPE_JJS_TRANSFERMONEY =88;//金交所转账到SPV机构账户

    protected function _initialize() {
        parent::_initialize();
        D('Payment/Ticket');
    }
	
// 	//修改基本信息
// 	public function saveBaseInfo(){
		
// 	}

    /**
     * 类型显示
     */
    public function getTypeView()
    {
        return array(
            self::OBJ_TYPE_JAVA_BONUS_IN => '投资者的奖励入账(红包)',
            self::OBJ_TYPE_JAVA_BONUS_OUT => '投资者的奖励出账(红包)',
            self::OBJ_TYPE_REDUCE_IN => '投资者的奖励入账(满减券)',
            self::OBJ_TYPE_REDUCE_OUT => '投资者的奖励出账(满减券)',
            self::OBJ_TYPE_RATE_IN => '投资者的奖励入账(加息券)',
            self::OBJ_TYPE_RATE_OUT => '投资者的奖励出账(加息券)',
            self::OBJ_TYPE_ZC_ZHINAJIN => '追偿滞纳金',
            self::OBJ_TYPE_ZC_PRI_INTEREST => '追偿回款本息',
            self::OBJ_TYPE_ZC_FAXI => '追偿罚息',
            self::OBJ_TYPE_ZS_MOVING => '余额转移',
            self::OBJ_TYPE_ZS_MOVED => '余额转移',
        );
    }
	
	public function save($data='',$options=array()){
		if(!$data) throw_exception("保存异常，需要传入参数data");
		$uid = $data['uid'];
		$version = $data['version'];
		$data['version'] = $data['version'] + 1;
		
		$data['mtime'] = time();
		$res = M("user_account")->where(array("uid"=>$uid, "version"=>$version))->save($data);
		
		if(!$res){
			//写日志文件
			$user_account = M('user_account')->find($uid);
			
			$path = SITE_DATA_PATH . "/logs/account_err/".date('Y')."/".date('m');
			$logPath = $path."/".date('d').".txt";
			if(!is_dir($path)) mkdir($path,0777,true);
			
			$logstr = $uid.'-db:'.$user_account['version'].'-condition:'.$version.'-'.$data['mtime'];
			$logstr .= ";\r\n";		
			file_put_contents($logPath, $logstr,FILE_APPEND);
		}
		
		return $res;
		
	}
	
	public function getFundAccountList($uid){
		$list = M('fund_account')->where(array('uid'=>(int)$uid))->select();
		return $list;
	}
	
	public function getFundAccount($uid, $id){
// 		$fund_account = M('fund_account')->find((int)$id);
		$sql = "SELECT t1.*, t2.name_cn province_name, t3.name_cn city_name FROM fi_fund_account t1 
				LEFT JOIN fi_dict_city t2 ON t1.bank_province = t2.code
				LEFT JOIN fi_dict_city t3 ON t1.bank_city = t3.code
				WHERE t1.id = '{$id}'";
		$fund_account = M('model')->query($sql);
		$fund_account = $fund_account[0];
		if(!$fund_account || $fund_account['uid'] != $uid) return array();
		return $fund_account;
	}
	//增/改三方账户信息 $channel先做字符串
	public function saveFundAccount($uid, $account_no, $channel, $acount_name, $bank, $bank_name, $bank_province, $bank_city, $sub_bank_id, $is_default = 0, $sub_bank, $id=0, $is_init=0){
		$uid = (int)$uid;
//		$this->legalCheck($uid);
		$data['account_no'] = $account_no;
		$data['channel'] = $channel;
		$data['acount_name'] = $acount_name;
		$data['bank'] = $bank;
		$data['bank_name'] = $bank_name;//名称
		$data['bank_province'] = $bank_province;
		$data['bank_city'] = $bank_city;
		$data['is_default'] = $is_default;
		$data['sub_bank'] = $sub_bank;
		$data['sub_bank_id'] = $sub_bank_id;
		$data['mtime'] = time();
		//如果选为默认银行卡，则取消其他银行卡的默认

        $user = M('user')->find($uid);
        $fund_account_arr = M('fund_account')->where(array('mi_no'=>$user['mi_no'], 'account_no'=>$account_no))->select();
        if( $fund_account_arr ) {
            foreach ($fund_account_arr as $fund_account_x) {
                if ($fund_account_x['uid'] != $uid) {
                    $user_x = M('user')->find($fund_account_x['uid']);
                    if ($user_x['person_id'] != $user['person_id']) {
                        throw_exception("该银行卡已经被其他用户绑定了");
                    }
                }
            }
        }
        //港澳台用户客服修改通行证号不走redis验证
        if($user['uid_type'] == 1 && $user['is_invest_finance'] == 1 && !$user['special_usertype']) {
            import_addon("libs.Cache.RedisSimply");
            $redis = new RedisSimply('bankFundtwo');
            if ($redis->get($account_no) && $redis->get($account_no) != $user['person_id']) {
                M('user_account')->save(array('uid' => $user['uid'], 'status' => 2, 'mtime' => time()));
                D("Admin/ActionLog")->insertLog($user['uid'], '绑卡异常账户被冻结', $account_no, $user['person_id']);
                throw_exception("绑卡异常，账户被冻结，请联系客服");
            }
        }

		if($is_default){
			M('fund_account')->where('uid = '.$uid)->save(array('is_default' => 0, 'mtime'=>time()));
		}
        $this->startTrans();
        try {
            if($id){
                $id = (int)$id;
                $fund_account = M('fund_account')->find($id);
                if(!$fund_account) throw_exception("账户不存在");
                if($fund_account['uid'] != $uid) throw_exception("没有权限");
                if($fund_account['account_no'] == $account_no) {
                    if ($fund_account['recharge_bank_id']) {
//                        $recharge_bank_no = M('recharge_bank_no')->find($fund_account['recharge_bank_id']);
//                        if ( $recharge_bank_no && ($recharge_bank_no['cashout_amount'] || $recharge_bank_no['freeze_cashout_amount']) ) {
//                            throw_exception("该银行卡上还有手机的提现额度，不能删除，请提现额度用完后再删除");
//                        }
//                        $con1 = M('recharge_bank_no')->where(array('id' => $fund_account['recharge_bank_id']))->delete();
//                        if (!$con1) throw_exception("删除失败");
//
//                        $has_re = M('recharge_bank_no')->where(array('account_no'=>$account_no
//                            , 'bank'=>$bank, 'uid'=>$uid))->find();
//                        $data['recharge_bank_id'] = 0;
//                        if($has_re) $data['recharge_bank_id'] = $has_re['id'];
                        $recharge_bank_data['is_default'] = $is_default;
                        $recharge_bank_data['id'] = $fund_account['recharge_bank_id'];
                        $recharge_bank_data['account_no'] = $data['account_no'];
                        $recharge_bank_data['channel'] = $data['channel'];
                        $recharge_bank_data['acount_name'] = $data['acount_name'];
                        $recharge_bank_data['bank'] = $data['bank'];
                        $recharge_bank_data['bank_name'] = $data['bank_name'];
                        $recharge_bank_data['sub_bank'] = $data['sub_bank'];
                        $recharge_bank_data['mtime'] = $data['mtime'];
                        M('recharge_bank_no')->save($recharge_bank_data);
                    }
                }
                $data['id'] = $id;
                M('fund_account')->save($data);
            } else {
                $h_fund_account = M('fund_account')->where(array('uid'=>$uid, 'account_no'=>$account_no))->find();
                if($h_fund_account) throw_exception("该卡已经绑定过，不能再次绑定");
                $data['uid'] = $uid;
                $data['ctime'] = time();
                $data['is_active'] = 1;
                $data['is_init'] = $is_init;
                $has_re = M('recharge_bank_no')->where(array('account_no'=>$account_no
                , 'bank'=>$bank, 'uid'=>$uid))->find();
                $data['recharge_bank_id'] = 0;
                if($has_re) $data['recharge_bank_id'] = $has_re['id'];
                if(!$data['recharge_bank_id']) {
                    $recharge_bank_data['uid'] = $data['uid'];
                    $recharge_bank_data['account_no'] = $data['account_no'];
                    $recharge_bank_data['channel'] = $data['channel'];
                    $recharge_bank_data['acount_name'] = $data['acount_name'];
                    $recharge_bank_data['bank'] = $data['bank'];
                    $recharge_bank_data['bank_name'] = $data['bank_name'];
                    $recharge_bank_data['sub_bank'] = $data['sub_bank'];
                    $recharge_bank_data['ctime'] = $data['ctime'];
                    $recharge_bank_data['mtime'] = $data['mtime'];
                    $recharge_bank_data['sub_bank_id'] = $data['sub_bank_id'] ? $data['sub_bank_id'] : 0;
                    $recharge_bank_data['bank_province'] = $data['bank_province'] ? $data['bank_province'] : 0;
                    $recharge_bank_data['bank_city'] = $data['bank_city'] ? $data['bank_city'] : 0;
                    $id = M('recharge_bank_no')->add($recharge_bank_data);
                    if (!$id) throw_exception("绑卡失败，请重试");
                    $data['recharge_bank_id'] = $id;
                } else{
                    $recharge_bank_data['id'] = $data['recharge_bank_id'];
                    $recharge_bank_data['account_no'] = $data['account_no'];
                    $recharge_bank_data['channel'] = $data['channel'];
                    $recharge_bank_data['acount_name'] = $data['acount_name'];
                    $recharge_bank_data['bank'] = $data['bank'];
                    $recharge_bank_data['bank_name'] = $data['bank_name'];
                    $recharge_bank_data['sub_bank'] = $data['sub_bank'];
                    $recharge_bank_data['mtime'] = $data['mtime'];
                    M('recharge_bank_no')->save($recharge_bank_data);
                }
                $id = M('fund_account')->add($data);
                if($id){
                    if($uid >= 10){
                        $user_account = M("user_account")->find($uid);
                        if(!$user_account['is_merchant']) service("Index/ActivitySet")->regSet($uid,ActivitySetService::TYPE_BAND_BANK_NO);
                    }
                }
                else throw_exception("保存失败");
            }
            $this->commit();
            //港澳台用户客服修改通行证号不走redis验证
            if($user['uid_type'] == 1 && $user['is_invest_finance'] == 1 && !$user['special_usertype']) {
                $redis->set($account_no, $user['person_id']);
            }

            return array('boolen' => 1, 'message' => '保存成功', 'id'=>$id);
        }catch(Exception $e){
            $this->rollback();
            return array('boolen' => 0, 'message' => $e->getMessage());
        }
	}
	
    public function saveAppFundAccount($uid, $account_no, $channel, $acount_name, $bank, $bank_name, $sub_bank, $account_id){
        $uid = (int)$uid;
//        $this->legalCheck($uid);
        $data['account_no'] = $account_no;
        $data['channel'] = $channel;
        $data['acount_name'] = $acount_name;
        $data['bank'] = $bank;
        $data['bank_name'] = $bank_name;//名称
        $data['sub_bank'] = $sub_bank;
//         $data['sub_bank_id'] = $sub_bank_id;
        $data['mtime'] = time();

        $user = M('user')->find($uid);
        $fund_account_arr = M('fund_account')->where(array('mi_no'=>$user['mi_no'], 'account_no'=>$account_no))->select();
        if( $fund_account_arr ) {
            foreach ($fund_account_arr as $fund_account_x) {
                if ($fund_account_x['uid'] != $uid) {
                    $user_x = M('user')->find($fund_account_x['uid']);
                    if ($user_x['person_id'] != $user['person_id']) {
                        throw_exception("该银行卡已经被其他用户绑定了");
                    }
                }
            }
        }

        if($user['uid_type'] == 1 && $user['is_invest_finance'] == 1) {
            import_addon("libs.Cache.RedisSimply");
            $redis = new RedisSimply('bankFundtwo');
            if ($redis->get($account_no) && $redis->get($account_no) != $user['person_id']) {
                M('user_account')->save(array('uid' => $user['uid'], 'status' => 2, 'mtime' => time()));
                D("Admin/ActionLog")->insertLog($user['uid'], '绑卡异常账户被冻结', $account_no, $user['person_id']);
                throw_exception("绑卡异常，账户被冻结，请联系客服");
            }
        }

        $this->startTrans();
        try {
            if ($id = $account_id) {
                $id = (int)$id;
                $fund_account = M('fund_account')->find($id);
                if (!$fund_account) throw_exception("账户不存在");
                if ($fund_account['uid'] != $uid) throw_exception("没有权限");
                if ($fund_account['account_no'] != $account_no) {
                    if ($fund_account['recharge_bank_id']) {
                        $recharge_bank_no = M('recharge_bank_no')->find($fund_account['recharge_bank_id']);
                        if ( $recharge_bank_no && ($recharge_bank_no['cashout_amount'] || $recharge_bank_no['freeze_cashout_amount'])) {
                            throw_exception('该银行卡上还有手机的提现额度，不能删除，请提现额度用完后再删除');
                        }
                        $con1 = M('recharge_bank_no')->where(array('id' => $fund_account['recharge_bank_id']))->delete();
                        if (!$con1) throw_exception('删除失败');

                        $recharge_bank_data['id'] = $fund_account['recharge_bank_id'];
                        $recharge_bank_data['account_no'] = $data['account_no'];
                        $recharge_bank_data['channel'] = $data['channel'];
                        $recharge_bank_data['acount_name'] = $data['acount_name'];
                        $recharge_bank_data['bank'] = $data['bank'];
                        $recharge_bank_data['bank_name'] = $data['bank_name'];
                        $recharge_bank_data['sub_bank'] = $data['sub_bank'];
                        $recharge_bank_data['mtime'] = $data['mtime'];
                        M('recharge_bank_no')->save($recharge_bank_data);
                    }
                }
                $data['id'] = $id;
                M('fund_account')->save($data);
            } else {
                $h_fund_account = M('fund_account')->where(array('uid'=>$uid, 'account_no'=>$account_no))->find();
                if($h_fund_account) throw_exception("该卡已经绑定过，不能再次绑定");
                $data['uid'] = $uid;
                $data['ctime'] = time();
                $data['is_active'] = 1;
                $has_re = M('recharge_bank_no')->where(array('account_no' => $account_no
                    , 'uid' => $uid))->find();
                $data['recharge_bank_id'] = 0;
                if ($has_re) $data['recharge_bank_id'] = $has_re['id'];
//             $data['is_init'] = $is_init;
                if(!$data['recharge_bank_id']) {
                    $recharge_bank_data['uid'] = $data['uid'];
                    $recharge_bank_data['account_no'] = $data['account_no'];
                    $recharge_bank_data['channel'] = $data['channel'];
                    $recharge_bank_data['acount_name'] = $data['acount_name'];
                    $recharge_bank_data['bank'] = $data['bank'];
                    $recharge_bank_data['bank_name'] = $data['bank_name'];
                    $recharge_bank_data['sub_bank'] = $data['sub_bank'];
                    $recharge_bank_data['ctime'] = $data['ctime'];
                    $recharge_bank_data['mtime'] = $data['mtime'];
                    $id = M('recharge_bank_no')->add($recharge_bank_data);
                    if (!$id) throw_exception("绑卡失败，请重试");
                    $data['recharge_bank_id'] = $id;
                } else{
                    $recharge_bank_data['id'] = $data['recharge_bank_id'];
                    $recharge_bank_data['account_no'] = $data['account_no'];
                    $recharge_bank_data['channel'] = $data['channel'];
                    $recharge_bank_data['acount_name'] = $data['acount_name'];
                    $recharge_bank_data['bank'] = $data['bank'];
                    $recharge_bank_data['bank_name'] = $data['bank_name'];
                    $recharge_bank_data['sub_bank'] = $data['sub_bank'];
                    $recharge_bank_data['mtime'] = $data['mtime'];
                    M('recharge_bank_no')->save($recharge_bank_data);
                }
                $id = M('fund_account')->add($data);
                if ($id) {
                    if ($uid >= 10) {
                        $user_account = M("user_account")->find($uid);
                        if (!$user_account['is_merchant']) service("Index/ActivitySet")->regSet($uid, ActivitySetService::TYPE_BAND_BANK_NO);
                    }
                } else throw_exception('保存失败');
            }
            $this->commit();
            if($user['uid_type'] == 1 && $user['is_invest_finance'] == 1){
                $redis->set($account_no, $user['person_id']);
            }
            return array('boolen' => 1, 'message' => '保存成功');
        }catch(Exception $e){
            $this->rollback();
            return array('boolen' => 0, 'message' => $e->getMessage());
        }
	}
	
	//删除三方账户信息
	public function delFundAccount($uid, $id){
		$this->legalCheck($uid);
		$fund_account = M('fund_account')->find($id);
		if(!$fund_account) throw_exception("账户不存在");
		if($fund_account['uid'] != $uid) throw_exception("不是您的账户");
        $this->startTrans();
        try {
            if($fund_account['recharge_bank_id']){
                $recharge_bank_no = M('recharge_bank_no')->find($fund_account['recharge_bank_id']);
                if ( $recharge_bank_no && ($recharge_bank_no['cashout_amount'] || $recharge_bank_no['freeze_cashout_amount'])) {
                    return throw_exception("该银行卡上还有手机的提现额度，不能删除，请提现额度用完后再删除");
                }
                $con1 = M('recharge_bank_no')->where(array('id'=>$fund_account['recharge_bank_id']))->delete();
                if(!$con1) throw_exception("删除失败x");
            }
            $con = M('fund_account')->where("id=".$id)->delete();
            if(!$con) throw_exception("删除失败,请重试");
            $this->commit();
            service('Account/AccountBank')->unbindQueue(array('uid' => $uid, 'cardno' => $recharge_bank_no['account_no']));
            return array('boolen'=>1, 'message'=>'删除成功', 'data'=>$recharge_bank_no);
        }catch(Exception $e){
            $this->rollback();
            return array('boolen' => 0, 'message' => $e->getMessage());
        }
	}
	//激活三方账户信息
	public function activeFundAccount($uid, $id){
		$this->legalCheck($uid);
		$fund_account = M('fund_account')->find($id);
		if(!$fund_account) throw_exception("账户不存在");
		if($fund_account['uid'] != $uid) throw_exception("不是您的账户");
		$data['is_active'] = 1;
		$data['id'] = $id;
		$data['mtime'] = time();
		M('fund_account')->save($data);
		return array('boolen'=>1, 'message'=>'成功激活');
	}
	//禁用三方账户信息
	public function unactiveFundAccount($uid, $id){
		$this->legalCheck($uid);
		$fund_account = M('fund_account')->find($id);
		if(!$fund_account) throw_exception("账户不存在");
		if($fund_account['uid'] != $uid) throw_exception("不是您的账户");
        if($fund_account['recharge_bank_id']){
            $recharge_bank_no = M('recharge_bank_no')->find($fund_account['recharge_bank_id']);
            if ( $recharge_bank_no && ($recharge_bank_no['cashout_amount'] || $recharge_bank_no['freeze_cashout_amount'])) {
                return array('boolen'=>0, 'message'=>'该银行卡上还有手机的提现额度，不能删除，请提现额度用完后再删除');
            }
        }
		$data['is_active'] = 0;
		$data['id'] = $id;
		$data['mtime'] = time();
		M('fund_account')->save($data);
		return array('boolen'=>1, 'message'=>'处理成功');
	}
	
	//指给CashoutApplyModel使用
	public function cancelCashout($uid, $cashout_apply){
		$uid = (int)$uid;
		$this->legalCheck($uid);
		if($cashout_apply['uid'] != $uid){
			 throw_exception("没有权限");
		}
		$account_money = $cashout_apply['money'] + $cashout_apply['fee'];
// 		$key = "cash_freeze_money_{$uid}";
		if($this->getCashFreezeMoneyCache($uid) && $this->getCashFreezeMoneyCache($uid) < $account_money) {// cache里面为了不为0，所有money都+1
			return array('boolen'=>0, 'message'=>'您的余额不足3');
		}
		$user_account = M('user_account')->find($uid);
		if($this->getCashFreezeMoneyCache($uid) && (($user_account['cash_freeze_money']) != $this->getCashFreezeMoneyCache($uid)) ){
			throw_exception("取消提现 暂未提交成功，请重试");
		}

		if($user_account['cash_freeze_money'] < $account_money){
			throw_exception("没有这么多提现金额，处理异常");
		}
		
		try{
			$user_account_data['uid'] = $uid;
			$user_account_data['amount'] = $user_account['amount'] + $account_money - $cashout_apply['reward_money'];
			$user_account_data['reward_money'] = $user_account['reward_money'] + $cashout_apply['reward_money'];
			$user_account_data['cash_freeze_money'] = $user_account['cash_freeze_money'] - $account_money;
			$user_account_data['free_money'] = $user_account['free_money'] + $cashout_apply['free_money'];
			$user_account_data['cumu_cashout_times'] = $user_account['cumu_cashout_times'] - 1; //提现次数还回去
			if($cashout_apply['free_tixian_times']){
				$user_account_data['free_tixian_times'] = $user_account['free_tixian_times'] + $cashout_apply['free_tixian_times']; //免费手续费次数还回去
			}

			$user_account_data['mtime'] = time();
// 			$con = M('user_account')->save($user_account_data);

			$user_account_data['version'] = $user_account['version'];
			$con = D("Payment/PayAccount")->save($user_account_data);
			if(!$con) {
				throw_exception("系统异常，请重新再试");
			}
			
			D('Payment/Ticket');
			$this->addRecord($uid, self::OBJ_TYPE_CASHOUT, $cashout_apply['id'], TicketModel::INCOMING_FLAG, $account_money,
					array(
							'amount'=>array('from'=>$user_account['amount'], 'to'=>$user_account_data['amount']),
							'reward_money'=>array('from'=>$user_account['reward_money'], 'to'=>$user_account_data['reward_money']),
							'cash_freeze_money'=>array('from'=>$user_account['cash_freeze_money'], 'to'=>$user_account_data['cash_freeze_money']),
							'free_money'=>array('from'=>$user_account['free_money'], 'to'=>$user_account_data['free_money']),
					)
					, array('cash_id'=>$cashout_apply['id']), '', '');
	// 		cache($key, $user_account_data['cash_freeze_money']+1, array('expire'=>60));
		} catch(Exception $e){throw $e;}
		$this->setAmountCache($uid, $user_account_data['amount']);
		$this->setRewardCache($uid, $user_account_data['reward_money']);
		$this->setCashFreezeMoneyCache($uid, $user_account_data['cash_freeze_money']);
		return true;
	}
	//申请提现 $money实际到账的金额 $fee费用 $money包含了$reward_money
    public function applyCashout($uid, $money, $fee, $reward_money, $channel, $bank, $sub_bank, $bak, $free_times, $free_money, $cost_saving, $out_account_no, $out_account_id = '', $free_tixian_times = 0, $source = 1, $no_auto = 0, $no_role = true,$zs_code='')
    {
		$uid = (int)$uid;
		$money = $money;
		$reward_money = $reward_money;
		$fee = $fee;
		$account_money = $money + $fee - $reward_money;
		$this->legalCheck($uid);
		
		if($this->getAmountCache($uid) && $this->getAmountCache($uid) < $account_money) {// cache里面为了不为0，所有money都+1
			return array('boolen'=>0, 'message'=>'您的余额不足（EC:2001）');
		}
		if($this->getRewardCache($uid) && $this->getRewardCache($uid) < $reward_money) {// cache里面为了不为0，所有money都+1
			return array('boolen'=>0, 'message'=>'您的奖金余额不足（EC:2002）');
		}
		$cacheKey = "applyCashout_".$uid."_".$money."_".$reward_money;
		$this->startTrans($cacheKey);
		try{
			$user_account = M('user_account')->find($uid);
            $amount_key = 'amount';
            $reward_money_key = 'reward_money';
            $cash_freeze_money = 'cash_freeze_money';
			if( $channel ==  PaymentService::TYPE_ZHESHANG ){
                $amount_key = 'zs_amount';
                $reward_money_key = 'zs_reward_money';
                $cash_freeze_money = 'zs_cash_freeze_money';
            }
			if($this->getAmountCache($uid) && ($user_account['amount'] != $this->getAmountCache($uid)) ){
				throw_exception("申请提现,暂未提交成功，请重试（EC:2003）");
			}
			if($this->getRewardCache($uid) && ($user_account['reward_money'] != $this->getRewardCache($uid)) ) {
				throw_exception("申请提现 暂未提交成功，请重试（EC:2004）");
			}
// 			if($this->getCashoutTimes($user_account) < 1) return array('boolen'=>0, 'message'=>'提现次数不足, 请先充值获取更多提现次数');
			
			if($user_account[$amount_key] < $account_money){
				return array('boolen'=>0, 'message'=>$user_account[$amount_key].":".$account_money.'您的余额不足（EC:2005）');
			}
			if($user_account[$reward_money_key] < $reward_money){
				return array('boolen'=>0, 'message'=>'您的奖金余额不足（EC:2006）');
			}
			
			$user_college_ext = M('user_college_ext')->find($uid);
			if($user_college_ext && $user_college_ext['coll_amount']){
				if($user_account['amount'] - $user_college_ext['coll_amount'] < $account_money){
					return array('boolen'=>0, 'message'=>'您的余额不足,不能提现大学生账户金额（2007）');
				}
			}
				
			$user_account_data['uid'] = $uid;
			$user_account_data[$amount_key] = $user_account[$amount_key] - $account_money;
			$user_account_data[$reward_money_key] = $user_account[$reward_money_key] - $reward_money;
			if($free_times){
				$user_account_data['free_times'] = $user_account['free_times'] - 1;
				$free_money = 0;
			} else {
				$user_account_data['free_money'] = $user_account['free_money'] - $free_money;
			}
			$user_account_data[$cash_freeze_money] = $user_account[$cash_freeze_money] + $account_money + $reward_money;
			$user_account_data['cumu_cashout_times'] = $user_account['cumu_cashout_times'] + 1;//累计提现次数
			$user_account_data['mtime'] = time();
// 			$con = M('user_account')->save($user_account_data);
			$user_account_data['version'] = $user_account['version'];
			$con = D("Payment/PayAccount")->save($user_account_data);
			if($free_tixian_times){
				service("Payment/PayAccount")->descFreeTixianTimes($uid);
			}
			if(!$con) {
				$this->rollback($cacheKey);
				return array('boolen'=>0, 'message'=>"系统异常，请重新再试（EC:2008）");
			}

            $cashoutId = D('Payment/CashoutApply')->apply($uid, $money, $fee, $reward_money, $channel, $bank, $sub_bank, $bak, $free_times, $free_money, $cost_saving, $out_account_no, $out_account_id, $free_tixian_times, $source, $no_auto, $no_role,$zs_code);
			if(!$cashoutId) {
				$this->rollback($cacheKey);
				return array('boolen'=>0, 'message'=>"系统异常，请重新再试（EC:2009）");
			}
            D('Payment/Ticket');
			$this->addRecord($uid, self::OBJ_TYPE_CASHOUT, $cashoutId, TicketModel::OUTGOING_FLAG, ($account_money + $reward_money),
					array(
						$amount_key=>array('from'=>$user_account[$amount_key], 'to'=>$user_account_data[$amount_key]),
							$reward_money_key=>array('from'=>$user_account[$reward_money_key], 'to'=>$user_account_data[$reward_money_key]),
                        $cash_freeze_money=>array('from'=>$user_account[$cash_freeze_money], 'to'=>$user_account_data[$cash_freeze_money]),
							'free_times'=>array('from'=>$user_account['free_times'], 'to'=>$user_account_data['free_times']),
							'free_money'=>array('from'=>$user_account['free_money'], 'to'=>$user_account_data['free_money']),
					)
					, array('cash_id'=>$cashoutId), '', '');
			
			$user = M('user')->find($uid);
			$this->commit($cacheKey);
            $content= [
                '0' =>$user['uname'],
                '1'=>number_format(($account_money + $reward_money)/100, 2)."元",
                '2'=> date('Y-m-d H:i:s', time()),
            ];
			service("Message/Message")->sendMessage($uid,1,6, $content, 0, 0, array(1,2), true);

            //推送微信消息给用户
            $msgdata = array(
                'fee' => $fee,
                'money' => $money,
                'time' => time(),
            );
            service('Weixin/Xhhfuwu')->addCashoutMsgQueue($uid, $cashoutId, $msgdata);


// 			cache($key, $user_account_data['amount']+1, array('expire'=>60));
			//提交申请成功以后需要通知是否取消预约(通知类型：1投资  2提现)
			service("Financing/Appoint")->pushCheckAppointCancelInvest('is_appoint_cancel',$uid,$cashoutId,42);
			return array('boolen'=>1, 'message'=>"提交申请成功", 'cashoutId'=>$cashoutId);
		}catch (Exception $e){
			$this->rollback($cacheKey);
			return array('boolen'=>0, 'message'=>$e->getMessage());
		}
		$this->setAmountCache($uid, $user_account_data['amount']);
		$this->setRewardCache($uid, $user_account_data['reward_money']);
		$this->setCashFreezeMoneyCache($uid, $user_account_data['cash_freeze_money']);
	}

	//支付冻结 @only PayOrderModel
	public function freezePay($uid, $money, $reward_money, $invest_reward_money, $toUserId, $objType, $objId, $orderId, $free_money=0){
		$this->legalCheck($uid);
		
		$uid = (int)$uid;
		$toUserId = (int)$toUserId;
// 		$key = "amount_{$uid}";
		if($this->getAmountCache($uid) && $this->getAmountCache($uid) < $money) {// cache里面为了不为0，所有money都+1
			throw_exception("您的可用余额不足");
		}
		if($this->getRewardCache($uid) && $this->getRewardCache($uid) < $reward_money) {// cache里面为了不为0，所有money都+1
			throw_exception("您的可提现奖励金额不足");
		}
		if($this->getInvestRewardCache($uid) && $this->getInvestRewardCache($uid) < $invest_reward_money) {// cache里面为了不为0，所有money都+1
			throw_exception("您的投资后可提现奖励金额不足");
		}
		
		$user_account = M('user_account')->find($uid);
		if($this->getAmountCache($uid) && (($user_account['amount']) != $this->getAmountCache($uid)) ) throw_exception("暂未提交成功，请重试");
		if($this->getRewardCache($uid) && (($user_account['reward_money']) != $this->getRewardCache($uid)) ) throw_exception("暂未提交成功，请重试");
		if($this->getInvestRewardCache($uid) && (($user_account['invest_reward_money']) != $this->getInvestRewardCache($uid)) ) throw_exception("暂未提交成功，请重试");

        if($user_account['amount'] < $money){
			throw_exception("您的可用余额不足");
		}
		if($user_account['reward_money'] < $reward_money) {// cache里面为了不为0，所有money都+1
			throw_exception("您的可提现奖励金额不足");
		}
		if($user_account['invest_reward_money'] < $invest_reward_money) {// cache里面为了不为0，所有money都+1
			throw_exception("您的投资后可提现奖励金额不足");
		}
		try{
			$user_account_data['uid'] = $uid;
			$user_account_data['amount'] = $user_account['amount'] - $money;
			$user_account_data['reward_money'] = $user_account['reward_money'] - $reward_money;
			$user_account_data['invest_reward_money'] = $user_account['invest_reward_money'] - $invest_reward_money;
			$user_account_data['buy_freeze_money'] = $user_account['buy_freeze_money'] + $money + $reward_money+$invest_reward_money;;
			$user_account_data['freeze_free_money'] = $user_account['freeze_free_money'] + $free_money;
			$user_account_data['mtime'] = time();
// 			$res = M('user_account')->save($user_account_data);
			$user_account_data['version'] = $user_account['version'];
			$res = D("Payment/PayAccount")->save($user_account_data);
			if(!$res) throw_exception("暂未 提交成功，请重试");
			
	// 		cache($key, $user_account_data['amount']+1, array('expire'=>60));
			
			D('Payment/Ticket');
			$this->addRecord($uid, $objType, $objId, TicketModel::OUTGOING_FLAG, ($money + $reward_money),
					array(
							'amount'=>array('from'=>$user_account['amount'], 'to'=>$user_account_data['amount']),
							'reward_money'=>array('from'=>$user_account['reward_money'], 'to'=>$user_account_data['reward_money']),
							'invest_reward_money'=>array('from'=>$user_account['invest_reward_money'], 'to'=>$user_account_data['invest_reward_money']),
							'buy_freeze_money'=>array('from'=>$user_account['buy_freeze_money'], 'to'=>$user_account_data['buy_freeze_money']),
							'freeze_free_money'=>array('from'=>$user_account['freeze_free_money'], 'to'=>$user_account_data['freeze_free_money']),
					)
					, array('pay_order_id'=>(int)$orderId), '', $toUserId, true);
		} catch(Exception $e){throw $e; }
		$this->setAmountCache($uid, $user_account_data['amount']);
		$this->setRewardCache($uid, $user_account_data['reward_money']);
		$this->setInvestRewardCache($uid, $user_account_data['invest_reward_money']);
		$this->setBuyFreezeMoneyCache($uid, $user_account_data['buy_freeze_money']);
	}

    /**
     * 托管项目资金冻结
     * @param $uid
     * @param $money
     * @param $reward_money
     * @param $invest_reward_money
     * @param $to_user_id
     * @param $obj_type
     * @param $obj_id
     * @param $order_id
     * @param int $free_money
     * @throws Exception
     */
    public function freezePayZs(
        $uid,
        $money,
        $reward_money,
        $invest_reward_money,
        $to_user_id,
        $obj_type,
        $obj_id,
        $order_id,
        $free_money = 0
    ) {
        $this->legalCheck($uid);

        $uid = (int)$uid;
        $to_user_id = (int)$to_user_id;
        $user_account = M('user_account')->find($uid);

        if ($user_account['zs_amount'] < $money) {
            throw_exception("您的可用余额不足");
        }
        if ($user_account['zs_reward_money'] < $reward_money) {// cache里面为了不为0，所有money都+1
            throw_exception("您的可提现奖励金额不足");
        }
        if ($user_account['invest_reward_money'] < $invest_reward_money) {// cache里面为了不为0，所有money都+1
            throw_exception("您的投资后可提现奖励金额不足");
        }
        try {
            $user_account_data['uid'] = $uid;
            $user_account_data['zs_amount'] = $user_account['zs_amount'] - $money;
            $user_account_data['zs_reward_money'] = $user_account['zs_reward_money'] - $reward_money;
            $user_account_data['invest_reward_money'] = $user_account['invest_reward_money'] - $invest_reward_money;
            $user_account_data['zs_buy_freeze_money'] = $user_account['zs_buy_freeze_money'] + $money + $reward_money + $invest_reward_money;;
            $user_account_data['freeze_free_money'] = $user_account['freeze_free_money'] + $free_money;
            $user_account_data['mtime'] = time();
            $user_account_data['version'] = $user_account['version'];
            $res = D('Payment/PayAccount')->save($user_account_data);
            if (!$res) {
                throw_exception("暂未提交成功，请重试");
            }

            D('Payment/Ticket');
            $this->addRecord($uid, $obj_type, $obj_id, TicketModel::OUTGOING_FLAG, ($money + $reward_money),
                array(
                    'zs_amount' => array('from' => $user_account['zs_amount'], 'to' => $user_account_data['zs_amount']),
                    'zs_reward_money' => array(
                        'from' => $user_account['zs_reward_money'],
                        'to' => $user_account_data['zs_reward_money']
                    ),
                    'invest_reward_money' => array(
                        'from' => $user_account['invest_reward_money'],
                        'to' => $user_account_data['invest_reward_money']
                    ),
                    'zs_buy_freeze_money' => array(
                        'from' => $user_account['zs_buy_freeze_money'],
                        'to' => $user_account_data['zs_buy_freeze_money']
                    ),
                    'freeze_free_money' => array(
                        'from' => $user_account['freeze_free_money'],
                        'to' => $user_account_data['freeze_free_money']
                    ),
                )
                , array('pay_order_id' => (int) $order_id), '', $to_user_id, true);
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * 司马小鑫订单购买项目
     * @param $uid
     * @param $money
     * @param $to_user
     * @param $obj_type
     * @param $obj_id
     * @param $x_order_id
     * @param int $free_money
     * @throws Exception
     */
    public function freezePayX($uid, $money, $to_user, $obj_type, $obj_id, $x_order_id, $free_money=0)
    {
        $this->legalCheck($uid);
        $user_account = M('user_account')->find($uid);
        if ($user_account['amountx'] < $money) {
            throw_exception('您的可用余额不足');
        }

        try {
            $user_account_data['uid'] = $uid;
            $user_account_data['amountx'] = $user_account['amountx'] - $money;
            $user_account_data['buy_freeze_moneyx'] = $user_account['buy_freeze_moneyx'] + $money;
            $user_account_data['freeze_free_money'] = $user_account['freeze_free_money'] + $free_money;
            $user_account_data['mtime'] = NOW_TIME;
            $user_account_data['version'] = $user_account['version'];
            $res = D('Payment/PayAccount')->save($user_account_data);
            if(!$res) {
                throw_exception('暂未提交成功，请重试');
            }

            $this->addRecord($uid, $obj_type, $obj_id, TicketModel::OUTGOING_FLAG, $money,
                array(
                    'buy_freeze_moneyx' => array(
                        'from'=>$user_account['buy_freeze_moneyx'],
                        'to'=>$user_account_data['buy_freeze_moneyx'],
                    ),
                    'freeze_free_money' => array(
                        'from'=>$user_account['freeze_free_money'],
                        'to'=>$user_account_data['freeze_free_money'],
                    ),
                    'amountx' => array(
                        'from' => $user_account['amountx'],
                        'to' => $user_account_data['amountx'],
                    ),
                ), array('record_id'=> (int) $x_order_id), '', $to_user, true);
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * 加入司马小鑫时使用红包和满减券，直接从平台账户划钱到个人amountx，同时流水用队列来跑
     * @param $uid
     * @param $money  司马小鑫订单委托金额
     * @param $x_order_id
     * @param $freezeList
     * @param $requestId
     * @param $reward_info  奖励信息(desc,remark)
     * @throws Exception
     */
    public function giveXReward($uid, $money, $x_order_id, $freezeList, $requestId, $reward_info) {
        try {
            //划账到用户,流水用队列来跑
            foreach ($freezeList as $from_user) {
                    $queue_data['list'][] = array(
                        'from_user' => $from_user['accountId'],
                        'uid' => $uid,
                        'money' => $from_user['amounts'],  //变化金额
                    );
            }
            //将一次划账的所有流水内容放进一个队列任务异步处理
            if($queue_data['list']) {
                $queue_data['money'] = $money;
                $queue_data['obj_type'] = PayAccountModel::OBJ_TYPE_GIVEXREWARD;
                $queue_data['x_order_id'] = $x_order_id;
                $queue_data['record_id'] = array('record_id' => (int)$x_order_id);
                $queue_data['requestId'] = $requestId;
                $queue_data['queue_num'] = count($queue_data['list']);
                $queue_data['freeze_amount'] = $reward_info['freeze_amount'];
                $queue_data['to_user'] = $uid;
                $queue_data['desc'] = $reward_info['desc'];
                $queue_data['remark'] = $reward_info['remark'];
                $queue_key = $x_order_id . $uid . microtime(true);
                $ret = queue("reward_add_record", $queue_key, $queue_data);
                if (!$ret) {
                    \Addons\Libs\Log\Logger::err("reward_add_record入队列失败","REWARD_ADD_RECORD_FAIL",array('queue_key' => $queue_key, 'queue_data' => $queue_data));
                    throw_exception('加入失败，请稍后重试');
                }
            }
        } catch (Exception $e) {
            throw $e;
        }
    }


    /**
     * 加入司马小鑫资金变化和流水
     * @param $uid
     * @param $money
     * @param $to_user
     * @param $obj_type
     * @param $obj_id
     * @param int $x_order_id 司马小鑫订单ID
     * @param string $remark 流水备注
     * @param int $freeze_free_money 新增加的免费提现额度
     * @throws Exception
     */
    public function joinInXPay($uid, $money, $to_user, $obj_type, $obj_id, $x_order_id, $remark, $freeze_free_money = 0)
    {
        $this->legalCheck($uid);
        $user_account = M('user_account')->find($uid);
        if ($user_account['amount'] + $user_account['reward_money'] < $money) {
            throw_exception('您的可用余额不足');
        }

        try {

            //加入司马小鑫支持使用员工补贴(优先使用reward_money,如果不足再用amount)
            $data = $this->rewardMoneyCalculate($user_account, $money);
            $user_account_data = $data['user_account_data'];

            $user_account_data['uid'] = $uid;
            $user_account_data['amountx'] = $user_account['amountx'] + $money;
            $user_account_data['freeze_free_money'] = $user_account['freeze_free_money'] + $freeze_free_money;
            $user_account_data['mtime'] = NOW_TIME;
            $user_account_data['version'] = $user_account['version'];
            $res = D('Payment/PayAccount')->save($user_account_data);
            if(!$res) {
                throw_exception('暂未提交成功，请重试');
            }

            $desc = $remark;
            $changeParams = $data['changeParams'];
            $this->addRecord($uid, $obj_type, $obj_id, TicketModel::OUTGOING_FLAG, $money,
                $changeParams, array('record_id'=> (int) $x_order_id), $desc, $to_user, true, $remark);
        } catch (Exception $e) {
            throw $e;
        }
    }

	//支付冻结后退款 @only PayOrderModel
	public function reback($uid, $money, $reward_money, $invest_reward_money, $toUserId, $objType, $objId, $orderId, $freeze_free_money=0){
		$this->legalCheck($uid, false);
		
		$uid = (int)$uid;
		$toUserId = (int)$toUserId;
// 		$key = "buy_freeze_money_{$uid}";
		if($this->getBuyFreezeMoneyCache($uid) && $this->getBuyFreezeMoneyCache($uid) < $money) {// cache里面为了不为0，所有money都+1
			throw_exception("您的冻结余额不足");
		}
		
		$user_account = M('user_account')->find($uid);
		if($this->getBuyFreezeMoneyCache($uid) && (($user_account['buy_freeze_money']) != $this->getBuyFreezeMoneyCache($uid)) ) throw_exception("退款并发异常");
		if($user_account['buy_freeze_money'] < ($money + $reward_money + $invest_reward_money)){
			throw_exception("您的冻结余额不足");
		}
		try{
			$user_account_data['uid'] = $uid;
			$user_account_data['buy_freeze_money'] = $user_account['buy_freeze_money'] - $money - $reward_money - $invest_reward_money;
			$user_account_data['amount'] = $user_account['amount'] + $money;
			$user_account_data['reward_money'] = $user_account['reward_money'] + $reward_money;
			$user_account_data['invest_reward_money'] = $user_account['invest_reward_money'] + $invest_reward_money;
			if($freeze_free_money){
				$user_account_data['freeze_free_money'] = $user_account['freeze_free_money'] - $freeze_free_money;
				if($user_account_data['freeze_free_money'] < 0) $user_account_data['freeze_free_money'] = 0;
			}
			$paymentParam = array(
					'amount'=>array('from'=>$user_account['amount'], 'to'=>$user_account_data['amount']),
					'reward_money'=>array('from'=>$user_account['reward_money'], 'to'=>$user_account_data['reward_money']),
					'invest_reward_money'=>array('from'=>$user_account['invest_reward_money'], 'to'=>$user_account_data['invest_reward_money']),
					'buy_freeze_money'=>array('from'=>$user_account['buy_freeze_money'], 'to'=>$user_account_data['buy_freeze_money']),
					'freeze_free_money'=>array('from'=>$user_account['freeze_free_money'], 'to'=>$user_account_data['freeze_free_money']),
			);
			$user_account_data['mtime'] = time();
// 			$res = M('user_account')->save($user_account_data);
			$user_account_data['version'] = $user_account['version'];
			$res = D("Payment/PayAccount")->save($user_account_data);
			if(!$res) throw_exception("系统数据库异常RB1");
	// 		cache($key, $user_account_data['buy_freeze_money']+1, array('expire'=>60));
			D('Payment/Ticket');
			$this->addRecord($uid, $objType, $objId, TicketModel::INCOMING_FLAG, ($money + $reward_money + $invest_reward_money),
					$paymentParam
					, array('pay_order_id'=>(int)$orderId), '', $toUserId, true);
		} catch(Exception $e){throw $e;}
		$this->setBuyFreezeMoneyCache($uid, $user_account_data['buy_freeze_money']);
		$this->setAmountCache($uid, $user_account_data['amount']);
	}
	
	//实际支付 @only PayOrderModel $is_touzhi 默认是投资项目，就会记录本金 $free_money是需要增加的免费额度
	public function pay($uid, $money, $fee, $toUserId, $objType, $objId, $orderId, $free_money){
		$this->legalCheck($uid,false);
		
		$uid = (int)$uid;
		$toUserId = (int)$toUserId;
// 		$key = "buy_freeze_money_{$uid}";
		if($this->getBuyFreezeMoneyCache($uid) && $this->getBuyFreezeMoneyCache($uid) < $money) {// cache里面为了不为0，所有money都+1
			throw_exception("您的冻结余额不足");
		}
		$user_account = M('user_account')->find($uid);
		if($this->getBuyFreezeMoneyCache($uid) && (($user_account['buy_freeze_money']) != $this->getBuyFreezeMoneyCache($uid)) ) throw_exception("支付并发异常");

		try{
            //根据当前订单号获取奖励金额
            $pay_order_info = M('pay_order')->where(array('id'=>$orderId))->field(array('obj_id','obj_type'))->find();
            if (!$pay_order_info) {
                throw_exception("自动截标异常");
            }
            $order_info = D('Financing/PrjOrder')->where(array('id'=>$pay_order_info['obj_id']))->find();
            $will_reward = (int)$order_info['invest_reward_money'];

            if($user_account['buy_freeze_money'] < $money+$will_reward){
                throw_exception("您的冻结余额不足(uid:".$uid.";freeze:".$user_account['buy_freeze_money'].";money:".$money);
            }


			$user_account_data['uid'] = $uid;
			$user_account_data['buy_freeze_money'] = $user_account['buy_freeze_money'] - ($money+$will_reward);
            //把预期收益增加为收益
            $user_account_data['profit'] = $user_account['profit'] + $will_reward;
			$user_account_data['free_money'] = $user_account['free_money'] + $free_money;
			$user_account_data['freeze_free_money'] = $user_account['freeze_free_money'] - $free_money;
			if($user_account_data['freeze_free_money'] < 0) $user_account_data['freeze_free_money'] = 0;
			$paymentParam['buy_freeze_money'] = array('from'=>$user_account['buy_freeze_money'], 'to'=>$user_account_data['buy_freeze_money']);
			$paymentParam['free_money'] = array('from'=>$user_account['free_money'], 'to'=>$user_account_data['free_money']);
			$paymentParam['freeze_free_money'] = array('from'=>$user_account['freeze_free_money'], 'to'=>$user_account_data['freeze_free_money']);
			$user_account_data['mtime'] = time();
// 			$res = M('user_account')->save($user_account_data);

			$user_account_data['version'] = $user_account['version'];
			$res = D("Payment/PayAccount")->save($user_account_data);
			if(!$res) throw_exception("系统数据库异常P1");
	// 		cache($key, $user_account_data['buy_freeze_money']+1, array('expire'=>60));
			//减少预期收益
            $this->changeWillReward($uid, $will_reward, "-");
			D('Payment/Ticket');
			$this->addRecord($uid, $objType, $objId, TicketModel::OUTGOING_FLAG, $money,
					$paymentParam
					, array('pay_order_id'=>(int)$orderId), '', $toUserId, true);
			
			$this->incoming($toUserId, $money - $fee, $uid, $objType, $objId, $orderId, 0, null, false);
			if($fee) {
				D('Payment/PayAccount')->incoming(PLAT_ACCOUNTID, $fee, $uid, PayAccountModel::OBJ_TYPE_SHOUXU, $orderId, '');
			}
		} catch(Exception $e){throw $e;}
		$this->setBuyFreezeMoneyCache($uid, $user_account_data['buy_freeze_money']);
	}

    /**
     * 添加用户的invest_reward_money
     * @param $uid
     * @param $changeInvestMoney
     */
    public function addInvestRewardMoney($uid,$changeInvestMoney)
    {
        $db = D("Account/UserAccount");
        $db->startTrans();
        try {
            $user_account_version = $db->where(array('uid' => $uid))->getField("version");
            if ($user_account_version === false) {
                throw_exception("获取数据失败");
            }
            $user_account_data['invest_reward_money'] = array(
                'exp',
                'invest_reward_money+' . $changeInvestMoney
            );
            $user_account_data['version'] = array('exp', 'version+1');
            D("Account/UserAccount")->where(array(
                'uid' => $uid,
                'version' => $user_account_version
            ))->save($user_account_data);
            $db->commit();
        } catch (Exception $e) {
            $db->rollback();
            throw_exception($e->getMessage());
        }

    }

    /**
     * 修改用户的预期收益
     * @param $uid
     * @param $changeWillReward
     */
    public function changeWillReward($uid, $changeWillReward,$opt = '+')
    {
        $db = M("user_account_summary");
        $db->startTrans();
        try {
            //是否是合法的操作符合
            if (!in_array($opt, array('+', '-'))) {
                throw_exception("非法的操作符合");
            }
            $user_account_summary_version = $db->where(array('uid' => $uid))->getField("ver");
            if ($user_account_summary_version === false) {
                throw_exception("获取数据失败");
            }
            $user_account_summary_data['will_reward'] = array('exp', 'will_reward'.$opt . $changeWillReward);
            $user_account_summary_data['ver'] = array('exp', 'ver+1');
            $db->where(array(
                'uid' => $uid,
                'ver' => $user_account_summary_version
            ))->save($user_account_summary_data);
            $db->commit();
        } catch (Exception $e) {
            $db->rollback();
            throw_exception($e->getMessage());
        }

    }

    //从还款冻结账户里扣到对方账户余额
    public function repayDirectPay($uid, $money, $toUserId, $objType, $objId, $orderId, $desc='', $is_merchant=false){
        $this->legalCheck($uid,false);

        $uid = (int)$uid;
        $toUserId = (int)$toUserId;
// 		$key = "repay_freeze_money_{$uid}";
        if($this->getRepayFreezeMoneyCache($uid) && $this->getRepayFreezeMoneyCache($uid) < $money) {// cache里面为了不为0，所有money都+1
            throw_exception("您的还款金额不足error1");
        }

        $user_account = M('user_account')->find($uid);
        if($this->getRepayFreezeMoneyCache($uid) && (($user_account['repay_freeze_money']) != $this->getRepayFreezeMoneyCache($uid)) ) throw_exception("还款并发异常");
        if($user_account['repay_freeze_money'] < $money){
            throw_exception("您的还款金额不足error2");
        }
        try{
            $user_account_data['uid'] = $uid;
            $user_account_data['repay_freeze_money'] = $user_account['repay_freeze_money'] - $money;
            $paymentParam['repay_freeze_money'] = array('from'=>$user_account['repay_freeze_money'], 'to'=>$user_account_data['repay_freeze_money']);
            $user_account_data['mtime'] = time();
// 			$res = M('user_account')->save($user_account_data);
            $user_account_data['version'] = $user_account['version'];
            $res = D("Payment/PayAccount")->save($user_account_data);
            if(!$res) throw_exception("系统数据库异常RY1:".$user_account['uid'].":".$user_account['version']);

            D('Payment/Ticket');
            $this->addRecord($uid, $objType, $objId, TicketModel::OUTGOING_FLAG, $money,
                $paymentParam
                , array('pay_order_id'=>(int)$orderId), $desc, $toUserId, true, $desc);
            $this->incoming($toUserId, $money, $uid, $objType, $objId, $orderId, 0, null, $is_merchant, 0, $desc);
        } catch(Exception $e){throw $e;}
// 		cache($key, $user_account_data['repay_freeze_money']+1, array('expire'=>60));
        $this->setRepayFreezeMoneyCache($uid, $user_account_data['repay_freeze_money']);
    }

    //还款冻结 @only PayOrderModel
    public function repayFreeze($uid, $money, $profit, $toUserId, $objType, $objId, $orderId, $desc='', $is_merchant=false){
        $this->legalCheck($uid,false);

        $uid = (int)$uid;
        $toUserId = (int)$toUserId;
// 		$key = "repay_freeze_money_{$uid}";
        if($this->getRepayFreezeMoneyCache($uid) && $this->getRepayFreezeMoneyCache($uid) < $money) {// cache里面为了不为0，所有money都+1
            throw_exception("您的还款金额不足error1");
        }

        $user_account = M('user_account')->find($uid);
        if($this->getRepayFreezeMoneyCache($uid) && (($user_account['repay_freeze_money']) != $this->getRepayFreezeMoneyCache($uid)) ) throw_exception("还款并发异常");
        if($user_account['repay_freeze_money'] < $money){
            throw_exception(print_r($user_account,1)."您的还款金额不足error2");
        }
        try{
            $user_account_data['uid'] = $uid;
            $user_account_data['repay_freeze_money'] = $user_account['repay_freeze_money'] - $money;
            $paymentParam['repay_freeze_money'] = array(
                'from' => $user_account['repay_freeze_money'],
                'to' => $user_account_data['repay_freeze_money']
            );
            $user_account_data['mtime'] = time();
// 			$res = M('user_account')->save($user_account_data);
            $user_account_data['version'] = $user_account['version'];
            $res = D("Payment/PayAccount")->save($user_account_data);
            if (!$res) {
                throw_exception("系统数据库异常RY1:" . $user_account['uid'] . ":" . $user_account['version']);
            }

            D('Payment/Ticket');
            $this->addRecord(
                $uid,
                $objType,
                $objId,
                TicketModel::OUTGOING_FLAG,
                $money,
                $paymentParam,
                array('pay_order_id'=>(int)$orderId),
                $desc,
                $toUserId,
                true
            );
            $this->incoming(
                $toUserId,
                $money,
                $uid,
                $objType,
                $objId,
                $orderId,
                $profit,
                null,
                $is_merchant,
                1,
                $desc
            );
        } catch(Exception $e){throw $e;}
// 		cache($key, $user_account_data['repay_freeze_money']+1, array('expire'=>60));
        $this->setRepayFreezeMoneyCache($uid, $user_account_data['repay_freeze_money']);
    }
	
	//还款 @only PayOrderModel
	public function repay($uid, $money, $profit, $toUserId, $objType, $objId, $orderId, $is_merchant=false){
		$this->legalCheck($uid, false);
	
		$uid = (int)$uid;
		$toUserId = (int)$toUserId;
// 		$key = "repay_freeze_money_{$uid}";
		if($this->getRepayFreezeMoneyCache($uid) && $this->getRepayFreezeMoneyCache($uid) < $money) {// cache里面为了不为0，所有money都+1
			throw_exception("您的还款金额不足error1");
		}
		
		$user_account = M('user_account')->find($uid);
		if($this->getRepayFreezeMoneyCache($uid) && (($user_account['repay_freeze_money']) != $this->getRepayFreezeMoneyCache($uid)) ) throw_exception("还款并发异常");
		if($user_account['repay_freeze_money'] < $money){
			throw_exception("您的还款金额不足error2");
		}
		try{
			$user_account_data['uid'] = $uid;
			$user_account_data['repay_freeze_money'] = $user_account['repay_freeze_money'] - $money;
			$paymentParam['repay_freeze_money'] = array('from'=>$user_account['repay_freeze_money'], 'to'=>$user_account_data['repay_freeze_money']);
			$user_account_data['mtime'] = time();
// 			$res = M('user_account')->save($user_account_data);
			$user_account_data['version'] = $user_account['version'];
			$res = D("Payment/PayAccount")->save($user_account_data);
			if(!$res) throw_exception("系统数据库异常RY1:".$user_account['uid'].":".$user_account['version']);
			
			D('Payment/Ticket');
			$this->addRecord($uid, $objType, $objId, TicketModel::OUTGOING_FLAG, $money,
					$paymentParam
					, array('pay_order_id'=>(int)$orderId), '', $toUserId, true);
			$this->incoming($toUserId, $money, $uid, $objType, $objId, $orderId, $profit, null, $is_merchant);
		} catch(Exception $e){throw $e;}
// 		cache($key, $user_account_data['repay_freeze_money']+1, array('expire'=>60));
		$this->setRepayFreezeMoneyCache($uid, $user_account_data['repay_freeze_money']);
	}
	
	public function getMyPrjProfitInfo($recordId){
		$AccountRecord = D('Payment/AccountRecord')->find($recordId);
		if($AccountRecord['obj_type'] != self::OBJ_TYPE_REPAY_FREEZE && $AccountRecord['obj_type'] != self::OBJ_TYPE_REPAY 
		        && $AccountRecord['obj_type'] != self::OBJ_TYPE_RAISINGINCOME && $AccountRecord['obj_type'] != self::OBJ_TYPE_ZS_RRPAIED){
			throw_exception("不是回款");
		}

		if ($AccountRecord['obj_type'] != self::OBJ_TYPE_ZS_RRPAIED) {
            $person_repayment = M('person_repayment')->where(array("id"=>$AccountRecord['obj_id']))->find();
            $prj_id = $person_repayment['prj_id'];
            $order_repay_plan = M('prj_order_repay_plan')->where(array("person_repayment_id"=>$person_repayment['id']))->find();
        } else {
            $order_repay_plan = M('prj_order_repay_plan')->where(array("id"=>$AccountRecord['obj_id']))->find();
            $prj_id = $order_repay_plan['prj_id'];
        }

        $prj = M('prj')->where(array("id"=>$prj_id))->find();
		
		$data = array();
		$data['name'] = $prj['prj_name'];//名称
		$data['give_free_money'] =  ($AccountRecord['to_free_money'] - $AccountRecord['from_free_money'])/100; //免费额度
		
		if($prj['repay_way'] == 'endate') {
			$data['total_periods'] = 1;//总期数
		} else if($prj['repay_way'] == 'D' || $prj['repay_way'] == 'permonth'){
			if($prj['time_limit_unit'] == 'month'){
				$data['total_periods'] = $prj['time_limit'];//总期数
			} else if($prj['time_limit_unit'] == 'year'){
				$data['total_periods'] = $prj['time_limit']*12;//总期数
			} else{
				$data['total_periods'] = 1;//总期数
			}
		} else if($prj['repay_way'] == 'halfyear' || $prj['time_limit_unit'] == 'year'){
			$data['total_periods'] = $prj['time_limit']*2;//总期数
		} else {
			$data['total_periods'] = 1;//总期数
		}
			
		$data['repay_periods'] = $order_repay_plan['repay_periods'];//期数
		$data['pri_interest'] = $order_repay_plan['pri_interest']/100;//本息
		if($AccountRecord['obj_type'] == self::OBJ_TYPE_RAISINGINCOME){
			$data['type'] = '募集期资金占用费';
		} else if($order_repay_plan['repay_periods'] == 2001){
            $data['type'] = '利息补偿';
        }  else if($order_repay_plan['repay_periods'] == 0){
            $data['type'] = '募集期资金占用费';
        } else if($order_repay_plan['principal']>0 && $order_repay_plan['yield']>0){
			$data['type'] = '本息';
		} else if($order_repay_plan['principal']>0){
			$data['type'] = '本金';
		} else if($order_repay_plan['yield']>0){
			$data['type'] = '利息';
		}
		return $data;
	}
	
	function getUserAccountRecord($cond=array(), $parameter=array(), $all = false){
		return $this->getRecordsByUid($cond, $parameter, $all);
	}
	
	//记录账户流水
	public function getRecordsByUid($cond=array(), $parameter=array(), $all = false){
		if($all){
			$data['data'] = D('Payment/AccountRecord')->where($cond)->order("id desc")->select();
        }else{
            $data = D('Payment/AccountRecord')->where($cond)->order("id desc")->findPage(10, $parameter);
        }
//         var_dump(M()->getLastSql());

		if($data['data']){
			$list = array();
			D('Payment/Ticket');
            $uid = $cond['uid'];
            $userinfo = D("Account/User")->getByUid($uid);
            D("Account/User")->initUserRole($userinfo);
            foreach($data['data'] as $ele){
                $ele['role'] = $userinfo['role'];
				$ele = $this->formatRecord($ele);
				$list[] = $ele;
			}
			$data['data'] = $list;
		}
		return $data;
	}
	
	public function getRecord($id){
		$accountRecord = D('Payment/AccountRecord')->find($id);
		$accountRecord = $this->formatRecord($accountRecord);
		return $accountRecord;
	}
	
	protected function formatRecord($ele){
		$ele['amount'] = $ele['to_amount'] + $ele['to_reward_money'] + $ele['to_invest_reward_money']+$ele['to_coupon_money'];// 可用余额
		$ele['give_free_money'] =  $ele['to_free_money'] - $ele['from_free_money']; //免费额度
		$ele['freeze_money'] = '';
		$ele['is_repay'] = 0;
		if(in_array($ele['obj_type'], array(self::OBJ_TYPE_CASHOUT, self::OBJ_TYPE_PAYFREEZE, self::OBJ_TYPE_REPAY_FREEZE))){
			$ele['freeze_money'] = $ele['change_amount'];//冻结
            $ele['mod_money'] = $ele['change_amount'];//前端页面统一使用的mod_money字段
            if($ele['from_cash_freeze_money'] > $ele['to_cash_freeze_money']) $ele['in_or_out'] = TicketModel::INCOMING_FLAG;
		} else {
			$ele['mod_money'] = $ele['change_amount'];//收入 - 支出
		}
        $prj_id = '';
		if($ele['cash_id']){
			$ele['type'] = '提现';
			if($ele['obj_type'] == self::OBJ_TYPE_OUTSHOUXU){
				$ele['type'] = '手续费';
			}
			$cashout = D('Payment/CashoutApply')->field("id, prj_id, free_money, money, out_account_id")->find((int)$ele['cash_id']);
            $bind_account = service('Financing/BindAccount')->getBindAccountById($cashout['out_account_id'], $ele['uid']);
            $bank_name = getCodeItemName('E009', $bind_account['bank']);
            $ele['remark'] = '提现'.humanMoney($cashout['money'],2).'至'.$bank_name.' 尾号'.substr($bind_account['account_no'], -4);
            if($cashout['prj_id']){
				$cprj = service("Financing/Project")->getPrjInfo($cashout['prj_id']);
				$ele['ext'] = $cprj['ext'];
				$ele['prj_series'] = $cprj['prj_series'];// 1-高富帅 2-白富美
				$ele['prj_name'] = $cprj['prj_name'];// 项目名称
				$ele['max_bid_amount'] = $cprj['max_bid_amount'];// 项目类型
				$ele['prj_class'] = $cprj['prj_class'];// 项目类型
				$ele['prj_type'] = $cprj['prj_type'];// 项目类型
				$ele['prj_no'] = $cprj['prj_no'];// 项目no
				$ele['safeguards'] = $cprj['safeguards'];
				$ele['type'] = "项目支付";
			}
			$ele['reward_money'] =  $ele['from_reward_money'] - $ele['to_reward_money'];
			if($ele['mod_money'] && $ele['in_or_out'] == 2 && $ele['cash_id']){
				// 						$cashout_apply = M('cashout_apply')->field("reward_money")->where(array("id"=>$ele['cash_id']))->find();
				$ele['reward_money'] = $cashout['reward_money'];
				$ele['use_free_money'] = $cashout['free_money'];
			}
		} else if($ele['ticket_id']){
			$ele['type'] = '充值';
            if($ele['obj_type'] == self::OBJ_TYPE_RECHARGE_CZ) $ele['type'] = '充值冲正';
            if($ele['obj_type'] == self::OBJ_TYPE_QFXRECHARGE_CZ) $ele['type'] = '企福鑫充值冲正';
            if($ele['obj_type'] == self::OBJ_TYPE_REPAYRECHARGE_CZ) $ele['type'] = '还款充值冲正';
            if($ele['obj_type'] == self::OBJ_TYPE_DEPOSITRECHARGE_CZ) $ele['type'] = '保证金充值冲正';
            if($ele['obj_type'] == self::OBJ_TYPE_ZRRECHARGE_CZ) $ele['type'] = '直融保证金充值冲正';
		} else if($ele['obj_type'] == self::OBJ_TYPE_REPAY || $ele['obj_type'] == self::OBJ_TYPE_RAISINGINCOME  || self::OBJ_TYPE_REPAY_FREEZE == $ele['obj_type']){
			$person_repayment = M('person_repayment')->where(array("id"=>$ele['obj_id']))->find();
			if($ele['obj_type'] == self::OBJ_TYPE_RAISINGINCOME){
				$minxi = '(募集期资金占用费)';
			} else {
				$order_plan = M('prj_order_repay_plan')->where(array("person_repayment_id"=>$person_repayment['id']))->find();
				if(!$order_plan){
					$minxi = '(本息)';
//				} else if($order_plan['repay_periods'] == 0){
//					$minxi = '(募集期资金占用费)';
				} else if($order_plan['principal'] > 0 && $order_plan['yield'] > 0){
					$minxi = '(本息)';
				} else if($order_plan['principal'] > 0){
					$minxi = '(本金)';
				} else if($order_plan['yield'] > 0){
                    if($order_plan['ptype'] == 1) $minxi = '(利息)';
                    if($order_plan['ptype'] == 2) $minxi = '(募集期资金占用费)';
                    if($order_plan['ptype'] == 3) $minxi = '(利息补偿)';
				}
                $order = M('prj_order')->find($order_plan['prj_order_id']);
                $creditor_rights_status = $order['creditor_rights_status'];
			}
            if(self::OBJ_TYPE_REPAY_FREEZE == $ele['obj_type']){
                if($creditor_rights_status == 2) $ele['type'] = '债权转让回款冻结'.$minxi;
                else $ele['type'] = '(变现)偿还借款本息'.$minxi;// 回款冻结 ->(变现)偿还借款本息 Q4
            } else {
                if($creditor_rights_status == 2) $ele['type'] = '债权转让回款'.$minxi;
                else $ele['type'] = '回款'.$minxi;
            }
			$ele['is_repay'] = 1;
		} else if($ele['obj_type'] == self::OBJ_TYPE_SHOUXU || $ele['obj_type'] == self::OBJ_TYPE_REPAY_SHOUXU || $ele['obj_type'] == self::OBJ_TYPE_OUTSHOUXU){
			$ele['type'] = '手续费';
		} else if($ele['obj_type'] == self::OBJ_TYPE_REWARD_CASHOUT){
			$ele['type'] = '奖励';
		} else if($ele['obj_type'] == self::OBJ_TYPE_REWARD_INVEST){
			$ele['type'] = '奖励';
		} else if($ele['obj_type'] == self::OBJ_TYPE_USE_COUPON){
			$ele['type'] = '奖励抵用券';
		} else if($ele['obj_type'] == self::OBJ_TYPE_ADD_COUPON){
			$ele['type'] = '奖励';
		} else if($ele['obj_type'] == self::OBJ_TYPE_EXPIRE_COUPON){
			$ele['type'] = '奖励过期回收';
		} else if($ele['obj_type'] == self::OBJ_TYPE_REWARD_CBACK || $ele['obj_type'] == self::OBJ_TYPE_REWARD_IBACK){
			$ele['type'] = '奖励过期回收';
		} else if(self::OBJ_TYPE_ALLOT == $ele['obj_type']){
			$ele['type'] = '划拨';
		}else if(self::OBJ_TYPE_ROLLOUT == $ele['obj_type']){
            $ele['type'] = '冻结资金划出到余额';
            $hasprjA = strpos($ele['adesc'], 'prjAccount_');
            if($hasprjA !== false) {
                $ele['type'] = '还款完成冻结资金划出';
                $prjAccountUid = substr($ele['adesc'], strlen('prjAccount_'));
                $prjAccount = M('user_prj_account')->find($prjAccountUid);
                $prj_id = $prjAccount['prj_id'];
                if($prjAccount['base_order_id'] && $prjAccount['base_prj_id'] == $prjAccount['prj_id']){
                    $order = M('prj_order')->find($prjAccount['base_order_id']);
                    if($order['creditor_rights_status'] == 2) $ele['type'] = '债权转让资金划出';
                }
            }
		} else if(self::OBJ_TYPE_TRANSFER == $ele['obj_type']){
			if($ele['in_or_out'] == 1) $ele['type'] = '卖出债权';
			if($ele['in_or_out'] == 2) $ele['type'] = '买入债权';
			$ele['reward_money'] =  $ele['from_reward_money'] - $ele['to_reward_money'];
			$ele['invest_reward_money'] =  $ele['from_invest_reward_money'] - $ele['to_invest_reward_money'];
		}  else if($ele['obj_type'] == '90001' || $ele['obj_type'] == self::OBJ_TYPE_REWARDFREEMONEY){
			$ele['type'] = '奖励免费提现额度';
			$ele['mod_money'] = 0;
		} else if($ele['obj_type'] == self::OBJ_TYPE_REBACK){
			$ele['type'] = '退款';
		} else if($ele['obj_type'] == self::OBJ_TYPE_SHOUXU_SDT){
            $ele['type'] = '速兑通服务费';
            $prj_id = $ele['obj_id'];
        } else if($ele['obj_type'] == self::OBJ_PT_SERVICE_FEE){
			$ele['type'] = '服务费';
            $prj_id = $ele['obj_id'];
		}else if($ele['obj_type'] == self::OBJ_TYPE_CUTINREPAY){
            $ele['type'] = '划转到还款账户';
            $ele['in_or_out'] = TicketModel::INCOMING_FLAG;
            $ele['freeze_money'] = $ele['change_amount'];
        }else if($ele['obj_type'] == self::OBJ_TYPE_ZS_MOVING){
            $ele['type'] = '余额转移';
            $ele['in_or_out'] = TicketModel::OUTGOING_FLAG;
        }else {
			$ele['type'] = '投资';
			$ele['reward_money'] =  $ele['from_reward_money'] - $ele['to_reward_money'];
			$ele['invest_reward_money'] =  $ele['from_invest_reward_money'] - $ele['to_invest_reward_money'];
			if($ele['mod_money'] && $ele['in_or_out'] == 2 && $ele['pay_order_id']){
				$pay_order = M('pay_order')->field("reward_money, invest_reward_money")->where(array("id"=>$ele['pay_order_id']))->find();
				$ele['reward_money'] = $pay_order['reward_money'];
				$ele['invest_reward_money'] = $pay_order['invest_reward_money'];
			}
		}
		if($ele['obj_type'] == self::OBJ_TYPE_USER_BONUS) $ele['type'] .= '(红包入账)';
		// 				$ele['ctime'] = date("Y-m-d H:i:s", $ele['ctime']);
		$ele['is_transfer'] = 0;
		if(in_array($ele['obj_type'], array(self::OBJ_TYPE_REBACK, self::OBJ_TYPE_PAYFREEZE, self::OBJ_TYPE_PAY))){
			$prj_order = M('prj_order')->where(array("id"=>$ele['obj_id']))->find();
            if($ele['obj_type']==self::OBJ_TYPE_PAY && $ele['in_or_out'] == 1) $ele['type'] = '变现';
			$prj_id = $prj_order['prj_id'];
			$ele['from_order_id'] = $prj_order['from_order_id'];
		} else if($ele['obj_type'] == self::OBJ_TYPE_REPAY || $ele['obj_type'] == self::OBJ_TYPE_RAISINGINCOME || self::OBJ_TYPE_REPAY_FREEZE == $ele['obj_type']){
			// 					$person_repayment = M('person_repayment')->where(array("id"=>$ele['obj_id']))->find();
			$prj_id = $person_repayment['prj_id'];
			$to_order_id = $person_repayment['order_id'];
			$fedTransfer = M("asset_transfer")->where(array("to_order_id"=>$to_order_id))->find();
			if($fedTransfer){
				$ele["from_order_id"] = $fedTransfer["from_order_id"];
				$ele['transfer_id'] = $fedTransfer['id'];
			}
		} else if($ele['obj_type'] == self::OBJ_TYPE_TRANSFER){
			$asset_transfer = M('asset_transfer')->where(array("id"=>$ele['obj_id']))->find();
			$prj_id = $asset_transfer['prj_id'];
			$ele['transfer_id'] = $asset_transfer['id'];
			$ele['is_transfer'] = 1;
			$ele['transfer_fee'] = $asset_transfer['fee'];
			$ele["from_order_id"] = $asset_transfer["from_order_id"];
		} elseif($ele['obj_type'] == self::OBJ_TYPE_USER_BONUS) {
			$prj_id = $ele['obj_id'];
		}
		if($prj_id){
			$prj = service("Financing/Project")->getPrjInfo($prj_id);
            if($ele['type'] == '变现' && $prj['prj_type'] != 'H'){
                $ele['type'] = '项目融资';
            }
			$ele['prj_series'] = $prj['prj_series'];// 1-高富帅 2-白富美
			$ele['repay_way'] = $prj['repay_way'];
			if($ele['from_order_id']){
				$ele['prj_series_name'] =  "富二代";
			}else if($ele['prj_series'] == 1)	$ele['prj_series_name'] =  "高富帅";
			else if($ele['prj_series'] == 2)	$ele['prj_series_name'] =  "白富美";
			$ele['repay_way'] = $prj['repay_way'];
			$ele['ext'] = $prj['ext'];// 项目名称
			$ele['prj_name'] = $prj['prj_name'];// 项目名称
			$ele['is_new'] = $prj['is_new'];// 是否新客项目
			$ele['prj_no'] = $prj['prj_no'];// 项目no
			$ele['prj_id'] = $prj_id;
			$ele['prj_class'] = $prj['prj_class'];
			$ele['prj_type'] = $prj['prj_type'];// 项目类型
			$ele['safeguards'] = $prj['safeguards'];
            $ele['huodong'] = $prj['huodong'];
            $ele['bid_status'] = $prj['bid_status'];
			$ele['activity_id'] = $prj['activity_id'];
			$ele['transfer_id'] = $prj['transfer_id'];
			$ele['max_bid_amount'] = $prj['max_bid_amount'];
		}
		return $ele;
	}
	
	//提现申请记录
	public function getCashoutApplys($param=array(), $parameter=array()){
		$where = array();
		if($param) $where = $param;
		$data = D("Payment/CashoutApply")->where($where)->order("id desc")->findPage(10, $parameter);

		if($data['data']){
			$list = array();
			foreach($data['data'] as $ele){
			    $ele['out_account'] = service('Financing/BindAccount')->getBindAccountById($ele['out_account_id'], $ele['uid']);
// 				$ele['out_account'] = M('fund_account')->find($ele['out_account_id']);
				$ele['out_account']['account_no'] = $ele['out_account_no'];
				$ele['out_account']['bank_name'] = getCodeItemName('E009', $ele['bank']);
                $ele['bak'] = '提现'.humanMoney($ele['money'],2).'至'.$ele['out_account']['bank_name'].' 尾号'.substr($ele['out_account_no'], -4);
				$userInfo['uid'] = $ele['uid'];
				$cUserRole = D('Account/User')->initUserRole($userInfo);
				if(empty($userInfo['role'])){
				$ele['out_account']['sub_bank'] = $ele['sub_bank'];
				}
				
				$prj = service("Financing/Project")->getDataById($ele['prj_id']);
				$ele['prj_series'] = $prj['prj_series'];// 1-高富帅 2-白富美
				$ele['prj_name'] = $prj['prj_name'];// 项目名称
				$ele['prj_no'] = $prj['prj_no'];// 项目no
				$ele['safeguards'] = $prj['safeguards'];
// 				$ele['ctime'] = date("Y-m-d H:i:s", $ele['ctime']);
				$ele['fuwu_fee'] = $ele['fee'] - $ele['tixian_fee'];
                $ele['real_fee'] = $ele['channel'] == PaymentService::TYPE_ZHESHANG ? '0.00' : '2.00';
				$list[] = $ele;
			}
			$data['data'] = $list;
		}
		return $data;
	}
	
	
	//系统内收款 @only PayOrderModel 和 自己model用 $is_profit还款的时候=1, $is_merchant只有募集期代扣的时候用
	public function incoming($uid, $money, $toUserId, $objType, $objId, $orderId, $profit=0, $cashout_id=null, $is_merchant=false, $is_repay_freeze=0, $desc=''){
		$this->legalCheck($uid, false);
		
		$uid = (int)$uid;
		$toUserId = (int)$toUserId;
		
// 		$key = "amount_{$uid}";
        if($is_repay_freeze){//如果是还款到冻结账户就走悲观锁
		$user_account = M('user_account')->find($uid);
        } else {
            $user_account = M('user_account')->find($uid);
        }
		if($this->getAmountCache($uid) && (($user_account['amount']) != $this->getAmountCache($uid)) ) throw_exception("收入并发异常");
		try{
            $user_account_data['uid'] = $uid;
            if ($is_repay_freeze) {
                $user_account_data['repay_freeze_money'] = $user_account['repay_freeze_money'] + $money;
                $paymentParam = array(
                    'repay_freeze_money' => array('from' => $user_account['repay_freeze_money'], 'to' => $user_account_data['repay_freeze_money']),
                );
            } else {
                $user_account_data['amount'] = $user_account['amount'] + $money;
                $paymentParam = array(
                    'amount' => array('from' => $user_account['amount'], 'to' => $user_account_data['amount']),
                );
            }
            if ($profit) {
                $prj_order_ext_info = M('person_repayment')
                    ->join('fi_prj_order as prj_order on prj_order.id=fi_person_repayment.order_id')
                    ->where([
                        'fi_person_repayment.id'=>$objId,
                    ])->field('prj_order.xprj_order_id, prj_order.bank_freeze_status')->find();

                if (!($prj_order_ext_info['xprj_order_id'] || $prj_order_ext_info['bank_freeze_status'])) {
                    $user_account_data['profit'] = $user_account['profit'] + $profit;
                    $paymentParam['profit'] = array('from' => $user_account['profit'], 'to' => $user_account_data['profit']);
                    $user_account_data['free_money'] = $user_account['free_money'] + $profit;
                    $paymentParam['free_money'] = array('from' => $user_account['free_money'], 'to' => $user_account_data['free_money']);
                }
                $user_account_data['capital'] = $user_account['capital'] + $money - $profit;
                $paymentParam['capital'] = array('from' => $user_account['capital'], 'to' => $user_account_data['capital']);
            }
            $user_account_data['mtime'] = time();
// 			$res = M('user_account')->save($user_account_data);
            $user_account_data['version'] = $user_account['version'];
            $res = D("Payment/PayAccount")->save($user_account_data);
            if (!$res) {
                throw_exception("系统数据库异常IC1");
            }
			
			D('Payment/Ticket');
			$this->addRecord(
                $uid,
                $objType,
                $objId,
                TicketModel::INCOMING_FLAG,
                $money,
                $paymentParam,
                array('pay_order_id'=>(int)$orderId, 'cash_id'=>$cashout_id),
                $desc,
                $toUserId,
                $is_merchant,
                $desc
            );
		} catch(Exception $e){throw $e;}
// 		cache($key, $user_account_data['amount']+1, array('expire'=>60));
		$this->setAmountCache($uid, $user_account_data['amount']);
	}
	
	//划拨到商户号 还款充值余额里
	public function allotIncoming($uid, $money, $toUserId, $objType, $objId, $orderId){
		$this->legalCheck($uid);
	
		$uid = (int)$uid;
		$toUserId = (int)$toUserId;
	
		// 		$key = "amount_{$uid}";
	
		$user_account = M('user_account')->find($uid);
		if($this->getRepayFreezeMoneyCache($uid) && (($user_account['repay_freeze_money']) != $this->getRepayFreezeMoneyCache($uid)) ) throw_exception("划拨并发异常");
		try{
			$user_account_data['uid'] = $uid;
			$user_account_data['repay_freeze_money'] = $user_account['repay_freeze_money'] + $money;
			$user_account_data['repay_freeeze_cache'] = $user_account['repay_freeeze_cache'] + $money;
			$paymentParam = array(
					'repay_freeze_money'=>array('from'=>$user_account['repay_freeze_money'], 'to'=>$user_account_data['repay_freeze_money']),
			);
			$user_account_data['mtime'] = time();
// 			$res = M('user_account')->save($user_account_data);
			$user_account_data['version'] = $user_account['version'];
			$res = D("Payment/PayAccount")->save($user_account_data);
			if(!$res) throw_exception("系统数据库异常AIC1");
				
			D('Payment/Ticket');
			$this->addRecord($uid, $objType, $objId, TicketModel::INCOMING_FLAG, $money,
					$paymentParam
					, array('pay_order_id'=>(int)$orderId), '', $toUserId);
		} catch(Exception $e){throw $e;}
		// 		cache($key, $user_account_data['amount']+1, array('expire'=>60));
		$this->setRepayFreezeMoneyCache($uid, $user_account_data['repay_freeze_money']);
	}
	
	//添加券
	public function addConpon($uid, $money, $objType, $objId){
		$uid = (int)$uid;
		
		$user_account = M('user_account')->find($uid);
		if($this->getCouponMoneyCache($uid) && (($user_account['coupon_money']) != $this->getCouponMoneyCache($uid)) ) throw_exception("添加券并发异常");
		try{
			$user_account_data['uid'] = $uid;
			$user_account_data['coupon_money'] = $user_account['coupon_money'] + $money;
			$paymentParam = array(
					'coupon_money'=>array('from'=>$user_account['coupon_money'], 'to'=>$user_account_data['coupon_money']),
			);
			$user_account_data['mtime'] = time();
			// 			$res = M('user_account')->save($user_account_data);
			$user_account_data['version'] = $user_account['version'];
			$res = D("Payment/PayAccount")->save($user_account_data);
			if(!$res) throw_exception("系统数据库异常ADDCP1");
		
			D('Payment/Ticket');
			$this->addRecord($uid, $objType, $objId, TicketModel::INCOMING_FLAG, $money,
					$paymentParam
					, array(), '', 0);
		} catch(Exception $e){throw $e;}
		$this->setCouponMoneyCache($uid, $user_account_data['coupon_money']);
	}
	
	//过期券
	public function expireConpon($uid, $money, $objType, $objId){
		$uid = (int)$uid;
		
		$user_account = M('user_account')->find($uid);
		if($this->getCouponMoneyCache($uid) && (($user_account['coupon_money']) != $this->getCouponMoneyCache($uid)) ) throw_exception("添加券并发异常");
		try{
			$user_account_data['uid'] = $uid;
			$user_account_data['coupon_money'] = $user_account['coupon_money'] - $money;
			$paymentParam = array(
					'coupon_money'=>array('from'=>$user_account['coupon_money'], 'to'=>$user_account_data['coupon_money']),
			);
			$user_account_data['mtime'] = time();
			// 			$res = M('user_account')->save($user_account_data);
			$user_account_data['version'] = $user_account['version'];
			$res = D("Payment/PayAccount")->save($user_account_data);
			if(!$res) throw_exception("系统数据库异常ADDCP1");
		
			D('Payment/Ticket');
			$this->addRecord($uid, $objType, $objId, TicketModel::OUTGOING_FLAG, $money,
					$paymentParam
					, array(), '', 0);
		} catch(Exception $e){throw $e;}
		$this->setCouponMoneyCache($uid, $user_account_data['coupon_money']);
	}
	
	//从某个运营账户奖励可以提现金额给用户 $obj_type=101
	public function rewardCashout($uid, $money, $toUserId, $objType, $objId, $orderId, $coupon_money=0, $adesc='',$remark=''){
// 		$this->legalCheck($uid);

		$uid = (int)$uid;
		$toUserId = (int)$toUserId;
	
		$user_account = M('user_account')->find($uid);
		if($this->getRewardCache($uid) && (($user_account['reward_money']) != $this->getRewardCache($uid)) ) throw_exception("奖励并发异常");
		if($coupon_money){
			if($this->getCouponMoneyCache($uid) && (($user_account['coupon_money']) != $this->getCouponMoneyCache($uid)) ) throw_exception("券后奖励并发异常");
		}
		try{
            $user_account_data['uid'] = $uid;
            //普通奖励
            if ($objType == self::OBJ_TYPE_REWARD_CASHOUT || $objType == self::OBJ_TYPE_USER_TYJ_CASH_BONUS) {
                $user_account_data['reward_money'] = $user_account['reward_money'] + $money;
                $paymentParam = array(
                    'reward_money' => array(
                        'from' => $user_account['reward_money'],
                        'to' => $user_account_data['reward_money']
                    ),
                );
            } elseif ($objType == self::OBJ_TYPE_REWARD_CASHOUTX) { //司马小鑫奖励到amountx
                $user_account_data['amountx'] = $user_account['amountx'] + $money;
                $paymentParam = array(
                    'amountx' => array(
                        'from' => $user_account['amountx'],
                        'to' => $user_account_data['amountx']
                    ),
                );
            } elseif ($objType == self::OBJ_TYPE_REWARD_ZS) {
                $user_account_data['zs_reward_money'] = $user_account['zs_reward_money'] + $money;
                $paymentParam = array(
                    'zs_reward_money' => array(
                        'from' => $user_account['zs_reward_money'],
                        'to' => $user_account_data['zs_reward_money']
                    ),
                );
            }

            $user_account_data['free_money'] = $user_account['free_money'] + $money;
            if ($coupon_money) {
                $user_account_data['coupon_money'] = $user_account['coupon_money'] - $money;
            }
            $paymentParam['free_money'] = array('from' => $user_account['free_money'], 'to' => $user_account_data['free_money']);
            if ($coupon_money) {
                $paymentParam['coupon_money'] = array('from' => $user_account['coupon_money'], 'to' => $user_account_data['coupon_money']);
            }
            $user_account_data['mtime'] = time();
// 			$res = M('user_account')->save($user_account_data);
            $user_account_data['version'] = $user_account['version'];
            $res = D("Payment/PayAccount")->save($user_account_data);
            if (!$res) {
                throw_exception("系统数据库异常ICRC1");
            }

            D('Payment/Ticket');
            $this->addRecord(
                $uid,
                $objType,
                $objId, 
                TicketModel::INCOMING_FLAG, 
                $money,
                $paymentParam,
                array('pay_order_id' => (int)$orderId), 
                $adesc, 
                $toUserId, 
                false, 
                $remark
            );

            service("Payment/PayAccount")->addCashoutAmount($uid, $money);

		} catch(Exception $e){throw $e;}
		// 		cache($key, $user_account_data['amount']+1, array('expire'=>60));
		$this->setRewardCache($uid, $user_account_data['reward_money']);
		if($coupon_money) $this->setCouponMoneyCache($uid, $user_account_data['coupon_money']);
	}
	
	//给用户可以提现奖励金额过期会某个运营账户  $obj_type=1013
	public function rewardCBack($uid, $money, $toUserId, $objType, $objId, $orderId){
		$uid = (int)$uid;
		$toUserId = (int)$toUserId;
	
		$user_account = M('user_account')->find($uid);
		if($this->getRewardCache($uid) && (($user_account['reward_money']) != $this->getRewardCache($uid)) ) throw_exception("奖励并发异常");
		try{
			$user_account_data['uid'] = $uid;
			$user_account_data['reward_money'] = $user_account['reward_money'] - $money;
			if($user_account_data['reward_money'] < 0){
				throw_exception($uid."奖励为负数，过期失败");
			}
			$user_account_data['free_money'] = $user_account['free_money'] - $money;
			if($user_account_data['free_money'] < 0) $user_account_data['free_money'] = 0;
			$paymentParam = array(
					'reward_money'=>array('from'=>$user_account['reward_money'], 'to'=>$user_account_data['reward_money']),
					'free_money'=>array('from'=>$user_account['free_money'], 'to'=>$user_account_data['free_money']),
			);
			$user_account_data['mtime'] = time();
			// 			$res = M('user_account')->save($user_account_data);
			$user_account_data['version'] = $user_account['version'];
			$res = D("Payment/PayAccount")->save($user_account_data);
			if(!$res) throw_exception("系统数据库异常ICRCB1");
	
			D('Payment/Ticket');
			$this->addRecord($uid, $objType, $objId, TicketModel::OUTGOING_FLAG, $money,
					$paymentParam
					, array('pay_order_id'=>(int)$orderId), '', $toUserId);
		} catch(Exception $e){throw $e;}
		// 		cache($key, $user_account_data['amount']+1, array('expire'=>60));
		$this->setRewardCache($uid, $user_account_data['reward_money']);
	}
	
	//从某个运营账户奖励投资后可以提现金额给用户 $obj_type=102
	public function rewardInvest($uid, $money, $toUserId, $objType, $objId, $orderId, $coupon_money=0, $adesc=''){
// 		$this->legalCheck($uid);
	
		$uid = (int)$uid;
		$toUserId = (int)$toUserId;
	
		$user_account = M('user_account')->find($uid);
		if($this->getInvestRewardCache($uid) && (($user_account['invest_reward_money']) != $this->getInvestRewardCache($uid)) ) throw_exception("投资奖励并发异常");
		if($coupon_money){
			if($this->getCouponMoneyCache($uid) && (($user_account['coupon_money']) != $this->getCouponMoneyCache($uid)) ) throw_exception("券后投资奖励并发异常");
		}
		try{
			$user_account_data['uid'] = $uid;
			$user_account_data['invest_reward_money'] = $user_account['invest_reward_money'] + $money;
			$user_account_data['free_money'] = $user_account['free_money'] + $money;
			if($coupon_money) $user_account_data['coupon_money'] = $user_account['coupon_money'] - $money;
			$paymentParam = array(
					'invest_reward_money'=>array('from'=>$user_account['invest_reward_money'], 'to'=>$user_account_data['invest_reward_money']),
					'free_money'=>array('from'=>$user_account['free_money'], 'to'=>$user_account_data['free_money']),
			);
			if($coupon_money) $paymentParam['coupon_money'] = array('from'=>$user_account['coupon_money'], 'to'=>$user_account_data['coupon_money']);
			$user_account_data['mtime'] = time();
// 			$res = M('user_account')->save($user_account_data);
			$user_account_data['version'] = $user_account['version'];
			$res = D("Payment/PayAccount")->save($user_account_data);
			if(!$res) throw_exception("系统数据库异常ICRC1");
	
			D('Payment/Ticket');
			$this->addRecord($uid, $objType, $objId, TicketModel::INCOMING_FLAG, $money,
					$paymentParam
					, array('pay_order_id'=>(int)$orderId), $adesc, $toUserId);
            service("Payment/PayAccount")->addCashoutAmount($uid, $money);
		} catch(Exception $e){throw $e;}
		// 		cache($key, $user_account_data['amount']+1, array('expire'=>60));
		$this->setInvestRewardCache($uid, $user_account_data['invest_reward_money']);
		if($coupon_money) $this->setCouponMoneyCache($uid, $user_account_data['coupon_money']);
	}
	
	//给用户投资后可以提现奖励金额过期 $obj_type=1023
	public function rewardIBack($uid, $money, $toUserId, $objType, $objId, $orderId){
		// 		$this->legalCheck($uid);
	
		$uid = (int)$uid;
		$toUserId = (int)$toUserId;
	
		$user_account = M('user_account')->find($uid);
		if($this->getInvestRewardCache($uid) && (($user_account['invest_reward_money']) != $this->getInvestRewardCache($uid)) ) throw_exception("投资奖励并发异常");
		try{
			$user_account_data['uid'] = $uid;
			$user_account_data['invest_reward_money'] = $user_account['invest_reward_money'] - $money;
			if($user_account_data['invest_reward_money'] < 0){
				throw_exception($uid."奖励为负数，过期失败");
			}
			$user_account_data['free_money'] = $user_account['free_money'] - $money;
			if($user_account_data['free_money'] < 0) $user_account_data['free_money'] = 0;
			$paymentParam = array(
					'invest_reward_money'=>array('from'=>$user_account['invest_reward_money'], 'to'=>$user_account_data['invest_reward_money']),
					'free_money'=>array('from'=>$user_account['free_money'], 'to'=>$user_account_data['free_money']),
			);
			$user_account_data['mtime'] = time();
			// 			$res = M('user_account')->save($user_account_data);
			$user_account_data['version'] = $user_account['version'];
			$res = D("Payment/PayAccount")->save($user_account_data);
			if(!$res) throw_exception("系统数据库异常ICRC1");
	
			D('Payment/Ticket');
			$this->addRecord($uid, $objType, $objId, TicketModel::OUTGOING_FLAG, $money,
					$paymentParam
					, array('pay_order_id'=>(int)$orderId), '', $toUserId);
		} catch(Exception $e){throw $e;}
		// 		cache($key, $user_account_data['amount']+1, array('expire'=>60));
		$this->setInvestRewardCache($uid, $user_account_data['invest_reward_money']);
	}
	
	//系统内 直接扣款 转账 @only PayOrderModel 和 自己model用
	public function outcoming($uid, $money, $toUserId, $objType, $objId, $orderId, $isMerchant=false, $reward_money=0, $invest_reward_money=0, $free_money=0){
		$this->legalCheck($uid);
		
		$uid = (int)$uid;
		$toUserId = (int)$toUserId;
		
// 		$key = "amount_{$uid}";
		if($this->getAmountCache($uid) && $this->getAmountCache($uid) < $money) {// cache里面为了不为0，所有money都+1
			throw_exception($uid."您的支付金额不足");
		}
		
		if($reward_money && $this->getRewardCache($uid) && $this->getRewardCache($uid) < $reward_money) {// cache里面为了不为0，所有money都+1
			throw_exception($uid."您的支付奖金不足");
		}
		if($invest_reward_money && $this->getInvestRewardCache($uid) && $this->getInvestRewardCache($uid) < $invest_reward_money) {// cache里面为了不为0，所有money都+1
			throw_exception($uid."您的支付投资奖金不足");
		}
		
		$user_account = M('user_account')->find($uid);
        if ($objType == self::OBJ_TYPE_REWARD_ZS) {
            $user_account_money = $user_account['zs_amount'];
            $user_account_reward_money = $user_account['zs_reward_money'];
        } else {
            $user_account_money = $user_account['amount'];
            $user_account_reward_money = $user_account['reward_money'];
        }

		if($user_account_money < $money){
			throw_exception($user_account['uid']."您的支付金额不足");
		}
		if($user_account_reward_money < $reward_money){
			throw_exception($user_account['uid']."您的支付奖金不足");
		}
		if($user_account['invest_reward_money'] < $invest_reward_money){
			throw_exception($user_account['uid']."您的支付投资奖金不足");
		}
		
		if($this->getAmountCache($uid) && (($user_account['amount']) != $this->getAmountCache($uid)) ) throw_exception("暂未成功提交，请重试");
		if($this->getRewardCache($uid) && (($user_account['reward_money']) != $this->getRewardCache($uid)) ) throw_exception("暂未成功提交，请重试");
		if($this->getInvestRewardCache($uid) && (($user_account['invest_reward_money']) != $this->getInvestRewardCache($uid)) ) throw_exception("暂未成功提交，请重试");
		try{
			$user_account_data['uid'] = $uid;

            if ($objType == self::OBJ_TYPE_REWARD_ZS) {
                $user_account_data['zs_amount'] = $user_account['zs_amount'] - $money;
                $param = array('zs_amount'=>array('from'=>$user_account['zs_amount'], 'to'=>$user_account_data['zs_amount']),);
                if($reward_money){
                    $user_account_data['zs_reward_money'] = $user_account['zs_reward_money'] - $reward_money;
                    $param['zs_reward_money'] = array('from'=>$user_account['zs_reward_money'], 'to'=>$user_account_data['zs_reward_money']);
                }
            } else {
                $user_account_data['amount'] = $user_account['amount'] - $money;
                $param = array('amount'=>array('from'=>$user_account['amount'], 'to'=>$user_account_data['amount']),);
                if($reward_money){
                    $user_account_data['reward_money'] = $user_account['reward_money'] - $reward_money;
                    $param['reward_money'] = array('from'=>$user_account['reward_money'], 'to'=>$user_account_data['reward_money']);
                }
            }

			if($invest_reward_money){
				$user_account_data['invest_reward_money'] = $user_account['invest_reward_money'] - $invest_reward_money;
				$param['invest_reward_money'] = array('from'=>$user_account['invest_reward_money'], 'to'=>$user_account_data['invest_reward_money']);
			}
			if($free_money){
				$user_account_data['free_money'] = $user_account['free_money'] + $free_money;
				$param['free_money'] = array('from'=>$user_account['free_money'], 'to'=>$user_account_data['free_money']);
			}
			$user_account_data['mtime'] = time();
// 			$res = M('user_account')->save($user_account_data);
			$user_account_data['version'] = $user_account['version'];
			$res = D("Payment/PayAccount")->save($user_account_data);
			if(!$res){
				throw_exception("暂未成功提交,请重试(".$this->getDbError()."-".$this->getError().")");
			}
			
			D('Payment/Ticket');
			$this->addRecord($uid, $objType, $objId, TicketModel::OUTGOING_FLAG, $money+$reward_money+$invest_reward_money,
					$param
					, array('pay_order_id'=>(int)$orderId), $this->yunying_adesc, $toUserId, $isMerchant);
		} catch(Exception $e){throw $e;}
// 		cache($key, $user_account_data['amount']+1, array('expire'=>60));
		$this->setAmountCache($uid, $user_account_data['amount']);
		if($reward_money) $this->setRewardCache($uid, $user_account_data['reward_money']);
		if($invest_reward_money) $this->setInvestRewardCache($uid, $user_account_data['invest_reward_money']);
	}

    //转让还款账户
    public function cutInRepayAccount($uid, $money, $objType, $objId, $orderId, $has_cache=true, $desc='', $remark=''){
        $this->legalCheck($uid, false);
        if($this->getRepayFreezeMoneyCache($uid) && $this->getRepayFreezeMoneyCache($uid) < $money) {// cache里面为了不为0，所有money都+1
            throw_exception("您的支付金额不足");
        }
        $user_account = M('user_account')->lock(1)->find($uid);
        if($user_account['amount'] < $money){
            throw_exception("您的支付金额不足");
        }

        try{
            $user_account_data['uid'] = $uid;
            $user_account_data['amount'] = $user_account['amount'] - $money;
            $param['amount'] = array('from'=>$user_account['amount'], 'to'=>$user_account_data['amount']);
            $user_account_data['repay_freeze_money'] = $user_account['repay_freeze_money'] + $money;
            $param['repay_freeze_money'] = array('from'=>$user_account['repay_freeze_money'], 'to'=>$user_account_data['repay_freeze_money']);
            if($has_cache) $user_account_data['repay_freeeze_cache'] = $user_account['repay_freeeze_cache'] + $money;
            $user_account_data['mtime'] = time();
// 			$res = M('user_account')->save($user_account_data);
            $user_account_data['version'] = $user_account['version'];
            $res = D("Payment/PayAccount")->save($user_account_data);
            if(!$res) throw_exception("系统数据库异常OC1");

            D('Payment/Ticket');
            $this->addRecord($uid, $objType, $objId, TicketModel::NO_FLAG, $money,
                $param
                , array('pay_order_id'=>(int)$orderId), $desc, $uid, 1, $remark);
        } catch(Exception $e){throw $e;}
    }
	
	public function rollout($uid, $money, $objType, $objId, $orderId, $has_cache=true, $desc=''){
		$this->legalCheck($uid, false);
		if($this->getRepayFreezeMoneyCache($uid) && $this->getRepayFreezeMoneyCache($uid) < $money) {// cache里面为了不为0，所有money都+1
			throw_exception("您的支付金额不足");
		}
		$user_account = M('user_account')->lock(1)->find($uid);
		if($user_account['repay_freeze_money'] < $money){
			throw_exception("您的支付金额不足");
		}
		
		if($this->getRepayFreezeMoneyCache($uid) && (($user_account['repay_freeze_money']) != $this->getRepayFreezeMoneyCache($uid)) ) throw_exception("并发异常");
		if($this->getAmountCache($uid) && (($user_account['amount']) != $this->getAmountCache($uid)) ) throw_exception("提出并发异常");
		try{
			$user_account_data['uid'] = $uid;
			$user_account_data['amount'] = $user_account['amount'] + $money;
			$param['amount'] = array('from'=>$user_account['amount'], 'to'=>$user_account_data['amount']);
			$user_account_data['repay_freeze_money'] = $user_account['repay_freeze_money'] - $money;
			$param['repay_freeze_money'] = array('from'=>$user_account['repay_freeze_money'], 'to'=>$user_account_data['repay_freeze_money']);
			if($has_cache) $user_account_data['repay_freeeze_cache'] = $user_account['repay_freeeze_cache'] - $money;
			$user_account_data['mtime'] = time();
// 			$res = M('user_account')->save($user_account_data);
			$user_account_data['version'] = $user_account['version'];
			$res = D("Payment/PayAccount")->save($user_account_data);
			if(!$res) throw_exception("系统数据库异常OC1");
				
			D('Payment/Ticket');
			$this->addRecord($uid, $objType, $objId, TicketModel::INCOMING_FLAG, $money,
					$param
					, array('pay_order_id'=>(int)$orderId), $desc, $uid, 1);
		} catch(Exception $e){throw $e;}
		$this->setAmountCache($uid, $user_account_data['amount']);
		$this->setRepayFreezeMoneyCache($uid, $user_account_data['repay_freeze_money']);
	}

    /**
     * 司马小鑫还款用
     * @param $uid
     * @param $money
     * @param $objType
     * @param $objId
     * @param $orderId
     * @param bool $has_cache
     * @param string $desc
     * @throws Exception
     */
    public function rollOutX($uid, $money, $objType, $objId, $orderId, $has_cache = true, $desc = '')
    {
        $this->legalCheck($uid, false);
        if ($this->getRepayFreezeMoneyCache($uid) && $this->getRepayFreezeMoneyCache($uid) < $money) {// cache里面为了不为0，所有money都+1
            throw_exception("您的支付金额不足");
        }
        $user_account = M('user_account')->lock(1)->find($uid);
        if ($user_account['repay_freeze_money'] < $money) {
            throw_exception("您的支付金额不足");
        }

        if ($this->getRepayFreezeMoneyCache($uid) && (($user_account['repay_freeze_money']) != $this->getRepayFreezeMoneyCache($uid))) {
            throw_exception("并发异常");
        }
        if ($this->getAmountCache($uid) && (($user_account['amount']) != $this->getAmountCache($uid))) {
            throw_exception("提出并发异常");
        }
        try {
            $user_account_data['uid'] = $uid;
            $user_account_data['amountx'] = $user_account['amountx'] + $money;
            $param['amountx'] = array(
                'from' => $user_account['amountx'], 
                'to' => $user_account_data['amountx']
            );
            $user_account_data['repay_freeze_money'] = $user_account['repay_freeze_money'] - $money;
            $param['repay_freeze_money'] = array(
                'from' => $user_account['repay_freeze_money'], 
                'to' => $user_account_data['repay_freeze_money']
            );
            if ($has_cache) {
                $user_account_data['repay_freeeze_cache'] = $user_account['repay_freeeze_cache'] - $money;
            }
            $user_account_data['mtime'] = time();
            $user_account_data['version'] = $user_account['version'];
            $res = $this->save($user_account_data);
            if (!$res) {
                throw_exception("系统数据库异常OC1");
            }

            D('Payment/Ticket');
            $this->addRecord(
                $uid, 
                $objType, 
                $objId, 
                TicketModel::INCOMING_FLAG,
                $money,
                $param, 
                array('pay_order_id' => (int)$orderId), 
                $desc, 
                $uid, 
                1
            );
        } catch (Exception $e) {
            throw $e;
        }
        $this->setAmountCache($uid, $user_account_data['amount']);
        $this->setRepayFreezeMoneyCache($uid, $user_account_data['repay_freeze_money']);
    }

    /**
     * 托管划账用 repay_freeze_money => zs_repaying_money
     * @param $uid
     * @param $money
     * @param $objType
     * @param $objId
     * @param $orderId
     * @param bool $has_cache
     * @param string $desc
     * @throws Exception
     */
    public function rollOutZs($uid, $money, $objType, $objId, $orderId, $has_cache = true, $desc = '')
    {
        $this->legalCheck($uid, false);
        if ($this->getRepayFreezeMoneyCache($uid) && $this->getRepayFreezeMoneyCache($uid) < $money) {// cache里面为了不为0，所有money都+1
            throw_exception("您的支付金额不足");
        }
        $user_account = M('user_account')->lock(1)->find($uid);
        if ($user_account['repay_freeze_money'] < $money) {
            throw_exception("您的支付金额不足");
        }

        if ($this->getRepayFreezeMoneyCache($uid) && (($user_account['repay_freeze_money']) != $this->getRepayFreezeMoneyCache($uid))) {
            throw_exception("并发异常");
        }
        try {
            $user_account_data['uid'] = $uid;
            $user_account_data['zs_repaying_money'] = $user_account['zs_repaying_money'] + $money;
            $param['zs_repaying_money'] = array(
                'from' => $user_account['zs_repaying_money'],
                'to' => $user_account_data['zs_repaying_money']
            );
            $user_account_data['repay_freeze_money'] = $user_account['repay_freeze_money'] - $money;
            $param['repay_freeze_money'] = array(
                'from' => $user_account['repay_freeze_money'],
                'to' => $user_account_data['repay_freeze_money']
            );
            if ($has_cache) {
                $user_account_data['repay_freeeze_cache'] = $user_account['repay_freeeze_cache'] - $money;
            }
            $user_account_data['mtime'] = time();
            $user_account_data['version'] = $user_account['version'];
            $res = $this->save($user_account_data);
            if (!$res) {
                throw_exception("系统数据库异常OC2");
            }

            D('Payment/Ticket');
            $this->addRecord(
                $uid,
                $objType,
                $objId,
                TicketModel::INCOMING_FLAG,
                $money,
                $param,
                array('pay_order_id' => (int)$orderId),
                $desc,
                $uid,
                1
            );
        } catch (Exception $e) {
            throw $e;
        }
        $this->setRepayFreezeMoneyCache($uid, $user_account_data['repay_freeze_money']);
    }

    public function transferIncoming($uid, $money, $toUserId, $objType, $objId, $orderId, $recordId){
        $this->legalCheck($uid);

        $uid = (int)$uid;
        $toUserId = (int)$toUserId;
        $user_account = M('user_account')->find($uid);
        try{
            $user_account_data['uid'] = $uid;
            $user_account_data['zs_moving_money'] = $user_account['zs_moving_money'] + $money;
            $paymentParam = array(
                'zs_moving_money'=>array('from'=>$user_account['zs_moving_money'], 'to'=>$user_account_data['zs_moving_money']),
            );
            $user_account_data['mtime'] = time();
            $user_account_data['version'] = $user_account['version'];
            $res = D("Payment/PayAccount")->save($user_account_data);
            if(!$res) throw_exception("系统数据库异常transferIncoming");

            D('Payment/Ticket');
            $this->addRecord($uid, $objType, $objId, TicketModel::INCOMING_FLAG, $money,
                $paymentParam
                , array('pay_order_id'=>(int)$orderId, 'record_id'=> $recordId), '', $toUserId);
        } catch(Exception $e){
            throw $e;
        }
    }

    /**
     * 转账入口方法
     * @param array $params
     * @return bool
     */
    public function transfer(array $params)
    {
        $from_user = K($params,'from_user');
        $to_user = K($params,'to_user');
        $money = (int)abs(K($params,'money'));
        $profit = (int)K($params,'profit',0);
        $free_money = (int)K($params,'free_money',0);
        $out_order_no = K($params,'out_order_no');
        $obj_type = K($params,'obj_type');
        $obj_id = K($params,'obj_id');
        $pay_order_id = K($params,'pay_order_id');
        $desr = K($params,'desr');
        $remark = K($params,'remark');

        $trans_key = 'pay_account_'.$out_order_no;

        $this->legalCheck($from_user, false);
        //账号内划账
        if ($from_user === $to_user) {
            $transfer_method = 'transfer_'.$obj_type;
            if (!method_exists($this, $transfer_method)) {
                throw_exception('对应的账户转账处理类型方法不存在,当前obj_type='.$obj_type);
            }
            $transfer_data = $this->$transfer_method($from_user, $money, $profit, $free_money);
            $message = $transfer_data['message'];
            $user_account_data = $transfer_data['user_account_data'];
            $user_account_record_data = $transfer_data['user_account_record_data'];
            $flag = $transfer_data['flag'];
            $is_merchant = $transfer_data['is_merchant'];
            if ($message) {
                throw_exception('UID='.$from_user.$message.'money='.$money);
            }
            try {
                $this->startTrans($trans_key);
                if (!$this->save($user_account_data)) {
                    throw_exception("user_account数据报错失败".$this->getLastSql());
                }
                $this->addRecord(
                    $from_user,
                    $obj_type,
                    $obj_id,
                    $flag,
                    $money,
                    $user_account_record_data,
                    array('pay_order_id' => $pay_order_id),
                    $desr,
                    $to_user,
                    $is_merchant,
                    $remark
                );

                return $this->commit($trans_key);

            } catch (Exception $exc) {
                $this->rollback($trans_key);
                throw_exception($exc->getMessage());
            }
            return false;
        }

        //不同账号之间划账
        $this->legalCheck($to_user, false);
        $transfer_method_out = 'transfer_'.$obj_type.'_out';
        $transfer_method_in = 'transfer_'.$obj_type.'_in';
        if (!method_exists($this, $transfer_method_out) || !method_exists($this, $transfer_method_in)) {
            throw_exception('对应的账户转账处理类型方法不存在,当前obj_type='.$obj_type);
        }

        $transfer_data_out = $this->$transfer_method_out($from_user, $money);
        $message_out = $transfer_data_out['message'];
        $user_account_data_out = $transfer_data_out['user_account_data'];
        $user_account_record_data_out = $transfer_data_out['user_account_record_data'];
        $flag_out = TicketModel::OUTGOING_FLAG;
        $is_merchant_out = $transfer_data_out['is_merchant'];
        if ($message_out) {
            throw_exception('UID='.$from_user.$message_out.'money='.$money);
        }

        $transfer_data_in = $this->$transfer_method_in($to_user, $money);
        $message_in = $transfer_data_in['message'];
        $user_account_data_in = $transfer_data_in['user_account_data'];
        $user_account_record_data_in = $transfer_data_in['user_account_record_data'];
        $flag_in = TicketModel::INCOMING_FLAG;
        $is_merchant_in = $transfer_data_in['is_merchant'];
        if ($message_in) {
            throw_exception('UID='.$from_user.$message_in.'money='.$money);
        }

        try {
            $this->startTrans($trans_key);

            $res = $this->save($user_account_data_out);
            if (!$res) {
                throw_exception("user_account数据报错失败".$this->getLastSql());
            }
            $res = $this->save($user_account_data_in);
            if (!$res) {
                throw_exception("user_account数据报错失败".$this->getLastSql());
            }

            $this->addRecord(
                $from_user,
                $obj_type,
                $obj_id,
                $flag_out,
                $money,
                $user_account_record_data_out,
                array('pay_order_id' => $pay_order_id),
                $desr,
                $to_user,
                $is_merchant_out,
                $remark
            );
            $this->addRecord(
                $to_user,
                $obj_type,
                $obj_id,
                $flag_in,
                $money,
                $user_account_record_data_in,
                array('pay_order_id' => $pay_order_id),
                $desr,
                $from_user,
                $is_merchant_in,
                $remark
            );
            $res = $this->commit($trans_key);
            return $res;

        } catch (Exception $exc) {
            $this->rollback($trans_key);
            throw_exception($exc->getMessage());
        }
        return false;
    }

    /**
     * 转账数据默认值
     * @return array
     */
    private function transferDefaultData()
    {
        return [
            //message 错误信息
            'message'=>'',
            //需要更新的账户信息
            'user_account_data'=>[],
            //账户流水信息
            'user_account_record_data'=>[],
            //额外的账户流水信息
            'id_params'=>[
                'ticket_id'=>0,
                'cash_id'=>0,
                'pay_order_id'=>0,
                'record_id'=>0,
            ],
            //amount增加是1,amount减少是2
            'flag'=>1,
            //是否商户
            'is_merchant'=>false,
            //账户内划账是false,不同账户间划账是true
            'is_need_incoming'=>false,
        ];
    }

    /**
     * OBJ_TYPE_XRPJ_SETTLEMENT = 760;
     * 司马小鑫结算 从用户amountx到用户amount
     * @param int $uid
     * @param int $money
     * @param int $profit
     * @param int $free_money
     * @return array
     */
    private function transfer_760($uid, $money, $profit, $free_money)
    {
        $return_data = $this->transferDefaultData();
        $user_account = M('user_account')->lock(1)->find($uid);
        if ($user_account['amountx'] < $money) {
            $return_data['message'] = '司马小鑫结算中760:amountx='.$user_account['amountx'];
        }

        $user_account_new = [
            'uid' => $uid,
            'amountx' => $user_account['amountx'] - $money,
            'amount' => $user_account['amount'] + $money,
            'version' => $user_account['version'],
        ];

        $user_account_record_data = [
            'amountx' => [
                'from' => $user_account['amountx'],
                'to' => $user_account_new['amountx']
            ],
            'amount' => [
                'from' => $user_account['amount'],
                'to' => $user_account_new['amount']
            ],
        ];

        if ($profit > 0) {
            $user_account_new['profit'] = $user_account['profit'] + $profit;
            $user_account_record_data['profit'] = array(
                'from' => $user_account['profit'], 
                'to' => $user_account_new['profit']
            );
            $user_account_new['capital'] = $user_account['capital'] + $money - $profit;
            $user_account_record_data['capital'] = array(
                'from' => $user_account['capital'], 
                'to' => $user_account_new['capital']
            );
        }

        if ($free_money > 0) {
            $user_account_new['free_money'] = $user_account['free_money'] + $free_money;
            $user_account_record_data['free_money'] = array(
                'from' => $user_account['free_money'],
                'to' => $user_account_new['free_money']
            );
        }

        $return_data['user_account_data'] = $user_account_new;
        $return_data['user_account_record_data'] = $user_account_record_data;
        $return_data['flag'] = TicketModel::INCOMING_FLAG;

        return $return_data;
    }

    /**
     * OBJ_TYPE_XRPJ_BALANCE_INCOME = 761;
     * 司马小鑫结算 多退 从用户amountx退到平台多退账号amount
     * 用户的amountx减少
     */
    private function transfer_761_out($uid,$money)
    {
        $return_data = $this->transferDefaultData();
        $user_account = M('user_account')->lock(1)->find($uid);
        if ($user_account['amountx'] < $money) {
            $return_data['message'] = '司马小鑫结算中 761 out:amountx='.$user_account['amountx'];
        }

        $user_account_new = [
            'uid' => $user_account['uid'],
            'amountx' => $user_account['amountx'] - $money,
            'version' => $user_account['version'],
        ];

        $user_account_record_data = [
            'amountx' => [
                'from' => $user_account['amountx'],
                'to' => $user_account_new['amountx']
            ],
        ];

        $return_data['user_account_data'] = $user_account_new;
        $return_data['user_account_record_data'] = $user_account_record_data;
        $return_data['flag'] = TicketModel::OUTGOING_FLAG;
        $return_data['is_need_incoming'] = true;

        return $return_data;
    }

    /**
     * 平台多退账号amount增加
     * @param $uid
     * @param $money
     * @return array
     */
    private function transfer_761_in($uid,$money)
    {
        $return_data = $this->transferDefaultData();
        $user_account = M('user_account')->lock(1)->find($uid);
        $user_account_new = [
            'uid' => $user_account['uid'],
            'amount' => $user_account['amount'] + $money,
            'version' => $user_account['version'],
        ];

        $user_account_record_data = [
            'amount' => [
                'from' => $user_account['amount'],
                'to' => $user_account_new['amount']
            ],
        ];

        $return_data['user_account_data'] = $user_account_new;
        $return_data['user_account_record_data'] = $user_account_record_data;
        $return_data['flag'] = TicketModel::INCOMING_FLAG;

        return $return_data;
    }


    /**
     * OBJ_TYPE_XRPJ_BALANCE_OUT = 762
     * 司马小鑫结算 少补 从平台少补账号amount转到用户amountx
     * 平台少补账号amount减少
     * @param int $uid
     * @param $money
     * @return array
     */
    private function transfer_762_out($uid,$money)
    {
        $return_data = $this->transferDefaultData();
        $user_account = M('user_account')->lock(1)->find($uid);
        if ($user_account['amount'] < $money) {
            $return_data['message'] = '司马小鑫结算中 762 out:amount='.$user_account['amount'];
        }

        $user_account_new = [
            'uid' => $user_account['uid'],
            'amount' => $user_account['amount'] - $money,
            'version' => $user_account['version'],
        ];

        $user_account_record_data = [
            'amount' => [
                'from' => $user_account['amount'],
                'to' => $user_account_new['amount']
            ],
        ];

        $return_data['user_account_data'] = $user_account_new;
        $return_data['user_account_record_data'] = $user_account_record_data;
        $return_data['flag'] = TicketModel::OUTGOING_FLAG;

        return $return_data;
    }

    /**
     * 用户amountx增加
     * @param $uid
     * @param $money
     * @return array
     */
    private function transfer_762_in($uid,$money)
    {
        $return_data = $this->transferDefaultData();
        $user_account = M('user_account')->lock(1)->find($uid);
        $user_account_new = [
            'uid' => $uid,
            'amountx' => $user_account['amountx'] + $money,
            'version' => $user_account['version'],
        ];

        $user_account_record_data = [
            'amountx' => [
                'from' => $user_account['amountx'],
                'to' => $user_account_new['amountx']
            ],
        ];

        $return_data['user_account_data'] = $user_account_new;
        $return_data['user_account_record_data'] = $user_account_record_data;
        $return_data['flag'] = TicketModel::INCOMING_FLAG;

        return $return_data;
    }



    /**
     * OBJ_TYPE_XRPJ_RATE_ADDON = 763;
     * 司马小鑫结算  奖励 从平台奖励用户amount转到用户reward_money
     * 平台奖励账号amount减少
     * @param int $uid
     * @param $money
     * @return array
     */
    private function transfer_763_out($uid,$money)
    {
        return $this->transfer_762_out($uid, $money);
    }

    /**
     * 用户reward_money增加
     * @param $uid
     * @param $money
     * @return array
     */
    private function transfer_763_in($uid,$money)
    {
        $return_data = $this->transferDefaultData();
        $user_account = M('user_account')->lock(1)->find($uid);
        $user_account_new = [
            'uid' => $user_account['uid'],
            'reward_money' => $user_account['reward_money'] + $money,
            'version' => $user_account['version'],
        ];

        $user_account_record_data = [
            'reward_money' => [
                'from' => $user_account['reward_money'],
                'to' => $user_account_new['reward_money']
            ],
        ];

        $return_data['user_account_data'] = $user_account_new;
        $return_data['user_account_record_data'] = $user_account_record_data;
        $return_data['flag'] = TicketModel::INCOMING_FLAG;

        return $return_data;
    }

    /**
     * OBJ_TYPE_XRPJ_ACTIVITY = 764;
     * 司马小鑫结算 加成 从平台加成账号amount转到用户reward_money
     * @param  int $uid
     * @param $money
     * @return array
     */
    private function transfer_764_out($uid,$money)
    {
        return $this->transfer_763_out($uid, $money);
    }

    /**
     * 回款在途转移到余额 zs_repaying_money => zs_amount
     * @param $uid
     * @param $money
     * @param $profit
     * @param $free_money
     * @return array
     */
    private function transfer_773($uid, $money, $profit, $free_money)
    {
        $return_data = $this->transferDefaultData();
        $user_account = M('user_account')->lock(1)->find($uid);
        if ($user_account['zs_repaying_money'] < $money) {
            $return_data['message'] = '回款金额转移中:zs_repaying_money='.$user_account['zs_repaying_money'];
        }

        $user_account_new = [
            'uid' => $uid,
            'zs_repaying_money' => $user_account['zs_repaying_money'] - $money,
            'zs_amount' => $user_account['zs_amount'] + $money,
            'version' => $user_account['version'],
        ];

        $user_account_record_data = [
            'zs_repaying_money' => [
                'from' => $user_account['zs_repaying_money'],
                'to' => $user_account_new['zs_repaying_money']
            ],
            'zs_amount' => [
                'from' => $user_account['zs_amount'],
                'to' => $user_account_new['zs_amount']
            ],
        ];

        if ($profit > 0) {
            $user_account_new['profit'] = $user_account['profit'] + $profit;
            $user_account_record_data['profit'] = array(
                'from' => $user_account['profit'],
                'to' => $user_account_new['profit']
            );
            $user_account_new['free_money'] = $user_account['free_money'] + $profit;
            $user_account_record_data['free_money'] = array(
                'from' => $user_account['free_money'],
                'to' => $user_account_new['free_money']
            );
        }

        $return_data['user_account_data'] = $user_account_new;
        $return_data['user_account_record_data'] = $user_account_record_data;
        $return_data['flag'] = TicketModel::INCOMING_FLAG;

        return $return_data;
    }

    /**
     * 鑫合汇余额转移到余额转移在途  amount => zs_moving_money reward_amount => zs_reward_moving_money
     * @param $uid
     * @param $money    amount + reward_amount
     * @param $reward_money
     * @return array
     */
    private function transfer_774($uid, $money, $reward_money = 0)
    {
        $return_data = $this->transferDefaultData();
        $user_account = M('user_account')->lock(1)->find($uid);
        if ($user_account['amount'] + $user_account['reward_amount']< $money) {
            $return_data['message'] = '鑫合汇余额转移到余额转移在途:amount='.$user_account['amount'] + $user_account['reward_amount'];
        }
        $money = $money - $reward_money;
        $user_account_new = [
            'uid' => $uid,
            'amount' => $user_account['amount'] - $money,
            'reward_money' => $user_account['reward_money'] - $reward_money,
            'zs_moving_money' => $user_account['zs_moving_money'] + $money,
            'zs_moving_reward_money' => $user_account['zs_moving_reward_money'] + $reward_money,
            'version' => $user_account['version'],
        ];

        $user_account_record_data = [
            'zs_moving_money' => [
                'from' => $user_account['zs_moving_money'],
                'to' => $user_account_new['zs_moving_money']
            ],
            'zs_moving_reward_money' => [
                'from' => $user_account['zs_moving_reward_money'],
                'to' => $user_account_new['zs_moving_reward_money']
            ],
            'amount' => [
                'from' => $user_account['amount'],
                'to' => $user_account_new['amount']
            ],
            'reward_money' => [
                'from' => $user_account['reward_money'],
                'to' => $user_account_new['reward_money']
            ],
        ];

        $return_data['user_account_data'] = $user_account_new;
        $return_data['user_account_record_data'] = $user_account_record_data;
        $return_data['flag'] = TicketModel::OUTGOING_FLAG;

        return $return_data;
    }
    
    /**
     * 余额转移在途到浙商余额 zs_moving_money => zs_amount
     * @param $uid
     * @param $money
     * @return array
     */
    private function transfer_775($uid, $money, $reward_money)
    {
        $return_data = $this->transferDefaultData();
        $user_account = M('user_account')->lock(1)->find($uid);
        if ($user_account['amount'] + $user_account['reward_amount']< $money) {
            $return_data['message'] = '鑫合汇余额转移到余额转移在途:amount='.$user_account['amount'] + $user_account['reward_amount'];
        }
        $money = $money - $reward_money;
        $user_account_new = [
            'uid' => $uid,
            'zs_amount' => $user_account['zs_amount'] + $money,
            'zs_reward_money' => $user_account['zs_reward_money'] + $reward_money,
            'zs_moving_money' => $user_account['zs_moving_money'] - $money,
            'zs_moving_reward_money' => $user_account['zs_moving_reward_money'] - $reward_money,
            'version' => $user_account['version'],
        ];

        $user_account_record_data = [
            'zs_moving_money' => [
                'from' => $user_account['zs_moving_money'],
                'to' => $user_account_new['zs_moving_money']
            ],
            'zs_moving_reward_money' => [
                'from' => $user_account['zs_moving_reward_money'],
                'to' => $user_account_new['zs_moving_reward_money']
            ],
            'zs_amount' => [
                'from' => $user_account['zs_amount'],
                'to' => $user_account_new['zs_amount']
            ],
            'zs_reward_money' => [
                'from' => $user_account['zs_reward_money'],
                'to' => $user_account_new['zs_reward_money']
            ],
        ];

        $return_data['user_account_data'] = $user_account_new;
        $return_data['user_account_record_data'] = $user_account_record_data;
        $return_data['flag'] = TicketModel::INCOMING_FLAG;

        return $return_data;
    }

    /**
     * 用户reward_money增加
     * @param $uid
     * @param $money
     * @return array
     */
    private function transfer_764_in($uid,$money)
    {
        return $this->transfer_763_in($uid, $money);
    }

    /**
     * OBJ_TYPE_XPRJ_JIAXI_ADDON = 765;
     * 司马小鑫结算 加息 从平台账号amount转到用户amount
     * @param int $uid
     * @param $money
     * @return array
     */
    private function transfer_765_out($uid,$money) {
        $return_data = $this->transferDefaultData();
        $user_account = M('user_account')->lock(1)->find($uid);
        if ($user_account['amount'] < $money) {
            $return_data['message'] = '司马小鑫结算中 765 out:amount='.$user_account['amount'];
        }

        $user_account_new = [
            'uid' => $user_account['uid'],
            'amount' => $user_account['amount'] - $money,
            'version' => $user_account['version'],
        ];

        $user_account_record_data = [
            'amount' => [
                'from' => $user_account['amount'],
                'to' => $user_account_new['amount']
            ],
        ];

        $return_data['user_account_data'] = $user_account_new;
        $return_data['user_account_record_data'] = $user_account_record_data;
        $return_data['flag'] = TicketModel::OUTGOING_FLAG;

        return $return_data;
    }

    private function transfer_765_in($uid, $money) {
        $return_data = $this->transferDefaultData();
        $user_account = M('user_account')->lock(1)->find($uid);
        $user_account_new = [
            'uid' => $user_account['uid'],
            'amount' => $user_account['amount'] + $money,
            'version' => $user_account['version'],
        ];

        $user_account_record_data = [
            'amount' => [
                'from' => $user_account['amount'],
                'to' => $user_account_new['amount']
            ],
        ];

        $return_data['user_account_data'] = $user_account_new;
        $return_data['user_account_record_data'] = $user_account_record_data;
        $return_data['flag'] = TicketModel::INCOMING_FLAG;

        return $return_data;
    }

    //冲正充值
    public function czRecharge($record){
        $ticket = D('Payment/Ticket')->find($record['ticket_id']);
        if(!$ticket) throw_exception("系统异常，没有对应的票据");
        $uid = (int)$ticket['uid'];
        $ticketId = (int)$ticket['id'];
        $money = $ticket['amount'];
        service('PaymentService');

        $user = M('user')->find($uid);
        if(!$user) throw_exception("用户不存在");
        if(!$user['is_active']) throw_exception("用户未激活");

        $user_account = M('user_account')->find($uid);
        if(!$user_account) throw_exception("账户不存在");

        if($ticket['status'] != TicketModel::STATUS_FINISHED
            && $ticket['status'] != TicketModel::STATUS_QFX_FINISHED) throw_exception("数据异常");

        try{
            $user_account_data['uid'] = $uid;
            if($ticket['is_repay_in']) {
                $user_account_data['repay_freeze_money'] = $user_account['repay_freeze_money'] - $money;
                $user_account_data['repay_freeeze_cache'] = $user_account['repay_freeeze_cache'] - $money;
                $changeParams['repay_freeze_money'] = array('from'=>$user_account['repay_freeze_money'], 'to'=>$user_account_data['repay_freeze_money']);
                if($user_account_data['repay_freeze_money']<0) throw_exception("余额不足");

                $objType = self::OBJ_TYPE_REPAYRECHARGE_CZ;
                if(D('Payment/Ticket')->getTDesc($ticket, "deposit_prj_id")){
                    $objType = self::OBJ_TYPE_DEPOSITRECHARGE_CZ;
                }
                if(D('Payment/Ticket')->getTDesc($ticket, "zhr_re_prj_id")){
                    $objType = self::OBJ_TYPE_ZRRECHARGE_CZ;
                }
            } else {
                if($this->getAmountCache($uid) && (($user_account['amount']) != $this->getAmountCache($uid)) ) throw_exception("并发异常, 请等10秒钟，再重试");
                $user_account_data['amount'] = $user_account['amount'] - $money;
                if($user_account_data['amount']<0) throw_exception("余额不足");
                $changeParams['amount'] = array('from'=>$user_account['amount'], 'to'=>$user_account_data['amount']);
                $objType = self::OBJ_TYPE_RECHARGE_CZ;
                if($ticket['status'] == TicketModel::STATUS_QFX_FINISHED) $objType = self::OBJ_TYPE_QFXRECHARGE_CZ;
            }
            $rest = D('Payment/Ticket')->save(array('id'=>$ticket['id'], 'status'=>TicketModel::STATUS_CZ));
            if(!$rest) throw_exception("系统数据库异常SR1TCZ");
            service('PaymentService');

            $user_account_data['cumu_recharge_money'] = $user_account['cumu_recharge_money'] - $money;//累计充值金额
            $user_account_data['mtime'] = time();
            $user_account_data['version'] = $user_account['version'];
            $res = D("Payment/PayAccount")->save($user_account_data);
            if(!$res) throw_exception("系统数据库异常SR1CZ");
            $remark = "针对资金充值记录".$record['record_no']."的冲正";

            $this->addRecord($uid, $objType, $ticketId, TicketModel::OUTGOING_FLAG, $money,
                $changeParams
                , array('ticket_id'=>$ticketId, 'record_id'=>$record['id']), '', '', false, $remark);
        }catch(Exception $e){throw $e;}
        if($ticket['is_repay_in']) {
            $this->setRepayFreezeMoneyCache($uid, $user_account_data['repay_freeze_money']);
        } else {
            $this->setAmountCache($uid, $user_account_data['amount']);
        }
    }
	
	//外部充值到系统 @only 只供ticketModel recharge充值
	public function submitRecharge($ticket){
// 		$this->legalCheck($ticket['uid']);
		
		$uid = (int)$ticket['uid'];
		$ticketId = (int)$ticket['id'];
		$money = $ticket['amount'];
		service('PaymentService');
		if($money > PaymentService::$maxCashoutAmount) {
			throw_exception("充入的金额过大不支持");
		}
		
		$user = M('user')->find($uid);
		if(!$user) throw_exception("用户不存在");
		if(!$user['is_active']) throw_exception("用户未激活");
        service('PaymentService');
		$user_account = M('user_account')->find($uid);
		if(!$user_account) throw_exception("账户不存在");
		if($user_account['status'] == self::STATUS_FREEZE) throw_exception("该支付账户已经冻结");
		if($user_account['status'] == self::STATUS_FORCEOUT) throw_exception("该支付账户已经被封杀");
		$ticket = D('Payment/Ticket')->find($ticketId);
		if(!$ticket) throw_exception("系统异常，没有对应的票据");
		if($ticket['status'] != TicketModel::STATUS_FINISHED
            && $ticket['status'] != TicketModel::STATUS_QFX_FINISHED) throw_exception("数据异常");
		
		try{
			$user_account_data['uid'] = $uid;
			if($ticket['is_repay_in']) {
				if($this->getRepayFreezeMoneyCache($uid) && (($user_account['repay_freeze_money']) != $this->getRepayFreezeMoneyCache($uid)) ) throw_exception("并发异常, 请等10秒钟，再重试");
				$user_account_data['repay_freeze_money'] = $user_account['repay_freeze_money'] + $money;
				$user_account_data['repay_freeeze_cache'] = $user_account['repay_freeeze_cache'] + $money;
				$changeParams['repay_freeze_money'] = array('from'=>$user_account['repay_freeze_money'], 'to'=>$user_account_data['repay_freeze_money']);
				$objType = self::OBJ_TYPE_REPAYRECHARGE;
				if(D('Payment/Ticket')->getTDesc($ticket, "deposit_prj_id")){
					$objType = self::OBJ_TYPE_DEPOSITRECHARGE;
				}
				if(D('Payment/Ticket')->getTDesc($ticket, "zhr_re_prj_id")){
					$objType = self::OBJ_TYPE_ZRRECHARGE;
				}
            } else {
                $objType = self::OBJ_TYPE_RECHARGE;
                if($this->getAmountCache($uid) && (($user_account['amount']) != $this->getAmountCache($uid)) ) throw_exception("并发异常, 请等10秒钟，再重试");
                $user_account_data['amount'] = $user_account['amount'] + $money;
                if( substr($ticket['channel'],0, 8) == PaymentService::TYPE_ZHESHANG ){
                    $user_account_data['zs_amount'] = $user_account['zs_amount'] + $money;
                    $user_account_data['zs_recharging_money'] = $user_account['zs_recharging_money'] + $money;
                    $changeParams['zs_amount'] = array('from'=>$user_account['zs_amount'], 'to'=>$user_account_data['zs_amount']);
                    $user_account_data['amount'] = $user_account['amount'];
                    $objType = self::OBJ_TYPE_ZS_RECHARGING;
                }
                $changeParams['amount'] = array('from'=>$user_account['amount'], 'to'=>$user_account_data['amount']);
                if($ticket['status'] == TicketModel::STATUS_QFX_FINISHED) $objType = self::OBJ_TYPE_QFXRECHARGE;
			}
			if(($ticket['channel'] == PaymentService::TYPE_XIANXIA || $ticket['channel'] == PaymentService::TYPE_STOCK_TRADING) && !$ticket['is_repay_in']){//免费额度
				$user_account_data['free_money'] = $user_account['free_money'] + $money;
				$changeParams['free_money'] = array('from'=>$user_account['free_money'], 'to'=>$user_account_data['free_money']);
			}
			$user_account_data['cumu_recharge_money'] = $user_account['cumu_recharge_money'] + $money;//累计充值金额
			$user_account_data['mtime'] = time();
// 			$res = M('user_account')->save($user_account_data);
			$user_account_data['version'] = $user_account['version'];
			$res = D("Payment/PayAccount")->save($user_account_data);
			if(!$res) throw_exception("系统数据库异常SR1");
            $remark = D('Payment/Ticket')->getTDesc($ticket, "remark");
			
			$this->addRecord($uid, $objType, $ticketId, TicketModel::INCOMING_FLAG, $money,
					$changeParams
					, array('ticket_id'=>$ticketId), '', '', false, $remark);
		}catch(Exception $e){throw $e;}
		if($ticket['is_repay_in']) {
			$this->setRepayFreezeMoneyCache($uid, $user_account_data['repay_freeze_money']);
		} else {
			$this->setAmountCache($uid, $user_account_data['amount']);
		}
	}
	
	//提现系统金额到外部 @only 只供ticketModel cashout提现用, $is_prjPay给项目打款
	public function cashout($uid, $money, $ticketId, $free_times, $free_money, $is_prjPay=0){
//		$this->legalCheck($uid);
		
		$uid = (int)$uid;
		$ticketId = (int)$ticketId;
		$user_account = M('user_account')->find($uid);
		if(!$user_account) throw_exception("账户不存在");
		$ticket = D('Payment/Ticket')->find($ticketId);
		if(!$ticket || !$ticket['cashout_id']) throw_exception("系统异常，没有对应的票据");
        $amount_key = 'amount';
        $reward_money_key = 'reward_money';
        $cash_freeze_money = 'cash_freeze_money';
        if( $ticket['channel'] ==  PaymentService::TYPE_ZHESHANG ){
            $amount_key = 'zs_amount';
            $reward_money_key = 'zs_reward_money';
            $cash_freeze_money = 'zs_cash_freeze_money';
        }
		if($is_prjPay){
			if($user_account[$amount_key] < $money) throw_exception($uid.":".$user_account[$amount_key].":".$money."没有这么多提现金额，提现失败");
		} else {
			if($user_account[$cash_freeze_money] < $money) throw_exception($uid.":".$user_account[$cash_freeze_money].":".$money."没有这么多提现金额，提现失败");
		}
		
		try{
			$user_account_data['uid'] = $uid;
			if($is_prjPay){
				if($this->getAmountCache($uid) && (($user_account[$amount_key]) != $this->getAmountCache($uid)) ) throw_exception("提现并发异常");
				$obj_type = self::OBJ_TYPE_CASHOUTPRJDONE;
				$user_account_data[$amount_key] = $user_account[$amount_key] - $money;
				$param[$amount_key] = array('from'=>$user_account[$amount_key], 'to'=>$user_account_data[$amount_key]);
			} else {
				if($this->getCashFreezeMoneyCache($uid) && (($user_account[$cash_freeze_money]) != $this->getCashFreezeMoneyCache($uid)) ) throw_exception("并发异常");
				$obj_type = self::OBJ_TYPE_CASHOUTDONE;
				$user_account_data[$cash_freeze_money] = $user_account[$cash_freeze_money] - $money;
				$param[$cash_freeze_money] = array('from'=>$user_account[$cash_freeze_money], 'to'=>$user_account_data[$cash_freeze_money]);
				
// 				if($free_times){
// 					$user_account_data['free_times'] = $user_account['free_times'] - 1;
// 					if($user_account_data['free_times'] < 0) throw_exception("数据异常so1");
// 					$free_money = 0;
// 					$param['free_times'] = array('from'=>$user_account['free_times'], 'to'=>$user_account_data['free_times']);
// 				} else {
// 					$user_account_data['free_money'] = $user_account['free_money'] - $free_money;
// 					if($user_account_data['free_money'] < 0) throw_exception("数据异常so2");
// 					$param['free_money'] = array('from'=>$user_account['free_money'], 'to'=>$user_account_data['free_money']);
// 				}
			}
	 		$user_account_data['mtime'] = time();
// 			$res = M('user_account')->save($user_account_data);
	 		$user_account_data['version'] = $user_account['version'];
	 		$res = D("Payment/PayAccount")->save($user_account_data);
			if(!$res) throw_exception("系统数据库异常CO1");
			
			$this->addRecord($uid, $obj_type, $ticket['cashout_id'], TicketModel::OUTGOING_FLAG, $money,
					$param
					, array('cash_id'=>$ticket['cashout_id'], 'ticket_id'=>$ticketId), '', '');
		}catch(Exception $e){throw $e;}
		if($is_prjPay) {
			$this->setAmountCache($uid, $user_account_data[$amount_key]);
		} else {
			$this->setCashFreezeMoneyCache($uid, $user_account_data[$cash_freeze_money]);
		}
	}
	
	//临时处理代码
	public function cashoutMoneyBack($user_account){
		try{
		$user_account_data['uid'] = $user_account['uid'];
		$user_account_data['amount'] = $user_account['amount'] + $user_account['cash_freeze_money'];
		$user_account_data['cash_freeze_money'] = 0;
		$user_account_data['version'] = $user_account['version'];
		$res = D("Payment/PayAccount")->save($user_account_data);
		if(!$res) throw_exception("系统数据库异常xxx1");
		$this->addRecord($user_account['uid'], self::OBJ_TYPE_CASHOUTFAIL, $user_account['uid'], TicketModel::INCOMING_FLAG, $user_account['cash_freeze_money'],
				array(
						'amount'=>array('from'=>$user_account['amount'], 'to'=>$user_account_data['amount']),
						'cash_freeze_money'=>array('from'=>$user_account['cash_freeze_money'], 'to'=>$user_account_data['cash_freeze_money']),
				)
				, array(), '', '');
		}catch(Exception $e){throw $e;}
	}
	
	//提现系统金额到外部失败回滚 @only 只供cashoutApplyModel deal调用
	public function cashfailed($cashout_apply){
		$uid = $cashout_apply['uid'];
		$this->legalCheck($uid);
		$money = $cashout_apply['money'] + $cashout_apply['fee'];
		$user_account = M('user_account')->find($uid);
		if(!$user_account) throw_exception("账户不存在");
        $amount_key = 'amount';
        $reward_money_key = 'reward_money';
        $cash_freeze_money = 'cash_freeze_money';
        if( $cashout_apply['channel'] ==  PaymentService::TYPE_ZHESHANG ){
            $amount_key = 'zs_amount';
            $reward_money_key = 'zs_reward_money';
            $cash_freeze_money = 'zs_cash_freeze_money';
        }
		if($this->getCashFreezeMoneyCache($uid) && (($user_account['cash_freeze_money']) != $this->getCashFreezeMoneyCache($uid)) ) throw_exception("提现取消并发异常");
		if($user_account['cash_freeze_money'] < $money) throw_exception("没有这么多提现金额，处理异常");
		try{
			$user_account_data['uid'] = $uid;
			$user_account_data[$amount_key] = $user_account[$amount_key] + $money - $cashout_apply['reward_money'];
			$user_account_data[$reward_money_key] = $user_account[$reward_money_key] + $cashout_apply['reward_money'];
			$user_account_data['free_money'] = $user_account['free_money'] + $cashout_apply['free_money'];
			$user_account_data[$cash_freeze_money] = $user_account[$cash_freeze_money] - $money;
			$user_account_data['cumu_cashout_times'] = $user_account['cumu_cashout_times'] - 1; //提现次数还回去
			if($cashout_apply['free_tixian_times'])
				$user_account_data['free_tixian_times'] = $user_account['free_tixian_times'] + $cashout_apply['free_tixian_times']; //免费手续费次数还回去
			$user_account_data['mtime'] = time();
// 			$res = M('user_account')->save($user_account_data);
			$user_account_data['version'] = $user_account['version'];
			$res = D("Payment/PayAccount")->save($user_account_data);
			if(!$res) throw_exception("系统数据库异常COf1");
			D('Payment/Ticket');
			$this->addRecord($uid, self::OBJ_TYPE_CASHOUTFAIL, $cashout_apply['id'], TicketModel::INCOMING_FLAG, $money,
					array(
                        $amount_key=>array('from'=>$user_account[$amount_key], 'to'=>$user_account_data[$amount_key]),
							$reward_money_key=>array('from'=>$user_account[$reward_money_key], 'to'=>$user_account_data[$reward_money_key]),
							'free_money'=>array('from'=>$user_account['free_money'], 'to'=>$user_account_data['free_money']),
                            $cash_freeze_money=>array('from'=>$user_account[$cash_freeze_money], 'to'=>$user_account_data[$cash_freeze_money]),
					)
					, array('cash_id'=>$cashout_apply['id']), '', '');
		}catch(Exception $e){throw $e;}
		$this->setAmountCache($uid, $user_account_data[$amount_key]);
		$this->setRewardCache($uid, $user_account_data[$reward_money_key]);
		$this->setCashFreezeMoneyCache($uid, $user_account_data['cash_freeze_money']);
	}
	
	public function addFreeMoney($uid, $free_money){
// 		$this->legalCheck($uid);
		$user_account = M('user_account')->find($uid);
		if(!$user_account) throw_exception("账户不存在");
		if($this->getAmountCache($uid) && (($user_account['amount']) != $this->getAmountCache($uid)) ) throw_exception("出账并发异常");
		try{
			$user_account_data['uid'] = $uid;
			$user_account_data['free_money'] = $user_account['free_money'] + $free_money;
			$user_account_data['mtime'] = time();
// 			$res = M('user_account')->save($user_account_data);
			$user_account_data['version'] = $user_account['version'];
			$res = D("Payment/PayAccount")->save($user_account_data);
			if(!$res) throw_exception("系统数据库异常CFR1");
			D('Payment/Ticket');
			$this->addRecord($uid, self::OBJ_TYPE_REWARDFREEMONEY, $uid, TicketModel::INCOMING_FLAG, $free_money,
				array(
						'free_money'=>array('from'=>$user_account['free_money'], 'to'=>$user_account_data['free_money']),
				)
				, array(), '', '');
		}catch(Exception $e){throw $e;}
	}
	
	/**
	 * 
	 * @param int $uid 用户id
	 * @param int $obj_type 对象类型 OBJ_TYPE_的常量
	 * @param int $obj_id 对象id
	 * @param int $in_or_out 进账或出账 TicketModel的常量
	 * @param int $change_amount 变化金额
	 * @param array $changeParams 变化参数
	 * @param int $to_user 出账如果是站内的话，所到的用户id，如果to_user是商户，就要设置merchant_id 如果是入账就设置到from_user
	 * $desc 描述
	 * $idParams ticket_id,cash_id,pay_order_id设置
	 */
    /**
     * @param $uid /用户id
     * @param $obj_type /对象类型 OBJ_TYPE_的常量
     * @param $obj_id /对象id
     * @param $in_or_out
     * @param $change_amount
     * @param $changeParams
     * @param $idParams
     * @param $desc
     * @param string $to_user
     * @param bool $isMerchant
     * @param string $remark
     * @return mixed
     */
	private function addRecord(
        $uid,
        $obj_type,
        $obj_id,
        $in_or_out,
        $change_amount,
        $changeParams,
        $idParams,
        $desc,
        $to_user='',
        $isMerchant=false,
        $remark=''
    ){
		$uid = (int)$uid;
		$to_user = (int)$to_user;
		$user_account = M('user_account')->find($uid);
		if(!$user_account) throw_exception("请先设置支付密码");
		if(!$changeParams) throw_exception("参数异常");
		$record_data['uid'] = $uid;
		$record_data['record_no'] = $this->createNo();
		if($obj_type) $record_data['obj_type'] = $obj_type;
		if($obj_id) $record_data['obj_id'] = (int)$obj_id;
		$record_data['in_or_out'] = $in_or_out;
		$record_data['change_amount'] = $change_amount;
		$record_data['adesc'] = $desc;
        $record_data['remark'] = $remark;
        if(!$remark && $this->desc_eq_remark) {
            $record_data['remark'] = $desc;
        }
		if($to_user){
			D('Payment/Ticket');
			$to_user_account = M('user_account')->find($to_user);
			if($to_user_account){
				if($in_or_out == TicketModel::OUTGOING_FLAG){
					$record_data['to_user'] = $to_user;
					if($to_user_account['is_merchant'] && $isMerchant){
						$record_data['merchant_id'] = $to_user;
					}
				} else if($in_or_out == TicketModel::INCOMING_FLAG){
					$record_data['from_user'] = $to_user;
				}
			}
		}
		if($idParams){
			if($idParams['ticket_id']) $record_data['ticket_id'] = $idParams['ticket_id'];
			if($idParams['cash_id']) $record_data['cash_id'] = $idParams['cash_id'];
			if($idParams['pay_order_id']) $record_data['pay_order_id'] = $idParams['pay_order_id'];
            if($idParams['record_id']) $record_data['record_id'] = $idParams['record_id'];
		}
		if($changeParams['amount']){
			$record_data['from_amount'] = $changeParams['amount']['from'];
			$record_data['to_amount'] = $changeParams['amount']['to'];
		} else {
			$record_data['from_amount'] = $user_account['amount'];
			$record_data['to_amount'] = $user_account['amount'];
		}

		if($changeParams['reward_money']){
			$record_data['from_reward_money'] = $changeParams['reward_money']['from'];
			$record_data['to_reward_money'] = $changeParams['reward_money']['to'];
		} else {
			$record_data['from_reward_money'] = $user_account['reward_money'];
			$record_data['to_reward_money'] = $user_account['reward_money'];
		}
		if($changeParams['invest_reward_money']){
			$record_data['from_invest_reward_money'] = $changeParams['invest_reward_money']['from'];
			$record_data['to_invest_reward_money'] = $changeParams['invest_reward_money']['to'];
		} else {
			$record_data['from_invest_reward_money'] = $user_account['invest_reward_money'];
			$record_data['to_invest_reward_money'] = $user_account['invest_reward_money'];
		}
		if($changeParams['coupon_money']){
			$record_data['from_coupon_money'] = $changeParams['coupon_money']['from'];
			$record_data['to_coupon_money'] = $changeParams['coupon_money']['to'];
		} else {
			$record_data['from_coupon_money'] = $user_account['coupon_money'];
			$record_data['to_coupon_money'] = $user_account['coupon_money'];
		}
		
		if($changeParams['buy_freeze_money']){
			$record_data['from_buy_freeze_money'] = $changeParams['buy_freeze_money']['from'];
			$record_data['to_buy_freeze_money'] = $changeParams['buy_freeze_money']['to'];
		} else {
			$record_data['from_buy_freeze_money'] = $user_account['buy_freeze_money'];
			$record_data['to_buy_freeze_money'] = $user_account['buy_freeze_money'];
		}
		
		if($changeParams['cash_freeze_money']){
			$record_data['from_cash_freeze_money'] = $changeParams['cash_freeze_money']['from'];
			$record_data['to_cash_freeze_money'] = $changeParams['cash_freeze_money']['to'];
		} else {
			$record_data['from_cash_freeze_money'] = $user_account['cash_freeze_money'];
			$record_data['to_cash_freeze_money'] = $user_account['cash_freeze_money'];
		}
		
		if($changeParams['repay_freeze_money']){
			$record_data['from_repay_freeze_money'] = $changeParams['repay_freeze_money']['from'];
			$record_data['to_repay_freeze_money'] = $changeParams['repay_freeze_money']['to'];
		} else {
			$record_data['from_repay_freeze_money'] = $user_account['repay_freeze_money'];
			$record_data['to_repay_freeze_money'] = $user_account['repay_freeze_money'];
		}
		
		if($changeParams['profit']){
			$record_data['from_profit'] = $changeParams['profit']['from'];
			$record_data['to_profit'] = $changeParams['profit']['to'];
		} else {
			$record_data['from_profit'] = $user_account['profit'];
			$record_data['to_profit'] = $user_account['profit'];
		}
		
		if($changeParams['capital']){
			$record_data['from_capital'] = $changeParams['capital']['from'];
			$record_data['to_capital'] = $changeParams['capital']['to'];
		} else {
			$record_data['from_capital'] = $user_account['capital'];
			$record_data['to_capital'] = $user_account['capital'];
		}
		
		if($changeParams['free_times']){
			$record_data['from_free_times'] = $changeParams['free_times']['from'];
			$record_data['to_free_times'] = $changeParams['free_times']['to'];
		} else {
			$record_data['from_free_times'] = $user_account['free_times'];
			$record_data['to_free_times'] = $user_account['free_times'];
		}
		
		if($changeParams['free_money']){
			$record_data['from_free_money'] = $changeParams['free_money']['from'];
			$record_data['to_free_money'] = $changeParams['free_money']['to'];
		} else {
			$record_data['from_free_money'] = $user_account['free_money'];
			$record_data['to_free_money'] = $user_account['free_money'];
		}
		
		if($changeParams['freeze_free_money']){
			$record_data['from_freeze_free_money'] = $changeParams['freeze_free_money']['from'];
			$record_data['to_freeze_free_money'] = $changeParams['freeze_free_money']['to'];
		} else {
			$record_data['from_freeze_free_money'] = $user_account['freeze_free_money'];
			$record_data['to_freeze_free_money'] = $user_account['freeze_free_money'];
		}

        //以下两个针对司马小鑫加入的时候
        if ($changeParams['amountx']) {
            $record_data['from_amountx'] = $changeParams['amountx']['from'];
            $record_data['to_amountx'] = $changeParams['amountx']['to'];
        } else {
            $record_data['from_amountx'] = $user_account['amountx'];
            $record_data['to_amountx'] = $user_account['amountx'];
        }

        if ($changeParams['buy_freeze_moneyx']) {
            $record_data['from_buy_freeze_moneyx'] = $changeParams['buy_freeze_moneyx']['from'];
            $record_data['to_buy_freeze_moneyx'] = $changeParams['buy_freeze_moneyx']['to'];
        } else {
            $record_data['from_buy_freeze_moneyx'] = $user_account['buy_freeze_moneyx'];
            $record_data['to_buy_freeze_moneyx'] = $user_account['buy_freeze_moneyx'];
        }

        //浙商存管新增
        if($changeParams['zs_amount']){
            $record_data['is_zs'] = 1;
            $record_data['from_zs_amount'] = $changeParams['zs_amount']['from'];
            $record_data['to_zs_amount'] = $changeParams['zs_amount']['to'];
        } else {
            $record_data['from_zs_amount'] = $user_account['zs_amount'];
            $record_data['to_zs_amount'] = $user_account['zs_amount'];
        }

        if($changeParams['zs_recharging_money']){
            $record_data['is_zs'] = 1;
            $record_data['from_zs_recharging_money'] = $changeParams['zs_recharging_money']['from'];
            $record_data['to_zs_recharging_money'] = $changeParams['zs_recharging_money']['to'];
        } else {
            $record_data['from_zs_recharging_money'] = $user_account['zs_recharging_money'];
            $record_data['to_zs_recharging_money'] = $user_account['zs_recharging_money'];
        }

        if($changeParams['zs_repaying_money']){
            $record_data['is_zs'] = 1;
            $record_data['from_zs_repaying_money'] = $changeParams['zs_repaying_money']['from'];
            $record_data['to_zs_repaying_money'] = $changeParams['zs_repaying_money']['to'];
        } else {
            $record_data['from_zs_repaying_money'] = $user_account['zs_repaying_money'];
            $record_data['to_zs_repaying_money'] = $user_account['zs_repaying_money'];
        }

        //浙商存管新增
        if($changeParams['zs_amount']){
            $record_data['is_zs'] = 1;
            $record_data['from_zs_amount'] = $changeParams['zs_amount']['from'];
            $record_data['to_zs_amount'] = $changeParams['zs_amount']['to'];
        } else {
            $record_data['from_zs_amount'] = $user_account['zs_amount'];
            $record_data['to_zs_amount'] = $user_account['zs_amount'];
        }

        if($changeParams['zs_reward_money']){
            $record_data['is_zs'] = 1;
            $record_data['from_zs_reward_money'] = $changeParams['zs_reward_money']['from'];
            $record_data['to_zs_reward_money'] = $changeParams['zs_reward_money']['to'];
        } else {
            $record_data['from_zs_reward_money'] = $user_account['zs_reward_money'];
            $record_data['to_zs_reward_money'] = $user_account['zs_reward_money'];
        }

        if($changeParams['zs_recharging_money']){
            $record_data['is_zs'] = 1;
            $record_data['from_zs_recharging_money'] = $changeParams['zs_recharging_money']['from'];
            $record_data['to_zs_recharging_money'] = $changeParams['zs_recharging_money']['to'];
        } else {
            $record_data['from_zs_recharging_money'] = $user_account['zs_recharging_money'];
            $record_data['to_zs_recharging_money'] = $user_account['zs_recharging_money'];
        }

        if($changeParams['zs_repaying_money']){
            $record_data['is_zs'] = 1;
            $record_data['from_zs_repaying_money'] = $changeParams['zs_repaying_money']['from'];
            $record_data['to_zs_repaying_money'] = $changeParams['zs_repaying_money']['to'];
        } else {
            $record_data['from_zs_repaying_money'] = $user_account['zs_repaying_money'];
            $record_data['to_zs_repaying_money'] = $user_account['zs_repaying_money'];
        }

        if($changeParams['zs_moving_money']){
            $record_data['is_zs'] = 1;
            $record_data['from_zs_moving_money'] = $changeParams['zs_moving_money']['from'];
            $record_data['to_zs_moving_money'] = $changeParams['zs_moving_money']['to'];
        } else {
            $record_data['from_zs_moving_money'] = $user_account['zs_moving_money'];
            $record_data['to_zs_moving_money'] = $user_account['zs_moving_money'];
        }

        if($changeParams['zs_moving_reward_money']){
            $record_data['is_zs'] = 1;
            $record_data['from_zs_moving_reward_money'] = $changeParams['zs_moving_reward_money']['from'];
            $record_data['to_zs_moving_reward_money'] = $changeParams['zs_moving_reward_money']['to'];
        } else {
            $record_data['from_zs_moving_reward_money'] = $user_account['zs_moving_reward_money'];
            $record_data['to_zs_moving_reward_money'] = $user_account['zs_moving_reward_money'];
        }

        if($changeParams['zs_buy_freeze_money']){
            $record_data['is_zs'] = 1;
            $record_data['from_zs_buy_freeze_money'] = $changeParams['zs_buy_freeze_money']['from'];
            $record_data['to_zs_buy_freeze_money'] = $changeParams['zs_buy_freeze_money']['to'];
        } else {
            $record_data['from_zs_buy_freeze_money'] = $user_account['zs_buy_freeze_money'];
            $record_data['to_zs_buy_freeze_money'] = $user_account['zs_buy_freeze_money'];
        }
        if($changeParams['zs_cash_freeze_money']){
            $record_data['is_zs'] = 1;
            $record_data['from_zs_cash_freeze_money'] = $changeParams['zs_cash_freeze_money']['from'];
            $record_data['to_zs_cash_freeze_money'] = $changeParams['zs_cash_freeze_money']['to'];
        } else {
            $record_data['from_zs_cash_freeze_money'] = $user_account['zs_cash_freeze_money'];
            $record_data['to_zs_cash_freeze_money'] = $user_account['zs_cash_freeze_money'];
        }

		$record_data['ctime'] = time();
		$record_data['mtime'] = time();

		$id = M('user_account_record')->add($record_data);
		if(!$id) throw_exception("系统数据库异常PR1");
		$this->modifyRecordSum($record_data);

        $this->setDescEqRemark(false); // 还原掉
		return $id;
	}

    public function addXRewardRecord($params) {
        /** @var  $pay_account_model PayAccountModel */
        $pay_account_model = D('Payment/PayAccount');
        $queue_success = 0;
        try {
            $rest_money = $params['money'];
            $obj_type = $params['obj_type'];
            $x_order_id = $params['x_order_id'];
            $record_id = $params['record_id'];
            $requestId = $params['requestId'];
            $queue_num = $params['queue_num'];
            $to_user = $params['to_user'];
            $freezeAmount = $params['freeze_amount'];
            $trans_key = 'x_join_in_reward' . $requestId . microtime(true);
            $pay_account_model->startTrans($trans_key);
            foreach ($params['list'] as $cash_flow) {
                $from_user = $cash_flow['from_user'];
                $uid = $cash_flow['uid'];
                $money = $cash_flow['money'];
                //从平台账户出账
                $from_user_account = M('user_account')->find($from_user);
                $from_user_account_data['uid'] = $from_user_account['uid'];
                if ($from_user_account['amount'] < $money) {
                    \Addons\Libs\Log\Logger::err('司马小鑫奖励平台账户余额不足,uid:' . $uid . ',平台账户：' . $from_user . ',划账金额：' . $money);
                    throw_exception('司马小鑫红包、满减券平台出账账户余额不足');
                }
                $from_user_account_data['amount'] = $from_user_account['amount'] - $money;
                $from_user_account_data['mtime'] = NOW_TIME;
                $from_user_account_data['version'] = $from_user_account['version'];
                $transfer_out = $pay_account_model->save($from_user_account_data);
                //划账到用户账户
                $user_account = M('user_account')->find($uid);
                $user_account_data['uid'] = $user_account['uid'];
                $user_account_data['amountx'] = $user_account['amountx'] + $money;
                $user_account_data['mtime'] = NOW_TIME;
                $user_account_data['version'] = $user_account['version'];
                $transfer_in = $pay_account_model->save($user_account_data);
                if (!$transfer_out || !$transfer_in) {
                    //划账失败，记录日志
                    \Addons\Libs\Log\Logger::err('司马小鑫奖励划账失败,uid:' . $uid . ',平台账户：' . $from_user . ',划账金额：' . $money);
                    throw_exception('加入司马小鑫划账提交失败，请重试');
                }else{
                    $desc = $params['desc'];
                    $remark = $params['remark'];
                    //obj_type 关联对象类型
                    $changeParams = array(
                        'from_user' => array(
                            'amount' => array(
                                'from' => $from_user_account['amount'],
                                'to' => $from_user_account_data['amount'],
                            ),
                        ),
                        'to_user' => array(
                            'amountx' => array(
                                'from' => $user_account['amountx'],
                                'to' => $user_account_data['amountx'],
                            ),
                        )
                    );
                    //从平台账户出账流水
                    $ret_out = $pay_account_model->addRecord($from_user, $obj_type, (int)$x_order_id, TicketModel::OUTGOING_FLAG, $money, $changeParams['from_user'], $record_id, $desc, $uid, true, $remark);
                    //划账进用户账户流水
                    $ret_in = $pay_account_model->addRecord($uid, $obj_type, (int)$x_order_id, TicketModel::INCOMING_FLAG, $money, $changeParams['to_user'], $record_id, $desc, $from_user, true, $remark);
                    if (!$ret_out || !$ret_in) {
                        \Addons\Libs\Log\Logger::err('司马小鑫奖励生成流水失败,uid:' . $uid . ',平台账户：' . $from_user . ',划账金额：' . $money);
                        throw_exception('加入司马小鑫异步生成流水失败');
                    } else {
                        $queue_success += 1;
                    }
                }
            }

            //全部流水跑完，更新用户累计总收益、通知营销系统核销、启动匹配
            if ($queue_success === $queue_num) {
                //6.2.0 加入司马小鑫以后红包、满减券奖励直接更新到用户的累计总收益
                $account = M('user_account')->find($to_user);
                $profit_data['uid'] = $to_user;
                $profit_data['profit'] = $account['profit'] + $freezeAmount;
                $profit_data['mtime'] = NOW_TIME;
                $profit_data['version'] = $account['version'];
                $profit_res = $pay_account_model->save($profit_data);
                if(!$profit_res) {
                    \Addons\Libs\Log\Logger::err("司马小鑫奖励更新到用户的累计总收益失败", "CHANGE_ACCOUNT_PROFIT_FAIL", array('uid' => $to_user, 'freezeAmount' => $freezeAmount));
                    throw_exception('司马小鑫奖励更新到用户的累计总收益失败');
                }

                /**@var $service \App\Modules\JavaApi\Service\JavaBonusService */
                $service = new \App\Modules\JavaApi\Service\JavaBonusService();
                $ret = $service->dealUserRewardOrderByRequestId($requestId);
                if($ret['message']) { //核销系统返回错误信息
                    \Addons\Libs\Log\Logger::err('司马小鑫奖励核销失败', 'XPRJ_VERIFICATE_FAIL', array('uid' => $uid, 'from_user' => $from_user, 'rest_money' => $rest_money, 'x_order_id' => $x_order_id, 'msg' => $ret['message']));
                    throw_exception('司马小鑫奖励核销失败');
                }
                //更新司马小鑫订单的rest_money
                if($x_order_id) {
                    $data['id'] = $x_order_id;
                    $data['rest_money'] = $rest_money;
                    $r = M('xprj_order')->where(array('id' => $x_order_id))->save($data);
                    if(!$r) {
                        \Addons\Libs\Log\Logger::err('司马小鑫奖励更新rest_money失败,uid:' . $uid . '金额：' . $rest_money . ',订单id：' . $x_order_id);
                    }
                }

                //进行司马小鑫匹配
                service('Financing/XProjectMatch')->noPerfectMatching($x_order_id);
                $pay_account_model->commit($trans_key);
            }

            return true;
        } catch (\Exception $e) {
            $pay_account_model->rollback($trans_key);
            echo $e->getMessage() . PHP_EOL;
            return false;
        }
    }

    /**
     * TODO 司马小鑫相关投资回款不做统计
     * 处理user_account_record_sum表相关数据
     * @param $record_data
     * @return bool
     */
    public function modifyRecordSum($record_data)
    {
        //统计数字过滤掉司马小鑫的投资和还款 通过类型增加司马小鑫的加入和退出
        if (
        ($record_data['from_buy_freeze_moneyx'] > $record_data['to_buy_freeze_moneyx'])
        || (($record_data['to_amountx'] > $record_data['from_amountx']) && $record_data['obj_type'] != PayAccountModel::OBJ_TYPE_JOIN_IN_X)
        || $record_data['obj_type'] == self::OBJ_TYPE_PAYFREEZE_X
        || $record_data['obj_type'] == self::OBJ_TYPE_PAY_X_TRANSFER
        || $record_data['obj_type'] == self::OBJ_TYPE_PAY_X
        ) {
            return null;
        }
        $field = '';
        if ($record_data['ticket_id'] && !$record_data['cash_id']) {
            $field = 'recharge_sum';
        }
        if (!$record_data['ticket_id'] && !$record_data['cash_id']
            && !in_array($record_data['obj_type'], array(
                PayAccountModel::OBJ_TYPE_REPAY,
                PayAccountModel::OBJ_TYPE_REPAY_FREEZE,
                PayAccountModel::OBJ_TYPE_ROLLOUT,
                PayAccountModel::OBJ_TYPE_RAISINGINCOME,
                PayAccountModel::OBJ_TYPE_REWARD_CASHOUT,
                PayAccountModel::OBJ_TYPE_REWARD_INVEST,
                PayAccountModel::OBJ_TYPE_REWARDFREEMONEY,
                "90001",
                PayAccountModel::OBJ_TYPE_TRANSFER,
                PayAccountModel::OBJ_TYPE_REBACK,
            ))
        ) {
            if ($record_data['from_buy_freeze_money'] > $record_data['to_buy_freeze_money']
                || $record_data['from_zs_buy_freeze_money'] > $record_data['to_zs_buy_freeze_money']) {
                $field = 'invest_sum';
            }
        }

        //计入鑫计划 增加投资金额
        if (in_array(
            $record_data['obj_type'],
            array(
                PayAccountModel::OBJ_TYPE_JOIN_IN_X,
            ))) {
            if ($record_data['to_amountx'] > $record_data['from_amountx']) {
                $field = 'invest_sum';
            }
        }

        //老还款
        if (in_array(
            $record_data['obj_type'],
            array(
                PayAccountModel::OBJ_TYPE_REPAY,
                PayAccountModel::OBJ_TYPE_ROLLOUT,
                PayAccountModel::OBJ_TYPE_RAISINGINCOME
            ))) {
            if ($record_data['to_amount'] > $record_data['from_amount']) {
                $field = 'repay_sum';
            }
        }

        //老版的
        if (in_array($record_data['obj_type'], array(PayAccountModel::OBJ_TYPE_ALLOT))) {//新还款
            if ($record_data['to_amount'] > $record_data['from_amount']) {
                $field = 'repay_sum_old';
            }
        }

        //新版的
        //司马小鑫结算 增加还款统计
        if (in_array(
            $record_data['obj_type'],
            array(
                PayAccountModel::OBJ_TYPE_ALLOT,
                PayAccountModel::OBJ_TYPE_XRPJ_SETTLEMENT,
                PayAccountModel::OBJ_TYPE_ZS_RRPAIED,
            )
        )) {
            if (($record_data['to_amount'] > $record_data['from_amount'])
                || ($record_data['to_zs_amount'] > $record_data['from_zs_amount'])) {
                $field = 'repay_sum';
            }
        }

        if ($record_data['cash_id']) {
            if (($record_data['from_cash_freeze_money'] > $record_data['to_cash_freeze_money'])) {
                if (in_array($record_data['obj_type'], array(PayAccountModel::OBJ_TYPE_CASHOUTPRJDONE, PayAccountModel::OBJ_TYPE_CASHOUTDONE))) {
                    $field = 'cashout_sum';
                }
            }
        }
        //老版奖励
        if (in_array($record_data['obj_type'], array(
            PayAccountModel::OBJ_TYPE_REWARD_CASHOUT,
            PayAccountModel::OBJ_TYPE_REWARD_INVEST,
            PayAccountModel::OBJ_TYPE_ADD_COUPON,
            PayAccountModel::OBJ_TYPE_EXPIRE_COUPON,
            PayAccountModel::OBJ_TYPE_REWARD_CBACK,
            PayAccountModel::OBJ_TYPE_REWARD_IBACK
        ))) {
            if (($record_data['to_reward_money'] != $record_data['from_reward_money'])
                || ($record_data['to_invest_reward_money'] != $record_data['from_invest_reward_money'])
                || ($record_data['to_coupon_money'] != $record_data['from_coupon_money'])
            ) {
                $field = 'reward_sum_old';
            }
        }
        //start 新版201554(主要修改使用红包的) 新版奖励  --201565增加类型PayAccountModel::OBJ_TYPE_REWARD=10
        if (in_array($record_data['obj_type'], array(
            PayAccountModel::OBJ_TYPE_REWARD_CASHOUT,
            PayAccountModel::OBJ_TYPE_REWARD_INVEST,
            PayAccountModel::OBJ_TYPE_USE_COUPON,
            PayAccountModel::OBJ_TYPE_USER_BONUS,
            PayAccountModel::OBJ_TYPE_USER_CASH_BONUS,
            PayAccountModel::OBJ_TYPE_USER_TYJ_CASH_BONUS,
            PayAccountModel::OBJ_TYPE_REWARD,
            PayAccountModel::OBJ_TYPE_REWARD_CBACK,
            PayAccountModel::OBJ_TYPE_REWARD_IBACK,
            PayAccountModel::OBJ_TYPE_REWARD_ZS,
        ))) {
            if (($record_data['to_reward_money'] != $record_data['from_reward_money'])
                || ($record_data['to_invest_reward_money'] != $record_data['from_invest_reward_money'])
                || ($record_data['to_zs_reward_money'] != $record_data['from_zs_reward_money'])
            ) {
                $field = 'reward_sum';
            }
        }

        //司马小鑫加成和奖励直接划到amount 增加奖励统计
        if (in_array($record_data['obj_type'], array(
            PayAccountModel::OBJ_TYPE_XRPJ_RATE_ADDON,
            PayAccountModel::OBJ_TYPE_XRPJ_ACTIVITY,
        ))) {
            if (($record_data['to_reward_money'] > $record_data['from_reward_money'])) {
                $field = 'reward_sum';
            }
        }

        //201554end
        //start 添加201557增加计算速兑通手续费用的
        if ($record_data['obj_type'] == PayAccountModel::OBJ_TYPE_SHOUXU_SDT) {
            if ($record_data['in_or_out'] == 2) {
                $field = 'fee_sum';
            }
        }//201557end

        if ($record_data['obj_type'] == PayAccountModel::OBJ_TYPE_TRANSFER) {
            if ($record_data['in_or_out'] == 1) {
                $field = 'transfer_our_sum';
            }
            if ($record_data['in_or_out'] == 2) {
                $field = 'transfer_in_sum';
            }
        }
        if (!$field) {
            return null;
        }
        $record_sum = M('user_account_record_sum')->find($record_data['uid']);
        if ($record_sum) {
            if (in_array($field, array('reward_sum_old', 'reward_sum'))) {
                $field = 'reward_sum_old';//老版
                if (in_array($record_data['obj_type'], array(
                    PayAccountModel::OBJ_TYPE_REWARD_CASHOUT,
                    PayAccountModel::OBJ_TYPE_REWARD_INVEST,
                    PayAccountModel::OBJ_TYPE_ADD_COUPON
                ))) {
                    $con = M('user_account_record_sum')->where(array("uid" => $record_data['uid']))->setInc($field, $record_data['change_amount']);
                }
                if (in_array($record_data['obj_type'], array(
                    PayAccountModel::OBJ_TYPE_EXPIRE_COUPON,
                    PayAccountModel::OBJ_TYPE_REWARD_CBACK,
                    PayAccountModel::OBJ_TYPE_REWARD_IBACK
                ))) {
                    $con = M('user_account_record_sum')->where(array("uid" => $record_data['uid']))->setDec($field, $record_data['change_amount']);
                }//老版end
                //新版 201565增加类型PayAccountModel::OBJ_TYPE_REWARD=10
                $field = 'reward_sum';
                if (in_array($record_data['obj_type'], array(
                    PayAccountModel::OBJ_TYPE_REWARD_CASHOUT,
                    PayAccountModel::OBJ_TYPE_REWARD_INVEST,
                    PayAccountModel::OBJ_TYPE_USE_COUPON,
                    PayAccountModel::OBJ_TYPE_USER_BONUS,
                    PayAccountModel::OBJ_TYPE_USER_CASH_BONUS,
                    PayAccountModel::OBJ_TYPE_USER_TYJ_CASH_BONUS,
                    PayAccountModel::OBJ_TYPE_REWARD,
                    PayAccountModel::OBJ_TYPE_XRPJ_RATE_ADDON,
                    PayAccountModel::OBJ_TYPE_XRPJ_ACTIVITY,
                    PayAccountModel::OBJ_TYPE_REWARD_ZS,
                ))) {
                    $con = M('user_account_record_sum')->where(array("uid" => $record_data['uid']))->setInc($field, $record_data['change_amount']);
                }
                if (in_array(
                    $record_data['obj_type'],
                    array(
                        PayAccountModel::OBJ_TYPE_REWARD_CBACK,
                        PayAccountModel::OBJ_TYPE_REWARD_IBACK)
                )) {
                    $con = M('user_account_record_sum')->where(array("uid" => $record_data['uid']))->setDec($field, $record_data['change_amount']);
                }//201554end
            } else {
                if ($field == 'recharge_sum') {
                    if ($record_data['in_or_out'] == 1) {
                        $con = M('user_account_record_sum')->where(array("uid" => $record_data['uid']))->setInc($field,
                            $record_data['change_amount']);
                    } else {
                        $con = M('user_account_record_sum')->where(array("uid" => $record_data['uid']))->setDec($field,
                            $record_data['change_amount']);
                    }
                } else {
                    $con = M('user_account_record_sum')->where(array("uid" => $record_data['uid']))->setInc($field, $record_data['change_amount']);
                }
            }
        } else {
            $record_sum_data['uid'] = $record_data['uid'];
            $record_sum_data[$field] = $record_data['change_amount'];
            $con = M('user_account_record_sum')->add($record_sum_data);
        }
        if (isset($con) && $con) {
            return $con;
        }
        throw_exception("账户流水统计异常,没有con也没报异常");
    }
	
	//获取余额
	public function getAmount($uid){
		$user_account = M('user_account')->field('amount')->find($uid);
		if(!$user_account) throw_exception("您还没有账户");
		return $user_account['amount'];
	}
	
	//获取提现次数
	public function getCashoutTimes($user_account){
		$recTimes = (int)($user_account['cumu_recharge_money']/(2000*100));
		$casTimes = $user_account['cumu_cashout_times'];
		$rewardTimes = $user_account['reward_cashout_times'];
		return ($recTimes + $rewardTimes - $casTimes);
	}
	
	public function freeTixianTimes($uid, $times){
		$user_account = M("user_account")->find($uid);
		
		$times = (int)$times;
		$where = " where uid=".(int)$uid;
		$sql = "update fi_user_account set free_tixian_times=free_tixian_times+".$times.$where;
		$res = $this->execute($sql);
		
		D("Admin/ActionLog")->insertLog(1,"给用户".$uid."送了".$times."次免提现费用",$user_account['free_tixian_times'], $user_account['free_tixian_times']+$times);
		if(!$res) throw_exception("数据更新异常，请重新再试");
		return true;
	}
	
	public function rewardCashoutTimes($uid, $times){
		$times = (int)$times;
		$where = " where uid=".(int)$uid;
		$sql = "update fi_user_account set reward_cashout_times=reward_cashout_times+".$times.$where;
		$res = $this->execute($sql);
		if(!$res) throw_exception("数据更新异常，请重新再试");
		return true; 
	}
	//$money 充值金额 单位是分 如果元需要*100
	public function stockRecharge($user_account, $money){
		if(!$user_account['uid']) throw_exception("参数异常，该用户不存在");
		$payment = service('Payment/Payment')->init(PaymentService::TYPE_STOCK_TRADING);
		try{
			$result = $payment->submit($money, $user_account['uid'], $user_account['bank_info']['bank']
					, 0, $user_account['bank_info']['account_no'], ''
					, array('bankName'=>$user_account['bank_info']['bank_name'], 'bak'=>'股交中心注册客户，注册鑫合汇平台需要返现'.($money/100).'元'));
		} catch(Exception $e){
			$result = array('boolen'=>0, 'message'=>$e->getMessage());
		}
		if($result && $result['boolen'] == 0){
			return $result;
		}
// 		try{
// 			$ticket = array();
// 			$ticket['ticket_no'] = D('Payment/Ticket')->createNo();
// 			$ticket['uid'] = $user_account['uid'];
// 			$ticket['amount'] = $money;
// 			$ticket['fee'] = 0;
// 			$ticket['channel'] = PaymentService::TYPE_STOCK_TRADING;
// 			$ticket['status'] = TicketModel::STATUS_TODO;
// 			$ticket['is_repay_in'] = 0;
// 			$ticket['in_or_out'] = TicketModel::INCOMING_FLAG;
// 			Import("libs.Ip", ADDON_PATH);
// 			$ticket['ip'] = Ip::remote_addr();
// 			$ticket['sub_bank'] = "";
// 			$ticket['out_account_no'] = "";
// 			$ticket['out_account_id'] = "";
// 			$ticket['bak'] = "";
// 			$ticket['ticket_desc'] = "";
// 			$ticket['ctime'] = time();
// 			$ticket['mtime'] = time();
// 			$id = D('Payment/Ticket')->add($ticket);
// 			if(!$id) throw_exception("系统异常，请再试");
// 			D('Payment/Ticket')->recharge($id, TicketModel::STATUS_FINISHED, "", '', $money, array());
// 			$this->commit();
// 		}catch(Exception $e){
// 			$this->rollback();
// 			throw $e;
// 		}
	}
	
	//获取repay_freeeze_cache
    public function getRepayFreeezeCache($uid){
        $user_account = M('user_account')->find((int)$uid);
        return min($user_account['repay_freeze_money'],$user_account['repay_freeeze_cache']);
    }
	
	//减少repay_freeeze_cache金额 $money 单位是分
	public function delRepayFreeezeCache($uid, $money){
		$where = " where uid=".(int)$uid." and repay_freeeze_cache>=".(int)$money;
		$sql = "update fi_user_account set repay_freeeze_cache=repay_freeeze_cache-".(int)$money;
		$res = M('user')->execute($sql.$where);
		return $res;
	}
	
	public function legalCheck($uid, $checkStatus=1){
		$uid = (int)$uid;
		
		$user = M('user')->find($uid);
		if(!$user) throw_exception("用户不存在");
		if(!$user['is_active']) throw_exception("用户未激活");
		$user_account = M('user_account')->find($uid);
		if(!$user_account) throw_exception("账户异常");
		
		D('Account/User')->initUserRole($user);
		if(!($user_account['is_merchant'] == 1 && $user['role'])){//融资企业的商户不认证
			if(!$user['is_id_auth'] || !$user['person_id']) throw_exception($user['uid']."请先实名认证");
		}
        if($checkStatus) {
            if ($user_account['status'] == self::STATUS_FREEZE) throw_exception("该支付账户已经冻结");
            if ($user_account['status'] == self::STATUS_FORCEOUT) throw_exception("该支付账户已经被封杀");
		}

	}
	
	public function createNo(){
        $id_gen_instance = new \Addons\Libs\IdGen\IdGen();
        return $id_gen_instance->get(\Addons\Libs\IdGen\IdGen::WORK_TYPE_PAY_ORDER);
	}
	
	private function getRewardCache($uid){
// 		$key = "reward_{$uid}";
// 		return cache($key);
		return '';
	}
	
	private function setRewardCache($uid, $amout){
// 		$key = "reward_{$uid}";
// 		$amout = $amout;
// 		cache($key, $amout, array('expire'=>10));
	}
	
	private function getInvestRewardCache($uid){
// 		$key = "invest_reward_{$uid}";
// 		return cache($key);
		return '';
	}
	
	private function setInvestRewardCache($uid, $amout){
// 		$key = "invest_reward_{$uid}";
// 		$amout = $amout;
// 		cache($key, $amout, array('expire'=>10));
	}
	
	private function getCouponMoneyCache($uid){
// 		$key = "coupon_money_{$uid}";
// 		return cache($key);
		return '';
	}
	
	private function setCouponMoneyCache($uid, $amout){
// 		$key = "coupon_money_{$uid}";
// 		$amout = $amout;
// 		cache($key, $amout, array('expire'=>10));
	}
	
	private function getAmountCache($uid){
// 		$key = "amount_{$uid}";
// 		return cache($key);
		return '';
	}
	
	private function setAmountCache($uid, $amout){
// 		$key = "amount_{$uid}";
// 		$amout = $amout;
// 		cache($key, $amout, array('expire'=>10));
	}
	
	private function getRepayFreezeMoneyCache($uid){
// 		$key = "repay_freeze_money_{$uid}";
// 		return cache($key);
		return '';
	}
	
	private function setRepayFreezeMoneyCache($uid, $repay_freeze_money){
// 		$key = "repay_freeze_money_{$uid}";
// 		$repay_freeze_money = $repay_freeze_money;
// 		cache($key, $repay_freeze_money, array('expire'=>10));
	}
	
	private function getBuyFreezeMoneyCache($uid){
// 		$key = "buy_freeze_money_{$uid}";
// 		return cache($key);
		return '';
	}
	
	private function setBuyFreezeMoneyCache($uid, $buy_freeze_money){
// 		$key = "buy_freeze_money_{$uid}";
// 		$buy_freeze_money = $buy_freeze_money;
// 		cache($key, $buy_freeze_money, array('expire'=>10));
	}
	
	private function getCashFreezeMoneyCache($uid){
// 		$key = "cash_freeze_money_{$uid}";
// 		return cache($key);
		return '';
	}
	
	private function setCashFreezeMoneyCache($uid, $cash_freeze_money){
// 		$key = "cash_freeze_money_{$uid}";
// 		$cash_freeze_money = $cash_freeze_money;
// 		cache($key, $cash_freeze_money, array('expire'=>10));
	}

    private function rewardMoneyCalculate($user_account, $money) {
        $user_account_data['amountx'] = $user_account['amountx'] + $money;
        if ($user_account['reward_money'] >= $money) {
            $user_account_data['reward_money'] = $user_account['reward_money'] - $money;
            $changeParams = array(
                'reward_money' => array(
                    'from' => $user_account['reward_money'],
                    'to' => $user_account_data['reward_money'],
                ),
                'amountx' => array(
                    'from' => $user_account['amountx'],
                    'to' => $user_account_data['amountx'],
                ),
            );
        } elseif($user_account['reward_money'] > 0) {
            $user_account_data['reward_money'] = 0;
            $user_account_data['amount'] = $user_account['amount'] - ($money - $user_account['reward_money']);
            $changeParams = array(
                'reward_money' => array(
                    'from' => $user_account['reward_money'],
                    'to' => $user_account_data['reward_money'],
                ),
                'amount' => array(
                    'from' => $user_account['amount'],
                    'to' => $user_account_data['amount'],
                ),
                'amountx' => array(
                    'from' => $user_account['amountx'],
                    'to' => $user_account_data['amountx'],
                ),
            );
        } else {
            $user_account_data['amount'] = $user_account['amount'] - $money;
            $changeParams = array(
                'amount' => array(
                    'from' => $user_account['amount'],
                    'to' => $user_account_data['amount'],
                ),
                'amountx' => array(
                    'from' => $user_account['amountx'],
                    'to' => $user_account_data['amountx'],
                ),
            );
        }
        $data = [
            'user_account_data' => $user_account_data,
            'changeParams' => $changeParams,
        ];
        return $data;
    }

    public function setAdesc($yunying_adesc)
    {
        $this->yunying_adesc = $yunying_adesc;
        return $this;
    }


    public function setDescEqRemark($v=true) {
        $this->desc_eq_remark = $v;
        return $this;
    }

    public function getTodatInSum($uid){
        $uid = intval($uid);
        $date = date("Y-m-d");
        $start = strtotime($date." 00:00:00");
        $end = strtotime($date." 23:59:59");
        $where['ctime'] = array(array("GT", $start),array("LT", $end),'and');
        $where['uid']  = $uid;
        $where['status']  = 2;
        $where['in_or_out'] = 1;
        $where['channel'] = PaymentService::TYPE_ZHESHANG;
        $res = M('out_ticket')->where($where)->sum('amount');
//        echo M('out_ticket')->_sql();
        return $res;
    }

    public function zsAccountTransferApply($uid, $move_type = 1, $amount, $fund_account){
            $user_account = $this->where(array('uid' => $uid))->find();
            if ($user_account === false) {
                throw_exception("获取用户账户数据失败");
            }
            $account_move['order_no'] = $this->createMoveNo();
            $account_move['uid'] = $uid;
            $account_move['amount'] = $user_account['amount'];
            $account_move['reward_money'] = $user_account['reward_money'];
            $account_move['fund_account'] = $fund_account;
            $account_move['zs_amount'] = $user_account['zs_amount'];
            $account_move['zs_reward_money'] = $user_account['zs_reward_money'];
            $account_move['move_amount'] = $amount;
            $account_move['move_type'] = $move_type;
            $account_move['status'] = 1;
            $account_move['ctime'] = $account_move['rtime'] = $account_move['mtime'] = time();
            $res = M('user_account_move')->add( $account_move );
            if( !$res ) throw_exception( '写入数据失败' );
            return array('move_id'=>$res, 'order_no'=>$account_move['order_no']);
    }

    public function createMoveNo(){
        Import("libs.Counter.MultiCounter", ADDON_PATH);
        $no_id = MultiCounter::init(Counter::GROUP_DEFAULT, Counter::LIFETIME_TODAY);
        $key = 'ORDER'.date("Ymd");
        $the_id = $no_id->incr($key);
        if($the_id) {
            $no = $key . str_pad($the_id,9,"0",STR_PAD_LEFT);
            $where['order_no'] = $no;
            $has = M("user_account_move")->where($where)->find();
            if($has){
                if(C("APP_STATUS") != 'product'){
                    $maxTicket = M("user_account_move")->where(array("order_no" => array('LIKE', $key . '%')))->order('order_no desc')->find();
                    // 计数器不能自增的时候，这样只读取当天的最大数更保险一点
                }else{
                    $maxTicket = M("user_account_move")->order('id desc')->find();
                }
                $the_id = substr($maxTicket['order_no'], strlen($maxTicket['order_no'])-9, 9);
                $the_id = (int)$the_id + 1;
                $no_id->set($key, $the_id);
                $no = $key . str_pad($the_id,9,"0",STR_PAD_LEFT);
                return $no;
            } else {
                return $no;
            }
        } else {
            if(C("APP_STATUS") != 'product'){
                $maxTicket = M("user_account_move")->where(array("order_no" => array('LIKE', $key . '%')))->order('order_no desc')->find();
                // 计数器不能自增的时候，这样只读取当天的最大数更保险一点
            }else{
                $maxTicket = M("user_account_move")->order('id desc')->find();
            }

            $the_id = substr($maxTicket['order_no'], strlen($maxTicket['order_no'])-9, 9);
            $the_id = (int)$the_id + 1;
            $no = $key . str_pad($the_id,9,"0",STR_PAD_LEFT);
            return $no;
        }
    }
    /*
     * 余额转移账户出款
     */
    public function realAccountOut($user_data, $amount, $objId){
            $account_uid = service("Public/BonusAccount")
                ->getFromUidByAccountType( BonusAccountService::ACCOUNT_TYPE_ZS_TRANS );
            $user_account = $this->where(array('uid' => $account_uid))->find();
            if ($user_account === false) {
                throw_exception("获取专用资金账户数据失败");
            }
            $user_account_modify['amount'] = array('exp', 'amount - ' . $amount);
            $user_account_modify['version'] = array('exp', 'version+1');
            $res = M('user_account')->where(array('uid' => $account_uid,'version' => $user_account['version']))->save($user_account_modify);
            if( !$res ) throw_exception( '保存数据失败' );
            D('Payment/Ticket');
            $user_account = $user_data;
            $paymentParam['amount'] = array('from'=>$user_account['amount'], 'to'=>$user_account['amount'] - $amount);
            //资金账户出账记录
            $this->addRecord(
                $account_uid,
                self::OBJ_TYPE_ZS_MOVING,
                $objId,
                TicketModel::OUTGOING_FLAG,
                $amount,
                $paymentParam,
                array('record_id'),
                '存管存量账户转移',
                $user_account['uid'],
                false,
                '存管存量账户转移出账记录'
            );
            //用户账户出账记录
             $paymentParam['zs_moving_money'] = array('from'=>$user_account['zs_moving_money'], 'to'=>$user_account['zs_moving_money'] + $amount);
             $this->addRecord(
                $user_account['uid'],
                self::OBJ_TYPE_ZS_MOVING,
                $objId,
                TicketModel::OUTGOING_FLAG,
                $amount,
                $paymentParam,
                array('record_id'),
                '存管存量账户转移',
                '',
                false,
                '存管存量账户转移出账记录'
            );
            
    }

    public function checkRealAccount( $amount ){
        $account_uid = service("Public/BonusAccount")
            ->getFromUidByAccountType( BonusAccountService::ACCOUNT_TYPE_ZS_TRANS );
        $user_account = $this->where(array('uid' => $account_uid))->find();
        if ($user_account === false) {
            throw_exception("账户不存在");
        }
        
        if( $amount > $user_account['amount'] ){
            throw_exception("余额转移专用账户资金不够,需充值");
        }
        return true;
    }
    
    //账户信息
    public function accountInfo( $uid ){
        $account = $this->find( $uid );
        $arr['total_amount'] = humanMoney($account['amount']
        + $account['zs_amount']
        + $account['zs_reward_money']
        + $account['reward_money'], 2, false);//总可用余额
        $arr['xhh_amount'] = humanMoney($account['amount'] + $account['reward_money'], 2, false);//鑫合汇虚拟户可用余额
        $arr['zs_amount'] = humanMoney($account['zs_amount'] + $account['zs_reward_money'], 2, false);//浙商虚拟户可用余额
        $arr['zs_amount_num'] = $account['zs_amount'] + $account['zs_reward_money'];//浙商虚拟户可用余额
        return $arr;
    }
}

<?php
class UserAction extends BaseAction
{
    const LOGIN_REFERER_URL = "LOGIN_REFERER_URL";//登录返回urlkey
    const RESET_PWD_TOKEN = "RESET_PWD_TOKEN";//忘记密码key
    const PAGESIZE = 10;
    const RECOMMEND_TOKEN_KEY = "RECOMMEND_TOKEN_KEY";//推荐注册session key
    const RECOMMEND_COOKIE_TOKEN_KEY = "RECOMMEND_COOKIE_TOKEN_KEY";
    const PRIZE_INFO_CACHE = "PRIZE_INFO_CACHE";//中奖信息

    const LAOK_KEY = 'xinhehui20151103lkgamezxcvb';
    const LAOK_TYPE = 0;//注册类型为0  投资类型为1
    const LAOK_SOURCE_STR = 'laok';//老K来源 source 标志
    const LAOK_URL = 'http://www.lkgame.com/activities/xinhehui/index.aspx';//老K捕鱼达人游戏地址

    const OPENING_ACCOUNT = 1;
    const OPENED_ACCOUNT = 2;

    private $exec_times = array(); //临时

    protected $fraudmetrix_token = null;

    public function _initialize() {
        parent::_initialize();
        $this->assign('basicUserInfo', service('Account/Account')->basicUserInfo($this->loginedUserInfo));
    }

    /**
     * 生成同盾token
     * @return null|string
     */
    public function getFraudmetrixToken() {
        if ($this->fraudmetrix_token === null) {
            $this->fraudmetrix_token = pwd_md5(session_id());
        }
        return $this->fraudmetrix_token;
    }

    /**
     * 用户信息查看
     */
    //2015.1.22 机构注册资料填写
    function index_corp() {
        $userInfo = $this->loginedUserInfo;

        $step1 = $step2 = $step3 = $step4 = true;//默认为已填写
        $obj = service("Account/Account");
        $getinfo = $obj->getCorpinfo();
        $arr = explode('|', $getinfo['step']);
        $exec_times[__LINE__] = microtime(true) - $GLOBALS['_beginTime'];
        if (!in_array(1, $arr)) {
            $step1 = false;//未填写机构信息
        }

        if (!in_array(2, $arr)) {
            $step2 = false;//未填写法人信息
        }
        if (2 == $getinfo['proposer'] && !in_array(3, $arr)) {
            $step3 = false;//未填写代理人信息
        }

        if (1 == $getinfo['status'] && $step1 && $step2 && $step3 && !in_array(4, $arr)) {

            $step4 = false;//echo '未提交，请提交';
        }
        if (C('ACCOUNT_VIEW_NEW')) {

            $this->indexNew();

        } else {
            $this->guarantor_id = $userInfo['guarantor_id'];
            $this->usc_token = $userInfo['usc_token'];

            $obj = service("Account/Account");
            $uid = $userInfo['uid'];

            $userInfo = $obj->getByUid($uid);
            $exec_times[__LINE__] = microtime(true) - $GLOBALS['_beginTime'];
            $ava = $obj->getAvatar($uid);
            $sumary = $obj->getSummaryById($uid);
            $exec_times[__LINE__] = microtime(true) - $GLOBALS['_beginTime'];
            //待收收益
            $sumary['will_profit_view'] = humanMoney($sumary['will_profit'], 2, false);
            //待收本金
            $sumary['will_principal_view'] = humanMoney($sumary['will_principal'], 2, false);

            $memBankCardInfo = D('Account/UserAccount')->getUserBankCardsInfo($uid);
            $exec_times[__LINE__] = microtime(true) - $GLOBALS['_beginTime'];
            if ($defaultCardId = $memBankCardInfo['default']['list'][0]['id']) {
                if (array_key_exists($defaultCardId, $memBankCardInfo['autoable']['list'])) {
                    $defaultCardId = false;
                }
            } else {
                $fiestBankCard = $memBankCardInfo['all']['list'][0];
                $fiestBankCardFull = D('Account/UserAccount')->isBankCardInfoFull($uid, array($fiestBankCard['bank'], $fiestBankCard['sub_bank_id'], $fiestBankCard['bank_province'], $fiestBankCard['bank_city']));
                if (!$fiestBankCardFull) {
                    $defaultCardId = $memBankCardInfo['all']['list'][0]['id'];
                }
            }
            $exec_times[__LINE__] = microtime(true) - $GLOBALS['_beginTime'];
            //资金信息
            $serviceObj = service("Payment/PayAccount");
            $account = $serviceObj->getBaseInfo($uid);
            $exec_times[__LINE__] = microtime(true) - $GLOBALS['_beginTime'];
            // 摇一摇
            // $srvUserBonusUse = service('Account/UserBonusUse');
            // $srvUserBonusUse->setBonusType(UserBonusModel::BONUS_FROM_TYPE_YAOYIYAO, UserBonusModel::BONUS_FROM_ID_YAOYIYAO);
            // $amount_yao1yao = $srvUserBonusUse->getAmount($uid, TRUE);
            //$account['amount_yao1yao'] = humanMoney($amount_yao1yao, 2, FALSE);
            $haobao_user = D('Account/UserBonus');
            $amount_yao1yao = $haobao_user->getMyBonus($uid);
            $account['amount_yao1yao'] = humanMoney($amount_yao1yao, 2, FALSE);

            $account['top_amount_view'] = humanMoney(($account['amount'] + $account['reward_money'] + $account['invest_reward_money'] + $account['coupon_money'] + $account['buy_freeze_money'] + $account['cash_freeze_money'] + $amount_yao1yao), 2, false);
            $account['amount_view'] = humanMoney($account['amount'], 2, false);
            $account['amount_use_view'] = humanMoney(($account['amount'] + $account['reward_money'] + $account['invest_reward_money'] + $account['coupon_money'] + $amount_yao1yao), 2, false);
            $account['freeze_money'] = humanMoney(($account['buy_freeze_money'] + $account['cash_freeze_money']), 2, false);
            //净收益 = 总收益-已还变现利息
            $account['profit_view'] = humanMoney(($account['profit'] - $sumary['fastcash_repay_profit']), 2, false);
            $account['buy_freeze_money_view'] = humanMoney($account['buy_freeze_money'], 2, false);
            $account['cash_freeze_money_view'] = humanMoney($account['cash_freeze_money'], 2, false);

            $account['reward_total_view'] = humanMoney(($account['reward_money'] + $account['invest_reward_money'] + $account['coupon_money']), 2, false);
            $account['reward_money_view'] = humanMoney($account['reward_money'], 2, false);
//         $account['invest_reward_money_view'] = humanMoney($account['invest_reward_money'],2,false);
            $account['invest_reward_money_view'] = humanMoney(($account['invest_reward_money'] + $account['coupon_money']), 2, false);

            $account['repay_freeze_view'] = humanMoney($account['repay_freeze_money'], 2, false);
            $account['free_money_view'] = humanMoney($account['free_money'], 2, false);

//        var_dump($sumary);exit;

            $totalAccount = $account['amount'] + $account['buy_freeze_money'] + $account['cash_freeze_money'] + $account['reward_money'] + $account['invest_reward_money'] + $sumary['will_profit'] + $sumary['will_principal'] + $account['coupon_money'] - $sumary['fastcash_principal'] - $sumary['fastcash_profit'] + $amount_yao1yao;

            //预估资产总额 = 原资产总额-待还变现本金-待还变现利息
            $totalAccountView = humanMoney($totalAccount, 2, false);
            $this->assign("totalAccountView", $totalAccountView);

            //待还变现本金
            $sumary['fastcash_principal'] = humanMoney($sumary['fastcash_principal'], 2, false);
            //待还变现利息
            $sumary['fastcash_profit'] = humanMoney($sumary['fastcash_profit'], 2, false);


            $totalInvest = $sumary['investing_prj_money'] + $sumary['willrepay_prj_money'] + $sumary['repayed_prj_money'] + $sumary['repayIn_money'];
            $totalInvestView = humanMoney($totalInvest, 2, false);

            $totalInvestCount = (int)($sumary['investing_prj_count'] + $sumary['willrepay_prj_count'] + $sumary['repayed_prj_count'] + $sumary['repayIn_count']);


            $this->assign("totalInvestView", $totalInvestView);
            $this->assign("totalInvestCount", $totalInvestCount);
            $this->assign("sumary", $sumary);
            $this->assign("user", $userInfo);
            $this->assign("ava", $ava);
            $this->assign("account", $account);

            //安全级别
            $safeLevel = service("Account/Account")->computeSafeLevel($uid);
            $this->assign("safeLevel", $safeLevel[0]);
            $this->assign("verifyInfo", $safeLevel[1]);
            $this->assign("verifyLevel", $safeLevel[2]);
            $this->assign("defaultCardId", $defaultCardId);

//         $user_account = M('user_account')->find($this->loginedUserInfo['uid']);
//         $cashoutTimes = service("Payment/PayAccount")->getCashoutTimes($user_account);
//         $this->assign("cashoutTimes",$cashoutTimes);
            $exec_times[__LINE__] = microtime(true) - $GLOBALS['_beginTime'];
            //下周还款数据
            $this->nextWeekData = service("Account/Repay")->getNextWeekData();
            $exec_times[__LINE__] = microtime(true) - $GLOBALS['_beginTime'];
            $this->nextRepay = service("Account/Repay")->getNewNextRepay($uid, '', 1);
            $repayDate = isset($this->nextRepay['repay_date']) ? $this->nextRepay['repay_date'] : '';
            $this->isNull = service("Account/Repay")->isNullRepay($uid, $repayDate);
            $this->isPreNull = service("Account/Repay")->isNullRepayPre($uid, $repayDate);
            $exec_times[__LINE__] = microtime(true) - $GLOBALS['_beginTime'];
            $this->todayHasRepay = service("Account/Repay")->todayHasRepay($uid);
            $exec_times[__LINE__] = microtime(true) - $GLOBALS['_beginTime'];
            // 是否设置了安全保障问题
            $hash_sqa = $userInfo['sqa_key'] && $userInfo['sqa_value'];
            $this->assign('hash_sqa', $hash_sqa);

            $period = C('PRIZE_PERIOD_SHJB');
            $start = strtotime($period['start']);
            $end = strtotime($period['end']);
            $date = date('Y-m-d');
            if (strtotime($date) < $start || strtotime($date) > $end) {
                $lottery_num = 0;
            } else {
                import_app("Index.Service.ActivityPoolService");
                $hd1_user_lottery = M('hd1_user_lottery')->where(array("uid" => $this->loginedUserInfo['uid'], "activity_name" => ActivityPoolService::SHJB_KEY))->find();
                $lottery_num = $hd1_user_lottery['lottery_num'];
            }
            $exec_times[__LINE__] = microtime(true) - $GLOBALS['_beginTime'];
            $this->assign('cupUrl', U("Index/Act/worldCup"));
            $this->assign('lottery_num', $lottery_num);
        }
        $this->assign("step1", $step1);
        $this->assign("step2", $step2);
        $this->assign("step3", $step3);
        $this->assign("step4", $step4);
        $this->assign("getorginfo", $getinfo);

        if (C('ACCOUNT_VIEW_NEW')) {
            $this->display("indexNew");
        } else {
            $this->display("indexCorp");
        }
    }

    function index() {
        $this->exec_times[__LINE__] = microtime(true) - $GLOBALS['_beginTime'];

        $userInfo = $this->loginedUserInfo;

        // 去除免费提现额度提醒, TODO: 2015-09-30之后可删除
        $show_free_notice_start = C('SHOW_FREE_NOTICE_START') ? C('SHOW_FREE_NOTICE_START') : '2015-08-12 00:00:00';
        $show_free_notice_start = strtotime($show_free_notice_start);
        $show_free_notice_end = strtotime('2015-10-01 00:00:00');
        $now = time();
        if ($now < $show_free_notice_end && $userInfo['ctime'] < $show_free_notice_start) {
            $redis = \Addons\Libs\Cache\Redis::getInstance('activity');
            $key = 'SHOW_FREE_NOTICE_' . $userInfo['uid'];
            if (!$redis->get($key)) {
                $redis->setex($key, $show_free_notice_end - $show_free_notice_start, 1);
                $this->assign('is_show_free_notice', 1);
            }
        }

        $this->exec_times[__LINE__] = microtime(true) - $GLOBALS['_beginTime'];

        if ($userInfo['uid_type'] == 2) {
            $this->index_corp();
            if (microtime(true) - $GLOBALS['_beginTime'] > 5) {
                \Addons\Libs\Log\Logger::info('index_corp超过5秒', "Account/User/index", $this->exec_times);
            }
            return;
        }

        if (C('ACCOUNT_VIEW_NEW')) {
            $this->indexNew();
            if (microtime(true) - $GLOBALS['_beginTime'] > 5) {
                \Addons\Libs\Log\Logger::info('indexNew超过5秒', "Account/User/index", $this->exec_times);
            }
            return;
        }

        // $this->indexNew();
        // return;

        $this->guarantor_id = $userInfo['guarantor_id'];
        $this->usc_token = $userInfo['usc_token'];

        $obj = service("Account/Account");
        $uid = $userInfo['uid'];

        $userInfo = $obj->getByUid($uid);
        $this->exec_times[__LINE__] = microtime(true) - $GLOBALS['_beginTime'];
        $ava = $obj->getAvatar($uid);
        $this->exec_times[__LINE__] = microtime(true) - $GLOBALS['_beginTime'];
        $sumary = $obj->getSummaryById($uid);
        $this->exec_times[__LINE__] = microtime(true) - $GLOBALS['_beginTime'];

        //待收收益
        $sumary['will_profit_view'] = humanMoney($sumary['will_profit'], 2, false);
        //待收本金
        $sumary['will_principal_view'] = humanMoney($sumary['will_principal'], 2, false);

        $memBankCardInfo = D('Account/UserAccount')->getUserBankCardsInfo($uid);

        if ($defaultCardId = $memBankCardInfo['default']['list'][0]['id']) {
            if (array_key_exists($defaultCardId, $memBankCardInfo['autoable']['list'])) {
                $defaultCardId = false;
            }
        } else {
            $fiestBankCard = $memBankCardInfo['all']['list'][0];
            $fiestBankCardFull = D('Account/UserAccount')->isBankCardInfoFull($uid, array($fiestBankCard['bank'], $fiestBankCard['sub_bank_id'], $fiestBankCard['bank_province'], $fiestBankCard['bank_city']));
            if (!$fiestBankCardFull) {
                $defaultCardId = $memBankCardInfo['all']['list'][0]['id'];
            }
        }
        $this->exec_times[__LINE__] = microtime(true) - $GLOBALS['_beginTime'];
        //资金信息
        $serviceObj = service("Payment/PayAccount");
        $account = $serviceObj->getBaseInfo($uid);
        $this->exec_times[__LINE__] = microtime(true) - $GLOBALS['_beginTime'];
        // 摇一摇
        // $srvUserBonusUse = service('Account/UserBonusUse');
        // $srvUserBonusUse->setBonusType(UserBonusModel::BONUS_FROM_TYPE_YAOYIYAO, UserBonusModel::BONUS_FROM_ID_YAOYIYAO);
        // $amount_yao1yao = $srvUserBonusUse->getAmount($uid, TRUE);
        $haobao_user = D('Account/UserBonus');
        $amount_yao1yao = $haobao_user->getMyBonus($uid);
        $this->exec_times[__LINE__] = microtime(true) - $GLOBALS['_beginTime'];
        $account['amount_yao1yao'] = humanMoney($amount_yao1yao, 2, FALSE);
        $account['top_amount_view'] = humanMoney(($account['amount'] + $account['reward_money'] + $account['invest_reward_money'] + $account['coupon_money'] + $account['buy_freeze_money'] + $account['cash_freeze_money'] + $amount_yao1yao), 2, false);
        $account['amount_view'] = humanMoney($account['amount'], 2, false);
        $account['amount_use_view'] = humanMoney(($account['amount'] + $account['reward_money'] + $account['invest_reward_money'] + $account['coupon_money'] + $amount_yao1yao), 2, false);
        $account['freeze_money'] = humanMoney(($account['buy_freeze_money'] + $account['cash_freeze_money']), 2, false);
        //净收益 = 总收益-已还变现利息
        $account['profit_view'] = humanMoney(($account['profit'] - $sumary['fastcash_repay_profit']), 2, false);
        $account['buy_freeze_money_view'] = humanMoney($account['buy_freeze_money'], 2, false);
        $account['cash_freeze_money_view'] = humanMoney($account['cash_freeze_money'], 2, false);

        $account['reward_total_view'] = humanMoney(($account['reward_money'] + $account['invest_reward_money'] + $account['coupon_money']), 2, false);
        $account['reward_money_view'] = humanMoney($account['reward_money'], 2, false);
//         $account['invest_reward_money_view'] = humanMoney($account['invest_reward_money'],2,false);
        $account['invest_reward_money_view'] = humanMoney(($account['invest_reward_money'] + $account['coupon_money']), 2, false);

        $account['repay_freeze_view'] = humanMoney($account['repay_freeze_money'], 2, false);
        $account['free_money_view'] = humanMoney($account['free_money'], 2, false);

//        var_dump($sumary);exit;

        $totalAccount = $account['amount'] + $account['buy_freeze_money'] + $account['cash_freeze_money'] + $account['reward_money'] + $account['invest_reward_money'] + $sumary['will_profit'] + $sumary['will_principal'] + $account['coupon_money'] - $sumary['fastcash_principal'] - $sumary['fastcash_profit'] + $amount_yao1yao;

        //预估资产总额 = 原资产总额-待还变现本金-待还变现利息
        $totalAccountView = humanMoney($totalAccount, 2, false);
        $this->assign("totalAccountView", $totalAccountView);

        //待还变现本金
        $sumary['fastcash_principal'] = humanMoney($sumary['fastcash_principal'], 2, false);
        //待还变现利息
        $sumary['fastcash_profit'] = humanMoney($sumary['fastcash_profit'], 2, false);


        $totalInvest = $sumary['investing_prj_money'] + $sumary['willrepay_prj_money'] + $sumary['repayed_prj_money'] + $sumary['repayIn_money'];
        $totalInvestView = humanMoney($totalInvest, 2, false);

        $totalInvestCount = (int)($sumary['investing_prj_count'] + $sumary['willrepay_prj_count'] + $sumary['repayed_prj_count'] + $sumary['repayIn_count']);


        $this->assign("totalInvestView", $totalInvestView);
        $this->assign("totalInvestCount", $totalInvestCount);
        $this->assign("sumary", $sumary);
        $this->assign("user", $userInfo);
        $this->assign("ava", $ava);
        $this->assign("account", $account);

        //安全级别
        $safeLevel = service("Account/Account")->computeSafeLevel($uid);
        $this->exec_times[__LINE__] = microtime(true) - $GLOBALS['_beginTime'];
        $this->assign("safeLevel", $safeLevel[0]);
        $this->assign("verifyInfo", $safeLevel[1]);
        $this->assign("verifyLevel", $safeLevel[2]);
        $this->assign("defaultCardId", $defaultCardId);

//         $user_account = M('user_account')->find($this->loginedUserInfo['uid']);
//         $cashoutTimes = service("Payment/PayAccount")->getCashoutTimes($user_account);
//         $this->assign("cashoutTimes",$cashoutTimes);

        //下周还款数据
        $this->nextWeekData = service("Account/Repay")->getNextWeekData();
        $this->exec_times[__LINE__] = microtime(true) - $GLOBALS['_beginTime'];
        $this->nextRepay = service("Account/Repay")->getNewNextRepay($uid, '', 1);
        $this->exec_times[__LINE__] = microtime(true) - $GLOBALS['_beginTime'];
        $repayDate = isset($this->nextRepay['repay_date']) ? $this->nextRepay['repay_date'] : '';
        $this->isNull = service("Account/Repay")->isNullRepay($uid, $repayDate);
        $this->exec_times[__LINE__] = microtime(true) - $GLOBALS['_beginTime'];
        $this->isPreNull = service("Account/Repay")->isNullRepayPre($uid, $repayDate);
        $this->exec_times[__LINE__] = microtime(true) - $GLOBALS['_beginTime'];

        $this->todayHasRepay = service("Account/Repay")->todayHasRepay($uid);
        $this->exec_times[__LINE__] = microtime(true) - $GLOBALS['_beginTime'];

        // 是否设置了安全保障问题
        $hash_sqa = $userInfo['sqa_key'] && $userInfo['sqa_value'];
        $this->assign('hash_sqa', $hash_sqa);

        $period = C('PRIZE_PERIOD_SHJB');
        $start = strtotime($period['start']);
        $end = strtotime($period['end']);
        $date = date('Y-m-d');
        if (strtotime($date) < $start || strtotime($date) > $end) {
            $lottery_num = 0;
        } else {
            import_app("Index.Service.ActivityPoolService");
            $hd1_user_lottery = M('hd1_user_lottery')->where(array("uid" => $this->loginedUserInfo['uid'], "activity_name" => ActivityPoolService::SHJB_KEY))->find();
            $lottery_num = $hd1_user_lottery['lottery_num'];

        }
        $this->exec_times[__LINE__] = microtime(true) - $GLOBALS['_beginTime'];
        $this->assign('cupUrl', U("Index/Act/worldCup"));
        $this->assign('lottery_num', $lottery_num);

        if (microtime(true) - $GLOBALS['_beginTime'] > 5) {
            \Addons\Libs\Log\Logger::info('account超过5秒', "Account/User/index", $this->exec_times);
        }

        $this->display();
    }

    /**
     * 上传头像 弹出页面
     * @return [type] [description]
     */
    function uploadPhoto() {
        $this->display();
    }

    /**
     * 设置用户名 弹出页面
     * @return [type] [description]
     */
    function updateUname() {
        $this->display();
    }

    /**
     * 账户信息
     */
    function account() {
        C('TOKEN_ON');
        $userInfo = $this->loginedUserInfo;
        $uid = $userInfo['uid'];
        $obj = service("Account/Account");
        $userInfo = $obj->getByUid($uid);
        $ava = $obj->getAvatar($uid);
//         echo $userInfo['mobile'];
        $mobile = marskName($userInfo['mobile'], 3, 4);

        $emailArr = explode('@', $userInfo['email']);
        $emailFirst = substr($emailArr[0], 0, 2) . str_repeat("*", strlen($emailArr[0]) - 2);

        $email = $emailFirst . "@" . $emailArr[1];
        $this->assign("email", $email);
        $this->assign("mobile", $mobile);
        $this->assign("user", $userInfo);
        $this->assign("ava", $ava);

        //安全级别
        $safeLevel = service("Account/Account")->computeSafeLevel($uid);
        $this->assign("safeLevel", $safeLevel[0]);
        $this->assign("verifyInfo", $safeLevel[1]);
        $this->assign("verifyLevel", $safeLevel[2]);

        // 是否设置了安全保障问题
        $hash_sqa = $userInfo['sqa_key'] && $userInfo['sqa_value'];
        $this->assign('hash_sqa', $hash_sqa);

        //2015.1.19 机构注册,审核,认证
        $obj = service("Account/Account");
        $getinfo = $obj->getCorpinfo();

        $user_account = M('user_account')->find((int)$userInfo['uid']);

        $this->assign('getorginfo', $getinfo);
        $this->assign("is_paypwd_edit", $userInfo['is_paypwd_edit'] || $user_account['pay_password']);//20160309 是否设置支付密码

        //2016322 start 设置过密码密码的老用户,进入用户中心>账户设置后,应弹窗引导其修改支付密码
        if ($userInfo['ctime'] < strtotime(C('Q4_GO_LINE'))) {
            $key = 'Q4_GO_LINE_' . $userInfo['uid'];
            if (S($key)) {
                $this->assign('is_popup', 0);
            } else {
                S($key, 1);
                $this->assign('is_popup', 1);
            }
        }//end
        //是否开通浙商存管
        $zsOpen = $userInfo['zs_status'] == '2' ? 1 : 0;
        if($zsOpen){
            //浙商的存管账号
            $this->assign('zs_account_id',$userInfo['zs_account_id']);
            //银行预留手机号
            $this->assign('zs_bank_mobile',moblie_view($userInfo['zs_bank_mobile']));
            //浙商绑定的银行卡信息
            $result = M('fund_account')->where(array('uid'=>$uid,'zs_bankck'=>1))->find();
            //银行卡号
            $this->assign('zs_bind_bank_card',$result['zs_bind_bank_card']);
            //银行标示
            $this->assign('bank',$result['bank']);
            //银行名称
            $this->assign('bank_name',$result['bank_name']);
            //打星号的实名显示
            $this->assign('zs_real_name', '*' .mb_substr($userInfo['real_name'], 1, strlen($userInfo['real_name']), 'utf-8') );
            //银行卡变更状态:1 表示：变更中; 0 表示：最新的
            $redis = new RedisSimply('bankCardStatus');
            $key = $userInfo['uid'].'_'.$result['zs_bind_bank_card'];
            $bank_status = $redis->get($key) ? 1 : 0;
            $this->assign('bank_status',$bank_status);
        }
        $if_white_name = service('Account/Account')->checkNameListUser($userInfo['uid']) ? 1 : 0;
        $this->assign('if_white_name',$if_white_name);
        $this->assign('is_zs_open',$zsOpen);

        $this->display("account");
    }

    /**
     * 绑定的银行
     */
    function getMyBindBanks() {
        $userInfo = $this->loginedUserInfo;
        $uid = $userInfo['uid'];
        $bindBank = service("Account/Account")->getBindBanks($uid);
        foreach ($bindBank as &$v) {
            $ret = service("Account/AccountBank")->bindCardType($uid, $v['acount_name']);
            $v['bind_status'] = $ret['bind_status'];
            $v['tx_status'] = $ret['tx_status'];
//            $v['bank'] = strtolower($v['bank']);
        }
        $count = M('fund_account')->field('count(1) as count')->where(array("uid" => $this->loginedUserInfo['uid'],"zs_bankck"=>0))->find();
        $count = $count['count'];
        $this->assign("count", $count);
        $this->assign("bindBank", $bindBank);
        $this->display();
    }

    //修改手机号码显示1
    function editMobileView() {
        $uid = $this->loginedUserInfo['uid'];
        $userInfo = service("Account/Account")->getByUid($uid);
        $this->assign('mobile', marskName($userInfo['mobile'], 3, 4));
        $this->assign('raw_mobile', $userInfo['mobile']);
        $this->assign('uname', $userInfo['uname']);
        $this->display("editMobileView");
    }

    //修改手机号码
    function editMobileView2() {

        $oldMobile = $this->_request("oldMobile");
        $code = $this->_request("code");
        if (!service("Account/Validate")->validateCheckOldMobileCode($oldMobile, $code)) {
            $this->error(MyError::lastError(), U("Account/User/editMobileView"));
//            ajaxReturn(0,MyError::lastError(),0);
        }

        $uid = $this->loginedUserInfo['uid'];
        $userInfo = service("Account/Account")->getByUid($uid);
        if ($userInfo['mobile'] != $oldMobile) {
            ajaxReturn(0, "手机号码不匹配!", 0);
        }
        BaseModel::newSession('editMobile', 1);
        $this->display("editMobileView2");
    }

    //重新绑定
    function resetEditMobile() {
        if (!BaseModel::newSession('editMobile')) {
            $this->error("非法访问!", U("Account/User/editMobileView"));
        }

        $mobile = $this->_request('mobile');
        $code = $this->_request('code');
        // $uid_type =  $this->_request('uid_type');
        //if(!$uid_type) $uid_type = 1;

        if (!$mobile || !$code) {
            $this->error("异常,参数错误!", U("Account/User/editMobileView"));
        }

        if (!service("Account/Validate")->validateEditMobileCode($mobile, $code)) {
            ajaxReturn(0, MyError::lastError(), 0);
        }

        $userInfo = $this->loginedUserInfo;
        $uid = $userInfo['uid'];
        $old_mobile = M('user')->where(array('uid' => $uid))->getField('mobile');
        if ($old_mobile == $mobile) {
            ajaxReturn(0, "旧手机号码与新手机号码不能相同!", 0);
        }
        //检查是否重复
        //$otherUser = service("Account/Account")->getUserByMobile($mobile, $uid_type);
        $otherUser = service("Account/Account")->getUserByMobile($mobile, $userInfo['uid_type'], $userInfo['is_invest_finance']);
        if ($otherUser) {
            if ($otherUser['uid'] != $uid) {
                ajaxReturn(0, "手机号码已被占用,请重新输入!", 0);
//                $this->error("手机号码已被占用,请重新输入!",U("Account/User/editMobileView"));
            }
        }

        service("Account/Account")->updateMobile($uid, $mobile);
        service("Account/Account")->resetLogin($uid);
        if (MyError::hasError()) {
            $this->error(MyError::lastError(), U("Account/User/editMobileView"));
        }
        ajaxReturn(1, '修改成功', 1);
//        $this->success('修改成功',U("Account/User/account"));
    }


    function sendResetBindMobile($mobile, $uid_type = 1, $is_invest_finance = 1) {
        if (!BaseModel::newSession('editMobile')) {
            ajaxReturn('', '非法访问,请按正规流程绑定手机号码!', 0);
        }

        if (!checkMobile($mobile)) {
            ajaxReturn(0, "手机号码格式错误!", 0);
        }

        $userInfo = $this->loginedUserInfo;
        $uid = $userInfo['uid'];
        //检查是否重复
        //$otherUser = service("Account/Account")->getUserByMobile($mobile, $uid_type);
        $otherUser = service("Account/Account")->getUserByMobile($mobile, $userInfo['uid_type'], $userInfo['is_invest_finance']);
        if ($otherUser) {
            if ($otherUser['uid'] != $uid) {
                ajaxReturn(0, "手机号码已被占用,请重新输入!", 0);
            }
        }

        $service = service("Account/Validate");
        $service->sendEditMobileCode($mobile);
        if (MyError::hasError()) {
            ajaxReturn('', MyError::lastError(), 0);
        } else {
            ajaxReturn('', '动态码发送成功!');
        }
    }

    /**
     * 修改手机号码
     */
    function editMobile($oldmobile, $mobile, $code, $uid_type = 1, $is_invest_finance = 1) {
        if (!$oldmobile || !$mobile || !$code) {
            ajaxReturn(0, "异常,参数错误!", 0);
        }

        if ($oldmobile == $mobile) {
            ajaxReturn(0, "旧手机号码与新手机号码不能相同!", 0);
        }

        $userInfo = $this->loginedUserInfo;
        if ($oldmobile != $userInfo['mobile']) {
            ajaxReturn(0, "旧手机号码输入错误!", 0);
        }

        if (!checkMobile($mobile)) {
            ajaxReturn(0, "手机号码格式错误!", 0);
        }

        if (!service("Account/Validate")->validateEditMobileCode($mobile, $code)) {
            ajaxReturn(0, MyError::lastError(), 0);
        }

        $uid = $userInfo['uid'];
        //检查是否重复
        //$otherUser = service("Account/Account")->getUserByMobile($mobile,$uid_type);
        $otherUser = service("Account/Account")->getUserByMobile($mobile, $userInfo['uid_type'], $userInfo['is_invest_finance']);
        if ($otherUser) {
            if ($otherUser['is_mobile_auth'] && ($otherUser['uid'] != $uid)) {
                ajaxReturn(0, "手机号码已被占用,请重新输入!", 0);
            }
        }

        $uid = $this->loginedUserInfo['uid'];
        service("Account/Account")->updateMobile($uid, $mobile);
        if (MyError::hasError()) {
            ajaxReturn(0, MyError::lastError(), 0);
        }
        service("Account/Account")->resetLogin($uid);
        ajaxReturn();
    }

    /**
     * 发送修改手机号码验证码
     */
    function sendEditMobileCode($mobile) {
        $service = service("Account/Validate");
        $service->sendEditMobileCode($mobile);
        if (MyError::hasError()) {
            ajaxReturn('', MyError::lastError(), 0);
        } else {
            ajaxReturn('', '动态码发送成功!');
        }
    }

    //发送当前手机号码检查验证码
    function sendCheckOldMobileCode($mobile) {
        $uid = $this->loginedUserInfo['uid'];
        $userInfo = service("Account/Account")->getByUid($uid);
        if ($mobile != $userInfo['mobile']) {
            ajaxReturn('', '当前手机号码不匹配!', 0);
        }

        $service = service("Account/Validate");
        $service->sendCheckOldMobileCode($mobile);
        if (MyError::hasError()) {
            ajaxReturn('', MyError::lastError(), 0);
        } else {
            ajaxReturn('', '动态码发送成功!');
        }
    }

    /**
     * 手机号码认证
     */
    function mobileAuth($mobile, $code, $uid_type = 1, $is_invest_finance = 1) {
        if (!$mobile || !$code) {
            ajaxReturn(0, "异常,参数错误!", 0);
        }
        if (!checkMobile($mobile)) {
            ajaxReturn(0, "手机号码格式错误!", 0);
        }

        if (!service("Account/Validate")->validateAuthMobileCode($mobile, $code)) {
            ajaxReturn(0, MyError::lastError(), 0);
        }
        $userInfo = $this->loginedUserInfo;
        $uid = $userInfo['uid'];
        //检查是否重复
        //$otherUser = service("Account/Account")->getUserByMobile($mobile,$uid_type);
        $otherUser = service("Account/Account")->getUserByMobile($mobile, $userInfo['uid_type'], $userInfo['is_invest_finance']);
        if ($otherUser) {
            if ($otherUser['is_mobile_auth']) {
                ajaxReturn(0, "手机号码已被占用,请重新输入!", 0);
            }
        }

        service("Account/Account")->authMobile($uid, $mobile);
        if (MyError::hasError()) {
            ajaxReturn(0, MyError::lastError(), 0);
        }
        service("Account/Account")->resetLogin($uid);
        ajaxReturn(array("mobile" => $mobile));

    }

    //修改用户名  已弃用
    function editUserName() {
        $isAjax = (int)$this->_request("isAjax");
        if ($isAjax) {
            $newUsername = $this->_request("username");
            $uid = $this->loginedUserInfo['uid'];
            $userInfo = service("Account/Account")->getByUid($uid);
            if (!$userInfo['is_change_uname']) {
                ajaxReturn(0, "你没有修改用户名的权限!", 0);
            }

            if (!service("Account/Account")->checkUname($newUsername)) {
                ajaxReturn(0, MyError::lastError(), 0);
            }

            $result = service("Account/Account")->updateUname($uid, $newUsername);
            if (!$result) {
                ajaxReturn(0, MyError::lastError(), 0);
            }
            ajaxReturn(0, "操作成功!");
        } else {
            // echo "<pre>"; print_r($this->loginedUserInfo);
            $this->assign('is_change_uname', $this->loginedUserInfo['is_change_uname']);
            $this->assign('username', $this->loginedUserInfo['uname']);
            $this->display();
        }
    }

    //修改邮箱
    function editEmailView() {
        $uname = $this->loginedUserInfo['uname'];
        $this->assign("uname", $uname);
        $this->display("editemailview");
    }

    //修改邮箱2 --20160304 关闭原逻辑
//    function editEmailView2(){
//        $oldEmail = $this->_request("oldemail");
//        $oldPwd = $this->_request("oldpwd");
//
//        if(!$oldEmail || !$oldPwd){
//            $this->error('当前邮箱和当前密码必须填写!',U('Account/User/editEmailView'));
//        }
//
//        $uid = $this->loginedUserInfo['uid'];
//        $userInfo = service("Account/Account")->getByUid($uid);
//        if($userInfo['password'] != pwd_md5($oldPwd)){
//            $this->error('当前密码不匹配!',U('Account/User/editEmailView'));
//        }
//
//        if($userInfo['email'] != $oldEmail){
//            $this->error('当前邮箱不匹配!',U('Account/User/editEmailView'));
//        }
//        BaseModel::newSession("EDIT_EMAIL",1);
//        $this->display("editemailview2");
//    }

    //修改邮箱2 --20160304 新的逻辑 start
    function editEmailView2() {
        $oldEmail = $this->_request("oldemail");
        $code = $this->_request("code");
        if (!$oldEmail || !$code) {
            $this->error('当前邮箱和手机动态码必须填写!', U('Account/User/editEmailView'));
        }

        $uid = $this->loginedUserInfo['uid'];
        $userInfo = service("Account/Account")->getByUid($uid);
        $mobile = $userInfo['mobile'];

        if ($userInfo['email'] != $oldEmail) {
            $this->error('当前邮箱不匹配!', U('Account/User/editEmailView'));
        }
        if (!service("Account/Validate")->validateAuthMobileCode($mobile, $code)) {
            $this->error(MyError::lastError(), U("Account/User/editEmailView"));

        }
        BaseModel::newSession("EDIT_EMAIL", 1);
        $this->display("editemailview2");
    }//end


    function editemail() {
        if (!BaseModel::newSession("EDIT_EMAIL")) {
            $this->error('非法访问!', U('Account/User/editEmailView'));
        }
        $email = $this->_request("email");
        C("TOKEN_ON", false);

        if (!$email) {
            $this->error("邮箱地址为空!");
        }

        if (!service("Account/Account")->checkEmail($email)) {
            $this->error(MyError::lastError());
        }

        $userInfo = $this->loginedUserInfo;

        $uid = $userInfo['uid'];
        $uname = $userInfo['uname'];
        service("Account/Account")->sendActiveEmail($uid, $email);
        if (MyError::hasError()) {
            $this->error(MyError::lastError());
        } else {
            redirect(U("Account/User/emailAuthSuccess?email=" . $email));
        }
    }

    /**
     * 发送手机认证动态码
     */
    function sendMobileAuthCode($mobile) {
        $service = service("Account/Validate");
        $service->sendAuthMobileCode($mobile);
        if (MyError::hasError()) {
            ajaxReturn('', MyError::lastError(), 0);
        } else {
            ajaxReturn('', '动态码发送成功!');
        }
    }

    //2016318 检查修改原邮箱需发送消息验证
    function sendEditEmailSms() {
        $oldEmail = $this->_request("oldemail");
        $userInfo = $this->loginedUserInfo;
        $uid = $userInfo['uid'];
        $userInfo = service("Account/Account")->getByUid($uid);
        $mobile = $userInfo['mobile'];
        if ($oldEmail && $userInfo['email'] == $oldEmail) {
            $this->sendMobileAuthCode($mobile);
        } elseif (!$oldEmail) {
            ajaxReturn('', '当前邮箱不能为空!', 0);
        } elseif ($userInfo['email'] != $oldEmail) {
            ajaxReturn('', '当前邮箱不匹配!', 0);
        }
    }

    /**
     * 上传头像
     * @param number $width
     * @param number $heigth
     * @param number $x
     * @param number $y
     */
    function uploadAva($path) {
        $service = service("Account/Account");
        if (!$path) {
            ajaxReturn(0, "参数异常", 0);
        }
        $result = D("Public/Upload")->moveFiles("img", $path, 1);
        $userInfo = $service->getLoginedUserInfo();
        $uid = $userInfo['uid'];
        $service->saveOrUpdateAvatar($uid, $result);
        if (MyError::hasError()) {
            $this->error(MyError::lastError());
        } else {
            $data['path'] = D("Public/Upload")->parseFileParam($result);
            $data['width'] = $this->_post('width');
            $data['heigth'] = $this->_post('heigth');
            $data['x'] = $this->_post('x');
            $data['y'] = $this->_post('y');
            service("Account/Account")->resetLogin($uid);
            ajaxReturn($data, "上传成功");
        }
    }

    /**
     * 第一次上传的图片路径
     * @param number $width
     * @param number $heigth
     */
    function getFirstAva($width = 420, $heigth = 285) {
        $service = service("Account/Account");
        $basePath = UPLOAD_PATH . "/image/avatar_tmp/";
        $imgPath = $service->uploadAvatar($basePath);
        if (MyError::hasError()) {
            ajaxReturn('', MyError::lastError(), 0);
        } else {
            //压缩
            $baseName = basename($imgPath);
            $md5 = substr(md5($baseName), 0, 2) . "/" . substr(md5($baseName), 2, 2) . "/" . substr(md5($baseName), 4, 2) . "/";
            $newPath = UPLOAD_PATH . "/image/avatar_thumb_tmp/" . $md5 . $baseName;
            $path = $service->thumb($imgPath, $newPath, $width, $heigth);

            $data['path'] = "/data/uploads/image" . $path;
            $data['width'] = $width;
            $data['heigth'] = $heigth;
            ajaxReturn($data);
        }
    }

    /**
     * 注册
     */
    function register() {
        $hmsr_param = session('hmsr_params');
        //$hmsr_param=BaseModel::newSession('hmsr_params'); //封装的BaseModel::newSession 空间被分割，有bug
        if ($this->loginedUserInfo['uid']) {
            redirect(U('Account/User/index'));
        }
//        if ($hmsr_param) {
//            redirect( U('Account/User/registerSem?'.$hmsr_param) );
//        }
        setMyCookie('xhh_red_amount', NULL);
        setMyCookie(self::PRIZE_INFO_CACHE, null);//清除上次注册的奖金
        setMyCookie('REGISTER_REFERER', $_SERVER['HTTP_REFERER']);
        //推荐来注册的
        $recommend = $this->_request('recommend');
        if (empty($recommend)) {
            $recommend = cookie('recommend');
        }
        if ($recommend) {
            if (preg_match('/\d+t\d+/', $recommend)) {
                $QiuShouEnd = strtotime(C('QIUSHOU'));  //秋收起义结束的时间
                $time = time();
                if ($time < $QiuShouEnd) {
                    $url = D("Account/Recommend")->getUidTokenUrlFall($recommend);
                    $jumpUrl = "Location:" . $url;
                    header($jumpUrl);
                }
            }
        }
        //记录渠道来源 2015/05
        $source['channel'] = I('request.source_channel', '');
        $source['uid'] = I('request.source_uid', null);
        if ($source['channel'] == 'wps') {
            $source['extra'] = I('request.extra', '');
        }
        if ($source['channel']) {//渠道信息存在时才改写，新的渠道信息会覆盖旧的，而如果没有新的渠道信息还是老的渠道信息，直到session结束
            BaseModel::newSession(UserModel::REGISTER_SOURCE_KEY, $source);
        }
        //如果是企福鑫企业码
        $type = I("type", "company", "trim");
        $qrcode = I("qrcode", "", "trim");
        $t = 1;//默认值
        if ($type && $qrcode) {
//            $recommend = "&type={$type}&qrcode={$qrcode}";
            $recommend = service("Financing/CompanySalary")->getQrcodeRecommend($type, $qrcode);
            $t = 2;//企福鑫
        }
        if (is_mobile_request()) {
            if ($_REQUEST['go'] == 'about') {
                setMyCookie('xhh_redcode', $recommend, 86400);
                $url2 = C('WAP_API_URL') . 'about.html';
                redirect($url2);
            }
            $url2 = 'http://' . C('SITE_DOMAIN') . "/Mobile2/Share/t?t={$t}&r=";
            $url2 .= $recommend;
            if ($recommend) {
                if (preg_match('/\d+t\d+/', $recommend)) {
                    $QiuShouEnd = strtotime(C('QIUSHOU'));  //秋收起义结束的时间
                    $time = time();
                    if ($time < $QiuShouEnd) {
                        $url = D("Account/Recommend")->getUidTokenUrlFall($recommend, 2);
                        redirect($url);
                    }
                }

            }
            redirect($url2);
        }

        $this->userCode = "";
        if (preg_match("/\d+t\d+/", $recommend)) {
            $fromUid = D("Account/Recommend")->parse2Uid($recommend);
            $this->userCode = D("Account/Recommend")->getUserCode($fromUid);
        } else {
            $fromUid = D("Account/Recommend")->getUidByUserCode($recommend);
            if ($fromUid) {
                $this->userCode = $recommend;
            }
        }
        $this->recommend = $recommend;
        if ($type && $qrcode) {
            $this->type = $type;
            $this->qrcode = $qrcode;
        }
        $this->assign('fraudmetrix_token', $this->getFraudmetrixToken());
        $this->display();
    }

    /**
     * 检查用户code
     */
    function checkUserCode() {
        $userCode = trim(I("request.hongbaoCode"));
        if (!$userCode)
            ajaxReturn();
        $fromUid = D("Account/Recommend")->getUidByUserCode($userCode);
        if ($fromUid) {
            ajaxReturn($fromUid);
        } else {
            ajaxReturn(0, "红包代码不存在!", 0);
        }
    }

    function checkRealName() {
        $real_name = $this->_request('real_name');
        $real_name = trim($real_name);
        if (!$real_name)
            ajaxReturn(0, 0, 0);
        $userInfo = M("user")->where(array("real_name" => $real_name, "is_del" => 0))->field("uid,uname,mobile")->select();
        if (!$userInfo)
            ajaxReturn(0, 0, 0);
        foreach ($userInfo as $key => $usr) {
            if (!$usr['mobile'])
                continue;
            $userInfo[$key]['mobile'] = marskName($usr['mobile'], 3, 4);
        }

        $data = array();
        $data['count'] = count($userInfo);
        $data['list'] = $userInfo;
        ajaxReturn($data);
    }

    /**
     * 处理注册
     * @param unknown $username
     * @param unknown $pw1
     * @param unknown $pw2
     * @param unknown $email
     * @param unknown $authCode
     */
    function doregister() {
        $recommend = I("request.recommend", "", "trim");
        $mobile = $username = I("request.mobile");
        $code = I("request.code");
        $authCode = I("request.authCode");
        $pwd1 = $_REQUEST['pwd'];
        $hongbaoCode = I("request.hongbaoCode", "", "trim");
        $is_invest_finance = I("request.is_invest_finance", 1, 'intval');//2015.3.23 增加一个类型是否是投资人或融资人,默认1为投资人
        // add by 001181 企福鑫企业码注册
        $type = I("type", "company", "trim");
        $qrcode = I("qrcode", "", "trim");
        $uid_type = I("request.uid_type", 1, 'intval');
        $source_channel = $_REQUEST['source_channel'];
        $source = BaseModel::newSession(UserModel::REGISTER_SOURCE_KEY);

        //发给同盾的数据
        $register_data_send_tag_params = ['event_type' => 'register_web', 'account_login' => $username, 'account_mobile' => $mobile, 'ip_address' => get_client_ip(), 'refer_cust' => cookie('REGISTER_REFERER'), 'fraudmetrix_token' => $this->getFraudmetrixToken(), 'state' => 1,];

        setMyCookie('REGISTER_REFERER', null);

        $ext_info = array();

        //响应错误类型
        $error_type = 1;
        try {
            //把验证的放到这里来重新验证一次
            if (!service("Account/Account")->checkMobile($mobile, $uid_type, $is_invest_finance)) {
                throw_exception('手机号码已被占用');
            }

            //2015.1.16 类型注册为企业的，如果类型是企业，则username为用户名，否则是个人为手机。
            if ($uid_type == 2) {
                $uname = $username = I('request.uname');//用户名
                $proposer = I('request.proposer');//申请人身份1.法人2.代理人
                if (empty($uname)) {
                    $error_type = '';
                    throw_exception('没有填写用户名');
                }
                if (empty($proposer)) {
                    $error_type = '';
                    throw_exception('没有填写申请人身份');
                }
                $ext_info['hongbaoCode'] = $hongbaoCode;
                $service = service("Account/Account");
                if (!$service->checkUname($uname)) {
                    $error_type = 4;
                    throw_exception(MyError::lastError());
                }
                $register_data_send_tag_params['account_login'] = $username;
            }

            //关闭令牌
            C('TOKEN_ON', false);

            if (!service("Account/Validate")->validateRegisterMobileCode($mobile, $code)) {
                $error_type = 3;
                throw_exception(MyError::lastError());
            }

            import_addon("libs.Verify.Verify");
            $key = Verify::$seKey;
            BaseModel::newSession($key, null);

            //$pwd1 = substr($mobile,-6);
            $userObj = service("Account/Account");

            //企福鑫继承上级的企业ID
            if ($recommend) {
                $ext_info['recommend'] = $recommend;
            }
            //通过企业二维码加入企业计划
            if ($type && $qrcode) {
                $ext_info['type'] = $type;
                $ext_info['qrcode'] = $qrcode;
            }
            $uid = 0;
            try {
                $uid = $userObj->register_web($username, $pwd1, $mobile, $uid_type, $ext_info, $is_invest_finance);
                //$uid = $userObj->register_vOne($username, $pwd1, $mobile, $uid_type,$ext_info,$is_invest_finance);
            } catch (Exception $e) {
                $error_type = 6;
                throw_exception($e->getMessage());
            }
            if ($uid) {
                $register_data_send_tag_params['state'] = 0;
                tag('safe_data_send', $register_data_send_tag_params);
                //TODO: 登录
                if ($uid_type != 2) {
                    $userObj->login($mobile, $pwd1);
                } else {
                    $userObj->login($username, $pwd1, $uid_type, $is_invest_finance);
                }
                tag('exit_end');


                if ($uid_type == 2) {
                    ajaxReturn(U("Account/User/infoStep1"), "注册成功", 1);
                }
                
                //集趣注册过来，则回调
                $jiqu_service = service('Api/JiQu');
                if($_COOKIE['hmsr']==$jiqu_service->user_source){
                    $jiqu_service->addQueue($uid,$mobile);
                }

                //注册成功,返回json
                ajaxReturn(U("Account/User/registerSuccess", array('id' => $uid)), "注册成功", 1);
            } else {
                $error_type = 5;
                throw_exception(MyError::lastError());
            }

        } catch (Exception $exc) {
            tag('safe_data_send', $register_data_send_tag_params);
            tag('exit_end');
            ajaxReturn($error_type, MyError::lastError(), 0);
        }
    }

    //检查注册动态码
    function checkRegisterCode($mobile, $code) {
        if (!service("Account/Validate")->validateRegisterMobileCode($mobile, $code)) {
            ajaxReturn(0, MyError::lastError(), 0);
        }
        ajaxReturn();
    }

    /**
     * 注册发送验证码
     */
    function sendRegisterMobileCode($mobile, $authCode, $uid_type = 1, $is_invest_finance = 1) {
        if (!check_verify($authCode)) {
            ajaxReturn(2, "验证码错误!", 0);
        }
        $service = service("Account/Validate");

        if (!checkMobile($mobile)) {
            ajaxReturn(1, '手机号码格式错误', 0);
        }

        if (!service("Account/Account")->checkMobile($mobile, $uid_type, $is_invest_finance)) {
            ajaxReturn(1, '手机号码已被占用', 0);
        }

        $service->sendRegisterMobileCode($mobile);
        if (MyError::hasError()) {
            ajaxReturn(3, MyError::lastError(), 0);
        } else {
            ajaxReturn('', '动态码发送成功!');
        }
    }

    /**
     * 注册发送验证码
     */
    function sendRegisterMobileCodeSway($mobile, $uid_type = 1, $is_invest_finance = 1) {
        $xhh_challenge = $_POST['xhh_challenge'];
        $xhh_validate = $_POST['xhh_validate'];
        $xhh_seccode = $_POST['xhh_seccode'];
        $sway_gtserver = BaseModel::newSession('sway_gtserver');
        $user_id = BaseModel::newSession('sway_gtuser_id');
        $gtret = service('Mobile/User')->verifyLoginServlet($xhh_challenge, $xhh_validate, $xhh_seccode, $user_id, $sway_gtserver);
        if (!$gtret)
            ajaxReturn(4, "滑动验证失败!", 0);

        /*if(!check_verify($authCode)){
            ajaxReturn(2,"验证码错误!",0);
        }*/

        $service = service("Account/Validate");

        if (!checkMobile($mobile)) {
            ajaxReturn(1, '手机号码格式错误', 0);
        }

        if (!service("Account/Account")->checkMobile($mobile, $uid_type, $is_invest_finance)) {
            ajaxReturn(1, '手机号码已被占用', 0);
        }

        $service->sendRegisterMobileCode($mobile);
        if (MyError::hasError()) {
            ajaxReturn(3, MyError::lastError(), 0);
        } else {
            ajaxReturn('', '动态码发送成功!');
        }
    }

    function modifyLoginPwdSuccess() {
        if ($this->loginedUserInfo['uid']) {
            redirect(U('Account/User/index'));
        }
        $this->display();
    }

    /**
     * 注册成功页
     */
    function registerSuccess() {
        $uid = (int)$this->_get("id");
        if (!$uid)
            $this->error("参数错误!");
        $obj = service("Account/Account");
        $info = $obj->getByUid($uid);
        $loginUrl = $obj->getEmailLoginUrl($uid);

        $prizeMoney = cookie(self::PRIZE_INFO_CACHE);
        $this->assign("prize", $prizeMoney);

        $this->assign("user", $info);
        $this->assign("loginUrl", $loginUrl);
        $this->useruid = $uid;
        $company_id = D("Account/UserExt")->getUserCompanyId($uid);
        $this->assign("company_id", $company_id);//大于0 （企福鑫用户） 等于0（普通用户）
        $this->display();
    }

    /**
     * 登录显示
     */
    function login() {
        $url = I('get.url', $_SERVER['HTTP_REFERER']);
        $from = I('get.from', $_SERVER['HTTP_REFERER']);
        if ($url == '/Financing/Invest/plist') {
            $url = 'http://' . $_SERVER['HTTP_HOST'] . $url;
        }
        if ($url == '/Account/XinPartner/index') {
            $url = 'http://' . $_SERVER['HTTP_HOST'] . $url;
        }
        if ($url) {
            setMyCookie(self::LOGIN_REFERER_URL, $url);
        }

        if ($from) {
            setMyCookie("LOGIN_REFERER_URL_FROM", $from);
        }

        if ($this->loginedUserInfo) {
            $this->redirect("/");
        }

        $this->assign("loginName", service("Account/Account")->getLoginUseName());
        //是否需要显示验证码

        $showAuthCode = false;
        if (S('login_error_num_pc_' . get_client_ip()) > 2) {
            $showAuthCode = true;
        }

        $this->assign('showAuthCode', $showAuthCode);

        $this->assign('fraudmetrix_token', $this->getFraudmetrixToken());
        $this->display("login");
    }

    /**
     * 处理登录
     * @param unknown $username
     * @param unknown $pwd
     * @param unknown $authCode
     */
    function dologin() {
        $username = I('post.username');
        $pwd = $_POST['pwd'];
        $authCode = I('request.authCode');
        $remember = I('request.remember', 1);
        $uid_type = I('request.uid_type', 1);
        $is_invest_finance = I('request.is_invest_finance', 1);

        $url = cookie(self::LOGIN_REFERER_URL);
        $url = $url ? rawurldecode($url) : U('Account/User/index');
        if (cookie("LOGIN_REFERER_URL_FROM") == 'bbs' && $url) {
            $url = loginBBS($url);
        }

        $clientIp = get_client_ip();
        $error_data = 0;
        $error_type = 0;
        $login_data_send_tag_params = ['event_type' => 'login_web', 'account_login' => $username, 'ip_address' => $clientIp, 'refer_cust' => $url, 'fraudmetrix_token' => $this->getFraudmetrixToken(), 'state' => 1,];

        try {

            /* 检测验证码 */
            if (S('login_error_num_pc_' . $clientIp) > 2) {
                if (!check_verify($authCode, "login")) {
                    $error_data = array('showAuthCode' => 1);
                    throw_exception(MyError::lastError());
                }
            }

            if (!$username || !$pwd) {
                throw_exception('帐号或者密码不能为空！');
            }

            $userObj = service("Account/Account");

            if ($userObj->login($username, $pwd, $uid_type, $is_invest_finance)) {
                $login_data_send_tag_params['state'] = 0;
                tag('safe_data_send', $login_data_send_tag_params);
                if ($remember) {
                    $userObj->rememberLogin($username);
                } else {
                    $userObj->unsetRememberLogin();
                }

                $loginUserInfo = service("Account/Account")->getLoginedUserInfo();
                $data = D("Account/LoginLog")->getUserLoginLog($loginUserInfo['uid']);
                setcookie("visit", json_encode($data));
                setcookie("tipShow", "", time() - 3600, "/Financing/Invest/");
                setcookie("tipShow", "", time() - 3600, "/index.php/Financing/Invest/");
                setcookie("tipShow", "", time() - 3600, "/Financing/Invest");
                setcookie("tipShow", "", time() - 3600, "/index.php/Financing/Invest");


                if (!$loginUserInfo['is_set_uname']) {
                    $url = U('Account/User/index');
                }

                $cookietmp = $_COOKIE[self::LOGIN_REFERER_URL];
                $urltmp = parse_url($cookietmp);
                $cookietmp = $urltmp['path'];
                if ($cookietmp == U('Account/XinPartner/index'))
                    $url = U('Account/XinPartner/index');//$url = U('Activity/AprilFoe/index')."#mdrecommend";
                if ($cookietmp == U('/Financing/Invest/plist'))
                    $url = U('/Financing/Invest/plist');
                if ($cookietmp == U('/Activity/AprilFoe/index'))
                    $url = U('Activity/AprilFoe/index');
                //访问客户理财账单，登入后所有用户需要跳回客户理财账单页
                if ($cookietmp == U('/Account/Bill/check'))
                    $url = U('Account/Bill/check');
                //鑫拍档二期登入后跳回二期活动页面
                if($cookietmp == U('/Activity/XinPartner2/index'))  $url = U('/Activity/XinPartner2/index');

                if (strpos($cookietmp, '/Financing/Invest/view') !== false) {
                    $url = $_COOKIE[self::LOGIN_REFERER_URL];
                }
                //鑫拍档三期登入后跳回三期活动页面
                if ($cookietmp == U('/Activity/XinPartner3/index'))
                    $url = U('/Activity/XinPartner3/index');

                //活动系统跳转过来的链接，登录成功后回跳回去
                if(isset($_COOKIE[self::LOGIN_REFERER_URL])){
                    if(strpos($_COOKIE[self::LOGIN_REFERER_URL],'xinhehui.com') !== false && (strpos($_COOKIE[self::LOGIN_REFERER_URL],'http://') !== false || strpos($_COOKIE[self::LOGIN_REFERER_URL],'https://') !== false )){
                        $url = $_COOKIE[self::LOGIN_REFERER_URL];
                    }
                }

                //角色跳转
                $_url = D('Account/User')->getHomeUrlByRole($loginUserInfo['role']);
                $_url && $url = $_url;

                S('login_error_num_pc_' . $clientIp, null);

                setMyCookie("LOGIN_REFERER_URL_FROM", null);
                setMyCookie(self::LOGIN_REFERER_URL, null);

                //春节登录送888元满减券活动
                $fetchBonusByRegService = new \App\Modules\JavaApi\Service\JavaBonusService();
                $fetchBonusByRegService->sendInformEvent($loginUserInfo['uid'], 'EC2017011301');
                \Addons\Libs\Log\Logger::info("春节登录送888元满减券事件", 'EC2017011301', array('uid' => $loginUserInfo['uid']));

                service('Api/TianYanRequest')->memberBind($username, $loginUserInfo['uid']);

                ajaxReturn2($url, "登录成功!");
            } else {

                if ($currentN = S('login_error_num_pc_' . $clientIp)) {
                    S('login_error_num_pc_' . $clientIp, $currentN + 1);
                } else {
                    S('login_error_num_pc_' . $clientIp, 1, array('expire' => 86400));
                }
                $error_data = array('showAuthCode' => (int)$currentN > 1 ? 1 : 0);
                throw_exception(MyError::lastError());
            }

        } catch (Exception $exc) {
            tag('safe_data_send', $login_data_send_tag_params);
            ajaxReturn2($error_data, $exc->getMessage(), $error_type);
        }

    }

    /**
     * 短信登录
     */
    function doSMSLogin($mobile, $code) {
        if (!$mobile || !$code) {
            ajaxReturn(0, '手机号码或者动态码不能为空！', 0);
        }

        if (!checkMobile($mobile)) {
            ajaxReturn(0, '手机号码格式错误！', 0);
        }

        $service = service("Account/Validate");
        if ($service->validateLoginMobileCode($mobile, $code)) {
            $userService = service("Account/Account");
            if (!$userService->login($mobile, '', 1)) {
                ajaxReturn(0, MyError::lastError(), 0);
            } else {
                $url = BaseModel::newSession(self::LOGIN_REFERER_URL);
                $url = $url ? urldecode($url) : U('Account/User/index');

                $loginUserInfo = service("Account/Account")->getLoginedUserInfo();
                $data = D("Account/LoginLog")->getUserLoginLog($loginUserInfo['uid']);
                setcookie("visit", json_encode($data));

                ajaxReturn($url, "登录成功!");
            }
        } else {
            ajaxReturn(0, MyError::lastError(), 0);
        }
    }

    /**
     * 登录发送验证码
     */
    function sendLoginMobileCode($mobile, $uid_type = 1, $is_invest_finance = 1) {
        if (!checkMobile($mobile)) {
            ajaxReturn('', '手机号码格式错误', 0);
        }
        $userInfo = service("Account/Account")->getUserByMobile($mobile, $uid_type, $is_invest_finance);
        if (!$userInfo)
            ajaxReturn('', '操作失败，未认证的手机号码!', 0);
        $service = service("Account/Validate");
        $result = $service->sendLoginMobileCode($mobile);
        $mobile = moblie_view($mobile);
        if (!$result) {
            ajaxReturn('', MyError::lastError(), 0);
        } else {
            ajaxReturn($mobile, '动态码发送成功!');
        }
    }

    /**
     * 退出
     */
    function logout() {
        $userObj = service("Account/Account");
        $userObj->logout();
        BaseModel::newSession(self::LOGIN_REFERER_URL, null);

        $this->redirect('/Index/Index/index');
    }

    /**
     * 修改密码
     * @param unknown $oldPwd
     * @param unknown $pwd1
     * @param unknown $pwd2
     */
    function updatePwd() {
        $oldPwd = $_REQUEST['oldPwd'];
        $pwd1 = $_REQUEST['pwd1'];
        $pwd2 = $_REQUEST['pwd2'];

        C("TOKEN_ON", false);
        if (!$oldPwd || !$pwd1 || !$pwd2) {
            ajaxReturn(0, '参数错误', 0);
        }

        if ($pwd1 != $pwd2) {
            ajaxReturn(0, '两次新密码输入不一致!', 0);
        }

        $user = service("Account/Account");
        $uid = $this->loginedUserInfo['uid'];

        $act_qsqy = M('user')->where(array('uid' => $uid))->find();

        if ($act_qsqy['is_set_pwd'] == 1) {
            if ($user->updatePwdWithOldPwd($uid, $pwd1, $oldPwd)) {
                $user->updatePayPwd($uid, $pwd1);
                $state = M('user')->where(array('uid' => $uid, 'is_set_pwd' => 1))->save(array('is_set_pwd' => 2));
                if ($state) {
                    ajaxReturn(0, '修改成功!', 1);
                }
            } else {
                ajaxReturn(0, '修改失败!', 0);
            }
        }

        $userAccountInfo = M("user_account")->where(array("uid" => $uid))->find();

        if ($userAccountInfo['pay_password'] == pwd_md5($pwd1)) {
            ajaxReturn(0, '支付密码不能与登录密码相同!', 0);
        }

        if ($user->updatePwdWithOldPwd($uid, $pwd1, $oldPwd)) {
            //修改密码打个时间点，修改密码弹窗的内容
            import("libs/Cache/RedisSimply", ADDON_PATH);
            $key = "tishi";
            $redis = new RedisSimply($key);
            $now = time();
            $redis->set($uid, $now);

            ajaxReturn(0, '修改成功!', 1);
        } else {
            ajaxReturn(0, MyError::lastError(), 0);
        }
    }

    /**
     * 修改付款密码
     * @param unknown $oldPwd
     * @param unknown $pwd1
     * @param unknown $pwd2
     */
    function updatePayPwd() {

        $oldPwd = $_REQUEST['oldPwd'];
        $pwd1 = $_REQUEST['pwd1'];
        $pwd2 = $_REQUEST['pwd2'];

        C("TOKEN_ON", false);
        if (!$oldPwd || !$pwd1 || !$pwd2) {
            ajaxReturn(0, '参数错误', 0);
        }

        if ($pwd1 != $pwd2) {
            ajaxReturn(0, '两次新密码输入不一致!', 0);
        }

        $uid = $this->loginedUserInfo['uid'];
        $user = service("Account/Account");
        $userInfo = $user->getByUid($uid);

        if (!$userInfo['is_id_auth'] && !$this->loginedUserInfo['role']) {//角色用户 不用实名认证
            ajaxReturn(0, '您还没有通过实名认证，不能修改支付密码!', 0);
        }

        if ($userInfo['password'] == pwd_md5($pwd1)) {
            ajaxReturn(0, '支付密码不能与登录密码相同!', 0);
        }

        if (!$user->checkOldPayPwd($uid, $oldPwd)) {
            ajaxReturn(0, '当前支付密码错误!', 0);
        }

        $payPwd = M("user_account")->where(array("uid" => $uid))->getField("pay_password");
        if ($payPwd == pwd_md5($pwd1)) {
            ajaxReturn(0, '支付密码不能与旧的支付密码相同!', 0);
        }

        if ($user->updatePayPwd($uid, $pwd1)) {
            ajaxReturn(0, '修改成功!', 1);
        } else {
            ajaxReturn(0, MyError::lastError(), 0);
        }

    }


    /**
     * 检查昵称
     * @param string $username
     */
    function checkUname($username) {
        $service = service("Account/Account");
        if ($service->checkUname($username)) {
            ajaxReturn();
        } else {
            ajaxReturn(0, MyError::lastError(), 0);
        }
    }

    /**
     * 检查昵称 同checkUname
     * @param string $uname
     */
    function checkUserName($uname) {
        $this->checkUname($uname);
    }

    /**
     * ajax mobile检查
     * @param unknown $mobile
     */
    function checkMobile($mobile, $uid_type = 1, $is_invest_finance = 1) {
        if (!checkMobile($mobile)) {
            ajaxReturn('', '手机格式错误,请使用正确的大陆移动电话号码，以方便讯息查收', 0);
        }
        $service = service("Account/Account");
        if ($service->checkMobile($mobile, $uid_type, $is_invest_finance)) {
            ajaxReturn();
        } else {
            ajaxReturn(0, MyError::lastError(), 0);
        }
    }

    /**
     * 检查手机格式
     * @param unknown $mobile
     */
    function checkMobileFormat($mobile) {
        if (checkMobile($mobile)) {
            ajaxReturn();
        } else {
            ajaxReturn(0, "手机号码格式错误!", 0);
        }
    }

    /**
     * ajax email检查
     * @param unknown $email
     */
    function checkEmail($email) {
        $service = service("Account/Account");
        if ($service->checkEmail($email)) {
            ajaxReturn();
        } else {
            ajaxReturn(0, MyError::lastError(), 0);
        }
    }

    /**
     * 检查密码
     * @param unknown $pw1
     */
    function checkPwd() {
        $pwd1 = isset($_REQUEST['pwd1']) ? $_REQUEST['pwd1'] : $_REQUEST['password1'];
        $service = service("Account/Account");
        if ($service->checkPwd($pwd1)) {
            ajaxReturn();
        } else {
            ajaxReturn(0, MyError::lastError(), 0);
        }
    }

    /**
     * 2016229 修改支付密码时,先检查支付密码，支付密码是6位数字
     * @param unknown $pw1
     */
    function checkPaymentPwd() {
        $pwd1 = isset($_REQUEST['pwd1']) ? $_REQUEST['pwd1'] : $_REQUEST['password1'];
        $service = service("Account/Account");
        if ($service->checkPaymentPwd($pwd1)) {
            ajaxReturn();
        } else {
            ajaxReturn(0, MyError::lastError(), 0);
        }
    }

    /**
     * 认证邮箱
     * @param unknown $token
     */
    function activeEmail($token) {
        if (!$token) {
            $this->error("参数错误!");
        }

        $userObj = service("Account/Account");

        $data = array();
        if ($userObj->emailActive($token, $data)) {
            //更新
            $userObj->closeEmailLog($token);

            //B端
            if (I('get.from') == 'bclient') {
                $this->assign('email', $data['email']);
                $this->display('activeEmailBclient');
                exit;
            }

            $this->success('邮箱认证成功！', U('Account/User/index'));
        } else {
            $this->error(MyError::lastError());
        }
    }

    /**
     * 重新发送激活邮件
     * @param unknown $uid
     */
    function resendActiveEmail($uid, $email) {
        $uid = (int)$uid;
        if (!$uid || !$email) {
            ajaxReturn('', "参数错误", 0);
        }
        $userObj = service("Account/Account");
        if ($userObj->resendActiveEmail($uid, $email)) {
            ajaxReturn('', '操作成功', 1);
        } else {
            ajaxReturn('', MyError::lastError(), 0);
        }
    }

    /**
     * 邮箱验证
     * @param unknown $email
     */
    function emailAuth($email) {
        C("TOKEN_ON", false);
        if (!$email) {
            $this->error("邮箱地址为空!");
        }

        if (!service("Account/Account")->checkEmail($email)) {
            $this->error(MyError::lastError());
        }

        $uid = (int)$this->loginedUserInfo['uid'];
        $userInfo = D("Account/User")->getByUid($uid);
        if ($userInfo['is_email_auth']) {
            $this->error("邮箱已验证，不能进行此操作");
        }

        $uid = $userInfo['uid'];
        $uname = $userInfo['uname'];
        service("Account/Account")->sendActiveEmail($uid, $email);
        if (MyError::hasError()) {
            $this->error(MyError::lastError());
        } else {
            redirect(U("Account/User/emailAuthSuccess?email=" . $email));
        }
    }

    /**
     * 成功页面
     */
    function emailAuthSuccess($email, $action = 0) {
        if (!$email) {
            $this->error("页面不存在!");
        }
        if ($action == 0) {
            if (!service("Account/Account")->checkEmail($email)) {
                $this->error(MyError::lastError());
            }
        }
        $loginUrl = service("Account/Account")->getEmailLoginUrl($email);
        $this->assign("loginUrl", $loginUrl);
        $this->assign("email", $email);
        $this->assign("action", $action);
        $this->assign("user", $this->loginedUserInfo);
        $this->display("emailauthsuccess");
    }

    /**
     * 忘记密码
     */
    function forgetpwd() {
        $this->error('非法操作', U("Account/User/forgetpwd2"));

        // $key = "doforgetpwd_user";
        // BaseModel::newSession($key,null);
        // $key = "doforgetpwd2";
        // BaseModel::newSession($key,null);
        // $this->display();
    }

    /**
     * 处理忘记密码
     */
    function doforgetpwd($account, $authCode) {
        $this->error('非法操作', U("Account/User/forgetpwd2"));

        // if(!$account || !$authCode){
        // 	$this->error("账户或验证码为空!");
        // }

        // if(!check_verify($authCode)){
        //     $this->error('验证码验证失败!');
        // }
        // import_addon("libs.Verify.Verify");
        // $key = Verify::$seKey;
        //  BaseModel::newSession($key, null);

        // $info = array();
        // $mobileFind = 0;
        // if(checkMobile($account)){
        //       $mobileFind  = 1;
        // 	    $info = service("Account/Account")->getUserByMobile($account);
        // }elseif(checkEmail($account)){
        // 	    $info = service("Account/Account")->getByEmail($account);
        // }

        // if(!$info){
        //     $info = service("Account/Account")->getUserByUname($account);
        // }

        // if(!$info){
        //     $this->error('该用户不存在!');
        // }

        // $key = "doforgetpwd_user";
        // $info["mobile_find"] = $mobileFind;
        // unset($info['password']);
        //  BaseModel::newSession($key,$info);
        // redirect(U("Account/User/forgetpwd2"));
    }

    /**
     * 忘记密码第二步
     */
    function forgetpwd2() {
        // $key = "doforgetpwd_user";
        // $userInfo = BaseModel::newSession($key);
        // $userInfo = $userInfo ? $userInfo :"";
        // if(!$userInfo){
        //     $this->error('非法操作',U("Account/User/forgetpwd"));
        // }

        // if(empty($userInfo['mobile']))
        // {
        // 	$this->error('该用户未绑定手机号');
        // }

        // $mobileView = substr($userInfo['mobile'], 0,3)."******".substr($userInfo['mobile'], -2);
        // $this->assign("mobileView",$mobileView);
        // $this->assign("mobile",$userInfo['mobile']);

        $tplPath = "forgetpwd2";
        // if($userInfo["mobile_find"] == 1){
        //     $tplPath = "forgetpwd22";
        // }
        $this->display($tplPath);
    }

    /**
     * 处理忘记密码
     */
    function doforgetpwd2($mobile, $code, $uid_type = 1, $is_invest_finance = 1) {
        if (!$mobile || !$code) {
            ajaxReturn('', '手机号码或动态码为空!', 0);
        }

        // $key = "doforgetpwd_user";
        // $userInfo = BaseModel::newSession($key);
        // $userInfo = $userInfo ? $userInfo :"";
        // if(!$userInfo){
        //     $this->error('非法操作',U("Account/User/forgetpwd"));
        // }

        $authCode = I("post.authCode");
        if (!check_verify($authCode)) {
            ajaxReturn(2, '验证码错误', 0);
        }


        if (!service("Account/Validate")->validateForgetpwd2MobileCode($mobile, $code)) {
            ajaxReturn('', MyError::lastError(), 0);
        }

        // if($userInfo['mobile'] != $mobile){
        //     $this->error("手机号码验证失败!");
        // }


        $info = service("Account/Account")->getUserByMobile($mobile, $uid_type, $is_invest_finance);
        if (!$info) {
            ajaxReturn('', '该用户不存在!', 0);
        }

        $key = "doforgetpwd2_mobile";
        BaseModel::newSession($key, array('uid' => $info['uid'], 'mobile' => $mobile));
        if ($this->isAjax()) {
            ajaxReturn(U("Account/User/forgetpwd3"));
        }
        redirect(U("Account/User/forgetpwd3"));
    }

    /**
     * 找回密码第三步
     */
    function forgetpwd3() {
        $key = "doforgetpwd2_mobile";
        $info = BaseModel::newSession($key);
        if (!$info) {
            $this->error('非法访问', U("Account/User/forgetpwd2"));
        }

        // $key = "doforgetpwd2";
        // $checkTmp = BaseModel::newSession($key);
        // if(!$checkTmp){
        //     $this->error('非法访问',U("Account/User/forgetpwd2"));
        // }

        $this->display();
    }


    /**
     * 忘记密码发送验证码
     */
    function sendForgetpwd2MobileCode($mobile) {
        if (!checkMobile($mobile)) {
            ajaxReturn('', '手机号码格式错误', 0);
        }
        $service = service("Account/Validate");
        $service->sendForgetpwd2MobileCode($mobile);
        if (MyError::hasError()) {
            ajaxReturn('', MyError::lastError(), 0);
        } else {
            ajaxReturn('', '动态码发送成功!');
        }
    }

    /**
     * 处理重设密码
     */
    function doResetPwd() {
        $pwd1 = $_REQUEST['pwd1'];
        $pwd2 = $_REQUEST['pwd2'];
        // $key = "doforgetpwd_user";
        // $userInfo = BaseModel::newSession($key);
        // $userInfo = $userInfo ? $userInfo :"";
        // if(!$userInfo){
        //     $this->error('非法访问',U("Account/User/forgetpwd"));
        // }

        // $key = "doforgetpwd2";
        // $checkTmp = BaseModel::newSession($key);
        // if(!$checkTmp){
        //     $this->error('非法访问',U("Account/User/forgetpwd2"));
        // }

        $key = "doforgetpwd2_mobile";
        $info = BaseModel::newSession($key);
        if (!$info) {
            $this->error('非法访问', U("Account/User/forgetpwd2"));
        }

        $uid = $info['uid'];
        $user = service("Account/Account");

        if ($pwd1 != $pwd2) {
            $this->error("两次密码输入不一致!");
        }

        $payPwd = M("user_account")->where(array("uid" => $uid))->getField("pay_password");

        if ($payPwd == pwd_md5($pwd1)) {
            $this->error('支付密码不能与登录密码相同!');
        }

        if (!$user->updatePwd($uid, $pwd1)) {
            $this->error(MyError::lastError());
        } else {
            $key = "doforgetpwd2_mobile";
            BaseModel::newSession($key, null);//设置密码后，value设置为空，将其删除
            $userObj = service("Account/Account");
            $logout = $userObj->logout();
//            ajaxReturn(0,'新密码设置成功，请重新登录',1);
            redirect(U('Account/User/modifyLoginPwdSuccess'));
        }
    }

    /**
     * 发送手机验证码
     * @param unknown $mobile
     */
    function sendResetPayPwdMobileCode() {
        $service = service("Account/Validate");
        $uid = $this->loginedUserInfo['uid'];
        $service->sendResetPayPwdMobileCode($uid);
        if (MyError::hasError()) {
            ajaxReturn('', MyError::lastError(), 0);
        } else {
            ajaxReturn('', '验证码发送成功!');
        }
    }


    /**
     * 检查身份证号码
     * @param unknown $person_id
     */
    function checkPersonId($person_id) {
        if (!$person_id) {
            ajaxReturn('', '身份证号码不能为空!', 0);
        }
        $accountService = service("Account/Account");

        $uid = $this->loginedUserInfo['uid'];
        $userInfo = $accountService->getByUid($uid);

        $realPersonId = $userInfo['person_id'];
        $isIdAuth = $userInfo['is_id_auth'];

        if (!$isIdAuth) {
            ajaxReturn('', '您还没有通过实名认证，不能进行此操作!', 0);
        }

        if ($person_id != $realPersonId) {
            ajaxReturn('', '身份证号码不匹配!', 0);
        }

        ajaxReturn();
    }

    //检查身份证是否唯一
    function checkPersonIdUnique($person_id) {
        // checkPersonId
        $service = service("Account/Account");
        if ($service->checkPersonId($person_id)) {
            ajaxReturn();
        } else {
            ajaxReturn(0, MyError::lastError(), 0);
        }
    }

    /**
     * 找回支付密码
     */
    function resetPayPwd() {
        $personId = $this->_post("person_id");
        $code = $this->_post("code");
        $pwd1 = $_POST("pwd1");
        $pwd2 = $_POST("pwd2");
        if (!$personId) {
            ajaxReturn('', '身份证号不能为空', 0);
        }

        if (!$code) {
            ajaxReturn('', '验证码不能为空', 0);
        }

        if (!$pwd1) {
            ajaxReturn('', '新密码不能为空', 0);
        }

        if ($pwd1 != $pwd2) {
            ajaxReturn('', '两次输入密码不匹配!', 0);
        }
        $accountService = service("Account/Account");

        $uid = $this->loginedUserInfo['uid'];
        $userInfo = $accountService->getByUid($uid);

        $realPersonId = $userInfo['person_id'];
        $isIdAuth = $userInfo['is_id_auth'];
        if (!$isIdAuth) {
            ajaxReturn('', '您还没有通过实名认证，不能进行此操作!', 0);
        }

        if ($personId != $realPersonId) {
            ajaxReturn('', '身份证号码不匹配!', 0);
        }

        $service = service("Validate");
        if (!$service->validateResetPayPwdMobileCode($uid, $code)) {
            ajaxReturn('', MyError::lastError(), 0);
        }

        //开始修改密码

        if (!$accountService->updatePayPwd($uid, $pwd1)) {
            ajaxReturn('', MyError::lastError(), 0);
        }

        ajaxReturn('', '操作成功');
    }

    //用户中心下线
    function usc_logout() {
        $uscUid = $this->_request("usc_user_id");
        $fromSign = $this->_request('sign');
        if (!$uscUid)
            ajaxReturn(0, "id为空", 0, 0);
        $sign = md5($uscUid . C('DATA_AUTH_KEY'));
//     	echo $sign;exit;
        if ($fromSign != $sign)
            ajaxReturn(0, "签名错误", 0, 0);
        $userInfo = service("Account/Account")->getByUscId($uscUid);
        if (!$userInfo)
            ajaxReturn(0, "用户不存在", 0, 0);
        service("Account/Account")->setUserOffline($userInfo['uid']);
        jsonReturn('', "退出成功");
    }

    //每月数据
    function getMonthData() {
        $year = (int)$this->_request("year");
        $month = (int)$this->_request("month");

        /* @var $repay_service RepayService */
        $repay_service = service("Account/Repay");
        $data = $repay_service->getMonthData($year, $month);
        $info = $repay_service->getMonthRepayedNum($year, $month);
        $list = $repay_service->getMonthRepayedMoney($year, $month, $info);
        import_addon("libs.Calendar");
        $D = new Calendar;
        $D->setSpecialDay($data);
        $D->setSpecialDay2($list);
        $D->setXjhPayedData($repay_service->getXjhMonthPayedMoney($this->loginedUserInfo['uid'], $year, $month));
        $D->OUT();
//         ajaxReturn($data);
    }

    //吓一跳数据
    function getNextRepay() {
        import_app("Financing.Model.PrjOrderRepayPlanModel");
        $date = $this->_request("date");
        $t = (int)$this->_request("t");

        $uinfo = service("Account/Account")->getLoginedUserInfo();
        $uid = $uinfo['uid'];

        if (!$t) {
            $data = service("Account/Repay")->getNewNextRepay($uid, $date, 0);

        } else {
            $data = service("Account/Repay")->getPreRepay($date);
        }

        $repayDate = isset($data['repay_date']) ? $data['repay_date'] : "";
        $isNull = service("Account/Repay")->isNullRepay($uid, $repayDate);
        $isPreNull = service("Account/Repay")->isNullRepayPre($uid, $repayDate);

        $data['nextWeekData'] = service("Account/Repay")->getNextWeekData($data['repay_date']);
        $data['is_null'] = $isNull;
        $data['is_pre_null'] = $isPreNull;
//         print_r($data);
        ajaxReturn($data);
    }

    // 修改保护问题之前回答以前的问题
    public function checkSQA() {
        BaseModel::newSession('SQA_CHECKED', 0);
        $mdUser = D('Account/User');
        $uid = $this->loginedUserInfo['uid'];
        $user = $mdUser->getByUid($uid);

        // GET
        if ($this->isget()) {
            $this->assign('user', $user);
            $this->display('answerQuestion');
            return;
        }

        // POST
        $sqa_key = I('request.sqa_key');
        $sqa_value = I('request.sqa_vaule');
        $sqa_value = pwd_md5($sqa_value);

        if (!$user['sqa_key'] || !$user['sqa_value']) {
            if (IS_AJAX)
                ajaxReturn('', '未曾设置安全保障问题', 0); else $this->error('未曾设置安全保障问题');
        }

        // 检验正确，跳到重新设置页面
        if ($sqa_key == $user['sqa_key'] && $sqa_value == $user['sqa_value']) {
            BaseModel::newSession('SQA_CHECKED', 1);
            if (IS_AJAX)
                ajaxReturn(U('Account/User/editSafeQuestion')); else $this->redirect(U('Account/User/editSafeQuestion'), '通过');
        }
        if (IS_AJAX)
            ajaxReturn('', '回答错误', 0); else $this->error('回答错误');
    }

    // 设置安全保证问题
    public function editSafeQuestion() {
        $mdUser = D('Account/User');
        $uid = $this->loginedUserInfo['uid'];
        $user = $mdUser->getByUid($uid);

        // GET
        if ($this->isget()) {
            // 跳转url
            if (!BaseModel::newSession('JUMP_URL_SAFE_QUESTION')) {
                $jump_url = I('request.jump_url', $_SERVER['HTTP_REFERER']);
                BaseModel::newSession('JUMP_URL_SAFE_QUESTION', $jump_url);
            }

            $hash_sqa = $user['sqa_key'] && $user['sqa_value'];
            // 如果以前设置过，需要回答老问题
            if ($hash_sqa && !BaseModel::newSession('SQA_CHECKED')) {
                $this->redirect('Account/User/checkSQA');
            }
            $hash_sqa = $user['sqa_key'] && $user['sqa_value'];
            $this->assign('hash_sqa', $hash_sqa);
            $this->assign('sqa_key', $user['sqa_key']);
            $this->assign('sqa_value', $user['sqa_value']);
            $this->assign('user', $user);
            $this->display();
            return;
        }

        // POST
        $sqa_key = I('request.sqa_key');
        $sqa_value = I('request.sqa_vaule');
        $is_ajax = I('request.ajax', IS_AJAX);
        $is_check = I('request.is_check');
        $pwd = I('request.pwd');
        try {
            $hash_sqa = $user['sqa_key'] && $user['sqa_value'];
            if (!$hash_sqa) {
                //20150318 start 用户未设置支付密码，点击确认支付后提示去设置支付密码
                $user_account = M('user_account')->find((int)$user['uid']);
                if ((!$user['is_paypwd_edit']) && (!$user_account['pay_password'])) {
                    ajaxReturn(0, '请先去设置支付密码', 0);
                }//end
                $ret = service("Account/Account")->checkOldPayPwd($uid, $pwd);
                if (!$ret) {
                    throw_exception('支付密码错误');
                }
            }
            // 成功
            $ret = $mdUser->setSQA($uid, $sqa_key, $sqa_value, !!$is_check);
            BaseModel::newSession('SQA_CHECKED', 0);
            $message = $ret[1];
            $this->assign('user', $user);
            $this->assign('message', $message);

            $jump_url = BaseModel::newSession('JUMP_URL_SAFE_QUESTION');
            if (!$jump_url)
                $jump_url = U("/Account/User/index");
            if ($is_ajax) {
                ajaxReturn($jump_url, $message);
            }
            $this->assign('jump_url', $jump_url);
            $this->display('editSafeQuestion2');
            BaseModel::newSession('JUMP_URL_SAFE_QUESTION', NULL);
        } catch (Exception $e) {
            // 失败
            if ($is_ajax) {
                ajaxReturn(U('Account/User/editSafeQuestion'), $e->getMessage(), 0);
            }
            $this->error($e->getMessage(), U('Account/User/editSafeQuestion'));
        }
    }

    function chooseFindPayPwd() {
        $this->display();
    }

    //通过验证已绑定的邮箱+手机动态码找回支付密码
    function findPayPwd2() {
        $uid = $this->loginedUserInfo['uid'];
        $result = M('user')->where(array('uid' => $uid))->find();
        $email = $result['email'];
        $email_auth = $result['is_email_auth'];
        if ($this->isget()) {
            if (!$email || !$email_auth) {
                $this->assign('email_not_auth', 1);
            }
            $email_mark = marskName($email, 4, 7);
            $this->assign('email', $email_mark);
            $this->display();
        } else {
            if (!$email || !$email_auth) {
                ajaxReturn(0, '邮箱没有通过认证!', 0);
            }
            $mobileCode = I('request.code');
            if (!$mobileCode) {
                ajaxReturn(0, '手机验证码为空!', 0);
            }
            $extra = array();
            $uid = $this->loginedUserInfo['uid'];
            if (!service("Validate")->validateResetPayPwdMobileCode($uid, $mobileCode)) {
                ajaxReturn(0, '手机验证码验证失败!', 0);
            }

            if (MyError::hasError()) {
                $this->error(MyError::lastError());
            } else {
                BaseModel::newSession('FINDPAYPWD2', 1, 600);
                service("Account/Account")->sendActiveEmail($uid, $email, $extra, 2);
                $data = array('url' => U("Account/User/emailAuthSuccess?email=" . $email . "&action=2"));
                ajaxReturn($data, '发送邮件成功！', 1);
            }
        }


    }

    //通过验证已绑定的银行卡信息+手机动态码找回支付密码
    function findPayPwd3() {
        $uid = $this->loginedUserInfo['uid'];
        if ($this->isget()) {
            $result = M("fund_account")->field('account_no,bank_name')->where(array('uid' => $uid, 'is_active' => 1))->select();
            if ($result) {
                $this->assign('bank_info', $result);
                $this->assign('not_bind_bank', 0);
            } else {
                $this->assign('not_bind_bank', 1);
            }
            $this->display();
        } else {
            $account_no = I("request.account_no");
            $bank = I("request.bank_name");
            $mobileCode = I("request.code");
            $isBind = M("fund_account")->where(array("uid" => $uid, "account_no" => $account_no, "bank_name" => $bank, "is_active" => 1))->find();
            if (!$isBind) {
                $this->error("绑定的银行信息错误!");
            }
            if (!$mobileCode) {
                $this->error("手机验证码为空!");
            }
            $uid = $this->loginedUserInfo['uid'];
            if (!service("Validate")->validateResetPayPwdMobileCode($uid, $mobileCode)) {
                $this->error("手机验证码验证失败!");
            }
            $key = "forgetPayPwd2";
            BaseModel::newSession($key, 1);
            $this->assign('uname', $this->loginedUserInfo['uname']);
            $this->display("editPaymentView2");
        }

    }

    //实名认证
    function realNameAuth() {
        $this->display();
    }

    //通过安全问题找回支付密码
    function forgetPayPwd1() {
        $accountService = service("Account/Account");
        $uid = $this->loginedUserInfo['uid'];
        $userInfo = $accountService->getByUid($uid);

        //是否有设置问题答案
        $this->issetSqa = ($userInfo['sqa_key'] && $userInfo['sqa_value']) ? true : false;
        $this->assign('uname', $userInfo['uname']);

        // 跳转url
        $jump_url = I('request.jump_url');
        if ($jump_url)
            BaseModel::newSession('JUMP_URL_FORGET_PAY', $jump_url);

        $this->display("editPaymentView");
    }

    function doForgetPayPwd1() {
        $answerKey = $this->_request("sqa_key");
        $answer = $this->_request("sqa_value");
        $code = $this->_request("code");

        if (!$answerKey) {
            ajaxReturn(0, '请选择问题!', 0);
//            $this->error("请选择问题!", U("/Account/User/forgetPayPwd1"));
        }
        if (!$answer) {
            ajaxReturn(0, '问题答案不能为空!', 0);
//            $this->error("问题答案不能为空!", U("/Account/User/forgetPayPwd1"));
        }
        if (!$code) {
            ajaxReturn(0, '手机动态码为空!', 0);
//            $this->error("手机动态码为空!", U("/Account/User/forgetPayPwd1"));
        }

        $accountService = service("Account/Account");

        $uid = $this->loginedUserInfo['uid'];
        $userInfo = $accountService->getByUid($uid);

        $isIdAuth = $userInfo['is_id_auth'];
        if (!$isIdAuth) {
            ajaxReturn(0, '您还没有通过实名认证，不能进行此操作!', 0);
//            $this->error('您还没有通过实名认证，不能进行此操作!', U("/Account/User/forgetPayPwd1"));
        }

        $service = service("Validate");
        if (!$service->validateResetPayPwdMobileCode($uid, $code)) {
            ajaxReturn(0, MyError::lastError(), 0);
//            $this->error(MyError::lastError(), U("/Account/User/forgetPayPwd1"));
        }

        //检查问题答案
        $sqa = $userInfo['sqa_key'];
        $sqaPwd = $userInfo['sqa_value'];

        if (($sqa != $answerKey) || ($sqaPwd != pwd_md5($answer))) {
            ajaxReturn(0, '安全问题答案不匹配!', 0);
//            $this->error("安全问题答案不匹配", U("/Account/User/forgetPayPwd1"));
        }

        $key = "forgetPayPwd2";
        BaseModel::newSession($key, 1);
        $data = array('url' => U('Account/User/forgetPayPwd2'));
        ajaxReturn($data, 1, 1);
    }

    function editPaymentView2() {
        $key = "forgetPayPwd2";
        if (BaseModel::newSession($key)) {
            $this->display('editPaymentView2');
        } else {
            $this->error("非法访问!", U("Account/User/findPayPwd2"));
        }

    }

    function forgetPayPwd2() {
//        $answerKey = $this->_request("sqa_key");
//        $answer = $this->_request("sqa_value");
//        $code = $this->_request("code");

//        if(!$answerKey) {
//            ajaxReturn(0,'请选择问题!',0);
////            $this->error("请选择问题!", U("/Account/User/forgetPayPwd1"));
//        }
//        if (!$answer) {
//            ajaxReturn(0,'问题答案不能为空!',0);
////            $this->error("问题答案不能为空!", U("/Account/User/forgetPayPwd1"));
//        }
//        if (!$code) {
//            ajaxReturn(0,'手机动态码为空!',0);
////            $this->error("手机动态码为空!", U("/Account/User/forgetPayPwd1"));
//        }
//
//        $accountService = service("Account/Account");
//
//        $uid = $this->loginedUserInfo['uid'];
//        $userInfo = $accountService->getByUid($uid);
//
//        $isIdAuth = $userInfo['is_id_auth'];
//        if (!$isIdAuth) {
//            ajaxReturn(0,'您还没有通过实名认证，不能进行此操作!',0);
////            $this->error('您还没有通过实名认证，不能进行此操作!', U("/Account/User/forgetPayPwd1"));
//        }
//
//        $service = service("Validate");
//        if (!$service->validateResetPayPwdMobileCode($uid, $code)) {
//            ajaxReturn(0,MyError::lastError(),0);
////            $this->error(MyError::lastError(), U("/Account/User/forgetPayPwd1"));
//        }
//
//        //检查问题答案
//        $sqa = $userInfo['sqa_key'];
//        $sqaPwd = $userInfo['sqa_value'];
//
//        if (($sqa != $answerKey) || ($sqaPwd != pwd_md5($answer))) {
//            ajaxReturn(0,'安全问题答案不匹配!',0);
////            $this->error("安全问题答案不匹配", U("/Account/User/forgetPayPwd1"));
//        }
//
        $key = "forgetPayPwd2";
        BaseModel::newSession($key, 1);

        $accountService = service("Account/Account");
        $uid = $this->loginedUserInfo['uid'];
        $userInfo = $accountService->getByUid($uid);
        $this->assign('uname', $userInfo['uname']);
        $this->display("editPaymentView2");
    }


    function checkEditPayPwdCode() {
        $code = $this->_request("code");
        if (!$code)
            ajaxReturn(0, '动态码不能为空!', 0);
        $uid = $this->loginedUserInfo['uid'];
        if (!service("Validate")->validateResetPayPwdMobileCode($uid, $code)) {
            ajaxReturn(0, MyError::lastError(), 0);
        }
        ajaxReturn();
    }


    function checkQas() {
        $answerKey = $this->_request("sqa_key");
        $answer = $this->_request("sqa_value");

        if (!$answerKey) {
            ajaxReturn(0, "请选择问题!", 0);
        }
        if (!$answer) {
            ajaxReturn(0, "问题答案不能为空!", 0);
        }

        $uid = $this->loginedUserInfo['uid'];
        $userInfo = service("Account/Account")->getByUid($uid);

        $sqa = $userInfo['sqa_key'];
        $sqaPwd = $userInfo['sqa_value'];

        if (($sqa != $answerKey) || ($sqaPwd != pwd_md5($answer))) {
            ajaxReturn(0, "安全问题答案不匹配", 0);
        }

        ajaxReturn();
    }

    //检查支付密码格式
    function checkPwdFormat() {
        $pwd = $_REQUEST['pwd'];
        if (!$pwd)
            ajaxReturn(0, "支付密码格式错误!", 0);
        $modelObj = D("Account/UserAccount");
        $data['pay_password'] = $pwd;
        if (!$modelObj->create($data)) {
            ajaxReturn(0, $modelObj->getError(), 0);
        }
        $uid = $this->loginedUserInfo['uid'];
        $pwd1 = M("user")->where(array("uid" => $uid))->getField("password");

        if ($pwd1 == pwd_md5($pwd)) {
            ajaxReturn(0, "支付密码不能与登录密码相同!", 0);
        }
        ajaxReturn();
    }

    // 修改支付密码
    function doForgetPayPwd() {
        $key = "forgetPayPwd2";
        $checkTmp = BaseModel::newSession($key);
        if (!$checkTmp) {
            $this->error('非法访问', U("/Account/User/forgetPayPwd1"));
        }

        $accountService = service("Account/Account");
        $uid = $this->loginedUserInfo['uid'];

        // POST
        $pwd1 = $_REQUEST["pwd1"];
        $pwd2 = $_REQUEST["pwd2"];

        if (!$pwd1) {
            ajaxReturn(0, "新密码不能为空!", 0);
//            $this->error("新密码不能为空!", U("/Account/User/forgetPayPwd1"));
        }
        if ($pwd1 != $pwd2) {
            ajaxReturn(0, "两次输入密码不匹配!", 0);
//            $this->error("两次输入密码不匹配!", U("/Account/User/forgetPayPwd1"));
        }

        $pwd = M("user")->where(array("uid" => $uid))->getField("password");

        if ($pwd == pwd_md5($pwd1)) {
            ajaxReturn(0, "支付密码不能与登录密码相同!", 0);
//            $this->error('支付密码不能与登录密码相同!',U("/Account/User/forgetPayPwd1"));
        }

        if (!$accountService->updatePayPwd($uid, $pwd1)) {
            ajaxReturn(0, MyError::lastError(), 0);
//            $this->error(MyError::lastError(), U("/Account/User/forgetPayPwd1"));
        }
        BaseModel::newSession($key, null);

        $jump_url = BaseModel::newSession('JUMP_URL_FORGET_PAY');
        BaseModel::newSession('JUMP_URL_FORGET_PAY', NULL);
        if (!$jump_url)
            $jump_url = U("/Account/User/account");
        ajaxReturn(1, "新密码设置成功", 1);
    }

    //设置自动投标
    function set_auto_bid($paypwd) {
        if (!$paypwd)
            ajaxReturn(0, "支付密码不能为空", 0);
        $user = $this->loginedUserInfo;
        $uid = $user['uid'];
        if (!service("Account/Account")->checkPayPwd($uid, $paypwd)) {
            ajaxReturn(0, "支付密码错误!", 0);
        }
        ajaxReturn();
    }

    //更新用户资料
    // function updateUserInfo(){
    //     $userName = $this->_request("username");
    //     $pwd1 = $this->_request("pwd1");
    //     $pwd2 = $this->_request("pwd2");
    //     $email = $this->_request("email");
    //     if(!$userName) ajaxReturn(0,"用户名不能为空!",0);
    //     if(!$pwd1||!$pwd2)  ajaxReturn(0,"密码不能为空!",0);
    //     if($pwd2!=$pwd1)  ajaxReturn(0,"两次密码不匹配!",0);
    //     //检查
    //     $uid = $this->loginedUserInfo['uid'];

    //     $userInfo = M("user")->find($uid);
    //     if($userInfo['is_set_uname'])   ajaxReturn(0,"已设置用户基本信息，不能重复设置!",0);

    //     $checkdata=array();
    //     $checkdata['uname'] = $userName;
    //     $checkdata['password'] = $pwd1;
    //     if($email) $checkdata['email'] = $email;
    //     $obj = D("Account/User");
    //     if(!$obj->create($checkdata)){
    //         ajaxReturn(0,$obj->getError(),0);
    //     }
    //     $updata=array();
    //     $updata['uid'] = $uid;
    //     $updata['uname'] = $userName;
    //     $updata['password'] = $pwd1;
    //     if(!service("Account/Account")->updateUser($updata)){
    //         ajaxReturn(0,MyError::lastError(),0);
    //     }
    //     //更新支付密码
    //     M("user_account")->where(array("uid"=>$uid))->save(array("pay_password"=>pwd_md5($pwd1)));
    //     M("user")->where(array("uid"=>$uid))->save(array("is_set_uname"=>1,"mtime"=>time()));
    //     service("Account/Account")->login($userName,$pwd1);
    //     //发送邮件
    //     if($email) service("Account/Account")->sendActiveEmail($uid, $email);
    //     $data=array();
    //     $data['uas_email'] = $email ?1:0;
    //     ajaxReturn($data);
    // }

    //email成功页
    function emailsendsuccess($email) {
        if (!$email) {
            $this->error("页面不存在!");
        }
        if (!service("Account/Account")->checkEmail($email)) {
            $this->error(MyError::lastError());
        }

        $loginUrl = service("Account/Account")->getEmailLoginUrl($email);
        $this->assign("loginUrl", $loginUrl);
        $this->assign("email", $email);
        $this->assign("user", $this->loginedUserInfo);
        $this->display("validEmail");
    }

    //设置用户信息
    function setUserInfo() {
        $this->display();
    }

    function registerSem() {
        //从百度推荐进来的保存其keyword参数
        $hmsr = $_REQUEST['hmsr'];
        $key_no = $_REQUEST['keyword'];
        $key_words = cookie("key_words");
//        $condition['key_no']=$key_no;
        if (!$key_words) {
//            $result = M('decode_map')->where($condition)->find();
            setcookie("key_words", $key_no, time() + 86400, '/');  // cookie有效期1天
        }
        $cache_user = 'UserCount';
        $userCount = cache($cache_user);
        if (!$userCount) {
            $userCount = M('user')->count();
            cache($cache_user, $userCount, array('expire' => 1800));
        }
        $investInfo = service('Index/Index')->getCountStats($ttl = 1800, $is_format = TRUE);

        //2015630  新增为投资者赚取收益
        $ret = service('Account/Account')->getProfitAllData();


        //150624去除
        /*$rysInfo = service('Index/Index')->getThreeProjects();
        foreach($rysInfo as $key=>$val){
            $rysInfo[$key]['min_bid_amount'] = humanMoney($val['min_bid_amount'],2,false);
            $rysInfo[$key]['total_year_rate'] = number_format($val['total_year_rate']/10, 2, '.', '');
        }
        $this->assign('rysInfo',$rysInfo);*/
        $this->assign('userInfo', $this->loginedUserInfo);
        $this->assign('userCount', $userCount);
        $this->assign('investCount', $investInfo['allaccount']);
        //2015629 新增加近7天的成交额
        $this->assign('weekCount', $investInfo['weekaccount']);
        //2015630 新增为投资者赚取收益
        $this->assign('profitall', $ret['all_profit']);
        $this->statInfo = service("Index/Common")->getStat();
        $this->assign('hmsr', $hmsr);
        $this->display();
    }


    function registerSemWay() {
        // 从百度推荐进来的保存其keyword参数
        $hmsr = $_REQUEST['hmsr'];
        $key_no = $_REQUEST['keyword'];
        $key_words = cookie('key_words');
        $key = $this->_request('key');
        $channel = $this->_request('channel') ? $this->_request('channel') : '';
        $chanl = $this->_request('chanl') ? $this->_request('chanl') : '';
        $data = cache($key);
        if ($data) {
            $data = json_decode($data, true);
            $source['channel'] = $data['partner'];
            $source['uid'] = $data['cid'];
            if ($source['channel'] == 'fbaba') {
                $source['uid'] = $data['ext'];
            }
            BaseModel::newSession(UserModel::REGISTER_SOURCE_KEY, $source);
        }
        /** @var  $source 判断渠道注册来源，如果是集趣注册，如果地址栏没有标示则要把他的来源给去掉*/
        $jiqu = service('Api/JiQu')->user_source;
        $jiqu_source = BaseModel::newSession(UserModel::REGISTER_SOURCE_KEY);
        if($jiqu_source['channel']==$jiqu){
            BaseModel::newSession(UserModel::REGISTER_SOURCE_KEY,null);
        }
        if($hmsr==$jiqu){
            $jiqu_source['channel'] = $hmsr;
            $jiqu_source['uid'] = $_REQUEST['ji7_uid'];
            BaseModel::newSession(UserModel::REGISTER_SOURCE_KEY, $jiqu_source);
        }
//        $condition['key_no'] = $key_no;
        if (!$key_words) {
//            $result = M('decode_map')->where($condition)->find();
            setcookie('key_words', $key_no, time() + 86400, '/');  // cookie有效期1天
        }
        //这里读取运维提供的数据和首页的数据保持一致
//        $count_user_cache_key = 'UserCount';
//        $count_user = cache($count_user_cache_key);
//        if (!$count_user) {
//            $count_user = M('user')->count();
//            cache($count_user_cache_key, $count_user, array('expire' => 1800));
//        }
        $count_invest = service('Index/Index')->getCountStats($ttl = 1800, $is_format = false);
        $count_profit = service('Account/Account')->getProfitAllData();
        //获取bannner图
        $baner_model = D('Index/Banner');
        $baner_ser = service('Index/Banner');
        if (empty($chanl)) {
            if ($channel == 72) {//registerSemWayTg
                $jpg = $baner_model->getBanner($channel);
                $carousels = $baner_model->getBanners(76);
                $this->assign('semwayurls', $baner_ser->registerSemWayUrl(2));
            } else {//registerSemWay
                $jpg = $baner_model->getBanner(71);
                $carousels = $baner_model->getBanners(73);
                $this->assign('semwayurls', $baner_ser->registerSemWayUrl(1));
            }
        } else {
            if ($chanl == 1) {//registerSemWayTg2
                $jpg = $baner_model->getBanner(106);
                $carousels = $baner_model->getBanners(76);
                $this->assign('semwayurls', $baner_ser->registerSemWayUrl(3));

            } else {//registerSemWayTg3
                $jpg = $baner_model->getBanner(115);
                $carousels = $baner_model->getBanners(76);
                $this->assign('semwayurls', $baner_ser->registerSemWayUrl(4));
            }
        }

        $CorpMaterial_model = D('Application/CorpMaterial');
        $jpg_img = $CorpMaterial_model->parseAttach($jpg['img']);
        $jpg_url = $jpg_img['url'];

        $tmp = array_column($carousels, 'img');
        $carousels_img = array();
        foreach ($tmp as $value) {
            $carousels_img[] = $CorpMaterial_model->parseAttach($value);
        }
        $carousels_urls = array_column($carousels_img, 'url');

        $count_stats = service('Index/Index')->getCountStats(1800, false);//统计数据
        $this->assign('allpeople', $count_stats['allpeople']);

        if ($jpg_url != '')
            $this->assign('jpg_url', $jpg_url);
        if (!empty($carousels_urls[0]))
            $this->assign('carousels_urls', $carousels_urls);


        $this->assign('userInfo', $this->loginedUserInfo);
        $this->assign('userCount', number_format($count_invest['allpeople'] / 10000, 2));
        $this->assign('investCount', number_format(str_replace(',', '', $count_invest['allaccount']) / 100000000, 2));
        $this->assign('weekCount', number_format(str_replace(',', '', $count_invest['weekaccount']) / 100000000, 2)); // 近7天的成交额
        $this->assign('profitall', number_format($count_profit['all_profit'] / 10000, 2)); // 为投资者赚取收益
        $this->assign('hmsr', $hmsr);

        $this->display();
    }

    //极验第一次验证
    public function startCaptchaServlet() {
        import("ORG.Net.GeetestLib");
        //$info = C("API_INFO");
        $keyinfo = C('ACTIVITY');
        $GtSdk = new GeetestLib($keyinfo['CAPTCHA_ID'], $keyinfo['PRIVATE_KEY']);
        $user_id = "apigeetest";
        $status = $GtSdk->pre_process($user_id);
        BaseModel::newSession('sway_gtserver', $status);
        BaseModel::newSession('sway_gtuser_id', $user_id);
        $arr = json_decode($GtSdk->get_response_str(), 'true');
        ajaxReturn($arr, '校验正确', 1);
    }

    /**
     * 港澳台注册引导页面
     */
    function userAuthGangAoTai() {
        $this->userInfo = $this->loginedUserInfo;
        $this->display();
    }

    /**
     * 校验银行卡号格式是否正确
     */
    function checkBankCardNoIsValid() {
        $bankCardNo = I('request.account_no');
        $checkObj = service('Account/Check');
        if ($checkObj->checkBankCardValid($bankCardNo, $this->loginedUserInfo, true)) {
            ajaxReturn(1, '校验正确', 1);
        } else {
            ajaxReturn(0, $checkObj->getError(), 0);
        }
    }

    //新版的更新用户资料-----更新用户名 pc端
    function updateUserInfoNew() {
        $userName = $this->_request("username");
        if (!$userName)
            ajaxReturn(0, "用户名不能为空!", 0);

        //检查
        $uid = $this->loginedUserInfo['uid'];

        $userInfo = M("user")->find($uid);
        if ($userInfo['is_set_uname'])
            ajaxReturn(0, "已设置用户基本信息，不能重复设置!", 0);

        $checkdata = array();
        $checkdata['uname'] = $userName;

        $obj = D("Account/User");
        if (!$obj->checkUname($userName))
            ajaxReturn(0, "用户名不能有特殊空格", 0);

        if (!$obj->create($checkdata)) {
            ajaxReturn(0, $obj->getError(), 0);
        }
        $updata = array();
        $updata['uid'] = $uid;
        $updata['uname'] = $userName;

        if (!service("Account/Account")->updateUser($updata)) {
            ajaxReturn(0, MyError::lastError(), 0);
        }

        M("user")->where(array("uid" => $uid))->save(array("is_set_uname" => 1, "mtime" => time()));
        service("Account/Account")->resetLogin($uid);
        // $this->redirect("Account/User/account");
        ajaxReturn('', '用户名设置成功!');
    }

    //2015.1.15增加机构注册


    function infoStep1() {
        $obj = service("Account/Account");
        $getinfo = $obj->getCorpinfo();
        if ($getinfo['status'] != 1) {
            redirect(U('Account/User/index'));
        }
        $this->assign('getorginfo', $getinfo);
        $this->display('infoStep1');
    }

    function doinfoStep1() {
        // $org_name=trim(I('request.org_name'));//企业名称
        // $busin_license_No=trim(I('request.busin_license_No'));//营业执照号
        // $business_term=I('request.business_term');//长期
        // $business_term_date=I('request.business_term_date');//年月日
        // $province_id=I('request.province_id');//省id
        // $city_id=I('request.city_id');//市id
        // $area_id=I('request.area_id');
        // $province_name=I('request.province_name');//省名
        // $city_name=I('request.city_name');//市名
        // $area_name=I('request.area_name');
        // $mailing_addr=I('request.mailing_addr');//通讯地址
        // $phone=I('request.phone');//电话
        // $org_code=I('request.org_code');//组织机构代码号
        $obj = service("Account/Account");
        $getinfo = $obj->getCorpinfo();
        $data = I('post.');
        foreach (explode(',', 'uid,status,proposer') as $key)
            if (isset($data[$key]))
                unset($data[$key]);

        // $result=M('user_extends_crop')->where('busin_license_No'=>$data['busin_license_No'])->count();
        // if($result>0){
        //    //$this->error('营业执照号已存在'));
        //      ajaxReturn('','营业执照号已存在!');
        // }

        // $m=M('user_extends_crop');
        // if($m->where('busin_license_No'=>$data['busin_license_No'])->count()>0){
        //     ajaxReturn('','营业执照号已存在!');
        // }
        // if($m->where('org_name'=>$data['org_name'])->count()>0){
        //     ajaxReturn('','公司名称已经存在!');
        // }
        // if($m->where('org_code'=>$data['org_code'])->count()>0){
        //     ajaxReturn('','组织代码号已经存在!');
        // }


        $arr = empty($getinfo['step']) ? array() : explode('|', $getinfo['step']);
        $arr[] = 1;
        $arr = array_unique($arr);
        $newStep = implode('|', $arr);
        $data['step'] = $newStep;
        $data['busin_license_No'] = 'Q_' . I('request.busin_license_No');
        $data['business_term_date'] = strtotime(I('request.business_term_date'));
        // $data=I('request.post');
        // $data['step']=1;
        if (I('request.business_term')) {
            $data['business_term'] = '长期';
            $data['business_term_date'] = NULL;
        } else {
            $data['business_term'] = NULL;
            $data['business_term_date'] = strtotime(I('request.business_term_date'));
        }
        $obj = service("Account/Account");
        $obj->setCorpinfo($data);

        $this->redirect(U('Account/User/infoStep2'));

    }

    //数据校验
    // function f(){
    //     $value=I('post.value');
    //     $type=I('post.type');
    //     $m=M('user_extends_crop');
    //     if(empty($value) || empty($type)){
    //         ajaxReturn('','类型或参数值不能为空！');
    //     }
    //     switch ($type) {
    //         case '1':
    //             if($m->where('busin_license_No'=>$value)->count()>0){
    //                 ajaxReturn('','营业执照号已存在!');
    //             }
    //             break;
    //         case '2':
    //             if($m->where('org_name'=>$value)->count()>0){
    //                 ajaxReturn('','公司名称已经存在!');
    //             }
    //             break;
    //         case '3':
    //             if($m->where('org_code'=>$value)->count()>0){
    //                 ajaxReturn('','组织代码号已经存在!');
    //             }
    //             break;

    //         default:
    //             ajaxReturn('','参数类型错误!');
    //             break;
    //     }
    // }


    // function check($busin_license_No,$org_name,$org_code){
    //     $obj = service("Account/Account");
    //     $getinfo=$obj->getCorpinfo();

    //     $result=M('user_extends_crop')->where('busin_license_No'=>$busin_license_No)->count();
    //     if($result>0){
    //        //$this->error('营业执照号已存在'));
    //          ajaxReturn('','营业执照号已存在!');
    //     }
    //     $result1=M('user_extends_crop')->where('org_name'=>$org_name)->count();
    //     if($result1>0){
    //        //$this->error('营业执照号已存在'));
    //          ajaxReturn('','公司名称已经存在!');
    //     }
    //     $result2=M('user_extends_crop')->where('org_code'=>$org_code)->count();
    //     if($result2>0){
    //        //$this->error('营业执照号已存在'));
    //          ajaxReturn('','组织代码号已经存在!');
    //     }

    // }

    function infoStep2() {
        $obj = service("Account/Account");
        $getinfo = $obj->getCorpinfo();
        if ($getinfo['status'] != 1) {
            redirect(U('Account/User/index'));
        }
        $codeList = getCodeItemList("L001");
        $this->assign('materialList', $codeList);
        $this->assign('getorginfo', $getinfo);
        $this->display('infoStep2');
    }

    function doinfostep2() {
        // $legal_representative_land=I('request.legal_representative_land');//法人归属地
        // $legal_representative_id=I('request.legal_representative_id');//法人归属地id
        // $legal_name=I('request.legal_name');//法人名
        // $legal_person_id=I('request.legal_person_id');//法人身份号码
        // $agent_representative_realname=I('request.agent_representative_realname');
        // $agent_representative_personcode=I('request.agent_representative_personcode');
        $obj = service("Account/Account");
        $getinfo = $obj->getCorpinfo();
        $data = I('post.');
        foreach (explode(',', 'uid,status,proposer') as $key)
            if (isset($data[$key]))
                unset($data[$key]);
        $arr = empty($getinfo['step']) ? array() : explode('|', $getinfo['step']);
        $arr[] = 2;
        $arr = array_unique($arr);
        $newStep = implode('|', $arr);
        $data['step'] = $newStep;
        // $data=I('request.post');
        // $data['step']=2;
        $obj = service("Account/Account");
        $obj->setCorpinfo($data);
        $getinfo = $obj->getCorpinfo();
        if (!empty($getinfo)) {
            if ($getinfo['proposer'] == 2) {//如果是代理人,进入第三步,否则是进入第四步
                $this->redirect(U('Account/User/infoStep3'));
            } else {
                $this->redirect(U('Account/User/infoStep4'));
            }
        }
    }

    function infoStep3() {
        $obj = service("Account/Account");
        $getinfo = $obj->getCorpinfo();
        if ($getinfo['status'] != 1) {
            redirect(U('Account/User/index'));
        }
        if ($getinfo['proposer'] != 2) {
            $this->redirect(U('Account/User/infoStep4'));
        }
        $codeList = getCodeItemList("L001");
        $this->assign('materialList', $codeList);
        $this->assign('getorginfo', $getinfo);
        $this->display('infoStep3');
    }

    function doInfoStep3() {
        // $agent_representative_land=I('request.legal_representative_land');
        // $agent_representative_id=I('request.legal_representative_id');
        // $agent_representative_realname=I('request.legal_representative_realname');
        // $agent_representative_personcode=I('request.legal_representative_personcode');
        //$data=I('request.post');
        //$data['step']=3;
        $obj = service("Account/Account");
        $getinfo = $obj->getCorpinfo();
        if ($getinfo['proposer'] != 2) {
            $this->redirect(U('Account/User/infoStep4'));
        }
        $data = I('post.');
        foreach (explode(',', 'uid,status,proposer') as $key)
            if (isset($data[$key]))
                unset($data[$key]);
        $arr = empty($getinfo['step']) ? array() : explode('|', $getinfo['step']);
        $arr[] = 3;
        $arr = array_unique($arr);
        $newStep = implode('|', $arr);
        $data['step'] = $newStep;
        $obj = service("Account/Account");
        $obj->setCorpinfo($data);
        //发邮件给指定人
        $obj->pushMessageToCheck($getinfo['uname'], $getinfo['ctime']);

        $this->redirect(U('Account/User/infoStep4'));
    }

    function infoStep4() {
        $obj = service("Account/Account");
        $getinfo = $obj->getCorpinfo();
        if ($getinfo['status'] != 1) {
            redirect(U('Account/User/index'));
        }
        $this->assign('getorginfo', $getinfo);
        $this->display('infoStep4');
    }

    function doInfoStep4() {
        //$data['step']=4;
        $obj = service("Account/Account");
        $getinfo = $obj->getCorpinfo();
        $data = I('post.');
        foreach (explode(',', 'uid,status,proposer') as $key)
            if (isset($data[$key]))
                unset($data[$key]);
        $arr = empty($getinfo['step']) ? array() : explode('|', $getinfo['step']);
        if (!in_array(1, $arr)) {

            redirect(U('Account/User/infoStep1'), 3, '请填写机构信息');

        } else if (!in_array(2, $arr)) {
            redirect(U('Account/User/infoStep2'), 3, '请填写法人信息');

        } else if ($getinfo['proposer'] == 2 && !in_array(3, $arr)) {

            redirect(U('Account/User/infoStep3'), 3, '请填写代理人信息');

        }
        $arr[] = 4;
        $arr = array_unique($arr);
        $newStep = implode('|', $arr);
        $data['step'] = $newStep;
        $data['status'] = 2;
        $data['aplication_time'] = time();//申请时间
        $obj = service("Account/Account");
        $obj->setCorpinfo($data, 0);
        $obj->pushMessageToCheck($getinfo['uname'], $getinfo['ctime']);
        $this->redirect(U('Account/User/waitCheck'));
    }

    function waitCheck() {
        $obj = service("Account/Account");
        $getinfo = $obj->getCorpinfo();
        if (!empty($getinfo)) {
            if ($getinfo['proposer'] == 1) {//法人

                $this->assign('getorginfo', $getinfo);

            } else {

                $this->assign('getorginfo', $getinfo);

            }

            $this->display('waitCheck');
        } else {
            $this->error("没有数据!");
        }
    }

    /**
     * 机构登录填写信息后撤销认证 发送验证码
     */
    function sendCancelApplyMobileCode($mobile, $authCode) {
        if (!check_verify($authCode)) {
            ajaxReturn('', "验证码错误!", 0);
        }
        $service = service("Account/Validate");

        $userInfo = $this->loginedUserInfo;
        if ($userInfo['mobile'] != $mobile) {
            ajaxReturn('', '与认证手机不匹配', 0);
        }

        $service->sendCancelApplyMobileCode($mobile);
        if (MyError::hasError()) {
            ajaxReturn('', MyError::lastError(), 0);
        } else {
            ajaxReturn('', '动态码发送成功!');
        }
    }

    //检查 机构登录填写信息后撤销认证动态码
    function checkCancelApplyCode($mobile, $code) {
        if (!service("Account/Validate")->validateCancelApplyMobileCode($mobile, $code)) {
            ajaxReturn(0, MyError::lastError(), 0);
        }
        ajaxReturn();
    }

    function cancelApply() {
        $obj = service("Account/Account");
        $getinfo = $obj->getCorpinfo();
        if ($getinfo['status'] != 2) {
            redirect(U('Account/User/index'));
        }
        $userInfo = $this->loginedUserInfo;
        if ($userInfo['mobile'] && $userInfo['is_mobile_auth'] == 1) {
            $mobileView = substr($userInfo['mobile'], 0, 3) . "****" . substr($userInfo['mobile'], -4);
        } else {
            $this->error("没有数据!");
        }
        $this->assign("mobileView", $mobileView);
        $this->display('cancelApply');

    }

    function doCancelApply() {
        $userInfo = $this->loginedUserInfo;
        $mobile = I("request.mobile");
        $code = I("request.code");
        $authCode = I("request.authCode");

        if (empty($userInfo['mobile']) || intval($userInfo['is_mobile_auth']) != 1) {
            $this->error("没有数据!");
        }

        if ($userInfo['mobile'] != $mobile) {
            $this->error("与认证手机不匹配!");

        }

        if (!check_verify($authCode)) {
            $this->error("验证码错误!");
        }

        C('TOKEN_ON', false);
        if (!service("Account/Validate")->validateCancelApplyMobileCode($mobile, $code)) {
            $this->error(MyError::lastError());
        }


        import_addon("libs.Verify.Verify");
        $key = Verify::$seKey;
        BaseModel::newSession($key, null);


        //审核未通过,将数据更新为空
        // $dataup['org_name']='';
        // $dataup['busin_license_No']='';
        // $dataup['business_term_date']='';
        // $dataup['business_term']='';
        // $dataup['province_id']='';
        // $dataup['city_id']='';
        // $dataup['province_name']='';
        // $dataup['city_name']='';
        // $dataup['mailing_addr']='';
        // $dataup['phone_zone']='';
        // $dataup['legal_representative_land']='';
        // $dataup['legal_representative_name']='';
        // $dataup['legal_name']='';
        // $dataup['legal_person_id']='';
        // $dataup['agent_representative_realname']='';
        // $dataup['agent_representative_personcode']='';
        // $dataup['status']=1;//更新状态
        // $dataup['step']='';//步骤清空
        // $dataup['mtime']=time();
        // M('fi_user_extends_corp')->where(array('uid'=>$userInfo['uid']))->save($dataup);

        // $dataupext['stamp_license_copy']='';
        // $dataupext['stamp_organization_code_copy']='';
        // $dataupext['stamp_personcode_front_legal']='';
        // $dataupext['stamp_personcode_back_legal']='';
        // $dataupext['stamp_personcode_front_agent']='';
        // $dataupext['stamp_personcode_back_agent']='';
        //M('fi_user_extends_corpext')->where(array('uid'=>$userInfo['uid']))->save($dataupext);
        $obj = service("Account/Account");
        $result = $obj->editCorpinfo($userInfo['uid']);
        if ($result)
            ajaxReturn('', '更新成功！', 1);

    }

    // function cancleSuccess(){
    //     $uid = I("request.id");
    //     if(!$uid) $this->error("参数错误!");
    //     //$obj = service("Account/Account");
    //     //$info = $obj->getByUid($uid);
    //     if($uid){
    //     $this->success('您已成功撤销本次认证申请','Account/User/index',3);
    //     }
    // }

    //上传图片
    public function uploadMaterial($material_name, $file) {
        $uid = $this->loginedUserInfo['uid'];

        $result = M('user_extends_cropext')->where(array('uid' => $uid))->save(array($material_name => $file));

        $data = array('url' => $file);

        if ($result > 0) {
            ajaxReturn($data, '上传成功！', 1);
        } else {
            ajaxReturn('', '上传失败！', 0);
        }


    }

    // 查看详情
    public function bondDetailed() {
        $this->assign('user', $this->loginedUserInfo);
        $this->display('bondDetailed');
    }

    //审核未通过,设置状态,修改内容
    public function doFailCheck() {
        $data['status'] = 1;//更新状态
        $data['step'] = '';//步骤清空
        $data['mtime'] = time();
        $obj = service("Account/Account");
        $obj->setCorpinfo($data, 0);

        $this->redirect(U('Account/User/infoStep1'));

    }

    public function addCreditorTransfer($prj_id) {
        $uid = $this->loginedUserInfo['uid'];
        $mdCreditorTransfer = D('Financing/CreditorTransfer');
        try {
            $mdCreditorTransfer->addCreditorTransfer($uid, $prj_id);
            $amount = $mdCreditorTransfer->getAmountTransfer($uid, $prj_id);
            ajaxReturn(array('amount' => humanMoney($amount, 2, FALSE),), '申请成功');
        } catch (Exception $e) {
            ajaxReturn('', $e->getMessage(), 0);
        }
    }

    //2015413新版账户
    public function indexNew() {
        $userInfo = $this->loginedUserInfo;
        $this->guarantor_id = $userInfo['guarantor_id'];
        $this->usc_token = $userInfo['usc_token'];

        $obj = service("Account/Account");
        $uid = $userInfo['uid'];

//        $state = service("Account/TyjBonus")->isChangeDefaultPwd($uid);

        $userInfo = $obj->getByUid($uid);
        $this->exec_times[__LINE__] = microtime(true) - $GLOBALS['_beginTime'];
        $ava = $obj->getAvatar($uid);
        $serviceObj = service("Payment/PayAccount");
        $account_info = $serviceObj->accountInfo( $uid );
        $account = $account_info['account'];
        $sumary = $account_info['sumary'];
        $this->exec_times[__LINE__] = microtime(true) - $GLOBALS['_beginTime'];

        $memBankCardInfo = D('Account/UserAccount')->getUserBankCardsInfo($uid);
        $this->exec_times[__LINE__] = microtime(true) - $GLOBALS['_beginTime'];
        if ($defaultCardId = $memBankCardInfo['default']['list'][0]['id']) {
            if (array_key_exists($defaultCardId, $memBankCardInfo['autoable']['list'])) {
                $defaultCardId = false;
            }
        } else {
            $fiestBankCard = $memBankCardInfo['all']['list'][0];
            $fiestBankCardFull = D('Account/UserAccount')->isBankCardInfoFull($uid, array($fiestBankCard['bank'], $fiestBankCard['sub_bank_id'], $fiestBankCard['bank_province'], $fiestBankCard['bank_city']));
            if (!$fiestBankCardFull) {
                $defaultCardId = $memBankCardInfo['all']['list'][0]['id'];
            }
        }
        $this->exec_times[__LINE__] = microtime(true) - $GLOBALS['_beginTime'];
        //余额转移
        $moving = $account_info['moving'];
        //红包资金
        $haobao_user = D('Account/UserBonus');
        $haobao_totalmoney = $haobao_user->getMyBonus($uid);
        $account['haobao_totalmoney'] = humanMoney($haobao_totalmoney, 2, false);
        $this->exec_times[__LINE__] = microtime(true) - $GLOBALS['_beginTime'];
        //我的投资

        //投资中的笔数 $sumary['investing_prj_count']

        //投资中的钱
        //$sumary['investing_prj_money']=humanMoney($sumary['investing_prj_money'],2,false);
        $sumary['investing_prj_money'] = humanMoney($account['buy_freeze_money'], 2, false);
        //待还款的笔数 
        $sumary['wait_repay_count'] = $sumary['willrepay_prj_count'] + $sumary['repayIn_count'];


        //待还款的钱
        $sumary['wait_repay_money'] = humanMoney($sumary['will_principal'], 2, false);
        // $sumary['wait_repay_money']=humanMoney($sumary['will_principal'],2,false);
        //还款完毕的笔数 $sumary['repayed_prj_count']

        //还款完毕的钱 $sumary['repayed_prj_money']

        //资产统计中的总投资笔数totalInvestCount以及总金额totalInvestView
        // $totalInvest =  $sumary['investing_prj_money']+
        //                 $sumary['willrepay_prj_money']+
        //                 $sumary['repayed_prj_money']+
        //                 $sumary['repayIn_money'];
        $totalInvestView = humanMoney(($account['buy_freeze_money'] + $sumary['will_principal'] + $sumary['repayed_prj_money']), 2, false);
        //$totalInvestView = humanMoney($totalInvest,2,false);

        $totalInvestCount = (int)($sumary['investing_prj_count'] + $sumary['willrepay_prj_count'] + $sumary['repayed_prj_count'] + $sumary['repayIn_count']);

        //收益状况 待收收益will_profit_view 已赚收益profit_view(公式:项目还款的收益+已变现的收益-已变现原始收益+使用的红包-手续费)  总收益=待收收益+已赚收益

        //待收收益
        $this->exec_times[__LINE__] = microtime(true) - $GLOBALS['_beginTime'];
        //已赚收益(项目已还收益+(待+已)已变现收益-已还已变现原始收益+已使用红包-速兑通手续费用)
        //$account['profit_view'] = humanMoney($account['profit'],2,false);//201556老版关闭
        //$sumary['fastcash_principa'] = humanMoney($sumary['fastcash_principal'],2,false);//老版变现借款
        $sumary['fastcash_principa'] = humanMoney($sumary['fastcash_will_original_principal'], 2, false);//变现借款
        //变现借款笔数
        $sumary['fastcash_in_count'] = (int)$sumary['fastcash_in_count'];
        //变现借款还款完毕
        $sumary['p_fastcash'] = humanMoney($sumary['fastcash_repayed_original_principal'], 2, false);
        //变现借款还款完毕笔数
        $sumary['fastcash_repay_count'] = (int)$sumary['fastcash_repay_count'];
        //累计变现
        $sumary['total_fastcash'] = humanMoney(($sumary['fastcash_will_original_principal'] + $sumary['fastcash_repayed_original_principal']), 2, false);
        //累计变现笔数
        $sumary['total_fastcash_count'] = (int)($sumary['fastcash_repay_count'] + $sumary['fastcash_in_count']);
        //从云融资平台取数据
        $perdata = service("Account/Account")->getData($uid);

        $this->assign("totalInvestView",$totalInvestView);
        $this->assign("totalInvestCount",$totalInvestCount);
        $this->assign("sumary",$sumary);
        $this->assign("user",$userInfo);
        $this->assign("ava",$ava);
        $this->assign("account",$account);
        $this->assign("moving",$moving);
        $this->assign("perdata",$perdata);
        //安全级别
        $safeLevel = service("Account/Account")->computeSafeLevel($uid);
        $user_status = service("Account/Account")->userStatus($this->loginedUserInfo);
        $this->assign("safeLevel",$safeLevel[0]);
        $this->assign("verifyInfo",$safeLevel[1]);
        $this->assign("verifyLevel",$safeLevel[2]);
        $this->assign("defaultCardId",$defaultCardId);
        $this->assign("userStatus",$user_status);

        //回款提醒
        $objRepay = service("Account/Repay");
        $result = $objRepay->repaymentRemind();
        $this->assign('list', $result);

        $this->exec_times[__LINE__] = microtime(true) - $GLOBALS['_beginTime'];
        //下周还款数据
        $this->nextWeekData = service("Account/Repay")->getNextWeekData();
        $this->exec_times[__LINE__] = microtime(true) - $GLOBALS['_beginTime'];
        $this->nextRepay = service("Account/Repay")->getNewNextRepay($uid, '', 1);
        $repayDate = isset($this->nextRepay['repay_date']) ? $this->nextRepay['repay_date'] : '';
        $this->isNull = service("Account/Repay")->isNullRepay($uid, $repayDate);
        $this->isPreNull = service("Account/Repay")->isNullRepayPre($uid, $repayDate);
        $this->exec_times[__LINE__] = microtime(true) - $GLOBALS['_beginTime'];
        $this->todayHasRepay = service("Account/Repay")->todayHasRepay($uid);
        $this->exec_times[__LINE__] = microtime(true) - $GLOBALS['_beginTime'];
        // 是否设置了安全保障问题
        $hash_sqa = $userInfo['sqa_key'] && $userInfo['sqa_value'];
        $this->assign('hash_sqa', $hash_sqa);

        $period = C('PRIZE_PERIOD_SHJB');
        $start = strtotime($period['start']);
        $end = strtotime($period['end']);
        $date = date('Y-m-d');
        if (strtotime($date) < $start || strtotime($date) > $end) {
            $lottery_num = 0;
        } else {
            import_app("Index.Service.ActivityPoolService");
            $hd1_user_lottery = M('hd1_user_lottery')->where(array("uid" => $this->loginedUserInfo['uid'], "activity_name" => ActivityPoolService::SHJB_KEY))->find();
            $lottery_num = $hd1_user_lottery['lottery_num'];

        }
        $this->exec_times[__LINE__] = microtime(true) - $GLOBALS['_beginTime'];
        $this->assign('cupUrl', U("Index/Act/worldCup"));
        $this->assign('lottery_num', $lottery_num);

        //2015527start 推荐码地址
        $recommend_url = D("Account/Recommend")->getUidTokenUrl($uid);
        $this->assign('recommend_url', $recommend_url);//end

        //201576
        $act_qsqy = M('user')->where(array('uid' => $uid))->find();
        if ($act_qsqy['uid']) {
            $this->assign('is_set_pwd', $act_qsqy['is_set_pwd']);
        }
        //显示专项体验金的banner位置
        $isNewbie = M('user')->where(array('uid' => $uid))->getField('is_newbie');
        $res = service("Account/TyjBonus")->getTyj($uid);
        $myTyj = $res['money'];
        if (!$myTyj) {
            $myTyj = 0;
        }
        $status = 0;
        if ($isNewbie) {
            $status = 1;
            $myStayMoney = service("Account/TyjBonus")->getMyBonus($uid);
            if (!$myStayMoney) {
                $myStayMoney = 0;
            }
        }
        $this->exec_times[__LINE__] = microtime(true) - $GLOBALS['_beginTime'];
        $this->assign('status', $status);             //1未投资用户0已投资用户
        $this->assign('myStayMoney', $myStayMoney);   //我的待收收益,status=1的时候展示
        $this->assign('myTyj', $myTyj);               //我的专享理财金
//        $this->assign('state',$state);               //秋收用户false提醒用户

        //显示满减券加息券的位置
        $MyCoupon_totalmoney = $haobao_user->getMyCouponRestAmounts($uid);
        $myCoupons = humanMoney($MyCoupon_totalmoney, 2, false);
        $myInterest = $haobao_user->getMyVoucherRestAmounts($uid);

//        $ser = service("Account/UserMarketTicket")->init($uid);//$uid当前用户UID
//        $rst =  $ser->getMyCoupons($uid,1);
//        $myInterest= $ser->getInterestNum($uid);
//        $myCoupons = humanMoney($rst,2,false);
        if (!$myCoupons) {
            $myCoupons = 0;
        }

        $list_newbie = $this->recommend();
        $result = D('Payment/PayAccountNew')->showFiveMoneyList($uid);
        $this->assign('list_newbie', $list_newbie);
        $this->assign('income_records', $result);
        //Q4后第一次登陆
        if (!$userInfo['before_loginclient'] && !$_COOKIE['q4_viewed']) {
            setcookie('q4_viewed', $_SERVER['REQUEST_TIME'] + 86400, time() + 86400);
            $this->assign('new_guest', 1);
        }

        $my_prize = service('Account/UserMarketTicket')->getAllCouponsReward($uid);
        $this->assign('myCoupons',$myCoupons);               //我的满减券
        $this->assign('myInterest',$myInterest);            //我的加息
        $this->assign('myLcj',$my_prize['lcj_money']);     //我的理财金

        $this->assign('myCoupons', $myCoupons);               //我的满减券
        $this->assign('myInterest', $myInterest);            //我的加息

        $importantNews = service('Account/Account')->showImportantNews();
        $this->assign('importantNews', $importantNews);
        //机构用户提供完善资料入口
//        if($userInfo['uid_type'] == 2){
//            $inst_user_info=service("Account/Account")->getCorpinfo();
//            if($inst_user_info['status']){
//                $this->assign('inst_user_status',$inst_user_info['status']);
//            }
//        }

        if (in_array($userInfo['uid_type'], array(1, 3))) {
            $this->display('indexNew');
        }

    }

    public function genLaokUrl() {
        $loginUserInfo = service("Account/Account")->getLoginedUserInfo();
        //直接拼接url
        $laokUrl = self::LAOK_URL;
        $sign = md5($loginUserInfo['uname'] . self::LAOK_TYPE . self::LAOK_KEY);
        $laokUrl .= '?account=' . $loginUserInfo['uname'] . '&type=' . self::LAOK_TYPE . '&sign=' . $sign;
        return $laokUrl;
    }


    public function unbindEmailView() {
        $this->display();
    }

    //解绑邮箱
    public function unbindEmail($email) {
        $uid = $this->loginedUserInfo['uid'];
        if (!$email) {
            ajaxReturn(0, "邮箱地址不能为空", 0);
        }
        $result = M('user')->where(array('uid' => $uid))->find();
        $is_email_auth = $result['is_email_auth'];
        if ($is_email_auth != 1) {
            ajaxReturn(0, "邮箱还未通过绑定", 0);
        }
        if ($result['email'] != $email) {
            ajaxReturn(0, "邮箱地址不正确", 0);
        }
        $extra = array();
        service("Account/Account")->sendActiveEmail($uid, $email, $extra, 1);
        if (MyError::hasError()) {
            ajaxReturn(0, MyError::lastError(), 0);
        } else {
            ajaxReturn("/Account/User/emailAuthSuccess?email=" . $email . '&action=1', '发送邮件成功', 1);
        }

    }

    /**
     * 解绑邮箱认证
     * @param unknown $token
     */
    function unbindEmailAuth($token) {
        if (!$token) {
            $this->error("参数错误!");
        }
        $userObj = service("Account/Account");
        $data = array();
        if ($userObj->unbindEmailActive($token, $data)) {
            //更新
            $userObj->closeEmailLog($token, $userObj::UNBIND_EMAIL);

            $this->success('邮箱解绑成功！', U('Account/User/index'));
        } else {
            $this->error(MyError::lastError());
        }
    }

    /**
     * 找回支付密码2验证
     * @param unknown $token
     */
    function findPayPwd2Auth($token) {
        if (!$token) {
            $this->error("参数错误!");
        }
        $userObj = service("Account/Account");
        if ($userObj->findPayPwd2Active($token)) {
            //更新
            $userObj->closeEmailLog($token, $userObj::FIND_PAY_PWD2_ACTIVE_EMAIL);
            $key = "forgetPayPwd2";
            BaseModel::newSession($key, 1);
            $this->display("editPaymentView2");

        } else {
            $this->error(MyError::lastError());
        }
    }

    function findPayPwdSuccess() {
        $this->display();
    }

    //用户中心推荐标和活动
    function recommend() {

        $userInfo = M("user")->find($this->loginedUserInfo['uid']);
        if ($userInfo['is_newbie']) {
            $is_newbie = 1;
        }
        if ($is_newbie) {
            $ret_prj = service('Index/Index')->getIndexRecommend(1);
            $prj_arr = $ret_prj;
            $ret_act = service('Index/Index')->getRecommendAct(3);
        } else {
            $ret_prj = service('Index/Index')->getIndexRecommend();
            $prj_arr = $ret_prj;
            $ret_act = service('Index/Index')->getRecommendAct(1);
        }
        if ($ret_act) {
            $data = array('prj' => $prj_arr, 'act' => $ret_act);
        } else {
            $data = array('prj' => $prj_arr);
        }
        return $data;
    }

    //检查支付密码
    function checkPayPwd($paypwd) {
        if (!$paypwd)
            ajaxReturn(0, "支付密码不能为空", 0);
        $uid = $this->loginedUserInfo['uid'];
        if (!service("Account/Account")->checkPayPwd($uid, $paypwd)) {
            ajaxReturn(0, "支付密码错误!", 0);
        } else {
            ajaxReturn(1, 1, 1);
        }
    }

    //重发通过邮件找回支付密码邮件
    function resendPayPwdEmail() {
        $uid = $this->loginedUserInfo['uid'];
        $result = M('user')->where(array('uid' => $uid))->find();
        $email = $result['email'];
        $email_auth = $result['is_email_auth'];
        if (!$email || !$email_auth) {
            ajaxReturn(0, "邮箱没有通过认证!", 0);
        }
        $lastSendTime = (int)BaseModel::newSession("LAST_SEND_ACTIVE_EMAIL");
        if (((time() - $lastSendTime) < 60)) {
            ajaxReturn(0, "60秒后才能重发邮件。", 0);
        }
        $extra = array();
        $uid = $this->loginedUserInfo['uid'];
        if (!BaseModel::newSession('FINDPAYPWD2')) {
            ajaxReturn(0, "手机验证码验证失败!", 0);
//            $this->redirect(U('Account/User/findPayPwd2'));
        }

        if (MyError::hasError()) {
            ajaxReturn(0, MyError::lastError(), 0);
        } else {
            service("Account/Account")->sendActiveEmail($uid, $email, $extra, 2);
            ajaxReturn(1, "发送邮件成功!", 1);
        }
    }

    /**重新发送解绑邮箱
     * @param $email
     */
    function resendUnbindEmail($email) {
        $lastSendTime = (int)BaseModel::newSession("LAST_SEND_ACTIVE_EMAIL");
        if (((time() - $lastSendTime) < 60)) {
            ajaxReturn(0, "60秒后才能重发邮件。", 0);
        }
        $this->unbindEmail($email);
    }

    //2016.2.29 默认是空,设置支付密码
    public function setPayPassword() {
        $uid = $this->loginedUserInfo['uid'];

        // GET
        if ($this->isget()) {
            $this->display('setPayPassword');
            return;
        }
        //POST
        $password = I('request.password');
        $password_repeat = I('request.password_repeat');

        if (!$password) {
            ajaxReturn(0, "请输入密码!", 0);
        }

        if (!preg_match('/^\d{6}$/', $password)) {
            ajaxReturn(0, "支付密码为6位数字!", 0);
        }

        if (!$password_repeat) {
            ajaxReturn(0, '请输入确认密码!', 0);
        }

        if ($password != $password_repeat) {
            ajaxReturn(0, '两次密码不匹配!', 0);
        }

        $mdUserAccount = M('user_account');
        $user_account = $mdUserAccount->where(array('uid' => $uid))->find();
        if ($user_account['pay_password'] && $user_account['pay_password_mobile']) {
            ajaxReturn(0, '您已设置过支付密码, 不能再设置', 0);
        }


        $result = service('Account/Account')->setPayPassword($uid, $password);
        if ($result)
            ajaxReturn(1, "支付密码设置成功!", 1);

    }
    //开通浙商存管户
    public function openAccount(){
        $userInfo = $this->loginedUserInfo;
        $ret = service("Account/Account")->checkCgAccount();
//        //不符合条件的提示信息
        if(!$ret){
            $this->error('不在白名单或者已经开户');
        }
        // GET 显示鑫合汇端开户表单
        if ($this->isget()) {
            //判断是否是实名认证过
            if($userInfo['is_id_auth']){
                $this->assign('real_name',$userInfo['real_name']);
                $this->assign('person_id',$userInfo['person_id']);
            }
            //浙商银行卡限额列表
            $bank_list = service('Mobile2/Bank')->getZsBankLimitList();
            $this->assign('bank_list',$bank_list);
            $this->display();
            return;
        }
        //POST 验证开户信息
        $inputs = array();
        $inputs['real_name'] = I('post.real_name');
        if(!$inputs['real_name']){
            ajaxReturn(0,'姓名不能为空!',0);
        }
        $inputs['person_id'] = I('post.person_id');
        if(!$inputs['person_id']){
            ajaxReturn(0,'身份证号不能为空!',0);
        }
        $inputs['mobile'] = I('post.mobile');
        if(!$inputs['mobile']){
            ajaxReturn(0,'手机号不能为空!',0);
        }
        $key = "openAccount";
        BaseModel::newSession($key, 1);
        ajaxReturn(1,'ok',1);

    }
    //提交表单信息到熊水林接口
    public function submitAccount(){
        if(!BaseModel::newSession('openAccount')){
            ajaxReturn(0,'验证失败,请重新输入开户信息!',0);
        }
        $inputs = array();
        $inputs['real_name'] = I('post.real_name');
        if(!$inputs['real_name']){
            ajaxReturn(0,'姓名不能为空!',0);
        }
        $inputs['person_id'] = I('post.person_id');
        if(!$inputs['person_id']){
            ajaxReturn(0,'身份证号不能为空!',0);
        }
        $inputs['mobile'] = I('post.mobile');
        if(!$inputs['mobile']){
            ajaxReturn(0,'手机号不能为空!',0);
        }
        if(!preg_match('/[\x{4E00}-\x{9FA5}]{2,5}(?:·[\x{4E00}-\x{9FA5}]{2,5})*$/u', $inputs['real_name'])){
            ajaxReturn(0,'姓名格式不正确!',0);
        };
        $result = checkMobile($inputs['mobile']);
        if(!$result){
            ajaxReturn(0,"手机号码不正确!",0);
        }
        $result = service("Account/Account")->checkPersonId($inputs['person_id']);
        if(!$result){
            ajaxReturn(0,MyError::lastError(),0);
        }
        $result = service("Account/Account")->checkIdCardUnique($inputs['person_id']);
        if(!$result){
            ajaxReturn(0,MyError::lastError(),0);
        }
        $userInfo = $this->loginedUserInfo;
        if (isset($_COOKIE['PHPSESSID'])) {
            session_id($_COOKIE['PHPSESSID']);
        }
        $session_id = session_id();
        S('zs_'.$userInfo['uid'], $session_id,3600);
        $inputs['uid'] = $userInfo['uid'];
        service('Payment/Payment');
        $channel = PaymentService::TYPE_ZHESHANG;//浙商银行
        $payment = service('Payment/Payment');
        $zheShang = $payment->init($channel);
        $ret = $zheShang->payment->openCard($inputs);
        if(!$ret['boolen']){
            ajaxReturn(0,$ret['message'],0);
        }
        echo json_encode($ret);

    }
    //ajax验证手机号
    public function ajaxCheckMobile(){
        $mobile = I('request.mobile');
        if(!$mobile) ajaxReturn(0,"手机号码不能为空!",0);
        $result = checkMobile($mobile);
        if(!$result) ajaxReturn(0,"手机号码格式不正确!",0);
        ajaxReturn(1,'此号码有效!',1);
    }
    //ajax变更手机号验证
    public function ajaxCheckBankMobile(){
        $userInfo = $this->loginedUserInfo;
        $mobile = I('request.mobile');
        if(!$mobile) ajaxReturn(0,"手机号码不能为空!",0);
        if($mobile == $userInfo['zs_bank_mobile']) ajaxReturn(0,"新手机号不得与当前手机号相同!",0);
        $result = checkMobile($mobile);
        if(!$result) ajaxReturn(0,"手机号码格式不正确!",0);
        ajaxReturn(1,'此号码有效!',1);
    }
    //ajax验证姓名
    public function ajaxCheckName(){
        $real_name = I('request.real_name');
        if(!$real_name) ajaxReturn(0,"姓名不能为空!",0);
        if(!preg_match('/[\x{4E00}-\x{9FA5}]{2,5}(?:·[\x{4E00}-\x{9FA5}]{2,5})*$/u', $real_name)){
            ajaxReturn(0,'姓名格式不正确!',0);
        };
        ajaxReturn(1,'OK!',1);
    }
    //ajax验证身份证号
    public function ajaxCheckIdCard(){
        $person_id = I('request.person_id');
        if(!$person_id) ajaxReturn(0,"身份证号不能为空!",0);
        $result = service("Account/Account")->checkPersonId($person_id);
        if(!$result){
            ajaxReturn(0,MyError::lastError(),0);
        }
        $result = service("Account/Account")->checkIdCardUnique($person_id);
        if(!$result){
            ajaxReturn(0,MyError::lastError(),0);
        }
        ajaxReturn(1,'OK!',1);
    }

    //java回调开户数据
    public function callBackNotify(){
        service('Payment/Payment');
        $channel = PaymentService::TYPE_ZHESHANG;//浙商银行
        $payment = service('Payment/Payment');
        $zheShang = $payment->init($channel);
        $param = file_get_contents("php://input");
        if(!$param){
            throw_exception('非法操作');
        }
        if(C('APP_STATUS') != 'product'){
            \Addons\Libs\Log\Logger::info('调取熊水林开户接口返回信息:', "Account/User/callBackNotify", $param);
        }
        $param = json_decode($param,true);
        $zheShang->payment->openAccountNotify($param);

    }


    //前端查詢是否开户成功
    public function queryResponse(){
        $userInfo = $this->loginedUserInfo;
        $param = array();
        $param['uid'] = $userInfo['uid'];
        $param['person_id'] = $userInfo['person_id'];
        $result = M('user')->where(array('uid'=>$param['uid']))->find();
        $status = $result['zs_status'];
//        余额转移暂时不做
//        $serviceObj = service("Payment/PayAccount");
//        $account = $serviceObj->getBaseInfo($userInfo['uid']);
//        $balance = ($account['amount'] + $account['reward_money'])/100;
        if($status == self::OPENING_ACCOUNT){
            ajaxReturn($status,'开户处理中',0);
        }elseif($status == self::OPENED_ACCOUNT){
//            $y=date("Y",time());
//            $m=date("m",time());
//            $d=date("d",time());
//            //00:00至10：59
//            $start_morning_time = mktime(0, 0, 0, $m, $d ,$y);
//            $end_morning_time = mktime(10, 59, 59, $m, $d ,$y);
//            //11:00至16:59
//            $start_noon_time = mktime(11, 0, 0, $m, $d ,$y);
//            $end_noon_time = mktime(16, 59, 59, $m, $d ,$y);
//            //17:00至23:59
//            $start_night_time = mktime(17, 0, 0, $m, $d ,$y);
//            $end_night_time = mktime(23, 59, 59, $m, $d ,$y);
//            $time = time();
//            if($time >= $start_morning_time && $time <= $end_morning_time) {
//                $time_tips = '预计今日11:30前到账';
//            }elseif($time >= $start_noon_time && $time <= $end_noon_time){
//                $time_tips = '预计今日17:30前到账';
//            }else{
//                $time_tips = '预计次日11:30前到账';
//            }
//
//            $message = $balance > 0 ?  '由于您的个人账户已升级为浙商存管户，需要将您账户余额'.$balance.'元转移到浙商银行，'.$time_tips.'。' : '';
            if($userInfo['zs_status'] != self::OPENED_ACCOUNT){
                //存管数据入session
                $session_result = BaseModel::newSession('USER_LOGIN_KEY');
                if($session_result){
                    service("Account/Account")->resetLogin($userInfo['uid']);
                }
            }
            ajaxReturn($status,'',1);
        }

    }
    //查询用户是否开存管户
    public function need_to_open_account(){
        $result = service("Account/Account")->checkCgAccount();
        //读取banner,banner位置82
        if($result){
            $ret = service("Index/Index")->getBannerFromJava(82);
            ajaxReturn($ret['img_url'],'ok','1');
        }

        ajaxReturn($result,'0','0');
    }

    /**
     * 发送切换免动态码投资
     */
    public function sendSwitchAutoInvest()
    {
        $uid = $this->loginedUserInfo['uid'];
        if($this->loginedUserInfo['zs_status'] != '2'){
            ajaxReturn(0,'请先去开户',0);
        }
        $mobile = marskName($this->loginedUserInfo['zs_bank_mobile'],3,4);
        try {
            /* @var $zs_service ZheShangService */
            $zs_service = service('Financing/ZheShang');
            $zs_service->sendMobileCode($uid, $this->loginedUserInfo['zs_bank_mobile'], 'auto_invest_set');
            ajaxReturn(1, '短信验证码已发送至<br />'.$mobile,1);
        } catch (Exception $e) {
            ajaxReturn('', $e->getMessage(), 0);
        }
    }

    /**
     * 切换免动态码投资
     */
    public function doSwitchAutoInvest()
    {
        if($this->loginedUserInfo['zs_status'] != '2'){
            ajaxReturn(0,'请先去开户',0);
        }
        $bind_serial_no = $this->loginedUserInfo['zs_bind_serial_no'];
        $verify_code = I('request.verify_code', '', 'trim');
        $flag = (int) I('request.flag');
        $flag = !$flag;
        $post_params = [
            'serviceId' => 'czb_autobidSet',
            'bindSerialNo' => $bind_serial_no,
            'verifyCode' => $verify_code,
            'flag' => $flag ? 1 : 0,
            'remark' => $flag ? 'open' : 'close',
            'bizSys' => 'xinhehui',
            'async' => 'false',
            'hostIp' => get_client_ip(),
            'dataId'=> $this->loginedUserInfo['person_id'],
        ];

        $http_header = czb_header_sign( $post_params );
        $result = postData(C('PAYMENT')['zheshang']['config']['getway'], http_build_query($post_params), $http_header);
        $result = json_decode($result, true);
        if($result['code'] == '0'){
            service("Account/Account")->modifyUserAutoInvestCode($flag);
        }
        ajaxReturn($result['message'],'网络异常请重试',0);
    }

    /**
     * 获取用户注册的手机号和银行绑定的手机号
     * @return array
     */
    public function queryMobile(){
        $result = service("Account/Account")->queryMobile();
        ajaxReturn($result,'ok','1');
    }
    // 关闭短信提醒页
    function closeMsg(){
        $this->display();
    }
    //修改浙商手机号,变更主账户发送验证码
    function sendMobileCode($remark = 'modify_mobile'){
        try {
            $userInfo = $this->loginedUserInfo;
            $uid = $userInfo['uid'];
            $obj = service("Account/Account");
            $userInfo = $obj->getByUid($uid);
            if($userInfo['zs_status'] != 2) ajaxReturn(0,"请开通存管户!",0);
            $zs_service = service('Financing/ZheShang');
            $sendType = $remark;
            $business =  I('request.business');  // 修改手机号business 为 新的手机号码;变更银行卡号 为 新的银行卡号
            $extension = '';
            if($sendType == 'modify_mobile'){
                $extension = $userInfo['zs_bind_serial_no'];
                $mobile = $business;
                if(!$mobile) ajaxReturn(0,"手机号不能为空!",0);
                $result = checkMobile($mobile);
                if(!$result) ajaxReturn(0,"手机号码不正确!",0);
                $zs_service->sendMobileCode($uid , $mobile, $sendType, $extension);
            }elseif($sendType == 'modify_account'){
                $bankInfo = M('fund_account')->where(array('uid'=>$userInfo['uid'],'zs_bankck'=>1))->find();
                if(!$bankInfo) ajaxReturn(0,"请开通存管户!",0);
                $extension = $business;
                if(!preg_match('/\d{16,20}/',  $extension)) ajaxReturn(0,"请输入正确的银行卡号!",0);
                $zs_service->sendMobileCode($uid , $userInfo['zs_bank_mobile'], $sendType, $extension);
            }else{
                $zs_service->sendMobileCode($uid , $userInfo['zs_bank_mobile'], $sendType, $extension);
            }
            ajaxReturn(1,'ok',1);
        } catch (Exception $e) {
            ajaxReturn('', $e->getMessage(), 0);
        }
    }
    //修改浙商手机号
    function editBankMobile(){
        $mobile = I('request.mobile');
        $verifyCode = I('request.verifyCode');
        if(!$mobile) ajaxReturn(0,"手机号不能为空!",0);
        $result = checkMobile($mobile);
        if(!$result) ajaxReturn(0,"手机号码不正确!",0);
        if(!$verifyCode) ajaxReturn(0,"验证码不能为空!",0);
        $userInfo = $this->loginedUserInfo;
        $uid = $userInfo['uid'];
        $obj = service("Account/Account");
        $userInfo = $obj->getByUid($uid);
        if($userInfo['zs_status'] != 2) ajaxReturn(0,"请开通存管户!",0);
        $params = array();
        $params['bindSerialNo'] = $userInfo['zs_bind_serial_no'];
        $params['mobile'] = $mobile;
        $params['oldMobile'] = $userInfo['zs_bank_mobile'];
        $params['verifyCode'] = $verifyCode;
        service('Payment/Payment');
        $channel = PaymentService::TYPE_ZHESHANG;//浙商银行
        $payment = service('Payment/Payment');
        $zheShang = $payment->init($channel);
        $ret = $zheShang->payment->updateMobile($params);
        if($ret['boolen'] == '1'){
            $res = service("Account/Account")->editBankMobile($uid,$mobile);
            if($res) D("Account/User")->addUserloginsession('zs_bank_mobile',$mobile);
        }
        echo json_encode($ret);
    }
    //修改存管户银行预留手机号
    function editBankMobileView(){
        $userInfo = $this->loginedUserInfo;
        $zs_bank_mobile = $userInfo['zs_bank_mobile'];
        $this->assign('zs_bank_mobile',moblie_view($zs_bank_mobile));
        $this->display();
    }
    //修改浙商银行卡显示页面
    function editZsBankView(){
        $userInfo = $this->loginedUserInfo;
        $uid = $userInfo['uid'];
        //银行预留手机号
        $this->assign('zs_bank_mobile',moblie_view($userInfo['zs_bank_mobile']));
        //浙商绑定的银行卡信息
        $result = M('fund_account')->where(array('uid'=>$uid,'zs_bankck'=>1))->find();
        //银行卡号
        $this->assign('zs_bind_bank_card',$result['zs_bind_bank_card']);
        //银行标示
        $this->assign('bank',$result['bank']);
        //银行名称
        $this->assign('bank_name',$result['bank_name']);
        //打星号的实名显示
        $this->assign('zs_real_name', '*' .mb_substr($userInfo['real_name'], 1, strlen($userInfo['real_name']), 'utf-8') );
        //银行卡变更状态:1 表示：变更中; 0 表示：最新的
        $redis = new RedisSimply('bankCardStatus');
        $key = $userInfo['uid'].'_'.$result['zs_bind_bank_card'];
        $bank_status = $redis->get($key) ? 1 : 0;
        $this->assign('bank_status',$bank_status);
        $this->display();
    }
    //修改浙商绑定的银行卡
    function editZsBankCard(){
        $userInfo = $this->loginedUserInfo;
        $verifyCode = I('request.verifyCode');
        $newCard = I('request.card_no');
        $sub_bank = I('request.sub_bank');
        if(!$verifyCode) ajaxReturn(0,"验证码不能为空!",0);
        if(!preg_match('/\d{16,20}/',  $newCard)) ajaxReturn(0,"请输入正确的银行卡号!",0);
        if(!$sub_bank) ajaxReturn(0,"支行信息不能为空!",0);
        $bankInfo = M('fund_account')->where(array('uid'=>$userInfo['uid'],'zs_bankck'=>1))->find();
        $oldCard = $bankInfo['zs_bind_bank_card'];
        $pregCard = marskName($newCard,6,4);
        if($oldCard == $pregCard ) ajaxReturn(0,"请绑定新的银行卡号!",0);
        $bankData = M('data_bank')->where(array('short_name'=>$sub_bank))->find();
        if(!$bankData) ajaxReturn(0,"没有找到该支行的银行联号!",0);
        $params = array();
        $params['bindSerialNo'] = $userInfo['zs_bind_serial_no'];
        $params['bindBankCard'] = $newCard ;
        $params['branchNo'] = $bankData['cnaps'];
        $params['verifyCode'] = $verifyCode;
        service('Payment/Payment');
        $channel = PaymentService::TYPE_ZHESHANG;//浙商银行
        $payment = service('Payment/Payment');
        $zheShang = $payment->init($channel);
        $ret = $zheShang->payment->editBankCard($params);
        if($ret['boolen'] == '1'){
            import_addon("libs.Cache.RedisSimply");
            $redis = new RedisSimply('bankCardStatus',864000);
            $key = $userInfo['uid'].'_'.$oldCard;
            $redis->set($key,1);
        }
        echo json_encode($ret);
    }
    //浙商修改银行卡回调
    function bankCardNotify(){
        service('Payment/Payment');
        $channel = PaymentService::TYPE_ZHESHANG;//浙商银行
        $payment = service('Payment/Payment');
        $zheShang = $payment->init($channel);
        $param = file_get_contents("php://input");
        if(!$param){
            throw_exception('非法操作');
        }
        if(C('APP_STATUS') != 'product'){
            \Addons\Libs\Log\Logger::info('调取熊水林修改银行卡接口返回信息:', "Account/User/bankCardNotify", $param);
        }
        $param = json_decode($param,true);
        $zheShang->payment->bankCardToUpg($param);
    }

    /**
     * 两个月前注册并且无新密码，提示修改密码接口
     * @access public
     * @return boolen：1-成功 0-失败 message：消息
     *
     */
    public function isNewPwd() {
        import("libs/Cache/RedisSimply", ADDON_PATH);
        $key = "tishi";
        $redis = new RedisSimply($key);
        $value = (int)$redis->get($this->loginedUserInfo['uid']);
        $now = time();
        if ($value) {
            $tmp = $now - $value;
            if ($tmp <= 5184000) {
                $data = array('status'=>0,'content' =>'','replace'=>"");
                ajaxReturn($data,"");
            }
        }
        $ctime = (int)$this->loginedUserInfo['ctime'];
        $difference = $now - $ctime;
        if (array_key_exists('newpassword', $this->loginedUserInfo)) {
            if ($difference >= 5184000) {
                $data = array('status'=>1, 'content' => '小鑫发现您已经超过2个月没有修改登录密码了，为了您的账户安全，建议您经常更换哦~', 'replace'=>"2个月");
                $redis->set($this->loginedUserInfo['uid'], $now);
            }else {
                $data = array('status'=>0,'content' =>'','replace'=>"");
            }
        }else {
            $data = array('status'=>0,'content' =>'','replace'=>"");
        }
        ajaxReturn($data,"");

    }
}

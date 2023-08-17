<?php
/**
 * 购买队列相关服务

 * Class FastCashService
 */

class PrjBuyQueueService extends BaseService
{
    const QUEUE_PRJ_BUY_NAME = "prj_buy_queue_lists";
    const BUY_WAIT_QUEUE = 'buy_wait_queue';

    function __construct() {
        parent::__construct();
        $this->setBaseApiKey();
    }

    public function putInQueue($queueid,$prj_id,$uid,$money,$paypwd,$xprjIdAccess,$params){//放入队列中
        if($this->checkBuyQueueNum($uid, $queueid)){//加入队列
            try {
                $queue_key = $prj_id.$uid.microtime(true);
                $queue_data = array(
                    'prjbuyqueueid'=>$queueid,
                    'uid' => $uid,
                    'prjid'=>$prj_id,
                    'money'=>$money,
                    'paypwd'=>$paypwd,
                    'xprjIdAccess'=>$xprjIdAccess,
                    'params'=>$params,
                );
                $ret = queue(self::QUEUE_PRJ_BUY_NAME, $queue_key, $queue_data);
                if (!$ret) {
                    $this->decrQueueNum($uid);
                    throw_exception(self::QUEUE_PRJ_BUY_NAME . "入队列异常");
                }
                $this->remarkInvestor($uid, $prj_id, $queueid);
                return $ret;
            } catch (Exception $e) {
                throw_exception($e->getMessage());
                return false;
            }
        }else{//队列已满
            return false;
        }

    }

    //判断队列数量，及加1
    public function checkBuyQueueNum($uid, $uuid){
        try{
            $buyQueueLine = D("Public/SystemConfig")->getValueByKey("buyQueueLine",true);//队列长度

            $redis_instance = \Addons\Libs\Cache\Redis::getInstance('default');
            $nums = $redis_instance->hLen(self::BUY_WAIT_QUEUE);

            if($redis_instance->hExists(self::BUY_WAIT_QUEUE, $uid)){//如果当前用户已经在队列里，不让进队列，一个用户同时只能进一次队列
                return false;
            }
            if ($nums < $buyQueueLine) {
                $redis_instance->hSet(self::BUY_WAIT_QUEUE, $uid, $uuid);
                return true;
            }
            return false;
        }catch(Exception $e){
            throw_exception($e->getMessage());
            //MyError::add($e->getMessage());
            return false;
        }
    }

    //队列数量减少
    public function decrQueueNum($uid, $step = 1){
        try{
            $redis_instance = \Addons\Libs\Cache\Redis::getInstance('default');
            $nums = $redis_instance->hLen(self::BUY_WAIT_QUEUE);
            if ($nums) {
                $redis_instance->hDel(self::BUY_WAIT_QUEUE, $uid);
                return true;
            }
            return false;
        }catch(Exception $e){
            throw_exception($e->getMessage());
            //MyError::add($e->getMessage());
            return false;
        }
    }

    //是否已经满标
    public function getIsNoRemain($prj_id){
        if($prj_id){
            $remaining_cachekey = "buyremain_".$prj_id;//投标是剩余投标金额存在cache里的key
            $before_result = cache($remaining_cachekey);
            if($before_result == 1){
                return true;
            }
            return false;
        }
        return false;

    }

    /**
     * 队列入口 投标第一步 负责产生订单、生成流水、划扣项目金额
     * @param $uid
     * @param $prj_id
     * @param $money
     * @param $uuid
     * @param array $buy_params
     * @return bool
     */
    public function buyFirstStep($uid, $prj_id, $money, $uuid, $buy_params = [])
    {
        /* @var $project_service ProjectService */
        $project_service = service('Financing/Project');
        try {
            $result = $this->doBuyFirstStep($uid, $prj_id, $money, $buy_params);
            if($result['bool']){
                //设置满标
                $project_info = M('prj')
                    ->field('remaining_amount, matched_amount, prj_type')
                    ->where(['id' => $prj_id])
                    ->find();
                $project_service->setPrjFull(
                    $prj_id,
                    $project_info['remaining_amount'],
                    $project_info['matched_amount'],
                    $project_info['prj_type']
                );
                //设置用户投资成功 前段轮询获取
                $this->setInvestorSuccess($uid, $prj_id, $result['prj_order_id'], $uuid);
            }else{
                throw_exception($result['msg']);
            }

        } catch (Exception $e) {
            //设置用户投资失败 前段轮询获取
            $this->setInvestorFail($uid, $prj_id, $uuid,$e->getMessage());
            throw_exception($e->getMessage());
        }

        return true;
    }

    /**
     * 负责产生订单、生成流水、划扣项目金额
     * @param $uid
     * @param $prj_id
     * @param int $money 用户输入的金额
     * @param array $buy_params 额外的参数放在这里
     * @return array
     */
    private function doBuyFirstStep($uid, $prj_id, $money, $buy_params = [])
    {
        $doing_count = $cache_back = false;
        $trans_key = 'PROJECT_BUY_' . $uid . '_' . $prj_id;

        /* @var $project_service ProjectService */
        $project_service = service('Financing/Project');
        $db = new BaseModel();
        try {

            $prj_model = D('Financing/Prj');
            $project_info = $prj_model->where(['id' => $prj_id])->find();
            $project_ext_info = M('prj_ext')->where(['prj_id' => $prj_id])->find();
            if (!$project_info) {
                throw_exception('该项目不存在，请检查后重试');
            }

            $money = $free_money = number_format($money, 2, '.', '');
            $reward_type = (int) $buy_params['reward_type'];
            $reward_id = $buy_params['reward_id'];
            $money_account_id = $project_service->getTenantIdByPrj($project_info);

            $remaining_money = $this->buyFirewall($money, $uid, $project_info, $buy_params);
            $doing_count = $cache_back = true;
            $db->startTrans($trans_key);

            //解析本次投资可以用到的可提现奖励reward_money
            $project_info['is_deposit'] =  $project_ext_info['is_deposit'];
            list($freeze_result, $real_money, $reward_money, $freeze_amount, $request_id) = $this->getRewardMoney(
                $uid, $money, $reward_id, $reward_type, $project_info
            );
            if ($freeze_result == 0) {
                throw_exception($this->getError());
            }

            //产生订单fi_prj_order
            $prj_order_model = new PrjOrderModel();
            $create_prj_order_result = $prj_order_model->addOrder($uid, $prj_id, $money, 0, $project_info, $buy_params);
            if (!$create_prj_order_result) {
                throw_exception(MyError::lastError());
            }
            list($order_no, $prj_order_id) = $create_prj_order_result;

            //冻结用户资金
            D('Payment/PayAccount');
            /* @var $pay_account_service PayAccountService */
            $pay_account_service = service('Payment/PayAccount');
            $free_money = $pay_account_service->giveFreeMoney($uid, $free_money, $freeze_amount);

            $freeze_type = $project_info['is_deposit'] ?
                PayAccountModel::OBJ_TYPE_ZS_BUY_FREEZE :
                PayAccountModel::OBJ_TYPE_PAYFREEZE;
            $pay_account_result = $pay_account_service->freezePay($uid, $order_no, $real_money, $reward_money, 0, 0,
                '投资', '', $money_account_id, $freeze_type, $prj_order_id, $free_money);
            if (!$pay_account_result['boolen']) {
                throw_exception($pay_account_result['message']);
            }

            //扣除标的剩余额度
            $project_service->modifyPrjAmountAndStatus(0, $project_info, $money,
                $remaining_money, self::getPrjRemainAmountCacheKey($project_info['id']));

            //新客=》非新客 放在第一步处理 防止第二步没执行完 用户多次投资
            $user_model = new UserModel();
            $user_model->procNewBie($uid, $prj_id, $is_newbie);

            $queue_data = [
                'prj_order_id' => $prj_order_id,
                'prj_id' => $prj_id,
                'uid' => $uid,
                'money' => $money,
                'params' => [
                    'pay_order_no' => $pay_account_result['payorderno'],
                    'prj_order_no' => $order_no,
                    'free_money' => $free_money,
                    'reward_money' => $reward_money,
                    'freeze_amount' => $freeze_amount,
                    'real_money' => $real_money,
                    'reward_id' => $reward_id,
                    'reward_type' => $reward_type,
                    'request_id' => $request_id,
                    'is_newbie' => $is_newbie,
                ],
            ];

            //二次进队列处理其他操作
            queue('project_buy_two_step', $prj_order_id, $queue_data);
            unset($is_newbie);
            $db->commit($trans_key);

            $project_service->descDoingCountCache($prj_id, $doing_count);
            $bool = true;
            $msg = '投资成功';
        } catch (Exception $e) {
            if ($db) {
                $db->rollback($trans_key);
            }

            if (isset($request_id) && !empty($request_id)) {
                $account_except_log_model = new AccountExceptLogModel();
                //使用队列进行解冻通知
                $params['reward_type'] = (int) $buy_params['reward_type'];
                $params['uid'] = $uid;
                $params['memo'] = $e->getMessage();
                $params['requestId'] = $request_id;
                try {
                    queue('unfreeze', $request_id, $params);
                    $err_message = 'uid:' . $uid . ',request_id:' . $request_id . ',type:' .$params['reward_type']. '已发出回滚请求';
                } catch (Exception $e){
                    $err_message = 'uid:' . $uid . ',request_id:' . $request_id . ',type:' .$params['reward_type']. '回滚异常';
                }
                //记录异常的红包
                $account_except_log_model->insertLog($uid, __FUNCTION__, __CLASS__, __FUNCTION__, $err_message);
            }

            $bool = false;
            $msg = $e->getMessage();
            $project_service->descDoingCountCache($prj_id, $doing_count);
            if ($cache_back) {
                try {
                    $project_service->buyCacheRollback($prj_id, $money);
                } catch (Exception $e) {
                    $msg = $e->getMessage();
                }
            }
        }

        return [
            'bool'=>$bool,
            'msg'=>$msg,
            'prj_id' => $prj_id,
            'money' => $money,
            'prj_order_id' => isset($prj_order_id) ? $prj_order_id : 0,
        ];
    }

    /**
     * 本次投资用到的真实账户余额 invest_money freezeAmount
     * @param $uid
     * @param $money
     * @param $reward_id
     * @param $reward_type
     * @param $project_info
     * @return array
     */
    private function getRewardMoney($uid, $money, $reward_id, $reward_type, $project_info)
    {
        $user_account = M('user_account')->where(['uid' => $uid])->field('reward_money, zs_reward_money')->find();
        $user_reward_money = $project_info['is_deposit'] ? $user_account['zs_reward_money'] : $user_account['reward_money'];

        if ($user_reward_money >= $money) {
            $reward_money = $money;
        } else {
            $reward_money = $user_reward_money;
        }

        $freeze_result = 1;
        //营销系统奖励
        $freeze_amount = 0;
        if ($reward_type) {
            $invest_bonus_data = $this->freezeUserBonusInYunYSystem($reward_id, $reward_type, $uid, $project_info, $money);
            D('Financing/UserBonusFreeze');
            if ($reward_type != UserBonusFreezeModel::TYPE_RATE) {
                $freeze_amount = $invest_bonus_data['freezeAmount'];
            }
        }

        //如果全部使用了现金红包 并且使用了奖励
        $buy_params['invest_reward_money'] = $freeze_amount;
        if ($money - $freeze_amount - $reward_money < 0) {
            $reward_money = $money - $freeze_amount;
        }
        $real_money = $money - $freeze_amount - $reward_money;
        $user_account = M('user_account')->where(['uid' => $uid])->field('amount, zs_amount')->find();
        $user_account_money = $project_info['is_deposit'] ? $user_account['zs_amount'] : $user_account['amount'];
        if ($user_account_money < $real_money) {
            $freeze_result = 0;
            $this->error = '账户余额不足';
        }

        return [
            $freeze_result,
            $real_money,
            $reward_money,
            $freeze_amount,
            empty($invest_bonus_data['request_id']) ? '' : $invest_bonus_data['request_id'],
        ];
    }


    /**
     * 请求营销系统冻结用户红包 奖励和加息券
     * @param $reward_id
     * @param $reward_type
     * @param $uid
     * @param $project_info
     * @param $amount
     * @return bool|mixed
     */
    private function freezeUserBonusInYunYSystem($reward_id, $reward_type, $uid, $project_info, $amount)
    {
        if (!($reward_type > 0)) {
            return false;
        }

        $amount = (int) $amount;
        /* @var $reward_service UseRewardService */
        $reward_service = service('Financing/UseReward');
        $request_id = $reward_service->createRequestId([
            'uid' => $uid,
            'prjId' => $project_info['id'],
            'timeLimitDay' => $project_info['time_limit'],
            'amount' => $amount,
        ]);
        //项目名称加上项目类型
        $prj_model = new PrjModel();
        $show_prj_name = $prj_model->get_show_prj_name($project_info['prj_type'], $project_info['prj_name']);
        $will_reward_data = $reward_service->freezeReward($uid, $project_info['id'], $project_info['time_limit_day'],
            $amount, $request_id, $show_prj_name, ['reward_type' => $reward_type, 'reward_id' => $reward_id]);

        return array_merge(
            ['request_id' => $request_id],
            $will_reward_data
        );
    }

    /**
     * 并发控制检查 错误修复
     * @param $money
     * @param $uid
     * @param $project_info
     * @param $buy_params
     * @return mixed
     */
    public function buyFirewall($money, $uid, $project_info, $buy_params)
    {
        $prj_id = $project_info['id'];
        $remaining_cache_key = self::getPrjRemainAmountCacheKey($prj_id);
        $doing_cache_key = self::getDoingCacheKey($prj_id);

        $before_result = cache($remaining_cache_key);
        if (trim($before_result) === '0') { //校验 剩余投标金额 是否被击穿了，
            $this->fixCache($project_info, $money, 2);
        }
        if ($before_result == 1) {
            throw_exception('投标已经满了');
        }

        if ($before_result && $before_result < $money + 1) {
            throw_exception('剩余投标金额不足');
        }

        //防止重复提交,再检测一次
        $this->buyCheck($money, $project_info, $uid, $buy_params);

        $cache = Cache::getInstance();
        $remaining_money = $result = $cache->decrement($remaining_cache_key, $money);

        import('libs.Counter.MultiCounter', ADDON_PATH);
        $prj_cache_key_counter = MultiCounter::init(Counter::GROUP_PRJ_COUNTER . '_con', Counter::LIFETIME_THISMONTH);

        if (!$result && trim($result) !== '0') {
            $this->fixCache($project_info, $money, 1);
            $remaining_money = $result = $cache->decrement($remaining_cache_key, $money);
            if (!$result) {
                throw_exception('cache失效！2');
            }
        }
        if (trim($result) === '0') {
            $this->fixCache($project_info, $money, 2);
            throw_exception('投标已经结束');
        }

        //记录正在执行的请求数
        $prj_cache_key_counter->incr($doing_cache_key, 1);
        return $remaining_money;
    }

    /**
     * 修复投标项目剩余额度的cache值
     * @param array $project_info
     * @param int $money 投标金额
     * @param int $error_type 1-cache失效 2-cache被击穿了
     */
    private function fixCache($project_info, $money, $error_type)
    {
        $prj_id = $project_info['id'];
        $remaining_cache_key = self::getPrjRemainAmountCacheKey($prj_id);
        $doing_cache_key = self::getDoingCacheKey($prj_id);
        $error_cache_key = self::getErrorCacheKey($prj_id, $error_type);

        import('libs.Counter.MultiCounter', ADDON_PATH);
        $prj_error_cache_key_counter = MultiCounter::init(Counter::GROUP_PRJ_COUNTER . '_err', Counter::LIFETIME_THISHOUR);
        $act2 = $prj_error_cache_key_counter->incr($error_cache_key, 1);

        $prj_cache_key_counter = MultiCounter::init(Counter::GROUP_PRJ_COUNTER . '_con', Counter::LIFETIME_THISMONTH);
        if ($act2 == 1) {
            for ($ii = 1; $ii <= 3; $ii++) {
                $doing_con = $prj_cache_key_counter->get($doing_cache_key);
                if (!$doing_con) break;
                if ($ii == 3) {
                    $prj_error_cache_key_counter->set($error_cache_key, 0);
                    $prj_cache_key_counter->set($doing_cache_key, 0);
                    if ($error_type == 2) {
                        throw_exception('投标已经结束 ！(' . $doing_con . ')');
                    }
                    if ($error_type == 1) {
                        throw_exception('1cache失效！(' . $doing_con . ')');
                    }
                }
                sleep(1);
            }
            //开始执行修复错误的代码
            $prj = M('prj')->find($prj_id);
            cache($remaining_cache_key, (1 + $prj['remaining_amount']), array('expire' => 24 * 3600 * 30));
            $prj_error_cache_key_counter->set($error_cache_key, 0);
            if (!$prj['remaining_amount']) {
                if ($error_type == 2) {
                    throw_exception('投标结束了！');
                }
                if ($error_type == 1) {
                    throw_exception('投标结束了！！');
                }
            }
            if ($prj['remaining_amount'] < $money) {
                if ($error_type == 2) {
                    throw_exception('剩余投标金额不足！');
                }
                if ($error_type == 1) {
                    throw_exception('剩余投标金额不足！！');
                }
            }
        } else {
            if ($error_type == 2) {
                throw_exception('投标已经结束');
            }
            if ($error_type == 1) {
                throw_exception('cache失效');
            }
        }
    }

    /**
     * 剩余金额memKEY
     * @param $prj_id
     * @return string
     */
    static private function getPrjRemainAmountCacheKey($prj_id)
    {
        return 'buyremain_' . $prj_id;
    }

    /**
     * 当前正在处理的购买个数memKEY
     * @param $prj_id
     * @return string
     */
    static private function getDoingCacheKey($prj_id)
    {
        return 'buydoing_con' . $prj_id;
    }

    /**
     * @param $prj_id
     * @param $error_type
     * @return string
     */
    static private function getErrorCacheKey($prj_id, $error_type)
    {
        return 'err' . $error_type . '_buyremain_' . $prj_id;
    }

    /**
     * 权限检查 标的状态 客户端 企福鑫 新客 是否可以多次购买 实名等
     * @param $money
     * @param $prj_info
     * @param $uid
     * @param $buy_params
     * @return bool
     */
    public function buyCheck($money, $prj_info, $uid, $buy_params)
    {
        $prj_id = $prj_info['id'];
        $visit_source = visit_source();

        if (!is_numeric($money)) {
            throw_exception('投资金额必须是数字');
        }

        $prj_ext_info = M('prj_ext')->find($prj_id);
        D('Financing/Prj');

        if (!in_array($prj_info['status'], [PrjModel::STATUS_PASS, PrjModel::STATUS_PASS_SPECIAL])) {
            throw_exception('该项目未通过审核，您不能进行此操作');
        }

        if ($prj_info['bid_status'] > PrjModel::BSTATUS_BIDING) {
            throw_exception('项目已经停止投标');
        }
        if ($prj_info['bid_status'] < PrjModel::BSTATUS_BIDING
            && $prj_info['status'] != PrjModel::STATUS_PASS_SPECIAL
            && !IS_CLI) {
            throw_exception('项目还在待开标');
        }

        //企福鑫
        if ($prj_ext_info['is_qfx']) {
            /* @var $company_salary_service CompanySalaryService */
            $company_salary_service = service('Financing/CompanySalary');
            $service = $company_salary_service->init($uid);
            $res = $company_salary_service->buyBeforeCheckPermission($prj_ext_info);
            if (!$res) {
                throw_exception($service->getMsg());
            }
        }

        //新客
        if ($prj_info['is_new']) {
            $user_info = M('user')->field('is_id_auth, person_id, is_newbie,zs_status, zs_close_invest_code')->where(['uid' => $uid])->find();
            if (!$user_info['is_newbie']) {
                throw_exception('此项目仅针对首次投资的用户开放，你不能投资该项目!');
            }
        }

        //客户端检查
        $client_type = I('request.CLIENT_TYPE');
        $prj_filter_model = new PrjFilterModel();
        $rs = $prj_filter_model->getClientPermission($client_type, $prj_id);
        if (!$rs) {
            throw_exception(MyError::lastError());
        }

        //活动
        $project_service = new ProjectService();
        $rs = $project_service->activeCheck($uid, $prj_info);
        if (!$rs) {
            throw_exception(MyError::lastError());
        }

        //是否可以多次购买
        if (!$prj_info['is_multi_buy']) {
            $prj_order_model = D('Financing/PrjOrder');
            $where['uid'] = $uid;
            $where['prj_id'] = $prj_id;
            $where['status'] = array('neq', PrjOrderModel::STATUS_NOT_PAY);
            $is_order_exist = (int) $prj_order_model->where($where)->getField('id');
            if ($is_order_exist) {
                throw_exception('此项目只可投资1次');
            }
        }

        if (!isset($user_info)) {
            $user_info = M('user')->field('is_id_auth, person_id, mi_id,zs_status, zs_close_invest_code')->where(['uid' => $uid])->find();
        }

        if ($prj_ext_info['is_deposit'] && $user_info['zs_close_invest_code'] == 0) {
            throw_exception('请先关闭投资短信验证码');
        }

        if ((!$user_info['is_id_auth']) || (!$user_info['person_id'])) {
            if ($visit_source == 'pc') {
                $auth_url = U('Account/Bank/identify');
                throw_exception('投资前需先进行实名认证,去<a href="' . $auth_url . '" class="blue" target="_blank">认证</a>');
            }
            throw_exception('投资前请先在账户中进行实名认证');
        }

        $buy_params['is_deposit'] = $prj_ext_info['is_deposit'];
        $this->buyAmountCheck($money, $prj_id, $uid, $buy_params);

        //判断项目专属性
        if ($prj_info['user_mi_id']) {
            if ($user_info['mi_id'] != $prj_info['user_mi_id']) {
                return errorReturn("您没有投资该项目的权限!");
            }
        }

        //购买时token验证开关
        if (C('BUY_TOKEN_ON')) {
            unset($_SESSION['xprjIdAccess']);
        }

        return true;
    }

    /**
     * 当前投资人的金额检查 标的最大最小和阶梯金额检查
     * @param $money
     * @param $prj_id
     * @param $uid
     * @param array $buy_params
     * @return bool
     */
    function buyAmountCheck($money, $prj_id, $uid, $buy_params = array())
    {
        $buy_params['money'] = $money = (int) $money;

        /* @var $project_service ProjectService */
        $project_service = service('Financing/Project');

        $prj_model = D('Financing/Prj');
        $fields = 'max_bid_amount, min_bid_amount, step_bid_amount, remaining_amount';
        $prj_info = $prj_model->field($fields)->where(['id' => $prj_id])->find();
        $prj_info['min_bid_amount'] = (int) $prj_info['min_bid_amount'];
        $prj_info['remaining_amount'] = (int) $prj_info['remaining_amount'];

        if ($prj_info['max_bid_amount']) {
            $had_invested_money = $project_service->getTotalOrderMoney($uid, $prj_id);
            $current_invested_money = $had_invested_money + $money;
            if ($current_invested_money > $prj_info['max_bid_amount']) {
                $diff = number_format($prj_info['max_bid_amount'] / 100, 2);
                $recommend_amount = humanMoney($prj_info['max_bid_amount'] - $had_invested_money, 2, false) . "元";
                throw_exception('您投资该项目的总金额大于投资上限' . $diff . '元,您还能投资的额度为' . $recommend_amount);
            }
        }

        if (!$prj_info['remaining_amount']) {
            throw_exception('没有可投资剩余金额');
        }

        if ($money > $prj_info['remaining_amount']) {
            $diff = humanMoney($prj_info['remaining_amount'], 2, false);
            throw_exception('投资金额不能大于剩余可投资金额' . $diff . '元');
        }

        if ($prj_info['remaining_amount'] < $prj_info['min_bid_amount']) {
            if ($prj_info['remaining_amount'] != $money) {
                $diff = humanMoney($prj_info['remaining_amount'], 2, false);
                throw_exception('剩余可投资金额小于起投金额时，投资金额应等于剩余可投资金额' . $diff . '元');
            }
        }

        $min_bid_amount = $project_service->getMinBidAmount(
            $prj_info['remaining_amount'],
            $prj_info['step_bid_amount'],
            $prj_info['min_bid_amount']
        );
        if (($prj_info['remaining_amount'] >= $min_bid_amount) && ($money < $min_bid_amount)) {
            $diff = humanMoney($min_bid_amount, 2, false);
            throw_exception('投资金额必须大于等于投资起始金额' . $diff . '元');
        }

        if ($prj_info['step_bid_amount']
            &&  ($prj_info['remaining_amount'] > $prj_info['min_bid_amount'])
            && ($money % $prj_info['step_bid_amount']) != 0) {
            $step_money_view = humanMoney($prj_info['step_bid_amount'], 2, false) . "元";
            throw_exception('投资金额必须按递增金额' . $step_money_view . '增加!');
        }

        return true;
    }

    /**
     * 队列中投标第二步 负责免费次数 活动触发 账户变更 消息推送等操作
     * @param $uid
     * @param $prj_id
     * @param $money
     * @param $prj_order_id
     * @param array $params
     * @return bool
     */
    public function buyTwoStep($uid, $prj_id, $money, $prj_order_id, $params = [])
    {
        /* @var $project_service ProjectService */
        $project_service = service('Financing/Project');
        $prj_model = new PrjModel();
        $now = time();

        $prj_info = $prj_model->where(['id' => $prj_id])->find();
        $user_info = M('user')->where(['uid' => $uid])->field('real_name, person_id, zs_bind_serial_no')->find();

        //给免费提现次数
        $prj_is_deposit = M('prj_ext')->where(['prj_id' => $prj_id])->getField('is_deposit');
        $prj_info['is_deposit'] = (int) $prj_is_deposit;
        $free_tx_times = $this->getFreeTiXianTimes($uid, $params['is_newbie'], $money, $prj_info);
        $prj_order_save_data = [
            'free_tixian_times' => $free_tx_times,
            'out_order_no' => $params['pay_order_no'],
            'free_money' => $params['free_money'],
            'prj_order_id' => $prj_order_id,
        ];

        try {
            $prj_model->startTrans();

            //通知托管银行冻结用户资金
            if ($prj_is_deposit) {
                $project_service->noticeBankFreezeUserMoney(
                    $params['real_money'] + $params['reward_money'],
                    $prj_order_id,
                    $params['prj_order_no'], $user_info, $prj_info,
                    ['is_prj_order' => 1]
                );
            }

            //协议保存
            if ($prj_info['prj_type'] != PrjModel::PRJ_TYPE_C) {
                $protocol = $project_service->getProtocol($prj_info['prj_type'], $prj_info['zhr_apply_id'], $prj_id);
                /* @var $protocol_service ProtocolService */
                $protocol_service = service('Financing/Protocol');
                $protocol_id = $protocol_service->savePdf($protocol['key'], $prj_order_id, $prj_id);
                if (!$protocol_id) {
                    throw_exception(MyError::lastError());
                }

                $prj_order_save_data['protocol_id'] = $protocol_id;
                M('prj_ext')->where(['prj_id' => $prj_id])->save(['order_protocol_id' => $protocol_id]);
            }

            //推荐是否投资
            M('user_recommed')->where(array('uid' => $uid))->save(['is_invest' => 1, 'mtime' => $now]);
            $user_source = M('user_register_source')->where(array('uid' => $uid))->find();
            if ($user_source && $user_source['has_invested'] == 0) {
                $user_source['has_invested'] = 1;
                $user_source['first_invest_time'] = $now;
                $user_source['first_invest_amount'] = $params['real_money'];
                M('user_register_source')->save($user_source);
            }

            //保存奖励数据
            $rewardData = array();
            $rewardData['reward_type'] = $params['reward_type'];
            $rewardData['reward_money'] = $params['reward_money'];
            $rewardData['invest_reward_money'] = $params['freeze_amount'];
            if ($rewardData['reward_type']) {
                $rewardData['reward_id'] = $params['reward_id'];
                $now = time();
                $rewardData['prj_order_id'] = $prj_order_id;
                $rewardData['ctime'] = $now;
                $rewardData['mtime'] = $now;
                M('prj_order_ext')->add($rewardData);
            }

            //回款数据记录生成 用户还款日历
            /* @var $user_account_summary_service UserAccountSummaryService */
            $user_account_summary_service = service('Payment/UserAccountSummary');
            $user_account_summary_service->changeAcountSummary('buy', $prj_order_id, 0, 0);
            /* @var $invest_service InvestService */
            $invest_service = service('Financing/Invest');
            $invest_service->addUserRepayPlan($prj_order_id);

            if (!empty($params['request_id'])) {
                $user_bonus_freeze_model = new UserBonusFreezeModel();
                $user_bonus_freeze_model->updateFreezeOrderId($params['request_id'], $prj_order_id, $params);
            }

            //处理分期付款等
            $project_service->bugAfterDb($prj_order_id);
            if (MyError::hasError()) {
                throw_exception(MyError::lastError());
            }

            //从返利投链接过来投资记录
            /* @var $fan_li_service FanlitouService */
            $fan_li_service = service('Api/Fanlitou');
            $fan_li_service->addFanlitouOrderLog($uid, $prj_order_id);

            $this->dealSomePrjOrderFields($uid, $prj_info, $prj_order_save_data);

            //其他异步处理,营销系统奖励等等
            /* @var $after_invest_task_service AfterInvestTaskService*/
            $after_invest_task_service = service('Financing/AfterInvestTask');
            $after_invest_task_service->notice($uid, $prj_order_id, 0);

            //签章 订单合同
            //签约系统开启 2016年11月 不再在投标时往队列里放数据
//            if (C('SIGNATURE_SWITCH')) {
//                $project_service->addContractQueue($prj_order_id, $prj_info, $uid);
//            }

            $project_service->bugAfter($prj_order_id);
            //推送消息
            $this->pushFinishInvestMessage($uid, $prj_order_id, $money, $params['free_money'], $prj_info);

            $prj_model->commit();
        } catch (Exception $e) {
            $prj_model->rollback();
            throw_exception($e->getMessage());
        }
        return true;
    }

    /**
     * 本次投资奖励的免费提现次数
     * @param $uid
     * @param int $is_new_bie
     * @param int $money 投资金额
     * @param $prj_info
     * @return int
     */
    private function getFreeTiXianTimes($uid, $is_new_bie, $money, $prj_info)
    {
        if ($prj_info['is_deposit'] == 1) {
            return 0;
        }

        $prj_id = $prj_info['id'];
        import('libs.Counter.MultiCounter', ADDON_PATH);
        $counter_key = Counter::GROUP_PRJ_COUNTER . '_free_tixian_times';
        $count_key = 'free_tixian_' . $prj_id . '_' . $uid;
        $prj_free_times_counter = MultiCounter::init($counter_key, Counter::LIFETIME_TODAY);

        if ($is_new_bie) {
            $current_count = $prj_free_times_counter->incr($count_key, 1);
            if ($current_count == 1) {
                return 1;
            }
        }

        if (in_array($prj_info['prj_type'], [
            PrjModel::PRJ_TYPE_B,
            PrjModel::PRJ_TYPE_D,
            PrjModel::PRJ_TYPE_F])) {
            $current_count = $prj_free_times_counter->incr($count_key, 1);
            if ($current_count == 1) {
                return 1;
            }
        } elseif ((($prj_info['time_limit_unit'] == 'day' && $prj_info['time_limit'] >= 30)
                || ($prj_info['time_limit_unit'] == 'month' && $prj_info['time_limit'] >= 1)
                || $prj_info['time_limit_unit'] == 'year') && $money >= 100000) {
            $current_count = $prj_free_times_counter->incr($count_key, 1);
            if ($current_count == 1) {
                return 1;
            }
        }

        return 0;
    }

    /**
     * 投资完毕后订单的一下字段更新 合并多个字段一个更新
     * @param $uid
     * @param $prj_info
     * @param $prj_order_save_data
     */
    private function dealSomePrjOrderFields($uid, $prj_info, $prj_order_save_data)
    {
        //invest_count和invest_users更新
        $prj_order_save_data['invest_count'] = ['exp', 'invest_count + 1'];
        $prj_order_model = new PrjOrderModel();
        $have_invested = $prj_order_model->field('id')->where([
            'prj_id' => $prj_info['id'],
            'uid' => $uid,
            'from_order_id' => array('EXP', 'IS NULL'),
            'status' => array('NEQ', PrjOrderModel::STATUS_NOT_PAY)
        ])->limit(2)->select();
        if(count($have_invested) == 1){
            $prj_order_save_data['invest_users'] = ['exp', 'invest_users + 1'];
        }

        //last_repay_date
        $last_repay_time = strtotimeX("+{$prj_info['time_limit']} {$prj_info['time_limit_unit']}", $prj_info['end_bid_time']);
        $last_repay_date = strtotime(date('Y-m-d', $last_repay_time));
        $prj_order_save_data['last_repay_date'] = $last_repay_date;

        //利率期限
        $cash_level = M('prj_ext')->where(['prj_id' => $prj_info['id']])->getField('cash_level');
        $prj_order_save_data = array_merge($prj_order_save_data, [
            'rate' => $prj_info['rate'],
            'rate_type' => $prj_info['rate_type'],
            'year_rate' => $prj_info['year_rate'],
            'time_limit' => $prj_info['time_limit'],
            'time_limit_unit' => $prj_info['time_limit_unit'],
            'time_limit_day' => $prj_info['time_limit_day'],
            'cash_level' => $cash_level,
        ]);

        $prj_order_save_data['mtime'] = time();
        $prj_order_model->where(array('id' => $prj_order_save_data['prj_order_id']))->save($prj_order_save_data);
    }

    /**
     * 投资完成后消息推送
     * @param $uid
     * @param $prj_order_id
     * @param int $money 投资金额
     * @param int $free_money 本次投资获得的免费提现额度
     * @param $prj_info
     */
    private function pushFinishInvestMessage($uid, $prj_order_id, $money, $free_money, $prj_info)
    {
        //发送消息
        $user_info = M('user')->field('uname, real_name')->where(['uid' => $uid])->find();
        $messageData = array();
        $messageData[] = $user_info['uname'];
        $messageData[] = $prj_info['prj_name'];
        $messageData[] = humanMoney($money, 2, false) . '元';
        if ($free_money) {
            $messageData[] = '，直到投标结束您账户内的这部分资金将被冻结';
            $messageData[] = '，并将获得' . humanMoney($free_money, 2, false) . '元的免费提现额度';
        } else {
            $messageData[] = '';
            $messageData[] = '';
        }

        // 6. 展期/非展期还款日提醒
        $extend_pre = '该项目还款最迟';
        $extend_tail = ' 13:00前到账。';
        $last_repay_date = date('Y-m-d', $prj_info['last_repay_date']);
        if ($prj_info['value_date_shadow'] > 0) {
            $extend_tail = ' 16:00-24:00到账。';
        }
        $messageData[] = $extend_pre . $last_repay_date . $extend_tail;

        $messageData[]= date("Y-m-d H:i:s");
        $extend_pre_m = '该项目最迟还款';
        $messageData[]=$extend_pre_m . $last_repay_date;
        //成功这发送邮件
        /* @var $message_service MessageService */
        $message_service = service('Message/Message');
        $message_service->sendMessage($uid, 1, 1, $messageData, $prj_info['id'], 1, array(1, 2, 3), true);

        //投资成功推送手机消息
        /* @var $remain_service RemineService */
        $remain_service = service('Mobile2/Remine');
        $remain_service->push_uid($uid, 1, time(), $prj_info['id'], $messageData, '', 0);

        //推送微信消息给用户
        $wx_msg_data = array(
            'real_name' => $user_info['real_name'],
            'prj_name' => $prj_info['prj_name'],
            'money' => humanMoney($money, 2, false),
        );
        /* @var $xhh_fw_service XhhfuwuService */
        $xhh_fw_service = service('Weixin/Xhhfuwu');
        $xhh_fw_service->addInvestMsgQueue($uid, $prj_order_id, $wx_msg_data);
    }

    /**
     * @param $prj_id
     * @return string
     */
    static private function getInvestorStatusHashKey($prj_id)
    {
        return 'INVEST_' . $prj_id;
    }

    /**
     * 标识用户的当前的投资状态
     * @param $uid
     * @param $prj_id
     * @param $uuid
     */
    public function remarkInvestor($uid, $prj_id, $uuid)
    {
        $redis_instance = \Addons\Libs\Cache\Redis::getInstance('default');
        $hash_key = self::getInvestorStatusHashKey($prj_id);

        $redis_instance->hSet(
            $hash_key,
            $uuid,
            serialize([
                'uid' => $uid,
                'prj_id' => $prj_id,
                'status' => 1, //初始化
            ])
        );

        $redis_instance->expire($hash_key, 86400);
    }

    /**
     * 标识投资状态成功
     * @param $uid
     * @param $prj_id
     * @param $prj_order_id
     * @param $uuid
     */
    private function setInvestorSuccess($uid, $prj_id, $prj_order_id, $uuid)
    {
        $redis_instance = \Addons\Libs\Cache\Redis::getInstance('default');
        $hash_key = self::getInvestorStatusHashKey($prj_id);

        $new_value = serialize([
            'uid' => $uid,
            'prj_id' => $prj_id,
            'prj_order_id' => $prj_order_id,
            'status' => 2, //成功
        ]);

        //其他的返回值在这里扩充
        //$new_value = array_merge($new_value, []);

        $redis_instance->hSet(
            $hash_key,
            $uuid,
            $new_value
        );
    }

    /**
     * 标识投资状态失败
     * @param $uid
     * @param $prj_id
     * @param $uuid
     */
    private function setInvestorFail($uid, $prj_id, $uuid, $msg)
    {
        $redis_instance = \Addons\Libs\Cache\Redis::getInstance('default');
        $hash_key = self::getInvestorStatusHashKey($prj_id);

        $new_value = serialize([
            'uid' => $uid,
            'prj_id' => $prj_id,
            'status' => 3, //失败
            'msg'=>$msg,
        ]);

        $redis_instance->hSet(
            $hash_key,
            $uuid,
            $new_value
        );
    }

    /**
     * 删除用户投资状态
     * @param $prj_id
     * @param $uuid
     */
    public function deleteInvestorRemark($prj_id, $uuid)
    {
        $redis_instance = \Addons\Libs\Cache\Redis::getInstance('default');
        $hash_key = self::getInvestorStatusHashKey($prj_id);

        $redis_instance->hDel($hash_key, $uuid);
    }

    /**
     * 供前端轮询查询用户的投资状态 以返回值中的status为准
     * @param $prj_id
     * @param $uid
     * @param $uuid
     * @return mixed|string status 1-初始化 2-成功(跳转至buySuccess页面) 3-失败
     */
    public function getInvestorStatus($prj_id, $uid, $uuid)
    {
        if (empty($uuid)) {
            throw_exception('请传入投资标识号');
        }

        $redis_instance = \Addons\Libs\Cache\Redis::getUnSerializerInstance('default');
        $hash_key = self::getInvestorStatusHashKey($prj_id);

        $invest_info = $redis_instance->hGet($hash_key, $uuid);
        $invest_info = unserialize($invest_info);
        if($invest_info){
            if ($invest_info['uid'] != $uid) {
                throw_exception('你没有权限操作');
            }
            if ($invest_info['prj_id'] != $prj_id) {
                throw_exception('参数错误');
            }

            if ($invest_info['status'] == 2 || $invest_info['status'] == 3) {
                $this->deleteInvestorRemark($prj_id, $uuid);
            }

            return $invest_info;
        }else{
            throw_exception('没找到数据');
        }


    }

    protected function setBaseApiKey()
    {
        $api_key = C('api_info');
        $api_key = $api_key['api_key'];

        if (isset($_COOKIE['PHPSESSID'])) {
            session_id($_COOKIE['PHPSESSID']);
        }
        $session_id = session_id();

        //如果作为服务端，uid需要从客户端获取 这里需要重写
        //作为客户端uid需要从原生的session中获取 这就需要登录的时候要保存uid到原生session中
        $login_user_id = session('uid');

        if(intval($login_user_id) > 0){
            $this->mid = $login_user_id;
        }

        BaseModel::setApiKey($api_key,$session_id,$login_user_id);
    }
}

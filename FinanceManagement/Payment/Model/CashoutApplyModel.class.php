<?php

//提现申请
class CashoutApplyModel extends BaseModel {

    protected $tableName = 'cashout_apply';

    const STATUS_TODO = 1; //人工审核
    const STATUS_SUCCESS = 2; //提现成功
    const STATUS_FAILED = 3; //提现不批准
    const STATUS_CANCEL = 4; //取消申请
    const STATUS_DEALING = 5; //处理中
    const STATUS_MAC_TODO = 6; //机器待处理
    const STATUS_MAC_DEALING = 7; //机器处理中
    const STATUS_MAC_CUSTOM = 8; //客服跟踪
    const STATUS_KEFU_CAIFU = 9; //客服跟踪不通过(到财务的处理)
    const STATUS_YIBAO_SHENFUT = 11; //易宝失败转盛付通
    
    /**
     * 禁用自动提现的用户列表
     * @var type 
     */
    private static $_disableUserIdList = array(
        10442,//鲍蕾
        129673, //吴春雷 bawcl1958
        84702,//王冬存 wangdongcun
        10275,//包吴军 baowujun
        132998,//卢红文 卢红文
        90598,//黄海业 huanghaiye
        123774,//罗鸿铭 scotchjean
        42988,//段可 dk1995
    	589330, // 数米财富，shumi001
    );

    //申请，@only 只供账户model 提现申请调用

    public function apply($uid, $money, $fee, $reward_money, $channel, $bank, $sub_bank, $bak, $free_times, $free_money, $cost_saving, $out_account_no, $out_account_id = '', $free_tixian_times = 0, $source = 1, $no_auto = 0, $no_role = true,$zs_code='')
    {
//        if($bank == 'PSBC') throw_exception('暂不支持邮政储蓄银行的提现操作');

        $channelUser = M('user_channel_ext')->where(array('is_mi_user'=>1, 'uid'=>$uid))->find();
//        if(!$channelUser) {
//            $user_account = M('user_account')->where(array('uid'=>$uid))->find();
//            if($user_account['is_merchant'] != 1) {
//                $has_repay = D('Financing/PrjOrder')->where(array('uid' => $uid
//                , 'repay_status' => array('gt', PrjOrderModel::REPAY_STATUS_NOT_REPAYMENT)))->find();
//                service("Financing/FastCash");
//                $has_repay2 = M('prj_fast_cash')->where(array('uid' => $uid
//                , 'status' => array('in', FastCashService::STATUS_END . "," . FastCashService::STATUS_FULL)))->find();
//                if (!$has_repay && !$has_repay2) throw_exception('您还未收到过还款金额，暂不能提现');
//            }
//        }

        $uid = (int) $uid;
        $money = (int) $money;
        //获取提现额度
        $total_cashout_money = $money+$fee;
        $mobile_cashout_money = 0;
        $free_cashout_money = 0;
        $mcashout_amount = 0;
        $cashout_amount = 0;
        $recharge_bank_no = null;

        $user_cashout_tamount = M('user_cashout_tamount')->find($uid);
        if($user_cashout_tamount) $free_cashout_money = $user_cashout_tamount['cashout_amount'];

        $data['uid'] = (int) $uid;
        $data['channel'] = $channel;
        $data['bank'] = $bank; //银行code
        $data['sub_bank'] = $sub_bank; //银行支行
        $data['bak'] = $bak;
        if ($free_times) {
            $data['free_times'] = 1;
        } else {
            $data['free_times'] = 0;
        }
        $data['free_money'] = $free_money;
        $data['out_account_no'] = $out_account_no;
        if ($out_account_id) {
            $fund_account = M('fund_account')->find((int) $out_account_id);

            $data['out_account_id'] = $fund_account['id'];
            if (!$fund_account['bank'])
                throw_exception("该账户不存在我们支持的银行列表中");
            $data['bank_province_id'] = $fund_account['bank_province'];
            $data['bank_province'] = M("dict_city")->where(array('code' => $data['bank_province_id']))->getField('name_cn');
            $data['bank_city_id'] = $fund_account['bank_city'];
            $data['bank_city'] = M("dict_city")->where(array('code' => $data['bank_city_id']))->getField('name_cn');
            $data['bank'] = $fund_account['bank'];
            $data['sub_bank_id'] = $fund_account['sub_bank_id'];
            $data['sub_bank'] = $fund_account['sub_bank'];
            $data['out_account_no'] = $fund_account['account_no'];

//            if (!$data['bank_province'] || !$data['bank_city']) {
//                //如果不是商户抛出完善信息
//                $is_merchant = M('user_account')->where("uid = '{$uid}'")->getField('is_merchant');
//                if (!$is_merchant)
//                    throw_exception("请完善您的银行卡信息");
//            }

            if($fund_account['recharge_bank_id']){
                $recharge_bank_no = M('recharge_bank_no')->find($fund_account['recharge_bank_id']);
                if($recharge_bank_no){
                    $mobile_cashout_money = $recharge_bank_no['cashout_amount'];
                }
            }
        }
//        if(!preg_match('/\d{16,20}/', $data['out_account_no'])){
//            throw_exception("卡号格式不正确（16-20位数字）");
//        }
        if (!$channelUser && $no_role) {
            if ($total_cashout_money <= $mobile_cashout_money) {
                $mcashout_amount = $total_cashout_money;
                $cashout_amount = 0;
            } else if ($total_cashout_money > $mobile_cashout_money
                && $total_cashout_money <= ($free_cashout_money + $mobile_cashout_money)
            ) {
                $cashout_amount = $total_cashout_money - $mobile_cashout_money;
                $mcashout_amount = $mobile_cashout_money;
            } else {
                $mobile_cashout_str = '';
                if ($mobile_cashout_money) $mobile_cashout_str = ",手机额度" . ($mobile_cashout_money / 100);
                throw_exception("提现金额" . ($total_cashout_money / 100) . "元，超过了该卡提现额度"
                    . (($free_cashout_money + $mobile_cashout_money) / 100) . "元（自由额度：" . ($free_cashout_money / 100) . $mobile_cashout_str . "）");
            }

            $data['mcashout_amount'] = $mcashout_amount;
            $data['cashout_amount'] = $cashout_amount;
            $data['f_mcashout_amount'] = $mobile_cashout_money - $mcashout_amount;
            $data['f_cashout_amount'] = $free_cashout_money - $cashout_amount;
            if ($cashout_amount || $mcashout_amount) {
                if($mcashout_amount) {
                    $recharge_bank_data['id'] = $recharge_bank_no['id'];
                    $recharge_bank_data['mtime'] = time();
                    $recharge_bank_data['cashout_amount'] = $data['f_mcashout_amount'];
                    $recharge_bank_data['freeze_cashout_amount'] = $recharge_bank_no['freeze_cashout_amount'] + $mcashout_amount;
                    $recharge_bank_data['version'] = $recharge_bank_no['version'] + 1;
                    $con = M('recharge_bank_no')->where(array('id' => $recharge_bank_no['id']
                    , 'version' => $recharge_bank_no['version']))->save($recharge_bank_data);
                    if (!$con) throw_exception("并发异常，手机额度减扣失败，请重试");
                }
                if($cashout_amount) {
                    $user_cashout_data['uid'] = $user_cashout_tamount['uid'];
                    $user_cashout_data['mtime'] = time();
                    $user_cashout_data['cashout_amount'] = $data['f_cashout_amount'];
                    $user_cashout_data['version'] = $user_cashout_tamount['version'] + 1;
                    $con = M('user_cashout_tamount')->where(array('uid' => $user_cashout_tamount['uid']
                    , 'version' => $user_cashout_tamount['version']))->save($user_cashout_data);
                    if (!$con) throw_exception("并发异常，自由额度减扣失败，请重试");
                }
            }
        } else {
            $data['mcashout_amount'] = 0;
            $data['cashout_amount'] = 0;
            $data['f_mcashout_amount'] = $mobile_cashout_money;
            $data['f_cashout_amount'] = $free_cashout_money;
        }

        $data['money'] = $money;
        $data['fee'] = $fee;

        if ($free_tixian_times || !$no_role) {
            $data['tixian_fee'] = 0;
        } else {
            $data['tixian_fee'] = CASHOUT_CASH_FEE;
        }
        $data['free_tixian_times'] = $free_tixian_times;
        $data['reward_money'] = $reward_money;
        $data['cost_saving'] = $cost_saving;
        $data['source'] = $source;
        if($no_auto) $data['is_no_auto'] = 1;
        $data['status'] = $this->getCashStatus($data,$bank);
        if($channel == PaymentService::TYPE_ZHESHANG){
            $data['status'] = self::STATUS_MAC_DEALING;
            $data['tixian_fee'] = 0;
        }
        $data['ctime'] = time();
        $data['mtime'] = time();
        $channel == PaymentService::TYPE_ZHESHANG && $data['dataupload_time'] = time();
        $data['client_type'] = getFrom();
        $data['mi_no'] = self::$api_key;

        $data['cashout_process_no'] = $this->createNo();//201568增加提现流水号
     
        $id = M('cashout_apply')->add($data);
        if (!$id) {
            //如果出错 添加日志start
            /* $tempDir = APP_PATH.'Runtime'.DIRECTORY_SEPARATOR.'cashout';
              if(!is_dir($tempDir)){
              mkdir($tempDir, 0777);
              }
              $logFile = $tempDir.DIRECTORY_SEPARATOR.'cashout_'.date('YmdHis').'.log';
              file_put_contents($logFile, var_export($data, true)."\n", FILE_APPEND);
              file_put_contents($logFile, M('cashout_apply')->getLastSql()."\n", FILE_APPEND); */
            //end
            throw_exception("系统数据异常CAOA1");
        }
        return $id;
    }

    private function cannelCashoutAmount($id, $cashout_apply=array()){
        if(!$cashout_apply) $cashout_apply = M('cashout_apply')->find($id);
        if(!$cashout_apply) throw_exception("异常CCShout");

        if($cashout_apply['cashout_amount']) {
            $user_cashout_tamount = M('user_cashout_tamount')->find($cashout_apply['uid']);
            if (!$user_cashout_tamount) throw_exception("没有自由额度数据");
            $user_cashout_data['uid'] = $user_cashout_tamount['uid'];
            $user_cashout_data['mtime'] = time();
            $user_cashout_data['cashout_amount'] = $user_cashout_tamount['cashout_amount'] + $cashout_apply['cashout_amount'];
            $user_cashout_data['version'] = $user_cashout_tamount['version']+1;
            $con = M('user_cashout_tamount')->where(array('uid'=>$user_cashout_tamount['uid']
                ,'version'=>$user_cashout_tamount['version']))->save($user_cashout_data);
            if(!$con) throw_exception("并发异常，自由额度减扣失败，请重试");
        }
        if($cashout_apply['mcashout_amount']) {
            $fund_account = M('fund_account')->find($cashout_apply['out_account_id']);
            if(!$fund_account && !$fund_account['recharge_bank_id']) throw_exception("没有对应的提现卡");
            $recharge_bank_no = M('recharge_bank_no')->find($fund_account['recharge_bank_id']);
            if(!$recharge_bank_no)throw_exception("没有对应的充值提现卡");
            $recharge_bank_data['id'] = $recharge_bank_no['id'];
            $recharge_bank_data['mtime'] = time();
            $recharge_bank_data['cashout_amount'] = $recharge_bank_no['cashout_amount'] + $cashout_apply['mcashout_amount'];
            $recharge_bank_data['freeze_cashout_amount'] = $recharge_bank_no['freeze_cashout_amount'] - $cashout_apply['mcashout_amount'];
            $recharge_bank_data['version'] = $recharge_bank_no['version'] + 1;
            $con = M('recharge_bank_no')->where(array('id' => $recharge_bank_no['id']
                , 'version' => $recharge_bank_no['version']))->save($recharge_bank_data);
            if (!$con) throw_exception("并发异常，手机额度减扣失败，请重试");
        }
    }

    //机器处理中 $macChannel=shenft
    public function dealingMac($id, $batchId, $macChannel, $out_order_id = 0) {
        $id = (int) $id;
        $cashout_apply = M('cashout_apply')->find($id);
        if (!$cashout_apply)
            throw_exception("数据不存在");
        if ($cashout_apply['status'] != self::STATUS_MAC_TODO && $cashout_apply['status'] !=11 )
            throw_exception("不在机器待处理状态下");

        $data['id'] = $id;
        $data['status'] = self::STATUS_MAC_DEALING;
        $data['mac_channel'] = $macChannel;
        $data['mac_banch_id'] = $batchId;
        $data['out_order_id'] = $out_order_id;
        $data['mac_time'] = time();
        $con = M('cashout_apply')->save($data);
        if (!$con) {
            throw_exception("系统数据异常dealingMac");
        }
        return true;
    }

    //自动处理结果回调 $id数据id
    //$status 1-成功 0-失败 2-处理中 $bak备注 $out_id对应的三方接口返回的id
    public function macRollback($id, $status, $bak, $out_id = "") {
        $cashout_apply = M('cashout_apply')->find($id);
        if (!$cashout_apply)
            return array('boolen' => 0, 'message' => "数据不存在");
        if ($cashout_apply['status'] != self::STATUS_MAC_DEALING)
            return array('boolen' => 0, 'message' => "不在机器处理中状态下");

        $this->startTrans();
        try {
            if ($status == 1) {
                $result = $this->deal($id, 1, self::STATUS_SUCCESS, "", $out_id);
                if(!$result['boolen']){
                	throw_exception($result['message']);
                }
            } else if ($status == 2) {
            	throw_exception("处理中");
            } else if ($status == 0) {//提现失败处理
                $result = $this->deal($id, 1, self::STATUS_FAILED, "", $out_id);
                if(!$result['boolen']){
                    throw_exception($result['message']);
                }
            } else {
                $data['status'] = self::STATUS_MAC_CUSTOM;
            }
            $data['id'] = $id;
            $data['mac_reason'] = $bak;
            $data['mac_time'] = time();
            $data['mtime'] = $data['mac_time'];
            $con = M('cashout_apply')->save($data);
            if (!$con) {
            	throw_exception("系统数据异常macRollback");
            }

            //添加机器备注
            if ($bak) {
                $this->addCashoutRemark(0, $id, $bak, 3);
            }

            $this->commit();
        } catch (Exception $e) {
            $this->rollback();
            return array('boolen' => 0, 'message' => $e->getMessage());
        }
        return array('boolen' => 1, 'message' => "处理成功");
    }

    public function changeToCus($id, $uid = '', $reason = '') {
        $cashout_apply = M('cashout_apply')->find((int) $id);
        if (!in_array($cashout_apply['status'], array(self::STATUS_TODO, self::STATUS_DEALING)))
            throw_exception("参数异常");
        $cashout_data['id'] = $cashout_apply['id'];
        $cashout_data['status'] = self::STATUS_MAC_CUSTOM;
        $cashout_data['mtime'] = time();

        //添加财务备注
        $this->addCashoutRemark($uid, $cashout_apply['id'], $reason, 1);

        M('cashout_apply')->save($cashout_data);
    }

    /**
     * 客服处理 $status 1-同意提现申请  0-不同意提现申请
     * @param type $id
     * @param type $status
     * @param type $uid
     * @param type $bak
     * @param type $uname
     * @return boolean
     * @throws Exception
     */
    public function cusDeal($id, $status, $uid, $bak, $uname = '') {
        $cashout_apply = M('cashout_apply')->find($id);
        if (!$cashout_apply) {
            throw_exception("数据不存在");
        }
        if ($cashout_apply['status'] != self::STATUS_MAC_CUSTOM) {
            throw_exception("不在客服跟踪状态下");
        }

        $this->startTrans();
        try {
            if ($status == 1) {
                $isAuto = service("Payment/Payment")->isAutoCashoutBank($cashout_apply['bank'], $cashout_apply['sub_bank_id'], $cashout_apply['bank_province_id'], $cashout_apply['bank_city_id'], $cashout_apply['channel']);
                if ($cashout_apply['mac_time'] || !$isAuto || $this->isDisableAutoCashoutUser($cashout_apply['uid'])) {
                    $data['status'] = self::STATUS_TODO;
                } else {
                    $data['status'] = self::STATUS_MAC_TODO;
                }
            } else {
                $this->deal($id, 1, self::STATUS_KEFU_CAIFU, $bak);
            }

            if ($data['status']) {
                $data['id'] = $id;
// 				$data['cus_uid'] = $uid;
// 				$data['cus_bak'] = $bak;
// 				$data['cus_time'] = time();
// 				$data['mtime'] = $data['cus_time'];

                $con = M('cashout_apply')->save($data);
                if (!$con) {
                    throw_exception("系统数据异常cusDeal");
                }
            }

            //添加客服备注
            if ($bak) {
                $this->addCashoutRemark($uid, $id, $bak, 2);
            }

            $this->commit();
        } catch (Exception $e) {
            $this->rollback();
            throw $e;
        }
        return true;
    }

    //取消申请
    public function cancel($id, $uid) {
        try {
            $cashout_apply = M('cashout_apply')->find((int) $id);
            if($cashout_apply['channel'] == 'yibaoApi')
                throw_exception("易宝提现不允许取消操作");
            if ($cashout_apply['uid'] != $uid)
                throw_exception("没有权限");
            if (!in_array($cashout_apply['status'], array(self::STATUS_TODO, self::STATUS_MAC_TODO, self::STATUS_MAC_CUSTOM)))
                throw_exception("已经处理，不能取消");
            $cashout_apply_data['id'] = (int) $id;
            $cashout_apply_data['status'] = self::STATUS_CANCEL;
            $con = M('cashout_apply')->save($cashout_apply_data);
            if (!$con) {
                $this->rollback();
                throw_exception("系统数据异常CAo2");
            }
            $this->cannelCashoutAmount($cashout_apply['id'], $cashout_apply);
            $rel = D("Payment/PayAccount")->cancelCashout($uid, $cashout_apply);
            if (!$rel) {
                $this->rollback();
                return false;
            }
            return true;
        } catch (Exception $e) {
            $this->rollback();
            throw $e;
        }
    }

    /**
     * 处理 $out_id如果是有api调用是，返回的流水no
     * @param type $id
     * @param type $dealUid
     * @param type $dealStatus
     * @param type $dealReason
     * @param type $out_id
     * @param type $tmp_cashout_apply
     * @param type $channel
     * @return type
     */
    public function deal($id, $dealUid, $dealStatus, $dealReason, $out_id = '', $tmp_cashout_apply = '', $need_msg=1,$channel='') {
        $id = (int) $id;
        $is_dealing=0;//2015611 用于区分提现中和提现处理中，默认是提现中status=5,为0
        //echo $dealStatus;exit;
        if($dealStatus == 99){
            $dealStatus = 5;
            $is_dealing = 1;
        }

        if (!in_array($dealStatus, array(self::STATUS_TODO, self::STATUS_KEFU_CAIFU, self::STATUS_DEALING, self::STATUS_SUCCESS, self::STATUS_FAILED)))
            throw_exception("参数异常");
        if ($tmp_cashout_apply) {
            $cashout_apply = $tmp_cashout_apply;
        } else {
            $cashout_apply = M('cashout_apply')->find($id);
        }

        $key = "cashout_apply_deal_{$id}";

        if (in_array($dealStatus, array(self::STATUS_SUCCESS, self::STATUS_FAILED))) {
            $cachekey = "cashout_apply_dealed_{$id}";
            if (cache($cachekey)) {
                throw_exception("该订单已经处理过了");
            }

            cache($cachekey, 1, array('expire' => 8));
        }

        if (!$cashout_apply)
            throw_exception("数据不存在");
        //TODO 不清楚 等刘俊回来解决
// 		if($cashout_apply['status'] != self::STATUS_MAC_CUSTOM) throw_exception("数据已经处理");
		$now = time();
        $this->startTrans($key);
        try {
            if ($dealStatus == self::STATUS_SUCCESS) {
                $is_prjPay = 0;
                if ($cashout_apply['prj_id'])
                    $is_prjPay = 1;
                $result = D('Payment/Ticket')->cashout($cashout_apply['money'], $cashout_apply['fee'], $cashout_apply['uid']
                        , $cashout_apply['channel'], $id, $cashout_apply['out_account_no'], $out_id, $cashout_apply['out_account_id'], $cashout_apply['bak']
                        , $cashout_apply['free_times'], $cashout_apply['free_money'], $is_prjPay, $cashout_apply['client_type']);
                if ($result['boolen'] == 0) {
                    $this->rollback($key);
                    return $result;
                }

                if(!$is_prjPay) {
                    $fund_account = M('fund_account')->find($cashout_apply['out_account_id']);
                    if ($fund_account['recharge_bank_id']) {
                        $recharge_bank_no = M('recharge_bank_no')->find($fund_account['recharge_bank_id']);
                        if ($cashout_apply['mcashout_amount']) {
                            $recharge_bank_data['id'] = $recharge_bank_no['id'];
                            $recharge_bank_data['mtime'] = time();
                            $recharge_bank_data['freeze_cashout_amount'] = $recharge_bank_no['freeze_cashout_amount'] - $cashout_apply['mcashout_amount'];
                            if ($recharge_bank_data['freeze_cashout_amount'] <= 0) $recharge_bank_data['freeze_cashout_amount'] = 0;
                            $recharge_bank_data['version'] = $recharge_bank_no['version'] + 1;
                            $con = M('recharge_bank_no')->where(array('id' => $recharge_bank_no['id']
                            , 'version' => $recharge_bank_no['version']))->save($recharge_bank_data);
                            if (!$con) throw_exception("并发异常，手机额度减扣失败，请重试");
                        }
                    }
                }


            } else if ($dealStatus == self::STATUS_FAILED) {
                $this->cannelCashoutAmount($cashout_apply['id'], $cashout_apply);
                D('Payment/PayAccount')->cashfailed($cashout_apply);
            }
            $data['id'] = $id;
            if ($dealStatus == self::STATUS_DEALING) {
                $data['dealing_uid'] = (int) $dealUid;
                $data['dealing_time'] = $now;
            } else if ($dealStatus != self::STATUS_TODO && $dealUid != 1) {
                $data['deal_uid'] = (int) $dealUid;
                $data['deal_reason'] = $dealReason;
                $data['manual_channel'] = $channel;
                $data['deal_time'] = $now;
                //添加财务备注
                $this->addCashoutRemark($dealUid, $id, $dealReason, 1);
            }
            $data['status'] = $dealStatus;

            //2015610 增加判断提交处理中 start
            if( $dealStatus == self::STATUS_DEALING && $is_dealing == 1){
                    $data['is_dealing'] = 1;
            }
            if(($dealStatus == self::STATUS_DEALING && $is_dealing == 0) || $dealStatus==2 || $dealStatus==3){
                $data['is_dealing'] = 0;
            }//end

            if($dealStatus == self::STATUS_KEFU_CAIFU){
                $data['cus_no_pass'] = 1;
            }

            $data['ticket_id'] = $result['id'];
            $data['mtime'] = $now;
            $res = M('cashout_apply')->save($data);
            if (!$res) {
                $this->rollback($key);
                return array('boolen' => 0, 'message' => '系统数据异常CAODL1('.$this->getDbError()."-".$this->getError().")");
            }

            $com = $this->commit($key);
            if ($com && $dealStatus == self::STATUS_SUCCESS) {
                //对接BFS资金系统
                $cashout_apply['mtime'] = $now;
                C('BFS_IF_OPEN') == 1 && service('Payment/Payment')->pushToBFS(1, $cashout_apply['mac_channel'], $cashout_apply);
            }
            if ($com && $dealStatus == self::STATUS_FAILED) {
                $content = array(date('Y-m-d'), number_format(($cashout_apply['money'] + $cashout_apply['fee']) / 100, 2) . "元", date("Y-m-d H:i"));
                service("Message/Message")->sendMessage($cashout_apply['uid'], 1, 247, $content ,0,0,array(1,2),1);
                service("Mobile2/Remine")->push_uid($cashout_apply['uid'], 247, time(), '提现失败', $content, $memo = '', 0);
            }
            return array('boolen' => 1, 'message' => "处理成功");
        } catch (Exception $e) {
            $this->rollback($key);
            return array('boolen' => 0, 'message' => $e->getMessage());
        }
    }

    /**
     * 获取提现处理的列表
     * @param $cond
     * @param $parameter
     * @param string $order
     * @param int $num 新增 数量
     * @return mixed
     */
    public function getList($where, $parameter, $order = "", $num = 10, $page = '') {
       // $where['mi_no'] = self::$api_key;
        if (!$order)
            $order = "id desc";
        $data = $this->where($where)->order($order)->findPage($num, $parameter, false, array(), false, $page);
//        p($this->getLastSql());exit;
        if ($data['data']) {
            $list = array();
            foreach ($data['data'] as $ele) {
                $ele['user'] = M('user')->field("uname, real_name")->find($ele['uid']);
                $ele['user_account'] = M('user_account')->field("amount")->find($ele['uid']);
                $ele['company_id'] =  D("Account/UserExt")->getUserCompanyId($ele['uid']);
// 				if($ele['out_account_id']){
// 					$ele['fund_account'] = M('fund_account')->field("account_no,bank_name,sub_bank")->find($ele['out_account_id']);
// 				} else {
                $ele['fund_account']['account_no'] = $ele['out_account_no'];
                $ele['fund_account']['bank_name'] = getCodeItemName('E009', $ele['bank']);
                $ele['fund_account']['sub_bank'] = $ele['sub_bank'];
                $ele['fund_account']['bank_province'] = $ele['bank_province'];
                $ele['fund_account']['bank_city'] = $ele['bank_city'];
// 				}
                if ($ele['prj_id']) {
                    $cprj = service("Financing/Project")->getDataById($ele['prj_id']);
                    $ele['prj_name'] = $cprj['prj_name']; // 项目名称
                    $ele['prj_no'] = $cprj['prj_no']; // 项目no
// 					$ele['safeguards'] = $cprj['safeguards'];
                }

                if (!$ele['deal_time']) {
                    $ele['deal_time'] = $ele['mac_time'];
                }
                $ele['fuwu_fee'] = $ele['fee'] - $ele['tixian_fee'];
// 				$ele['ctime'] = date("Y-m-d H:i:s", $ele['ctime']);
// 				$ele['mtime'] = date("Y-m-d H:i:s", $ele['mtime']);
// 				$ele['deal_time'] = date("Y-m-d H:i:s", $ele['deal_time']);
                //判断是自动提现还是财务手动操作
                if (in_array($where['status'], array(2, 3))) {
                    $ele['automatic'] = $ele['mac_time'] == $ele['mtime'] ? 1 : 0;
                }

                $ele['hasRemark'] = M('cashout_remark')->where('apply_id = '.$ele['id'])->count();

                $list[] = $ele;
            }
            $data['data'] = $list;
        }
        return $data;
    }

    /**
     * 获取符合条件的提现请求数量
     * @param $cond 查询条件 数组形式
     */
    public function getCount($cond) {
        $cond['mi_no'] = self::$api_key;
        return $this->where($cond)->count();
    }

    /**
     * 添加备注
     * @param int $uid 添加备注人
     * @param int $id	对应的申请提现id
     * @param string $remark	备注内容
     * @param int $role	添加备注人角色 （1财务2客服3机器）
     */
    private function addCashoutRemark($uid, $id, $remark, $role) {
        if ($uid = intval($uid)) {
            $operator = M('user')->where("uid = '{$uid}'")->getField('uname');
        } else {
            $operator = '机器处理';
        }
        $remarkData = array(
            'apply_id' => $id,
            'remark' => $remark,
            'uid' => $uid,
            'operator' => $operator,
            'operator_role' => $role,
            'ctime' => time()
        );
        return M('cashout_remark')->add($remarkData);
    }
    /**
     * 该用户是否禁用自动提现
     * @param $uid
     * @return bool
     */
    private function isDisableAutoCashoutUser($uid) {
        return in_array($uid, self::$_disableUserIdList);
    }

    /**
     * 获取当前变现的处理状态
     * @param $data
     * @param $bank
     * @return bool
     */
    private function getCashStatus($data,$bank)
    {
        return $status = self::STATUS_TODO;
        if($data['is_no_auto']) return $status = self::STATUS_TODO;
        $status = self::STATUS_TODO;

        if($data['uid'] < 10){
            return $status;
        }

        if (($data['money'] + $data['fee']) >= CASHOUT_CUS_MONEY) {
            return self::STATUS_MAC_CUSTOM;
        }

        if($this->isDisableAutoCashoutUser($data['uid'])){
            return $status;
        }

        if(M('user_account')->where(array('uid'=>$data['uid']))->getField('is_merchant')){
            return $status;
        }

        $channelUser = M('user_channel_ext')->where(array('is_mi_user'=>1, 'uid'=>$data['uid']))->find();
        if($channelUser){
            return $status;
        }

        if(!service("Payment/Payment")->isAutoCashoutBank($bank, $data['sub_bank_id'], $data['bank_province_id'], $data['bank_city_id'], $data['channel'])){
            return $status;
        }

        return self::STATUS_MAC_TODO;
    }

     /**
     * 2015610 增加创建提现流水号 前缀TX(表示提现含义)
     * @return no
     */
    public function createNo(){
        $id_gen_instance = new \Addons\Libs\IdGen\IdGen();
        return $id_gen_instance->get(\Addons\Libs\IdGen\IdGen::WORK_TYPE_CASH_APPLY);
    }
}

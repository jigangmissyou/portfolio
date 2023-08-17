<?php
/**
 * 浙商托管的一些服务
 * User: luoman
 * Date: 2016/8/9
 * Time: 14:42
 */
class ZheShangService extends BaseService
{

    public function createPrjBonusOrder()
    {
        $prj_model = D('Financing/Prj');
        /* @var $prj_order_bonus_summary_model PrjOrderBonusSummaryModel */
        $prj_order_bonus_summary_model = D('Financing/PrjOrderBonusSummary');

        //所有满标的项目
        $condition = [
            'fi_prj.status' => PrjModel::STATUS_PASS,
            'fi_prj.bid_status' => PrjModel::BSTATUS_FULL,
            'fi_prj_ext.is_deposit' => 1,
        ];

        $prj_list = $prj_model
            ->field('fi_prj.*')
            ->join('fi_prj_ext on fi_prj.id = fi_prj_ext.prj_id')
            ->where($condition)
            ->select();

        if (empty($prj_list)) {
            return true;
        }

        $redis_instance = \Addons\Libs\Cache\Redis::getInstance('default');
        $redis_key = 'CREATE_PRJ_BONUS_ORDER_' . date('Ymd');
        $deal_in_redis = $redis_instance->lRange($redis_key, 0, -1);
        foreach ($prj_list as $prj) {
            $is_deal = $prj_order_bonus_summary_model->where(['prj_id' => $prj['id']])->find();
            $total_bonus_money = (int) $this->getPrjOrderBonusTotalAmount($prj['id']);
            if ($is_deal || in_array($prj['id'], $deal_in_redis) || $total_bonus_money == 0) {
                continue;
            }

            $add_result = $prj_order_bonus_summary_model->createPrjBonusOrder($prj['id'], $total_bonus_money);

            if ($add_result === false) {
                echo $prj_order_bonus_summary_model->getError() . PHP_EOL;
            } else {
                $redis_instance->rPush($redis_key, $prj['id']);
                $redis_instance->expire($redis_key, 86400);
            }

            //通知浙商
            /* @var $project_service ProjectService */
            $project_service = service('Financing/Project');

            $zs_bonus_user = M('user')->field('real_name, person_id, zs_bind_serial_no')
                ->where(['uid' => C('ZS_BONUS_UID')])->find();

            try {
                $project_service->noticeBankFreezeUserMoney(
                    $add_result['money'],
                    $add_result['id'],
                    $add_result['order_no'],
                    [
                        'real_name' => $zs_bonus_user['real_name'],
                        'person_id' => $zs_bonus_user['person_id'],
                        'zs_bind_serial_no' => $zs_bonus_user['zs_bind_serial_no'],
                    ],
                    [
                        'id' => $prj['id'],
                        'prj_name' => $prj['prj_name'],
                    ],
                    ['is_prj_order' => 0]
                );
            } catch (Exception $e) {

            }
        }

        return true;
    }

    /**
     * 存管失败 发送通知邮件
     * @param $notice_title
     * @param $notice_message
     */
    public function noticeManager($notice_title, $notice_message)
    {
        $notice_user_emails = C('ZS_REPAYMENT_NOTICE_USERS');
        if (empty($notice_user_emails)) {
            return;
        }
        import_addon("libs.Email.Email");
        foreach ($notice_user_emails as $email) {
            Email::send($email, $notice_title, var_export($notice_message, true), null, 0);
        }
    }

    /**
     * 一个托管项目的所有订单用到的满减券和营销系统的红包总额
     * @param $prj_id
     * @return mixed
     */
    private function getPrjOrderBonusTotalAmount($prj_id)
    {
        $user_bonus_freeze = D('Financing/UserBonusFreeze');
        D('Financing/PrjOrder');
        $total_bonus_amount = $user_bonus_freeze
            ->field('sum(fi_user_bonus_freeze.amount) amount')
            ->join('fi_prj_order on fi_user_bonus_freeze.order_id = fi_prj_order.id')
            ->where([
                'fi_user_bonus_freeze.prj_id' => $prj_id,
                'fi_user_bonus_freeze.type' => ['neq', UserBonusFreezeModel::TYPE_RATE],
                'fi_prj_order.status' => ['neq', PrjOrderModel::STATUS_NOT_PAY],
                'has_freeze' => 0,
            ])
            ->find();

        return $total_bonus_amount['amount'];
    }

    /**
     * 分批次请求浙商换本息 鑫合汇还款成功后调用一次
     * 定时任务检查失败的会继续重试
     * @param $prj_id
     * @return bool
     */
    public function noticeZsRepayWithRange($prj_id)
    {
        $per_send = 200;
        $prj_repay_request_record_model = new PrjRepayRequestRecordModel();
        $condition = [
            'prj_id' => $prj_id,
            'status' => ['in', [
                PrjRepayRequestRecordModel::STATUS_REQUEST_INIT,
                PrjRepayRequestRecordModel::STATUS_REQUEST_FAIL,
                PrjRepayRequestRecordModel::STATUS_RESPONSE_FAIL
            ]],
            'zs_status' => ['in', [
                PrjRepayRequestRecordModel::ZS_STATUS_INIT,
            ]],
        ];

        $notice_data_count = $prj_repay_request_record_model->where($condition)->count();
        if (!$notice_data_count) {
            throw_exception('没有要处理的数据');
        }

        //项目数据
        $prj_info = M('prj')->where(['id' => $prj_id])->find();
        $project_info = $this->getWaitNoticeProjectInfo($prj_info);

        $max_request_times = ceil($notice_data_count / $per_send);
        //本批订单数据
        for ($i = 0; $i * $per_send < $notice_data_count; $i += 1) {
            $notice_data = $prj_repay_request_record_model->where($condition)->limit($per_send)->page($i+1)->select();
            $orders = [];
            foreach ($notice_data as $order) {
                $order = $this->getWaitNoticeProjectOrderInfo($order);
                $orders[] = $order;
            }

            //本期是否上送完毕
            $project_info['count'] = count($notice_data);
            $project_info['debtFee'] = 0; //融资人手续费
            $project_info['batchFlag'] = ($i == $max_request_times - 1 ? 1 : 0);
            $project_info['repayments'] = $orders;

            $post_params = [
                'serviceId' => 'czb_repayment',
                'dataId' => $prj_info['id'] . '_' . $i . '_' . date('YmdHis'),
                'bizSys' => 'xinhehui',
                'async' => 'false',
            ];
            $post_params['params'] = json_encode($project_info, JSON_UNESCAPED_UNICODE);

            //请求浙商
            $http_header = czb_header_sign($post_params);
            $http_body = http_build_query($post_params);
            $result = postData(C('PAYMENT')['zheshang']['config']['getway'], $http_body, $http_header);
            $response_result = $result;

            $prj_repay_request_record_model->startTrans();
            try {
                //请求LOG
                $prj_repay_zs_log_model = new PrjRepayZsLogModel();
                $request_log_id = $prj_repay_zs_log_model->addZsRepayLog(
                    $prj_id, 'request',
                    $http_header, var_export($post_params, true)
                );

                $result = $this->dealRepayResponseRecord($request_log_id, $result, $notice_data);
                if ($result && $project_info['batchFlag'] == 1) {
                    $now = time();
                    M('prj_repay_callback_data')->add([
                        'prj_id' => $prj_id,
                        'request_time' => $now,
                        'ctime' => $now,
                        'mtime' => $now,
                    ]);
                }

                $prj_repay_request_record_model->commit();
            } catch (Exception $e) {
                //记录日志
                \Addons\Libs\Log\Logger::err('存管还款请求失败', 'deposit_repay_fail', json_decode($response_result, true));
                $prj_repay_request_record_model->rollback();
            }

            if ($result === false) {
                return true;
            }
        }

        return true;
    }

    /**
     * 本息还款浙商即时返回的结果处理
     * @param int $request_log_id 日志记录ID
     * @param array $response 浙商响应信息
     * @param array $notice_data
     * @return bool
     */
    public function dealRepayResponseRecord($request_log_id, $response, $notice_data)
    {
        $result = json_decode($response, true);
        $result['value']['details'] = json_decode($result['value']['details'], true);
        $now = time();

        $prj_repay_zs_log_model = new PrjRepayZsLogModel();
        $prj_repay_request_record_model = new PrjRepayRequestRecordModel();

        if ($result === false || $result['code'] != 0) {
            $prj_repay_zs_log_model->where(['id' => $request_log_id])->save([
                'status' => PrjRepayZsLogModel::STATUS_REQUEST_FAIL,
            ]);

            $ids = array_column($notice_data, 'id');
            $prj_repay_request_record_model->where(['id' => ['in', $ids]])->save([
                'status' => PrjRepayRequestRecordModel::STATUS_REQUEST_FAIL,
                'mtime' => $now,
            ]);

            $this->noticeManager(
                '请求浙商还款失败,请重新发起请求',
                $response
            );
            $prj_repay_response_status = PrjRepayZsLogModel::STATUS_REQUEST_FAIL;
        } else {
            $prj_repay_response_status = PrjRepayZsLogModel::STATUS_SUCCESS;
            $response_items = $result['value']['details'];
            foreach ($response_items as $item) {
                $save_data['zs_status'] = $item['status'];
                $save_data['mtime'] = $now;
                if ($item['status'] == PrjRepayRequestRecordModel::ZS_STATUS_SUCCESS) {
                    $save_data['status'] = PrjRepayRequestRecordModel::STATUS_RESPONSE_SUCCESS;
                } else {
                    $save_data['status'] = PrjRepayRequestRecordModel::STATUS_RESPONSE_FAIL;
                }
                $order_no = ltrim($item['p2pSerialNo'], 'XHH');
                $prj_order_id = M('prj_order')->where(['order_no' => $order_no])->getField('id');
                $prj_repay_request_record_model->where(['prj_order_id' => $prj_order_id])->save($save_data);
            }
        }

        $prj_repay_zs_log_model->where(['id' => $request_log_id])->save([
            'status' => $prj_repay_response_status,
            'response_time' => $now,
            'response_message' => var_export($result, true),
            'mtime' => $now,
        ]);

        return $prj_repay_response_status == PrjRepayZsLogModel::STATUS_REQUEST_FAIL ? false : true;
    }

    /**
     * 待通知的项目数据
     * @param $prj_info
     * @return array
     */
    public function getWaitNoticeProjectInfo($prj_info)
    {
        $project_info = [
            'projectId' => $prj_info['id'],
            'projectName' => $prj_info['prj_name'],
            'realName' => $prj_info['corp_name'] ? $prj_info['corp_name'] : $prj_info['person_name'],
            'witnessFee' => 0,
            'flag' => 1,
            'remark' => 'demo_repayment',
        ];

        //是不是最后一期
        $prj_repay_plan_model = new PrjRepayPlanModel();
        $prj_repay_plan = $prj_repay_plan_model->where(['prj_id' => $prj_info['id']])->field('rest_principal')->find();
        if ($prj_repay_plan['rest_principal'] == 0) {
            $project_info['allOver'] = 1;
        } else {
            $project_info['allOver'] = 0;
        }

        return $project_info;
    }

    /**
     * 待通知的项目订单数据
     * @param array $data
     * @return array
     */
    public function getWaitNoticeProjectOrderInfo(array $data)
    {
        $prj_order = M('prj_order')->where(['id' => $data['prj_order_id']])->find();
        $order = [
            'p2pSerialNo' => $prj_order['order_no'],
            'investSerialNo' => $data['bank_serial_no'],
            'type' => '0',
            'bankFlag' => '0',
            'accountNo' => '',
            'accountName' => '',
            'branchNo' => '',
            'amount' => (string) $data['amount'],
            'fee' => '0',
            'extraInterest' => (string) $data['extra_income'], //加息金额
            'currency' => '156',
            'payType' => '2', //1-还息 2-还本付息
        ];

        $investor_real_name = M('user')->where(['uid' => $prj_order['uid']])->getField('real_name');
        //投资人银行卡
        $investor_bank_card = M('fund_account')->where([
            'uid' => $prj_order['uid'],
            'is_active' => 1,
            'zs_bankck' => 1,
        ])->find();

        $order['accountNo'] = $investor_bank_card['zs_bind_bank_card'];
        $order['accountName'] = $investor_real_name;
        $order['branchNo'] = $investor_bank_card['zs_branch_no'];

        return $order;
    }

    /**
     * 发送各种短信
     * @param int $uid
     * @param string $mobile
     * @param $send_type
     * @param string $remark
     */
    public function sendMobileCode($uid = 0, $mobile = '', $send_type, $remark = 'mock mobile message')
    {
        if (empty($mobile)) {
            $mobile = M('user')->where(['uid' => $uid])->getField('mobile');
        }

        if (empty($mobile)) {
            throw_exception('手机号码不正确');
        }

        $send_type = self::mobileCodeMap($send_type);

        import_app('Payment.Service.Payment.ZheShangPayment');
        $obj = new ZheShangPayment();

        $result = $obj->sendCode($mobile, $send_type, $remark);

        if ($result['boolen' == 0]) {
            throw_exception('验证码发送失败');
        }
    }

    /**
     * 短信发送类型
     * @param $send_type
     * @return mixed
     */
    static private function mobileCodeMap($send_type)
    {
        $map_array = [
            'bind_zs_card' => 1, //绑定浙商e卡
            'invest_freeze' => 2, //资金冻结
            'recharge' => 4, //充值
            'cash_out' => 5, //提现
            'transfer' => 6, //转让
            'auto_invest_set' => 7, //自动投标设置
            'unbind' => 8, //解绑
            'modify_mobile' => 9, //修改手机号码
            'modify_account' => 10, //变更存管账户
        ];

        if (!array_key_exists($send_type, $map_array)) {
            throw_exception('未知的发送类型');
        }

        return $map_array[$send_type];
    }

    /**
     * 初始化还款请求浙商的数据
     * @param $prj_id
     * @return bool
     */
    public function initZsRequestData($prj_id)
    {
        $prj_model = D('Financing/Prj');
        $prj_info = $prj_model->where(['id' => $prj_id])->find();

        if ($prj_info['bid_status'] != PrjModel::BSTATUS_ZS_REPAYING) {
            throw_exception('项目状态不是还款完成,不能请求浙商');
        }

        //所有符合条件的订单
        $prj_order_repay_plan_model = new PrjOrderRepayPlanModel();
        $prj_repay_request_record_model = new PrjRepayRequestRecordModel();
        $prj_order_reward_model = new PrjOrderRewardModel();
        $order_pay_status_model = M('order_pay_status');
        $prj_order_repay_plans = $prj_order_repay_plan_model
            ->where([
                'prj_id' => $prj_id,
                'status' => PrjOrderRepayPlanModel::ORDER_REPAYMENT_STATUS_SUCCESS,
                'zs_repay_status' => PrjOrderRepayPlanModel::ZS_REPAY_STATUS_BEFORE_CALLBACK,
                'ptype' => PrjOrderRepayPlanModel::PTYPE_ORG,
                'repay_date' => ['elt', strtotime(date('Ymd'))],
            ])
            ->select();

        $now = time();
        foreach ($prj_order_repay_plans as $prj_order_repay_plan) {
            $trans_key = 'INIT_ZS_DATA' . $prj_order_repay_plan['id'];
            $prj_repay_request_record_model->startTrans($trans_key);

            try {
                $prj_order_pay_status = $order_pay_status_model
                    ->where([
                        'order_id' => $prj_order_repay_plan['prj_order_id'],
                    ])->find();
                if ($prj_order_pay_status['status'] != 4
                    || empty($prj_order_pay_status['bank_serial_no'])) {
                    throw_exception('支付状态不完整,请检查');
                }

                $data = [
                    'prj_id' => $prj_id,
                    'prj_order_id' => $prj_order_repay_plan['prj_order_id'],
                    'amount' => $prj_order_repay_plan['pri_interest'],
                    'bank_serial_no' => $prj_order_pay_status['bank_serial_no'],
                    'repay_time' => $prj_order_repay_plan['repay_date'],
                    'status' => PrjRepayRequestRecordModel::STATUS_REQUEST_INIT,
                    'zs_status' => PrjRepayRequestRecordModel::ZS_STATUS_INIT,
                    'ctime' => $now,
                    'mtime' => $now,
                ];

                if ($prj_order_repay_plan['repay_periods'] == 1) {
                    //第一期把募集期的利息算进去
                    $biding_income = $prj_order_repay_plan_model->where([
                        'prj_order_id' => $prj_order_repay_plan['prj_order_id'],
                        'ptype' => PrjOrderRepayPlanModel::PTYPE_BIDING,
                    ])->getField('pri_interest');
                    $data['amount'] += $biding_income;
                }
                $data['extra_income'] = $prj_order_reward_model->getPrjOrderRewardMoney($prj_order_repay_plan['prj_order_id']);
                $prj_repay_request_record_model->add($data);

                $prj_repay_request_record_model->commit($trans_key);
            } catch (Exception $e) {
                throw_exception($e->getMessage());
                $prj_repay_request_record_model->rollback($trans_key);
            }
            usleep(100);
        }

        //发请求队列
        queue('repay_end_notice_zs', $prj_id, ['prj_id' => $prj_id]);

        return true;
    }
}
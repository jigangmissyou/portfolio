<?php


class PrjEarlyRepayApplyModel extends BaseModel
{
    protected $tableName = 'prj_early_repay_apply';

    const STATUS_TODO = 1;  // 待处理
    const STATUS_SUCCESS = 2;  // 待处理
    const STATUS_FAILED = 3;  // 待处理

    const MSG_TYPE_APPLY = 121; // 申请
    const MSG_TYPE_AUDIT = 122; // 审核


    // 提交提前回款申请
    public function applyEarlyRepay($prj_id, $cuid, $repay_date, $dept_id, $prj_info = null)
    {
        $early_repay_apply = M('prj_early_repay_apply')->where(array('prj_id' => $prj_id))->find();
        if ($early_repay_apply) {
            throw_exception('该项目已经申请过提前还款申请了');
        }

        if (!$prj_info) {
            $prj_info = M('prj')->find($prj_id);
        }
        $prj_ext = M('prj_ext')->find($prj_id);
        if (!$prj_ext || !$prj_ext['is_early_repay']) {
            throw_exception('该项目不能申请提前还款');
        }

        $repay_date = strtotime(date('Y-m-d', $repay_date));
        if ($repay_date < (strtotime(date('Y-m-d')) + 86400)) {
            throw_exception('还款时间必须晚于（含）第二天');
        }
        if ($repay_date >= $prj_info['last_repay_date']) {
            throw_exception('申请提前还款的时间不能等于或超过本身的还款日');
        }

        $prj_default_interest = M('prj_default_interest')->where(array('prj_id' => $prj_id))->order('time_limit_day desc')->find();
        if ($prj_default_interest['time_limit_day'] <= 0) {
            $prj_default_interest['time_limit_day'] = 1;
        }
        $mindate = strtotime('+' . $prj_default_interest['time_limit_day'] . ' days', strtotime(date('Y-m-d', $prj_info['end_bid_time'])));
        if ($repay_date < $mindate) {
            throw_exception('最早只能申请' . date('Y-m-d', $mindate) . '的提前还款');
        }

        $early_repay_data['prj_id'] = $prj_id;
        $early_repay_data['cuid'] = $cuid;
        if ($prj_info['zhr_apply_id']) {
            if ($prj_info['tenant_id'] != $cuid) {
                throw_exception('您无权提交该申请');
            }
            $early_repay_data['tenant_id'] = $cuid;
        } else {
            if ($prj_info['dept_id'] != $dept_id) {
                throw_exception('您无权提交申请');
            }
            $early_repay_data['tenant_id'] = $prj_info['tenant_id'];
        }
        $early_repay_data['dept_id'] = $dept_id;
        $early_repay_data['repay_date'] = $repay_date;
        $early_repay_data['status'] = self::STATUS_TODO;
        $early_repay_data['ctime'] = time();
        $early_repay_data['mtime'] = time();
        $id = M('prj_early_repay_apply')->add($early_repay_data);
        if (!$id) {
            throw_exception('申请失败');
        }

        // 发消息
        $this->sendMsgApply($prj_id);
        return $id;
    }


    // 计算提前还款申请时间范围
    public function getMinMaxTime($prj_id, $prj_info = array())
    {
        if (!$prj_info) {
            $prj_info = M('prj')->find($prj_id);
        }
//        $prj_default_interest = M('prj_default_interest')->where(array('prj_id' => $prj_id))->order('time_limit_day desc')->find();
//        if ($prj_default_interest['time_limit_day'] <= 0) {
//            $prj_default_interest['time_limit_day'] = 1;
//        }
//        $mindate = strtotime('+' . $prj_default_interest['time_limit_day'] . ' days', strtotime(date('Y-m-d', $prj_info['end_bid_time'])));
        $mindate = strtotime(date('Y-m-d', $prj_info['end_bid_time'])) + 86400;
        if(strtotime(date('Y-m-d')) > $mindate) {
            $mindate = strtotime(date('Y-m-d'));
        }
        $mindate += 86400;
        $maxdate = $prj_info['last_repay_date'] - 86400;
        return array('mindate' => $mindate, 'maxdate' => $maxdate);
    }


    // 获取提前还款罚息金额
    public function getEarlyMoney($prj_id, $repay_date, $prj_info = array())
    {
        if (!$prj_info) {
            $prj_info = M('prj')->find($prj_id);
        }
        if (!$repay_date) {
            $repay_date = time();
        }
        $repay_date = strtotime(date('Y-m-d', $repay_date));

        $early_days = $this->getEarlyDays($prj_id, $repay_date);
        $earlyDInParams = $this->getDefaultInterest($prj_id, $early_days);

        $srvProject = service('Financing/Project');
        $interest = $srvProject->profitComputeByDate($earlyDInParams['rate_type'], $earlyDInParams['rate'], $repay_date,
            ($repay_date + $early_days * 86400), $prj_info['demand_amount']);
        $interest = bcmul($interest, bcdiv($earlyDInParams['ratio'], 100, 12), 12);
        $interest = humanMoney($interest, 2, false);
        return array('intere' => $interest, 'ratio' => number_format($earlyDInParams['ratio'], 2));
    }


    // 获取提前还款申请列表
    public function earlyRepaylist($status)
    {
        $obj = M('prj_early_repay_apply');
        if ($status) {
            $parameter['status'] = $status;
            $obj = $obj->where(array('status' => $status));
        }
        $data = $obj->order('id desc')->findPage(10, $parameter);

        $list = array();
        $repay_date = strtotime(date('Y-m-d'));
        foreach ($data['data'] as $early_repay_apply) {
            if ($repay_date < $early_repay_apply['repay_date']) {
                $early_repay_apply['is_show'] = 0;
            }
            $prj_info = M('prj')->find($early_repay_apply['prj_id']);
            if ($repay_date > $prj_info['last_repay_date']) {
                $early_repay_apply['is_show'] = 0;
            }
            if ($early_repay_apply['status'] == self::STATUS_TODO) {
                $early_repay_apply['is_show'] = 1;
            }
            $early_repay_apply['is_show'] = 0;
            $list[] = $early_repay_apply;
        }
        $data['data'] = $list;
        return $data;
    }


    // 审核处 显示是否有提前还款
    public function show3EarlyRepay($prj_id, $repay_date)
    {
        $early_repay_apply = M('prj_early_repay_apply')->where(array('prj_id' => $prj_id))->find();
        if ($repay_date < $early_repay_apply['repay_date']) {
            return array('is_show' => 0);
        }
        $prjInfo = M('prj')->find($prj_id);
        if ($repay_date > $prjInfo['last_repay_date']) {
            return array('is_show' => 0);
        }

        $data = M('prj_early_repay_apply')->where(array('prj_id' => $prj_id, 'status' => self::STATUS_TODO))->find();
        if ($data) {
            return array('is_show' => 1, 'data' => $data);
        } else {
            return array('is_show' => 0);
        }
    }


    // 申请 显示是否有提前还款
    public function show2EarlyRepay($prj_id, $repay_date)
    {
        $early_repay_apply = M('prj_early_repay_apply')->where(array('prj_id' => $prj_id))->find();
        if ($repay_date < $early_repay_apply['repay_date']) {
            return array('is_show' => 0);
        }
        $prjInfo = M('prj')->find($prj_id);
        if ($repay_date > $prjInfo['last_repay_date']) {
            return array('is_show' => 0);
        }

        $data = M('prj_early_repay_apply')->where(array('prj_id' => $prj_id))->find();
        if ($data) {
            return array('is_show' => 0, 'data' => $data);
        } else {
            return array('is_show' => 1);
        }
    }


    //
    public function getEarlyRepay($prj_id)
    {
        $data = M('prj_early_repay_apply')->where(array('prj_id' => $prj_id))->find();
        return $data;
    }


    // 审核处 显示是否有提前还款
    public function hasEarlyRepay($prj_id)
    {
        $has = M('prj_early_repay_apply')->where(array('prj_id' => $prj_id))->find();
        return $has;
    }


    // 审核提前回款申请 2-处理成功 3-失败
    public function dealEarlyRepay($prj_id, $status)
    {
        $early_repay_apply = M('prj_early_repay_apply')->where(array('prj_id' => $prj_id))->find();
        if (!$early_repay_apply) {
            throw_exception('数据不存在');
        }
        if ($early_repay_apply['status'] != self::STATUS_TODO) {
            throw_exception('已经处理了');
        }
        if (strtotime(date('Y-m-d')) > $early_repay_apply['repay_date']) {
            throw_exception('处理时间超过了预期的还款时间');
        }
        if ($status != self::STATUS_SUCCESS && $status != self::STATUS_FAILED) {
            throw_exception('状态异常');
        }
        $early_repay_data['status'] = $status;
        $early_repay_data['id'] = $early_repay_apply['id'];
        $con = M('prj_early_repay_apply')->save($early_repay_data);
        if (!$con) {
            throw_exception('处理失败');
        }

        $project = M('prj')->find($prj_id);
        if (!$project) {
            return false;
        }

        $uid = $project['uid'];
        $prj_default_interest = M('prj_default_interest')->where(array('prj_id' => $prj_id))->order('time_limit_day desc')->find();
        if (!$prj_default_interest) {
            return false;
        }

        if ($prj_default_interest['time_limit_day'] <= 0) {
            $prj_default_interest['time_limit_day'] = 1;
        }
        $mindate = strtotime('+' . $prj_default_interest['time_limit_day'] . ' days', strtotime(date('Y-m-d', $project['end_bid_time'])));
        $ctime = M('prj_early_repay_apply')->where(array('prj_id' => $prj_id))->getField('ctime');
        $repay_date = M('prj_early_repay_apply')->where(array('prj_id' => $prj_id))->getField('repay_date');
        if (!$ctime) {
            return false;
        }

        // 发消息
        $message = array(
            date('Y年m月d日', $ctime),
            $project['prj_name'],
            date('Y年m月d日', $repay_date),
        );
        if ($status == self::STATUS_SUCCESS) {
            $this->sendMsgAudit($uid, $message);
            Import('libs.Queue.Queue', ADDON_PATH);
            Queue::create('SendMesEarlyRepayQueue')->push(array(
                'er_apply_id' => $early_repay_apply['id'],
                'mindate' => date('Y年m月d日', $repay_date)
            ));
        }

        return array('boolen' => 1, 'data' => $early_repay_apply);
    }


    // 单独发成功信息
    public function sendSuccessMsg($apply_id)
    {
        $apply = M('prj_early_repay_apply')->where(array(
            'id' => $apply_id,
            'status' => self::STATUS_SUCCESS
        ))->find();
        if (!$apply) {
            throw_exception('申请不存在或状态不对');
        }
        $project = M('prj')->find($apply['prj_id']);
        if (!$project) {
            throw_exception('项目不存在');
        }

        $uid = $project['uid'];
        $ctime = $apply['ctime'];
        $repay_date = $apply['repay_date'];

        // 发消息
        $message = array(
            date('Y年m月d日', $ctime),
            $project['prj_name'],
            date('Y年m月d日', $repay_date),
        );
        $this->sendMsgAudit($uid, $message);

        return true;
    }


    // 还款管理里面 是否可以显示提前还款的按钮
    public function showEarlyRepay($prj_id, $prjInfo = array())
    {
        $early_repay_apply = M('prj_early_repay_apply')->where(array('prj_id' => $prj_id))->find();
        if (!$early_repay_apply) {
            return array('is_show' => 0);
        }

        if (!$prjInfo) {
            $prjInfo = M('prj')->find($prj_id);
        }
        if (!$prjInfo) {
            throw_exception('项目不存在');
        }
        $time = strtotime(date('Y-m-d'));
        if ($early_repay_apply['status'] == self::STATUS_SUCCESS && $time >= $early_repay_apply['repay_date']/* && $time < $prjInfo['repay_time']*/) {
            return array('is_show' => 1, 'data' => $early_repay_apply);
        }
        return array('is_show' => 0, 'data' => $early_repay_apply);
    }


    // 根据还款日期生成提前还款计划
    public function earlyRepayPlan($prj_id, $repay_date = '', $prj_info = array())
    {
        // TODO: 创客融先不支持提前还款
        $project = M('prj')->where(array('id' => $prj_id))->find();
        if($project['repay_way'] != 'E') {
            throw_exception('当前还款方式不支持提前还款');
        }

        if (!$repay_date) {
            $repay_date = strtotime(date('Y-m-d'));
        }
        $early_days = $this->getEarlyDays($prj_id, $repay_date);
        $earlyDInParams = $this->getDefaultInterest($prj_id, $early_days);
        $earlyDInParams['early_days'] = $early_days;

        $orderList = D('Financing/PrjOrder')->where(array(
            'prj_id' => $prj_id,
            'status' => PrjOrderModel::STATUS_PAY_SUCCESS,
            'repay_status' => array('lt', PrjOrderModel::REPAY_STATUS_ALL_REPAYMENT),
            'is_regist' => 0
        ))->select();

        $obj = service('Financing/Project')->getTradeObj($prj_id, 'QueueTradeJob');
        if (!$obj) {
            throw_exception(MyError::lastError());
        }

        if (count($orderList)) {
            $obj->beforeCreatePrjPlan($prj_id, count($orderList));
        }
        foreach ($orderList as $orderInfo) {
            $params = array(
                'freeze_time' => $orderInfo['freeze_time'],
                'rest_money' => $orderInfo['rest_money'],
                'prj_id' => $prj_id,
                'fromOrderId' => $orderInfo['id'],
                'lastPeriods' => 0,
                'earlyEndTime' => $repay_date,
                'reCreat' => 1,
                'earlyDInParams' => $earlyDInParams,
            );
            $obj->doCreateOrderPlanJob($params);
            if (MyError::hasError()) {
                throw_exception(MyError::lastError());
            }
        }
        S('if_prj_view_' . $prj_id, null); // 清除项目详情页的缓存

        return true;
    }


    // 获取原始还款日与提前还款日的天数差
    private function getEarlyDays($prj_id, $repay_date = '')
    {
        if (!$repay_date) {
            $repay_date = strtotime(date('Y-m-d'));
        }
        $prj_ext = M('prj_ext')->find($prj_id);
        if (!($prj_ext && $prj_ext['old_last_repay_date'])) {
            throw_exception('没设置最后还款时间扩展');
        }
        if ($prj_ext['old_last_repay_date'] <= $repay_date) {
            throw_exception('已经不在提前时间范围内了');
        }
        $early_days = (strtotime(date('Y-m-d', $prj_ext['old_last_repay_date'])) - $repay_date) / 86400;
        return $early_days;
    }


    // 获取罚息比例
    private function getDefaultInterest($prj_id, $early_days, $prj_info = array())
    {
        if (!$prj_info) {
            $prj_info = M('prj')->find($prj_id);
        }

        $prj_ext = M('prj_ext')->find($prj_id);
        if (!$prj_ext['old_last_repay_date']) {
            throw_exception('没设置最后还款时间扩展');
        }
        $_prj_time_limit_day = (strtotime(date('Y-m-d', $prj_ext['old_last_repay_date'])) - strtotime(date('Y-m-d',
                    $prj_info['end_bid_time']))) / 86400;

        $time_limit_day = $_prj_time_limit_day - $early_days;
        $where = array(
            'prj_id' => $prj_id,
            'time_limit_day' => array(array('eq', 0), array('elt', $time_limit_day), 'OR'),
        );
        $prj_default_interest = M('prj_default_interest')->where($where)->order('time_limit_day desc')->find();
        if (!$prj_default_interest) {
            throw_exception($_prj_time_limit_day . ':' . $early_days . '不存在这样的罚息利率');
        }
        return array(
            'rate_type' => $prj_info['rate_type'],
            'rate' => $prj_info['rate'],
            'ratio' => $prj_default_interest['ratio']
        );
    }


    // 通知运营部审核
    private function sendMsgApply($prj_id)
    {
        $title = '提前还款申请';
        $prj_name = M('prj')->where(array('id' => $prj_id))->getField('prj_name');
        $time = date('Y-m-d H:i:s');
        $content = "项目{$prj_name}在{$time}提交了提前还款申请，请及时审核。";
        $emails = 'xiangmuyunyingbu.xinhehui@upg.cn';

        import_addon('libs.Email.Email');
        Email::send($emails, $title, $content, null, 0);
    }


    // 告诉融资者审核通过
    private function sendMsgAudit($uid, $message)
    {
        D('Message/Message')->simpleSend($uid, 1, self::MSG_TYPE_AUDIT, $message, array(1, 2, 3), true);

        // 给财务发通知
        $body = <<<HTML
<h3>($message[1])项目的提前还款申请已审核通过, 请及时关注!</h3>
HTML;
        $mail_addrs = C('EARLY_REPAY_APPLY_NOTICE');
        if (C('APP_STATUS') == 'product') {
            $mail_to = $mail_addrs['PRODUCT'];
        } else {
            $mail_to = $mail_addrs['TEST'];
        }
        if ($mail_to) {
            import_addon('libs.Email.Email');
            Email::send($mail_to, '提前还款审核通过', $body, null, 0);
        }
    }


    public function sendRepayPlanMail($prj_id) {
        $apply = M('prj_early_repay_apply')->field('repay_date')->where(array(
            'status' => self::STATUS_SUCCESS,
            'prj_id' => $prj_id
        ))->find();
        if (!$apply) {
            return;
        }

        $prj = M('prj')->field(array(
            'id',
            'uid',
            'prj_name',
            'time_limit_day',
            'prj_type',
            'year_rate',
            'activity_id',
            'value_date_shadow',
            'last_repay_date',
            'corp_name',
        ))->where(array('id' => $prj_id))->find();

        $prj_ext = M('prj_ext')->field(array(
            'is_early_repay',
            'early_repay_days',
            'is_extend',
            'extend_time',
            'extend_time_unit',
            'extend_day_repayed',
            'old_last_repay_date',
            'acount_name',
        ))->where(array('prj_id' => $prj_id))->find();

        $plans = M('prj_order_repay_plan')->field(array(
            'ptype',
            'sum(yield)     AS yield',
            'sum(principal) AS principal',
        ))->where(array(
            'prj_id' => $prj_id,
            'status' => array('NEQ', 4),
            'repay_date' => $apply['repay_date']
        ))->group('ptype')->select();

        $plan_reward = M('prj_order_reward')->field('sum(amount) AS amount')->where(array(
            'prj_id' => $prj_id,
            'reward_type' => 1,
            'status' => array('NEQ', 4),
        ))->find();

        $user = M('user')->field('uname')->where(array('uid' => $prj['uid']))->find();

        $repay_date = date('Y/m/d', $apply['repay_date']);
        $early_repay_days = (strtotime(date('Y-m-d', $prj_ext['old_last_repay_date'])) - strtotime(date('Y-m-d', $apply['repay_date']))) / 86400;
        $prj_type = service('Financing/Project')->getPrjTypeName($prj['prj_type']);
        $year_rate = number_format($prj['year_rate']/10, 2, '.', '');
        $last_repay_date = date('Y/m/d', $prj['last_repay_date']);
        $is_extend = $prj_ext['is_extend'] ? '是' : '否';
        $is_early_repay = $prj_ext['is_early_repay'] ? '是' : '否';
        $old_last_repay_date = date('Y/m/d', $prj_ext['old_last_repay_date']);

        $principal = 0;
        $income = 0;
        $income_biding = 0;
        $income_hongbao = $plan_reward['amount'];
        foreach ($plans as $item) {
            if($item['ptype'] == 2) {
                $income_biding += $item['yield'];
            } else {
                $income += $item['yield'];
            }
            $principal += $item['principal'];
        }
        $total = $principal + $income + $income_biding;

        $total = humanMoney($total, 2, false);
        $principal = humanMoney($principal, 2, false);
        $income = humanMoney($income, 2, false);
        $income_biding = humanMoney($income_biding, 2, false);
        $income_hongbao = humanMoney($income_hongbao, 2, false);

        if($prj_ext['acount_name']) {
            $acount_name = $prj_ext['acount_name'];
        } else {
            $_ext = service('Financing/Project')->getExt($prj_id);
            $acount_name = $_ext['fund_account']['acount_name'];
        }

        $html = <<<HTML
<h2>提前还款提现</h2>
<h3>{$prj['prj_name']}项目已经生成提前还款计划，请关注!</h3>
<table border="1">
    <thead>
    <tr>
        <td>还款日期</td>
        <td>融资账号</td>
        <td>入账账户</td>
        <td>项目名</td>
        <td>融资人</td>
        <td>项目期限(天数)</td>
        <td>项目类型</td>
        <td>利率</td>
        <td>还款日(T+n)</td>
        <td>还款总金额</td>
        <td>还款本金</td>
        <td>还款利息</td>
        <td>募集期资金占用费</td>
        <td>年化红包</td>
        <td>是否展期</td>
        <td>展期时间</td>
        <td>最迟还款日</td>
        <td>是否可提前还款</td>
        <td>提前天数(实际)</td>
        <td>提前还款日期(实际)</td>
    </tr>
    </thead>
    <tbody>
    <tr>
        <td>{$repay_date}</td>
        <td>{$user['uname']}</td>
        <td>{$acount_name}</td>
        <td>{$prj['prj_name']}</td>
        <td>{$prj['corp_name']}</td>
        <td>{$prj['time_limit_day']}</td>
        <td>{$prj_type}</td>
        <td>{$year_rate}%</td>
        <td>T+{$prj['value_date_shadow']}</td>
        <td>{$total}</td>
        <td>{$principal}</td>
        <td>{$income}</td>
        <td>{$income_biding}</td>
        <td>{$income_hongbao}</td>
        <td>{$is_extend}</td>
        <td>{$prj_ext['extend_time']}</td>
        <td>{$old_last_repay_date}</td>
        <td>{$is_early_repay}</td>
        <td>{$early_repay_days}</td>
        <td>{$repay_date}</td>
    </tr>
    </tbody>
</table>
HTML;

        $mail_addrs = C('EARLY_REPAY_APPLY_NOTICE');
        if (C('APP_STATUS') == 'product') {
            $mail_to = $mail_addrs['PRODUCT'];
        } else {
            $mail_to = $mail_addrs['TEST'];
        }
        if ($mail_to) {
            import_addon('libs.Email.Email');
            Email::send($mail_to, '提前还款计划已生成', $html, null, 0);
        }
    }
}

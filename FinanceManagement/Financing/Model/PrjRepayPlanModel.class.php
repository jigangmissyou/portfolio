<?php

class PrjRepayPlanModel extends BaseModel {
    protected $tableName = 'prj_repay_plan';
   	
    const PRJ_REPAYMENT_STATUS_WAIT = 1;//待还款
    const PRJ_REPAYMENT_STATUS_REPAYMENTING = 2;//还款中
    const PRJ_REPAYMENT_STATUS_SUCCESS = 3;//还款结束
    const PRJ_REPAYMENT_STATUS_FAIL= 4;//废弃，不还款

    const OVERDUE_STATUS_A = 1; //未申请逾期
    const OVERDUE_STATUS_B = 2; //申请待审核
    const OVERDUE_STATUS_C = 5; //已代偿
    const OVERDUE_STATUS_D = 6; //已逾期
    const OVERDUE_STATUS_E = 7; //待追偿
    const OVERDUE_STATUS_F = 8; //已追偿

    const ZS_REPAY_STATUS_INIT= 0; //初始化
    const ZS_REPAY_STATUS_BEFORE_CALLBACK = 1; //鑫合汇还款后接受浙商通知前
    const ZS_REPAY_STATUS_ING = 10; //浙商处理中
    const ZS_REPAY_STATUS_SUCCESS = 20; //浙商还款成功
    const ZS_REPAY_STATUS_FAIL = 30; //浙商还款失败

    /**
     * 获取一个项目的还款本金 还款利息 借款期限 还款时间等
     *
     * @param $prj_id
     */
    public function getPrjInfoById($prj_id){
        $result = array();
        $finance_service = service('Financing/Financing');
        $project_service = service('Financing/Project');

        //借款金额
        $result['amount'] = number_format($finance_service->getOnline($prj_id) / 100, 2);

        //其他
        $child_ids = $project_service->getChildrenId($prj_id);

        $where = array(
            'prj_id' => array('in', $child_ids),
            'status' => array('neq', self::PRJ_REPAYMENT_STATUS_FAIL),
        );
        $repay_plan = $this->where($where)
            ->field('sum(principal) principal, sum(yield) yield, repay_date, status')
            ->find();

        $principal_yield = $repay_plan['principal'] + $repay_plan['yield'];

        $result['principal'] = number_format($repay_plan['principal'] / 100, 2);
        $result['yield'] = number_format($repay_plan['yield'] / 100, 2);
        $result['principal_yield'] = number_format($principal_yield / 100, 2);
        $result['repay_date_show'] = date('Y-m-d', $repay_plan['repay_date']);
        $result = array_merge($result, $this->getBtnShow($prj_id, $repay_plan));

        return $result;
    }

    /**
     * 创客融项目最新一起还款信息
     * @param $prj_id
     * @param $status 还款状态 1-待还款 2-已还款
     */
    public function getCkrPrjInfoById($prj_id, $status)
    {
        $project_service = service('Financing/Project');

        //其他
        $child_ids = $project_service->getChildrenId($prj_id);

        $condition = array(
            'prj_id' => array('in', $child_ids),
        );

        //总期数 阶段
        $total_periods = $this->getTotalRepayPeriodsOfPrj($prj_id);

        $condition['status'] = array('eq', $status == 1 ?
            self::PRJ_REPAYMENT_STATUS_WAIT :
            self::PRJ_REPAYMENT_STATUS_SUCCESS);

        //最新一期信息
        $order = 'repay_date' . ($status == 1 ? '' : ' DESC');
        $newest_repay_plan = $this->where($condition)->order($order)->find();
        
        //本金 利息 平台服务费 融资服务费 剩余本金 借款期限 剩余还款期数
        $result['repay_date'] = date('Y-m-d', $newest_repay_plan['repay_date']);
        $result['principal_yield'] = number_format(($newest_repay_plan['pri_interest'] + $newest_repay_plan['fee'] + 
                $newest_repay_plan['org_fee'] + $newest_repay_plan['default_interest'] + $newest_repay_plan['late_fees']) / 100, 2);
        $result['pri_interest'] = number_format($newest_repay_plan['pri_interest'] / 100, 2);
        $result['principal'] = number_format($newest_repay_plan['principal'] / 100, 2);
        $result['yield'] = number_format($newest_repay_plan['yield'] / 100, 2);
        $result['rest_principal'] = number_format($newest_repay_plan['rest_principal'] / 100, 2);
        $result['fee'] = number_format($newest_repay_plan['fee'] / 100, 2);
        $result['org_fee'] = number_format($newest_repay_plan['org_fee'] / 100, 2);
        $result['rest_repay_periods'] = $total_periods - $newest_repay_plan['repay_periods'];
        $result['default_interest'] = number_format($newest_repay_plan['default_interest'] / 100, 2); //罚息
        $result['late_fees'] = number_format($newest_repay_plan['late_fees'] / 100, 2); //滞纳金
        $result = array_merge($result, $this->getBtnShow($prj_id, $newest_repay_plan));

        return $result;
    }

    /**
     * 创客融还款列表明细
     * @param $prj_id
     * @param $status 还款状态 1-待还款 2-已还款
     * @param $page
     * @param $per_page
     * @return array
     */
    public function ckrRepayPrjDetailList($prj_id, $status, $page = 1, $per_page = 10)
    {
        D('Financing/Prj');
        $condition = array(
            't1.prj_id' => $prj_id,
        );

        if ($status == 1) {
            $condition['_string'] = 't1.`status` = ' . self::PRJ_REPAYMENT_STATUS_WAIT . ' OR (t1.`status` = ' . self::PRJ_REPAYMENT_STATUS_SUCCESS . '
            AND t2.prj_business_type = "' . PrjModel::PRJ_BUSINESS_TYPE_H . '"
            AND t1.overdue_status IN (' . self::OVERDUE_STATUS_C .', ' . self::OVERDUE_STATUS_D  .', ' . self::OVERDUE_STATUS_E .'))';
        } else {
            $condition['_string'] = 't1.`status` = ' . self::PRJ_REPAYMENT_STATUS_SUCCESS . ' AND (t2.prj_business_type = "' . PrjModel::PRJ_BUSINESS_TYPE_H . '" AND
            (t1.overdue_status is null OR t1.overdue_status = ' . self::OVERDUE_STATUS_F . ') OR t2.prj_business_type <> "' . PrjModel::PRJ_BUSINESS_TYPE_H . '")';;
        }

        $total = M()->table('fi_prj_repay_plan t1')
            ->join('left join fi_prj t2 on t1.prj_id = t2.id')
            ->where($condition)
            ->count();

        $total_pages = ceil($total / $per_page);
        if ($page > $total_pages) {
            $page = $total_pages;
        }

        $order = 'repay_date' . ($status == 1 ? '' : ' DESC');
        $list = M()->table('fi_prj_repay_plan t1')
            ->join('left join fi_prj t2 on t1.prj_id = t2.id')
            ->where($condition)
            ->field('t1.*, t2.time_limit, t2.time_limit_unit, t2.prj_business_type')
            ->page($page)->limit($per_page)
            ->order($order)->select();
            
        $result = array();
        $project_service = service('Financing/InvestFund');
        if ($list) {
            $total_periods = $this->getTotalRepayPeriodsOfPrj($prj_id);
            foreach ($list as $key => $value) {
                $result[] = array_merge(array(
                    'prj_id' => $prj_id,
                    'time_limit_show' => $project_service->getPrjTime($value['time_limit'], $value['time_limit_unit']),
                    'repay_date' => date('Y-m-d', $value['repay_date']),
                    'principal_yield' => number_format(($value['pri_interest'] + $value['fee'] + $value['org_fee'] + $value['default_interest'] + $value['late_fees']) / 100, 2),
                    'pri_interest' => number_format($value['pri_interest'] / 100, 2),
                    'principal' => number_format($value['principal'] / 100, 2),
                    'yield' => number_format($value['yield'] / 100, 2),
                    'rest_principal' => number_format($value['rest_principal'] / 100, 2),
                    'fee' => number_format($value['fee'] / 100, 2),
                    'org_fee' => number_format($value['org_fee'] / 100, 2),
                    'default_interest' => number_format($value['default_interest'] / 100, 2), //罚息
                    'late_fees' => number_format($value['late_fees'] / 100, 2), //滞纳金
                    'rest_repay_periods' => $total_periods - $value['repay_periods'],
                    'prj_business_type' => $value['prj_business_type'],
                ), $this->getBtnShow($prj_id, $value));
            }
        }

        $result = array(
            'page' => $page,
            'total_page' => $total_pages,
            'total' => $total,
            'list' => $result,
        );
        
        return $result;
    }

    /**
     * 按钮显示
     * @param $prj_id 项目ID
     * @param $repay_plan 项目还款计划
     * @return array
     */
    public function getBtnShow($prj_id, $repay_plan)
    {
        D('Financing/Prj');

        $project = M('prj')->where(array('id' => $prj_id))->field('id, spid, have_children, prj_business_type')->find();
        $prj_ext = M('prj_ext')->where(array('prj_id' => $prj_id))->field('old_last_repay_date, is_early_repay')->find();

        //是否支持自动还款 目前只有创客融 自动还款的按钮不一样
        $auto_repayable = $project['prj_business_type'] == PrjModel::PRJ_BUSINESS_TYPE_H;

        //提前还款
        if (in_array($repay_plan['status'], array(self::PRJ_REPAYMENT_STATUS_WAIT, self::PRJ_REPAYMENT_STATUS_REPAYMENTING))) {
            $ymd = date('Ymd');
            $repay_date_ymd = date('Ymd', $repay_plan['repay_date']);
            $repay_date_ymd_old = date('Ymd', $prj_ext['old_last_repay_date']); // 原始的最后还款时间

            if($ymd == $repay_date_ymd) {
                $result['time_remind'] = '';
                if ($auto_repayable) {
                    if (is_null($repay_plan['overdue_status'])) {
                        if ($prj_ext['is_early_repay']) {
                            $result['show_type'] = 6; // 提前还款
                        } else {
                            $result['show_type'] = 22; // 合计还款
                        }
                    } else {
                        if (in_array($repay_plan['overdue_status'], array(
                            self::OVERDUE_STATUS_A,
                            self::OVERDUE_STATUS_B,
                            self::OVERDUE_STATUS_D
                        ))) {
                            $result['show_type'] = 22; // 合计还款
                        }
                    }
                } else {
                    $result['show_type'] = 1;
                }

                if(!$project['have_children']) {
                    $mdPrjEarlyRepayApply = D('Financing/PrjEarlyRepayApply');
                    if($prj_ext['is_early_repay']) {
                        $early_repay = $mdPrjEarlyRepayApply->hasEarlyRepay($project['id']);
                        if($early_repay) { // 有提前还款申请
                            if($early_repay['status'] == 2) {
                                $result['early_repay_pass'] = 2;
                            } else {
                                $result['early_repay_pass'] = 1;
                            }
                        }
                    }
                }
                return $result;
            }

            // 过期了 是否展期 展期暂不考虑创客融自动还款的
            if($ymd > $repay_date_ymd_old) {
                $prj_repay_extend_service = service('Financing/PrjRepayExtend');
                if($prj_repay_extend_service->isShowExtend($project['id'])) {
                    $result['show_type'] = 4; // 重新生成还款计划(展期)
                    $result['extend_days'] = ((strtotime($ymd) - strtotime($repay_date_ymd)) / 86400);
                } else {
                    $result['show_type'] = 5; // 灰掉
                }
            } else {
                $is_show = 0;
                if(!$project['have_children']) {
                    $mdPrjEarlyRepayApply = D('Financing/PrjEarlyRepayApply');
                    $repay_date = strtotime(date('Y-m-d'));
                    if($prj_ext['is_early_repay']) {
                        $is_show = $mdPrjEarlyRepayApply->show2EarlyRepay($project['id'], $repay_date);
                        $is_show = $is_show['is_show'];

                        $early_repay = $mdPrjEarlyRepayApply->hasEarlyRepay($project['id']);
                        if($early_repay) { // 有提前还款申请
                            if($early_repay['status'] == 2) {
                                $result['early_repay_pass'] = 2;
                                if(strtotime(date('Y-m-d')) >= $early_repay['repay_date']) { // 到了时间
                                    $result['show_type'] = 7; // 重新生成还款计划(提前)
                                    return $result;
                                }
                            } else {
                                $result['show_type'] = 8; // 提前还款申请未通过
                                $result['early_repay_status'] = $early_repay['status']; // 1未处理, 3不通过
                                $result['early_repay_pass'] = 1;
                                return $result;
                            }
                        }
                    }
                }
                if($is_show) {
                    $result['show_type'] = 6; // 提前还款申请
                } else {
                    if ($auto_repayable) {
                        $result['show_type'] = 22;
                    } else {
                        $result['show_type'] = 2; // 显示剩余时间
                        $result['time_remind'] = ((strtotime($repay_date_ymd) - strtotime($ymd)) / 86400) . '天';
                    }
                }
            }
        } else {
            if (is_null($repay_plan['overdue_status'])) {
                $result['show_type'] = 25; // 正常还款
            } else {
                if ($auto_repayable) {
                    if ($repay_plan['overdue_status'] == self::OVERDUE_STATUS_F) {
                        $result['show_type'] = 24; // 代偿已还款
                    } elseif (in_array($repay_plan['overdue_status'], array(
                        self::OVERDUE_STATUS_C,
                        self::OVERDUE_STATUS_E,
                    ))) {
                        $result['show_type'] = 23; // 代偿未还款
                    }
                } else {
                    $result['show_type'] = 26; //已代偿
                }
            }
        }

        return $result;
    }

    /**
     * 项目总还款期数
     * @param $prj_id
     * @return mixed
     */
    public function getTotalRepayPeriodsOfPrj($prj_id)
    {
        $condition = array(
            'prj_id' => $prj_id,
            'status' => array('neq', self::PRJ_REPAYMENT_STATUS_FAIL),
        );
        $total_periods = $this->where($condition)->count();
        return $total_periods;
    }

    /**
     * 还款计划ID获取还款计划
     * @param $prj_repay_plan_id 还款计划ID
     * @return array
     */
    public function getPrjRepayInfoById($prj_repay_plan_id)
    {
        $result = $this->where(array(
            'id' => $prj_repay_plan_id,
            'status' => array('neq', self::PRJ_REPAYMENT_STATUS_FAIL,
        )))->find();

        return $result;
    }
    
    /**
     * 获取项目回款浙商状态
     * @param $prj_id 项目id
     * @return array
     */
    public function getZsRepayStatus( $prj_id )
    {
        $result = $this->where(array(
            'prj_id' => $prj_id,
            'zs_repay_status' => array('in', array(self::ZS_REPAY_STATUS_BEFORE_CALLBACK, self::ZS_REPAY_STATUS_ING)
            )))->getField('id');

        return $result;
    }
}

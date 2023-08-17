<?php
/**
 * 项目展期相关服务
 * 预约服务协议：47
 * 预约展期借款合同：48
 *
 * User: channing
 * Date: 15-4-14
 * Time: 下午2:08
 */
class PrjRepayExtendService extends BaseService {
    const LIMIT_UNIT_DAY = 'day';        // 期限单位
    const LIMIT_UNIT_MONTH = 'month';
    const LIMIT_UNIT_YEAR = 'year';
    const CACHE_KEY_GEN_REPAY_PLAN = 'GEN_EXTEND_REPAY_PLAN_';
    const TIME_LIMIT_COMMENT = "展期指借款人延期偿还借款的期限。本项目可能存在展期，展期的最大期限为A天，展期期间的借款利率不变。";

    private $mdPrj;
    private $mdPrjOrder;
    private $mdPrjOrderRepayPlan;
    private $mdPrjRepayPlan;
    private $mdPrjExt;
    private $srvProject;

    function __construct() {
        parent::__construct();

        $this->mdPrj = D('Financing/Prj');
        $this->mdPrjOrder = D('Financing/PrjOrder');
        $this->mdPrjOrderRepayPlan = D('Financing/PrjOrderRepayPlan');
        $this->mdPrjRepayPlan = D('Financing/PrjRepayPlan');
        $this->mdPrjExt = M('prj_ext');
        $this->srvProject = service('Financing/Project');
    }

    private function getCodeItemName($code_key, $code_no) {
        $ret = getCodeItemName($code_key, $code_no);
        if($code_key == self::LIMIT_UNIT_MONTH) $ret = "个{$ret}";

        return $ret;
    }

    // 是否展期
    public function isExtend($prj_id) {
        return (int)$this->mdPrjExt->where(array('prj_id' => $prj_id))->getField('is_extend');
    }


    // 获取最大展期时间
    public function getMaxExtendTime($prj_id) {
        $project = $this->mdPrj->where(array('id' => $prj_id))->field('id,status,bid_status')->find();
        if(!$project) return 0;

        $_last_repay_date = $this->srvProject->getPrjEndTime($prj_id);
        $last_repay_date = strtotime(date('Y-m-d', $_last_repay_date));
        $ext = $this->mdPrjExt->where(array('prj_id' => $prj_id))->field('is_extend,extend_time,extend_time_unit')->find();
        if(!$ext || !$ext['is_extend'] || !$ext['extend_time'] || !in_array($ext['extend_time_unit'], array(self::LIMIT_UNIT_DAY, self::LIMIT_UNIT_MONTH, self::LIMIT_UNIT_YEAR))) return $last_repay_date;

        return strtotimeX("+{$ext['extend_time']} {$ext['extend_time_unit']}", $last_repay_date);
    }


    // 返回项目期限展示
    public function getTimeLimitShow($prj_id, $is_uni=FALSE) {
        $ret = array(
            'time_limit' => '',                  // 原期限
            'time_limit_unit' => '',             // 原期限单位
            'uni_symbol' => '',                  // 连接符
            'time_limit_extend' => '',           // 展期期限
            'time_limit_extend_unit' => '',      // 展期期限单位
        );
        $project = $this->mdPrj->where(array('id' => $prj_id))->field('id,status,bid_status,time_limit,time_limit_unit')->find();
        if(!$project) return $is_uni ? '' : $ret;

        $ret['time_limit'] = $project['time_limit'];
        $ret['time_limit_unit'] = $this->getCodeItemName('E002', $project['time_limit_unit']);

        $ext = $this->mdPrjExt->where(array('prj_id' => $prj_id))->field('is_extend,extend_time,extend_time_unit,extend_day_repayed')->find();
        if($ext && $ext['is_extend'] && $ext['extend_time']) {
            if(!in_array($project['bid_status'], array(PrjModel::BSTATUS_REPAY_IN, PrjModel::BSTATUS_REPAID))) {
                $ret['uni_symbol'] = '+';
                $ret['time_limit_extend'] = $ext['extend_time'];
                $ret['time_limit_extend_unit'] = $this->getCodeItemName('E002', $ext['extend_time_unit']);
            } else {
                // 实际展期天数
                if(0 && $ext['extend_time_unit'] == $project['time_limit_unit']) {
                    $ret['uni_symbol'] = '';
                    $ret['time_limit'] += $ext['extend_day_repayed'];
                } elseif($ext['extend_day_repayed'] > 0) {
                    $ret['uni_symbol'] = '+';
                    $ret['time_limit_extend'] = $ext['extend_day_repayed'];
                    $ret['time_limit_extend_unit'] = $this->getCodeItemName('E002', 'day');
                }
            }
        }

        if($is_uni) $ret = implode('', array_values($ret));

        return $ret;
    }


    // 重新生成一个还款计划
    private function genRepayPlanOne($prj_id, $extend_to=NULL) {
        $this->genRepayPlanCache($prj_id);

        if(!$extend_to) $extend_to = time();
        $extend_to = strtotime(date('Y-m-d', $extend_to));
        $project = $this->mdPrj->where(array('id' => $prj_id))->field('id,status,bid_status,repay_way')->find();
        if(!$project) throw_exception("项目不存在: {$prj_id}");
        if($project['repay_way'] != PrjModel::REPAY_WAY_E) {
            throw_exception('当前还款方式不支持展期还款');
        }
        if($project['bid_status'] != PrjModel::BSTATUS_REPAYING) throw_exception("项目状态不是待还款状态: {$prj_id}, {$project['bid_status']}");

        if(!$this->isExtend($prj_id)) throw_exception("项目不支持展期: {$prj_id}");

        $_last_repay_date = $this->srvProject->getPrjEndTime($prj_id);
        $max_extend_time = $this->getMaxExtendTime($prj_id);
        $max_extend_time_display = date('Y-m-d', $max_extend_time);
        $last_repay_date_display = date('Y-m-d', $_last_repay_date + 86400);
        $last_repay_date = $_last_repay_date;
        if(!($extend_to >= $last_repay_date + 86400 && $extend_to < $max_extend_time + 86400)) throw_exception("当前时间不在展期范围内({$last_repay_date_display} - {$max_extend_time_display}), $prj_id");

        if($this->mdPrjRepayPlan->where(array(
            'prj_id' => $prj_id,
            'repay_date' => $extend_to,
            'status' => array('NEQ', PrjRepayPlanModel::PRJ_REPAYMENT_STATUS_FAIL),
        ))->getField('id')) throw_exception('今天已经生成过展期还款计划, 无需重新生成');

        // 生成还款计划
        $where_order = array(
            'prj_id' => $prj_id,
            'status' => PrjOrderModel::STATUS_PAY_SUCCESS,
            'repay_status' => array('LT', PrjOrderModel::REPAY_STATUS_ALL_REPAYMENT),
            'is_regist' => 0,
        );
        // 生成订单还款计划
        $orders = $this->mdPrjOrder->where($where_order)->select();

        if(!$orders) return TRUE;
        $oBid = $this->srvProject->getTradeObj($prj_id, 'Bid');
        if(!$oBid) throw_exception(MyError::lastError());

        $oQueueTradeJob = $this->srvProject->getTradeObj($prj_id, 'QueueTradeJob');
        if(!$oQueueTradeJob) throw_exception(MyError::lastError());

        if(count($orders)) $oQueueTradeJob->beforeCreatePrjPlan($prj_id, count($orders));
        foreach($orders as $order) {
            $params = array(
                'freeze_time' => $order['freeze_time'],
                'rest_money' => $order['rest_money'],
                'prj_id' => $order['prj_id'],
                'fromOrderId' => $order['id'],
                'lastPeriods' => 0,
                'reCreat' => 1,
                'extend_to' => $extend_to,
            );
            $oQueueTradeJob->doCreateOrderPlanJob($params);
            if(MyError::hasError()) throw_exception(MyError::lastError());
        }

        // 生成项目还款计划
//        import_addon('trade.Permonth.Permonth_Pay');
//        $Pay = new Permonth_Pay();
//        $Pay->debug = TRUE; // 不走队列
//        $Pay->savePrjRepaymentPlan(array('prj_id' => $prj_id));

        // 实际展期天数
        $old_last_repay_date = $this->mdPrjExt->where(array('prj_id' => $prj_id))->getField('old_last_repay_date');
        $extend_day_repayed = max(0, ($extend_to - strtotime(date('Y-m-d', $old_last_repay_date))) / 86400);
        if(FALSE === $this->mdPrjExt->where(array('prj_id' => $prj_id))->save(array('extend_day_repayed' => $extend_day_repayed))) throw_exception('修改实际展期天数失败');

        S('if_prj_view_' . $prj_id, NULL); // 清除项目详情页的缓存

        $this->genRepayPlanCache($prj_id, NULL);

        return TRUE;
    }


    // 获取待还款子id
    private function getChildrenIds($prj_id) {
        $project = $this->mdPrj->where(array('id' => $prj_id))->field('have_children,spid')->find();
        if(!$project['have_children']) return array($prj_id);

        $rows = $this->mdPrj->where(array(
            'spid' => $prj_id,
            'status' => PrjModel::STATUS_PASS,
            'bid_status' => array('IN', array(PrjModel::BSTATUS_REPAYING, PrjModel::BSTATUS_REPAY_IN)),
        ))->field('id')->select();
        $ids = array();
        foreach ($rows as $row) {
            $ids[] = $row['id'];
        }

        return $ids;
    }


    // 缓存并发控制
    private function genRepayPlanCache($prj_id, $value=1) {
        $key = self::CACHE_KEY_GEN_REPAY_PLAN . $prj_id;
        if(!is_null($value) && S($key)) throw_exception('正在生成中, 请不要重复提交');

        S($key, $value, 1800); // 30分钟
        return TRUE;
    }


    private function isShowExtendOne($prj_id) {
        $is_extend = $this->isExtend($prj_id);
        if(!$is_extend) return FALSE;

        $extend_to = strtotime(date('Y-m-d', time()));
        $max_extend_time = $this->getMaxExtendTime($prj_id);
        $project = $this->mdPrj->where(array('id' => $prj_id))->field('id,status,bid_status,last_repay_date')->find();
        $last_repay_date = strtotime(date('Y-m-d', $project['last_repay_date']));
        $is_have_repay = $this->mdPrjRepayPlan->where(array(
            'prj_id' => $prj_id,
            'repay_date' => $extend_to,
            'status' => array('NEQ', PrjRepayPlanModel::PRJ_REPAYMENT_STATUS_FAIL),
        ))->getField('id');
        if(!$is_have_repay && in_array($project['bid_status'], array(PrjModel::BSTATUS_REPAYING, PrjModel::BSTATUS_REPAY_IN)) && $extend_to >= $last_repay_date + 86400 && $extend_to < $max_extend_time + 86400) return TRUE;

        return FALSE;
    }


    // 生成所有还款计划
    public function genRepayPlan($spid, $extend_to=NULL) {
        $ids = $this->getChildrenIds($spid);
        foreach ($ids as $prj_id) {
            $this->genRepayPlanOne($prj_id, $extend_to);
        }

        return TRUE;
    }


    // 清除并发缓存
    public function clearRepayPlanCache($spid) {
        $ids = $this->getChildrenIds($spid);
        foreach ($ids as $prj_id) {
            $this->genRepayPlanCache($prj_id, NULL);
        }

        return TRUE;
    }


    // 是否要重新生成展期还款计划
    public function isShowExtend($spid) {
        $ids = $this->getChildrenIds($spid);
        foreach ($ids as $prj_id) {
            if($this->isShowExtendOne($prj_id)) return TRUE;
        }

        return FALSE;
    }


    public function getLastRepayDate($spid) {
        $ids = $this->getChildrenIds($spid);
        foreach ($ids as $prj_id) {
            return $this->mdPrj->where(array('id' => $prj_id))->getField('last_repay_date');
        }
    }
}

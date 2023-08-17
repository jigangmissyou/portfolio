<?php
/**
 * User: 000802
 * Date: 2013-11-18 10:38
 * $Id$
 *
 * Type: Action
 * Group: Account
 * Module: 融资记录
 * Description:
 */

class FinancingRecordAction extends BaseAction {
    const PAGESIZE = 10;

    private $uid = 0;
    private $project_mapping = array();
    private $transfer_mapping = array();
    private $corpfinancing_mapping = array();
    private $time_limit_display = array();      // 11天、12个月的区别
    private $corpfinancing_status_display = array();
    private $prj_bid_status_display = array();

    public function _initialize() {
        parent::_initialize();
        $this->uid = $this->loginedUserInfo['uid'];

        // 初始化筛选条件
        $now = time();
        D('Financing/Prj');
        D('Financing/AssetTransfer');
        D('Financing/CorpFinancing');
        $this->time_limit_display = array(
            PrjModel::LIMIT_UNIT_DAY => '天',
            PrjModel::LIMIT_UNIT_MONTH => '个月',
        );
        $this->project_mapping = array(
            // 项目类型
            'PRJ_TYPE' => array(
                // 全部
                '0' => array(
                    'transfer_id' => array('EXP', 'IS NULL'),
                ),
                // 日升益(短期借贷)
                '1' => array(
                    'prj_type' => PrjModel::PRJ_TYPE_A,
                    'transfer_id' => array('EXP', 'IS NULL'),
                ),
                // 年益升(中长期借贷)
                '2' => array(
                    'prj_type' => PrjModel::PRJ_TYPE_B,
                    'transfer_id' => array('EXP', 'IS NULL'),
                ),
//                // 稳保益(私募基金)
//                '3' => array(
//                    'prj_type' => PrjModel::PRJ_TYPE_C,
//                ),
//                // 抵融益
//                '4' => array(
//                    'prj_type' => PrjModel::PRJ_TYPE_D,
//                ),
                // 聚优宝--->月益升
                '5' => array(
                    'prj_type' => PrjModel::PRJ_TYPE_F,
                    'transfer_id' => array('EXP', 'IS NULL'),
                ),
            ),
            // 项目状态
            'PRJ_STATUS' => array(
                // 审核未通过
                '1' => array(
                    'status' => PrjModel::STATUS_FAILD,
                ),
                // 待开标
                '2' => array(
                    'status' => PrjModel::STATUS_PASS,
                    'bid_status' => PrjModel::BSTATUS_WATING,
                ),
                // 投标中
                '3' => array(
                    'status' => PrjModel::STATUS_PASS,
                    'bid_status' => PrjModel::BSTATUS_BIDING,
                ),
                // 已满标
                '4' => array(
                    'status' => PrjModel::STATUS_PASS,
                    'bid_status' => array("IN",array(PrjModel::BSTATUS_FULL,PrjModel::BSTATUS_END)),
                ),
                // 待还款
                '5' => array(
                    'status' => PrjModel::STATUS_PASS,
                    'bid_status' => PrjModel::BSTATUS_REPAYING,
                ),
                // 已还款结束
                '6' => array(
                    'status' => PrjModel::STATUS_PASS,
                    'bid_status' => PrjModel::BSTATUS_REPAID,
                ),
                // 截止投标
                '7' => array(
                    'status' => PrjModel::STATUS_PASS,
                    'bid_status' => PrjModel::BSTATUS_END,
                ),
            ),
            // 字段排序
            'ORDERBY' => array(
                // 融资规模
                '1' => 'demand_amount',
                // 预期利率
                '2' => 'year_rate',
                // 期限
                '3' => 'time_limit_day',
                // 发布时间
                '4' => 'id',
                // 融资开标时间
                '5' => 'start_bid_time',
                // 审核时间
                '6' => 'verify_time',
            ),
        );
        $this->transfer_mapping = array(
            'STATUS' => array(
                '0'=>array("pt.status"=>array("NEQ",AssetTransferModel::STATUS_DELETE)),
                // 转让中
                '1' => array(
                    'pt.status' => AssetTransferModel::STATUS_WATING,
//                    'pt.end_time' => array('GT', $now),
                ),
                // 已转让
                '2' => array(
                    'pt.status' => AssetTransferModel::STATUS_PROCESSED,
                ),
                // 已过期
                '3' => array(
                    'pt.status' => AssetTransferModel::STATUS_CANSEL,
//                    'pt.end_time' => array('LT', $now),
                ),
            ),
            'ORDERBY' => array(
                '1' => 'po.expect_repay_time', // 还款日期
                '2' => 'pj.rate', // 预期利率
                '3' => 'pj.time_limit_day', // 期限
                '4' => 'po.money', // 投资金额
                '5' => 'pt.money', // 转让价格
                '6' => 'pt.buy_time', // 转让时间
            ),
        );
        $this->corpfinancing_mapping = array(
            'STATUS' => array(
                // 所有
                '0' => array(
                    'is_del' => 0,
                ),
                // 待审核
                '1' => array(
                    'check_statue' => CorpFinancingModel::STATUS_FINANCE_WAIT,
                    'is_del' => 0,
                ),
                // 审核未通过
                '2' => array(
                    'check_statue' => CorpFinancingModel::STATUS_FINANCE_NOPASS,
                    'is_del' => 0,
                ),
                // 金融助理跟进
                '3' => array(
                    'check_statue' => CorpFinancingModel::STATUS_FINANCE_PASS,
                    'is_del' => 0,
                ),
                // 项目已提交
                '4' => array(
                    'check_statue' => CorpFinancingModel::STATUS_FINANCE_INIT,
                    'is_del' => 0,
                ),
                // 项目审核中
                '5' => array(
                    'check_statue' => CorpFinancingModel::STATUS_FINANCE_APPROVAL,
                    'is_del' => 0,
                ),
                // 项目已驳回
                '6' => array(
                    'check_statue' => CorpFinancingModel::STATUS_FINANCE_REJECTED,
                    'is_del' => 0,
                ),
                // 放款中
                '7' => array(
                    'check_statue' => CorpFinancingModel::STATUS_FINANCE_PASSED,
                    'is_del' => 0,
                ),
                // 已放款
                '8' => array(
                    'check_statue' => CorpFinancingModel::STATUS_FINANCE_LOANEND,
                    'is_del' => 0,
                ),
            ),
            'ORDERBY' => array(
                // 融资申请日期
                '1' => 'ctime',
                // 融资金额
                '2' => 'finance_amount',
            ),
        );
        $this->corpfinancing_status_display = array(
            CorpFinancingModel::STATUS_FINANCE_WAIT => '待审核',
            CorpFinancingModel::STATUS_FINANCE_NOPASS => '审核未通过',
            CorpFinancingModel::STATUS_FINANCE_PASS => '金融助理跟进',

            // 状态：UBSP
            CorpFinancingModel::STATUS_FINANCE_INIT => '项目已提交',
            CorpFinancingModel::STATUS_FINANCE_APPROVAL => '项目审核中',
            CorpFinancingModel::STATUS_FINANCE_REJECTED => '项目已驳回',
            CorpFinancingModel::STATUS_FINANCE_PASSED => '放款中',
            CorpFinancingModel::STATUS_FINANCE_LOANEND => '已放款',
        );
        $this->prj_bid_status_display = array(
            PrjModel::BSTATUS_WATING => '待开标',
            PrjModel::BSTATUS_BIDING => '投标中',
            PrjModel::BSTATUS_FULL => '已满标',
            PrjModel::BSTATUS_REPAYING => '待还款',
            PrjModel::BSTATUS_REPAID => '已还款结束',
            PrjModel::BSTATUS_END => '截止投标',
            PrjModel::BSTATUS_REPAY_IN => '还款中',
            PrjModel::BSTATUS_FAILD => '已流标',
        );
    }


    /**
     * 融资记录主页
     * 状态统计
     */
    public function index() {
        $counts = array();
        // 项目统计
        $oModel = M('Prj');
        $index = 'project';
        foreach ($this->project_mapping['PRJ_TYPE'] as $ptkey => $ptype) {
            foreach ($this->project_mapping['PRJ_STATUS'] as $pskey => $pstatus) {
                $where = array_merge($ptype, $pstatus);
                $where['uid'] = $this->uid;
                $counts[$index][$ptkey][$pskey] = (int)$oModel->where($where)->count();
            }
        }

        // 转让统计
        $oModel = M();
        $index = 'transfer';
        foreach ($this->transfer_mapping['STATUS'] as $k => $v) {
            $where =  $v;
            $where['uid'] = $this->uid;
            $counts[$index][0][$k] = (int)$oModel->table('fi_asset_transfer pt')->where($where)->count();
        }

        // 企业融资统计
        $oModel = M('corpFinancing');
        $index = 'corpfinancing';
        foreach ($this->corpfinancing_mapping['STATUS'] as $k => $v) {
            $where =  $v;
            $where['uid'] = $this->uid;
            $counts[$index][0][$k] = (int)$oModel->where($where)->count();
        }

        $default_channel = in_array(IdentityConst::PROD_PUBLISH_NO, $this->loginedUserInfo['identity_no']) ? 'project' : 'corpfinancing';

        $this->assign('channel', $default_channel);
        $this->assign('counts', json_encode($counts));
        $this->display();
    }


    /**
     * 融资记录数据列表分发
     * 根据prj_type分发到project_list或transfer_list
     */
    public function index_ajax() {
        $default_channel = in_array(IdentityConst::PROD_PUBLISH_NO, $this->loginedUserInfo['identity_no']) ? 'project' : 'corpfinancing';
        $channel = I('request.channel', $default_channel);
        if(!in_array($channel, array('project', 'transfer', 'corpfinancing'))) $this->_404();

        $action = $channel . '_list';
        $this->{$action}();
        return;
    }


    /**
     * 融资记录-项目列表
     */
    private function project_list() {
        $channel = I('request.channel', 'project');
        $prj_type = (int)I('request.prj_type', 0);
        $status = (int)I('request.status_project', 1);
        $page = (int)I('request.p', 1);
        $order = I('request.order');
        if(!in_array($prj_type, range(0, 5))) $prj_type = 0;
        if(!in_array($status, range(0, 7))) $status = 0;

        $keyword = I('request.keyword');
        $starttime = strtotime(I('request.starttime'));
        $endtime = strtotime(I('request.endtime'));

        $tenant_id = service("Application/ProjectManage")->getTenantByUid($this->uid);
        // 条件筛选
        $where = array(
            'uid' => $tenant_id ? $tenant_id : $this->uid,
        );
        if(array_key_exists($prj_type, $this->project_mapping['PRJ_TYPE'])) {
            $where = array_merge($where, $this->project_mapping['PRJ_TYPE'][$prj_type]);
        }
        if(array_key_exists($status, $this->project_mapping['PRJ_STATUS'])) {
            $where = array_merge($where, $this->project_mapping['PRJ_STATUS'][$status]);
        }
        if($keyword) {
            $where['prj_name'] = array('LIKE', "%$keyword%");
        }
        if($starttime) {
            switch ($status) {
                case 1:
                    $where['verify_time'][] = array('EGT', $starttime);
                    break;
                case 2:
                    $where['start_bid_time'][] = array('EGT', $starttime);
                    break;
                case 3:
                case 4:
                case 7:
                    $where['end_bid_time'][] = array('EGT', $starttime);
                    break;
                case 6:
                    $where['actual_repay_time'][] = array('EGT', $starttime);
                    break;
                default:
                    $where['repay_time'][] = array('EGT', $starttime);
                    break;
            }
        }
        if($endtime) {
            if($endtime == $starttime) {
                $endtime = $endtime + 86400;
            }
            switch ($status) {
                case 1:
                    $where['verify_time'][] = array('ELT', $endtime);
                    break;
                case 2:
                    $where['start_bid_time'][] = array('ELT', $endtime);
                    break;
                case 3:
                case 4:
                case 7:
                    $where['end_bid_time'][] = array('ELT', $endtime);
                    break;
                case 6:
                    $where['actual_repay_time'][] = array('ELT', $endtime);
                    break;
                default:
                    $where['repay_time'][] = array('ELT', $endtime);
                    break;
            }
        }

        // 默认ORDER
        if(!$order) {
            switch ($status) {
                case 1:
                    $order = '6|desc|nohightlight';
                    break;

                case 2:
                    $order = '5|asc';
                    break;

                default:
                    # code...
                    break;
            }
        }

        // 字段排序
        list($order_field, $order_type, $nohightlight) = explode('|', $order);
        if(!($order_type && in_array(strtolower($order_type), array('asc', 'desc')))) {
            $order_type = 'desc';
        }
        if (array_key_exists($order_field, $this->project_mapping['ORDERBY'])) {
            $orderby = $this->project_mapping['ORDERBY'][$order_field] . ' ' . $order_type;
        } else {
            $orderby = 'id DESC';
        }


        $srvProject = service('Financing/Project');
        $result = $srvProject->getList($where, $orderby, $page, self::PAGESIZE);

        $list = $result['data'];
        // 特殊字段处理
        foreach ($list as $key => $row) {
            $list[$key]['time_limit_display'] = $row['time_limit'] . $this->time_limit_display[$row['time_limit_unit']];
            $list[$key]['real_amount'] = $row['demand_amount'] - $row['remaining_amount'];                              // 实际融到额度
            $list[$key]['will_repay_amount'] = $srvProject->getIncomeById($row['id'], $list[$key]['real_amount'], 0) + $list[$key]['real_amount'];   // 待还款金额
        }

        $paging  =  W('Paging', array(
            'totalRows'=> $result['total_row'],
            'pageSize'=> self::PAGESIZE,
            'parameter' => array(
                'channel' => $channel,
                'prj_type' => $prj_type,
                'status_project' => $status,
                'order' => $order,
                'keyword' => $keyword,
                'starttime' => $starttime,
                'endtime' => $endtime,
            ),
        ), TRUE);

        $this->assign('channel', $channel);
        $this->assign('prj_type', $prj_type);
        $this->assign('status', $status);

        $this->assign('list', $list);
        $this->assign('paging', $paging);

        // 默认排序不高亮 @2013/12/17
        if(!$nohightlight) {
            $this->assign('order_field', $order_field);
            $this->assign('order_type', $order_type);
        }

        $this->display("index_ajax_{$status}");
    }


    /**
     * 融资记录-转让列表
     */
    private function transfer_list() {
        $channel = I('request.channel', 'transfer');
        $prj_type = (int)I('request.prj_type', 1);
        $status = (int)I('request.status_transfer', 0);
        $page = (int)I('request.p', 1);
        $order = I('request.order');
        if(!in_array($status, range(0, 3))) $status = 0;

        $keyword = I('request.keyword');
        $starttime = strtotime(I('request.starttime'));
        $endtime = strtotime(I('request.endtime'));
        import_app("Financing.Model.PrjModel");
        // 条件筛选
        $where = array(
            'pt.uid' => $this->uid,
        );
        if(array_key_exists($status, $this->transfer_mapping['STATUS'])) {
            $where = array_merge($where, $this->transfer_mapping['STATUS'][$status]);
        }

        if($keyword) {
            $where['pj.prj_name'] = array('LIKE', "%$keyword%");
        }
        if($starttime) {
            switch ($status) {
                case 2:
                    $where['pt.buy_time'][] = array('EGT', $starttime);
                    break;
                default:
                    $where['pt.ctime'][] = array('EGT', $starttime);
                    break;
            }
        }
        if($endtime) {
            // 截至时间等于开始时间的时候
            if($endtime == $starttime) {
                $endtime = $endtime + 86400;
            }
            switch ($status) {
                case 2:
                    $where['pt.buy_time'][] = array('ELT', $endtime);
                    break;
                default:
                    $where['pt.ctime'][] = array('ELT', $endtime);
                    break;
            }
        }

        // 字段排序
        list($order_field, $order_type) = explode('|', $order);
        if(!($order_type && in_array(strtolower($order_type), array('asc', 'desc')))) {
            $order_type = 'desc';
        }
        if (array_key_exists($order_field, $this->transfer_mapping['ORDERBY'])) {
            $orderby = $this->transfer_mapping['ORDERBY'][$order_field] . ' ' . $order_type;
        } else {
            $orderby = 'pt.id DESC';
        }

        $join = array(
            'LEFT JOIN fi_prj pj ON pt.prj_id = pj.id',
            'LEFT JOIN fi_prj_order po ON pt.from_order_id = po.id ',
        );
        $fields = array(
            'pt.*',
            'pt.from_order_id as p_from_order_id',
            'pj.prj_name',
            'pj.id as pjid',
            'pj.prj_type',
            'pj.year_rate',
            'pj.prj_no',
            'pj.safeguards',
            'pj.rate',
            'pj.rate_type',
            'pj.time_limit',
            'pj.time_limit_unit',
            'pj.repay_way', // 还款方式
            'pj.bid_status', // 项目状态
            'po.expect_repay_time', // 还款日期
            'po.ctime AS poctime', // 持有日期
            'po.is_transfer',
            'po.from_order_id',
        );
//print_r($where);
        $oModel = M();
        $list = $oModel->table('fi_asset_transfer pt')
            ->where($where)
            ->join($join)
            ->field($fields)
            ->order($orderby)
            ->page($page)
            ->limit(self::PAGESIZE)
            ->select();

        $total = $oModel->table('fi_asset_transfer pt')
            ->where($where)
            ->join($join)
            ->count();

        // 特殊字段处理
        $srvProject = service('Financing/Project');
        $srvFinancing = service('Financing/Financing');
        foreach ($list as $key => $row) {
            // 利率
            $list[$key]['rate_type_display'] = $srvProject->getRateType($row['rate_type']);
            $list[$key]['rate_symbol'] = $row['rate_type'] == 'day' ? '‰' : '%';
            if ($row['rate_type'] != PrjModel::RATE_TYPE_DAY) {
                $list[$key]['rate'] = number_format($list[$key]['rate'] / 10, 2);
            } else {
                $list[$key]['rate'] = number_format($list[$key]['rate'], 2);
            }

            $synMoney = service('Financing/Financing')->getTransMoney($row['id'],1);
            $erpire = service("Financing/Transfer")->getTimeLimit($row['id']);

            $list[$key]['money'] =  $synMoney;
            // 期限
            $list[$key]['time_limit_display'] = $row['time_limit'] . $this->time_limit_display[$row['time_limit_unit']];
            $list[$key]['expire_time_limit'] = $erpire;
            // 由于目前转让都是一次性转让，列表中的投资金额就去转让表里的装让资产字段(property)

            // 还款进度
            if($row['bid_status'] == PrjModel::BSTATUS_REPAY_IN) {
                $list[$key]['repay_progress'] = $srvFinancing->getOrderRepayProgress($row['from_order_id']);
            }

            // 状态
            $list[$key]['bid_status_display'] = $this->prj_bid_status_display[$row['bid_status']];

//            $list[$key]['gain'] = $row['property'] + $row['prj_revenue'];
        }
        $paging  =  W('Paging', array(
            'totalRows'=> $total,
            'pageSize'=> self::PAGESIZE,
            'parameter' => array(
                'channel' => $channel,
                'prj_type' => $prj_type,
                'status_transfer' => $status,
                'order' => $order,
                'keyword' => $keyword,
                'starttime' => $starttime,
                'endtime' => $endtime,
            ),
        ), TRUE);

        $this->assign('status_transfer', $status);
        $this->assign('list', $list);
        $this->assign('paging', $paging);

        $this->assign('channel', $channel);
        $this->assign('order_field', $order_field);
        $this->assign('order_type', $order_type);
        $status = !$status ? 1: $status;
        $this->display("index_transfer_ajax_{$status}");
    }


    /**
     * 融资记录-企业融资
     */
    private function corpfinancing_list() {
        $channel = I('request.channel', 'project');
        $prj_type = (int)I('request.prj_type', 1);
        $status = (int)I('request.status_corpfinancing', 1);
        $page = (int)I('request.p', 1);
        $order = I('request.order');
        if(!in_array($status, range(0, 8))) $status = 0;

        // 条件筛选
        $where = array(
            'uid' => $this->uid,
        );
        if(array_key_exists($status, $this->corpfinancing_mapping['STATUS'])) {
            $where = array_merge($where, $this->corpfinancing_mapping['STATUS'][$status]);
        }

        // 字段排序
        list($order_field, $order_type) = explode('|', $order);
        if(!($order_type && in_array(strtolower($order_type), array('asc', 'desc')))) {
            $order_type = 'desc';
        }
        if (array_key_exists($order_field, $this->corpfinancing_mapping['ORDERBY'])) {
            $orderby = $this->corpfinancing_mapping['ORDERBY'][$order_field] . ' ' . $order_type;
        } else {
            $orderby = 'id DESC';
        }

        $oModel = M('corpFinancing');
        $list = $oModel
            ->where($where)
            ->order($orderby)
            ->page($page)
            ->limit(self::PAGESIZE)
            ->select();

        $total = $oModel
            ->where($where)
            ->count();

        // 特殊字段处理
        $srvCorpFinancing = service('Financing/CorpFinancing');
        foreach ($list as $key => $row) {
            $list[$key]['status_display'] = $this->corpfinancing_status_display[$row['check_statue']];
            $list[$key]['trade_name'] = $srvCorpFinancing->getTradeName($row['corp_main_industry']);
            $list[$key]['city_display'] = $srvCorpFinancing->getAreaById($row['corp_city'], $row['corp_area']);
        }

        $paging  =  W('Paging', array(
            'totalRows'=> $total,
            'pageSize'=> self::PAGESIZE,
            'parameter' => array(
                'channel' => $channel,
                'prj_type' => $prj_type,
                'status_corpfinancing' => $status,
                'order' => $order,
            ),
        ), TRUE);

        $this->assign('status_corpfinancing', $status);
        $this->assign('list', $list);
        $this->assign('paging', $paging);

        $this->assign('order_field', $order_field);
        $this->assign('order_type', $order_type);

        $this->display('index_corpfinancing_ajax');
    }


    /**
     * 取消转让
     */
    public function cancel_transfer() {
        $transfer_id = (int)I('transfer_id');
        $srvFinancing = service('Financing/Financing');
        $ret = $srvFinancing->cancelTransfer($this->uid, $transfer_id);
        ajaxReturn('', $ret[1], $ret[0]);
    }


    /**
     * 删除过期转让
     */
    public function del_transfer() {
        $transfer_id = (int)I('transfer_id');
        $srvFinancing = service('Financing/Financing');
        $ret = $srvFinancing->delTransfer($this->uid, $transfer_id);
        ajaxReturn('', $ret[1], $ret[0]);
    }
}

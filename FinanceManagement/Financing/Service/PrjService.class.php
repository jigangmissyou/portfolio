<?php
/**
 * Created by PhpStorm.
 * User: luoman
 * Date: 2015/10/12
 * Time: 14:41
 */
class PrjService extends BaseService
{

    //项目列表页新增排序
    const ORDER_MIN_BID_AMOUNT = 6; //按照起投金额
    const ORDER_INCOME = 2; //按照项目利率
    const ORDER_TIME_LIMIT = 3; //按照投资期限

    //排序
    const SORT_DESC = "DESC";
    const SORT_ASC = "ASC";

    /**
     * 统计各标的类型统计当前开标中的标的个数
     * @param string $prj_type 项目类型
     * @param int $is_newbie 当前登录用户是否是新客
     * @param bool|false $real_time 是否要求实时 不实时缓存10S
     * @return array
     */
    public function getBidCountByPrjType($prj_type = '', $is_newbie = 0, $real_time = 0)
    {
        $cache_key = 'BID_COUNT_BY_PRJ_TYPE';
        $result = S($cache_key);
        if (empty($result) || $real_time) {
            $prj_model = D('Financing/Prj');
            $condition = array(
                'fi_prj.status' => array('eq', PrjModel::STATUS_PASS),
                'fi_prj.is_show' => 1,
            );
            if (!$is_newbie) {
                $condition['fi_prj.is_new'] = 0;
            }
            $condition['_string'] = 'fi_prj.`bid_status` = ' . PrjModel::BSTATUS_BIDING . '
                OR (
                    fi_prj.bid_status = ' . PrjModel::BSTATUS_WATING . '
                    AND fi_prj_ext.is_pre_sale = 1
                )
            ) AND (
                fi_prj_ext.is_qfx = 0
                OR (
                    fi_prj_ext.is_qfx = 1
                    AND fi_prj.bid_status NOT IN (' . PrjModel::BSTATUS_WATING . ', ' . PrjModel::BSTATUS_BIDING . ')
                )';
            $condition['mi_no'] = array('IN', array('1234567889', '1234567890'));

            $prj_count_array = (array) $prj_model->join('LEFT JOIN fi_prj_ext fi_prj_ext ON fi_prj.id = fi_prj_ext.prj_id')
                ->field('fi_prj.prj_type')->where($condition)->select();

            if ($prj_count_array) {
                foreach ($prj_count_array as $key => $value) {
                    $prj_count_array[$key] = current($value);
                }
            }

            $prj_count_array = array_count_values($prj_count_array);
            //0元购项目
            $zeroCount = service('Activity/Zero')->getPrjCount();
            $prj_count_array['Z'] = $zeroCount;
            $result = array_fill_keys(array(PrjModel::PRJ_TYPE_A, PrjModel::PRJ_TYPE_F, PrjModel::PRJ_TYPE_B, PrjModel::PRJ_TYPE_H, 'Z'), 0);
            $result = array_merge($result, $prj_count_array);
            S($cache_key, $result, 10);
        }

        if ($prj_type) {
            return (int)$result['prj_type'];
        }
        return $result;
    }

    /**
     * 担保措施
     * @param int $prj_id
     * @param array $prj_info
     * @return array
     */
    public function getPrjGuaranteeMeasure($prj_id = 0, $prj_info = array())
    {
        if (empty($prj_info)) {
            $prj_info = service('Financing/Project')->getDataById($prj_id);
        }

        $measure_array = array();
        $guarantee_desc = M('prj_ext')->where(array('prj_id' => $prj_id))->getField('guarantee_desc');
        if ($guarantee_desc) {
            $measure_array = explode("\n", strip_tags(str_replace(PHP_EOL, "\n", trim($guarantee_desc, PHP_EOL))));
            $measure_array = array_filter($measure_array);
        }

        /*if (!$prj_info['zhr_apply_id']) {
            array_unshift($measure_array, '保理公司承诺回购');
        }*/

        D('Financing/Prj');
        if ($prj_info['prj_business_type'] == PrjModel::PRJ_BUSINESS_TYPE_D) {
            $measure_array = array_merge($measure_array, array(
                '专业担保公司兴农担保无限连带责任反担保',
                '10%风险保证金担保',
            ));
        } elseif($prj_info['prj_business_type'] == PrjModel::PRJ_BUSINESS_TYPE_H) {
            array_push($measure_array, '风险准备金保障（创客融将成立专项风险准备金，按产品总额8%计提，为投资人提供担保服务）');
        }
        if ($prj_info['prj_business_type'] == prjModel::PRJ_BUSINESS_TYPE_G){//央企融类型的统一显示担保措施
            $measure_array = ['央企商票质押'];
        }
        return $measure_array;
    }

    /**
     * 时间轴
     * @param int $prj_id
     * @param array $prj_info
     * @param int $uid
     * @return array
     */
    public function getPrjTimeLine($prj_id = 0, $prj_info = array(), $uid = 0)
    {
        D('Financing/Prj');
        $time_line_array = array();
        $project_service = service('Financing/Project');
        if (empty($prj_info)) {
            $prj_info = $project_service->getDataById($prj_id);
        }
        $combine_keys = array(
            'finance_apply_date',
            'cooperate_guarantee_audit_time',
            'manager_invest_date',
            'xindun_audit_date',
            'finance_contract_sign_date',
        );
        $time_line_items = self::getTimeLineItemByBusinessType($prj_info['prj_business_type'], $combine_keys);
//        $fields = implode(',', $combine_keys) . ', is_pre_sale';
        $other_fields = array(
            'is_pre_sale',
            'show_bid_time',
        );
        $fields = array_merge($combine_keys,$other_fields);
        $prj_ext_info = M('prj_ext')->where(array('prj_id' => $prj_id))->field($fields)->find();

//        $pre_five_node_show = self::decideTimeLinePreFiveNodeShowOrNot($prj_info, $time_line_items, $prj_ext_info);
        $date_format = 'Y年m月d日 H:i:s';
        $time_line_node_keys = array('title', 'time', 'highlight');
        $highlight = 1;
        //开标时间节点
        if ($prj_info['bid_status'] == PrjModel::BSTATUS_WATING) {
            $highlight = 0;
        }
        if($prj_ext_info['is_pre_sale']){
            $start_time_node =  array('预售开始时间', date($date_format, $prj_ext_info['show_bid_time']), 1);
        }else{
            $start_time_node = array('开标时间', date($date_format, $prj_info['start_bid_time']), $highlight);
        }
        $start_bid_time_node = array_combine($time_line_node_keys, $start_time_node);
//        if (!$pre_five_node_show) {
//            $time_line_array['begin'][] = $start_bid_time_node;
//        } else {
        $i = 0;
        foreach ($time_line_items as $key => $value) {
            $this_node_not_show = empty($prj_ext_info[$key]) || empty($value);
            $node = array_combine($time_line_node_keys, array(
                $this_node_not_show ? '' : $value,
                $this_node_not_show ? '' : date($date_format, $prj_ext_info[$key]),
                $this_node_not_show ? 0 : 1,
            ));
            if (isset($time_line_array['ready'][0]['title']) && empty($time_line_array['ready'][0]['title']) && !$this_node_not_show) {
                $time_line_array['ready'][0] = $node;
                $time_line_array['ready'][$i] = array_combine($time_line_node_keys, array('', '', 0));
            } else {
                $time_line_array['ready'][$i] = $node;
            }
            $i++;
        }
        $time_line_array['begin'][] = $start_bid_time_node;
//        }

        //投资时间|预售时间
        $time_line_array['begin'][] = array_combine($time_line_node_keys, $this->getInvestNodeOfPrjTimeLine($prj_info['id'], $prj_ext_info['is_pre_sale'], $uid));

        //起息时间 生产还款计划后亮起
        $income_time = $this->getPrjIncomeDate($prj_id, $prj_info, 'Y年m月d日');
        if ($project_service->isOrderRepayPlanAllCreated($prj_info['id']) && $prj_info['invest_count']) {
            $highlight = 1;
        } else {
            $highlight = 0;
        }
        $time_line_array['begin'][] = array_combine($time_line_node_keys, array('起息时间', $income_time, $highlight));

        //还款时间 到账时间
        if ($prj_info['bid_status'] != PrjModel::BSTATUS_REPAID) {
            $highlight = 0;
            $repay_date = date('Y年m月d日', $prj_info['next_repay_date']);
            $repay_date .= $prj_info['value_date_shadow'] == 0 ? ' 13:00:00' : ' 24:00:00';
            D("Financing/PrjRepayPlan");
            $repay_data = M('prj_repay_plan')->where(
                array(
                    'prj_id'=>$prj_id,
                    'status'=>array('NEQ',PrjRepayPlanModel::PRJ_REPAYMENT_STATUS_FAIL),
                )
            )->order('repay_periods desc')->find();
            if($repay_data){
                $end_repay_date = $prj_info['value_date_shadow'] == 0 ? date('Y年m月d日',$repay_data['repay_date']).' 13:00:00' : date('Y年m月d日',$repay_data['repay_date']).' 24:00:00' ;
            }else{
                $end_repay_date = $prj_info['value_date_shadow'] == 0 ? date('Y年m月d日', $prj_info['last_repay_date']).' 13:00:00' : date('Y年m月d日', $prj_info['last_repay_date']).' 24:00:00';
            }
        } else {
            $highlight = 1;
            $end_repay_date = $repay_date = date($date_format, $prj_info['actual_repay_time']);
        }
        $repay_arr = $this->showRepayTimeList($prj_id);
        $repay_count = $repay_arr ? count($repay_arr) : 0;
        if($repay_count>1 && $highlight == 0){
            $extra_repay = 2;
        }elseif($repay_count>1 && $highlight == 1){
            $extra_repay = 3;
        }elseif($highlight){
            $extra_repay = 1;
        }else{
            $extra_repay = 0;
        }
        $time_line_array['begin'][] = array_combine($time_line_node_keys, array( '还款时间', $repay_date, $extra_repay));
        $time_line_array['begin'][] = array_combine($time_line_node_keys, array( '结束', $end_repay_date, $highlight));
        return $time_line_array;
    }

    /**
     * 时间轴的投资|预售节点
     * @param $prj_id
     * @param $is_pre_sale
     * @param $uid
     * @param string $date_format
     * @return array
     */
    private function getInvestNodeOfPrjTimeLine($prj_id, $is_pre_sale, $uid, $date_format = 'Y年m月d日 H:i:s')
    {
        $first_order = D('Financing/PrjOrder')->getFirstOrderOfPrj($prj_id, $uid);
        $num_order = D('Financing/PrjOrder')->getOrderOfPrjNum($prj_id, $uid);
        if($num_order>1){
            $extra = 3;
        }else{
            $extra = 1;
        }
        if ($is_pre_sale) {
            $prj_order_pre_model = D('Financing/PrjOrderPre');
            if (empty($first_order)) {
                //有没有买预售
                $my_pre_sale = $prj_order_pre_model->where(array('uid' => $uid, 'prj_id' => $prj_id, 'status' => PrjOrderPreModel::STATUS_SUCCESS))->find();
                if ($my_pre_sale) {
                    $node = array('预售', '', 1);
                } else {
                    $node = array('', '', 0);
                }
            } else {
                //是不是预售
                $is_pre_sale = $prj_order_pre_model->where(array('order_id' => $first_order['id'], 'status' => PrjOrderPreModel::STATUS_SUCCESS))->find();
                if ($is_pre_sale) {
                    $node = array('预售', date($date_format, $first_order['ctime']), 1);
                } else {
                    $node = array('投资时间', date($date_format, $first_order['ctime']), $extra);
                }
            }
        } else {
            if (empty($first_order)) {
                $node = array('', '', 0);
            } else {
                $node = array('投资时间', date($date_format, $first_order['ctime']), $extra);
            }
        }
        return $node;
    }

    /**
     * 判断时间轴前五个节点要不要显示 判断顺序不能颠倒
     * @param array $prj_info 项目信息
     * @param array $time_line_items
     * @param array $time_nodes prj_ext里面的各节点时间
     * @return bool
     */
    static private function decideTimeLinePreFiveNodeShowOrNot($prj_info, array $time_line_items, array $time_nodes)
    {
        if (empty($time_nodes)) {
            return false;
        }

        foreach ($time_nodes as $key => $value) {
            if (empty($time_line_items[$key]) || empty($value)) {
                unset($time_nodes[$key]);
            }
        }

        if (empty($time_nodes)) {
            return false;
        }

        $time_nodes = array_values($time_nodes);
        $combine_keys_count = count($time_nodes);
        for ($i = 0; $i < $combine_keys_count - 1; $i++) {
            if ($time_nodes[$i] >= $time_nodes[$i + 1]) {
                return false;
            }
        }

        //最后一个非空的时间
        $the_last_not_empty_time = end($time_nodes);

        if ($prj_info['start_bid_time'] - $the_last_not_empty_time > 86400 * 90
            || $prj_info['start_bid_time'] <= $the_last_not_empty_time) {
            return false;
        }

        return true;
    }

    /**
     * 根据项目业务类型获取时间轴展示项 三农贷和电商贷不同于其他
     * @param $prj_business_type 项目业务类型
     * @param $combine_keys 项目业务类型
     * @return array
     */
    static private function getTimeLineItemByBusinessType($prj_business_type, $combine_keys)
    {
        D('Financing/Prj');
        $time_line_item_array = array(
            //三农贷
            PrjModel::PRJ_BUSINESS_TYPE_D => array(
                '借款人提出申请',
                '合作担保公司初审通过',
                '客户经理尽职调查',
                '鑫盾风险审查',
                '借款合同签署',
            ),
            //电商贷
            PrjModel::PRJ_BUSINESS_TYPE_C => array(
                '借款人提出申请',
                '',
                '风控大数据模型评估风险、测算额度',
                '客户经理实地核实',
                '借款合同签署',
            ),
        );

        if (array_key_exists($prj_business_type, $time_line_item_array)) {
            return array_combine($combine_keys, $time_line_item_array[$prj_business_type]);
        } else {
            return array_combine($combine_keys, array(
                '借款人提出申请',
                '',
                '客户经理尽职调查',
                '鑫盾风险审查',
                '借款合同签署',
            ));
        }
    }

    /**
     * 项目标题格式化展示
     * eg:年益升-垫资贷CD2015052201111
     * @param int $prj_id
     * @param array $prj_info
     * @param array $prj_activity_info 项目关联的活动信息
     * @return string
     */
    public function getPrjTitleShow($prj_id = 0, $prj_info = array(), $prj_activity_info = array())
    {
        $project_service = service('Financing/Project');
        if (empty($prj_info)) {
            $prj_info = $project_service->getDataById($prj_id);
        }

        $prj_title_show = $project_service->getPrjType($prj_info['prj_type']);
        $prj_title_show .= '-';
        if ($prj_info['prj_business_type']) {
            if (in_array($prj_info['prj_business_type'],array('C', 'D', 'E', 'C0V14', 'C00303'))) {
                $prj_title_show .= '经营贷';
            } else {
                $prj_title_show .= $project_service->getPrjBusinessType($prj_info['prj_business_type']);
            }
        }

        if ($prj_activity_info) {
            $prj_title_show .= '<a href="' . ($prj_activity_info['url'] ? $prj_activity_info['url'] : "javascript:;") . '" target="_blank" class="org">' . $prj_info['prj_name'] . '</a>';
        } else {
            $prj_title_show .= $prj_info['prj_name'];
        }
        return $prj_title_show;
    }

    /**
     * 排序字段数组
     */
    public function getOrder($order,$sort)
    {
        switch($order){
            case self::ORDER_MIN_BID_AMOUNT: //起投金额
                $order = "min_bid_amount";
                break;
            case self::ORDER_INCOME: //项目利率 +  奖励利率
                $order = "total_year_rate";
                break;
            case self::ORDER_TIME_LIMIT: //投资期限
                $order = "time_limit_day";
                break;
            default:
                $order = "ctime";
        }
        return array($order=>$sort);
    }

    /**
     * 格式化输出项目的还款时间
     * @param $prj_info 项目基本信息
     * @param bool|false $only_date 只返回时间吗
     * @param string $format 时间格式
     * @return string
     */
    public function formatPrjRepayDate($prj_info, $only_date = false, $format = 'Y-m-d')
    {
        if (empty($prj_info['next_repay_date']) && empty($prj_info['last_repay_date'])) {
            $project_service = service('Financing/Project');
            $prj_info = $project_service->getDataById($prj_info['id']);
        }
        $last_repay_date = $prj_info['next_repay_date'] ? $prj_info['next_repay_date'] : $prj_info['last_repay_date'];
        if (!$last_repay_date) {
            if (!isset($project_service)) {
                $project_service = service('Financing/Project');
            }
            $last_repay_date = $project_service->getPrjFirstRepayDate($prj_info['id'], null, false);
        }

        if ($only_date) {
            return $format ? date($format, $last_repay_date) : $last_repay_date;
        } else {
            $result = date($format, $last_repay_date);
            $result .= $prj_info['value_date_shadow'] == 0 ? '   13点之前' : '   16:00-24:00';
            return $result;
        }
    }

    /**
     * 项目详情-分业务逻辑展示借款人信息
     * @param array $prj_info 项目信息
     * @param array $borrower_info 借款人信息
     * @return array 需要展示的KV
     */
    public function getBorrowerShowItems(array $prj_info, array $borrower_info)
    {
        D('Financing/Prj');
        $result = array();
        $is_bao_li = $prj_info['zhr_apply_id'] ? false : true;

        if (PrjModel::BORROWER_TYPE_COMPANY == $prj_info['borrower_type']) {
            $result[] = array(
                'k' => '企业名称',
                'v' => $prj_info['corp_name'] ? marskName($prj_info['corp_name'],1,2) : '',
            );
            if (PrjModel::PRJ_BUSINESS_TYPE_C == $prj_info['prj_business_type']) {
                $result[] = array(
                    'k' => '成立日期',
                    'v' => $is_bao_li ? $borrower_info['fund_date'] : '',
                );
                $result[] = array(
                    'k' => '注册资本',
                    'v' => D('Zhr/FinanceCorp')->getRegisterCapital($borrower_info),
                );
                $result[] = array(
                    'k' => '主营业务',
                    'v' => $borrower_info['main_business'],
                );
                $result[] = array(
                    'k' => '电商平台',
                    'v' => $borrower_info['store_platform'],
                );
                $result[] = array(
                    'k' => '店铺等级',
                    'v' => $borrower_info['store_rank'],
                );
                $result[] = array(
                    'k' => '销售收入',
                    'v' => is_numeric($borrower_info['sale_amount']) ? $borrower_info['sale_amount'] . '万'
                        : $borrower_info['sale_amount'],
                );
            } else {
                $result[] = array(
                    'k' => '所属行业',
                    'v' => $is_bao_li ? $borrower_info['trade'] : $borrower_info['trade_name'],
                );
                $result[] = array(
                    'k' => '成立日期',
                    'v' => $is_bao_li ? $borrower_info['fund_date'] : '',
                );

                $result = array_merge($result, self::getBorrowerShowItemsSection($borrower_info, $is_bao_li, $prj_info['prj_business_type']));

                if (PrjModel::PRJ_BUSINESS_TYPE_A == $prj_info['prj_business_type']) {
                    $result[] = array(
                        'k' => '特殊资质',
                        'v' => $borrower_info['special_quality'] ? $borrower_info['special_quality'] : '',
                    );
                }
            }
        } else {
            if (!$is_bao_li) {
                $borrower_name = M('user')->where(array('uid' => (int) $borrower_info['uid']))->getField('real_name');
            } else {
                $borrower_name = $prj_info['person_name'];
            }
            $result[] = array(
                'k' => '姓名',
                'v' => $borrower_name ? real_name_view($borrower_name) : '',
            );
            $result[] = array(
                'k' => '年龄',
                'v' => $borrower_info['age'],
            );
            if (PrjModel::PRJ_BUSINESS_TYPE_C == $prj_info['prj_business_type']) {
                $result[] = array(
                    'k' => '经营地',
                    'v' => $borrower_info['store_address'],
                );
                $result[] = array(
                    'k' => '店龄',
                    'v' => $borrower_info['store_age'],
                );
                $result[] = array(
                    'k' => '主营业务',
                    'v' => $borrower_info['main_business'],
                );
            } elseif (PrjModel::PRJ_BUSINESS_TYPE_D == $prj_info['prj_business_type']) {
                $matrimonial = getCodeName('A002', $borrower_info['matrimonial']);
                $result[] = array(
                    'k' => '婚姻状况',
                    'v' => $matrimonial,
                );
                $result[] = array(
                    'k' => '经营地',
                    'v' => $borrower_info['store_address'],
                );
                $year_income = $is_bao_li ? $borrower_info['annual_earnings'] : $borrower_info['year_incom'] ;
                $result[] = array(
                    'k' => '年收入',
                    'v' => is_numeric($year_income) ? $year_income . '万'
                        : $year_income,
                );
            } else{
                if ($is_bao_li) {
                    $matrimonial = getCodeName('A002', $borrower_info['matrimonial']);
                    $result[] = array(
                        'k' => '婚姻状况',
                        'v' => $matrimonial,
                    );

                    $income_source = array();
                    if ($borrower_info['has_house']) {
                        $income_source[] = '房产/有';
                    }
                    if ($borrower_info['has_car']) {
                        $income_source[] = '车辆/有';
                    }
                    $result[] = array(
                        'k' => '主要资产',
                        'v' => $income_source ? implode(' ', $income_source) : '',
                    );
                } else {
                    $matrimonial = $borrower_info['marital_status'] == 0 ? '未婚'
                        : ($borrower_info['marital_status'] == 1 ? '已婚'
                            : ($borrower_info['marital_status'] == 2 ? '离异' : ''));
                    $result[] = array(
                        'k' => '婚姻状况',
                        'v' => $matrimonial,
                    );
                    $result[] = array(
                        'k' => '主要资产',
                        'v' => $is_bao_li ? '' : $borrower_info['prime_assets'],
                    );
                    $result[] = array(
                        'k' => '主要资产来源',
                        'v' => $is_bao_li ? '' : $borrower_info['income_source'],
                    );
                }
            }
        }

        foreach ($result as $key => $value) {
            if (empty($value['v'])) {
                unset($result[$key]);
            }
        }
        return array_values($result);
    }

    /**
     * 借款人信息展示片段
     * @param array $borrower_info 借款人信息
     * @param bool $is_bao_li 是否保理
     * @param string $prj_business_type 业务类型
     * @return array
     */
    static private function getBorrowerShowItemsSection(array $borrower_info, $is_bao_li = false, $prj_business_type = '')
    {
        $result = array();
        if (!$is_bao_li && $prj_business_type == PrjModel::PRJ_BUSINESS_TYPE_E) {
            //直融模式下的，业务类型为经营贷的项目，在项目详情页-项目信息下的 ‘借款企业信息’ 里的注册资本 改为 企业规模，注册资本不显示。
            $result[] = array(
                'k' => '企业规模',
                'v' => is_numeric($borrower_info['corp_scale']) ? getCodeName('A137', $borrower_info['corp_scale']) : $borrower_info['corp_scale'],
            );
            $result[] = array(
                'k' => '主营业务',
                'v' => $borrower_info['main_business'],
            );
        } else {
            if ($borrower_info['register_capital']) {
                $result[] = array(
                    'k' => '注册资本',
                    'v' => D('Zhr/FinanceCorp')->getRegisterCapital($borrower_info),
                );
            }
            $result[] = array(
                'k' => '主营业务',
                'v' => $borrower_info['main_business'],
            );
            $result[] = array(
                'k' => '企业规模',
                'v' => is_numeric($borrower_info['corp_scale']) ? getCodeName('A137', $borrower_info['corp_scale']) : $borrower_info['corp_scale'],
            );
        }

        return $result;
    }


    /**
     * 项目起息时间
     * @param int $prj_id
     * @param array $prj_info
     * @param string $format
     * @return bool|string
     */
    public function getPrjIncomeDate($prj_id = 0, $prj_info = array(), $format = 'Y-m-d')
    {
        $project_service = service('Financing/Project');
        if (empty($prj_info)) {
            $prj_info = $project_service->getDataById($prj_id);
        }
        //是否给募集期利息
        if($prj_info['act_prj_ext']['is_have_biding_incoming'] && $prj_info['freeze_time']){
            if (!$prj_info['value_date_shadow']) {
                return date($format, $prj_info['freeze_time']);
            } else {
                return date($format, strtotime("+{$prj_info['value_date_shadow']} day", $prj_info['freeze_time']));
            }
        }else{
            if (!$prj_info['value_date_shadow']) {
                return date($format, $prj_info['end_bid_time']);
            } else {
                return date($format, strtotime("+{$prj_info['value_date_shadow']} day", $prj_info['end_bid_time']));
            }
        }

    }


    public function getPrjQixiShow($prj_id, $format = 'Y-m-d')
    {
        $prj_info = M('prj')->where(array('id' => $prj_id))->field('end_bid_time,value_date_shadow')->find();
        $is_have_biding_incoming = M('prj_ext')->where(array('prj_id' => $prj_id))->getField('is_have_biding_incoming');

        // 是否给募集期利息
        $start_time = $prj_info['end_bid_time'];
        if ($is_have_biding_incoming) {
            $start_time = min(time(), $prj_info['end_bid_time']);
        }

        if (!$prj_info['value_date_shadow']) {
            return date($format, $start_time);
        } else {
            return date($format, strtotime("+{$prj_info['value_date_shadow']} day", $start_time));
        }
    }

    /**
     * 项目的还款时间列表
     * @param int $prjId
     * @return mixed
     */
    public function showRepayTimeList($prjId = 0)
    {
        $mod = D("Financing/PrjRepayPlan");
        $fields = array('prj_id,repay_periods,repay_date,status,mtime');
        $where = array(
            'prj_id' => $prjId,
            "status" => array('neq', PrjRepayPlanModel::PRJ_REPAYMENT_STATUS_FAIL),
        );
        $result = $mod->field($fields)->where($where)->order('repay_periods asc')->select();
//        echo $mod->getLastSql();
        if ($result) {
            return $result;
        }

        return;

    }

    /**时间轴展示个人投资记录时间
     * @param $uid
     * @param $prjId
     * @return mixed
     */
    public function showUserBidTimeList($uid,$prjId){
        $mod = D("Financing/PrjOrder");
        D("Financing/PrjOrderPre");
        $fields = array('prj_id,freeze_time');
        $where = array(
            'uid' => $uid,
            'prj_id' => $prjId,
            'pre_sale_status' => array(
                'not in',
                array(
                    PrjOrderPreModel::STATUS_FAIL,
                )
            ),
        );
        $result = $mod->field($fields)->where($where)->order('id asc')->select();
//        echo $mod->getLastSql();
        if($result){
            $i = 0;
            foreach($result as &$res){
                $i++;
                $res['freeze_time'] = date('Y-m-d H:i:s',$res['freeze_time']);
                $res['term'] = $i;
            }
            return $result;
        }
        return;
    }

    /**
     * 组装项目列表页面搜索条件
     * @param $bid_status
     * @param $time_limit
     * @param $prj_business_type
     * @param $c
     * @return mixed
     */
    public function createPlistSearchCondition($bid_status, $time_limit, $prj_business_type, $c)
    {
        D('Financing/Prj');
        //标的状态
        if ($bid_status) {
            $bid_status_condition_map = array(
                '1' => PrjModel::BSTATUS_WATING,
                '2' => PrjModel::BSTATUS_BIDING,
                '88' => array(array('EQ', 3), array('EQ', 7), 'OR'),
                '4' => PrjModel::BSTATUS_REPAYING,
                '5' => PrjModel::BSTATUS_REPAID,
                '8' => PrjModel::BSTATUS_REPAY_IN,
                '9' =>  array(array('EQ', 1), array('EQ', 2), 'OR'),
            );

            if (array_key_exists($bid_status, $bid_status_condition_map)) {
                $where['bid_status'] = $bid_status_condition_map[$bid_status];
            } else{
                $where['bid_status'] = $where['bid_status'] = array(
                    array('NEQ', PrjModel::BSTATUS_CANCEL),
                    array('NEQ', PrjModel::BSTATUS_FAILD),
                );
            }
        }

        //标的期限
        if ($time_limit) {
            $time_limit_condition_map = array(
                '1' => array('ELT', 7),
                '2' => array(array('EGT', 8), array('ELT', 15)),
                '3' => array('GT', 15),
                '4' => array(array('EGT', 16), array('ELT', 31)),
                '5' => array(array('EGT', 32), array('ELT', 60)),
                '6' => array(array('EGT', 61), array('ELT', 180)),
                '7' => array('GT', 180),
                '8' => array(array('EGT', 201), array('ELT', 300)),
                '9' => array(array('EGT', 301), array('ELT', 365)),
                '10' => array('GT', 365),
            );
            $where['time_limit_day'] = $time_limit_condition_map[$time_limit];
        }

        if ($prj_business_type) {
            //标的业务类型
            $prj_business_type_condition_map = array(
                '1' => ['eq', PrjModel::PRJ_BUSINESS_TYPE_A],
                '2' => ['eq', PrjModel::PRJ_BUSINESS_TYPE_B],
                '3' => ['eq', PrjModel::PRJ_BUSINESS_TYPE_C],
                '4' => ['eq', PrjModel::PRJ_BUSINESS_TYPE_D],
                '5' => ['eq', PrjModel::PRJ_BUSINESS_TYPE_E],
                '6' => ['eq', PrjModel::PRJ_BUSINESS_TYPE_F],
                '7' => ['eq', PrjModel::PRJ_BUSINESS_TYPE_G],
                '8' => ['eq', PrjModel::PRJ_BUSINESS_TYPE_H],
                '9' => [array('eq', PrjModel::PRJ_BUSINESS_TYPE_C),
                    array('eq', PrjModel::PRJ_BUSINESS_TYPE_D),
                    array('eq', PrjModel::PRJ_BUSINESS_TYPE_E),
                    array('eq', PrjModel::PRJ_BUSINESS_TYPE_G),
                    array('eq', PrjModel::PRJ_BUSINESS_TYPE_H), 'OR'],
                '10' =>['eq', PrjModel::PRJ_BUSINESS_TYPE_I],
                '11' =>['eq', PrjModel::PRJ_BUSINESS_TYPE_J]
            );
            $where['prj_business_type'] = $prj_business_type_condition_map[$prj_business_type];
        }

        //标的产品类型 仅留日、月、年益升和速兑通
        $prj_type_condition_map = array(
            '2' => ['eq', PrjModel::PRJ_TYPE_A],
            '3' => ['eq', PrjModel::PRJ_TYPE_F],
            '4' => ['eq', PrjModel::PRJ_TYPE_B],
            '6' => ['eq', PrjModel::PRJ_TYPE_H],
        );

        $where['status'] = PrjModel::STATUS_PASS;//审核通过
        $where['is_show'] = 1;
        $order_by = I('request.sort_id');
        if ($c == 1 && (($order_by || $bid_status || $time_limit || $prj_business_type) || $is_newbie) ) {
            $where['_string'] = "(`prj_type` IN('".PrjModel::PRJ_TYPE_A."', '".PrjModel::PRJ_TYPE_B."', '".PrjModel::PRJ_TYPE_F."', '".PrjModel::PRJ_TYPE_I."'))
						        OR (
						        		`prj_type` = '".PrjModel::PRJ_TYPE_H."'
						        		AND prj_sort = 99
						        )";
            if ($is_newbie && $c > 1) {//过滤新客非默认类型
                $where['prj_type'] = $prj_type_condition_map[$c];
            }
        } else {
            $where['prj_type'] = $prj_type_condition_map[$c];
        }
//        if (!$is_newbie) {
//            $where['is_new'] = 0;
//        }
        if ($c == 0) {
            $where['is_new'] = 1;
            unset($where['prj_type']);
        }

        if ($c != 7) {
            $map = $this->notListQfx();
            $where['_logic'] = 'AND';
            $where['_complex'] = $map;
        }

        return $where;
    }

    /**
     * 组装项目列表页面排序字串
     * @param $order_by
     * @param $sort
     * @return string
     */
    public function createPlistOrderSort($order_by, $sort)
    {
        if ($order_by) {
            $order_arr[] = 'bid_status_order ASC';
            $sort = !empty($sort) ? $sort : 'ASC';
            $order_by_field_map = array(
                self::ORDER_INCOME => 'total_year_rate',
                self::ORDER_TIME_LIMIT => 'time_limit_day',
                self::ORDER_MIN_BID_AMOUNT => 'min_bid_amount',
            );

            if (array_key_exists($order_by, $order_by_field_map)) {
                $order_arr[] .= $order_by_field_map[$order_by] . ' ' . $sort;
            }
        } else {
            import_app('Financing.Service.ProjectService');
            $order_arr[] = ProjectService::getDefaultOrder();
        }

        return implode(',', $order_arr);
    }

    /**
     * 其他的项目要过滤掉企福鑫项目和判断是否存管项目
     * 这里额外加上过滤的条件(不管当前用户是否是企福鑫用户)，以前的查询条件不变
     * $is_zs_user 用户判断是否存管项目
     * @return string
     */
    public function notListQfx() {
        $table = 'fi_prj';
        $where = 'exists(select 1 from fi_prj_ext where ' . $table . '.id=fi_prj_ext.prj_id
            and (is_qfx=0 or (is_qfx =1
            and ' . $table . '.bid_status not in('.PrjModel::BSTATUS_WATING.','.PrjModel::BSTATUS_BIDING.'))))';

        return $where;
    }

    /**
     * 天网项目进度
     * @param int $prj_id
     * @param array $prj_info
     * @param int $uid
     * @return array
     */
    public function getPrjProcedure($prj_id = 0, $prj_info = array(), $uid = 0)
    {
        D('Financing/Prj');
        $time_line_array = array();
        $project_service = service('Financing/Project');
        if (empty($prj_info)) {
            $prj_info = $project_service->getDataById($prj_id);
        }
        $combine_keys = array(
            'manager_invest_date',
            'xindun_audit_date',
            'finance_contract_sign_date',
        );

        $other_fields = array(
            'is_pre_sale',
            'show_bid_time',
            'ubsp_apply_id',
            'is_deposit',
        );
        $fields = array_merge($combine_keys, $other_fields);
        $prj_ext_info = M('prj_ext')->where(array('prj_id' => $prj_id))->field($fields)->find();
        $combine_keys = array_merge($combine_keys, array('control_seal_date'));

        $time_line_items = array_combine($combine_keys,array('借款人提交借款申请，客户经理尽职调查已完成','鑫盾风险审查已完成', '借款合同已签署', '借款人资金流转账户已受监控'));

        $date_format = 'Y年m月d日 H:i';
        $time_line_node_keys = array('title', 'time');

        //如果该项目无签署合同内容就不展示,控制借款人印章时间为鑫盾审核时间
        if (!$prj_ext_info['finance_contract_sign_date']) {
            unset($fields['finance_contract_sign_date']);
            $prj_ext_info['control_seal_date'] = $prj_ext_info['xindun_audit_date'];
        } else {
            $prj_ext_info['control_seal_date'] = $prj_ext_info['finance_contract_sign_date'];
        }

        foreach ($time_line_items as $key => $value) {
            $node = array_combine($time_line_node_keys, array($value, date($date_format, $prj_ext_info[$key])));
            if ($prj_ext_info[$key]) {
                $time_line_array['ready'][] = $node;
            }
        }

        //不是待开标状态
        if ($prj_info['bid_status'] != PrjModel::BSTATUS_WATING) {
            $time_line_array['begin'][] = array_combine($time_line_node_keys,
                array('项目已开标', date($date_format, $prj_info['start_bid_time'])));
            $first_order = D('Financing/PrjOrder')->getFirstOrderOfPrj($prj_id, $uid);
            if ($first_order && $uid) {
                $myInvestData = M('prj_order')->where(array('uid' => $uid, 'prj_id' => $prj_id))->select();
                foreach ($myInvestData as $myData) {
                    $investStatus = '您已投资该项目' . ($myData['money'] / 100) . '元';
                    $time_line_array['begin'][] = array_combine($time_line_node_keys,
                        array($investStatus, date($date_format, $myData['ctime'])));
                }
            } else {
                $investStatus = '投资人投资项目中';
                $time_line_array['begin'][] = array_combine($time_line_node_keys,
                    array($investStatus, date($date_format, $prj_info['start_bid_time'])));
            }
            if ($prj_info['bid_status'] >= prjModel::BSTATUS_FULL) {
                //截标读取截标时间
                if ($prj_info['bid_status'] == prjModel::BSTATUS_END) {
                    $end_time = $prj_info['end_bid_time'];
                    //满标读取满标时间
                } elseif ($prj_info['bid_status'] == prjModel::BSTATUS_FULL) {
                    $end_time = $prj_info['full_scale_time'];
                } elseif ($prj_info['remaining_amount'] > 0) {
                    $end_time = $prj_info['end_bid_time'];
                } else {
                    $end_time = $prj_info['full_scale_time'];
                }
                $time_line_array['begin'][] = array_combine($time_line_node_keys,
                    array('项目募集已完成', date($date_format, $end_time)));
            }
        }
        //保理资金流
        if (!$prj_info['business_type'] && !$prj_info['zhr_apply_id']) {
            $capital_stream = M('dynamic_capital_info')->where(array('ubsp_apply_id' => $prj_ext_info['ubsp_apply_id']))->field('bank_name,capital_status,process_time')->select();
        } else {
            //直融资金流，先判断是否是子标,子标取母标的资金流
            $parent_prj_id = M('prj')->where(array('id' => $prj_info['id']))->getField('spid');
            if ($parent_prj_id) {
                $capital_stream = M('prj_apply')->join('LEFT JOIN fi_dynamic_capital_info  ON fi_prj_apply.ubsp_apply_id = fi_dynamic_capital_info.ubsp_apply_id')
                    ->field('fi_dynamic_capital_info.bank_name,fi_dynamic_capital_info.capital_status,fi_dynamic_capital_info.process_time')->where(array('fi_prj_apply.prj_id' => $parent_prj_id))->select();
            } else {
                $capital_stream = M('prj_apply')->join('LEFT JOIN fi_dynamic_capital_info  ON fi_prj_apply.ubsp_apply_id = fi_dynamic_capital_info.ubsp_apply_id')
                    ->field('fi_dynamic_capital_info.bank_name,fi_dynamic_capital_info.capital_status,fi_dynamic_capital_info.process_time')->where(array('fi_prj_apply.prj_id' => $prj_info['id']))->select();
            }

        }
        if ($prj_info['bid_status'] >= PrjModel::BSTATUS_FULL && $capital_stream) {
            foreach ($capital_stream as $key => $value) {
                if ($value['capital_status'] && $key < 3) {
                    switch ($key) {
                        case 0:
                            $value['capital_status'] = '资金划转至借款人' . $this->pregAccountName($value['bank_name']) . '账户已完成';
                            break;
                        case 1:
                            $value['capital_status'] = '借款人还款至' . $this->pregAccountName($value['bank_name']) . '已完成';
                            break;
                        case 2:
                            $value['capital_status'] = $this->pregAccountName($capital_stream[1]['bank_name']).'划款至借款人'.$this->pregAccountName($capital_stream[2]['bank_name']).'账户已完成';
                            break;
                        default:
                            break;
                    }


                    $time_line_array['stream'][] = array_combine($time_line_node_keys,
                        array($value['capital_status'], date($date_format, $value['process_time'])));
                    $time_line_array['show_remark'] = 1;
                }
            }
        }

        if ($prj_ext_info['is_deposit']) {
            if ($prj_info['bid_status'] == PrjModel::BSTATUS_ZS_REPAYING || $prj_info['bid_status'] == PrjModel::BSTATUS_REPAID) {
                $time_line_array['stream'][] = array_combine($time_line_node_keys,
                    array('借款人还款至浙商银行指定账户已完成', date($date_format, $prj_info['actual_repay_time'])));
                $time_line_array['stream'][] = array_combine($time_line_node_keys,
                    array('浙商银行开始划拨资金至投资人存管户', date($date_format, $prj_info['actual_repay_time'])));
            }
            if ($prj_info['bid_status'] == PrjModel::BSTATUS_REPAID) {
                $zs_callback_time = M('prj_repay_callback_data')->where(['prj_id' => $prj_id])->getField('callback_time');
                $time_line_array['stream'][] = array_combine($time_line_node_keys,
                    array('投资人收到投资本金与利息', date($date_format, $zs_callback_time)));
                $time_line_array['end'][] = array_combine($time_line_node_keys,
                    array('项目结束', date($date_format, $zs_callback_time)));
            }
        } else {
            if ($prj_info['bid_status'] == PrjModel::BSTATUS_REPAID) {
                $time_line_array['stream'][] = array_combine($time_line_node_keys,
                    array('资金还款至投资人鑫合汇平台账户已完成', date($date_format, $prj_info['actual_repay_time'])));
                $time_line_array['stream'][] = array_combine($time_line_node_keys,
                    array('投资人收到投资本金与利息', date($date_format, $prj_info['actual_repay_time'])));
                $time_line_array['end'][] = array_combine($time_line_node_keys,
                    array('项目结束', date($date_format, $prj_info['actual_repay_time'])));
            }
        }
        return $time_line_array;
    }

    /**
     * 银行支行或者证券名称打星号处理
     * @param string $string
     * @return mixed
     */
    public function pregAccountName($string)
    {
        $string = trim($string);
        $pattern1 = '/(\S+)银行(\S+)支行(\S*)/';
        $pattern2 = '/(\S+)银行(\S*)/';
        if (preg_match($pattern1, $string)) {
            $replacement = '${1}银行***支行';
            $string = preg_replace($pattern1, $replacement, $string);
            return $string;
        } elseif (preg_match($pattern2, $string)) {
            $replacement = '${1}银行';
            $string = preg_replace($pattern2, $replacement, $string);
            return $string;
        } else {
            $pattern3 = '/(\S+)证券(\S*)/';
            $replacement = '***证券';
            $string = preg_replace($pattern3, $replacement, $string);
            return $string;
        }
    }

    /**
     * 满标的时候异步生产还款计划
     * @param $prj_id
     * @param bool $create_in_queue 是不是在队列中
     * @return bool
     */
    public function createRepayPlanAfterInvestFull($prj_id, $create_in_queue = false)
    {
        $prj_model = D('Financing/Prj');
        $project = $prj_model->where(['id' => $prj_id])->find();
        if (empty($project)) {
            throw_exception('项目不存在');
        }

        if (in_array($project['prj_type'], [PrjModel::PRJ_TYPE_H, PrjModel::PRJ_TYPE_J])
            || !in_array($project['bid_status'], [PrjModel::BSTATUS_FULL, PrjModel::BSTATUS_END])
            || $project['have_children']) {
            return true;
        }

        if ($project['spid']) {
            //子标 要判断当前子标所属母标下的所有子标都已经满标
            $invalid_children_prj = (int) $prj_model
                ->where([
                    'spid' => $project['spid'],
                    'status' => ['NOT IN', [
                        PrjModel::STATUS_FAILD,
                        PrjModel::STATUS_SPLITED_PARENT,
                        PrjModel::STATUS_SPLITED_PARENT_OK,
                    ]],
                    'bid_status' => ['LT', PrjModel::BSTATUS_FULL],
                ])
                ->getField('id');
            if ($invalid_children_prj) {
                return true;
            }

            $prj_id = $project['spid'];
        }

        $this->createPrjOrderRepayPlan($prj_id, $create_in_queue);
        return true;
    }

    /**
     * 生成还款计划 被调用的服务 满标生成和JAVA后台手动生成
     * @param $prj_id
     * @param bool $create_in_queue 是不是在队列中
     */
    public function createPrjOrderRepayPlan($prj_id, $create_in_queue = false)
    {
//        if (C('SIGNATURE_SWITCH')) { 2016年11月 把生产还款计划作为合同生成的开始时间
//            //激活签约流程
//            /* @var $sign_service SignService */
//            $sign_service = service('Signature/Sign');
//            $result = $sign_service->set_signing($prj_id, $create_in_queue);
//            if (empty($result)) {
//                throw_exception('开始签约流程失败!');
//            }
//        }
        /* @var $sign_service SignService */
        $sign_service = service('Signature/Sign');
        $queue_result = $sign_service->addOrderToQueue($prj_id);
        if ($queue_result[0]!=$queue_result[1])
        {
            if ($this->isProductMode()) {
                \Addons\Libs\Log\Logger::record('订单数和入队列的处理数不一致', 'sign', 1, array('prj_id'=>$prj_id,'queue_result'=>$queue_result));
            }
        }

        /* @var $srvProject ProjectService */
        $srvProject = service('Financing/Project');
        $ids = $srvProject->getChildrenId($prj_id, 'for_pay');
        foreach ($ids as $_prj_id) {

            if (!$this->depositPrjAllOrdersAreFreezeOK($_prj_id)) {
                throw_exception('存管项目存在冻结异常的订单,请处理再生成');
            }

            $prj_info = D('Financing/Prj')->where(['id'=>$_prj_id])->find();
            $prj_ext_info = D('Financing/PrjExt')->where(['prj_id'=>$_prj_id])->find();

            if (empty($prj_info) || empty($prj_ext_info)) {
                throw_exception('项目信息加载错误');
            }

            $order_count = D('Financing/PrjOrder')->where(['prj_id'=>$_prj_id])->count();
            $rand = rand(1, 100);

            if (C('GO_NEW_REPAYMENT') && $rand <= C('GO_NEW_REPAYMENT_RATE') && $order_count <= 300
                && $prj_info['prj_type'] == PrjModel::PRJ_TYPE_A) {

                $ret = true;
                // 设置截标时间
                if (!in_array($prj_info['bid_status'], [PrjModel::BSTATUS_FULL, PrjModel::BSTATUS_END])
                    && !$prj_ext_info['is_early_close']) {
                    throw_exception('该项目不能提前截标');
                }

                if ($prj_info['end_bid_time'] > time() && $prj_info['prj_class'] != PrjModel::PRJ_CLASS_LC) {
                    if ($prj_ext_info['is_early_close']) {
                        $ret = D('Financing/Prj')->setNewEndBidTime($prj_id);
                    }
                }



                \Addons\Libs\Log\Logger::info('新还款计划', 'new_repay_plan', $prj_id);

                //日益升走新还款计划流程
                $ret && $ret = \App\Modules\Financing\Service\RepaymentApiService::doCreateRepayPlan($_prj_id);
                if ($ret === false) {
                    \Addons\Libs\Log\Logger::info('生成还款计划失败1', 'new_repay_plan', $prj_id);
                    throw_exception('生成还款计划失败');
                } elseif ($ret['boolen']==0) {
                    \Addons\Libs\Log\Logger::info('生成还款计划失败2' . json_encode($ret), 'new_repay_plan', $prj_id);
                    throw_exception($ret['message']);
                }
            } else {
                //其他项目走老还款计划流程
                if ($srvProject->isOrderRepayPlanAllCreated($_prj_id)) {
                    continue;
                }
                try {
                    $srvProject->createOrderRepayPlan($_prj_id);
                } catch (Exception $e) {
                    $srvProject->createOrderRepayPlanCache($_prj_id, null);
                    throw_exception('PRJ_ID:' . $prj_id . ',' . $e->getMessage());
                }
            }
        }
    }

    /**
     * 银行托管冻结资金回调
     * @param array $response
     * @param array $request
     * @return bool
     */
    public static function asyncBuyFreezeCallBack(array $response, array $request, array $extra_params)
    {
        foreach (['bankSerialNo', 'status', 'instSettleDate'] as $field) {
            if (empty($response[$field])) {
                throw_exception('报文缺少必要参数:' . $field);
            }
        }

        $prj_order_model = D('Financing/PrjOrder');
        $prj_order = $prj_order_model->where(['id' => $request['dataId']])->find();

        if (empty($prj_order)) {
            throw_exception('项目订单不存在' . $request['dataId']);
        }

        if ($prj_order['bank_freeze_status'] == PrjOrderModel::BANK_FREEZE_STATUS_SUCCESS) {
            return true;
        }

        $back_data = [
            'bank_freeze_serial_no' => $response['bankSerialNo'],
            'bank_freeze_status' => $response['status'],
            'bank_settle_time' => $response['instSettleDate'],
            'mtime' => time(),
        ];

        $result = $prj_order_model->where(['id' => $request['dataId']])->save($back_data);
        if ($result === false) {
            throw_exception('订单冻结状态更新失败');
        }

        return true;
    }
    //浙商当前可投金额
    public function zsGetPrjInvestMoney(){
//        $where['is_show'] = 1;
        $where['status'] = 2;
        $where['bid_status'] = 2;
        $where['is_deposit'] = 1;//存管
        //当前可投金额=当前存管项目的剩余可投金额
        $arr['can_invest_money'] = M('prj')->join('left join fi_prj_ext on fi_prj.id=fi_prj_ext.prj_id')
            ->where( $where )->getField('sum(remaining_amount)');
        $arr['can_invest_money_num'] = $arr['can_invest_money']/100;
        $arr['can_invest_money'] = humanMoney($arr['can_invest_money'], 2, false);

        //即将开标金额=当天已发布但未开标的项目募集金额
        $where['bid_status'] = 1;
        $arr['will_invest_money'] = M('prj')->join('left join fi_prj_ext on fi_prj.id=fi_prj_ext.prj_id')
            ->where( $where )->getField('sum(demand_amount)');
        $arr['will_invest_money'] = humanMoney($arr['will_invest_money'], 2, false);
        
        //日均开标金额=过去三天存管项目开标金额总和/3
        $where3['is_deposit'] = 1;
        $start_time = strtotime(date('Y-m-d',strtotime('-3day')));
        $end_time = strtotime(date('Y-m-d'),strtotime());
//        $where3['bid_status'] = array(array('gt',1));
        $where3['start_bid_time'] = array(array('egt',$start_time),array('elt',$end_time));
        $arr['pass3day_invest_money'] = M('prj')->join('left join fi_prj_ext on fi_prj.id=fi_prj_ext.prj_id')
            ->where( $where3 )->getField('sum(demand_amount)');
        $arr['pass3day_invest_money'] = humanMoney($arr['pass3day_invest_money']/3, 2, false);
        return $arr;
    }


    /**
     * 存管项目的所以订单是否都经冻结OK
     * @param $prj_id
     * @return bool
     */
    public function depositPrjAllOrdersAreFreezeOK($prj_id)
    {
        $is_deposit = M('prj_ext')->where(['prj_id' => $prj_id])->getField('is_deposit');
        if (!$is_deposit) {
            return true;
        }

        $prj_order_model = new PrjOrderModel();
        $no_freeze_order = $prj_order_model->where([
            'prj_id' => $prj_id,
            'bank_freeze_status' => ['neq', PrjOrderModel::BANK_FREEZE_STATUS_SUCCESS
        ]])->getField('id');
        return $no_freeze_order ? false : true;
    }
}

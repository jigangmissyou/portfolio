<?php
/**
 * // +----------------------------------------------------------------------
 * // | UPG
 * // +----------------------------------------------------------------------
 * // | Copyright (c) 2012 http://upg.cn All rights reserved.
 * // +----------------------------------------------------------------------
 * // | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
 * // +----------------------------------------------------------------------
 * // | Author: kaihui.wang <wangkaihui@upg.cn>
 * // +----------------------------------------------------------------------
 * //  项目相关服务
 */
import_app("Financing.Model.PrjModel");
import_app("Financing.Model.PrjFilterModel");
import_app("Financing.Model.PrjOrderModel");
import_app("Financing.Model.PrjOrderPreModel");
import_app("Financing.Model.AssetTransferModel");
import_app("Zhr.Model.FinanceApplyModel");

class ProjectService extends BaseService
{
    const CACHE_TTL = 1800;
    const CACHE_PROJECT_PREFIX = 'CACHE_PROJECT_';
    const CACHE_PROJECT_CONTROLLER_PREFIX = 'CACHE_PROJECT_CONTROLLER_';
    const CACHE_SCHEDULE_PREFIX = 'CACHE_SCHEDULE_';

    private $mdCreditorTransfer;

    private $patch_join_financlist = '';

    function __construct() {
        parent::__construct();

        $this->mdCreditorTransfer = D('Financing/CreditorTransfer');
    }


    public function clearDataCache($prj_id)
    {
        $cache_key_controller = self::CACHE_PROJECT_CONTROLLER_PREFIX . $prj_id;
        S($cache_key_controller, null);
    }


    private function getDataCache($prj_id, $function)
    {
        $functions_use_controller = array('getPrjInfo', 'getDataById');
        $cache_key = self::CACHE_PROJECT_PREFIX . $function . '_' . $prj_id;
        $cache_key_controller = self::CACHE_PROJECT_CONTROLLER_PREFIX . $prj_id;

        if(!in_array($function, $functions_use_controller) || in_array($function, $functions_use_controller) && S($cache_key_controller)) {
            return S($cache_key);
        }

        return false;
    }


    private function setDataCache($prj_id, $function, $data)
    {
        $cache_key = self::CACHE_PROJECT_PREFIX . $function . '_' . $prj_id;
        $cache_key_controller = self::CACHE_PROJECT_CONTROLLER_PREFIX . $prj_id;

        S($cache_key, $data, self::CACHE_TTL);
        S($cache_key_controller, 1, self::CACHE_TTL);
    }


    public function setScheduleCache($prj_id, $demand_amount, $remaining_amount, $demand_amount_pre)
    {
        $key = self::CACHE_SCHEDULE_PREFIX . $prj_id;
        $data = array(
            'demand_amount' => $demand_amount,
            'remaining_amount' => $remaining_amount,
            'demand_amount_pre' => $demand_amount_pre,
        );
        S($key, $data, self::CACHE_TTL);
    }


    public function getScheduleCache($prj_id, $field=null)
    {
        $key = self::CACHE_SCHEDULE_PREFIX . $prj_id;
        $data = S($key);
        if(!$data) $data = array(
            'demand_amount' => 0,
            'remaining_amount' => 0,
            'demand_amount_pre' => 0,
        );
        if($field) return (int)$data[$field];

        return $data;
    }


    function getBidStatus($status = '',$prj_id=0, $pre_sale_status=0, $is_from_order=false)
    {
        if(is_numeric($prj_id) && $prj_id > 0 && $this->mdCreditorTransfer->isPrjTransfered($prj_id)) return '债权已转让';
        $bidStatus = array(
            PrjModel::BSTATUS_WATING => "待投标", //
            PrjModel::BSTATUS_BIDING => $is_from_order ? "投标结束" : "立即投资",
            PrjModel::BSTATUS_FULL => "投标结束",//2016315 将投资结束改成投标结束
            PrjModel::BSTATUS_REPAYING => "待还款",
            PrjModel::BSTATUS_REPAID => "已还款结束",
            PrjModel::BSTATUS_END => "投标结束",//2016315 将投资结束改成投标结束
            PrjModel::BSTATUS_REPAY_IN => "还款中",
            PrjModel::BSTATUS_FAILD => "已流标",
            PrjModel::BSTATUS_CANCEL => "已取消变现",
            PrjModel::BSTATUS_ZS_REPAYING => "待还款"
        );

        $preStatus = array(
            PrjOrderPreModel::STATUS_FAIL => "预售投标失败",
            PrjOrderPreModel::STATUS_DEFAULT => "预售投标中",
        );

        $st = isset($bidStatus[$status]) ? $bidStatus[$status] : "";
        if($pre_sale_status != 2 && $pre_sale_status != 0){//预售状态显示 3失败和1预售中
            $st = isset($preStatus[$pre_sale_status]) ? $preStatus[$pre_sale_status] : "";
        }

        return $st;
    }

    function getClientType($prj_id){
        $model = new PrjFilterModel();
        return $model->getClientType($prj_id);
    }

    /**
     * 获取列表
     * @param $uid 登录用户id
     */
    function getList($where = array(), $orderBy = "", $pageNumber = 1, $pageSize = 10, $groupBy = '', $getTotal = true, $uid = 0,$is_admin=0)
    {
        $model = M("prj");
        if ($pageNumber && $pageSize) $model = $model->page($pageNumber . "," . $pageSize);
        if ($orderBy) $model = $model->order($orderBy);
        if ($groupBy) $model = $model->group($groupBy);
        if ($where){
            if(!$is_admin){
                $where['mi_no'] = self::$api_key;
            }
            $model = $model->where($where);
        }
        $fields = array(
            '*',
            '(CASE WHEN next_repay_date>0 THEN next_repay_date ELSE last_repay_date END) AS real_repay_date',
        );
        $data = $model->field($fields)->select();
//         echo $model->getLastSql();

        //发布站点
        $mi_list = D('Application/Member')->MemberListNokey();

        if($data){
        	foreach ($data as $k=>$v){
//                $data[$k]['is_creditor_transfered'] = $this->mdCreditorTransfer->isPrjTransfered($v['id']); // 债权已转让

                $prjInfo = $this->getPrjInfo($v['id']);
                $data[$k]['is_creditor_transfered'] = $prjInfo['is_creditor_transfered'];

                //投放站点
                $data[$k]['mi_name'] = $mi_list[$v['mi_no']]['mi_name'];

                $data[$k]['ext'] = $prjInfo['ext'];// +2014/03/05 用于判断可转让图标
        	    $data[$k]['id_view'] = $this->getIdView($v['id']);
        		$data[$k]['time_limit_unit_view'] = $this->getTimeLimitUnit($v['time_limit_unit']);
        		$data[$k]['rate_type_view'] = $this->getRateType($v['rate_type']);
        		$data[$k]['rate_symbol'] = $v['rate_type'] == PrjModel::RATE_TYPE_DAY ? '‰' : '%';
        		$data[$k]['repay_way_view'] = $this->getRepayWay($v['repay_way']);
        		$data[$k]['rate'] = number_format($v['rate'],2);
        		$data[$k]['start_bid_time_view'] = date('Y-m-d',$v['start_bid_time'])."<br>".date('H:i:s',$v['start_bid_time']);
        		$data[$k]['min_bid_amount_view'] = humanMoney($v['min_bid_amount'],2,false)."元";
            $data[$k]['min_bid_amount_name'] = humanMoney($v['min_bid_amount'],2,false);
        		$data[$k]['step_bid_amount_view'] = humanMoney($v['step_bid_amount'],2,false)."元";
        		$data[$k]['demand_amount_view'] = humanMoney($v['demand_amount'],2);
        		$data[$k]['schedule'] = $this->getSchedule($v['demand_amount'], $v['remaining_amount']);
        		$data[$k]['guarantor'] = $this->getGuarantor($v['guarantor_id']);
        		$data[$k]['real_money'] = humanMoney(($v['demand_amount']-$v['remaining_amount']));
        		$data[$k]['bid_status_view'] = $this->getBidStatus($v['bid_status'],$v['id']);
            $data[$k]['prj_type_name'] = $this->getPrjTypeName($v['prj_type']);
            $data[$k]['safeguards_view'] = $v['safeguards'] == 1 ? "保":"本";
                $data[$k]['safeguards_name2'] = $this->getSafeguards($v['safeguards']);
                $data[$k]['is_xinchun'] = $prjInfo['is_xinchun'];
                // 利率统一转换成千分
                if ($data[$k]['rate_type'] != PrjModel::RATE_TYPE_DAY) {
                    $data[$k]['rate_view'] = number_format($v['rate'] / 10, 2);
                } else {
                    $data[$k]['rate_view'] = number_format($v['rate'], 2);
                }


                $data[$k]['has_tender_intention'] = 0;
                //是否已有投资意向
                if ($v['bid_status'] == PrjModel::BSTATUS_WATING) {
                    $data[$k]['bid_status_view'] = "即将开标";
                    $uid = (int)$uid;
                    if ($uid) {
                        $info = M("tender_intention")->where(array("prj_id" => $v['id'], "uid" => $uid))->find();
                        if ($info) {
                            $data[$k]['has_tender_intention'] = 1;
                            $data[$k]['bid_status_view'] = "已提交意向";
                        }
                    }
                }

            }
        }
//         echo "<pre>";
//         print_r($data);
        $output['data'] = $data;

        if ($getTotal) {
            $modelCount = M("prj");
            if ($where) $modelCount = $modelCount->where($where);
            if ($groupBy) $modelCount = $modelCount->group($groupBy);
            $result = $modelCount->field("COUNT(*) AS CNT")->find(); // TODO: InnoDB的COUN(*)问题
            $totalRow = (int)$result['CNT'];
            $output['total_row'] = $totalRow;
        }
        return $output;
    }
    /**
     * 获取新标的列表 （手机端调用）
     * @param int $is_pre_sale 0-非预售新标  1-预售新标
     * @return mixed
     */
    function getNewBidList($is_pre_sale = 0,$pages = 1,$pageSize = 10,$uid = 0){
        $where = array(
            'prj.status' => PrjModel::STATUS_PASS,
            'prj.bid_status' => PrjModel::BSTATUS_WATING,
            'ext.is_pre_sale' => $is_pre_sale,
            'prj.mi_no' => array(array('eq',C('API_INFO')['api_key_all']),array('eq',self::$api_key),'or'),
            'prj.is_show' => 1
        );
        $field = "ext.is_pre_sale,ext.is_extend,ext.extend_time,ext.extend_time_unit,ext.demand_amount_pre,
            prj.id,prj.prj_name,prj.prj_type,prj.time_limit,prj.time_limit_unit,prj.year_rate,prj.min_bid_amount,prj.max_bid_amount,
            prj.demand_amount,prj.start_bid_time,prj.bid_status,ext.guarantor2_id,ext.guarantor3_id";
        $result = M("prj")->Table("fi_prj prj")
                    ->page($pages.",".$pageSize)
                    ->where($where)
                    ->field($field)
                    ->join("INNER JOIN fi_prj_ext ext ON ext.prj_id = prj.id")
                    ->select();
    //    echo M()->_sql();
        service("Financing/PrjRepayExtend");
        foreach ($result as &$v) {
            $v['demand_amount_view'] = humanMoney($v['demand_amount'],2);
            $temp = explode(" ",$v['demand_amount_view']);
            $v['demand_amount_value'] = $temp[0];
            $v['demand_amount_unit'] = $temp[1];
            $v['min_bid_amount_name'] = humanMoney($v['min_bid_amount'],2,false);
            $v['max_bid_amount_name'] = humanMoney($v['max_bid_amount'],2,false);
            $v['schedule'] = $this->getSchedule($v['demand_amount'], $v['demand_amount']-$v['demand_amount_pre']);
            $v['year_rate'] = number_format($v['year_rate'] / 10, 2);
            $v['prj_type_name'] = $this->getPrjTypeName($v['prj_type']);
            $v['uni_symbol'] = '+';
            $v['time_limit_unit_view'] = $this->getTimeLimitUnit($v['time_limit_unit']);
            $v['time_limit_extend'] = $v['extend_time'];
            $v['time_limit_extend_unit'] = $this->getTimeLimitUnit($v['extend_time_unit']);
            $v['time_limit_comment'] = preg_replace('/A/',$v['time_limit_extend'],PrjRepayExtendService::TIME_LIMIT_COMMENT);
            $v['start_bid_time_diff'] = max(0, $v['start_bid_time'] - time());

            if($is_pre_sale == 0 && $uid > 0){
                // 提醒数据
                $remind = D('Public/Remind')->getRemind($uid, RemindModel::REMIND_TYPE_START_BID, $v['id']);
                if ($remind) {
                    $v['remind'] = array(
                        'remind_id' => $remind['id'],
                        'is_available' => $remind['is_available'],
                    );
                } else {
                    $v['remind'] = array(
                        'remind_id' => '0',
                        'is_available' => '0',
                    );
                }

            }

            $num = 1;
            if($v['guarantor2_id'] > 0) $num++;
            if($v['guarantor3_id'] > 0) $num++;
            $v['guarantor_num'] = $num;

            unset($v['time_limit_unit'],$v['demand_amount'],$v['min_bid_amount'],$v['max_bid_amount'],$v['prj_type'], $v['guarantor2_id'],$v['guarantor3_id']);
            $v['transfer_id'] = 0;
        }
        $totalRow = M("prj")->Table("fi_prj prj")
            ->where($where)
            ->join("INNER JOIN fi_prj_ext ext ON ext.prj_id = prj.id")
            ->count();

        $data = array(
            'list' => $result,
            'current_page' => $pages,
            'total' => $totalRow,
            'total_page' => ceil($totalRow/$pageSize),
        );

        return $data;
    }

    /**
     * 项目列表统一方法
     * @param array $where
     * @param string $order_by
     * @param int $page
     * @param int $page_size
     * @param string $group_by
     * @param bool $get_total
     * @param int $uid 当前登录用户
     * @param array $extraParams 其他参数放在这里
     * @return mixed
     */
    function fetchProjectList($where = array(), $order_by = '', $page = 1, $page_size = 10, $group_by = '', $get_total = true, $uid = 0, $extraParams = array())
    {
        if(!$where['_string']) {
            unset($where['_string']);
        }
        $model = M('prj');
        if ($page && $page_size) {
            $model = $model->page($page . ',' . $page_size);
        }

        $model = $this->recreateModel($model, $where, $order_by);

        if ($order_by) $model = $model->order($order_by);
        if ($group_by) $model = $model->group($group_by);

        //将数据prj_id缓存10秒
        $key = 'fetchProjectList' . md5(json_encode($model->getOptions()));
        $cache = \Addons\Libs\Cache\Redis::getInstance();
        $str = $cache->get($key);
        if ($str && C('APP_STATUS') == 'product') {
            $model->resetWhere()->resetJoin()->where(array('fi_prj.id' => array('IN', json_decode($str, true))));
            $data = $model->page(1, $page_size)->select();
            $arr_prj_id = json_decode($str, true);
        } else {
            $data = $model->select();
//            echo $model->getLastSql();die();
            if ($data) {
                $arr_prj_id = array_column($data, 'id');
                if (!empty($arr_prj_id)) {
                    $cache->setex($key, 10, json_encode($arr_prj_id));
                }
            }
        }

        //获取项目其他信息
        if ($arr_prj_id) {
            //ext信息
            $prj_ext_infos = D('Financing/Prj')->getMultiPrjExtInfos($arr_prj_id);

            //其他扩展信息
            $spical_infos = array();
            $prjs = $data;
            while ($prjs) {
                $prj_types = array_diff_key(array_column($prjs, 'prj_type', 'id'), array());
                $spical_infos2 = $this->getMultiSpicalPrjInfo($prj_types);
                $spical_infos += $spical_infos2 ? $spical_infos2 : array();

                //富二代
                $p_prj_ids = array();
                foreach ($prjs as $each) {
                    if (array_key_exists($each['prj_id'], $spical_infos)) continue;
                    if (empty($each['transfer_id']) || empty($each['p_prj_id'])) continue;

                    $p_prj_ids[] = $each['p_prj_id'];
                }
                if ($p_prj_ids) {
                    $prjs = D('Financing/Prj')->getMultiPrjInfos($p_prj_ids);
                } else {
                    break;
                }
            }
        }

        //调整位置，如果是速兑通的，就去掉这里的order
        if ($data) {
            $data = $this->formatProject($data, $prj_ext_infos, $spical_infos, $uid, $extraParams);
        }
        $output['data'] = $data;

        if ($get_total) {
            $modelCount = M('prj');
            if ($where) {
                $modelCount = $modelCount->where($where);
            }
            if ($group_by) {
                $modelCount = $modelCount->group($group_by);
            }

            $this->patchJoinFinancList($modelCount);
            $result = $modelCount->field('COUNT(*) AS CNT')->cache(true, 60)->find();
            $totalRow = (int) $result['CNT'];
            $output['total_row'] = $totalRow;
        }
        return $output;
    }
    /**
     * 重新加工列表搜索模型2
     * @param $model
     * @param array $where
     * @param string $order_by
     * @return mixed
     * @author ywx
     */
    private function recreateModelHot($model, &$where = array(), $order_by = '')
    {
        if ($where) {
            $api_info = C('API_INFO');
            if ($where['prj_type'] == Prjmodel::PRJ_TYPE_H) {
                $bid_status_order = "
            (
                CASE
                WHEN (bid_status = " . PrjModel::BSTATUS_BIDING . " AND prj_type = '" . PrjModel::PRJ_TYPE_H . "' AND mi_no = '1234567890') THEN 4.01
                WHEN (bid_status = " . PrjModel::BSTATUS_BIDING . " AND  transfer_id IS NULL AND prj_sort >0 AND mi_no IN ('1234567889', '1234567890')) THEN 1
                WHEN (bid_status = " . PrjModel::BSTATUS_BIDING . " AND  transfer_id IS NULL AND prj_sort=0 AND is_new=0 AND mi_no IN ('1234567889', '1234567890')) THEN 2
                WHEN (bid_status = " . PrjModel::BSTATUS_BIDING . " AND  transfer_id IS NULL AND prj_sort=0 AND is_new=1 AND mi_no IN ('1234567889', '1234567890')) THEN 3
                WHEN (bid_status = " . PrjModel::BSTATUS_BIDING . " AND  transfer_id IS NULL AND prj_sort >0 AND mi_no NOT IN ('1234567889', '1234567890','1234567992')) THEN 4.1
                WHEN (bid_status = " . PrjModel::BSTATUS_BIDING . " AND  transfer_id IS NULL AND prj_sort=0 AND is_new=0 AND mi_no NOT IN ('1234567889', '1234567890','1234567992')) THEN 4.2
                WHEN (bid_status = " . PrjModel::BSTATUS_BIDING . " AND  transfer_id IS NULL AND prj_sort=0 AND is_new=1 AND mi_no NOT IN ('1234567889', '1234567890','1234567992')) THEN 4.3
                WHEN bid_status = " . PrjModel::BSTATUS_WATING . " AND transfer_id IS NULL AND mi_no IN ('1234567889', '1234567890') THEN 5
                WHEN bid_status = " . PrjModel::BSTATUS_WATING . " AND transfer_id IS NULL AND mi_no NOT IN ('1234567889', '1234567890','1234567992') THEN 5.1
                WHEN bid_status IN (" . PrjModel::BSTATUS_FULL . "," . PrjModel::BSTATUS_END . ") AND transfer_id IS NULL THEN 6
                WHEN bid_status IN (" . PrjModel::BSTATUS_REPAY_IN . "," . PrjModel::BSTATUS_REPAYING . ") AND transfer_id IS NULL THEN 8
                WHEN bid_status = " . PrjModel::BSTATUS_REPAID . " AND transfer_id IS NULL THEN 9
                ELSE 100
                END) AS bid_status_order,
                (1 - remaining_amount / demand_amount) AS order_schedule";
            } elseif ($order_by != self::getDefaultOrder()) {
                $bid_status_order = "
            (
                CASE
                WHEN (bid_status = " . PrjModel::BSTATUS_BIDING . ") THEN 1
                WHEN (bid_status = " . PrjModel::BSTATUS_WATING . ")  THEN 2
                WHEN (bid_status IN (" . PrjModel::BSTATUS_FULL . "," . PrjModel::BSTATUS_END . ")) THEN 3
                WHEN (bid_status IN (" . PrjModel::BSTATUS_REPAY_IN . "," . PrjModel::BSTATUS_REPAYING . ")) THEN 4
                WHEN (bid_status = " . PrjModel::BSTATUS_REPAID . ")  THEN 5
                ELSE 100
                END) AS bid_status_order";
            }
            $bid_status_order = $bid_status_order.", ( CASE WHEN e.hotPrj_position > 0 THEN e.hotPrj_position ELSE 100 END  ) AS hotPrj_position1, ( CASE WHEN e.hotPrj_position > 0 THEN e.hotPrj_position ELSE fi_prj.id END  ) AS hotPrj_position2";

            //分渠道拼装
            if (self::$api_key != $api_info['api_key'] && self::$api_key != $api_info['api_key_all']) {
                $where['m.mi_no'] = array(array('eq', self::$api_key), array('eq', $api_info['api_key_all']), 'OR');
                $model = $model->join('INNER JOIN fi_prj_ext e ON p.id = e.prj_id')
                    ->join('left join fi_prj_member m on m.prj_id = fi_prj.id');
                $model = $model->where($where);
            } else {
                $model = $model->join('INNER JOIN fi_prj_ext e ON fi_prj.id = e.prj_id')->where($where);
            }
        }

        $this->patchJoinFinancList($model);
        $model = $model->field('fi_prj.*' . ($bid_status_order ? ', ' . $bid_status_order : ''));
        return $model;
    }
    /**
     * 项目列表统一方法
     * @param array $where
     * @param string $order_by
     * @param int $page
     * @param int $page_size
     * @param string $group_by
     * @param bool $get_total
     * @param int $uid 当前登录用户
     * @param array $extraParams 其他参数放在这里
     * @return mixed
     */
    function fetchProjectHotList($where = array(), $order_by = '', $page = 1, $page_size = 10, $group_by = '',$get_total = true, $uid = 0, $extraParams = array())
    {
//         if(!$where['_string']) {
//             unset($where['_string']);
//         }

        $model = M('prj');
        if ($page && $page_size) {
            $model = $model->page($page . ',' . $page_size);
        }
        $model = $this->recreateModelHot($model, $where, $order_by);
        if ($order_by) $model = $model->order($order_by);
        if ($group_by) $model = $model->group($group_by);

        //将数据prj_id缓存10秒
        $key = 'fetchProjectList' . md5(json_encode($model->getOptions()));
        $cache = \Addons\Libs\Cache\Redis::getInstance();
        $str = $cache->get($key);
        if ($str && C('APP_STATUS') == 'product') {

            $model->resetWhere()->where(array('fi_prj.id' => array('IN', json_decode($str, true))));
            $data = $model->page(1, $page_size)->select();
            $arr_prj_id = json_decode($str, true);
        } else {
            $data = $model->select();
            if ($data) {
                $arr_prj_id = array_column($data, 'id');
                if (!empty($arr_prj_id)) {
                    $cache->setex($key, 10, json_encode($arr_prj_id));
                }
            }
        }

        //获取项目其他信息
        if ($arr_prj_id) {
            //ext信息
            $prj_ext_infos = D('Financing/Prj')->getMultiPrjExtInfos($arr_prj_id);
            foreach($data as $key=>$val){
                $prjid= $val['id'];
                $data[$key][is_hot]= $prj_ext_infos[$prjid]['is_hot'];
                $data[$key][hotPrj_position]= $prj_ext_infos[$prjid]['hotPrj_position'];
                $data[$key]['is_deposit'] = $prj_ext_infos[$prjid]['is_deposit'];
            }
        }

        //调整位置，如果是速兑通的，就去掉这里的order
//        if ($data) {
//           $data = $this->formatProject($data, $prj_ext_infos, '', $uid, $extraParams);
//        }
        $output['data'] = $data;
        return $output;
    }
    /**
     * 重新加工列表搜索模型
     * @param $model
     * @param array $where
     * @param string $order_by
     * @return mixed
     */
    private function recreateModel($model, &$where = array(), $order_by = '')
    {
        if ($where) {
            $api_info = C('API_INFO');
            if ($where['prj_type'] == Prjmodel::PRJ_TYPE_H) {
                $bid_status_order = "
            (
                CASE
                WHEN (bid_status = " . PrjModel::BSTATUS_BIDING . " AND prj_type = '" . PrjModel::PRJ_TYPE_H . "' AND mi_no = '1234567890') THEN 4.01
                WHEN (bid_status = " . PrjModel::BSTATUS_BIDING . " AND  transfer_id IS NULL AND prj_sort >0 AND mi_no IN ('1234567889', '1234567890')) THEN 1
                WHEN (bid_status = " . PrjModel::BSTATUS_BIDING . " AND  transfer_id IS NULL AND prj_sort=0 AND is_new=0 AND mi_no IN ('1234567889', '1234567890')) THEN 2
                WHEN (bid_status = " . PrjModel::BSTATUS_BIDING . " AND  transfer_id IS NULL AND prj_sort=0 AND is_new=1 AND mi_no IN ('1234567889', '1234567890')) THEN 3
                WHEN (bid_status = " . PrjModel::BSTATUS_BIDING . " AND  transfer_id IS NULL AND prj_sort >0 AND mi_no NOT IN ('1234567889', '1234567890','1234567992')) THEN 4.1
                WHEN (bid_status = " . PrjModel::BSTATUS_BIDING . " AND  transfer_id IS NULL AND prj_sort=0 AND is_new=0 AND mi_no NOT IN ('1234567889', '1234567890','1234567992')) THEN 4.2
                WHEN (bid_status = " . PrjModel::BSTATUS_BIDING . " AND  transfer_id IS NULL AND prj_sort=0 AND is_new=1 AND mi_no NOT IN ('1234567889', '1234567890','1234567992')) THEN 4.3
                WHEN bid_status = " . PrjModel::BSTATUS_WATING . " AND transfer_id IS NULL AND mi_no IN ('1234567889', '1234567890') THEN 5
                WHEN bid_status = " . PrjModel::BSTATUS_WATING . " AND transfer_id IS NULL AND mi_no NOT IN ('1234567889', '1234567890','1234567992') THEN 5.1
                WHEN bid_status IN (" . PrjModel::BSTATUS_FULL . "," . PrjModel::BSTATUS_END . ") AND transfer_id IS NULL THEN 6
                WHEN bid_status IN (" . PrjModel::BSTATUS_REPAY_IN . "," . PrjModel::BSTATUS_REPAYING . ") AND transfer_id IS NULL THEN 8
                WHEN bid_status = " . PrjModel::BSTATUS_REPAID . " AND transfer_id IS NULL THEN 9
                ELSE 100
                END) AS bid_status_order,
                (1 - remaining_amount / demand_amount) AS order_schedule";
            } elseif ($order_by != self::getDefaultOrder()) {
                $bid_status_order = "
            (
                CASE
                WHEN (bid_status = " . PrjModel::BSTATUS_BIDING . ") THEN 1
                WHEN (bid_status = " . PrjModel::BSTATUS_WATING . ")  THEN 2
                WHEN (bid_status IN (" . PrjModel::BSTATUS_FULL . "," . PrjModel::BSTATUS_END . ")) THEN 3
                WHEN (bid_status IN (" . PrjModel::BSTATUS_REPAY_IN . "," . PrjModel::BSTATUS_REPAYING . ")) THEN 4
                WHEN (bid_status = " . PrjModel::BSTATUS_REPAID . ")  THEN 5
                ELSE 100
                END) AS bid_status_order";
            }

            //分渠道拼装
            if (self::$api_key != $api_info['api_key'] && self::$api_key != $api_info['api_key_all']) {
                $where['m.mi_no'] = array(array('eq', self::$api_key), array('eq', $api_info['api_key_all']), 'OR');
                $model = $model->join('left join fi_prj_member m on m.prj_id = fi_prj.id');
                $model = $model->where($where);
            } else {
                $model = $model->where($where);
            }
        }

        $this->patchJoinFinancList($model);
        $model = $model->field('fi_prj.*' . ($bid_status_order ? ', ' . $bid_status_order : ''));
        return $model;
    }

    /**
     * 格式化项目列表标的信息
     * @param $data 标的信息
     * @param $prj_ext_infos ext表信息
     * @param $spical_infos 分类型扩展表信息
     * @param $uid 当前登录用户UID
     * @param array $extraParams
     * @return array
     */
    private function formatProject($data, $prj_ext_infos, $spical_infos, $uid = 0, $extraParams = array())
    {
        $_config = C('FAST_CASH');
        if ($extraParams['is_qfx']) {
            $qfx_service = service("Financing/CompanySalary")->init($uid);
        }

        foreach ($data as $k => $v) {
            $prjInfo = $v;
            $prjInfo['is_creditor_transfered'] = !!$prj_ext_infos[$v['id']]['creditor_rights_transfered'];

            if (isset($spical_infos[$v['id']])) {
                $prjInfo['ext'] = $spical_infos[$v['id']];
            } elseif ($v['transfer_id'] && isset($spical_infos[$v['p_prj_id']])) {
                $prjInfo['ext'] = $spical_infos[$v['p_prj_id']];
            } else {
                $prjInfo['ext'] = array();
            }
            $prjInfo['safeguards_name2'] = $this->getSafeguards($prjInfo['safeguards']);

            $prjInfo['act_prj_ext'] = isset($prj_ext_infos[$v['id']]) ? $prj_ext_infos[$v['id']] : array();
            $prjInfo['old_end_bid_time'] = $prjInfo['act_prj_ext']['old_end_bid_time']
                ? $prjInfo['act_prj_ext']['old_end_bid_time'] : $v['end_bid_time'];

            $data[$k]['is_pre_sale'] = $prjInfo['act_prj_ext']['is_pre_sale'];
            $data[$k]['is_auto_start'] = $prjInfo['act_prj_ext']['is_auto_start'];
            $data[$k]['is_extend'] = $prjInfo['act_prj_ext']['is_extend'];
            //预售
            if ($prjInfo['act_prj_ext']['is_pre_sale'] == 1 && $v['bid_status'] == PrjModel::BSTATUS_WATING) {
                //pc
                $data[$k]['pre_schedule'] = $this->getSchedule($prjInfo['demand_amount'], $prjInfo['demand_amount'] - $prjInfo['act_prj_ext']['demand_amount_pre']);
                $data[$k]['pre_remaind_amount'] = humanMoney($prjInfo['demand_amount'] - $prjInfo['act_prj_ext']['demand_amount_pre'], 2, false) . '元';
                $data[$k]['remaining_amount'] = $data[$k]['pre_remaind_amount'];//如果是预售 前端列表页统一使用 remaining_amount 显示 todo mobile 预售
                //mobile
                $data[$k]['pre_sale_schedule'] = $this->getSchedule($prjInfo['demand_amount'], $prjInfo['demand_amount'] - $prjInfo['act_prj_ext']['demand_amount_pre']);
                $data[$k]['rest_remaind_amount'] = humanMoney($prjInfo['demand_amount'] - $prjInfo['act_prj_ext']['demand_amount_pre'], 2);
                $data[$k]['is_sale_over'] = ($prjInfo['demand_amount'] - $prjInfo['act_prj_ext']['demand_amount_pre']) == 0 ? "1" : "0";
            }else{
                $data[$k]['remaining_amount'] = humanMoney($v['remaining_amount'], 2, false);
                $data[$k]['remaining_amount_wap'] = $v['remaining_amount'];//wap端转换为分 使用
            }
            $data[$k]['is_extend'] = $prjInfo['act_prj_ext']['is_extend'];
            if ($prjInfo['act_prj_ext']['is_extend'] == 1) {
                $timeshow = service("Financing/PrjRepayExtend")->getTimeLimitShow($v['id']);
                $data[$k]['time_limit_comment'] = preg_replace('/A/', $timeshow['time_limit_extend'], PrjRepayExtendService::TIME_LIMIT_COMMENT);
                $data[$k]['uni_symbol'] = $timeshow['uni_symbol'];
                $data[$k]['time_limit_extend'] = $timeshow['time_limit_extend'];
                $data[$k]['time_limit_extend_unit'] = $timeshow['time_limit_extend_unit'];
                $data[$k]['time_limit_comment'] = preg_replace('/A/',$timeshow['time_limit_extend'],PrjRepayExtendService::TIME_LIMIT_COMMENT);
            }
            $salaryBtn = 1;
            $data[$k]['project_raw'] = $prjInfo;
            $data[$k]["ext"] = $prjInfo['ext'];
            $data[$k]['is_xinchun'] = $prjInfo['act_prj_ext']['is_xinchun'];

            if ($extraParams['is_qfx']) {
                $res = $qfx_service->getCompanysByPrjId($v['id']);
                if (!$res) {
                    $res = array();
                }
                $salary_info = array(
                    'is_qfx' => $extraParams['is_qfx'],
                    'company_id' => $prjInfo['act_prj_ext']['company_id'],
                );
                $salary = $qfx_service->buyBeforeCheckPermission($salary_info);
                $salaryBtn = $salary ? 1 : 0;
                $data[$k]['salary_permission'] = $salaryBtn;//列表的按钮相关
            }
            if ($v['business_type'] == 2) {
                $bank_name = M('bill_info')->where(array('prj_id' => $v['id']))->getField("bank_name");
                $data[$k]['bank_name'] = $bank_name;
            }
            $data[$k]['salary_permission'] = $salaryBtn;//列表的按钮相关
            $data[$k]['companylist'] = $res;

            $data[$k]['id_view'] = $idView = $this->getIdView($v['id']);
            $data[$k]['time_limit_unit_view'] = $this->getTimeLimitUnit($v['time_limit_unit']);
            $data[$k]['rate_type_view'] = $this->getRateType($v['rate_type']);//2条
            $data[$k]['rate_symbol'] = $v['rate_type'] == PrjModel::RATE_TYPE_DAY ? '‰' : '%';
            $data[$k]['repay_way_view'] = $this->getRepayWay($v['repay_way']);//2条

            $data[$k]['rate'] = number_format($v['rate'], 2);
            $data[$k]['start_bid_time_view'] = date('Y-m-d', $v['start_bid_time']) . "<br>" . date('H:i:s', $v['start_bid_time']);
            $data[$k]['min_bid_amount_view'] = humanMoney($v['min_bid_amount'], 2, false) . "元";
            $data[$k]['min_bid_amount_name'] = humanMoney($v['min_bid_amount'], 2, false);
            $data[$k]['step_bid_amount_view'] = humanMoney($v['step_bid_amount'], 2, false) . "元";
            $data[$k]['max_bid_amount_view'] = humanMoney($v['max_bid_amount'], 2, false) . "元";
            $data[$k]['demand_amount_view'] = humanMoney($v['demand_amount'], 2);
            $data[$k]['matched_amount_view'] = humanMoney($v['matched_amount'], 2, false);
            $data[$k]['schedule'] = $this->getSchedule($v['demand_amount'], $v['remaining_amount']);
            $data[$k]['guarantor'] = $this->getGuarantor($v['guarantor_id']);//1条
            $data[$k]['real_money'] = humanMoney(($v['demand_amount'] - $v['remaining_amount']));

            $data[$k]['bid_status_view'] = $this->getBidStatus($v['bid_status'],$v['id']);//1条
            if ($v['matched_amount'] > 0 && $v['bid_status'] == PrjModel::BSTATUS_BIDING) {
                if ($v['demand_amount'] == $v['remaining_amount'] + $v['matched_amount']) {
                    $data[$k]['bid_status_view'] = '投标结束';
                }
            }

            $data[$k]['prj_type_name'] = $this->getPrjTypeName($v['prj_type']);
            $data[$k]['safeguards_view'] = $v['safeguards'] == 1 ? "保" : "本";
            //$data[$k]['prj_business_type_name'] = $this->getPrjBusinessType($v['prj_business_type']);
            if (in_array($v['prj_business_type'],array('C', 'D', 'E', 'C0V14', 'C00303'))) {
                $data[$k]['prj_business_type_name'] = '经营贷';
            } else {
                $data[$k]['prj_business_type_name'] = $this->getPrjBusinessType($v['prj_business_type']);
            }
            $data[$k]['demand_amount_array'] = $this->getDemandAmount($v['demand_amount']);

            // 利率统一转换成千分
            if ($data[$k]['rate_type'] != PrjModel::RATE_TYPE_DAY) {
                $data[$k]['rate_view'] = number_format($v['rate'] / 10, 2);
            } else {
                $data[$k]['rate_view'] = number_format($v['rate'], 2);
            }

            $data[$k]['has_tender_intention'] = 0;
            //是否已有投资意向
            if ($v['bid_status'] == PrjModel::BSTATUS_WATING) {
                $data[$k]['bid_status_view'] = "即将开标";
                $uid = (int)$uid;
                if ($uid) {
                    $info = M("tender_intention")->where(array("prj_id" => $v['id'], "uid" => $uid))->find();
                    if ($info) {
                        $data[$k]['has_tender_intention'] = 1;
                        $data[$k]['bid_status_view'] = "已提交意向";
                    }
                }
            }

            //是否可变现
            if ((isset($prjInfo['ext']['is_transfer']) && $prjInfo['ext']['is_transfer']) || ($prjInfo['prj_type'] == PrjModel::PRJ_TYPE_H && $prjInfo['act_prj_ext']['cash_level'] <= $_config['cash_level_limit'])) {
                $data[$k]['can_bianxian'] = (string)1;
            } else {
                $data[$k]['can_bianxian'] = (string)0;
            }

            if ($prjInfo['prj_type'] == PrjModel::PRJ_TYPE_H) {
                $data[$k]['demand_amount_array'] = $this->getDemandAmount($v['demand_amount'], 1);
            }
            $max_bid_amount_view = humanMoney($v['max_bid_amount'], 0, false, '', true, '');//去掉小数位,太长
            $min_max_bid_amount = humanMoney($v['min_bid_amount'], 0, false, '', true, '');
            if ($v['max_bid_amount']) {
                //投资金额区间
                $data[$k]['min_max_bid_amount'] = $min_max_bid_amount . "-" . $max_bid_amount_view;
            } else {
                //起投金额
                $data[$k]['min_max_bid_amount'] = $min_max_bid_amount." +";
            }
            $data[$k]['schedule'] = $this->getSchedule($v['demand_amount'], $v['remaining_amount']);
            if ($prjInfo['act_prj_ext']['is_pre_sale']) $data[$k]['pre_schedule'] = $this->getSchedule($v['demand_amount'], $v['demand_amount'] - $prjInfo['act_prj_ext']['demand_amount_pre']);
            $data[$k]['guarantor'] = $this->getGuarantor($v['guarantor_id']);

            //是否富二代
            $viewUrl = U('Financing/Invest/view', array('id' => $idView));
            $data[$k]['is_fed'] = 0;
            if ($v['transfer_id']) {
                $data[$k]['is_fed'] = 1;
                $data[$k]['expire_time_limit'] = $this->parseExpireTime($v['transfer_id']);
                $data[$k]['id_view'] = $this->getIdView($v['transfer_id']);
                $data[$k]['schedule'] = $v['transfer_status'] == AssetTransferModel::PRJ_TRANSFER_BIDED ? 100 : 0;
                $data[$k]['demand_amount_view'] = humanMoney($v['demand_amount'], 2, false) . "元";
                $data[$k]['can_transfer'] = service("Financing/Transfer")->fedCanTransfer($v['transfer_id']);
                $transferInfo = M("asset_transfer")->find($v['transfer_id']);
                $data[$k]['income_get_view'] = humanMoney(($transferInfo['property'] + $transferInfo['prj_revenue'] - $transferInfo['money']), 2, false);
                $data[$k]['transfer_buy_date'] = date('Y-m-d H:i:s', $transferInfo['buy_time']);
            }
            $data[$k]['title'] = service("Financing/Financing")->getTitle($data[$k], $data[$k]['is_fed']);
            $data[$k]['ico'] = service("Financing/Financing")->getIcoNew($prjInfo, $v['is_fed']);
            $data[$k]['rate_show'] = service("Index/Index")->showRate($v, $v['is_fed'], true);
            //$data[$k]['name_style'] = service("Financing/Project")->setPrjNameStyle($v, $v['is_fed']);
            $data[$k]['name_style'] = service("Financing/Prj")->getPrjTitleShow($v['id'], $v);
            if ($v['prj_type'] == PrjModel::PRJ_TYPE_H) {
                try {
                    //在显示中在加上一天
                    if($v['bid_status']!=PrjModel::BSTATUS_BIDING){
                        $start_time = $v['ctime'];
                    }else{
                        $start_time = time();
                    }

                    $show = service("Financing/FastCash")->showTimeLimitSdt($start_time - 24 * 3600, $v['last_repay_date']);
                } catch (Exception $e) {
                    $show = "";
                }
                $time_show = array(
                    $show,
                    //过滤掉HTML
                    strip_tags($show)
                );
                $data[$k]['time_show'] = $time_show;
                $viewUrl = U('Financing/Invest/fastCash', array('id' => $idView));
            } else {
                $data[$k]['time_show'] = service("Index/Index")->showTimeLimit($data[$k], $v['transfer_id']);
            }
            $data[$k]['prj_type_name'] = $this->getPrjTypeName($v['prj_type']);
            if ($uid) {
                $data[$k]['url'] = $viewUrl;
            } else {
                $refUrl = rawurlencode($viewUrl);
                $data[$k]['url'] = U('Account/User/login', array("url" => $refUrl));
            }
            $data[$k]['bid_diff_time'] = max(0, $v['start_bid_time'] - time());
            $data[$k]['bid_status_view'] = $this->getBidStatus($v['bid_status'],$v['id']);

            $data[$k]['btn_status'] = $this->getBtnStatus($v['bid_status'], $v);

            $data[$k]['client_type'] = $this->getClientType($v['id']);
            $data[$k]['is_channel'] = !in_array($v['mi_no'], array('1234567889', '1234567890'));
            if ($data[$k]['client_type'] && $data[$k]['bid_status'] == 1 && !$data[$k]['is_channel']) {
                $data[$k]['bid_status_view'] = "即将开标&gt;&gt;";
            }
            $data[$k]['is_xzd'] = (String)$prjInfo['act_prj_ext']['is_xzd'];
            $data[$k]['is_deposit'] = (String)$prjInfo['act_prj_ext']['is_deposit'];
            $data[$k]['is_xlb'] = (String)$prjInfo['act_prj_ext']['is_xlb'];
        }
        return $data;
    }

    /**
     * 项目列表页默认排序
     * @return string
     */
    public static function getDefaultOrder()
    {
        return 'combine_order ASC';
    }

    //获取投资按钮状态
    function getBtnStatus($status){

        /*if (!empty($prj_info['matched_amount'])
            && $prj_info['bid_status'] == PrjModel::BSTATUS_BIDING
            && $prj_info['demand_amount'] = $prj_info['remaining_amount'] + $prj_info['matched_amount']) {
            return 3;
        }*/

        if($status == PrjModel::BSTATUS_WATING ){
            return 1;//待开标状态
        }elseif($status == PrjModel::BSTATUS_BIDING){
            return 2;//投资中状态
        }elseif($status != PrjModel::BSTATUS_WATING && $status != PrjModel::BSTATUS_BIDING){
            return 3;//灰掉的状态
        }

    }
    //获取融资金额和单位
    function getDemandAmount($number = 0, $isFed = 0){
        $number += 0;
        $number /= 100;
        $decimal = 2;

        if ($isFed){
            return array('num'=>number_format($number, $decimal, '.', ','),'unit'=>'元');
        }
        if ($number >= 100000000) {
            $number = round($number / 100000000, $decimal);
            $unit = '亿';
        } elseif ($number >= 10000) {
            $decimal = max(2, (int)strpos(strrev($number/10000), '.')) ; // 万小数点位数
            $decimal = min(4, $decimal); //最多四位小数
            $number = round($number / 10000, $decimal);
            $unit = '万';
        } else {
            $unit = '元';
        }
        return array('num' => number_format($number, $decimal, '.', ','), 'unit' => $unit);
    }

    function doBidOpen()
    {
        import_app('Financing.Model.PrjModel');
        $claimsPrjStatusApi = new App\Modules\JavaApi\Service\ClaimsPrjStatusApiService();
        $where['bid_status'] = PrjModel::BSTATUS_WATING;
        $where['status'] = PrjModel::STATUS_PASS;
        $where['start_bid_time'] = array('ELT', time());

        $list = M('prj')->where($where)->select();
        foreach ($list as $prj) {
            $prj_data = array();
            $prj_ext = M('prj_ext')->where(array('prj_id' => $prj['id']))->field('is_pre_sale,is_pre_sale_deal')->find();
            if ($prj_ext['is_pre_sale'] == 1 && $prj_ext['is_pre_sale_deal'] != 2) {
                continue;
            }

            $prj_data['id'] = $prj['id'];
            if (!$prj['remaining_amount']) {
                $prj_data['bid_status'] = PrjModel::BSTATUS_FULL;
            } else {
                $prj_data['bid_status'] = PrjModel::BSTATUS_BIDING;
            }

            //司马小鑫相关 (预售的标不参与匹配 不然会出问题)
            if ($prj['prj_type'] != PrjModel::PRJ_TYPE_J) {
                $matched_amount = D('Financing/XPrjMatching')->getMatchedTotalAmountOfPrj($prj['id']);
            } else {
                $matched_amount = 0;
            }

            if ($matched_amount > 0) {
                $prj_data['matched_amount'] = $matched_amount;
                $remind_amount = $prj_data['remaining_amount'] = $prj['remaining_amount'] - $matched_amount;
            } else {
                $remind_amount = $prj['remaining_amount'];
            }

            $remaining_cachekey = 'buyremain_' . $prj['id'];
            $remaining_cache = cache($remaining_cachekey, (1 + $remind_amount),
                array('expire' => 24 * 3600 * 30));
            if (!$remaining_cache) {
                cache($remaining_cachekey, (1 + $remind_amount), array('expire' => 24 * 3600 * 30));
            }

            $prj_data['mtime'] = time();
            M('prj')->save($prj_data);
            
            //开标时调用java接口修改项目提报状态，若为子标则传母标id
            $prjid = $prj['spid'] ? $prj['spid'] : $prj['id'];
            $claimsPrjStatusApi->doClaimsPrjStatus($prjid);

            service('Financing/Appoint')->appoint_project((int)$prj['id'], $prj['prj_type']);

            //司马小鑫启动匹配
            if ($matched_amount > 0) {
                service('Financing/XProject')->buyMatchRecord($prj['id']);
            }

            if ($remind_amount && !in_array($prj['prj_type'], [PrjModel::PRJ_TYPE_J, PrjModel::PRJ_TYPE_H])) {
                service('Financing/XProjectMatch')->projectMatchXOrder($prj['id']);
            }

        }
        service('Financing/PreSale')->beforeBidOpenApply();


//     	M("prj")->where($where)->save(array("bid_status"=>PrjModel::BSTATUS_BIDING,"mtime"=>time()));
    }

    /**
     *
     * 获取速兑通剩余时间
     * @param $transferId
     * @param int $prjId
     * @param int $time
     */
    function parseNewExpireTime($transferId, $prjId = 0, $time = 0){
        $expire = service("Financing/Transfer")->getTimeLimit($transferId, $prjId, $time);
        $num = "";
        $unit = "";
        $title = "";
        if ($expire) {
            $month = $expire[0];
            $day = $expire[1];

            if ($month > 0 && $day == 0) {
                $num = $month;
                $unit = "个月";
                $title = $month . "个月";
            }
            if ($month == 0 && $day > 0) {
                $num = $day;
                $unit = "天";
                $title = $day . "天";
            }
            if ($month > 0 && $day > 0) {
                $num = $month;
                $unit = "个月+";
                $title = $month . "个月零" .$day . "天";
            }
        } else {
            $title = "-";
        }
        return array($num, $unit, $title);
    }


    function parseExpireTime($transferId, $prjId = 0, $time = 0)
    {
        $expire = service("Financing/Transfer")->getTimeLimit($transferId, $prjId, $time);
        $str = "";
        $str2 = "";
        $return = "";
        if ($expire) {
            $month = $expire[0];
            $day = $expire[1];

            if ($month > 0 && $day == 0) {
                $str2 = $month . "个月";
                $str = $month . "个月";
                $return = $str2;
            }
            if ($month == 0 && $day > 0) {
                $str = $day . "天";
                $str2 = $day . "天";
                $return = $str2;
            }
            if ($month > 0 && $day > 0) {
                $str = $month . "个月<i class='dateplus'>+</i>";
                $str2 = $month . "个月零" . $day . "天";
                $return = "<span class=\"blue\" title=\"" . $str2 . "\">$str</span>";
            }
        } else {
            $return = "-";
        }
        return array($str, $str2, $return);
    }


    //推荐项目
    function recommend($where, $pageNumber = 1, $pageSize = 10)
    {
        $modeltmp = M("prj");
        if ($pageNumber && $pageSize) $model = $modeltmp->page($pageNumber . "," . $pageSize);
        $model = $model->field("*,(1-round(remaining_amount/demand_amount,2)) as invest_schedule");
        $model = $model->order(" invest_schedule DESC");
        if ($where) $model = $model->where($where);
        $data = $model->select();
//     	echo $modeltmp->getLastSql();exit;
        if ($data) {
            $srvFinancing = service('Financing/Financing');
            foreach ($data as $k => $v) {
                $data[$k]['id_view'] = $this->getIdView($v['id']);
                $data[$k]['time_limit_unit_view'] = $this->getTimeLimitUnit($v['time_limit_unit']);
                $data[$k]['rate_type_view'] = $this->getRateType($v['rate_type']);
                $data[$k]['rate_symbol'] = $v['rate_type'] == PrjModel::RATE_TYPE_DAY ? '‰' : '%';
                $data[$k]['repay_way_view'] = $this->getRepayWay($v['repay_way']);
                $data[$k]['rate'] = number_format($v['rate'], 2);
                $data[$k]['start_bid_time_view'] = date('Y-m-d', $v['start_bid_time']) . "<br>" . date('H:i:s', $v['start_bid_time']);
                $data[$k]['min_bid_amount_view'] = humanMoney($v['min_bid_amount'], 2, false) . "元";
                $data[$k]['min_bid_amount_name'] = humanMoney($v['min_bid_amount'], 2, false);

                $data[$k]['max_bid_amount_view'] = humanMoney($v['max_bid_amount'], 2, false) . "元";
                $data[$k]['max_bid_amount_name'] = humanMoney($v['max_bid_amount'], 2, false);
                $data[$k]['step_bid_amount_view'] = humanMoney($v['step_bid_amount'], 2, false) . "元";
                $data[$k]['demand_amount_view'] = humanMoney($v['demand_amount'], 2);
                $data[$k]['schedule'] = $this->getSchedule($v['demand_amount'], $v['remaining_amount']);
                $data[$k]['guarantor'] = $this->getGuarantor($v['guarantor_id']);
                $data[$k]['real_money'] = humanMoney(($v['demand_amount'] - $v['remaining_amount']));
                $data[$k]['bid_status_view'] = $this->getBidStatus($v['bid_status'],$v['id']);
                $data[$k]['prj_type_name'] = $this->getPrjTypeName($v['prj_type']);
                $data[$k]['safeguards_view'] = $v['safeguards'] == 1 ? "保" : "本";
                // 利率统一转换成千分
                if ($data[$k]['rate_type'] != PrjModel::RATE_TYPE_DAY) {
                    $data[$k]['rate_view'] = number_format($v['rate'] / 10, 2);
                } else {
                    $data[$k]['rate_view'] = number_format($v['rate'], 2);
                }

                if ($v['transfer_id'] > 0) {
                    $data[$k]['min_bid_amount_view'] = $data[$k]['max_bid_amount_view'] = humanMoney($v['demand_amount'], 2, false) . "元";
                    $data[$k]['min_bid_amount_name'] = $data[$k]['max_bid_amount_name'] = humanMoney($v['demand_amount'], 2, false);
                    $info = $this->getPrjInfo($v['p_prj_id']);
                } else {
                    $info = $this->getPrjInfo($v['id']);
                }
                $data[$k]['is_transfer'] = (int)($info['ext']['is_transfer'] ? $info['ext']['is_transfer'] : '0');
                $data[$k]['is_hot'] = (int)($info['ext']['is_hot'] ? $info['ext']['is_hot'] : '0');
                $data[$k]['is_limit_amount'] = $srvFinancing->isPrjLimitAmount($v['id'], $v);
                $data[$k]['activity_id'] = $v['activity_id'];
            }
        }
        return $data;
    }

    /**
     * 获取产品系列名称
     */
    function getPrjSeriesName($prjSeries)
    {
        switch ($prjSeries) {
            case 1 :
                $result = "高富帅";
                break;
            case 2 :
                $result = "白富美";
                break;
            case 3 :
                $result = "富二代";
                break;
            default:
                $result = "高富帅";
                break;
        }
        return $result;
    }


    //去掉稳益保
    function notShowTypeC($priType)
    {
        import_app("Financing.Model.PrjModel");
        //去掉稳益保
        if (!isset($priType)) {
            return array("NEQ", PrjModel::PRJ_TYPE_C);
        } elseif ($priType == PrjModel::PRJ_TYPE_C) {
            return "NONE";
        }
        return array();
    }

    /**
     * 获取查看id
     * @param unknown $id
     * @return string
     */
    function getIdView($id)
    {
        return $id;
//    	return think_encrypt($id.chr(0).'1399457117');
    }

    /**
     * 解析id
     * @param unknown $idView
     */
    function parseIdView($idView)
    {
        $idView = trim($idView);
        if (is_numeric($idView)) return $idView;
        $idArr = explode(chr(0), think_decrypt($idView));
        $id = (int)$idArr[0];
        return $id;
    }

    /**
     * 获取投资意向
     * @return Ambigous <Model, Model>
     */
    function getTenderIntention($prjId, $uid)
    {
        $prjId = (int)$prjId;
        $uid = (int)$uid;
        $info = M("tender_intention")->where(array("prj_id" => $prjId, "uid" => $uid))->find();
        return $info;
    }

    /**
     * 获取 proj表数据
     * @param unknown $id
     * @return mixed
     */
    function getDataById($id,$is_mi_no=false)
    {
        $id = (int)$id;
        $model = M("prj");
        $api_info = C('API_INFO');
        $where = array(
            "id" => $id,
//             'prj_class'=>PrjModel::PRJ_CLASS_TZ,
        );
        if($is_mi_no){
            $where['mi_no'] = array(array('eq',self::$api_key),array('eq',$api_info['api_key_all']),'or');
        }
        $info = $model->where($where)->find();
        $info['is_creditor_transfered'] = $this->mdCreditorTransfer->isPrjTransfered($id); // 债权已转让
        return $info;
    }

    /**
     * 更新bid_status 状态
     * @param unknown $prjId
     */
    function updatePrjBidStatus($prjId,$status, $pre_check=FALSE){
        if($pre_check && $status == M('prj')->where(array('id' => $prjId))->getField('bid_status')) return TRUE;
        M("prj")->where(array("id"=>$prjId))->save(array("bid_status"=>$status,"mtime"=>time()));
        //状态对应
//        $applyStatus = D("Zhr/FinanceApply")->prjStatus2Apply($status);
//        if(!$applyStatus) return true;
//        M("finance_apply")->where(array("prj_id"=>$prjId))->save(array("status"=>$applyStatus,"mtime"=>time()));
        return true;
    }


    function getPrjSeriesGfs()
    {
        import_app("Financing.Model.PrjModel");
        return PrjModel::PRJ_SERIES_GFS;
    }


    function getPrjSeriesBfm()
    {
        import_app("Financing.Model.PrjModel");
        return PrjModel::PRJ_SERIES_BFM;
    }

    function getPrjSeriesFed()
    {
        import_app("Financing.Model.PrjModel");
        return PrjModel::PRJ_SERIES_FED;
    }

    /**
     * 获取需要实时抓取的数据
     * @param unknown $id $info项目信息
     */
    function getRealTimeData($info)
    {
        $prj_ext = M("prj_ext")->field("is_pre_sale,demand_amount_pre")->where("prj_id=".$info['id'])->find();
        if($prj_ext['is_pre_sale'] == 0 || $info['bid_status'] == PrjModel::BSTATUS_BIDING) {
            //待开标状态 剩余可投资金额=融资规模
            $info['remaining_amount_name'] = $info['bid_status'] == PrjModel::BSTATUS_WATING ? humanMoney($info['demand_amount'], 2, false) . "元" : humanMoney($info['remaining_amount'], 2, false) . "元";
            $info['remaining_amount_name2'] = $info['bid_status'] == PrjModel::BSTATUS_WATING ? humanMoney($info['demand_amount'], 2, false) : humanMoney($info['remaining_amount'], 2, false);
            //待开标状态融资进度默认为0
            $info['schedule'] = $info['bid_status'] == PrjModel::BSTATUS_WATING ? 0 : $this->getSchedule($info['demand_amount'], $info['remaining_amount']);
            $info['bid_status_view'] = $this->getBidStatus($info['bid_status'], $info['id']);
        }elseif($prj_ext['is_pre_sale']==1 && $info['bid_status'] == PrjModel::BSTATUS_WATING){
            $info['remaining_amount_name2'] = humanMoney($info['demand_amount']-$prj_ext['demand_amount_pre'],2,false);
            $info['remaining_amount_name'] = $info['remaining_amount_name2'].'元';

            $info['schedule'] = $this->getSchedule($info['demand_amount'],$info['demand_amount']-$prj_ext['demand_amount_pre']);
            $info['bid_status_view'] = $info['demand_amount'] == $prj_ext['demand_amount_pre'] ? "预约已满" : "预约投资";
        }
        $info['remaining_amount_view'] = "<strong>" . humanMoney($info['remaining_amount'], 2, false, ' ', false) . "</strong>元";
        $info['surplus_bid_time_view'] = $this->formatTime(($info['end_bid_time'] - time()));
        $info['format_start_bid_time_view'] = $info['start_bid_time'] > time() ? $this->formatTime(($info['start_bid_time'] - time())) : 0;
        return $info;
    }

    /**
     * 通过id获取
     * @param unknown $id
     */
    function getById($prjId, $prj_info = null,$is_mi_no=false)
    {
//         $prjId = (int) $prjId;
//         $cache_key = "if_getById_".$prjId;
//         $result_info = cache($cache_key);
//         if($result_info) return $result_info;
        if ($prj_info) $info = $prj_info;
        else $info = $this->getPrjInfo($prjId,$is_mi_no);
        if (!$info) {
            MyError::add("项目数据不存在!");
            return false;
        }
        $info['id_view'] = $this->getIdView($info['id']);
        $info['repay_way_name'] = $this->getRepayWay($info['repay_way']);
        $info['rate'] = number_format($info['rate'], 2);

        $info['total_year_rate'] = number_format($info['total_year_rate'], 2);

        $info['min_bid_amount_name'] = humanMoney($info['min_bid_amount'], 2, false) . "元";
        $info['max_bid_amount_name'] = $info['max_bid_amount'] > 0 ? humanMoney($info['max_bid_amount'], 2, false) . "元" : "0";
        //待开标状态 剩余可投资金额=融资规模
        $info['remaining_amount_name'] = $info['bid_status'] == PrjModel::BSTATUS_WATING ? humanMoney($info['demand_amount'], 2, false) . "元" : humanMoney($info['remaining_amount'], 2, false) . "元";
        $info['remaining_amount_name2'] = $info['bid_status'] == PrjModel::BSTATUS_WATING ? humanMoney($info['demand_amount'], 2, false) : humanMoney($info['remaining_amount'], 2, false);
        $info['remaining_amount_view'] = "<strong>" . humanMoney($info['remaining_amount'], 2, false, ' ', false) . "</strong>元";
        $info['demand_amount_name'] = $info['prj_type'] == PrjModel::PRJ_TYPE_H ? humanMoney($info['demand_amount'], 2, false).'元' : humanMoney($info['demand_amount'], 2);
        $info['step_bid_amount_name'] = humanMoney($info['step_bid_amount'], 2, false) . "元";
        $info['step_bid_amount_view'] = $info['step_bid_amount_name'];
        $info['safeguards_view'] = $info['safeguards'] == 1 ? "保" : "本";
        //保障措施
        $addcreditIds = $info['addcredit_ids'];
        if($addcreditIds){
            $addcredit = $this->getAddCredit($addcreditIds);

            $addcreditTmp = array();

            if(!isset($addcredit['way'])){
                foreach($addcredit as $v){
                    $addcreditTmp['way'] .= $v['way'].",";
                }
                $addcreditTmp['way'] = trim($addcreditTmp['way'],",");
            }else{
                $addcreditTmp['way'] =$addcredit['way'];
            }
        }else{
            $addcreditTmp = "";
        }
        $info['addcredit'] = $addcreditTmp;
        //如果是速兑通项目就不设置担保人和担保公司
        if($info['prj_type'] != 'H'){
            if($info['prj_business_type'] == prjModel::PRJ_BUSINESS_TYPE_G){//央企融类型的担保人固定显示
                $info['guarantor']['title'] = '接受央企商票质押的第三方公司';
            }else{
                //担保人
                $guarantorId = $info['guarantor_id'];
                $guarantor = $this->getGuarantor($guarantorId);
                $info['guarantor'] = $guarantor;

                // 二三级担保公司
                $guarantors = D('Financing/Prj')->getGuarantorExt($prjId);
                if(is_array($guarantors)) $info += $guarantors;
            }
        }

        if ($info['prj_business_type'] == prjModel::PRJ_BUSINESS_TYPE_G) {
            //增加央企融保障 固定
            $info['safeguards_name'] = $info['safeguards_name2'] = "央企票据质押";
        } else {
            $info['safeguards_name2'] = $info['safeguards_name'] = $this->getSafeguards($info['safeguards']);;
        }

        $info['time_limit_unit_name'] = $this->getTimeLimitUnit($info['time_limit_unit']);
        $info['time_limit_unit_view'] = $info['time_limit_unit_name'] == "月" ? "个月" : $info['time_limit_unit_name'];
        $info['prj_type_name'] = $this->getPrjType($info['prj_type']);
        $info['rate_type_name'] = $this->getRateType($info['rate_type']);
        $info['rate_type_view'] = $info['rate_type_name'];
        $info['rate_symbol'] = $info['rate_type'] == 'day' ? '‰' : '%';
        $info['repay_way_name'] = $this->_getCodeItem('E004', $info['repay_way']);

        //待开标状态融资进度默认为0
        $info['schedule'] = $info['bid_status'] == PrjModel::BSTATUS_WATING ? 0 : $this->getSchedule($info['demand_amount'], $info['remaining_amount']);
        //预售
        if($info['act_prj_ext']['is_pre_sale'] == 1) {
            $info['pre_schedule'] = $this->getSchedule($info['demand_amount'], $info['demand_amount'] - $info['act_prj_ext']['demand_amount_pre']);
            $info['pre_remaind_amount'] = humanMoney($info['demand_amount'] - $info['act_prj_ext']['demand_amount_pre'], 2, false).'元';
        }

        $info['start_bid_time_view'] = date('Y-m-d H:i:s', $info['start_bid_time']);
        $info['end_bid_time_view'] = date('Y-m-d H:i:s', $info['end_bid_time']);
        $info['surplus_bid_time_view'] = $this->formatTime(($info['end_bid_time'] - time()));
        $info['format_start_bid_time_view'] = $info['start_bid_time'] > time() ? $this->formatTime(($info['start_bid_time'] - time())) : 0;
        $info['bid_status_view'] = $this->getBidStatus($info['bid_status'],$info['id']);
        $info['prj_type_name'] = $this->getPrjTypeName($info['prj_type']);
        $info['prj_business_type_name'] = $this->getPrjBusinessType($info['prj_business_type']);
        $last_repay_date = $info['last_repay_date'];
        $next_repay_date = $info['next_repay_date'] ? $info['next_repay_date'] : $info['last_repay_date'];
        if (!$next_repay_date) {
            $next_repay_date = $this->getPrjFirstRepayDate($prjId, NULL, FALSE);
        }
        $info['next_repay_date_view'] = date('Y-m-d', $next_repay_date);
        $info['last_repay_date_view'] = date('Y年m月d日', $last_repay_date);
        $info['actual_repay_time_view'] = date('Y年m月d日', $info['actual_repay_time']);
        $info['is_xinchun'] = $info['act_prj_ext']['is_xinchun'];
        //利率计算值
//         print_r($info);
        // 利率统一转换成千分
        if ($info['rate_type'] != PrjModel::RATE_TYPE_DAY) {
            $info['rate_view'] = number_format($info['rate'] / 10, 2);
            $info['total_year_rate_view'] = number_format($info['total_year_rate'] / 10, 2);
        } else {
            $info['rate_view'] = number_format($info['rate'], 2);
            $info['total_year_rate_view'] = number_format($info['total_year_rate'], 2);
        }
        cache('prj_info'.$prjId, $info, array('expire' => 600));
        return $info;
    }

    /**
     * 收益计算
     * @param unknown $unitNumber
     * @param unknown $rate_type
     * @param unknown $rate
     * @param unknown $startDate
     * @param unknown $endDate
     * @param unknown $money
     * @return number
     */
    function profitComputeByDate($rate_type, $rate, $startDate, $endDate, $money, $precision=0)
    {
        if($rate <= 0 || $startDate > $endDate) {
            return 0;
        }
        $startTime = is_numeric($startDate) ? $startDate : strtotime($startDate);
        $endTime = is_numeric($endDate) ? $endDate : strtotime($endDate);

        $result = 0;
        $bc = 0;
        $rate = bcdiv($rate, 1000, 12);
        if($rate_type == 'day') {
            $day = day_diff($startTime, $endTime);
            if($day > 0) {
                $result = $rate * $day * $money;
                $bc = bcmul(bcmul($rate, $day, 12), $money, 12);
            }
        } elseif($rate_type == 'month') {
            list($month, $day) = month_diff($startTime, $endTime);
            if($month > 0) {
                $result += $rate * $month * $money;
                $bc = bcadd($bc, bcmul(bcmul($rate, $month, 12), $money, 12));
            }
            if($day > 0) {
                $result += ($rate * 12 / 365) * $day * $money;
                $bc = bcadd($bc, bcmul(bcmul(bcmul($rate, bcdiv(12, 365, 12), 12), $day, 12), $money,12), 12);
            }
        } elseif($rate_type == 'year') {
            list($year, $month, $day) = year_diff($startTime, $endTime);
            if($year > 0) {
                $result += $rate * $year * $money;
                $bc = bcadd($bc, bcmul(bcmul($rate, $year, 12), $money, 12), 12);
                $endTimeTmp = strtotime("+" . $year . ' years', $startTime);
                $day = day_diff($endTimeTmp, $endTime);
            } elseif($month > 0) {
                $day = day_diff($startTime, $endTime);
            }
            if($day > 0) {
                $result += ($rate / 365) * $day * $money;
                $bc = bcadd($bc, bcmul(bcmul(bcdiv($rate, 365, 12), $day, 12), $money, 12), 12);
            }
        }

        if($startTime <= strtotime('2014-03-13')) {
            return round($result);
        }

        return intval($bc * pow(10, $precision)) / pow(10, $precision)  ;
//        return floor($result);
    }


    /**
     * 修正日益升的起息日
     *
     * @param int|string $value_time        起息时间
     * @param string $prj_type
     * @param int $value_date               T+n
     * @param int|string $start_bid_time
     * @param int|string $end_bid_time
     * @return bool|int|string
     */
    public function fixPrjValueTime($value_time, $prj_type, $value_date, $start_bid_time, $end_bid_time) {
        if(/*$prj_type != 'A' || */$value_date > 0) {
            return $value_time;
        }

        $is_formate = !is_numeric($value_time);
        if(is_numeric($value_time)) {
            $value_time = strtotime(date('Y-m-d', $value_time));
        }
        if(is_numeric($start_bid_time)) {
            $start_bid_time = strtotime(date('Y-m-d', $start_bid_time));
        }
        if(is_numeric($end_bid_time)) {
            $end_bid_time = strtotime(date('Y-m-d', $end_bid_time));
        }
        if($value_time == $start_bid_time && $start_bid_time == $end_bid_time) {
            $value_time += 86400;
        }

        return $is_formate ? date('Y-m-d H:i:s', $value_time) : $value_time;
    }

    /**
     * 通过项目id估算收益，，
     * 计息时间=购买时间+计息日
     * @param unknown $prjId
     * @param unknown $money
     *
     */
    function getIncomeById($prjId, $money, $time = 0, $endTime=0)
    {
        $prjId = (int)$prjId;
        $info = $this->getPrjInfo($prjId);
        if (!$info) {
            MyError::add("项目数据不存在!");
            return false;
        }
        $ext = $info['ext'];
        //计息时间
        $cTime = $time ? $time : time();
        $cTime = $this->fixPrjValueTime($cTime, $info['prj_type'], $info['ext']['value_date'], $info['start_bid_time'], $info['end_bid_time']);

        $valueDate = $ext['value_date'];
        $timeLimit = $info['time_limit'];
        $timeLimitUnit = $info['time_limit_unit'];
        $rateType = $info['rate_type'];
        $rate = $info['rate'];
        $endBidTime = $info['end_bid_time'];
//         echo date('Y-m-d',$cTime)."|".date('Y-m-d',$endBidTime);
    	  return $this->getIncome($cTime,$endBidTime,$valueDate,$timeLimit,$timeLimitUnit,$rateType,$rate,$money,$prjId,$endTime);
    }

    /**
     * 根据时间计算利息
     * @param unknown $prjId
     * @param unknown $money
     * @param unknown $startTime
     * @param unknown $endTime
     * @return boolean
     */
    function getIncomeByTime($prjId,$money,$startTime,$endTime, $prj_info=null){
        $startTimeTmp = $startTime;
        $endTimeTmp = $endTime;
        $startTime = strtotime(date('Y-m-d',$startTime));
        $endTime = strtotime(date('Y-m-d',$endTime));

        $prjId = (int) $prjId;
        if($prj_info) $info = $prj_info;
        else $info = $this->getPrjInfo($prjId);

        if(!$info){
            MyError::add("项目数据不存在!");
            return false;
        }
        $startTime = $this->fixPrjValueTime($startTime, $info['prj_type'], $info['ext']['value_date'], $info['start_bid_time'], $info['end_bid_time']);

        $ext = $info['ext'];
        $valueDate = $ext['value_date'];
        $timeLimit = $info['time_limit'];
        $timeLimitUnit = $info['time_limit_unit'];
        $rateType = $info['rate_type'];
        $rate = $info['rate'];

        $balanceTime = $this->getBalanceTime($startTimeTmp,$info['id']);
        if($balanceTime < $startTimeTmp) $valueDate +=1;

        $startTime= strtotime("+".$valueDate." days ",$startTime);

        $endValue = 1;
        $endbalanceTime = $this->getBalanceTime($endTimeTmp,$info['id']);
        if($endbalanceTime < $endTimeTmp)  $endValue +=1;
        $endTime = strtotime("+".$endValue." days ",$endTime);
//     	echo date('Y-m-d',$startTime)."|".date('Y-m-d',$endTime)."<br>";
        return $this->profitComputeByDate($rateType,$rate,$startTime,$endTime,$money);
    }

    /**
     * 获取平衡时间
     * @param unknown $todate
     */
    function getBalanceTime($todate,$prjId){
    	if(!$todate) return 0;
        $prjId = intval($prjId);
        if(1 || $prjId >C('LAST_16_PRJ_ID')){ // 现在都是走这个逻辑
            //新项目取消16点
            $todate = is_numeric($todate) ? date('Y-m-d',$todate) : $todate;
            return strtotime('+1 days',strtotime($todate));
        }else{
            $todate = is_numeric($todate) ? date('Y-m-d',$todate) : $todate;
            $newDate = C('PERIODS_TIME')? $todate." ".C('PERIODS_TIME'):$todate;
            return strtotime($newDate);
        }
    }

    /**
     * 获取收益
     * @param unknown $cTime 计算收益开始时间
     * @param unknown $valueDate 起息日
     * @param unknown $timeLimit
     * @param unknown $timeLimitUnit
     * @param unknown $rateType
     * @param unknown $rate
     * @param unknown $money
     * @return number
     */
    function getIncome($cTime,$endBidTime,$valueDate,$timeLimit,$timeLimitUnit,$rateType,$rate,$money,$prjId,$enddoTime=0){
    	$balanceTime = $this->getBalanceTime($cTime,$prjId);
    	$endbalanceTime = $this->getBalanceTime($endBidTime,$prjId);

        if ($balanceTime < $cTime) $valueDate += 1;
        //去杂，，
        $cDate = date('Y-m-d', $cTime);
        $endBidDate = date('Y-m-d', $endBidTime);
        $startTime = strtotime("+" . $valueDate . " days ", strtotime($cDate));
        $endBidTime = strtotime($endBidDate);

        if($enddoTime){
        	$endTime = $enddoTime;
        } else {
	        $endTime = $this->formatUnitTime($timeLimit, $timeLimitUnit, $endBidTime);
        }
        $endValue = 1;
        if ($endbalanceTime < $endBidTime) $endValue += 1;
        $endTime = strtotime("+" . $endValue . " days ", $endTime);
        if($valueDate > 1) $endTime = strtotime('+' . ($valueDate - 1) . 'days', $endTime);

        $income = $this->profitComputeByDate($rateType, $rate, $startTime, $endTime, $money);
        return $income;
    }

    function getPrjValueDate($prjId, $cTime)
    {
        $info = $this->getPrjInfo($prjId);
        $cTime = date('Y-m-d', $cTime);
        $valueDate = $info['ext']['value_date'] ? $info['ext']['value_date'] : 1;
        return strtotime("+" . $valueDate . " days ", strtotime($cTime));
    }

    /**
     * 获取起息日
     * @param unknown $cTime 下单时间
     * @param unknown $valueDate
     */
    function getValueTime($cTime,$valueDate,$prjId){
    	$balanceTime = $this->getBalanceTime($cTime,$prjId);
    	if($balanceTime < $cTime) $valueDate +=1;

        $cTime = date('Y-m-d', $cTime);
        return strtotime("+" . $valueDate . " days ", strtotime($cTime));
    }

    /**
     * 项目还款日(最后一次还款日)
     * @param unknown $prjId
     */
    function getPrjEndTime($prjId, $prj_info=null, $order_info=null){
      $prjId = (int) $prjId;
      if($prj_info) $info = $prj_info;
      else $info = $this->getPrjInfo($prjId);
        if(!$info['end_bid_time']) return 0;

        if($info['prj_class'] == PrjModel::PRJ_CLASS_LC){
        	if(is_null($order_info)){
        		$maxRate = service('Financing/InvestFund')->getPrjLastRepayDate($prjId);
//         		$maxRate = M('invest_fund_rate')->where(array('prj_id' => $prjId))->field('time_limit, time_limit_unit')->order('time_limit_day desc')->find();
        	}else{
        		$maxRate = $order_info;
        	}

        	$timeLimit = $maxRate['time_limit'];
        	$timeLimitUnit = $maxRate['time_limit_unit'];
        }else{
    	$timeLimit = $info['time_limit'];
    	$timeLimitUnit = $info['time_limit_unit'];
        }

    	$cTime = strtotime(date('Y-m-d',$info['end_bid_time']));
        if($info['value_date_shadow'] > 1) $cTime = strtotime('+' . ($info['value_date_shadow'] - 1) . 'days', $cTime);
    	$endTime = $this->formatUnitTime($timeLimit,$timeLimitUnit,$cTime);
    	return $endTime;
    }


    /**
     * 时间转换
     * @param unknown $timeLimit
     * @param unknown $timeLimitUnit
     * @param unknown $startDate
     * @return number
     */
    function formatUnitTime($timeLimit, $timeLimitUnit, $date)
    {
        $time = is_numeric($date) ? $date : strtotime($date);
        $timeLimit = intval($timeLimit);
        /* if ($timeLimitUnit == 'day') {
            return strtotime("+" . $timeLimit . " days", $time);
        } elseif ($timeLimitUnit == 'month') {
            return strtotimeX("+" . $timeLimit . " months", $time);
        } */
        if ($timeLimitUnit == 'month') {
        	return strtotimeX("+" . $timeLimit . " months", $time);
        } else {
        	return strtotime("+{$timeLimit} {$timeLimitUnit}", $time);
        }
        return 0;
    }

    /**
     * 投资意向
     * @param unknown $money
     * @param unknown $uid
     * @param unknown $prjId
     */
    function buyIntention($money, $uid, $prjId)
    {
        if (!is_numeric($money)) {
            MyError::add("投资金额必须是数字!");
            return false;
        }
        $prjId = (int)$prjId;
        $uid = (int)$uid;
        $model = M("prj");
        $info = $model->where(array("id" => $prjId))->field("prj_type,min_bid_amount,max_bid_amount,remaining_amount,status,bid_status")->find();
        if (!$info) {
            MyError::add("异常,项目不存在!");
            return false;
        }

        if (!$info['remaining_amount']) {
            MyError::add("没有可投资剩余金额");
            return false;
        }

        if ($money > $info['remaining_amount']) {
            $diff = number_format($info['remaining_amount'] / 100);
            MyError::add("投资金额不能大于剩余可投资金额" . $diff . "元");
            return false;
        }

        if ($money < $info['min_bid_amount']) {
            $diff = number_format($info['min_bid_amount'] / 100);
            MyError::add("投资金额必须大于等于投资起始金额" . $diff . "元");
            return false;
        }

        //检查状态
        if ($info['status'] != PrjModel::STATUS_PASS) {
            MyError::add("该项目未通过审核，不能进行此操作");
            return false;
        }

        if ($info['bid_status'] != PrjModel::BSTATUS_WATING) {
            MyError::add("该项目不是待开标状态，不能添加投资意向");
            return false;
        }

        //检查是否添加过
        $db = M("tender_intention");
        if ($db->where(array("prj_id" => $prjId, "uid" => $uid))->find()) {
            MyError::add("你已经添加过该项目的投资意向，不能重复添加");
            return false;
        }

        //保存
        $data['prj_id'] = $prjId;
        $data['prj_type'] = $info['prj_type'];
        $data['uid'] = $uid;
        $data['money'] = $money;
        $data['ctime'] = time();
        $data['mtime'] = time();
        $id = $db->add($data);

        if (!$id) {
            MyError::add("数据添加失败!");
            return false;
        }
        $obj = M("prj");
        $obj->where(array("id" => $prjId))->setInc("intent_user_count", 1);
        // echo $obj->getLastSql();
        M("prj")->where(array("id" => $prjId))->setInc("intent_money_count", $money);
        return $id;
    }

    /**
     * 认购数量
     * @param unknown $prjId
     * @return number
     */
    function buyIntentionCount($prjId)
    {
        $prjId = (int)$prjId;
        $db = M("tender_intention");
        $info = $db->where(array("prj_id" => $prjId))->field("count(*) AS cnt")->find();
        return (int)$info['cnt'];
    }

    /**
     * 获取投资记录
     * @param unknown $where
     * @param string $orderBy
     * @param number $pageNumber
     * @param number $pageSize
     * @param string $groupBy
     */
    function getBuyLog($where = array(), $orderBy = "", $pageNumber = 1, $pageSize = 15, $groupBy = '')
    {
        $model = M("prj_order");
        $model = $model->page($pageNumber . "," . $pageSize);
        if ($orderBy) $model = $model->order($orderBy);
        if ($groupBy) $model = $model->group($groupBy);
        if ($where) $model = $model->where($where);
        $model = $model->Table("fi_prj_order prj_order");
        $model = $model->field("prj_order.*,user.uname,user.real_name,user.mobile");
        $model = $model->join(" LEFT JOIN fi_user user ON user.uid=prj_order.uid");

        $data = $model->select();

        if ($data) {
            //组装是否有预约的
            $order_list_ids = array_column($data, 'id');
            $appoint_list_order = D("Financing/AppointOrder")->getAppointListOrderByOrderIds($order_list_ids);
            $rows  = M('prj_order_pre')->where(array('status' => 2,'order_id' => array('IN', $order_list_ids)))->field('order_id,ctime')->select();
            $pre_order_list = array();
            foreach ($rows as $row) {
                $pre_order_list[$row['order_id']] = $row;
            }
            foreach ($data as $k => $v) {
                if(array_key_exists($v['id'], $pre_order_list)) {
                    $data[$k]['ctime_view'] = date('Y-m-d H:i:s', $pre_order_list[$v['id']]['ctime']);
                } else {
                    $data[$k]['ctime_view'] = date('Y-m-d H:i:s', $v['ctime']);
                }
                $data[$k]['money_view'] = humanMoney($v['money'], 2, false);
                if (!$v['uname']) {
                    $data[$k]['uname'] = $v['show_uname'];
                }
                if (!$data[$k]['uname']) {
                    $data[$k]['uname'] = $v['real_name'];
                }
                $data[$k]['is_appoint'] = array_key_exists($v['id'], $appoint_list_order) ? 1 : 0;
                $data[$k]['is_pre_sale'] = array_key_exists($v['id'], $pre_order_list) ? 1 : 0;
                $device_type = $data[$k]['from_client'];
                $device_arr = array(2,3,4);
                if(in_array($device_type,$device_arr)){    //手机端显示
                    $data[$k]['device_type'] = 1;
                }else{
                    $data[$k]['device_type'] = 0;
                }

                //区分客户端
                $data[$k]['show_type'] = self::getOrderShowType($v);
            }
        }
        $output['data'] = $data;
//             	echo $model->getLastSql();
        $modelCount = M("prj_order");
        $modelCount = $modelCount->Table("fi_prj_order prj_order");
        $modelCount = $modelCount->join(" fi_user user ON user.uid=prj_order.uid");
        if ($where) $modelCount = $modelCount->where($where);
        $result = $modelCount->field("COUNT(*) AS CNT")->find(); // TODO: InnoDB的COUN(*)问题
        $totalRow = (int)$result['CNT'];

        $output['total_row'] = $totalRow;
        return $output;
    }

    /**
     * 订单的投标来源
     * @param array $order
     * @return string
     */
    static private function getOrderShowType(array $order)
    {
        if ($order['xprj_order_id']) {
            $show_type = 'xjh';
        } elseif (in_array($order['from_client'], [2, 3, 4])) {
            $show_type = 'app';
        } elseif ($order['from_client'] == 7) {
            $show_type = 'wei_xin';
        } elseif ($order['pre_sale_id']) {
            $show_type = 'pre_sale';
        } else {
            $is_appoint = (int) M('appoint_order')->where(['prj_order_id' => $order['id']])->getField('id');
            if ($is_appoint) {
                $show_type = 'auto_invest';
            } else {
                $show_type = '';
            }
        }

        return $show_type;
    }

    /**
     * 判断是否满足补足情况(目前只适用于基金)
     *
     * 补足条件，
     * 1、余额大于最低投资金额
     * 2、用户之前购买过该基金
     *
     */
    function checkMakeup($uid, $prjId)
    {
        $uid = (int)$uid;
        $prjId = (int)$prjId;
        $pmodel = M("prj");
        $info = $pmodel->where(array("id" => $prjId))->find();
        if (!$info) return true;
        if ($info['remaining_amount'] < $info['min_bid_amount']) {
            $orderModel = M("prj_order");
            $order = $orderModel->where(array("prj_id" => $prjId, "uid" => $uid))->find();
            return $order ? true : false;
        } else {
            return false;
        }
    }

    const BUY_CACHE_KEY = "FINANCING_PROJECT_BUY";

    /**
     * 修复投标项目剩余额度的cache值
     * @param string $prjId
     * @param int $money 投标金额
     * @param int $errType 1-cache失效 2-cache被击穿了
     */
    private function fixCache($prjId, $money, $errType){
    	$remaining_cachekey = "buyremain_".$prjId;//投标是剩余投标金额存在cache里的key
    	$doing_cachekey = "buydoing_con".$prjId;//判断通过cache防火墙 正在处理请求数
    	$err_cachekey = "err".$errType."_buyremain_".$prjId; // 初始化执行恢复cache的key，修复的条件必须满足（1）已经再处理投标数据的程序都执行完成，2）高并发下多个请求只有一个进程再执行修复代码）
    	Import("libs.Counter.MultiCounter", ADDON_PATH);
    	$prjerr_cachekey_counter = MultiCounter::init(Counter::GROUP_PRJ_COUNTER."_err", Counter::LIFETIME_THISHOUR);
    	$act2 = $prjerr_cachekey_counter->incr($err_cachekey, 1);

    	$prj_cachekey_counter = MultiCounter::init(Counter::GROUP_PRJ_COUNTER."_con", Counter::LIFETIME_THISMONTH);
    	if($act2 == 1){// 确保高并发下多个请求只有第一个进程再执行修复代码
    		for($ii=1; $ii<=3;$ii++){// 就是看漏进去的数据（已经再处理投标的进程）都有没有跑完
    			$doing_con = $prj_cachekey_counter->get($doing_cachekey);
    			if(!$doing_con) break; //都跑完了就跳出来
    			if($ii == 3){
    				$prjerr_cachekey_counter->set($err_cachekey, 0);//还没有跑完，不等了，直接设置确保高并发下多个请求只有一个进程再执行修复代码的开关，然下个进程可以执行这个修复代码
                    $prj_cachekey_counter->set($doing_cachekey, 0);
                    if($errType == 2) throw_exception("投标已经结束 ！(".$doing_con.")");//抛出错误信息来
    				if($errType == 1) throw_exception("1cache失效！(".$doing_con.")");
    			}
    			sleep(1);
    		}
    		//开始执行修复错误的代码
    		$prj = M("prj")->find($prjId);
    		cache($remaining_cachekey, (1+$prj['remaining_amount']), array('expire'=>24*3600*30));
    		$prjerr_cachekey_counter->set($err_cachekey, 0);
    		if(!$prj['remaining_amount']){
    			if($errType == 2) throw_exception("投标结束了！");
    			if($errType == 1) throw_exception("投标结束了！！");
    		}
    		if($prj['remaining_amount'] < $money){
    			if($errType == 2) throw_exception("剩余投标金额不足！");
    			if($errType == 1) throw_exception("剩余投标金额不足！！");
    		}
    	} else {// 确保高并发下多个请求只有一个进程再执行修复代码,已经于再修复的进程了，其他的进程就跑这段，直接异常
    		if($errType == 2) throw_exception("投标已经结束");
    		if($errType == 1) throw_exception("cache失效");
    	}
    }

    function buyFirewall($money,$uid,$prjId,$title="购买借贷产品",$moneyAccountId,$params,$isMobile=0,$is_mo=0){
    	$remaining_cachekey = "buyremain_".$prjId;//投标是剩余投标金额存在cache里的key
    	$doing_cachekey = "buydoing_con".$prjId; //判断通过cache防火墙 正在处理请求数
        $before_result = cache($remaining_cachekey);
        if(trim($before_result) === '0'){ //校验 剩余投标金额 是否被击穿了，
            $this->fixCache($prjId, $money, 2);
        }
        if($before_result == 1){
            throw_exception("投标已经满了");
        }

        if($before_result && $before_result < $money+1){
            throw_exception("剩余投标金额不足");
        }

        //防止重复提交，，再检测一次
        if (!$params['x_order_id'] && !$params['is_platform_user']) {
            $checkResult = $this->buyCheck($money,$prjId,$uid,$params,$isMobile,$is_mo,0,0);
            if(!$checkResult) throw_exception(MyError::lastError());
        } else {
            service('Financing/XProject')->buyMatchRecordCheck($uid, $prjId, $money, $params['x_order_id'], false, $params['is_platform_user']);
        }

        $cache = Cache::getInstance();
        $remaining_money = $result = $cache->decrement($remaining_cachekey, $money);

        Import("libs.Counter.MultiCounter", ADDON_PATH);
        $prj_cachekey_counter = MultiCounter::init(Counter::GROUP_PRJ_COUNTER."_con", Counter::LIFETIME_THISMONTH);

        if(!$result && trim($result) !== '0'){//校验 是否cache失效，
            $this->fixCache($prjId, $money, 1);
            $remaining_money = $result = $cache->decrement($remaining_cachekey, $money);
            if(!$result){//如果cache投标金额为0，减的时候有被击穿了，直接异常，等下一个进程来进行修复
                throw_exception("cache失效！2");
            }
        }
        if(trim($result) === '0'){
            $this->fixCache($prjId, $money, 2);
            throw_exception("投标已经结束");
        }

        //该项目的总投标数
//     		$remaincon_cachekey = "buyremain_con".$prjId;
//         	$prj_cachekey_counter = MultiCounter::init(Counter::GROUP_PRJ_COUNTER."_con", Counter::LIFETIME_THISMONTH);
//     		$prj_cachekey_counter->incr($remaincon_cachekey, 1);

        //记录正在执行的请求数
        $prj_cachekey_counter->incr($doing_cachekey, 1);
        return $remaining_money;
    }

    function descDoingCountCache($prjId, $doing_count){
    	Import("libs.Counter.MultiCounter", ADDON_PATH);
    	$doing_cachekey = "buydoing_con".$prjId;
    	$prj_cachekey_counter = MultiCounter::init(Counter::GROUP_PRJ_COUNTER."_con", Counter::LIFETIME_THISMONTH);
    	if($doing_count) $prj_cachekey_counter->desc($doing_cachekey, 1);
    }

    function buyCacheRollback($prjId, $money){
    	$remaining_cachekey = "buyremain_".$prjId;
//         $remaincon_cachekey = "buyremain_con".$prjId;
        $cache = Cache::getInstance();
//         $prj_cachekey_counter = MultiCounter::init(Counter::GROUP_PRJ_COUNTER."_con", Counter::LIFETIME_THISMONTH);
        $before_result = cache($remaining_cachekey);
        if(trim($before_result) === '0'){ //校验 剩余投标金额 是否被击穿了，
        	$this->fixCache($prjId, $money, 2);
        	throw_exception("投标已经结束了!");
        }
    	$result = $cache->increment($remaining_cachekey, $money);
    	if($result - $money < 1){
    		cache($remaining_cachekey, 0);
    		$this->fixCache($prjId, $money, 2);
    		throw_exception("投标已经结束!");
    	}
//     	$prj_cachekey_counter->desc($remaincon_cachekey, 1);

    	M('prj')->where(array("id"=>$prjId, "bid_status"=>PrjModel::BSTATUS_FULL))
    	->save(array("bid_status"=>PrjModel::BSTATUS_BIDING,"mtime"=>time()));
    }

    function getPrjCouponList($uid, $coupon_money, $prjInfo=array()){
    	$prj_min_month = 0;
    	$prj_min_day = 0;
    	if($prjInfo['time_limit_unit'] == 'year') $prj_min_month = 12*$prjInfo['time_limit'];
    	if($prjInfo['time_limit_unit'] == 'month') $prj_min_month = $prjInfo['time_limit'];
    	if($prjInfo['time_limit_unit'] == 'day') $prj_min_day = $prjInfo['time_limit'];
    	$coulist = D('Payment/Coupon')->getCouponList($uid, $coupon_money, array('prj_min_month'=>$prj_min_month, 'prj_min_day'=>$prj_min_day));
    	return $coulist;
    }



    /**
     * 投资之前判定用户的身份是否可以购买企福鑫项目
     * @param type $info扩展项目信息
     * @param type $uid
     * @return array
     */
    protected function _buyBeforeCheckPermission($info,$uid) {
        $service = service("Financing/CompanySalary")->init($uid);
        $res = $service->buyBeforeCheckPermission($info);
        return array(
            'status'=>$res,
            'error'=>$service->getMsg(),
        );
    }

    private function firewall($money, $uid, $prjId, $title, $moneyAccountId, $params, $isMobile, $is_mo)
    {
        if (!$params['is_lock']) {
            return $this->buyFirewall($money, $uid, $prjId, $title, $moneyAccountId, $params, $isMobile, $is_mo);
        } else {
            return service('Financing/XProject')->buyMatchRecordCheck($uid, $prjId, $money, $params['x_order_id'], true, false);
        }
    }

    /**
     * TODO 满标处理在事务内处理是否合适
     * 判断一个标的是不是满了
     * @param int $is_lock 是不是司马小鑫匹配到的提前锁定的标
     * @param $prj_info
     * @param money
     * @param $remaining_money
     * @param $remaining_cachekey
     */
    public function modifyPrjAmountAndStatus($is_lock, $prj_info, $money, $remaining_money, $remaining_cachekey)
    {
        $prj_model = D('Financing/Prj');
        if (!$is_lock) {
            $con = $prj_model->where(array('id' => $prj_info['id'], 'remaining_amount' => array('egt', $money)))->setDec('remaining_amount', $money);
        } else {
            $con = $prj_model->where(array('id' => $prj_info['id'], 'matched_amount' => array('egt', $money)))->setDec('matched_amount', $money);
        }

        if(!$con) {
            throw_exception("剩余可投资金额不足，投资失败，请下次再来!");
        }

        $save_data = array(
            'bid_status' => PrjModel::BSTATUS_FULL,
            'full_scale_time' => time(),
            'mtime' => time(),
        );
        if ($is_lock) {
            if ($prj_info['remaining_amount'] == 0 && $remaining_money == 0) {
                $save_data['matched_amount'] = 0;
                $prj_model->where(array('id' => $prj_info['id']))->save($save_data);
            }
        } else {
            if($remaining_money == 1 && cache($remaining_cachekey) == 1 && $prj_info['matched_amount'] == 0){
                $save_data['remaining_amount'] = 0;
                $prj_model->where(array('id' => $prj_info['id']))->save($save_data);
            }
        }
    }

    /**
     * 开标 (使用事务) $is_mo=1 说明是渠道（新浪微财富，数米等的api投资）
     */
    function buy($money, $uid, $prjId, $title = "购买借贷产品", $moneyAccountId, $params, $isMobile = 0, $is_mo = 0, $mo_order_no = '', $coupon_type = 1)
    {
        // 时间间隔检测开始
        $is_check_time_interval = !$params['skip_time_interval'];
        if ($is_check_time_interval) {
            $srvTimerBuy = service('Financing/TimerBuy');
            $srvTimerBuy->init($uid, $prjId);
            $srvTimerBuy->start();
        }
        $amount = $money;
        $params['award'] = number_format($params['award'], 2, '.', '');

        $money = number_format($money, 2, '.', '');

        $key = self::BUY_CACHE_KEY . $uid . $prjId;
        $remaining_cachekey = "buyremain_" . $prjId;
        $doing_count = false;
        $reward_service = service("Financing/UseReward");

        $x_order_id = isset($params['x_order_id']) && $params['x_order_id'] ? 1 : 0;
        $is_lock_buy = (int)$params['is_lock']; //是不是司马小鑫匹配到的一定要购买成功的
        $is_platform_user = (int)$params['is_platform_user']; //是不是平台账户接盘债权
        $db = null;
        $cache_back = false;

        //购买处理
        try {

            $remaining_money = $this->firewall($money, $uid, $prjId, $title, $moneyAccountId, $params, $isMobile, $is_mo);
            $doing_count = true;

            $cache_back = true;// 这个不可移动，需要根据这标来判断是否需要回滚cache计算器

            $award = (isset($params['award']) && ($params['award'] > 0)) ? $params['award'] : 0;
            $realMoney = $money - $award;

            /* @var $payAccount PayAccountService */
            $payAccount = service("Payment/PayAccount");
            $reward_money = 0;

            $prjObj = M("prj");
            //项目详情增加
            $prjInfo = $prjObj->where(array("id" => $prjId))->find();
            $act_prj_ext = M('prj_ext')->find($prjId);

            $investuser = M('user')->find($uid);
            $apiInfo = C("api_info");

            $prj_member = M("prj_member")->field('mi_no')->where(array('prj_id' => $prjId))->select();
            $members = [];
            if ($prj_member) {
                foreach ($prj_member as $v) {
                    $members[] = $v['mi_no'];
                }
            }

            if (
                $prjInfo['prj_type'] != PrjModel::PRJ_TYPE_J
                && !in_array($investuser['mi_no'],$members)
                && ($prjInfo['mi_no'] != $apiInfo['api_key_all'])
            ) {
                throw_exception("该项目为渠道标，您不可投资");
            }

            // 同步利息等字段到order里 TODO: 理财上线的时候这些值需要变动
            $sync_data_to_order = array(
                'rate' => $prjInfo['rate'],
                'rate_type' => $prjInfo['rate_type'],
                'year_rate' => $prjInfo['year_rate'],
                'time_limit' => $prjInfo['time_limit'],
                'time_limit_unit' => $prjInfo['time_limit_unit'],
                'time_limit_day' => $prjInfo['time_limit_day'],
                'cash_level' => $act_prj_ext['cash_level'],
            );
            //理财产品
            if ($prjInfo['prj_type'] == PrjModel::PRJ_TYPE_G) {
                $sync_data_to_order = array_intersect_key($params, $sync_data_to_order);
            }
            $reward_type = (int)$params['reward_type'];
            if ($is_mo) {
                //写入订单
                $db = new BaseModel();
                $db->startTrans($key);
                $prjOrder = D("Financing/PrjOrder");
                $result = $prjOrder->addMiOrder($uid, $prjId, $money, $mo_order_no, 0, $prjInfo);
                if (!$result) {
                    throw_exception(MyError::lastError());
                }
                list($orderNo, $buyOrderId) = $result;
                $this->syncOrderData($buyOrderId, $sync_data_to_order);
            } else {
                $db = new BaseModel();
                $db->startTrans($key);
                if ($reward_type > 0) {
                    // 冻结用户奖励(红包、加息券、满减券)
                    $requestId = $reward_service->createRequestId(array(
                        'uid' => $uid,
                        'prjId' => $prjId,
                        'timeLimitDay' => $prjInfo['time_limit'],
                        'amount' => $amount,
                    ));
                    //项目名称加上项目类型
                    $show_prj_name = D("Financing/Prj")->get_show_prj_name($prjInfo['prj_type'], $prjInfo['prj_name']);
                    $will_reward_data = $reward_service->freezeReward($uid, $prjId, $prjInfo['time_limit_day'], $amount,
                        $requestId, $show_prj_name, $params);

                    \Addons\Libs\Log\Logger::info("UID[$uid]投资PRJ[$prjId]冻结", 'BUY_FREEZE', array('data'=>$will_reward_data));
                }
                $a_coupon_money = -1;

                $awardCheck = $this->parseAward($params, $uid, $a_coupon_money, (int) $act_prj_ext['is_deposit']);
                if ($awardCheck === false) {
                    throw_exception(MyError::lastError());
                } else {
                    list($reward_money, $invest_reward_money, $coupon_money) = $awardCheck;
                }
                //奖励金额就是冻结金额
                $invest_reward_money = 0;
                $freeze_amount = 0;
                D("Financing/UserBonusFreeze");
                if ($reward_type > 0 && $reward_type != UserBonusFreezeModel::TYPE_RATE) {
                    $freeze_amount = $will_reward_data['freezeAmount'];
                }

                $params['invest_reward_money'] = $freeze_amount;
                $realMoney = $money - $freeze_amount - $reward_money;
                //如果全部使用了现金红包 并且使用了奖励
                if ($realMoney < 0) {
                    $reward_money = $params['reward_money'] = $money - $freeze_amount;
                }
                $realMoney = $money - $freeze_amount - $reward_money;
                //写入订单
                $prjOrder = D("Financing/PrjOrder");
                $result = $prjOrder->addOrder($uid, $prjId, $money, 0, $prjInfo, $params);
                if (!$result) {
                    throw_exception(MyError::lastError());
                }
                list($orderNo, $buyOrderId) = $result;

                $this->syncOrderData($buyOrderId, $sync_data_to_order);

                //冻结账户
                import_app("Payment.Model.PayAccountModel");
                $objType = $x_order_id ?
                    PayAccountModel::OBJ_TYPE_PAYFREEZE_X :
                    ($act_prj_ext['is_deposit'] ?
                        PayAccountModel::OBJ_TYPE_ZS_BUY_FREEZE :
                        PayAccountModel::OBJ_TYPE_PAYFREEZE);

                $free_money = (int)$realMoney + $reward_money + $freeze_amount;
                $free_money = $payAccount->giveFreeMoney($uid, $free_money, $freeze_amount, $x_order_id);

                $payResult = $payAccount->freezePay($uid, $orderNo, $realMoney, $reward_money, $invest_reward_money, 0, $title, "", $moneyAccountId,
                    $objType, $buyOrderId, $free_money);


                if (!$payResult['boolen']) {
                    throw_exception($payResult['message']);
                }
                $payOrderNo = $payResult['payorderno'];

                $is_no_xorder = true;
                if($x_order_id||$is_platform_user) $is_no_xorder=false;//是司马小鑫

                if($is_no_xorder){//司马小鑫直接跳过，在这里不修改免费提现次数
                    if ($investuser['is_newbie'] == 1) {
                        $prj_tixian_times_counter = MultiCounter::init(Counter::GROUP_PRJ_COUNTER . "_free_tixian_times", Counter::LIFETIME_TODAY);
                        $tixian_con = $prj_tixian_times_counter->incr("free_tixian_" . $prjId . "_" . $uid, 1);
                        if ($tixian_con == 1) {
                            $prjOrder->where(array("id" => $buyOrderId))->setInc("free_tixian_times", 1);
                        }
                    } else {
                        if (($prjInfo['prj_type'] == PrjModel::PRJ_TYPE_B || $prjInfo['prj_type'] == PrjModel::PRJ_TYPE_D
                            || $prjInfo['prj_type'] == PrjModel::PRJ_TYPE_F || $prjInfo['prj_type'] == PrjModel::PRJ_TYPE_G)
                        ) {
                            $prj_tixian_times_counter = MultiCounter::init(Counter::GROUP_PRJ_COUNTER . "_free_tixian_times", Counter::LIFETIME_TODAY);
                            $tixian_con = $prj_tixian_times_counter->incr("free_tixian_" . $prjId . "_" . $uid, 1);
                            if ($tixian_con == 1) {
                                $prjOrder->where(array("id" => $buyOrderId))->setInc("free_tixian_times", 1);
                            }
                        } else {
                            if (($prjInfo['time_limit_unit'] == 'day' && $prjInfo['time_limit'] >= 30)
                                || ($prjInfo['time_limit_unit'] == 'month' && $prjInfo['time_limit'] >= 1)
                                || ($prjInfo['time_limit_unit'] == 'year')
                            ) {
                                if ($money >= 100000) {
                                    $prj_tixian_times_counter = MultiCounter::init(Counter::GROUP_PRJ_COUNTER . "_free_tixian_times",
                                        Counter::LIFETIME_TODAY);
                                    $tixian_con = $prj_tixian_times_counter->incr("free_tixian_" . $prjId . "_" . $uid, 1);
                                    if ($tixian_con == 1) {
                                        $prjOrder->where(array("id" => $buyOrderId))->setInc("free_tixian_times", 1);
                                    }
                                }
                            }
                        }
                    }
                }

                //更新
                $prjOrder->where(array("id" => $buyOrderId))->save(array(
                    "mtime" => time(),
                    "out_order_no" => $payOrderNo,
                    "free_money" => $free_money
                ));

                if (!$remaining_money) {
                    throw_exception("剩余可投资金额不足，投资失败，请下次再来!");
                }

                //协议保存
                if ($prjInfo['prj_class'] == PrjModel::PRJ_CLASS_LC) {
                    service('Financing/InvestFund')->saveOrderProtocolId($buyOrderId);
                } else {
                    if ($prjInfo['prj_type'] != PrjModel::PRJ_TYPE_C) {
                        $protocol = $this->getProtocol($prjInfo['prj_type'], $prjInfo['zhr_apply_id'], $prjId);
                        $protocolKey = $protocol['key'];
                        $protocolId = service("Financing/Protocol")->savePdf($protocolKey, $buyOrderId, $prjId);
                        if (!$protocolId) {
                            throw_exception(MyError::lastError());
                        }
                        M("prj_order")->where(array("id" => $buyOrderId))->save(array("protocol_id" => $protocolId));
                        //判断当前购买项目ext表中 order_protocol_id 字段是否已保存
                        $order_protocol_id = M("prj_ext")->where(['prj_id' => $prjId])->getField('order_protocol_id');
                        if (!$order_protocol_id) {//保存
                            M("prj_ext")->where(['prj_id' => $prjId])->save(['order_protocol_id' => $protocolId]);
                        }
                    }
                }
                //推荐是否投资
                M("user_recommed")->where(array("uid" => $uid))->save(array("is_invest" => 1, "mtime" => time()));
                //2015/05 推广渠道记录是否投资
                $user_source = M('user_register_source')->where(array('uid' => $uid))->find();
                if ($user_source && $user_source['has_invested'] == 0) {
                    $temp_now = time();
                    $user_source['has_invested'] = 1;
                    $user_source['first_invest_time'] = $temp_now;
                    $user_source['first_invest_amount'] = $realMoney;//记录的真实资金，不包括送的红包什么的奖励。
                    M('user_register_source')->save($user_source);
                }

                //保存奖励数据
                $rewardData = array();
                $rewardData['reward_type'] = $params['reward_type'];
                $rewardData['reward_money'] = $reward_money;
                $rewardData['invest_reward_money'] = $freeze_amount;
                if ($rewardData['reward_type']) {
                    $rewardData['reward_id'] = $params['reward_id'];
                    $now = time();
                    $rewardData['prj_order_id'] = $buyOrderId;
                    $rewardData['ctime'] = $now;
                    $rewardData['mtime'] = $now;
                    M("prj_order_ext")->add($rewardData);
                }

                if (!($is_lock_buy || $params['x_order_id'] || $params['is_platform_user'])) {
                    //回款数据记录生成
                    service("Payment/UserAccountSummary")->changeAcountSummary('buy', $buyOrderId, 0, 0);
                    service("Financing/Invest")->addUserRepayPlan($buyOrderId);
                }

                //登记债权
                if ($params['x_order_id'] || $params['is_platform_user']==1) {
                    service('Financing/ClaimsTransfer')->bookinClaimsTransfer($buyOrderId);
                }
            }
            //处理分期付款等
            $this->bugAfterDb($buyOrderId);
            if (MyError::hasError()) {
                throw_exception(MyError::lastError());
            }

            //从返利投链接过来投资记录
            service('Api/Fanlitou')->addFanlitouOrderLog($uid,$buyOrderId);

            if ($act_prj_ext && $act_prj_ext['is_college']) {//如果是大学生专属项目
                service("Payment/CollegeAccount")->cgfreeze($uid, $money, $buyOrderId);
            }

            $prjOrder->addOrderAfterCount($uid, $prjId, 0);
            $this->setOrderLastRepayDate($buyOrderId); // 写入订单last_repay_date
            $this->modifyPrjAmountAndStatus($is_lock_buy, $prjInfo, $money, $remaining_money, $remaining_cachekey);

            //同步关联订单状态
            D("Financing/UserBonusFreeze")->updateFreezeOrderId($requestId, $buyOrderId, $params);

            //通知托管银行冻结用户资金
            if ($act_prj_ext['is_deposit']) {
                $this->noticeBankFreezeUserMoney($realMoney + $reward_money, $buyOrderId, $orderNo, $investuser, $prjInfo, ['is_prj_order' => 1]);
            }

            $db->commit($key);

            $this->bugAfter($buyOrderId);
            if (!$is_lock_buy) {
                $this->descDoingCountCache($prjId, $doing_count);
            }

            if (!($x_order_id || $is_platform_user)) {
                //投资完毕以后的通知
                $is_appoint_invest = $params['is_appoint_invest'];
                service("Financing/AfterInvestTask")->notice($uid, $buyOrderId, $is_appoint_invest);
            }

//            //签约系统开启 2016年11月 不再在投标时往队列里放数据
//            if (C('SIGNATURE_SWITCH')) {
//                //创建订单合同
//                $this->addContractQueue($buyOrderId, $prjInfo, $uid);
//            }

            // 时间间隔检查结束
            if ($is_check_time_interval && isset($srvTimerBuy)) {
                $srvTimerBuy->end();
            }

        } catch (Exception $e) {

            if ($db) {

                $db->rollback($key);
                \Addons\Libs\Log\Logger::warning("UID[$uid]投资PRJ[$prjId]回滚STEP1", 'BUY_FREEZE', array('data'=>[
                    'requestId' => $requestId,
                    'freeze_amount' => $freeze_amount,
                ]));

            }

            if ($requestId/* && $freeze_amount > 0*/) {
                //使用队列进行解冻通知
                $params['uid'] = $uid;
                $params['memo'] = $e->getMessage();
                $params['requestId'] = $requestId;
                try {

                    if ($freeze_amount > 0) {
                        \Addons\Libs\Log\Logger::info("UID[$uid]投资PRJ[$prjId]回滚STEP2", 'BUY_UNFREEZE', array('data'=>[
                            'requestId' => $requestId,
                            'freeze_amount' => $freeze_amount,
                        ]));
                    } else {
                        \Addons\Libs\Log\Logger::err("UID[$uid]投资PRJ[$prjId]回滚STEP3", 'BUY_UNFREEZE', array('data'=>[
                            'requestId' => $requestId,
                            'freeze_amount' => $freeze_amount,
                        ]));
                    }

                    queue("unfreeze", $requestId, $params);
                } catch (Exception $exc) {

                    \Addons\Libs\Log\Logger::err("UID[$uid]投资PRJ[$prjId]回滚STEP4".$exc->getMessage(), 'BUY_UNFREEZE', array('data'=>[
                        'requestId' => $requestId,
                        'freeze_amount' => $freeze_amount,
                    ]));

                }
            }

            if (!$is_lock_buy) {
                $this->descDoingCountCache($prjId, $doing_count);
                if ($cache_back) {
                    try {
                        $this->buyCacheRollback($prjId, $money);
                    } catch (Exception $e) {
                        MyError::add($e->getMessage());
                        return false;
                    }
                }
            }

            MyError::add($e->getMessage());
            return false;
        }

        if (!$is_mo && !($is_lock_buy || $is_platform_user || $params['x_order_id'])) {
            if ($prjInfo['status'] == PrjModel::STATUS_PASS_SPECIAL) {
                \Addons\Libs\Log\Logger::info("messageData数据", 'messageData','是否进入2');
                $messageData = array();
                $messageData[] = $prjInfo['prj_name'];
                $messageData[] = humanMoney($money, 2, false);
                service("Message/Message")->sendMessage($uid, 1, 206, $messageData, $prjId, 0, array(1, 2), true);
                service("Mobile2/Remine")->push_uid($uid, 206, time(), $prjId, $messageData, '', 0);
            } else {

                //发送消息
                $info = M("user")->where(array("uid" => $uid))->find();
                $messageData = array();
                $messageData[] = $info['uname'];
                $messageData[] = $prjInfo['prj_name'];
                $messageData[] = humanMoney($money, 2, false) . "元";
                if ($free_money) {
                    $messageData[] = "，直到投标结束您账户内的这部分资金将被冻结";
                    $messageData[] = "，并将获得" . humanMoney($free_money, 2, false) . "元的免费提现额度";
                } else {
                    $messageData[] = '';
                    $messageData[] = '';
                }

                // 6. 展期/非展期还款日提醒
                $extend_pre = '该项目还款最迟';
                $extend_tail = ' 13:00前到账。';
                $last_repay_date = date('Y-m-d', $prjInfo['last_repay_date']);
                if ($act_prj_ext['is_extend']) {
                    $srvPrjRepayExtend = service('Financing/PrjRepayExtend');
                    $extend_pre = '该项目允许展期还款，最迟';
                    $last_repay_date = date('Y-m-d', $srvPrjRepayExtend->getMaxExtendTime($prjId));
                }
                if ($prjInfo['value_date_shadow'] > 0) {
                    $extend_tail = ' 16:00-24:00到账。';
                }
                $messageData[] = $extend_pre . $last_repay_date . $extend_tail;

                $messageData[]= date("Y-m-d H:i:s");
                $extend_pre_m = '该项目最迟还款';
                $messageData[]=$extend_pre_m . $last_repay_date;
                //成功这发送邮件
                service("Message/Message")->sendMessage($uid, 1, 1, $messageData, $prjId, 1, array(1, 2, 3), true);
                //投资成功推送手机消息
                service("Mobile2/Remine")->push_uid($uid, 1, time(), $prjId, $messageData, '', 0);

                //推送微信消息给用户
                $wx_msg_data = array(
                    'real_name' => $info['real_name'],
                    'prj_name' => $prjInfo['prj_name'],
                    'money' => humanMoney($money, 2, false),
                );
                service('Weixin/Xhhfuwu')->addInvestMsgQueue($uid, $buyOrderId, $wx_msg_data);
            }
        }

        return $buyOrderId;
    }

    /**
     * 通知托管银行冻结用户资金
     * @param $money
     * @param $serial_id
     * @param $serial_no
     * @param $user
     * @param $prj_info
     * @param array $extra_params
     */
    public function noticeBankFreezeUserMoney($money, $serial_id, $serial_no, $user, $prj_info, $extra_params = [])
    {
        $notice_bank_params = [
            'projectId' => $prj_info['id'],
            'projectName' => $prj_info['prj_name'],
            'realName' => $user['real_name'],
            'certType' => '1',
            'certNo' => $user['person_id'],
            'bindSerialNo' => $user['zs_bind_serial_no'],
            'p2pSerialNo' => $serial_no,
            'amount' => (int) $money,
            'currency' => '156',
            'verifyCode' => '',
            'commendCode' => '',
            'remark' => 'mock fundBlock',
        ];

        $call_result = async_call_czb_api(
            'czb_fundBlock',
            $notice_bank_params,
            $serial_id,
            'PrjService::asyncBuyFreezeCallBack',
            ['Financing.Service.PrjService'],
            $extra_params
        );
        if (!$call_result) {
            throw_exception('银行冻结流水失败');
        }
    }

    /**
     * 设置项目满标 满标返回true 否则 false
     * @param $prjId
     * @param $remainingAmount
     * @param $remain_matched_amount
     * @param $prjType
     * @return bool
     */
    function setPrjFull($prjId, $remainingAmount, $remain_matched_amount, $prjType)
    {
        if ($remainingAmount == 0 && ($remain_matched_amount == 0 || $prjType == PrjModel::PRJ_TYPE_J)) {
            $now = time();
            $update = [
                'bid_status' => PrjModel::BSTATUS_FULL,
                'full_scale_time' => $now,
                'mtime' => $now,
            ];

            M('prj')->where(['id' => $prjId, 'bid_status' => PrjModel::BSTATUS_BIDING])->save($update);
            if ($prjType == PrjModel::PRJ_TYPE_J) {

                $x_project_service = new XProjectService();
                if (!$x_project_service->checkTransferPrjIsRealFull($prjId)) {
                    return false;
                }

                // 设置转让订单的转让状态
                D('Financing/PrjOrder')->setTransStatusEndFull($prjId, true);
                service('Financing/ClaimsTransfer')->complete($prjId);
            }/* elseif ($prjType != PrjModel::PRJ_TYPE_H) {
                //生成还款计划去
                if ($update_rows > 0) {
                    queue('create_repay_plan_after_full', $prjId . '_' . date('Ymd'), ['prj_id' => $prjId]);
                }
            }*/

            return true;
        }

        return false;
    }

    /**
     * 购买后处理
     * @param unknown $orderId
     */
    function bugAfterDb($orderId){
    	$orderInfo = M("prj_order")->where(array("id"=>$orderId))->find();
    	$prjId = $orderInfo['prj_id'];
    	$prjInfo = M("prj")->where(array("id"=>$prjId))->find();
    	//        if($prjInfo['is_auto_end_bid_time']) return true;
        if($this->isPrjVersionRepayPlanDelay($prjInfo['version'])) return TRUE; // 新项目都不生成还款计划

    	$isEnd = service("Financing/Invest")->isEndDate($prjInfo['repay_way']);
    	if(!$isEnd){
    		$obj = $this->getTradeObj($prjId, "Bid");
    		if(!$obj) return errorReturn(MyError::lastError());
    		$obj->savePersonRepaymentJobDb(array("order_id"=>$orderId));
    	}
    	if(MyError::hasError()){
    		return errorReturn(MyError::lastError());
    	}
    	return true;
    }

    /**
     * 购买后处理
     * @param unknown $orderId
     */
    function bugAfter($orderId){
    	$orderInfo = M("prj_order")->where(array("id"=>$orderId))->find();
    	$prjId = $orderInfo['prj_id'];
    	$prjInfo = M("prj")->where(array("id"=>$prjId))->find();
//      if($prjInfo['is_auto_end_bid_time']) return true;
        //非新浪微财富等渠道的订单处理
        if (!$orderInfo['is_mo'])
        {
            $service = service("Index/ActivitySet");
            $service->projectSet($orderInfo['uid'], ActivitySetService::PROJECT_TYPE_INVEST, $prjInfo['activity_id'], $orderId);
        }

        if($this->isPrjVersionRepayPlanDelay($prjInfo['version'])) return TRUE; // 新项目都不生成还款计划


    	$isEnd = service("Financing/Invest")->isEndDate($prjInfo['repay_way']);
    	if(!$isEnd){
    		$obj = $this->getTradeObj($prjId, "Bid");
            if(!$obj) return errorReturn(MyError::lastError());
    		$obj->savePersonRepaymentJob(array("order_id"=>$orderId));
    	}
        if(MyError::hasError()){
            return errorReturn(MyError::lastError());
        }
        return true;
    }

    //发送到node
    function send2node($prjId)
    {
        $prjIds = C("REALTIME_PRJ_ID");
        if (!in_array($prjId, $prjIds)) return true;
        $info = $this->getDataById($prjId);

        $remaining_cachekey = "buyremain_" . $prjId;
        $result = cache($remaining_cachekey);
        if (!$result) {
            $result = cache($remaining_cachekey);
        }
        if (!$result) {
            $result = $info['remaining_amount'];
        }
        if (!$result) $result = 0;
        else $result = $result - 1;
        $remainingAmountView = humanMoney((int)$result / 10000, 2, false);

        $schedule = $this->getSchedule($info['demand_amount'], (int)$result);

        if ($schedule > 100) return true;

        $remaincon_cachekey = "buyremain_con" . $prjId;
//     	Import("libs.Counter.SimpleCounter", ADDON_PATH);
//     	$prj_counter = SimpleCounter::init(Counter::GROUP_PRJ_COUNTER, Counter::LIFETIME_THISMONTH);
//     	$prj_cnt = $prj_counter->get($remaincon_cachekey);

// 		$prj_cnt = cache($remaincon_cachekey);
    	Import("libs.Counter.MultiCounter", ADDON_PATH);
    	$prj_cachekey_counter = MultiCounter::init(Counter::GROUP_PRJ_COUNTER."_con", Counter::LIFETIME_THISMONTH);
    	$prj_cnt = $prj_cachekey_counter->get($remaincon_cachekey);

    	if(!$prj_cnt) {
    		$cnt = M("prj_order")->where(array('prj_id'=>$prjId))->field("COUNT(*) AS CNT")->find();
    		$prj_cnt = (int)$cnt['CNT'];
    	}

        $url = C('SEND_TO_NODE_URL') . "id={$prjId}&schedule=" . $schedule . "&leftamount=" . $remainingAmountView . "&cnt=" . (int)$prj_cnt;
//     	@file_get_contents($url);
        curlGetContents($url);
//     	$send_node_log['prj_id'] = $prjId;
//     	$send_node_log['log'] = $url;
//     	$send_node_log['status'] = 1;
//     	if(!$url) $send_node_log['status'] = 2;
//     	$send_node_log['ctime'] = time();
//     	M('send_node_log')->add($send_node_log);
    }


    //购买检查,适用于 白富美，高富帅
    /**
     * @param $money
     * @param $prjid
     * @param $uid
     * @param $params
     * @param int $isMobile
     * @param int $is_mo
     * @param int $is_pre_sale 是否预售检查
     * @param int $init 判断使用默认投资金额还是输入金额
     * @return bool
     */
    function buyCheck($money, $prjid, $uid, $params, $isMobile = 0, $is_mo = 0,$is_pre_sale=0,$init=1)
    {
        $params['award'] = number_format($params['award'], 2, '.', '');
        $money = number_format($money, 2, '.', '');
        $award = (isset($params['award']) && ($params['award'] > 0)) ? $params['award'] : 0;
//     	$coupon_money = (isset($params['coupon_money']) && ($params['coupon_money']> 0)) ? $params['coupon_money']:0;
        $realMoney = $money - $award;
        if ($realMoney < 0) return errorReturn("活动奖励金额不能大于投资金额");
        $info = service("Financing/Project")->getDataById($prjid);
        if (!$info) {
            return errorReturn('异常，项目不存在！');
        }

        $act_prj_ext = M('prj_ext')->find($prjid);

//        $is_pre_sale = $act_prj_ext['is_pre_sale'];
        //预售状态检查 项目扩展表is_pre_sale=1 项目表status=21 bid_status=1的时候才可以预售
        if($is_pre_sale == 0){
            import_app("Financing.Model.PrjModel");
            if ($info['status'] != PrjModel::STATUS_PASS) {
                if( $info['status'] != PrjModel::STATUS_PASS_SPECIAL){
                    return errorReturn("该项目未通过审核，您不能进行此操作");
                }
            }
            if($is_mo){
                if($info['bid_status'] != PrjModel::BSTATUS_BIDING && $info['bid_status'] != PrjModel::BSTATUS_END)
                    return errorReturn("项目已经停止投标/不能补标");
            }
            if ($info['bid_status'] > PrjModel::BSTATUS_BIDING) {
                return errorReturn("项目已经停止投标");
            }
            if($info['bid_status'] < PrjModel::BSTATUS_BIDING && $info['status'] != PrjModel::STATUS_PASS_SPECIAL && !IS_CLI){
                return errorReturn("项目还在待开标");
            }
        }else {
            if (!($info['status'] == PrjModel::STATUS_PASS && $info['bid_status'] == PrjModel::BSTATUS_WATING)) {
                return errorReturn('该项目状态已不接受预售');
            }
            if ($act_prj_ext['is_pre_sale'] == 0) {
                return errorReturn('该项目不是预售项目');
            }
            if ($act_prj_ext['is_pre_sale_deal'] == 2) {
                return errorReturn('预售订单已处理, 不接受预售');
            }
        }

        //大学生项目
        $user_college_ext = M('user_college_ext')->find($uid);
        if($act_prj_ext && $act_prj_ext['is_college'] && !$user_college_ext){
            return errorReturn("没有投标权限");
        }

        //用户是否符合够买此项目（企福鑫的权限）
        $res = $this->_buyBeforeCheckPermission($act_prj_ext,$uid);
        if(!$res['status']){
             return errorReturn($res['error']);
        }

        //判断是否是新客项目
        if ($info['is_new']) {
            $userInfo = M("user")->find($uid);
            if (!$userInfo['is_newbie']) {
                return errorReturn("此项目仅针对首次投资的用户开放，你不能投资该项目!");
            }
        }

        //客户端检查
        $clienttype = $_REQUEST['CLIENT_TYPE'];
        $rs = D("Financing/PrjFilter")->getClientPermission($clienttype,$prjid);
        if(!$rs){
            return errorReturn(MyError::lastError());
        }
        //活动检查放在前面
        $rs = $this->activeCheck($uid, $info);
        if (!$rs) {
            return errorReturn(MyError::lastError());
        }

        if (!$info['is_multi_buy']) {
            $where = array();
            $where['uid'] = $uid;
            $where['prj_id'] = $prjid;
            $where['status'] = array("NEQ", PrjOrderModel::STATUS_NOT_PAY);
            $check = M("prj_order")->where($where)->find();
            if ($check) {
                return errorReturn("此项目只可投资1次");
            }
        }

        if (!is_numeric($award)) {
            return errorReturn("活动奖励金额必须是数字!");
        }

        if (!is_numeric($money)) {
            return errorReturn("投资金额必须是数字!");
        }

        $userModel = service("Account/Account");
        $userinfo = $userModel->getByUid($uid);
        if ((!$userinfo['is_id_auth']) || (!$userinfo['person_id'])) {
            $renzhengUrl = U("Account/Bank/identify");
            if ($isMobile) {
                return errorReturn("投资前请先在账户中进行实名认证");
            }
            return errorReturn("投资前需先进行实名认证,去<a href='{$renzhengUrl}' class='blue' target='_blank'>认证</a>");
        }

        if ($act_prj_ext['is_deposit'] && !$userinfo['zs_close_invest_code']) {
            return errorReturn("请先关闭投资短信校验码");
        }

        if (!$is_mo) {
        	//检查账户余额是否充足
        	$payment = service("Payment/PayAccount");

            $balance = $payment->getBaseInfo($uid);
            if (!$params['is_xjh']) {
                $balance = $act_prj_ext['is_deposit'] ? $balance['zs_amount'] : $balance['amount'];
            } else {
                $balance = $balance['amountx'];}

        	if($user_college_ext && $user_college_ext['coll_amount']){
        		if($act_prj_ext && $act_prj_ext['is_college']){
        			$balance = $user_college_ext['coll_amount'];
        		} else {
        			$balance = $balance - $user_college_ext['coll_amount'];
        		}
        	}

            $checkReturn = $this->buyAmountCheck($money, $prjid, $uid, $params, $is_pre_sale,$init);
            if (!$checkReturn) return errorReturn(MyError::lastError());

        	if((int)$realMoney > (int)$balance){
        		$renzhengUrl = $act_prj_ext['is_deposit'] ? U("Payment/PayAccount/zsRechargePage") : U("Payment/PayAccount/rechargePage");
        		if($isMobile){
                    return errorReturn('账户余额不足，请先充值');
        		}
        		return errorReturn("账户余额不足,去<a href='".$renzhengUrl."'  class='blue' target='_blank'>充值</a>");
        	}

        }

        //判断项目专属性
        if ($info['user_mi_id']) {
            if ($userinfo['mi_id'] != $info['user_mi_id']) {
                return errorReturn("您没有投资该项目的权限!");
            }
        }

        //购买时token验证开关
        if (C('BUY_TOKEN_ON')) {
            //销毁验证token所需的session
            unset($_SESSION['xprjIdAccess']);
            //$this->buyTokenCheck($uid, $prjid);
        }

        return true;
    }


    //购买时验证token，防止脚本支付，验证成功或者失败都销毁session,分为wap验证和app验证
    public function buyTokenCheck($uid, $prjid) {
        $vsource = visit_source();
        if (I('request.client') == 'wap') {//$vsource不是pc,other,ipad浏览器访问都是wap
            if (empty($_SESSION['xprjIdAccess']) || md5($_SESSION['xprjIdAccess'] . $uid . $prjid) != I('request.xprjIdAccess')) {
                \Addons\Libs\Log\Logger::err("投资token验证失败", "buytokencheck", array('from'=>'wap','uid'=>$uid, 'prjid'=>$prjid , 'session'=>$_SESSION['xprjIdAccess'],'xprjIdAccess'=>I('request.xprjIdAccess')));
                unset($_SESSION['xprjIdAccess']);
                return errorReturn("太快了，请刷新重试!");
            }
        }else if ($vsource == 'android'|| $vsource == 'iphone') {//app
            if (empty($_SESSION['xprjIdAccess']) || md5($_SESSION['xprjIdAccess'] . C('XPRJACESSTOKEN')) != I('request.xprjIdAccess')) {
                \Addons\Libs\Log\Logger::err("投资token验证失败", "buytokencheck", array('from'=>$vsource,'uid'=>$uid, 'prjid'=>$prjid , 'session'=>$_SESSION['xprjIdAccess'],'xprjIdAccess'=>I('request.xprjIdAccess')));
                unset($_SESSION['xprjIdAccess']);
                return errorReturn("太快了，请刷新重试!");
            }
        }else{//pc判断  $vsource是pc,other,ipad浏览器访问都是pc
            list($xprjIdAccess,$tktime) = explode('_',I('request.xprjIdAccess'));
            $tokensesname = 'xprjIdAccess'.$prjid.$tktime;
            if(empty($_SESSION[$tokensesname]) || md5($_SESSION[$tokensesname].C('XPRJACESSTOKEN')) != $xprjIdAccess){
                \Addons\Libs\Log\Logger::err("投资token验证失败", "buytokencheck", array('from'=>'pc','uid'=>$uid, 'prjid'=>$prjid , 'session'=>$_SESSION[$tokensesname],'xprjIdAccess'=>I('request.xprjIdAccess')));
                unset($_SESSION[$tokensesname]);
                return errorReturn("太快了，请刷新重试!");
            }
            unset($_SESSION[$tokensesname]);
        }
        unset($_SESSION['xprjIdAccess']);
        return true;
    }

    //用于接口入口处校验token
    public function apiCheckToken($uid, $prjid) {
        $vsource = visit_source();
        if (I('request.client') == 'wap') {//$vsource不是pc,other,ipad浏览器访问都是wap
            if (empty($_SESSION['xprjIdAccess']) || md5($_SESSION['xprjIdAccess'] . $uid . $prjid) != I('request.xprjIdAccess')) {
                \Addons\Libs\Log\Logger::err("投资token验证失败", "buytokencheck", array('from'=>'wap','uid'=>$uid, 'prjid'=>$prjid , 'session'=>$_SESSION['xprjIdAccess'],'xprjIdAccess'=>I('request.xprjIdAccess')));
                return false;
            }
        }else{//app
            if (empty($_SESSION['xprjIdAccess']) || md5($_SESSION['xprjIdAccess'] . C('XPRJACESSTOKEN')) != I('request.xprjIdAccess')) {
                \Addons\Libs\Log\Logger::err("投资token验证失败", "buytokencheck", array('from'=>'app','uid'=>$uid, 'prjid'=>$prjid , 'session'=>$_SESSION['xprjIdAccess'],'xprjIdAccess'=>I('request.xprjIdAccess')));
                return false;
            }
        }
        return true;
    }

    //购买时验证token，pc购买时buy入口住验证token
    public function pcTokenCheck($uid, $prjid) {
        list($xprjIdAccess,$tktime) = explode('_',I('request.xprjIdAccess'));
        $tokensesname = 'xprjIdAccess'.$prjid.$tktime;
        if(empty($_SESSION[$tokensesname]) || md5($_SESSION[$tokensesname].C('XPRJACESSTOKEN')) != $xprjIdAccess){
            \Addons\Libs\Log\Logger::err("投资token验证失败", "buytokencheck", array('from'=>'pc','session'=>$_SESSION[$tokensesname],'xprjIdAccess'=>I('request.xprjIdAccess')));
            return false;
        }
        return true;
    }

    //pc投资时token
    function getAccessToken($prj_id,$uid){
        $tktime = time();
        $tokenkey = session_id().$tktime.$prj_id.$uid;
        $tokensesname = 'xprjIdAccess'.$prj_id.$tktime;
        $_SESSION[$tokensesname] = $xprjIdAccess = pwd_md5($tokenkey);
        $xprjIdAccess = md5($xprjIdAccess.C('XPRJACESSTOKEN')).'_'.$tktime;
        return $xprjIdAccess;
    }

    //活动检查
    function activeCheck($uid, $prjInfo)
    {
        if (!$uid) {
            return errorReturn("用户id不存在");
        }
        if ($prjInfo['huodong'] == 1) {
            $sql = "SELECT a.id FROM fi_prj_order a INNER JOIN fi_prj b ON a.prj_id=b.id WHERE
                a.uid=" . $uid . " AND a.status != " . PrjOrderModel::STATUS_NOT_PAY . " AND b.huodong=1 ";
            $check = M()->query($sql);
            if ($check) {
                return errorReturn("该系列项目只能投资一次");
            }
        }
        return true;
    }


    //购买金额检查,适用于 白富美，高富帅
    function buyAmountCheck($money, $prjId, $uid, $params = array(),$is_pre_buy=0,$init=1)
    {
        $award = (isset($params['award']) && ($params['award'] > 0)) ? $params['award'] : 0;
        $realMoney = $money - $award;
        $params['money'] = $money;
        //使用奖励money检查
        if (!$this->checkReward($params, $uid, $prjId,$init)) {
            return errorReturn(MyError::lastError());
        }

//     	$prjInfo = service("Financing/Project")->getById($prjId);
        $prjInfo = $this->getDataById($prjId);
        D('Financing/Prj');

        $money = (int)$money;
        $prjInfo['min_bid_amount'] = (int)$prjInfo['min_bid_amount'];

        if($is_pre_buy && $prjInfo['bid_status']==PrjModel::BSTATUS_WATING){
            $pre_service = service('Financing/PreSale');
            $prjInfo['remaining_amount'] = $pre_service->getPrjPreRemainingAmount($prjId);
        }
        $prjInfo['remaining_amount'] = (int)$prjInfo['remaining_amount'];

        if ($prjInfo['max_bid_amount']) {
            $totalOrderMoney = service("Financing/Project")->getTotalOrderMoney($uid, $prjId);
            $totalOrderMoneyTmp = $totalOrderMoney + $money; //你投资该项目的总金额
            if ($totalOrderMoneyTmp > $prjInfo['max_bid_amount']) {
                $diff = number_format($prjInfo['max_bid_amount'] / 100, 2);
                $recommendAmount = $prjInfo['max_bid_amount'] - $totalOrderMoney;
                $recommendAmount = humanMoney($recommendAmount, 2, false) . "元";
                return errorReturn("您投资该项目的总金额大于投资上限" . $diff . "元,您还能投资的额度为" . $recommendAmount);
            }
        }

//        //投资金额等于剩余可投资金额的时候不做任何检测直接通过         guojigang 处理case 7404 的时候将其注释
        if ($money == $prjInfo['remaining_amount']) {
            return true;
        }

        if (!$prjInfo['remaining_amount']) {
            MyError::add("没有可投资剩余金额");
            return false;
        }

        if ($money > $prjInfo['remaining_amount']) {
            $diff = humanMoney($prjInfo['remaining_amount'], 2, false);
            return errorReturn("投资金额不能大于剩余可投资金额" . $diff . "元");
        }

        if($prjInfo['remaining_amount'] < $prjInfo['min_bid_amount']){
            if($prjInfo['remaining_amount'] != $money){
                $diff = humanMoney($prjInfo['remaining_amount'], 2, false);
                return errorReturn("剩余可投资金额小于起投金额时，投资金额应等于剩余可投资金额". $diff . "元");
            }
        }

        //司马小鑫不受最小金额和递增金额的限制
        if (!$params['is_xjh']) {
            $minBidAmount = $this->getMinBidAmount($prjInfo['remaining_amount'], $prjInfo['step_bid_amount'], $prjInfo['min_bid_amount']);
            if (($prjInfo['remaining_amount'] >= $minBidAmount) && ($money < $minBidAmount)) {
                $diff = humanMoney($minBidAmount, 2, false);
                return errorReturn("投资金额必须大于等于投资起始金额" . $diff . "元");
            }

            if ($prjInfo['step_bid_amount']) {
                if ($prjInfo['remaining_amount']>$prjInfo['min_bid_amount']) {
                    if (($money % $prjInfo['step_bid_amount']) != 0) {
                        $stepMoneyView = humanMoney($prjInfo['step_bid_amount'], 2, false) . "元";
                        return errorReturn("投资金额必须按递增金额" . $stepMoneyView . "增加!");
                    }
                }
            }
        }

        return true;
    }

    /**
     * 获取真实活动奖励
     * @param $uid
     * @param $money
     * @param int $coupon_money
     * @param int $prjId
     * @param int $a_coupon_money
     * @param int $init
     * @param $reward_info
     * @return int
     */
    function getRealAward($uid, $money, $coupon_money = 0, $prjId=0, $a_coupon_money=0,$init=1,$reward_info = array())
    {
    	/*if($prjId){
	    	$info = M('prj')->find($prjId);
            if(!$a_coupon_money) {
	    	$coulist = service("Financing/Project")->getPrjCouponList($uid, 0, $info);
	    	$a_coupon_money = 0;
                foreach ($coulist as $couEle) {
	    		$a_coupon_money += $couEle['amount'];
	    	}
            }
	    	$coupon_money = $a_coupon_money;
    	}*/
        $totalAward = $this->getTotalAward($uid, NULL, $prjId, $money,$init,$reward_info);
        $totalAward = $coupon_money + $totalAward;
        return $totalAward >= $money ? $money : $totalAward;
    }

    //检查奖励money
    function checkReward($params, $uid, $prj_id=0,$init=1)
    {
        if (!$params) return true;

        if (intval($params['award']) < 0) return errorReturn("活动奖励金额不能为负数");


        $totalAwardMoney = $this->getTotalAward($uid, NULL, $prj_id,$params['money'],$init,$params) + $params['coupon_money'];

        if ($totalAwardMoney < intval($params['award'])) {
            $moneyView = humanMoney($totalAwardMoney, 2, false) . "元";
            return errorReturn("活动奖励金额不能超过活动奖励金额最大值" . $moneyView);
        }
        return true;
    }

    //解析奖励金额,
    function parseAward($params,$uid, $coupon_money=-1, $is_deposit){
    	if(!$params)  return array(0, 0, 0);
    	$user = service("Payment/PayAccount")->getBaseInfo($uid);
    	if($coupon_money>=0) $user['coupon_money'] = $coupon_money;

        $user_reward_money = $is_deposit ? $user['zs_reward_money'] : $user['reward_money'];

    	if($params['award'] <= $user['coupon_money']){
    		return array(0,0,$params['award']);
    	} else if($params['award'] > $user['invest_reward_money']+$user['coupon_money']){
    		$reward_money = $params['award']- $user['invest_reward_money']-$user['coupon_money'];
    		if($reward_money < 0){
    			return errorReturn("活动奖励金额不足");
    		}
            if($user_reward_money < $reward_money) $reward_money = $user_reward_money;
    		return array($reward_money,$user['invest_reward_money'],$user['coupon_money']);
    	}else{
    		$reward_money = $params['award']-$user['coupon_money'];
            if($user['invest_reward_money'] < $reward_money) $reward_money = $user['invest_reward_money'];
    		return array(0,$reward_money,$user['coupon_money']);
    	}
    }

    //获取剩余可用的总奖励
    function getTotalAward($uid, $userC=NULL, $prj_id=0, $money=0,$init=1,$reward_info = array())
    {
        $total = $this->getRewardMoney($uid,0,$prj_id);
        $totle_bonus = 0;
        // 注册红包和普通红包
        if ($prj_id > 0) {
            /* @var $base_invest_rule_service BaseInvestRuleService */
            $base_invest_rule_service = service("Financing/BaseInvestRule");
            $totle_bonus = $base_invest_rule_service->getAllBonusAmount($uid, $prj_id, $money, $init,$reward_info);
        }

        return (int)($total + $totle_bonus);
    }

    public function getRewardMoney($uid,$money = 0,$prj_id)
    {
        $user = service("Payment/PayAccount")->getBaseInfo($uid);
        $prj_is_deposit = (int) M('prj_ext')->where(['prj_id'=>$prj_id])->getField('is_deposit');
        $reward_money = $prj_is_deposit ? (int)$user['zs_reward_money'] : (int)$user['reward_money'];
        if ($money > 0) {
            return $reward_money > $money ? $money : $reward_money;
        }

        return $reward_money;

    }

    //获取最低投标额度
    function getMinBidAmount($remainingAmount, $stepBidAmount, $minBidAmount)
    {
        if ($remainingAmount < $minBidAmount) {
            return $stepBidAmount;
        } else {
            return $minBidAmount;
        }
    }

    //最低投资金额是否更改
    function isMinBidAmountCHange($remainingAmount, $stepBidAmount, $minBidAmount)
    {
        $minBidAmountTmp = $this->getMinBidAmount($remainingAmount, $stepBidAmount, $minBidAmount);
        return $minBidAmount == $minBidAmountTmp ? false : true;
    }

    //获取用户在该项目的所有投资
    function getTotalOrderMoney($uid, $prjId)
    {
        $where['uid'] = $uid;
        $where['prj_id'] = $prjId;
        $where['status'] = array('NEQ', PrjOrderModel::STATUS_NOT_PAY);
        $where['_string'] = 'xprj_order_id is null or xprj_order_id = 0';

        $result = M('prj_order')->field(" SUM(money) as CNT")->where($where)->find();
        return (int)$result['CNT'];
    }

    function getProtocol($prjType, $zhr_apply_id=0, $prj_id=0,$mi_no='')
    {
        if(!$zhr_apply_id) {
            $protocol_type = 17; // 应收账款转让及回购合同
            if ($prj_id>0) {
                $no_guarantor = M('prj_ext')->where(array('prj_id'=>$prj_id))->getField('is_guarantee');
                if ($no_guarantor==1)
                {
                    $protocol_type = 117; // 应收账款转让及回购合同 去担保的
                }
            }
        } else {
            if($prjType == PrjModel::PRJ_TYPE_I){
                $protocol_type = 45; // 质押借款协议
            }else{
                $protocol_type = 15; // 通用借款协议
            }
        }

        if($prjType == PrjModel::PRJ_TYPE_H){
            $protocol_type = 36;
        }

        if ($prjType == PrjModel::PRJ_TYPE_J) {
            $protocol_type = 58;
        }

        if ($prj_id){//为金交所项目设定协议类型
            $is_jjs = M('prj_ext')->where(array('prj_id'=>$prj_id))->getField('is_jjs');
            if ($is_jjs==1) {
                $protocol_type = 71;
            }
        }

        if ($prj_id){//为鑫利宝设定协议类型
            $is_xlb = M('prj_ext')->where(array('prj_id'=>$prj_id))->getField('is_xlb');
            if ($is_xlb==1) {
                $protocol_type = 15;
            }
        }

        if(empty($mi_no)){
            $mi_no = self::$api_key;
        }

        // 获取最终协议名称
        $where = array(
            'is_active' => 1,
            'name_en' => $protocol_type,
            'mi_no' => $mi_no,
        );
        if(in_array($protocol_type, array(15, 17)) && $prj_id) {
            if($protocol_type == 15) $guarantor_id = M('prj')->where(array('id' => $prj_id, 'zhr_apply_id' => array('gt', 0)))->getField('guarantor_id');
            elseif($protocol_type == 17) $guarantor_id = $this->getBlIdByPrjId($prj_id);
            else $guarantor_id = 0;
            if($guarantor_id) {
                $where['guarantor_id'] = $guarantor_id;
            }
        }
        $protocol_name = M('protocol')->where($where)->order('id desc')->getField('name_cn');
        if(!$protocol_name) {
            // 先尝试采用全部接入点或新合会接入点的
            if(!in_array($where['mi_no'], array('1234567889', '1234567890'))) {
                $where['mi_no'] = array(array('EQ', '1234567889'), array('EQ', '1234567890'), 'OR');
                $protocol_name = M('protocol')->where($where)->order('id desc')->getField('name_cn');
            }

            // 直融去不到, 去掉担保公司再尝试
            if(!$protocol_name && isset($where['guarantor_id'])) {
                $where['guarantor_id'] = array(array('EQ', 0), array('EXP', 'IS NULL'), 'OR');
                $protocol_name = M('protocol')->where($where)->order('id desc')->getField('name_cn');
            }
        }

        return array("key" => $protocol_type, "name" => $protocol_name);
    }


    /**
     * 项目还款方式展示
     * 创客融的还款方式为“ 每月等额本息(固定还款日)” 为了区分老的，做特殊处理
     * @param $repay_way
     * @return array|mixed|string
     */
    function getRepayWay($repay_way = '')
    {
        D('Financing/Prj');
        $repay_way_show = getCodeItemName('E004', $repay_way);
        if ($repay_way == PrjModel::REPAY_WAY_PERMONTHFIXN) {
            $repay_way_show = preg_replace('/\(.*\)/', '', $repay_way_show);
        }
        return $repay_way_show;
    }

    /**
     * 获取产品归类
     * @param string $key
     * @return mixed
     */
    function getProductType($key = '')
    {
        return $this->_getCodeItem('E007', $key);
    }

    /**
     * 保障性质
     * @param string $key
     * @return mixed
     */
    function getSafeguards($key = '')
    {
        return $this->_getCodeItem('E006', $key);
    }

    /**
     *
     * 获取利率类型
     * @param string $key
     * @return mixed
     */
    function getRateType($key = '')
    {
        return $this->_getCodeItem('E003', $key);
    }

    /**
     * 期限单位
     * @param string $key
     * @return mixed
     */
    function getTimeLimitUnit($key = '')
    {
        return $this->_getCodeItem('E002', $key);
    }

    /**
     * 产品类型
     * @param string $key
     * @return mixed
     */
    function getPrjType($key = '')
    {
        return $this->_getCodeItem('E005', $key);
    }


    /**
     * 性别
     * @param string $key
     * @return mixed
     */
    function getSex($key = '')
    {
        return $this->_getCodeItem('E005', $key);
    }

    /**
     * 获取字典项
     * @param string $key
     */
    function _getCodeItem($code, $key = '')
    {
        if(!$key) return '';
        return getCodeItemName($code, $key);
    }

    public function getPrjBusinessType($key = '')
    {
        if (in_array($key,array('C', 'D', 'E', 'C0V14', 'C00303'))) {
            return  '经营贷';
        } else {
            return $this->_getCodeItem('FRB001', $key);
        }
    }

    /**
     * 得到担保人
     * @param unknown $guarantorId
     */
    function getGuarantor($guarantorId=''){
        if($guarantorId){
            $guarantorId = (int) $guarantorId;
            $model = M("invest_guarantor");
            return $model->where(array("id"=>$guarantorId))->find();
        }else{
            return M("invest_guarantor")->select();
        }
    }

    /**
     * 获取保障措施
     */
    function getAddCredit($addcreditIds=''){
        $model = M("pro_addcredit");
    	if(!$addcreditIds) {
            $rows = $model->select();
            $ret = array();
            if($rows){
                foreach ($rows as $row) {
                    $ret[$row['id']] = $row;
                }
            }
            return $ret;
        }


        // 目前担保措施Id为单个Id
        if (is_numeric($addcreditIds)) {
            return $model->where(array("id" => $addcreditIds))->find();
        }

        $rows = $model->where(array("id" => array("in", $addcreditIds)))->select();
        $ret = array();
        if ($rows) {
            foreach ($rows as $row) {
                $ret[$row['id']] = $row;
            }
        }
        return $ret;
    }


    /**
     * 获取项目信息
     * @param $prjId
     * @param bool $is_mi_no
     * @return bool|mixed
     */
    function getPrjInfo($prjId,$is_mi_no=false)
    {
        $prjId = (int)$prjId;

//     	$cache_key = "if_getPrjInfo_".$prjId;
//     	$result_info = cache($cache_key);
//     	if($result_info) return $result_info;

        $info = $this->getDataById($prjId,$is_mi_no);
        if (!$info) return false;
        $ext = $this->getExt($prjId,$is_mi_no);
        $info['ext'] = $ext;
        $info['act_prj_ext'] = M('prj_ext')->find($prjId);

        // 融资方信息
        $borrower = array();
        switch ($info['borrower_type']) {
            case PrjModel::BORROWER_TYPE_PERSONAL:
                $borrower = M('PrjPerson')->where(array('prj_id' => $prjId))->find();
                break;

            case PrjModel::BORROWER_TYPE_COMPANY:
                $borrower = M('PrjCorp')->where(array('prj_id' => $prjId))->find();
                break;
        }
        $info['borrower'] = $borrower;
        $info['old_end_bid_time'] = $this->getPrjEndBidTimeOld($prjId); // 原始截标时间
        cache('prj_info_'.$prjId, $info, array('expire' => 600));
        return $info;
    }

    function getExt($prjId,$is_mi_no)
    {
        $prjId = (int)$prjId;
        $info = $this->getDataById($prjId,$is_mi_no);
        if (!$info) return false;
        $ext = array();
        $prjType = $info['prj_type'];
        switch ($prjType){
            case PrjModel::PRJ_TYPE_A:$ext = $this->getInvestExt($prjId,$info);break;//投资借贷扩展表
            case PrjModel::PRJ_TYPE_B:$ext = $this->getLongInvestExt($prjId,$info);break;//长期借贷扩展表
            case PrjModel::PRJ_TYPE_C:$ext = $this->getFundExt($prjId,$info);break;//基金扩展表
            case PrjModel::PRJ_TYPE_D:$ext = $this->getHouseGuarantee($prjId,$info);break;//获取抵押扩展表
            case PrjModel::PRJ_TYPE_F:$ext = $this->getJuyou($prjId,$info);break;//聚优宝--->月益升
            case PrjModel::PRJ_TYPE_H:$ext = $this->getSuduitong($info);break;//速兑通
            case PrjModel::PRJ_TYPE_G:$ext = $this->getLiCai($prjId, $info);break;//鑫湖理财
            case PrjModel::PRJ_TYPE_I:$ext = $this->getPiaoju($prjId, $info);break;//鑫银票
        }
        //富二代的情况
        if (!$ext) {
            if ($info['p_prj_id']) $ext = $this->getExt($info['p_prj_id'],$is_mi_no);
        }
        return $ext;
    }

    function getExtTableName($prjId, $info)
    {
        $prjId = (int)$prjId;
        if(!$info) $info = $this->getDataById($prjId);
        if(!$info) return false;
        $ext = array();
        $prjType = $info['prj_type'];
        switch ($prjType) {
            case PrjModel::PRJ_TYPE_A:
                $ext = "invest_prj";
                break; //投资借贷扩展表
            case PrjModel::PRJ_TYPE_B:
                $ext = "invest_long_prj";
                break; //长期借贷扩展表
            case PrjModel::PRJ_TYPE_C:
                $ext = "manage_fund";
                break; //基金扩展表
            case PrjModel::PRJ_TYPE_D:
                $ext = "house_guarantee";
                break; //获取抵押扩展表
            case PrjModel::PRJ_TYPE_F:
                $ext = "invest_juyou_prj";
                break; //聚优宝--->月益升
        }
        return $ext;
    }

    //获取类型名称
    function getPrjTypeName($prjType)
    {
        $title = "";
        if ($prjType == PrjModel::PRJ_TYPE_A) {
            $title = "日益升";
        } elseif ($prjType == PrjModel::PRJ_TYPE_C) {
            $title = "稳益保";
        } elseif ($prjType == PrjModel::PRJ_TYPE_B) {
            $title = "年益升";
        } elseif ($prjType == PrjModel::PRJ_TYPE_D) {
            $title = "抵益融";
        } elseif ($prjType == PrjModel::PRJ_TYPE_E) {
            $title = "快益转";
        } elseif ($prjType == PrjModel::PRJ_TYPE_F) {
            $title = "月益升";
        }elseif ($prjType == PrjModel::PRJ_TYPE_H) {
            $title = "速兑通";
        }elseif ($prjType == PrjModel::PRJ_TYPE_G) {
            $title = "鑫湖理财";
        }elseif ($prjType == PrjModel::PRJ_TYPE_I) {
            $title = "鑫银票";
        }
        return $title;
    }

    //获取类型名称
    function getPrjOldTypeName($prjType)
    {
        $title = "";
        if ($prjType == PrjModel::PRJ_TYPE_A) {
            $title = "日益升";
        } elseif ($prjType == PrjModel::PRJ_TYPE_C) {
            $title = "稳益保";
        } elseif ($prjType == PrjModel::PRJ_TYPE_B) {
            $title = "企益升";
        } elseif ($prjType == PrjModel::PRJ_TYPE_D) {
            $title = "抵益融";
        } elseif ($prjType == PrjModel::PRJ_TYPE_F) {
            $title = "月益升";
        }
        return $title;
    }

    //更新支付时间
    function updateActualPayTime($prjId, $prjType)
    {
        if ($prjType == PrjModel::PRJ_TYPE_A) {
            M("invest_prj")->where(array("prj_id" => $prjId))->save(array("actual_pay_time" => time()));
        } elseif ($prjType == PrjModel::PRJ_TYPE_B) {
            M("invest_long_prj")->where(array("prj_id" => $prjId))->save(array("actual_pay_time" => time()));
        } elseif ($prjType == PrjModel::PRJ_TYPE_D) {
            M("house_guarantee")->where(array("prj_id" => $prjId))->save(array("actual_pay_time" => time()));
        } elseif ($prjType == PrjModel::PRJ_TYPE_F) {
            M("invest_juyou_prj")->where(array("prj_id" => $prjId))->save(array("actual_pay_time" => time()));
        }
    }

    //提前还款天数
    function getDiffData($prjType)
    {
        $diff = 0;
        $dayNumber = 0;
        if ($prjType == PrjModel::PRJ_TYPE_A) {
            $diff = 259200; //3天
            $dayNumber = 3;
        } elseif ($prjType == PrjModel::PRJ_TYPE_C) {
            $diff = 604800; //7天
            $dayNumber = 7;
        } elseif ($prjType == PrjModel::PRJ_TYPE_B) {
            $diff = 432000; //5天
            $dayNumber = 5;
        } elseif ($prjType == PrjModel::PRJ_TYPE_D) {
            $diff = 432000; //5天
            $dayNumber = 5;
        } else {
            $diff = 432000; //5天
            $dayNumber = 5;
        }
        return array($diff, $dayNumber);
    }

	function getMultiSpicalPrjInfo($prj_ids){
		$result = array();

		//$ids = array_keys($prj_ids, PrjModel::BUSINESS_TYPE_SMZ);
		//$infos = D("Financing/InvestSMZ")->getMultiInfosByPrjId($ids);
		//$result = $infos ? array_merge($result, $infos) : $result;

		//投资借贷扩展表
		$ids = array_keys($prj_ids, PrjModel::PRJ_TYPE_A);
		$infos = D("Financing/InvestPrj")->getMultiInfosByPrjId($ids);
		$result = $infos ? array_merge($result, $infos) : $result;

		//长期借贷扩展表
		$ids = array_keys($prj_ids, PrjModel::PRJ_TYPE_B);
		$infos = D("Financing/InvestLongPrj")->getMultiInfosByPrjId($ids);
		$result = $infos ? array_merge($result, $infos) : $result;

		//基金扩展表
		$ids = array_keys($prj_ids, PrjModel::PRJ_TYPE_C);
		$infos = D("Financing/ManageFund")->getMultiInfosByPrjId($ids);
		$result = $infos ? array_merge($result, $infos) : $result;

		//获取抵押扩展表
		$ids = array_keys($prj_ids, PrjModel::PRJ_TYPE_D);
		$infos = D("Financing/HouseGuarantee")->getMultiInfosByPrjId($ids);
		$result = $infos ? array_merge($result, $infos) : $result;

		//聚优宝--->月益升
		$ids = array_keys($prj_ids, PrjModel::PRJ_TYPE_F);
		$infos = D("Financing/InvestJuyou")->getMultiInfosByPrjId($ids);
		$result = $infos ? array_merge($result, $infos) : $result;

		//速兑通
		$ids = array_keys($prj_ids, PrjModel::PRJ_TYPE_H);
        foreach($ids as $id){
            $result[$id] = array(
                'value_date'=>0,
            );
        }

		//鑫湖理财
		$ids = array_keys($prj_ids, PrjModel::PRJ_TYPE_G);
		$infos = D("Financing/InvestFund")->getMultiInfosByPrjId($ids);
		$result = $infos ? array_merge($result, $infos) : $result;

		//鑫银票
		$ids = array_keys($prj_ids, PrjModel::PRJ_TYPE_I);
		$infos = D("Financing/InvestBillPrj")->getMultiInfosByPrjId($ids);
		$result = $infos ? array_merge($result, $infos) : $result;

		$result = array_column($result, null, 'prj_id');

		return $result;
	}

    /**
     * 获取抵押
     * @param  [type] $prjId [description]
     * @return [type]        [description]
     */
    function getHouseGuarantee($prjId,$prjInfo=""){
        $prjId = (int) $prjId;
        $model = M("house_guarantee");
        $result = $model->where(array("prj_id" => $prjId))->find();
//         echo $model->getLastSql();
        $result['fund_account']  = $this->getFundAccount($result['fund_account'],$result['uid'],$prjInfo);
        $result['fund_account_id'] = $result['fund_account']['id'];
        return $result;
    }

    /**
     * 投资借贷
     * @param unknown $prjId
     */
    function getInvestExt($prjId,$prjInfo=""){
        $prjId = (int) $prjId;
        $model = M("invest_prj");
        $result = $model->where(array("prj_id" => $prjId))->find();
//         echo $model->getLastSql();
//        $result['product'] = $this->getProductById($result['products_id']);
        $result['fund_account']  = $this->getFundAccount($result['fund_account'],$result['uid'],$prjInfo);
        $result['fund_account_id'] = $result['fund_account']['id'];
        return $result;
    }

    /**
     * 基金理财
     * @param unknown $prjId
     */
    function getFundExt($prjId,$prjInfo=""){
        $prjId = (int) $prjId;
        $model = M("manage_fund");
        $result = $model->where(array("prj_id" => $prjId))->find();
        $result['fund_account'] = $this->getFundAccount($result['fund_account'],$result['uid'],$prjInfo="");
        $result['fund_account_id'] = $result['fund_account']['id'];

        // 解析附件路径
        $mdUpload = D('Public/Upload');
        $info = $mdUpload->parseFileParam($result['attach']);
        $info = $info['attach'];
        if ($info['id']) {
            $result['attach_url'] = $info['url'];
            $result['attach_download'] = $info['download'];
            // 非图片attach_url替换未下载地址
            if (!in_array($info['extension'], array('png', 'jpg', 'jpeg', 'bmp', 'gif'))) {
                $result['attach_url'] = $result['attach_download'];
            }
        }
        // 第一个有效企业合伙企业（供项目修改的时候显示使用）
        $result['partner_first'] = D('Financing/PrjPartner')->getPartner($prjId);

        return $result;
    }

    /**
     * 鑫湖理财
     * @param int $prjId
     */
    function getLiCai($prjId,$prjInfo=""){
        $prjId = (int) $prjId;
        $model = M("invest_fund");
        $result = $model->where(array("prj_id" => $prjId))->find();

        $result['fund_account'] = $this->getFundAccount($result['loanbank_account_id'],$result['cuid'],$prjInfo);
        $result['fund_account_id'] = $result['fund_account']['id'];
        return $result;
    }

    /**
     * 长期借贷理财
     * @param unknown $prjId
     */
    function getLongInvestExt($prjId,$prjInfo=""){
        $prjId = (int) $prjId;
        $model = M("invest_long_prj");
        $result = $model->where(array("prj_id" => $prjId))->find();
        //         echo $model->getLastSql();
        $result['fund_account'] = $this->getFundAccount($result['fund_account'],$result['uid'],$prjInfo);
        $result['fund_account_id'] = $result['fund_account']['id'];
        return $result;
    }

    /**
     * 2.    聚优宝--->月益升
     * @param unknown $prjId
     */
    function getJuyou($prjId,$prjInfo=''){
    	$prjId = (int) $prjId;
    	$model = M("invest_juyou_prj");
    	$result = $model->where(array("prj_id"=>$prjId))->find();
    	//         echo $model->getLastSql();
    	$result['fund_account'] = $this->getFundAccount($result['fund_account'],$result['uid'],$prjInfo);
    	$result['fund_account_id'] = $result['fund_account']['id'];
    	return $result;
    }

    function getPiaoju($prjId,$prjInfo=''){
        $prjId = (int) $prjId;
        $model = M("invest_bill_prj");
        $result = $model->where(array("prj_id"=>$prjId))->find();
        $result['fund_account'] = $this->getFundAccount($result['fund_account'],$result['uid'],$prjInfo);
        $result['fund_account_id'] = $result['fund_account']['id'];
        return $result;
    }


    public function getSuduitong($prj_info)
    {
        $day = day_diff(time(),$prj_info['end_bid_time']);
        return array(
            'value_date'=>0,
            'is_transfer'=>1,//因为都已经是速兑通项目了。所以一定能变现
        );
    }

    /**
     * 适用产品
     */
    function getProductById($productId)
    {
        $productId = (int)$productId;
        $model = M("product");
        return $model->where(array("id" => $productId))->find();
    }

    /**
     * 获取最新开标产品时间
     * 目前只支持PC端
     */
    function getNewBidOpenTime($c){
        $c = (int) $c;
        $loginedUserInfo = service("Account/Account")->getLoginedUserInfo();
        $now = time();
        $model = M("prj");
        import_app("Financing.Model.PrjModel");

        $defaultWhere=array();
        $defaultWhere['status']= PrjModel::STATUS_PASS;
        $defaultWhere['bid_status'] = PrjModel::BSTATUS_WATING;

        //PC 项目专享
        // $defaultWhere['b.client_type'] = PrjFilterModel::CLIENT_TYPE_PC;
        $defaultWhere['b.client_type'] = array(array('eq',PrjFilterModel::CLIENT_TYPE_PC),array('exp',' is NULL'),'or');

        //发布站点  非全部和中新力合的项目排除
        $defaultWhere['mi_no'] = array(array('eq',C('MI_NO_INFO.api_key_all')),array('eq',BaseModel::getApiKey('api_key')),'or');

        $loginedUserInfo = M("user")->find($loginedUserInfo['uid']);

        $is_newbie = $loginedUserInfo['is_newbie'];

        $xin = 0;
        if(!$loginedUserInfo || $is_newbie){
            $xin = 1;
        }
        $result=array();
        $join = "left join fi_prj_filter b on fi_prj.id=b.prj_id ";

        if($c==1){//所有系列
            if($xin){
                $where = array();
                $where['start_bid_time'] = array("EGT",$now);
                $where = array_merge($defaultWhere,$where);
                $result =  $model->join($join)->field('start_bid_time,prj_type,is_new')->where($where)->order(' start_bid_time ASC ')->find();
            }else{
                $where = array();
                $where['start_bid_time'] = array("EGT",$now);
                $where['is_new'] = 0;
                $where = array_merge($defaultWhere,$where);
                $result =  $model->join($join)->field('start_bid_time,prj_type,is_new')->where($where)->order(' start_bid_time ASC ')->find();
                //var_dump($model->getLastSql());
            }
        }elseif($c==2){//日益升
            if($xin){
                $where = array();
                $where['start_bid_time'] = array("EGT",$now);
                $where['prj_type'] = PrjModel::PRJ_TYPE_A;
                $where = array_merge($defaultWhere,$where);
                $result =  $model->join($join)->field('start_bid_time,prj_type,is_new')->where($where)->join($join)->order(' start_bid_time ASC ')->find();
                if(!$result){
                    $where = array();
                    $where['start_bid_time'] = array("EGT",$now);
                    $where = array_merge($defaultWhere,$where);
                    $result =  $model->join($join)->field('start_bid_time,prj_type,is_new')->where($where)->order(' start_bid_time ASC ')->find();
                }
            }else{
                $where = array();
                $where['start_bid_time'] = array("EGT",$now);
                $where['prj_type'] = PrjModel::PRJ_TYPE_A;
                $where['is_new'] = 0;
                $where = array_merge($defaultWhere,$where);
                $result =  $model->join($join)->field('start_bid_time,prj_type,is_new')->where($where)->order(' start_bid_time ASC ')->find();
                if(!$result){
                    $where = array();
                    $where['start_bid_time'] = array("EGT",$now);
                    $where['is_new'] = 0;
                    $where = array_merge($defaultWhere,$where);
                    $result =  $model->join($join)->field('start_bid_time,prj_type,is_new')->where($where)->order(' start_bid_time ASC ')->find();
                }
            }
        }elseif($c==3){//聚优宝--->月益升

            if($xin){
                $where = array();
                $where['start_bid_time'] = array("EGT",$now);
                $where['prj_type'] = PrjModel::PRJ_TYPE_F;
                $where = array_merge($defaultWhere,$where);
                $result = $model->join($join)->field('start_bid_time,prj_type,is_new')->where($where)->order(' start_bid_time ASC ')->find();
                if(!$result){
                    $where = array();
                    $where['start_bid_time'] = array("EGT",$now);
                    $where = array_merge($defaultWhere,$where);
                    $result =  $model->join($join)->field('start_bid_time,prj_type,is_new')->where($where)->order(' start_bid_time ASC ')->find();
                }
            }else{
                $where = array();
                $where['start_bid_time'] = array("EGT",$now);
                $where['prj_type'] = PrjModel::PRJ_TYPE_F;
                $where['is_new'] = 0;
                $where = array_merge($defaultWhere,$where);
                $result =  $model->join($join)->field('start_bid_time,prj_type,is_new')->where($where)->order(' start_bid_time ASC ')->find();
                if(!$result){
                    $where = array();
                    $where['start_bid_time'] = array("EGT",$now);
                    $where['is_new'] = 0;
                    $where = array_merge($defaultWhere,$where);
                    $result =  $model->join($join)->field('start_bid_time,prj_type,is_new')->where($where)->order(' start_bid_time ASC ')->find();
                }
            }
        }elseif($c==4){//年益升

            if($xin){
                $where = array();
                $where['start_bid_time'] = array("EGT",$now);
                $where['prj_type'] = PrjModel::PRJ_TYPE_B;
                $where = array_merge($defaultWhere,$where);
                $result =  $model->join($join)->field('start_bid_time,prj_type,is_new')->where($where)->order(' start_bid_time ASC ')->find();
                if(!$result){
                    $where = array();
                    $where['start_bid_time'] = array("EGT",$now);
                    $where = array_merge($defaultWhere,$where);
                    $result =  $model->join($join)->field('start_bid_time,prj_type,is_new')->where($where)->order(' start_bid_time ASC ')->find();
                }
            }else{
                $where = array();
                $where['start_bid_time'] = array("EGT",$now);
                $where['prj_type'] = PrjModel::PRJ_TYPE_B;
                $where['is_new'] = 0;
                $where = array_merge($defaultWhere,$where);
                $result = $model->join($join)->field('start_bid_time,prj_type,is_new')->where($where)->order(' start_bid_time ASC ')->find();
                if(!$result){
                    $where = array();
                    $where['start_bid_time'] = array("EGT",$now);
                    $where['is_new'] = 0;
                    $where = array_merge($defaultWhere,$where);
                    $result =  $model->join($join)->field('start_bid_time,prj_type,is_new')->where($where)->order(' start_bid_time ASC ')->find();
                }
            }
        }elseif($c==5){//新客
            $where = array();
            $where['start_bid_time'] = array("EGT",$now);
            $where = array_merge($defaultWhere,$where);
            $result =  $model->join($join)->field('start_bid_time,prj_type,is_new')->where($where)->order(' start_bid_time ASC ')->find();
        }
//        var_dump($model->_sql());
        if($result){
            $diff = $result['start_bid_time']-$now;
            $diff = $diff > 0 ? $diff: 0;
            $prjType = $this->getPrjTypeName($result['prj_type']);
            if($result['is_new']) $prjType = $prjType."-新客";
            return array($diff,$prjType,$result['is_new']);
        }else{
            return array(0,'','');
        }

    }

    //获取时间差
    function getDiffTime($time)
    {
        $diff = $time - time();
        return $diff > 0 ? $diff : 0;
    }

    /**
     * 格式化开标时间
     * @param unknown $time
     */
    function formatNewBidOpenTime($time)
    {
        //小时
        $day = (int)floor($time / (86400));
        $time = $time - $day * 86400;
        $hour = (int)floor($time / (3600));
        $time = $time - $hour * 3600;
        $minute = (int)floor($time / (60));
        $second = (int)($time - $minute * 60);
        $str = "";
        if ($day) $str .= "<em>{$day}</em>天";
        $str .= "<em>{$hour}</em>时<em>{$minute}</em>分<em>{$second}</em>秒";
        return $str;
    }

    function formatTime($time)
    {
        if ($time < 0) return 0;
        return $time;
    }

    /**
     * 融资进度计算
     * @param unknown $demandAmount 总金额
     * @param unknown $remainingAmount 剩余金额
     */
    function getSchedule($demandAmount, $remainingAmount)
    {
        if(!$demandAmount) return '0';

        $tmp = (1 - ($remainingAmount / $demandAmount)) * 100;
        $tmp = number_format($tmp, 0);
        if ($tmp == 100) {
            if ($remainingAmount > 0) return "99";
        }
        return "{$tmp}";
    }

    function getPrjTypeInt($prjType)
    {
        $c = 1;
        switch ($prjType) {
            case PrjModel::PRJ_TYPE_A:
                $c = 2;
                break;
            case PrjModel::PRJ_TYPE_B:
                $c = 4;
                break;
            case PrjModel::PRJ_TYPE_F:
                $c = 3;
                break;
            case PrjModel::PRJ_TYPE_G:
                $c = 5;
                break;
            case PrjModel::PRJ_TYPE_H:
                $c = 6;
                break;
            default;
                $c = 1;
                break;
        }
        return $c;
    }


    /**
     * 获取绑定账户信息
     * @param int $id 账户id
     * @return int|mixed    如果查不到结果返回原账户Id，否则返回账户数组
     */
    public function getFundAccount($id=0,$uid,$prjInfo="") {
        if($prjInfo && $prjInfo['zhr_apply_id']){
            return M("loanbank_account")->where(array('id' => $id))->find();
        }
        $condition = array(
        	'uid' => $uid,
        	'id' => $id,
        );
        if($return = M('loanbank_account')->where($condition)->find()){
        	return $return;
        }
        return M('fund_account')->where(array('id' => $id))->find();
    }

    //获取年利率最高
    function getTopYearRate($where, $limit = 0)
    {
        $where['bid_status'] = PrjModel::BSTATUS_BIDING;
        $model = M("prj");
        if ($limit) $model = $model->page("1," . $limit);
        $info = $model->where($where)->order(array("year_rate" => "DESC"))->find();
        if (!$info) return 0;
        return $info['year_rate'];
    }

    function getLowYearRate($where, $limit = 0)
    {
        $where['bid_status'] = PrjModel::BSTATUS_BIDING;
        $model = M("prj");
        if ($limit) $model = $model->page("1," . $limit);
        $info = $model->where($where)->order(array("year_rate" => "ASC"))->find();
        if (!$info) return 0;
        return $info['year_rate'];
    }

    /**
     * 获取还款  对象
     * @param int $prjId
     * @param string $taiStr
     * @return boolean|object
     */
    function getTradeObj($prjId, $taiStr)
    {
        if (!$prjId) {
            return errorReturn("prj_id不存在");
        }

        $repay_way = M("prj")->where(array("id" => $prjId))->getField('repay_way');
        $prj_is_deposit = $this->projectIsDeposit($prjId);

        $classPre = ucfirst($repay_way);
        $className = $classPre . "_" . $taiStr;

        if ($taiStr == 'Repayment') {
            if ($prj_is_deposit) {
                $className = $classPre . "_" . $taiStr . "_ZS";
            } elseif (C('IS_NEW_REPAYMENT') == 1) {
                $className = $classPre . "_" . $taiStr . "_N";
            }
        }

        $path = ADDON_PATH . "/trade/" . $classPre . "/" . $className . ".class.php";
        if (!is_file($path)) {
            return errorReturn($path . $prjId . "未支持的还款方式");
        }
        import_addon("trade." . $classPre . "." . $className);

        return new $className;
    }

    /**
     * 债权转让还款
     * @param unknown $prjId
     * @param unknown $taiStr
     * @return boolean|unknown
     */
    function getTsTradeObj($prjId, $taiStr){
        if (!$prjId) return errorReturn("prj_id不存在");

        $prjInnfo = M("prj")->where(array("id" => $prjId))->find();

        $repay_way = $prjInnfo['repay_way'];
        $classPre = ucfirst($repay_way);
        if($taiStr=='Repayment' && C('IS_NEW_REPAYMENT') == 1) $className = $classPre . "_" . $taiStr . "_NO";
        else $className = $classPre . "_" . $taiStr;
        $path = ADDON_PATH . "/trade/" . $classPre . "/" . $className . ".class.php";
        if (!is_file($path)){
            throw_exception($path.$prjId."未支持的还款方式");
            return errorReturn($path.$prjId."未支持的还款方式");
        }
        import_addon("trade." . $classPre . "." . $className);

        return new $className;
    }

    //获取最初订单信息
    function getParentOrderId($orderId)
    {
        $from_order_id = M("prj_order")->where(array("id" => $orderId, "from_order_id" => array("EXP", "IS NOT NULL")))->getField("from_order_id");
        if (!$from_order_id) {
            return $orderId;
        } else {
            return $this->getParentOrderId($from_order_id);
        }
    }

    //获取项目商户账户id
    public function getTenantIdByPrj($prj){
    	if($prj['tenant_id']) return $prj['tenant_id'];
    	else {
//     		$merchant_id = service("Financing/Project")->getTenantIdByPrj($prj);
    		 $merchant_id = service('Payment/PayAccount')->getTenantAccountId($prj['dept_id']);
    		 return $merchant_id;
    	}
    }


    /**
     * @deprecated
     * 自动设置结标时间(暂时废弃)
     */
    function autoCreateEndBidTime($prjId){
        $prjInfo = M("prj")->where(array("id"=>$prjId))->find();
        if(!$prjInfo['is_auto_end_bid_time']) return true;
        $endTime = $this->getAutoEndBidTime();
        M("prj")->where(array("id"=>$prjId))->save(array("end_bid_time"=>$endTime));

        $last_repay_date = $this->getPrjEndTime($prjId,$prjInfo);
        // 下次还款日
        if($prjInfo['repay_way'] == PrjModel::REPAY_WAY_ENDATE || (!service("Financing/Invest")->isShowPlan($prjId))) {
            $next_repay_date = $last_repay_date;
        }else{
            $next_repay_date = strtotime('+1 day', strtotime($prjInfo['end_bid_time']));
        }
        $udata=array();
        $udata['last_repay_date'] = $last_repay_date;
        $udata['next_repay_date']=$next_repay_date;
        $udata['repay_time']=$last_repay_date;
        $udata['mtime']=time();
        M("prj")->where(array("id"=>$prjId))->save($udata);

        //
        $odata=array();
        $odata['expect_repay_time'] = $next_repay_date;
        $odata['mtime']=time();
        M("prj_order")->where(array("prj_id"=>$prjId))->save($odata);

        $this->toCreateOrderPlan($prjId);

        if(MyError::hasError()){
            return errorReturn(MyError::lastError());
        }

        return true;
    }

    function toCreateOrderPlan($prjId){
        $list = M("prj_order")->where(array("prj_id"=>$prjId,"status"=>PrjOrderModel::STATUS_FREEZE,"is_regist"=>0))->select();
        if(!$list) return true;
        //生成还款计划
        $obj = $this->getTradeObj($prjId, "Bid");
        if(!$obj) return errorReturn(MyError::lastError());
        foreach($list  as $orderInfo){
            $obj->savePersonRepaymentJob(array("order_id"=>$orderInfo['id']));
            if(MyError::hasError()){
                return errorReturn(MyError::lastError());
            }
        }
        return true;
    }

    /**
     * @deprecated
     * 获取自动结标时间(暂时废弃)
     */
    function getAutoEndBidTime(){
        return time();
    }


    function setPrjNameStyle($prjInfo,$isFed){
        $prjTypeName = $this->getPrjTypeName($prjInfo['prj_type']);
        $str = $prjTypeName."-";
//        if(($prjInfo['huodong'] ==1 || $prjInfo['activity_id']) && (!$isFed)){
//            $str .="<span class=\"org\">".$prjInfo['prj_name']."</span>";
//        }else{
            $str .= "<em>".$prjInfo['prj_name']."</em>";
//        }
        return $str;
    }


    /**
     * 是否可以提前截标
     *
     * @param $prj_id
     * @return bool
     */
    public function isPrjCanEarlyClose($prj_id) {
        return !!M('prj_ext')->where(array('prj_id' => $prj_id))->getField('is_early_close');
    }


    /**
     * 是否可提前还款
     *
     * @param $prj_id
     * @return bool
     */
    public function isPrjCanEarlyRepay($prj_id) {
        return !!M('prj_ext')->where(array('prj_id' => $prj_id))->getField('is_early_repay');
    }


    /**
     * 判断prj版本是否符合还款计划支付时生成
     *
     * @param $prj_version
     * @param bool $is_prj_id
     * @return bool
     */
    public function isPrjVersionRepayPlanDelay($prj_version, $is_prj_id=FALSE) {
        if($is_prj_id) $prj_version = M('prj')->where(array('id' => $prj_version))->getField('version');
        return $prj_version >= 2;
    }


    /**
     * 同步利息等字段到order里
     *
     * @param $order_id
     * @param array $input
     * @return bool
     */
    public function syncOrderData($order_id, $input=array()) {
        $keys = array(
            'rate',
            'rate_type',
            'year_rate',
            'time_limit',
            'time_limit_unit',
            'time_limit_day',
        );
        $data = array();
        foreach($keys as $key) {
            if($input[$key]) $data[$key] = $input[$key];
        }
        if(empty($data)) return TRUE;

        $mdOrder = M('prj_order');
        if(FALSE === $mdOrder->where(array('id' => $order_id))->save($data)) throw_exception('同步表单利率数据出错');
        return TRUE;
    }


    /**
     * 检测订单是否去全部生成还款计划
     *
     * @param $prj_id
     * @return bool
     */
    public function isOrderRepayPlanAllCreated($prj_id) {
        $mdOrder = D('Financing/PrjOrder');
        $where = array(
            'prj_id' => $prj_id,
            'status' => array('NEQ', PrjOrderModel::STATUS_NOT_PAY),
            'is_regist' => 0,
            'is_have_repayplan' => 0,
        );
        $res = $mdOrder->field('id')->where($where)->find();

        return !$res;
    }


    /**
     * 检测项目还款计划是否生成完毕
     *
     * @param $prj_id
     * @return bool
     */
    public function isPrjRepayPlanCreated($prj_id) {
        $mdOrder = D('Financing/PrjOrder');
        $mdPrjRepayPlan = D('Financing/PrjRepayPlan');

        $where = array(
            'prj_id' => $prj_id,
            'status' => array('NEQ', PrjOrderModel::STATUS_NOT_PAY),
            'is_regist' => 0,
        );
        $result = $mdOrder->where($where)->field('SUM(money) AS TOTAL')->find();
        $sum_order_money = (int)$result['TOTAL'];
        if($sum_order_money <= 0) return FALSE;

        $where = array(
            'prj_id' => $prj_id,
            'status' => array('NEQ', PrjRepayPlanModel::PRJ_REPAYMENT_STATUS_FAIL),
        );
        $result = $mdPrjRepayPlan->where($where)->field('SUM(principal) AS TOTAL')->find();
        $sum_prj_repay_plan = (int)$result['TOTAL'];

        return $sum_prj_repay_plan >= $sum_order_money;
    }


    /**
     * 检测是否已生成过还款计划
     * 用于判断失败等
     *
     * @param $prj_id
     * @return bool
     */
    public function isOrderRepayPlanStartCreate($prj_id) {
        $mdOrder = D('Financing/PrjOrder');
        $where = array(
            'prj_id' => $prj_id,
            'status' => array('NEQ', PrjOrderModel::STATUS_NOT_PAY),
            'is_regist' => 0,
            'is_have_repayplan' => 1,
        );
        return !!$mdOrder->field('id')->where($where)->find();
    }


    public function createOrderRepayPlanCache($prj_id, $value=FALSE) {
        $key = 'doEarlyEndBidCache_' . $prj_id;
        if($value !== FALSE) {
            S($key, $value, 108000); // 30分钟
            return TRUE;
        }

        return S($key);
    }


    /**
     * 生成所有订单还款计划
     * @param $prj_id
     * @return bool
     */
    public function createOrderRepayPlan($prj_id)
    {
        if (!$this->isPrjVersionRepayPlanDelay($prj_id, false)) {
            throw_exception('老项目不支持');
        }
        if ($this->createOrderRepayPlanCache($prj_id)) {
            throw_exception('CREATING...');
        }
        $this->createOrderRepayPlanCache($prj_id, 1);

        $mdPrj = D('Financing/Prj');
        $mdOrder = M('prj_order');
        $project = $mdPrj->where(array('id' => $prj_id))->find();
        if (!$project) {
            throw_exception('项目不存在');
        }
        if ($project['bid_status'] < PrjModel::BSTATUS_FULL) {
            throw_exception('项目未满标');
        }
        if ($this->isOrderRepayPlanAllCreated($prj_id)) {
            throw_exception('所有还款计划已生成过了');
        }

        $is_early_close = $this->isPrjCanEarlyClose($prj_id);
        if (!$is_early_close && !in_array($project['bid_status'],
                array(PrjModel::BSTATUS_FULL, PrjModel::BSTATUS_END))
        ) {
            throw_exception('该项目不能提前截标');
        }

        // 失败重新生成的, 尝试作废之前的
        if ($this->isOrderRepayPlanStartCreate($prj_id)) {
            if (false === M('prj_order_repay_plan')->where(array('prj_id' => $prj_id))->save(array('status' => 3))) {
                throw_exception('作废之前的还款计划失败');
            }
        }

        // 设置截标时间
        if ($project['end_bid_time'] > time() && $project['prj_class'] != PrjModel::PRJ_CLASS_LC) {
            if ($is_early_close) {
                $mdPrj->setNewEndBidTime($prj_id);
            }
        }

        $where_order = array(
            'prj_id' => $prj_id,
            'is_regist' => 0,
        );
        // 生成订单还款计划
        ($project['prj_class'] != PrjModel::PRJ_CLASS_LC && $where_order['status'] = PrjOrderModel::STATUS_FREEZE)
        || $where_order['status'] = array('in', array(PrjOrderModel::STATUS_PAY_SUCCESS, PrjOrderModel::STATUS_FREEZE));

        $orders = $mdOrder->where($where_order)->select();

        if (!$orders) {
            return true;
        }
        $oBid = $this->getTradeObj($prj_id, 'Bid');
        if (!$oBid) {
            throw_exception(MyError::lastError());
        }

        $oQueueTradeJob = $this->getTradeObj($prj_id, 'QueueTradeJob');
        if (!$oQueueTradeJob) {
            throw_exception(MyError::lastError());
        }

        if (count($orders)) {
            $oQueueTradeJob->beforeCreatePrjPlan($prj_id, count($orders));
        }
        foreach ($orders as $order) {
            $params = array(
                'freeze_time' => $order['freeze_time'],
                'rest_money' => $order['rest_money'],
                'prj_id' => $order['prj_id'],
                'fromOrderId' => $order['id'],
                'lastPeriods' => -1,
            );
            $oQueueTradeJob->doCreateOrderPlanJob($params);
            if (MyError::hasError()) {
                throw_exception(MyError::lastError());
            }
        }

        return true;
    }

    /**
     * @param $prj_id
     * @param $params
     * @return mixed
     */
    public function createOrderRepayPlanTask($prj_id, $params)
    {
        $oQueueTradeJob = $this->getTradeObj($prj_id, 'QueueTradeJob');
        if (!$oQueueTradeJob) {
            throw_exception(MyError::lastError());
        }
        return $oQueueTradeJob->doCreateOrderPlanJobTask($params);
    }


    // 获取项目原本的截标时间
    public function getPrjEndBidTimeOld($prj_id) {
        $end_bid_time = M('prj_ext')->where(array('prj_id' => $prj_id))->getField('old_end_bid_time');
        if(!$end_bid_time) $end_bid_time = M('prj')->where(array('id' => $prj_id))->getField('end_bid_time');

        return $end_bid_time;
    }

    // 获取项目首次还款时间
    public function getPrjFirstRepayDate($prj_id, $project, $format='Y-m-d', $is_use_old=FALSE) {
        if(!$project) $project = M('prj')->where(array('id' => $prj_id))->find();
        if($is_use_old) {
            $end_bid_time = $project['old_end_bid_time'];
            if(!$end_bid_time) $end_bid_time = $this->getPrjEndBidTimeOld($prj_id);
        } else {
            $end_bid_time = $project['end_bid_time'];
        }

        $t = strtotime(date('Y-m-d', $project['end_bid_time']));
        $balance_time = $this->getBalanceTime($project['end_bid_time'], $prj_id);
        if ($project['end_bid_time'] > $balance_time) $t = strtotime('+1 days', $t); // T向后延迟一天

        if(!$this->isPrjVersionRepayPlanDelay($project['version'])) {
            $repay_time = strtotime('+1 day', $t);
        } else {
            if($project['repay_way'] == 'E') {
            	if($project['prj_type'] == PrjModel::PRJ_TYPE_G){
            		$repay_time = service('Financing/InvestFund')->getPrjFirstRepayDate($project);
            	}else{
                    $repay_time = strtotimeX("+{$project['time_limit']} {$project['time_limit_unit']}", $end_bid_time);
            	}
            } else {
                $freeze_time = $this->getPrjValueDate($prj_id, $t); // 计息日
                $freeze_date = date('Y-m-d', $freeze_time);
                if($project['repay_way'] == PrjModel::REPAY_WAY_PERMONTH) {
                    import('libs.RepayPlan.AvgCapInter', ADDON_PATH);
                    $o_repay_plan = new AvgCapInter();
                } elseif($project['repay_way'] == PrjModel::REPAY_WAY_D) {
                    import('libs.RepayPlan.MonthlyPay', ADDON_PATH);
                    $o_repay_plan = new MonthlyPay();
                }
                elseif($project['repay_way'] == PrjModel::REPAY_WAY_PERMONTHFIXN) {
                    import('libs.RepayPlan.AvgCapInterFixN', ADDON_PATH);
                    $o_repay_plan = new AvgCapInterFixN();
                    $prj_ext = M('prj_ext')->where(array('prj_id' => $prj_id))->find();
                    $fix_repay_day = (int)$prj_ext['fix_repay_day'];
                    $freeze_date = $o_repay_plan->getValueDate($fix_repay_day, $freeze_date);
                }
                elseif($project['repay_way'] == 'halfyear') {
                    import('libs.RepayPlan.HalfYear', ADDON_PATH);
                    $o_repay_plan = new HalfYear();
                }
                $repay_date = $o_repay_plan->getDateByI($freeze_date, 1);
                $repay_time = strtotime($repay_date);
            }
        }

        if(!$format) return $repay_time;
        return date($format, $repay_time);
    }

    // 拆标的所有兄弟标是否都已经满
    public function isAllSiblingsFull($prj_id) {
        $mdPrjSplid = D('Financing/PrjSplit');
        $mdPrj = D('Financing/Prj');
        $me = $mdPrjSplid->where(array('prj_id' => $prj_id))->find();
        if(!$me) {
            $project = $mdPrj->where(array('id' => $prj_id, 'status' => PrjModel::STATUS_PASS))->find();
            if($project && $project['bid_status'] >= PrjModel::BSTATUS_FULL) return TRUE;
            return -1;
        }

        $siblings = $mdPrjSplid->where(array('parent_prj_id' => $me['parent_prj_id']))->select();
        foreach ($siblings as $row) {
            if($row['status'] < PrjSplitModel::STATUS_PUBLISHED) return -2;
            $project = $mdPrj->where(array('id' => $row['prj_id'], 'status' => PrjModel::STATUS_PASS))->find();
            if(!$project || $project['bid_status'] < PrjModel::BSTATUS_FULL) return -3;
        }

        return TRUE;
    }


    /**
     * 获取外部渠道的截标时间
     *
     * @param $prj_id
     * @return int      UNIX时间戳, 如果不存在可能返回0
     */
    public function getPrjEndBidTimeChannel($prj_id) {
        return (int)M('prj_ext')->where(array('prj_id' => $prj_id))->getField('channel_end_bid_time');
    }


    /**
     * 获取项目起息时间
     *
     * @param $prj_id
     * @param null $project
     * @return int      UNIX时间戳
     */
    public function getProjectValueTime($prj_id, $project=NULL) {
        if(is_null($project)) $project = $this->getPrjInfo($prj_id);

        $t = strtotime(date('Y-m-d', $project['end_bid_time']));
        $balance_time = service('Financing/Project')->getBalanceTime($project['end_bid_time'],$project['id']);
        if ($project['end_bid_time'] > $balance_time) $t = strtotime('+1 days', $t); // T向后延迟一天

        $ret = $this->getPrjValueDate($prj_id, $t);
        return $ret;
    }

    /**
     * 项目是否有募集期利息
     *
     * @param $prj_id
     * @return bool
     */
    public function isPrjHaveBidingIncoming($prj_id) {
        $ret = M('prj_ext')->where(array('prj_id' => $prj_id))->getField('is_have_biding_incoming');
        if(FALSE === $ret) return TRUE; // 如果没找到扩展标就收募集期利息
        return !!$ret;
    }


    // 获取项目罚息比例
    public function getPrjDefaultRatio($prj_id) {
        return (float)M('prj_default_interest')->where(array('prj_id' => $prj_id))->getField('ratio');
    }

    /**
     * 检查用户是保理公司
     * @param $uid
     * @return bool
     */
    public function isBaoli($uid){
    	$uid = (int)$uid;
    	$sql = "select g.id from fi_guarantor_user u left join fi_invest_guarantor g ON u.guarantor_id=g.id where g.org_type in(2,91) and u.uid=".$uid;
    	$ret = M()->query($sql);
    	if($ret) return true;
    	return false;
    }

    /**
     * 获取出借人信息列表
     * 取得改项目下的所有的有效订单
     */
    public function getLenderListByPrjId($prj_id)
    {
        !$prj_id && throw_exception("请勿非法提交!");

        $prjModel = D("Financing/PrjOrder");
        try {
            $list = $prjModel->getLenderListByPrjId($prj_id);
        }
        catch (Exception $e) {
            throw_exception($e->getMessage());
        }

        return $list;
    }

    // 更新订单最后还款日
    public function setOrderLastRepayDate($order_id, $end_bid_time=NULL, $is_throw_exception=TRUE) {
        $mdOrder = M('prj_order');
        $mdPrj = M('prj');

        $order = $mdOrder->where(array('id' => $order_id))->find();
        if(!$order) return;
        if(!$end_bid_time) $end_bid_time = $mdPrj->where(array('id' => $order['prj_id']))->getField('end_bid_time');
        if(!$end_bid_time) return;

        $last_repay_time =strtotimeX("+{$order['time_limit']} {$order['time_limit_unit']}", $end_bid_time);
        $last_repay_date = strtotime(date('Y-m-d', $last_repay_time));
        $result = $mdOrder->where(array('id' => $order_id))->setField(array('last_repay_date' => $last_repay_date));
        if($is_throw_exception && FALSE === $result) throw_exception('更新订单最后还款日出错');
    }

    public function addOrderAfterCount($uid, $prjId, $fromOrderId=0){
        return D('Financing/PrjOrder')->addOrderAfterCount($uid, $prjId, $fromOrderId);
    }

    /**
     * * 获取订单的募集期利息（针对还款计划已经生成的项目）
     * @param number $orderId
     * @return number 没有除以100
     */
    function getOrderMjqIncome($orderId){

    	$prjInfo = M()->table('fi_prj_order t1')->join('left join fi_prj t2 on t1.prj_id = t1.id')
    				  ->field('t1.id, t1.money, t1.prj_id, t1.ctime, t2.end_bid_time, t2.time_limit, t2.time_limit_unit, t2.time_limit_day, t2.rate_type, t2.rate, t2.year_rate')
    				  ->where(array('t1.id' => $orderId))->find();
    	if(!$prjInfo){
    		throw_exception('订单所属项目不存在。');
    	}
    	if(!$this->isOrderRepayPlanStartCreate($prjInfo['prj_id'])){
    		throw_exception('该项目还款计划尚未生成。');
    	}

    	$prjModel = D('Financing/Prj');

    	$dayRate = $prjInfo['rate'];
    	if($prjInfo['rate_type'] == PrjModel::RATE_TYPE_YEAR){
    		$dayRate = $prjInfo['year_rate'] / PrjModel::RATE_DAY_TO_YEAR;
    	}
    	//募集期天数
    	$mjqDays = (strtotime(date('Ymd', $prjInfo['end_bid_time'])) - strtotime(date('Ymd', $prjInfo['ctime']))) / 86400;
    	$income = $prjInfo['money'] * $mjqDays * $dayRate / 1000;
    	return $income;
    }

    /**
     * 获取项目的确认书id
     * @param number $zhrApplyid 如果项目为直融则不为空
     */
    function getPrjConfirmProtocolId($zhrApplyid = 0){
    	$codeNo = $zhrApplyid ? 20 : 21;
    	$condition = array(
    			'is_active' => 1,
    			'name_en' => $codeNo,
    			'mi_no' => self::$api_key,
    	);
    	$id = M('protocol')->where($condition)->order('id desc')->getField('id');
//     	file_put_contents('c:\444.txt', M('protocol')->getLastSql());
    	return $id;
    }


    // 根据项目Id获取保理公司Id
    public function getBlIdByPrjId($prj_id) {
        $oModel = M();
        return (int)$oModel->table('fi_prj prj')->join('LEFT JOIN fi_guarantor_user gu ON prj.uid=gu.uid')->where(array('prj.id' => $prj_id))->getField('gu.guarantor_id');
    }

    public function getRepayDeposit($prj_id){
        $prj_ext = M('prj_ext')->where(array('prj_id'=>$prj_id))->find();
        $prj = M('prj')->find($prj_id);
        if(!$prj) throw_exception("项目不存在");
        if(!$prj_ext || !$prj_ext['repay_fee_status']) return false;
        if($prj_ext['repay_guarant_fee']){
            $guarant_fee = $prj_ext['repay_guarant_fee'];
        } else if($prj_ext['repay_guarant_rate']) {
            $guarant_rate = $prj_ext['repay_guarant_rate'] / 1000;
            $guarant_fee = bcmul(($prj['demand_amount'] - $prj['remaining_amount'])
                , $guarant_rate, 0);
        }

        if($prj_ext['repay_deposit_fee']){
            $deposit_fee = $prj_ext['repay_deposit_fee'];
        } else if($prj_ext['repay_deposit_rate']) {
            $deposit_rate = $prj_ext['repay_deposit_rate'] / 1000;
            $deposit_fee = bcmul(($prj['demand_amount'] - $prj['remaining_amount'])
                , $deposit_rate, 0);
        }
        return array('deposit_fee'=>$deposit_fee, 'guarant_fee'=>$guarant_fee);
    }

    //还款流程扣取手续费和服务费
    public function repayDeposit($prj_id, $kwargs)
    {
        $prj = M('prj')->find($prj_id);
        $param = $this->getRepayDeposit($prj_id);
        $guarant_fee = $param['guarant_fee'];
        $deposit_fee = $param['deposit_fee'];

        $tenant_id = K($kwargs, 'tenant_id');
        $fee_platform = K($kwargs, 'fee_platform', 0);
        $fee_org = K($kwargs, 'fee_org', 0);
        $prj_repay_plan_id = K($kwargs, 'prj_repay_plan_id', 0);

        $db = D('Payment/PayAccount');
        $db->startTrans();
        try {
            // 合作方还款获取的手续费
            if ($guarant_fee) {
                $guarantor_user = M('guarantor_user')->where(array(
                    'guarantor_id' => $prj['guarantor_id'],
                    'is_pay' => 1
                ))->find();
                if (!$guarantor_user) {
                    throw_exception('合作机构支付账户不存在');
                }
                $result = service('Payment/PayAccount')->repayDirectPay($tenant_id,
                    $prj['id'] . '_gua_' . $guarantor_user['uid'],
                    $guarant_fee,
                    '机构[' . $prj['guarantor_id'] . ']收取项目[' . $prj['id'] . ']服务费' . ($guarant_fee / 100),
                    '机构[' . $prj['guarantor_id'] . ']收取项目[' . $prj['id'] . ']服务费' . ($guarant_fee / 100),
                    $guarantor_user['uid'], PayAccountModel::OBJ_TYPE_REPAY_SHOUXU, $prj['id'], '合作机构服务费');
                if(!$result['boolen']) {
                    throw_exception($result['message']);
                }
            }

            // 平台管理费
            if ($deposit_fee) {
                $result = service('Payment/PayAccount')->repayDirectPay($tenant_id,
                    $prj['id'] . '_pt_' . PLAT_ACCOUNTID,
                    $deposit_fee,
                    '平台[' . PLAT_ACCOUNTID . ']收取项目[' . $prj['id'] . ']服务费' . ($deposit_fee / 100),
                    '平台[' . PLAT_ACCOUNTID . ']收取项目[' . $prj['id'] . ']服务费' . ($deposit_fee / 100),
                    PLAT_ACCOUNTID, PayAccountModel::OBJ_TYPE_REPAY_SHOUXU, $prj['id'], '平台服务费');
                if(!$result['boolen']) {
                    throw_exception($result['message']);
                }
            }

            // 创客融平台管理费
            if($fee_platform) {
                $platform_account_chuangke_id = C('PLATFORM_ACCOUNT_CHUANGKE_ID');
                if(!$platform_account_chuangke_id) {
                    throw_exception('未配置PLATFORM_ACCOUNT_CHUANGKE_ID');
                }
                $result = service('Payment/PayAccount')->repayDirectPay($tenant_id,
                    $prj['id'] . '_plan_' .$prj_repay_plan_id . '_pt_' . $platform_account_chuangke_id,
                    $fee_platform,
                    '平台[' . $platform_account_chuangke_id . ']收取项目[' . $prj['id'] . ']服务费' . ($fee_platform / 100),
                    '平台[' . $platform_account_chuangke_id . ']收取项目[' . $prj['id'] . ']服务费' . ($fee_platform / 100),
                    $platform_account_chuangke_id, PayAccountModel::OBJ_TYPE_REPAY_SHOUXU, $prj['id'], "平台服务费({$prj['prj_name']})");
                if(!$result['boolen']) {
                    throw_exception($result['message']);
                }
            }

            // 创客融机构管理费
            if($fee_org) {
                $org_account_chuangke_id = C('ORG_ACCOUNT_CHUANGKE_ID');
                if(!$org_account_chuangke_id) {
                    throw_exception('未配置ORG_ACCOUNT_CHUANGKE_ID');
                }
                $result = service('Payment/PayAccount')->repayDirectPay($tenant_id,
                    $prj['id']  . '_plan_' .$prj_repay_plan_id . '_org_' . $org_account_chuangke_id,
                    $fee_org,
                    '机构[' . $org_account_chuangke_id . ']收取项目[' . $prj['id'] . ']服务费' . ($fee_org / 100),
                    '机构[' . $org_account_chuangke_id . ']收取项目[' . $prj['id'] . ']服务费' . ($fee_org / 100),
                    $org_account_chuangke_id, PayAccountModel::OBJ_TYPE_REPAY_SHOUXU, $prj['id'], "融资管理费({$prj['prj_name']})");
                if(!$result['boolen']) {
                    throw_exception($result['message']);
                }
            }
            $db->commit();
        } catch (Exception $e) {
            $db->rollback();
            throw $e;
        }
    }

    //保证金充值
    public function prjDeposit($prj_id, $prj_deposit){
        $db = D('Payment/PayAccount');
        $prj_ext = M('prj_ext')->find($prj_id);
        $project = M('prj')->find($prj_id);
        $db->startTrans();
        try {

            if ($prj_ext) {
                if($prj_ext['prj_deposit']) throw_exception("项目保证金已经扣取");
                $con = M('prj_ext')->where(array('prj_id'=>$prj_id))->setInc('prj_deposit', $prj_deposit);
                if(!$con) throw_exception("项目保证金失败，数据库异常1");
                $prj_ext_data['prj_id'] = $prj_id;
                $prj_ext_data['cut_deposit'] = 0;
                $prj_ext_data['prj_deposit_status'] = 1;
                M('prj_ext')->save($prj_ext_data);

                $prj_d['id'] = $project['id'];
                $prj_d['platform_fee'] = $prj_ext['prj_deposit'] + $prj_deposit;
                $prj_d['mtime'] = time();
                M('prj')->save($prj_d);
            } else {
                $prj_ext_data['prj_id'] = $prj_id;
                $prj_ext_data['prj_deposit'] = $prj_deposit;
                $prj_ext_data['cut_deposit'] = 0;
                $prj_ext_data['prj_deposit_status'] = 1;
                $con = M('prj_ext')->add($prj_ext_data);

                $prj_d['id'] = $project['id'];
                $prj_d['platform_fee'] = $prj_deposit;
                $prj_d['mtime'] = time();
                M('prj')->save($prj_d);
                if(!$con) throw_exception("项目保证金失败，数据库异常");
            }

            //服务费是否足够
            $tenant = service('Payment/PayAccount')->getBaseInfo($project['tenant_id']);
            if($tenant['amount'] + $tenant['reward_money'] < $prj_deposit){
            	throw_exception("支付账户余额不足");
            }

            service('Payment/PayAccount')->directPay($project['tenant_id'],
                $project['tenant_id'] . "_" . $project['id'] . "deposit_" . PLAT_ACCOUNTID
                , $prj_deposit, 0, 0, 0,
                "收取项目保证金" . $project['id'] . "保证金手续费" . ($prj_deposit / 100)
                , "收取项目保证金" . $project['id'] . "保证金手续费" . ($prj_deposit / 100)
                , PLAT_ACCOUNTID, PayAccountModel::OBJ_PT_SERVICE_FEE, $project['id'], 1); // 内部已采用异常机制

            $db->commit();
        } catch (Exception $e){
            $db->rollback();
            throw $e;
        }
    }

    //扣取保证金充值
    public function cutDeposit($prj_id, $prj_deposit){
        $db = D('Payment/PayAccount');
        $prj_ext = M('prj_ext')->find($prj_id);
        $project = M('prj')->find($prj_id);
        $db->startTrans();
        try {
            if ($prj_ext) {
                $con = M('prj_ext')->where(array('prj_id'=>$prj_id))->setDec('prj_deposit', $prj_deposit);
                if(!$con) throw_exception("扣取保证金失败，数据库异常1");
                $con = M('prj_ext')->where(array('prj_id'=>$prj_id))->setInc('cut_deposit', $prj_deposit);
                if(!$con) throw_exception("扣取保证金失败，数据库异常2");
                $prj_ext_data['prj_id'] = $prj_id;
                $prj_ext_data['prj_deposit_status'] = 2;
                M('prj_ext')->save($prj_ext_data);

                $prj_d['id'] = $project['id'];
                $prj_d['platform_fee'] = $prj_ext['prj_deposit'] - $prj_deposit;
                $prj_d['mtime'] = time();
                M('prj')->save($prj_d);
            } else {
                throw_exception("扣取保证金失败，数据库异常");
            }

            service('Payment/PayAccount')->directPay(PLAT_ACCOUNTID,
                $project['tenant_id'] . "_" . $project['id'] . "cutdeposit_" . PLAT_ACCOUNTID
                , $prj_deposit, 0, 0, 0,
                "收取项目保证金" . $project['id'] . "保证金手续费" . ($prj_deposit / 100)
                , "收取项目保证金" . $project['id'] . "保证金手续费" . ($prj_deposit / 100)
                , $project['tenant_id'], PayAccountModel::OBJ_PT_SERVICE_FEE, $project['id'], 1); // 内部已采用异常机制

            $db->commit();
        } catch (Exception $e){
            $db->rollback();
            throw $e;
        }
    }

    // 拆标父子列表相关方法
    public function getParentsWhere($where, $filter=FALSE) {
        if($filter && !is_array($filter)) $filter = explode(',', $filter);
        D('Financing/Prj');
        $sql_addon_status = '';
        foreach ($where as $key => $v) {
            preg_match('/^(\w+?\.)*?(status|bid_status)$/', $key, $match);
            $prefix = $match[1] ? $match[1] : 'fi_prj.';
            $field = $match[2];
            if(!$field) continue;

            if($field == 'status' && $v == PrjModel::STATUS_PASS) {
                $where[$key] = array('IN', array($v, PrjModel::STATUS_SPLITED_PARENT_OK));
                if(!$sql_addon_status) $sql_addon_status = ' AND `status`=' . PrjModel::STATUS_PASS . ' ';
            }
            if($field == 'bid_status') {
                if(is_numeric($v)) $stack = array($v);
                elseif(is_array($v) && strtoupper($v[0]) == 'IN' && is_array($v[1])) $stack = $v[1];
                else continue;
                $stacks = implode(',', $stack);

                $sql_addon = '';
                $sql_addon2 = '';
                $_string = array();
                $_string2 = array();
                if(in_array('valid', $filter)) $_string[] = "NOT (status=" . PrjModel::STATUS_SPLITED_CHILDREN . " AND bid_status=" . PrjModel::BSTATUS_END . ")";
                if(in_array('for_pay', $filter) || in_array('for_repay', $filter)) $_string[] = "NOT (status=" . PrjModel::STATUS_PASS . " AND bid_status=" . PrjModel::BSTATUS_END . " AND demand_amount=remaining_amount)";
                if(in_array('for_pay', $filter)) {
                    // wait they...
                    $_string2[] = "(status=" . PrjModel::STATUS_SPLITED_CHILDREN . " AND bid_status=" . PrjModel::BSTATUS_WATING . ")";
                    $_string2[] = "(status=" . PrjModel::STATUS_PASS . " AND bid_status=" . PrjModel::BSTATUS_BIDING . ")";
                    $_string2[] = "(status=" . PrjModel::STATUS_PASS . " AND bid_status=" . PrjModel::BSTATUS_WATING . ")";
                }
                if(!empty($_string)) $sql_addon = ' AND (' . implode(') AND (', $_string) . ')';
                if(!empty($_string2)) {
                    $sql_addon2 = ' AND ((' . implode(') OR (', $_string2) . '))';
                    $sql_addon2 = " AND NOT exists(SELECT id FROM fi_prj ewodfjow WHERE prj_type!='" . PrjModel::PRJ_TYPE_H . "' AND spid={$prefix}id $sql_addon2)";
                }

                $sql = "(CASE WHEN {$prefix}have_children=0 THEN {$prefix}bid_status IN ({$stacks}) ELSE (exists(SELECT id FROM fi_prj qiowdhio WHERE prj_type!='" . PrjModel::PRJ_TYPE_H . "' AND spid={$prefix}id {$sql_addon_status} AND bid_status IN ({$stacks})$sql_addon)$sql_addon2) END)";
                if(!array_key_exists('_string', $where)) $where['_string'] = $sql;
                else $where['_string'] = "({$where['_string']}) AND {$sql}";
                unset($where[$key]);
            }
        }
        $where['spid'] = 0;

        return $where;
    }
    public function getChildrenWhere($parents_id, $prefix='', $filter=FALSE) {
        if($filter && !is_array($filter)) $filter = explode(',', $filter);
        D('Financing/Prj');
        $ret = array(
            $prefix . 'prj_type' => array('NEQ', PrjModel::PRJ_TYPE_H),
            $prefix . 'status' => array('IN', array(PrjModel::STATUS_PASS, PrjModel::STATUS_SPLITED_CHILDREN)),
            $prefix . 'spid' => array('IN', $parents_id),
        );
        $_string = array();
        if(in_array('valid', $filter)) $_string[] = "NOT ({$prefix}status=" . PrjModel::STATUS_SPLITED_CHILDREN . " AND {$prefix}bid_status=" . PrjModel::BSTATUS_END . ")";
        if(in_array('for_pay', $filter) || in_array('for_repay', $filter)) $_string[] = "NOT ({$prefix}status=" . PrjModel::STATUS_PASS . " AND {$prefix}bid_status=" . PrjModel::BSTATUS_END . " AND {$prefix}demand_amount={$prefix}remaining_amount)";
        if(in_array('for_pay', $filter)) {
            // wait they
            $_string[] = "NOT ({$prefix}status=" . PrjModel::STATUS_SPLITED_CHILDREN . " AND {$prefix}bid_status=" . PrjModel::BSTATUS_WATING . ")";
            $_string[] = "NOT ({$prefix}status=" . PrjModel::STATUS_PASS . " AND {$prefix}bid_status=" . PrjModel::BSTATUS_BIDING . ")";
            $_string[] = "NOT ({$prefix}status=" . PrjModel::STATUS_PASS . " AND {$prefix}bid_status=" . PrjModel::BSTATUS_WATING . ")";
        }
        if(!empty($_string)) $ret['_string'] = '(' . implode(') AND (', $_string) . ')';

        return $ret;
    }
    public function getParentsId(&$parents, $field_prj_id='id') {
        $ret = array();
        foreach ($parents as $row) {
//            if(!$row['spid']) continue;
            $ret[] = $row[$field_prj_id];
        }

        return $ret;
    }
    public function bindChildren($parents, &$childrend, $field_prj_id='id') {
        if(!$parents) return $parents;
        if(!$childrend) return $parents;

        $_childrend = array();
        foreach ($childrend as $row) {
            if(!array_key_exists($row['spid'], $_childrend)) $_childrend[$row['spid']] = array($row);
            else $_childrend[$row['spid']][] = $row;
        }

        $ret = array();
        foreach ($parents as $row) {
            $ret[] = $row;
            if(!array_key_exists($row[$field_prj_id], $_childrend)) continue;
            foreach ($_childrend[$row[$field_prj_id]] as $child) {
                $ret[] = $child;
            }
        }

        return $ret;
    }
    public function getChildrenId($parent_id, $filter = '')
    {
        if ($filter && !is_array($filter)) {
            $filter = explode(',', $filter);
        }
        $mdPrj = D('Financing/Prj');
        $where = array(
            'prj_type' => array('NEQ', PrjModel::PRJ_TYPE_H),
            'spid' => $parent_id,
            'status' => PrjModel::STATUS_PASS,
        );
        if (in_array('for_pay', $filter)) {
            // 待支付
            $where['bid_status'] = array('IN', array(PrjModel::BSTATUS_FULL, PrjModel::BSTATUS_END));
            $where['_string'] = "NOT (status=" . PrjModel::STATUS_PASS . " AND bid_status=" . PrjModel::BSTATUS_END . " AND demand_amount=remaining_amount)";
        }

        $ret = array();
        $rows = $mdPrj->where($where)->field('id,status,bid_status,demand_amount,remaining_amount')->select();
        if (!$rows) {
            return array($parent_id);
        }
        foreach ($rows as $_project) {
            $ret[] = $_project['id'];
        }
        return $ret;
    }
    public function combineBooleanAttr(&$list, $attrs=array(), $field_prj_id='id') {
        if(!$attrs) return;
        foreach ($attrs as $attr) {
            $logic_or = FALSE;
            if(is_array($attr)) {
                $logic_or = $attr[1];
                $attr = $attr[0];
            }
            foreach ($list as &$row) {
                if(!$row['have_children']) continue;
                $value = $logic_or;
                $spid = $row[$field_prj_id];
                foreach ($list as $_row) {
                    if($_row['spid'] != $spid) continue;
                    if(!$logic_or && !$_row[$attr] || $logic_or && $_row[$attr]) {
                        $value = $logic_or;
                        break;
                    }
                    $value = $_row[$attr];
                }
                $row[$attr] = $value;
            }
        }
    }
    public function combineIntAttr(&$list, $attrs=array(), $field_prj_id='id') {
        if(!$attrs) return;
        foreach ($attrs as $attr) {
            foreach ($list as &$row) {
                if(!$row['have_children']) continue;
                $value = 0;
                $spid = $row[$field_prj_id];
                foreach ($list as $_row) {
                    if($_row['spid'] != $spid) continue;
                    $value += (int)$_row[$attr];
                }
                $row[$attr] = $value;
            }
        }
    }
    public function combineOrderAttr(&$list, $attrs=array(), $field_prj_id='id', $desc=FALSE) {
        if(!$attrs) return;
        foreach ($attrs as $attr) {
            foreach ($list as &$row) {
                if(!$row['have_children']) continue;
                $stack = array();
                $spid = $row[$field_prj_id];
                foreach ($list as $_row) {
                    if($_row['spid'] != $spid) continue;
                    $stack[] = $_row[$attr];
                }
                if($desc) rsort($stack);
                else sort($stack);
                if($stack) $row[$attr] = $stack[0];
            }
        }
    }
    public function getChildField($parent_id, $field, $order='ASC') {
        $mdPrj = D('Financing/Prj');
        $children_id = $this->getChildrenId($parent_id);

        return $mdPrj->where(array(
            'id' => array('IN', $children_id),
            'status' => PrjModel::STATUS_PASS
        ))->order("{$field} $order")->getField($field);
    }
    public function checkRepayMoney($uid, $prj_id, $return_diff=FALSE, &$total_repay_money = 0, $is_daichang = false) {
        $prj_repay_plan_model = D('Financing/PrjRepayPlan');
        $mdUserAccount = M('user_account');
        $children_id = $this->getChildrenId($prj_id);
        $account = $mdUserAccount->where(array('uid' => $uid))->find();

        if ($is_daichang) {
            //代偿 不扣除融资服务费
            $filed = 'SUM(pri_interest + fee) TT';
        } else {
            $filed = 'SUM(pri_interest + fee + org_fee) TT';
        }
        $row = $prj_repay_plan_model->where(array(
            'prj_id' => array('IN', $children_id),
            'status' => PrjRepayPlanModel::PRJ_REPAYMENT_STATUS_WAIT,
            'repay_date' => array('ELT', strtotime(date('Y-m-d'))),
        ))->field($filed)->find();

        $total_repay_money = (int)$row['TT'];
        if($total_repay_money > $account['repay_freeeze_cache'] || $total_repay_money > $account['repay_freeze_money']) {
            if($return_diff) return $account['repay_freeze_money'] - $total_repay_money;
            return FALSE;
        }

        return TRUE;
    }
    public function convertToParentPrjName($prj_name) {
        $md = M('prj');
        $spid = $md->where(array('prj_name' => $prj_name))->getField('spid');
        if(!$spid) return $prj_name;

        return $md->where(array('id' => $spid))->getField('prj_name');
    }

    /**
     * 根据用户判断是否有项目可以投资
     * @param type $uid
     */
    public function hasQfxProject($uid){
        $model = M("Prj");
        $where =  service("Financing/CompanySalary")->init($uid)->getQfxFilter();
        $count = $model->where($where)->count();
        return $count;
    }

    public function newBuy(array $params)
    {
        $return_data = array(
            'msg' => '',
            'type' => 1,
            'status' => 2
        );

        try {
            checkArrayData($params, array(
                'uid',
                'prj_id',
                'money',
                'is_pre_buy',
                'skip_time_interval',
                'is_appoint_invest',
                'reward_type',
                'reward_id',
                'is_pre_sale_create',
                'pre_sale_id',
                'x_order_id',
                'is_lock',
                'match_id',
                'is_platform_user',
                'award',
            ), array(
                'skip_time_interval',
                'is_appoint_invest',
                'reward_type',
                'reward_id',
                'is_pre_sale_create',
                'pre_sale_id',
                'x_order_id',
                'is_lock',
                'match_id',
                'is_platform_user',
                'award',
            ));

            $uid = $params['uid'];
            $prj_id = $params['prj_id'];
            $money = $params['money'];
            $is_pre_buy = $params['is_pre_buy'];

            $info = $this->getDataById($prj_id);
            if (!$info) {
                throw_exception('该项目不存在，请检查后重试');
            }

            // 时间间隔检测开始
            $is_check_time_interval = !$params['skip_time_interval'];
            if($is_check_time_interval) {
                $srvTimerBuy = service('Financing/TimerBuy');
                $srvTimerBuy->init($uid, $prj_id, $info);
                $srvTimerBuy->start();
            }

            $money_params = $this->getMoneyParams($uid, $money, $params, $info, (int) $params['x_order_id']);
            $money_params = array_merge($money_params, array(
                'skip_time_interval' => 1,
                'reward_id' => $params['reward_id'],
                'reward_type' => $params['reward_type'],
                'is_pre_sale_create' => $params['is_pre_sale_create'] ? 1 : 0,
                'pre_sale_id' => $params['is_pre_sale_create'] ? 1 : 0,
                'is_platform_user' => $params['is_platform_user'] ? 1 : 0
            ));

            //项目类型加载对应的service
            import_app("Financing.Model.PrjModel");
            $obj = null;
            if($is_pre_buy == 0){
                switch ($info['prj_type']) {
                    case PrjModel::PRJ_TYPE_A:
                        $obj = service("Financing/Loan");
                        break;
                    case PrjModel::PRJ_TYPE_B:
                        $obj = service("Financing/LongLoan");
                        break;
                    case PrjModel::PRJ_TYPE_C:
                        $obj = service("Financing/Fund");
                        break;
                    case PrjModel::PRJ_TYPE_D:
                        $obj = service("Financing/House");
                        break;
                    case PrjModel::PRJ_TYPE_F:
                        $obj = service("Financing/Juyou");
                        break;
                    case PrjModel::PRJ_TYPE_G:
                        $obj = service("Financing/LiCai");
                        break;
                    case PrjModel::PRJ_TYPE_H:
                        $obj = service("Financing/FastCash");
                        break;
                    case PrjModel::PRJ_TYPE_I:
                        $obj = service("Financing/Piaoju");
                        break;
                    case PrjModel::PRJ_TYPE_J:
                        $obj = service("Financing/ClaimsTransfer");
                }
            }else{
                //预售
                $obj = service('Financing/PreSale');
                $result = $obj->buy($uid,$prj_id,$money,$info['prj_type'],$money_params);
                if(!$result){
                    throw_exception('预售投标失败，请重试');
                }
                $return_data['is_pre_sale'] = 1;
                $return_data['pre_order_id'] = $result;
                $return_data['status']=1;
                $return_data['msg']='ok';
                return $return_data;
            }

            if (!$obj) {
                throw_exception('项目类型异常，请刷新后重试。');
            }

            // 手动进行令牌验证
            C("TOKEN_ON", true);
            $result = null;

            if($info['prj_type'] !== PrjModel::PRJ_TYPE_H){
                $result = $obj->buy($money, $uid, $prj_id, $money_params, 0);
            }else{
                $result = $obj->buy($money, $uid, $prj_id, "投资速兑通", 0, $money_params, 0);
            }
            if (!$result) {
                throw_exception(MyError::lastError());
            }

            //交易确认
            service("Account/Active")->clickBidLog("prjid_" . $prj_id, 1);

            $return_data['order_id']=$result;
            $return_data['status']=1;
            //设置满标
            $prjStatus = M('prj')->find($prj_id);
            $this->setPrjFull(
                $prj_id,
                $prjStatus['remaining_amount'],
                $prjStatus['matched_amount'],
                $prjStatus['prj_type']
            );

            //钩子
            $params = array(
                'uid' => $uid,
                'prj_id' => $prj_id,
                'money' => $money,
                'time_limit_day' => $info['time_limit_day'],
                'prj_type' => $info['prj_type'],
                'guarantor_id' => $info['guarantor_id'], //担保公司的ID
            );

            tag('bid_end', $params);
            $return_data = array_merge($return_data, $params);

            $return_data['orderInfo'] = array("prj_id" => $prj_id, "uid" => $uid, "time" => date('Y-m-d H:i:s'));
                
            // 时间间隔检查结束
            if($is_check_time_interval && isset($srvTimerBuy)) {
                $srvTimerBuy->end();
            }
        } catch (Exception $exc) {
            D("Admin/AccountExceptLog")->insertLog($uid, "Invest/buy", "Financing/$prj_id","buy", $exc->getMessage());
            $return_data['msg']=$exc->getMessage();
        }
        return $return_data;
    }

    /**
     * 投资之前使用代金券 红包等等
     * @param $uid
     * @param $money
     * @param $params
     * @param $info
     * @param int $x_order_id 如果是司马小鑫投资的 为司马小鑫订单ID
     * @return array
     */
    private function getMoneyParams($uid, $money, $params, $info, $x_order_id = 0)
    {
        if ($x_order_id) {
            //司马小鑫 暂时不支持代金券 红包 等等
            return array(
                'coupon_money' => 0,
                'award' => 0,
                'x_order_id' => $x_order_id,
                'is_lock' => (int) $params['is_lock'],
                'xprj_match_id' => (int) $params['match_id'],
            );
        }

        //奖金参数准备
        $money_params = array(
            //待使用券奖金
            'coupon_money' => M('user_account')->where(array('uid'=>$uid))->getField('coupon_money'),
        );

        $coulist = $this->getPrjCouponList($uid, $money_params['coupon_money'], $info);
        $a_coupon_money = 0;
        foreach($coulist as $couEle){
            $a_coupon_money += $couEle['amount'];
        }
        $money_params['coupon_money'] = $a_coupon_money;
        $money_params['reward_type'] = $params['reward_type'];
        $money_params['reward_id'] = $params['reward_id'];
        //不适用任何奖励的时候 需要把可提现现金包含进去;
        if ($money_params['reward_type']) {
            $init = 0;//是否是初始化奖励页面
            //所有活动奖励
            $award = $this->getRealAward($uid, $money, $a_coupon_money, $info['id'], $a_coupon_money, $init, $params);
        } else {
            $award = $this->getRewardMoney($uid,$money);
        }
        $money_params['award'] = $award;

        //是否是大学生专有标
        $is_college = M('prj_ext')->where(array('prj_id'=>$info['id']))->getField('is_college');
        if ($is_college === 1) {
            $money_params['award'] = 0;
        }

        return $money_params;
    }

    /**
     * 每万元收益（年化收益）
     * @param $time_limit
     * @param $time_limit_unit
     * @param $year_rate
     * @return number
     */
    public function getWanYuanProfit($time_limit, $time_limit_unit, $year_rate, $notShowUnit=0){
        $cTime= strtotime(date('Y-m-d'));
        $endBidTime = $this->formatUnitTime($time_limit,$time_limit_unit,$cTime);
        $startTime= strtotime("+1 days ",$cTime);
        $endTime= strtotime("+1 days ",$endBidTime);
        $ret = $this->profitComputeByDate('year',$year_rate,$startTime,$endTime,10000*100);
        if($notShowUnit == 1){
            return humanMoney($ret, 2, false);
        }
        return humanMoney($ret, 2, false).'元';
    }

    /**
     * 获取标的最后一次还款时间
     * @param int $prjId
     * @param string $format
     * @return string
     */
	public function getPrjLastRepayTimeById($prjId, $format = 'Y-m-d'){
		if (!(int)$prjId) return '';
		$prj = M('prj')->field('have_children, last_repay_date')
			->where(array('id' => $prjId))->find();
		if(!$prj['have_children']){
			$lastRepayDate = $prj['last_repay_date'];
		}else{
			$childPrjs = M('prj')->field('last_repay_date')
					->where(array('spid' => $prjId))->order('last_repay_date desc')->find();
			$lastRepayDate = $childPrjs['last_repay_date'];
		}
		$return = $format ? date($format, $lastRepayDate) : $lastRepayDate;
		return $return;
	}

	/**
	 * 获取标的实际募集期
	 * @param int $prj_id
	 * @return string
	 */
	public function getPrjActualMujiqById($prj_id){
        if (!(int)$prj_id) {
            return '';
        }
        $mdPrj = D('Financing/Prj');
        $prj_info = $mdPrj->field('have_children, start_bid_time, end_bid_time')->where(array('id' => $prj_id))->find();
        if (!$prj_info['have_children']) {
            $start_bid_time = $prj_info['start_bid_time'];
            $end_bid_time = $prj_info['end_bid_time'];
        } else {
            $where = array(
                'spid' => $prj_id,
                'status' => PrjModel::STATUS_PASS,
                'bid_status' => array('NOT IN', array(PrjModel::BSTATUS_CANCEL, PrjModel::BSTATUS_FAILD, )),
            );
            $start_bid_time = $mdPrj->where($where)->order('start_bid_time ASC')->getField('start_bid_time');
            $end_bid_time = $mdPrj->where($where)->order('end_bid_time DESC')->getField('end_bid_time');
        }
        $ret = (strtotime(date('Ymd', $end_bid_time)) - strtotime(date('Ymd', $start_bid_time))) / 86400 + 1;
        $ret .= '天';
        return $ret;
	}


    /**
     * 创建签章合同队列 //2016年11月 签章改造上线后不再使用
     * @param [type] $order_id 订单id
     * @param [type] $prjInfo  项目
     * @param [type] $uid      投资人id
     */
    public function addContractQueue($order_id,$prjInfo,$uid){
        if(empty($order_id) || empty($prjInfo)) return;

        try {
            $fund_id=$uid;
            $queue_key = $order_id;
            $project_id=$prjInfo['id'];

            //转换成父id
            $spid=M('prj')->where(array('id'=>$project_id))->getField('spid');
            if(intval($spid) > 0)$project_id=$spid;

            //老项目不走电子合同
            $signService = service('Signature/Sign');
            if(!$signService->isNeedSigned($project_id))return;

            //合同类型
            $protocolType=service('Public/ProtocolData')->getProtocolTypeByPrj($prjInfo['id'],$prjInfo);

            if (in_array($protocolType, array(15,17))) { //应收账款转让及回购合同,借款协议走签章流程

                $step_ids = M('prj_protocol_param')->where(array('prj_id'=>$project_id,'protocol_type'=>$protocolType,'param_name'=>'step_ids'))->getField('param_value');
                if(empty($step_ids)) throw_exception('未设置签约流程的项目，不签约');

                import_addon('libs.Queue.Beanstalk');
                $queue = new Queue\Beanstalk();
                $queue_name = 'create_sign_contract';

                $queue_data = array(
                    'fund_id'=> $fund_id,
                    'project_id'=> $project_id,
                    'order_id'=> $order_id,
                    'queue_key'=> $queue_key,
                    'is_alarm' => 1,
                );
                checkArrayData($queue_data,array('fund_id','project_id','order_id','queue_key','is_alarm'));
                $queue->set($queue_name,$queue_key,$queue_data);
            }
        } catch (Exception $e) {
            $this->signLog($fund_id,$project_id,$order_id, $e->getMessage());
        }
    }

    /**
     * 创建合同队列出错日志
     * @param  int $fund_id  投资人uid
     * @param  int $prj_id   项目id
     * @param  int $order_id 订单id
     * @return void
     */
    private function signLog($fund_id,$prj_id,$order_id,$msg){
        $path = SITE_DATA_PATH . "/logs/sign/".date('Y')."/".date('m');
        $logPath = $path."/".date('d').".txt";
        if(!is_dir($path)) mkdir($path,0777,true);
        $cdate=date('Y-m-d H:i:s');
        $logstr = "ERROR:{$cdate}\t\ttype:project\t\tfund_id:{$fund_id}\t\tprj_id:{$prj_id}\t\torder_id:{$order_id}\t\tmsg:{$msg}\r\n";
        file_put_contents($logPath, $logstr,FILE_APPEND);
    }

    /**
     * 鑫整点的SQL的过滤语句
     */
    public function getNewWholePointFilter($bid_status = array(),$is_newbie = 1)
    {
        //投标中的项目
        D("Financing/Prj");
        $where['is_show'] = 1;
//        $where['prj_type'] = array('NEQ',PrjModel::PRJ_TYPE_H) ;
        if(!empty($bid_status) && is_array($bid_status)) {
            $where['bid_status'] = array(
                'IN',
                $bid_status
            );
        }
        $where['status'] = 2;
        $where['is_show'] = 1;
//        $where['_string'] = "exists(select 1 from fi_prj_ext where fi_prj.id=fi_prj_ext.prj_id and is_xzd=1)";

        $this->setJoinFinancList(array(
            'LEFT JOIN fi_prj_ext ON fi_prj.id=fi_prj_ext.prj_id',
        ));
        $where['fi_prj_ext.is_xzd'] = 1;
        if (is_null($is_newbie)) {
            $is_newbie = 1;
        }
        if (!$is_newbie) {
            $where['is_new'] = 0;
        }
        return $where;
    }

    public function setJoinFinancList($join='') {
        if(!$join) return;
        $this->patch_join_financlist = $join;
    }

    private function patchJoinFinancList(&$model) {
        if(!$this->patch_join_financlist) return;
        $model->join($this->patch_join_financlist);
    }

    public function genCombineOrder($prj_id) {
        return D('Financing/Prj')->genCombineOrder($prj_id);
    }

    /**
     * 项目是不是存管
     * @param $prj_id
     * @param array $prj_ext_info
     * @return bool
     */
    public function projectIsDeposit($prj_id, $prj_ext_info = [])
    {
        if (isset($prj_ext_info['is_deposit'])) {
            $is_deposit = $prj_ext_info['is_deposit'];
        } else {
            $is_deposit = M('prj_ext')->where(['prj_id' => $prj_id])->getField('is_deposit');
        }

        return $is_deposit ? true : false;
    }
}

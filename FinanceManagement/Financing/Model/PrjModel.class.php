<?php
/**
 * User: 000802
 * Date: 2013-10-10 14:05
 * $Id$
 *
 * Type: Model
 * Group: Fanancing
 * Module: 我要融资
 * Description: 项目基本信息
 */


class PrjModel extends BaseModel {
    protected $tableName = 'prj';

    const PRJ_VERSION = 2; // 项目版本，便于功能调整时对新旧项目区别对待

    const PRJ_TYPE_A = 'A'; // 日益升
    const PRJ_TYPE_B = 'B'; // 企益升 ---> 年益升
    const PRJ_TYPE_C = 'C'; // 稳益保 ---> 废除
    const PRJ_TYPE_D = 'D'; // 抵益融 ---> 废除(合并到B)
    const PRJ_TYPE_E = 'E'; // 快益转
    const PRJ_TYPE_F = 'F'; // 聚优宝--->月益升
    const PRJ_TYPE_G = 'G'; // 鑫湖一号
    const PRJ_TYPE_H = 'H'; // 速兑通
    const PRJ_TYPE_I = 'I'; // 鑫银票
    const PRJ_TYPE_J = 'J'; // 债权转让

    const PRJ_BUSINESS_TYPE_A = 'A';//垫资贷 业务类型 鑫保一号
    const PRJ_BUSINESS_TYPE_B = 'B';//鑫银一号
    const PRJ_BUSINESS_TYPE_C = 'C';//电商贷 经营贷
    const PRJ_BUSINESS_TYPE_D = 'D';//三农贷 经营贷
    const PRJ_BUSINESS_TYPE_E = 'E';//经营贷
    const PRJ_BUSINESS_TYPE_F = 'F';//鑫股一号
    const PRJ_BUSINESS_TYPE_G = 'C00303';//央企融代码 经营贷
    const PRJ_BUSINESS_TYPE_H = 'C0V14';//创客融 经营贷
    const PRJ_BUSINESS_TYPE_I = 'G'; //假日升
    const PRJ_BUSINESS_TYPE_J = 'H'; //增小闲

    const RATE_TYPE_DAY = 'day';        // 利率类型
    const RATE_TYPE_MONTH = 'month';
    const RATE_TYPE_YEAR = 'year';
    const RATE_MONTH_TO_YEAR = 12;      // 月转年
    const RATE_DAY_TO_YEAR = 365;       // 日转年

    const LIMIT_UNIT_DAY = 'day';        // 期限单位
    const LIMIT_UNIT_MONTH = 'month';
    const LIMIT_UNIT_YEAR = 'year';

    const BORROWER_TYPE_COMPANY = 1;    // 借款人类型：企业
    const BORROWER_TYPE_PERSONAL = 2;   // 借款人类型：个人

    const PRJ_SERIES_GFS = 1;   // 期限30天以内就是 高富帅
    const PRJ_SERIES_BFM = 2;   // 期限30天以上就是 白富美
    const PRJ_SERIES_FED = 3;   // 期限30天以上就是 白富美

    const STATUS_WATING = 1;    // 等待审核
    const STATUS_PASS = 2;      // 审核通过
    const STATUS_PASS_SPECIAL = 21;// 审核通过，但是不允许普通用户购买
    const STATUS_FAILD = 3;     // 审核不通过
    const STATUS_SPLITED_PARENT = -1;   // 待拆分的母标
    const STATUS_SPLITED_PARENT_OK = -2;// 已拆分的母标
    const STATUS_SPLITED_CHILDREN = -3; // 待发布的子标

    const BSTATUS_WATING = 1;   // 待开标
    const BSTATUS_BIDING = 2;   // 投标中
    const BSTATUS_FULL = 3;     // 已满标
    const BSTATUS_REPAYING = 4; // 待还款
    const BSTATUS_REPAID = 5;   // 已还款结束
    const BSTATUS_ZS_REPAYING = 6;   // 浙商划账中
    const BSTATUS_REPAID_OTHER = 51; // 已代偿
    const BSTATUS_END = 7;      // 截止投标
    const BSTATUS_REPAY_IN = 8; // 还款中
    const BSTATUS_CANCEL = 98;   // 已撤标(用户自己取消)
    const BSTATUS_FAILD = 99;   // 已流标
    const BSTATUS_END_MANUAL = 97; // 理财手动截标
    const BSTATUS_FULL_PAY = 31; //满标状态下java支付状态
    const BSTATUS_END_PAY = 71;  //截止投标状态下java支付状态

    const SAFEGUARDS_INTEREST = 1;  // 保本保息
    const SAFEGUARDS_CAPITAL = 2;   // 保本

    const REPAY_WAY_ENDATE = 'endate';      // 到期还本付息
    const REPAY_WAY_HALFYEAR = 'halfyear';  // 半年
    const REPAY_WAY_PERMONTH = 'permonth';  // 每月等额本息还款
    const REPAY_WAY_D = 'D';                // 按月付息，到期还本
    const REPAY_WAY_E = 'E';                // 到期还本付息(新)
    const REPAY_WAY_PERMONTHFIXN = 'PermonthFixN'; // 创客融的固定日期等额本息算法

    const PAY_WAY_AFTER_FULL = 1;            // 满标后支付
    const PAY_WAY_EVERY_DAY_IN_BIDING = 2;   // 募集期每天支付

    const ADDCREDIT_ID_GUARANTOR = '1';   // 保障措施中【第三方担保】的id

    const MESSAGE_TYPE_CANCEL = 21;         // 撤销产品时的消息类型ID
    const MESSAGE_TYPE_FAILD = 42;          // 流标消息类型
    const MESSAGE_TYPE_CANCEL_PRE = 207;    // 预售标的取消

    const PRJ_CLASS_TZ = 1;
    const PRJ_CLASS_LC = 2;

    /**
     * 接口筛选的条件标示
     */
    const FILTER_PRJ_ALL = 0;//所有项目,不过滤
    const FILTER_PRJ_NEW_BIE = 1;//新客专区
    const FILTER_PRJ_NEW_ENABLE = 2;//可投的新标
    const FILTER_PRJ_NEW_SDT = 3;//排序为99的速兑通项目
    const FILTER_PRJ_WAIT = 4;//即将开标
    const FILTER_PRJ_EDN = 5;//完成投资
    const FILTER_PRJ_PRE = 6;//预售
    const FILTER_PRJ_WHOLE_POINT = 7;//鑫正点
    const FILTER_PRJ_SDT_INIT = 98;//速兑通初始化数据
    const FILTER_PRJ_INIT = 99;//投资推荐初始化数据

    protected $_auto = array(
        array('mtime', 'time', self::MODEL_BOTH, 'function'),
    );

    protected $_validate = array(
        array('prj_name', 'require', '项目名称必填'),
        array('prj_name', '2,20', '项目名称必须2到10个字符', self::EXISTS_VALIDATE, 'length'),
        array('prj_name','checkPrjName','项目名称和已有项目重复', self::EXISTS_VALIDATE, 'callback'),
        array('demand_amount', 'require', '融资规模必填'),
        array('demand_amount', 'is_numeric', '融资规模必须为金额数字', self::EXISTS_VALIDATE ,'function'),
//        array('demand_amount', '/^\d+(\.\d{1,4})?$/', '融资规模必须为金额数字', self::EXISTS_VALIDATE, 'regex'),
        array('time_limit', 'gtZero','期限必须大于0', self::EXISTS_VALIDATE, 'function'),

        array('time_limit', 'require', '期限必填'),
        array('time_limit', 'number', '期限必须为数字'),
        array('time_limit', 'gtZero','期限必须大于0', self::EXISTS_VALIDATE, 'function'),
        array('time_limit_unit', 'require', '期限单位必选'),

        array('rate', 'require', '利率必填'),
        array('rate', '/^\d+(\.\d{1,2})?$/', '利率必须为数字，最多2位小数', self::EXISTS_VALIDATE, 'regex'),
        array('rate', 'gtZero','利率必须大于0', self::EXISTS_VALIDATE, 'function'),

        array('rate_type', 'require', '利率类型必选'),
        array('addcredit_ids', 'require', '保障措施必选'),
        array('guarantor_id', 'require', '担保人必选'),
        array('addcredit_desc', 'require', '保障措施说明此项必填'),
        array('safeguards', 'require', '保障性质必选'),
        array('repay_way', 'require', '还款方式必选'),
        array('pay_way', 'require', '支付方式必选'),

        array('start_bid_time', 'require', '开标时间必填'),
        array('start_bid_time', '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/i', '格式错误，正确格式：2013-11-14 12:00:00', self::EXISTS_VALIDATE, 'regex'),
        array('start_bid_time','checkTime','开标时间必须大于当前时间', self::EXISTS_VALIDATE, 'callback'),

        array('end_bid_time', 'require', '截止时间必填'),
        array('end_bid_time', '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/i', '格式错误，正确格式：2013-11-14 12:00:00', self::EXISTS_VALIDATE, 'regex'),
        array('end_bid_time','checkTime','截止时间必须大于当前时间', self::EXISTS_VALIDATE, 'callback'),
//        array('end_bid_time','checkEndBidTime','截止时间必须在16点之前', self::EXISTS_VALIDATE, 'callback'),

        array('min_bid_amount', 'require', '投资起始金额必填'),
        array('min_bid_amount', 'currency', '投资起始金额必为数字'),
        array('min_bid_amount', 'gtZero','投资起始金额必须大于0', self::EXISTS_VALIDATE, 'function'),

        array('max_bid_amount', 'number', '最大投资金额必为数字', self::VALUE_VALIDATE),
        array('max_bid_amount', 'gteZero','最大投资金额必须大于或等于0', self::VALUE_VALIDATE, 'function'),

        array('prj_type', 'require', '产品类别必选'),

        array('step_bid_amount', 'require', '投资递增金额必填'),
        array('step_bid_amount', 'number', '投资递增金额必须为数字'),
        array('step_bid_amount', 'gtZero','投资递增金额必须大于0', self::EXISTS_VALIDATE, 'function'),
        array('step_bid_amount', 'check_step_bid_amount','投资递增金额不在选择范围内', self::EXISTS_VALIDATE, 'callback'),

        array('borrower_type', 'require', '借款人类型必选'),
        array('borrower', 'require', '借款人必填'),
//        array('ubsp_prj_id', 'require', 'UBSP的项目ID未填写，请重新获取'),

        // 平台管理费
        array('platform_fee', 'currency', '必须为大于或等于0的数字'),
        array('platform_fee', 'gteZero','必须大于0', self::EXISTS_VALIDATE, 'function'),
//        array('corp_register_no', 'require', '工商注册号，请重新获取'),
//        array('corp_name', 'require', '企业名称为空，请重新重新获取'),
//        array('person_no', 'require', '借款人身份证为空，请重新获取'),
//        array('person_name', 'require', '借款人姓名为空，请重新获取'),

        array('is_new', 'require', '请选择是否新客项目'),
        array('is_multi_buy', 'require', '请选择是否允许多次投标'),
    );
    static $step_bid_mount_range = array(50, 100, 1000, 10000, 100000);
    static $step_bid_amount_range = array(50, 100, 1000, 10000, 100000,5000,10000,100000,1000000,10000000);

    static $prj_time_set = array(
        PrjModel::BSTATUS_BIDING => 5, //投标中 缓存 5秒
        PrjModel::BSTATUS_FULL => 3600, // 已满标  缓存 1小时
        PrjModel::BSTATUS_END => 3600,  // 投资结束 缓存 1小时
        PrjModel::BSTATUS_REPAYING => 86400, // 待款中 缓存1小时
        PrjModel::BSTATUS_REPAID => 86400, // 还款结束  缓存1天
    );
    /**
     * 返回项目状态的列表
     * @return type
     */
    public function getPrjStatusList()
    {
        return array(
            self::BSTATUS_WATING => "待开标",
            self::BSTATUS_BIDING => "投标中",
            self::BSTATUS_FULL => "已满标",
            self::BSTATUS_REPAYING => "待还款",
            self::BSTATUS_REPAID => "已还款结束",
            self::BSTATUS_END => "截止投标",
            self::BSTATUS_REPAY_IN => "还款中",
            self::BSTATUS_FAILD => "已流标",
        );
    }
    /**
     * 获取项目名称列表,新增项目类型的时候同时补齐这里
     * @return type
     */
    public function getPrjNameList()
    {
        return array(
            self::PRJ_TYPE_A => "日益升",
            self::PRJ_TYPE_B => "年益升",
            self::PRJ_TYPE_E => "快益转",
            self::PRJ_TYPE_F => "月益升",
            self::PRJ_TYPE_G => "鑫湖一号",
            self::PRJ_TYPE_H => "速兑通",
        );
    }

    /**
     * 根据项目的状态返回状态的提示语句
     * @param type $status
     * @return string
     */
    public function getPrjStatusText($status)
    {
        $statusList = $this->getPrjStatusList();
        $keys = array_keys($statusList);
        if (!in_array($status, $keys)){
            return "未知";
        }
        return $statusList[$status];
    }

    /**
     * 自定义验证
     *
     * @param string $data
     * @param string $type
     * @return mixed|string
     */
    public function create($data='',$type='') {
//        if(C('APP_STATUS') != 'product'/*非线上取消时间验证*/) {
//            foreach ($this->_validate as $key => $validate) {
//                if($validate[0] == 'start_bid_time' || $validate[0] == 'end_bid_time') {
//                    unset($this->_validate[$key]);
//                }
//            }
//        }

        // 合伙企业验证
        $partner = FALSE;
        if(array_key_exists('partner', $data)) {
            if(!$data['partner']) {
                throw_exception('合伙企业必填');
            }
            $partner = $data['partner'];
            unset($data['partner']);
        }
        if(!$data) {
            return TRUE;
        }
        $_data = $data;
        $data = parent::create($data, $type);
        if(!$data) {
            throw_exception($this->getError());
        }

        if(isset($data['time_limit']) && isset($data['time_limit_unit'])) {
            $day = (int)$data['time_limit'];
            if ($data['time_limit_unit'] == self::LIMIT_UNIT_MONTH) {
                $day *= 30;
            }
//            if (isset($_data['is_transfer']) && $_data['is_transfer'] == 1) {
//                if($day < 180) throw_exception('只有期限大于6个月的才能转让');
//            }

            if($data['show_bid_time']){
                if(strtotime($data['show_bid_time']) <= time()){
                    throw_exception('发标时间必须大于当前时间！');
                }elseif(strtotime($data['show_bid_time']) >= strtotime($data['start_bid_time'])){
                    throw_exception('发标时间必须小于开标时间！');
                }
            }

            if (isset($data['start_bid_time']) && isset($data['end_bid_time']) && C('APP_STATUS') != 'product'/*非线上取消时间验证*/) {
                // 截至时间必须大于开始时间
                $time_diff = strtotime($data['end_bid_time']) - strtotime($data['start_bid_time']);
                if($time_diff <=0 ) {
                    throw_exception('融资截至时间必须大于融资开始时间！');
                }

                // 起始时间到截止时间的区间要在融资期限内
//                $time_interval = $day * 86400;
//                if($time_diff > $time_interval) {
//                    throw_exception('融资开始、截至时间必须在融资期限范围内！');
//                }
            } elseif (isset($data['prj_type'])) {
                // 根据期限判断产品类型
                // 2013/11/22 产品类别不做限制
//                if(I('prj_id')) {
//                    // 修改时
//                    if($data['prj_type'] == self::PRJ_TYPE_A && $day > 29) {
//                        throw_exception('短期借贷需为29天以下');
//                    } elseif ($data['prj_type'] == self::PRJ_TYPE_B && ($day <= 29)) {
//                        throw_exception('中长期借贷需为29天以上');
//                    } elseif ($data['prj_type'] == self::PRJ_TYPE_C && $day <=180 ) {
//                        throw_exception('私募基金需为180天以上');
//                    }
//                } else {
//                    // 添加时
//                    if ($day <= 29 && $data['prj_type'] != self::PRJ_TYPE_A) {
//                        throw_exception('期限30天以下的需为短期借贷');
//                    } elseif ($day > 29 && $day <= 180 && $data['prj_type'] != self::PRJ_TYPE_B) {
//                        throw_exception('期限30至180天的需为中长期借贷');
//                    } elseif ($day > 180 && $data['prj_type'] == self::PRJ_TYPE_A) {
//                        throw_exception('期限180以上的需选择私募基金或中长期借贷');
//                    }
//                }

            } elseif (isset($data['repay_way'])) {
                if($day < 30*12 && $data['repay_way'] == self::REPAY_WAY_PERMONTH) throw_exception('只有12个月以上的项目才可以用“每月等额本息”还款方式');
                if($day < 30*12 && $data['repay_way'] == self::REPAY_WAY_D) throw_exception('只有12个月以上的项目才可以用“按月付息，到期还本”还款方式');
            } else {
                // 期限类型判断
//                if($day > 29 && $data['time_limit_unit'] == self::LIMIT_UNIT_DAY) {
//                    throw_exception('期限超过29天请按月设置');
//                }
            }

        }

        if(isset($data['min_bid_amount']) && isset($data['demand_amount'])) {
            if($data['min_bid_amount'] > $data['demand_amount'] * 10000) {
                throw_exception('投资起始金额不能大于融资规模');
            }
        }

        if(isset($data['max_bid_amount']) && isset($data['demand_amount'])) {
            if($data['max_bid_amount'] > $data['demand_amount'] * 10000) {
                throw_exception('最大投资金额不能大于融资规模');
            }
        }

        if(isset($data['min_bid_amount']) && isset($data['step_bid_amount'])) {
            if($data['min_bid_amount'] < $data['step_bid_amount']) {
                throw_exception('投资递增金额不能大于投资起始金额');
            }
        }

        if (isset($data['min_bid_amount']) && isset($data['max_bid_amount']) && !empty($data['min_bid_amount']) && !empty($data['max_bid_amount'])) {
            if ($data['max_bid_amount'] <= $data['min_bid_amount']) {
                throw_exception('最大投资金额必须大于投资起始金额');
            }
        }

        // 预期年化利率不得低于10%
//        if(isset($data['rate']) && isset($data['rate_type'])) {
//            $rate = (int)$data['rate'];
//            if($data['rate_type'] == self::RATE_TYPE_YEAR && $rate < 10) {
//                throw_exception('预期年化利率不得低于10%');
//            }
//        }

        if($partner) {
            $data['partner'] = $partner;
        }

        // 加入prj版本
        $data['version'] = self::PRJ_VERSION;
        return $data;
    }


    private function _data_process(&$input) {
        if(isset($input['value_date'])) {
            $input['value_date_shadow'] = $input['value_date'];
            if($input['value_date'] == '0') $input['value_date'] = '1';
        }

        $input['is_allow_regist'] = (int)!!$input['is_allow_regist'];
    }

    private function getPlatformRate($year_rate, $rate_interest) {
        if($rate_interest < $year_rate) return 0;
        return ($rate_interest/1000 - $year_rate/1000)/2;
    }


    /**
     * 添加产品基础数据
     *
     * @param $uid          添加者uid
     * @param array $input
     * @return mixed
     */
    private function _addPrj($uid, $input=array()) {

        $guarantor_id = $input['guarantor_id_baoli'] ? $input['guarantor_id_baoli'] : $input['guarantor_id'];
        $now = time();
        $data = array(
            'p_prj_id' => $input['p_prj_id'],//add by 001181 维护速兑通父节的id
            'mi_no' => $input['mi_no'],//add by 001181接入点ID
            'prj_name' => $input['prj_name'],
            'demand_amount' => $input['demand_amount'],
            'time_limit' => $input['time_limit'],
            'time_limit_unit' => $input['time_limit_unit'],
            'rate' => $input['rate'],
            'rate_type' => $input['rate_type'],
            'addcredit_ids' => $input['addcredit_ids'],
            'addcredit_desc' => $input['addcredit_desc'],
            'guarantor_id' => $guarantor_id,
            'safeguards' => $input['safeguards'],
            'repay_way' => $input['repay_way'],
            'pay_way' => $input['pay_way'],

            'show_bid_time' => $input['show_bid_time'],
            'start_bid_time' => $input['start_bid_time'],
            'end_bid_time' => $input['end_bid_time'],
            'max_bid_amount' => $input['max_bid_amount'],
            'step_bid_amount' => $input['step_bid_amount'],
            'prj_type' => $input['prj_type'],
            'prj_old_type' => $input['prj_type'],
            'prj_no' => $input['prj_no'],

            'borrower_type' => $input['borrower_type'],
            'ubsp_prj_id' => $input['ubsp_prj_id'],
            'ubsp_cust_id' => $input['ubsp_cust_id'],
            'ubsp_prj_no' => $input['ubsp_prj_no'],
            'corp_register_no' => $input['corp_register_no'],
            'corp_name' => $input['corp_name'],
            'person_no' => $input['person_no'],
            'person_name' => $input['person_name'],
            'is_new' => $input['is_new'],
            'is_multi_buy' => ($input['is_multi_buy'] ==='0') ? 0:1,

            'is_college' => $input['is_college'],
            'business_type' => $input['business_type'],
        );

        if(isset($input['tenant_id']) && $input['tenant_id']){
            $data['tenant_id'] = $input['tenant_id'];
        }elseif($tenant = service("Application/ProjectManage")->getTenantByUid($uid)){
            $data['tenant_id'] = $tenant;
        }

        if(isset($input['zhr_apply_id']) && $input['zhr_apply_id']){
            $data['zhr_apply_id'] = $input['zhr_apply_id'];
        }

        // 日益升没有支付方式
//        if($data['prj_type'] == self::PRJ_TYPE_A) unset($data['pay_way']);

        // 保障措施为质押或抵押时，不需要担保人
//guarantor_id

        ////////////////////////////////////////////////////////////////////////////////////////////////



        // 计算产品系列
        $day = (int)$data['time_limit'];
        if ($data['time_limit_unit'] == self::LIMIT_UNIT_MONTH) {
            $day *= 30;
        }
        if($day < 30) {
            $prj_series = self::PRJ_SERIES_GFS;
        } else {
            $prj_series = self::PRJ_SERIES_BFM;
        }

        if(!$data['prj_no']){
            $data['prj_no'] = $this->genPrjNo($data['prj_type'], $prj_series);
        }

        //入库检测通不过，单独提出来判断
        if(isset($input['min_bid_amount'])) $data['min_bid_amount'] = fMoney($input['min_bid_amount'], '');
        if(isset($input['step_bid_amount'])) $data['step_bid_amount'] = fMoney($input['step_bid_amount'], '');
        // 字段处理
        $data['demand_amount'] = fMoney($data['demand_amount']);
        $data['max_bid_amount'] = fMoney($data['max_bid_amount'], '');      // 单位：元


        // 验证基本字段
        $data = $this->create($data);

        $data['start_bid_time'] = strtotime($data['start_bid_time']);
        $data['end_bid_time'] = strtotime($data['end_bid_time']);
        if($data['rate_type'] != self::RATE_TYPE_DAY) {                     // 利率统一转换成千分
            $data['rate'] *= 10;
            $data['total_year_rate'] = $data['rate'];
        }


        // 下次还款日
        if($data['repay_way'] != self::REPAY_WAY_ENDATE) {
            $data['next_repay_date'] = strtotime('+1 day', $data['end_bid_time']);
        }

        // 初始化字段
        $data['uid'] = $uid;
        $data['prj_series'] = $prj_series;
        $data['remaining_amount'] = $data['demand_amount'];                 // 剩余金额
        $data['status'] = self::STATUS_WATING;                              // 审核状态
        $data['bid_status'] = self::BSTATUS_WATING;                         // 开标状态
        $data['ctime'] = $now;
        $data['mtime'] = $now;

        // 融资期限统一成天，便于统计
        if($data['time_limit_unit'] == self::LIMIT_UNIT_MONTH) {
            $data['time_limit_day'] = $data['time_limit'] * 30;
        } elseif($data['time_limit_unit'] == self::LIMIT_UNIT_YEAR) {
            $data['time_limit_day'] = $data['time_limit'] * self::RATE_DAY_TO_YEAR;
        }  else {
            $data['time_limit_day'] = $data['time_limit'];
        }

        // 统一转换成年利率，便于排序
        $data['year_rate'] = $data['rate'];
        if($data['rate_type'] == self::RATE_TYPE_DAY) {
            $data['year_rate'] *= self::RATE_DAY_TO_YEAR;
        } elseif($data['rate_type'] == self::RATE_TYPE_MONTH) {
            $data['year_rate'] *= self::RATE_MONTH_TO_YEAR;
        }

        // 预计还款时间
        $data['repay_time'] = $data['end_bid_time'] + $data['time_limit_day'] * 86400;


//        $data['publish_uid'] = $uid;
        $data['publish_inst_id'] = M("guarantor_user")->where("uid=".$uid)->getField('guarantor_id');
        if(!empty($data['publish_inst_id'])){
            $data['publish_inst'] = M('invest_guarantor')->where(array('id' => $data['publish_inst_id']))->getField('title');
        }else{
            // 部门Id和发布机构
            $publish = service('Financing/Financing')->getPublishInst($uid);
            $data['dept_id'] = $publish['dept_id'];
            $data['publish_inst'] = $publish['publish_inst'];
        }


        // 平台管理费
//        $data['platform_fee'] = service('Financing/Trade')->getFee($data['demand_amount'], $data['time_limit'], $data['time_limit_unit']);
        $data['platform_fee'] = 0;
        $data['is_withhold'] = 0; // 募集期内，收益是否代扣
        $data['platform_rate'] = 0;//0.02; // 平台管理费率

        // 票面利率和平台管理费
        if($data['prj_type'] == self::PRJ_TYPE_F) {
            $data['rate_interest'] = (float)($input['rate_interest']) * 10;
            $data['platform_rate'] = 0;//$this->getPlatformRate($data['year_rate'], $data['rate_interest']);
        }

        $data['value_date_shadow'] = $input['value_date_shadow'];
        $data['is_allow_regist'] = $input['is_allow_regist'];

        //如果发表时间不为空 则is_show置为0
        if(!empty($input['show_bid_time'])) $data['is_show'] = 0;

        // 插入数据
        $prj_id = $this->add($data);
        if($prj_id === FALSE) {
            throw_exception('添加基础信息失败！');
        }

        $prj_ext['prj_id'] = $prj_id;
        $prj_ext['ctime'] = time();
        M('prj_ext')->add($prj_ext);

        if($input['is_college'] || $input['show_bid_time']){
            $prj_ext['prj_id'] = $prj_id;

            $input['is_college'] && $prj_ext['is_college'] = 1;
            $input['show_bid_time'] && $prj_ext['show_bid_time'] = strtotime($input['show_bid_time']);

//        	$ext = M('prj_ext')->where($prj_ext)->field('id')->find();
            /*if($ext) $con = */M('prj_ext')->save($prj_ext);
//        	else {
//        		$prj_ext['ctime'] = time();
//        		$prj_ext_id = M('prj_ext')->add($prj_ext);
//        	}
        }

        return $prj_id;
    }

    private function checkPrjExt($dataExt, $default_interest, $a_time_limit, $a_time_limit_unit){
        if($dataExt['is_early_repay']){
//    		if(!$default_interest['ratio']) throw_exception('提前回购罚息利息必须大于0！');
//    		if(!$default_interest['time_limit']) throw_exception('请设置提前回购时间！');
            if(!$default_interest['time_limit_unit']) throw_exception('请设置提前回购时间单位！');
            if($default_interest['time_limit_unit'] == 'day'){
                $a_time_limit_day = $a_time_limit;
                if($a_time_limit_unit == 'month') $a_time_limit_day = 30*$a_time_limit;
                if($a_time_limit_unit == 'year') $a_time_limit_day = 365*$a_time_limit;
                $min_time_limit = ceil($a_time_limit_day*0.4);
            }
            if($default_interest['time_limit_unit'] == 'month'){
                if($a_time_limit_unit == 'day') throw_exception('提前回购最少时间单位选择错误');
                if($a_time_limit_unit == 'month') $a_time_limit_month = $a_time_limit;
                if($a_time_limit_unit == 'year') $a_time_limit_month = 12*$a_time_limit;
                $min_time_limit = ceil($a_time_limit_month*0.4);
            }
            if($default_interest['time_limit_unit'] == 'year') {
                if($a_time_limit_unit != 'year') throw_exception('提前回购最少时间单位选择错误');
                $min_time_limit = ceil($a_time_limit*0.4);
            }
//    		if($min_time_limit > $default_interest['time_limit']){
//    			throw_exception('提前回购最少时间设置不能小于'.$min_time_limit);
//    		}

        }
    }


    /**
     * 修改产品基础数据
     *
     * @param $prj_id          修改者uid
     * @param array $input
     * @return mixed
     */
    private function _updatePrj($prj_id, $input=array()) {
        $where = array(
            'id' => $prj_id,
        );
        $project = $this->where($where)->find();
        $now = time();
        $data = array();
//        $data = array(
//            'prj_name' => $input['prj_name'],
//            'addcredit_ids' => $input['addcredit_ids'],
//            'addcredit_desc' => $input['addcredit_desc'],
//            'safeguards' => $input['safeguards'],
//
//            'start_bid_time' => $input['start_bid_time'],
//            'end_bid_time' => $input['end_bid_time'],
//            'min_bid_amount' => $input['min_bid_amount'],
//            'max_bid_amount' => $input['max_bid_amount'],
//            'step_bid_amount' => $input['step_bid_amount'],
//
//            'borrower_type' => $input['borrower_type'],
//            'ubsp_prj_id' => $input['ubsp_prj_id'],
//            'ubsp_cust_id' => $input['ubsp_cust_id'],
//            'ubsp_prj_no' => $input['ubsp_prj_no'],
//            'corp_register_no' => $input['corp_register_no'],
//            'corp_name' => $input['corp_name'],
//            'person_no' => $input['person_no'],
//            'person_name' => $input['person_name'],
//            'is_new' => $input['is_new'],
//            'is_multi_buy' => $input['is_multi_buy'],
//        );

        // 保障措施为质押或抵押时，不需要担保人
//        if(in_array((int)$data['addcredit_ids'], array(2, 3))) {
//             $data['guarantor_id'] = 0;
//        }

        ////////////////////////////////////////////////////////////////////////////////////////////////
        if(isset($input['safeguards']))  $data['safeguards'] = $input['safeguards'];
        if(isset($input['start_bid_time']))  $data['start_bid_time'] = $input['start_bid_time'];
        if(isset($input['end_bid_time']))  $data['end_bid_time'] = $input['end_bid_time'];
        if(isset($input['min_bid_amount']))  $data['min_bid_amount'] = $input['min_bid_amount'];
        if(isset($input['max_bid_amount']))  $data['max_bid_amount'] = $input['max_bid_amount'];
        if(isset($input['step_bid_amount']))  $data['step_bid_amount'] = $input['step_bid_amount'];
        if(isset($input['borrower_type']))  $data['borrower_type'] = $input['borrower_type'];

        //借款人企业或名字记录到项目表中
        if(isset($input['corp_name']))  $data['corp_name'] = $input['corp_name'];
        if(isset($input['person_name']))  $data['person_name'] = $input['person_name'];

        if(isset($input['ubsp_prj_id']))  $data['ubsp_prj_id'] = $input['ubsp_prj_id'];
        if(isset($input['ubsp_cust_id']))  $data['ubsp_cust_id'] = $input['ubsp_cust_id'];
        if(isset($input['ubsp_prj_no']))  $data['ubsp_prj_no'] = $input['ubsp_prj_no'];
        if(isset($input['corp_register_no']))  $data['corp_register_no'] = $input['corp_register_no'];
        if(isset($input['corp_name']))  $data['corp_name'] = $input['corp_name'];
        if(isset($input['person_no']))  $data['person_no'] = $input['person_no'];
        if(isset($input['person_name']))  $data['person_name'] = $input['person_name'];
        if(isset($input['is_new']))  $data['is_new'] = $input['is_new'];
        if(isset($input['is_multi_buy']))  $data['is_multi_buy'] = $input['is_multi_buy'];

        if(isset($input['addcredit_desc']))  $data['addcredit_desc'] = $input['addcredit_desc'];
        if(isset($input['addcredit_ids']))  $data['addcredit_ids'] = $input['addcredit_ids'];
        if(isset($input['is_transfer']))  $data['is_transfer'] = $input['is_transfer'];
        if(isset($input['pay_way']))  $data['pay_way'] = $input['pay_way'];
        if(isset($input['value_date_shadow']))  $data['value_date_shadow'] = $input['value_date_shadow'];
        if(isset($input['value_date']))  $data['value_date'] = $input['value_date'];

        if(isset($input['prj_name'])){
            $data['prj_name'] = $input['prj_name'];
        }

        if(isset($input['rate'])){
            $data['rate'] = $input['rate'];
        }

        if(isset($input['guarantor_id'])){
            $data['guarantor_id'] = $input['guarantor_id'];
        }

        if(isset($input['demand_amount'])){
            $data['demand_amount'] = $input['demand_amount'];
        }

        if(isset($input['repay_way'])){
            $data['repay_way'] = $input['repay_way'];
        }

        if(isset($input['rate_type'])){
            $data['rate_type'] = $input['rate_type'];
        }

        if(isset($input['time_limit'])){
            $data['time_limit'] = $input['time_limit'];
        }

        if(isset($input['time_limit_unit'])){
            $data['time_limit_unit'] = $input['time_limit_unit'];
        }

        if(isset($input['prj_type'])){
            $data['prj_type'] = $input['prj_type'];
            $data['prj_old_type'] = $input['prj_type'];
        }
        // 验证基本字段
//        $data['prj_type'] = $project['prj_type']; // 验证用户选择时间范围是否符合当前产品类型
        $data = $this->create($data);
//        unset($data['prj_type']);                 // 产品类型不可修改，去除

        // 计算产品系列
//        if($data['time_limit_unit'] == 'month' && (int)$data['time_limit'] > 1) {
//            $prj_series = self::PRJ_SERIES_BFM;
//        } else {
//            $prj_series = self::PRJ_SERIES_GFS;
//        }
        // 字段处理
        if(isset($input['demand_amount'])) $data['demand_amount'] = fMoney($data['demand_amount']);
        if(isset($input['min_bid_amount'])) $data['min_bid_amount'] = fMoney($data['min_bid_amount'], '');      // 单位：元
        if(isset($input['max_bid_amount'])) $data['max_bid_amount'] = fMoney($data['max_bid_amount'], '');      // 单位：元
        if(isset($input['step_bid_amount']))  $data['step_bid_amount'] = fMoney($data['step_bid_amount'], '');    // 单位：元
        if(isset($input['start_bid_time']))  $data['start_bid_time'] = strtotime($data['start_bid_time']);
        if(isset($input['end_bid_time'])) $data['end_bid_time'] = strtotime($data['end_bid_time']);

        if(isset($input['rate'])){
            if($data['rate_type'] != self::RATE_TYPE_DAY) {                     // 利率统一转换成千分
                $data['rate'] *= 10;
            }
        }

        if(isset($input['repay_way'])){
            // 下次还款日
            if($data['repay_way'] != self::REPAY_WAY_ENDATE) {
                $data['next_repay_date'] = strtotime('+1 day', $data['end_bid_time']);
            }
        }

        // 初始化字段
//            $data['uid'] = $uid;
//        $data['prj_series'] = $prj_series;
        if(isset($data['demand_amount'])) $data['remaining_amount'] = $data['demand_amount'];                 // 剩余金额
        $data['status'] = self::STATUS_WATING;                              // 审核状态
        $data['bid_status'] = self::BSTATUS_WATING;                         // 开标状态
//            $data['ctime'] = $now;
        $data['mtime'] = $now;

        // 融资期限统一成天，便于统计
        if(isset($input['time_limit_unit'])){
            if($data['time_limit_unit'] == self::LIMIT_UNIT_MONTH) {
                $data['time_limit_day'] = $data['time_limit'] * 30;
            } elseif($data['time_limit_unit'] == self::LIMIT_UNIT_YEAR) {
                $data['time_limit_day'] = $data['time_limit'] * self::RATE_DAY_TO_YEAR;
            } else {
                $data['time_limit_day'] = $data['time_limit'];
            }
        }
        // 统一转换成年利率，便于排序
        if(isset($input['rate']) && isset($input['rate_type'])){
            $data['year_rate'] = $data['rate'];
            if($data['rate_type'] == self::RATE_TYPE_DAY) {
                $data['year_rate'] *= self::RATE_DAY_TO_YEAR;
            } elseif($data['rate_type'] == self::RATE_TYPE_MONTH) {
                $data['year_rate'] *= self::RATE_MONTH_TO_YEAR;
            }
        }

        // 票面利率和平台管理费
        if($data['prj_type'] == self::PRJ_TYPE_F) {
            $data['rate_interest'] = (float)($input['rate_interest']) * 10;
            $data['platform_rate'] = 0;//$this->getPlatformRate($data['year_rate'], $data['rate_interest']);
        }

        // 预计还款时间
        if(isset($input['end_bid_time'])) $data['repay_time'] = $data['end_bid_time'] + $data['time_limit_day'] * 86400;

        if(isset($input['value_date_shadow'])) $data['value_date_shadow'] = $input['value_date_shadow'];
        if(isset($input['is_allow_regist'])) $data['is_allow_regist'] = $input['is_allow_regist'];


        if(!$project['prj_no']){
            $data['prj_no'] = $this->genPrjNo($data['prj_type'], $project['prj_series']);
        }


        $dataExt['prj_id'] =  $prj_id;

        //发表时间
        $data['is_show'] = 1;
        $dataExt['show_bid_time'] = null;
        if(!empty($input['show_bid_time'])){
            $showBidTime = strtotime($input['show_bid_time']);
            if($showBidTime > $now){
                $data['is_show'] = 0;
                $dataExt['show_bid_time'] = $showBidTime;
            }
        }

        //出借人确认书协议id
        $dataExt['protocol_id'] = service('Financing/Project')->getPrjConfirmProtocolId(intval($project['zhr_apply_id']));
        if($input['last_repay_date']) $dataExt['old_last_repay_date'] =  $input['last_repay_date'];
        if($data['end_bid_time']) $dataExt['old_end_bid_time'] =  $data['end_bid_time'];
        if(isset($input['is_early_close'])) $dataExt['is_early_close'] = $input['is_early_close'];
        else $dataExt['is_early_close'] = 0;
        if(isset($input['is_early_repay'])) $dataExt['is_early_repay'] = $input['is_early_repay'];
        else $dataExt['is_early_repay'] = 0;
        $default_interest['prj_id'] =  $prj_id;
        if(isset($input['di_time_limit'])) $default_interest['time_limit'] = $input['di_time_limit'];
        if(isset($input['di_time_limit_unit'])) $default_interest['time_limit_unit'] = $input['di_time_limit_unit'];
        if(isset($input['di_ratio'])){
            $default_interest['ratio'] = $input['di_ratio'];
        }
        $default_interest['time_limit_day'] = $default_interest['time_limit'];
        if($default_interest['time_limit_unit'] == 'month') $default_interest['time_limit_day'] = 30*$default_interest['time_limit'];
        if($default_interest['time_limit_unit'] == 'year') $default_interest['time_limit_day'] = 365 * $default_interest['time_limit'];
        $this->checkPrjExt($dataExt, $default_interest, $data['time_limit'], $data['time_limit_unit']);

        // 更新数据
        if($this->where($where)->save($data) === FALSE) {
            throw_exception('系统异常：修改基础信息失败！');
        }

        // 外部渠道的截标时间
        if($_REQUEST['mi_no'] == '1234567991') {
            if($_REQUEST['channel_end_bid_time']) {
                $channel_end_bid_time = strtotime($_REQUEST['channel_end_bid_time']);
                if($channel_end_bid_time > $data['end_bid_time']) throw_exception('外部渠道截标时间不能大于项目原截标时间');
                $dataExt['channel_end_bid_time'] = $channel_end_bid_time;
            } else {
                throw_exception('投放站点为数米必须定义对外截标时间');
            }
        }

        // 是否给募集期利息
        if(isset($_REQUEST['is_have_biding_incoming'])) $dataExt['is_have_biding_incoming'] = (int)!!$_REQUEST['is_have_biding_incoming'];
        else $dataExt['is_have_biding_incoming'] = 1;

        //企福鑫企业是否存在
        $dataExt['is_qfx'] = (int)$input['is_qfx'];
        $dataExt['company_id'] = $input['company_id'] === NULL ? '0' : $input['company_id'];
        if($dataExt['company_id']){
            $is_exist = service("Financing/CompanySalary")->companyIsExist(array('id'=>$dataExt['company_id']));
            !$is_exist && throw_exception('企福鑫企业不存在！请重新输入!');
        }

        $ext = M('prj_ext')->find($prj_id);
        if($ext) $con = M('prj_ext')->save($dataExt);
        else {
            $dataExt['ctime'] = time();
            $con = M('prj_ext')->add($dataExt);
        }
//         if(!$con) throw_exception('系统异常：修改扩展信息失败！');
        $default_interest_a = M('prj_default_interest')->where(array('prj_id'=>$prj_id))->find();
        if($dataExt['is_early_repay'] && $data['time_limit_day'] > 90) throw_exception('提前还款限3个月以内');
        if($dataExt['is_early_repay']){
            if($default_interest_a) {
                $default_interest['id'] = $default_interest_a['id'];
                $default_interest['mtime'] = time();
                $dcon = M('prj_default_interest')->save($default_interest);
            } else {
                $default_interest['ctime'] = time();
                $default_interest['mtime'] = time();
                $dcon = M('prj_default_interest')->add($default_interest);
            }
            if(!$dcon) throw_exception('系统异常：修改回款信息失败！');
        } else {
            if($default_interest_a) M('prj_default_interest')->where(array('prj_id'=>$prj_id))->delete();
        }
    }


    /**
     * 初始化证据列表项
     *
     * @param $prj_id
     * @param $input
     */
    private function _init_audit_record($prj_id, $input) {
        $srvProjectAudit = service('Application/ProjectAudit');
        if($srvProjectAudit->cloneAuditRecord($prj_id, $input)) return; // 尝试克隆同企业项目的审核资料

        $records = getCodeItemList('E021', TRUE);
        if(!$records) return;

        $record_id = 0;
        $mdAuditRecord = D('Application/AuditRecord');
        foreach ($records as $record) {
            $result = $mdAuditRecord->addRecord($prj_id, $record['code_name']);
            if($record['code_name'] == '担保承诺函') $record_id = $result[2];
        }

        if(isset($input['prj_type'])) $srvProjectAudit->addDanbaoHan($prj_id, $input['prj_type'], $record_id);
    }


    /**
     * 销毁项目审核证据
     *
     * @param $prj_id
     */
    private function _destory_audit_record($prj_id) {
        $mdAuditRecord = D('Application/AuditRecord');
        $records = $mdAuditRecord->where(array('prj_id' => $prj_id))->select();
        if(!$records) return;
        foreach ($records as $record) {
            $mdAuditRecord->delRecord($record['id']);
        }
    }


    /**
     * 添加项目及附加信息
     *
     * @param $uid
     * @param array $input
     * @param bool $is_init_audit
     * @return array
     * @throws Exception
     */
    public function addPrj($uid, $input=array(), $is_init_audit=TRUE) {
        $this->_data_process($input);
        $this->startTrans();
        try {
            $prj_id = $this->_addPrj($uid, $input);

            // 附加信息
            if($input['prj_type'] == self::PRJ_TYPE_A) {        // 日升益
                D('Financing/InvestPrj')->addInvest($prj_id, $uid, $input);
                if($is_init_audit) $this->_init_audit_record($prj_id, $input);
            } elseif($input['prj_type'] == self::PRJ_TYPE_B) {  // 年益升
                D('Financing/InvestLongPrj')->addInvest($prj_id, $uid, $input);
                if($is_init_audit) $this->_init_audit_record($prj_id, $input);
            } elseif($input['prj_type'] == self::PRJ_TYPE_F) {  // 聚优宝--->月益升
                D('Financing/InvestJuyou')->addInvest($prj_id, $uid, $input);
                if($is_init_audit) $this->_init_audit_record($prj_id, $input);
            }

            // 融资方信息
            if(in_array($input['prj_type'], array(self::PRJ_TYPE_A, self::PRJ_TYPE_B, /*self::PRJ_TYPE_D, self::PRJ_TYPE_F*/))) {
                if($input['borrower_type'] == self::BORROWER_TYPE_COMPANY) {
                    D('Financing/PrjCorp')->addCorp($prj_id, $uid, $input);
                } elseif($input['borrower_type'] == self::BORROWER_TYPE_PERSONAL) {
                    D('Financing/PrjPerson')->addPerson($prj_id, $uid, $input);
                }
            }

            // 增加合伙企业
            if(in_array($input['prj_type'], array(self::PRJ_TYPE_C))) {
                D('Financing/PrjPartner')->addPartner($prj_id, $input['partner']);
            }

            // 计算&更新最迟还款日
            $where = array(
                'id' => $prj_id,
            );
            $last_repay_date = service('Financing/Project')->getPrjEndTime($prj_id);
            if($last_repay_date){
                $data = array(
                    'last_repay_date' => $last_repay_date,
                    'repay_time'=>$last_repay_date
                );
            }

            // 下次还款日
            if($input['repay_way'] == self::REPAY_WAY_ENDATE || (!service("Financing/Invest")->isShowPlan($prj_id))) {
                $data['next_repay_date'] = $data['last_repay_date'];
            }else{
//                if($input['end_bid_time']) $data['next_repay_date'] = strtotime('+1 day', strtotime($input['end_bid_time']));
                $data['next_repay_date'] = service('Financing/Project')->getPrjFirstRepayDate($prj_id, NULL, FALSE);

            }

            if($this->where($where)->save($data) === FALSE) {
                throw_exception('更新最迟还款日出错（1001）');
            }

            $this->commit();
            return array($prj_id, '添加成功！');
        } catch (Exception $e) {
//            echo $e->getMessage();exit;
            $this->rollback();
            throw $e;
        }
    }


    /**
     * 修改项目及附加信息
     *
     * @param $prj_id
     * @param $uid
     * @param array $input
     * @return array
     * @throws Exception
     */
    public function updatePrj($prj_id, $uid, $input=array(),$mustCheck=1) {
        $this->_data_process($input);
        $where = array(
            'id' => $prj_id,
        );
        $project = $this->where($where)->find();
        if(!$project) {
            throw_exception('项目不存在。');
        }

        if($mustCheck){
            $publish = service('Financing/Financing')->getPublishInst($uid);
            $dept_id = $publish['dept_id'];
            if(!($dept_id && $dept_id == $project['dept_id'] || $project['uid'] == $uid)) {
                throw_exception('只有本人或同部门的人才可以操作。');
            }

            if($project['status'] != self::STATUS_FAILD) {
                throw_exception('只能编辑审核未通过的项目。');
            }
        }

        $this->startTrans();
        try {
            if(isset($input['end_bid_time'])){
                $last_repay_date = service('Financing/Project')->getPrjEndTime($prj_id); // TODO: 此时计算不出来
                $input['last_repay_date'] = $last_repay_date;
            }

            $this->_updatePrj($prj_id, $input);
            $project = $this->where($where)->find();
            // 附加信息
            if($project['prj_type'] == self::PRJ_TYPE_A) {          // 日升益
                D('Financing/InvestPrj')->updateInvest($prj_id, $input);
//                $this->_init_audit_record($prj_id, $input['prj_type']);
            } elseif($project['prj_type'] == self::PRJ_TYPE_B) {    // 企升益
                D('Financing/InvestLongPrj')->updateInvest($prj_id, $input);
//                $this->_init_audit_record($prj_id, $input['prj_type']);
            } elseif($project['prj_type'] == self::PRJ_TYPE_F) {    // 聚优宝--->月益升
                D('Financing/InvestJuyou')->updateInvest($prj_id, $input);
            }

            // 融资方信息
            if(in_array($project['prj_type'], array(self::PRJ_TYPE_A, self::PRJ_TYPE_B, /*self::PRJ_TYPE_D, self::PRJ_TYPE_F*/))) {
                if($input['borrower_type'] == self::BORROWER_TYPE_COMPANY) {
                    D('Financing/PrjCorp')->updateCorp($prj_id, $input);
                } elseif($input['borrower_type'] == self::BORROWER_TYPE_PERSONAL) {
                    D('Financing/PrjPerson')->updatePerson($prj_id, $input);
                }
            }

            // 修改合伙企业
            if(in_array($input['prj_type'], array(self::PRJ_TYPE_C))) {
                $mdPartner = D('Financing/PrjPartner');
                $partner = $mdPartner->getPartner($prj_id);
                if($partner) {
                    $mdPartner->updatePartner($partner['id'], $input['partner']);
                } else {
                    $mdPartner->addPartner($prj_id, $input['partner']);
                }
            }

            // 计算&更新最迟还款日
            $where = array(
                'id' => $prj_id,
            );

            if(isset($input['end_bid_time'])){
                $last_repay_date = service('Financing/Project')->getPrjEndTime($prj_id);
                $data = array(
                    'last_repay_date' => $last_repay_date,
                    'repay_time'=> $last_repay_date
                );
            }
            // 下次还款日
            if($input['repay_way'] == self::REPAY_WAY_ENDATE || (!service("Financing/Invest")->isShowPlan($prj_id))) {
                $data['next_repay_date'] = $data['last_repay_date'];
            }else{
//                if($input['end_bid_time']) $data['next_repay_date'] = strtotime('+1 day', strtotime($input['end_bid_time']));
                $data['next_repay_date'] = service('Financing/Project')->getPrjFirstRepayDate($prj_id, $project, FALSE);
            }

            // _updatePrj里可能没写入old_last_repay_date
            if($last_repay_date && M('prj_ext')->where(array('prj_id' => $prj_id))->find()) {
                M('prj_ext')->where(array('prj_id' => $prj_id))->save(array('old_last_repay_date' => $last_repay_date));
            }

            if($this->where($where)->save($data) === FALSE) {
                throw_exception('更新最迟还款日出错（1002）');
            }

            $this->commit();
            return array(1, '修改成功！');
        } catch (Exception $e) {
            $this->rollback();
            throw $e;
        }
    }


    /**
     * 删除项目
     *
     * @param int $uid      删除者Id
     * @param int $prj_id   项目Id
     * @return array
     * @throws Exception
     */
    public function delPrj($uid=0, $prj_id=0) {
        $uid = (int)$uid;
        $prj_id = (int)$prj_id;

        $where = array(
            'id' => $prj_id,
        );
        $project = $this->find($prj_id);
        if(!$project) {
            throw_exception('项目不存在！');
        }
        if($project['status'] != self::STATUS_FAILD) {
            throw_exception('只可删除审核未通过的项目。');
        }
        $publish = service('Financing/Financing')->getPublishInst($uid);
        $dept_id = $publish['dept_id'];
        if(!($dept_id && $dept_id == $project['dept_id'] || $project['uid'] == $uid)) {
            throw_exception('只有本人或同部门的人才可以操作。');
        }

        $this->startTrans();
        try {
            // 附加信息
            if($project['prj_type'] == self::PRJ_TYPE_A) {          // 日升益
                D('Financing/InvestPrj')->delInvest($prj_id);
            } elseif($project['prj_type'] == self::PRJ_TYPE_B) {    // 企升益
                D('Financing/InvestLongPrj')->delInvest($prj_id);
            }/* elseif($project['prj_type'] == self::PRJ_TYPE_C) {    // 稳保益
                D('Financing/ManageFund')->delManageFund($prj_id);
            } elseif($project['prj_type'] == self::PRJ_TYPE_D) {    // 抵融益
                D('Financing/HouseGuarantee')->delInvest($prj_id);
            } */elseif($project['prj_type'] == self::PRJ_TYPE_F) {    // 聚优宝--->月益升
                D('Financing/InvestJuyou')->delInvest($prj_id);
            }elseif($project['prj_type'] == self::PRJ_TYPE_G){
                D('Financing/InvestFund')->delInvest($prj_id);
            }

            // 融资方信息
            if(in_array($project['prj_type'], array(self::PRJ_TYPE_A, self::PRJ_TYPE_B, /*self::PRJ_TYPE_D, */self::PRJ_TYPE_F))) {
                if($project['borrower_type'] == self::BORROWER_TYPE_COMPANY) {
                    D('Financing/PrjCorp')->delCorp($prj_id);
                } elseif($project['borrower_type'] == self::BORROWER_TYPE_PERSONAL) {
                    D('Financing/PrjPerson')->delPerson($prj_id);
                }
            }

            // 删除项目
            if($this->where($where)->delete() === FALSE) {
                throw_exception('删除项目失败！');
            }

            // 删除证据
            $this->_destory_audit_record($prj_id);

            $this->commit();
            return array(1, '删除成功！');
        } catch (Exception $e) {
            $this->rollback();
            throw $e;
        }
    }


    /**
     * 生成产品编号
     *
     * @param $prj_type         产品类型标识符，如JD（借贷）、JJ（基金）
     * @param int $prj_series   产品系列，1：高富帅，2：白富美
     * @return string
     */
    public function genPrjNo($prj_type, $prj_series=1) {
        Import('libs.Counter.MultiCounter', ADDON_PATH);
        $oCounter = MultiCounter::init(Counter::GROUP_DEFAULT, Counter::LIFETIME_TODAY);
        $prefix = $prj_series . $prj_type . date('Ymd');
        $field_no = 'prj_no';
        $tail_length = 5;

        $the_id = $oCounter->incr($prefix);
        if($the_id) {
            $no = $prefix . str_pad($the_id, $tail_length, '0', STR_PAD_LEFT);
            $has = $this->where(array($field_no => $no))->find();
            if ($has) {
//                throw_exception('系统异常，请联系管理员');
                if(C("APP_STATUS") != 'product'){
                    $max = $this->where(array($field_no => array('LIKE', $prefix . '%')))->order($field_no . ' desc')->find();
                    // 计数器不能自增的时候，这样只读取当天的最大数更保险一点
                }else{
                    $max = $this->order('id desc')->find();
                }
                $the_id = substr($max[$field_no], strlen($max[$field_no]) - $tail_length, $tail_length);
                $the_id = (int)$the_id + 1;
                $no = $prefix . str_pad($the_id, $tail_length, '0', STR_PAD_LEFT);
                $oCounter->set($prefix, $the_id);
                return $no;
            } else {
                return $no;
            }
        } else {
            if(C("APP_STATUS") != 'product'){
                $max = $this->where(array($field_no => array('LIKE', $prefix . '%')))->order($field_no . ' desc')->find();
                // 计数器不能自增的时候，这样只读取当天的最大数更保险一点
            }else{
                $max = $this->order('id desc')->find();
            }
            $the_id = substr($max[$field_no], strlen($max[$field_no]) - $tail_length, $tail_length);
            $the_id = (int)$the_id + 1;
            $no = $prefix . str_pad($the_id, $tail_length, '0', STR_PAD_LEFT);
            return $no;
        }
    }


    /**
     * 审核产品
     *
     * @param $uid
     * @param $prj_id
     * @param bool $is_pass 是否通过
     * @param string $desc
     * @param int $activity_id
     * @param int $client_type
     * @param array $mi_array
     * @return array
     * @throws Exception
     */
    public function verifyPrj($uid=0, $prj_id=0, $is_pass=TRUE, $desc='', $activity_id=0, $client_type=0, $mi_array = array(), $prjExt=array()) {
        if(!$is_pass && strlen($desc) < 2) {
            throw_exception('请输入原因，至少两个字');
        }
        $project = service('Financing/Project')->getById($prj_id,null,false);
        if(!$project) {
            throw_exception('产品不存在！');
        }

        // 判断是否已审核，已审核的不允许再改变status值
        if($project['status'] != self::STATUS_WATING) {
            throw_exception('您已经审核过了，不能重复审核！');
        }
        if($prjExt['is_early_repay'] && $project['time_limit_day'] > 90) throw_exception('提前还款限3个月以内');

        //企福鑫企业是否存在
        if($prjExt['company_id']){
            $company_array=explode(',',$prjExt['company_id']);
            $count_input=count($company_array);
            $all=false;
            foreach ($company_array as $val) {
                if ($val==0) {
                    $company_array=array(0);//如果选了全部，就忽略其他，只存0
                    $prjExt['company_id']='0';
                    $count_input=1;
                    $all=true;
                }
            }
            if (!$all) { //如果没有选全部就，核对企业id
                $map_company['id']=array('IN',  $company_array);
                $count = service("Financing/CompanySalary")->companyCount($map_company);
                $count!=$count_input && throw_exception('企福鑫企业数据输入错误！-输入序号和实际有效序号不一致');
            }
        }

        if($is_pass) {
            $status = self::STATUS_PASS;
        } else {
            $status = self::STATUS_FAILD;
        }
        $where = array(
            'id' => $prj_id,
            'status' => array('NEQ', $status),
        );
        $data = array(
            'verify_uid' => $uid,
            'verify_time' => time(),
            'status' => $status,
        );
        if($is_pass && $project['start_bid_time'] < time() && !is_null($project['start_bid_time'])) {
            $data['bid_status'] = self::BSTATUS_BIDING;
        }
        if(!$is_pass) {
            $data['verify_desc'] = $desc;
        }
        if(is_numeric($activity_id) && (int)$activity_id > 0) {
            $data['activity_id'] = $activity_id;
        }

        // 聚优宝可修改平台管理费
        if($project['prj_type'] == self::PRJ_TYPE_F && I('request.platform_rate')) {
            $data['platform_rate'] = I('request.platform_rate', 0)/100;
        }

        //出借人确认书协议id
        $dataExt['protocol_id'] = service('Financing/Project')->getPrjConfirmProtocolId(intval($project['zhr_apply_id']));

        $dataExt['prj_id'] = $prj_id;
        $dataExt['is_early_close'] = $project['prj_class'] == self::PRJ_CLASS_LC ? 1 : $prjExt['is_early_close'];
        $dataExt['is_early_repay'] = $prjExt['is_early_repay'];
        $dataExt['old_last_repay_date'] = $project['last_repay_date'];
        //加入企福鑫两个字段
        $dataExt['is_qfx'] = (int)$prjExt['is_qfx'];
        $dataExt['is_xzd'] = (int)$prjExt['is_xzd'];
        //2015/05 加入投之家字段
        $dataExt['is_tzj'] = 0;//(int)$prjExt['is_tzj'];
        $dataExt['company_id'] = $prjExt['company_id'] === NULL ? '0' : $prjExt['company_id'];
        $default_interest['prj_id'] = $prj_id;
        $default_interest['time_limit'] = $prjExt['di_time_limit'];
        $default_interest['time_limit_unit'] = $prjExt['di_time_limit_unit'];
        $default_interest['ratio'] = $prjExt['di_ratio'];
        $default_interest['time_limit_day'] = $default_interest['time_limit'];
        if($default_interest['time_limit_unit'] == 'month') $default_interest['time_limit_day'] = 30*$default_interest['time_limit'];
        if($default_interest['time_limit_unit'] == 'year') $default_interest['time_limit_day'] = 365 * $default_interest['time_limit'];
        $this->checkPrjExt($dataExt, $default_interest, $project['time_limit'], $project['time_limit_unit']);
        $is_notify = TRUE;
        $this->startTrans();
        try {
            // 审核
            if($this->where($where)->setField($data) === FALSE) {
                throw_exception('系统异常，请稍后再试！');
            }
            $project = service('Financing/Project')->getById($prj_id);
            $data2 = array();
            $totalYearRate = service("Account/Active")->getActiveYearRate($project);
            $data2['total_year_rate'] = $totalYearRate;
            $this->where(array("id"=>$prj_id))->setField($data2);

            //聚财富为app标
            if(in_array($mi_array['mi_no'], array('1234567992','1234567994'))) $client_type = 0;//临时丑陋的修改

            //投放渠道
            if(D('Financing/PrjFilter')->addType($uid, $prj_id, $client_type) === false){
                throw_exception('客户端类型保存异常，请稍后再试！');
            }

            // 外部渠道的截标时间
            if($mi_array['mi_no'] == '1234567991') {
                if($_REQUEST['channel_end_bid_time']) {
                    $channel_end_bid_time = strtotime($_REQUEST['channel_end_bid_time']);
                    if($channel_end_bid_time > $project['end_bid_time']) throw_exception('外部渠道截标时间不能大于项目原截标时间');
                    $dataExt['channel_end_bid_time'] = $channel_end_bid_time;
                } else {
                    throw_exception('投放站点为数米必须定义对外截标时间');
                }
            }

            // 是否给募集期利息
            if(isset($_REQUEST['is_have_biding_incoming'])) $dataExt['is_have_biding_incoming'] = (int)!!$_REQUEST['is_have_biding_incoming'];
            else $dataExt['is_have_biding_incoming'] = 1;

            //添加项目扩展信息
            $ext = M('prj_ext')->find($prj_id);
            if($ext){
                $con = M('prj_ext')->save($dataExt);
            }
            else {
                $dataExt['ctime'] = time();
                $con = M('prj_ext')->add($dataExt);
            }
            //如果是企福鑫计划，添加项目id和企业id的关联
            if ($company_array && $company_array[0]) {
                foreach ($company_array as $val) {
                    M('prj_qfx_org')->add(array('prj_id' => $prj_id, 'org_user_id' => $val));
                }
            }
            $default_interest_a = M('prj_default_interest')->where(array('prj_id'=>$prj_id))->find();
            if($dataExt['is_early_repay']){
                if($default_interest_a) {
                    $default_interest['id'] = $default_interest_a['id'];
                    $default_interest['mtime'] = time();
                    $dcon = M('prj_default_interest')->save($default_interest);
                } else {
                    $default_interest['ctime'] = time();
                    $default_interest['mtime'] = time();
                    $dcon = M('prj_default_interest')->add($default_interest);
                }
                if(FALSE === $dcon) throw_exception('系统异常：修改回款信息失败！');
            } else {
                if($default_interest_a) M('prj_default_interest')->where(array("prj_id"=>$default_interest_a['id']))->delete();
            }

            // 同步is_early_repay到prj表的is_early
            if ($project['prj_type'] == self::PRJ_TYPE_A) {          // 日升益
                $mdAddon = D('Financing/InvestPrj');
            } elseif ($project['prj_type'] == self::PRJ_TYPE_B) {    // 企升益
                $mdAddon = D('Financing/InvestLongPrj');
            } elseif ($project['prj_type'] == self::PRJ_TYPE_F) {    // 聚优宝
                $mdAddon = D('Financing/InvestJuyou');
            }
            if(isset($mdAddon)) $mdAddon->where(array('prj_id' => $prj_id))->setField(array('is_early' => (int)!!$dataExt['is_early_repay']));

            //投放站点
            /**
             * 投放站点和投放渠道/客户端组合判断
             *
             * 全部：mi_no:1234567889
             *
             * mi_no:1234567890
             * client_type:  0:all;1:pc;2:weixin-wap;3:app
             *
             *  投放站点选择“鑫合汇”，投放终端可选择“全部、网站端、手机app端、微信wap端”；
            投放站点选择其他站点或者全部，投放终端只可选择“网站端”；
            默认流程先选择“投放站点”，再选择“投放终端”；
             * 若先选择“投放终端”中“全部、手机app端、微信wap端”，则“投放站点”中只能选择“鑫合汇”；
             * 若先选择“投放终端”中“网站端”，则“投放站点”中可选择全部选项；
             */
            if( !in_array($mi_array['mi_no'], array('1234567890','1234567992','1234567994')) && in_array($client_type, array(0,2,3)) ){
                throw_exception('只能选择网站端投放！'); //待鑫合汇后台全部迁移，这块就没用了
            }

            if($project['prj_type'] == self::PRJ_TYPE_G){
                $this->where(array("id"=>$prj_id))->setField(array('mi_no'=>$mi_array['mi_no']));
                //理财项目审核
                if(!D('Financing/InvestFund')->verify($project)){
                    throw_exception(D('Financing/InvestFund')->error);
                }
            }else{
                if(D("Application/Member")->savePrjMemberInfo($mi_array) === false){
                    throw_exception('投放站点保存异常，请稍后再试！');
                }else{
                    $this->where(array("id"=>$prj_id))->setField(array('mi_no'=>$mi_array['mi_no']));

                }
                // 二三级担保公司
                $this->setGuarantorExt($prj_id, (int)$_REQUEST['guarantor2_id'], (int)$_REQUEST['guarantor3_id']);
                //$this->setPrjExtInfo($prj_id,I('post.contract_no','','trim'));
            }

            // 删除证据
//            if(!$is_pass) {
//                $this->_destory_audit_record($prj_id);
//            }
            //是否可变现
            if(service("Financing/FastCash")->is_prj_cash($prj_id)){
                if(is_object($mdAddon) && ($project['prj_type'] != self::PRJ_TYPE_A)) {
                    $is_transfer = isset($_REQUEST['is_transfer'])?$_REQUEST['is_transfer']:0;
                    $mdAddon->where(array('prj_id' => $prj_id))->save(array('is_transfer' => $is_transfer));
                }
            }
            //聚财富同步项目操作
            if(in_array($mi_array['mi_no'], array('1234567992')) && $is_pass) service("Mobile2/ApiForJucf")->putPrjInfo($prj_id);
            // 拆标, 必须最后一步
            if($_REQUEST['split_bid_data'] && $is_pass) {
                $mdSplitPrj = D('Financing/PrjSplit');
                $mdSplitPrj->addSplits($prj_id, $_REQUEST['split_bid_data']);

                $is_notify = FALSE; // 被拆母标不推送
            }

            // 协议项目变量
            if(!empty($_REQUEST['protocol_params'])) {
                $prjInfo=M('prj')->where(array('id'=>$prj_id))->find();
                $protocol=service('Financing/Project')->getProtocol($prjInfo['prj_type'],$prjInfo['zhr_apply_id'],$prj_id);

                if (service('Signature/Sign')->signSwitch() && in_array($protocol['key'],array(15,17))) {//所有接入点都签合同,协议类型是15 17。
                    $sign_order=service('Signature/Sign')->getSignOrder($prj_id);
                    if (!$sign_order) throw_exception('未获得协议中，电子签章的顺序！请设置协议中签章的顺序。');
                    $order_value="";
                    foreach ($sign_order as $p) {
                        if ($p['sign_user_id']<=0) throw_exception('该项目所需的签约用户还未申请电子签章，不能审核通过！');
                        $order_value=$order_value.$p['sign_user_id'].'|';
                    }
                    $order_value=substr($order_value,0,-1);
                    $_REQUEST['protocol_params']['step_ids']=$order_value;
                    $service=service('Signature/Sign');
                    //签章生成主合同
                    $service->create_master_contract_for_project($protocol['name'],$prjInfo['prj_name'],$prj_id,explode('|',$order_value));
                }
                service('Public/ProtocolData')->setProtocolParamPrj($prj_id, $_REQUEST['protocol_params']);
            }

            $this->commit();

            // 拆标, 必须最后一步
            if($_REQUEST['split_bid_data'] && $is_pass) {
                $mdSplitPrj = D('Financing/PrjSplit');
                $mdSplitPrj->pushSplitPrjJob($prj_id);
            }
        } catch (Exception $e) {
            $this->rollback();
            throw $e;
        }


        //20150512 暂时取消新开标提醒
        if(0 && $is_pass && $is_notify && !$ext['is_qfx']) {
            // 推送消息
            $notify_data = array();
            if (time() + 3600 >= $project['start_bid_time']) { //一小时内开标 立即推送
                $this->notifyOne($project['id'], time());
            }else{
                //当天要开标的信息 且距离现在时间大于1小时
                $map['bid_status'] = array('eq', self::BSTATUS_WATING);
                $map['status'] = array('eq', self::STATUS_PASS);
                $map['mi_no'] = $mi_array['mi_no'];
                $map['start_bid_time'] = array('gt', time() + 3600);
                $map['_string'] = 'FROM_UNIXTIME(start_bid_time, "%Y%m%d") = '.date('Ymd', $project['start_bid_time']);
                $field = 'id, prj_type, activity_id, time_limit, time_limit_unit, time_limit_day,bid_status,huodong, year_rate, rate, rate_type, start_bid_time, end_bid_time, transfer_id';
                $projects = M('prj')->field($field)->where($map)->select();
                $projectsCnt = count($projects);

                if($projectsCnt == 1){
                    $_project = service('Financing/Project')->getById($projects[0]['id']);
                    $this->notifyOne($_project, $_project['start_bid_time'] - 3600);
                }elseif($projectsCnt > 1){
                    //多个标 合并发送
                    $condition['status'] = array('eq', 1);
                    $condition['remind_type'] = array('in', array('31', '101'));
                    $condition['_string'] = 'FROM_UNIXTIME(remind_time, "%Y%m%d") = '.date('Ymd', $project['start_bid_time']);
                    M('remind')->where($condition)->delete();

                    //最高最低利率 最短最长日期
                    $_projects = $yearRate = $dateline = $startBidTime = array();
                    $reward = false;
                    foreach ($projects as $key => $value){
                        $yearRate[$value['id']] = $value['total_year_rate'];
                        $dateline[$value['id']] = $value['time_limit_day'];
                        $startBidTime[] = $value['start_bid_time'];
                        $_projects[$value['id']] = $value;
                        //红包判断
                        if(!$reward){
                            if($this->checkPrjReward($value)){
                                $reward = true;
                            }
                        }
                    }
                    $notify_data[] = $reward ? '[还有奖励哦~]，' : '';
                    $maxYearRateId = array_search(max($yearRate), $yearRate);
                    $minYearRateId = array_search(min($yearRate), $yearRate);
                    $maxDatelineId = array_search(max($dateline), $dateline);
                    $minDatelineId = array_search(min($dateline), $dateline);
                    //最多利率
                    $pjSrv = service('Financing/Project');
                    $maxRateUnit = $pjSrv->getRateType($_projects[$maxYearRateId]['rate_type']);
                    if($_projects[$maxYearRateId]['rate_type'] != self::RATE_TYPE_DAY){
                        $maxRate = sprintf('%.2f', $_projects[$maxYearRateId]['total_year_rate'] / 10).'%'.$maxRateUnit;
                    }else{
                        $maxRate = sprintf('%.2f', $_projects[$maxYearRateId]['total_year_rate']).'‰'.$maxRateUnit;
                    }
                    //最少利率
                    $minRateUnit = $pjSrv->getRateType($_projects[$minYearRateId]['rate_type']);
                    if($_projects[$minYearRateId]['rate_type'] != self::RATE_TYPE_DAY){
                        $minRate = sprintf('%.2f', $_projects[$minYearRateId]['total_year_rate'] / 10).'%'.$minRateUnit;
                    }else{
                        $minRate = sprintf('%.2f', $_projects[$minYearRateId]['total_year_rate']).'‰'.$minRateUnit;
                    }
                    //最长期限
                    $maxDatelineUnit = $pjSrv->getTimeLimitUnit($_projects[$maxDatelineId]['time_limit_unit']);
                    $maxDateline = $_projects[$maxDatelineId]['time_limit'].($maxDatelineUnit == '月' ? '个月' : $maxDatelineUnit);
                    //最短期限
                    $minDatelineUnit = $pjSrv->getTimeLimitUnit($_projects[$minDatelineId]['time_limit_unit']);
                    $minDateline = $_projects[$minDatelineId]['time_limit'].($minDatelineUnit == '月' ? '个月' : $minDatelineUnit);
                    $notify_data = array($projectsCnt, $minRate, $maxRate, $minDateline, $maxDateline, $reward ? '[还有奖励哦~]，' : '');

                    $memo['prj_id'] = '';
                    $memo['notice_type'] = 'new_bid';

                    //提醒时间
                    /* sort($startBidTime);
                    if($startBidTime[0] + 3600 < time()){
                        $pushTime = time();
                    }else{
                        $pushTime = $startBidTime[0] - 3600;
                    } */

                    service('Mobile2/Remine')->pushType101(min($startBidTime) - 3600, $notify_data, $memo);
                }
            }
            return array(1, '审核成功！');
        } else {
            return array(1, '操作成功！');
        }
    }

    /**
     * 当前标时候有红包
     * @param arr $project 标的信息
     * @return boolean
     *
     */
    private function checkPrjReward($project){
        if(!$project['transfer_id'] && $project['activity_id']){
            $activeSrv = service("Account/Active");
            $activityYearRate = $activeSrv->getOnlyActiveYearRate($project);
            if($activityYearRate){
                return true;
            }
        }
        return false;
    }

    /**
     * 撤标
     *
     * @param int $prj_id
     * @param string $desc  撤标原因
     * @return array
     * @throws Exception
     */
    public function cancelPrj($prj_id=0, $desc='') {
        if(!$desc) {
            throw_exception('请输入撤标原因！');
        }

        $project = $this->getById($prj_id);
        if(!$project) {
            throw_exception('产品不存在！');
        }
        if($project['status'] == self::STATUS_FAILD) {
            throw_exception('该产品处于不通过状态，无需撤销');
        }
        if($project['bid_status'] != self::BSTATUS_WATING) {
            throw_exception('该产品不处于待开标状态，不能撤销');
        }

        $now = time();
        $where = array(
            'id' => $prj_id,
        );
        $data = array(
            'verify_uid' => 1,
            'verify_time' => $now,
            'status' => self::STATUS_WATING,
            'verify_desc' => '撤标：' . $desc,
        );

//         $this->startTrans();
        try {
            // 撤标 -> 审核不通过
            if($this->where($where)->setField($data) === FALSE) {
                throw_exception('系统异常，请稍后再试！');
            }
            if($project['zhr_apply_id'] && FALSE === M('finance_apply')->where(array('id' => $project['zhr_apply_id']))->save(array('status' => 3))) throw_exception('更改直融申请状态失败');

            if($project['mi_no'] == C("api_info")['jcf_api_key']) service("Mobile2/ApiForJucf")->delPrj($prj_id);

            // 发消息
            $list = M("tender_intention")->where(array("prj_id"=>$prj_id))->select();
            $mUser = M('User');
            if($list){
                foreach($list as $v){
                    $user = $mUser->find($v['uid']);
                    $message = array($user['uname'], $project['prj_name'], $desc);
                    D("Message/Message")->simpleSend($v['uid'],1,self::MESSAGE_TYPE_CANCEL,$message,array(1,2,3), true);
                }

            }
            //删除mobile推送消息
            $this->delMobilePushMessage($prj_id);
//             $this->commit();
        } catch (Exception $e) {
//             $this->rollback();
            throw $e;
        }
        return array(1, '撤标成功！');
    }


    public function cancelPrjPre($prj_id=0, $desc='') {
//        if(!$desc) throw_exception('请输入撤标原因！');

        $mdPrjExt = M('prj_ext');
        $mdPrjOrderPre = M('prj_order_pre');

        $is_pre_sale = $mdPrjExt->where(array('prj_id' => $prj_id))->getField('is_pre_sale');
        if(!$is_pre_sale) throw_exception('该项目不是预售标的');

        $project = $this->where(array('id' => $prj_id))->field('id,prj_name,status,bid_status,end_bid_time,mi_no')->find();
        if(!$project) throw_exception('产品不存在！');

        if(!(
            $project['status'] == PrjModel::STATUS_PASS &&
            in_array($project['bid_status'], array(PrjModel::BSTATUS_WATING, PrjModel::BSTATUS_END))
        )) throw_exception('该项目状态不允许此操作');
//        if($project['end_bid_time'] <= time()) throw_exception('项目还未过截标时间');

        $now = time();
        $data = array(
            'verify_uid' => 1,
            'verify_time' => $now,
            'status' => self::STATUS_FAILD,
            'bid_status' => self::BSTATUS_WATING,
            'verify_desc' => '预售标取消：' . $desc,
        );

        $this->startTrans();
        try {
            // 撤标 -> 审核不通过
            if($this->where(array('id' => $prj_id))->setField($data) === FALSE) throw_exception('更改项目状态失败');

            // 取消预售投资
            if($mdPrjOrderPre->where(array(
                    'prj_id' => $prj_id,
                    'status' => 1
                ))->setField(array(
                    'status' => 3,
                )) === FALSE) throw_exception('系统异常，请稍后再试！');

            //删除mobile推送消息
//            $this->delMobilePushMessage($prj_id);
            $this->commit();

            // 发消息
            $mdMessage = D('Message/Message');
            $orders = $mdPrjOrderPre->where(array(
                'prj_id' => $prj_id,
                'status' => 3
            ))->select();
            $message = array(
                $project['prj_name'],
            );
            foreach ($orders as $order) {
//                $mdMessage->simpleSend($order['uid'], 1, self::MESSAGE_TYPE_CANCEL_PRE, $message, array(1, 2), true);
                $mdMessage->sendMessage($order['uid'], 1, self::MESSAGE_TYPE_CANCEL_PRE, $message, $order['prj_id'], 0, array(1,2), true);
                service('Mobile2/Remine')->push_uid($order['uid'], self::MESSAGE_TYPE_CANCEL_PRE, $now, $prj_id, $message, '', 0);
            }
        } catch (Exception $e) {
            $this->rollback();
            throw $e;
        }
        return TRUE;
    }

    //删除Mobile推送消息
    function delMobilePushMessage($prjId){
        if(!$prjId) return true;
        $where = array();
        $where['is_push'] = 1;
        $where['obj_id'] = $prjId;
        $where['mtype'] = array("IN",array("31","32"));
        return M("user_message")->where($where)->save(array("is_del"=>1));
    }

    /**
     * 修改平台管理费
     *
     * @param $uid
     * @param int $prj_id
     * @param $platform_fee
     * @param $platform_rate
     * @param string $expression
     * @return array
     */
    public function editPrjFee($uid, $prj_id, $platform_fee, $platform_rate, $expression='') {
        $platform_fee = fMoney($platform_fee, ''); // 单位：元
        if(!$platform_fee < 0) {
            throw_exception('平台管理费不能小于0！');
        }

        $srvFinancing = service('Financing/Financing');
        $project = $this->getById($prj_id);
        if(!$project) {
            throw_exception('产品不存在！');
        }
        if($project['pay_way'] == self::PAY_WAY_EVERY_DAY_IN_BIDING && $srvFinancing->isPrjPayed($prj_id)) {
            throw_exception('募集期每天支付方式下，项目已经发生过支付就不能修改平台管理费');
        }
        if($project['pay_way'] == self::PAY_WAY_EVERY_DAY_IN_BIDING && !empty($project['fee_expression'])) {
            throw_exception('募集期每天支付方式下，只能修改一次');
        }
        if(!in_array($project['bid_status'], array(self::BSTATUS_BIDING, self::BSTATUS_FULL, self::BSTATUS_END))) {
            throw_exception('该产品不处于待支付状态，不能修改平台管理费');
        }
        if($project['bid_status'] == self::BSTATUS_BIDING && $project['pay_way'] != self::PAY_WAY_EVERY_DAY_IN_BIDING) {
            throw_exception('投标中的只有“募集期每天支付”的才能修改平台管理费');
        }

        $where = array(
            'id' => $prj_id,
        );
        $is_free = ($platform_fee == 0/* && $project['pay_way'] == self::PAY_WAY_EVERY_DAY_IN_BIDING*/) ? 1 : 0;
        $data = array(
            'platform_fee' => $platform_fee,
            'platform_rate' => $platform_rate,
            'is_free' => $is_free,
        );
        if($expression) $data['fee_expression'] = $expression;
        if($this->where($where)->setField($data) === FALSE) {
            throw_exception('系统异常，请稍后再试！');
            // 日志
            D("Admin/ActionLog")->insertLog($uid, '修改平台管理费', $project['platform_fee'], $platform_fee);
        }

        return array(1, '修改成功！');
    }


    public function checkPrjName($prj_name) {
        $where = array(
            'prj_name' => $prj_name,
            'status' => array('NEQ', PrjModel::STATUS_FAILD),
        );
        if($_REQUEST['prj_id']) $where['id'] = array('NEQ', $_REQUEST['prj_id']);
        return !$this->where($where)->find();
    }


    /**
     * 验证回调函数，用于检测用户提交时间是否大于系统时间
     *
     * @param $value    时间字符串：Y-d-m[ H:i:s]
     * @return bool
     */
    public function checkTime($value) {
        return strtotime($value) > time();
    }


    // 截止时间必须在16点之前
    public function checkEndBidTime($vaule) {
        $time = strtotime($vaule);
        $h = (int)date('H', $time);
        $i = (int)date('i', $time);

        if($h > 16) return FALSE;
        if($h == 16 && $i > 0) return FALSE;

        return TRUE;
    }


    /**
     * 投资梯度金额范围检查
     *
     * @param $value
     * @return bool
     */
    public function check_step_bid_amount($value) {
        return in_array($value, self::$step_bid_amount_range);
    }


    public function getStats($filed='status', $exclude=array('status' => array('EQ', self::STATUS_PASS))) {
        $fields = array(
            $filed,
            'COUNT(*) AS CNT',
        );
        $ret = array(
            'counts' => array(),
            'total' => 0,
        );
        $where = array();
        if($exclude !== FALSE) {
            $where = array_merge($where, $exclude);
        }

        $rows = $this->field($fields)->where($where)->group($filed)->select();
        if(is_array($rows)) {
            $counts = array();
            foreach ($rows as $row) {
                $counts[$row[$filed]] = $row['CNT'];
                $ret['total'] += $row['CNT'];
            }
            $ret['counts'] = $counts;
        }

        return $ret;
    }


    public function changeEndBidTime($prj_id, $time) {
        $where = array(
            'id' => $prj_id,
        );
        $project = $this->where($where)->find();
        if(!$project) {
            throw_exception('项目不存在。');
        }

        if($time <= $project['end_bid_time']) {
            throw_exception('时间不符合');
        }
        if($project['bid_status'] != self::BSTATUS_BIDING) {
            throw_exception('只有项目处于投标中才能延期');
        }

        $this->startTrans();
        try {
            $srvProject = service('Financing/Project');
            // 第一步：修改end_bid_time
            $data = array(
                'end_bid_time' => $time,
            );
            if($this->where($where)->save($data) === FALSE) {
                throw_exception('修改项目字段出错');
            }

            // 第二步：更新最迟还款日
            $last_repay_date = $srvProject->getPrjEndTime($prj_id);
            $data = array(
                'last_repay_date' => $last_repay_date,
                'repay_time' =>$last_repay_date
            );
            if($this->where($where)->save($data) === FALSE) {
                throw_exception('更新最迟还款日出错（1003）');
            }

            // 第三步：order和transfer
            $mdOrder = D('Financing/PrjOrder');
            // $mdTransfer = D('Financing/AssetTransfer');
            $prj_orders = $mdOrder->where(array('prj_id' => $prj_id))->select();
            if($prj_orders) {
                foreach ($prj_orders as $v) {
                    $income = $srvProject->getIncomeById($prj_id, $v['rest_money'],$v['freeze_time']);
                    $udata = array();
                    $udata['expect_repay_time'] = $srvProject->getPrjEndTime($prj_id);
                    $udata['possible_yield'] = $income;
                    M("prj_order")->where(array("id"=>$v['id']))->save($udata);
                }
            }
            $this->commit();
        } catch (Exception $e) {
            $this->rollback();
            throw $e;
        }
    }


    // 流标
    public function rebackPrj($prj_id, $desc='') {
        ignore_user_abort(TRUE);
        set_time_limit(0);

        if(!$desc) throw_exception('请输入流标原因！');
        $project = $this->getById($prj_id);
        if(!$project) throw_exception('产品不存在！');
        if($project['repay_way'] != self::REPAY_WAY_E) throw_exception('只有新到期还本付息才可以流标');
        if($project['pay_way'] != self::PAY_WAY_AFTER_FULL) throw_exception('只有支付方式为满标后支付的才可以流标');

        $srvFinancing = service('Financing/Financing');
        if(!(time() > $project['end_bid_time'] && $project['remaining_amount'] > 0 && !$srvFinancing->isPrjPayed($prj_id))) throw_exception('只有过了募集期未满标且未支付的情况下才能流标');

        $income_detail = $srvFinancing->getIncomeDetail($prj_id, FALSE, $project);
        $total_biding = $income_detail['total_biding'];
        $total_biding_display = humanMoney($total_biding, 2, FALSE). '元';
        $deposit_display = humanMoney($project['deposit'], 2, FALSE) . '元';
        if($project['deposit'] < $total_biding) throw_exception("保证金（{$deposit_display}）不足以支付募集期利息（{$total_biding_display}），不能流标");

        $now = time();
        $mdPrjOrder = D('Financing/PrjOrder');
        $mdPrjOrderRepayPlan = D('Financing/PrjOrderRepayPlan');
        $mdPrjRepayPlan = D('Financing/PrjRepayPlan');
        $mdRemind = D('Public/Remind');
        $mdMessage = D('Message/Message');
        $mdUser = M('User');
        $svrPayAccount = service('Payment/PayAccount');
        $srvInvest = service('Financing/Invest');
        $srvProject = service('Financing/Project');

        $is_enddate = $srvInvest->isEndDate($project['repay_way']);

        $this->startTrans();
        try {
            // 1. 处理订单
            $where_order = array(
                'prj_id' => $prj_id,
                'status' => PrjOrderModel::STATUS_FREEZE,
                'is_regist' => 0,
            );
            $orders = $mdPrjOrder->where($where_order)->select();
            foreach ($orders as $order) {
                // 退款
                $svrPayAccount->reback($order['order_no']);

                //  更新用户统计，退标或撤标的时候，项目未做过支付。新的逻辑里 募集期利息是与首次还款一起支付的.
                service("Payment/UserAccountSummary")->changeAcountSummary('backbuy', $order['id'], 0,0);



//                 // 更新用户统计
//                 $is_order_plan_repayed = !!$mdPrjOrderRepayPlan->where(array('prj_order_id' => $order['id'], 'repay_periods' => 0, 'status' => PrjOrderRepayPlanModel::ORDER_REPAYMENT_STATUS_SUCCESS))->find();
//                 if ($is_order_plan_repayed) {
//                     // 如果还过募集期利息, 还款中减少
//                     if (M('UserAccountSummary')->where(array('uid' => $order['uid']))->setDec('repayIn_count', 1) === FALSE
//                         || M('UserAccountSummary')->where(array('uid' => $order['uid']))->setDec('repayIn_money', $order['rest_money']) === FALSE
//                         || M('UserAccountSummary')->where(array('uid' => $order['uid']))->setField('mtime', time()) === FALSE
//                     ) {
//                         throw_exception('更新投资者统计信息出错！');
//                     }
//                 } else {
//                     // 如果未还募集期利息, 投资中减少
//                     if (M('UserAccountSummary')->where(array('uid' => $order['uid']))->setDec('investing_prj_count', 1) === FALSE
//                         || M('UserAccountSummary')->where(array('uid' => $order['uid']))->setDec('investing_prj_money', $order['money']) === FALSE
//                         || M('UserAccountSummary')->where(array('uid' => $order['uid']))->setField('mtime', time()) === FALSE
//                     ) {
//                         throw_exception('更新投资者统计信息出错！');
//                     }
//                 }

                // 回款统计
                if(!$is_enddate) {
                    $where_order_repay_plan = array(
                        'prj_order_id' => $order['id'],
                        'repay_periods' => array('NEQ', 0),
                        'status' => PrjOrderRepayPlanModel::ORDER_REPAYMENT_STATUS_WAIT,
                    );
                    $order_repay_plans = $mdPrjOrderRepayPlan->where($where_order_repay_plan)->select();

                    // 废除订单还款计划
                    $data = array(
                        'status' => PrjOrderRepayPlanModel::ORDER_REPAYMENT_STATUS_FAIL,
                        'mtime' => $now,
                    );
                    if($mdPrjOrderRepayPlan->where($where_order_repay_plan)->save($data) === FALSE) throw_exception('批量更新个人还款计划状态失败！');

                    foreach ($order_repay_plans as $plan) {
                        $srvInvest->cutUserRepayPlan($order['id'], $plan['repay_date'], $plan['pri_interest']);
                    }

                } else {
                    $srvInvest->cutUserRepayPlan($order['id'], $project['last_repay_date'], $order['money'] + $order['possible_yield']);
                }

                // 取消到期提醒
                $is_cancel_remind33 = $is_enddate;
                if(!$is_enddate) {
                    $where_yield_biding = array(
                        'repay_periods' => 0,
                        'prj_order_id' => $order['id'],
                    );
                    $yield_biding = $mdPrjOrderRepayPlan->where($where_yield_biding)->find();
                    if($yield_biding && $yield_biding['yield']) $is_cancel_remind33 = TRUE;
                }
                if($is_cancel_remind33) {
                    $where_remind_33 = array(
                        'remind_type' => 33,
                        'obj_id' => $order['id'],
                    );
                    $data = array(
                        'is_available' => 0,
                    );
                    if($mdRemind->where($where_remind_33)->save($data) === FALSE) throw_exception('取消到期提醒失败！');
                }
            }

            // 更新订单状态
            $data = array(
                'status' => PrjOrderModel::STATUS_NOT_PAY,
                'is_liubiao' => 1,
                'mtime' => $now,
            );
            if($mdPrjOrder->where($where_order)->save($data) === FALSE) throw_exception('批量更新订单状态失败！');


            // 2. 废除项目还款计划
            $where_repay_plan = array(
                'prj_id' => $prj_id,
                'repay_periods' => array('NEQ', 0),
            );
            $data = array(
                'status' => PrjRepayPlanModel::PRJ_REPAYMENT_STATUS_FAIL,
                'mtime' => $now,
            );
            if($mdPrjRepayPlan->where($where_repay_plan)->save($data) === FALSE) throw_exception('批量更新项目还款计划状态失败！');


            // 3. 更新项目状态
            $where_prj = array(
                'id' => $prj_id,
            );
            $data = array(
                'bid_status' => self::BSTATUS_FAILD,
                'mtime' => $now,
            );
            if($this->where($where_prj)->save($data) === FALSE) throw_exception('更新项目状态失败！');

            // 4. 取消还款提醒
            $where_remind_34 = array(
                'remind_type' => 34,
                'obj_id' => $prj_id,
            );
            $data = array(
                'is_available' => 0,
            );
            if($mdRemind->where($where_remind_34)->save($data) === FALSE) throw_exception('取消还款提醒失败！');

            // 5. 扣除保证金(新)
            $prj_ext = M('prj_ext')->find($prj_id);
            if($prj_ext['prj_deposit']) $srvProject->cutDeposit($prj_id, ($prj_ext['prj_deposit']));

            $this->commit();

            // 5. 发消息
            foreach ($orders as $order) {
                $user = $mdUser->find($order['uid']);
                $message = array(
                    $user['uname'],
                    $project['prj_name'],
                    $desc,
                    humanMoney($order['rest_money'], 2, FALSE) . '元',
                );
                $mdMessage->passMessage($order['uid'], 1, self::MESSAGE_TYPE_FAILD, $message);
            }
        } catch (Exception $e) {
            $this->rollback();
            throw $e;
        }
        return array(1, '流标成功！');
    }


    // 新客标推荐
    public function doTop($prj_id, $top_type, $uid=0) {
        $project = $this->getById($prj_id);
        if(!$project) throw_exception('项目不存在');
        if(!in_array($project['bid_status'], array(self::BSTATUS_WATING, self::BSTATUS_BIDING))) throw_exception('只有待开标或投标中才能操作');
//        if(!$project['is_new']) throw_exception('只有新客标才能操作');

        $mdPrjRecommend = M('prj_recommend');
        $recommend = $mdPrjRecommend->where(array('prj_id' => $prj_id, 'type' => $top_type))->find();
        $now = time();
        if(!$recommend) {
            $data = array(
                'prj_id' => $prj_id,
                'uid' => $uid,
                'type' => $top_type,
                'is_recommend' => 1,
                'ctime' => $now,
                'mtime' => $now,
            );
            if($mdPrjRecommend->add($data) === FALSE) throw_exception('数据库异常' . $mdPrjRecommend->getLastSql());
        } else {
            $data = array(
                'uid' => $uid,
                'type' => $top_type,
                'is_recommend' => (int)!$recommend['is_recommend'],
                'mtime' => $now,
            );
            if($mdPrjRecommend->where(array('id' => $recommend['id']))->save($data) === FALSE) throw_exception('数据库异常' . $mdPrjRecommend->getLastSql());
        }

        return TRUE;
    }


    /**
     * 单推
     *
     * @param $arg
     * @param $push_time
     * @return bool
     */
    public function notifyOne($arg, $push_time) {
        if(is_numeric($arg)) $project = service('Financing/Project')->getById($arg);
        else $project = $arg;
        if(!$project) return FALSE;

        $prj_id = $project['id'];
        //获取扩展信息.如果是企福鑫项目,就直接过滤掉~~~
        $prj_model = M("PrjExt");
        $where_qfx = array(
            'prj_id'=>$prj_id,
            'is_qfx'=>1,
        );
        $count = $prj_model->where($where_qfx)->count();
        if($count){
            return false;
        }
        $notify_data[] = $project['is_new'] ? $project['prj_name'] . "（新客专属）" : $project['prj_name'];
        $notify_data[] = $project['total_year_rate_view'] . $project['rate_symbol'] . $project['rate_type_view'];
        $notify_data[] = $project['time_limit'] . $project['time_limit_unit_view'];
        $notify_data[] = date('Y-m-d H:i', $project['start_bid_time']);
        //是否有红包
        $notify_data[] = $this->checkPrjReward($project) ? '[还有奖励哦~]，' : '';

        $memo['prj_id'] = $prj_id;
        $memo['notice_type'] = 'new_bid';

        service('Mobile2/Remine')->pushType31($prj_id, $push_time, $notify_data, $memo);

        //4.05版本的本地闹铃提醒的推送标记(update 2015/03/05)去掉，实现和android一样的，不推送
//        $this->systemAlarmPush($project);


    }

    /**
     * 闹铃推送标记
     */
    public function systemAlarmPush($project)
    {
        //同时发送广播通知，针对IOS.新版本4.05以后版本,开标前10分钟提醒
        $prj_id = $project['id'];
        $projectService = service("Financing/Project");
        $type_name = $projectService->getPrjTypeName($project['prj_type']);
        $prj_name = $project['prj_name'];
        $start_bid_time = $project['start_bid_time'];
        $start_bid_time_show = date("H:i",$start_bid_time);
        $tplData = array(
            0=>"鑫合汇温馨提醒：您设置闹钟的{$prj_name}项目将于{$start_bid_time_show}开标，别迟到哟~【鑫合汇】"
        );
        D("Public/Remind");
        $memo = array(
            'prj_id'=>$prj_id,
            'prj_type'=>$project['prj_type'],
            'prj_type_name'=>$type_name,
            'notice_type'=>  RemindModel::NOTICE_TYPE_ALARM_PUSH,
            'start_bid_time'=>$start_bid_time,
        );
        $service = service('Mobile2/Remine');
        $service->pushType99($start_bid_time-10*60,$prj_id,$tplData,$memo);
    }

    /**
     * 项目是否可以支付（目前只针对直融）
     * @param int $prjId
     * @param array $prjInfo
     * @return boolean
     */
    /* function prjPayable($prjId, $prjInfo = array()){
        if(!$prjInfo){
            $prjInfo = M("prj")->find($prjId);
        }
        $zhrId = $prjInfo['zhr_apply_id'];
        $materialDelayList = C('BORROWER_MATERIAL_DELAY');
        if(is_null($zhrId) || !$materialDelayList){
            return true;
        }
        $where['apply_id'] = $zhrId;
        $where['material_type'] = array('in', $materialDelayList);
        $material = M('finance_apply_material')->where($where)->select();
        if(count($material) >= count($materialDelayList)){
            return true;
        }
        return false;
    } */

    function prjPayable($prjId){
        $spid = M('prj')->where(array('id' => $prjId))->getField('spid');
        if($spid) $prjId = $spid;
        $prjMaterialExtObj = D('Financing/PrjMaterialExt');
        $condition = array(
            'prj_id' => $prjId,
            'item_id' => PrjMaterialExtModel::CJQQRS
        );
        if($prjMaterialExtObj->where($condition)->getField('id')){
            return true;
        }
        return false;
    }

    //获取担保公司项目数量   $status  1 -在保  2-解保 $type  num- 项目数 money- 金额数
    function getDanbaoInfo($gid = 1, $status = 1, $type = 'num'){
        $where = array();
        $where['guarantor_id'] = $gid;
        $where['is_show'] = 1;
        $where['status'] = self::STATUS_PASS;
        $where['_string'] = "transfer_id IS NULL";

//        $api_info = C('API_INFO');
//        $where['mi_no'] = array(array('eq', $api_info['api_key_all']), array('eq', self::$api_key), 'or');

        if ($status == 1) {
            $where['bid_status'] = array("NEQ", self::BSTATUS_REPAID);
        } elseif ($status == 2) {
            $where['bid_status'] = self::BSTATUS_REPAID;
        }

        if ($type == 'num') {
            return $this->where($where)->count();
        } elseif ($type == 'money') {
            $result = $this->where($where)->sum('demand_amount');
            return humanMoney($result);
        }
    }


    /**
     * 新增/修改二三级担保公司
     *
     * @param $prj_id
     * @param int $guarantor2_id    二级担保公司Id
     * @param int $guarantor3_id    三级担保公司Id
     * @return bool
     */
    public function setGuarantorExt($prj_id, $guarantor2_id=0, $guarantor3_id=0) {
        if(!$guarantor2_id && !$guarantor3_id) return TRUE;

        $mdPrjExt = M('prj_ext');
        $where = array('prj_id' => $prj_id);
        $row = $mdPrjExt->where($where)->find();
        $data = array(
            'guarantor2_id' => $guarantor2_id,
            'guarantor3_id' => $guarantor3_id,
        );
        if($row){
            $ret = $mdPrjExt->where($where)->save($data);
        }else{
            $data['prj_id'] = $prj_id;
            $ret = $mdPrjExt->add($data);
        }

        if($ret === FALSE) throw_exception('更新二三级担保公司失败!');
        return TRUE;
    }

    /**
     * 设置项目扩展信息（暂时添加 合同编号字段）
     * @param $prj_id
     * @param varchar $no 合同编号
     * @return bool
     */
    public function setPrjExtInfo($prj_id,$no=''){
        if(!$no) throw_exception("合同编号为必填项");
        $mdPrjExt = M('prj_ext');
        $where = array('prj_id' => $prj_id);
        $row = $mdPrjExt->where($where)->find();
        $data = array(
            'contract_no' => $no
        );
        if($row){
            $ret = $mdPrjExt->where($where)->save($data);
        }else{
            $data['prj_id'] = $prj_id;
            $ret = $mdPrjExt->add($data);
        }

        if($ret === FALSE) throw_exception('设置合同编号失败!');
        return TRUE;
    }

    /**
     * 获取项目扩展信息
     * @param $prj_id
     * @return mixed
     */
    public function getPrjExtInfo($prj_id){
        $mdPrjExt = M('prj_ext');
        $where = array('prj_id' => $prj_id);
        $row = $mdPrjExt->where($where)->find();
        return $row;
    }

    public function getMultiPrjExtInfos($arr_prj_id = array()){
        if (!is_array($arr_prj_id)) return false;
        if (empty($arr_prj_id)) return array();

        $mdPrjExt = M('prj_ext');
        $where = array();
        if (count($arr_prj_id) == 1) {
            $where['prj_id'] = $arr_prj_id[0];
        } else {
            $where['prj_id'] = array('IN', $arr_prj_id);
        }
        $row = $mdPrjExt->where($where)->select();
        if($row){
            $row = array_column($row, null, 'prj_id');
        }
        return $row;
    }

    public function getMultiPrjInfos($arr_prj_id = array()){
        if (!is_array($arr_prj_id)) return false;
        if (empty($arr_prj_id)) return array();

        $where = array();
        if (count($arr_prj_id) == 1) {
            $where['id'] = $arr_prj_id[0];
        } else {
            $where['id'] = array('IN', $arr_prj_id);
        }
        $row = $this->where($where)->select();
        if($row){
            $row = array_column($row, null, 'id');
        }
        return $row;
    }

    /**
     * 获取二三级担保公司
     *
     * @param $prj_id
     * @return array
     */
    public function getGuarantorExt($prj_id) {
        $ret = array(
            'guarantor2' => 0,
            'guarantor3' => 0,
        );
        $mdPrjExt = M('prj_ext');
        $row = $mdPrjExt->where(array('prj_id' => $prj_id))->find();
        if(!$row) return $ret;

        $mdInvestGuarantor = M('invest_guarantor');
        if($row['guarantor2_id'] && $guarantor = $mdInvestGuarantor->where(array('id' => $row['guarantor2_id']))->find()) $ret['guarantor2'] = $guarantor;
        if($row['guarantor3_id'] && $guarantor = $mdInvestGuarantor->where(array('id' => $row['guarantor3_id']))->find()) $ret['guarantor3'] = $guarantor;
        return $ret;
    }


    /**
     * 计息时间开始
     */
    static function addInterestDate($valueDateShadow){
        $arr = array(
            '0' => '当日',
            '1' => '次日',
            '2' => '第二日',
            '3' => '第三日',
            '4' => '第四日',
            '5' => '第五日',
            '6' => '第六日',
            '7' => '第七日',
            '8' => '第八日',
            '9' => '第九日',
        );
        return $arr[$valueDateShadow] ? $arr[$valueDateShadow] : $arr[1];
    }


    /**
     * 修改截标时间
     *
     * @param $prj_id
     * @param null $end_bid_time
     * @throws Exception
     */
    public function setNewEndBidTime($prj_id, $end_bid_time=NULL) {
        $where_prj = array('id' => $prj_id);
        $project = $this->where($where_prj)->find();
        if(!$project) throw_exception('更新截标时间: 项目不存在');

        /* @var $srvProject ProjectService */
        $srvProject = service('Financing/Project');
        $is_early_close = $srvProject->isPrjCanEarlyClose($prj_id);

        if(!($end_bid_time < $project['end_bid_time'] && $project['end_bid_time'] > time() && $is_early_close)) throw_exception('项目不满足提前截标');

        $this->startTrans();
        try {
            // 修改项目信息
            $now = time();
            if (is_null($end_bid_time)) $end_bid_time = time();
            $_project = $project;
            $_project['end_bid_time'] = $end_bid_time;
            $repay_time = $srvProject->getPrjFirstRepayDate($prj_id, $_project, FALSE); // 首次还款日
            $data = array(
                'end_bid_time' => $end_bid_time,
                'next_repay_date' => $repay_time,
                'mtime' => $now,
            );
            if (FALSE === $this->where($where_prj)->save($data)) throw_exception('更新截标时间: 修改项目信息失败');

            // 第二步：更新最迟还款日
            $last_repay_date = $srvProject->getPrjEndTime($prj_id);
            $data = array(
                'last_repay_date' => $last_repay_date,
                'repay_time' =>$last_repay_date
            );
            if (FALSE === $this->where($where_prj)->save($data)) throw_exception('更新截标时间: 更新最迟还款日出错');

            $where_order = array('prj_id' => $prj_id);
            // 修改订单信息
            if($project['prj_class'] == self::PRJ_CLASS_LC){
                service('Financing/InvestFund')->setOrderNextRepayDate($prj_id, $_project['end_bid_time']);
            }else{
                $data = array(
                    'expect_repay_time' => $repay_time,
                    'mtime' => $now,
                );
                if (FALSE === M('prj_order')->where($where_order)->save($data)) throw_exception('更新截标时间: 修改订单信息失败');
            }

            // 修改订单last_repay_date
            $orders = M('prj_order')->where($where_order)->select() ;
            foreach ($orders as $order) {
                $srvProject->setOrderLastRepayDate($order['id'], $end_bid_time);
            }

            // 备份
            $mdPrjExt = M('prj_ext');
            $where_ext = array('prj_id' => $prj_id);
            $row = $mdPrjExt->where($where_ext)->find();
            $data = array(
                'old_last_repay_date' => $last_repay_date,
                'old_end_bid_time' => $project['end_bid_time'],
            );
            if ($row) {
                $ret = $mdPrjExt->where($where_ext)->save($data);
            } else {
                $data['prj_id'] = $prj_id;
                $ret = $mdPrjExt->add($data);
            }
            if ($ret === FALSE) throw_exception('更新截标时间: 备份截标时间失败');

            $this->commit();

            S('if_prj_view_' . $prj_id, NULL); // 清除项目详情页的缓存
            /*//鑫利宝项目通知java募集未满的情况,异步通知
            $row = M('prj_order')->field('SUM(money) AS money')->where(array(
                'prj_id' => $prj_id,
                'status' => array('NEQ', 4)
            ))->find();
            $remaining_amount = $project['demand_amount']-$row['money'];
            if($project['xlb_prj_id'] && $remaining_amount>0){
                $ret = service('Mobile2/XinLiBao')->async_method_call('investNoticeJava', array('prj_id'=>$prj_id,'amount'=>$row['money']));
                \Addons\Libs\Log\Logger::info("鑫利宝项目通知java募集未满的修改截标时间情况", "Financing/prjModel/setNewEndBidTime", array(array('prj_id'=>$prj_id,'amount'=>$row['money'],'ret'=>$ret)));
            }*/

            return TRUE;
        } catch (Exception $e) {
            $this->rollback();
            throw $e;
        }
    }


    /**
     * 根据类型代号获取中文名
     * @param $type
     */
    public function get_prj_type_name($type,$is_pinyin=0)
    {
        if(!$type) return false;
        switch ($type) {
            case self::PRJ_TYPE_A:
                if($is_pinyin){
                    return 'RYS';
                }
                return '日益升';
                break;
            case self::PRJ_TYPE_B:
                if($is_pinyin){
                    return 'QYR';
                }
                return '年益升';
                break;
            case self::PRJ_TYPE_F:
                if($is_pinyin){
                    return 'XYB';
                }
                return '月益升';
                break;
            case self::PRJ_TYPE_H:
                if($is_pinyin){
                    return 'SDT';
                }
                return '速兑通';
                break;
            case self::PRJ_TYPE_J:
                if($is_pinyin){
                    return 'ZQZR';
                }
                return '债权转让';
            default:
                return false;
                break;
        }
    }

    /**
     * 获取显示的项目名称 例如 聚优宝-JYB1214091202
     * 自动生成插入数据库的prj_name 例如 JYB1214091202
     * @param $prj_type
     * @param string $prj_name
     * @return string
     */
    public function get_show_prj_name($prj_type,$prj_name='',$prj_business_type_name='')
    {
        /**
         * 返回带中文的在网页显示的项目名称
         */
        $decorate = ' - ';
        $prj_type_name = $this->get_prj_type_name($prj_type);
        if(!empty($prj_name)){
            return $prj_type_name . $decorate . $prj_business_type_name . $prj_name;
        }
        /**
         * 下面是根据计数器自动生成新的prj_name
         */
        $prj_type_name_pinyin = $this->get_prj_type_name($prj_type,1);
        $today = date('Ymd');
        Import("libs.Counter.MultiCounter", ADDON_PATH);
        $oCounter = MultiCounter::init(Counter::GROUP_DEFAULT, Counter::LIFETIME_TODAY);
        $key = 'prj_name_' . $prj_type . $today;
        $number = $oCounter->incr($key)+10000;

        return $prj_type_name_pinyin . $today . $number;
    }

    /**
     * 显示是否可变现的Icon
     * @param type $projectInfo
     * @param type $_config//配置文件
     */
    public function icon_can_cash($projectInfo,$_config)
    {
        $now = time();
        //最后还款时间大于60天(投资期限)
//            if ($projectInfo['last_repay_date'] - $now >= 3600 * 24 * $_config['min_surplus_days'] && $projectInfo['end_bid_time'] - $now >= 3600 * 24 * $_config['min_have_days'])
//            {
//                $time_is_ok = 1;//时间判定可以
//            }
//            else
//            {
//               $time_is_ok = 0;//最后还款时间比60天小 
//            }

        $time_is_ok = 1;
        //是否可变现
        if((isset($projectInfo['ext']['is_transfer']) && $projectInfo['ext']['is_transfer'] && $time_is_ok)
            || (
                $projectInfo['prj_type'] == PrjModel::PRJ_TYPE_H &&
                $projectInfo['act_prj_ext']['cash_level'] <= $_config['cash_level_limit'] &&
                $time_is_ok
            )
        ){
            $can_bianxian = (string)1;
        }else{
            $can_bianxian = (string)0;
        }
        return $can_bianxian;
    }

    /**
     * 根据当前的项目ID获取父类的prj_id
     * @param type $prj_id
     */
    public function getParentPrjId($prj_id)
    {
        $where = array(
            'id'=>$prj_id,
        );
        $count = $this->where($where)->count();
        if(!$count){
            throw_exception("项目不存在!请刷新后重试");
        }
        return $this->where($where)->getField("p_prj_id");
    }

    public function get_repay_way_name($way)
    {
        switch ($way) {
            case self::REPAY_WAY_ENDATE:
                return '到期还本付息';
                break;
            case self::REPAY_WAY_D:
                return '按月付息，到期还本';
                break;
            case self::REPAY_WAY_E:
                return '到期还本付息';
                break;
            case self::REPAY_WAY_PERMONTH:
                return '每月等额本息还款';
                break;
            default:
                throw_exception('系统当前不支持该还款方式');
                break;
        }
    }

    // 车贷流标
    public function rebackPrj2($prj_id, $desc='') {
        ignore_user_abort(TRUE);
        set_time_limit(0);

        if(!$desc) throw_exception('请输入流标原因！');
        $project = $this->getById($prj_id);
        if(!$project) throw_exception('产品不存在！');
//        if($project['repay_way'] != self::REPAY_WAY_E) throw_exception('只有新到期还本付息才可以流标');
//        if($project['pay_way'] != self::PAY_WAY_AFTER_FULL) throw_exception('只有支付方式为满标后支付的才可以流标');
        if(date('y-m-d', $project['start_bid_time']) != date('y-m-d', $project['end_bid_time'])) throw_exception('必须没有募集期利息的才可以流标');

        $srvFinancing = service('Financing/Financing');
        if(!(time() > $project['end_bid_time'] && $project['remaining_amount'] > 0 && !$srvFinancing->isPrjPayed($prj_id))) throw_exception('只有过了募集期未满标且未支付的情况下才能流标');

//        $income_detail = $srvFinancing->getIncomeDetail($prj_id, FALSE, $project);
//        $total_biding = $income_detail['total_biding'];
//        $total_biding_display = humanMoney($total_biding, 2, FALSE). '元';
//        $deposit_display = humanMoney($project['deposit'], 2, FALSE) . '元';
//        if($project['deposit'] < $total_biding) throw_exception("保证金（{$deposit_display}）不足以支付募集期利息（{$total_biding_display}），不能流标");

        $now = time();
        $mdPrjOrder = D('Financing/PrjOrder');
        $mdPrjOrderRepayPlan = D('Financing/PrjOrderRepayPlan');
        $mdPrjRepayPlan = D('Financing/PrjRepayPlan');
        $mdRemind = D('Public/Remind');
        $mdMessage = D('Message/Message');
        $mdUser = M('User');
        $svrPayAccount = service('Payment/PayAccount');
        $srvInvest = service('Financing/Invest');
        $srvProject = service('Financing/Project');

        $this->startTrans();
        try {
            // 1. 处理订单
            $where_order = array(
                'prj_id' => $prj_id,
                'status' => PrjOrderModel::STATUS_FREEZE,
                'is_regist' => 0,
            );
            $orders = $mdPrjOrder->where($where_order)->select();
            foreach ($orders as $order) {
                // 退款
                $svrPayAccount->reback($order['order_no']);


                //  更新用户统计，退标或撤标的时候，项目未做过支付。新的逻辑里 募集期利息是与首次还款一起支付的.
                service("Payment/UserAccountSummary")->changeAcountSummary('backbuy', $order['id'], 0,0);

//                 // 更新用户统计
//                 $is_order_plan_repayed = !!$mdPrjOrderRepayPlan->where(array('prj_order_id' => $order['id'], 'repay_periods' => 0, 'status' => PrjOrderRepayPlanModel::ORDER_REPAYMENT_STATUS_SUCCESS))->find();
//                 if ($is_order_plan_repayed) {
//                     // 如果还过募集期利息, 还款中减少
//                     if (M('UserAccountSummary')->where(array('uid' => $order['uid']))->setDec('repayIn_count', 1) === FALSE
//                         || M('UserAccountSummary')->where(array('uid' => $order['uid']))->setDec('repayIn_money', $order['rest_money']) === FALSE
//                         || M('UserAccountSummary')->where(array('uid' => $order['uid']))->setField('mtime', time()) === FALSE
//                     ) {
//                         throw_exception('更新投资者统计信息出错！');
//                     }
//                 } else {
//                     // 如果未还募集期利息, 投资中减少
//                     if (M('UserAccountSummary')->where(array('uid' => $order['uid']))->setDec('investing_prj_count', 1) === FALSE
//                         || M('UserAccountSummary')->where(array('uid' => $order['uid']))->setDec('investing_prj_money', $order['money']) === FALSE
//                         || M('UserAccountSummary')->where(array('uid' => $order['uid']))->setField('mtime', time()) === FALSE
//                     ) {
//                         throw_exception('更新投资者统计信息出错！');
//                     }
//                 }

                // 回款统计
                $srvInvest->cutUserRepayPlan($order['id'], $project['last_repay_date'], $order['money'] + $order['possible_yield']);

                // 取消到期提醒
                $is_cancel_remind33 = FALSE;
                $where_yield_biding = array(
                    'repay_periods' => 0,
                    'prj_order_id' => $order['id'],
                );
                $yield_biding = $mdPrjOrderRepayPlan->where($where_yield_biding)->find();
                if($yield_biding && $yield_biding['yield']) $is_cancel_remind33 = TRUE;

                if($is_cancel_remind33) {
                    $where_remind_33 = array(
                        'remind_type' => 33,
                        'obj_id' => $order['id'],
                    );
                    $data = array(
                        'is_available' => 0,
                    );
                    if($mdRemind->where($where_remind_33)->save($data) === FALSE) throw_exception('取消到期提醒失败！');
                }
            }

            // 更新订单状态
            $data = array(
                'status' => PrjOrderModel::STATUS_NOT_PAY,
                'is_liubiao' => 1,
                'mtime' => $now,
            );
            if($mdPrjOrder->where($where_order)->save($data) === FALSE) throw_exception('批量更新订单状态失败！');

            if($project['zhr_apply_id'] > 0) {
                $mdFinanceApply = D('Zhr/FinanceApply');
                $apply_info = $mdFinanceApply->where(array('id' => $project['zhr_apply_id']))->find();
                if($apply_info && !$apply_info['have_children'] && FALSE === $mdFinanceApply->where(array('id' => $project['zhr_apply_id']))->save(array('status' => FinanceApplyModel::STATUS_END))) throw_exception('更改融资申请状态失败');
            }

            // 2. 废除项目还款计划
            $where_repay_plan = array(
                'prj_id' => $prj_id,
                'repay_periods' => array('NEQ', 0),
            );
            $data = array(
                'status' => PrjRepayPlanModel::PRJ_REPAYMENT_STATUS_FAIL,
                'mtime' => $now,
            );
            if($mdPrjRepayPlan->where($where_repay_plan)->save($data) === FALSE) throw_exception('批量更新项目还款计划状态失败！');

            // 重新生成项目还款计划
            $Pay = service('Financing/Project')->getTradeObj($prj_id, 'Pay');
            if (!$Pay) {
                throw_exception(MyError::lastError());
            }
            $Pay->savePrjRepaymentPlan(array('prj_id' => $prj_id));

            // 3. 更新项目状态
            $where_prj = array(
                'id' => $prj_id,
            );
            $data = array(
                'bid_status' => self::BSTATUS_FAILD,
                'mtime' => $now,
            );
            if($this->where($where_prj)->save($data) === FALSE) throw_exception('更新项目状态失败！');

            // 4. 取消还款提醒
            $where_remind_34 = array(
                'remind_type' => 34,
                'obj_id' => $prj_id,
            );
            $data = array(
                'is_available' => 0,
            );
            if($mdRemind->where($where_remind_34)->save($data) === FALSE) throw_exception('取消还款提醒失败！');

            // 5. 扣除保证金(新)
            $prj_ext = M('prj_ext')->find($prj_id);
            if($prj_ext['prj_deposit']) $srvProject->cutDeposit($prj_id, ($prj_ext['prj_deposit']));

            $this->commit();

            // 5. 发消息
            foreach ($orders as $order) {
                $user = $mdUser->find($order['uid']);
                $message = array(
                    $user['uname'],
                    $project['prj_name'],
                    urldecode($desc),
                    humanMoney($order['rest_money'], 2, FALSE) . '元',
                );
                $mdMessage->passMessage($order['uid'], 1, self::MESSAGE_TYPE_FAILD, $message,0,0);
            }
        } catch (Exception $e) {
            $this->rollback();
            throw $e;
        }

        return TRUE;
    }

    /**
     * 根据担保公司ID获取该担保公司的项目个数
     */
    public function getPrjCountByGuarantorId($guarantorId){
        if(!($guarantorId = intval($guarantorId))){
            return 0;
        }
        //指定担保下有没有项目
        $condition = array(
            'status' => PrjModel::STATUS_PASS,
            'guarantor_id' => $guarantorId,
            'is_show' => 1,
        );
        $prjCnt = M('prj')->where($condition)->count();
        return $prjCnt;
    }


    public function getParentBidStatus($prj_id, $bid_status) {
        $srvProject = service('Financing/Project');
        $ids = $srvProject->getChildrenId($prj_id);
        if(!$ids) return $bid_status;

        $projects = $this->where(array('id' => array('IN', $ids)))->field('id,bid_status')->select();
        foreach ($projects as $project) {
            if(in_array($project['bid_status'], array(self::BSTATUS_REPAYING, self::BSTATUS_REPAY_IN, self::BSTATUS_REPAID))) {
                $bid_status = $project['bid_status'];
                break;
            }
        }

        return $bid_status;
    }


    public function changeBindBank($where, $bank_id) {
        import_addon('trade.Permonth.Permonth_Pay');

        $srvAccount = service('Account/Account');
        $user_login = $srvAccount->getLoginedUserInfo();
        if(!$user_login) throw_exception('未登陆2');

        $mdLoanbankAccount = M('loanbank_account');
        $bank = $mdLoanbankAccount->where(array('id' => $bank_id))->field('uid,is_active')->find();
        if(!$bank) throw_exception("银行卡信息不存在, id: {$bank_id}");
        if($user_login['uid'] != $bank['uid']) throw_exception("变更后的银行卡必须属于融资申请者本人, id={$bank_id}");

        $extend_mapping = array(
            self::PRJ_TYPE_A => 'invest_prj',
            self::PRJ_TYPE_B => 'invest_long_prj',
            self::PRJ_TYPE_F => 'invest_juyou_prj',
        );
        $projects = $this->where($where)->field('id,uid,prj_type,bid_status,have_children')->select();
        foreach ($projects as $project) {
            $prj_id = $project['id'];
            if($user_login['uid'] != $project['uid']) throw_exception("必须项目所有者本人才能执行此操作, id={$prj_id}");

            $bid_status_parent = $this->getParentBidStatus($prj_id, $project['bid_status']);
            if(in_array($bid_status_parent, array(self::BSTATUS_REPAYING, self::BSTATUS_REPAY_IN, self::BSTATUS_REPAID))) {
                throw_exception("有项目不处于融资中状态，不能变更");
            }
            if(S(Pay::PRJ_PAY_LOCKER_PREFIX . $prj_id)) throw_exception("有项目正在支付中，不能变更，id={$prj_id}");

            $prj_type = $project['prj_type'];
            if(!$prj_type) continue; // java未审核的
            if(!array_key_exists($prj_type, $extend_mapping)) throw_exception("未支持的项目类型, id={$prj_id}");
            $ret = M($extend_mapping[$prj_type])->where(array('prj_id' => $prj_id))->save(array('fund_account' => $bank_id));
            if($ret === FALSE) throw_exception("更改项目资金转入帐户失败，id={$prj_id}");
        }

        return TRUE;
    }


    public function genCombineOrder($prj_id) {
        $row = $this->where(array('id' => $prj_id))->field('bid_status,prj_type,transfer_id,prj_sort,is_new,mi_no,start_bid_time,transfer_time,actual_repay_time')->find();
        if(!$row) return true;

        $pre  = '999';
        $tail = '9999999999';
        $pre_length  = 3;
        $tail_length = 10;

        if($row['bid_status'] ==  self::BSTATUS_BIDING && $row['prj_type'] == self::PRJ_TYPE_H && $row['mi_no'] == '1234567890') $pre = '401';

        elseif($row['bid_status'] == self::BSTATUS_BIDING && !$row['transfer_id'] && $row['prj_sort'] > 0 && in_array($row['mi_no'], array('1234567889', '1234567890'))) $pre = '100';
        elseif($row['bid_status'] == self::BSTATUS_BIDING && !$row['transfer_id'] && $row['prj_sort'] == 0 && $row['is_new'] == 0 && in_array($row['mi_no'], array('1234567889', '1234567890'))) $pre = '200';
        elseif($row['bid_status'] == self::BSTATUS_BIDING && !$row['transfer_id'] && $row['prj_sort'] == 0 && $row['is_new'] == 1 && in_array($row['mi_no'], array('1234567889', '1234567890'))) $pre = '300';

        elseif($row['bid_status'] == self::BSTATUS_BIDING && !$row['transfer_id'] && $row['prj_sort'] > 0 && !in_array($row['mi_no'], array('1234567889', '1234567890','1234567992'))) $pre = '410';
        elseif($row['bid_status'] == self::BSTATUS_BIDING && !$row['transfer_id'] && $row['prj_sort'] == 0 && $row['is_new'] == 0 && !in_array($row['mi_no'], array('1234567889', '1234567890','1234567992'))) $pre = '420';
        elseif($row['bid_status'] == self::BSTATUS_BIDING && !$row['transfer_id'] && $row['prj_sort'] == 0 && $row['is_new'] == 1 && !in_array($row['mi_no'], array('1234567889', '1234567890','1234567992'))) $pre = '430';

        elseif($row['bid_status'] == self::BSTATUS_WATING && !$row['transfer_id'] && in_array($row['mi_no'], array('1234567889', '1234567890'))) $pre = '500';
        elseif($row['bid_status'] == self::BSTATUS_WATING && !$row['transfer_id'] && !in_array($row['mi_no'], array('1234567889', '1234567890','1234567992'))) $pre = '510';
        elseif(in_array($row['bid_status'], array(self::BSTATUS_FULL, self::BSTATUS_END)) && !$row['transfer_id']) $pre = '600';
        elseif(in_array($row['bid_status'], array(self::BSTATUS_REPAY_IN, self::BSTATUS_REPAYING)) && !$row['transfer_id']) $pre = '800';
        elseif($row['bid_status'] == self::BSTATUS_REPAID && !$row['transfer_id']) $pre = '900';

        if($pre == '100') $tail = $row['prj_sort'];
        elseif($pre == '200') $tail = $row['start_bid_time'];
        elseif($pre == '300') $tail = $row['start_bid_time'];
        elseif(in_array($pre, array('400' , '401', '410', '420', '430', '440'))) $tail = $row['start_bid_time'];
        elseif(in_array($pre, array('500' , '510'))) $tail = $row['start_bid_time'];
        elseif($pre == '600') $tail = 9999999999 - $row['start_bid_time'];
        elseif($pre == '700') $tail = 9999999999 - $row['transfer_time'];
        elseif($pre == '800') $tail = $row['start_bid_time'];
        elseif($pre == '900') $tail = 9999999999 - $row['actual_repay_time'];

        $pre = str_pad($pre, $pre_length, '0', STR_PAD_LEFT);
        $tail = str_pad($tail, $tail_length, '0', STR_PAD_LEFT);
        $value = $pre . $tail;

        $this->where(array('id' => $prj_id))->save(array('combine_order' => $value));

        return $value;
    }

    public function genCombineOrderMulti($where) {
        $rows = $this->where($where)->field('id')->select();
        foreach ($rows as $row) {
            $this->genCombineOrder($row['id']);
        }

        return true;
    }
}

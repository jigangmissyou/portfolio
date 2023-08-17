<?php
/**
 * 拆标
 *
 * Created by PhpStorm.
 * User: channing
 * Date: 21/08/2014
 * Time: 11:15
 */
class PrjSplitModel extends BaseModel {
    protected $tableName = 'prj_split';

    const STATUS_WAITING = 1;   // 待拆标
    const STATUS_SPLITED = 2;   // 已拆标
    const STATUS_PUBLISHED = 3; // 已发布

    const PUBLISH_TYPE_TIME = 1; // 按时间
    const PUBLISH_TYPE_RATE = 2; // 按满标率

    private $mdPrj;
    private $mdPrjRaw;
    private $parent_project_data;
    private $split_num = 0;
    private $children_group = array();
    private $children_time_stack = array();

    public $debug = FALSE;
    public $debug_nl = '<hr>';

    static $step_bid_amount_range = array(50, 100, 1000, 10000, 100000); // 投资梯度金额范围
    protected $_validate = array(
        array('prj_name', 'require', '项目名称必填'),
        array('prj_name', '2,20', '项目名称必须2到10个字符', self::EXISTS_VALIDATE, 'length'),
        array('prj_name','checkPrjName','项目名称和已有项目重复', self::EXISTS_VALIDATE, 'callback'),
        array('demand_amount', 'require', '融资规模必填'),
//        array('demand_amount', 'number', '融资规模必须为1万的整数倍'),
        array('demand_amount', 'is_numeric', '融资规模必须为金额数字', self::EXISTS_VALIDATE ,'function'),

        array('publish_time', '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/i', '发布时间格式错误，正确格式：2013-11-14 12:00:00', self::VALUE_VALIDATE, 'regex'),
        array('publish_time','checkTime','发布时间必须大于当前时间', self::VALUE_VALIDATE, 'callback'),
        array('start_bid_percent', 'number', '满标率必须为整数'),
//        array('start_bid_time', 'number', '开标时间必须为整数'),

        array('min_bid_amount', 'require', '投资起始金额必填'),
        array('min_bid_amount', 'number', '投资起始金额必为数字'),
        array('min_bid_amount', 'gtZero','投资起始金额必须大于0', self::EXISTS_VALIDATE, 'function'),

        array('max_bid_amount', 'number', '最大投资金额必为数字', self::VALUE_VALIDATE),
        array('max_bid_amount', 'gteZero','最大投资金额必须大于或等于0', self::VALUE_VALIDATE, 'function'),

        array('step_bid_amount', 'require', '投资递增金额必填'),
        array('step_bid_amount', 'number', '投资递增金额必须为数字'),
        array('step_bid_amount', 'gtZero','投资递增金额必须大于0', self::EXISTS_VALIDATE, 'function'),
        array('step_bid_amount', 'check_step_bid_amount','投资递增金额不在选择范围内', self::EXISTS_VALIDATE, 'callback'),
    );


    public function _initialize() {
        parent::_initialize();

        $this->mdPrj = D('Financing/Prj');
        $this->mdPrjRaw = M('prj');
        $this->parent_project_data = NULL;
    }


    private function debug_trace($m='') {
        if(!$this->debug) return;
        echo $this->debug_nl;
        echo $m;
    }


    public function create($data='', $type='') {
        if($data['publish_type'] != self::PUBLISH_TYPE_TIME) unset($data['publish_time']);
        $data = parent::create($data, $type);
        if(!$data) {
            throw_exception($this->getError());
        }

        // 自定义验证
        $prj_name = "{$data['prj_name']}, ";
        if($data['publish_type'] == self::PUBLISH_TYPE_TIME && !preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/i', $data['start_bid_time'])) throw_exception($prj_name . '开标时间格式错误');
        if($data['publish_type'] == self::PUBLISH_TYPE_RATE && !preg_match('/^\d+$/', $data['start_bid_time'])) throw_exception($prj_name . '开标时间必须为整数');
        if($data['publish_type'] == self::PUBLISH_TYPE_TIME && !$data['publish_time']) throw_exception($prj_name . '请选择发布时间');
        if($data['publish_type'] == self::PUBLISH_TYPE_RATE && !$data['start_bid_percent']) throw_exception($prj_name . '请填写满标率');
        if(!in_array($data['mi_no'], array(C('MI_NO_INFO.api_key'))) && in_array($data['client_type'], array(0, 2, 3))) throw_exception($prj_name . '只能选择网站端投放!');


        if(isset($data['min_bid_amount']) && isset($data['demand_amount'])) {
            if($data['min_bid_amount'] > $data['demand_amount'] * 10000) {
                throw_exception($prj_name . '投资起始金额不能大于融资规模');
            }
        }

        if(isset($data['max_bid_amount']) && isset($data['demand_amount'])) {
            if($data['max_bid_amount'] > $data['demand_amount'] * 10000) {
                throw_exception($prj_name . '最大投资金额不能大于融资规模');
            }
        }

        if(isset($data['min_bid_amount']) && isset($data['step_bid_amount'])) {
            if($data['min_bid_amount'] < $data['step_bid_amount']) {
                throw_exception($prj_name . '投资递增金额不能大于投资起始金额');
            }
        }

        if (isset($data['min_bid_amount']) && isset($data['max_bid_amount']) && !empty($data['min_bid_amount']) && !empty($data['max_bid_amount'])) {
            if ($data['max_bid_amount'] <= $data['min_bid_amount']) {
                throw_exception($prj_name . '最大投资金额必须大于投资起始金额');
            }
        }

        return $data;
    }


    public function checkPrjName($prj_name) {
        return !$this->mdPrjRaw->where(array('prj_name' => $prj_name))->find();
    }


    public function checkTime($value) {
        return strtotime($value) > time();
    }


    public function check_step_bid_amount($value) {
        return in_array($value, self::$step_bid_amount_range);
    }


    /**
     * 添加拆分规则
     *
     * @param $parent_prj_id
     * @param int $depend_id
     * @param array $input
     * @return mixed
     */
    private function addSplit($parent_prj_id, $depend_id=0, $input=array()) {
        $now = time();
        $data = array(
            'parent_prj_id' => $parent_prj_id,
            'status' => self::STATUS_WAITING,
            'prj_id' => 0,
            'prj_name' => $input['sp_prj_name'],
            'demand_amount' => $input['sp_demand_amount'],
            'min_bid_amount' => $input['min_bid_amount'],
            'max_bid_amount' => $input['max_bid_amount'],
            'step_bid_amount' => $input['step_bid_amount'],
            'is_new' => (int)!!$input['sp_is_new'],
            'activity_id' => (int)$input['sp_activity_id'],

            'client_type' => $input['sp_client_type'],
            'mi_no' => $input['sp_mi_no'],
            'mi_rate_type' => $input['sp_mi_rate_type'],
            'mi_rate' => $input['sp_mi_rate'],

            'publish_type' => $input['sp_publish_type'],
            'publish_time' => $input['sp_publish_time'],
            'start_bid_time' => $input['sp_start_bid_time'],
            'start_bid_percent' => (int)$input['sp_start_bid_percent'],
            'depend_id' => $depend_id,
            'ctime' => $now,
            'mtime' => $now,
        );
        if(!$data['max_bid_amount']) $data['max_bid_amount'] = 0;

        $data = $this->create($data);
        if($this->where(array(
            'parent_prj_id' => $parent_prj_id,
            'prj_name' => $data['prj_name'],
        ))->find()) throw_exception('已经存在一个同名的子标了');

        $data['publish_time'] = strtotime($data['publish_time']);
        if($data['publish_type'] == self::PUBLISH_TYPE_TIME) $data['start_bid_time'] = strtotime($data['start_bid_time']);
        else $data['start_bid_time'] = (int)$data['start_bid_time'];
        $data['demand_amount'] = fMoney($data['demand_amount']);
        $data['min_bid_amount'] = fMoney($data['min_bid_amount'], '');      // 单位：元
        $data['max_bid_amount'] = fMoney($data['max_bid_amount'], '');      // 单位：元
        $data['step_bid_amount'] = fMoney($data['step_bid_amount'], '');    // 单位：元

        $split_id = $this->add($data);
        if($split_id === FALSE) throw_exception('系统错误, 添加拆分规则失败');

        return $split_id;
    }


    /**
     * 检验拆分数据
     *
     * @param $prj_id
     * @param array $inputs
     * @return bool
     */
    private function checkSplits($prj_id, $inputs=array()) {
        $project = $this->mdPrj->where(array('id' => $prj_id))->find();
        if(!$project) throw_exception('母项目不存在');

        $total_amount = 0;
        foreach($inputs as $input) {
            $total_amount += $input['sp_demand_amount'];
        }
        $total_amount = (int)fMoney($total_amount);
        $row = $this->where(array('parent_prj_id' => $prj_id))->field('SUM(demand_amount) AS TT')->find();
        $total_amount += (int)$row['TT'];
        if($total_amount != $project['demand_amount']) throw_exception('所有子标募集金额之应该等于母标的募集金额');

        return TRUE;
    }


    /**
     * 生成拆分规则
     *
     * @param int $prj_id
     * @param array $inputs
     * @return array
     */
    public function addSplits($prj_id, $inputs=array()) {
        $this->checkSplits($prj_id, $inputs);

        $split_id = 0;
        $split_ids = array();
        foreach($inputs as $input) {
            $split_id = $this->addSplit($prj_id, $input['sp_publish_type'] == self::PUBLISH_TYPE_RATE ? $split_id : 0, $input);
            $split_ids[] = $split_id;
        }

        if($this->mdPrj->where(array('id' => $prj_id))->save(array('status' => PrjModel::STATUS_SPLITED_PARENT)) === FALSE) throw_exception('修改项目状态失败1');
        return $split_ids;
    }


    /**
     * 根据一条拆分规则克隆项目
     *
     * @param $split_data
     * @return bool
     * @throws Exception
     */
    private function clonePrj($split_data) {
        if($split_data['status'] != self::STATUS_WAITING) return TRUE;
        if($this->parent_project_data) {
            $project = $this->parent_project_data;
        } else {
            $project = $this->mdPrj->where(array('id' => $split_data['parent_prj_id']))->find();
            $this->parent_project_data = $project;
        }
        if(!$project) throw_exception('父项目不存在');
        $prj_id_old = $project['id'];
        if(!$prj_id_old) throw_exception('异常：父项目Id为0');

        $mdAddonA = D('Financing/InvestPrj'); // 日益升
        $mdAddonB = D('Financing/InvestLongPrj'); // 年益升
        $mdAddonF = D('Financing/InvestJuyou'); // 聚优宝--->月益升
        $mdPrjCorp = D('Financing/PrjCorp'); // 企业
        $mdPrjPerson = D('Financing/PrjPerson'); // 个人
        $mdAuditRecord = D('Application/AuditRecord'); // 审核记录
        $mdCorpMaterial = D('Application/CorpMaterial'); // 审核材料
        $mdPrjFilter = D('Financing/PrjFilter'); // 投放渠道
        $mdPrjMember = D('Application/Member'); // 投放站点
        $mdPrjExt = M('prj_ext'); //
        $mdPrjDefaultInterest = M('prj_default_interest'); // 项目表罚息

        $this->debug_trace(">>>开始拆分第{$this->split_num}条");
        try {
            // 基本信息
            $this->debug_trace('克隆项目基本信息');
            unset($project['id']);
            $project['prj_name'] = $split_data['prj_name'];
            $project['demand_amount'] = $split_data['demand_amount'];
            $project['remaining_amount'] = $split_data['demand_amount'];
            $project['min_bid_amount'] = $split_data['min_bid_amount'];
            $project['max_bid_amount'] = $split_data['max_bid_amount'];
            $project['step_bid_amount'] = $split_data['step_bid_amount'];
            $project['is_new'] = $split_data['is_new'];
            $project['activity_id'] = $split_data['activity_id'];
            $project['status'] = PrjModel::STATUS_SPLITED_CHILDREN;
            $project['bid_status'] = PrjModel::BSTATUS_WATING;
            $project['prj_no'] = $this->mdPrj->genPrjNo($project['prj_type'], $project['prj_series']);
            if($split_data['publish_type'] == self::PUBLISH_TYPE_TIME) $project['ctime'] = $split_data['publish_time'];
            else $project['ctime'] = 0;
            $project['start_bid_time'] = 2145888000;
            $project['have_children'] = 0;
            $project['spid'] = $prj_id_old;
            $project['is_show'] = !isset($split_data['is_show']) ? 1 : (int)$split_data['is_show'];

            // 特殊活动, 需要隐藏项目
            if($project['activity_id']) {
                $is_prj_hide = M('reward_rule')->where(array('id' => $project['activity_id']))->getField('is_prj_hide');
                if($is_prj_hide) {
                    $project['is_show'] = 0;
                }
            }
            $prj_id = $this->mdPrjRaw->add($project);
            if($prj_id === FALSE) throw_exception('克隆项目基本信息失败' . $this->mdPrjRaw->getError());

            $where = array('prj_id' => $prj_id_old);

            // 附加信息
            $this->debug_trace('克隆附加信息, 项目类型: ' . $project['prj_type']);
            if($project['prj_type'] == PrjModel::PRJ_TYPE_A) {
                $_data = $mdAddonA->where($where)->find();
                if($_data) {
                    unset($_data['id']);
                    $_data['prj_id'] = $prj_id;
                    $_data = $mdAddonA->create($_data);
                    if($mdAddonA->add($_data) === FALSE) throw_exception('克隆附加信息失败' . PrjModel::PRJ_TYPE_A);
                }
            } elseif ($project['prj_type'] == PrjModel::PRJ_TYPE_B) {
                $_data = $mdAddonB->where($where)->find();
                if($_data) {
                    unset($_data['id']);
                    $_data['prj_id'] = $prj_id;
                    $_data = $mdAddonB->create($_data);
                    if($mdAddonB->add($_data) === FALSE) throw_exception('克隆附加信息失败' . PrjModel::PRJ_TYPE_B);
                }
            } elseif ($project['prj_type'] == PrjModel::PRJ_TYPE_F) {
                $_data = $mdAddonF->where($where)->find();
                if($_data) {
                    unset($_data['id']);
                    $_data['prj_id'] = $prj_id;
                    $_data = $mdAddonF->create($_data);
                    if($mdAddonF->add($_data) === FALSE) throw_exception('克隆附加信息失败' . PrjModel::PRJ_TYPE_F);
                }
            }

            // 融资方信息
            if($project['borrower_type'] == PrjModel::BORROWER_TYPE_COMPANY) {
                $_data = $mdPrjCorp->where($where)->find();
                if($_data) {
                    unset($_data['id']);
                    $_data['prj_id'] = $prj_id;
                    $_data = $mdPrjCorp->create($_data);
                    if($mdPrjCorp->add($_data) === FALSE) throw_exception('克隆融资方信息(企业)失败');
                }
            } elseif ($project['borrower_type'] == PrjModel::BORROWER_TYPE_PERSONAL) {
                $_data = $mdPrjPerson->where($where)->find();
                if($_data) {
                    unset($_data['id']);
                    $_data['prj_id'] = $prj_id;
                    $_data = $mdPrjPerson->create($_data);
                    if($mdPrjPerson->add($_data) === FALSE) throw_exception('克隆融资方信息(个人)失败');
                }
            }

            // 审核记录
            $this->debug_trace('克隆审核记录');
            $records = $mdAuditRecord->where($where)->select();
            $num = 0;
            foreach($records as $record) {
                $this->debug_trace('审核记录: ' . $num . ', id: ' . $record['id'] . ', name:' . $record['name']);
                $record_id_old = $record['id'];
                unset($record['id']);
                $record['prj_id'] = $prj_id;
                $record = $mdAuditRecord->create($record);
                $record_id = $mdAuditRecord->add($record);
                if($record_id === FALSE) throw_exception('克隆审核记录失败' . $record_id_old);

                $num2 = 0;
                $materias = $mdCorpMaterial->where(array('audit_record_id' => $record_id_old))->select();
                if($materias) $this->debug_trace('克隆[' . $record['name'] . ']的上传材料');
                foreach($materias as $materia) {
                    $this->debug_trace('上传材料: ' . $num2 . ', id: ' . $materia['id'] . ', name:' . $materia['name']);
                    $materia_id_old = $materia['id'];
                    unset($materia['id']);
                    $materia['audit_record_id'] = $record_id;
                    $materia = $mdCorpMaterial->create($materia);
                    if($mdCorpMaterial->add($materia) === FALSE) throw_exception('克隆上传材料失败' . $materia_id_old);

                    $num2 += 1;
                }

                $num += 1;
            }

            // 投放渠道
            $this->debug_trace('克隆投放渠道');
            if($mdPrjFilter->addType(1, $prj_id, $split_data['client_type']) === FALSE) throw_exception('克隆投放渠道失败');

            // 投放站点
            $this->debug_trace('克隆投放站点');

            $mi_no_all = explode(',', $split_data['mi_no']);
            $mi_no_prj = in_array('1234567890', $mi_no_all) ? '1234567890' : $mi_no_all[0];
            foreach ($mi_no_all as $mi_no) {
                $mi_array = array(
                    'prj_id' => $prj_id,
                    'mi_no' => $mi_no,
                    'rate_type' => $split_data['mi_rate_type'],
                    'rate' => $split_data['mi_rate'],
                );
                if($mdPrjMember->savePrjMemberInfo($mi_array) === FALSE) throw_exception('克隆投放站点失败');
            }
            $this->debug_trace('更新项目mi_no字段');
            if($this->mdPrj->where(array('id' => $prj_id))->save(array('mi_no' => $mi_no_prj)) === FALSE) throw_exception('更新项目信息失败:mi_no');

            // prj_ext
            $this->debug_trace('克隆prj_ext');
            $_data = $mdPrjExt->where($where)->find();
            if($_data) {
                unset($_data['id']);
                $_data['prj_id'] = $prj_id;
                $_data['is_early_repay'] = 0; // 拆标强制不能提前还款
                $_data['show_bid_time'] = $split_data['publish_time'];
                $_data['is_xprj'] = $split_data['is_xprj'];
//                $_data = $mdPrjExt->create($_data);
                //如果是金交所项目那么是否鑫整点从拆标规则表里获取
                if($_data['is_jjs']){
                    $_data['is_xzd'] = $split_data['is_xzd'];
                }

                if($mdPrjExt->add($_data) === FALSE) throw_exception('克隆prj_ext失败');
            }

            // 罚息
            $this->debug_trace('克隆prj_default_interest');
            $_data = $mdPrjDefaultInterest->where($where)->find();
            if($_data) {
                unset($_data['id']);
                $_data['prj_id'] = $prj_id;
//                $_data = $mdPrjDefaultInterest->create($_data);
                if($mdPrjDefaultInterest->add($_data) === FALSE) throw_exception('克隆prj_default_interest失败');
            }

            // 二三级担保(TODO: 可考虑把prj_ext全部同步, 其他字段在另一个CASE中涉及: YRZIF-5494)
            $guarantors = $this->mdPrj->getGuarantorExt($prj_id_old);
            if($guarantors['guarantor2']['id'] && $guarantors['guarantor3']['id']) $this->mdPrj->setGuarantorExt($prj_id, (int)$guarantors['guarantor2']['id'], (int)$guarantors['guarantor3']['id']);

            //合同编号
            $prjExtInfo = $this->mdPrj->getPrjExtInfo($prj_id_old);
            if($prjExtInfo['contract_no']) $this->mdPrj->setPrjExtInfo($prj_id, $prjExtInfo['contract_no']);

            // 更新拆分规则
            $this->debug_trace('更新拆分规则: status=STATUS_SPLITED, prj_id=' . $prj_id);
            $_data = array(
                'prj_id' => $prj_id,
                'status' => self::STATUS_SPLITED,
            );
            if($this->where(array('id' => $split_data['id']))->save($_data) === FALSE) throw_exception('更新拆分规则信息失败');

            // 直融
            if($project['zhr_apply_id']) $this->cloneZhr($project['zhr_apply_id'], $prj_id, $project);

            $this->split_num += 1;

            // 按天分组资产供应列表
            if($split_data['is_xprj']) {
                if($split_data['publish_type'] == self::PUBLISH_TYPE_TIME && !isset($this->children_time_stack[date('Ymd', $split_data['start_bid_time'])])) {
                    $this->children_group[] = array(
                        'start_bid_time' => $split_data['start_bid_time'],
                        'demand_amount' => 0,
                        'ids' => array(),
                    );
                    $this->children_time_stack[date('Ymd', $split_data['start_bid_time'])] = 1;
                }
                $this->children_group[count($this->children_group) -1]['ids'][] = $prj_id;
                $this->children_group[count($this->children_group) -1]['demand_amount'] += $split_data['demand_amount'];
            }
        } catch (Exception $e) {
            throw $e;
        }
    }


    private function cloneZhr($old_apply_id, $prj_id, $project) {
        $this->debug_trace("克隆直融数据$old_apply_id, $prj_id");
        $mdFinanceApply = M('finance_apply');
        $mdFinanceApplyMaterial = M('finance_apply_material');
        try {
            $_data = $mdFinanceApply->where(array('id' => $old_apply_id))->find();
            if($_data) {
                unset($_data['id']);
                unset($_data['have_children']);
                $_data['prj_id'] = $prj_id;
                $_data['finance_amount'] = $project['demand_amount'];
                $_data['pid'] = $old_apply_id;
                $apply_id = $mdFinanceApply->add($_data);
                if($apply_id === FALSE) throw_exception('克隆直融申请数据失败');

                $materials = $mdFinanceApplyMaterial->where(array('apply_id' => $old_apply_id))->select();
                foreach ($materials as $row) {
                    $mid = $row['id'];
                    $this->debug_trace("克隆直融材料：$mid");
                    unset($row['id']);
                    $row['apply_id'] = $apply_id;
                    if($mdFinanceApplyMaterial->add($row) === FALSE) throw_exception("克隆直融资料失败：$mid");
                }
                if($this->mdPrj->where(array('id' => $prj_id))->save(array('zhr_apply_id' => $apply_id)) === FALSE) throw_exception('更新项目zhr_apply_id信息失败');
            }
            if(FALSE === $mdFinanceApply->where(array('id' => $old_apply_id))->save(array('have_children' => 1))) throw_exception('更新直融have_children失败');
        } catch (Exception $e) {
            throw $e;
        }
    }


    /**
     * 拆分项目
     *
     * @param $arg
     * @return bool
     * @throws Exception
     */
    public function splitPrj($arg) {
        $this->parent_project_data = NULL;

        if(is_numeric($arg)) {
            $split_datas = $this->where(array('parent_prj_id' => $arg, 'status' => self::STATUS_WAITING))->select();
        } else {
            $split_datas = $arg;
        }
        if(!$split_datas) return TRUE;

        $parent_prj_id = $split_datas[0]['parent_prj_id'];
        $this->startTrans();
        try {
            $this->split_num = 0;
            $this->children_group = array();
            $this->children_time_stack = array();

            $this->debug_trace('【拆标开始】');
            foreach($split_datas as $split_data) {
                if($split_data['status'] != self::STATUS_WAITING) continue;
                $this->clonePrj($split_data);
            }
            $this->debug_trace('修改项目状态为STATUS_SPLITED_PARENT_OK');
            if($this->mdPrj->where(array('id' => $parent_prj_id))->save(array('status' => PrjModel::STATUS_SPLITED_PARENT_OK, 'have_children' => 1)) === FALSE) throw_exception('修改项目状态失败2');

            // 按天分组资产供应列表
            if(!empty($this->children_group)) {
                $this->debug_trace('插入资产供应列表');
                $this->insertXprjAssets($parent_prj_id);
            }
            //回填金交所的产品表
            $this->updateJjsProduct($parent_prj_id);


            $this->commit();
            $this->debug_trace('【拆标结束】');
        } catch (Exception $e) {
            $this->rollback();
            throw $e;
        }
    }


    /**
     * 通过队列拆分项目
     * @param $parent_prj_id
     * @return bool
     */
    public function pushSplitPrjJob($parent_prj_id, $extra = '') {
        $split_datas = $this->where(array('parent_prj_id' => $parent_prj_id, 'status' => self::STATUS_WAITING))->select();
        if(!$split_datas) {
            sleep(1);
            $split_datas = $this->where(array('parent_prj_id' => $parent_prj_id, 'status' => self::STATUS_WAITING))->select();
            if(!$split_datas) throw_exception('没有拆标规则数据');
        }
        if($extra){
            $extra = md5($extra);
            return queue('project_split', $parent_prj_id.'_'.$extra, array('split_datas' => $parent_prj_id));
        }
        return queue('project_split', $parent_prj_id, array('split_datas' => $split_datas));

//        import_addon('libs.Queue.Queue');
//        Queue::create('splitPrjQueue')->push($split_datas);
    }


    /**
     * 获取依赖标的投标进度
     *
     * @param $depend_id
     * @param bool $is_xprj 是不是司马小鑫
     * @return int
     */
    private function getDependBidPercent($depend_id, $is_xprj = false) {
        $split = $this->where(array('id' => $depend_id))->find();
        if(!$split) return 0;

        $project = $this->mdPrjRaw->where(array('id' => $split['prj_id']))->find();
        if(!$project) return 0;

        if ($project['bid_status'] == PrjModel::BSTATUS_WATING) {
            if ($is_xprj) {
                $remain_amount = $project['demand_amount'] - $project['matched_amount'];
                return service('Financing/Project')->getSchedule($project['demand_amount'], $remain_amount);
            } else {
                return 0;
            }
        } else {
            return service('Financing/Project')->getSchedule($project['demand_amount'], $project['remaining_amount']);
        }
    }


    /**
     * 根据一条拆分规则发布标
     *
     * @param $split_data
     * @param bool|TRUE $is_ignore_xprj 是否过滤司马小鑫的标(只针对按满标率发布的, 新计划匹配完成后调用此方法传FALSE)
     * @return bool
     */
    public function publishPrj($split_data, $is_ignore_xprj=TRUE) {
        if($split_data['status'] != self::STATUS_SPLITED) return TRUE;
        $this->debug_trace('>>>正在处理发布子标, id: ' . $split_data['id'] . ', prj_id:' . $split_data['prj_id']);

        $now = time();
        $is_publish = FALSE;
        $start_bid_time = 0;
        if($split_data['publish_type'] == self::PUBLISH_TYPE_TIME) {
            $this->debug_trace('发布条件: 按时间发布, ' . date('Y-m-d H:i:s', $split_data['publish_time']));
            if($now >= $split_data['publish_time']) {
                $is_publish = TRUE;
//                $start_bid_time = $split_data['publish_time'] + $split_data['start_bid_time'] * 60;
                $start_bid_time = $split_data['start_bid_time'];
                $publish_time = $split_data['publish_time'];
            }
        } else {
            $this->debug_trace('发布条件: 按安标率发布, ' . $split_data['start_bid_percent'] . '%');
//            if($is_ignore_xprj && $split_data['is_xprj']) {
//                return TRUE;
//            }

            $percent_depend = $this->getDependBidPercent($split_data['depend_id'], $split_data['is_xprj']);
            if($percent_depend >= $split_data['start_bid_percent']) {
                $is_publish = TRUE;
                $start_bid_time = $now + $split_data['start_bid_time'] * 60;
                $publish_time = $now;
            }
        }

        if($is_publish) {
            $this->startTrans();
            try {
                $data = array(
                    'status' => PrjModel::STATUS_PASS,
                    'start_bid_time' => $start_bid_time,
                    'ctime' => $publish_time,
                );

                service('Financing/XProject')->xOrderMatchingSplitPrjLock([], $split_data['id'], 'check');

                if($this->mdPrjRaw->where(array('id' => $split_data['prj_id']))->save($data) === FALSE) throw_exception('更新项目信息失败');
                if($this->where(array('id' => $split_data['id']))->save(array('status' => self::STATUS_PUBLISHED)) === FALSE) throw_exception('更新拆分规则状态失败');
                $this->commit();

                // 开标时间超过半小时的推送
                $now = time();
                if ($start_bid_time > $now + 1800) {
                    if($start_bid_time > $now + 3600) $push_time = $start_bid_time - 3600; // 超过一小时的开标前一小时推
                    else $push_time = $now;
                    $this->mdPrj->notifyOne($split_data['prj_id'], $push_time);
                }
            } catch (Exception $e) {
                $this->rollback();
                $this->debug_trace('ERROR: ' . $e->getMessage());
                return FALSE;
            }
        }

        return TRUE;
    }


    /**
     * 获取总编号
     *
     * @param $arg
     * @return mixed
     */
    public function getParentPrjNo($arg) {
        if(is_numeric($arg)) {
            $project = NULL;
            $prj_id = $arg;
        } else {
            $project = $arg;
            $prj_id = $project['id'];
        }
        $parent_prj_id = $this->where(array('prj_id' => $prj_id))->getField('parent_prj_id');
        if(!$parent_prj_id) {
            if($project) return $project['prj_no'];
        } else {
            $prj_id = $parent_prj_id;
        }

        return $this->mdPrj->where(array('id' => $prj_id))->getField('prj_no');
    }


    /**
     * 获取父项目Id
     *
     * @param $prj_id
     * @return mixed
     */
    public function getParentPrjId($prj_id) {
        $parent_prj_id = $this->where(array('prj_id' => $prj_id))->getField('parent_prj_id');
        return $parent_prj_id ? $parent_prj_id : $prj_id;
    }


    // 按天分组资产供应列表
    private function insertXprjAssets($parent_prj_id)
    {
        if(empty($this->children_group)) {
            throw_exception("insertXprjAssets失败, parent_prj_id={$parent_prj_id}");
        }

        $mdPrjExt = M('prj_ext');
        $mdXprjAssets= M('xprj_assets');

        if($this->parent_project_data) {
            $project = $this->parent_project_data;
        } else {
            $project = $this->mdPrj->where(array('id' => $parent_prj_id))->find();
        }
        $prj_ext = $mdPrjExt->where(array('prj_id' => $parent_prj_id))->find();;

        $data = array(
            'assets_name' => $project['prj_no'],
            'maturity_date' => strtotime(date('Y-m-d', $project['end_bid_time'])) + (int)$project['value_date_shadow'] * 86400,
            'year_rate' => $project['year_rate'],
            'repay_way' => $project['repay_way'],
            'time_limit' => $project['time_limit'],
            'time_limit_unit' => $project['time_limit_unit'],
            'safeguards' => $project['safeguards'],
            'prj_type' => $project['zhr_apply_id'] > 0 ? 1 : 2,
            'prj_business_type' => $project['prj_business_type'],
            'is_extend' => $prj_ext['is_extend'],
            'is_early_repay' => $prj_ext['is_early_repay'],
            'is_pre_sale' => $prj_ext['is_pre_sale'],
            'is_matched' => 0,
            'status' => 0,
            'used_status' => 1,
            'version' => 0,
        );
        foreach ($this->children_group as $item) {
            $_data = $data;
            $_data['start_bid_time'] = $item['start_bid_time'] ? $item['start_bid_time'] : $project['start_bid_time'];
            $_data['demand_amount'] = $item['demand_amount'];
            $_data['remaining_amount'] = $item['demand_amount'];
            $_data['sub_prj_ids'] = implode(',', $item['ids']);

            $id = $mdXprjAssets->add($_data);
            if (!$id) {
                throw_exception("添加xprj_assets失败, parent_prj_id={$parent_prj_id}");
            }

            // 回写项目表里的assets_id
            $this->mdPrj->where(array('id' => array('IN', $item['ids'])))->save(array('assets_id' => $id));
        }

        return true;
    }

    /**
     * 拆标后同步jjs_loan_product表的数据
     * @param int $parent_prj_id 母标id
     * @return bool
     */
    public function updateJjsProduct($parent_prj_id){
        $mdPrjExt = M('prj_ext');
        $prj_ext = $mdPrjExt->where(array('prj_id' => $parent_prj_id))->find();
        if($prj_ext['is_jjs'] == '1'){
            $split_datas = $this->where(array('parent_prj_id' => $parent_prj_id))->select();
            foreach($split_datas as $split_data){
                $ret = M()->execute("update jjs_loan_product set sub_prj_id = {$split_data['prj_id']} where product_id = {$split_data['product_id']} ");
                if(!$ret){
                    throw_exception("更新金交所产品表失败id=".$split_data['jjs_id']);
                }
            }
        }
        return true;
    }
}

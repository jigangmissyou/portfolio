<?php
/**
 * User: 000802
 * Date: 2013-11-21 14:56
 * $Id$
 *
 * Type: Model
 * Group: Financing
 * Module: 我要融资
 * Description: 项目关联个人详情信息
 */


class PrjPersonModel extends BaseModel {
    protected $tableName = '';
    protected $_validate = array(
        array('prj_id', 'require', '缺少项目Id'), // 项目id
//        array('sex', 'require', ''), // 性别 A001
//        array('age', 'require', ''), // 年龄
        array('age', 'number', '年龄必须为整数', self::VALUE_VALIDATE), // 年龄
        array('age', 'gtZero','年龄必须大于0', self::VALUE_VALIDATE, 'function'),
        array('educational', 'require', '请选择学历'), // 学历 A003
        array('matrimonial', 'require', '请选择婚姻状况'), // 婚姻状况 A002
//        array('occupation', 'require', ''), // 职业
        array('annual_earnings', 'require', '个人年现金收入必填'), // 个人年现金收入
//        array('has_financial_assets', 'require', ''), // 个人金融资产 1-有 0-无
//        array('has_house', 'require', ''), // 房产情况 1-有 0-无
//        array('has_car', 'require', ''), // 车辆情况 1-有 0-无
//        array('has_other_loan', 'require', ''), // 有无其他贷款
//        array('loan_card_desc', 'require', ''), // 信用卡情况 1-正常 0-不正常
//        array('bank_credit_grade', 'require', ''), // 个人信用记录 1-正常 0-不正常
    );


    public function create($data='', $type='') {
        $data = parent::create($data, $type);
        if(!$data) {
            throw_exception($this->getError());
        }

        // 自定义验证
        // ...

        return $data;
    }


    public function addPerson($prj_id, $uid, $input=array()) {
        $now = time();
        $data = array(
            'uid' => $uid,
            'prj_id' => $prj_id,
            'sex' => $input['sex'],
            'age' => $input['age'],
            'educational' => $input['educational'],
            'matrimonial' => $input['matrimonial'],
            'occupation' => $input['occupation'],
            'annual_earnings' => $input['annual_earnings'],
            'has_financial_assets' => $input['has_financial_assets'],
            'has_house' => $input['has_house'],
            'has_car' => $input['has_car'],
            'has_other_loan' => $input['has_other_loan'],
            'loan_card_desc' => $input['loan_card_desc'],
            'bank_credit_grade' => $input['bank_credit_grade'],
            'ctime' => $now,
            'mtime' => $now,
        );

        $data = $this->create($data);
        $ret = $this->add($data);
        if($ret === FALSE) {
            throw_exception('系统异常：添加融资方信息(个人)失败！');
        }
    }


    public function updatePerson($prj_id, $input=array()) {
        $where = array(
            'prj_id' => $prj_id,
        );

        $now = time();
        $data = array(
//            'uid' => $uid,
//            'prj_id' => $prj_id,
            'sex' => $input['sex'],
            'age' => $input['age'],
            'educational' => $input['educational'],
            'matrimonial' => $input['matrimonial'],
            'occupation' => $input['occupation'],
            'annual_earnings' => $input['annual_earnings'],
            'has_financial_assets' => $input['has_financial_assets'],
            'has_house' => $input['has_house'],
            'has_car' => $input['has_car'],
            'has_other_loan' => $input['has_other_loan'],
            'loan_card_desc' => $input['loan_card_desc'],
            'bank_credit_grade' => $input['bank_credit_grade'],
//            'ctime' => $now,
            'mtime' => $now,
        );

        $data = $this->create($data);
        if($this->where($where)->save($data) === FALSE) {
            throw_exception('系统异常：修改融资方信息(个人)失败！');
        }
    }


    public function delPerson($prj_id) {
        $where = array(
            'prj_id' => $prj_id,
        );
        $ret = $this->where($where)->delete();
        if($ret === FALSE) {
            throw_exception('系统异常：删除产品融资方信息(个人)时出错！');
        }
    }

    public function getMultiInfosByPrjId($arr_prj_id = array()){
        if (!is_array($arr_prj_id)) return false;
        if (empty($arr_prj_id)) return array();

        $rows = $this->where(array(
            'prj_id' => array('IN', $arr_prj_id)
        ))->select();

        if($rows){
            $rows = array_column($rows, null, 'prj_id');
        }

        return $rows;
    }
}

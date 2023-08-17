<?php
/**
 * User: 000802
 * Date: 2013-11-21 14:55
 * $Id$
 *
 * Type: Model
 * Group: Financing
 * Module: 我要融资
 * Description: 项目关联企业详情信息
 */


class PrjCorpModel extends BaseModel {
    protected $tableName = 'prj_corp';
    protected $_validate = array(
        array('prj_id', 'require', '缺少项目Id'), // 项目id
        array('trade', 'require', '所属行业必选'), // 所属行业
        array('fund_date', 'require', '成立日期必填'), // 成立日期
//        array('register_capital', 'number', '注册资本必须为数字'), // 注册资本
        //array('register_capital', '/^\d+(\.\d{1,2})?$/', '注册资本必须为数字', self::EXISTS_VALIDATE, 'regex'),
        array('main_business', 'require', '主营业务必填'), // 主营业务
//        array('last_year_sale_amount', 'require', ''), // 上一年度销售收入
        array('corp_scale', 'require', '企业规模必选'), // 企业规模 字典项 A137
        array('sharehold_radio', 'currency', '持股比例必须为数字', self::VALUE_VALIDATE), // 企业主持股比例
        array('sex', 'require', '请选择性别'), // 性别 A001
//        array('age', 'require', ''), // 年龄
        array('age', 'number', '年龄必须为整数', self::VALUE_VALIDATE), // 年龄
        array('age', 'gtZero','年龄必须大于0', self::VALUE_VALIDATE, 'function'),
        array('educational', 'require', '请选择学历'), // 学历 A003
//        array('matrimonial', 'require', ''), // 婚姻状况 A002
//        array('owner_city', 'require', ''), // 企业主户籍城市
        array('loan_card_desc', 'require', '请选择企业贷款卡状态'), // 企业贷款卡状态 1-正常 0-不正常  贷款卡逾期次 0就是正常吧
        array('bank_credit_grade', 'require', '请选择企业信用状况'), // 企业信用状况，1-正常 0-不正常
    );


    public function create($data='',$type='') {
        $data = parent::create($data, $type);
        if(!$data) {
            throw_exception($this->getError());
        }

        // 自定义验证
        // ...

        return $data;
    }


    public function addCorp($prj_id, $uid, $input=array()) {
        $input['sharehold_radio'] = preg_replace('/%/', '', $input['sharehold_radio']); // 去除百分号
        $now = time();
        $data = array(
//            'uid' => $uid,
            'prj_id' => $prj_id,
            'db_corp_id' => empty($input['yrz_id']) ? (int)$input['yrz_corp_id'] : (int)$input['yrz_id'],
            'trade' => $input['trade'],
            'fund_date' => $input['fund_date'],
            'register_capital' => $input['register_capital'],
            'main_business' => $input['main_business'],
            'last_year_sale_amount' => $input['last_year_sale_amount'],
            'corp_scale' => $input['corp_scale'],
            'sharehold_radio' => $input['sharehold_radio'],
            'sex' => $input['sex'],
            'age' => $input['age'],
            'educational' => $input['educational'],
            'matrimonial' => $input['matrimonial'],
            'owner_city' => $input['owner_city'],
            'loan_card_desc' => $input['loan_card_desc'],
            'bank_credit_grade' => $input['bank_credit_grade'],
            'ctime' => $now,
            'mtime' => $now,
        );

        $data = $this->create($data);
        $ret = $this->add($data);
        if($ret === FALSE) {
            throw_exception('系统异常：添加融资方信息(企业)失败！');
        }
    }


    public function updateCorp($prj_id, $input=array()) {
        $where = array(
            'prj_id' => $prj_id,
        );

        $now = time();
        $data = array(
//            'uid' => $uid,
//            'prj_id' => $prj_id,
            'db_corp_id' => empty($input['yrz_id']) ? (int)$input['yrz_corp_id'] : (int)$input['yrz_id'],
            'trade' => $input['trade'],
            'fund_date' => $input['fund_date'],
            'register_capital' => $input['register_capital'],
            'main_business' => $input['main_business'],
            'last_year_sale_amount' => $input['last_year_sale_amount'],
            'corp_scale' => $input['corp_scale'],
            'sharehold_radio' => $input['sharehold_radio'],
            'sex' => $input['sex'],
            'age' => $input['age'],
            'educational' => $input['educational'],
            'matrimonial' => $input['matrimonial'],
            'owner_city' => $input['owner_city'],
            'loan_card_desc' => $input['loan_card_desc'],
            'bank_credit_grade' => $input['bank_credit_grade'],
//            'ctime' => $now,
            'mtime' => $now,
        );

        $data = $this->create($data);
        if($this->where($where)->save($data) === FALSE) {
            throw_exception('系统异常：修改融资方信息(企业)失败！');
        }
    }


    public function delCorp($prj_id) {
        $where = array(
            'prj_id' => $prj_id,
        );
        $ret = $this->where($where)->delete();
        if($ret === FALSE) {
            throw_exception('系统异常：删除产品融资方信息(企业)时出错！');
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

<?php
/**
 * User: 000802
 * Date: 2013-10-10 14:06
 * $Id$
 *
 * Type: Model
 * Group: Fanancing
 * Module: 我要融资
 * Description: 日升益（原：短期借贷）
 *              PRJ_TYPE: A
 */


class InvestPrjModel extends BaseModel {
    protected $tableName = 'invest_prj';
    protected $_validate = array(
        array('prj_id', 'require', '缺少项目Id'),

//        array('products_id', 'require', '请选择适用产品'),

        array('value_date', 'require', '请选择起息日'),
        array('value_date', 'number', '起息日必须为数字'),
        array('value_date', 'gteZero','起息日必须大于等于0', self::EXISTS_VALIDATE, 'function'),

//        array('is_early', 'require', '请选择是否可能提前到期'),
        array('fund_account', 'require', '请选择资金转入账户'),
        array('money_using', 'require', '资金用途必填'),
        array('repay_origin', 'require', '还款来源必填'),
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


    /**
     * 添加
     *
     * @param $prj_id       关联产品Id
     * @param int $uid      发布者uid
     * @param array $input
     */
    public function addInvest($prj_id, $uid, $input=array()) {
        $now = time();
        $data = array(
            'uid' => $uid,
            'prj_id' => $prj_id,
            'value_date' => $input['value_date'],
//            'products_id' => $input['products_id'],
//            'is_early' => $input['is_early'],
            'fund_account' => $input['fund_account'],
            'money_using' => $input['money_using'],
            'repay_origin' => $input['repay_origin'],
            'other' => $input['other'],
            'ctime' => $now,
            'mtime' => $now,
        );

        $data = $this->create($data);
        $ret = $this->add($data);
        if($ret === FALSE) {
            throw_exception('系统异常A：添加附加信息失败！');
        }
    }


    /**
     * 修改
     * @param $prj_id       关联产品Id
     * @param array $input
     */
    public function updateInvest($prj_id, $input=array()) {
        $where = array(
            'prj_id' => $prj_id,
        );
        $check = $this->where($where)->find();
        $now = time();
        $data = array(
//            'uid' => $uid,
//            'prj_id' => $prj_id,
            'value_date' => $input['value_date'],
//            'products_id' => $input['products_id'],
//            'is_early' => $input['is_early'],
            'fund_account' => $input['fund_account'],
            'money_using' => $input['money_using'],
            'repay_origin' => $input['repay_origin'],
            'other' => $input['other'],
//            'ctime' => $now,
            'mtime' => $now,
        );

        $data = $this->create($data);

        if($check){
            if($this->where($where)->save($data) === FALSE) {
                throw_exception('系统异常A：修改附加信息失败！');
            }
        }else{
            $info = M("prj")->find($prj_id);
            $data['uid'] = $info['uid'];
            $data['prj_id'] = $prj_id;
            $data['ctime'] = $now;
            if($this->add($data) === FALSE) {
                throw_exception('系统异常B：修改附加信息失败！');
            }
        }


    }


    /**
     * 按项目Id删除
     *
     * @param $prj_id
     */
    public function delInvest($prj_id) {
        $where = array(
            'prj_id' => $prj_id,
        );
        $ret = $this->where($where)->delete();
        if($ret === FALSE) {
            throw_exception('系统异常A：删除产品附加信息时出错！');
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

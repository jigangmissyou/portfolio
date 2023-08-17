<?php

/**
 * User: 001181
 * Date: 2015-03-24 16:39
 * Author:001181
 * Description: 企福鑫红包的投资规则 
 */
import_app('Financing.Service.BaseInvestRuleService');
import_app('Financing.Service.InvestRuleService');
class CompanySalaryInvestRuleService extends BaseInvestRuleService implements InvestRuleService {

    protected $_prj_id;
    protected $_max_use_money;
    protected $_min_invest_money;
    protected $_use_rate;
    protected $_prj_info;
    protected $_uid = 0;
    protected $_userinfo = array();
    protected $is_init = FALSE; //强制初始化信息
    protected $_min_time_limit = 0;
    protected $_is_qfx_prj = 0; //是否是企福鑫项目
    protected $_product_rule; //生产红包的规则
    protected $_my_bonus = 0; //我的企福鑫红包

    public function __construct() {
        parent::__construct();
        $this->db = new BaseModel();
        $this->srvPayAccount = service('Payment/PayAccount');
        $this->mod = D("Account/UserBonusPackage");
        $this->user_mod = D("Account/User");
        $this->_min_invest_money = 0; //最小投资金额(0代表不限)
        $this->_max_use_money = 0; //最大投资金额使用金额(0代表不限)
        $this->_use_rate = 0; //使用投资金额的比率(0不限制)
        $this->_min_time_limit = 0; //最小投资的期限(0代表不限制)
        $this->activity_type = UserBonusLogModel::ACTIVITY_TYPE_REG_HONGBAO;
        $this->mdUserBonus = D("Account/UserBonus");
    }

    /**
     * 初始化信息
     */
    public function init($uid, $prj_id) {
        $this->_uid = $uid;
        $this->_prj_id = $prj_id;
        $this->_uid && $this->_prj_id ? $this->is_init = true : $this->is_init = false;
        if ($this->is_init) {
            $this->_prj_info = service("Financing/Invest")->getPrjView($this->_prj_id);
            $info = $this->_prj_info;
            $this->_is_qfx_prj = $info['act_prj_ext']['is_qfx'];
            $this->_product_rule = service("Account/CompanySalaryRule")->init($this->_userinfo);
            $this->_userinfo = M('User')->find($this->_uid);
            $this->bonus_from_type = $this->_product_rule->getFromType();
            $this->bonus_from_id = $this->_product_rule->getFromId();
            $this->_my_bonus = $this->mdUserBonus->getMyBonusByFromId($this->_uid, $this->bonus_from_type, $this->bonus_from_id);
        }

        return $this;
    }

    /**
     * 是否是企福鑫项目
     * @return type
     */
    public function isQfx() {
        return $this->_is_qfx_prj == 1 ? TRUE : FALSE;
    }

    /**
     * 是否能使用红包满足以下几个条件
     * 1、是企福鑫
     * 2、用户红包余额足够
     * return amount
     */
    public function isCanUse() {
        try {
            $this->forceInit();
            $is_qfx = $this->isQfx();
            if ($is_qfx === FALSE || !$this->_my_bonus) {
                return 0;
            }
            return $this->_my_bonus;
        }
        catch (Exception $e) {
            $this->error = $e->getMessage();
            return 0;
        }
    }

    /**
     * 强制初始化必要参数
     */
    public function forceInit() {
        if (!$this->is_init) {
            throw_exception("请先初始化RuleService~");
        }
        return true;
    }

    /**
     * 消费规则
     */
    public function investRule() {
        try {
            $this->forceInit();
        }
        catch (Exception $e) {
            $this->error($e->getMessage());
        }
    }

    public function useBonusForInvest($uid, $invest_amount, $prj_id, &$project) {

        $amount_for_invest = $this->getAmountForInvest($uid, $invest_amount, $prj_id, $project);
        if ($amount_for_invest <= 0)
            return 0;
        $amount = $this->getAmount($uid, FALSE);
        if ($amount['amount'] < $amount_for_invest)
            throw_exception('可使用红包不足');
        $out_order_no = $this->genCounter('BONUSBID', ".{$amount['id']}.");
        $cashout_type = 2;
        $money = $amount_for_invest;
        $title = "投资{$prj_id}使用红包{$money}";
        $desr = '';
        $from_user = YUNYING_ACCOUNTID;
        $obj_type = PayAccountModel::OBJ_TYPE_USER_BONUS;
        $obj_id = $prj_id;
        $this->db->startTrans();
        try {
            $this->srvPayAccount->userBonus($uid, $out_order_no, $cashout_type, $money, $title, $desr, $from_user, $obj_type, $obj_id);
            $this->mdUserBonus->where(array('id' => $amount['id']))->setDec('amount', $amount_for_invest);
            $this->db->commit();
            return $amount_for_invest;
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    /**
     * 投资可使用金额
     * @return type
     */
    public function getAmountForInvest($uid, $invest_amount, $prj_id, $project = array()) {
        $my_bonus = $this->isCanUse();
        if($invest_amount>=$my_bonus){
            return $my_bonus;      //全部使用红包(使用账户金额$invest_amount-$my_bonus) 
        }else{
            return $invest_amount;//全部使用投资金额代替(使用账户金额0) 
        }
    }

    /**
     *我的金额
     */
    public function getAmount() {
        $where = array(
            'uid' => $this->_uid,
            'status' => UserBonusModel::STATUS_ACTIVE,
            'from_type' => $this->bonus_from_type,
            'from_id' => $this->bonus_from_id,
        );
        $row = $this->mdUserBonus->where($where)->field(array("id","amount"))->find();
        return $row;
    }

}

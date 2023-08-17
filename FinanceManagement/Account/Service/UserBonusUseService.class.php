<?php
/**
 * Created by PhpStorm.
 * User: channing
 * Date: 15-2-3
 * Time: 上午10:30
 */
class UserBonusUseService extends BaseService {
    protected $bonus_from_type = NULL;
    protected $bonus_from_id = NULL;

    protected  $db;
    protected $mdUserBonus;
    protected $mdUserBonusLog;
    protected $mdPayAccount;
    protected $mdPayOrder;
    protected $mdPrjOrder;
    protected $srvPayAccount;
    protected $activity_type = NULL;

    const RATE_FRO_INVEST = 0.01;
    const MAX_RATE_FOR_INVEST = 0.02;//注册红包和其他红包叠加使用
    const PRJ_TIME_LIMIT_DAY = 30;

    const COUNTER_LENGTH = 7;

    const OBJ_TYPE_INVEST = 7;


    function __construct() {
        parent::__construct();

//        $this->setBonusType();
        $this->db = new BaseModel();
        $this->mdUserBonus = D('Account/UserBonus');
        $this->mdUserBonusLog = D('Account/UserBonusLog');
        $this->mdPayAccount = D('Payment/PayAccount');
        $this->mdPayOrder = D('Payment/PayOrder');
        $this->mdPrjOrder = D('Financing/PrjOrder');
        $this->srvPayAccount = service('Payment/PayAccount');

        $this->activity_type = UserBonusLogModel::ACTIVITY_TYPE_REG_HONGBAO;
    }


    private function checkBonusType() {
        if(is_null($this->activity_type)) throw_exception('未设置activity_type');
        if(is_null($this->bonus_from_id) || is_null($this->bonus_from_type)) throw_exception('未设置bonus_from_id和bonus_from_type');

        return TRUE;
    }


    public function setBonusType($from_type/*=UserBonusModel::BONUS_FROM_TYPE_YAOYIYAO*/, $from_id/*=UserBonusModel::BONUS_FROM_ID_YAOYIYAO*/) {
        $this->bonus_from_type = $from_type;
        $this->bonus_from_id = $from_id;
    }


    // 获取当前账户金额
    public function getAmount($uid, $only_amount=TRUE) {
        $this->checkBonusType();
        $where = array(
            'uid' => $uid,
            'status' => UserBonusModel::STATUS_ACTIVE,
            'from_type' => $this->bonus_from_type,
            'from_id' => $this->bonus_from_id,
        );
        $row = $this->mdUserBonus->where($where)->find();
        if(isset($row['amount'])) $row['amount'] = max(0, (int)$row['amount']);

        if($only_amount) return (int)$row['amount'];
        return $row;
    }


    // 获取投资时使用金额
    public function getAmountForInvest($uid, $invest_amount, $prj_id=0, &$project=NULL) {
        if (is_null($project)) $project = M('prj')->where(array('id' => $prj_id))->field('prj_type,time_limit_day')->find();
        if ($project['time_limit_day'] < self::PRJ_TIME_LIMIT_DAY) return 0;
        $amount = $this->getAmount($uid);
        $ret = min($amount, (int)($invest_amount * self::RATE_FRO_INVEST));
        return (int)$ret;
    }


    // 获取使用次数
    public function getCountUsed($uid) {
        $where = array(
            'uid' => $uid,
            'type' => UserBonusLogModel::TYPE_CONSUME,
            'obj_type' => UserBonusLogModel::OBJ_TYPE_INVEST,
            'activity_type' => $this->activity_type,
        );
        return $this->mdUserBonusLog->where($where)->count();
    }

    public function getAmountUsed($uid, $prj_id=0) {
        $where = array(
            'uid' => $uid,
            'type' => UserBonusLogModel::TYPE_CONSUME,
            'obj_type' => UserBonusLogModel::OBJ_TYPE_INVEST,
            'activity_type' => $this->activity_type,
        );
        if($prj_id > 0) {
            $order_ids = array();
            $rows = $this->mdPrjOrder->where(array('uid' => $uid,'prj_id'=>$prj_id, 'status' => array('NEQ', PrjOrderModel::STATUS_NOT_PAY)))->field('id')->select();
            foreach ($rows as $row) {
                $order_ids[] = $row['id'];
            }
            if(!empty($order_ids)) $where['obj_id'] = array('IN', $order_ids);
            else return 0;
        }
        $row = $this->mdUserBonusLog->where($where)->field('SUM(amount) AS TT')->find();
        return (int)$row['TT'];

    }


    // 使用红包充值
    public function useBonusForInvest($uid, $invest_amount, $prj_id, &$project) {
        $amount_for_invest = $this->getAmountForInvest($uid, $invest_amount, $prj_id, $project);
//        throw_exception("$amount_for_invest, $uid, $invest_amount, $prj_id, $project, ");
        if($amount_for_invest <= 0) return 0;

        $amount = $this->getAmount($uid, FALSE);
        if($amount['amount'] < $amount_for_invest) throw_exception('可使用红包不足');

//        $out_order_no = 'BONUSBID' . $amount['id'] . '.' . $prj_id . '.' . date('YmdHis');
        $out_order_no = $this->genCounter('BONUSBID', ".{$amount['id']}.");
        $cashout_type = 2;
        $money = $amount_for_invest;
        $title = "投资{$prj_id}使用红包{$money}";
        $desr = '';
        $account_type_service = service("Public/BonusAccount");
        $from_user = $account_type_service->getFromUidByAccountType(BonusAccountService::ACCOUNT_TYPE_MARKET);
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
     * 获取某个项目的最大可投资金额,不包含前置红包的奖励
     */
    public function getMaxMoney($uid, $prj_id)
    {

        D("Financing/Prj");
        $project = M('prj')->where(array('id' => $prj_id))->find();
        if (!$project){
            throw_exception(__FUNCTION__.':项目不存在');
        }
        $prj_is_deposit = M('prj_ext')->where(['prj_id' => $prj_id])->getField('is_deposit');
        $amount = service('Mobile2/Mobile')->getUserAmount($uid, $prj_is_deposit);
        //获取最大可投资金额
        $balance = $amount / (1 - self::MAX_RATE_FOR_INVEST);
        if (!$balance) {
            return 0;
        }
        if ($project['prj_type'] == PrjModel::PRJ_TYPE_H) {
           return $balance;
        } else {
            $min_bid_amount = $project['min_bid_amount'];
            $max_bid_amount = $project['max_bid_amount'];
            if (!$max_bid_amount) $max_bid_amount = $project['remaining_amount'];
            $money = floor((min($balance, $max_bid_amount, $project['remaining_amount']) - $min_bid_amount) / $project['step_bid_amount']) * $project['step_bid_amount'] + $min_bid_amount;
            return $money;
        }
    }


    // 插入日志
    public function logInvest($uid, $order_id, $amount_for_invest,$activity_type = null) {
        $now = time();
        if(empty($activity_type)){
            $activity_type = $this->activity_type;
        }
        $data = array(
            'type' => UserBonusLogModel::TYPE_CONSUME,
            'amount' => $amount_for_invest,
            'obj_id' => $order_id,
            'obj_type' => UserBonusLogModel::OBJ_TYPE_INVEST,
            'activity_type' => $activity_type,
            'uid' => $uid,
            'memo' => '',
            'ctime' => $now,
            'mtime' => $now,
        );
        if(FALSE === $this->mdUserBonusLog->addData($data)) throw_exception('添加红包使用日志失败');

        return TRUE;
    }


    public function genCounter($prefix, $midfix){
        import('libs.Counter.MultiCounter', ADDON_PATH);
        $oCounter = MultiCounter::init(Counter::GROUP_DEFAULT, Counter::LIFETIME_TODAY);
        $counter_key = $prefix . date('Ymd');
        $counter_prefix = $counter_key . $midfix;

        $where_like = array(
            'obj_type' => PayAccountModel::OBJ_TYPE_USER_BONUS,
            'out_order_no' => array('LIKE', $counter_key . '%'),
        );
        $max_num = $oCounter->incr($counter_key);
        if($max_num) {
            $no = $counter_prefix . str_pad($max_num, self::COUNTER_LENGTH, '0', STR_PAD_LEFT);
            $where = array(
                'obj_type' => PayAccountModel::OBJ_TYPE_USER_BONUS,
                'out_order_no' => $no,
            );
            $has = $this->mdPayOrder->where($where)->find();
            if($has) {
                $row = $this->mdPayOrder->where($where_like)->order('out_order_no desc')->find(); // 计数器不能自增的时候，这样只读取当天的最大数更保险一点
                $max_num = substr($row['out_order_no'], strlen($row['out_order_no']) - self::COUNTER_LENGTH, self::COUNTER_LENGTH);
                $max_num = (int)$max_num + 1;
                $oCounter->set($counter_key, $max_num);
                $no = $counter_prefix . str_pad($max_num, self::COUNTER_LENGTH, '0', STR_PAD_LEFT);
                return $no;
            } else {
                return $no;
            }
        } else {
            $row = $this->mdPayOrder->where($where_like)->order('out_order_no desc')->find(); // 计数器不能自增的时候，这样只读取当天的最大数更保险一点
            $max_num = substr($row['out_order_no'], strlen($row['out_order_no']) - self::COUNTER_LENGTH, self::COUNTER_LENGTH);
            $max_num = (int)$max_num + 1;
            $no = $counter_prefix . str_pad($max_num, self::COUNTER_LENGTH, '0', STR_PAD_LEFT);
            return $no;
        }
    }
}
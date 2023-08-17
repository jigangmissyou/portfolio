<?php

/**
 * Created by PhpStorm.
 * User: will
 * Date: 15/9/29
 * Time: 09:11
 */
class UserBonusFreezeModel extends BaseModel
{
    protected $tableName = 'user_bonus_freeze';

    const TYPE_BONUS = 1;//红包
    const TYPE_REDUCE = 2;//满减券
    const TYPE_RATE = 3;//加息券
    const TYPE_LCJ = 4;//理财金

    protected $_auto = array(
        array('ctime', 'time', self::MODEL_INSERT, 'function'),
        array('mtime', 'time', self::MODEL_BOTH, 'function'),
    );

    /**
     * 投资以后使用奖励,由于加息券投资没有奖励，而是还款记录，所以这里排除
     * @return array
     */
    public function getRewardTypeList()
    {
        return array(
            self::TYPE_BONUS,
            self::TYPE_REDUCE,
        );
    }

    /**
     * 添加冻结操作的记录
     * @param $data
     * @return mixed
     */
    public function addFreeze($data)
    {
        try {
            K($data, 'uid');
            K($data, 'prj_id');
            K($data, 'request_id');
            K($data, 'amount');
            K($data, 'has_freeze', 0);
            K($data, 'memo', '');
            K($data, 'type', '');
            K($data, 'account_type', '');
            K($data, 'rate', '');
            K($data, 'project_type', 0);
            $res = $this->add($this->create($data));
            if (!$res) {
                throw_exception($this->getDbError());
            }

            return $res;
        } catch (Exception $e) {
            throw_exception($e->getMessage());
        }
    }

    /**
     * 解冻的记录
     * @param $request_id
     * @param $memo
     * @return bool
     */
    public function updateFreezeMemo($request_id, $memo)
    {
        if (empty($request_id)) {
            throw_exception("requestId参数无效");
        }
        $where = array(
            'request_id' => $request_id,
        );

        return $this->where($where)->save(array('has_freeze' => 1, 'memo' => $memo));
    }

    /**
     * 获取冻结金额
     * @param $requestId
     * @return mixed
     */
    public function getInfoByRequestId($requestId)
    {
        if (empty($request_id)) {
            throw_exception("requestId参数无效");
        }
        $where = array(
            'request_id' => $requestId,
        );

        return $this->where($where)->find();
    }

    /**
     * 生成订单的关联关系
     * @param $request_id
     * @param $order_id
     * @param $params
     * @return bool
     */
    public function updateFreezeOrderId($request_id, $order_id, $params = array())
    {
        $db = new BaseModel();
        $db->startTrans();
        try {
            $reward_type = K($params, "reward_type", 0);
//            //java 杨勇传过来的账户类型
            $where = array(
                'request_id' => $request_id,
            );
            $data = array('order_id' => $order_id, 'mtime' => time());
            $this->where($where)->save($data);
//            if ($reward_type > 1) {
//                M("user_market_ticket_order")->where(array('request_id' => $request_id))->save($data);
//            }

            return $db->commit();
        } catch (Exception $e) {
            $db->rollback();
            throw_exception($e->getMessage());
        }
    }
}
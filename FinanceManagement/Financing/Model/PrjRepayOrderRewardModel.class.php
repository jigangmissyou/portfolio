<?php
/**
 * Created by PhpStorm.
 * User: luoman
 * Date: 16/9/22
 * Time: 下午5:23
 */

class PrjRepayOrderRewardModel extends BaseModel
{
    const STATUS_INIT = 1;
    const STATUS_ZS_RESPONSE_SUCCESS = 2;
    const STATUS_ZS_RESPONSE_FAIL = 3;
    const STATUS_REQUEST_ZS_FAIL = 4;
    const STATUS_ING = 4;
    const STATUS_SUCCESS = 5;
    const STATUS_FAIL = 6;

    const ZS_STATUS_INIT = '0';
    const ZS_STATUS_WAIT = '00';
    const ZS_STATUS_ING = '10';
    const ZS_STATUS_SUCCESS = '20';
    const ZS_STATUS_FAIL = '30';

    /**
     * 初始化汇总订单的奖励数据(包含加息券和奖励)
     * @param $prj_order_id
     * @return bool
     */
    public function addOrderRewardSummary($prj_order_id)
    {
        $prj_order_model = D('Financing/PrjOrder');
        $prj_order = $prj_order_model->where([
            'id' => $prj_order_id,
            'status' => PrjOrderModel::STATUS_PAY_SUCCESS,
            'repay_status' => ['neq', PrjOrderModel::REPAY_STATUS_NOT_REPAYMENT]
        ])->find();

        if (empty($prj_order)) {
            throw_exception($prj_order_id . '不是有效的订单');
        }

        //项目加息奖励
        $prj_order_reward_model = D('Financing/PrjOrderReward');
        $prj_order_reward = $prj_order_reward_model
            ->field('sum(amount) amount')
            ->where([
                'order_id' => $prj_order_id,
                'uid' => $prj_order['uid'],
                'status' => ['eq', PrjOrderRewardModel::STATUS_WAIT],
                'reward_type' => ['in', [
                    PrjOrderRewardModel::REWARD_TYPE_RATE,
                    PrjOrderRewardModel::REWARD_TYPE_RATE_ADD,
                ]],
            ])->find();

        if ($prj_order_reward['amount'] == 0) {
            return true;
        }

        $now = time();
        $add_data = [
            'prj_id' => $prj_order['prj_id'],
            'prj_order_id' => $prj_order_id,
            'order_no' => $this->createOrderNo(),
            'uid' => $prj_order['uid'],
            'money' => $prj_order_reward['amount'],
            'status' => self::STATUS_INIT,
            'repay_time' => $prj_order['expect_repay_time'],
            'request_time' => $now,
            'response_code' => 0,
            'response_message' => '',
            'ctime' => $now,
            'mtime' => $now,
        ];

        if ($this->add($add_data) === false) {
            throw_exception('汇总用户奖励数据失败');
        }
    }

    /**
     * 产生编号 order_no
     * @return string
     */
    public function createOrderNo()
    {
        $id_gen_instance = new \Addons\Libs\IdGen\IdGen();
        return $id_gen_instance->get(\Addons\Libs\IdGen\IdGen::WORK_TYPE_PRJ_REPAY_ORDER_REWARD);
    }
}
<?php
/**
 * Created by PhpStorm.
 * User: luoman
 * Date: 16/9/27
 * Time: 上午10:45
 */

class PrjOrderBonusSummaryModel extends BaseModel
{

    // 浙商冻结状态
    const STATUS_INIT = '0';
    const STATUS_ING = '10'; //处理中
    const STATUS_SUCCESS = '20'; //成功
    const STATUS_FAIL = '30'; //失败
    const STATUS_UNSURE = '40'; //争议

    /**
     * 生成项目使用的满减券和营销红包的汇总记录
     * @param $prj_id
     * @param $total_bonus_money
     * @return array|bool
     */
    public function createPrjBonusOrder($prj_id, $total_bonus_money)
    {
        $order_no = $this->createOrderNo();
        $uid = C('ZS_BONUS_UID');

        if (empty($uid)) {
            $this->error = '请配置:ZS_BONUS_UID';
            return false;
        }

        $now = time();
        $add_data = [
            'order_no' => $order_no,
            'prj_id' => $prj_id,
            'uid' => $uid,
            'money' => $total_bonus_money,
            'bank_freeze_status' => self::STATUS_INIT,
            'ctime' => $now,
            'mtime' => $now,
        ];

        try {
            if ($insert_id = $this->add($add_data)) {
                return array_merge(['id' => $insert_id], $add_data);
            } else {
                $this->error = 'PRJ_ID:' . $prj_id . '添加失败';
                return false;
            }
        } catch (Exception $e) {
            $this->error = $e->getMessage();
            return false;
        }
    }

    /**
     * 产生编号 order_no
     * @return string
     */
    public function createOrderNo()
    {
        $id_gen_instance = new \Addons\Libs\IdGen\IdGen();
        return $id_gen_instance->get(\Addons\Libs\IdGen\IdGen::WORK_TYPE_PRJ_ORDER_BONUS_SUMMARY);
    }
}
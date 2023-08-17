<?php

/**
 * Created by PhpStorm.
 * User: 000429
 * Date: 2014/11/12
 * Time: 16:03
 */
class RepayDealRecordModel extends BaseModel
{
    protected $tableName = 'repay_deal_record';

    const STATUS_TODO = 1;   // 初始化
    const STATUS_SUCCESS = 2;   // 成功
    const STATUS_FAILED = 3; // 失败
    const STATUS_DOING = 4; // 执行中
    const STATUS_PAYED = 5;   // 支付完成
    const STATUS_FAILED2 = 6; // 失败
    const STATUS_PAYED_Nolast = 51;   // 支付完成 但是最后的下一层异步没有调用成功
    
    const LAST_DEFAULT = 0;   //不是最后一个
    const LAST_CURR_LEVEL = 1;   //当前层最后一个
    const LAST_ALL_LEVEL = 2;   //所有层最后一个


    /**
     * 当前还款级别是否还有未经过task1处理的数据
     * @param $base_prj_id
     * @param $repay_level
     * @return mixed
     */
    public function hasTodoLevelRecord($base_prj_id, $repay_level)
    {
        $where = [
            'base_prj_id' => $base_prj_id,
            'repay_level' => $repay_level,
            'status' => self::STATUS_TODO,
        ];
        return $this->where($where)->getField('id');
    }

    /**
     * 当前base_prj_id下是否还有未经过task2处理的数据
     * @param $base_prj_id
     * @return mixed
     */
    public function hasTodoAllRecord($base_prj_id)
    {
        $where = [
            'base_prj_id' => $base_prj_id,
            'status' => self::STATUS_PAYED,
        ];
        return $this->where($where)->getField('id');
    }

}
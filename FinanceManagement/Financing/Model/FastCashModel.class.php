<?php

/**
 * 速兑通
 * Class FastCashModel
 */
class FastCashModel extends BaseModel
{
    protected $tableName = 'prj_fast_cash';

    const STATUS_WAITING = 1; //待审核;
    const STATUS_DOING = 2; //变现中;
    const STATUS_DONE = 3; //已变现;

    //获取已经变现总额
    public function getTotalCashMoney($uid,$orderid,$status){
       $result = $this->where(array(
            'uid' => $uid,
            'prj_order_id' => $orderid,
            'status' => array('IN', $status)
        ))->field('SUM(money) AS SUM')->find();

        return  $result['SUM'];

   }

    
}

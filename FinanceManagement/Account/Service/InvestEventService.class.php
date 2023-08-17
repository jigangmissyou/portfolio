<?php

/**
 * Created by PhpStorm.
 * User: 001073
 * Date: 2015/11/12
 * Time: 10:53
 */
class InvestEventService
{
    protected $queue_name = 'invest_event';

    public function addToQueue($uid, $order_id)
    {
        try {
            $queue_data = $this->checkCallback($uid, $order_id);
            if (!$queue_data) return true;
            $ret = queue($this->queue_name, $order_id, $queue_data);
            if (!$ret) {
                throw_exception($this->queue_name . "入队列失败！");
            }
            return true;
        } catch (Exception $e) {
            echo($e->getMessage());
            return true;
        }
    }

    public function checkCallback($uid, $order_id)
    {
        $prj_id = M('prj_order')->where(array('id' => $order_id))->getField('prj_id');
        $queue_data = array('uid' => $uid, 'order_id' => $order_id, 'prj_id' => $prj_id);
        //118114电信活动的回调
        if (service('Activity/EcfTelecom')->isActivityProject($prj_id)) {
            return $queue_data;
        }
        return $queue_data;
    }

    public function process($uid, $order_id, $prj_id)
    {
        service('Api/InvestCallback')->callback($uid, array('order_id' => $order_id, 'prj_id' => $prj_id, 'uid' => $uid));
        return true;
    }

}
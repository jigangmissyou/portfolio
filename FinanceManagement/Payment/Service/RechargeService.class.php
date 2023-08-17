<?php
/**
 * User: BEN
 * Create: 14-6-18 下午2:58
 * Mail: xiaobenjiang@upg.cn
 */

class RechargeService extends BaseService {

    public function getFailedList($status='', $condition, $parameter, $order=""){
        return D('Payment/Ticket')->getFailedList($status, $condition, $parameter, $order);
    }

    public function getFirstSuccess($uid){
        return D('Payment/Ticket')->getFirstSuccessRecharge($uid);
    }
}
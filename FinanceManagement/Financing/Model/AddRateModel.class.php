<?php
/**
 * Created by PhpStorm.
 * User: 001164
 * Date: 2015/9/29
 * Time: 17:34
 */
Import("libs.Cache.Redis", ADDON_PATH, '.php');

use App\Modules\JavaApi\Service\JavaBonusService as JavaBonusService;
use App\Lib\Service\JavaService as JavaService;

class AddRateModel extends BaseModel{
    private $_service;
    protected $tableName = 'user_market_ticket';
    const UNUSED_STATUS =1;
    const USED_STATUS = 2;
    const EXPIRE_STATUS = 3;
    const FREEZE_STATUS = 4;
    const ROLLBACK_STATUS = 5; //记录回滚日志
    const MANJIAN =1;
    const JIAXI =2;
    function __construct()
    {
//        parent::__construct();
//        $this->redis = \Addons\Libs\Cache\Redis::getInstance('default',false);
//        $this->redis->select( 6 );
        parent::__construct();
        if (C('APP_STATUS') == 'product') {
            $this->_debug = false;
        }
        $this->_service = new JavaBonusService();
    }

    /** 加息券 old
     * @param $uid
     * @param $prjId
     * @param $timeLimitDay
     * @param $amount
     * @param $requestId
     * @param $prjName
     * @param array $params
     */
//    public function freezeRateTicket($uid,$prjId,$timeLimitDay,$amount,$requestId,$prjName,$params){
//        try{
//            $ticket_type=$params['reward_type'];
//            if($ticket_type!=3){
//                throw_exception("异常数据，使用的不是加息券!");
//            }
//            $ticket_id=(int)$params['reward_id'];
//            if($ticket_id<=0){
//                throw_exception("加息券参数异常!");
//            }
//            $key = 'JX_'.$ticket_id;
//            $key_exists = D('Financing/ReduceTicket')->checkKeyExist($key);
//            if($key_exists){
//                throw_exception("加息券使用频繁，请稍后再试~");
//            }else{
//                $this->redis->set($key,1,5);
//            }
//            $result = M('user_market_ticket')->where(array('id'=>$ticket_id,'uid'=>$uid,'user_bonus_status'=>self::UNUSED_STATUS,'type'=>self::JIAXI))->find();
//            if(!$result){
//                \Addons\Libs\Log\Logger::info("查询加息券异常,没有该加息券!", "Financing/AddRate/freezeRateTicket", array('ticket_id' => $ticket_id,'uid'=>$uid,'disp_name'=>$result['disp_name'],'prj_limit'=>$result['prj_limit']));
//                throw_exception("查询加息券异常!");
//            }
//            $freezeAmount = $this->getAmountRateAdd($amount, $ticket_id, $prjId);
//
////            //查询redis数据是否异常
////            $key = 'JX_'.$uid.'_'.$result['rate'].'_'.$result['prj_limit'];
////            $key_exists = D('Financing/ReduceTicket')->checkKeyExist($key);
//            $this->redis->set($requestId,$key,86400);
////            if(!$key_exists){
////                $this->redis->set($key,1);
////            }
////            $count = $this->redis->get($key);
////            if($count<=0){
////                if($count<0){
////                    $r = $this->redis->set($key,0);
////                }
////                \Addons\Libs\Log\Logger::info("加息券库存出现异常", "Financing/AddRate/freezeRateTicket", array('key' => $key,'uid'=>$uid,'rate'=>$result['rate'],'prj_limit'=>$result['prj_limit']));
////                throw_exception("redis里此加息券库存为0!");
////            }
//            $data=array(
//                'uid'=>$uid,
//                'user_bonus_id'=>$ticket_id,
//                'request_id'=>$requestId,
//                'prj_id'=>$prjId,
//                'prj_name'=>$prjName,
//                'prj_limit'=>$timeLimitDay,
//                'money'=>$amount,
//                'amount'=>$freezeAmount,
//                'rate'=>$result['rate'],
//                'freeze_status'=>self::FREEZE_STATUS,
//                'type'=>self::JIAXI,
//                'ctime'=>time(),
//                'mtime'=>time(),
//            );
//            $result = M('user_market_ticket_order')->add($data);
//            if(!$result){
//                throw_exception("添加加息券订单失败!");
//            }
//            $data = array('user_bonus_status'=>self::FREEZE_STATUS);
//            $res = M('user_market_ticket')->where(array('id'=>$ticket_id,'uid'=>$uid))->save($data);
//            if(!$res){
//                throw_exception("冻结加息券失败!");
//            }
////            $r=$this->descAmount($key);
////            \Addons\Libs\Log\Logger::info("加息券剩余库存库写入redis", "Financing/AddRate/freezeRateTicket", array('key' => $key,'uid'=>$uid,'disp_name'=>$result['disp_name'],'prj_limit'=>$result['prj_limit'],'remain_val'=>$r));
//            return array(
//                'freezeAmount' => $freezeAmount,
//            );
//
//        }catch (Exception $e){
//            throw_exception($e->getMessage());
//        }
//    }

    /**
     * 2016323
     * 加息券解冻调用java接口
     * @param $uid
     * @param $prjId
     * @param $timeLimitDay
     * @param $amount
     * @param $requestId
     * @param $prjName
     * @param array $params
     */
    public function freezeRateTicket($uid,$prjId,$timeLimitDay,$amount,$requestId,$prjName,$params){
        try {
            $data = $this->_service->freezeBonus($uid, $prjId, $timeLimitDay, $amount, $requestId, $prjName,$params);

            return $data;

        }catch (Exception $e){
            throw_exception($e->getMessage());
        }
    }

//    /**解冻满减券 old
//     * @param $ticketId
//     * @param $uid
//     */
//    public function unFreezeRateTicket($uid, $requestId){
//        try{
//            //查询冻结成功的key,
//            $key = $this->redis->get($requestId);
//            if(!$key){
//                \Addons\Libs\Log\Logger::info("解冻回滚加息券库存查询key不存在", "Financing/AddRate/unFreezeRateTicket", array('key' => $key,'uid'=>$uid,'request_id'=>$requestId));
//            }else{
//                    $this->redis->del($key);
//                }
////            //恢复对应券的库存
////            $result = $this->incrAmount($key);
////            if($result){
////                $this->redis->delete($requestId);
////                \Addons\Libs\Log\Logger::info("解冻回滚加息券库存写入redis成功", "Financing/AddRate/unFreezeRateTicket", array('key' => $key,'uid'=>$uid,'request_id'=>$requestId));
////            }else{
////                \Addons\Libs\Log\Logger::info("解冻回滚加息券库存写入redis失败", "Financing/AddRate/unFreezeRateTicket", array('key' => $key,'uid'=>$uid,'request_id'=>$requestId));
////            }
////            $result = M('user_market_ticket_order')->where(array('request_id'=>$requestId,'uid'=>$uid,'freeze_status'=>self::FREEZE_STATUS))->find();
////            if(!$result){
////                return true;
////            }
////            $data = array('user_bonus_status'=>self::UNUSED_STATUS);
////            $res = M('user_market_ticket')->where(array('id'=>$result['user_bonus_id'],'uid'=>$uid))->save($data);
////            if(!$res){
////                throw_exception("解冻加息券失败!");
////            }
////            //更新订单状态为无效状态
////            $res = M('user_market_ticket_order')->where(array('request_id'=>$requestId,'uid'=>$uid,'freeze_status'=>self::FREEZE_STATUS))->save(array('freeze_status'=>self::ROLLBACK_STATUS,'mtime'=>time()));
////            if(!$res){
////                throw_exception("更新加息券订单无效失败!");
////            }
////            //redis库存相应的加息券进行恢复
////            $key = 'JX_'.$uid.'_'.$result['rate'].'_'.$result['prj_limit'];
////            $r = $this->incrAmount($key);
////            if($r){
////                \Addons\Libs\Log\Logger::info("解冻回滚加息券库存写入redis成功", "Financing/ReduceTicket/unFreezeReduceTicket", array('key' => $key,'uid'=>$uid,'rate'=>$result['rate'],'prj_limit'=>$result['prj_limit']));
////            }else{
////                \Addons\Libs\Log\Logger::info("解冻回滚加息券库存写入redis失败", "Financing/ReduceTicket/unFreezeReduceTicket", array('key' => $key,'uid'=>$uid,'rate'=>$result['rate'],'prj_limit'=>$result['prj_limit']));
////            }
//            return true;
//
//        }catch (Exception $e){
//            throw_exception($e->getMessage());
//        }
//    }

    /**解冻满减券
     * 2016323 调用java接口
     * @param $requestId
     * @param $uid
     */
    public function unFreezeRateTicket($uid, $requestId){
        try {
            $data = $this->_service->unfreezeBonus($uid, $requestId);

            return $data;
        } catch (Exception $e) {
            throw_exception($e->getMessage());
        }
    }

    /**加息券冻结解冻日志
     * @param $data
     * @return mixed
     */
    public function freeRateTicketLog($ticketId,$uid,$rate,$status){
        try{
            if($status == 1){
                $arr=array('freeze_rate'=>$rate,'bonus_status'=>1);
            }elseif($status ==2){
                $arr=array('used_rate'=>$rate,'bonus_status'=>2);
            }
            $data = array('bonus_id'=>$ticketId,'uid'=>$uid,'type'=>2,'ctime'=>time());
            $data = array_merge($arr,$data);
            $res = M('user_market_ticket_record')->add($data);
            if (!$res) {
                throw_exception("记录失败!");
            }
            return $res;
        }catch (Exception $e){
            throw_exception($e->getMessage());
        }
    }

    /**查询加息券
     * @param $uid
     * @return bool
     */
    public function checkMyRate($uid,$timeLimit){
        $where = array('uid'=>$uid,'user_bonus_status'=>1,'type'=>2);
        $where['prj_limit'] = array('elt',$timeLimit);
        $result = M('user_market_ticket')->field('id,rate,ctime,end_time')->where($where)->order('rate desc,end_time asc,ctime asc')->select();
        if($result){
            return $result;
        }else{
            return false;
        }
    }
    public function incrAmount($key){
        return $this->redis->incr( $key, 1 );
    }
    public function descAmount($key){
        return $this->redis->decr( $key, 1 );
    }

    // 获取加息券所产生的利息
    public function getAmountRateAdd($money, $rate, $prj_id, $only_unused = true,$order_id = 0)
    {
//        $where = array(
//            'id' => $user_ticket_id,
//            'user_bonus_status' => self::UNUSED_STATUS,
//            'type' => self::JIAXI,
//        );
//        if(!$only_unused) {
//            $where['user_bonus_status'] = array('NOT IN', array(self::EXPIRE_STATUS, self::ROLLBACK_STATUS));
//        }
//
//        $market_ticket = M('user_market_ticket')->where($where)->find();
//        if (!$market_ticket) {
//            return 0;
//        }

//        $bonus_rule = M('bonus_rule')->where(array(
//            'id' => $market_ticket['bonus_rule_id'],
//            'status' => 1,
//        ))->find();
//        if (!$bonus_rule) {
//            return 0;
//        }
        $rate = $rate;
        D("Financing/PrjModel");
        $project = M('prj')->where(array('id' => $prj_id))->field('end_bid_time,last_repay_date,prj_type,repay_way,bid_status')->find();
        if (!$project) {
            return 0;
        }
        //预售不使用加息券。预期收益不包含加息券
        $is_pre_sale = M("prj_ext")->where(array('prj_id' => $prj_id))->getField("is_pre_sale");
        if ($project['bid_status'] == PrjModel::BSTATUS_WATING && $is_pre_sale == 1) {
            return 0;
        }

        // 半年付息到期还本能否使用加息券的控制
        if(!C('IS_HALFYEAR_USE_JIAXI') && $project['repay_way'] == 'halfyear') {
            return 0;
        }

        $start_time = strtotime(date('Y-m-d', $project['end_bid_time']));
        $end_time = $project['last_repay_date'];
        if ($project['prj_type'] == 'H') {
            //如果是投资结束的状态,要获取订单的冻结时间，需要传order_id
            if (!$order_id && $only_unused === false) {
                throw_exception("order_id参数错误");
            }
            //订单的投资时间
            if ($only_unused === false) {
                $order_info = M("prj_order")->where(array('id' => $order_id))->find();
                $start_time = strtotime(date('Y-m-d', $order_info['freeze_time']));

            } else {
                //预计投资时间
                $start_time = time();
            }
            $end_time += 86400;
        }

        $amount = service('Financing/Project')->profitComputeByDate('year', $rate, $start_time, $end_time,
            $money);
        return $amount;
    }
}

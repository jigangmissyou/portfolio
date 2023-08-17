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

class ReduceTicketModel extends BaseModel{
    private $_service;
    protected $tableName = 'user_market_ticket';
    const UNUSED_STATUS =1;  //未使用
    const USED_STATUS = 2;   //已使用
    const EXPIRE_STATUS = 3;  //已过期
    const FREEZE_STATUS = 4;  //使用券后冻结状态
    const ROLLBACK_STATUS = 5; //记录回滚日志
    const MANJIAN =1;  //满减券
    const JIAXI =2;    //加息券
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

    /**冻结满减券
     * @param $uid
     * @param $timeLimitDay
     * @param $amount
     * @param $type  1满减券2加息券
     * @param $prjName
     * @param array $params
     */
//    public function freezeReduceTicket($uid, $prjId, $timeLimitDay, $amount, $requestId, $prjName, $params){
//
//            try{
//                $ticket_type=$params['reward_type'];
//                if($ticket_type!=2){
//                    throw_exception("异常数据，使用的不是满减券!");
//                }
//                $ticket_id=(int)$params['reward_id'];
//                if($ticket_id<=0){
//                    throw_exception("满减券参数异常!");
//                }
//                $key = 'MJ_'.$ticket_id;
//                $key_exists = $this->checkKeyExist($key);
//                if($key_exists){
//                    throw_exception("满减券使用频繁，请稍后再试~");
//                }else{
//                    $this->redis->set($key,1,5);
//                }
//                $result = M('user_market_ticket')->where(array('id'=>$ticket_id,'uid'=>$uid,'user_bonus_status'=>self::UNUSED_STATUS))->find();
//                if(!$result){
//                    \Addons\Libs\Log\Logger::info("查询满减券异常,没有该满减券!", "Financing/ReduceTicket/freezeReduceTicket", array('ticket_id' => $ticket_id,'uid'=>$uid,'disp_name'=>$result['disp_name'],'prj_limit'=>$result['prj_limit']));
//                    throw_exception("查询满减券异常,没有该满减券!");
//                }
//                if($amount<$result['disp_name']){
//                    \Addons\Libs\Log\Logger::info("投资金额小于满减券金额范围异常", "Financing/ReduceTicket/freezeReduceTicket", array('amount' => $amount,'uid'=>$uid,'disp_name'=>$result['disp_name'],'prj_limit'=>$result['prj_limit']));
//                    throw_exception("投资金额小于满减券金额范围异常!");
//                }
//                if($timeLimitDay<$result['prj_limit']){
//                    \Addons\Libs\Log\Logger::info("投资期限小于满减券投资期限范围异常!", "Financing/ReduceTicket/freezeReduceTicket", array('amount' => $amount,'uid'=>$uid,'disp_name'=>$result['disp_name'],'invest_limit'=>$timeLimitDay,'prj_limit'=>$result['prj_limit']));
//                    throw_exception("投资期限小于满减券投资期限范围异常!");
//                }
//                $this->redis->set($requestId,$key,86400);
////                $count = $this->redis->get($key);
////                if($count<=0){
////                    if($count<0){
////                        $r = $this->redis->set($key,0);
////                    }
////                    \Addons\Libs\Log\Logger::info("满减券库存出现异常", "Financing/ReduceTicket/freezeReduceTicket", array('key' => $key,'uid'=>$uid,'disp_name'=>$result['disp_name'],'prj_limit'=>$result['prj_limit']));
////                    throw_exception("redis里此满减券库存为0!");
////                }
//                $data=array(
//                    'uid'=>$uid,
//                    'user_bonus_id'=>$ticket_id,
//                    'request_id'=>$requestId,
//                    'prj_id'=>$prjId,
//                    'prj_name'=>$prjName,
//                    'prj_limit'=>$timeLimitDay,
//                    'money'=>$amount,
//                    'amount'=>$result['amount'],
//                    'freeze_status'=>self::FREEZE_STATUS,
//                    'type'=>self::MANJIAN,
//                    'ctime'=>time(),
//                    'mtime'=>time(),
//                );
//                $res_order = M('user_market_ticket_order')->add($data);
//                if(!$res_order){
//                    throw_exception("添加满减券订单失败!");
//                }
//                $data = array('user_bonus_status'=>self::USED_STATUS);
//                $res = M('user_market_ticket')->where(array('id'=>$ticket_id,'uid'=>$uid))->save($data);
//                if(!$res){
//                    throw_exception("冻结满减券失败!");
//                }
////                //记录满减券冻结日志
////                $log_data = array('uid'=>$uid,'bonus_id'=>$ticket_id,'amount'=>$result['amount'],'bonus_status'=>self::FREEZE_STATUS,'ctime'=>time(),'mtime'=>time());
////                $res = M('user_market_ticket_record')->add($log_data);
////                if(!$res){
////                    throw_exception("记录满减券冻结日志失败!");
////                }
////                $r=$this->descAmount($key);
////                \Addons\Libs\Log\Logger::info("满减券库存写入redis", "Financing/ReduceTicket/freezeReduceTicket", array('key' => $key,'uid'=>$uid,'disp_name'=>$result['disp_name'],'prj_limit'=>$result['prj_limit'],'remain_val'=>$r));
//                return array(
//                    'freezeAmount' => $result['amount'],
//                );
//
//            }catch (Exception $e){
//                throw_exception($e->getMessage());
//            }
//    }


    /**冻结满减券
     * 2016323 调用java接口
     * @param $uid
     * @param $timeLimitDay
     * @param $amount
     * @param $type  1满减券2加息券
     * @param $prjName
     * @param array $params
     */
    public function freezeReduceTicket($uid, $prjId, $timeLimitDay, $amount, $requestId, $prjName, $params){

        try {
            $data = $this->_service->freezeBonus($uid, $prjId, $timeLimitDay, $amount, $requestId, $prjName,$params);

            return $data;
        } catch (\Exception $e) {
            throw_exception($e->getMessage());
        }

    }

    /**解冻满减券 原
     * @param $ticketId
     * @param $uid
     */
//    public function unFreezeReduceTicket($uid, $requestId){
//        try{
//            //查询冻结成功的key,
//            $key = $this->redis->get($requestId);
//            if(!$key){
//                \Addons\Libs\Log\Logger::info("解冻回滚满减券库存查询key不存在", "Financing/ReduceTicket/unFreezeReduceTicket", array('key' => $key,'uid'=>$uid,'request_id'=>$requestId));
//            }else{
//                $this->redis->del($key);
//            }
////            //恢复对应券的库存
////            $result = $this->incrAmount($key);
////            if($result){
////                $this->redis->delete($requestId);
////                \Addons\Libs\Log\Logger::info("解冻回滚满减券库存写入redis成功", "Financing/ReduceTicket/unFreezeReduceTicket", array('key' => $key,'uid'=>$uid,'request_id'=>$requestId));
////            }else{
////                \Addons\Libs\Log\Logger::info("解冻回滚满减券库存写入redis失败", "Financing/ReduceTicket/unFreezeReduceTicket", array('key' => $key,'uid'=>$uid,'request_id'=>$requestId));
////            }
////            $result = M('user_market_ticket_order')->where(array('request_id'=>$requestId,'uid'=>$uid,'freeze_status'=>self::FREEZE_STATUS))->find();
////            if(!$result){
////                return true;
////            }
////            $data = array('user_bonus_status'=>self::UNUSED_STATUS);
////            //将用户的满减券置为未使用状态
////            $res = M('user_market_ticket')->where(array('id'=>$result['user_bonus_id'],'uid'=>$uid))->save($data);
////            if(!$res){
////                throw_exception("解冻满减券失败!");
////            }
////            //满减券使用记录更新为回滚状态
////            $res = M('user_market_ticket_order')->where(array('request_id'=>$requestId,'uid'=>$uid,'freeze_status'=>self::FREEZE_STATUS))->save(array('freeze_status'=>self::ROLLBACK_STATUS,'mtime'=>time()));
////            if(!$res){
////                throw_exception("回滚满减券订单失败!");
////            }
//////            //记录投资异常满减券回滚日志
//////            $log_data = array('uid'=>$uid,'bonus_id'=>$res['user_bonus_id'],'bonus_status'=>self::ROLLBACK_STATUS,'ctime'=>time(),'mtime'=>time());
//////            $res = M('user_market_ticket_record')->add($log_data);
//////            if(!$res){
//////                throw_exception("记录满减券解冻日志失败!");
//////            }
////            //redis库存相应的满减券进行恢复
////            $key = 'MJ_'.$uid.'_'.$result['disp_name'].'_'.$result['prj_limit'];
////            $r = $this->incrAmount($key);
////            if($r){
////                \Addons\Libs\Log\Logger::info("解冻回滚满减券库存写入redis成功", "Financing/ReduceTicket/unFreezeReduceTicket", array('key' => $key,'uid'=>$uid,'disp_name'=>$result['disp_name'],'prj_limit'=>$result['prj_limit']));
////            }else{
////                \Addons\Libs\Log\Logger::info("解冻回滚满减券库存写入redis失败", "Financing/ReduceTicket/unFreezeReduceTicket", array('key' => $key,'uid'=>$uid,'disp_name'=>$result['disp_name'],'prj_limit'=>$result['prj_limit']));
////            }
//            return true;
//        }catch (Exception $e){
//            throw_exception($e->getMessage());
//        }
//
//    }

    /**解冻满减券
     * 2016323 调用java接口
     * @param $ticketId
     * @param $uid
     */
    public function unFreezeReduceTicket($uid, $requestId){
        try {
            $data = $this->_service->unfreezeBonus($uid, $requestId);

            return $data;
        } catch (Exception $e) {
            throw_exception($e->getMessage());
        }

    }

    /**添加满减券
     * @param $uid
     * @return bool|mixed
     */
    public function addReduceTicket($uid,$serial_no,$event_no,$amount,$accountType,$type,$source){
        $this->startTrans();
        $userInfo = M('user')->where(array('uid'=>$uid))->find();
        if(!$userInfo){
            throw_exception("该用户查询异常!");
        }
        //获取满减券,兼容红包的规则表从规则表里读取的满减券类型是2，加息券类型是3
        $where = array('event_id'=>$event_no,'status'=>1,'type'=>$type);
        $datas = M('bonus_rule')->field('id,bonus_id,disp_name,amount,rate,prj_limit,use_tip,exp_date,end_time,remark')->where($where)->select();
        $count = count($datas);
        try {
            foreach ($datas as $data)
            {
                $data['uid']=$uid;
                $data['account_type']=$data['bonus_id'];
                $data['bonus_rule_id'] = $data['id'];
                $data['serial_no'] = $serial_no;
                $data['user_bonus_status']=self::UNUSED_STATUS;
                $data['prj_limit']=$data['prj_limit'];
                if($type==2){
                    $data['type']=self::MANJIAN;
                }elseif($type==3){
                    $data['type']=self::JIAXI;
                }
                unset($data['id']);
                $data['start_time']=strtotime(date('Y-m-d'));
                if($data['end_time']){
                     $data['end_time'] = $data['end_time'];   //如果规则表里有配置过期时间，那么使用规则表里配置的。
                }else{
                     $data['end_time']=strtotime(date('Y-m-d'))+(86400*$data['exp_date']-1);//暂时写成30天，到时候从规则表里去
                }
                $data['ctime']=time();
                $data['mtime']=time();
                $data['source']=$source;
                $lastID=M('user_market_ticket')->add($data);
//                $log['uid'] = $uid;
//                $log['serial_no'] = $serial_no;
//                $log['account_type'] = $accountType;
//                $log['event_no'] = $event_no;
//                $log['amount'] = $data['amount'];
//                $log['rate'] = $data['rate'];
//                $log['bonus_id'] = $lastID;
//                $log['bonus_status'] = self::UNUSED_STATUS;
//                $log['type'] = $data['type'];
//                $log['ctime'] = time();
//                $lastID=M('user_market_ticket_record')->add($log);
            }
        }
        catch (Exception $e){
            $this->rollback();
            throw_exception($e->getMessage());
        }
        $this->commit();
        //注册成功计数器加1,这里是防止异常注册
        if($event_no == 1) {
            $lock = new \Addons\Libs\Lock\Lock();
            $key = "FetchTicketByReg_" . $uid;
            $num = $lock->lock($key, 1);
            if ($num > 1) {
                \Addons\Libs\Log\Logger::info("注册时写入计数器异常", "Financing/ReduceTicket/addReduceTicket",
                    array('key' => $key));
                return false;
            }
        }
//        //增加用户满减券数据到redis
//        foreach ($datas as $data)
//        {
//            if($type==2){
//                $key = 'MJ_'.$uid.'_'.$data['disp_name'].'_'.$data['prj_limit'];
//            }elseif($type==3){
//                $key = 'JX_'.$uid.'_'.$data['rate'].'_'.$data['prj_limit'];
//            }
//            $IncrResult = $this->incrAmount($key);
//            if(!$IncrResult){
//                \Addons\Libs\Log\Logger::info("注册送满减券写入redis失败", "Financing/ReduceTicket/addReduceTicket", array('key' => $key,'uid'=>$uid,'prj_limit'=>$data['prj_limit']));
//            }else{
//                \Addons\Libs\Log\Logger::info("注册送满减券写入redis成功", "Financing/ReduceTicket/addReduceTicket", array('key' => $key,'uid'=>$uid,'prj_limit'=>$data['prj_limit']));
//            }
//        }
        //满减券发送消息
        if($type==2){
            if($count==1){
                $money = $datas[0]['disp_name']/100;
                $amount = $datas[0]['amount']/100;
                $arr[]=$userInfo['uname'];
                $arr[]='['.$money.'-'.$amount.'满减券]';
                $arr[]=$datas[0]['use_tip'];
                $arr[]=$datas[0]['remark'];
                //只获得一张满减券的时候暂时不发放站内信
                //$result = service('Message/Message')->sendMessage($uid,1,230,$arr,$objId=0,$mustConfig=0,$send_type=array(1,2), $isNotice=false);
            }elseif($event_no==1){
                $arr[]=$userInfo['uname'];
                $result = service('Message/Message')->sendMessage($uid,1,231,$arr,$objId=0,$mustConfig=0,$send_type=array(1,2), $isNotice=false);
            }
        }

        return true;
    }

    /**
     * 计算最优的满减券
     * @param $amount
     * @param $uid
     */
    public function calBestTicket($amount,$uid,$timeLimit,$init){
        try{
            //金额最优化满减券;分别计算1年，6个月，3个月，1个月
            if($timeLimit>=360){      //仅限投资1年的
                $where['uid']=$uid;
                $where['prj_limit']=array('egt',1);
                $where['type']=self::MANJIAN;
                $where['user_bonus_status']=self::UNUSED_STATUS;
            }elseif($timeLimit>=180){   //仅限投资6个月的
                $where['uid']=$uid;
                $where['prj_limit']=array(array('egt',1),array('elt',180));
                $where['type']=self::MANJIAN;
                $where['user_bonus_status']=self::UNUSED_STATUS;
            }elseif($timeLimit>=89){   //仅限投资3个月的
                $where['uid']=$uid;
                $where['prj_limit']=array(array('egt',1),array('elt',89));
                $where['type']=self::MANJIAN;
                $where['user_bonus_status']=self::UNUSED_STATUS;
            }elseif($timeLimit>=28 && $timeLimit<89){ //仅限投资1个月-3个月的
                $where['uid']=$uid;
                $where['prj_limit']=array(array('egt',1),array('lt',89));
                $where['type']=self::MANJIAN;
                $where['user_bonus_status']=self::UNUSED_STATUS;
            }elseif($timeLimit>=1 && $timeLimit<28){
                $where['uid']=$uid;
                $where['prj_limit']=array(array('egt',1),array('lt',28));
                $where['type']=self::MANJIAN;
                $where['user_bonus_status']=self::UNUSED_STATUS;
            }else{
                return 0;
            }

            $result = M('user_market_ticket')->field('id,disp_name,amount,ctime,end_time')->where($where)->order('amount desc,end_time asc,ctime desc')->select();
            if($init==1){
                foreach($result as $res){
                    if($amount >= ($res['disp_name']-$res['amount'])){
                        $reward_name = ($res['disp_name']/100).'-'.$res['amount']/100;
                        return array('id'=>$res['id'],'disp_name'=>$res['disp_name'],'amount'=>$res['amount'],'reward_name'=>$reward_name,'end_time'=>date('Y-m-d',$res['end_time']),'guo_qi'=>$res['end_time'],'huo_de'=>$res['ctime']);
                    }
                }
            }else{
                foreach($result as $res){
                    if($amount >= $res['disp_name']){
                        $reward_name = ($res['disp_name']/100).'-'.$res['amount']/100;
                        return array('id'=>$res['id'],'disp_name'=>$res['disp_name'],'amount'=>$res['amount'],'reward_name'=>$reward_name,'end_time'=>date('Y-m-d',$res['end_time']),'guo_qi'=>$res['end_time'],'huo_de'=>$res['ctime']);
                    }
                }
            }
        }catch (Exception $e){
            throw_exception($e->getMessage());
        }

    }

    /**查询我的满减券
     * @param $uid
     * @return bool
     */
    public function checkMyTicket($uid){
        $result = M('user_market_ticket')->where(array('uid'=>$uid,'user_bonus_status'=>self::UNUSED_STATUS,'type'=>self::MANJIAN))->find();
        if($result){
            return true;
        }else{
            return false;
        }
    }
    public function addOneReduceTicket($uid,$event_no,$amount){
        $where = array('bonus_id'=>1,'event_id'=>$event_no,'disp_name'=>$amount,'status'=>1);
        $data = M('bonus_rule')->field('id,disp_name,amount,prj_limit,start_time,end_time,use_tip')->where($where)->find();
        $data['uid']=$uid;
        $data['bonus_rule_id'] = $data['id'];
        $data['user_bonus_status']=1;
        $data['prj_limit']=$data['prj_limit'];
        $data['type']=1;
        unset($data['id']);
        $data['ctime']=time();
        $data['mtime']=time();
        $data['source']=$event_no;
        $lastID=M('user_market_ticket')->add($data);
        if($lastID){
            $money = $data['disp_name']/100;
            $amount = $data['amount']/100;
            $arr[1]='['.$money.'-'.$amount.']';
            $arr[2]=$uid;
            service('Message/Message')->sendMessage($uid,1,234,$arr,$objId=0,$mustConfig=0,$send_type=array(1,2), $isNotice=false);
        }

    }

    /**生成满减券序列号
     * @param $uid
     * @param string $pre
     * @return string
     */
    public function makeSerialNo($uid, $pre = "MANJIAN")
    {
        $today = date('Ymd');
        Import("libs.Counter.MultiCounter", ADDON_PATH);
        $oCounter = MultiCounter::init(Counter::GROUP_DEFAULT, Counter::LIFETIME_TODAY);
        $key = 'mj_ticket_'. $today;
        $number = $oCounter->incr($key) + 10000;
        return $pre . $today . $uid . $number;
    }
    /**满减券库存加1
     * @param $key
     * @return int
     */
    public function incrAmount($key){
        for($i=0;$i<3;$i++){
            $result = $this->redis->incr( $key, 1 );
            if($result){
                return true;
            }
        }
    }
    /**满减券库存减1
     * @param $key
     * @return int
     */
    public function descAmount($key){
        return $this->redis->decr( $key, 1 );
    }

//    public function  checkIsUsedTicket($uid,$prjId){
//        $result = M('user_market_ticket_order')->where(array('uid'=>$uid,'prj_id'=>$prjId,'freeze_status'=>array('IN',array(2,4))))->find();
//        if($result){
//            return true;
//        }else{
//            return false;
//        }
//    }

    /**查询满减券，加息券的key是否存在
     * @param $key
     * @return bool
     */
    public function checkKeyExist($key){
        $result = $this->redis->exists($key);
        return $result;
    }


}
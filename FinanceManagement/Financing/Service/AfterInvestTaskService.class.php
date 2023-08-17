<?php

/**
 * Created by PhpStorm.
 * User: 001181
 * Date: 2015/5/14
 * Time: 15:05
 * Desc:投资完成以后的大任务处理,然后通过自己的队列分发子任务
 */
use App\Modules\JavaApi\Service\FetchBonusByInvestService as FetchBonusByInvestService;

class AfterInvestTaskService extends BaseService
{
    //总任务的队列名称
    const QUEUE_GENERAL_NAME = "after_invest";
    //子任务的队列的名称,所有的子任务队列名称在此声明
    const QUEUE_SUB_NAME_FIRST_REWARD = "first_invest_reward";
    const QUEUE_SUB_NAME_IS_APPOINT_CANCAL = "is_appoint_cancel";
    const QUEUE_SUB_NAME_FIRST_REWARD_GAYS = "first_invest_reward_back";

    //转推荐下线时间
    const REWARD_BACK_END_TIME = '2015-12-03 23:59:59';

    /**
     * @param $uid
     * @param $order_id
     * 投资以后的总任务的生产
     */
    public function notice($uid, $order_id, $is_appoint_invest)
    {
        try {
            //第一次投资的通知。其实就是进总任务的队列
            $queue_data = array(
                'uid' => $uid,
                'order_id' => $order_id,
                'is_appoint_invest' => $is_appoint_invest,
            );
            $ret = queue(self::QUEUE_GENERAL_NAME, $order_id, $queue_data);
            if (!$ret) {
                throw_exception(self::QUEUE_GENERAL_NAME . "入队列异常");
            }
            return $ret;
            //其他需要的子任务在此添加
        } catch (Exception $e) {
            throw_exception($e->getMessage());
        }
    }

    /**
     * @param $params
     * 总任务的消费,这里是所有子任务的分发
     */
    public function procAfterInvestQueue($params)
    {
        $uid = $params['uid'];
        $order_id = $params['order_id'];
        try {
            //队列分发在此执行,只做子任务的生产,不做其他的事情,不能抛出任何异常
            

//            //如果用户时候首投，给推荐人发送奖励
//            service('Activity/XinPartner3')->sendUserStepReward($uid);
            //2016  植树节活动
            $time_limit = C('ACTIVITY.ACTAPRIL');
            $start = strtotime($time_limit['start_time']);
            $end = strtotime($time_limit['end_time']);
            $timeact = time();
            if ($timeact >= $start && $timeact <= $end) {
                service('Activity/AprilFoe')->methodone($uid, $order_id, '');
                service('Activity/AprilFoe')->methodtwo($uid, $order_id, '');
            }

            service("Financing/Appoint")->pushCheckAppointCancelInvest(self::QUEUE_SUB_NAME_IS_APPOINT_CANCAL, $uid, $order_id, 43, $params['is_appoint_invest']);
            //关于投资后的http回调
            service('Account/FirstInvestEvent')->addToQueue($uid,$order_id);//第一次投资后的回调
            service('Account/InvestEvent')->addToQueue($uid,$order_id);//普通的每次投资后的可能的回调
            //新人满月
            service('Activity/XinRenManYue')->addQueue($uid, $order_id);
            //add by 001637 
            //判断悦居中海抽奖活动是否开启，若开启处理被推荐用户投资后的事项
            if(C('YUEJU_ACTI_IS_OPEN')) service('Weixin/YuejuActivity')->addToQueue($uid,$order_id);
            //人人利用户投资后处理事项
            service('Api/RrlInvestCallback')->callback($uid,array('order_id'=>$order_id, 'is_xprj_order' => 0));
            //值得投用户回调
            service('Api/ZhiDeTouRequest')->dataPushToZhiDeTou($uid, $order_id);
            //加息盒子用户回调
            service('Api/JxhzRequest')->dataPushJxhz($uid,$order_id);
            //红包雨
            service('Activity/HongBaoYu')->addToQueue($uid,$order_id);
            //网贷天眼用户投资回调
            service('Api/TianYanRequest')->dataPushToTianYan($uid, $order_id);

            //鑫拍档三期活动时间内，如果投资判断是否符合条件增加抽奖机会和发现金红包
            service('Activity/XinPartner3')->addQueue($uid,$order_id);
            
            //投资以后的埋点对接JAVA
            $fetch_bonus_invest_service = new \App\Modules\JavaApi\Service\JavaBonusService();
            $fetch_bonus_invest_service->pushInformEvent(\App\Modules\JavaApi\Service\JavaBonusService::INFORM_EVENT_INVEST_QUEUE_NAME,$order_id,array('uid'=>$uid,'order_id'=>$order_id));
            $service = new FetchBonusByInvestService();
            $service->pushFirstInvest(self::QUEUE_SUB_NAME_FIRST_REWARD, $uid, $order_id);
            return true;
        } catch (Exception $e) {
            //记录日志TODO
            D("Public/Exception")->save($e->getMessage(), 'proc_after_invest');
            return true;
        }
    }


} 

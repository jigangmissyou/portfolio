<?php
/**
 * Created by PhpStorm.
 * User: 001164
 * Date: 2015/9/29
 * Time: 15:25
 */
class AddRateService extends BaseService{

    /**
     * @param $ticketId
     * @param $uid
     * @param $timeLimitDay
     * @param $amount
     * @param $requestId
     * @param array $params
     */
    public function freezeBonus($uid,$prjId,$timeLimitDay,$amount,$requestId,$prjName,$params)
    {
        try {
            $data = D("Financing/AddRate")->freezeRateTicket($uid, $prjId, $timeLimitDay, $amount, $requestId, $prjName, $params
            );
            return $data;
        } catch (Exception $e) {
            throw_exception($e->getMessage());
        }
    }
    /**
     * 解冻加息券
     * @param $ticketId
     * @param $uid
     * @param int $type
     */
    public function unFreezeBonus($uid, $requestId){
        try {
            $data = D("Financing/AddRate")->unFreezeRateTicket($uid, $requestId);
            return $data;
        } catch (Exception $e) {
            throw_exception($e->getMessage());
        }
    }
    /**
     * 计算加息券
     * @param $money
     * @param $uid
     * @param $timeLimit
     */
    public function calBestRate($money,$uid,$prjId,$timeLimit){
        try {
            $returnArr=array();
            if($money == 0){
                return 0;
                exit();
            }
            $prjInfo=M('prj')->where(array('id'=>$prjId))->find();
            if(!$prjInfo){
                return 0;
                exit();
            }

            // 半年付息到期还本能否使用加息券的控制
            if(!C('IS_HALFYEAR_USE_JIAXI') && $prjInfo['repay_way'] == 'halfyear') {
                return 0;
            }

            //检查下是否有加息券
            $result = D("Financing/AddRate")->checkMyRate($uid,$timeLimit);
            if(!$result){
                return 0;
                exit();
            }
            foreach($result as &$res){
                $add_profit = D('Financing/AddRate')->getAmountRateAdd($money,$res['id'], $prjId);
                $res['reward_name'] = number_format($res['rate']/10,2).'%';
                $res['amount'] = round($add_profit/100,2);
                $res['expire_date'] = date('Y-m-d',$res['end_time']);
                $res['reward_type']= 3;
            }
            $returnArr['reward_type']=3;
            $returnArr['reward_id']=$result[0]['id'];
            $add_profit = D('Financing/AddRate')->getAmountRateAdd($money,$result[0]['id'], $prjId);
            $returnArr['amount']= round($add_profit/100,2);
            $returnArr['original_num']= $add_profit;
            $returnArr['reward_name']= $result[0]['reward_name'];
            $returnArr['rate']= $result[0]['rate'];
            $returnArr['guo_qi']= $result[0]['end_time'];
            $returnArr['huo_de']= $result[0]['ctime'];
            $returnArr['list'] = array_slice($result,1);
            return $returnArr;
        } catch (Exception $e) {
            throw_exception($e->getMessage());
        }

    }
}

<?php

/**
 * Created by PhpStorm.
 * User: will
 * Date: 15/9/29
 * Time: 11:57
 * Desc:使用红包的记录，这里实现两个方法.一个冻结,一个解冻
 */
class UseRewardService extends BaseService
{
    protected $_service;

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 发起冻结请求
     * @param $uid
     * @param $prjId
     * @param $timeLimitDay
     * @param $amount
     * @param $requestId
     * @param $prjName
     * @param array $params
     * @return mixed
     */
    public function freezeReward($uid, $prjId, $timeLimitDay, $amount, $requestId, $prjName, $params = array())
    {
        $db = new BaseModel();
        $db->startTrans();
        D("Financing/UserBonusFreeze");
        try {
            K($params,'reward_type');
            $service = $this->getService($params['reward_type']);
            $data = $service->freezeBonus($uid, $prjId, $timeLimitDay, $amount, $requestId, $prjName, $params);

            if (!is_array($data)) {//java接口冻结失败返回message,抛出异常
                \Addons\Libs\Log\Logger::info("UID[$uid]加入司马小鑫X_PRJ[$prjId]冻结奖励失败", 'BUY_FREEZE_FAIL', array('data'=>$data));
                throw_exception("加入失败，请稍后再试!");
            }

            //(司马小鑫和普通项目)红包、满减券、加息券重复冻结失败，返回金额为0或者加息券利率为0的时候，在这里记录日志
            if ((($params['reward_type'] == 1 || $params['reward_type'] == 2) && $data['freezeAmount'] == 0) || ($params['reward_type'] == 3 && $data['rate'] == 0)) {
                \Addons\Libs\Log\Logger::info($prjName . "使用奖励失败", "PRJ_REWARD_FAIL", array('uid' => $uid, 'prjid' => $prjId, 'money' => $amount, 'requestId' => $requestId));
                throw_exception('重复使用奖励');
            }
            //冻结成功以后,记录冻结结果
            $log = array(
                'uid' => $uid,
                'prj_id' => $prjId,
                'request_id' => $requestId,
                'amount' => $data['freezeAmount'],
                'has_freeze' => 0,
                'type' => $params['reward_type'],
                'account_type' => $data['accountType'],
                'rate'=>$data['rate']*10,
            );
            if (strstr($prjName, "司马小鑫")) {
                $log['project_type'] = 1;
            }
            D("Financing/UserBonusFreeze")->addFreeze($log);
            if ($params['reward_type'] != UserBonusFreezeModel::TYPE_RATE && $data['freezeAmount'] > 0 && !strstr($prjName, "司马小鑫")) { //司马小鑫红包奖励直接到账，不记录到待收收益
                //同时增加用户金额.这里是一个事务。
                $payAccountModel = D("Payment/PayAccount");
//                $payAccountModel->addInvestRewardMoney($uid, $data['freezeAmount']);
                //同时增加用户的预期收益
                $payAccountModel->changeWillReward($uid, $data['freezeAmount']);
            }
            $db->commit();
            return $data;
        } catch (Exception $e) {
            $db->rollback();
            throw_exception($e->getMessage());
        }
    }

    /**
     * 冻结红包
     * @param $uid
     * @param $requestId
     * @param $params
     * @return mixed
     */
    public function unFreezeReward($uid, $requestId, $params)
    {
        try {
            K($params, 'reward_type');
            K($params, 'memo', "冻结奖励异常:request_id->{$requestId}未捕捉到");
            $service = $this->getService($params['reward_type']);
            $service->unFreezeBonus($uid, $requestId);

            return true;
        } catch (Exception $e) {
            throw_exception($e->getMessage());
        }
    }

    /**
     * 获取service的方法
     */
    protected function getService($type)
    {
        D("Financing/UserBonusFreeze");
        $service = null;
        switch ($type) {
            case UserBonusFreezeModel::TYPE_BONUS:
                $service = new \App\Modules\JavaApi\Service\JavaBonusService();
                break;
            case UserBonusFreezeModel::TYPE_REDUCE:
                $service = service("Financing/ReduceTicket");
                break;
            case UserBonusFreezeModel::TYPE_RATE:
                $service = service("Financing/AddRate");
                break;
            case UserBonusFreezeModel::TYPE_LCJ:
                $service = new \App\Modules\JavaApi\Service\JavaBonusService();
                break;
            default :
                throw_exception("无效的奖励类型参数");
                break;
        }
        $this->_service = $service;

        return $service;
    }

    public function createRequestId($data)
    {
        $data['ip'] = get_client_ip();
        $query = http_build_query($data);

        return strtoupper(md5(time() . rand(1000, 9999) . $query));
    }
}
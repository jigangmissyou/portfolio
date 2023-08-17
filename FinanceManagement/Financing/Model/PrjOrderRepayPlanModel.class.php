<?php

class PrjOrderRepayPlanModel extends BaseModel {
    protected $tableName = 'prj_order_repay_plan';

    const ORDER_REPAYMENT_STATUS_WAIT = 1;      // 待还款
    const ORDER_REPAYMENT_STATUS_SUCCESS = 2;   // 还款结束
    const ORDER_REPAYMENT_STATUS_FAIL = 3;      // 废弃，不还款
    const ORDER_REPAYMENT_STATUS_DOING = 4;      //还款中

    const ZS_REPAY_STATUS_INIT= 0; //初始化
    const ZS_REPAY_STATUS_BEFORE_CALLBACK = 1; //鑫合汇还款后接受浙商通知前
    const ZS_REPAY_STATUS_ING = 10; //浙商处理中
    const ZS_REPAY_STATUS_SUCCESS = 20; //浙商还款成功
    const ZS_REPAY_STATUS_FAIL = 30; //浙商还款失败

    const PTYPE_ORG = 1;        // 用款期
    const PTYPE_BIDING = 2;     // 募集期
    const PTYPE_DEFAULT = 3;    // 提前还款的罚息
    const PTYPE_EXTEND = 4;     // 展期还款

    static $showWord = array(
        '1' => '正常还款期',
        '2' => '募集期资金占用费',
        '3' =>'罚息',
        '4' => '展期',
    );


    public function save($data='',$where=array()){
        if(!$data) return false;
        $version = (int) $data['version'];
        $data['version'] = $version + 1;
        $where['version'] = $version;

        $ret = M("prj_order_repay_plan")->where($where)->save($data);
        if (!$ret) {
            \Addons\Libs\Log\Logger::err("PrjModelRepayPlanModel Save Error：SQL" . M("prj_order_repay_plan")->getLastSql(), 'prj_order_repay_plan');
        }
        return $ret;
    }
    
    /**
     * 获取一个项目的详细还款金额
     * @param number $prjId
     * @return array
     */
    public function getPrjRepayDetailByPrjId($prjId){
    	$condition = array(
    		'prj_id' => $prjId,
            'status' => array('NEQ', self::ORDER_REPAYMENT_STATUS_FAIL)
//     		'status' => self::ORDER_REPAYMENT_STATUS_WAIT,
    	);
    	$field = 'sum(if(ptype = '.self::PTYPE_ORG.', principal, 0)) as principal, sum(if(ptype = '.self::PTYPE_ORG.', yield, 0)) as yield, sum(if(ptype = '.self::PTYPE_BIDING.', pri_interest, 0)) as mjqlixi, sum(if(ptype = '.self::PTYPE_DEFAULT.', pri_interest, 0)) as lixibc';
    	$result = $this->where($condition)->field($field)->find();
    	
    	foreach ($result as $key => $value){
    		$result[$key] = number_format($value / 100, 2);
    	}
    	return $result;
    }
    
    /**
     * 根据天数获取最近要还款的总额
     * @param number $uid
     * @param array $daysArr 由天数构成的数组
     * @return array
     */
    public function getRecentRepayTotalByDays($uid, $daysArr = array(3, 7)){
    	
    	if(!$uid || !$daysArr){
    		return;
    	}
    	$daysArr = array_values((array)$daysArr);
    	arsort($daysArr);
    	$cacheKey = $uid.'_'.md5(implode('_', $daysArr));
    	
    	$prjModel = D('Financing/Prj');
    	$result = S($cacheKey);
    	if(empty($result) || strtolower(C('APP_STATUS')) != 'product'){
	    	$now = time();
	    	$where = array();
	    	$user = M()->field('t1.uid, t1.dept_id, t2.is_merchant')->table('fi_user t1')->join('left join fi_user_account t2 using(uid)')->where(array('t1.uid' => $uid))->find();
	    	
	    	if($user['is_merchant']){
	    		$where['t1.tenant_id'] = $uid;
	    	} else {
	    		$where['t1.dept_id'] = $user['dept_id'];
	    	}
	    	$_daysArr = array();
	    	foreach ($daysArr as $k => $day){
	    		$_daysArr[$daysArr[$k]] = $now + $day * 86400;
	    	}
	    	$where['t1.bid_status'] = PrjModel::BSTATUS_REPAYING;
	    	$where['t2.status'] = 1;
	    	$where['_string'] = "t2.repay_date between {$now} and ".current($_daysArr);
	    	$repayPlan = M()->table('fi_prj t1')->join('left join fi_prj_repay_plan t2 on t1.id = t2.prj_id')->field('t2.*')->where($where)->select();
	    	
	    	$result = array_fill_keys($daysArr, 0);
	    	
	    	foreach ($repayPlan as $key => $value){
	    		foreach ($_daysArr as $k => $v){
	    			if($value['repay_date'] <= $v){
	    				$result[$k] += $value['pri_interest'];
	    			}
	    		}
	    	}
	    	
	    	foreach ($result as $k => $v){
	    		$result[$k] = number_format($v / 100, 2);
	    	}
	    	S($cacheKey, $result, 3600);
    	}
    	ksort($result);
    	return $result;
    }

    /**
     * 获取还款中 或还款结束之后 的还款计划
     * @param $prj_id
     * ptype  1- 正常 4-展期
     */
    function getRepayPlanAfter($prj_id,$order_id=0){
        if($order_id){
            $sql = "SELECT ptype,repay_periods,repay_date,SUM(rest_principal) AS rest_principal,SUM(pri_interest) AS benxi,SUM(principal) AS benjing,SUM(yield) AS lixi
                FROM fi_prj_order_repay_plan WHERE prj_id=".$prj_id." AND prj_order_id=".$order_id." AND STATUS!=".self::ORDER_REPAYMENT_STATUS_FAIL."
                GROUP BY ptype,repay_periods";
        }else{
            $sql = "SELECT ptype,repay_periods,repay_date,SUM(rest_principal) AS rest_principal,SUM(pri_interest) AS benxi,SUM(principal) AS benjing,SUM(yield) AS lixi
                FROM fi_prj_order_repay_plan WHERE prj_id=".$prj_id." AND STATUS!=".self::ORDER_REPAYMENT_STATUS_FAIL."
                GROUP BY ptype,repay_periods";
        }

        $result = $this->query($sql);
        $data = array();
        $totalProfit = 0;//总共的利息
        $totalMoney = 0; //总共本息
        $totalPrincipal =0;//总共本金
        foreach($result as $v){
            $temp = array();
            $temp['repay_date'] = date('Y-m-d',$v['repay_date']);
            $temp['no'] = $v['repay_periods'];
            $temp['last_capital_view'] = humanMoney($v['rest_principal'], 2, false);
            $temp['repay_money_view'] = humanMoney($v['benxi'], 2, false);
            $temp['capital_view'] = humanMoney($v['benjing'], 2, false);
            $temp['profit_view'] = humanMoney($v['lixi'], 2, false);

            $data[$v['ptype']][] = $temp;

            $totalProfit += $v['lixi'];
            $totalMoney += $v['benxi'];
            $totalPrincipal += $v['benjing'];
        }

        return array(
            'list' => $data,
            'totalProfit' => $totalProfit,
            'totalMoney' => $totalMoney,
            'totalPrincipal' => $totalPrincipal
        );
    }

    /**
     * 返回用户 针对某个标的还款计划 （标的状态为 待还款 还款中 还款完成）
     * @param $uid
     * @param $prj_id
     */
    function getPersonRepayPlan($uid,$prj_id){
        $sql = "SELECT rp.yield,rp.ptype,rp.principal,rp.pri_interest,rp.repay_date,rp.repay_periods,rp.rest_principal
                    from fi_prj_order_repay_plan rp INNER JOIN fi_prj_order po ON rp.prj_order_id = po.id
                    where po.uid=".$uid." and po.prj_id=".$prj_id;
        $result = M()->query($sql);
        $list = array();
        foreach($result['list'] as $v){
            $data = array();
            $data['repay_periods'] = $v['repay_periods'];
            $data['repay_date_view'] = date('Y-m-d',$v['repay_date']);
            $data['repay_money_view'] = humanMoney($v['pri_interest'],2,false);
            $data['yield_view'] = humanMoney($v['yield'],2,false);
            $data['principal_view'] = humanMoney($v['principal'],2,false);
            $data['rest_principal_view'] = humanMoney($v['rest_principal_view'],2,false);
            $list[$v['ptype']][] = $data;
        }
        return $list;
    }

}

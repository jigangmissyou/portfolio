<?php
import_app("Payment.Service.AccountSearchService");

class AccountSearchNewService extends AccountSearchService
{

    //提现收支记录
    public function getMyRecordList($uid, $status, $start_time, $end_time, $time_type,$ext_info= array())
    {
        $parameter['status'] = $status;//状态 充值-1 投资-2 回款-3 提现-4 奖金-5 变现-6  资金服务抵用券-7 服务费-8 余额转移-9
        $parameter['start_time'] = $start_time;//开始时间
        $parameter['end_time'] = $end_time;//结束时间
        $parameter['time_type'] = $time_type;//最近一周-week 最近一个月-month 最近六个月-6month

        D("Payment/PayAccount");
        $count_cond9 = $count_cond8 = $count_cond7 = $count_cond6 = $count_cond5 = $count_cond4 = $count_cond3 = $count_cond2 = $count_cond1 = array('uid' => $uid);
        $condition = array('uid' => $uid);
        $userinfo = D("Account/User")->getByUid($uid);
        D("Account/User")->initUserRole($userinfo);
        if (visit_source() != 'pc') {
            $condition['obj_type'] = array('neq', PayAccountModel::OBJ_TYPE_REPAY_FREEZE);
        }
        if($ext_info['visit_source']=='jinjiaosuo'){
            unset($condition['obj_type'] );
        }
        //司马小鑫不在收支记录里面展示的obj_type
        $xprj_not_in_obj_type = PayAccountModel::OBJ_TYPE_PAYFREEZE_X
            . "," . PayAccountModel::OBJ_TYPE_PAY_X
            . "," . PayAccountModel::OBJ_TYPE_PAY_X_TRANSFER
            . "," . PayAccountModel::OBJ_TYPE_XRPJ_BALANCE_INCOME
            . "," . PayAccountModel::OBJ_TYPE_XRPJ_BALANCE_OUT
            . "," . PayAccountModel::OBJ_TYPE_ROLLOUTX
            . "," . PayAccountModel::OBJ_TYPE_REWARD_CASHOUTX
            . "," . PayAccountModel::OBJ_TYPE_ZS_MOVING//余额转移暂时不显示
            . "," . PayAccountModel::OBJ_TYPE_ZS_MOVED
            . "," . PayAccountModel::OBJ_TYPE_ZS_REPAYING
            . "," .PayAccountModel::OBJ_TYPE_GIVEXREWARD;
        if ($status == 0) {
//            $map['obj_type'] = PayAccountModel::OBJ_TYPE_REPAY_FREEZE;
//            $map['in_or_out'] = 2;
//            $map2['_complex'] = $map;
            $map2 = array();
            $map2_not_in_obj_type = PayAccountModel::OBJ_TYPE_CASHOUTDONE
                . "," .PayAccountModel::OBJ_TYPE_PENALTY
                . "," .PayAccountModel::OBJ_TYPE_CANEL_BONUS
                . "," . PayAccountModel::OBJ_TYPE_ADD_COUPON
                . "," . PayAccountModel::OBJ_TYPE_EXPIRE_COUPON
                . ",90001"
                . "," . PayAccountModel::OBJ_TYPE_REWARDFREEMONEY
                . "," . $xprj_not_in_obj_type;
            if ($userinfo['role']) {
                $map2['obj_type'] = array(
                    'not in',
                    $map2_not_in_obj_type
                );//这些类型不展示
            } else {
                //投资人
                //投资的记录不再显示 投资冻结 -> 投资 Q4
                //提现的记录不再显示 提现冻结 -> 提现 Q4
                $map2['obj_type'] = array(
                    'not in',
                    $map2_not_in_obj_type
                    . "," . PayAccountModel::OBJ_TYPE_PAY
                    . "," . PayAccountModel::OBJ_TYPE_REPAY_FREEZE
                );//这些类型不展示
            }
            $map2['_logic'] = 'or';
            $map2['_string'] = '(obj_type = ' . PayAccountModel::OBJ_TYPE_PAY . ' and in_or_out = 1) or (obj_type = '. PayAccountModel::OBJ_TYPE_GIVEXREWARD .' and in_or_out = 2 )';//变现和投资都是71 in_or_out 不一样 Q4只过滤投资
            $condition['_complex'] = $map2;
        }
        if ($status == 1) {
            $condition['ticket_id'] = array('exp', ' is not NULL');
            $condition['cash_id'] = array('exp', ' is NULL');
        }
        $count_cond1['ticket_id'] = array('exp', ' is not NULL');
        $count_cond1['cash_id'] = array('exp', ' is NULL');

        if ($status == 2) {
            $condition['ticket_id'] = array('exp', ' is NULL');
            $condition['cash_id'] = array('exp', ' is NULL');
            $condition_not_in_obj_type = PayAccountModel::OBJ_TYPE_REPAY
                . "," . PayAccountModel::OBJ_TYPE_REPAY_FREEZE
                . "," . PayAccountModel::OBJ_TYPE_ROLLOUT
                . "," . PayAccountModel::OBJ_TYPE_RAISINGINCOME
                . "," . PayAccountModel::OBJ_TYPE_REWARD_CASHOUT
                . "," . PayAccountModel::OBJ_TYPE_REWARD_INVEST
                . "," . PayAccountModel::OBJ_TYPE_REWARDFREEMONEY
                . ",90001"
                . "," . PayAccountModel::OBJ_TYPE_TRANSFER
                . "," . PayAccountModel::OBJ_TYPE_EXPIRE_COUPON
                . "," . PayAccountModel::OBJ_TYPE_ADD_COUPON
                . "," . PayAccountModel::OBJ_TYPE_USE_COUPON
                . "," . PayAccountModel::OBJ_TYPE_REWARD_CBACK
                . "," . PayAccountModel::OBJ_TYPE_REWARD_IBACK
                . "," . PayAccountModel::OBJ_PT_SERVICE_FEE
                . "," . PayAccountModel::OBJ_TYPE_USER_BONUS
                . "," . PayAccountModel::OBJ_TYPE_SHOUXU_SDT
                . "," . PayAccountModel::OBJ_TYPE_FINENCE_MANAGE_FEE
                . "," . PayAccountModel::OBJ_TYPE_GUARANTEE_FEE
                . "," . PayAccountModel::OBJ_TYPE_GUWEN_FEE
                . "," . PayAccountModel::OBJ_TYPE_ZC_ZHINAJIN
                . "," . PayAccountModel::OBJ_TYPE_ZC_PRI_INTEREST
                . "," . PayAccountModel::OBJ_TYPE_ZC_FAXI
                . "," . PayAccountModel::OBJ_TYPE_REPAY_SHOUXU
                . "," . PayAccountModel::OBJ_TYPE_ZS_MOVING
                . "," . PayAccountModel::OBJ_TYPE_ZS_MOVED
                . "," . $xprj_not_in_obj_type;
            if ($userinfo['role']) {
                $condition['obj_type'] = array(
                    'not in',
                    $condition_not_in_obj_type
                );//2015417OBJ_TYPE_USER_BONUS 不展示
            } else {
                $condition['obj_type'] = array(
                    'not in',
                    $condition_not_in_obj_type
                    . "," . PayAccountModel::OBJ_TYPE_PAY
                );//2015417OBJ_TYPE_USER_BONUS 不展示
            }

            $condition['in_or_out'] = 2;
        }
        $count_cond2['ticket_id'] = array('exp', ' is NULL');
        $count_cond2['cash_id'] = array('exp', ' is NULL');
        $count_cond2['obj_type'] = array(
            'not in',
            PayAccountModel::OBJ_TYPE_REPAY
            . "," . PayAccountModel::OBJ_TYPE_REPAY_FREEZE
            . "," . PayAccountModel::OBJ_TYPE_ROLLOUT
            . "," . PayAccountModel::OBJ_TYPE_RAISINGINCOME
            . "," . PayAccountModel::OBJ_TYPE_REWARD_CASHOUT
            . "," . PayAccountModel::OBJ_TYPE_REWARD_INVEST
            . "," . PayAccountModel::OBJ_TYPE_REWARDFREEMONEY
            . ",90001"
            . "," . PayAccountModel::OBJ_TYPE_TRANSFER
            . "," . PayAccountModel::OBJ_TYPE_EXPIRE_COUPON
            . "," . PayAccountModel::OBJ_TYPE_ADD_COUPON
            . "," . PayAccountModel::OBJ_TYPE_USE_COUPON
            . "," . PayAccountModel::OBJ_TYPE_REWARD_CBACK
            . "," . PayAccountModel::OBJ_TYPE_REWARD_IBACK
            . "," . PayAccountModel::OBJ_PT_SERVICE_FEE
            . "," . PayAccountModel::OBJ_TYPE_USER_BONUS
            . "," . PayAccountModel::OBJ_TYPE_FINENCE_MANAGE_FEE
            . "," . PayAccountModel::OBJ_TYPE_GUARANTEE_FEE
            . "," . PayAccountModel::OBJ_TYPE_GUWEN_FEE
            . "," . PayAccountModel::OBJ_TYPE_ZC_ZHINAJIN
            . "," . PayAccountModel::OBJ_TYPE_ZC_PRI_INTEREST
            . "," . PayAccountModel::OBJ_TYPE_ZC_FAXI
            . "," . PayAccountModel::OBJ_TYPE_REPAY_SHOUXU
            . "," . PayAccountModel::OBJ_TYPE_ZS_MOVING
            . "," . PayAccountModel::OBJ_TYPE_ZS_MOVED

            . "," . PayAccountModel::OBJ_TYPE_ZS_RECHARGING
            . "," . PayAccountModel::OBJ_TYPE_ZS_REPAYING
            . "," . PayAccountModel::OBJ_TYPE_ZS_RRPAIED

            . "," . $xprj_not_in_obj_type
        );//2015417OBJ_TYPE_USER_BONUS 不展示
        $count_cond2['in_or_out'] = 2;

        if ($status == 3) {
// 			$condition['obj_type'] = PayAccountModel::OBJ_TYPE_REPAY;
            //$condition['obj_type'] = array('in', PayAccountModel::OBJ_TYPE_REPAY.",".PayAccountModel::OBJ_TYPE_REPAY_FREEZE.",".PayAccountModel::OBJ_TYPE_ROLLOUT.",".PayAccountModel::OBJ_TYPE_RAISINGINCOME);

            $map2['obj_type'] = PayAccountModel::OBJ_TYPE_REPAY_FREEZE;
            $map2['in_or_out'] = 2;
            $map['_complex'] = $map2;
            $map['obj_type'] = array(
                'in',
                PayAccountModel::OBJ_TYPE_REPAY
                . "," . PayAccountModel::OBJ_TYPE_ROLLOUT
                . "," . PayAccountModel::OBJ_TYPE_RAISINGINCOME
                . "," . PayAccountModel::OBJ_TYPE_XRPJ_SETTLEMENT
                . "," . PayAccountModel::OBJ_TYPE_ZS_RRPAIED
            );
            $map['_logic'] = 'or';
            $condition['_complex'] = $map;
        }
// 		$count_cond3['obj_type'] = PayAccountModel::OBJ_TYPE_REPAY;
        //$count_cond3['obj_type'] = array('in', PayAccountModel::OBJ_TYPE_REPAY.",".PayAccountModel::OBJ_TYPE_REPAY_FREEZE.",".PayAccountModel::OBJ_TYPE_ROLLOUT.",".PayAccountModel::OBJ_TYPE_RAISINGINCOME);
//        $count_cond3['obj_type'] = array('in', PayAccountModel::OBJ_TYPE_REPAY.",".PayAccountModel::OBJ_TYPE_ROLLOUT.",".PayAccountModel::OBJ_TYPE_RAISINGINCOME);//2015417 一些类型不展示
//        $count_cond3['_string'] = "(obj_type=".PayAccountModel::OBJ_TYPE_REPAY_FREEZE." and in_or_out=2)";
        $map2_cond3['obj_type'] = PayAccountModel::OBJ_TYPE_REPAY_FREEZE;
        $map2_cond3['in_or_out'] = 2;
        $map_cond3['_complex'] = $map2_cond3;
        $map_cond3['obj_type'] = array(
            'in',
            PayAccountModel::OBJ_TYPE_REPAY
            . "," . PayAccountModel::OBJ_TYPE_ROLLOUT
            . "," . PayAccountModel::OBJ_TYPE_RAISINGINCOME
            . "," . PayAccountModel::OBJ_TYPE_ZS_RRPAIED
        );
        $map_cond3['_logic'] = 'or';
        $count_cond3['_complex'] = $map_cond3;


        //临时加上 手机端不显示冻结回款这一项内容   Q4:PC端也不显示 //回款列表中不显示原回款冻结数据 在变现列表中显示 投资人不显示 融资人显示
//        if(visit_source() != 'pc'){
        $user_data = M("user")->find($uid);
        if ($user_data['is_invest_finance'] == 1 || visit_source() != 'pc') {
            if ($status == 3) {
                $condition['obj_type'] = array(
                    'in',
                    PayAccountModel::OBJ_TYPE_REPAY
                    . "," . PayAccountModel::OBJ_TYPE_ROLLOUT
                    . "," . PayAccountModel::OBJ_TYPE_RAISINGINCOME
                    . "," . PayAccountModel::OBJ_TYPE_XRPJ_SETTLEMENT
                    . "," . PayAccountModel::OBJ_TYPE_ZS_RRPAIED

                );
            }
            $count_cond3['obj_type'] = array(
                'in',
                PayAccountModel::OBJ_TYPE_REPAY
                . "," . PayAccountModel::OBJ_TYPE_ROLLOUT
                . "," . PayAccountModel::OBJ_TYPE_RAISINGINCOME
                . "," . PayAccountModel::OBJ_TYPE_ZS_RRPAIED
            );
        }
//        }

        if ($status == 4) {
            $condition['cash_id'] = array('exp', ' >0');
            $condition['obj_type'] = array('NOT IN', array(PayAccountModel::OBJ_TYPE_CASHOUTDONE));
        }
        $count_cond4['cash_id'] = array('exp', ' >0');
        $count_cond4['obj_type'] = array('NOT IN', array(PayAccountModel::OBJ_TYPE_CASHOUTDONE));

        if ($status == 5) {
            // $condition['obj_type'] = array('in',PayAccountModel::OBJ_TYPE_REWARD_CASHOUT.",".PayAccountModel::OBJ_TYPE_REWARD_INVEST.",".PayAccountModel::OBJ_TYPE_REWARDFREEMONEY.",90001"
            // 					.",".PayAccountModel::OBJ_TYPE_ADD_COUPON.",".PayAccountModel::OBJ_TYPE_EXPIRE_COUPON.",".PayAccountModel::OBJ_TYPE_USE_COUPON
            // 					.",".PayAccountModel::OBJ_TYPE_REWARD_CBACK.",".PayAccountModel::OBJ_TYPE_REWARD_IBACK);
            $map5['obj_type'] = array(
                'in',
                PayAccountModel::OBJ_TYPE_REWARD_CASHOUT
                . "," . PayAccountModel::OBJ_TYPE_REWARD_INVEST
                . "," . PayAccountModel::OBJ_TYPE_USE_COUPON
                . "," . PayAccountModel::OBJ_TYPE_REWARD_CBACK
                . "," . PayAccountModel::OBJ_TYPE_REWARD_IBACK
                . "," . PayAccountModel::OBJ_TYPE_USER_BONUS
                . "," . PayAccountModel::OBJ_TYPE_REWARD
                . "," . PayAccountModel::OBJ_TYPE_USER_CASH_BONUS
                . "," . PayAccountModel::OBJ_TYPE_USER_TYJ_CASH_BONUS
                . ',' . PayAccountModel::OBJ_TYPE_XRPJ_RATE_ADDON
                . ',' . PayAccountModel::OBJ_TYPE_XRPJ_ACTIVITY
                . ',' . PayAccountModel::OBJ_TYPE_REWARD_ZS
                . ',' . PayAccountModel::OBJ_TYPE_XPRJ_JIAXI_ADDON
            );//2015417 OBJ_TYPE_REPAY_FREEZE不展示,OBJ_TYPE_USER_BONUS 展示
            $map5['_logic'] = 'or';
            $map5['_string'] = 'obj_type = ' . PayAccountModel::OBJ_TYPE_GIVEXREWARD . ' and in_or_out =2';
            $condition['_complex'] = $map5;
        }
        // $count_cond5['obj_type'] = array('in',PayAccountModel::OBJ_TYPE_REWARD_CASHOUT.",".PayAccountModel::OBJ_TYPE_REWARD_INVEST.",".PayAccountModel::OBJ_TYPE_REWARDFREEMONEY.",90001"
        // 						.",".PayAccountModel::OBJ_TYPE_ADD_COUPON.",".PayAccountModel::OBJ_TYPE_EXPIRE_COUPON.",".PayAccountModel::OBJ_TYPE_USE_COUPON
        // 						.",".PayAccountModel::OBJ_TYPE_REWARD_CBACK.",".PayAccountModel::OBJ_TYPE_REWARD_IBACK);
        $count_cond5['obj_type'] = array(
            'in',
            PayAccountModel::OBJ_TYPE_REWARD_CASHOUT
            . "," . PayAccountModel::OBJ_TYPE_REWARD_INVEST
            . "," . PayAccountModel::OBJ_TYPE_USE_COUPON
            . "," . PayAccountModel::OBJ_TYPE_REWARD_CBACK
            . "," . PayAccountModel::OBJ_TYPE_REWARD_IBACK
            . "," . PayAccountModel::OBJ_TYPE_USER_BONUS
            . "," . PayAccountModel::OBJ_TYPE_REWARD
            . "," . PayAccountModel::OBJ_TYPE_USER_CASH_BONUS
            . "," . PayAccountModel::OBJ_TYPE_USER_TYJ_CASH_BONUS
            . "," . PayAccountModel::OBJ_TYPE_REWARD_ZS
        );//2015417 OBJ_TYPE_REPAY_FREEZE不展示,OBJ_TYPE_USER_BONUS 展示

        if ($status == 6) {
            //$condition['obj_type'] = PayAccountModel::OBJ_TYPE_TRANSFER;//原版
            $condition['_string'] = "(obj_type=" . PayAccountModel::OBJ_TYPE_PAY . " and in_or_out=1)  OR ( obj_type=" . PayAccountModel::OBJ_TYPE_SHOUXU_SDT . ")";// OR (obj_type=".PayAccountModel::OBJ_TYPE_REPAY_FREEZE." and in_or_out=2) ";//2015424处理变现和手续费
        }
        //$count_cond6['obj_type'] = PayAccountModel::OBJ_TYPE_TRANSFER;//原版
        $count_cond6['_string'] = "(obj_type=" . PayAccountModel::OBJ_TYPE_PAY . " and in_or_out=1)  OR ( obj_type=" . PayAccountModel::OBJ_TYPE_SHOUXU_SDT . ") ";//2015424处理变现和手续费

        if ($status == 7) {
            //$condition['obj_type'] = array('exp',' <> '.PayAccountModel::OBJ_TYPE_REPAY_FREEZE);//2015422回款冻结不展示
            $condition['to_free_money'] = array('exp', ' <> from_free_money');
        }
        //$count_cond7['obj_type'] = array('exp',' <> '.PayAccountModel::OBJ_TYPE_REPAY_FREEZE);//2015422回款冻结不展示
        $count_cond7['to_free_money'] = array('exp', ' <> from_free_money');

        if ($status == 8) {
            $condition['obj_type'] = PayAccountModel::OBJ_PT_SERVICE_FEE;
        }
        if ($status == 9) {
            $condition['obj_type'] = $condition9['obj_type'] = array('in', array(PayAccountModel::OBJ_TYPE_ZS_MOVING,PayAccountModel::OBJ_TYPE_ZS_MOVED));
        }
        $count_cond8['obj_type'] = PayAccountModel::OBJ_PT_SERVICE_FEE;

        //start 2015415 一些类型只能在全部去显示，其他地方不显示(如:投资，充值，回款等)
        if ($status != 0) {
            $str = ' obj_type NOT IN ('
                . implode(',', array(
                    PayAccountModel::OBJ_TYPE_TRANSFERMONEY,
                    PayAccountModel::OBJ_TYPE_REVISION,
                    //PayAccountModel::OBJ_TYPE_RAISINGINCOME,
                    PayAccountModel::OBJ_TYPE_TRANSFER,
                    PayAccountModel::OBJ_TYPE_SHOUXU,
                    PayAccountModel::OBJ_TYPE_REPAY_SHOUXU,
                    PayAccountModel::OBJ_TYPE_ALLOT,
                    PayAccountModel::OBJ_TYPE_CUTINREPAY,

                ))
                . ') ';
            if (isset($condition['_string'])) {
                $condition['_string'] .= ' AND' . $str;
            } else {
                $condition['_string'] = $str;
            }
        }
        //end



        if ($start_time || $end_time) {
            if ($start_time) {
                $start_time = strtotime($start_time);
                $condition['ctime'] = $start_where = array('gt', $start_time);
            }
            if ($end_time) {
                $end_time = strtotime($end_time) + 24 * 3600 - 1;
                $condition['ctime'] = $end_where = array('lt', $end_time);
            }
            if ($start_time && $end_time) {
                if ($end_time < $start_time) {
                    $error = "日期格式错误";
                } else {
                    $condition['ctime'] = array($start_where, $end_where);
                }
            }
        } else {
            if ($time_type == 'week') {
                $condition['ctime'] = array(array('gt', strtotime("-1 week", strtotime(date("Y-m-d")))), array('lt', strtotime("now")));
            }
            if ($time_type == 'month') {
                $condition['ctime'] = array(array('gt', strtotimeX("-1 month", strtotime(date("Y-m-d")))), array('lt', strtotime("now")));
            }
            if ($time_type == '6month') {
                $condition['ctime'] = array(array('gt', strtotimeX("-6 month", strtotime(date("Y-m-d")))), array('lt', strtotime("now")));
            }
        }
        $result = array();
        if (!isset($error) || !$error) {
            //amount-余额  freeze_money-冻结 mod_money-收入/支出金额 in_or_out-1是收入2是支出 type-类型 ctime-时间 prj_series-产品系列 prj_name-项目名称 prj_no-项目no
            $list = service("Payment/PayAccount")->getRecordByUidNew($condition, $parameter);
            $arr = array();
            foreach ($list['data'] as $item) {
                if ($item['ticket_id'] > 0 && !$item['cash_id']) {
                    $item['status'] = '1';
                }
                if (!$item['ticket_id'] && !$item['cash_id'] && $item['obj_type'] != PayAccountModel::OBJ_TYPE_REPAY
                    && $item['obj_type'] != PayAccountModel::OBJ_TYPE_RAISINGINCOME
                    && $item['obj_type'] != PayAccountModel::OBJ_TYPE_REPAY_FREEZE
                    && $item['obj_type'] != PayAccountModel::OBJ_TYPE_ROLLOUT
                    && $item['obj_type'] != PayAccountModel::OBJ_TYPE_REWARD_CASHOUT && $item['obj_type'] != PayAccountModel::OBJ_TYPE_REWARD_INVEST
                    && $item['obj_type'] != PayAccountModel::OBJ_TYPE_TRANSFER && $item['obj_type'] != PayAccountModel::OBJ_TYPE_ADD_COUPON
                    && $item['obj_type'] != PayAccountModel::OBJ_TYPE_USE_COUPON && $item['obj_type'] != PayAccountModel::OBJ_TYPE_EXPIRE_COUPON
                    && $item['obj_type'] != PayAccountModel::OBJ_TYPE_REWARD_CBACK && $item['obj_type'] != PayAccountModel::OBJ_TYPE_REWARD_IBACK
                    && $item['obj_type'] != PayAccountModel::OBJ_TYPE_ZS_MOVING && $item['obj_type'] != PayAccountModel::OBJ_TYPE_ZS_MOVED
                ) {
                    $item['status'] = '2';
                }
                if ($item['obj_type'] == PayAccountModel::OBJ_TYPE_REPAY_FREEZE
                    || $item['obj_type'] == PayAccountModel::OBJ_TYPE_ROLLOUT
                    || $item['obj_type'] == PayAccountModel::OBJ_TYPE_RAISINGINCOME
                ) {
                    $item['status'] = '3';
                }
                if ($item['cash_id'] > 0) {
                    $item['status'] = '4';
                }
                if ($item['obj_type'] == PayAccountModel::OBJ_TYPE_REWARD_CASHOUT || $item['obj_type'] == PayAccountModel::OBJ_TYPE_REWARD_INVEST
                    || $item['obj_type'] == PayAccountModel::OBJ_TYPE_ADD_COUPON || $item['obj_type'] == PayAccountModel::OBJ_TYPE_EXPIRE_COUPON
                    || $item['obj_type'] == PayAccountModel::OBJ_TYPE_REWARD_CBACK || $item['obj_type'] == PayAccountModel::OBJ_TYPE_REWARD_IBACK
                ) {
                    $item['status'] = '5';
                }
                if ($item['obj_type'] == PayAccountModel::OBJ_TYPE_TRANSFER) {
                    $item['status'] = '6';
                }
                if ($item['to_free_money'] != $item['from_free_money']) {
                    $item['status'] = '7';
                }

                if ($item['obj_type'] == PayAccountModel::OBJ_TYPE_ZS_MOVING || $item['obj_type'] == PayAccountModel::OBJ_TYPE_ZS_MOVED) {
                    $item['status'] = '9';
                }
                $arr[] = $item;
            }
            $list['data'] = $arr;
            $result['list'] = $list;
        } else {
            $result['error'] = $error;
        }
        $totalrecharge = D('Payment/AccountRecord')->field("COUNT(1) AS CNT")->where($count_cond1)->find();
        $result['totalrecharge'] = $totalrecharge['CNT'];
        $totalTouzhi = D('Payment/AccountRecord')->field("COUNT(1) AS CNT")->where($count_cond2)->find();
        $result['totalTouzhi'] = $totalTouzhi['CNT'];
        $totalHuikuan = D('Payment/AccountRecord')->field("COUNT(1) AS CNT")->where($count_cond3)->find();
        $result['totalHuikuan'] = $totalHuikuan['CNT'];
        $totalTixian = D('Payment/AccountRecord')->field("COUNT(1) AS CNT")->where($count_cond4)->find();
        $result['totalTixian'] = $totalTixian['CNT'];
        $totalJiangli = D('Payment/AccountRecord')->field("COUNT(1) AS CNT")->where($count_cond5)->find();
        $result['totalJiangli'] = $totalJiangli['CNT'];
        $totalZhuangrang = D('Payment/AccountRecord')->field("COUNT(1) AS CNT")->where($count_cond6)->find();
        $result['totalZhuangrang'] = $totalZhuangrang['CNT'];
        $totalFreeMoney = D('Payment/AccountRecord')->field("COUNT(1) AS CNT")->where($count_cond7)->find();
        $result['totalFreeMoney'] = $totalFreeMoney['CNT'];

        $totalPtFee = D('Payment/AccountRecord')->field("COUNT(1) AS CNT")->where($count_cond8)->find();
        $result['totalPtFee'] = $totalPtFee['CNT'];
//        $totalMove = D('Payment/AccountRecord')->field("COUNT(1) AS CNT")->where($count_cond9)->find();
//        $result['totalMove'] = $totalMove['CNT'];

        $result['status'] = $status;
        return $result;
    }

    //201555取出已使用红包,速兑通手续费的金额
    public function getRewardSum($uid)
    {
        if (!$uid) {
            return false;
        }
        $record_sum = M('user_account_record_sum')->where(array("uid" => $uid))->find();
        return $record_sum;
    }

    // //201555取出用户的手续费只计算速兑通(12002)手续费用
    //    public function getFeeSum($uid){
    //    	D("Payment/PayAccount");
    //        //速兑通(12002)手续费用
    //        $item['uid'] =$uid;
    //        $item['in_or_out'] =2;
    //    	$item['obj_type']=PayAccountModel::OBJ_TYPE_SHOUXU_SDT;
    //    	$feeout_sum=M('user_account_record')->field('change_amount','in_or_out','uid','obj_type')->where($item)->sum('change_amount');
    //    	return  $feeout_sum;
    //    }


}

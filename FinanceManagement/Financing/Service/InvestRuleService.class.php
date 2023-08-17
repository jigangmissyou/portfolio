<?php
/**
 * User: 001181
 * Date: 2015-03-24 16:39
 * Author:001181
 * Description: 红包的投资规则的基类接口 
 */
interface InvestRuleService {
    public function getAmount();
    public function getAmountForInvest($uid, $invest_amount, $prj_id, $project);
    public function useBonusForInvest($uid, $invest_amount, $prj_id, &$project);
}
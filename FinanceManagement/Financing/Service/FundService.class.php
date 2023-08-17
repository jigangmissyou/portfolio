<?php
/**
 * // +----------------------------------------------------------------------
// | UPG
// +----------------------------------------------------------------------
// | Copyright (c) 2012 http://upg.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: kaihui.wang <wangkaihui@upg.cn>
// +----------------------------------------------------------------------
// 基金理财
 */
import_app('Financing.Service.ProjectService');
import_app("Financing.Model.PrjModel");
class FundService extends ProjectService{

    /**
     * 开标
     */
    function buy($money,$uid,$prjId,$params,$ismobile=0, $is_mo=0, $mo_order_no=''){
        if(!is_numeric($money)){
            MyError::add("投资金额必须是数字!");
            return false;
        }
        $prjId = (int) $prjId;
        $uid = (int) $uid;
        $model = M("prj");
        $info = $model->where(array("id"=>$prjId))->find();
        if(!$info){
            MyError::add("异常,项目不存在!");
            return false;
        }

        try{
// 	         $bankId = service("Payment/PayAccount")->getTenantAccountId($info['dept_id']);
        	 $bankId = service("Financing/Project")->getTenantIdByPrj($info);
             $result = parent::buy($money, $uid, $prjId,"购买稳益保产品",$bankId,$params,$ismobile, $is_mo,$mo_order_no);
//              if($result){
//                  D('Financing/PrjPartner')->countInc($prjId);
//              }
             return $result;
        }catch (Exception $e){
            MyError::add($e->getMessage());
            return false;
        }
    }

}
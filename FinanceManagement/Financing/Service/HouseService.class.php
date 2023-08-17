<?php 
/**
 * 抵押
 */
import_app('Financing.Service.ProjectService');
import_app("Financing.Model.PrjModel");
class HouseService extends ProjectService{
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
       		$bankId = service("Financing/Project")->getTenantIdByPrj($info);
//            $bankId = service("Payment/PayAccount")->getTenantAccountId($info['dept_id']);
           return parent::buy($money, $uid, $prjId,"购买抵益融产品",$bankId,$params,$ismobile, $is_mo,$mo_order_no);
       }catch (Exception $e){
       	   MyError::add($e->getMessage());
       	   return false;
       }
    }

}
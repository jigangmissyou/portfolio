<?php

/**
 * 账套信息管理
 */

include_once '/inc/auth.inc.php';
include_once '/inc/utility_all.php';
include_once '/inc/utility_org.php';

include_once '/general/erp4/lib/dbTool/mssqlDB.class.php';
include_once '/general/erp4/lib/dbTool/mysqlDB2.class.php';

include_once '/general/erp4/service/field.ser.php';
include_once '/general/erp4/service/priv.ser.php';
include_once '/general/erp4/service/customer.ser.php';
include_once '/general/erp4/service/provebill.ser.php';
include_once '/general/erp4/service/order.ser.php';
include_once '/general/erp4/service/common.ser.php';
include_once '/general/erp4/service/flows.ser.php';
include_once '/general/erp4/lib/string.class.php';

class billDeliver{
                
    private $db;
    private $msdb;
    private $tabName='dev_provebill_deliver';

    private $fieldSer;
    private $privSer;
    
    private $flows;
    private $provebill;
    private $customerSer;
    private $commSer;

    public function __construct(){
        $this->db = new mysqlDB();
        // $this->db = new mysqlDB(1);

        //$this->msdb = new mssqlDB();
        //$this->msdb = new mssqlDB('D:/MYOA/logs/customer.log');

        $this->fieldSer = new fieldSer();
        $this->privSer = new privser();
        $this->provebill=new provebill();
        $this->orderSer = new orderSer();        
        $this->customerSer = new customerSer();
        $this->flows=new flowsSer();
        $this->commSer = new commonSer();
        //$this->flows = array("orderChange"=>"订单变更");//流程名称定义
        //$this->steps=array("变更修改","变更审核","变更导入","导入完成");
    }

    /**
     * 
     * @param type $pars
     * @param type $page
     * @param type $limit
     */
    public function getPageList($params){
        $where=" (1=1) ";
        $filter="a.*,b.id as flow_id,b.flowType,b.status,IF(ISNULL(c.backPrcs),0,c.backPrcs) as backPrcs,c.comment,c.id as flow_step_id,c.flowPrcs,c.prcsFlag,"
                . "if(a.expiry_date='0000-00-00','',a.expiry_date) as expiry_date,"
                . "if(a.deliver_date='0000-00-00','',a.deliver_date) as deliver_date,"
                . "if(a.sales_bill_date='0000-00-00','',a.sales_bill_date) as sales_bill_date ";   
        
         //(SELECT GROUP_CONCAT(sub4.bill_num) FROM dev_provebill AS sub4 WHERE  FIND_IN_SET(sub4.id,a.bill_ids)) AS bill_nums
        $order=" order by a.id DESC ";
        $table=" dev_provebill_deliver as a "
                
                . " left join dev_flows as b on a.id=b.flowCaseId and b.flowType in('发货流程') "
                . " left join dev_flows_step as c on c.id=(SELECT sub3.id FROM dev_flows_step sub3 WHERE  sub3.flowId = b.id AND sub3.flowPrcs = b.status ORDER BY sub3.id DESC LIMIT 1)"
                . " left join dev_runpriv as d on d.type=b.flowType and d.name=b.status " ;
        
        
        
        //$where.=" and b.id is not null "; 
        
        //按销货单号筛选
        if(!empty($params["sales_num"])){
            $where.=" and a.sales_num = '{$params["sales_num"]}' ";
        }
        
        //按流程类型筛选
        if(!empty($params["flowType"])){
            $where.=" and b.flowType = '{$params["flowType"]}' ";
        }
        
        //按流程类型筛选
        if(!empty($params["status"])){
            $where.=" and b.status = '{$params["status"]}' ";
        }
        
        if(!empty($params["sales_num_like"])){
            $where.=" and a.sales_num like '%{$params["sales_num_like"]}%' ";
        }
        
        if(!empty($params["order_num_like"])){
            $where.=" and a.order_num like '%{$params["order_num_like"]}%' ";
        }
        
        if(!empty($params["invoice_num_like"])){
            $where.=" and a.invoice_nums like '%{$params["invoice_num_like"]}%' ";
        }

        if(!empty($params["customer_short_like"])){
            $where.=" and a.customer_short like '%{$params["customer_short_like"]}%' ";
        }
        
        //按实际装柜日期筛选
        if(!empty($params["real_cabinet_time"])){
            $where.=" and a.real_cabinet_time = '{$params["real_cabinet_time"]}' ";
        }
        
        
        //按发货仓库筛选
        if(!empty($params["warehouse_name"])){
            $where.=" and a.warehouse_name = '{$params["warehouse_name"]}' ";
        }

        //制单人
        if(!empty($params["creator_id"]) && $params["creator_id"] != '1' && $params["showType"] == '50'){
            $where.=" and a.creator_id = '{$params["creator_id"]}' ";
        }
      
        if(!empty($params["id"])){
            $where.=" and a.id ={$params['id']} ";
        }
        
        if(!empty($params["load_type"])){      
            $where.=" and a.load_type='{$params["load_type"]}' ";
        }
        
        if(!empty($params["is_ship"])){      
            $where.=" and a.sales_num!='' ";
        }
        
        if(!empty($params["u8Code_like"])){
            $where.=" and exists(select 1 from dev_provebill_deliver_item as sub14 inner join dev_orderlist as sub15 on sub14.order_list_id=sub15.id where sub15.u8Code like '%{$params["u8Code_like"]}%' and sub14.deliver_id=a.id ) ";
        }
        
        
        if(isset($params["is_invoice"])){
            $where.=" and a.is_invoice='{$params["is_invoice"]}' ";
        }
        
        if(isset($params["is_auto"])){
            $where.=" and a.is_auto = 0 ";
        }

        if(isset($params["rd_complete"])){
            $where.=" and a.rd_complete = 0 ";
        }
        
        /*if(!empty($params["prcsFlag"])){
            $params["prcsFlag"]= is_array($params["prcsFlag"])?$params["prcsFlag"]:explode(",", $params["prcsFlag"]);
            $params["prcsFlag"]=implode(',',array_map(function ($item){return sprintf("'%s'", $item);}, $params["prcsFlag"]));
            $where.=" and c.prcsFlag in({$params["prcsFlag"]}) ";
        }*/
        
        
        $uids1=$this->commSer->getUserGroupIds(array("name"=>"发货通知（散货江西）"));
        $uids2=$this->commSer->getUserGroupIds(array("name"=>"发货通知（散货上海）"));
        $uids1= array_filter(explode(",", $uids1));
        $uids2= array_filter(explode(",", $uids2));
        
        if($_SESSION['LOGIN_USER_PRIV']!=1){
            /*$where.=" and a.load_type='散货' ";*/
            /*if(in_array($_SESSION["LOGIN_UID"], $uids1)){
                $where.=" and a.deliver_store='江西仓库' ";
            }else if(in_array($_SESSION["LOGIN_UID"], $uids2)){
                $where.=" and a.deliver_store='上海仓库' ";
            }*/
        }
        
        
  
        if(!empty($params["step"])){
            switch ($params["step"]){
                case "总监审核中":
                    $where.=" and b.status = '总监审核' ";
                    break;
                case "财务审核中":
                    $where.=" and b.status = '财务审核' ";
                    break;
                case "发货确认中":
                    $where.=" and b.status = '发货确认' and c.prcsFlag!='办理完毕' ";
                    break;
                case "已完成":
                    $where.=" and b.status = '发货确认' and c.prcsFlag='办理完毕' ";
                    break;
                case "未完成":
                    $where.=" and b.flowType = '发货流程' and c.prcsFlag!='办理完毕' ";
                    break;
            }
        }
        
        
        
        
        $page=!empty($params['page'])?$params['page']:1;
        $limit=!empty($params['limit'])?$params['limit']:10;
        
        
        //$sql=" select u.* from (select {$filter} from {$table} where {$where} {$order}) as u where (1=1) ";
        /*$where=" load_type!='' ";
        $filter=" * ";*/
        
        if($limit==1){
            $dataList=$this->db->get_one($table,$filter,$where);
        }else{
            
            //$dataList=$this->db->select_page_sql($sql,$page,$limit,1);
            
            $dataList=$this->db->select_page_raw($table,$filter,$where,$order,$page,$limit);
            if(!empty($dataList["data"])){
                foreach ($dataList["data"] as $key=>$val){
                    $dataList["data"][$key]["create_user"]= GetUserNameByUid($val['creator_id']);
                    
                    $log= $this->db->get_one("dev_update_logs", "id", " type_flag='deliver' and case_id={$val['id']} ");
                    if(!empty($log)) $dataList["data"][$key]["has_log"]=1;
                    
                    $u8Deliver=$this->customerSer->getDeliverData(array("sales_num"=>$val["sales_num"]));
                    $dataList["data"][$key]["expiry_date"]=$dataList["data"][$key]["approver"]="";
                    if(!empty($u8Deliver)&&$u8Deliver!=-1){
                        $dataList["data"][$key]["expiry_date"]=!empty($u8Deliver["dgatheringdate"])?$u8Deliver["dgatheringdate"]->format('Y-m-d'):"";//到期日
                        $dataList["data"][$key]["approver"]=$u8Deliver["cverifier"];//审批人
                    }
                    
                    $orderItemIds=$this->db->get_one("dev_provebill_deliver_item as a inner join dev_orderlist as b on a.order_list_id=b.id ", " GROUP_CONCAT(a.order_list_id) as order_list_ids,GROUP_CONCAT(b.id) as u8Code,GROUP_CONCAT(b.u8Code) as u8Codes ", " deliver_id={$val["id"]} ");
                    $dataList["data"][$key]["order_list_ids"]=$orderItemIds["order_list_ids"];
                    $dataList["data"][$key]["u8Codes"]=$orderItemIds["u8Codes"];                    
                    
                }
            }
        }
        
        

        return $dataList;
    }


    /**
     * 更新单证数据
     * @param type $params
     * @return type
     */
    public function updateInfo($params){ 
        $data=$params["data"];
        $id=!empty($data["id"])?$data["id"]:array(); 
        $products=!empty($data["products"])?$data["products"]:[];
        unset($data['products']);
        unset($data["id"]);
        unset($data['layTableCheckbox']);
        $this->db->query("BEGIN");//事务开始
        try {
            if(!empty($data["bill_ids"])){
                $cabinet=$this->db->get_one("dev_provebill_cabinet", "*", " bill_id={$data["bill_ids"]} ");
                $data["load_type"]=!empty($cabinet["load_type"])?$cabinet["load_type"]:"";
            }
           if(empty($id)){
                $data['creator_id']=$_SESSION['LOGIN_UID'];
                $data['create_time']= date('Y-m-d H:i:s');
                $id=$this->db->insert($this->tabName, $data);
            }else{
                $this->db->update($this->tabName, $data," id={$id} ");//更新单证信息
            }
            

            $this->db->delete("dev_provebill_deliver_item", "deliver_id={$id}");
            if(!empty($products)){
                foreach ($products as $key=>$val){
                    $this->db->insert("dev_provebill_deliver_item", array(
                        "deliver_id"=>$id,
                        "order_id"=>$val["orderId"],
                        "order_list_id"=>$val["id"],
                        "deliver_num"=>$val["deliver_num"],
                        "deliver_box"=>$val["deliver_box"], 
                        "warehouse_code"=>$val["warehouse_code"],
                        "warehouse_name"=>$val["warehouse_name"],
                        "warehouse_person"=>$val["warehouse_person"],
                        "unit"=>$val["poUnit"],
                        "more"=>$val["more"],
                        "order_num"=>$val["order_num_show"]
                    ));
                }
            }
            
            if(!empty($data["bill_ids"])){
                $this->db->update("dev_provebill", array("deliver_product_finish"=>1,"deliver_id"=>$id)," id in ({$data["bill_ids"]}) ");
                $bill_ids= explode(",", $data["bill_ids"]);

                $provebill_ids=array();
                //检索未发货产品以及订舱单号
                foreach ($bill_ids as $key=>$val){
                    $bill_product=$this->provebill->getProductList(array("provebill_ids"=>$val)); 
                    if(!empty($bill_product["data"])){
                        foreach($bill_product["data"] as $k=>$v){  
                            $deliverInfo=$this->db->get_one("dev_provebill_deliver_item", "sum(deliver_box) as deliver_box ", " order_list_id={$v["id"]} ");                    
                            $deliverNum=!empty($deliverInfo["deliver_box"])?$deliverInfo["deliver_box"]:0;
                            $allDeliverNum=$v["deliver_box_max"]-$deliverNum;//计算剩余最大发货数量
                            if(empty($products["key_".$v["id"]])||!empty($allDeliverNum)){
                                if(!in_array($val, $provebill_ids)) $provebill_ids[]=$val;
                            }
                        }
                    }
                }

                if(!empty($provebill_ids)){
                    $provebillIds= implode(",", $provebill_ids);
                    $this->db->update("dev_provebill", array("deliver_product_finish"=>0)," id in ({$provebillIds}) ");
                }
                
                //$bill_product=
            }
 
            $this->db->query("COMMIT");//提交事务

        } catch (Exception $e) {
            $this->db->query("ROLLBACK");//事务回滚
            return array("status"=>500,"msg"=>$e->getMessage(),"data"=>array());   
        }
        return array("status"=>200,"msg"=>"更新成功！","data"=>$id);
    }


    /**
     * 获取详情
     * @param type $params
     * @return type
     */
    public function getInfo($params){
        $where="(1=1)";
        if(!empty($params['id'])) $where.=" and id={$params['id']} ";
        if(!empty($params['sales_num'])) $where.=" and sales_num='{$params["sales_num"]}' ";
        if(!empty($params['deliver_num'])) $where.=" and deliver_num='{$params["deliver_num"]}' ";
        $dataInfo= $this->db->get_one($this->tabName,"*",$where);

        if(!empty($dataInfo)){
            $dataInfo["sales_bill_date"]=$dataInfo["sales_bill_date"]!="0000-00-00"?$dataInfo["sales_bill_date"]:"";
            $dataInfo["expiry_date"]=$dataInfo["expiry_date"]!="0000-00-00"?$dataInfo["expiry_date"]:"";
            $dataInfo["deliver_date"]=$dataInfo["deliver_date"]!="0000-00-00"?$dataInfo["deliver_date"]:"";
            $dataInfo["real_cabinet_time"]=$dataInfo["real_cabinet_time"]!="0000-00-00"?$dataInfo["real_cabinet_time"]:"";
            
            $dataInfo["creator_name"]=GetUserNameByUid($dataInfo["creator_id"]);

            $provebill_cabinet=$this->db->get_one("dev_provebill_cabinet","*"," bill_id in ({$dataInfo['bill_ids']}) ");
            //print_r($provebill_cabinet);exit;
            $dataInfo["entry_address"]=!empty($provebill_cabinet["entry_address"])?$provebill_cabinet["entry_address"]:"";
            $dataInfo["entry_time"]=!empty($provebill_cabinet["entry_time"])?$provebill_cabinet["entry_time"]:"";
            
            if(empty($dataInfo["account_type"])){
                $provebill=$this->db->get_one("dev_provebill","*"," id in ({$dataInfo['bill_ids']}) ");
                $dataInfo["account_type"]=!empty($provebill["account_type"])?$provebill["account_type"]:"";

            }
        
            
            //获取账套号
            $zhangTao=$this->db->get_one("dev_config","*","zhangTao='{$dataInfo["account_type"]}'");
            $dataInfo["cAccId"]=!empty($zhangTao["db3"])?$zhangTao["db3"]:"";
            //sale_type销售类型
            $saleInfo=$this->db->get_one("dev_provebill_deliver_item as a inner join dev_ordernum as b on b.status=a.order_id ","b.ddLeiXing,b.cCusPPerson,b.cCusPPersonText","a.deliver_id={$dataInfo["id"]} ");
            $dataInfo["sale_type"]=!empty($saleInfo["ddLeiXing"])?$saleInfo["ddLeiXing"]:"";
            $dataInfo["cCusPPersonText"]=!empty($saleInfo["cCusPPersonText"])?$saleInfo["cCusPPersonText"]:"";//业务员
        }
        
       
        
    
        $u8Deliver=$this->customerSer->getDeliverData(array("sales_num"=>$dataInfo["sales_num"]));
        $dataInfo["expiry_date"]=$dataInfo["approver"]="";
        if(!empty($u8Deliver)&&$u8Deliver!=-1){
            $dataInfo["expiry_date"]=!empty($u8Deliver["dgatheringdate"])?$u8Deliver["dgatheringdate"]->format('Y-m-d'):"";//到期日
            $dataInfo["approver"]=$u8Deliver["cverifier"];//审批人
        }
        
        
        
      
        return $dataInfo;
    }
    
    public function getBillNum(){
        $deliver_num =$billInfo= "";//获取订单号
        $maxLoop=100;//最大循环数
        $loopNum=0; //循环数
        while (!empty($billInfo["deliver_num"])||empty($deliver_num)) {
            if($loopNum>100) return false; //超过最大循环数返回空值
            $deliver_num = "FH".date("Y-mdHis").rand(10,100);//获取订单号   
            $billInfo=$this->getInfo(array("deliver_num"=>$deliver_num));
            $loopNum++;
        }
        return $deliver_num; 
    }
    
   
    public function del($params){
        if(empty($params["id"])){
           return array("status"=>500,"msg"=>"参数错误！","data"=>array()); 
        }
        
        $deliverInfo=$this->db->get_one($this->tabName, "*", " id={$params["id"]} ");
        
        $deliverItem=$this->db->get_one("dev_provebill_deliver_item", "*", " deliver_id={$deliverInfo["id"]} ");
        
        $ordHeader = $this->db->get_one("dev_order as a inner join dev_ordernum as b on b.status=a.id ",'a.*,b.ddLeiXing,b.orderType as num_orderType,b.desc as b_desc'," a.id={$deliverItem["order_id"]}");
       
        $ordHeader["typeInfo"]="内销";
        if(!empty($ordHeader['ddLeiXing'])&&in_array($ordHeader['ddLeiXing'], ["YW"])){
            $ordHeader["typeInfo"]="外销";
        }    
        
        $u8Info=$this->db->get_one("dev_config", "*"," zhangTao='{$deliverInfo["account_type"]}' ");
        

        //u8删除   
        if(!empty($deliverInfo["sales_num"])){
            $this->msdb = new mssqlDB($u8Info['ip'],array('Database'=>'MiddleBase','UID'=>'sa','PWD'=>$u8Info['pass']));        
            if(empty($this->msdb))  return array("status"=>500,"msg"=>"U8数据库连接失败！","data"=>array());

            $tableMap=array("SH"=>"Tbl_YG_ShRdRecord","JX"=>"Tbl_YG_JxRdRecord","JN"=>"Tbl_YG_JnOppRecord");
            if(!empty($tableMap[$u8Info["zhangTao"]])){
                $ret=$this->msdb->select_raw($tableMap[$u8Info["zhangTao"]], "*"," U8Code = '{$deliverInfo["sales_num"]}' ");
                if(!empty($ret)&&is_array($ret)) return array("status"=>500,"msg"=>"仓库已扫码，无法删除！","data"=>array());
            }

            $mtConfig=$this->db->get_one(" dev_config ", "*", " zhangTao='MT' ");
            $msdb=new mssqlDB($mtConfig['ip'],array('Database'=>$mtConfig['db1'],'UID'=>'sa','PWD'=>$mtConfig['pass']));              
            $ret = $msdb->select_raw("Tbl_YG_OutDetails", "id"," cAccId = '{$u8Info["db3"]}' and cDLCode='{$deliverInfo["sales_num"]}' ");
            if(!empty($ret)&&is_array($ret)) return array("status"=>500,"msg"=>"该发货单已经出货，无法删除！","data"=>array());
        }
        
        
        
        if(!empty($deliverInfo)){
            $ret= $this->db->delete($this->tabName," id={$params["id"]} "); //删除流程
            $this->db->delete("dev_provebill_deliver_item"," deliver_id={$params["id"]} "); //删除发货产品
            $this->db->delete("dev_flows"," flowType='发货流程'  and flowCaseId={$params["id"]} "); //删除流程信息
            $this->db->delete("dev_flows_step"," flowType='发货流程' and flowCaseId={$params["id"]} "); //删除流程信息
            $this->db->update("dev_flows_step",array("prcsFlag"=>"未接收")," flowType='订舱通知单' and flowCaseId in ({$deliverInfo['bill_ids']}) and flowPrcs='发货' "); //删除流程信息
            if(!empty($deliverInfo['bill_ids'])){
                $this->db->update("dev_provebill", array("deliver_product_finish"=>0,"deliver_id"=>"","u8_deliver"=>0)," id in ({$deliverInfo['bill_ids']}) ");
            }
            
               
            if(!empty($deliverInfo["sales_num"])){
                $success=true;
                $tempArr=array(
                    "cAccId"=>$u8Info["db3"],//账套号
                    "cVouchType"=>$ordHeader["typeInfo"],//单据类型(内销、外销)
                    "cU8Code"=>$deliverInfo["sales_num"],//U8单据号
                    "cType"=>"整单",//删除类型（整单/单行）
                );

                $temp = $this->msdb->insert('Tbl_YG_OAFHDelete',$tempArr);
                if($temp!=1) $success=false;
            
                if($success){
                    sqlsrv_commit($this->msdb->conn); //提交事务
                }else{
                    sqlsrv_rollback($this->msdb->conn); // 事务回滚
                    return array("status"=>500,"msg"=>"U8数据更新失败！","data"=>array()); 
                }
            }
            //$this->db->delete("dev_provebill_deliver_item"," deliver_id={$params["id"]} "); //删除发货产品
 
        }
        
        
        return array("status"=>200,"msg"=>"删除成功！","data"=>$ret);
    }


    /**
     * 删除发货明细
     * @param type $params
     */
    public function delDeliverItem($params){
        if(empty($params["id"])){
           return array("status"=>500,"msg"=>"参数错误！","data"=>array()); 
        }
        $deliverItem=$this->db->get_one("dev_provebill_deliver_item", "*"," id={$params["id"]} ");
        if(empty($deliverItem)) return array("status"=>500,"msg"=>"参数错误！","data"=>array()); 
        
        $billInfo=$this->db->get_one("dev_provebill as a left join dev_flows as b on b.flowCaseId=a.id and b.flowType='订舱通知单' ", "a.id,b.id as flow_id"," a.deliver_id={$deliverItem["deliver_id"]} ");
        
        
   

        $deliverInfo=$this->db->get_one("dev_provebill_deliver", "*", " id={$deliverItem["deliver_id"]} ");
        
        $ordHeader = $this->db->get_one("dev_order as a inner join dev_ordernum as b on b.status=a.id ",'a.*,b.ddLeiXing,b.orderType as num_orderType,b.desc as b_desc'," a.id={$deliverItem["order_id"]}");
       
        $ordHeader["typeInfo"]="内销";
        if(!empty($ordHeader['ddLeiXing'])&&in_array($ordHeader['ddLeiXing'], ["YW"])){
            $ordHeader["typeInfo"]="外销";
        }    
        
        $u8Info=$this->db->get_one("dev_config", "*"," zhangTao='{$deliverInfo["account_type"]}' ");
        
        //u8删除   
        $this->msdb = new mssqlDB($u8Info['ip'],array('Database'=>'MiddleBase','UID'=>'sa','PWD'=>$u8Info['pass']));        
        if(empty($this->msdb))  return array("status"=>500,"msg"=>"U8数据库连接失败！","data"=>array());
        
        $tableMap=array("SH"=>"Tbl_YG_ShRdRecord","JX"=>"Tbl_YG_JxRdRecord","JN"=>"Tbl_YG_JnOppRecord");
        if(!empty($tableMap[$u8Info["zhangTao"]])){
            $ret=$this->msdb->select_raw($tableMap[$u8Info["zhangTao"]], "*"," U8Code = '{$deliverInfo["sales_num"]}' ");
            if(!empty($ret)&&is_array($ret)) return array("status"=>500,"msg"=>"仓库已扫码，无法删除！","data"=>array());
        }
        
        
        $mtConfig=$this->db->get_one(" dev_config ", "*", " zhangTao='MT' ");
        $msdb=new mssqlDB($mtConfig['ip'],array('Database'=>$mtConfig['db1'],'UID'=>'sa','PWD'=>$mtConfig['pass']));              
        // $ret = $msdb->select_raw("Tbl_YG_OutDetails", "id"," cAccId = '{$u8Info["db3"]}' and cDLCode='{$deliverInfo["sales_num"]}' ");
        // if(!empty($ret)&&is_array($ret)) return array("status"=>500,"msg"=>"该发货单已出货，无法删除！","data"=>array());
        
        
        $this->db->delete("dev_provebill_deliver_item"," id={$params["id"]} "); //删除发货产品
        
        if(!empty($billInfo)){
            $this->db->delete("dev_provebill_product"," provebill_id={$billInfo["id"]} and order_list_id={$deliverItem["order_list_id"]} "); //删除发货产品
            $userGroup=$this->db->get_one("dev_user_group","*","name='订单变更通知单证人员'" );
            //$msgIds=$this->provebill->bill_msg_id(array("bill_id"=>$billInfo["id"]));
            $msgIds=$userGroup["user_ids"];
            $this->commSer->sendMsg(array("uids"=>$msgIds,"msg"=>"发货单有产品被删除请重新导出单据！","path"=>"erp4/view/provebill/index/add.php?/#/id={$billInfo["id"]}/opt=order_change/flow_id={$billInfo["flow_id"]}/showType=55"));
        }
        
        
        $success=true;
        $tempArr=array(
            "cAccId"=>$u8Info["db3"],//账套号
            "cVouchType"=>$ordHeader["typeInfo"],//单据类型(内销、外销)
            "cU8Code"=>$deliverInfo["sales_num"],//U8单据号
            "cType"=>"单行",//删除类型（整单/单行）
            "OAAutoId"=>$deliverItem["order_list_id"]
        );

        $temp = $this->msdb->insert('Tbl_YG_OAFHDelete',$tempArr);
        if($temp!=1) $success=false;

        if($success){
            sqlsrv_commit($this->msdb->conn); //提交事务
        }else{
            sqlsrv_rollback($this->msdb->conn); // 事务回滚
            return array("status"=>500,"msg"=>"U8数据更新失败！","data"=>array()); 
        }
            

        return array("status"=>200,"msg"=>"删除成功！","data"=>array());
        
    }


    //获取发货单账套类型
    public function getDeliverAccounts($params){
        $accountArray=array();
        if(empty($params["deliver_id"])) return $accountArray; 
        $deliverList = $this->db->select_raw("dev_provebill_deliver_item", "*", " deliver_id={$params["deliver_id"]} ");
       
        if(!empty($deliverList)){
            foreach ($deliverList as $key=>$val){
                //$val[""] order_num
                $account=substr($val["order_num"],2,2);
                if(!empty($account)&&!in_array($account, $accountArray)){
                    $accountArray[]=$account;
                }      
            }
        }
        
        return $accountArray;
        
    }




    /**
     * 获取仓库列表
     * @return type
     */
    public function warehouseList($params){
        $page=!empty($params["page"])?$params["page"]:1;
        $limit=!empty($params["limit"])?$params["limit"]:10;
        
        $where=" (1=1) ";
        
        if(!empty($params["name_like"])){
           $where.=" and cWhName like '%{$params["name_like"]}%' "; 
        }
        
        if(!empty($params["code_like"])){
           $where.=" and cWhCode like '%{$params["code_like"]}%' "; 
        }
        
        if(!empty($params["person_like"])){
           $where.=" and cWhPerson like '%{$params["person_like"]}%' "; 
        }
        
        $retInfo=$this->customerSer->getWarehouse($where,$page,$limit,$params['zt']);
        return $retInfo;  
    }
    
    public function getProductList($params){
        $params["orderListIds"]=!empty($params["orderListIds"])?$params["orderListIds"]:[];
        if(!empty($params["orderListIds"])){
            $dataInfo=array("orderListIds"=>$params["orderListIds"]);
            if(!empty($params['bill_ids'])){
                $dataInfo["provebill_id"]=$params['bill_ids'];
            }
            
            $retInfo=$this->provebill->getProductList($dataInfo);
        }

        
       
        
        
        if(!empty($params["deliver_id"])){
            $dataList=$this->db->select_raw("dev_provebill_deliver_item", "*", " deliver_id={$params['deliver_id']} ");
            $deliver=$this->db->get_one("dev_provebill_deliver", "*"," id={$params['deliver_id']} ");

            if(empty($dataList)) return false;     
            $deliverList=array();
            if(empty($params["orderListIds"])){
                foreach ($dataList as $key=>$val){                   
                    $params["orderListIds"][]=$val["order_list_id"];
                }
            }
            
            foreach ($dataList as $key=>$val)  $deliverList[$val["order_list_id"]]=$val;
            
            $tempInfo=array("orderListIds"=>$params["orderListIds"]);
            
                
            if(!empty($deliver['bill_ids'])){
                $tempInfo["provebill_id"]=$deliver['bill_ids'];
            }
            
        
            $retInfo=$this->provebill->getProductList($tempInfo);
            
            
            
            
            foreach ($retInfo["data"] as $key=>$val){
                
                
                $deliverInfo=$this->db->get_one("dev_provebill_deliver_item", "sum(deliver_box) as deliver_box ", " order_list_id={$val["id"]} and deliver_id!={$params['deliver_id']} ");
                $deliverNum=!empty($deliverInfo["deliver_box"])?$deliverInfo["deliver_box"]:0;
                $retInfo["data"][$key]["deliver_box_max"]=$val["deliver_box_max"]-$deliverNum;//计算剩余最大发货数量

                if(!empty($deliverList[$val["id"]])){

                    $retInfo["data"][$key]["warehouse_name"]=$deliverList[$val["id"]]["warehouse_name"];
                    $retInfo["data"][$key]["warehouse_code"]=$deliverList[$val["id"]]["warehouse_code"];
                    $retInfo["data"][$key]["warehouse_person"]=$deliverList[$val["id"]]["warehouse_person"];
                    $retInfo["data"][$key]["deliver_box"]=$deliverList[$val["id"]]["deliver_box"];
                    $retInfo["data"][$key]["deliver_num"]=$deliverList[$val["id"]]["deliver_num"]; 
                    $retInfo["data"][$key]["deliver_item_id"]=$deliverList[$val["id"]]["id"]; 
                    $retInfo["data"][$key]["more"]=$deliverList[$val["id"]]["more"];
                    $retInfo["data"][$key]["fgweights"]=$deliverList[$val["id"]]["fgweights"];
                    $retInfo["data"][$key]["fnweights"]=$deliverList[$val["id"]]["fnweights"];
                }
                $retInfo["data"][$key]["zpfs"]=$val["dwpf"]*$retInfo["data"][$key]["deliver_num"];
                
                /*if($retInfo['data'][$key]["complete"]=="半成品"){
                    $retInfo['data'][$key]["total_1"]=$retInfo["data"][$key]["deliver_num"]*floatval($val['poPrice']);
                }else{
                    $retInfo['data'][$key]["total_1"]=$retInfo["data"][$key]["deliver_box"]*floatval($val['poPrice']);
                }*/

            }
        }else{
            
            
             
                

            foreach ($retInfo["data"] as $key=>$val){
                
                
                $deliverInfo=$this->db->get_one("dev_provebill_deliver_item", "sum(deliver_box) as deliver_box ", " order_list_id={$val["id"]} ");
                
                
                $deliverNum=!empty($deliverInfo["deliver_box"])?$deliverInfo["deliver_box"]:0;
                
                //$retInfo["data"][$key]["deliver_box_max"]=$val["deliver_box_max"]-$deliverNum;//计算剩余最大发货数量
                //$retInfo["data"][$key]["deliver_box"]=$retInfo["data"][$key]["deliver_box_max"];//计算最大发货数量
                
     
                $onum=!empty($val["onum"])?$val["onum"]:1;
                $val["huanSuanLv"] = !empty($val["huanSuanLv"])?$val["huanSuanLv"]:1;
                
                
                if($val["hz"]==0){
                    $retInfo["data"][$key]["deliver_num"]=$retInfo["data"][$key]["deliver_box"]*$val["huanSuanLv"]*$onum;  
                }else{
                    $retInfo["data"][$key]["deliver_num"]=$retInfo["data"][$key]["deliver_box"]*$onum;
                }
                
     
                if(!empty($val["num"])){
                  
                    $retInfo["data"][$key]["deliver_num"]=$retInfo["data"][$key]["num"]*$val["huanSuanLv"];  
                    /*if($val["complete"]=="半成品"&&$val["category"]!="PVC胶带"){
                        $retInfo["data"][$key]["deliver_box"]=$val["deliver_box"]=$retInfo["data"][$key]["deliver_num"]/($val["huanSuanLv"]);   
                    }else{
                        $retInfo["data"][$key]["deliver_box"]=$val["deliver_box"]=$retInfo["data"][$key]["deliver_num"]/($onum);  
                    }*/
                    
                    $retInfo["data"][$key]["deliver_box"]=$val["box_num"];

                }
                //echo $val["boxNum"]."||";
                 
                if(ceil($val["boxNum"])==ceil($val["deliver_box"])){
                    $retInfo["data"][$key]["deliver_num"]=$val["proUantity"];
                }
                
               
   
                $retInfo["data"][$key]["zpfs"]=$val["dwpf"]*$retInfo["data"][$key]["deliver_num"];
            
                /*if($retInfo['data'][$key]["complete"]=="半成品"){
                    $retInfo['data'][$key]["total_1"]=$retInfo["data"][$key]["deliver_num"]*floatval($val['poPrice']);
                }else{
                    $retInfo['data'][$key]["total_1"]=$retInfo["data"][$key]["deliver_box"]*floatval($val['poPrice']);
                }*/
                
              
                if(!empty($params["deliver_store"])){
                    //先根据胶系查询,胶系查询不到再去掉胶系查询
                    $warehouse=$this->db->get_one("dev_deliver_warehouse", "*", " deliver_store='{$params["deliver_store"]}' and FIND_IN_SET('{$val["category"]}',product_type) and complete='{$val["complete"]}' and FIND_IN_SET('{$val["glueSty"]}',glue_style) ");
            
                  
                    $retInfo["data"][$key]["warehouse_name"]="";
                    $retInfo["data"][$key]["warehouse_code"]="";
                    $retInfo["data"][$key]["warehouse_person"]="";
                    if(!empty($warehouse)){
                        $retInfo["data"][$key]["warehouse_name"]=$warehouse["warehouse_name"];
                        $retInfo["data"][$key]["warehouse_code"]=$warehouse["warehouse_code"];
                        $retInfo["data"][$key]["warehouse_person"]=$warehouse["warehouse_person"];
                    }
                    
              
                    //混装产品仓库判断
                    if(!empty($val["hz"])&&!empty($val["provebill_id"])){
                        $cabinet=$this->db->get_one("dev_provebill_cabinet","*","bill_id={$val["provebill_id"]}");
                        $orderList=$this->db->select_raw("dev_orderlist","*","bhz={$val["id"]} and category!='PVC胶带' and deleted=0 ");
                        
    
                        $categorys=[];
                        foreach ($orderList as $k=>$v){
                            $categorys[]=$v["category"];
                        }
                        
                        
                        if($cabinet["address"]=="上海仓库"&&$val["complete"]=="成品"){
                            $retInfo["data"][$key]["warehouse_name"]="上海_布基成品仓库";
                            $retInfo["data"][$key]["warehouse_code"]="150";
                            $retInfo["data"][$key]["warehouse_person"]="陈振蕾";
                        }else if($cabinet["address"]=="江西仓库"&&$val["complete"]=="成品"){
                            $retInfo["data"][$key]["warehouse_name"]="分公司_外销成品仓库";
                            $retInfo["data"][$key]["warehouse_code"]="154";
                            $retInfo["data"][$key]["warehouse_person"]="姜何";
                        }
                        
                        
                        
                        if(!empty($cabinet)&&!empty($categorys)){
                            /*if($cabinet["address"]=="江西仓库"&&in_array("PVC胶带", $categorys)&&count($categorys)==1){
                                $retInfo["data"][$key]["warehouse_name"]="江西一期_PVC成品仓库";
                                $retInfo["data"][$key]["warehouse_code"]="153";
                                $retInfo["data"][$key]["warehouse_person"]="郎学荣";
                            }else*/
    
                            
                        }
                        
                        
                        
                        
                    } 
                }
                
                

            }    
 
        }
        
       
     
        foreach ($retInfo["data"] as $key=>$val){ 
            
           
            if(empty($val['total_1'])){
                $retInfo["data"][$key]["deliver_total_1"]=$val['deliver_total_1']=floatval($val['deliver_num'])*floatval($val['proPrice']);//发货单价
                $retInfo['data'][$key]["poPrice"]=$val['deliver_price']; //发货单价
                $retInfo['data'][$key]["total_1"]=$val['deliver_total_1']; //发货单价 
            }else{
                $retInfo["data"][$key]["deliver_total_1"]=$val['total_1']; 
                $retInfo['data'][$key]["poPrice"]=$retInfo['data'][$key]["proPrice"]=$retInfo['data'][$key]["deliver_price"]=$val['total_1']/floatval($val['deliver_num']); //发货单价
            }
           
            
            
            $retInfo['data'][$key]["apportion_total"]=$val["apportion_total"]= floatval($val['apportion_price']) +floatval($retInfo['data'][$key]["total_1"]); //发货单价
            
            //echo $val['deliver_box']."||";
            if(!empty($val["apportion_total"])){
                $retInfo['data'][$key]["apportion_unit_price"]= round(floatval($val["apportion_total"])/floatval($val["num"]),2) ;
                /*if($retInfo['data'][$key]["boxNum"]==$retInfo['data'][$key]["poUantity"]){
                    $retInfo['data'][$key]["apportion_total"]=$retInfo["data"][$key]["deliver_box"]*floatval($retInfo['data'][$key]["apportion_unit_price"]);  
                }else{
                    $retInfo['data'][$key]["apportion_total"]=$retInfo["data"][$key]["deliver_num"]*floatval($retInfo['data'][$key]["apportion_unit_price"]); 
                }*/
            }
            
            
            $retInfo['data'][$key]["total_1"]= round($retInfo['data'][$key]["total_1"],2);
            
            if(empty($val["deliver_num"])){
                unset($retInfo["data"][$key]);
            }
            
  
        }   
       
        
        
      
       
//        var_dump($retInfo);exit;
        return $retInfo;
    }
    

    /**
     * 导入U8
     * @param type $params
     */    
    public function deliverToU8($params){
      
        if(empty($params["deliverId"])) return array("status"=>500,"msg"=>"参数错误！","data"=>array());

  
        $deliverInfo = $this->getPageList(array("id"=>$params["deliverId"],"limit"=>1));
        $productList=$this->getProductList(array("deliver_id"=>$params["deliverId"],"orderListIds"=>$deliverInfo["product_ids"],"bill_ids"=>$deliverInfo["bill_ids"]));//订舱
        
        if(empty($productList["data"])) return array("status"=>500,"msg"=>"参数错误！","data"=>array());
        
        $orderProduct=$orderNums=array();
        foreach ($productList["data"] as $key=>$val){
            $orderProduct[$val["orderId"]][]=$val;
        }
  
        $billInfo=$this->db->get_one("dev_provebill", "*", " id in ({$deliverInfo['bill_ids']})");
        
        if(!empty($billInfo["adjust_bill"])){
            $billInfo["adjust_bill"] = string::autoCharset(json_decode(string::autoCharset($billInfo["adjust_bill"], 'gbk', 'utf-8'),true), 'utf-8', 'gbk');
        }
        
        $productItem=array();
        foreach ($productList["data"] as $key=>$val){
            $productItem=$val;
        }

        $orderId=$productItem["orderId"];
        
        
        
        $success=true;
        $ordHeader = $this->db->get_one("dev_order as a inner join dev_ordernum as b on b.status=a.id ",'a.*,b.ddLeiXing,b.orderType as num_orderType,b.desc as b_desc'," a.id={$orderId}");
       
    
        $ordHeader["typeInfo"]="内销";
        if(!empty($ordHeader['ddLeiXing'])&&in_array($ordHeader['ddLeiXing'], ["YW"])){
            $ordHeader["typeInfo"]="外销";
        }
        

 
        //根据订单号取对应的u8信息
        $u8Info = $this->orderSer->getDBNumByOrdNum($ordHeader['orderNum']); 
        if(empty($u8Info)){
            return array("status"=>500,"msg"=>"无法获取u8配置信息！","data"=>array());
        }
        //切换u8数据库连接
        $this->msdb = new mssqlDB($u8Info['ip'],array('Database'=>'MiddleBase','UID'=>'sa','PWD'=>$u8Info['pass']));

        if(empty($this->msdb)){
            return array("status"=>500,"msg"=>"无法连接u8数据库！","data"=>array());
        }

        $fhMainInfo = $this->msdb->select_one("Tbl_YG_OAFHMain", "*", "cBillCode='{$deliverInfo['invoice_nums']}'");
        if(!empty($fhMainInfo)&&$fhMainInfo!="-1"){
            //Tbl_YG_OAFHDelete,cU8Code有删除记录可以继续提交
            $fhDeleteInfo = $this->msdb->select_one("Tbl_YG_OAFHDelete", "*", "cU8Code='{$fhMainInfo['cU8Code']}'");
            if(empty($fhDeleteInfo)||$fhDeleteInfo=="-1"){
                return array("status"=>500,"msg"=>"发货中间表发票已经存在！","data"=>array());
            }
        }
        // 启动事务
        if (sqlsrv_begin_transaction($this->msdb->conn) === false) {
            return array("status"=>500,"msg"=>"启动事务失败！","data"=>array());
        }
        
        
 
        
        foreach ($orderProduct as $key=>$val){
            $orderInfo = $this->db->get_one("dev_order as a left join dev_ordernum as b on b.status=a.id ",
                'a.*,b.ddLeiXing,b.orderType as num_orderType,b.desc as b_desc'," a.id={$key} ");

            $manual=$this->db->get_one("dev_order_manual","*"," orderId={$orderInfo["id"]} ");
 
                
            $orderNums[]=$orderInfo['orderNum'].$orderInfo['b_desc'];
            foreach ($val as $k=>$v){
                $orderItem=$this->db->get_one("dev_orderlist", "*"," id={$v["id"]} ");
                if(empty($orderItem)) continue;
                $tempArr = array();
                $tempArr['cOrderCode'] = $orderInfo['orderNum'].$orderInfo['b_desc'];//订单号
                $tempArr['cFHCode'] =$deliverInfo['deliver_num'];//发货单号
                $tempArr['iOaAutoId'] = intval($v['id']);//订单明细ID
                $tempArr['iQuantity'] = floatval($v['deliver_num']);//发货数量
                if($v['category'] == '美纹纸胶带' && !empty($manual['manual'])){
                    $tempArr['cWhCode'] ='905';//发货仓库编码
                }else{
                    $tempArr['cWhCode'] =$v['warehouse_code'];//发货仓库编码
                }

                $tempArr['more'] = 0;//是否分批发货
                //判断是否存在E字符
                if(strpos($v['deliver_total_1'], 'E') !== false){
                    $tempArr['total'] = $v['total_1'];
                }else{
                    $tempArr['total'] =!empty($v['deliver_total_1'])?$v['deliver_total_1']:$v['total_1'];//总价
                }

                
                
                $ratio=0;
                if($billInfo["adjust_money"]!=0){
                    $ratio=$v['apportion_price']/$billInfo["adjust_money"];
                }
                
                
                if(!empty($billInfo["adjust_bill"])&&is_array($billInfo["adjust_bill"])&&!empty($v['is_apportion'])){
                    
                    $compensate=0;
                    foreach ($billInfo["adjust_bill"] as $k_1=>$v_1){
                        if($v_1["acut_adj_type"]=="赔款"){
                            $compensate=$ratio*$v_1["adjust_money"];
                            $tempArr['compensate'] = $compensate;//总价
                        }
                    }
 
                    //$tempArr['Rebate'] =$v['apportion_price']-$compensate;//总价
                    $tempArr['Rebate'] = round($v['apportion_price'],3)-round($compensate,3);//总价

                    //间接外销币种转换
                    if(!empty($v["frignTotal"])&&doubleval($v["frignTotal"])!=0){
                        $rate= doubleval($orderItem["total"]/$orderItem["frignTotal"]);
                        $rate=0;//间接外销调账清零
                        $tempArr['Rebate']=doubleval($tempArr['Rebate']*$rate);//算出汇率
                        $tempArr['compensate']=doubleval($tempArr['compensate']*$rate);
                    }
                    
                    $this->db->update("dev_provebill_deliver_item",array("compensate"=>$compensate,"rebate"=>$tempArr['Rebate'])," deliver_id={$deliverInfo["id"]}  and order_list_id={$v["id"]} ");
                }
                
                if(!empty($v["frignTotal"])&&doubleval($v["frignTotal"])!=0){
                    $tempArr['total']= doubleval($orderItem['proPrice'])*doubleval($v['deliver_num']);
                }
     
                if(!empty($v['more'])&&$v['more']=="是"){
                    $tempArr['more'] = 1;//是否分批发货
                }
                $productInfo = $this->db->get_one('dev_product','*',"id= {$orderItem['productId']} ");
                $hsInfo = $this->db->get_one('dev_product_hscode','*',"hs_code = '{$productInfo['hsCode']}' and hs_code_name = '{$productInfo['hsCodeName']}' ");
                $tempArr['cHsName'] = $hsInfo['grade_name'];//海关名称
                $tempArr['cHsCode'] = $productInfo['hsCode'];//海关编码

                $temp = $this->msdb->insert('Tbl_YG_OAFHDetail',$tempArr,1);

                
                if($temp!=1) $success=false;
            }
        }

        $tempArr = array();
        $tempArr['id'] = $deliverInfo['id'];//订单Id
        $tempArr['cOrderCode'] = implode(",", $orderNums);//订单号
        $tempArr['cFHCode'] = $deliverInfo['deliver_num'];//发货单号
        $tempArr['dDate'] =  date('Y-m-d', strtotime($deliverInfo['deliver_date']));//发货日期
        $tempArr['cMaker'] = current(explode(",", GetUserNameByUid($deliverInfo['creator_id'])));//发货人
        $tempArr['dCreateDate'] = date('Y-m-d H:i:s');//填报日期
        $tempArr['cAccId'] = $u8Info["db3"];//账套号
        $tempArr['cVouchType']=$ordHeader["typeInfo"];
        $tempArr['iStatus']=0;
        $tempArr['dLastDate'] = date('Y-m-d H:i:s');//最后更新时间
        $tempArr['cMsg'] = "";//错误消息
        $tempArr['cWhCode'] = $deliverInfo['warehouse_code'];//发货仓库编码
        $tempArr['cBox'] = $deliverInfo['pack_num'];//箱号
        $tempArr['cLock'] = $deliverInfo['seal_num'];//锁号
        $tempArr['cJsCode'] = $deliverInfo['settle_type'];//结算方式 
        if(mb_strlen($deliverInfo['mark'], 'GBK') > 170 ) {
            $tempArr['cMemo'] = mb_substr($deliverInfo['mark'], 0, 170,'GBK');
        }else{
            $tempArr['cMemo'] = $deliverInfo['mark'];//备注
        } 
        
        $tempArr['cBillCode'] =$deliverInfo["invoice_nums"];//发票号
        
        $tempArr['dMakeDate'] = $deliverInfo['create_time'];//发货日期
        $tempArr['cDefine3'] = $deliverInfo['final_customer_code'];//最终用户
        $tempArr['cExchName'] = $deliverInfo['coin_type'];
        

        $temp = $this->msdb->insert('Tbl_YG_OAFHMain',$tempArr,1);
        
        if($temp!=1) $success=false;
        
        if($success){
            sqlsrv_commit($this->msdb->conn); //提交事务
        }else{
            sqlsrv_rollback($this->msdb->conn); // 事务回滚
            return array("status"=>500,"msg"=>$temp,"data"=>array());   
        }

       
        return array("status"=>200,"msg"=>"成功！","data"=>$success);
    }
    
    //U8装箱
    public function cabinetToU8($params){

        if(empty($params["deliverId"])){
            return array("status"=>500,"msg"=>"参数错误！","data"=>array());
        }

        $u8Info=$this->db->get_one("dev_config", "*", " zhangTao='MT' ");
        if(empty($u8Info)){
            return array("status"=>500,"msg"=>"无法获取u8配置信息！","data"=>array());
        } 
        
       
        $prcsFlag=!empty($params["prcsFlag"])?$params["prcsFlag"]:"";
        
        $msdb = new mssqlDB($u8Info['ip'],array('Database'=>$u8Info['db1'],'UID'=>'sa','PWD'=>$u8Info['pass']));
        
        
        $deliverInfo = $this->getPageList(array("id"=>$params["deliverId"],"limit"=>1));
        if(empty($deliverInfo["sales_num"])){
            return array("status"=>500,"msg"=>"销货单号不得为空！","data"=>array());
        }

        $u8Cabinets=$this->getU8Cabinets(array("deliverId"=>$params["deliverId"]));
        
        if(empty($msdb)){
            return array("status"=>500,"msg"=>"无法连接u8数据库！","data"=>array());
        }

        // 启动事务
        if (sqlsrv_begin_transaction($msdb->conn) === false) {
            return array("status"=>500,"msg"=>"启动事务失败！","data"=>array());
        }

        $success=true;
        
        $lockMap=array();
        foreach ($u8Cabinets as $key=>$val){
            foreach ($val as $k=>$v){  
                if($prcsFlag=="办理完毕"){
                    $boxList=array();
                    if(empty($lockMap[$deliverInfo['sales_num']][$v['iOaAutoId']])){
                        $boxList = $msdb->select_raw('Tbl_YG_OAFHBox',"*"," cU8Code='{$deliverInfo['sales_num']}' and iOaAutoId='{$v['iOaAutoId']}' ");
                        $lockMap[$deliverInfo['sales_num']][$v['iOaAutoId']]=true;
                    }
                    
                    if(!empty($boxList)&&is_array($boxList)&& count($boxList)==1){
                        $boxInfo = current($boxList);
                        $temp = $msdb->update('Tbl_YG_OAFHBox',$v," cU8Code='{$deliverInfo['sales_num']}' and iOaAutoId='{$v['iOaAutoId']}' ");//更新
                        $msdb->update('Tbl_YG_CKDetails',array("cBox"=>$v['cBox'],"cLock"=>$v['cLock'])," cBox='{$boxInfo['cBox']}' and cLock='{$boxInfo['cLock']}' ");//更新
                    }else if(!empty($boxList)&&is_array($boxList)&&count($boxList)>1){
                        $msdb->delete('Tbl_YG_OAFHBox'," cU8Code='{$deliverInfo['sales_num']}' and iOaAutoId='{$v['iOaAutoId']}'  ");
                        $temp = $msdb->insert('Tbl_YG_OAFHBox',$v);//添加
                    }else{
                        $temp = $msdb->insert('Tbl_YG_OAFHBox',$v);//添加
                    }
                    
                }else{
                    $temp = $msdb->insert('Tbl_YG_OAFHBox',$v);//添加
                }
                if($temp!=1) $success=false;
            }
            
        }
        
        if(!empty($params["cabinet_change"])&& is_array($params["cabinet_change"])){
            foreach ($params["cabinet_change"] as $key=>$val){
                $temp = $msdb->select_one('Tbl_YG_CKDetails',"*"," cBox='{$val["pack_num"]}' and cLock='{$val["seal_num"]}' ");
                if(!empty($temp)&& is_array($temp)){
                   $msdb->update('Tbl_YG_CKDetails',array("cBox"=>$val["pack_num_new"],"cLock"=>$val["seal_num_new"])," cBox='{$val["pack_num"]}' and cLock='{$val["seal_num"]}' ");
                }      
            }
        }
         
   
        if($success){
            sqlsrv_commit($msdb->conn); //提交事务
        }else{
            sqlsrv_rollback($msdb->conn); // 事务回滚
            return array("status"=>500,"msg"=>"导入U8失败","data"=>array());   
        }
        
        
       
        
        return array("status"=>200,"msg"=>"成功！","data"=>$success);
       
    }
    
    public function getCabinetInfo($params){
        if(empty($params["billIds"])) return false;     
        $cabinetInfo=$this->db->get_one("dev_provebill_cabinet", "*", " bill_id in ({$params["billIds"]}) ");
        
        $cabinetInfo["real_cabinet_time"]=$cabinetInfo["real_cabinet_time"]!="0000-00-00"?$cabinetInfo["real_cabinet_time"]:"";
        
        //$retInfo=!empty($cabinetInfo["receipt_attachment"])?$cabinetInfo["receipt_attachment"]:"";
        return $cabinetInfo;
    }




    //获取U8装箱列表
    public function getU8Cabinets($params){
        $deliverInfo = $this->getPageList(array("id"=>$params["deliverId"],"limit"=>1));
        $dbInfo=array();
        if(!empty($deliverInfo["account_type"])){
            $dbInfo=$this->db->get_one("dev_config", "*", " zhangTao='{$deliverInfo["account_type"]}' ");
        }
        
        $cabinetList= string::autoCharset(json_decode(string::autoCharset($deliverInfo['cabinet_list'], 'gbk', 'utf-8'),true), 'utf-8', 'gbk');
      
        $updateList=array();
        if(!empty($cabinetList)){
            foreach ($cabinetList as $key=>$val){
                if(!empty($val["product_list"])){
                    foreach ($val["product_list"] as $k=>$v){
                        $updateList[$v['orderId']][]=array(
                            "cOrderCode"=>$v["order_num_show"],//订单号
                            "cFHCode"=>$deliverInfo["deliver_num"],
                            "iOaAutoId"=>$v["id"],
                            "iQuantity"=> floatval($v["deliver_num"]),
                            "Quantity"=>floatval($v["cabinet_num"]),
                            "cWhCode"=>$v["warehouse_code"],
                            "cBox"=>$val["pack_num"],
                            "cLock"=>$val["seal_num"],
                            "Company"=>$deliverInfo["pro_loan_company"],
                            "cU8Code"=>$deliverInfo["sales_num"],
                            "cAcc_Id"=>!empty($dbInfo["db3"])?$dbInfo["db3"]:"",
                            "transportType"=>$deliverInfo["transport"],
                            "tuopan"=>$v["tuopan_num"],                         
                            //"cU8Code"=>$v["u8Code"],
                        );
                    }
                }  
            }
        } 
        return $updateList;
    }
    
    public function changeToU8($params){
        
    
        $product=current($params["product"]);
        //根据订单号取对应的u8信息
        $u8Info = $this->db->get_one("dev_ordernum as a left join dev_config as b on a.zhangTao=b.zhangTao ", "b.*","a.status={$product['orderId']}");
        $flowInfo= $this->db->get_one("dev_flows","*"," id={$params['flow_id']} ");
        
        if(empty($flowInfo)){
            return array("status"=>500,"msg"=>"流程不存在！","data"=>array());
        }
        
        
        $deliverInfo=$this->db->get_one("dev_provebill_deliver","*"," id={$flowInfo['flowCaseId']} ");
        $billInfo=$this->db->get_one("dev_provebill", "*", " id in ({$deliverInfo['bill_ids']})");
        $billInfo["adjust_bill"] = string::autoCharset(json_decode(string::autoCharset($billInfo["adjust_bill"], 'gbk', 'utf-8'),true), 'utf-8', 'gbk');
        
        
        $flowData2= string::autoCharset(json_decode(string::autoCharset($flowInfo["data2"],'gbk','utf-8'), true),'utf-8','gbk');
        
  
        if(empty($u8Info)){
            return array("status"=>500,"msg"=>"无法获取u8配置信息！","data"=>array());
        }
        

        //切换u8数据库连接
        $this->msdb = new mssqlDB($u8Info['ip'],array('Database'=>'MiddleBase','UID'=>'sa','PWD'=>$u8Info['pass']));

        if(empty($this->msdb)){
            return array("status"=>500,"msg"=>"无法连接u8数据库！","data"=>array());
        }

        // 启动事务
        if (sqlsrv_begin_transaction($this->msdb->conn) === false) {
            return array("status"=>500,"msg"=>"启动事务失败！","data"=>array());
        }
           

        $success=true;
        

        foreach($params["product"] as $key=>$val){
           
            $u8Deliver = $this->msdb->select_one("Tbl_YG_OAFHMain", "*", "  cFHCode='{$deliverInfo["deliver_num"]}' ");
            if(empty($u8Deliver["bOA"])){
                $this->msdb->update("Tbl_YG_OAFHDetail",array("iQuantity"=>$val["deliver_num"],"total"=>$val["total_1"])," cFHCode='{$deliverInfo["deliver_num"]}' and iOaAutoId={$val["id"]} ");            
            }
            
            $U8AutoId=$this->provebill->getU8AutoId($val["id"],$deliverInfo["sales_num"]);
            //if(empty($U8AutoId)) continue;
            $tempArr=array(
                "U8AutoId"=>$U8AutoId,
                "iQuantity"=>$val["deliver_num"],
                "total"=>$val["total_1"],
                "u8code"=>$deliverInfo["sales_num"],
                "cAccId"=>$u8Info["db3"]
            );
            
    
            /*if(!empty($billInfo["adjust_bill"])&&is_array($billInfo["adjust_bill"])&&!empty($val['is_apportion'])){
                if(count($billInfo["adjust_bill"])>1){
                    $tempArr['Rebate'] = !empty($val['apportion_price'])?$val['apportion_price']:0;//总价
                }else{
                    $tempArr['compensate'] = !empty($val['apportion_price'])?$val['apportion_price']:0;//总价
                }
            }*/
            
            
            
            $ratio=0;
            if($billInfo["adjust_money"]!=0){
                $ratio=$val['apportion_price']/$billInfo["adjust_money"];
            }

            if(!empty($billInfo["adjust_bill"])&&is_array($billInfo["adjust_bill"])&&!empty($val['is_apportion'])){

                $tempArr['compensate']=$compensate=0;
                foreach ($billInfo["adjust_bill"] as $k_1=>$v_1){
                    if($v_1["acut_adj_type"]=="赔款"){
                        $compensate=$ratio*$v_1["adjust_money"];
                        $tempArr['compensate'] = $compensate;//总价
                    }
                }

                //$tempArr['Rebate'] =$v['apportion_price']-$compensate;//总价
                $tempArr['Rebate'] = round($val['apportion_price'],3)-round($compensate,3);//总价

                //间接外销币种转换
                if(!empty($val["frignTotal"])&&doubleval($val["frignTotal"])!=0){
                    $orderItem=$this->db->get_one("dev_orderlist", "*"," id={$val["id"]} ");
                    $rate= doubleval($orderItem["total"]/$orderItem["frignTotal"]);
                    $rate=0;//间接外销调账清零
                    $tempArr['Rebate']=doubleval($tempArr['Rebate']*$rate);//算出汇率
                    $tempArr['compensate']=doubleval($tempArr['compensate']*$rate);
                }

            }
            
            
            

            
            $temp = $this->msdb->insert('Tbl_YG_OAFHModifyDetail_Money',$tempArr);

            if($temp!=1) $success=false;
        }

        if($success){
            sqlsrv_commit($this->msdb->conn); //提交事务
        }else{
            sqlsrv_rollback($this->msdb->conn); // 事务回滚
            return array("status"=>500,"msg"=>"导入U8失败","data"=>array());   
        }

       
        return array("status"=>200,"msg"=>"成功！","data"=>$success);
        
    }
    
    
    public function getU8SettleType($params){
        $page=!empty($params["page"])?$params["page"]:1;
        $limit=!empty($params["limit"])?$params["limit"]:10;
        
        $where=" (1=1) ";
        
        if(!empty($params["name_like"])){
           $where.=" and cSSName like '%{$params["name_like"]}%' "; 
        }
        
        $retInfo=$this->customerSer->getSettleType($where,$page,$limit); 
        return $retInfo;  
    }
    
    
    public function getDeliverChange($params){
        if(empty($params['sales_num'])) return false;
        $retInfo=$this->db->select_raw("dev_provebill_deliver_change", "*", " sales_num='{$params['sales_num']}' and is_confirm=0 order by id DESC ");
        return $retInfo;  
    }
    
    public function saveDeliverChange($params){
        $retInfo=array("status"=>200,"msg"=>"更新成功！","data"=>array());
        if(empty($params['id'])) array("status"=>500,"msg"=>"参数错误！","data"=>array());
        
       
        
        $this->db->query("BEGIN");//事务开始
        try {
         
            $deliverInfo=$this->db->get_one("dev_provebill_deliver", "*"," id='{$params["id"]}' ");
            //更新箱号锁号
            $this->db->update("dev_provebill_deliver",array("pack_num"=>$params["pack_num"],"seal_num"=>$params["seal_num"],"cabinet_list"=>$params["cabinet_list"])," id='{$params["id"]}' ");


            $deliverInfo["cabinet_list"]=string::autoCharset(json_decode(string::autoCharset($deliverInfo["cabinet_list"],'gbk','utf-8'), true),'utf-8','gbk');
            $params["cabinet_list"]=string::autoCharset(json_decode(string::autoCharset($params["cabinet_list"],'gbk','utf-8'), true),'utf-8','gbk');

            $cabinetList=array();
            if(!empty($params["cabinet_list"])){
                foreach ($params["cabinet_list"] as $key=>$val){
                    $cabinetList[$val["id"]]=$val;
                }
            }

            $changeInfo=array("pack_num"=>array(),"seal_num"=>array(),"cabinet_type"=>array()); 
            foreach ($deliverInfo["cabinet_list"] as $key=>$val){
                foreach ($changeInfo as $k=>$v){
                    if($val[$k]!=$cabinetList[$val["id"]][$k]){
                        $changeInfo[$k][$val[$k]]=$cabinetList[$val["id"]][$k];
                    }
                }
            }

            $orderPack=$this->db->select_raw("dev_order_pack","*"," sales_num='{$deliverInfo["sales_num"]}'  ");
            if(!empty($orderPack)){
                foreach ($orderPack as $key=>$val){
                    $pack_nums= explode(",", $val["pack_nums"]);
                    $seal_nums= explode(",", $val["seal_nums"]);
                    $packList= string::autoCharset(json_decode(string::autoCharset($val["pack_list"],'gbk', 'utf-8'),true),'utf-8','gbk');
                    
                    if(empty($packList)) continue;
                    $change=false;
                    foreach ($packList as $k=>$v){
                        if(!empty($changeInfo["pack_num"][$v["pack_num"]])){
                            $packList[$k]["pack_num"]=$changeInfo["pack_num"][$v["pack_num"]];
                            $pack_nums[$k]=$changeInfo["pack_num"][$v["pack_num"]];
                            $change=true;
                        }
                        if(!empty($changeInfo["seal_num"][$v["seal_num"]])){
                            $packList[$k]["seal_num"]=$changeInfo["seal_num"][$v["seal_num"]];
                            $seal_nums[$k]=$changeInfo["seal_num"][$v["seal_num"]];
                            $change=true;
                        } 
                    }
                    if($change){
                        $this->db->update("dev_order_pack", array(
                            "is_finish"=>0,
                            "pack_nums"=> implode(",", $pack_nums),
                            "seal_nums"=> implode(",", $seal_nums),
                            "pack_list"=>string::autoCharset(json_encode(string::autoCharset($packList, 'gbk', 'utf-8'),JSON_UNESCAPED_UNICODE), 'utf-8', 'gbk'),
                        )," id={$val['id']} ");
                    }
                    
                }
            }

            //插入变更日志
            $this->db->insert("dev_update_logs", array(
                "type_flag"=>"deliver",
                "case_id"=>$params['id'],
                "name"=>"发货变更",
                "data1"=> string::autoCharset(json_encode(string::autoCharset($deliverInfo, 'gbk', 'utf-8'),JSON_UNESCAPED_UNICODE), 'utf-8', 'gbk'),
                "data2"=>string::autoCharset(json_encode(string::autoCharset($params, 'gbk', 'utf-8'),JSON_UNESCAPED_UNICODE), 'utf-8', 'gbk'), 
                "creator_id"=>$_SESSION["LOGIN_UID"],
                "creator_name"=>GetUserNameByUid($_SESSION['LOGIN_UID']),
                "create_time"=>date("Y-m-d H:i:s"),
            ));
            
            $retInfo=$this->db->update("dev_provebill_deliver_change", array("is_confirm"=>1), " sales_num='{$deliverInfo["sales_num"]}' and is_confirm=0 ");

            $this->db->query("COMMIT");//提交事务

        } catch (Exception $e) {
            $this->db->query("ROLLBACK");//事务回滚
            return array("status"=>500,"msg"=>$e->getMessage(),"data"=>array());   
        }
        
        return $retInfo;  
    }
    

    //导入打印数据到仓库
    public function printWarehouse($params){
        if(empty($params["U8Code"])) return array("status"=>500,"msg"=>"参数错误！","data"=>array());
        
        //根据订单号取对应的u8信息
        $u8Info = $this->db->get_one("dev_config", "*", "zhangTao='{$params["account_type"]}'"); 
        
        $deliver=$this->db->get_one("dev_provebill_deliver", "*", "sales_num='{$params["U8Code"]}'"); 
        if(empty($deliver))  return array("status"=>500,"msg"=>"参数错误！","data"=>array());
        
        $params["cPoCode"]="";
        if(!empty($deliver["po"])){
            $params["cPoCode"]=$deliver["po"];
        }else{ 
            $deliverItem=$this->db->get_one("dev_provebill_deliver_item", " GROUP_CONCAT(order_id) as order_ids ", "deliver_id='{$deliver["id"]}'");             
            if(!empty($deliverItem["order_ids"])){
                $orderInfo= $this->db->get_one("dev_order", "po", " id in ({$deliverItem["order_ids"]}) and po is not null and po !='' ");
                $params["cPoCode"]=!empty($orderInfo["po"])?$orderInfo["po"]:"";
            }
        }
        
    
        if(empty($u8Info)){
            return array("status"=>500,"msg"=>"无法获取u8配置信息！","data"=>array());
        }
        //切换u8数据库连接
        $this->msdb = new mssqlDB($u8Info['ip'],array('Database'=>'MiddleBase','UID'=>'sa','PWD'=>$u8Info['pass']));
        
        if(empty($this->msdb)){
            return array("status"=>500,"msg"=>"无法连接u8数据库！","data"=>array());
        }
        
        $printInfo= $this->msdb->select_one('Tbl_YG_Print_Warehouse','*'," U8Code='{$params["U8Code"]}' ");
        
        if(!empty($printInfo)&&$printInfo!="-1"&&empty($params["opt_type"])){
            return array("status"=>501,"msg"=>"该发货单已经提交打印，是否再次提交！","data"=>array());  
        }        
        

        // 启动事务
        if (sqlsrv_begin_transaction($this->msdb->conn) === false) {
            return array("status"=>500,"msg"=>"启动事务失败！","data"=>array());
        }

        $updateInfo=array(
            "U8Code"=>$params["U8Code"],
            "cAccId"=>$params["cAccId"],
            "cWhCode"=>$params["cWhCode"],
            "cAccName"=>$params["cAccName"],
            "cSource"=>$params["cSource"],
            "cPersonName"=>$params["cPersonName"],
            "cCusName"=>$params["cCusName"],
            "cOrderCode"=>$params["cOrderCode"],
            "dCreateDate"=>date("Y-m-d H:i:s"),
            "cMaker"=> str_replace(",","", $params["cMaker"]),
            "cPoCode"=>$params["cPoCode"]
        );
        
        $temp = $this->msdb->insert('Tbl_YG_Print_Warehouse',$updateInfo);
        $printRet = $this->printLogs(array("U8Code"=>$params["U8Code"]));
        if($temp==1&&$printRet){
            sqlsrv_commit($this->msdb->conn); //提交事务
        }else{
            sqlsrv_rollback($this->msdb->conn); // 事务回滚
            return array("status"=>500,"msg"=>$temp,"data"=>array());   
        }
        
        return array("status"=>200,"msg"=>"提交成功！","data"=>array());
    }
    
    public function getOrderNums($params){
        $retInfo=array("orderListIds"=>array(),"orderNums"=>array());
        if(!empty($params["deliver_id"])){
            $deliverItem = $this->db->get_one("dev_provebill_deliver_item","GROUP_CONCAT(order_list_id) as order_list_ids", " deliver_id={$params['deliver_id']} ");
            $retInfo["orderListIds"]=!empty($deliverItem["order_list_ids"])?$deliverItem["order_list_ids"]:"";
        }
        
        if(!empty($params["orderListIds"])){
            if(!is_array($params["orderListIds"])) $params["orderListIds"]= explode(",", $params["orderListIds"]);
            $retInfo["orderListIds"]= implode(",", $params["orderListIds"]); 
        }
        
        if(!empty($params["provebill_ids"])){
            if(!is_array($params["provebill_ids"])) $params["provebill_ids"]= explode(",", $params["provebill_ids"]);
            $billIds=implode(',',array_map(function ($item){return sprintf("'%s'", $item);},$params["provebill_ids"]));
            
            $billItem = $this->db->get_one("dev_provebill_product","GROUP_CONCAT(order_list_id) as order_list_ids", " provebill_id in ({$billIds}) ");
            $retInfo["orderListIds"]=!empty($billItem["order_list_ids"])?$billItem["order_list_ids"]:"";
        }
        
  
        if(empty($retInfo["orderListIds"])) return $retInfo;
    
        $orderNums = $this->db->get_one("dev_orderlist as a inner join dev_order as b on a.orderId=b.id","GROUP_CONCAT(b.orderNum) as order_nums", " a.id in({$retInfo["orderListIds"]}) group by a.id ");
        $orderNums=!empty($orderNums["order_nums"])?$orderNums["order_nums"]:"";
        
        $retInfo["orderListIds"]= explode(",", $retInfo["orderListIds"]);
        $retInfo["orderNums"]= explode(",", $orderNums);
        return $retInfo;
    }
    
    public function getOrderList($params){        
        $retInfo=array();
        $orderIds=array();
        if(!empty($params["orderListIds"])){
            if(is_array($params["orderListIds"])){
                $params["orderListIds"]= implode(",", $params["orderListIds"]);
                $orderInfo=$this->db->get_one("dev_orderlist", " GROUP_CONCAT(orderId) as orderIds ", " id in({$params["orderListIds"]}) ");
                $orderIds=$orderInfo["orderIds"];
            }
        }else if(!empty($params["billIds"])){
            $orderInfo=$this->db->get_one("dev_provebill_product", " GROUP_CONCAT(order_id) as orderIds ", " provebill_id in({$params["billIds"]}) ");
            $orderIds=$orderInfo["orderIds"];
        }

        if(!empty($orderIds)){
            $retInfo=$this->db->select_raw("dev_order","*"," id in ({$orderIds}) " );
        }
        return $retInfo;
        
    }



    public function printDeliver($params){
        $retInfo=array("status"=>200,"msg"=>"提交成功！","data"=>array());
        
        
        $deliver=$this->db->get_one("dev_provebill_deliver", "*", "sales_num='{$params["U8Code"]}'"); 
        $params["cPoCode"]="";
        if(!empty($deliver["po"])){
            $params["cPoCode"]=$deliver["po"];
        }else{ 
            $deliverItem=$this->db->get_one("dev_provebill_deliver_item", " GROUP_CONCAT(order_id) as order_ids ", "deliver_id='{$deliver["id"]}'");             
            if(!empty($deliverItem["order_ids"])){
                $orderInfo= $this->db->get_one("dev_order", "po", " id in ({$deliverItem["order_ids"]}) and po is not null and po !='' ");
                $params["cPoCode"]=!empty($orderInfo["po"])?$orderInfo["po"]:"";
            }
        }
        
        
        $updateInfo=array(
            "U8Code"=>$params["U8Code"],
            "cAccId"=>$params["cAccId"],
            "cWhCode"=>$params["cWhCode"],
            "cAccName"=>$params["cAccName"],
            "cSource"=>$params["cSource"],
            "cPersonName"=>$params["cPersonName"],
            "cCusName"=>$params["cCusName"],
            "cMaker"=>$params["cMaker"],
            "po"=>$params["cPoCode"],
        );
        $this->db->insert("dev_provebill_deliver_print", $updateInfo);
        
        return array("status"=>200,"msg"=>"提交成功！","data"=>array());

    }
    
    public function printLogs($params){
        $deliver = $this->db->get_one("dev_provebill_deliver","*"," sales_num='{$params["U8Code"]}' ");
        if(empty($deliver)) return false;
        $temp=array();
        if($_SESSION['LOGIN_USER_PRIV']!=1&&$_SESSION["LOGIN_UID"]!=$deliver["creator_id"]){
            $uids1=$this->commSer->getUserGroupIds(array("name"=>"发货通知（散货江西）"));
            $uids2=$this->commSer->getUserGroupIds(array("name"=>"发货通知（散货上海）"));
            $uids1= array_filter(explode(",", $uids1));
            $uids2= array_filter(explode(",", $uids2));
            if(in_array($_SESSION["LOGIN_UID"], $uids1)){
                $temp["print_status"]=2;
            }else if(in_array($_SESSION["LOGIN_UID"], $uids2)){
                $temp["print_status"]=2;
            }else{
                $temp["print_status"]=3;
            }
        }

        $temp["print_logs"] = string::autoCharset(json_decode(string::autoCharset($deliver['print_logs'], 'gbk', 'utf-8'),true), 'utf-8', 'gbk');
        $temp["print_logs"][]=array(
            "uid"=>$_SESSION["LOGIN_UID"],
            "user_name"=>GetUserNameByUid($_SESSION['LOGIN_UID']),
            "create_time"=>date("Y-m-d H:i:s"),
            "print_status"=>!empty($temp["print_status"])?$temp["print_status"]:0
        );

        $temp["print_logs"]= string::autoCharset(json_encode(string::autoCharset($temp["print_logs"], 'gbk', 'utf-8'),JSON_UNESCAPED_UNICODE), 'utf-8', 'gbk');

        $this->db->update('dev_provebill_deliver',$temp," id='{$deliver["id"]}' ");
        
        return true;
    }


    public function updateU8Cabinet($params){
        $u8Info=$this->db->get_one("dev_config", "*", " zhangTao='MT' ");
        $this->msdb = new mssqlDB($u8Info['ip'],array('Database'=>$u8Info['db1'],'UID'=>'sa','PWD'=>$u8Info['pass']));
        foreach ($params as $key=>$val){
            $this->msdb->update("SalePhoto_Pack", array("packNumber"=>$val["pack_num_new"],"sealNumber"=>$val["seal_num_new"])," packNumber='{$val["pack_num"]}' ");
            $this->msdb->update("SalePhoto_Out", array("packCode"=>$val["pack_num_new"])," packCode='{$val["pack_num"]}' ");
        }
        return array("status"=>200,"msg"=>"提交成功！","data"=>array());
    }
    
    public function checkDeliver($params){
        $retInfo = array("status"=>200,"msg"=>"提交成功！","data"=>array());
        $currentFlow=$this->flows->get_current_flow(array("flowId"=>$params["data"]["flow_id"]));
        if(empty($currentFlow)||$currentFlow["status"]!="单证审核"){
            return $retInfo;
        }
        
        $deliver = $this->db->get_one("dev_provebill_deliver", "*", " id={$currentFlow["flowCaseId"]} ");
        if(empty($deliver["sales_num"])){
            return array("status"=>500,"msg"=>"销货单号不得为空！","data"=>array());
        }
        
        
        $connect=$this->db->get_one("dev_config", "*", " zhangTao='MT' ");
        if(empty($connect)){
            return array("status"=>500,"msg"=>"获取服务器配置失败！","data"=>array());
        }
        
        $this->msdb = new mssqlDB($connect['ip'],array('Database'=>$connect['db1'],'UID'=>'sa','PWD'=>$connect['pass']));
        
        $ret=$this->msdb->select_one("Tbl_YG_OAFHBox_log", "*", " cU8Code='{$deliver["sales_num"]}' ");
        
        if(!empty($ret)&&is_array($ret)){
            return array("status"=>500,"msg"=>"箱单已存在，请不要重复装箱！","data"=>array());
        }

        return $retInfo;
    }
    
    /**
     * 设置调账
     * @param type $params
     */
    public function provebillProductAdjust($params){
        $retInfo = array("status"=>200,"msg"=>"提交成功！","data"=>array());
        if(empty($params["products"])){
            $retInfo = array("status"=>500,"msg"=>"参数错误！","data"=>array());
        }

        $this->db->query("BEGIN");//事务开始
        try {
            foreach ($params["products"] as $key=>$val){
                if(empty($val["provebill_id"])||empty($val["order_list_id"]))  continue;
                $product=$this->db->get_one("dev_provebill_product", "*", " provebill_id={$val["provebill_id"]} and order_list_id={$val["order_list_id"]} ");
                if(empty($product))   continue;
                $productAdjust=$this->db->get_one("dev_provebill_product_adjust", "*", " provebill_id={$val["provebill_id"]} and order_list_id={$val["order_list_id"]} ");
                if(!empty($productAdjust))  continue;
                $product["bill_product_id"]=$product["id"];
                unset($product["id"]);
                if(!empty($params['status'])){
                    $product["status"]=1;
                }
                $this->db->insert("dev_provebill_product_adjust",$product);
            }
            $this->db->query("COMMIT");//提交事务
        } catch (Exception $e) {
            $this->db->query("ROLLBACK");//事务回滚
            return array("status"=>500,"msg"=>$e->getMessage(),"data"=>array());   
        }

        return $retInfo;
    }
    
    
    public function deliverChangeOpt($params){
        $retInfo = array("status"=>200,"msg"=>"提交成功！","data"=>array());
        if(empty($params["change_list"])){
            $retInfo = array("status"=>500,"msg"=>"参数错误！","data"=>array());
        }
        
        $billInfo=array();
        //$billFlow=$this->db->get_one("dev_flows", "*", " flowCaseId={} ")
      
        foreach($params["change_list"] as $key=>$val){
            //$val["total_1"],$val["proPrice"];$val["id"];
            $orderItem = $this->db->get_one("dev_orderlist", "*", " id={$val["id"]} ");
            if(empty($orderItem)) continue;
            
            if(empty($billInfo)){
                $billInfo["provebill_id"]=$val["provebill_id"];
                $billFlow=$this->db->get_one("dev_flows", "*", "  flowCaseId={$val["provebill_id"]} and flowType='订舱通知单' ");
                $billInfo["flowId"]=$billFlow["id"];
            }
            
            //更新原始单生产单价
            $this->db->update("dev_orderlist", array("proPrice"=>$val["proPrice"])," id={$val["id"]} ");
            //重新计算订舱单价格
            
            $logList[$orderItem["orderId"]]["data1"]["orderlist"][$val["id"]]=array(
                "proUantity"=>$orderItem["proUantity"],
                "boxNum"=>$orderItem["boxNum"],
                "proPrice"=>$orderItem["proPrice"],
                "proTotal"=>$orderItem["proPrice"]*$orderItem["proUantity"]
            );
            
            $logList[$orderItem["orderId"]]["data2"]["orderlist"][$val["id"]]=array(
                "proUantity"=>$orderItem["proUantity"],
                "boxNum"=>$orderItem["boxNum"],
                "proPrice"=>$val["proPrice"],
                "proTotal"=>$val["proPrice"]*$orderItem["proUantity"]
            ); 
        }
        
      
        if(empty($logList)) return array("status"=>500,"msg"=>"订单不存在！","data"=>array());  
        
       
        $this->db->query("BEGIN");//事务开始
        try {
            foreach ($logList as $key=>$val){
                //插入变更日志
                $this->db->insert("dev_update_logs", array(
                    "type_flag"=>"order",
                    "case_id"=>$key,
                    "name"=>"订单更新",
                    "data1"=> string::autoCharset(json_encode(string::autoCharset($val["data1"], 'gbk', 'utf-8'),JSON_UNESCAPED_UNICODE), 'utf-8', 'gbk'),
                    "data2"=>string::autoCharset(json_encode(string::autoCharset($val["data2"], 'gbk', 'utf-8'),JSON_UNESCAPED_UNICODE), 'utf-8', 'gbk'),
                    "creator_id"=>$_SESSION['LOGIN_UID'],
                    "creator_name"=> GetUserNameByUid($_SESSION['LOGIN_UID'])
                ));
            }
            
            
            $userGroup=$this->db->get_one("dev_user_group","*","name='订单变更通知单证人员'" );
            $msgIds=$userGroup["user_ids"];
            
            $confirmData=array(
                "send_user_id"=>$_SESSION['LOGIN_UID'],
                "send_user_name"=>GetUserNameByUid($_SESSION['LOGIN_UID']),
                "user_ids"=>$msgIds,
                "bill_id"=>$billInfo['provebill_id'],
                "msg"=>"有新的[发货变更]引起的[订舱变更]需要您确认！",
                "change_type"=>"发货变更",
                "change_flow_id"=>"",  
                "change_reason"=>"价格调整",        
                "link"=>"erp4/view/provebill/index/add.php?/#/id=".$billInfo["provebill_id"]."/flow_id=".$billInfo["flowId"]."/change_log_id=0/opt=order_change/change_type=3" 
            );

            $retId= $this->db->insert("dev_provebill_confirm", $confirmData);
            $confirmData["link"].="/confirmId={$retId}";
            

            $this->commSer->sendMsg(array("uids"=>$msgIds,"msg"=>$confirmData["msg"],"path"=>$confirmData["link"]));  
     
            
            $this->db->query("COMMIT");//提交事务
        } catch (Exception $e) {
            $this->db->query("ROLLBACK");//事务回滚
            return array("status"=>500,"msg"=>$e->getMessage(),"data"=>array());   
        }
        
        return array("status"=>200,"msg"=>"成功！","data"=>array());   

    }
    
    
    public function updateDeliverBills($params){
        if(empty($params["ids"])||empty($params["updateInfo"])){
            $retInfo = array("status"=>500,"msg"=>"参数错误！","data"=>array());
        }
        
        $this->db->update("dev_provebill",$params["updateInfo"]," id in ({$params["ids"]}) ");
        return array("status"=>200,"msg"=>"成功！","data"=>array());   
    }


    public function print_up_print_status($params){
        $deliver = $this->db->get_one("dev_provebill_deliver","*"," sales_num='{$params["sales_num"]}' ");
        if(empty($deliver)) return false;
        $temp=array();
       /* print_r($_SESSION['LOGIN_USER_PRIV']);echo "\r\n";
        print_r($_SESSION['LOGIN_UID']);echo "\r\n";
        print_r($deliver["creator_id"]);echo "\r\n";
        exit;*/
        if($_SESSION["LOGIN_UID"]!=$deliver["creator_id"]){//$_SESSION['LOGIN_USER_PRIV']!=1&&
            $uids1=$this->commSer->getUserGroupIds(array("name"=>"发货通知（散货江西）"));
            $uids2=$this->commSer->getUserGroupIds(array("name"=>"发货通知（散货上海）"));
            $uids1= array_filter(explode(",", $uids1));
            $uids2= array_filter(explode(",", $uids2));
            if(in_array($_SESSION["LOGIN_UID"], $uids1)){
                $temp["print_status"]=2;
            }else if(in_array($_SESSION["LOGIN_UID"], $uids2)){
                $temp["print_status"]=2;
            }

            $temp["print_logs"] = string::autoCharset(json_decode(string::autoCharset($deliver['print_logs'], 'gbk', 'utf-8'),true), 'utf-8', 'gbk');
            $temp["print_logs"][]=array(
                "uid"=>$_SESSION["LOGIN_UID"],
                "user_name"=>GetUserNameByUid($_SESSION['LOGIN_UID']),
                "create_time"=>date("Y-m-d H:i:s"),
                "print_status"=>!empty($temp["print_status"])?$temp["print_status"]:0
            );

            $temp["print_logs"]= string::autoCharset(json_encode(string::autoCharset($temp["print_logs"], 'gbk', 'utf-8'),JSON_UNESCAPED_UNICODE), 'utf-8', 'gbk');
            $this->db->update('dev_provebill_deliver',$temp," id='{$deliver["id"]}' ");
        }





        return true;
    }

    public function getWarehouseInfo($params){
        $warehouse=$this->db->get_one("dev_deliver_warehouse", "*", " warehouse_code='{$params["warehouse_code"]}' ");
        return $warehouse;
    }

    public function warehouseChangeToU8($params){
        
    
        $product=current($params["product"]);
        //根据订单号取对应的u8信息
        $u8Info = $this->db->get_one("dev_ordernum as a left join dev_config as b on a.zhangTao=b.zhangTao ", "b.*","a.status={$product['orderId']}");
        $flowInfo= $this->db->get_one("dev_flows","*"," id={$params['flow_id']} ");
        
        if(empty($flowInfo)){
            return array("status"=>500,"msg"=>"流程不存在！","data"=>array());
        }
        
        
        $deliverInfo=$this->db->get_one("dev_provebill_deliver","*"," id={$flowInfo['flowCaseId']} ");
        $billInfo=$this->db->get_one("dev_provebill", "*", " id in ({$deliverInfo['bill_ids']})");
        $billInfo["adjust_bill"] = string::autoCharset(json_decode(string::autoCharset($billInfo["adjust_bill"], 'gbk', 'utf-8'),true), 'utf-8', 'gbk');
        
        
        $flowData2= string::autoCharset(json_decode(string::autoCharset($flowInfo["data2"],'gbk','utf-8'), true),'utf-8','gbk');
        
  
        if(empty($u8Info)){
            return array("status"=>500,"msg"=>"无法获取u8配置信息！","data"=>array());
        }
        

        //切换u8数据库连接
        $this->msdb = new mssqlDB($u8Info['ip'],array('Database'=>'MiddleBase','UID'=>'sa','PWD'=>$u8Info['pass']));

        if(empty($this->msdb)){
            return array("status"=>500,"msg"=>"无法连接u8数据库！","data"=>array());
        }

        // 启动事务
        if (sqlsrv_begin_transaction($this->msdb->conn) === false) {
            return array("status"=>500,"msg"=>"启动事务失败！","data"=>array());
        }
           

        $success=true;
        

        foreach($params["product"] as $key=>$val){
           
            $U8AutoId=$this->provebill->getU8AutoId($val["id"],$deliverInfo["sales_num"]);
            //if(empty($U8AutoId)) continue;
            $tempArr=array(
                "U8AutoId"=>$U8AutoId,
                "total"=>$val["total_1"],
                "u8code"=>$deliverInfo["sales_num"],
                "cAccId"=>$u8Info["db3"],
                "cWhCode"=>$val["warehouse_code"]
            );
            $temp = $this->msdb->insert('Tbl_YG_OAFHModifyDetail_Money',$tempArr);

            if($temp!=1) $success=false;
        }

        if($success){
            sqlsrv_commit($this->msdb->conn); //提交事务
        }else{
            sqlsrv_rollback($this->msdb->conn); // 事务回滚
            return array("status"=>500,"msg"=>"导入U8失败","data"=>array());   
        }
        return array("status"=>200,"msg"=>"成功！","data"=>$success);
        
    }

}



?>
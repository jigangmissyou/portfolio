<?php

/**
 * 账套信息管理
 */

include_once '/inc/auth.inc.php';
include_once '/inc/utility_all.php';
include_once '/inc/utility_org.php';

include_once '/general/erp4/lib/dbTool/mssqlDB.class.php';
include_once '/general/erp4/lib/dbTool/mysqlDB2.class.php';


include_once '/general/erp4/service/common.ser.php';
include_once '/general/erp4/service/flows.ser.php';
include_once '/general/erp4/service/order.ser.php';
include_once '/general/erp4/lib/string.class.php';

class billInvoice{
                
    private $db;
    private $msdb;
    private $tabName='dev_provebill_invoice';

    private $flows;
    private $customerSer;
    private $commSer;
    private $orderSer;
    private $timeField;
        

    public function __construct(){
        $this->db = new mysqlDB();
        $this->customerSer = new customerSer();
        $this->flows=new flowsSer();
        $this->commSer = new commonSer();
        
        $this->orderSer = new orderSer();
        
        $this->timeField=["invoice_date","account_date","expire_date","bill_lading_date","cabinet_date","arrival_date","export_date","pre_arrival_date"];
    }

    /**
     * 
     * @param type $pars
     * @param type $page
     * @param type $limit
     */
    public function getPageList($params){
        $where="(1=1)";
        $filter="a.*,b.id as flow_id,b.status,c.prcsFlag,d.is_invoice";
        $order=" order by a.id desc ";
        $table=" dev_provebill_invoice as a "
                . "left join dev_provebill_deliver as d on d.id=a.deliver_id "
                . " left join dev_flows as b on a.id=b.flowCaseId and b.flowType in('开票流程') "
                . " left join dev_flows_step as c on c.id=(SELECT sub3.id FROM dev_flows_step sub3 WHERE  sub3.flowId = b.id AND sub3.flowPrcs = b.status ORDER BY sub3.id DESC LIMIT 1) ";
   
    
        if(!empty($params["order_num_like"])){
            $where.=" and a.order_num like '%{$params["order_num_like"]}%' ";
        }

        
        if(!empty($params["customer_short_like"])){
            $where.=" and a.customer_short like '%{$params["customer_short_like"]}%' ";
        }
        
        if(!empty($params["invoice_ids"])){
            
            $invoiceIds=$params["invoice_ids"];
            if(!is_array($invoiceIds)) $invoiceIds= explode(",", $invoiceIds);
            foreach ($invoiceIds as $key=>$val){
                $invoice = $this->db->get_one("dev_provebill_invoice", "*"," id={$val} ");
                $invoices = $this->db->select_raw("dev_provebill_invoice", "*"," sales_num='{$invoice['sales_num']}' ");
                if(!empty($invoices)&& count($invoices)>1){
                    foreach ($invoices as $k=>$v){
                        if(in_array($v["id"], $invoiceIds))  continue;
                        $invoiceIds[]=$v["id"];
                    }
                }
            }
            
            $params["invoice_ids"]= implode(",", $invoiceIds);           
            $where.=" and a.id in ({$params["invoice_ids"]}) ";
        }
        
        
        if(!empty($params["status"])){
            $where.=" and b.status = '{$params["status"]}' ";

            if($params["status"]=="财务审核"&&$_SESSION['LOGIN_USER_PRIV']!=1){
                if($params["prcsFlag"]=="未接收"){
                    $where.=" and (c.userId = '' or c.userId is null or FIND_IN_SET({$_SESSION['LOGIN_UID']},c.userId) ) ";
                }else{
                    $where.=" and (a.creator_id={$_SESSION['LOGIN_UID']} or c.deliverUserId={$_SESSION['LOGIN_UID']}  ) ";
                }
            }

        }else if(empty($params["show_type"])&&$_SESSION['LOGIN_USER_PRIV']!=1&&!$this->isInWhiteNameList()){
            $where.=" and a.creator_id={$_SESSION['LOGIN_UID']} ";
        }

        

        //按销货单号筛选
        if(!empty($params["sales_num_like"])){
            $where.=" and a.sales_num like '%{$params["sales_num_like"]}%' ";
        }
        
        if(!empty($params["prcsFlag"])){
            $where.=" and c.prcsFlag = '{$params["prcsFlag"]}' ";
        }
        
        if(!empty($params["is_verify"])){
            $where.=" and a.is_verify = '{$params["is_verify"]}' ";
        }

        if($_SESSION['LOGIN_USER_PRIV']!=1){            
            //$_SESSION["LOGIN_UID"]
        }
        
        if(!empty($params["verify"])){
            switch ($params["verify"]){
                case "1":
                    $where.=" and a.total_balance>0 and cU8Code!='' "; 
                break;
            }
        }

        //按创建时间筛选
        if(!empty($params["start_time1"])){
            $where.=" and a.create_time>='{$params["start_time1"]}' ";
        }
        if(!empty($params["end_time1"])){
            $where.=" and a.create_time<='{$params["end_time1"]}' ";
        }

        //按创建时间筛选
        if(!empty($params["start_time2"])){
            $where.=" and a.export_date>='{$params["start_time2"]}' ";
        }
        if(!empty($params["end_time2"])){
            $where.=" and a.export_date<='{$params["end_time2"]}' ";
        }

        $page=!empty($params['page'])?$params['page']:1;
        $limit=!empty($params['limit'])?$params['limit']:10;
        
        
        if($limit==1){
            $dataList=$this->db->get_one($table,$filter,$where);
        }else{
            $dataList=$this->db->select_page_raw($table,$filter,$where,$order,$page,$limit);
            if(!empty($dataList["data"])){
                foreach ($dataList["data"] as $key=>$val){
                    $dataList["data"][$key]["create_user"]= GetUserNameByUid($val['creator_id']);
                    $this->timeFilter($dataList["data"][$key],$this->timeField);
                    /*$u8Deliver=$this->customerSer->getDeliverData(array("sales_num"=>$val["sales_num"]));
                    $dataList["data"][$key]["expiry_date"]=$dataList["data"][$key]["approver"]="";
                    if(!empty($u8Deliver)&&$u8Deliver!=-1){
                        $dataList["data"][$key]["expiry_date"]=!empty($u8Deliver["dgatheringdate"])?$u8Deliver["dgatheringdate"]->format('Y-m-d'):"";//到期日
                        $dataList["data"][$key]["approver"]=$u8Deliver["cverifier"];//审批人
                    }*/
                    
                    
                    $dataList["data"][$key]["adjust_money"]= 0.00;
                    $dataList["data"][$key]["settle_total"]= 0.00;
                    
                    
                    $frozenInfo= $this->db->get_one("dev_collection_verify_frozen", " sum(frozen_total) as frozen_total " , " case_id={$val["id"]} and capital_type='invoice' ");
                    $frozenTotal=!empty($frozenInfo["frozen_total"])?$frozenInfo["frozen_total"]:0;
                    $dataList["data"][$key]["total_balance"]= doubleval($val["total_balance"])-doubleval($frozenTotal); 
                    
                }
            }
        }
        
        
        return $dataList;
    }
    
    public function timeFilter(&$dataInfo,$filterArray){
        if(empty($dataInfo)||empty($filterArray)) return false;
        foreach ($filterArray as $key=>$val){
            if(!empty($dataInfo[$val])){
                $dataInfo[$val]=strpos($dataInfo[$val],'0000-00-00') === false?$dataInfo[$val]:"";
            }
        }
    }

    public function isInWhiteNameList(){
        $orderSer = new orderChange();
        $retInfo = $orderSer->getUserGroupIds(["name"=>"发票可以查看全部"]);
        if(!empty($retInfo)){
            $arr = explode(",", $retInfo);
            if(in_array($_SESSION['LOGIN_UID'], $arr)){
                return true;
            }else{
                return false;
            }
        }else{
            return false;
        }
    }


    //初始化
    public function initInvoiceInfo($params){
        $retInfo=array("status"=>200,"msg"=>"成功！","data"=>array());
        $invoice=$deliver=$provebill=$cabinet=$orderInfo=$accountInfo=$accountSet=$exportInfo=$flowInfo=array();
        if(!empty($params["id"])){
            $invoice=$this->db->get_one("dev_provebill_invoice", "*", " id={$params["id"]} ");
            $this->timeFilter($invoice,$this->timeField);
            $params["deliver_id"]=$invoice["deliver_id"];
        }
        
        if(!empty($params["deliver_id"])){
            $deliver=$this->db->get_one("dev_provebill_deliver", "*", " id in ({$params["deliver_id"]}) "); 
        }
        

        if(!empty($deliver["bill_ids"])){
            $provebill=$this->db->get_one("dev_provebill", "*", " id={$deliver["bill_ids"]} ");
            $orderInfo=$this->db->get_one("dev_provebill_product as a inner join dev_ordernum as b on a.order_id=b.status inner join dev_order as c on c.id=a.order_id "
                    , "b.*,c.rtype", " a.provebill_id={$provebill["id"]} ");
            if(!empty($orderInfo)){
                $orderNum=$orderInfo["num"].$orderInfo["desc"];
                $accountInfo=$this->db->get_one("dev_jdjl", "*", "orderNum='{$orderNum}' ");
            }
            
            $exportInfo=$this->db->get_one("dev_flows", "*", " flowCaseId={$provebill["id"]} and flowType='单据打印审核' and data1='6' order by id DESC ");
            $exportInfo["data2"]= string::autoCharset(json_decode(string::autoCharset($exportInfo["data2"],'gbk','utf-8'), true),'utf-8','gbk');
            $accountSet=$this->db->get_one("dev_account_set", "*", " account_type='{$orderInfo["zhangTao"]}' ");
            
        }
 
        if(!empty($provebill["id"])){
            $cabinet=$this->db->get_one("dev_provebill_cabinet", "*", " bill_id={$provebill["id"]} ");
            $accountMap=array("上海仓库"=>"SH","江西仓库"=>"JX","越南仓库"=>"YN");
            if(!empty($accountMap[$cabinet["address"]])){
                $accountSet=$this->db->get_one("dev_account_set", "*", " account_type='{$accountMap[$cabinet["address"]]}' ");
            }
    
        }
   

        
        if(!empty($invoice)){
            $retInfo["data"]=$invoice;
        }else{
            $retInfo["data"]=array( 
                "bill_id"=>$provebill["id"],
                "cabinet_id"=>$cabinet["id"],
                "deliver_id"=>$deliver["id"],
                "order_num"=>$deliver["invoice_nums"],
                "sales_num"=>$deliver["sales_num"],
                "customer_short"=>$deliver["customer_short"],
                "trade_type"=>$provebill["trade_type"],
                "pay_type"=>$deliver["settle_type"],
                "account_term"=>$accountInfo["zq"],
                "customer_code"=>$provebill["customer_code"],
                "coin_type"=>$provebill["coin_type"],
                "rtype"=>$orderInfo["rtype"],                
                "cabinet_date"=>!empty($cabinet["real_cabinet_time"])&&$cabinet["real_cabinet_time"]!='0000-00-00'?$cabinet["real_cabinet_time"]:"",
                "arrival_date"=>!empty($cabinet["real_cabinet_time"])&&$cabinet["real_cabinet_time"]!='0000-00-00'?date("Y-m-d", strtotime($cabinet["real_cabinet_time"]." +3 day")):"",
                "pre_arrival_date"=>!empty($cabinet["etd_date"])&&$cabinet["etd_date"]!='0000-00-00'?$cabinet["etd_date"]:"", 
                "cabinet_address"=>$accountSet["product_source"],
                "start_port"=>$provebill["start_port"],
                "end_port"=>$provebill["end_port"],
                "invoice_date"=>date("Y-m-d"),
                /*"export_date"=>$exportInfo["data2"]["export_date"]  */
                "export_date"=>!empty($cabinet["real_ship_time"])&&$cabinet["real_ship_time"]!='0000-00-00'?$cabinet["real_ship_time"]:"",
                "exchange_rate"=>1,
            );
            
            
            if(!empty($deliver["account_type"])){
                
    
                $config = $this->db->select_one('dev_config','*',"zhangTao='{$deliver["account_type"]}'");
                if(!empty($config)) {
                    //切换u8数据库连接
                    $this->msdb = new mssqlDB($config['ip'],array('Database'=>$config['db1'],'UID'=>'sa','PWD'=>$config['pass']));
                    $u8Info=$this->msdb->select_one("ex_consignment","csscode,dcreditstart,dgatheringdate,icreditdays,fexchrate"," ccode = '{$deliver["sales_num"]}' ");
    
                    if(!empty($u8Info)&& is_array($u8Info)){
                        $retInfo["data"]["pay_type"]=!empty($u8Info["csscode"])?$u8Info["csscode"]:"";
                        $retInfo["data"]["account_date"]=!empty($u8Info["dcreditstart"])?$u8Info["dcreditstart"]->format('Y-m-d H:i:s'):"";
                        $retInfo["data"]["expire_date"]=!empty($u8Info["dgatheringdate"])?$u8Info["dgatheringdate"]->format('Y-m-d H:i:s'):"";
                        $retInfo["data"]["account_term"]=$u8Info["icreditdays"];
                        // $retInfo["data"]["exchange_rate"]=$u8Info["fexchrate"]; //汇率暂时不用
                    }               
                }
               
            }

        }
        $config = $this->db->select_one('dev_config','*',"zhangTao='{$deliver["account_type"]}'");
        $this->msdb = new mssqlDB($config['ip'],array('Database'=>$config['db1'],'UID'=>'sa','PWD'=>$config['pass'])); 
        $hlList = $this->msdb->select_raw('exch','cexch_name,iYear,iperiod,nflat'," itype = 2 and cexch_name = '{$provebill["coin_type"]}' order by iYear desc, iperiod desc");
        $dataList = [];
        if(!empty($hlList)&&is_array($hlList)){
            foreach ($hlList as $key => $value) {
                $dataList[$key]=array("name"=>$value["iYear"]."-".$value["iperiod"]."(".$value["nflat"].")","value"=>$value["nflat"]);
            }
        }
        $retInfo["data"]["select"] = array("exchange_rate"=>array("desc"=>"请填写汇率","dataList"=>$dataList));

        if(!empty($params["flow_id"])){
            $retInfo["data"]["flow_info"]=$this->flows->get_current_flow(array("flowId"=>$params["flow_id"]));
        }

        return $retInfo;
    }

    /**
     * 更新发票数据
     * @param type $params
     * @return type
     */
    public function updateInfo($params){ 

        $data=$params["data"];
        $ids=array();
        $id=!empty($data["id"])?$data["id"]:array(); 
        $products=!empty($data["products"])?$data["products"]:[];
        unset($data['products']);
        unset($data["id"]);
        $this->db->query("BEGIN");//事务开始
       
      
        
        try {
            if(empty($id)){
                $data['creator_id']=$_SESSION['LOGIN_UID'];
                $data['create_time']= date('Y-m-d H:i:s');
                $data["invoice_num"]= $billNum = $this->getBillNum(); //发票号
                if(!empty($products)){
                    $total=0;
                    foreach ($products as $key=>$val){
                        $curItem= current($val);
                        $orderNum = $this->db->get_one("dev_ordernum", "*", " status={$curItem["orderId"]} ");
                        $data['account_type']=$orderNum["zhangTao"];
                        //手册的取关单号
                        if(!empty($curItem['gd_number'])){
                            $data["invoice_num"] = $curItem['gd_number'];
                        }else{
                            $data["invoice_num"] = $billNum;
                        }
                        $id=$this->db->insert($this->tabName, $data);
                        $total=0;
                        $updateTemp=array("bill_id"=>array(),"cabinet_id"=>array(),"deliver_id"=>array(),"sales_num"=>array(),"invoice_num"=>array());

                        foreach ($val as $k=>$v){
                            $addData = array(
                                "invoice_id"=>$id,
                                "order_list_id"=>$v["id"],
                                "order_id"=>$v["orderId"],
                                "invoice_num"=>!empty($v["deliver_num"])?$v["deliver_num"]:$v["invoice_num"],  //发票数量
                                "invoice_total"=>$v["invoice_total"], 
                                "invoice_price"=>$v["invoice_price"], 
                                
                                "adjust_money"=>$v["adjust_money"], 
                                "adjust_price"=>$v["adjust_price"], 
                                
                                "exchange_rate"=>$v["exchange_rate"], 
                                "order_num"=>$v["orderNum"],
                                    
                                "freight"=>$v["freight"],
                                "insure"=>$v["insure"],
                                "fob_total"=>$v["fob_total"],
                                
                                "sales_num"=>$v["sales_num"],
                                "deliver_id"=>$v["deliver_id"],

                            );
                            if(!empty($v["productId"])){
                                $productInfo = $this->db->get_one("dev_product", "*", " id={$v["productId"]} ");
                                if(!empty($productInfo)){
                                    $hsInfo = $this->db->get_one('dev_product_hscode','*',"hs_code = '{$productInfo['hsCode']}' and hs_code_name = '{$productInfo['hsCodeName']}' ");
                                    $addData["hs_code"]=$productInfo["hsCode"];
                                    $addData["hs_name"]=$hsInfo["grade_name"];
                                }
                            }
                            $this->db->insert("dev_provebill_invoice_item", $addData);
                            
                            $total+=$v["invoice_total"];
                            
                           
                            if(!empty($v["deliver_id"])&&!in_array($v["deliver_id"], $updateTemp["deliver_id"])){
                                $tempData=$this->db->get_one(" dev_provebill as a left join dev_provebill_cabinet as b on b.bill_id=a.id ", "a.id as bill_id,b.id as cabinet_id,a.invoice_num ", " a.deliver_id={$v["deliver_id"]} ");
                                if(!empty($tempData["bill_id"])) $updateTemp["bill_id"][]=$tempData["bill_id"];
                                if(!empty($tempData["cabinet_id"])) $updateTemp["cabinet_id"][]=$tempData["cabinet_id"];
                                if(!empty($tempData["invoice_num"])&& !in_array($tempData["invoice_num"],$updateTemp["invoice_num"])) $updateTemp["invoice_num"][]=$tempData["invoice_num"];
                                
                                $updateTemp["deliver_id"][]=$v["deliver_id"];
                                $updateTemp["sales_num"][]=$v["sales_num"];
                            }
                            
                        }
                        
                        
                        
                        $ids[]=$id;
                   
                        $this->db->update($this->tabName, array(
                            "total"=>$total,
                            "total_balance"=>$total,
                            "order_num"=>implode(",", $updateTemp["invoice_num"]),
                            "bill_id"=> implode(",", $updateTemp["bill_id"]),
                            "cabinet_id"=> implode(",", $updateTemp["cabinet_id"]),
                            "deliver_id"=> implode(",", $updateTemp["deliver_id"]),
                            "sales_num"=> implode(",", $updateTemp["sales_num"]),
                        )," id={$id} ");
                    }
                   
                }   
            }else{
                if($id == "[object Object]"){
                    //抛出异常提示
                    throw new Exception("参数不正确");
                }
                $this->db->update($this->tabName, $data," id in({$id}) ");//更新单证信息
                $this->db->delete("dev_provebill_invoice_item", " invoice_id in({$id}) ");
                if(!empty($products)){
                    $totalArray=$updateTemp=array();
                    foreach ($products as $key=>$val){
                     
                        foreach ($val as $k=>$v){
                            if(empty($updateTemp[$v["invoice_id"]])){
                                $updateTemp[$v["invoice_id"]]=array("bill_id"=>array(),"cabinet_id"=>array(),"deliver_id"=>array(),"sales_num"=>array(),"invoice_num"=>array());
                            }
                            $addData = array(
                                "invoice_id"=>$v["invoice_id"],
                                "order_list_id"=>$v["id"],
                                "order_id"=>$v["orderId"],
                                "invoice_num"=>!empty($v["deliver_num"])?$v["deliver_num"]:$v["invoice_num"], 
                                "invoice_total"=>$v["invoice_total"], 
                                "invoice_price"=>$v["invoice_price"], 
                                
                                "adjust_money"=>$v["adjust_money"], 
                                "adjust_price"=>$v["adjust_price"], 
                                
                                "exchange_rate"=>$v["exchange_rate"], 
                                "order_num"=>$v["orderNum"],
                                
                                "freight"=>$v["freight"],
                                "insure"=>$v["insure"],
                                "fob_total"=>$v["fob_total"],
                                "sales_num"=>$v["sales_num"],
                                "deliver_id"=>$v["deliver_id"],
                            );
                            if(!empty($v["productId"])){
                                $productInfo = $this->db->get_one("dev_product", "*", " id={$v["productId"]} ");
                                if(!empty($productInfo)){
                                    $addData["hs_code"]=$productInfo["hsCode"];
                                    $addData["hs_name"]=$productInfo["hsCodeName"];
                                }
                            }
                            $this->db->insert("dev_provebill_invoice_item", $addData);
                            
                            $totalArray[$v["invoice_id"]]+=$v["invoice_total"];
                            
                            if(!empty($v["deliver_id"])&&!in_array($v["deliver_id"], $updateTemp[$v["invoice_id"]]["deliver_id"])){
                                $tempData=$this->db->get_one(" dev_provebill as a left join dev_provebill_cabinet as b on b.bill_id=a.id ", "a.id as bill_id,b.id as cabinet_id,a.invoice_num ", " a.deliver_id={$v["deliver_id"]} ");
                                if(!empty($tempData["bill_id"]))  $updateTemp[$v["invoice_id"]]["bill_id"][]=$tempData["bill_id"];
                                if(!empty($tempData["cabinet_id"]))  $updateTemp[$v["invoice_id"]]["cabinet_id"][]=$tempData["cabinet_id"]; 
                                if(!empty($tempData["invoice_num"])&& !in_array($tempData["invoice_num"],$updateTemp[$v["invoice_id"]]["invoice_num"])) $updateTemp[$v["invoice_id"]]["invoice_num"][]=$tempData["invoice_num"];

                                $updateTemp[$v["invoice_id"]]["deliver_id"][]=$v["deliver_id"];
                                $updateTemp[$v["invoice_id"]]["sales_num"][]=$v["sales_num"];
                            }

                        }

                    }
                } 
                
                if(!empty($totalArray)){
                    foreach ($totalArray as $key=>$val){
                        $this->db->update($this->tabName, array(
                            "total"=>$val,
                            "total_balance"=>$val,
                            "order_num"=>implode(",", $updateTemp[$key]["invoice_num"]),
                            "bill_id"=> implode(",", $updateTemp[$key]["bill_id"]),
                            "cabinet_id"=> implode(",", $updateTemp[$key]["cabinet_id"]),
                            "deliver_id"=> implode(",", $updateTemp[$key]["deliver_id"]),
                            "sales_num"=> implode(",", $updateTemp[$key]["sales_num"]),
                        )," id={$key} ");
                    }
                }
                $ids= explode(",", $id);
            }

            $this->db->query("COMMIT");//提交事务

        } catch (Exception $e) {
            $this->db->query("ROLLBACK");//事务回滚
            return array("status"=>500,"msg"=>$e->getMessage(),"data"=>array());   
        }
        

    
        return array("status"=>200,"msg"=>"更新成功！","data"=> implode(",", $ids) );
    }


    
    
   
    public function del($params){
        if(empty($params["id"])){
           return array("status"=>500,"msg"=>"参数错误！","data"=>array()); 
        }
        
        $deliverInfo=$this->db->get_one($this->tabName, "*", " id={$params["id"]} ");
        if(!empty($deliverInfo)){
            $ret= $this->db->delete($this->tabName," id={$params["id"]} "); //删除流程
            $this->db->delete("dev_provebill_invoice_item"," invoice_id={$params["id"]} "); //删除发货产品
            $this->db->delete("dev_flows"," flowType='开票流程'  and flowCaseId={$params["id"]} "); //删除流程信息
            $this->db->delete("dev_flows_step"," flowType='开票流程' and flowCaseId={$params["id"]} "); //删除流程信息
        }
        return array("status"=>200,"msg"=>"删除成功！","data"=>$ret);
    }
    
    
    //
    public function getInvoiceItems($params){
        if(empty($params["deliver_id"])&&empty($params["id"])&&empty($params["deliver_ids"])){
           return array("status"=>500,"msg"=>"参数错误！","data"=>array()); 
        }
        $params["exchange_rate"]=!empty($params["exchange_rate"])?$params["exchange_rate"]:1;
        $deliver=array();
        
        if(!empty($params["id"])){
            $retInfo=$this->getInvoiceList(array("invoice_id"=>$params["id"]));
            return array("status"=>200,"msg"=>"成功！","data"=>$retInfo);
        }
        if(!empty($params["deliver_ids"])) $params["deliver_id"]=$params["deliver_ids"];
      
        if(!empty($params["deliver_id"])){
            
            $deliver=$this->db->get_one("dev_provebill_deliver", " GROUP_CONCAT(bill_ids) as bill_ids ", " id in ({$params["deliver_id"]}) ");
            
            if(empty($deliver)){
                return array("status"=>500,"msg"=>"发货单不存在！","data"=>array()); 
            }

            if(!empty($deliver["bill_ids"])){
                $retInfo=$this->getProbillItems(array("bill_id"=>$deliver["bill_ids"],"deliver_id"=>$params["deliver_id"],"exchange_rate"=>$params["exchange_rate"])); 
            }else{
                $retInfo=$this->getDeliverItems(array("deliver_id"=>$params["deliver_id"],"exchange_rate"=>$params["exchange_rate"]));
            }
        }
        
        
        return array("status"=>200,"msg"=>"成功！","data"=>$retInfo);
    }
    

    public function getProbillItems($params) {
        $productList=$this->db->select_raw("dev_provebill_product", "*", " FIND_IN_SET(provebill_id,'{$params["bill_id"]}') "); 
        
        
        $products=$retList=$ids=array();
        foreach ($productList as $key=>$val){
            $ids[]=$val["order_list_id"];
        }
        if(empty($ids)) return false;
        $ids= implode(",", $ids);

        $orderList=$this->db->select_page_raw("dev_orderlist as a inner join dev_ordernum as b on a.orderId=b.status  ", "a.*,concat_ws('',b.num,b.`desc`) as orderNum", " a.id in({$ids}) ","",1,999);
        
         
        if(empty($orderList["data"])) return false;
        
        foreach ($orderList["data"] as $key=>$val){
            $u8Info = $this->orderSer->getU8OrdListInfo($val['u8Code'],$val['complete'],$val['orderId']);
            $val["cInvName"]=$u8Info["chmc"];
            $hsInfo = $this->getHsCode($val);
            if(empty($hsInfo)){
                $productInfo = $this->db->get_one("dev_product", "*", " id={$val["productId"]} ");
                if(!empty($productInfo)){
                    $val["hs_code"]=$productInfo["hsCode"];
                    $val["hs_name"]=$productInfo["hsCodeName"];
                }
            }else{
                $val["hs_code"]=$hsInfo["hsCode"];
                $val["hs_name"]=$hsInfo["hsCodeName"];
            }
            $products[$val["id"]] =$val;
        }
        $deliverIds= explode(",", $params["deliver_id"]);

        foreach ($productList as $key=>$val){
            
            $billInfo=$this->db->get_one("dev_provebill","*"," id={$val["provebill_id"]} ");
            
            if(!empty($billInfo["deliver_id"])){
                $deliverItem=$this->db->get_one("dev_provebill_deliver_item","*"," deliver_id={$billInfo["deliver_id"]}  and order_list_id={$val["order_list_id"]} ");
                $deliverInfo=$this->db->get_one("dev_provebill_deliver","*"," id={$billInfo["deliver_id"]} ");
            }
            
           

            $adjustMoney= floatval($val["apportion_total"])!=0?floatval($val["apportion_total"]):$val["total_1"];
            
 
            
            //调账
            $adjustBill=$this->db->get_one("dev_provebill_product_adjust", "*", " provebill_id={$val["provebill_id"]} and order_list_id={$val["order_list_id"]} and status = 1 ");
            //$adjustBill=$this->db->get_one("dev_provebill_product_adjust", "*", " provebill_id={$val["provebill_id"]} and order_list_id={$val["order_list_id"]} ");
            if(!empty($adjustBill)){
                $productList[$key]=$val=$adjustBill;
            }

            $product=$products[$val["order_list_id"]];
            

            $product["huanSuanLv"]=!empty($product["huanSuanLv"])?$product["huanSuanLv"]:1;

            $totalNum = $product["invoice_num"]=floatval($val["num"])*$product["huanSuanLv"];
            $totalManualNum = 0;
           
            $product["sales_num"]=!empty($deliverInfo["sales_num"])?$deliverInfo["sales_num"]:0;
            $product["deliver_id"]=!empty($deliverInfo["id"])?$deliverInfo["id"]:0;
            //echo $product["invoice_num"]."||";
            
            $product["deliver_num"]=!empty($deliverItem["deliver_num"])?$deliverItem["deliver_num"]:0;
            //分摊价格
            $product["invoice_total"]=floatval($val["apportion_total"])!=0?floatval($val["apportion_total"]):$val["total_1"];
            
            $product["invoice_price"]=floatval($product["invoice_total"])/$product["invoice_num"];
            
            $product["adjust_money"]=$product["invoice_total"]-$adjustMoney;
            $product["adjust_price"]=$product["adjust_money"]/$product["invoice_num"];
            
            //取新的手册信息
           if(!empty($billInfo) && !empty($billInfo['product_list'])){
           
                $productListInfo= json_decode(string::autoCharset($billInfo['product_list'],'gbk','utf-8'), true);
                if(!empty($productListInfo)){
                    foreach ($productListInfo as $key=>$value){
                            $gdNumber = $value["gd_number"];
                            foreach ($value['product_list'] as $k1=>$v1){
                                if($v1['id']==$val['order_list_id']){
                                    $product["gd_number"]= $gdNumber;
                                    //手册数量*换算率 = 发票数量
                                    $product["invoice_num"]=floatval($v1["manua_num"])*$product["huanSuanLv"];
                                    $product["deliver_num"] = $product["invoice_num"];
                                    $totalManualNum += $product["invoice_num"];
                                    //调整金额
                                    $product["adjust_money"]=$product["invoice_num"]*$product["adjust_price"];
                                    //发票金额
                                    $product["invoice_total"]=$product["invoice_num"]*$product["invoice_price"];
                                    $retList[$value["manua"]][]=$product;
                                }
                            }
                    }
                }
                $remainTotalNum = $totalNum - $totalManualNum;
                if($remainTotalNum > 0){
                    if($remainTotalNum < $totalNum){
                        $product["invoice_num"]=$remainTotalNum;
                        //调整金额
                        $product["adjust_money"]=$product["invoice_num"]*$product["adjust_price"];
                        //发票金额
                        $product["invoice_total"]=$product["invoice_num"]*$product["invoice_price"];
                        $retList["no_manua"][]=$product;
                    }elseif( doubleval($product["invoice_num"])>0){
                        if($product["deliver_num"]>0){
                            $product["invoice_num"]= $product["deliver_num"];
                        }
                        $retList["no_manua"][]=$product;
                    }               
                }

            }else{
                if( doubleval($product["invoice_num"])>0){
                    if($product["deliver_num"]>0){
                        $product["invoice_num"]= $product["deliver_num"];
                    }
                    $retList["no_manua"][]=$product;
                }
            }
            
        }

        $this->dataCompute($retList,$params["exchange_rate"]);
        return $retList;
 
    }
    
    public function getDeliverItems($params) {
        $productList=$this->db->select_raw("dev_provebill_deliver_item", "*", " deliver_id in ({$params["deliver_id"]}) "); 
        
        
        
        $product=$retList=$ids=array();
        foreach ($productList as $key=>$val){
            $ids[]=$val["order_list_id"];
        }
        if(empty($ids)) return false;
        $ids= implode(",", $ids);
        
        $orderList=$this->db->select_page_raw("dev_orderlist as a inner join dev_ordernum as b on a.orderId=b.status  ", "a.*,concat_ws('',b.num,b.`desc`) as orderNum", " a.id in({$ids}) ","",1,999);
        
        if(empty($orderList["data"])) return false;
        
        foreach ($orderList["data"] as $key=>$val){
            $u8Info = $this->orderSer->getU8OrdListInfo($val['u8Code'],$val['complete'],$val['orderId']);
            $val["cInvName"]=$u8Info["chmc"];
            
            $product[$val["id"]] =$val;
        }

        foreach ($productList as $key=>$val){
            
            
            $item=$product[$val["order_list_id"]];
            //$item["huanSuanLv"]=!empty($item["huanSuanLv"])?floatval($item["huanSuanLv"]):1;
            if(floatval($val["deliver_num"])==0)         continue;
            
          
            $deliverInfo=$this->db->get_one("dev_provebill_deliver","*"," id={$item["deliver_id"]} ");
            $item["invoice_num"]=floatval($val["deliver_num"]);
            $item["invoice_total"]=floatval($item["proPrice"])*$item["invoice_num"];
            $item["invoice_price"]=floatval($item["proPrice"]);
            
            $item["sales_num"]=!empty($deliverInfo["sales_num"])?$deliverInfo["sales_num"]:"";
            $item["deliver_id"]=!empty($deliverInfo["id"])?$deliverInfo["id"]:"";
            
            $retList["no_manua"][]=$item;
        }
        
        $this->dataCompute($retList,$params["exchange_rate"]);
        
        return $retList;
    }
    
    public function getInvoiceList($params){
        $productList=$this->db->select_raw("dev_provebill_invoice_item", "*", " invoice_id={$params["invoice_id"]} "); 
        
        $product=$retList=$ids=array();
        foreach ($productList as $key=>$val){
            $ids[]=$val["order_list_id"];
        }
        if(empty($ids)) return false;
        $ids= implode(",", $ids);
        
        $orderList=$this->db->select_page_raw("dev_orderlist as a inner join dev_ordernum as b on a.orderId=b.status left join dev_provebill_invoice_item as c on c.order_list_id=a.id and c.invoice_id={$params["invoice_id"]}  ", "a.*,concat_ws('',b.num,b.`desc`) as orderNum,c.invoice_id,c.sales_num,c.deliver_id", " a.id in({$ids}) ","",1,999);
        
        if(empty($orderList["data"])) return false;
        
        foreach ($orderList["data"] as $key=>$val){
            $u8Info = $this->orderSer->getU8OrdListInfo($val['u8Code'],$val['complete'],$val['orderId']);
            $val["cInvName"]=$u8Info["chmc"];
            $hsInfo = $this->getHsCode($val);
            if(empty($hsInfo)){
                $productInfo = $this->db->get_one("dev_product", "*", " id={$val["productId"]} ");
                if(!empty($productInfo)){
                    $val["hs_code"]=$productInfo["hsCode"];
                    $val["hs_name"]=$productInfo["hsCodeName"];
                }
            }else{
                $val["hs_code"]=$hsInfo["hsCode"];
                $val["hs_name"]=$hsInfo["hsCodeName"];
            }
            $product[$val["id"]] =$val;
        }
        
    
        foreach ($productList as $key=>$val){
            $item=$product[$val["order_list_id"]];
            $item["invoice_num"]=floatval($val["invoice_num"]);
            $item["invoice_total"]=$val["invoice_total"];
            $item["invoice_price"]=$val["invoice_price"];
            $item["adjust_price"]=$val["adjust_price"];
            $item["adjust_money"]=$val["adjust_money"];
            $item["freight"]=$val["freight"];
            $item["insure"]=$val["insure"];
            $item["fob_total"]=$val["fob_total"]; 
            $retList["no_manua"][]=$item;
        }
        
        
        $this->dataCompute($retList,$params["exchange_rate"]); 
        return $retList;
    }
    
    //产品数据计算
    public function dataCompute(&$dataInfo,$exchangeRate){
        $exchangeRate=!empty($exchangeRate)?$exchangeRate:1;
        foreach ($dataInfo as $key=>$val){
            foreach ($val as $k=>$v){
                $dataInfo[$key][$k]["invoice_price"]=$v["invoice_price"]=$v["invoice_total"]/$v["invoice_num"];
                $dataInfo[$key][$k]["exchange_rate"]=!empty($v["exchange_rate"])?$v["exchange_rate"]:$exchangeRate;
                $dataInfo[$key][$k]["local_price"]= $dataInfo[$key][$k]["exchange_rate"]*$v["invoice_price"];
                $dataInfo[$key][$k]["local_total"]= $dataInfo[$key][$k]["exchange_rate"]*$v["invoice_total"];
            }
        }
    }


    //删除操作
    public function delInvoice($params){
        if(empty($params["id"])){
            return array("status"=>500,"msg"=>"参数错误！","data"=>array()); 
        }
        
        $invoice=$this->db->get_one("dev_provebill_invoice", "*", " id={$params["id"]} ");
        if(empty($invoice["deliver_id"])){
            return array("status"=>500,"msg"=>"参数错误！","data"=>array()); 
        }
        
        $deliverIds= implode(",", $invoice["deliver_id"]); 
        $this->db->query("BEGIN");//事务开始  
        try { 
            if(count($deliverIds)>1){
                $dataList=$this->db->select_raw("dev_provebill_invoice", "*", " id={$params["id"]} ");
            }else{
                $dataList=$this->db->select_raw("dev_provebill_invoice", "*", " deliver_id in ({$invoice["deliver_id"]}) ");
            }
            
            foreach ($dataList as $key=>$val){
                $this->rebackToU8(array("id"=>$params["id"],"deliver_id"=>$val["deliver_id"]));
                $this->db->delete("dev_provebill_invoice", " id={$val["id"]} ");
                $this->db->delete("dev_provebill_invoice_item", " invoice_id={$val["id"]} ");
                //删除流程
                $flowInfo = $this->db->get_one("dev_flows", "*", " flowCaseId={$val["id"]} and flowType='开票流程' ");
                if(!empty($flowInfo)){
                    $this->db->delete("dev_flows", " id={$flowInfo["id"]} ");
                    $this->db->delete("dev_flows_step", " flowId={$flowInfo["id"]} ");
                }
                $this->db->update("dev_provebill_deliver",array("is_invoice"=>0)," id in ({$val["deliver_id"]}) ");
            }
            
            $this->db->query("COMMIT");//提交事务
        } catch (Exception $e) {
            $this->db->query("ROLLBACK");//事务回滚
            return array("status"=>500,"msg"=>$e->getMessage(),"data"=>array());   
        }
        
        return array("status"=>200,"msg"=>"成功！","data"=>'');
        
    }
    
    public function rebackToU8($params){
        $invoice=$this->db->get_one("dev_provebill_invoice", "*", " id={$params["id"]} ");
        $deliverInfo=$this->db->get_one("dev_provebill_deliver", "*", " id in ({$params["deliver_id"]}) ");
        $u8Info=$this->db->get_one("dev_config", "*"," zhangTao='{$deliverInfo["account_type"]}' ");
        $deliverItem=$this->db->get_one("dev_provebill_deliver_item", "*", " deliver_id={$deliverInfo["id"]} ");
        $ordHeader = $this->db->get_one("dev_order as a inner join dev_ordernum as b on b.status=a.id ",'a.*,b.ddLeiXing,b.orderType as num_orderType,b.desc as b_desc'," a.id={$deliverItem["order_id"]}");
        
        $ordHeader["typeInfo"]="内销";
        if(!empty($ordHeader['ddLeiXing'])&&in_array($ordHeader['ddLeiXing'], ["YW"])){
            $ordHeader["typeInfo"]="外销";
        }  
        
        $this->msdb = new mssqlDB($u8Info['ip'],array('Database'=>'MiddleBase','UID'=>'sa','PWD'=>$u8Info['pass']));
        
        $cU8Code=$invoice["cU8Code"];
        if(empty($invoice["cU8Code"])){
            $u8Invoice=$this->msdb->select_one("Tbl_YG_Invoice","*"," cCode='{$invoice["invoice_num"]}' ");
            if(!empty($u8Invoice)&& is_array($u8Invoice)){
                if(!empty($u8Invoice["cU8Code"])){
                    $cU8Code=$u8Invoice["cU8Code"];
                }else{
                    $this->msdb->delete('Tbl_YG_Invoice'," cCode='{$invoice["invoice_num"]}' ");                
                    return false;      
                }
            }else{
                return false;      
            }
         
        }
        
        $success=true;
        $tempArr=array(
            "cAccId"=>$u8Info["db3"],//账套号
            "cVouchType"=>$ordHeader["typeInfo"],//单据类型(内销、外销)
            "cU8Code"=>$cU8Code,//U8单据号
            "cType"=>"发票",//删除类型（整单/单行）
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


    public function submitInvoiceInfo($params){
        if(empty($params["id"])){
            return array("status"=>500,"msg"=>"参数错误！","data"=>array()); 
        }
        
        $ids= explode(",", $params["id"]);
        
       
        
        foreach ($ids as $key=>$val){
            $invoice=$this->db->get_one("dev_provebill_invoice", "*", " id={$val} ");
            if(empty($invoice)){
                return array("status"=>500,"msg"=>"参数错误！","data"=>array()); 
            }

            $flowId = $this->flows->add(array(
                "flowType"=>"开票流程",
                "flowCaseId"=>$val,
                "userName"=>GetUserNameByUid($_SESSION['LOGIN_UID']),
                "dCreateDate"=>date("Y-m-d H:i:s"),
            ));
            
            

            if(!empty($flowId)){ 
                $userPriv=$this->db->get_one("dev_runpriv", "*", " name='财务审核' and type='开票流程' ");

                $userId="";
                if(!empty($userPriv["user_groups"])){
                    $userGroups= string::autoCharset(json_decode(string::autoCharset($userPriv["user_groups"],'gbk','utf-8'), true),'utf-8','gbk');
                    foreach ($userGroups as $key=>$val){
                        if($val["name"]==$invoice["account_type"]) $userId=$val["uids"];
                    }
                }
                
                //$this->flows->nextStep(array("flowId"=>$flowId,"userId"=>$userId));
                	
                $this->db->update("dev_flows_step", array("prcsFlag"=>"办理完毕")," flowId='{$flowId}' and flowPrcs='填写单据' ");
                $retInfo = $this->sendMsg(array("flowId"=>$flowId));
                if($retInfo['status'] == '500'){
                    return $retInfo;
                }
            } 
        }
        
        
        return array("status"=>200,"msg"=>"成功！","data"=>array()); 
    }
    
    public function auditInvoiceInfo($params){
        if(empty($params["flow_id"])){
            return array("status"=>500,"msg"=>"参数错误！","data"=>array()); 
        }
        $this->flows->nextStep(array("flowId"=>$params["flow_id"]));
        $this->sendMsg(array("flowId"=>$params["flow_id"]));
    }


    public function sendMsg($params){
        if(empty($params["flowId"])) return false;
        $flowInfo=$this->flows->get_current_flow(array("flowId"=>$params["flowId"]));
        if(empty($flowInfo)) return false;
        $msgIds=$flowInfo["step_user_id"];
        $privInfo=$this->db->select_one("dev_runpriv","*","name='{$flowInfo["status"]}' and type='{$flowInfo["flowType"]}' and deleted=0 ");        
        if(!empty($privInfo["uids"])) $msgIds=$privInfo["uids"];
        $invoice=$this->db->select_one("dev_provebill_invoice","*"," id={$flowInfo["flowCaseId"]} ");    
        if($flowInfo["status"]=="财务审核"&&$flowInfo["prcsFlag"]=="未接收"){
            
            $this->commSer->sendMsg(array(
                "uids"=>$msgIds,
                "msg"=>"您有一单开票记录【订单号：{$invoice["order_num"]}】需要审核",
                "path"=>"erp4/view/provebill/invoicePrint/add.php?/#/id={$flowInfo["flowCaseId"]}/flow_id={$flowInfo["id"]}/opt=audit/showType=57"
            ));
        }else if($flowInfo["prcsFlag"]=="办理完毕"){
            $this->syncDeclarationInvoice($invoice["order_num"]);
            $retInfo = $this->invoiceToU8(array("id"=>$invoice["id"]));
            if(empty($retInfo)||$retInfo["status"]!="200"){
                return $retInfo; 
            }else{
                
                $this->db->update("dev_provebill_deliver", array("is_invoice"=>1)," id={$invoice["deliver_id"]} ");
                $this->commSer->sendMsg(array(
                    "uids"=>$flowInfo["beginUser"],
                    "msg"=>"您的开票记录【订单号：{$invoice["order_num"]}】已审核通过，请前往查看",
                    "path"=>"erp4/view/provebill/invoicePrint/add.php?/#/id={$flowInfo["flowCaseId"]}/flow_id={$flowInfo["id"]}/opt=detail/showType=58"
                )); 
            }
            
        }

    }
    
    public function invoiceToU8($params){
        
        if(empty($params["id"])) return array("status"=>500,"msg"=>"参数错误！","data"=>array());
        $invoice=$this->db->select_one("dev_provebill_invoice","*"," id={$params["id"]} ");
        $deliver=$this->db->get_one("dev_provebill_deliver", "*", " id in ({$invoice["deliver_id"]}) ");
        if(empty($deliver["account_type"])) return array("status"=>500,"msg"=>"账套不存在！","data"=>array());
        //根据订单号取对应的u8信息
        $config = $this->db->select_one('dev_config','*',"zhangTao='{$deliver["account_type"]}'");
        if(!$config) return array("status"=>500,"msg"=>"配置信息获取失败！","data"=>array());
        $this->msdb = new mssqlDB($config['ip'],array('Database'=>'MiddleBase','UID'=>'sa','PWD'=>$config['pass']));
        
     
        
        
        if(empty($this->msdb)){
            return array("status"=>500,"msg"=>"无法连接u8数据库！","data"=>array());
        }

        // 启动事务
        if (sqlsrv_begin_transaction($this->msdb->conn) === false) {
            return array("status"=>500,"msg"=>"启动事务失败！","data"=>array());
        }
        $invoiceItems=$this->db->select_raw("dev_provebill_invoice_item", "*", " invoice_id={$invoice["id"]} ");
        $userInfo=$this->db->get_one("user","USER_NAME", " UID={$invoice["creator_id"]} ");
        
        $success=true;
        
        $updateInfo=array(
            "cAccId"=>$config["db3"], //账套号
            "cCode"=>$invoice["invoice_num"], //发票号
            "cMemo"=>$invoice["mark"], //摘要
            "cZhuangYunGang"=>$invoice["start_port"], //装运港
            "cMuDiGang"=>$invoice["end_port"], //目的港
            "cU8FHCode"=>$invoice["sales_num"], //OA销货单号
            "cMaker"=>!empty($userInfo["USER_NAME"])?$userInfo["USER_NAME"]:"", //制单人
            "dCreateDate"=>date("Y-m-d H:i:s"), //制单日期
            
            "nFlat"=>$invoice["exchange_rate"],//汇率
            "cZhuangYunDi"=>$invoice["cabinet_address"],// 装柜地点
            "cTiDanCode"=>empty($invoice["bill_lading_num"]) ? 1 : $invoice["bill_lading_num"],	//提单号
            "cContrNo"=>$invoice['order_num'],//合同协议号
            "cIncoterms"=>$invoice['trade_type'],//贸易术语
            "cTrade"=>$invoice['cTrade'],    //贸易方式
        );
        
        
        if(!empty($invoice["invoice_date"])&&$invoice["invoice_date"]!="0000-00-00") $updateInfo["dDate"]=$invoice["invoice_date"];//发票日期
        if(!empty($invoice["account_date"])&&$invoice["account_date"]!="0000-00-00") $updateInfo["dLiZhang"]=$invoice["account_date"];//立账日期
        if(!empty($invoice["expire_date"])&&$invoice["expire_date"]!="0000-00-00") $updateInfo["dDaoQi"]=$invoice["expire_date"]; //到期日
        if(!empty($invoice["cabinet_date"])&&$invoice["cabinet_date"]!="0000-00-00") $updateInfo["dZhuangGui"]=$invoice["cabinet_date"]; //装柜日期
        if(!empty($invoice["arrival_date"])&&$invoice["arrival_date"]!="0000-00-00") $updateInfo["dJinGang"]=$invoice["arrival_date"];//进港日期
        if(!empty($invoice["export_date"])&&$invoice["export_date"]!="0000-00-00") $updateInfo["dChuKou"]=$invoice["export_date"];//出口日期
        if(!empty($invoice["pre_arrival_date"])&&$invoice["pre_arrival_date"]!="0000-00-00") $updateInfo["dYuJiDangGang"]=$invoice["pre_arrival_date"];//预计到港日期

        $ret=$this->msdb->insert("Tbl_YG_Invoice",$updateInfo);
        
        

        if($ret!=1) $success=false;
        if(!empty($invoiceItems)&&$success){
            $retInfo= $this->msdb->select_one("Tbl_YG_Invoice"," max(Id) as Id ");
            foreach ($invoiceItems as $key=>$val){
                $orderItem=$this->db->get_one("dev_orderlist", "*", "id={$val["order_list_id"]}");
               
                $temp=$this->msdb->insert("Tbl_YG_Invoices",array(
                    "Id"=>$retInfo["Id"],       //表头主键
                    "OAAutoId"=>$val["order_list_id"],//OA订单Id
                    "cInvCode"=>$orderItem["u8Code"],//存货编码
                    "iQuantity"=>$val["invoice_num"],//发票数量
                    "iTaxPrice"=>$val["invoice_price"],//发票单价（原币金额）
                    "iMoney"=> $val["invoice_total"], //发票金额
                    "iFare"=> $val["freight"], //运费
                    "iInsurance"=> $val["insure"], //保险费 
                    "adjustMoney"=>$val["adjust_money"],//调整金额
                    "cHsCode"=>$val["hs_code"],//海关编码
                    "cHsName"=>$val["hs_name"],//海关名称
                ));
             
                if($temp!=1) $success=false;
            }          
        }

       
        
        if($success){
            sqlsrv_commit($this->msdb->conn); //提交事务
        }else{
            sqlsrv_rollback($this->msdb->conn); // 事务回滚
            return array("status"=>500,"msg"=>$temp,"data"=>array());   
        }
        
        return array("status"=>200,"msg"=>"成功！","data"=>array());   
        
    }
    
    
     
    public function getBillNum(){
        $invoice_num =$invoiceInfo= "";//获取订单号
        $maxLoop=100;//最大循环数
        $loopNum=0; //循环数
        while (!empty($invoiceInfo["invoice_num"])||empty($invoice_num)) {
            if($loopNum>100) return false; //超过最大循环数返回空值
            $invoice_num = "FP".date("Y-mdHis").rand(10,100);//获取订单号   
            $invoiceInfo=$this->db->get_one("dev_provebill_invoice","*"," invoice_num='{$invoice_num}' ");
            $loopNum++;
        }
        return $invoice_num; 
    }

    protected function syncDeclarationInvoice($cCode){
        $declarationInfo=$this->db->get_one("dev_declaration_invoice","*"," Code='{$cCode}' ");
        if(empty($declarationInfo)) return false;
        if($declarationInfo["is_invoice"]!=1){
            $this->db->update("dev_declaration_invoice",array("is_invoice"=>1)," Code='{$cCode}' ");
        }
        return true;
    }

    protected function getHsCode($orderListInfo){
        $category = $orderListInfo['category'];
        $model = $orderListInfo['model'];
        $customerCode = $orderListInfo['customerCode'];
        return $this->filterHsCode($category, $model, $customerCode);
    }

    protected function filterHsCode($category, $model, $customerCode){
        if(in_array($category, ["清洁胶带"])){
            if(in_array($model, ["","无手柄"])){                   
                $val["hsCode"]="4811410000";
                $val["hsCodeName"]="SPIRAL CLEANING TAPE"; 
            }else{
                $val["hsCode"]="9603909090";
                $val["hsCodeName"]="CLEANER TAPE"; 
            }
            
            if(strstr($customerCode,"蓝色小萌主")){
                $val["hsCode"]="3919909090";
                $val["hsCodeName"]="CLOTH TAPE";
            }
            
             if(strstr($customerCode, "牛皮纸清洁胶带")){
                $val["hsCode"]="4811410000";
                $val["hsCodeName"]="TAPE";
            }
            return $val;
        }else{
            return [];
        }
    }
    

}



?>
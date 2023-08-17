<?php

/**
 * 账套信息管理
 */

//include_once '/inc/auth.inc.php';
include_once '/inc/utility_all.php';
include_once '/inc/utility_org.php';

include_once '/general/erp4/lib/dbTool/mssqlDB.class.php';
include_once '/general/erp4/lib/dbTool/mysqlDB2.class.php';

include_once '/general/erp4/service/field.ser.php';
include_once '/general/erp4/service/priv.ser.php';

include_once '/general/erp4/lib/string.class.php';


include_once '/general/erp4/service/provebill.ser.php';
include_once '/general/erp4/service/accountSet.ser.php';
include_once '/general/erp4/service/order.ser.php';
include_once '/general/erp4/service/productHscode.ser.php';
include_once '/general/erp4/service/flows.ser.php';
include_once '/general/erp4/service/sqlManage.ser.php';


class billPrintSer{

    private $db;
    private $msdb;
    private $tabName='dev_account_set';

    private $fieldSer;
    private $privSer;
    private $flows;
    
    private $orderSer;
    private $provebill;
    private $accountSet;
    private $productHscode;

    public function __construct(){
        $this->db = new mysqlDB();
        // $this->db = new mysqlDB(1);

        $this->msdb = new mssqlDB();
        //$this->msdb = new mssqlDB('D:/MYOA/logs/customer.log');

        $this->fieldSer = new fieldSer();
        $this->privSer = new privser();        
        
        $this->orderSer=new orderSer();
        $this->provebill=new provebill();
        $this->accountSet=new accountSet();
        $this->productHscode=new productHscode();
        $this->flows=new flowsSer();
        $this->sqlManage=new sqlManage();
        
        //$this->flows = array("orderChange"=>"订单变更");//流程名称定义
        //$this->steps=array("变更修改","变更审核","变更导入","导入完成");
    }
    
    /**
     * report factor
     * @param unknown $params
     * @return multitype:unknown
     */
    public function reportFactor($params){
        $billInfo=$this->provebill->getBillInfo(array("id"=>$params['bill_id']));//获取订舱详情
        $billProducts = $this->orderSer->getOrderProductList(array("provebill_id"=>$params['bill_id'],"bhz"=>0,"limit"=>999)); //获取订舱产品列表 取[data]
        
        //Break down mix products
        foreach ($billProducts['data'] as $key=>$val){
            if($val["hz"]==1){
                $hzProducts = $this->orderSer->getOrderProductList(array("hz"=>0,"bhz"=>$val["id"],"limit"=>999)); //获取混装产品
                if(!empty($hzProducts['data'])){
                    foreach ($hzProducts['data'] as $k=>$v){ 
                        $billProducts['data'][]=$v;
                    }
                }
            }
        }

        $retInfo=array();
        foreach ($billProducts['data'] as $key=>$val){
           
            if(empty($val["poUantity"])){
                unset($billProducts['data'][$key]);
                continue;
            }

            if(in_array($val['category'], ["清洁胶带"])){
                if(in_array($val['model'], ["","无手柄"])){                   
                    $billProducts['data'][$key]["hsCode"]=$val["hsCode"]="4811410000";
                    $billProducts['data'][$key]["hsCodeName"]=$val["hsCodeName"]="SPIRAL CLEANING TAPE"; 
                }else{
                    $billProducts['data'][$key]["hsCode"]=$val["hsCode"]="9603909090";
                    $billProducts['data'][$key]["hsCodeName"]=$val["hsCodeName"]="CLEANER TAPE";
                }
                
                if(strstr($val["customerCode"],"蓝色小萌主")){
                    $billProducts['data'][$key]["hsCode"]=$val["hsCode"]="3919909090";
                    $billProducts['data'][$key]["hsCodeName"]=$val["hsCodeName"]="CLOTH TAPE";
                }
                
                 if(strstr($val["customerCode"], "牛皮纸清洁胶带")){
                    $billProducts['data'][$key]["hsCode"]=$val["hsCode"]="4811410000";
                    $billProducts['data'][$key]["hsCodeName"]=$val["hsCodeName"]="TAPE";
                }
                
                
            }

            if(!empty($val["hsCodeName"])){
                $hsCodeInfo=$this->productHscode->getInfo(array("hs_code_name"=>$val["hsCodeName"]));
                if(empty($retInfo[$val["hsCodeName"]])){
                    $retInfo[$val["hsCodeName"]]=$hsCodeInfo;
                }
                
                if(!in_array($val['brand'], ['无','','/'])) $val['brand'].="（境外品牌（贴牌生产）";
                if(!in_array($val['brand'], $retInfo[$val["hsCodeName"]]['brand'])){
                    $retInfo[$val["hsCodeName"]]['brand'][]=$val['brand'];
                }

                $thick = '';
                if($val['category'] == '铝箔胶带' && !empty($val['name'])){
                    $thick = substr($val['name'],0,strpos($val['name'],'um'));
                    if(!empty($thick)){
                        $thick = '*'. $thick/1000 . 'MM';
                    }
                }
                if($val['complete'] == '半成品'){
                    $specs=(int)$val['width']."MM"."*".(int)$val['length']."M".$thick.'等';
                }else{
                    $specs=(int)$val['width']."MM"."*".(int)$val['length']."M".$thick;
                }
                if(!in_array($specs, $retInfo[$val["hsCodeName"]]['specs'])){
                    $retInfo[$val["hsCodeName"]]['specs'][]=$specs; 
                }
            } 
        }
        
        if(!empty($retInfo)){
            foreach ($retInfo as $key=>$val){
                if(empty($val["data"])) continue;
                
                $hasSpecs=false;
                $specs=!empty($retInfo[$key]["specs"])?implode("/", $retInfo[$key]["specs"]):"无";   
                foreach ($val["data"] as $k=>$v){
                    if (in_array(substr($v["name"],0,4), ["规格"])) {
                        $retInfo[$key]["data"][$k]["value"]=$specs;  
                        $hasSpecs=true;
                    }
                }
                
                if(!$hasSpecs){
                    $retInfo[$key]["data"][]=array("name"=>"规格：","value"=>$specs);
                }
               
                $brand=!empty($retInfo[$key]["brand"])?implode("/", $retInfo[$key]["brand"]):"无";  
                $retInfo[$key]["data"][]=array("name"=>"品牌：","value"=>$brand);
            }  
        }
        $retInfo["mark"]=""; 
        $markInfo = $this->msdb->select_one("Customer", "*", " cCusCode='{$billInfo["customer_code"]}' ");
        if(!empty($markInfo)&&is_array($markInfo)){
            $retInfo["mark"]=$markInfo["cCusDefine1"]; 
        }
        
        return $retInfo;
    }

    /**
     * Print invoice-related data
     * @param array $params
     * @return bool
     */
    public function printData($params) {
        $retInfo = array();
        $bill_id = !empty($params["bill_id"]) ? $params["bill_id"] : $params["bill_ids"]; // Booking ID
        $billInfo = $this->provebill->getBillInfo(array("id" => $bill_id)); // Get booking details
        $billProducts = $this->orderSer->getOrderProductList(array("provebill_id" => $bill_id, "bhz" => 0, "order_by_id" => "ASC", "limit" => 999)); // Get booking product list (data)
        $cabinetInfo = $this->db->get_one("dev_provebill_cabinet", "*", " bill_id in ({$bill_id}) ");
        $this->provebill->productHandle($billProducts["data"]);

        $accSetInfo = $this->accountSet->getInfo(array("account_type" => $billInfo["account_type"])); // Get account set information
        $accCtrInfo = array();
        if (!empty($billInfo["account_ctr_id"])) {
            $accCtrInfo = $this->sqlManage->sqlManageApi(array("unique_code" => "account_ctr_list", "id" => $billInfo["account_ctr_id"], "limit" => 1)); // Get trade company account set information
        }

        if (empty($billProducts['data'])) return false;
        $this->processBillProducts($billProducts['data']);
        $productList = $this->processProducts($billProducts['data']);
        $retInfo["accSetInfo"] = $accSetInfo; // Account set information
        $retInfo["accCtrInfo"] = $accCtrInfo; // Trade company account set information

        $retInfo["billInfo"] = $billInfo; // Booking information
        $retInfo["cabinetInfo"] = !empty($cabinetInfo) ? $cabinetInfo : array(); // Cabinet loading information
        $retInfo["billProducts"] = $billProducts; // Product list
        $retInfo["productList"] = $productList; // Product print list

        $retInfo["mark"] = "";
        $markInfo = $this->msdb->select_one("Customer", "*", " cCusCode='{$billInfo["customer_code"]}' ");
        if (!empty($markInfo) && is_array($markInfo)) {
            $retInfo["mark"] = $markInfo["cCusDefine1"];
        }

        return $retInfo;
    }


    /**
     * Init print data
     * @param type $params
     * @return type
     * @throws Exception
     */
    public function initPrintData($params){
      
        $config=config();
        $coinTypeArray=$config["coin_type"];
        $retInfo=array("status"=>200,"msg"=>"提交成功！","data"=>array()); 
        if(!empty($params['data']['flow_id'])){
            $flowInfo=$this->flows->getInfo(array("flowId"=>$params['data']['flow_id'])); 
            $retInfo=str_replace("\\\""," ",$flowInfo["data2"]);
            $retInfo=str_replace("\\"," ",$retInfo);
            echo  $retInfo;exit;
        }else{
            if($params['data']['print_type']=="7"){
                $dataInfo["data"]=$this->reportFactor($params['data']);         
                return $dataInfo;
            }else{
                $dataInfo=$this->printData($params['data']);
            }   
        }
        $accountType=empty($dataInfo["billInfo"]["account_ctr_id"])?"accSetInfo":"accCtrInfo";
        
        $sealImgs=array("xd"=>"","bg"=>"");
        
        
        $markType=substr(trim($dataInfo["billInfo"]["invoice_num"]),0,4);
        if(!empty($dataInfo["billInfo"]['account_ctr_id'])){
            switch ($dataInfo["billInfo"]['account_ctr_id']){
                case "14"://宁波盛欣                    
                    $sealImgs=array("xd"=>"seal_images/NBSXXD.png","bg"=>"seal_images/NBSXBG.png") ;
                    break;
                case "15"://江西三水商贸有限公司
                    $sealImgs=array("xd"=>"seal_images/JXSSXD.png","bg"=>"seal_images/JXSSBG.png") ;
                    break;
            }
        }else{
            switch ($markType){
                case "YWJN":
                    $sealImgs=array("xd"=>"seal_images/JXJNXD.png","bg"=>"seal_images/JXJNBG.png") ;
                    break;
                case "YWJX": 
                    $sealImgs=array("xd"=>"seal_images/JXFZXD.png","bg"=>"seal_images/JXFZBG.png") ;
                    break;
                case "YWSH":
                    $sealImgs=array("xd"=>"seal_images/SHZCXD.png","bg"=>"seal_images/SHZCBG.png") ;
                    break;
                case "JXXH":
                    $sealImgs=array("xd"=>"seal_images/SHZCXD.png","bg"=>"seal_images/SHZCBG.png") ;
                    break;
                case "JNXH":
                    $sealImgs=array("xd"=>"seal_images/JXJNXD.png","bg"=>"seal_images/JXJNBG.png") ;
                    break;
                case "JJSH":
                    $sealImgs=array("xd"=>"seal_images/SHZCXD.png","bg"=>"seal_images/SHZCBG.png") ;
                    break;
                case "JJJX":
                    $sealImgs=array("xd"=>"seal_images/JXFZXD.png","bg"=>"seal_images/JXFZBG.png") ;
                    break; 
                case "YWSD":
                    $sealImgs=array("xd"=>"seal_images/SDZCXD.png","bg"=>"seal_images/SDZCBG.png") ;
                    break; 
            }
        }
        
        if(!empty($dataInfo["productList"])){
            $list_key=0;
            foreach ($dataInfo["productList"] as $key=>$val){
                $index=0;  
                $list_key++;
                $productsList=$val["products"];
                
                $mergeList=array();
                if(!empty($val["productItem"])){
                    foreach ($val["productItem"] as $k=>$v){
                        foreach ($v as $k_1=>$v_1){                            
                            if($v_1["complete"]=="成品"||$v_1['category']=="PVC胶带"){
                                $val["productItem"][$k][$k_1]["poUantity"]=$v_1["boxNum"];
                            } 
                            
                            $warehouse=$this->db->get_one("dev_warehouse_dz", "*", "FIND_IN_SET('{$v_1["glueSty"]}',glueSty)  and FIND_IN_SET('{$v_1["category"]}',product_type) and FIND_IN_SET('{$v_1["complete"]}',complete)  ");
                            $val["productItem"][$k][$k_1]["warehouse"]=$warehouse; 
                            
                            if(empty($warehouse)){ 
                                $mergeList[md5($v_1['id'])]=$val["productItem"][$k][$k_1];
                            }else{
                                if(!empty($mergeList[md5($warehouse["warehouse_person"])])){
                                    $tempItem=$mergeList[md5($warehouse["warehouse_person"])];
                                    $tempItem["poUantity"]+= doubleval($val["productItem"][$k][$k_1]["poUantity"]);
                                    $tempItem["u8_info"]['mz']+= doubleval($val["productItem"][$k][$k_1]["u8_info"]['mz']);
                                    $tempItem["u8_info"]['tj']+= doubleval($val["productItem"][$k][$k_1]["u8_info"]['tj']);
                                    
                                    $tempItem["u8_info"]['mz']= round($tempItem["u8_info"]['mz'],2);
                                    $tempItem["u8_info"]['tj']= round($tempItem["u8_info"]['tj'],2);
                                    $tempItem["tuopan_num"]+=doubleval($val["productItem"][$k][$k_1]["tuopan_num"]);
                                    $tempItem["tuopan_weight"]+=doubleval($val["productItem"][$k][$k_1]["tuopan_weight"]);
                                    if(!in_array($val["productItem"][$k][$k_1]["orderNum"], $tempItem["order_num_array"])){
                                        $tempItem["order_num_array"][]=$val["productItem"][$k][$k_1]["orderNum"];
                                    }
                                    
                                    $tempItem["orderNum"]=implode("/n", $tempItem["order_num_array"]);
                                    $mergeList[md5($warehouse["warehouse_person"])]=$tempItem;
                                }else{
                                    $mergeList[md5($warehouse["warehouse_person"])]=$val["productItem"][$k][$k_1];
                                    $mergeList[md5($warehouse["warehouse_person"])]["order_num_array"][]=$val["productItem"][$k][$k_1]["orderNum"];
                                }
                                
                            }
                          
                        }
                    }
                }
                
                $val["mergeList"]=$mergeList;
                

                $consignor=$dataInfo[$accountType]['company_name']."(".$dataInfo[$accountType]['gate_code']."/".$dataInfo[$accountType]['company_code'].")";
                
                
                $poUnit=$val["poUnit"];
                $moneyUnit=array("CTNS"=>"ctns","ROLLS"=>"rolls","PKGS"=>"packages");
                $totalMoneyEn= "SAY U.S {$coinTypeArray[$dataInfo["billInfo"]['coin_type']]}: ".rtrim(moneyFmt::umoney($val["totalMoney"],$coinTypeArray[$dataInfo["billInfo"]['coin_type']])).".";
                
   
                $preGiveTime=date("d/M/y", strtotime($dataInfo["billInfo"]['give_time']." -5 day "));
                
                if(!empty($dataInfo["cabinetInfo"]["cabinet_time"])&&$dataInfo["cabinetInfo"]["cabinet_time"]!="0000-00-00"){
                    $preGiveTime=$dataInfo["cabinetInfo"]["cabinet_time"];
                }

                $companyName = $dataInfo[$accountType]['company_name'];

                //判断订单是不是属于三水或者盛欣
                $orderNumArr = explode(',', $dataInfo["billInfo"]["order_nums"]);
                foreach($orderNumArr as $item){
                    $orderNumInfo = $this->db->select_one("dev_ordernum","*", " concat_ws('',num,`desc`) = '{$item}' ");
                    if($orderNumInfo['cCusCode'] == "0100100150028"){
                        $sanShuiInfo = $this->db->select_one("dev_account_ctr", "*", " trade_company = '江西三水商贸有限公司' ");
                        $companyName = '江西三水商贸有限公司';
                        $accountName = $sanShuiInfo['account_name'];
                        $accountAddress = $sanShuiInfo['account_address'];
                        $sealImgs=array("xd"=>"seal_images/JXSSXD.png","bg"=>"seal_images/JXSSBG.png") ;
                        $consignor=$sanShuiInfo['company_name']."(".$sanShuiInfo['gate_code']."/".$sanShuiInfo['company_code'].")";
                    }elseif($orderNumInfo['cCusCode'] == "010020301"){
                        $shengXinInfo = $this->db->select_one("dev_account_ctr", "*", " trade_company = '宁波盛欣' ");
                        $companyName = '宁波盛欣';
                        $accountName = $shengXinInfo['account_name'];
                        $accountAddress = $shengXinInfo['account_address'];
                        $sealImgs=array("xd"=>"seal_images/NBSXXD.png","bg"=>"seal_images/NBSXBG.png") ;
                        $consignor=$shengXinInfo['company_name']."(".$shengXinInfo['gate_code']."/".$shengXinInfo['company_code'].")";
                    }
                }
                
                $retInfo["data"][$key]=array(
                    "consignor"=>$consignor, //境内发货人
                    "record_no"=>($key=="no_manua")?"":$key,//备案号
                    "consignee"=>$dataInfo["billInfo"]['customer_name'],//境外收货人
                    "transport"=>"海运",  //运输方式
                    "produce_business"=>$consignor,  //生产销售单位
                    "supervise"=>($key=="no_manua")?"一般贸易":"进料对口",  //监管方式
                    "avoid_tax"=>($key=="no_manua")?"一般贸易":"进料加工",  //征免性质
                    "contract"=>$dataInfo["billInfo"]["invoice_num"],  //合同协议号
                    "customer_short"=>$dataInfo["billInfo"]["customer_short"],  //合同协议号
                    "trade_country"=>$dataInfo["billInfo"]["customer_country"],  //贸易国
                    "target_port"=>$dataInfo["billInfo"]["end_port"],  //指运港(目的港)
                    "leave_port"=>$dataInfo["billInfo"]["start_port"],  //离境口岸(起运港)
                    "package_type"=>strtoupper($poUnit),  //包装种类    
                    "package_num"=>$val["poUnit"]=="ROLLS"?$val["totalNum"]:$val["totalBoxNum"],  //所有产品数量总和
                    
                    "coin_type"=>$coinTypeArray[$dataInfo["billInfo"]['coin_type']],
                    "weight_mz"=>!empty($val["total_mz"])?$val["total_mz"]:0,  //毛重
                    "weight_jz"=>!empty($val["total_jz"])?$val["total_jz"]:0,  //净重
                    "trade_mode"=>!empty($dataInfo["billInfo"]["trade_mode"])?$dataInfo["billInfo"]["trade_mode"]:$dataInfo["billInfo"]["trade_type"],  //成交方式
                    "poUnit"=> $poUnit,  
                    "account_name"=>empty($accountName) ? $dataInfo[$accountType]['account_name'] : $accountName ,//账套英文名称
                    "account_address"=>empty($accountAddress) ? $dataInfo[$accountType]['account_address'] : $accountAddress,//账套英文地址
                    
                    "account_type"=>$dataInfo["billInfo"]["account_type"],//账套类型
                    "account_ctr_id"=>$dataInfo["billInfo"]["account_ctr_id"],//贸易公司ID
                    
                    
                    "seal_img_xd"=>$sealImgs["xd"],//箱单发票印章
                    "seal_img_bg"=>$sealImgs["bg"],//报关发票印章
                    
                    "marks_nos"=>$dataInfo['billInfo']['marks_nos'],
                    "customer_name"=>$dataInfo["billInfo"]['customer_name'],
                    "give_time"=> $dataInfo["billInfo"]['give_time'],
                    "cabinet_time"=>$dataInfo["cabinetInfo"]["cabinet_time"],
                    "pre_give_time"=>$preGiveTime,
                    "trailer_info"=> $dataInfo["cabinetInfo"]['trailer_info'],
                    "end_port"=>$dataInfo["billInfo"]['end_port'],
                    "start_port"=>$dataInfo["billInfo"]['start_port'],
                    "invoice_num"=>$dataInfo["billInfo"]['invoice_num'],
                    "pay_type"=>$dataInfo["billInfo"]['pay_type'],
                    "customer_address"=>$dataInfo["billInfo"]['customer_address'],
                    "totalMoney"=>($params['data']['adjust_type']==1&&$list_key==1)?$val["totalMoney"]+$dataInfo["billInfo"]['adjust_money']:$val["totalMoney"],
                    "totalBoxNum"=> $val["totalBoxNum"],
                    "totalMoneyEn"=> $totalMoneyEn, 
                    "totalNum"=> $val["totalNum"],
                    "totalNumEn"=> "PACKED IN ".rtrim(moneyFmt::umoney($val["totalBoxNum"],"rmb",$moneyUnit[$poUnit])).".",//strtolower($val["poUnit"])
                    "count"=> $val["count"],
                    "total_tuopan_num"=> $val["total_tuopan_num"],
                    "total_tuopan_weight"=> $val["total_tuopan_weight"],
                    "trade_type"=>$dataInfo["billInfo"]['trade_type'],
                    "cabinet_num_str"=>$dataInfo["billInfo"]['cabinet_num_str'],
                    "company_name"=>$companyName,
                    "box_address"=>$dataInfo[$accountType]['box_address'],
                    "entrust_man"=>$dataInfo[$accountType]['entrust_man'],
                    "entrust_tel"=>$dataInfo[$accountType]['entrust_tel'],
                    "entrust_fax"=>$dataInfo[$accountType]['entrust_fax'],
                    "address"=>$dataInfo[$accountType]['address'],
                    "zip_code"=>$dataInfo[$accountType]['zip_code'],
                    "adjust_type"=>(!empty($params['data']['adjust_type'])&&$list_key==1)?$params['data']['adjust_type']:0,
                    "adjust_name"=>$dataInfo["billInfo"]['adjust_name'],
                    "adjust_money"=>$dataInfo["billInfo"]['adjust_money'],
                    "adjust_money_list"=>string::autoCharset(json_decode(string::autoCharset($dataInfo["billInfo"]["adjust_bill"],'gbk','utf-8'),true),'utf-8','gbk'),//调账详情
                    "products"=>$productsList,
                    "payment"=>$dataInfo["billInfo"]['payment'],
                    "mark"=>$dataInfo['mark'],
                    "productItem"=>$val['productItem'],
                    "mergeList"=>$val['mergeList'],
                    "special_need_content"=> $dataInfo["billInfo"]["special_need"] == "是" ? $dataInfo["billInfo"]['special_need_content'] :  "",

                    "gd_number" => ($key=="no_manua")?$dataInfo["billInfo"]['invoice_num']: $val['gd_number'],//获取关单号

                );
             
            }
        }    
        return $retInfo;
    }

    /**
     * Process bill products
     * @param array $billProducts
     * @return array
     */
    private function processBillProducts(&$billProducts) {
        // Check if the array is not empty
        if (!empty($billProducts['data'])) {
            foreach ($billProducts['data'] as $key => $val) {
                // Set default values for poPrice and proPrice
                $val["poPrice"] = $val["poPrice1"];
                $val["proPrice"] = $val["proPrice1"];
            
                // Choose appropriate poPrice value
                if (doubleval($val["po_price"]) > 0) {
                    $val["poPrice"] = $val["po_price"];
                } elseif ($val['frignPrice'] != 0) {
                    $val["poPrice"] = $val['frignPrice'];
                }
            
                // Calculate apportion_total
                $poPrice = !empty($val["po_price"]) ? $val["po_price"] : $val["poPrice"];
                $val["apportion_total"] = floatval($poPrice * $val["num"] + $val["apportion_price"]);
            
                // Set a default value for huanSuanLv if empty
                if (empty($val['huanSuanLv'])) {
                    $val['huanSuanLv'] = 1;
                }
            
                // Remove items with empty poUantity and continue
                if (empty($val["poUantity"])) {
                    unset($billProducts['data'][$key]);
                    continue;
                }
            
                // Set isAll based on poUantity and num
                $val["isAll"] = $isAll = ($val["poUantity"] <= $val["num"]);
            
                // Set poUantity to num
                $val["poUantity"] = $val["num"];
            
                // Set poPrice and total based on frignPrice
                $val["poPrice"] = ($val['frignPrice'] == 0) ? $val['poPrice'] : $val['frignPrice'];
                $val["total"] = ($val['frignTotal'] == 0) ? $val['total'] : $val['frignTotal'];
            
                if ($val["hz"] == 1) {
                    // Calculate hzNumSum if empty
                    if (empty($val["hzNumSum"])) {
                        $val["hzNumSum"] = array_sum(array_filter(explode(",", $val["hzNum"])));
                    }
            
                    // Update u8_info values
                    $u8Info = $this->orderSer->getU8OrdListInfo($val['u8Code'], $val['complete'], $val['orderId']);
                    $u8Info['mz'] = (floatval($val["c_dwmz"]) != 0) ? floatval($val["c_dwmz"]) : floatval($u8Info['mz']);
                    $u8Info['jz'] = (floatval($val["c_dwjz"]) != 0) ? floatval($val["c_dwjz"]) : floatval($u8Info['jz']);
                    $u8Info['dwtj'] = (floatval($val["c_dwtj"]) != 0) ? floatval($val["c_dwtj"]) : floatval($u8Info['dwtj']);
                    $val['u8_info']['dwxs'] = ($val["complete"] == "半成品") ? $u8Info['dwxs'] : 1;
                    $val["u8_info"] = $u8Info;
            
                    // Calculate boxNum based on conditions
                    if (empty($val["box_num"])) {
                        $val["boxNum"] = ceil(round($val['num']) * $val['huanSuanLv'] / floatval($val['onum']));
                    } else {
                        $val["boxNum"] = $val["box_num"];
                    }
            
                    // Get and set hsCode and hsCodeName if hzProducts exist
                    $hzProducts = $this->orderSer->getOrderProductList(array("hz" => 0, "bhz" => $val["id"], "limit" => 999));
                    if (!empty($hzProducts['data'])) {
                        $val["hsCode"] = $hzProducts['data'][0]["hsCode"];
                        $val["hsCodeName"] = $hzProducts['data'][0]["hsCodeName"];
                    }
                }
                // Calculate tuopan_total
                $tupanTotal["tuopan_num"] += round($val["tuopan_num"]);
                $tupanTotal["tuopan_weight"] += round($val["tuopan_weight"]);
                
                // Set English description
                $val["en_desc"] = $val["podesc"] . $val["poEdesc"];
            
                // Process poUnitOne
                $val['poUnitOne'] = preg_replace('/(\w+)([S])/i', '${1}', $val['poUnit']);
            
                // Get U8 info
                $val["u8_info"] = $this->orderSer->getU8OrdListInfo($val['u8Code'], $val['complete'], $val['orderId']);
                
                // Handle missing U8 info values
                $missingValues = ['mz', 'jz', 'dwtj'];
                foreach ($missingValues as $value) {
                    if ($val["u8_info"][$value] == '未获取') {
                        $val["u8_info"][$value] = 0.00;
                    }
                }
            
                // Update U8 info values with custom or default values
                $customValues = ['mz' => 'c_dwmz', 'jz' => 'c_dwjz', 'dwtj' => 'c_dwtj'];
                foreach ($customValues as $key => $customValue) {
                    $customVal = floatval($val[$customValue]);
                    $defaultVal = floatval($val["u8_info"][$key]);
                    $val["u8_info"][$key] = ($customVal != 0) ? $customVal : $defaultVal;
                }
            
                // Set u8_info values
                $val['u8_info']['info_type'] = !empty($val["c_dwmz"]) ? "客户要求值" : $val['u8_info']['info_type'];
                $val['u8_info']['dwxs'] = empty($val["c_dwmz"]) ? $val['u8_info']['dwxs'] : 1;
                $val['u8_info']['dwxs'] = $val["complete"] == "半成品" ? $val['u8_info']['dwxs'] : 1;
            
                // Fetch HSCode details
                $hsCodeInfo = [];
                if (!empty($val["hsCodeName"])) {
                    $hsCodeInfo = $this->productHscode->getInfo(["hs_code_name" => $val["hsCodeName"]]);
                }
            
                // Calculate tuopan_weight
                $tuopan = $this->sqlManage->sqlManageApi(["unique_code" => "tuopan_list", "tpName" => $val['tuoPan'], "limit" => 1]);
                $tuopan["weight"] = !empty($tuopan["weight"]) ? $tuopan["weight"] : 20;
                $val['tuopan_weight'] = $val["tuopan_num"] * $tuopan["weight"];
            
                // Create separate arrays for products with and without manuals
                $nomanuaInfo = $manuaInfo = $val;
            
                if (!empty($nomanuaInfo['poUantity'])) {
                    if ($nomanuaInfo["poUantity"] < $nomanuaInfo['num'] / 2) {
                        $nomanuaInfo["tuopan_num"] = 0;
                        $nomanuaInfo['tuopan_weight'] = 0;
                    }
                    $manualArray["no_manua"]["productList"][] = $nomanuaInfo;
                }
                // Update the modified value in the original array
                $billProducts['data'][$key] = $val;
            }            
        }else{
            $billProducts['data'] = array();
        }
        return $billProducts;
    }

    /**
     * Calculate the total price of the product
     * @param $val
     * @return float|int
     */
    function calculateApportionTotal($val) {
        $poPrice = floatval($val['po_price']) > 0 ? $val['po_price'] : $val['poPrice'];
        return floatval($poPrice * $val['num'] + $val['apportion_price']);
    }

    /**
     * Process the product list
     * @param $manualArray
     * @return array
     */
    private function processProducts($manualArray) {
        $productList = [];
        foreach ($manualArray as $k => $v) {
            $productList[$k] = $this->initializeProductList();
            $typeArray = ["complete" => [], "tuoPan" => [], "category" => []];
            foreach ($v["productList"] as $key => $val) {
                if(!empty($val["zs_mz"])&&$val["zs_mz"]!='.00'&&empty($val["c_dwmz"])) $val["u8_info"]['mz']=$val["zs_mz"];//取实际毛重
                if(!empty($val["zs_jz"])&&$val["zs_jz"]!='.00'&&empty($val["c_dwjz"])) $val["u8_info"]['jz']=$val["zs_jz"];//取实际净重
                
                if(!empty($val["zs_dwmz"])&&empty($val["c_dwmz"])) $val["u8_info"]['dwmz']=$val["zs_dwmz"];//取实际单位毛重
                
                if(!empty($val["zs_dwjz"])&&empty($val["c_dwjz"])) $val["u8_info"]['dwjz']=$val["zs_dwjz"];//取实际单位净重
                
                if(!in_array($val['complete'], $typeArray['complete'])&&!empty($val['complete'])){
                    $typeArray['complete'][]=$val['complete'];
                }

                if(!in_array($val['tuoPan'], $typeArray['tuoPan'])&&!empty($val['tuoPan'])){
                    $typeArray['tuoPan'][]=$val['tuoPan'];
                }
                
                 if(!in_array($val['category'], $typeArray['category'])&&!empty($val['category'])){
                    $typeArray['category'][]=$val['category'];
                }
                $hsInfo=$this->db->get_one("dev_product_hscode", "*", " hs_code='{$val["hsCode"]}' and hs_code_name='{$val["hsCodeName"]}' ");
                $val['color']=!empty($hsInfo["color"])?str_replace("#","",$hsInfo["color"]):""; 
            }
            $productList[$k]['poUnit'] = $this->calculatePoUnit($typeArray);
        }

        return $productList;
    }

    /**
     * Initialize the product list
     * @return array
     */
    private function initializeProductList() {
        return [
            "count" => 0,
            "totalMoney" => 0,
            "total_mz" => 0,
            "total_jz" => 0,
            "total_tj" => 0,
            "totalNum" => 0,
            "totalBoxNum" => 0,
            "total_tuopan_num" => 0,
            "total_tuopan_weight" => 0,
            "poUnit" => "",
            "products" => [],
            "productItem" => []
        ];
    }

    /**
     * Calculate poUnit
     * @param $typeArray
     * @return string
     */
    private function calculatePoUnit($typeArray) {
        if (count($typeArray["complete"]) == 1 && $typeArray["complete"][0] == "成品") {
            return "CTNS";
        } elseif (count($typeArray["complete"]) == 1 && $typeArray["complete"][0] == "半成品") {
            $poUnit = (count($typeArray["category"]) > 0 && in_array("PVC胶带", $typeArray["category"])) ? "PKGS" : "ROLLS";
            if ($poUnit === "ROLLS" && count($typeArray["category"]) == 1 && $typeArray["category"][0] == "PVC胶带") {
                return "CTNS";
            }
            return $poUnit;
        } else {
            return "PKGS";
        }
    }
}


?>
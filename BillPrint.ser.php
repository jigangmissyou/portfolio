<?php

/**
 * ������Ϣ����
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
        
        //$this->flows = array("orderChange"=>"�������");//�������ƶ���
        //$this->steps=array("����޸�","������","�������","�������");
    }
    
    public function reportFactor($params){
        $billInfo=$this->provebill->getBillInfo(array("id"=>$params['bill_id']));//��ȡ��������
        $billProducts = $this->orderSer->getOrderProductList(array("provebill_id"=>$params['bill_id'],"bhz"=>0,"limit"=>999)); //��ȡ���ղ�Ʒ�б� ȡ[data]
        
        //��װ��Ʒ���
        foreach ($billProducts['data'] as $key=>$val){
            if($val["hz"]==1){
                $hzProducts = $this->orderSer->getOrderProductList(array("hz"=>0,"bhz"=>$val["id"],"limit"=>999)); //��ȡ��װ��Ʒ
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

            if(in_array($val['category'], ["��ེ��"])){
                if(in_array($val['model'], ["","���ֱ�"])){                   
                    $billProducts['data'][$key]["hsCode"]=$val["hsCode"]="4811410000";
                    $billProducts['data'][$key]["hsCodeName"]=$val["hsCodeName"]="SPIRAL CLEANING TAPE"; 
                }else{
                    $billProducts['data'][$key]["hsCode"]=$val["hsCode"]="9603909090";
                    $billProducts['data'][$key]["hsCodeName"]=$val["hsCodeName"]="CLEANER TAPE";
                }
                
                if(strstr($val["customerCode"],"��ɫС����")){
                    $billProducts['data'][$key]["hsCode"]=$val["hsCode"]="3919909090";
                    $billProducts['data'][$key]["hsCodeName"]=$val["hsCodeName"]="CLOTH TAPE";
                }
                
                 if(strstr($val["customerCode"], "ţƤֽ��ེ��")){
                    $billProducts['data'][$key]["hsCode"]=$val["hsCode"]="4811410000";
                    $billProducts['data'][$key]["hsCodeName"]=$val["hsCodeName"]="TAPE";
                }
                
                
            }

            if(!empty($val["hsCodeName"])){
                $hsCodeInfo=$this->productHscode->getInfo(array("hs_code_name"=>$val["hsCodeName"]));
                if(empty($retInfo[$val["hsCodeName"]])){
                    $retInfo[$val["hsCodeName"]]=$hsCodeInfo;
                }
                
                if(!in_array($val['brand'], ['��','','/'])) $val['brand'].="������Ʒ�ƣ�����������";
                if(!in_array($val['brand'], $retInfo[$val["hsCodeName"]]['brand'])){
                    $retInfo[$val["hsCodeName"]]['brand'][]=$val['brand'];
                }

                $thick = '';
                if($val['category'] == '��������' && !empty($val['name'])){
                    //��ȡname�е�um֮ǰ������
                    $thick = substr($val['name'],0,strpos($val['name'],'um'));
                    if(!empty($thick)){
                        $thick = '*'. $thick/1000 . 'MM';
                    }
                }
                if($val['complete'] == '���Ʒ'){
                    $specs=(int)$val['width']."MM"."*".(int)$val['length']."M".$thick.'��';
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
                $specs=!empty($retInfo[$key]["specs"])?implode("/", $retInfo[$key]["specs"]):"��";   
                foreach ($val["data"] as $k=>$v){
                    if (in_array(substr($v["name"],0,4), ["���"])) {
                        $retInfo[$key]["data"][$k]["value"]=$specs;  
                        $hasSpecs=true;
                    }
                }
                
                if(!$hasSpecs){
                    $retInfo[$key]["data"][]=array("name"=>"���","value"=>$specs);
                }
               
                $brand=!empty($retInfo[$key]["brand"])?implode("/", $retInfo[$key]["brand"]):"��";  
                $retInfo[$key]["data"][]=array("name"=>"Ʒ�ƣ�","value"=>$brand);
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
     * ��ӡ��Ʊ�������
     * @param type $params
     * @return boolean
     */
    public function printData($params){

        $retInfo=array();        
        $bill_id=!empty($params["bill_id"])?$params["bill_id"]:$params["bill_ids"]; //���յ�ID
        $print_type=$params["print_type"]; //��������
        $price_type=$params["price_type"]; //�۸����ͣ�1.�ܼۣ�2.��̯�۸�
        $manua_type=$params["manua_type"]; //�Ƿ��ֲ��ӡ
        $adjust_type=!empty($params["adjust_type"])?$params["adjust_type"]:0; //�Ƿ����
        $bill_adjust=!empty($params["bill_adjust"])?$params["bill_adjust"]:1;
        
        
    
        $billInfo=$this->provebill->getBillInfo(array("id"=>$bill_id));//��ȡ��������
        $billProducts = $this->orderSer->getOrderProductList(array("provebill_id"=>$bill_id,"bhz"=>0,"order_by_id"=>"ASC","limit"=>999)); //��ȡ���ղ�Ʒ�б� ȡ[data]
        $cabinetInfo=$this->db->get_one("dev_provebill_cabinet", "*", " bill_id in ({$bill_id}) ");
        $deliverInfo=$this->db->get_one("dev_provebill_deliver", "*", " bill_ids in ({$bill_id}) ");
        if(!empty($deliverInfo)){
            //�жϷ����������ǲ������˻���
            foreach($billProducts['data'] as $k=>$v){
                $rebackDeliverInfo = $this->db->get_one("dev_reback_deliver_item", "sum(sl) as sl, sum(je) as je, sum(js) as js", " deliver_id = {$deliverInfo['id']} and order_list_id =  {$v['id']}");
                if(!empty($rebackDeliverInfo)){
                    //������
                    $outBoxNum = $v['num'] / $v['box_num'];
                    //ʵ�ʶ�������
                    $billProducts['data'][$k]['num'] = $v['num'] - $rebackDeliverInfo['js']*$outBoxNum;
                    //װ����
                    $billProducts['data'][$k]['box_num'] = $v['box_num'] - $rebackDeliverInfo['js'];
                    //��̯�۸�
                    $billProducts['data'][$k]['apportion_price'] = $billProducts['data'][$k]['apportion_price'] * $billProducts['data'][$k]['num'] / $v['num'];
                    //�������Ҳ���ű仯
                    //��̯��۸�
                    $billProducts['data'][$k]['apportion_total'] = $billProducts['data'][$k]['apportion_total'] * $billProducts['data'][$k]['num'] / $v['num'];
                    //�ܼ�
                    $billProducts['data'][$k]['total_1'] = $billProducts['data'][$k]['total_1'] * $billProducts['data'][$k]['num'] / $v['num'];
                    //��ʵë��
                    $billProducts['data'][$k]['zs_mz'] = $billProducts['data'][$k]['zs_mz'] * $billProducts['data'][$k]['num'] / $v['num'];
                    //��ʵ����
                    $billProducts['data'][$k]['zs_jz'] = $billProducts['data'][$k]['zs_jz'] * $billProducts['data'][$k]['num'] / $v['num'];
                    //���
                    $billProducts['data'][$k]['tj_1'] = $billProducts['data'][$k]['tj_1'] * $billProducts['data'][$k]['num'] / $v['num'];
                }
                
            }
        }
        
        $this->provebill->productHandle($billProducts["data"]);



        $accSetInfo=$this->accountSet->getInfo(array("account_type"=>$billInfo["account_type"])); //��ȡ������Ϣ
        $accCtrInfo=array();
        if(!empty($billInfo["account_ctr_id"])){
           $accCtrInfo=$this->sqlManage->sqlManageApi(array("unique_code"=>"account_ctr_list","id"=>$billInfo["account_ctr_id"],"limit"=>1));   //��ȡó�׹�˾������Ϣ 
        }
        
       
    
        
        if(empty($billProducts['data'])) return false;
        $poUnit=$billProducts['data'][0]["poUnit"];
        
       
    
        //���յ�������ֵ
        if($bill_adjust==2){  
            $fieldArray=["is_apportion","apportion_price","apportion_total","num",'box_num',"mz_1","jz_1","tj_1","total_1","po_price"];
            foreach ($billProducts['data'] as $key=>$val){
                if(empty($val['provebill_id']))continue;
                $adjustInfo=$this->db->get_one("dev_provebill_product_adjust", "*", " order_list_id={$val['id']} and provebill_id={$val['provebill_id']} order by id ASC ");
                if(empty($adjustInfo))   continue;
                foreach ($fieldArray as $k=>$v){
                    $billProducts['data'][$key][$v]=$adjustInfo[$v];
                }
            }
        }
        
        
        
        
        
        //�ּ��װ��Ʒ�������װ��Ʒ�۸�ë����
        foreach ($billProducts['data'] as $key=>$val){

            $billProducts['data'][$key]["poPrice"]=$val["poPrice"]=$val["poPrice1"];
            $billProducts['data'][$key]["proPrice"]=$val["proPrice"]=$val["proPrice1"];
            
            $billProducts['data'][$key]["poPrice"]=$val["poPrice"]= doubleval($val["po_price"])>0?$val["po_price"]:$val["poPrice"] ;
          
            
            $billProducts['data'][$key]["poPrice"]=$val['poPrice']= $val['frignPrice']==0?$val['poPrice']:$val['frignPrice']; //�����������
   
            $poPrice=!empty($val["po_price"])?$val["po_price"]:$billProducts['data'][$key]["poPrice"];
            $billProducts['data'][$key]["apportion_total"]=$val["apportion_total"]= floatval($poPrice*$val["num"]+$val["apportion_price"]);
            
            
            //echo $val["poPrice"]=$val["poPrice1"]."||";
            
            if(empty($val['huanSuanLv'])) $billProducts['data'][$key]["huanSuanLv"]=$val['huanSuanLv']=1;
            
            if(empty($val["poUantity"])){
                unset($billProducts['data'][$key]);
                continue;
            }
            
           
       
            
            $billProducts['data'][$key]["isAll"]=$val["isAll"] =$isAll=false; 
            if($val["poUantity"]<=$val["num"]){
                $billProducts['data'][$key]["isAll"]=$val["isAll"]=$isAll=true;
            }
             

            $billProducts['data'][$key]["poUantity"]=$val["poUantity"]=$val["num"];//������
            
          
      
       
            
            //$billProducts['data'][$key]["poPrice"]=$val['poPrice']= $val['po_price']==0?$val['poPrice']:$val['po_price']; //�����������
            
            $billProducts['data'][$key]["poPrice"]=$val['poPrice']= $val['frignPrice']==0?$val['poPrice']:$val['frignPrice']; //�����������
            $billProducts['data'][$key]["total"]=$val['total']= $val['frignTotal']==0?$val['total']:$val['frignTotal']; //��������ܼ�
              
      
            if($val["hz"]==1){
                if(empty($val["hzNumSum"])){
                    $billProducts['data'][$key]["hzNumSum"] = $val["hzNumSum"] = array_sum(array_filter(explode(",", $val["hzNum"]) ));
                }
                
                $billProducts['data'][$key]["u8_info"]=$val["u8_info"]= $this->orderSer->getU8OrdListInfo($val['u8Code'],$val['complete'],$val['orderId']);
                
                
                
                
                $billProducts['data'][$key]["u8_info"]['mz']=$val["u8_info"]["mz"]=floatval($val["c_dwmz"])!=0?floatval($val["c_dwmz"]):floatval($val["u8_info"]['mz']);
                $billProducts['data'][$key]["u8_info"]['jz']=$val["u8_info"]["jz"]=floatval($val["c_dwjz"])!=0?floatval($val["c_dwjz"]):floatval($val["u8_info"]['jz']);
                $billProducts['data'][$key]["u8_info"]['dwtj']=$val["u8_info"]["dwtj"]=floatval($val["c_dwtj"])!=0?floatval($val["c_dwtj"]):floatval($val["u8_info"]['dwtj']);

               
                
                $val['u8_info']['dwxs']=$val["complete"]=="���Ʒ"?$val['u8_info']['dwxs']:1;
                
                if(empty($val["box_num"])){
                    $billProducts['data'][$key]["boxNum"]=$val["boxNum"]= ceil(round($val['num'])*$val['huanSuanLv']/floatval($val['onum'])); //�Զ�������
                }else{
                    $billProducts['data'][$key]["boxNum"]=$val["boxNum"]=$val["box_num"];
                }
                
                
     
                $hzProducts = $this->orderSer->getOrderProductList(array("hz"=>0,"bhz"=>$val["id"],"limit"=>999)); //��ȡ��װ��Ʒ
                
                
                $hzPrice=$price_type=="2"&&$val['is_apportion']?$val['apportion_total']:$billProducts['data'][$key]["total_1"];
                
               
                
                if(!empty($hzProducts['data'])){
                    
                    $billProducts['data'][$key]["hsCode"]=$hzProducts['data'][0]["hsCode"];
                    $billProducts['data'][$key]["hsCodeName"]=$hzProducts['data'][0]["hsCodeName"];
 
                    if($val['poUnit']!="SETS"){
                        
                        $unitProRoll=$val['proUantity']/$val['hzNumSum'];//��λ��װ����
                        
                        
                        if(empty($val["hzNumSum"])){
                            $unitProRoll=1;
                        }
                        
                        
                        
                        $allRolls=0;
                        $billProducts['data'][$key]["complete"]=$hzProducts['data'][0]["complete"];
                        if(in_array($print_type, ['2','4','6','5'])){

                            
                            
                            unset($billProducts['data'][$key]);
                            
                            
                            //$hzProducts['data'][$k]["proUantity"]=$v["proUantity"]=$v["poUantity"];

                            foreach ($hzProducts['data'] as $k=>$v){
                                $allRolls+=$v["proUantity"];
                            } 
                            
                            
                            
                            /*$hzMethodArray=array("hzids"=>array_filter(explode(",", $val['hzids'])),"hzNum"=>array_filter(explode(",", $val['hzNum'])));
                            $hzMethod=array();
                            if(!empty($hzMethodArray["hzids"])){
                                foreach ($hzMethodArray["hzids"] as $k=>$v){
                                    $hzMethod[$v]=$hzMethodArray["hzNum"][$k];
                                }
                            }*/
                            
                            
                            $total_box_num=0;
                            //��װ��Ʒ����̯
                            foreach ($hzProducts['data'] as $k=>$v){
                                
                                $hzProducts['data'][$k]["poPrice"]=$v['poPrice']= $v['po_price']==0?$v['poPrice']:$v['po_price']; //�����������
                                $hzProducts['data'][$k]["poPrice"]=$v['poPrice']= $v['frignPrice']==0?$v['poPrice']:$v['frignPrice']; //�����������
                                $hzProducts['data'][$k]["total"]=$v['total']= $v['frignTotal']==0?$v['total']:$v['frignTotal']; //��������ܼ�
                                
                                $hzProducts['data'][$k]["total_1"]=$hzPrice*($v["proUantity"]/$allRolls);
                                //$hzProducts['data'][$k]["boxNum"]=$val["poUantity"]*($v["proUantity"]/$allRolls);

                                $hzProducts['data'][$k]["poPrice"]=$hzPrice*($v["proUantity"]/$allRolls)/$v["poUantity"];
                                
        
                                $hzProducts['data'][$k]["poUnit"]="ROLLS";
                                $hzProducts['data'][$k]["boxNum"]=0;
                                
            
                                if($k==0) {    
                                    $hzProducts['data'][$k]["boxNum"]=$val["boxNum"];
                                    $hzProducts['data'][$k]["tuopan_num"]=$val["tuopan_num"];
                                    $hzProducts['data'][$k]["tuopan_weight"]=$val["tuopan_weight"];     
                                } 
      
                                if(!empty($v["huanSuanLv"])){
                                    $hzProducts['data'][$k]["boxNum"]=round($val["boxNum"]*($v['huanSuanLv']/$val['hzNumSum']));
                                    $total_box_num+=$hzProducts['data'][$k]["boxNum"];
                                }else if(!empty($v["onum"])){
                                    $hzProducts['data'][$k]["boxNum"]=round($val["boxNum"]*($v['onum']/$val['hzNumSum']));
                                    $total_box_num+=$hzProducts['data'][$k]["boxNum"];
                                }
                                
                                //$hzProducts['data'][$k]["boxNum"] = intval($hzProducts['data'][$k]["boxNum"]);
                               
                                
                                
                                $hzProducts['data'][$k]["manua"]=$val["manua"];

                                if(!empty($val["hzNumSum"])){
                                    $hzProducts['data'][$k]["hzPoUantity"]=(floatval($val["poUantity"])/floatval($val["hzNumSum"])*(floatval($v["proUantity"])/floatval($unitProRoll)));
                                    $hzProducts['data'][$k]["hzBoxNum"]=(floatval($val["boxNum"])/floatval($val["hzNumSum"])*(floatval($v["proUantity"])/floatval($unitProRoll)));
                                    $hzProducts['data'][$k]["manua_num"]=(floatval($val["manua_num"])/floatval($val["hzNumSum"])*(floatval($v["proUantity"])/floatval($unitProRoll)));
                                    
                                    
                                }else{
                                    $hzProducts['data'][$k]["hzPoUantity"]=$v["proUantity"];
                                    $hzProducts['data'][$k]["hzBoxNum"]=$v["boxNum"];
                                    $hzProducts['data'][$k]["manua_num"]=$val["manua_num"];
                                }
                        
                                $hzProducts['data'][$k]["manua_box"]=$hzProducts['data'][$k]["manua_num"]*$val['huanSuanLv']/floatval($val['onum']);

                                $hzProducts['data'][$k]["hz_u8_info"]=$val["u8_info"];
                    
                            }
                            
                            
                            
                            
                            //������ֵ
                            $hzProducts['data'][0]["boxNum"]+=$val["boxNum"]-$total_box_num;
                            
                            
                        }
                        
                        foreach ($hzProducts['data'] as $k=>$v){ 
                            $billProducts['data'][]=$v;
                        }
                        
                    }
                    
                    
   
                    
                }
                
            }
            
           
         
            
        }
        

        $apportion_total=0;
        
     
        
           
     
      
        //������������
        $tupanTotal=array("tuopan_num"=>0,"tuopan_weight"=>0);
        
       
        //�ֲ�������ݼ���
        $manualArray=array();
        foreach ($billProducts['data'] as $key=>$val){
             

            
            $tupanTotal["tuopan_num"]+=round($val["tuopan_num"]);
            $tupanTotal["tuopan_weight"]+=round($val["tuopan_weight"]);
            
         
            
            if(in_array($val['category'], ["��ེ��"])){
                if(in_array($val['model'], ["","���ֱ�"]) ){   
                    $billProducts['data'][$key]["hsCode"]=$val["hsCode"]="4811410000";
                    $billProducts['data'][$key]["hsCodeName"]=$val["hsCodeName"]="SPIRAL CLEANING TAPE";   
                }else{
                    $billProducts['data'][$key]["hsCode"]=$val["hsCode"]="9603909090";
                    $billProducts['data'][$key]["hsCodeName"]=$val["hsCodeName"]="CLEANER TAPE";
                }
                
                if(strstr($val["customerCode"], "��ɫС����")){
                    $billProducts['data'][$key]["hsCode"]=$val["hsCode"]="3919909090";
                    $billProducts['data'][$key]["hsCodeName"]=$val["hsCodeName"]="CLOTH TAPE";
                }
                
                 if(strstr($val["customerCode"], "ţƤֽ��ེ��")){
                    $billProducts['data'][$key]["hsCode"]=$val["hsCode"]="4811410000";
                    $billProducts['data'][$key]["hsCodeName"]=$val["hsCodeName"]="TAPE";
                }
                
            }
            

            $val["en_desc"]=$val["podesc"].$val["poEdesc"];//Ӣ������

            $val['poUnitOne']= preg_replace('/(\w+)([S])/i', '${1}', $val['poUnit']);
            
            $val["u8_info"]= $this->orderSer->getU8OrdListInfo($val['u8Code'],$val['complete'],$val['orderId']);

           
  
            //$val["u8_info"]= $this->orderSer->getU8OrdListInfo($val['u8Code'],$val['complete'],$val['orderId']);
   
            $val['u8_info']['dwxs']= !empty($val['u8_info']['dwxs'])?$val['u8_info']['dwxs']:1;
            
            
    
            if($val["u8_info"]['mz']=='δ��ȡ') $val["u8_info"]['mz']=0.00;
            if($val["u8_info"]['jz']=='δ��ȡ') $val["u8_info"]['jz']=0.00;
            if($val["u8_info"]['dwtj']=='δ��ȡ') $val["u8_info"]['dwtj']=0.00;

            $val["u8_info"]['mz']=floatval($val["c_dwmz"])!=0?floatval($val["c_dwmz"]):floatval($val["u8_info"]['mz']);
            $val["u8_info"]['jz']=floatval($val["c_dwjz"])!=0?floatval($val["c_dwjz"]):floatval($val["u8_info"]['jz']);
            $val["u8_info"]['dwtj']=floatval($val["c_dwtj"])!=0?floatval($val["c_dwtj"]):floatval($val["u8_info"]['dwtj']);
            $val['u8_info']['info_type']=!empty($val["c_dwmz"])?"�ͻ�Ҫ��ֵ":$val['u8_info']['info_type'];

            $val['u8_info']['dwxs']=empty($val["c_dwmz"])?$val['u8_info']['dwxs']:1;
            
            $val['u8_info']['dwxs']=$val["complete"]=="���Ʒ"?$val['u8_info']['dwxs']:1;
            
            $hsCodeInfo=array();
            if(!empty($val["hsCodeName"])){
                $hsCodeInfo=$this->productHscode->getInfo(array("hs_code_name"=>$val["hsCodeName"]));//��ȡHScode����
            }

        
            
            //������������
            $tuopan=$this->sqlManage->sqlManageApi(array("unique_code"=>"tuopan_list","tpName"=>$val['tuoPan'],"limit"=>1));
            $tuopan["weight"]=!empty($tuopan["weight"])?$tuopan["weight"]:20;
            $val['tuopan_weight']=$val["tuopan_num"]*$tuopan["weight"];
           
            
            $nomanuaInfo=$manuaInfo=$val;//���ֲ�����ֲ�
            //�������ֲ�Ĳ�����
           
            $mjzNum=0;
            if($val['bhz']==0){

                //echo $val["id"].":".$val["tuopan_num"]."||";
                

                $total=$price_type=="2"&&$val['is_apportion']?$val['apportion_total']:$val['total_1'];//�жϼ۸����ͣ�1�ܼۣ�2��̯���ܼۣ�
                
                
                
                $nomanuaInfo['poUantity']=$manua_type=="1"?$val["poUantity"]:$val["poUantity"]-$val["manua_num"];//���ֲ��Ʒ����
                
                if(empty($val["box_num"])&&!empty($val['onum'])){
                    $nomanuaInfo['boxNum']=($val["complete"]=="��Ʒ"||$val["category"]=="PVC����"||$val['hz']==1)?ceil(($nomanuaInfo['poUantity']*$val['huanSuanLv'])/$val['onum']):$nomanuaInfo['poUantity'];//��Ʒ���Ʒ����ת��                
                }else{
                    if($val["complete"]=="��Ʒ"||$val["category"]=="PVC����"||$val['hz']==1){
                        $nomanuaInfo['boxNum']=$val["box_num"];
                        
                    }else{
                        $nomanuaInfo['boxNum']=$val["num"];
                        
                    }
                }
                
                 
                
                
                if($manua_type!="1"){
                    $nomanuaInfo['boxNum']=($val["complete"]=="��Ʒ"||($val["category"]=="PVC����"&&!empty($val['onum']))||$val['hz']==1)?ceil(($nomanuaInfo['poUantity']*$val['huanSuanLv'])/$val['onum']):$nomanuaInfo['poUantity'];//��Ʒ���Ʒ����ת��
                }
                
                
                //$nomanuaInfo['boxNum']=($val["complete"]=="��Ʒ"||($val["category"]=="PVC����"&&!empty($val['onum']))||$val['hz']==1)?ceil(($nomanuaInfo['poUantity']*$val['huanSuanLv'])/$val['onum']):$nomanuaInfo['poUantity'];//��Ʒ���Ʒ����ת��   
                
               
                  
                   
                $nomanuaInfo['boxNum']= ceil($nomanuaInfo['boxNum']);
                
                
                if($price_type=="2"&&$val['is_apportion']){
                    $nomanuaInfo['total']=$nomanuaInfo['poUantity']*($total/$val["poUantity"]);
                }else{
                    $nomanuaInfo['total']=$nomanuaInfo['poUantity']*$val["poPrice"];//���ֲ��Ʒ�ܼ�
                }
                
       
                $mjzNum=$nomanuaInfo['boxNum'];
                
      
            }else{
               
                //$nomanuaInfo["poUantity"]=$val["poUantity"]= $val["proUantity"];//��װ��Ʒ��������    
                
                if($val['poUnit']!="SETS"){
                    if($val["poUantity"]>$val["manua_num"]){
                        $nomanuaInfo["poUantity"]=$val["poUantity"]= $val["poUantity"]-$val["manua_num"];//��װ��Ʒ��������  
                    }
                }else{
                    
                    $nomanuaInfo["poUantity"]=$val["poUantity"]= $val["hzPoUantity"]-$val["manua_num"];//��װ��Ʒ��������  
                }
                
                
               
                
                
                $nomanuaInfo['total']=$total=   $nomanuaInfo["poUantity"]*$val["poPrice"];
                
                
                
                
                //echo $nomanuaInfo["poUantity"]."*".$val["poPrice"]."=".$nomanuaInfo['total']."||";
                
                //$nomanuaInfo["poUantity"]=$val["poUantity"]= $val["hzBoxNum"];//��װ��Ʒ��������   
                
                $mjzNum = floatval($val['hzBoxNum']);
                //�ж��Ƿ�Ϊ�ֲ��ӡ
                if($manua_type!="1"&&$val['poUnit']!="SETS"){
                    $mjzNum=(floatval($val['hzBoxNum'])-floatval($val['manua_box']));
                }else{
                    $mjzNum=(floatval($val['boxNum'])-floatval($val['manua_box']));
                }
               
                
                //$nomanuaInfo['u8_info']=$val['u8_info']=$val["hz_u8_info"];
                $nomanuaInfo['u8_info']['mz']=$val['u8_info']['mz']=$val['hz_u8_info']['mz'];
                $nomanuaInfo['u8_info']['jz']=$val['u8_info']['jz']=$val['hz_u8_info']['jz'];
                $nomanuaInfo['u8_info']['dwtj']=$val['u8_info']['dwtj']=$val['hz_u8_info']['dwtj'];

            }
            
   
            if(empty($val["c_dwmz"])&&empty($val["bhz"])&&$val['complete']=="���Ʒ"){
                if(($val['category']=="PVC����"||$val['category']=="BOPPĤ")&&$val['omaterial'] != 'ľ��' && $val['cInvCode'] != '����ֽ��'){
                    //$nomanuaInfo['u8_info']["dwmz"]=$val['mz'];
                    $nomanuaInfo['u8_info']["dwmz"]=floatval($val['u8_info']['mz'])*floatval($val['u8_info']['dwxs']);
                }else{
                    $nomanuaInfo['u8_info']["dwmz"]=floatval(($val["proUantity"]*$val['mz']/$val['boxNum']));   
                }    
            }else{
                if(!empty($val["c_dwmz"])){
                    $nomanuaInfo['u8_info']["dwmz"]=$val["c_dwmz"];
                }else{
                    $nomanuaInfo['u8_info']["dwmz"]=floatval($val['u8_info']['mz'])*floatval($val['u8_info']['dwxs']);
                }
                
                //echo $val['u8_info']['mz']."||";
            }
           


            if(empty($val["c_dwjz"])&&empty($val["bhz"])&&$val['complete']=="���Ʒ"){
                if(($val['category']=="PVC����"||$val['category']=="BOPPĤ")&&$val['omaterial'] != 'ľ��' && $val['cInvCode'] != '����ֽ��'){
                    //$nomanuaInfo['u8_info']["dwjz"]=$val['jz'];
                    $nomanuaInfo['u8_info']["dwjz"]=floatval($val['u8_info']['jz'])*floatval($val['u8_info']['dwxs']);
                }else{
                    $nomanuaInfo['u8_info']["dwjz"]=floatval(($val["proUantity"]*$val['jz']/$val['boxNum']));  
                }  
                
            }else{
                
                if(!empty($val["c_dwjz"])){
                    $nomanuaInfo['u8_info']["dwjz"]=$val["c_dwjz"];
                }else{
                    $nomanuaInfo['u8_info']["dwjz"]=floatval($val['u8_info']['jz'])*floatval($val['u8_info']['dwxs']);
                }
            }
            
            //�ų���Ʒ����ʵë���ؼ���(��ʱ�ӵģ�����ɨ��û������Ҫȥ��)
            if(empty($val["bhz"])&&$val['complete']=="��Ʒ"&&$val['category']!="BOPPĤ"){
                $billProducts['data'][$key]['zs_mz']=$val["zs_mz"]=$val['mz'];
                $billProducts['data'][$key]['zs_jz']=$val["zs_jz"]=$val['jz'];
            }
            
            //echo $val["zs_jz"]."||";



            $nomanuaInfo["zs_dwjz"]=floatval($val['zs_jz']);
            $nomanuaInfo["zs_dwmz"]=floatval($val['zs_mz']);

            


            if(empty($val["bhz"])&&$val['complete']=="���Ʒ"){
                if($val['category']!="PVC����"){
                    $nomanuaInfo["zs_dwjz"]=floatval($val['zs_zjz']/$val['num']);  
                    $nomanuaInfo["zs_dwmz"]=floatval($val['zs_zmz']/$val['num']);  
                }
            }
            
           
            
       
            if(!empty($deliverInfo)){
                $deliverItem=$this->db->get_one("dev_provebill_deliver_item", "*", " deliver_id={$deliverInfo["id"]} and order_list_id={$val['id']} ");
                //$billItem=$this->db->get_one("dev_provebill_product", "*", " provebill_id in ({$bill_id}) and order_list_id={$val['id']} ");
                if(!empty($deliverItem)){
                    if(!empty($deliverItem['fnweights'])){
                        $nomanuaInfo["zs_dwjz"]=$val['zs_dwjz']=floatval($deliverItem['fnweights']/$val["num"]);  
                    }
                    if(!empty($deliverItem['fgweights'])){
                        $nomanuaInfo["zs_dwmz"]=$val['zs_dwmz']=floatval($deliverItem['fgweights']/$val["num"]); 
                    }
                }
            }
            
          
    
            /*$nomanuaInfo['u8_info']["dwmz"]=$val['u8_info']['mz']*$val['u8_info']['dwxs'];
            $nomanuaInfo['u8_info']["dwjz"]=$val['u8_info']['jz']*$val['u8_info']['dwxs'];*/
            
            
           
           
    
            $nomanuaInfo['u8_info']['mz']=floatval($mjzNum)*($nomanuaInfo['u8_info']["dwmz"]);//���ֲ�ë�أ�����*����λë��*ϵ������
            $nomanuaInfo['u8_info']['jz']=floatval($mjzNum)*($nomanuaInfo['u8_info']["dwjz"]);//���ֲΆ�أ�����*����λë��*ϵ������
            $nomanuaInfo['u8_info']['tj']=floatval($mjzNum)*$val["u8_info"]['dwtj'];//���ֲ������(����*��λ���)
            
            
            
           
            $nomanuaInfo['zs_mz']=floatval($mjzNum)*($nomanuaInfo["zs_dwmz"]);//���ֲ�ë�أ�����*����λë��*ϵ������
            $nomanuaInfo['zs_jz']=floatval($mjzNum)*($nomanuaInfo["zs_dwjz"]);//���ֲΆ�أ�����*����λë��*ϵ������
            

             if($manua_type=="1"){
                $nomanuaInfo['zs_mz']=$val["zs_zmz"];//���ֲ�ë�أ�����*����λë��*ϵ������
                $nomanuaInfo['zs_jz']=$val["zs_zjz"];//���ֲΆ�أ�����*����λë��*ϵ������
            }
            
            
            $nomanuaInfo['u8_info']['info_type']=$val["u8_info"]['info_type'];
            $nomanuaInfo["lading_bill_name"]=!empty($hsCodeInfo["lading_bill_name"])?$hsCodeInfo["lading_bill_name"]:"";
            $nomanuaInfo["grade_name"]=!empty($hsCodeInfo["grade_name"])?$hsCodeInfo["grade_name"]:"";
            $nomanuaInfo['u8_info']["dwtj"]=$val['u8_info']['dwtj'];

            $nomanuaInfo['u8_info']["dwxs"]=$val['u8_info']['dwxs'];
            $nomanuaInfo['u8_info']["length"]=$val['u8_info']['length'];
            $nomanuaInfo['u8_info']["height"]=$val['u8_info']['height'];
            $nomanuaInfo['u8_info']["width"]=$val['u8_info']['width'];
            $nomanuaInfo['u8_info']["formula"]=$val['u8_info']['length']."*".$val['u8_info']['width']."*".$val['u8_info']['height'];
            $nomanuaInfo['tuopan_weight']=$val['tuopan_weight'];

            /*echo "<pre>";
            echo var_dump($nomanuaInfo['poUantity']);*/
            
            if(!empty($nomanuaInfo['poUantity'])){  
                if($nomanuaInfo["poUantity"]<$nomanuaInfo['num']/2){
                    $nomanuaInfo["tuopan_num"]=0;
                    $nomanuaInfo['tuopan_weight']=0;
                }   
                
                $manualArray["no_manua"]["productList"][]=$nomanuaInfo;
            }
            
            
           
            
            if($manua_type=="2"&&!empty($val["manua"])&&!empty($val["manua_num"])){
               
                //�������ֲ�Ĳ�Ʒ����
                $manuaInfo['poUantity']=$val["manua_num"];
                
                $manuaInfo["zs_dwjz"]=floatval($val['zs_jz']);
                $manuaInfo["zs_dwmz"]=floatval($val['zs_mz']);

                if(empty($val["bhz"])&&$val['complete']=="���Ʒ"){
                    if($val['category']!="PVC����"){
                        $manuaInfo["zs_dwjz"]=floatval(($val["proUantity"]*$val['zs_jz']/$val['org_num']));  
                        $manuaInfo["zs_dwmz"]=floatval(($val["proUantity"]*$val['zs_mz']/$val['org_num']));  
                    }
                }

                
                if($val["bhz"]==0){
                    
                    $manuaInfo['boxNum']=($val["complete"]=="��Ʒ"||($val["category"]=="PVC����"&&!empty($val['onum']))||$val['hz']==1)?ceil(($manuaInfo['poUantity']*$val['huanSuanLv'])/$val['onum']):$manuaInfo['poUantity'];//��Ʒ���Ʒ����ת��
                    $manuaInfo['boxNum']= round($manuaInfo['boxNum']);
        
                    if($val["is_apportion"]){
                        $manuaInfo['total']=$val['apportion_total']*($val["manua_num"]/$val["poUantity"]);//���ֲ��Ʒ�ܼ�
                    }else{
                        $manuaInfo['total']=$manuaInfo['poUantity']*$val["poPrice"];//���ֲ��Ʒ�ܼ�
                    }
                    
                   
                   
                    if(empty($val["c_dwmz"])&&$val['complete']=="���Ʒ"&&$val['category']!="PVC����"){
                        $manuaInfo['u8_info']["dwmz"]=floatval(($val["proUantity"]*$val['mz']/$val['u8_info']['dwxs']/$val['org_num'])*$val['u8_info']['dwxs']); 
                        
                    }else{
                        $manuaInfo['u8_info']["dwmz"]=floatval($val['u8_info']['mz'])*floatval($val['u8_info']['dwxs']);
                    }
                    
                    if(empty($val["c_dwjz"])&&$val['complete']=="���Ʒ"&&$val['category']!="PVC����"){
                        $manuaInfo['u8_info']["dwjz"]=floatval(($val["proUantity"]*$val['jz']/$val['u8_info']['dwxs']/$val['org_num'])*$val['u8_info']['dwxs']);  
                    }else{
                        $manuaInfo['u8_info']["dwjz"]=floatval($val['u8_info']['jz'])*floatval($val['u8_info']['dwxs']);
                    }
                    
  
                    $manuaInfo['u8_info']['mz']=$manuaInfo['boxNum']*($manuaInfo['u8_info']["dwmz"]);//���ֲ�ë�أ�����*����λë��*ϵ������
                    $manuaInfo['u8_info']['jz']=$manuaInfo['boxNum']*($manuaInfo['u8_info']["dwjz"]);//���ֲΆ�أ�����*����λë��*ϵ������
                    
                   
                   
                    $manuaInfo['zs_mz']=$manuaInfo['boxNum']*($manuaInfo["zs_dwmz"]);//���ֲ�ë�أ�����*����λë��*ϵ������
                    $manuaInfo['zs_jz']=$manuaInfo['boxNum']*($manuaInfo["zs_dwjz"]);//���ֲΆ�أ�����*����λë��*ϵ������
                    
                    
                    
                    
                    $manuaInfo['u8_info']['tj']=$manuaInfo['boxNum']*$val["u8_info"]['dwtj'];//���ֲΆ��(����*��λ���)
                    $manuaInfo['u8_info']['info_type']=$val["u8_info"]['info_type'];

                    $manuaInfo["lading_bill_name"]=!empty($hsCodeInfo["lading_bill_name"])?$hsCodeInfo["lading_bill_name"]:"";
                    $manuaInfo["grade_name"]=!empty($hsCodeInfo["grade_name"])?$hsCodeInfo["grade_name"]:"";
                    $manuaInfo['u8_info']["dwtj"]=$val['u8_info']['dwtj'];
                    
    
                    $manuaInfo['u8_info']["dwxs"]=$val['u8_info']['dwxs'];
                    $manuaInfo['u8_info']["length"]=$val['u8_info']['length'];
                    $manuaInfo['u8_info']["height"]=$val['u8_info']['height'];
                    $manuaInfo['u8_info']["width"]=$val['u8_info']['width'];
                    $manuaInfo['u8_info']["formula"]=$val['u8_info']['length']."*".$val['u8_info']['width']."*".$val['u8_info']['height'];
                    $manuaInfo['tuopan_weight']=$val['tuopan_weight'];
                    
                }else{
                    
                    
                    if($val["is_apportion"]){
                        $manuaInfo['total']=$val['apportion_total']*($val["manua_num"]/$val["poUantity"]);//���ֲ��Ʒ�ܼ�
                    }else{
                        $manuaInfo['total']=$manuaInfo['poUantity']*$val["poPrice"];//���ֲ��Ʒ�ܼ�
                    }
                    
                    
                    
                    //$manuaInfo['total']=$manuaInfo['poUantity']*$val["poPrice"];//���ֲ��Ʒ�ܼ�
                    $manuaInfo['u8_info']['mz']=$manuaInfo['manua_box']*($val['hz_u8_info']['mz']);//���ֲ�ë�أ�����*����λë��*ϵ������
                    $manuaInfo['u8_info']['jz']=$manuaInfo['manua_box']*($val['hz_u8_info']['jz']);//���ֲΆ�أ�����*����λë��*ϵ������
                    
                    
                    
                    $manuaInfo['u8_info']['tj']=$manuaInfo['manua_box']*$val["u8_info"]['dwtj'];//���ֲΆ��(����*��λ���)
                    $manuaInfo['u8_info']['info_type']=$val["u8_info"]['info_type'];
                    $manuaInfo["grade_name"]=!empty($hsCodeInfo["grade_name"])?$hsCodeInfo["grade_name"]:"";
                    $manuaInfo["lading_bill_name"]=!empty($hsCodeInfo["lading_bill_name"])?$hsCodeInfo["lading_bill_name"]:"";

                    $manuaInfo['u8_info']["dwtj"]=$val['hz_u8_info']['dwtj'];
                    $manuaInfo['u8_info']["dwmz"]=$val['hz_u8_info']['mz']*$val['hz_u8_info']['dwxs'];
                    $manuaInfo['u8_info']["dwjz"]=$val['hz_u8_info']['jz']*$val['hz_u8_info']['dwxs'];
                    $manuaInfo['u8_info']["dwxs"]=$val['hz_u8_info']['dwxs'];
                    $manuaInfo['u8_info']["length"]=$val['hz_u8_info']['length'];
                    $manuaInfo['u8_info']["height"]=$val['hz_u8_info']['height'];
                    $manuaInfo['u8_info']["width"]=$val['hz_u8_info']['width'];
                    $manuaInfo['u8_info']["formula"]=$val['hz_u8_info']['length']."*".$val['hz_u8_info']['width']."*".$val['hz_u8_info']['height'];
                    $manuaInfo['tuopan_weight']=$val['tuopan_weight'];
                }
                if(!empty($manuaInfo['poUantity'])){
                    if($manuaInfo["poUantity"]<$manuaInfo['num']/2){
                        $manuaInfo["tuopan_num"]=0;
                        $manuaInfo['tuopan_weight']=0;
                    } 
                   
                    $manualArray[$val["manua"]]["productList"][]=$manuaInfo;
                }
                
            }
            
            

        }


        $productList=array();
        
       
     

        foreach ($manualArray as $k=>$v){
            
        
            
            $poPriceAll=$totalNum=$totalMoney=$count=0;
            
            $productList[$k]=array("count"=>0,"totalMoney"=>0,"poUnit"=>"","totalNum"=>0,'products'=>array(),"productItem"=>array());
            
            $typeArray=array("complete"=>array(),"tuoPan"=>array(),"category"=>array(),"hzBoxNum"=>array());
            
            $bhzArray=array();
    
            foreach ($v["productList"] as $key=>$val){
                
               
                
                if(!empty($val["zs_mz"])&&$val["zs_mz"]!='.00'&&empty($val["c_dwmz"])) $val["u8_info"]['mz']=$val["zs_mz"];//ȡʵ��ë��
                if(!empty($val["zs_jz"])&&$val["zs_jz"]!='.00'&&empty($val["c_dwjz"])) $val["u8_info"]['jz']=$val["zs_jz"];//ȡʵ�ʾ���
                
               
                
                if(!empty($val["zs_dwmz"])&&empty($val["c_dwmz"])) $val["u8_info"]['dwmz']=$val["zs_dwmz"];//ȡʵ�ʵ�λë��
                
                if(!empty($val["zs_dwjz"])&&empty($val["c_dwjz"])) $val["u8_info"]['dwjz']=$val["zs_dwjz"];//ȡʵ�ʵ�λ����
                
                
               
                
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
                
                              
                if(in_array($print_type, array("1","3"))){
                    
                   
         
                    $val["po"]=!empty($val["po"])?$val["po"]:$val["listpo"];
                    $val["po"]=!in_array($val["po"], ["","��"])?"PO:".$val["po"]:"";
                    $val["po"]=$val["po"]."\n".$val["orderNum"];
                    if($val["hz"]==1){
                        /*foreach ($v["productList"] as $key_1=>$val_1){
                            if($val_1["bhz"]==$val["id"]){
                                $val["u8_info"]['mz']+=$val_1["u8_info"]['mz'];
                                $val["u8_info"]['jz']+=$val_1["u8_info"]['jz'];
                                $val["u8_info"]['tj']+=$val_1["u8_info"]['tj'];
                            }
                        }*/
                    }  
                    
                    
                    
                    if($val["bhz"]==0){

                        $val["poPrice"]=$val['total']/$val['poUantity'];//���¼��㵥��
                        
                        //echo $val['poUantity']."||";
                        
                        $productList[$k]['products'][$val["po"]][]=$val;
                        $productList[$k]['count']++;
               
                        $productList[$k]['totalMoney']+=$val['total'];
                        $productList[$k]['total_mz']+= round($val["u8_info"]['mz'],2)+$val['tuopan_weight'];
                        $productList[$k]['total_jz']+= round($val["u8_info"]['jz'],2);
                        $productList[$k]['total_tj']+= round($val["u8_info"]['tj'],2);
                        $productList[$k]['totalNum']+=$val['poUantity']; 
                        $productList[$k]['totalBoxNum']+=$val['boxNum'];  
                        $productList[$k]['total_tuopan_num']+=$val['tuopan_num'];
                        $productList[$k]['total_tuopan_weight']+=$val['tuopan_weight'];
                       
                    }
                    
                }else{
                    
                    
                    //echo  $val["id"]."#".$val["bhz"]."#".$val["poUnit"]."||";
                    
                  
                    
                    //if($val["bhz"]!=0&&$val["poUnit"]=="SETS") continue;
                    $poPriceAll+=$val['poPrice'];
                    
                    $hsCodeName=$val["hsCodeName"] = empty($val["hsCodeName"])?"NULL-VALUE":$val["hsCodeName"];
                    if($val["bhz"]!=0){
                        //$val["hsCodeName"]="A_".$val['bhz'].$hsCodeName;
                        $val["hsCodeName"]=$hsCodeName;
                    }
                    
                  
                    
                    $productList[$k]['products'][$val["hsCodeName"]]['hsCodeName']=$hsCodeName;
                    $productList[$k]['products'][$val["hsCodeName"]]['hsCode']=$val['hsCode'];
                    $productList[$k]['products'][$val["hsCodeName"]]['grade_name']=$val['grade_name'];
                    $productList[$k]['products'][$val["hsCodeName"]]['lading_bill_name']=$val['lading_bill_name'];
                    if(!empty($val["bhz"])){
                        if(!in_array($val["bhz"], $bhzArray[$val["hsCodeName"]])){
                            $productList[$k]['products'][$val["hsCodeName"]]['poUantity']+=$val['hzPoUantity'];
                            $bhzArray[$val["hsCodeName"]][]=$val["bhz"];
                        }
                    }else{
                        $productList[$k]['products'][$val["hsCodeName"]]['poUantity']+=$val['poUantity']; 
                    }

                    $productList[$k]['products'][$val["hsCodeName"]]['poUnit']=$val['poUnit'];
                    $productList[$k]['products'][$val["hsCodeName"]]['total']+=$val['total'];
                   
                    
                    
                    $productList[$k]['products'][$val["hsCodeName"]]['poUnitOne']=$val['poUnitOne'];
                    $productList[$k]['products'][$val["hsCodeName"]]['boxNum']+=$val['boxNum'];
           
                    
                    $productList[$k]['products'][$val["hsCodeName"]]['poPrice']=$productList[$k]['products'][$val["hsCodeName"]]['total']/$productList[$k]['products'][$val["hsCodeName"]]['poUantity'];
                    //$productList[$k]['products'][$val["hsCodeName"]]['poPrice1']=$productList[$k]['products'][$val["hsCodeName"]]['total']/$productList[$k]['products'][$val["hsCodeName"]]['poUantity'];
                    $productList[$k]['products'][$val["hsCodeName"]]['bhz']=$val['bhz'];
                    
                    $productList[$k]['products'][$val["hsCodeName"]]['color']=$val['color'];
                    
                    
           
                    if(empty($productList[$k]['products'][$val["hsCodeName"]]['u8_info'])){
                        $productList[$k]['products'][$val["hsCodeName"]]['u8_info']=array('mz'=>0.00,'jz'=>0.00,'tj'=>0.00,"is_real"=>array(),"no_real"=>array());
                    }
                    
                    
                    
                    
                    if(!empty($val["u8_info"])){
                        
                        $productList[$k]['products'][$val["hsCodeName"]]['u8_info']['mz']+= round($val["u8_info"]['mz'],2);
                        $productList[$k]['products'][$val["hsCodeName"]]['u8_info']['jz']+= round($val["u8_info"]['jz'],2);
                        $productList[$k]['products'][$val["hsCodeName"]]['u8_info']['tj']+= round($val["u8_info"]['tj'],2);                        

                        if($val["u8_info"]['is_real']){
                            $productList[$k]['products'][$val["hsCodeName"]]['u8_info']['is_real'][]=$val["u8Code"];
                        }else{
                            $productList[$k]['products'][$val["hsCodeName"]]['u8_info']['is_real'][]=$val["u8Code"];
                        }
                    }
                    
                    $productList[$k]['count']++;
                    
                  
                     
                    
                    $productList[$k]['totalMoney']+=$val['total'];
                    $productList[$k]['total_mz']+=round($val["u8_info"]['mz'],2)+$val['tuopan_weight']; 
                    $productList[$k]['total_jz']+=round($val["u8_info"]['jz'],2);
                    $productList[$k]['total_tj']+=round($val["u8_info"]['tj'],2);
                    $productList[$k]['totalNum']+=$val['poUantity']; 
                    $productList[$k]['totalBoxNum']+=$val['boxNum'];  
                    $productList[$k]['total_tuopan_num']+=$val['tuopan_num'];
                    $productList[$k]['total_tuopan_weight']+=$val['tuopan_weight'];
                    $productList[$k]["productItem"][$val["orderId"]][]=$val;
                   
                }

                
                
    
            }
            

            if(count($typeArray["complete"])==1&&$typeArray["complete"][0]=="��Ʒ"){
                $poUnit="CTNS";
            }else if(count($typeArray["complete"])==1&&$typeArray["complete"][0]=="���Ʒ"){
                $poUnit="ROLLS";
                if(count($typeArray["category"])==1&&$typeArray["category"][0]=="PVC����"){
                    $poUnit="CTNS";
                }else if(count($typeArray["category"])>0&& in_array("PVC����", $typeArray["category"])){
                    $poUnit="PKGS";
                }
            }else{
                $poUnit="PKGS";
            }
            
           
            
            /*if(count($typeArray["tuoPan"])==1&&$typeArray["complete"][0]=="��������"){ //ȫ��������
                $poUnit="ctns";
            }else if(!in_array("��������", $typeArray["tuoPan"])){ //ȫ����
                $poUnit="ptls";
            }else{  //��ȫ����
                $poUnit="ctns";
            }*/
            
            
            
            /*if($k=="no_manua"){  
                $productList[$k]['total_tuopan_num']=$tupanTotal['tuopan_num'];
                $productList[$k]['total_tuopan_weight']=$tupanTotal['tuopan_weight'];
            }*/
            
            
            $productList[$k]['poUnit']=$poUnit;
  
        }

        
        
        $retInfo["accSetInfo"]=$accSetInfo;             //������Ϣ
        $retInfo["accCtrInfo"]=$accCtrInfo;             //ó�׹�˾������Ϣ
        
        $retInfo["billInfo"]=$billInfo;                 //������Ϣ
        $retInfo["cabinetInfo"]=!empty($cabinetInfo)?$cabinetInfo:array();                 //װ����Ϣ
        $retInfo["billProducts"]=$billProducts;         //��Ʒ�б�
        $retInfo["productList"]=$productList;           //��Ʒ��ӡ�б�
       
      
        /*echo "<pre>";
        echo var_dump($productItem);
        exit;*/
        
        $retInfo["mark"]=""; 
        $markInfo = $this->msdb->select_one("Customer", "*", " cCusCode='{$billInfo["customer_code"]}' ");
        if(!empty($markInfo)&&is_array($markInfo)){
            $retInfo["mark"]=$markInfo["cCusDefine1"]; 
        }

        return $retInfo;

    }

    /**
     * ��ӡ��Ʊ�������
     * @param type $params
     * @return boolean
     */
    public function printData_two($params){

        $retInfo=array();
        $bill_id=!empty($params["bill_id"])?$params["bill_id"]:$params["bill_ids"]; //���յ�ID
        $print_type=$params["print_type"]; //��������
        $price_type=$params["price_type"]; //�۸����ͣ�1.�ܼۣ�2.��̯�۸�
        $manua_type=$params["manua_type"]; //�Ƿ��ֲ��ӡ
        $adjust_type=!empty($params["adjust_type"])?$params["adjust_type"]:0; //�Ƿ����
        $bill_adjust=!empty($params["bill_adjust"])?$params["bill_adjust"]:1;



        $billInfo=$this->provebill->getBillInfo(array("id"=>$bill_id));//��ȡ��������
        //print_r($billInfo);
        $product_split_list = string::autoCharset(json_decode(string::autoCharset($billInfo['product_list'], 'gbk', 'utf-8'),true), 'utf-8', 'gbk');//����ֲ�

        $billProducts = $this->orderSer->getOrderProductList(array("provebill_id"=>$bill_id,"bhz"=>0,"order_by_id"=>"ASC","limit"=>999)); //��ȡ���ղ�Ʒ�б� ȡ[data]
        //print_r($billProducts);exit;
        $cabinetInfo=$this->db->get_one("dev_provebill_cabinet", "*", " bill_id in ({$bill_id}) ");
        $deliverInfo=$this->db->get_one("dev_provebill_deliver", "*", " bill_ids in ({$bill_id}) ");




        $this->provebill->productHandle($billProducts["data"]);





        $accSetInfo=$this->accountSet->getInfo(array("account_type"=>$billInfo["account_type"])); //��ȡ������Ϣ
        $accCtrInfo=array();
        if(!empty($billInfo["account_ctr_id"])){
            $accCtrInfo=$this->sqlManage->sqlManageApi(array("unique_code"=>"account_ctr_list","id"=>$billInfo["account_ctr_id"],"limit"=>1));   //��ȡó�׹�˾������Ϣ
        }



        if(empty($billProducts['data'])) return false;
        $poUnit=$billProducts['data'][0]["poUnit"];



        //���յ�������ֵ
        if($bill_adjust==2){
            $fieldArray=["is_apportion","apportion_price","apportion_total","num",'box_num',"mz_1","jz_1","tj_1","total_1","po_price"];
            foreach ($billProducts['data'] as $key=>$val){
                if(empty($val['provebill_id']))continue;
                $adjustInfo=$this->db->get_one("dev_provebill_product_adjust", "*", " order_list_id={$val['id']} and provebill_id={$val['provebill_id']} order by id ASC ");
                if(empty($adjustInfo))   continue;
                foreach ($fieldArray as $k=>$v){
                    $billProducts['data'][$key][$v]=$adjustInfo[$v];
                }
            }
        }




        //�ּ��װ��Ʒ�������װ��Ʒ�۸�ë����
        foreach ($billProducts['data'] as $key=>$val){

            $billProducts['data'][$key]["poPrice"]=$val["poPrice"]=$val["poPrice1"];
            $billProducts['data'][$key]["proPrice"]=$val["proPrice"]=$val["proPrice1"];

            $billProducts['data'][$key]["poPrice"]=$val["poPrice"]= doubleval($val["po_price"])>0?$val["po_price"]:$val["poPrice"] ;


            $billProducts['data'][$key]["poPrice"]=$val['poPrice']= $val['frignPrice']==0?$val['poPrice']:$val['frignPrice']; //�����������



            $billProducts['data'][$key]["apportion_total"]=$val["apportion_total"]= floatval($billProducts['data'][$key]["poPrice"]*$val["num"]+$val["apportion_price"]);


            //echo $val["poPrice"]=$val["poPrice1"]."||";

            if(empty($val['huanSuanLv'])) $billProducts['data'][$key]["huanSuanLv"]=$val['huanSuanLv']=1;

            if(empty($val["poUantity"])){
                unset($billProducts['data'][$key]);
                continue;
            }




            $billProducts['data'][$key]["isAll"]=$val["isAll"] =$isAll=false;
            if($val["poUantity"]<=$val["num"]){
                $billProducts['data'][$key]["isAll"]=$val["isAll"]=$isAll=true;
            }


            $billProducts['data'][$key]["poUantity"]=$val["poUantity"]=$val["num"];//������





            //$billProducts['data'][$key]["poPrice"]=$val['poPrice']= $val['po_price']==0?$val['poPrice']:$val['po_price']; //�����������

            $billProducts['data'][$key]["poPrice"]=$val['poPrice']= $val['frignPrice']==0?$val['poPrice']:$val['frignPrice']; //�����������
            $billProducts['data'][$key]["total"]=$val['total']= $val['frignTotal']==0?$val['total']:$val['frignTotal']; //��������ܼ�


            if($val["hz"]==1){
                if(empty($val["hzNumSum"])){
                    $billProducts['data'][$key]["hzNumSum"] = $val["hzNumSum"] = array_sum(array_filter(explode(",", $val["hzNum"]) ));
                }

                $billProducts['data'][$key]["u8_info"]=$val["u8_info"]= $this->orderSer->getU8OrdListInfo($val['u8Code'],$val['complete'],$val['orderId']);




                $billProducts['data'][$key]["u8_info"]['mz']=$val["u8_info"]["mz"]=floatval($val["c_dwmz"])!=0?floatval($val["c_dwmz"]):floatval($val["u8_info"]['mz']);
                $billProducts['data'][$key]["u8_info"]['jz']=$val["u8_info"]["jz"]=floatval($val["c_dwjz"])!=0?floatval($val["c_dwjz"]):floatval($val["u8_info"]['jz']);
                $billProducts['data'][$key]["u8_info"]['dwtj']=$val["u8_info"]["dwtj"]=floatval($val["c_dwtj"])!=0?floatval($val["c_dwtj"]):floatval($val["u8_info"]['dwtj']);



                $val['u8_info']['dwxs']=$val["complete"]=="���Ʒ"?$val['u8_info']['dwxs']:1;

                if(empty($val["box_num"])){
                    $billProducts['data'][$key]["boxNum"]=$val["boxNum"]= ceil(round($val['num'])*$val['huanSuanLv']/floatval($val['onum'])); //�Զ�������
                }else{
                    $billProducts['data'][$key]["boxNum"]=$val["boxNum"]=$val["box_num"];
                }



                $hzProducts = $this->orderSer->getOrderProductList(array("hz"=>0,"bhz"=>$val["id"],"limit"=>999)); //��ȡ��װ��Ʒ


                $hzPrice=$price_type=="2"&&$val['is_apportion']?$val['apportion_total']:$billProducts['data'][$key]["total_1"];



                if(!empty($hzProducts['data'])){

                    $billProducts['data'][$key]["hsCode"]=$hzProducts['data'][0]["hsCode"];
                    $billProducts['data'][$key]["hsCodeName"]=$hzProducts['data'][0]["hsCodeName"];

                    if($val['poUnit']!="SETS"){

                        $unitProRoll=$val['proUantity']/$val['hzNumSum'];//��λ��װ����


                        if(empty($val["hzNumSum"])){
                            $unitProRoll=1;
                        }



                        $allRolls=0;
                        $billProducts['data'][$key]["complete"]=$hzProducts['data'][0]["complete"];
                        if(in_array($print_type, ['2','4','6','5'])){



                            unset($billProducts['data'][$key]);


                            //$hzProducts['data'][$k]["proUantity"]=$v["proUantity"]=$v["poUantity"];

                            foreach ($hzProducts['data'] as $k=>$v){
                                $allRolls+=$v["proUantity"];
                            }



                            /*$hzMethodArray=array("hzids"=>array_filter(explode(",", $val['hzids'])),"hzNum"=>array_filter(explode(",", $val['hzNum'])));
                            $hzMethod=array();
                            if(!empty($hzMethodArray["hzids"])){
                                foreach ($hzMethodArray["hzids"] as $k=>$v){
                                    $hzMethod[$v]=$hzMethodArray["hzNum"][$k];
                                }
                            }*/


                            $total_box_num=0;
                            //��װ��Ʒ����̯
                            foreach ($hzProducts['data'] as $k=>$v){

                                $hzProducts['data'][$k]["poPrice"]=$v['poPrice']= $v['po_price']==0?$v['poPrice']:$v['po_price']; //�����������
                                $hzProducts['data'][$k]["poPrice"]=$v['poPrice']= $v['frignPrice']==0?$v['poPrice']:$v['frignPrice']; //�����������
                                $hzProducts['data'][$k]["total"]=$v['total']= $v['frignTotal']==0?$v['total']:$v['frignTotal']; //��������ܼ�

                                $hzProducts['data'][$k]["total_1"]=$hzPrice*($v["proUantity"]/$allRolls);
                                //$hzProducts['data'][$k]["boxNum"]=$val["poUantity"]*($v["proUantity"]/$allRolls);

                                $hzProducts['data'][$k]["poPrice"]=$hzPrice*($v["proUantity"]/$allRolls)/$v["poUantity"];


                                $hzProducts['data'][$k]["poUnit"]="ROLLS";
                                $hzProducts['data'][$k]["boxNum"]=0;


                                if($k==0) {
                                    $hzProducts['data'][$k]["boxNum"]=$val["boxNum"];
                                    $hzProducts['data'][$k]["tuopan_num"]=$val["tuopan_num"];
                                    $hzProducts['data'][$k]["tuopan_weight"]=$val["tuopan_weight"];
                                }

                                if(!empty($v["huanSuanLv"])){
                                    $hzProducts['data'][$k]["boxNum"]=round($val["boxNum"]*($v['huanSuanLv']/$val['hzNumSum']));
                                    $total_box_num+=$hzProducts['data'][$k]["boxNum"];
                                }else if(!empty($v["onum"])){
                                    $hzProducts['data'][$k]["boxNum"]=round($val["boxNum"]*($v['onum']/$val['hzNumSum']));
                                    $total_box_num+=$hzProducts['data'][$k]["boxNum"];
                                }

                                //$hzProducts['data'][$k]["boxNum"] = intval($hzProducts['data'][$k]["boxNum"]);



                                $hzProducts['data'][$k]["manua"]=$val["manua"];

                                if(!empty($val["hzNumSum"])){
                                    $hzProducts['data'][$k]["hzPoUantity"]=(floatval($val["poUantity"])/floatval($val["hzNumSum"])*(floatval($v["proUantity"])/floatval($unitProRoll)));
                                    $hzProducts['data'][$k]["hzBoxNum"]=(floatval($val["boxNum"])/floatval($val["hzNumSum"])*(floatval($v["proUantity"])/floatval($unitProRoll)));
                                    $hzProducts['data'][$k]["manua_num"]=(floatval($val["manua_num"])/floatval($val["hzNumSum"])*(floatval($v["proUantity"])/floatval($unitProRoll)));


                                }else{
                                    $hzProducts['data'][$k]["hzPoUantity"]=$v["proUantity"];
                                    $hzProducts['data'][$k]["hzBoxNum"]=$v["boxNum"];
                                    $hzProducts['data'][$k]["manua_num"]=$val["manua_num"];
                                }

                                $hzProducts['data'][$k]["manua_box"]=$hzProducts['data'][$k]["manua_num"]*$val['huanSuanLv']/floatval($val['onum']);

                                $hzProducts['data'][$k]["hz_u8_info"]=$val["u8_info"];

                            }




                            //������ֵ
                            $hzProducts['data'][0]["boxNum"]+=$val["boxNum"]-$total_box_num;


                        }

                        foreach ($hzProducts['data'] as $k=>$v){
                            $billProducts['data'][]=$v;
                        }

                    }




                }

            }




        }


        $apportion_total=0;






        //������������
        $tupanTotal=array("tuopan_num"=>0,"tuopan_weight"=>0);


        //�ֲ�������ݼ���
        $manualArray=array();


        if(!empty($product_split_list) &&$product_split_list!="null"){
            //��������
            //$product_split_list=$this->cl_data($product_split_list,$bill_adjust,$price_type,$print_type);
            //��ȡ�����ֲ��
            $no_manua_array_data=$this->get_no_manua_array_data($params);
            $manualArray['no_manua']=$no_manua_array_data['manualArray']['no_manua'];
            $tupanTotal=$no_manua_array_data['tupanTotal'];

            //��ȡ�����ֲ�
            foreach($product_split_list as $k_1=>$v_1){//�����ֵ��ֲ��

                    foreach($v_1['product_list']as $k_2=>$v_2){

                        $tupanTotal["tuopan_num"]+=round($v_2["tuopan_num"]);
                        $tupanTotal["tuopan_weight"]+=round($v_2["tuopan_weight"]);



                        if(in_array($v_2['category'], ["��ེ��"])){
                            if(in_array($v_2['model'], ["","���ֱ�"]) ){
                                $v_2["hsCode"]="4811410000";
                                $v_2["hsCodeName"]="SPIRAL CLEANING TAPE";
                            }else{
                                $v_2["hsCode"]="9603909090";
                                $v_2["hsCodeName"]="CLEANER TAPE";
                            }

                            if(strstr($v_2["customerCode"], "��ɫС����")){
                                $v_2["hsCode"]="3919909090";
                                $v_2["hsCodeName"]="CLOTH TAPE";
                            }

                            if(strstr($v_2["customerCode"], "ţƤֽ��ེ��")){
                                $v_2["hsCode"]="4811410000";
                                $v_2["hsCodeName"]="TAPE";
                            }

                        }


                        $v_2["en_desc"]=$v_2["podesc"].$v_2["poEdesc"];//Ӣ������

                        $v_2['poUnitOne']= preg_replace('/(\w+)([S])/i', '${1}', $v_2['poUnit']);

                        $v_2["u8_info"]= $this->orderSer->getU8OrdListInfo($v_2['u8Code'],$v_2['complete'],$v_2['orderId']);



                        //$v_2["u8_info"]= $this->orderSer->getU8OrdListInfo($v_2['u8Code'],$v_2['complete'],$v_2['orderId']);

                        $v_2['u8_info']['dwxs']= !empty($v_2['u8_info']['dwxs'])?$v_2['u8_info']['dwxs']:1;



                        if($v_2["u8_info"]['mz']=='δ��ȡ') $v_2["u8_info"]['mz']=0.00;
                        if($v_2["u8_info"]['jz']=='δ��ȡ') $v_2["u8_info"]['jz']=0.00;
                        if($v_2["u8_info"]['dwtj']=='δ��ȡ') $v_2["u8_info"]['dwtj']=0.00;

                        $v_2["u8_info"]['mz']=floatval($v_2["c_dwmz"])!=0?floatval($v_2["c_dwmz"]):floatval($v_2["u8_info"]['mz']);
                        $v_2["u8_info"]['jz']=floatval($v_2["c_dwjz"])!=0?floatval($v_2["c_dwjz"]):floatval($v_2["u8_info"]['jz']);
                        $v_2["u8_info"]['dwtj']=floatval($v_2["c_dwtj"])!=0?floatval($v_2["c_dwtj"]):floatval($v_2["u8_info"]['dwtj']);
                        $v_2['u8_info']['info_type']=!empty($v_2["c_dwmz"])?"�ͻ�Ҫ��ֵ":$v_2['u8_info']['info_type'];

                        $v_2['u8_info']['dwxs']=empty($v_2["c_dwmz"])?$v_2['u8_info']['dwxs']:1;

                        $v_2['u8_info']['dwxs']=$v_2["complete"]=="���Ʒ"?$v_2['u8_info']['dwxs']:1;

                        $hsCodeInfo=array();
                        if(!empty($v_2["hsCodeName"])){
                            $hsCodeInfo=$this->productHscode->getInfo(array("hs_code_name"=>$v_2["hsCodeName"]));//��ȡHScode����
                        }



                        //������������
                        $tuopan=$this->sqlManage->sqlManageApi(array("unique_code"=>"tuopan_list","tpName"=>$v_2['tuoPan'],"limit"=>1));
                        $tuopan["weight"]=!empty($tuopan["weight"])?$tuopan["weight"]:20;
                        $v_2['tuopan_weight']=$v_2["tuopan_num"]*$tuopan["weight"];


                        $manuaInfo=$v_2;//���ֲ�����ֲ�
                        //�������ֲ�Ĳ�����

                        $mjzNum=0;
                        if($v_2['bhz']==0){

                            //echo $v_2["id"].":".$v_2["tuopan_num"]."||";


                            $total=$price_type=="2"&&$v_2['is_apportion']?$v_2['apportion_total']:$v_2['total_1'];//�жϼ۸����ͣ�1�ܼۣ�2��̯���ܼۣ�

                            $mjzNum=$v_2['boxNum'];


                        }else{


                            $mjzNum = floatval($v_2['hzBoxNum']);
                            //�ж��Ƿ�Ϊ�ֲ��ӡ
                            if($manua_type!="1"&&$v_2['poUnit']!="SETS"){
                                $mjzNum=(floatval($v_2['hzBoxNum'])-floatval($v_2['manua_box']));
                            }else{
                                $mjzNum=(floatval($v_2['boxNum'])-floatval($v_2['manua_box']));
                            }




                        }








                        //�ų���Ʒ����ʵë���ؼ���(��ʱ�ӵģ�����ɨ��û������Ҫȥ��)
                        if(empty($v_2["bhz"])&&$v_2['complete']=="��Ʒ"&&$v_2['category']!="BOPPĤ"){
                            $v_2["zs_mz"]=$v_2['mz'];
                            $v_2["zs_jz"]=$v_2['jz'];
                        }





                        //�ֲ��ֲ����
                        if($manua_type=="2"){
                            $manuaInfo['gd_number']=$v_1["gd_number"];//�ص���
                            //�������ֲ�Ĳ�Ʒ����
                            $manuaInfo['poUantity']=$v_2["manua_num"];

                            $manuaInfo["zs_dwjz"]=floatval($v_2['zs_jz']);
                            $manuaInfo["zs_dwmz"]=floatval($v_2['zs_mz']);

                            if(empty($v_2["bhz"])&&$v_2['complete']=="���Ʒ"){
                                if($v_2['category']!="PVC����"){
                                    $manuaInfo["zs_dwjz"]=floatval(($v_2["proUantity"]*$v_2['zs_jz']/$v_2['org_num']));
                                    $manuaInfo["zs_dwmz"]=floatval(($v_2["proUantity"]*$v_2['zs_mz']/$v_2['org_num']));
                                }
                            }


                            if($v_2["bhz"]==0){

                                $manuaInfo['boxNum']=($v_2["complete"]=="��Ʒ"||($v_2["category"]=="PVC����"&&!empty($v_2['onum']))||$v_2['hz']==1)?ceil(($manuaInfo['poUantity']*$v_2['huanSuanLv'])/$v_2['onum']):$manuaInfo['poUantity'];//��Ʒ���Ʒ����ת��
                                $manuaInfo['boxNum']= round($manuaInfo['boxNum']);

                                if($v_2["is_apportion"]){
                                    $manuaInfo['total']=$v_2['apportion_total']*($v_2["manua_num"]/$v_2["poUantity"]);//���ֲ��Ʒ�ܼ�
                                }else{
                                    $manuaInfo['total']=$manuaInfo['poUantity']*$v_2["poPrice"];//���ֲ��Ʒ�ܼ�
                                }



                                if(empty($v_2["c_dwmz"])&&$v_2['complete']=="���Ʒ"&&$v_2['category']!="PVC����"){
                                    $manuaInfo['u8_info']["dwmz"]=floatval(($v_2["proUantity"]*$v_2['mz']/$v_2['u8_info']['dwxs']/$v_2['org_num'])*$v_2['u8_info']['dwxs']);

                                }else{
                                    $manuaInfo['u8_info']["dwmz"]=floatval($v_2['u8_info']['mz'])*floatval($v_2['u8_info']['dwxs']);
                                }

                                if(empty($v_2["c_dwjz"])&&$v_2['complete']=="���Ʒ"&&$v_2['category']!="PVC����"){
                                    $manuaInfo['u8_info']["dwjz"]=floatval(($v_2["proUantity"]*$v_2['jz']/$v_2['u8_info']['dwxs']/$v_2['org_num'])*$v_2['u8_info']['dwxs']);
                                }else{
                                    $manuaInfo['u8_info']["dwjz"]=floatval($v_2['u8_info']['jz'])*floatval($v_2['u8_info']['dwxs']);
                                }


                                $manuaInfo['u8_info']['mz']=$manuaInfo['boxNum']*($manuaInfo['u8_info']["dwmz"]);//���ֲ�ë�أ�����*����λë��*ϵ������
                                $manuaInfo['u8_info']['jz']=$manuaInfo['boxNum']*($manuaInfo['u8_info']["dwjz"]);//���ֲΆ�أ�����*����λë��*ϵ������



                                $manuaInfo['zs_mz']=$manuaInfo['boxNum']*($manuaInfo["zs_dwmz"]);//���ֲ�ë�أ�����*����λë��*ϵ������
                                $manuaInfo['zs_jz']=$manuaInfo['boxNum']*($manuaInfo["zs_dwjz"]);//���ֲΆ�أ�����*����λë��*ϵ������




                                $manuaInfo['u8_info']['tj']=$manuaInfo['boxNum']*$v_2["u8_info"]['dwtj'];//���ֲΆ��(����*��λ���)
                                $manuaInfo['u8_info']['info_type']=$v_2["u8_info"]['info_type'];

                                $manuaInfo["lading_bill_name"]=!empty($hsCodeInfo["lading_bill_name"])?$hsCodeInfo["lading_bill_name"]:"";
                                $manuaInfo["grade_name"]=!empty($hsCodeInfo["grade_name"])?$hsCodeInfo["grade_name"]:"";
                                $manuaInfo['u8_info']["dwtj"]=$v_2['u8_info']['dwtj'];


                                $manuaInfo['u8_info']["dwxs"]=$v_2['u8_info']['dwxs'];
                                $manuaInfo['u8_info']["length"]=$v_2['u8_info']['length'];
                                $manuaInfo['u8_info']["height"]=$v_2['u8_info']['height'];
                                $manuaInfo['u8_info']["width"]=$v_2['u8_info']['width'];
                                $manuaInfo['u8_info']["formula"]=$v_2['u8_info']['length']."*".$v_2['u8_info']['width']."*".$v_2['u8_info']['height'];
                                $manuaInfo['tuopan_weight']=$v_2['tuopan_weight'];

                            }else{


                                if($v_2["is_apportion"]){
                                    $manuaInfo['total']=$v_2['apportion_total']*($v_2["manua_num"]/$v_2["poUantity"]);//���ֲ��Ʒ�ܼ�
                                }else{
                                    $manuaInfo['total']=$manuaInfo['poUantity']*$v_2["poPrice"];//���ֲ��Ʒ�ܼ�
                                }



                                //$manuaInfo['total']=$manuaInfo['poUantity']*$v_2["poPrice"];//���ֲ��Ʒ�ܼ�
                                $manuaInfo['u8_info']['mz']=$manuaInfo['manua_box']*($v_2['hz_u8_info']['mz']);//���ֲ�ë�أ�����*����λë��*ϵ������
                                $manuaInfo['u8_info']['jz']=$manuaInfo['manua_box']*($v_2['hz_u8_info']['jz']);//���ֲΆ�أ�����*����λë��*ϵ������



                                $manuaInfo['u8_info']['tj']=$manuaInfo['manua_box']*$v_2["u8_info"]['dwtj'];//���ֲΆ��(����*��λ���)
                                $manuaInfo['u8_info']['info_type']=$v_2["u8_info"]['info_type'];
                                $manuaInfo["grade_name"]=!empty($hsCodeInfo["grade_name"])?$hsCodeInfo["grade_name"]:"";
                                $manuaInfo["lading_bill_name"]=!empty($hsCodeInfo["lading_bill_name"])?$hsCodeInfo["lading_bill_name"]:"";

                                $manuaInfo['u8_info']["dwtj"]=$v_2['hz_u8_info']['dwtj'];
                                $manuaInfo['u8_info']["dwmz"]=$v_2['hz_u8_info']['mz']*$v_2['hz_u8_info']['dwxs'];
                                $manuaInfo['u8_info']["dwjz"]=$v_2['hz_u8_info']['jz']*$v_2['hz_u8_info']['dwxs'];
                                $manuaInfo['u8_info']["dwxs"]=$v_2['hz_u8_info']['dwxs'];
                                $manuaInfo['u8_info']["length"]=$v_2['hz_u8_info']['length'];
                                $manuaInfo['u8_info']["height"]=$v_2['hz_u8_info']['height'];
                                $manuaInfo['u8_info']["width"]=$v_2['hz_u8_info']['width'];
                                $manuaInfo['u8_info']["formula"]=$v_2['hz_u8_info']['length']."*".$v_2['hz_u8_info']['width']."*".$v_2['hz_u8_info']['height'];
                                $manuaInfo['tuopan_weight']=$v_2['tuopan_weight'];
                            }
                            if(!empty($manuaInfo['poUantity'])){
                                if($manuaInfo["poUantity"]<$manuaInfo['num']/2){
                                    $manuaInfo["tuopan_num"]=0;
                                    $manuaInfo['tuopan_weight']=0;
                                }

                                $manualArray[$v_1["manua"]]["productList"][]=$manuaInfo;
                            }

                        }

                    }











            }


        }else{//���ֲ�
            foreach ($billProducts['data'] as $key=>$val){



                $tupanTotal["tuopan_num"]+=round($val["tuopan_num"]);
                $tupanTotal["tuopan_weight"]+=round($val["tuopan_weight"]);



                if(in_array($val['category'], ["��ེ��"])){
                    if(in_array($val['model'], ["","���ֱ�"]) ){
                        $billProducts['data'][$key]["hsCode"]=$val["hsCode"]="4811410000";
                        $billProducts['data'][$key]["hsCodeName"]=$val["hsCodeName"]="SPIRAL CLEANING TAPE";
                    }else{
                        $billProducts['data'][$key]["hsCode"]=$val["hsCode"]="9603909090";
                        $billProducts['data'][$key]["hsCodeName"]=$val["hsCodeName"]="CLEANER TAPE";
                    }

                    if(strstr($val["customerCode"], "��ɫС����")){
                        $billProducts['data'][$key]["hsCode"]=$val["hsCode"]="3919909090";
                        $billProducts['data'][$key]["hsCodeName"]=$val["hsCodeName"]="CLOTH TAPE";
                    }

                    if(strstr($val["customerCode"], "ţƤֽ��ེ��")){
                        $billProducts['data'][$key]["hsCode"]=$val["hsCode"]="4811410000";
                        $billProducts['data'][$key]["hsCodeName"]=$val["hsCodeName"]="TAPE";
                    }

                }


                $val["en_desc"]=$val["podesc"].$val["poEdesc"];//Ӣ������

                $val['poUnitOne']= preg_replace('/(\w+)([S])/i', '${1}', $val['poUnit']);

                $val["u8_info"]= $this->orderSer->getU8OrdListInfo($val['u8Code'],$val['complete'],$val['orderId']);



                //$val["u8_info"]= $this->orderSer->getU8OrdListInfo($val['u8Code'],$val['complete'],$val['orderId']);

                $val['u8_info']['dwxs']= !empty($val['u8_info']['dwxs'])?$val['u8_info']['dwxs']:1;



                if($val["u8_info"]['mz']=='δ��ȡ') $val["u8_info"]['mz']=0.00;
                if($val["u8_info"]['jz']=='δ��ȡ') $val["u8_info"]['jz']=0.00;
                if($val["u8_info"]['dwtj']=='δ��ȡ') $val["u8_info"]['dwtj']=0.00;

                $val["u8_info"]['mz']=floatval($val["c_dwmz"])!=0?floatval($val["c_dwmz"]):floatval($val["u8_info"]['mz']);
                $val["u8_info"]['jz']=floatval($val["c_dwjz"])!=0?floatval($val["c_dwjz"]):floatval($val["u8_info"]['jz']);
                $val["u8_info"]['dwtj']=floatval($val["c_dwtj"])!=0?floatval($val["c_dwtj"]):floatval($val["u8_info"]['dwtj']);
                $val['u8_info']['info_type']=!empty($val["c_dwmz"])?"�ͻ�Ҫ��ֵ":$val['u8_info']['info_type'];

                $val['u8_info']['dwxs']=empty($val["c_dwmz"])?$val['u8_info']['dwxs']:1;

                $val['u8_info']['dwxs']=$val["complete"]=="���Ʒ"?$val['u8_info']['dwxs']:1;

                $hsCodeInfo=array();
                if(!empty($val["hsCodeName"])){
                    $hsCodeInfo=$this->productHscode->getInfo(array("hs_code_name"=>$val["hsCodeName"]));//��ȡHScode����
                }



                //������������
                $tuopan=$this->sqlManage->sqlManageApi(array("unique_code"=>"tuopan_list","tpName"=>$val['tuoPan'],"limit"=>1));
                $tuopan["weight"]=!empty($tuopan["weight"])?$tuopan["weight"]:20;
                $val['tuopan_weight']=$val["tuopan_num"]*$tuopan["weight"];


                $nomanuaInfo=$manuaInfo=$val;//���ֲ�����ֲ�
                //�������ֲ�Ĳ�����

                $mjzNum=0;
                if($val['bhz']==0){

                    //echo $val["id"].":".$val["tuopan_num"]."||";


                    $total=$price_type=="2"&&$val['is_apportion']?$val['apportion_total']:$val['total_1'];//�жϼ۸����ͣ�1�ܼۣ�2��̯���ܼۣ�



                    $nomanuaInfo['poUantity']=$manua_type=="1"?$val["poUantity"]:$val["poUantity"]-$val["manua_num"];//���ֲ��Ʒ����

                    if(empty($val["box_num"])&&!empty($val['onum'])){
                        $nomanuaInfo['boxNum']=($val["complete"]=="��Ʒ"||$val["category"]=="PVC����"||$val['hz']==1)?ceil(($nomanuaInfo['poUantity']*$val['huanSuanLv'])/$val['onum']):$nomanuaInfo['poUantity'];//��Ʒ���Ʒ����ת��
                    }else{
                        if($val["complete"]=="��Ʒ"||$val["category"]=="PVC����"||$val['hz']==1){
                            $nomanuaInfo['boxNum']=$val["box_num"];

                        }else{
                            $nomanuaInfo['boxNum']=$val["num"];

                        }
                    }




                    if($manua_type!="1"){
                        $nomanuaInfo['boxNum']=($val["complete"]=="��Ʒ"||($val["category"]=="PVC����"&&!empty($val['onum']))||$val['hz']==1)?ceil(($nomanuaInfo['poUantity']*$val['huanSuanLv'])/$val['onum']):$nomanuaInfo['poUantity'];//��Ʒ���Ʒ����ת��
                    }


                    //$nomanuaInfo['boxNum']=($val["complete"]=="��Ʒ"||($val["category"]=="PVC����"&&!empty($val['onum']))||$val['hz']==1)?ceil(($nomanuaInfo['poUantity']*$val['huanSuanLv'])/$val['onum']):$nomanuaInfo['poUantity'];//��Ʒ���Ʒ����ת��




                    $nomanuaInfo['boxNum']= ceil($nomanuaInfo['boxNum']);


                    if($price_type=="2"&&$val['is_apportion']){
                        $nomanuaInfo['total']=$nomanuaInfo['poUantity']*($total/$val["poUantity"]);
                    }else{
                        $nomanuaInfo['total']=$nomanuaInfo['poUantity']*$val["poPrice"];//���ֲ��Ʒ�ܼ�
                    }


                    $mjzNum=$nomanuaInfo['boxNum'];


                }else{

                    //$nomanuaInfo["poUantity"]=$val["poUantity"]= $val["proUantity"];//��װ��Ʒ��������

                    if($val['poUnit']!="SETS"){
                        if($val["poUantity"]>$val["manua_num"]){
                            $nomanuaInfo["poUantity"]=$val["poUantity"]= $val["poUantity"]-$val["manua_num"];//��װ��Ʒ��������
                        }
                    }else{

                        $nomanuaInfo["poUantity"]=$val["poUantity"]= $val["hzPoUantity"]-$val["manua_num"];//��װ��Ʒ��������
                    }





                    $nomanuaInfo['total']=$total=   $nomanuaInfo["poUantity"]*$val["poPrice"];




                    //echo $nomanuaInfo["poUantity"]."*".$val["poPrice"]."=".$nomanuaInfo['total']."||";

                    //$nomanuaInfo["poUantity"]=$val["poUantity"]= $val["hzBoxNum"];//��װ��Ʒ��������

                    $mjzNum = floatval($val['hzBoxNum']);
                    //�ж��Ƿ�Ϊ�ֲ��ӡ
                    if($manua_type!="1"&&$val['poUnit']!="SETS"){
                        $mjzNum=(floatval($val['hzBoxNum'])-floatval($val['manua_box']));
                    }else{
                        $mjzNum=(floatval($val['boxNum'])-floatval($val['manua_box']));
                    }


                    //$nomanuaInfo['u8_info']=$val['u8_info']=$val["hz_u8_info"];
                    $nomanuaInfo['u8_info']['mz']=$val['u8_info']['mz']=$val['hz_u8_info']['mz'];
                    $nomanuaInfo['u8_info']['jz']=$val['u8_info']['jz']=$val['hz_u8_info']['jz'];
                    $nomanuaInfo['u8_info']['dwtj']=$val['u8_info']['dwtj']=$val['hz_u8_info']['dwtj'];

                }


                if(empty($val["c_dwmz"])&&empty($val["bhz"])&&$val['complete']=="���Ʒ"){
                    if($val['category']=="PVC����"&&$val['omaterial'] != 'ľ��' && $val['cInvCode'] != '����ֽ��'){
                        //$nomanuaInfo['u8_info']["dwmz"]=$val['mz'];
                        $nomanuaInfo['u8_info']["dwmz"]=floatval($val['u8_info']['mz'])*floatval($val['u8_info']['dwxs']);
                    }else{
                        $nomanuaInfo['u8_info']["dwmz"]=floatval(($val["proUantity"]*$val['mz']/$val['boxNum']));
                    }
                }else{
                    if(!empty($val["c_dwmz"])){
                        $nomanuaInfo['u8_info']["dwmz"]=$val["c_dwmz"];
                    }else{
                        $nomanuaInfo['u8_info']["dwmz"]=floatval($val['u8_info']['mz'])*floatval($val['u8_info']['dwxs']);
                    }

                    //echo $val['u8_info']['mz']."||";
                }



                if(empty($val["c_dwjz"])&&empty($val["bhz"])&&$val['complete']=="���Ʒ"){
                    if($val['category']=="PVC����"&&$val['omaterial'] != 'ľ��' && $val['cInvCode'] != '����ֽ��'){
                        //$nomanuaInfo['u8_info']["dwjz"]=$val['jz'];
                        $nomanuaInfo['u8_info']["dwjz"]=floatval($val['u8_info']['jz'])*floatval($val['u8_info']['dwxs']);
                    }else{
                        $nomanuaInfo['u8_info']["dwjz"]=floatval(($val["proUantity"]*$val['jz']/$val['boxNum']));
                    }

                }else{

                    if(!empty($val["c_dwjz"])){
                        $nomanuaInfo['u8_info']["dwjz"]=$val["c_dwjz"];
                    }else{
                        $nomanuaInfo['u8_info']["dwjz"]=floatval($val['u8_info']['jz'])*floatval($val['u8_info']['dwxs']);
                    }
                }

                //�ų���Ʒ����ʵë���ؼ���(��ʱ�ӵģ�����ɨ��û������Ҫȥ��)
                if(empty($val["bhz"])&&$val['complete']=="��Ʒ"&&$val['category']!="BOPPĤ"){
                    $billProducts['data'][$key]['zs_mz']=$val["zs_mz"]=$val['mz'];
                    $billProducts['data'][$key]['zs_jz']=$val["zs_jz"]=$val['jz'];
                }




                $nomanuaInfo["zs_dwjz"]=floatval($val['zs_jz']);
                $nomanuaInfo["zs_dwmz"]=floatval($val['zs_mz']);


                if(empty($val["bhz"])&&$val['complete']=="���Ʒ"){
                    if($val['category']!="PVC����"){
                        $nomanuaInfo["zs_dwjz"]=floatval($val['zs_zjz']/$val['num']);
                        $nomanuaInfo["zs_dwmz"]=floatval($val['zs_zmz']/$val['num']);
                    }
                }




                if(!empty($deliverInfo)){
                    $deliverItem=$this->db->get_one("dev_provebill_deliver_item", "*", " deliver_id={$deliverInfo["id"]} and order_list_id={$val['id']} ");
                    //$billItem=$this->db->get_one("dev_provebill_product", "*", " provebill_id in ({$bill_id}) and order_list_id={$val['id']} ");
                    if(!empty($deliverItem)){
                        if(!empty($deliverItem['fnweights'])){
                            $nomanuaInfo["zs_dwjz"]=$val['zs_dwjz']=floatval($deliverItem['fnweights']/$val["num"]);
                        }
                        if(!empty($deliverItem['fgweights'])){
                            $nomanuaInfo["zs_dwmz"]=$val['zs_dwmz']=floatval($deliverItem['fgweights']/$val["num"]);
                        }
                    }
                }



                /*$nomanuaInfo['u8_info']["dwmz"]=$val['u8_info']['mz']*$val['u8_info']['dwxs'];
                $nomanuaInfo['u8_info']["dwjz"]=$val['u8_info']['jz']*$val['u8_info']['dwxs'];*/





                $nomanuaInfo['u8_info']['mz']=floatval($mjzNum)*($nomanuaInfo['u8_info']["dwmz"]);//���ֲ�ë�أ�����*����λë��*ϵ������
                $nomanuaInfo['u8_info']['jz']=floatval($mjzNum)*($nomanuaInfo['u8_info']["dwjz"]);//���ֲΆ�أ�����*����λë��*ϵ������
                $nomanuaInfo['u8_info']['tj']=floatval($mjzNum)*$val["u8_info"]['dwtj'];//���ֲ������(����*��λ���)




                $nomanuaInfo['zs_mz']=floatval($mjzNum)*($nomanuaInfo["zs_dwmz"]);//���ֲ�ë�أ�����*����λë��*ϵ������
                $nomanuaInfo['zs_jz']=floatval($mjzNum)*($nomanuaInfo["zs_dwjz"]);//���ֲΆ�أ�����*����λë��*ϵ������


                if($manua_type=="1"){
                    $nomanuaInfo['zs_mz']=$val["zs_zmz"];//���ֲ�ë�أ�����*����λë��*ϵ������
                    $nomanuaInfo['zs_jz']=$val["zs_zjz"];//���ֲΆ�أ�����*����λë��*ϵ������
                }


                $nomanuaInfo['u8_info']['info_type']=$val["u8_info"]['info_type'];
                $nomanuaInfo["lading_bill_name"]=!empty($hsCodeInfo["lading_bill_name"])?$hsCodeInfo["lading_bill_name"]:"";
                $nomanuaInfo["grade_name"]=!empty($hsCodeInfo["grade_name"])?$hsCodeInfo["grade_name"]:"";
                $nomanuaInfo['u8_info']["dwtj"]=$val['u8_info']['dwtj'];

                $nomanuaInfo['u8_info']["dwxs"]=$val['u8_info']['dwxs'];
                $nomanuaInfo['u8_info']["length"]=$val['u8_info']['length'];
                $nomanuaInfo['u8_info']["height"]=$val['u8_info']['height'];
                $nomanuaInfo['u8_info']["width"]=$val['u8_info']['width'];
                $nomanuaInfo['u8_info']["formula"]=$val['u8_info']['length']."*".$val['u8_info']['width']."*".$val['u8_info']['height'];
                $nomanuaInfo['tuopan_weight']=$val['tuopan_weight'];

                /*echo "<pre>";
                echo var_dump($nomanuaInfo['poUantity']);*/

                if(!empty($nomanuaInfo['poUantity'])){
                    if($nomanuaInfo["poUantity"]<$nomanuaInfo['num']/2){
                        $nomanuaInfo["tuopan_num"]=0;
                        $nomanuaInfo['tuopan_weight']=0;
                    }

                    $manualArray["no_manua"]["productList"][]=$nomanuaInfo;
                }



                //�ֲ��ֲ����
                if($manua_type=="2"&&!empty($val["manua"])&&!empty($val["manua_num"])){

                    //�������ֲ�Ĳ�Ʒ����
                    $manuaInfo['poUantity']=$val["manua_num"];

                    $manuaInfo["zs_dwjz"]=floatval($val['zs_jz']);
                    $manuaInfo["zs_dwmz"]=floatval($val['zs_mz']);

                    if(empty($val["bhz"])&&$val['complete']=="���Ʒ"){
                        if($val['category']!="PVC����"){
                            $manuaInfo["zs_dwjz"]=floatval(($val["proUantity"]*$val['zs_jz']/$val['org_num']));
                            $manuaInfo["zs_dwmz"]=floatval(($val["proUantity"]*$val['zs_mz']/$val['org_num']));
                        }
                    }


                    if($val["bhz"]==0){

                        $manuaInfo['boxNum']=($val["complete"]=="��Ʒ"||($val["category"]=="PVC����"&&!empty($val['onum']))||$val['hz']==1)?ceil(($manuaInfo['poUantity']*$val['huanSuanLv'])/$val['onum']):$manuaInfo['poUantity'];//��Ʒ���Ʒ����ת��
                        $manuaInfo['boxNum']= round($manuaInfo['boxNum']);

                        if($val["is_apportion"]){
                            $manuaInfo['total']=$val['apportion_total']*($val["manua_num"]/$val["poUantity"]);//���ֲ��Ʒ�ܼ�
                        }else{
                            $manuaInfo['total']=$manuaInfo['poUantity']*$val["poPrice"];//���ֲ��Ʒ�ܼ�
                        }



                        if(empty($val["c_dwmz"])&&$val['complete']=="���Ʒ"&&$val['category']!="PVC����"){
                            $manuaInfo['u8_info']["dwmz"]=floatval(($val["proUantity"]*$val['mz']/$val['u8_info']['dwxs']/$val['org_num'])*$val['u8_info']['dwxs']);

                        }else{
                            $manuaInfo['u8_info']["dwmz"]=floatval($val['u8_info']['mz'])*floatval($val['u8_info']['dwxs']);
                        }

                        if(empty($val["c_dwjz"])&&$val['complete']=="���Ʒ"&&$val['category']!="PVC����"){
                            $manuaInfo['u8_info']["dwjz"]=floatval(($val["proUantity"]*$val['jz']/$val['u8_info']['dwxs']/$val['org_num'])*$val['u8_info']['dwxs']);
                        }else{
                            $manuaInfo['u8_info']["dwjz"]=floatval($val['u8_info']['jz'])*floatval($val['u8_info']['dwxs']);
                        }


                        $manuaInfo['u8_info']['mz']=$manuaInfo['boxNum']*($manuaInfo['u8_info']["dwmz"]);//���ֲ�ë�أ�����*����λë��*ϵ������
                        $manuaInfo['u8_info']['jz']=$manuaInfo['boxNum']*($manuaInfo['u8_info']["dwjz"]);//���ֲΆ�أ�����*����λë��*ϵ������



                        $manuaInfo['zs_mz']=$manuaInfo['boxNum']*($manuaInfo["zs_dwmz"]);//���ֲ�ë�أ�����*����λë��*ϵ������
                        $manuaInfo['zs_jz']=$manuaInfo['boxNum']*($manuaInfo["zs_dwjz"]);//���ֲΆ�أ�����*����λë��*ϵ������




                        $manuaInfo['u8_info']['tj']=$manuaInfo['boxNum']*$val["u8_info"]['dwtj'];//���ֲΆ��(����*��λ���)
                        $manuaInfo['u8_info']['info_type']=$val["u8_info"]['info_type'];

                        $manuaInfo["lading_bill_name"]=!empty($hsCodeInfo["lading_bill_name"])?$hsCodeInfo["lading_bill_name"]:"";
                        $manuaInfo["grade_name"]=!empty($hsCodeInfo["grade_name"])?$hsCodeInfo["grade_name"]:"";
                        $manuaInfo['u8_info']["dwtj"]=$val['u8_info']['dwtj'];


                        $manuaInfo['u8_info']["dwxs"]=$val['u8_info']['dwxs'];
                        $manuaInfo['u8_info']["length"]=$val['u8_info']['length'];
                        $manuaInfo['u8_info']["height"]=$val['u8_info']['height'];
                        $manuaInfo['u8_info']["width"]=$val['u8_info']['width'];
                        $manuaInfo['u8_info']["formula"]=$val['u8_info']['length']."*".$val['u8_info']['width']."*".$val['u8_info']['height'];
                        $manuaInfo['tuopan_weight']=$val['tuopan_weight'];

                    }else{


                        if($val["is_apportion"]){
                            $manuaInfo['total']=$val['apportion_total']*($val["manua_num"]/$val["poUantity"]);//���ֲ��Ʒ�ܼ�
                        }else{
                            $manuaInfo['total']=$manuaInfo['poUantity']*$val["poPrice"];//���ֲ��Ʒ�ܼ�
                        }



                        //$manuaInfo['total']=$manuaInfo['poUantity']*$val["poPrice"];//���ֲ��Ʒ�ܼ�
                        $manuaInfo['u8_info']['mz']=$manuaInfo['manua_box']*($val['hz_u8_info']['mz']);//���ֲ�ë�أ�����*����λë��*ϵ������
                        $manuaInfo['u8_info']['jz']=$manuaInfo['manua_box']*($val['hz_u8_info']['jz']);//���ֲΆ�أ�����*����λë��*ϵ������



                        $manuaInfo['u8_info']['tj']=$manuaInfo['manua_box']*$val["u8_info"]['dwtj'];//���ֲΆ��(����*��λ���)
                        $manuaInfo['u8_info']['info_type']=$val["u8_info"]['info_type'];
                        $manuaInfo["grade_name"]=!empty($hsCodeInfo["grade_name"])?$hsCodeInfo["grade_name"]:"";
                        $manuaInfo["lading_bill_name"]=!empty($hsCodeInfo["lading_bill_name"])?$hsCodeInfo["lading_bill_name"]:"";

                        $manuaInfo['u8_info']["dwtj"]=$val['hz_u8_info']['dwtj'];
                        $manuaInfo['u8_info']["dwmz"]=$val['hz_u8_info']['mz']*$val['hz_u8_info']['dwxs'];
                        $manuaInfo['u8_info']["dwjz"]=$val['hz_u8_info']['jz']*$val['hz_u8_info']['dwxs'];
                        $manuaInfo['u8_info']["dwxs"]=$val['hz_u8_info']['dwxs'];
                        $manuaInfo['u8_info']["length"]=$val['hz_u8_info']['length'];
                        $manuaInfo['u8_info']["height"]=$val['hz_u8_info']['height'];
                        $manuaInfo['u8_info']["width"]=$val['hz_u8_info']['width'];
                        $manuaInfo['u8_info']["formula"]=$val['hz_u8_info']['length']."*".$val['hz_u8_info']['width']."*".$val['hz_u8_info']['height'];
                        $manuaInfo['tuopan_weight']=$val['tuopan_weight'];
                    }
                    if(!empty($manuaInfo['poUantity'])){
                        if($manuaInfo["poUantity"]<$manuaInfo['num']/2){
                            $manuaInfo["tuopan_num"]=0;
                            $manuaInfo['tuopan_weight']=0;
                        }

                        $manualArray[$val["manua"]]["productList"][]=$manuaInfo;
                    }

                }



            }
        }



        $productList=array();




        foreach ($manualArray as $k=>$v){



            $poPriceAll=$totalNum=$totalMoney=$count=0;

            $productList[$k]=array("count"=>0,"totalMoney"=>0,"poUnit"=>"","totalNum"=>0,'products'=>array(),"productItem"=>array());

            $typeArray=array("complete"=>array(),"tuoPan"=>array(),"category"=>array(),"hzBoxNum"=>array());

            $bhzArray=array();

            foreach ($v["productList"] as $key=>$val){



                if(!empty($val["zs_mz"])&&$val["zs_mz"]!='.00'&&empty($val["c_dwmz"])) $val["u8_info"]['mz']=$val["zs_mz"];//ȡʵ��ë��
                if(!empty($val["zs_jz"])&&$val["zs_jz"]!='.00'&&empty($val["c_dwjz"])) $val["u8_info"]['jz']=$val["zs_jz"];//ȡʵ�ʾ���



                if(!empty($val["zs_dwmz"])&&empty($val["c_dwmz"])) $val["u8_info"]['dwmz']=$val["zs_dwmz"];//ȡʵ�ʵ�λë��

                if(!empty($val["zs_dwjz"])&&empty($val["c_dwjz"])) $val["u8_info"]['dwjz']=$val["zs_dwjz"];//ȡʵ�ʵ�λ����




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


                if(in_array($print_type, array("1","3"))){



                    $val["po"]=!empty($val["po"])?$val["po"]:$val["listpo"];
                    $val["po"]=!in_array($val["po"], ["","��"])?"PO:".$val["po"]:"";
                    $val["po"]=$val["po"]."\n".$val["orderNum"];
                    if($val["hz"]==1){
                        /*foreach ($v["productList"] as $key_1=>$val_1){
                            if($val_1["bhz"]==$val["id"]){
                                $val["u8_info"]['mz']+=$val_1["u8_info"]['mz'];
                                $val["u8_info"]['jz']+=$val_1["u8_info"]['jz'];
                                $val["u8_info"]['tj']+=$val_1["u8_info"]['tj'];
                            }
                        }*/
                    }



                    if($val["bhz"]==0){

                        $val["poPrice"]=$val['total']/$val['poUantity'];//���¼��㵥��

                        //echo $val['poUantity']."||";

                        $productList[$k]['products'][$val["po"]][]=$val;
                        $productList[$k]['count']++;

                        $productList[$k]['totalMoney']+=$val['total'];
                        $productList[$k]['total_mz']+= round($val["u8_info"]['mz'],2)+$val['tuopan_weight'];
                        $productList[$k]['total_jz']+= round($val["u8_info"]['jz'],2);
                        $productList[$k]['total_tj']+= round($val["u8_info"]['tj'],2);
                        $productList[$k]['totalNum']+=$val['poUantity'];
                        $productList[$k]['totalBoxNum']+=$val['boxNum'];
                        $productList[$k]['total_tuopan_num']+=$val['tuopan_num'];
                        $productList[$k]['total_tuopan_weight']+=$val['tuopan_weight'];

                    }

                }else{


                    //echo  $val["id"]."#".$val["bhz"]."#".$val["poUnit"]."||";



                    //if($val["bhz"]!=0&&$val["poUnit"]=="SETS") continue;
                    $poPriceAll+=$val['poPrice'];

                    $hsCodeName=$val["hsCodeName"] = empty($val["hsCodeName"])?"NULL-VALUE":$val["hsCodeName"];
                    if($val["bhz"]!=0){
                        //$val["hsCodeName"]="A_".$val['bhz'].$hsCodeName;
                        $val["hsCodeName"]=$hsCodeName;
                    }



                    $productList[$k]['products'][$val["hsCodeName"]]['hsCodeName']=$hsCodeName;
                    $productList[$k]['products'][$val["hsCodeName"]]['hsCode']=$val['hsCode'];
                    $productList[$k]['products'][$val["hsCodeName"]]['grade_name']=$val['grade_name'];
                    $productList[$k]['products'][$val["hsCodeName"]]['lading_bill_name']=$val['lading_bill_name'];
                    if(!empty($val["bhz"])){
                        if(!in_array($val["bhz"], $bhzArray[$val["hsCodeName"]])){
                            $productList[$k]['products'][$val["hsCodeName"]]['poUantity']+=$val['hzPoUantity'];
                            $bhzArray[$val["hsCodeName"]][]=$val["bhz"];
                        }
                    }else{
                        $productList[$k]['products'][$val["hsCodeName"]]['poUantity']+=$val['poUantity'];
                    }

                    $productList[$k]['products'][$val["hsCodeName"]]['poUnit']=$val['poUnit'];
                    $productList[$k]['products'][$val["hsCodeName"]]['total']+=$val['total'];



                    $productList[$k]['products'][$val["hsCodeName"]]['poUnitOne']=$val['poUnitOne'];
                    $productList[$k]['products'][$val["hsCodeName"]]['boxNum']+=$val['boxNum'];


                    $productList[$k]['products'][$val["hsCodeName"]]['poPrice']=$productList[$k]['products'][$val["hsCodeName"]]['total']/$productList[$k]['products'][$val["hsCodeName"]]['poUantity'];
                    //$productList[$k]['products'][$val["hsCodeName"]]['poPrice1']=$productList[$k]['products'][$val["hsCodeName"]]['total']/$productList[$k]['products'][$val["hsCodeName"]]['poUantity'];
                    $productList[$k]['products'][$val["hsCodeName"]]['bhz']=$val['bhz'];

                    $productList[$k]['products'][$val["hsCodeName"]]['color']=$val['color'];



                    if(empty($productList[$k]['products'][$val["hsCodeName"]]['u8_info'])){
                        $productList[$k]['products'][$val["hsCodeName"]]['u8_info']=array('mz'=>0.00,'jz'=>0.00,'tj'=>0.00,"is_real"=>array(),"no_real"=>array());
                    }




                    if(!empty($val["u8_info"])){

                        $productList[$k]['products'][$val["hsCodeName"]]['u8_info']['mz']+= round($val["u8_info"]['mz'],2);
                        $productList[$k]['products'][$val["hsCodeName"]]['u8_info']['jz']+= round($val["u8_info"]['jz'],2);
                        $productList[$k]['products'][$val["hsCodeName"]]['u8_info']['tj']+= round($val["u8_info"]['tj'],2);

                        if($val["u8_info"]['is_real']){
                            $productList[$k]['products'][$val["hsCodeName"]]['u8_info']['is_real'][]=$val["u8Code"];
                        }else{
                            $productList[$k]['products'][$val["hsCodeName"]]['u8_info']['is_real'][]=$val["u8Code"];
                        }
                    }

                    $productList[$k]['count']++;




                    $productList[$k]['totalMoney']+=$val['total'];
                    $productList[$k]['total_mz']+=round($val["u8_info"]['mz'],2)+$val['tuopan_weight'];
                    $productList[$k]['total_jz']+=round($val["u8_info"]['jz'],2);
                    $productList[$k]['total_tj']+=round($val["u8_info"]['tj'],2);
                    $productList[$k]['totalNum']+=$val['poUantity'];
                    $productList[$k]['totalBoxNum']+=$val['boxNum'];
                    $productList[$k]['total_tuopan_num']+=$val['tuopan_num'];
                    $productList[$k]['total_tuopan_weight']+=$val['tuopan_weight'];
                    $productList[$k]["productItem"][$val["orderId"]][]=$val;

                }




            }


            if(count($typeArray["complete"])==1&&$typeArray["complete"][0]=="��Ʒ"){
                $poUnit="CTNS";
            }else if(count($typeArray["complete"])==1&&$typeArray["complete"][0]=="���Ʒ"){
                $poUnit="ROLLS";
                if(count($typeArray["category"])==1&&$typeArray["category"][0]=="PVC����"){
                    $poUnit="CTNS";
                }else if(count($typeArray["category"])>0&& in_array("PVC����", $typeArray["category"])){
                    $poUnit="PKGS";
                }
            }else{
                $poUnit="PKGS";
            }



            /*if(count($typeArray["tuoPan"])==1&&$typeArray["complete"][0]=="��������"){ //ȫ��������
                $poUnit="ctns";
            }else if(!in_array("��������", $typeArray["tuoPan"])){ //ȫ����
                $poUnit="ptls";
            }else{  //��ȫ����
                $poUnit="ctns";
            }*/



            /*if($k=="no_manua"){
                $productList[$k]['total_tuopan_num']=$tupanTotal['tuopan_num'];
                $productList[$k]['total_tuopan_weight']=$tupanTotal['tuopan_weight'];
            }*/


            $productList[$k]['poUnit']=$poUnit;

        }



        $retInfo["accSetInfo"]=$accSetInfo;             //������Ϣ
        $retInfo["accCtrInfo"]=$accCtrInfo;             //ó�׹�˾������Ϣ

        $retInfo["billInfo"]=$billInfo;                 //������Ϣ
        $retInfo["cabinetInfo"]=!empty($cabinetInfo)?$cabinetInfo:array();                 //װ����Ϣ
        $retInfo["billProducts"]=$billProducts;         //��Ʒ�б�
        $retInfo["productList"]=$productList;           //��Ʒ��ӡ�б�


        /*echo "<pre>";
        echo var_dump($productItem);
        exit;*/

        $retInfo["mark"]="";
        $markInfo = $this->msdb->select_one("Customer", "*", " cCusCode='{$billInfo["customer_code"]}' ");
        if(!empty($markInfo)&&is_array($markInfo)){
            $retInfo["mark"]=$markInfo["cCusDefine1"];
        }

        return $retInfo;

    }

    /**
     * ��ӡ��Ʊ�������
     * @param type $params
     * @return boolean
     */
    public function printData_p($params){
        $retInfo=array();        
        $bill_id=!empty($params["bill_id"])?$params["bill_id"]:$params["bill_ids"]; //���յ�ID
        $print_type=$params["print_type"]; //��������
        $price_type=$params["price_type"]; //�۸����ͣ�1.�ܼۣ�2.��̯�۸�
        $manua_type=$params["manua_type"]; //�Ƿ��ֲ��ӡ
        $adjust_type=!empty($params["adjust_type"])?$params["adjust_type"]:0; //�Ƿ����
        $bill_adjust=!empty($params["bill_adjust"])?$params["bill_adjust"]:1;
        
        
    
        $billInfo=$this->provebill->getBillInfo(array("id"=>$bill_id));//��ȡ��������
        $billProducts = $this->orderSer->getOrderProductList(array("provebill_id"=>$bill_id,"bhz"=>0,"order_by_id"=>"ASC","limit"=>999)); //��ȡ���ղ�Ʒ�б� ȡ[data]
        $cabinetInfo=$this->db->get_one("dev_provebill_cabinet", "*", " bill_id in ({$bill_id}) ");
        $deliverInfo=$this->db->get_one("dev_provebill_deliver", "*", " bill_ids in ({$bill_id}) ");
        

        $this->provebill->productHandle($billProducts["data"]);

        $product_split_list = string::autoCharset(json_decode(string::autoCharset($billInfo['product_list'], 'gbk', 'utf-8'),true), 'utf-8', 'gbk');//����ֲ�

 
        $accSetInfo=$this->accountSet->getInfo(array("account_type"=>$billInfo["account_type"])); //��ȡ������Ϣ
        $accCtrInfo=array();
        if(!empty($billInfo["account_ctr_id"])){
           $accCtrInfo=$this->sqlManage->sqlManageApi(array("unique_code"=>"account_ctr_list","id"=>$billInfo["account_ctr_id"],"limit"=>1));   //��ȡó�׹�˾������Ϣ 
        }
        
       
        
        if(empty($billProducts['data'])) return false;
        $poUnit=$billProducts['data'][0]["poUnit"];
        
       
    
        //���յ�������ֵ
        if($bill_adjust==2){  
            $fieldArray=["is_apportion","apportion_price","apportion_total","num",'box_num',"mz_1","jz_1","tj_1","total_1","po_price"];
            foreach ($billProducts['data'] as $key=>$val){
                if(empty($val['provebill_id']))continue;
                $adjustInfo=$this->db->get_one("dev_provebill_product_adjust", "*", " order_list_id={$val['id']} and provebill_id={$val['provebill_id']} order by id ASC ");
                if(empty($adjustInfo))   continue;
                foreach ($fieldArray as $k=>$v){
                    $billProducts['data'][$key][$v]=$adjustInfo[$v];
                }
            }
        }
        
        
        //�ֲ�����ӳ���
        $product_split_map=array();$manua_split_map=array();
        //��ȡ�в�ֵ����ֲ�����
        $array_no_manua= array();

        if(!empty($product_split_list)){


            foreach ($product_split_list as $key=>$val){
                if(empty($val["product_list"])) continue;
                foreach ($val["product_list"] as $k=>$v){
                    $v['gd_number']=$val['gd_number'];//��ȡ�ص���
                    $product_split_map[$val["manua"]][$v["id"]]=$v;
                    $manua_split_map[$v["id"]][$val["manua"]]=$v;

                    $array_no_manua[$v['id']]['total_manua_num']+=$v['manua_num'];//�����Ʒ���ֲ�����

                }
     
            }

        }

        $array_no_manua_keys = array_keys($array_no_manua);




        //�ּ��װ��Ʒ�������װ��Ʒ�۸�ë����
        foreach ($billProducts['data'] as $key=>$val){

            if(in_array($val['id'],$array_no_manua_keys)){
                $billProducts['data'][$key]['no_manua_num'] = ($val['num']-$array_no_manua[$val['id']]['total_manua_num']); //���ֲ��Ʒ������

            }else{
                $billProducts['data'][$key]['no_manua_num'] = $val['num']; //���ֲ��Ʒ������

            }
            
            //�޸����ֲ�����
            $splitPoUantity=!empty($product_split_map["no_manua"][$val["id"]])?$product_split_map["no_manua"][$val["id"]]["manua_num"]:0;
            $billProducts['data'][$key]["manua_num"]=$val["manua_num"]=$val["num"]-$splitPoUantity;

            $billProducts['data'][$key]["poPrice"]=$val["poPrice"]=$val["poPrice1"];
            $billProducts['data'][$key]["proPrice"]=$val["proPrice"]=$val["proPrice1"];
            
            $billProducts['data'][$key]["poPrice"]=$val["poPrice"]= doubleval($val["po_price"])>0?$val["po_price"]:$val["poPrice"] ;
          
            
            $billProducts['data'][$key]["poPrice"]=$val['poPrice']= $val['frignPrice']==0?$val['poPrice']:$val['frignPrice']; //�����������
   
            $poPrice=!empty($val["po_price"])?$val["po_price"]:$billProducts['data'][$key]["poPrice"];
            $billProducts['data'][$key]["apportion_total"]=$val["apportion_total"]= floatval($poPrice*$val["num"]+$val["apportion_price"]);
            
            
            //echo $val["poPrice"]=$val["poPrice1"]."||";
            
            if(empty($val['huanSuanLv'])) $billProducts['data'][$key]["huanSuanLv"]=$val['huanSuanLv']=1;
            
            if(empty($val["poUantity"])){
                unset($billProducts['data'][$key]);
                continue;
            }
            
           
       
            
            $billProducts['data'][$key]["isAll"]=$val["isAll"] =$isAll=false; 
            if($val["poUantity"]<=$val["num"]){
                $billProducts['data'][$key]["isAll"]=$val["isAll"]=$isAll=true;
            }
             

            $billProducts['data'][$key]["poUantity"]=$val["poUantity"]=$val["num"];//������
            
          
      
       
            
            //$billProducts['data'][$key]["poPrice"]=$val['poPrice']= $val['po_price']==0?$val['poPrice']:$val['po_price']; //�����������
            
            $billProducts['data'][$key]["poPrice"]=$val['poPrice']= $val['frignPrice']==0?$val['poPrice']:$val['frignPrice']; //�����������
            $billProducts['data'][$key]["total"]=$val['total']= $val['frignTotal']==0?$val['total']:$val['frignTotal']; //��������ܼ�
              
      
            if($val["hz"]==1){
                if(empty($val["hzNumSum"])){
                    $billProducts['data'][$key]["hzNumSum"] = $val["hzNumSum"] = array_sum(array_filter(explode(",", $val["hzNum"]) ));
                }
                
                $billProducts['data'][$key]["u8_info"]=$val["u8_info"]= $this->orderSer->getU8OrdListInfo($val['u8Code'],$val['complete'],$val['orderId']);
                
                
                
                
                $billProducts['data'][$key]["u8_info"]['mz']=$val["u8_info"]["mz"]=floatval($val["c_dwmz"])!=0?floatval($val["c_dwmz"]):floatval($val["u8_info"]['mz']);
                $billProducts['data'][$key]["u8_info"]['jz']=$val["u8_info"]["jz"]=floatval($val["c_dwjz"])!=0?floatval($val["c_dwjz"]):floatval($val["u8_info"]['jz']);
                $billProducts['data'][$key]["u8_info"]['dwtj']=$val["u8_info"]["dwtj"]=floatval($val["c_dwtj"])!=0?floatval($val["c_dwtj"]):floatval($val["u8_info"]['dwtj']);

               
                
                $val['u8_info']['dwxs']=$val["complete"]=="���Ʒ"?$val['u8_info']['dwxs']:1;
                
                if(empty($val["box_num"])){
                    $billProducts['data'][$key]["boxNum"]=$val["boxNum"]= ceil(round($val['num'])*$val['huanSuanLv']/floatval($val['onum'])); //�Զ�������
                }else{
                    $billProducts['data'][$key]["boxNum"]=$val["boxNum"]=$val["box_num"];
                }
                
                
     
                $hzProducts = $this->orderSer->getOrderProductList(array("hz"=>0,"bhz"=>$val["id"],"limit"=>999)); //��ȡ��װ��Ʒ
                
                
                $hzPrice=$price_type=="2"&&$val['is_apportion']?$val['apportion_total']:$billProducts['data'][$key]["total_1"];
                
               
                
                if(!empty($hzProducts['data'])){
                    
                    $billProducts['data'][$key]["hsCode"]=$hzProducts['data'][0]["hsCode"];
                    $billProducts['data'][$key]["hsCodeName"]=$hzProducts['data'][0]["hsCodeName"];
 
                    if($val['poUnit']!="SETS"){
                        
                        $unitProRoll=$val['proUantity']/$val['hzNumSum'];//��λ��װ����
                        
                        
                        if(empty($val["hzNumSum"])){
                            $unitProRoll=1;
                        }
                        
                        
                        
                        $allRolls=0;
                        $billProducts['data'][$key]["complete"]=$hzProducts['data'][0]["complete"];
                        if(in_array($print_type, ['2','4','6','5'])){

                            
                            
                            unset($billProducts['data'][$key]);
                            
                            
                            //$hzProducts['data'][$k]["proUantity"]=$v["proUantity"]=$v["poUantity"];

                            foreach ($hzProducts['data'] as $k=>$v){
                                $allRolls+=$v["proUantity"];
                            } 
                            
                            
                            
                            /*$hzMethodArray=array("hzids"=>array_filter(explode(",", $val['hzids'])),"hzNum"=>array_filter(explode(",", $val['hzNum'])));
                            $hzMethod=array();
                            if(!empty($hzMethodArray["hzids"])){
                                foreach ($hzMethodArray["hzids"] as $k=>$v){
                                    $hzMethod[$v]=$hzMethodArray["hzNum"][$k];
                                }
                            }*/
                            
                            
                            $total_box_num=0;
                            //��װ��Ʒ����̯
                            foreach ($hzProducts['data'] as $k=>$v){
                                
                                $hzProducts['data'][$k]["poPrice"]=$v['poPrice']= $v['po_price']==0?$v['poPrice']:$v['po_price']; //�����������
                                $hzProducts['data'][$k]["poPrice"]=$v['poPrice']= $v['frignPrice']==0?$v['poPrice']:$v['frignPrice']; //�����������
                                $hzProducts['data'][$k]["total"]=$v['total']= $v['frignTotal']==0?$v['total']:$v['frignTotal']; //��������ܼ�
                                
                                $hzProducts['data'][$k]["total_1"]=$hzPrice*($v["proUantity"]/$allRolls);
                                //$hzProducts['data'][$k]["boxNum"]=$val["poUantity"]*($v["proUantity"]/$allRolls);

                                $hzProducts['data'][$k]["poPrice"]=$hzPrice*($v["proUantity"]/$allRolls)/$v["poUantity"];
                                
        
                                $hzProducts['data'][$k]["poUnit"]="ROLLS";
                                $hzProducts['data'][$k]["boxNum"]=0;
                                
            
                                if($k==0) {    
                                    $hzProducts['data'][$k]["boxNum"]=$val["boxNum"];
                                    $hzProducts['data'][$k]["tuopan_num"]=$val["tuopan_num"];
                                    $hzProducts['data'][$k]["tuopan_weight"]=$val["tuopan_weight"];     
                                } 
      
                                if(!empty($v["huanSuanLv"])){
                                    $hzProducts['data'][$k]["boxNum"]=round($val["boxNum"]*($v['huanSuanLv']/$val['hzNumSum']));
                                    $total_box_num+=$hzProducts['data'][$k]["boxNum"];
                                }else if(!empty($v["onum"])){
                                    $hzProducts['data'][$k]["boxNum"]=round($val["boxNum"]*($v['onum']/$val['hzNumSum']));
                                    $total_box_num+=$hzProducts['data'][$k]["boxNum"];
                                }
                                
                                //$hzProducts['data'][$k]["boxNum"] = intval($hzProducts['data'][$k]["boxNum"]);
                               
                                
                                
                                $hzProducts['data'][$k]["manua"]=$val["manua"];

                                if(!empty($val["hzNumSum"])){
                                    $hzProducts['data'][$k]["hzPoUantity"]=(floatval($val["poUantity"])/floatval($val["hzNumSum"])*(floatval($v["proUantity"])/floatval($unitProRoll)));
                                    $hzProducts['data'][$k]["hzBoxNum"]=(floatval($val["boxNum"])/floatval($val["hzNumSum"])*(floatval($v["proUantity"])/floatval($unitProRoll)));
                                    $hzProducts['data'][$k]["manua_num"]=(floatval($val["manua_num"])/floatval($val["hzNumSum"])*(floatval($v["proUantity"])/floatval($unitProRoll)));
                                    
                                    
                                }else{
                                    $hzProducts['data'][$k]["hzPoUantity"]=$v["proUantity"];
                                    $hzProducts['data'][$k]["hzBoxNum"]=$v["boxNum"];
                                    $hzProducts['data'][$k]["manua_num"]=$val["manua_num"];
                                }
                        
                                $hzProducts['data'][$k]["manua_box"]=$hzProducts['data'][$k]["manua_num"]*$val['huanSuanLv']/floatval($val['onum']);

                                $hzProducts['data'][$k]["hz_u8_info"]=$val["u8_info"];
                    
                            }
                            
                            
                            
                            
                            //������ֵ
                            $hzProducts['data'][0]["boxNum"]+=$val["boxNum"]-$total_box_num;
                            
                            
                        }
                        
                        foreach ($hzProducts['data'] as $k=>$v){ 
                            $billProducts['data'][]=$v;
                        }
                        
                    }
                    
                    
   
                    
                }
                
            }
            
           
         
            
        }
        

        $apportion_total=0;
        
     
        
 
        //������������
        $tupanTotal=array("tuopan_num"=>0,"tuopan_weight"=>0);
        
       
        //�ֲ�������ݼ���
        $manualArray=array();//print_r($billProducts);print_r($product_split_map);exit;
        foreach ($billProducts['data'] as $key=>$val){
             

            
            $tupanTotal["tuopan_num"]+=round($val["tuopan_num"]);
            $tupanTotal["tuopan_weight"]+=round($val["tuopan_weight"]);
            
         
            
            if(in_array($val['category'], ["��ེ��"])){
                if(in_array($val['model'], ["","���ֱ�"]) ){   
                    $billProducts['data'][$key]["hsCode"]=$val["hsCode"]="4811410000";
                    $billProducts['data'][$key]["hsCodeName"]=$val["hsCodeName"]="SPIRAL CLEANING TAPE";   
                }else{
                    $billProducts['data'][$key]["hsCode"]=$val["hsCode"]="9603909090";
                    $billProducts['data'][$key]["hsCodeName"]=$val["hsCodeName"]="CLEANER TAPE";
                }
                
                if(strstr($val["customerCode"], "��ɫС����")){
                    $billProducts['data'][$key]["hsCode"]=$val["hsCode"]="3919909090";
                    $billProducts['data'][$key]["hsCodeName"]=$val["hsCodeName"]="CLOTH TAPE";
                }
                
                 if(strstr($val["customerCode"], "ţƤֽ��ེ��")){
                    $billProducts['data'][$key]["hsCode"]=$val["hsCode"]="4811410000";
                    $billProducts['data'][$key]["hsCodeName"]=$val["hsCodeName"]="TAPE";
                }
                
            }
            

            $val["en_desc"]=$val["podesc"].$val["poEdesc"];//Ӣ������

            $val['poUnitOne']= preg_replace('/(\w+)([S])/i', '${1}', $val['poUnit']);
            
            $val["u8_info"]= $this->orderSer->getU8OrdListInfo($val['u8Code'],$val['complete'],$val['orderId']);

           
  
            //$val["u8_info"]= $this->orderSer->getU8OrdListInfo($val['u8Code'],$val['complete'],$val['orderId']);
   
            $val['u8_info']['dwxs']= !empty($val['u8_info']['dwxs'])?$val['u8_info']['dwxs']:1;
            
            
    
            if($val["u8_info"]['mz']=='δ��ȡ') $val["u8_info"]['mz']=0.00;
            if($val["u8_info"]['jz']=='δ��ȡ') $val["u8_info"]['jz']=0.00;
            if($val["u8_info"]['dwtj']=='δ��ȡ') $val["u8_info"]['dwtj']=0.00;

            $val["u8_info"]['mz']=floatval($val["c_dwmz"])!=0?floatval($val["c_dwmz"]):floatval($val["u8_info"]['mz']);
            $val["u8_info"]['jz']=floatval($val["c_dwjz"])!=0?floatval($val["c_dwjz"]):floatval($val["u8_info"]['jz']);
            $val["u8_info"]['dwtj']=floatval($val["c_dwtj"])!=0?floatval($val["c_dwtj"]):floatval($val["u8_info"]['dwtj']);
            $val['u8_info']['info_type']=!empty($val["c_dwmz"])?"�ͻ�Ҫ��ֵ":$val['u8_info']['info_type'];

            $val['u8_info']['dwxs']=empty($val["c_dwmz"])?$val['u8_info']['dwxs']:1;
            
            $val['u8_info']['dwxs']=$val["complete"]=="���Ʒ"?$val['u8_info']['dwxs']:1;
            
            $hsCodeInfo=array();
            if(!empty($val["hsCodeName"])){
                $hsCodeInfo=$this->productHscode->getInfo(array("hs_code_name"=>$val["hsCodeName"]));//��ȡHScode����
            }

        
            
            //������������
            $tuopan=$this->sqlManage->sqlManageApi(array("unique_code"=>"tuopan_list","tpName"=>$val['tuoPan'],"limit"=>1));
            $tuopan["weight"]=!empty($tuopan["weight"])?$tuopan["weight"]:20;
            $val['tuopan_weight']=$val["tuopan_num"]*$tuopan["weight"];
           
           
   
            
            $nomanuaInfo=$manuaInfo=$val;//���ֲ�����ֲ�
            //�������ֲ�Ĳ�����
           
            $mjzNum=0;
            if($val['bhz']==0){

                //echo $val["id"].":".$val["tuopan_num"]."||";
                

                $total=$price_type=="2"&&$val['is_apportion']?$val['apportion_total']:$val['total_1'];//�жϼ۸����ͣ�1�ܼۣ�2��̯���ܼۣ�
                
                
                
                $nomanuaInfo['poUantity']=$manua_type=="1"?$val["poUantity"]:$val["no_manua_num"];//���ֲ��Ʒ����$val["poUantity"]-$val["manua_num"]
                
                if(empty($val["box_num"])&&!empty($val['onum'])){
                    $nomanuaInfo['boxNum']=($val["complete"]=="��Ʒ"||$val["category"]=="PVC����"||$val['hz']==1)?ceil(($nomanuaInfo['poUantity']*$val['huanSuanLv'])/$val['onum']):$nomanuaInfo['poUantity'];//��Ʒ���Ʒ����ת��                
                }else{
                    if($val["complete"]=="��Ʒ"||$val["category"]=="PVC����"||$val['hz']==1){
                        $nomanuaInfo['boxNum']=$val["box_num"];
                        
                    }else{
                        $nomanuaInfo['boxNum']=$val["num"];
                        
                    }
                }
                
                 
                
                
                if($manua_type!="1"){
                    $nomanuaInfo['boxNum']=($val["complete"]=="��Ʒ"||($val["category"]=="PVC����"&&!empty($val['onum']))||$val['hz']==1)?ceil(($nomanuaInfo['poUantity']*$val['huanSuanLv'])/$val['onum']):$nomanuaInfo['poUantity'];//��Ʒ���Ʒ����ת��
                }
                
                
                //$nomanuaInfo['boxNum']=($val["complete"]=="��Ʒ"||($val["category"]=="PVC����"&&!empty($val['onum']))||$val['hz']==1)?ceil(($nomanuaInfo['poUantity']*$val['huanSuanLv'])/$val['onum']):$nomanuaInfo['poUantity'];//��Ʒ���Ʒ����ת��   
                
               
                  
                   
                $nomanuaInfo['boxNum']= ceil($nomanuaInfo['boxNum']);
                
                
                if($price_type=="2"&&$val['is_apportion']){
                    $nomanuaInfo['total']=$nomanuaInfo['poUantity']*($total/$val["poUantity"]);
                }else{
                    $nomanuaInfo['total']=$nomanuaInfo['poUantity']*$val["poPrice"];//���ֲ��Ʒ�ܼ�
                }
                
       
                $mjzNum=$nomanuaInfo['boxNum'];
                
      
            }else{
               
                //$nomanuaInfo["poUantity"]=$val["poUantity"]= $val["proUantity"];//��װ��Ʒ��������    
                
                if($val['poUnit']!="SETS"){
                    if($val["poUantity"]>$val["manua_num"]){
                        $nomanuaInfo["poUantity"]=$val["poUantity"]= $val["poUantity"]-$val["manua_num"];//��װ��Ʒ��������  
                    }
                }else{
                    
                    $nomanuaInfo["poUantity"]=$val["poUantity"]= $val["hzPoUantity"]-$val["manua_num"];//��װ��Ʒ��������  
                }
                
                
               
                
                
                $nomanuaInfo['total']=$total=   $nomanuaInfo["poUantity"]*$val["poPrice"];
                
                
                
                
                //echo $nomanuaInfo["poUantity"]."*".$val["poPrice"]."=".$nomanuaInfo['total']."||";
                
                //$nomanuaInfo["poUantity"]=$val["poUantity"]= $val["hzBoxNum"];//��װ��Ʒ��������   
                
                $mjzNum = floatval($val['hzBoxNum']);
                //�ж��Ƿ�Ϊ�ֲ��ӡ
                if($manua_type!="1"&&$val['poUnit']!="SETS"){
                    $mjzNum=(floatval($val['hzBoxNum'])-floatval($val['manua_box']));
                }else{
                    $mjzNum=(floatval($val['boxNum'])-floatval($val['manua_box']));
                }
               
                
                //$nomanuaInfo['u8_info']=$val['u8_info']=$val["hz_u8_info"];
                $nomanuaInfo['u8_info']['mz']=$val['u8_info']['mz']=$val['hz_u8_info']['mz'];
                $nomanuaInfo['u8_info']['jz']=$val['u8_info']['jz']=$val['hz_u8_info']['jz'];
                $nomanuaInfo['u8_info']['dwtj']=$val['u8_info']['dwtj']=$val['hz_u8_info']['dwtj'];

            }
            
   
            if(empty($val["c_dwmz"])&&empty($val["bhz"])&&$val['complete']=="���Ʒ"){
                if(($val['category']=="PVC����"||$val['category']=="BOPPĤ")&&$val['omaterial'] != 'ľ��' && $val['cInvCode'] != '����ֽ��'){
                    //$nomanuaInfo['u8_info']["dwmz"]=$val['mz'];
                    $nomanuaInfo['u8_info']["dwmz"]=floatval($val['u8_info']['mz'])*floatval($val['u8_info']['dwxs']);
                }else{
                    $nomanuaInfo['u8_info']["dwmz"]=floatval(($val["proUantity"]*$val['mz']/$val['boxNum']));   
                }    
            }else{
                if(!empty($val["c_dwmz"])){
                    $nomanuaInfo['u8_info']["dwmz"]=$val["c_dwmz"];
                }else{
                    $nomanuaInfo['u8_info']["dwmz"]=floatval($val['u8_info']['mz'])*floatval($val['u8_info']['dwxs']);
                }
                
                //echo $val['u8_info']['mz']."||";
            }
           


            if(empty($val["c_dwjz"])&&empty($val["bhz"])&&$val['complete']=="���Ʒ"){
                if(($val['category']=="PVC����"||$val['category']=="BOPPĤ")&&$val['omaterial'] != 'ľ��' && $val['cInvCode'] != '����ֽ��'){
                    //$nomanuaInfo['u8_info']["dwjz"]=$val['jz'];
                    $nomanuaInfo['u8_info']["dwjz"]=floatval($val['u8_info']['jz'])*floatval($val['u8_info']['dwxs']);
                }else{
                    $nomanuaInfo['u8_info']["dwjz"]=floatval(($val["proUantity"]*$val['jz']/$val['boxNum']));  
                }  
                
            }else{
                
                if(!empty($val["c_dwjz"])){
                    $nomanuaInfo['u8_info']["dwjz"]=$val["c_dwjz"];
                }else{
                    $nomanuaInfo['u8_info']["dwjz"]=floatval($val['u8_info']['jz'])*floatval($val['u8_info']['dwxs']);
                }
            }
            
            //�ų���Ʒ����ʵë���ؼ���(��ʱ�ӵģ�����ɨ��û������Ҫȥ��)
            if(empty($val["bhz"])&&$val['complete']=="��Ʒ"&&$val['category']!="BOPPĤ"){
                $billProducts['data'][$key]['zs_mz']=$val["zs_mz"]=$val['mz'];
                $billProducts['data'][$key]['zs_jz']=$val["zs_jz"]=$val['jz'];
            }
            
            //echo $val["zs_jz"]."||";



            $nomanuaInfo["zs_dwjz"]=floatval($val['zs_jz']);
            $nomanuaInfo["zs_dwmz"]=floatval($val['zs_mz']);

            


            if(empty($val["bhz"])&&$val['complete']=="���Ʒ"){
                if($val['category']!="PVC����"){
                    $nomanuaInfo["zs_dwjz"]=floatval($val['zs_zjz']/$val['num']);  
                    $nomanuaInfo["zs_dwmz"]=floatval($val['zs_zmz']/$val['num']);  
                }
            }
            
           
            
       
            if(!empty($deliverInfo)){
                $deliverItem=$this->db->get_one("dev_provebill_deliver_item", "*", " deliver_id={$deliverInfo["id"]} and order_list_id={$val['id']} ");
                //$billItem=$this->db->get_one("dev_provebill_product", "*", " provebill_id in ({$bill_id}) and order_list_id={$val['id']} ");
                if(!empty($deliverItem)){
                    if(!empty($deliverItem['fnweights'])){
                        $nomanuaInfo["zs_dwjz"]=$val['zs_dwjz']=floatval($deliverItem['fnweights']/$val["num"]);  
                    }
                    if(!empty($deliverItem['fgweights'])){
                        $nomanuaInfo["zs_dwmz"]=$val['zs_dwmz']=floatval($deliverItem['fgweights']/$val["num"]); 
                    }
                }
            }
            
          
    
            /*$nomanuaInfo['u8_info']["dwmz"]=$val['u8_info']['mz']*$val['u8_info']['dwxs'];
            $nomanuaInfo['u8_info']["dwjz"]=$val['u8_info']['jz']*$val['u8_info']['dwxs'];*/
            
            
           
           
    
            $nomanuaInfo['u8_info']['mz']=floatval($mjzNum)*($nomanuaInfo['u8_info']["dwmz"]);//���ֲ�ë�أ�����*����λë��*ϵ������
            $nomanuaInfo['u8_info']['jz']=floatval($mjzNum)*($nomanuaInfo['u8_info']["dwjz"]);//���ֲΆ�أ�����*����λë��*ϵ������
            $nomanuaInfo['u8_info']['tj']=floatval($mjzNum)*$val["u8_info"]['dwtj'];//���ֲ������(����*��λ���)
            
            
            
           
            $nomanuaInfo['zs_mz']=floatval($mjzNum)*($nomanuaInfo["zs_dwmz"]);//���ֲ�ë�أ�����*����λë��*ϵ������
            $nomanuaInfo['zs_jz']=floatval($mjzNum)*($nomanuaInfo["zs_dwjz"]);//���ֲΆ�أ�����*����λë��*ϵ������
            

             if($manua_type=="1"){
                $nomanuaInfo['zs_mz']=$val["zs_zmz"];//���ֲ�ë�أ�����*����λë��*ϵ������
                $nomanuaInfo['zs_jz']=$val["zs_zjz"];//���ֲΆ�أ�����*����λë��*ϵ������
            }
            
            
            $nomanuaInfo['u8_info']['info_type']=$val["u8_info"]['info_type'];
            $nomanuaInfo["lading_bill_name"]=!empty($hsCodeInfo["lading_bill_name"])?$hsCodeInfo["lading_bill_name"]:"";
            $nomanuaInfo["grade_name"]=!empty($hsCodeInfo["grade_name"])?$hsCodeInfo["grade_name"]:"";
            $nomanuaInfo['u8_info']["dwtj"]=$val['u8_info']['dwtj'];

            $nomanuaInfo['u8_info']["dwxs"]=$val['u8_info']['dwxs'];
            $nomanuaInfo['u8_info']["length"]=$val['u8_info']['length'];
            $nomanuaInfo['u8_info']["height"]=$val['u8_info']['height'];
            $nomanuaInfo['u8_info']["width"]=$val['u8_info']['width'];
            $nomanuaInfo['u8_info']["formula"]=$val['u8_info']['length']."*".$val['u8_info']['width']."*".$val['u8_info']['height'];
            $nomanuaInfo['tuopan_weight']=$val['tuopan_weight'];

            /*echo "<pre>";
            echo var_dump($nomanuaInfo['poUantity']);*/
            
            if(!empty($nomanuaInfo['poUantity'])){  
                if($nomanuaInfo["poUantity"]<$nomanuaInfo['num']/2){
                    $nomanuaInfo["tuopan_num"]=0;
                    $nomanuaInfo['tuopan_weight']=0;
                }   
                
                $manualArray["no_manua"]["productList"][]=$nomanuaInfo;
            }
            
            
           
            
            if($manua_type=="2"){
                
  
                if(!empty($manua_split_map)){
                    if(empty($manua_split_map[$val["id"]]))  continue;
                    foreach ($manua_split_map[$val["id"]] as $k=>$v){
    
                        
                        //�������ֲ�Ĳ�Ʒ����
                        //$manuaInfo['poUantity']=$val["manua_num"];
                        $manuaInfo['poUantity']=$v["manua_num"];
                        $manuaInfo['gd_number']=$v["gd_number"];
       
                        $manuaInfo["zs_dwjz"]=floatval($val['zs_jz']);
                        $manuaInfo["zs_dwmz"]=floatval($val['zs_mz']);

                        if(empty($val["bhz"])&&$val['complete']=="���Ʒ"){
                            if($val['category']!="PVC����"){
                                $manuaInfo["zs_dwjz"]=floatval(($val["proUantity"]*$val['zs_jz']/$val['org_num']));  
                                $manuaInfo["zs_dwmz"]=floatval(($val["proUantity"]*$val['zs_mz']/$val['org_num']));  
                            }
                        }


                        if($val["bhz"]==0){

                            $manuaInfo['boxNum']=($val["complete"]=="��Ʒ"||($val["category"]=="PVC����"&&!empty($val['onum']))||$val['hz']==1)?ceil(($manuaInfo['poUantity']*$val['huanSuanLv'])/$val['onum']):$manuaInfo['poUantity'];//��Ʒ���Ʒ����ת��
                            $manuaInfo['boxNum']= round($manuaInfo['boxNum']);

                            if($val["is_apportion"]){
                                $manuaInfo['total']=$val['apportion_total']*($manuaInfo['poUantity']/$val["poUantity"]);//���ֲ��Ʒ�ܼ�
                            }else{
                                $manuaInfo['total']=$manuaInfo['poUantity']*$val["poPrice"];//���ֲ��Ʒ�ܼ�
                            }



                            if(empty($val["c_dwmz"])&&$val['complete']=="���Ʒ"&&$val['category']!="PVC����"){
                                $manuaInfo['u8_info']["dwmz"]=floatval(($val["proUantity"]*$val['mz']/$val['u8_info']['dwxs']/$val['org_num'])*$val['u8_info']['dwxs']); 

                            }else{
                                $manuaInfo['u8_info']["dwmz"]=floatval($val['u8_info']['mz'])*floatval($val['u8_info']['dwxs']);
                            }

                            if(empty($val["c_dwjz"])&&$val['complete']=="���Ʒ"&&$val['category']!="PVC����"){
                                $manuaInfo['u8_info']["dwjz"]=floatval(($val["proUantity"]*$val['jz']/$val['u8_info']['dwxs']/$val['org_num'])*$val['u8_info']['dwxs']);  
                            }else{
                                $manuaInfo['u8_info']["dwjz"]=floatval($val['u8_info']['jz'])*floatval($val['u8_info']['dwxs']);
                            }


                            $manuaInfo['u8_info']['mz']=$manuaInfo['boxNum']*($manuaInfo['u8_info']["dwmz"]);//���ֲ�ë�أ�����*����λë��*ϵ������
                            $manuaInfo['u8_info']['jz']=$manuaInfo['boxNum']*($manuaInfo['u8_info']["dwjz"]);//���ֲΆ�أ�����*����λë��*ϵ������



                            $manuaInfo['zs_mz']=$manuaInfo['boxNum']*($manuaInfo["zs_dwmz"]);//���ֲ�ë�أ�����*����λë��*ϵ������
                            $manuaInfo['zs_jz']=$manuaInfo['boxNum']*($manuaInfo["zs_dwjz"]);//���ֲΆ�أ�����*����λë��*ϵ������




                            $manuaInfo['u8_info']['tj']=$manuaInfo['boxNum']*$val["u8_info"]['dwtj'];//���ֲΆ��(����*��λ���)
                            $manuaInfo['u8_info']['info_type']=$val["u8_info"]['info_type'];

                            $manuaInfo["lading_bill_name"]=!empty($hsCodeInfo["lading_bill_name"])?$hsCodeInfo["lading_bill_name"]:"";
                            $manuaInfo["grade_name"]=!empty($hsCodeInfo["grade_name"])?$hsCodeInfo["grade_name"]:"";
                            $manuaInfo['u8_info']["dwtj"]=$val['u8_info']['dwtj'];


                            $manuaInfo['u8_info']["dwxs"]=$val['u8_info']['dwxs'];
                            $manuaInfo['u8_info']["length"]=$val['u8_info']['length'];
                            $manuaInfo['u8_info']["height"]=$val['u8_info']['height'];
                            $manuaInfo['u8_info']["width"]=$val['u8_info']['width'];
                            $manuaInfo['u8_info']["formula"]=$val['u8_info']['length']."*".$val['u8_info']['width']."*".$val['u8_info']['height'];
                            $manuaInfo['tuopan_weight']=$val['tuopan_weight'];

                        }else{


                            if($val["is_apportion"]){
                                $manuaInfo['total']=$val['apportion_total']*($manuaInfo['poUantity']/$val["poUantity"]);//���ֲ��Ʒ�ܼ�
                            }else{
                                $manuaInfo['total']=$manuaInfo['poUantity']*$val["poPrice"];//���ֲ��Ʒ�ܼ�
                            }



                            //$manuaInfo['total']=$manuaInfo['poUantity']*$val["poPrice"];//���ֲ��Ʒ�ܼ�
                            $manuaInfo['u8_info']['mz']=$manuaInfo['manua_box']*($val['hz_u8_info']['mz']);//���ֲ�ë�أ�����*����λë��*ϵ������
                            $manuaInfo['u8_info']['jz']=$manuaInfo['manua_box']*($val['hz_u8_info']['jz']);//���ֲΆ�أ�����*����λë��*ϵ������



                            $manuaInfo['u8_info']['tj']=$manuaInfo['manua_box']*$val["u8_info"]['dwtj'];//���ֲΆ��(����*��λ���)
                            $manuaInfo['u8_info']['info_type']=$val["u8_info"]['info_type'];
                            $manuaInfo["grade_name"]=!empty($hsCodeInfo["grade_name"])?$hsCodeInfo["grade_name"]:"";
                            $manuaInfo["lading_bill_name"]=!empty($hsCodeInfo["lading_bill_name"])?$hsCodeInfo["lading_bill_name"]:"";

                            $manuaInfo['u8_info']["dwtj"]=$val['hz_u8_info']['dwtj'];
                            $manuaInfo['u8_info']["dwmz"]=$val['hz_u8_info']['mz']*$val['hz_u8_info']['dwxs'];
                            $manuaInfo['u8_info']["dwjz"]=$val['hz_u8_info']['jz']*$val['hz_u8_info']['dwxs'];
                            $manuaInfo['u8_info']["dwxs"]=$val['hz_u8_info']['dwxs'];
                            $manuaInfo['u8_info']["length"]=$val['hz_u8_info']['length'];
                            $manuaInfo['u8_info']["height"]=$val['hz_u8_info']['height'];
                            $manuaInfo['u8_info']["width"]=$val['hz_u8_info']['width'];
                            $manuaInfo['u8_info']["formula"]=$val['hz_u8_info']['length']."*".$val['hz_u8_info']['width']."*".$val['hz_u8_info']['height'];
                            $manuaInfo['tuopan_weight']=$val['tuopan_weight'];
                        }
                        if(!empty($manuaInfo['poUantity'])){
                            if($manuaInfo["poUantity"]<$manuaInfo['num']/2){
                                $manuaInfo["tuopan_num"]=0;
                                $manuaInfo['tuopan_weight']=0;
                            } 

                            $manualArray[$k]["productList"][]=$manuaInfo;
                        }

                    }
                }else if(!empty($val["manua"])&&!empty($val["manua_num"])){
                    //�������ֲ�Ĳ�Ʒ����
                    $manuaInfo['poUantity']=$val["manua_num"];

                    $manuaInfo["zs_dwjz"]=floatval($val['zs_jz']);
                    $manuaInfo["zs_dwmz"]=floatval($val['zs_mz']);

                    if(empty($val["bhz"])&&$val['complete']=="���Ʒ"){
                        if($val['category']!="PVC����"){
                            $manuaInfo["zs_dwjz"]=floatval(($val["proUantity"]*$val['zs_jz']/$val['org_num']));  
                            $manuaInfo["zs_dwmz"]=floatval(($val["proUantity"]*$val['zs_mz']/$val['org_num']));  
                        }
                    }


                    if($val["bhz"]==0){

                        $manuaInfo['boxNum']=($val["complete"]=="��Ʒ"||($val["category"]=="PVC����"&&!empty($val['onum']))||$val['hz']==1)?ceil(($manuaInfo['poUantity']*$val['huanSuanLv'])/$val['onum']):$manuaInfo['poUantity'];//��Ʒ���Ʒ����ת��
                        $manuaInfo['boxNum']= round($manuaInfo['boxNum']);

                        if($val["is_apportion"]){
                            $manuaInfo['total']=$val['apportion_total']*($val["manua_num"]/$val["poUantity"]);//���ֲ��Ʒ�ܼ�
                        }else{
                            $manuaInfo['total']=$manuaInfo['poUantity']*$val["poPrice"];//���ֲ��Ʒ�ܼ�
                        }



                        if(empty($val["c_dwmz"])&&$val['complete']=="���Ʒ"&&$val['category']!="PVC����"){
                            $manuaInfo['u8_info']["dwmz"]=floatval(($val["proUantity"]*$val['mz']/$val['u8_info']['dwxs']/$val['org_num'])*$val['u8_info']['dwxs']); 

                        }else{
                            $manuaInfo['u8_info']["dwmz"]=floatval($val['u8_info']['mz'])*floatval($val['u8_info']['dwxs']);
                        }

                        if(empty($val["c_dwjz"])&&$val['complete']=="���Ʒ"&&$val['category']!="PVC����"){
                            $manuaInfo['u8_info']["dwjz"]=floatval(($val["proUantity"]*$val['jz']/$val['u8_info']['dwxs']/$val['org_num'])*$val['u8_info']['dwxs']);  
                        }else{
                            $manuaInfo['u8_info']["dwjz"]=floatval($val['u8_info']['jz'])*floatval($val['u8_info']['dwxs']);
                        }


                        $manuaInfo['u8_info']['mz']=$manuaInfo['boxNum']*($manuaInfo['u8_info']["dwmz"]);//���ֲ�ë�أ�����*����λë��*ϵ������
                        $manuaInfo['u8_info']['jz']=$manuaInfo['boxNum']*($manuaInfo['u8_info']["dwjz"]);//���ֲΆ�أ�����*����λë��*ϵ������



                        $manuaInfo['zs_mz']=$manuaInfo['boxNum']*($manuaInfo["zs_dwmz"]);//���ֲ�ë�أ�����*����λë��*ϵ������
                        $manuaInfo['zs_jz']=$manuaInfo['boxNum']*($manuaInfo["zs_dwjz"]);//���ֲΆ�أ�����*����λë��*ϵ������




                        $manuaInfo['u8_info']['tj']=$manuaInfo['boxNum']*$val["u8_info"]['dwtj'];//���ֲΆ��(����*��λ���)
                        $manuaInfo['u8_info']['info_type']=$val["u8_info"]['info_type'];

                        $manuaInfo["lading_bill_name"]=!empty($hsCodeInfo["lading_bill_name"])?$hsCodeInfo["lading_bill_name"]:"";
                        $manuaInfo["grade_name"]=!empty($hsCodeInfo["grade_name"])?$hsCodeInfo["grade_name"]:"";
                        $manuaInfo['u8_info']["dwtj"]=$val['u8_info']['dwtj'];


                        $manuaInfo['u8_info']["dwxs"]=$val['u8_info']['dwxs'];
                        $manuaInfo['u8_info']["length"]=$val['u8_info']['length'];
                        $manuaInfo['u8_info']["height"]=$val['u8_info']['height'];
                        $manuaInfo['u8_info']["width"]=$val['u8_info']['width'];
                        $manuaInfo['u8_info']["formula"]=$val['u8_info']['length']."*".$val['u8_info']['width']."*".$val['u8_info']['height'];
                        $manuaInfo['tuopan_weight']=$val['tuopan_weight'];

                    }else{


                        if($val["is_apportion"]){
                            $manuaInfo['total']=$val['apportion_total']*($val["manua_num"]/$val["poUantity"]);//���ֲ��Ʒ�ܼ�
                        }else{
                            $manuaInfo['total']=$manuaInfo['poUantity']*$val["poPrice"];//���ֲ��Ʒ�ܼ�
                        }



                        //$manuaInfo['total']=$manuaInfo['poUantity']*$val["poPrice"];//���ֲ��Ʒ�ܼ�
                        $manuaInfo['u8_info']['mz']=$manuaInfo['manua_box']*($val['hz_u8_info']['mz']);//���ֲ�ë�أ�����*����λë��*ϵ������
                        $manuaInfo['u8_info']['jz']=$manuaInfo['manua_box']*($val['hz_u8_info']['jz']);//���ֲΆ�أ�����*����λë��*ϵ������



                        $manuaInfo['u8_info']['tj']=$manuaInfo['manua_box']*$val["u8_info"]['dwtj'];//���ֲΆ��(����*��λ���)
                        $manuaInfo['u8_info']['info_type']=$val["u8_info"]['info_type'];
                        $manuaInfo["grade_name"]=!empty($hsCodeInfo["grade_name"])?$hsCodeInfo["grade_name"]:"";
                        $manuaInfo["lading_bill_name"]=!empty($hsCodeInfo["lading_bill_name"])?$hsCodeInfo["lading_bill_name"]:"";

                        $manuaInfo['u8_info']["dwtj"]=$val['hz_u8_info']['dwtj'];
                        $manuaInfo['u8_info']["dwmz"]=$val['hz_u8_info']['mz']*$val['hz_u8_info']['dwxs'];
                        $manuaInfo['u8_info']["dwjz"]=$val['hz_u8_info']['jz']*$val['hz_u8_info']['dwxs'];
                        $manuaInfo['u8_info']["dwxs"]=$val['hz_u8_info']['dwxs'];
                        $manuaInfo['u8_info']["length"]=$val['hz_u8_info']['length'];
                        $manuaInfo['u8_info']["height"]=$val['hz_u8_info']['height'];
                        $manuaInfo['u8_info']["width"]=$val['hz_u8_info']['width'];
                        $manuaInfo['u8_info']["formula"]=$val['hz_u8_info']['length']."*".$val['hz_u8_info']['width']."*".$val['hz_u8_info']['height'];
                        $manuaInfo['tuopan_weight']=$val['tuopan_weight'];
                    }
                    if(!empty($manuaInfo['poUantity'])){
                        if($manuaInfo["poUantity"]<$manuaInfo['num']/2){
                            $manuaInfo["tuopan_num"]=0;
                            $manuaInfo['tuopan_weight']=0;
                        } 

                        $manualArray[$val["manua"]]["productList"][]=$manuaInfo;
                    }





                }
                
               
               
                
                

                
                
            }
            
            

        }


        $productList=array();
        
       
     
        foreach ($manualArray as $k=>$v){
            
        
            
            $poPriceAll=$totalNum=$totalMoney=$count=0;
            
            $productList[$k]=array("count"=>0,"totalMoney"=>0,"poUnit"=>"","totalNum"=>0,'products'=>array(),"productItem"=>array());
            
            $typeArray=array("complete"=>array(),"tuoPan"=>array(),"category"=>array(),"hzBoxNum"=>array());
            
            $bhzArray=array();
    
            foreach ($v["productList"] as $key=>$val){
                
               
                
                if(!empty($val["zs_mz"])&&$val["zs_mz"]!='.00'&&empty($val["c_dwmz"])) $val["u8_info"]['mz']=$val["zs_mz"];//ȡʵ��ë��
                if(!empty($val["zs_jz"])&&$val["zs_jz"]!='.00'&&empty($val["c_dwjz"])) $val["u8_info"]['jz']=$val["zs_jz"];//ȡʵ�ʾ���
                
               
                
                if(!empty($val["zs_dwmz"])&&empty($val["c_dwmz"])) $val["u8_info"]['dwmz']=$val["zs_dwmz"];//ȡʵ�ʵ�λë��
                
                if(!empty($val["zs_dwjz"])&&empty($val["c_dwjz"])) $val["u8_info"]['dwjz']=$val["zs_dwjz"];//ȡʵ�ʵ�λ����
                
                
               
                
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
                
                              
                if(in_array($print_type, array("1","3"))){
                    
                   
         
                    $val["po"]=!empty($val["po"])?$val["po"]:$val["listpo"];
                    $val["po"]=!in_array($val["po"], ["","��"])?"PO:".$val["po"]:"";
                    $val["po"]=$val["po"]."\n".$val["orderNum"];
                    if($val["hz"]==1){
                        /*foreach ($v["productList"] as $key_1=>$val_1){
                            if($val_1["bhz"]==$val["id"]){
                                $val["u8_info"]['mz']+=$val_1["u8_info"]['mz'];
                                $val["u8_info"]['jz']+=$val_1["u8_info"]['jz'];
                                $val["u8_info"]['tj']+=$val_1["u8_info"]['tj'];
                            }
                        }*/
                    }  
                    
                    
                    
                    if($val["bhz"]==0){

                        $val["poPrice"]=$val['total']/$val['poUantity'];//���¼��㵥��
                        
                        //echo $val['poUantity']."||";
                        
                        $productList[$k]['products'][$val["po"]][]=$val;
                        $productList[$k]['count']++;
               
                        $productList[$k]['totalMoney']+=$val['total'];
                        $productList[$k]['total_mz']+= round($val["u8_info"]['mz'],2)+$val['tuopan_weight'];
                        $productList[$k]['total_jz']+= round($val["u8_info"]['jz'],2);
                        $productList[$k]['total_tj']+= round($val["u8_info"]['tj'],2);
                        $productList[$k]['totalNum']+=$val['poUantity']; 
                        $productList[$k]['totalBoxNum']+=$val['boxNum'];  
                        $productList[$k]['total_tuopan_num']+=$val['tuopan_num'];
                        $productList[$k]['total_tuopan_weight']+=$val['tuopan_weight'];
                       
                    }
                    
                }else{
                    
                    
                    //echo  $val["id"]."#".$val["bhz"]."#".$val["poUnit"]."||";
                    
                  
                    
                    //if($val["bhz"]!=0&&$val["poUnit"]=="SETS") continue;
                    $poPriceAll+=$val['poPrice'];
                    
                    $hsCodeName=$val["hsCodeName"] = empty($val["hsCodeName"])?"NULL-VALUE":$val["hsCodeName"];
                    if($val["bhz"]!=0){
                        //$val["hsCodeName"]="A_".$val['bhz'].$hsCodeName;
                        $val["hsCodeName"]=$hsCodeName;
                    }
                    
                  
                    
                    $productList[$k]['products'][$val["hsCodeName"]]['hsCodeName']=$hsCodeName;
                    $productList[$k]['products'][$val["hsCodeName"]]['hsCode']=$val['hsCode'];
                    $productList[$k]['products'][$val["hsCodeName"]]['grade_name']=$val['grade_name'];
                    $productList[$k]['products'][$val["hsCodeName"]]['lading_bill_name']=$val['lading_bill_name'];
                    if(!empty($val["bhz"])){
                        if(!in_array($val["bhz"], $bhzArray[$val["hsCodeName"]])){
                            $productList[$k]['products'][$val["hsCodeName"]]['poUantity']+=$val['hzPoUantity'];
                            $bhzArray[$val["hsCodeName"]][]=$val["bhz"];
                        }
                    }else{
                        $productList[$k]['products'][$val["hsCodeName"]]['poUantity']+=$val['poUantity']; 
                    }

                    $productList[$k]['products'][$val["hsCodeName"]]['poUnit']=$val['poUnit'];
                    $productList[$k]['products'][$val["hsCodeName"]]['total']+=$val['total'];
                   
                    
                    
                    $productList[$k]['products'][$val["hsCodeName"]]['poUnitOne']=$val['poUnitOne'];
                    $productList[$k]['products'][$val["hsCodeName"]]['boxNum']+=$val['boxNum'];
           
                    
                    $productList[$k]['products'][$val["hsCodeName"]]['poPrice']=$productList[$k]['products'][$val["hsCodeName"]]['total']/$productList[$k]['products'][$val["hsCodeName"]]['poUantity'];
                    //$productList[$k]['products'][$val["hsCodeName"]]['poPrice1']=$productList[$k]['products'][$val["hsCodeName"]]['total']/$productList[$k]['products'][$val["hsCodeName"]]['poUantity'];
                    $productList[$k]['products'][$val["hsCodeName"]]['bhz']=$val['bhz'];
                    
                    $productList[$k]['products'][$val["hsCodeName"]]['color']=$val['color'];
                    
                    
           
                    if(empty($productList[$k]['products'][$val["hsCodeName"]]['u8_info'])){
                        $productList[$k]['products'][$val["hsCodeName"]]['u8_info']=array('mz'=>0.00,'jz'=>0.00,'tj'=>0.00,"is_real"=>array(),"no_real"=>array());
                    }
                    
                    
                    
                    
                    if(!empty($val["u8_info"])){
                        
                        $productList[$k]['products'][$val["hsCodeName"]]['u8_info']['mz']+= round($val["u8_info"]['mz'],2);
                        $productList[$k]['products'][$val["hsCodeName"]]['u8_info']['jz']+= round($val["u8_info"]['jz'],2);
                        $productList[$k]['products'][$val["hsCodeName"]]['u8_info']['tj']+= round($val["u8_info"]['tj'],2);                        

                        if($val["u8_info"]['is_real']){
                            $productList[$k]['products'][$val["hsCodeName"]]['u8_info']['is_real'][]=$val["u8Code"];
                        }else{
                            $productList[$k]['products'][$val["hsCodeName"]]['u8_info']['is_real'][]=$val["u8Code"];
                        }
                    }
                    
                    $productList[$k]['count']++;
                    
                  
                     
                    
                    $productList[$k]['totalMoney']+=$val['total'];
                    $productList[$k]['total_mz']+=round($val["u8_info"]['mz'],2)+$val['tuopan_weight']; 
                    $productList[$k]['total_jz']+=round($val["u8_info"]['jz'],2);
                    $productList[$k]['total_tj']+=round($val["u8_info"]['tj'],2);
                    $productList[$k]['totalNum']+=$val['poUantity']; 
                    $productList[$k]['totalBoxNum']+=$val['boxNum'];  
                    $productList[$k]['total_tuopan_num']+=$val['tuopan_num'];
                    $productList[$k]['total_tuopan_weight']+=$val['tuopan_weight'];
                    $productList[$k]["productItem"][$val["orderId"]][]=$val;
                   
                }

                
                
    
            }
            

            if(count($typeArray["complete"])==1&&$typeArray["complete"][0]=="��Ʒ"){
                $poUnit="CTNS";
            }else if(count($typeArray["complete"])==1&&$typeArray["complete"][0]=="���Ʒ"){
                $poUnit="ROLLS";
                if(count($typeArray["category"])==1&&$typeArray["category"][0]=="PVC����"){
                    $poUnit="CTNS";
                }else if(count($typeArray["category"])>0&& in_array("PVC����", $typeArray["category"])){
                    $poUnit="PKGS";
                }
            }else{
                $poUnit="PKGS";
            }
            
           
            
            /*if(count($typeArray["tuoPan"])==1&&$typeArray["complete"][0]=="��������"){ //ȫ��������
                $poUnit="ctns";
            }else if(!in_array("��������", $typeArray["tuoPan"])){ //ȫ����
                $poUnit="ptls";
            }else{  //��ȫ����
                $poUnit="ctns";
            }*/
            
            
            
            /*if($k=="no_manua"){  
                $productList[$k]['total_tuopan_num']=$tupanTotal['tuopan_num'];
                $productList[$k]['total_tuopan_weight']=$tupanTotal['tuopan_weight'];
            }*/
            
            
            $productList[$k]['poUnit']=$poUnit;
            //��ȡ�ص���
            $productList[$k]['gd_number']=(!empty($v["productList"][0]['gd_number']))?$v["productList"][0]['gd_number']:'';
  
        }

       
        
        
        $retInfo["accSetInfo"]=$accSetInfo;             //������Ϣ
        $retInfo["accCtrInfo"]=$accCtrInfo;             //ó�׹�˾������Ϣ
        
        $retInfo["billInfo"]=$billInfo;                 //������Ϣ
        $retInfo["cabinetInfo"]=!empty($cabinetInfo)?$cabinetInfo:array();                 //װ����Ϣ
        $retInfo["billProducts"]=$billProducts;         //��Ʒ�б�
        $retInfo["productList"]=$productList;           //��Ʒ��ӡ�б�
       
      

        /*echo "<pre>";
        echo var_dump($productItem);
        exit;*/
        
        $retInfo["mark"]=""; 
        $markInfo = $this->msdb->select_one("Customer", "*", " cCusCode='{$billInfo["customer_code"]}' ");
        if(!empty($markInfo)&&is_array($markInfo)){
            $retInfo["mark"]=$markInfo["cCusDefine1"]; 
        }
        
        return $retInfo;

    }

    public function initPrintData($params){
      
        $config=config();
        $coinTypeArray=$config["coin_type"];
        $retInfo=array("status"=>200,"msg"=>"�ύ�ɹ���","data"=>array()); 
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
                //��ȡ��ӡ��������
                $dataInfo=$this->printData($params['data']);
            }   
        }

   
       
        $accountType=empty($dataInfo["billInfo"]["account_ctr_id"])?"accSetInfo":"accCtrInfo";
        
        $sealImgs=array("xd"=>"","bg"=>"");
        
        
        $markType=substr(trim($dataInfo["billInfo"]["invoice_num"]),0,4);
        if(!empty($dataInfo["billInfo"]['account_ctr_id'])){
            switch ($dataInfo["billInfo"]['account_ctr_id']){
                case "14"://����ʢ��                    
                    $sealImgs=array("xd"=>"seal_images/NBSXXD.png","bg"=>"seal_images/NBSXBG.png") ;
                    break;
                case "15"://������ˮ��ó���޹�˾
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
                            if($v_1["complete"]=="��Ʒ"||$v_1['category']=="PVC����"){
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
                
                
                if($params['data']['print_type']=="6"){
                    $productsList=array();
                    foreach($val["products"] as $k=>$v){
                        $index++; 
                        $tempData=array(
                            "id"=>$index,
                            "product_code"=>$v["hsCode"],
                            "product_specs"=>$v["hsCodeName"],
                            "grade_name"=>$v["grade_name"],
                            "lading_bill_name"=>$v["lading_bill_name"],
                            "num"=>$v["u8_info"]["jz"], 
                            "unit"=>"KGS",
                            "total_price"=>$v["total"],//�ܼ�
                            "coin_type"=>$coinTypeArray[$dataInfo["billInfo"]['coin_type']],//����
                            "origin_country"=>"CHINA",//ԭ����
                            "target_country"=>$dataInfo["billInfo"]["customer_country"], //���˹�
                            "source_region"=>$dataInfo[$accountType]['product_source'],//���ڻ�Դ��
                            "levy_exempt"=>$key=="no_manua"?"����":"ȫ��", //��������
                            "u8_info"=>$v["u8_info"]
                        );
                        $productsList[]=$tempData;
                    }
                }

                $consignor=$dataInfo[$accountType]['company_name']."(".$dataInfo[$accountType]['gate_code']."/".$dataInfo[$accountType]['company_code'].")";
                
                
                $poUnit=$val["poUnit"];
                $moneyUnit=array("CTNS"=>"ctns","ROLLS"=>"rolls","PKGS"=>"packages");
                $totalMoneyEn= "SAY U.S {$coinTypeArray[$dataInfo["billInfo"]['coin_type']]}: ".rtrim(moneyFmt::umoney($val["totalMoney"],$coinTypeArray[$dataInfo["billInfo"]['coin_type']])).".";
                
   
                $preGiveTime=date("d/M/y", strtotime($dataInfo["billInfo"]['give_time']." -5 day "));
                
                if(!empty($dataInfo["cabinetInfo"]["cabinet_time"])&&$dataInfo["cabinetInfo"]["cabinet_time"]!="0000-00-00"){
                    $preGiveTime=$dataInfo["cabinetInfo"]["cabinet_time"];
                }

                $companyName = $dataInfo[$accountType]['company_name'];

                //�ж϶����ǲ���������ˮ����ʢ��
                $orderNumArr = explode(',', $dataInfo["billInfo"]["order_nums"]);
                foreach($orderNumArr as $item){
                    $orderNumInfo = $this->db->select_one("dev_ordernum","*", " concat_ws('',num,`desc`) = '{$item}' ");
                    if($orderNumInfo['cCusCode'] == "0100100150028"){
                        $sanShuiInfo = $this->db->select_one("dev_account_ctr", "*", " trade_company = '������ˮ��ó���޹�˾' ");
                        $companyName = '������ˮ��ó���޹�˾';
                        $accountName = $sanShuiInfo['account_name'];
                        $accountAddress = $sanShuiInfo['account_address'];
                        $sealImgs=array("xd"=>"seal_images/JXSSXD.png","bg"=>"seal_images/JXSSBG.png") ;
                        $consignor=$sanShuiInfo['company_name']."(".$sanShuiInfo['gate_code']."/".$sanShuiInfo['company_code'].")";
                    }elseif($orderNumInfo['cCusCode'] == "010020301"){
                        $shengXinInfo = $this->db->select_one("dev_account_ctr", "*", " trade_company = '����ʢ��' ");
                        $companyName = '����ʢ��';
                        $accountName = $shengXinInfo['account_name'];
                        $accountAddress = $shengXinInfo['account_address'];
                        $sealImgs=array("xd"=>"seal_images/NBSXXD.png","bg"=>"seal_images/NBSXBG.png") ;
                        $consignor=$shengXinInfo['company_name']."(".$shengXinInfo['gate_code']."/".$shengXinInfo['company_code'].")";
                    }
                }
                
                $retInfo["data"][$key]=array(
                    "consignor"=>$consignor, //���ڷ�����
                    "record_no"=>($key=="no_manua")?"":$key,//������
                    "consignee"=>$dataInfo["billInfo"]['customer_name'],//�����ջ���
                    "transport"=>"����",  //���䷽ʽ
                    "produce_business"=>$consignor,  //�������۵�λ
                    "supervise"=>($key=="no_manua")?"һ��ó��":"���϶Կ�",  //��ܷ�ʽ
                    "avoid_tax"=>($key=="no_manua")?"һ��ó��":"���ϼӹ�",  //��������
                    "contract"=>$dataInfo["billInfo"]["invoice_num"],  //��ͬЭ���
                    "customer_short"=>$dataInfo["billInfo"]["customer_short"],  //��ͬЭ���
                    "trade_country"=>$dataInfo["billInfo"]["customer_country"],  //ó�׹�
                    "target_port"=>$dataInfo["billInfo"]["end_port"],  //ָ�˸�(Ŀ�ĸ�)
                    "leave_port"=>$dataInfo["billInfo"]["start_port"],  //�뾳�ڰ�(���˸�)
                    "package_type"=>strtoupper($poUnit),  //��װ����    
                    "package_num"=>$val["poUnit"]=="ROLLS"?$val["totalNum"]:$val["totalBoxNum"],  //���в�Ʒ�����ܺ�
                    
                    "coin_type"=>$coinTypeArray[$dataInfo["billInfo"]['coin_type']],
                    "weight_mz"=>!empty($val["total_mz"])?$val["total_mz"]:0,  //ë��
                    "weight_jz"=>!empty($val["total_jz"])?$val["total_jz"]:0,  //����
                    "trade_mode"=>!empty($dataInfo["billInfo"]["trade_mode"])?$dataInfo["billInfo"]["trade_mode"]:$dataInfo["billInfo"]["trade_type"],  //�ɽ���ʽ
                    "poUnit"=> $poUnit,  
                    "account_name"=>empty($accountName) ? $dataInfo[$accountType]['account_name'] : $accountName ,//����Ӣ������
                    "account_address"=>empty($accountAddress) ? $dataInfo[$accountType]['account_address'] : $accountAddress,//����Ӣ�ĵ�ַ
                    
                    "account_type"=>$dataInfo["billInfo"]["account_type"],//��������
                    "account_ctr_id"=>$dataInfo["billInfo"]["account_ctr_id"],//ó�׹�˾ID
                    
                    
                    "seal_img_xd"=>$sealImgs["xd"],//�䵥��Ʊӡ��
                    "seal_img_bg"=>$sealImgs["bg"],//���ط�Ʊӡ��
                    
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
                    "adjust_money_list"=>string::autoCharset(json_decode(string::autoCharset($dataInfo["billInfo"]["adjust_bill"],'gbk','utf-8'),true),'utf-8','gbk'),//��������
                    "products"=>$productsList,
                    "payment"=>$dataInfo["billInfo"]['payment'],
                    "mark"=>$dataInfo['mark'],
                    "productItem"=>$val['productItem'],
                    "mergeList"=>$val['mergeList'],
                    "special_need_content"=> $dataInfo["billInfo"]["special_need"] == "��" ? $dataInfo["billInfo"]['special_need_content'] :  "",

                    "gd_number" => ($key=="no_manua")?$dataInfo["billInfo"]['invoice_num']: $val['gd_number'],//��ȡ�ص���

                );

                
             
            }
        }    
        
        
        return $retInfo;
    }

    /**
     * ��ȡ��ֺ����ֲ��Ʒ�������
     * @return void
     */
    public function get_no_manua_array_data($params){
        $retInfo=array();
        $bill_id=!empty($params["bill_id"])?$params["bill_id"]:$params["bill_ids"]; //���յ�ID
        $print_type=$params["print_type"]; //��������
        $price_type=$params["price_type"]; //�۸����ͣ�1.�ܼۣ�2.��̯�۸�
        $manua_type=$params["manua_type"]; //�Ƿ��ֲ��ӡ
        $adjust_type=!empty($params["adjust_type"])?$params["adjust_type"]:0; //�Ƿ����
        $bill_adjust=!empty($params["bill_adjust"])?$params["bill_adjust"]:1;



        $billProducts=$this->get_no_manua_bill_products($params);
        $billInfo=$this->provebill->getBillInfo(array("id"=>$bill_id));//��ȡ��������
        //$billProducts = $this->orderSer->getOrderProductList(array("provebill_id"=>$bill_id,"bhz"=>0,"order_by_id"=>"ASC","limit"=>999)); //��ȡ���ղ�Ʒ�б� ȡ[data]
        $cabinetInfo=$this->db->get_one("dev_provebill_cabinet", "*", " bill_id in ({$bill_id}) ");
        $deliverInfo=$this->db->get_one("dev_provebill_deliver", "*", " bill_ids in ({$bill_id}) ");


//start

         $this->provebill->productHandle($billProducts["data"]);





        $accSetInfo=$this->accountSet->getInfo(array("account_type"=>$billInfo["account_type"])); //��ȡ������Ϣ
        $accCtrInfo=array();
        if(!empty($billInfo["account_ctr_id"])){
            $accCtrInfo=$this->sqlManage->sqlManageApi(array("unique_code"=>"account_ctr_list","id"=>$billInfo["account_ctr_id"],"limit"=>1));   //��ȡó�׹�˾������Ϣ
        }



        if(empty($billProducts['data'])) return false;
        $poUnit=$billProducts['data'][0]["poUnit"];



        //���յ�������ֵ
        if($bill_adjust==2){
            $fieldArray=["is_apportion","apportion_price","apportion_total","num",'box_num',"mz_1","jz_1","tj_1","total_1","po_price"];
            foreach ($billProducts['data'] as $key=>$val){
                if(empty($val['provebill_id']))continue;
                $adjustInfo=$this->db->get_one("dev_provebill_product_adjust", "*", " order_list_id={$val['id']} and provebill_id={$val['provebill_id']} order by id ASC ");
                if(empty($adjustInfo))   continue;
                foreach ($fieldArray as $k=>$v){
                    $billProducts['data'][$key][$v]=$adjustInfo[$v];
                }
            }
        }




        //�ּ��װ��Ʒ�������װ��Ʒ�۸�ë����
        foreach ($billProducts['data'] as $key=>$val){

            $billProducts['data'][$key]["poPrice"]=$val["poPrice"]=$val["poPrice1"];
            $billProducts['data'][$key]["proPrice"]=$val["proPrice"]=$val["proPrice1"];

            $billProducts['data'][$key]["poPrice"]=$val["poPrice"]= doubleval($val["po_price"])>0?$val["po_price"]:$val["poPrice"] ;


            $billProducts['data'][$key]["poPrice"]=$val['poPrice']= $val['frignPrice']==0?$val['poPrice']:$val['frignPrice']; //�����������



            $billProducts['data'][$key]["apportion_total"]=$val["apportion_total"]= floatval($billProducts['data'][$key]["poPrice"]*$val["num"]+$val["apportion_price"]);


            //echo $val["poPrice"]=$val["poPrice1"]."||";

            if(empty($val['huanSuanLv'])) $billProducts['data'][$key]["huanSuanLv"]=$val['huanSuanLv']=1;

            if(empty($val["poUantity"])){
                unset($billProducts['data'][$key]);
                continue;
            }




            $billProducts['data'][$key]["isAll"]=$val["isAll"] =$isAll=false;
            if($val["poUantity"]<=$val["num"]){
                $billProducts['data'][$key]["isAll"]=$val["isAll"]=$isAll=true;
            }


            $billProducts['data'][$key]["poUantity"]=$val["poUantity"]=$val["num"];//������





            //$billProducts['data'][$key]["poPrice"]=$val['poPrice']= $val['po_price']==0?$val['poPrice']:$val['po_price']; //�����������

            $billProducts['data'][$key]["poPrice"]=$val['poPrice']= $val['frignPrice']==0?$val['poPrice']:$val['frignPrice']; //�����������
            $billProducts['data'][$key]["total"]=$val['total']= $val['frignTotal']==0?$val['total']:$val['frignTotal']; //��������ܼ�


            if($val["hz"]==1){
                if(empty($val["hzNumSum"])){
                    $billProducts['data'][$key]["hzNumSum"] = $val["hzNumSum"] = array_sum(array_filter(explode(",", $val["hzNum"]) ));
                }

                $billProducts['data'][$key]["u8_info"]=$val["u8_info"]= $this->orderSer->getU8OrdListInfo($val['u8Code'],$val['complete'],$val['orderId']);




                $billProducts['data'][$key]["u8_info"]['mz']=$val["u8_info"]["mz"]=floatval($val["c_dwmz"])!=0?floatval($val["c_dwmz"]):floatval($val["u8_info"]['mz']);
                $billProducts['data'][$key]["u8_info"]['jz']=$val["u8_info"]["jz"]=floatval($val["c_dwjz"])!=0?floatval($val["c_dwjz"]):floatval($val["u8_info"]['jz']);
                $billProducts['data'][$key]["u8_info"]['dwtj']=$val["u8_info"]["dwtj"]=floatval($val["c_dwtj"])!=0?floatval($val["c_dwtj"]):floatval($val["u8_info"]['dwtj']);



                $val['u8_info']['dwxs']=$val["complete"]=="���Ʒ"?$val['u8_info']['dwxs']:1;

                if(empty($val["box_num"])){
                    $billProducts['data'][$key]["boxNum"]=$val["boxNum"]= ceil(round($val['num'])*$val['huanSuanLv']/floatval($val['onum'])); //�Զ�������
                }else{
                    $billProducts['data'][$key]["boxNum"]=$val["boxNum"]=$val["box_num"];
                }



                $hzProducts = $this->orderSer->getOrderProductList(array("hz"=>0,"bhz"=>$val["id"],"limit"=>999)); //��ȡ��װ��Ʒ


                $hzPrice=$price_type=="2"&&$val['is_apportion']?$val['apportion_total']:$billProducts['data'][$key]["total_1"];



                if(!empty($hzProducts['data'])){

                    $billProducts['data'][$key]["hsCode"]=$hzProducts['data'][0]["hsCode"];
                    $billProducts['data'][$key]["hsCodeName"]=$hzProducts['data'][0]["hsCodeName"];

                    if($val['poUnit']!="SETS"){

                        $unitProRoll=$val['proUantity']/$val['hzNumSum'];//��λ��װ����


                        if(empty($val["hzNumSum"])){
                            $unitProRoll=1;
                        }



                        $allRolls=0;
                        $billProducts['data'][$key]["complete"]=$hzProducts['data'][0]["complete"];
                        if(in_array($print_type, ['2','4','6','5'])){



                            unset($billProducts['data'][$key]);


                            //$hzProducts['data'][$k]["proUantity"]=$v["proUantity"]=$v["poUantity"];

                            foreach ($hzProducts['data'] as $k=>$v){
                                $allRolls+=$v["proUantity"];
                            }



                            /*$hzMethodArray=array("hzids"=>array_filter(explode(",", $val['hzids'])),"hzNum"=>array_filter(explode(",", $val['hzNum'])));
                            $hzMethod=array();
                            if(!empty($hzMethodArray["hzids"])){
                                foreach ($hzMethodArray["hzids"] as $k=>$v){
                                    $hzMethod[$v]=$hzMethodArray["hzNum"][$k];
                                }
                            }*/


                            $total_box_num=0;
                            //��װ��Ʒ����̯
                            foreach ($hzProducts['data'] as $k=>$v){

                                $hzProducts['data'][$k]["poPrice"]=$v['poPrice']= $v['po_price']==0?$v['poPrice']:$v['po_price']; //�����������
                                $hzProducts['data'][$k]["poPrice"]=$v['poPrice']= $v['frignPrice']==0?$v['poPrice']:$v['frignPrice']; //�����������
                                $hzProducts['data'][$k]["total"]=$v['total']= $v['frignTotal']==0?$v['total']:$v['frignTotal']; //��������ܼ�

                                $hzProducts['data'][$k]["total_1"]=$hzPrice*($v["proUantity"]/$allRolls);
                                //$hzProducts['data'][$k]["boxNum"]=$val["poUantity"]*($v["proUantity"]/$allRolls);

                                $hzProducts['data'][$k]["poPrice"]=$hzPrice*($v["proUantity"]/$allRolls)/$v["poUantity"];


                                $hzProducts['data'][$k]["poUnit"]="ROLLS";
                                $hzProducts['data'][$k]["boxNum"]=0;


                                if($k==0) {
                                    $hzProducts['data'][$k]["boxNum"]=$val["boxNum"];
                                    $hzProducts['data'][$k]["tuopan_num"]=$val["tuopan_num"];
                                    $hzProducts['data'][$k]["tuopan_weight"]=$val["tuopan_weight"];
                                }

                                if(!empty($v["huanSuanLv"])){
                                    $hzProducts['data'][$k]["boxNum"]=round($val["boxNum"]*($v['huanSuanLv']/$val['hzNumSum']));
                                    $total_box_num+=$hzProducts['data'][$k]["boxNum"];
                                }else if(!empty($v["onum"])){
                                    $hzProducts['data'][$k]["boxNum"]=round($val["boxNum"]*($v['onum']/$val['hzNumSum']));
                                    $total_box_num+=$hzProducts['data'][$k]["boxNum"];
                                }

                                //$hzProducts['data'][$k]["boxNum"] = intval($hzProducts['data'][$k]["boxNum"]);



                                $hzProducts['data'][$k]["manua"]=$val["manua"];

                                if(!empty($val["hzNumSum"])){
                                    $hzProducts['data'][$k]["hzPoUantity"]=(floatval($val["poUantity"])/floatval($val["hzNumSum"])*(floatval($v["proUantity"])/floatval($unitProRoll)));
                                    $hzProducts['data'][$k]["hzBoxNum"]=(floatval($val["boxNum"])/floatval($val["hzNumSum"])*(floatval($v["proUantity"])/floatval($unitProRoll)));
                                    $hzProducts['data'][$k]["manua_num"]=(floatval($val["manua_num"])/floatval($val["hzNumSum"])*(floatval($v["proUantity"])/floatval($unitProRoll)));


                                }else{
                                    $hzProducts['data'][$k]["hzPoUantity"]=$v["proUantity"];
                                    $hzProducts['data'][$k]["hzBoxNum"]=$v["boxNum"];
                                    $hzProducts['data'][$k]["manua_num"]=$val["manua_num"];
                                }

                                $hzProducts['data'][$k]["manua_box"]=$hzProducts['data'][$k]["manua_num"]*$val['huanSuanLv']/floatval($val['onum']);

                                $hzProducts['data'][$k]["hz_u8_info"]=$val["u8_info"];

                            }




                            //������ֵ
                            $hzProducts['data'][0]["boxNum"]+=$val["boxNum"]-$total_box_num;


                        }

                        foreach ($hzProducts['data'] as $k=>$v){
                            $billProducts['data'][]=$v;
                        }

                    }




                }

            }




        }


        $apportion_total=0;

//end



        //������������
        $tupanTotal=array("tuopan_num"=>0,"tuopan_weight"=>0);
        //�ֲ�������ݼ���
        $manualArray=array();

        foreach ($billProducts['data'] as $key=>$val){

            $tupanTotal["tuopan_num"]+=round($val["tuopan_num"]);
            $tupanTotal["tuopan_weight"]+=round($val["tuopan_weight"]);



            if(in_array($val['category'], ["��ེ��"])){
                if(in_array($val['model'], ["","���ֱ�"]) ){
                    $billProducts['data'][$key]["hsCode"]=$val["hsCode"]="4811410000";
                    $billProducts['data'][$key]["hsCodeName"]=$val["hsCodeName"]="SPIRAL CLEANING TAPE";
                }else{
                    $billProducts['data'][$key]["hsCode"]=$val["hsCode"]="9603909090";
                    $billProducts['data'][$key]["hsCodeName"]=$val["hsCodeName"]="CLEANER TAPE";
                }

                if(strstr($val["customerCode"], "��ɫС����")){
                    $billProducts['data'][$key]["hsCode"]=$val["hsCode"]="3919909090";
                    $billProducts['data'][$key]["hsCodeName"]=$val["hsCodeName"]="CLOTH TAPE";
                }

                if(strstr($val["customerCode"], "ţƤֽ��ེ��")){
                    $billProducts['data'][$key]["hsCode"]=$val["hsCode"]="4811410000";
                    $billProducts['data'][$key]["hsCodeName"]=$val["hsCodeName"]="TAPE";
                }

            }


            $val["en_desc"]=$val["podesc"].$val["poEdesc"];//Ӣ������

            $val['poUnitOne']= preg_replace('/(\w+)([S])/i', '${1}', $val['poUnit']);

            $val["u8_info"]= $this->orderSer->getU8OrdListInfo($val['u8Code'],$val['complete'],$val['orderId']);



            //$val["u8_info"]= $this->orderSer->getU8OrdListInfo($val['u8Code'],$val['complete'],$val['orderId']);

            $val['u8_info']['dwxs']= !empty($val['u8_info']['dwxs'])?$val['u8_info']['dwxs']:1;



            if($val["u8_info"]['mz']=='δ��ȡ') $val["u8_info"]['mz']=0.00;
            if($val["u8_info"]['jz']=='δ��ȡ') $val["u8_info"]['jz']=0.00;
            if($val["u8_info"]['dwtj']=='δ��ȡ') $val["u8_info"]['dwtj']=0.00;

            $val["u8_info"]['mz']=floatval($val["c_dwmz"])!=0?floatval($val["c_dwmz"]):floatval($val["u8_info"]['mz']);
            $val["u8_info"]['jz']=floatval($val["c_dwjz"])!=0?floatval($val["c_dwjz"]):floatval($val["u8_info"]['jz']);
            $val["u8_info"]['dwtj']=floatval($val["c_dwtj"])!=0?floatval($val["c_dwtj"]):floatval($val["u8_info"]['dwtj']);
            $val['u8_info']['info_type']=!empty($val["c_dwmz"])?"�ͻ�Ҫ��ֵ":$val['u8_info']['info_type'];

            $val['u8_info']['dwxs']=empty($val["c_dwmz"])?$val['u8_info']['dwxs']:1;

            $val['u8_info']['dwxs']=$val["complete"]=="���Ʒ"?$val['u8_info']['dwxs']:1;

            $hsCodeInfo=array();
            if(!empty($val["hsCodeName"])){
                $hsCodeInfo=$this->productHscode->getInfo(array("hs_code_name"=>$val["hsCodeName"]));//��ȡHScode����
            }



            //������������
            $tuopan=$this->sqlManage->sqlManageApi(array("unique_code"=>"tuopan_list","tpName"=>$val['tuoPan'],"limit"=>1));
            $tuopan["weight"]=!empty($tuopan["weight"])?$tuopan["weight"]:20;
            $val['tuopan_weight']=$val["tuopan_num"]*$tuopan["weight"];


            $nomanuaInfo=$manuaInfo=$val;//���ֲ�����ֲ�

            //�������ֲ�Ĳ�����

            $mjzNum=0;
            if($val['bhz']==0){

                //echo $val["id"].":".$val["tuopan_num"]."||";


                $total=$price_type=="2"&&$val['is_apportion']?$val['apportion_total']:$val['total_1'];//�жϼ۸����ͣ�1�ܼۣ�2��̯���ܼۣ�



                $nomanuaInfo['poUantity']=$manua_type=="1"?$val["poUantity"]:$val["no_manua_num"];//���ֲ��Ʒ����

                if(empty($val["box_num"])&&!empty($val['onum'])){
                    $nomanuaInfo['boxNum']=($val["complete"]=="��Ʒ"||$val["category"]=="PVC����"||$val['hz']==1)?ceil(($nomanuaInfo['poUantity']*$val['huanSuanLv'])/$val['onum']):$nomanuaInfo['poUantity'];//��Ʒ���Ʒ����ת��
                }else{
                    if($val["complete"]=="��Ʒ"||$val["category"]=="PVC����"||$val['hz']==1){
                        $nomanuaInfo['boxNum']=$val["box_num"];

                    }else{
                        $nomanuaInfo['boxNum']=$val["num"];

                    }
                }




                if($manua_type!="1"){
                    $nomanuaInfo['boxNum']=($val["complete"]=="��Ʒ"||($val["category"]=="PVC����"&&!empty($val['onum']))||$val['hz']==1)?ceil(($nomanuaInfo['poUantity']*$val['huanSuanLv'])/$val['onum']):$nomanuaInfo['poUantity'];//��Ʒ���Ʒ����ת��
                }


                //$nomanuaInfo['boxNum']=($val["complete"]=="��Ʒ"||($val["category"]=="PVC����"&&!empty($val['onum']))||$val['hz']==1)?ceil(($nomanuaInfo['poUantity']*$val['huanSuanLv'])/$val['onum']):$nomanuaInfo['poUantity'];//��Ʒ���Ʒ����ת��




                $nomanuaInfo['boxNum']= ceil($nomanuaInfo['boxNum']);


                if($price_type=="2"&&$val['is_apportion']){
                    $nomanuaInfo['total']=$nomanuaInfo['poUantity']*($total/$val["poUantity"]);
                }else{
                    $nomanuaInfo['total']=$nomanuaInfo['poUantity']*$val["poPrice"];//���ֲ��Ʒ�ܼ�
                }


                $mjzNum=$nomanuaInfo['boxNum'];


            }else{

                //$nomanuaInfo["poUantity"]=$val["poUantity"]= $val["proUantity"];//��װ��Ʒ��������

                if($val['poUnit']!="SETS"){
                    if($val["poUantity"]>$val["manua_num"]){
                        $nomanuaInfo["poUantity"]=$val["poUantity"]= $val["poUantity"]-$val["manua_num"];//��װ��Ʒ��������
                    }
                }else{

                    $nomanuaInfo["poUantity"]=$val["poUantity"]= $val["hzPoUantity"]-$val["manua_num"];//��װ��Ʒ��������
                }





                $nomanuaInfo['total']=$total=   $nomanuaInfo["poUantity"]*$val["poPrice"];




                //echo $nomanuaInfo["poUantity"]."*".$val["poPrice"]."=".$nomanuaInfo['total']."||";

                //$nomanuaInfo["poUantity"]=$val["poUantity"]= $val["hzBoxNum"];//��װ��Ʒ��������

                $mjzNum = floatval($val['hzBoxNum']);
                //�ж��Ƿ�Ϊ�ֲ��ӡ
                if($manua_type!="1"&&$val['poUnit']!="SETS"){
                    $mjzNum=(floatval($val['hzBoxNum'])-floatval($val['manua_box']));
                }else{
                    $mjzNum=(floatval($val['boxNum'])-floatval($val['manua_box']));
                }


                //$nomanuaInfo['u8_info']=$val['u8_info']=$val["hz_u8_info"];
                $nomanuaInfo['u8_info']['mz']=$val['u8_info']['mz']=$val['hz_u8_info']['mz'];
                $nomanuaInfo['u8_info']['jz']=$val['u8_info']['jz']=$val['hz_u8_info']['jz'];
                $nomanuaInfo['u8_info']['dwtj']=$val['u8_info']['dwtj']=$val['hz_u8_info']['dwtj'];

            }


            if(empty($val["c_dwmz"])&&empty($val["bhz"])&&$val['complete']=="���Ʒ"){
                if($val['category']=="PVC����"&&$val['omaterial'] != 'ľ��' && $val['cInvCode'] != '����ֽ��'){
                    //$nomanuaInfo['u8_info']["dwmz"]=$val['mz'];
                    $nomanuaInfo['u8_info']["dwmz"]=floatval($val['u8_info']['mz'])*floatval($val['u8_info']['dwxs']);
                }else{
                    $nomanuaInfo['u8_info']["dwmz"]=floatval(($val["proUantity"]*$val['mz']/$val['boxNum']));
                }
            }else{
                if(!empty($val["c_dwmz"])){
                    $nomanuaInfo['u8_info']["dwmz"]=$val["c_dwmz"];
                }else{
                    $nomanuaInfo['u8_info']["dwmz"]=floatval($val['u8_info']['mz'])*floatval($val['u8_info']['dwxs']);
                }

                //echo $val['u8_info']['mz']."||";
            }



            if(empty($val["c_dwjz"])&&empty($val["bhz"])&&$val['complete']=="���Ʒ"){
                if($val['category']=="PVC����"&&$val['omaterial'] != 'ľ��' && $val['cInvCode'] != '����ֽ��'){
                    //$nomanuaInfo['u8_info']["dwjz"]=$val['jz'];
                    $nomanuaInfo['u8_info']["dwjz"]=floatval($val['u8_info']['jz'])*floatval($val['u8_info']['dwxs']);
                }else{
                    $nomanuaInfo['u8_info']["dwjz"]=floatval(($val["proUantity"]*$val['jz']/$val['boxNum']));
                }

            }else{

                if(!empty($val["c_dwjz"])){
                    $nomanuaInfo['u8_info']["dwjz"]=$val["c_dwjz"];
                }else{
                    $nomanuaInfo['u8_info']["dwjz"]=floatval($val['u8_info']['jz'])*floatval($val['u8_info']['dwxs']);
                }
            }

            //�ų���Ʒ����ʵë���ؼ���(��ʱ�ӵģ�����ɨ��û������Ҫȥ��)
            if(empty($val["bhz"])&&$val['complete']=="��Ʒ"&&$val['category']!="BOPPĤ"){
                $billProducts['data'][$key]['zs_mz']=$val["zs_mz"]=$val['mz'];
                $billProducts['data'][$key]['zs_jz']=$val["zs_jz"]=$val['jz'];
            }




            $nomanuaInfo["zs_dwjz"]=floatval($val['zs_jz']);
            $nomanuaInfo["zs_dwmz"]=floatval($val['zs_mz']);


            if(empty($val["bhz"])&&$val['complete']=="���Ʒ"){
                if($val['category']!="PVC����"){
                    $nomanuaInfo["zs_dwjz"]=floatval($val['zs_zjz']/$val['num']);
                    $nomanuaInfo["zs_dwmz"]=floatval($val['zs_zmz']/$val['num']);
                }
            }




            if(!empty($deliverInfo)){
                $deliverItem=$this->db->get_one("dev_provebill_deliver_item", "*", " deliver_id={$deliverInfo["id"]} and order_list_id={$val['id']} ");
                //$billItem=$this->db->get_one("dev_provebill_product", "*", " provebill_id in ({$bill_id}) and order_list_id={$val['id']} ");
                if(!empty($deliverItem)){
                    if(!empty($deliverItem['fnweights'])){
                        $nomanuaInfo["zs_dwjz"]=$val['zs_dwjz']=floatval($deliverItem['fnweights']/$val["num"]);
                    }
                    if(!empty($deliverItem['fgweights'])){
                        $nomanuaInfo["zs_dwmz"]=$val['zs_dwmz']=floatval($deliverItem['fgweights']/$val["num"]);
                    }
                }
            }



            /*$nomanuaInfo['u8_info']["dwmz"]=$val['u8_info']['mz']*$val['u8_info']['dwxs'];
            $nomanuaInfo['u8_info']["dwjz"]=$val['u8_info']['jz']*$val['u8_info']['dwxs'];*/





            $nomanuaInfo['u8_info']['mz']=floatval($mjzNum)*($nomanuaInfo['u8_info']["dwmz"]);//���ֲ�ë�أ�����*����λë��*ϵ������
            $nomanuaInfo['u8_info']['jz']=floatval($mjzNum)*($nomanuaInfo['u8_info']["dwjz"]);//���ֲΆ�أ�����*����λë��*ϵ������
            $nomanuaInfo['u8_info']['tj']=floatval($mjzNum)*$val["u8_info"]['dwtj'];//���ֲ������(����*��λ���)




            $nomanuaInfo['zs_mz']=floatval($mjzNum)*($nomanuaInfo["zs_dwmz"]);//���ֲ�ë�أ�����*����λë��*ϵ������
            $nomanuaInfo['zs_jz']=floatval($mjzNum)*($nomanuaInfo["zs_dwjz"]);//���ֲΆ�أ�����*����λë��*ϵ������


            if($manua_type=="1"){
                $nomanuaInfo['zs_mz']=$val["zs_zmz"];//���ֲ�ë�أ�����*����λë��*ϵ������
                $nomanuaInfo['zs_jz']=$val["zs_zjz"];//���ֲΆ�أ�����*����λë��*ϵ������
            }


            $nomanuaInfo['u8_info']['info_type']=$val["u8_info"]['info_type'];
            $nomanuaInfo["lading_bill_name"]=!empty($hsCodeInfo["lading_bill_name"])?$hsCodeInfo["lading_bill_name"]:"";
            $nomanuaInfo["grade_name"]=!empty($hsCodeInfo["grade_name"])?$hsCodeInfo["grade_name"]:"";
            $nomanuaInfo['u8_info']["dwtj"]=$val['u8_info']['dwtj'];

            $nomanuaInfo['u8_info']["dwxs"]=$val['u8_info']['dwxs'];
            $nomanuaInfo['u8_info']["length"]=$val['u8_info']['length'];
            $nomanuaInfo['u8_info']["height"]=$val['u8_info']['height'];
            $nomanuaInfo['u8_info']["width"]=$val['u8_info']['width'];
            $nomanuaInfo['u8_info']["formula"]=$val['u8_info']['length']."*".$val['u8_info']['width']."*".$val['u8_info']['height'];
            $nomanuaInfo['tuopan_weight']=$val['tuopan_weight'];

            /*echo "<pre>";
            echo var_dump($nomanuaInfo['poUantity']);*/

            if(!empty($nomanuaInfo['poUantity'])){
                if($nomanuaInfo["poUantity"]<$nomanuaInfo['num']/2){
                    $nomanuaInfo["tuopan_num"]=0;
                    $nomanuaInfo['tuopan_weight']=0;
                }

                $manualArray["no_manua"]["productList"][]=$nomanuaInfo;
            }

        }

        return array('tupanTotal'=>$tupanTotal,"manualArray"=>$manualArray);

    }

    /**
     * ��ȡ��ֺ����ֲ��Ʒ�б�(�б�����������ֲ���ֲ������ֶ�)
     * @param $params
     * @return mixed
     */
    public function get_no_manua_bill_products($params,$type=0){
        $billInfo=$this->provebill->getBillInfo(array("id"=>$params['bill_id']));//��ȡ��������
        //print_r($billInfo);
        $billProducts = $this->orderSer->getOrderProductList(array("provebill_id"=>$params['bill_id'],"bhz"=>0,"order_by_id"=>"ASC","limit"=>999)); //��ȡ���ղ�Ʒ�б� ȡ[data]
       // var_dump(empty($billInfo['product_list']));exit;
        if(!empty($billInfo['product_list'])&&$billInfo['product_list']!="null"){
            $product_split_list = string::autoCharset(json_decode(string::autoCharset($billInfo['product_list'], 'gbk', 'utf-8'),true), 'utf-8', 'gbk');
            $array_no_manua= array();
            foreach($product_split_list as $k=>$v){
                foreach($v['product_list'] as $k_1=>$v_1){
                    $array_no_manua[$v_1['id']]['total_manua_num']+=$v_1['manua_num'];


                }
            }
            $array_no_manua_keys = array_keys($array_no_manua);
            //print_r($array_no_manua);



        }

            $array = array();
            foreach($billProducts['data'] as $k_2=>$v_2){
                //var_dump(in_array($v_2['id'],$array_no_manua_keys));exit;
                if(in_array($v_2['id'],$array_no_manua_keys)){
                    $billProducts['data'][$k_2]['no_manua_num'] = ($v_2['num']-$array_no_manua[$v_2['id']]['total_manua_num']);
                    $array[$v_2['id']]['no_manua_num'] = ($v_2['num']-$array_no_manua[$v_2['id']]['total_manua_num']);
                }else{
                    $billProducts['data'][$k_2]['no_manua_num'] = $v_2['num'];
                    $array[$v_2['id']]['no_manua_num'] = $v_2['num'];
                }

            }

            if($type==0){
                return $billProducts;
            }else{
                return $array;
            }




    }




    Public function cl_data(&$product_split_list,$bill_adjust,$price_type,$print_type){
       // print_r($product_split_list);exit;
        $product_split_list= $this->provebill->productHandle_p($product_split_list);
       // print_r($product_split_list);exit;
        if(empty($product_split_list)) return false;
        $poUnit=$product_split_list[0]["poUnit"];



        //���յ�������ֵ
        if($bill_adjust==2){
            $fieldArray=["is_apportion","apportion_price","apportion_total","num",'box_num',"mz_1","jz_1","tj_1","total_1","po_price"];
            foreach ($product_split_list as $key=>$val){
                if(empty($val['provebill_id']))continue;
                $adjustInfo=$this->db->get_one("dev_provebill_product_adjust", "*", " order_list_id={$val['id']} and provebill_id={$val['provebill_id']} order by id ASC ");
                if(empty($adjustInfo))   continue;
                foreach ($fieldArray as $k=>$v){
                    $product_split_list[$key][$v]=$adjustInfo[$v];
                }
            }
        }




        //�ּ��װ��Ʒ�������װ��Ʒ�۸�ë����
        foreach ($product_split_list as $key=>$val){

            $product_split_list[$key]["poPrice"]=$val["poPrice"]=$val["poPrice1"];
            $product_split_list[$key]["proPrice"]=$val["proPrice"]=$val["proPrice1"];

            $product_split_list[$key]["poPrice"]=$val["poPrice"]= doubleval($val["po_price"])>0?$val["po_price"]:$val["poPrice"] ;


            $product_split_list[$key]["poPrice"]=$val['poPrice']= $val['frignPrice']==0?$val['poPrice']:$val['frignPrice']; //�����������



            $product_split_list[$key]["apportion_total"]=$val["apportion_total"]= floatval($product_split_list[$key]["poPrice"]*$val["num"]+$val["apportion_price"]);


            //echo $val["poPrice"]=$val["poPrice1"]."||";

            if(empty($val['huanSuanLv'])) $product_split_list[$key]["huanSuanLv"]=$val['huanSuanLv']=1;

            if(empty($val["poUantity"])){
                unset($product_split_list[$key]);
                continue;
            }




            $product_split_list[$key]["isAll"]=$val["isAll"] =$isAll=false;
            if($val["poUantity"]<=$val["num"]){
                $product_split_list[$key]["isAll"]=$val["isAll"]=$isAll=true;
            }


            $product_split_list[$key]["poUantity"]=$val["poUantity"]=$val["num"];//������





            //$billProducts['data'][$key]["poPrice"]=$val['poPrice']= $val['po_price']==0?$val['poPrice']:$val['po_price']; //�����������

            $product_split_list[$key]["poPrice"]=$val['poPrice']= $val['frignPrice']==0?$val['poPrice']:$val['frignPrice']; //�����������
            $product_split_list[$key]["total"]=$val['total']= $val['frignTotal']==0?$val['total']:$val['frignTotal']; //��������ܼ�


            if($val["hz"]==1){
                if(empty($val["hzNumSum"])){
                    $product_split_list[$key]["hzNumSum"] = $val["hzNumSum"] = array_sum(array_filter(explode(",", $val["hzNum"]) ));
                }

                $product_split_list[$key]["u8_info"]=$val["u8_info"]= $this->orderSer->getU8OrdListInfo($val['u8Code'],$val['complete'],$val['orderId']);




                $product_split_list[$key]["u8_info"]['mz']=$val["u8_info"]["mz"]=floatval($val["c_dwmz"])!=0?floatval($val["c_dwmz"]):floatval($val["u8_info"]['mz']);
                $product_split_list[$key]["u8_info"]['jz']=$val["u8_info"]["jz"]=floatval($val["c_dwjz"])!=0?floatval($val["c_dwjz"]):floatval($val["u8_info"]['jz']);
                $product_split_list[$key]["u8_info"]['dwtj']=$val["u8_info"]["dwtj"]=floatval($val["c_dwtj"])!=0?floatval($val["c_dwtj"]):floatval($val["u8_info"]['dwtj']);



                $val['u8_info']['dwxs']=$val["complete"]=="���Ʒ"?$val['u8_info']['dwxs']:1;

                if(empty($val["box_num"])){
                    $product_split_list[$key]["boxNum"]=$val["boxNum"]= ceil(round($val['num'])*$val['huanSuanLv']/floatval($val['onum'])); //�Զ�������
                }else{
                    $product_split_list[$key]["boxNum"]=$val["boxNum"]=$val["box_num"];
                }



                $hzProducts = $this->orderSer->getOrderProductList(array("hz"=>0,"bhz"=>$val["id"],"limit"=>999)); //��ȡ��װ��Ʒ


                $hzPrice=$price_type=="2"&&$val['is_apportion']?$val['apportion_total']:$product_split_list[$key]["total_1"];



                if(!empty($hzProducts['data'])){

                    $product_split_list[$key]["hsCode"]=$hzProducts['data'][0]["hsCode"];
                    $product_split_list[$key]["hsCodeName"]=$hzProducts['data'][0]["hsCodeName"];

                    if($val['poUnit']!="SETS"){

                        $unitProRoll=$val['proUantity']/$val['hzNumSum'];//��λ��װ����


                        if(empty($val["hzNumSum"])){
                            $unitProRoll=1;
                        }



                        $allRolls=0;
                        $product_split_list[$key]["complete"]=$hzProducts['data'][0]["complete"];
                        if(in_array($print_type, ['2','4','6','5'])){



                            unset($product_split_list[$key]);


                            //$hzProducts['data'][$k]["proUantity"]=$v["proUantity"]=$v["poUantity"];

                            foreach ($hzProducts['data'] as $k=>$v){
                                $allRolls+=$v["proUantity"];
                            }



                            /*$hzMethodArray=array("hzids"=>array_filter(explode(",", $val['hzids'])),"hzNum"=>array_filter(explode(",", $val['hzNum'])));
                            $hzMethod=array();
                            if(!empty($hzMethodArray["hzids"])){
                                foreach ($hzMethodArray["hzids"] as $k=>$v){
                                    $hzMethod[$v]=$hzMethodArray["hzNum"][$k];
                                }
                            }*/


                            $total_box_num=0;
                            //��װ��Ʒ����̯
                            foreach ($hzProducts['data'] as $k=>$v){

                                $hzProducts['data'][$k]["poPrice"]=$v['poPrice']= $v['po_price']==0?$v['poPrice']:$v['po_price']; //�����������
                                $hzProducts['data'][$k]["poPrice"]=$v['poPrice']= $v['frignPrice']==0?$v['poPrice']:$v['frignPrice']; //�����������
                                $hzProducts['data'][$k]["total"]=$v['total']= $v['frignTotal']==0?$v['total']:$v['frignTotal']; //��������ܼ�

                                $hzProducts['data'][$k]["total_1"]=$hzPrice*($v["proUantity"]/$allRolls);
                                //$hzProducts['data'][$k]["boxNum"]=$val["poUantity"]*($v["proUantity"]/$allRolls);

                                $hzProducts['data'][$k]["poPrice"]=$hzPrice*($v["proUantity"]/$allRolls)/$v["poUantity"];


                                $hzProducts['data'][$k]["poUnit"]="ROLLS";
                                $hzProducts['data'][$k]["boxNum"]=0;


                                if($k==0) {
                                    $hzProducts['data'][$k]["boxNum"]=$val["boxNum"];
                                    $hzProducts['data'][$k]["tuopan_num"]=$val["tuopan_num"];
                                    $hzProducts['data'][$k]["tuopan_weight"]=$val["tuopan_weight"];
                                }

                                if(!empty($v["huanSuanLv"])){
                                    $hzProducts['data'][$k]["boxNum"]=round($val["boxNum"]*($v['huanSuanLv']/$val['hzNumSum']));
                                    $total_box_num+=$hzProducts['data'][$k]["boxNum"];
                                }else if(!empty($v["onum"])){
                                    $hzProducts['data'][$k]["boxNum"]=round($val["boxNum"]*($v['onum']/$val['hzNumSum']));
                                    $total_box_num+=$hzProducts['data'][$k]["boxNum"];
                                }

                                //$hzProducts['data'][$k]["boxNum"] = intval($hzProducts['data'][$k]["boxNum"]);



                                $hzProducts['data'][$k]["manua"]=$val["manua"];

                                if(!empty($val["hzNumSum"])){
                                    $hzProducts['data'][$k]["hzPoUantity"]=(floatval($val["poUantity"])/floatval($val["hzNumSum"])*(floatval($v["proUantity"])/floatval($unitProRoll)));
                                    $hzProducts['data'][$k]["hzBoxNum"]=(floatval($val["boxNum"])/floatval($val["hzNumSum"])*(floatval($v["proUantity"])/floatval($unitProRoll)));
                                    $hzProducts['data'][$k]["manua_num"]=(floatval($val["manua_num"])/floatval($val["hzNumSum"])*(floatval($v["proUantity"])/floatval($unitProRoll)));


                                }else{
                                    $hzProducts['data'][$k]["hzPoUantity"]=$v["proUantity"];
                                    $hzProducts['data'][$k]["hzBoxNum"]=$v["boxNum"];
                                    $hzProducts['data'][$k]["manua_num"]=$val["manua_num"];
                                }

                                $hzProducts['data'][$k]["manua_box"]=$hzProducts['data'][$k]["manua_num"]*$val['huanSuanLv']/floatval($val['onum']);

                                $hzProducts['data'][$k]["hz_u8_info"]=$val["u8_info"];

                            }




                            //������ֵ
                            $hzProducts['data'][0]["boxNum"]+=$val["boxNum"]-$total_box_num;


                        }

                        foreach ($hzProducts['data'] as $k=>$v){
                            $product_split_list[]=$v;
                        }

                    }




                }

            }




        }
        return $product_split_list;
    }













}



?>
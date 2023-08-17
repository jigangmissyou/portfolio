<?php

include_once '/inc/auth.inc.php';
include_once '/inc/utility_all.php';
include_once '/inc/utility_org.php';

include_once '/general/erp4/lib/dbTool/mssqlDB.class.php';
include_once '/general/erp4/lib/dbTool/mysqlDB2.class.php';

include_once '/general/erp4/service/field.ser.php';
include_once '/general/erp4/service/customer.ser.php';
include_once '/general/erp4/service/priv.ser.php';
include_once '/general/erp4/service/order.ser.php';

include_once '/general/erp4/lib/string.class.php';

class flowsSer{

    private $db;
    private $msdb;
    private $tabName='dev_flows';

    private $fieldSer;
    private $customerSer;
    private $privSer;
    
    private $flows;
   
    public function __construct(){
        $this->db = new mysqlDB();
        // $this->db = new mysqlDB(1);

        //$this->msdb = new mssqlDB();
        //$this->msdb = new mssqlDB('D:/MYOA/logs/customer.log');

        $this->fieldSer = new fieldSer();
        $this->customerSer = new customerSer();
        $this->privSer = new privser();
        
        //$this->flows = array("orderChange"=>"�������");//�������ƶ���
        //$this->steps=array("����޸�","������","�������","�������");
    }

    /**
     * Add a flow
     * @param array $params
     * @return int
     */
    public function add($params){
        $steps=$this->db->select_one("dev_runpriv","GROUP_CONCAT(`name` ORDER BY sort ASC) AS steps"," type='{$params['flowType']}' and deleted=0 ");
        if(empty($steps['steps'])){
            return array("status"=>500,"msg"=>"���̲��費���ڣ�","data"=>array());
        }
        $params['status'] = current(explode(',', $steps['steps']));//ȡ�����̵�һ��
        $params["allStep"]= $steps['steps'];
        
        $flowWhere=" a.flowCaseId='{$params['flowCaseId']}' and a.flowType='{$params['flowType']}' and a.status='{$params['status']}' and b.prcsFlag!='�������' and a.delFlag=0  ";
        
        if(!empty($params['data1'])&&in_array($params['flowType'], ["���ݴ�ӡ���"])){
            $flowWhere.=" and a.data1='{$params["data1"]}' ";
        }
      
        $flowInfo = $this->db->get_one("dev_flows as a left join  dev_flows_step as b on b.flowId=a.id ",'a.id',$flowWhere);
  
        if(empty($flowInfo)){
            $params['beginUser']= $_SESSION['LOGIN_UID'];
            $params['beginDept'] = $_SESSION['LOGIN_DEPT_ID'];
            $params['beginTime']=  date('Y-m-d H:i:s');
            $params['endTime']=  date('Y-m-d H:i:s');
            $params["data1"]= str_replace("\\'", "\\\\'", $params["data1"]) ;
            $params["data2"]= str_replace("\\'", "\\\\'", $params["data2"]) ;
            $params["data3"]= str_replace("\\'", "\\\\'", $params["data3"]) ;
            $flowId = $this->db->insert($this->tabName,$params);
            $nextData=array("step"=>!empty($params["step"])?$params["step"]:0, "flowId"=>$flowId);
            $nextData["userId"]=!empty($params["userId"])?$params["userId"]:"";
            $this->nextStep($nextData);
            return $flowId;
        }else{
            $this->db->update($this->tabName,array(
                "data1"=>!empty($params['data1'])?str_replace("\\'", "\\\\", $params['data1']):"",
                "data2"=>!empty($params['data2'])?str_replace("\\'", "\\\\", $params['data2']):"",
                "data3"=>!empty($params['data3'])?str_replace("\\'", "\\\\", $params['data3']):"",
            ),"id={$flowInfo['id']}");
            return $flowInfo["id"];
        }

    }
    
    /**
     * Del a flow
     * @param array $params
     * @return array
     */
    public function del($params){
        if(empty($params["id"])){
           return array("status"=>500,"msg"=>"��������","data"=>array()); 
        }
        
        $flowInfo=$this->get_current_flow(array("flowId"=>$params["id"]));
        if(in_array($flowInfo["flowType"],["���۱��"])){
            $this->db->update("dev_order", array("bill_del"=>0)," id={$flowInfo['flowCaseId']} " );
        }
       
        $this->db->delete($this->tabName," id={$params["id"]} "); //ɾ������
        $this->db->delete("dev_flows_step"," flowId={$params["id"]} ");  //ɾ���Ӳ���
        
        return array("status"=>200,"msg"=>"ɾ���ɹ���","data"=>array());
    }

    /**
     * Get current flow
     * @param array $params
     * @return array
     */
    public function get_current_flow($params){
        if(empty($params['flowId'])) return false;
        
        $where=" (1=1) ";
        $table=" dev_flows as a"
                . " left join dev_flows_step as b on b.flowId=a.id and b.flowPrcs=a.status "
                . " left join dev_runpriv as c on c.type=a.flowType and c.name=a.status ";
        $filter="a.*,b.userId as step_user_id,b.step_model,b.audit_user_id,b.audit_time,b.prcsFlag,b.id as step_id,b.deliverUserId,if(b.deliverTime='0000-00-00 00:00:00','',b.deliverTime) as deliverTime,c.uids as priv_uids"; 
        $where.=!empty($params['flowId'])?" and a.id={$params['flowId']} ":"";
        $where.="order by b.id desc";
        $dataInfo=$this->db->get_one($table,$filter,$where);
        return  $dataInfo;
    }

    /**
     * Get flow info
     * @param array $params
     * @return array
     */
    public function getInfo($params){  
        $where=" (1=1) ";
        if(!empty($params["flowId"])) $where.=" and id='{$params["flowId"]}' ";
        if(!empty($params["flowCaseId"])) $where.=" and flowCaseId='{$params["flowCaseId"]}' ";
        if(!empty($params["flowType"])) $where.=" and flowType='{$params["flowType"]}' ";
        if(!empty($params["status"])) $where.=" and status='{$params["status"]}' ";
        if(!empty($params["currentStep"])) $where.=" and currentStep='{$params["currentStep"]}' ";
       
        $dataInfo = $this->db->select_one($this->tabName,'*',$where);
        if(!empty($dataInfo["data2"])){
            $dataInfo["data2"]=str_replace('\\\"','\"',$dataInfo["data2"]); 
            $dataInfo["data2"]=str_replace('\\\"','\"',$dataInfo["data2"]);
        }
 
        return $dataInfo;
    }
    

    /**
     * Jump to the next step
     * @param array $params
     * @return array
     */
    public function nextStep($params){
        $flowInfo= $this ->getInfo($params);
        $currentFlow =$this->get_current_flow(array("flowId"=>$flowInfo["id"]));
        
        if(!empty($currentFlow["step_id"])&&$_SESSION["LOGIN_USER_PRIV"]!=1){
            $userId=explode(",", $currentFlow["step_user_id"]);
            $auditUserId=$auditTime=[];
            if(!empty($currentFlow["audit_user_id"])){
                $auditUserId= explode(",", $currentFlow["audit_user_id"]);
                $auditTime= explode(",", $currentFlow["audit_time"]);
            }
            if(!in_array($_SESSION['LOGIN_UID'], $auditUserId)){
                $auditUserId[]=$_SESSION['LOGIN_UID'];
                $auditTime[]= date("Y-m-d H:i:s");
            }
            $this->db->update("dev_flows_step", array("audit_user_id"=> implode(",", $auditUserId),"audit_time"=>implode(",", $auditTime)),"id={$currentFlow["step_id"]}");

            $finish=true;
            foreach ($userId as $key=>$val){
                if(!in_array($val, $auditUserId)) $finish=false;
            }
            if($currentFlow["step_model"]==2&&!$finish){
                return array("status"=>200,"msg"=>"���̴�����ϣ�","data"=>array());
            }

        }

        $params['opt_type']=!empty($params['opt_type'])?$params['opt_type']:0;
        if(empty($flowInfo)){
            return array("status"=>500,"msg"=>"���̲����ڣ�","data"=>array());
        }
        $flowStep= explode(',', $flowInfo['allStep']);  
        //Current flow name                   
        $flowName = $flowInfo['flowType'];   
        //Current flow add one step           
        $curStep= isset($params['step'])?$params['step']:$flowInfo['currentStep']+1;           
        //Update previous step status
        $prevStepInfo = $this->db->select_raw('dev_flows_step', '*', "flowId={$flowInfo['id']} and step<{$curStep}  order by id desc");
        if(!empty($prevStepInfo)){
            foreach ($prevStepInfo as $key=>$val){
                if((!isset($params['step'])||$params['opt_type']==1)&&$val["prcsFlag"]!="�������"){
                    $deliverUserId = isset($params['deliverUserId']) ? $params['deliverUserId'] : $_SESSION['LOGIN_UID'];
                    $this->db->update('dev_flows_step',array('deliverTime'=>date('Y-m-d H:i:s'),"deliverUserId"=>$deliverUserId,'prcsFlag'=>"�������"),"id='{$val['id']}'");
                }
            }
        }

        //Current flow name
        $authInfo=$this->db->select_one('dev_runpriv', '*'," type='{$flowName}' and name='{$flowStep[$curStep]}' ");
        if(empty($authInfo)){
            return array("status"=>200,"msg"=>"���̴�����ϣ�","data"=>array());
        }   

        //Current flow step info
        $stepInfo = $this->db->select_one('dev_flows_step', '*', "flowId={$flowInfo['id']} and flowPrcs='{$flowStep[$curStep]}'  order by id desc ");

        if (empty($stepInfo)||$stepInfo["prcsFlag"]=="�������") {
            $temp = array();
            $temp['flowId'] = $flowInfo['id'];
            $temp['flowType'] = $flowInfo['flowType'];
            $temp['flowCaseId'] = $flowInfo['flowCaseId'];
            $temp['userId'] = !empty($params['userId'])?$params['userId']:'';
            $temp['atTime'] = date('Y-m-d H:i:s');   //�ɷ�ʱ��
            //$temp['prcsTime'] = $flowInfo['beginTime'];  //����ʱ��
            //$temp['deliverTime'] = date('Y-m-d H:i:s');  //�������ʱ��
            $temp['no_confirm'] = !empty($params['no_confirm'])?$params['no_confirm']:0;
            $temp['prcsFlag'] = !empty($params["prcsFlag"])?$params["prcsFlag"]: 'δ����';//����״̬(1-δ����,2-������,3-�������,)
            $temp['flowPrcs'] =$flowStep[$curStep];
            $temp['step'] =$curStep;
            $temp['opFlag'] = 1;//�Ƿ�����(0-����,1-����)
            $temp['parentPrcs'] = 0;//��һ������FLOW_PRCS
            $temp['step_model'] = !empty($params['step_model'])?$params['step_model']:1;//���ģʽ
            $ret = $this->db->insert('dev_flows_step', $temp);
            if ($ret == false) {
                return array("status"=>500,"msg"=>"���̲������ʧ�ܣ�","data"=>array());
            }
            //UPdate major flow status
            $this->db->update('dev_flows',array('currentStep'=>$curStep,'status'=>$flowStep[$curStep]),"id='{$flowInfo['id']}'");

        }else{
            if($stepInfo["prcsFlag"]!="�������"){ 
                $prcsFlag="������";
                $prcsFlag=!empty($params["prcsFlag"])?$params["prcsFlag"]:$prcsFlag;
                $dataInfo=array("prcsUserId"=>$_SESSION['LOGIN_UID'],'prcsFlag'=>$prcsFlag);
                if($params["prcsFlag"]=="�������"){
                    $dataInfo['deliverTime']=date('Y-m-d H:i:s');
                    $dataInfo['deliverUserId']=$_SESSION['LOGIN_UID'];
                }else{
                    $dataInfo['prcsTime']=date('Y-m-d H:i:s');
                }
                
                $this->db->update('dev_flows_step',$dataInfo,"id='{$stepInfo['id']}'");               
            }   
        }
        
        return array("status"=>200,"msg"=>"�ύ�ɹ���","data"=>array("flow_id"=>$flowInfo["id"],"opt"=>"����ύ"));
    }
    
    /**
     * Flow reback
     * @param array $params
     * @return array
     */
    public function reback($params){
        $flowInfo=$this->get_current_flow(array("flowId"=>$params["flowId"]));
        if(empty($flowInfo)){
            return array("status"=>500,"msg"=>"���̲����ڣ�","data"=>array());
        }
        $prevStepInfo=array();
        if(isset($params["step"])){
            $allStep= explode(",", $flowInfo["allStep"]);
            $prevStepInfo = $this->db->select_one('dev_flows_step', '*', "flowId={$params["flowId"]} and step={$params['step']}  order by id desc");              
        }else{
            //find previous step
            $prevStepInfo = $this->db->select_one('dev_flows_step', '*', "flowId={$params["flowId"]} and step<{$flowInfo["currentStep"]}  order by id desc");
        }

        if(empty($prevStepInfo)){
            return array("status"=>500,"msg"=>"���̲����ڣ�","data"=>array());
        }
        $this->db->query("BEGIN");//����ʼ
        try {
            //Update current flow status
            $this->db->update('dev_flows',array("currentStep"=>$prevStepInfo["step"],"status"=>$prevStepInfo["flowPrcs"]),"id='{$params["flowId"]}'");   
            //Update current flow step status
            $this->db->update('dev_flows_step',array('deliverTime'=>date('Y-m-d H:i:s'),"deliverUserId"=>$_SESSION['LOGIN_UID'],'prcsFlag'=>"�������"),"id='{$flowInfo['step_id']}'");
            //Insert new flow step
            $temp = array();
            $temp['flowId'] = $flowInfo['id'];
            $temp['flowType'] = $flowInfo['flowType'];
            $temp['flowCaseId'] = $flowInfo['flowCaseId'];
            $temp['userId'] = $prevStepInfo['userId'];
            $temp['atTime'] = date('Y-m-d H:i:s');   //�ɷ�ʱ��
            $temp['prcsFlag'] = 'δ����';//����״̬(1-δ����,2-������,3-�������,)
            $temp['flowPrcs'] =$prevStepInfo["flowPrcs"];
            $temp['step'] =$prevStepInfo["step"];
            $temp['opFlag'] = 1;//�Ƿ�����(0-����,1-����)
            $temp['parentPrcs'] = 0;//��һ������FLOW_PRCS
            $temp['backPrcs'] = 1;//��һ������FLOW_PRCS
            $temp['step_model'] = $prevStepInfo["step_model"];//���ģʽ
            $temp['comment'] = !empty($params["comment"])?$params["comment"]:"";//�˻�ԭ��
            $temp['freeItem'] = !empty($params["freeItem"])?$params["freeItem"]:"";//�˻�����
            $flowStepId = $this->db->insert('dev_flows_step', $temp);
            
            $this->db->query("COMMIT");//�ύ����
        } catch (Exception $e) {
            $this->db->query("ROLLBACK");//����ع�
            return array("status"=>500,"msg"=>$e->getMessage(),"data"=>array());   
        }

        return array("status"=>200,"msg"=>"�ύ�ɹ���","data"=>array(
            "flow_step_id"=>$flowStepId,
            "flow_id"=>$flowInfo['id'],
            "uids"=>$prevStepInfo["deliverUserId"],
            "opt"=>"�˻�����"
        ));
    }

}



?>
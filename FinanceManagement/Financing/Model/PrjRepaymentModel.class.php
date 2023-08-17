<?php
class PrjRepaymentModel extends BaseModel{
    protected $tableName = 'prj_repayment';
    const ORDER_NO_PER = "HKDD";
    
    const STATUS_WAIT_DEAL_WITH = "1";//未处理
    const STATUS_DEAL_WITH_SUCCESS = "2";//处理成功
    const STATUS_NOT_DEAL_WITH = "3";//不处理
    const STATUS_DEALING= "4";//处理中
    
    
    protected $_auto = array(
            array('ctime','time',self::MODEL_INSERT,'function'),
            array('mtime','time',self::MODEL_BOTH,'function'),
    );
    
    
    function getId($prjId){
    	$info = $this->where(array("prj_id"=>$prjId))->find();
    	if(!$info){
    		return $this->addData($prjId);
    	}
    	return $info['id'];
    }
    
    function addData($prjId){
        $prjId = (int) $prjId;
        $info = M("prj")->where(array("id"=>$prjId))->find();
        if(!$info){
            MyError::add("异常,项目不存在!");
            return false;
        }
        
        $prjRepaymentData['prj_type'] = $info['prj_type'];
        $prjRepaymentData['prj_id'] = $prjId;
        $prjRepaymentData['capital'] = $info['demand_amount'];
        $prjRepaymentData['uid'] = $info['uid'];
        $prjRepaymentData['status'] = self::STATUS_WAIT_DEAL_WITH;
        
        if($this->create($prjRepaymentData)){
            $id=$this->add();
            if(!$id){
                MyError::add("写入项目还款数据失败!");
                return false;
            }
            return $id;
        }else{
            MyError::add($this->getError());
            return false;
        }
    }
    
}
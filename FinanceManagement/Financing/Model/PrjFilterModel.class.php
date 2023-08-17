<?php
/**
 * User: BEN
 * Create: 14-6-25 下午2:02
 * Mail: xiaobenjiang@upg.cn
 */

class PrjFilterModel extends BaseModel {

    protected $tableName = "prj_filter";

    const CLIENT_TYPE_ALL = 0;

    const CLIENT_TYPE_PC = 1;

    const CLIENT_TYPE_MOBILE = 2;

    const CLIENT_TYPE_API = 3;

    public $client_type_config = array('0'=>'全部','1'=>'网站','2'=>'微信','3'=>'手机APP');

    public function addType($uid, $prj_id, $client_type){
        //$client_type_array = is_array($client_type)?$client_type : array_filter(explode(',', $client_type), 'trim');
//        $client_type_array = empty($client_type_array)?array(0=>0):$client_type_array;
        if ($client_type==0) return true;
        try{
            //foreach($client_type_array as $type){
                $data['prj_id'] = $prj_id;
                //$data['client_type'] = $type;
                $data['client_type'] = (int)$client_type;
                $data['uid'] = $uid;
                $data['ctime'] = $data['mtime'] = $_SERVER['REQUEST_TIME'];
                return $this->data($data)->add();
            //}
            //return true;
        }catch (Exception $e){
            return $e->getMessage();
        }

    }

    public function updateType($uid, $prj_id, $client_type){
        $client_type_array = is_array($client_type)?$client_type : array_filter(explode(',', $client_type), 'trim');
        $record = $this->where(array('prj_id'=>$prj_id))->select();
        if($record){    //update
            foreach($record as $val){
                if(in_array($val['client_type'], $client_type_array)){
                    $data['mtime'] = $_SERVER['REQUEST_TIME'];
                    $data['client_type'] = (int) $client_type;
                    $this->where(array('prj_id'=>$prj_id))->save($data);
                }else{
                    $this->addType($uid, $prj_id, $client_type);
                }
            }
        }else{  //insert
            $this->addType($uid, $prj_id, $client_type);
        }

    }

    public function getClientType($prj_id){
        //$types = array();
        $data = $this->where(array('prj_id'=>$prj_id))->field("client_type")->find();
        return $data['client_type']?(int)$data['client_type']:0;
        /*if($data){
            foreach($data as $val){
                $types[] = $val['client_type'];
            }
        }
        return $types;*/
    }

    public function getClientPermission($const_client_type, $prj_id){
        $client_type = $this->getClientType($prj_id);
        if(!$client_type) return true;
        if(in_array($client_type, array(0, $const_client_type ))){
        //if($const_client_type == $client_type){
            return true;
        }
        errorReturn("仅针对".$this->client_type_config[$client_type]."端投资的用户");
        return false;
    }
}
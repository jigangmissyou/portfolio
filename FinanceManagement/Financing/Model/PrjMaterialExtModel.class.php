<?php
/**
 * 项目扩展资料
 * @author luoman
 *
 */
class PrjMaterialExtModel extends BaseModel {
    
    protected $tableName = 'prj_material_ext';
    
    /**
     * 出借人确认书 后续的下面添加
     * @var number
     */
    const CJQQRS = 1;
    
    function uploadExtMaterial($prjId, $itemId){
        $file = I("file");
        $mdUpload = D('Public/Upload');
        $movied = $mdUpload->moveFiles('img', $file);
        $info = $mdUpload->parseFileParam($movied);
        $info = $info['attach'];
        
        if(!$info['id']) {
            return errorReturn('上传失败！');
        }
        
        $now = time();
        $data = array();
        $data['prj_id'] = $prjId;
        $data['item_id'] = $itemId;
        $data['path'] = $movied;
        $data['relate_path'] = $info['saveapppath'];
        $data['file_name'] = $info['savename'];
        $data['file_type'] = $info['extension'];
        $data['mtime'] = $now;
        $data['ctime'] = $now;
        $ret = $this->add($data);
        if($ret === false) {
            return errorReturn('系统异常：添加审核资料失败！');
        }
        $tmp = D("Zhr/Guarantee")->parseAttach($movied);
        $data['id'] = $ret;
        $result = array_merge($data, $tmp);
        return $result;
    }
    
    /**
     * 删除图片
     * @param int $id 图片id
     * @return string|boolean
     */
    function delExtMaterial($id){
        $chek = $this->find($id);
        if(!$chek){
            $this->error = '数据不存在';
            return false;
        }
        if($this->where(array('id' => $id))->delete()){
            return true;
        }else{
            $this->error = '删除失败';
            return false;
        }
    }
    
    /**
     * 根据项目ID获取资料
     * @param number $prjId
     * @param number $itemId
     */
    function getMaterialByPrjId($prjId, $itemId = 0){
        $spid = M('prj')->where(array('id' => $prjId))->getField('spid');
        if($spid) $prjId = $spid;
        $condition = array('prj_id' => $prjId);
        $itemId && $condition['item_id'] = $itemId;
        $list =  $this->where($condition)->select();
        if($list){
            foreach($list as &$val){
                $data = D("Zhr/Guarantee")->parseAttach($val['path']);
                $val = array_merge($val, $data);
            }
        }
        return empty($list)?array():$list;
    }
}

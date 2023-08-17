<?php
class UserPhotoModel extends BaseModel{
    protected $tableName = 'user_photo';
    /**
     * 获取用户头像
     * @param unknown $uid
     */
    function getAvatar($uid){
        $uid = intval($uid);
        $where['uid'] = $uid;
        $where['is_key'] = 1;
    	$ava =$this->where($where)->find();
//     	$ava['photo_path'] = $ava && $ava['photo_path']? imgUrl($ava['photo_path']) : "";
    	$ava['photo_path'] = D("Public/Upload")->parseFileParam($ava['photo_path']);
    	return $ava;
    }
    
    /**
     * 添加或更新头像
     * @param unknown $uid
     * @param unknown $imgPath
     * @return boolean
     */
    function saveOrUpdateAvatar($uid,$imgPath){
        $where['uid'] = $uid;
        $where['is_key'] = 1;
    	$info = $this->where($where)->find();
    	if($info){
    		$data['photo_path'] = $imgPath;
    		$data['mtime'] = time();
    		$this->where($where)->save($data);
    	}else{
    	    $data['photo_path'] = $imgPath;
    	    $data['uid'] = $uid;
    	    $data['is_key'] = 1;
    	    $data['ctime'] = time();
    	    $this->add($data);
    	}
    	return true;
    }
    
}
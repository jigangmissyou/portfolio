<?php
/**
 * // +----------------------------------------------------------------------
// | UPG
// +----------------------------------------------------------------------
// | Copyright (c) 2012 http://upg.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: Jigang Guo <guojigang@xihehui.com>
// +----------------------------------------------------------------------
// user model
 */
class LoginLogModel extends BaseModel{
  protected $tableName = 'user_login_log';
  const LOGIN_LOG_DATA_KEY = 'LOGIN_LOG_DATA_KEY';
  
  function save($uid){
  	 $data['uid'] = $uid;
  	 $data['ctime'] = time();
  	 $data['ip'] = get_client_ip();
  	 $id = $this->add($data);
  	 if(!$id) return false;
  	 $key = self::LOGIN_LOG_DATA_KEY;
  	 cache_with_mi_no($key,null);
  }
  
  //获取用户登录日志
  function getUserLoginLog($uid){
  	$key = self::LOGIN_LOG_DATA_KEY;
  	
  	$result = cache_with_mi_no($key);
  	if($result) return $result;
  	$info = $this->where(array("uid"=>$uid))->order(array("ctime"=>"desc"))->limit(2)->select();
  	if(!$info) return array();
  	$data = array();
  	$data['last_login_time'] = (isset($info[1]) && $info[1]) ?  date('Y-m-d H:i:s',$info[1]['ctime']) : "";
  	//本月登录次数
  	$where['uid'] = $uid;
  	$where['ctime'][] = array("EGT",strtotime(date('Y-m-01')));
  	$where['ctime'][] = array("LT",strtotime(date('Y-m-01',strtotimeX("+1 months"))));
  	$count = $this->where($where)->field(" count(*) as cnt")->find();
  	$data['count'] = $count['cnt'];
      cache_with_mi_no($key, $data, array('expire'=>3600));
  	return $data;
  }

	/**
	 * 根据属性获取登录记录
	 * @param array $attributes 检索属性
	 * @return bool|mixed
	 */
	public function getLoginLogByAttributes(array $attributes)
	{
		if(empty($attributes))
			return false;

		if(isset($condition['stime']) && isset($condition['etime'])){
			$condition['ctime'] = array(array('EGT',$condition['stime']),array('ELT',$condition['etime']));
			unset($condition['stime']);
			unset($condition['etime']);
		}

		if(isset($attributes['stime'])){
			$attributes['ctime'] = array('EGT',$attributes['stime']);
			unset($attributes['stime']);
		}

		if(isset($attributes['etime'])){
			$attributes['ctime'] = array('ELT',$attributes['etime']);
			unset($attributes['etime']);
		}

		return $this->where($attributes)->find();
	}

	/**
	 * 根据检索条件获取用户登记录
	 * @param array $condition  检索条件
	 * @return bool
	 */
	public function getLoginLogCountByCondition(array $condition)
	{
		if(empty($condition))
			return false;

		if(isset($condition['stime']) && isset($condition['etime'])){
			$condition['ctime'] = array(array('EGT',$condition['stime']),array('ELT',$condition['etime']));
			unset($condition['stime']);
			unset($condition['etime']);
		}

		if(isset($condition['stime'])){
			$condition['ctime'] = array('EGT',$condition['stime']);
			unset($condition['stime']);
		}

		if(isset($condition['etime'])){
			$condition['ctime'] = array('ELT',$condition['etime']);
			unset($condition['etime']);
		}

		return $this->where($condition)->count();
	}
  
}
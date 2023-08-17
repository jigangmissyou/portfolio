<?php

/**
 * Created by PhpStorm.
 * User: wrr
 * Date: 14-10-15
 * Time: 下午2:07
 */
class AppointModel extends BaseModel
{

    protected $tableName = 'invest_appoint';

    static public $timeLimit = array(
        '30' => "30天",
        '60' => "60天",
        '90' => "90天",
        '180' => "180天",
        '365' => "365天"
    );

    //预约提交中
    const APPOINT_COMMIT = 1;
    //预约成功
    const APPOINT_SUCCESS = 2;
    //预约失效(有效期到了)
    const APPOINT_FAIL = 3;
    //预约取消(金额不足 投资或者提现)
    const APPOINT_CANCEL = 4;

    const APPOINT_ENABLE = 1;   //可用状态

    const APPOINT_DISENABLE = 2;//不可用

    const CTIME_FORMAT = "Y年m月d日";

    const APPOINT_CANCEL_CODE_1 = 41;//手动取消
    const APPOINT_CANCEL_CODE_2 = 42;//提现导致资金不足取消
    const APPOINT_CANCEL_CODE_3 = 43;//投资其他项目导致资金不足取消
    const APPOINT_CANCEL_CODE_4 = 44;//项目预约时候取消

    protected $_validate = array(
        array('min_rate', 'number', '最小年化利率必填', 1),
        array('min_rate', '/^\d+(\.\d{1,2})?$/', '利率必须为数字，最多2位小数', self::EXISTS_VALIDATE, 'regex'),
        array('max_rate', 'number', '最大年化利率必填', 1),
        array('max_rate', '/^\d+(\.\d{1,2})?$/', '利率必须为数字，最多2位小数', self::EXISTS_VALIDATE, 'regex'),
        array('min_time', 'number', '最小项目期限必填', 1),
        array('max_time', 'number', '最大项目期限必填', 1),
        array('min_money', 'number', '最小投资金额必填', 1),
        array('max_money', 'gtZero', '最小投资金额必须大于0', self::EXISTS_VALIDATE, 'function'),
        array('max_money', 'number', '最大投资金额', 2),
//        array('is_all_money', 'number', '是否全部可用余额', 1),
    );

    /**
     * 获取用户当前的预约情况
     * @param $uid
     * @return mixed
     */
    public function getCurrentStatus($uid)
    {
        return $this->where(array(
            'uid'=>$uid,
        ))->field('id,status,is_enable')->order('id DESC')->find();
    }

    /**
     * 获取用户的预约状态
     * @param $uid
     * @return int
     */
    public function is_has_appoint($uid)
    {
        $res = $this->getCurrentStatus($uid);
        if(is_array($res) && $res['is_enable'] == self::APPOINT_ENABLE){
            return $res['id'];
        }
        $this->error = "当前没有可用的预约";
        return 0;
    }

    /**
     * 获取全部
     * @return array|mixed
     */
    public function getTotalAppoint()
    {
        $ret = array();
        $sql = "select count(*) as num,sum(amount) as total from fi_invest_appoint where status=" . self::APPOINT_SUCCESS;
        $result = $this->query($sql);
        $result = $result[0];
        $ret['num'] = $result['num'] ? $result['num'] : 0;
        $ret['total'] = humanMoney($result['total'], 2, false);

        return $ret;
    }

    /**
     * 获取用户的预约记录
     */
    public function getMyAppoint($uid)
    {
        $ret = array();
        $sql = "select count(*) as num,sum(amount) as total from fi_invest_appoint where status=" . self::APPOINT_SUCCESS . " and uid=" . $uid;
        $result = $this->query($sql);

        $ret['num'] = $result['num'] ? $result['num'] : 0;
        $ret['total'] = humanMoney($result['total'], 2, false);
        return $ret;
    }

    /**
     * 提交预约
     * @param $params
     * @return array|mixed
     */
    public function addAppoint(array $params)
    {

        if($this->is_has_appoint($params['uid'])){
            throw_exception("您已有过预约，请先取消之后再重新预约");
        }

        $data = $params;
        $data['appoint_remaining_money'] = $params['appoint_money'];
        //当天的八点半作为预约条件的过期时间
        $time = mktime(9,0,0);
        $data['appoint_end_time'] = $time+$params['appoint_day']*24*3600;//预约天数以后
        $data['ctime'] = time();
        $data['status'] = self::APPOINT_COMMIT;
        $data['is_enable'] = self::APPOINT_ENABLE;
        $aid = $this->add($data);
        if($aid == NULL){
            throw_exception('预约提交失败，请重试');
        }
        return $aid;
    }

    /**
     * 修改预约
     * @param $input
     * @param $id
     * @return bool
     */
    public function modifyAppoint($input, $id,$uid)
    {
        //如果没有可用的预约是不能修改的
        $has_appoint = $this->is_has_appoint($uid);
        if(!$has_appoint){
            throw_exception($this->getError());
        }
        $data = $this->_checkInput($input,$id,$uid);
        if(!$data){
            throw_exception($this->getError());
        }
        //修改appoint_remaining_money的值
        $data['appoint_remaining_money'] = $this->getReGetAppointRemainingMoney($input,$id);//重新获取剩余可预约金额
        $result = $this->where(array('id'=>$id))->save($data);
        if ($result) {
            //如果是追加预约,重新增加队列进行新的一轮匹配
            if ($data['appoint_remaining_money'] > 0) {
                service("Financing/Appoint")->rePushUserAppoint($id,$uid);
            }
            return true;
        }
        throw_exception($this->getError());
    }

    /**
     * 重新获取剩余可预约金额
     */
    public function getReGetAppointRemainingMoney($input,$id)
    {
        $field = array('appoint_money', 'appoint_remaining_money');
        $recode = D("Financing/Appoint")->where(array('id' => $id))->field($field)->find();
        $new_apppoint_remaining_money = $input['appoint_money'] - ($recode['appoint_money'] - $recode['appoint_remaining_money']);
        return $new_apppoint_remaining_money > 0 ? $new_apppoint_remaining_money : 0;
    }

    /**
     * @param $input
     * @param $id
     * @param $uid
     * @return bool
     * 对数据进行校验
     */
    public function _checkInput($input,$id,$uid)
    {

        try {
            $data = $this->getAppointDataById($id, $uid);
            !$data && throw_exception("记录不存在!");
            $addData = service("Financing/Appoint")->checkData($input,$data);
            $addData['mtime'] = time();//修改时间
            if ($input['appoint_day']) {
                $month = date("m", $data['ctime']);
                $day = date("d", $data['ctime']);
                $year = date("y", $data['ctime']);
                //创建日的九点时间
                $time = mktime(9, 0, 0, $month, $day, $year);
                $addData['appoint_end_time'] = $time + $input['appoint_day'] * 24 * 3600;//预约天数以后
            }
            return $addData;
        } catch (Exception $e) {
            $this->error = $e->getMessage();
            return false;
        }
    }

    /**
     * 取消预约
     * @param $id
     * @return bool
     */
    public function cancleAppoint($id,$uid)
    {
        $params = array(
            'uid' => $uid,
            'aid' => $id,
            'type' => self::APPOINT_CANCEL_CODE_1,
        );
        try {
            return queue('appoint_cancel', $id, $params);
        } catch (Exception $e) {
            return false;
        }

    }

    /**
     * 获取预约统计数据
     * @return array
     */
    public function getAppointData()
    {
        $ret = array();
        $totalSucMoney = $this->where("status=" . self::APPOINT_SUCCESS)->sum('amount');
        $ret['totalSucMoney'] = humanMoney($totalSucMoney, 2, false);
        return $ret;
    }

    public function getAppointDataById($id,$uid)
    {

        return $this->where(array(
                'id'=>(int)$id,
                'uid'=>(int)$uid,
            ))->find();
    }

    /**
     * 获取预约信息
     * @param $uid
     * @param $status
     * @return mixed
     */
    public function getAppointRecord($uid,$status='',$page=1,$num=5)
    {
        $where = array(
            'uid' => (int)$uid,
        );
        $status = $this->_checkStatus($status);
        if($status !== false){
            $where['status']=(int)$status;
        }
        $count = $this->where($where)->count();
        if($count <= 0){
            return array();
        }
        $res = $this
            ->where($where)
            ->order('id DESC')
            ->page($page,$num)
            ->select();
        if(!$res){
            return array();
        }
        foreach ($res as $key=>$val) {
            $res[$key]['min_rate'] = number_format($val['min_rate'] / 10, 2);
            $res[$key]['max_rate'] = number_format($val['max_rate'] / 10, 2);
            $res[$key]['min_money'] = humanMoney($val['min_money'], 2, false) . '元';
            $res[$key]['max_money'] = humanMoney($val['max_money'], 2, false) . '元';
            $res[$key]['amount'] = humanMoney($val['amount'], 2, false) . '元';
            $res[$key]['ctime'] = toDate($val['ctime']);
            $res[$key]['status_show'] = $this->statusShow($val['status'],'index');
            $res[$key]['appoint_money'] = humanMoney($val['appoint_money'], 2, false);
        }
        return array(
            'list'=>$res,
            'page_info'=>array(
                'total'=>$count,
                'page'=>$page,
                'total_page'=>(int)($count/$num)+1
            )
        );
    }

    private function _checkStatus($status='')
    {
        if(!empty($status) && array_key_exists((int)$status,array(
                self::APPOINT_COMMIT=>1,
                self::APPOINT_SUCCESS=>2,
                self::APPOINT_CANCEL=>4,
                self::APPOINT_FAIL=>3,
            )))
        {
            return (int)$status;
        }
        return false;
    }

    /**
     * 获取当前可预约的总人数
     */
    public function getCurrentEnableCount()
    {
        $where = array(
            'is_enable' => 1,
        );
        $cnt = $this->where($where)->count();
        return $cnt > 0 ? $cnt : 0;
    }

    /**
     * 获取当前的预约的配置
     * 1、限制发起预约
     */
    public function getAppointLimit()
    {
        $limit = D("Public/SystemConfig")->getValueByKey("appointManLimit");
        if(!$limit){
            return 0;
        }
        return (int)$limit;
    }

    /**
     * @return bool
     * 是否限制发起预约
     */
    public function isLimitApply($uid)
    {

        if ($this->is_has_appoint($uid)) {
            return 0;
        }
        $set_appoint_limit = $this->getAppointLimit();
        $current_enable_count = $this->getCurrentEnableCount();
        if ($set_appoint_limit > $current_enable_count) {
            return 0;
        }
        return 1;
    }

    public function statusShow($status,$page = 'index')
    {
        $status_show = array(
            'index' => array(
                AppointModel::APPOINT_COMMIT => '预约中',
                AppointModel::APPOINT_SUCCESS => '已匹配',
                AppointModel::APPOINT_FAIL => '已失效',
                AppointModel::APPOINT_CANCEL => '已取消',
                AppointModel::APPOINT_CANCEL_CODE_1 => '已取消',
                AppointModel::APPOINT_CANCEL_CODE_2 => '已取消',
                AppointModel::APPOINT_CANCEL_CODE_3 => '已取消',
                AppointModel::APPOINT_CANCEL_CODE_4 => '已取消',
            ),
            'view' => array(
                AppointModel::APPOINT_COMMIT => '预约中',
                AppointModel::APPOINT_SUCCESS => '已匹配',
                AppointModel::APPOINT_FAIL => '已失效',
                AppointModel::APPOINT_CANCEL => '已取消',
                AppointModel::APPOINT_CANCEL_CODE_1 => '已取消(用户取消)',
                AppointModel::APPOINT_CANCEL_CODE_2 => '已取消(账户资金不足)',
                AppointModel::APPOINT_CANCEL_CODE_3 => '已取消(账户资金不足)',
                AppointModel::APPOINT_CANCEL_CODE_4 => '已取消(系统取消)',
            )
        );
        return $status_show[$page][$status];
    }

}
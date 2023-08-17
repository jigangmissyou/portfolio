<?php

/**
 * Created by PhpStorm.
 * User: 001181
 * Date: 2015/4/24
 * Time: 14:58
 */
class UserBonusShareLogModel extends BaseModel
{
    protected $tableName = 'user_bonus_share_log';
    protected $msg = "";

    /**
     * 获取信息通过mobile
     */
    public function getListByWhere($where = array(), $field = "*")
    {
        $res = $this->where($where)->field($field)->select();
        return $res;
    }

    /**
     * 删除临时记录
     * @param type $where
     * @return type
     */
    public function deleteTmp($where)
    {
        return $this->where($where)->delete();
    }

    /**
     * 被分享者第一次登陆后check是否有奖励
     */
    public function initShareUserBonus($userinfo)
    {
        $mobile = $userinfo['mobile'];
        //查找是否有红包mobilefrom_typefrom_id
        $where = array(
            'mobile' => $mobile,
        );
        $tmp_list = $this->getListByWhere($where);
        if (!$tmp_list) {
            $this->error = "用户没有临时红包!";
            return false;
        }
        $save = array('status' => 1);
        $mod = D("Account/UserBonus");
        foreach ($tmp_list as $v) {
            //开启事务
            $mod->startTrans();
            $id = $v['id'];
            $uid = $v['uid'];
            $del = $this->deleteTmp(array('id' => $id));
            $message = "";//返回的消息

            D("Account/UserBonusLog")->where(array('id' => $v['user_bonus_log_id']))->save($save);
            $behavior = D("Account/UserBonusBehavior")->fetchByWhereInfo(array('id' => $v['user_behavior_id']));

            $sharing_data = array(
                'uid' => $uid,
                'from_type' => UserBonusModel::BONUS_FROM_TYPE_YAOYIYAO,
                'from_id' => UserBonusModel::BONUS_FROM_ID_YAOYIYAO,
                'amount' => $behavior['money'], //拆开红包的金额
            );
            //update
            $inc = $mod->incBonus($sharing_data);
            $save['user_bonus_id'] = $inc;
            $proc = D("Account/UserBonusBehavior")->where(array('id' => $v['user_behavior_id']))->save($save);
            if ($proc && $del) {
                $mod->commit();
                $message .= "sucess" . $mod->getLastSql() . "\r\n";
            } else {
                $mod->rollback();
                $message .= "fail" . $mod->getError() . "\r\n";
            }
        }
        return $message;
    }


    /**
     * 临时记录处理的记录
     */
    public function updateProStatus($id, $status = 1, $error = 'ok')
    {
        $where = array(
            'id' => $id,
        );
        try {
            $res = $this->where($where)->save(array('status' => $status, 'status_bak' => $error));
            if (!$res) {
                throw_exception($this->getError());
            }
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    public function getMsg()
    {
        return $this->msg;
    }

    public function getCount($where)
    {
        $count = $this->where($where)->count();
        return $count;
    }
} 
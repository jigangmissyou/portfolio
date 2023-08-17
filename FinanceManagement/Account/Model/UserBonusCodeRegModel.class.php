<?php

/**
 * Created by PhpStorm.
 * User: 001181
 * Date: 2015/4/23
 * Time: 11:03
 */
class UserBonusCodeRegModel extends BaseModel
{
    protected $tableName = 'user_bonus_code_reg';

    protected $_auto = array(
        array('ctime', 'time', self::MODEL_INSERT, 'function'),
    );

    /**
     * @param $data
     * 通过输入注册码以后获得金额进入日志记录
     */
    public function addLog($data)
    {
        try {
            $validata = $this->_checkAddLog($data);
            $res = $this->add($validata);
            if ($res) {
                return true;
            }
            throw_exception($this->error);
        } catch (Exception $e) {
            $this->error = $e->getMessage();
            return false;
        }

    }

    private function _checkAddLog($data)
    {
        if (!$data['uid']) {
            throw_exception("uid必须填写");
        }
        if (!$data['code']) {
            throw_exception("code必须填写");
        }
        if (!$data['bonus']) {
            throw_exception("code必须填写");
        }
        $data['ctime'] = time();
        return $data;
    }
} 
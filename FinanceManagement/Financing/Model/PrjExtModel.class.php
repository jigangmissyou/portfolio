<?php

/**
 * Created by PhpStorm.
 * User: will
 * Date: 15/9/9
 * Time: 14:23
 */
class PrjExtModel extends BaseModel
{
    protected $tableName = 'prj_ext';


    /**
     * @param $prj_id
     * 获取机构的数量
     */
    public function getGuarantorNum($prj_id)
    {
        $info = $this->where(array('prj_id' => $prj_id))->field('guarantor2_id,guarantor3_id')->find();
        if (!$info) {
            return 0;
        }
        $num = 1;
        if ($info['guarantor2_id']) {
            $num++;
        }
        if ($info['guarantor3_id']) {
            $num++;
        }

        return $num;
    }
}
<?php
/**
 * Created by PhpStorm.
 * User: luoman
 * Date: 16/9/29
 * Time: 下午3:06
 */

class PrjRepayZsLogModel extends BaseModel
{

    const STATUS_INIT = '0';
    const STATUS_WAIT = '00';
    const STATUS_ING = '10';
    const STATUS_SUCCESS = '20';
    const STATUS_FAIL = '30';
    const STATUS_REQUEST_FAIL = '90'; //请求的时候失败了

    /**
     * 鑫合汇和浙商的还款对接请求和响应日志
     * @param $prj_id
     * @param $type
     * @param $request_header
     * @param $request_body
     * @return mixed
     */
    public function addZsRepayLog($prj_id, $type, $request_header, $request_body) {
        $prj_repay_zs_log_model = D('Financing/PrjRepayZsLog');

        $now = time();
        $save_data = [
            'prj_id' => $prj_id,
            'type' => $type,
            'request_header' => var_export($request_header, true),
            'request_body' => $request_body,
            'request_time' => time(),
            'status' => self::STATUS_INIT,
            'ctime' => $now,
            'mtime' => $now,
        ];
        $add_result = $prj_repay_zs_log_model->add($save_data);
        return $add_result;
    }
}
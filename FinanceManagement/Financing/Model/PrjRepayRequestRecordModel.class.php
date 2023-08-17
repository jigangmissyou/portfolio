<?php
/**
 * Created by PhpStorm.
 * User: luoman
 * Date: 16/9/22
 * Time: 下午5:23
 */

class PrjRepayRequestRecordModel extends BaseModel
{

    const STATUS_REQUEST_INIT = 0;
    const STATUS_REQUEST_SUCCESS = 1; //1-请求成功
    const STATUS_REQUEST_FAIL = 2; //2-请求失败
    const STATUS_RESPONSE_SUCCESS = 3; //3-响应成功
    const STATUS_RESPONSE_FAIL = 4; //4-响应失败(需要重试)

    const ZS_STATUS_INIT = '0';
    const ZS_STATUS_WAIT = '00';
    const ZS_STATUS_ING = '10';
    const ZS_STATUS_SUCCESS = '20';
    const ZS_STATUS_FAIL = '30';
}
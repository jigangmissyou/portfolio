<?php
/**
 * User: 000802
 * Date: 2013-12-31 10:44
 * $Id$
 *
 * Type: Service
 * Group: Financing
 * Module: 企业融资
 * Description:
 */

class CorpFinancingService extends BaseService {
    private $mdCorpFinancing;
    public function __construct() {
        $this->mdCorpFinancing = D('Financing/CorpFinancing');
    }


    // 检测企业是否存在
    public function fsAPICorpCheck($corp_name) {
        try {
            $ret = $this->mdCorpFinancing->fsAPICorpCheck($corp_name);
            return array(1, '', $ret);
        } catch (Exception $e) {
            return array(0, $e->getMessage(), '');
        }
    }


    // 审核通过的企业列表
    public function fsAPICorpList($keyword='', $page=1, $page_size=10) {
        try {
            $ret = $this->mdCorpFinancing->fsAPICorpList($keyword, $page, $page_size);
            return array(1, '', $ret);
        } catch (Exception $e) {
            return array(0, $e->getMessage(), '');
        }
    }


    // 供数据平台和UBSP更新融资状态
    public function APIUpdateStatus($api_secret, $fs_cfid,  $check_status, $corp_id=0, $check_desc='') {
        try {
            return $this->mdCorpFinancing->APIUpdateStatus($api_secret, $fs_cfid,  $check_status, $corp_id, $check_desc);
        } catch (Exception $e) {
            return array(0, $e->getMessage());
        }
    }


    // 供数据盘台向这边同步融资数据
    public function APISync($api_secret, $input=array()) {
        try {
            if(get_magic_quotes_gpc())
                $data = stripslashes($input['data']);
            else
                $data = $input['data'];
            $data = htmlspecialchars_decode($data);
            $data = json_decode($data, TRUE);
            if(!$data) return array(0, '解析数据失败！');

            foreach ($data as $input) {
                $ret = $this->mdCorpFinancing->APISync($api_secret, $input);
            }
            return $ret;
        } catch (Exception $e) {
            return array(0, $e->getMessage());
        }
    }

    //获取云融资企业信息
    public function APIGetCorp($api_secret, $input=array()) {
        try {
            if(get_magic_quotes_gpc())
                $data = stripslashes($input['data']);
            else
                $data = $input['data'];
            $data = htmlspecialchars_decode($data);
            $data = json_decode($data, TRUE);
            if(!$data) return array(0, '解析数据失败！');
//             $ret = D("Zhr/FinanceCorp")->initCorpInfo($api_secret, $data);
            $ret = D("Zhr/FinanceCorp")->initCorpFromYrz($api_secret, $data);
            
            return $ret;
        } catch (Exception $e) {
            return array(0, $e->getMessage());
        }
    }


    // 获取行业名称
    public function getTradeName($trade_id) {
        $cache_key = 'TRADE_' . $trade_id;
        $cached = S($cache_key);
        if($cached) return $cached;

        $trade = M('dictTrade')->where(array('id' => $trade_id))->find();
        if(!$trade) return '';

        S($cache_key, $trade['trade_name_cn']);
        return $trade['trade_name_cn'];
    }


    // 获取区域显示
    public function getAreaById($city_id, $area_id=0, $province_id=0) {
        $cache_key = 'TRADE_' . $city_id . '-' . $area_id . '-' . $province_id;
        $cached = S($cache_key);
        if($cached) return $cached;

        $oModel = M('dictArea');
        $city = $oModel->where(array('id' => $city_id))->find();
        if(!$city) return '';

        $ret = $city['name_cn'];
        if($area_id) {
            $area = $oModel->where(array('id' => $area_id))->find();
            if($area) $ret .= ' ' . $area['name_cn'];
        }
        if($province_id) {
            $province = $oModel->where(array('id' => $province_id))->find();
            if($province) $ret = $province['name_cn'] . ' ' . $ret;
        }

        S($cache_key, $ret);
        return $ret;
    }



}
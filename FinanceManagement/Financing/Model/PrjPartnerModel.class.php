<?php
/**
 * User: 000802
 * Date: 2013-11-27 13:54
 * $Id$
 *
 * Type: Model
 * Group: Financing
 * Module: 项目
 * Description: 稳保益合伙企业
 *
 * 基金项目（稳保益）的时候才会有合伙企业
 * 0、第50个投标就必须使用第二个合伙企业
 * 1、发布项目：需添加第一个合伙企业
 * 2、修改项目：修改第一条合伙企业
 * 3、项目管理：投标中的项目可以添加合伙企业
 * 4、增加合伙企业统计（countInc）：投标
 * 4、减少合伙企业统计（countDec）：退标
 */


 class PrjPartnerModel extends BaseModel {
     protected $tableName = 'prj_partner';

     const MAX_BID_COUNT = 49;  // 一个合伙企业最大使用次数

     protected $_validate = array(
         array('partner', 'require', '企业名称必填'),
     );


     public function create($data='',$type='') {
         $data = parent::create($data, $type);
         if(!$data) {
             throw_exception($this->getError());
         }

         // 自定义验证
         // ...

         return $data;
     }


     /**
      * 添加合伙企业
      *
      * @param $prj_id
      * @param $name
      * @return array
      */
     public function addPartner($prj_id, $name) {
         $project = M('Prj')->find($prj_id);
         if(!$project) {
             throw_exception('关联项目不存在！');
         }
         if($project['prj_type'] != PrjModel::PRJ_TYPE_C) {
             throw_exception('只有稳益保才能添加合伙企业');
         }
         if(!(in_array($project['status'], array(PrjModel::STATUS_WATING))
           || in_array($project['bid_status'], array(PrjModel::BSTATUS_BIDING)))) {
             throw_exception('只有投标中状态下才能添加合伙企业！');
         }

         $now = time();
         $data = array(
             'prj_id' => $prj_id,
             'partner' => $name,
             'protocol_path1' => '',
             'protocol_path2' => '',
             'protocol_path3' => '',
             'user_count' => 0,
             'ctime' => $now,
             'mtime' => $now,
         );

         $data = $this->create($data);
         $ret = $this->add($data);
         if($ret === FALSE) {
             throw_exception('系统异常：添加合伙企业失败！');
         }

         return array(1, '添加成功！');
     }


     /**
      * 更新合伙企业
      *
      * @param $partner_id
      * @param $name
      * @return array
      */
     public function updatePartner($partner_id, $name) {
         $where = array(
             'id' => $partner_id,
         );
         $partner = $this->find($partner_id);
         if(!$partner) {
             throw_exception('修改项不存在！');
         }
         if($partner['user_count'] > 0) {
             throw_exception('合伙企业下已有人投标，不可修改！');
         }

         $now = time();
         $data = array(
             'partner' => $name,
//             'protocol_path1' => '',
//             'protocol_path2' => '',
//             'protocol_path3' => '',
//             'user_count' => 0,
//             'ctime' => $now,
             'mtime' => $now,
         );

         $data = $this->create($data);
         if($this->where($where)->save($data) === FALSE) {
             throw_exception('系统异常：修改合伙企业失败！');
         }

         return array(1, '修改成功！');
     }


     /**
      * 增加合伙企业统计
      *
      * @param $prj_id
      */
     public function countInc($prj_id) {
         $partner = $this->getPartner($prj_id);
         if(!$partner) {
             throw_exception('无可用合伙企业，请添加新的合伙企业！');
         }

         $where = array(
             'id' => $partner['id'],
         );
         if($this->where($where)->setInc('user_count', 1) === FALSE) {
             throw_exception('更新合伙企业统计出错！');
         }
     }


     /**
      * 减少合伙企业统计
      *
      * @param $prj_id
      */
     public function countDec($prj_id) {
         $where = array(
             'prj_id' => $prj_id,
         );
         $partner = $this->where($where)->order('id ASC')->find();
         if(!$partner) {
             throw_exception('未找到该项目的合伙企业！');
         }

         $where = array(
             'id' => $partner['id'],
         );
         if($this->where($where)->setDec('user_count', 1) === FALE) {
             throw_exception('更新合伙企业统计出错！');
         }
     }


     /**
      * 返回最早的一个人数未满的合伙企业
      * 小于49
      *
      * @param $prj_id
      * @return mixed
      */
     public function getPartner($prj_id) {
         $where = array(
             'prj_id' => $prj_id,
             'user_count' => array('LT', self::MAX_BID_COUNT),
         );
         return $this->order('id ASC')->where($where)->find();
     }
 }
<?php
/**
 * Created by PhpStorm.
 * User: luoman
 * Date: 2015/10/14
 * Time: 14:05
 */
class PrjRiskAuditModel extends BaseModel
{

    protected $tableName = 'prj_risk_audit';

    /**
     * 获取一个项目的风险审核
     * @param int $risk_audit_id
     * @param int $prj_info
     * @return mixed|string
     */
    public function getPrjRiskAudit($risk_audit_id = 0, $prj_info = array())
    {
        if (!$risk_audit_id = (int) $risk_audit_id) {
            $risk_audit_id = (int) M('prj_ext')->where(array('prj_id' => $prj_info['id']))->getField('risk_audit_id');
        }

        //兼容老数据 id定义为1
        if(!$risk_audit_id){
            $risk_audit_id = 1;
        }

        $risk_audit = array();
        $risk_audit_info = $this->where(array('id' => $risk_audit_id))->find();
        if ($risk_audit_info) {
            $replace = '<a href="' . $risk_audit_info['audit_subject_url'] .'" class="blue" target="_blank">' . $risk_audit_info['audit_subject'] .'</a>';
            $risk_audit[] = str_replace($risk_audit_info['audit_subject'], $replace, $risk_audit_info['audit_subject_desc']);
        }

        D('Financing/Prj');
        if ($prj_info['prj_business_type'] == PrjModel::PRJ_BUSINESS_TYPE_H) {
            $risk_audit[] = '借款金额由夸客大数据风控模型核定';
        }

        return $risk_audit;
    }

    /**
     * 获取一个项目的风险审核(app,wap)
     * @param int $risk_audit_id
     * @param int $prj_info
     * @return mixed|string
     */
    public function getPrjRiskAuditApp($risk_audit_id = 0, $prj_info = array())
    {
        if (!$risk_audit_id = (int) $risk_audit_id) {
            $risk_audit_id = (int) M('prj_ext')->where(array('prj_id' => $prj_info['id']))->getField('risk_audit_id');
        }

        //兼容老数据 id定义为1
        if(!$risk_audit_id){
            $risk_audit_id = 1;
        }

        $risk_audit = array();
        $risk_audit_info = $this->where(array('id' => $risk_audit_id))->find();
        if ($risk_audit_info) {
            //$replace = '<a href="' . $risk_audit_info['audit_subject_url'] .'" class="blue" target="_blank">' . $risk_audit_info['audit_subject'] .'</a>';
            //$risk_audit[] = str_replace($risk_audit_info['audit_subject'], $replace, $risk_audit_info['audit_subject_desc']);
            $risk_audit[] = $risk_audit_info['audit_subject_desc'];
        }

        D('Financing/Prj');
        if ($prj_info['prj_business_type'] == PrjModel::PRJ_BUSINESS_TYPE_H) {
            $risk_audit[] = '借款金额由夸客大数据风控模型核定';
        }

        return $risk_audit;
    }

}
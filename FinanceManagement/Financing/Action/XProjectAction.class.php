<?php
/**
 * 司马小鑫
 * Created by PhpStorm.
 * User: luoman
 * Date: 2016/3/29
 * Time: 16:54
 */
class XProjectAction extends BaseAction
{

    public function _initialize()
    {
        parent::_initialize();
    }

    public function ajaxBuyLog()
    {
        $id         = I('id', 0, 'intval');
        $page       = I("p",  1, 'intval');
        $page_size  = I('size',  20, 'intval');

        $page = max(1, $page);
        $page_size = max(1, min(100, $page_size));

        $x_prj = D('Financing/XPrj')->where(array('id' => $id))->find();
        $investor_list = service('Financing/XProject')->getInvestorList($id, $page, $page_size);

        if (is_array($investor_list['list'])) {
            foreach ($investor_list['list'] as $key => $each) {
                $investor_list['list'][$key]['real_name'] = marskName($each['real_name'], 1, 0);
                $investor_list['list'][$key]['money_view'] = humanMoney($each['money']);
                $investor_list['list'][$key]['ctime_view'] = date('Y-m-d H:i:s', $each['ctime']);
            }
        }

        $this->assign('xprjInfo', $x_prj);

        //融资规模
        $this->assign("demandAmount", humanMoney($x_prj['demand_amount'], 2));
        //剩余金额
        $this->assign("remainingAmount", humanMoney($x_prj['remaining_amount'], 2, false). "元");

        //分页
        $paging  = W("Paging",array("totalRows"=>$investor_list['page']['record_count'],"pageSize"=>$page_size,"parameter"=>array("id"=>$id)),true);
        $this->assign("paging",$paging);
        $this->assign("totalRow", $investor_list['page']['record_count']);
        $this->assign("list",$investor_list['list']);

        $this->assign('investor_list', $investor_list);

        $this->display();
    }

    /**
     * 参与司马小鑫
     */
    public function joinIn()
    {
        $user = $this->loginedUserInfo;
        $x_prj_id = (int) I('request.x_prj_id');
        $money = floatval(I('request.money', 0));
        $money = (int) bcmul($money, '100', 0);
        $pay_password = I('request.pay_password', '', 'trim');
        
        try {
            /* @var $x_prj_service XProjectService */
            $x_prj_service = service('Financing/XProject');
            $x_prj_service->joinInCheck($user, $x_prj_id, $money, $pay_password);
            $x_order_id = $x_prj_service->joinIn($user['uid'], $x_prj_id, $money);

            //启动匹配
            service('Financing/XProjectMatch')->noPerfectMatching($x_order_id);
            ajaxReturn('', '加入成功');
        } catch (Exception $e) {
            $message = $e->getMessage();
            ajaxReturn('', $message, 0);
        }
    }
}

<?php

class ProtocolService extends BaseService
{
    
    private $_protocol;

    //查看协议类型
    const PROTOCOL_TYPE_ORDER = 1;     //订单的协议类型

    const PROTOCOL_TYPE_FAST_CASH = 2;//快速变现的协议类型

    /**
     * 下载合同的的文件的名称
     * @var array
     */
    private $_titleList = array(
        'sdtview'=>'借款合同',
        'Sdtborrowerview'=>'借款合同',
        'Sdtlenderview'=>'借款合同',
        'appoint'=>'服务协议',
    );

    private $_methodList = array(
        'Appoint',//预约服务协议
        'Sdtview',//速兑通查看
        'Sdtborrowerview',//速兑通查看
        'Sdtlenderview',//速兑通查看
    );

    public function __construct()
    {
        parent::__construct();
        $protocol = C("Protocol");
        if (!$protocol){
            throw_exception("请先配置好协议模板的配置!");
        }
        $this->_protocol = C("Protocol");
    }

    public function getProtocolConf()
    {
        return $this->_protocol;
    }

    /**
     * 保存pdf
     * @param unknown $tplType
     * @param unknown $orderid
     * @return multitype:string number
     */
    function savePdf($tplType, $orderid, $prj_id=0)
    {
        list($tpl, $saveprotocolId) = $this->dataMatch($tplType, $orderid, $prj_id);
        if (!$tpl) {
            MyError::add("合同生成失败4".$tplType.'-'.$orderid);
            return false;
        }
        return $saveprotocolId;
    }

    /**
     * 数据匹配
     * @param unknown $tplType
     * @param unknown $orderid
     */
    function dataMatch($tplType, $orderid, $prj_id=0)
    {
        $paramData = $this->getMatchData($tplType, $orderid);
        $result = D('Admin/SystemManage')->getProtocolByNameEn($tplType, $paramData, $prj_id);

        $tpl = $result['tpl'];

        if (!$tpl) {
            MyError::add("合同生成失败5");
            return false;
        }

        if ($result['boolen']) {
            MyError::add($result['message']);
            return false;
        }

        return array($tpl, $result['id']);
    }


    /**
     * 获取匹配的数据
     * @param unknown $tplType
     * @param unknown $orderid
     */
    function getMatchData($tplType,$orderid){
        $config = $this->getProtocolConf();
        $sdt_tplid = $config['sdt_tplid'];
        //鑫合汇资金管理服务协议
        if($tplType == '2') {
            $protocolData['values'] = array();
        } elseif($tplType == '3') { // 已废弃, 合并到17
            $protocolData = service("Public/ProtocolData")->transferBonds($orderid);
        } elseif($tplType == '5') {
            $protocolData = service("Public/ProtocolData")->partnerSub($orderid);
        } elseif($tplType == '8') {
            $protocolData = service("Public/ProtocolData")->transferAmount($orderid);
        } elseif($tplType == '9') { // 已废弃, 合并到17
            $protocolData = service("Public/ProtocolData")->transferRight($orderid);
        } elseif($tplType == '10') {
            $protocolData = service("Public/ProtocolData")->transferBonds2($orderid);
        } elseif($tplType == '11') { // 已废弃, 合并到15
            $protocolData = service("Public/ProtocolData")->contractTaian($orderid);
        } elseif($tplType == '13') { // 已废弃, 合并到15
            $protocolData = service("Public/ProtocolData")->contractTaian0($orderid);
        } elseif($tplType == '15') {
            $protocolData = service("Public/ProtocolData")->GeneralLoanAgreement($orderid);
        } elseif($tplType == '17') {
            $protocolData = service("Public/ProtocolData")->GeneralBorrowAgreement($orderid);
        } elseif($tplType == '30') {
            $protocolData = array('values' => array());
        } elseif($tplType == $sdt_tplid) {
            $protocolData = service("Public/ProtocolData")->fetchSdtValues($orderid);
        } elseif($tplType == 71) { //金交所项目协议
            $protocolData = service("Public/ProtocolData")->fetchJJSAgreement($orderid);
        } elseif($tplType == 70){
            $protocolData = service("Public/ProtocolData")->fetchJJSDirections($orderid);
        } elseif($tplType == 31) {
            $protocolData = service("Public/ProtocolData")->fetchFundContractValues($orderid);
        } elseif($tplType == 32) {
            $protocolData = service("Public/ProtocolData")->fetchFundQrsValues($orderid);
        } elseif($tplType == 45) {
            $protocolData = service("Public/ProtocolData")->BillAgreement($orderid);  //票据质押借款合同
        } elseif($tplType == 47) {
            $protocolData = service("Public/ProtocolData")->preAgreement($orderid); // 預約服務協議
        } elseif($tplType == 58) {
            $protocolData = service("Public/ProtocolData")->claimsTransferData($orderid);
        } elseif($tplType == 60) {
            $protocolData = service("Public/ProtocolData")->xjhJoinIn($orderid);
        } elseif($tplType == 117) { //去担保的 保理项目
            $protocolData = service("Public/ProtocolData")->GeneralBorrowAgreement($orderid,117);
        }

        $paramData = $protocolData['values'];
        //置换参数，关于营业执照和组织机构代码证
        if(!$paramData['license_no_b'] && $paramData['org_code'] && checkOrgCode($paramData['org_code'])) {
            $paramData['license_type_b'] = '组织机构代码证';
            $paramData['license_no_b'] = $paramData['org_code'];
        }
        //
        return $paramData;
    }

    /*
     * 鑫利宝获取委托支付协议、借款合同（线下）匹配数据
     * @param unknown $tplType
     * @param unknown $prjId
     */
    function getXinLiBaoContractMatchData($tplType,$prjId){
        switch ($tplType) {
            case 100:
                $protocolData = service("Public/ProtocolData")->xlbCommissionPayment($prjId);
                break;
            case 101:
                $protocolData = service("Public/ProtocolData")->xlbLoanContract($prjId);
                break;
            default:
                ;
                break;
        }
        $paramData = $protocolData['values'];
        return $paramData;
    }
	
    /**
     * 获取标的出借人确认书匹配数据
     * @param number $tplId
     * @param number $prjId
     */
    function getConfirmMatchData($tplId, $prjId){
    	switch ($tplId) {
    		case 20://出借人确认书 针对直融项目
    			$protocolData = service("Public/ProtocolData")->getZhrProjectMatchData($prjId);;
    			break;
    		case 21://应收账款受让人确认书 针对保理项目
    			$protocolData = service("Public/ProtocolData")->getBaoLiProjectMatchData($prjId);
    			break;
            case 121://应收账款受让人确认书 去担保公司 针对保理项目
                $protocolData = service("Public/ProtocolData")->getBaoLiProjectMatchData($prjId);
                break;
            case 46:
                $protocolData = service("Public/ProtocolData")->getBillMatchData($prjId);
                break;
            case 48:
                $protocolData = service("Public/ProtocolData")->Zqzrqrs($prjId);  //针对车贷项目
                break;
    		default:
    			;
    			break;
    	}
    	$paramData = $protocolData['values'];
    	return $paramData;
    }

    /**
     * 不支持F
     * @param unknown $tpl
     * @param string $status
     * @return boolean
     */
    function pdf($title, $tpl, $status = 'D')
    {
        if ($status == 'F') return false;
        $tpl = $this->clearTpl($tpl);
        $this->pdfDeal($title, $tpl, $status);
    }


    function pdf2($title, $tpl, $status = 'D')
    {
//        if ($status == 'F') return false;
        $tpl = $this->clearTpl($tpl);
        $this->pdfDeal2($title, $tpl, $status);
    }

    //过滤模版
    function clearTpl($tpl)
    {
        if (!$tpl) return false;
        $tpl = htmlspecialchars_decode($tpl);
        $tpl = str_replace("<(.*?)>", "", $tpl);
        $tpl = preg_replace("/<a.*?>(.*?)<\/a>/is", "$1", $tpl);
        $tpl = preg_replace("/<span.*?>(.*?)<\/span>/is", "$1", $tpl);
//        $tpl = preg_replace("/style=\".*?\"/is",'',$tpl);
        $tpl = preg_replace("/<p.*?>(.*?)<\/p>/is", "<p>$1</p>", $tpl);
        $tpl = str_replace('<strong>', '', $tpl);
        $tpl = str_replace('</strong>', '', $tpl);
        return $tpl;
    }

    /**
     * pdf处理
     * @param unknown $title
     * @param unknown $tpl
     * @param unknown $path
     * @param string $status
     */
    function pdfDeal($title, $tpl, $status = 'D')
    {
        header("Content-Type:text/html; charset=utf-8");
        import_addon("libs.Tcpdf.tcpdf_include");
        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        // set document information
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor($title);
        $pdf->SetTitle($title);
        $pdf->SetSubject($title);
        $pdf->SetKeywords($title);
        $pdf->SetProtection(array('annot-forms', 'fill-forms', 'extract', 'assemble', 'print-high'), '', null, 0, null);

        $headerLogo = "logoImg.png";
        $pdf->SetHeaderData($headerLogo, PDF_HEADER_LOGO_WIDTH, "", "", array(0, 64, 255), array(0, 64, 128));
        $pdf->setFooterData(array(0, 64, 0), array(0, 64, 128));

        // set header and footer fonts
        $pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
        $pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

        $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

        // set margins
        $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
        $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
        $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

        // set auto page breaks
        $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

        // set image scale factor
        $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
        $pdf->setFontSubsetting(true);

        $pdf->SetFont('cid0cs', '', 12);
        $pdf->AddPage();
        // set text shadow effect
        $pdf->setTextShadow(array('enabled' => true, 'depth_w' => 0.2, 'depth_h' => 0.2, 'color' => array(196, 196, 196), 'opacity' => 1, 'blend_mode' => 'Normal'));
        //        $pdf->writeHTMLCell(0, 0, '', '', $tpl, 0, 1, 0, true, '', true);
        $pdf->writeHTML($tpl);
        $path = mb_convert_encoding($title, 'gbk', 'utf-8') . '.pdf';
        $pdf->Output($path, $status);
    }

    /**
     * 获取下载合同的标题，根据不同的操作
     */
    public function getTitleList()
    {
        return $this->_titleList;
    }

    public function getMethodList()
    {
        return $this->_methodList;
    }

    /**
     * 获取下载PDF文件的文件的名称
     */
    public function getDownPdfTitle($action)
    {
        $titlelist = $this->getTitleList();
        $keys = array_keys($titlelist);
        if (!in_array($action, $keys))
        {
            return "合同文件";
        }
        return $titlelist[$action];
    }


    public function getProtocolView($id, $tplName,$type)
    {
        //为了保证安全，所有的方法必须配置才可以调用
        $methodList = $this->getMethodList();
        $method = ucfirst(strtolower($tplName)); //首字母大写的方式
        if (!in_array($method, $methodList))
        {
            throw_exception($method . "方法必须配置");
        }
        $service = service("Public/ProtocolData");

        if (!method_exists($service, $method))
        {
            throw_exception($method . "方法必须实现");
        }
        $values = $service->$method($id);

        try{
            $protocolId = $this->getProtocolID($id,$tplName,$type);
        }catch(Exception $e){
            throw_exception($e->getMessage());
        }
        $result = D('Admin/SystemManage')->gettProtocolDataById($protocolId, $values);
        $result['action'] = $method;
        $result['tplname'] = $tplName;
        $result['type'] = $type;
        return $result;
    }

    /**获取模板的ID
     * @param $tplid
     * @param $type 查看的合同类型,目前两种类型 1、订单 2快速变现
     * @return mixed
     */
    public function getProtocolID($id,$tplid,$type=1)
    {
        $config = service("Financing/Protocol")->getProtocolConf();
        $tplId = $config[$tplid];
        $field = array("id");

        $tableType = array(
            self::PROTOCOL_TYPE_ORDER=>'prj_order',
            self::PROTOCOL_TYPE_FAST_CASH=>'prj_fast_cash',
        );
        $model = M($tableType[$type]);

        //查看模板
        if(!$id){
            $protocolId = M('protocol')->where(array('name_en' => $tplId))->order("id desc")->getField($field);
        }else{

            switch($type){
                case self::PROTOCOL_TYPE_ORDER:
                    //根据订单的信息，查看信息
                    $where = array(
                        'id'=>$id,
                        'protocol_active'=>1//生效,
                    );
                    //如果是订单id 先获取 prj_id
                    $id = M('prj_order')->where($where)->getField('prj_id');
                    $order_protocol_id = M("prj_ext")->where(['prj_id'=>$id])->getField('order_protocol_id');
                    if(!$order_protocol_id) {
                        $order_protocol_id = $model->where($where)->getField("protocol_id");
                    }
                    break;
                case self::PROTOCOL_TYPE_FAST_CASH:
                    //根据订单的信息，查看信息  //从prj_ext表中获取 order_protocol_id 字段
                    $where = array(
                        'prj_id'=>$id,
                        'protocol_active'=>1//生效,
                    );
                    $order_protocol_id = $model->where($where)->getField("protocol_id");
                    break;
            }

            if(!$order_protocol_id){
                throw_exception("协议内容异常!");
            }
            $protocolId = M("protocol")->where(array('id'=>$order_protocol_id))->order("id desc")->getField($field);
        }
        !$protocolId && throw_exception("请先在后台配置好协议内容!");
        return $protocolId;
    }

    public function getLastProtocolId($name_en)
    {
         $mod = D("Protocol");
         $where = array(
             'name_en'=>$name_en,
         	 'is_active' => 1,
         );
        $field = array("id");
        $order = array("id desc");
        $id = $mod->where($where)->order($order)->getField($field);
        return $id;
    }
    //批量下载PDF文件
    function pdfDeal2( $tplArr, $prj_id, $uid, $status = 'F')
    {
        header("Content-Type:text/html; charset=utf-8");
        import_addon("libs.Tcpdf.tcpdf_include");
        import_addon("libs.PclZip");
        // ZIP the PDF file
        $zip_path = SITE_DATA_PATH . "/pack/tr_pdf/".$uid.'_'.$prj_id;
        $pdf_path = SITE_DATA_PATH . "/pack/tr_pdf/".$uid.'_'.$prj_id.'/pdf';
        $prj_dir = SITE_PATH . '/public/prj_pdf';

        if(!is_dir($zip_path)) mkdir($zip_path,0777,true);
        if(!is_dir($pdf_path)) mkdir($pdf_path,0777,true);
        if(!is_dir($prj_dir)) mkdir($prj_dir,0777,true);
        $aimUrl =$pdf_path;


        $prj_name =M('prj')->where(array('id' => $prj_id))->getField('prj_name');
        if (is_dir($prj_dir)){
            if ($dh = opendir($prj_dir)){
                while (($file = readdir($dh)) !== false) {
                    if ($file!="." && $file!="..") {
                        $file_name = basename($file, '.pdf');
                        if($file_name == $prj_name){
                            copy( $prj_dir.'/'.$file, $aimUrl.'/'.mb_convert_encoding("担保函",'gbk','utf-8').'.pdf');
                    };
                }
                }
                closedir($dh);
            }
        }
//        dump($filePath);


//        $source = $filePath;
//        $data = file_get_contents($source);

        foreach($tplArr as $key=>$val){

            if($key == '债权转让合同') {
                $pdf = new myFootPdf(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
            } else {
                $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
            }
            // set document information
            $pdf->SetCreator(PDF_CREATOR);
            $title = $key;
            $tpl = $val;
            $pdf->SetAuthor($title);
            $pdf->SetTitle($title);
            $pdf->SetSubject($title);
            $pdf->SetKeywords($title);
            $pdf->SetProtection(array('annot-forms', 'fill-forms', 'extract', 'assemble', 'print-high'), '', null, 0, null);

            $headerLogo = "logoImg.png";
            $pdf->SetHeaderData($headerLogo, PDF_HEADER_LOGO_WIDTH, "", "", array(0, 64, 255), array(0, 64, 128));
            $pdf->setFooterData(array(0, 64, 0), array(0, 64, 128));

            // set header and footer fonts
            $pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
            $pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

            $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

            // set margins
            $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
            $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
            $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

            // set auto page breaks
            $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

            // set image scale factor
            $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
            $pdf->setFontSubsetting(true);

            $pdf->SetFont('cid0cs', '', 12);
            $pdf->AddPage();
            // set text shadow effect
            $pdf->setTextShadow(array('enabled' => true, 'depth_w' => 0.2, 'depth_h' => 0.2, 'color' => array(196, 196, 196), 'opacity' => 1, 'blend_mode' => 'Normal'));
            //        $pdf->writeHTMLCell(0, 0, '', '', $tpl, 0, 1, 0, true, '', true);
            $pdf->writeHTML($tpl);
            $title = mb_convert_encoding($title,'gbk','utf-8');

            $path = $pdf_path.'/'.$title.'.pdf';

            $pdf->Output($path, $status);

        }
        $zipName ='yrzr.zip';
        $saveFile = $zip_path."/".$zipName;
        $archive = new PclZip($saveFile);
        $list= $archive->create($pdf_path,PCLZIP_OPT_REMOVE_PATH, $pdf_path, PCLZIP_OPT_ADD_TEMP_FILE_ON);
        if(!$list){
            return errorReturn($archive->errorInfo(true));
        }else{
            ob_end_clean();
            header("Content-Type: application/force-download");
            header('Pragma: public');
            header('Last-Modified: '.gmdate('D, d M Y H:i:s') . ' GMT');
            header('Cache-Control: no-store, no-cache, must-revalidate');
            header('Cache-Control: pre-check=0, post-check=0, max-age=0');
            header("Content-Transfer-Encoding: binary");
            header('Content-Type: application/zip');
            header("Content-Disposition: attachment; filename=".$zipName."");
            header('Content-Length: '.filesize($saveFile));
            error_reporting(0);
            readfile($saveFile);
            flush();
            ob_flush();
        }
//        $zip->close();

//        header('Content-type: application/zip');
//        header('Content-Disposition: attachment; filename="jkzr-pdf.zip"');
//        readfile($file);
//dump($file);
    }




}

import_addon("libs.Tcpdf.tcpdf_include");
import_addon("libs.PclZip");
class myFootPdf extends TCPDF{
    function Footer() //设定页脚
    {
        $cur_y = $this->y;
        $this->SetTextColorArray($this->footer_text_color);
        //set style for cell border
        $line_width = (0.85 / $this->k);
        $this->SetLineStyle(array('width' => $line_width, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => $this->footer_line_color));
        //print document barcode
        $barcode = $this->getBarcode();
        if (!empty($barcode)) {
            $this->Ln($line_width);
            $barcode_width = round(($this->w - $this->original_lMargin - $this->original_rMargin) / 3);
            $style = array(
                'position' => $this->rtl?'R':'L',
                'align' => $this->rtl?'R':'L',
                'stretch' => false,
                'fitwidth' => true,
                'cellfitalign' => '',
                'border' => false,
                'padding' => 0,
                'fgcolor' => array(0,0,0),
                'bgcolor' => false,
                'text' => false
            );
            $this->write1DBarcode($barcode, 'C128', '', $cur_y + $line_width, '', (($this->footer_margin / 3) - $line_width), 0.3, $style, '');
        }
        $this->SetY($cur_y);
        $this->SetFont('cid0cs', '', 12);
        if ($this->getRTL()) {
            $this->SetX($this->original_rMargin);
            $this->Cell(0, 0,  "甲方签字：    ", 'T', 0, 'L');
        } else {
            $this->SetX($this->original_lMargin);
            $this->Cell(0, 0, $this->getAliasRightShift(). "甲方签字：    ", 'T', 0, 'R');
        }
    }
}

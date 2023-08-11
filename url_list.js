layui.define(function(exports){ 
  var obj = {  
    "1":"/general/erp4/view/provebill/index?/#/flowType=订舱通知单/status=单证审核",
    "2":"/general/erp4/view/provebill/index?/#/flowType=订舱通知单/status=装柜,发货/listType=6",
    "3":"/general/erp4/view/provebill/saleAudit/index.php?/#/flowType=装柜流程/status=销售审核",
    "4":"/general/erp4/view/provebill/saleAudit/index.php?/#/flowType=装柜流程/status=装柜确认/depart=plan",
    "5":"/general/erp4/view/provebill/saleAudit/index.php?/#/flowType=装柜流程/status=装柜确认/depart=storage",
    "6":"/general/erp4/view/provebill/planAudit/index.php?/#/flowType=发货流程/status=单证审核/prcsFlag=未接收,办理中/load_type=整柜",
    "7":"/general/erp4/view/provebill/planAudit/index.php?/#/flowType=发货流程/status=财务审核",
    "8":"/general/erp4/view/provebill/planAudit/index.php?/#/flowType=发货流程/status=发货确认",
    "9":"/general/erp4/view/provebill/saleAudit/index.php?/#/flowType=装柜流程",
    "10":"/general/erp4/view/provebill/index?/#/flowType=订舱通知单/status=总监审核",
    "11":"/general/erp4/view/provebill/saleAudit/index.php?/#/flowType=装柜流程/depart=plan", 
    "12":"/general/erp4/view/provebill/change/bill/index.php?/#/flowType=订舱变更/step=1/show1=1",
    "13":"/general/erp4/view/provebill/change/cabinet/index.php?/#/flowType=装柜变更/step=1/show1=1",
    "14":"/general/erp4/view/provebill/change/cabinet/index.php?/#/flowType=装柜变更/step=2/show1=1",
    "15":"/general/erp4/view/provebill/change/bill?/#/",
    "16":"/general/erp4/view/provebill/change/cabinet?/#/",
    "17":"/general/erp4/view/provebill/index?/#/",
    "18":"/general/erp4/view/orderChange?/#/flowType=销售变更",
    "19":"/general/erp4/view/orderChange?/#/flowType=销售变更/step=变更审核/prcsFlag=未接收,办理中",
    "20":"/general/erp4/view/orderChange?/#/flowType=销售变更/step=计划确认/confirm=3",
    "21":"/general/erp4/view/orderChange?/#/flowType=生产变更",
    "22":"/general/erp4/view/orderChange?/#/flowType=设计稿变更",
    "23":"/general/erp4/view/orderChange/printImg?/#/",
    "24":"/general/erp4/view/provebill/index?/#/flowType=订舱通知单/status=托盘录入",
    "25":"/general/erp4/view/provebill/accountMoney/index.php?/#/",
    "26":"/general/erp4/view/provebill/accountCtr/index.php?/#/",
    "27":"/general/erp4/view/provebill/spells/index.php?/#/",
    "28":"/general/erp4/view/provebill/spells/index.php?/#/flowType=拼货流程/status=经理审核",
    "29":"/general/erp4/view/provebill/spells/index.php?/#/flowType=拼货流程/status=单证审核",
    "30":"/general/erp4/view/provebill/spells/index.php?/#/flowType=拼货流程/status=仓库确认",
    "31":"/general/erp4/view/provebill/tuopan/index.php?/#/",
    "32":"/general/erp4/view/orderChange?/#/flowType=生产变更/step=变更审核/prcsFlag=未接收",
    
    "33":"/general/erp4/view/orderChange/printImg?/#/flowType=设计稿变更",
    /*"34":"/general/erp4/view/orderChange/printImg?/#/flowType=设计稿变更/status=采购审核",*/
    "34":"/general/erp4/view/orderChange?/#/flowType=设计稿变更/step=采购审核",


    /*"35":"/general/erp4/view/orderChange/printImg?/#/flowType=设计稿变更/status=销售确认/prcsFlag=未接收,办理中",*/
    "35":"/general/erp4/view/orderChange?/#/flowType=设计稿变更/step=销售确认/prcsFlag=未接收,办理中",
    /*"36":"/general/erp4/view/orderChange/printImg?/#/flowType=设计稿变更/status=销售确认/prcsFlag=办理完毕",*/

    "36":"/general/erp4/view/orderChange?/#/flowType=设计稿变更/step=销售确认/prcsFlag=办理完毕",
    "37":"/general/erp4/view/orderChange/printImg?/#/flowType=设计稿变更/status=销售确认/prcsFlag=办理完毕",
    "38":"/general/erp4/view/orderChange/printImg?/#/flowType=设计稿变更/status=销售确认/prcsFlag=办理完毕",

    "39":"/general/erp4/view/orderChange?/#/",
    
    "40":"/general/erp4/view/orderChange?/#/flowType=销售变更/prcsFlag=办理完毕/confirm=3",
    "41":"/general/erp4/view/provebill/userGroup?/#/group_type=2",
    "42":"/general/erp4/view/orderChange?/#/flowType=销售变更/step=计划员审核/prcsFlag=未接收,办理中",
    "43":"/general/erp4/view/provebill/tuopanBatch?/#/",
    "44":"/general/erp4/view/provebill/orderBox?/#/",
    "45":"/general/erp4/view/orderChange?/#/flowType=生产变更/prcsFlag=办理完毕",
    "46":"/general/erp4/view/provebill/customerMjt/index.php?/#/",
    "47":"/general/erp4/view/provebill/index?/#/flowType=订舱通知单/status=发货/listType=1",
    "48":"/general/erp4/view/provebill/userAgency/index.php?/#/status=未接收",
    "49":"/general/erp4/view/provebill/change/bill/index.php?/#/flowType=订舱变更/step=1/show4=1",
    "50":"/general/erp4/view/provebill/planAudit?/#/",

    "51":"/general/erp4/view/provebill/change/deliver/index.php?/#/flowType=发货变更/status=销售审核/prcsFlag=未接收",
    "52":"/general/erp4/view/provebill/change/deliver/index.php?/#/flowType=发货变更/status=单证审核/prcsFlag=未接收",
    "53":"/general/erp4/view/provebill/change/deliver/index.php?/#/flowType=发货变更/status=单证审核",

    "54":"/general/erp4/view/orderChange?/#/flowType=销售变更/step=确认交期/prcsFlag=未接收,办理中",


    "55":"/general/erp4/view/provebill/change/billConfirm/index.php?/#/status=未办理",


    "56":"/general/erp4/view/provebill/invoicePrint/index.php?/#/",
    "57":"/general/erp4/view/provebill/invoicePrint/index.php?/#/status=财务审核/prcsFlag=未接收",
    "58":"/general/erp4/view/provebill/invoicePrint/index.php?/#/status=财务审核/prcsFlag=办理完毕",

    "59":"/general/erp4/view/orderChange?/#/flowType=生产变更/step=计划审核",
    "60":"/general/erp4/view/flowPriv/index.php?/#/flow_type=4",
    "61":"/general/erp4/view/orderChange?/#/flowType=销售变更/step=确认贸易术语/prcsFlag=未接收,办理中",

    "provebill_warehouse":"/general/erp4/view/provebill/warehouse?/#/",
    "deliver_list":"/general/erp4/view/provebill/planAudit/index.php?/#/flowType=发货流程/status=单证审核",
    "ggs_audit":"/general/erp4/view/orderChange?/#/flowType=规格书变更/step=技术部审核/prcsFlag=未接收,办理中",
    "ggs_confirm":"/general/erp4/view/orderChange?/#/flowType=规格书变更/step=销售确认/prcsFlag=未接收,办理中",
    "ggs_list":"/general/erp4/view/orderChange?/#/flowType=规格书变更/step=销售确认/prcsFlag=办理完毕",

    



    "99":"/general/erp4/view/provebill/saleAudit/index.php?/#/flowType=装柜流程/depart=Doc",
    "100":"/general/erp4/view/provebill/provebillCabinet/index.php?/#/flowType=订舱通知单/status=装柜,发货/listType=5",
    "101":"/general/erp4/view/provebill/collection/index.php?/#/",
    "102":"/general/erp4/view/provebill/collectionVerify1/index.php?/#/",
    "103":"/general/erp4/view/provebill/collectionVerify1/index.php?/#/flowType=核销流程/status=财务审核",
    "104":"/general/erp4/view/flowPriv/index.php?/#/flow_type=5",
    "105":"/general/erp4/view/uniondown?/#/",
    "106":"/general/erp4/view/provebill/collectionVerify/index.php?/#/",
    "107":"/general/erp4/view/provebill/collectionVerify/index.php?/#/flowType=核销流程/status=财务审核",
    "108":"/general/erp4/view/customerManage/index.php",
    "109":"/general/erp4/view/provebill/middle/index.php?/#/",
    "110":"/general/erp4/view/provebill/delivernoSame/index.php?/#/type=YW",
    "112":"/general/erp4/view/provebill/delivernoSame/index.php?/#/type=NX",
    "113":"/general/erp4/view/unionfile?/#",
    "114":"/general/erp4/view/tiniData?/#",
    "115":"/general/erp4/view/tiniDate?/#",
    "116":"/general/erp4/view/tiniNoSame?/#/type=2",
    "117":"/general/erp4/view/tiniNoSame?/#/type=0",
    "118":"/general/erp4/view/order_orderlist?/#",
    "119":"/general/erp4/view/order_orderlist?/#",

    "111":"/general/erp4/view/provebill/collectionVerify1/water_bill.php?/#/",

    "150":"/general/erp4/view/provebill/summary/index.php?/#/flowType=订舱通知单/status=装柜,发货/listType=6",
    "151":"/general/erp4/view/provebill/index?/#/flowType=订舱通知单/status=单证审核",
    "152":"/general/erp4/view/provebill/index?/#/flowType=订舱通知单/status=单证审核",
    "153":"/general/erp4/view/provebill/index?/#/flowType=订舱通知单/status=装柜,发货/listType=6",
    "154":"/general/erp4/view/provebill/index?/#/flowType=订舱通知单/status=装柜,发货/listType=6",
    "155":"/general/erp4/view/provebill/change/billConfirm/index2.php?/#/status=未办理/listType=1",
  };

  exports('url_list', obj);
});   
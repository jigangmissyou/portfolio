layui.define(function(exports){ 
  var obj = {  
    "1":"/general/erp4/view/provebill/index?/#/flowType=����֪ͨ��/status=��֤���",
    "2":"/general/erp4/view/provebill/index?/#/flowType=����֪ͨ��/status=װ��,����/listType=6",
    "3":"/general/erp4/view/provebill/saleAudit/index.php?/#/flowType=װ������/status=�������",
    "4":"/general/erp4/view/provebill/saleAudit/index.php?/#/flowType=װ������/status=װ��ȷ��/depart=plan",
    "5":"/general/erp4/view/provebill/saleAudit/index.php?/#/flowType=װ������/status=װ��ȷ��/depart=storage",
    "6":"/general/erp4/view/provebill/planAudit/index.php?/#/flowType=��������/status=��֤���/prcsFlag=δ����,������/load_type=����",
    "7":"/general/erp4/view/provebill/planAudit/index.php?/#/flowType=��������/status=�������",
    "8":"/general/erp4/view/provebill/planAudit/index.php?/#/flowType=��������/status=����ȷ��",
    "9":"/general/erp4/view/provebill/saleAudit/index.php?/#/flowType=װ������",
    "10":"/general/erp4/view/provebill/index?/#/flowType=����֪ͨ��/status=�ܼ����",
    "11":"/general/erp4/view/provebill/saleAudit/index.php?/#/flowType=װ������/depart=plan", 
    "12":"/general/erp4/view/provebill/change/bill/index.php?/#/flowType=���ձ��/step=1/show1=1",
    "13":"/general/erp4/view/provebill/change/cabinet/index.php?/#/flowType=װ����/step=1/show1=1",
    "14":"/general/erp4/view/provebill/change/cabinet/index.php?/#/flowType=װ����/step=2/show1=1",
    "15":"/general/erp4/view/provebill/change/bill?/#/",
    "16":"/general/erp4/view/provebill/change/cabinet?/#/",
    "17":"/general/erp4/view/provebill/index?/#/",
    "18":"/general/erp4/view/orderChange?/#/flowType=���۱��",
    "19":"/general/erp4/view/orderChange?/#/flowType=���۱��/step=������/prcsFlag=δ����,������",
    "20":"/general/erp4/view/orderChange?/#/flowType=���۱��/step=�ƻ�ȷ��/confirm=3",
    "21":"/general/erp4/view/orderChange?/#/flowType=�������",
    "22":"/general/erp4/view/orderChange?/#/flowType=��Ƹ���",
    "23":"/general/erp4/view/orderChange/printImg?/#/",
    "24":"/general/erp4/view/provebill/index?/#/flowType=����֪ͨ��/status=����¼��",
    "25":"/general/erp4/view/provebill/accountMoney/index.php?/#/",
    "26":"/general/erp4/view/provebill/accountCtr/index.php?/#/",
    "27":"/general/erp4/view/provebill/spells/index.php?/#/",
    "28":"/general/erp4/view/provebill/spells/index.php?/#/flowType=ƴ������/status=�������",
    "29":"/general/erp4/view/provebill/spells/index.php?/#/flowType=ƴ������/status=��֤���",
    "30":"/general/erp4/view/provebill/spells/index.php?/#/flowType=ƴ������/status=�ֿ�ȷ��",
    "31":"/general/erp4/view/provebill/tuopan/index.php?/#/",
    "32":"/general/erp4/view/orderChange?/#/flowType=�������/step=������/prcsFlag=δ����",
    
    "33":"/general/erp4/view/orderChange/printImg?/#/flowType=��Ƹ���",
    /*"34":"/general/erp4/view/orderChange/printImg?/#/flowType=��Ƹ���/status=�ɹ����",*/
    "34":"/general/erp4/view/orderChange?/#/flowType=��Ƹ���/step=�ɹ����",


    /*"35":"/general/erp4/view/orderChange/printImg?/#/flowType=��Ƹ���/status=����ȷ��/prcsFlag=δ����,������",*/
    "35":"/general/erp4/view/orderChange?/#/flowType=��Ƹ���/step=����ȷ��/prcsFlag=δ����,������",
    /*"36":"/general/erp4/view/orderChange/printImg?/#/flowType=��Ƹ���/status=����ȷ��/prcsFlag=�������",*/

    "36":"/general/erp4/view/orderChange?/#/flowType=��Ƹ���/step=����ȷ��/prcsFlag=�������",
    "37":"/general/erp4/view/orderChange/printImg?/#/flowType=��Ƹ���/status=����ȷ��/prcsFlag=�������",
    "38":"/general/erp4/view/orderChange/printImg?/#/flowType=��Ƹ���/status=����ȷ��/prcsFlag=�������",

    "39":"/general/erp4/view/orderChange?/#/",
    
    "40":"/general/erp4/view/orderChange?/#/flowType=���۱��/prcsFlag=�������/confirm=3",
    "41":"/general/erp4/view/provebill/userGroup?/#/group_type=2",
    "42":"/general/erp4/view/orderChange?/#/flowType=���۱��/step=�ƻ�Ա���/prcsFlag=δ����,������",
    "43":"/general/erp4/view/provebill/tuopanBatch?/#/",
    "44":"/general/erp4/view/provebill/orderBox?/#/",
    "45":"/general/erp4/view/orderChange?/#/flowType=�������/prcsFlag=�������",
    "46":"/general/erp4/view/provebill/customerMjt/index.php?/#/",
    "47":"/general/erp4/view/provebill/index?/#/flowType=����֪ͨ��/status=����/listType=1",
    "48":"/general/erp4/view/provebill/userAgency/index.php?/#/status=δ����",
    "49":"/general/erp4/view/provebill/change/bill/index.php?/#/flowType=���ձ��/step=1/show4=1",
    "50":"/general/erp4/view/provebill/planAudit?/#/",

    "51":"/general/erp4/view/provebill/change/deliver/index.php?/#/flowType=�������/status=�������/prcsFlag=δ����",
    "52":"/general/erp4/view/provebill/change/deliver/index.php?/#/flowType=�������/status=��֤���/prcsFlag=δ����",
    "53":"/general/erp4/view/provebill/change/deliver/index.php?/#/flowType=�������/status=��֤���",

    "54":"/general/erp4/view/orderChange?/#/flowType=���۱��/step=ȷ�Ͻ���/prcsFlag=δ����,������",


    "55":"/general/erp4/view/provebill/change/billConfirm/index.php?/#/status=δ����",


    "56":"/general/erp4/view/provebill/invoicePrint/index.php?/#/",
    "57":"/general/erp4/view/provebill/invoicePrint/index.php?/#/status=�������/prcsFlag=δ����",
    "58":"/general/erp4/view/provebill/invoicePrint/index.php?/#/status=�������/prcsFlag=�������",

    "59":"/general/erp4/view/orderChange?/#/flowType=�������/step=�ƻ����",
    "60":"/general/erp4/view/flowPriv/index.php?/#/flow_type=4",
    "61":"/general/erp4/view/orderChange?/#/flowType=���۱��/step=ȷ��ó������/prcsFlag=δ����,������",

    "provebill_warehouse":"/general/erp4/view/provebill/warehouse?/#/",
    "deliver_list":"/general/erp4/view/provebill/planAudit/index.php?/#/flowType=��������/status=��֤���",
    "ggs_audit":"/general/erp4/view/orderChange?/#/flowType=�������/step=���������/prcsFlag=δ����,������",
    "ggs_confirm":"/general/erp4/view/orderChange?/#/flowType=�������/step=����ȷ��/prcsFlag=δ����,������",
    "ggs_list":"/general/erp4/view/orderChange?/#/flowType=�������/step=����ȷ��/prcsFlag=�������",

    



    "99":"/general/erp4/view/provebill/saleAudit/index.php?/#/flowType=װ������/depart=Doc",
    "100":"/general/erp4/view/provebill/provebillCabinet/index.php?/#/flowType=����֪ͨ��/status=װ��,����/listType=5",
    "101":"/general/erp4/view/provebill/collection/index.php?/#/",
    "102":"/general/erp4/view/provebill/collectionVerify1/index.php?/#/",
    "103":"/general/erp4/view/provebill/collectionVerify1/index.php?/#/flowType=��������/status=�������",
    "104":"/general/erp4/view/flowPriv/index.php?/#/flow_type=5",
    "105":"/general/erp4/view/uniondown?/#/",
    "106":"/general/erp4/view/provebill/collectionVerify/index.php?/#/",
    "107":"/general/erp4/view/provebill/collectionVerify/index.php?/#/flowType=��������/status=�������",
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

    "150":"/general/erp4/view/provebill/summary/index.php?/#/flowType=����֪ͨ��/status=װ��,����/listType=6",
    "151":"/general/erp4/view/provebill/index?/#/flowType=����֪ͨ��/status=��֤���",
    "152":"/general/erp4/view/provebill/index?/#/flowType=����֪ͨ��/status=��֤���",
    "153":"/general/erp4/view/provebill/index?/#/flowType=����֪ͨ��/status=װ��,����/listType=6",
    "154":"/general/erp4/view/provebill/index?/#/flowType=����֪ͨ��/status=װ��,����/listType=6",
    "155":"/general/erp4/view/provebill/change/billConfirm/index2.php?/#/status=δ����/listType=1",
  };

  exports('url_list', obj);
});   
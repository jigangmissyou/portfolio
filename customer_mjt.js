layui.define(['utils','laydate','customer_cols'],function(exports){ 
  var obj = {
    
    data:{
       dataInfo:{}
    },
      
      
    initCustomerMjt:function(store){
        if(!layui.utils.isEmpty(store["params"]["order_list_id"])){
            var orderProduct=layui.utils.sqlManage("order_product_list",{"id":store["params"]["order_list_id"],"limit":"1"});
            
            if(!layui.utils.isEmpty(orderProduct)){
                
                var u8Customer=layui.utils.post("get_u8_customer",{"customer_code":orderProduct["cCusCode"]});
                var customer_short=!layui.utils.isEmpty(u8Customer["data"]["cCusAbbName"])?u8Customer["data"]["cCusAbbName"]:"";
                layui.form.val("form-account-set",{
                    "u8_code":orderProduct["u8Code"],
                    "cInvCode":orderProduct["cInvCode"],
                    "customer_code":orderProduct["cCusCode"],
                    "customer_name":orderProduct["cCusName"],
                    "customer_short":customer_short,
                    "order_list_id":store["params"]["order_list_id"]
                });
            }
            var formData=layui.utils.sqlManage("customer_mjt_list",{"u8_code":orderProduct["u8Code"],"customer_code":orderProduct["cCusCode"],"limit":"1"});
            if(!layui.utils.isEmpty(formData)){
                layui.form.val("form-account-set",formData);
            }
        }
        
        if(!layui.utils.isEmpty(store["params"]["id"])){
            var formData=layui.utils.sqlManage("customer_mjt_list",{"id":store["params"]["id"],"limit":"1"});
            if(!layui.utils.isEmpty(formData)){
                layui.form.val("form-account-set",formData);
            }
        }

    },
    
    addUrl:function(store){
        location.replace("/general/erp4/view/provebill/customerMjt/add.php?/#/order_list_id=" + store["data"]["id"]+"/showType="+store['params']['showType']);
    }

  };
 

  exports('customer_mjt', obj);
});    
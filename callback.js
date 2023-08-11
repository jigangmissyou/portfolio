layui.define(['utils','laydate'],function(exports){ 
  var obj = {
    
    data:{
       dataInfo:{}
    },
  
    tuopan_select_order:function(store){
        
        location.replace("/general/erp4/view/provebill/tuopanBatch/add.php?/#/store="+encodeURI(layui.utils.jsonEncode(store)));      
    },
    
    
  
  };
 
  exports('callback', obj);
});    
layui.define(['utils','laydate','xmSelect'],function(exports){ 
  var obj = {
    
    data:{
       dataInfo:{}
    },
    
    init_warehouse:function(store){
        //��ʼ����Ʒ����
        var categorys = layui.xmSelect.render({el: '#product_type',filterable: true, size: 'mini',name: 'product_type',tips: '��ѡ���Ʒ���', data: []});
        var res=layui.utils.ajaxPost('/general/erp4/controller/product/getCategorys_k.php');
        categorys.update({data: res});

         //��ʼ����ϵ
         var glueStyle = layui.xmSelect.render({el: '#glue_style',filterable: true, size: 'mini',name: 'glue_style',tips: '��ѡ��ϵ', data: []});
         var glueRes=layui.utils.ajaxPost('/general/erp4/controller/product/getGlueStyle_k.php');
         glueStyle.update({data: glueRes});
        
        if(!layui.utils.isEmpty(store['params']["id"])){
            var dataInfo=layui.utils.sqlManage("warehouse_list",{"id":store['params']["id"],limit:1});
            store["dataModel"]["warehouse"]=dataInfo;  
            var selectArray=dataInfo["product_type"].split(',');
            var categoryArray=[];
            if(!layui.utils.isEmpty(selectArray)){
                for(var index in selectArray){
                    categoryArray.push({name: selectArray[index], value:selectArray[index]});
                }
            }
            categorys.setValue(categoryArray);
            if(!layui.utils.isEmpty(dataInfo["glue_style"])){
                var selectGlueArray=dataInfo["glue_style"].split(',');
                var glueArray=[];
                if(!layui.utils.isEmpty(selectGlueArray)){
                    for(var index in selectGlueArray){
                        glueArray.push({name: selectGlueArray[index], value:selectGlueArray[index]});
                    }
                }
                glueStyle.setValue(glueArray);
            }
        }
        var selectList=layui.utils.post("get_select_item",{"key_list":{"account_type":"�������"}});
        store["data"]["select_list"]=selectList["data"];     
        
        setTimeout(function(){
            //��ʼ������������
            var selectArray=["account_type"];
            for(var index in selectArray){
                layui.utils.initTpl("select_tmp","."+selectArray[index],{
                    name:selectArray[index],
                    data:selectList["data"][selectArray[index]]
                });//��ʼ��������  
            }
            layui.form.render();//��Ⱦ�������ؼ�
            layui.form.val("form-warehouse", store["dataModel"]["warehouse"]);//����ֵ
        },200);
    },    
    warehouse_select:function(store){
        layui.form.val("form-warehouse", {
            "warehouse_code":store["data"]["select_warehouse_item"]["cWhCode"],
            "warehouse_person":store["data"]["select_warehouse_item"]["cWhPerson"],
            "warehouse_name":store["data"]["select_warehouse_item"]["cWhName"]
        });//����ֵ
          
    }
  
  };
 

  exports('warehouse', obj);
});    
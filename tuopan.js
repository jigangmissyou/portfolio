layui.define(['utils','laydate','order_cols'],function(exports){ 
  var obj = {
    
    data:{
       dataInfo:{}
    },
    
    
    initData:function(store){

        if(store['params']['id']==0){
            var params_store=JSON.parse(decodeURI(store['params']["store"]));
            store['params']["order_ids"]=params_store["data"]["ids"];
            store['params']["showType"]=params_store["params"]["showType"];
            store["dataModel"]["formData"]["order_ids"]=store['params']["order_ids"].join(",");
        }else{
            //store['params']["order_ids"]        
            store["dataModel"]["formData"]=layui.utils.post('sql_manage_api',{"unique_code":"tuopan_batch_list",id:store['params']['id'],limit:1});
            store["dataModel"]["formData"]["order_ids"]=store["dataModel"]["formData"]["order_ids"];
        }
        
        if(layui.utils.isEmpty(store["dataModel"]["formData"]["batch_num"])){            
            store["dataModel"]["formData"]["batch_num"]=layui.utils.post('get_batch_num',{});       
        }
     
        layui.form.render();
        layui.form.val("form-account-set", store["dataModel"]["formData"]);
    },
  
    initTab1:function (store){
        
        return layui.table.render({elem: '#tab1', url: layui.utils.urlData['provebill'],cols:layui.order_cols.order_list
            , where:{opt:"get_product_list",orderIds:store["dataModel"]["formData"]["order_ids"],"tuopan_list":true,limit:999}
            ,toolbar: '#tab1Bar1'
            ,totalRow: true 
            ,parseData: function(res){   
                for(var index in res.data){ 
                    
                    var itemData=res.data[index];
                    if(typeof(store["data"]['tab1Ids'])!="undefined"){
                        if(store["data"]['tab1Ids'].indexOf(res.data[index].id)>-1){
                            res.data[index]['LAY_CHECKED']=true;
                        }
                    }

                    res.data[index]["tuopanDisplay"]=!layui.utils.isEmpty(itemData["tuopanDisplay"])?itemData["tuopanDisplay"]:itemData["tuopan_display"];
                    res.data[index]["tuopanNum"]=!layui.utils.isEmpty(itemData["tuopanNum"])?itemData["tuopanNum"]:itemData["tuopan_num"];
                    res.data[index]["tuopanSize"]=!layui.utils.isEmpty(itemData["tuopanSize"])?itemData["tuopanSize"]:itemData["tuopan_size"];
                    res.data[index]["tuopanWeight"]=!layui.utils.isEmpty(itemData["tuopanWeight"])?itemData["tuopanWeight"]:itemData["tuopan_weight"];
                    
                    var storeProduct=store["data"]["orderLists"]["key_"+res.data[index]['id']];
                    if(storeProduct){
                        for(var i in store["data"]['synFields']){
                            var item=store["data"]['synFields'][i];
                            res.data[index][item] =storeProduct[item]?storeProduct[item]:res.data[index][item];//ͬ������
                        }
                    }
                }
                return {"code": res.code,"msg": res.msg, "count": res.count, "data": res.data };
            }
            ,done: function(res, curr, count){
                if(res.data){
                    var tableData=layui.table.cache["tab1"]
                    var groupNums={};
                    for(var i in tableData){
                        if(tableData[i]["tuopanGroup"]){
                            if(!groupNums[tableData[i]["tuopanGroup"]]){
                                groupNums[tableData[i]["tuopanGroup"]]={"id":tableData[i]["id"],"num":0};                               
                            } 
                            groupNums[tableData[i]["tuopanGroup"]]["num"]=groupNums[tableData[i]["tuopanGroup"]]["num"]+1;
                        }
                    }
                    
                    var keyCross={"id":"orderlist_ids","orderNum":"order_nums","tuoPan":"pallets"}
                    
                    for(var index in res.data){ 
                        var item=res.data[index]; 
                        store["data"]["orderLists"]["key_"+item['id']]={};
                        for(var i in store["data"]['synFields']){
                            var item_i=store["data"]['synFields'][i];
                            store["data"]["orderLists"]["key_"+item['id']][item_i]=item[item_i];
                        }
                        
                        
                        for(var i in keyCross){
                            var item_key=store["dataModel"]["formData"][keyCross[i]];
                            if(!layui.utils.inArray(item[i],item_key)&&Array.isArray(item_key)){
                                store["dataModel"]["formData"][keyCross[i]].push(item[i]);
                            }                        
                        }
                        
                     
                        
                        var mergeCols=["tuopanNum","tuopanWeight"];
                        if(item["tuopanGroup"]){
                          
                            for(var i in mergeCols){
                                if(groupNums[item["tuopanGroup"]]["id"]==item["id"]){
                                    layui.$("#tab1 + div tr[data-index='" + index + "'] td[data-field="+mergeCols[i]+"] ").attr({"rowspan": groupNums[item["tuopanGroup"]]["num"]});
                                }else{
                                    layui.$("#tab1 + div tr[data-index='" + index + "'] td[data-field="+mergeCols[i]+"] ").attr({"style": "display:none"});
                                }  
                            }
                        }
                        
                    }
                
                    for(var i in keyCross){
                        var item=store["dataModel"]["formData"][keyCross[i]];
                        if(!layui.utils.isEmpty(item)&&Array.isArray(item)){
                            store["dataModel"]["formData"][keyCross[i]]=item.join(",");
                        }                        
                    }
                   
                } 
                
                if(store['params']['id']==0){
                    layui.form.val("form-account-set", store["dataModel"]["formData"]);
                }
                
            }
        });
    }
    
  };
 
  exports('tuopan', obj);
});    
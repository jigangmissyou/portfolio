layui.define(['utils','laydate','order_cols','deliver_cols'],function(exports){ 
    var obj = {
        data:{
           dataInfo:{}
        },

        initData:function(store){
            var id=typeof(store["params"].id)!="undefined"?store["params"].id:0;  //获取页面参数
            var dataInfo=layui.utils.post('init_bill_deliver',{id:id,billIds:store["params"].billIds,flow_id:store["params"].flow_id});

            if(store["params"]["billIds"]){
                var initBillInfo =layui.utils.post('get_bill_info',{id:store["params"]["billIds"]});//加载订舱信息
            }else{
                var initBillInfo =layui.utils.post('get_bill_info',{id:dataInfo["billDeliver"]["bill_ids"]});//加载订舱信息
            }
            var initPriFinalUser = layui.utils.get('pri_final_user');
            if(initPriFinalUser["status"] == 200){
                store["data"]["author"]=1;
            }else if(layui.utils.isEmpty(dataInfo["billDeliver"]["creator_id"])){
                store["data"]["author"]=1;
            }else if(!layui.utils.isEmpty(dataInfo["creator_id"]) && dataInfo["creator_id"] == store["data"]["user"]["user_id"]){
                store["data"]["author"]=1;
            } 
            store["dataModel"]["billDeliver"]=dataInfo["billDeliver"];
            if(store["params"]["billIds"]){
                var initBillInfo =layui.utils.post('get_bill_info',{id:store["params"]["billIds"]});//加载订舱信息
            }else{
                var initBillInfo =layui.utils.post('get_bill_info',{id:dataInfo["billDeliver"]["bill_ids"]});//加载订舱信息
            }

            if(typeof(dataInfo["billDeliver"]["id"])=="undefined"){
                store["dataModel"]["billDeliver"]["order_num"]=dataInfo["order_num"];
                store["dataModel"]["billDeliver"]["customer_short"]=dataInfo["customer_short"];
                store["dataModel"]["billDeliver"]["coin_type"]=dataInfo["coin_type"];
                store["dataModel"]["billDeliver"]["deliver_store"]=dataInfo["deliver_store"];
                store["dataModel"]["billDeliver"]["invoice_nums"]=dataInfo["invoice_nums"];
                store["dataModel"]["billDeliver"]["ship_time"]=dataInfo["ship_time"];
                store["dataModel"]["billDeliver"]["trade_type"]=dataInfo["trade_type"];
                store["dataModel"]["billDeliver"]["mark"]=dataInfo["mark"];

            }
            store["dataModel"]["billDeliver"]["end_port"] = initBillInfo["end_port"];
            store["dataModel"]["billDeliver"]["final_customer_short"]= layui.utils.isEmpty(dataInfo["billDeliver"]["final_customer_short"]) ? initBillInfo["customer_short"] : dataInfo["billDeliver"]["final_customer_short"];
            store["dataModel"]["billDeliver"]["final_customer_code"]= layui.utils.isEmpty(dataInfo["billDeliver"]["final_customer_code"]) ? initBillInfo["customer_code"] : dataInfo["billDeliver"]["final_customer_code"];

            if(!layui.utils.isEmpty(dataInfo["billDeliver"]["cabinet_list"])){
                store["data"]["batch_cabinet"]["cabinet_list"]=JSON.parse(dataInfo["billDeliver"]["cabinet_list"]);//分柜数据
            }
                        
            var changeInfo=layui.utils.post("get_deliver_change",{"sales_num":store["dataModel"]["billDeliver"]["sales_num"]});
            store['data']['batch_cabinet']["change_info"]=!layui.utils.isEmpty(changeInfo["data"])? changeInfo["data"]:[];
     
            store["data"]["orderListIds"]=dataInfo["order_list_ids"];
            store["data"]["currentFlow"]=dataInfo["currentFlow"];
            layui.utils.initTpl("form_tmp","#form_box",store);
            setTimeout(function(){
                var selectArray={"key_list":{"pro_loan_company":"货代公司","transport":"运输方式"}};
                var selectItem = layui.utils.post("get_select_item",selectArray);
                for(var index in selectArray["key_list"]){
                    dataInfo['select'][index]=selectItem["data"][index];
                }
               
                var selectArray=["coin_type","deliver_store","settle_type","pro_loan_company","transport"];
                for(var index in selectArray){
                    
                    var required=true;
                    if(layui.utils.inArray(selectArray[index],["pro_loan_company"])){
                        if(!layui.utils.inArray(store["params"]["showType"],['6'])){
                            required=false;
                        }
                    }
                    
                    layui.utils.initTpl("select_tmp","."+selectArray[index],{
                        name:selectArray[index],
                        data:dataInfo['select'][selectArray[index]],
                        required:required
                    });
                }
                
                var dataArray=["expiry_date","sales_bill_date","deliver_date","real_cabinet_time"];
                for(var index in dataArray) layui.laydate.render({elem: '.'+dataArray[index]});
                layui.form.render();
                layui.form.val("form-plan-audit",{
                    bill_ids:store["params"].billIds,
                    bill_flow_ids:store["params"].billFlowIds,
                    bill_flow_steps:store["params"].billFlowSteps
                });
                if( layui.utils.inArray(store["params"]["showType"],['6'])||store["params"]["opt"]=="detail" ){
                    layui.deliver_cabinet.initData(store,"['data']['batch_cabinet']","CabinetList")//渲染分柜组件
                }
             
                if(typeof(store["dataModel"]["billDeliver"]["receipt_attachment"])=="undefined"){
                    store["dataModel"]["billDeliver"]["receipt_attachment"]="";
                } 
                layui.utils.initUpload("receipt_attachment",store["dataModel"]["billDeliver"]["receipt_attachment"],"",true); 
                
                
                layui.form.val("form-plan-audit",{"gui_style":initBillInfo["cabinet_num_str"]});//表单赋值  
                layui.form.val("form-plan-audit", store["dataModel"]["billDeliver"]);//表单赋值
            },300);
        },
        
        initEvent:function(store){
            var that=this;
            var is_total_change=false;
            layui.table.on('edit(tab2)', function(obj){
              
                var huanSuanLv=!layui.utils.isEmpty(obj.data["huanSuanLv"])? parseFloat(obj.data["huanSuanLv"]):1;
                var onum =!layui.utils.isEmpty( obj.data['onum'])? parseFloat(obj.data['onum']):1;
                if(layui.utils.inArray(obj.field,["deliver_box"])){
                    var is_check=layui.utils.post('check_if_change',{id:store["dataModel"]["billDeliver"]["id"]});
                    if (is_check.status !== 0){
                        layer.msg(is_check['msg']);
                        setTimeout(function(){
                            layui.$("table").find("tr").click();
                        },100);
                        return false;
                    }
                    var deliver_num=0;
                    if(obj.data.hz==0&&obj.data.complete=="半成品"&&obj.data.category!="PVC胶带"){
                        deliver_num=parseInt(obj.data["deliver_box"])*huanSuanLv;
                    }else{
                        deliver_num=parseInt(obj.data["deliver_box"])*onum; 
                    }
                    
                }else if(layui.utils.inArray(obj.field,["deliver_num"])){
                    var is_check=layui.utils.post('check_if_change',{id:store["dataModel"]["billDeliver"]["id"]});
                    if (is_check.status !== 0){
                        layer.msg(is_check['msg']);
                        setTimeout(function(){
                            layui.$("table").find("tr").click();
                        },100);
                        return false;
                    }
                    var deliver_box=0;
                    if(obj.data.hz==0&&obj.data.complete=="半成品"&&obj.data.category!="PVC胶带"){
                        deliver_box=parseInt(obj.data["deliver_num"])/huanSuanLv;
                    }else{
                        deliver_box=parseInt(obj.data["deliver_num"])/onum; 
                    }
                    store["data"]["products"]["key_"+obj.data.id]["deliver_box"]=deliver_box;
                }else if(layui.utils.inArray(obj.field,["total_1"])){
                    if(Number(store["data"]["products"]["key_"+obj.data.id][obj.field])!=Number(obj.value)){
                        is_total_change=true;
                    }
                }
    
                store["data"]["products"]["key_"+obj.data.id][obj.field]=obj.value;
                that.dataCompute(store,is_total_change);
                if(layui.utils.isEmpty(store["data"]["total_change"])) store["data"]["total_change"]={};
                store["data"]["total_change"]["key_"+obj.data.id]=store["data"]["products"]["key_"+obj.data.id];
                
                console.log(store["data"]["total_change"]);
  
            })
            
            layui.form.on('switch(more)', function(obj){
                //layer.tips(this.value + ' ' + this.name + '：'+ obj.elem.checked, obj.othis);
                var id=layui.$(this).attr("data-id"); 
                layui.store["data"]["products"]["key_"+id]['more']=obj.elem.checked?"是":"否";
            });

            layui.table.on('row(tab2)', function(obj){
                var data =store["data"]["products"]["key_"+obj.data.id];
                obj.update({
                    "total_1":data["total_1"],
                    "deliver_num":data["deliver_num"],
                    "deliver_box":data["deliver_box"],
                    "proPrice":data["proPrice"],
                    
                    "zpfs":data["zpfs"],
                    "mz_1":data["mz_1"],
                    "jz_1":data["jz_1"],
                    "tj_1":data["tj_1"],
                    "poPrice":data["poPrice"],
                    "warehouse_code":data["warehouse_code"],
                    "warehouse_name":data["warehouse_name"],
                    "warehouse_person":data["warehouse_person"],      
                    "apportion_total":data["apportion_total"], 
                });
            });
            
  
        },
        
        // save deliver data
        saveDeliver:function(obj,store,opt){
            opt=!layui.utils.isEmpty(opt)?opt:"update_bill_deliver";
            if(opt=="update_bill_deliver"){
                obj.field["products"]=store["data"]["products"];
            }
            obj.field["product_ids"]=store["data"]["orderListIds"].join(",");
            if(!layui.utils.isEmpty(store["data"]["batch_cabinet"])){
                obj.field["pack_num"]=[];
                obj.field["seal_num"]=[];
                for(var index in store["data"]["batch_cabinet"]["cabinet_list"]){
                    var item=store["data"]["batch_cabinet"]["cabinet_list"][index];
                    if(!layui.utils.inArray(item.pack_num,obj.field["pack_num"])){
                        obj.field["pack_num"].push(item.pack_num);
                    }
                    if(!layui.utils.inArray(item.seal_num,obj.field["seal_num"])){
                        obj.field["seal_num"].push(item.seal_num);
                    }  
                }
                obj.field["pack_num"]=obj.field["pack_num"].join(",");
                obj.field["seal_num"]=obj.field["seal_num"].join(",");
                obj.field["cabinet_list"]=layui.utils.jsonEncode(store["data"]["batch_cabinet"]["cabinet_list"]);
            }
            var retInfo=layui.utils.post(opt,obj.field);
            return retInfo;
        },
        
        // Calculate the total price
        dataCompute:function(store,is_total_change){
 
            for(var index in store["data"]["products"]){
                
                var product=store["data"]["products"][index];
                var onum=!layui.utils.isEmpty(product["onum"])?product["onum"]:1;               
                var huanSuanLv=!layui.utils.isEmpty(product["huanSuanLv"])?product["huanSuanLv"]:1;
                product["mz_1"]=Number(product["deliver_box"])*Number(product["dwmz"]);

                product["jz_1"]=Number(product["deliver_box"])*Number(product["dwjz"]);
                product["tj_1"]=Number(product["deliver_box"])*Number(product["dwtj"]);
                product["zpfs"]=Number(product["deliver_num"])*Number(product["dwpf"]);
                
                if(is_total_change){
                    product["proPrice"]=Number(product["total_1"])/Number(product["deliver_num"]);
                    //product["poPrice"]=Number(product["total_1"])/Number(product["deliver_num"]);
                }else{
                    var total_1=(Number(product["deliver_num"])*Number(product["proPrice"])).toFixed(2);
                    if(Math.abs(total_1-product["total_1"])>1){
                        product["total_1"]=(Number(product["deliver_num"])*Number(product["proPrice"])).toFixed(2);
                    } 
                }
                
                product["apportion_total"]=(Number(product["total_1"])+Number(product["apportion_price"])).toFixed(2);
                
                store["data"]["products"][index]=product;
            }
            

            setTimeout(function(){
                layui.$("table").find("tr").click();
                layui.utils.tableSum("tab2",{"deliver_num":0,"total_1":0,"deliver_box":0,"tuopan_num":0,"tuopan_weight":0,"mz_1":0,"jz_1":0,"tj_1":0,"apportion_total":0});//重新计算表格总数
            },100);
        },
        
        

        initTab2:function (store){
            
            var bill_ids="";  //获取页面参数        
            var formData= layui.form.val("form-plan-audit");
            var that=this;

            if(!layui.utils.isEmpty(store["dataModel"]["billDeliver"]["bill_ids"])){
                bill_ids=store["dataModel"]["billDeliver"]["bill_ids"]
            }else{
                bill_ids=store["params"]["billIds"];
            }
            var where={opt:"deliver_product_list","orderListIds":store["data"]["orderListIds"],"bill_ids":bill_ids};
             
             
            if(!layui.utils.isEmpty(store["dataModel"]["billDeliver"]["deliver_store"])){
                where["deliver_store"]=store["dataModel"]["billDeliver"]["deliver_store"]; 
            }
             
            if(!layui.utils.isEmpty(store["params"]["id"])){
                where["deliver_id"]=store["params"]["id"];
            }
            
            var cols=layui.deliver_cols.product_list;
            
            
            if(layui.utils.inArray(store["params"]["showType"],['6'])){
                //cols[0][8]["edit"]="";
                cols=layui.deliver_cols.product_list1;
                //var cols = layui.utils.setColsAttr(cols,{"u8Code":{"hide":true},"more":{"hide":true}});
               
            }
            
            //init product list
            layui.table.render({elem: '#tab2', url: layui.utils.urlData['provebill'],cols:cols, 
                where:where,
                toolbar: '#tab2Bar1',
                totalRow: true ,
                loading:false,
                parseData: function(res){ 
                    if(!layui.utils.isEmpty(res.data)){
                        for(var index in res.data){ 
                            let item=res.data[index];
                            //计算毛净重
                            if(layui.utils.isEmpty(store["data"]["products"]["key_"+item.id])){
                                store["data"]["products"]["key_"+item.id]=layui.utils.objClone(item);
                            }else{
                                let productItem=store["data"]["products"]["key_"+item.id];
                                if(layui.utils.isEmpty(productItem["warehouse_code"])){
                                    store["data"]["products"]["key_"+item.id]["warehouse_code"]=formData["warehouse_code"];
                                    store["data"]["products"]["key_"+item.id]["warehouse_name"]=formData["warehouse_name"];
                                    store["data"]["products"]["key_"+item.id]["warehouse_person"]=formData["warehouse_person"];
                                }
                                res.data[index]=store["data"]["products"]["key_"+item.id];
                            }
     
                        }
          
                    }
                    return {"code": res.code,"msg": res.msg, "count": res.count, "data": res.data };
                }, done: function (res, curr, count) {
                    if(!layui.utils.isEmpty(res.data)){
                        layui.$("#tab2 + div .layui-table-box tr td").attr({"style": "background:#f8f8f8",});
                        layui.$("#tab2 + div .layui-table-box tr td[data-edit=text]").attr({"style": "background:#fff"});
                    }
                    
                    if(layui.utils.isEmpty(store["dataModel"]["billDeliver"]["warehouse_code"])){
                        that.warehouse_check(store);  
                    }
                    that.dataCompute(store);//计算发货数据
                }     
            });
        },
        
        warehouse_select:function(store){
            var updateInfo={
                "warehouse_code":store["data"]["select_warehouse_item"]["cWhCode"],
                "warehouse_person":store["data"]["select_warehouse_item"]["cWhPerson"],
                "warehouse_name":store["data"]["select_warehouse_item"]["cWhName"]
            }
            layui.form.val("form-plan-audit", updateInfo);
            layui.form.val("form-select", updateInfo);
        },
    
        get_product_total:function(store){
            var retInfo={"deliver_total":0,"cabinet_total":0};
            for(var index in store["data"]["products"]){
                retInfo["deliver_total"]+=Number(store["data"]["products"][index]["deliver_num"]);
            }
            
            if(layui.utils.isEmpty(store["data"]["batch_cabinet"])) return retInfo;
            if(layui.utils.isEmpty(store["data"]["batch_cabinet"]["cabinet_list"])) return retInfo;
                    

            for(var index in store["data"]["batch_cabinet"]["cabinet_list"]){
                var item=store["data"]["batch_cabinet"]["cabinet_list"][index];
                if(!layui.utils.isEmpty(item["product_list"])){
                    for(var i in item["product_list"]){
                        retInfo["cabinet_total"]+=Number(item["product_list"][i]["cabinet_num"]);
                    }
                }
            }  
            
            
            return retInfo;
        },
        
        warehouse_check:function(store){
            var tableData=layui.table.cache["tab2"];
            var warehouseInfo={"warehouse_code":"","warehouse_person":"","warehouse_name":"","account_type":""}
            if(!layui.utils.isEmpty(tableData)){  
                var completeArray=[];
                var categoryArray=[];
                for(var index in tableData){
                    var item=tableData[index];
                    warehouseInfo["account_type"]=item["zhangTao"];
                    if(layui.utils.isEmpty(warehouseInfo["warehouse_code"])){
                        warehouseInfo["warehouse_code"]=item["warehouse_code"];
                        warehouseInfo["warehouse_person"]=item["warehouse_person"];
                        warehouseInfo["warehouse_name"]=item["warehouse_name"];
                    }
                    if(warehouseInfo["warehouse_code"]!=item["warehouse_code"]){
                        warehouseInfo={"account_type":warehouseInfo["account_type"],"warehouse_code":"","warehouse_person":"","warehouse_name":""};
                        break;  
                    }                       
                }
                
       
                for(var i in tableData){
                    var val=tableData[i];
                    if(!layui.utils.inArray(val["complete"],completeArray)){
                        completeArray.push(val["complete"]);
                    }
                    if(!layui.utils.inArray(val["category"],categoryArray)){
                        categoryArray.push(val["category"]);
                    }
                } 
                
                if(layui.utils.isEmpty(warehouseInfo["warehouse_code"])){
                    

                    if(layui.utils.inArray("成品",completeArray)){
                        if(store["dataModel"]["billDeliver"]["deliver_store"]=="上海仓库"){
                            warehouseInfo["warehouse_code"]="001";
                            warehouseInfo["warehouse_person"]="张安宝";
                            warehouseInfo["warehouse_name"]="上海成品仓库(销售开发货单用)";
                        }else if(store["dataModel"]["billDeliver"]["deliver_store"]=="越南仓库"){
                            warehouseInfo["warehouse_code"]="104";
                            warehouseInfo["warehouse_person"]="吴彪";
                            warehouseInfo["warehouse_name"]="江西二期_淋膜原材料仓库";
                        }else{
                            warehouseInfo["warehouse_code"]="003";
                            warehouseInfo["warehouse_person"]="闻伟";
                            warehouseInfo["warehouse_name"]="江西成品仓库(销售开发货单用)";
                        }
                    }else{
                        if(store["dataModel"]["billDeliver"]["deliver_store"]=="上海仓库"){
                            warehouseInfo["warehouse_code"]="002";
                            warehouseInfo["warehouse_person"]="吴在连";
                            warehouseInfo["warehouse_name"]="上海半成品仓库(销售开发货单用)";
                        }else if(store["dataModel"]["billDeliver"]["deliver_store"]=="越南仓库"){
                            warehouseInfo["warehouse_code"]="102";
                            warehouseInfo["warehouse_person"]="段正元";
                            warehouseInfo["warehouse_name"]="江西一期_造纸原材料仓库";
                           
                        }else{
                            warehouseInfo["warehouse_code"]="004";
                            warehouseInfo["warehouse_person"]="黄志伟";
                            warehouseInfo["warehouse_name"]="江西半成品仓库(销售开发货单用)";
                        }
                    }
     
                }else if(store["dataModel"]["billDeliver"]["deliver_store"]=="上海仓库"&&completeArray.length==1){
                    //如果都是PVC胶带那么显示为成品仓库
                    if(completeArray[0]=="成品" || (categoryArray.length==1 && categoryArray[0]=='PVC胶带')){
                        warehouseInfo["warehouse_code"]="001";
                        warehouseInfo["warehouse_person"]="张安宝";
                        warehouseInfo["warehouse_name"]="上海成品仓库(销售开发货单用)";
                    }else if(completeArray[0]=="半成品"){
                        warehouseInfo["warehouse_code"]="002";
                        warehouseInfo["warehouse_person"]="吴在连";
                        warehouseInfo["warehouse_name"]="上海半成品仓库(销售开发货单用)";
                    }
    
                } 
            }   
            
            layui.form.val("form-plan-audit",warehouseInfo);
        },
        
        product_warehouse_select:function(store){
            
            var orderListId = store["data"]["select_order_info"]["id"];
            store["data"]["products"]["key_"+orderListId]["warehouse_code"]=store["data"]["select_warehouse_item"]["cWhCode"];
            store["data"]["products"]["key_"+orderListId]["warehouse_name"]=store["data"]["select_warehouse_item"]["cWhName"];
            store["data"]["products"]["key_"+orderListId]["warehouse_person"]=store["data"]["select_warehouse_item"]["cWhPerson"];
            //this.initTab2(store);
            console.log(store["data"]["products"]);
            layui.$("table").find("tr").click();
            this.warehouse_check(store);
            
        },
        
        batch_warehouse_select:function(store){
            if(!layui.utils.isEmpty(store["data"]["select_order_list"])){
                for(var index in store["data"]["select_order_list"]){
                    var item=store["data"]["select_order_list"][index];
                    store["data"]["products"]["key_"+item.id]["warehouse_code"]=store["data"]["select_warehouse_item"]["cWhCode"];
                    store["data"]["products"]["key_"+item.id]["warehouse_name"]=store["data"]["select_warehouse_item"]["cWhName"];
                    store["data"]["products"]["key_"+item.id]["warehouse_person"]=store["data"]["select_warehouse_item"]["cWhPerson"]; 
                }
            }
            layui.$("table").find("tr").click(); 
            this.warehouse_check(store);
        },
        
        
        
        print_deliver:function(store,deliver_id){
            var deliver=layui.utils.post("get_deliver_info",{"id":deliver_id});
            var products=layui.utils.post("deliver_product_list",{"deliver_id":deliver_id});

           
            //设置打印数据
            store['data']['printInfo']={
                "customer_short":deliver.data["customer_short"],
                "deliver_date":deliver.data["deliver_date"],
                "invoice_nums":deliver.data["invoice_nums"],
                "sales_num":deliver.data["sales_num"],
                "account_type":deliver.data["account_type"],
                "cAccId":deliver.data["cAccId"],
                "sale_type":deliver.data["sale_type"],
                "cCusPPersonText":deliver.data["cCusPPersonText"],
                "order_num":deliver.data["order_num"],
                "coin_type":deliver.data["coin_type"],
                "expiry_date":deliver.data["expiry_date"],
                "mark1":deliver.data["mark"],
                "entry_num":deliver.data["entry_num"],
                "creator_name":deliver.data["creator_name"],
                "approver":deliver.data["approver"],
                "financial":" ",
                "create_time":deliver.data["create_time"],
                "mark2":"白联:销货存档用;&nbsp;&nbsp;&nbsp;&nbsp;红联:给财务;&nbsp;&nbsp;&nbsp;&nbsp;绿联:给单证;&nbsp;&nbsp;&nbsp;其他联:给仓库﹔质量管理者:",
                "products":products["data"]     ,
                "user":store['data']['user']['user_name'],
                "entry_address":deliver.data["entry_address"],
                "entry_time":deliver.data["entry_time"],
                "po":deliver.data["po"]
            };
            layui.print.show(store,"['data']['printInfo']","print_deliver")//调用打印组件
        }
    };
 
  exports('bill_deliver', obj);
});    
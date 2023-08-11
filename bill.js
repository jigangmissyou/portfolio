layui.define(['utils','laydate','bill_cols','deliver_cols','customer_cols','xmSelect'],function(exports){
  var obj = {
    
    data:{
       dataInfo:{}
    },

    initData: function(store){
        var that=this;
        var id=typeof(store['params']['id'])!="undefined"?store['params']['id']:0;  //获取页面参数
        var flow_id=typeof(store['params']['flow_id'])!="undefined"?store['params']['flow_id']:0;  //获取页面参数
        var parent_flow_id=typeof(store['params']['parent_flow_id'])!="undefined"?store['params']['parent_flow_id']:0;  //获取页面参数
        var is_customs_information = store['params']['is_customs_information'];  //获取页面参数       
        if(!layui.utils.isEmpty(store['params']['msg_id'])){
            store['dataModel']['agency']=layui.utils.sqlManage("user_agency_list",{"id":store['params']['msg_id'],"limit":1});
        }
  
        //调用远程接口获取页面数据
        var dataInfo=layui.utils.post('init_bill_info',{id:id,confirmId:store["params"]["confirmId"]});

        if(flow_id){
            //调用远程接口获取当前流程数据
            store["dataModel"]["flowInfo"]=layui.utils.post('get_current_flow',{flow_id:flow_id});
        }

        if(parent_flow_id){
            //调用远程接口获取当前流程数据
            store["dataModel"]["parentFlowInfo"]=layui.utils.post('get_current_flow',{flow_id:parent_flow_id});
        }
        
        
        //初始化订仓数据模型
        store["dataModel"]["billInfo"]=dataInfo["bill_info"];
        store["dataModel"]["provebill_confirm"]=dataInfo["provebill_confirm"];
      
        //初始化已勾选数据信息
        if(dataInfo["store_data"]) store["data"]=dataInfo["store_data"];
        
        //初始化柜量模型数据表
        store["dataModel"]["cabinetNum"]={"cabinet_type":"数量","cabinet1":0,"cabinet2":0,"cabinet3":0,"cabinet4":0}
       
        
        if(dataInfo["bill_info"]["cabinet_num"]){


            store["dataModel"]["cabinetNum"]=JSON.parse(dataInfo["bill_info"]["cabinet_num"]);

            //初始化装柜模型数据
            store["dataModel"]["cabinetInfo"]=dataInfo["cabinet_info"];
            store["dataModel"]["cabinetInfo"]["end_port"]=dataInfo["bill_info"]["end_port"];//添加目的港数据
            store["data"]['tab3Ids']=[];
            if(typeof(store["dataModel"]["cabinetInfo"]["freeItem"])!="undefined"&&store["dataModel"]["cabinetInfo"]["freeItem"]){
                //console.log(store["dataModel"]["cabinetInfo"]);
                store["data"]['tab3Ids']=JSON.parse(store["dataModel"]["cabinetInfo"]["freeItem"]);
            }   
        }
    
        
        //初始化调账数据
        if(!layui.utils.isEmpty(store["dataModel"]["billInfo"]["adjust_bill"])){
            store["dataModel"]["billInfo"]["adjust_bill"]=JSON.parse(store["dataModel"]["billInfo"]["adjust_bill"]);
        }
        

        //初始化产品权限
        store["data"]["user_runpriv"]=typeof(dataInfo["user_runpriv"]!="undefined")?dataInfo["user_runpriv"]:"";
       
        this.data.dataInfo=dataInfo;
        

        //初始化贸易公司信息
        store["data"]["accountCtr"]={};


        if(!layui.utils.isEmpty(dataInfo["bill_info"]["account_ctr_id"])){
            var accountCtr=layui.utils.post('sql_manage_api',{"unique_code":"account_ctr_list","id":dataInfo["bill_info"]["account_ctr_id"],limit:1});
            store["data"]["accountCtr"]={"customer_code":accountCtr["customer_code"], "customer_short":accountCtr["trade_company"]}
        }
      
 
        //初始化柜量信息
        store["data"]["cabinetNum"]=[store["dataModel"]["cabinetNum"]];
        store["dataModel"]["cabinetInfo"]["is_overweight_cabinet"]=dataInfo["bill_info"]["is_overweight_cabinet"];
        if(layui.utils.isEmpty(store["dataModel"]["cabinetInfo"]["is_customs_information"]) && is_customs_information ==1){
            store["dataModel"]["cabinetInfo"]["is_customs_information"] = '是';
        }
        
        
        //渲染地址列表
        var addressList=[]
        if(dataInfo["store_info"]&&dataInfo["store_info"]["data_content"]){ 
            
            
            //console.log(dataInfo["store_info"]["data_content"]);
            
            var addressList=typeof(dataInfo["store_info"]["data_content"])!="undefined"?JSON.parse(dataInfo["store_info"]["data_content"]):[];
            addressList=addressList["addressList"]?addressList["addressList"]:[];
        }
        
        if(dataInfo["bill_info"]["customer_address"]&&!layui.utils.inArray(dataInfo["bill_info"]["customer_address"],addressList,"customer_address")){
            addressList.push({
                "start_port":dataInfo["bill_info"]["start_port"],
                "end_port":dataInfo["bill_info"]["end_port"],
                "customer_address":dataInfo["bill_info"]["customer_address"]
            }); 
        }
        
        store["data"]["addressList"]=addressList;
        store["data"]["spells_info"]=layui.utils.sqlManage("spells_list",{"bill_id":id,limit:1});


        //console.log( store);
        //渲染页面主体
        layui.utils.initTpl("form_tmp","#form_box",store);  
        layui.utils.initTpl("select_address_tmp","#select_address_box",{addressList:addressList}); 
        layui.utils.initTpl("customer_short_tmp","#customer_short_box",store);  //渲染客户列表
        this.initChangeInfo(store);//加载变更数据
        //加载变更数据
        console.log(store);
        that.dataBind(store); 
    },
    
    
    //加载变更数据
    initChangeInfo:function(store){
        
        
        
        if(!layui.utils.isEmpty(store['params']['change_flow_id'])) {
            if(layui.utils.inArray(store['params']['opt'],["change","change_audit","change_detail"])){
                var retInfo = layui.utils.post('sql_manage_api',{"unique_code":"change_bill_list",flowType:"订舱变更",flowId:store['params']['change_flow_id'],limit:1});//提交表单数据请求操作

                var changeInfo=JSON.parse(retInfo["data2"]);
               

                store['data']['change_bill_info']=changeInfo;
                store["data"]['source_order_list']=layui.utils.objClone(store["data"]['orderLists']);
                store["data"]['orderLists']=changeInfo['orderLists'];
                
                
             
                store["data"]["orderListIds"]=[];
                for(var i in changeInfo['orderLists']){
                    store["data"]["orderListIds"].push(changeInfo['orderLists'][i]['order_list_id']);

                    store["data"]["orderIds"].push(changeInfo['orderLists'][i]['order_id']);
                    
                }
                var cabinetNum=[];
                cabinetNum.push(changeInfo['cabinet_num']);
                store["data"]['source_cabinet_num']=store["data"]['cabinetNum'];
                store["data"]['cabinetNum']=cabinetNum;
                store["dataModel"]["cabinetNum"]=changeInfo['cabinet_num'];
                
                /*if(!layui.utils.isEmpty(changeInfo["orderLists"])){
                    
                }*/
                
            }else if(layui.utils.inArray(store['params']['opt'],["cabinet_change","cabinet_change_audit","cabinet_change_detail"])){
                var retInfo = layui.utils.post('sql_manage_api',{"unique_code":"change_cabinet_list",flowType:"装柜变更",flowId:store['params']['change_flow_id'],limit:1});//提交表单数据请求操作
                var changeInfo=JSON.parse(retInfo["data2"]);
                store['data']['change_cabinet_info']=changeInfo;
                var cabinetNum=[];
                cabinetNum.push(changeInfo['cabinet_num']);
                store["data"]['source_cabinet_num']=store["data"]['cabinetNum'];
                store["data"]['cabinetNum']=cabinetNum;
                store["dataModel"]["cabinetNum"]=changeInfo['cabinet_num'];
            }

        }else{
            var changeLog = "";
            if(!layui.utils.isEmpty(store['params']['change_log_id'])){
                changeLog = layui.utils.sqlManage("get_log_list",{id:store['params']['change_log_id'],limit:1}); 
            }else{
                changeLog = layui.utils.sqlManage("get_log_list",{case_id:store['params']['id'],type_flag:"provebill",order_by:" id DESC ",limit:1}); 
            }


            if(layui.utils.isEmpty(changeLog)) return false;
            store['data']['change_bill_log']={"data1":{},"data2":{}};
            store['data']['change_bill_log']["data1"]=changeLog["data1"];
            store['data']['change_bill_log']["data2"]=changeLog["data2"];
        }
        
        

    },
    
    initStoreData:function(store){
        //get memory data
        var storeInfo=[];
        if(store["dataModel"]["billInfo"]["customer_code"]!=""){
            storeInfo = layui.utils.post("sql_manage_api",{"unique_code":"data_store_list","data_type":1,"data_key":store["dataModel"]["billInfo"]["customer_code"],"limit":1});
        }
        var storeContent="";
        if(!layui.utils.isEmpty(storeInfo["data_content"])) storeContent=JSON.parse(storeInfo["data_content"]);
        //appoint_info
        if(!layui.utils.isEmpty(storeContent)){
            var filter=["customer_start","customer_end","give_time","inspect_time","adjust_name","adjust_money","acut_adj_type","pre_ocean_freight","ocean_freight"];
            for(var index in filter){
                delete storeContent[filter[index]];
            }
        }
        store["data"]["addressList"]=typeof(storeContent["addressList"])!="undefined"?storeContent["addressList"]:[];
        if(store["dataModel"]["billInfo"]["customer_address"]&&store["data"]["addressList"].length==0){
            store["data"]["addressList"].push({"start_port":"","end_port":"","customer_address":store["dataModel"]["billInfo"]["customer_address"]});
        }
        layui.utils.initTpl("select_address_tmp","#select_address_box",{addressList:store["data"]["addressList"]});
        if(storeContent){
            delete storeContent["bill_num"];
            delete storeContent["order_nums"];
            delete storeContent["invoice_num"];
            storeContent['appoint_info'] = '';
            storeContent['ship_doc'] = '';
            storeContent['special_need'] = '';
            // console.log(storeContent);debugger;
            store["dataModel"]["billInfo"]["customer_name"]=storeContent["customer_name"];//更新开票抬头
            layui.form.val("form-bill", storeContent);//表单数据更新
        }
        
        layui.form.render();//渲染表单各个控件
    },
    
    productSum:function(store){
        store["data"]["product_sum"]={"total_1":0,"box_num":0,"tuopan_num":0,"tuopan_weight":0,"mz_1":0,"jz_1":0,"tj_1":0,"apportion_total":0};
        for(var index in store["data"]["orderLists"]){
           var item= store["data"]["orderLists"][index];
           store["data"]["product_sum"]["total_1"]+=parseFloat(item.total_1);
           store["data"]["product_sum"]["box_num"]+=parseFloat(item.box_num);
           store["data"]["product_sum"]["tuopan_num"]+=parseFloat(item.tuopan_num);
           store["data"]["product_sum"]["tuopan_weight"]+=parseFloat(item.tuopan_weight);
           store["data"]["product_sum"]["mz_1"]+=parseFloat(item.mz_1);
           store["data"]["product_sum"]["jz_1"]+=parseFloat(item.jz_1);
           store["data"]["product_sum"]["tj_1"]+=parseFloat(item.tj_1);
           store["data"]["product_sum"]["apportion_total"]+=parseFloat(item.apportion_total);
        }    
        
        store["data"]["product_sum"]["total_1"]=store["data"]["product_sum"]["total_1"].toFixed(2);
        
        for(var index in store["data"]["product_sum"]){
            layui.$("div[lay-id=tab3] .layui-table-total").find("td[data-field="+index+"] div").html(store["data"]["product_sum"][index]);
        }
    },
    
    upload_back(res){
      
        var formInfo=layui.form.val("form-bill");
        if(formInfo["attachment_list"]){
            formInfo["attachment_list"]=JSON.parse(formInfo["attachment_list"]);
            formInfo["attachment_list"].push(JSON.parse(res));
        }
        
        
        
        //layui.form.val("form-bill",{"attachment_list":layui.utils.jsonEncode(formInfo["attachment_list"])})
        
        /*console.log(formInfo["attachment_list"]);
        console.log(res);*/
    },
    
    selectAddressHook:function(formData){
        layui.form.val("form-bill", {
            "customer_address":formData["customer_address"],
            "end_port":formData["end_port"],
            "start_port":formData["start_port"],
            "customer_name":formData["customer_name"]
        });
    },
   
    
    
    dataBind: function(store){
        var that=this;console.log(that.data);
        //延时渲染表单控件并赋值
  
        setTimeout(function(){

            //初始化下拉框数据
            var selectArray=["load_type","trade_type","inout_type","pay_type","coin_type","prove_type","start_port","acut_adj_type","certificate_type"];
            for(var index in selectArray){
                var tempData={name:selectArray[index],data:that.data.dataInfo['select'][selectArray[index]]}
                if(layui.utils.inArray(selectArray[index],["trade_type"])) tempData["required"]=true;
                layui.utils.initTpl("select_tmp","."+selectArray[index],tempData);//初始化下拉框  
            }
            
            //初始化贸易公司
            that.initloadCtr(store);
            //初始化时间控件
            var dataArray=["give_time","entry_time","inspect_time","customer_start","customer_end","cabinet_time","real_cabinet_time","ship_time","real_ship_time","etd_date","withhold_time"];
            for(var index in dataArray) layui.laydate.render({elem: '.'+dataArray[index]});

            //初始化上传控件
            var uploadArray=["ship_doc"];
            for(var index in uploadArray) {
                var uploadStr=that.data.dataInfo['bill_info'][uploadArray[index]];
                uploadStr=(typeof(uploadStr)!="undefined")?uploadStr:"";
                layui.utils.initUpload(uploadArray[index],uploadStr);  
            }
            
            //初始化装柜附件

            if(typeof(that.data.dataInfo['bill_info']["attachment"])=="undefined"){
                that.data.dataInfo['bill_info']["attachment"]="";
            }



            layui.utils.initUpload("attachment",that.data.dataInfo['bill_info']["attachment"],"layui.bill.upload_back",true);  
            
            //初始化装柜附件
            if(typeof(store["dataModel"]["cabinetInfo"]["so_attachment"])=="undefined"){
                store["dataModel"]["cabinetInfo"]["so_attachment"]="";
            }
            layui.utils.initUpload("so_attachment",store["dataModel"]["cabinetInfo"]["so_attachment"]);
            
            
            //初始化进仓单附件
            if(typeof(store["dataModel"]["cabinetInfo"]["receipt_attachment"])=="undefined"){
                store["dataModel"]["cabinetInfo"]["receipt_attachment"]="";
            } 
            layui.utils.initUpload("receipt_attachment",store["dataModel"]["cabinetInfo"]["receipt_attachment"],"",true);

            //提单复印件
            if(typeof(store["dataModel"]["cabinetInfo"]["lading_bill"])=="undefined"){
                store["dataModel"]["cabinetInfo"]["lading_bill"]="";
            }
            layui.utils.initUpload("lading_bill",store["dataModel"]["cabinetInfo"]["lading_bill"],"",true);

            //提单原件
            if(typeof(store["dataModel"]["cabinetInfo"]["origin_lading_bill"])=="undefined"){
                store["dataModel"]["cabinetInfo"]["origin_lading_bill"]="";
            }
            layui.utils.initUpload("origin_lading_bill",store["dataModel"]["cabinetInfo"]["origin_lading_bill"],"",true);

            //产地证
            if(typeof(store["dataModel"]["cabinetInfo"]["origin_card"])=="undefined"){
                store["dataModel"]["cabinetInfo"]["origin_card"]="";
            }
            layui.utils.initUpload("origin_card",store["dataModel"]["cabinetInfo"]["origin_card"],"",true);

            //customs_agreement
            if(typeof(store["dataModel"]["cabinetInfo"]["customs_agreement"])=="undefined"){
                store["dataModel"]["cabinetInfo"]["customs_agreement"]="";
            }
            layui.utils.initUpload("customs_agreement",store["dataModel"]["cabinetInfo"]["customs_agreement"],"",true);

            //domestic_transportation_invoice
            if(typeof(store["dataModel"]["cabinetInfo"]["domestic_transportation_invoice"])=="undefined"){
                store["dataModel"]["cabinetInfo"]["domestic_transportation_invoice"]="";
            }
            layui.utils.initUpload("domestic_transportation_invoice",store["dataModel"]["cabinetInfo"]["domestic_transportation_invoice"],"",true);
            //international_transportation_invoice
            if(typeof(store["dataModel"]["cabinetInfo"]["international_transportation_invoice"])=="undefined"){
                store["dataModel"]["cabinetInfo"]["international_transportation_invoice"]="";
            }
            layui.utils.initUpload("international_transportation_invoice",store["dataModel"]["cabinetInfo"]["international_transportation_invoice"],"",true);
            //customs_broker_invoice
            if(typeof(store["dataModel"]["cabinetInfo"]["customs_broker_invoice"])=="undefined"){
                store["dataModel"]["cabinetInfo"]["customs_broker_invoice"]="";
            }
            layui.utils.initUpload("customs_broker_invoice",store["dataModel"]["cabinetInfo"]["customs_broker_invoice"],"",true);



            if(typeof(store["dataModel"]["cabinetInfo"]["customs_declaration"])=="undefined"){
                store["dataModel"]["cabinetInfo"]["customs_declaration"]="";
            }
            layui.utils.initUpload("customs_declaration",store["dataModel"]["cabinetInfo"]["customs_declaration"],"",true);


            if(typeof(store["dataModel"]["cabinetInfo"]["certificate"])=="undefined"){
                store["dataModel"]["cabinetInfo"]["certificate"]="";
            }
            if(store["dataModel"]["billInfo"]["special_need"]=="是"){
                layui.$(".special_need_div").show();
            }
            layui.utils.initUpload("certificate",store["dataModel"]["cabinetInfo"]["certificate"],"",true);

       

            layui.form.render();//渲染表单各个控件
            
            console.log(store["dataModel"]["cabinetInfo"])
            store["dataModel"]["cabinetInfo"]["invoice_num"]=store["dataModel"]["billInfo"]["invoice_num"];
            console.log(store);
            layui.form.val("form-bill", store["dataModel"]["billInfo"]);//表单赋值
            layui.form.val("form-cabinet", store["dataModel"]["cabinetInfo"]);//表单赋值
            that.initChangeData(store);//初始化变更数据
            //初始化
            that.initCertificate();
        },500);
    },
      initCertificate: function(){
        var certificate_type = !layui.utils.isEmpty(this.data.dataInfo['bill_info']['certificate_type'])?this.data.dataInfo['bill_info']['certificate_type'].split(','):'';
          //渲染多选下拉菜单
          var demo1 = layui.xmSelect.render({
              el: '#certificate_type',
              filterable: true,
              size: 'mini',
              name: 'certificate_type',
              tips: '请选择认证书类型',
              initValue:certificate_type ,
              data: this.data.dataInfo['select']['certificate_type']['dataList'],
          })

      },
    
    formReload:function(params){      
        
    },
    
    //加载下拉框
    initSelect:function(store){
        //return false;
        store["data"]["select"]=!layui.utils.isEmpty(store["data"]["selectList"])?store["data"]["selectList"]:[];
        store["data"]["select"]["invoice_num"]={"desc":"发票","dataList":[]};

        if(!layui.utils.isEmpty(store["data"]["order_nums"])){
            for(var index in store["data"]["order_nums"]){
                store["data"]["select"]["invoice_num"]["dataList"].push({
                    name:store["data"]["order_nums"][index],
                    value:store["data"]["order_nums"][index]
                }); 
            }
        }

        /*var selectArray=["invoice_num"];
        for(var index in selectArray){
            layui.utils.initTpl("select_tmp","."+selectArray[index],{
                name:selectArray[index],
                data:store["data"]["select"][selectArray[index]],
            });//初始化下拉框  
        }*/
 
        var ins3 = layui.selectInput.render({
            elem: '#invoice_num',
            data:store["data"]["select"]["invoice_num"]["dataList"],
            placeholder: '请输入发票号',
            name: 'invoice_num',
            initValue:"", // 渲染初始化默认值
            remoteSearch: false
        });
        
        layui.$("input[name='invoice_num']").click(function(){
           layui.$(this).parents("#invoice_num").find(".layui-form-selectInput dl,.layui-form-selectInput dd").css("display","block");
        });
        
        if(layui.utils.isEmpty(store["dataModel"]["billInfo"]["invoice_num"])&&store["data"]["select"]["invoice_num"]["dataList"].length>0){
            store["dataModel"]["billInfo"]["invoice_num"]=store["data"]["select"]["invoice_num"]["dataList"][0]["value"];
        }

        layui.form.render();//渲染表单各个控件
        layui.form.val("form-bill",{"invoice_num":store["dataModel"]["billInfo"]["invoice_num"]});//表单赋值
  
    },
    
    
    
    /**
     * 
     * @param {type} store
     * @returns {undefined}
     */
    initloadCtr:function(store){
        var selectItem=[],customerItem=[];
        
       
        if(typeof(store["data"]["accountCtr"])!="undefined"&&store["data"]["accountCtr"]['customer_code']){
            store["data"]["account_ctr_info"]=layui.utils.post('sql_manage_api',{"unique_code":"account_ctr_list","customer_code":store["data"]["accountCtr"]['customer_code'],limit:1});
      
            if(typeof(store["data"]["account_ctr_info"]["id"])!="undefined"&&store["data"]["account_ctr_info"]["id"]!=""){
                selectItem.push({name:store["data"]["account_ctr_info"]["trade_company"],value:store["data"]["account_ctr_info"]["id"]});
                store["data"]["customerList"]=JSON.parse(store["data"]["account_ctr_info"]["customer_list"]);  
                if(store["data"]["customerList"]&&store["data"]["customerList"].length>0){
                    for(var index in store["data"]["customerList"]){
                        var item=store["data"]["customerList"][index];
                        customerItem.push({name:item["customer_short"],value:item["customer_short"]});
                    }
                   
                }
                
                
            }
            
        }
        
       
        layui.utils.initTpl("customer_short_tmp","#customer_short_box",{customerItem:customerItem});  //渲染客户列表
        layui.utils.initTpl("select_tmp",".account_ctr",{ name:"account_ctr_id",data:{desc: "贸易公司",dataList: selectItem} });//初始化下拉框
        
        layui.form.val("form-bill",{"account_ctr_id":store["dataModel"]["billInfo"]["account_ctr_id"],"customer_short":store["dataModel"]["billInfo"]["customer_short"]} );//表单赋值
        layui.form.render();//渲染表单各个控件
    },
    
    /**
     * 初始化贸易公司
     * @param {type} store
     * @returns {Boolean}
     */
    initAccountCtr:function(store){
        this.initloadCtr(store);
        var formData={"account_ctr_id":store["dataModel"]["billInfo"]["account_ctr_id"],"customer_short":store["dataModel"]["billInfo"]["customer_short"]};
        if(store["dataModel"]["billInfo"]["customer_code"]==""){
            store["data"]["account_ctr_info"]=[];
            store["data"]["customerList"]=[];
        }
        if(typeof(store["data"]["account_ctr_info"])!="undefined"&&typeof(store["data"]["customerList"])!="undefined"&&store["data"]["customerList"].length>0){
            formData["account_ctr_id"]=store["data"]["account_ctr_info"]["id"];

            if(!layui.utils.isEmpty(store['dataModel']['billInfo']['orderNum'])){
                var order_num_type = store['dataModel']['billInfo']['orderNum'].slice(0, 4);//0123
                if (order_num_type == 'JJJX' || order_num_type == 'JJSH'){

                }else{
                    store["dataModel"]["billInfo"]["customer_country"]=store["data"]["customerList"][0]["customer_country"];
                    store["dataModel"]["billInfo"]["customer_code"]=store["data"]["customerList"][0]["customer_code"];
                    store["dataModel"]["billInfo"]["customer_name"]=store["data"]["customerList"][0]["customer_name"];
                    store["dataModel"]["billInfo"]["customer_address"]=store["data"]["customerList"][0]["customer_address"];
                    store["dataModel"]["billInfo"]["customer_short"]=store["data"]["customerList"][0]["customer_short"];
                    formData["customer_short"]=store["data"]["customerList"][0]["customer_short"];
                }
            }

        }
     
        setTimeout(function(){
            layui.form.render();//渲染表单各个控件
            layui.form.val("form-bill",formData);//表单赋值

        },500);
        
       
    },

    initTab1: function (store){
        
    
        
        var customer_code=store["dataModel"]["billInfo"]["customer_code"];
        
      
        if(typeof(store["data"]["accountCtr"])!="undefined"&&store["data"]["accountCtr"]['customer_code']){
            customer_code=store["data"]["accountCtr"]['customer_code'];
        }
        
        var show_order_ids=!layui.utils.isEmpty(store["data"]["orderIds"])?store["data"]["orderIds"]:[];
  
        
       
        var that=this;
        //初始化订单列表
        return  layui.table.render({elem: '#tab1',url: layui.utils.urlData['provebill'], cols: layui.bill_cols['order_list'] 
            ,loading:true
            ,toolbar: '<div>订单列表</div>'
            ,toolbar: true
            ,text: {  none: '暂无相关数据'}
            ,where:{opt:"get_order_list", cCusCode:customer_code,view_type:1,show_order_ids:show_order_ids}
            ,page: true
            ,parseData: function(res){ 
                //默认选中判断
                if(typeof(res.data)!='undefined'&&res.data){
                    for(var index in res.data){ 
                        if(store["data"]['orderIds'].indexOf(res.data[index].id)>-1){
                            res.data[index]['LAY_CHECKED']=true;
                            if(!layui.utils.isEmpty(res.data[index]["cCusCode"])){
                                store["data"]["accountCtr"]={"customer_code":res.data[index]["cCusCode"], "customer_short":res.data[index]["cCusCode"]}
                            }
                        }
                    }
                } 
                return {"code": res.code,"msg": res.msg, "count": res.count, "data": res.data };
            }
            ,done: function(res, curr, count){
                that.initloadCtr(store)//初始化贸易公司
                that.initTab2(store);
                
            }
        });
        
    },
    
    
    
    initTab2: function (store){
   
        var provebill_id=store["dataModel"]["billInfo"]["id"]?store["dataModel"]["billInfo"]["id"]:0;
        //同步渲染产品表格
        return layui.table.render({elem: '#tab2', url: layui.utils.urlData['provebill'],cols:layui.bill_cols['product_list']
            , where:{opt:"get_product_list",orderIds:store["data"]["orderIds"],"bill_product":true,billId:provebill_id},page: false
            ,parseData: function(res){ 
                //默认选中判断
                if(typeof(res.data)!='undefined'&&res.data){
                    for(var index in res.data){ 
                        if(store["data"]['orderListIds'].indexOf(res.data[index].id)>-1){
                            res.data[index]['LAY_CHECKED']=true;
                        }
                    }
                } 
                return {"code": res.code,"msg": res.msg, "count": res.count, "data": res.data };
            }
            ,toolbar: '<div>产品列表</div>'
            ,totalRow:true
        });
    },
    

    initTab3:function(store,bill_id){
        
        var provebill_id=store["dataModel"]["billInfo"]["id"]?store["dataModel"]["billInfo"]["id"]:0;
        var listType=0;
        if(!store["params"]["opt"]||store["params"]["opt"]=="edit") listType=1;
        if(layui.utils.inArray(store["params"]["opt"],["cabinet_audit","cabinet","cabinet_detail"])) listType=2;
        
        if(typeof(store["params"]["showType"])!="undefined"&&layui.utils.inArray(store["params"]["showType"],["10","17","5",'3','9'])){
            listType=3;
        };
        
        if(typeof(store["params"]["showType"])!="undefined"&&layui.utils.inArray(store["params"]["showType"],["24"])){
            listType=4;
        };
        
         if(typeof(store["params"]["showType"])!="undefined"&&layui.utils.inArray(store["params"]["showType"],["5"])){
            listType=5;
        };
        
        if(typeof(store["params"]["showType"])!="undefined"&&layui.utils.inArray(store["params"]["showType"],["12"])){
            listType=1;
        };
                
        if(typeof(store["params"]["showType"])!="undefined"&&layui.utils.inArray(store["params"]["showType"],["15"])){
            listType=3;
        };
        
        if(typeof(store["params"]["showType"])!="undefined"&&layui.utils.inArray(store["params"]["showType"],["48"])){
            listType=6;
        }; 

  
        var order_product_list=layui.bill_cols.order_product_list(listType);
        var that=this;
        
        var where={opt:"get_product_list",orderListIds:store["data"]["orderListIds"],bill_id:bill_id,bill_product:true,billId:provebill_id,limit:999};
        
        if(!layui.utils.isEmpty(store['params']['change_type'])){
            where["change_type"]=store['params']['change_type'];
        }
        if(store["dataModel"]["billInfo"]["rd_id"] > 0){
            return layui.table.render({elem: '#tab3', url: layui.utils.urlData['provebill'],cols:order_product_list
            , where:where
            ,toolbar: '<div>产品列表</div>'
            ,totalRow: true //开启合计行
            ,loading:false
           /* , initSort: {
                field: 'tuopan_group', //排序字段，对应 cols 设定的各字段名
                type: 'asc' //排序方式  asc: 升序、desc: 降序、null: 默认排序
            }*/
            // ,parseData: function(res){ 
            //     return {"code": res.code,"msg": res.msg, "count": res.count, "data": res.data };
            // }
            
        });
        }else{
            return layui.table.render({elem: '#tab3', url: layui.utils.urlData['provebill'],cols:order_product_list
            , where:where
            ,toolbar: '<div>产品列表</div>'
            ,totalRow: true //开启合计行
            ,loading:false
           /* , initSort: {
                field: 'tuopan_group', //排序字段，对应 cols 设定的各字段名
                type: 'asc' //排序方式  asc: 升序、desc: 降序、null: 默认排序
            }*/
            ,parseData: function(res){ 
                
                //默认选中判断
                if(typeof(res.data)!='undefined'&&res.data&&layui.utils.jsonEncode(store["data"]["orderLists"])!="{}"){
                    
                   
                    for(var index in res.data){ 

                        
                        if(typeof(store["data"]['tab3Ids'])!="undefined"){
                            if(store["data"]['tab3Ids'].indexOf(res.data[index].id)>-1){
                                res.data[index]['LAY_CHECKED']=true;
                            }
                        }
                        
                        
                        
                        var storeProduct=store["data"]["orderLists"]["key_"+res.data[index]['id']];
                       
                        store["data"]["orderLists"]["key_"+res.data[index]['id']]["org_box_num"] =  store["data"]["orderLists"]["key_"+res.data[index]['id']]["box_num"];
                       
                        //if(storeProduct["box_num"]){}
             
                        if(Number(storeProduct["num"])<Number(res.data[index]['poUantity'])||!layui.utils.isEmpty(store["data"]['source_order_list'])){
                            
                            res.data[index]['tuoPan'] =storeProduct["tuoPan"]?storeProduct["tuoPan"]:res.data[index]['tuoPan'];
                            res.data[index]['is_apportion'] =storeProduct["is_apportion"]?storeProduct["is_apportion"]:res.data[index]['is_apportion'];
                            res.data[index]['apportion_price'] = storeProduct["apportion_price"]?storeProduct["apportion_price"]:res.data[index]['apportion_price'];
                            res.data[index]['apportion_total'] = storeProduct["apportion_total"]?storeProduct["apportion_total"]:res.data[index]['apportion_total'];
                            res.data[index]['total_1'] = storeProduct["total_1"]?storeProduct["total_1"]:res.data[index]['total_1'];
                            res.data[index]['mz_1'] = storeProduct["mz_1"]?storeProduct["mz_1"]:res.data[index]['mz_1'];
                            res.data[index]['jz_1'] = storeProduct["jz_1"]?storeProduct["jz_1"]:res.data[index]['jz_1'];
                            res.data[index]['tj_1'] = storeProduct["tj_1"]?storeProduct["tj_1"]:res.data[index]['tj_1'];
                            res.data[index]['num'] = storeProduct["num"]?storeProduct["num"]:res.data[index]['num'];
                            res.data[index]['poPrice'] = storeProduct["poPrice"]?storeProduct["poPrice"]:res.data[index]['poPrice1'];
                            
                            res.data[index]['sample_num1'] = storeProduct["sample_num1"]?storeProduct["sample_num1"]:res.data[index]['sample_num1'];
                            res.data[index]['sample_num2'] = storeProduct["sample_num2"]?storeProduct["sample_num2"]:res.data[index]['sample_num2'];

                            res.data[index]['box_num'] = storeProduct["box_num"]?storeProduct["box_num"]:res.data[index]['box_num']; 
                            res.data[index]['tuopan_num'] = storeProduct["id"]?storeProduct["tuopan_num"]:res.data[index]['tuopan_num'];
                            res.data[index]['tuopan_weight'] = storeProduct["id"]?storeProduct["tuopan_weight"]:res.data[index]['tuopan_weight'];
                            
                            
                            res.data[index]['tuopan_group'] = storeProduct["tuopan_group"]?storeProduct["tuopan_group"]:res.data[index]['tuopan_group'];
                            res.data[index]['tuopan_size'] = storeProduct["tuopan_size"]?storeProduct["tuopan_size"]:res.data[index]['tuopan_size'];
                            res.data[index]['tuopan_display'] = storeProduct["tuopan_display"]?storeProduct["tuopan_display"]:res.data[index]['tuopan_display'];
                        }else{
                            
                            res.data[index]['num']=storeProduct["num"]=res.data[index]['poUantity'];
                        }
                        
                        
                        if(!layui.utils.isEmpty(store["params"]["opt"])&&store["params"]["opt"]=="change_audit"){
                            res.data[index]["is_apportion"]=0;
                        }
                        
                       
                    }
                
                } 
                
                return {"code": res.code,"msg": res.msg, "count": res.count, "data": res.data };
            }
            ,done: function(res, curr, count){
               
                if(res.data){
                    layui.$("#tab3 + div .layui-table-box tr td").attr({"style": "background:#f8f8f8",});
                    layui.$("#tab3 + div .layui-table-box tr td[data-edit=text]").attr({"style": "background:#fff"});
                    store["data"]['brands']=[]; 
                    store["data"]['order_nums']=[];

                    var tableData=layui.table.cache["tab3"];
                  
                    //分组数量统计
                    var groupNums={};
                    for(var i in tableData){
                        if(tableData[i]["tuopan_group"]){
                            if(!groupNums[tableData[i]["tuopan_group"]]){
                                groupNums[tableData[i]["tuopan_group"]]={"id":tableData[i]["id"],"num":0};                               
                            } 
                            groupNums[tableData[i]["tuopan_group"]]["num"]=groupNums[tableData[i]["tuopan_group"]]["num"]+1;
                        }
                    }

                    for(var index in tableData){
                        
                        var item=tableData[index];
                        
                        
                        if(store["data"]["user_runpriv"]){
                            if(item["complete"]!=store["data"]["user_runpriv"]["complete"]||!layui.utils.inArray(item["category"],store["data"]["user_runpriv"]["category"])){
                                layui.$("#tab3 + div tr[data-index='" + index + "'] td").attr({"style": "background:#BFBFBF"});
                            }
                        }

                        //退回样式
                        if(layui.utils.inArray(item.id,store["data"]['tab3Ids'])&&store["params"]["showType"]!=24){
                            layui.$("#tab3 + div tr[data-index='" + index + "']").attr({"style": "color:red"});
                        }
                        
                        //全局存储方案赋值
                        store["data"]["orderLists"]["key_"+item['id']]["tuoPan"]=item["tuoPan"];
                        store["data"]["orderLists"]["key_"+item['id']]["is_apportion"]=item["is_apportion"];
                        store["data"]["orderLists"]["key_"+item['id']]["apportion_price"]=item["apportion_price"];
                        store["data"]["orderLists"]["key_"+item['id']]["apportion_total"]=item["apportion_total"];
                        store["data"]["orderLists"]["key_"+item['id']]["order_num"]=item["orderNum"];
                        store["data"]["orderLists"]["key_"+item['id']]["num"]=item["num"];
                        store["data"]["orderLists"]["key_"+item['id']]["old_box_num"]=item["box_num"];
                        store["data"]["orderLists"]["key_"+item['id']]["poPrice"]=item["poPrice1"];
                        
                        
                        
                        //alert(store["data"]["orderLists"]["key_"+item['id']]["poPrice"]);
                       
                        store["data"]["orderLists"]["key_"+item['id']]["sample_num1"]=item["sample_num1"];
                        store["data"]["orderLists"]["key_"+item['id']]["sample_num2"]=item["sample_num2"];
                        
                        store["data"]["orderLists"]["key_"+item['id']]["total_1"]=item["total_1"]?item["total_1"]:item["total"];
                        store["data"]["orderLists"]["key_"+item['id']]["mz_1"]=item["mz_1"]?item["mz_1"]:item["mz"];
                        store["data"]["orderLists"]["key_"+item['id']]["jz_1"]=item["jz_1"]?item["jz_1"]:item["jz"];
                        store["data"]["orderLists"]["key_"+item['id']]["tj_1"]=item["tj_1"]?item["tj_1"]:item["tj"];
                        store["data"]["orderLists"]["key_"+item['id']]["box_num"]=item["box_num"]?item["box_num"]:item["box_num"];
                
                        
                        store["data"]["orderLists"]["key_"+item['id']]["tuopan_num"]=item["tuopan_num"];
                        store["data"]["orderLists"]["key_"+item['id']]["tuopan_weight"]=item["tuopan_weight"]; 
                        store["data"]["orderLists"]["key_"+item['id']]["tuopan_group"]=item["tuopan_group"];
                        store["data"]["orderLists"]["key_"+item['id']]["tuopan_size"]=item["tuopan_size"]; 
                        store["data"]["orderLists"]["key_"+item['id']]["tuopan_display"]=item["tuopan_display"];  
                        store["data"]["orderLists"]["key_"+item['id']]["tuopan_single_weight"]=item["tuopan_single_weight"]; 
                        
                        
            
                        store["data"]['brands'].push(item["brand"]);
                        
                        if(!layui.utils.inArray(item["orderNum"],store["data"]['order_nums'])){
                            store["data"]['order_nums'].push(item["orderNum"]);
                        }
                        
                        
                        /*if(item["no_tuopan"]){
                            layui.$("#tab3 + div tr[data-index='" + index + "']  ").attr({"style": "color:#FFB800"});
                        }*/
                        
                        
                        //分组算法
                        var mergeCols=["tuopan_num","tuopan_weight"];
                        if(item["tuopan_group"]){
                           
                            for(var i in mergeCols){
                                if(groupNums[item["tuopan_group"]]["id"]==item["id"]){
                                 
                                    layui.$("#tab3 + div tr[data-index='" + index + "'] td[data-field="+mergeCols[i]+"] ").attr({"rowspan": groupNums[item["tuopan_group"]]["num"]});
                                }else{
                                    
                                    layui.$("#tab3 + div tr[data-index='" + index + "'] td[data-field="+mergeCols[i]+"] ").attr({"style": "display:none"});
                                }  
                            }
                        }
                        
                        if(!layui.utils.isEmpty(store["data"]['source_order_list'])){
                            var item1=store["data"]['source_order_list']["key_"+item["id"]];
                            var item2=store["data"]["orderLists"]["key_"+item['id']];
                            
                            if(!layui.utils.isEmpty(item1)){
                                for(var key in item1){
                                    if(typeof(item2[key])=="undefined") continue;
                                    
                                    if (item1[key] != item2[key]) {
                                        var fieldObj=layui.$("#tab3 + div tr[data-index='" + index + "'] td[data-field="+key+"] ");
                                        fieldObj.attr({"style": "color:red"});
                                        fieldObj.click(function () {//绑定点击事件
                                            var id = layui.$(this).parents("tr").find(" td[data-field='id'] div ").html();
                                            var nameKey = layui.$(this).attr("data-field");
                                            layer.tips("原值为：" + item2[nameKey], this, {tips: [1, '#000'], time: 3000});
                                        })
    
                                    }
                                }
                            }
                            
                        }
                        
                        
                    } 
                    
                    layui.$("#orderNum").val(store["data"]['order_nums'].join(","));
                }
                
                that.checkOrderChange(store);
                that.initSelect(store);
            }
        }); 
        }
    },
    
    
    initTab4:function(store){console.log(store);
        
        //return layui.table.render({elem: '#tab4', data:store["data"]['cabinetNum'],cols:layui.bill_cols['cabinet_num'] ,toolbar: '<div>柜量信息</div>'});
        
        if(!layui.utils.isEmpty(store.data.orderIds&&store.params.new==1)){//获取选中订单柜型
            var retInfo=layui.utils.post('get_order_by_ids',{ids:layui.utils.removeNull(store.data.orderIds)});//提交表单数据请求操作
            console.log(store["data"]['cabinetNum']);
            store["data"]['cabinetNum']= [retInfo];
            store["dataModel"]["cabinetNum"]= retInfo;
        }

        //console.log(store["data"]['cabinetNum']);
        var tab4=layui.table.render({elem: '#tab4', data:store["data"]['cabinetNum'],cols:layui.bill_cols['cabinet_num'] ,toolbar: '<div>柜量信息</div>'});
        //初始化事件
        /*setTimeout(function(){
            //修改产品信息
            layui.table.on('edit(tab4)', function(obj){
                store["dataModel"]["cabinetNum"]=obj.data;
            });
        },500);*/
        
        return tab4;
    },


      initorder:function(store){console.log(store);

          if(layui.utils.isEmpty(store["data"]['order_attachment'])){
              store["data"]['order_attachment']=[];
          }
          if(!layui.utils.isEmpty(store.data.orderIds)){//获取选中订单柜型  &&store.params.new==1
              //console.log(layui.utils.removeNull(store.data.orderIds));
              var retInfo=layui.utils.post('get_order_data_by_ids',{ids:layui.utils.removeNull(store.data.orderIds)});//提交表单数据请求操作
              //console.log(retInfo);
              /*if(!layui.utils.isEmpty(retInfo)){
                  retInfo = JSON.parse(retInfo)
              }*/
              if(!layui.utils.isEmpty(retInfo)){
                  console.log(retInfo);
                  for (var index in retInfo) {
                      var item = retInfo[index];
                      if(!layui.utils.isEmpty(item)){
                          ret = JSON.parse(item);
                          if(!layui.utils.isEmpty(ret)){
                              for(var ind in ret){
                                  store["data"]['order_attachment'].push(ret[ind]);
                              }
                          }

                      }


                  }
                  store["data"]['order_attachment']=JSON.stringify(store["data"]['order_attachment']);
              }

              layui.utils.initUpload("attachment",store["data"]['order_attachment'],"",true);
              console.log(store["data"]['order_attachment']);

          }

          return store;
      },
   

    initTab5:function(store){
        return layui.table.render({elem: '#tab5', data:store["data"]['cabinetNum'],cols:layui.bill_cols['cabinet_num'] ,toolbar: '<div>柜量信息</div>'});
    },
    
    initTab6: function (store){
        if(layui.utils.isEmpty(store["data"]["spells_info"])) return false;
        store["data"]["spells_info"]["spell_list"]=JSON.parse(store["data"]["spells_info"]["spell_list"]);
        return layui.table.render({elem: '#tab6', data:store["data"]["spells_info"]["spell_list"],cols:layui.customer_cols["spell_list"],totalRow: true,toolbar: '<div>拼货信息</div>'});
    },
    //初始化退货单引用的发货单列表,自动生成的发货单不算
    initDeliverTab: function (store){
        var customer_code=store["dataModel"]["billInfo"]["customer_code"];
        
        if(typeof(store["data"]["accountCtr"])!="undefined"&&store["data"]["accountCtr"]['customer_code']){
            customer_code=store["data"]["accountCtr"]['customer_code'];
        }
        var show_order_ids=!layui.utils.isEmpty(store["data"]["orderIds"])?store["data"]["orderIds"]:[];
        var that=this;
        //初始化订单列表
        return  layui.table.render({elem: '#tab1',url: layui.utils.urlData['provebill'], cols: layui.bill_cols['bill_deliver'] 
            ,loading:true
            ,toolbar: '<div>发货单列表</div>'
            ,toolbar: true
            ,text: {  none: '暂无相关数据'}
            ,where:{opt:"bill_deliver_list", cCusCode:customer_code,view_type:1,show_order_ids:show_order_ids,is_auto:0,rd_complete:0}
            ,page: true
            ,parseData: function(res){ 
                return {"code": res.code,"msg": res.msg, "count": res.count, "data": res.data };
            }
            ,done: function(res, curr, count){
                that.initloadCtr(store)//初始化贸易公司
                that.initTab2(store);
                
            }
        });
        
    },
    
    //初始化发货单下面的产品列表
    initDeliverProductsTab: function (store){
        var that=this;
        // 初始化订单列表
        return  layui.table.render({elem: '#tab2',url: layui.utils.urlData['provebill'], cols: layui.bill_cols['bill_deliver_products'] 
            ,loading:true
            ,toolbar: '<div>产品列表</div>'
            ,toolbar: true
            ,text: {  none: '暂无相关数据'}
            ,where:{opt:"init_deliver_product_list", "deliver_id":store["data"]['deliver_id']}
            ,page: true
            ,parseData: function(res){ 
                return {"code": res.code,"msg": res.msg, "count": res.count, "data": res.data };
            }
            ,done: function(res, curr, count){
            }
        });
        
    },
    //选中的发货单下面的产品列表
    checkedDeliverProductsTab: function (store){
        var where = {opt:"init_deliver_product_list","orderListIds":store["data"]["orderListIds"],"deliver_id":store["data"]['deliver_id']} ;
        // 初始化订单列表
        return  layui.table.render({elem: '#deliverProductsTab',url: layui.utils.urlData['provebill'], cols: layui.bill_cols['bill_deliver_products'] 
            ,loading:true
            ,toolbar: '<div>产品列表</div>'
            ,toolbar: true
            ,text: {  none: '暂无相关数据'}
            ,where:where           
            ,page: true
            ,parseData: function(res){ 
                if(res.data instanceof Array){
                    res.data.forEach(function(item,index){
                        store["data"]["orderLists"][item.id]=item;
                        store["data"]["orderLists"][item.id]["js"]= item.js; 
                        store["data"]["orderLists"][item.id]["je"]= item.je;
                        store["data"]["orderLists"][item.id]["sl"]= item.sl; 
                    })
                }
                return {"code": res.code,"msg": res.msg, "count": res.count, "data": res.data };
            }
            ,done: function(res, curr, count){
                
            }
        });
        
    },
    rebackDeliverInfo:function(id){
        retInfo=layui.utils.get('get_rd_detail',{id:id});//提交表单数据请求操作
        return retInfo;
    },
    //详情页退货产品列表
    rebackDeliverProductsTab: function (store){
        console.log(store);
        var where = {opt:"get_deliver_product_list","deliver_id":store['params']['deliver_id'],"rd_id":store['params']['id']} ;
        // 初始化订单列表
        return  layui.table.render({elem: '#deliverProductsTab',url: layui.utils.urlData['provebill'], cols: layui.bill_cols['bill_deliver_products'] 
            ,loading:true
            ,toolbar: '<div>产品列表</div>'
            ,toolbar: true
            ,text: {  none: '暂无相关数据'}
            ,where:where           
            ,page: true
            ,parseData: function(res){
                
                // if res.data is array
                if(res.data instanceof Array){
                    res.data.forEach(function(item,index){
                        store["data"]["orderLists"][item.id]=item;
                    })
                }
                return {"code": res.code,"msg": res.msg, "count": res.count, "data": res.data };
            }
            ,done: function(res, curr, count){

            }
        });
        
    },
    //初始化退货单下面的产品列表
    initRdProductsTab: function (store){
        // 初始化订单列表
        return  layui.table.render({elem: '#tab2',url: layui.utils.urlData['provebill'], cols:layui.bill_cols['product_list']
            ,loading:true
            ,toolbar: '<div>产品列表</div>'
            ,toolbar: true
            ,text: {  none: '暂无相关数据'}
            ,where:{opt:"get_rd_product_list", "deliver_id":store["data"]['deliver_id'], "rd_id":store["data"]['rd_id'],"bill_ids":store["data"]['provebill_id']}
            ,page: true
            ,parseData: function(res){
                return {"code": res.code,"msg": res.msg, "count": res.count, "data": res.data };
            }
            ,done: function(res, curr, count){
            }
        });
        
    },
    rdTab3: function (store){
        var that = this;
        // 退货单下面的产品列表
        return  layui.table.render({elem: '#rdTab3',url: layui.utils.urlData['provebill'], cols:layui.bill_cols['bill_deliver_products']
            ,loading:true
            ,toolbar: '<div>产品列表</div>'
            ,toolbar: true
            ,text: {  none: '暂无相关数据'}
            ,where:{opt:"get_deliver_product_list", "orderListIds":store["data"]['orderListIds'],"bill_ids":store["data"]['provebill_id'],"deliver_id":store["data"]['deliver_id'], "rd_id":store["data"]['rd_id'],"act":"add"}
            ,page: true
            ,parseData: function(res){
                //默认选中判断
                if(typeof(res.data)!='undefined'&&res.data&&layui.utils.jsonEncode(store["data"]["orderLists"])!="{}"){
                    for(var index in res.data){ 
                        if(typeof(store["data"]['tab3Ids'])!="undefined"){
                            if(store["data"]['tab3Ids'].indexOf(res.data[index].id)>-1){
                                res.data[index]['LAY_CHECKED']=true;
                            }
                        }
                    }
                } 
                return {"code": res.code,"msg": res.msg, "count": res.count, "data": res.data }; 
            }
            ,done: function(res, curr, count){
            }
            });
    },
    
    rebackDeliverStat:function(store){
        // 初始化订单列表
        return  layui.table.render({elem: '#stat1',url: layui.utils.urlData['provebill'], cols:layui.bill_cols['product_list']
            ,loading:true
            ,toolbar: '<div>产品列表</div>'
            ,toolbar: true
            ,text: {  none: '暂无相关数据'}
            ,where:{opt:"get_rd_statistics"}
            ,page: true
            ,parseData: function(res){ 
                return {"code": res.code,"msg": res.msg, "count": res.count, "data": res.data };
            }
            ,done: function(res, curr, count){
            }
        });
    },
    

    saveCabinet:function (obj,store){
        delete obj.field["comment"];
        if(obj.field["load_type"]=="散货"&&layui.utils.isEmpty(obj.field["entry_num"])){
            return {"status":"500","msg":"进舱编号不得为空","data":{}};
        }
        if (!layui.utils.isEmpty(obj.field['lading_bill'])) {
            var lading_bill=JSON.parse(obj.field['lading_bill']);
        }
        if (!layui.utils.isEmpty(obj.field['origin_lading_bill'])) {
            var origin_lading_bill=JSON.parse(obj.field['origin_lading_bill']);
        }

        if (!layui.utils.isEmpty(lading_bill) || !layui.utils.isEmpty(origin_lading_bill)) {
            if(layui.utils.isEmpty(obj.field['real_ship_time'])){
                return {"status":"500","msg":"实际船期不得为空","data":{}};
            }

        }

        obj.field["cabinet_num"]= layui.utils.jsonEncode(store["dataModel"]["cabinetNum"]);//柜量赋值
    
        var retInfo=layui.utils.post('save_cabinet',obj.field);//提交表单数据请求操作
        return retInfo;
    },
    
    submitCabinet:function (obj,store){
        var that=this;
        var saveData=that.saveCabinet(obj,store);        
        if(saveData["status"]=="500") return saveData;
        
        var retInfo = layui.utils.post('submit_cabinet',{id:saveData["data"],parent_flow_id:store['params']['parent_flow_id']});//提交表单数据请求操作
        return retInfo;
    },
    


    saveData:function (obj,store){
        
        obj.field["confirmId"]=!layui.utils.isEmpty(store["params"]["confirmId"])?store["params"]["confirmId"]:0;
        
        if(layui.utils.jsonEncode(store["data"]["orderLists"]) == "{}"){
            layer.msg("您还没选择订单明细");
            return false;
        }
        
        if(obj.field.adjust_money&&parseFloat(obj.field.adjust_money)!=0&&!obj.field.adjust_name){
            /*layer.msg("请填写调账名称");
            layui.$("input[name=adjust_name]").focus();
            return false;*/
        }
        
        
        if(layui.utils.isEmpty(obj.field.invoice_num)){
            layer.msg("请选择发票号");
            layui.$("input[name=invoice_num]").focus();
            return false;
        }
        
                
        var chackData = layui.utils.post("check_data",{"field":"invoice_num","value":obj.field["invoice_num"],"id":obj.field["id"]});
        if(!chackData["data"]){
            layer.msg("发票号重复");
            return false;
        }
  
        if(!obj.field.customer_address){
            obj.field.customer_address=obj.field.customer_address1;
        }
        delete obj.field.customer_address1;
        if(!obj.field.customer_address){
            layer.msg("客户地址不得为空！");
            return false;
        }
        
        obj.field.customer_address=obj.field.customer_address.replace(/\'/g, '\`');
        console.log(store);
        obj.field["cabinet_num"]= layui.utils.jsonEncode(store["dataModel"]["cabinetNum"]);//柜量赋值
        obj.field["orderLists"]=store["data"]["orderLists"];//产品明细列表赋值
        
        obj.field["adjust_bill"]="";
        if(!layui.utils.isEmpty(store["dataModel"]["billInfo"]["adjust_bill"])){
            obj.field["adjust_bill"]= layui.utils.jsonEncode(store["dataModel"]["billInfo"]["adjust_bill"]);//柜量赋值
        }
        
        if(!layui.utils.isEmpty(store['params']['change_type'])){
            var typeMap={"1":"销售变更","2":"生产变更"};
            var change_type = store['params']['change_type'];
            obj.field["change_type"]=typeMap[change_type];
        }
        var current_this = this;
        var res_tep = layui.utils.post('ver_fal',obj.field);//提交表单数据请求操作
        if (res_tep.status === 500){
            layer.confirm(res_tep.msg, {icon: 3, title:'提示'}, function(index){
                var retInfo=layui.utils.post('add_provebill',obj.field);//提交表单数据请求操作
                if(!layui.utils.isEmpty(store["params"]["confirmId"])){
                    //layui.utils.sqlManage("provebill_confirm_update",{"id":store["params"]["confirmId"],"status":"已办理"});
                    layui.utils.post("provebill_confirm_update",{"id":store["params"]["confirmId"]});
                }
                current_this.storeSave(obj,store);//更新记忆存储
                layer.close(index);
                return retInfo;
            });
            return false;
        }
        var retInfo=layui.utils.post('add_provebill',obj.field);//提交表单数据请求操作
        if(!layui.utils.isEmpty(store["params"]["confirmId"])){
            //layui.utils.sqlManage("provebill_confirm_update",{"id":store["params"]["confirmId"],"status":"已办理"});
            layui.utils.post("provebill_confirm_update",{"id":store["params"]["confirmId"]});
        }
        current_this.storeSave(obj,store);//更新记忆存储
        return retInfo;
        //debugger;

    },
    
    /**
     * 更新记忆存储
     * @param {type} obj
     * @param {type} store
     * @returns {undefined}
     */
    storeSave:function(obj,store){
        if(!obj.field.customer_address) return false;
        //更新存储信息
        store["data"]["addressList"]=typeof(store["data"]["addressList"])!="undefined"?store["data"]["addressList"]:[];
        var hasAddress=false;
        for(var index in store["data"]["addressList"]){
            var item=store["data"]["addressList"][index];
            if(item.customer_address==obj.field.customer_address){
                store["data"]["addressList"][index]["start_port"]=obj.field["start_port"];
                store["data"]["addressList"][index]["end_port"]=obj.field["end_port"];
                store["data"]["addressList"][index]["customer_name"]=obj.field.customer_name,
                hasAddress=true;
            }
        }
        
        if(!hasAddress){
            store["data"]["addressList"].push({
                "start_port":obj.field["start_port"],
                "end_port":obj.field["end_port"],
                "customer_address":obj.field.customer_address,
                "customer_name":obj.field.customer_name,
            });
        }

        layui.utils.post("update_customer_address",{
            "customer_code":store["dataModel"]["billInfo"]["customer_code"],
            "customer_country":store["dataModel"]["billInfo"]["customer_country"],
            "customer_name":obj.field["customer_name"],
            "customer_short":store["dataModel"]["billInfo"]["customer_short"],
            "customer_address":store["data"]["addressList"]
        });
        
      
        
        
        
        
        var storeData=obj.field;
        delete storeData["id"];
        delete storeData["attachment"];
        delete storeData["cabinet_num"];//柜量赋值
        delete storeData["orderLists"];//产品明细列表赋值
        delete storeData["file"];
        
        storeData["addressList"]=store["data"]["addressList"];

        layui.utils.post("sql_manage_api",{"unique_code":"data_store_update",
            "data_type":1,
            "data_key":obj.field["customer_code"],
            "data_content":layui.utils.jsonEncode(storeData)
        });
        
    },
    
    /**
     * 分摊价格计算
     * @param {type} store 全局存储
     * @param {type} adjust_money 调账金额
     * @returns {unresolved}
     */
    apportionCompute:function (store,adjust_money){

        //var adjust_money=layui.
        if(layui.utils.isEmpty(adjust_money)){
            adjust_money=0;
            if(!layui.utils.isEmpty(store["dataModel"]["billInfo"]["adjust_money"])){
                adjust_money=store["dataModel"]["billInfo"]["adjust_money"];
            }
        }
        var apportion_total=0;
        for(var index in store["data"]["orderLists"]){
            
           
            var item=store["data"]["orderLists"][index];
            
           
            item.total_1=(Number(item.poPrice)*Number(item.num));
            item.total_1=Math.round(item.total_1*100)/100;
            store["data"]["orderLists"][index]["apportion_price"]=0;
            store["data"]["orderLists"][index]["apportion_total"]=item.total_1?item.total_1:0;
            if(item.is_apportion==1) apportion_total+= parseFloat(store["data"]["orderLists"][index]['total_1']);           
        }
        
        
      
        if(apportion_total){
            for(var i in store["data"]["orderLists"]){
                var item=store["data"]["orderLists"][i];
                item.total_1=item.poPrice*item.num;
                item.total_1=Math.round(item.total_1*100)/100;
                if(item.is_apportion==1){
                    var apportion_price=((item.total_1/apportion_total)*adjust_money); 
                    store["data"]["orderLists"][i]["apportion_price"]=Math.round(apportion_price*100)/100;
                    
                    var temp=Number(item.total_1)+Number(apportion_price);
                    store["data"]["orderLists"][i]["apportion_total"]=Math.round(temp*100)/100;  
                    
                    
                   
                }             
            }
        }
        return store;
    },
    
    /**
     * save change bill
     * @param {type} obj
     * @param {type} store
     * @returns {unresolved}
     */
    billChangeSave:function(obj,store){
        if(!obj.field.customer_address){
            obj.field.customer_address=obj.field.customer_address1;
        }
        delete obj.field.customer_address1;
        if(!obj.field.customer_address){
            layer.msg("客户地址不得为空！");
            return false;
        }
        this.storeSave(obj,store);//更新记忆存储
        
        var id=typeof(store['params']['id'])!="undefined"?store['params']['id']:0;  //获取页面参数  
        obj.field["attachment"]=obj.field["attachment"]?JSON.parse(obj.field["attachment"]):{};
        obj.field["cabinet_num"]= store["dataModel"]["cabinetNum"];//柜量赋值
        
        console.log(store["dataModel"]["cabinetNum"]);
        
        obj.field["orderLists"]=store["data"]["orderLists"];//产品明细列表赋值
        obj.field["adjust_bill"]=store['dataModel']['billInfo']['adjust_bill'];
        
        var saveData={id:id,"flowType":"订舱变更",data2:layui.utils.jsonEncode(obj.field)};
        
        if(!layui.utils.isEmpty(store["params"]["change_flow_id"])){
            saveData["flow_id"]=store["params"]["change_flow_id"];
        }
       // console.log(saveData);return false;
  
        var retInfo=layui.utils.post('change_save',saveData);//提交表单数据请求操作
        return retInfo;
    },
    
    
    /**
     * save cabinet change bill
     * @param {type} obj
     * @param {type} store
     * @returns {unresolved}
     */
    cabinetChangeSave:function(obj,store){
        var cabinetInfo=store["dataModel"]["cabinetInfo"];
        if(typeof(cabinetInfo['id'])=="undefined") return false; 
        delete obj.field["comment"];
        obj.field["cabinet_num"]= store["dataModel"]["cabinetNum"];//柜量赋值
        var retInfo=layui.utils.post('change_save',{id:cabinetInfo['id'],"flowType":"装柜变更",data2:layui.utils.jsonEncode(obj.field)});//提交表单数据请求操作
        return retInfo;
    },
   
    /**
     * 初始化变更数据
     * @param {type} store
     * @returns {undefined}
     */
    initChangeData: function(store){console.log(store)
        if(typeof(store['data']['change_bill_info'])!="undefined"){
            var billInfo=store['data']['change_bill_info'];
            
            //调账信息
            store['dataModel']['billInfo']['adjust_bill']=billInfo['adjust_bill'];
            layui.adjust_bill.show(store,"['dataModel']['billInfo']['adjust_bill']","AdjustBill")//渲染调账组件

            this.data.dataInfo['bill_info']['certificate_type'] = billInfo.certificate_type;            

            var formInfo=layui.form.val("form-bill");//表单赋值
            
            //初始化上传控件
            var uploadArray=["ship_doc"];
            for(var index in uploadArray) {
                var uploadStr=store['data']['change_bill_info'][uploadArray[index]];
                uploadStr=(typeof(uploadStr)!="undefined")?uploadStr:"";
                layui.utils.initUpload(uploadArray[index],uploadStr);  
            }
            //原数据比对修改后的值
            //delete formInfo["customer_address"];
            delete formInfo["customer_address1"];
            delete formInfo["attachment"];
            var updateInfo=this.setFormTips(formInfo,billInfo)
            layui.form.val("form-bill",updateInfo);//重新赋值
        }else if(typeof(store['data']['change_cabinet_info'])!="undefined"){
            var formInfo=layui.form.val("form-cabinet");//表单赋值
            var updateInfo=this.setFormTips(formInfo,store['data']['change_cabinet_info'])
            layui.form.val("form-cabinet",updateInfo);//重新赋值
        }else if(!layui.utils.isEmpty(store['params']['real_ship_time'])){
            var formInfo=layui.form.val("form-cabinet");//表单赋值
            var showInfo={
                "origin":{"real_ship_time":store['params']['real_ship_time']},
                "change":{"real_ship_time":formInfo["real_ship_time"]},   
            };

            var updateInfo=this.setFormTips(showInfo["origin"],showInfo["change"]);
            //layui.form.val("form-cabinet",updateInfo);//重新赋值
        }else if(!layui.utils.isEmpty(store['data']['change_bill_log'])){
            var formInfo=layui.form.val("form-bill");//表单赋值
            var updateInfo=this.setFormTips(store['data']['change_bill_log']["data1"],formInfo);
            layui.form.val("form-cabinet",updateInfo);//重新赋值
        }
        
        
    },
    
    setFormTips: function(formInfo,changeInfo){
        var updateInfo={};
        for (var index in formInfo) {
            if (formInfo[index] != changeInfo[index]) {
                var curObj = layui.$("input[name="+index+"],select[name="+index+"],textarea[name="+index+"]").parents(".layui-input-inline,.layui-input-block");
                curObj.addClass("red");
                curObj.click(function () {//绑定点击事件
                    var nameKey = layui.$(this).find("input,select,textarea").attr("name");
                    layer.tips("原值为：" + formInfo[nameKey], this, {tips: [1, '#000'], time: 3000});
                })
                updateInfo[index]=changeInfo[index];
            }
        }
        return updateInfo;       
    },
    
    initSelectInput1:function(store){
        
        var customer_code="";
        var showList=[{value:"无",name:"无"}],endPorts=[];
        if(!layui.utils.isEmpty(store["dataModel"]["billInfo"]["customer_code"])){
            customer_code=store["dataModel"]["billInfo"]["customer_code"];
            var dataInfo=layui.utils.sqlManage("customer_address_list",{"customer_code":customer_code});
          
            if(!layui.utils.isEmpty(dataInfo["data"])){
                for(var index in dataInfo["data"]){
                    var item=dataInfo["data"][index]; 
                    if(!layui.utils.isEmpty(item.end_port_list)){
                        item.end_port_list=item.end_port_list.replace(/\t/g, ""); 
                    }

                    var end_ports=JSON.parse(item.end_port_list);
                    if(!layui.utils.isEmpty(end_ports)&&!layui.utils.inArray(item.end_port,endPorts)){ 
                        for(var key in end_ports){
                            showList.push({value:end_ports[key], name:end_ports[key]});
                            endPorts.push(end_ports[key]);
                        } 
                    }
                }
            }              
        }
        
       
        var endPort= !layui.utils.isEmpty(store["data"]['orderEndPort'])?store["data"]['orderEndPort'][0]:"";
        var ins2 = layui.selectInput.render({
            elem: '#end_port',
            data:showList,
            placeholder: '请输入名称',
            name: 'end_port',
            initValue:endPort, // 渲染初始化默认值
            remoteSearch: false,
        });
        
        layui.$("input[name='end_port']").click(function(){
           layui.$(this).parents("#end_port").find(".layui-form-selectInput dl,.layui-form-selectInput dd").css("display","block");
        });
        
        return ins2;
    },
    
    saveOrderChange:function(store){
        var tableData=layui.table.cache["tab3"];
        var optLock=false;
        for(var index in tableData){
            var item=tableData[index];
            var obj=layui.$("#tab3 + div tr[data-index='" + index + "'] ");
            if(Number(item["num"])<Number(item["org_num"])){
                obj.attr({"style": "color:red"});
                //layer.tips("订舱数量小于总数量，请手动修改后保存打印" , obj, {tips: [1, '#000'], time: 3000});
                optLock=true;
            }else{
                obj.attr({"style": "color:#666"});
            }
        }
        
        if(optLock){
            layer.msg("有产品订舱数量小于总数量，请手动修改订舱数量后再保存打印！");
            return false;
        }
        return true;
    },
    
    //回调更新函数
    callUpdate:function(store){

        var adjustMoney=0; 
        var adjustInfo={"adjust_money":0,"adjust_name":"","acut_adj_type":""}
        if(!layui.utils.isEmpty(store['dataModel']['billInfo']['adjust_bill'])){
            for(var index in store['dataModel']['billInfo']['adjust_bill']){
                var item=store['dataModel']['billInfo']['adjust_bill'][index];
                adjustInfo["adjust_money"]+= Number(item.adjust_money); 
                adjustInfo["adjust_name"]+=item.adjust_name+",";
                adjustInfo["acut_adj_type"]+=item.acut_adj_type+",";
            }
        }

        layui.form.val("form-bill", adjustInfo);//表单数据更新
        store=this.apportionCompute(store,adjustInfo["adjust_money"])//计算调账金额
        layui.$("table").find("tr").click();//伪造点击事件更新表格每一列数据

      
    },
   
     
     
     
    tab3_edit:function (store,obj){        
        var that=this;
        var $onum=obj.data['onum']?obj.data['onum']:1;
        var $huanSuanLv=obj.data['huanSuanLv']?obj.data['huanSuanLv']:1;
        if(layui.utils.inArray(obj.field,["num","box_num"])){
            var box_num;var num=0;
            if(obj.field=="num"){
                if(parseInt(obj.value)>parseInt(obj.data.poUantity)) obj.value=parseInt(obj.data.poUantity); 
                num=parseInt(obj.value);
                box_num=store["data"]["orderLists"]["key_"+obj.data.id]["box_num"];
                
                if(obj.data.hz==0){
                    box_num=parseInt(obj.value);
                    if((obj.data.complete=="成品"||(obj.data.complete=="半成品"&&obj.data.category=="PVC胶带"))){
                        if(Number(obj.data['onum'])>0){
                            box_num=(Number(obj.value)*Number($huanSuanLv))/Number($onum);
                        }else{
                            box_num=Number(obj.value);
                        }
                    } 
   
                }else{
                    box_num=num/parseFloat(obj.data.onum);
                }
              
                //小数判断
                if(box_num%1!=0){
                    //box_num=store["data"]["orderLists"]["key_"+obj.data.id]["box_num"];
                    box_num=Math.ceil(box_num);
                }
                
                
                if(num==obj.data.org_num){
                    box_num=obj.data.boxNum;
                    
                }
                
                
  
            }else if(obj.field=="box_num"){
                
                
                if(parseInt(obj.value)>parseInt(obj.data.boxNum)) obj.value=parseInt(obj.data.boxNum);
                box_num=parseInt(obj.value);
                num=store["data"]["orderLists"]["key_"+obj.data.id]["num"];
              
                if(store["data"]["orderLists"]["key_"+obj.data.id]["box_num"]%1==0){
                    if(obj.data.hz==0){
                        num=(obj.data.complete=="成品")?(parseInt(obj.value)*$onum/$huanSuanLv):parseInt(obj.value);
                    }else{
                        num=box_num*parseFloat(obj.data.onum);
                       
                    }
                }
                
            }
           
            store["data"]["orderLists"]["key_"+obj.data.id]["num"]=num;
            store["data"]["orderLists"]["key_"+obj.data.id]["box_num"]=box_num;//实际箱数
            
            //计算半成品毛重净重 合同数*单位毛净体
            var old_box_num = parseInt(store["data"]["orderLists"]["key_"+obj.data.id]["old_box_num"]);
            if(layui.utils.isEmpty(old_box_num)){
                old_box_num = parseInt(obj.data.box_num);
            }
            store["data"]["orderLists"]["key_"+obj.data.id]["mz_1"]=box_num*(parseFloat(obj.data.mz)/old_box_num);
            store["data"]["orderLists"]["key_"+obj.data.id]["jz_1"]=box_num*(parseFloat(obj.data.jz)/old_box_num);
            store["data"]["orderLists"]["key_"+obj.data.id]["tj_1"]=box_num*(parseFloat(obj.data.tj)/old_box_num); 
            //store["data"]["orderLists"]["key_"+obj.data.id]["total_1"]=parseInt(num)*(parseFloat(obj.data.total)/parseInt(obj.data.poUantity));
            store["data"]["orderLists"]["key_"+obj.data.id]["total_1"]=(parseInt(num)*parseFloat(obj.data.poPrice)).toFixed(2);
    
        }
        
        if(layui.utils.inArray(obj.field,["c_dwtj","c_dwmz","c_dwjz"])){
            box_num=store["data"]["orderLists"]["key_"+obj.data.id]["box_num"];
            var arrayMap={"c_dwtj":"tj_1","c_dwmz":"mz_1","c_dwjz":"jz_1"}
            store["data"]["orderLists"]["key_"+obj.data.id][arrayMap[obj.field]]=box_num*parseFloat(obj.value);

        }
        
        
        if(layui.utils.inArray(obj.field,["poPrice"])){
            store["data"]["orderLists"]["key_"+obj.data.id]["total_1"]=(parseInt(obj.data.num)*parseFloat(obj.data.poPrice)).toFixed(2);

        }
      
        //托盘重量
        if(layui.utils.inArray(obj.field,["tuopan_num","tuopan_weight"])){
            if(obj.field=="tuopan_num"){
                store["data"]["orderLists"]["key_"+obj.data.id]["tuopan_weight"]=parseInt(obj.value)*parseFloat(obj.data.tuopan_single_weight);
            }
        }
        
        if(layui.utils.inArray(obj.field,["tuopan_size","tuopan_display"])){
            store["data"]["orderLists"]["key_"+obj.data.id][obj.field]=obj.value;
        }
        store["data"]["orderLists"]["key_"+obj.data.id][obj.field]=obj.value;

        that.apportionCompute(store);
        that.productSum(store);//计算总数
        /*setTimeout(function(){
            layui.$("table").find("tr").click();//伪造点击事件更新表格每一列数据
        },100);*/
        
        //if(utils.inArray(obj.field,["num","box_num","tuopan_num","tuopan_weight"])) tab3.reload();//数量修改刷新表格
    },
     
    rd_tab3_edit:function (store,obj){
        var $onum=obj.data['onum']?obj.data['onum']:1;
        var $huanSuanLv=obj.data['huanSuanLv']?obj.data['huanSuanLv']:1;
        if(layui.utils.inArray(obj.field,["js"])){
            var box_num;var num=0;
            if(obj.field=="js"){
                var oldtext = obj.data.remain_deliver_box;
                if(Math.abs(parseFloat(obj.value)) > oldtext){
                    layer.msg("件数不能大于剩余可退货件数");
                    obj.tr.find('td[data-field=js] input').val(oldtext);
                    obj.update({ js: oldtext });
                    return false;     
                }          
                box_num=obj.value;
                if(obj.data.hz==0){
                    num=(obj.data.complete=="成品")?(parseInt(obj.value)*$onum/$huanSuanLv):parseInt(obj.value);
                }else{
                    num=box_num*parseFloat(obj.data.onum);
                }
            }
            var iBili = box_num/obj.data.remain_deliver_box;
           
            store["data"]["orderLists"]["key_"+obj.data.id]["num"]=num;
            var sl = iBili*parseFloat(obj.data.sl);
            sl = Math.ceil(sl);
            store["data"]["orderLists"]["key_"+obj.data.id]["sl"]=sl;
            store["data"]["orderLists"]["key_"+obj.data.id]["box_num"]=box_num;//实际箱数
            store["data"]["orderLists"]["key_"+obj.data.id]["je"]=(parseInt(num)*parseFloat(obj.data.poPrice1)).toFixed(2);
            store["data"]["orderLists"]["key_"+obj.data.id][obj.field]=obj.value;
            obj.update(store["data"]["orderLists"]["key_"+obj.data.id]);
            layui.$("table").find("tr").click();//伪造点击事件更新表格每一列数据
        }
        
        
        // if(utils.inArray(obj.field,["num","js"])) tab3.reload();//数量修改刷新表格
    },
    rebackDeliverTabEdit:function (obj){
        var newtext = Math.abs(obj.value);
        //如果是正数或者非数字
        if(!(obj.value < 0 || obj.value > 0)){
            layer.msg("只能为数字");
            return false;
        }
        //绝对值大小比较
        if(obj.field == 'js'){
            if(Math.abs(parseFloat(obj.value)) > Math.abs(obj.data.remain_deliver_box)){
                var selector = obj.tr.find('[data-field=' + obj.field + ']');
                var oldtext = layui.$(selector).text();
                layer.msg("件数不能大于原值");
                obj.tr.find('td[data-field=js] input').val(oldtext);
                obj.update({ 'js': oldtext });                
                return false;
            }
        }
        if(obj.field == 'sl'){
            if(Math.abs(parseFloat(obj.value))  > Math.abs(obj.data.remain_deliver_num)){
                var selector = obj.tr.find('[data-field=' + obj.field + ']');
                var oldtext = layui.$(selector).text();
                layer.msg("数量不能大于原值");
                obj.tr.find('td[data-field=sl] input').val(oldtext);
                obj.update({ 'sl': oldtext });    
                return false;
            }
        }
        if(obj.field == 'je'){
            if(Math.abs(parseFloat(obj.value))  > Math.abs(obj.data.remain_total_1)){
                var selector = obj.tr.find('[data-field=' + obj.field + ']');
                var oldtext = layui.$(selector).text();
                layer.msg("金额不能大于原值");
                obj.tr.find('td[data-field=je] input').val(oldtext);
                obj.update({ 'je': oldtext });                
                return false;
            }
        }
        //比例修改
        if(obj.field=="js"){
            var bili = parseFloat(newtext)/Math.abs(obj.data.remain_deliver_box);
            var newJs = newtext;
            var newSl = obj.data.sl*bili;
            var newJe = obj.data.je*bili;
            obj.update({'js':newJs,'sl':newSl,'je':newJe});
        }else{
            var newJs = obj.data.js;
            var newSl = obj.data.sl;
            var newJe = obj.data.je;
            obj.update({'js':newJs,'sl':newSl,'je':newJe});
        }
        layui.$("table").find("tr").click();//伪造点击事件更新表格每一列数据
    },
    
    
    checkOrderChange:function (store){
        var tableData=layui.table.cache["tab3"];
        var retInfo=true;

        var changeOderItems = layui.utils.post("get_change_order_item",{"confirmId":store["params"]["confirmId"]});
        //if(layui.utils.isEmpty(changeOderItems["data"])) return true;
        
        
        for(var index in tableData){
            var item=tableData[index];
            var obj=layui.$("#tab3 + div tr[data-index='" + index + "'] ");
            
            
            //alert(item["can_bill_num"]);
            //alert(item["num"]+"<"+item["org_num"]);
            if(!layui.utils.inArray(Number(item["id"]),changeOderItems["data"])) continue;

            if(Number(item["num"])<Number(item["org_num"])&&store['params']['opt']=="order_change"&&Number(item["can_bill_num"])<=0){
               
                var event_obj={"data":item,"field":"num","value":item["org_num"]}
                obj.attr({"style": "color:red"});              
            }else if(Number(item["num"])<Number(item["org_num"])&&store['params']['opt']=="order_change"&&Number(item["can_bill_num"])>0){
                
                if(!layui.utils.isEmpty(item["change_num"])&&Number(item["change_num"])==Number(item["can_bill_num"])){
                    var event_obj={"data":item,"field":"num","value":Number(item["num"])+Number(item["can_bill_num"])}
                    obj.attr({"style": "color:red"});   
                }else{
                    var event_obj={"data":item,"field":"num","value":Number(item["num"])}
                }
 
            }else{
                
                var event_obj={"data":item,"field":"num","value":item["num"]}
                
                if(Number(item["poPrice1"])!=Number(item["po_price"])||!layui.utils.isEmpty(item["is_red"])){
                    obj.attr({"style": "color:red"});     
                }else{
                    obj.attr({"style": "color:#666"});
                }
                
            }
            
            //构建事件对象
            this.tab3_edit(store,event_obj)
        }
        
        
        
        layui.$("table").find("tr").click();//伪造点击事件更新表格每一列数据
        
        if(store['params']['opt']=="order_change"){   
            this.apportionCompute(store)//计算调账金额(强制分摊)
            //自动
            if(!layui.utils.isEmpty(store["dataModel"]["provebill_confirm"])&&store["dataModel"]["provebill_confirm"]["status"]=="未办理"){
                setTimeout(function(){
                    layui.$("#save_order_change").click();
                },1000);
            }  
        }
        
        return retInfo;
    },
    
    exportPrint:function(field){
        var urlFmt = new layui.url_format.URL("");
        urlFmt.setParams(field);
  
        var url ="";
        if(field.batch=="1"){
            if(!field['print_types']){
                parent.layer.msg("请选择打印类型");
                return false;
            } 

            if(field["show_type"]==1){
                url ="/general/erp4/controller/provebill/excel/batchExcel.php"+urlFmt.url();
             
                parent.window.open(url,"_self");
            }else{
                var urlKey={1:"invoiceBill",2:"invoiceBill",3:"packingList",4:"packingList",5:"transportBill",6:"gateBill",7:"reportFactor"}
                var printTypes=field['print_types'].split(",");
                for(var index in printTypes){
                    urlFmt.set("flowId",0);
                    urlFmt.set("exportZip",1);
                    urlFmt.set("print_type",printTypes[index]);
                    url ="/general/erp4/controller/provebill/excel/"+urlKey[printTypes[index]]+".php"+urlFmt.url();  
                    layui.utils.ajaxGet(url);                        
                }
                parent.window.open("/general/erp4/controller/provebill/download.php","_self");   

            }

        }else{
            url ="/general/erp4/view/provebill/printView/index.php"+urlFmt.url("layui");
           // console.log(url);return;
            parent.window.open(url,"_self");
        }
        
    },
    
    dataCompute:function(obj,dataInfo){
      
        var box_num;var num=0;
        if(obj.field=="num"){
            if(parseInt(obj.value)>parseInt(obj.data.poUantity)) obj.value=parseInt(obj.data.poUantity); 
            
            num=parseInt(obj.value);
            box_num=dataInfo[obj.data.id]["box_num"];
            
             
            if(obj.data.hz==0){
                box_num=parseInt(obj.value);
              
                if(obj.data.complete=="成品"||(obj.data.complete=="半成品"&&obj.data.category=="PVC胶带")){ 
                    if(Number(obj.data['onum'])>0){
                        box_num=(Number(obj.value)*Number(obj.data['huanSuanLv']))/Number(obj.data['onum']);
                    }else{
                        
                        box_num=Number(obj.value);
                    }
                    
                }
               
            }else{
                box_num=num/parseFloat(obj.data.onum);
            }

            if(box_num%1!=0){
                box_num=Math.ceil(box_num);
                 
            }

            if(num==obj.data.org_num){
                box_num=obj.data.boxNum;
               
            }

        }else if(obj.field=="box_num"){
            if(parseInt(obj.value)>parseInt(obj.data.boxNum)) obj.value=parseInt(obj.data.boxNum);
            box_num=parseInt(obj.value);
           
            num=dataInfo[obj.data.id]["num"];

            if(dataInfo[obj.data.id]["box_num"]%1==0){
                if(obj.data.hz==0){
                    num=(obj.data.complete=="成品")?(parseInt(obj.value)*obj.data['onum']/obj.data['huanSuanLv']):parseInt(obj.value);
                }else{
                    num=box_num*parseFloat(obj.data.onum);
                }
            }
        }

        dataInfo[obj.data.id]["num"]=num;
        dataInfo[obj.data.id]["box_num"]=box_num;

        dataInfo[obj.data.id]["mz_1"]=box_num*(parseFloat(obj.data.mz)/parseInt(obj.data.boxNum));
        dataInfo[obj.data.id]["jz_1"]=box_num*(parseFloat(obj.data.jz)/parseInt(obj.data.boxNum));
        dataInfo[obj.data.id]["tj_1"]=box_num*(parseFloat(obj.data.tj)/parseInt(obj.data.boxNum)); 

        dataInfo[obj.data.id]["total_1"]=(parseInt(num)*parseFloat(obj.data.poPrice)).toFixed(2);
        dataInfo[obj.data.id]["apportion_total"]=Number(dataInfo[obj.data.id]["total_1"])+Number(obj.data.apportion_price);
    }

  };
 

  exports('bill', obj);
});    
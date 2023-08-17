layui.define(['utils','laydate','order_cols','bill_deliver','deliver_cols','url_format'],function(exports){ 
    var obj = {

        data:{
           dataInfo:{},
           filter:["deliver_num","deliver_box","total_1","poPrice","proPrice"],//��������ֶ�
           changeField:["deliver_date","deliver_store","warehouse_code","warehouse_person","warehouse_name","mark"]
        },


        initData:function(){
            var that=this; 
            
            var dataInfo={};
            
            if(!layui.utils.isEmpty(layui.store["params"]["flow_id"])){
                dataInfo=layui.utils.sqlManage("deliver_change_list",{"flowId":layui.store["params"]["flow_id"],limit:1});
            }else if(!layui.utils.isEmpty(layui.store["params"]["deliver_id"])){
                dataInfo=layui.utils.sqlManage("deliver_change_list",{"deliver_id":layui.store["params"]["deliver_id"],limit:1});
                dataInfo["flow_id"]=0;
            }
            

            layui.store["dataModel"]["billDeliver"]={};
 
            if(!layui.utils.isEmpty(dataInfo)){
                if(layui.utils.isEmpty(layui.store["params"]["flow_id"])){
                    dataInfo["data2"]="{}";
                    dataInfo["data1"]="{}";
                    dataInfo["flowPrcs"]="";
                    dataInfo["flowType"]="";
                }
 
                dataInfo["data2"]=JSON.parse(dataInfo["data2"]);
                layui.store["dataModel"]["billDeliver"]=dataInfo;
            }
            if(layui.store["dataModel"]["billDeliver"]["sales_num"]){
                ret = layui.utils.post('check_u8_deliver_change',{"sales_num":layui.store["dataModel"]["billDeliver"]["sales_num"]});
                layui.store["dataModel"]["billDeliver"]["is_u8_change"] = ret.data;
            }


            //��Ⱦ������
            if(!layui.utils.isEmpty(dataInfo["flow_id"])){

                var logs=layui.utils.sqlManage("get_log_list",{"flow_id":dataInfo["flow_id"]});
                if(!layui.utils.isEmpty(logs["data"])){
                    layui.store["data"]["logs"]=logs["data"];
                }
            }

            //console.log(layui.store["dataModel"]["billDeliver"]);
            layui.utils.initTpl("form_tmp","#form_box",layui.store);
            layui.laydate.render({elem: '.deliver_date'});
            setTimeout(function(){
                //��ʼ�����ֵ�����
                if(typeof(layui.store["dataModel"]["billDeliver"]["receipt_attachment"])=="undefined"){
                    layui.store["dataModel"]["billDeliver"]["receipt_attachment"]="";
                } 
                layui.utils.initUpload("receipt_attachment",layui.store["dataModel"]["billDeliver"]["receipt_attachment"],"",true); 
                
                layui.form.val("form-plan-audit", layui.store["dataModel"]["billDeliver"]);//����ֵ
                
                
                that.initEvent();
                that.initTab2();
                layui.bill_deliver.initEvent(layui.store);
                that.initChange();//���ر������
            },500);
        },
        
        initChange:function(){
            var that=this;
            var billDeliver=layui.store["dataModel"]["billDeliver"];
            
            if(layui.utils.isEmpty(billDeliver["data1"])) return false;
            
            var data1=!layui.utils.isEmpty(billDeliver["data1"])?JSON.parse(billDeliver["data1"]):"";
            var data3=!layui.utils.isEmpty(billDeliver["data3"])?JSON.parse(billDeliver["data3"]):"";
            
            if(layui.utils.isEmpty(data1)||layui.utils.isEmpty(data3)) return true;
            for(var key in data1){
                if(key == "deliver_store"){
                    var fieldObj=layui.$("form select[name= '"+ key +"'] ");
                    layui.form.val("form-plan-audit", {"deliver_store":data1[key]});//����ֵ
                    layui.$("form select[name='deliver_store'] +div input").css("color","red");
                }else{
                    var fieldObj=layui.$("form input[name='" + key + "']");
                    fieldObj.attr({"style": "color:red"});
                    fieldObj.val(data1[key]);
                    if(key=="mark"){
                        var fieldObj=layui.$("form textarea[name='" + key + "']");
                        fieldObj.attr({"style": "color:red"});
                        fieldObj.val(data1[key]);
                    }
                }         
               
                fieldObj.click(function () {//�󶨵���¼�
                    var fieldKey = layui.$(this).attr("name");
                    layer.tips("ԭֵΪ��" + data3[fieldKey], this, {tips: [1, '#000'], time: 3000});
                })
            }
        },
        //��ʼ���¼�
        initEvent:function(){
            var that=this;
            var billDeliver=layui.store["dataModel"]["billDeliver"];
            
            layui.form.on('submit(save)', function(obj){
                var ret=that.saveData();
                layer.msg("���³ɹ���");
                return false;
            })
            
   
            layui.form.on('submit(save_submit)', function(obj){
                if(layui.store["data"]["opt_lock"]) return false;
                
                var tableData=layui.table.cache["tab2"];
                
                for(var index in tableData){
                    var item=tableData[index];
                    if(item["deliver_box"]%1!=0){
                        layer.msg("����������С��������������ύ��");
                        return false;
                    }
                }

                /*if(billDeliver["flowPrcs"]=="�������"){
                    that.saveSubmitData(1);//���޸�ԭʼ������
                    return false;
                }*/
                
                var urlFmt = new layui.url_format.URL("/general/erp4/view/provebill/change/deliver/template/submit_choose.php");
                urlFmt.setParams({"callback":"deliver_change.saveConfirm","flowPrcs":billDeliver["flowPrcs"]});
                var layer1 = layui.utils.popup({ url:urlFmt.url("layui"),title:"��ѡ���ύ����",width:'400px',height:'320px'}); 
                return false;
            })
            
            layui.form.on('submit(add_submit)', function(obj){
                
                if(layui.store["data"]["opt_lock"]) return false;
                
                var tableData=layui.table.cache["tab2"];
                
                for(var index in tableData){
                    var item=tableData[index];
                    if(item["deliver_box"]%1!=0){
                        layer.msg("����������С��������������ύ��");
                        return false;
                    }
                }
                
                that.addSubmitData();//���޸�ԭʼ������
                return false;
            })
            
            layui.$("#warehouse_name").click(function(){
                var where={"has_tuopan":true,showType:"tuopan"};
                var index=layui.utils.popup({
                    url:'/general/erp4/view/provebill/public/template/warehouse_list.php?/#/select_type=radio/callback=bill_deliver.warehouse_select/where='+encodeURI(layui.utils.jsonEncode(where)),
                    title:"ѡ��ֿ�",
                    width:"800px",
                    height:"670px"
                });
                //layer.full(index) 
            })

            layui.form.on('submit(reback)', function(obj){
                if(layui.store["data"]["opt_lock"]) return false;
                that.deliverReback();
                that.reback();  
                return false;
            })
            
            //���ز���
            layui.$("#reback").click(function(){that.reback();})
    
        },
        
        reback:function(){
         
            if(typeof(layui.store['params']["showType"])!="undefined"){
                location.replace(layui.url_list[layui.store['params']["showType"]]+"/showType="+layui.store['params']["showType"]);
            }
            return false;
        },
        
        //����
        saveData:function(){
            var billDeliver=layui.store["dataModel"]["billDeliver"];
            var data2=billDeliver["data2"];
            
            console.log(billDeliver);
            console.log(layui.store["data"]);
            console.log(layui.store["data"]["org_products"]);
            layui.form.val("form-plan-audit")["warehouse_code"];
            var data1 = {};
            if(!layui.utils.isEmpty(billDeliver["data1"]) && billDeliver["data1"] != "null"){
                var data1Parsed = JSON.parse(billDeliver["data1"]);
                if(!layui.utils.isEmpty(data1Parsed["deliver_store"])){
                    data1["deliver_store"] = layui.form.val("form-plan-audit")["deliver_store"];
                }else{
                    if(billDeliver["deliver_store"] != layui.form.val("form-plan-audit")["deliver_store"]){
                        data1["deliver_store"] = layui.form.val("form-plan-audit")["deliver_store"];
                    }
                }
                if(!layui.utils.isEmpty(data1Parsed["warehouse_code"])){
                    data1["warehouse_code"] = layui.form.val("form-plan-audit")["warehouse_code"];
                    data1["warehouse_person"] = layui.form.val("form-plan-audit")["warehouse_person"];
                    data1["warehouse_name"] = layui.form.val("form-plan-audit")["warehouse_name"];
                }else{
                    if(billDeliver["warehouse_code"] != layui.form.val("form-plan-audit")["warehouse_code"]){
                        data1["warehouse_code"] = layui.form.val("form-plan-audit")["warehouse_code"];
                        data1["warehouse_person"] = layui.form.val("form-plan-audit")["warehouse_person"];
                        data1["warehouse_name"] = layui.form.val("form-plan-audit")["warehouse_name"];
                    }
                }
            }else{
                if(billDeliver["deliver_store"] != layui.form.val("form-plan-audit")["deliver_store"]){
                    data1["deliver_store"] = layui.form.val("form-plan-audit")["deliver_store"];
                }
                if(billDeliver["warehouse_code"] != layui.form.val("form-plan-audit")["warehouse_code"]){
                    data1["warehouse_code"] = layui.form.val("form-plan-audit")["warehouse_code"];
                    data1["warehouse_person"] = layui.form.val("form-plan-audit")["warehouse_person"];
                    data1["warehouse_name"] = layui.form.val("form-plan-audit")["warehouse_name"];
                }
            }
            if(!layui.utils.isEmpty(layui.store["data"]["products"])){
                for(var index in layui.store["data"]["products"]){
                    var item=layui.store["data"]["products"][index]; 
                    var org_item = layui.store["data"]["org_products"][index];
                    if(org_item.warehouse_name != item.warehouse_name){
                        if(layui.utils.isEmpty(data2["modifyWarehouseDetail"])){
                            data2["modifyWarehouseDetail"] = {};
                        }
                        if(layui.utils.isEmpty(data2["modifyWarehouseDetail"][item.id])){
                            data2["modifyWarehouseDetail"][item.id] = {};
                        }
                        data2["modifyWarehouseDetail"][item.id]["id"]=item.deliver_item_id;
                        data2["modifyWarehouseDetail"][item.id]["warehouse_name"]=item.warehouse_name;
                        data2["modifyWarehouseDetail"][item.id]["warehouse_code"]=item.warehouse_code;
                        data2["modifyWarehouseDetail"][item.id]["warehouse_person"]=item.warehouse_person;
                    }
                    var isChange=false;
                    if(!layui.utils.isEmpty(layui.store["data"]["total_change"])&&!layui.utils.isEmpty(layui.store["data"]["total_change"]["key_"+item.id])) isChange=true; 
                    if(!layui.utils.isEmpty(data2["modifyDetail"])&&!layui.utils.isEmpty(data2["modifyDetail"][item.id])) isChange=true;
                    if(!isChange) continue;
                    if(layui.utils.isEmpty(data2["modifyDetail"]))  data2["modifyDetail"]={};
                    if(layui.utils.isEmpty(data2["modifyDetail"][item.id]))  data2["modifyDetail"][item.id]={};
                    data2["modifyDetail"][item.id]["cOrderCode"]=item.order_num_show;
                    data2["modifyDetail"][item.id]["iOaAutoId"]=item.id;
                    data2["modifyDetail"][item.id]["iQuantity"]=item.deliver_num;
                    data2["modifyDetail"][item.id]["poPrice"]=item.proPrice;
                    data2["modifyDetail"][item.id]["total"]=item.total_1;
                    data2["modifyDetail"][item.id]["deliver_box"]=item.deliver_box;
                    
                }
            }
 
            if(!layui.utils.isEmpty(data1)){
                var retInfo= layui.utils.sqlManage("update_flows",{"id":billDeliver["flow_id"],"data1":layui.utils.jsonEncode(data1),"data2":layui.utils.jsonEncode(data2),condition:"update"});//��������    
            }else{
                var retInfo= layui.utils.sqlManage("update_flows",{"id":billDeliver["flow_id"],"data2":layui.utils.jsonEncode(data2),condition:"update"});//��������
            }
            return retInfo;
        },
        //�ύǰ����֤
        saveConfirm:function(submit_type){
            var that = this;
            layui.store["data"]["confirm_lock"]=false;
            if(submit_type == 3){
                layer.prompt({
                    formType: 1,
                    placeholder: '����������',
                    title: '����������',
                }, function(value, index, elem){
                    if(layui.store["data"]["confirm_lock"]){
                        return false;
                    }
                    var pwdInfo = layui.utils.post('get_dz_password');
                    //����,�ָ��ַ���
                    var pwdArr = pwdInfo.v1.split(",");
                    //�ж��������������Ƿ����
                    if(pwdArr.indexOf(value) == -1){
                        layer.msg("�����������������");
                        layui.store["data"]["opt_lock"]=false;
                        return false;
                    }else{
                        layui.store["data"]["confirm_lock"]=true;
                        that.saveSubmitData(submit_type);
                        layer.close(index);
                    }
                });         
            }else{
                layui.store["data"]["confirm_lock"]=true;
                that.saveSubmitData(submit_type);
            }
             
        },
        
        //���沢�ύ
        saveSubmitData:function(submit_type){
            layui.store["data"]["opt_lock"]=true;
            var that=this;
            var billDeliver=layui.store["dataModel"]["billDeliver"];
            
   
            var ret=that.saveData(); //������
        
            //���¶�����Ϣ
            switch(submit_type){
                case 1:
                    ret=that.changeOrder(1);
                    break;
                case 2:
                    ret=that.changeOrder(2);
                    break;
                case 3: 
                    ret=that.changeBill(1);         
                    break;   
                case 4:
                    ret=that.changeBill(2);
                    break;
            }

     
            /*if(layui.utils.isEmpty(ret["status"])||ret["status"]!="200"){
                layer.msg(ret["msg"]);
                layui.store["data"]["opt_lock"]=false;
                return false;
            }*/
                            
            
            
            var ret = that.sendU8Change();

            if(!ret){
                layer.msg("u8���ͬ��ʧ�ܣ�");
                layui.store["data"]["opt_lock"]=false;
                return false;
            }
            
            //�ύ����
            ret = layui.utils.post('flows_next',{"flow_id":billDeliver["flow_id"]});
            
            if(layui.utils.isEmpty(ret["status"])||ret["status"]!="200"){
                layer.msg("�����ύʧ�ܣ�");
                layui.store["data"]["opt_lock"]=false;
                return false;
            }
            
            var retData = that.sendMsg();//������Ϣ
            layui.utils.post('deliver_change_back',{"flow_id":billDeliver["flow_id"]});
            if(retData) layer.msg("�ύ�ɹ���");  
            setTimeout(function(){
                if(layui.utils.inArray(submit_type,[3,4])){
                    var url='/general/erp4/view/provebill/index/template/print_choose.php?/#/id='+billDeliver["bill_ids"]+"/flow_id="+billDeliver["bill_flow_ids"]+"/show_type=2";
                    var lay1 = layui.utils.popup({ url:url,title:"���ݴ�ӡ",width:'500px',height:'450px'});
                }else{
                   that.reback();  
                }
            },1000);    
            return true;
        },
        
        
        addSubmitData:function(){
            layui.store["data"]["opt_lock"]=true;
            var billDeliver=layui.store["dataModel"]["billDeliver"];
            var that=this,data1={},data3={};; 
          
            var formData = layui.form.val("form-plan-audit");
            for(var index in this.data["changeField"]){
                var item=this.data["changeField"][index];
                if(formData[item]!=billDeliver[item]){
                    data1[item]=formData[item];
                    data3[item]=billDeliver[item];
                }
            }

            var tableData=layui.table.cache["tab2"];
            
            var ret=layui.utils.post('add_deliver_change',{"product":tableData,"data1":data1,"data3":data3,"deliver_id":billDeliver["id"]});//��ȡ�û���Ϣ
            
          
            if(layui.utils.isEmpty(ret["status"])||ret["status"]!="200"){
                layer.msg("�����ύʧ�ܣ�");
                layui.store["data"]["opt_lock"]=false;
                return false;
            }
            
            
            billDeliver=layui.utils.sqlManage("deliver_change_list",{"flowId":ret["data"],limit:1});
            if(!layui.utils.isEmpty(billDeliver)){
                billDeliver["data2"]=JSON.parse(billDeliver["data2"]);
            }
            layui.store["dataModel"]["billDeliver"]=billDeliver;
            if(ret["jumpFlow"]){
                if(ret["warehouseChange"]){
                    //����вֿ���
                    that.saveSubmitData2(1);
                }else{
                    that.saveSubmitData2(0);
                }
            }else{
                var urlFmt = new layui.url_format.URL("/general/erp4/view/provebill/change/deliver/template/submit_choose.php");
                urlFmt.setParams({"callback":"deliver_change.saveSubmitData","flowPrcs":billDeliver["flowPrcs"]});
                var layer1 = layui.utils.popup({ url:urlFmt.url("layui"),title:"��ѡ���ύ����",width:'400px',height:'320px'}); 
            }
            
            //that.saveSubmitData(1);
            
            /*layer.msg("�ύ�ɹ���");  
            setTimeout(function(){that.reback();},1000);  */
        },

        deliverReback:function(){
            layui.store["data"]["opt_lock"]=true;
            var billDeliver=layui.store["dataModel"]["billDeliver"];
            retInfo=layui.utils.post('reback_bill_deliver',{"flow_id":billDeliver.flow_id});
        },
        
        sendU8Change:function(){
            var billDeliver=layui.store["dataModel"]["billDeliver"];
            if(billDeliver["flowPrcs"]=="��֤���"){
               
                if(!layui.utils.isEmpty(layui.store["data"]["total_change"])){  
                    var has_adjust=false;
                    var products=layui.store["data"]["total_change"];
                    for(var index in layui.store["data"]["total_change"]){
                        var item=layui.store["data"]["total_change"][index];
                        if(Number(item["apportion_price"])>0)  has_adjust=true;
                    }
                    if(has_adjust) products=layui.store["data"]["products"];
                    var ret=layui.utils.post('change_to_u8',{"product":products,"flow_id":billDeliver["flow_id"]});//������Ϣ
                    if(ret["status"]!="200") return false;
                }
                //���Ͳֿ���
                var warehouse_change = false;
                for(var index in layui.store["data"]["products"]){
                    var item=layui.store["data"]["products"][index]; 
                    var org_item = layui.store["data"]["org_products"][index];
                    if(org_item.warehouse_name != item.warehouse_name){
                        warehouse_change = true;
                    }
                }
                if(warehouse_change){
                    var ret=layui.utils.post('warehouse_change_to_u8',{"product":layui.store["data"]["products"],"flow_id":billDeliver["flow_id"]});//������Ϣ  
                    if(ret["status"]!="200") return false;
                }
            }
           
           
            
            return true;
        },
        
        
        
        
        
        //������ʾ��Ϣ
        sendMsg:function(){
            var billDeliver=layui.store["dataModel"]["billDeliver"];
            var uids="";
            var userGroup=[];
            var flowPrv =layui.utils.post('get_flow_prveuids',{"flowType":"�������","status":"��֤���"});//������Ϣ
            
            if(!layui.utils.isEmpty(flowPrv["data"])){
                uids+=flowPrv["data"]["uids"];
                userGroup=JSON.parse(flowPrv["data"]["user_groups"]);
            }
            


            if(layui.utils.isEmpty(billDeliver["flowPrcs"])||billDeliver["flowPrcs"]=="�������"){ 

                if(!layui.utils.isEmpty(userGroup)){
                    for(var i in userGroup){
                        var item=userGroup[i];
                        if(item.name==billDeliver["deliver_store"]){
                            uids+=item.uids;
                        }
                    }
                }
                
          
                if(!layui.utils.isEmpty(uids)){
                    
                    layui.utils.post('flows_msg',{"flow_id":billDeliver["flow_id"],"uids":uids,"msg":"���µķ��������Ҫ����[�ͻ�����"+billDeliver["customer_short"]+",��Ʊ�ţ�"+billDeliver["invoice_nums"]+"]����ǰ������","ret_url":"erp4/view/provebill/index.php?showType=52"});//������Ϣ
                }
            }else if(billDeliver["flowPrcs"]=="��֤���"){ 
                layui.utils.post('flows_msg',{"flow_id":billDeliver["flow_id"],"uids":billDeliver["beginUser"],"ret_url":"erp4/view/provebill/change/deliver/add.php?/#/flow_id="+billDeliver["flow_id"]+"/opt=detail/showType=53"});//������Ϣ
            }
            
            
            if(!layui.utils.isEmpty(billDeliver["flow_step_id"])){
                layui.utils.sqlManage("user_agency_update",{"flow_step_id":billDeliver["flow_step_id"],"status":"�������",condition:"ret3"});//���´���״̬
            }
            
           
            return true;

        },

        initTab2:function (){
            var data2 = layui.store["dataModel"]["billDeliver"]["data2"];
            var modifyWarehouseDetail = {};
            
            if(layui.utils.isEmpty(layui.store["dataModel"]["billDeliver"])) return false;
            var billDeliver=layui.store["dataModel"]["billDeliver"];
            var formData= layui.form.val("form-plan-audit");
            
                    

            var that=this;

            var where={opt:"deliver_product_list","orderListIds":billDeliver["product_ids"],"bill_ids":billDeliver["bill_ids"]};
            where["deliver_store"]=billDeliver["deliver_store"]; 
            where["deliver_id"]=billDeliver["id"];
            
            var cols=layui.deliver_cols.product_list;
            // cols[0].pop();//ɾ�����һ��Ԫ��
            for(var index in  cols[0]){
                var item=cols[0][index];
                if(layui.utils.inArray(cols[0][index]["field"],["deliver_num","deliver_box","total_1"])&&(billDeliver["flowPrcs"]=="�������"||layui.utils.isEmpty(layui.store["params"]["flow_id"]))){
                    if(cols[0][index]["field"] == 'deliver_num' && layui.store["dataModel"]["billDeliver"]["is_u8_change"] == 1){
                        cols[0][index]["edit"]="";
                    }else{
                        cols[0][index]["edit"]="text";
                    }
                }
            }
        
            var logs = layui.store["data"]["logs"];
            var changeItem={};
            if(!layui.utils.isEmpty(logs)){
                for(var i in logs){
                    var item=logs[i];
                    if(layui.utils.isEmpty(item["data1"])) continue;
                    var data1= JSON.parse(item["data1"]);
                    for(var k in data1["orderlist"]){
                        changeItem[k]=data1["orderlist"][k];
                    }
                }
            }
            
            //��ʼ����Ʒ�б�
            layui.table.render({elem: '#tab2', url: layui.utils.urlData['provebill'],cols:cols, 
                where:where,
                toolbar: '#tab2Bar1',
                totalRow: true ,
                loading:false,
                parseData: function(res){ 
                    layui.store["data"]["org_products"]={};
                    if(!layui.utils.isEmpty(data2["modifyWarehouseDetail"])){
                        modifyWarehouseDetail = data2["modifyWarehouseDetail"];
                    }
                    //Ĭ��ѡ���ж�
                    //store["data"]["hz_products"]
                    if(!layui.utils.isEmpty(res.data)){
                        for(var index in res.data){ 
                            let item=res.data[index];

                        
                            //����ë����
                            if(layui.utils.isEmpty(layui.store["data"]["products"]["key_"+item.id])){
                                layui.store["data"]["products"]["key_"+item.id]=layui.utils.objClone(item);  
                            }else{
                                let productItem=layui.store["data"]["products"]["key_"+item.id];
                                if(layui.utils.isEmpty(productItem["warehouse_code"])){
                                    layui.store["data"]["products"]["key_"+item.id]["warehouse_code"]=layui.form.val("form-plan-audit")["warehouse_code"];
                                    layui.store["data"]["products"]["key_"+item.id]["warehouse_name"]=layui.form.val("form-plan-audit")["warehouse_name"];
                                    layui.store["data"]["products"]["key_"+item.id]["warehouse_person"]=layui.form.val("form-plan-audit")["warehouse_person"];
                                }   
                            }
                            //����ֿ��б����ҳ����Ⱦ����ϸ�Ĳֿ�
                            if(!layui.utils.isEmpty(modifyWarehouseDetail[item.id])){
                                layui.store["data"]["products"]["key_"+item.id]["warehouse_code"]=modifyWarehouseDetail[item.id]["warehouse_code"];
                                layui.store["data"]["products"]["key_"+item.id]["warehouse_name"]=modifyWarehouseDetail[item.id]["warehouse_name"];
                                layui.store["data"]["products"]["key_"+item.id]["warehouse_person"]=modifyWarehouseDetail[item.id]["warehouse_person"];
                            }

                            layui.store["data"]["org_products"]["key_"+item.id]= layui.utils.objClone(item);//��¼ԭʼ������
                            
                            
                            
                            //���±������
                            if(!layui.utils.isEmpty(changeItem[item.id])){
                                for(var i in changeItem[item.id]){
                                    layui.store["data"]["org_products"]["key_"+item.id][i]=changeItem[item.id][i];
                                    
                                    /*if(i=="deliver_num"){
                                        //layui.store["data"]["org_products"]["key_"+item.id]["deliver_box"]=Math.ceil(changeItem[item.id]["deliver_num"]/Number(item["huanSuanLv"]));
                                       
                                        if(item.hz==0&&item.complete=="���Ʒ"&&item.category!="PVC����"){
                                            layui.store["data"]["org_products"]["key_"+item.id]["deliver_box"]=Number(changeItem[item.id]["deliver_num"]/Number(item["huanSuanLv"]));
                                        }else{
                                            layui.store["data"]["org_products"]["key_"+item.id]["deliver_box"]=Number(changeItem[item.id]["deliver_num"]/Number(item["onum"]));
                                        }

                                    }*/
                                }
                            }
  
                        }
                    }

  
                    
                    //store["data"]["flowData"]
                    return {"code": res.code,"msg": res.msg, "count": res.count, "data": res.data };
                }, done: function (res, curr, count) {
                    if(!layui.utils.isEmpty(res.data)){
                        layui.$("#tab2 + div .layui-table-box tr td").attr({"style": "background:#f8f8f8",});
                        layui.$("#tab2 + div .layui-table-box tr td[data-edit=text]").attr({"style": "background:#fff"});
                        var changeMap={"iQuantity":"deliver_num",deliver_box:"deliver_box",poPrice:"proPrice","total":"total_1"};
                        var change_ids=[];
                        var orgValue={};
                        for(var index in res.data){
                            if(layui.utils.isEmpty(billDeliver["data2"])) break;
                            var item=res.data[index];
                            orgValue[item.id]=layui.store["data"]["org_products"]["key_"+item.id];
                            //��Ⱦ�ֿ���Ϣ
                            if(layui.store["data"]["products"]["key_"+item.id]["warehouse_name"]!=orgValue[item.id]["warehouse_name"]){
                                var fieldObj=layui.$("#tab2 + div tr[data-index='" + index + "'] td[data-field='warehouse_name'] ");
                                fieldObj.find("div").html(layui.store["data"]["products"]["key_"+item.id]["warehouse_name"]);
                                fieldObj.attr({"style": "color:red"});
                                fieldObj.click(function () {//�󶨵���¼�
                                    var id = layui.$(this).parents("tr").find(" td[data-field='id'] div ").html();
                                    var nameKey = layui.$(this).attr("data-field");
                                    layer.tips("ԭֵΪ��"+orgValue[id][nameKey] , this, {tips: [1, '#000'], time: 3000});
                                })
                            } 
                        
                            if(layui.utils.isEmpty(billDeliver["data2"]["modifyDetail"])) continue;
                            if(layui.utils.isEmpty(billDeliver["data2"]["modifyDetail"][item.id])) continue;
                            var modify=billDeliver["data2"]["modifyDetail"][item.id];
                            
                            if(!layui.utils.isEmpty(modify["iQuantity"])){
       
                                /*if(item.hz==0&&item.complete=="���Ʒ"&&item.category!="PVC����"){
                                    modify["deliver_box"]=Number(Number(modify["iQuantity"])/Number(item["huanSuanLv"]));
                                }else{
                                    modify["deliver_box"]=Number(Number(modify["iQuantity"])/Number(item["onum"]));
                                }*/
       
                            }
                            
                            var has_change=false;
                            
                            for(var i in changeMap){   
                                if(!layui.utils.isEmpty(modify[i])&&modify[i]!=orgValue[item.id][changeMap[i]]){
                                    has_change=true;
                                    //orgValue[item.id][changeMap[i]]=item[changeMap[i]];
                                    var fieldObj=layui.$("#tab2 + div tr[data-index='" + index + "'] td[data-field="+changeMap[i]+"] ");
                                    layui.store["data"]["products"]["key_"+item.id][changeMap[i]]=modify[i];
                                    var fieldMap=["deliver_num","proPrice","total_1"]; 
                                    if(fieldMap.indexOf(changeMap[i])!=-1){
                                        layui.store["data"]["products"]["key_"+item.id][changeMap[i]]=Number(modify[i]);
                                    }
                                    
                                    

                                    fieldObj.find("div").html(modify[i]);
                                    fieldObj.attr({"style": "color:red"});
                                    fieldObj.click(function () {//�󶨵���¼�
                                        var id = layui.$(this).parents("tr").find(" td[data-field='id'] div ").html();
                                        var nameKey = layui.$(this).attr("data-field");
                                        layer.tips("ԭֵΪ��"+orgValue[id][nameKey] , this, {tips: [1, '#000'], time: 3000});
                                    })
                                }                                
                            }
                            
                            if(has_change){
                                change_ids.push(item.id);
                            }
        
                        }
                        
                        layui.bill_deliver.dataCompute(layui.store);//���㷢������
                        layui.store["data"]["total_change"]={};
                        for(var k in layui.store["data"]["products"]){
                            var item=layui.store["data"]["products"][k];
                            if(layui.utils.inArray(item["id"],change_ids)){
                                layui.store["data"]["total_change"][k] = layui.store["data"]["products"][k];
                            } 
                        }
                        
                        //layui.store["data"]["total_change"] = layui.store["data"]["products"];
                       
                    }
                }     
            });
        },
        
        
        //��ȡ�����־�б�
        getChangeData:function(){
            var billDeliver=layui.store["dataModel"]["billDeliver"];
            var flow_id=billDeliver["flow_id"];
            var retInfo={data1:{},data2:{},fields:{}};
            var logs = layui.store["data"]["logs"];
           
            if(!layui.utils.isEmpty(logs)){
                for(var index in logs){
                    var item=logs[index];
                    var data1= JSON.parse(item["data1"]);
                    var data2= JSON.parse(item["data2"]);
                    if(layui.utils.isEmpty(data1)||layui.utils.isEmpty(data2)) continue;
                    if(layui.utils.isEmpty(retInfo["fields"][item.case_id])) retInfo["fields"][item.case_id]={};
                    for(var k in data2["orderlist"]){
                        retInfo["fields"][item.case_id][k]=layui.utils.getArrayKey(data2["orderlist"][k]);
                    }
                    retInfo["data1"][item.case_id]=data1["orderlist"];
                    retInfo["data2"][item.case_id]=data2["orderlist"];  
                }
                return retInfo;
            }
            
            
    
            var orgProducts=layui.store["data"]["org_products"];
            var products=layui.store["data"]["products"];
            var data1={},data2={},fields={};
            for(var index in products){
                var item=products[index];
                if(layui.utils.isEmpty(fields[item.orderId])) fields[item.orderId]={};
                if(layui.utils.isEmpty(data1[item.orderId]))  data1[item.orderId]={};
                if(layui.utils.isEmpty(data2[item.orderId]))  data2[item.orderId]={};
                for(var k in item){        
                    if(layui.utils.inArray(k,["LAY_TABLE_INDEX"])) continue;
                    if(typeof(orgProducts[index][k])!="undefined"&&item[k]!=orgProducts[index][k]){
                        if(layui.utils.isEmpty(fields[item.orderId][item.id])) fields[item.orderId][item.id]=[];
                        if(layui.utils.isEmpty(data1[item.orderId][item.id]))  data1[item.orderId][item.id]={};
                        if(layui.utils.isEmpty(data2[item.orderId][item.id]))  data2[item.orderId][item.id]={};
                        fields[item.orderId][item.id].push(k);
                        data1[item.orderId][item.id][k]=orgProducts[index][k];
                        data2[item.orderId][item.id][k]=item[k];                        
                    }
                }
            }
            
            retInfo={data1:data1,data2:data2,fields:fields};
            return retInfo;
                
        },
        
        //����ԭ����
        changeOrder:function (optType){
            if(layui.utils.isEmpty(layui.store["dataModel"]["billDeliver"])) return false;
            var orgProducts=layui.store["data"]["org_products"];
            var products=layui.store["data"]["products"];
            
          

            var billDeliver=layui.store["dataModel"]["billDeliver"];
            var updateList=[],logs=[],deliverItems=[];
 
            var beginUser=layui.utils.post('get_user_info',{"id":billDeliver["beginUser"]});//��ȡ�û���Ϣ
            
            var changeData=this.getChangeData();
            
            if(layui.utils.isEmpty(changeData["data2"])){
                return {"status":"500","msg":"�ޱ�����ݣ�","data":{}};
            }

            for(var index in changeData["data2"]){
                
                logs.push({
                    "type_flag":"order",
                    "case_id":index,
                    "flow_id":billDeliver["flow_id"],
                    "name":"��������",
                    "mark":"",
                    "data1": layui.utils.jsonEncode({"orderlist":changeData["data1"][index]}),
                    "data2": layui.utils.jsonEncode({"orderlist":changeData["data2"][index]}), 
                    "creator_id": beginUser["user_id"],
                    "creator_name":beginUser["user_name"],
                    "change_type":"�������",
                });
                
                for(var k in changeData["data2"][index]){
                    if(!layui.utils.isEmpty(layui.utils.getArrayEqual(changeData["fields"][index][k],this.data["filter"]))){
                        var item=products["key_"+k];
                         
                        var updateItem={}
                        var onum=!layui.utils.isEmpty(item["onum"])?item["onum"]:1;//������   
                        var huanSuanLv=!layui.utils.isEmpty(item["huanSuanLv"])?item["huanSuanLv"]:1;//������   
                        updateItem["orderId"]=item.orderId; 
                        updateItem["id"]=item.id; 
                        if(item["more"]=="��"){
                        
                            updateItem["proUantity"]=Number(item["proUantity"])+(Number(item["deliver_num"])-Number(orgProducts["key_"+k]["deliver_num"]));
                            updateItem["boxNum"]=Number(item["boxNum"])+Number(item["deliver_box"])-Number(orgProducts["key_"+k]["deliver_box"]);  
                        }else{
                        
                            updateItem["proUantity"]=Number(item["deliver_num"]);
                            updateItem["boxNum"]=Number(item["deliver_box"])
                        }
                        //�����λ��kg���ó���������
                        /*if(item["proUnit"] == "KG"){
                            updateItem["poUantity"]=Number(updateItem["boxNum"]);     
                        }else{
                            updateItem["poUantity"]=Number(updateItem["boxNum"])*Number(onum);     
                        }*/
                        if(layui.utils.isEmpty(item["onum"])){
                            updateItem["poUantity"]=Number(updateItem["boxNum"]);    
                        }else{
                            updateItem["poUantity"]=Number(updateItem["boxNum"])*Number(onum)/huanSuanLv; 
                        }
                         
                        
                        if(onum==1){
                            //total_1:�ֹ�����ı����deliver_box���ֹ�����ķ�������,
                            updateItem["poPrice"]=(Number(item["total_1"])/Number(item["deliver_box"])).toFixed(6);
                        }else{
                            updateItem["poPrice"]=(Number(item["total_1"])/(Number(item["deliver_box"])*Number(onum)/Number(huanSuanLv))).toFixed(6);
                        }
                        
                        updateItem["total"]=(Number(updateItem["poPrice"])*Number(updateItem["poUantity"])).toFixed(6);
                        updateItem["proPrice"]=Number(item["total_1"])/Number(item["deliver_num"]);
                        
                        //console.log(item["total_1"]+"/"+item["deliver_num"]);
                      
                        updateList.push(updateItem);
                    }
                }
    
            }
            
 
            if(optType==2){
                
               
                
                //���¶���
                if(!layui.utils.isEmpty(updateList)){
                    
                    var ret = layui.utils.post("order_list_update",{"product":updateList});//���¶�����ϸ����
                    
                    if(layui.utils.isEmpty(ret)||ret["status"]!="200"){
                        return {"status":"500","msg":"�������ݸ���ʧ�ܣ�","data":{}};
                    }
                }
                
                
                //���±����־
                if(!layui.utils.isEmpty(logs)){
                    var ret = layui.utils.sqlManage("update_log_list",{logs:logs,condition:"updateList"});
                    if(layui.utils.isEmpty(ret)||ret["status"]!="200"){
                        return {"status":"500","msg":"�����־����ʧ�ܣ�","data":{}};
                    }
                }

                layui.utils.post("deliver_change_order",{"deliver_id":billDeliver["id"]});
            }else{
                var productList=[];
                if(!layui.utils.isEmpty(updateList)){
                    for(var i in updateList){
                        productList.push({"id":updateList[i]["id"],"proPrice":updateList[i]["proPrice"]});
                    }
                    
                    var ret = layui.utils.sqlManage("order_list_update",{"product":productList});
                    if(layui.utils.isEmpty(ret)||ret["status"]!="200"){
                        return {"status":"500","msg":"�������ݸ���ʧ�ܣ�","data":{}};
                    }
                }
      
            }
            
 
            return {"status":"200","msg":"�ɹ���","data":{}};

        },
        
        //�������յ�����
        changeBill:function(optType){
            
            if(layui.utils.isEmpty(layui.store["dataModel"]["billDeliver"])) return false;
            var billDeliver=layui.store["dataModel"]["billDeliver"];
            var orgProducts=layui.store["data"]["org_products"];

            if(layui.utils.isEmpty(billDeliver["data2"]["modifyDetail"])) return false;

            var changeProducts=billDeliver["data2"]["modifyDetail"];
            
            var products=layui.store["data"]["products"];
            var optType=!layui.utils.isEmpty(optType)?optType:1;

            var billProductList={},deliverItems=[];
            var billProducts=layui.utils.sqlManage("provebill_product_list",{provebill_id:billDeliver["bill_ids"],limit:999});
            

            if(layui.utils.isEmpty(billProducts["data"])){
                return {"status":"500","msg":"��ȡ������ϸʧ�ܣ�","data":{}};
            }
            
            for(var i in billProducts["data"]){
                var item=billProducts["data"][i];
                if(layui.utils.isEmpty(orgProducts["key_"+item["order_list_id"]])){
                    billProducts["data"].splice(i,1);
                }
            }
            
          
   
            var adjustList=[],productList=[];
            //var changeData=this.getChangeData();
            
            for(var index in billProducts["data"]){
                
                var item=billProducts["data"][index];
                var productItem=layui.utils.objClone(item);
               
                if(layui.utils.isEmpty(changeProducts[item["order_list_id"]])) continue;
                
                var changeItem=changeProducts[item["order_list_id"]];
  
                productItem["bill_product_id"]=item["id"];
                if(!layui.utils.isEmpty(orgProducts["key_"+item["order_list_id"]])){
                    productItem["po_price"]=orgProducts["key_"+item["order_list_id"]]["poPrice"];
                }
               
  

                adjustList.push(productItem);
                 
                var product=products["key_"+item["order_list_id"]];
                if(layui.utils.isEmpty(product)) continue;
                //��λ����                
                var unit={
                    "dwmz":Number(item["mz_1"])/Number(item["num"]),
                    "dwjz":Number(item["jz_1"])/Number(item["num"]),
                    "dwtj":Number(item["tj_1"])/Number(item["num"])
                };
                var updateItem={}
                var onum=!layui.utils.isEmpty(product["onum"])?product["onum"]:1;//������  
                var huanSuanLv=!layui.utils.isEmpty(product["huanSuanLv"])?product["huanSuanLv"]:1;//������ 

                item["num"]=Number(changeItem["iQuantity"])/Number(huanSuanLv);
                
 
                if(item["num"]%1!=0) {
                    if(layui.utils.isEmpty(product["onum"])){
                        item["num"]=Number(changeItem["deliver_box"]);    
                    }else{
                        item["num"]=changeItem["deliver_box"]*onum/Number(huanSuanLv);
                    }
                }
       
                
                item["box_num"]=changeItem["deliver_box"]; 
                item["total_1"]=changeItem["total"];
                item["po_price"]=Number(changeItem["total"])/Number(item["num"]);
                item["apportion_total"]=Number(item["total_1"])+Number(item["apportion_price"]);

                item["mz_1"]=Number(item["num"])*unit["dwmz"];
                item["jz_1"]=Number(item["num"])*unit["dwjz"];
                item["tj_1"]=Number(item["num"])*unit["dwtj"];
                if(!layui.utils.isEmpty(product["deliver_item_id"])){
                    deliverItems.push({"id":product["deliver_item_id"], "deliver_num":product["deliver_num"],"deliver_box":product["deliver_box"]});
                }
 
                productList.push(item);
            }
            
        
            //��¼�����־
            var retInfo = layui.utils.post("set_deliver_log",{
                "flow_id":billDeliver["flow_id"],
                "productList":productList
            }); 
            

            //����ԭ����������
            if(!layui.utils.isEmpty(deliverItems)){
                var ret = layui.utils.sqlManage("provebill_deliver_item",{"product":deliverItems});//���¶�����ϸ����
                if(layui.utils.isEmpty(ret)||ret["status"]!="200"){
                    return {"status":"500","msg":"����������ʧ�ܣ�","data":{}};
                }
            }
            
     
            if(!layui.utils.isEmpty(productList)){
                
                var retInfo={};
                if(optType==1){
                    //var retInfo = layui.utils.sqlManage("provebill_product_adjust",{"product":adjustList});
                    var retInfo = layui.utils.post("provebill_product_adjust",{"products":adjustList,"status":1})
                }
                
                
                
                
                var retInfo = layui.utils.sqlManage("provebill_product_update",{"product":productList,"status":"1",condition:"deliver_update"});
                
            }else{
                retInfo = {"status":"200","msg":"�ɹ���","data":{}};
            }
            
            
            
            return retInfo;
 
        },
        //���沢�ύ
        saveSubmitData2:function(type){
            layui.store["data"]["opt_lock"]=true;
            var that=this;
            var billDeliver=layui.store["dataModel"]["billDeliver"];
            var ret = that.saveData(); //������
            var ret = that.changeOrder(1);
            if(type==1){
                var ret=layui.utils.post('warehouse_change_to_u8',{"product":layui.store["data"]["products"],"flow_id":billDeliver["flow_id"]});
                if(!ret){
                    layer.msg("u8���ͬ��ʧ�ܣ�");
                    layui.store["data"]["opt_lock"]=false;
                    return false;
                }
            }
            
            //�ύ����
            var ret = layui.utils.post('flows_next',{"flow_id":billDeliver["flow_id"]});
            if(layui.utils.isEmpty(ret["status"])||ret["status"]!="200"){
                layer.msg("�����ύʧ�ܣ�");
                layui.store["data"]["opt_lock"]=false;
                return false;
            }
            var ret = layui.utils.post('flows_next',{"flow_id":billDeliver["flow_id"]});
            if(layui.utils.isEmpty(ret["status"])||ret["status"]!="200"){
                layer.msg("�����ύʧ�ܣ�");
                layui.store["data"]["opt_lock"]=false;
                return false;
            }
            var retData = layui.utils.post('deliver_change_back',{"flow_id":billDeliver["flow_id"]});
            if(retData) layer.msg("�ύ�ɹ���");  
            setTimeout(function(){
                that.reback();  
            },1000);    
            return true;
        },
        
        
 
    };
 
  exports('deliver_change', obj);
});    
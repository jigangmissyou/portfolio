layui.define(['utils','laydate'],function(exports){ 
  var obj = {
    
    data:{
       dataInfo:{}
    },
  

    specialCallback:function(store){
        this.updateSpecial(store);  
        this.initTab3(store)
    },
    initTab3:function(store){
        
        var orgData=layui.utils.ajaxGet('/general/erp4/controller/tsyq/list_k.php',{orderId: store["data"]["id"],"page":"1","limit":"999"});
        
        //获取特殊要求变更明细
        var changeSpecial=!layui.utils.isEmpty(store["data"]["flowData"]["data2"]["special"])?store["data"]["flowData"]["data2"]["special"]:[];
        var orgSpecial=[];
        
  
        if(!layui.utils.isEmpty(orgData["data"])){
            for(var index in orgData["data"]){
                var item=orgData["data"][index];
                orgSpecial[item.id]=item;
            }
        }else{
            orgData["data"]=[];
        }
        
       
        
        if(!layui.utils.isEmpty(changeSpecial)){
            for(var index in changeSpecial){
                if(index.match(RegExp(/new_\w+/))){
                   orgData["data"].push(changeSpecial[index]);
                }
            }
            for(var i in orgData["data"]){
                var item=orgData["data"][i];
                if(!layui.utils.isEmpty(changeSpecial[item.id])){
                    for(var k in changeSpecial[item.id]){
                         orgData["data"][i][k]=changeSpecial[item.id][k];
                    }
                }
            }
        }

        return layui.table.render({elem: '#tab3', data:orgData["data"]
            , cols: [[
                    {field: 'id', title: 'id', width: '', hide: 'true', fixed: 'left'}
                    , {type: 'numbers', title: '序号', width: 90, hide: ''}
                    , {field: 'del', title: '是否删除', width: 90, hide: '',templet: function(d){
                        var del= d.del=="1"? "已删除":"";
                        return '<span>'+del+'</span>'; 
                    }}
                    , {field: 'name', title: '项目', width: 200, hide: ''}
                    , {field: 'desc', title: '描述', width: '', hide: ''}
                    , {field: 'attId', title: '附件Id', width: 200, hide: 'true'}
                    , {field: 'attText', title: '附件', width: 200, event: 'viewAtt', hide: ''}
                    , {fixed: 'right', width: 120, align: 'center', toolbar: '#tab3Bar'}
                ]]
            , toolbar: '#tab1Bar3'
            ,limit:999
            ,parseData: function(res){ }
            , done: function (res, curr, count) {

                //数据字段比对
                //layui.$("#tab3 + div .layui-table-box tr td").attr({"style": "background:#f8f8f8",});
                layui.$("#tab3 + div .layui-table-box tr td[data-edit=text]").attr({"style": "background:#fff"});
                if(!layui.utils.isEmpty(res.data)){
                    for(var index in res.data){
                        var item=res.data[index];
                        if (item.del=="1") {
                            layui.$("#tab3 + div tr[data-index='" + index + "']").attr({"style": " color:red"});
                        }
                        
                        delete item['LAY_TABLE_INDEX'];
                        delete item['del'];
                        if(!layui.utils.isEmpty(orgSpecial[item.id])){
                            for(var key in item){
                                if (item[key] != orgSpecial[item.id][key]) {
                                    var fieldObj=layui.$("#tab3 + div tr[data-index='" + index + "'] td[data-field="+key+"] ");
                                    fieldObj.attr({"style": "color:red"});
                                    fieldObj.click(function () {//绑定点击事件
                                        var id = layui.$(this).parents("tr").find(" td[data-field='id'] div ").html();
                                        var nameKey = layui.$(this).attr("data-field");
                                        layer.tips("原值为：" + orgSpecial[id][nameKey], this, {tips: [1, '#000'], time: 3000});
                                    })
                                }
                            }
                        }
                        
                        
                        if (!layui.utils.isEmpty(item.id)&&item.id.match(RegExp(/new_\w+/))) {  
                            layui.$("#tab3 + div tr[data-index='" + index + "']").attr({"style": "background:#1CA21C; color:#ffffff"});
                        }  
  
                    }
                } 
                
                
                
                

            }
            
            

        })
    },

    orderChangeCols:function(store){console.log(store);
        
        var editext=store["data"]["isedit"] != "0"?"text":"";

        var cols=[[
                {field: 'id', title: '明细内码', width: '', hide: 'true', sort: false, fixed: 'left'}
                , {type: 'checkbox', title: '', width: 70, hide: '', sort: false}
                , {type: 'numbers', title: '序号', width: 70, hide: '', sort: false}
                , {field: 'delete', title: '是否删除', width: 100, hide: '', sort: false}
                , {field: 'category', title: '类别', width: 100, hide: '', sort: false}
                , {field: 'allComplete', title: '整单完结', width: 100, hide: '', sort: false}
                , {field: 'cInvCode', title: '型号', event: 'cInvCode', width: 90, hide: '', sort: false}
                , {field: 'name', title: '名称', event: '', width: 90, hide: '', sort: false}
                , {field: 'bhzSta', title: '被混装', width: 80, hide: '', sort: false}
                , {field: 'u8Code', title: '存货编码', width: 90, hide: '', sort: false}
                , {field: 'customerCode', title: '客户货号', width: 90, hide: '', sort: false, edit: ''}
                , {field: 'proColor', title: '颜色', width: 90, hide: '', sort: false}
                , {field: 'width', title: '宽（MM）', width: 90, hide: '', sort: false, edit: ''}
                , {field: 'length', title: '长（M）', width: 90, hide: '', sort: false, edit: ''}
                , {field: 'glueSty', title: '胶系', width: 90, hide: '', sort: false}
                , {field: 'sfieldText', title: '特性参数', width: 90, hide: '', sort: false}
                , {field: 'barCode', title: '条形码', width: 90, hide: '', sort: false, edit: ''}
                , {field: 'proUnit', title: '生产单位', width: 90, hide: '', sort: false}
                
                , {field: 'print_width', title: '有效宽度',width: 100, hide: 'true', sort: false,edit: "text"}
                , {field: 'print_price', title: '印刷平方单价', width: 100, hide: 'true', sort: false,edit: "text"}
                , {field: 'print_price_total', title: '印刷平方总价', width: 100, hide: 'true', sort: false}
                
                , {field: 'poUantitys', title: '变更前合同数量', width: 120, hide: 'true', sort: false}
                , {field: 'poUantity', title: '变更后合同数量', width: 120, hide: 'true', sort: false,edit: editext}
                , {field: 'proUantitys', title: '变更前数量', width: 90, hide: '', sort: false}
                , {field: 'proUantity', title: '变更后数量', width: 90, hide: '', sort: false,edit: editext}
                , {field: 'boxNums', title: '变更前箱数', width: 90, hide: '', sort: false}
                , {field: 'boxNum', title: '变更后箱数', width: 90, hide: '',edit: editext, sort: false}
                , {field: 'total', title: '变更前金额', width: 90, hide: '', sort: false}
                , {field: 'proTotal', title: '变更后金额', width: 90, hide: '',edit: editext, sort: false}
                , {field: 'inum', title: '内入箱数', width: 90, hide: '', sort: false, edit: '',totalRow:'true'}
                , {field: 'onum', title: '外入箱数', width: 90, hide: '', sort: false, edit: '',totalRow:'true'}
                , {field: 'poPrice2', title: '公斤单价', width: 100, hide: "", sort: false}
                , {field: 'zs_zjz', title: '条码总净重', width: 100, hide: "", sort: false,totalRow:'true'}
                , {field: 'zs_zmz', title: '条码总毛重', width: 100, hide: "", sort: false,totalRow:'true'}
                , {field: 'poPrices', title: '变更前合同单价', width: 150, hide: true, sort: false}
                , {field: 'poPrice', title: '变更后合同单价', width: 150, hide: true, sort: false}
                , {field: 'proPrices', title:'变更前生产单价', width: 150, hide: true, sort: false}
                , {field: 'proPrice', title: '变更后生产单价', width: 150, hide: true, sort: false}
                , {field: 'total', title: '金额小计', width: 90, hide: true, sort: false, totalRow: ''}
                , {field: 'baoZhuangMS', title: '包装方式', width: 90, hide: '', sort: '', edit: ''}
                , {field: 'tintd', title: '内经MM', width: 90, hide: '', sort: ''}
                , {field: 'tthick', title: '管壁厚度MM', width: 90, hide: '', sort: ''}
                , {field: 'tdf1', title: '是否印刷', width: 90, hide: '', sort: ''}
                , {field: 'desc', title: '其它要求', width: 90, hide: '', sort: '', edit: ''}
                , {field: 'imaterial', title: '内箱材质', width: 90, hide: '', sort: ''}
                , {field: 'icolor', title: '内箱颜色', width: 90, hide: '', sort: ''}
                , {field: 'omaterial', title: '外箱材质', width: 90, hide: '', sort: ''}
                , {field: 'ocolor', title: '外箱颜色', width: 90, hide: '', sort: ''}
                , {field: 'tuoPan', title: '托盘', width: 90, hide: '', sort: ''}
                , {field: 'tpdesc', title: '托盘特殊要求', width: 90, hide: '', sort: '', edit: ''}
                , {fixed: 'right', width: 200, align: 'center', toolbar: '#tab1Bar'}
            ]];

            var showMap={
                1:["poUantitys","poUantity"],
                2:["poPrices","poPrice","proPrices","proPrice"],
                3:["print_width","print_price","print_price_total"],
            }

            if(store["data"]["flowData"]["info"]["flowType"]=="销售变更"){
                //cols[0][22]={field: 'boxNum', title: '变更后箱数', width: 90, hide: '', sort: false};
               // cols[0][24]={field: 'boxNum', title: '变更后箱数', width: 90, hide: '', sort: false};
                 
       
                for(var index in cols[0]){
                    var item=cols[0][index];
                    if(layui.utils.inArray(item.field,showMap[1])){
                        cols[0][index]["hide"]="";
                    }
                }
                
                //console.log(store);
                if(layui.utils.inArray(store["params"]["retType"],["18","40"])){
                    
                    for(var index in cols[0]){
                        var item=cols[0][index];
                        if(layui.utils.inArray(item.field,showMap[2])){
                            cols[0][index]["hide"]="";
                        }
                    }
                }
                 
            }else if(store["data"]["flowData"]["info"]["flowType"]=="生产变更"){
                for(var index in cols[0]){
                    var item=cols[0][index];
                    if(layui.utils.inArray(item.field,showMap[3])){
                        cols[0][index]["hide"]="";
                    }
                }
            }

        if(typeof store['data']['flowData']['data1']['ddLeiXing'] != "undefined" && store['data']['flowData']['data1']['ddLeiXing']!='YW'){
             cols[0].forEach((x,index)=> {
                if(x.field==='poUantitys'){
                    cols[0].splice(index, 1);
                }
            });
            cols[0].forEach((x,index)=> {
                if(x.field==='poUantity'){
                    cols[0].splice(index, 1);
                }
            });

        }
        
       return  cols;
    },
    
    updateSpecial:function(store){
        layui.utils.post("save_step",{data2:{special:store["data"]["flowData"]["data2"]["special"]},flowId:store["data"]["flowId"]},"order_change");
    }
    
  };
 
  exports('order_change', obj);
});    
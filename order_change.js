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
        
        //��ȡ����Ҫ������ϸ
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
                    , {type: 'numbers', title: '���', width: 90, hide: ''}
                    , {field: 'del', title: '�Ƿ�ɾ��', width: 90, hide: '',templet: function(d){
                        var del= d.del=="1"? "��ɾ��":"";
                        return '<span>'+del+'</span>'; 
                    }}
                    , {field: 'name', title: '��Ŀ', width: 200, hide: ''}
                    , {field: 'desc', title: '����', width: '', hide: ''}
                    , {field: 'attId', title: '����Id', width: 200, hide: 'true'}
                    , {field: 'attText', title: '����', width: 200, event: 'viewAtt', hide: ''}
                    , {fixed: 'right', width: 120, align: 'center', toolbar: '#tab3Bar'}
                ]]
            , toolbar: '#tab1Bar3'
            ,limit:999
            ,parseData: function(res){ }
            , done: function (res, curr, count) {

                //�����ֶαȶ�
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
                                    fieldObj.click(function () {//�󶨵���¼�
                                        var id = layui.$(this).parents("tr").find(" td[data-field='id'] div ").html();
                                        var nameKey = layui.$(this).attr("data-field");
                                        layer.tips("ԭֵΪ��" + orgSpecial[id][nameKey], this, {tips: [1, '#000'], time: 3000});
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
                {field: 'id', title: '��ϸ����', width: '', hide: 'true', sort: false, fixed: 'left'}
                , {type: 'checkbox', title: '', width: 70, hide: '', sort: false}
                , {type: 'numbers', title: '���', width: 70, hide: '', sort: false}
                , {field: 'delete', title: '�Ƿ�ɾ��', width: 100, hide: '', sort: false}
                , {field: 'category', title: '���', width: 100, hide: '', sort: false}
                , {field: 'allComplete', title: '�������', width: 100, hide: '', sort: false}
                , {field: 'cInvCode', title: '�ͺ�', event: 'cInvCode', width: 90, hide: '', sort: false}
                , {field: 'name', title: '����', event: '', width: 90, hide: '', sort: false}
                , {field: 'bhzSta', title: '����װ', width: 80, hide: '', sort: false}
                , {field: 'u8Code', title: '�������', width: 90, hide: '', sort: false}
                , {field: 'customerCode', title: '�ͻ�����', width: 90, hide: '', sort: false, edit: ''}
                , {field: 'proColor', title: '��ɫ', width: 90, hide: '', sort: false}
                , {field: 'width', title: '��MM��', width: 90, hide: '', sort: false, edit: ''}
                , {field: 'length', title: '����M��', width: 90, hide: '', sort: false, edit: ''}
                , {field: 'glueSty', title: '��ϵ', width: 90, hide: '', sort: false}
                , {field: 'sfieldText', title: '���Բ���', width: 90, hide: '', sort: false}
                , {field: 'barCode', title: '������', width: 90, hide: '', sort: false, edit: ''}
                , {field: 'proUnit', title: '������λ', width: 90, hide: '', sort: false}
                
                , {field: 'print_width', title: '��Ч���',width: 100, hide: 'true', sort: false,edit: "text"}
                , {field: 'print_price', title: 'ӡˢƽ������', width: 100, hide: 'true', sort: false,edit: "text"}
                , {field: 'print_price_total', title: 'ӡˢƽ���ܼ�', width: 100, hide: 'true', sort: false}
                
                , {field: 'poUantitys', title: '���ǰ��ͬ����', width: 120, hide: 'true', sort: false}
                , {field: 'poUantity', title: '������ͬ����', width: 120, hide: 'true', sort: false,edit: editext}
                , {field: 'proUantitys', title: '���ǰ����', width: 90, hide: '', sort: false}
                , {field: 'proUantity', title: '���������', width: 90, hide: '', sort: false,edit: editext}
                , {field: 'boxNums', title: '���ǰ����', width: 90, hide: '', sort: false}
                , {field: 'boxNum', title: '���������', width: 90, hide: '',edit: editext, sort: false}
                , {field: 'total', title: '���ǰ���', width: 90, hide: '', sort: false}
                , {field: 'proTotal', title: '�������', width: 90, hide: '',edit: editext, sort: false}
                , {field: 'inum', title: '��������', width: 90, hide: '', sort: false, edit: '',totalRow:'true'}
                , {field: 'onum', title: '��������', width: 90, hide: '', sort: false, edit: '',totalRow:'true'}
                , {field: 'poPrice2', title: '���ﵥ��', width: 100, hide: "", sort: false}
                , {field: 'zs_zjz', title: '�����ܾ���', width: 100, hide: "", sort: false,totalRow:'true'}
                , {field: 'zs_zmz', title: '������ë��', width: 100, hide: "", sort: false,totalRow:'true'}
                , {field: 'poPrices', title: '���ǰ��ͬ����', width: 150, hide: true, sort: false}
                , {field: 'poPrice', title: '������ͬ����', width: 150, hide: true, sort: false}
                , {field: 'proPrices', title:'���ǰ��������', width: 150, hide: true, sort: false}
                , {field: 'proPrice', title: '�������������', width: 150, hide: true, sort: false}
                , {field: 'total', title: '���С��', width: 90, hide: true, sort: false, totalRow: ''}
                , {field: 'baoZhuangMS', title: '��װ��ʽ', width: 90, hide: '', sort: '', edit: ''}
                , {field: 'tintd', title: '�ھ�MM', width: 90, hide: '', sort: ''}
                , {field: 'tthick', title: '�ܱں��MM', width: 90, hide: '', sort: ''}
                , {field: 'tdf1', title: '�Ƿ�ӡˢ', width: 90, hide: '', sort: ''}
                , {field: 'desc', title: '����Ҫ��', width: 90, hide: '', sort: '', edit: ''}
                , {field: 'imaterial', title: '�������', width: 90, hide: '', sort: ''}
                , {field: 'icolor', title: '������ɫ', width: 90, hide: '', sort: ''}
                , {field: 'omaterial', title: '�������', width: 90, hide: '', sort: ''}
                , {field: 'ocolor', title: '������ɫ', width: 90, hide: '', sort: ''}
                , {field: 'tuoPan', title: '����', width: 90, hide: '', sort: ''}
                , {field: 'tpdesc', title: '��������Ҫ��', width: 90, hide: '', sort: '', edit: ''}
                , {fixed: 'right', width: 200, align: 'center', toolbar: '#tab1Bar'}
            ]];

            var showMap={
                1:["poUantitys","poUantity"],
                2:["poPrices","poPrice","proPrices","proPrice"],
                3:["print_width","print_price","print_price_total"],
            }

            if(store["data"]["flowData"]["info"]["flowType"]=="���۱��"){
                //cols[0][22]={field: 'boxNum', title: '���������', width: 90, hide: '', sort: false};
               // cols[0][24]={field: 'boxNum', title: '���������', width: 90, hide: '', sort: false};
                 
       
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
                 
            }else if(store["data"]["flowData"]["info"]["flowType"]=="�������"){
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
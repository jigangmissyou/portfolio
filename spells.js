layui.define(['utils','laydate','customer_cols'],function(exports){ 
  var obj = {
    
    data:{
       dataInfo:{}
    },
      
    initData: function(store){
        var router = layui.router();//����·����
        this.data.dataInfo['id']=typeof(router.search.id)!="undefined"?router.search.id:0;  //��ȡҳ�����
        this.data.dataInfo['bill_id']=typeof(router.search.bill_id)!="undefined"?router.search.bill_id:0;  //��ȡҳ�����

        if(this.data.dataInfo['id']){
            this.data["spellsInfo"]=layui.utils.post('sql_manage_api',{"unique_code":"spells_list",id:this.data.dataInfo['id'],limit:1});
            if(this.data["spellsInfo"]&&this.data["spellsInfo"].length!=0){
                this.data["spellsInfo"]["spell_list"]=JSON.parse(this.data["spellsInfo"]["spell_list"]);
                this.data.dataInfo=this.data["spellsInfo"];
                store["data"]["spell_list"]=this.data.dataInfo["spell_list"]; 
            }
        }

        //��ȡ��������
        var initBillInfo =layui.utils.post('init_bill_info',{id:this.data.dataInfo['bill_id']});//���ض�����Ϣ
        var cabinetNum=[];
        cabinetNum.push(JSON.parse(initBillInfo["bill_info"]["cabinet_num"]));
        store["data"]["cabinetNum"]=cabinetNum;
        store["data"]["cabinet_info"]=initBillInfo["cabinet_info"];

        //��ȡ��������ͳ��
        this.data['billTotal'] = layui.utils.post('sql_manage_api',{"unique_code":"spells_bill_info",id:this.data.dataInfo['bill_id'],limit:1});

        this.data.dataInfo["tj"]=this.data['billTotal']["bill_tj"];
        this.data.dataInfo["mz"]=this.data['billTotal']["bill_mz"];
        this.data.dataInfo["bill_num"]=this.data['billTotal']["bill_num"];
        this.data.dataInfo["customer_short"]=this.data['billTotal']["customer_short"];
        this.data.dataInfo["order_nums"]=this.data['billTotal']["order_nums"];
        store["dataModel"]["accountCtr"]=this.data.dataInfo; 
        
        layui.utils.initTpl("form_tmp","#form_box",store);  
        setTimeout(function(){
            layui.form.render();//��Ⱦ�������ؼ�
            layui.form.val("form-account-set", store["dataModel"]["accountCtr"]);//����ֵ
        },500);
    },
    
    /**
     * �������
     * @param {type} obj
     * @param {type} store
     * @returns {Boolean}
     */
    saveData:function(obj,store){
        if(typeof(store["data"]["spell_list"])=="undefined"||store["data"]["spell_list"].length==0){
            return {status:500,msg:"�����ƴ����˾��",data:""};
        }

        obj.field["create_user"]=store['data']['user']["user_name"];
        obj.field["creator_id"]=store['data']['user']["user_id"];
        obj.field["unique_code"]="spells_update";
        obj.field["spell_list"]=layui.utils.jsonEncode(store["data"]["spell_list"]);
        var retInfo=layui.utils.post('sql_manage_api',obj.field);
        layui.form.val("form-account-set", {id:retInfo['data']['ret1']});//����ֵ
        return retInfo;
    },
    
    getListCols:function(data){
        data[0][1]["templet"]=function(d){
            var retInfo=d.bill_num;
            if(d.backPrcs==1){
                return "<span style='color:red' title='�˻�ԭ��"+d.comment+"' >"+d.bill_num+"&nbsp;(�˻�)</span>";
            }
            return retInfo;
        } 
        return data
    },
    
    
    reback:function(store){
        if(typeof(store['params']["showType"])!="undefined"){
            location.replace(layui.url_list[store['params']["showType"]]+"/showType="+store['params']["showType"]);
        }
    },
    
    
    
    initTab1:function(store){  
        var formData={
            "total_mz":parseFloat(store["dataModel"]["accountCtr"]["mz"]),
            "total_tj":parseFloat(store["dataModel"]["accountCtr"]["tj"])
        }
        //������ë�����
        if(typeof(store["data"]["spell_list"])!="undefined"&&store["data"]["spell_list"].length>0){
            for(var index in store["data"]["spell_list"]){
                var item=store["data"]["spell_list"][index];  
                formData["total_mz"]+=parseFloat(item["mz"]);
                formData["total_tj"]+=parseFloat(item["tj"]);
            }
        }

        layui.form.val("form-account-set", formData);//����ֵ
        //��ʼ�������б�
        return  layui.table.render({elem: '#tab1',data:store["data"]["spell_list"],totalRow: true, cols: layui.customer_cols["spell_list"],toolbar: '#tab1Bar1',text: {  none: '�����������'}});
    },
    
    initTab2:function(store){
        return layui.table.render({elem: '#tab2', data:store["data"]['cabinetNum'],cols:layui.customer_cols['cabinet_num'] ,toolbar: '<div>������Ϣ</div>'});
    },
    
    selectBill:function(store){
        location.replace( "/general/erp4/view/provebill/spells/add.php?/#/bill_id=" + store["data"]['bill_id']);
    }
    
   
  };
 

  exports('spells', obj);
});    
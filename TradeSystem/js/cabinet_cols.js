layui.define(function(exports){


    var field_data = [];
    var res = layui.utils.post('isFieldHide',{'table_type':9},'manual_fun');
    if (res.code == 200){
        field_data =  res.data;
    }
    var field_all = [
        {type: 'checkbox', width:'', align:'center', fixed: 'left', toolbar: '#tab1Bar'},
        {field:'id', title: '���', width:80,hide:true},


        {field:'invoice_num', title: '��Ʊ��', fixed: 'left', width:150},
        {field:'orderNums', title: '������',fixed: 'left', width:200,
            templet: function(d){
                var retInfo=d.orderNums;
                if(d.backPrcs==1){
                    return "<span style='color:red'>"+d.orderNums+"&nbsp;(�˻�)</span>";
                }
                return retInfo;
            }
        },
        {field:'customer_short', title: '�ͻ����',fixed: 'left', width:150},
        {field:'customer_country', title: '����', width:150},

        {field:'cabinet_num', title: '����', width:150},
        {field:'appoint_info', title: '����˾����', width:150},
        {field:'address', title: 'װ���ַ',width:100},
        {field:'cabinet_time', title: 'Ԥ��װ������',width:100},
        {field:'real_cabinet_time', title: 'ʵ��װ������',width:100},
        {field:'ship_time', title: 'Ԥ�ƴ���',width:100},
        {field:'real_ship_time', title: 'ʵ�ʴ���',width:100},
        {field:'etd_date', title: '����ʱ��',width:100},

        {field:'is_ontime', title: '�Ƿ�׼ʱ����',width:100},

        {field:'ship_confirm', title: '����/����˾��ȷ��',width:100},
        {field:'so_confirm', title: 'SO�Ƿ�ȷ��',width:100},
        {field:'is_export_invoice', title: '��Ʊ�Ƿ񵼳�',width:100,templet: function(d){
                var is_export_invoice=d.is_export_invoice;
                if(d.is_export_invoice=="��"){
                    return "<span style='color:blue'>"+d.is_export_invoice+"</span>";
                }
                return is_export_invoice;
            }},
       /* {field:'is_ok', title: '���ص���ί��Э���Ƿ��ϴ�',width:100,templet: function(d){
                var customs_agreement=d.customs_agreement;
                if(d.customs_agreement!=""&&d.customs_declaration!=""){
                    return "��";
                }else{
                    return "<span style='color:blue'>��</span>";
                }
                //return is_export_invoice;
            }},*/
        {field:'so_info', title: 'SO��Ϣ',width:150},
        {field:'no_so_cause_new', title: 'SOδȷ��ԭ��',width:150},
        {field:'load_type', title: 'װ������',width:100},
        {field:'source_region', title: '���ڻ�Դ��',width:150},
        {field:'create_time', title: '�Ƶ�ʱ��',width:150 },

        {field:'flowPrcs', title: '����',width:150},
        {field:'prcsFlag', title: '״̬',width:100},

        {field:'order_users', title: '������Ա',width:150},
        {field:'bill_num', title: '����֪ͨ����', width:250,
            templet: function(d){
                var retInfo=d.bill_num;
                if(d.backPrcs==1){
                    return "<span style='color:red' title='�˻�ԭ��"+d.comment+"' >"+d.bill_num+"&nbsp;(�˻�)</span>";
                }
                return retInfo;
            }
        },
        {field: 'td_time', title: '�ᵥ�ϴ�ʱ��',width:150},
        {fixed: 'right', align:'center',width:480, toolbar: '#tab1Bar'}
    ];
    field_data.forEach(function (value, index, array) {
        if (value['field'] && value['type'] == ''){
            field_all.forEach(function (v,i,a) {
                if (field_all[i]['field'] == value['field']){
                    field_all[i]['hide'] = 'true';
                }
            })
        }
    })
    var obj = {
        'cabinet_list':[field_all],
        'cabinet_list_plan':[[
                
            {type: 'checkbox', width:'', align:'center', toolbar: '#tab1Bar'},   
            {field:'id', title: '���', width:80},
            {field:'bill_num', title: '����֪ͨ����', width:250,
                templet: function(d){
                    var retInfo=d.bill_num;
                    if(d.backPrcs==1){
                        return "<span style='color:red' title='�˻�ԭ��"+d.comment+"' >"+d.bill_num+"&nbsp;(�˻�)</span>";
                    }
                    return retInfo;
                }
            },


            {field:'orderNums', title: '������', width:200},
            {field:'customer_country', title: '����', width:150},
            {field:'customer_short', title: '�ͻ����', width:150},
            {field:'cabinet_time', title: 'װ������',width:100},
            {field:'ship_time', title: 'Ԥ�ƴ���',width:100},
            {field:'address', title: 'װ���ַ',width:200},
           
  
            {field:'create_time', title: '�Ƶ�ʱ��',width:150 },
            
            {field:'flowPrcs', title: '����',width:150},
            {field:'prcsFlag', title: '״̬',width:100,hide:true},
            
            {field:'order_users', title: '������Ա',width:150},
            {field:'beginUser_name', title: 'װ���Ƶ���',width:150},

            {fixed: 'right', align:'center',width:250, toolbar: '#tab1Bar'}
        ]],
        'cabinet_list_Doc':[[

            // {type: 'checkbox', width:'', align:'center', toolbar: '#tab1Bar'},
            {field:'id', title: '���', width:80,hide:'true'},
            // {field:'bill_num', title: '����֪ͨ����', width:250,
            //     templet: function(d){
            //         var retInfo=d.bill_num;
            //         if(d.backPrcs==1){
            //             return "<span style='color:red' title='�˻�ԭ��"+d.comment+"' >"+d.bill_num+"&nbsp;(�˻�)</span>";
            //         }
            //         return retInfo;
            //     }
            // },
            {field:'ship_time', title: 'Ԥ�ƴ���',width:100},
            {field:'real_ship_time', title: 'ʵ�ʴ���',width:100},
            // {field:'orderNums', title: '������', width:200},
            {field:'invoice_num', title: '��Ʊ��', width:200},
            // {field:'customer_country', title: '����', width:100},
            {field:'customer_short', title: '�ͻ����', width:150},
            {field:'coin_type', title: '����', width:60},
            {field:'total', title: '���ս��', width:100},
            // {field:'cabinet_time', title: 'װ������',width:100},

            // {field:'address', title: 'װ���ַ',width:200},


            // {field:'create_time', title: '�Ƶ�ʱ��',width:150 },

            // {field:'flowPrcs', title: '����',width:150},
            // {field:'prcsFlag', title: '״̬',width:100,hide:true},

            // {field:'order_users', title: '������Ա',width:100},

            {fixed: 'right', align:'center',width:250, toolbar: '#tab1Bar'}
        ]],
        'cabinet_list_other':[[
            {type: 'radio', width:'', align:'center', fixed: 'left'},
            {field:'id', title: '���', width:80,hide:true},
            {field:'invoice_num', title: '��Ʊ��', fixed: 'left', width:150},
            {field:'orderNums', title: '������',fixed: 'left', width:200,
                templet: function(d){
                    var retInfo=d.orderNums;
                    if(d.backPrcs==1){
                        return "<span style='color:red'>"+d.orderNums+"&nbsp;(�˻�)</span>";
                    }
                    return retInfo;
                }
            },
            {field:'customer_short', title: '�ͻ����',fixed: 'left', width:150},
            {field:'customer_country', title: '����', width:150},

            {field:'cabinet_num', title: '����', width:150},
            {field:'appoint_info', title: '����˾����', width:150},
            {field:'address', title: 'װ���ַ',width:100},
            {field:'cabinet_time', title: 'Ԥ��װ������',width:100},
            {field:'real_cabinet_time', title: 'ʵ��װ������',width:100},
            {field:'ship_time', title: 'Ԥ�ƴ���',width:100},
            {field:'real_ship_time', title: 'ʵ�ʴ���',width:100},
            {field:'etd_date', title: '����ʱ��',width:100},

            {field:'is_ontime', title: '�Ƿ�׼ʱ����',width:100},

            {field:'ship_confirm', title: '����/����˾��ȷ��',width:100},
            {field:'so_confirm', title: 'SO�Ƿ�ȷ��',width:100},
            {field:'is_export_invoice', title: '��Ʊ�Ƿ񵼳�',width:100,templet: function(d){
                    var is_export_invoice=d.is_export_invoice;
                    if(d.is_export_invoice=="��"){
                        return "<span style='color:blue'>"+d.is_export_invoice+"</span>";
                    }
                    return is_export_invoice;
                }},
            /* {field:'is_ok', title: '���ص���ί��Э���Ƿ��ϴ�',width:100,templet: function(d){
                     var customs_agreement=d.customs_agreement;
                     if(d.customs_agreement!=""&&d.customs_declaration!=""){
                         return "��";
                     }else{
                         return "<span style='color:blue'>��</span>";
                     }
                     //return is_export_invoice;
                 }},*/
            {field:'so_info', title: 'SO��Ϣ',width:150},
            {field:'no_so_cause_new', title: 'SOδȷ��ԭ��',width:150},
            {field:'load_type', title: 'װ������',width:100},
            {field:'source_region', title: '���ڻ�Դ��',width:150},
            {field:'create_time', title: '�Ƶ�ʱ��',width:150 },

            {field:'flowPrcs', title: '����',width:150},
            {field:'prcsFlag', title: '״̬',width:100},

            {field:'order_users', title: '������Ա',width:150},
            {field:'bill_num', title: '����֪ͨ����', width:250,
                templet: function(d){
                    var retInfo=d.bill_num;
                    if(d.backPrcs==1){
                        return "<span style='color:red' title='�˻�ԭ��"+d.comment+"' >"+d.bill_num+"&nbsp;(�˻�)</span>";
                    }
                    return retInfo;
                }
            },
            {field: 'td_time', title: '�ᵥ�ϴ�ʱ��',width:150}
           ] ],
        'get_cabinet_list':function (colsType){
            var retArray=this.cabinet_list;
            switch(colsType){
                case 1: 
                    retArray = this.cabinet_list_plan;
                    break;
                case 2:
                    retArray = this.cabinet_list_Doc;
                    break;
            }
            return retArray;
        }

    };

    exports('cabinet_cols', obj);
});   
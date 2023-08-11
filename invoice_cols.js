layui.define(function(exports){ 
  var obj = {
        'coin_unit':{"��Ԫ":"usd","�����":"rmb","ŷԪ":"eur","�ձ�":"jpy"},
        'invoice_list':[[
            {field:'id', title: 'ID', width:80,hide:'', sort: "", fixed: 'left'},
            {field:'is_auto', title: '�Ƿ��Զ�����', width:100,hide:'', sort: "",fixed: 'left',templet: function(d){
                    return (d.is_auto=="0")?"��":"��";
                }},
            {field:'order_num', title: '���ڶ�����', width:200,hide:'', sort: "",fixed: 'left'},
            {field:'sales_num', title: '��������', width:200,hide:'', sort: "",fixed: 'left'},
            {field:'customer_short', title: '�ͻ�����', width:150,hide:'', sort: "",fixed: 'left'},
            {field:'customer_code', title: '�ͻ�����', width:100,hide:'', sort: ""},
            {field:'trade_type', title: 'ó������', width:100,hide:'', sort: ""},
            {field:'pay_type', title: '���㷽ʽ', width:100,hide:'', sort: ""},
            {field:'account_date', title: '������', width:100,hide:'', sort: ""},
            {field:'expire_date', title: '������', width:100,hide:'', sort: ""},
            {field:'bill_lading_num', title: '�ᵥ��', width:200,hide:'', sort: ""},
            
            {field:'account_term', title: '����', width:'',hide:100, sort: ""},
            
            {field:'coin_type', title: '����', width:100,hide:100, sort: ""},
            
            {field:'bill_lading_date', title: '�ᵥ����', width:100,hide:'', sort: ""},
            {field:'payment_term', title: '��������', width:160,hide:'', sort: "" },
            {field:'export_num', title: '���ڱ��ص���', width:200,hide:'', sort: "" },
            {field:'write_off_num', title: '��������', width:200,hide:'', sort: "" },
            {field:'rtype', title: '�ո���Э��', width:100,hide:'', sort: "" },
            {field:'total_money', title: 'ԭ���ܽ��', width:200,hide:'', sort: "" },
            {field:'cabinet_date', title: 'װ������', width:100,hide:'', sort: "" },
            {field:'arrival_date', title: '��������', width:100,hide:'', sort: "" },
            
            {field:'export_date', title: '��������', width:100,hide:'', sort: "" },
            {field:'pre_arrival_date', title: 'Ԥ�Ƶ�������', width:100,hide:'', sort: "" },
            {field:'cabinet_address', title: 'װ��ص�', width:100,hide:'', sort: "" },
            {field:'start_port', title: '���˸�', width:'',hide:100, sort: "" },
            {field:'end_port', title: 'Ŀ�ĸ�', width:'',hide:100, sort: "" },
            {field:'create_time', title: '��������', width:'150', sort: "" },
            {field:'create_user', title: '�Ƶ���', width:'',hide:150, sort: "" },
            {fixed: 'right', width:200, align:'center',hide:'', toolbar: '#tab1Bar'}
            
        ]],
    
        'invoice_items':[[
                
            {type: 'checkbox', width:'', align:'center', toolbar: '#tab1Bar',fixed: 'left'},    
            {field:'id', title: 'ID', width:80,hide:'', sort: "", fixed: 'left'},
            {field:'orderNum', title: '������', width:200,hide:'', sort: "", fixed: 'left', totalRowText: '�ϼƣ�'},
            {field:'u8Code', title: '�������', width:100,hide:'', sort: "", fixed: 'left'},

            {field:'cInvName', title: '�������', width:200,hide:'', sort: "", fixed: 'left'},
            {field:'hs_code', title: 'hs����', width:100,hide:'', sort: ""},

            {field:'cInvCode', title: '����ͺ�',event:'cInvCode', width:'200',hide:''},
            
            {field:'proUnit', title: '������λ', width:100,hide:'' },
            {field:'invoice_num', title: '����', width:100,hide:'',totalRow: true},            
            {field:'invoice_price', title: 'ԭ�ҵ���', width:100,hide:''},
            {field:'invoice_total', title: 'ԭ�ҽ��', width:100,hide:'',edit:'text',totalRow: true},
            
            /*{field:'invoice_total', title: 'ԭ����˰���', width:100,hide:'' ,totalRow: true},  */
            {field:'tax', title: 'ԭ��˰��', width:100,hide:'',totalRow: true },
            
            {field:'local_price', title: '���ҵ���', width:100,hide:'' },
            /*{field:'local_price', title: '������˰����', width:100,hide:'' },*/
            {field:'local_total', title: '���ҽ��', width:100,hide:'' ,totalRow: true},
            {field:'adjust_money', title: '�������', width:100,hide:'' ,totalRow: true},
            {field:'freight', title: '�˷�', width:100,hide:'' ,totalRow: true},
            {field:'insure', title: '���շ�', width:100,hide:'' ,totalRow: true},
            {field:'fob_total', title: 'FOB���', width:100,hide:'' ,totalRow: true}
        
        ]],
        'split_items':[[
                
            {type: 'checkbox', width:'', align:'center', toolbar: '#tab1Bar',fixed: 'left'},    
            {field:'id', title: 'ID', width:80,hide:'', sort: "", fixed: 'left'},
            {field:'orderNum', title: '������', width:200,hide:'', sort: "", fixed: 'left', totalRowText: '�ϼƣ�'},
            {field:'u8Code', title: '�������', width:100,hide:'', sort: "", fixed: 'left'},
            
            {field:'cInvName', title: '�������', width:200,hide:'', sort: "", fixed: 'left'},
       
            {field:'cInvCode', title: '����ͺ�',event:'cInvCode', width:'200',hide:''},
            
            {field:'proUnit', title: '������λ', width:100,hide:'' },
            {field:'invoice_num', title: '����', width:100,hide:'',edit:"text" },            
            {field:'invoice_price', title: 'ԭ�ҵ���', width:100,hide:'' },
            {field:'invoice_total', title: 'ԭ�ҽ��', width:100,hide:'' ,totalRow: true},
            
            /*{field:'invoice_total', title: 'ԭ����˰���', width:100,hide:'' ,totalRow: true},  */
            {field:'tax', title: 'ԭ��˰��', width:100,hide:'',totalRow: true },
            
            {field:'local_price', title: '���ҵ���', width:100,hide:'' },
            /*{field:'local_price', title: '������˰����', width:100,hide:'' },*/
            {field:'local_total', title: '���ҽ��', width:100,hide:'' ,totalRow: true},
            {field:'adjust_money', title: '�������', width:100,hide:'' ,totalRow: true},
            {field:'freight', title: '�˷�', width:100,hide:'' ,totalRow: true},
            {field:'insure', title: '���շ�', width:100,hide:'' ,totalRow: true},
            {field:'fob_total', title: 'FOB���', width:100,hide:'' ,totalRow: true}
        
        ]]
        
        
  };

  exports('invoice_cols', obj);
});   
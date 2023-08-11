layui.define(function(exports){ 
  var obj = {
        'coin_unit':{"美元":"usd","人民币":"rmb","欧元":"eur","日币":"jpy"},
        'invoice_list':[[
            {field:'id', title: 'ID', width:80,hide:'', sort: "", fixed: 'left'},
            {field:'is_auto', title: '是否自动核销', width:100,hide:'', sort: "",fixed: 'left',templet: function(d){
                    return (d.is_auto=="0")?"否":"是";
                }},
            {field:'order_num', title: '出口订单号', width:200,hide:'', sort: "",fixed: 'left'},
            {field:'sales_num', title: '销货单号', width:200,hide:'', sort: "",fixed: 'left'},
            {field:'customer_short', title: '客户名称', width:150,hide:'', sort: "",fixed: 'left'},
            {field:'customer_code', title: '客户编码', width:100,hide:'', sort: ""},
            {field:'trade_type', title: '贸易术语', width:100,hide:'', sort: ""},
            {field:'pay_type', title: '结算方式', width:100,hide:'', sort: ""},
            {field:'account_date', title: '立账日', width:100,hide:'', sort: ""},
            {field:'expire_date', title: '到期日', width:100,hide:'', sort: ""},
            {field:'bill_lading_num', title: '提单号', width:200,hide:'', sort: ""},
            
            {field:'account_term', title: '账期', width:'',hide:100, sort: ""},
            
            {field:'coin_type', title: '币种', width:100,hide:100, sort: ""},
            
            {field:'bill_lading_date', title: '提单日期', width:100,hide:'', sort: ""},
            {field:'payment_term', title: '付款条件', width:160,hide:'', sort: "" },
            {field:'export_num', title: '出口报关单号', width:200,hide:'', sort: "" },
            {field:'write_off_num', title: '核销单号', width:200,hide:'', sort: "" },
            {field:'rtype', title: '收付款协议', width:100,hide:'', sort: "" },
            {field:'total_money', title: '原币总金额', width:200,hide:'', sort: "" },
            {field:'cabinet_date', title: '装柜日期', width:100,hide:'', sort: "" },
            {field:'arrival_date', title: '进港日期', width:100,hide:'', sort: "" },
            
            {field:'export_date', title: '出口日期', width:100,hide:'', sort: "" },
            {field:'pre_arrival_date', title: '预计到港日期', width:100,hide:'', sort: "" },
            {field:'cabinet_address', title: '装柜地点', width:100,hide:'', sort: "" },
            {field:'start_port', title: '起运港', width:'',hide:100, sort: "" },
            {field:'end_port', title: '目的港', width:'',hide:100, sort: "" },
            {field:'create_time', title: '创建日期', width:'150', sort: "" },
            {field:'create_user', title: '制单人', width:'',hide:150, sort: "" },
            {fixed: 'right', width:200, align:'center',hide:'', toolbar: '#tab1Bar'}
            
        ]],
    
        'invoice_items':[[
                
            {type: 'checkbox', width:'', align:'center', toolbar: '#tab1Bar',fixed: 'left'},    
            {field:'id', title: 'ID', width:80,hide:'', sort: "", fixed: 'left'},
            {field:'orderNum', title: '订单号', width:200,hide:'', sort: "", fixed: 'left', totalRowText: '合计：'},
            {field:'u8Code', title: '存货编码', width:100,hide:'', sort: "", fixed: 'left'},

            {field:'cInvName', title: '存货名称', width:200,hide:'', sort: "", fixed: 'left'},
            {field:'hs_code', title: 'hs编码', width:100,hide:'', sort: ""},

            {field:'cInvCode', title: '规格型号',event:'cInvCode', width:'200',hide:''},
            
            {field:'proUnit', title: '计量单位', width:100,hide:'' },
            {field:'invoice_num', title: '数量', width:100,hide:'',totalRow: true},            
            {field:'invoice_price', title: '原币单价', width:100,hide:''},
            {field:'invoice_total', title: '原币金额', width:100,hide:'',edit:'text',totalRow: true},
            
            /*{field:'invoice_total', title: '原币无税金额', width:100,hide:'' ,totalRow: true},  */
            {field:'tax', title: '原币税额', width:100,hide:'',totalRow: true },
            
            {field:'local_price', title: '本币单价', width:100,hide:'' },
            /*{field:'local_price', title: '本币无税单价', width:100,hide:'' },*/
            {field:'local_total', title: '本币金额', width:100,hide:'' ,totalRow: true},
            {field:'adjust_money', title: '调整金额', width:100,hide:'' ,totalRow: true},
            {field:'freight', title: '运费', width:100,hide:'' ,totalRow: true},
            {field:'insure', title: '保险费', width:100,hide:'' ,totalRow: true},
            {field:'fob_total', title: 'FOB金额', width:100,hide:'' ,totalRow: true}
        
        ]],
        'split_items':[[
                
            {type: 'checkbox', width:'', align:'center', toolbar: '#tab1Bar',fixed: 'left'},    
            {field:'id', title: 'ID', width:80,hide:'', sort: "", fixed: 'left'},
            {field:'orderNum', title: '订单号', width:200,hide:'', sort: "", fixed: 'left', totalRowText: '合计：'},
            {field:'u8Code', title: '存货编码', width:100,hide:'', sort: "", fixed: 'left'},
            
            {field:'cInvName', title: '存货名称', width:200,hide:'', sort: "", fixed: 'left'},
       
            {field:'cInvCode', title: '规格型号',event:'cInvCode', width:'200',hide:''},
            
            {field:'proUnit', title: '计量单位', width:100,hide:'' },
            {field:'invoice_num', title: '数量', width:100,hide:'',edit:"text" },            
            {field:'invoice_price', title: '原币单价', width:100,hide:'' },
            {field:'invoice_total', title: '原币金额', width:100,hide:'' ,totalRow: true},
            
            /*{field:'invoice_total', title: '原币无税金额', width:100,hide:'' ,totalRow: true},  */
            {field:'tax', title: '原币税额', width:100,hide:'',totalRow: true },
            
            {field:'local_price', title: '本币单价', width:100,hide:'' },
            /*{field:'local_price', title: '本币无税单价', width:100,hide:'' },*/
            {field:'local_total', title: '本币金额', width:100,hide:'' ,totalRow: true},
            {field:'adjust_money', title: '调整金额', width:100,hide:'' ,totalRow: true},
            {field:'freight', title: '运费', width:100,hide:'' ,totalRow: true},
            {field:'insure', title: '保险费', width:100,hide:'' ,totalRow: true},
            {field:'fob_total', title: 'FOB金额', width:100,hide:'' ,totalRow: true}
        
        ]]
        
        
  };

  exports('invoice_cols', obj);
});   
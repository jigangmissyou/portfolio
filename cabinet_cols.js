layui.define(function(exports){


    var field_data = [];
    var res = layui.utils.post('isFieldHide',{'table_type':9},'manual_fun');
    if (res.code == 200){
        field_data =  res.data;
    }
    var field_all = [
        {type: 'checkbox', width:'', align:'center', fixed: 'left', toolbar: '#tab1Bar'},
        {field:'id', title: '项号', width:80,hide:true},


        {field:'invoice_num', title: '发票号', fixed: 'left', width:150},
        {field:'orderNums', title: '订单号',fixed: 'left', width:200,
            templet: function(d){
                var retInfo=d.orderNums;
                if(d.backPrcs==1){
                    return "<span style='color:red'>"+d.orderNums+"&nbsp;(退回)</span>";
                }
                return retInfo;
            }
        },
        {field:'customer_short', title: '客户简称',fixed: 'left', width:150},
        {field:'customer_country', title: '国家', width:150},

        {field:'cabinet_num', title: '柜型', width:150},
        {field:'appoint_info', title: '船公司名称', width:150},
        {field:'address', title: '装柜地址',width:100},
        {field:'cabinet_time', title: '预计装柜日期',width:100},
        {field:'real_cabinet_time', title: '实际装柜日期',width:100},
        {field:'ship_time', title: '预计船期',width:100},
        {field:'real_ship_time', title: '实际船期',width:100},
        {field:'etd_date', title: '到港时间',width:100},

        {field:'is_ontime', title: '是否准时开船',width:100},

        {field:'ship_confirm', title: '货代/船公司已确认',width:100},
        {field:'so_confirm', title: 'SO是否确认',width:100},
        {field:'is_export_invoice', title: '发票是否导出',width:100,templet: function(d){
                var is_export_invoice=d.is_export_invoice;
                if(d.is_export_invoice=="否"){
                    return "<span style='color:blue'>"+d.is_export_invoice+"</span>";
                }
                return is_export_invoice;
            }},
       /* {field:'is_ok', title: '报关单和委托协议是否上传',width:100,templet: function(d){
                var customs_agreement=d.customs_agreement;
                if(d.customs_agreement!=""&&d.customs_declaration!=""){
                    return "是";
                }else{
                    return "<span style='color:blue'>否</span>";
                }
                //return is_export_invoice;
            }},*/
        {field:'so_info', title: 'SO信息',width:150},
        {field:'no_so_cause_new', title: 'SO未确认原因',width:150},
        {field:'load_type', title: '装载类型',width:100},
        {field:'source_region', title: '境内货源地',width:150},
        {field:'create_time', title: '制单时间',width:150 },

        {field:'flowPrcs', title: '步骤',width:150},
        {field:'prcsFlag', title: '状态',width:100},

        {field:'order_users', title: '跟单人员',width:150},
        {field:'bill_num', title: '订舱通知单号', width:250,
            templet: function(d){
                var retInfo=d.bill_num;
                if(d.backPrcs==1){
                    return "<span style='color:red' title='退回原因："+d.comment+"' >"+d.bill_num+"&nbsp;(退回)</span>";
                }
                return retInfo;
            }
        },
        {field: 'td_time', title: '提单上传时间',width:150},
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
            {field:'id', title: '项号', width:80},
            {field:'bill_num', title: '订舱通知单号', width:250,
                templet: function(d){
                    var retInfo=d.bill_num;
                    if(d.backPrcs==1){
                        return "<span style='color:red' title='退回原因："+d.comment+"' >"+d.bill_num+"&nbsp;(退回)</span>";
                    }
                    return retInfo;
                }
            },


            {field:'orderNums', title: '订单号', width:200},
            {field:'customer_country', title: '国家', width:150},
            {field:'customer_short', title: '客户简称', width:150},
            {field:'cabinet_time', title: '装柜日期',width:100},
            {field:'ship_time', title: '预计船期',width:100},
            {field:'address', title: '装柜地址',width:200},
           
  
            {field:'create_time', title: '制单时间',width:150 },
            
            {field:'flowPrcs', title: '步骤',width:150},
            {field:'prcsFlag', title: '状态',width:100,hide:true},
            
            {field:'order_users', title: '跟单人员',width:150},
            {field:'beginUser_name', title: '装柜制单人',width:150},

            {fixed: 'right', align:'center',width:250, toolbar: '#tab1Bar'}
        ]],
        'cabinet_list_Doc':[[

            // {type: 'checkbox', width:'', align:'center', toolbar: '#tab1Bar'},
            {field:'id', title: '项号', width:80,hide:'true'},
            // {field:'bill_num', title: '订舱通知单号', width:250,
            //     templet: function(d){
            //         var retInfo=d.bill_num;
            //         if(d.backPrcs==1){
            //             return "<span style='color:red' title='退回原因："+d.comment+"' >"+d.bill_num+"&nbsp;(退回)</span>";
            //         }
            //         return retInfo;
            //     }
            // },
            {field:'ship_time', title: '预计船期',width:100},
            {field:'real_ship_time', title: '实际船期',width:100},
            // {field:'orderNums', title: '订单号', width:200},
            {field:'invoice_num', title: '发票号', width:200},
            // {field:'customer_country', title: '国家', width:100},
            {field:'customer_short', title: '客户简称', width:150},
            {field:'coin_type', title: '币种', width:60},
            {field:'total', title: '保险金额', width:100},
            // {field:'cabinet_time', title: '装柜日期',width:100},

            // {field:'address', title: '装柜地址',width:200},


            // {field:'create_time', title: '制单时间',width:150 },

            // {field:'flowPrcs', title: '步骤',width:150},
            // {field:'prcsFlag', title: '状态',width:100,hide:true},

            // {field:'order_users', title: '跟单人员',width:100},

            {fixed: 'right', align:'center',width:250, toolbar: '#tab1Bar'}
        ]],
        'cabinet_list_other':[[
            {type: 'radio', width:'', align:'center', fixed: 'left'},
            {field:'id', title: '项号', width:80,hide:true},
            {field:'invoice_num', title: '发票号', fixed: 'left', width:150},
            {field:'orderNums', title: '订单号',fixed: 'left', width:200,
                templet: function(d){
                    var retInfo=d.orderNums;
                    if(d.backPrcs==1){
                        return "<span style='color:red'>"+d.orderNums+"&nbsp;(退回)</span>";
                    }
                    return retInfo;
                }
            },
            {field:'customer_short', title: '客户简称',fixed: 'left', width:150},
            {field:'customer_country', title: '国家', width:150},

            {field:'cabinet_num', title: '柜型', width:150},
            {field:'appoint_info', title: '船公司名称', width:150},
            {field:'address', title: '装柜地址',width:100},
            {field:'cabinet_time', title: '预计装柜日期',width:100},
            {field:'real_cabinet_time', title: '实际装柜日期',width:100},
            {field:'ship_time', title: '预计船期',width:100},
            {field:'real_ship_time', title: '实际船期',width:100},
            {field:'etd_date', title: '到港时间',width:100},

            {field:'is_ontime', title: '是否准时开船',width:100},

            {field:'ship_confirm', title: '货代/船公司已确认',width:100},
            {field:'so_confirm', title: 'SO是否确认',width:100},
            {field:'is_export_invoice', title: '发票是否导出',width:100,templet: function(d){
                    var is_export_invoice=d.is_export_invoice;
                    if(d.is_export_invoice=="否"){
                        return "<span style='color:blue'>"+d.is_export_invoice+"</span>";
                    }
                    return is_export_invoice;
                }},
            /* {field:'is_ok', title: '报关单和委托协议是否上传',width:100,templet: function(d){
                     var customs_agreement=d.customs_agreement;
                     if(d.customs_agreement!=""&&d.customs_declaration!=""){
                         return "是";
                     }else{
                         return "<span style='color:blue'>否</span>";
                     }
                     //return is_export_invoice;
                 }},*/
            {field:'so_info', title: 'SO信息',width:150},
            {field:'no_so_cause_new', title: 'SO未确认原因',width:150},
            {field:'load_type', title: '装载类型',width:100},
            {field:'source_region', title: '境内货源地',width:150},
            {field:'create_time', title: '制单时间',width:150 },

            {field:'flowPrcs', title: '步骤',width:150},
            {field:'prcsFlag', title: '状态',width:100},

            {field:'order_users', title: '跟单人员',width:150},
            {field:'bill_num', title: '订舱通知单号', width:250,
                templet: function(d){
                    var retInfo=d.bill_num;
                    if(d.backPrcs==1){
                        return "<span style='color:red' title='退回原因："+d.comment+"' >"+d.bill_num+"&nbsp;(退回)</span>";
                    }
                    return retInfo;
                }
            },
            {field: 'td_time', title: '提单上传时间',width:150}
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
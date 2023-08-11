layui.define(function(exports){ 
  var obj = {
        'gate_bill':[[
            {field:'id', title: '项号', width:80},
            {field:'product_code', title: '商品编号', width:300},
            {field:'product_specs', title: '商品名称及规格型号', width:200, templet: function(d){
                    return "<span>"+d.product_specs+"<br/>"+d.grade_name+"</span>"; 
                }, unresize: true
            },
            {field:'num', title: '数量以及单位',width:200, templet: function(d){
                    return "<span>"+d.u8_info.jz+d.unit+"</span>"; 
                }, unresize: true
            },
            {field:'total_price', title: '总价',width:100},
            {field:'coin_type', title: '币制',width:100},
            {field:'origin_country', title: '原产国（地区）',width:150},
            {field:'target_country', title: '最终目的国（地区）',width:200},
            {field:'source_region', title: '境内货源地',width:100},
            {field:'levy_exempt', title: '征免',width:100 },
            {title: '备注', align:'center',width:250,   templet: function(d){
                    if(d['u8_info']['no_real'].length>0){
                       return  "<span>理论毛净重:["+d['u8_info']['no_real'].join(",")+"]";
                    }
                    return "";  
                }, unresize: true
            }
        ]],
    
        'audit_bill':[[
            {field:'id', title: '项号', width:80},
            {field:'bill_num', title: '订舱单号', width:200},
            {field:'print_type_name', title: '打印类型', width:300}, 
            {field:'beginUserName', title: '提交人',width:150},
            {field:'beginTime', title: '提交时间',width:200},
            {field:'prcsFlag1', title: '当前状态', width:100}, 
            {field:'deliverUser', title: '审核人',width:150},
            {field:'deliverTime', title: '审核时间',width:200},
            {fixed: 'right', align:'center', toolbar: '#tab1Bar',width:300}
        ]],
        
  };

  exports('print_cols', obj);
});   
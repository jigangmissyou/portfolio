layui.define(function(exports){ 
    var obj = {
        'customer_list':[[
            {type: 'checkbox', width:70, align:'center', toolbar: '#tab1Bar'},
            {type:'numbers', title: '序号', width:80,hide:'', sort: true},
            {field: 'customer_code', title: 'U8客户编码',width:200}, 
            {field: 'customer_short', title: '客户简称',width:200}, 
            {field: 'customer_name', title: '客户名称',width:200}, 
            {field: 'customer_country', title: '客户国家',width:100},
            {field: 'customer_address', title: '客户地址',width:400},
        ]], 
    
        'spell_list':[[
            {type: 'checkbox', width:70, align:'center', toolbar: '#tab1Bar'},
            {type:'numbers', title: '序号', width:80,hide:'', sort: true},
            {field: 'company_name', title: '拼货家公司名称',width:300}, 
            {field: 'grade_name', title: '拼货品名',width:300,totalRowText: '合计：'}, 
            {field: 'mz', title: '拼货毛重',width:200 ,totalRow: true }, 
            {field: 'tj', title: '批货体积',width:200 ,totalRow: true },

            {field:'attachment', title:'报关附件', width:297, templet: function(d){
                if(typeof(d.attachment)!="undefined"){
                    return '<span>'+d.attachment["NAME"]+'</span>'; 
                }
                return '<span></span>'; 
            }, unresize: true ,event:'viewAtt'},

        ]],
    
        'cabinet_num': [[
            {field:'cabinet_type',width:100, title: '柜型'},
            {field:'cabinet1', title: '20尺柜', width:200,edit:''},
            {field:'cabinet2', title: '40尺平柜', width:200,edit:''},
            {field:'cabinet3', title: '40尺高柜', width:200,edit:''},
            {field:'cabinet4', title: '45尺柜', width:200,edit:''}, 
            {field:'cabinet5', title: 'NOR平柜', width:200,edit:''}, 
            {field:'cabinet6', title: 'NOR高柜', width:200,edit:''}, 
           
        ]], 

    };

    exports('customer_cols', obj);
});   
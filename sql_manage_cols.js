layui.define(function(exports){ 
    var obj = {
        'sql_list':[[
            {field:'id', title: '项号', width:80},
            {field:'name', title: '名称'},
            {field:'unique_code', title: '唯一标识码'},
            {field:'type_name', title: '类型'},
            {field: 'create_time', title: '创建时间'},
            {fixed: 'right', align:'center',width:250, toolbar: '#tab1Bar'}
        ]],
    };

    exports('sql_manage_cols', obj);
});   
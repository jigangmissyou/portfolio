layui.define(function(exports){ 
    var obj = {
        'sql_list':[[
            {field:'id', title: '���', width:80},
            {field:'name', title: '����'},
            {field:'unique_code', title: 'Ψһ��ʶ��'},
            {field:'type_name', title: '����'},
            {field: 'create_time', title: '����ʱ��'},
            {fixed: 'right', align:'center',width:250, toolbar: '#tab1Bar'}
        ]],
    };

    exports('sql_manage_cols', obj);
});   
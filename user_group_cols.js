layui.define(function(exports){ 
    var obj = {
        'user_group_list':[[
            {field:'id', title: '项号', width:80},
            {field:'name', title: '分组名称', width:200},
            {field:'deptTexts', title: '分组部门', width:300},
            {field:'group_user', title: '分组人员', width:500},
            {field:'create_time', title: '创建日期', width:150},
            {field:'create_user', title: '创建人', width:150},
            {fixed: 'right', align:'center',width:250, toolbar: '#tab1Bar'}
        ]],
    
        'user_group_list1':[[
            {field:'id', title: 'ID', width:80},
      

            {field:'user_texts', title: '被替换人', width:500},
            {field:'user_texts1', title: '替换人员列表', width:500},
            {field:'create_time', title: '创建日期', width:150},
            {field:'create_user', title: '创建人', width:150},
            {fixed: 'right', align:'center',width:250, toolbar: '#tab1Bar'}
        ]],
    };

    exports('user_group_cols', obj);
});   
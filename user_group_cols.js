layui.define(function(exports){ 
    var obj = {
        'user_group_list':[[
            {field:'id', title: '���', width:80},
            {field:'name', title: '��������', width:200},
            {field:'deptTexts', title: '���鲿��', width:300},
            {field:'group_user', title: '������Ա', width:500},
            {field:'create_time', title: '��������', width:150},
            {field:'create_user', title: '������', width:150},
            {fixed: 'right', align:'center',width:250, toolbar: '#tab1Bar'}
        ]],
    
        'user_group_list1':[[
            {field:'id', title: 'ID', width:80},
      

            {field:'user_texts', title: '���滻��', width:500},
            {field:'user_texts1', title: '�滻��Ա�б�', width:500},
            {field:'create_time', title: '��������', width:150},
            {field:'create_user', title: '������', width:150},
            {fixed: 'right', align:'center',width:250, toolbar: '#tab1Bar'}
        ]],
    };

    exports('user_group_cols', obj);
});   
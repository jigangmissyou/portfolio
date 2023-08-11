layui.define(function(exports){ 
    var obj = {
        'customer_list':[[
            {type: 'checkbox', width:70, align:'center', toolbar: '#tab1Bar'},
            {type:'numbers', title: '���', width:80,hide:'', sort: true},
            {field: 'customer_code', title: 'U8�ͻ�����',width:200}, 
            {field: 'customer_short', title: '�ͻ����',width:200}, 
            {field: 'customer_name', title: '�ͻ�����',width:200}, 
            {field: 'customer_country', title: '�ͻ�����',width:100},
            {field: 'customer_address', title: '�ͻ���ַ',width:400},
        ]], 
    
        'spell_list':[[
            {type: 'checkbox', width:70, align:'center', toolbar: '#tab1Bar'},
            {type:'numbers', title: '���', width:80,hide:'', sort: true},
            {field: 'company_name', title: 'ƴ���ҹ�˾����',width:300}, 
            {field: 'grade_name', title: 'ƴ��Ʒ��',width:300,totalRowText: '�ϼƣ�'}, 
            {field: 'mz', title: 'ƴ��ë��',width:200 ,totalRow: true }, 
            {field: 'tj', title: '�������',width:200 ,totalRow: true },

            {field:'attachment', title:'���ظ���', width:297, templet: function(d){
                if(typeof(d.attachment)!="undefined"){
                    return '<span>'+d.attachment["NAME"]+'</span>'; 
                }
                return '<span></span>'; 
            }, unresize: true ,event:'viewAtt'},

        ]],
    
        'cabinet_num': [[
            {field:'cabinet_type',width:100, title: '����'},
            {field:'cabinet1', title: '20�߹�', width:200,edit:''},
            {field:'cabinet2', title: '40��ƽ��', width:200,edit:''},
            {field:'cabinet3', title: '40�߸߹�', width:200,edit:''},
            {field:'cabinet4', title: '45�߹�', width:200,edit:''}, 
            {field:'cabinet5', title: 'NORƽ��', width:200,edit:''}, 
            {field:'cabinet6', title: 'NOR�߹�', width:200,edit:''}, 
           
        ]], 

    };

    exports('customer_cols', obj);
});   
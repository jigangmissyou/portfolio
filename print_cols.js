layui.define(function(exports){ 
  var obj = {
        'gate_bill':[[
            {field:'id', title: '���', width:80},
            {field:'product_code', title: '��Ʒ���', width:300},
            {field:'product_specs', title: '��Ʒ���Ƽ�����ͺ�', width:200, templet: function(d){
                    return "<span>"+d.product_specs+"<br/>"+d.grade_name+"</span>"; 
                }, unresize: true
            },
            {field:'num', title: '�����Լ���λ',width:200, templet: function(d){
                    return "<span>"+d.u8_info.jz+d.unit+"</span>"; 
                }, unresize: true
            },
            {field:'total_price', title: '�ܼ�',width:100},
            {field:'coin_type', title: '����',width:100},
            {field:'origin_country', title: 'ԭ������������',width:150},
            {field:'target_country', title: '����Ŀ�Ĺ���������',width:200},
            {field:'source_region', title: '���ڻ�Դ��',width:100},
            {field:'levy_exempt', title: '����',width:100 },
            {title: '��ע', align:'center',width:250,   templet: function(d){
                    if(d['u8_info']['no_real'].length>0){
                       return  "<span>����ë����:["+d['u8_info']['no_real'].join(",")+"]";
                    }
                    return "";  
                }, unresize: true
            }
        ]],
    
        'audit_bill':[[
            {field:'id', title: '���', width:80},
            {field:'bill_num', title: '���յ���', width:200},
            {field:'print_type_name', title: '��ӡ����', width:300}, 
            {field:'beginUserName', title: '�ύ��',width:150},
            {field:'beginTime', title: '�ύʱ��',width:200},
            {field:'prcsFlag1', title: '��ǰ״̬', width:100}, 
            {field:'deliverUser', title: '�����',width:150},
            {field:'deliverTime', title: '���ʱ��',width:200},
            {fixed: 'right', align:'center', toolbar: '#tab1Bar',width:300}
        ]],
        
  };

  exports('print_cols', obj);
});   
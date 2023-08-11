layui.define(function(exports){ 
  var obj = {
       
        'order_product_list':[[
            {field:'id', title: '���', width:50,hide:'true', sort: true, fixed: 'left'}
            ,{type:'numbers', title: '���', width:80,hide:'', sort: true}
            ,{field: 'deleted', title: '�Ƿ�ɾ��', width: 100, templet: function(d){
                return d.deleted==1?"��ɾ��":"";
            }, hide: '', sort: false}

            ,{field:'u8Code', title: '�������', width:115,hide:'', sort: true,event:'editU8code'}
            ,{field:'cInvCode', title: '�ͺ�',event:'cInvCode', width:'200',hide:'', sort: true}
            ,{field:'proColor', title: '��ɫ', width:150,hide:'', sort: true}
            ,{field:'name', title: '��Ʒ����',event:'', width:210,hide:'', sort: true}
            ,{field:'width', title: '��MM', width:70,hide:'', sort: true}
            ,{field:'length', title: '��M', width:70,hide:'', sort: true}
            ,{field:'glueSty', title: '��ϵ', width:80,hide:'', sort: true}
            ,{field:'sfieldText', title: '��Ʒ����', width:90,hide:'', sort: true}
            ,{field:'inum', title: '��װ����', width:78,hide:'', sort: true}
            ,{field:'onum', title: '��װ����', width:78,hide:'', sort: true}
            ,{field:'proUantity', title: '��������', width:90,hide:'', sort: true}
            ,{field:'proUnit', title: '������λ', width:78,hide:'', sort: true }
            ,{field:'boxNum', title: '����', width:60,hide:'', sort: true }
            ,{field:'baoZhuangMS', title: '��װ��ʽ', width:500,hide:'', sort: ''}
            ,{field:'tintd', title: '�ھ�', width:60,hide:'', sort: ''}
            ,{field:'tthick', title: '�ܺ�', width:60,hide:'', sort: ''}                
            ,{field:'tdf1', title: 'ֽ������', width:74,hide:'', sort: ''}
            ,{field:'tdesc', title: 'ֽ������Ҫ��', width:60,hide:'', sort: ''}
            ,{field:'icontent', title: '��������', width:75,hide:'', sort: ''}
            ,{field:'icolor', title: '������ɫ', width:75,hide:'', sort: ''}
            ,{field:'imaterial', title: '�������', width:75,hide:'', sort: ''}
            ,{field:'itm', title: '����������', width:130,hide:'', sort: ''}
            ,{field:'ocontent', title: '��������', width:74,hide:'', sort: ''}
            ,{field:'ocolor', title: '������ɫ', width:74,hide:'', sort: ''}
            ,{field:'omaterial', title: '�������', width:74,hide:'', sort: ''}
            ,{field:'otm', title: '����������', width:130,hide:'', sort: ''}
            // ,{field:'', title: '����', width:'',hide:'', sort: ''}
            ,{field:'', title: '�����Ҫ��', width:88,hide:'', sort: ''}
            ,{field:'customerCode', title: '�ͻ�����', width:92,hide:'', sort: true}
            ,{field:'barCode', title: '������', width:130,hide:'', sort: true}
            ,{field:'tuoPan', title: '����', width:110,hide:'', sort: ''}
            ,{field:'tpdesc', title: '��������Ҫ��',width:105,hide:'', sort: ''}
            ,{field:'sfieldText', title: '��Ʒ����',width:90,hide:'true', sort: true}
            ,{field:'desc', title: '��ע',width:190,hide:'', sort: true}
            ,{fixed: 'right', width:120, align:'center', toolbar: '#tab1Bar'}
        ]],
    
        'order_hzProduct_list': [[
            {field: 'id', title: 'id', width: '', hide: 'true', sort: true, fixed: 'left'}
            , {field: 'cInvCode', title: '��װ����', width: 120, hide: '', sort: true}
            , {field: 'name', title: '��װ����', width: '', hide: '', sort: true}
            , {field: 'hzName', title: '��װ��Ʒ', width: '', hide: '', sort: true}
            // ,{field:'hzNum', title: '��װ����', width:100,hide:'', sort: true}
            , {field: 'hzNumSum', title: '��װ����', width: 100, hide: '', sort: true}
            , {field: 'desc', title: '��װ��ע', width: '', hide: '', sort: true}
            , {field: 'baoZhuangMS', title: '��װ��ʽ����', width: '', hide: '', sort: true}

            , {fixed: 'right', width: 120, align: 'center', toolbar: '#tab2Bar'}
        ]],
        
        'order_select_radio':[[
            {type: 'radio', width:'', align:'center', toolbar: '#tab1Bar'}
            ,{field:'id', title: '����', width:80,hide:'true', sort: true, fixed: 'left'}
            ,{field:'runId', title: '��ˮ��', width:90,hide:'', sort: true}
            ,{field:'orderNum', title: '�������', width:156,hide:'', sort: true}
            ,{field:'zhangTaoText', title: '����', width:100,hide:'', sort: true}
            ,{field:'ddLeiXingText', title: '��������', width:100,hide:'', sort: true}
            ,{field:'categorys', title: '��Ʒ���', width:'',hide:'', sort: true}
            ,{field:'cCCName', title: '����', width:100,hide:'', sort: true}
            ,{field:'cCusAbbName', title: '�ͻ����', width:'',hide:'', sort: true}
            ,{field:'cCusName', title: '�ͻ�����', width:'',hide:'true', sort: true}
            ,{field:'atTime', title: '�����', width:160,hide:'', sort: true }
            ,{field:'status', title: '����״̬',width:100,hide:'', sort: true}
            ,{field:'atUserText', title: '�Ƶ���', width:90,hide:'', sort: true }
            ,{field:'cCusPPersonOAText', title: 'ҵ��Ա', width:90,hide:'', sort: true}
            ,{field:'date1', title: 'Ŀ�꽻��', width:100,hide:'true', sort: true}
            ,{field:'date2', title: 'ȷ�Ͻ���', width:100,hide:'true', sort: true}
            ,{field:'amount', title: '�������', width:100,hide:'true', sort: true}            
        ]],
    
        'order_select_checkbox':[[
            {type: 'checkbox', width:'', align:'center', toolbar: '#tab1Bar'},
            ,{field:'id', title: '����', width:80,hide:'true', sort: true, fixed: 'left'}
            ,{field:'runId', title: '��ˮ��', width:90,hide:'', sort: true}
            ,{field:'orderNum', title: '�������', width:156,hide:'', sort: true}
            ,{field:'zhangTaoText', title: '����', width:100,hide:'', sort: true}
            ,{field:'ddLeiXingText', title: '��������', width:100,hide:'', sort: true}
            ,{field:'categorys', title: '��Ʒ���', width:'',hide:'', sort: true}
            ,{field:'cCCName', title: '����', width:100,hide:'', sort: true}
            ,{field:'cCusAbbName', title: '�ͻ����', width:'',hide:'', sort: true}
            ,{field:'cCusName', title: '�ͻ�����', width:'',hide:'true', sort: true}
            ,{field:'atTime', title: '�����', width:160,hide:'', sort: true }
            ,{field:'status', title: '����״̬',width:100,hide:'', sort: true}
            ,{field:'atUserText', title: '�Ƶ���', width:90,hide:'', sort: true }
            ,{field:'cCusPPersonOAText', title: 'ҵ��Ա', width:90,hide:'', sort: true}
            ,{field:'date1', title: 'Ŀ�꽻��', width:100,hide:'true', sort: true}
            ,{field:'date2', title: 'ȷ�Ͻ���', width:100,hide:'true', sort: true}
            ,{field:'amount', title: '�������', width:100,hide:'true', sort: true}            
        ]],
    
     
        order_list:[[
            {type: 'checkbox', width:'', align:'center', toolbar: '#tab1Bar'},
            {type:'numbers', title: '���', width:80,hide:''},
            {field:'order_num_show', title: '������', width:"200",hide:'', sort: ""},
            {field:'poUantity', title: '��ͬ����',event:'', width:100,hide:''},
            {field:'boxNum', title: '��ͬ������', width:100,hide:'' },
          
            {field:'tuoPan', title: '����', width:110,hide:'', sort: ''},
            {field:'tuopanNum', title: '��������',event:'', width:100,hide:'',edit:'text'}, 
            {field:'tuopanWeight', title: '��������',event:'', width:100,hide:'',edit:'text'}, 
            {field:'tuopanSize', title: '���̳ߴ�',event:'', width:100,hide:'',edit:'text'}, 
            {field:'tuopanDisplay', title: '���̰ڷ�',event:'', width:100,hide:'',edit:'text'},
            {field:'tuopan_group', title: '���̷���',event:'', width:100,hide:'',sort: true}, 
            {field:'u8Code', title: '�������', width:115,hide:'',event:'editU8code'},
            {field:'complete', title: '��Ʒ/���Ʒ',event:'', width:150,hide:''},
            {field:'category', title: '��Ʒ����',event:'', width:210,hide:''},
            {field:'proColor', title: '��ɫ', width:150,hide:''},    
            {field:'name', title: '��Ʒ����',event:'', width:210,hide:''},
            {field:'cInvCode', title: '��Ʒ�ͺ�',event:'cInvCode', width:'200',hide:''},
            {field:'proUnit', title: '������λ',event:'', width:100,hide:''},
          
            {field:'glueSty', title: '��ϵ', width:80,hide:''},
            {field:'sfieldText', title: '��Ʒ����', width:90,hide:''},
            {field:'baoZhuangMS', title: '��װ��ʽ', width:500,hide:'', sort: ''},
            {field:'tdf1', title: 'ֽ������', width:200,hide:'', sort: ''},
            {field:'customerCode', title: '�ͻ�����', width:200,hide:''},
            {fixed: 'right', width:80, align:'center',hide:'', toolbar: '#tab1Bar'}
        ]],
        
    
  };

  exports('order_cols', obj);
});   
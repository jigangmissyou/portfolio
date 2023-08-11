layui.define(function(exports){ 
  var obj = {
       
        'order_product_list':[[
            {field:'id', title: '编号', width:50,hide:'true', sort: true, fixed: 'left'}
            ,{type:'numbers', title: '序号', width:80,hide:'', sort: true}
            ,{field: 'deleted', title: '是否删除', width: 100, templet: function(d){
                return d.deleted==1?"已删除":"";
            }, hide: '', sort: false}

            ,{field:'u8Code', title: '存货编码', width:115,hide:'', sort: true,event:'editU8code'}
            ,{field:'cInvCode', title: '型号',event:'cInvCode', width:'200',hide:'', sort: true}
            ,{field:'proColor', title: '颜色', width:150,hide:'', sort: true}
            ,{field:'name', title: '产品名称',event:'', width:210,hide:'', sort: true}
            ,{field:'width', title: '宽MM', width:70,hide:'', sort: true}
            ,{field:'length', title: '长M', width:70,hide:'', sort: true}
            ,{field:'glueSty', title: '胶系', width:80,hide:'', sort: true}
            ,{field:'sfieldText', title: '产品特性', width:90,hide:'', sort: true}
            ,{field:'inum', title: '内装箱数', width:78,hide:'', sort: true}
            ,{field:'onum', title: '外装箱数', width:78,hide:'', sort: true}
            ,{field:'proUantity', title: '生产数量', width:90,hide:'', sort: true}
            ,{field:'proUnit', title: '生产单位', width:78,hide:'', sort: true }
            ,{field:'boxNum', title: '箱数', width:60,hide:'', sort: true }
            ,{field:'baoZhuangMS', title: '包装方式', width:500,hide:'', sort: ''}
            ,{field:'tintd', title: '内径', width:60,hide:'', sort: ''}
            ,{field:'tthick', title: '管厚', width:60,hide:'', sort: ''}                
            ,{field:'tdf1', title: '纸管内容', width:74,hide:'', sort: ''}
            ,{field:'tdesc', title: '纸管其他要求', width:60,hide:'', sort: ''}
            ,{field:'icontent', title: '内箱内容', width:75,hide:'', sort: ''}
            ,{field:'icolor', title: '内箱颜色', width:75,hide:'', sort: ''}
            ,{field:'imaterial', title: '内箱材质', width:75,hide:'', sort: ''}
            ,{field:'itm', title: '内箱条形码', width:130,hide:'', sort: ''}
            ,{field:'ocontent', title: '外箱内容', width:74,hide:'', sort: ''}
            ,{field:'ocolor', title: '外箱颜色', width:74,hide:'', sort: ''}
            ,{field:'omaterial', title: '外箱材质', width:74,hide:'', sort: ''}
            ,{field:'otm', title: '外箱条形码', width:130,hide:'', sort: ''}
            // ,{field:'', title: '内容', width:'',hide:'', sort: ''}
            ,{field:'', title: '外封箱要求', width:88,hide:'', sort: ''}
            ,{field:'customerCode', title: '客户货号', width:92,hide:'', sort: true}
            ,{field:'barCode', title: '条形码', width:130,hide:'', sort: true}
            ,{field:'tuoPan', title: '托盘', width:110,hide:'', sort: ''}
            ,{field:'tpdesc', title: '托盘特殊要求',width:105,hide:'', sort: ''}
            ,{field:'sfieldText', title: '产品参数',width:90,hide:'true', sort: true}
            ,{field:'desc', title: '备注',width:190,hide:'', sort: true}
            ,{fixed: 'right', width:120, align:'center', toolbar: '#tab1Bar'}
        ]],
    
        'order_hzProduct_list': [[
            {field: 'id', title: 'id', width: '', hide: 'true', sort: true, fixed: 'left'}
            , {field: 'cInvCode', title: '混装编码', width: 120, hide: '', sort: true}
            , {field: 'name', title: '混装名称', width: '', hide: '', sort: true}
            , {field: 'hzName', title: '混装产品', width: '', hide: '', sort: true}
            // ,{field:'hzNum', title: '混装数量', width:100,hide:'', sort: true}
            , {field: 'hzNumSum', title: '混装数量', width: 100, hide: '', sort: true}
            , {field: 'desc', title: '混装备注', width: '', hide: '', sort: true}
            , {field: 'baoZhuangMS', title: '包装方式描述', width: '', hide: '', sort: true}

            , {fixed: 'right', width: 120, align: 'center', toolbar: '#tab2Bar'}
        ]],
        
        'order_select_radio':[[
            {type: 'radio', width:'', align:'center', toolbar: '#tab1Bar'}
            ,{field:'id', title: '内码', width:80,hide:'true', sort: true, fixed: 'left'}
            ,{field:'runId', title: '流水号', width:90,hide:'', sort: true}
            ,{field:'orderNum', title: '订单编号', width:156,hide:'', sort: true}
            ,{field:'zhangTaoText', title: '账套', width:100,hide:'', sort: true}
            ,{field:'ddLeiXingText', title: '订单类型', width:100,hide:'', sort: true}
            ,{field:'categorys', title: '产品类别', width:'',hide:'', sort: true}
            ,{field:'cCCName', title: '国家', width:100,hide:'', sort: true}
            ,{field:'cCusAbbName', title: '客户简称', width:'',hide:'', sort: true}
            ,{field:'cCusName', title: '客户名称', width:'',hide:'true', sort: true}
            ,{field:'atTime', title: '填报日期', width:160,hide:'', sort: true }
            ,{field:'status', title: '订单状态',width:100,hide:'', sort: true}
            ,{field:'atUserText', title: '制单人', width:90,hide:'', sort: true }
            ,{field:'cCusPPersonOAText', title: '业务员', width:90,hide:'', sort: true}
            ,{field:'date1', title: '目标交期', width:100,hide:'true', sort: true}
            ,{field:'date2', title: '确认交期', width:100,hide:'true', sort: true}
            ,{field:'amount', title: '订单金额', width:100,hide:'true', sort: true}            
        ]],
    
        'order_select_checkbox':[[
            {type: 'checkbox', width:'', align:'center', toolbar: '#tab1Bar'},
            ,{field:'id', title: '内码', width:80,hide:'true', sort: true, fixed: 'left'}
            ,{field:'runId', title: '流水号', width:90,hide:'', sort: true}
            ,{field:'orderNum', title: '订单编号', width:156,hide:'', sort: true}
            ,{field:'zhangTaoText', title: '账套', width:100,hide:'', sort: true}
            ,{field:'ddLeiXingText', title: '订单类型', width:100,hide:'', sort: true}
            ,{field:'categorys', title: '产品类别', width:'',hide:'', sort: true}
            ,{field:'cCCName', title: '国家', width:100,hide:'', sort: true}
            ,{field:'cCusAbbName', title: '客户简称', width:'',hide:'', sort: true}
            ,{field:'cCusName', title: '客户名称', width:'',hide:'true', sort: true}
            ,{field:'atTime', title: '填报日期', width:160,hide:'', sort: true }
            ,{field:'status', title: '订单状态',width:100,hide:'', sort: true}
            ,{field:'atUserText', title: '制单人', width:90,hide:'', sort: true }
            ,{field:'cCusPPersonOAText', title: '业务员', width:90,hide:'', sort: true}
            ,{field:'date1', title: '目标交期', width:100,hide:'true', sort: true}
            ,{field:'date2', title: '确认交期', width:100,hide:'true', sort: true}
            ,{field:'amount', title: '订单金额', width:100,hide:'true', sort: true}            
        ]],
    
     
        order_list:[[
            {type: 'checkbox', width:'', align:'center', toolbar: '#tab1Bar'},
            {type:'numbers', title: '序号', width:80,hide:''},
            {field:'order_num_show', title: '订单号', width:"200",hide:'', sort: ""},
            {field:'poUantity', title: '合同数量',event:'', width:100,hide:''},
            {field:'boxNum', title: '合同总箱数', width:100,hide:'' },
          
            {field:'tuoPan', title: '托盘', width:110,hide:'', sort: ''},
            {field:'tuopanNum', title: '托盘数量',event:'', width:100,hide:'',edit:'text'}, 
            {field:'tuopanWeight', title: '托盘总重',event:'', width:100,hide:'',edit:'text'}, 
            {field:'tuopanSize', title: '托盘尺寸',event:'', width:100,hide:'',edit:'text'}, 
            {field:'tuopanDisplay', title: '托盘摆法',event:'', width:100,hide:'',edit:'text'},
            {field:'tuopan_group', title: '托盘分组',event:'', width:100,hide:'',sort: true}, 
            {field:'u8Code', title: '存货编码', width:115,hide:'',event:'editU8code'},
            {field:'complete', title: '成品/半成品',event:'', width:150,hide:''},
            {field:'category', title: '产品分类',event:'', width:210,hide:''},
            {field:'proColor', title: '颜色', width:150,hide:''},    
            {field:'name', title: '产品名称',event:'', width:210,hide:''},
            {field:'cInvCode', title: '产品型号',event:'cInvCode', width:'200',hide:''},
            {field:'proUnit', title: '生产单位',event:'', width:100,hide:''},
          
            {field:'glueSty', title: '胶系', width:80,hide:''},
            {field:'sfieldText', title: '产品特性', width:90,hide:''},
            {field:'baoZhuangMS', title: '包装方式', width:500,hide:'', sort: ''},
            {field:'tdf1', title: '纸管内容', width:200,hide:'', sort: ''},
            {field:'customerCode', title: '客户货号', width:200,hide:''},
            {fixed: 'right', width:80, align:'center',hide:'', toolbar: '#tab1Bar'}
        ]],
        
    
  };

  exports('order_cols', obj);
});   
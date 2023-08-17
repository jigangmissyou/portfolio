layui.define(function(exports){ 
  var obj = {
        'coin_unit':{"美元":"usd","人民币":"rmb","欧元":"eur","日币":"jpy"},
        'order_list':[[
            {type: 'checkbox', width:'', align:'center', toolbar: '#tab1Bar'},
            {field:'id', title: '内码', width:80,hide:'true', sort: true, fixed: 'left'},
            {field:'formId', title: '订单内码', width:90,hide:'true', sort: true},
            {field:'runId', title: '流水号', width:90,hide:'true', sort: true},
            {field:'prcsId', title: 'prcsId', width:90,hide:'true', sort: true},
            {field:'orderNum', title: '订单编号', width:156,hide:'', sort: true},
            {field:'ddLeiXingText', title: '订单类型', width:100,hide:'', sort: true},
            {field:'zhangTaoText', title: '账套', width:80,hide:'', sort: true},
            {field:'currency', title: '币种', width:'',hide:'', sort: true},
            {field:'cCCName', title: '国家', width:'',hide:'', sort: true},
            {field:'cCusAbbName', title: '客户简称', width:'',hide:'', sort: true},
            {field:'cCusName', title: '客户名称', width:'',hide:'', sort: true},
            {field:'date1', title: '目标交期', width:100,hide:'', sort: true},
            {field:'cCusPPersonOAText', title: '业务员', width:90,hide:'', sort: true},
            {field:'atTime', title: '送达时间', width:160,hide:'', sort: true },
            {fixed: 'right', width:80, align:'center',hide:'', toolbar: '#tab1Bar'}
            
        ]],
        'product_list': [[
            {type: 'checkbox', width:'', align:'center', toolbar: '#tab1Bar'},
            {field:'id', title: '编号', width:50,hide:'true', sort: true, fixed: 'left'},
            
            
            {type:'numbers', title: '序号', width:80,hide:'', sort: true},
            {field:'order_num_show', title: '订单号', width:200,hide:'',},
            {field:'u8Code', title: '存货编码', width:115,hide:'', sort: true,event:'editU8code'},
            {field:'cInvCode', title: '型号',event:'cInvCode', width:'200',hide:'', sort: true},
            {field:'customerCode', title: '客户货号', width:92,hide:'', sort: true},
            {field:'proColor', title: '颜色', width:150,hide:'', sort: true},
            {field:'name', title: '产品名称',event:'', width:210,hide:'', sort: true},
            {field:'o_width', title: '宽MM', width:70,hide:'', sort: true},
            {field:'o_length', title: '长M', width:70,hide:'', sort: true},
            {field:'glueSty', title: '胶系', width:80,hide:'', sort: true},
            {field:'sfieldText', title: '产品特性', width:90,hide:'', sort: true},
            {field:'inum', title: '内装箱数', width:78,hide:'', sort: true},
            {field:'onum', title: '外装箱数', width:78,hide:'', sort: true},
            {field:'proUantity', title: '生产数量', width:90,hide:'', sort: true},
          
            {field:'proUnit', title: '生产单位', width:78,hide:'', sort: true },
            {field:'boxNum', title: '箱数', width:60,hide:'', sort: true },
            {field:'baoZhuangMS', title: '包装方式', width:500,hide:'', sort: ''},
            {field:'tintd', title: '内径', width:60,hide:'', sort: ''},
            {field:'tthick', title: '管厚', width:60,hide:'', sort: ''},                
            {field:'tdf1', title: '纸管内容', width:74,hide:'', sort: ''},
            {field:'tdesc', title: '纸管其他要求', width:60,hide:'', sort: ''},
            {field:'icontent', title: '内箱内容', width:75,hide:'', sort: ''},
            {field:'icolor', title: '内箱颜色', width:75,hide:'', sort: ''},
            {field:'itm', title: '内箱条形码', width:130,hide:'', sort: ''},
            {field:'ocontent', title: '外箱内容', width:74,hide:'', sort: ''},
            {field:'ocolor', title: '外箱颜色', width:74,hide:'', sort: ''},
            {field:'otm', title: '外箱条形码', width:130,hide:'', sort: ''},
            {field:'orderInfo', title: '内容', width:'',hide:'', sort: '', hide:true},
            {field:'', title: '外封箱要求', width:88,hide:'', sort: ''},
            
            {field:'barCode', title: '条形码', width:130,hide:'', sort: true},
            {field:'tuoPan', title: '托盘', width:110,hide:'', sort: ''},
            {field:'tpdesc', title: '托盘特殊要求',width:105,hide:'', sort: ''},
            {field:'sfieldText', title: '产品参数',width:90,hide:'true', sort: true},
            {field:'desc', title: '备注',width:190,hide:'', sort: true},
            {field:'listpo',fixed: 'right',title: '客户产品PO号', width:200, align:'center'}
            
        ]],
        'order_product_list':function (colsType){
            var retArray=[];
            var tempArray=[];
            var allCols=[
                {type:'numbers', title: '序号', width:80,hide:''},
                {field:'order_num_show', title: '订单号', width:"200",hide:'', sort: ""},
                {field:'hsCode', title: 'hs编码', width:"100",hide:'', sort: ""},
                {field:'u8Code', title: '存货编码',event:'', width:100,hide:'',edit:''}, 
                {field:'hsCodeName', title: '品名', width:"150",hide:'', sort: "", totalRowText: '合计：'},
                {field:'is_apportion', title:'是否分摊', width:85, templet: function(d){
                    return '<input type="checkbox" data-id="'+d.id+'" value="'+d.is_apportion+'" lay-skin="switch" lay-text="是|否" lay-filter="is_apportion" '+ (d.is_apportion == 1 ? 'checked' : '')+'>'; 
                }, unresize: true},
                {field:'total_1', title: '总价',event:'', width:100,hide:'',totalRow: true},
  
                {field:'apportion_price', title: '分摊价格',event:'', width:150,hide:'',sort: ""},
                {field:'apportion_total', title: '分摊后总价格',event:'', width:150,hide:'',sort: "",totalRow: true },
       
                {field:'manua', title: '手册',event:'', width:80,hide:'',edit:'text'},
                {field:'manua_num', title: '手册数量',event:'', width:100,hide:'',edit:'text'}, 
                {field:'num', title: '订舱数量',event:'', width:100,hide:'',edit:'text'}, 
                {field:'poUantity', title: '总数量',event:'', width:100,hide:'',edit:''}, 
                {field:'box_num', title: '订舱箱数', width:100,hide:'', edit:'text',totalRow: true }, 
                {field:'poPrice', title: '单价',event:'', width:100,hide:'',edit:'text',totalRow: true},
                {field:'tuoPan', title: '托盘', width:110,hide:''},
                {field:'tuopan_num', title: '托盘数量',event:'', width:100,hide:'',edit:'text',totalRow: true}, 
                {field:'tuopan_weight', title: '托盘总重',event:'', width:100,hide:'',edit:'text',totalRow: true}, 
                {field:'tuopan_size', title: '托盘尺寸',event:'', width:100,hide:'',edit:'text',totalRow: true}, 
                {field:'tuopan_display', title: '托盘摆法',event:'', width:100,hide:'',edit:'text',totalRow: true},
                {field:'tuopan_group', title: '托盘分组',event:'', width:100,hide:'',sort: true,totalRow: true}, 

                {field:'jz_1', title: '净重',event:'', width:100,hide:'',edit:'',totalRow: true}, 
                {field:'mz_1', title: '毛重',event:'', width:100,hide:'',edit:'',totalRow: true}, 
                {field:'tj_1', title: '总体积',event:'', width:150,hide:'',edit:'',totalRow: true}, 
                {field:'zs_zjz', title: '真实净重',event:'', width:100,hide:'',edit:'',totalRow: true}, 
                {field:'zs_zmz', title: '真实毛重',event:'', width:100,hide:'',edit:'',totalRow: true}, 
                
                {field:'c_dwjz', title: '客户要求单位净重',event:'', width:150,hide:''}, 
                {field:'c_dwmz', title: '客户要求单位毛重',event:'', width:150,hide:''}, 
                {field:'c_dwtj', title: '客户要求单位体积',event:'', width:150,hide:''}, 
                
                {field:'dwtj', title: '单位体积',event:'', width:150,hide:'',edit:''},  
                {field:'dwmz', title: '单位毛重',event:'', width:100,hide:'',edit:''}, 
                {field:'dwjz', title: '单位净重',event:'', width:100,hide:'',edit:''}, 
                {field:'dwxs', title: '单位系数',event:'', width:100,hide:'',edit:''}, 
                
                {field:'u8Code', title: '存货编码',event:'', width:100,hide:'',edit:''}, 

                {field:'o_length', title: '长',event:'', width:60,hide:'',edit:''}, 
                {field:'o_width', title: '宽',event:'', width:60,hide:'',edit:''}, 
                {field:'height', title: '高',event:'', width:60,hide:'',edit:''}, 
                {field:'formula', title: '单位体积计算公式',event:'', width:300,hide:'',edit:''}, 
                {field:'u8Code', title: '存货编码', width:115,hide:'',event:'editU8code'},
                {field:'org_num', title: '合同数量',event:'', width:100,hide:''},
                {field:'proUantity', title: '生产数量', width:90,hide:''},
                {field:'onum', title: '外装箱数', width:100,hide:''},
                {field:'proUnit', title: '生产单位', width:100,hide:'' },
                {field:'complete', title: '成品/半成品',event:'', width:150,hide:''},
                {field:'category', title: '产品分类',event:'', width:210,hide:''},
                {field:'name', title: '产品名称',event:'', width:210,hide:''},
                {field:'cInvCode', title: '产品型号',event:'cInvCode', width:'200',hide:''},
                {field:'poUnit', title: '合同单位',event:'', width:100,hide:''},

               
                {field:'inum', title: '内装箱数', width:100,hide:''},
                
                {field:'boxNum', title: '合同总箱数', width:100,hide:'' },
                {field:'proColor', title: '颜色', width:150,hide:''},
                {field:'width', title: '宽MM', width:70,hide:''},
                {field:'length', title: '长M', width:70,hide:''},
                {field:'glueSty', title: '胶系', width:80,hide:''},
                {field:'sfieldText', title: '产品特性', width:90,hide:''},
                {field:'baoZhuangMS', title: '包装方式', width:500,hide:'', sort: ''},
                {field:'tintd', title: '内径', width:60,hide:'', sort: ''},
                {field:'tthick', title: '管厚', width:60,hide:'', sort: ''},                
                {field:'tdf1', title: '纸管内容', width:74,hide:'', sort: ''},
                {field:'tdesc', title: '纸管其他要求', width:60,hide:'', sort: ''},
                {field:'icontent', title: '内箱内容', width:75,hide:'', sort: ''},
                {field:'icolor', title: '内箱颜色', width:75,hide:'', sort: ''},
                {field:'itm', title: '内箱条形码', width:130,hide:'', sort: ''},
                {field:'ocontent', title: '外箱内容', width:74,hide:'', sort: ''},
                {field:'ocolor', title: '外箱颜色', width:74,hide:'', sort: ''},
                {field:'otm', title: '外箱条形码', width:130,hide:'', sort: ''},
                // {field:'', title: '内容', width:'',hide:'', sort: ''},
                {field:'', title: '外封箱要求', width:88,hide:'', sort: ''},
                {field:'customerCode', title: '客户货号', width:92,hide:''},
                {field:'barCode', title: '条形码', width:130,hide:''},
                {field:'tuoPan', title: '托盘', width:110,hide:'', sort: ''},
                {field:'tpdesc', title: '托盘特殊要求',width:105,hide:'', sort: ''},
                {field:'sfieldText', title: '产品参数',width:90,hide:'true'},
                {field:'desc', title: '备注',width:190,hide:''},
               
            ];

            switch(colsType){
                case 1: 
                    
                    break;
                case 2: 
                    return this.product_list_cabinet;
                    break;
                case 3: 
                    //allCols.splice(6, 4);//删除索引值为8的后面两个元素 
                    //allCols.splice(4, 1);//删除索引值为8的后面两个元素
                    
                    return this.sale_product_list
                    break;  
                case 4: 
                    return this.plan_product_list;
                case 5: 
                    return this.store_product_list;
                case 6: 
                    return this.gendan_cabinet_product_list;
                    break;
            }
            retArray.push(allCols);
            return retArray;
        },
        
        split_product_list:function(){
          return [[
                {type: 'checkbox', width:'', align:'center', toolbar: '#tab1Bar'},
                {type:'numbers', title: '序号', width:80,hide:''},
                {field:'order_num_show', title: '订单号', width:"200",hide:'', sort: ""},
                {field:'u8Code', title: '存货编码',event:'', width:100,hide:'',edit:''}, 
                {field:'name', title: '产品名称',event:'', width:100,hide:''},
                {field:'customerCode', title: '客户货号', width:92,hide:''},
                {field:'num', title: '订舱数量',event:'', width:100,hide:'',edit:'text'}, 
                {field:'box_num', title: '订舱箱数', width:100,hide:'', edit:'text',totalRow: true }, 
                {field:'proColor', title: '产品颜色', width:92,hide:''},
                {field:'o_width', title: '宽MM', width:70,hide:''},
                {field:'o_length', title: '长M', width:70,hide:''},
                {field:'norms', title: '产品规格',event:'', width:100,hide:true,edit:'',templet: function(d){
                    return d.length+"M*"+d.width+"MM";
                }}, 
  
                {field:'poUantity', title: '合同数量',event:'', width:100,hide:''},
                {field:'boxNum', title: '合同箱数', width:100,hide:'' },
                {field:'proUantity', title: '生产数量', width:90,hide:''},
                {field:'boxNum', title: '生产箱数', width:100,hide:''},
                {field:'proUnit', title: '生产单位', width:100,hide:'' },
                
                {field:'sample_num1', title: '样品数量', width:110,hide:''},
                {field:'sample_num2', title: '样品数量(免费)', width:110,hide:true},


                {field:'poPrice', title: '单价',event:'', width:100,hide:'',totalRow: true},
                
                {field:'total_1', title: '总价',event:'', width:100,hide:'',totalRow: true},
                
                {field:'apportion_price', title: '分摊价格',event:'', width:150,hide:'',sort: ""},
                {field:'apportion_total', title: '分摊后总价格',event:'', width:150,hide:'',sort: "",totalRow: true },
                
                {field:'jz_1', title: '净重',event:'', width:100,hide:'',edit:'',totalRow: true}, 
                {field:'mz_1', title: '毛重',event:'', width:100,hide:'',edit:'',totalRow: true}, 
                {field:'tj_1', title: '总体积',event:'', width:150,hide:'',edit:'',totalRow: true}, 
                
                
                {field:'c_dwjz', title: '客户要求单位净重',event:'', width:150,hide:''}, 
                {field:'c_dwmz', title: '客户要求单位毛重',event:'', width:150,hide:''}, 
                {field:'c_dwtj', title: '客户要求单位体积',event:'', width:150,hide:''},  

                {field:'dwjz', title: '单位净重',event:'', width:100,hide:'',edit:''}, 
                {field:'dwmz', title: '单位毛重',event:'', width:100,hide:'',edit:''}, 
                {field:'dwtj', title: '单位体积',event:'', width:150,hide:'',edit:''},  
                
                
                {field:'dwxs', title: '单位系数',event:'', width:100,hide:'',edit:''}, 
                {field:'u8Code', title: '存货编码',event:'', width:100,hide:'',edit:''}, 
                {field:'inum', title: '内装箱数', width:100,hide:''},
            
                {field:'icontent', title: '内箱内容', width:75,hide:'', sort: ''},
                {field:'icolor', title: '内箱颜色', width:75,hide:'', sort: ''},
                {field:'itm', title: '内箱条形码', width:130,hide:'', sort: ''},
                {field:'ocontent', title: '外箱内容', width:74,hide:'', sort: ''},
                {field:'ocolor', title: '外箱颜色', width:74,hide:'', sort: ''},
                {field:'otm', title: '外箱条形码', width:130,hide:'', sort: ''},
                // {field:'', title: '内容', width:'',hide:'', sort: ''},
                {field:'', title: '外封箱要求', width:88,hide:'', sort: ''},
                {field:'tuoPan', title: '托盘', width:110,hide:''},
                {field:'tuopan_num', title: '托盘数量',event:'', width:100,hide:'',edit:'text',totalRow: true}, 
                {field:'tuopan_weight', title: '托盘总重',event:'', width:100,hide:'',edit:'text',totalRow: true}, 
                {field:'tuopan_size', title: '托盘尺寸',event:'', width:100,hide:'',edit:'text',totalRow: true}, 
                {field:'tuopan_display', title: '托盘摆法',event:'', width:100,hide:'',edit:'text',totalRow: true},
                {field:'tuopan_group', title: '托盘分组',event:'', width:100,hide:'',sort: true,totalRow: true}, 
                {field:'tpdesc', title: '托盘特殊要求',width:105,hide:'', sort: ''},
            
         
                {field:'length', title: '长',event:'', width:60,hide:'',edit:''}, 
                {field:'width', title: '宽',event:'', width:60,hide:'',edit:''}, 
                {field:'height', title: '高',event:'', width:60,hide:'',edit:''}, 
                {field:'formula', title: '单位体积计算公式',event:'', width:300,hide:'',edit:''}, 
                {field:'u8Code', title: '存货编码', width:115,hide:'',event:'editU8code'},
                
                
                {field:'complete', title: '成品/半成品',event:'', width:150,hide:''},
                {field:'category', title: '产品分类',event:'', width:210,hide:''},
               
                {field:'cInvCode', title: '产品型号',event:'cInvCode', width:'200',hide:''},
                {field:'poUnit', title: '合同单位',event:'', width:100,hide:''},

               
                {field:'inum', title: '内装箱数', width:100,hide:''},
                {field:'onum', title: '外装箱数', width:100,hide:''},
               
                {field:'proColor', title: '颜色', width:150,hide:''},
               
                {field:'glueSty', title: '胶系', width:80,hide:''},
                {field:'sfieldText', title: '产品特性', width:90,hide:''},
                {field:'baoZhuangMS', title: '包装方式', width:500,hide:'', sort: ''},
                {field:'tintd', title: '内径', width:60,hide:'', sort: ''},
                {field:'tthick', title: '管厚', width:60,hide:'', sort: ''},                
                {field:'tdf1', title: '纸管内容', width:74,hide:'', sort: ''},
                {field:'tdesc', title: '纸管其他要求', width:60,hide:'', sort: ''},
                
                
                {field:'barCode', title: '条形码', width:130,hide:''},
             
                {field:'sfieldText', title: '产品参数',width:90,hide:'true'},
                {field:'desc', title: '备注',width:190,hide:''},
                {field:'listpo', title: '产品PO号', width:150,hide:''},
          ]]  
        },
        
        
        manua_product_list:[[
                
                {type: 'checkbox', width:'', align:'center', toolbar: '#tab1Bar'},
                {type:'numbers', title: '序号', width:80,hide:''},
                {field:'order_num_show', title: '订单号', width:"200",hide:'', sort: ""},
                {field:'hsCode', title: 'hs编码', width:"100",hide:'', sort: ""},
                {field:'u8Code', title: '存货编码',event:'', width:100,hide:'',edit:''}, 
                {field:'hsCodeName', title: '品名', width:"150",hide:'', sort: "", totalRowText: '合计：'},
                {field:'total_1', title: '总价',event:'', width:100,hide:'',totalRow: true},
                {field:'apportion_price', title: '分摊价格',event:'', width:150,hide:'',sort: ""},
                {field:'apportion_total', title: '分摊后总价格',event:'', width:150,hide:'',sort: "",totalRow: true},
                {field:'gd_number', title: '关单号', width:155,hide:'',edit:'text'},
                {field:'manua', title: '手册',event:'', width:80,hide:'',edit:'text'},
                {field:'manua_num', title: '手册数量',event:'', width:100,hide:'',edit:'text'},
                {field:'num', title: '订舱数量',event:'', width:100,hide:'',totalRow: true}, 
                {field:'poUantity', title: '总数量',event:'', width:100,hide:'',edit:''}, 
                {field:'box_num', title: '订舱箱数', width:100,hide:'',totalRow: true }, 
 
                {field:'tuoPan', title: '托盘', width:110,hide:''},
                {field:'tuopan_num', title: '托盘数量',event:'', width:100,hide:'',totalRow: true}, 
                {field:'tuopan_weight', title: '托盘总重',event:'', width:100,hide:'',totalRow: true}, 
                {field:'tuopan_size', title: '托盘尺寸',event:'', width:100,hide:'',totalRow: true}, 
                {field:'tuopan_display', title: '托盘摆法',event:'', width:100,hide:'',totalRow: true},
                {field:'tuopan_group', title: '托盘分组',event:'', width:100,hide:'',sort: true,totalRow: true}, 

                {field:'jz_1', title: '净重',event:'', width:100,hide:'',edit:'',totalRow: true}, 
                {field:'mz_1', title: '毛重',event:'', width:100,hide:'',edit:'',totalRow: true}, 
                {field:'tj_1', title: '总体积',event:'', width:150,hide:'',edit:'',totalRow: true}, 
                
                
                {field:'c_dwjz', title: '客户要求单位净重',event:'', width:150,hide:''}, 
                {field:'c_dwmz', title: '客户要求单位毛重',event:'', width:150,hide:''}, 
                {field:'c_dwtj', title: '客户要求单位体积',event:'', width:150,hide:''}, 
                
                {field:'dwtj', title: '单位体积',event:'', width:150,hide:'',edit:''},  
                {field:'dwmz', title: '单位毛重',event:'', width:100,hide:'',edit:''}, 
                {field:'dwjz', title: '单位净重',event:'', width:100,hide:'',edit:''}, 
                {field:'dwxs', title: '单位系数',event:'', width:100,hide:'',edit:''}, 
                
                {field:'u8Code', title: '存货编码',event:'', width:100,hide:'',edit:''}, 

                {field:'o_length', title: '长',event:'', width:60,hide:'',edit:''}, 
                {field:'o_width', title: '宽',event:'', width:60,hide:'',edit:''}, 
                {field:'height', title: '高',event:'', width:60,hide:'',edit:''}, 
                {field:'formula', title: '单位体积计算公式',event:'', width:300,hide:'',edit:''}, 
                {field:'u8Code', title: '存货编码', width:115,hide:'',event:'editU8code'},
                {field:'org_num', title: '合同数量',event:'', width:100,hide:''},
                {field:'proUantity', title: '生产数量', width:90,hide:''},
                {field:'onum', title: '外装箱数', width:100,hide:''},
                {field:'proUnit', title: '生产单位', width:100,hide:'' },
                {field:'complete', title: '成品/半成品',event:'', width:150,hide:''},
                {field:'category', title: '产品分类',event:'', width:210,hide:''},
                {field:'name', title: '产品名称',event:'', width:210,hide:''},
                {field:'cInvCode', title: '产品型号',event:'cInvCode', width:'200',hide:''},
                {field:'poUnit', title: '合同单位',event:'', width:100,hide:''},

               
                {field:'inum', title: '内装箱数', width:100,hide:''},
                
                {field:'boxNum', title: '合同总箱数', width:100,hide:'' },
                {field:'proColor', title: '颜色', width:150,hide:''},
                {field:'width', title: '宽MM', width:70,hide:''},
                {field:'length', title: '长M', width:70,hide:''},
                {field:'glueSty', title: '胶系', width:80,hide:''},
                {field:'sfieldText', title: '产品特性', width:90,hide:''},
                {field:'baoZhuangMS', title: '包装方式', width:500,hide:'', sort: ''},
                {field:'tintd', title: '内径', width:60,hide:'', sort: ''},
                {field:'tthick', title: '管厚', width:60,hide:'', sort: ''},                
                {field:'tdf1', title: '纸管内容', width:74,hide:'', sort: ''},
                {field:'tdesc', title: '纸管其他要求', width:60,hide:'', sort: ''},
                {field:'icontent', title: '内箱内容', width:75,hide:'', sort: ''},
                {field:'icolor', title: '内箱颜色', width:75,hide:'', sort: ''},
                {field:'itm', title: '内箱条形码', width:130,hide:'', sort: ''},
                {field:'ocontent', title: '外箱内容', width:74,hide:'', sort: ''},
                {field:'ocolor', title: '外箱颜色', width:74,hide:'', sort: ''},
                {field:'otm', title: '外箱条形码', width:130,hide:'', sort: ''},
                // {field:'', title: '内容', width:'',hide:'', sort: ''},
                {field:'', title: '外封箱要求', width:88,hide:'', sort: ''},
                {field:'customerCode', title: '客户货号', width:92,hide:''},
                {field:'barCode', title: '条形码', width:130,hide:''},
                {field:'tuoPan', title: '托盘', width:110,hide:'', sort: ''},
                {field:'tpdesc', title: '托盘特殊要求',width:105,hide:'', sort: ''},
                {field:'sfieldText', title: '产品参数',width:90,hide:'true'},
                {field:'desc', title: '备注',width:190,hide:''},
               
            ]],
        
        sale_product_list:[[
                {type:'numbers', title: '序号', width:80,hide:'', fixed: 'left'},
                {field:'order_num_show', title: '订单号', width:"150",hide:'', sort: "", fixed: 'left'},
                {field:'u8Code', title: '存货编码',event:'', width:100,hide:'',edit:'', fixed: 'left'}, 
                {field:'name', title: '产品名称',event:'', width:100,hide:'', fixed: 'left'},
                {field:'customerCode', title: '客户货号', width:92,hide:'', fixed: 'left'},
                {field:'proColor', title: '产品颜色', width:92,hide:'', fixed: 'left'},
                {field:'o_width', title: '宽MM', width:70,hide:'', fixed: 'left'},
                {field:'o_length', title: '长M', width:70,hide:'', fixed: 'left'},
                {field:'norms', title: '产品规格',event:'', width:100,hide:true,edit:'',templet: function(d){
                    return d.length+"M*"+d.width+"MM";
                }}, 
                {field:'poUantity', title: '合同数量',event:'', width:100,hide:''},
                {field:'boxNum', title: '合同箱数', width:100,hide:'' },
                {field:'proUantity', title: '生产数量', width:90,hide:''},
                {field:'boxNum', title: '生产箱数', width:100,hide:''},
                {field:'proUnit', title: '生产单位', width:100,hide:'' },
                
                {field:'sample_num1', title: '样品数量', width:110,edit:'text',hide:''},
                {field:'sample_num2', title: '样品数量(免费)', width:110,edit:'text',hide:true},
                {field:'num', title: '订舱数量',event:'', width:100,hide:'',edit:'text'}, 
                {field:'box_num', title: '订舱箱数', width:100,hide:'', edit:'text',totalRow: true }, 
                {field:'poPrice', title: '单价',event:'', width:100,hide:'',edit:'text',totalRow: true},
                {field:'rmbPrice', title: '单价(RMB)',event:'', width:100,hide:'',totalRow: true},
                {field:'poPrice2', title: '公斤单价',event:'', width:100,hide:'',totalRow: true},
                
                {field:'total_1', title: '总价',event:'', width:100,hide:'',totalRow: true},
                {field:'rmbTotal1', title: '总价(RMB)',event:'', width:100,hide:'',totalRow: true},
                
                {field:'apportion_price', title: '分摊价格',event:'', width:150,hide:'',sort: ""},
                {field:'apportion_total', title: '分摊后总价格',event:'', width:150,hide:'',sort: "",totalRow: true },
                
                {field:'jz_1', title: '净重',event:'', width:100,hide:'',edit:'text',totalRow: true}, 
                {field:'mz_1', title: '毛重',event:'', width:100,hide:'',edit:'text',totalRow: true}, 
                {field:'tj_1', title: '总体积',event:'', width:150,hide:'',edit:'text',totalRow: true}, 
                {field:'zs_zjz', title: '真实净重',event:'', width:100,hide:'',edit:'',totalRow: true}, 
                {field:'zs_zmz', title: '真实毛重',event:'', width:100,hide:'',edit:'',totalRow: true}, 
                
                {field:'c_dwjz', title: '客户要求单位净重',event:'', width:150,hide:''}, 
                {field:'c_dwmz', title: '客户要求单位毛重',event:'', width:150,hide:''}, 
                {field:'c_dwtj', title: '客户要求单位体积',event:'', width:150,hide:''},  

                {field:'dwjz', title: '单位净重',event:'', width:100,hide:'',edit:''}, 
                {field:'dwmz', title: '单位毛重',event:'', width:100,hide:'',edit:''}, 
                {field:'dwtj', title: '单位体积',event:'', width:150,hide:'',edit:''},  
                
                
                {field:'dwxs', title: '单位系数',event:'', width:100,hide:'',edit:''}, 
                {field:'u8Code', title: '存货编码',event:'', width:100,hide:'',edit:''}, 
                {field:'inum', title: '内装箱数', width:100,hide:''},
            
                {field:'icontent', title: '内箱内容', width:75,hide:'', sort: ''},
                {field:'icolor', title: '内箱颜色', width:75,hide:'', sort: ''},
                {field:'itm', title: '内箱条形码', width:130,hide:'', sort: ''},
                {field:'ocontent', title: '外箱内容', width:74,hide:'', sort: ''},
                {field:'ocolor', title: '外箱颜色', width:74,hide:'', sort: ''},
                {field:'otm', title: '外箱条形码', width:130,hide:'', sort: ''},
                // {field:'', title: '内容', width:'',hide:'', sort: ''},
                {field:'', title: '外封箱要求', width:88,hide:'', sort: ''},
                {field:'tuoPan', title: '托盘', width:110,hide:''},
                {field:'tuopan_num', title: '托盘数量',event:'', width:100,hide:'',edit:'text',totalRow: true}, 
                {field:'tuopan_weight', title: '托盘总重',event:'', width:100,hide:'',edit:'text',totalRow: true}, 
                {field:'tuopan_size', title: '托盘尺寸',event:'', width:100,hide:'',edit:'text',totalRow: true}, 
                {field:'tuopan_display', title: '托盘摆法',event:'', width:100,hide:'',edit:'text',totalRow: true},
                {field:'tuopan_group', title: '托盘分组',event:'', width:100,hide:'',sort: true,totalRow: true}, 
                {field:'tpdesc', title: '托盘特殊要求',width:105,hide:'', sort: ''},
            
         
                {field:'length', title: '长',event:'', width:60,hide:'',edit:''}, 
                {field:'width', title: '宽',event:'', width:60,hide:'',edit:''}, 
                {field:'height', title: '高',event:'', width:60,hide:'',edit:''}, 
                {field:'formula', title: '单位体积计算公式',event:'', width:300,hide:'',edit:''}, 
                {field:'u8Code', title: '存货编码', width:115,hide:'',event:'editU8code'},
                
                
                {field:'complete', title: '成品/半成品',event:'', width:150,hide:''},
                {field:'category', title: '产品分类',event:'', width:210,hide:''},
               
                {field:'cInvCode', title: '产品型号',event:'cInvCode', width:'200',hide:''},
                {field:'poUnit', title: '合同单位',event:'', width:100,hide:''},

               
                {field:'inum', title: '内装箱数', width:100,hide:''},
                {field:'onum', title: '外装箱数', width:100,hide:''},
               
                {field:'proColor', title: '颜色', width:150,hide:''},
               
                {field:'glueSty', title: '胶系', width:80,hide:''},
                {field:'sfieldText', title: '产品特性', width:90,hide:''},
                {field:'baoZhuangMS', title: '包装方式', width:500,hide:'', sort: ''},
                {field:'tintd', title: '内径', width:60,hide:'', sort: ''},
                {field:'tthick', title: '管厚', width:60,hide:'', sort: ''},                
                {field:'tdf1', title: '纸管内容', width:74,hide:'', sort: ''},
                {field:'tdesc', title: '纸管其他要求', width:60,hide:'', sort: ''},
                
                
                {field:'barCode', title: '条形码', width:130,hide:''},
             
                {field:'sfieldText', title: '产品参数',width:90,hide:'true'},
                {field:'desc', title: '备注',width:190,hide:''},
            {field:'listpo', title: '产品PO号', width:92,hide:'', fixed: 'left'},
        ]],
        sale_product_list2:[[
            {type:'numbers', title: '序号', width:80,hide:'', fixed: 'left'},
            {field:'order_num_show', title: '订单号', width:"150",hide:'', sort: "", fixed: 'left'},
            {field:'u8Code', title: '存货编码',event:'', width:100,hide:'',edit:'', fixed: 'left'}, 
            {field:'name', title: '产品名称',event:'', width:100,hide:'', fixed: 'left'},
            {field:'customerCode', title: '客户货号', width:92,hide:'', fixed: 'left'},
            {field:'proColor', title: '产品颜色', width:92,hide:'', fixed: 'left'},
            {field:'o_width', title: '宽MM', width:70,hide:'', fixed: 'left'},
            {field:'o_length', title: '长M', width:70,hide:'', fixed: 'left'},
            {field:'norms', title: '产品规格',event:'', width:100,hide:true,edit:'',templet: function(d){
                return d.length+"M*"+d.width+"MM";
            }}, 
            {field:'poUantity', title: '合同数量',event:'', width:100,hide:''},
            {field:'boxNum', title: '合同箱数', width:100,hide:'' },
            {field:'proUantity', title: '生产数量', width:90,hide:''},
            {field:'boxNum', title: '生产箱数', width:100,hide:''},
            {field:'proUnit', title: '生产单位', width:100,hide:'' },
            
            {field:'sample_num1', title: '样品数量', width:110,edit:'text',hide:''},
            {field:'sample_num2', title: '样品数量(免费)', width:110,edit:'text',hide:true},
            {field:'num', title: '订舱数量',event:'', width:100,hide:'',edit:'text'}, 
            {field:'box_num', title: '订舱箱数', width:100,hide:'', edit:'text',totalRow: true }, 
            {field:'poPrice', title: '单价',event:'', width:100,hide:'',edit:'text',totalRow: true},
            {field:'poPrice2', title: '公斤单价',event:'', width:100,hide:'',totalRow: true},
            
            {field:'total_1', title: '总价',event:'', width:100,hide:'',totalRow: true},
            
            {field:'apportion_price', title: '分摊价格',event:'', width:150,hide:'',sort: ""},
            {field:'apportion_total', title: '分摊后总价格',event:'', width:150,hide:'',sort: "",totalRow: true },
            
            {field:'jz_1', title: '净重',event:'', width:100,hide:'',edit:'',totalRow: true}, 
            {field:'mz_1', title: '毛重',event:'', width:100,hide:'',edit:'',totalRow: true}, 
            {field:'tj_1', title: '总体积',event:'', width:150,hide:'',edit:'',totalRow: true}, 
            
            
            {field:'c_dwjz', title: '客户要求单位净重',event:'', width:150,hide:''}, 
            {field:'c_dwmz', title: '客户要求单位毛重',event:'', width:150,hide:''}, 
            {field:'c_dwtj', title: '客户要求单位体积',event:'', width:150,hide:''},  

            {field:'dwjz', title: '单位净重',event:'', width:100,hide:'',edit:''}, 
            {field:'dwmz', title: '单位毛重',event:'', width:100,hide:'',edit:''}, 
            {field:'dwtj', title: '单位体积',event:'', width:150,hide:'',edit:''},  
            
            
            {field:'dwxs', title: '单位系数',event:'', width:100,hide:'',edit:''}, 
            {field:'u8Code', title: '存货编码',event:'', width:100,hide:'',edit:''}, 
            {field:'inum', title: '内装箱数', width:100,hide:''},
        
            {field:'icontent', title: '内箱内容', width:75,hide:'', sort: ''},
            {field:'icolor', title: '内箱颜色', width:75,hide:'', sort: ''},
            {field:'itm', title: '内箱条形码', width:130,hide:'', sort: ''},
            {field:'ocontent', title: '外箱内容', width:74,hide:'', sort: ''},
            {field:'ocolor', title: '外箱颜色', width:74,hide:'', sort: ''},
            {field:'otm', title: '外箱条形码', width:130,hide:'', sort: ''},
            // {field:'', title: '内容', width:'',hide:'', sort: ''},
            {field:'', title: '外封箱要求', width:88,hide:'', sort: ''},
            {field:'tuoPan', title: '托盘', width:110,hide:''},
            {field:'tuopan_num', title: '托盘数量',event:'', width:100,hide:'',edit:'text',totalRow: true}, 
            {field:'tuopan_weight', title: '托盘总重',event:'', width:100,hide:'',edit:'text',totalRow: true}, 
            {field:'tuopan_size', title: '托盘尺寸',event:'', width:100,hide:'',edit:'text',totalRow: true}, 
            {field:'tuopan_display', title: '托盘摆法',event:'', width:100,hide:'',edit:'text',totalRow: true},
            {field:'tuopan_group', title: '托盘分组',event:'', width:100,hide:'',sort: true,totalRow: true}, 
            {field:'tpdesc', title: '托盘特殊要求',width:105,hide:'', sort: ''},
        
     
            {field:'length', title: '长',event:'', width:60,hide:'',edit:''}, 
            {field:'width', title: '宽',event:'', width:60,hide:'',edit:''}, 
            {field:'height', title: '高',event:'', width:60,hide:'',edit:''}, 
            {field:'formula', title: '单位体积计算公式',event:'', width:300,hide:'',edit:''}, 
            {field:'u8Code', title: '存货编码', width:115,hide:'',event:'editU8code'},
            
            
            {field:'complete', title: '成品/半成品',event:'', width:150,hide:''},
            {field:'category', title: '产品分类',event:'', width:210,hide:''},
           
            {field:'cInvCode', title: '产品型号',event:'cInvCode', width:'200',hide:''},
            {field:'poUnit', title: '合同单位',event:'', width:100,hide:''},

           
            {field:'inum', title: '内装箱数', width:100,hide:''},
            {field:'onum', title: '外装箱数', width:100,hide:''},
           
            {field:'proColor', title: '颜色', width:150,hide:''},
           
            {field:'glueSty', title: '胶系', width:80,hide:''},
            {field:'sfieldText', title: '产品特性', width:90,hide:''},
            {field:'baoZhuangMS', title: '包装方式', width:500,hide:'', sort: ''},
            {field:'tintd', title: '内径', width:60,hide:'', sort: ''},
            {field:'tthick', title: '管厚', width:60,hide:'', sort: ''},                
            {field:'tdf1', title: '纸管内容', width:74,hide:'', sort: ''},
            {field:'tdesc', title: '纸管其他要求', width:60,hide:'', sort: ''},
            {field:'barCode', title: '条形码', width:130,hide:''},
         
            {field:'sfieldText', title: '产品参数',width:90,hide:'true'},
            {field:'desc', title: '备注',width:190,hide:''},
            {field:'listpo', title: '产品PO号', width:92,hide:'', fixed: 'left'},
    ]],
        
        
        store_product_list:[[
            {type: 'checkbox', width:'', align:'center', toolbar: '#tab1Bar'},
            {type:'numbers', title: '序号', width:80,hide:''},
            {field:'order_num_show', title: '订单号', width:"200",hide:'', sort: ""},
            {field:'num', title: '订舱数量',event:'', width:100,hide:''}, 
            {field:'poUantity', title: '总数量',event:'', width:100,hide:'',edit:''}, 
            {field:'box_num', title: '订舱箱数', width:100,hide:'',totalRow: true }, 
            {field:'tuoPan', title: '托盘', width:110,hide:'', sort: ''},
            {field:'tuopan_num', title: '托盘数量',event:'', width:100,hide:'',edit:'text',totalRow: true}, 
            {field:'tuopan_weight', title: '托盘总重',event:'', width:100,hide:'',edit:'text',totalRow: true}, 
            {field:'tuopan_size', title: '托盘尺寸',event:'', width:100,hide:'',edit:'text',totalRow: true}, 
            {field:'tuopan_display', title: '托盘摆法',event:'', width:100,hide:'',edit:'text',totalRow: true},
            {field:'tuopan_group', title: '托盘分组',event:'', width:100,hide:'',sort: true,totalRow: true}, 
            {field:'jz_1', title: '净重',event:'', width:100,hide:'',edit:'',totalRow: true}, 
            {field:'mz_1', title: '毛重',event:'', width:100,hide:'',edit:'',totalRow: true}, 
            {field:'tj_1', title: '总体积',event:'', width:150,hide:'',edit:'',totalRow: true}, 
            
            {field:'c_dwjz', title: '客户要求单位净重',event:'', width:150,hide:''}, 
            {field:'c_dwmz', title: '客户要求单位毛重',event:'', width:150,hide:''}, 
            {field:'c_dwtj', title: '客户要求单位体积',event:'', width:150,hide:''},  
            
            {field:'dwtj', title: '单位体积',event:'', width:150,hide:'',edit:''},  
            {field:'dwmz', title: '单位毛重',event:'', width:100,hide:'',edit:''}, 
            {field:'dwjz', title: '单位净重',event:'', width:100,hide:'',edit:''}, 
            {field:'dwxs', title: '单位系数',event:'', width:100,hide:'',edit:''}, 
            
            {field:'u8Code', title: '存货编码',event:'', width:100,hide:'',edit:''}, 

            {field:'length', title: '长',event:'', width:60,hide:'',edit:''}, 
            {field:'width', title: '宽',event:'', width:60,hide:'',edit:''}, 
            {field:'height', title: '高',event:'', width:60,hide:'',edit:''}, 
            {field:'formula', title: '单位体积计算公式',event:'', width:300,hide:'',edit:''}, 
            {field:'u8Code', title: '存货编码', width:115,hide:'',event:'editU8code'},

            {field:'complete', title: '成品/半成品',event:'', width:150,hide:''},
            {field:'category', title: '产品分类',event:'', width:210,hide:''},
            {field:'proColor', title: '颜色', width:150,hide:''},    
            {field:'name', title: '产品名称',event:'', width:210,hide:''},
            {field:'cInvCode', title: '产品型号',event:'cInvCode', width:'200',hide:''},
            {field:'proUnit', title: '生产单位',event:'', width:100,hide:''},
            {field:'org_num', title: '合同数量',event:'', width:100,hide:''},
            {field:'boxNum', title: '合同总箱数', width:100,hide:'' },
            {field:'glueSty', title: '胶系', width:80,hide:''},
            {field:'sfieldText', title: '产品特性', width:90,hide:''},
            {field:'baoZhuangMS', title: '包装方式', width:500,hide:'', sort: ''},
            {field:'tdf1', title: '纸管内容', width:200,hide:'', sort: ''},
            {field:'customerCode', title: '客户货号', width:200,hide:''},
        ]],
        
        plan_product_list:[[
            {type: 'checkbox', width:'', align:'center', toolbar: '#tab1Bar'},
            {type:'numbers', title: '序号', width:80,hide:''},
            {field:'order_num_show', title: '订单号', width:"200",hide:'', sort: ""},
            {field:'num', title: '订舱数量',event:'', width:100,hide:''}, 
            {field:'poUantity', title: '总数量',event:'', width:100,hide:'',edit:''}, 
            {field:'box_num', title: '订舱箱数', width:100,hide:'',totalRow: true }, 
            {field:'tuoPan', title: '托盘', width:110,hide:'', sort: ''},
            {field:'tuopan_num', title: '托盘数量',event:'', width:100,hide:'',edit:'text',totalRow: true}, 
            {field:'tuopan_weight', title: '托盘总重',event:'', width:100,hide:'',edit:'text',totalRow: true}, 
            {field:'tuopan_size', title: '托盘尺寸',event:'', width:100,hide:'',edit:'text',totalRow: true}, 
            {field:'tuopan_display', title: '托盘摆法',event:'', width:100,hide:'',edit:'text',totalRow: true},
            {field:'tuopan_group', title: '托盘分组',event:'', width:100,hide:'',sort: true,totalRow: true}, 
            {field:'u8Code', title: '存货编码', width:115,hide:'',event:'editU8code'},
            {field:'complete', title: '成品/半成品',event:'', width:150,hide:''},
            {field:'category', title: '产品分类',event:'', width:210,hide:''},
            {field:'proColor', title: '颜色', width:150,hide:''},    
            {field:'name', title: '产品名称',event:'', width:210,hide:''},
            {field:'cInvCode', title: '产品型号',event:'cInvCode', width:'200',hide:''},
            {field:'proUnit', title: '生产单位',event:'', width:100,hide:''},
            {field:'org_num', title: '合同数量',event:'', width:100,hide:''},
            {field:'boxNum', title: '合同总箱数', width:100,hide:'' },
            {field:'glueSty', title: '胶系', width:80,hide:''},
            {field:'sfieldText', title: '产品特性', width:90,hide:''},
            {field:'baoZhuangMS', title: '包装方式', width:500,hide:'', sort: ''},
            {field:'tdf1', title: '纸管内容', width:200,hide:'', sort: ''},
            {field:'customerCode', title: '客户货号', width:200,hide:''},
        ]],
        
        'product_list_cabinet':[[
            {type: 'checkbox', width:'', align:'center', toolbar: '#tab1Bar'}, 
            {type:'numbers', title: '序号', width:80,hide:''},
            {field:'is_closing', title: '完工状态', width:80,hide:''},
            {field:'order_num_show', title: '订单号', width:"200",hide:''},
            {field:'num', title: '订舱数量',event:'', width:100,hide:'',edit:'text'}, 
            {field:'box_num', title: '订舱箱数', width:100,hide:'', edit:'text',totalRow: true },
            
            {field:'tuoPan', title: '托盘', width:110,hide:''},
            {field:'tuopan_num', title: '托盘数量',event:'', width:100,hide:'',edit:'text',totalRow: true}, 
            {field:'tuopan_weight', title: '托盘总重',event:'', width:100,hide:'',edit:'text',totalRow: true}, 
            {field:'tuopan_size', title: '托盘尺寸',event:'', width:100,hide:'',edit:'text',totalRow: true}, 
            {field:'tuopan_display', title: '托盘摆法',event:'', width:100,hide:'',edit:'text',totalRow: true},
            {field:'tuopan_group', title: '托盘分组',event:'', width:100,hide:'',sort: true,totalRow: true}, 
            
            {field:'cInvCode', title: '产品型号',event:'cInvCode', width:'200',hide:''},  
            {field:'u8Code', title: '存货编码', width:115,hide:'',event:'editU8code'},
            {field:'complete', title: '成品/半成品',event:'', width:150,hide:''},
            {field:'category', title: '产品分类',event:'', width:210,hide:''},
            {field:'proColor', title: '颜色', width:150,hide:''},    
            {field:'name', title: '产品名称',event:'', width:210,hide:''},
            {field:'cInvCode', title: '产品型号',event:'cInvCode', width:'200',hide:''},
            {field:'proUnit', title: '生产单位',event:'', width:100,hide:''},
            {field:'org_num', title: '合同数量',event:'', width:100,hide:''},
            {field:'boxNum', title: '合同总箱数', width:100,hide:'' },
            {field:'glueSty', title: '胶系', width:80,hide:''},
            {field:'sfieldText', title: '产品特性', width:90,hide:''},
            {field:'baoZhuangMS', title: '包装方式', width:500,hide:'', sort: ''},
            {field:'tdf1', title: '纸管内容', width:200,hide:'', sort: ''},
            {field:'customerCode', title: '客户货号', width:200,hide:''},
        ]],

        'cabinet_num': [[
            {field:'cabinet_type',width:100, title: '柜型'},
            {field:'cabinet1',event:'', title: '20尺柜', width:200,edit:'text'},
            {field:'cabinet2',event:'', title: '40尺平柜', width:200,edit:'text'},
            {field:'cabinet3',event:'', title: '40尺高柜', width:200,edit:'text'},
            {field:'cabinet4',event:'', title: '45尺柜', width:200,edit:'text'}, 
            {field:'cabinet5',event:'', title: 'NOR平柜', width:200,edit:'text'}, 
            {field:'cabinet6',event:'', title: 'NOR高柜', width:200,edit:'text'}, 
           
        ]], 
        'provebill':[[ 
                
            {type: 'checkbox', width:'', align:'center',hide:true, toolbar: '#tab1Bar',fixed: 'left'}, 
            {field: 'id', title: 'ID', width: 60, sort: true, fixed: 'left'}, 
           
            {field:'bill_num', title: '订舱单号', hide:true, width:250,fixed: 'left',
                templet: function(d){
                    var retInfo=d.bill_num;
                    if(d.backPrcs==1){
                        return "<span style='color:red' title='退回原因："+d.comment+"' >"+d.bill_num+"&nbsp;(退回)</span>";
                    }else if(d.has_adjust==1){
                        return "<span style='color:#FFB800' title='' >"+d.bill_num+"&nbsp;(有调整)</span>";
                    }else if(d.bill_status==1){
                        return "<span style='color:red' title='' >"+d.bill_num+"&nbsp;(已取消)</span>";
                    }
                    
                    
      
                    var arrayMap={"1":{"text":"系统已打印","color":""},"2":{"text":"物流已打印","color":""},"3":{"text":"仓库已打印","color":"#FFB800"}};
                    if(d.print_status!=0&&d.print_status!=""&&d.print_status!=null){
                        var style="";
                        if(arrayMap[d.print_status]["color"]!=""){
                            style="style='color:"+arrayMap[d.print_status]["color"]+"'";
                        } 
                        return "<span "+style+" title='"+arrayMap[d.print_status]["text"]+"' >"+d.bill_num+"&nbsp;("+arrayMap[d.print_status]["text"]+")</span>";
                    }
                 
                    

                   /* if(d.currentStep>3){
                        return "<span style='color:#ff5722' >"+d.bill_num+"&nbsp;(已审核)</span>";
                    }*/

                    return retInfo;
                } 
            },
            
            {field: 'invoice_num', title: '发票号',width:200,fixed: 'left',
                templet: function(d){
                    var retInfo=d.invoice_num;
                    if(d.rd_id>0){
                        return "<span style='color:red' title='由退货单生成' >"+d.invoice_num+"&nbsp;(退货)</span>";
                    }
                    if(d.backPrcs==1){
                        return "<span style='color:red' title='退回原因："+d.comment+"' >"+d.invoice_num+"&nbsp;(退回)</span>";
                    }else if(d.has_adjust==1){
                        return "<span style='color:#FFB800' title='' >"+d.invoice_num+"&nbsp;(有调整)</span>";
                    }else if(d.bill_status==1){
                        return "<span style='color:red' title='' >"+d.invoice_num+"&nbsp;(已取消)</span>";
                    }
                    
                    
    
                    var arrayMap={"1":{"text":"系统已打印","color":""},"2":{"text":"物流已打印","color":""},"3":{"text":"仓库已打印","color":"#FFB800"}};
                    if(d.print_status!=0&&d.print_status!=""&&d.print_status!=null){
                        var style="";
                        if(arrayMap[d.print_status]["color"]!=""){
                            style="style='color:"+arrayMap[d.print_status]["color"]+"'";
                        } 
                        return "<span "+style+" title='"+arrayMap[d.print_status]["text"]+"' >"+d.invoice_num+"&nbsp;("+arrayMap[d.print_status]["text"]+")</span>";
                    }
                
                    

                /* if(d.currentStep>3){
                        return "<span style='color:#ff5722' >"+d.bill_num+"&nbsp;(已审核)</span>";
                    }*/

                    return retInfo;
                } 
            }, 
            
            {field: 'sales_num', title: '销货单号',hide:'true',width:150,fixed: 'left'}, 
            
            {field: 'order_nums', title: '订单号',width:150,fixed: 'left'}, 
            {field: 'customer_short', title: '客户简称',width:100,fixed: 'left'}, 
            {field: 'cabinet_num', title: '柜型',width:150},
            {field: 'give_time', title: '确认交期',width:150},
            {field: 'cabinet_time', title: '装柜日期',width:150},
            {field: 'ship_time',  title: '预计船期',width:150},
            {field: 'real_ship_time', title: '实际船期',width:150},
            {field: 'start_port', title: '起运港',width:100},
            {field: 'end_port', title: '目的港',width:100},
            {field: 'address', title: '发货地',width:100},
            {field: 'prove_type', title: '产地证及类型',width:100},           
            {field: 'customer_country', title: '国家',width:100}, 
            {field: 'customer_name', title: '客户名称',hide:'true',width:200}, 
            {field: 'customer_code', title: '客户编码',hide:'true'}, 
            {field: 'load_type', title: '装载类型',width:80},
            {field: 'trade_type', title: '贸易方式',width:80},
            {field: 'inout_type', title: '提放单方式',width:100},
            {field: 'pay_type', title: '付款方式',width:100},
            {field: 'coin_type', title: '币种',width:80},
            {field: 'adjust_money', title: '调账金额',width:100},
            {field: 'brand', title: '品牌',width:100},
            {field: 'inspect_time', title: '验货时间',width:150},       
            {field: 'is_appoint', title: '是否指定货代或船公司',width:100, align:'center'},
            {field: 'appoint_info', title: '指定货代或船公司信息',width:200},
            {field: 'customer_start', title: '客户要求开船日期',width:150},
            {field: 'customer_end', title: '客户要求到港日期',width:150},
            
            {field: 'status', title: '当前步骤',width:150},
            {field: 'prcsFlag', title: '当前状态',width:150},
            
            
            {field: 'create_user', title: '制单人',width:100},
            {field: 'deliver_create_time', title: '销货单提交时间', hide:"true" ,width:150},
            {field: 'create_time', title: '制单时间',width:150},
            {field: 'atTime', title: '提交时间',width:150},
            {fixed: 'right', width: 500, align: 'center', toolbar: '#tab1Bar'}
        ]],

        'bill_deliver':[[
            {type: 'radio', width:'', align:'center', toolbar: '#tab1Bar'},
            {field: 'id', title: 'ID', width: 60, sort: true}, 
            {field: 'deliverId', title: 'ID', width: 60, sort: true, hide:"true"}, 
            {field: 'deliver_num', title: '发货单号', width:260},
            {field: 'order_num', title: '订单号',width:300},
            {field: 'sales_num', title: '销货单号',width:200, sort: 'true'}, 
            {field: 'customer_short', title: '客户简称',width:200}, 
            {field: 'deliver_date', title: '发货时间',width:100}, 
            {field: 'deliver_store', title: '发货仓库',width:100},
            {field: 'coin_type', title: '币种',width:100},
            {field: 'create_time', title: '创建日期',width:150},
            {fixed: 'right', width: 400, align: 'center', toolbar: '#tab1Bar'}

        ]],
        'reback_deliver':[[
            {type: 'radio', width:'', align:'center', toolbar: '#tab1Bar'},
            {field: 'id', title: 'ID', width: 60, sort: true}, 
            {field: 'deliverId', title: 'ID', width: 60, sort: true, hide:"true"}, 
            // {field: 'bill_ids', title: 'ID', width: 60, sort: true, hide:"true"}, 

            {field:'deliver_num', title: '发货单号', width:260},
            {field: 'reback_num', title: '退货单号',width:300},
            {field: 'order_num', title: '订单号',width:300},
            {field: 'sales_num', title: '销货单号',width:200, sort: 'true'}, 
            {field: 'customer_short', title: '客户简称',width:200}, 
            {field: 'ctime', title: '退货时间',width:150}, 
            {field: 'total_je', title: '退货金额',width:100},
            {field: 'total_sl', title: '退货数量',width:100},
            {field: 'total_js', title: '退货件数',width:100},
            {field: 'status', title: '审核状态',width:100},
            {field: 'shr_name', title: '审核人',width:100},
            {field: 'sh_time', title: '审核时间',width:150},
            {field: 'ctime', title: '创建日期',width:150},
            {field: 'cname', title: '创建人',width:100},
            {fixed: 'right', width: 400, align: 'center', toolbar: '#tab1Bar'}

        ]],
        'reback_stat':[[
            {field: 'id', title: 'ID', width: 60, sort: true}, 
            {field:'deliver_num', title: '发货单号', width:260},
            {field: 'reback_num', title: '退货单号',width:300},
            {field: 'sales_num', title: '销货单号',width:200}, 
            {field: 'cInvCode', title: '存货编码',width:200}, 
            {field: 'name', title: '名称',width:200}, 
            {field: 'remain_je', title: '退货金额',width:100},
            {field: 'remain_sl', title: '退货数量',width:100},
            {field: 'remain_js', title: '退货件数',width:100},
            {field: 'ctime', title: '创建日期',width:150}
        ]],
        'bill_deliver_products':[[
            {type: 'checkbox', width:'', align:'center', toolbar: '#tab1Bar'},
            {type:'numbers', title: '行号', width:50,hide:''},
            {field:'id', title: '明细ID', width:"200",hide:true, sort: ""},
            {field:'order_num_show', title: '订单号', width:"200",hide:'', sort: ""},
            {field:'u8Code', title: '存货编码', width:115,hide:'',event:'editU8code'},            
            {field: 'chmc', title: '存货名称',width:150,sort: true},
            {field:'glueSty', title: '胶系', width:80,hide:''},
            {field:'norms', title: '规格型号',event:'', width:100,hide:true,edit:'',templet: function(d){
                return d.length+"M*"+d.width+"MM";
            }}, 
            {field:'deliver_num', title: '总发货数量', width:80,hide:'',totalRow: true },
            // {field:'remain_deliver_num', title: '剩余可退货数量', width:80,hide:'',totalRow: true },
            {field:'sl', title: '本次退货数量', width:120,hide:'',edit:'text',totalRow: true },
            {field:'poUnit', title: '单位',event:'', width:100,hide:true},
            {field:'sldw', title: '单位',event:'', width:100,hide:true},
            {field:'proUnit', title: '单位',event:'', width:60,hide:""},
            {field:'deliver_box', title: '总发货件数',event:'', width:80,totalRow: true},
            {field:'remain_deliver_box', title: '剩余可退货件数',event:'', width:80,totalRow: true},
            {field:'js', title: '本次订舱数量', width:120,hide:'',edit:'text',totalRow: true },
            {field:'poUantity', title: '合同数量',event:'', width:80,hide:'',totalRow: true},
            {field:'proPrice', title: '生产单价',event:'', width:100,hide:''},
            {field:'poPrice1', title: '合同单价',event:'', width:100,hide:''},
            {field:'poPrice', title: '单价',event:'', width:100,hide:'',edit:'text',totalRow: true},
            {field:'total_1', title: '原币金额',event:'', width:100,hide:'',totalRow: true},
            // {field:'remain_total_1', title: '剩余可退货原币金额',event:'', width:100,hide:'',totalRow: true},
            {field:'je', title: '本次退货金额',event:'', width:120,hide:'',edit:'text',totalRow: true},
            {field:'fgweights', title: '发货毛重',event:'', width:100,hide:'',totalRow: true},
            {field:'fnweights', title: '发货净重',event:'', width:100,hide:'',totalRow: true},
            {field:'xztm', title: '袋子及箱子条码',event:'', width:300,hide:''},
            {field:'zpfs', title: '平方数',event:'', width:300,hide:''},
            {field:'mz_1', title: '毛重',event:'', width:100,hide:'',edit:'',totalRow: true}, 
            {field:'jz_1', title: '净重',event:'', width:100,hide:'',edit:'',totalRow: true}, 
            {field:'tj_1', title: '总体积',event:'', width:150,hide:'',edit:'',totalRow: true}, 

            {field:'dwtj', title: '单位体积',event:'', width:150,hide:'',edit:''},  
            {field:'dwmz', title: '单位毛重',event:'', width:100,hide:'',edit:''}, 
            {field:'dwjz', title: '单位净重',event:'', width:100,hide:'',edit:''}, 
            {field:'dwxs', title: '单位系数',event:'', width:100,hide:'',edit:''}, 

            {field:'length', title: '长',event:'', width:60,hide:'',edit:''}, 
            {field:'width', title: '宽',event:'', width:60,hide:'',edit:''}, 
            {field:'height', title: '高',event:'', width:60,hide:'',edit:''}, 
            {field:'formula', title: '单位体积计算公式',event:'', width:300,hide:'',edit:''}, 
           

            {field:'complete', title: '成品/半成品',event:'', width:150,hide:''},
            {field:'category', title: '产品分类',event:'', width:210,hide:''},
            {field:'proColor', title: '颜色', width:150,hide:''},    
            {field:'name', title: '产品名称',event:'', width:210,hide:''},
            {field:'cInvCode', title: '产品型号',event:'cInvCode', width:'200',hide:''},
            {field:'proUnit', title: '生产单位',event:'', width:100,hide:''},
            {field:'poUantity', title: '合同数量',event:'', width:100,hide:''},
            {field:'boxNum', title: '合同总箱数', width:100,hide:'' },
           
            {field:'sfieldText', title: '产品特性', width:90,hide:''},
            {field:'baoZhuangMS', title: '包装方式', width:500,hide:'', sort: ''},
            {field:'tdf1', title: '纸管内容', width:200,hide:'', sort: ''},
            {field:'customerCode', title: '客户货号', width:200,hide:''},
            
        ]], 
    
        'provebill_cabinet':[[ 
            {type: 'checkbox', width:'', align:'center', fixed: 'left', toolbar: '#tab1Bar'},    
            {field: 'id', title: 'ID', width: 60, sort: true, fixed: 'left'}, 
           
            {field:'bill_num', title: '订舱单号', width:230,fixed: 'left',
                templet: function(d){
                    var retInfo=d.bill_num;
                    if(d.backPrcs==1){
                        return "<span style='color:red' title='退回原因："+d.comment+"' >"+d.bill_num+"&nbsp;(退回)</span>";
                    }else if(d.has_adjust==1){
                        return "<span style='color:#FFB800' title='' >"+d.bill_num+"&nbsp;(有调整)</span>";
                    }

                   /* if(d.currentStep>3){
                        return "<span style='color:#ff5722' >"+d.bill_num+"&nbsp;(已审核)</span>";
                    }*/

                    return retInfo;
                } 
            },
            
            {field: 'invoice_num', title: '发票号',width:200,fixed: 'left'}, 
            {field: 'order_nums', title: '订单号',width:150,fixed: 'left'}, 
            {field: 'customer_short', title: '客户简称',width:100,fixed: 'left'},
            {field: 'customer_country', title: '国家',width:100},
            {field: 'customer_name', title: '客户名称',hide:'true',width:200}, 
            {field: 'customer_code', title: '客户编码',hide:'true'}, 
            {field: 'load_type', title: '装载类型',width:80},
            {field: 'trade_type', title: '贸易方式',width:80},
            {field: 'inout_type', title: '提放单方式',width:100},
            {field: 'pay_type', title: '付款方式',width:100},
            {field: 'coin_type', title: '币种',width:80},
            {field: 'adjust_money', title: '调账金额',width:100},
            {field: 'brand', title: '品牌',width:100},
            {field: 'prove_type', title: '产地证及类型',width:100},
            {field: 'start_port', title: '起运港',width:100},
            {field: 'end_port', title: '目的港',width:100},

            {field: 'give_time', title: '确认交期',width:150},

            {field: 'inspect_time', title: '验货时间',width:150},         
            {field: 'is_appoint', title: '是否指定货代或船公司',width:100, align:'center'},
            {field: 'appoint_info', title: '指定货代或船公司信息',width:200},
            {field: 'customer_start', title: '客户要求开船日期',width:150},
            {field: 'customer_end', title: '客户要求到港日期',width:150},
            
            {field: 'status', title: '当前步骤',width:150},
            {field: 'prcsFlag', title: '当前状态',width:150},
            
            
            {field: 'create_user', title: '制单人',width:100},
            {field: 'create_time', title: '制单时间',width:150},
            {field: 'atTime', title: '提交时间',width:150},
            {field: 'differTime', title: '相差时间',width:150},
            /*{field: 'td_time', title: '提单上传时间',width:150},
            {field: 'cdz_time', title: '产地证上传时间',width:150},*/
            {fixed: 'right', width: 300, align: 'center', toolbar: '#tab1Bar'}
        ]],

       /* get_provebill_cols(store){
            console.log(store);
            this.provebill[0][1]={
                field:'bill_num', title: '订舱单号', width:250,fixed: 'left',
                templet: function(d){
                    var retInfo=d.bill_num;
                    if(d.backPrcs==1){
                        return "<span style='color:red' title='退回原因："+d.comment+"' >"+d.bill_num+"&nbsp;(退回)</span>";
                    }

                    if(d.currentStep>3&&store["params"]["showType"]==17){
                        return "<span style='color:#ff5722' >"+d.bill_num+"&nbsp;(已审核)</span>";
                    }

                    return retInfo;
                }
            };

            return this.provebill;
        },*/

        'selectbill':function(colsType){
           
            return [[
                {type: 'checkbox', width:'', align:'center',hide:true, toolbar: '#tab1Bar',fixed: 'left'}, 
                {field: 'id', title: 'ID', width: 60, sort: true, fixed: 'left'}, 
                {field: 'invoice_num', title: '发票号',width:200,fixed: 'left',
                    templet: function(d){
                        var retInfo=d.invoice_num;
                        if(d.backPrcs==1){
                            return "<span style='color:red' title='退回原因："+d.comment+"' >"+d.invoice_num+"&nbsp;(退回)</span>";
                        }else if(d.has_adjust==1){
                            return "<span style='color:#FFB800' title='' >"+d.invoice_num+"&nbsp;(有调整)</span>";
                        }else if(d.bill_status==1){
                            return "<span style='color:red' title='' >"+d.invoice_num+"&nbsp;(已取消)</span>";
                        }



                        var arrayMap={"1":{"text":"系统已打印","color":""},"2":{"text":"物流已打印","color":""},"3":{"text":"仓库已打印","color":"#FFB800"}};
                        if(d.print_status!=0&&d.print_status!=""&&d.print_status!=null){
                            var style="";
                            if(arrayMap[d.print_status]["color"]!=""){
                                style="style='color:"+arrayMap[d.print_status]["color"]+"'";
                            } 
                            return "<span "+style+" title='"+arrayMap[d.print_status]["text"]+"' >"+d.invoice_num+"&nbsp;("+arrayMap[d.print_status]["text"]+")</span>";
                        }



                    /* if(d.currentStep>3){
                            return "<span style='color:#ff5722' >"+d.bill_num+"&nbsp;(已审核)</span>";
                        }*/

                        return retInfo;
                    } 
                }, 

                {field: 'sales_num', title: '销货单号',hide:'true',width:150,fixed: 'left'}, 

                {field: 'order_nums', title: '订单号',width:150,fixed: 'left'}, 
                {field: 'customer_short', title: '客户简称',width:100,fixed: 'left'}, 
                {field: 'cabinet_num', title: '柜型',width:150},
                {field: 'give_time', title: '确认交期',width:150},
                {field: 'cabinet_time', title: '装柜日期',width:150},
                {field: 'ship_time', title: '预计船期',width:150},
                {field: 'real_ship_time', title: '实际船期',width:150},
                {field: 'start_port', title: '起运港',width:100},
                {field: 'end_port', title: '目的港',width:100},
                {field: 'address', title: '发货地',width:100},
                {field: 'prove_type', title: '产地证及类型',width:100},           
                {field: 'customer_country', title: '国家',width:100}, 
                {field: 'customer_name', title: '客户名称',hide:'true',width:200}, 
                {field: 'customer_code', title: '客户编码',hide:'true'}, 
                {field: 'load_type', title: '装载类型',width:80},
                {field: 'trade_type', title: '贸易方式',width:80},
                {field: 'inout_type', title: '提放单方式',width:100},
                {field: 'pay_type', title: '付款方式',width:100},
                {field: 'coin_type', title: '币种',width:80},
                {field: 'adjust_money', title: '调账金额',width:100},
                {field: 'brand', title: '品牌',width:100},
                {field: 'inspect_time', title: '验货时间',width:150},       
                {field: 'is_appoint', title: '是否指定货代或船公司',width:100, align:'center'},
                {field: 'appoint_info', title: '指定货代或船公司信息',width:200},
                {field: 'customer_start', title: '客户要求开船日期',width:150},
                {field: 'customer_end', title: '客户要求到港日期',width:150},

                {field: 'status', title: '当前步骤',width:150},
                {field: 'prcsFlag', title: '当前状态',width:150},


                {field: 'create_user', title: '制单人',width:100},
                {field: 'deliver_create_time', title: '销货单提交时间', hide:"true" ,width:150},
                {field: 'create_time', title: '制单时间',width:150},

            ]];
        },

        'account_set':[[
            {field: 'id', title: 'ID', width: 60, sort: true}, 
            {field: 'zhangTaoText', title: '账套类型',width:200, sort: 'true'}, 
            {field: 'account_name', title: '账套名称（英文）',width:300}, 
            {field: 'account_address', title: '账套地址（英文）',width:300}, 
            {field: 'company_name', title: '公司全称（中文）',width:200},
            {field: 'entrust_man', title: '货运委托人',width:100},
            {field: 'entrust_tel', title: '货运联系人电话',width:200},
            {field: 'entrust_fax', title: '货运传真',width:200},
            {field: 'zip_code', title: '邮编',width:100},
            {field: 'create_user', title: '制单人',width:100},
            {field: 'create_time', title: '创建日期',width:150},
            {fixed: '', width: 200, align: 'center', toolbar: '#tab1Bar'}
        ]],
          'cattle_list':[[
              {field: 'id', title: 'ID', width: 60, sort: true},
              {field: 'cCode', title: '报关单号',width:200, sort: 'true'},
              {field: 'Code', title: '发票号',width:300},
              {field: 'dDate', title: '出口日期',width:'150'},
              {field: 'cblno', title: '提单号',width:'180'},
              {field: 'declarationName', title: '报关单附件名',width:'230',event:'declarationName'},
              {field: 'attId', title: '报关单id',hide:true},
              {field: 'attName', title: '报关单附件',hide:true},
              {field: 'dName', title: '委托书附件名',width:'230',event:'dName'},
              {field: 'attId2', title: '委托书id',hide:true},
              {field: 'attName2', title: '报关单附件',hide:true},
              {field: 'money_y', title: '运费/率',width:'100'},
              {field: 'currency_b', title: '保险费币种',width:'100'},
              {field: 'money_b', title: '保险费',width:'100'},
              {fixed: 'right', width: 200, align: 'center', toolbar: '#tab1Bar'}
          ]],
        'bill_confirm_list':[[
            {"field": "id", "title": "ID", "width": 60, "sort": "TRUE", "FIXED": "left"},
            {"field": "bill_num", "title": "单号","width":"250", "sort": "true","fixed": "left"},
            {"field": "invoice_num", "title": "订单号","width":"200", "sort": "true","fixed": "left"},
            {"field": "customer_name", "title": "客户名称","width":"200"},
            {"field": "customer_short", "title": "客户简称","width":"200"},

            {"field": "customer_code", "title": "客户编码","hide":"true"},
            {"field": "load_type", "title": "装载类型","width":"100"},
            {"field": "trade_type", "title": "贸易方式","width":"100"},
            {"field": "inout_type", "title": "提放单方式","width":"100"},
            {"field": "pay_type", "title": "付款方式","width":"100"},
            {"field": "coin_type", "title": "币种","width":"100"},
            {"field": "status", "title": "状态","width":"100"},
            {"field": "brand", "title": "品牌","width":"150"},
            {"field": "prove_type", "title": "产地证及类型","width":"100"},
            {"field": "start_port", "title": "起运港","width":"100"},
            {"field": "end_port", "title": "目的港","width":"100"},
            {"field": "give_time", "title": "确认交期","width":"150"},
            {"field": "give_time_change", "title": "交期是否变更","width":"150"},
            {"field": "inspect_time", "title": "验货时间","width":"150"},
            {"field": "is_appoint", "title": "是否指定货代或船公司","width":"100", "align":"center"},
            {"field": "appoint_info", "title": "指定货代或船公司信息","width":200},
            {"field": "customer_start", "title": "客户要求开船日期","width":150},
            {"field": "customer_end", "title": "客户要求到港日期","width":150},
            {"field": "beginTime", "title": "变更时间","width":150},
            {"field": "beginUserName", "title": "制单人","width":150},
            {"fixed": "right", "width":"100", "align":"center","hide":"", "toolbar": "#tab1Bar"}
        ]],

        'gendan_cabinet_product_list':[[
            {type: 'checkbox', width:'', align:'center', toolbar: '#tab1Bar'}, 
            {type:'numbers', title: '序号', width:80,hide:''},
            {field:'order_num_show', title: '订单号', width:"200",hide:''},
            {field:'num', title: '订舱数量',event:'', width:100,hide:'',edit:'text'}, 
            {field:'box_num', title: '订舱箱数', width:100,hide:'', edit:'text',totalRow: true },
            {field:'jz_1', title: '净重',event:'', width:100,hide:'',edit:'',totalRow: true}, 
            {field:'mz_1', title: '毛重',event:'', width:100,hide:'',edit:'',totalRow: true},
            {field:'tj_1', title: '总体积',event:'', width:150,hide:'',edit:'',totalRow: true}, 
            {field:'tuoPan', title: '托盘', width:110,hide:''},
            {field:'tuopan_num', title: '托盘数量',event:'', width:100,hide:'',edit:'text',totalRow: true}, 
            {field:'tuopan_weight', title: '托盘总重',event:'', width:100,hide:'',edit:'text',totalRow: true}, 
            {field:'tuopan_size', title: '托盘尺寸',event:'', width:100,hide:'',edit:'text',totalRow: true}, 
            {field:'tuopan_display', title: '托盘摆法',event:'', width:100,hide:'',edit:'text',totalRow: true},
            {field:'tuopan_group', title: '托盘分组',event:'', width:100,hide:'',sort: true,totalRow: true}, 
            
            {field:'cInvCode', title: '产品型号',event:'cInvCode', width:'200',hide:''},  
            {field:'u8Code', title: '存货编码', width:115,hide:'',event:'editU8code'},
            {field:'complete', title: '成品/半成品',event:'', width:150,hide:''},
            {field:'category', title: '产品分类',event:'', width:210,hide:''},
            {field:'proColor', title: '颜色', width:150,hide:''},    
            {field:'name', title: '产品名称',event:'', width:210,hide:''},
            {field:'cInvCode', title: '产品型号',event:'cInvCode', width:'200',hide:''},
            {field:'proUnit', title: '生产单位',event:'', width:100,hide:''},
            {field:'org_num', title: '合同数量',event:'', width:100,hide:''},
            {field:'boxNum', title: '合同总箱数', width:100,hide:'' },
            {field:'glueSty', title: '胶系', width:80,hide:''},
            {field:'sfieldText', title: '产品特性', width:90,hide:''},
            {field:'baoZhuangMS', title: '包装方式', width:500,hide:'', sort: ''},
            {field:'tdf1', title: '纸管内容', width:200,hide:'', sort: ''},
            {field:'customerCode', title: '客户货号', width:200,hide:''},
        ]],
        'pay_type':{
            "DP 60天":"DP 60Days","DP 90天":"DP 90Days","DP 120天":"DP 120Days","TT 30天":"TT 30Days","TT 40天":"TT 40Days","TT 45天":"TT 45Days",
            "TT 55天":"TT 55Days","TT 60天":"TT 60Days","TT 90天":"TT 90Days","TT 120天":"TT 120Days","见提单付款-40天":"40Days from BL","见提单付款-45天":"45Days from BL",
            "见提单付款-60天":"60Days from BL","DP30天":"DP 30Days","TT100天":"TT 100Days","即期信用证":"LC at sight","远期信用证-15天":"LC within 15Days",
            "远期信用证-30天":"LC within 30Days","远期信用证-45天":"LC within 45Days","远期信用证-90天":"LC within 90Days","远期信用证-120天":"LC within 120Days"
        },

  };

  exports('bill_cols', obj);
});   
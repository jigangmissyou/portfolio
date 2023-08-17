layui.define(function(exports){ 
  var obj = {
        'bill_deliver':[[
            {field: 'id', title: 'ID', width: 60, sort: true}, 

            {field:'deliver_num', title: '发货单号', width:260,
                templet: function(d){
                    var retInfo=d.deliver_num;
                    var arrayMap={"1":{"text":"系统已打印","color":""},"2":{"text":"物流已打印","color":""},"3":{"text":"仓库已打印","color":"#FFB800"}};
                    if(d.print_status!=0&&d.print_status!=""){
                        var style="";
                        if(arrayMap[d.print_status]["color"]!=""){
                            style="style='color:"+arrayMap[d.print_status]["color"]+"'";
                        } 
                        return "<span "+style+" title='"+arrayMap[d.print_status]["text"]+"' >"+d.deliver_num+"&nbsp;("+arrayMap[d.print_status]["text"]+")</span>";
                    }
                    return retInfo;
                } 
            },
            
            
            {field: 'order_num', title: '订单号',width:300},
            {field: 'invoice_nums', title: '发票号',width:300},
            {field: 'sales_num', title: '销货单号',width:200, sort: 'true'}, 
            {field: 'customer_short', title: '客户简称',width:200}, 
            {field: 'deliver_date', title: '发货时间',width:100}, 
            {field: 'sales_bill_date', title: '销货单据日期',width:100},
            {field: 'deliver_store', title: '发货仓库',width:100},
            
            {field: 'coin_type', title: '币种',width:100},
            {field: 'expiry_date', title: '到期日',width:100},
            {field: 'create_user', title: '制单人',width:100},
            {field: 'create_time', title: '创建日期',width:150},
            {fixed: 'right', width: 400, align: 'center', toolbar: '#tab1Bar'}
        ]], 
        
        
        product_list:[[
            {type: 'checkbox', width:'', align:'center', toolbar: '#tab1Bar'},
            {type:'numbers', title: '行号', width:50,hide:''},
            
            {field:'id', title: '明细ID', width:"200",hide:true, sort: ""},
            
            {field:'order_num_show', title: '订单号', width:"200",hide:'', sort: ""},
            {field:'u8Code', title: '存货编码', width:115,hide:'',event:'editU8code'},
            
            {field:'more', title:'分批发货', width:85, templet: function(d){
                    return '<input type="checkbox" data-id="'+d.id+'" value="'+d.more+'" lay-skin="switch" lay-text="是|否" lay-filter="more" '+ (d.more == '是' ? 'checked' : '')+'>'; 
            }, unresize: true},
            
            {field:'warehouse_name', title: '发货仓库', width:150,hide:''},
            
            {field: 'chmc', title: '存货名称',width:150,sort: true},
            {field:'glueSty', title: '胶系', width:80,hide:''},
            {field:'norms', title: '规格型号',event:'', width:100,hide:true,edit:'',templet: function(d){
                return d.length+"M*"+d.width+"MM";
            }}, 
        
            {field:'deliver_num', title: '发货数量', width:80,hide:'',edit:'',totalRow: true },
            
            {field:'poUnit', title: '单位',event:'', width:100,hide:true},
            {field:'sldw', title: '单位',event:'', width:100,hide:true},
            {field:'proUnit', title: '单位',event:'', width:60,hide:""},

            {field:'deliver_box', title: '发货件数',event:'', width:80,hide:'',totalRow: true},
            {field:'poUantity', title: '合同数量',event:'', width:80,hide:'',totalRow: true},
            {field:'proPrice', title: '生产单价',event:'', width:100,hide:''},
            {field:'poPrice1', title: '合同单价',event:'', width:100,hide:''},
            {field:'total_1', title: '原币金额',event:'', width:100,edit:'text',hide:'',totalRow: true},
            {field:'apportion_price', title: '分摊价格',event:'', width:100,hide:'',totalRow: true},
            {field:'apportion_total', title: '分摊后总价',event:'', width:100,hide:'',totalRow: true},
            
            
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
            {fixed: 'right', width:150, align:'center',hide:'', toolbar: '#tab2Bar'}
            
        ]],
    
    
        'product_list1': [[
            {type: 'checkbox', width:'', align:'center', toolbar: '#tab1Bar'},
            {field:'id', title: '编号', width:50,hide:'true', sort: true, fixed: 'left'},
            {type:'numbers', title: '序号', width:80,hide:''},
            {field:'order_num_show', title: '订单号', width:150,hide:'',},
            {field:'u8Code', title: '存货编码', width:115,hide:true,event:'editU8code'},
            {field:'cInvCode', title: '型号',event:'cInvCode',hide:true, width:'200'},
            {field:'deliver_num', title: '发货数量',event:'deliver_num', width:'100',hide:'',edit:'',totalRow: true},
            {field:'poUnit', title: '单位',event:'', width:100,hide:''},
            {field:'deliver_box', title: '发货件数',event:'deliver_box', width:'100',hide:''},
            {field:'proColor', title: '颜色', width:150,hide:''},
            {field:'name', title: '产品名称',event:'', width:210,hide:''},
            
            
            {field:'mz_1', title: '毛重',event:'', width:100,hide:'',edit:'',totalRow: true}, 
            {field:'jz_1', title: '净重',event:'', width:100,hide:'',edit:'',totalRow: true}, 
            {field:'tj_1', title: '总体积',event:'', width:150,hide:'',edit:'',totalRow: true}, 
            
            {field:'dwtj', title: '单位体积',event:'', width:150,hide:'',edit:''},  
            {field:'dwmz', title: '单位毛重',event:'', width:100,hide:'',edit:''}, 
            {field:'dwjz', title: '单位净重',event:'', width:100,hide:'',edit:''}, 
            {field:'dwxs', title: '单位系数',event:'', width:100,hide:'',edit:''}, 
            
            
            
            {field:'poUnit', title: '单位',event:'', width:100,hide:''},
            {field:'poPrice', title: '单价',event:'', width:200,hide:''},
            {field:'', title: '袋子及箱子条码',event:'', width:300,hide:''},
            {field:'complete', title: '成品/半成品',event:'', width:150,hide:''},
            {field:'category', title: '产品分类',event:'', width:210,hide:''},
            {field:'proUnit', title: '生产单位',event:'', width:100,hide:''},
            {field:'poUantity', title: '合同数量',event:'', width:100,hide:''},
            {field:'boxNum', title: '合同总箱数', width:100,hide:'' },
           
            {field:'sfieldText', title: '产品特性', width:90,hide:''},
            {field:'baoZhuangMS', title: '包装方式', width:500,hide:'', sort: ''},
            {field:'tdf1', title: '纸管内容', width:200,hide:'', sort: ''},
            {field:'customerCode', title: '客户货号', width:200,hide:''},
            {fixed: 'right', width:150, align:'center',hide:'', toolbar: '#tab2Bar'}
        ]],
        
    
        'warehouse_select_list':[[
            {type: 'radio', width:'', align:'center', toolbar: '#tab1Bar'},
            {field: 'cWhCode', title: '仓库编码',width:150},
            {field: 'cWhName', title: '仓库名称',width:300},
            {field: 'cWhPerson', title: '仓库负责人',width:150}
        ]],
        
  };

  exports('deliver_cols', obj);
});   
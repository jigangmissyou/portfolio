layui.define(function(exports){ 
  var obj = {
        'bill_deliver':[[
            {field: 'id', title: 'ID', width: 60, sort: true}, 

            {field:'deliver_num', title: '��������', width:260,
                templet: function(d){
                    var retInfo=d.deliver_num;
                    var arrayMap={"1":{"text":"ϵͳ�Ѵ�ӡ","color":""},"2":{"text":"�����Ѵ�ӡ","color":""},"3":{"text":"�ֿ��Ѵ�ӡ","color":"#FFB800"}};
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
            
            
            {field: 'order_num', title: '������',width:300},
            {field: 'invoice_nums', title: '��Ʊ��',width:300},
            {field: 'sales_num', title: '��������',width:200, sort: 'true'}, 
            {field: 'customer_short', title: '�ͻ����',width:200}, 
            {field: 'deliver_date', title: '����ʱ��',width:100}, 
            {field: 'sales_bill_date', title: '������������',width:100},
            {field: 'deliver_store', title: '�����ֿ�',width:100},
            
            {field: 'coin_type', title: '����',width:100},
            {field: 'expiry_date', title: '������',width:100},
            {field: 'create_user', title: '�Ƶ���',width:100},
            {field: 'create_time', title: '��������',width:150},
            {fixed: 'right', width: 400, align: 'center', toolbar: '#tab1Bar'}
        ]], 
        
        
        product_list:[[
            {type: 'checkbox', width:'', align:'center', toolbar: '#tab1Bar'},
            {type:'numbers', title: '�к�', width:50,hide:''},
            
            {field:'id', title: '��ϸID', width:"200",hide:true, sort: ""},
            
            {field:'order_num_show', title: '������', width:"200",hide:'', sort: ""},
            {field:'u8Code', title: '�������', width:115,hide:'',event:'editU8code'},
            
            {field:'more', title:'��������', width:85, templet: function(d){
                    return '<input type="checkbox" data-id="'+d.id+'" value="'+d.more+'" lay-skin="switch" lay-text="��|��" lay-filter="more" '+ (d.more == '��' ? 'checked' : '')+'>'; 
            }, unresize: true},
            
            {field:'warehouse_name', title: '�����ֿ�', width:150,hide:''},
            
            {field: 'chmc', title: '�������',width:150,sort: true},
            {field:'glueSty', title: '��ϵ', width:80,hide:''},
            {field:'norms', title: '����ͺ�',event:'', width:100,hide:true,edit:'',templet: function(d){
                return d.length+"M*"+d.width+"MM";
            }}, 
        
            {field:'deliver_num', title: '��������', width:80,hide:'',edit:'',totalRow: true },
            
            {field:'poUnit', title: '��λ',event:'', width:100,hide:true},
            {field:'sldw', title: '��λ',event:'', width:100,hide:true},
            {field:'proUnit', title: '��λ',event:'', width:60,hide:""},

            {field:'deliver_box', title: '��������',event:'', width:80,hide:'',totalRow: true},
            {field:'poUantity', title: '��ͬ����',event:'', width:80,hide:'',totalRow: true},
            {field:'proPrice', title: '��������',event:'', width:100,hide:''},
            {field:'poPrice1', title: '��ͬ����',event:'', width:100,hide:''},
            {field:'total_1', title: 'ԭ�ҽ��',event:'', width:100,edit:'text',hide:'',totalRow: true},
            {field:'apportion_price', title: '��̯�۸�',event:'', width:100,hide:'',totalRow: true},
            {field:'apportion_total', title: '��̯���ܼ�',event:'', width:100,hide:'',totalRow: true},
            
            
            {field:'fgweights', title: '����ë��',event:'', width:100,hide:'',totalRow: true},
            {field:'fnweights', title: '��������',event:'', width:100,hide:'',totalRow: true},
            
            
            {field:'xztm', title: '���Ӽ���������',event:'', width:300,hide:''},
            {field:'zpfs', title: 'ƽ����',event:'', width:300,hide:''},
            
            {field:'mz_1', title: 'ë��',event:'', width:100,hide:'',edit:'',totalRow: true}, 
            {field:'jz_1', title: '����',event:'', width:100,hide:'',edit:'',totalRow: true}, 
            {field:'tj_1', title: '�����',event:'', width:150,hide:'',edit:'',totalRow: true}, 

            {field:'dwtj', title: '��λ���',event:'', width:150,hide:'',edit:''},  
            {field:'dwmz', title: '��λë��',event:'', width:100,hide:'',edit:''}, 
            {field:'dwjz', title: '��λ����',event:'', width:100,hide:'',edit:''}, 
            {field:'dwxs', title: '��λϵ��',event:'', width:100,hide:'',edit:''}, 

            {field:'length', title: '��',event:'', width:60,hide:'',edit:''}, 
            {field:'width', title: '��',event:'', width:60,hide:'',edit:''}, 
            {field:'height', title: '��',event:'', width:60,hide:'',edit:''}, 
            {field:'formula', title: '��λ������㹫ʽ',event:'', width:300,hide:'',edit:''}, 
           

            {field:'complete', title: '��Ʒ/���Ʒ',event:'', width:150,hide:''},
            {field:'category', title: '��Ʒ����',event:'', width:210,hide:''},
            {field:'proColor', title: '��ɫ', width:150,hide:''},    
            {field:'name', title: '��Ʒ����',event:'', width:210,hide:''},
            {field:'cInvCode', title: '��Ʒ�ͺ�',event:'cInvCode', width:'200',hide:''},
            {field:'proUnit', title: '������λ',event:'', width:100,hide:''},
            {field:'poUantity', title: '��ͬ����',event:'', width:100,hide:''},
            {field:'boxNum', title: '��ͬ������', width:100,hide:'' },
           
            {field:'sfieldText', title: '��Ʒ����', width:90,hide:''},
            {field:'baoZhuangMS', title: '��װ��ʽ', width:500,hide:'', sort: ''},
            {field:'tdf1', title: 'ֽ������', width:200,hide:'', sort: ''},
            {field:'customerCode', title: '�ͻ�����', width:200,hide:''},
            {fixed: 'right', width:150, align:'center',hide:'', toolbar: '#tab2Bar'}
            
        ]],
    
    
        'product_list1': [[
            {type: 'checkbox', width:'', align:'center', toolbar: '#tab1Bar'},
            {field:'id', title: '���', width:50,hide:'true', sort: true, fixed: 'left'},
            {type:'numbers', title: '���', width:80,hide:''},
            {field:'order_num_show', title: '������', width:150,hide:'',},
            {field:'u8Code', title: '�������', width:115,hide:true,event:'editU8code'},
            {field:'cInvCode', title: '�ͺ�',event:'cInvCode',hide:true, width:'200'},
            {field:'deliver_num', title: '��������',event:'deliver_num', width:'100',hide:'',edit:'',totalRow: true},
            {field:'poUnit', title: '��λ',event:'', width:100,hide:''},
            {field:'deliver_box', title: '��������',event:'deliver_box', width:'100',hide:''},
            {field:'proColor', title: '��ɫ', width:150,hide:''},
            {field:'name', title: '��Ʒ����',event:'', width:210,hide:''},
            
            
            {field:'mz_1', title: 'ë��',event:'', width:100,hide:'',edit:'',totalRow: true}, 
            {field:'jz_1', title: '����',event:'', width:100,hide:'',edit:'',totalRow: true}, 
            {field:'tj_1', title: '�����',event:'', width:150,hide:'',edit:'',totalRow: true}, 
            
            {field:'dwtj', title: '��λ���',event:'', width:150,hide:'',edit:''},  
            {field:'dwmz', title: '��λë��',event:'', width:100,hide:'',edit:''}, 
            {field:'dwjz', title: '��λ����',event:'', width:100,hide:'',edit:''}, 
            {field:'dwxs', title: '��λϵ��',event:'', width:100,hide:'',edit:''}, 
            
            
            
            {field:'poUnit', title: '��λ',event:'', width:100,hide:''},
            {field:'poPrice', title: '����',event:'', width:200,hide:''},
            {field:'', title: '���Ӽ���������',event:'', width:300,hide:''},
            {field:'complete', title: '��Ʒ/���Ʒ',event:'', width:150,hide:''},
            {field:'category', title: '��Ʒ����',event:'', width:210,hide:''},
            {field:'proUnit', title: '������λ',event:'', width:100,hide:''},
            {field:'poUantity', title: '��ͬ����',event:'', width:100,hide:''},
            {field:'boxNum', title: '��ͬ������', width:100,hide:'' },
           
            {field:'sfieldText', title: '��Ʒ����', width:90,hide:''},
            {field:'baoZhuangMS', title: '��װ��ʽ', width:500,hide:'', sort: ''},
            {field:'tdf1', title: 'ֽ������', width:200,hide:'', sort: ''},
            {field:'customerCode', title: '�ͻ�����', width:200,hide:''},
            {fixed: 'right', width:150, align:'center',hide:'', toolbar: '#tab2Bar'}
        ]],
        
    
        'warehouse_select_list':[[
            {type: 'radio', width:'', align:'center', toolbar: '#tab1Bar'},
            {field: 'cWhCode', title: '�ֿ����',width:150},
            {field: 'cWhName', title: '�ֿ�����',width:300},
            {field: 'cWhPerson', title: '�ֿ⸺����',width:150}
        ]],
        
  };

  exports('deliver_cols', obj);
});   
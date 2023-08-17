layui.define(function(exports){ 
  var obj = {
        'coin_unit':{"��Ԫ":"usd","�����":"rmb","ŷԪ":"eur","�ձ�":"jpy"},
        'order_list':[[
            {type: 'checkbox', width:'', align:'center', toolbar: '#tab1Bar'},
            {field:'id', title: '����', width:80,hide:'true', sort: true, fixed: 'left'},
            {field:'formId', title: '��������', width:90,hide:'true', sort: true},
            {field:'runId', title: '��ˮ��', width:90,hide:'true', sort: true},
            {field:'prcsId', title: 'prcsId', width:90,hide:'true', sort: true},
            {field:'orderNum', title: '�������', width:156,hide:'', sort: true},
            {field:'ddLeiXingText', title: '��������', width:100,hide:'', sort: true},
            {field:'zhangTaoText', title: '����', width:80,hide:'', sort: true},
            {field:'currency', title: '����', width:'',hide:'', sort: true},
            {field:'cCCName', title: '����', width:'',hide:'', sort: true},
            {field:'cCusAbbName', title: '�ͻ����', width:'',hide:'', sort: true},
            {field:'cCusName', title: '�ͻ�����', width:'',hide:'', sort: true},
            {field:'date1', title: 'Ŀ�꽻��', width:100,hide:'', sort: true},
            {field:'cCusPPersonOAText', title: 'ҵ��Ա', width:90,hide:'', sort: true},
            {field:'atTime', title: '�ʹ�ʱ��', width:160,hide:'', sort: true },
            {fixed: 'right', width:80, align:'center',hide:'', toolbar: '#tab1Bar'}
            
        ]],
        'product_list': [[
            {type: 'checkbox', width:'', align:'center', toolbar: '#tab1Bar'},
            {field:'id', title: '���', width:50,hide:'true', sort: true, fixed: 'left'},
            
            
            {type:'numbers', title: '���', width:80,hide:'', sort: true},
            {field:'order_num_show', title: '������', width:200,hide:'',},
            {field:'u8Code', title: '�������', width:115,hide:'', sort: true,event:'editU8code'},
            {field:'cInvCode', title: '�ͺ�',event:'cInvCode', width:'200',hide:'', sort: true},
            {field:'customerCode', title: '�ͻ�����', width:92,hide:'', sort: true},
            {field:'proColor', title: '��ɫ', width:150,hide:'', sort: true},
            {field:'name', title: '��Ʒ����',event:'', width:210,hide:'', sort: true},
            {field:'o_width', title: '��MM', width:70,hide:'', sort: true},
            {field:'o_length', title: '��M', width:70,hide:'', sort: true},
            {field:'glueSty', title: '��ϵ', width:80,hide:'', sort: true},
            {field:'sfieldText', title: '��Ʒ����', width:90,hide:'', sort: true},
            {field:'inum', title: '��װ����', width:78,hide:'', sort: true},
            {field:'onum', title: '��װ����', width:78,hide:'', sort: true},
            {field:'proUantity', title: '��������', width:90,hide:'', sort: true},
          
            {field:'proUnit', title: '������λ', width:78,hide:'', sort: true },
            {field:'boxNum', title: '����', width:60,hide:'', sort: true },
            {field:'baoZhuangMS', title: '��װ��ʽ', width:500,hide:'', sort: ''},
            {field:'tintd', title: '�ھ�', width:60,hide:'', sort: ''},
            {field:'tthick', title: '�ܺ�', width:60,hide:'', sort: ''},                
            {field:'tdf1', title: 'ֽ������', width:74,hide:'', sort: ''},
            {field:'tdesc', title: 'ֽ������Ҫ��', width:60,hide:'', sort: ''},
            {field:'icontent', title: '��������', width:75,hide:'', sort: ''},
            {field:'icolor', title: '������ɫ', width:75,hide:'', sort: ''},
            {field:'itm', title: '����������', width:130,hide:'', sort: ''},
            {field:'ocontent', title: '��������', width:74,hide:'', sort: ''},
            {field:'ocolor', title: '������ɫ', width:74,hide:'', sort: ''},
            {field:'otm', title: '����������', width:130,hide:'', sort: ''},
            {field:'orderInfo', title: '����', width:'',hide:'', sort: '', hide:true},
            {field:'', title: '�����Ҫ��', width:88,hide:'', sort: ''},
            
            {field:'barCode', title: '������', width:130,hide:'', sort: true},
            {field:'tuoPan', title: '����', width:110,hide:'', sort: ''},
            {field:'tpdesc', title: '��������Ҫ��',width:105,hide:'', sort: ''},
            {field:'sfieldText', title: '��Ʒ����',width:90,hide:'true', sort: true},
            {field:'desc', title: '��ע',width:190,hide:'', sort: true},
            {field:'listpo',fixed: 'right',title: '�ͻ���ƷPO��', width:200, align:'center'}
            
        ]],
        'order_product_list':function (colsType){
            var retArray=[];
            var tempArray=[];
            var allCols=[
                {type:'numbers', title: '���', width:80,hide:''},
                {field:'order_num_show', title: '������', width:"200",hide:'', sort: ""},
                {field:'hsCode', title: 'hs����', width:"100",hide:'', sort: ""},
                {field:'u8Code', title: '�������',event:'', width:100,hide:'',edit:''}, 
                {field:'hsCodeName', title: 'Ʒ��', width:"150",hide:'', sort: "", totalRowText: '�ϼƣ�'},
                {field:'is_apportion', title:'�Ƿ��̯', width:85, templet: function(d){
                    return '<input type="checkbox" data-id="'+d.id+'" value="'+d.is_apportion+'" lay-skin="switch" lay-text="��|��" lay-filter="is_apportion" '+ (d.is_apportion == 1 ? 'checked' : '')+'>'; 
                }, unresize: true},
                {field:'total_1', title: '�ܼ�',event:'', width:100,hide:'',totalRow: true},
  
                {field:'apportion_price', title: '��̯�۸�',event:'', width:150,hide:'',sort: ""},
                {field:'apportion_total', title: '��̯���ܼ۸�',event:'', width:150,hide:'',sort: "",totalRow: true },
       
                {field:'manua', title: '�ֲ�',event:'', width:80,hide:'',edit:'text'},
                {field:'manua_num', title: '�ֲ�����',event:'', width:100,hide:'',edit:'text'}, 
                {field:'num', title: '��������',event:'', width:100,hide:'',edit:'text'}, 
                {field:'poUantity', title: '������',event:'', width:100,hide:'',edit:''}, 
                {field:'box_num', title: '��������', width:100,hide:'', edit:'text',totalRow: true }, 
                {field:'poPrice', title: '����',event:'', width:100,hide:'',edit:'text',totalRow: true},
                {field:'tuoPan', title: '����', width:110,hide:''},
                {field:'tuopan_num', title: '��������',event:'', width:100,hide:'',edit:'text',totalRow: true}, 
                {field:'tuopan_weight', title: '��������',event:'', width:100,hide:'',edit:'text',totalRow: true}, 
                {field:'tuopan_size', title: '���̳ߴ�',event:'', width:100,hide:'',edit:'text',totalRow: true}, 
                {field:'tuopan_display', title: '���̰ڷ�',event:'', width:100,hide:'',edit:'text',totalRow: true},
                {field:'tuopan_group', title: '���̷���',event:'', width:100,hide:'',sort: true,totalRow: true}, 

                {field:'jz_1', title: '����',event:'', width:100,hide:'',edit:'',totalRow: true}, 
                {field:'mz_1', title: 'ë��',event:'', width:100,hide:'',edit:'',totalRow: true}, 
                {field:'tj_1', title: '�����',event:'', width:150,hide:'',edit:'',totalRow: true}, 
                {field:'zs_zjz', title: '��ʵ����',event:'', width:100,hide:'',edit:'',totalRow: true}, 
                {field:'zs_zmz', title: '��ʵë��',event:'', width:100,hide:'',edit:'',totalRow: true}, 
                
                {field:'c_dwjz', title: '�ͻ�Ҫ��λ����',event:'', width:150,hide:''}, 
                {field:'c_dwmz', title: '�ͻ�Ҫ��λë��',event:'', width:150,hide:''}, 
                {field:'c_dwtj', title: '�ͻ�Ҫ��λ���',event:'', width:150,hide:''}, 
                
                {field:'dwtj', title: '��λ���',event:'', width:150,hide:'',edit:''},  
                {field:'dwmz', title: '��λë��',event:'', width:100,hide:'',edit:''}, 
                {field:'dwjz', title: '��λ����',event:'', width:100,hide:'',edit:''}, 
                {field:'dwxs', title: '��λϵ��',event:'', width:100,hide:'',edit:''}, 
                
                {field:'u8Code', title: '�������',event:'', width:100,hide:'',edit:''}, 

                {field:'o_length', title: '��',event:'', width:60,hide:'',edit:''}, 
                {field:'o_width', title: '��',event:'', width:60,hide:'',edit:''}, 
                {field:'height', title: '��',event:'', width:60,hide:'',edit:''}, 
                {field:'formula', title: '��λ������㹫ʽ',event:'', width:300,hide:'',edit:''}, 
                {field:'u8Code', title: '�������', width:115,hide:'',event:'editU8code'},
                {field:'org_num', title: '��ͬ����',event:'', width:100,hide:''},
                {field:'proUantity', title: '��������', width:90,hide:''},
                {field:'onum', title: '��װ����', width:100,hide:''},
                {field:'proUnit', title: '������λ', width:100,hide:'' },
                {field:'complete', title: '��Ʒ/���Ʒ',event:'', width:150,hide:''},
                {field:'category', title: '��Ʒ����',event:'', width:210,hide:''},
                {field:'name', title: '��Ʒ����',event:'', width:210,hide:''},
                {field:'cInvCode', title: '��Ʒ�ͺ�',event:'cInvCode', width:'200',hide:''},
                {field:'poUnit', title: '��ͬ��λ',event:'', width:100,hide:''},

               
                {field:'inum', title: '��װ����', width:100,hide:''},
                
                {field:'boxNum', title: '��ͬ������', width:100,hide:'' },
                {field:'proColor', title: '��ɫ', width:150,hide:''},
                {field:'width', title: '��MM', width:70,hide:''},
                {field:'length', title: '��M', width:70,hide:''},
                {field:'glueSty', title: '��ϵ', width:80,hide:''},
                {field:'sfieldText', title: '��Ʒ����', width:90,hide:''},
                {field:'baoZhuangMS', title: '��װ��ʽ', width:500,hide:'', sort: ''},
                {field:'tintd', title: '�ھ�', width:60,hide:'', sort: ''},
                {field:'tthick', title: '�ܺ�', width:60,hide:'', sort: ''},                
                {field:'tdf1', title: 'ֽ������', width:74,hide:'', sort: ''},
                {field:'tdesc', title: 'ֽ������Ҫ��', width:60,hide:'', sort: ''},
                {field:'icontent', title: '��������', width:75,hide:'', sort: ''},
                {field:'icolor', title: '������ɫ', width:75,hide:'', sort: ''},
                {field:'itm', title: '����������', width:130,hide:'', sort: ''},
                {field:'ocontent', title: '��������', width:74,hide:'', sort: ''},
                {field:'ocolor', title: '������ɫ', width:74,hide:'', sort: ''},
                {field:'otm', title: '����������', width:130,hide:'', sort: ''},
                // {field:'', title: '����', width:'',hide:'', sort: ''},
                {field:'', title: '�����Ҫ��', width:88,hide:'', sort: ''},
                {field:'customerCode', title: '�ͻ�����', width:92,hide:''},
                {field:'barCode', title: '������', width:130,hide:''},
                {field:'tuoPan', title: '����', width:110,hide:'', sort: ''},
                {field:'tpdesc', title: '��������Ҫ��',width:105,hide:'', sort: ''},
                {field:'sfieldText', title: '��Ʒ����',width:90,hide:'true'},
                {field:'desc', title: '��ע',width:190,hide:''},
               
            ];

            switch(colsType){
                case 1: 
                    
                    break;
                case 2: 
                    return this.product_list_cabinet;
                    break;
                case 3: 
                    //allCols.splice(6, 4);//ɾ������ֵΪ8�ĺ�������Ԫ�� 
                    //allCols.splice(4, 1);//ɾ������ֵΪ8�ĺ�������Ԫ��
                    
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
                {type:'numbers', title: '���', width:80,hide:''},
                {field:'order_num_show', title: '������', width:"200",hide:'', sort: ""},
                {field:'u8Code', title: '�������',event:'', width:100,hide:'',edit:''}, 
                {field:'name', title: '��Ʒ����',event:'', width:100,hide:''},
                {field:'customerCode', title: '�ͻ�����', width:92,hide:''},
                {field:'num', title: '��������',event:'', width:100,hide:'',edit:'text'}, 
                {field:'box_num', title: '��������', width:100,hide:'', edit:'text',totalRow: true }, 
                {field:'proColor', title: '��Ʒ��ɫ', width:92,hide:''},
                {field:'o_width', title: '��MM', width:70,hide:''},
                {field:'o_length', title: '��M', width:70,hide:''},
                {field:'norms', title: '��Ʒ���',event:'', width:100,hide:true,edit:'',templet: function(d){
                    return d.length+"M*"+d.width+"MM";
                }}, 
  
                {field:'poUantity', title: '��ͬ����',event:'', width:100,hide:''},
                {field:'boxNum', title: '��ͬ����', width:100,hide:'' },
                {field:'proUantity', title: '��������', width:90,hide:''},
                {field:'boxNum', title: '��������', width:100,hide:''},
                {field:'proUnit', title: '������λ', width:100,hide:'' },
                
                {field:'sample_num1', title: '��Ʒ����', width:110,hide:''},
                {field:'sample_num2', title: '��Ʒ����(���)', width:110,hide:true},


                {field:'poPrice', title: '����',event:'', width:100,hide:'',totalRow: true},
                
                {field:'total_1', title: '�ܼ�',event:'', width:100,hide:'',totalRow: true},
                
                {field:'apportion_price', title: '��̯�۸�',event:'', width:150,hide:'',sort: ""},
                {field:'apportion_total', title: '��̯���ܼ۸�',event:'', width:150,hide:'',sort: "",totalRow: true },
                
                {field:'jz_1', title: '����',event:'', width:100,hide:'',edit:'',totalRow: true}, 
                {field:'mz_1', title: 'ë��',event:'', width:100,hide:'',edit:'',totalRow: true}, 
                {field:'tj_1', title: '�����',event:'', width:150,hide:'',edit:'',totalRow: true}, 
                
                
                {field:'c_dwjz', title: '�ͻ�Ҫ��λ����',event:'', width:150,hide:''}, 
                {field:'c_dwmz', title: '�ͻ�Ҫ��λë��',event:'', width:150,hide:''}, 
                {field:'c_dwtj', title: '�ͻ�Ҫ��λ���',event:'', width:150,hide:''},  

                {field:'dwjz', title: '��λ����',event:'', width:100,hide:'',edit:''}, 
                {field:'dwmz', title: '��λë��',event:'', width:100,hide:'',edit:''}, 
                {field:'dwtj', title: '��λ���',event:'', width:150,hide:'',edit:''},  
                
                
                {field:'dwxs', title: '��λϵ��',event:'', width:100,hide:'',edit:''}, 
                {field:'u8Code', title: '�������',event:'', width:100,hide:'',edit:''}, 
                {field:'inum', title: '��װ����', width:100,hide:''},
            
                {field:'icontent', title: '��������', width:75,hide:'', sort: ''},
                {field:'icolor', title: '������ɫ', width:75,hide:'', sort: ''},
                {field:'itm', title: '����������', width:130,hide:'', sort: ''},
                {field:'ocontent', title: '��������', width:74,hide:'', sort: ''},
                {field:'ocolor', title: '������ɫ', width:74,hide:'', sort: ''},
                {field:'otm', title: '����������', width:130,hide:'', sort: ''},
                // {field:'', title: '����', width:'',hide:'', sort: ''},
                {field:'', title: '�����Ҫ��', width:88,hide:'', sort: ''},
                {field:'tuoPan', title: '����', width:110,hide:''},
                {field:'tuopan_num', title: '��������',event:'', width:100,hide:'',edit:'text',totalRow: true}, 
                {field:'tuopan_weight', title: '��������',event:'', width:100,hide:'',edit:'text',totalRow: true}, 
                {field:'tuopan_size', title: '���̳ߴ�',event:'', width:100,hide:'',edit:'text',totalRow: true}, 
                {field:'tuopan_display', title: '���̰ڷ�',event:'', width:100,hide:'',edit:'text',totalRow: true},
                {field:'tuopan_group', title: '���̷���',event:'', width:100,hide:'',sort: true,totalRow: true}, 
                {field:'tpdesc', title: '��������Ҫ��',width:105,hide:'', sort: ''},
            
         
                {field:'length', title: '��',event:'', width:60,hide:'',edit:''}, 
                {field:'width', title: '��',event:'', width:60,hide:'',edit:''}, 
                {field:'height', title: '��',event:'', width:60,hide:'',edit:''}, 
                {field:'formula', title: '��λ������㹫ʽ',event:'', width:300,hide:'',edit:''}, 
                {field:'u8Code', title: '�������', width:115,hide:'',event:'editU8code'},
                
                
                {field:'complete', title: '��Ʒ/���Ʒ',event:'', width:150,hide:''},
                {field:'category', title: '��Ʒ����',event:'', width:210,hide:''},
               
                {field:'cInvCode', title: '��Ʒ�ͺ�',event:'cInvCode', width:'200',hide:''},
                {field:'poUnit', title: '��ͬ��λ',event:'', width:100,hide:''},

               
                {field:'inum', title: '��װ����', width:100,hide:''},
                {field:'onum', title: '��װ����', width:100,hide:''},
               
                {field:'proColor', title: '��ɫ', width:150,hide:''},
               
                {field:'glueSty', title: '��ϵ', width:80,hide:''},
                {field:'sfieldText', title: '��Ʒ����', width:90,hide:''},
                {field:'baoZhuangMS', title: '��װ��ʽ', width:500,hide:'', sort: ''},
                {field:'tintd', title: '�ھ�', width:60,hide:'', sort: ''},
                {field:'tthick', title: '�ܺ�', width:60,hide:'', sort: ''},                
                {field:'tdf1', title: 'ֽ������', width:74,hide:'', sort: ''},
                {field:'tdesc', title: 'ֽ������Ҫ��', width:60,hide:'', sort: ''},
                
                
                {field:'barCode', title: '������', width:130,hide:''},
             
                {field:'sfieldText', title: '��Ʒ����',width:90,hide:'true'},
                {field:'desc', title: '��ע',width:190,hide:''},
                {field:'listpo', title: '��ƷPO��', width:150,hide:''},
          ]]  
        },
        
        
        manua_product_list:[[
                
                {type: 'checkbox', width:'', align:'center', toolbar: '#tab1Bar'},
                {type:'numbers', title: '���', width:80,hide:''},
                {field:'order_num_show', title: '������', width:"200",hide:'', sort: ""},
                {field:'hsCode', title: 'hs����', width:"100",hide:'', sort: ""},
                {field:'u8Code', title: '�������',event:'', width:100,hide:'',edit:''}, 
                {field:'hsCodeName', title: 'Ʒ��', width:"150",hide:'', sort: "", totalRowText: '�ϼƣ�'},
                {field:'total_1', title: '�ܼ�',event:'', width:100,hide:'',totalRow: true},
                {field:'apportion_price', title: '��̯�۸�',event:'', width:150,hide:'',sort: ""},
                {field:'apportion_total', title: '��̯���ܼ۸�',event:'', width:150,hide:'',sort: "",totalRow: true},
                {field:'gd_number', title: '�ص���', width:155,hide:'',edit:'text'},
                {field:'manua', title: '�ֲ�',event:'', width:80,hide:'',edit:'text'},
                {field:'manua_num', title: '�ֲ�����',event:'', width:100,hide:'',edit:'text'},
                {field:'num', title: '��������',event:'', width:100,hide:'',totalRow: true}, 
                {field:'poUantity', title: '������',event:'', width:100,hide:'',edit:''}, 
                {field:'box_num', title: '��������', width:100,hide:'',totalRow: true }, 
 
                {field:'tuoPan', title: '����', width:110,hide:''},
                {field:'tuopan_num', title: '��������',event:'', width:100,hide:'',totalRow: true}, 
                {field:'tuopan_weight', title: '��������',event:'', width:100,hide:'',totalRow: true}, 
                {field:'tuopan_size', title: '���̳ߴ�',event:'', width:100,hide:'',totalRow: true}, 
                {field:'tuopan_display', title: '���̰ڷ�',event:'', width:100,hide:'',totalRow: true},
                {field:'tuopan_group', title: '���̷���',event:'', width:100,hide:'',sort: true,totalRow: true}, 

                {field:'jz_1', title: '����',event:'', width:100,hide:'',edit:'',totalRow: true}, 
                {field:'mz_1', title: 'ë��',event:'', width:100,hide:'',edit:'',totalRow: true}, 
                {field:'tj_1', title: '�����',event:'', width:150,hide:'',edit:'',totalRow: true}, 
                
                
                {field:'c_dwjz', title: '�ͻ�Ҫ��λ����',event:'', width:150,hide:''}, 
                {field:'c_dwmz', title: '�ͻ�Ҫ��λë��',event:'', width:150,hide:''}, 
                {field:'c_dwtj', title: '�ͻ�Ҫ��λ���',event:'', width:150,hide:''}, 
                
                {field:'dwtj', title: '��λ���',event:'', width:150,hide:'',edit:''},  
                {field:'dwmz', title: '��λë��',event:'', width:100,hide:'',edit:''}, 
                {field:'dwjz', title: '��λ����',event:'', width:100,hide:'',edit:''}, 
                {field:'dwxs', title: '��λϵ��',event:'', width:100,hide:'',edit:''}, 
                
                {field:'u8Code', title: '�������',event:'', width:100,hide:'',edit:''}, 

                {field:'o_length', title: '��',event:'', width:60,hide:'',edit:''}, 
                {field:'o_width', title: '��',event:'', width:60,hide:'',edit:''}, 
                {field:'height', title: '��',event:'', width:60,hide:'',edit:''}, 
                {field:'formula', title: '��λ������㹫ʽ',event:'', width:300,hide:'',edit:''}, 
                {field:'u8Code', title: '�������', width:115,hide:'',event:'editU8code'},
                {field:'org_num', title: '��ͬ����',event:'', width:100,hide:''},
                {field:'proUantity', title: '��������', width:90,hide:''},
                {field:'onum', title: '��װ����', width:100,hide:''},
                {field:'proUnit', title: '������λ', width:100,hide:'' },
                {field:'complete', title: '��Ʒ/���Ʒ',event:'', width:150,hide:''},
                {field:'category', title: '��Ʒ����',event:'', width:210,hide:''},
                {field:'name', title: '��Ʒ����',event:'', width:210,hide:''},
                {field:'cInvCode', title: '��Ʒ�ͺ�',event:'cInvCode', width:'200',hide:''},
                {field:'poUnit', title: '��ͬ��λ',event:'', width:100,hide:''},

               
                {field:'inum', title: '��װ����', width:100,hide:''},
                
                {field:'boxNum', title: '��ͬ������', width:100,hide:'' },
                {field:'proColor', title: '��ɫ', width:150,hide:''},
                {field:'width', title: '��MM', width:70,hide:''},
                {field:'length', title: '��M', width:70,hide:''},
                {field:'glueSty', title: '��ϵ', width:80,hide:''},
                {field:'sfieldText', title: '��Ʒ����', width:90,hide:''},
                {field:'baoZhuangMS', title: '��װ��ʽ', width:500,hide:'', sort: ''},
                {field:'tintd', title: '�ھ�', width:60,hide:'', sort: ''},
                {field:'tthick', title: '�ܺ�', width:60,hide:'', sort: ''},                
                {field:'tdf1', title: 'ֽ������', width:74,hide:'', sort: ''},
                {field:'tdesc', title: 'ֽ������Ҫ��', width:60,hide:'', sort: ''},
                {field:'icontent', title: '��������', width:75,hide:'', sort: ''},
                {field:'icolor', title: '������ɫ', width:75,hide:'', sort: ''},
                {field:'itm', title: '����������', width:130,hide:'', sort: ''},
                {field:'ocontent', title: '��������', width:74,hide:'', sort: ''},
                {field:'ocolor', title: '������ɫ', width:74,hide:'', sort: ''},
                {field:'otm', title: '����������', width:130,hide:'', sort: ''},
                // {field:'', title: '����', width:'',hide:'', sort: ''},
                {field:'', title: '�����Ҫ��', width:88,hide:'', sort: ''},
                {field:'customerCode', title: '�ͻ�����', width:92,hide:''},
                {field:'barCode', title: '������', width:130,hide:''},
                {field:'tuoPan', title: '����', width:110,hide:'', sort: ''},
                {field:'tpdesc', title: '��������Ҫ��',width:105,hide:'', sort: ''},
                {field:'sfieldText', title: '��Ʒ����',width:90,hide:'true'},
                {field:'desc', title: '��ע',width:190,hide:''},
               
            ]],
        
        sale_product_list:[[
                {type:'numbers', title: '���', width:80,hide:'', fixed: 'left'},
                {field:'order_num_show', title: '������', width:"150",hide:'', sort: "", fixed: 'left'},
                {field:'u8Code', title: '�������',event:'', width:100,hide:'',edit:'', fixed: 'left'}, 
                {field:'name', title: '��Ʒ����',event:'', width:100,hide:'', fixed: 'left'},
                {field:'customerCode', title: '�ͻ�����', width:92,hide:'', fixed: 'left'},
                {field:'proColor', title: '��Ʒ��ɫ', width:92,hide:'', fixed: 'left'},
                {field:'o_width', title: '��MM', width:70,hide:'', fixed: 'left'},
                {field:'o_length', title: '��M', width:70,hide:'', fixed: 'left'},
                {field:'norms', title: '��Ʒ���',event:'', width:100,hide:true,edit:'',templet: function(d){
                    return d.length+"M*"+d.width+"MM";
                }}, 
                {field:'poUantity', title: '��ͬ����',event:'', width:100,hide:''},
                {field:'boxNum', title: '��ͬ����', width:100,hide:'' },
                {field:'proUantity', title: '��������', width:90,hide:''},
                {field:'boxNum', title: '��������', width:100,hide:''},
                {field:'proUnit', title: '������λ', width:100,hide:'' },
                
                {field:'sample_num1', title: '��Ʒ����', width:110,edit:'text',hide:''},
                {field:'sample_num2', title: '��Ʒ����(���)', width:110,edit:'text',hide:true},
                {field:'num', title: '��������',event:'', width:100,hide:'',edit:'text'}, 
                {field:'box_num', title: '��������', width:100,hide:'', edit:'text',totalRow: true }, 
                {field:'poPrice', title: '����',event:'', width:100,hide:'',edit:'text',totalRow: true},
                {field:'rmbPrice', title: '����(RMB)',event:'', width:100,hide:'',totalRow: true},
                {field:'poPrice2', title: '���ﵥ��',event:'', width:100,hide:'',totalRow: true},
                
                {field:'total_1', title: '�ܼ�',event:'', width:100,hide:'',totalRow: true},
                {field:'rmbTotal1', title: '�ܼ�(RMB)',event:'', width:100,hide:'',totalRow: true},
                
                {field:'apportion_price', title: '��̯�۸�',event:'', width:150,hide:'',sort: ""},
                {field:'apportion_total', title: '��̯���ܼ۸�',event:'', width:150,hide:'',sort: "",totalRow: true },
                
                {field:'jz_1', title: '����',event:'', width:100,hide:'',edit:'text',totalRow: true}, 
                {field:'mz_1', title: 'ë��',event:'', width:100,hide:'',edit:'text',totalRow: true}, 
                {field:'tj_1', title: '�����',event:'', width:150,hide:'',edit:'text',totalRow: true}, 
                {field:'zs_zjz', title: '��ʵ����',event:'', width:100,hide:'',edit:'',totalRow: true}, 
                {field:'zs_zmz', title: '��ʵë��',event:'', width:100,hide:'',edit:'',totalRow: true}, 
                
                {field:'c_dwjz', title: '�ͻ�Ҫ��λ����',event:'', width:150,hide:''}, 
                {field:'c_dwmz', title: '�ͻ�Ҫ��λë��',event:'', width:150,hide:''}, 
                {field:'c_dwtj', title: '�ͻ�Ҫ��λ���',event:'', width:150,hide:''},  

                {field:'dwjz', title: '��λ����',event:'', width:100,hide:'',edit:''}, 
                {field:'dwmz', title: '��λë��',event:'', width:100,hide:'',edit:''}, 
                {field:'dwtj', title: '��λ���',event:'', width:150,hide:'',edit:''},  
                
                
                {field:'dwxs', title: '��λϵ��',event:'', width:100,hide:'',edit:''}, 
                {field:'u8Code', title: '�������',event:'', width:100,hide:'',edit:''}, 
                {field:'inum', title: '��װ����', width:100,hide:''},
            
                {field:'icontent', title: '��������', width:75,hide:'', sort: ''},
                {field:'icolor', title: '������ɫ', width:75,hide:'', sort: ''},
                {field:'itm', title: '����������', width:130,hide:'', sort: ''},
                {field:'ocontent', title: '��������', width:74,hide:'', sort: ''},
                {field:'ocolor', title: '������ɫ', width:74,hide:'', sort: ''},
                {field:'otm', title: '����������', width:130,hide:'', sort: ''},
                // {field:'', title: '����', width:'',hide:'', sort: ''},
                {field:'', title: '�����Ҫ��', width:88,hide:'', sort: ''},
                {field:'tuoPan', title: '����', width:110,hide:''},
                {field:'tuopan_num', title: '��������',event:'', width:100,hide:'',edit:'text',totalRow: true}, 
                {field:'tuopan_weight', title: '��������',event:'', width:100,hide:'',edit:'text',totalRow: true}, 
                {field:'tuopan_size', title: '���̳ߴ�',event:'', width:100,hide:'',edit:'text',totalRow: true}, 
                {field:'tuopan_display', title: '���̰ڷ�',event:'', width:100,hide:'',edit:'text',totalRow: true},
                {field:'tuopan_group', title: '���̷���',event:'', width:100,hide:'',sort: true,totalRow: true}, 
                {field:'tpdesc', title: '��������Ҫ��',width:105,hide:'', sort: ''},
            
         
                {field:'length', title: '��',event:'', width:60,hide:'',edit:''}, 
                {field:'width', title: '��',event:'', width:60,hide:'',edit:''}, 
                {field:'height', title: '��',event:'', width:60,hide:'',edit:''}, 
                {field:'formula', title: '��λ������㹫ʽ',event:'', width:300,hide:'',edit:''}, 
                {field:'u8Code', title: '�������', width:115,hide:'',event:'editU8code'},
                
                
                {field:'complete', title: '��Ʒ/���Ʒ',event:'', width:150,hide:''},
                {field:'category', title: '��Ʒ����',event:'', width:210,hide:''},
               
                {field:'cInvCode', title: '��Ʒ�ͺ�',event:'cInvCode', width:'200',hide:''},
                {field:'poUnit', title: '��ͬ��λ',event:'', width:100,hide:''},

               
                {field:'inum', title: '��װ����', width:100,hide:''},
                {field:'onum', title: '��װ����', width:100,hide:''},
               
                {field:'proColor', title: '��ɫ', width:150,hide:''},
               
                {field:'glueSty', title: '��ϵ', width:80,hide:''},
                {field:'sfieldText', title: '��Ʒ����', width:90,hide:''},
                {field:'baoZhuangMS', title: '��װ��ʽ', width:500,hide:'', sort: ''},
                {field:'tintd', title: '�ھ�', width:60,hide:'', sort: ''},
                {field:'tthick', title: '�ܺ�', width:60,hide:'', sort: ''},                
                {field:'tdf1', title: 'ֽ������', width:74,hide:'', sort: ''},
                {field:'tdesc', title: 'ֽ������Ҫ��', width:60,hide:'', sort: ''},
                
                
                {field:'barCode', title: '������', width:130,hide:''},
             
                {field:'sfieldText', title: '��Ʒ����',width:90,hide:'true'},
                {field:'desc', title: '��ע',width:190,hide:''},
            {field:'listpo', title: '��ƷPO��', width:92,hide:'', fixed: 'left'},
        ]],
        sale_product_list2:[[
            {type:'numbers', title: '���', width:80,hide:'', fixed: 'left'},
            {field:'order_num_show', title: '������', width:"150",hide:'', sort: "", fixed: 'left'},
            {field:'u8Code', title: '�������',event:'', width:100,hide:'',edit:'', fixed: 'left'}, 
            {field:'name', title: '��Ʒ����',event:'', width:100,hide:'', fixed: 'left'},
            {field:'customerCode', title: '�ͻ�����', width:92,hide:'', fixed: 'left'},
            {field:'proColor', title: '��Ʒ��ɫ', width:92,hide:'', fixed: 'left'},
            {field:'o_width', title: '��MM', width:70,hide:'', fixed: 'left'},
            {field:'o_length', title: '��M', width:70,hide:'', fixed: 'left'},
            {field:'norms', title: '��Ʒ���',event:'', width:100,hide:true,edit:'',templet: function(d){
                return d.length+"M*"+d.width+"MM";
            }}, 
            {field:'poUantity', title: '��ͬ����',event:'', width:100,hide:''},
            {field:'boxNum', title: '��ͬ����', width:100,hide:'' },
            {field:'proUantity', title: '��������', width:90,hide:''},
            {field:'boxNum', title: '��������', width:100,hide:''},
            {field:'proUnit', title: '������λ', width:100,hide:'' },
            
            {field:'sample_num1', title: '��Ʒ����', width:110,edit:'text',hide:''},
            {field:'sample_num2', title: '��Ʒ����(���)', width:110,edit:'text',hide:true},
            {field:'num', title: '��������',event:'', width:100,hide:'',edit:'text'}, 
            {field:'box_num', title: '��������', width:100,hide:'', edit:'text',totalRow: true }, 
            {field:'poPrice', title: '����',event:'', width:100,hide:'',edit:'text',totalRow: true},
            {field:'poPrice2', title: '���ﵥ��',event:'', width:100,hide:'',totalRow: true},
            
            {field:'total_1', title: '�ܼ�',event:'', width:100,hide:'',totalRow: true},
            
            {field:'apportion_price', title: '��̯�۸�',event:'', width:150,hide:'',sort: ""},
            {field:'apportion_total', title: '��̯���ܼ۸�',event:'', width:150,hide:'',sort: "",totalRow: true },
            
            {field:'jz_1', title: '����',event:'', width:100,hide:'',edit:'',totalRow: true}, 
            {field:'mz_1', title: 'ë��',event:'', width:100,hide:'',edit:'',totalRow: true}, 
            {field:'tj_1', title: '�����',event:'', width:150,hide:'',edit:'',totalRow: true}, 
            
            
            {field:'c_dwjz', title: '�ͻ�Ҫ��λ����',event:'', width:150,hide:''}, 
            {field:'c_dwmz', title: '�ͻ�Ҫ��λë��',event:'', width:150,hide:''}, 
            {field:'c_dwtj', title: '�ͻ�Ҫ��λ���',event:'', width:150,hide:''},  

            {field:'dwjz', title: '��λ����',event:'', width:100,hide:'',edit:''}, 
            {field:'dwmz', title: '��λë��',event:'', width:100,hide:'',edit:''}, 
            {field:'dwtj', title: '��λ���',event:'', width:150,hide:'',edit:''},  
            
            
            {field:'dwxs', title: '��λϵ��',event:'', width:100,hide:'',edit:''}, 
            {field:'u8Code', title: '�������',event:'', width:100,hide:'',edit:''}, 
            {field:'inum', title: '��װ����', width:100,hide:''},
        
            {field:'icontent', title: '��������', width:75,hide:'', sort: ''},
            {field:'icolor', title: '������ɫ', width:75,hide:'', sort: ''},
            {field:'itm', title: '����������', width:130,hide:'', sort: ''},
            {field:'ocontent', title: '��������', width:74,hide:'', sort: ''},
            {field:'ocolor', title: '������ɫ', width:74,hide:'', sort: ''},
            {field:'otm', title: '����������', width:130,hide:'', sort: ''},
            // {field:'', title: '����', width:'',hide:'', sort: ''},
            {field:'', title: '�����Ҫ��', width:88,hide:'', sort: ''},
            {field:'tuoPan', title: '����', width:110,hide:''},
            {field:'tuopan_num', title: '��������',event:'', width:100,hide:'',edit:'text',totalRow: true}, 
            {field:'tuopan_weight', title: '��������',event:'', width:100,hide:'',edit:'text',totalRow: true}, 
            {field:'tuopan_size', title: '���̳ߴ�',event:'', width:100,hide:'',edit:'text',totalRow: true}, 
            {field:'tuopan_display', title: '���̰ڷ�',event:'', width:100,hide:'',edit:'text',totalRow: true},
            {field:'tuopan_group', title: '���̷���',event:'', width:100,hide:'',sort: true,totalRow: true}, 
            {field:'tpdesc', title: '��������Ҫ��',width:105,hide:'', sort: ''},
        
     
            {field:'length', title: '��',event:'', width:60,hide:'',edit:''}, 
            {field:'width', title: '��',event:'', width:60,hide:'',edit:''}, 
            {field:'height', title: '��',event:'', width:60,hide:'',edit:''}, 
            {field:'formula', title: '��λ������㹫ʽ',event:'', width:300,hide:'',edit:''}, 
            {field:'u8Code', title: '�������', width:115,hide:'',event:'editU8code'},
            
            
            {field:'complete', title: '��Ʒ/���Ʒ',event:'', width:150,hide:''},
            {field:'category', title: '��Ʒ����',event:'', width:210,hide:''},
           
            {field:'cInvCode', title: '��Ʒ�ͺ�',event:'cInvCode', width:'200',hide:''},
            {field:'poUnit', title: '��ͬ��λ',event:'', width:100,hide:''},

           
            {field:'inum', title: '��װ����', width:100,hide:''},
            {field:'onum', title: '��װ����', width:100,hide:''},
           
            {field:'proColor', title: '��ɫ', width:150,hide:''},
           
            {field:'glueSty', title: '��ϵ', width:80,hide:''},
            {field:'sfieldText', title: '��Ʒ����', width:90,hide:''},
            {field:'baoZhuangMS', title: '��װ��ʽ', width:500,hide:'', sort: ''},
            {field:'tintd', title: '�ھ�', width:60,hide:'', sort: ''},
            {field:'tthick', title: '�ܺ�', width:60,hide:'', sort: ''},                
            {field:'tdf1', title: 'ֽ������', width:74,hide:'', sort: ''},
            {field:'tdesc', title: 'ֽ������Ҫ��', width:60,hide:'', sort: ''},
            {field:'barCode', title: '������', width:130,hide:''},
         
            {field:'sfieldText', title: '��Ʒ����',width:90,hide:'true'},
            {field:'desc', title: '��ע',width:190,hide:''},
            {field:'listpo', title: '��ƷPO��', width:92,hide:'', fixed: 'left'},
    ]],
        
        
        store_product_list:[[
            {type: 'checkbox', width:'', align:'center', toolbar: '#tab1Bar'},
            {type:'numbers', title: '���', width:80,hide:''},
            {field:'order_num_show', title: '������', width:"200",hide:'', sort: ""},
            {field:'num', title: '��������',event:'', width:100,hide:''}, 
            {field:'poUantity', title: '������',event:'', width:100,hide:'',edit:''}, 
            {field:'box_num', title: '��������', width:100,hide:'',totalRow: true }, 
            {field:'tuoPan', title: '����', width:110,hide:'', sort: ''},
            {field:'tuopan_num', title: '��������',event:'', width:100,hide:'',edit:'text',totalRow: true}, 
            {field:'tuopan_weight', title: '��������',event:'', width:100,hide:'',edit:'text',totalRow: true}, 
            {field:'tuopan_size', title: '���̳ߴ�',event:'', width:100,hide:'',edit:'text',totalRow: true}, 
            {field:'tuopan_display', title: '���̰ڷ�',event:'', width:100,hide:'',edit:'text',totalRow: true},
            {field:'tuopan_group', title: '���̷���',event:'', width:100,hide:'',sort: true,totalRow: true}, 
            {field:'jz_1', title: '����',event:'', width:100,hide:'',edit:'',totalRow: true}, 
            {field:'mz_1', title: 'ë��',event:'', width:100,hide:'',edit:'',totalRow: true}, 
            {field:'tj_1', title: '�����',event:'', width:150,hide:'',edit:'',totalRow: true}, 
            
            {field:'c_dwjz', title: '�ͻ�Ҫ��λ����',event:'', width:150,hide:''}, 
            {field:'c_dwmz', title: '�ͻ�Ҫ��λë��',event:'', width:150,hide:''}, 
            {field:'c_dwtj', title: '�ͻ�Ҫ��λ���',event:'', width:150,hide:''},  
            
            {field:'dwtj', title: '��λ���',event:'', width:150,hide:'',edit:''},  
            {field:'dwmz', title: '��λë��',event:'', width:100,hide:'',edit:''}, 
            {field:'dwjz', title: '��λ����',event:'', width:100,hide:'',edit:''}, 
            {field:'dwxs', title: '��λϵ��',event:'', width:100,hide:'',edit:''}, 
            
            {field:'u8Code', title: '�������',event:'', width:100,hide:'',edit:''}, 

            {field:'length', title: '��',event:'', width:60,hide:'',edit:''}, 
            {field:'width', title: '��',event:'', width:60,hide:'',edit:''}, 
            {field:'height', title: '��',event:'', width:60,hide:'',edit:''}, 
            {field:'formula', title: '��λ������㹫ʽ',event:'', width:300,hide:'',edit:''}, 
            {field:'u8Code', title: '�������', width:115,hide:'',event:'editU8code'},

            {field:'complete', title: '��Ʒ/���Ʒ',event:'', width:150,hide:''},
            {field:'category', title: '��Ʒ����',event:'', width:210,hide:''},
            {field:'proColor', title: '��ɫ', width:150,hide:''},    
            {field:'name', title: '��Ʒ����',event:'', width:210,hide:''},
            {field:'cInvCode', title: '��Ʒ�ͺ�',event:'cInvCode', width:'200',hide:''},
            {field:'proUnit', title: '������λ',event:'', width:100,hide:''},
            {field:'org_num', title: '��ͬ����',event:'', width:100,hide:''},
            {field:'boxNum', title: '��ͬ������', width:100,hide:'' },
            {field:'glueSty', title: '��ϵ', width:80,hide:''},
            {field:'sfieldText', title: '��Ʒ����', width:90,hide:''},
            {field:'baoZhuangMS', title: '��װ��ʽ', width:500,hide:'', sort: ''},
            {field:'tdf1', title: 'ֽ������', width:200,hide:'', sort: ''},
            {field:'customerCode', title: '�ͻ�����', width:200,hide:''},
        ]],
        
        plan_product_list:[[
            {type: 'checkbox', width:'', align:'center', toolbar: '#tab1Bar'},
            {type:'numbers', title: '���', width:80,hide:''},
            {field:'order_num_show', title: '������', width:"200",hide:'', sort: ""},
            {field:'num', title: '��������',event:'', width:100,hide:''}, 
            {field:'poUantity', title: '������',event:'', width:100,hide:'',edit:''}, 
            {field:'box_num', title: '��������', width:100,hide:'',totalRow: true }, 
            {field:'tuoPan', title: '����', width:110,hide:'', sort: ''},
            {field:'tuopan_num', title: '��������',event:'', width:100,hide:'',edit:'text',totalRow: true}, 
            {field:'tuopan_weight', title: '��������',event:'', width:100,hide:'',edit:'text',totalRow: true}, 
            {field:'tuopan_size', title: '���̳ߴ�',event:'', width:100,hide:'',edit:'text',totalRow: true}, 
            {field:'tuopan_display', title: '���̰ڷ�',event:'', width:100,hide:'',edit:'text',totalRow: true},
            {field:'tuopan_group', title: '���̷���',event:'', width:100,hide:'',sort: true,totalRow: true}, 
            {field:'u8Code', title: '�������', width:115,hide:'',event:'editU8code'},
            {field:'complete', title: '��Ʒ/���Ʒ',event:'', width:150,hide:''},
            {field:'category', title: '��Ʒ����',event:'', width:210,hide:''},
            {field:'proColor', title: '��ɫ', width:150,hide:''},    
            {field:'name', title: '��Ʒ����',event:'', width:210,hide:''},
            {field:'cInvCode', title: '��Ʒ�ͺ�',event:'cInvCode', width:'200',hide:''},
            {field:'proUnit', title: '������λ',event:'', width:100,hide:''},
            {field:'org_num', title: '��ͬ����',event:'', width:100,hide:''},
            {field:'boxNum', title: '��ͬ������', width:100,hide:'' },
            {field:'glueSty', title: '��ϵ', width:80,hide:''},
            {field:'sfieldText', title: '��Ʒ����', width:90,hide:''},
            {field:'baoZhuangMS', title: '��װ��ʽ', width:500,hide:'', sort: ''},
            {field:'tdf1', title: 'ֽ������', width:200,hide:'', sort: ''},
            {field:'customerCode', title: '�ͻ�����', width:200,hide:''},
        ]],
        
        'product_list_cabinet':[[
            {type: 'checkbox', width:'', align:'center', toolbar: '#tab1Bar'}, 
            {type:'numbers', title: '���', width:80,hide:''},
            {field:'is_closing', title: '�깤״̬', width:80,hide:''},
            {field:'order_num_show', title: '������', width:"200",hide:''},
            {field:'num', title: '��������',event:'', width:100,hide:'',edit:'text'}, 
            {field:'box_num', title: '��������', width:100,hide:'', edit:'text',totalRow: true },
            
            {field:'tuoPan', title: '����', width:110,hide:''},
            {field:'tuopan_num', title: '��������',event:'', width:100,hide:'',edit:'text',totalRow: true}, 
            {field:'tuopan_weight', title: '��������',event:'', width:100,hide:'',edit:'text',totalRow: true}, 
            {field:'tuopan_size', title: '���̳ߴ�',event:'', width:100,hide:'',edit:'text',totalRow: true}, 
            {field:'tuopan_display', title: '���̰ڷ�',event:'', width:100,hide:'',edit:'text',totalRow: true},
            {field:'tuopan_group', title: '���̷���',event:'', width:100,hide:'',sort: true,totalRow: true}, 
            
            {field:'cInvCode', title: '��Ʒ�ͺ�',event:'cInvCode', width:'200',hide:''},  
            {field:'u8Code', title: '�������', width:115,hide:'',event:'editU8code'},
            {field:'complete', title: '��Ʒ/���Ʒ',event:'', width:150,hide:''},
            {field:'category', title: '��Ʒ����',event:'', width:210,hide:''},
            {field:'proColor', title: '��ɫ', width:150,hide:''},    
            {field:'name', title: '��Ʒ����',event:'', width:210,hide:''},
            {field:'cInvCode', title: '��Ʒ�ͺ�',event:'cInvCode', width:'200',hide:''},
            {field:'proUnit', title: '������λ',event:'', width:100,hide:''},
            {field:'org_num', title: '��ͬ����',event:'', width:100,hide:''},
            {field:'boxNum', title: '��ͬ������', width:100,hide:'' },
            {field:'glueSty', title: '��ϵ', width:80,hide:''},
            {field:'sfieldText', title: '��Ʒ����', width:90,hide:''},
            {field:'baoZhuangMS', title: '��װ��ʽ', width:500,hide:'', sort: ''},
            {field:'tdf1', title: 'ֽ������', width:200,hide:'', sort: ''},
            {field:'customerCode', title: '�ͻ�����', width:200,hide:''},
        ]],

        'cabinet_num': [[
            {field:'cabinet_type',width:100, title: '����'},
            {field:'cabinet1',event:'', title: '20�߹�', width:200,edit:'text'},
            {field:'cabinet2',event:'', title: '40��ƽ��', width:200,edit:'text'},
            {field:'cabinet3',event:'', title: '40�߸߹�', width:200,edit:'text'},
            {field:'cabinet4',event:'', title: '45�߹�', width:200,edit:'text'}, 
            {field:'cabinet5',event:'', title: 'NORƽ��', width:200,edit:'text'}, 
            {field:'cabinet6',event:'', title: 'NOR�߹�', width:200,edit:'text'}, 
           
        ]], 
        'provebill':[[ 
                
            {type: 'checkbox', width:'', align:'center',hide:true, toolbar: '#tab1Bar',fixed: 'left'}, 
            {field: 'id', title: 'ID', width: 60, sort: true, fixed: 'left'}, 
           
            {field:'bill_num', title: '���յ���', hide:true, width:250,fixed: 'left',
                templet: function(d){
                    var retInfo=d.bill_num;
                    if(d.backPrcs==1){
                        return "<span style='color:red' title='�˻�ԭ��"+d.comment+"' >"+d.bill_num+"&nbsp;(�˻�)</span>";
                    }else if(d.has_adjust==1){
                        return "<span style='color:#FFB800' title='' >"+d.bill_num+"&nbsp;(�е���)</span>";
                    }else if(d.bill_status==1){
                        return "<span style='color:red' title='' >"+d.bill_num+"&nbsp;(��ȡ��)</span>";
                    }
                    
                    
      
                    var arrayMap={"1":{"text":"ϵͳ�Ѵ�ӡ","color":""},"2":{"text":"�����Ѵ�ӡ","color":""},"3":{"text":"�ֿ��Ѵ�ӡ","color":"#FFB800"}};
                    if(d.print_status!=0&&d.print_status!=""&&d.print_status!=null){
                        var style="";
                        if(arrayMap[d.print_status]["color"]!=""){
                            style="style='color:"+arrayMap[d.print_status]["color"]+"'";
                        } 
                        return "<span "+style+" title='"+arrayMap[d.print_status]["text"]+"' >"+d.bill_num+"&nbsp;("+arrayMap[d.print_status]["text"]+")</span>";
                    }
                 
                    

                   /* if(d.currentStep>3){
                        return "<span style='color:#ff5722' >"+d.bill_num+"&nbsp;(�����)</span>";
                    }*/

                    return retInfo;
                } 
            },
            
            {field: 'invoice_num', title: '��Ʊ��',width:200,fixed: 'left',
                templet: function(d){
                    var retInfo=d.invoice_num;
                    if(d.rd_id>0){
                        return "<span style='color:red' title='���˻�������' >"+d.invoice_num+"&nbsp;(�˻�)</span>";
                    }
                    if(d.backPrcs==1){
                        return "<span style='color:red' title='�˻�ԭ��"+d.comment+"' >"+d.invoice_num+"&nbsp;(�˻�)</span>";
                    }else if(d.has_adjust==1){
                        return "<span style='color:#FFB800' title='' >"+d.invoice_num+"&nbsp;(�е���)</span>";
                    }else if(d.bill_status==1){
                        return "<span style='color:red' title='' >"+d.invoice_num+"&nbsp;(��ȡ��)</span>";
                    }
                    
                    
    
                    var arrayMap={"1":{"text":"ϵͳ�Ѵ�ӡ","color":""},"2":{"text":"�����Ѵ�ӡ","color":""},"3":{"text":"�ֿ��Ѵ�ӡ","color":"#FFB800"}};
                    if(d.print_status!=0&&d.print_status!=""&&d.print_status!=null){
                        var style="";
                        if(arrayMap[d.print_status]["color"]!=""){
                            style="style='color:"+arrayMap[d.print_status]["color"]+"'";
                        } 
                        return "<span "+style+" title='"+arrayMap[d.print_status]["text"]+"' >"+d.invoice_num+"&nbsp;("+arrayMap[d.print_status]["text"]+")</span>";
                    }
                
                    

                /* if(d.currentStep>3){
                        return "<span style='color:#ff5722' >"+d.bill_num+"&nbsp;(�����)</span>";
                    }*/

                    return retInfo;
                } 
            }, 
            
            {field: 'sales_num', title: '��������',hide:'true',width:150,fixed: 'left'}, 
            
            {field: 'order_nums', title: '������',width:150,fixed: 'left'}, 
            {field: 'customer_short', title: '�ͻ����',width:100,fixed: 'left'}, 
            {field: 'cabinet_num', title: '����',width:150},
            {field: 'give_time', title: 'ȷ�Ͻ���',width:150},
            {field: 'cabinet_time', title: 'װ������',width:150},
            {field: 'ship_time',  title: 'Ԥ�ƴ���',width:150},
            {field: 'real_ship_time', title: 'ʵ�ʴ���',width:150},
            {field: 'start_port', title: '���˸�',width:100},
            {field: 'end_port', title: 'Ŀ�ĸ�',width:100},
            {field: 'address', title: '������',width:100},
            {field: 'prove_type', title: '����֤������',width:100},           
            {field: 'customer_country', title: '����',width:100}, 
            {field: 'customer_name', title: '�ͻ�����',hide:'true',width:200}, 
            {field: 'customer_code', title: '�ͻ�����',hide:'true'}, 
            {field: 'load_type', title: 'װ������',width:80},
            {field: 'trade_type', title: 'ó�׷�ʽ',width:80},
            {field: 'inout_type', title: '��ŵ���ʽ',width:100},
            {field: 'pay_type', title: '���ʽ',width:100},
            {field: 'coin_type', title: '����',width:80},
            {field: 'adjust_money', title: '���˽��',width:100},
            {field: 'brand', title: 'Ʒ��',width:100},
            {field: 'inspect_time', title: '���ʱ��',width:150},       
            {field: 'is_appoint', title: '�Ƿ�ָ�������򴬹�˾',width:100, align:'center'},
            {field: 'appoint_info', title: 'ָ�������򴬹�˾��Ϣ',width:200},
            {field: 'customer_start', title: '�ͻ�Ҫ�󿪴�����',width:150},
            {field: 'customer_end', title: '�ͻ�Ҫ�󵽸�����',width:150},
            
            {field: 'status', title: '��ǰ����',width:150},
            {field: 'prcsFlag', title: '��ǰ״̬',width:150},
            
            
            {field: 'create_user', title: '�Ƶ���',width:100},
            {field: 'deliver_create_time', title: '�������ύʱ��', hide:"true" ,width:150},
            {field: 'create_time', title: '�Ƶ�ʱ��',width:150},
            {field: 'atTime', title: '�ύʱ��',width:150},
            {fixed: 'right', width: 500, align: 'center', toolbar: '#tab1Bar'}
        ]],

        'bill_deliver':[[
            {type: 'radio', width:'', align:'center', toolbar: '#tab1Bar'},
            {field: 'id', title: 'ID', width: 60, sort: true}, 
            {field: 'deliverId', title: 'ID', width: 60, sort: true, hide:"true"}, 
            {field: 'deliver_num', title: '��������', width:260},
            {field: 'order_num', title: '������',width:300},
            {field: 'sales_num', title: '��������',width:200, sort: 'true'}, 
            {field: 'customer_short', title: '�ͻ����',width:200}, 
            {field: 'deliver_date', title: '����ʱ��',width:100}, 
            {field: 'deliver_store', title: '�����ֿ�',width:100},
            {field: 'coin_type', title: '����',width:100},
            {field: 'create_time', title: '��������',width:150},
            {fixed: 'right', width: 400, align: 'center', toolbar: '#tab1Bar'}

        ]],
        'reback_deliver':[[
            {type: 'radio', width:'', align:'center', toolbar: '#tab1Bar'},
            {field: 'id', title: 'ID', width: 60, sort: true}, 
            {field: 'deliverId', title: 'ID', width: 60, sort: true, hide:"true"}, 
            // {field: 'bill_ids', title: 'ID', width: 60, sort: true, hide:"true"}, 

            {field:'deliver_num', title: '��������', width:260},
            {field: 'reback_num', title: '�˻�����',width:300},
            {field: 'order_num', title: '������',width:300},
            {field: 'sales_num', title: '��������',width:200, sort: 'true'}, 
            {field: 'customer_short', title: '�ͻ����',width:200}, 
            {field: 'ctime', title: '�˻�ʱ��',width:150}, 
            {field: 'total_je', title: '�˻����',width:100},
            {field: 'total_sl', title: '�˻�����',width:100},
            {field: 'total_js', title: '�˻�����',width:100},
            {field: 'status', title: '���״̬',width:100},
            {field: 'shr_name', title: '�����',width:100},
            {field: 'sh_time', title: '���ʱ��',width:150},
            {field: 'ctime', title: '��������',width:150},
            {field: 'cname', title: '������',width:100},
            {fixed: 'right', width: 400, align: 'center', toolbar: '#tab1Bar'}

        ]],
        'reback_stat':[[
            {field: 'id', title: 'ID', width: 60, sort: true}, 
            {field:'deliver_num', title: '��������', width:260},
            {field: 'reback_num', title: '�˻�����',width:300},
            {field: 'sales_num', title: '��������',width:200}, 
            {field: 'cInvCode', title: '�������',width:200}, 
            {field: 'name', title: '����',width:200}, 
            {field: 'remain_je', title: '�˻����',width:100},
            {field: 'remain_sl', title: '�˻�����',width:100},
            {field: 'remain_js', title: '�˻�����',width:100},
            {field: 'ctime', title: '��������',width:150}
        ]],
        'bill_deliver_products':[[
            {type: 'checkbox', width:'', align:'center', toolbar: '#tab1Bar'},
            {type:'numbers', title: '�к�', width:50,hide:''},
            {field:'id', title: '��ϸID', width:"200",hide:true, sort: ""},
            {field:'order_num_show', title: '������', width:"200",hide:'', sort: ""},
            {field:'u8Code', title: '�������', width:115,hide:'',event:'editU8code'},            
            {field: 'chmc', title: '�������',width:150,sort: true},
            {field:'glueSty', title: '��ϵ', width:80,hide:''},
            {field:'norms', title: '����ͺ�',event:'', width:100,hide:true,edit:'',templet: function(d){
                return d.length+"M*"+d.width+"MM";
            }}, 
            {field:'deliver_num', title: '�ܷ�������', width:80,hide:'',totalRow: true },
            // {field:'remain_deliver_num', title: 'ʣ����˻�����', width:80,hide:'',totalRow: true },
            {field:'sl', title: '�����˻�����', width:120,hide:'',edit:'text',totalRow: true },
            {field:'poUnit', title: '��λ',event:'', width:100,hide:true},
            {field:'sldw', title: '��λ',event:'', width:100,hide:true},
            {field:'proUnit', title: '��λ',event:'', width:60,hide:""},
            {field:'deliver_box', title: '�ܷ�������',event:'', width:80,totalRow: true},
            {field:'remain_deliver_box', title: 'ʣ����˻�����',event:'', width:80,totalRow: true},
            {field:'js', title: '���ζ�������', width:120,hide:'',edit:'text',totalRow: true },
            {field:'poUantity', title: '��ͬ����',event:'', width:80,hide:'',totalRow: true},
            {field:'proPrice', title: '��������',event:'', width:100,hide:''},
            {field:'poPrice1', title: '��ͬ����',event:'', width:100,hide:''},
            {field:'poPrice', title: '����',event:'', width:100,hide:'',edit:'text',totalRow: true},
            {field:'total_1', title: 'ԭ�ҽ��',event:'', width:100,hide:'',totalRow: true},
            // {field:'remain_total_1', title: 'ʣ����˻�ԭ�ҽ��',event:'', width:100,hide:'',totalRow: true},
            {field:'je', title: '�����˻����',event:'', width:120,hide:'',edit:'text',totalRow: true},
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
            
        ]], 
    
        'provebill_cabinet':[[ 
            {type: 'checkbox', width:'', align:'center', fixed: 'left', toolbar: '#tab1Bar'},    
            {field: 'id', title: 'ID', width: 60, sort: true, fixed: 'left'}, 
           
            {field:'bill_num', title: '���յ���', width:230,fixed: 'left',
                templet: function(d){
                    var retInfo=d.bill_num;
                    if(d.backPrcs==1){
                        return "<span style='color:red' title='�˻�ԭ��"+d.comment+"' >"+d.bill_num+"&nbsp;(�˻�)</span>";
                    }else if(d.has_adjust==1){
                        return "<span style='color:#FFB800' title='' >"+d.bill_num+"&nbsp;(�е���)</span>";
                    }

                   /* if(d.currentStep>3){
                        return "<span style='color:#ff5722' >"+d.bill_num+"&nbsp;(�����)</span>";
                    }*/

                    return retInfo;
                } 
            },
            
            {field: 'invoice_num', title: '��Ʊ��',width:200,fixed: 'left'}, 
            {field: 'order_nums', title: '������',width:150,fixed: 'left'}, 
            {field: 'customer_short', title: '�ͻ����',width:100,fixed: 'left'},
            {field: 'customer_country', title: '����',width:100},
            {field: 'customer_name', title: '�ͻ�����',hide:'true',width:200}, 
            {field: 'customer_code', title: '�ͻ�����',hide:'true'}, 
            {field: 'load_type', title: 'װ������',width:80},
            {field: 'trade_type', title: 'ó�׷�ʽ',width:80},
            {field: 'inout_type', title: '��ŵ���ʽ',width:100},
            {field: 'pay_type', title: '���ʽ',width:100},
            {field: 'coin_type', title: '����',width:80},
            {field: 'adjust_money', title: '���˽��',width:100},
            {field: 'brand', title: 'Ʒ��',width:100},
            {field: 'prove_type', title: '����֤������',width:100},
            {field: 'start_port', title: '���˸�',width:100},
            {field: 'end_port', title: 'Ŀ�ĸ�',width:100},

            {field: 'give_time', title: 'ȷ�Ͻ���',width:150},

            {field: 'inspect_time', title: '���ʱ��',width:150},         
            {field: 'is_appoint', title: '�Ƿ�ָ�������򴬹�˾',width:100, align:'center'},
            {field: 'appoint_info', title: 'ָ�������򴬹�˾��Ϣ',width:200},
            {field: 'customer_start', title: '�ͻ�Ҫ�󿪴�����',width:150},
            {field: 'customer_end', title: '�ͻ�Ҫ�󵽸�����',width:150},
            
            {field: 'status', title: '��ǰ����',width:150},
            {field: 'prcsFlag', title: '��ǰ״̬',width:150},
            
            
            {field: 'create_user', title: '�Ƶ���',width:100},
            {field: 'create_time', title: '�Ƶ�ʱ��',width:150},
            {field: 'atTime', title: '�ύʱ��',width:150},
            {field: 'differTime', title: '���ʱ��',width:150},
            /*{field: 'td_time', title: '�ᵥ�ϴ�ʱ��',width:150},
            {field: 'cdz_time', title: '����֤�ϴ�ʱ��',width:150},*/
            {fixed: 'right', width: 300, align: 'center', toolbar: '#tab1Bar'}
        ]],

       /* get_provebill_cols(store){
            console.log(store);
            this.provebill[0][1]={
                field:'bill_num', title: '���յ���', width:250,fixed: 'left',
                templet: function(d){
                    var retInfo=d.bill_num;
                    if(d.backPrcs==1){
                        return "<span style='color:red' title='�˻�ԭ��"+d.comment+"' >"+d.bill_num+"&nbsp;(�˻�)</span>";
                    }

                    if(d.currentStep>3&&store["params"]["showType"]==17){
                        return "<span style='color:#ff5722' >"+d.bill_num+"&nbsp;(�����)</span>";
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
                {field: 'invoice_num', title: '��Ʊ��',width:200,fixed: 'left',
                    templet: function(d){
                        var retInfo=d.invoice_num;
                        if(d.backPrcs==1){
                            return "<span style='color:red' title='�˻�ԭ��"+d.comment+"' >"+d.invoice_num+"&nbsp;(�˻�)</span>";
                        }else if(d.has_adjust==1){
                            return "<span style='color:#FFB800' title='' >"+d.invoice_num+"&nbsp;(�е���)</span>";
                        }else if(d.bill_status==1){
                            return "<span style='color:red' title='' >"+d.invoice_num+"&nbsp;(��ȡ��)</span>";
                        }



                        var arrayMap={"1":{"text":"ϵͳ�Ѵ�ӡ","color":""},"2":{"text":"�����Ѵ�ӡ","color":""},"3":{"text":"�ֿ��Ѵ�ӡ","color":"#FFB800"}};
                        if(d.print_status!=0&&d.print_status!=""&&d.print_status!=null){
                            var style="";
                            if(arrayMap[d.print_status]["color"]!=""){
                                style="style='color:"+arrayMap[d.print_status]["color"]+"'";
                            } 
                            return "<span "+style+" title='"+arrayMap[d.print_status]["text"]+"' >"+d.invoice_num+"&nbsp;("+arrayMap[d.print_status]["text"]+")</span>";
                        }



                    /* if(d.currentStep>3){
                            return "<span style='color:#ff5722' >"+d.bill_num+"&nbsp;(�����)</span>";
                        }*/

                        return retInfo;
                    } 
                }, 

                {field: 'sales_num', title: '��������',hide:'true',width:150,fixed: 'left'}, 

                {field: 'order_nums', title: '������',width:150,fixed: 'left'}, 
                {field: 'customer_short', title: '�ͻ����',width:100,fixed: 'left'}, 
                {field: 'cabinet_num', title: '����',width:150},
                {field: 'give_time', title: 'ȷ�Ͻ���',width:150},
                {field: 'cabinet_time', title: 'װ������',width:150},
                {field: 'ship_time', title: 'Ԥ�ƴ���',width:150},
                {field: 'real_ship_time', title: 'ʵ�ʴ���',width:150},
                {field: 'start_port', title: '���˸�',width:100},
                {field: 'end_port', title: 'Ŀ�ĸ�',width:100},
                {field: 'address', title: '������',width:100},
                {field: 'prove_type', title: '����֤������',width:100},           
                {field: 'customer_country', title: '����',width:100}, 
                {field: 'customer_name', title: '�ͻ�����',hide:'true',width:200}, 
                {field: 'customer_code', title: '�ͻ�����',hide:'true'}, 
                {field: 'load_type', title: 'װ������',width:80},
                {field: 'trade_type', title: 'ó�׷�ʽ',width:80},
                {field: 'inout_type', title: '��ŵ���ʽ',width:100},
                {field: 'pay_type', title: '���ʽ',width:100},
                {field: 'coin_type', title: '����',width:80},
                {field: 'adjust_money', title: '���˽��',width:100},
                {field: 'brand', title: 'Ʒ��',width:100},
                {field: 'inspect_time', title: '���ʱ��',width:150},       
                {field: 'is_appoint', title: '�Ƿ�ָ�������򴬹�˾',width:100, align:'center'},
                {field: 'appoint_info', title: 'ָ�������򴬹�˾��Ϣ',width:200},
                {field: 'customer_start', title: '�ͻ�Ҫ�󿪴�����',width:150},
                {field: 'customer_end', title: '�ͻ�Ҫ�󵽸�����',width:150},

                {field: 'status', title: '��ǰ����',width:150},
                {field: 'prcsFlag', title: '��ǰ״̬',width:150},


                {field: 'create_user', title: '�Ƶ���',width:100},
                {field: 'deliver_create_time', title: '�������ύʱ��', hide:"true" ,width:150},
                {field: 'create_time', title: '�Ƶ�ʱ��',width:150},

            ]];
        },

        'account_set':[[
            {field: 'id', title: 'ID', width: 60, sort: true}, 
            {field: 'zhangTaoText', title: '��������',width:200, sort: 'true'}, 
            {field: 'account_name', title: '�������ƣ�Ӣ�ģ�',width:300}, 
            {field: 'account_address', title: '���׵�ַ��Ӣ�ģ�',width:300}, 
            {field: 'company_name', title: '��˾ȫ�ƣ����ģ�',width:200},
            {field: 'entrust_man', title: '����ί����',width:100},
            {field: 'entrust_tel', title: '������ϵ�˵绰',width:200},
            {field: 'entrust_fax', title: '���˴���',width:200},
            {field: 'zip_code', title: '�ʱ�',width:100},
            {field: 'create_user', title: '�Ƶ���',width:100},
            {field: 'create_time', title: '��������',width:150},
            {fixed: '', width: 200, align: 'center', toolbar: '#tab1Bar'}
        ]],
          'cattle_list':[[
              {field: 'id', title: 'ID', width: 60, sort: true},
              {field: 'cCode', title: '���ص���',width:200, sort: 'true'},
              {field: 'Code', title: '��Ʊ��',width:300},
              {field: 'dDate', title: '��������',width:'150'},
              {field: 'cblno', title: '�ᵥ��',width:'180'},
              {field: 'declarationName', title: '���ص�������',width:'230',event:'declarationName'},
              {field: 'attId', title: '���ص�id',hide:true},
              {field: 'attName', title: '���ص�����',hide:true},
              {field: 'dName', title: 'ί���鸽����',width:'230',event:'dName'},
              {field: 'attId2', title: 'ί����id',hide:true},
              {field: 'attName2', title: '���ص�����',hide:true},
              {field: 'money_y', title: '�˷�/��',width:'100'},
              {field: 'currency_b', title: '���շѱ���',width:'100'},
              {field: 'money_b', title: '���շ�',width:'100'},
              {fixed: 'right', width: 200, align: 'center', toolbar: '#tab1Bar'}
          ]],
        'bill_confirm_list':[[
            {"field": "id", "title": "ID", "width": 60, "sort": "TRUE", "FIXED": "left"},
            {"field": "bill_num", "title": "����","width":"250", "sort": "true","fixed": "left"},
            {"field": "invoice_num", "title": "������","width":"200", "sort": "true","fixed": "left"},
            {"field": "customer_name", "title": "�ͻ�����","width":"200"},
            {"field": "customer_short", "title": "�ͻ����","width":"200"},

            {"field": "customer_code", "title": "�ͻ�����","hide":"true"},
            {"field": "load_type", "title": "װ������","width":"100"},
            {"field": "trade_type", "title": "ó�׷�ʽ","width":"100"},
            {"field": "inout_type", "title": "��ŵ���ʽ","width":"100"},
            {"field": "pay_type", "title": "���ʽ","width":"100"},
            {"field": "coin_type", "title": "����","width":"100"},
            {"field": "status", "title": "״̬","width":"100"},
            {"field": "brand", "title": "Ʒ��","width":"150"},
            {"field": "prove_type", "title": "����֤������","width":"100"},
            {"field": "start_port", "title": "���˸�","width":"100"},
            {"field": "end_port", "title": "Ŀ�ĸ�","width":"100"},
            {"field": "give_time", "title": "ȷ�Ͻ���","width":"150"},
            {"field": "give_time_change", "title": "�����Ƿ���","width":"150"},
            {"field": "inspect_time", "title": "���ʱ��","width":"150"},
            {"field": "is_appoint", "title": "�Ƿ�ָ�������򴬹�˾","width":"100", "align":"center"},
            {"field": "appoint_info", "title": "ָ�������򴬹�˾��Ϣ","width":200},
            {"field": "customer_start", "title": "�ͻ�Ҫ�󿪴�����","width":150},
            {"field": "customer_end", "title": "�ͻ�Ҫ�󵽸�����","width":150},
            {"field": "beginTime", "title": "���ʱ��","width":150},
            {"field": "beginUserName", "title": "�Ƶ���","width":150},
            {"fixed": "right", "width":"100", "align":"center","hide":"", "toolbar": "#tab1Bar"}
        ]],

        'gendan_cabinet_product_list':[[
            {type: 'checkbox', width:'', align:'center', toolbar: '#tab1Bar'}, 
            {type:'numbers', title: '���', width:80,hide:''},
            {field:'order_num_show', title: '������', width:"200",hide:''},
            {field:'num', title: '��������',event:'', width:100,hide:'',edit:'text'}, 
            {field:'box_num', title: '��������', width:100,hide:'', edit:'text',totalRow: true },
            {field:'jz_1', title: '����',event:'', width:100,hide:'',edit:'',totalRow: true}, 
            {field:'mz_1', title: 'ë��',event:'', width:100,hide:'',edit:'',totalRow: true},
            {field:'tj_1', title: '�����',event:'', width:150,hide:'',edit:'',totalRow: true}, 
            {field:'tuoPan', title: '����', width:110,hide:''},
            {field:'tuopan_num', title: '��������',event:'', width:100,hide:'',edit:'text',totalRow: true}, 
            {field:'tuopan_weight', title: '��������',event:'', width:100,hide:'',edit:'text',totalRow: true}, 
            {field:'tuopan_size', title: '���̳ߴ�',event:'', width:100,hide:'',edit:'text',totalRow: true}, 
            {field:'tuopan_display', title: '���̰ڷ�',event:'', width:100,hide:'',edit:'text',totalRow: true},
            {field:'tuopan_group', title: '���̷���',event:'', width:100,hide:'',sort: true,totalRow: true}, 
            
            {field:'cInvCode', title: '��Ʒ�ͺ�',event:'cInvCode', width:'200',hide:''},  
            {field:'u8Code', title: '�������', width:115,hide:'',event:'editU8code'},
            {field:'complete', title: '��Ʒ/���Ʒ',event:'', width:150,hide:''},
            {field:'category', title: '��Ʒ����',event:'', width:210,hide:''},
            {field:'proColor', title: '��ɫ', width:150,hide:''},    
            {field:'name', title: '��Ʒ����',event:'', width:210,hide:''},
            {field:'cInvCode', title: '��Ʒ�ͺ�',event:'cInvCode', width:'200',hide:''},
            {field:'proUnit', title: '������λ',event:'', width:100,hide:''},
            {field:'org_num', title: '��ͬ����',event:'', width:100,hide:''},
            {field:'boxNum', title: '��ͬ������', width:100,hide:'' },
            {field:'glueSty', title: '��ϵ', width:80,hide:''},
            {field:'sfieldText', title: '��Ʒ����', width:90,hide:''},
            {field:'baoZhuangMS', title: '��װ��ʽ', width:500,hide:'', sort: ''},
            {field:'tdf1', title: 'ֽ������', width:200,hide:'', sort: ''},
            {field:'customerCode', title: '�ͻ�����', width:200,hide:''},
        ]],
        'pay_type':{
            "DP 60��":"DP 60Days","DP 90��":"DP 90Days","DP 120��":"DP 120Days","TT 30��":"TT 30Days","TT 40��":"TT 40Days","TT 45��":"TT 45Days",
            "TT 55��":"TT 55Days","TT 60��":"TT 60Days","TT 90��":"TT 90Days","TT 120��":"TT 120Days","���ᵥ����-40��":"40Days from BL","���ᵥ����-45��":"45Days from BL",
            "���ᵥ����-60��":"60Days from BL","DP30��":"DP 30Days","TT100��":"TT 100Days","��������֤":"LC at sight","Զ������֤-15��":"LC within 15Days",
            "Զ������֤-30��":"LC within 30Days","Զ������֤-45��":"LC within 45Days","Զ������֤-90��":"LC within 90Days","Զ������֤-120��":"LC within 120Days"
        },

  };

  exports('bill_cols', obj);
});   
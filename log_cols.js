layui.define(function(exports){ 
  var obj = {
        
         
        deliver:{
            "name":"����",
            "cols":{
                
                "deliver_store":"�����ֿ�",
                "deliver_date":"����ʱ��",
                "coin_type":"����",
                "warehouse_name":"�����ֿ�",
                "warehouse_person":"�ֿ⸺����",
                "warehouse_code":"�ֿ����",
                "entry_num":"���ֱ��",
                "real_cabinet_time":"ʵ��װ������",
                "pro_loan_company":"������˾",
                "transport":"���䷽ʽ",
                "mark":"��ע"
            }
        },


        
        provebill:{
            "name":"����",
            "cols":{
                "load_type": "װ������",
                "marks_nos": "Marks & Nos",
                "invoice_num": "��Ʊ��",
                "customer_name": "��Ʊ̧ͷ",
                "cabinet_num": "������Ϣ",
                "is_overweight_cabinet": "�Ƿ��ع�",
                "cabinet_mark": "���͹������ⱸע",
                "trade_type": "ó�׷�ʽ",
                "inout_type": "��ŵ���ʽ",
                "pay_type": "���ʽ",
                "coin_type": "����",
                "brand": "Ʒ��",
                "prove_type": "����֤������",
                "certificate_type": "��֤������",
                "start_port": "���˸�",
                "end_port": "Ŀ�ĸ�",
                "payment": "��������",
                "is_appoint": "�Ƿ�ָ������������˾",
                "appoint_info": "ָ������������˾��Ϣ",
                "give_time": "ȷ�Ͻ���",
                "inspect_time": "���ʱ��",
                "customer_start": "�ͻ�Ҫ�󿪴�����",
                "customer_end": "�ͻ�Ҫ�󵽸�����",
                "adjust_money":"�����ܽ��",
                "pre_ocean_freight":"Ԥ�����˷�",
                "ocean_freight":"ʵ�ʺ��˷�",
                "acut_adj_reason":"����ԭ��",
                "special_mark":"����Ҫ��",
                "acut_adj_type":"��������",
                "adjust_name":"��������",
                "entry_address":"���ֵ�ַ",
                "mark":"��ע"
            }
        },
        
        
        
        'product':{
            "name":"��Ʒ����",
            "cols":{
                "attId": "����ID",
                "attText": "��������",
                "cInvCode": "��Ʒ�ͺ�",
                "cInvCode2": "�²�Ʒ�ͺ�",
                "category": "��Ʒ���",
                "desc": "��ע",
                "glueSty": "��ϵ",
                "gongYi": "���յ���",
                "js1": "�����(N/IN)",
                "js2": "��ճ��(#)",
                "js3": "��ճ��(H/25*25mm)",
                "js4": "������(N/IN)",
                "js5": "��������(N/IN)",
                "js6": "������(N/IN)",
                "js7": "�쳤��%",
                "name": "��Ʒ����",
                "s_144": "ԭĤ��",
                "s_147": "Ĥ����",
                "s_171": "OPP��ˮ",
                "s_176": "ר�ÿͻ�",
                "s_178": "��֤����",
                "s_184": "��֤����",
                "thick": "�ܺ�um",
                "rwidth": "Ϳ����Ч���MM",
                "sumWidth": "���Ʒ�ܿ��MM",
                "rlength": "���Ʒ���鳤��M",
                "stopDate":"ͣ������",
                "structureDiagramId":"��Ʒ�ṹͼID",
                "structureDiagramName":"��Ʒ�ṹͼ�ļ�����",
                "application":"��ƷӦ��",
                "description":"��Ʒ˵��",
                "cCusAbbName":"ר�ÿͻ�����",
                "cCusCode":"ר�ÿͻ�����",
                "sourceCode":"ԭ����",
                "UV":"��UV����"
                
            }
        },
        
        'order':{
            "name":"��������",
            "cols":{
                "orderNum": "������",
                "cCusName2": "�ͻ�",
                "ddLeiXingText": "��������",
                "zhangTaoText": "����",
                "po": "�ͻ�PO��",
                "date1": "Ŀ�꽻��",
                "date2": "ȷ�Ͻ���",
                "warehouse": "�����ֿ�",
                "incoterms": "ó������",
                "currency": "����",
                "rbank": "�տ�����",
                "rtype": "�ո���Э��",
                "isCheck": "���",
                "yhaddr": "����ص�",
                "guistyle": "����",
                "ordTotal": "�����ܽ��",
                "frignTotal":"����ܽ��",
                "cCusCode":"�ͻ�����",
                "adjust_money":"���˽��",
                "adjust_bill":"�����б�",
                "ygBrand":"����Ʒ��",
                "end_port":"Ŀ�ĸ�",
            }
        },
        'order_list':{
            "name":"������ϸ����",
            "cols":{
                "u8Code": "u8�������",
                "cInvCode": "��Ʒ�ͺ�",
                "category": "��Ʒ���",
                "zhangTaoText": "����",
                "thick": "�ܺ��",
                "glueSty": "��ϵ",
                "proColor": "��Ʒ��ɫ",
                "name": "��Ʒ����",
                "sfieldText": "��Ʒ����",
                "customerCode": "�ͻ�����",
                "brand": "�걨Ʒ��",
                "barCode": "������",
                "complete": "��Ʒ/���Ʒ",
                "length": "��",
                "width": "��",
                "baoZhuangMS": "��װ��ʽ����", 
                "podesc": "��Ʒ����", 
                "poEdesc": "Ӣ������", 
                "poUnit": "��ͬ��λ", 
                "poUantity": "��ͬ����", 
                "poPrice": "��ͬ����", 
                "poPrice2": "opp ���ﵥ��", 
                "total": "���С��", 
                "huanSuanLv": "������", 
                "proUnit": "������λ", 
                "proUantity": "��������", 
                "proPrice": "��������", 
                "tintd": "ֽ���ھ�", 
                "tmaterial2": "ֽ�ܱ���оֽ����", 
                "tmaterial": "�ܱڲ���", 
                "tthick": "�ܱں��", 
                "tdf1": "ֽ������", 
                "tdesc": "ֽ������Ҫ��", 
                "inum": "ֽ��-��װ����", 
                "imaterial": "ֽ��-�������", 
                "icontent": "ֽ��-��������", 
                "itm": "ֽ��-����������", 
                "itieBiao": "�Ƿ�����", 
                "iyinShua": "ӡˢ", 
                "icolor": "ֽ��-������ɫ", 
                "iCloseType": "ֽ��-�������Ҫ��", 
                "idesc": "�ڰ�װ����", 
                "onum": "ֽ��-��װ����", 
                "boxNum": "��װ����", 
                "omaterial": "ֽ��-�������", 
                "otm": "ֽ��-����������", 
                "otieBiao": "�����Ƿ�����", 
                "oyinShua": "�����Ƿ�ӡˢ", 
                "ocolor": "ֽ��-������ɫ", 
                "oCloseType": "ֽ��-�������Ҫ��", 
                "ocontent": "ֽ��-��������", 
                "odesc": "��������", 
                "tuoPan": "����", 
                "tpdesc": "��������Ҫ��", 
                "desc": "��ע", 
                "js1": "�����", 
                "js2": "��ճ��", 
                "js3": "��ճ��", 
                "js4": "������", 
                "js5": "��������", 
                "js6": "������", 
                "js7": "�쳤��", 
                "hsCode": "hs����", 
                "mz":"ë��",
                "jz":"����",
                "frignType":"��ұ���",
                "frignPrice":"��ҵ���",
                "frignTotal":"����ܼ�",
                "model":"�ֱ�",
                "obox":"���ӳ����",
                "oboxtj":"�������",
                "zjz":"�ܾ���",
                "zmz":"��ë��",
                "price_type":"��������",
                "proTotal":"�����ܽ��",
                /*"s_215":"�п���״",
                "s_216":"Ϳ����ʽ",
                "s_218":"ճ��",
                "s_219":"���װֽ",
                "s_234":"�վ���״",
                "s_235":"����",
                "s_238":"ӡˢ����",
                "s_245":"��ͷOPP��ɫ",
                "s_244":"ӡ�ְ��",
                "s_243":"ӡ������",
                "s_217":"����",*/
                "print_width":"��Ч���",
                "print_price":"ӡˢƽ������",
                "print_price_total":"ӡˢƽ���ܼ�",
                
                "is_apportion":"�Ƿ����",
                "apportion_price":"���˵���",
                "apportion_total":"�����ܽ��",
                "num":"��������",
                "box_num":"��������",
                "mz_1":"ë��",
                "jz_1":"����",
                "tj_1":"���",
                "total_1":"�ܼ�",
                "tuopan_num":"��������",
                "tuopan_weight":"��������",
                "deliver_box":"��������",
                "deliver_num":"��������",
                "deliver_total":"�������",
                "productId":"��ƷID"
            }
        },
        
        
        'cabinet':{
            "name":"װ����",
            "cols":{
                "address": "װ���ַ",
                "cabinet_time": "Ԥ��װ������",
                "ship_time": "Ԥ�ƴ���",
                "ship_confirm": "����/����˾��ȷ��",
                "appoint_info": "ָ�������򴬹�˾��Ϣ",
                "so_confirm": "SO�Ƿ�ȷ��",
                "so_info": "SO��Ϣ",
                "load_type": "װ������",
                "real_ship_time": "ʵ�ʴ���",
                "give_time": "ȷ�Ͻ���",
                "etd_date": "����ʱ��",
                "trailer_info": "�ϳ�����",
                "is_ontime": "�Ƿ�׼ʱ����",              
                "entry_num": "���ֱ��",
                "entry_time": "�������ʱ��",
                "real_cabinet_time": "ʵ��װ������",
                "no_so_cause_new": "SOδȷ��ԭ��",
            }
        },

      'orderNum':{
          "name":"�����ű��",
          "cols":{
              "num": "������",
              "cCusCode": "�ͻ�����",
              "cCusName": "�ͻ�����",
              "cCusPPersonText": "ҵ��Ա",
          }
      },
      'finance':{
          "name":"��ƱƷ������",
          "cols":{
              "invcInvDefine": "��ϸ����",
              "invoiceName": "��ƱƷ��",
          }
      },
        table_cols:[[
            {type:'numbers', title: '���', width:80,hide:'', sort: true},
            {field:'mark', title: '��ע',width:200},
            {field:'data1', title:'���ǰ', templet: function(d){ 
                var html="";
                for(var k in d.data1){
                    var dataKey= typeof(layui.log_cols[d.type_flag])!="undefined"?layui.log_cols[d.type_flag]['cols'][k]:k;
                    html+=dataKey+":"+d.data1[k]+",  ";
                }
                return html; 
            }, unresize: true},
            {field:'data2', title:'�����', templet: function(d){
                var html="";
                for(var k in d.data2){
                    var dataKey= typeof(layui.log_cols[d.type_flag])!="undefined"?layui.log_cols[d.type_flag]['cols'][k]:k;
                    html+=dataKey+":"+d.data2[k]+" , ";
                }
                return html; 
            }, unresize: true},
            {field:'create_time',width:150, title: '��������'},
            {field:'creator_name', width:100, title: '������'},
        ]]
  };

  exports('log_cols', obj);
});   
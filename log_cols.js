layui.define(function(exports){ 
  var obj = {
        
         
        deliver:{
            "name":"发货",
            "cols":{
                
                "deliver_store":"发货仓库",
                "deliver_date":"发货时间",
                "coin_type":"币种",
                "warehouse_name":"发货仓库",
                "warehouse_person":"仓库负责人",
                "warehouse_code":"仓库编码",
                "entry_num":"进仓编号",
                "real_cabinet_time":"实际装柜日期",
                "pro_loan_company":"货贷公司",
                "transport":"运输方式",
                "mark":"备注"
            }
        },


        
        provebill:{
            "name":"订舱",
            "cols":{
                "load_type": "装载类型",
                "marks_nos": "Marks & Nos",
                "invoice_num": "发票号",
                "customer_name": "发票抬头",
                "cabinet_num": "柜量信息",
                "is_overweight_cabinet": "是否超重柜",
                "cabinet_mark": "柜型柜量特殊备注",
                "trade_type": "贸易方式",
                "inout_type": "提放单方式",
                "pay_type": "付款方式",
                "coin_type": "币种",
                "brand": "品牌",
                "prove_type": "产地证及类型",
                "certificate_type": "认证书类型",
                "start_port": "起运港",
                "end_port": "目的港",
                "payment": "付款条件",
                "is_appoint": "是否指定货代货船公司",
                "appoint_info": "指定货代货船公司信息",
                "give_time": "确认交期",
                "inspect_time": "验货时间",
                "customer_start": "客户要求开船日期",
                "customer_end": "客户要求到港日期",
                "adjust_money":"调账总金额",
                "pre_ocean_freight":"预估海运费",
                "ocean_freight":"实际海运费",
                "acut_adj_reason":"调账原因",
                "special_mark":"特殊要求",
                "acut_adj_type":"调账类型",
                "adjust_name":"调账名称",
                "entry_address":"进仓地址",
                "mark":"备注"
            }
        },
        
        
        
        'product':{
            "name":"产品更新",
            "cols":{
                "attId": "附件ID",
                "attText": "附件名称",
                "cInvCode": "产品型号",
                "cInvCode2": "新产品型号",
                "category": "产品类别",
                "desc": "备注",
                "glueSty": "胶系",
                "gongYi": "工艺单号",
                "js1": "解卷力(N/IN)",
                "js2": "初粘力(#)",
                "js3": "持粘力(H/25*25mm)",
                "js4": "剥离力(N/IN)",
                "js5": "背剥离力(N/IN)",
                "js6": "抗拉力(N/IN)",
                "js7": "伸长率%",
                "name": "产品名称",
                "s_144": "原膜厚",
                "s_147": "膜类型",
                "s_171": "OPP胶水",
                "s_176": "专用客户",
                "s_178": "认证类型",
                "s_184": "认证类型",
                "thick": "总厚um",
                "rwidth": "涂胶有效宽度MM",
                "sumWidth": "半成品总宽度MM",
                "rlength": "半成品建议长度M",
                "stopDate":"停用日期",
                "structureDiagramId":"产品结构图ID",
                "structureDiagramName":"产品结构图文件名称",
                "application":"产品应用",
                "description":"产品说明",
                "cCusAbbName":"专用客户名称",
                "cCusCode":"专用客户编码",
                "sourceCode":"原代码",
                "UV":"耐UV天数"
                
            }
        },
        
        'order':{
            "name":"订单更新",
            "cols":{
                "orderNum": "订单号",
                "cCusName2": "客户",
                "ddLeiXingText": "订单类型",
                "zhangTaoText": "账套",
                "po": "客户PO号",
                "date1": "目标交期",
                "date2": "确认交期",
                "warehouse": "发货仓库",
                "incoterms": "贸易术语",
                "currency": "币种",
                "rbank": "收款银行",
                "rtype": "收付款协议",
                "isCheck": "验货",
                "yhaddr": "验货地点",
                "guistyle": "柜型",
                "ordTotal": "订单总金额",
                "frignTotal":"外币总金额",
                "cCusCode":"客户编码",
                "adjust_money":"调账金额",
                "adjust_bill":"调账列表",
                "ygBrand":"永冠品牌",
                "end_port":"目的港",
            }
        },
        'order_list':{
            "name":"订单明细更新",
            "cols":{
                "u8Code": "u8存货编码",
                "cInvCode": "产品型号",
                "category": "产品类别",
                "zhangTaoText": "账套",
                "thick": "总厚度",
                "glueSty": "胶系",
                "proColor": "产品颜色",
                "name": "产品名称",
                "sfieldText": "产品特性",
                "customerCode": "客户货号",
                "brand": "申报品牌",
                "barCode": "条形码",
                "complete": "成品/半成品",
                "length": "长",
                "width": "宽",
                "baoZhuangMS": "包装方式描述", 
                "podesc": "产品描述", 
                "poEdesc": "英文描述", 
                "poUnit": "合同单位", 
                "poUantity": "合同数量", 
                "poPrice": "合同单价", 
                "poPrice2": "opp 公斤单价", 
                "total": "金额小计", 
                "huanSuanLv": "换算率", 
                "proUnit": "生产单位", 
                "proUantity": "生产数量", 
                "proPrice": "生产单价", 
                "tintd": "纸管内径", 
                "tmaterial2": "纸管壁内芯纸材质", 
                "tmaterial": "管壁材质", 
                "tthick": "管壁厚度", 
                "tdf1": "纸管内容", 
                "tdesc": "纸管其他要求", 
                "inum": "纸箱-内装箱数", 
                "imaterial": "纸箱-内箱材质", 
                "icontent": "纸箱-内箱内容", 
                "itm": "纸箱-内箱条形码", 
                "itieBiao": "是否贴标", 
                "iyinShua": "印刷", 
                "icolor": "纸箱-内箱颜色", 
                "iCloseType": "纸箱-内箱封箱要求", 
                "idesc": "内包装描述", 
                "onum": "纸箱-外装箱数", 
                "boxNum": "总装箱数", 
                "omaterial": "纸箱-外箱材质", 
                "otm": "纸箱-外箱条形码", 
                "otieBiao": "外箱是否贴标", 
                "oyinShua": "外箱是否印刷", 
                "ocolor": "纸箱-外箱颜色", 
                "oCloseType": "纸箱-外箱封箱要求", 
                "ocontent": "纸箱-外箱内容", 
                "odesc": "外箱描述", 
                "tuoPan": "托盘", 
                "tpdesc": "托盘特殊要求", 
                "desc": "备注", 
                "js1": "解卷力", 
                "js2": "初粘力", 
                "js3": "持粘力", 
                "js4": "剥离力", 
                "js5": "背剥离力", 
                "js6": "抗拉力", 
                "js7": "伸长率", 
                "hsCode": "hs编码", 
                "mz":"毛重",
                "jz":"净重",
                "frignType":"外币币种",
                "frignPrice":"外币单价",
                "frignTotal":"外币总价",
                "model":"手柄",
                "obox":"箱子长宽高",
                "oboxtj":"箱子体积",
                "zjz":"总净重",
                "zmz":"总毛重",
                "price_type":"结算类型",
                "proTotal":"生产总金额",
                /*"s_215":"切口形状",
                "s_216":"涂胶方式",
                "s_218":"粘力",
                "s_219":"外包装纸",
                "s_234":"收卷形状",
                "s_235":"周数",
                "s_238":"印刷内容",
                "s_245":"口头OPP颜色",
                "s_244":"印字版号",
                "s_243":"印字内容",
                "s_217":"表面",*/
                "print_width":"有效宽度",
                "print_price":"印刷平方单价",
                "print_price_total":"印刷平方总价",
                
                "is_apportion":"是否调账",
                "apportion_price":"调账单价",
                "apportion_total":"调整总金额",
                "num":"订舱数量",
                "box_num":"订舱箱数",
                "mz_1":"毛重",
                "jz_1":"净重",
                "tj_1":"体积",
                "total_1":"总价",
                "tuopan_num":"托盘数量",
                "tuopan_weight":"托盘重量",
                "deliver_box":"发货件数",
                "deliver_num":"发货数量",
                "deliver_total":"发货金额",
                "productId":"产品ID"
            }
        },
        
        
        'cabinet':{
            "name":"装柜变更",
            "cols":{
                "address": "装柜地址",
                "cabinet_time": "预计装柜日期",
                "ship_time": "预计船期",
                "ship_confirm": "货代/船公司已确认",
                "appoint_info": "指定货代或船公司信息",
                "so_confirm": "SO是否确认",
                "so_info": "SO信息",
                "load_type": "装载类型",
                "real_ship_time": "实际船期",
                "give_time": "确认交期",
                "etd_date": "到港时间",
                "trailer_info": "拖车货代",
                "is_ontime": "是否准时开船",              
                "entry_num": "进仓编号",
                "entry_time": "最晚进仓时间",
                "real_cabinet_time": "实际装柜日期",
                "no_so_cause_new": "SO未确认原因",
            }
        },

      'orderNum':{
          "name":"订单号变更",
          "cols":{
              "num": "订单号",
              "cCusCode": "客户编码",
              "cCusName": "客户名称",
              "cCusPPersonText": "业务员",
          }
      },
      'finance':{
          "name":"开票品名更新",
          "cols":{
              "invcInvDefine": "明细分类",
              "invoiceName": "开票品名",
          }
      },
        table_cols:[[
            {type:'numbers', title: '序号', width:80,hide:'', sort: true},
            {field:'mark', title: '备注',width:200},
            {field:'data1', title:'变更前', templet: function(d){ 
                var html="";
                for(var k in d.data1){
                    var dataKey= typeof(layui.log_cols[d.type_flag])!="undefined"?layui.log_cols[d.type_flag]['cols'][k]:k;
                    html+=dataKey+":"+d.data1[k]+",  ";
                }
                return html; 
            }, unresize: true},
            {field:'data2', title:'变更后', templet: function(d){
                var html="";
                for(var k in d.data2){
                    var dataKey= typeof(layui.log_cols[d.type_flag])!="undefined"?layui.log_cols[d.type_flag]['cols'][k]:k;
                    html+=dataKey+":"+d.data2[k]+" , ";
                }
                return html; 
            }, unresize: true},
            {field:'create_time',width:150, title: '更新日期'},
            {field:'creator_name', width:100, title: '更新人'},
        ]]
  };

  exports('log_cols', obj);
});   
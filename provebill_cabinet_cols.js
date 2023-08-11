layui.define(['utils'],function(exports){
  var  $ = layui.$
  var date = layui.util.toDateString(new Date(),'yyyy-MM-dd');

  var field_data = [];
  var res = layui.utils.post('isFieldHide',{'table_type':4},'manual_fun');

  if (res.code == 200){
      field_data =  res.data;
  }
  var is_send={"0":"未下发","1":"已下发","2":"下发后退回","3":"正在执行","4":"装柜完成"};//0未下发、1已下发、2下发后退回、3正在执行、4装柜完成
  var field_all = [

        {type: 'checkbox', width:'', align:'center', fixed: 'left', toolbar: '#tab1Bar', rowspan: 2},
        {field: 'id', title: 'ID', width: 60, sort: true, fixed: 'left', rowspan: 2, totalRowText: '合计：'},
        {field:'bill_num', title: '订舱单号', width:230,fixed: 'left',hide:'', rowspan: 2,
            templet: function(d){
                var retInfo=d.bill_num;
                if(d.backPrcs==1){
                    return "<span style='color:red' title='退回原因："+d.comment+"' >"+d.bill_num+"&nbsp;(退回)</span>";
                }else if(d.has_adjust==1){
                    return "<span style='color:#986e00' title='' >"+d.bill_num+"&nbsp;(有调整)</span>";
                }
                return retInfo;
            }
        },
        {field: 'invoice_num', title: '发票号',width:200,fixed: 'left', rowspan: 2},
        {field: 'order_nums', title: '订单号',width:150,fixed: 'left', rowspan: 2},
        {field: 'is_many_gd', title: '是否多个关单',width:100,fixed: 'left', rowspan: 2},
        {field: 'zhangTaoText', title: '账套',width:100,fixed: 'left', rowspan: 2},
        {field: 'is_send', title: '物流状态',width:100,fixed: 'left', rowspan: 2,templet:function (d){ return is_send[d.is_send]; }},
        {field: 'count_gd_num', title: '关单数',width:100,filter: true,event: 'count_gd_num', rowspan: 2,totalRow: true},
      //
        {field: 'address', title: '装货地址',width:100, rowspan: 2},
        {field: 'cCusPPersonText', title: '业务员',width:100, rowspan: 2},
        {field: 'create_user', title: '跟单员',width:100, rowspan: 2},

        {field: 'space_user', title: '订舱员',width:100, rowspan: 2},

        {field: 'dzy', title: '单证员',rowspan: 2, width: 100},
        {field: 'customer_country', title: '国家',width:100, rowspan: 2},
        {field: 'customer_short', title: '客户简称',width:100, rowspan: 2},
        {field: 'pay_type', title: '付款方式',width:100, rowspan: 2},
        {field: 'is_customs_information', title: '报关资料寄',width:100, rowspan: 2},
        {field: 'is_overweight_cabinet', title: '是否超重柜',width:100, rowspan: 2},
        {field: 'special_notes', title: '特殊注明',width:100, rowspan: 2},
        {field: 'prove_type', title: '产地证类型',width:100, rowspan: 2},
        {field: 'origin_card', title: '产地证',width:100, rowspan: 2,event:'origin_card',templet:function (d){
                return !layui.utils.isEmpty(d.origin_card)?'<a style="color: #0e86fe">点击查看</a>':'无';
            }},
        {field: 'certificate_type', title: '认证书类型',width:100, rowspan: 2},
        {field: 'certificate', title: '认证书',width:100, rowspan: 2,event:'certificate',templet:function (d){
                return  !layui.utils.isEmpty(d.certificate)?'<a style="color: #0e86fe">点击查看</a>':'无';
            }},

        {field: 'inout_type', title: '提放单方式',width:100, rowspan:2},
        {field: 'end_port', title: '目的港',width:100, rowspan: 2},
        {field: 'demo_gx', title: '柜型',align: "center",width:500, colspan:7},
        {field: 'trailer_info', title: '货代名称',width:100, rowspan: 2},
        {field: 'give_time', title: '货好时间',width:150, rowspan: 2},
        {field:'cabinet_time', title: '做箱时间(预计)', width:150, event:'cabinet_time', rowspan: 2,
            templet: function(d){
                if(d.cabinet_time=="" || d.cabinet_time=="0000-00-00" || d.cabinet_time==null){
                    return "";
                }else{
                    return d.cabinet_time;
                }
            }
        },
        {field:'special_status', title: '状态', width:150 ,rowspan: 2},
        {field:'is_closing', title: '完工状态', width:150 ,rowspan: 2},
        {field: 'no_cabinet_cause', title: '未做箱原因',width:250, rowspan: 2},
        {field: 'withhold_time', title: '预提时间',width:150, rowspan: 2},
        {field:'ship_time', title: '开船时间(预计)',event: 'ship_time', width:150, rowspan: 2,
            templet: function(d){
                if(d.ship_time=="" || d.ship_time=="0000-00-00" || d.ship_time == null){
                    return '';
                }else{
                    return d.ship_time;
                }
            }
        },
        {field:'real_ship_time', title: '开船时间(实际)',event: 'real_ship_time', width:150, rowspan: 2,
            templet: function(d){
                if(d.real_ship_time=="" || d.real_ship_time=="0000-00-00" || d.real_ship_time == null){
                    return "";
                }else{
                    return d.real_ship_time;
                }
            }
        },
        {field:'etd_date', title: '到港时间',width:150,event: 'etd_date', rowspan: 2,
            templet: function(d){
                if(d.etd_date=="" || d.etd_date=="0000-00-00" || d.etd_date == null){
                    return "";
                }else{
                    return d.etd_date;
                }
            }
        },
        {field: 'appoint_info', title: '船公司',width:200, rowspan: 2},
        {field: 'so_confirm', title: 'SO是否确认',width:50, rowspan: 2},
        {field: 'no_so_cause', title: 'so未确认原因',width:250, rowspan: 2},
        {field: 'trade_type', title: '贸易术语',width:80, rowspan: 2},
        {field:'lb', title: '产品类别',width:150, rowspan: 2 },
        {field: 'td_time', title: '提单上传时间',width:150, rowspan: 2},
        {field: 'cdz_time', title: '产地证上传时间',width:150, rowspan: 2},
        {field: 'customer_code', title: '客户编码',width:150, rowspan: 2,hide:'true'},
        {field: 'coin_type', title: '币种',width:100, rowspan: 2},
        {field: 'total', title: '金额',width:150, rowspan: 2},
      /*{field: 'load_type', title: '装载类型',width:80},

     {field: 'inout_type', title: '提放单方式',width:100},

     {field: 'coin_type', title: '币种',width:80},
     {field: 'adjust_money', title: '调账金额',width:100},
     {field: 'brand', title: '品牌',width:100},

     {field: 'start_port', title: '起运港',width:100},


     {field: 'inspect_time', title: '验货时间',width:150},
     {field: 'is_appoint', title: '是否指定货代或船公司',width:100, align:'center'},
     {field: 'appoint_info', title: '指定货代或船公司信息',width:200},
     {field: 'customer_start', title: '客户要求开船日期',width:150},
     {field: 'customer_end', title: '客户要求到港日期',width:150},

     {field: 'status', title: '当前步骤',width:150},
     {field: 'prcsFlag', title: '当前状态',width:150},


     {field: 'create_user', title: '制单人',width:100},
     {field: 'create_time', title: '制单时间',width:150},*/
        {fixed: 'right', width: 280, align: 'center', toolbar: '#tab1Bar',rowspan: 2}
    ];
  var demo_gx = [

      { field: "cabinet_type", title: "散货", align: "center"},
      { field: "cabinet1", title: "20GP", align: "center",totalRow: true },
      { field: "cabinet2", title: "40GP", align: "center",totalRow: true },
      { field: "cabinet3", title: "40HQ", align: "center",totalRow: true },
      { field: "cabinet4", title: "45HQ", align: "center",totalRow: true },
      { field: "cabinet5", title: "40NOR", align: "center",totalRow: true},
      { field: "cabinet_count", title: "TEU量", align: "center",totalRow: true},
  ];
  field_data.forEach(function (value, index, array) {
      if (value['field'] && value['type'] == ''){
          field_all.forEach(function (v,i,a) {
               if (field_all[i]['field'] == value['field']){
                   field_all[i]['hide'] = 'true';
               }
               if (field_all[i]['field'] == 'demo_gx' && field_all[i]['field'] == value['field']){
                   demo_gx = [];
               }
          })
      }
  })
    console.log(demo_gx);
  var obj = {
        'coin_unit':{"美元":"usd","人民币":"rmb","欧元":"eur","日币":"jpy"},
        'provebill_cabinet':[
            field_all,
            demo_gx
        ],

        'pay_type':{
            "DP 60天":"DP 60Days","DP 90天":"DP 90Days","DP 120天":"DP 120Days","TT 30天":"TT 30Days","TT 40天":"TT 40Days","TT 45天":"TT 45Days",
            "TT 55天":"TT 55Days","TT 60天":"TT 60Days","TT 90天":"TT 90Days","TT 120天":"TT 120Days","见提单付款-40天":"40Days from BL","见提单付款-45天":"45Days from BL",
            "见提单付款-60天":"60Days from BL","DP30天":"DP 30Days","TT100天":"TT 100Days","即期信用证":"LC at sight","远期信用证-15天":"LC within 15Days",
            "远期信用证-30天":"LC within 30Days","远期信用证-45天":"LC within 45Days","远期信用证-90天":"LC within 90Days","远期信用证-120天":"LC within 120Days"
        },

  };

  exports('provebill_cabinet_cols', obj);
});   
layui.define([], function (exports) {
    var obj = {

        data: {
            dataInfo: {}
        },

        initData: function (store) {
            var that=this;
            var sel_vals = [];
            layui.form.on('select(test)', function (data) {
                var mb_name = data.value;
                var value = store["data"]["arr_hb"][mb_name];//获取type

                if (value == 6) {
                    $('#content').addClass('layui-show');
                    $(".data7").attr("style", "display:none;");

                    $(".data9").attr("style", "display:none;");

                    $(".data11").attr("style", "display:none;");

                    $(".data13").attr("style", "display:none;");

                    $(".data15").attr("style", "display:none;");

                    $(".data17").attr("style", "display:none;");
                    $(".data19").attr("style", "display:none;");
                    $(".data21").attr("style", "display:none;");

                    $("input[name='data8']").attr("lay-verify", "");
                    $("input[name='data10']").attr("lay-verify", "");
                    $("input[name='data12']").attr("lay-verify", "");
                    $("input[name='data14']").attr("lay-verify", "");
                    $("input[name='data16']").attr("lay-verify", "");
                    $("input[name='data18']").attr("lay-verify", "");
                    $("input[name='data20']").attr("lay-verify", "");
                    $("input[name='data22']").attr("lay-verify", "");


                } else if (value == 8) {
                    $('#content').addClass('layui-show');
                    $(".data7").attr("style", "display:block;");
                    $(".data9").attr("style", "display:none;");
                    $(".data11").attr("style", "display:none;");
                    $(".data13").attr("style", "display:none;");
                    $(".data15").attr("style", "display:none;");
                    $(".data17").attr("style", "display:none;");
                    $(".data19").attr("style", "display:none;");
                    $(".data21").attr("style", "display:none;");
                    $("input[name='data8']").attr("lay-verify", "required");
                    $("input[name='data10']").attr("lay-verify", "");
                    $("input[name='data12']").attr("lay-verify", "");
                    $("input[name='data14']").attr("lay-verify", "");
                    $("input[name='data16']").attr("lay-verify", "");
                    $("input[name='data18']").attr("lay-verify", "");
                    $("input[name='data20']").attr("lay-verify", "");
                    $("input[name='data22']").attr("lay-verify", "");

                } else if (value == 10) {
                    $('#content').addClass('layui-show');
                    $(".data7").attr("style", "display:block;");
                    $(".data9").attr("style", "display:block;");
                    $(".data11").attr("style", "display:none;");
                    $(".data13").attr("style", "display:none;");
                    $(".data15").attr("style", "display:none;");
                    $(".data17").attr("style", "display:none;");
                    $(".data19").attr("style", "display:none;");
                    $(".data21").attr("style", "display:none;");
                    $("input[name='data8']").attr("lay-verify", "required");
                    $("input[name='data10']").attr("lay-verify", "required");
                    $("input[name='data12']").attr("lay-verify", "");
                    $("input[name='data14']").attr("lay-verify", "");
                    $("input[name='data16']").attr("lay-verify", "");
                    $("input[name='data18']").attr("lay-verify", "");
                    $("input[name='data20']").attr("lay-verify", "");
                    $("input[name='data22']").attr("lay-verify", "");

                } else if (value == 12) {
                    $('#content').addClass('layui-show');
                    $(".data7").attr("style", "display:block;");
                    $(".data9").attr("style", "display:block;");
                    $(".data11").attr("style", "display:block;");
                    $(".data13").attr("style", "display:none;");
                    $(".data15").attr("style", "display:none;");
                    $(".data17").attr("style", "display:none;");
                    $(".data19").attr("style", "display:none;");
                    $(".data21").attr("style", "display:none;");
                    $("input[name='data8']").attr("lay-verify", "required");
                    $("input[name='data10']").attr("lay-verify", "required");
                    $("input[name='data12']").attr("lay-verify", "required");
                    $("input[name='data14']").attr("lay-verify", "");
                    $("input[name='data16']").attr("lay-verify", "");
                    $("input[name='data18']").attr("lay-verify", "");
                    $("input[name='data20']").attr("lay-verify", "");
                    $("input[name='data22']").attr("lay-verify", "");

                } else if (value == 14) {
                    $('#content').addClass('layui-show');
                    $(".data7").attr("style", "display:block;");
                    $(".data9").attr("style", "display:block;");
                    $(".data11").attr("style", "display:block;");
                    $(".data13").attr("style", "display:block;");
                    $(".data15").attr("style", "display:none;");
                    $(".data17").attr("style", "display:none;");
                    $(".data19").attr("style", "display:none;");
                    $(".data21").attr("style", "display:none;");
                    $("input[name='data8']").attr("lay-verify", "required");
                    $("input[name='data10']").attr("lay-verify", "required");
                    $("input[name='data12']").attr("lay-verify", "required");
                    $("input[name='data14']").attr("lay-verify", "required");
                    $("input[name='data16']").attr("lay-verify", "");
                    $("input[name='data18']").attr("lay-verify", "");
                    $("input[name='data20']").attr("lay-verify", "");
                    $("input[name='data22']").attr("lay-verify", "");

                } else if (value == 16) {
                    $('#content').addClass('layui-show');
                    $(".data7").attr("style", "display:block;");
                    $(".data9").attr("style", "display:block;");
                    $(".data11").attr("style", "display:block;");
                    $(".data13").attr("style", "display:block;");
                    $(".data15").attr("style", "display:block;");
                    $(".data17").attr("style", "display:none;");
                    $(".data19").attr("style", "display:none;");
                    $(".data21").attr("style", "display:none;");
                    $("input[name='data8']").attr("lay-verify", "required");
                    $("input[name='data10']").attr("lay-verify", "required");
                    $("input[name='data12']").attr("lay-verify", "required");
                    $("input[name='data14']").attr("lay-verify", "required");
                    $("input[name='data16']").attr("lay-verify", "required");
                    $("input[name='data18']").attr("lay-verify", "");
                    $("input[name='data20']").attr("lay-verify", "");
                    $("input[name='data22']").attr("lay-verify", "");


                } else if (value == 18) {
                    $('#content').addClass('layui-show');
                    $(".data7").attr("style", "display:block;");
                    $(".data9").attr("style", "display:block;");
                    $(".data11").attr("style", "display:block;");
                    $(".data13").attr("style", "display:block;");
                    $(".data15").attr("style", "display:block;");
                    $(".data17").attr("style", "display:block;");
                    $(".data19").attr("style", "display:none;");
                    $(".data21").attr("style", "display:none;");
                    $("input[name='data8']").attr("lay-verify", "required");
                    $("input[name='data10']").attr("lay-verify", "required");
                    $("input[name='data12']").attr("lay-verify", "required");
                    $("input[name='data14']").attr("lay-verify", "required");
                    $("input[name='data16']").attr("lay-verify", "required");
                    $("input[name='data18']").attr("lay-verify", "required");
                    $("input[name='data20']").attr("lay-verify", "");
                    $("input[name='data22']").attr("lay-verify", "");

                }else if (value == 22) {
                    $('#content').addClass('layui-show');
                    $(".data7").attr("style", "display:block;");
                    $(".data9").attr("style", "display:block;");
                    $(".data11").attr("style", "display:block;");
                    $(".data13").attr("style", "display:block;");
                    $(".data15").attr("style", "display:block;");
                    $(".data17").attr("style", "display:block;");
                    $(".data19").attr("style", "display:block;");
                    $(".data21").attr("style", "display:block;");
                    $("input[name='data8']").attr("lay-verify", "required");
                    $("input[name='data10']").attr("lay-verify", "required");
                    $("input[name='data12']").attr("lay-verify", "required");
                    $("input[name='data14']").attr("lay-verify", "required");
                    $("input[name='data16']").attr("lay-verify", "required");
                    $("input[name='data18']").attr("lay-verify", "required");
                    $("input[name='data20']").attr("lay-verify", "required");
                   // $("input[name='data22']").attr("lay-verify", "required");


                }


            })

            /*==============================10============================*/

            for (let i = 1, len = 22; i <= len; i += 2) {
                var ins = layui.selectInput.render({
                    elem: '#data' + i,
                    name: 'data' + i,
                    hasSelectIcon: true,
                    initValue: '',
                    placeholder: '请输入名称',
                    data: layui.selectData.data,
                    invisibleMode: true,
                    remoteSearch: false,
                });
                ins.on('itemSelect(data' + i + ')', function (data) {console.log(i)
                    var str = $("input[name='data" + (i) + "']").val();
                    var value = data.data;
                    if(str=='SIZE(尺寸,手动填写,米数取实际)'){
                        str='SIZE(尺寸,手动填写,米数取实际)';
                    }
                    if (sel_vals.indexOf(str) > -1) {
                        layer.msg('该元素已存在,请重选');
                        return;
                    }
                    var obj = $("input[name='data" + (i + 1) + "']");console.log(obj)
                    that.sel_str(value, obj,store);
                    that.sel_push(sel_vals);
                });

            }


            layui.form.on('submit(subBtn)', function (data) {
                var obj = data.field;
                for (let key in obj) {
                    if (obj[key] in layui.selectData.kv) {
                        obj[key] = layui.selectData.kv[obj[key]];
                    }
                }
                layer.confirm('确定?', {icon: 3, title: '提示'}, function (index) {
                    $.ajax({
                        type: 'post',
                        url: '/general/erp4/controller/mt/mbConfig/up_order_k.php',
                        data: {
                            data: {data: obj, type: store["params"]['type']}
                        },
                        success: function (res) {
                            if (res.code == 200) {
                                if (store["params"]['type'] == 0) {
                                    parent.layui.$("#mb_name").val(res.mb_name);
                                    parent.layui.$("#mb_bz").val(res.mb_bz);
                                } else {
                                    parent.layui.$("#mb_name2").val(res.mb_name);
                                    parent.layui.$("#mb_bz2").val(res.mb_bz);
                                }
                                layer.msg('成功');
                            } else {
                                layer.msg('失败');
                            }
                            window.parent.layer.closeAll();//关闭当前页面弹窗
                        },
                        dataType: 'json',
                        async: false
                    })
                    layer.close(index);
                })
                return false;
            })




        },
        sel_push: function (vals) {
            $("#content input ").each(function () {
                if($(this).val()=='SIZE(尺寸,手动填写,米数取实际)'){
                    vals.push('SIZE(尺寸,手动填写,米数取实际)');
                }else{
                    vals.push($(this).val());
                }
            })
       },
        sel_str : function (val, selector,store) {console.log(val);
            switch (val) {
                case 'Code:'://卷号/毛重
                    selector.val("codemaozhong");
                    layui.selectData.attr(selector);
                    break;
                case 'ChangDu:'://米数-2000
                    selector.val("ichangduTS");
                    layui.selectData.attr(selector);
                    break;
                case 'Manufacturing Code'://一维码
                    selector.val("cSn");
                    layui.selectData.attr(selector);
                    break;
                case 'CBarCode:'://二维码
                    selector.val("CBarCode");
                    layui.selectData.attr(selector);
                    break;
                case 'Customer:':
                    selector.val(store.data['customerCode']);
                    layui.selectData.removeAttr(selector)
                    break;
                case 'PO NO:'://客户订单号
                    selector.val(store.data['po']);
                    layui.selectData.removeAttr(selector);
                    selector.attr("lay-verify", "");
                    break;
                case 'INVOICE NO:'://生产单号
                    selector.val(store.data['fullNum']);
                    layui.selectData.removeAttr(selector)
                    break;
                case 'ROLL NO:'://卷号
                    selector.val("ROLL NO");
                    layui.selectData.attr(selector);
                    break;
                case 'JUMBO ROLL:'://卷号
                    selector.val("ROLL NO");
                    layui.selectData.attr(selector);
                    break;
                case 'GROSS WEIGHT:'://毛重
                    selector.val("iMaoZhong");
                    layui.selectData.attr(selector);
                    break;
                case 'NET WEIGHT:'://净重
                    selector.val("iJingZhong");
                    layui.selectData.attr(selector);
                    break;
                case 'LOT NO:'://生产日期
                    selector.val("cBatch");
                    layui.selectData.attr(selector);
                    break;
                case 'Manufacturing date:'://生产日期
                    selector.val("cBatch");
                    break;
                case 'MADE IN CHINA:'://
                    selector.val("MADE IN CHINA");
                    break;
                case 'Manufacturing Code:'://RollNos(szp专用)
                    selector.val("RollNos");
                    layui.selectData.attr(selector);
                    break;
                case 'cQuanXuHao:'://
                    selector.val("cQuanXuHao");
                    layui.selectData.attr(selector);
                    break;
                default:
                    selector.val("");
                    layui.selectData.removeAttr(selector)
            }

        },


    };


    exports('mt_select', obj);
});    
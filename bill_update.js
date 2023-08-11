layui.define(['utils','laydate','order_cols','deliver_cols'],function(exports){
    var obj = {

        initBillUpdate: function(store,tab1){

            var where=this.getUserConfig(store);
            if(where){
                layui.form.val("form-select",where);//渲染数据
                this.reloadTab1(where,tab1);
            }


        },


        getUserConfig:function(store){
            var retInfo= layui.utils.post("get_user_config",{key:"dev_dclb"});
            //console.log(retInfo);
            return retInfo;
        },

        reloadTab1:function(where,tab1){
            tab1.reload({
                where:where
                ,page: { curr: 1}
            })
        },
        //保存搜索数据
        save:function (){
        res = layui.form.val('form-select');
        //console.log(res);
        var jsonStr = JSON.stringify(res);
        jsonStr = encodeURI(jsonStr);
        layui.utils.post("set_user_config",{key:"dev_dclb",val:jsonStr});
        /*$.ajax({
            type:'post'
            ,url:'/general/erp4/controller/setSelIni_k.php'
            ,data:{
                key:'dev_dclb'
                ,val:jsonStr
            }
            ,success:function(res){
                if(res.code == 200){
                    // layer.msg('成功!');
                }
            }
            ,dataType:'json'
            ,async:false

        })*/
    }




    };

    exports('bill_update', obj);
});
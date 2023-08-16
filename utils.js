layui.define(['laytpl','upload','url_format','log_cols','table'],function(exports){ 
  var obj = {
    
    urlData:{
        'provebill':"/general/erp4/controller/provebill/index.php",
        'orderlist':'/general/erp4/controller/order/run200/inpList2.php',
        'order_change':'/general/erp4/controller/orderChange/index.php',
        'manual_fun':"/general/erp4/controller/provebill/manual.php",
    },
    post: function(opt,data,module){
        var that=this;
        var retInfo="";
        module=module?module:"provebill"; 
        var url=that.urlData[module];
        layui.$.ajax({
            type: 'post', 
            url: url, 
            data: {
                opt:opt,
                data:data
            }, 
            success: function (res) {
                retInfo=res;
            } ,
            error: function (XMLHttpRequest, textStatus, errorThrown) {
                retInfo={status:500,msg:"network error!",data:{}};
            }, 
            dataType: 'json',
            async:false
        })
       return retInfo;
    }, 
    get:function(opt,data,module){
        var that=this;
        var retInfo="";
        module=module?module:"provebill";
        var url=that.urlData[module];
        layui.$.ajax({
            type: 'get', 
            url: url, 
            data: {
                opt:opt,
                data:data
            }, 
            success: function (res) {
                retInfo=res;
            } ,
            error: function (XMLHttpRequest, textStatus, errorThrown) {
                retInfo={status:500,msg:"network error!",data:{}};
            }, 
            dataType: 'json',
            async:false
        })
       return retInfo;
    },
    
    ajaxGet:function(url,data,dataType){
        var that=this;
        var retInfo="";
        dataType=dataType?dataType:"json";
        layui.$.ajax({
            type: 'get', 
            url: url, 
            data: data, 
            success: function (res) {
                retInfo=res;
            } ,
            error: function (XMLHttpRequest, textStatus, errorThrown) {
                retInfo={status:500,msg:"network error!",data:{}};
            }, 
            dataType: dataType,
            async:false
        })
       return retInfo;
    },
    
    ajaxPost:function(url,data,dataType){
        var that=this;
        var retInfo="";
        dataType=dataType?dataType:"json";
        layui.$.ajax({
            type: 'post', 
            url: url, 
            data: data, 
            success: function (res) {
                retInfo=res;
            } ,
            error: function (XMLHttpRequest, textStatus, errorThrown) {
                retInfo={status:500,msg:"network error!",data:{}};
            }, 
            dataType: dataType,
            async:false
        })
       return retInfo;
    },
    
    
    getTemp:function(url){return this.ajaxGet(url,{},"html");},
    
    //???????????
    tableSum:function(tableId,sumCols){
        //sumCols={"total_1":0,"num":0,"tuopan_num":0,"tuopan_weight":0,"mz_1":0,"jz_1":0,"tj_1":0};
        if(this.isEmpty(sumCols)||this.isEmpty(tableId)) return false;
        var tableData=layui.table.cache[tableId];//???????????????
        for(var index in tableData){
           var item= tableData[index];
           for(var i in sumCols){
               if(this.isEmpty(item[i])) continue;
               sumCols[i]+=parseFloat(item[i]);
           }
        }    
        
        for(var index in sumCols){
            layui.$("div[lay-id="+tableId+"] .layui-table-total").find("td[data-field="+index+"] div").html(sumCols[index]);
        }
    },

    
    sqlManage:function(opt,data){
        data["unique_code"]=opt;
        return this.post('sql_manage_api',data);
    },
    
    /**
     * ????
     * @param {type} url ????¡¤??
     * @param {type} title  ???????
     * @param {type} width  ???????
     * @param {type} height ??????
     * @returns {unresolved} ?????????
     */
    popup:function(option){
        if(!option['url']) return false;
        option['title']=option['title']?option['title']:"?????";      
        option['width']=option['width']?option['width']:"500px";        
        option['height']=option['height']?option['height']:"300px";
        option['data']=option['data']?option['data']:{};
        var urlFmt = new layui.url_format.URL(option['url']);
        urlFmt.setParams(option['data']);
        var url=urlFmt.url("layui");
        return layer.open({
            title: option['title'], 
            type:2,
            content:option['url'],
            area:[option['width'],option['height']],
            shade:0.6,  //???????
            shadeClose :true, //?????????
            maxmin:true
        });
    },

      /**
       * ????
       * @param {type} url ????¡¤??
       * @param {type} title  ???????
       * @param {type} width  ???????
       * @param {type} height ??????
       * @returns {unresolved} ?????????
       */
      popup_fun: function (option,fun) {
          if (!option['url']) return false;
          option['title'] = option['title'] ? option['title'] : "?????";
          option['width'] = option['width'] ? option['width'] : "500px";
          option['height'] = option['height'] ? option['height'] : "300px";
          option['data'] = option['data'] ? option['data'] : {};
          var urlFmt = new layui.url_format.URL(option['url']);
          urlFmt.setParams(option['data']);
          var url = urlFmt.url("layui");
          return layer.open({
              title: option['title'],
              type: 2,
              content: option['url'],
              area: [option['width'], option['height']],
              shade: 0.6,  //???????
              shadeClose: true, //?????????
              maxmin: true,
              end:arguments[1]?arguments[1]:''
          });
      },
    
    showForm:function(option){
        if(!option['content']) return false;
        option['title']=option['title']?option['title']:"?????";      
        option['width']=option['width']?option['width']:"500px";        
        option['height']=option['height']?option['height']:"300px";
        option['type']=option['type']?option['type']:1;
        option['data']=option['data']?option['data']:{};
        return layer.open({
            title: option['title'], 
            type:option['type'],
            content:option['content'],
            area:[option['width'],option['height']],
            shade:0.6,  //???????
            shadeClose :true, //?????????
            maxmin:true
        });
    },
    
    showImg:function(img){
        layer.open({
            type: 2,//1:????  2:url  
            title: '????',
            shadeClose: true,
            shade: 0.6,//?????
            area: ['90%', '90%'],
            anim:1,//????  -1?????
            content: img
        }); 
    },
    
    /**
     * ??????????
     */
    tempPopup:function(url,data,params){
        var that=this;
        if(this.isEmpty(url)) return false;
        var data=!this.isEmpty(data)?data:{};
        var tpl=this.getTemp(url); 
        layui.laytpl(tpl).render(data, function(html){
            that.showForm({"title":params["title"],"content":html,"width":params["width"],"height":params["height"] });//??????????                
        });
        return true;
    },

    /**
     * ?????????
     * @param {type} element
     * @returns {unresolved}
     */
    initUpload:function(element,uploadStr,callback,is_more){
        var that=this;
        uploadStr=!this.isEmpty(uploadStr)?uploadStr:"";
        that.initUploadData(element,uploadStr,is_more);
        //???????????
        return  layui.upload.render({
            elem: '#'+element,
            url: '/general/erp4/controller/upload_k.php',
            data: {},
            multiple: true,
            accept:'file',
            // ,exts:'pdf'
            done: function(res){
             
                if(callback){
                    eval(callback+"('"+layui.utils.jsonEncode(res)+"')");
                }
                
                var dataList=layui.$('#'+element+"_box").find("input[name="+element+"]").attr("value");

                //that.initUploadData(element,layui.utils.jsonEncode(res),is_more);
                that.initUpload(element,layui.utils.jsonEncode(res),callback,is_more);
            }
        });
    },
    
    initUploadTable:function(element,uploadList){
        if(!this.isEmpty(uploadList)){
            for(var index in uploadList){
                var item=uploadList[index];
                uploadList[index]['box']=element;
            }
        }
        
        //?????????????
        layui.table.render({elem: '#'+element+'_table', data:uploadList ,cols:[[
            {field:'ID', title: '????ID', width:400},
            {field:'box', title: '????',hide:true},
            {field:'NAME', title:'????????', width:400, templet: function(d){
                if(typeof(d.NAME)!="undefined"){
                    return '<span onclick=\"layui.utils.viewAtt1('+'\''+d.ID+'\''+','+'\''+d.NAME+'\''+')\" >'+d.NAME+'</span>'; 
                }
                return '<span></span>'; 
            }, unresize: true ,event:'viewAtt'},
            {fixed: 'right', width: 100, align:'center', toolbar: '#tab1Upload'}
        
        ]] ,toolbar: '<div>????§Ò?</div>'});        
    },
    
    viewAtt1:function(ID,NAME){
        NAME = this.getUrlEncode(NAME);
        var lay1 = layer.open({
            title: '??????'
            ,type:2
            ,content:'/general/erp4/view/common/viewAtt.php?attId='+ID+'&attName='+NAME
            ,area:['650px','300px']
            ,shade:0.6  //???????
            ,shadeClose :true //?????????
        });
    },
    
    delUpload:function(obj,element){
        var index = layui.$(obj).parents("tr").attr("data-index");
        var uploadInfo = layui.$("#"+element+"_box").find("input[name="+element+"]").attr("value");
        var uploadList=[];
        if(uploadInfo){
            uploadInfo=JSON.parse(uploadInfo);
            if(Array.isArray(uploadInfo)){
                uploadList=uploadInfo;
            }else{
                uploadList.push(uploadInfo);
            }
        }
        delete uploadList[index];
  
        uploadList=this.removeNull(uploadList);
        layui.$("#"+element+"_box").find("input[name="+element+"]").attr("value",this.jsonEncode(uploadList));
        layui.$("#"+element+"_box").find("input[name="+element+"]").val(this.jsonEncode(uploadList));
        
        this.initUploadTable(element,uploadList);
    },
    
    /**
     * ????????????
     * @param {type} element
     * @param {type} uploadStr
     * @returns {undefined}
     */
    initUploadData:function(element,uploadStr,is_more){
        var that=this;
        var data={
            'name':element,
            'data':!that.isEmpty(uploadStr)?JSON.parse(uploadStr):{ID:"",NAME:"",status:"1"},
            'json_str':uploadStr,
            'is_more':is_more?1:0
        };
        var uploadList=[];
        if(is_more&&!that.isEmpty(uploadStr)){
            var uploadItem=JSON.parse(uploadStr)
            var uploadList=uploadItem;
            if(!Array.isArray(uploadItem)){
                dataList=layui.$('#'+element+"_box").find("input[name="+element+"]").attr("value");
                dataList=dataList?JSON.parse(dataList):[];
                if(!Array.isArray(dataList)){
                    uploadList.push(dataList)
                }else{
                    uploadList=dataList;
                }
                uploadList.push(uploadItem);
            }
            data['json_str']=layui.utils.jsonEncode(uploadList);
        }
        that.initTpl("upload_control_tmp","#"+element+"_control_box",data);//??????
        that.initTpl("upload_tmp","#"+element+"_box",data);//???????
        
        if(is_more){
            that.initUploadTable(element,uploadList)
        }
        //???????
        if(uploadStr){
            layui.$("#"+element+"_name_input").click(function(){
                that.viewAtt(uploadStr);
            })             
        }
    },
    

    /**
     * ??????
     * @param {type} str
     * @param {type} ary
     * @returns {Boolean}
     */
    viewAtt:function(uploadStr) {
        var data=JSON.parse(uploadStr);console.log(data);
        data.NAME = this.getUrlEncode(data.NAME);
        var lay1 = layer.open({
            title: '??????'
            ,type:2
            ,content:'/general/erp4/view/common/viewAtt.php?attId='+data.ID+'&attName='+data.NAME
            ,area:['650px','300px']
            ,shade:0.6  //???????
            ,shadeClose :true //?????????
        });
    },
      /**
       * ??????
       * @param {type} str
       * @param {type} ary
       * @returns {Boolean}
       */
      viewAtt2:function(uploadStr) {
          let IDS= []; var NAMES=[];
          var data=JSON.parse(uploadStr);
          for(var p in data){//????json??????????§Õp???????0,1
              IDS.push(data[p].ID);
              NAMES.push(this.getUrlEncode(data[p].NAME));
          }
          var lay1 = layer.open({
              title: '??????'
              ,type:2
              ,content:'/general/erp4/view/common/viewAtt2.php?attIds='+IDS.join('/')+'&attNames='+NAMES.join('/')
              ,area:['650px','300px']
              ,shade:0.6  //???????
              ,shadeClose :true //?????????
          });
      },
      viewAtt3:function(uploadStr,uploadStr2) {
          let IDS= []; var NAMES=[];
          var data=JSON.parse(uploadStr);
          for(var p in data){//????json??????????§Õp???????0,1
              IDS.push(data[p].ID);
              NAMES.push(this.getUrlEncode(data[p].NAME));
          }
          var data2=JSON.parse(uploadStr2);
          for(var p in data2){//????json??????????§Õp???????0,1
              IDS.push(data2[p].ID);
              NAMES.push(this.getUrlEncode(data2[p].NAME));
          }
          var lay1 = layer.open({
              title: '??????'
              ,type:2
              ,content:'/general/erp4/view/common/viewAtt2.php?attIds='+IDS.join('/')+'&attNames='+NAMES.join('/')
              ,area:['650px','300px']
              ,shade:0.6  //???????
              ,shadeClose :true //?????????
          });
      },
    
    getUrlEncode:function(url){
        url=url.replace(/\&/g, "%26");
        url=url.replace(/\#/g, "%23");
        url=url.replace(/\?/g, "%3F");
        return url;
    },
    
    jsonEncode:function(jsonArray){
        return JSON.stringify(jsonArray).replace(/\\n/g, "\\\\n")
                .replace(/\\"/g, '\\\\"')
                .replace(/\\&/g, "\\\\&")
                .replace(/\\r/g, "\\\\r")
                .replace(/\\t/g, "\\\\t")
                .replace(/\\b/g, "\\\\b");
    },
    
    getJsonStr(jsonStr){
        return jsonStr.replace(/\\n/g, "\\\\n")
            .replace(/\\"/g, '\\\\"')
            .replace(/\\&/g, "\\\\&")
            .replace(/\\r/g, "\\\\r")
            .replace(/\\t/g, "\\\\t")
            .replace(/\\b/g, "\\\\b");
    },
    
    isEmpty:function(obj){
        return (typeof(obj)== 'undefined' ||obj==null||obj == ""||obj == 0||JSON.stringify(obj)=="[]"||JSON.stringify(obj)=="{}");
    },
   
    /**
     * ????????????
     * @param {type} tpl
     * @param {type} view
     * @param {type} data
     * @returns {undefined}
     */
    initTpl:function(tpl,view,data,append){
        var getTpl = document.getElementById(tpl).innerHTML;
        
        layui.laytpl(getTpl).render(data, function(html){
            if(append){
                layui.$(view).append(html);  
            }else{
                layui.$(view).html(html);
            }
        });
    },
    
    renderTpl:function(tpl,view,data,append){
        //var getTpl = document.getElementById(tpl).innerHTML;
        layui.laytpl(tpl).render(data, function(html){
            if(append){ 
                layui.$("#"+view).append(html);  
            }else{
                layui.$("#"+view).html(html);
            }
        });
    },
    
    /**
     * ?§Ø??????????????????
     * @param {type} str
     * @param {type} ary
     * @returns {Boolean}
     */
    inArray:function(str,ary,fld){
        if(fld){
            if(!Array.isArray(ary)) return false;
            for(var index in ary) {
                if(str==ary[index][fld]) return true;
            }
            return false;
        }else{
            if(!Array.isArray(ary)) return false;
            if(ary.indexOf(str)>-1) return true;  
        }
    },
    
    uniArray:function(ary){
        var retArray=[];
        for(var index in ary){
            if(retArray.indexOf(ary[index])==-1){
                retArray.push(ary[index]);
            }
        }
        return retArray;
    },
    
    /**
     * ??????????????????????????ï‚
     * @param {type} data
     * @returns {undefined}
     */
    formDataArray:function(data){
        if(!data) return false;
        for(var index in data){
            var temp=index.replace(/\[([^"^\]^\[]*)\]/g, "|$1");//?????????
            //var temp=temp.replace("][", "|");
            if(index!=temp){
                var itemArray=temp.split("|");
                if(typeof(data[itemArray[0]]=="undefined")&&!data[itemArray[0]]){
                    data[itemArray[0]]=[];  
                }
                if(typeof(data[itemArray[0]][itemArray[1]])=="undefined"&&!data[itemArray[0]][itemArray[1]]){
                    data[itemArray[0]][itemArray[1]]={};
                }
                data[itemArray[0]][itemArray[1]][itemArray[2]]=data[index];
                delete data[index];
            }
        }
        
        return data;
    },
    
    /**
     * ?????§Ö??§Ò?
     * @param {type} res
     * @param {type} arrayList
     * @returns {unresolved}
     */
    getCheckList:function(res,arrayList){
        if(typeof(res.data)!='undefined'&&res.data){
            for(var index in res.data){ 
                if(arrayList.indexOf(res.data[index].id)>-1){
                    res.data[index]['LAY_CHECKED']=true;
                }
            }
        } 
        return res;
    },
    
    /**
     * ????????
     * @param {type} params
     * @returns {Array|Boolean}
     */
    removeNull:function(params){
        if(!Array.isArray(params)) return [];
        var retInfo=[];
        for(var i in params){
            if(params[i]){
                retInfo.push(params[i]);
            }
        }
        return retInfo;
    },
    
    arrToStr(params){
        var retInfo="";
        var retArray=[];
        if(typeof(params["data_list"])=="undefined"||!Array.isArray(params["data_list"])) return retInfo;
        for(var index in params["data_list"]){
            var item=params["data_list"][index];
            if(typeof(params["key"])=="undefined"){
                retArray.push(item);
            }else{
                retArray.push(item[params["key"]]);
            } 
        }
        retInfo=retArray.join(",");
        return retInfo;
    },
    
    getArrayCols(params){
        var retArray=[];
        if(typeof(params["data_list"])=="undefined"||!Array.isArray(params["data_list"])) return retArray;
        for(var index in params["data_list"]){
            var item=params["data_list"][index];
            if(typeof(params["key"])=="undefined"){
                retArray.push(item);
            }else{
                retArray.push(item[params["key"]]);
            } 
        }
        return retArray;
    },
    
    /**
     * ???????
     * @param {type} data1
     * @param {type} data2
     * @param {type} type_flag
     * @returns {Boolean}
     */
    setLog:function(data1,data2,type_flag){
        if(!data1||!data2||!type_flag) return false;
        var typeFlagName=layui.log_cols[type_flag].name;
        if(!typeFlagName) return false;
        var that=this;
        var changeInfo={"data1":{},data2:{}};
        for(var index in data1){     
            if(data1[index]!=data2[index]){
                changeInfo.data1[index]=data1[index];
                changeInfo.data2[index]=data2[index];
            }
        }

        var userInfo=that.post('get_user_info',{});//????????????
        
        if(typeof(userInfo['user_id'])=="undefined") return false;

        if(that.jsonEncode(changeInfo.data1)!="{}"&&that.jsonEncode(changeInfo.data2)!="{}"){
            var logData={
                unique_code:"update_log_list",
                name:typeFlagName,
                type_flag:type_flag,
                case_id:data1.id,
                data1:that.jsonEncode(changeInfo.data1),
                data2:that.jsonEncode(changeInfo.data2),
                creator_id:userInfo.user_id,
                creator_name:userInfo.user_name
            };
            that.post('sql_manage_api',logData);
        }
        
        return true;
    },
    
    getLog:function(type_flag,case_id,list_box){
        var that=this;
        var tab1 = layui.table.render({elem:list_box, url:  that.urlData['provebill'] , cols:layui.log_cols['table_cols'],
            parseData: function(res){ 
                //???????§Ø?
                if(typeof(res.data)!='undefined'&&res.data){
                    for(var index in res.data){ 
                        if(res.data[index]["data1"]&&res.data[index]["data2"]){
                            res.data[index]["data1"]=JSON.parse(res.data[index]["data1"]);
                            res.data[index]["data2"]=JSON.parse(res.data[index]["data2"]);
                        }
                    }
                } 
                return {"code": res.code,"msg": res.msg, "count": res.count, "data": res.data };
            },
            where:{opt:"sql_manage_api","unique_code":"get_log_list",type_flag:type_flag,case_id:case_id,order_desc:"id"}, 
            page: true, toolbar: false, text: { none: '???????????' }
        });
        return tab1;
    },
    
    getArrayByPath:function(data,path){
        if(!Array.isArray(path)) return false;
        var retInfo=data;
        for(var index in path){
           retInfo = retInfo[path[index]];
        }
        return retInfo;
    },
    
    getArrayEqual:function(arr1,arr2){
        var newArr = [];
        if(!Array.isArray(arr1)||!Array.isArray(arr2)) return false;
        for(var i in arr1){
            if(this.inArray(arr1[i],arr2)){
                newArr.push(arr1[i]);
            }
        }
        return newArr;
    },
    
    setColsAttr:function(cols,data){
        for(var index in cols[0]){
            var field = cols[0][index]["field"];
            if(!this.isEmpty(data[field])){
                //cols[0][index][data[field]["key"]]
                for(var i in data[field]){
                    cols[0][index][i]=data[field][i];
                }
            }
        }
        return cols;
    },
    
    getArrayKey:function(arr1){
        var newArr = [];
        //if(!Array.isArray(arr1)) return false;
        for(var i in arr1){
            newArr.push(i);
        }
        return newArr;
    },
    
    decodeArray:function(data){
        //if(!Array.isArray(data)) return false;
        var ret={};
        for(var i in data) ret[i]=decodeURI(data[i]);
        return ret;
    },
    

    //????????
    objClone:function(target) {
        // ???????????
        let result;
        // ??????????????????????????
        if (typeof target === 'object') {
            // ??????????????
            if (Array.isArray(target)) {
                result = []; // ??result??????????ï…??????§Ò???
                for (let i in target) {
                    // ??????????§Ö?????
                    result.push(this.objClone(target[i]))
                }
             // ?§Ø????????????null???????????null
            } else if (target === null) {
                result = null;
             // ?§Ø???????????????RegExp????????????? 
            } else if (target.constructor === RegExp) {
                result = target;
            } else {
                // ????????????????for in???????Ô§???????????
                result = {};
                for (let i in target) {
                    result[i] = this.objClone(target[i]);
                }
            }
        } else {
            // ??????????????????????????????????????
            result = target;
        }
        // ??????????
        return result;
    },

      /**
       * ???????????
       * @param arr
       * @param val
       * @returns {boolean}
       */
       removeByValue:function(arr, val) {
        var found = false;
        for(var i=0; i<arr.length; i++) {
            if(arr[i] === val) {
                arr.splice(i, 1);
                break;
            }
        }
        return found;
         },
        //?????????§Ò????link???
        parseURL:function(str){
            //?????????????????????
            if(typeof str!='string'){
                return {}
            }
            var paramObj = {},//????????????????
                _str = str.substr(str.indexOf('?')+1);
            //????????
            paraArr = decodeURI(_str).split("&");
            var tmp , key, value, newValue;
            for(var i=0, len=paraArr.length; i<len;i++){
                tmp = paraArr[i].split("=");
                key = tmp[0];
                value = tmp[1]||true;
                //????????'100'=>100
                if(typeof value === 'string' && isNaN(Number(value)) === false){
                    value = Number(value);
                }
                //???key??§Ô????(??????0 ????false)
                if(typeof paramObj[key] === "undefined"){
                    paramObj[key] = value;
                }else{
                    newValue = Array.isArray(paramObj[key]) ? paramObj[key] : [paramObj[key]];
                    newValue.push(value);
                    paramObj[key] = newValue;
                }
            }
             return paramObj;
         },

  };
 
  exports('utils', obj);
});    
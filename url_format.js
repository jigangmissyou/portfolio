layui.define(function(exports){ 
    var LG = (function (lg) {
        var objURL = function (url) {
            //this.ourl = url || window.location.href;
            this.ourl = url ;
            this.href = "";//?前面部分
            this.params = {};//url参数对象
            this.jing = "";//#及后面部分
            this.host = location.protocol+"//"+location.hostname+"/";//#域名
            this.init();
        }
        //分析url,得到?前面存入this.href,参数解析为this.params对象，#号及后面存入this.jing
        objURL.prototype.init = function () {
            var str = this.ourl;
            var index = str.indexOf("#");
            if (index > 0) {
                this.jing = str.substr(index);
                str = str.substring(0, index);
            }
            index = str.indexOf("?");
            if (index > 0) {
                this.href = str.substring(0, index);
                str = str.substr(index + 1);
                var parts = str.split("&");
                for (var i = 0; i < parts.length; i++) {
                    var kv = parts[i].split("=");
                    this.params[kv[0]] = kv[1];
                }
            } else {
                this.href = this.ourl;
                this.params = {};
            }
        }
        //只是修改this.params
        objURL.prototype.set = function (key, val) {
            this.params[key] = val;
        }
        objURL.prototype.setParams = function (params) {
            this.params= params;
        }
        //只是设置this.params
        objURL.prototype.remove = function (key) {
            this.params[key] = undefined;
        }
        //根据三部分组成操作后的url
        objURL.prototype.url = function (type_name) {
            var strurl = this.href;
            var objps = [];//这里用数组组织,再做join操作
            for (var k in this.params) {
                if (this.params[k]) {
                    objps.push(k + "=" + this.params[k]);
                }
            }
            if (objps.length > 0) {
                if(type_name=="layui"){
                    strurl += "?/#/" + objps.join("/");
                }else{
                    strurl += "?" + objps.join("&");
                }
            }
            if (this.jing.length > 0) {
                strurl += this.jing;
            }
            return strurl;
        }
        //得到参数值
        objURL.prototype.get = function (key) {
            return this.params[key];
        }
  
        //得到所有参数值
        objURL.prototype.getParams = function () {
            return this.params;
        }
        lg.URL = objURL;
        return lg;
    }(LG || {}))

  exports('url_format', LG);
});   
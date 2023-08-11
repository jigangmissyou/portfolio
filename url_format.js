layui.define(function(exports){ 
    var LG = (function (lg) {
        var objURL = function (url) {
            //this.ourl = url || window.location.href;
            this.ourl = url ;
            this.href = "";//?ǰ�沿��
            this.params = {};//url��������
            this.jing = "";//#�����沿��
            this.host = location.protocol+"//"+location.hostname+"/";//#����
            this.init();
        }
        //����url,�õ�?ǰ�����this.href,��������Ϊthis.params����#�ż��������this.jing
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
        //ֻ���޸�this.params
        objURL.prototype.set = function (key, val) {
            this.params[key] = val;
        }
        objURL.prototype.setParams = function (params) {
            this.params= params;
        }
        //ֻ������this.params
        objURL.prototype.remove = function (key) {
            this.params[key] = undefined;
        }
        //������������ɲ������url
        objURL.prototype.url = function (type_name) {
            var strurl = this.href;
            var objps = [];//������������֯,����join����
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
        //�õ�����ֵ
        objURL.prototype.get = function (key) {
            return this.params[key];
        }
  
        //�õ����в���ֵ
        objURL.prototype.getParams = function () {
            return this.params;
        }
        lg.URL = objURL;
        return lg;
    }(LG || {}))

  exports('url_format', LG);
});   
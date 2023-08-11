layui.define(function(exports){ 
  var obj = {
        'hscode':[[
            {field: 'id', title: 'ID', width: 60, sort: true}, 
            {field: 'hs_code', title: 'HS CODE',width:200, sort: 'true'}, 
            {field: 'hs_code_name', title: '英文品名'}, 
            
            {field: 'color', width: 120,  title: '颜色',templet: function(d){
                    var ret="";
                    if(d.color!==""){
                        ret = "<span style='display: block; height: 50px;margin: auto; width: 100px;background-color:"+d.color+"'></span>"; 
                    }
                    return ret;
                }
            }, 
            {field: 'grade_name', title: '品名'}, 
            
            {field: 'create_user', title: '创建人',width:100},
            {field: 'create_time', title: '创建日期',width:150},
            {fixed: '', width: 150, align: 'center', toolbar: '#tab1Bar'}
        ]], 
       
        
  };

  exports('hscode_cols', obj);
});   
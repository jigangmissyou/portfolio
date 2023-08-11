layui.define(function(exports){ 
  var obj = {
        'hscode':[[
            {field: 'id', title: 'ID', width: 60, sort: true}, 
            {field: 'hs_code', title: 'HS CODE',width:200, sort: 'true'}, 
            {field: 'hs_code_name', title: 'Ӣ��Ʒ��'}, 
            
            {field: 'color', width: 120,  title: '��ɫ',templet: function(d){
                    var ret="";
                    if(d.color!==""){
                        ret = "<span style='display: block; height: 50px;margin: auto; width: 100px;background-color:"+d.color+"'></span>"; 
                    }
                    return ret;
                }
            }, 
            {field: 'grade_name', title: 'Ʒ��'}, 
            
            {field: 'create_user', title: '������',width:100},
            {field: 'create_time', title: '��������',width:150},
            {fixed: '', width: 150, align: 'center', toolbar: '#tab1Bar'}
        ]], 
       
        
  };

  exports('hscode_cols', obj);
});   
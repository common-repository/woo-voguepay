var rd_url;
var processed=0;

var functionobj=function (transaction_id) {
     processed=1;
      setTimeout(function(){
          var f = document.createElement('form');
          f.action=rd_url;
          f.method='POST';


          var i=document.createElement('input');
          i.type='hidden';
          i.name='transaction_id';
          i.value=transaction_id;
          f.appendChild(i);

          document.body.appendChild(f);
          f.submit();

      },5000);
  }

var cn_url;
  var x_functionobj=function () {
    if(!processed){
       window.location=cn_url;
   }
}

  function vp_inline(url,text,cancel,txurl) {
        var obj={};
        obj.url=url;
        rd_url=txurl;
        cn_url=cancel;
         obj.closed=x_functionobj;
        obj.success=obj.failed=functionobj;
        if(text.trim().length>0) obj.loadText=text;
        Voguepay.link(obj);
   }


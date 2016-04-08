$(document).ready(function(){
   $('#form_cn').change(function(){
      var val = $(this).val();
      val = val.replace(" ", "_").toLowerCase();
      $('#form_samaccountname').val(val); 
   }); 
});
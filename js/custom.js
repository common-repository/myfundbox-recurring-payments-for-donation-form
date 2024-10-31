jQuery(document).ready(function(){
  jQuery(".btn_pop_up").click(function(){
    //alert(jQuery(this).attr("href"));
   // jQuery("#modal_frm_btn").attr("src",jQuery(this).attr("href"));
  });
   jQuery(".btn_pop_up").click(function(){
    //alert(jQuery(this).attr("href"));
  //  jQuery("#modal_frm_btn").attr("src",jQuery(this).attr("href"));
            jQuery.ajax({
          type: "POST",
          url: "/wp-admin/admin-ajax.php",
          action: 'myaction',
          success: function(data) {
            alert(data);
          }
        });
    
  });
  
});



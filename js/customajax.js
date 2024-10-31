jQuery(document).ready(function(){
	jQuery(".btn_pop_up").click(function(){
		jQuery("#modal_frm_btn").attr("src",jQuery(this).attr("href"));
    });
	var interval = parseInt(jQuery("#nmyinterval").val());
	//console.log("Interval: "+interval);
		if(interval > 0){
			setInterval(function(){
		   jQuery.ajax({
				type: "POST",
				url: ajax_object.ajaxurl,
				data: {
						action : 'myfundbox_ajax_shortcode',
						npostid : jQuery("#npostid").val()
					},
				success : function( response ){ 
					if(response!=npostid)
					{
						jQuery("#mainwrapperfundbox").empty();    
						jQuery("#mainwrapperfundbox").append(response);				
					}
				},
			error : function(error){ console.log(error) }
			});

		}, interval);
	}
});
// Write your custom js code here

//jQuery("#selected_finishes_and_color").prop("disabled", true);
jQuery(document).ready(function($) {
	
	jQuery("#selected_finishes_and_color").on("change",function(){
  		
		jQuery("#loader_gif").show();
		 
		var finishes_id = jQuery(this).val();
		//var model_color = jQuery(this).val();
		var model_name	= jQuery("#model_name").val();
		var colors_id	= jQuery('option:selected', this).attr('model_color'); 
				
		if( finishes_id == '' ) {
			jQuery("#loader_gif").hide();
			return false;
		}
		
		var data = {
			'action'	  : 'generate_new_combination_image',
			'finishes_id' : finishes_id,
			'colors_id'   : colors_id,
			'model_name'  : model_name
		};

		// since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
		jQuery.post(ajaxurl, data, function(response) {
			//alert('Got this from the server: ' + response);
			jQuery("#loader_gif").hide();
			//var data = jQuery.parseJSON(response);
			if( response.success ) {
				if( response.png_image ) {
					jQuery("#generated_new_image").attr("src",response.png_image);
					jQuery("#png_image_download_link").attr("href",response.png_image);
				}
				if( response.jpg_image ) {
					jQuery("#jpg_image_download_link").attr("href",response.jpg_image);
				}
				jQuery("#loader_gif").hide();
			} else { 
				
				jQuery("#loader_gif").hide();	
			} 
		});
	});
	
	jQuery("#selected_varient").on("change",function(){
		var varient_id = jQuery(this).val();  
		
		if( varient_id == '' ) {
			jQuery("#selected_finishes_and_color").val('').attr('disabled', true);
			jQuery("#loader_gif").hide();
			return false;
		}
  		
		jQuery("#loader_gif").show();
		 				
		var data = {
			'action'	  : 'generate_new_varient_combination',
			'varient_id'  : varient_id, 
		};

		// since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
		jQuery.post(ajaxurl, data, function(response) {
			//alert('Got this from the server: ' + response);
			jQuery("#loader_gif").hide();
			//var data = jQuery.parseJSON(response);
			if( response.success ) {
				if( response.png_image ) {
					jQuery("#generated_new_image").attr("src",response.png_image);
					jQuery("#png_image_download_link").attr("href",response.png_image);
				}
				if( response.jpg_image ) {
					jQuery("#jpg_image_download_link").attr("href",response.jpg_image);
				}
				if(response.finish_color_option) {
					jQuery('#selected_finishes_and_color').removeAttr('disabled'); 
					jQuery("#selected_finishes_and_color").html("<option value=''> -- Select Finishes & Color -- </option>" + response.finish_color_option);
				} 
				jQuery("#loader_gif").hide();
			} else { 
				
				jQuery("#loader_gif").hide();	
			} 
		});
	});
	
	
}); 

 
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */



jQuery(document).ready(function($){
	$('.bind-category').change(function(){
		var user_id = $(this).attr('id');
		var cat_id = $(this).val();
		var image_id = '#imgajax-' + user_id;
		$(image_id).css({'display':'inline'});
		//alert(CatUserAjax.ajaxurl);
		
		$.ajax({						
			async: false,
			type:'post',			
			dataType:"html",
			url:CatUserAjax.ajaxurl,
			cache:false,
			timeout:10000,
			data:{
				'action' : 'cat_user_ajax_data',
				'cat' : cat_id,
				'uid' : user_id				
			},

			success:function(result){				
				window.location.href = window.location.href;				
			},

			error: function(jqXHR, textStatus, errorThrown){
				jQuery('#footer').html(textStatus);
				alert(textStatus);
				return false;
			}

		});		
	});
});
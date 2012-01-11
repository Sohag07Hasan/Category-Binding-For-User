/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */



jQuery(document).ready(function($){
	
	var ids = CatUser.aid.split('-');
	for(i=0;i<ids.length;i++){
		test = '#category-' + ids[i];
		$(test).html(null);
	}
	
	//show the category after the nulling the others
	$('.categorychecklist').css({'display':'block'});
});

function clock_in_anchors(){
jQuery(".clockin_anchor").click(function(e){
	e.preventDefault();
	var t = jQuery(this);
	jQuery.ajax(t.attr("href")).done(function(content){
//		content = JSON.parse(content);
		if(content != '') alert(content);
	});
});

}

jQuery(function($){
clock_in_anchors();


});
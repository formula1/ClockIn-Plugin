
function clock_in_anchors(){
jQuery(".clockin_anchor").click(function(e){
	e.preventDefault();
	var t = jQuery(this);
	jQuery.ajax(t.attr("href")).done(function(content){
//		content = JSON.parse(content);
		if(content.indexOf("failure: ") == 0) throw new Error(content);
		else{ jQuery(".clockin-wrap").replaceWith(content); clock_in_anchors();}
	});
});

}

jQuery(function($){
clock_in_anchors();


});
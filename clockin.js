var clock_in_anchors;

jQuery(function($){
clock_in_anchors = function(){
$(".clockin_anchor").click(function(e){
	e.preventDefault();
	var t = $(this);
	$.ajax(t.attr("href")).done(function(content){
		content = JSON.parse(content);
		if(content != '') alert(content);
	});
});

}();


});
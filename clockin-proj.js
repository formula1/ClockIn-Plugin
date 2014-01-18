
function clock_in_proj(item){
	that = this;
	var contain = jQuery(item);
	var child = contain.children(".clockin_projects").eq(0);
	this.looking = false;
	contain.scroll(function(){
		var bottom = jQuery(this).scrollTop() + jQuery(this).height();
		var more = child.find(".view_more.bottom");
		if(bottom >= child.height() && more.size() > 0 && !that.looking){
			that.looking = true;
			var href = more.attr("href");
			more.html("loading");
			jQuery.ajax(href).done(function(content){
				more.remove();
				child.append(content);
				that.looking = false;
				clock_in_anchors();
			});
		}
	});
}
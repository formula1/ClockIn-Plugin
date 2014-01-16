
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
/*
var clock_in_proj = function($){
	var page = 1;
	var last_page = -1;
	var that = this;
	
	
	

'infinite', {
		container: 'auto',
		items: 'li',
		more: '.clock_in_more',
		offset: 'bottom-in-view',
		loadingClass: 'infinite-loading',
		onBeforePageLoad: function(){},
		onAfterPageLoad: function(){
			if(typeof content == "string" && content.indexOf("failure: ") == 0) return;
//			$(".clockin-wrap>.clockin_projects").empty();
//			$(".clockin-wrap>.clockin_projects").append(content);
			clock_in_anchors();

		}
	});
		
	this.getPage = function(num){
		if(typeof num == "undefined") num = page;
		else if(num == "+") num = page+1;
		else if(num == "-") num = page-1;
		
		if(num < 1) num == 1;
		if(last_page != -1 && num > last_page) num = last_page;
		page = num;
		$(".clockin-wrap>a").css('background-color', '#000');
		$(".clockin-wrap>a").unbind("click");
		$.ajax('').done(function(content){
			if(content.indexOf("failure: ") == 0){ last_page = num-1; getPage(last_page); return; }
			$(".clockin-wrap>.clockin_projects").empty();
			$(".clockin-wrap>.clockin_projects").append(content);
			if(last_page == -1 || page < last_page){
				$(".clockin-wrap>a:eq(1)").css('background-color', '#DDD');
				$(".clockin-wrap>a:eq(1)").click(function(e){e.preventDefault();that.getPage("+")});
			}else $(".clockin-wrap>a:eq(1)").click(function(e){e.preventDefault()});
			if(page > 1){
				$(".clockin-wrap>a:eq(0)").css('background-color', '#DDD');
				$(".clockin-wrap>a:eq(0)").click(function(e){e.preventDefault();that.getPage("-")});
			} else $(".clockin-wrap>a:eq(0)").click(function(e){e.preventDefault();});
			clock_in_anchors();
		}).fail(function() {
			alert( "error" );
		});
	}
	
//	this.getPage();

};

*/
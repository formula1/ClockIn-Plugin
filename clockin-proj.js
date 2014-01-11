
var clock_in_proj;
jQuery(function($){
clock_in_proj = function($){
	var page = 1;
	var last_page = -1;
	var that = this;
		
	this.getPage = function(num){
		if(typeof num == "undefined") num = page;
		else if(num == "+") num = page+1;
		else if(num == "-") num = page-1;
		
		if(num < 1) num == 1;
		if(last_page != -1 && num > last_page) num = last_page;
		page = num;
		$(".clockin-wrap>.a").css('background-color', '#000');
		$(".clockin-wrap>.clockin_projects").empty();
		$.ajax("https://api.github.com/users/"+clock_in_vars.github_user+"/repos?type=all&sort=updated&per_page=4&page="+num)
		.done(function(content){
			if(content.length == 0){ last_page = num-1; getPage(last_page); return; }
			for(var i=0;i<content.length;i++){
				$(".clockin-wrap>.clockin_projects")
				.append(
					"<span style='display:inline-block;width:64px;height:64px;'>"
					+"<span style='display:block'>"+content[i].full_name+"</span>"
					+"<a class='clockin_anchor' href='"+clock_in_vars.clockin_uri+"&proj="+content[i].full_name+"'>Clock In</a>"
					+"</span>"
				);
			}
			if(last_page == -1 || page < last_page){
				$(".clockin-wrap>.a:eq(1)").css('background-color', '#DDD');
				$(".clockin-wrap>.a:eq(1)").click(function(e){e.preventDefault();clock_in_proj.getPage("+")});
			}
			if(page > 1){
				$(".clockin-wrap>.a:eq(0)").css('background-color', '#DDD');
				$(".clockin-wrap>.a:eq(1)").click(function(e){e.preventDefault();clock_in_proj.getPage("-")});
			}
			clock_in_anchors();
		});
	}
	
	this.getPage();

}($);

});
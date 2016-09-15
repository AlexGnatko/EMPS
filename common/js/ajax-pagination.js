function setup_pagination(opts){
	if(opts.autoload){
		$(".ap-page-next-link").appear();
		
		$(".ap-page-next-link").on('appear', function(e){
			$(this).click();
		});
	}
	
	$(".ap-page-next-link").click(function(e){
		e.stopImmediatePropagation();
		e.stopPropagation()
		e.preventDefault();
		
		var loading = $(".ap-page-loading");

		var _url = $(this).attr('href');
		_url = _url + "?pages=1";
		
		var target = $(this).data('target');
		
		var elem = $(this);
		
		loading.show();
		elem.hide();
		
		setTimeout(function(){
			
			$.ajax({
				type: "GET",
				dataType: 'json',		
				url: _url,
				success: function(data, status){
					loading.hide();
					elem.show();
					var html = data.html;
					$(target).append(html);
					if(!data.next){
						elem.parent().css("display", "none");
					}else{
						elem.attr('href', data.next);
					}
				}
			});	
		
		}, 0);	

		return false;		
	});
}
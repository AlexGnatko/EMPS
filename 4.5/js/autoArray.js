// JavaScript Document

(function( $ ){
	var methods = {
		init : function( options ) { 
			var settings = {
				'id'		: '',
				'idx'		: 0
			};
			
			return this.each(function() {        
				if ( options ) { 
					$.extend( settings, options );
				}
				var data = new Array();
				$.extend( data, settings);
				data['html'] = $(this).html();
				var id=data['id'];
				var idx=0;
				var caller = $(this);
				$("[id*="+id+"]").each(function(){				
					$(this).bind('keypress change',function(){
						caller.EMPSAutoArray('ensure_line');
					});
			
					idx++;
				});
				
				data['idx']=idx;

				$(this).data('autoarray_data',data);
				$(this).EMPSAutoArray('ensure_line');
			});
		
		},
		ensure_line : function( ) {
			var data = $(this).data('autoarray_data');
			var id = data['id'];
		
			var found_free=0;
			$("[id^="+id+"]").each(function(){
				var empty = true;
				$(this).find("input,select,textarea").each(function(){
					if($(this).attr('type')=='search' || $(this).attr('type')=='text' || $(this).get(0).tagName == 'TEXTAREA'){
						if($(this).val()!=''){
							empty = false;
						}
					}
			
				});
				if(empty){
					found_free++;
				}
			});
			if(found_free==0){
				$(this).EMPSAutoArray('add_line');
			}
			if(found_free>1){
				$(this).EMPSAutoArray('compact');
			}
		},
		add_line : function( values ) {
			var data = $(this).data('autoarray_data');
			var idx = data['idx'];			
			var id = data['id'];
			var html = data['html'];
			var template = $('<div><div id="'+id+'_'+idx+'">'+html+'</div></div>');
			var caller = $(this);
			template.find("input,select,textarea").each(function(){
				var name=$(this).attr('name');
				
				if(typeof values != 'undefined'){
					if(typeof (values[name]) != 'undefined'){
						if($(this).get(0).tagName == 'SELECT'){

							$(this).find("option").each(function(e){
								if($(this).val() == values[name]){
									$(this).attr("selected","selected");
								}else{
									$(this).attr("selected","");	
								}
							});
						}
						
						$(this).val(values[name]);
					}
				}

				$(this).attr('name',name+"_idx["+idx+"]");
				$(this).attr('list', "datalist_"+id+"_"+idx);
				$(this).bind('keypress change select',function(){
					caller.EMPSAutoArray('ensure_line');
				});
				$(this).bind('keyup',function(){
					caller.EMPSAutoArray('on_keypress', this, 0);
				});
				
			});
			template.find("datalist").each(function(){
				$(this).attr("id", "datalist_"+id+"_"+idx);
			});
//			alert(template.html());
			$(this).before(template);
			idx++;			
			data['idx']=idx;
			$(this).data('autoarray_data',data);
		},
		on_keypress: function ( obj, mode ){
			var data = $(this).data('autoarray_data');
			
			if(typeof data['autocomplete_source'] == "undefined"){
				return;
			}
			
			var o = $(obj);
			var dl = o.parent().find("datalist");
			
			var caller = $(this);
			
			if(mode == 0){
				clearTimeout(data['ajax_timeout']);
				data['ajax_timeout'] = setTimeout(function(){
					caller.EMPSAutoArray('on_keypress', obj, 1);
				}, 600);
				$(this).data('autoarray_data', data);
			}
			if(mode == 1){
				$.ajax(data['autocomplete_source']+"?q="+escape(o.val()), {success: function(result){
					if(result.result == "OK"){
						var list = result.list;
						dl.html('');
						var l = list.length;
						for(var i = 0; i < l; i++){
							dl.append("<option value=\""+list[i].name+"\" />");
						}
					}
				}});
			}
			
		},
		compact : function( anyway ) {
			var data = $(this).data('autoarray_data');
			var idx = data['idx'];			
			var id = data['id'];
//			alert('compact');
			var last = $("[id*="+id+"]").last();
			$("[id*="+id+"]").each(function(){
				var empty = true;
				$(this).find("input,select,textarea").each(function(){
					if($(this).attr('type')=='search' || $(this).attr('type')=='text' || $(this).get(0).tagName == 'TEXTAREA'){
						if($(this).val()!=''){
							empty = false;
						}
					}
				
				});
				if(empty){
					if(anyway){
						$(this).remove();					
					}else if(!$(this).is(last)){
						if(!$(this).find("search,select,textarea").is($(document.activeElement ))){
							$(this).remove();
						}
					}
				}
			});
			if(anyway){
				data['idx']=0;
			}
		}

	};
	
	$.fn.EMPSAutoArray = function( method ) {
		if ( methods[method] ) {
			return methods[ method ].apply( this, Array.prototype.slice.call( arguments, 1 ));
		} else if ( typeof method === 'object' || ! method ) {
			return methods.init.apply( this, arguments );
		} else {
			$.error( 'Method ' +  method + ' does not exist on jQuery.EMPSAutoArray' );
		}    
	};
})( jQuery );


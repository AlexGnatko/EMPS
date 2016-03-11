// JavaScript Document

(function( $ ){
	var methods = {
		init : function( options ) { 
			var settings = {
				'selector_type'		: '',
				'selector_name'		: 'Object',
				'selector'			: '/objsel-select/?',
				'descriptor'		: '/objsel-describe/?',
				'value_holder'		: '',
				'beforeShow'	: function(){ return true; },
				'onSetValue'	: function(){},
				'onNewSelection'	: function(){},
			};
			
			return this.each(function() {        
				if ( options ) { 
					$.extend( settings, options );
				}
				var data = new Array();
				$.extend( data, settings);
				$(this).data('selector_data',data);
				$(this).bind('focus.EMPSObjectSelector',methods.show);

			});
		
		},
		show : function( ) {
			var data = $(this).data('selector_data');

			var r = data.beforeShow.call(this);
			if(!r){
				return;
			}
			
			var href=data.selector+"id="+$(this).attr('id')+"&value="+escape($(this).val())+"&selector_type="+data.selector_type+"&selector_name="+escape(data.selector_name);
			
			var saveContent = $(".fancybox-inner");
			if(saveContent){
				var html = saveContent.html();
				if(html){
					if(html.length > 0){
						$('body').append("<div id=\"saved_fancybox_content\"></div>");
//						$('.fancybox-inner').children().clone(true, true).appendTo('#saved_fancybox_content');
						$('.fancybox-inner').children().appendTo('#saved_fancybox_content');
//						alert("Saving content");
//						alert($("#saved_fancybox_content").html());
						$('.fancybox-inner').children().remove();
						var obj = $.fancybox.current;
						if(obj){
							obj['beforeClose'] = $.noop;
//							alert("resetting");
						}
//						alert($(".fancybox-inner").html());						
					}
				}
			}
			
			$.fancybox({
				href: href,
				type: 'ajax',
				ajax: {},
				autoHeight: true,
				autoWidth: true,
				helpers: {
					overlay: {
						speedIn: 0,
						speedOut: 0,
						css : {
							'background' : 'rgba(90, 120, 160, 0.3)'
						}						
					}
				},
				openEffect: 'none',
				closeEffect: 'none',
				margin: 0,
				padding: 0,
				beforeClose: function(){ 
					var content = $("#saved_fancybox_content");
					if(content.length > 0){

//						alert("Restoring content");						
						var inner = $('.fancybox-inner');
						inner.children().remove();
///						$("#saved_fancybox_content").children().clone(true, true).appendTo(".fancybox-inner");
						$("#saved_fancybox_content").children().appendTo(".fancybox-inner");
						$("#saved_fancybox_content").remove();
//						alert($(".fancybox-inner").html());
						$.fancybox.update();
						return false;
					}
					return true;
				}

			});
		},
		hide : function( ) {
			$.fancybox.close();
		},
		describe : function ( ) {
			var data = $(this).data('selector_data');
			var id = 0;
//			alert(data);
			if(data.value_holder){
				id = parseInt($("#"+data.value_holder).val(),10);
			}else{
				id = parseInt($(this).data('selector_value').id,10);
			}
			
			if(id==0 || isNaN(id)){
				return false;
			}
			
			
			var url = data.descriptor+"id="+id+"&selector_type="+data.selector_type;
//			alert("url: "+url);
			$.ajax({url:url,context:this,success:function(data,status){
				var values=new Array();
				if(data=='ERROR'){
					$(this).val('??: Error');
					return false;
				}
				var s=data.split('!$row$!');
				var v,name,value;
				for(i=0;i<s.length;i++){
					v=s[i].split('!$:$!');
					name=v[0];
					value=v[1];
					values[name]=value;
				}
				$(this).data('value_data',values);
				var text;
				text=values.name+values.extra+" <"+values.id+">";
				$(this).val(text);
				
				if($(this).data('new_selection')){
					$(this).data('new_selection', false);
					var idata=$(this).data('selector_data');
					if(idata.onNewSelection){
						idata.onNewSelection();
					}
				}
			}});
		},
		golink : function (){
			var values=$(this).data('value_data');
			_navigate(values.link);
		},
		setvalue : function( value ) {
			$(this).data('selector_value', value);
			$(this).data('new_selection', true);
			var data = $(this).data('selector_data');
			if(data.value_holder){
				var o = $("#"+data.value_holder);
				$("#"+data.value_holder).val(value.id);
				if(o.data("ng")){
					var idata = $(this).data('selector_data');
					idata.onSetValue.call($(this), value.id);
				}
			}
			methods.hide();
			$(this).EMPSObjectSelector('describe');
		},
		settings : function( settings ){
			var data = $(this).data('selector_data');			
			$.extend( data, settings);
			$(this).data('selector_data',data);			
		}
	};
	
	$.fn.EMPSObjectSelector = function( method ) {
		if ( methods[method] ) {
			return methods[ method ].apply( this, Array.prototype.slice.call( arguments, 1 ));
		} else if ( typeof method === 'object' || ! method ) {
			return methods.init.apply( this, arguments );
		} else {
			$.error( 'Method ' +  method + ' does not exist on jQuery.EMPSObjectSelector' );
		}    
	};
})( jQuery );

var num=0;

function EMPSSelector_newline(id,value,settings){
	var s=$("#pick_sample_"+id);
	var sp=$("#pick_span_"+id);	
	
	var html=s.html();
	
	sp.append(html);
	sp.find("#pick_input_"+id).attr('id','pick_input_'+id+'_'+num);
	sp.find("#pick_value_"+id).attr('id','pick_value_'+id+'_'+num);
	sp.find("#pick_button_"+id).attr('id','pick_button_'+id+'_'+num);
	sp.find("#pick_clear_"+id).attr('id','pick_clear_'+id+'_'+num);
	
	settings.value_holder='pick_value_'+id+'_'+num;
	var input=sp.find("#pick_input_"+id+"_"+num);
	input.EMPSObjectSelector(settings);
	var vh=sp.find("#pick_value_"+id+"_"+num);	
	vh.attr('name',vh.attr('name')+'['+num+']');
	vh.val(value);
	
	input.EMPSObjectSelector('describe');	
		
	sp.find("#pick_button_"+id+"_"+num).bind('click',function(ev){
		var o=$(ev.currentTarget);
		o.blur();
		o.prevAll('.pick_input').EMPSObjectSelector('show');
	});	
	sp.find("#pick_clear_"+id+"_"+num).bind('click',function(ev){
		var o=$(ev.currentTarget);
		o.parent().remove();
		EMPSSelector_ensure_line(id,settings);
	});		

	num++;
}

function EMPSSelector_ensure_line(id,settings){
	var sp=$("#pick_span_"+id);	
	var empty=false;
	sp.find(".pick_value").each(function(i,e){
		if($(e).val()=='' || $(e).val()==0){
			empty=true;
		}
	});
	if(!empty){
		EMPSSelector_newline(id,0,settings);
	}
}
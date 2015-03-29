// JavaScript Document

(function( $ ){
	var methods = {
		init : function( options ) { 
			var settings = {
				'onSelect' 		: false,
				'onDeselect'	: false,
				'onDetail'		: false,				
				'peerView'		: false,				
				'sourceURL' 	: false,
				'sourceVar'		: 'id',
				'deleteURL' 	: false,
				'deleteVar'		: 'id',				
				'createURL'		: false,
				'createVar'		: 'id',
				'dataURL'		: false,
				'dataVar'		: 'id',
				'listURL'		: false,
				'listVar'		: 'id',
				'detailURL'		: false,
				'detailVar'		: 'id'
			};
			
			var rv=this.each(function() {        
				if ( options ) { 
					$.extend( settings, options );
				}
				
				var data=new Array();
				$.extend(data,settings);
				
				$(this).data('tree_data',data);
				
				treeInitializeSubtree(this);
				
				$(this).bind('click',treeClickVoid);
				
				if(data['peerView']){
					var pv=$(data['peerView']);
					if(pv){
						$(this).css('height',pv.height()+'px');
					}
				}
			});
			
			return rv;
		
		},
		create : function( options ) {
			var data = $(this).data('tree_data');
			var url=data['createURL']+"?"+data['createVar']+"="+options['parent'];
			
			var s=$(this);
			if(options['po']){
				s=$(options['po']).nextAll('.trsubtree');
			}

			treeEnsureOpen($(s).prevAll('.trclosed').get());			
			
			var ctx=$(s).get();
			$.ajax({'url': url, 'context': ctx, success: function(data,status,xhr){
				$(this).append(data);

				treeInitializeSubtree(this);
				if($(this).hasClass('trsubtree')){
//					treeSelectEx($(this).find('.trlink:last').get());					
				}
			}});			
			
			return this;
		},
		load : function( options ) {
			var data = $(this).data('tree_data');
			var url=data['sourceURL']+"?"+data['sourceVar']+"="+options['parent'];
			
			var s=$(options['container']);
			
			var ctx=$(s).get();

			$.ajax({'url': url, 'async': false, 'context': ctx, success: function(data,status,xhr){
				$(this).html(data);

				treeInitializeSubtree(this);
				if($(this).hasClass('trsubtree')){
					treeEnsureOpen($(this).prevAll('.trclosed').get());
				}
			}});			
			
			return this;
		},
		'findnode' : function ( options ) {
			$(".trlink").each(function(e){
				var id = parseInt($(this).attr("rel"));
				
				if(options['node'] == id){
//					alert(id);
					var i = $(this).parent();
					var tv=i.parents('.treeview');
					
//					alert(i.attr("class"));
		
					tv.EMPSTreeView('load',{'container':i.children('.trsubtree').get(),'parent':parent});	
					
//					treeSelectPlain(i.find(".trlink"));				
					treeEnsureOpen(i.get());
				}
			});
		},
		detail : function( options ) {
			var o=$(options['link']);
			var p=o.parents('.treeview');
			var data=p.data('tree_data');
			
			if(data){
				data['last_detail']=o;
				data['last_list']=false;
				p.data('tree_data',data);			
				loadPagePart(data['peerView'],data['detailURL']+"?"+data['detailVar']+"="+o.attr('rel'));
			}
			return false;
		},
		list : function( options ) {
			var o,p,node_id=-1;
			if(!options['link']){
				p=$(this);
			}else{
				o=$(options['link']);
				node_id=o.attr('rel');
				p=o.parents('.treeview');
			}
			var data=p.data('tree_data');
			
			if(data){
				data['last_detail']=false;
				if(o){
					data['last_list']=o;
				}else{
					data['last_list']=node_id;
				}
				p.data('tree_data',data);				
				var url = data['listURL']+"?"+data['listVar']+"="+node_id;
				loadPagePart(data['peerView'],url);
			}			
			return false;
		},
		'update' : function( ) {
			var data=$(this).data('tree_data');
			if(data){
				if(data['last_detail']){
					var o=data['last_detail'];
					var url=data['dataURL']+"?"+data['dataVar']+"="+o.attr('rel');		
					var ctx=o.get();
					$.ajax({'url':url,'context':ctx,success:function(data,status,xhr){
						var x=data.split('|');
						if($(this).attr('rel')==x[0]){
							$(this).html(x[1]);
						}
					}});
					$(this).EMPSTreeView('detail',{link:o.get()});
				}
			}
		},
		'delete' : function( options ) {
			var data = $(this).data('tree_data');
			var url=data['deleteURL']+"?"+data['deleteVar']+"="+options['id'];
			
			var ctx=options['node'];

			if(confirm('Удалить этот элемент и все подэлементы?')){			
				$.ajax({'url': url, 'context': ctx, success: function(data,status,xhr){
					if(data=='DELETED'){
						treeClickVoidEx($(this).parents('.treeview').get());										
						$(this).remove();
					}
				}});			
			}
			
			return false;
		}
	};
	
	$.fn.EMPSTreeView = function( method ) {
		if ( methods[method] ) {
			return methods[ method ].apply( this, Array.prototype.slice.call( arguments, 1 ));
		} else if ( typeof method === 'object' || ! method ) {
			return methods.init.apply( this, arguments );
		} else {
			$.error( 'Method ' +  method + ' does not exist on jQuery.EMPSTreeView' );
		}    
	};
})( jQuery );

function treeVoid(){
}

function treeInitializeSubtree(obj){
	var a=$(obj).find('.tropen,.trclosed');

	a.attr('href','javascript:treeVoid()');
	
	a.delegate('','click',treeToggle);
	
	a=$(obj).find('.trlink');
	a.addClass('trunsel');
	
	a.delegate('', 'click',treeSelect);

	a=$(obj).find('.trdetail');
	a.delegate('','click',treeDetail);
	a=$(obj).find('.trdelete');
	a.delegate('','click',treeDeleteNode);	
	
	a=$(obj).find('.trdetail,.trdelete');
	a.attr('href','javascript:treeVoid()');	
		
	$(obj).find('.tropen,.trclosed,.trlink').attr('href','javascript:treeVoid()');
	
	$(obj).find('.tritem').each(function(i,e){
		var ip=$(e).parent();
		var width=0;
		if(ip.hasClass('treeview')){
			width=(ip.width()-15);
		}	
		if(ip.hasClass('trsubtree')){
			width=(ip.parent().width()-15);			
		}
		$(e).css('width',width+'px');
		$(e).children('.trlink').css('max-width',(width-80)+"px");
	});	
}

function treeEnsureOpen(obj){
	var o=$(obj);
	if(o.hasClass('trclosed')){
		treeOpenNode(obj);
	}
}

function treeDetail(e){
	var o=$(e.currentTarget);
	var d=o.parent().parent();
	var l=d.children('.trlink');
	treeSelectEx(l.get());
	
	var p=d.parents('.treeview');
	var data=$(p).data('tree_data');
	if(data){
		var os=data['onDetail'];
		if(os){
			os(l);
		}
	}	
	return false;
}

function treeDeleteNode(e){
	var o=$(e.currentTarget);
	var d=o.parent().parent();
	var l=d.children('.trlink');
	treeSelectEx(l.get());
	
	var tv=d.parents('.treeview');
		
	tv.EMPSTreeView('delete',{'node':d.get(),'id':parseInt(l.attr('rel'),10)});		
}

function treeOpenNode(obj){
	var o=$(obj);
	var d=o.parent();

	o.addClass('tropen');
	o.removeClass('trclosed');
	d.children('.trsubtree').css('display','block');
	
	var parent=parseInt(d.children('.trlink').attr('rel'),10);
	
	var tv=o.parents('.treeview');
		
	tv.EMPSTreeView('load',{'container':d.children('.trsubtree').get(),'parent':parent});	
}

function treeToggle(e){
	var o=$(e.currentTarget);
	return treeTogglePlain(o);
}

function treeTogglePlain(o){
	o = $(o);
	var d=o.parent();	
	if(o.hasClass('tropen')){
		o.addClass('trclosed');
		o.removeClass('tropen');
		d.children('.trsubtree').css('display','none');
	}else{
		treeOpenNode(o.get());		
	}

	o.blur();
	return false;
	
}

function treeSelectEx(obj){
	var o=$(obj);
	var p=o.parents('.treeview');
	p.find('.trlink').removeClass('trselected');
	o.addClass('trselected');
	o.blur();
	var data=$(p).data('tree_data');
	if(data){
		var os=data['onSelect'];
		if(os){
			os(o);
		}
	}

	return false;	
}

function treeSelect(e){
	var o=$(e.currentTarget);
	return treeSelectPlain(o);
}

function treeSelectPlain(o){
	o = $(o);
	var rv=treeSelectEx(o.get());
	o.parents('.treeview').EMPSTreeView('list',{link:o.get(),vars:{'start':0}});	
	return rv;
}

function treeClickVoidEx(obj){
	var o=$(obj);
	if(!o.hasClass('treeview')){
		o.blur();
		return false;
	}
	o.find('.trlink').removeClass('trselected');
	var data=$(o).data('tree_data');

	if(data){
		var os=data['onDeselect'];
		if(os){
			os(false);
		}

	}
	
	o.EMPSTreeView('list',{vars:{'start':0}});
		
	o.blur();	
	return false;
}

function treeClickVoid(e){
	var o=$(e.currentTarget);
	return treeClickVoidEx(o.get());
}

function loadPagePart(view_s,url){
	var ctx=$(view_s).get();
	$.ajax({'url': url, 'context': ctx, success: function(data,status,xhr){
		$(this).html(data);
		if(afterPagePartLoad){
			afterPagePartLoad();
		}
	}});
}

function set_all_boxes(){
	$(".ieright input[type=checkbox]").each(function(e){
		$(this).prop("checked", true);
	});
}

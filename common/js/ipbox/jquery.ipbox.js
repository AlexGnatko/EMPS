// JavaScript Document

/*
 * EMPS Irkplus Lightbox
 *
 * Copyright 2014, Alex Gnatko
 *
 * Using code by Sebastian Tschan
 * from blueimp Gallery JS 2.14.0
 *
 */


(function( $ ){
	function EMPS_IPBox(container, params) {	
		// Constructor function
//		alert(JSON.stringify(container));
		this.params = $.extend(true, this.params, this.defaults);		
		this.params = $.extend(true, this.params, params);
		this.params = $.extend(true, this.params, {container: container});		
		this.initialize();
	}
	
	// Class definition
    $.extend(EMPS_IPBox.prototype, {
		defaults: {
			// Sometimes when you swipe with mouse, text gets selected, which is annoying. 
			// You can turn it off in options, though. Default is on.
			unselectable: true,
			// Swipe with mouse
			emulate_touch_events: true,
			// Disable scroll when swiping
			disable_scroll: true,
			stop_touch_propagation: true,
			circle_rows: true,
			circle_cols: true,
			lst: [],
			one_row: false,
			thumbs: false,
			with_text: false,
			// Ratio: how much an image has to be swiped by user in order to get set in motion
			swipe_threshold: 0.2,
			default_speed: {h: 300, v: 200},
			onchange: function(){}
		},
        // Detect touch support:
        support_touch: (function () {
            return window.ontouchstart !== undefined ||
            (window.DocumentTouch && document instanceof DocumentTouch);
		}),

        console: window.console && typeof window.console.log === 'function' ?
            window.console :
            {log: function () {}},
		
        initialize: function () {
			var c = $(this.params.container);
			
//			alert("nit ok");
			this.init_images();
			this.init_event_listners();
			this.show_selected();
		},
		show_selected: function () {
			var c = $(this.params.container.find(".ipb-container"));			
			var swipegrid = this;
			c.find(".emps-swipe-grid-row").each(function(){
				$(this).css("z-index",0);
				if($(this).data('selected')=='selected'){
					$(this).css("z-index",100);
					swipegrid.current_row = this;
					$(this).find(".emps-swipe-grid-col").each(function(){
						$(this).css("z-index",0);
						$(this).css("display", "none");
						if($(this).data('selected')=='selected'){
							$(this).css("z-index",100);
							$(this).css("display", "block");
							swipegrid.current_col = this;
						}
					});
					
				}else{
					$(this).find(".emps-swipe-grid-col").each(function(){
						$(this).css("z-index",0);
						$(this).css("display", "none");
					});
				}
			});			
			
			if(this.params.with_text){
				this.current_text = $(this.current_col).data('text');
								
				var t = c.parent().find(".ipb-textvalue");
				
				t.html(this.current_text);
			}
			
		},
		init_image: function(img) {
			img.data('EMPS_IPBox', this);
			
			var c = $(this.params.container);

			var row = c.find(".emps-swipe-grid-row");
			row.append('<div class="emps-swipe-grid-col"></div>');
		
			var col = row.children().last();
	
			var text = img.attr("title");
			col.data("text", text);

			var zoom = img.data("zoom");
			if(typeof zoom == "undefined"){
				zoom = img.attr("href");
			}
			col.css("background-image", "url('"+zoom+"')");

			img.data("col", col);

			img.off('click');
						
			img.on('click', function(e){
				var obj = $(this).data('EMPS_IPBox');

				obj.open_wrapper();				
				
				obj.change(obj.current_row, $(this).data('col'));
				
				e.preventDefault();
				return false;
			});
		},
		init_images: function () {

			var c = $(this.params.container);
			

			c.find(".ipb-container").append('<div class="emps-swipe-grid-row"></div>');
			var row = c.find(".ipb-container").children().last();
			
			row.data('link', 'row');
			row.data('selected', 'selected');
			
						
			var that = this;
			
			$.each(this.params.lst, function(index, value){
				that.init_image(value);
			});
			
			row.children().first().data("selected", "selected");			
		},
        init_event_listners: function () {
			var c = $(this.params.container).find(".ipb-container");

			proxy_listener = function (event) {
				var type = event.type;
				var that = $(event.currentTarget).parent().data('EMPS_IPBox');
				that['on' + type](event);
			};
			
			var images = c;
			
			c.parent().find(".ipb-galclose").off('click').on('click', function(){
				var obj = $(this).parent().data('EMPS_IPBox');
				obj.close_wrapper();
			});
			
			c.parent().find(".ipb-galleft").off('click').on('click', function(){
				var obj = $(this).parent().data('EMPS_IPBox');				
				
				obj.step(1);
			});
			c.parent().find(".ipb-galright").off('click').on('click', function(){
				var obj = $(this).parent().data('EMPS_IPBox');				
				
				obj.step(-1);
			});			

            images.off('click').on('click', proxy_listener);
            if (this.support_touch()) {
//				alert("touch!");
                images
                    .on('touchstart touchmove touchend touchcancel', proxy_listener);
            } else if (this.params.emulate_touch_events) {
                images
                    .on('mousedown mousemove mouseup mouseout', proxy_listener);
            }
            this.proxy_listener = proxy_listener;
        },
		open_wrapper: function () {
			var c = $(this.params.container);
			c.css("display", "block");
			
		},
		close_wrapper: function () {
			var c = $(this.params.container);
			c.css("display", "none");
		},
		
		step: function (val) {
			this.touchreset();				
			this.touch_delta = {x: val*100, y: 0};
			this.move();
			this.finish_transition();
		},

        destroy_event_listners: function () {
			var c = $(this.params.container);
			var images = c;
			
            var proxy_listener = this.proxy_listener;
            images.off('click', proxy_listener);
            if (this.support.touch) {
                images
                    .off('touchstart touchmove touchend touchcancel', proxy_listener);
            } else if (this.params.emulate_touch_events) {
                images
                    .off('mousedown mousemove mouseup mouseout', proxy_listener);
            }
        },
		
        stopPropagation: function (event) {
            if (event.stopPropagation) {
                event.stopPropagation();
            } else {
                event.cancelBubble = true;
            }
        },
		
		pixels: function (v) {
			var r = parseInt(v.replace("px", ""), 10);
			return r;
		},
		
        animate: function (obj, pos, speed, done) {
			if(!obj){
				return;
			}
			if(typeof obj == 'undefined'){
				return;
			}
			if(typeof obj.style == 'undefined'){
				return;
			}			
			var c = obj;
			var cur = {x: 0, y:0};
			
//			alert(c.style.left);

			cur.x = this.pixels(c.style.left);
			cur.y = this.pixels(c.style.top);			
//			alert(cur.x + " / " + cur.y + " ::: " +c.style.left + " / " + c.style.top);
			
            if (!speed) {
                c.style.left = pos.x + 'px';
                c.style.top = pos.y + 'px';		
				done.call(that);		
                return;
            }
            var that = this,
                start = new Date().getTime(),
                timer = window.setInterval(function () {
                    var elapsed = new Date().getTime() - start;
                    if (elapsed > speed) {
						try{
						c.style.left = pos.x + 'px';
						c.style.top = pos.y + 'px';	
						}catch(e){
						};
                        window.clearInterval(timer);
						done.call(that);
                        return;
                    }
					
					var ratio = elapsed / speed;
					
					try{
						if(pos.x != cur.x){
							c.style.left = Math.floor((pos.x - cur.x) * ratio) + cur.x + "px";
	//						this.console.log(ratio + ": " + "@" + (pos.x - cur.x) + " / "+c.style.left + "/" + elapsed + "/" + speed);
						}
						if(pos.y != cur.y){
							c.style.top = Math.floor((pos.y - cur.y) * ratio) + cur.y + "px";						
						}
					}catch(e){
					};
                }, 10);
        },
		
		
		onthumbclick: function (event) {
			var o = $(event.currentTarget);

			var clink = o.data("link");
			var col = false;
			
			$(this.params.container).find(".emps-swipe-grid-col").each(function(){
				if($(this).data('link') == clink){
					col = $(this);
				}
			});

			if(!col){
				return;
			}
			

			
			this.change(this.current_row, col);
			
		},
		onclick: function (event) {
		},
		
        onmousedown: function (event) {
            // Trigger on clicks of the left mouse button only
            if (event.which && event.which === 1) {
                // Preventing the default mousedown action is required
                // to make touch emulation work with Firefox:
                event.preventDefault();
                (event.originalEvent || event).touches = [{
                    pageX: event.pageX,
                    pageY: event.pageY
                }];
                this.ontouchstart(event);
            }
        },
		
        onmousemove: function (event) {
            if (this.touch_start) {
                (event.originalEvent || event).touches = [{
                    pageX: event.pageX,
                    pageY: event.pageY
                }];
                this.ontouchmove(event);
            }
        },
		
        onmouseup: function (event) {
            if (this.touch_start) {
                this.ontouchend(event);
                delete this.touch_start;
            }
        },
		
        onmouseout: function (event) {
            if (this.touch_start) {
                var target = event.target,
                    related = event.relatedTarget;
                if (!related || (related !== target &&
                        !$.contains(target, related))) {
                    this.onmouseup(event);
                }
            }
        },
		
		touchreset: function () {
            // Reset delta values:
            this.touch_delta = {};
			this.moving_v = false;
			this.moving_h = false;
			this.peer_default = {};
			this.amount = 0;
			this.threshold = 0;
			this.current_peer = false;
			this.view_on = false;
			this.last_shadow = new Date().getTime();
			
		},
		
        ontouchstart: function (event) {
            if (this.params.stop_touch_propagation) {
                this.stopPropagation(event);
            }
            if (this.params.disable_scroll) {
                event.preventDefault();
            }			
            // jQuery doesn't copy touch event properties by default,
            // so we have to access the originalEvent object:
            var touches = (event.originalEvent || event).touches[0];
            this.touch_start = {
                // Remember the initial touch coordinates:
                x: touches.pageX,
                y: touches.pageY,
                // Store the time to determine touch duration:
                time: Date.now()
            };
			this.touchreset();
        },
		
        ontouchmove: function (event) {
            if (this.params.stop_touch_propagation) {
                this.stopPropagation(event);
            }
            if (this.params.disable_scroll) {
                event.preventDefault();
            }			
            // jQuery doesn't copy touch event properties by default,
            // so we have to access the originalEvent object:
            var touches = (event.originalEvent || event).touches[0],
                scale = (event.originalEvent || event).scale;
            // Ensure this is a one touch swipe and not, e.g. a pinch:
            if (touches.length > 1 || (scale && scale !== 1)) {
                return;
            }
            // Measure change in x and y coordinates:
            this.touch_delta = {
                x: touches.pageX - this.touch_start.x,
                y: touches.pageY - this.touch_start.y
            };
			
			this.move();
			
        },
		
		move: function(){
			var c = $(this.params.container);
			
			var x,y, sx,sy, px,py;
			x = this.touch_delta.x;
			y = this.touch_delta.y;
			
			if(!this.moving_v){
				if(Math.abs(x) > Math.abs(y) && Math.abs(x) > 50) {
					this.moving_h = true;
				}
			}

			var image = this.current_col;
			var peer = false;			
			var amount = 0, signed_amount = 0, threshold = 0;
			sx = x; sy = y;
			if(this.moving_h){
				sy = py = 0;
				if(sx < 0) {
					if((this.current_peer==false)){
						peer = $(this.current_col).next();
						if(this.params.circle_cols){
							if(peer.length == 0){
								 peer = $(this.current_col).siblings().first();
							}
						}
					}
					px = c.width() + sx;
					this.peer_default = 
					{
						y: 0,
						x: c.width()
					};
				}else{
					if((this.current_peer==false)){
						peer = $(this.current_col).prev();
						if(this.params.circle_cols){
							if(peer.length == 0){
								 peer = $(this.current_col).siblings().last();
							}
						}								
					}
					px = sx - c.width();
					this.peer_default = 
					{
						y: 0,
						x: (-c.width())
					};					
				}
				signed_amount = sx;
				amount = Math.abs(sx);
				threshold = c.width() * this.params.swipe_threshold;
				
				if(peer.length == 0){
					peer = false;
				}
			}
		
			if(!(peer == false) || !(this.current_peer == false)){
				if(this.current_peer == false){				
					this.current_peer = peer_o = peer[0];
					
				}else{
					peer_o = this.current_peer;
				}
				if(!this.view_on){
					image.style.zIndex = 100;
					image.style.display = "block";
					peer_o.style.zIndex = 100;
					peer_o.style.display = "block";
					this.view_on = true;
				}
				image.style.left = sx+"px";
				image.style.top = sy+"px";				
				peer_o.style.left = px+"px";
				peer_o.style.top = py+"px";								
				
				this.amount = amount;
				this.threshold = threshold;
				
			}else{
				this.current_peer = false;
				// there is no peer, but the user is swiping the image:
				// display shadow to indicate pressure
				if(this.moving_v || this.moving_h){
					var current_shadow = new Date().getTime();
					
//					alert((current_shadow-this.last_shadow));
					if(amount > 5){
						if((current_shadow-this.last_shadow)>50){
							var amounts;
							if(this.moving_h){
								amounts = signed_amount+"px 0 ";
							}else{
								amounts = "0 "+signed_amount+"px ";
							}
							this.last_shadow = new Date().getTime();
							image.style.boxShadow = amounts + amount + "px -"+amount+"px rgba(0,0,0,0.8) inset";
						}
					}else{
						image.style.boxShadow = "none";
					}
				}
			}
			
		},
		
		find_selected_col: function(o){
			var found = false;
			var that = this;
			o.find(".emps-swipe-grid-col").each(function(){
				if($(this).data('selected')=='selected'){
					found = $(this);
				}
			});
			return found;
		},
		
		finish_transition: function(){
			var c = $(this.params.container);
			
			var speed = this.get_speed();
			
//			alert(speed);
			
			var that = this;
			
			var target_pos = {}, peer_pos = {};
			
			var cx,cy;
			
			var image = $(this.current_col);
			
			var pos = image.position();
			cx = pos.left;
			cy = pos.top;
			
			var more_amount = 0;
			
			peer_pos = 
			{
				x: 0,
				y: 0
			};			
			
			if(this.moving_h){
				if(cx < 0) {
					target_pos =
					{
						y: 0,
						x: (-c.width())
					};
				}else{
					target_pos =
					{
						y: 0,
						x: c.width()
					};					
				}
				speed = speed * (c.width() / this.amount);
			}			
			
			if(this.moving_h || (this.moving_v && this.params.one_row)){
				$(this.current_col).data("selected", false);
				$(this.current_peer).data("selected", "selected");
			}
			
			if(this.moving_v || this.moving_h){
//				alert(JSON.stringify(target_pos)+" / "+JSON.stringify(pos)+" / speed:"+speed);
				
				this.animate(this.current_col, target_pos, speed, function()
				{
					c.find(".emps-swipe-grid-col")
						.css("top", "0")
						.css("left", "0")
						.css("box-shadow", "none");
					that.show_selected();
					that.params.onchange.call(that);
	
				});
				this.current_col.style.boxShadow = "none";
				this.animate(this.current_peer, peer_pos, speed, function(){});
			}

		},
		
		get_speed: function () {
			var c = $(this.params.container);
			var speed = this.params.default_speed.h;
			
			if(this.moving_h){
				speed = this.params.default_speed.h * Math.abs(this.touch_delta.x / c.width());
			}
			if(this.moving_v){
				speed = this.params.default_speed.v * Math.abs(this.touch_delta.y / c.height());
			}			
			return speed;
		},
		
		abort_transition: function () {
			var speed = this.get_speed();
			
			var that = this;
			
			this.animate(this.current_col,{x:0, y:0}, speed, function()
			{
				var c = $(this.params.container);				
				c.find(".emps-swipe-grid-col")
					.css("top", "0")
					.css("left", "0")
					.css("box-shadow", "none");
				that.show_selected();
	
			});
			this.current_col.style.boxShadow = "none";
			
			this.animate(this.current_peer, this.peer_default, speed, function(){});
			
		},
		
        ontouchend: function (event) {
            if (this.params.stop_touch_propagation) {
                this.stopPropagation(event);
            }
            if (this.params.disable_scroll) {
                event.preventDefault();
            }			
			delete this.touch_start;
			
			if((this.amount > this.threshold) && (!(this.current_peer == false))){
				this.finish_transition();
			}else{
				this.abort_transition();
			}
			

        },

        ontouchcancel: function (event) {
            if (this.touch_start) {
                this.ontouchend(event);
            }
        },
		

		change: function (row, col) {
			if(row != this.current_row){
				$(this.current_row).data("selected", false);				
				$(row).data("selected", "selected");
			}else{
				$(this.current_col).data("selected", false);					
			}
			$(col).data("selected", "selected");
			this.show_selected();
		}
		
	});
	
	$.fn.EMPS_IPBox = function(params) {
		var lst = [];
		this.each(function() {
			lst.push($(this));
		});
		
/*		$.each(lst, function(index, value){
			alert(value.attr("href"));
		});*/
		
		params['lst'] = lst;
		
		$("#ipb-wrapper").remove();

		$("body").append('<div id="ipb-wrapper"><div class="ipb-container"></div><div class="ipb-text"><div class="ipb-textvalue"></div></div><a class="ipb-galleft"></a><a class="ipb-galright"></a><a class="ipb-galclose"></a></div>');
		$("#ipb-wrapper").data('EMPS_IPBox', new EMPS_IPBox($("#ipb-wrapper"), params));		
		return this;
    }
})( jQuery );


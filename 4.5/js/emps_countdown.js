// JavaScript Document

(function( $ ){
	function EMPS_Countdown(params, object) {	
		// Constructor function
		this.params = $.extend(true, this.params, this.defaults);		
		this.params = $.extend(true, this.params, params);
		this.object = object;
		this.initialize();
	}
	
	// Class definition
    $.extend(EMPS_Countdown.prototype, {
		defaults: {
			seconds: 60,
			level: 3,
			onExpired: function(obj){},
			off: 'Expired!'
		},
		initialize: function(){
			var data_seconds = $(this.object).data("seconds");
			this.params.seconds = data_seconds;
			this.params.start_dt = this.get_time();
			this.show_formatted();
		},
		get_time: function(){
			return Math.floor(Date.now() / 1000);
		},
		two_digits: function(v){
			var text = v.toString();
			if(v<10){
				text = "0" + text;
			}
			return text;
		},
		show_formatted: function(){
			var elapsed = this.get_time() - this.params.start_dt;
			var left = this.params.seconds - elapsed;
			var text = "";
			if(left > 0){
				var seconds = this.two_digits(left % 60);
				var minutes = this.two_digits(Math.floor(left/60) % 60);
				var hours = this.two_digits(Math.floor(Math.floor(left / 60) / 60));
				if(this.params.level == 3){
					text = hours+":"+minutes+":"+seconds;
				}else if(this.params.level == 2){
					text = minutes+":"+seconds;
				}
			}else{
				text = this.params.off;
				if(!$(this.object).data('expired')){
					$(this.object).data('expired', true);
					this.params.onExpired.call(this.params.object);
				}
			}
			$(this.object).html(text);
			var that = this;
			setTimeout(function(){
				that.show_formatted();
			}, 1000);
		}
	});
	
	$.fn.EMPS_Countdown = function(params) {
		this.each(function() {
			$(this).data('EMPS_Countdown', new EMPS_Countdown(params, this));
		});
		
		return this;
    }
})( jQuery );


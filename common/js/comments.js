// JavaScript Document

function comment_answer(id){
	var box=$("#commbox");
	var comment=$("#comm_"+id);
	var spacer=$("#commsp_"+id);
	
	$(".commsp").css("width",0);
	$(".commsp").css("height",0);
	
	var width=box.width();
	var height=box.height()+20;
	
	spacer.css("width",width);
	spacer.css("height",height);
	

	$("#commdef").css("display","block");

	box.css("display","none");
	
	var where=spacer.position();	
	
	
	box.css("position","absolute");
	box.css("display","block");
	box.css("top",(where.top-10)+"px");
	box.css("left",where.left+"px");	
	box.css("width", width);
	
	$("#id_answer").val(id);	
}

function plain_comment(){
	$("#commdef").css("display","none");	
	$("#commbox").css("position","relative");	
	$("#commbox").css("top",0);	
	$("#commbox").css("left",0);		
	$(".commsp").css("width",0);
	$(".commsp").css("height",0);
	$("#id_answer").val(0);
}

function setCaretTo(obj, pos) {
	if(obj.createTextRange) {
		var range = obj.createTextRange();
		range.move('character', pos);
		range.select();
	} else if(obj.selectionStart) {
		obj.focus();
		obj.setSelectionRange(pos, pos);
	}
}

function insertAtCaret(obj, text) {
	var start;
	
	if(document.selection) {
		obj.focus();
		var orig = obj.value.replace(/\r\n/g, "\n");
		var range = document.selection.createRange();

		if(range.parentElement() != obj) {
			return false;
		}

		range.text = text;
		
		var actual = tmp = obj.value.replace(/\r\n/g, "\n");

		for(var diff = 0; diff < orig.length; diff++) {
			if(orig.charAt(diff) != actual.charAt(diff)) break;
		}

		start=diff+htext.length;
	} else if(!isNaN(obj.selectionStart)) {
		start = obj.selectionStart;
		var end   = obj.selectionEnd;

		obj.value = obj.value.substr(0, start) 
				+ text 
				+ obj.value.substr(end, obj.value.length);
	}
		
	if(start != null) {
		setCaretTo(obj, start + text.length);
	} else {
		obj.value += text;
	}
}

function enclose_comment_selection(a,b){
	var obj=$("#commta").get(0);
	var text,htext;
	var caret=-1;
	var c;
	
	if(document.selection) {
		obj.focus();
		var orig = obj.value.replace(/\r\n/g, "\n");
		var range = document.selection.createRange();

		if(range.parentElement() != obj) {
			return false;
		}

		text=range.text;
		var skip=false;
		var space="";
		c=text.charAt(text.length-1);
		if(c==' ' || c=='\r' || c=='\n'){
			skip=true;
			space=c;
			text=text.substr(0,text.length-1);
			
		}		
		htext=a+text;		
		text=htext+b+space;
		
		range.text = text;
		
		var actual = tmp = obj.value.replace(/\r\n/g, "\n");

		for(var diff = 0; diff < orig.length; diff++) {
			if(orig.charAt(diff) != actual.charAt(diff)) break;
		}

		caret=diff+htext.length;
	} else if(!isNaN(obj.selectionStart)) {
		var start = obj.selectionStart;
		var end   = obj.selectionEnd;
		
		text=obj.value.substr(start, end-start);
		c=text.charAt(text.length-1);
		if(c==' ' || c=='\r' || c=='\n'){
			end--;
		}
		
		text=a + obj.value.substr(start, end-start) + b ;
		htext=a + obj.value.substr(start, end-start) ;
		
		caret=start+htext.length;

		obj.value = obj.value.substr(0, start) 
				+ text 
				+ obj.value.substr(end, obj.value.length-end);
	}
		
	if(caret != -1) {
		setCaretTo(obj, caret);
	} else {
		obj.value += text;
	}
}

function comment_bold(){
	enclose_comment_selection("[b]","[/b]");
}

function comment_italic(){
	enclose_comment_selection("[i]","[/i]");
}

function quote_loaded(data,status){
	var obj=$("#commta").get(0);
	
	if(data.substr(0,5)=="ERROR"){
		return;
	}
	var x=data.split('|');
	
	insertAtCaret(obj,"[quote="+x[0]+"]"+x[1]+"[/quote]\r\n");
	
}

function comment_quote(id){
	$.ajax({
		type: "GET",
		url: "/commsource/"+id+"/",
		success: quote_loaded
		   });	
	
}

String.prototype.trim = function () {
    return this.replace(/^\s*/, "").replace(/\s*$/, "");
}

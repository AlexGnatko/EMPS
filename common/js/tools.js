// JavaScript Document

function _navigate(url){
	window.location=url;
}

function ask_do(text,url){
	if(confirm(text)){
		_navigate(url);
	}
}

function ask_do_target(text, url, target){
	if(confirm(text)){	
		$("#"+target).get(0).src = url;
	}
}

function ask_form(text,id){
	if(confirm(text)){
		f=document.getElementById(id);
		f.submit();
	}
}

function ask_kill(id){
	f=document.getElementById(id);
	if(confirm("Delete selected?")){
		f.submit();
	}
}

function ask_call(text,code){
	if(confirm(text)){
		eval(code);
	}	
}


function submit_form(el,id){
	var f=document.getElementById(id);
	var r;
	var h=f.getAttribute('onsubmit');
	
	if(h){
		r=f.onsubmit();
	}else{
		r=true;
	}
	if(!r){
		return;
	};
	
	el.disabled=true;
	f.contentEditable=false;
	f.submit();
}

var tl_array=false;

function transliterate(c){
	var src="A.B.C.D.E.F.G.H.I.J.K.L.M.N.O.P.Q.R.S.T.U.V.W.X.Y.Z."+
	"a.b.c.d.e.f.g.h.i.j.k.l.m.n.o.p.q.r.s.t.u.v.w.x.y.z."+
	"1.2.3.4.5.6.7.8.9.0.А.Б.В.Г.Д.Е.Ё.Ж.З.И.Й.К.Л.М.Н.О.П.Р.С.Т.У.Ф.Х.Ц.Ч.Ш.Щ.Ъ.Ы.Ь.Э.Ю.Я."+
			"а.б.в.г.д.е.ё.ж.з.и.й.к.л.м.н.о.п.р.с.т.у.ф.х.ц.ч.ш.щ.ъ.ы.ь.э.ю.я";
	var dest=	"a.b.c.d.e.f.g.h.i.j.k.l.m.n.o.p.q.r.s.t.u.v.w.x.y.z."+
		"a.b.c.d.e.f.g.h.i.j.k.l.m.n.o.p.q.r.s.t.u.v.w.x.y.z."+
			"1.2.3.4.5.6.7.8.9.0.a.b.v.g.d.e.yo.zh.z.i.y.k.l.m.n.o.p.r.s.t.u.f.kh.c.ch.sh.sch.y.y.y.e.yu.ya."+
			"a.b.v.g.d.e.yo.zh.z.i.y.k.l.m.n.o.p.r.s.t.u.f.kh.c.ch.sh.sch.y.y.y.e.yu.ya";
	if(!tl_array){
		var x=src.split('.');
		var y=dest.split('.');
		var l=x.length;
		tl_array=new Array(l);
		for(var i=0;i<l;i++){
			tl_array[x[i]]=y[i];
		}
	}
	
	if(tl_array[c]){
		return tl_array[c];
	}
	
	if(c==' ' || c=='-' || c=='_' || c==':' || c=='*'){
		return '-';
	}
	
	if(c=='\'' || c=='"'){
		return "";
	}
	
	if(c==',' || c==';'){
		return ',';
	}
	
	return '.';
}

function transliterate_url(source,dest){
	var s=$("#"+source).val();
	var t="";
	var l=s.length;
	var c,pc;
	for(i=0;i<l;i++){
		c=s.substr(i,1);
		var tc=transliterate(c);
		if((pc=='-' || pc=='.' || pc==',') && (tc=='-' || tc=='.' || tc==',')){
			continue;
		}
		pc=tc;
		t=t+tc;
	}
	l=t.length;
	var lc=t.substr(l-1,1);
	if(lc=='.' || lc==',' || lc=='-'){
		t=t.substr(0,l-1);
	}
	$("#"+dest).val(t);
}

function transliterate_text(s){
    var t="";
    var l=s.length;
    var c,pc;
    for(i=0;i<l;i++){
        c=s.substr(i,1);
        var tc=transliterate(c);
        if((pc=='-' || pc=='.' || pc==',') && (tc=='-' || tc=='.' || tc==',')){
            continue;
        }
        pc=tc;
        t=t+tc;
    }
    l=t.length;
    var lc=t.substr(l-1,1);
    if(lc=='.' || lc==',' || lc=='-'){
        t=t.substr(0,l-1);
    }
    return t;
}

function print_window(url){
	var w = window.open(url,"printversion","left=100,top=100,width=850,height=700,location=no,status=no,resizable=yes,scrollbars=yes,menubar=yes",false);
	w.focus();
}

function sink(){
}

function report_banner_show(id, pos){
	$.ajax("/banners-show/"+pos+"/?time="+Date.now(),{success: function(){
	}});
}

function voidClick(){
}


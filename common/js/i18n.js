function i18n(init){
    var x = init.split(",");
    for(i in x){
        this[x[i]] = {};
    }
}

i18n.prototype.get = function(v){
    var lang = $lang;
    if(this[lang] === undefined){
        lang = "nn";
        if(this[lang] === undefined){
            return "";
        }
    }
    if(this[lang][v] === undefined){
        lang = "nn";
        if(this[lang][v] === undefined){
            return "";
        }
    }
    return this[lang][v];
}

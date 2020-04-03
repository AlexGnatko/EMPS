
var EMPS = {
    enum_cache: {},
    scroll_data: {},
    get_path_vars: function(){
        var l = window.location.href;
        var p = l.split('//');
        var l = p[1].split('/');
        var last = l.pop();
        if (last != ''){
            l.push(last);
        }
        var rv = {};
        rv['pp'] = l[1];
        rv['key'] = l[2];
        rv['start'] = l[3];
        rv['ss'] = l[4];
        rv['sd'] = l[5];
        rv['sk'] = l[6];
        rv['sm'] = l[7];
        rv['sx'] = l[8];
        rv['sy'] = l[9];
        for(var v in rv){
            if(rv[v] == '-'){
                rv[v] = undefined;
            }
        }
        return rv;
    },
    elink: function(define, undefine){
        var path = this.get_path_vars();
        for (var v in define) {
            path[v] = define[v];
        }
        for (var i = 0; i < undefine.length; i++) {
            path[undefine[i]] = undefined;
        }
        return this.link(path);
    },
    link: function(path){
        var rv = [];
        var vars = ['pp', 'key', 'start', 'ss', 'sd', 'sk', 'sm', 'sx', 'sy'];
        for (var i = 0; i < vars.length; i++){
            if (path[vars[i]] !== undefined) {
                rv.push(path[vars[i]])
            }else{
                rv.push('-');
            }
        }
        while (rv.length > 0) {
            var c = rv.pop();
            if (c != '-') {
                rv.push(c);
                break;
            }
        }
        var url = rv.join("/");
        if (url) {
            return "/" + url + "/";
        }
        return "/";
    },
    soft_navi: function(title, href) {
        window.history.pushState([], title, href);
    },
    load_css: function(href) {
        var head  = document.getElementsByTagName('head')[0];
        var link  = document.createElement('link');
        link.rel  = 'stylesheet';
        link.type = 'text/css';
        link.href = href;
        link.media = 'all';
        head.appendChild(link);
    },
    load_js: function(src, target, after) {
        var tag = document.createElement('script');
        tag.src = src;

        tag.onload = after;
        tag.onreadystatechange = after;

        target.appendChild(tag);
    },
    format_size: function(bytes) {
        var units = [
            {size: 1000000000, suffix: ' GB'},
            {size: 1000000, suffix: ' MB'},
            {size: 1000, suffix: ' KB'}
        ];

        if (typeof bytes !== 'number') {
            return '';
        }
        var unit = true,
            i = 0,
            prefix,
            suffix;
        while (unit) {
            unit = units[i];
            prefix = unit.prefix || '';
            suffix = unit.suffix || '';
            if (i === units.length - 1 || bytes >= unit.size) {
                return prefix + (bytes / unit.size).toFixed(2) + suffix;
            }
            i += 1;
        }
    },
    load_enum: function(code, then) {
        if (this.enum_cache[code] !== undefined) {
            then(this.enum_cache[code]);
            return;
        }
        var that = this;
        axios
            .get("/json-loadenum/" + code + "/")
            .then(function(response){
                var data = response.data;
                if (data.code == 'OK') {
                    if (then !== undefined) {
                        that.enum_cache[code] = data.enum;
                        then(data.enum);
                    }
                }else{
                    alert(data.message);
                }
            });
    },
    load_enum_str: function(code, then) {
        if (this.enum_cache[code] !== undefined) {
            then(this.enum_cache[code]);
            return;
        }
        var that = this;
        axios
            .get("/json-loadenum/" + code + "/?string=1")
            .then(function(response){
                var data = response.data;
                if (data.code == 'OK') {
                    if (then !== undefined) {
                        that.enum_cache[code] = data.enum;
                        then(data.enum);
                    }
                }else{
                    alert(data.message);
                }
            });
    },
    login: function() {
        $("#siteLoginModal").addClass("is-active");
        $.ajax({url: '/ensure_session/'});
    },
    open_modal: function(s) {
        $(s).addClass("is-active");
    },
    close_modal: function(s) {
        $(s).removeClass("is-active");
    },
    into_view: function(selector) {
        var $target = $(selector);
        if ($target.position()) {
            if (


                (
                    $target.position().top + ((
                        window.innerHeight || document.documentElement.clientHeight
                    ) / 3) >
                    $(window).scrollTop() + (
                        window.innerHeight || document.documentElement.clientHeight
                    )
                ) ||
                (
                    $(window).scrollTop() > ($target.offset().top + 50)
                )


            )
            {
                $(window).scrollTop($(selector).offset().top - 50);
            }
        }
    },
    scroll_to_pos: function(selector) {
        var key = JSON.stringify(this.get_path_vars());
        var value = this.scroll_data[key];
        if (value !== undefined) {
            $(window).scrollTop(value);
//            console.log("Scroll restored: " + value + " / " + key);
        } else {
            this.into_view(selector);
        }
    },
    save_scroll_pos: function() {
        var key = JSON.stringify(this.get_path_vars());
        var value = $(window).scrollTop();
        this.scroll_data[key] = value;
//        console.log("Scroll saved: " + value + " / " + key);
    },
    get_context: function(ref_type, ref_sub, ref_id, then) {
        if (ref_type <= 0) {
            return 0;
        }
        if (ref_sub <= 0) {
            return 0;
        }
        if (ref_id <= 0) {
            return 0;
        }
        axios
            .get("/json-context/" + ref_type + "-" + ref_sub + "-" + ref_id + "/")
            .then(function(response){
                var data = response.data;
                if (data.code == 'OK') {
                    if (then !== undefined) {
                        then(data.context_id);
                    }
                }else{
                    alert(data.message);
                }
            });
    }
}
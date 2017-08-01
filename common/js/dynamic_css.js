
$("link[media=none]").each(function(idx){
    var link = $(this);
    if(link.data('dynamic')){
        link.prop('media', 'screen');
    }
});

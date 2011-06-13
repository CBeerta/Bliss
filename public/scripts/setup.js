(function ($) {


$(document).endlessScroll({
    fireOnce: true,
    bottomPixels: 300,
    fireDelay: 250,
    loader: '<div class="loading"></div>',
    callback: function(p){
        console.log("Loading");
        var last_id = $('article').last().attr('id');
        $.get("load_next/" + last_id, function(data) {
            //console.log(data);
            $("#" + last_id).append(data);
        
        });
    }
});



})(jQuery);



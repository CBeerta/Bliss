(function ($) {

$(document).endlessScroll({
    fireOnce: false,
    fireDelay: 250,
    bottomPixels: 300,
    callback: function(p) {
        var last_id = $('article').last().attr('id');
        $.get("load_next/" + last_id, function(data) {
            $("#" + last_id).after(data);
        });
    }
});

})(jQuery);

(function ($) {

function in_array (string, array) {
    for (i in array) if(array[i] == string) return true;
    return false;
};

$(document).endlessScroll({
    fireOnce: true,
    fireDelay: 250,
    bottomPixels: 100,
    callback: function(p) {
    
        var idlist = [];
        $('article').each(function(article) {
            idlist.push($(this).attr('id'));
        });
        
        var last_id = $('article').last().attr('id');

        $.ajax({
            type: "POST",
            url: "load_next",
            data: { 'last_id': last_id, 'idlist': idlist },
            success: function(data) {
                $("#" + last_id).after(data);
            }
        });
    }
});


})(jQuery);

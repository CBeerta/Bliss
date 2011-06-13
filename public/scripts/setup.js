(function ($) {

$('.pulldown #spinner').ajaxStart(function() {
    $(this).show();
}).ajaxStop(function() {
    $(this).hide();
});


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

$('.pulldown #handle').click(function() {
    $('.pulldown #options').slideToggle('fast');
});

$("#options").submit(function() {
        var uri = $('form #add_feed').attr('value');  
        
        $.ajax({  
            type: "POST",
            url: "add_feed",
            dataType: 'json',
            data: { 'uri': uri, '_METHOD': 'PUT' },
            success: function(reply)
            {
                switch (reply['status']) {
                case 'OK':
                    $('.feedback').css('background-color', 'rgba(30, 120, 30, 0.8)');
                    $('.feedback').html(reply['message']);
                    $('.pulldown #options').slideToggle('fast');
                    break;

                default:
                    $('.feedback').css('background-color', 'rgba(120, 30, 30, 0.8)');
                    $('.feedback').html(reply['message']);
                    break;
                }
                
                $('.feedback').fadeIn('slow');
                $('.feedback').delay(1500).fadeOut('slow');
                $('form #add_feed').attr('value', '');  
            }
        });          
        
        
        
        return false;
});


})(jQuery);

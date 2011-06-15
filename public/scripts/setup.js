/**
* Like PHP's in_array
*
* @param string $string needle
* @param array  $array  haystack
*
* return bool
**/
function in_array (string, array) {
    for (i in array) if(array[i] == string) return true;
    return false;
};

/**
* Initialy Page Load completed
**/
$(document).ready(function() {

    /**
    * Show spinner when we do ajaxy stuff
    **/
    $('.pulldown #spinner').ajaxStart(function() {
        $(this).show();
    }).ajaxStop(function() {
        $(this).hide();
    });

    /**
    * Show or Hide the "Options" panel
    **/
    $('.pulldown #handle').click(function() {
        $('.pulldown #options').slideToggle('fast');
    });

    /**
    * Submit a new Feed Url
    **/
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

    
});

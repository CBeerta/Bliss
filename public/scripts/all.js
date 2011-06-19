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

function success_or_fail(status, msg) {
    switch(status) {
    case 'OK':
        $('.feedback').css('background-color', 'rgba(30, 120, 30, 0.8)');
        $('.feedback').html(msg);
        $('.feedback').fadeIn('slow');
        $('.feedback').delay(5000).fadeOut('slow');
        return true;
    default:
        $('.feedback').css('background-color', 'rgba(120, 30, 30, 0.8)');
        $('.feedback').html(msg);
        $('.feedback').fadeIn('slow');
        $('.feedback').delay(5000).fadeOut('slow');
        return false;
    }
}


function delete_feed(uri, id) {
    var answer = confirm("Are You sure You want to Remove: \n" + uri);
    
    if (!answer) {
        return;
    }    

    $.ajax({  
        type: "POST",
        url: "/remove_feed",
        dataType: 'json',
        data: { 'uri': uri, '_METHOD': 'DELETE' },
        success: function(reply) {
            if (success_or_fail(reply['status'], reply['message'])) {
                ($('tr#' + id)).fadeOut("slow", function(){$(this).remove();});
            }
        }
    });
}


/**
* Initialy Page Load completed
**/
$(document).ready(function() {

    /**
    * Show spinner when we do ajaxy stuff
    **/
    $('#spinner')
    .hide()  // hide it initially
    .ajaxStart(function() {
        $(this).show();
    })
    .ajaxStop(function() {
        $(this).hide();
    });

    /**
    * Activate fancybox if its loaded
    **/
    if (window.jQuery.fancybox) {
        $('a.fancyme').fancybox({
            'titlePosition': 'below',
            'hideOnContentClick': true,
            'centerOnScroll': true,
        	'padding'			: 0,
			'transitionIn'		: 'none',
			'transitionOut'		: 'none',
			'type'              : 'image',
			'changeFade'        : 0
        });
    }
    
    /**
    * Show or Hide the "Options" panel
    **/
    $('.pulldown #handle').click(function() {
        $('.pulldown #options').toggle();
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
                if (success_or_fail(reply['status'], reply['message'])) {   
                    $('.pulldown #options').slideToggle('fast');
                    $('form #add_feed').attr('value', '');  
                }
            }
        });          
        return false;
    });

    
});

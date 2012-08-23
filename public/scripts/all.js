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

/**
* Delete a Feed
**/
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
        $(this).fadeOut();
    });

    /**
    * Move the input box to our add feed icon
    **/    
    $('.pulldown #options').css(
        'top', 
        Number($('.pulldown #handle').offset().top) - 5 + 'px'
    );
    
    /**
    * Show or Hide the "Options" panel
    **/
    $('.pulldown #handle').unbind("click");
    $('.pulldown #handle').click(function(e) {
        $('.pulldown #options').fadeToggle();
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

    /**
    * Handle Mouse Clicks in the Window
    **/
    $('body').click(function(event) {
        parent = $(event.target).parent();
        
        if ($(event.target).is('.pulldown #handle')) {
            return false;
        } else if ($(event.target).is('.pulldown img')) {
            document.title = "Bliss - " + $(event.target).attr('title');
        } else if ($(event.target).is('.updater')) {
            fillPage();
        } else if ($(event.target).is('article header .flag')) {
            var id = $(event.target).attr('name');
            $.ajax({
                type: "POST",
                url: "flag",
                dataType: 'json',
                async: true,
                data: { 'name': id },
                success: function(data) {
                    $(event.target).attr('src', data);
                }
            });
        } else if ($(parent).is('.enclosures .fancyme')) {
            $.fancybox({
                'padding'		: 0,
                'href'			: $(parent).attr('href'),
                'transitionIn'	: 'elastic',
                'transitionOut'	: 'elastic'
            });        
            return false;
        } else if ($(event.target).is('article img')
            && $(parent).is('article a')
            && $(parent).attr('href').match(/.*\.(jpg|jpeg|png|gif|bmp).*/i)
            && !$(parent).attr('href').match(/\?/)
        ) {
            /**
            * Check if clicked link is a img that links to an image
            * and run fancybox on it if it is
            **/
            $.fancybox({
                'padding'		: 0,
                'href'			: $(parent).attr('href'),
                'transitionIn'	: 'elastic',
                'transitionOut'	: 'elastic'
            });        
            return false;
        } else if ($(event.target).is('article a')) {
            // Force article links to open  in a new window
            window.open($(event.target).attr('href'));
            return false;
        } else if ($(event.target).is('article img')
            && $(parent).is('article a')
        ) {
            // Force article links to open in a new window
            window.open($(parent).attr('href'));
            return false;
        }
    });
    
});

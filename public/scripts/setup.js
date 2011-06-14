(function ($) {

/**
* Show spinner when we do ajaxy stuff
**/
$('.pulldown #spinner').ajaxStart(function() {
    $(this).show();
}).ajaxStop(function() {
    $(this).hide();
});

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
* Figure out what articles we have, and load the next available after that
*
* return void
**/
function loadNext() {
    var idlist = [];
    $('article').each(function(article) {
        idlist.push($(this).attr('id'));
    });

    var last_id = $('article').last().attr('id');
    if ( !last_id ) {
        last_id = Math.round(Date.now() / 1000);
    }

    $.ajax({
        type: "POST",
        url: "load_next",
        async: false,
        data: { 'last_id': last_id, 'idlist': idlist },
        success: function(data) {
            if ($("#" + last_id).length == 0) {
                // First item, insert into content
                $("#content").html(data);
            } else {
                // append
                $("#" + last_id).after(data);
            }
        }
    });
}

/**
* Continuously poll for updates
*
* return void
**/
function poll() {
    first_id = $('article').first().attr('id');

    $.ajax({
        type: "POST",
        url: "poll",
        async: false,
        dataType: 'json',
        data: { 'first_id': first_id },
        success: function(data) {
            if (data['updates_available'] == true) {
                $('.updater').html('New Articles Available!');
                $('.updater').fadeIn('slow');
            }
        }
    })
}

/**
* Setup endlessScroll
**/
$(document).endlessScroll({
    fireOnce: true,
    fireDelay: 250,
    bottomPixels: 100,
    callback: function(p) {
        loadNext();
    }
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

/**
* Initialy Page Load completed
**/
$(document).ready(function() {
    /**
    * Lets initially load the first items
    * Halt at 10 items to prevent an endless loop
    **/
    for (var i=0 ; i<= 10 ; i ++) {
        var footer = $('footer').offset();
        loadNext();
        // Check if the footer scrolled outside viewport, and break initial load.
        // the rest is done by endless scroll
        if (footer.top > $(window).height()) break;
    }
    
    /* Setup the poller */
    window.setInterval(poll, 60000);
});

})(jQuery);

/**
* Figure out what articles we have, and load the next available after that
*
* return void
**/
function loadNext() {
    var idlist = [];
    $('article.bliss-article').each(function(article) {
        idlist.push($(this).attr('id'));
    });

    var last_id = $('article').last().attr('id');
    if ( !last_id ) {
        last_id = Math.round(Date.now() / 1000);
    }
    
    var filter = unescape(self.document.location.hash.substring(1));
    
    if (document.there_is_no_more != undefined) {   
        // prevent going back any further without having anything
        return false;
    }

    var response = $.ajax({
        type: "POST",
        url: "load_next/" + filter,
        async: false,
        data: { 'last_id': last_id, 'idlist': idlist },
        success: function(data) {
            if (data.length == 0) {
                return false;
            }
            
            if ($("article.bliss-article#" + last_id).length == 0) {
                // First item, insert into content
                $("#content").html(data);
            } else {
                // append
                $("article.bliss-article#" + last_id).after(data);
            }
        }
    }).responseText;
        
    if (response.length == 0) {
        document.there_is_no_more = true;
        return false;
    }

    return true;
}

/**
* Lets initially load the first items
* Halt at 20 items to prevent an endless loop
*
* return void
**/
function fillPage() {
    // remove all articles, if any
    $('article.bliss-article').remove();
    
    // reset limit
    document.there_is_no_more = undefined;
    
    for (var i=0 ; i<= 30 ; i++) {
        var footer = $('footer').offset();
        if (!loadNext(false)) break;

        // Check if we actually loaded anything at all, and stop if we didn't
        if (!$('article.bliss-article').last().attr('id')) break;
        
        // Check if the footer scrolled outside viewport, and break initial load.
        // the rest is done by endless scroll
        if (footer.top > $(window).height()) break;
    }
    
    if (i == 0) {
        // Nothing Loaded
        var filter = unescape(self.document.location.hash.substring(1));
        $.get('nothing/' + filter, function(data) {
            $("#content").html(data);
        });
    } else {
        $(document).trigger('scroll');
    }
}

function checkRead(id) {
    var ele = document.elementFromPoint(300, 40);
    var current_id = $(ele).closest('article.bliss-article');

    if (current_id.length != 0 && id == $(current_id).attr('id')) {
        markRead(id);
    }

}
/**
* Check if user is still over the reported article id
* if so, send ajax request to mark article as read
**/
function markRead(id) {
    var current_id = $("article.bliss-article#" + id);

    if (current_id.length != 0 && id == $(current_id).attr('id')) {
        
        if ($(current_id).hasClass('unread')) {

            $.ajax({
                type: "POST",
                url: "read",
                async: true,
                dataType: 'json',
                data: { 'name': $(current_id).attr('name') },
                success: function(data) {
                    if (data != null) {
                        $(current_id).removeClass('unread');
                    }
                }
            });

        }
    }
}

/**
* Continuously poll for updates
*
* return void
**/
function poll() {
    first_id = $('article').first().attr('id');
    if ( !first_id ) {
        // Currently no article on display, so poll from day 0
        first_id = 0;
    }

    var filter = unescape(self.document.location.hash.substring(1));

    $.ajax({
        type: "POST",
        url: "poll/" + filter,
        async: true,
        dataType: 'json',
        data: { 'first_id': first_id },
        success: function(data) {
            if (data['updates_available'] == true) {
                $('.updater').fadeIn('slow');
            }
        }
    })
}

/**
* Initialy Page Load completed
**/
$(document).ready(function() {
    
    /**
    * Handle Keyboard navigation
    **/
    $(window).keypress(function(event) {
        
        // Ignore keypresses when in input masks
        if ($(event.target).is('input, textarea')) {
            return;
        }

        // Find current first article        
        var ele = document.elementFromPoint(300, 40);
        var current_id = $(ele).closest('article.bliss-article');

        // finde article above and below
        if (current_id.length != 0) {
            var prev = $(current_id).prev('article.bliss-article');
            var next = $(current_id).next('article.bliss-article');

            if (next.length == 0 && event.which == 110) {
                // check if there is one to follow, if not, load one
                loadNext();
                var next = $(current_id).next('article');
            }

            // Find the positions of the next and previous articles        
            var prev_pos = $(prev).position();
            var next_pos = $(next).position();
        }
        
        // check which key was pressed
        switch (event.which) {
        /*
        case 110: // 'n'
            if (next_pos != null) {
                $(window).scrollTop(next_pos.top);
            }
            break;
        case 112: // 'p'
            if (prev_pos != null) {
                $(window).scrollTop(prev_pos.top);
            }
            break;
        */
        case 114: // 'r'
            fillPage();
            break;
        /*
        default:
            console.log(event.which);
            break;
        */
        }
        
    });
    
    /**
    * Watch for scroll events, record currently active article
    * And recheck after x seconds if user is still hovering over that
    * article
    **/
    $(document).scroll(function() {
        var ele = document.elementFromPoint(300, 40);
        var current_id = $(ele).closest('article.bliss-article');
        
        if (current_id.length != 0) {
            setTimeout("checkRead(" + $(current_id).attr('id') + ")", 1000);
        }

        if  ($(window).scrollTop() == $(document).height() - $(window).height()){
           loadNext();
        }

        $('article.bliss-article.unread').each(function () { 
            if($(this).offset().top + $(this).height() + 0 < $(window).scrollTop()) {       
                markRead($(this).attr('id'));
            }
        });
    });
   
    /**
    * Fetch hash changes, and reload articles if needed
    **/
    window.onhashchange = function() {
        fillPage();
    };

    /* Fill the size initially */
    fillPage();
    
    /* Setup the poller */
    window.setInterval(poll, 60000);

});

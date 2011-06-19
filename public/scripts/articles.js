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

    var response = $.ajax({
        type: "POST",
        url: "load_next/" + filter,
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
    }).responseText;
        
    if (!response) {
        return false;
    }

    return true;
}

/**
* Lets initially load the first items
* Halt at 10 items to prevent an endless loop
*
* return void
**/
function fillPage() {
    // remove all articles, if any
    $('article').remove();

    for (var i=0 ; i<= 10 ; i ++) {
        var footer = $('footer').offset();
        if (!loadNext()) break;

        // Check if we actually loaded anything at all, and stop if we didn't
        if (!$('article.bliss-article').last().attr('id')) break;
        
        // Check if the footer scrolled outside viewport, and break initial load.
        // the rest is done by endless scroll
        if (footer.top > $(window).height()) break;
    }
}


/**
* Continuously poll for updates
*
* return void
**/
function poll() {
    first_id = $('article').first().attr('id');

    var filter = unescape(self.document.location.hash.substring(1));

    $.ajax({
        type: "POST",
        url: "poll/" + filter,
        async: false,
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
    * Handle Keyboard navigation
    **/
    $(window).keypress(function(event) {
        
        // Ignore keypresses when in input masks
        if ($(event.target).is('input, textarea')) {
            return;
        }

        // Find current first article        
        var ele = document.elementFromPoint(150, 100);
        var current_id = $(ele).closest('article.bliss-article');

        // finde article above and below
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
        
        // check which key was pressed
        switch (event.which) {
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
    
    $('body').click(function(event) {
        if ($(event.target).is('article header .flag')) {
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
        } else if ($(event.target).is('.updater')) {
            fillPage();
            $('.updater').fadeOut("slow");
        }
    });
    
    /**
    * Fetch hash changes, and reload articles if needed
    **/
    window.onhashchange = function() {
        $('article').remove();
        fillPage();
    };

    /* Fill the size initially */
    fillPage();
    
    /* Setup the poller */
    window.setInterval(poll, 60000);
});

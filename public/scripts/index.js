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
    
    var filter = unescape(self.document.location.hash.substring(1));
    
    $.ajax({
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
        }
    });
    
    /**
    * Fetch hash changes, and reload articles if needed
    **/
    window.onhashchange = function() {
        $('article').remove();
        loadNext();
    };


    /**
    * Lets initially load the first items
    * Halt at 10 items to prevent an endless loop
    **/
    for (var i=0 ; i<= 10 ; i ++) {
        var footer = $('footer').offset();
        loadNext();
        // Check if we actually loaded anything at all, and stop
        if (!$('article').last().attr('id')) break;
        // Check if the footer scrolled outside viewport, and break initial load.
        // the rest is done by endless scroll
        if (footer.top > $(window).height()) break;
    }
    
    /* Setup the poller */
    window.setInterval(poll, 60000);
});

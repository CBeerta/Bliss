function loadNext() {

    var hash_page = unescape(self.document.location.hash.substring(1));
    var last_page = $('.gallery-page').last().attr('page');
    
    if (last_page == undefined) {
        if (hash_page != '') {
            var page = Number(hash_page);
        } else {
            var page = 0;
        }
        //self.document.location.hash = page;
    } else {
        var page = Number(last_page);
        page++;
        self.document.location.hash = page;
    }
    
    var response = $.ajax({ 
        type: "POST",
        url: "gallery_page/" + page,
        async: false,
        data: { 'page': page },
        success: function(data) {
            if ($(".gallery-page").attr('page') == undefined) {
                // First item, insert into content
                $("#content").html(data);
            } else {
                // append
                $(".gallery-page").last().after(data);
            }
        }
    }).responseText;

    if (response.length <= 100) {
        if (page > 0) {
            /* Nothing loaded, must be last page */
            self.document.location.hash = page - 1;
        }
        return false;
    }

    $(".fancyme").fancybox({
        'hideOnContentClick': true,
        'padding'           : 0,
    	'transitionIn'		: 'none',
		'transitionOut'		: 'none',
		'autoScale'     	: true,
		'type'				: 'image',
		'scrolling'   		: 'no',
		'changeFade'        : 0,
		'centerOnScroll'    : true
	});

    return true;
}

/**
* Initialy Page Load completed
**/
$(document).ready(function() {

    $(document).endlessScroll({
        fireOnce: true,
        fireDelay: 250,
        bottomPixels: 100,
        callback: function(p) {
            loadNext();
        }
    });

    /**
    * Initially load us one page
    **/
    loadNext();

/*
$(function() {
    $("img").lazyload( { placeholder : "{$base_uri}public/reload.png" } );
} );
*/


});

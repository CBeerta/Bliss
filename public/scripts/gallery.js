function loadNext() {

    var last_page = $('.gallery-page').last().attr('page');

    if (document.there_is_no_more != undefined) {   
        // prevent going back any further without having anything
        return false;
    }
    
    if (last_page == undefined) {
        var page = 0;
    } else {
        var page = Number(last_page);
        page++;
    }
    
    var response = $.ajax({ 
        type: "POST",
        url: "gallery_page/" + page,
        async: false,
        data: { 'page': page },
        success: function(data) {

            if (data.length == 0) {
                document.there_is_no_more = true;
                return false;
            }

            if ($(".gallery-page").attr('page') == undefined) {
                // First item, insert into content
                $("#content").html(data);
            } else {
                // append
                $(".gallery-page").last().after(data);
            }

        }
    }).responseText;
    
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

    
    /**
    * Initially load us one page
    **/
    loadNext();

    $(document).scroll(function() {
        if  ($(window).scrollTop() == $(document).height() - $(window).height()){
            loadNext();
        }
    });


});

jQuery(document).ready(function($) {
    var frame;

    $('#pterotype_blog_icon_button').on('click', function(event) {
	event.preventDefault();

	if (frame) {
	    frame.open();
	    return;
	}

	frame = wp.media({
	    title: 'Select an image',
	    button: {
		text: 'Use this image'
	    },
	    multiple: false
	});

	frame.on('select', function() {
	    var attachment = frame.state().get('selection').first().toJSON();
	    $('#pterotype_blog_icon_image').attr('src', attachment.url);
	    $('#pterotype_blog_icon').attr('value', attachment.url);
	});

	frame.open();
    });
});

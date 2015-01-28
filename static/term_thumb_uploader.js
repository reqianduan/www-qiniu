jQuery(document).ready(function($){

    var custom_uploader;

    $('#thumbnail_button').on('click',function(e) {
 
        e.preventDefault();
 
        //If the uploader object has already been created, reopen the dialog
        if (custom_uploader) {
            custom_uploader.open();
            return;
        }
 
        //Extend the wp.media object
        custom_uploader = wp.media.frames.file_frame = wp.media({
            title: '选择图片',
            button: {text: '选择图片'},
            multiple: false
        });
 
        //When a file is selected, grab the URL and set it as the text field's value
        custom_uploader.on('select', function() {
            attachment = custom_uploader.state().get('selection').first().toJSON();
            $('#thumbnail').val(attachment.url);
        });
 
        //Open the uploader dialog
        custom_uploader.open();

    });
});
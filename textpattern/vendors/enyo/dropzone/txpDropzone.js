jQuery.fn.txpDropzone = function (options) {
    options = $.extend({
        paramName: 'thefile',
        params : {app_mode: 'async'},
        maxFiles: 12,
        maxFilesize : 2,
        uploadMultiple: true,
        autoProcessQueue: false,
        addRemoveLinks: true,
        previewsContainer: '.dropzone-previews',
/*        successmultiple: function() {
            reset.click();
        },*/
        init: function() {
            var dz = this;
            dz.on('addedfile', function(file) {
                previews.show()
                reset.show()
                upload.val(uploadText)
            })
            reset.on('click', function() {
                dz.removeAllFiles()
                previews.hide()
                reset.hide()
                upload.val(selectText)
            }).click()
            dropform.on('submit', function(e) {
                e.preventDefault()
                if (dz.files.length) {
                    dz.processQueue()
                } else {
                    previews.click()
                }
            })
        },
        success : function(file, response) {
            eval(response)
        }
    }, options)

    var dropform = $(this),
        reset = $('<input type="reset" />').hide(),
        upload = dropform.find('input[type=submit]').after(reset),
        uploadText = upload.val(),
        selectText = textpattern.gTxt('select'),
        previews = $(options.previewsContainer).hide()

    dropform.off('submit').find('input[type=file]').remove()

    options.clickable = previews.toArray()
    options.parallelUploads = options.maxFiles

    dropform.dropzone(options)

    return dropform
}

jQuery.fn.txpDropzone = function (options) {
    if (!jQuery.fn.dropzone) return this

    options = $.extend({
        params : {app_mode: 'async'},
        maxFiles: 12,
        maxFilesize : 2,
        autoProcessQueue: false,
        addRemoveLinks: true,
        previewsContainer: '.dropzone-previews',
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
            reset.click()
            eval(response)
        }
    }, options)

    var dropform = $(this),
        fileinput = dropform.find('input[type=file]'),
        reset = $('<input type="reset" />').hide(),
        upload = dropform.find('input[type=submit]').after(reset),
        uploadText = upload.val(),
        selectText = textpattern.gTxt('select'),
        previews = $(options.previewsContainer).hide()

    options.clickable = previews.toArray()
    options.parallelUploads = options.maxFiles
    options.paramName = options.paramName || fileinput.attr('name').replace(/\[\]$/, '') || 'thefile'

    if (typeof options.uploadMultiple === 'undefined') {
        options.uploadMultiple = fileinput.attr('multiple') ? true : false
    }

    fileinput.remove()
    dropform.off('submit').dropzone(options)

    return dropform
}

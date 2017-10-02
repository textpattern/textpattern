jQuery.fn.txpFileupload = function (options) {
    if (!jQuery.fn.fileupload) return this

    var form = this, maxChunkSize = options.maxChunkSize || 2000000

    form.fileupload($.extend({
        url: form.attr('action')+'?app_mode=async',
        dataType: 'html',
//            paramName: 'thefile',
//            autoUpload: false,
        maxChunkSize: maxChunkSize,
        fileInput: null,
        done: function (e, data) {
            textpattern.Relay.callback('uploadEnd', data)
            eval(data.result)
        },
        progressall: function (e, data) {
            textpattern.Relay.callback('uploadProgress', data)
        },
        start: function (e, data) {
            textpattern.Relay.callback('uploadStart', data)
        }
    }, options))

    form.find('input[type="file"]').on('change', function(e) {
        var singleFileUploads = false

        $(this.files).each(function () {
            if (this.size > maxChunkSize) {
                singleFileUploads = true
            }
        })

        form.fileupload('option', 'singleFileUploads', singleFileUploads)
        .fileupload('add', {
            fileInput: $(this)
        })
    })

    return this
}

jQuery.fn.txpFileupload = function (options) {
    if (!jQuery.fn.fileupload) return this

    var form = this, fileInput = this.find('input[type="file"]'), maxChunkSize = options.maxChunkSize || 2000000

    form.fileupload($.extend({
        url: form.attr('action')+'?app_mode=async',
        dataType: 'html',
//        autoUpload: false,
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
    }, options)).off('submit').submit(function (e) {
        e.preventDefault();
        form.fileupload('add', {
            fileInput: $(fileInput)
        })
    })

    fileInput.on('change', function(e) {
        var singleFileUploads = false

        $(this.files).each(function () {
            if (this.size > maxChunkSize) {
                singleFileUploads = true
            }
        })

        form.fileupload('option', 'singleFileUploads', singleFileUploads)
    })

    return this
}

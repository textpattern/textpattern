jQuery.fn.txpFileupload = function (options) {
    if (!jQuery.fn.fileupload) return this

    var form = this, fileInput = this.find('input[type="file"]'), paramName = fileInput.attr('name'), maxChunkSize = options.maxChunkSize || 2000000

    form.fileupload($.extend({
        url: form.attr('action'),
        dataType: 'html',//script?
//        autoUpload: false,
        maxChunkSize: maxChunkSize,
        formData: null,
        fileInput: null,
        done: function (e, data) {
            textpattern.Relay.callback('txpAsyncForm.success', {data: data.result})
            textpattern.Relay.callback('uploadEnd', data)
        },
        progressall: function (e, data) {
            textpattern.Relay.callback('uploadProgress', data)
        },
        start: function (e, data) {
            textpattern.Relay.callback('uploadStart', data)
        }
    }, options)).off('submit').submit(function (e) {
        e.preventDefault()
        var formData = $(options.extraForm).serializeArray()
        $.merge(formData, form.serializeArray())
        
        form.fileupload('add', {
            formData: formData,
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

        textpattern.Relay.data.chunked = singleFileUploads
        form.fileupload('option', 'singleFileUploads', singleFileUploads)
    })

    return this
}

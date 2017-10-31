jQuery.fn.txpFileupload = function (options) {
    if (!jQuery.fn.fileupload) return this

    var form = this, fileInput = this.find('input[type="file"]'),
        maxChunkSize = options.maxChunkSize || 1000000

    form.fileupload($.extend({
        paramName: fileInput.attr('name'),
        dataType: 'script',
//        autoUpload: false,
        maxChunkSize: maxChunkSize,
        formData: null,
        fileInput: null,
        dropZone: null,
        replaceFileInput: false,
/*        add: function (e, data) {
            data.submit()
        },
        done: function (e, data) {
            console.log(data)
        },*/
        progressall: function (e, data) {
            textpattern.Relay.callback('uploadProgress', data)
        },
        start: function (e) {
            textpattern.Relay.callback('uploadStart', e)
        },
        stop: function (e) {
            textpattern.Relay.callback('uploadEnd', e)
        }
    }, options)).off('submit').submit(function (e) {
        e.preventDefault()
        
        form.fileupload('add', {
            files: fileInput.prop('files')
        })
    }).bind('fileuploadsubmit', function (e, data) {
        var formData = options.formData || []
        $.merge(formData, form.serializeArray())
        data.formData = formData;
    });

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

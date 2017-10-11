jQuery.fn.txpFileupload = function (options) {
    if (!jQuery.fn.fileupload) return this

    var form = this, fileInput = this.find('input[type="file"]'),
        paramName = fileInput.attr('name'),
        maxChunkSize = options.maxChunkSize || 2000000,
        result = null, messagepane = null, message = [], paneClass = ''

    form.fileupload($.extend({
        url: form.attr('action'),
        dataType: 'html',//script?
//        autoUpload: false,
        maxChunkSize: maxChunkSize,
        formData: null,
        fileInput: null,
        done: function (e, data) {
            result = $(data.result)
            messagepane = $(result.find('#messagepane noscript').html())
            message.push(messagepane.html())
            if (!paneClass) paneClass = messagepane.hasClass('error') ? 'error' : (messagepane.hasClass('warning') ? 'warning' : 'success')
            else if (paneClass == 'success' && messagepane.hasClass('error') ||
                paneClass == 'error' && messagepane.hasClass('success'))
                paneClass = 'warning'
//            $('#messagepane').html(messagepane.html(message.join('<br />')))
        },
        progressall: function (e, data) {
            textpattern.Relay.callback('uploadProgress', data)
        },
        start: function (e) {
            textpattern.Relay.callback('uploadStart', e)
            message = []
            paneClass = ''
        },
        stop: function (e) {
            result.find('#messagepane').html(messagepane.removeClass('success error warning').addClass(paneClass).html(message.join('<br />')))
            textpattern.Relay.callback('uploadEnd', e)
            textpattern.Relay.callback('txpAsyncForm.success', {data: result})
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

jQuery.fn.txpFileupload = function (options) {
    if (!jQuery.fn.fileupload) return this

    var form = this, fileInput = this.find('input[type="file"]'), paramName = fileInput.attr('name'), maxChunkSize = options.maxChunkSize || 2000000

    form.fileupload($.extend({
        url: form.attr('action')/*+'?app_mode=async'*/,
        dataType: 'html',
//        autoUpload: false,
        maxChunkSize: maxChunkSize,
        formData: null,
        fileInput: null,
        done: function (e, data) {
            textpattern.Relay.callback('uploadEnd', data)
            textpattern.Relay.callback('updateList', {html: data.result}, 100)
//            eval(data.result)
        },
        progressall: function (e, data) {
            textpattern.Relay.callback('uploadProgress', data)
        },
        start: function (e, data) {
            textpattern.Relay.callback('uploadStart', data)
        }
    }, options)).off('submit').submit(function (e) {
        e.preventDefault()
        var formData = new FormData($(options.extraForm).toArray()[0]),
            sendData = []

        for (var pair of form.serializeArray()) {
          formData.delete(pair['name'])
          formData.append(pair['name'],  pair['value'])
        }

        for (var pair of formData) {
          sendData.push({name: pair[0], value: pair[1]})
        }
        
        form.fileupload('add', {
            formData: sendData,
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

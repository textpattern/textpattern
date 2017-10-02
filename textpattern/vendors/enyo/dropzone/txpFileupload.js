jQuery.fn.txpFileupload = function (options) {
    if (!jQuery.fn.fileupload) return this

    this.fileupload($.extend({
        url: this.attr('action')+'?app_mode=async',
        dataType: 'html',
//            paramName: 'thefile',
//            singleFileUploads: false,
//            autoUpload: false,
        maxChunkSize: 2000000,
        done: function (e, data) {
            textpattern.Relay.callback('uploadEnd', data)
            eval(data.result);
        },
        progressall: function (e, data) {
            textpattern.Relay.callback('uploadProgress', data)
        },
        start: function (e, data) {
            textpattern.Relay.callback('uploadStart', data)
        }
    }, options))

    return this
}
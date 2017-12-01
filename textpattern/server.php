<?php
class File {
private static $available = array('textpattern.js');

static function output($path) {
    // Check if the file exists
    if(!in_array($path, File::$available) || !is_file($path)) {
        header('HTTP/1.0 404 Not Found');
        exit();
    }

    $fileModificationTime = gmdate('D, d M Y H:i:s', filemtime($path)).' GMT';

    // Set the content-type header
    header('Content-Type: '.self::mimeType($path).'; charset=utf-8');
    header('Access-Control-Allow-Origin: "*"');
    header('Last-Modified: '.$fileModificationTime);

    // Handle caching
    $headers = getallheaders();

    if(isset($headers['If-Modified-Since']) && $headers['If-Modified-Since'] == $fileModificationTime) {
        header('HTTP/1.1 304 Not Modified');
        exit();
    }

    // Read the file
    @readfile($path);

    exit();
}

static function mimeType($path) {
    switch($ext = strtolower(pathinfo($path, PATHINFO_EXTENSION))) {
        case 'js' :
            return 'application/x-javascript';
        case 'json' :
            return 'application/json';
        case 'jpg' :
        case 'jpeg' :
        case 'jpe' :
            return 'image/jpg';
        case 'png' :
        case 'gif' :
        case 'bmp' :
        case 'tiff' :
            return 'image/'.$ext;
        case 'css' :
        case 'txt' :
            return 'text/'.$ext;
        case 'xml' :
        case 'rtf' :
        case 'pdf' :
            return 'application/'.$ext;
        case 'html' :
        case 'htm' :
        case 'php' :
            return 'text/html';
        default :
            return 'text/' . mime_content_type($path);
    }
}
}

if (!empty($_GET['file'])) {
    File::output((string)$_GET['file']);
}
?>
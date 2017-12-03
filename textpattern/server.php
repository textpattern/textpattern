<?php
if (!class_exists('FileServer')) {
    class FileServer {
        private static $available = array('textpattern.js');

        static function output($path) {
            // Check if the file exists
            if(!in_array($path, self::$available) || !is_file($path)) {
                header('HTTP/1.0 404 Not Found');

                exit();
            }

            $mTime = filemtime($path);
            $fileModificationTime = gmdate('D, d M Y H:i:s', $mTime).' GMT';
            $etag = base_convert($mTime, 10, 32);

            // Set headers
            header('Access-Control-Allow-Origin: "*"');
            header('Content-Type: '.mime_content_type($path).'; charset=utf-8');
            header('Last-Modified: '.$fileModificationTime);
            header('ETag: "' . $etag . '"');

            // Handle caching
            if(!empty($_SERVER['HTTP_IF_MODIFIED_SINCE']) && strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) >= $mTime) {
                header('HTTP/1.1 304 Not Modified');

                exit();
            }

            // Read the file
            @readfile($path);

            exit();
        }
    }
}

if (!empty($_GET['file'])) {
    FileServer::output((string)$_GET['file']);
}
?>
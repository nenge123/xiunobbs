<?php
function http_root_path()
{
    $port = $_SERVER['SERVER_PORT'];
    $host = $_SERVER['HTTP_HOST'];
    $https = strtolower($_SERVER['HTTPS'] ?? 'off');
    $proto = strtolower($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '');
    $http = (($port == 443) || $proto == 'https' || ($https && $https != 'off')) ? 'https' : 'http';
    return $http.'://'.$host;
}

?>
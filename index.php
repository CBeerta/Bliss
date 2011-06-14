<?php
/**
* Bliss
*
* PHP Version 5.3
*
* Copyright (C) 2011 by Claus Beerta
*
* Permission is hereby granted, free of charge, to any person obtaining a copy
* of this software and associated documentation files (the "Software"), to deal
* in the Software without restriction, including without limitation the rights
* to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
* copies of the Software, and to permit persons to whom the Software is
* furnished to do so, subject to the following conditions:
*
* The above copyright notice and this permission notice shall be included in
* all copies or substantial portions of the Software.
*
* THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
* IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
* FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
* AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
* LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
* OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
* THE SOFTWARE.
*
* @category RSS_Reader
* @package  Bliss
* @author   Claus Beerta <claus@beerta.de>
* @license  http://www.opensource.org/licenses/mit-license.php MIT License
* @link     http://claus.beerta.de/
**/

define('BLISS_VERSION', '2.0.0');

require_once __DIR__ . '/vendor/Smarty/libs/Smarty.class.php';
require_once __DIR__ . '/vendor/flight/flight/Flight.php';

/**
* Autoloader for helpers and controllers
*
* @param string $class A Class file that is needed
*
* @return void
**/
function autoloader($class)
{
    $directories = array('/controllers/', '/lib/');
    
    foreach ($directories as $dir) {
        if (file_exists(__DIR__ . $dir . strtolower($class) . '.php')) {
            include_once __DIR__ . $dir . strtolower($class) . '.php';
        }
    }
}

spl_autoload_register("autoloader");

/**
* Options with defaults, overridable in config.ini
**/
$options = array (
    'opml' => null,
    'cache_dir' => '/var/tmp/',
    'simplepie_cache_duration' => 2*60*60,
    'data_dir' => __DIR__ . '/data/',
);

/**
* Load config file and override default options
**/    
$config = parse_ini_file(__DIR__."/config.ini", true);
foreach ( $options as $k => $v ) {
    $v = isset($config[$k]) ? $config[$k] : $options[$k];
    Flight::set($k, $v);
}
Flight::set('config', $config);

Flight::register(
    'view', 'Smarty', array(), function($smarty)
    {
        $smarty->compile_dir = Flight::get('cache_dir');
        $smarty->template_dir = __DIR__ . '/views/';
        $smarty->debugging = false;
    }
);

Flight::map(
    'render', function($template, $data)
    {
        Flight::view()->assign($data);
        Flight::view()->display($template);
    }
);

/**
* Debugging shortcut function
*
* @param string $message Message to log
* 
* @return void
**/
function d($message)
{
    if (!is_string($message)) {
        $message = print_r($message, true);
    }
    
    if ( class_exists("WebServer", false) ) {
        WebServer::log($message);
    } else {
        error_log($message);
    }
}



Flight::route('POST /load_next', array('Reader', 'next'));
Flight::route('POST /poll', array('Reader', 'poll'));
Flight::route('POST /add_feed', array('Config', 'add'));

Flight::route('/image', array('Reader', 'image'));

Flight::route('/', array('Reader', 'index'));


if (PHP_SAPI == 'cli') {
    Fetch::parseArgs();
} else {
    Flight::start();
}



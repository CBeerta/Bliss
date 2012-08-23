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
        if (file_exists(BLISS_BASE_DIR . $dir . strtolower($class) . '.php')) {
            include_once BLISS_BASE_DIR . $dir . strtolower($class) . '.php';
            return;
        }
    }

    if (strstr($class, "Plugin") !== false) {
        $name = str_replace('_', '.', strtolower($class));
        if (file_exists(BLISS_BASE_DIR . '/plugins/' . $name . '.php')) {
            include_once BLISS_BASE_DIR . '/plugins/' . $name . '.php';
            return;
        }
    }
}

spl_autoload_register("autoloader");

/**
* Load config file and override default options
**/    
$config = parse_ini_file(BLISS_BASE_DIR."/config.ini", true);
foreach ( $config as $k => $v ) {
    Feeds::option($k, $v);
}
Feeds::option('sources', $config['feeds']['sources']);
Feeds::option('filters', $config['filters']['filter']);

/**
* Register Smarty as View for Flight
**/
Flight::register(
    'view', 'Smarty', array(), function($smarty)
    {
        $smarty->compile_dir = Feeds::option('cache_dir');
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

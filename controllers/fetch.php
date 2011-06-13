<?php 
/**
* RssReader
*
* PHP Version 5.3
*
* Copyright (C) <year> by <copyright holders>
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
* @category RssReader
* @package  RssReader
* @author   Claus Beerta <claus@beerta.de>
* @license  http://www.opensource.org/licenses/mit-license.php MIT License
* @link     http://claus.beerta.de/
**/

if ( PHP_SAPI != 'cli' ) {
    // dont do anything if we're not a cli php
    return;
}

require_once __DIR__ . '/../vendor/simplepie/SimplePieAutoloader.php';

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

/**
* Fetch
*
* @category RssReader
* @package  RssReader
* @author   Claus Beerta <claus@beerta.de>
* @license  http://www.opensource.org/licenses/mit-license.php MIT License
* @link     http://claus.beerta.de/
**/
class Fetch
{
    /**
    * Commands that we understand
    **/
    private static $_commands = array(
            'update' => 'Load new Items from frrds',
            'help' => 'This Help',
            'dry-run' => 'Don\'t apply',
            'force' => 'Force Overwrites',
    );
        
    /**
    * Print help
    *
    * @return void
    **/
    private static function _help()
    {
        print "Usage: {$_SERVER['argv'][0]} [OPTIONS]\n";
        foreach (self::$_commands as $h => $t) {
            printf("\t--%-16s\t%s\n", $h, $t);
        }
    }

    /**
    * Parse CLI Args
    *
    * @return void
    **/
    public static function parseArgs()
    {
        $options = getopt('h', array_keys(self::$_commands));
        
        $dryrun = false;
        $force = false;
        $call = false;
        
        foreach ($options as $k => $v) {
            switch ($k) {
            case 'h':
            case 'help':
            default:
                self::_help();
                exit;
            case 'dry-run':
                $dryrun = true;
                break;
            case 'force':
                $force = true;
                break;
            }
        }
        
        if ($call !== false) {
            $ret = new $call();
            $ret->setup($value, $dryrun, $force)->run();
            exit;
        } else {
            self::_help();
            exit;
        }
    }

    
}


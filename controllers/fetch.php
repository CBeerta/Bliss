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

if ( PHP_SAPI != 'cli' ) {
    // dont do anything if we're not a cli php
    return;
}

require_once __DIR__ . '/../vendor/simplepie/SimplePieAutoloader.php';

// Simplepie throws notices on unreadalble feeds, dont want these
error_reporting(E_ALL ^ E_USER_NOTICE);

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
* @category RSS_Reader
* @package  Bliss
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
        
        $call = false;
        
        foreach ($options as $k => $v) {
            switch ($k) {
            case 'h':
            case 'help':
            default:
                self::_help();
                exit;
            case 'update':
                self::update();
                exit;
            }
        }
        
        self::_help();
    }
    
    /**
    * Update Feeds
    *
    * @return void
    **/
    public static function update()
    {
        $rss = new SimplePie();
        
        foreach (Reader::feeds() as $feed_uri) {
            d("Fetching: {$feed_uri}");
            
            $rss->set_feed_url($feed_uri);
            $rss->set_cache_location(Flight::get('cache_dir'));
            $rss->set_cache_duration(12 * 60 * 60);
            $rss->set_image_handler('image', 'i');

            $rss->init();
            $rss->handle_content_type();
            
             
            if ($rss->error()) {
                d($rss->error());
                continue;
            }
    
            $dir = Flight::get('data_dir') . '/' .
                Helpers::buildSlug(
                    $rss->get_author() . ' ' . 
                    $rss->get_title()
                );

            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            
            $feed_info = (object) array(
                'title' => $rss->get_title(),
                'feed_type' => $rss->get_type(),
                'feed_encoding' => $rss->get_encoding(),
                'description' => $rss->get_description(),
                'author_name' => $rss->get_author()->name,
                'author_email' => $rss->get_author()->email,
            );
            
            file_put_contents("{$dir}/feed.info", serialize($feed_info), LOCK_EX);
            
            foreach ($rss->get_items() as $item) {
                $outfile = "{$dir}/"
                    . $item->get_date('U')
                    . '-' 
                    . Helpers::buildSlug($item->get_id())
                    . '.item';
                
                $content = (object) array(
                    'title' => $item->get_title(),
                    'author' => $item->get_author(),
                    'authors' => $item->get_authors(),
                    'categories' => $item->get_categories(),
                    'content' => $item->get_content(),
                    'description' => $item->get_description(),
                    'date' => $item->get_date('r'),
                    'link' => $item->get_link(),
                    'enclosures' => $item->get_enclosures(),
                    'source' => $item->get_source(),
                    'id' => $item->get_id(),
                );
                
                file_put_contents($outfile, serialize($content));
            }
        }
        // Sanity Check, load all files, anc check them
        Reader::filelist(mktime(), $errors);
        d($errors);
    } // end update()

}


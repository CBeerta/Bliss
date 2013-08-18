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

if ( !defined('BLISS_VERSION') ) {
    die('No direct Script Access Allowed!');
}

/**
* Feeds
*
* @category RSS_Reader
* @package  Bliss
* @author   Claus Beerta <claus@beerta.de>
* @license  http://www.opensource.org/licenses/mit-license.php MIT License
* @link     http://claus.beerta.de/
**/
class Feeds
{
    // Class configuration
    protected static $config = array(
        'sources' => array(),
        'filters' => array(),
        'opml' => null,
        'data_dir' => 'data/',
        'cache_dir' => 'cache/',
        'simplepie_cache_duration' => 7200,
        'expire_before' => '6 week ago',
        'thumb_size' => 200,
        'gallery_minimum_image_size' => 800,
        'enable_gallery' => false,
    );
    
    // Cache filelist for multiple "next" calls
    protected static $glob = null;

    /**
    * Configure Feeds Class
    *
    * @param string $key   Key
    * @param mixed  $value Value
    *
    * @return void
    **/
    public static function option($key, $value = null)
    {
        if ($value === null) {
            return self::$config[$key];
        }
        
        self::$config[$key] = $value;
    }

    /**
    * The configured Feed Uris
    *
    * @return array
    **/
    public static function feedlist()
    {
        $feeds = array();
        $sources = array();

        // First: Feeds from config.ini
        foreach (self::$config['sources'] as $source) {
            $feeds[] = $source;
            $sources[] = 'config';
        }

        // Second: Feeds from opml source
        $opml = array();        
        if (self::$config['opml'] && is_file(self::$config['opml'])) {
            $fh = file_get_contents(self::$config['opml']);
            preg_match_all("=<outline (.+)/>=sU", $fh, $items);
            foreach ($items[1] as $item) {
                preg_match("#xmlUrl=\"(.+)\"#U", $item, $matches);
                $feeds[] = $matches[1];
                $sources[] = 'opml';
            }
        }
        
        //Third: Feeds subitted through the site
        $ret = Store::load('feeds.json');
        if ($ret !== false) {
            foreach ($ret as $source) {
                $feeds[] = $source;
                $sources[] = 'json';
            }
        }
        
        return (object) array('feeds' => $feeds, 'sources' => $sources);
    }
    
    /**
    * Load and return avaialable feed info files
    *
    * @param bool $only_subscribed Filter any non subscribed feeds
    *
    * @return array
    **/
    public static function feedinfo($only_subscribed = false)
    {
        $subscribed = self::feedlist();
        
        $data_dir = rtrim(self::$config['data_dir'], '/');
        $feedinfo = array();
         
        foreach (glob($data_dir . '/*/feed.info') as $file) {        
            $ret = Store::load($file);
            
            if (!is_object($ret)) {
                continue;
            }
            if ($only_subscribed && !in_array($ret->feed_uri, $subscribed->feeds)) {
                continue;
            }
            
            $key = md5($ret->feed_uri);
            $feedinfo[$key] = $ret;

            $nr = array_search($ret->feed_uri, $subscribed->feeds);
            if ($nr !== false) {
                $feedinfo[$key]->source = $subscribed->sources[$nr];
            } else {
                // This is possible if a feed was unsubscribed, but there are stil
                // archived articles
                $feedinfo[$key]->source = 'unknown';
            }
        }
        
        /**
        * Add All feed uris that are only configured but never actually had
        * a run, and dont have a 'feed.info' yet
        **/
        foreach ($subscribed->feeds as $feed) {
            $key = md5($feed);
            if (in_array($key, array_keys($feedinfo))) {
                continue;
            }

            $nr = array_search($feed, $subscribed->feeds);
            $uri = parse_url($feed);
            
            $feedinfo[$key] = (object) array(
                'title' => $uri['host'] . $uri['path'],
                'feed_uri' => $feed,
                'source' => $subscribed->sources[$nr],
            );
        }
        
        //ksort($feedinfo, SORT_STRING);
        return $feedinfo;        
    }

    /**
    * Gather a list of all available files
    *
    * @param int   $offset  Offset timestamp where to start pulling articles
    * @param array &$errors Errors will be put into that array
    *
    * @return array
    **/
    public static function filelist($offset = 0, &$errors = array())
    {
        $files = array();
        $data_dir = rtrim(self::$config['data_dir'], '/');
        $feed_infos = array();
        
        if (is_null(self::$glob)) {
            // Cache the glob for multiple iterations
            self::$glob = glob($data_dir . '/*/*.item');
            arsort(self::$glob);
        }  
        
        foreach (self::$glob as $file) {
            
            if (!preg_match(
                "#{$data_dir}/((.*?)/(([0-9]+)-(.*?\.item)))$#i",
                $file,
                $matches
            )) {
                $errors[] = "Invalid file: {$file}";
                continue;
            }
            
            $relative = $matches[1];
            $feed = $matches[2];
            $fname = $matches[3];
            $timestamp = $matches[4];
            $guid = $matches[5];
            $dir = $data_dir . '/' . $matches[2];
            
            if ($timestamp >= $offset) {
                continue;
            }

            if (!file_exists($dir . '/feed.info')) {
                $errors[] = "No {$dir}/feed.info File";
                continue;
            }
            
            if (!isset($feed_infos[$dir])) {
                $info = Store::load($dir . '/feed.info');
                if ($info === false) {
                    $errors[] = "{$dir}/feed.info Unreadable";
                    continue;
                }
                $feed_infos[$dir] = $info;
            }

            $files[$timestamp] = array_merge(
                array(
                    'timestamp' => $timestamp,
                    'dir' => $dir,
                    'feed' => $feed,
                    'file' => $file,
                    'fname' => $fname,
                    'guid' => $guid,
                    'relative' => $relative,
                ), 
                (array) $feed_infos[$dir]
            );
        }
        krsort($files);
        $errors = array_unique($errors);        
        
        return $files;
    }

    /**
    * Load all titles for all files
    *
    * FIXME: This might become to slow
    *
    * @return array
    **/
    public static function titles()
    {
        $filelist = Feeds::filelist(time());
        $titles = array();
        

        foreach ($filelist as $item) {  
            $article = Store::load($item['file']);
            $titles[$item['dir']][$item['fname']] = $article->title;
        }
        
        return $titles;    
    }
    
    /**
    * Load Next item after $offset
    *
    * @param int    $offset Article Offset
    * @param string $filter A Filter to apply. Currently only 'flagged'
    *
    * @return array
    **/
    public static function next($offset, $filter)
    {
        $filelist = self::filelist($offset);
        
        preg_match('#^select-(.*?)-(.*)$#i', $filter, $matches);
        
        foreach ($filelist as $item) {

            switch ($matches[1]) {
            
            case 'flagged':
                if (in_array($item['relative'], Store::toggle('flagged'))) {
                    break 2;
                }
                array_shift($filelist);
                break;

            case 'feed':
                if ($item['feed'] == $matches[2]) {
                    break 2;
                }
                array_shift($filelist);
                break;

            case 'unread':
                if (in_array($item['relative'], Store::toggle('unread'))) {
                    break 2;
                }
                array_shift($filelist);
                break;

            case 'article':
                if ($item['fname'] == $matches[2]) {
                    break 2;
                }
                array_shift($filelist);
                break;
                
            case 'day':
                try {
                    $today = new DateTime(urldecode($matches[2]));
                    $itemdate = new DateTime("@" . $item['timestamp']);
                } catch (Exception $e) {
                    break;
                }
        
                if ($today->format('Y-z') == $itemdate->format('Y-z')) {
                    break 2;
                }
                array_shift($filelist);
                break;

            default:
                break 2;
            
            }                                

        }

        $info = array_shift($filelist);
        
        if (empty($info)) {
            return false;
        }

        $item = Store::load($info['relative']);
        $item->info = (object) $info;
        
        return $item;
    }

}

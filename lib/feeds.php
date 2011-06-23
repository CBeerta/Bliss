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
        'opml' => null,
        'data_dir' => 'data/',
        'cache_dir' => 'data/cache/',
        'simplepie_cache_duration' => 7200,
        'expire_before' => '6 month ago',
        'thumb_size' => 200,
        'gallery_minimum_image_size' => 800,
        'enable_gallery' => false,
        'disable_plugins' => array(),
    );
    
    // Cache filelist for multiple "next" calls
    protected static $glob = null;

    // Cache flagged
    protected static $flagged = null;

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
        if ($value == null) {
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
        if (self::$config['opml']) {
            $fh = file_get_contents(self::$config['opml']);
            preg_match_all("=<outline (.+)/>=sU", $fh, $items);
            foreach ($items[1] as $item) {
                preg_match("#xmlUrl=\"(.+)\"#U", $item, $matches);
                $feeds[] = $matches[1];
                $sources[] = 'opml';
            }
        }
        
        //Third: Feeds subitted through the site
        $fe_feeds = array();
        $save_file = self::$config['data_dir'] . '/feeds.json';
        if (is_file($save_file) && is_readable($save_file)) {
            $ret = json_decode(file_get_contents($save_file));
            if (!is_array($ret)) {
                break;
            }
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
            $ret = json_decode(file_get_contents($file));
            
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
        Helpers::bench();
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
                $info = json_decode(file_get_contents($dir . '/feed.info'));
                if ($info === false) {
                    $errors[] = "{$dir}/feed.info Unreadable";
                    continue;
                }
                $feed_infos[$dir] = $info;
            }
            
            if (!is_null(self::$flagged) && in_array($relative, self::$flagged)) {
                $flagged = true;
            } else if (!is_null(self::$flagged)) {
                $flagged = false;
            } else {
                $flagged = null;
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
                    'flagged' => $flagged,
                ), 
                (array) $feed_infos[$dir]
            );
        }
        krsort($files);
        $errors = array_unique($errors);        
        
        error_log('Filelist took : ' . Helpers::bench());
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
        $filelist = Feeds::filelist(mktime());
        $titles = array();
        
        foreach ($filelist as $item) {
            $file = file_get_contents($item['file']);
            
            if (!$file) {
                continue;
            }
            
            $article = json_decode($file);
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
                if (in_array($item['relative'], self::flag())) {
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

            case 'article':
                if ($item['fname'] == $matches[2]) {
                    break 2;
                }
                array_shift($filelist);
                break;
                
            default:
                break;
            
            }                                

        }

        $info = array_shift($filelist);
        
        if (empty($info)) {
            return false;
        }

        $item = json_decode(file_get_contents($info['file']));
        $item->info = (object) $info;
        
        return $item;
    }

    /**
    * Find Available Plugins
    *
    * @return array
    **/
    public static function findPlugins()
    {
        $glob = glob(BLISS_BASE_DIR . '/plugins/*.plugin.php');
        
        $plugins = array();
        
        foreach ($glob as $file) {
            if (preg_match('#.*/((.*?).plugin).php$#i', $file, $matches)) {
                $class = ucwords(str_replace('.', ' ', $matches[1]));
                $class = str_replace(' ', '_', $class);
                
                if (class_exists($class, true)) {
                    $plugins[] = $class;
                }
            }
        }
        
        return $plugins;
    }
    
    /**
    * Flag a item
    *
    * @param string $file File to Flag
    *
    * @return array
    **/
    public static function flag($file = null)
    {
        $flag_file = self::$config['data_dir'] . '/flagged.json';
        
        if (self::$flagged === null
            && is_file($flag_file) 
            && ($ret = file_get_contents($flag_file)) !== false
        ) {
            self::$flagged = (array) json_decode($ret);
        } else if (self::$flagged === null) {
            self::$flagged = array();
        }
        
        if (is_null($file)) {
            return self::$flagged;
        }
        
        if (!in_array($file, self::$flagged)) {
            // It's not set yet, so set it
            self::$flagged[] = $file;
        } else {
            // it's set, so unset
            $nr = array_search($file, self::$flagged);
            unset(self::$flagged[$nr]);
            self::$flagged = array_merge(self::$flagged);
        }
        file_put_contents($flag_file, json_encode(self::$flagged), LOCK_EX);
 
        return self::$flagged;    
    }

}

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
    );

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
        
        // Second: Feeds from opml source
        $opml = array();        
        if (self::$config['opml']) {
            $fh = file_get_contents(self::$config['opml']);
            preg_match_all("=<outline (.+)/>=sU", $fh, $items);
            foreach ($items[1] as $item) {
                preg_match("#xmlUrl=\"(.+)\"#U", $item, $matches);
                $opml[] = $matches[1];
            }
        }
        
        //Third: Feeds subitted through the site
        $fe_feeds = array();
        $save_file = self::$config['data_dir'] . '/feeds.json';
        if (is_file($save_file) && is_readable($save_file)) {
            $ret = json_decode(file_get_contents($save_file));
            if (is_array($ret)) {
                $fe_feeds = $ret;
            }
        }

        // Finally: Merge all sources
        $feeds = array_merge($opml, self::$config['sources'], $fe_feeds);
        
        return $feeds;
    }
    
    /**
    * Load and return avaialable feed info files
    *
    * @return array
    **/
    public static function feedinfo()
    {
        $data_dir = rtrim(self::$config['data_dir'], '/');
        $feedinfo = array();
        foreach (glob($data_dir . '/*/feed.info') as $file) {        
            $ret = json_decode(file_get_contents($file));
            if (is_object($ret)) {
                $feedinfo[strtolower($ret->title)] = $ret;
            }
        }
        ksort($feedinfo, SORT_STRING);
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
        
        foreach (glob($data_dir . '/*/*.item') as $file) {
            $fname = trim(str_replace($data_dir, '', $file), '/');
            list($dir, $fname) = explode('/', $fname);
            $dir = $data_dir . '/' . $dir; 
            list($timestamp) = explode('-', $fname);
            
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
                
            $files[$timestamp] = array(
                'timestamp' => $timestamp,
                'dir' => $dir,
                'file' => $file,
                'feed_info' => $feed_infos[$dir],
            );
        }
        krsort($files);
        $errors = array_unique($errors);        
        
        Helpers::d("filelist took: " . Helpers::bench());
        return $files;
    }

    /**
    * Load Next item after $offset
    *
    * @param int $offset Article Offset
    *
    * @return array
    **/
    public static function next($offset)
    {
        $files = self::filelist($offset);

        $info = array_pop(array_slice($files, 0, 1));
        if (empty($info)) {
            return false;
        }

        $item = json_decode(file_get_contents($info['file']));
        $item->info = (object) $info;
        
        return $item;
    }


}

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

/**
* Reader
*
* @category RssReader
* @package  RssReader
* @author   Claus Beerta <claus@beerta.de>
* @license  http://www.opensource.org/licenses/mit-license.php MIT License
* @link     http://claus.beerta.de/
**/
class Reader
{
    /**
    * The configured Feed Uris
    *
    * @return array
    **/
    public static function feeds()
    {
        $feeds = array();
        $config = Flight::get('config');
        
        if (!is_array($config['feeds']['sources'])) {
            $config['feeds']['sources'] = array();
        }

        $opml = array();        
        if (Flight::get('opml')) {
            $fh = file_get_contents(Flight::get('opml'));
            preg_match_all("=<outline (.+)/>=sU", $fh, $items);
            foreach ($items[1] as $item) {
                preg_match("#xmlUrl=\"(.+)\"#U", $item, $matches);
                $opml[] = $matches[1];
            }
        }

        $feeds = array_merge($opml, $config['feeds']['sources']);
        
        return $feeds;
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
        $data_dir = rtrim(Flight::get('data_dir'), '/');
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
                $info = unserialize(file_get_contents($dir . '/feed.info'));
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
        
        return $files;
    }

    /**
    * Landing Page
    *
    * @return html
    **/
    public static function index()
    {
        $entries = array();
        $offset = mktime();
        for ($i=1;$i<=Flight::get('items_to_display');$i++) {
            $entry = self::LoadNext($offset);
            $entries[] = $entry;
            $offset = $entry->info->timestamp;
        }
       
        $data = array(
            'title' => 'RssReader',
            'entries' => $entries,
        );
        
        return Flight::render('index.tpl.html', $data);
    }

    /**
    * Ajax load_next to pull new item
    *
    * @param int $offset Article Offset
    *
    * @return html
    **/
    public static function next($offset)
    {
        $data = array('entry' => self::loadNext($offset));
        return Flight::render('article.snippet.tpl.html', $data);
    }

    /**
    * Load Next item
    *
    * @param int $offset Article Offset
    *
    * @return array
    **/
    public static function loadNext($offset)
    {
        $files = self::filelist($offset);

        $info = array_pop(array_slice($files, 0, 1));
        $item = unserialize(file_get_contents($info['file']));

        if ($item === false) {
            self::loadNext($info['timestamp']);
        }
        $item->info = (object) $info;
        
        if ($info['timestamp'] == $offset) {
            // We are at the last article
            return;
        }
        return $item;
    }
    
}


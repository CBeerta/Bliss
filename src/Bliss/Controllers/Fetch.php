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

namespace Bliss\Controllers;

use \SimplePie;
use \Bliss\Feeds;
use \Bliss\Store;
use \Bliss\Helpers;
use \DateTime;

if ( PHP_SAPI != 'cli' ) {
    // dont do anything if we're not a cli php
    return;
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
    * Cling
    **/
    private $_cling = null;

    /**
    * Constructor
    *
    * @param object $cling Cling app
    *
    * @return void
    **/
    public function __construct($cling)
    {
        $this->_cling = $cling;
    }

    /**
    * Check for a Article Title if it is filtered
    *
    * @param string $title Title to check
    *
    * @return book
    **/
    private function _isFiltered($title)
    {
        foreach (Feeds::option('filters') as $filter) {
        
            if (preg_match("#{$filter}#i", $title)) {
                return true;
            }
        
        }
        return false;
    }
    

    /**
    * Find Available Plugins
    *
    * @return array
    **/
    private function _findPlugins()
    {
        $glob = glob(BLISS_BASE_DIR . '/src/Bliss_Plugin/Content/*.php');
        
        $plugins = array();
        
        foreach ($glob as $file) {

            if (!preg_match('#.*Content/(.*?).php$#i', $file, $matches)) {
                continue;
            }
            
            $class = ucwords(str_replace('.', ' ', $matches[1]));
            $class = '\\Bliss_Plugin\\Content\\' . str_replace(' ', '_', $class);
            
            if (!class_exists($class, true)) {
                $this->_cling->log()->error("Tried loading {$class}, but failed.");
                continue;
            }

            $p = new $class();
            $prio = $p->priority();
            if (in_array($prio, array_keys($plugins))) {
                $this->_cling->log()->error(
                    "Skipping {$class} because of Duplicate Priority"
                );
                continue;
            }
            $plugins[$prio] = $class;
            unset($p);

        }
        ksort($plugins);
        return $plugins;
    }

    /**
    * Update Feeds
    *
    * @return void
    **/
    public function update()
    {
        /**
        * Setup SimplePie
        **/
        $rss = new \SimplePie();
        $rss->set_useragent(
            'Mozilla/4.0 (Bliss: ' 
            . BLISS_VERSION
            . '; https://github.com/CBeerta/Bliss'
            . '; Allow like Gecko)'
        );
        $rss->set_cache_location(Feeds::option('cache_dir'));
        $rss->set_cache_duration(Feeds::option('simplepie_cache_duration'));
        $rss->set_image_handler('image', 'i');
        $rss->set_timeout(30);
        $rss->set_autodiscovery_level(
            SIMPLEPIE_LOCATOR_AUTODISCOVERY 
            | SIMPLEPIE_LOCATOR_LOCAL_BODY
            | SIMPLEPIE_LOCATOR_LOCAL_EXTENSION
        );

        $strip_htmltags = $rss->strip_htmltags;
        array_splice($strip_htmltags, array_search('object', $strip_htmltags), 1);
        array_splice($strip_htmltags, array_search('param', $strip_htmltags), 1);
        array_splice($strip_htmltags, array_search('embed', $strip_htmltags), 1);
         
        $rss->strip_htmltags($strip_htmltags);

        try {
            $expire_before = new DateTime(Feeds::option('expire_before'));
        } catch (Exception $e) {
            die($e->getMessage()."\n");
        }
        
        $plugins = $this->_findPlugins();
        $unread = array();
        $errors = array();

        foreach (Feeds::feedlist()->feeds as $feed_uri) {
            $this->_cling->log()->info("Fetching: {$feed_uri}");

            $rss->set_feed_url($feed_uri);

            $rss->init();
            $rss->handle_content_type();

            $feed = Helpers::buildSlug(
                md5($feed_uri) . ' ' .
                $rss->get_title()
            );
             
            if ($rss->error()) {
                $this->_cling->log()->error($rss->error());
                continue;
            }
                
            $dir = rtrim(Feeds::option('data_dir'), '/') . '/' . $feed;

            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            
            $feed_info = (object) array(
                'title' => $rss->get_title(),
                'feed_uri' => $feed_uri,
                'simplepie_feed_url' => $rss->feed_url,
                'feed' => $feed,
                'last_update' => time(),
                'link' => $rss->get_link(),
                'feed_type' => $rss->get_type(),
                'feed_encoding' => $rss->get_encoding(),
                'description' => $rss->get_description(),
                'author_name' => 
                    isset($rss->get_author()->name) 
                    ? $rss->get_author()->name 
                    : 'na' ,
                'author_email' => 
                    isset($rss->get_author()->email) 
                    ? $rss->get_author()->email 
                    : 'na',
            );
            
            $title_list = array();

            foreach ($rss->get_items() as $item) {

                try {
                    $article_time = new DateTime($item->get_date());
                } catch (Exception $e) {
                    $this->_cling->log()->error(
                        "Can't parse timestamp for : " . $item['file']
                    );
                    $this->_cling->log()->error($e->getMessage());
                    continue;
                }
    
                if ($article_time <= $expire_before) {
                    // Skip items that are to old already
                    break;
                }
                
                if ($this->_isFiltered($item->get_title())) {
                    // Skip articles that are filtered
                    $this->_cling->log()->info(
                        "Filtered Item: " . $item->get_title()
                    );
                    continue;
                }

                $guid = Helpers::buildSlug($item->get_id());
                $outfile = "{$dir}/"
                    . $item->get_date('U')
                    . '-'
                    . $guid
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

                /**
                * Apply Content Plugins
                **/                
                foreach ($plugins as $plugin) {
                    $p = new $plugin();
                    if ($p->match($feed_uri)) {
                        $content = $p->apply($content);
                    }

                    if ($content === false) {
                        // Skip Item
                        continue 2;
                    }
                    unset($p);
                }

                if (!isset($newest)) {
                    $newest = $content;
                    $feed_info->newest_article = $content->date;
                }
                
                // Check if file exists, if not -> unread
                if (!file_exists($outfile)) {
                    Store::toggle('unread', $feed . '/' . basename($outfile));
                }

                Store::save($outfile, $content);
                
                /**
                * Check for Duplicates
                **/
                $glob = glob("{$dir}/*-{$guid}.item");
                if (!file_exists("{$dir}/feed.info")) {
                    /**
                    * This is a freshly added feed that we can skip for dupe
                    * check alltogether
                    **/
                } else if (is_array($glob) && count($glob) == 2) {
                    /**
                    * Remove The Older File.
                    **/
                    sort($glob);
                    unlink($glob[0]);
                    continue;
                }
                
            } // items foreach 

            // Save feed info
            Store::save("{$dir}/feed.info", $feed_info);
            unset($newest);
        }
        // Sanity Check, load all files, anc check them
        Feeds::filelist(time(), $errors);
        //error_log(print_r($errors, true));
    } // end update()


    /**
    * Expire Old Articles
    *
    * @return void
    **/
    public function expire()
    {
        $errors = array();

        try {
            $expire_before = new DateTime(Feeds::option('expire_before'));
        } catch (Exception $e) {
            die($e->getMessage()."\n");
        }
    
        $flagged = Store::toggle('flagged');
        $count = 0;
        $total = 0;
        
        foreach (Feeds::filelist(time(), $errors) as $item) {
            $total ++;

            try {
                $article_time = new DateTime("@" . $item['timestamp']);
            } catch (Exception $e) {
                $this->_cling->log()->error(
                    "Can't parse timestamp for : " . $item['file']
                );
                $this->_cling->log()->error($e->getMessage());
                continue;
            }
            
            if ($article_time <= $expire_before 
                && !in_array($item['relative'], $flagged)
            ) {
                $this->_cling->log()->info("Removing: " . $item['file']);
                unlink($item['file']);
                $count++;
            }
        }
        
        if ($total > 1000) {
            $this->_cling->log()->error(
                "You have {$total} Articles stored." . 
                "Consider Tuning the Expire of Articles."
            );
        }
        
        /**
        * Expire Nonexisting Unread Items from json file
        **/
        $flagged = Store::toggle('unread');
        foreach ($flagged as $name) {
            if (file_exists(Feeds::option('data_dir') . '/' . $name)) {
                continue;
            }
            
            Store::toggle('unread', $name);
        }
        
        /**
        * Expire Nonexisting Flagged items from json file.
        * This in theory should never happen unless the user deletes stuff
        **/
        $flagged = Store::toggle('flagged');
        foreach ($flagged as $name) {
            if (file_exists(Feeds::option('data_dir') . '/' . $name)) {
                continue;
            }
            
            Store::toggle('flagged', $name);
        }
        
        $this->_cling->log()->info("Expired {$count} Articles.");    
        //error_log(print_r($errors, true));
    }

    /**
    * Generate Thumbnails for all SPI files in cache
    *
    * @return void
    **/
    public function thumbs()
    {
        $cache_dir = rtrim(Feeds::option('cache_dir'), '/');
        $thumb_size = Feeds::option('thumb_size');
        $min_size = Feeds::option('gallery_minimum_image_size');
        
        $count = 0;
        
        foreach (glob($cache_dir . '/*.spi') as $img) {

            $dst_fname = $img . ".thumb.png";
            
            if (file_exists($dst_fname)) {
                continue;
            }

            $content = unserialize(file_get_contents($img));
            
            if (!$content || empty($content)) {
                continue;
            }

            list($type, $format) = explode('/', $content['headers']['content-type']);
            
            if ($type !== 'image') {
                continue;
            }
            
            $src = imagecreatefromstring($content['body']);
            if (!$src) {
                $this->_cling->log()->error(
                    "Can't read {$img}.\n"
                    . print_r($content['headers'], true)
                );
            }

            $w = imagesx($src);
            $h = imagesy($src);
            
            if ($w >= $h && $w <= $min_size) {
                continue;
            } else if ($h >= $w && $h <= $min_size) {
                continue;
            }

            $dst = Helpers::imgResize($src, $thumb_size, $thumb_size, false);
            if ($dst === false) {
                continue;
            }

            $w = imagesx($dst);
            $h = imagesy($dst);
            
            /**
            * Put a Thumb Size canvas around it so all thumbs are the same size
            **/
            $canvas = imagecreatetruecolor($thumb_size, $thumb_size);
            imagesavealpha($canvas, true);
            $trans_color = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
            imagefill($canvas, 0, 0, $trans_color);

            $canvas_center = array($thumb_size/2, $thumb_size/2);
            $dst_center = array($w/2, $h/2);
            
            imagecopyresampled(
                $canvas, 
                $dst, 
                $canvas_center[0] - $dst_center[0],
                $canvas_center[1] - $dst_center[1],
                0,
                0,
                $w,
                $h,
                $w,
                $h
            );
                        
            imagepng($canvas, $dst_fname, 6);
            $count++;
        }
        $this->_cling->log()->info("Created {$count} Thumbnails.");    
    }
    

}


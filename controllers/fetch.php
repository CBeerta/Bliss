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
            'update' => 'Load new Items from feeds',
            'expire' => 'Expire old Articles',
            'thumbs' => 'Build Thumbnails for all SPI files in the cache',
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
                self::cacheTriedToLoad();
                self::expire();
                if (Feeds::option('enable_gallery') != false) {
                    self::thumbs();
                }
                exit;
            case 'expire':
                self::expire();
                exit;
            case 'thumbs':
                self::thumbs();
                exit;
            }
        }
        
        self::_help();
    }

    /**
    * Encode a $url for simplepie to a cache file name
    *
    * @param string $url Url to encode to filename
    *
    * @return encoded
    **/
    public static function cacheName($url)
    {
        $current_feed = Feeds::option('_current_feed');

        // FIXME: Remove sometimes
        error_log("DEBUG: Cachefile: " . $url);
        
        $oldname = rtrim(Feeds::option('cache_dir'), '/')
            . '/'
            . strtr(base64_encode($url), '+/=', '-_,')
            . '.spi';
            
            
        $newname = rtrim(Feeds::option('cache_dir'), '/')
            . '/'
            . urlencode("{$current_feed}/" . md5($url))
            . '.spi';
            
        if (file_exists($oldname)) {
            error_log("Old Cache File Name Exists, renaming.");
            rename($oldname, $newname);
        }
        
        if (!empty($current_feed)) {
            return urlencode("{$current_feed}/" . md5($url));
        }

        return md5($url);
    }

    /**
    * Replace the '.tried-to-load' spi files with a 404 image
    *
    * FIXME Aslong as there is no good way in simplepie to deal with broken
    *       links for the image handler, we need to do it "the hard way"
    *
    * @return encoded
    **/
    public static function cacheTriedToLoad()
    {
        $cache_dir = rtrim(Feeds::option('cache_dir'), '/');
        
        $count = 0;
        
        foreach (glob($cache_dir . '/*.spi.tried-to-load') as $failed) {
            if (!preg_match('|^(.*?)\.spi.tried-to-load$|i', $failed, $matches)) {
                continue;
            }
            
            $dest_file = $matches[1] . '.spi';
            
            if (is_file($dest_file)) {
                // Eeh??? this must be garbage leftover
                unlink($failed);
                continue;
            }
            $image = array(
                'headers' => array('content-type' => 'image/png'),
                'body' => file_get_contents(dirname(__DIR__) . '/public/file.png')
            );
            
            if (file_put_contents(
                $dest_file, 
                serialize($image), 
                LOCK_EX
            ) !== false
            ) {
                $count++;
                unlink($failed);
            }            
        }
        error_log("Replaced {$count} tried-to-load files with a 404 image.");    
    }
    
    /**
    * Handle all enclosures from a Feed
    *
    * @param simplepie_class $input $item->get_enclosures() from simplepie
    *
    * @return array
    **/
    public static function handleEnclosures($input)
    {
        $enclosures = array();
        $thumbnails = array();
        
        foreach ($input as $enclosure) {

            if (!empty($enclosure->thumbnails)) {

                $thumbnails = $enclosure->thumbnails;

            } else if (!empty($enclosure->link)) {
                
                $title = !empty($enclosure->title)
                    ? $enclosure->title
                    : basename($enclosure->link);
                
                list($m) = explode('/', $enclosure->type);
                
                $medium = !empty($enclosure->medium)
                    ? $enclosure->medium
                    : $m;

                $enclosures[] = array(
                    'title' => $title,
                    'link' => $enclosure->link,
                    'content-type' => $enclosure->type,
                    'medium' => $medium,
                    'length' => $enclosure->length,
                );

            }

            $thumbnails = array_unique($thumbnails);
            
        }

        /**
        * Convert thumbs to enclosures
        **/
        foreach ($thumbnails as $thumb) {
            $enclosures[] = array(
                'title' => basename($thumb),
                'link' => $thumb,
                'medium' => 'image'
            );
        }
    
        return $enclosures;
    }
        
    /**
    * Cache Enclosures locally
    *
    * @param array $enclosures A list of enclosures
    *
    * @return void
    **/
    public static function cacheEnclosures($enclosures)
    {

        foreach ($enclosures as $k => $enc) {
        
            if ($enc['medium'] != 'image') {
                // currently only care about images
                continue;
            }

            // Generate Cache Class            
            $image_url = Fetch::cacheName($enc['link']);
            $cache = BlissPie_Cache::create(
                Feeds::option('cache_dir'), 
                $image_url, 
                'spi'
            );
            
            // add url to enclosure
            $enclosures[$k]['image_url'] = $image_url;
            
            // check if there's already soemthing in cache
            if ($cache->load() !== false) {
                return;
            }
            
            // Use SimplePie to load file
            $file = new SimplePie_File(
                $enc['link'], 
                $timeout = 10, 
                $redirects = 5, 
                $headers = null, 
                $useragent = $rss->useragent, 
                $force_fsockopen = false
            );

            // Although we know better, this is simply copy&pasted from simplepie
            // blisspie_cache will take care of the load failures
            $headers = $file->headers;
            if ($file->success 
                && ($file->method & SIMPLEPIE_FILE_SOURCE_REMOTE === 0 
                || ($file->status_code === 200 
                || $file->status_code > 206 
                && $file->status_code < 300))
            ) {
                // Store result in cache
                $cache->save(
                    array(
                        'headers' => $file->headers, 
                        'body' => $file->body,
                    )
                );
            }
            
        } // foreach
    }
        
    
    /**
    * Update Feeds
    *
    * @return void
    **/
    public static function update()
    {
        $rss = new SimplePie();
        $rss->set_useragent(
            'Mozilla/4.0 (Bliss: ' 
            . BLISS_VERSION
            . '; https://github.com/CBeerta/Bliss'
            . '; Allow like Gecko)'
        );
        $rss->set_cache_location(Feeds::option('cache_dir'));
        $rss->set_cache_duration(Feeds::option('simplepie_cache_duration'));
        $rss->set_image_handler('image', 'i');
        $rss->set_cache_name_function('Fetch::cacheName');
        $rss->set_cache_class('BlissPie_Cache');

        try {
            $expire_before = new DateTime(Feeds::option('expire_before'));
        } catch (Exception $e) {
            die($e->getMessage()."\n");
        }
        
        foreach (Feeds::feedlist()->feeds as $feed_uri) {
            error_log("Fetching: {$feed_uri}");
            
            $rss->set_feed_url($feed_uri);

            $rss->init();
            $rss->handle_content_type();
             
            if ($rss->error()) {
                error_log($rss->error());
                continue;
            }

            $feed = Helpers::buildSlug(
                md5($feed_uri) . ' ' .
                $rss->get_title()
            );
    
            $dir = rtrim(Feeds::option('data_dir'), '/') . '/' . $feed;

            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            
            $feed_info = (object) array(
                'title' => $rss->get_title(),
                'feed_uri' => $feed_uri,
                'feed' => $feed,
                'last_update' => mktime(),
                'link' => $rss->get_link(),
                'feed_type' => $rss->get_type(),
                'feed_encoding' => $rss->get_encoding(),
                'description' => $rss->get_description(),
                'author_name' => $rss->get_author()->name,
                'author_email' => $rss->get_author()->email,
            );
            
            $title_list = array();
            
            
            // Set _current_feed here, which is later used by cacheName
            // and the blisscache stuff
            // Very "through the eye"
            Feeds::option('_current_feed', $feed);

            foreach ($rss->get_items() as $item) {

                try {
                    $article_time = new DateTime($item->get_date());
                } catch (Exception $e) {
                    error_log("Can't parse timestamp for : " . $item['file']);
                    error_log($e->getMessage());
                    continue;
                }
    
                if ($article_time <= $expire_before) {
                    // Skip items that are to old already
                    // FIXME This is a bit late. At this point all images
                    // have been downloaded , even if theres no need for them
                    continue;
                }
            
                $outfile = "{$dir}/"
                    . $item->get_date('U')
                    . '-' 
                    . Helpers::buildSlug($item->get_id())
                    . '.item';
                    
                // Handle Enclosures
                $enclosures = self::handleEnclosures($item->get_enclosures());
                self::cacheEnclosures($enclosures);
                
                $content = (object) array(
                    'title' => $item->get_title(),
                    'author' => $item->get_author(),
                    'authors' => $item->get_authors(),
                    'categories' => $item->get_categories(),
                    'content' => $item->get_content(),
                    'description' => $item->get_description(),
                    'date' => $item->get_date('r'),
                    'link' => $item->get_link(),
                    'enclosures' => $enclosures,
                    'source' => $item->get_source(),
                    'id' => $item->get_id(),
                );
                
                if (!isset($newest)) {
                    $newest = $content;
                    $feed_info->newest_article = $content->date;
                }
                
                file_put_contents($outfile, json_encode($content));
            } // items foreach 

            // Save feed info
            file_put_contents("{$dir}/feed.info", json_encode($feed_info), LOCK_EX);
            unset($newest);
        }
        // Sanity Check, load all files, anc check them
        Feeds::filelist(mktime(), $errors);
        error_log(print_r($errors, true));
    } // end update()


    /**
    * Expire Old Articles
    *
    * @return void
    **/
    public static function expire()
    {
        try {
            $expire_before = new DateTime(Feeds::option('expire_before'));
        } catch (Exception $e) {
            die($e->getMessage()."\n");
        }
    
        $flagged = Feeds::flag();
        $count = 0;
        
        foreach (Feeds::filelist(mktime(), $errors) as $item) {

            try {
                $article_time = new DateTime("@" . $item['timestamp']);
            } catch (Exception $e) {
                error_log("Can't parse timestamp for : " . $item['file']);
                error_log($e->getMessage());
            }
            
            if ($article_time <= $expire_before 
                && !in_array($item['relative'], $flagged)
            ) {
                error_log("Removing: " . $item['file']);
                unlink($item['file']);
                $count++;
            }
        }
        
        error_log("Expired {$count} Articles.");    
        error_log(print_r($errors, true));
    }
    
    /**
    * Generate Thumbnails for all SPI files in cache
    *
    * @return void
    **/
    public static function thumbs()
    {
        $cache_dir = rtrim(Feeds::option('data_dir'), '/');
        $thumb_size = Feeds::option('thumb_size');
        $min_size = Feeds::option('gallery_minimum_image_size');
        
        $count = 0;
        
        foreach (glob($cache_dir . '/*/enclosures/*.spi') as $img) {
            
        
            $content = unserialize(file_get_contents($img));
            
            if (!$content || empty($content)) {
                continue;
            }

            list($type, $format) = split('/', $content['headers']['content-type']);
            
            if ($type !== 'image') {
                continue;
            }
            
            $src = imagecreatefromstring($content['body']);
            if (!$src) {
                error_log(
                    "Can't read {$img}. " 
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
            if ($dst !== false) {
                $w = imagesx($dst);
                $h = imagesy($dst);

                $dst_fname = $img . ".{$w}x{$h}.thumb.png";
                imagepng($dst, $dst_fname, 6);
                $count++;
            }
        }
        error_log("Created {$count} Thumbnails.");    
    }
    

}


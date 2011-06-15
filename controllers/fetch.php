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
        
        foreach (Feeds::feedlist() as $feed_uri) {
            error_log("Fetching: {$feed_uri}");
            
            $rss->set_feed_url($feed_uri);
            $rss->set_useragent(
                'Mozilla/4.0 (Bliss: ' 
                . BLISS_VERSION
                . '; https://github.com/CBeerta/Bliss'
                . '; Allow like Gecko)'
            );
            $rss->set_cache_location(Feeds::option('cache_dir'));
            $rss->set_cache_duration(Feeds::option('simplepie_cache_duration'));
            $rss->set_image_handler('image', 'i');

            $rss->init();
            $rss->handle_content_type();
             
            if ($rss->error()) {
                Helpers::d($rss->error());
                continue;
            }
    
            $dir = Feeds::option('data_dir') . '/' .
                Helpers::buildSlug(
                    $rss->get_author() . ' ' . 
                    $rss->get_title()
                );

            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            
            $feed_info = (object) array(
                'title' => $rss->get_title(),
                'feed_uri' => $feed_uri,
                'last_update' => mktime(),
                'link' => $rss->get_link(),
                'feed_type' => $rss->get_type(),
                'feed_encoding' => $rss->get_encoding(),
                'description' => $rss->get_description(),
                'author_name' => $rss->get_author()->name,
                'author_email' => $rss->get_author()->email,
            );
            
            $title_list = array();

            foreach ($rss->get_items() as $item) {
                $outfile = "{$dir}/"
                    . $item->get_date('U')
                    . '-' 
                    . Helpers::buildSlug($item->get_id())
                    . '.item';
                    
                $thumbnails = array();
                $enclosures = array();
                
                foreach ($item->get_enclosures() as $enclosure) {
                    if (!empty($enclosure->thumbnails)) {
                        $thumbnails = $enclosure->thumbnails;
                    } else if ($enclosure->medium == 'image') {
                        // Assume image mediums to be thumbs
                        $thumbnails = $enclosure->link;
                    } else if (!empty($enclosure->link)) {
                        $title = !empty($enclosure->title)
                            ? $enclosure->title
                            : basename($enclosure->link);
                        
                        $enclosures[$title] = array(
                            'link' => $enclosure->link,
                            'content-type' => $enclosure->type,
                            'length' => $enclosure->length,
                        );
                    }
                    $thumbnails = array_unique($thumbnails);
                }
                
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
                    'thumbnails' => $thumbnails,
                );
                
                if (!isset($newest)) {
                    $newest = $content;
                    $feed_info->newest_article = $content->date;
                }
                
                //$title_list[
                
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

}


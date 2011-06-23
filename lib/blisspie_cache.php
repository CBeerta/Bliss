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

/**
* SimplePie Cache Replacer
*
* @category RSS_Reader
* @package  Bliss
* @author   Claus Beerta <claus@beerta.de>
* @license  http://www.opensource.org/licenses/mit-license.php MIT License
* @link     http://claus.beerta.de/
**/
class BlissPie_Cache extends SimplePie_Cache
{
    /**
    * Configure Feeds Class
    *
    * @param string $location  Location
    * @param string $filename  Filename
    * @param string $extension Extension
    *
    * @return void
    **/
    public static function create($location, $filename, $extension)
    {
        return new Bliss_File_Cache($location, $filename, $extension);
    }


    /**
    * Cache Enclosures locally
    *
    * @param array $enclosures A list of enclosures
    *
    * @return array
    **/
    public static function cacheEnclosures($enclosures)
    {

        foreach ($enclosures as $k => $enc) {
        
            if ($enc['medium'] != 'image') {
                // currently only care about images
                continue;
            }

            // add url to enclosure
            $enclosures[$k]['image_url'] = self::cacheUri($enc['link']);

        } // foreach
        
        return $enclosures;
    }
    
    /**
    * Cache a URL Locally
    *
    * @param string $uri Url to Cache
    *
    * @return string $image_url Url to the cached image
    **/
    public static function cacheUri($uri)
    {
        // Generate Cache Class            
        $image_url = Fetch::cacheName($uri);
        $cache = BlissPie_Cache::create(
            Feeds::option('cache_dir'), 
            $image_url, 
            'spi'
        );
        
        // check if there's already soemthing in cache
        if ($cache->load() !== false) {
            return $image_url;
        }
        
        // Use SimplePie to load file
        $file = new SimplePie_File(
            $uri, 
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
        
        return $image_url;
    }
    
}

/**
* SimplePie Cache Replacer
*
* @category RSS_Reader
* @package  Bliss
* @author   Claus Beerta <claus@beerta.de>
* @license  http://www.opensource.org/licenses/mit-license.php MIT License
* @link     http://claus.beerta.de/
**/
class Bliss_File_Cache implements SimplePie_Cache_Base
{
    // Location
    var $location;

    // Filename
    var $filename;
    
    // Extension
    var $extension;
    
    // Name
    var $name;

    /**
    * Configure Feeds Class
    *
    * @param string $location  Location
    * @param string $filename  Filename
    * @param string $extension Extension
    *
    * @return void
    **/
    public function __construct($location, $filename, $extension)
    {
        $current_feed_dir = rtrim(Feeds::option('data_dir'), '/')
            . '/'
            . Feeds::option('_current_feed');

        $this->filename = $filename;
        $this->extension = $extension;
        $this->location = $location;

        $oldname = "$this->location/$this->filename.$this->extension";
        
        if ($extension == 'spi'&& is_dir($current_feed_dir)) {
            if (!is_dir($current_feed_dir . '/enclosures/')) {
                mkdir($current_feed_dir . '/enclosures/', 0755, true);
            }
            $this->location = $current_feed_dir . '/enclosures/';
            
            // Migrate Cache files
            // FIXME deprecate this sometime
            $this->name = "$this->location/$this->filename.$this->extension";
            if (is_file($oldname)) {    
                error_log("Old Cache Entry exists. Migrating.");
                rename($oldname, $this->name);
            }
        }

        $this->name = "$this->location/$this->filename.$this->extension";
    }

    /**
    * Save Cache
    *
    * @param mixed $data Data to store
    *
    * @return bool
    **/
    public function save($data)
    {
        if (file_exists($this->name)
            && is_writeable($this->name) 
            || file_exists($this->location) 
            && is_writeable($this->location)
        ) {
            if (is_a($data, 'SimplePie')) {
                $data = $data->data;
            }

            if ($this->extension == 'spi') {
                /**
                * We downloaded an image, so we can remove the
                * tried-to-load hint
                **/
                unlink($this->name . '.tried-to-load');
            }

            $data = serialize($data);
            return (bool) file_put_contents($this->name, $data);
        }
        return false;
    }

    /**
    * Load Cache
    *
    * @return bool
    **/
    public function load()
    {
        if (file_exists($this->name) && is_readable($this->name)) {
            return unserialize(file_get_contents($this->name));
        } else if ($this->extension == 'spi'
            && file_exists($this->name . '.tried-to-load') 
            && is_readable($this->name . '.tried-to-load')
        ) {
            /**
            * Tried loading a cached file which failed
            * And there is already a .tried-to-load
            * So just return something, to stop simplepie from reloading
            **/
            return true;
        }
        
        if ($this->extension == 'spi') {
            /**
            * SimplePie tried loading me, but it failed.
            * Either this is a file that has not been cached yet
            * Or, worse, its a file that cant be loaded from the remote server
            *
            * first case, we will simply delete the touched file on save
            * second case, the file will remain, and we will pretend it worked
            **/
            touch($this->name . '.tried-to-load');
        }
        return false;
    }

    /**
    * Get mtime
    *
    * @return bool
    **/
    public function mtime()
    {
        if (file_exists($this->name)) {
            return filemtime($this->name);
        }
        return false;
    }

    /**
    * Touch a cache file
    *
    * @return bool
    **/
    public function touch()
    {
        if (file_exists($this->name)) {
            return touch($this->name);
        }
        return false;
    }

    /**
    * Remove a cache file
    *
    * @return bool
    **/
    public function unlink()
    {
        if ($this->extension == 'spi') {
            // Don't ever expire images. EVER
            return false;
        }
        
        if (file_exists($this->name)) {
            return unlink($this->name);
        }
        return false;
    }
    
}

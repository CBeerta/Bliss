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
        $this->location = $location;
        $this->filename = $filename;
        $this->extension = $extension;
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
                @unlink($this->name . '.tried-to-load');
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
        }
        
        if ($this->extension == 'spi') {
            /**
            * SimplePie tried loading me, but it failed.
            * Either this is a file that has not been cached yet
            * Or, worse, its a file that cant be loaded from the remote server
            *
            * first case, we will simply delete the touched file on save
            * second case, the file will remain, and we 
            * can replace that file with a 404 image
            *
            * FIXME: There has to be an easier way! somehow
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

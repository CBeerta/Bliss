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
* Store
*
* Unify all reads and writes of what is in the Data Directory into this Class
*
* @category RSS_Reader
* @package  Bliss
* @author   Claus Beerta <claus@beerta.de>
* @license  http://www.opensource.org/licenses/mit-license.php MIT License
* @link     http://claus.beerta.de/
**/
class Store
{
    // Cache
    protected static $cache = array(
        'unread' => null,
        'flagged' => null,
    );

    /**
    * Store a variable to a json file
    *
    * @param string $filename Filename to put stuff into
    * @param mixed  $content  Content to store
    * @param bool   $merge    Wether to merge the old file or overwrite it
    *
    * @return array
    **/
    public static function save($filename, $content, $merge = false)
    {
        $data_dir = rtrim(Feeds::option('data_dir'), '/');
        
        if (strstr($filename, $data_dir . '/') === false) {
            $filename = $data_dir . '/' . $filename;
        }

        if ($merge !== false && is_array($content)) {
            if (($old = self::load($filename)) !== false) {
                $content = array_merge($content, $old);
                $content = array_unique($content);
                $content = array_merge($content);
            }
        }
        
        $json = json_encode($content);
        
        if ((file_put_contents($filename, $json, LOCK_EX)) === false) {
            return false;
        }
        
        return $content;
    }
    
    /**
    * Load a JSON file
    *
    * @param string $filename Filename to put stuff into
    *
    * @return mixed
    **/
    public static function load($filename)
    {
        $data_dir = rtrim(Feeds::option('data_dir'), '/');

        if (strstr($filename, $data_dir . '/') === false) {
            $filename = $data_dir . '/' . $filename;
        }
    
        if (($content = file_get_contents($filename)) === false) {
            return false;
        }
        
        if (($json = json_decode($content)) === null) {
            return false;
        }
        
        return $json;
    }

    /**
    * Toggle a Item in a File
    *
    * @param string $toggle File to use to store toggle
    * @param string $what   What to toggle. Probably a filename
    *
    * @return array
    **/
    public static function toggle($toggle, $what = null)
    {
        $fname = $toggle . '.toggle.json';
        if (self::$cache[$toggle] === null
            && ($ret = self::load($fname)) !== false
        ) {
            self::$cache[$toggle] = $ret;
        } else if (self::$cache[$toggle] === null) {
            self::$cache[$toggle] = array();
        }
        
        if (is_null($what)) {
            return self::$cache[$toggle];
        }
        
        if (!in_array($what, self::$cache[$toggle])) {
            // It's not set yet, so set it
            self::$cache[$toggle][] = $what;
        } else {
            // it's set, so unset
            $nr = array_search($what, self::$cache[$toggle]);
            unset(self::$cache[$toggle][$nr]);
            self::$cache[$toggle] = array_merge(self::$cache[$toggle]);
        }
        self::save($fname, self::$cache[$toggle]);
 
        return self::$cache[$toggle];
    }

}


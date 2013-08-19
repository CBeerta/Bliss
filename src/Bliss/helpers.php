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

namespace Bliss;

/**
* Helpers
*
* @category RSS_Reader
* @package  Bliss
* @author   Claus Beerta <claus@beerta.de>
* @license  http://www.opensource.org/licenses/mit-license.php MIT License
* @link     http://claus.beerta.de/
**/
class Helpers
{
    /**
    * Create a "Slug" from a title
    *
    * @param string $title   The title to create a slug from
    * @param string $sep     A Seperator
    * @param string $charset The Charset to use
    *
    * @return string a slug
    **/
    public static function buildSlug($title, $sep = "-", $charset = "UTF-8")
    {
        // Build Slug
        $slug = strtolower(htmlentities($title, ENT_COMPAT, $charset));
        $slug = preg_replace(
            '/&(.)(acute|cedil|circ|lig|grave|ring|tilde|uml);/', "$1", 
            $slug
        );
        $slug = preg_replace(
            '/([^a-z0-9]+)/', 
            $sep, 
            html_entity_decode($slug, ENT_COMPAT, $charset)
        );
        $slug = trim($slug, $sep);
        
        return $slug;
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

            if (!preg_match('#.*/((.*?).plugin).php$#i', $file, $matches)) {
                continue;
            }

            $class = ucwords(str_replace('.', ' ', $matches[1]));
            $class = str_replace(' ', '_', $class);
            
            if (!class_exists($class, true)) {
                continue;
            }

            $p = new $class();
            $prio = $p->priority();
            if (in_array($prio, array_keys($plugins))) {
                error_log("Skipping {$class} because of Duplicate Priority");
                continue;
            }
            $plugins[$prio] = $class;
            unset($p);

        }
        ksort($plugins);
        return $plugins;
    }

    /**
    * Resize an image, keep aspect ratio
    *
    * @param img  $src           Source Image (GD)
    * @param int  $target_width  How wide should the image be
    * @param int  $target_height And how Hight
    * @param bool $force_size    Force Target widh, or use calculated size
    *
    * @return img $dest New resized Image
    **/
    public static function imgResize(
        $src, 
        $target_width, 
        $target_height, 
        $force_size
    ) {
        $width = imagesx($src);
        $height = imagesy($src);
        $imgratio = ($width / $height);

        if ($width < $target_width || $height < $target_height) {   
            return false;
        }

        if ($imgratio>1) { 
            $new_width = $target_width; 
            $new_height = ($target_width / $imgratio); 
        } else { 
            $new_height = $target_height; 
            $new_width = ($target_height * $imgratio); 
        }
        
        if ($force_size) {
            // Force new image to be of target size
            $dest = imagecreatetruecolor($target_width, $target_height);
        } else {
            // will use aspect ratio
            $dest = imagecreatetruecolor($new_width, $new_height);
        }
        imagesavealpha($dest, true);
        $trans_color = imagecolorallocatealpha($dest, 0, 0, 0, 127);
        imagefill($dest, 0, 0, $trans_color);
        
        imagecopyresampled(
            $dest, 
            $src, 
            0, 
            0, 
            0,
            0,
            $new_width,
            $new_height,
            $width,
            $height
        );
        
        return $dest;
    }

    /**
    * Mini Benchmarking
    *
    * @return microtime
    **/
    function bench()
    {
        static $microtime_start = null;
        if ($microtime_start === null) {
            $microtime_start = microtime(true);
            return 0.0; 
        }    
        return microtime(true) - $microtime_start; 
    }

}


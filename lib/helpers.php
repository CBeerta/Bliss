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


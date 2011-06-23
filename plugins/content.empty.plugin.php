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
* Plugin that tries to generate content on empty articles
*
* @category RSS_Reader
* @package  Bliss
* @author   Claus Beerta <claus@beerta.de>
* @license  http://www.opensource.org/licenses/mit-license.php MIT License
* @link     http://claus.beerta.de/
**/
class Content_Empty_Plugin implements Bliss_Content_Plugin
{
    /**
    * The constructor
    *
    * @return void
    **/
    public function __construct()
    {
        // Nothing to init here
    }

    /**
    * Match uri, and check if we want to apply this filter here
    *
    * @param string $uri URI from feed to check
    *
    * @return bool Wether or not to apply this filter
    **/
    public function match($uri)
    {   
        // Everything could be empty, so yes.
        return true;
    }

    /**
    * Apply this filter to the $item
    *
    * @param array $item Bliss Item 
    *
    * @return $item Bliss
    **/
    public function apply($item)
    {
        if (!empty($item->content)) {
            // Already Has Content
            return $item;
        }

        $body = '<h2>Article has no Content!</h2>';
        
        foreach ($item->enclosures as $k => $v) {
            
            if ($v['medium'] = 'image') {
                $body .= '<a href="' . $v['link'] . '">';
                $body .= '<img src="' . $v['link'] . '">';
                $body .= '</a>';
            } else {
                $body .= '<a href="' . $v['link'] . '">';
                $body .= $k;
                $body .= '</a>';
            }
            unset($item->enclosures[$k]);
        }

        $item->content = $body;
                
        return $item;
    }
}


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
* Reader
*
* @category RSS_Reader
* @package  Bliss
* @author   Claus Beerta <claus@beerta.de>
* @license  http://www.opensource.org/licenses/mit-license.php MIT License
* @link     http://claus.beerta.de/
**/
class Reader
{
    /**
    * Landing Page
    *
    * @return html
    **/
    public static function index()
    {
        /**
        * We dont actually produce anything usefull on our initial load
        * The page is filled with content by jquery
        **/
        Flight::view()->assign('is_index', true);
        return Flight::render('index.tpl.html');
    }

    /**
    * Archive Page
    *
    * @return html
    **/
    public static function archive()
    {
        $data = array(
            'archives' => Feeds::filelist(mktime()),
            'titles' => Feeds::titles(),
        );
        
        return Flight::render('archive.tpl.html', $data);
    }

    /**
    * Image Gallery json loadnext
    *
    * @return html
    **/
    public static function gallery()
    {
        return Flight::render('gallery.tpl.html');
    }

    /**
    * Image Gallery json loadnext
    *
    * @param int $page Which page to load
    *
    * @return html
    **/
    public static function galleryPage($page)
    {
        $images = array();
        $cache_dir = rtrim(Feeds::option('cache_dir'), '/');
        $glob = $cache_dir . '/*.thumb.png';
    
        foreach (glob($glob) as $img) {
            if (!preg_match(
                "|({$cache_dir}/((.*?).spi))\.(\d+)x(\d+).thumb.png|i",
                $img,
                $matches
            )) {
                continue;
            }
            
            if (!is_file($matches[1])) {
                continue;
            }
            
            $images[] = array(
                'thumb' => basename($matches[0]),
                'id' => $matches[3],
                'width' => $matches[4],
                'height' => $matches[5],
            );
        }
        
        $images = array_slice($images, 50 * $page, 50);
        
        $data = array(
            'page' => $page,
            'images' => $images,
        );
        
        return Flight::render('gallery.snippet.tpl.html', $data);
    }


    /**
    * Load an Image from cache
    *
    * @return img
    **/
    public static function image()
    {
        $cache_dir = rtrim(Feeds::option('cache_dir'), '/');
        
        if (isset($_GET['thumb'])) {
            $file = $cache_dir . '/' . basename($_GET['thumb']);
            if (!file_exists($file) || !is_readable($file)) {
                return false;
            }

            header('content-type: image/png');
            echo file_get_contents($file);
            exit;
        } else if (!isset($_GET['i'])) {
            return false;
        }
        
        $file = $cache_dir . '/' . basename($_GET['i']) . '.spi';
        
        if (!file_exists($file) || !is_readable($file)) {
            return false;
        }

        $img = unserialize(file_get_contents($file));
        
        foreach (
            array(
                'content-type', 
                'expires', 
                'content-disposition',
                'cache-control',
            ) as $header ) {
            
            if (isset($img['headers'][$header])) {
                header($header . ':' . $img['headers'][$header]);
            }
        }
        
        echo $img['body'];
        exit;
    }

    /**
    * Ajax load_next to pull new item
    *
    * @param string $filter A filter to hand Feeds::next()
    *
    * @return html
    **/
    public static function next($filter)
    {
        $last_id = (isset($_POST['last_id']) && is_numeric($_POST['last_id']))
            ? $_POST['last_id']
            : null;

        $idlist = (isset($_POST['idlist']) && is_array($_POST['idlist']))
            ? $_POST['idlist']
            : array();
        
        if ($last_id == null) {
            return;
        }

        $next = Feeds::next($last_id, $filter);
        
        if (!$next || in_array($next->info->timestamp, $idlist)) {
            return;
        }
        
        $data = array(
            'entry' => $next,
            'flagged' => in_array($next->info->relative, Feeds::flag()),
        );

        return Flight::render('article.snippet.tpl.html', $data);
    }

    /**
    * Flag or unflag a item
    *
    * @return json
    **/
    public static function flag()
    {
        $name = (!empty($_POST['name']) && is_string($_POST['name']))
            ? $_POST['name']
            : null;
            
        $flagged = Feeds::flag($name);
        
        if (!in_array($name, $flagged)) {
            $ret = Flight::get('base_uri') . 'public/tag_stroke_24x24.png';
        } else {
            $ret = Flight::get('base_uri') . 'public/tag_fill_24x24.png';
        }

        echo json_encode($ret);
    }

    /**
    * Check if updates exist for the user
    *
    * @param string $filter A filter to hand Feeds::next()
    *
    * @return html
    **/
    public static function poll($filter)
    {
        $first_id = (isset($_POST['first_id']) && is_numeric($_POST['first_id']))
            ? $_POST['first_id']
            : null;
            
        if (is_null($first_id)) {
            exit ("Invalid POST");
        }

        $first = Feeds::next(mktime(), $filter);
        
        if ($first->info->timestamp > $first_id) {
            echo json_encode(array('updates_available' => true));
        } else {
            echo json_encode(array('updates_available' => false));
        }
        return;
    }
    
}


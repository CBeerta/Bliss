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
        * This index page doesnt produce any content on the initial load
        * The page is filled with content by jquery
        **/
        $data = array(
            'is_index' => true,
        );
        return Flight::render('index.tpl.html', $data);
    }

    /**
    * Archive Page
    *
    * @return html
    **/
    public static function archive()
    {
        $articles = array();
        
        foreach (Feeds::filelist(mktime()) as $item) {
            $day = new DateTime("@" . $item['timestamp']);
            $articles[$day->format('Y-m-d')][] = $item;
        }
        
        $data = array(
            'title' => 'Archive',
            'archives' => $articles,
            'flagged' => Store::toggle('flagged'),
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
        $data = array('title' => 'Image Gallery');
        return Flight::render('gallery.tpl.html', $data);
    }

    /**
    * Return something for empty pages
    *
    * @param string $page What page is loaded to display an empty message for
    *
    * @return html
    **/
    public static function nothing($page)
    {
        preg_match('#^select-(.*?)-(.*)$#i', $page, $matches);

        $text = null;
                
        switch ($matches[1]) {
        case 'flagged':
            $text = "You have no Flagged Articles";
            break;
        case 'feed':
            $text = "This Feed has no Articles";
            break;
        case 'unread':
            $text = "No Unread Articles";
            break;
        case 'article':
            $text = "The Selected Article can't be found";
            break;
        case 'day':
            $text = "This Day has no Articles";
            break;
        default:
            break;
        }        

        $data = array(
            'text' => $text,
            'page' => $page,
        );

        return Flight::render('nothing.snippet.tpl.html', $data);
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
        $gallery = array();
        $data_dir = rtrim(Feeds::option('data_dir'), '/');
        $glob = glob($data_dir . '/*/enclosures/*.thumb.png');

        sort($glob);

        foreach ($glob as $img) {
            if (!preg_match(
                "|{$data_dir}/(.*?)/enclosures/((.*?).spi).thumb.png|i",
                $img,
                $matches
            )) {
                continue;
            }
            
            $images[] = array(
                'thumb' => basename($matches[0]),
                'feed' => $matches[1],
                'id' => $matches[3],
            );
        }

        $images = array_slice($images, 30 * $page, 30);
        
        if (empty($images)) {
            return false;
        }
        
        /*
        $feeds = Feeds::feedinfo();
        $feedinfo = array();
        
        foreach ($feeds as $k => $v) {
            $feedinfo[$v->feed] = $v;
        }
        
        foreach ($images as $image) {
            $gallery[$image['feed']][] = $image;
        }
        */
        
        $data = array(
            'page' => $page,
            'images' => $images,
            'gallery' => $gallery,
            'feedinfo' => $feedinfo,
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
        $cache_dir = rtrim(Feeds::option('data_dir'), '/');
        
        $i = !empty($_GET['thumb']) ? $_GET['thumb'] : $_GET['i'];
        
        list($feed, $file) = explode('/', $i);
        
        $feed = basename($feed);
        $file = urlencode($i);
        
        $file = "{$cache_dir}/{$feed}/enclosures/{$file}";

        /**
        * Set expire headers to enable caching
        **/
        $expires = 60 * 60 * 24 * 14;
        header("Pragma: public");
        header("Cache-Control: maxage=" . $expires);
        header('Expires: ' . gmdate('D, d M Y H:i:s', time() + $expires) . ' GMT');
        
        if (isset($_GET['thumb'])) {
            if (!file_exists($file) || !is_readable($file)) {
                return false;
            }

            header('content-type: image/png');
            echo file_get_contents($file);
            exit;
        } else if (!isset($_GET['i'])) {
            return false;
        }
        
        $file = $file . '.spi';
        if (!file_exists($file) || !is_readable($file)) {
            return false;
        }
        
        if (preg_match(
            '#enclosures/(.*?)%2F(\w{32,32})-(.*?).spi#i', 
            $file,
            $matches
        )) {
            header("Content-Disposition: inline; filename=\"{$matches[3]}\"");
        }
        
        $img = unserialize(file_get_contents($file));

        /** 
        * Load headers from file
         * FIXME: Keep this? or force our own caching headers?
        **/
        foreach (
            array(
                'content-type', 
                'expires', 
                'pragma',
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
            'flagged' => in_array($next->info->relative, Store::toggle('flagged')),
            'unread' => in_array($next->info->relative, Store::toggle('unread')),
        );

        return Flight::render('article.snippet.tpl.html', $data);
    }

    /**
    * Mark an article as read
    *
    * @return json
    **/
    public static function read()
    {
        $name = (!empty($_POST['name']) && is_string($_POST['name']))
            ? $_POST['name']
            : null;
            
        if (is_null($name)) {
            exit ("Invalid POST");
        }
        
        $unread = Store::toggle('unread');
        $found = array_search($name, $unread);
        
        if ($found === false) {
            exit ("Can't Find Your Article!");
        }
        Store::toggle('unread', $name);
        
        echo json_encode("OK");
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
            
        $flagged = Store::toggle('flagged', $name);
        
        if (!in_array($name, $flagged)) {
            $ret = Flight::get('base_uri') . 'public/tag_blue_add.png';
        } else {
            $ret = Flight::get('base_uri') . 'public/tag_blue_delete.png';
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


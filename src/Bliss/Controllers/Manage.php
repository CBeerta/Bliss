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

namespace Bliss\Controllers;

use \Bliss\Feeds;
use \Bliss\Store;

if ( !defined('BLISS_VERSION') ) {
    die('No direct Script Access Allowed!');
}

/**
* Config
*
* @category RSS_Reader
* @package  Bliss
* @author   Claus Beerta <claus@beerta.de>
* @license  http://www.opensource.org/licenses/mit-license.php MIT License
* @link     http://claus.beerta.de/
**/
class Manage
{
    /**
    * Manage current subscriptions
    *
    * @return html
    **/
    public static function feedlist()
    {
        $data = array(
            'title' => 'Manage Subscriptions',
            'feedlist' => Feeds::feedlist(),
            'feedinfo' => Feeds::feedinfo(true),
        );
        
        return $data;
    }

    /**
    * Add a new Feed
    *
    * @return json
    **/
    public static function add()
    {
        $uri = (isset($_POST['uri']) && is_string($_POST['uri']))
            ? $_POST['uri']
            : null;
            
        $reply = array('status' => 'FAIL');
            
        if (is_null($uri)) {        
            $reply['message'] = 'Feed URI not Parseable!';
            return json_encode($reply);
        }
        
        // Do a quick test request, to check if the url is valid.
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $uri); 
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_NOBODY, true); // do HEAD request only
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, false);
        $head = curl_exec($ch);
        $info = curl_getinfo($ch);
        curl_close($ch);
        
        if ($info['http_code'] != 200) {
            $reply['message'] = 'There is nothing at that URL';
            return json_encode($reply);
        }

        // Check if the feed is already beeing pulled
        $feedlist = Feeds::feedlist();
        if (in_array($uri, $feedlist->feeds)) {
            $reply['message'] = 'Already pulling that feed.';
            return json_encode($reply);
        }

        $feeds = Store::load('feeds.json');
        if ($feeds === false) {
            $feeds = array();
        }
        
        $feeds[] = $uri;
        
        if (Store::save('feeds.json', $feeds) === false) {
            $reply['message'] = 'Unable to save feed info.';
            return json_encode($reply);
        }
        
        $reply['status'] = 'OK';
        $reply['message']
            = 'Feed Added Successfully!<br>
            New items will be pulled on the next regular run.';
            
        return json_encode($reply);
    }

    /**
    * Remove a Feed
    *
    * FIXME: This will leave the archive intact, and thus
    * The Feed will still happily show up on the archives page
    *
    * @return json
    **/
    public static function remove()
    {
        $uri = (isset($_POST['uri']) && is_string($_POST['uri']))
            ? $_POST['uri']
            : null;

        $reply = array('status' => 'FAIL');
            
        if (is_null($uri)) {        
            $reply['message'] = 'Not a Valid Feed to Remove!';
            return json_encode($reply);
        }
        
        $feeds = Store::load('feeds.json');
        
        if (!$feeds) {
            $reply['message'] = 'Can not load feeds.json file!';
            return json_encode($reply);
        }
        
        if (($found = array_search($uri, $feeds)) === false) {
            $reply['message'] 
                = 'Can not Delete that Feed!<br/>
                This can happen if the feed has been added to the `config.ini`
                or part of an OPML file.';
            return json_encode($reply);
        }
        
        unset($feeds[$found]);
        $feeds = array_merge($feeds);

        if (Store::save('feeds.json', $feeds) === false) { 
            $reply['message'] = 'Unable to save feed info.';
            return json_encode($reply);
        }

        $reply['status'] = 'OK';
        $reply['message'] = 'Feed removed!';
            
        return json_encode($reply);
    }

    /**
    * Export all Feeds to an OPML File
    *
    * @return void
    **/
    public static function opml()
    {
        $feedinfo = Feeds::feedinfo();
        header('Content-type: text/xml');
        header('Content-Disposition: attachment; filename="bliss-export.opml"');

        return array('feedinfo' => $feedinfo);
    }

}

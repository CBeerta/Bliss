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
    * @return htmls
    **/
    public static function edit()
    {
        $data = array(
            'feedlist' => Feeds::feedlist(),
            'feedinfo' => Feeds::feedinfo(true),
        );
        
        return Flight::render('manage.tpl.html', $data);
    }

    /**
    * Add a new Feed
    *
    * @return json
    **/
    public static function add()
    {
        $save_file = Feeds::option('data_dir') . '/feeds.json';
        
        $uri = (isset($_POST['uri']) && is_string($_POST['uri']))
            ? $_POST['uri']
            : null;
            
        $reply = array('status' => 'FAIL');
            
        if (is_null($uri)) {        
            $reply['message'] = 'Feed URI not Parseable!';
            exit(json_encode($reply));
        }
        
        // Do a quick test request, to check if the url is valid.
        // FIXME: Should we? Maybe the Frontend will run on a disconnected host?
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
            exit(json_encode($reply));
        }
        
        $feeds = array();

        if (is_file($save_file) 
            && is_readable($save_file)
        ) {
            $feeds = json_decode(file_get_contents($save_file));
            if ($feeds === false) {
                $feeds = array();
            } else if (in_array($uri, $feeds)) {
                $reply['message'] = 'Already pulling that feed.';
                exit(json_encode($reply));
            } else {
                copy($save_file, $save_file . '.bak');
            }
        }
        
        $feeds[] = $uri;
        
        if (!file_put_contents($save_file, json_encode($feeds), LOCK_EX)) {
            $reply['message'] = 'Unable to save feed info.';
            exit(json_encode($reply));
        }
        
        $reply['status'] = 'OK';
        $reply['message']
            = 'Feed Added Successfully!<br>
            New items will be pulled on the next regular run.';
            
        exit(json_encode($reply));
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
        $save_file = Feeds::option('data_dir') . '/feeds.json';
        
        $uri = (isset($_POST['uri']) && is_string($_POST['uri']))
            ? $_POST['uri']
            : null;

        $reply = array('status' => 'FAIL');
            
        if (is_null($uri)) {        
            $reply['message'] = 'Not a Valid Feed to Remove!';
            exit(json_encode($reply));
        }
        
        $feeds = json_decode(file_get_contents($save_file));
        
        if (!$feeds) {
            $reply['message'] = 'Can not load feeds.json file!';
            exit(json_encode($reply));
        }
        
        if (($found = array_search($uri, $feeds)) === false) {
            $reply['message'] 
                = 'Can not Delete that Feed!<br/>
                This can happen if the feed has been added to the `config.ini`
                or part of an OPML file.';
            exit(json_encode($reply));
        }
        
        unset($feeds[$found]);

        copy($save_file, $save_file . '.bak');
        if (!file_put_contents($save_file, json_encode($feeds), LOCK_EX)) {
            $reply['message'] = 'Unable to save feed info.';
            exit(json_encode($reply));
        }

        $reply['status'] = 'OK';
        $reply['message'] = 'Feed removed!';
            
        exit(json_encode($reply));
    }


}
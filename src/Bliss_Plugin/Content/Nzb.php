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

namespace Bliss_Plugin\Content;

use \Bliss\Plugin;

/**
* Plugin that tries to generate content on empty articles
*
* @category RSS_Reader
* @package  Bliss
* @author   Claus Beerta <claus@beerta.de>
* @license  http://www.opensource.org/licenses/mit-license.php MIT License
* @link     http://claus.beerta.de/
**/
class Nzb implements Plugin
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
    * Priority of when in the stack to execute.
    *
    * @return int Priority. Lower numbers go first
    **/
    public function priority()
    {
        return 999;
    }

    /**
    * The Template this Plugin uses to display Content
    *
    * @return string Template Name
    **/
    public function template()
    {
        return "plugins/tmdb.tpl.html";
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
        if (!preg_match("|.*nzbindex.*|i", $uri)) {
            return false;
        }

        $api = Feeds::option('tmdb_plugin_api');
        if (strlen($api) != 32) {
            error_log("Not a Valid TMDb API Key");
            return false;
        }

        return true;
    }

    /**
    * Look at tmdb api an pull movie info
    *
    * @param string $title Title of the Bliss Item
    * @param string $imdb  IMDB Number of item
    *
    * @return $item Bliss
    **/
    public function loadTmdb($title, $imdb = null)
    {
        $cache_file = md5($title) . '.tmdb.cache';

        if (is_null($imdb)) {
            // Load from Cache
            return Store::load($cache_file, 'cache_dir');
        }

        $api = Feeds::option('tmdb_plugin_api');
        $req = "http://api.themoviedb.org/2.1/Movie.imdbLookup/en/json/".$api."/";

        error_log("Loading: {$req}{$imdb}");

        if (($ret = file_get_contents("{$req}{$imdb}")) === false) {
            // Api call Failed
            return false;
        }

        $json = json_decode($ret);
        if (is_null($json) || !is_array($json) || !is_object($json[0])) {
            // Nothing Found, Store title for future checks
            Store::save('tmdb_not_found.json', array(md5($title)), true);
            return false;
        }
        $json = array_pop($json);

        // Cache the tmdb output
        Store::save($cache_file, $json, false, 'cache_dir');

        return $json;
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
        if (in_array(md5($item->title), Store::load('tmdb_not_found.json'))) {
            // Item wasnt found on tmdb previously, no sense looking again
            return false;
        }

        $tmdb = $this->loadTmdb($item->title);

        if ($tmdb === false) {
            preg_match("|.*\"(http://.*/nfo/.*?)\".*|mi", $item->content, $matches);
            if (!isset($matches[1])) {
                // No NFO Link
                return false;
            }
            error_log("Loading: {$matches[1]}");
            $nfo = file_get_contents($matches[1]);

            if (!$nfo) {
                // nfo could not be loaded
                return false;
            }

            preg_match(
                "|.*\"(http://www.imdb.com/title/(.*?)/?)\".*|mi",
                $nfo,
                $matches
            );

            if (!isset($matches[2])) {
                // no imdb link in the nfo, store for later checks
                Store::save('tmdb_not_found.json', array(md5($item->title)), true);
                return false;
            }

            $tmdb = $this->loadTmdb($item->title, $matches[2]);
        }

        if (empty($tmdb->name)) {
            return false;
        }

        $images = array();

        foreach ($tmdb->backdrops as $img) {
            $images[$img->image->id][$img->image->size] = (array) $img->image;
        }

        Flight::view()->assign(
            array(
                'json' => $tmdb,
                'item' => $item,
                'images' => $images
            )
        );
        
        $item->content = Flight::view()->fetch('plugins/tmdb.tpl.html');
        $item->title = $tmdb->name;

        return $item;
    }
}

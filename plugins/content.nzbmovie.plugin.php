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
* Try to find a Movie for a NZB title
*
* @category RSS_Reader
* @package  Bliss
* @author   Claus Beerta <claus@beerta.de>
* @license  http://www.opensource.org/licenses/mit-license.php MIT License
* @link     http://claus.beerta.de/
**/
class Content_Nzbmovie_Plugin implements Bliss_Content_Plugin
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
        return 10;
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
        if (!preg_match("#nzbindex#i", $uri)) {
            return false;
        }
        
        $api = Feeds::option('nzbmovie_plugin_api');
        if (strlen($api) != 32) {
            error_log("Not a Valid TMDb API Key");
            return false;
        }

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
        $cache_dir = rtrim(Feeds::option('cache_dir'), '/');
        $cache_file = $cache_dir . '/' . md5($item->link) . '.tmdb.cache';

        if (file_exists($cache_file . '.not-found')) {
            return $item;
        } else if (($ret = file_get_contents($cache_file)) !== false) {
            // Successfully loaded. Do nothing
        } else {
            $ret = $this->scrap($item->title);
            if (!$ret) {
                // Scrap didnt fid a thing, so remember that
                touch($cache_file . '.not-found');
                return $item;
            } 
            //Store TMDb result
            file_put_contents($cache_file, $ret);
        }

        // We expect our search to be awesome, so just pick the first match.
        $json = array_pop(json_decode($ret));
        
        Flight::view()->assign(array('json' => $json, 'item' => $item));
        $item->content = Flight::view()->fetch('plugins/nzbmovie.tpl.html');
        $item->attachements = null;

        $year = date('Y', strtotime($json->released));
        $item->title = "{$json->name} - {$year}";
        
        return $item;
    }

    /**
    * Strip down the title to bare minimum, then look on TMDb for match
    * Return False if nothing found, or a string with the json
    *
    * @param array $title Title to look for
    *
    * @return mixed
    **/
    public function scrap($title)
    {
        // Trim and remove html and entities
        $title = trim($title);
        $title = strip_tags($title);
        $title = preg_replace('#(&(.*?);)#', '', $title);

        if (empty($title)) {
            return false;
        }
        
        // Lowercase
        $title = strtolower($title);
        
        // Strip newsgroups names
        $title = preg_replace('|#(\w+)\.(\w+)(\.\w+)?|', ' ', $title);

        // Strip usual delimiter characters
        $title = preg_replace('#(_|-|,|\.|:|\)|\()#', ' ', $title);
        
        $wordlist = array(
            '(\[[\w\d]{2,6}\])',
            '([0-9]{3,4}p)',
            '(x|h)\s?264',
            '(yenc)',
            '\.(\w{3,3})\s+',
            'blu(e)?ray',
            '(\[[0-9]+/[0-9]+\])',
            '(extended|sample|efnet|\w+hd|hd[\d\w]+|nzbsrus)',
            '\s+(nfo|mkv|avchd|jpg|com|nzb)',
            '(\[[0-9]{5,}\])',
            '(unrated|repack|remastered|foreign)',
            '(dts|ac3|hdtv|hdrip|\w+sub(s)?|web\sdl)',
            '([\d(\.\d?)?])\s?g(b)?',
        );

        // remove some common words
        foreach ($wordlist as $pattern) {
            $title = preg_replace("#{$pattern}#", '', $title);
        }
        
        // Try and grab the year
        $year = null;
        if (preg_match('#([12][0-9]{3,3})#', $title, $matches)) {
            // remove the year
            $title = preg_replace('#([12][0-9]{3,3})#', '', $title);
            $matches = array_unique($matches);

            if (!isset($matches[0]) || !is_numeric($matches[0])) {
            } else if ($matches[0] > 1900 && $matches[0] <= date('Y')) {
                $year = $matches[0];
            }
        }
        
        // now drop the remaining non word chars
        $title = preg_replace('#\W#', ' ', $title);
        
        // replace extranous whitespace
        $title = preg_replace('#\s+#', ' ', $title);
        $title = trim($title);
        
        $words = explode(' ', $title);
        $words = array_unique($words);
        
        if (count($words) > 6) {
            // after all this filtering, we still got this much crap.
            // no movie has a title this long, so give up
            return false;
        }
        
        foreach ($words as $k=>$v) {
        
            // dont care about numbers much anymore
            if (is_numeric($v) && !is_null($year)) {
                unset($words[$k]);
            }
            
            // one character words, dont need these either
            if (strlen($v) == 1) {
                unset($words[$k]);
            }

        }    
        
        // So, ended up with nothing. Not good.
        if (count($words) === 0) {
            return false;
        }

        $api = Feeds::option('nzbmovie_plugin_api');
        $req = "http://api.themoviedb.org/2.1/Movie.search/en/json/" . $api . "/";

        while (count($words) > 0) {

            // Compose search string            
            $search = implode($words, '+') . "+{$year}";
            
            // Shorten the search string for the next run
            array_pop($words);
            
            
            // FIXME Debugging code
            error_log("{$req}{$search}");
            
            // And use TMDb
            if (($ret = file_get_contents("{$req}{$search}")) === false) {
                return false;
            }

            // Slow Down
            sleep(5);

            $json = json_decode($ret);
            if (is_null($json) || !is_array($json) || !is_object($json[0])) {
                // Nothing Found
                continue;
            }
            
            // If we come this far: found something!
            return $ret;
        }
        
        // *sadface*
        return false;
    }

}


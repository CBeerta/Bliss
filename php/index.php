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

require_once __DIR__ . '/../setup.php';

use \Bliss\Feeds;
use \Bliss\Controllers\Reader;
use \Bliss\Controllers\Manage;
use \Flight;

$app = new \Slim\Slim(
    array(
    'view' => new \Slim\Views\Smarty(),
    'templates.path' => BLISS_BASE_DIR . '/templates/'
    )
);

$app->view()->parserDirectory = BLISS_BASE_DIR . '/templates/';
$app->view()->parserCompileDirectory = Feeds::option('cache_dir');
$app->view()->parserCacheDirectory = Feeds::option('cache_dir');

$base_uri = "//{$_SERVER['HTTP_HOST']}" . dirname($_SERVER['SCRIPT_NAME']);
$app->view()->setData('bliss_version', BLISS_VERSION);
$app->view()->setData('base_uri', $base_uri);

/* ######### Ajax requests ################################ */

$app->post(
    // Called to load the next batch of articles
    '/load_next/:filter', function ($filter) use ($app) {
        if (($next = Reader::next($filter))) {
            $app->render('article.snippet.tpl.html', $next);
        }
    }
);

$app->post(
    // Mark a Post as 'read'
    '/read', function () use ($app) {
        echo Reader::read();
    }
);

$app->post(
    // Flag a Post
    '/flag', function () use ($app) {
        echo Reader::flag();
    }
);

$app->get(
    // Called when not a single article has been loaded
    '/nothing/:filter', function ($filter) use ($app) {
        $app->render('nothing.snippet.tpl.html', Reader::nothing($filter));
    }
);

$app->post(
    // Loaded regularly to check for new posts
    '/poll/:filter', function ($filter) use ($app) {
        echo Reader::poll($filter);
    }
);



/* ######### Access to the image cache #################### */
$app->get(
    // get an image from cache and display
    '/image', function () use ($app) {
        echo Reader::image();
    }
);

/* ######### Gallery ###################################### */
if (Feeds::option('enable_gallery') == true) {

    $app->view()->setData('enable_gallery', true);

    $app->get(
        // Gallery Mainpage
        '/gallery', function () use ($app) {
            $app->render('gallery.tpl.html', array('title' => 'Image Gallery'));

        }
    );

    $app->post(
        // Gallery single Page
        '/gallery_page/:page', function ($page) use ($app) {
            $app->render(
                'gallery.snippet.tpl.html', 
                Reader::galleryPage($page)
            );
        }
    );

}

/* ######### Archives ##################################### */
$app->get(
    // Load Archives
    '/archive', function () use ($app) {
        $app->render('archive.tpl.html', Reader::archive());
    }
);

/* ######### Config Stuff ################################# */
/*
Flight::route('GET /manage', array('Manage', 'feedlist'));
Flight::route('GET /opml', array('Manage', 'opml'));
Flight::route('POST /add_feed', array('Manage', 'add'));
Flight::route('POST /remove_feed', array('Manage', 'remove'));

/* ######### The Main Page ################################ */
$app->get(
    // Get the Main Page
    '/', function () use ($app) {
        $app->render('index.tpl.html');
    }
);

$app->run();

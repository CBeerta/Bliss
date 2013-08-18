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

define('BLISS_VERSION', '2.3.0');
define('BLISS_BASE_DIR', rtrim(__DIR__, '/'));

require_once BLISS_BASE_DIR . '/setup.php';

Flight::view()->assign('bliss_version', BLISS_VERSION);

/* ######### Ajax requests ################################ */
Flight::route('POST /load_next/@filter', array('Reader', 'next'));
Flight::route('POST /poll/@filter', array('Reader', 'poll'));
Flight::route('POST /flag', array('Reader', 'flag'));
Flight::route('POST /read', array('Reader', 'read'));
Flight::route('GET /nothing/@filter', array('Reader', 'nothing'));

/* ######### Access to the image cache #################### */
Flight::route('GET /image', array('Reader', 'image'));

/* ######### Gallery ###################################### */
if (Feeds::option('enable_gallery') != false) {
    Flight::view()->assign('enable_gallery', true);
    Flight::route('GET /gallery', array('Reader', 'gallery'));
    Flight::route('POST /gallery_page/@page', array('Reader', 'galleryPage'));
}

/* ######### Archives ##################################### */
Flight::route('GET /archive', array('Reader', 'archive'));

/* ######### Config Stuff ################################# */
Flight::route('GET /manage', array('Manage', 'feedlist'));
Flight::route('GET /opml', array('Manage', 'opml'));
Flight::route('POST /add_feed', array('Manage', 'add'));
Flight::route('POST /remove_feed', array('Manage', 'remove'));

/* ######### The Main Page ################################ */
Flight::route('GET /', array('Reader', 'index'));

if (PHP_SAPI == 'cli') {
    Fetch::parseArgs();
} else {
    $base_uri = "//{$_SERVER['HTTP_HOST']}" . dirname($_SERVER['SCRIPT_NAME']);
    Flight::set('base_uri', $base_uri);
    Flight::view()->assign('base_uri', $base_uri);

    Flight::start();
}

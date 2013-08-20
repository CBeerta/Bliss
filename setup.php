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

use Bliss\Feeds;
use Bliss\Controllers\Reader;

error_reporting(E_ALL);

define('BLISS_VERSION', '2.5.0');
define('BLISS_BASE_DIR', rtrim(__DIR__, '/'));

chdir(BLISS_BASE_DIR);

require_once BLISS_BASE_DIR . '/vendor/autoload.php';

date_default_timezone_set('GMT');

/**
* Load config file and override default options
**/
if (is_file(BLISS_BASE_DIR . "/config.ini")) {
    $config = parse_ini_file(BLISS_BASE_DIR . "/config.ini", false);
    foreach ($config as $k => $v) {
        Feeds::option($k, $v);
    }
}

if (getenv('OPENSHIFT_DATA_DIR')) {
    Feeds::option('sources', array());
    Feeds::option('filters', array());

    /* Running on Openshift */
    Feeds::option('data_dir', getenv('OPENSHIFT_DATA_DIR'));
    Feeds::option('cache_dir', getenv('OPENSHIFT_TMP_DIR'));

    Feeds::option('opml', Feeds::option('data_dir') . '/feeds.opml');
}

#!/usr/bin/env php
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

require_once __DIR__ . '/setup.php';

use \Bliss\Feeds;
use \Bliss\Store;
use \Bliss\Content_Plugin;

use \Bliss\Controllers\Fetch;

$app = new \Cling\Cling(
    array(
        'debug' => true,
        'log.destination' => \Cling\Logger::LOG_STDOUT,
        'log.severity' => \Cling\Logger::DEBUG,
        'log.dir' => Feeds::option('data_dir'),
        'template.path' => BLISS_BASE_DIR . '/templates/'
    )
);

$fetch = new \Bliss\Controllers\Fetch($app);

$app->command(
    // Help Text
    'help',
    'h',
    function () use ($app) {
        echo $app->notFound();
        exit;
    }
)->help("This Helptext.");

$app->command(
    // Update Feeds
    'update',
    'u',
    function () use ($app, $fetch) {
        $fetch->update();
    }
)->help("Load new Items from feeds");

$app->command(
    // Expire old Artices
    'expire',
    'e',
    function () use ($app, $fetch) {
        $fetch->expire();
    }
)->help("Expire old Articles");

$app->command(
    // Render Thumbs
    'thumbs',
    't',
    function () use ($app, $fetch) {
        if (Feeds::option('enable_gallery') != false) {
            $fetch->thumbs();
        }
    }
)->help("Build Thumbnails for all SPI files in the cache");

$app->run();



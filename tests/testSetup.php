<?php

Flight::map(
    'render', function($template, $data)
    {
        Flight::view()->assign($data);
        return Flight::view()->fetch($template);
    }
);

Flight::set('base_uri', '/');
Flight::view()->assign('base_uri', '/');
Flight::view()->assign('bliss_version', BLISS_VERSION);


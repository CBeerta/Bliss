<?php


Flight::map(
    'render', function($template, $data)
    {
        Flight::view()->assign($data);
        return Flight::view()->fetch($template);
    }
);



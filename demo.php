<?php

require 'vendor/autoload.php';

use Coderatio\SimpleBackup\SimpleBackup;

SimpleBackup::setDatabase(['test_import_db', 'root', ''])
    ->then()->importFrom('backups/my_db.sql')
    ->then(function ($simpleBackup) {
        echo var_dump($simpleBackup->getResponse());
    });

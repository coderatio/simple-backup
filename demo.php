<?php

require 'vendor/autoload.php';

use Coderatio\SimpleBackup\SimpleBackup;

$simpleBackup = SimpleBackup::start()
    ->setDbName('db_name')
    ->setDbUser('db_username')
    ->setDbPassword('db_password')
    ->then()->storeAfterExportTo('backups')
    ->then()->getResponse();

echo $simpleBackup->message;
    
 
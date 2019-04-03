<?php

require 'vendor/autoload.php';

use Coderatio\SimpleBackup\SimpleBackup;

$simpleBackup = SimpleBackup::start()
    ->setDbName('byarent')
    ->setDbUser('root')
    ->setDbPassword('')
    ->includeTables(['users', 'agents', 'houses', 'test'])
    ->then()->storeAfterExportTo('backups', 'byarent')
    ->then()->getResponse();

echo $simpleBackup->message;
    
 
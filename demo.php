<?php

    require 'vendor/autoload.php';

    use Coderatio\SimpleBackup\SimpleBackup;


    SimpleBackup::start()
        ->setDbName('byarent')
        ->setDbUser('root')
        ->setDbPassword('')
        ->includeOnly(['carts', 'houses', 'categories'])
        ->then()->storeAfterExportTo('backups', 'byarent')
        ->then()->getResponse();

    
 


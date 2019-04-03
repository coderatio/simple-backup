<?php

    require 'vendor/autoload.php';

    use Coderatio\SimpleBackup\SimpleBackup;


    SimpleBackup::start()
        ->setDbHost('DB_HOST') // Default is localhost
        ->setDbName('DB_NAME')
        ->setDbUser('DB_USERNAME')
        ->setDbPassword('DB_PASSWORD')
        ->then()->storeAfterExportTo('backups')
        ->then()->getResponse();

    
 


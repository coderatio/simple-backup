<?php
namespace Coderatio\SimpleBackup\Foundation;

use mysqli;

final class Database
{
    protected $connection;

    public static function prepare($config = [])
    {
       return new mysqli(
            $config['host'], 
            $config['db_user'],
            $config['db_password'],
            $config['db_name']
        ); 
    }
    
}

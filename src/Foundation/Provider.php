<?php
namespace Coderatio\SimpleBackup\Foundation;

use Coderatio\SimpleBackup\Foundation\Mysqldump;


class Provider extends Mysqldump
{
    public static function init($config)
    {
        $config['db_host'] = !isset($config['db_host']) ? 'localhost' : $config['db_host'];
        $config['include_tables'] = isset($config['include_tables']) ? $config['include_tables'] : [];
        $config['exclude_tables'] = isset($config['exclude_tables']) ? $config['exclude_tables'] : [];

        return new self(
            "mysql:host={$config['db_host']};dbname={$config['db_name']}",
            "{$config['db_user']}",
            "{$config['db_password']}",
            [
                'include-tables' => $config['include_tables'],
                'exclude-tables' => $config['exclude_tables']
            ]
        );
    }
}

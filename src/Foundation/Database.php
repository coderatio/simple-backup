<?php
namespace Coderatio\SimpleBackup\Foundation;

use mysqli;
use SebastianBergmann\Timer\RuntimeException;

final class Database
{
    protected $connection;

    public static function prepare($config = [])
    {
        $self = new self();

        $self->connection = new mysqli(
            $config['host'],
            $config['db_user'],
            $config['db_password'],
            $config['db_name']
        );

        if ($self->connection->connect_error) {
            throw new RuntimeException('Failed to connect to database: ' . $self->connection->connect_error);
        }

        return $self->connection;
    }
}

<?php
namespace Coderatio\SimpleBackup\Foundation;


final class Configurator
{
    protected $config = [
        'db_host' => 'localhost',
        'db_name' => '',
        'db_user' => '',
        'insert_chunk' => 100,
        'db_password' => ''
    ];

    protected function prepare($config = [])
    {
        if ($this->isAssociativeArray($config)) {
            foreach ($config as $key => $value) {
                if (isset($this->config[$key])) {
                    $this->config[$key] = $value;
                }
            }
        } else {
            $this->config['db_name'] = $config[0];
            $this->config['db_user'] = $config[1];
            $this->config['db_password'] = $config[2];
            $this->config['db_host'] = isset($config[3]) ? $config[3] : $this->config['db_host'];
            $this->config['insert_chunk'] = isset($config[4]) ? $config[4] : $this->config['insert_chunk'];
        }

        return $this;
    }

    public static function parse($config = [])
    {
        $self = new self();

        $self->prepare($config);

        return $self->config;
    }

    public static function insertDumpHeader($connection, $config)
    {
        $php_version = PHP_VERSION;
        $mysql_version = mysqli_get_server_info($connection);
        $generation_time = date('M d, Y ') . 'at ' . date('h:s A');
        $copyright =  'Coderatio';

        $contents = "-- Simple Backup SQL Dump\r\n-- Version 1.0\r\n-- https://www.github.com/coderatio/simple-backup/\r\n--\r\n-- Host: localhost:3306\r\n-- Generation Time: {$generation_time}\r\n-- MYSQL Server Version: {$mysql_version}\r\n-- PHP Version: {$php_version}\r\n-- Developer: Josiah O. Yahaya\r\n-- Copyright: {$copyright}";

        $contents .= "\r\n\r\nSET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\r\nSET AUTOCOMMIT = 0;\r\nSTART TRANSACTION;\r\nSET time_zone = \"+00:00\"\r\n\r\n--\r\n-- Database: `" . $config['db_name'] . "`\r\n-- Total Tables: {$config['total_tables']}\r\n--\r\n\r\n";

        return $contents;
    }

    protected function isAssociativeArray($config)
    {
        if (array_keys($config) !== range(0, count($config) - 1)) {
            return true;
        }

        return false;
    }
}

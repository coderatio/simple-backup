<?php
namespace Coderatio\SimpleBackup\Foundation;


final class Configurator
{
    protected $config = [
        'host' => 'localhost',
        'db_name' => '',
        'db_user' => '',
        'db_password' => ''
    ];

    protected function prepare($config = [])
    {
        if ($this->isAssociativeArray($config)) {
           foreach($config as $key => $value) {
                if (isset($this->config[$key])) {
                    $this->config[$key] = $value;
                }
            }
        } else {
            $this->config['db_name'] = $config[0];
            $this->config['db_user'] = $config[1];
            $this->config['db_password'] = $config[2];
            $this->config['host'] = isset($config[3]) ? $config[3] : $this->config['host'];
        }

        return $this;
    }

    public static function parse($config = [])
    {
        $self = new self();

        $self->prepare($config);

        return $self->config;
    }

    protected function isAssociativeArray($config)
    {
        if (array_keys($config) !== range(0, count($config) - 1)) {
            return true;
        }

        return false;
    }
}

<?php

namespace Coderatio\SimpleBackup;

use RuntimeException;
use Coderatio\SimpleBackup\Foundation\Database;
use Coderatio\SimpleBackup\Foundation\Provider;
use Coderatio\SimpleBackup\Foundation\Configurator;
use Coderatio\SimpleBackup\Exceptions\NoTablesFoundException;

/***************************************************
 * @Author: Coderatio
 * @Developer: Josiah Ovye Yahaya
 * @Licence: MIT
 * @Type: Library
 * @Title: Simple database backup
 * @Desc: A clean and simple mysql database backup library
 * @DBSupport: MySQL
 **************************************************/
class SimpleBackup
{
    /** @var array $config */
    protected $config = [];

    /** @var object $provider */
    protected $provider;

    /** @var boolean $condition_tables */
    protected $condition_tables = false;

    /** @var array $tables_to_set_conditions */
    protected $tables_to_set_conditions = [];

    /** @var boolean $set_table_limits */
    protected $set_table_limits = false;

    /** @var array $tables_to_set_limits */
    protected $tables_to_set_limits = [];

    /** @var mixed $connection */
    protected $connection;

    /** @var string $contents */
    protected $contents = '';

    /** @var string $export_name */
    protected $export_name = '';

    /** @var boolean $export_only_some_tables */
    protected $include_only_some_tables = false;

    /** @var array $tables_to_export */
    protected $tables_to_include = [];

    /** @var boolean $exclude_only_some_tables */
    protected $exclude_only_some_tables = false;

    /** @var array $tables_to_exclude */
    protected $tables_to_exclude = [];

    /** @var array $response */
    protected $response = [
        'status' => true,
        'message' => ''
    ];

    /** @var bool $to_download */
    protected $to_download = false;

    /** @var array $tables */
    protected $tables = [];

    /**
     * Get the instance of the class
     *
     * @return SimpleBackup
     */
    public static function start()
    {
        return new self();
    }


    /**
     * Set up mysql database connection details
     * @param array $config
     * @return $this
     */
    public static function setDatabase($config = [])
    {
        $self = new self();

        $self->parseConfig($config);

        return $self;
    }

    public function setDbHost($host)
    {
        $this->config['db_host'] = $host;

        return $this;
    }

    public function setDbName($db_name)
    {
        $this->config['db_name'] = $db_name;

        return $this;
    }

    public function setDbUser($db_user)
    {
        $this->config['db_user'] = $db_user;

        return $this;
    }

    public function setDbPassword($db_password)
    {
        $this->config['db_password'] = $db_password;

        return $this;
    }

    /**
     * Parse the config as associative or non-associative array
     * @param array $config
     * @return $this
     */
    protected function parseConfig($config = [])
    {
        $this->config = Configurator::parse($config);

        return $this;
    }

    /**
     * Get provider instance
     *
     * @return Provider
     */
    public function getProvider()
    {
        return $this->provider;
    }

    /**
     * Get the database tables
     * @return array
     */
    protected function getTargetTables()
    {
        $mysqli = Database::prepare($this->config);

        $this->connection = $mysqli;

        $mysqli->select_db($this->config['db_name']);
        $mysqli->query("SET NAMES 'utf8'");

        $target_tables = [];

        $query_tables = $mysqli->query('SHOW TABLES');

        while ($row = $query_tables->fetch_row()) {
            $target_tables[] = $row[0];
        }

        $this->tables = $target_tables;
        $this->config['total_tables'] = count($this->tables);

        return $this->tables;
    }

    /**
     * Build the sql pre_insert_statements to export
     * @return $this
     */
    protected function prepareExportContentsFrom($file_path)
    {
        try {
            $this->provider = Provider::init($this->config);

            if($this->include_only_some_tables && !empty($this->tables_to_include)) {
                $this->includeOnly($this->tables_to_include);
            }

            if($this->exclude_only_some_tables && !empty($this->tables_to_exclude)) {
                $this->excludeOnly($this->tables_to_exclude);
            }

            if ($this->condition_tables && !empty($this->tables_to_set_conditions)) {
                $this->provider->setTableWheres($this->tables_to_set_conditions);
            }

            if ($this->set_table_limits && !empty($this->tables_to_set_limits)) {
                $this->provider->setTableLimits($this->tables_to_set_limits);
            }

            $this->provider->start($file_path);

            $this->contents = Configurator::insertDumpHeader(
                $this->connection,
                $this->config
            );

            $this->contents .= file_get_contents($file_path);
        } catch (\Exception $e) {
            $this->response = [
                'status' => false,
                'message' => $e->getMessage()
            ];
        }

        return $this;
    }

    /**
     * This function takes care of the importing.
     * You should provide an sql file or contents.
     *
     * @param string $sql_file_OR_content
     * @param array $config
     * @return $this
     */
    public function importFrom($sql_file_OR_content, $config = [])
    {
        // Increase script loading time
        set_time_limit(3000);

        $error_message = '';
        $error_status = true;

        try {
            if (!empty($config)) {
                $this->parseConfig($config);
            }

            $sql_contents = (strlen($sql_file_OR_content) > 300
                ? $sql_file_OR_content
                : file_get_contents($sql_file_OR_content));

            $allLines = explode("\n", $sql_contents);

            $target_tables = $this->getTargetTables();

            $mysqli = $this->connection;

            $mysqli->query('SET foreign_key_checks = 0');
            preg_match_all("/\nCREATE TABLE(.*?)\`(.*?)\`/si", "\n" . $sql_contents, $target_tables);

            // Let's drop all tables on the database first.
            foreach ($target_tables[2] as $table) {
                $mysqli->query('DROP TABLE IF EXISTS ' . $table);
            }

            $mysqli->query('SET foreign_key_checks = 0');
            $mysqli->query("SET NAMES 'utf8'");

            $templine = '';    // Temporary variable, used to store current query

            // Loop through each line
            foreach ($allLines as $line) {

                // (if it is not a comment..) Add this line to the current segment
                if ($line != '' && strpos($line, '--') !== 0) {
                    $templine .= $line;

                    // If it has a semicolon at the end, it's the end of the query
                    if (substr(trim($line), -1, 1) == ';') {
                        if (!$mysqli->query($templine)) {
                            $this->response['status'] = true;
                            $error_message .= "<strong>Error performing query</strong>: {$templine} {$mysqli->error} <br/><br/>";
                        }

                        // set variable to empty, to start picking up the lines after ";"
                        $templine = '';
                    }
                }
            }
        } catch (\Exception $e) {
            $error_status = false;

            $this->response = [
                'status' => false,
                'message' => $e->getMessage()
            ];
        }

        $this->response['message'] = $error_message;

        if ($error_status === true) {
            $this->response['message'] = 'Importing finished successfully';
        }

        return $this;
    }

    /**
     * This function allows you download the export.
     *
     * @param string $export_name
     */
    public function downloadAfterExport($export_name = '')
    {
        $this->abortIfEmptyTables();

        $this->to_download = true;

        if (!empty($export_name)) {
            $this->export_name = $export_name;
        }

        $export_name = !empty($this->export_name)
            ? "{$this->export_name}.sql"
            : $this->config['db_name'] . '_db_backup_(' . date('H-i-s') . '_' . date('d-m-Y') . ').sql';

        $this->export_name = $export_name;

        $this->prepareExportContentsFrom($export_name);

        ob_get_clean();
        header('Content-Type: application/octet-stream');
        header('Content-Transfer-Encoding: Binary');
        header('Content-Length: ' . (function_exists('mb_strlen') ? mb_strlen($this->contents, '8bit') : strlen($this->contents)));

        header('Content-disposition: attachment; filename="' . $export_name . '"');

        echo $this->contents;

        $this->response['message'] = 'Export completed successfully';

        @unlink($export_name);

        exit;
    }

    /**
     * This method allows you store the exported db to a directory
     *
     * @param $path_to_store
     * @param null $name
     * @return $this
     */
    public function storeAfterExportTo($path_to_store, $name = null)
    {
        $this->abortIfEmptyTables();

        $export_name = $this->config['db_name'] . '_db_backup_(' . date('H-i-s') . '_' . date('d-m-Y') . ').sql';

        if ($name !== null) {
            $export_name = str_replace('.sql', '', $name) . '.sql';
        }

        $this->export_name = $export_name;

        if (!file_exists($path_to_store) && !mkdir($path_to_store)) {
            throw new RuntimeException(sprintf('Directory "%s" was not created', $path_to_store));
        }

        $file_path = $path_to_store . '/' . $export_name;

        $this->prepareExportContentsFrom($file_path);


        $file = fopen($file_path, 'wb') or die('Unable to open file!');
        fwrite($file, $this->contents);
        fclose($file);

        $this->response['message'] = 'Export finished successfully.';

        return $this;
    }

    /**
     * Get the just exported file name
     *
     * @return string
     */
    public function getExportedName()
    {
        return $this->export_name;
    }

    /**
     * This is used to chain more methods.
     * You can pass in a function to modify any other thing.
     *
     * @param null $callback
     * @return $this
     */
    public function then($callback = null)
    {
        if ($callback !== null && is_callable($callback)) {
            $callback($this);
        }

        return $this;
    }

    /**
     * Sets SQL like where clauses on tables before export
     *
     * @param array $tables
     * @return $this
     */
    public function setTableConditions($tables = [])
    {
        $this->tables_to_set_conditions = $tables;
        $this->condition_tables = true;

        return $this;
    }

    /**
     * Sets limits on tables before export
     *
     * @param array $tables
     * @return $this
     */
    public function setTableLimitsOn($tables = [])
    {
        $this->set_table_limits = true;
        $this->tables_to_set_limits = $tables;

        return $this;
    }

     /**
     *  Include only the tables mentioned in @var $tables
     *
     * @param array $tables
     * @return $this
     */
    public function includeOnly($tables = [])
    {
        $this->include_only_some_tables = true;

        $this->tables_to_include = array_filter($tables, function($table) {
            if(in_array($table, $this->getTargetTables())) {
                return $table;
            }
        });
        
        $this->tables = $this->tables_to_include;

        if(empty($this->tables_to_include)) {
            throw new NoTablesFoundException("No tables found to export.");
        }

        $this->config['include_tables'] = $this->tables_to_include;
        $this->config['total_tables'] = count($this->tables);

        return $this;
    }

    /**
     *  Exclude only the tables mentioned in @var $tables
     *
     * @param array $tables
     * @return $this
     */
    public function excludeOnly($tables = [])
    {
        $this->exclude_only_some_tables = true;

        $this->tables_to_exclude = array_filter($this->getTargetTables(), function($table) use ($tables) {
            if(in_array($table, $tables)) {
                return $table;
            }
        });

        $this->tables = array_filter($this->tables, function($table){
            if(!in_array($table, $this->tables_to_exclude)) {
                return $table;
            }

        });

        if(empty($this->tables)) {
            throw new NoTablesFoundException("No tables found to export.");
        }

        $this->config['exclude_tables'] = $this->tables_to_exclude;
        $this->config['total_tables'] = count($this->tables);

        return $this;
    }
    
    /**
     * Get the response for each action.
     *
     * @return string
     */
    public function getResponse()
    {
        return (object)$this->response;
    }

    /**
     * Check if database has atleast one table.
     *
     * @return $this
     */
    protected function abortIfEmptyTables()
    {
        if (empty($this->getTargetTables())) {
            die('No tables found on ' . $this->config['db_name']);
        }

        return $this;
    }
}

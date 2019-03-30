<?php
namespace Coderatio\SimpleBackup;

use Coderatio\SimpleBackup\Foundation\Database;
use Coderatio\SimpleBackup\Foundation\Configurator;
use RuntimeException;

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

    /** @var mixed $connection */
    protected $connection;

    /** @var string $contents */
    protected $contents = '';

    /** @var string $export_name */
    protected $export_name = '';

    /** @var string $response */
    protected $response = '';

    /** @var bool $to_download */
    protected $to_download = false;

    /**
     * Get the instance of the class
     *
     * @return SimpleBackup
     */
    public static function instance()
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
     * Get the database tables
     * @return array
     */
    protected function getTargetTables()
    {
        $mysqli = Database::prepare($this->config); 

        if ($mysqli->connect_error) {
            die('Connection failed: ' . $mysqli->connect_error);
        } 

        $this->connection = $mysqli;

        $mysqli->select_db($this->config['db_name']); 
        $mysqli->query("SET NAMES 'utf8'");

        $target_tables = [];

        $queryTables = $mysqli->query('SHOW TABLES'); 

        while($row = $queryTables->fetch_row()) { 
            $target_tables[] = $row[0]; 
        }	

        $this->config['tables'] = false;
        
        if($this->config['tables'] !== false) { 
            $target_tables = array_intersect( $target_tables, $this->config['tables']); 
        } 

        $this->contents = "--\r\n--EXPORTED WITH SIMPLE-BACKUP PACKAGE BY JOSIAH O. YAHAYA OF <CODERATIO>\r\n--\r\n\r\n\r\nSET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\r\nSET time_zone = \"+01:00\";\r\n\r\n\r\n/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;\r\n/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;\r\n/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;\r\n/*!40101 SET NAMES utf8 */;\r\n--\r\n-- Database: `".$this->config['db_name']."`\r\n--\r\n\r\n\r\n";
        
        return $target_tables;
    }

    /**
     * Build the sql statements to export
     * @return $this
     */
    protected function prepareExportContents()
    {
        // Increase script loading time
        set_time_limit(3000);

        $target_tables = $this->getTargetTables();

        //exit(var_dump($target_tables));

        foreach($target_tables as $table) {
            if (empty($table)){ 
                continue; 
            } 

            $result	= $this->connection->query('SELECT * FROM `'.$table.'`');  
            $fields_count = $result->field_count;  
            $rows_num = $this->connection->affected_rows; 	
            $show_tables_query = $this->connection->query('SHOW CREATE TABLE '.$table);
            $TableMLine = $show_tables_query->fetch_row(); 

            $this->contents .= "\n\n".$TableMLine[1].";\n\n";   
            $TableMLine[1] = str_ireplace('CREATE TABLE `','CREATE TABLE IF NOT EXISTS `', $TableMLine[1]);

            for ($i = 0, $string_count = 0; $i < $fields_count;   $i++, $string_count = 0) {
                while($row = $result->fetch_row())	{ 
                    //when started (and every after 100 command cycle):
                    if ($string_count % 100 == 0 || $string_count == 0 )	{
                        $this->contents .= "\nINSERT INTO ".$table. ' VALUES';}
                        $this->contents .= "\n(";

                        for($j = 0; $j < $fields_count; $j++) { 
                            $row[$j] = str_replace("\n","\\n", addslashes($row[$j]) ); 
                            
                            if (isset($row[$j])) {
                                $this->contents .= '"'.$row[$j].'"' ;
                            }  else { 
                                $this->contents .= '""';
                            }	   
                            
                            if ($j < ($fields_count - 1)) {
                                $this->contents .= ',';
                            }   
                        }        
                        
                        $this->contents .= ')';

                    //every after 100 command cycle [or at last line] ....p.s. but should be inserted 1 cycle eariler
                    if ( (($string_count + 1) % 100 == 0 && $string_count !=0 ) || $string_count + 1 == $rows_num) {
                        $this->contents .= ';';
                    } else {
                        $this->contents .= ',';
                    }	
                    
                    ++$string_count;
                }
            } 
            
            $this->contents .="\n\n\n";
        }

        $this->contents .= "\r\n\r\n/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;\r\n/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;\r\n/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;";

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
        if (!empty($config)) {
            $this->parseConfig($config);
        }

        // Increase script loading time
        set_time_limit(3000);

        $sql_contents = (strlen($sql_file_OR_content) > 300 
        ?  $sql_file_OR_content 
        : file_get_contents($sql_file_OR_content)); 

        $allLines = explode("\n", $sql_contents); 

        $target_tables = $this->getTargetTables();

        $mysqli = $this->connection;

        $mysqli->query('SET foreign_key_checks = 0');
        preg_match_all("/\nCREATE TABLE(.*?)\`(.*?)\`/si", "\n". $sql_contents, $target_tables); 

        foreach ($target_tables[2] as $table) {
            $mysqli->query('DROP TABLE IF EXISTS '.$table);
        }    

        //$query = $mysqli->query('SET foreign_key_checks = 0');    
        $mysqli->query("SET NAMES 'utf8'");	

        $templine = '';	// Temporary variable, used to store current query
        
        // Loop through each line
        foreach ($allLines as $line)	{											
            if ($line != '' && strpos($line, '--') !== 0) {
                // (if it is not a comment..) Add this line to the current segment
                $templine .= $line;

                // If it has a semicolon at the end, it's the end of the query
                if (substr(trim($line), -1, 1) == ';') {
                    if(!$mysqli->query($templine)) { 
                        print('Error performing query \'<strong>' . $templine . '\': ' . $mysqli->error . '<br /><br />');  
                    }  
                    
                    // set variable to empty, to start picking up the lines after ";"
                    $templine = ''; 
                }
            }
        }	
        
        $this->response = 'Importing finished successfully.';

        return $this;
    }

    /**
     * This function allows you download the export.
     *
     * @param string $export_name
     */
    public function downloadAfterExport($export_name = '')
    {
        $this->prepareExportContents();

        $this->to_download = true;

        if (!empty($export_name)) {
            $this->export_name = $export_name;
        }

        $export_name = !empty($this->export_name) 
        ? "{$this->export_name}.sql" 
        : $this->config['db_name'].'_db_backup_('.date('H-i-s').'_'.date('d-m-Y').').sql';

        $this->export_name = $export_name;

        ob_get_clean(); 
        header('Content-Type: application/octet-stream');  
        header('Content-Transfer-Encoding: Binary');
        header('Content-Length: '. (
            function_exists('mb_strlen') ? mb_strlen($this->contents, '8bit') : strlen($this->contents)
        ));    

        header('Content-disposition: attachment; filename="' .$export_name. '"');

        echo $this->contents;

        $this->response = 'Export completed successfully';

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
        $this->prepareExportContents();

        $export_name = $this->config['db_name'].'_db_backup_('.date('H-i-s').'_'.date('d-m-Y').').sql';

        if ($name !== null) {
            $export_name = str_replace('.sql', '', $name) . '.sql';
        }

        $this->export_name = $export_name;

        if (!mkdir($path_to_store) && !is_dir($path_to_store)) {
            throw new RuntimeException(sprintf('Directory "%s" was not created', $path_to_store));
        }

        $file = fopen($path_to_store . '/'. $export_name, 'wb') or die('Unable to open file!');
        fwrite($file, $this->contents);
        fclose($file);

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
     * Get the response for each action.
     *
     * @return string
     */
    public function getResponse()
    {
        return $this->response;
    }
}

# Simple-backup
<img src="/_docs/store_export.PNG"/><br/>
A simple mysql database backup library for php. This library helps you backup your mysql database to a directory in your project or download it to your machine. 

The library also allows you import or restore a database without stress. Follow the instructions below to get started.

## Installation
Open your terminal or command prompt and type the below command:
```vim
composer require coderatio/simple-backup
```
## New in version 1.0.1
1. You can now chain static `::start()` on `SimpleBackup class. 
2. This version includes fluent chaining of database settings.
3. Added `->includeOnly(array $tables)` which allows you to export data for only tables mentioned in `$tables` variable.
4. Added `->excludeOnly(array $tables)` which removes the inclussion of tables mentioned in `$tables` variable during export.

#### (v1.0.1) Exporting specific tables only
```php
  SimpleBackup::start()
    ->setDbName('db_name')
    ->setDbUser('db_username')
    ->setDbPassword('db_password')
    ->includeOnly(['carts', 'houses', 'categories'])
    ->then()->storeAfterExportTo('backups')
    ->then()->getResponse();
```

#### (v1.0.1) Excluding specific tables only
```php
  SimpleBackup::start()
    ->setDbName('db_name')
    ->setDbUser('db_username')
    ->setDbPassword('db_password')
    ->excludeOnly(['users', 'posts'])
    ->then()->storeAfterExportTo('backups')
    ->then()->getResponse();
```

---

## Exporting
The export can be done in two ways.
1. Store in a directory
2. Download to your machine

#### 1-- Store in a directory
To store the export in a directory, do this:
```php
require 'vendor/autoload.php';

use Coderatio\SimpleBackup\SimpleBackup;

// Set the database to backup
$simpleBackup = SimpleBackup::setDatabase(['db_name', 'db_user', 'db_password', 'db_host'])
  ->storeAfterExportTo('pathtostore', 'file_name (optional)');
```
To get the stored file name, you can echo it out like this:
```php
echo $simpleBackup->getExportedName();
```
You can also get the reponse by doing this:
```php
/**
* @return object
**/
var_dump($simpleBackup->getResponse());
```

#### 2-- Download
To download the export to your machine, do this:
```php
require 'vendor/autoload.php';

use Coderatio\SimpleBackup\SimpleBackup;

// Set the database to backup
$simpleBackup = SimpleBackup::setDatabase(['db_name', 'db_user', 'db_password', 'db_host'])
  ->downloadAfterExport($file_name (optional));
```
If $file_name isn't provided, a random name will be generated for the download.

## Adding where clauses to tables
To add where clauses as you would do on SQL, you can do this before exporting:

<b>Note:</b> `$tables` variable must be an associative array e.g
```php
$tables = [
  'users' => 'is_active = true'
];
```

```php
$simpleBackup->setTableConditions(array $tables);
```

## Setting rows limit on tables
To limit how many rows to be included in your backup for a table, do this before exporting:

<b>Note:</b> Just like adding where clauses, the `$tables` variable here must be an associative array. e.g
```php
$tables = [
  'users' => 50,
  'posts' => 50
]
```
```php
$simpleBackup->setTableLimitsOn(array $tables);
```


## Importing
This package makes importing or restoring your mysql database easy. To import your database, do this:
```php
require 'vendor/autoload.php';

use Coderatio\SimpleBackup\SimpleBackup;

// Set the database to backup
$simpleBackup = SimpleBackup::setDatabase(['db_name', 'db_user', 'db_password', 'db_host (optional)']])
    ->importFrom('pathtosql_file or sql_contents');

/**
* You can then dump the response like this. 
*
* @return object
**/
var_dump($simpleBackup->getResponse());
```
<b>Note:</b> You can provide sql statements as the parameter. You may also overwrite the database configuration by passing it as second parameter to the importFrom(). e.g `importFrom(pathtosql_file, array $db_config);`.

## Todo
1. Add a scheduler method to use with cron
2. Store backup in Dropbox, Google Drive.
3. Send backup to emails

## Contribution
To contribute to this project, send a pull request or find me on <a href="https://twitter.com/josiahoyahaya" target="_blank">Twitter</a>.

## License
This project is licenced with the MIT license. 


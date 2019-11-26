<?php

require 'vendor/autoload.php';

use Coderatio\SimpleBackup\SimpleBackup;

SimpleBackup::setDatabase(['test_papay', 'root', 'root'])->downloadAfterExport();

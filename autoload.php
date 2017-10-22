<?php

// Register additional autoloads here

$vendorDir = dirname(dirname(dirname(__DIR__))) . '/vendor';

$file = $vendorDir . '/studio-42/elfinder/php/autoload.php';
if (is_file($file)) require_once($file); // need if ElFinder-package was installed manually without Composer service

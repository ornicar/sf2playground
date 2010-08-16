<?php

require_once __DIR__.'/../mysql/MysqlKernel.php';

$kernel = new MysqlKernel('dev', true);
$kernel->handle()->send();

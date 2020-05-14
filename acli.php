<?php
require __DIR__.'/vendor/autoload.php';
use AKikhaev\Cli\cli;

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();
cli::run();

<?php

    require_once 'vendor/autoload.php';
    require_once 'run.php';

    $dotenv = Dotenv\Dotenv::createImmutable("./");
    $dotenv->load();

    ini_set('extension', 'php_imap.dll');

    $envDataHL = [
        'directory' => $_ENV['DIRECTORY_HL'],
        'username' => $_ENV['USER_HL'],
        'password' => $_ENV['PASSWORD_HL'],
        'dbHost' => $_ENV['DB_HOST_HL'],
        'dbUsername' => $_ENV['DB_USERNAME_HL'],
        'dbPassword' => $_ENV['DB_PASSWORD_HL'],
        'dataBase' => $_ENV['DB_DATABASE_HL'],
        'dbForeignTable' => $_ENV['DB_FOREIGN_TABLE_HL'],
        'titleText' => $_ENV['TITLE_TEXT_HL'],
        'mail' => 'hylok',
    ];
    $envDataHY = [
        'directory' => $_ENV['DIRECTORY_HY'],
        'username' => $_ENV['USER_HY'],
        'password' => $_ENV['PASSWORD_HY'],
        'dbHost' => $_ENV['DB_HOST_HY'],
        'dbUsername' => $_ENV['DB_USERNAME_HY'],
        'dbPassword' => $_ENV['DB_PASSWORD_HY'],
        'dataBase' => $_ENV['DB_DATABASE_HY'],
        'dbForeignTable' => $_ENV['DB_FOREIGN_TABLE_HY'],
        'titleText' => $_ENV['TITLE_TEXT_HL'],
        'mail' => 'hy-lok',
    ];

    run($envDataHL);
    run($envDataHY);

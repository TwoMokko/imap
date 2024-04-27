<?php
//    die('stop');

    require_once 'vendor/autoload.php';
    require_once 'run.php';
    require_once 'set.php';

    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->load();

    ini_set('extension', 'php_imap.dll');

    $envDataHL = new Common\Set(
        $_ENV['DIRECTORY_HL'],
        $_ENV['USER_HL'],
        $_ENV['PASSWORD_HL'],
        $_ENV['DB_HOST_HL'],
        $_ENV['DB_USERNAME_HL'],
        $_ENV['DB_PASSWORD_HL'],
        $_ENV['DB_DATABASE_HL'],
        $_ENV['DB_FOREIGN_TABLE_HL'],
        $_ENV['TITLE_TEXT_HL'],
        'hylok.ru',
    );

    $envDataHY = new Common\Set(
        $_ENV['DIRECTORY_HY'],
        $_ENV['USER_HY'],
        $_ENV['PASSWORD_HY'],
        $_ENV['DB_HOST_HY'],
        $_ENV['DB_USERNAME_HY'],
        $_ENV['DB_PASSWORD_HY'],
        $_ENV['DB_DATABASE_HY'],
        $_ENV['DB_FOREIGN_TABLE_HY'],
        $_ENV['TITLE_TEXT_HY'],
        'hy-lok.ru',
    );

    $envDataSW = new Common\Set(
        $_ENV['DIRECTORY_SW'],
        $_ENV['USER_SW'],
        $_ENV['PASSWORD_SW'],
        $_ENV['DB_HOST_SW'],
        $_ENV['DB_USERNAME_SW'],
        $_ENV['DB_PASSWORD_SW'],
        $_ENV['DB_DATABASE_SW'],
        $_ENV['DB_FOREIGN_TABLE_SW'],
        $_ENV['TITLE_TEXT_SW'],
        'swagelok.su',
    );

    $envDataWIKA = new Common\Set(
        $_ENV['DIRECTORY_WIKA'],
        $_ENV['USER_WIKA'],
        $_ENV['PASSWORD_WIKA'],
        $_ENV['DB_HOST_WIKA'],
        $_ENV['DB_USERNAME_WIKA'],
        $_ENV['DB_PASSWORD_WIKA'],
        $_ENV['DB_DATABASE_WIKA'],
        $_ENV['DB_FOREIGN_TABLE_WIKA'],
        $_ENV['TITLE_TEXT_WIKA'],
        'wika-manometry.ru',
    );

    $envDataCZ = new Common\Set(
        $_ENV['DIRECTORY_CZ'],
        $_ENV['USER_CZ'],
        $_ENV['PASSWORD_CZ'],
        $_ENV['DB_HOST_CZ'],
        $_ENV['DB_USERNAME_CZ'],
        $_ENV['DB_PASSWORD_CZ'],
        $_ENV['DB_DATABASE_CZ'],
        $_ENV['DB_FOREIGN_TABLE_CZ'],
        $_ENV['TITLE_TEXT_CZ'],
        'camozzi.ru.net',
    );

    run($envDataHL);
    run($envDataHY);
    run($envDataSW);
    run($envDataWIKA);
//    run($envDataCZ);

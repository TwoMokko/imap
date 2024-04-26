<?php
//    die('stop');

    require_once 'vendor/autoload.php';
    require_once 'run.php';

    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->load();

    ini_set('extension', 'php_imap.dll');

    class Sett {
        public string $id;
        public string $directory;

        public function __construct(string $id, string $directory)
        {
            $this->id = $id;
            $this->directory = $directory;
        }
    }

    $envDataHL2 = new Sett(1, $_ENV['DIRECTORY_HL']);

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
        'mail' => 'hylok.ru',
        'id' => 1
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
        'titleText' => $_ENV['TITLE_TEXT_HY'],
        'mail' => 'hy-lok.ru',
    ];
    $envDataSW = [
        'directory' => $_ENV['DIRECTORY_SW'],
        'username' => $_ENV['USER_SW'],
        'password' => $_ENV['PASSWORD_SW'],
        'dbHost' => $_ENV['DB_HOST_SW'],
        'dbUsername' => $_ENV['DB_USERNAME_SW'],
        'dbPassword' => $_ENV['DB_PASSWORD_SW'],
        'dataBase' => $_ENV['DB_DATABASE_SW'],
        'dbForeignTable' => $_ENV['DB_FOREIGN_TABLE_SW'],
        'titleText' => $_ENV['TITLE_TEXT_SW'],
        'mail' => 'swagelok.su',
    ];
    $envDataWIKA = [
        'directory' => $_ENV['DIRECTORY_WIKA'],
        'username' => $_ENV['USER_WIKA'],
        'password' => $_ENV['PASSWORD_WIKA'],
        'dbHost' => $_ENV['DB_HOST_WIKA'],
        'dbUsername' => $_ENV['DB_USERNAME_WIKA'],
        'dbPassword' => $_ENV['DB_PASSWORD_WIKA'],
        'dataBase' => $_ENV['DB_DATABASE_WIKA'],
        'dbForeignTable' => $_ENV['DB_FOREIGN_TABLE_WIKA'],
        'titleText' => $_ENV['TITLE_TEXT_WIKA'],
        'mail' => 'wika-manometry.ru',
    ];

    run($envDataHL);
//    run($envDataHY);
//    run($envDataSW);
//    run($envDataWIKA);

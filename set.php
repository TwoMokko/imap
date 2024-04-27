<?php

    namespace Common;

    class Set {
        public string $directory;
        public string $username;
        public string $password;
        public string $dbHost;
        public string $dbUsername;
        public string $dbPassword;
        public string $dataBase;
        public string $dbForeignTable;
        public string $titleText;
        public string $mail;

        public function __construct(
            string $directory,
            string $username,
            string $password,
            string $dbHost,
            string $dbUsername,
            string $dbPassword,
            string $dataBase,
            string $dbForeignTable,
            string $titleText,
            string $mail)
        {
            $this->directory = $directory;
            $this->username = $username;
            $this->password = $password;
            $this->dbHost = $dbHost;
            $this->dbUsername = $dbUsername;
            $this->dbPassword = $dbPassword;
            $this->dataBase = $dataBase;
            $this->dbForeignTable = $dbForeignTable;
            $this->titleText = $titleText;
            $this->mail = $mail;

        }
    }
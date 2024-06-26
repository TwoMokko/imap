<?php

require_once 'functions.php';

function run(Common\Set $envDataMail): void
{
    $table = 'email_tracking';
    $mailbox = '{imap.yandex.ru:993/imap/ssl}' . $envDataMail->directory;
    $imap = imap_open($mailbox, $envDataMail->username, $envDataMail->password);
    if (!$imap)
    {
        echo 'Ошибка соединения';
        return;
    }
    $ids = imap_search($imap, 'UNSEEN');

    if (!$ids)
    {
        echo date("Y-m-d H:i:s") . ' нет новых писем с почтового ящика: ' . $envDataMail->username . PHP_EOL;
        return;
    }

    $dbName = $envDataMail->dbUsername;
    $dbHost = $envDataMail->dbHost;
    $connect = new PDO("mysql:host=$dbHost;dbname=$dbName", $envDataMail->dataBase, $envDataMail->dbPassword);
    $connect->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);


    createTable($connect, $table, $envDataMail->dbForeignTable, 'id');

    $iter = 0;
    foreach ($ids as $id)
    {
        $data = getData($imap, $id, $connect, $envDataMail->dbForeignTable, $envDataMail->mail);
        if (!$data)
        {
            echo 'нет таких писем';
            continue;
        };



        echo '<br>-----------<br><br>';
//        echo '<strong>Mail ID:</strong> ', $clientMailId;
        echo '<strong>ID:</strong> ', $id, '<br>';
        echo '<strong>Дата:</strong> ', $data['date'], '<br>';
        echo '<strong>Тема:</strong> ', $data['subject'], '<br>';
        echo '<strong>Получатель:</strong> ', $data['recipient'], '<br>';
        echo '<strong>Отправитель: </strong>', $data['senderName'], ' (', $data['sender'], ')', '<br>';
        echo '<strong>Сценарий:</strong><br>', $data['scenario'], '<br>';
        echo '<strong>Сообщение:</strong><br>', $data['message'], '<br>';


        sendData($connect, $data, $table);
        $clientId = getClientId($connect, $envDataMail->dbForeignTable, $data['visitor_id']);
        $clientMailId = implode('.', [$envDataMail->mail, $id, uniqid('', true)]);
        sendMail($data, $envDataMail, $clientId, $clientMailId, $imap, $id);

        imap_setflag_full($imap, $id, 'seen');
    }

    imap_close($imap);
}
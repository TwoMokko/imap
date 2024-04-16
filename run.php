<?php

require_once 'functions.php';

function run(array $envDataHL): void {
    $table = 'email_tracking_test';
    $mailbox = '{imap.yandex.ru:993/imap/ssl}' . $envDataHL['directory'];
    $imap = imap_open($mailbox, $envDataHL['username'], $envDataHL['password']);
    if (!$imap) die('Ошибка соединения');
    $ids = imap_search($imap, 'UNSEEN');
//$ids = imap_search($imap, 'ALL');
//$ids = imap_search($imap, 'SINCE "15-Apr-2024" BEFORE "15-Apr-2024"', FT_PEEK);

    if (!$ids) die('нет новых писем');

    $dbName = $envDataHL['dbUsername'];
    $dbHost = $envDataHL['dbHost'];
    $connect = new PDO("mysql:host=$dbHost;dbname=$dbName", $envDataHL['dataBase'], $envDataHL['dbPassword']);
    $connect->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);


    createTable($connect, $table, $envDataHL['dbForeignTable'], 'id');

    $iter = 0;
    foreach ($ids as $id) {
        $data = getData($imap, $id, $connect, $envDataHL['dbForeignTable'], $envDataHL['mail']);
        if (!$data) {
            echo 'нет таких писем';
            continue;
        };

        echo '<strong>ID:</strong> ', $id, '<br>';
        echo '<strong>Дата:</strong> ', $data['date'], '<br>';
        echo '<strong>Тема:</strong> ', $data['subject'], '<br>';
        echo '<strong>Получатель:</strong> ', $data['recipient'], '<br>';
        echo '<strong>Отправитель: </strong>', $data['senderName'], ' (', $data['sender'], ')', '<br>';
//    echo '<strong>Сценарий:</strong><br>', $data['scenario'], '<br>';
        echo '<strong>Сообщение:</strong><br>', $data['message'], '<br>';
        echo '-----------<br><br>';

        sendData($connect, $data, $table);
        sendMail($data, $envDataHL);
        imap_setflag_full($imap, $id, 'seen');
    }

    imap_close($imap);
}
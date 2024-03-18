<?php
    ini_set('extension','php_imap.dll');

    $server                                     = '{imap.yandex.ru:993/imap/ssl}';
    $directory                                  = 'fold';
    $user                                       = 'sashamehovnikova@yandex.ru';
    $password                                   = 'valiDate22';
    $mailbox                                    = $server . $directory;

    $imap = imap_open($mailbox, $user, $password);
//    $mails_id = imap_search($imap, 'ALL');
    $mails_id = imap_search($imap, 'NEW');

    if (empty($mails_id)) die('нет писем');

    foreach ($mails_id as $num) {
        // Заголовок письма
        $header = imap_headerinfo($imap, $num);
        $header = json_decode(json_encode($header), true);
        $subject = mb_decode_mimeheader($header['subject']);
        $date = mb_decode_mimeheader($header['date']);
        $toadress = $header['toaddress'];
        $fromadress = $header['from'][0]['mailbox'] . "@" . $header['from'][0]['host'];

        echo mb_decode_mimeheader($header['date']) . "<br>";
        echo mb_decode_mimeheader($header['subject']) . "<br>";
        echo mb_decode_mimeheader($header['toaddress']) . "<br>";
        echo mb_decode_mimeheader($header['fromaddress']) . "<br>";
        echo $header['from'][0]['mailbox'] . "@" . $header['from'][0]['host'] . "<br>";

        // Тело письма
        $body = imap_fetchbody($imap, $num, 1);
        echo quoted_printable_decode($body);

        $file = 'sample.csv';
        $tofile = "$date;$fromadress;$toadress;сценарий;номер визита;рекл канал;$subject;$body";
        $bom = "\xEF\xBB\xBF";
        file_put_contents($file, $bom . file_get_contents($file) . $tofile);
    }

    imap_close($imap);



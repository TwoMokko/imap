<?php

    require_once 'vendor/autoload.php';

    $dotenv = Dotenv\Dotenv::createImmutable("./");
    $dotenv->load();

    ini_set('extension','php_imap.dll');

    $mailbox = $_ENV['SERVER'] . $_ENV['DIRECTORY'];

    $imap = imap_open($mailbox, $_ENV['USER'], $_ENV['PASSWORD']);
    $mails_id = imap_search($imap, 'ON "28-Apr-2023"');
//    $mails_id = imap_search($imap, 'ON "06-Apr-2023"');
//    $mails_id = imap_search($imap, "NEW');

    if (empty($mails_id)) die('нет новых писем');

    foreach ($mails_id as $num) {
        // Заголовок письма
        $header = imap_headerinfo($imap, $num);
        $header = json_decode(json_encode($header), true);
//        var_dump($header);
//        die;
        (in_array($header['subject'], $header)) ? $subject = mb_decode_mimeheader($header['subject']) : $subject = 'нет темы';
        $date = mb_decode_mimeheader($header['date']);
        $toadress = $header['toaddress'];
        $fromadress = $header['from'][0]['mailbox'] . "@" . $header['from'][0]['host'];
        $charset = imap_bodystruct($imap, $num, "1")->parameters[0]->value;

        dump(imap_bodystruct($imap, $num, "1"));
//        continue;
        echo '<strong>Дата</strong>'; echo mb_decode_mimeheader($header['date']) . "<br>";
        echo '<strong>Тема</strong>'; echo $subject . "<br>";
        echo '<strong>Кому</strong>'; echo mb_decode_mimeheader($header['toaddress']) . "<br>";
        echo '<strong>От Кого</strong>'; echo mb_decode_mimeheader($header['fromaddress']) . "<br>";
        echo '<strong>От кого еще раз</strong>'; echo $header['from'][0]['mailbox'] . "@" . $header['from'][0]['host'] . "<br>";

//        // Тело письма
//        $message = ((imap_body($imap, $num)));


        $message = imap_fetchbody($imap, $num, 1, FT_PEEK);
        dump(imap_fetchstructure($imap, $num));
        continue;
        $message = match ($charset) {
            'UTF-8' => quoted_printable_decode($message),
            'utf-8' => base64_decode($message),
            'koi8-r' => iconv("koi8-r//IGNORE", 'UTF-8', quoted_printable_decode($message)),
            default => imap_base64($message),
        };
//        $message = iconv("koi8-r//IGNORE", 'UTF-8', quoted_printable_decode($message));
//        $message = base64_decode($message);
        $message = strip_tags($message);
        $message = mb_eregi_replace("[^a-zа-яё0-9&.,!?:;%@#№$()=+-_'\/ ]", ' ', $message);
        $message = preg_replace("/[\r\n]/", '', $message);


        $message = str_replace($charset, '', $message);
        $message = str_replace('--', '', $message);
        $message = preg_replace('/charset *= *[^ ]*/m', '', $message);
        $message = preg_replace('/Content-[^:]*: *[^ ]*/m', '', $message);


        echo "<strong>ТЕЛО ПИСЬМА</strong>" . "<br>";
        print_r(($message));
        echo "<br>";


//        $file = 'sample.csv';
//        $tofile = "$date;$fromadress;$toadress;сценарий;номер визита;рекл канал;$subject;$message\n";
//        $bom = "\xEF\xBB\xBF";
//        file_put_contents($file, $bom . file_get_contents($file) . $tofile);
    }

    imap_close($imap);



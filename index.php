<?php

    require_once 'vendor/autoload.php';

    $dotenv = Dotenv\Dotenv::createImmutable("./");
    $dotenv->load();

    ini_set('extension','php_imap.dll');

    $mailbox = $_ENV['SERVER'] . $_ENV['DIRECTORY'];

    $imap = imap_open($mailbox, $_ENV['USER'], $_ENV['PASSWORD']);
    $mails_id = imap_search($imap, 'ON "06-Apr-2023"');
//    $mails_id = imap_search($imap, "NEW');

    if (empty($mails_id)) die('нет новых писем');

    foreach ($mails_id as $num) {
        // Заголовок письма
        $header = imap_headerinfo($imap, $num);
        $header = json_decode(json_encode($header), true);
        $subject = mb_decode_mimeheader($header['subject']);
        $date = mb_decode_mimeheader($header['date']);
        $toadress = $header['toaddress'];
        $fromadress = $header['from'][0]['mailbox'] . "@" . $header['from'][0]['host'];

        echo '<strong>Дата</strong>'; echo mb_decode_mimeheader($header['date']) . "<br>";
        echo '<strong>Тема</strong>'; echo mb_decode_mimeheader($header['subject']) . "<br>";
        echo '<strong>Кому</strong>'; echo mb_decode_mimeheader($header['toaddress']) . "<br>";
        echo '<strong>От Кого</strong>'; echo mb_decode_mimeheader($header['fromaddress']) . "<br>";
        echo '<strong>От кого еще раз</strong>'; echo $header['from'][0]['mailbox'] . "@" . $header['from'][0]['host'] . "<br>";

//        // Тело письма
//        $body = (quoted_printable_decode(imap_body($imap, $num)));
        $message = ((imap_body($imap, $num)));
//        echo "<strong>ТЕЛО ПИСЬМА</strong>" . "<br>";
//        echo ($body);
//        echo mb_convert_encoding($body, 'windows-1251', 'utf-8');

//        $structure = imap_fetchstructure($imap, $num);
//        if (isset($structure->parts[1])) {
//            $part = $structure->parts[1];
//            $message = imap_fetchbody($imap,$num,1);
//            if(str_contains($message, "<html")) {
//                $message = trim((quoted_printable_decode($message)));
//                echo "<br> 1 <br>";
//            }
//            else if ($part->encoding == 3) {
//                $message = imap_base64($message);
//                echo "<br> 2 <br>";
//            }
//            else if($part->encoding == 2) {
//                $message = imap_binary($message);
//                echo "<br> 3 <br>";
//            }
//            else if($part->encoding == 1) {
//                $message = imap_8bit($message);
//                echo "<br> 4 <br>";
//            }
//            else {
//                $message = trim(utf8_encode(quoted_printable_decode(imap_qprint($message))));
//                echo "<br> 5 <br>";
//            }
//        }
        $bom = "\xEF\xBB\xBF";

        $charset = imap_bodystruct($imap, $num, "1")->parameters[0]->value;
        // проверка, если письмо не в koi8-r, оно не должно идти дальше
        var_dump($charset);
//        die;

//        $message = mb_convert_encoding($message, 'windows-1251');
        $message = iconv("koi8-r", 'UTF-8', quoted_printable_decode($message));
//        $message = strip_tags($message);
        echo "<strong>ТЕЛО ПИСЬМА</strong>" . "<br>";
        echo '<pre>';
        print_r( $message);
        echo '</pre>';


//
//        $file = 'sample.csv';
//        $tofile = "$date;$fromadress;$toadress;сценарий;номер визита;рекл канал;$subject;$message\n";
//
//        file_put_contents($file, $bom . file_get_contents($file) . $tofile);
    }

    imap_close($imap);



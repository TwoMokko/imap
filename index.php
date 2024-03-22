<?php

require_once 'vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable("./");
$dotenv->load();

ini_set('extension', 'php_imap.dll');

$mailbox = $_ENV['SERVER'] . $_ENV['DIRECTORY'];

$imap = imap_open($mailbox, $_ENV['USER'], $_ENV['PASSWORD']);
//$mails_id = imap_search($imap, 'ON "15-Sep-2023"', FT_PEEK);
$mails_id = imap_search($imap, 'SINCE "15-Aug-2023" BEFORE "20-Sep-2023"', FT_PEEK);
//$mails_id = imap_search($imap, 'SEEN', FT_PEEK);
//    $mails_id = imap_search($imap, "NEW');

if (empty($mails_id)) die('нет новых писем');


foreach ($mails_id as $num) {
    // Заголовок письма
    $header = imap_headerinfo($imap, $num);
    $header = json_decode(json_encode($header), true);

    (in_array('subject', $header)) ? $subject = mb_decode_mimeheader($header['subject']) : $subject = 'нет темы';
    $date = mb_decode_mimeheader($header['date']);
    $toadress = $header['toaddress'];
    $fromadress = $header['from'][0]['mailbox'] . "@" . $header['from'][0]['host'];

    echo '<strong>Дата</strong>';
    echo mb_decode_mimeheader($header['date']) . "<br>";
    echo '<strong>Тема</strong>';
    echo $subject . "<br>";
    echo '<strong>Кому</strong>';
    echo mb_decode_mimeheader($header['toaddress']) . "<br>";
    echo '<strong>От Кого</strong>';
    echo mb_decode_mimeheader($header['fromaddress']) . "<br>";
    echo '<strong>От кого еще раз</strong>';
    echo $header['from'][0]['mailbox'] . "@" . $header['from'][0]['host'] . "<br>";





//        // Тело письма
//        $message = ((imap_body($imap, $num)));
    $parts = imap_fetchstructure($imap, $num);

//        var_dump($arr);

    $iter = 0;
//    $section = 0;
//    die;
    dump($parts);

    if (!function_exists('foundCharset')) {
        function foundCharset($part, $iter, $imap, $num, $key = 1): mixed
        {
            echo $iter . '<br>';
            if ($iter >= 10) return false;
            if (isset($part->subtype) && $part->subtype === 'HTML') {
                echo '<br>SUB ' . $part->subtype;
                echo '<br>ENCOD ' . $part->encoding;
                echo '<br>CHAR ' . $part->parameters[0]->value . '<br>';
                echo '<br>SECTION ' . $key . '<br>';
                return ['charset' => $part->parameters[0]->value, 'section' => $key, 'encoding' => $part->encoding];
            }
            if (!isset($part->parts)) return false;
            foreach ($part->parts as $key => $partInstance) {
//                if (!property_exists($part, 'parts')) continue;
                $result = foundCharset($partInstance, $iter + 1, $imap, $num, $key);
                if ($result !== false) return $result;
            }
            return false;
        }
    }


////    dump($parts->encoding);
////    continue;
    $ar = foundCharset($parts, $iter + 1, $imap, $num);
    $charset = $ar['charset'];
    $section = $ar['section'];
    $encoding = $ar['encoding'];
//    continue;
    $message = imap_fetchbody($imap, $num, 1, FT_PEEK);
    $message = match ($encoding) {
//        0 => quoted_printable_decode($message),
//        1 => quoted_printable_decode($message),
//        2 => quoted_printable_decode($message),
        3 => imap_base64($message),
        4 => imap_qprint($message),
        default => $message,
//        default => iconv("$charset//IGNORE", 'UTF-8', quoted_printable_decode($message)),
    };
    $message = iconv("$charset//IGNORE", 'UTF-8', quoted_printable_decode($message));
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

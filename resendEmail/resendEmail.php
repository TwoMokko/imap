<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;

require_once 'vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable("./");
$dotenv->load();

ini_set('extension', 'php_imap.dll');

$mailbox = $_ENV['SERVER'] . $_ENV['DIRECTORY'];

$imap = imap_open($mailbox, $_ENV['USER'], $_ENV['PASSWORD']);
if (!$imap) die('Ошибка соединения');
//$ids = imap_search($imap, 'UNSEEN');
//$ids = imap_search($imap, 'ALL');
$ids = imap_search($imap, 'SINCE "01-Apr-2024" BEFORE "10-Apr-2024"', FT_PEEK);
//$mails_id = imap_search($imap, 'ON "15-Sep-2023"', FT_PEEK);
//$mails_id = imap_search($imap, 'SEEN', FT_PEEK);
//$mails_id = imap_search($imap, "NEW');

if (!$ids) die('нет новых писем');


$iter = 0;
foreach ($ids as $id) {
    $data = getData($imap, $id);
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

    sendMail($data);
    imap_setflag_full($imap, $id, 'seen');
}

imap_close($imap);

function getData($imap, int $uid): array|bool {
    $headerInfo = imap_headerinfo($imap, $uid);
    $structure = imap_fetchstructure($imap, $uid, FT_UID);

    $matches = preg_match ('/[^ "][a-z0-9]{1,}@hylok.ru/m', $headerInfo->toaddress, $found);
    $recipient = ($matches) ? $found[0] : 'ошибка в регулярном выражении поиска емайла';

    if (!getScenario($recipient)) return false;

    return [
        'date' => $headerInfo->date,
        'subject' => property_exists($headerInfo, 'subject') ? mb_decode_mimeheader($headerInfo->subject) : 'нет темы',
        'recipient' => $recipient,
        'sender' => $headerInfo->from[0]->mailbox . '@' . $headerInfo->from[0]->host,
        'senderName' => mb_decode_mimeheader($headerInfo->fromaddress),
        'message' => (getBody($imap, $uid, $structure)),
    ];
}

function getBody($imap, int $uid, stdClass $structure): string {
    if ($body = getPart($imap, $uid, $structure, 'TEXT/HTML')) return $body;
    return getPart($imap, $uid, $structure, 'TEXT/PLAIN');
}

function getPart($imap, int $uid, stdClass $structure, string $mimeType, string $section = ''): string {
    if ($mimeType == getMimeType($structure)) {
        if (!$section) $section = 1;
        $text = imap_fetchbody($imap, $uid, $section);
        $text = match ($structure->encoding) {
            3 => imap_base64($text),
            4 => imap_qprint($text),
            default => $text,
        };
//        var_dump(($structure->parameters[0]->value));
        if (gettype($structure->parameters) == 'array' && $structure->parameters[0]->attribute == 'charset') {
            $text = match ($structure->parameters[0]->value) {
                'koi8-r' => mb_convert_encoding($text, 'UTF-8', 'KOI8-R'),
                'windows-1251' => mb_convert_encoding($text, 'UTF-8', 'WINDOWS-1251'),
                'ks_c_5601-1987' => mb_convert_encoding($text, 'UTF-8', 'EUC-KR'),
                default => $text
            };
        }


        return $text;
    }
    // MULTIPART
    if (property_exists($structure, 'parts') && $structure->type == 1) {
        foreach ($structure->parts as $index => $subStruct) {
            $prefix = $section ? $section . '.' : '';
            if ($data = getPart($imap, $uid, $subStruct, $mimeType, $prefix . ($index + 1))) return $data;
        }
    }

    return '';
}

function getMimeType(stdClass $structure): string {
    $primaryMimetype = ['TEXT', 'MULTIPART', 'MESSAGE', 'APPLICATION', 'AUDIO', 'IMAGE', 'VIDEO', 'OTHER'];

    return $structure->subtype ? $primaryMimetype[(int)$structure->type] . '/' . $structure->subtype : 'TEXT/PLAIN';
}

function getScenario(string $scenario): bool {
    $nameMail = explode('@', $scenario)[0];
    if ($nameMail === 'mail') return true;
    return false;
}

function sendMail($data): void
{
// Создаем письмо
    $mail = new PHPMailer();
    $mail->SMTPDebug = SMTP::DEBUG_SERVER;
    $mail->isSMTP();                                                                    // Отправка через SMTP
    $mail->Host   = $_ENV['SMTP_HOST'];                                                 // Адрес SMTP сервера
    $mail->Port   = 465;                                                                // Адрес порта
    $mail->SMTPAuth   = true;                                                           // Enable SMTP authentication
    $mail->Username   = $_ENV['SMTP_USER_NAME'];                                        // ваше имя пользователя (без домена и @) info@swagelok.su
    $mail->Password   = $_ENV['SMTP_PASSWORD'];                                         // ваш пароль zRX8r*5Z
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;                                    // шифрование ssl
    $mail->CharSet = "utf-8";

    $sender = $data['sender'];
    $message = $data['message'];

    $mail->setFrom('mail@hylok.ru', $data['senderName']);                       // от кого (email и имя)
    $mail->addAddress($_ENV['SMTP_TO_EMAIL'], $_ENV['SMTP_TO_NAME']);                   // кому (email и имя)
// html текст письма
    $mail->isHTML(true);
    $mail->Subject = $data['subject'] . '-hylok-site';

    $mail->msgHTML("<div style='background-color: lightgray;padding: 12px'><strong>Письмо от: </strong>$sender</div>$message");

// Отправляем
    if ($mail->send()) {
        echo 'Письмо отправлено!';
    } else {
        echo 'Ошибка: ' . $mail->ErrorInfo;
    }
}

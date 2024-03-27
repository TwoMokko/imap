<?php

require_once 'vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable("./");
$dotenv->load();

ini_set('extension', 'php_imap.dll');

$mailbox = $_ENV['SERVER'] . $_ENV['DIRECTORY'];

$imap = imap_open($mailbox, $_ENV['USER'], $_ENV['PASSWORD']);
if (!$imap) die('Ошибка соединения');
$ids = imap_search($imap, 'SINCE "15-Aug-2023" BEFORE "20-Sep-2023"', FT_PEEK);
//$mails_id = imap_search($imap, 'ON "15-Sep-2023"', FT_PEEK);
//$mails_id = imap_search($imap, 'SEEN', FT_PEEK);
//$mails_id = imap_search($imap, "NEW');

if (!$ids) die('нет новых писем');

$mysqli = mysqli_connect('localhost', 'root', '', 'test');

$iter = 0;
foreach ($ids as $id) {
    $data = getData($imap, $id);

		echo '<strong>Дата:</strong> ', $data['date'], '<br>';
		echo '<strong>Тема:</strong> ', $data['subject'], '<br>';
		echo '<strong>Получатель:</strong> ', $data['recipient'], '<br>';
		echo '<strong>Отправитель: </strong>', $data['senderName'], ' (', $data['sender'], ')', '<br>';
        echo '<strong>Сценарий:</strong><br>', $data['scenario'], '<br>';
        echo '<strong>Сообщение:</strong><br>', $data['message'], '<br>';
		echo '-----------<br><br>';

    sendData($mysqli, $data);
}

imap_close($imap);

function getData($imap, int $uid): array {
    $headerInfo = imap_headerinfo($imap, $uid);
    $structure = imap_fetchstructure($imap, $uid, FT_UID);

    return [
        'date' => $headerInfo->date,
        'subject' => property_exists($headerInfo, 'subject') ? mb_decode_mimeheader($headerInfo->subject) : 'нет темы',
        'recipient' => $headerInfo->toaddress,
        'sender' => $headerInfo->from[0]->mailbox . '@' . $headerInfo->from[0]->host,
        'senderName' => mb_decode_mimeheader($headerInfo->fromaddress),
        'message' => getBody($imap, $uid, $structure),
        'scenario' => getScenario($headerInfo->toaddress)
    ];
}

function getBody($imap, int $uid, stdClass $structure): string {
    if ($body = getPart($imap, $uid, $structure, 'TEXT/HTML')) return $body;
    return getPart($imap, $uid, $structure, 'TEXT/PLAIN');
}

function getPart($imap, int $uid, stdClass $structure, string $mimeType, string $section = ''): string {
    if ($mimeType == getMimeType($structure)) {
        if (!$section) $section = 1;
        $text = imap_fetchbody($imap, $uid, $section, FT_PEEK);
        $text = match ($structure->encoding) {
            3 => imap_base64($text),
            4 => imap_qprint($text),
            default => $text,
        };
        if ($structure->parameters[0]->attribute == 'charset') {
//				echo $structure->parameters[0]->value, ' ';
            $text = match ($structure->parameters[0]->value) {
                'koi8-r' => mb_convert_encoding($text, 'UTF-8', 'KOI8-R'),
                'windows-1251' => mb_convert_encoding($text, 'UTF-8', 'WINDOWS-1251'),
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

function getScenario(string $scenario): string {
    $nameMail = explode('@', $scenario)[0];
    if ($nameMail === 'mail') return 'прямое';
    return 'подмена адреса';
}

function sendData(mysqli $mysqli, array $data): bool|mysqli_result {
    $date = mysqli_real_escape_string($mysqli, $data['date']);
    $subject = mysqli_real_escape_string($mysqli, $data['subject']);
    $recipient = mysqli_real_escape_string($mysqli, $data['recipient']);
    $sender = mysqli_real_escape_string($mysqli, $data['sender']);
    $message = mysqli_real_escape_string($mysqli, $data['message']);
    $scenario = mysqli_real_escape_string($mysqli, $data['scenario']);
    return mysqli_query("INSERT INTO `imap` (`date`, `subject`, `recipient`, `sender`, `message`, `scenario`) VALUES ($date, $subject, $recipient, $sender, $message, $scenario)");
}

<?php
die('die');

require_once 'vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable("./");
$dotenv->load();

ini_set('extension', 'php_imap.dll');

$mailbox = $_ENV['SERVER'] . $_ENV['DIRECTORY'];

$imap = imap_open($mailbox, $_ENV['USER'], $_ENV['PASSWORD']);
if (!$imap) die('Ошибка соединения');
//$ids = imap_search($imap, 'SINCE "09-Jun-2023" BEFORE "12-Jun-2023"', FT_PEEK);
$ids = imap_search($imap, 'ALL');
//$mails_id = imap_search($imap, 'ON "15-Sep-2023"', FT_PEEK);
//$mails_id = imap_search($imap, 'SEEN', FT_PEEK);
//$mails_id = imap_search($imap, "NEW');

if (!$ids) die('нет новых писем');
$dbName = $_ENV['DB_DATABASE'];
$dbHost = $_ENV['DB_HOST'];
$connect = new PDO("mysql:host=$dbHost;dbname=$dbName", $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD']);
$connect->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$table = $_ENV['DB_TABLE'];
$foreignTable = $_ENV['DB_FOREIGN_TABLE'];
$foreignField = $_ENV['DB_FOREIGN_FIELD'];

createTable($connect, $table, $foreignTable, $foreignField);

$iter = 0;
foreach ($ids as $id) {
    $data = getData($imap, $id, $connect);

		echo '<strong>ID:</strong> ', $id, '<br>';
		echo '<strong>Дата:</strong> ', $data['date'], '<br>';
		echo '<strong>Тема:</strong> ', $data['subject'], '<br>';
//		echo '<strong>Получатель:</strong> ', $data['recipient'], '<br>';
//		echo '<strong>Отправитель: </strong>', $data['senderName'], ' (', $data['sender'], ')', '<br>';
        echo '<strong>Сценарий:</strong><br>', $data['scenario'], '<br>';
//        echo '<strong>Сообщение:</strong><br>', $data['message'], '<br>';
		echo '-----------<br><br>';

    sendData($connect, $data, $table);
    imap_setflag_full($imap, $id, 'seen');
}

imap_close($imap);
$connect = null;

function getData($imap, int $uid, PDO $connect): array {
    $headerInfo = imap_headerinfo($imap, $uid);
    $structure = imap_fetchstructure($imap, $uid, FT_UID);

    $matches = preg_match ('/[^ "][a-z0-9]{1,}@hylok.ru/m', $headerInfo->toaddress, $found);
    $recipient = ($matches) ? $found[0] : 'ошибка в регулярном выражении поиска емайла';

    $scenarioAndVid = getScenarioAndVid($recipient, $connect);

    return [
        'date' => $headerInfo->date,
        'subject' => property_exists($headerInfo, 'subject') ? mb_decode_mimeheader($headerInfo->subject) : 'нет темы',
        'recipient' => $recipient,
        'sender' => $headerInfo->from[0]->mailbox . '@' . $headerInfo->from[0]->host,
        'senderName' => mb_decode_mimeheader($headerInfo->fromaddress),
        'message' => strip_tags(getBody($imap, $uid, $structure)),
        'scenario' => $scenarioAndVid['scenario'],
        'visitor_id' => $scenarioAndVid['visitor_id']
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

function getScenarioAndVid(string $scenario, PDO $connect): array {
    $nameMail = explode('@', $scenario)[0];
    if ($nameMail === 'mail') return [
        'scenario' => 'прямое',
        'visitor_id' => null
    ];
    $stmt = $connect->prepare("SELECT `id` FROM visitor_info WHERE `vid` = ?");
    $stmt->execute([$nameMail]);
    $result = $stmt->fetch();
    $vid = $result ? $result['id'] : null;
//    var_dump($result);
    return [
        'scenario' => 'подмена адреса',
        'visitor_id' => $vid
    ];
}

function createTable(PDO $connect, string $table, string $foreignTable, string $foreignField): void {
    $sql = "CREATE TABLE IF NOT EXISTS $table (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY NOT NULL,
    `visitor_id` INT,
    `date` TIMESTAMP,
    `subject` VARCHAR(250),
    `recipient` VARCHAR(50),
    `sender` VARCHAR(50),
    `message`	 TEXT,
    `scenario` SET('прямое', 'подмена адреса'), 
	FOREIGN KEY (visitor_id) REFERENCES $foreignTable($foreignField)
    )";
    $connect->exec($sql);
}

function sendData(PDO $connect, array $data, string$table): void {
    $date = new DateTime($data['date']);
    $dateResult = $date->format('Y-m-d H:i:s');
//    var_dump($data['message']);
    $stmt = $connect->prepare("INSERT INTO $table VALUES (0, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$data['visitor_id'], $dateResult, $data['subject'], $data['recipient'], $data['sender'], $data['message'], $data['scenario']]);
//    return mysqli_stmt_get_result($stmt);
}

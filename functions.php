<?php

    use PHPMailer\PHPMailer\PHPMailer;
    use PHPMailer\PHPMailer\SMTP;

    function getData($imap, int $uid, PDO $connect, string $foreignTable, string $mail): array|bool {
        $headerInfo = imap_headerinfo($imap, $uid);
        $structure = imap_fetchstructure($imap, $uid);

        switch ($mail) {
            case 'hy-lok.ru':
                $matches = preg_match ('/[^ "][a-z0-9]{1,}@hy-lok.ru/m', $headerInfo->toaddress, $found);
                break;
            case 'hylok.ru':
                $matches = preg_match ('/[^ "][a-z0-9]{1,}@hylok.ru/m', $headerInfo->toaddress, $found);
                break;
            case 'swagelok.su':
                $matches = preg_match ('/[^ "][a-z0-9]{1,}@.swagelok.su/m', $headerInfo->toaddress, $found);
                break;
            case 'wika-manometry.ru':
                $matches = preg_match ('/[^ "][a-z0-9]{1,}@wika-manometry.ru/m', $headerInfo->toaddress, $found);
                break;
            default:
                break;
        }
        $recipient = ($matches) ? $found[0] : $headerInfo->toaddress;
//        $recipient = ($matches) ? $found[0] : 'ошибка в регулярном выражении поиска емайла';

        $scenarioAndVid = getScenarioAndVid($recipient, $connect, $foreignTable, $mail);

        return [
            'date' => $headerInfo->date,
            'subject' => property_exists($headerInfo, 'subject') ? mb_decode_mimeheader($headerInfo->subject) : 'нет темы',
            'recipient' => $recipient,
            'sender' => $headerInfo->from[0]->mailbox . '@' . $headerInfo->from[0]->host,
            'senderName' => mb_decode_mimeheader($headerInfo->fromaddress),
            'message' => (getBody($imap, $uid, $structure)),
            'scenario' => $scenarioAndVid['scenario'],
            'visitor_id' => $scenarioAndVid['visitor_id'],
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


    function getScenarioAndVid(string $scenario, PDO $connect, string $foreignTable, string $mail): array {
        $arraySeparatorMail = explode('@', $scenario);
        if ($arraySeparatorMail[1] !== $mail) return [
            'scenario' => 'откуда-то еще',
            'visitor_id' => null
        ];
        $nameMail = $arraySeparatorMail[0];
        if ($nameMail === 'mail') return [
            'scenario' => 'прямое',
            'visitor_id' => null
        ];
        $stmt = $connect->prepare("SELECT `id` FROM $foreignTable WHERE `vid` = ?");
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
        `scenario` SET('прямое', 'подмена адреса', 'откуда-то еще'), 
        FOREIGN KEY (visitor_id) REFERENCES $foreignTable($foreignField)
        )";
        $connect->exec($sql);
    }

    function getClientId(PDO $connect, string $foreignTable, mixed $vid): mixed {
        if ($vid === null) return 'test';
        $stmt = $connect->prepare("SELECT `client_id` FROM $foreignTable WHERE `id` = ?");
        $stmt->execute([$vid]);
        $result = $stmt->fetch();
        if ($result['client_id'] === null) return 'barahlo';
        return $result ? $result['client_id'] : 'нет значения';
    }

    function sendData(PDO $connect, array $data, string $table): void {
        $date = new DateTime($data['date']);
        $dateResult = $date->format('Y-m-d H:i:s');
        $stmt = $connect->prepare("INSERT INTO $table VALUES (0, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$data['visitor_id'], $dateResult, $data['subject'], $data['recipient'], $data['sender'], $data['message'], $data['scenario']]);
    }

    function sendMail(array $data, array $dataEnv, string $clientId): void
    {
    // Создаем письмо
        $mail = new PHPMailer();
        $mail->SMTPDebug = SMTP::DEBUG_SERVER;
        $mail->isSMTP();                                                                    // Отправка через SMTP
        $mail->Host   = $_ENV['SMTP_HOST'];                                                 // Адрес SMTP сервера
        $mail->Port   = $_ENV['SMTP_PORT'];                                                                // Адрес порта
        $mail->SMTPAuth   = true;                                                           // Enable SMTP authentication
        $mail->Username   = $_ENV['SMTP_EMAIL'];                                            // ваше имя пользователя (без домена и @) info@swagelok.su
        $mail->Password   = $_ENV['SMTP_PASSWORD'];                                         // ваш пароль zRX8r*5Z
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;                                    // шифрование ssl
        $mail->CharSet = "utf-8";

        $sender = $data['sender'];
        $message = $data['message'];

        $mail->setFrom($_ENV['SMTP_TO_EMAIL'], 'Zakaz Fluidline');                    // от кого (email и имя)
        $mail->addAddress($_ENV['SMTP_TO_EMAIL'], $_ENV['SMTP_TO_NAME']);                   // кому (email и имя)
    // html текст письма
        $mail->isHTML(true);
        $mail->Subject = $data['subject'] . $dataEnv['titleText'];
        $mail->addCustomHeader('X-client_mail', $sender);
        $mail->addCustomHeader('X-fluid_tag', $dataEnv['titleText']);
        $mail->addCustomHeader('X-client_id', $clientId);

        $mail->msgHTML($message);

    // Отправляем
        if ($mail->send()) {
            echo 'Письмо отправлено!' . PHP_EOL;
        } else {
            echo 'Ошибка: ' . $mail->ErrorInfo;
        }
    }

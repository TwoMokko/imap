<?php



require_once 'vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable("./");
$dotenv->load();

use Ddeboer\Imap\Server;
use Ddeboer\Imap\SearchExpression;
use Ddeboer\Imap\Search\Email\To;
use Ddeboer\Imap\Search\Text\Body;


$server = new Server($_ENV['SERVER']);
$connection = $server->authenticate($_ENV['USER'], $_ENV['PASSWORD']);


$mailboxes = $connection->getMailboxes();

foreach ($mailboxes as $mailbox) {
    // Skip container-only mailboxes
    // @see https://secure.php.net/manual/en/function.imap-getmailboxes.php
    if ($mailbox->getAttributes() & \LATT_NOSELECT) {
        continue;
    }

    // $mailbox is instance of \Ddeboer\Imap\Mailbox
    printf('Mailbox "%s" has %s messages', $mailbox->getName(), $mailbox->count());
}

$mailbox = $connection->getMailbox($_ENV['DIRECTORY']);

$messages = $mailbox->getMessages();

//$search = new SearchExpression();
//$search->addCondition(new To('me@here.com'));
//$search->addCondition(new Body('contents'));
//
//$messages = $mailbox->getMessages($search);

foreach ($messages as $message) {
    $body = $message->getCompleteBodyHtml();    // Content of text/html part, if present
    if ($body === null) { // If body is null, there are no HTML parts, so let's try getting the text body
        $body = $message->getCompleteBodyText();    // Content of text/plain part, if present
    }
}

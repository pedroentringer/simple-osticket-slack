<?php
require_once (INCLUDE_DIR . 'class.signal.php');
require_once (INCLUDE_DIR . 'class.plugin.php');
require_once ('config.php');

include ('lib/Html2Text.php');

class SlackPlugin extends Plugin
{
    var $config_class = 'SlackPluginConfig';

    function bootstrap()
    {
        Signal::connect('ticket.created', array(
            $this,
            'onTicketCreated'
        ));
        Signal::connect('threadentry.created', array(
            $this,
            'onNewMessage'
        ));
    }

    function onTicketCreated(Ticket $ticket)
    {
        global $cfg;

        $payload = ["attachments" => [["color" => "#ffc107", "blocks" => [["type" => "header", "text" => ["type" => "plain_text", "text" => "🐛 Novo Chamado", "emoji" => true]], ["type" => "divider"], ["type" => "section", "fields" => [["type" => "mrkdwn", "text" => "*Título:*\n" . $ticket->getSubject() ], ["type" => "mrkdwn", "text" => "*Usuário:*\n" . sprintf('%s (%s)', $ticket->getEmail()
            ->getName() , $ticket->getEmail()) ]]], ["type" => "divider"], ["type" => "context", "elements" => [["type" => "plain_text", "text" => strip_tags($ticket->getLastMessage()
            ->getBody()
            ->getClean()) , "emoji" => true]]], ["type" => "divider"], ["type" => "actions", "elements" => [["type" => "button", "text" => ["type" => "plain_text", "text" => "Visualizar Chamado", "emoji" => true], "style" => "primary", "url" => $cfg->getUrl() . 'scp/tickets.php?id=' . $ticket->getId() ]]]]]]];

        $this->slackMessage($payload);

        return;
    }

    function onNewMessage(ThreadEntry $entry)
    {
        global $cfg;

        if ($entry instanceof MessageThreadEntry)
        {
            $ticketId = $entry->getThreadId();

            $ticket = Ticket::lookup($ticketId);

            if ($ticket)
            {
                $payload = ["attachments" => [["color" => "#2583db", "blocks" => [["type" => "header", "text" => ["type" => "plain_text", "text" => "💬 Nova Resposta - Chamado", "emoji" => true]], ["type" => "divider"], ["type" => "section", "fields" => [["type" => "mrkdwn", "text" => "*Título:*\n" . $ticket->getSubject() ], ["type" => "mrkdwn", "text" => "*Usuário:*\n" . $entry->getPoster() ]]], ["type" => "divider"], ["type" => "context", "elements" => [["type" => "plain_text", "text" => $this->escapeText($entry->getBody()
                    ->body) , "emoji" => true]]], ["type" => "divider"], ["type" => "actions", "elements" => [["type" => "button", "text" => ["type" => "plain_text", "text" => "Visualizar Comentário", "emoji" => true], "style" => "primary", "url" => $cfg->getUrl() . 'scp/tickets.php?id=' . $ticketId . "#reply"]]]]]]];

                $this->slackMessage($payload);
            }

        }

        return;
    }

    function slackMessage($payload)
    {

        $data_string = utf8_encode(json_encode($payload));

        $url = $this->getConfig()
            ->get('slack-webhook-url');

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLINFO_HEADER_OUT, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            sprintf('Content-Length: %s', strlen($data_string)) ,
        ));

        $response = curl_exec($ch);

        curl_close($ch);

    }

    function escapeText($text)
    {
        $text = Html2Text\Html2Text::convert($text);

        $text = preg_replace("/[\r\n]+/", "\n", $text);
        $text = preg_replace("/[\n\n]+/", "\n", $text);

        if (strlen($text) >= $this->getConfig()
            ->get('slack-text-length'))
        {
            $text = substr($text, 0, $this->getConfig()
                ->get('slack-text-length')) . '...';
        }
        return $text;
    }

}


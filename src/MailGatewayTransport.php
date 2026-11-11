<?php

declare(strict_types=1);

namespace MailGateway\Laravel;

use MailGateway\MailGateway;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\MessageConverter;

/**
 * Sends Laravel Mailables through the MailGateway.id Email API.
 * The API accepts one recipient per request, so each To/Cc/Bcc address is sent separately.
 */
final class MailGatewayTransport extends AbstractTransport
{
    public function __construct(private readonly MailGateway $client)
    {
        parent::__construct();
    }

    protected function doSend(SentMessage $message): void
    {
        $email = MessageConverter::toEmail($message->getOriginalMessage());
        $from = $email->getFrom()[0] ?? null;
        if ($from === null) {
            throw new \RuntimeException('MailGateway: alamat From wajib diisi.');
        }
        $recipients = array_merge($email->getTo(), $email->getCc(), $email->getBcc());
        $replyTo = $email->getReplyTo()[0] ?? null;
        $headers = $email->getHeaders();
        $baseKey = $headers->get('Idempotency-Key')?->getBodyAsString()
            ?? $headers->get('X-Idempotency-Key')?->getBodyAsString();
        $headers->remove('Idempotency-Key');
        $headers->remove('X-Idempotency-Key');
        $multi = count($recipients) > 1;

        foreach ($recipients as $address) {
            $payload = array_filter([
                'from' => $from->toString(),
                'to' => [$address->getAddress()],
                'subject' => $email->getSubject(),
                'html' => $email->getHtmlBody() !== null ? (string) $email->getHtmlBody() : null,
                'text' => $email->getTextBody() !== null ? (string) $email->getTextBody() : null,
                'replyTo' => $replyTo?->getAddress(),
            ], static fn ($v) => $v !== null && $v !== '');
            $key = $baseKey ? ($multi ? substr($baseKey . ':' . $address->getAddress(), 0, 255) : $baseKey) : null;
            $this->client->send($payload, $key);
        }
    }

    public function __toString(): string
    {
        return 'mailgateway';
    }
}

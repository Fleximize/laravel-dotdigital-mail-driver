<?php

declare(strict_types=1);

namespace Fleximize\LaravelDotmailerMailDriver\Mail\Transport;

use Fleximize\LaravelDotmailerMailDriver\DotmailerClient;
use Fleximize\LaravelDotmailerMailDriver\Enums\DotmailerEnum;
use Fleximize\LaravelDotmailerMailDriver\Exceptions\DotmailerRequestFailedException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\MessageConverter;

class DotmailerTransport extends AbstractTransport
{
    public function __construct(private DotmailerClient $dotmailerClient)
    {
        parent::__construct();
    }

    protected function doSend(SentMessage $message): void
    {
        $email = MessageConverter::toEmail($message->getOriginalMessage());

        $response = $this->dotmailerClient->sendMail(
            $email->getFrom()[0]->getAddress(),
            array_map(fn ($address) => $address->getAddress(), $email->getTo()),
            array_map(fn ($address) => $address->getAddress(), $email->getCc()),
            array_map(fn ($address) => $address->getAddress(), $email->getBcc()),
            $email->getSubject(),
            $email->getHtmlBody(),
            $email->getTextBody(),
            $email->getAttachments(),
        );

        if ($response->successful()) {
            return;
        }

        throw new DotmailerRequestFailedException($response);
    }

    public function __toString(): string
    {
        return DotmailerEnum::DOTMAILER->value;
    }
}

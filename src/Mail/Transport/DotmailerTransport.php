<?php

declare(strict_types=1);

namespace Samharvey\LaravelDotmailerMailDriver\Mail\Transport;

use Samharvey\LaravelDotmailerMailDriver\DotmailerClient;
use Samharvey\LaravelDotmailerMailDriver\Enums\DotmailerEnum;
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

        $this->dotmailerClient->sendMail(
            $email->getFrom()[0]->getAddress(),
            array_map(fn ($address) => $address->getAddress(), $email->getTo()),
            array_map(fn ($address) => $address->getAddress(), $email->getCc()),
            array_map(fn ($address) => $address->getAddress(), $email->getBcc()),
            $email->getSubject(),
            $email->getHtmlBody(),
            $email->getTextBody(),
            $email->getAttachments(),
        );
    }

    public function __toString(): string
    {
        return DotmailerEnum::DOTMAILER->value;
    }
}

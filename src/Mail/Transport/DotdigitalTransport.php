<?php

declare(strict_types=1);

namespace Fleximize\LaravelDotdigitalMailDriver\Mail\Transport;

use Fleximize\LaravelDotdigitalMailDriver\DotdigitalClient;
use Fleximize\LaravelDotdigitalMailDriver\DTOs\DotdigitalAttachmentDTO;
use Fleximize\LaravelDotdigitalMailDriver\Enums\DotdigitalEnum;
use Fleximize\LaravelDotdigitalMailDriver\Exceptions\DotdigitalRequestFailedException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\MessageConverter;
use Symfony\Component\Mime\Part\DataPart;

class DotdigitalTransport extends AbstractTransport
{
    public function __construct(private DotdigitalClient $dotdigitalClient)
    {
        parent::__construct();
    }

    protected function doSend(SentMessage $message): void
    {
        $email = MessageConverter::toEmail($message->getOriginalMessage());

        $response = $this->dotdigitalClient->sendMail(
            $email->getFrom()[0]->getAddress(),
            array_map(fn ($address) => $address->getAddress(), $email->getTo()),
            array_map(fn ($address) => $address->getAddress(), $email->getCc()),
            array_map(fn ($address) => $address->getAddress(), $email->getBcc()),
            $email->getSubject(),
            $email->getHtmlBody(),
            $email->getTextBody(),
            array_map(
                fn (DataPart $attachment) => new DotdigitalAttachmentDTO(
                    $attachment->getFilename(),
                    "{$attachment->getMediaType()}/{$attachment->getMediaSubtype()}",
                    base64_encode($attachment->getBody()),
                ),
                $email->getAttachments(),
            ),
        );

        if ($response->successful()) {
            return;
        }

        throw new DotdigitalRequestFailedException($response);
    }

    public function __toString(): string
    {
        return DotdigitalEnum::DOTDIGITAL->value;
    }
}

<?php

declare(strict_types=1);

namespace Fleximize\LaravelDotmailerMailDriver;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Fleximize\LaravelDotmailerMailDriver\Enums\RequestMethodEnum;
use Fleximize\LaravelDotmailerMailDriver\Exceptions\MissingDotmailerCredentialsException;
use Fleximize\LaravelDotmailerMailDriver\Exceptions\MissingDotmailerEmailContentException;
use Fleximize\LaravelDotmailerMailDriver\Exceptions\MissingDotmailerRegionException;

class DotmailerClient
{
    private function makeRequest(
        RequestMethodEnum $requestMethod,
        string $endpoint,
        array $data,
        array $headers = [],
        bool $requiresAuth = true,
    ): Response {
        if (is_null($region = config('dotmailer.region'))) {
            throw new MissingDotmailerRegionException();
        }

        $authTuple = [config('dotmailer.username'), config('dotmailer.password')];

        if ($requiresAuth && count(array_filter($authTuple)) !== 2) {
            throw new MissingDotmailerCredentialsException();
        }

        if ($endpoint[0] !== '/') {
            // Ensure we have a leading slash for our request to the endpoint.
            $endpoint = '/'.$endpoint;
        }

        $endpoint = sprintf('https://%s-api.dotdigital.com%s', $region, $endpoint);

        $pendingRequest = Http::asJson()
            ->acceptJson()
            ->withHeaders($headers)
            ->when($requiresAuth, fn (PendingRequest $request) => $request->withBasicAuth(...$authTuple));

        return match ($requestMethod) {
            RequestMethodEnum::GET => $pendingRequest->get($endpoint, $data),
            RequestMethodEnum::POST => $pendingRequest->post($endpoint, $data),
            RequestMethodEnum::PUT => $pendingRequest->put($endpoint, $data),
            RequestMethodEnum::DELETE => $pendingRequest->delete($endpoint, $data),
            RequestMethodEnum::PATCH => $pendingRequest->patch($endpoint, $data),
        };
    }

    public function sendMail(
        string $fromEmail,
        string|array|null $toEmail,
        string|array|null $ccEmail,
        string|array|null $bccEmail,
        string $subject,
        ?string $htmlContent = null,
        ?string $textContent = null,
        ?array $attachments = null,
        string|array|null $metadata = null,
        ?array $tags = null,
    ): Response {
        if (is_null($htmlContent) && is_null($textContent)) {
            throw new MissingDotmailerEmailContentException();
        }

        $mailData = [
            'toAddresses' => Arr::wrap($toEmail),
            'ccAddresses' => Arr::wrap($ccEmail),
            'bccAddresses' => Arr::wrap($bccEmail),

            'subject' => $subject,
            'fromAddress' => $fromEmail,

            'htmlContent' => $htmlContent,
            'plainTextContent' => $textContent,
            'attachments' => $attachments,

            'metadata' => $metadata,
            'tags' => $tags,
        ];

        return $this->makeRequest(
            RequestMethodEnum::POST,
            '/v2/email',
            $mailData,
        );
    }
}

<?php

namespace Fleximize\LaravelDotdigitalMailDriver\Tests\Feature;

use Fleximize\LaravelDotdigitalMailDriver\DTOs\DotdigitalAttachmentDTO;
use Fleximize\LaravelDotdigitalMailDriver\Enums\DotdigitalEnum;
use Fleximize\LaravelDotdigitalMailDriver\Exceptions\DotdigitalRequestFailedException;
use Fleximize\LaravelDotdigitalMailDriver\Exceptions\MissingDotdigitalCredentialsException;
use Fleximize\LaravelDotdigitalMailDriver\Exceptions\MissingDotdigitalRegionException;
use Fleximize\LaravelDotdigitalMailDriver\Providers\LaravelDotdigitalMailDriverServiceProvider;
use Illuminate\Http\Client\Request;
use Illuminate\Http\Testing\File;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Orchestra\Testbench\Concerns\WithWorkbench;
use Orchestra\Testbench\TestCase;

class DotdigitalMailDriverTest extends TestCase
{
    use WithWorkbench;

    protected function getPackageProviders($app): array
    {
        return [
            LaravelDotdigitalMailDriverServiceProvider::class,
        ];
    }

    public static function mailDataProvider(): array
    {
        $uniqueFaker = fake()->unique();

        return [
            'all parameters present - html' => [
                $uniqueFaker->safeEmail(),
                $uniqueFaker->safeEmail(),
                $uniqueFaker->safeEmail(),
                $uniqueFaker->safeEmail(),
                $uniqueFaker->sentence(),
                true,
                false,
                [
                    File::fake()->createWithContent('test1.txt', 'File 1 content'),
                    File::fake()->createWithContent('test2.txt', 'File 2 content'),
                ],
            ],

            'all parameters present - plain text' => [
                $uniqueFaker->safeEmail(),
                $uniqueFaker->safeEmail(),
                $uniqueFaker->safeEmail(),
                $uniqueFaker->safeEmail(),
                $uniqueFaker->sentence(),
                false,
                true,
                [
                    File::fake()->createWithContent('test1.txt', 'File 1 content'),
                    File::fake()->createWithContent('test2.txt', 'File 2 content'),
                ],
            ],

            'only required parameters present - html' => [
                $uniqueFaker->safeEmail(),
                $uniqueFaker->safeEmail(),
                null,
                null,
                $uniqueFaker->sentence(),
                true,
                false,
                [],
            ],

            'only required parameters present - plain text' => [
                $uniqueFaker->safeEmail(),
                $uniqueFaker->safeEmail(),
                null,
                null,
                $uniqueFaker->sentence(),
                false,
                true,
                [],
            ],
        ];
    }

    public static function authDataProvider(): array
    {
        return [
            'username missing' => [
                'username' => null,
                'password' => 'test',
            ],

            'password missing' => [
                'username' => 'test',
                'password' => null,
            ],

            'both missing' => [
                'username' => null,
                'password' => null,
            ],
        ];
    }

    /**
     * @dataProvider mailDataProvider
     */
    public function test_can_send_mail_via_dotdigital_when_parameters_are_present(
        string $fromAddress,
        string|array $toAddresses,
        string|array|null $ccAddresses,
        string|array|null $bccAddresses,
        string $subject,
        bool $htmlContent,
        bool $plainTextContent,
        array $attachments,
    ): void {
        config()->set('dotdigital.region', 'test');
        config()->set('dotdigital.username', 'test');
        config()->set('dotdigital.password', 'test');

        Http::fake([
            '*' => Http::response(['message' => 'Mail sent'], 200),
        ]);

        $fakeMailable = new class($fromAddress, $toAddresses, $ccAddresses, $bccAddresses, $subject, $htmlContent, $plainTextContent, $attachments) extends Mailable
        {
            public function __construct(
                public string $testFromAddress,
                public string|array $testToAddresses,
                public string|array|null $testCcAddresses,
                public string|array|null $testBccAddresses,
                public string $testSubject,
                public bool $testHtmlContent,
                public bool $testPlainTextContent,
                public array $testAttachments,
            ) {
                //
            }

            public function envelope(): Envelope
            {
                return new Envelope(
                    from: $this->testFromAddress,
                    to: $this->testToAddresses,
                    cc: $this->testCcAddresses,
                    bcc: $this->testBccAddresses,
                    subject: $this->testSubject,
                );
            }

            public function content(): Content
            {
                return match (true) {
                    $this->testHtmlContent => new Content(
                        html: 'test_html_mailable_content',
                    ),

                    $this->testPlainTextContent => new Content(
                        text: 'test_text_mailable_content',
                    ),

                    default => throw new \InvalidArgumentException('Invalid content type!'),
                };
            }

            public function attachments(): array
            {
                return $this->testAttachments;
            }
        };

        Mail::driver(DotdigitalEnum::DOTDIGITAL->value)->send($fakeMailable);

        Http::assertSent(function (Request $request) use (
            $fromAddress,
            $toAddresses,
            $ccAddresses,
            $bccAddresses,
            $subject,
            $htmlContent,
            $plainTextContent,
            $attachments
        ) {
            $expectedData = [
                'fromAddress' => $fromAddress,
                'toAddresses' => Arr::wrap($toAddresses),
                'ccAddresses' => Arr::wrap($ccAddresses),
                'bccAddresses' => Arr::wrap($bccAddresses),
                'subject' => $subject,
                'htmlContent' => $htmlContent ? view('test_html_mailable_content')->render() : null,
                'plainTextContent' => $plainTextContent ? view('test_text_mailable_content')->render() : null,

                // We aren't passing these yet...
                'metadata' => null,
                'tags' => null,
            ];
            $actualData = $request->data();
            $requestAttachments = $request->data()['attachments'];

            // Do not compare attachments, since we would be comparing different classes.
            // We'll do this separately below, since we need to normalize the data first.
            unset($actualData['attachments']);

            $mappedFakedAttachments = array_map(fn (File $attachment) => [
                'fileName' => $attachment->getFilename(),
                'mimeType' => 'application/octet-stream', // These faked files are always viewed by PHP as octet-stream
                'content' => base64_encode($attachment->getContent()),
            ], $attachments);

            $mappedRequestAttachments = array_map(
                fn (DotdigitalAttachmentDTO $attachment) => (array) $attachment,
                $requestAttachments
            );

            // Sort the data to ensure the key orders match before checking equality
            ksort($expectedData);
            ksort($actualData);
            ksort($mappedFakedAttachments);
            ksort($mappedRequestAttachments);

            return $request->url() === 'https://test-api.dotdigital.com/v2/email'
                && $expectedData === $actualData
                && $mappedFakedAttachments === $mappedRequestAttachments
                && $request->hasHeader('Authorization', 'Basic '.base64_encode('test:test'))
                && $request->hasHeader('Content-Type', 'application/json')
                && $request->hasHeader('Accept', 'application/json');
        });

        Http::assertSentCount(1);
    }

    /**
     * @dataProvider authDataProvider
     */
    public function test_exception_is_thrown_when_username_or_password_is_missing(
        ?string $username,
        ?string $password,
    ): void {
        config()->set('dotdigital.region', 'test');
        config()->set('dotdigital.username', $username);
        config()->set('dotdigital.password', $password);

        $this->expectException(MissingDotdigitalCredentialsException::class);

        Http::fake([
            '*' => Http::response(['message' => 'Mail sent'], 200),
        ]);

        $fakeMailable = new class extends Mailable
        {
            public function envelope(): Envelope
            {
                return new Envelope(
                    from: 'from@example.com',
                    to: 'to@example.com',
                );
            }

            public function content(): Content
            {
                return new Content(
                    htmlString: '<p>This is an example email body</p>',
                );
            }
        };

        Mail::driver(DotdigitalEnum::DOTDIGITAL->value)->send($fakeMailable);

        Http::assertNothingSent();
    }

    public function test_exception_is_thrown_when_region_is_missing(): void
    {
        config()->set('dotdigital.region', null);
        config()->set('dotdigital.username', 'test');
        config()->set('dotdigital.password', 'test');

        $this->expectException(MissingDotdigitalRegionException::class);

        Http::fake([
            '*' => Http::response(['message' => 'Mail sent'], 200),
        ]);

        $fakeMailable = new class extends Mailable
        {
            public function envelope(): Envelope
            {
                return new Envelope(
                    from: 'from@example.com',
                    to: 'to@example.com',
                );
            }

            public function content(): Content
            {
                return new Content(
                    htmlString: '<p>This is an example email body</p>',
                );
            }
        };

        Mail::driver(DotdigitalEnum::DOTDIGITAL->value)->send($fakeMailable);

        Http::assertNothingSent();
    }

    public function test_exception_is_thrown_when_request_to_dotdigital_fails(): void
    {
        config()->set('dotdigital.region', 'test');
        config()->set('dotdigital.username', 'test');
        config()->set('dotdigital.password', 'test');

        $this->expectException(DotdigitalRequestFailedException::class);
        $this->expectExceptionMessage('Dotdigital request failed with status code 500 and body: {"message":"Mail NOT sent"}');

        Http::fake([
            '*' => Http::response(['message' => 'Mail NOT sent'], 500),
        ]);

        $fakeMailable = new class extends Mailable
        {
            public function envelope(): Envelope
            {
                return new Envelope(
                    from: 'from@example.com',
                    to: 'to@example.com',
                );
            }

            public function content(): Content
            {
                return new Content(
                    htmlString: '<p>This is an example email body</p>',
                );
            }
        };

        Mail::driver(DotdigitalEnum::DOTDIGITAL->value)->send($fakeMailable);

        Http::assertSentCount(1);
    }
}

<?php

namespace Samharvey\LaravelDotmailerMailDriver\Tests\Feature;

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
use Samharvey\LaravelDotmailerMailDriver\Enums\DotmailerEnum;
use Samharvey\LaravelDotmailerMailDriver\Providers\LaravelDotmailerMailDriverServiceProvider;
use Symfony\Component\Mime\Part\DataPart;

class DotmailerMailDriverTest extends TestCase
{
    use WithWorkbench;

    protected function getPackageProviders($app): array
    {
        return [
            LaravelDotmailerMailDriverServiceProvider::class,
        ];
    }

    public function mailDataProvider(): array
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
                [File::create('test.pdf'), File::create('test2.pdf')],
            ],

            'all parameters present - plain text' => [
                $uniqueFaker->safeEmail(),
                $uniqueFaker->safeEmail(),
                $uniqueFaker->safeEmail(),
                $uniqueFaker->safeEmail(),
                $uniqueFaker->sentence(),
                false,
                true,
                [File::create('test.pdf'), File::create('test2.pdf')],
            ],
        ];
    }

    /**
     * @dataProvider mailDataProvider
     */
    public function test_can_send_email_via_dotmailer_when_parameters_are_present(
        string $fromAddress,
        string|array|null $toAddresses,
        string|array|null $ccAddresses,
        string|array|null $bccAddresses,
        string $subject,
        bool $htmlContent,
        bool $plainTextContent,
        array $attachments,
    ): void {
        config()->set('dotmailer.region', 'test');
        config()->set('dotmailer.username', 'test');
        config()->set('dotmailer.password', 'test');

        Mail::fake();

        Http::fake([
            '*' => Http::response(['message' => 'Mail sent'], 200),
        ]);

        $fakeMailable = new class($fromAddress, $toAddresses, $ccAddresses, $bccAddresses, $subject, $htmlContent, $plainTextContent, $attachments) extends Mailable
        {
            public function __construct(
                public string $testFromAddress,
                public string|array|null $testToAddresses,
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

        Mail::driver(DotmailerEnum::DOTMAILER->value)->send($fakeMailable);

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
                'attachments' => $attachments,

                // We aren't passing these yet...
                'metadata' => null,
                'tags' => null,
            ];
            $actualData = $request->data();
            $requestAttachments = $request->data()['attachments'];

            // Do not compare attachments, since the class instances are different.
            // We'll do this separately below.
            unset($expectedData['attachments']);
            unset($actualData['attachments']);

            // Sort the data to ensure the key orders match before checking equality
            ksort($expectedData);
            ksort($actualData);

            if (
                Arr::first(
                    $request->data()['attachments'],
                    fn (DataPart $dataPart) => ! in_array(
                        $dataPart->getFilename(),
                        array_map(fn (File $uploadedFile) => $uploadedFile->getFilename(), $attachments)
                    )
                ) || count($requestAttachments) !== count($attachments)
            ) {
                // If we have a filename that is not present in the request attachments data,
                // or the count of attachments is different, then we know the attachments
                // are not the same.
                return false;
            }

            return $request->url() === 'https://test-api.dotdigital.com/v2/email'
                && $expectedData === $actualData;
        });
    }
}

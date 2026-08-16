<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domain\Price;
use App\Domain\PriceCollection;
use App\Domain\PriceProviderInterface;
use App\Mail\ResultSubmission;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

final class SubmissionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Stub the price provider so the submission flow never touches the
        // network; the report is non-empty so the email path runs.
        $this->app->bind(PriceProviderInterface::class, static function (): PriceProviderInterface {
            return new class implements PriceProviderInterface {
                public function getPrices(string $region, int $startUtc, int $endUtc): PriceCollection
                {
                    return new PriceCollection([
                        new Price($startUtc, 10.0),
                        new Price($startUtc + 900, 20.0),
                        new Price($startUtc + 1800, 30.0),
                    ]);
                }
            };
        });
    }

    public function testValidSubmissionSendsEmailAndRedirectsWithStatus(): void
    {
        Mail::fake();

        $response = $this->post(route('api.submissions'), [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'phone' => '+372 555 1234',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('status');

        Mail::assertSent(ResultSubmission::class, static function (ResultSubmission $mail): bool {
            return $mail->data['name'] === 'Test User'
                && $mail->data['email'] === 'test@example.com'
                && isset($mail->data['commit'])
                && isset($mail->data['phpVersion']);
        });
    }

    public function testInvalidSubmissionRedirectsWithErrorsAndSendsNoEmail(): void
    {
        Mail::fake();

        $response = $this->post(route('api.submissions'), [
            'name' => '',
            'email' => 'not-an-email',
            'phone' => 'abc',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors(['name', 'email', 'phone']);

        Mail::assertNothingSent();
    }
}

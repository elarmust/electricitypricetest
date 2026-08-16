<?php

declare(strict_types=1);

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Email sent when a candidate submits their result.
 * Holds only the already validated, structured data - no raw request objects.
 *
 * @property array<string, string> $data
 */
class ResultSubmission extends Mailable {
    use Queueable;
    use SerializesModels;

    /**
     * @param  array<string, string>  $data
     */
    public function __construct(public array $data) {
    }

    public function envelope(): Envelope {
        return new Envelope(subject: 'Electricity price test result submission');
    }

    public function content(): Content {
        return new Content(
            view: 'emails.result',
            with: ['data' => $this->data],
        );
    }
}

<?php

namespace App\Services;

use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Mail\Mailable;

class EmailService
{
    /**
     * Send an email using either SMTP or Mailtrap API based on configuration
     *
     * @param Mailable $mailable The mail class to send
     * @param string $recipient Recipient email address
     * @return bool Whether the email was sent successfully
     */
    public function send(Mailable $mailable, string $recipient)
    {
        // Check if SMTP is enabled in environment
        $useSmtp = filter_var(env('USE_SMTP', true), FILTER_VALIDATE_BOOLEAN);

        try {
            if ($useSmtp) {
                // Use Laravel's built-in Mail facade with SMTP configuration
                Mail::to($recipient)->send($mailable);
                return true;
            } else {
                // Use Mailtrap API directly
                return $this->sendViaMailtrapApi($mailable, $recipient);
            }
        } catch (\Exception $e) {
            Log::error('Email sending failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Send email using Mailtrap API
     *
     * @param Mailable $mailable The mail class to send
     * @param string $recipient Recipient email address
     * @return bool Whether the email was sent successfully
     */
    private function sendViaMailtrapApi(Mailable $mailable, string $recipient )
    {
        $mailtrapApiToken = env('MAILTRAP_API_TOKEN');
        $mailtrapInboxId = env('MAILTRAP_INBOX_ID');



        // Render the mailable to get its content
        $renderedMailable = $mailable->render();

        // Extract the subject from the mailable
        $subject = $mailable->subject ?? 'No Subject';

        // Extract the from address
        $fromAddress = env('MAIL_FROM_ADDRESS', 'noreply@example.com');
        $fromName = env('MAIL_FROM_NAME', 'Example App');
        if(env('APP_ENV') == 'local')
        {
            $mailtrapInboxId = env('MAILTRAP_INBOX_ID');
            $url = "https://sandbox.api.mailtrap.io/api/send/{$mailtrapInboxId}";
            if (!$mailtrapApiToken || !$mailtrapInboxId) {
                Log::error('Mailtrap API configuration missing');
                return false;
            }
        }
        else
        {
            if (!$mailtrapApiToken) {
                Log::error('Mailtrap API Token Is missing');
                return false;
            }
            $url = "https://send.api.mailtrap.io/api/send";
        }

        // Send via Mailtrap API
        $response = Http::withToken($mailtrapApiToken)
            ->post($url, [
                'to' => [
                    [
                        'email' => $recipient,
                    ]
                ],
                'from' => [
                    'email' => $fromAddress,
                    'name' => $fromName,
                ],
                'subject' => $subject,
                'html' => $renderedMailable,
                'category' => 'App Emails'
            ]);

        if ($response->successful()) {
            return true;
        } else {
            Log::error('Mailtrap API error: ' . $response->body());
            return false;
        }
    }
}

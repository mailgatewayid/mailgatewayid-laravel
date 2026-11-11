<?php

declare(strict_types=1);

namespace MailGateway\Laravel;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\ServiceProvider;
use MailGateway\MailGateway;

final class MailGatewayServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(MailGateway::class, function ($app) {
            $config = $app['config']->get('services.mailgateway', []);
            return new MailGateway(
                (string) ($config['key'] ?? env('MAILGATEWAY_API_KEY', '')),
                $config['base_url'] ?? env('MAILGATEWAY_BASE_URL'),
            );
        });
    }

    public function boot(): void
    {
        // Allows MAIL_MAILER=mailgateway without editing config/mail.php.
        $mailers = $this->app['config']->get('mail.mailers', []);
        if (!isset($mailers['mailgateway'])) {
            $this->app['config']->set('mail.mailers.mailgateway', ['transport' => 'mailgateway']);
        }
        Mail::extend('mailgateway', fn () => new MailGatewayTransport($this->app->make(MailGateway::class)));
    }
}

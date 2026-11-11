# mailgatewayid/laravel

```bash
composer require mailgatewayid/laravel
```

`.env`:

```env
MAIL_MAILER=mailgateway
MAILGATEWAY_API_KEY=mg_live_xxxxxxxx
MAIL_FROM_ADDRESS=no-reply@domainanda.id
```

Kode lama tetap jalan: `Mail::to($user)->send(new OrderShipped($order));`

Retry aman (Idempotency-Key, opsional, berlaku 24 jam). Tambahkan header di Mailable; untuk banyak penerima, key otomatis diberi akhiran alamat penerima:

```php
public function headers(): \Illuminate\Mail\Mailables\Headers
{
    return new \Illuminate\Mail\Mailables\Headers(text: ['Idempotency-Key' => 'invoice-' . $this->order->id]);
}
```

Verifikasi email & webhook:

```php
use MailGateway\MailGateway;

app(MailGateway::class)->verify('user@gmail.com');
MailGateway::verifyWebhookSignature($request->getContent(), $request->header('X-MailGateway-Signature'), env('MAILGATEWAY_WEBHOOK_SECRET'));
```

Catatan: lampiran belum didukung Email API; gunakan SMTP Relay untuk email berlampiran.

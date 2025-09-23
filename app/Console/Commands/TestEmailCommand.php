<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use App\Mail\TestMail;

class TestEmailCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:email {to=robot@tics.grupopakatnamu.com}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test email sending configuration';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $to = $this->argument('to');

        $this->info('Testing email configuration...');
        $this->info('To: ' . $to);

        // Mostrar configuración actual
        $this->info('Mail configuration:');
        $this->line('MAIL_MAILER: ' . config('mail.default'));
        $this->line('MAIL_HOST: ' . config('mail.mailers.smtp.host'));
        $this->line('MAIL_PORT: ' . config('mail.mailers.smtp.port'));
        $this->line('MAIL_USERNAME: ' . config('mail.mailers.smtp.username'));
        $this->line('MAIL_ENCRYPTION: ' . config('mail.mailers.smtp.encryption'));
        $this->line('MAIL_FROM_ADDRESS: ' . config('mail.from.address'));

        $this->info('PHP Extensions:');
        $this->line('OpenSSL: ' . (extension_loaded('openssl') ? '✅ Enabled' : '❌ Disabled'));
        $this->line('Sockets: ' . (extension_loaded('sockets') ? '✅ Enabled' : '❌ Disabled'));
        $this->line('cURL: ' . (extension_loaded('curl') ? '✅ Enabled' : '❌ Disabled'));

        try {
            Mail::to($to)->send(new TestMail());
            $this->info('✅ Email sent successfully!');
        } catch (\Exception $e) {
            $this->error('❌ Failed to send email: ' . $e->getMessage());
            $this->line('Full error: ' . get_class($e));
        }
    }
}

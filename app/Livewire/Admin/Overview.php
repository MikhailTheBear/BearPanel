<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Config;

class Overview extends Component
{
    /*
    |--------------------------------------------------------------------------
    | Overview Data
    |--------------------------------------------------------------------------
    */

    public string $app_name;
    public string $panel_version;
    public string $app_env;
    public string $app_locale;

    public array $locales = [];

    /*
    |--------------------------------------------------------------------------
    | SMTP Settings
    |--------------------------------------------------------------------------
    */

    public string $mail_mailer = 'smtp';
    public string $mail_host = '';
    public string $mail_port = '';
    public string $mail_username = '';
    public string $mail_password = '';
    public string $mail_encryption = '';
    public string $mail_from_address = '';
    public string $mail_from_name = '';

    public string $test_email = '';

    /*
    |--------------------------------------------------------------------------
    | SMTP Status
    |--------------------------------------------------------------------------
    */

    public bool $smtp_failed = false;
    public ?string $smtp_error = null;

    /*
    |--------------------------------------------------------------------------
    | Mount
    |--------------------------------------------------------------------------
    */

    public function mount(): void
    {
        $this->app_name      = config('app.name');
        $this->panel_version = config('app.version', env('PANEL_VERSION', '—'));
        $this->app_env       = config('app.env');
        $this->app_locale    = config('app.locale');

        $this->mail_mailer       = config('mail.default');
        $this->mail_host         = config('mail.mailers.smtp.host') ?? '';
        $this->mail_port         = config('mail.mailers.smtp.port') ?? '';
        $this->mail_username     = config('mail.mailers.smtp.username') ?? '';
        $this->mail_password     = config('mail.mailers.smtp.password') ?? '';
        $this->mail_encryption   = config('mail.mailers.smtp.encryption') ?? '';
        $this->mail_from_address = config('mail.from.address') ?? '';
        $this->mail_from_name    = config('mail.from.name') ?? '';

        $this->loadLocales();
    }

    /*
    |--------------------------------------------------------------------------
    | Validation Rules
    |--------------------------------------------------------------------------
    */

    protected function rules(): array
    {
        return [
            'app_locale'        => 'required|string',

            'mail_mailer'       => 'required|string',
            'mail_host'         => 'required|string',
            'mail_port'         => 'required|numeric',
            'mail_username'     => 'nullable|string',
            'mail_password'     => 'nullable|string',
            'mail_encryption'   => 'nullable|string',
            'mail_from_address' => 'required|email',
            'mail_from_name'    => 'required|string',

            'test_email'        => 'nullable|email',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Save Settings
    |--------------------------------------------------------------------------
    */

    public function save(): void
    {
        $this->validate();

        // App
        $this->setEnv('APP_LOCALE', $this->app_locale);

        // Mail
        $this->setEnv('MAIL_MAILER', $this->mail_mailer);
        $this->setEnv('MAIL_HOST', $this->mail_host);
        $this->setEnv('MAIL_PORT', $this->mail_port);
        $this->setEnv('MAIL_USERNAME', $this->mail_username);
        $this->setEnv('MAIL_PASSWORD', $this->mail_password);
        $this->setEnv('MAIL_ENCRYPTION', $this->mail_encryption);
        $this->setEnv('MAIL_FROM_ADDRESS', $this->mail_from_address);
        $this->setEnv('MAIL_FROM_NAME', $this->mail_from_name);

        Artisan::call('config:clear');

        session()->flash('status', __('Settings saved.'));
    }

    /*
    |--------------------------------------------------------------------------
    | Send Test Email
    |--------------------------------------------------------------------------
    */

    public function sendTestEmail(): void
    {
        $this->validateOnly('test_email');

        $this->smtp_failed = false;
        $this->smtp_error  = null;

        try {
            // Apply runtime config
            Config::set('mail.default', 'smtp');
            Config::set('mail.mailers.smtp.host', $this->mail_host);
            Config::set('mail.mailers.smtp.port', $this->mail_port);
            Config::set('mail.mailers.smtp.username', $this->mail_username);
            Config::set('mail.mailers.smtp.password', $this->mail_password);
            Config::set('mail.mailers.smtp.encryption', $this->mail_encryption);
            Config::set('mail.from.address', $this->mail_from_address);
            Config::set('mail.from.name', $this->mail_from_name);

            $recipient = $this->test_email ?: auth()->user()->email;

            Mail::raw(__('SMTP test email from BearPanel.'), function ($message) use ($recipient) {
                $message->to($recipient)
                        ->subject(__('SMTP Test Email'));
            });

            session()->flash('status', __('Test email sent successfully.'));
        } catch (\Throwable $e) {
            $this->smtp_failed = true;
            $this->smtp_error  = $e->getMessage();
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    protected function setEnv(string $key, string $value): void
    {
        $path = base_path('.env');

        if (!file_exists($path)) {
            return;
        }

        $env = file_get_contents($path);
        $escaped = preg_quote($key, '/');

        if (preg_match("/^{$escaped}=.*/m", $env)) {
            $env = preg_replace(
                "/^{$escaped}=.*/m",
                "{$key}=\"{$value}\"",
                $env
            );
        } else {
            $env .= PHP_EOL . "{$key}=\"{$value}\"";
        }

        file_put_contents($path, $env);
    }

    protected function loadLocales(): void
    {
    foreach (File::files(lang_path()) as $file) {

        if ($file->getExtension() !== 'json') {
            continue;
        }

        $code = $file->getFilenameWithoutExtension();
        $json = json_decode($file->getContents(), true);

        $this->locales[$code] =
            $json['_meta']['display_name']
            ?? $json['display_name']
            ?? strtoupper($code);
    }
}

    /*
    |--------------------------------------------------------------------------
    | Render
    |--------------------------------------------------------------------------
    */

    public function render()
    {
        return view('livewire.admin.overview')
            ->layout('layouts.app');
    }
}
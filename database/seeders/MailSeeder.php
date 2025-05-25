<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\MailTemplates\Models\MailTemplate;
use Illuminate\Support\Facades\File;

class MailSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Bulk Notification template
        if (!MailTemplate::where('mailable', 'App\Mail\Admin\BulkNotification')->exists()) {
            $bulkTemplate = File::get(resource_path('views/emails/bulk-notification.blade.php'));
            MailTemplate::create([
                'mailable' => 'App\Mail\Admin\BulkNotification',
                'subject' => '{{ config("app.name") }} - Notification',
                'html_template' => $bulkTemplate,
            ]);
        }

        // Migration Notification template
        if (!MailTemplate::where('mailable', 'App\Mail\Admin\MigrationNotification')->exists()) {
            $migrationTemplate = File::get(resource_path('views/emails/migration-notification.blade.php'));
            MailTemplate::create([
                'mailable' => 'App\Mail\Admin\MigrationNotification',
                'subject' => '{{ config("app.name") }} - Migration Information',
                'html_template' => $migrationTemplate,
            ]);
        }

        // Migration Password Reset template
        if (!MailTemplate::where('mailable', 'App\Mail\Admin\MigrationPasswordResetMail')->exists()) {
            $migrationPasswordTemplate = File::get(resource_path('views/emails/migration-password-reset.blade.php'));
            MailTemplate::create([
                'mailable' => 'App\Mail\Admin\MigrationPasswordResetMail',
                'subject' => 'New Account Password - {{ $site_name }}',
                'html_template' => $migrationPasswordTemplate,
            ]);
        }
    }
}
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\MailTemplates\Models\MailTemplate;
use App\Mail\Auth\NewDeviceLoginMail;
use App\Mail\Auth\FailedLoginMail;

class AuthLogEmailTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $templates = [
            [
                'mailable' => NewDeviceLoginMail::class,
                'subject' => (new NewDeviceLoginMail())->getDefaultSubject(),
                'html_template' => (new NewDeviceLoginMail())->getDefaultContent(),
            ],
            [
                'mailable' => FailedLoginMail::class,
                'subject' => (new FailedLoginMail())->getDefaultSubject(),
                'html_template' => (new FailedLoginMail())->getDefaultContent(),
            ],
        ];

        foreach ($templates as $template) {
            // Kiểm tra nếu template đã tồn tại để tránh duplicate
            $exists = MailTemplate::where('mailable', $template['mailable'])->exists();
            
            if (!$exists) {
                MailTemplate::create($template);
                $this->command->info('Created mail template for: ' . class_basename($template['mailable']));
            } else {
                $this->command->info('Mail template already exists for: ' . class_basename($template['mailable']));
            }
        }
    }
}
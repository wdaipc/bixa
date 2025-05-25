<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\MailTemplates\Models\MailTemplate;

class UpdateMailTemplatesSeeder extends Seeder
{
    public function run()
    {
        $template = MailTemplate::where('mailable', 'App\Mail\Admin\MigrationNotification')->first();
        if ($template) {
            $content = $template->html_template;
            
            if (strpos($content, '{{{ body }}}') === false) {
                $pattern = '/<td[^>]*>/i';
                $replacement = '$0{{{ body }}}';
                $modifiedContent = preg_replace($pattern, $replacement, $content, 1);
                
                $template->update([
                    'html_template' => $modifiedContent
                ]);
                
                $this->command->info('MigrationNotification template updated with body placeholder!');
            } else {
                $this->command->info('Template already has body placeholder.');
            }
        } else {
            // Tạo template mới nếu chưa tồn tại
            $templateContent = file_get_contents(resource_path('views/emails/migration-notification.blade.php'));
            
            MailTemplate::create([
                'mailable' => 'App\Mail\Admin\MigrationNotification',
                'subject' => '{{ config("app.name") }} - Migration Information',
                'html_template' => str_replace('{!! $content !!}', '{{{ body }}}', $templateContent),
            ]);
            
            $this->command->info('MigrationNotification template created!');
        }
    }
}
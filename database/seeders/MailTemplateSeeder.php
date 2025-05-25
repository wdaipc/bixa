<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\MailTemplates\Models\MailTemplate;

class MailTemplateSeeder extends Seeder
{
    public function run()
    {
        // Auth Templates
        MailTemplate::create([
            'mailable' => 'App\Mail\Auth\VerifyEmailMail',
            'subject' => 'Verify Your Email Address',
            'html_template' => '
                <h1>Verify Your Email</h1>
                <p>Hi {{name}},</p>
                <p>Please click the button below to verify your email address:</p>
                <p>
                    <a href="{{verification_url}}" 
                       style="background: #3490dc; color: white; padding: 12px 25px; text-decoration: none; border-radius: 4px;">
                        Verify Email Address
                    </a>
                </p>
                <p>If you did not create an account, no further action is required.</p>
            ',
            'variables' => 'name,verification_url'
        ]);

        MailTemplate::create([
            'mailable' => 'App\Mail\Auth\ResetPasswordMail',
            'subject' => 'Reset Password',
            'html_template' => '
                <h1>Reset Password</h1>
                <p>Hi {{name}},</p>
                <p>You are receiving this email because we received a password reset request for your account.</p>
                <p>
                    <a href="{{reset_url}}" 
                       style="background: #3490dc; color: white; padding: 12px 25px; text-decoration: none; border-radius: 4px;">
                        Reset Password
                    </a>
                </p>
                <p>This password reset link will expire in 60 minutes.</p>
                <p>If you did not request a password reset, no further action is required.</p>
            ',
            'variables' => 'name,reset_url'
        ]);

        // Hosting Templates
        MailTemplate::create([
            'mailable' => 'App\Mail\Hosting\AccountCreatedMail',
            'subject' => 'Your Hosting Account for {{domain}} is Ready',
            'html_template' => '
                <h1>Welcome to Your New Hosting Account!</h1>
                <p>Your hosting account has been created successfully.</p>
                <p><strong>Account Details:</strong></p>
                <ul>
                    <li>Domain: {{domain}}</li>
                    <li>Username: {{username}}</li>
                    <li>Password: {{password}}</li>
                    <li>Control Panel: {{cpanel_url}}</li>
                    <li>Label: {{label}}</li>
                </ul>
            ',
            'variables' => 'username,password,domain,cpanel_url,label'
        ]);

        MailTemplate::create([
            'mailable' => 'App\Mail\Hosting\AccountDeactivatedMail',
            'subject' => 'Hosting Account Deactivated - {{domain}}',
            'html_template' => '
                <h1>Hosting Account Deactivated</h1>
                <p>Your hosting account has been deactivated:</p>
                <ul>
                    <li>Domain: {{domain}}</li>
                    <li>Username: {{username}}</li>
                    <li>Label: {{label}}</li>
                </ul>
                <p><strong>Reason:</strong> {{reason}}</p>
            ',
            'variables' => 'username,domain,label,reason'
        ]);

        MailTemplate::create([
            'mailable' => 'App\Mail\Hosting\AccountReactivatedMail',
            'subject' => 'Hosting Account Reactivated - {{domain}}',
            'html_template' => '
                <h1>Hosting Account Reactivated</h1>
                <p>Your hosting account has been reactivated:</p>
                <ul>
                    <li>Domain: {{domain}}</li>
                    <li>Username: {{username}}</li>
                    <li>cPanel URL: {{cpanel_url}}</li>
                </ul>
            ',
            'variables' => 'username,domain,cpanel_url'
        ]);

        // Ticket Templates
        MailTemplate::create([
            'mailable' => 'App\Mail\Ticket\NewTicketMail',
            'subject' => '[Ticket #{{ticket_id}}] {{title}}',
            'html_template' => '
                <h1>New Support Ticket</h1>
                <p><strong>Ticket ID:</strong> #{{ticket_id}}</p>
                <p><strong>Subject:</strong> {{title}}</p>
                <p><strong>Category:</strong> {{category}}</p>
                <p><strong>Priority:</strong> {{priority}}</p>
                <p><strong>Service:</strong> {{service_info}}</p>
                <p><strong>Message:</strong><br>{{message}}</p>
            ',
            'variables' => 'ticket_id,title,message,category,priority,service_type,service_info'
        ]);

        MailTemplate::create([
            'mailable' => 'App\Mail\Ticket\TicketReplyMail',
            'subject' => 'Re: [Ticket #{{ticket_id}}] {{title}}',
            'html_template' => '
                <h1>New Reply to Your Ticket</h1>
                <p><strong>Ticket ID:</strong> #{{ticket_id}}</p>
                <p><strong>Subject:</strong> {{title}}</p>
                <p><strong>Reply from {{replier}}:</strong><br>{{reply}}</p>
            ',
            'variables' => 'ticket_id,title,reply,replier'
        ]);

        MailTemplate::create([
            'mailable' => 'App\Mail\Ticket\TicketStatusChangedMail',
            'subject' => 'Status Changed: [Ticket #{{ticket_id}}] {{title}}',
            'html_template' => '
                <h1>Ticket Status Updated</h1>
                <p><strong>Ticket ID:</strong> #{{ticket_id}}</p>
                <p><strong>Subject:</strong> {{title}}</p>
                <p><strong>Status changed from:</strong> {{old_status}} to {{new_status}}</p>
            ',
            'variables' => 'ticket_id,title,old_status,new_status'
        ]);
    }
}
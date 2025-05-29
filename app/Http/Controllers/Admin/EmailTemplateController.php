<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Spatie\MailTemplates\Models\MailTemplate;

class EmailTemplateController extends Controller
{
    public function index()
    {
        $templates = MailTemplate::all()->map(function($template) {
            return [
                'id' => $template->id,
                'subject' => $template->subject,
                'mailable' => class_basename($template->mailable),
                'used_for' => $this->getUsageDescription(class_basename($template->mailable))
            ];
        });
        
        return view('admin.email.index', compact('templates'));
    }
    
    private function getUsageDescription($classBasename)
    {
        return match($classBasename) {
            'VerifyEmailMail' => 'Email Verification',
            'ResetPasswordMail' => 'Password Reset',
            'AccountCreatedMail' => 'Hosting Account Created',
            'AccountDeactivatedMail' => 'Hosting Account Deactivated',
            'AccountReactivatedMail' => 'Hosting Account Reactivated',
            'NewTicketMail' => 'New Support Ticket',
            'TicketReplyMail' => 'Ticket Reply',
            'TicketStatusChangedMail' => 'Ticket Status Update',
            'NewDeviceLoginMail' => 'New Device Login Alert',
            'FailedLoginMail' => 'Failed Login Attempt Alert',
            'BulkNotification' => 'Bulk Email Notification',
            'MigrationNotification' => 'Migration Notification',
            'MigrationPasswordResetMail' => 'Migration Password Reset',
            default => ucwords(str_replace('Mail', '', $classBasename))
        };
    }
    
    public function edit($id)
    {
        $template = MailTemplate::findOrFail($id);
        
        // Map trực tiếp từ tên class
        $variables = [
            'App\Mail\Auth\VerifyEmailMail' => ['name', 'verification_url'],
            'App\Mail\Auth\ResetPasswordMail' => ['name', 'reset_url'],
            'App\Mail\Hosting\AccountCreatedMail' => ['username', 'password', 'domain', 'cpanel_url', 'label'],
            'App\Mail\Hosting\AccountDeactivatedMail' => ['username', 'domain', 'label', 'reason'],
            'App\Mail\Hosting\AccountReactivatedMail' => ['username', 'domain', 'label', 'cpanel_url'],
            'App\Mail\Hosting\PasswordChangedMail' => ['username', 'domain', 'new_password', 'cpanel_url'],
            'App\Mail\Ticket\NewTicketMail' => ['ticket_id', 'title', 'message', 'category', 'priority', 'service_type', 'service_info'],
            'App\Mail\Ticket\TicketReplyMail' => ['ticket_id', 'title', 'reply', 'replier'],
            'App\Mail\Ticket\TicketStatusChangedMail' => ['ticket_id', 'title', 'old_status', 'new_status'],
            'App\Mail\Auth\NewDeviceLoginMail' => ['name', 'time', 'ip_address', 'browser', 'device', 'location'],
            'App\Mail\Auth\FailedLoginMail' => ['name', 'time', 'ip_address', 'browser', 'device', 'location'],
            'App\Mail\Admin\BulkNotification' => ['content'],
            'App\Mail\Admin\MigrationNotification' => ['content'],
            'App\Mail\Admin\MigrationPasswordResetMail' => ['site_name', 'name', 'email', 'password', 'role', 'login_url'],
        ];
        
        // Lấy variables dựa trên mailable class
        $templateVariables = $variables[$template->mailable] ?? [];
        
        return view('admin.email.edit', compact('template', 'templateVariables'));
    }
    
    public function update(Request $request, $id)
    {
        $template = MailTemplate::findOrFail($id);
        
        // Create validator instance for better error handling
        $validator = Validator::make($request->all(), [
            'subject' => 'required|string|max:255',
            'html_template' => 'required|string|min:10'
        ], [
            'subject.required' => 'Subject field is required.',
            'subject.max' => 'Subject cannot be longer than 255 characters.',
            'html_template.required' => 'Template content is required.',
            'html_template.min' => 'Template content must be at least 10 characters.'
        ]);
        
        // Handle validation failures
        if ($validator->fails()) {
            // For AJAX requests
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed.',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            // For normal form submissions
            return back()
                ->withErrors($validator)
                ->withInput();
        }
        
        try {
            // Update the template
            $template->update([
                'subject' => $request->input('subject'),
                'html_template' => $request->input('html_template')
            ]);
            
            // For AJAX requests
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Template updated successfully!'
                ]);
            }
            
            // For normal form submissions - redirect back to edit page to continue editing
            return redirect()
                ->route('admin.email.edit', $id)
                ->with('success', 'Template updated successfully!');
                
        } catch (\Exception $e) {
            // Log the error for debugging
            \Log::error('Email template update error: ' . $e->getMessage());
            
            // For AJAX requests
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update template. Please try again.'
                ], 500);
            }
            
            // For normal form submissions
            return back()
                ->withInput()
                ->withErrors(['general' => 'Failed to update template. Please try again.']);
        }
    }
    
    /**
     * Handle image uploads for the email template editor
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function uploadImage(Request $request)
    {
        try {
            // Validate the uploaded file
            $validator = Validator::make($request->all(), [
                'image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:2048' // 2MB max
            ], [
                'image.required' => 'Please select an image to upload.',
                'image.image' => 'The file must be an image.',
                'image.mimes' => 'Only JPEG, PNG, JPG, GIF, and WebP images are allowed.',
                'image.max' => 'Image size cannot exceed 2MB.'
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'error' => $validator->errors()->first()
                ], 422);
            }
            
            $image = $request->file('image');
            
            // Generate unique filename
            $filename = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
            
            // Store in public/uploads/email-images directory
            $path = $image->storeAs('email-images', $filename, 'public');
            
            // Generate URL
            $url = asset('storage/' . $path);
            
            return response()->json([
                'success' => true,
                'data' => [
                    'url' => $url,
                    'filename' => $filename,
                    'original_name' => $image->getClientOriginalName(),
                    'size' => $image->getSize()
                ],
                'message' => 'Image uploaded successfully!'
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Email template image upload error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'error' => 'Failed to upload image. Please try again.'
            ], 500);
        }
    }
}
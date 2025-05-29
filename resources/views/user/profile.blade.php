@extends('layouts.master')

@section('title') @lang('translation.Profile') @endsection

@section('css')
<!-- Froala Editor CSS -->
<link href="https://cdn.jsdelivr.net/npm/froala-editor@latest/css/froala_editor.pkgd.min.css" rel="stylesheet">
<!-- Font Awesome for Froala icons -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<!-- CodeMirror CSS -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.62.0/codemirror.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.62.0/theme/monokai.min.css">
<!-- Froala Code View Plugin CSS -->
<link href="https://cdn.jsdelivr.net/npm/froala-editor@latest/css/plugins/code_view.min.css" rel="stylesheet">

<style>
    /* Signature Editor Container */
    .signature-container {
        margin-bottom: 30px;
    }

    .signature-label {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 15px;
    }

    /* Editor container styling */
    .signature-editor-container {
        position: relative;
        margin-bottom: 20px;
    }
    
    /* Froala editor styling */
    #signature-editor {
        min-height: 250px;
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        font-size: 14px;
        border: 1px solid #ced4da;
        border-radius: 0.25rem;
    }
    
    /* Upload progress indicator */
    #upload-progress {
        display: none;
        margin-top: 10px;
    }
    
    #upload-progress.show {
        display: block;
    }
    
    #upload-status {
        font-size: 0.875rem;
        margin-top: 0.25rem;
    }
    
    /* Toast notification */
    .toast-message {
        position: fixed;
        bottom: 20px;
        right: 20px;
        padding: 12px 20px;
        background-color: #28a745;
        color: #fff;
        border-radius: 4px;
        z-index: 9999;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        display: none;
    }
    
    .toast-message.error {
        background-color: #dc3545;
    }
    
    .toast-message.show {
        display: block;
        animation: fadeInOut 3s ease-in-out;
    }
    
    @keyframes fadeInOut {
        0% { opacity: 0; }
        15% { opacity: 1; }
        85% { opacity: 1; }
        100% { opacity: 0; }
    }
    
    /* Responsive YouTube Embeds */
    .responsive-embed-container {
        position: relative;
        padding-bottom: 56.25%; /* 16:9 aspect ratio */
        height: 0;
        overflow: hidden;
        max-width: 100%;
        margin: 15px 0;
        border-radius: 8px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }
    
    .responsive-embed-container iframe {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        border: 0;
        border-radius: 8px;
    }
    
    /* Style images in editor */
    .fr-view img {
        max-width: 100%;
        height: auto;
        border-radius: 4px;
        margin: 5px 0;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    }
    
    /* Mobile optimizations */
    @media (max-width: 767.98px) {
        #signature-editor {
            min-height: 200px;
            font-size: 13px;
        }
        
        /* Make toolbar scrollable on small screens */
        .fr-toolbar {
            overflow-x: auto;
            white-space: nowrap;
            padding-bottom: 8px;
        }
    }
</style>
@endsection

@section('content')
@component('components.breadcrumb')
@slot('li_1') @lang('translation.Home') @endslot
@slot('title') @lang('translation.Profile') @endslot
@endcomponent

<div class="row">
    <div class="col-xl-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4 class="card-title mb-0">@lang('translation.Personal_Information')</h4>
                    <button type="button" class="btn btn-primary waves-effect waves-light" 
                            onclick="editProfile()">
                        <i data-feather="edit-2" class="icon-sm me-1"></i> @lang('translation.Edit_Profile')
                    </button>
                </div>

                @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                @endif

                @if($errors->any())
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <ul class="mb-0">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                @endif

                <!-- View Profile Info -->
                <div id="viewProfile">
                    <div class="row">
                        <div class="col-lg-3 col-md-4">
                            <div class="text-center mb-4">
                                <img src="{{ 'https://www.gravatar.com/avatar/' . md5(strtolower(trim(auth()->user()->email))) . '?s=200&d=mp' }}" 
                                     alt="user-image" 
                                     class="rounded-circle avatar-xl img-thumbnail">
                                <div class="mt-3">
                                    <p class="text-muted mb-1">
                                        @lang('translation.Profile_picture_managed_by') <a href="https://gravatar.com" target="_blank">Gravatar</a>
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-9 col-md-8">
                            <div class="table-responsive">
                                <table class="table table-nowrap mb-0">
                                    <tbody>
                                        <tr>
                                            <th scope="row" style="width: 30%;">@lang('translation.Full_Name'):</th>
                                            <td>{{ auth()->user()->name }}</td>
                                        </tr>
                                        <tr>
                                            <th scope="row">@lang('translation.Email'):</th>
                                            <td>{{ auth()->user()->email }}</td>
                                        </tr>
                                        <tr>
                                            <th scope="row">@lang('translation.Role'):</th>
                                            <td>
                                                @if(auth()->user()->role === 'admin')
                                                    <span class="badge bg-danger">@lang('translation.Administrator')</span>
                                                @elseif(auth()->user()->role === 'support')
                                                    <span class="badge bg-success">@lang('translation.Support_Staff')</span>
                                                @else
                                                    <span class="badge bg-info">@lang('translation.User')</span>
                                                @endif
                                            </td>
                                        </tr>
                                        <tr>
                                            <th scope="row">@lang('translation.Joined_Date'):</th>
                                            <td>{{ auth()->user()->created_at->format('d M Y') }}</td>
                                        </tr>
                                        
                                        @if(in_array(auth()->user()->role, ['admin', 'support']))
                                        <tr>
                                            <th scope="row">@lang('translation.Signature'):</th>
                                            <td>
                                                @if(auth()->user()->signature)
                                                    <div class="border p-3 rounded">
                                                        {!! auth()->user()->signature !!}
                                                    </div>
                                                @else
                                                    <span class="text-muted">@lang('translation.No_signature_set')</span>
                                                @endif
                                            </td>
                                        </tr>
                                        @endif
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Edit Profile Form -->
                <div id="editProfile" style="display: none;">
                    <form action="{{ route('profile.update') }}" method="POST" id="profile-form">
                        @csrf
                        <div class="row mb-3">
                            <label for="name" class="col-sm-3 col-form-label">@lang('translation.Name')</label>
                            <div class="col-sm-9">
                                <input type="text" class="form-control" id="name" name="name"
                                    value="{{ old('name', Auth::user()->name) }}" required>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label class="col-sm-3 col-form-label">@lang('translation.Email')</label>
                            <div class="col-sm-9">
                                <p class="form-control-plaintext">{{ Auth::user()->email }}</p>
                                <small class="text-muted">@lang('translation.Email_cannot_be_changed')</small>
                            </div>
                        </div>

                        @if(in_array(auth()->user()->role, ['admin', 'support']))
                        <div class="row">
                            <div class="col-sm-3">
                                <label class="col-form-label">@lang('translation.Signature')</label>
                            </div>
                            <div class="col-sm-9">
                                <!-- Signature Container -->
                                <div class="signature-container">
                                    <div class="signature-label">
                                        <button type="button" class="btn btn-sm btn-outline-primary" id="insert-default-signature">
                                            <i class="bx bx-plus-circle me-1"></i> @lang('translation.Insert_Default_Signature')
                                        </button>
                                    </div>
                                    
                                    <!-- Editor Container -->
                                    <div class="signature-editor-container">
                                        <textarea id="signature-editor" name="signature" class="@error('signature') is-invalid @enderror">{{ old('signature', Auth::user()->signature) }}</textarea>
                                        
                                        <!-- Upload progress indicator -->
                                        <div id="upload-progress" class="progress mt-2">
                                            <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%"></div>
                                        </div>
                                        <div id="upload-status" class="mt-1 small"></div>
                                        
                                        <small class="text-muted mt-2 d-block">@lang('translation.Signature_will_be_added')</small>
                                        
                                        @error('signature')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif

                        <div class="row justify-content-end mt-4">
                            <div class="col-sm-9">
                                <div class="d-flex flex-wrap gap-2">
                                    <button type="submit" class="btn btn-primary waves-effect waves-light">
                                        <i data-feather="save" class="icon-sm me-1"></i> @lang('translation.Save_Changes')
                                    </button>
                                    <button type="button" class="btn btn-secondary waves-effect" 
                                            onclick="cancelEdit()">
                                        <i data-feather="x" class="icon-sm me-1"></i> @lang('translation.Cancel')
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-4">@lang('translation.Change_Password')</h4>

                <form action="{{ route('profile.password') }}" method="POST">
                    @csrf
                    <div class="row mb-3">
                        <label for="current_password" class="col-sm-3 col-form-label">@lang('translation.Current_Password')</label>
                        <div class="col-sm-9">
                            <input type="password" class="form-control" id="current_password" name="current_password" required>
                            @error('current_password')
                            <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="row mb-3">
                        <label for="password" class="col-sm-3 col-form-label">@lang('translation.New_Password')</label>
                        <div class="col-sm-9">
                            <input type="password" class="form-control" id="password" name="password" required>
                            @error('password')
                            <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="row mb-3">
                        <label for="password_confirmation" class="col-sm-3 col-form-label">@lang('translation.Confirm_Password')</label>
                        <div class="col-sm-9">
                            <input type="password" class="form-control" id="password_confirmation" 
                                name="password_confirmation" required>
                        </div>
                    </div>

                    <div class="row justify-content-end">
                        <div class="col-sm-9">
                            <button type="submit" class="btn btn-primary waves-effect waves-light">
                                <i data-feather="key" class="icon-sm me-1"></i> @lang('translation.Change_Password')
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Two-Factor Authentication Card -->
        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-4">@lang('translation.Two_Factor_Authentication')</h4>

                <div class="row">
                    <div class="col-lg-9">
                        <p class="card-title-desc">
                            @lang('translation.2FA_description')
                        </p>
                        
                        @if(auth()->user()->google2fa_secret)
                            <div class="alert alert-success d-flex align-items-center mb-4" role="alert">
                                <i data-feather="shield" class="me-2"></i>
                                <div>
                                    <strong>@lang('translation.Enabled'):</strong> @lang('translation.2FA_enabled_message')
                                </div>
                            </div>

                            <button type="button" class="btn btn-danger waves-effect waves-light" 
                                    data-bs-toggle="modal" data-bs-target="#disable2faModal">
                                <i data-feather="shield-off" class="icon-sm me-1"></i> @lang('translation.Disable_2FA')
                            </button>
                        @else
                            <div class="alert alert-warning d-flex align-items-center mb-4" role="alert">
                                <i data-feather="alert-triangle" class="me-2"></i>
                                <div>
                                    <strong>@lang('translation.Not_Enabled'):</strong> @lang('translation.2FA_not_enabled_message')
                                </div>
                            </div>

                            <a href="{{ route('2fa.setup') }}" class="btn btn-primary waves-effect waves-light">
                                <i data-feather="shield" class="icon-sm me-1"></i> @lang('translation.Enable_2FA')
                            </a>
                        @endif
                    </div>
                    <div class="col-lg-3 text-center">
                        <div class="p-3 border rounded">
                            <i data-feather="smartphone" style="width: 64px; height: 64px;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Disable 2FA Modal -->
<div class="modal fade" id="disable2faModal" tabindex="-1" aria-labelledby="disable2faModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="disable2faModalLabel">@lang('translation.Disable_2FA')</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('2fa.disable') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="alert alert-warning d-flex align-items-center" role="alert">
                        <i data-feather="alert-triangle" class="me-2"></i>
                        <div>
                            <strong>@lang('translation.Warning'):</strong> @lang('translation.Disable_2FA_warning')
                        </div>
                    </div>
                    <p>@lang('translation.Enter_password_to_confirm'):</p>
                    <div class="mb-3">
                        <label for="password" class="form-label">@lang('translation.Password')</label>
                        <input type="password" class="form-control" id="disable2fa_password" name="password" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">@lang('translation.Cancel')</button>
                    <button type="submit" class="btn btn-danger">@lang('translation.Disable')</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Toast Message -->
<div id="toast-message" class="toast-message"></div>
@endsection

@section('script')
<!-- Froala Editor JS -->
<script src="https://cdn.jsdelivr.net/npm/froala-editor@latest/js/froala_editor.pkgd.min.js"></script>
<!-- CodeMirror JS -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.62.0/codemirror.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.62.0/mode/xml/xml.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.62.0/mode/htmlmixed/htmlmixed.min.js"></script>
<!-- Froala Code View Plugin -->
<script src="https://cdn.jsdelivr.net/npm/froala-editor@latest/js/plugins/code_view.min.js"></script>

<script>
// Global variables
let signatureEditor = null;

// Function to toggle profile display
function editProfile() {
    document.getElementById('viewProfile').style.display = 'none';
    document.getElementById('editProfile').style.display = 'block';
}

function cancelEdit() {
    document.getElementById('viewProfile').style.display = 'block';
    document.getElementById('editProfile').style.display = 'none';
}

// Show toast message
function showToast(message, type = 'success') {
    const toast = document.getElementById('toast-message');
    toast.textContent = message;
    
    if (type === 'error') {
        toast.classList.add('error');
    } else {
        toast.classList.remove('error');
    }
    
    toast.classList.add('show');
    
    // Auto hide after 3 seconds
    setTimeout(() => {
        toast.classList.remove('show');
    }, 3000);
}

// Function to parse YouTube URL
function parseYoutubeUrl(url) {
    if (!url) return false;
    
    // Support various YouTube URL formats
    const patterns = [
        /(?:https?:\/\/)?(?:www\.)?youtube\.com\/watch\?v=([^&]+)/i,
        /(?:https?:\/\/)?(?:www\.)?youtube\.com\/embed\/([^/?]+)/i,
        /(?:https?:\/\/)?(?:www\.)?youtube\.com\/v\/([^/?]+)/i,
        /(?:https?:\/\/)?(?:www\.)?youtu\.be\/([^/?]+)/i
    ];
    
    for (const pattern of patterns) {
        const match = url.match(pattern);
        if (match && match[1]) {
            return match[1];
        }
    }
    
    return false;
}

// Function to create YouTube embed HTML
function createYoutubeEmbed(videoId) {
    return `<div class="responsive-embed-container">
        <iframe 
            width="560" 
            height="315" 
            src="https://www.youtube-nocookie.com/embed/${videoId}?rel=0&showinfo=0&modestbranding=1&playsinline=1&fs=1" 
            title="YouTube video player" 
            frameborder="0" 
            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
            allowfullscreen
            loading="lazy">
        </iframe>
    </div><p><br></p>`;
}

// Function to insert default signature
function insertDefaultSignature() {
    if (!signatureEditor) {
        console.error("Editor not initialized");
        showToast('@lang("translation.Editor_not_ready")', 'error');
        return false;
    }
    
    const userName = "{{ Auth::user()->name }}";
    const appName = "{{ config('app.name') }}";
    const isAdmin = {{ Auth::user()->role === 'admin' ? 'true' : 'false' }};
    
    const roleColor = isAdmin ? "#d63939" : "#198754";
    const roleTitle = isAdmin ? "@lang('translation.Administrator')" : "@lang('translation.Support_Staff')";
    
    const defaultSignature = `<div style="font-family: Arial, sans-serif; color: #333; line-height: 1.5;">
        <p style="margin: 0 0 10px 0;"><strong>${userName}</strong><br>
        <span style="color: ${roleColor};">${roleTitle}</span><br>
        ${appName} @lang('translation.Support_Team')</p>
        
        <p style="font-size: 12px; color: #666; margin: 0;">
            @lang('translation.Need_further_assistance')
        </p>
    </div>`;
    
    // Set the content in Froala editor
    signatureEditor.html.set(defaultSignature);
    
    showToast('@lang("translation.Default_signature_inserted")');
    return true;
}

document.addEventListener('DOMContentLoaded', function() {
    // Initialize elements
    const uploadProgress = document.getElementById('upload-progress');
    const progressBar = uploadProgress.querySelector('.progress-bar');
    const uploadStatus = document.getElementById('upload-status');
    
    // Get CSRF token
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    
    // Initialize Froala Editor for signature
    const signatureEditorElement = document.getElementById('signature-editor');
    if (signatureEditorElement) {
        signatureEditor = new FroalaEditor('#signature-editor', {
            // License key
            key: "1C%kZPBZ`JWSDBCQ@ZGD\\@\\JXDAAOZWJh,/.!==",
            
            // Basic configuration
            height: 250,
            placeholderText: '@lang("translation.Enter_your_signature_here")',
            charCounterCount: true,
            attribution: false, // Disable Froala branding with license key
            
            // CodeMirror configuration for HTML view
            codeMirror: window.CodeMirror,
            codeMirrorOptions: {
                lineNumbers: true,
                lineWrapping: true,
                mode: 'text/html',
                theme: 'monokai',
                tabSize: 2,
                indentWithTabs: false
            },
            
            // Simplified toolbar for signature
            toolbarButtons: [
                'bold', 'italic', 'underline', 'textColor', '|',
                'alignLeft', 'alignCenter', '|',
                'formatUL', '|',
                'insertImage', 'insertVideo', '|',
                'html'
            ],
            
            // Disable default image upload to use our custom handler
            imageUpload: false,
            
            // Events
            events: {
                'initialized': function() {
                    const that = this;
                    
                    // Custom image upload function
                    function uploadImage(file, editor) {
                        // Show upload progress
                        uploadProgress.style.display = 'block';
                        progressBar.style.width = '30%';
                        uploadStatus.innerHTML = '@lang("translation.Uploading_image")';
                        
                        const formData = new FormData();
                        formData.append('image', file);
                        formData.append('_token', csrfToken);
                        
                        fetch('/admin/upload-image', {
                            method: 'POST',
                            body: formData,
                            headers: {
                                'X-CSRF-TOKEN': csrfToken,
                                'Accept': 'application/json'
                            },
                            credentials: 'same-origin'
                        })
                        .then(response => response.json())
                        .then(data => {
                            progressBar.style.width = '100%';
                            
                            if (data.success && data.data && data.data.url) {
                                // Success! Insert the image
                                editor.image.insert(data.data.url, null, null, editor.image.get());
                                
                                uploadStatus.innerHTML = '<span class="text-success">@lang("translation.Image_uploaded_successfully")</span>';
                                showToast('@lang("translation.Image_uploaded_successfully")');
                                
                                // Hide progress bar after a delay
                                setTimeout(() => {
                                    uploadProgress.style.display = 'none';
                                    progressBar.style.width = '0%';
                                    uploadStatus.innerHTML = '';
                                }, 3000);
                            } else {
                                // Error from server
                                const errorMsg = data.error || '@lang("translation.Failed_to_upload_image")';
                                uploadStatus.innerHTML = `<span class="text-danger">${errorMsg}</span>`;
                                showToast(errorMsg, 'error');
                                
                                // Hide progress bar after a delay
                                setTimeout(() => {
                                    uploadProgress.style.display = 'none';
                                    progressBar.style.width = '0%';
                                }, 5000);
                            }
                        })
                        .catch(error => {
                            console.error('Image upload error:', error);
                            const errorMsg = '@lang("translation.Error_uploading_image")';
                            uploadStatus.innerHTML = `<span class="text-danger">${errorMsg}</span>`;
                            showToast(errorMsg, 'error');
                            
                            // Hide progress bar after a delay
                            setTimeout(() => {
                                uploadProgress.style.display = 'none';
                                progressBar.style.width = '0%';
                            }, 5000);
                        });
                    }
                    
                    // Override the default image insertion method
                    this.image.upload = function() {
                        const input = document.createElement('input');
                        input.setAttribute('type', 'file');
                        input.setAttribute('accept', 'image/jpeg,image/png,image/gif,image/webp');
                        input.click();
                        
                        input.onchange = function() {
                            if (input.files && input.files[0]) {
                                uploadImage(input.files[0], that);
                            }
                        };
                    };
                    
                    // Custom YouTube video insertion
                    this.registerCommand('insertVideo', {
                        title: 'Insert YouTube Video',
                        focus: true,
                        undo: true,
                        refreshAfterCallback: true,
                        callback: function () {
                            const videoUrl = prompt('@lang("translation.Enter_YouTube_URL")');
                            if (videoUrl) {
                                const videoId = parseYoutubeUrl(videoUrl);
                                if (videoId) {
                                    const embedHtml = createYoutubeEmbed(videoId);
                                    this.html.insert(embedHtml);
                                    showToast('@lang("translation.YouTube_video_embedded_successfully")');
                                } else {
                                    showToast('@lang("translation.Invalid_YouTube_URL")', 'error');
                                }
                            }
                        }
                    });
                    
                    // Enhanced paste functionality for images and YouTube links
                    this.$el.on('paste', function(e) {
                        if (e.originalEvent && e.originalEvent.clipboardData && e.originalEvent.clipboardData.items) {
                            const items = e.originalEvent.clipboardData.items;
                            
                            // Check for images first
                            for (let i = 0; i < items.length; i++) {
                                if (items[i].type.indexOf('image') !== -1) {
                                    e.preventDefault();
                                    const blob = items[i].getAsFile();
                                    if (blob) {
                                        uploadImage(blob, that);
                                    }
                                    return;
                                }
                            }
                            
                            // Check for YouTube URLs in text
                            const clipboardText = e.originalEvent.clipboardData.getData('text');
                            if (clipboardText && (clipboardText.includes('youtube.com/watch') || clipboardText.includes('youtu.be/'))) {
                                const videoId = parseYoutubeUrl(clipboardText);
                                if (videoId) {
                                    e.preventDefault();
                                    const embedHtml = createYoutubeEmbed(videoId);
                                    that.html.insert(embedHtml);
                                    showToast('@lang("translation.YouTube_video_embedded_successfully")');
                                    return;
                                }
                            }
                        }
                    });
                    
                    // Handle drag and drop for images
                    this.$el.on('drop', function(e) {
                        if (e.originalEvent.dataTransfer && e.originalEvent.dataTransfer.files) {
                            const files = e.originalEvent.dataTransfer.files;
                            
                            for (let i = 0; i < files.length; i++) {
                                if (files[i].type.indexOf('image') !== -1) {
                                    e.preventDefault();
                                    uploadImage(files[i], that);
                                    break;
                                }
                            }
                        }
                    });
                },
                
                // Handle image insertion after upload to make them responsive
                'image.inserted': function($img) {
                    // Ensure images are responsive and styled
                    $img.addClass('img-fluid');
                    $img.css('max-width', '100%');
                    $img.css('height', 'auto');
                }
            }
        });
    }
    
    // Setup default signature button
    const insertDefaultBtn = document.getElementById('insert-default-signature');
    if (insertDefaultBtn) {
        insertDefaultBtn.addEventListener('click', function(e) {
            e.preventDefault();
            insertDefaultSignature();
        });
    }
    
    // Handle form submission
    const form = document.getElementById('profile-form');
    if (form) {
        form.addEventListener('submit', function(e) {
            // Add loading state to button
            const submitBtn = this.querySelector('button[type="submit"]');
            if (submitBtn) {
                const originalContent = submitBtn.innerHTML;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> @lang("translation.Saving")';
                submitBtn.disabled = true;
                
                // Re-enable button after a timeout as fallback
                setTimeout(() => {
                    submitBtn.innerHTML = originalContent;
                    submitBtn.disabled = false;
                }, 10000);
            }
        });
    }
    
    // Mobile-specific adjustments
    const isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
    
    if (isMobile) {
        // Make toolbar scrollable on mobile
        setTimeout(() => {
            const toolbar = document.querySelector('.fr-toolbar');
            if (toolbar) {
                toolbar.style.overflowX = 'auto';
                toolbar.style.flexWrap = 'nowrap';
                toolbar.style.whiteSpace = 'nowrap';
            }
        }, 1000);
    }
    
    // Initialize Feather Icons
    if (typeof feather !== 'undefined') {
        feather.replace();
    }
});
</script>
@endsection
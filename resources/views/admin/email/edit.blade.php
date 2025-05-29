@extends('layouts.master')

@section('title') Edit Email Template @endsection

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
    /* Email template editor specific styles */
    .email-template-editor {
        /* Container styles */
    }
    
    /* Editor container styling */
    .editor-container {
        position: relative;
        margin-bottom: 20px;
        border: 1px solid #ced4da;
        border-radius: 0.25rem;
    }
    
    /* Froala editor styling */
    #froala-editor {
        min-height: 400px;
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        font-size: 14px;
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
    
    /* Variable pills with full notation - now clickable to copy */
    .email-template-editor .variable-pill {
        display: inline-block;
        background-color: #e9ecef;
        border: 1px solid #ced4da;
        border-radius: 3px;
        padding: 6px 10px;
        margin: 4px;
        font-family: SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
        font-size: 0.875em;
        cursor: pointer;
        user-select: none; /* Prevent text selection since we handle copy programmatically */
        transition: all 0.2s ease;
    }
    
    .email-template-editor .variable-pill:hover {
        background-color: #dee2e6;
        border-color: #adb5bd;
        transform: translateY(-1px);
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    
    .email-template-editor .variable-pill:active {
        transform: translateY(0);
        box-shadow: 0 1px 2px rgba(0,0,0,0.1);
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
        10% { opacity: 1; }
        90% { opacity: 1; }
        100% { opacity: 0; }
    }
    
    /* Variables section styling */
    .email-template-editor .variables-section {
        background-color: #f8f9fa;
        border: 1px solid #ced4da;
        border-radius: 5px;
        padding: 15px;
        margin-bottom: 20px;
    }
    
    .email-template-editor .variables-heading {
        display: flex;
        align-items: center;
        margin-bottom: 10px;
    }
    
    .email-template-editor .variables-heading i {
        margin-right: 8px;
        color: #4263eb;
    }
    
    .email-template-editor .variables-note {
        font-size: 0.9em;
        color: #6c757d;
        margin-bottom: 15px;
    }
    
    /* Variables container */
    .email-template-editor .variables-container {
        display: flex;
        flex-wrap: wrap;
        gap: 5px;
    }
    
    /* Sticky footer for save buttons */
    .email-template-editor .sticky-footer {
        position: sticky;
        bottom: 0;
        background-color: #fff;
        padding: 15px 0;
        border-top: 1px solid #e2e8f0;
        margin-top: 20px;
        z-index: 100;
        box-shadow: 0 -2px 10px rgba(0,0,0,0.05);
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
        #froala-editor {
            min-height: 300px;
            font-size: 13px;
        }
        
        /* Fix variable container overflow */
        .email-template-editor .variables-container {
            max-height: 150px;
            overflow-y: auto;
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
        @slot('li_1') Email Templates @endslot
        @slot('title') Edit Template @endslot
    @endcomponent

    <div class="row">
        <div class="col-lg-12">
            <div class="card email-template-editor">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h4 class="card-title mb-0">Edit Email Template</h4>
                        <a href="{{ route('admin.email.index') }}" class="btn btn-secondary waves-effect">
                            <i data-feather="arrow-left" class="icon-sm me-1"></i> Back to List
                        </a>
                    </div>

                    <form action="{{ route('admin.email.update', $template->id) }}" method="POST" id="template-form">
                        @csrf
                        @method('PUT')
                        
                        <!-- Subject -->
                        <div class="mb-3">
                            <label class="form-label">Subject</label>
                            <input type="text" 
                                name="subject" 
                                class="form-control @error('subject') is-invalid @enderror"
                                value="{{ old('subject', $template->subject) }}">
                            @error('subject')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <!-- Available Variables -->
                        @if(count($templateVariables) > 0)
                        <div class="variables-section">
                            <div class="variables-heading">
                                <i class="bx bx-code-curly"></i>
                                <h5 class="mb-0">Available Variables</h5>
                            </div>
                            <p class="variables-note">
                                <i class="bx bx-info-circle text-info me-1"></i>
                                Click on any variable below to automatically copy it to clipboard, then paste into the editor:
                            </p>
                            <div class="variables-container">
                                @foreach($templateVariables as $variable)
                                    <span class="variable-pill" title="Click to copy">
                                        <i class="bx bx-copy" style="font-size: 0.8em; margin-right: 4px;"></i>@php echo '{{'.$variable.'}}'; @endphp
                                    </span>
                                @endforeach
                            </div>
                        </div>
                        @endif

                        <!-- HTML Content -->
                        <div class="mb-3">
                            <label class="form-label">Content</label>
                            
                            <!-- Editor Container -->
                            <div class="editor-container">
                                <textarea id="froala-editor" name="html_template">{{ old('html_template', $template->html_template) }}</textarea>
                            </div>
                            
                            <!-- Upload progress indicator -->
                            <div id="upload-progress" class="progress mt-2">
                                <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%"></div>
                            </div>
                            <div id="upload-status" class="mt-1 small"></div>
                            
                            @error('html_template')
                                <div class="text-danger mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Sticky Save Button Area -->
                        <div class="sticky-footer">
                            <div class="text-end">
                                <a href="{{ route('admin.email.index') }}" class="btn btn-secondary me-2">Cancel</a>
                                <button type="submit" class="btn btn-primary">
                                    <i data-feather="save" class="icon-sm me-1"></i> Save Changes
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
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
document.addEventListener('DOMContentLoaded', function() {
    // Initialize elements
    const uploadProgress = document.getElementById('upload-progress');
    const progressBar = uploadProgress.querySelector('.progress-bar');
    const uploadStatus = document.getElementById('upload-status');
    
    // Get CSRF token
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    
    // Make variable pills auto-copy on click
    document.querySelectorAll('.variable-pill').forEach(function(pill) {
        pill.addEventListener('click', function() {
            const text = this.textContent.trim();
            
            // Try modern clipboard API first
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(text).then(function() {
                    showToast(`Variable "${text}" copied to clipboard!`);
                    
                    // Visual feedback
                    pill.style.backgroundColor = '#28a745';
                    pill.style.color = 'white';
                    setTimeout(() => {
                        pill.style.backgroundColor = '#e9ecef';
                        pill.style.color = '';
                    }, 500);
                }).catch(function() {
                    // Fallback if clipboard API fails
                    fallbackCopyText(text, pill);
                });
            } else {
                // Fallback for older browsers
                fallbackCopyText(text, pill);
            }
        });
    });
    
    // Fallback copy function for older browsers
    function fallbackCopyText(text, element) {
        // Create temporary textarea
        const textarea = document.createElement('textarea');
        textarea.value = text;
        textarea.setAttribute('readonly', '');
        textarea.style.position = 'absolute';
        textarea.style.left = '-9999px';
        document.body.appendChild(textarea);
        
        // Select and copy
        textarea.select();
        textarea.setSelectionRange(0, 99999); // For mobile devices
        
        try {
            const successful = document.execCommand('copy');
            if (successful) {
                showToast(`Variable "${text}" copied to clipboard!`);
                
                // Visual feedback
                element.style.backgroundColor = '#28a745';
                element.style.color = 'white';
                setTimeout(() => {
                    element.style.backgroundColor = '#e9ecef';
                    element.style.color = '';
                }, 500);
            } else {
                showToast('Failed to copy. Please select and copy manually.', 'error');
            }
        } catch (err) {
            showToast('Copy not supported. Please select and copy manually.', 'error');
        }
        
        // Remove temporary textarea
        document.body.removeChild(textarea);
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
    
    // Initialize Froala Editor
    const editor = new FroalaEditor('#froala-editor', {
        // License key
        key: "1C%kZPBZ`JWSDBCQ@ZGD\\@\\JXDAAOZWJh,/.!==",
        
        // Basic configuration
        height: 400,
        placeholderText: 'Enter your email template content here...',
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
        
        // Simplified toolbar with only essential formatting options
        toolbarButtons: [
            'bold', 'italic', 'underline', 'textColor', '|',
            'alignLeft', 'alignCenter', '|',
            'formatUL', '|',
            'insertImage', 'insertVideo', '|',
            'html'
        ],
        
        // Disable default image upload to use our custom handler
        imageUpload: false,
        
        // Enable paste functionality
        pastePlain: false,
        
        // Events
        events: {
            'initialized': function() {
                const that = this;
                
                // Custom image upload function
                function uploadImage(file, editor) {
                    // Show upload progress
                    uploadProgress.style.display = 'block';
                    progressBar.style.width = '30%';
                    uploadStatus.innerHTML = 'Uploading image...';
                    
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
                            
                            uploadStatus.innerHTML = '<span class="text-success"><i class="bx bx-check-circle me-1"></i> Image uploaded successfully!</span>';
                            showToast('Image uploaded successfully!');
                            
                            // Hide progress bar after a delay
                            setTimeout(() => {
                                uploadProgress.style.display = 'none';
                                progressBar.style.width = '0%';
                                uploadStatus.innerHTML = '';
                            }, 3000);
                        } else {
                            // Error from server
                            const errorMsg = data.error || 'Failed to upload image';
                            uploadStatus.innerHTML = `<span class="text-danger"><i class="bx bx-error-circle me-1"></i> ${errorMsg}</span>`;
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
                        const errorMsg = 'Error uploading image. Please try again.';
                        uploadStatus.innerHTML = `<span class="text-danger"><i class="bx bx-error-circle me-1"></i> ${errorMsg}</span>`;
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
                        const videoUrl = prompt('Enter YouTube URL (e.g., https://www.youtube.com/watch?v=VIDEO_ID):');
                        if (videoUrl) {
                            const videoId = parseYoutubeUrl(videoUrl);
                            if (videoId) {
                                const embedHtml = createYoutubeEmbed(videoId);
                                this.html.insert(embedHtml);
                                showToast('YouTube video embedded successfully!');
                            } else {
                                showToast('Invalid YouTube URL. Please check the URL and try again.', 'error');
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
                                showToast('YouTube video embedded successfully!');
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
            },
            
            // Content changed event
            'contentChanged': function() {
                // Auto-save could be implemented here if needed
            }
        }
    });
    
    // Handle form submission
    const form = document.getElementById('template-form');
    if (form) {
        form.addEventListener('submit', function(e) {
            // Add loading state to button
            const submitBtn = this.querySelector('button[type="submit"]');
            if (submitBtn) {
                const originalContent = submitBtn.innerHTML;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Saving...';
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
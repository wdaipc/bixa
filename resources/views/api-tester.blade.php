
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>API Tester</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        pre {
            background-color: #f5f5f5;
            padding: 15px;
            border-radius: 5px;
            max-height: 400px;
            overflow: auto;
        }
        .json-key { color: #8f0000; }
        .json-string { color: #008000; }
        .json-number { color: #0000ff; }
        .json-boolean { color: #b22222; }
        .json-null { color: #808080; }
    </style>
</head>
<body>
    <div class="container my-4">
        <h1>API Tester - Debug</h1>
        
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5>Test API Endpoint</h5>
                    </div>
                    <div class="card-body">
                        <form id="apiTestForm">
                            <div class="mb-3">
                                <label for="endpoint" class="form-label">Endpoint:</label>
                                <input type="text" class="form-control" id="endpoint" value="/api/ad-slots">
                            </div>
                            <div class="mb-3">
                                <label for="method" class="form-label">Method:</label>
                                <select class="form-select" id="method">
                                    <option value="GET" selected>GET</option>
                                    <option value="POST">POST</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="headers" class="form-label">Headers (JSON):</label>
                                <textarea class="form-control" id="headers" rows="3">{"Accept": "application/json", "X-Requested-With": "XMLHttpRequest"}</textarea>
                            </div>
                            <button type="submit" class="btn btn-primary">Send Request</button>
                        </form>
                    </div>
                </div>
                
                <div class="card mt-3">
                    <div class="card-header">
                        <h5>Quick Links</h5>
                    </div>
                    <div class="card-body">
                        <div class="list-group">
                            <button class="list-group-item list-group-item-action" data-endpoint="/api/ad-slots">Get Ad Slots</button>
                            <button class="list-group-item list-group-item-action" data-endpoint="/api/advertisements">Get Advertisements</button>
                            <button class="list-group-item list-group-item-action" data-endpoint="/api/ad-slots?debug=1">Get Ad Slots (Debug)</button>
                            <button class="list-group-item list-group-item-action" data-endpoint="/api/test">Test API Connection</button>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5>Response</h5>
                        <div>
                            <span id="statusBadge" class="badge bg-secondary">Waiting...</span>
                            <button id="copyBtn" class="btn btn-sm btn-outline-secondary ms-2" disabled>Copy</button>
                        </div>
                    </div>
                    <div class="card-body">
                        <h6>Status: <span id="statusCode">-</span></h6>
                        <div class="mb-3">
                            <h6>Headers:</h6>
                            <pre id="responseHeaders">-</pre>
                        </div>
                        <div>
                            <h6>Body:</h6>
                            <pre id="responseBody">-</pre>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h5>JavaScript Console</h5>
            </div>
            <div class="card-body">
                <pre id="consoleOutput" class="mb-0">// Console output will appear here</pre>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const apiTestForm = document.getElementById('apiTestForm');
            const endpointInput = document.getElementById('endpoint');
            const methodSelect = document.getElementById('method');
            const headersTextarea = document.getElementById('headers');
            const statusBadge = document.getElementById('statusBadge');
            const statusCode = document.getElementById('statusCode');
            const responseHeaders = document.getElementById('responseHeaders');
            const responseBody = document.getElementById('responseBody');
            const copyBtn = document.getElementById('copyBtn');
            const consoleOutput = document.getElementById('consoleOutput');
            
            // Intercept console methods
            const originalConsoleLog = console.log;
            const originalConsoleError = console.error;
            const originalConsoleWarn = console.warn;
            
            function appendToConsole(message, className = '') {
                const date = new Date();
                const timestamp = date.toLocaleTimeString() + '.' + date.getMilliseconds();
                const div = document.createElement('div');
                div.className = className;
                div.textContent = `[${timestamp}] ${typeof message === 'object' ? JSON.stringify(message) : message}`;
                consoleOutput.appendChild(div);
                consoleOutput.scrollTop = consoleOutput.scrollHeight;
            }
            
            console.log = function(...args) {
                originalConsoleLog.apply(console, args);
                args.forEach(arg => appendToConsole(arg));
            };
            
            console.error = function(...args) {
                originalConsoleError.apply(console, args);
                args.forEach(arg => appendToConsole(arg, 'text-danger'));
            };
            
            console.warn = function(...args) {
                originalConsoleWarn.apply(console, args);
                args.forEach(arg => appendToConsole(arg, 'text-warning'));
            };
            
            // Format JSON with syntax highlighting
            function formatJSON(json) {
                if (typeof json !== 'string') {
                    json = JSON.stringify(json, null, 2);
                }
                
                return json.replace(/("(\\u[a-zA-Z0-9]{4}|\\[^u]|[^\\"])*"(\s*:)?|\b(true|false|null)\b|-?\d+(?:\.\d*)?(?:[eE][+\-]?\d+)?)/g, function (match) {
                    let cls = 'json-number';
                    if (/^"/.test(match)) {
                        if (/:$/.test(match)) {
                            cls = 'json-key';
                        } else {
                            cls = 'json-string';
                        }
                    } else if (/true|false/.test(match)) {
                        cls = 'json-boolean';
                    } else if (/null/.test(match)) {
                        cls = 'json-null';
                    }
                    return '<span class="' + cls + '">' + match + '</span>';
                });
            }
            
            // Send API request
            function sendApiRequest(endpoint, method) {
                console.log(`Sending ${method} request to ${endpoint}`);
                statusBadge.textContent = 'Loading...';
                statusBadge.className = 'badge bg-secondary';
                copyBtn.disabled = true;
                
                let headers;
                try {
                    headers = JSON.parse(headersTextarea.value);
                } catch (e) {
                    console.error('Invalid headers JSON:', e);
                    headers = { 'Accept': 'application/json' };
                }
                
                fetch('{{ route("api.test-internal") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        endpoint,
                        method,
                        headers
                    })
                })
                .then(response => response.json())
                .then(data => {
                    console.log('Response received:', data.status);
                    
                    statusCode.textContent = data.status;
                    
                    if (data.status >= 200 && data.status < 300) {
                        statusBadge.textContent = 'Success';
                        statusBadge.className = 'badge bg-success';
                    } else {
                        statusBadge.textContent = 'Error';
                        statusBadge.className = 'badge bg-danger';
                    }
                    
                    // Display headers
                    responseHeaders.innerHTML = formatJSON(data.headers);
                    
                    // Display body
                    if (data.formatted) {
                        responseBody.innerHTML = formatJSON(data.formatted);
                    } else {
                        responseBody.textContent = data.body;
                    }
                    
                    copyBtn.disabled = false;
                })
                .catch(error => {
                    console.error('Error:', error);
                    statusBadge.textContent = 'Error';
                    statusBadge.className = 'badge bg-danger';
                    statusCode.textContent = 'Request failed';
                    responseBody.textContent = error.toString();
                });
            }
            
            // Form submit handler
            apiTestForm.addEventListener('submit', function(e) {
                e.preventDefault();
                sendApiRequest(endpointInput.value, methodSelect.value);
            });
            
            // Quick links
            document.querySelectorAll('[data-endpoint]').forEach(button => {
                button.addEventListener('click', function() {
                    endpointInput.value = this.getAttribute('data-endpoint');
                    sendApiRequest(endpointInput.value, 'GET');
                });
            });
            
            // Copy response
            copyBtn.addEventListener('click', function() {
                const content = responseBody.textContent;
                navigator.clipboard.writeText(content)
                    .then(() => {
                        copyBtn.textContent = 'Copied!';
                        setTimeout(() => {
                            copyBtn.textContent = 'Copy';
                        }, 2000);
                    })
                    .catch(err => {
                        console.error('Failed to copy:', err);
                    });
            });
            
            // Initial log
            console.log('API Tester initialized');
        });
    </script>
</body>
</html>
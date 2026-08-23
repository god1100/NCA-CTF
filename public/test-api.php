<?php
$baseUrl = $GLOBALS['baseUrl'] ?? '/NCA-CTF/public';
$fullBaseUrl = 'http://localhost' . $baseUrl;
?>
<!DOCTYPE html>
<html>
<head>
    <title>API Test</title>
    <style>
        body { background: #252525; color: #f0f0f0; font-family: monospace; padding: 2rem; }
        pre { background: #1a1a1a; padding: 1rem; border-radius: 6px; overflow-x: auto; max-height: 400px; overflow-y: auto; }
        .success { color: #4CAF50; }
        .error { color: #f44336; }
        input, button { padding: 0.5rem; margin: 0.5rem 0; background: #333; color: #fff; border: 1px solid #555; }
        button { background: #472525; cursor: pointer; }
        .section { margin: 2rem 0; border-top: 1px solid #333; padding-top: 1rem; }
        .info { color: #ff0; }
    </style>
</head>
<body>
    <h1>🔧 API Debug Tool</h1>
    <p>Base URL: <strong><?= $baseUrl ?></strong></p>
    <p>Full URL: <strong><?= $fullBaseUrl ?></strong></p>

    <div class="section">
        <h2>1. Test Registration</h2>
        <input type="text" id="regUsername" placeholder="Username" value="testuser_<?= time() ?>">
        <input type="email" id="regEmail" placeholder="Email" value="test_<?= time() ?>@example.com">
        <input type="text" id="regFullname" placeholder="Full Name" value="Test User">
        <input type="password" id="regPassword" placeholder="Password" value="TestPass123">
        <button onclick="testRegister()">Test Register</button>
        <pre id="regResult">Click the button to test...</pre>
    </div>

    <div class="section">
        <h2>2. Test Login</h2>
        <input type="text" id="loginIdentifier" placeholder="Username or Email" value="testuser">
        <input type="password" id="loginPassword" placeholder="Password" value="TestPass123">
        <button onclick="testLogin()">Test Login</button>
        <pre id="loginResult">Click the button to test...</pre>
    </div>

    <div class="section">
        <h2>3. Test Session (GET /api/v1/auth/me)</h2>
        <button onclick="testMe()">Test Me</button>
        <pre id="meResult">Click the button to test...</pre>
    </div>

<script>
    // Use absolute URL with full path
    const FULL_BASE_URL = '<?= $fullBaseUrl ?>';

    function url(path) {
        // Route through index.php
        return FULL_BASE_URL + '/index.php' + path;
    }

    async function testRegister() {
        const result = document.getElementById('regResult');
        const data = {
            username: document.getElementById('regUsername').value,
            email: document.getElementById('regEmail').value,
            full_name: document.getElementById('regFullname').value,
            password: document.getElementById('regPassword').value
        };

        const requestUrl = url('/api/v1/auth/register');
        result.textContent = 'Requesting: ' + requestUrl + '\n\nWaiting for response...';
        result.style.color = '#ff0';

        try {
            const response = await fetch(requestUrl, {
                method: 'POST',
                credentials: 'include',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });

            const text = await response.text();
            let responseData;
            try {
                responseData = JSON.parse(text);
            } catch (e) {
                responseData = { raw: text.substring(0, 500) + '...' };
            }

            result.textContent = 'URL: ' + requestUrl + '\n';
            result.textContent += 'Status: ' + response.status + ' ' + response.statusText + '\n\n';
            result.textContent += 'Response:\n' + (typeof responseData === 'object' ? JSON.stringify(responseData, null, 2) : responseData);

            if (response.ok) {
                result.style.color = '#4CAF50';
            } else {
                result.style.color = '#f44336';
            }
        } catch (e) {
            result.textContent = 'ERROR: ' + e.message;
            result.style.color = '#f44336';
        }
    }

    async function testLogin() {
        const result = document.getElementById('loginResult');
        const data = {
            identifier: document.getElementById('loginIdentifier').value,
            password: document.getElementById('loginPassword').value
        };

        const requestUrl = url('/api/v1/auth/login');
        result.textContent = 'Requesting: ' + requestUrl + '\n\nWaiting for response...';
        result.style.color = '#ff0';

        try {
            const response = await fetch(requestUrl, {
                method: 'POST',
                credentials: 'include',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });

            const text = await response.text();
            let responseData;
            try {
                responseData = JSON.parse(text);
            } catch (e) {
                responseData = { raw: text.substring(0, 500) + '...' };
            }

            result.textContent = 'URL: ' + requestUrl + '\n';
            result.textContent += 'Status: ' + response.status + ' ' + response.statusText + '\n\n';
            result.textContent += 'Response:\n' + (typeof responseData === 'object' ? JSON.stringify(responseData, null, 2) : responseData);

            if (response.ok) {
                result.style.color = '#4CAF50';
            } else {
                result.style.color = '#f44336';
            }
        } catch (e) {
            result.textContent = 'ERROR: ' + e.message;
            result.style.color = '#f44336';
        }
    }

    async function testMe() {
        const result = document.getElementById('meResult');
        const requestUrl = url('/api/v1/auth/me');

        result.textContent = 'Requesting: ' + requestUrl + '\n\nWaiting for response...';
        result.style.color = '#ff0';

        try {
            const response = await fetch(requestUrl, {
                method: 'GET',
                credentials: 'include',
                headers: { 'Accept': 'application/json' }
            });

            const text = await response.text();
            let responseData;
            try {
                responseData = JSON.parse(text);
            } catch (e) {
                responseData = { raw: text.substring(0, 500) + '...' };
            }

            result.textContent = 'URL: ' + requestUrl + '\n';
            result.textContent += 'Status: ' + response.status + ' ' + response.statusText + '\n\n';
            result.textContent += 'Response:\n' + (typeof responseData === 'object' ? JSON.stringify(responseData, null, 2) : responseData);

            if (response.ok) {
                result.style.color = '#4CAF50';
            } else {
                result.style.color = '#f44336';
            }
        } catch (e) {
            result.textContent = 'ERROR: ' + e.message;
            result.style.color = '#f44336';
        }
    }
</script>
</body>
</html>
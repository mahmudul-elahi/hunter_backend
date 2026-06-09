<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>FCM Browser Test</title>
    <style>
        body {
            font-family: system-ui, sans-serif;
            max-width: 640px;
            margin: 40px auto;
            padding: 0 16px;
            color: #222;
        }

        input,
        button {
            font-size: 14px;
            padding: 8px;
        }

        input {
            width: 100%;
            box-sizing: border-box;
            margin-bottom: 8px;
        }

        button {
            cursor: pointer;
        }

        pre {
            background: #111;
            color: #0f0;
            padding: 12px;
            border-radius: 6px;
            white-space: pre-wrap;
            word-break: break-all;
            min-height: 90px;
        }
    </style>
</head>

<body>
    <h1>FCM Browser Test</h1>
    <ol>
        <li>Enter a <b>premium</b> user's email &amp; password below.</li>
        <li>Click <b>Enable &amp; register</b>, then allow notifications.</li>
        <li>As an admin, create a prediction &mdash; watch the log / OS notification.</li>
    </ol>

    <input id="email" placeholder="Premium user email" value="john@example.com">
    <input id="password" type="password" placeholder="Password" value="password">
    <button id="register">Enable &amp; register token</button>
    <pre id="log"></pre>

    <script type="module">
        import {
            initializeApp
        } from 'https://www.gstatic.com/firebasejs/10.12.0/firebase-app.js';
        import {
            getMessaging,
            getToken,
            onMessage
        } from 'https://www.gstatic.com/firebasejs/10.12.0/firebase-messaging.js';

        const firebaseConfig = {
            apiKey: 'AIzaSyDjt0J8zxc6CCzNUHjnXurTqX8lYOtIufc',
            authDomain: 'hunter-6fc87.firebaseapp.com',
            projectId: 'hunter-6fc87',
            messagingSenderId: '769800705478',
            appId: '1:769800705478:web:fffd42f254f45303d54ec7',
        };
        const VAPID_KEY = 'BKLqzh7XoTtocQfP4VxHaZwXxNrsg98rZiluD2REb9oF-Gd4F8dpApHSmhr3OdflhPgTTJ6fo8qQ3vJ8IuFPo2g';

        const out = document.getElementById('log');
        const log = (message) => {
            out.textContent += message + '\n';
        };

        const messaging = getMessaging(initializeApp(firebaseConfig));

        // Foreground messages (fires when this tab is focused).
        onMessage(messaging, (payload) => {
            log('Foreground message: ' + JSON.stringify(payload.notification));
            if (Notification.permission === 'granted' && payload.notification) {
                new Notification(payload.notification.title, {
                    body: payload.notification.body
                });
            }
        });

        document.getElementById('register').addEventListener('click', async () => {
            try {
                const email = document.getElementById('email').value.trim();
                const password = document.getElementById('password').value;
                if (!email || !password) {
                    return log('Enter email and password first.');
                }

                const permission = await Notification.requestPermission();
                log('Permission: ' + permission);
                if (permission !== 'granted') {
                    return;
                }

                // Register the service worker and wait until it is active before
                // requesting a token, otherwise PushManager.subscribe() fails.
                const swRegistration = await navigator.serviceWorker.register('/firebase-messaging-sw.js');
                await navigator.serviceWorker.ready;
                log('Service worker ready.');

                const token = await getToken(messaging, {
                    vapidKey: VAPID_KEY,
                    serviceWorkerRegistration: swRegistration,
                });
                log('FCM token: ' + token);

                // Logging in with the token registers it for this user.
                const res = await fetch('/api/auth/login', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ email, password, fcm_token: token }),
                });
                log('Login + register: ' + res.status + ' ' + await res.text());
            } catch (error) {
                log('Error: ' + error.message);
            }
        });
    </script>
</body>

</html>

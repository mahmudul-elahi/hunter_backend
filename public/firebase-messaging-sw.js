/* Firebase Cloud Messaging service worker.
 * Shows notifications when the page/tab is in the background or closed.
 * Must be served from the site root: /firebase-messaging-sw.js
 */
importScripts('https://www.gstatic.com/firebasejs/10.12.0/firebase-app-compat.js');
importScripts('https://www.gstatic.com/firebasejs/10.12.0/firebase-messaging-compat.js');

firebase.initializeApp({
    apiKey: 'AIzaSyDjt0J8zxc6CCzNUHjnXurTqX8lYOtIufc',
    authDomain: 'hunter-6fc87.firebaseapp.com',
    projectId: 'hunter-6fc87',
    messagingSenderId: '769800705478',
    appId: '1:769800705478:web:fffd42f254f45303d54ec7',
});

firebase.messaging().onBackgroundMessage((payload) => {
    const notification = payload.notification || {};
    self.registration.showNotification(notification.title || 'Notification', {
        body: notification.body || '',
        icon: '/favicon.ico',
    });
});

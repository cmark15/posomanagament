import './bootstrap';
import '../css/app.css';
import 'bootstrap/dist/js/bootstrap.bundle.min.js';
// resources/js/app.js

if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/serviceworker.js').then(registration => {
            console.log('ServiceWorker registered:', registration);
        }).catch(error => {
            console.log('ServiceWorker failed:', error);
        });
    });
}
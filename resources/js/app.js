import Echo from 'laravel-echo';
import io from 'socket.io-client';
import Alpine from 'alpinejs';

window.io = io;

window.Echo = new Echo({
    broadcaster: 'socket.io',
    host: window.location.hostname + ':6001',
    transports: ['websocket'], // Force WebSockets
    reconnection: true,
    rejectUnauthorized: false
});

window.Echo.join('online')
    .here((users) => {
        console.log('Users online:', users);
    })
    .joining((user) => {
        console.log(user.name + ' joined.');
    })
    .leaving((user) => {
        console.log(user.name + ' left.');
    })
    .error((error) => {
        console.error('WebSocket error:', error);
    });

window.Alpine = Alpine;
Alpine.start();

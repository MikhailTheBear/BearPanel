import './bootstrap';
document.addEventListener('livewire:init', () => {
    Livewire.on('console:error', ({ message, trace }) => {
        console.error('Server Error:', message);
        console.error('Stack trace:', trace);
    });
    // Слушаем событие статуса Reverb
    Livewire.on('reverb:status', ({ status, message }) => {
        if (status === 'connected') {
            console.log('✅ Reverb:', message);
            console.log('🟢 Reverb подключён на', new Date().toLocaleTimeString());
        } else if (status === 'disconnected') {
            console.warn('⚠️ Reverb:', message);
        } else if (status === 'error') {
            console.error('❌ Reverb:', message);
        }
    });
});
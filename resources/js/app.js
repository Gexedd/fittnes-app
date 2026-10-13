//

/**
 * Feedback de envío para formularios clásicos (los que hacen un POST de página
 * completa y luego redirigen, como el de suscripción a la pasarela).
 *
 * Se activa solo en los botones que llevan data-submitting-label, para no pisar
 * el texto de los formularios de Livewire.
 */
document.addEventListener('submit', (event) => {
    const button = event.target.querySelector('button[data-submitting-label]');

    if (! button || button.disabled) {
        return;
    }

    button.disabled = true;
    button.textContent = button.dataset.submittingLabel;
});

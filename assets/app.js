import './styles/app.css';

// Stimulus est optionnel : s’il échoue en prod (assets non compilés), l’UI critique reste dans /js/umu-ui.js
import('./stimulus_bootstrap.js').catch(() => {
    /* ignore */
});

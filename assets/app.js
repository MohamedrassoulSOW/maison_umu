// CSS chargé via <link href="{{ asset('css/umu.css') }}"> dans base.html.twig
// (hors Asset Mapper — fiable en prod Hostinger même sans asset-map:compile)

// Stimulus optionnel : s’il échoue, l’UI critique reste dans /js/umu-ui.js
import('./stimulus_bootstrap.js').catch(() => {
    /* ignore */
});

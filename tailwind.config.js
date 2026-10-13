import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    // darkMode 'class': el <html class="dark"> de los layouts activa los estilos
    // oscuros que genera Breeze. Nuestro diseño es dark-first.
    darkMode: 'class',

    theme: {
        extend: {
            fontFamily: {
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
                display: ['"Barlow Condensed"', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                // ---- Paleta A: "Neon Gym" ----
                ink: {
                    DEFAULT: '#0B0B0F', // fondo de la app
                    soft: '#15151C',    // superficies / tarjetas
                    line: '#24242E',    // bordes
                },
                ember: { // naranja primario (energía)
                    50: '#FFF3EC',
                    100: '#FFE1D1',
                    300: '#FFB08A',
                    400: '#FF8A4C',
                    500: '#FF5A1F', // primario
                    600: '#E64A12',
                    700: '#BF3A0B', // primario sobre fondo claro (WCAG AA)
                },
                volt: { // verde neón (acento + estados de éxito)
                    DEFAULT: '#B6FF00',
                    dark: '#8FCC00',
                },
                electric: '#FFD400', // amarillo eléctrico (destacados)
            },
            boxShadow: {
                ember: '0 10px 30px -10px rgba(255, 90, 31, 0.55)',
                neon: '0 0 24px rgba(182, 255, 0, 0.30)',
            },
            backgroundImage: {
                'grid-dark':
                    'linear-gradient(to right, rgba(255,255,255,.04) 1px, transparent 1px), ' +
                    'linear-gradient(to bottom, rgba(255,255,255,.04) 1px, transparent 1px)',
            },
        },
    },

    plugins: [forms],
};

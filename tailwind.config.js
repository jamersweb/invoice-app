import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
    ],

    darkMode: 'class',

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                dark: {
                    primary: '#1E1E1E',
                    secondary: '#2D2D2D',
                    tertiary: '#3A3A3A',
                    card: '#252525',
                    border: '#3A3A3A',
                    text: {
                        primary: '#FFFFFF',
                        secondary: '#B0B0B0',
                        muted: '#808080',
                    },
                },
                purple: {
                    accent: '#9333EA',
                    hover: '#A855F7',
                    light: '#C084FC',
                },
            },
            borderRadius: {
                'card': '12px',
            },
        },
    },

    plugins: [forms],
};

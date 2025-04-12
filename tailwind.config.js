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

    theme: {
        extend: {
            fontFamily: {
                sans: ['"Cairo"', ...defaultTheme.fontFamily.sans],
                heading: ['"Cairo"', ...defaultTheme.fontFamily.sans],
                arabic: ['"Cairo"', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                primary: {
                    50: '#e6f1fe',
                    100: '#cce3fd',
                    200: '#99c7fb',
                    300: '#66abf9',
                    400: '#338ff7',
                    500: '#0073f5',
                    600: '#005cc4',
                    700: '#004593',
                    800: '#002e62',
                    900: '#001731',
                }
            }
        },
    },

    plugins: [forms],
};

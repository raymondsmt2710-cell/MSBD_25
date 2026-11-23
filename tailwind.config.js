import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.js',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                sekolah: {
                    teal: '#09656F',
                    'teal-dark': '#07535C',
                    'teal-deep': '#043D44',
                    amber: '#C97A14',
                    'amber-dark': '#7A4A0C',
                    ink: '#1E3335',
                    blue: '#1B5E8E',
                },
            },
            boxShadow: {
                lembut: '0 18px 45px -18px rgba(4, 61, 68, 0.28)',
            },
        },
    },

    plugins: [forms],
};

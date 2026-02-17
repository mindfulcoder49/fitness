import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';
import typography from '@tailwindcss/typography';

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
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                theme: {
                    page: 'rgb(var(--color-bg-page) / <alpha-value>)',
                    card: 'rgb(var(--color-bg-card) / <alpha-value>)',
                    elevated: 'rgb(var(--color-bg-elevated) / <alpha-value>)',
                    input: 'rgb(var(--color-bg-input) / <alpha-value>)',
                    nav: 'rgb(var(--color-bg-nav) / <alpha-value>)',
                    header: 'rgb(var(--color-bg-header) / <alpha-value>)',
                    'text-primary': 'rgb(var(--color-text-primary) / <alpha-value>)',
                    'text-secondary': 'rgb(var(--color-text-secondary) / <alpha-value>)',
                    'text-muted': 'rgb(var(--color-text-muted) / <alpha-value>)',
                    'text-faint': 'rgb(var(--color-text-faint) / <alpha-value>)',
                    border: 'rgb(var(--color-border) / <alpha-value>)',
                    'border-subtle': 'rgb(var(--color-border-subtle) / <alpha-value>)',
                    accent: 'rgb(var(--color-accent) / <alpha-value>)',
                    'accent-hover': 'rgb(var(--color-accent-hover) / <alpha-value>)',
                    'accent-text': 'rgb(var(--color-accent-text) / <alpha-value>)',
                    'accent-ring': 'rgb(var(--color-accent-ring) / <alpha-value>)',
                    'btn-primary': 'rgb(var(--color-btn-primary-bg) / <alpha-value>)',
                    'btn-primary-text': 'rgb(var(--color-btn-primary-text) / <alpha-value>)',
                    'btn-primary-hover': 'rgb(var(--color-btn-primary-hover) / <alpha-value>)',
                    'btn-secondary': 'rgb(var(--color-btn-secondary-bg) / <alpha-value>)',
                    'btn-secondary-text': 'rgb(var(--color-btn-secondary-text) / <alpha-value>)',
                    'btn-secondary-hover': 'rgb(var(--color-btn-secondary-hover) / <alpha-value>)',
                    danger: 'rgb(var(--color-danger) / <alpha-value>)',
                    'danger-hover': 'rgb(var(--color-danger-hover) / <alpha-value>)',
                    success: 'rgb(var(--color-success) / <alpha-value>)',
                    warning: 'rgb(var(--color-warning) / <alpha-value>)',
                    link: 'rgb(var(--color-link) / <alpha-value>)',
                    'link-hover': 'rgb(var(--color-link-hover) / <alpha-value>)',
                },
            },
        },
    },

    plugins: [forms, typography],
};

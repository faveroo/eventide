import { createInertiaApp } from '@inertiajs/react';

const appName = 'Eventide';

if (typeof window !== 'undefined') {
    const theme =
        localStorage.getItem('eventide-theme') ??
        (matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
    document.documentElement.dataset.theme = theme;
}

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    progress: {
        color: '#4B5563',
    },
});

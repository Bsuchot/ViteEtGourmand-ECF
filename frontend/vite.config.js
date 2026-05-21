import { defineConfig } from 'vite';

export default defineConfig({
    server: {
        port: 5500,
        headers: {
            "Content-Security-Policy": "default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline'; font-src 'self' https://fonts.googleapis.com https://fonts.gstatic.com; img-src 'self' data: blob: http://localhost:8000; connect-src 'self' http://localhost:8000 https://geo.api.gouv.fr https://api-adresse.data.gouv.fr https://api.openrouteservice.org; frame-src 'none'; object-src 'none'; base-uri 'self'; form-action 'self'; frame-ancestors 'none';"
        },
        proxy: {
            '/api': {
                target: 'http://localhost:8000',
                changeOrigin: true,
                secure: false,
            },
            '/uploads': {
                target: 'http://localhost:8000',
                changeOrigin: true,
                secure: false,
            }
        }
    }
});
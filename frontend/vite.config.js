import { defineConfig } from 'vite';

export default defineConfig({
    server: {
        port: 5500,
        headers: {
            "Content-Security-Policy": [
                "default-src 'self'",
                "script-src 'self' https://cdn.jsdelivr.net",
                "style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://fonts.googleapis.com",
                "font-src 'self' https://fonts.googleapis.com https://fonts.gstatic.com https://cdn.jsdelivr.net",
                "img-src 'self' data: blob: https://res.cloudinary.com",
                "connect-src 'self' ws://localhost:5500 https://geo.api.gouv.fr https://api-adresse.data.gouv.fr https://api.openrouteservice.org",
                "frame-src 'none'",
                "object-src 'none'",
                "base-uri 'self'",
                "form-action 'self'",
                "frame-ancestors 'none'"
            ].join("; ")
        },
        proxy: {
            '/api': {
                target: 'http://backend:80',
                changeOrigin: true,
                secure: false,
            },
            '/uploads': {
                target: 'http://backend:80',
                changeOrigin: true,
                secure: false,
            }
        }
    }
});
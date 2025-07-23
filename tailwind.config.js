import preset from './vendor/filament/filament/tailwind.config.preset'

export default {
    presets: [preset],
    content: [
        './app/Filament/**/*.php',
        './resources/views/filament/**/*.blade.php',
        './vendor/filament/**/*.blade.php',
    ],
    theme: {
        extend: {
            colors: {
                primary: {
                    50: '#fdf8f3',
                    100: '#faf0e4',
                    200: '#f4ddc4',
                    300: '#ecc49a',
                    400: '#e2a36e',
                    500: '#da8a4f',
                    600: '#cc7043',
                    700: '#aa5a39',
                    800: '#794E24', // Main MR Shife brand color
                    900: '#6d4220',
                    950: '#3a2110',
                },
            },
            fontFamily: {
                'sans': ['Cairo', 'Inter', 'ui-sans-serif', 'system-ui'],
                'arabic': ['Cairo', 'ui-sans-serif', 'system-ui'],
                'english': ['Inter', 'ui-sans-serif', 'system-ui'],
            },
        },
    },
}

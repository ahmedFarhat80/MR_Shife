<script>
// Language Toggle Function
function toggleLanguage() {
    const currentLocale = document.documentElement.getAttribute('lang') || 'ar';
    const newLocale = currentLocale === 'ar' ? 'en' : 'ar';

    fetch('/language-switch', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({
            locale: newLocale
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.documentElement.setAttribute('lang', newLocale);
            document.documentElement.setAttribute('dir', newLocale === 'ar' ? 'rtl' : 'ltr');
            window.location.reload();
        }
    })
    .catch(error => {
        console.error('Language switch error:', error);
        window.location.reload();
    });
}

// Theme Toggle Function
function toggleTheme() {
    const currentTheme = localStorage.getItem('theme') ||
                        (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');

    const newTheme = currentTheme === 'dark' ? 'light' : 'dark';

    // Save theme preference
    localStorage.setItem('theme', newTheme);

    // Apply theme immediately
    applyTheme(newTheme);

    // Update Filament's theme
    if (window.filament) {
        window.filament.theme = newTheme;
    }

    // Trigger Filament's theme change event
    window.dispatchEvent(new CustomEvent('theme-changed', { detail: { theme: newTheme } }));
}

// Apply Theme Function
function applyTheme(theme) {
    const html = document.documentElement;

    if (theme === 'dark') {
        html.classList.add('dark');
    } else {
        html.classList.remove('dark');
    }
}

// Initialize Theme and Language on Page Load
document.addEventListener('DOMContentLoaded', function() {
    // Initialize Language
    const currentLocale = document.documentElement.getAttribute('lang') || 'ar';
    document.documentElement.setAttribute('dir', currentLocale === 'ar' ? 'rtl' : 'ltr');

    // Initialize Theme
    const savedTheme = localStorage.getItem('theme');
    const systemTheme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
    const initialTheme = savedTheme || systemTheme;

    applyTheme(initialTheme);

    // Listen for system theme changes
    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', function(e) {
        if (!localStorage.getItem('theme')) {
            applyTheme(e.matches ? 'dark' : 'light');
        }
    });
});


</script>

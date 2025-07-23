<style>
/* Simple Arabic Font Support */
html[lang="ar"] {
    font-family: 'Cairo', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif !important;
}

html[lang="en"] {
    font-family: 'Inter', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif !important;
}

/* Language switch styling */
.language-switch-item {
    cursor: pointer !important;
}

.language-switch-item:hover {
    background-color: rgba(0, 0, 0, 0.05) !important;
}
</style>

<script>
// Set initial language
document.addEventListener('DOMContentLoaded', function() {
    const currentLocale = '{{ app()->getLocale() }}' || 'ar';
    document.documentElement.setAttribute('lang', currentLocale);
});

// Language toggle function
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
            window.location.reload();
        }
    })
    .catch(error => {
        console.error('Language switch error:', error);
        window.location.reload();
    });
}
</script>

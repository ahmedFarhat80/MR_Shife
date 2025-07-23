function toggleLanguage() {
    const currentLocale = document.documentElement.getAttribute('lang') || 'ar';
    const newLocale = currentLocale === 'ar' ? 'en' : 'ar';
    
    // Send AJAX request to change language
    fetch('/admin/language-switch', {
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
            // Update document direction and language
            document.documentElement.setAttribute('lang', newLocale);
            document.documentElement.setAttribute('dir', newLocale === 'ar' ? 'rtl' : 'ltr');
            
            // Reload page to apply changes
            window.location.reload();
        }
    })
    .catch(error => {
        console.error('Language switch error:', error);
        // Fallback: reload page
        window.location.reload();
    });
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    // Set initial direction based on current locale
    const currentLocale = document.documentElement.getAttribute('lang') || 'ar';
    document.documentElement.setAttribute('dir', currentLocale === 'ar' ? 'rtl' : 'ltr');
});

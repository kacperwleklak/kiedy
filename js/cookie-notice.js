document.addEventListener('DOMContentLoaded', () => {
    const cookieNotice = document.getElementById('cookieNotice');
    const acceptBtn = document.getElementById('acceptCookies');

    // Only show if not accepted before
    if (!localStorage.getItem('kiedy_cookies_accepted')) {
        setTimeout(() => {
            cookieNotice.classList.remove('hidden');
        }, 1000); // Show after 1 second delay
    }

    if (acceptBtn) {
        acceptBtn.addEventListener('click', () => {
            localStorage.setItem('kiedy_cookies_accepted', 'true');
            cookieNotice.style.transform = 'translateX(-50%) translateY(100px)';
            cookieNotice.style.opacity = '0';
            setTimeout(() => {
                cookieNotice.classList.add('hidden');
            }, 600);
        });
    }
});

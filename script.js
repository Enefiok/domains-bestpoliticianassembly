document.addEventListener('DOMContentLoaded', () => {
    const toggleBtn = document.getElementById('menu-toggle');
    const mobileMenu = document.getElementById('mobile-menu');
    if (!toggleBtn || !mobileMenu) return;
    function toggleMenu(e) {
        if (e) e.stopPropagation();
        const isOpen = !mobileMenu.classList.contains('hidden');
        if (isOpen) {
            mobileMenu.classList.add('hidden');
            mobileMenu.classList.remove('is-open');
            toggleBtn.setAttribute('aria-expanded', 'false');
            toggleBtn.innerHTML = '<iconify-icon icon="lucide:menu" class="text-3xl pointer-events-none"></iconify-icon>';
        } else {
            mobileMenu.classList.remove('hidden');
            mobileMenu.classList.add('is-open');
            toggleBtn.setAttribute('aria-expanded', 'true');
            toggleBtn.innerHTML = '<iconify-icon icon="lucide:x" class="text-3xl pointer-events-none"></iconify-icon>';
        }
    }
    toggleBtn.addEventListener('click', toggleMenu);
    const mobileDropdownBtns = mobileMenu.querySelectorAll('.mobile-dropdown-btn');
    mobileDropdownBtns.forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.stopPropagation();
            const parent = btn.closest('.mobile-dropdown');
            const subMenu = parent.querySelector('.mobile-dropdown-menu');
            const chevron = btn.querySelector('.chevron-icon');
            mobileMenu.querySelectorAll('.mobile-dropdown-menu').forEach(otherMenu => {
                if (otherMenu !== subMenu) {
                    otherMenu.classList.add('hidden');
                    const otherChevron = otherMenu.previousElementSibling?.querySelector('.chevron-icon');
                    if (otherChevron) otherChevron.style.transform = 'rotate(0deg)';
                }
            });
            const isSubHidden = subMenu.classList.toggle('hidden');
            if (chevron) {
                chevron.style.transform = isSubHidden ? 'rotate(0deg)' : 'rotate(180deg)';
            }
        });
    });
    mobileMenu.querySelectorAll('a').forEach(link => {
        link.addEventListener('click', () => {
            mobileMenu.classList.add('hidden');
            mobileMenu.classList.remove('is-open');
            toggleBtn.setAttribute('aria-expanded', 'false');
            toggleBtn.innerHTML = '<iconify-icon icon="lucide:menu" class="text-3xl pointer-events-none"></iconify-icon>';
        });
    });
    document.addEventListener('click', (e) => {
        if (!mobileMenu.classList.contains('hidden') && !mobileMenu.contains(e.target) && !toggleBtn.contains(e.target)) {
            mobileMenu.classList.add('hidden');
            mobileMenu.classList.remove('is-open');
            toggleBtn.setAttribute('aria-expanded', 'false');
            toggleBtn.innerHTML = '<iconify-icon icon="lucide:menu" class="text-3xl pointer-events-none"></iconify-icon>';
        }
    });
document.addEventListener('contextmenu', (e) => {
  e.preventDefault();
});
document.addEventListener('keydown', (e) => {
  if (
    e.key === 'F12' || 
    (e.ctrlKey && e.shiftKey && ['I', 'J', 'C'].includes(e.key.toUpperCase())) || 
    (e.ctrlKey && e.key.toUpperCase() === 'U')
  ) {
    e.preventDefault();
  }
});
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && !mobileMenu.classList.contains('hidden')) {
            toggleMenu();
        }
    });
});
(function initScrollAnimations() {
    const elements = document.querySelectorAll('.fade-in');
    if (!elements.length) return;
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('visible');
            }
        });
    }, {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    });
    elements.forEach(el => observer.observe(el));
})();
(function initBackToTop() {
    const btn = document.createElement('button');
    btn.className = 'fixed bottom-6 right-6 bg-[#1a3a6b] text-white p-3 rounded-full shadow-lg hover:bg-[#0d2647] transition-all opacity-0 invisible z-40 cursor-pointer';
    btn.innerHTML = '<iconify-icon icon="lucide:arrow-up" class="text-xl pointer-events-none"></iconify-icon>';
    btn.setAttribute('aria-label', 'Back to top');
    document.body.appendChild(btn);
    let isVisible = false;
    window.addEventListener('scroll', () => {
        const shouldShow = window.scrollY > 500;
        if (shouldShow !== isVisible) {
            isVisible = shouldShow;
            btn.style.opacity = shouldShow ? '1' : '0';
            btn.style.visibility = shouldShow ? 'visible' : 'hidden';
            btn.style.transform = shouldShow ? 'translateY(0)' : 'translateY(20px)';
        }
    });
    btn.addEventListener('click', () => {
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });
})();
console.log('%c Best Politicians', 'background: #1a3a6b; color: white; font-size: 20px; font-weight: bold; padding: 10px; border-radius: 5px;');
console.log('%c Thank you for visiting! Need help? Contact us at info@bestpoliticians.org', 'font-size: 14px;');
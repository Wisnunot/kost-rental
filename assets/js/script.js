// ============================================================
// KOST RENTAL — JavaScript
// ============================================================

document.addEventListener('DOMContentLoaded', function () {

    // === FAQ Accordion ===
    const faqItems = document.querySelectorAll('.faq-item');
    faqItems.forEach(item => {
        const question = item.querySelector('.faq-question');
        if (question) {
            question.addEventListener('click', () => {
                const isActive = item.classList.contains('active');
                faqItems.forEach(i => i.classList.remove('active'));
                if (!isActive) item.classList.add('active');
            });
        }
    });

    // === Alert Auto-dismiss ===
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.transition = 'opacity 0.5s';
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 500);
        }, 4000);
    });

    // === Smooth scroll for anchor links ===
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            const href = this.getAttribute('href');
            if (href === '#') return;
            e.preventDefault();
            const target = document.querySelector(href);
            if (target) {
                target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        });
    });

    // === Lightbox galeri ===
    document.querySelectorAll('.gallery-grid img').forEach(img => {
        img.addEventListener('click', () => {
            const lb = document.createElement('div');
            lb.className = 'lightbox';
            const big = document.createElement('img');
            big.src = img.src;
            lb.appendChild(big);
            lb.addEventListener('click', () => lb.remove());
            document.body.appendChild(lb);
        });
    });

    // === Notifikasi pemesanan (bell + popup) ===
    const notifWrap  = document.getElementById('notifWrap');
    const notifBell  = document.getElementById('notifBell');
    const notifBadge = document.getElementById('notifBadge');

    if (notifWrap && notifBell) {
        notifBell.addEventListener('click', function (e) {
            e.stopPropagation();
            notifWrap.classList.toggle('open');
        });

        // Klik di luar popup -> tutup
        document.addEventListener('click', function (e) {
            if (!notifWrap.contains(e.target)) {
                notifWrap.classList.remove('open');
            }
        });

        // Tutup pakai tombol Escape
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                notifWrap.classList.remove('open');
            }
        });

        // Kalau tidak ada notifikasi, badge tetap disembunyikan (double-guard)
        if (notifBadge && notifBadge.textContent.trim() === '0') {
            notifBadge.style.display = 'none';
        }
    }

});
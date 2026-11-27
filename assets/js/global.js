/**
 * DENTARA GLOBAL JAVASCRIPT & SPA ENGINE
 * File: assets/js/global.js
 */

window.DENTARA = window.DENTARA || {};

(function() {
  'use strict';

  // ====================================================================
  // 1. PROGRESS BAR HELPER
  // ====================================================================
  const ProgressBar = {
    el: null,
    init() {
      if (!this.el) {
        this.el = document.getElementById('spa-progress-bar');
        if (!this.el) {
          this.el = document.createElement('div');
          this.el.id = 'spa-progress-bar';
          document.body.appendChild(this.el);
        }
      }
    },
    start() {
      this.init();
      this.el.style.opacity = '1';
      this.el.style.width = '30%';
    },
    advance() {
      if (this.el) this.el.style.width = '75%';
    },
    finish() {
      if (this.el) {
        this.el.style.width = '100%';
        setTimeout(() => {
          this.el.style.opacity = '0';
          setTimeout(() => {
            if (this.el) this.el.style.width = '0%';
          }, 300);
        }, 200);
      }
    }
  };

  // ====================================================================
  // 2. SWEETALERT2 HELPERS (GANTI SEMUA WINDOW.ALERT & CONFIRM)
  // ====================================================================
  DENTARA.toast = function(type, title, message = '') {
    if (typeof Swal === 'undefined') {
      console.log(`[Toast ${type}] ${title}: ${message}`);
      return;
    }
    const Toast = Swal.mixin({
      toast: true,
      position: 'top-end',
      showConfirmButton: false,
      timer: 3500,
      timerProgressBar: true,
      didOpen: (toast) => {
        toast.addEventListener('mouseenter', Swal.stopTimer);
        toast.addEventListener('mouseleave', Swal.resumeTimer);
      }
    });
    Toast.fire({
      icon: type || 'success',
      title: title,
      text: message
    });
  };

  DENTARA.alert = function(type, title, text = '') {
    if (typeof Swal === 'undefined') {
      window.alert(title + "\n" + text);
      return Promise.resolve();
    }
    return Swal.fire({
      icon: type || 'info',
      title: title,
      html: text,
      confirmButtonText: 'Mengerti',
      buttonsStyling: true
    });
  };

  DENTARA.confirm = function(title, text, confirmText = 'Ya, Lanjutkan', cancelText = 'Batal') {
    if (typeof Swal === 'undefined') {
      return Promise.resolve(window.confirm(title + "\n" + text));
    }
    return Swal.fire({
      title: title,
      html: text,
      icon: 'question',
      showCancelButton: true,
      confirmButtonText: confirmText,
      cancelButtonText: cancelText,
      reverseButtons: true
    }).then(result => result.isConfirmed);
  };

  // ====================================================================
  // 3. DARK MODE ENGINE
  // ====================================================================
  DENTARA.initDarkMode = function() {
    const savedTheme = localStorage.getItem('dentara_theme');
    const prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
    
    if (savedTheme === 'dark' || (!savedTheme && prefersDark)) {
      document.documentElement.setAttribute('data-theme', 'dark');
    } else {
      document.documentElement.setAttribute('data-theme', 'light');
    }
    this.updateThemeToggleIcons();
  };

  DENTARA.toggleDarkMode = function() {
    const current = document.documentElement.getAttribute('data-theme');
    const next = current === 'dark' ? 'light' : 'dark';
    document.documentElement.setAttribute('data-theme', next);
    localStorage.setItem('dentara_theme', next);
    this.updateThemeToggleIcons();
  };

  DENTARA.updateThemeToggleIcons = function() {
    const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
    const toggles = document.querySelectorAll('.theme-toggle-btn');
    toggles.forEach(btn => {
      const icon = btn.querySelector('i');
      if (icon) {
        icon.className = isDark ? 'bx bx-sun' : 'bx bx-moon';
      }
    });
  };

  // ====================================================================
  // 4. NUMBER COUNT-UP ANIMATION
  // ====================================================================
  DENTARA.countUp = function(el, target, duration = 1500) {
    if (!el) return;
    const start = 0;
    const startTime = performance.now();
    const updateCount = (currentTime) => {
      const elapsed = currentTime - startTime;
      const progress = Math.min(elapsed / duration, 1);
      // Ease out cubic
      const current = Math.floor(start + (target - start) * (1 - Math.pow(1 - progress, 3)));
      el.textContent = current.toLocaleString('id-ID');
      if (progress < 1) {
        requestAnimationFrame(updateCount);
      } else {
        el.textContent = target.toLocaleString('id-ID');
      }
    };
    requestAnimationFrame(updateCount);
  };

  // ====================================================================
  // 5. SIDEBAR DRAWER TOGGLE (STAFF)
  // ====================================================================
  DENTARA.toggleSidebar = function() {
    const sidebar = document.querySelector('.staff-sidebar');
    const overlay = document.querySelector('.sidebar-overlay');
    if (sidebar && overlay) {
      sidebar.classList.toggle('is-open');
      overlay.classList.toggle('is-open');
    }
  };

  DENTARA.closeSidebar = function() {
    const sidebar = document.querySelector('.staff-sidebar');
    const overlay = document.querySelector('.sidebar-overlay');
    if (sidebar && overlay) {
      sidebar.classList.remove('is-open');
      overlay.classList.remove('is-open');
    }
  };

  // ====================================================================
  // 6. SPA INTERCEPTOR & CONTENT SWAP
  // ====================================================================
  DENTARA.loadPage = async function(url, pushState = true) {
    const contentArea = document.getElementById('content-area');
    if (!contentArea) {
      window.location.href = url;
      return;
    }

    ProgressBar.start();

    try {
      const response = await fetch(url, {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
      });

      if (!response.ok) {
        throw new Error('HTTP ' + response.status);
      }

      ProgressBar.advance();
      const htmlText = await response.text();
      const parser = new DOMParser();
      const doc = parser.parseFromString(htmlText, 'text/html');

      const newContent = doc.getElementById('content-area');
      if (!newContent) {
        // Fallback full reload jika halaman tidak punya #content-area
        window.location.href = url;
        return;
      }

      // Update Page Title
      if (doc.title) {
        document.title = doc.title;
      }

      // Update Topbar Title jika ada (Portal Staff)
      const currentTopbarTitle = document.querySelector('.staff-topbar h2');
      const newTopbarTitle = doc.querySelector('.staff-topbar h2');
      if (currentTopbarTitle && newTopbarTitle) {
        currentTopbarTitle.innerHTML = newTopbarTitle.innerHTML;
      }

      // Swap Content with fade effect
      contentArea.style.opacity = '0';
      setTimeout(() => {
        contentArea.innerHTML = newContent.innerHTML;
        contentArea.style.opacity = '1';

        // Update active menu link
        DENTARA.updateActiveNavLink(url);

        // Run embedded scripts
        newContent.querySelectorAll('script').forEach(oldScript => {
          const newScript = document.createElement('script');
          Array.from(oldScript.attributes).forEach(attr => newScript.setAttribute(attr.name, attr.value));
          newScript.appendChild(document.createTextNode(oldScript.innerHTML));
          document.body.appendChild(newScript).parentNode.removeChild(newScript);
        });

        // Trigger Event
        document.dispatchEvent(new CustomEvent('dentara:page-loaded', { detail: { url } }));
        ProgressBar.finish();

        if (pushState) {
          window.history.pushState({ url }, doc.title, url);
        }

        // Close sidebar if on mobile
        DENTARA.closeSidebar();
        window.scrollTo({ top: 0, behavior: 'smooth' });
      }, 150);

    } catch (err) {
      console.warn('SPA Navigation failed, falling back to full reload:', err);
      ProgressBar.finish();
      window.location.href = url;
    }
  };

  DENTARA.updateActiveNavLink = function(url) {
    const links = document.querySelectorAll('.nav-link, .patient-nav-item, .bottom-nav-link');
    const path = new URL(url, window.location.origin).pathname;
    links.forEach(link => {
      const linkPath = new URL(link.href, window.location.origin).pathname;
      if (linkPath === path) {
        link.classList.add('active');
      } else {
        link.classList.remove('active');
      }
    });
  };

  // ====================================================================
  // 7. EVENT LISTENERS SETUP
  // ====================================================================
  document.addEventListener('DOMContentLoaded', function() {
    DENTARA.initDarkMode();

    // Intercept clicks on internal links
    document.body.addEventListener('click', function(e) {
      const link = e.target.closest('a');
      if (!link) return;

      const href = link.getAttribute('href');
      // Lewatkan jika link luar, link aksi, atau download
      if (!href || href.startsWith('#') || href.startsWith('javascript:') || 
          href.startsWith('tel:') || href.startsWith('mailto:') ||
          link.getAttribute('target') === '_blank' || 
          link.hasAttribute('download') || 
          link.classList.contains('no-spa')) {
        return;
      }

      // Cek apakah domain internal
      const targetUrl = new URL(link.href, window.location.origin);
      if (targetUrl.origin === window.location.origin) {
        // Jangan intercept logout atau print kuitansi langsung
        if (targetUrl.pathname.includes('logout.php') || targetUrl.pathname.includes('kuitansi.php')) {
          return;
        }
        e.preventDefault();
        DENTARA.loadPage(link.href, true);
      }
    });

    // Handle Back/Forward history buttons
    window.addEventListener('popstate', function(e) {
      if (e.state && e.state.url) {
        DENTARA.loadPage(e.state.url, false);
      } else {
        DENTARA.loadPage(window.location.href, false);
      }
    });

    // Check PHP Flash Message from session
    if (window.dentaraFlash && window.dentaraFlash.type) {
      DENTARA.toast(window.dentaraFlash.type, window.dentaraFlash.title, window.dentaraFlash.message);
    }
  });

})();


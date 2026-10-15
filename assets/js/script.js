document.addEventListener('DOMContentLoaded', () => {
  const menuButton = document.querySelector('.menu-toggle');
  const nav = document.querySelector('.main-nav');
  if (menuButton && nav) {
    menuButton.addEventListener('click', () => {
      const opened = menuButton.getAttribute('aria-expanded') === 'true';
      menuButton.setAttribute('aria-expanded', String(!opened));
      nav.classList.toggle('is-open', !opened);
    });
    nav.querySelectorAll('a').forEach(link => link.addEventListener('click', () => {
      menuButton.setAttribute('aria-expanded', 'false');
      nav.classList.remove('is-open');
    }));
  }

  document.querySelectorAll('.nav-dropdown-toggle').forEach(button => {
    button.addEventListener('click', () => {
      const dropdown = button.closest('.nav-dropdown');
      if (dropdown) dropdown.classList.toggle('dropdown-open');
    });
  });

  document.querySelectorAll('form[onsubmit]').forEach(form => {
    form.addEventListener('submit', event => {
      const confirmation = form.getAttribute('onsubmit');
      if (confirmation && confirmation.includes('confirm(')) {
        // Native inline confirmation is retained for compatibility.
      }
    });
  });

  document.querySelectorAll('input[type="file"][multiple]').forEach(input => {
    input.addEventListener('change', () => {
      const files = Array.from(input.files || []);
      const invalid = files.find(file => file.size > 5 * 1024 * 1024);
      if (invalid) {
        alert('Ukuran setiap foto maksimal 5 MB.');
        input.value = '';
      }
    });
  });
});
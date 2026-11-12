document.querySelectorAll('.contact-enquiry__form').forEach(form => {
  const button = form.querySelector('button[type="submit"]');
  const status = form.querySelector('.contact-enquiry__status');
  form.addEventListener('submit', async event => {
    event.preventDefault();
    if (button.disabled || !form.reportValidity()) return;
    button.disabled = true;
    form.setAttribute('aria-busy', 'true');
    status.hidden = false;
    status.textContent = 'Sending your message…';
    status.classList.remove('is-success');
    try {
      const response = await fetch(form.getAttribute('action'), {method: 'POST', body: new FormData(form), headers: {Accept: 'application/json'}, credentials: 'same-origin'});
      const result = await response.json();
      status.textContent = result.data?.message || 'We could not send your message. Please try again or contact us by email or phone.';
      if (response.ok && result.success) {
        status.classList.add('is-success');
        form.reset();
      }
    } catch {
      status.textContent = 'We could not confirm your submission. Please check your connection before trying again, or contact us by email or phone.';
    } finally {
      button.disabled = false;
      form.removeAttribute('aria-busy');
      status.focus();
    }
  });
});

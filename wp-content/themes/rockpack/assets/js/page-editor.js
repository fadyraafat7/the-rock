/* Native page meta boxes: nested repeaters and WordPress media, no build step. */
(() => {
  let nextId = 0;
  // HTMX normally includes the entire enclosing WordPress form on POST.
  // Keep only this section's controls so large pages do not hit max_input_vars.
  document.addEventListener('htmx:configRequest', event => {
    const preview = event.detail.elt.closest('.rp-live-preview');
    if (!preview) return;
    const allowed = new Set(['action', 'post_id', 'section', 'nonce']);
    preview.closest('.postbox').querySelectorAll('input[name], select[name], textarea[name]').forEach(input => allowed.add(input.name));
    Object.keys(event.detail.parameters).forEach(name => {
      if (!allowed.has(name)) delete event.detail.parameters[name];
    });
  });
  document.addEventListener('htmx:responseError', event => {
    const preview = event.detail.elt.closest('.rp-live-preview');
    if (!preview) return;
    let message = preview.querySelector('.rp-live-preview__error');
    if (!message) {
      message = document.createElement('p');
      message.className = 'rp-live-preview__error';
      message.setAttribute('role', 'alert');
      preview.prepend(message);
    }
    message.textContent = 'Preview could not be updated. Check the section fields and try again.';
  });
  const direct = (node, selector) => [...node.children].filter(child => child.matches(selector));
  function refresh(group, prefix = group.dataset.prefix) {
    group.dataset.prefix = prefix;
    const items = group.querySelector(':scope > .rp-items');
    direct(items, '.rp-item').forEach((row, index, rows) => {
      row.querySelector(':scope > .rp-item-toolbar strong').textContent = `Item ${index + 1}`;
      row.querySelector(':scope > .rp-item-toolbar .rp-up').disabled = index === 0;
      row.querySelector(':scope > .rp-item-toolbar .rp-down').disabled = index === rows.length - 1;
      direct(row.querySelector(':scope > .rp-fields'), '.rp-field').forEach(field => {
        const name = `${prefix}[${index}][${field.dataset.key}]`;
        const nested = field.querySelector(':scope > .rp-repeater');
        if (nested) refresh(nested, name);
        else field.querySelector('input,textarea,select').name = name;
      });
    });
  }
  document.querySelectorAll('.rp-repeater').forEach(group => { if (!group.parentElement.closest('.rp-repeater')) refresh(group); });
  document.addEventListener('click', event => {
    const button = event.target.closest('button');
    if (!button) return;
    const group = button.closest('.rp-repeater');
    if (button.matches('.rp-add,.rp-remove,.rp-up,.rp-down')) {
      const items = group.querySelector(':scope > .rp-items');
      const row = button.closest('.rp-item');
      if (button.matches('.rp-add')) {
        const fragment = group.querySelector(':scope > template').content.cloneNode(true);
        // Cloned controls need unique label targets.
        fragment.querySelectorAll('[id]').forEach(input => {
          const old = input.id;
          input.id = `rp-new-${Date.now()}-${++nextId}`;
          fragment.querySelectorAll('label').forEach(label => { if (label.htmlFor === old) label.htmlFor = input.id; });
        });
        items.append(fragment);
        items.lastElementChild.querySelector('input:not([type=hidden]),textarea,select')?.focus();
      }
      if (button.matches('.rp-remove')) {
        const next = row.nextElementSibling || row.previousElementSibling;
        row.remove();
        (next?.querySelector('button') || group.querySelector(':scope > .rp-add')).focus();
      }
      if (button.matches('.rp-up') && row.previousElementSibling) items.insertBefore(row, row.previousElementSibling);
      if (button.matches('.rp-down') && row.nextElementSibling) items.insertBefore(row.nextElementSibling, row);
      refresh(group);
      group.dispatchEvent(new Event('change', { bubbles: true }));
      group.querySelector(':scope > .rp-repeater-status').textContent = 'Items updated. Use Update or Publish to save.';
    }
    if (button.matches('.rp-media,.rp-media-clear')) {
      const field = button.closest('.rp-field');
      const input = field.querySelector('[data-rp-image]');
      const idField = [...field.parentElement.children].find(el => el.dataset.key === field.dataset.key + '_id');
      const set = (url, id) => {
        input.value = url;
        if (idField) idField.querySelector('input').value = id;
        const preview = field.querySelector('.rp-media-preview');
        preview.hidden = !url;
        if (url) preview.src = url; else preview.removeAttribute('src');
        input.dispatchEvent(new Event('change', { bubbles: true }));
      };
      if (button.matches('.rp-media-clear')) return set('', 0);
      const frame = wp.media({ title: 'Select an image', library: { type: 'image' }, multiple: false });
      frame.on('select', () => { const attachment = frame.state().get('selection').first().toJSON(); set(attachment.url, attachment.id); });
      frame.open();
    }
  });
  document.addEventListener('click', event => {
    const img = event.target.closest('.rp-section-preview-img');
    if (img) img.classList.toggle('is-expanded');
    const del = event.target.closest('.rp-section-preview-delete');
    if (del) {
      if (!confirm('Delete this section preview image?')) return;
      const wrap = del.closest('.rp-section-preview');
      const slug = wrap.dataset.slug;
      const fd = new FormData();
      fd.append('action','rp_preview_delete'); fd.append('nonce',rpPreview.nonce); fd.append('slug',slug);
      fetch(rpPreview.ajaxurl,{method:'POST',body:fd}).then(r=>r.json()).then(r=>{
        if(r.success) wrap.outerHTML = '<div class="rp-section-preview rp-section-preview--empty" data-slug="'+slug+'"><p class="rp-section-preview-missing">No preview image for <code>'+slug+'</code></p><label class="button rp-section-preview-edit">Add image<input type="file" accept="image/*" hidden></label></div>';
        else alert('Error: '+r.data);
      });
    }
  });
  document.addEventListener('change', event => {
    const input = event.target.closest('.rp-section-preview-edit input[type=file]');
    if (!input || !input.files[0]) return;
    const wrap = input.closest('.rp-section-preview');
    const slug = wrap.dataset.slug;
    const fd = new FormData();
    fd.append('action','rp_preview_upload'); fd.append('nonce',rpPreview.nonce); fd.append('slug',slug); fd.append('file',input.files[0]);
    fetch(rpPreview.ajaxurl,{method:'POST',body:fd}).then(r=>r.json()).then(r=>{
      if(r.success) {
        const img = wrap.querySelector('.rp-section-preview-img');
        if(img) { img.src = r.data.url+'?t='+Date.now(); }
        else {
          wrap.classList.remove('rp-section-preview--empty');
          wrap.innerHTML = '<img src="'+r.data.url+'?t='+Date.now()+'" alt="" class="rp-section-preview-img"><div class="rp-section-preview-actions"><label class="button rp-section-preview-edit">Replace<input type="file" accept="image/*" hidden></label><button type="button" class="button rp-section-preview-delete">Delete</button></div>';
        }
      }
      else alert('Error: '+r.data);
    });
    input.value='';
  });
  document.addEventListener('input', event => {
    if (!event.target.matches('[data-rp-image]')) return;
    const field = event.target.closest('.rp-field');
    const idField = [...field.parentElement.children].find(el => el.dataset.key === field.dataset.key + '_id');
    if (idField) idField.querySelector('input').value = 0;
    const preview = field.querySelector('.rp-media-preview');
    preview.hidden = !event.target.value;
    if (event.target.value) preview.src = event.target.value;
  });
})();

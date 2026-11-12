document.querySelectorAll('.rp-listing-hero-editor').forEach(editor => {
  const title = editor.querySelector('.rp-listing-title');
  const image = editor.querySelector('.rp-listing-image');
  const preview = editor.querySelector('.rp-listing-preview');
  const update = () => {
    const doc = preview.contentDocument;
    if (!doc) return;
    const heading = doc.querySelector('.rp-inner-hero h1');
    const breadcrumb = doc.querySelector('.rp-inner-breadcrumb [aria-current]');
    const photo = doc.querySelector('.rp-inner-hero__image');
    if (heading) heading.textContent = title.value;
    if (breadcrumb) breadcrumb.textContent = title.value;
    if (photo) photo.src = image.value;
  };
  preview.addEventListener('load', update);
  title.addEventListener('input', update);
  image.addEventListener('input', update);
  editor.querySelector('.rp-listing-select').addEventListener('click', () => {
    const picker = wp.media({title: 'Choose hero image', button: {text: 'Use image'}, library: {type: 'image'}, multiple: false});
    picker.on('select', () => { image.value = picker.state().get('selection').first().toJSON().url; update(); });
    picker.open();
  });
});

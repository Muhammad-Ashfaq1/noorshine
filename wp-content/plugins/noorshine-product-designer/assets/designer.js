(() => {
  document.querySelectorAll('[data-nspd]').forEach(root => {
  const textOption = root.querySelector('[data-nspd-text-option]');
  const customFields = root.querySelector('[data-custom-fields]');
  const customText = root.querySelector('[data-custom-text]');
  const previewText = root.querySelector('[data-preview-text]');
  const previewShape = root.querySelector('[data-preview-shape]');
  const previewArt = root.querySelector('[data-preview-art]');
  const ring = root.querySelector('[data-preview-ring]');
  const style = root.querySelector('[data-nspd-style]');
  const template = root.querySelector('[data-nspd-template]');
  const finish = root.querySelector('[data-nspd-finish]');
  const material = root.querySelector('[data-nspd-material]');
  const size = root.querySelector('[data-nspd-size]');
  const estimate = root.querySelector('[data-nspd-estimate]');
  const pricing = window.nspdConfig;
  let basePrice = pricing?.basePrice ?? null;

  const colors = { 'Matte Black': '#1f1e1a', 'Gloss Black': '#090909', Gold: '#b89452', Silver: '#8f9395', 'Natural Wood': '#b98c61', Walnut: '#684735', White: '#f9f7f1' };
  const shapes = {
    arch: 'M170 420V220C170 105 470 105 470 220V420Z',
    round: 'M320 92A170 170 0 1 1 319.9 92Z',
    landscape: 'M90 155H550V385H90Z'
  };

  function update() {
    const custom = textOption.value === '__custom__';
    customFields.hidden = !custom;
    const value = custom ? customText.value : textOption.value;
    previewText.textContent = value;
    previewText.setAttribute('font-size', String(Math.min(42, 490 / Math.max(1, Array.from(value).length))));
    previewShape.setAttribute('d', shapes[template.value.toLowerCase()] || shapes.arch);
    previewShape.setAttribute('fill', colors[finish.value] || '#1f1e1a');
    previewText.setAttribute('fill', ['Matte Black', 'Gloss Black', 'Walnut'].includes(finish.value) ? '#f4ead9' : '#1f1e1a');
    const scale = Math.min(1, 0.57 + (parseInt(size.value, 10) || 12) / 85);
    previewArt.setAttribute('transform', `translate(320 260) scale(${scale}) translate(-320 -260)`);
    ring.setAttribute('opacity', style.value === 'Quiet Minimal' ? '0' : '1');
    ring.setAttribute('stroke-dasharray', style.value === 'Geometric Halo' ? '8 5' : 'none');

    if (pricing?.pricingApproved) {
      const adjustment = pricing.pricingRules.reduce((sum, rule) => rule.size === size.value && (rule.material === '*' || rule.material === material.value) ? sum + Number(rule.adjustment) : sum, 0);
      if (basePrice === null) {
        estimate.textContent = 'Select a product variation to see the estimate.';
      } else {
        const total = Number(basePrice) + Math.max(0, adjustment);
        const formatted = new Intl.NumberFormat(undefined, { style: 'currency', currency: pricing.currency }).format(total);
        estimate.textContent = `Estimated configuration: ${formatted}`;
      }
    }
  }
  root.querySelectorAll('select, input').forEach(field => field.addEventListener('input', update));
  const variationForm = root.closest('.variations_form');
  if (variationForm) {
    window.jQuery(variationForm).on('found_variation', (_event, variation) => {
      basePrice = variation.is_purchasable ? variation.display_price : null;
      update();
    }).on('reset_data hide_variation', () => {
      basePrice = null;
      update();
    });
  }
  update();
  });
})();


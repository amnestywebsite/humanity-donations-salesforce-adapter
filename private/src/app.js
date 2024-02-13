const { addFilter } = wp.hooks;
const { __, sprintf } = wp.i18n;

/**
 * Find a parent element, optionally by selector
 *
 * @param {Node} $node the origin node
 * @param {string} selector selector to optionally traverse for
 *
 * @return {Node|null} the found parent
 */
const parent = ($node, selector = '') => {
  if (!$node) {
    return null;
  }

  let elem = $node.parentElement;

  if (!selector) {
    return elem;
  }

  while (elem != null) {
    if (elem.matches(selector)) {
      return elem;
    }

    elem = elem.parentElement;
  }

  return null;
};

/**
 * Remove sobject fields and render message
 *
 * @param {Event} event the current change event
 */
const handleSobjectChange = (event) => {
  const $container = parent(event.target, '.cmb-repeatable-group');
  const $inputs = $container.querySelectorAll(`[data-replace-from="sobject"]`);
  const $wrap = parent($inputs[0], '.cmb-nested.cmb-field-list');

  if (!$wrap) {
    return;
  }

  $inputs.forEach(($i) => $wrap.removeChild(parent($i, '.cmb-row')));

  const frag = document.createElement('template');
  frag.innerHTML = sprintf(
    '<p>%s</p>',
    __('Please save your settings to load the field list for this object.', 'adsa'),
  );

  $wrap.appendChild(frag.content);
};

addFilter('aisc.events.change.sobject', 'adsa', (callback, event) => {
  if (!event.target.dataset.replaceFields) {
    return callback;
  }

  return handleSobjectChange;
});

addFilter('aisc.events.change.default', 'adsa', (callback, event) => {
  if (event.target.name.indexOf('[object]') === -1) {
    return callback;
  }

  if (!event.target.dataset.replaceFields) {
    return callback;
  }

  return handleSobjectChange;
});

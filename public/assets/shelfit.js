'use strict';

const filters = document.querySelector('[data-catalog-filters]');
if (filters) {
    filters.addEventListener('change', (event) => {
        if (event.target.matches('select')) filters.requestSubmit();
    });
    filters.querySelector('[data-clear-search]').addEventListener('click', (event) => {
        if (event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
        const hasFilters = [...new FormData(filters).values()].some((value) => value !== '');
        if (!hasFilters && !window.location.search) {
            event.preventDefault();
            filters.elements.q.focus();
        }
    });
}

const copyPicker = document.querySelector('[data-copy-picker]');
if (copyPicker && copyPicker.elements.copy.options.length > 1) {
    const showCopy = () => {
        const copyId = copyPicker.elements.copy.value;
        const location = document.getElementById('copy-location');
        const template = document.getElementById('copy-location-' + copyId);
        location.replaceChildren(template.content.cloneNode(true));
        const url = new URL(window.location.href);
        url.searchParams.set('copy', copyId);
        window.history.replaceState(null, '', url);
    };
    copyPicker.elements.copy.addEventListener('change', showCopy);
    copyPicker.addEventListener('submit', (event) => {
        event.preventDefault();
        showCopy();
    });
}

function narrowOptions(select, parentId) {
    for (const option of select.options) {
        const available = !option.value || option.dataset.parent === parentId;
        option.hidden = !available;
        option.disabled = !available;
    }
    if (select.selectedOptions[0]?.disabled) select.value = '';
    select.disabled = !parentId;
}

for (const form of document.querySelectorAll('[data-location-form]')) {
    const { section_id: section, cabinet_id: cabinet, shelf_id: shelf } = form.elements;
    const chooseCabinet = () => narrowOptions(shelf, cabinet.value);
    const chooseSection = () => {
        narrowOptions(cabinet, section.value);
        chooseCabinet();
    };
    section.addEventListener('change', chooseSection);
    cabinet.addEventListener('change', chooseCabinet);
    chooseSection();
}

let errorId = 0;
for (const form of document.querySelectorAll('[data-validate]')) {
    function clearError(field) {
        const error = document.getElementById(field.dataset.errorId);
        if (!error) return;
        error.remove();
        field.removeAttribute('aria-invalid');
        field.removeAttribute('aria-describedby');
        delete field.dataset.errorId;
    }

    form.addEventListener('invalid', (event) => {
        const field = event.target;
        if (field.dataset.errorId) return;
        const error = document.createElement('span');
        error.className = 'field-error';
        error.id = 'field-error-' + (++errorId);
        error.textContent = field.validationMessage;
        (field.closest('label') || field).insertAdjacentElement('afterend', error);
        field.dataset.errorId = error.id;
        field.setAttribute('aria-invalid', 'true');
        field.setAttribute('aria-describedby', error.id);
    }, true);
    form.addEventListener('input', (event) => clearError(event.target));
    form.addEventListener('change', (event) => clearError(event.target));
}

for (const form of document.querySelectorAll('[data-confirm]')) {
    form.addEventListener('submit', (event) => {
        if (!window.confirm(form.dataset.confirm)) event.preventDefault();
    });
}

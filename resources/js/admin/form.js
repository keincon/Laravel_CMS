import { api } from './api.js';
import { showToast } from './toast.js';

/**
 * Guard against duplicate submissions and map validation errors to fields.
 */
export function bindAjaxForm(form, { onSuccess = null } = {}) {
  if (!form || form.dataset.ajaxBound === '1') return;
  form.dataset.ajaxBound = '1';

  let submitting = false;

  form.addEventListener('submit', async (event) => {
    event.preventDefault();
    if (submitting) return;
    submitting = true;

    const submitBtn = form.querySelector('[type="submit"]');
    if (submitBtn) submitBtn.disabled = true;
    form.querySelectorAll('[data-error-for]').forEach((el) => {
      el.textContent = '';
    });

    try {
      const formData = new FormData(form);
      const method = (form.getAttribute('method') || 'POST').toUpperCase();
      const payload = await api(form.action, { method, body: formData });
      showToast(payload?.message || 'Saved.', 'success');
      if (onSuccess) onSuccess(payload);
    } catch (error) {
      if (error.status === 422 && error.errors) {
        Object.entries(error.errors).forEach(([field, messages]) => {
          const target = form.querySelector(`[data-error-for="${field}"]`);
          if (target) target.textContent = Array.isArray(messages) ? messages[0] : String(messages);
        });
        showToast(error.message || 'Validation failed.', 'error');
      } else if (error.status === 403) {
        showToast('You are not authorized to perform this action.', 'error');
      } else {
        showToast(error.message || 'Request failed.', 'error');
      }
    } finally {
      submitting = false;
      if (submitBtn) submitBtn.disabled = false;
    }
  });
}

export function warnUnsavedChanges(form) {
  if (!form) return;
  let dirty = false;
  form.addEventListener('input', () => {
    dirty = true;
  });
  form.addEventListener('submit', () => {
    dirty = false;
  });
  window.addEventListener('beforeunload', (e) => {
    if (!dirty) return;
    e.preventDefault();
    e.returnValue = '';
  });
}

/** Post editor helpers: autosave + status actions. */
import { api } from './api.js';
import { showToast } from './toast.js';
import { warnUnsavedChanges } from './form.js';

export function bindPostEditor(form, { autosaveUrl = null, intervalMs = 30000 } = {}) {
  if (!form) return;
  warnUnsavedChanges(form);

  if (!autosaveUrl) return;

  setInterval(async () => {
    const formData = new FormData(form);
    formData.append('autosave', '1');
    try {
      await api(autosaveUrl, { method: 'POST', body: formData });
    } catch {
      // Autosave failures are non-blocking.
    }
  }, intervalMs);
}

export async function transitionStatus(url, status) {
  try {
    const result = await api(url, { method: 'PATCH', body: { status } });
    showToast(`Status updated to ${status}.`, 'success');
    return result;
  } catch (error) {
    showToast(error.message || 'Status update failed.', 'error');
    throw error;
  }
}

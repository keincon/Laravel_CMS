/** Media library AJAX helpers (upload / attach). */
import { api } from './api.js';
import { showToast } from './toast.js';

export async function uploadMedia(file, endpoint = '/admin/media') {
  const body = new FormData();
  body.append('file', file);
  try {
    const result = await api(endpoint, { method: 'POST', body });
    showToast('Media uploaded.', 'success');
    return result;
  } catch (error) {
    showToast(error.message || 'Upload failed.', 'error');
    throw error;
  }
}

export function bindDropzone(element, { onUploaded = null, endpoint = '/admin/media' } = {}) {
  if (!element) return;

  element.addEventListener('dragover', (e) => {
    e.preventDefault();
    element.classList.add('is-dragover');
  });
  element.addEventListener('dragleave', () => element.classList.remove('is-dragover'));
  element.addEventListener('drop', async (e) => {
    e.preventDefault();
    element.classList.remove('is-dragover');
    const files = [...(e.dataTransfer?.files || [])];
    for (const file of files) {
      const result = await uploadMedia(file, endpoint);
      if (onUploaded) onUploaded(result, file);
    }
  });
}

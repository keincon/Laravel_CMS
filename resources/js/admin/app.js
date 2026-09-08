import { bindBlockEditor } from './editor.js';

window.LaravelPress = window.LaravelPress || {};
window.LaravelPress.bindBlockEditor = bindBlockEditor;

function readInitialBlocks(hidden) {
  const dataEl = document.getElementById('blocks_json_data');
  if (dataEl) {
    try {
      const parsed = JSON.parse(dataEl.textContent || '[]');
      if (Array.isArray(parsed)) {
        hidden.value = JSON.stringify(parsed);
        return parsed;
      }
    } catch {
      // fall through
    }
  }
  try {
    const parsed = JSON.parse(hidden.value || '[]');
    return Array.isArray(parsed) ? parsed : [];
  } catch {
    return [];
  }
}

document.addEventListener('DOMContentLoaded', () => {
  const root = document.getElementById('block-editor-root');
  const hidden = document.getElementById('blocks_json');
  if (!root || !hidden || root.dataset.bound === '1') return;
  root.dataset.bound = '1';

  bindBlockEditor(root, {
    hiddenInput: hidden,
    initialBlocks: readInitialBlocks(hidden),
    mediaJsonUrl: root.dataset.mediaJsonUrl || '/admin/media/json',
  });
});

import { bindBlockEditor } from './editor.js';

window.LaravelPress = window.LaravelPress || {};
window.LaravelPress.bindBlockEditor = bindBlockEditor;

document.addEventListener('DOMContentLoaded', () => {
  const root = document.getElementById('block-editor-root');
  const hidden = document.getElementById('blocks_json');
  if (!root || !hidden || root.dataset.bound === '1') return;
  root.dataset.bound = '1';

  let initial = [];
  try {
    initial = JSON.parse(hidden.value || '[]');
  } catch {
    initial = [];
  }

  bindBlockEditor(root, {
    hiddenInput: hidden,
    initialBlocks: Array.isArray(initial) ? initial : [],
    mediaJsonUrl: root.dataset.mediaJsonUrl || '/admin/media/json',
  });
});

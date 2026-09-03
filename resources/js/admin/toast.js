export function showToast(message, type = 'info') {
  let root = document.getElementById('cms-toast-root');
  if (!root) {
    root = document.createElement('div');
    root.id = 'cms-toast-root';
    root.style.cssText = 'position:fixed;right:1rem;bottom:1rem;z-index:9999;display:flex;flex-direction:column;gap:.5rem;';
    document.body.appendChild(root);
  }

  const el = document.createElement('div');
  const colors = {
    info: '#1e293b',
    success: '#166534',
    error: '#991b1b',
    warning: '#92400e',
  };
  el.textContent = message;
  el.style.cssText = `background:${colors[type] || colors.info};color:#fff;padding:.75rem 1rem;border-radius:.5rem;box-shadow:0 8px 24px rgba(0,0,0,.2);max-width:22rem;`;
  root.appendChild(el);
  setTimeout(() => el.remove(), 4000);
}

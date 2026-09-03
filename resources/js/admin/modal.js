export function createModal({ title = '', body = '', onConfirm = null, confirmLabel = 'OK' } = {}) {
  const overlay = document.createElement('div');
  overlay.style.cssText = 'position:fixed;inset:0;background:rgba(15,23,42,.55);display:flex;align-items:center;justify-content:center;z-index:10000;';

  const panel = document.createElement('div');
  panel.style.cssText = 'background:#fff;border-radius:.75rem;max-width:32rem;width:90%;padding:1.25rem;box-shadow:0 20px 50px rgba(0,0,0,.25);';
  panel.innerHTML = `
    <h2 style="font-size:1.125rem;font-weight:600;margin:0 0 .75rem;">${title}</h2>
    <div class="cms-modal-body" style="margin-bottom:1rem;">${body}</div>
    <div style="display:flex;justify-content:flex-end;gap:.5rem;">
      <button type="button" data-action="cancel" style="padding:.5rem .9rem;border:1px solid #cbd5e1;border-radius:.4rem;background:#fff;">Cancel</button>
      <button type="button" data-action="confirm" style="padding:.5rem .9rem;border:0;border-radius:.4rem;background:#2563eb;color:#fff;">${confirmLabel}</button>
    </div>
  `;

  overlay.appendChild(panel);
  document.body.appendChild(overlay);

  const close = () => overlay.remove();
  panel.querySelector('[data-action="cancel"]').addEventListener('click', close);
  panel.querySelector('[data-action="confirm"]').addEventListener('click', async () => {
    if (onConfirm) await onConfirm();
    close();
  });
  overlay.addEventListener('click', (e) => {
    if (e.target === overlay) close();
  });

  return { close, overlay };
}

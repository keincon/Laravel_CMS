(function () {
/**
 * Gutenberg-inspired block editor for LaravelPress (vanilla JS).
 * Features: slash inserter, floating toolbar, inspector, nested columns,
 * drag-reorder, transforms, media modal, preview mode, keyboard shortcuts.
 */

const BLOCK_TYPES = [
  { type: 'paragraph', label: 'Paragraph', keywords: 'text p', category: 'text' },
  { type: 'heading', label: 'Heading', keywords: 'title h1 h2', category: 'text' },
  { type: 'list', label: 'List', keywords: 'ul ol bullets', category: 'text' },
  { type: 'quote', label: 'Quote', keywords: 'blockquote cite', category: 'text' },
  { type: 'image', label: 'Image', keywords: 'photo media', category: 'media' },
  { type: 'gallery', label: 'Gallery', keywords: 'images photos', category: 'media' },
  { type: 'video', label: 'Video', keywords: 'media mp4', category: 'media' },
  { type: 'audio', label: 'Audio', keywords: 'media mp3', category: 'media' },
  { type: 'embed', label: 'Embed', keywords: 'iframe youtube', category: 'media' },
  { type: 'button', label: 'Button', keywords: 'cta link', category: 'design' },
  { type: 'columns', label: 'Columns', keywords: 'layout grid', category: 'design' },
  { type: 'separator', label: 'Separator', keywords: 'hr divider', category: 'design' },
  { type: 'spacer', label: 'Spacer', keywords: 'space gap', category: 'design' },
  { type: 'code', label: 'Code', keywords: 'pre snippet', category: 'text' },
  { type: 'html', label: 'Custom HTML', keywords: 'raw markup', category: 'widgets' },
];

function uid() {
  return `b_${Math.random().toString(36).slice(2, 10)}`;
}

function defaultBlock(type) {
  const block = { id: uid(), type, content: '', attrs: {}, innerBlocks: [] };
  if (type === 'heading') block.attrs = { level: 2, align: 'left' };
  if (type === 'paragraph') block.attrs = { align: 'left' };
  if (type === 'button') block.attrs = { url: '#', label: 'Learn more', align: 'left' };
  if (type === 'spacer') block.attrs = { height: 24 };
  if (type === 'image') block.attrs = { src: '', alt: '', align: 'center' };
  if (type === 'gallery') block.attrs = { images: [] };
  if (type === 'list') block.attrs = { ordered: false, items: [''] };
  if (type === 'video' || type === 'audio' || type === 'embed') block.attrs = { src: '' };
  if (type === 'columns') {
    block.attrs = { columns: 2 };
    block.innerBlocks = [
      { id: uid(), type: 'paragraph', content: '', attrs: {}, innerBlocks: [] },
      { id: uid(), type: 'paragraph', content: '', attrs: {}, innerBlocks: [] },
    ];
  }
  return block;
}

function ensureIds(blocks) {
  return (Array.isArray(blocks) ? blocks : []).map((b) => ({
    ...b,
    id: b.id || uid(),
    attrs: b.attrs || {},
    innerBlocks: ensureIds(b.innerBlocks || []),
    content: b.content ?? '',
  }));
}

function escapeHtml(value) {
  return String(value ?? '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;');
}

function renderPreviewHtml(blocks) {
  return blocks.map((block) => {
    const a = block.attrs || {};
    const c = block.content || '';
    switch (block.type) {
      case 'paragraph':
        return `<p class="lp-align-${a.align || 'left'}">${escapeHtml(c)}</p>`;
      case 'heading': {
        const level = Math.min(6, Math.max(1, Number(a.level) || 2));
        return `<h${level} class="lp-align-${a.align || 'left'}">${escapeHtml(c)}</h${level}>`;
      }
      case 'quote':
        return `<blockquote><p>${escapeHtml(c)}</p></blockquote>`;
      case 'list': {
        const tag = a.ordered ? 'ol' : 'ul';
        const items = (a.items || []).map((i) => `<li>${escapeHtml(i)}</li>`).join('');
        return `<${tag}>${items}</${tag}>`;
      }
      case 'image':
        return a.src
          ? `<figure class="lp-align-${a.align || 'center'}"><img src="${escapeHtml(a.src)}" alt="${escapeHtml(a.alt || '')}"></figure>`
          : '';
      case 'gallery':
        return `<div class="cms-gallery">${(a.images || []).map((img) => `<figure><img src="${escapeHtml(img.src || '')}" alt="${escapeHtml(img.alt || '')}"></figure>`).join('')}</div>`;
      case 'video':
        return a.src ? `<video controls src="${escapeHtml(a.src)}"></video>` : '';
      case 'audio':
        return a.src ? `<audio controls src="${escapeHtml(a.src)}"></audio>` : '';
      case 'embed':
        return a.src ? `<div class="cms-embed"><iframe src="${escapeHtml(a.src)}" loading="lazy" title="Embed"></iframe></div>` : '';
      case 'button':
        return `<p class="lp-align-${a.align || 'left'}"><a class="cms-button" href="${escapeHtml(a.url || '#')}">${escapeHtml(a.label || c || 'Button')}</a></p>`;
      case 'columns':
        return `<div class="cms-columns">${renderPreviewHtml(block.innerBlocks || [])}</div>`;
      case 'code':
        return `<pre><code>${escapeHtml(c)}</code></pre>`;
      case 'html':
        return c;
      case 'separator':
        return '<hr>';
      case 'spacer':
        return `<div style="height:${Number(a.height) || 24}px"></div>`;
      default:
        return '';
    }
  }).join('\n');
}

function bindBlockEditor(root, options = {}) {
  if (!root) return null;

  const {
    hiddenInput,
    mediaJsonUrl = '/admin/media/json',
    initialBlocks = [],
  } = options;

  if (!hiddenInput) return null;

  let state = ensureIds(
    Array.isArray(initialBlocks) && initialBlocks.length
      ? initialBlocks
      : (() => {
          try {
            const parsed = JSON.parse(hiddenInput.value || '[]');
            return Array.isArray(parsed) ? parsed : [];
          } catch {
            return [];
          }
        })(),
  );

  let selectedId = state[0]?.id || null;
  let mode = 'edit'; // edit | preview
  let inserter = null; // { index, filter, active }
  let dragId = null;
  let inspectorOpen = false;

  const shell = document.createElement('div');
  shell.className = 'lp-editor';
  root.innerHTML = '';
  root.appendChild(shell);

  let canvasEl;
  let previewEl;
  let inspectorEl;
  let layoutEl;

  function syncHidden() {
    const payload = state.map(({ id, ...rest }) => {
      const copy = { ...rest };
      if (Array.isArray(copy.innerBlocks)) {
        copy.innerBlocks = copy.innerBlocks.map(({ id: _id, ...inner }) => inner);
      }
      return copy;
    });
    hiddenInput.value = JSON.stringify(payload);
    hiddenInput.dispatchEvent(new Event('input', { bubbles: true }));
  }

  function findBlock(id, list = state) {
    for (let i = 0; i < list.length; i++) {
      if (list[i].id === id) return { block: list[i], index: i, list };
      if (list[i].innerBlocks?.length) {
        const nested = findBlock(id, list[i].innerBlocks);
        if (nested) return nested;
      }
    }
    return null;
  }

  function selected() {
    return selectedId ? findBlock(selectedId) : null;
  }

  function select(id, opts = {}) {
    const rebuild = !!opts.rebuild;
    if (selectedId === id && !rebuild && canvasEl) {
      if (inspectorEl && inspectorOpen) renderInspector(inspectorEl);
      return;
    }
    selectedId = id;
    if (rebuild || !canvasEl || mode === 'preview') {
      render();
      focusSelectedEditable();
      return;
    }
    shell.querySelectorAll('.lp-block.is-selected').forEach((el) => el.classList.remove('is-selected'));
    const card = id ? shell.querySelector(`[data-block-id="${CSS.escape(id)}"]`) : null;
    if (card) card.classList.add('is-selected');
    if (inspectorEl && inspectorOpen) renderInspector(inspectorEl);
  }

  function focusSelectedEditable() {
    queueMicrotask(() => {
      if (!selectedId) return;
      const el = shell.querySelector(`[data-block-id="${CSS.escape(selectedId)}"] [contenteditable="true"]`);
      if (!el) return;
      el.focus();
      try {
        const range = document.createRange();
        range.selectNodeContents(el);
        range.collapse(false);
        const sel = window.getSelection();
        sel.removeAllRanges();
        sel.addRange(range);
      } catch {
        // ignore
      }
    });
  }

  function insertAt(index, type, list = state) {
    const block = defaultBlock(type);
    list.splice(index, 0, block);
    selectedId = block.id;
    inserter = null;
    syncHidden();
    render();
    focusSelectedEditable();
  }

  function removeSelected() {
    const hit = selected();
    if (!hit) return;
    hit.list.splice(hit.index, 1);
    selectedId = hit.list[hit.index]?.id || hit.list[hit.index - 1]?.id || null;
    syncHidden();
    render();
  }

  function moveSelected(delta) {
    const hit = selected();
    if (!hit) return;
    const next = hit.index + delta;
    if (next < 0 || next >= hit.list.length) return;
    const tmp = hit.list[hit.index];
    hit.list[hit.index] = hit.list[next];
    hit.list[next] = tmp;
    syncHidden();
    render();
  }

  function duplicateSelected() {
    const hit = selected();
    if (!hit) return;
    const clone = ensureIds([JSON.parse(JSON.stringify({ ...hit.block, id: undefined }))])[0];
    hit.list.splice(hit.index + 1, 0, clone);
    selectedId = clone.id;
    syncHidden();
    render();
  }

  function transformSelected(toType) {
    const hit = selected();
    if (!hit) return;
    const prev = hit.block;
    const next = defaultBlock(toType);
    next.content = prev.content;
    if (toType === 'list' && prev.content) {
      next.attrs.items = prev.content.split(/\n+/).filter(Boolean);
    }
    if (prev.type === 'list' && Array.isArray(prev.attrs?.items)) {
      next.content = prev.attrs.items.join('\n');
    }
    if (toType === 'heading') next.attrs.level = prev.attrs?.level || 2;
    if (prev.attrs?.align) next.attrs.align = prev.attrs.align;
    hit.list[hit.index] = next;
    selectedId = next.id;
    syncHidden();
    render();
  }

  function openInserter(index, anchorEl) {
    inserter = { index, filter: '', active: 0, anchorEl };
    render();
  }

  function closeInserter() {
    inserter = null;
    render();
  }

  function filteredTypes() {
    const q = (inserter?.filter || '').trim().toLowerCase();
    if (!q) return BLOCK_TYPES;
    return BLOCK_TYPES.filter((t) =>
      `${t.label} ${t.type} ${t.keywords}`.toLowerCase().includes(q),
    );
  }

  async function openMediaPicker(onPick) {
    const modal = document.createElement('div');
    modal.className = 'lp-media-modal';
    modal.innerHTML = `<div class="lp-media-modal__panel">
      <div style="display:flex;justify-content:space-between;align-items:center;gap:.5rem">
        <strong>Media Library</strong>
        <button type="button" data-close>Close</button>
      </div>
      <div class="lp-media-modal__grid" data-grid>Loading…</div>
    </div>`;
    document.body.appendChild(modal);
    modal.querySelector('[data-close]').addEventListener('click', () => modal.remove());
    modal.addEventListener('click', (e) => { if (e.target === modal) modal.remove(); });

    try {
      const res = await fetch(`${mediaJsonUrl}?per_page=48`, {
        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        credentials: 'same-origin',
      });
      const payload = await res.json();
      const items = payload.data || payload || [];
      const grid = modal.querySelector('[data-grid]');
      grid.innerHTML = '';
      if (!items.length) {
        grid.textContent = 'No media yet. Upload files in Media Library.';
        return;
      }
      items.forEach((item) => {
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'lp-media-modal__item';
        btn.innerHTML = item.url
          ? `<img src="${escapeHtml(item.url)}" alt=""><span>${escapeHtml(item.filename || item.alt || `#${item.id}`)}</span>`
          : `<span>${escapeHtml(item.filename || `#${item.id}`)}</span>`;
        btn.addEventListener('click', () => {
          onPick(item);
          modal.remove();
        });
        grid.appendChild(btn);
      });
    } catch {
      modal.querySelector('[data-grid]').textContent = 'Failed to load media.';
    }
  }

  function field(parent, label, el) {
    const wrap = document.createElement('div');
    wrap.className = 'lp-inspector-field';
    const lab = document.createElement('label');
    lab.textContent = label;
    wrap.appendChild(lab);
    wrap.appendChild(el);
    parent.appendChild(wrap);
  }

  function renderInspector(panel) {
    panel.innerHTML = '';
    const title = document.createElement('h3');
    title.textContent = 'Block';
    panel.appendChild(title);

    const hit = selected();
    if (!hit) {
      const empty = document.createElement('p');
      empty.style.color = 'var(--lp-muted)';
      empty.textContent = 'Select a block to edit settings.';
      panel.appendChild(empty);
      return;
    }

    const { block } = hit;
    const typeLabel = document.createElement('p');
    typeLabel.innerHTML = `<strong>${escapeHtml(block.type)}</strong>`;
    panel.appendChild(typeLabel);

    if (block.type === 'heading') {
      const select = document.createElement('select');
      [1, 2, 3, 4, 5, 6].forEach((n) => {
        const opt = document.createElement('option');
        opt.value = String(n);
        opt.textContent = `H${n}`;
        opt.selected = Number(block.attrs.level || 2) === n;
        select.appendChild(opt);
      });
      select.addEventListener('change', () => {
        block.attrs.level = Number(select.value);
        syncHidden();
        render();
      });
      field(panel, 'Level', select);
    }

    if (['paragraph', 'heading', 'button', 'image'].includes(block.type)) {
      const select = document.createElement('select');
      ['left', 'center', 'right', 'wide', 'full'].forEach((align) => {
        const opt = document.createElement('option');
        opt.value = align;
        opt.textContent = align;
        opt.selected = (block.attrs.align || 'left') === align;
        select.appendChild(opt);
      });
      select.addEventListener('change', () => {
        block.attrs.align = select.value;
        syncHidden();
        render();
      });
      field(panel, 'Alignment', select);
    }

    if (block.type === 'image') {
      const alt = document.createElement('input');
      alt.value = block.attrs.alt || '';
      alt.addEventListener('input', () => {
        block.attrs.alt = alt.value;
        syncHidden();
      });
      field(panel, 'Alt text', alt);

      const src = document.createElement('input');
      src.value = block.attrs.src || '';
      src.addEventListener('input', () => {
        block.attrs.src = src.value;
        syncHidden();
        renderCanvasOnly();
      });
      field(panel, 'URL', src);

      const pick = document.createElement('button');
      pick.type = 'button';
      pick.className = 'btn btn-sm btn-outline-secondary';
      pick.textContent = 'Open Media Library';
      pick.addEventListener('click', () => {
        openMediaPicker((item) => {
          block.attrs.src = item.url || '';
          block.attrs.alt = item.alt || item.filename || '';
          syncHidden();
          render();
        });
      });
      panel.appendChild(pick);
    }

    if (['video', 'audio', 'embed'].includes(block.type)) {
      const src = document.createElement('input');
      src.value = block.attrs.src || '';
      src.addEventListener('input', () => {
        block.attrs.src = src.value;
        syncHidden();
        render();
      });
      field(panel, 'Source URL', src);
    }

    if (block.type === 'button') {
      ['label', 'url'].forEach((key) => {
        const input = document.createElement('input');
        input.value = block.attrs[key] || '';
        input.addEventListener('input', () => {
          block.attrs[key] = input.value;
          syncHidden();
          renderCanvasOnly();
        });
        field(panel, key === 'label' ? 'Label' : 'URL', input);
      });
    }

    if (block.type === 'list') {
      const ordered = document.createElement('select');
      [['false', 'Unordered'], ['true', 'Ordered']].forEach(([v, label]) => {
        const opt = document.createElement('option');
        opt.value = v;
        opt.textContent = label;
        opt.selected = String(!!block.attrs.ordered) === v;
        ordered.appendChild(opt);
      });
      ordered.addEventListener('change', () => {
        block.attrs.ordered = ordered.value === 'true';
        syncHidden();
        render();
      });
      field(panel, 'List style', ordered);
    }

    if (block.type === 'spacer') {
      const height = document.createElement('input');
      height.type = 'number';
      height.min = '8';
      height.value = String(block.attrs.height ?? 24);
      height.addEventListener('input', () => {
        block.attrs.height = Number(height.value) || 24;
        syncHidden();
        render();
      });
      field(panel, 'Height (px)', height);
    }

    if (block.type === 'columns') {
      const cols = document.createElement('input');
      cols.type = 'number';
      cols.min = '2';
      cols.max = '4';
      cols.value = String(block.attrs.columns || block.innerBlocks.length || 2);
      cols.addEventListener('change', () => {
        const n = Math.min(4, Math.max(2, Number(cols.value) || 2));
        block.attrs.columns = n;
        while (block.innerBlocks.length < n) {
          block.innerBlocks.push(defaultBlock('paragraph'));
        }
        while (block.innerBlocks.length > n) {
          block.innerBlocks.pop();
        }
        syncHidden();
        render();
      });
      field(panel, 'Columns', cols);
    }

    if (['paragraph', 'heading', 'quote', 'code', 'html'].includes(block.type)) {
      const transforms = document.createElement('select');
      const options = [
        ['', 'Transform to…'],
        ['paragraph', 'Paragraph'],
        ['heading', 'Heading'],
        ['quote', 'Quote'],
        ['list', 'List'],
        ['code', 'Code'],
      ];
      options.forEach(([v, label]) => {
        const opt = document.createElement('option');
        opt.value = v;
        opt.textContent = label;
        transforms.appendChild(opt);
      });
      transforms.addEventListener('change', () => {
        if (transforms.value) transformSelected(transforms.value);
      });
      field(panel, 'Transform', transforms);
    }
  }

  function bindRichText(el, block, multiline = false) {
    el.contentEditable = 'true';
    el.dataset.placeholder = multiline
      ? 'Write, or type / for blocks…'
      : 'Write…';
    el.innerText = block.content || '';
    el.addEventListener('input', () => {
      block.content = el.innerText;
      syncHidden();
      maybeSlash(el, block);
    });
    el.addEventListener('keydown', (e) => {
      if (e.key === 'Enter' && !e.shiftKey && block.type === 'paragraph') {
        e.preventDefault();
        const hit = findBlock(block.id);
        if (!hit) return;
        insertAt(hit.index + 1, 'paragraph', hit.list);
      }
      if (e.key === 'Backspace' && !block.content) {
        e.preventDefault();
        selectedId = block.id;
        removeSelected();
      }
      if ((e.metaKey || e.ctrlKey) && e.key.toLowerCase() === 'b') {
        e.preventDefault();
        document.execCommand('bold');
      }
      if ((e.metaKey || e.ctrlKey) && e.key.toLowerCase() === 'i') {
        e.preventDefault();
        document.execCommand('italic');
      }
      if ((e.metaKey || e.ctrlKey) && e.key.toLowerCase() === 'k') {
        e.preventDefault();
        const url = window.prompt('Link URL');
        if (url) document.execCommand('createLink', false, url);
      }
    });
    el.addEventListener('focus', () => select(block.id));
  }

  function maybeSlash(el, block) {
    const text = el.innerText || '';
    if (text.startsWith('/')) {
      const hit = findBlock(block.id);
      if (!hit) return;
      inserter = {
        index: hit.index,
        filter: text.slice(1),
        active: 0,
        replaceId: block.id,
        anchorEl: el,
      };
      renderInserterOnly();
    }
  }

  function renderBlockNode(block, list) {
    const card = document.createElement('div');
    card.className = `lp-block lp-block--${block.type}${selectedId === block.id ? ' is-selected' : ''}`;
    card.dataset.blockId = block.id;
    card.draggable = true;
    card.addEventListener('dragstart', (e) => {
      dragId = block.id;
      card.classList.add('is-dragging');
      e.dataTransfer.effectAllowed = 'move';
    });
    card.addEventListener('dragend', () => {
      dragId = null;
      card.classList.remove('is-dragging');
    });
    card.addEventListener('dragover', (e) => {
      e.preventDefault();
    });
    card.addEventListener('drop', (e) => {
      e.preventDefault();
      if (!dragId || dragId === block.id) return;
      const from = findBlock(dragId);
      const to = findBlock(block.id);
      if (!from || !to || from.list !== to.list) return;
      const [moved] = from.list.splice(from.index, 1);
      const target = findBlock(block.id);
      target.list.splice(target.index, 0, moved);
      selectedId = moved.id;
      syncHidden();
      render();
    });
    card.addEventListener('click', (e) => {
      e.stopPropagation();
      select(block.id);
    });

    const toolbar = document.createElement('div');
    toolbar.className = 'lp-block__toolbar';
    [
      ['↑', 'Move up', () => { selectedId = block.id; moveSelected(-1); }],
      ['↓', 'Move down', () => { selectedId = block.id; moveSelected(1); }],
      ['Duplicate', 'Duplicate block', () => { selectedId = block.id; duplicateSelected(); }],
      ['B', 'Bold', () => document.execCommand('bold')],
      ['I', 'Italic', () => document.execCommand('italic')],
      ['Link', 'Add link', () => {
        const url = window.prompt('Link URL');
        if (url) document.execCommand('createLink', false, url);
      }],
      ['Delete', 'Delete block', () => { selectedId = block.id; removeSelected(); }],
    ].forEach(([label, title, fn]) => {
      const btn = document.createElement('button');
      btn.type = 'button';
      btn.textContent = label;
      btn.title = title;
      btn.setAttribute('aria-label', title);
      if (label === 'Delete') btn.classList.add('is-danger');
      btn.addEventListener('click', (e) => {
        e.stopPropagation();
        fn();
      });
      toolbar.appendChild(btn);
    });
    card.appendChild(toolbar);

    const body = document.createElement('div');
    body.className = `lp-block__body lp-align-${block.attrs?.align || 'left'}`;

    if (['paragraph', 'heading', 'quote'].includes(block.type)) {
      const tag = block.type === 'heading'
        ? `h${Math.min(6, Math.max(1, Number(block.attrs.level) || 2))}`
        : (block.type === 'quote' ? 'blockquote' : 'p');
      const el = document.createElement(tag);
      if (block.type === 'quote') {
        const p = document.createElement('p');
        el.appendChild(p);
        bindRichText(p, block, true);
      } else {
        bindRichText(el, block, block.type === 'paragraph');
      }
      body.appendChild(el);
    } else if (block.type === 'list') {
      const tag = block.attrs.ordered ? 'ol' : 'ul';
      const listEl = document.createElement(tag);
      (block.attrs.items || ['']).forEach((item, idx) => {
        const li = document.createElement('li');
        li.contentEditable = 'true';
        li.textContent = item;
        li.addEventListener('input', () => {
          block.attrs.items[idx] = li.innerText;
          syncHidden();
        });
        li.addEventListener('keydown', (e) => {
          if (e.key === 'Enter') {
            e.preventDefault();
            block.attrs.items.splice(idx + 1, 0, '');
            syncHidden();
            render();
          }
        });
        listEl.appendChild(li);
      });
      body.appendChild(listEl);
    } else if (block.type === 'image') {
      if (block.attrs.src) {
        const figure = document.createElement('figure');
        const img = document.createElement('img');
        img.src = block.attrs.src;
        img.alt = block.attrs.alt || '';
        figure.appendChild(img);
        body.appendChild(figure);
      } else {
        const ph = document.createElement('div');
        ph.className = 'lp-placeholder';
        ph.textContent = 'Select an image from the inspector or media library.';
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.textContent = 'Media Library';
        btn.addEventListener('click', (e) => {
          e.stopPropagation();
          openMediaPicker((item) => {
            block.attrs.src = item.url || '';
            block.attrs.alt = item.alt || item.filename || '';
            syncHidden();
            render();
          });
        });
        ph.appendChild(document.createElement('br'));
        ph.appendChild(btn);
        body.appendChild(ph);
      }
    } else if (block.type === 'gallery') {
      const area = document.createElement('textarea');
      area.className = 'form-control';
      area.rows = 3;
      area.placeholder = 'One image URL per line';
      area.value = (block.attrs.images || []).map((i) => i.src || '').join('\n');
      area.addEventListener('input', () => {
        block.attrs.images = area.value.split(/\r?\n/).filter(Boolean).map((src) => ({ src, alt: '' }));
        syncHidden();
      });
      body.appendChild(area);
      const pick = document.createElement('button');
      pick.type = 'button';
      pick.textContent = 'Add from Media Library';
      pick.addEventListener('click', () => {
        openMediaPicker((item) => {
          block.attrs.images = [...(block.attrs.images || []), { src: item.url, alt: item.alt || '' }];
          syncHidden();
          render();
        });
      });
      body.appendChild(pick);
    } else if (['video', 'audio', 'embed'].includes(block.type)) {
      if (block.attrs.src) {
        if (block.type === 'video') {
          const v = document.createElement('video');
          v.controls = true;
          v.src = block.attrs.src;
          body.appendChild(v);
        } else if (block.type === 'audio') {
          const a = document.createElement('audio');
          a.controls = true;
          a.src = block.attrs.src;
          body.appendChild(a);
        } else {
          const wrap = document.createElement('div');
          wrap.className = 'cms-embed';
          wrap.innerHTML = `<iframe src="${escapeHtml(block.attrs.src)}" loading="lazy" title="Embed"></iframe>`;
          body.appendChild(wrap);
        }
      } else {
        const ph = document.createElement('div');
        ph.className = 'lp-placeholder';
        ph.textContent = `Add a ${block.type} URL in the inspector.`;
        body.appendChild(ph);
      }
    } else if (block.type === 'button') {
      const a = document.createElement('a');
      a.className = 'cms-button';
      a.href = block.attrs.url || '#';
      a.textContent = block.attrs.label || 'Learn more';
      a.addEventListener('click', (e) => e.preventDefault());
      body.appendChild(a);
    } else if (block.type === 'columns') {
      const grid = document.createElement('div');
      grid.className = 'lp-columns';
      grid.style.setProperty('--lp-cols', String(block.attrs.columns || block.innerBlocks.length || 2));
      (block.innerBlocks || []).forEach((inner) => {
        const col = document.createElement('div');
        col.className = 'lp-column';
        col.appendChild(renderBlockNode(inner, block.innerBlocks));
        grid.appendChild(col);
      });
      body.appendChild(grid);
    } else if (block.type === 'separator') {
      body.appendChild(document.createElement('hr'));
    } else if (block.type === 'spacer') {
      const spacer = document.createElement('div');
      spacer.style.height = `${Number(block.attrs.height) || 24}px`;
      body.appendChild(spacer);
    } else if (['code', 'html'].includes(block.type)) {
      const area = document.createElement('textarea');
      area.className = 'form-control';
      area.rows = 5;
      area.value = block.content || '';
      area.addEventListener('input', () => {
        block.content = area.value;
        syncHidden();
      });
      body.appendChild(area);
    }

    card.appendChild(body);
    return card;
  }

  function renderInserterOnly() {
    const existing = shell.querySelector('.lp-inserter');
    if (existing) existing.remove();
    if (!inserter) return;

    const panel = document.createElement('div');
    panel.className = 'lp-inserter';
    const search = document.createElement('input');
    search.className = 'lp-inserter__search';
    search.placeholder = 'Search blocks';
    search.value = inserter.filter;
    search.addEventListener('input', () => {
      inserter.filter = search.value;
      inserter.active = 0;
      renderInserterOnly();
      shell.querySelector('.lp-inserter__search')?.focus();
    });
    search.addEventListener('keydown', (e) => {
      const types = filteredTypes();
      if (e.key === 'ArrowDown') {
        e.preventDefault();
        inserter.active = Math.min(types.length - 1, inserter.active + 1);
        renderInserterOnly();
        shell.querySelector('.lp-inserter__search')?.focus();
      } else if (e.key === 'ArrowUp') {
        e.preventDefault();
        inserter.active = Math.max(0, inserter.active - 1);
        renderInserterOnly();
        shell.querySelector('.lp-inserter__search')?.focus();
      } else if (e.key === 'Enter') {
        e.preventDefault();
        const pick = types[inserter.active];
        if (pick) chooseInserter(pick.type);
      } else if (e.key === 'Escape') {
        closeInserter();
      }
    });
    panel.appendChild(search);

    const grid = document.createElement('div');
    grid.className = 'lp-inserter__grid';
    filteredTypes().forEach((t, i) => {
      const btn = document.createElement('button');
      btn.type = 'button';
      btn.textContent = t.label;
      if (i === inserter.active) btn.classList.add('is-active');
      btn.addEventListener('click', () => chooseInserter(t.type));
      grid.appendChild(btn);
    });
    panel.appendChild(grid);

    const canvas = shell.querySelector('.lp-editor__canvas');
    if (canvas) {
      canvas.style.position = 'relative';
      canvas.appendChild(panel);
      if (inserter.anchorEl) {
        const rect = inserter.anchorEl.getBoundingClientRect();
        const canvasRect = canvas.getBoundingClientRect();
        panel.style.top = `${rect.bottom - canvasRect.top + 8}px`;
        panel.style.left = `${Math.max(8, rect.left - canvasRect.left)}px`;
      } else {
        panel.style.top = '1rem';
        panel.style.left = '1rem';
      }
    }
    queueMicrotask(() => search.focus());
  }

  function chooseInserter(type) {
    if (inserter?.replaceId) {
      const hit = findBlock(inserter.replaceId);
      if (hit) {
        const next = defaultBlock(type);
        hit.list[hit.index] = next;
        selectedId = next.id;
        inserter = null;
        syncHidden();
        render();
        focusSelectedEditable();
        return;
      }
    }
    insertAt(inserter?.index ?? state.length, type);
  }

  function renderCanvasOnly() {
    if (!canvasEl) return;
    render();
  }

  function renderEmptyState(canvas) {
    const empty = document.createElement('div');
    empty.className = 'lp-empty';

    const title = document.createElement('p');
    title.className = 'lp-empty__title';
    title.textContent = 'Start writing';
    empty.appendChild(title);

    const help = document.createElement('p');
    help.className = 'lp-empty__help';
    help.textContent = 'Click a block below, or press + Add block. You can also type / inside a paragraph.';
    empty.appendChild(help);

    const quick = document.createElement('div');
    quick.className = 'lp-empty__quick';
    [
      ['paragraph', 'Paragraph'],
      ['heading', 'Heading'],
      ['image', 'Image'],
      ['list', 'List'],
      ['quote', 'Quote'],
    ].forEach(([type, label]) => {
      const btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'lp-empty__chip';
      btn.textContent = label;
      btn.addEventListener('click', () => insertAt(0, type));
      quick.appendChild(btn);
    });
    empty.appendChild(quick);

    const write = document.createElement('button');
    write.type = 'button';
    write.className = 'lp-empty__write';
    write.textContent = 'Start with a paragraph';
    write.addEventListener('click', () => insertAt(0, 'paragraph'));
    empty.appendChild(write);

    canvas.appendChild(empty);
  }

  function render() {
    const savedInserter = inserter;
    shell.innerHTML = '';

    const chrome = document.createElement('div');
    chrome.className = 'lp-editor__chrome';
    chrome.innerHTML = `
      <div class="lp-editor__chrome-left"></div>
      <div class="lp-editor__chrome-right"></div>
    `;
    const left = chrome.querySelector('.lp-editor__chrome-left');
    const right = chrome.querySelector('.lp-editor__chrome-right');

    const addBtn = document.createElement('button');
    addBtn.type = 'button';
    addBtn.className = 'lp-editor__add';
    addBtn.textContent = '+ Add block';
    addBtn.addEventListener('click', () => openInserter(state.length, addBtn));
    left.appendChild(addBtn);

    const hint = document.createElement('span');
    hint.className = 'lp-editor__hint';
    hint.textContent = 'Enter = new paragraph · / = block menu · drag handle area to reorder';
    left.appendChild(hint);

    const settingsBtn = document.createElement('button');
    settingsBtn.type = 'button';
    settingsBtn.className = inspectorOpen ? 'is-active' : '';
    settingsBtn.textContent = inspectorOpen ? 'Hide settings' : 'Block settings';
    settingsBtn.title = 'Toggle block settings sidebar';
    settingsBtn.addEventListener('click', () => {
      inspectorOpen = !inspectorOpen;
      render();
    });
    right.appendChild(settingsBtn);

    const previewToggle = document.createElement('button');
    previewToggle.type = 'button';
    previewToggle.textContent = mode === 'edit' ? 'Preview' : 'Back to editor';
    previewToggle.addEventListener('click', () => {
      mode = mode === 'edit' ? 'preview' : 'edit';
      render();
    });
    right.appendChild(previewToggle);

    shell.appendChild(chrome);

    layoutEl = document.createElement('div');
    layoutEl.className = `lp-editor__layout${mode === 'preview' ? ' is-preview-only' : ''}${inspectorOpen ? '' : ' is-inspector-closed'}`;

    canvasEl = document.createElement('div');
    canvasEl.className = `lp-editor__canvas${mode === 'preview' ? ' is-hidden' : ''}`;
    if (!state.length) {
      renderEmptyState(canvasEl);
    } else {
      state.forEach((block) => canvasEl.appendChild(renderBlockNode(block, state)));
    }

    const appender = document.createElement('div');
    appender.className = 'lp-appender';
    const plus = document.createElement('button');
    plus.type = 'button';
    plus.textContent = '+';
    plus.title = 'Add block';
    plus.addEventListener('click', () => openInserter(state.length, plus));
    appender.appendChild(plus);
    const appendLabel = document.createElement('span');
    appendLabel.textContent = 'Add block';
    appender.appendChild(appendLabel);
    canvasEl.appendChild(appender);

    previewEl = document.createElement('div');
    previewEl.className = `lp-editor__preview${mode === 'edit' ? ' is-hidden' : ''}`;
    previewEl.innerHTML = renderPreviewHtml(state) || '<p class="text-muted">Nothing to preview yet.</p>';

    inspectorEl = document.createElement('aside');
    inspectorEl.className = 'lp-editor__inspector';
    if (inspectorOpen) {
      renderInspector(inspectorEl);
    }

    layoutEl.appendChild(canvasEl);
    layoutEl.appendChild(previewEl);
    if (inspectorOpen) layoutEl.appendChild(inspectorEl);
    shell.appendChild(layoutEl);

    inserter = savedInserter;
    if (inserter) renderInserterOnly();
  }

  document.addEventListener('keydown', (e) => {
    if (!root.contains(document.activeElement) && document.activeElement !== document.body) return;
    if ((e.metaKey || e.ctrlKey) && e.shiftKey && e.key.toLowerCase() === 'd') {
      e.preventDefault();
      duplicateSelected();
    }
    if (e.key === 'Delete' && selectedId && document.activeElement === document.body) {
      removeSelected();
    }
  });

  if (!state.length) {
    // friendly empty canvas — user picks a starter block
  }
  syncHidden();
  render();

  return {
    getBlocks: () => state,
    setBlocks: (blocks) => {
      state = ensureIds(blocks);
      selectedId = state[0]?.id || null;
      syncHidden();
      render();
    },
  };
}
window.LaravelPress = window.LaravelPress || {};
window.LaravelPress.bindBlockEditor = bindBlockEditor;
document.addEventListener('DOMContentLoaded', function () {
  var root = document.getElementById('block-editor-root');
  var hidden = document.getElementById('blocks_json');
  if (!root || !hidden || root.dataset.bound === '1') return;
  root.dataset.bound = '1';
  var initial = [];
  try { initial = JSON.parse(hidden.value || '[]'); } catch (e) { initial = []; }
  bindBlockEditor(root, {
    hiddenInput: hidden,
    initialBlocks: Array.isArray(initial) ? initial : [],
    mediaJsonUrl: root.dataset.mediaJsonUrl || '/admin/media/json'
  });
});
})();

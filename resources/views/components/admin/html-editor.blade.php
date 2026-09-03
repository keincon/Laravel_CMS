@props([
    'name' => 'content',
    'value' => '',
    'rows' => 14,
    'label' => 'Content (HTML)',
])

@php $id = 'html-editor-'.\Illuminate\Support\Str::slug($name).'-'.uniqid(); @endphp

<div class="html-editor" x-data="{
    insert(tag) {
        const el = this.$refs.area;
        const start = el.selectionStart;
        const end = el.selectionEnd;
        const selected = el.value.substring(start, end) || 'text';
        let snippet = '';
        if (tag === 'p') snippet = `<p>${selected}</p>`;
        else if (tag === 'h2') snippet = `<h2>${selected}</h2>`;
        else if (tag === 'h3') snippet = `<h3>${selected}</h3>`;
        else if (tag === 'a') snippet = `<a href=\"https://\">${selected}</a>`;
        else if (tag === 'img') snippet = `<img src=\"\" alt=\"${selected}\" />`;
        else if (tag === 'ul') snippet = `<ul>\n  <li>${selected}</li>\n</ul>`;
        else if (tag === 'code') snippet = `<pre><code>${selected}</code></pre>`;
        else if (tag === 'div') snippet = `<div class=\"\">${selected}</div>`;
        else if (tag === 'section') snippet = `<section>\n  ${selected}\n</section>`;
        else snippet = selected;
        el.value = el.value.substring(0, start) + snippet + el.value.substring(end);
        el.focus();
        el.dispatchEvent(new Event('input'));
    }
}">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
        <label class="form-label mb-0" for="{{ $id }}">{{ $label }}</label>
        <div class="html-toolbar">
            <button type="button" class="btn btn-sm btn-outline-secondary" @click="insert('p')">P</button>
            <button type="button" class="btn btn-sm btn-outline-secondary" @click="insert('h2')">H2</button>
            <button type="button" class="btn btn-sm btn-outline-secondary" @click="insert('h3')">H3</button>
            <button type="button" class="btn btn-sm btn-outline-secondary" @click="insert('a')">Link</button>
            <button type="button" class="btn btn-sm btn-outline-secondary" @click="insert('img')">Img</button>
            <button type="button" class="btn btn-sm btn-outline-secondary" @click="insert('ul')">List</button>
            <button type="button" class="btn btn-sm btn-outline-secondary" @click="insert('div')">Div</button>
            <button type="button" class="btn btn-sm btn-outline-secondary" @click="insert('section')">Section</button>
            <button type="button" class="btn btn-sm btn-outline-secondary" @click="insert('code')">Code</button>
        </div>
    </div>
    <textarea
        id="{{ $id }}"
        name="{{ $name }}"
        class="form-control code-editor"
        rows="{{ $rows }}"
        x-ref="area"
        spellcheck="false"
        placeholder="Write HTML here…"
    >{{ $value }}</textarea>
    <p class="small page-intro mt-1 mb-0">HTML is rendered on the front end. You can paste full HTML blocks freely.</p>
</div>

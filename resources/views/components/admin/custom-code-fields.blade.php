@props(['model'])

<div class="panel mb-3" x-data="{ tab: 'html' }">
    <h2 class="h6 mb-2">Custom Code</h2>
    <p class="page-intro mb-3">Optional HTML / CSS / JS for this entry only.</p>
    <div class="users-role-tabs mb-3">
        <a href="#" :class="{ 'is-active': tab === 'html' }" @click.prevent="tab='html'">HTML</a>
        <a href="#" :class="{ 'is-active': tab === 'css' }" @click.prevent="tab='css'">CSS</a>
        <a href="#" :class="{ 'is-active': tab === 'js' }" @click.prevent="tab='js'">JS</a>
    </div>
    <div x-show="tab === 'html'">
        <textarea name="custom_html" class="code-editor" rows="8" spellcheck="false" placeholder="&lt;div class=&quot;notice&quot;&gt;Hello&lt;/div&gt;">{{ old('custom_html', $model->custom_html) }}</textarea>
    </div>
    <div x-show="tab === 'css'" x-cloak>
        <textarea name="custom_css" class="code-editor" rows="8" spellcheck="false" placeholder=".notice { padding: 1rem; }">{{ old('custom_css', $model->custom_css) }}</textarea>
    </div>
    <div x-show="tab === 'js'" x-cloak>
        <textarea name="custom_js" class="code-editor" rows="8" spellcheck="false" placeholder="console.log('ready');">{{ old('custom_js', $model->custom_js) }}</textarea>
        <p class="small page-intro mt-1 mb-0">Raw JavaScript (without &lt;script&gt; tags). Injected before &lt;/body&gt;.</p>
    </div>
</div>

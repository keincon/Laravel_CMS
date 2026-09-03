@props(['post', 'comments'])

@php
    $enabled = \App\Models\CmsSetting::getValue('comments_enabled', true);
@endphp

<section class="cms-comments" style="margin-top:3rem;padding-top:1.5rem;border-top:1px solid var(--color-border,#e2e8f0)">
    <h2 style="font-size:1.2rem;margin-bottom:1rem">
        {{ $comments->count() }} {{ \Illuminate\Support\Str::plural('Comment', $comments->count()) }}
    </h2>

    @if (session('success'))
        <p style="color:var(--color-primary,#2563eb)">{{ session('success') }}</p>
    @endif

    @forelse ($comments as $comment)
        <article style="margin-bottom:1.25rem;padding:1rem;border:1px solid var(--color-border,#e2e8f0);border-radius:.75rem;background:var(--color-surface,#fff)">
            <header style="display:flex;justify-content:space-between;gap:1rem;margin-bottom:.5rem">
                <strong>{{ $comment->displayName() }}</strong>
                <time style="opacity:.7;font-size:.85rem">{{ $comment->created_at?->toFormattedDateString() }}</time>
            </header>
            <div>{!! nl2br(e($comment->content)) !!}</div>
            @foreach ($comment->children as $child)
                <article style="margin:.85rem 0 0 1rem;padding:.85rem;border-left:3px solid var(--color-primary,#2563eb)">
                    <strong>{{ $child->displayName() }}</strong>
                    <div style="margin-top:.35rem">{!! nl2br(e($child->content)) !!}</div>
                </article>
            @endforeach
        </article>
    @empty
        <p style="opacity:.75">No comments yet.</p>
    @endforelse

    @if ($enabled && $post->isCommentsOpen())
        <div style="margin-top:1.5rem">
            <h3 style="font-size:1.05rem;margin-bottom:.75rem">Leave a Reply</h3>
            <form method="POST" action="{{ $post instanceof \App\Models\Content ? route('comments.store.content', $post) : route('comments.store', $post) }}">
                @csrf
                @guest
                    <div style="display:grid;gap:.65rem;margin-bottom:.75rem">
                        <input name="author_name" required placeholder="Name *" value="{{ old('author_name') }}"
                               style="padding:.55rem .75rem;border:1px solid var(--color-border,#cbd5e1);border-radius:.5rem;background:var(--color-elevated,#fff);color:var(--color-text)">
                        <input type="email" name="author_email" required placeholder="Email *" value="{{ old('author_email') }}"
                               style="padding:.55rem .75rem;border:1px solid var(--color-border,#cbd5e1);border-radius:.5rem;background:var(--color-elevated,#fff);color:var(--color-text)">
                        <input type="url" name="author_url" placeholder="Website" value="{{ old('author_url') }}"
                               style="padding:.55rem .75rem;border:1px solid var(--color-border,#cbd5e1);border-radius:.5rem;background:var(--color-elevated,#fff);color:var(--color-text)">
                    </div>
                @endguest
                <textarea name="content" required rows="5" placeholder="Comment *"
                          style="width:100%;padding:.75rem;border:1px solid var(--color-border,#cbd5e1);border-radius:.5rem;background:var(--color-elevated,#fff);color:var(--color-text)">{{ old('content') }}</textarea>
                @error('content')<div style="color:#b91c1c;font-size:.85rem">{{ $message }}</div>@enderror
                <button type="submit" style="margin-top:.75rem;padding:.55rem 1rem;border:0;border-radius:.5rem;background:var(--color-primary,#2563eb);color:#fff;font-weight:650;cursor:pointer">
                    Post Comment
                </button>
            </form>
        </div>
    @elseif (! $enabled)
        <p style="opacity:.75;margin-top:1rem">Comments are disabled.</p>
    @else
        <p style="opacity:.75;margin-top:1rem">Comments are closed.</p>
    @endif
</section>

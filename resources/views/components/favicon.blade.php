@php
    $mediaId = \App\Models\CmsSetting::getValue('site_favicon_media_id');
    $media = $mediaId ? \App\Models\Media::query()->find($mediaId) : null;
    // Root-relative avoids hanging on wrong APP_URL host/port (Docker).
    $href = $media ? '/storage/'.ltrim((string) $media->path, '/') : null;
    $type = $media?->mime_type;
@endphp

@if ($href)
    <link rel="icon" href="{{ $href }}"@if($type) type="{{ $type }}"@endif>
    <link rel="apple-touch-icon" href="{{ $href }}">
@else
    <link rel="icon" href="{{ asset('favicon.ico') }}">
@endif

<x-layout.master :page="$page ?? null" context="home">
    <h1>{{ $page?->title ?? \App\Models\CmsSetting::getValue('site_name', 'My Website') }}</h1>
    <div>{!! $page?->content ?? '<p>'.e(\App\Models\CmsSetting::getValue('site_description', 'Welcome.')).'</p>' !!}</div>
</x-layout.master>

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\GenerateMediaVariants;
use App\Models\Media;
use App\Services\Media\MediaUploadValidator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class MediaController extends Controller
{
    public function __construct(private readonly MediaUploadValidator $uploads) {}
    public function index(Request $request): View
    {
        $query = Media::query()->with('uploader')->latest();

        if ($search = trim((string) $request->get('q', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('filename', 'like', "%{$search}%")
                    ->orWhere('alt', 'like', "%{$search}%");
            });
        }

        return view('admin.media.index', [
            'media' => $query->paginate(24)->withQueryString(),
            'q' => $search ?? '',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'max:10240'],
            'alt' => ['nullable', 'string', 'max:255'],
        ]);

        $file = $request->file('file');
        $mime = $this->uploads->assertSafe($file);
        $path = $file->store('media/'.now()->format('Y/m'), 'public');

        $media = Media::query()->create([
            'disk' => 'public',
            'path' => $path,
            'filename' => $file->getClientOriginalName(),
            'mime_type' => $mime,
            'size' => $file->getSize() ?: 0,
            'alt' => $request->input('alt'),
            'uploaded_by' => Auth::id(),
        ]);

        GenerateMediaVariants::dispatch($media->id);

        return back()->with('success', 'File uploaded.');
    }

    public function update(Request $request, Media $medium): RedirectResponse
    {
        $data = $request->validate([
            'alt' => ['nullable', 'string', 'max:255'],
        ]);

        $medium->update($data);

        return back()->with('success', 'Media updated.');
    }

    public function destroy(Media $medium): RedirectResponse
    {
        if ($medium->disk && $medium->path) {
            Storage::disk($medium->disk)->delete($medium->path);
        }

        $medium->delete();

        return back()->with('success', 'Media deleted.');
    }

    public function json(Request $request): JsonResponse
    {
        $query = Media::query()->latest();
        if ($search = trim((string) $request->get('q', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('filename', 'like', "%{$search}%")
                    ->orWhere('alt', 'like', "%{$search}%");
            });
        }

        $items = $query->paginate(24);

        return response()->json([
            'data' => $items->getCollection()->map(fn (Media $m) => [
                'id' => $m->id,
                'filename' => $m->filename,
                'alt' => $m->alt,
                'mime_type' => $m->mime_type,
                'url' => $m->url(),
                'is_image' => str_starts_with((string) $m->mime_type, 'image/'),
            ]),
            'meta' => [
                'current_page' => $items->currentPage(),
                'last_page' => $items->lastPage(),
                'total' => $items->total(),
            ],
        ]);
    }

    public function storeJson(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'max:10240'],
            'alt' => ['nullable', 'string', 'max:255'],
        ]);

        $file = $request->file('file');
        $mime = $this->uploads->assertSafe($file);
        $path = $file->store('media/'.now()->format('Y/m'), 'public');

        $media = Media::query()->create([
            'disk' => 'public',
            'path' => $path,
            'filename' => $file->getClientOriginalName(),
            'mime_type' => $mime,
            'size' => $file->getSize() ?: 0,
            'alt' => $request->input('alt'),
            'uploaded_by' => Auth::id(),
        ]);

        GenerateMediaVariants::dispatch($media->id);

        return response()->json([
            'id' => $media->id,
            'filename' => $media->filename,
            'alt' => $media->alt,
            'mime_type' => $media->mime_type,
            'url' => $media->url(),
            'is_image' => str_starts_with((string) $media->mime_type, 'image/'),
        ], 201);
    }
}

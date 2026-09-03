<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CommentController extends Controller
{
    public function index(Request $request): View
    {
        $status = (string) $request->query('status', 'all');
        $q = trim((string) $request->query('q', ''));

        $counts = [
            'all' => Comment::query()->whereNotIn('status', ['trash', 'spam'])->count(),
            'pending' => Comment::query()->where('status', 'pending')->count(),
            'approved' => Comment::query()->where('status', 'approved')->count(),
            'spam' => Comment::query()->where('status', 'spam')->count(),
            'trash' => Comment::query()->where('status', 'trash')->count(),
        ];

        $comments = Comment::query()
            ->with(['post', 'user'])
            ->when($status === 'all', fn ($query) => $query->whereNotIn('status', ['trash', 'spam']))
            ->when($status !== 'all', fn ($query) => $query->where('status', $status))
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($inner) use ($q) {
                    $inner->where('content', 'like', '%'.$q.'%')
                        ->orWhere('author_name', 'like', '%'.$q.'%')
                        ->orWhere('author_email', 'like', '%'.$q.'%');
                });
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.comments.index', compact('comments', 'status', 'q', 'counts'));
    }

    public function update(Request $request, Comment $comment): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:pending,approved,spam,trash'],
        ]);

        $comment->update(['status' => $data['status']]);

        return back()->with('success', 'Comment updated.');
    }

    public function destroy(Comment $comment): RedirectResponse
    {
        if ($comment->status !== 'trash') {
            $comment->update(['status' => 'trash']);

            return back()->with('success', 'Comment moved to Trash.');
        }

        $comment->delete();

        return back()->with('success', 'Comment permanently deleted.');
    }

    public function bulk(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'action' => ['required', 'in:approve,unapprove,spam,trash,delete,restore'],
            'comments' => ['required', 'array', 'min:1'],
            'comments.*' => ['integer', 'exists:comments,id'],
        ]);

        $comments = Comment::query()->whereIn('id', $data['comments'])->get();

        foreach ($comments as $comment) {
            match ($data['action']) {
                'approve' => $comment->update(['status' => 'approved']),
                'unapprove' => $comment->update(['status' => 'pending']),
                'spam' => $comment->update(['status' => 'spam']),
                'trash' => $comment->update(['status' => 'trash']),
                'restore' => $comment->update(['status' => 'pending']),
                'delete' => $comment->delete(),
            };
        }

        return back()->with('success', 'Bulk action applied.');
    }
}

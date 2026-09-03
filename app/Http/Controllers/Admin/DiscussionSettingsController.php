<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CmsSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DiscussionSettingsController extends Controller
{
    public function edit(): View
    {
        return view('admin.settings.discussion', [
            'commentsEnabled' => (bool) CmsSetting::getValue('comments_enabled', true),
            'commentModeration' => (bool) CmsSetting::getValue('comment_moderation', true),
            'defaultCommentStatus' => CmsSetting::getValue('default_comment_status', 'open'),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'comments_enabled' => ['nullable', 'boolean'],
            'comment_moderation' => ['nullable', 'boolean'],
            'default_comment_status' => ['required', 'in:open,closed'],
        ]);

        CmsSetting::setValue('comments_enabled', $request->boolean('comments_enabled'), 'boolean');
        CmsSetting::setValue('comment_moderation', $request->boolean('comment_moderation'), 'boolean');
        CmsSetting::setValue('default_comment_status', $data['default_comment_status']);

        return back()->with('success', 'Discussion settings saved.');
    }
}

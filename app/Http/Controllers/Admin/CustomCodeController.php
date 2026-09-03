<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\CustomCodeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomCodeController extends Controller
{
    public function edit(CustomCodeService $code): View
    {
        return view('admin.appearance.custom-code', [
            'additionalCss' => $code->additionalCss(),
            'headerScripts' => $code->headerScripts(),
            'footerScripts' => $code->footerScripts(),
            'customHtmlHead' => $code->customHtmlHead(),
            'customHtmlBodyOpen' => $code->customHtmlBodyOpen(),
            'customHtmlBodyClose' => $code->customHtmlBodyClose(),
        ]);
    }

    public function update(Request $request, CustomCodeService $code): RedirectResponse
    {
        $data = $request->validate([
            'additional_css' => ['nullable', 'string'],
            'header_scripts' => ['nullable', 'string'],
            'footer_scripts' => ['nullable', 'string'],
            'custom_html_head' => ['nullable', 'string'],
            'custom_html_body_open' => ['nullable', 'string'],
            'custom_html_body_close' => ['nullable', 'string'],
        ]);

        $code->update($data);

        return back()->with('success', 'Custom HTML / CSS / JS saved.');
    }
}

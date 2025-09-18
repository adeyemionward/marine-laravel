<?php

namespace App\Http\Controllers\Api\Communication;

use App\Http\Controllers\Controller;
use App\Models\NewsletterTemplate;
use Illuminate\Http\Request;

class NewsLetterTemplateController extends Controller
{
    public function index()
    {
        return response()->json(NewsLetterTemplate::all());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'template_name'    => 'required|string|max:255',
            'description'      => 'nullable|string',
            'subject_template' => 'required|string|max:255',
            'html_template'    => 'required|string',
        ]);

        $template = NewsLetterTemplate::create($validated);

        return response()->json([
            'message' => 'Newsletter template created successfully',
            'data'    => $template
        ], 201);
    }

    public function show($id)
    {
        $template = NewsletterTemplate::findOrFail($id);
        return response()->json($template);
    }

    public function update(Request $request, $id)
    {
        $template = NewsletterTemplate::findOrFail($id);

        $validated = $request->validate([
            'template_name'    => 'required|string|max:255',
            'description'      => 'nullable|string',
            'subject_template' => 'required|string|max:255',
            'html_template'    => 'required|string',
        ]);

        $template->update($validated);

        return response()->json([
            'message' => 'Newsletter template updated successfully',
            'data'    => $template
        ]);
    }

    public function destroy($id)
    {
        $template = NewsLetterTemplate::findOrFail($id);
        $template->delete();

        return response()->json(['message' => 'Newsletter template deleted successfully']);
    }
}

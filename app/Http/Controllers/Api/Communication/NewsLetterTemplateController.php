<?php

namespace App\Http\Controllers\Api\Communication;

use App\Http\Controllers\Controller;
use App\Models\NewsletterTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class NewsLetterTemplateController extends Controller
{
    public function index(Request $request)
    {
        $query = NewsletterTemplate::query();

        if ($request->has('category')) {
            $query->where('category', $request->category);
        }

        if ($request->has('active')) {
            $query->where('is_active', $request->boolean('active'));
        }

        $templates = $query->orderBy('created_at', 'desc')->get();

        // Transform the data to match frontend expectations
        $transformedTemplates = $templates->map(function ($template) {
            return [
                'id' => $template->id,
                'name' => $template->template_name,
                'description' => $template->description,
                'content' => $template->html_template,
                'subject' => $template->subject_template,
                'category' => $template->category ?? 'general',
                'is_active' => $template->is_active ?? true,
                'created_at' => $template->created_at,
                'updated_at' => $template->updated_at,
            ];
        });

        return response()->json([
            'data' => $transformedTemplates,
            'success' => true
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'content' => 'required|string',
            'category' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        // Map frontend fields to database fields
        $templateData = [
            'template_name' => $validated['name'],
            'description' => $validated['description'] ?? '',
            'html_template' => $validated['content'],
            'subject_template' => $validated['name'], // Use name as default subject
        ];

        $template = NewsletterTemplate::create($templateData);

        return response()->json([
            'message' => 'Newsletter template created successfully',
            'data' => $template,
            'success' => true
        ], 201);
    }

    public function show($id)
    {
        $template = NewsletterTemplate::findOrFail($id);

        // Transform data to match frontend expectations
        $transformedTemplate = [
            'id' => $template->id,
            'name' => $template->template_name,
            'description' => $template->description,
            'content' => $template->html_template,
            'subject' => $template->subject_template,
            'category' => 'basic', // Default since not stored
            'is_active' => true, // Default since not stored
            'created_at' => $template->created_at,
            'updated_at' => $template->updated_at,
        ];

        return response()->json([
            'data' => $transformedTemplate,
            'success' => true
        ]);
    }

    public function update(Request $request, $id)
    {
        $template = NewsletterTemplate::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'content' => 'sometimes|string',
            'category' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        // Map frontend fields to database fields
        $updateData = [];
        if (isset($validated['name'])) {
            $updateData['template_name'] = $validated['name'];
            $updateData['subject_template'] = $validated['name']; // Update subject too
        }
        if (isset($validated['description'])) {
            $updateData['description'] = $validated['description'];
        }
        if (isset($validated['content'])) {
            $updateData['html_template'] = $validated['content'];
        }

        $template->update($updateData);

        return response()->json([
            'message' => 'Newsletter template updated successfully',
            'data' => $template,
            'success' => true
        ]);
    }

    public function destroy($id)
    {
        $template = NewsletterTemplate::findOrFail($id);

        // Check if template is being used by any newsletters
        if ($template->newsletters()->exists()) {
            return response()->json([
                'message' => 'Cannot delete template that is being used by newsletters'
            ], 422);
        }

        // Delete thumbnail if exists
        if ($template->thumbnail) {
            Storage::disk('public')->delete($template->thumbnail);
        }

        $template->delete();

        return response()->json([
            'message' => 'Newsletter template deleted successfully',
            'success' => true
        ]);
    }

    public function duplicate($id)
    {
        $template = NewsletterTemplate::findOrFail($id);

        $duplicate = NewsletterTemplate::create([
            'template_name' => $template->template_name . ' (Copy)',
            'description' => $template->description,
            'html_template' => $template->html_template,
            'subject_template' => $template->subject_template . ' (Copy)',
        ]);

        return response()->json([
            'message' => 'Newsletter template duplicated successfully',
            'data' => $duplicate,
            'success' => true
        ], 201);
    }

    public function preview($id)
    {
        $template = NewsletterTemplate::findOrFail($id);

        return response()->json([
            'data' => [
                'id' => $template->id,
                'name' => $template->template_name,
                'content' => $template->html_template,
                'subject' => $template->subject_template,
                'description' => $template->description,
            ],
            'success' => true
        ]);
    }
}

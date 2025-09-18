<?php

namespace App\Http\Controllers\Api\Communication;

use App\Http\Controllers\Controller;
use App\Models\Newsletter;
use Illuminate\Http\Request;

class NewsLetterController extends Controller
{
    public function index()
    {
        return response()->json(Newsletter::with('template')->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title'               => 'required|string|max:255',
            'template_id'         => 'nullable|exists:newsletter_templates,id',
            'use_default_template'=> 'boolean',
            'schedule_for'        => 'nullable|date',
        ]);

        $newsletter = Newsletter::create($validated);

        return response()->json([
            'message' => 'Newsletter created successfully',
            'data'    => $newsletter->load('template'),
        ], 201);
    }

    public function show($id)
    {
        $newsletter = Newsletter::with('template')->findOrFail($id);
        return response()->json($newsletter);
    }

    public function update(Request $request, $id)
    {
        $newsletter = Newsletter::findOrFail($id);

        $validated = $request->validate([
            'title'               => 'sometimes|string|max:255',
            'template_id'         => 'nullable|exists:newsletter_templates,id',
            'use_default_template'=> 'boolean',
            'schedule_for'        => 'nullable|date',
        ]);

        $newsletter->update($validated);

        return response()->json([
            'message' => 'Newsletter updated successfully',
            'data'    => $newsletter->load('template'),
        ]);
    }

    public function destroy($id)
    {
        $newsletter = Newsletter::findOrFail($id);
        $newsletter->delete();

        return response()->json(['message' => 'Newsletter deleted successfully']);
    }


}

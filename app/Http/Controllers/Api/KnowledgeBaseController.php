<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\KnowledgeBaseDocument;
use App\Models\KnowledgeBaseCategory;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class KnowledgeBaseController extends Controller
{
    public function index(Request $request)
    {
        try {
            $query = KnowledgeBaseDocument::with(['knowledgeBaseCategories', 'userProfiles'])
                ->published()
                ->ordered();

            if ($request->has('category')) {
                $query->byCategory($request->category);
            }

            if ($request->has('type')) {
                $query->byType($request->type);
            }

            if ($request->has('search')) {
                $query->search($request->search);
            }

            if ($request->has('featured')) {
                $query->featured();
            }

            $perPage = min($request->get('per_page', 12), 50);
            $documents = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $documents
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch knowledge base documents',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getBySlug($slug)
    {
        try {
            $document = KnowledgeBaseDocument::with(['knowledgeBaseCategories', 'userProfiles'])
                ->published()
                ->where('slug', $slug)
                ->first();

            if (!$document) {
                return response()->json([
                    'success' => false,
                    'message' => 'Document not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $document
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch document',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $document = KnowledgeBaseDocument::with(['knowledgeBaseCategories', 'userProfiles'])
                ->published()
                ->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $document
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Document not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    public function trackView($id)
    {
        try {
            $document = KnowledgeBaseDocument::findOrFail($id);
            $document->incrementViewCount();

            return response()->json([
                'success' => true,
                'message' => 'View tracked successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to track view',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getRelated($id)
    {
        try {
            $document = KnowledgeBaseDocument::findOrFail($id);
            $relatedDocuments = $document->getRelatedDocuments();

            return response()->json([
                'success' => true,
                'data' => $relatedDocuments
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch related documents',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function categories()
    {
        try {
            $categories = Cache::remember('knowledge_base_categories', 3600, function () {
                return KnowledgeBaseCategory::active()
                    ->ordered()
                    ->withCount(['publishedDocuments'])
                    ->get();
            });

            return response()->json([
                'success' => true,
                'data' => $categories
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch categories',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function featured()
    {
        try {
            $featuredDocuments = Cache::remember('knowledge_base_featured', 1800, function () {
                return KnowledgeBaseDocument::with(['knowledgeBaseCategories', 'userProfiles'])
                    ->published()
                    ->featured()
                    ->ordered()
                    ->limit(6)
                    ->get();
            });

            return response()->json([
                'success' => true,
                'data' => $featuredDocuments
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch featured documents',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function popular()
    {
        try {
            $popularDocuments = Cache::remember('knowledge_base_popular', 1800, function () {
                return KnowledgeBaseDocument::with(['knowledgeBaseCategories', 'userProfiles'])
                    ->published()
                    ->orderByDesc('view_count')
                    ->limit(6)
                    ->get();
            });

            return response()->json([
                'success' => true,
                'data' => $popularDocuments
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch popular documents',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function search(Request $request)
    {
        try {
            $term = $request->get('q', '');

            if (empty($term)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Search term is required'
                ], 400);
            }

            $documents = KnowledgeBaseDocument::with(['knowledgeBaseCategories', 'userProfiles'])
                ->published()
                ->search($term)
                ->ordered()
                ->paginate(15);

            return response()->json([
                'success' => true,
                'data' => $documents,
                'search_term' => $term
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Search failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Admin: Get all documents with admin permissions
     */
    public function indexAdmin(Request $request)
    {
        try {
            $query = KnowledgeBaseDocument::with(['category']);

            // Apply filters
            if ($request->has('category_id')) {
                $query->where('category_id', $request->category_id);
            }

            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                      ->orWhere('content', 'like', "%{$search}%");
                });
            }

            $documents = $query->orderBy('created_at', 'desc')
                              ->paginate($request->get('per_page', 15));

            return response()->json([
                'success' => true,
                'data' => $documents
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch documents',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Admin: Create new document
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'title' => 'required|string|max:255',
                'content' => 'required|string',
                'category_id' => 'nullable|exists:knowledge_base_categories,id',
                'tags' => 'nullable|array',
                'is_featured' => 'boolean',
                'published_at' => 'nullable|date'
            ]);

            // Use "Getting Started" category as default if none provided
            $defaultCategoryId = \App\Models\KnowledgeBaseCategory::where('name', 'Getting Started')->first()?->id ?? 1;

            $document = KnowledgeBaseDocument::create([
                'title' => $validated['title'],
                'slug' => \Str::slug($validated['title']),
                'content' => $validated['content'],
                'category_id' => $validated['category_id'] ?? $defaultCategoryId,
                'tags' => $validated['tags'] ?? [],
                'is_featured' => $validated['is_featured'] ?? false,
                'published_at' => $validated['published_at'] ?? null,
                'created_by' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Document created successfully',
                'data' => $document->load('category')
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create document',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Admin: Update document
     */
    public function update(Request $request, $id)
    {
        try {
            $document = KnowledgeBaseDocument::findOrFail($id);

            $validated = $request->validate([
                'title' => 'sometimes|string|max:255',
                'content' => 'sometimes|string',
                'category_id' => 'nullable|exists:knowledge_base_categories,id',
                'tags' => 'nullable|array',
                'is_featured' => 'boolean',
                'published_at' => 'nullable|date'
            ]);

            if (isset($validated['title'])) {
                $validated['slug'] = \Str::slug($validated['title']);
            }

            $document->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Document updated successfully',
                'data' => $document->load('category')
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update document',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Admin: Delete document
     */
    public function destroy($id)
    {
        try {
            $document = KnowledgeBaseDocument::findOrFail($id);
            $document->delete();

            return response()->json([
                'success' => true,
                'message' => 'Document deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete document',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Admin: Get categories with document counts
     */
    public function categoriesAdmin()
    {
        try {
            $categories = KnowledgeBaseCategory::withCount('documents')->get();

            return response()->json([
                'success' => true,
                'data' => $categories
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch categories',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Admin: Get statistics
     */
    public function statistics()
    {
        try {
            $stats = [
                'total_documents' => KnowledgeBaseDocument::count(),
                'published_documents' => KnowledgeBaseDocument::whereNotNull('published_at')->count(),
                'draft_documents' => KnowledgeBaseDocument::whereNull('published_at')->count(),
                'featured_documents' => KnowledgeBaseDocument::where('is_featured', true)->count(),
                'total_categories' => KnowledgeBaseCategory::count(),
                'total_views' => KnowledgeBaseDocument::sum('view_count')
            ];

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}

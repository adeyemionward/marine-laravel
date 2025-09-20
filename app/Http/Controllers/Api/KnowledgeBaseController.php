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
}

<?php

namespace App\Services\User;

use App\Models\InstagramPost;
use Illuminate\Http\Request;

class InstagramPostService
{
    /**
     * Get list of Instagram posts with pagination
     *
     * @param Request $request
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function list(Request $request)
    {
        $query = InstagramPost::query();

        // Optional filters
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        if ($request->has('owner_username')) {
            $query->where('owner_username', $request->owner_username);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('caption', 'like', "%{$search}%")
                  ->orWhere('owner_username', 'like', "%{$search}%")
                  ->orWhere('owner_full_name', 'like', "%{$search}%");
            });
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'timestamp');
        $sortOrder = $request->get('sort_order', 'desc');

        $allowedSortFields = ['timestamp', 'likes_count', 'comments_count', 'owner_username'];
        if (in_array($sortBy, $allowedSortFields)) {
            $query->orderBy($sortBy, $sortOrder);
        } else {
            $query->orderBy('timestamp', 'desc');
        }

        $perPage = $request->get('per_page', 100);
        $perPage = min($perPage, 100); // Maximum 50 items per page

        return $query->paginate($perPage);
    }

    /**
     * Get Instagram post detail by ID
     *
     * @param int $id
     * @return InstagramPost
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function detail($id)
    {
        return InstagramPost::findOrFail($id);
    }
}

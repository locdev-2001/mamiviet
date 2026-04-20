<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Resources\InstagramPostResource;
use App\Services\User\InstagramPostService;
use Illuminate\Http\Request;

class InstagramPostController extends Controller
{
    protected $service;

    public function __construct(InstagramPostService $service)
    {
        $this->service = $service;
    }

    /**
     * Display a listing of the Instagram posts.
     *
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index(Request $request)
    {
        $posts = $this->service->list($request);

        return InstagramPostResource::collection($posts);
    }

    /**
     * Display the specified Instagram post.
     *
     * @param  int  $id
     * @return InstagramPostResource
     */
    public function show($id)
    {
        $post = $this->service->detail($id);

        return new InstagramPostResource($post);
    }
}
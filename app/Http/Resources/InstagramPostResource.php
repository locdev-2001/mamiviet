<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InstagramPostResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'short_code' => $this->short_code,
            'caption' => $this->caption,
            'hashtags' => $this->hashtags,
            'mentions' => $this->mentions,
            'url' => $this->url,
            'comments_count' => $this->comments_count,
            'first_comment' => $this->first_comment,
            'latest_comments' => $this->latest_comments,
            'dimensions_height' => $this->dimensions_height,
            'dimensions_width' => $this->dimensions_width,
            'display_url' => $this->display_url,
            'images' => $this->images,
            'alt' => $this->alt,
            'likes_count' => $this->likes_count,
            'timestamp' => $this->timestamp,
            'child_posts' => $this->child_posts,
            'owner_full_name' => $this->owner_full_name,
            'owner_username' => $this->owner_username,
            'owner_id' => $this->owner_id,
            'is_comments_disabled' => $this->is_comments_disabled,
            'input_url' => $this->input_url,
            'is_sponsored' => $this->is_sponsored,
        ];
    }
}
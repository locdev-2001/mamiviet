<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InstagramPost extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'short_code', 
        'caption',
        'hashtags',
        'mentions',
        'url',
        'comments_count',
        'first_comment',
        'latest_comments',
        'dimensions_height',
        'dimensions_width',
        'display_url',
        'images',
        'alt',
        'likes_count',
        'timestamp',
        'child_posts',
        'owner_full_name',
        'owner_username', 
        'owner_id',
        'is_comments_disabled',
        'input_url',
        'is_sponsored'
    ];

    protected $casts = [
        'hashtags' => 'array',
        'mentions' => 'array',
        'latest_comments' => 'array',
        'images' => 'array',
        'child_posts' => 'array',
        'comments_count' => 'integer',
        'dimensions_height' => 'integer',
        'dimensions_width' => 'integer',
        'likes_count' => 'integer',
        'owner_id' => 'integer',
        'is_comments_disabled' => 'boolean',
        'is_sponsored' => 'boolean',
        'timestamp' => 'datetime'
    ];

    /**
     * Disable Laravel's default timestamps since we use custom timestamp field
     */
    public $timestamps = false;
}
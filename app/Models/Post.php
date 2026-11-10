<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\User;
use App\Models\PostImage;


class Post extends Model
{
     protected $fillable = [
        'user_id',
        'content',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(PostImage::class);
    }

    public function likes()
    {
        return $this->hasMany(PostLike::class);
    }
    public function comments()
    {
        return $this->hasMany(PostComment::class);
    }

}

<?php

namespace App\Models;

use Database\Factories\EventFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    /** @use HasFactory<EventFactory> */
    use HasFactory;

    protected $fillable = [
        'titre',
        'description',
        'date',
        'heure',
        'lieu',
        'category_id',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AppNotification extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'causer_id',
        'title',
        'message',
        'notifiable_type',
        'notifiable_id',
        'is_read',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function causer()
    {
        return $this->belongsTo(User::class, 'causer_id');
    }

    public function notifiable()
    {
        return $this->morphTo();
    }
}

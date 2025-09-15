<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Flag extends Model
{
    //
    public $timestamps = false;
    protected $fillable = ['key','label'];

    public function tickets()
    {
        return $this->belongsToMany(
            Ticket::class,
            'ticket_flags',
            'flag_id',
            'ticket_id'
        );
    }
}

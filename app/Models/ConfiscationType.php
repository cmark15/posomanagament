<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConfiscationType extends Model
{
    //
    public $timestamps = false;
    protected $fillable = ['name'];

    public function tickets()
    {
        return $this->hasMany(Ticket::class );
    }
}

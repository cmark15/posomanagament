<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReleasedVehicle extends Model
{
    //
    protected $fillable = ['ticket_id','reference_number','released_at'];

    protected $casts = [
        'released_at' => 'datetime',
      ];
  
    public function ticket()
    {
      return $this->belongsTo(Ticket::class);
    }

}

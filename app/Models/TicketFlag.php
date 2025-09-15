<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Pivot;
class TicketFlag extends Pivot
{
    //
    
    protected $fillable = ['ticket_id','flag_id'];
    protected $table = 'ticket_flags';
    public function tickets()
    {
        return $this->hasMany(Ticket::class, 'id');
    }
}

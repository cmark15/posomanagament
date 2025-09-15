<?php

namespace App\Models;
use App\Models\Ticket;

use Illuminate\Database\Eloquent\Model;

class PaidTicket extends Model
{
    //
    protected $table = 'paid_tickets';
    protected $fillable = [
        'ticket_id',
        'reference_number',
        'paid_at',
    ];

    public function ticket()
    {
        return $this->belongsTo(Ticket::class, 'ticket_id');
    }
}

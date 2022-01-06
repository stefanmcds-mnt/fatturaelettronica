<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class eFatturaNotifiche extends Model
{
    protected $primaryKey = 'id';
    public $timestamps = false;
    protected $table = 'efattura_notifiche';
    protected $fillable = [
        'invoice_id',
        'remote_id',
        'type',
        'status',
        'blob',
        'actor',
        'nomefile',
        'ctime'
    ];
}

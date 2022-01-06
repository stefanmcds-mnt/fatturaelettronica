<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class eFatturaCas extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'efattura_cas';

    /**
     * Primary Key
     *
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * Indicates if the model should be timestamped.
     *
     * use : true
     * don't use : false
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The storage format of the model's date columns.
     *
     * @var string
     */
    protected $dateFormat = '';

    /**
     * The connection name for the model.
     *
     * @var string
     */
    protected $connection = '';

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = [
        'deleted_at'
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'efattura_gen_id',
        'cassa_tipo',
        'cassa_aliquota',
        'importo',
        'aliquota_iva',
        'ritenuta',
        'natura',
        'riferimento'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [];
}

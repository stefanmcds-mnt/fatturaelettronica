<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class eFatturaRif extends Model
{

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'efattura_rif';

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
        'efattura_det_id',
        'tipo',
        'documento',
        'numero',
        'data',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [];

    /**
     * One to One relation
     *
     * @return \Illuminate\Database\Eloquent\Relations\hasOne
     */
    public function linea()
    {
        return $this->hasOne(eFattura_Det::class, 'id', 'efattura_det_id');
    }
}

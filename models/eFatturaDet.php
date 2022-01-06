<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class eFatturaDet extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'efattura_det';

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
        'nlinea',
        'descrizione',
        'qta',
        'um',
        'unitario',
        'aliquotaiva',
        'natura',
        'tipocessione',
        'ritenuta',
        'codice_tipo',
        'codice_valore',
        'altro_tipo',
        'altro_testo',
        'altro_numero',
        'altro_data'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [];

    /**
     * One to Many relation
     *
     * @return \Illuminate\Database\Eloquent\Relations\hasOne
     */
    public function scontomaggiorazione()
    {
        return $this->hasMany(eFattura_SM::class, 'efattura_det_id', 'id');
    }

    /**
     * One to Many relation
     *
     * @return \Illuminate\Database\Eloquent\Relations\hasOne
     */
    public function riferimenti()
    {
        return $this->hasMany(eFattura_Rif::class, 'efattura_det_id', 'id');
    }
}

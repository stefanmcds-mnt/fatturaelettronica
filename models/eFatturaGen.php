<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class eFatturaGen extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'efattura_gen';

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
        'prestatore_id',
        'committente_id',
        'vettore_id',
        'trasmissione',
        'tipo',
        'modalitapagamento',
        'causale',
        'numero',
        'anno',
        'data',
        'datascadenzapagamento',
        'imponibile',
        'imposta',
        'totale',
        'divisa',
        'condizionipagamento',
        'ritenuta_tipo',
        'ritenuta_importo',
        'ritenuta_aliquota',
        'ritenuta_causale',
        'bollo_tipo',
        'bollo_importo',
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
    public function getPrestatore()
    {
        return $this->hasOne(Anagrafica::class, 'id_anagrafica', 'prestatore_id');
    }

    /**
     * One to One relation
     *
     * @return \Illuminate\Database\Eloquent\Relations\hasOne
     */
    public function getCommittente()
    {
        return $this->hasOne(Anagrafica::class, 'id_anagrafica', 'committente_id');
    }

    /**
     * One to One relation
     *
     * @return \Illuminate\Database\Eloquent\Relations\hasOne
     */
    public function getVettore()
    {
        return $this->hasOne(Anagrafica::class, 'id_anagrafica', 'vettore_id');
    }

    /**
     * One to Many relation
     *
     * @return \Illuminate\Database\Eloquent\Relations\hasMany
     */
    public function getDettaglio()
    {
        return $this->hasMany(eFattura_Det::class, 'efattura_gen_id', 'id');
    }

    /**
     * One to Many relation
     *
     * @return \Illuminate\Database\Eloquent\Relations\hasMany
     */
    public function getRiepilogo()
    {
        return $this->hasMany(eFattura_IVA::class, 'efattura_gen_id', 'id');
    }

    /**
     * One to Many relation
     *
     * @return \Illuminate\Database\Eloquent\Relations\hasMany
     */
    public function getCassa()
    {
        //return $this->belongsTO(Customer::class, 'customer_id', 'id');
        return $this->hasMany(eFattura_Cas::class, 'efattura_gen_id', 'id');
    }

    /**
     * One to Many relation
     *
     * @return \Illuminate\Database\Eloquent\Relations\hasMany
     */
    public function getRiferimenti()
    {
        return $this->hasMany(eFattura_Rif::class, 'efattura_gen_id', 'id');
    }

    /**
     * One to Many relation
     *
     * @return \Illuminate\Database\Eloquent\Relations\hasMany
     */
    public function getPagamenti()
    {
        return $this->hasMany(StoricoPagamenti::class, 'fatture_id', 'id');
    }
}

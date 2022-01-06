<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class eFatturaAnagrafica extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'eFatturaAnagrafica';

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
        'professionista',
        'prestatore',
        'committente',
        'vettore',
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
        return $this->hasOne(Anagrafica::class, 'id_anagrafica', 'prestatore');
    }

    /**
     * One to One relation
     *
     * @return \Illuminate\Database\Eloquent\Relations\hasOne
     */
    public function getProfessionista()
    {
        return $this->hasOne(Anagrafica::class, 'id_anagrafica', 'professionista');
    }

    /**
     * One to One relation
     *
     * @return \Illuminate\Database\Eloquent\Relations\hasOne
     */
    public function getCommittente()
    {
        return $this->hasOne(Anagrafica::class, 'id_anagrafica', 'comittente');
    }

    /**
     * One to One relation
     *
     * @return \Illuminate\Database\Eloquent\Relations\hasOne
     */
    public function getVettore()
    {
        return $this->hasOne(Anagrafica::class, 'id_anagrafica', 'vettore');
    }
}

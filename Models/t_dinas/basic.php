<?php

namespace App\Models\BasicModels;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use App\Traits\ModelTrait;

class t_dinas extends Model
{   
    use ModelTrait;

    protected $table    = 't_dinas';
    protected $guarded  = ['id'];
    protected $casts    = ['created_at'=> 'datetime:d-m-Y','updated_at'=>'datetime:d-m-Y'];
    protected $fillable = ["kode","m_kary_id","date","keterangan","status","creator_id","last_editor_id","created_at","updated_at"];

    public $columns     = ["id","kode","m_kary_id","date","keterangan","status","creator_id","last_editor_id","created_at","updated_at"];
    public $columnsFull = ["id:bigint","kode:string:191","m_kary_id:bigint","date:date","keterangan:text","status:string:191","creator_id:integer","last_editor_id:integer","created_at:datetime","updated_at:datetime"];
    public $rules       = [];
    public $joins       = [];
    public $details     = ["t_dinas_d"];
    public $heirs       = [];
    public $detailsChild= [];
    public $detailsHeirs= [];
    public $unique      = [];
    public $required    = ["kode","date","keterangan","status"];
    public $createable  = ["kode","m_kary_id","date","keterangan","status","creator_id","last_editor_id","created_at","updated_at"];
    public $updateable  = ["kode","m_kary_id","date","keterangan","status","creator_id","last_editor_id","created_at","updated_at"];
    public $searchable  = ["kode","m_kary_id","date","keterangan","status","creator_id","last_editor_id","created_at","updated_at"];
    public $deleteable  = true;
    public $cascade     = true;
    public $deleteOnUse = false;

    
    public function t_dinas_d() :\HasMany
    {
        return $this->hasMany('App\Models\BasicModels\t_dinas_d', 't_dinas_id', 'id');
    }
    
    
}

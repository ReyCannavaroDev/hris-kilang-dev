<?php

namespace App\Models\BasicModels;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use App\Traits\ModelTrait;

class t_dinas_d extends Model
{   
    use ModelTrait;

    protected $table    = 't_dinas_d';
    protected $guarded  = ['id'];
    protected $casts    = ['created_at'=> 'datetime:d-m-Y','updated_at'=>'datetime:d-m-Y'];
    protected $fillable = ["t_dinas_id","m_kary_id","created_at","updated_at"];

    public $columns     = ["id","t_dinas_id","m_kary_id","created_at","updated_at"];
    public $columnsFull = ["id:bigint","t_dinas_id:bigint","m_kary_id:bigint","created_at:datetime","updated_at:datetime"];
    public $rules       = [];
    public $joins       = ["t_dinas.id=t_dinas_d.t_dinas_id"];
    public $details     = [];
    public $heirs       = [];
    public $detailsChild= [];
    public $detailsHeirs= [];
    public $unique      = [];
    public $required    = [""];
    public $createable  = ["t_dinas_id","m_kary_id","created_at","updated_at"];
    public $updateable  = ["t_dinas_id","m_kary_id","created_at","updated_at"];
    public $searchable  = ["t_dinas_id","m_kary_id","created_at","updated_at"];
    public $deleteable  = true;
    public $cascade     = true;
    public $deleteOnUse = false;

    
    
    
    public function t_dinas() :\BelongsTo
    {
        return $this->belongsTo('App\Models\BasicModels\t_dinas', 't_dinas_id', 'id');
    }
}

<?php

namespace App\Models\BasicModels;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use App\Traits\ModelTrait;

class t_libur_d extends Model
{   
    use ModelTrait;

    protected $table    = 't_libur_d';
    protected $guarded  = ["id"];
    protected $casts    = [
    "created_at"=> "datetime:d\/m\/Y H:i",
    "updated_at"=> "datetime:d\/m\/Y H:i"
	];
    protected $fillable = ["t_libur_id","m_kary_id","creator_id","last_editor_id"];

    public $columns     = ["id","t_libur_id","m_kary_id","creator_id","last_editor_id","created_at","updated_at"];
    public $columnsFull = ["id:bigint","t_libur_id:bigint","m_kary_id:bigint","creator_id:integer","last_editor_id:integer","created_at:datetime","updated_at:datetime"];
    public $rules       = [];
    public $joins       = ["t_libur.id=t_libur_d.t_libur_id","m_kary.id=t_libur_d.m_kary_id"];
    public $details     = [];
    public $heirs       = [];
    public $detailsChild= [];
    public $detailsHeirs= [];
    public $unique      = [];
    public $required    = ["m_kary_id"];
    public $createable  = ["t_libur_id","m_kary_id","creator_id","last_editor_id"];
    public $updateable  = ["t_libur_id","m_kary_id","creator_id","last_editor_id"];
    public $searchable  = ["id","t_libur_id","m_kary_id","creator_id","last_editor_id","created_at","updated_at"];
    public $deleteable  = true;
    public $cascade     = true;
    public $deleteOnUse = false;

    
    
    
    public function t_libur() :\BelongsTo
    {
        return $this->belongsTo('App\Models\BasicModels\t_libur', 't_libur_id', 'id');
    }
    public function m_kary() :\BelongsTo
    {
        return $this->belongsTo('App\Models\BasicModels\m_kary', 'm_kary_id', 'id');
    }
}

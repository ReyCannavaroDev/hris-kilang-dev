<?php

namespace App\Models\BasicModels;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use App\Traits\ModelTrait;

class t_bonus extends Model
{   
    use ModelTrait;

    protected $table    = 't_bonus';
    protected $guarded  = ["id"];
    protected $casts    = [
    "created_at"=> "datetime:d\/m\/Y H:i",
    "updated_at"=> "datetime:d\/m\/Y H:i"
	];
    protected $fillable = ["nomor","m_kary_id","jenis_bonus_id","date_from","date_to","no_doc","doc","nilai","keterangan","status","is_lunas","percentage","m_comp_id","m_dir_id","creator_id","last_editor_id"];

    public $columns     = ["id","nomor","m_kary_id","jenis_bonus_id","date_from","date_to","no_doc","doc","nilai","keterangan","status","is_lunas","percentage","m_comp_id","m_dir_id","creator_id","last_editor_id","created_at","updated_at"];
    public $columnsFull = ["id:bigint","nomor:string:50","m_kary_id:bigint","jenis_bonus_id:bigint","date_from:date","date_to:date","no_doc:string:191","doc:string:191","nilai:decimal","keterangan:text","status:string:50","is_lunas:boolean","percentage:decimal","m_comp_id:bigint","m_dir_id:bigint","creator_id:bigint","last_editor_id:bigint","created_at:datetime","updated_at:datetime"];
    public $rules       = [];
    public $joins       = ["m_kary.id=t_bonus.m_kary_id","m_general.id=t_bonus.jenis_bonus_id","m_comp.id=t_bonus.m_comp_id","m_dir.id=t_bonus.m_dir_id","default_users.id=t_bonus.creator_id","default_users.id=t_bonus.last_editor_id"];
    public $details     = [];
    public $heirs       = [];
    public $detailsChild= [];
    public $detailsHeirs= [];
    public $unique      = [];
    public $required    = ["m_kary_id","jenis_bonus_id","date_from","date_to","nilai","is_lunas"];
    public $createable  = ["nomor","m_kary_id","jenis_bonus_id","date_from","date_to","no_doc","doc","nilai","keterangan","status","is_lunas","percentage","m_comp_id","m_dir_id","creator_id","last_editor_id"];
    public $updateable  = ["nomor","m_kary_id","jenis_bonus_id","date_from","date_to","no_doc","doc","nilai","keterangan","status","is_lunas","percentage","m_comp_id","m_dir_id","creator_id","last_editor_id"];
    public $searchable  = ["id","nomor","m_kary_id","jenis_bonus_id","date_from","date_to","no_doc","doc","nilai","keterangan","status","is_lunas","percentage","m_comp_id","m_dir_id","creator_id","last_editor_id","created_at","updated_at"];
    public $deleteable  = true;
    public $cascade     = true;
    public $deleteOnUse = false;

    
    
    
    public function m_kary() :\BelongsTo
    {
        return $this->belongsTo('App\Models\BasicModels\m_kary', 'm_kary_id', 'id');
    }
    public function jenis_bonus() :\BelongsTo
    {
        return $this->belongsTo('App\Models\BasicModels\m_general', 'jenis_bonus_id', 'id');
    }
    public function m_comp() :\BelongsTo
    {
        return $this->belongsTo('App\Models\BasicModels\m_comp', 'm_comp_id', 'id');
    }
    public function m_dir() :\BelongsTo
    {
        return $this->belongsTo('App\Models\BasicModels\m_dir', 'm_dir_id', 'id');
    }
    public function creator() :\BelongsTo
    {
        return $this->belongsTo('App\Models\BasicModels\default_users', 'creator_id', 'id');
    }
    public function last_editor() :\BelongsTo
    {
        return $this->belongsTo('App\Models\BasicModels\default_users', 'last_editor_id', 'id');
    }
}

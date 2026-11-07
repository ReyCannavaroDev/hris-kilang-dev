<?php

namespace App\Models\BasicModels;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use App\Traits\ModelTrait;

class t_libur extends Model
{   
    use ModelTrait;

    protected $table    = 't_libur';
    protected $guarded  = ["id"];
    protected $casts    = [
    "created_at"=> "datetime:d\/m\/Y H:i",
    "updated_at"=> "datetime:d\/m\/Y H:i"
	];
    protected $fillable = ["m_comp_id","m_dir_id","kode","tanggal_mulai","tanggal_akhir","desc","is_all_kary","is_active","creator_id","last_editor_id"];

    public $columns     = ["id","m_comp_id","m_dir_id","kode","tanggal_mulai","tanggal_akhir","desc","is_all_kary","is_active","creator_id","last_editor_id","created_at","updated_at"];
    public $columnsFull = ["id:bigint","m_comp_id:bigint","m_dir_id:bigint","kode:string:50","tanggal_mulai:date","tanggal_akhir:date","desc:text","is_all_kary:boolean","is_active:boolean","creator_id:bigint","last_editor_id:bigint","created_at:datetime","updated_at:datetime"];
    public $rules       = [];
    public $joins       = ["m_comp.id=t_libur.m_comp_id","m_dir.id=t_libur.m_dir_id","default_users.id=t_libur.creator_id","default_users.id=t_libur.last_editor_id"];
    public $details     = ["t_libur_d"];
    public $heirs       = [];
    public $detailsChild= [];
    public $detailsHeirs= [];
    public $unique      = [];
    public $required    = ["tanggal_mulai","tanggal_akhir","desc","is_active"];
    public $createable  = ["m_comp_id","m_dir_id","kode","tanggal_mulai","tanggal_akhir","desc","is_all_kary","is_active","creator_id","last_editor_id"];
    public $updateable  = ["m_comp_id","m_dir_id","kode","tanggal_mulai","tanggal_akhir","desc","is_all_kary","is_active","creator_id","last_editor_id"];
    public $searchable  = ["id","m_comp_id","m_dir_id","kode","tanggal_mulai","tanggal_akhir","desc","is_all_kary","is_active","creator_id","last_editor_id","created_at","updated_at"];
    public $deleteable  = true;
    public $cascade     = true;
    public $deleteOnUse = false;

    
    public function t_libur_d() :\HasMany
    {
        return $this->hasMany('App\Models\BasicModels\t_libur_d', 't_libur_id', 'id');
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

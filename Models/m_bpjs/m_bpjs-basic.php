<?php

namespace App\Models\BasicModels;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use App\Traits\ModelTrait;

class m_bpjs extends Model
{
    use ModelTrait;

    protected $table    = 'm_bpjs';
    protected $guarded  = ["id"];
    protected $casts    = [
        "created_at" => "datetime:d\/m\/Y H:i",
        "updated_at" => "datetime:d\/m\/Y H:i"
    ];
    protected $fillable = ["m_comp_id","m_dir_id","kota_id","jenis","tahun","nominal","effective_from","effective_to","is_default","desc","is_active","creator_id","last_editor_id"];

    public $columns     = ["id","m_comp_id","m_dir_id","kota_id","jenis","tahun","nominal","effective_from","effective_to","is_default","desc","is_active","creator_id","last_editor_id","created_at","updated_at"];
    public $columnsFull = ["id:bigint","m_comp_id:bigint","m_dir_id:bigint","kota_id:bigint","jenis:string:20","tahun:integer","nominal:decimal","effective_from:date","effective_to:date","is_default:boolean","desc:text","is_active:boolean","creator_id:bigint","last_editor_id:bigint","created_at:datetime","updated_at:datetime"];
    public $rules       = [];
    public $joins       = ["m_comp.id=m_bpjs.m_comp_id","m_dir.id=m_bpjs.m_dir_id","m_general.id=m_bpjs.kota_id","default_users.id=m_bpjs.creator_id","default_users.id=m_bpjs.last_editor_id"];
    public $details     = [];
    public $heirs       = [];
    public $detailsChild= [];
    public $detailsHeirs= [];
    public $unique      = [];
    public $required    = ["jenis","tahun","nominal","is_active"];
    public $createable  = ["m_comp_id","m_dir_id","kota_id","jenis","tahun","nominal","effective_from","effective_to","is_default","desc","is_active","creator_id","last_editor_id"];
    public $updateable  = ["m_comp_id","m_dir_id","kota_id","jenis","tahun","nominal","effective_from","effective_to","is_default","desc","is_active","creator_id","last_editor_id"];
    public $searchable  = ["id","m_comp_id","m_dir_id","kota_id","jenis","tahun","nominal","effective_from","effective_to","is_default","desc","is_active","creator_id","last_editor_id","created_at","updated_at"];
    public $deleteable  = true;
    public $cascade     = true;
    public $deleteOnUse = false;

    public function m_comp() :\BelongsTo
    {
        return $this->belongsTo('App\Models\BasicModels\m_comp', 'm_comp_id', 'id');
    }

    public function m_dir() :\BelongsTo
    {
        return $this->belongsTo('App\Models\BasicModels\m_dir', 'm_dir_id', 'id');
    }

    public function kota() :\BelongsTo
    {
        return $this->belongsTo('App\Models\BasicModels\m_general', 'kota_id', 'id');
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

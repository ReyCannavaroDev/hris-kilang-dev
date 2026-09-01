<?php

namespace App\Models\BasicModels;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use App\Traits\ModelTrait;

class m_hutang_kary extends Model
{   
    use ModelTrait;

    protected $table    = 'm_hutang_kary';
    protected $guarded  = ["id"];
    protected $casts    = [
    "created_at"=> "datetime:d\/m\/Y H:i",
    "updated_at"=> "datetime:d\/m\/Y H:i"
	];
    protected $fillable = ["m_kary_id","jenis_potongan_id","tanggal","total_hutang","is_active","creator_id","last_editor_id","t_potongan_id"];

    public $columns     = ["id","m_kary_id","jenis_potongan_id","tanggal","total_hutang","is_active","creator_id","last_editor_id","created_at","updated_at","t_potongan_id"];
    public $columnsFull = ["id:bigint","m_kary_id:bigint","jenis_potongan_id:bigint","tanggal:date","total_hutang:decimal","is_active:boolean","creator_id:integer","last_editor_id:integer","created_at:datetime","updated_at:datetime","t_potongan_id:bigint"];
    public $rules       = [];
    public $joins       = ["m_kary.id=m_hutang_kary.m_kary_id","m_general.id=m_hutang_kary.jenis_potongan_id","t_potongan.id=m_hutang_kary.t_potongan_id"];
    public $details     = [];
    public $heirs       = [];
    public $detailsChild= [];
    public $detailsHeirs= [];
    public $unique      = [];
    public $required    = ["tanggal","total_hutang"];
    public $createable  = ["m_kary_id","jenis_potongan_id","tanggal","total_hutang","is_active","creator_id","last_editor_id","t_potongan_id"];
    public $updateable  = ["m_kary_id","jenis_potongan_id","tanggal","total_hutang","is_active","creator_id","last_editor_id","t_potongan_id"];
    public $searchable  = ["id","m_kary_id","jenis_potongan_id","tanggal","total_hutang","is_active","creator_id","last_editor_id","created_at","updated_at","t_potongan_id"];
    public $deleteable  = true;
    public $cascade     = true;
    public $deleteOnUse = false;

    
    
    
    public function m_kary() :\BelongsTo
    {
        return $this->belongsTo('App\Models\BasicModels\m_kary', 'm_kary_id', 'id');
    }
    public function jenis_potongan() :\BelongsTo
    {
        return $this->belongsTo('App\Models\BasicModels\m_general', 'jenis_potongan_id', 'id');
    }
    public function t_potongan() :\BelongsTo
    {
        return $this->belongsTo('App\Models\BasicModels\t_potongan', 't_potongan_id', 'id');
    }
}

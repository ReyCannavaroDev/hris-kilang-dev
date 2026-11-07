<?php

namespace App\Models\BasicModels;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use App\Traits\ModelTrait;

class t_cuti_uang extends Model
{   
    use ModelTrait;

    protected $table    = 't_cuti_uang';
    protected $guarded  = ['id'];
    protected $casts    = ['created_at'=> 'datetime:d-m-Y','updated_at'=>'datetime:d-m-Y'];
    protected $fillable = ["m_kary_id","t_cuti_adjustment_id","jumlah_cuti_diuangkan","sisa_cuti","nominal","gaji","kode","tanggal","status","created_at","updated_at"];

    public $columns     = ["id","m_kary_id","t_cuti_adjustment_id","jumlah_cuti_diuangkan","sisa_cuti","nominal","gaji","kode","tanggal","status","created_at","updated_at"];
    public $columnsFull = ["id:bigint","m_kary_id:bigint","t_cuti_adjustment_id:bigint","jumlah_cuti_diuangkan:integer","sisa_cuti:integer","nominal:decimal","gaji:decimal","kode:string:50","tanggal:date","status:string:191","created_at:datetime","updated_at:datetime"];
    public $rules       = [];
    public $joins       = ["m_kary.id=t_cuti_uang.m_kary_id"];
    public $details     = [];
    public $heirs       = [];
    public $detailsChild= [];
    public $detailsHeirs= [];
    public $unique      = [];
    public $required    = ["nominal","gaji"];
    public $createable  = ["m_kary_id","t_cuti_adjustment_id","jumlah_cuti_diuangkan","sisa_cuti","nominal","gaji","kode","tanggal","status","created_at","updated_at"];
    public $updateable  = ["m_kary_id","t_cuti_adjustment_id","jumlah_cuti_diuangkan","sisa_cuti","nominal","gaji","kode","tanggal","status","created_at","updated_at"];
    public $searchable  = ["m_kary_id","t_cuti_adjustment_id","jumlah_cuti_diuangkan","sisa_cuti","nominal","gaji","kode","tanggal","status","created_at","updated_at"];
    public $deleteable  = true;
    public $cascade     = true;
    public $deleteOnUse = false;

    
    
    
    public function m_kary() :\BelongsTo
    {
        return $this->belongsTo('App\Models\BasicModels\m_kary', 'm_kary_id', 'id');
    }
}

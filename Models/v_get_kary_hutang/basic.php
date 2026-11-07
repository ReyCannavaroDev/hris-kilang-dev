<?php

namespace App\Models\BasicModels;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use App\Traits\ModelTrait;

class v_get_kary_hutang extends Model
{   
    use ModelTrait;

    protected $table    = 'v_get_kary_hutang';
    protected $guarded  = ['id'];
    protected $casts    = ['created_at'=> 'datetime:d-m-Y','updated_at'=>'datetime:d-m-Y'];
    protected $fillable = ["nama_lengkap","jenis_potongan_id","nama_jenis_potongan","total_hutang","total_bayar","sisa_hutang","tanggal_hutang_terakhir"];

    public $columns     = ["id","nama_lengkap","jenis_potongan_id","nama_jenis_potongan","total_hutang","total_bayar","sisa_hutang","tanggal_hutang_terakhir"];
    public $columnsFull = ["id:string","nama_lengkap:string","jenis_potongan_id:string","nama_jenis_potongan:string","total_hutang:string","total_bayar:string","sisa_hutang:string","tanggal_hutang_terakhir:string"];
    public $rules       = "[]";
    public $joins       = [];
    public $details     = [];
    public $heirs       = [];
    public $detailsChild= [];
    public $detailsHeirs= [];
    public $unique      = [];
    public $required    = '[]';
    public $createable  = ["nama_lengkap","jenis_potongan_id","nama_jenis_potongan","total_hutang","total_bayar","sisa_hutang","tanggal_hutang_terakhir"];
    public $updateable  = ["nama_lengkap","jenis_potongan_id","nama_jenis_potongan","total_hutang","total_bayar","sisa_hutang","tanggal_hutang_terakhir"];
    public $searchable  = ["nama_lengkap","jenis_potongan_id","nama_jenis_potongan","total_hutang","total_bayar","sisa_hutang","tanggal_hutang_terakhir"];
    public $deleteable  = true;
    public $cascade     = true;
    public $deleteOnUse = false;

    
    
    
}

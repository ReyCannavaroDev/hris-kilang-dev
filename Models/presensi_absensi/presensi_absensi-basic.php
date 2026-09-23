<?php

namespace App\Models\BasicModels;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use App\Traits\ModelTrait;

class presensi_absensi extends Model
{   
    use ModelTrait;

    protected $table    = 'presensi_absensi';
    protected $guarded  = ["id"];
    protected $casts    = [
    "created_at"=> "datetime:d\/m\/Y H:i",
    "updated_at"=> "datetime:d\/m\/Y H:i"
	];
    protected $fillable = ["m_comp_id","default_user_id","tanggal","status","checkin_time","checkin_foto","checkin_lat","checkin_long","checkin_address","checkin_region","checkin_on_scope","catatan_in","checkout_time","checkout_foto","checkout_lat","checkout_long","checkout_address","checkout_region","checkout_on_scope","catatan_out","checkout_istirahat_time","checkout_istirahat_foto","checkout_istirahat_lat","checkout_istirahat_long","checkout_istirahat_address","checkout_istirahat_region","checkout_istirahat_on_scope","catatan_checkout_istirahat","checkin_kerja_time","checkin_kerja_foto","checkin_kerja_lat","checkin_kerja_long","checkin_kerja_address","checkin_kerja_region","checkin_kerja_on_scope","catatan_checkin_kerja","creator_id","last_editor_id","catatan","is_lembur","shift","jadwal_kerja_id","missing_check"];

    public $columns     = ["id","m_comp_id","default_user_id","tanggal","status","checkin_time","checkin_foto","checkin_lat","checkin_long","checkin_address","checkin_region","checkin_on_scope","catatan_in","checkout_time","checkout_foto","checkout_lat","checkout_long","checkout_address","checkout_region","checkout_on_scope","catatan_out","checkout_istirahat_time","checkout_istirahat_foto","checkout_istirahat_lat","checkout_istirahat_long","checkout_istirahat_address","checkout_istirahat_region","checkout_istirahat_on_scope","catatan_checkout_istirahat","checkin_kerja_time","checkin_kerja_foto","checkin_kerja_lat","checkin_kerja_long","checkin_kerja_address","checkin_kerja_region","checkin_kerja_on_scope","catatan_checkin_kerja","creator_id","last_editor_id","created_at","updated_at","catatan","is_lembur","shift","jadwal_kerja_id","missing_check"];
    public $columnsFull = ["id:bigint","m_comp_id:bigint","default_user_id:bigint","tanggal:date","status:string:191","checkin_time:time","checkin_foto:string:191","checkin_lat:string:191","checkin_long:string:191","checkin_address:string:191","checkin_region:string:191","checkin_on_scope:boolean","catatan_in:text","checkout_time:time","checkout_foto:string:191","checkout_lat:string:191","checkout_long:string:191","checkout_address:string:191","checkout_region:string:191","checkout_on_scope:boolean","catatan_out:text","checkout_istirahat_time:time","checkout_istirahat_foto:string:191","checkout_istirahat_lat:string:191","checkout_istirahat_long:string:191","checkout_istirahat_address:string:191","checkout_istirahat_region:string:191","checkout_istirahat_on_scope:boolean","catatan_checkout_istirahat:text","checkin_kerja_time:time","checkin_kerja_foto:string:191","checkin_kerja_lat:string:191","checkin_kerja_long:string:191","checkin_kerja_address:string:191","checkin_kerja_region:string:191","checkin_kerja_on_scope:boolean","catatan_checkin_kerja:text","creator_id:bigint","last_editor_id:bigint","created_at:datetime","updated_at:datetime","catatan:text","is_lembur:boolean","shift:string:191","jadwal_kerja_id:bigint","missing_check:integer"];
    public $rules       = [];
    public $joins       = ["m_comp.id=presensi_absensi.m_comp_id","default_users.id=presensi_absensi.default_user_id","default_users.id=presensi_absensi.creator_id","default_users.id=presensi_absensi.last_editor_id","jadwal_kerja.id=presensi_absensi.jadwal_kerja_id"];
    public $details     = [];
    public $heirs       = [];
    public $detailsChild= [];
    public $detailsHeirs= [];
    public $unique      = [];
    public $required    = ["tanggal","status","checkin_time","checkin_lat","checkin_long","checkin_address","checkin_region","checkin_on_scope"];
    public $createable  = ["m_comp_id","default_user_id","tanggal","status","checkin_time","checkin_foto","checkin_lat","checkin_long","checkin_address","checkin_region","checkin_on_scope","catatan_in","checkout_time","checkout_foto","checkout_lat","checkout_long","checkout_address","checkout_region","checkout_on_scope","catatan_out","checkout_istirahat_time","checkout_istirahat_foto","checkout_istirahat_lat","checkout_istirahat_long","checkout_istirahat_address","checkout_istirahat_region","checkout_istirahat_on_scope","catatan_checkout_istirahat","checkin_kerja_time","checkin_kerja_foto","checkin_kerja_lat","checkin_kerja_long","checkin_kerja_address","checkin_kerja_region","checkin_kerja_on_scope","catatan_checkin_kerja","creator_id","last_editor_id","catatan","is_lembur","shift","jadwal_kerja_id","missing_check"];
    public $updateable  = ["m_comp_id","default_user_id","tanggal","status","checkin_time","checkin_foto","checkin_lat","checkin_long","checkin_address","checkin_region","checkin_on_scope","catatan_in","checkout_time","checkout_foto","checkout_lat","checkout_long","checkout_address","checkout_region","checkout_on_scope","catatan_out","checkout_istirahat_time","checkout_istirahat_foto","checkout_istirahat_lat","checkout_istirahat_long","checkout_istirahat_address","checkout_istirahat_region","checkout_istirahat_on_scope","catatan_checkout_istirahat","checkin_kerja_time","checkin_kerja_foto","checkin_kerja_lat","checkin_kerja_long","checkin_kerja_address","checkin_kerja_region","checkin_kerja_on_scope","catatan_checkin_kerja","creator_id","last_editor_id","catatan","is_lembur","shift","jadwal_kerja_id","missing_check"];
    public $searchable  = ["id","m_comp_id","default_user_id","tanggal","status","checkin_time","checkin_foto","checkin_lat","checkin_long","checkin_address","checkin_region","checkin_on_scope","catatan_in","checkout_time","checkout_foto","checkout_lat","checkout_long","checkout_address","checkout_region","checkout_on_scope","catatan_out","checkout_istirahat_time","checkout_istirahat_foto","checkout_istirahat_lat","checkout_istirahat_long","checkout_istirahat_address","checkout_istirahat_region","checkout_istirahat_on_scope","catatan_checkout_istirahat","checkin_kerja_time","checkin_kerja_foto","checkin_kerja_lat","checkin_kerja_long","checkin_kerja_address","checkin_kerja_region","checkin_kerja_on_scope","catatan_checkin_kerja","creator_id","last_editor_id","created_at","updated_at","catatan","is_lembur","shift","jadwal_kerja_id","missing_check"];
    public $deleteable  = true;
    public $cascade     = true;
    public $deleteOnUse = false;

    
    
    
    public function m_comp() :\BelongsTo
    {
        return $this->belongsTo('App\Models\BasicModels\m_comp', 'm_comp_id', 'id');
    }
    public function default_user() :\BelongsTo
    {
        return $this->belongsTo('App\Models\BasicModels\default_users', 'default_user_id', 'id');
    }
    public function creator() :\BelongsTo
    {
        return $this->belongsTo('App\Models\BasicModels\default_users', 'creator_id', 'id');
    }
    public function last_editor() :\BelongsTo
    {
        return $this->belongsTo('App\Models\BasicModels\default_users', 'last_editor_id', 'id');
    }
    public function jadwal_kerja() :\BelongsTo
    {
        return $this->belongsTo('App\Models\BasicModels\jadwal_kerja', 'jadwal_kerja_id', 'id');
    }
}

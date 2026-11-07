<?php

namespace App\Models\CustomModels;

class t_dinas_d extends \App\Models\BasicModels\t_dinas_d
{    
    public function __construct()
    {
        parent::__construct();
    }
    
    public $fileColumns    = [ /*file_column*/ ];

    //public $createAdditionalData = ["creator_id"=>"auth:id"];
    //public $updateAdditionalData = ["last_editor_id"=>"auth:id"];
    
    public function t_dinas() :\BelongsTo
    {
        return $this->belongsTo('App\Models\BasicModels\t_dinas', 't_dinas_id', 'id');
    }

    public function m_kary() :\BelongsTo
    {
        return $this->belongsTo('App\Models\BasicModels\m_kary', 'm_kary_id', 'id');
    }

    public function transformRowData( array $row )
    {
        $namaKary = m_kary::where('id', $row['m_kary_id'])->first()->nama_lengkap ?? '-';
        $row['m_kary.nama_lengkap'] = $namaKary;
        return $row;
        // return array_merge( $row, [
        //     'm_kary.nama_lengkap' => $namaKary
        // ] );
    }
    
}
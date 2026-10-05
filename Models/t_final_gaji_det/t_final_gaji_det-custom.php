<?php

namespace App\Models\CustomModels;

class t_final_gaji_det extends \App\Models\BasicModels\t_final_gaji_det
{    
    public function __construct()
    {
        parent::__construct();
    }
    
    public $fileColumns    = [ /*file_column*/ ];

    //public $createAdditionalData = ["creator_id"=>"auth:id"];
    //public $updateAdditionalData = ["last_editor_id"=>"auth:id"];

    public static function isRestrictedUser(): bool
    {
        $currentUser = auth()->user();
        if (!$currentUser) return false;
        $username = strtolower($currentUser->username ?? '');
        $name = strtolower($currentUser->name ?? '');
        return in_array($username, ['sisi', 'kristina']) || in_array($name, ['sisi', 'kristina']);
    }

    protected static function boot()
    {
        parent::boot();
        static::addGlobalScope('restrictUserKaryDet', function ($builder) {
            if (self::isRestrictedUser()) {
                $builder->whereHas('m_kary', function($q) {
                    $q->whereRaw("LOWER(m_kary.nama_lengkap) LIKE '%wagino%'");
                });
            }
        });
    }

    
}

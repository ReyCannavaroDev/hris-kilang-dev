<?php

namespace App\Models\CustomModels;

class v_get_kary_hutang extends \App\Models\BasicModels\v_get_kary_hutang
{    
    public function __construct()
    {
        parent::__construct();
    }
    
    public $fileColumns    = [ /*file_column*/ ];

    //public $createAdditionalData = ["creator_id"=>"auth:id"];
    //public $updateAdditionalData = ["last_editor_id"=>"auth:id"];

    
}
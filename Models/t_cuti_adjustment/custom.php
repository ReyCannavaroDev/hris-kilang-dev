<?php

namespace App\Models\CustomModels;

class t_cuti_adjustment extends \App\Models\BasicModels\t_cuti_adjustment
{    
    private $helper;
    public function __construct()
    {
        parent::__construct();
        $this->helper = getCore("Helper");
    }
    
    public $fileColumns    = [ /*file_column*/ ];

    //public $createAdditionalData = ["creator_id"=>"auth:id"];
    //public $updateAdditionalData = ["last_editor_id"=>"auth:id"];

    public function createBefore( $model, $arrayData, $metaData, $id=null )
    {
      $newArrayData  = array_merge( $arrayData,[
        "nomor" => $this->helper->generateNomor("KODE ADJ CUTI"),
      ] );
      return [
          "model"  => $model,
          "data"   => $newArrayData,
          // "errors" => ['error1']
      ];
    }

    public function transformRowData(array $row)
    {
        $data = [];
        if (app()->request) {
            $data = \DB::select("select public.employee_attendance(?,?)",[Date('Y-m-d'),$row['m_kary.id'] ??0]);
            $data = json_decode($data[0]->employee_attendance);
            $object['sisa_cuti_satu_hari'] = $data->sisa_cuti_satu_hari;
            $object['sisa_setengah_hari'] = $data->sisa_cuti_setengah_hari;
        }
        return array_merge($row, $object);
    }
}
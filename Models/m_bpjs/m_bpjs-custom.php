<?php

namespace App\Models\CustomModels;

use Illuminate\Database\Eloquent\Model;
use App\Traits\ModelTrait;

class m_bpjs extends Model
{
    use ModelTrait;

    protected $table    = 'm_bpjs';
    protected $guarded  = ["id"];
    public $casts    = [
        "created_at" => "datetime:d\/m\/Y H:i",
        "updated_at" => "datetime:d\/m\/Y H:i"
    ];
    protected $fillable = ["kota_id","jenis","tahun","nominal","effective_from","effective_to","is_default","desc","is_active"];

    public $columns     = ["id","kota_id","jenis","tahun","nominal","effective_from","effective_to","is_default","desc","is_active","created_at","updated_at"];
    public $columnsFull = ["id:bigint","kota_id:bigint","jenis:string:20","tahun:integer","nominal:decimal","effective_from:date","effective_to:date","is_default:boolean","desc:text","is_active:boolean","created_at:datetime","updated_at:datetime"];
    public $rules       = [];
    public $joins       = ["m_general.id=m_bpjs.kota_id"];
    public $details     = [];
    public $heirs       = [];
    public $detailsChild= [];
    public $detailsHeirs= [];
    public $unique      = [];
    public $required    = ["jenis","tahun","nominal","is_active"];
    public $createable  = ["kota_id","jenis","tahun","nominal","effective_from","effective_to","is_default","desc","is_active"];
    public $updateable  = ["kota_id","jenis","tahun","nominal","effective_from","effective_to","is_default","desc","is_active"];
    public $searchable  = ["id","kota_id","jenis","tahun","nominal","effective_from","effective_to","is_default","desc","is_active","created_at","updated_at"];
    public $deleteable  = true;
    public $cascade     = true;
    public $deleteOnUse = false;

    public function __construct()
    {
        parent::__construct();
    }

    public $fileColumns = [ /*file_column*/ ];

    public function transformRowData(array $row)
    {
        if (!empty($row['kota_id'])) {
            $row['kota_nama'] = \DB::table('m_general')->where('id', $row['kota_id'])->value('value');
        }

        $row['nominal_fmt'] = number_format((float) ($row['nominal'] ?? 0), 0, ',', '.');

        return $row;
    }

    public function createBefore($model, $arrayData, $metaData, $id = null)
    {
        return [
            "model" => $model,
            "data"  => array_merge($arrayData, [
                'jenis' => strtoupper($arrayData['jenis'] ?? 'UMK'),
            ])
        ];
    }

    public function updateBefore($model, $arrayData, $metaData, $id = null)
    {
        if (isset($arrayData['jenis'])) {
            $arrayData['jenis'] = strtoupper($arrayData['jenis']);
        }

        return [
            "model" => $model,
            "data"  => $arrayData
        ];
    }
}

<?php

namespace App\Models\CustomModels;

class m_bpjs extends \App\Models\BasicModels\m_bpjs
{
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
        $isDefault = isset($arrayData['is_default']) ? filter_var($arrayData['is_default'], FILTER_VALIDATE_BOOLEAN) : false;
        $isActive = isset($arrayData['is_active']) ? filter_var($arrayData['is_active'], FILTER_VALIDATE_BOOLEAN) : true;

        return [
            "model" => $model,
            "data"  => array_merge($arrayData, [
                'jenis'      => strtoupper($arrayData['jenis'] ?? 'UMK'),
                'is_default' => $isDefault ? 1 : 0,
                'is_active'  => $isActive ? 1 : 0,
            ])
        ];
    }

    public function updateBefore($model, $arrayData, $metaData, $id = null)
    {
        if (isset($arrayData['jenis'])) {
            $arrayData['jenis'] = strtoupper($arrayData['jenis']);
        }
        if (isset($arrayData['is_default'])) {
            $arrayData['is_default'] = filter_var($arrayData['is_default'], FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
        }
        if (isset($arrayData['is_active'])) {
            $arrayData['is_active'] = filter_var($arrayData['is_active'], FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
        }

        return [
            "model" => $model,
            "data"  => $arrayData
        ];
    }
}

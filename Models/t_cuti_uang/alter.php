<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class tcutiuang extends Migration
{
    protected $tableName = "t_cuti_uang";
    
    public function up()
    {
        Schema::table($this->tableName, function (Blueprint $table) {
            //$table->string('_existColumnName_')->change();
            //$table->string('_columnName_');
            //$table->dropColumn([ ]);
            $table->bigInteger('m_kary_id')->comment('{"src":"m_kary.id"}')->nullable()->change();

        });
    }
}

<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class tdinasd extends Migration
{
    protected $tableName = "t_dinas_d";
    
    public function up()
    {
        Schema::table($this->tableName, function (Blueprint $table) {
            //$table->string('_existColumnName_')->change();
            //$table->string('_columnName_');
            // $table->bigInteger('t_dinas_id')->comment('{"src":"t_dinas.id"}')->nullable();
            // $table->dropColumn(['t_dinas_id']);
            //$table->bigInteger('t_dinas_id')->comment('{"fk":"t_dinas.id"}')->nullable()->change();
        });
    }
}

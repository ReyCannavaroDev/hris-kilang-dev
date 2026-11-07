<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class vgetkaryhutang extends Migration
{
 protected $tableName = "v_get_kary_hutang";

     public function up()
    {
        DB::statement("DROP VIEW IF EXISTS {$this->tableName}");

        DB::unprepared("
            CREATE VIEW {$this->tableName} AS
            SELECT * FROM get_sisa_hutang_all('')
        ");
    }

    public function down()
    {
        DB::statement("DROP VIEW IF EXISTS {$this->tableName}");
    }
}
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class tcutiuang extends Migration
{
    protected $tableName = "t_cuti_uang";

    public function up()
    {
        Schema::create($this->tableName, function (Blueprint $table) {
            $table->id()->from(1);
            $table->bigInteger('m_kary_id')->comment('{"src":"m_kary.id"}')->nullable();
            $table->bigInteger('t_cuti_adjustment_id')->comment('{"src":"t_cuti_adjustment.id"}')->nullable();
            $table->integer('jumlah_cuti_diuangkan')->nullable();
            $table->integer('sisa_cuti')->nullable();
            $table->decimal('nominal', 18, 2);
            $table->decimal('gaji', 18, 2);
            $table->string('kode', 50)->nullable();
            $table->date('tanggal')->nullable();
            $table->string('status')->nullable();
            //kode
            //tanggal
            //status posted:draft
            // $table->string('samplecolumn',100)->nullable();
            // $table->integer('creator_id')->nullable();
            // $table->integer('last_editor_id')->nullable();
            $table->timestamps();
        });

        table_config($this->tableName, [
            "guarded"       => ["id"],
            "required"      => [],
            "!createable"   => ["id","created_at","updated_at"],
            "!updateable"   => ["id","created_at","updated_at"],
            "searchable"    => "all",
            "deleteable"    => "true",
            "deleteOnUse"   => "false",
            "extendable"    => "false",
            "casts"     => [
                'created_at' => 'datetime:d/m/Y H:i',
                'updated_at' => 'datetime:d/m/Y H:i'
            ]
        ]);

        // if( $data = \Cache::pull($this->tableName) ){
        //     $fixedData = json_decode( json_encode( $data ), true );
        //     \DB::table($this->tableName)->insert( $fixedData );
        // }
    }
    public function down()
    {
        // if( Schema::hasTable($this->tableName) ){
        //     \Cache::put($this->tableName, \DB::table($this->tableName)->get(), 60*30 );
        // }
        Schema::dropIfExists($this->tableName);
    }
}
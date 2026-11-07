<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class tbonus extends Migration
{
    protected $tableName = "t_bonus";

    public function up()
    {
        Schema::create($this->tableName, function (Blueprint $table) {
            $table->id()->from(1);

            $table->string('nomor', 50)->nullable(); // Nomor
            $table->unsignedBigInteger('m_kary_id')->comment('{"src":"m_kary.id"}'); // Karyawan

            $table->unsignedBigInteger('jenis_bonus_id')->comment('{"src":"m_general.id"}'); // Jenis Bonus (ubah dari jenis_potongan_id ke nama sesuai)
            
            $table->date('date_from'); // Tanggal Awal
            $table->date('date_to');   // Tanggal Akhir

            $table->string('no_doc')->nullable(); // No Dok
            $table->string('doc')->nullable();    // Dokumen Upload

            $table->decimal('nilai', 12, 2); // Nilai (Bonus)
            $table->text('keterangan')->nullable(); // Keterangan

            $table->string('status', 50)->default('DRAFT')->nullable(); // STATUS (DRAFT -> POST)

            $table->boolean('is_lunas')->default(0); // Optional (jika tetap dipakai)
            $table->decimal('percentage', 12 ,2)->nullable(); // Optional (jika tetap dipakai)

            $table->unsignedBigInteger('m_comp_id')->default(1)->nullable()->comment('{"src":"m_comp.id"}'); // Optional
            $table->unsignedBigInteger('m_dir_id')->nullable()->comment('{"src":"m_dir.id"}');              // Optional

            $table->unsignedBigInteger('creator_id')->nullable()->comment('{"src":"default_users.id"}');
            $table->unsignedBigInteger('last_editor_id')->nullable()->comment('{"src":"default_users.id"}');

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
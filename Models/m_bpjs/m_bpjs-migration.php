<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class mbpjs extends Migration
{
    protected $tableName = "m_bpjs";

    public function up()
    {
        Schema::create($this->tableName, function (Blueprint $table) {
            $table->id()->from(1);
            $table->bigInteger('m_comp_id')->comment('{"src":"m_comp.id"}')->nullable();
            $table->bigInteger('m_dir_id')->comment('{"src":"m_dir.id"}')->nullable();
            $table->bigInteger('kota_id')->comment('{"src":"m_general.id"}')->nullable();
            $table->string('jenis', 20)->default('UMK');
            $table->integer('tahun');
            $table->decimal('nominal', 12, 2);
            $table->date('effective_from')->nullable();
            $table->date('effective_to')->nullable();
            $table->boolean('is_default')->default(0);
            $table->text('desc')->nullable();
            $table->boolean('is_active')->default(1);
            $table->bigInteger('creator_id')->comment('{"src":"default_users.id"}')->nullable();
            $table->bigInteger('last_editor_id')->comment('{"src":"default_users.id"}')->nullable();
            $table->timestamps();

            $table->index(['m_comp_id', 'm_dir_id', 'tahun', 'jenis', 'is_active'], 'm_bpjs_lookup_index');
        });

        table_config($this->tableName, [
            "guarded"       => ["id"],
            "required"      => ["jenis", "tahun", "nominal", "is_active"],
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
    }

    public function down()
    {
        Schema::dropIfExists($this->tableName);
    }
}

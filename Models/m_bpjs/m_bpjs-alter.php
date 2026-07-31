<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class mbpjs extends Migration
{
    protected $tableName = "m_bpjs";

    public function up()
    {
        if (!Schema::hasTable($this->tableName)) {
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
            });
        } else {
            Schema::table($this->tableName, function (Blueprint $table) {
                if (!Schema::hasColumn('m_bpjs', 'm_comp_id')) {
                    $table->bigInteger('m_comp_id')->comment('{"src":"m_comp.id"}')->nullable();
                }
                if (!Schema::hasColumn('m_bpjs', 'm_dir_id')) {
                    $table->bigInteger('m_dir_id')->comment('{"src":"m_dir.id"}')->nullable();
                }
                if (!Schema::hasColumn('m_bpjs', 'kota_id')) {
                    $table->bigInteger('kota_id')->comment('{"src":"m_general.id"}')->nullable();
                }
                if (!Schema::hasColumn('m_bpjs', 'jenis')) {
                    $table->string('jenis', 20)->default('UMK');
                }
                if (!Schema::hasColumn('m_bpjs', 'tahun')) {
                    $table->integer('tahun')->nullable();
                }
                if (!Schema::hasColumn('m_bpjs', 'nominal')) {
                    $table->decimal('nominal', 12, 2)->default(0);
                }
                if (!Schema::hasColumn('m_bpjs', 'effective_from')) {
                    $table->date('effective_from')->nullable();
                }
                if (!Schema::hasColumn('m_bpjs', 'effective_to')) {
                    $table->date('effective_to')->nullable();
                }
                if (!Schema::hasColumn('m_bpjs', 'is_default')) {
                    $table->boolean('is_default')->default(0);
                }
                if (!Schema::hasColumn('m_bpjs', 'desc')) {
                    $table->text('desc')->nullable();
                }
                if (!Schema::hasColumn('m_bpjs', 'is_active')) {
                    $table->boolean('is_active')->default(1);
                }
                if (!Schema::hasColumn('m_bpjs', 'creator_id')) {
                    $table->bigInteger('creator_id')->comment('{"src":"default_users.id"}')->nullable();
                }
                if (!Schema::hasColumn('m_bpjs', 'last_editor_id')) {
                    $table->bigInteger('last_editor_id')->comment('{"src":"default_users.id"}')->nullable();
                }
                if (!Schema::hasColumn('m_bpjs', 'created_at')) {
                    $table->timestamp('created_at')->nullable();
                }
                if (!Schema::hasColumn('m_bpjs', 'updated_at')) {
                    $table->timestamp('updated_at')->nullable();
                }
            });
        }

        \DB::statement('CREATE INDEX IF NOT EXISTS m_bpjs_company_lookup_index ON m_bpjs (m_comp_id, m_dir_id, tahun, jenis, is_active)');
        \DB::statement('CREATE INDEX IF NOT EXISTS m_bpjs_lookup_index ON m_bpjs (kota_id, tahun, jenis, is_active)');
    }
}

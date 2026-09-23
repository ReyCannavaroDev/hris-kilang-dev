<?php
namespace Tests;
use Laravel\Lumen\Testing\DatabaseTransactions;
use Laravel\Passport\Passport;
use App\Models\Defaults\User;

class presensiAbsensiTest extends TestCase
{
    use DatabaseTransactions;

    public function testReadingData()
    {
        Passport::actingAs(User::first());
        $payload = [
            'paginate' => 25
        ];

        $this->call('GET', '/operation/presensi_absensi', $payload);

        $responseArr = json_decode( $this->response->getContent(),true );
        ff( $responseArr, 'dump data' );

        $this->assertTrue(true);
    }

    public function testCreatingData()
    {
        $user = User::where('username', 'USERNAME')->first();
        $this->assertNotEmpty( $user );
        
        Passport::actingAs($user);

        $payload = [
		    "id" => "bigint:optional:autocreate",
		    "m_comp_id" => "bigint:optional",
		    "default_user_id" => "bigint:optional",
		    "tanggal" => "date:required",
		    "status" => "string:191:required",
		    "checkin_time" => "time:required",
		    "checkin_foto" => "string:191:optional",
		    "checkin_lat" => "string:191:required",
		    "checkin_long" => "string:191:required",
		    "checkin_address" => "string:191:required",
		    "checkin_region" => "string:191:required",
		    "checkin_on_scope" => "boolean:required",
		    "catatan_in" => "text:optional",
		    "checkout_time" => "time:optional",
		    "checkout_foto" => "string:191:optional",
		    "checkout_lat" => "string:191:optional",
		    "checkout_long" => "string:191:optional",
		    "checkout_address" => "string:191:optional",
		    "checkout_region" => "string:191:optional",
		    "checkout_on_scope" => "boolean:optional",
		    "catatan_out" => "text:optional",
		    "checkout_istirahat_time" => "time:optional",
		    "checkout_istirahat_foto" => "string:191:optional",
		    "checkout_istirahat_lat" => "string:191:optional",
		    "checkout_istirahat_long" => "string:191:optional",
		    "checkout_istirahat_address" => "string:191:optional",
		    "checkout_istirahat_region" => "string:191:optional",
		    "checkout_istirahat_on_scope" => "boolean:optional",
		    "catatan_checkout_istirahat" => "text:optional",
		    "checkin_kerja_time" => "time:optional",
		    "checkin_kerja_foto" => "string:191:optional",
		    "checkin_kerja_lat" => "string:191:optional",
		    "checkin_kerja_long" => "string:191:optional",
		    "checkin_kerja_address" => "string:191:optional",
		    "checkin_kerja_region" => "string:191:optional",
		    "checkin_kerja_on_scope" => "boolean:optional",
		    "catatan_checkin_kerja" => "text:optional",
		    "creator_id" => "bigint:optional",
		    "last_editor_id" => "bigint:optional",
		    "created_at" => "datetime:optional:autocreate",
		    "updated_at" => "datetime:optional:autocreate",
		    "catatan" => "text:optional",
		    "is_lembur" => "boolean:optional",
		    "shift" => "string:191:optional",
		    "jadwal_kerja_id" => "bigint:optional",
		    "missing_check" => "integer:optional"
		];

        $this->call('POST', '/operation/presensi_absensi', $payload);

        $responseArr = json_decode( $this->response->getContent(),true );
        // ff( $responseArr, 'dump data' );

        $this->assertEquals( 200, $this->response->status() );
        // $this->seeJsonStructure( ['status'] );

        $this->seeInDatabase('presensi_absensi', array_filter($payload, function($dt){
            return !is_array($dt);
        } ));
    }
}
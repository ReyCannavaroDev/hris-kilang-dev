<?php
namespace Tests;
use Laravel\Lumen\Testing\DatabaseTransactions;
use Laravel\Passport\Passport;
use App\Models\Defaults\User;

class tCutiUangTest extends TestCase
{
    use DatabaseTransactions;

    public function testReadingData()
    {
        Passport::actingAs(User::first());
        $payload = [
            'paginate' => 25
        ];

        $this->call('GET', '/operation/t_cuti_uang', $payload);

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
		    "m_kary_id" => "bigint:optional",
		    "t_cuti_adjustment_id" => "bigint:optional",
		    "jumlah_cuti_diuangkan" => "integer:optional",
		    "sisa_cuti" => "integer:optional",
		    "nominal" => "decimal:required",
		    "gaji" => "decimal:required",
		    "kode" => "string:50:optional",
		    "tanggal" => "date:optional",
		    "status" => "string:191:optional",
		    "created_at" => "datetime:optional:autocreate",
		    "updated_at" => "datetime:optional:autocreate"
		];

        $this->call('POST', '/operation/t_cuti_uang', $payload);

        $responseArr = json_decode( $this->response->getContent(),true );
        // ff( $responseArr, 'dump data' );

        $this->assertEquals( 200, $this->response->status() );
        // $this->seeJsonStructure( ['status'] );

        $this->seeInDatabase('t_cuti_uang', array_filter($payload, function($dt){
            return !is_array($dt);
        } ));
    }
}
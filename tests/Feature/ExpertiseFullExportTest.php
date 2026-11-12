<?php
namespace Tests\Feature;
use App\Models\{Permission,Role,User,Vehicle,OficinaExpertiseProcess};
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
class ExpertiseFullExportTest extends TestCase
{
    use DatabaseTransactions;
    public function test_export_all_filtered_rows_and_permissions(): void
    {
        $role=Role::create(['title'=>uniqid('Export')]);$role->permissions()->attach(Permission::firstOrCreate(['title'=>'oficina_expertise_process_access'])->id);
        $user=User::factory()->create();$user->roles()->sync([$role->id]);$this->actingAs($user);
        $license=uniqid('EXPORT');$vehicle=Vehicle::create([]);
        $base=['vehicle_id'=>$vehicle->id,'license'=>$license,'insurance_company'=>'=Teste','created_by_id'=>$user->id,'status'=>'received','entry_date'=>'2026-11-12','approved_amount'=>10.50];
        $ids=[];for($i=0;$i<43;$i++)$ids[]=OficinaExpertiseProcess::create($base)->id;
        OficinaExpertiseProcess::create(array_replace($base,['status'=>'closed']));
        OficinaExpertiseProcess::create(array_replace($base,['created_by_id'=>null]));
        foreach ([null, 0, 10.49, 20.76] as $amount) OficinaExpertiseProcess::create(array_replace($base,['approved_amount'=>$amount]));
        $before=OficinaExpertiseProcess::whereIn('id',$ids)->get()->toArray();
        $params=['amount_min'=>'10,50','amount_max'=>'20,75','export'=>'csv','page'=>2,'vehicle_search'=>$license,'created_by_id'=>$user->id,'status'=>'received','insurance_company'=>'=Teste'];
        $res=$this->get(route('admin.oficina-expertise-processes.index',$params))->assertOk()->assertDownload();
        $stream=fopen('php://temp','r+');fwrite($stream,substr($res->streamedContent(),3));rewind($stream);$this->assertCount(13,fgetcsv($stream,0,';'));$found=[];
        while(($row=fgetcsv($stream,0,';'))!==false){$found[]=(int)$row[0];$this->assertSame("'=Teste",$row[3]);$this->assertSame('10,50',$row[8]);}fclose($stream);
        $this->assertSame(array_reverse($ids),$found);$this->assertSame($before,OficinaExpertiseProcess::whereIn('id',$ids)->get()->toArray());
        $this->getJson(route('admin.oficina-expertise-processes.index',['export'=>'bad']))->assertUnprocessable();
        foreach ([['amount_min'=>-1],['amount_max'=>'abc'],['amount_min'=>20,'amount_max'=>10]] as $invalid) $this->getJson(route('admin.oficina-expertise-processes.index',$invalid))->assertUnprocessable();
        $this->get(route('admin.oficina-expertise-processes.index',['vehicle_search'=>$license,'amount_max'=>0]))->assertOk()->assertViewHas('processes',fn($p)=>$p->count()===1);
        $user->roles()->detach();$this->actingAs($user->fresh())->get(route('admin.oficina-expertise-processes.index',$params))->assertForbidden();
    }
}

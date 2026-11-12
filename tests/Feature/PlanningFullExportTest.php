<?php
namespace Tests\Feature;
use App\Models\{Permission,Role,User,Vehicle,Repair,WorkshopIntervention};
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
class PlanningFullExportTest extends TestCase
{
    use DatabaseTransactions;
    public function test_export_filters_all_pages_and_keeps_access(): void
    {
        $role=Role::create(['title'=>uniqid('Export')]);$role->permissions()->attach(Permission::firstOrCreate(['title'=>'workshop_planning_access'])->id);
        $user=User::factory()->create();$user->roles()->sync([$role->id]);$this->actingAs($user);
        $repair=Repair::create(['vehicle_id'=>Vehicle::create([])->id]);
        $type=\App\Models\WorkshopInterventionType::create(['name'=>uniqid('Tipo')]);
        $base=['type_id'=>$type->id,'repair_id'=>$repair->id,'title'=>'=1+1','status'=>'planned','planned_start_date'=>'2026-11-12','planned_end_date'=>'2026-11-13'];
        $ids=[];for($i=0;$i<43;$i++){$item=WorkshopIntervention::create($base);$item->mechanics()->attach($user->id);$ids[]=$item->id;}
        WorkshopIntervention::create($base);
        $other=WorkshopIntervention::create(array_replace($base,['status'=>'completed']));$other->mechanics()->attach($user->id);
        $before=WorkshopIntervention::whereIn('id',$ids)->get()->toArray();
        $params=['export'=>'csv','page'=>2,'mechanic_id'=>$user->id,'status'=>'planned','start_date'=>'2026-11-12','end_date'=>'2026-11-12'];
        $res=$this->get(route('admin.workshop-interventions.index',$params))->assertOk()->assertDownload();
        $stream=fopen('php://temp','r+');fwrite($stream,substr($res->streamedContent(),3));rewind($stream);$this->assertCount(12,fgetcsv($stream,0,';'));$found=[];
        while(($row=fgetcsv($stream,0,';'))!==false){$found[]=(int)$row[0];$this->assertSame("'=1+1",$row[5]);$this->assertSame('0',$row[10]);}fclose($stream);
        $this->assertSame($ids,$found);$this->assertSame($before,WorkshopIntervention::whereIn('id',$ids)->get()->toArray());
        $this->getJson(route('admin.workshop-interventions.index',['export'=>'bad']))->assertUnprocessable();
        $user->roles()->detach();$this->actingAs($user->fresh())->get(route('admin.workshop-interventions.index',$params))->assertForbidden();
    }
}

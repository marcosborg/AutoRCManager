<?php
namespace Tests\Feature;
use App\Models\{Role,User,Vehicle,SaleClosureApproval};
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
class SaleClosureAmountRangeTest extends TestCase
{
 use DatabaseTransactions;
 public function test_sale_total_ranges_preserve_records_and_export():void
 {
  $u=User::factory()->create();$u->roles()->sync([Role::firstOrCreate(['title'=>'Stand Adm'])->id]);$this->actingAs($u);$v=Vehicle::create([]);$ids=[];
  foreach([0,10.49,10.50,20.75,20.76] as $amount)$ids[]=SaleClosureApproval::create(['vehicle_id'=>$v->id,'closed_by_id'=>$u->id,'trigger_type'=>'payment','status'=>'pending','sales_total'=>$amount,'closed_at'=>now()])->id;
  $before=SaleClosureApproval::whereIn('id',$ids)->get()->toArray();
  $p=['closed_by_id'=>$u->id,'amount_min'=>'10,50','amount_max'=>'20,75'];
  $this->get(route('admin.sale-closure-approvals.index',$p))->assertOk()->assertViewHas('approvals',fn($rows)=>$rows->pluck('id')->sort()->values()->all()===[$ids[2],$ids[3]]);
  $res=$this->get(route('admin.sale-closure-approvals.export',$p))->assertOk()->assertDownload();$csv=$res->streamedContent();$this->assertStringContainsString('10,50',$csv);$this->assertStringNotContainsString('20,76',$csv);
  foreach([['amount_min'=>-1],['amount_max'=>'abc'],['amount_min'=>20,'amount_max'=>10]] as $bad)$this->getJson(route('admin.sale-closure-approvals.export',$bad))->assertUnprocessable();
  $this->assertSame($before,SaleClosureApproval::whereIn('id',$ids)->get()->toArray());
  $u->update(['name'=>'=1+1']);
  for($i=0;$i<53;$i++)SaleClosureApproval::create(['vehicle_id'=>$v->id,'closed_by_id'=>$u->id,'trigger_type'=>'payment','status'=>'pending','sales_total'=>12,'closed_at'=>now()]);
  $response=$this->get(route('admin.sale-closure-approvals.export',$p+['page'=>2]))->assertOk()->assertDownload();
  $f=fopen('php://temp','r+');fwrite($f,substr($response->streamedContent(),3));rewind($f);$this->assertCount(12,fgetcsv($f,0,';'));$count=0;
  while(($row=fgetcsv($f,0,';'))!==false){$count++;$this->assertSame("'=1+1",$row[3]);}fclose($f);$this->assertSame(55,$count);
  $u->roles()->detach();$this->actingAs($u->fresh())->get(route('admin.sale-closure-approvals.export',$p))->assertForbidden();
 }
}

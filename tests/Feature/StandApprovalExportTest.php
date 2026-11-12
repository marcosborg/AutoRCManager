<?php
namespace Tests\Feature;
use App\Models\{Role,User,Vehicle,StandCashPaymentApproval};
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
class StandApprovalExportTest extends TestCase
{
 use DatabaseTransactions;
 public function test_export_is_complete_filtered_and_read_only():void
 {
  $user=User::factory()->create();$user->roles()->sync([Role::firstOrCreate(['title'=>'Stand Adm'])->id]);$this->actingAs($user);
  $vehicle=Vehicle::create([]);$ids=[];
  for($i=0;$i<54;$i++){
   $payment=\App\Models\VehicleClientPayment::withoutEvents(fn()=>\App\Models\VehicleClientPayment::create(['payment_method_id'=>\App\Models\PaymentMethod::firstOrCreate(['name'=>'Teste exportação'])->id,'vehicle_id'=>$vehicle->id,'amount'=>12,'paid_at'=>'2026-11-12']));
   $approval=StandCashPaymentApproval::create(['vehicle_client_payment_id'=>$payment->id,'vehicle_id'=>$vehicle->id,'status'=>$i<53?'pending':'rejected']);
   if($i<53)$ids[]=$approval->id;
  }
  $before=StandCashPaymentApproval::whereIn('id',$ids)->get()->toArray();
  $response=$this->get(route('admin.stand-cash-payment-approvals.index',['export'=>'csv','page'=>2]))->assertOk()->assertDownload();
  $f=fopen('php://temp','r+');fwrite($f,substr($response->streamedContent(),3));rewind($f);$this->assertCount(12,fgetcsv($f,0,';'));$found=[];
  while(($row=fgetcsv($f,0,';'))!==false)$found[]=(int)$row[0];fclose($f);
  $this->assertSame(array_reverse($ids),array_values(array_intersect($found,$ids)));
  $this->assertSame($before,StandCashPaymentApproval::whereIn('id',$ids)->get()->toArray());
  $this->getJson(route('admin.stand-cash-payment-approvals.index',['export'=>'bad']))->assertUnprocessable();
  $user->roles()->detach();$this->actingAs($user->fresh())->get(route('admin.stand-cash-payment-approvals.index',['export'=>'csv']))->assertForbidden();
 }
}

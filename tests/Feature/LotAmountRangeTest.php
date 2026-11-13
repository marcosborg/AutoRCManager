<?php
namespace Tests\Feature;
use App\Models\{Role,Permission,User,VehicleGroup};
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
class LotAmountRangeTest extends TestCase
{
 use DatabaseTransactions;
 public function test_total_range_is_inclusive_and_read_only():void
 {
  $role=Role::create(['title'=>uniqid('Range')]);$role->permissions()->attach(Permission::firstOrCreate(['title'=>'vehicle_lot_access'])->id);
  $u=User::factory()->create();$u->roles()->sync([$role->id]);$this->actingAs($u);$ids=[];
  foreach([0,10.49,10.50,20.75,20.76] as $amount)$ids[]=VehicleGroup::create(['name'=>uniqid('Lote'),'total_amount'=>$amount])->id;
  $before=VehicleGroup::whereIn('id',$ids)->get()->toArray();
  $this->get(route('admin.vehicle-groups.index',['amount_min'=>'10,50','amount_max'=>'20,75']))->assertOk()->assertViewHas('vehicleGroups',fn($rows)=>array_values(array_intersect($rows->pluck('id')->sort()->all(),$ids))===[$ids[2],$ids[3]]);
  foreach([['amount_min'=>-1],['amount_max'=>'abc'],['amount_min'=>20,'amount_max'=>10]] as $bad)$this->getJson(route('admin.vehicle-groups.index',$bad))->assertUnprocessable();
  $this->assertSame($before,VehicleGroup::whereIn('id',$ids)->get()->toArray());
  $u->roles()->detach();$this->actingAs($u->fresh())->get(route('admin.vehicle-groups.index'))->assertForbidden();
 }
}

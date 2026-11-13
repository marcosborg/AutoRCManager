<?php
namespace Tests\Feature;
use App\Models\{Permission,Role,User,Vehicle,DismantledPart};
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
class DismantledPartTest extends TestCase {
 use DatabaseTransactions;
 public function test_register_search_and_duplicate_submission_preserve_vehicle():void{
  $r=Role::create(['title'=>uniqid('Inventory')]);foreach(['repair_access','repair_edit'] as $p)$r->permissions()->attach(Permission::firstOrCreate(['title'=>$p])->id);
  $u=User::factory()->create();$u->roles()->sync([$r->id]);$this->actingAs($u);$v=Vehicle::create(['foreign_license'=>'TEST-INVENTORY']);$before=$v->fresh()->getAttributes();
  $data=['vehicle_id'=>$v->id,'description'=>'Farol','reference'=>'REF-X','condition'=>'untested','location'=>'A1','submission_token'=>(string)\Illuminate\Support\Str::uuid()];
  $this->get(route('admin.dismantled-parts.create'))->assertOk();
  $this->get(route('admin.dismantled-parts.create',['vehicle_search'=>'TEST-INVENTORY']))->assertOk()->assertViewHas('vehicles',fn($rows)=>$rows->pluck('id')->all()===[$v->id]);
  $this->post(route('admin.dismantled-parts.store'),$data)->assertRedirect()->assertSessionHasNoErrors();
  $this->post(route('admin.dismantled-parts.store'),$data)->assertRedirect();$this->assertSame(1,DismantledPart::where('submission_token',$data['submission_token'])->count());
  $this->assertSame($before,$v->fresh()->getAttributes());
  $this->get(route('admin.dismantled-parts.index',['search'=>'REF-X']))->assertOk()->assertSee('Farol');
  $part=DismantledPart::where('submission_token',$data['submission_token'])->sole();$token=$part->revisionToken();
  $update=['reference'=>'REF-X','description'=>'Farol corrigido','condition'=>'good','location'=>'B2','revision'=>$token,'vehicle_id'=>999999];
  $this->patch(route('admin.dismantled-parts.update',$part),$update)->assertRedirect()->assertSessionHasNoErrors();
  $this->assertSame($v->id,$part->fresh()->vehicle_id);$this->assertSame('B2',$part->fresh()->location);
  $this->patchJson(route('admin.dismantled-parts.update',$part),$update)->assertUnprocessable();
  $this->assertDatabaseHas('audit_logs',['subject_type'=>DismantledPart::class.'#'.$part->id,'description'=>'audit:updated','user_id'=>$u->id]);
  $this->postJson(route('admin.dismantled-parts.store'),array_replace($data,['value'=>-1]))->assertUnprocessable();
  $u->roles()->detach();$this->actingAs($u->fresh())->get(route('admin.dismantled-parts.index'))->assertForbidden();
 }
}

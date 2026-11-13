<?php
namespace Tests\Feature;
use App\Models\{Permission,Role,User,Vehicle,DismantledPart};
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\TestCase;
class DismantledPartSafetyTest extends TestCase {
 use DatabaseTransactions;
 private function actor(): User {
  $role=Role::create(['title'=>uniqid('Safety')]);
  $role->permissions()->attach(Permission::firstOrCreate(['title'=>'repair_edit'])->id);
  $user=User::factory()->create();$user->roles()->sync([$role->id]);return $user;
 }
 private function payload():array{return ['vehicle_id'=>Vehicle::create([])->id,'reference'=>' REF ','description'=>' Farol ','condition'=>'untested','location'=>' A1 ','notes'=>' Nota ','value'=>'12.50','submission_token'=>(string)Str::uuid()];}
 public function test_repeated_token_requires_all_same_fields_and_owner():void{
  $user=$this->actor();$this->actingAs($user);$data=$this->payload();$url=route('admin.dismantled-parts.store');
  $this->post($url,$data)->assertRedirect()->assertSessionHasNoErrors();
  $part=DismantledPart::where('submission_token',$data['submission_token'])->sole();$before=$part->getAttributes();
  $this->post($url,array_replace($data,['value'=>12.5]))->assertRedirect()->assertSessionHasNoErrors();
  foreach(['reference'=>'OTHER','description'=>'Porta','condition'=>'good','location'=>'B2','notes'=>'Outra','value'=>'12.51','vehicle_id'=>Vehicle::create([])->id] as $field=>$value){
   $this->postJson($url,array_replace($data,[$field=>$value]))->assertUnprocessable()->assertJsonValidationErrors('submission_token');
  }
  $this->actingAs($this->actor())->postJson($url,$data)->assertUnprocessable();
  $this->assertSame(1,DismantledPart::where('submission_token',$data['submission_token'])->count());$this->assertSame($before,$part->fresh()->getAttributes());
  $this->assertSame('Farol',$part->description);$this->assertSame('A1',$part->location);
 }
 public function test_unicode_blank_required_fields_are_rejected_on_create_and_update():void{
  $this->actingAs($this->actor());$data=$this->payload();$url=route('admin.dismantled-parts.store');
  foreach(['description','location'] as $field)$this->postJson($url,array_replace($data,[$field=>" \u{00A0}\u{200B} "]))->assertUnprocessable()->assertJsonValidationErrors($field);
  $this->post($url,$data)->assertRedirect();$part=DismantledPart::where('submission_token',$data['submission_token'])->sole();$before=$part->getAttributes();
  foreach(['description','location'] as $field)$this->patchJson(route('admin.dismantled-parts.update',$part),array_replace($data,['revision'=>$part->revisionToken(),$field=>"\u{00A0}\u{FEFF}"]))->assertUnprocessable()->assertJsonValidationErrors($field);
  $this->assertSame($before,$part->fresh()->getAttributes());
 }
}

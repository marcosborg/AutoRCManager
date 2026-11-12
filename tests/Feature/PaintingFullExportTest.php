<?php
namespace Tests\Feature;
use App\Models\{Permission, Role, User, Vehicle, PaintingJob};
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
class PaintingFullExportTest extends TestCase
{
    use DatabaseTransactions;
    public function test_export_respects_filters_access_and_all_pages(): void
    {
        $role = Role::create(['title' => uniqid('Export')]);
        $role->permissions()->attach(Permission::firstOrCreate(['title' => 'painting_job_access'])->id);
        $user = User::factory()->create(); $user->roles()->sync([$role->id]); $this->actingAs($user);
        $license = uniqid('PAINT'); $vehicle = Vehicle::create([]);
        $base = ['vehicle_id' => $vehicle->id, 'painter_id' => $user->id, 'license' => $license, 'brand_model' => '=1+1', 'status' => 'open', 'entry_date' => '2026-11-12'];
        $ids = []; for ($i=0;$i<33;$i++) $ids[] = PaintingJob::create($base)->id;
        PaintingJob::create(array_replace($base,['status'=>'completed']));
        PaintingJob::create(array_replace($base,['painter_id'=>null]));
        PaintingJob::create(array_replace($base,['entry_date'=>'2026-11-10']));
        $deleted = PaintingJob::create($base); $deleted->delete();
        $before = PaintingJob::whereIn('id',$ids)->get()->toArray();
        $params = ['search'=>$license,'painter_id'=>$user->id,'status'=>'open','from'=>'2026-11-12','to'=>'2026-11-12','export'=>'csv','page'=>2];
        $res = $this->get(route('admin.painting-jobs.index',$params))->assertOk()->assertDownload();
        $stream=fopen('php://temp','r+'); fwrite($stream,substr($res->streamedContent(),3)); rewind($stream);
        $this->assertCount(7,fgetcsv($stream,0,';')); $found=[];
        while(($row=fgetcsv($stream,0,';'))!==false){$found[]=(int)$row[0];$this->assertSame("'=1+1",$row[2]);$this->assertSame('2026-11-12',$row[3]);}
        fclose($stream); $this->assertSame(array_reverse($ids),$found);
        $this->assertSame($before,PaintingJob::whereIn('id',$ids)->get()->toArray());
        $this->getJson(route('admin.painting-jobs.index',['export'=>'bad']))->assertUnprocessable();
        $user->roles()->detach();$this->actingAs($user->fresh())->get(route('admin.painting-jobs.index',$params))->assertForbidden();
    }
}

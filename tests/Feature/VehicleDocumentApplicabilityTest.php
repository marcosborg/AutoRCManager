<?php

namespace Tests\Feature;

use App\Models\{AuditLog, Permission, Role, User, Vehicle};
use App\Support\VehicleDocuments;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class VehicleDocumentApplicabilityTest extends TestCase
{
    use DatabaseTransactions;

    private function user(array $permissions = ['vehicle_access', 'vehicle_edit', 'vehicle_documents_area_access']): User
    {
        $user = User::factory()->create();
        $role = Role::create(['title' => uniqid('Applicability ')]);
        foreach ($permissions as $permission) $role->permissions()->attach(Permission::firstOrCreate(['title' => $permission])->id);
        $user->roles()->attach($role);
        return $user;
    }

    private function vehicle(): Vehicle
    {
        return Vehicle::create(array_merge(array_fill_keys(array_keys(VehicleDocuments::checks()), 1),
            ['license' => uniqid('APPL'), 'model' => 'Applicability test', 'leasing_agreement' => 0]));
    }

    private function decide(Vehicle $v, string $status = 'not_applicable', string $reason = 'Sem leasing', ?string $revision = null)
    {
        return $this->patchJson(route('admin.vehicles.documents.applicability', [$v, 'leasing_agreement']),
            ['status' => $status, 'reason' => $reason, 'revision' => $revision ?? VehicleDocuments::applicabilityRevision($v, 'leasing_agreement')]);
    }

    public function test_dispense_restore_history_and_idempotency_preserve_receipts_and_media(): void
    {
        $user=$this->user(); $this->actingAs($user); $v=$this->vehicle();
        $before=$v->getAttributes(); $revision=VehicleDocuments::applicabilityRevision($v, 'leasing_agreement');
        $this->decide($v)->assertOk()->assertJsonPath('message', 'Decisão guardada. As confirmações de receção e os ficheiros mantêm-se.');
        $fresh=$v->fresh();
        $this->assertSame([], VehicleDocuments::pendingChecks($fresh));
        $this->assertSame(0, (int)$fresh->leasing_agreement);
        foreach ($before as $key=>$value) if ($key !== 'updated_at') $this->assertEquals($value, $fresh->getAttributes()[$key]);
        $this->assertSame($user->id, $fresh->document_applicability['leasing_agreement']['user_id']);
        $this->decide($v, revision: $revision)->assertOk();
        $this->assertCount(1, $v->fresh()->document_applicability['leasing_agreement']['history']);
        $this->decide($v, 'review', 'Engano', $revision)->assertStatus(409);
        $this->decide($fresh, 'review', 'Reavaliar contrato')->assertOk();
        $fresh=$v->fresh();
        $this->assertArrayHasKey('leasing_agreement', VehicleDocuments::pendingChecks($fresh));
        $this->assertCount(2, $fresh->document_applicability['leasing_agreement']['history']);
        $this->assertSame(2, AuditLog::where('description','vehicle_document:applicability')->where('subject_id',$v->id)->count());
        $html=view('admin.vehicles.partials.document-summary', ['vehicle'=>$fresh])->render();
        $this->assertStringContainsString('Sem leasing', $html);
        $this->assertStringContainsString('Reavaliar contrato', $html);
        $this->assertArrayNotHasKey('document_applicability', $fresh->toArray());
    }

    public function test_review_query_excludes_dispensed_checks_but_keeps_unidentified_files(): void
    {
        $this->actingAs($this->user()); $v=$this->vehicle();
        $url=route('admin.vehicles.documents.review',['scope'=>'all','license'=>$v->license]);
        $this->get($url)->assertOk()->assertSee('data-vehicle-id="'.$v->id.'"',false);
        $this->decide($v)->assertOk();
        $this->get($url)->assertOk()->assertDontSee('data-vehicle-id="'.$v->id.'"',false);
        $file=$v->media()->create(['collection_name'=>'documents','name'=>'Unclassified', 'file_name'=>'paper.pdf',
            'disk'=>'public','size'=>1,'manipulations'=>[],'custom_properties'=>[],'generated_conversions'=>[],'responsive_images'=>[]]);
        $attrs=$file->fresh()->getAttributes();
        $this->get($url)->assertOk()->assertSee('data-vehicle-id="'.$v->id.'"',false);
        $this->assertEquals($attrs,$file->fresh()->getAttributes());
        $this->decide($v->fresh(),'review','Documento esperado da companhia')->assertOk();
        $this->assertArrayHasKey('leasing_agreement',VehicleDocuments::pendingChecks($v->fresh()));
    }

    public function test_permissions_input_validation_and_no_mass_assignment(): void
    {
        $v=$this->vehicle();
        foreach ([[],['vehicle_edit'],['vehicle_documents_area_access']] as $permissions) {
            $this->actingAs($this->user($permissions)); $this->decide($v)->assertForbidden();
        }
        $this->actingAs($this->user());
        $this->decide($v, reason:'   ')->assertUnprocessable();
        $this->decide($v, reason:str_repeat('x',501))->assertUnprocessable();
        $this->decide($v, status:'received')->assertUnprocessable();
        $this->patchJson(route('admin.vehicles.documents.applicability',[$v,'invented']),[])->assertNotFound();
        $v->fill(['document_applicability'=>['leasing_agreement'=>['status'=>'not_applicable']]]);
        $this->assertNull($v->document_applicability);
        $this->decide($v, reason:'<script>alert(1)</script>')->assertOk();
        $html=view('admin.vehicles.partials.document-summary',['vehicle'=>$v->fresh()])->render();
        $this->assertStringNotContainsString('<script>alert(1)</script>',$html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
    }

    public function test_digital_only_types_can_be_dispensed_without_classifying_unknown_files(): void
    {
        $this->actingAs($this->user()); $v=$this->vehicle();
        foreach (['dav','inspection','purchase_invoice'] as $document) {
            $this->patchJson(route('admin.vehicles.documents.applicability',[$v,$document]),[
                'status'=>'not_applicable','reason'=>'Confirmado manualmente para este teste',
                'revision'=>VehicleDocuments::applicabilityRevision($v,$document),
            ])->assertOk();
        }
        $fresh=$v->fresh();
        foreach (['dav','inspection','purchase_invoice'] as $document) {
            $this->assertTrue(VehicleDocuments::notApplicable($fresh,$document));
            $this->assertSame(0,VehicleDocuments::count($fresh,$document));
        }
    }
    public function test_backoffice_api_requires_permissions_and_preserves_decisions_across_documents(): void
    {
        $v=$this->vehicle();
        $url='/api/v1/backoffice/vehicles/'.$v->id.'/documents/leasing_agreement/applicability';
        $data=['status'=>'not_applicable','reason'=>'Sem leasing','revision'=>VehicleDocuments::applicabilityRevision($v,'leasing_agreement')];
        $this->patchJson($url,$data)->assertUnauthorized();
        $this->actingAs($this->user(['vehicle_edit']))->patchJson($url,$data)->assertForbidden();
    }

    public function test_authorized_integration_preserves_other_document_decisions(): void
    {
        $v=$this->vehicle();
        $url='/api/v1/backoffice/vehicles/'.$v->id.'/documents/leasing_agreement/applicability';
        $data=['status'=>'not_applicable','reason'=>'Sem leasing','revision'=>VehicleDocuments::applicabilityRevision($v,'leasing_agreement')];
        $user=$this->user();
        $user->roles()->attach(Role::firstOrCreate(['title'=>'Admin']));
        $token=$user->createToken('integration:applicability-test')->plainTextToken;
        $this->withHeader('Authorization','Bearer '.$token)->patchJson($url,$data)->assertOk();
        $this->patchJson('/api/v1/backoffice/vehicles/'.$v->id.'/documents/dav/applicability',[
            'status'=>'not_applicable','reason'=>'Teste local nacional','revision'=>VehicleDocuments::applicabilityRevision($v,'dav'),
        ])->assertOk();
        $this->assertTrue(VehicleDocuments::notApplicable($v->fresh(),'leasing_agreement'));
        $this->assertTrue(VehicleDocuments::notApplicable($v->fresh(),'dav'));
    }

}

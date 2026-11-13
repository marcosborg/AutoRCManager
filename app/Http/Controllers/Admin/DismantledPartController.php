<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\{DismantledPart,Vehicle};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB,Gate};
use Illuminate\Validation\Rule;
class DismantledPartController extends Controller {
 public function index(Request $request){
  abort_unless(Gate::allows('repair_access'),403);
  $request->validate(['search'=>['nullable','string','max:255'],'condition'=>['nullable',Rule::in(array_keys(DismantledPart::CONDITIONS))],'location'=>['nullable','string','max:255'],'vehicle_id'=>['nullable','integer',Rule::exists('vehicles','id')]]);
  $origin=$request->filled('vehicle_id')?Vehicle::withTrashed()->findOrFail($request->vehicle_id):null;
  $parts=DismantledPart::with(['vehicle','createdBy'])
   ->when($origin,fn($q)=>$q->where('vehicle_id',$origin->id))
   ->when($request->filled('condition'),fn($q)=>$q->where('condition',$request->condition))
   ->when($request->filled('location'),fn($q)=>$q->where('location','like','%'.trim($request->location).'%'))
   ->when($request->filled('search'),function($q)use($request){$s='%'.trim($request->search).'%';$q->where(fn($w)=>$w->where('reference','like',$s)->orWhere('description','like',$s)->orWhere('location','like',$s)->orWhereHas('vehicle',fn($v)=>$v->searchByLicense(trim($request->search))));})
   ->latest('id')->paginate(50)->withQueryString();
  return view('admin.dismantledParts.index',compact('parts','origin'));
 }
 public function create(Request $request){
  abort_unless(Gate::allows('repair_edit'),403);
  $search=trim((string)$request->query('vehicle_search',''));
  $vehicles=Vehicle::query()->when($search!=='',fn($q)=>$q->searchByLicense($search))->orderByDesc('id')->limit(25)->get();
  if($request->old('vehicle_id')){$selected=Vehicle::find($request->old('vehicle_id'));if($selected)$vehicles=$vehicles->prepend($selected)->unique('id');}
  return view('admin.dismantledParts.create',compact('vehicles','search'));
 }
 public function store(Request $request){
  abort_unless(Gate::allows('repair_edit'),403);
  $this->normalizeTextInput($request);
  $data=$request->validate(['vehicle_id'=>['required','integer',Rule::exists('vehicles','id')->whereNull('deleted_at')],
   'reference'=>['nullable','string','max:255'],'description'=>['required','string','max:255'],
   'condition'=>['required',Rule::in(array_keys(DismantledPart::CONDITIONS))],'location'=>['required','string','max:255'],
   'value'=>['nullable','numeric','min:0','max:9999999999.99'],'notes'=>['nullable','string','max:5000'],'submission_token'=>['required','uuid']]);
  DB::transaction(function()use($data,$request){
   Vehicle::whereKey($data['vehicle_id'])->lockForUpdate()->firstOrFail();
   $existing=DismantledPart::where('submission_token',$data['submission_token'])->first();
   if($existing){
    $same=(int)$existing->created_by_id===(int)$request->user()->id && (int)$existing->vehicle_id===(int)$data['vehicle_id'];
    foreach(['reference','description','condition','location','notes'] as $field) $same=$same && ($existing->$field??null)===($data[$field]??null);
    $money=fn($value)=>$value===null?null:number_format((float)$value,2,'.','');
    $same=$same && $money($existing->value)===$money($data['value']??null);
    if(!$same) throw \Illuminate\Validation\ValidationException::withMessages(['submission_token'=>'Este envio já foi utilizado com outros dados. Atualize a página antes de registar outra peça.']);
    return;
   }
   DismantledPart::create($data+['created_by_id'=>$request->user()->id]);
  });
  return redirect()->route('admin.dismantled-parts.index')->with('message','Peça registada no inventário.');
 }
 public function edit(DismantledPart $part){
  abort_unless(Gate::allows('repair_edit'),403);
  return view('admin.dismantledParts.edit',compact('part'));
 }
 public function history(DismantledPart $part){
  abort_unless(Gate::allows('repair_access'),403);
  $events=\App\Models\AuditLog::with('user')->where('subject_type',DismantledPart::class.'#'.$part->id)->orderByDesc('id')->paginate(30);
  return view('admin.dismantledParts.history',compact('part','events'));
 }
 public function update(Request $request,DismantledPart $part){
  abort_unless(Gate::allows('repair_edit'),403);
  $this->normalizeTextInput($request);
  $data=$request->validate(['reference'=>['nullable','string','max:255'],'description'=>['required','string','max:255'],
   'condition'=>['required',Rule::in(array_keys(DismantledPart::CONDITIONS))],'location'=>['required','string','max:255'],
   'value'=>['nullable','numeric','min:0','max:9999999999.99'],'notes'=>['nullable','string','max:5000'],'revision'=>['required','string','size:64']]);
  DB::transaction(function()use($part,$data){
   $part=DismantledPart::whereKey($part->id)->lockForUpdate()->firstOrFail();
   if(!hash_equals($part->revisionToken(),$data['revision'])) throw \Illuminate\Validation\ValidationException::withMessages(['revision'=>'A ficha foi alterada por outra pessoa. Atualize a página antes de corrigir.']);
   unset($data['revision']);$part->update($data);
  });
  return redirect()->route('admin.dismantled-parts.index')->with('message','Ficha corrigida. A alteração ficou registada no histórico.');
 }
 private function normalizeTextInput(Request $request):void {
  $values=[];
  foreach(['reference','description','location','notes'] as $field){
   if(!$request->exists($field) || !is_string($request->input($field))) continue;
   $value=preg_replace('/^[\s\p{Z}\x{200B}\x{FEFF}]+|[\s\p{Z}\x{200B}\x{FEFF}]+$/u','',$request->input($field));
   $values[$field]=$value===''?null:$value;
  }
  $request->merge($values);
 }
}

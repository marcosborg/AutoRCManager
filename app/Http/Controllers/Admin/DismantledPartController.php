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
  $parts=DismantledPart::with(['vehicle','createdBy'])
   ->when($request->filled('search'),function($q)use($request){$s='%'.trim($request->search).'%';$q->where(fn($w)=>$w->where('reference','like',$s)->orWhere('description','like',$s)->orWhere('location','like',$s)->orWhereHas('vehicle',fn($v)=>$v->searchByLicense(trim($request->search))));})
   ->latest('id')->paginate(50)->withQueryString();
  return view('admin.dismantledParts.index',compact('parts'));
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
  $data=$request->validate(['vehicle_id'=>['required','integer',Rule::exists('vehicles','id')->whereNull('deleted_at')],
   'reference'=>['nullable','string','max:255'],'description'=>['required','string','max:255'],
   'condition'=>['required',Rule::in(array_keys(DismantledPart::CONDITIONS))],'location'=>['required','string','max:255'],
   'value'=>['nullable','numeric','min:0','max:9999999999.99'],'notes'=>['nullable','string','max:5000'],'submission_token'=>['required','uuid']]);
  DB::transaction(function()use($data,$request){
   Vehicle::whereKey($data['vehicle_id'])->lockForUpdate()->firstOrFail();
   $existing=DismantledPart::where('submission_token',$data['submission_token'])->first();
   if($existing){abort_unless((int)$existing->created_by_id===(int)$request->user()->id && (int)$existing->vehicle_id===(int)$data['vehicle_id'] && $existing->description===$data['description'],422);return;}
   DismantledPart::create($data+['created_by_id'=>$request->user()->id]);
  });
  return redirect()->route('admin.dismantled-parts.index')->with('message','Peça registada no inventário.');
 }
}

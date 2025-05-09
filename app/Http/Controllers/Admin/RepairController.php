<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\CsvImportTrait;
use App\Http\Controllers\Traits\MediaUploadingTrait;
use App\Http\Requests\MassDestroyRepairRequest;
use App\Http\Requests\StoreRepairRequest;
use App\Http\Requests\UpdateRepairRequest;
use App\Models\Repair;
use App\Models\RepairState;
use App\Models\User;
use App\Models\Vehicle;
use Gate;
use Illuminate\Http\Request;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpFoundation\Response;
use Yajra\DataTables\Facades\DataTables;

class RepairController extends Controller
{
    use MediaUploadingTrait, CsvImportTrait;

    public function index(Request $request)
    {
        abort_if(Gate::denies('repair_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        if ($request->ajax()) {
            $query = Repair::with(['vehicle', 'user', 'repair_state'])->select(sprintf('%s.*', (new Repair)->table));
            $table = Datatables::of($query);

            $table->addColumn('placeholder', '&nbsp;');
            $table->addColumn('actions', '&nbsp;');

            $table->editColumn('actions', function ($row) {
                $viewGate      = 'repair_show';
                $editGate      = 'repair_edit';
                $deleteGate    = 'repair_delete';
                $crudRoutePart = 'repairs';

                return view('partials.datatablesActions', compact(
                    'viewGate',
                    'editGate',
                    'deleteGate',
                    'crudRoutePart',
                    'row'
                ));
            });

            $table->editColumn('id', function ($row) {
                return $row->id ? $row->id : '';
            });
            $table->addColumn('vehicle_license', function ($row) {
                return $row->vehicle ? $row->vehicle->license : '';
            });

            $table->editColumn('obs_1', function ($row) {
                return $row->obs_1 ? $row->obs_1 : '';
            });
            $table->editColumn('checkin', function ($row) {
                if (! $row->checkin) {
                    return '';
                }
                $links = [];
                foreach ($row->checkin as $media) {
                    $links[] = '<a href="' . $media->getUrl() . '" target="_blank"><img src="' . $media->getUrl('thumb') . '" width="50px" height="50px"></a>';
                }

                return implode(' ', $links);
            });
            $table->addColumn('user_name', function ($row) {
                return $row->user ? $row->user->name : '';
            });

            $table->editColumn('kilometers', function ($row) {
                return $row->kilometers ? $row->kilometers : '';
            });
            $table->editColumn('front_windshield', function ($row) {
                return '<input type="checkbox" disabled ' . ($row->front_windshield ? 'checked' : null) . '>';
            });
            $table->editColumn('front_windshield_text', function ($row) {
                return $row->front_windshield_text ? $row->front_windshield_text : '';
            });
            $table->editColumn('front_lights', function ($row) {
                return '<input type="checkbox" disabled ' . ($row->front_lights ? 'checked' : null) . '>';
            });
            $table->editColumn('front_lights_text', function ($row) {
                return $row->front_lights_text ? $row->front_lights_text : '';
            });
            $table->editColumn('rear_lights', function ($row) {
                return '<input type="checkbox" disabled ' . ($row->rear_lights ? 'checked' : null) . '>';
            });
            $table->editColumn('rear_lights_text', function ($row) {
                return $row->rear_lights_text ? $row->rear_lights_text : '';
            });
            $table->editColumn('horn_functionality', function ($row) {
                return '<input type="checkbox" disabled ' . ($row->horn_functionality ? 'checked' : null) . '>';
            });
            $table->editColumn('horn_functionality_text', function ($row) {
                return $row->horn_functionality_text ? $row->horn_functionality_text : '';
            });
            $table->editColumn('wiper_blades_water_level', function ($row) {
                return '<input type="checkbox" disabled ' . ($row->wiper_blades_water_level ? 'checked' : null) . '>';
            });
            $table->editColumn('wiper_blades_water_level_text', function ($row) {
                return $row->wiper_blades_water_level_text ? $row->wiper_blades_water_level_text : '';
            });
            $table->editColumn('brake_clutch_oil_level', function ($row) {
                return '<input type="checkbox" disabled ' . ($row->brake_clutch_oil_level ? 'checked' : null) . '>';
            });
            $table->editColumn('brake_clutch_oil_level_text', function ($row) {
                return $row->brake_clutch_oil_level_text ? $row->brake_clutch_oil_level_text : '';
            });
            $table->editColumn('electrical_systems', function ($row) {
                return '<input type="checkbox" disabled ' . ($row->electrical_systems ? 'checked' : null) . '>';
            });
            $table->editColumn('electrical_systems_text', function ($row) {
                return $row->electrical_systems_text ? $row->electrical_systems_text : '';
            });
            $table->editColumn('engine_coolant_level', function ($row) {
                return '<input type="checkbox" disabled ' . ($row->engine_coolant_level ? 'checked' : null) . '>';
            });
            $table->editColumn('engine_coolant_level_text', function ($row) {
                return $row->engine_coolant_level_text ? $row->engine_coolant_level_text : '';
            });
            $table->editColumn('engine_oil_level', function ($row) {
                return '<input type="checkbox" disabled ' . ($row->engine_oil_level ? 'checked' : null) . '>';
            });
            $table->editColumn('engine_oil_level_text', function ($row) {
                return $row->engine_oil_level_text ? $row->engine_oil_level_text : '';
            });
            $table->editColumn('filters_air_cabin_oil_fuel', function ($row) {
                return '<input type="checkbox" disabled ' . ($row->filters_air_cabin_oil_fuel ? 'checked' : null) . '>';
            });
            $table->editColumn('filters_air_cabin_oil_fuel_text', function ($row) {
                return $row->filters_air_cabin_oil_fuel_text ? $row->filters_air_cabin_oil_fuel_text : '';
            });
            $table->editColumn('check_leaks_engine_gearbox_steering', function ($row) {
                return '<input type="checkbox" disabled ' . ($row->check_leaks_engine_gearbox_steering ? 'checked' : null) . '>';
            });
            $table->editColumn('check_leaks_engine_gearbox_steering_text', function ($row) {
                return $row->check_leaks_engine_gearbox_steering_text ? $row->check_leaks_engine_gearbox_steering_text : '';
            });
            $table->editColumn('brake_pads_disks', function ($row) {
                return '<input type="checkbox" disabled ' . ($row->brake_pads_disks ? 'checked' : null) . '>';
            });
            $table->editColumn('brake_pads_disks_text', function ($row) {
                return $row->brake_pads_disks_text ? $row->brake_pads_disks_text : '';
            });
            $table->editColumn('shock_absorbers', function ($row) {
                return '<input type="checkbox" disabled ' . ($row->shock_absorbers ? 'checked' : null) . '>';
            });
            $table->editColumn('shock_absorbers_text', function ($row) {
                return $row->shock_absorbers_text ? $row->shock_absorbers_text : '';
            });
            $table->editColumn('tire_condition', function ($row) {
                return '<input type="checkbox" disabled ' . ($row->tire_condition ? 'checked' : null) . '>';
            });
            $table->editColumn('tire_condition_text', function ($row) {
                return $row->tire_condition_text ? $row->tire_condition_text : '';
            });
            $table->editColumn('battery', function ($row) {
                return '<input type="checkbox" disabled ' . ($row->battery ? 'checked' : null) . '>';
            });
            $table->editColumn('battery_text', function ($row) {
                return $row->battery_text ? $row->battery_text : '';
            });
            $table->editColumn('spare_tire_vest_triangle_tools', function ($row) {
                return '<input type="checkbox" disabled ' . ($row->spare_tire_vest_triangle_tools ? 'checked' : null) . '>';
            });
            $table->editColumn('spare_tire_vest_triangle_tools_text', function ($row) {
                return $row->spare_tire_vest_triangle_tools_text ? $row->spare_tire_vest_triangle_tools_text : '';
            });
            $table->editColumn('check_clearance', function ($row) {
                return '<input type="checkbox" disabled ' . ($row->check_clearance ? 'checked' : null) . '>';
            });
            $table->editColumn('check_clearance_text', function ($row) {
                return $row->check_clearance_text ? $row->check_clearance_text : '';
            });
            $table->editColumn('check_shields', function ($row) {
                return '<input type="checkbox" disabled ' . ($row->check_shields ? 'checked' : null) . '>';
            });
            $table->editColumn('check_shields_text', function ($row) {
                return $row->check_shields_text ? $row->check_shields_text : '';
            });
            $table->editColumn('paint_condition', function ($row) {
                return '<input type="checkbox" disabled ' . ($row->paint_condition ? 'checked' : null) . '>';
            });
            $table->editColumn('paint_condition_text', function ($row) {
                return $row->paint_condition_text ? $row->paint_condition_text : '';
            });
            $table->editColumn('dents', function ($row) {
                return '<input type="checkbox" disabled ' . ($row->dents ? 'checked' : null) . '>';
            });
            $table->editColumn('dents_text', function ($row) {
                return $row->dents_text ? $row->dents_text : '';
            });
            $table->editColumn('diverse_strips', function ($row) {
                return '<input type="checkbox" disabled ' . ($row->diverse_strips ? 'checked' : null) . '>';
            });
            $table->editColumn('diverse_strips_text', function ($row) {
                return $row->diverse_strips_text ? $row->diverse_strips_text : '';
            });
            $table->editColumn('diverse_plastics_check_scratches', function ($row) {
                return '<input type="checkbox" disabled ' . ($row->diverse_plastics_check_scratches ? 'checked' : null) . '>';
            });
            $table->editColumn('diverse_plastics_check_scratches_text', function ($row) {
                return $row->diverse_plastics_check_scratches_text ? $row->diverse_plastics_check_scratches_text : '';
            });
            $table->editColumn('wheels', function ($row) {
                return '<input type="checkbox" disabled ' . ($row->wheels ? 'checked' : null) . '>';
            });
            $table->editColumn('wheels_text', function ($row) {
                return $row->wheels_text ? $row->wheels_text : '';
            });
            $table->editColumn('bolts_paint', function ($row) {
                return '<input type="checkbox" disabled ' . ($row->bolts_paint ? 'checked' : null) . '>';
            });
            $table->editColumn('bolts_paint_text', function ($row) {
                return $row->bolts_paint_text ? $row->bolts_paint_text : '';
            });
            $table->editColumn('seat_belts', function ($row) {
                return '<input type="checkbox" disabled ' . ($row->seat_belts ? 'checked' : null) . '>';
            });
            $table->editColumn('seat_belts_text', function ($row) {
                return $row->seat_belts_text ? $row->seat_belts_text : '';
            });
            $table->editColumn('radio', function ($row) {
                return '<input type="checkbox" disabled ' . ($row->radio ? 'checked' : null) . '>';
            });
            $table->editColumn('radio_text', function ($row) {
                return $row->radio_text ? $row->radio_text : '';
            });
            $table->editColumn('air_conditioning', function ($row) {
                return '<input type="checkbox" disabled ' . ($row->air_conditioning ? 'checked' : null) . '>';
            });
            $table->editColumn('air_conditioning_text', function ($row) {
                return $row->air_conditioning_text ? $row->air_conditioning_text : '';
            });
            $table->editColumn('front_rear_window_functionality', function ($row) {
                return '<input type="checkbox" disabled ' . ($row->front_rear_window_functionality ? 'checked' : null) . '>';
            });
            $table->editColumn('front_rear_window_functionality_text', function ($row) {
                return $row->front_rear_window_functionality_text ? $row->front_rear_window_functionality_text : '';
            });
            $table->editColumn('seats_upholstery', function ($row) {
                return '<input type="checkbox" disabled ' . ($row->seats_upholstery ? 'checked' : null) . '>';
            });
            $table->editColumn('seats_upholstery_text', function ($row) {
                return $row->seats_upholstery_text ? $row->seats_upholstery_text : '';
            });
            $table->editColumn('sun_visors', function ($row) {
                return '<input type="checkbox" disabled ' . ($row->sun_visors ? 'checked' : null) . '>';
            });
            $table->editColumn('sun_visors_text', function ($row) {
                return $row->sun_visors_text ? $row->sun_visors_text : '';
            });
            $table->editColumn('carpets', function ($row) {
                return '<input type="checkbox" disabled ' . ($row->carpets ? 'checked' : null) . '>';
            });
            $table->editColumn('carpets_text', function ($row) {
                return $row->carpets_text ? $row->carpets_text : '';
            });
            $table->editColumn('trunk_shelf', function ($row) {
                return '<input type="checkbox" disabled ' . ($row->trunk_shelf ? 'checked' : null) . '>';
            });
            $table->editColumn('trunk_shelf_text', function ($row) {
                return $row->trunk_shelf_text ? $row->trunk_shelf_text : '';
            });
            $table->editColumn('buttons', function ($row) {
                return '<input type="checkbox" disabled ' . ($row->buttons ? 'checked' : null) . '>';
            });
            $table->editColumn('buttons_text', function ($row) {
                return $row->buttons_text ? $row->buttons_text : '';
            });
            $table->editColumn('door_panels', function ($row) {
                return '<input type="checkbox" disabled ' . ($row->door_panels ? 'checked' : null) . '>';
            });
            $table->editColumn('door_panels_text', function ($row) {
                return $row->door_panels_text ? $row->door_panels_text : '';
            });
            $table->editColumn('locks', function ($row) {
                return '<input type="checkbox" disabled ' . ($row->locks ? 'checked' : null) . '>';
            });
            $table->editColumn('locks_text', function ($row) {
                return $row->locks_text ? $row->locks_text : '';
            });
            $table->editColumn('interior_covers_headlights_taillights', function ($row) {
                return '<input type="checkbox" disabled ' . ($row->interior_covers_headlights_taillights ? 'checked' : null) . '>';
            });
            $table->editColumn('interior_covers_headlights_taillights_text', function ($row) {
                return $row->interior_covers_headlights_taillights_text ? $row->interior_covers_headlights_taillights_text : '';
            });
            $table->editColumn('open_close_doors_remote_control_all_functions', function ($row) {
                return '<input type="checkbox" disabled ' . ($row->open_close_doors_remote_control_all_functions ? 'checked' : null) . '>';
            });
            $table->editColumn('open_close_doors_remote_control_all_functions_text', function ($row) {
                return $row->open_close_doors_remote_control_all_functions_text ? $row->open_close_doors_remote_control_all_functions_text : '';
            });
            $table->editColumn('turn_on_ac_check_glass', function ($row) {
                return '<input type="checkbox" disabled ' . ($row->turn_on_ac_check_glass ? 'checked' : null) . '>';
            });
            $table->editColumn('turn_on_ac_check_glass_text', function ($row) {
                return $row->turn_on_ac_check_glass_text ? $row->turn_on_ac_check_glass_text : '';
            });
            $table->editColumn('check_engine_lift_hood', function ($row) {
                return '<input type="checkbox" disabled ' . ($row->check_engine_lift_hood ? 'checked' : null) . '>';
            });
            $table->editColumn('check_engine_lift_hood_text', function ($row) {
                return $row->check_engine_lift_hood_text ? $row->check_engine_lift_hood_text : '';
            });
            $table->editColumn('connect_vehicle_to_scanner_check_errors', function ($row) {
                return '<input type="checkbox" disabled ' . ($row->connect_vehicle_to_scanner_check_errors ? 'checked' : null) . '>';
            });
            $table->editColumn('connect_vehicle_to_scanner_check_errors_text', function ($row) {
                return $row->connect_vehicle_to_scanner_check_errors_text ? $row->connect_vehicle_to_scanner_check_errors_text : '';
            });
            $table->editColumn('check_chassis_confirm_with_registration', function ($row) {
                return '<input type="checkbox" disabled ' . ($row->check_chassis_confirm_with_registration ? 'checked' : null) . '>';
            });
            $table->editColumn('check_chassis_confirm_with_registration_text', function ($row) {
                return $row->check_chassis_confirm_with_registration_text ? $row->check_chassis_confirm_with_registration_text : '';
            });
            $table->editColumn('manufacturer_plate', function ($row) {
                return '<input type="checkbox" disabled ' . ($row->manufacturer_plate ? 'checked' : null) . '>';
            });
            $table->editColumn('manufacturer_plate_text', function ($row) {
                return $row->manufacturer_plate_text ? $row->manufacturer_plate_text : '';
            });
            $table->editColumn('check_chassis_stickers', function ($row) {
                return '<input type="checkbox" disabled ' . ($row->check_chassis_stickers ? 'checked' : null) . '>';
            });
            $table->editColumn('check_chassis_stickers_text', function ($row) {
                return $row->check_chassis_stickers_text ? $row->check_chassis_stickers_text : '';
            });
            $table->editColumn('check_gearbox_oil', function ($row) {
                return '<input type="checkbox" disabled ' . ($row->check_gearbox_oil ? 'checked' : null) . '>';
            });
            $table->editColumn('check_gearbox_oil_text', function ($row) {
                return $row->check_gearbox_oil_text ? $row->check_gearbox_oil_text : '';
            });
            $table->editColumn('obs_2', function ($row) {
                return $row->obs_2 ? $row->obs_2 : '';
            });
            $table->editColumn('checkout', function ($row) {
                if (! $row->checkout) {
                    return '';
                }
                $links = [];
                foreach ($row->checkout as $media) {
                    $links[] = '<a href="' . $media->getUrl() . '" target="_blank"><img src="' . $media->getUrl('thumb') . '" width="50px" height="50px"></a>';
                }

                return implode(' ', $links);
            });

            $table->editColumn('name', function ($row) {
                return $row->name ? $row->name : '';
            });
            $table->addColumn('repair_state_name', function ($row) {
                return $row->repair_state ? $row->repair_state->name : '';
            });

            $table->rawColumns(['actions', 'placeholder', 'vehicle', 'checkin', 'user', 'front_windshield', 'front_lights', 'rear_lights', 'horn_functionality', 'wiper_blades_water_level', 'brake_clutch_oil_level', 'electrical_systems', 'engine_coolant_level', 'engine_oil_level', 'filters_air_cabin_oil_fuel', 'check_leaks_engine_gearbox_steering', 'brake_pads_disks', 'shock_absorbers', 'tire_condition', 'battery', 'spare_tire_vest_triangle_tools', 'check_clearance', 'check_shields', 'paint_condition', 'dents', 'diverse_strips', 'diverse_plastics_check_scratches', 'wheels', 'bolts_paint', 'seat_belts', 'radio', 'air_conditioning', 'front_rear_window_functionality', 'seats_upholstery', 'sun_visors', 'carpets', 'trunk_shelf', 'buttons', 'door_panels', 'locks', 'interior_covers_headlights_taillights', 'open_close_doors_remote_control_all_functions', 'turn_on_ac_check_glass', 'check_engine_lift_hood', 'connect_vehicle_to_scanner_check_errors', 'check_chassis_confirm_with_registration', 'manufacturer_plate', 'check_chassis_stickers', 'check_gearbox_oil', 'checkout', 'repair_state']);

            return $table->make(true);
        }

        $vehicles      = Vehicle::get();
        $users         = User::get();
        $repair_states = RepairState::get();

        return view('admin.repairs.index', compact('vehicles', 'users', 'repair_states'));
    }

    public function create()
    {
        abort_if(Gate::denies('repair_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $vehicles = Vehicle::pluck('license', 'id')->prepend(trans('global.pleaseSelect'), '');

        $users = User::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        $repair_states = RepairState::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        return view('admin.repairs.create', compact('repair_states', 'users', 'vehicles'));
    }

    public function store(StoreRepairRequest $request)
    {
        $repair = Repair::create($request->all());

        foreach ($request->input('checkin', []) as $file) {
            $repair->addMedia(storage_path('tmp/uploads/' . basename($file)))->toMediaCollection('checkin');
        }

        foreach ($request->input('checkout', []) as $file) {
            $repair->addMedia(storage_path('tmp/uploads/' . basename($file)))->toMediaCollection('checkout');
        }

        if ($media = $request->input('ck-media', false)) {
            Media::whereIn('id', $media)->update(['model_id' => $repair->id]);
        }

        return redirect()->route('admin.repairs.index');
    }

    public function edit(Repair $repair)
    {
        abort_if(Gate::denies('repair_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $vehicles = Vehicle::pluck('license', 'id')->prepend(trans('global.pleaseSelect'), '');

        $users = User::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        $repair_states = RepairState::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        $repair->load('vehicle', 'user', 'repair_state');

        return view('admin.repairs.edit', compact('repair', 'repair_states', 'users', 'vehicles'));
    }

    public function update(UpdateRepairRequest $request, Repair $repair)
    {
        $repair->update($request->all());

        if (count($repair->checkin) > 0) {
            foreach ($repair->checkin as $media) {
                if (! in_array($media->file_name, $request->input('checkin', []))) {
                    $media->delete();
                }
            }
        }
        $media = $repair->checkin->pluck('file_name')->toArray();
        foreach ($request->input('checkin', []) as $file) {
            if (count($media) === 0 || ! in_array($file, $media)) {
                $repair->addMedia(storage_path('tmp/uploads/' . basename($file)))->toMediaCollection('checkin');
            }
        }

        if (count($repair->checkout) > 0) {
            foreach ($repair->checkout as $media) {
                if (! in_array($media->file_name, $request->input('checkout', []))) {
                    $media->delete();
                }
            }
        }
        $media = $repair->checkout->pluck('file_name')->toArray();
        foreach ($request->input('checkout', []) as $file) {
            if (count($media) === 0 || ! in_array($file, $media)) {
                $repair->addMedia(storage_path('tmp/uploads/' . basename($file)))->toMediaCollection('checkout');
            }
        }

        return redirect()->route('admin.repairs.index');
    }

    public function show(Repair $repair)
    {
        abort_if(Gate::denies('repair_show'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $repair->load('vehicle', 'user', 'repair_state');

        return view('admin.repairs.show', compact('repair'));
    }

    public function destroy(Repair $repair)
    {
        abort_if(Gate::denies('repair_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $repair->delete();

        return back();
    }

    public function massDestroy(MassDestroyRepairRequest $request)
    {
        $repairs = Repair::find(request('ids'));

        foreach ($repairs as $repair) {
            $repair->delete();
        }

        return response(null, Response::HTTP_NO_CONTENT);
    }

    public function storeCKEditorImages(Request $request)
    {
        abort_if(Gate::denies('repair_create') && Gate::denies('repair_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $model         = new Repair();
        $model->id     = $request->input('crud_id', 0);
        $model->exists = true;
        $media         = $model->addMediaFromRequest('upload')->toMediaCollection('ck-media');

        return response()->json(['id' => $media->id, 'url' => $media->getUrl()], Response::HTTP_CREATED);
    }
}
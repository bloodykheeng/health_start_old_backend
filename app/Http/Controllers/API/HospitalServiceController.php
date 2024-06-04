<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\HospitalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HospitalServiceController extends Controller
{
    public function index(Request $request)
    {
        $query = HospitalService::with(['hospital', 'service', 'createdBy', 'updatedBy']);

        if ($request->has('hospital_id')) {
            $query->where('hospital_id', $request->hospital_id);
        }

        // You can add more filters here as needed, for example:
        if ($request->has('service_id')) {
            $query->where('service_id', $request->service_id);
        }

        $hospitalServices = $query->get();

        return response()->json(['data' => $hospitalServices], 200);
    }
    public function show($id)
    {
        $hospitalService = HospitalService::with(['hospital', 'service', 'createdBy', 'updatedBy'])->find($id);

        if (!$hospitalService) {
            return response()->json(['message' => 'Hospital Service not found'], 404);
        }

        return response()->json($hospitalService, 200);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'hospital_id' => 'required|integer|exists:hospitals,id',
            'services' => 'required|array|min:1',
            'services.*.id' => 'required|integer|exists:services,id',
            'services.*.name' => 'required|string',
        ]);

        DB::beginTransaction();

        try {
            foreach ($validated['services'] as $service) {
                if (HospitalService::where('hospital_id', $validated['hospital_id'])
                    ->where('service_id', $service['id'])
                    ->exists()) {
                    DB::rollBack();
                    return response()->json(['message' => 'The combination of hospital ID and service ID already exists.'], 400);
                }
            }

            foreach ($validated['services'] as $service) {
                HospitalService::create([
                    'hospital_id' => $validated['hospital_id'],
                    'service_id' => $service['id'],
                    'created_by' => auth()->id(),
                    'updated_by' => auth()->id(),
                ]);
            }

            DB::commit();

            return response()->json(['message' => 'Hospital Services added successfully'], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'An error occurred while adding Hospital Services.'], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $hospitalService = HospitalService::find($id);

        if (!$hospitalService) {
            return response()->json(['message' => 'Hospital Service not found'], 404);
        }

        $validated = $request->validate([
            'hospital_id' => 'required|integer|exists:hospitals,id',
            'service_id' => 'required|integer|exists:services,id',
            'no_of_points' => 'required|numeric',
        ]);

        DB::beginTransaction();

        try {
            if (HospitalService::where('hospital_id', $validated['hospital_id'])
                ->where('service_id', $validated['service_id'])
                ->where('id', '!=', $id)
                ->exists()) {
                DB::rollBack();
                return response()->json(['message' => 'The combination of hospital ID and service ID already exists.'], 400);
            }

            $hospitalService->update([
                'hospital_id' => $validated['hospital_id'],
                'service_id' => $validated['service_id'],
                'no_of_points' => $validated['no_of_points'],
                'updated_by' => auth()->id(),
            ]);

            DB::commit();

            return response()->json(['message' => 'Hospital Service updated successfully'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'An error occurred while updating Hospital Service.', 'error' => $e->getMessage()], 500);
        }
    }public function destroy($id)
    {
        $hospitalService = HospitalService::find($id);

        if (!$hospitalService) {
            return response()->json(['message' => 'Hospital Service not found'], 404);
        }

        $hospitalService->delete();
        return response()->json(['message' => 'Hospital Service deleted successfully'], 200);
    }
}
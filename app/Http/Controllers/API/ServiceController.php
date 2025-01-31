<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Hospital;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class ServiceController extends Controller
{
    public function index(Request $request)
    {
        $query = Service::with(['createdBy', 'updatedBy']);

        // Apply search filter (if provided)
        $search = $request->query('search');
        if ($search) {
            $query->where('name', 'like', "%{$search}%");
        }

        // Apply filter by hospital_id
        $hospitalId = $request->query('hospital_id');
        if ($hospitalId) {
            // Find the hospital and eagerly load its services
            $hospital = Hospital::with('services')->find($hospitalId);
            if ($hospital) {
                // Get the ids of services attached to the hospital
                $serviceIds = $hospital->services->pluck('id')->toArray();
                // Filter the query by these service IDs
                $query->whereIn('id', $serviceIds);
            } else {
                // If no hospital found, return an empty result
                return response()->json(['message' => 'Hospital not found'], 401);
            }
        }

        // Apply orderBy and orderDirection if both are provided
        // $orderBy = $request->query('orderBy');
        // $orderDirection = $request->query('orderDirection', 'asc');

        // if ($orderBy && $orderDirection) {
        //     if (in_array($orderDirection, ['asc', 'desc'])) {
        //         if (Schema::hasColumn('services', $orderBy)) {
        //             $query->orderBy($orderBy, $orderDirection);
        //         }
        //     }
        // }

        // Pagination
        // $perPage = $request->query('per_page', 10);
        // $page = $request->query('page', 1);

        // $paginatedServices = $query->paginate($perPage);

        // return response()->json($paginatedServices);

        $data = $query->get();

        return response()->json(["data" => $data]);
    }

    public function show($id)
    {
        $service = Service::with(['createdBy', 'updatedBy'])->find($id);

        return response()->json($service);
    }

    public function store(Request $request)
    {

        // $requestData = $request->all();

        // return response()->json(['message' => 'Unauthorized', 'permoissionData' => $requestData], 403);
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'required|string|max:255',
            'photo' => 'nullable|file|mimes:jpg,jpeg,png|max:2048',
        ]);

        $validatedData['created_by'] = Auth::id();
        $validatedData['updated_by'] = Auth::id();

        DB::beginTransaction();

        try {

            $photoUrl = null;
            if ($request->hasFile('photo')) {

                $photoUrl = $this->uploadPhoto($request->file('photo'), 'service_photos'); // Save the photo in a specific folder
                $validatedData['photo_url'] = $photoUrl;
            }

            $service = Service::create($validatedData);

            DB::commit();
            return response()->json(['message' => 'Service created successfully!', 'service' => $service], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Service creation failed: ' . $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'required|string|max:255',
            'photo_url' => 'nullable|string|max:255',
        ]);

        $service = Service::find($id);
        if (!$service) {
            return response()->json(['message' => 'Service not found'], 404);
        }

        $validatedData['updated_by'] = Auth::id();

        DB::beginTransaction();

        try {

            $photoUrl = $service->photo_url;
            if ($request->hasFile('photo')) {
                // Delete old photo if it exists
                if ($photoUrl) {
                    $this->deletePhoto($photoUrl);
                }
                $photoUrl = $this->uploadPhoto($request->file('photo'), 'spare_part_photos');
                $validated['photo_url'] = $photoUrl;
            }
            $service->update($validatedData);

            DB::commit();
            return response()->json(['message' => 'Service updated successfully!', 'service' => $service], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Update failed: ' . $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        $service = Service::find($id);
        if (!$service) {
            return response()->json(['message' => 'Service not found'], 404);
        }

        $service->delete();

        return response()->json(['message' => 'Service deleted successfully']);
    }

    private function uploadPhoto($photo, $folderPath)
    {
        $publicPath = public_path($folderPath);
        if (!File::exists($publicPath)) {
            File::makeDirectory($publicPath, 0777, true, true);
        }

        $fileName = time() . '_' . $photo->getClientOriginalName();
        $photo->move($publicPath, $fileName);

        return '/' . $folderPath . '/' . $fileName;
    }

    private function deletePhoto($photoUrl)
    {
        $photoPath = parse_url($photoUrl, PHP_URL_PATH);
        $photoPath = public_path($photoPath);
        if (File::exists($photoPath)) {
            File::delete($photoPath);
        }
    }
}
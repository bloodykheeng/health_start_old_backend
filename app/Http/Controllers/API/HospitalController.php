<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Hospital;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class HospitalController extends Controller
{
    public function index(Request $request)
    {
        $query = Hospital::with(['createdBy', 'updatedBy']);

        // Apply search filter (if provided)
        $search = $request->query('search');
        if ($search) {
            $query->where(function ($subQuery) use ($search) {
                $subQuery->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('city', 'like', "%{$search}%")
                    ->orWhere('state', 'like', "%{$search}%")
                    ->orWhere('country', 'like', "%{$search}%");
            });
        }

        // Apply orderBy and orderDirection if both are provided
        // $orderBy = $request->query('orderBy');
        // $orderDirection = $request->query('orderDirection', 'asc');

        // if ($orderBy && $orderDirection) {
        //     // Validate orderDirection
        //     if (in_array($orderDirection, ['asc', 'desc'])) {
        //         // Check if the column exists in the hospitals table
        //         if (Schema::hasColumn('hospitals', $orderBy)) {
        //             $query->orderBy($orderBy, $orderDirection);
        //         }
        //     }
        // }

        $query->latest();

        // Pagination
        // $perPage = $request->query('per_page', 10); // Default to 10 per page
        // $page = $request->query('page', 1); // Default to first page

        // $paginatedHospitals = $query->paginate($perPage);

        // return response()->json($paginatedHospitals);

        $data = $query->get();

        return response()->json(["data" => $data]);
    }

    public function show($id)
    {
        $hospital = Hospital::with(['createdBy', 'updatedBy'])->find($id);

        if (!$hospital) {
            return response()->json(['message' => 'Hospital not found'], 404);
        }

        return response()->json($hospital);
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'required|string|max:255',
            'city' => 'required|string|max:255',
            'state' => 'required|string|max:255',
            'country' => 'required|string|max:255',
            'zip_code' => 'required|string|max:10',
            'phone_number' => 'required|string|max:15',
            'email' => 'required|string|email|max:255|unique:hospitals',
            'website' => 'nullable|url|max:255',
            'capacity' => 'required|integer|min:1',
            'status' => 'required|string|max:255',
        ]);

        $validatedData['created_by'] = Auth::id();
        $validatedData['updated_by'] = Auth::id();

        DB::beginTransaction();

        try {

            $photoUrl = null;
            if ($request->hasFile('photo')) {
                $photoUrl = $this->uploadPhoto($request->file('photo'), 'hospital_photos'); // Save the photo in a specific folder
                $validatedData['photo_url'] = $photoUrl;
            }

            $hospital = Hospital::create($validatedData);

            DB::commit();
            return response()->json(['message' => 'Hospital created successfully!', 'hospital' => $hospital], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Hospital creation failed: ' . $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'required|string|max:255',
            'city' => 'required|string|max:255',
            'state' => 'required|string|max:255',
            'country' => 'required|string|max:255',
            'zip_code' => 'required|string|max:10',
            'phone_number' => 'required|string|max:15',
            'email' => 'required|string|email|max:255|unique:hospitals,email,' . $id,
            'website' => 'nullable|url|max:255',
            'capacity' => 'required|integer|min:1',
            'status' => 'required|string|max:255',
        ]);

        $hospital = Hospital::find($id);
        if (!$hospital) {
            return response()->json(['message' => 'Hospital not found'], 404);
        }

        DB::beginTransaction();

        try {

            $validatedData['updated_by'] = Auth::id();

            $photoUrl = $hospital->photo_url;
            if ($request->hasFile('photo')) {
                // Delete old photo if it exists
                if ($photoUrl) {
                    $this->deletePhoto($photoUrl);
                }
                $photoUrl = $this->uploadPhoto($request->file('photo'), 'spare_part_photos');
                $validatedData['photo_url'] = $photoUrl;
            }
            $hospital->update($validatedData);

            DB::commit();
            return response()->json(['message' => 'Hospital updated successfully!', 'hospital' => $hospital], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Update failed: ' . $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        $hospital = Hospital::find($id);
        if (!$hospital) {
            return response()->json(['message' => 'Hospital not found'], 404);
        }

        $hospital->delete();

        return response()->json(['message' => 'Hospital deleted successfully']);
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
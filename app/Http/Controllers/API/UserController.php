<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\HospitalUser;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index(Request $request)
    {
        // Uncomment and use this if you need authorization check
        // if (!Auth::user()->can('view users')) {
        //     return response()->json(['message' => 'Unauthorized'], 403);
        // }

        $query = User::with(['hospitals', 'hospitalUsers.hospital']);

        // Check if hospital_id is provided and not null
        if ($request->has('hospital_id') && $request->hospital_id !== null) {
            // Filter users by the provided hospital_id
            $query->whereHas('hospitals', function ($query) use ($request) {
                $query->where('hospital_id', $request->hospital_id);
            });
        }

        // Filter by role if provided
        if ($request->has('role') && $request->role !== null) {
            $query->role($request->role); // This uses the role scope provided by Spatie's permission package
        }

        // Apply search filter (if provided)
        $search = $request->query('search');
        if ($search) {
            $query->where(function ($subQuery) use ($search) {
                $subQuery->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Apply orderBy and orderDirection if both are provided
        $orderBy = $request->query('orderBy');
        $orderDirection = $request->query('orderDirection', 'asc');

        // if (isset($orderBy) && isset($orderDirection)) {
        //     // Validate orderDirection
        //     if (in_array($orderDirection, ['asc', 'desc'])) {
        //         // Check if the column exists in the users table
        //         if (Schema::hasColumn('users', $orderBy)) {
        //             $query->orderBy($orderBy, $orderDirection);
        //         }
        //     }
        // }

        $query->latest();
        // Pagination
        $perPage = $request->query('per_page', 10); // Default to 10 per page
        $page = $request->query('page', 1); // Default to first page

        $paginatedUsers = $query->paginate($perPage);

        // Adding role names and permissions to each user in the data collection
        $paginatedUsers->getCollection()->transform(function ($user) {
            $user->role = $user->getRoleNames()->first() ?? "";
            $user->permissions = $user->getAllPermissions()->pluck('name') ?? null;
            return $user;
        });

        // Return the paginated response
        return response()->json($paginatedUsers);
    }

    public function show($id)
    {
        // if (!Auth::user()->can('view user')) {
        //     return response()->json(['message' => 'Unauthorized'], 403);
        // }

        // $user = User::with(["vendors.vendor"])->findOrFail($id);
        $user = User::with(['hospitals', 'hospitalUsers.hospital'])->findOrFail($id);

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        // Adding role name
        $user->role = $user->getRoleNames()->first() ?? "";

        return response()->json($user);
    }

    public function store(Request $request)
    {
        // Check permission
        // if (!Auth::user()->can('create user')) {
        //     return response()->json(['message' => 'Unauthorized'], 403);
        // }

        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:255|unique:users',
            'email' => 'nullable|string|email|max:255|unique:users',
            'status' => 'required|string|max:255',
            'dateOfBirth' => 'required|date',
            'lastlogin' => 'nullable|date',
            'password' => 'required|string|min:8',
            'role' => 'required|exists:roles,name',
            // 'vendor_id' => 'nullable|exists:vendors,id', // validate vendor_id
            'photo' => 'nullable|file|mimes:jpg,jpeg,png|max:2048', // Expect a file for the photo
            'health_facilities' => 'nullable|array', // Ensure hospitals is an array
        ]);

        if (empty($request->phone) && empty($request->email)) {
            return response()->json([
                'message' => 'Either phone or email is required.',
            ], 400); // 400 Bad Request
        }

        $photoUrl = null;
        if ($request->hasFile('photo')) {
            $photoUrl = $this->uploadPhoto($request->file('photo'), 'user_photos'); // Save the photo in a specific folder
        }

        DB::beginTransaction();

        try {

            // Create user
            $user = User::create([
                'name' => $validatedData['name'],
                'phone' => $validatedData['phone'],
                'email' => $validatedData['email'],
                'dateOfBirth' => $validatedData['dateOfBirth'],
                'status' => $validatedData['status'],
                'lastlogin' => $validatedData['lastlogin'] ?? now(),
                'password' => Hash::make($validatedData['password']),
                'photo_url' => $photoUrl,
            ]);

            // Sync the user's role
            $user->syncRoles([$validatedData['role']]);

            // Attach hospitals if provided
            if (isset($validatedData['health_facilities'])) {
                foreach ($validatedData['health_facilities'] as $hospitalData) {

                    $hospitalUser = HospitalUser::firstOrNew([
                        'user_id' => $user->id,
                        'hospital_id' => $hospitalData['id'],
                    ]);

                    if (!$hospitalUser->exists) {
                        $hospitalUser->save();
                    } else {
                        throw new \Exception("User is already associated with hospital '{$hospitalData['name']}'");
                    }
                }
            }

            // Optionally get permissions associated with the user's role
            // $permissions = Permission::whereIn('id', $user->roles->first()->permissions->pluck('id'))->pluck('name');
            // $user->permissions = $permissions;

            // Handle UserVendor relationship
            // if (isset($validatedData['vendor_id'])) {
            //     $user->vendors()->create(['vendor_id' => $validatedData['vendor_id']]);
            // }

            DB::commit();
            return response()->json(['message' => 'User created successfully!', 'user' => $user], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'User creation failed: ' . $e->getMessage()], 500);
        }
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

    public function update(Request $request, $id)
    {

        // Check permission
        // if (!Auth::user()->can('update user')) {
        //     return response()->json(['message' => 'Unauthorized'], 403);
        // }
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $id,
            'phone' => 'nullable|string|max:255|unique:users,phone,' . $id,
            'status' => 'required|string|max:255',
            'dateOfBirth' => 'required|date',
            'lastlogin' => 'nullable|date',
            'photo' => 'nullable|file|mimes:jpg,jpeg,png|max:2048', // Validation for photo
            'role' => 'sometimes|exists:roles,name',
            // 'vendor_id' => 'nullable|exists:vendors,id',
            // 'health_facilities' => 'nullable|array', // Ensure hospitals is an array
        ]);

        $healthFacilities = json_decode($request->input('health_facilities'), true);

        // return response()->json(['message' => 'testing', 'data' => $healthFacilities], 400);

        // Ensure healthFacilities is an array
        if (isset($healthFacilities) && !is_array($healthFacilities)) {
            return response()->json(['message' => 'health_facilities must be an array'], 400);
        }

        $user = User::find($id);
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $photoUrl = $user->photo_url;
        if ($request->hasFile('photo')) {
            // Delete old photo if exists
            if ($photoUrl) {
                $photoPath = parse_url($photoUrl, PHP_URL_PATH);
                $photoPath = ltrim($photoPath, '/');
                if (file_exists(public_path($photoPath))) {
                    unlink(public_path($photoPath));
                }
            }
            $photoUrl = $this->uploadPhoto($request->file('photo'), 'user_photos');
        }

        DB::beginTransaction();

        try {
            $user->update([
                'name' => $validatedData['name'],
                'phone' => $validatedData['phone'],
                'email' => $validatedData['email'],
                'dateOfBirth' => $validatedData['dateOfBirth'],
                'status' => $validatedData['status'],
                'lastlogin' => $validatedData['lastlogin'] ?? now(),
                'photo_url' => $photoUrl,
            ]);

            if (isset($validatedData['role'])) {
                $user->syncRoles([$validatedData['role']]);
            }

            // Update or attach hospitals if provided
            if (isset($healthFacilities)) {
                $newHospitalIds = collect($healthFacilities)->pluck('id')->toArray();

                // Remove existing hospitals not in the new list
                $user->hospitalUsers()->whereNotIn('hospital_id', $newHospitalIds)->delete();

                foreach ($healthFacilities as $hospitalData) {
                    $hospitalUser = HospitalUser::firstOrNew([
                        'user_id' => $user->id,
                        'hospital_id' => $hospitalData['id'],
                    ]);

                    if (!$hospitalUser->exists) {
                        $hospitalUser->save();
                    }
                    // else {
                    //     throw new \Exception("User is already associated with hospital '{$hospitalData['name']}'");
                    // }
                }
            }

            DB::commit();
            return response()->json(['message' => 'User updated successfully!', 'user' => $user], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Update failed: ' . $e->getMessage()], 500);
        }
    }

    // ========================== destroy ====================

    public function destroy($id)
    {

        // if (!Auth::user()->can('delete user')) {
        //     return response()->json(['message' => 'Unauthorized'], 403);
        // }
        $user = User::find($id);
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        // Delete user photo if exists
        if ($user->photo_url) {
            $photoPath = parse_url($user->photo_url, PHP_URL_PATH);
            $photoPath = ltrim($photoPath, '/');
            if (file_exists(public_path($photoPath))) {
                unlink(public_path($photoPath));
            }
        }

        $user->delete();

        return response()->json(['message' => 'User deleted successfully']);
    }
}
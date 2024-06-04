<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\UserPoint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class UserPointController extends Controller
{
    public function index(Request $request)
    {
        $query = UserPoint::with(['user', 'hospital', 'createdBy', 'updatedBy']);

        // Apply search filter (if provided)
        $search = $request->query('search');
        if ($search) {
            $query->where(function ($subQuery) use ($search) {
                $subQuery->where('details', 'like', "%{$search}%")
                    ->orWhere('payment_method', 'like', "%{$search}%");
            });
        }

        // Apply user_id and hospital_id filters if provided
        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->has('hospital_id')) {
            $query->where('hospital_id', $request->hospital_id);
        }

        // // Apply orderBy and orderDirection if both are provided
        // $orderBy = $request->query('orderBy');
        // $orderDirection = $request->query('orderDirection', 'asc');

        // if ($orderBy && $orderDirection) {
        //     // Validate orderDirection
        //     if (in_array($orderDirection, ['asc', 'desc'])) {

        //         $orderBy = $request->query('orderBy', 'created_at');
        //         $orderDirection = $request->query('orderDirection', 'desc');

        //         $query->orderBy($orderBy, $orderDirection);
        //     }
        // }

        $query->latest();

        // // Pagination
        // $perPage = $request->query('per_page', 10); // Default to 10 per page
        // $userPoints = $query->paginate($perPage);

        // return response()->json($userPoints);

        $data = $query->get();

        return response()->json(["data" => $data]);
    }

    public function show($id)
    {
        $userPoint = UserPoint::with(['user', 'hospital', 'createdBy', 'updatedBy'])->find($id);

        if (!$userPoint) {
            return response()->json(['message' => 'UserPoint not found'], 404);
        }

        return response()->json($userPoint);
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'user_id' => 'required|exists:users,id',
            'hospital_id' => 'nullable|exists:hospitals,id',
            'no_of_points' => 'required|numeric',
            'price' => 'required|numeric',
            'payment_method' => 'required|string',
            'details' => 'nullable|string',
        ]);

        $validatedData['created_by'] = Auth::id();
        $validatedData['updated_by'] = Auth::id();

        DB::beginTransaction();

        try {
            $userPoint = UserPoint::create($validatedData);

            DB::commit();
            return response()->json(['message' => 'UserPoint created successfully!', 'userPoint' => $userPoint], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'UserPoint creation failed: ' . $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $validatedData = $request->validate([
            'user_id' => 'required|exists:users,id',
            'hospital_id' => 'nullable|exists:hospitals,id',
            'no_of_points' => 'required|numeric',
            'price' => 'required|numeric',
            'payment_method' => 'required|string',
            'details' => 'nullable|string',
        ]);

        $userPoint = UserPoint::find($id);
        if (!$userPoint) {
            return response()->json(['message' => 'UserPoint not found'], 404);
        }

        DB::beginTransaction();

        try {
            $validatedData['updated_by'] = Auth::id();

            $userPoint->update($validatedData);

            DB::commit();
            return response()->json(['message' => 'UserPoint updated successfully!', 'userPoint' => $userPoint], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Update failed: ' . $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        $userPoint = UserPoint::find($id);
        if (!$userPoint) {
            return response()->json(['message' => 'UserPoint not found'], 404);
        }

        $userPoint->delete();

        return response()->json(['message' => 'UserPoint deleted successfully']);
    }
}
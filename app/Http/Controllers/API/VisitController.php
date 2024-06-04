<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Visit;
use App\Models\VisitService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class VisitController extends Controller
{
    public function index(Request $request)
    {
        // Start building the query
        $query = Visit::with(['user', 'hospital', 'createdBy', 'updatedBy', 'visitServices.hospitalService.service', 'hospitalServices.service']);

        // Get the currently authenticated user
        /** @var \App\Models\User */
        $user = Auth::user();

        // Check if the user has a specific role and apply the filter
        // if ($user && $user->hasRole('SomeRole')) {
        //     $query->where('some_column', $some_value);
        // }

        // Apply filters
        if ($request->has('hospital_id')) {
            $query->where('hospital_id', $request->input('hospital_id'));
        }
        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        // Execute the query and get the results
        $visits = $query->get();

        return response()->json(['data' => $visits]);
    }

    public function show($id)
    {
        // Include related models in the with() method to fetch their data
        $visit = Visit::with(['user', 'hospital', 'createdBy', 'updatedBy', 'visitServices.hospitalService.service'])->find($id);
        if (!$visit) {
            return response()->json(['message' => 'Visit not found'], 404);
        }
        return response()->json($visit);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'hospital_id' => 'required|exists:hospitals,id',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'no_of_points' => 'required|numeric',
            'purpose' => 'nullable|string',
            'doctor_name' => 'nullable|string',
            'details' => 'nullable|string',
            'status' => 'nullable|string',
            'visit_services' => 'nullable|array',
            'visit_services.*.id' => 'required_with:visit_services|exists:hospital_services,id',
        ]);

        try {
            // Start a database transaction
            DB::beginTransaction();

            $validated['created_by'] = Auth::id();
            $validated['updated_by'] = Auth::id();

            // Create the Visit
            $visit = Visit::create($validated);

            if (isset($validated['visit_services'])) {
                foreach ($validated['visit_services'] as $service) {
                    // Create each VisitService record
                    VisitService::create([
                        'visit_id' => $visit->id,
                        'hospital_services_id' => $service['id'],
                        'created_by' => Auth::id(),
                        'updated_by' => Auth::id(),
                    ]);
                }
            }

            // Commit the transaction if all operations succeed
            DB::commit();

            // Load hospitalService relationship with visitServices
            $visit->load('visitServices.hospitalService');

            return response()->json(['message' => 'Visit created successfully', 'data' => $visit], 201);

        } catch (\Exception $e) {
            // Rollback the transaction if an exception occurs
            DB::rollback();

            // Handle the exception, log it, or return an appropriate error response
            return response()->json(['message' => 'Failed to create visit', 'error' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $visit = Visit::find($id);
        if (!$visit) {
            return response()->json(['message' => 'Visit not found'], 404);
        }

        $validated = $request->validate([
            'user_id' => 'sometimes|exists:users,id',
            'hospital_id' => 'sometimes|exists:hospitals,id',
            'no_of_points' => 'required|numeric',
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date|after_or_equal:start_date',
            'purpose' => 'nullable|string',
            'doctor_name' => 'nullable|string',
            'details' => 'nullable|string',
            'status' => 'nullable|string',
            'visit_services' => 'nullable|array',
            'visit_services.*.id' => 'required_with:visit_services|exists:hospital_services,id',
        ]);

        try {
            // Start a database transaction
            DB::beginTransaction();

            $validated['updated_by'] = Auth::id();

            $visit->update($validated);

            // Delete existing visit services
            VisitService::where('visit_id', $visit->id)->delete();

            // Add updated visit services
            if (isset($validated['visit_services'])) {
                foreach ($validated['visit_services'] as $service) {
                    VisitService::create([
                        'visit_id' => $visit->id,
                        'hospital_services_id' => $service['id'],
                        'created_by' => Auth::id(),
                        'updated_by' => Auth::id(),
                    ]);
                }
            }

            // Commit the transaction if all operations succeed
            DB::commit();

            // Reload the visit with updated visit services and their associated service information
            $visit->load('visitServices.hospitalService');

            return response()->json(['message' => 'Visit updated successfully', 'data' => $visit]);

        } catch (\Exception $e) {
            // Rollback the transaction if an exception occurs
            DB::rollback();

            // Handle the exception, log it, or return an appropriate error response
            return response()->json(['message' => 'Failed to update visit', 'error' => $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        $visit = Visit::find($id);
        if (!$visit) {
            return response()->json(['message' => 'Visit not found'], 404);
        }

        $visit->delete();

        return response()->json(null, 204);
    }
}
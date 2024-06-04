<?php

use App\Http\Controllers\API\HospitalController;
use App\Http\Controllers\API\HospitalServiceController;
use App\Http\Controllers\API\ServiceController;
use App\Http\Controllers\API\UserController;
use App\Http\Controllers\API\UserPermissionsController;
use App\Http\Controllers\API\UserPointController;
use App\Http\Controllers\API\UserRolesController;
use App\Http\Controllers\API\VisitController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PasswordResetController;
use Illuminate\Support\Facades\Route;

// Public Routes
Route::post('/register', [AuthController::class, 'register']);
// Route::post('/login', [AuthController::class, 'login']);

//check if user is still logged in
// Route::get('/user', [AuthController::class, 'checkLoginStatus']);
Route::middleware('auth:sanctum')->get('/user', [AuthController::class, 'checkLoginStatus']);

Route::post('/login', [AuthController::class, 'login'])->name('login');
Route::post('/third-party-login-auth', [AuthController::class, 'thirdPartyLoginAuthentication'])->name('thirdPartyLoginAuthentication');
Route::post('/third-party-register-auth', [AuthController::class, 'thirdPartyRegisterAuthentication'])->name('thirdPartyRegisterAuthentication');

Route::post('forgot-password', [PasswordResetController::class, 'forgetPassword']);
Route::get('/reset-password', [PasswordResetController::class, 'handleresetPasswordLoad']);
Route::post('/reset-password', [PasswordResetController::class, 'handlestoringNewPassword']);

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');

//=============================== private routes ==================================
Route::group(
    ['middleware' => ['auth:sanctum']],
    function () {
        // Vendor routes

        //======================== User Management =================================
        Route::Resource('users', UserController::class);

        //Roles AND Permisions
        Route::get('/roles', [UserRolesController::class, 'index']);
        Route::get('/getAssignableRoles', [UserRolesController::class, 'getAssignableRoles']);

        Route::Resource('users-roles', UserRolesController::class);
        Route::Post('users-roles-addPermissionsToRole', [UserRolesController::class, 'addPermissionsToRole']);
        Route::Post('users-roles-deletePermissionFromRole', [UserRolesController::class, 'deletePermissionFromRole']);

        Route::Resource('users-permissions', UserPermissionsController::class);
        Route::get('users-permissions-permissionNotInCurrentRole/{id}', [UserPermissionsController::class, 'permissionNotInCurrentRole']);

        // Sync permision to roles
        Route::get('roles-with-modified-permissions', [UserRolesController::class, 'getRolesWithModifiedPermissions']);

        Route::post('sync-permissions-to-role', [UserRolesController::class, 'syncPermissionsToRole']);

        //================================== Hospitals ===============================
        Route::apiResource('hospitals', HospitalController::class);
        Route::resource('hospital-services', HospitalServiceController::class);

        //======================== services============================
        Route::apiResource('services', ServiceController::class);

        //=================== user points ====================================
        Route::apiResource('user-points', UserPointController::class);

        //================== visits ========================================
        Route::get('visits', VisitController::class);
    }
);
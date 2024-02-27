<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

//users
Route::post('/login', [UserController::class, 'loginAPI']);
Route::get('users', [UserController::class, 'users']);

//tasks
Route::get('tasks', [UserController::class, 'tasks']);

// Filter by status || Date || Assigned user
Route::get('/get_tasks/status/{status}', [UserController::class, 'tasksByStatus']);
Route::get('/get_tasks/date/{date}', [UserController::class, 'tasksByDate']);
Route::get('/get_tasks/user/{username}', [UserController::class, 'tasksByUserName']);

// Crud operation
Route::post('/tasks', [UserController::class, 'insertTask']);
Route::put('/tasks/{taskId}', [UserController::class, 'updateTask']);
Route::delete('/tasks/{taskId}', [UserController::class, 'deleteTask']);
Route::get('/tasks/{taskId}', [UserController::class, 'viewTask']);

// assign user to task and unassign
Route::post('/tasks/{taskId}/assign', [UserController::class, 'assignUsersToTask']);
Route::delete('/tasks/{taskId}/unassign/{userId}', [UserController::class, 'unassignUserFromTask']);
Route::put('/task_update/{taskID}/status', [UserController::class, 'updateStatus']);
Route::get('/users/{userId}/tasks', [UserController::class, 'tasksAssignedToUser']);

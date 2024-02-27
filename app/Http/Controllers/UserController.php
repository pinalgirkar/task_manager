<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Auth;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use App\Models\User;
use App\Models\task;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Validator;


class UserController extends Controller
{
    //login 
    function login(){
        return view('login');
    }   
    
    //Login validation
    function loginPost(Request $request){
        $request->validate([
            'email'=> 'required',
            'password'=>'required',
        ]);

        $credentials = $request->only('email', 'password');
        if(Auth::attempt($credentials)){
            return redirect()->intended(route('home'));
        }

        return redirect(route('login'))->with('error', 'Login details are not valid !');
    }
    
    // Login API to return API token
    public function loginAPI(Request $request){
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json(['error' =>'Invalid Login Details'], 200);
        }
    
        $user = Auth::user();
        $token = $user->createToken('AuthToken')->plainTextToken;
    
        return response()->json(['token' => $token], 200);
    }

    // List of users
    function users(){
        $users= User::all();

        if($users->count() > 0){
            
            $data = ['status' => 200,
            'users' => $users];
        }else{
            
            $data = ['status' => 404,
            'error' => 'No users found'];
        }

        return response()->json($data,200);
    }

    //List task
    function tasks(){

        $tasks=task::all();

        if($tasks->count() > 0){
            
            $data = ['status' => 200,
            'tasks' => $tasks];
        }else{
            
            $data = ['status' => 404,
            'error' => 'No Tasks found'];
        }

        return response()->json($data, 200);
    }

    // Filter By Status
    public function tasksByStatus(Request $request, $status){
        try {
            // Retrieve tasks filtered by status
            $tasks = Task::where('status', $status)->get();
            
            if ($tasks->isEmpty()) {
                return response()->json(['error' => "No tasks found with the status '{$status}'."], 404);
            }

            return response()->json(['tasks' => $tasks], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // Filter By Date
    public function tasksByDate(Request $request, $date){
        try {
            // Validate the date parameter
            // $request->validate([
            //     'date' => 'required|date',
            // ]);

            // Retrieve tasks filtered by date
            $tasks = Task::whereDate('due_date', $date)->get();
           
            if ($tasks->isEmpty()) {
                return response()->json(['error' => "No tsk found with the given date '{$date}'."], 404);
            }

            return response()->json(['tasks' => $tasks], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // Filter By Assigned user
    public function tasksByUserName(Request $request, $userName){
        try {
            // Retrieve the user by their name
            $user = User::where('name', $userName)->first();

            // If no user is found, throw an exception
            if (!$user) {
                return response()->json(['error' => "No user found with the name '{$userName}'."], 404);
            }

            // Retrieve tasks assigned to the user
            $tasks = $user->tasks;
            
            if ($tasks->isEmpty()) {
                return response()->json(['error' => "No tasks found for user '{$userName}'."], 404);
            }

            return response()->json(['tasks' => $tasks], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    //Insert Task API
    function insertTask(Request $request){
        $ret = $request->validate([
            'title'=>'required',
            'description'=>'required',
            'due_date'=>'required',
            'status'=>'in:pending,in_progress,completed',
        ]);

        if (!$ret) {
            return response()->json(['error' => 'Task creation failed !!!'], 500);
        }

        $task = task::create($request->all());

        if (!$task) {
            return response()->json(['error' => 'Task creation failed'], 500);
        }

        return response()->json(['task' => $task], 201);
    }

    // Update Task API
    public function updateTask(Request $request, $taskId){
        $request->validate([
            'title' => 'required',
            'description'=>'required',
            'due_date' => 'required|date',
            'status' => 'in:pending,in_progress,completed',
        ]);

        $task = task::find($taskId);

        if (!$task) {
            return response()->json(['error' => 'Task not found'], 404);
        }
        
        $task->update($request->all());

        return response()->json(['task' => $task], 200);
    }

    // Delete Task API
    public function deleteTask($taskId){
        $task = task::find($taskId);

        if (!$task) {
            return response()->json(['error' => 'Task not found'], 404);
        }

        $task->delete();

        return response()->json(['message' => 'Task deleted successfully'], 200);
    }

    //List task bu Id API
    public function viewTask($taskId){
        $task = task::find($taskId);

        if (!$task) {
            return response()->json(['error' => 'Task not found'], 404);
        }

        $data=['task'=>$task];
        return response()->json($data, 200);
    
    }   
    
    // Assign multiple user to a task
    public function assignUsersToTask(Request $request, $taskId){
        try {
            $request->validate([
                'user_ids' => 'required|array',
                'user_ids.*' => 'exists:users,id',
            ]);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        }

        $task = Task::find($taskId);
        if (!$task) {
            return response()->json(['error' => 'Task not found'], 404);
        }

        $task->users()->attach($request->input('user_ids'));

        return response()->json(['message' => 'Users assigned to task successfully'], 200);
    }

    // Unassign User from a task
    public function unassignUserFromTask(Request $request, $taskId, $userId){
        try {
            $task = task::find($taskId);
            if (!$task) {
                return response()->json(['error' => 'Task not found'], 404);
            }
    
            $task->users()->detach($userId);
    
            return response()->json(['message' => 'User unassigned from task successfully'], 200);
        } catch (\Exception $e) {
            // Log the exception or handle it as per your application's requirements
            return response()->json(['error' => 'An error occurred while unassigning user from task.'], 500);
        }
    }

    // Allow users to change the status of a task.
    public function updateStatus(Request $request, $taskId){
        try {
            // Validate the request parameters
            $request->validate([
                'email' => 'required|email',
                'password' => 'required',
                'status' => 'required|in:pending,in_progress,completed',
            ]);
    
            // Authenticate the user using the provided credentials
            if (!Auth::attempt($request->only('email', 'password'))) {
                return response()->json(['error' => 'Invalid credentials.'], 403);
            }

            $user = $request->user();

            // Retrieve tasks assigned to the user
            $tasks = $user->tasks()->where('tasks.id', $taskId)->get();

            if ($tasks->isEmpty()) {
                return response()->json(['error' => 'Task not found for this user'], 404);
            }

            // Find the task by ID
            $task = task::findOrFail($taskId);
    
            // Update the task status
            $task->status = $request->status;
            $task->save();
    
            return response()->json(['message' => 'Task status updated successfully.'], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 401);
        }
    }

    // task assigned to a particular user
    public function tasksAssignedToUser($userId){
        // Check if the user exists
        $user = User::find($userId);

        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        // Retrieve the tasks assigned to the specified user
        $tasks = Task::whereHas('users', function ($query) use ($userId) {
            $query->where('users.id', $userId); // Specify the table alias to avoid ambiguity
        })->get();

        return response()->json(['tasks' => $tasks], 200);
    }
   
}

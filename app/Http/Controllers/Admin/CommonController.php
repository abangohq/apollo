<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Bank;
use App\Models\Task;
use Illuminate\Http\Request;

class CommonController extends Controller
{
    /**
     * Fetch task that users can complete
     */
    public function fetchTasks(Request $request)
    {
        $tasks = Task::all();
        return $this->success($tasks, 'Reward tasks');
    }

    /**
     * Update a user task details.
     */
    public function updateTask(Request $request, Task $task)
    {
        $attributes = collect($request->all());

        if ($request->hasFile('image')) {
            $filename = $request->image->storeOnCloudinaryAs('tasks')->getSecurePath();
            $attributes->put('image', $filename);
        }

        $task->update($attributes->toArray());
        return $this->success($task, 'Task successfully updated');
    }

    /**
     * Retrieve the list of banks on the system.
     */
    public function banks(Request $request)
    {
        $banks = Bank::orderBy('bank_name')->get();
        return $this->success($banks, 'Banks');
    }

    /**
     * Update the a bank information
     */
    public function updateBank(Request $request, Bank $bank)
    {
        $attributes = collect($request->all());

        if ($request->hasFile('bank_logo')) {
            $filename = $request->bank_logo->storeOnCloudinaryAs('banks')->getSecurePath();
            $attributes->put('bank_logo', $filename);
        }
        
        $bank->update($attributes->toArray());
        return $this->success($bank, 'bank successfully updated');
    }
}

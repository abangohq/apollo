<?php

namespace App\Http\Requests\Pin;

use App\Models\Task;
use App\Support\Utils;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class CreatePinRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'pin' => ['required', 'digits:4', 'confirmed']
        ];
    }

    /**
     * Get task for pin creation
     */
    public function pinTask()
    {
        $task = Task::find(2);

        if (is_null($task)) {
            abort(409, 'Unable to find task for your pin creation action.');
        }

        return $task;
    }

    /**
     * Pin Attributes to save
     */
    public function pinAttributes()
    {
        return [
            'pin' => Hash::make($this->input('pin'))
        ];
    }

    /**
     * Save pin record
     */
    public function createPin()
    {
        $task = $this->pinTask();

        DB::transaction(function () use ($task) {
            $this->user()->update($this->pinAttributes());
            Utils::completeTask($this->user()->id, $task->id);
        });
    }
}

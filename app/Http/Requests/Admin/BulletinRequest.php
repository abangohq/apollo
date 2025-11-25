<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class BulletinRequest extends FormRequest
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
            'target' => ['required', 'in:all_users,active_users,inactive_users,recent_users,specific_user'],
            'user' => ['nullable', 'required_if:target,specific_user'],
            'title' => ['required', 'string', 'max:150'],
            'body' => ['required', 'string', 'max:2000']
        ];
    }

    /**
     * Prepare notification attributes
     */
    public function bulletinAttributes()
    {
        return [
            "title" => $this->title,
            "body" => $this->body,
            "group" => $this->groupName($this->input('target')),
            "successful" => 0,
            "failed" => 0,
        ];
    }

    /**
     * Get the group the user belongs to
     */
    private function groupName($key)
    {
        return match ($key) {
            'all_users' => 'All Users',
            'active_users' => 'Active Users',
            'inactive_users' => 'Inactive Users',
            'recent_users' => 'Recent Users',
            'specific_user' => 'Specific User',
            default => 'Unknown'
        };
    }
}

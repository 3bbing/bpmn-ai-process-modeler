<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProcessStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', \App\Models\Process::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'domain_id' => ['required', 'exists:domains,id'],
            'code' => ['required', 'string', 'max:50', 'unique:processes,code'],
            'title' => ['required', 'string', 'max:255'],
            'level' => ['required', 'in:L1,L2,L3,L4'],
            'owner_user_id' => ['required', 'exists:users,id'],
            'status' => ['required', 'in:draft,in_review,published,archived'],
            'summary' => ['nullable', 'string'],
            'guidance' => ['nullable', 'array'],
            'meta' => ['nullable', 'array'],
        ];
    }
}

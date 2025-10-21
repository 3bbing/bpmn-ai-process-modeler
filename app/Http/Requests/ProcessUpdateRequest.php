<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProcessUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('process')) ?? false;
    }

    public function rules(): array
    {
        $processId = $this->route('process')->id ?? null;

        return [
            'domain_id' => ['sometimes', 'exists:domains,id'],
            'code' => ['sometimes', 'string', 'max:50', 'unique:processes,code,'.$processId],
            'title' => ['sometimes', 'string', 'max:255'],
            'level' => ['sometimes', 'in:L1,L2,L3,L4'],
            'owner_user_id' => ['sometimes', 'exists:users,id'],
            'status' => ['sometimes', 'in:draft,in_review,published,archived'],
            'summary' => ['nullable', 'string'],
            'guidance' => ['nullable', 'array'],
            'meta' => ['nullable', 'array'],
        ];
    }
}

<?php

namespace App\Http\Requests;

use App\Models\Process;
use Illuminate\Foundation\Http\FormRequest;

class ProcessGenerateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Process::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'level' => ['required', 'in:L1,L2,L3,L4'],
            'summary' => ['nullable', 'string'],
            'transcript' => ['nullable', 'string'],
            'bpmn_xml' => ['required', 'string'],
            'extraction' => ['nullable', 'array'],
            'domain_id' => ['nullable', 'exists:domains,id'],
        ];
    }
}

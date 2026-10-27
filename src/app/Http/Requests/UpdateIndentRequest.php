<?php

namespace App\Http\Requests;

use App\Enums\IndentPriority;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class UpdateIndentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $indent = $this->route('indent');

        return $indent && $this->user()?->can('update', $indent);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'work_type' => ['required', 'string', 'max:100'],
            'installation_id' => ['required', 'integer', 'exists:installations,id'],
            'priority' => ['required', new Enum(IndentPriority::class)],
            'required_by' => ['required', 'date'],
            'description' => ['nullable', 'string', 'max:5000'],
            'justification' => ['nullable', 'string', 'max:5000'],
            'action' => ['required', 'string', Rule::in(['draft', 'submit'])],
            'remark' => ['nullable', 'string', 'max:1000'],
            'attachments' => ['nullable', 'array', 'max:20'],
            'attachments.*' => ['file', new \App\Rules\ValidAttachmentRule()],
        ];
    }

    /**
     * Custom validation attribute names.
     */
    public function attributes(): array
    {
        return [
            'title' => 'work title',
            'work_type' => 'work type',
            'installation_id' => 'installation',
            'priority' => 'priority level',
            'required_by' => 'required completion date',
            'description' => 'scope description',
            'justification' => 'justification',
            'action' => 'submission action',
            'remark' => 'submission remarks',
            'attachments' => 'attached files',
            'attachments.*' => 'attachment file',
        ];
    }
}

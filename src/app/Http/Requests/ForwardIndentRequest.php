<?php

namespace App\Http\Requests;

use App\Enums\MovementAction;
use App\Models\Indent;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ForwardIndentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $indent = $this->route('indent');

        return $indent instanceof Indent && $this->user()?->can('forward', $indent);
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_rejected' => filter_var($this->input('is_rejected'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false,
            'action' => $this->input('action') ?: MovementAction::FORWARD->value,
            'remark' => $this->input('remark') !== null ? trim((string) $this->input('remark')) : null,
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'recipient_cpf' => ['required', 'string', 'max:14', 'exists:users,cpf'],
            'action' => [
                'required',
                'string',
                Rule::in([
                    MovementAction::FORWARD->value,
                    MovementAction::RETURN ->value,
                    MovementAction::REJECT->value,
                ])
            ],
            'is_rejected' => ['boolean'],
            'remark' => [
                Rule::requiredIf(function () {
                    return $this->boolean('is_rejected') || $this->input('action') === MovementAction::REJECT->value;
                }),
                'nullable',
                'string',
                'max:2000',
            ],
        ];
    }

    /**
     * Get custom error messages.
     */
    public function messages(): array
    {
        return [
            'recipient_cpf.required' => 'Please select a recipient.',
            'recipient_cpf.exists' => 'The selected recipient does not exist in the system.',
            'remark.required' => 'A reason / remark is mandatory when forwarding with rejection.',
        ];
    }

    /**
     * Additional validation logic.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $user = $this->user();
            $indent = $this->route('indent');
            $recipientCpf = $this->input('recipient_cpf');
            $action = $this->input('action');
            $isRejected = $this->boolean('is_rejected') || $action === MovementAction::REJECT->value;

            if ($recipientCpf && $user && $recipientCpf === $user->cpf) {
                $validator->errors()->add('recipient_cpf', 'You cannot forward or return the indent to yourself.');
            }

            if ($isRejected && $indent instanceof Indent && $user && ! $user->can('reject', $indent)) {
                $validator->errors()->add('action', 'Only management authorities (IM, SAM, SM) have the authority to reject indents.');
            }

            if ($action === MovementAction::RETURN->value && $indent instanceof Indent && $recipientCpf) {
                $isParticipant = $indent->participants()->where('cpf', $recipientCpf)->exists();
                if (!$isParticipant) {
                    $validator->errors()->add('recipient_cpf', 'When returning an indent, the recipient must be an earlier participant.');
                }
            }

            if ($action === MovementAction::FORWARD->value && ! $isRejected && $indent instanceof Indent && $recipientCpf && $user) {
                $expectedRole = $indent->getNextExpectedRole();
                if ($expectedRole && ! $user->isSuperAdmin()) {
                    $recipient = \App\Models\User::where('cpf', $recipientCpf)->first();
                    if ($recipient && ! $recipient->hasRole($expectedRole)) {
                        $validator->errors()->add('recipient_cpf', "This indent must be forwarded to a {$expectedRole} next according to the workflow sequence.");
                    }
                }
            }
        });
    }
}

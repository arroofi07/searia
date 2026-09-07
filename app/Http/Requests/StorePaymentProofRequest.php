<?php

namespace App\Http\Requests;

use App\Models\Invoice;
use App\Support\UploadedFileGuard;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StorePaymentProofRequest extends FormRequest
{
    public function authorize(): bool
    {
        $invoice = $this->route('invoice');

        return $invoice instanceof Invoice
            && ($this->user()?->can('uploadProof', $invoice) ?? false);
    }

    /**
     * @return array<string, list<string|ValidationRule>>
     */
    public function rules(): array
    {
        $maxKilobytes = (int) ceil(((int) config('searia.invoice.proof_max_bytes', 5 * 1024 * 1024)) / 1024);

        return [
            'proof' => ['required', 'file', 'max:'.$maxKilobytes],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $file = $this->file('proof');
            if ($file === null) {
                return;
            }

            try {
                UploadedFileGuard::assertSafe(
                    $file,
                    ['image/jpeg', 'image/png', 'application/pdf'],
                    (int) config('searia.invoice.proof_max_bytes', 5 * 1024 * 1024),
                );
            } catch (\Illuminate\Validation\ValidationException $exception) {
                foreach ($exception->errors() as $messages) {
                    foreach ($messages as $message) {
                        $validator->errors()->add('proof', $message);
                    }
                }
            }
        });
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'proof' => 'bukti pembayaran',
        ];
    }
}

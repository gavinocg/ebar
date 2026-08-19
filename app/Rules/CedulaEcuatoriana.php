<?php

namespace App\Rules;

use App\Services\ValidacionCedulaRuc;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class CedulaEcuatoriana implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!app(ValidacionCedulaRuc::class)->validarCedula((string) $value)) {
            $fail('El campo :attribute no contiene una cédula ecuatoriana válida.');
        }
    }
}
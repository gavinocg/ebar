<?php

namespace App\Rules;

use App\Services\ValidacionCedulaRuc;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class RucEcuatoriano implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!app(ValidacionCedulaRuc::class)->validarRuc((string) $value)) {
            $fail('El campo :attribute no contiene un RUC ecuatoriano válido.');
        }
    }
}
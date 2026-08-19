<?php

namespace Tests\Unit;

use App\Services\ValidacionCedulaRuc;
use Tests\TestCase;

class ValidacionCedulaRucTest extends TestCase
{
    private ValidacionCedulaRuc $validador;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validador = new ValidacionCedulaRuc();
    }

    public function test_cedula_valida(): void
    {
        $this->assertTrue($this->validador->validarCedula('1002003000'));
    }

    public function test_cedula_invalida_por_digito_verificador(): void
    {
        $this->assertFalse($this->validador->validarCedula('1002003001'));
    }

    public function test_cedula_con_longitud_incorrecta(): void
    {
        $this->assertFalse($this->validador->validarCedula('12345'));
    }

    public function test_cedula_con_provincia_invalida(): void
    {
        $this->assertFalse($this->validador->validarCedula('9900000001'));
    }

    public function test_ruc_persona_natural_valido(): void
    {
        $this->assertTrue($this->validador->validarRuc('1002003000001'));
    }

    public function test_ruc_sufijo_incorrecto(): void
    {
        $this->assertFalse($this->validador->validarRuc('1002003000002'));
    }
}
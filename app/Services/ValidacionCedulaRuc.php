<?php

namespace App\Services;

class ValidacionCedulaRuc
{
    /**
     * Valida una cédula ecuatoriana (10 dígitos, algoritmo módulo 10).
     */
    public function validarCedula(string $cedula): bool
    {
        $cedula = $this->limpiar($cedula);

        if (!preg_match('/^\d{10}$/', $cedula)) {
            return false;
        }

        $provincia = (int) substr($cedula, 0, 2);
        if ($provincia < 1 || $provincia > 24) {
            return false;
        }

        $tercerDigito = (int) substr($cedula, 2, 1);
        if ($tercerDigito > 5) {
            return false;
        }

        return $this->verificarModulo10($cedula);
    }

    /**
     * Valida un RUC ecuatoriano (13 dígitos).
     * Persona natural: cédula válida + sufijo 001.
     * Persona jurídica/privada (3er dígito 9/6): algoritmo módulo 11.
     */
    public function validarRuc(string $ruc): bool
    {
        $ruc = $this->limpiar($ruc);

        if (!preg_match('/^\d{13}$/', $ruc)) {
            return false;
        }

        $provincia = (int) substr($ruc, 0, 2);
        if ($provincia < 1 || $provincia > 24) {
            return false;
        }

        $tercerDigito = (int) substr($ruc, 2, 1);
        $ultimosTres = substr($ruc, -3);

        if ($tercerDigito <= 5) {
            return $ultimosTres === '001' && $this->validarCedula(substr($ruc, 0, 10));
        }

        if (in_array($tercerDigito, [6, 9], true)) {
            return $this->verificarModulo11($ruc);
        }

        return false;
    }

    private function verificarModulo10(string $numero): bool
    {
        $digitoVerificador = (int) substr($numero, -1);
        $coeficientes = [2, 1, 2, 1, 2, 1, 2, 1, 2];
        $suma = 0;

        foreach ($coeficientes as $i => $coeficiente) {
            $producto = (int) $numero[$i] * $coeficiente;
            $suma += $producto >= 10 ? $producto - 9 : $producto;
        }

        $residuo = $suma % 10;
        $esperado = $residuo === 0 ? 0 : 10 - $residuo;

        return $digitoVerificador === $esperado;
    }

    private function verificarModulo11(string $numero): bool
    {
        $digitoVerificador = (int) substr($numero, -1);
        $coeficientes = [3, 2, 7, 6, 5, 4, 3, 2];
        $suma = 0;

        for ($i = 0; $i < 9 && isset($numero[$i]); $i++) {
            $suma += (int) $numero[$i] * $coeficientes[$i];
        }

        $residuo = $suma % 11;
        $esperado = $residuo === 0 ? 0 : 11 - $residuo;

        return $digitoVerificador === $esperado;
    }

    private function limpiar(string $valor): string
    {
        return preg_replace('/\D/', '', $valor);
    }
}
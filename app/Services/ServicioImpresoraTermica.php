<?php

namespace App\Services;

use App\Models\Venta as Sale;
use App\Models\Impresora as Printer;
use App\Models\ConfiguracionNegocio as BusinessSetting;

class ServicioImpresoraTermica
{
    private $printer;
    private $business;
    private $commands = [];

    public function __construct(Printer $printer)
    {
        $this->printer = $printer;
        $this->business = BusinessSetting::obtenerConfiguracion();
    }

    public function imprimirComprobante($sale)
    {
        if ($this->printer->ancho_papel === '58mm') {
            return $this->imprimirComprobante58($sale);
        }

        $this->inicializar();
        $this->agregarEncabezado($sale);
        $this->agregarDetalles($sale);
        $this->agregarTotales($sale);
        $this->agregarPie($sale);
        $this->cortarPapel();

        return $this->obtenerComandos();
    }

    public function imprimirComprobantePrueba()
    {
        if ($this->printer->ancho_papel === '58mm') {
            $this->inicializar();
            $this->centrar();
            $this->commands[] = "\x1B\x21\x10";
            $this->commands[] = "PRUEBA DE IMPRESION\n";
            $this->commands[] = "\x1B\x21\x00";
            $this->commands[] = $this->separador() . "\n";
            $this->commands[] = "Fecha: " . now()->format('d/m/Y H:i') . "\n";
            $this->commands[] = "\x1B\x61\x00";
            $this->commands[] = $this->separador() . "\n";
            $this->commands[] = $this->alinearColumnas('1xProducto', '$0.50') . "\n";
            $this->commands[] = $this->alinearColumnas('2xProducto', '$1.00') . "\n";
            $this->commands[] = $this->separador() . "\n";
            $this->commands[] = $this->alinearColumnas('Total', '$1.50') . "\n";
            $this->commands[] = "\n";
            $this->cortarPapel();

            return $this->obtenerComandos();
        }

        $this->inicializar();

        $this->commands[] = "\x1D\x21\x11";
        $this->commands[] = "PRUEBA DE IMPRESION\n";
        $this->commands[] = "\x1D\x21\x00";
        $this->commands[] = "\n";
        $this->commands[] = config('app.name', 'MI TIENDA') . "\n";
        $this->commands[] = "\n";
        $this->commands[] = "\x1B\x61\x00";
        $this->commands[] = "Fecha: " . now()->format('d/m/Y H:i:s') . "\n";
        $this->commands[] = "Impresora: " . $this->printer->nombre . "\n";
        $this->commands[] = "Conexión: " . strtoupper($this->printer->tipo_conexion) . "\n";
        $this->commands[] = "Dirección: " . $this->printer->direccion . "\n";
        $this->commands[] = "Puerto: " . $this->printer->puerto . "\n";
        $this->commands[] = "Papel: " . $this->printer->ancho_papel . "\n";
        $this->commands[] = "\n";
        $this->commands[] = str_repeat('-', 42) . "\n";
        $this->commands[] = "\n";
        $this->commands[] = "ABCDEFGHIJKLMNOPQRSTUVWXYZ\n";
        $this->commands[] = "0123456789\n";
        $this->commands[] = "!@#$%^&*()_+-=[]{}|;:,.<>?\n";
        $this->commands[] = "\n";
        $this->commands[] = str_repeat('-', 42) . "\n";
        $this->commands[] = "\x1B\x61\x01";
        $this->commands[] = "\n";
        $this->commands[] = "SI IMPRIME CORRECTAMENTE\n";
        $this->commands[] = "LA IMPRESORA ESTA CONFIGURADA\n";
        $this->commands[] = "PARA USAR EL SISTEMA TPV\n";
        $this->commands[] = "\n";
        $this->commands[] = "\n";

        $this->cortarPapel();

        return $this->obtenerComandos();
    }

    private function inicializar()
    {
        $this->commands[] = "\x1B\x40"; // Initialize
        $this->commands[] = "\x1B\x61\x01"; // Center alignment
    }

    private function imprimirComprobante58($sale): string
    {
        $this->inicializar();
        $this->centrar();
        $this->commands[] = $this->texto($this->business->nombre_negocio) . "\n";
        $this->centrar();
        $this->commands[] = $this->texto($sale->created_at->format('d/m/Y H:i')) . "\n";
        $this->commands[] = $this->separador() . "\n";
        $this->commands[] = "\x1B\x61\x00";

        foreach ($sale->detalles as $item) {
            $descripcion = $item->cantidad . 'x' . $item->nombre_producto;
            $importe = '$' . number_format((float) $item->subtotal, 2, '.', '');
            $this->commands[] = $this->alinearColumnas($descripcion, $importe) . "\n";
        }

        $this->commands[] = $this->separador() . "\n";
        $this->commands[] = $this->alinearColumnas('Total', '$' . number_format((float) $sale->total, 2, '.', '')) . "\n";
        if ($sale->metodo_pago === 'credito') {
            $this->commands[] = $this->alinearColumnas('Pago', 'CREDITO') . "\n";
        } else {
            $this->commands[] = $this->alinearColumnas('Pago', '$' . number_format((float) $sale->pagado, 2, '.', '')) . "\n";
            $this->commands[] = $this->alinearColumnas('Cambio', '$' . number_format((float) $sale->cambio, 2, '.', '')) . "\n";
        }
        $this->commands[] = "\n";
        $this->centrar();
        $mensaje = trim((string) $this->business->mensaje_comprobante) ?: 'GRACIAS POR SU COMPRA!';
        $this->commands[] = $this->texto($mensaje) . "\n";
        $this->commands[] = "\n\n";
        $this->cortarPapel();

        return $this->obtenerComandos();
    }

    private function centrar(): void
    {
        $this->commands[] = "\x1B\x61\x01";
    }

    private function separador(): string
    {
        return str_repeat('-', 32);
    }

    private function alinearColumnas(string $izquierda, string $derecha): string
    {
        $izquierda = $this->limpiar($izquierda);
        $derecha = $this->limpiar($derecha);
        $anchoDerecha = mb_strwidth($derecha, 'UTF-8');
        $maxIzquierda = 32 - $anchoDerecha - 1;
        $izquierda = mb_strimwidth($izquierda, 0, max(1, $maxIzquierda), '', 'UTF-8');
        $espacios = max(1, 32 - mb_strwidth($izquierda, 'UTF-8') - $anchoDerecha);

        return $this->texto($izquierda . str_repeat(' ', $espacios) . $derecha);
    }

    private function texto(string $texto): string
    {
        $texto = $this->limpiar($texto);

        $texto = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $texto) ?: $texto;

        return preg_replace('/[^\x20-\x7E]/', '', $texto) ?? '';
    }

    private function limpiar(string $texto): string
    {
        return preg_replace('/[\x00-\x1F\x7F]/u', '', $texto) ?? '';
    }

    private function agregarEncabezado($sale)
    {
        $this->commands[] = "\x1D\x21\x11";
        $this->commands[] = $this->texto($this->business->nombre_negocio) . "\n";
        $this->commands[] = "\x1D\x21\x00";
        
        $this->commands[] = "RFC: " . $this->texto($this->business->rfc) . "\n";
        $this->commands[] = "Tel: " . $this->texto($this->business->telefono) . "\n";
        
        if ($this->business->direccion) {
            $this->commands[] = $this->texto($this->business->direccion) . "\n";
        }
        
        $this->commands[] = "\n";
        
        $this->commands[] = "\x1B\x61\x00";
        $this->commands[] = "Comprobante: {$sale->numero_comprobante}\n";
        $this->commands[] = "Fecha: " . $sale->created_at->format('d/m/Y H:i:s') . "\n";
        $this->commands[] = str_repeat('-', 42) . "\n";
    }

    private function agregarDetalles($sale)
    {
        foreach ($sale->detalles as $item) {
            $this->commands[] = $this->texto("{$item->cantidad} x {$item->nombre_producto}") . "\n";
            $this->commands[] = "  P.U.: $" . number_format($item->precio, 2) . "\n";
            $this->commands[] = "\x1B\x61\x02"; // Right alignment
            $this->commands[] = "  $" . number_format($item->subtotal, 2) . "\n";
            $this->commands[] = "\x1B\x61\x00"; // Left alignment
        }
        
        $this->commands[] = str_repeat('-', 42) . "\n";
    }

    private function agregarTotales($sale)
    {
        $this->commands[] = "\x1B\x61\x02";
        
        $this->commands[] = "Subtotal:" . str_repeat(' ', 25) . "$" . number_format($sale->subtotal, 2) . "\n";
        
        if ($sale->impuesto_habilitado) {
            $this->commands[] = "Impuesto (" . $sale->porcentaje_impuesto . "%):" . str_repeat(' ', 20) . "$" . number_format($sale->impuesto, 2) . "\n";
        }
        
        $this->commands[] = "\x1D\x21\x11";
        $this->commands[] = "TOTAL:" . str_repeat(' ', 26) . "$" . number_format($sale->total, 2) . "\n";
        $this->commands[] = "\x1D\x21\x00";
        
        $this->commands[] = "\n";
        $this->commands[] = "\x1B\x61\x00";
        $this->commands[] = "Pago: " . strtoupper($sale->metodo_pago) . "\n";
        if ($sale->metodo_pago !== 'credito') {
            $this->commands[] = "Recibido: $" . number_format($sale->pagado, 2) . "\n";
            $this->commands[] = "Cambio: $" . number_format($sale->cambio, 2) . "\n";
        }
    }

    private function agregarPie($sale)
    {
        $this->commands[] = "\x1B\x61\x01";
        $this->commands[] = "\n";
        $this->commands[] = $this->texto($this->business->mensaje_comprobante) . "\n";
        $this->commands[] = "\n";
        $this->commands[] = "\n";
    }

    private function cortarPapel()
    {
        $this->commands[] = "\x1D\x56\x00"; // Cut paper
    }

    public function obtenerComandos()
    {
        return implode('', $this->commands);
    }

    public function obtenerDatosConexion()
    {
        return [
            'tipo' => $this->printer->tipo_conexion,
            'direccion' => $this->printer->direccion,
            'puerto' => $this->printer->puerto,
        ];
    }
}

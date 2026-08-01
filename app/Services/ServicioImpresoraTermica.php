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

    private function agregarEncabezado($sale)
    {
        $this->commands[] = "\x1D\x21\x11";
        $this->commands[] = $this->business->nombre_negocio . "\n";
        $this->commands[] = "\x1D\x21\x00";
        
        $this->commands[] = "RFC: " . $this->business->rfc . "\n";
        $this->commands[] = "Tel: " . $this->business->telefono . "\n";
        
        if ($this->business->direccion) {
            $this->commands[] = $this->business->direccion . "\n";
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
            $this->commands[] = "{$item->cantidad} x {$item->nombre_producto}\n";
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
        $this->commands[] = "Recibido: $" . number_format($sale->pagado, 2) . "\n";
        $this->commands[] = "Cambio: $" . number_format($sale->cambio, 2) . "\n";
    }

    private function agregarPie($sale)
    {
        $this->commands[] = "\x1B\x61\x01";
        $this->commands[] = "\n";
        $this->commands[] = $this->business->mensaje_comprobante . "\n";
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

<?php

namespace App\Services;

use App\Models\Sale;
use App\Models\Printer;
use App\Models\BusinessSetting;

class ThermalPrinterService
{
    private $printer;
    private $business;
    private $commands = [];

    public function __construct(Printer $printer)
    {
        $this->printer = $printer;
        $this->business = BusinessSetting::getSettings();
    }

    public function printTicket($sale)
    {
        $this->initialize();
        $this->addHeader($sale);
        $this->addItems($sale);
        $this->addTotals($sale);
        $this->addFooter($sale);
        $this->cutPaper();

        return $this->getCommands();
    }

    public function printTestTicket()
    {
        $this->initialize();

        $this->commands[] = "\x1D\x21\x11";
        $this->commands[] = "PRUEBA DE IMPRESION\n";
        $this->commands[] = "\x1D\x21\x00";
        $this->commands[] = "\n";
        $this->commands[] = config('app.name', 'MI TIENDA') . "\n";
        $this->commands[] = "\n";
        $this->commands[] = "\x1B\x61\x00";
        $this->commands[] = "Fecha: " . now()->format('d/m/Y H:i:s') . "\n";
        $this->commands[] = "Impresora: " . $this->printer->name . "\n";
        $this->commands[] = "Conexion: " . strtoupper($this->printer->connection_type) . "\n";
        $this->commands[] = "Direccion: " . $this->printer->address . "\n";
        $this->commands[] = "Puerto: " . $this->printer->port . "\n";
        $this->commands[] = "Papel: " . $this->printer->paper_width . "\n";
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

        $this->cutPaper();

        return $this->getCommands();
    }

    private function initialize()
    {
        $this->commands[] = "\x1B\x40"; // Initialize
        $this->commands[] = "\x1B\x61\x01"; // Center alignment
    }

    private function addHeader($sale)
    {
        $this->commands[] = "\x1D\x21\x11";
        $this->commands[] = $this->business->business_name . "\n";
        $this->commands[] = "\x1D\x21\x00";
        
        $this->commands[] = "RFC: " . $this->business->rfc . "\n";
        $this->commands[] = "Tel: " . $this->business->phone . "\n";
        
        if ($this->business->address) {
            $this->commands[] = $this->business->address . "\n";
        }
        
        $this->commands[] = "\n";
        
        $this->commands[] = "\x1B\x61\x00";
        $this->commands[] = "Ticket: {$sale->ticket_number}\n";
        $this->commands[] = "Fecha: " . $sale->created_at->format('d/m/Y H:i:s') . "\n";
        $this->commands[] = str_repeat('-', 42) . "\n";
    }

    private function addItems($sale)
    {
        foreach ($sale->items as $item) {
            $this->commands[] = "{$item->quantity} x {$item->product_name}\n";
            $this->commands[] = "  P.U.: $" . number_format($item->price, 2) . "\n";
            $this->commands[] = "\x1B\x61\x02"; // Right alignment
            $this->commands[] = "  $" . number_format($item->subtotal, 2) . "\n";
            $this->commands[] = "\x1B\x61\x00"; // Left alignment
        }
        
        $this->commands[] = str_repeat('-', 42) . "\n";
    }

    private function addTotals($sale)
    {
        $this->commands[] = "\x1B\x61\x02";
        
        $this->commands[] = "Subtotal:" . str_repeat(' ', 25) . "$" . number_format($sale->subtotal, 2) . "\n";
        
        if ($this->business->charge_tax) {
            $this->commands[] = "IVA (" . $this->business->tax_percentage . "%):" . str_repeat(' ', 24) . "$" . number_format($sale->tax, 2) . "\n";
        }
        
        $this->commands[] = "\x1D\x21\x11";
        $this->commands[] = "TOTAL:" . str_repeat(' ', 26) . "$" . number_format($sale->total, 2) . "\n";
        $this->commands[] = "\x1D\x21\x00";
        
        $this->commands[] = "\n";
        $this->commands[] = "\x1B\x61\x00";
        $this->commands[] = "Pago: " . strtoupper($sale->payment_method) . "\n";
        $this->commands[] = "Recibido: $" . number_format($sale->paid, 2) . "\n";
        $this->commands[] = "Cambio: $" . number_format($sale->change, 2) . "\n";
    }

    private function addFooter($sale)
    {
        $this->commands[] = "\x1B\x61\x01";
        $this->commands[] = "\n";
        $this->commands[] = $this->business->ticket_message . "\n";
        $this->commands[] = "\n";
        $this->commands[] = "\n";
    }

    private function cutPaper()
    {
        $this->commands[] = "\x1D\x56\x00"; // Cut paper
    }

    public function getCommands()
    {
        return implode('', $this->commands);
    }

    public function getConnectionData()
    {
        return [
            'type' => $this->printer->connection_type,
            'address' => $this->printer->address,
            'port' => $this->printer->port,
        ];
    }
}

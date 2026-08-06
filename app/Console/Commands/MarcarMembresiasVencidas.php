<?php

namespace App\Console\Commands;

use App\Models\Membresia;
use Illuminate\Console\Command;

class MarcarMembresiasVencidas extends Command
{
    protected $signature = 'membresias:marcar-vencidas';

    protected $description = 'Marca como vencidas las membresías cuya fecha de vencimiento ya pasó.';

    public function handle(): int
    {
        $afectadas = Membresia::query()
            ->whereNotIn('estado', ['suspendida', 'cancelada'])
            ->where('fecha_vencimiento', '<', now()->toDateString())
            ->get()
            ->each->aplicarVencimiento()
            ->count();

        $this->info("Membresías marcadas como vencidas: {$afectadas}.");

        return self::SUCCESS;
    }
}
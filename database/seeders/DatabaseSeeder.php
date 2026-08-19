<?php

namespace Database\Seeders;

use App\Models\Caja;
use App\Models\Categoria;
use App\Models\Contrato;
use App\Models\Membresia;
use App\Models\MembresiaNegocio;
use App\Models\Negocio;
use App\Models\Pago;
use App\Models\Producto;
use App\Models\Sucursal;
use App\Models\User;
use App\Services\ContextoNegocio;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(RolePermissionSeeder::class);

        $planBasico = \App\Models\Plan::query()->updateOrCreate(
            ['nombre' => 'Básico'],
            ['descripcion' => 'Plan inicial para bares pequeños.', 'precio_mensual' => 10.00, 'limite_cajeros' => 2, 'limite_cajas' => 1, 'limite_sucursales' => 1],
        );

        $planPro = \App\Models\Plan::query()->updateOrCreate(
            ['nombre' => 'Pro'],
            ['descripcion' => 'Ideal para bares en crecimiento.', 'precio_mensual' => 25.00, 'limite_cajeros' => 5, 'limite_cajas' => 3, 'limite_sucursales' => 2],
        );

        // --- Universo de prueba -------------------------------------------------
        $negocioPrincipal = $this->crearNegocioPrincipal($planPro);
        app(ContextoNegocio::class)->establecer($negocioPrincipal->id);

        $this->crearCatalogoDePrueba();
        Caja::create(['nombre' => 'Caja principal', 'esta_activa' => true]);

        $this->crearUsuariosDePrueba();

        $claveGabriela = Str::password(14);
        $this->crearBarYPropietarioDePrueba($planPro, $claveGabriela);

        $this->command?->info("Gaby's Bar creado. Propietario Gabriela Rueda: gavinocg@gmail.com / clave temporal: {$claveGabriela}");
        $this->command?->info('Usuarios de prueba: sadmin@ebar.com / prop1@ebar.com (claves seed documentadas en esta salida).');
    }

    private function crearNegocioPrincipal(\App\Models\Plan $plan): Negocio
    {
        $negocio = Negocio::firstOrCreate(
            ['identificador' => 'negocio-principal'],
            [
                'nombre' => 'Negocio principal',
                'ruc' => '1790010001001',
                'esta_activo' => true,
                'zona_horaria' => 'America/Guayaquil',
                'moneda' => 'USD',
                'numero_sucursales_contratadas' => 2,
            ]
        );

        $sucursal = Sucursal::firstOrCreate(
            ['negocio_id' => $negocio->id, 'nombre' => 'Sucursal principal'],
            ['esta_activa' => true, 'n_cajeros_contratados' => 3],
        );

        Membresia::firstOrCreate(
            ['negocio_id' => $negocio->id],
            ['plan_id' => $plan->id, 'estado' => 'activa', 'fecha_inicio' => now(), 'fecha_vencimiento' => now()->addYear()],
        );

        Contrato::firstOrCreate(
            ['negocio_id' => $negocio->id, 'estado' => 'activo'],
            ['fecha_inicio' => now(), 'fecha_fin' => now()->addYear(), 'forma_contratacion' => 'anual'],
        );

        session(['negocio_id' => $negocio->id]);

        return $negocio;
    }

    private function crearCatalogoDePrueba(): void
    {
        $bebidas = Categoria::firstOrCreate(['nombre' => 'Bebidas'], ['descripcion' => 'Bebidas frías y calientes']);
        $alimentos = Categoria::firstOrCreate(['nombre' => 'Alimentos'], ['descripcion' => 'Comida y snacks']);
        $postres = Categoria::firstOrCreate(['nombre' => 'Postres'], ['descripcion' => 'Dulces y postres']);
        $otros = Categoria::firstOrCreate(['nombre' => 'Otros'], ['descripcion' => 'Productos diversos']);

        $productos = [
            ['Nombre' => 'Coca-Cola 600ml', 'codigo_barras' => '7501050345678', 'precio' => 1.00],
            ['Nombre' => 'Agua Natural 500ml', 'codigo_barras' => '7501050345679', 'precio' => 0.60],
            ['Nombre' => 'Café Americano', 'precio' => 0.90],
            ['Nombre' => 'Sandwich Jamón', 'precio' => 2.00],
            ['Nombre' => 'Papas Fritas', 'precio' => 1.50],
            ['Nombre' => 'Pastel Chocolate', 'precio' => 2.50],
            ['Nombre' => 'Galletas', 'precio' => 0.75],
            ['Nombre' => 'Chicles', 'precio' => 0.50],
        ];

        $categorias = ['Bebidas' => $bebidas, 'Alimentos' => $alimentos, 'Postres' => $postres, 'Otros' => $otros];
        $map = [
            'Coca-Cola' => 'Bebidas', 'Agua' => 'Bebidas', 'Café' => 'Bebidas',
            'Sandwich' => 'Alimentos', 'Papas' => 'Alimentos',
            'Pastel' => 'Postres', 'Galletas' => 'Postres', 'Chicles' => 'Otros',
        ];

        foreach ($productos as $p) {
            $categoria = $categorias[$map[strtok($p['Nombre'], ' ')]] ?? $otros;

            Producto::firstOrCreate(
                ['nombre' => $p['Nombre']],
                ['categoria_id' => $categoria->id, 'descripcion' => '', 'precio' => $p['precio'], 'existencias' => 50, 'codigo_barras' => $p['codigo_barras'] ?? null],
            );
        }
    }

    private function crearUsuariosDePrueba(): void
    {
        // Claves seed documentadas (solo desarrollo)
        $superAdmin = User::where('correo', 'sadmin@ebar.com')->first() ?? new User;
        if (!$superAdmin->exists) {
            $superAdmin->correo = 'sadmin@ebar.com';
            $superAdmin->nombre = 'Super Admin Test';
            $superAdmin->password = 'sadmin123456';
            $superAdmin->save();
        }
        $superAdmin->forceFill(['rol' => 'super_admin'])->save();

        $prop1 = User::where('correo', 'prop1@ebar.com')->first() ?? new User;
        if (!$prop1->exists) {
            $prop1->correo = 'prop1@ebar.com';
            $prop1->nombre = 'Propietario Test';
            $prop1->password = 'prop123456';
            $prop1->save();
        }

        $negocioPrincipal = Negocio::where('identificador', 'negocio-principal')->firstOrFail();

        MembresiaNegocio::firstOrCreate(
            ['negocio_id' => $negocioPrincipal->id, 'usuario_id' => $prop1->id],
            ['rol' => 'propietario', 'rol_id' => \App\Models\Rol::where('slug', 'propietario')
                ->where(fn ($q) => $q->where('negocio_id', $negocioPrincipal->id)->orWhereNull('negocio_id'))
                ->value('id'), 'esta_activa' => true],
        );

        $this->command?->info('Claves seed (desarrollo): sadmin@ebar.com / sadmin123456 | prop1@ebar.com / prop123456');
    }

    private function crearBarYPropietarioDePrueba(\App\Models\Plan $plan, string $claveGabriela): void
    {
        $gabys = Negocio::firstOrCreate(
            ['identificador' => 'gabys-bar'],
            [
                'nombre' => "Gaby's Bar",
                'ruc' => '1002003000001',
                'esta_activo' => true,
                'zona_horaria' => 'America/Guayaquil',
                'moneda' => 'USD',
                'numero_sucursales_contratadas' => 2,
            ]
        );

        if ($gabys->wasRecentlyCreated) {
            $central = Sucursal::create([
                'negocio_id' => $gabys->id,
                'nombre' => 'Sucursal Central',
                'direccion' => 'Av. Central 100',
                'telefono' => '04-2220000',
                'provincia' => 'Azuay',
                'canton' => 'Cuenca',
                'ciudad' => 'Cuenca',
                'n_cajeros_contratados' => 2,
                'esta_activa' => true,
            ]);

            Sucursal::create([
                'negocio_id' => $gabys->id,
                'nombre' => 'Sucursal Norte',
                'direccion' => 'Av. Norte 200',
                'telefono' => '04-2221111',
                'provincia' => 'Azuay',
                'canton' => 'Cuenca',
                'ciudad' => 'Cuenca',
                'n_cajeros_contratados' => 1,
                'esta_activa' => true,
            ]);

            app(ContextoNegocio::class)->establecer($gabys->id);
            session(['negocio_id' => $gabys->id]);

            \App\Models\ConfiguracionNegocio::firstOrCreate(
                ['negocio_id' => $gabys->id],
                ['nombre_negocio' => $gabys->nombre],
            );

            Membresia::firstOrCreate(
                ['negocio_id' => $gabys->id],
                ['plan_id' => $plan->id, 'estado' => 'activa', 'fecha_inicio' => now(), 'fecha_vencimiento' => now()->addYear()],
            );

            $contrato = Contrato::firstOrCreate(
                ['negocio_id' => $gabys->id, 'estado' => 'activo'],
                ['fecha_inicio' => now(), 'fecha_fin' => now()->addYear(), 'forma_contratacion' => 'anual'],
            );

            Pago::create([
                'contrato_id' => $contrato->id,
                'fecha_pago' => now(),
                'concepto' => 'Cuota inicial',
                'forma_pago' => 'transferencia',
                'valor' => 300.00,
                'estado' => 'registrado',
                'referencia' => 'PAGO-001',
            ]);

            $gabriela = User::where('correo', 'gavinocg@gmail.com')->first() ?? new User;
            if (!$gabriela->exists) {
                $gabriela->correo = 'gavinocg@gmail.com';
                $gabriela->nombre = 'Gabriela Rueda';
                $gabriela->cedula = '1002003000';
                $gabriela->celular = '0964142527';
                $gabriela->password = $claveGabriela;
                $gabriela->debe_cambiar_password = true;
                $gabriela->save();
            }

            MembresiaNegocio::create([
                'negocio_id' => $gabys->id,
                'usuario_id' => $gabriela->id,
                'rol' => 'propietario',
                'rol_id' => \App\Models\Rol::where('slug', 'propietario')
                    ->where(fn ($q) => $q->where('negocio_id', $gabys->id)->orWhereNull('negocio_id'))
                    ->value('id'),
                'esta_activa' => true,
            ]);
        }
    }
}
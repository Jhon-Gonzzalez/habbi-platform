<?php

namespace Tests\Feature;

use App\Models\Alojamiento;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FotosDemoTest extends TestCase
{
    use RefreshDatabase;

    /** Deja en storage/app/<carpeta> unas imágenes de verdad. */
    private function prepararOrigen(string $carpeta, int $cuantas = 3, int $ancho = 2400): void
    {
        $ruta = storage_path('app/' . $carpeta);
        @mkdir($ruta, 0755, true);

        for ($i = 1; $i <= $cuantas; $i++) {
            $img = imagecreatetruecolor($ancho, (int) ($ancho * 0.66));
            imagefilledrectangle($img, 0, 0, $ancho, (int) ($ancho * 0.66), imagecolorallocate($img, 30 * $i, 90, 160));
            imagejpeg($img, "{$ruta}/foto{$i}.jpg", 95);
            imagedestroy($img);
        }
    }

    private function borrarOrigen(string $carpeta): void
    {
        $ruta = storage_path('app/' . $carpeta);

        foreach (glob($ruta . '/*') ?: [] as $f) {
            @unlink($f);
        }

        @rmdir($ruta);
    }

    private function alojamiento(): Alojamiento
    {
        return Alojamiento::factory()->create([
            'user_id'    => User::factory()->create(['role' => User::ROLE_ARRENDADOR])->id,
            'photos'     => [],
            'cover_path' => null,
        ]);
    }

    public function test_reparte_las_fotos_entre_los_alojamientos_sin_foto(): void
    {
        Storage::fake('public');
        $this->prepararOrigen('fotos-test');

        $alojamiento = $this->alojamiento();

        $this->artisan('habbi:fotos-demo', ['--carpeta' => 'fotos-test', '--por' => 2])
            ->assertSuccessful();

        $alojamiento->refresh();

        $this->assertCount(2, $alojamiento->photos);
        $this->assertSame($alojamiento->photos[0], $alojamiento->cover_path);
        Storage::disk('public')->assertExists($alojamiento->photos[0]);

        $this->borrarOrigen('fotos-test');
    }

    public function test_redimensiona_las_fotos_grandes(): void
    {
        /*
         * Una foto de banco pesa 4-6 MB y viene a 4000px. Servirla tal cual
         * se come el disco del hosting compartido y los datos móviles de
         * quien abra la página desde Montería.
         */
        Storage::fake('public');
        $this->prepararOrigen('fotos-test', cuantas: 1, ancho: 2400);

        $alojamiento = $this->alojamiento();

        $this->artisan('habbi:fotos-demo', ['--carpeta' => 'fotos-test'])->assertSuccessful();

        $tamanos = getimagesizefromstring(
            Storage::disk('public')->get($alojamiento->refresh()->photos[0])
        );

        $this->assertSame(1600, $tamanos[0], 'La foto tendría que haberse reducido a 1600px.');

        $this->borrarOrigen('fotos-test');
    }

    public function test_ignora_lo_que_no_sea_una_imagen(): void
    {
        // Un .txt renombrado a .jpg hace petar a GD si se mira la extensión
        // en vez del contenido.
        Storage::fake('public');
        $this->prepararOrigen('fotos-test', cuantas: 1);
        file_put_contents(storage_path('app/fotos-test/falsa.jpg'), 'esto no es una imagen');

        $alojamiento = $this->alojamiento();

        $this->artisan('habbi:fotos-demo', ['--carpeta' => 'fotos-test', '--por' => 5])
            ->assertSuccessful();

        $this->assertCount(1, $alojamiento->refresh()->photos, 'Solo había una imagen válida.');

        $this->borrarOrigen('fotos-test');
    }

    public function test_no_pisa_las_fotos_de_quien_ya_tiene_salvo_con_forzar(): void
    {
        Storage::fake('public');
        $this->prepararOrigen('fotos-test');

        $alojamiento = $this->alojamiento();
        $alojamiento->forceFill(['photos' => ['alojamientos/mia.jpg'], 'cover_path' => 'alojamientos/mia.jpg'])->save();

        $this->artisan('habbi:fotos-demo', ['--carpeta' => 'fotos-test'])->assertSuccessful();
        $this->assertSame(['alojamientos/mia.jpg'], $alojamiento->refresh()->photos);

        $this->artisan('habbi:fotos-demo', ['--carpeta' => 'fotos-test', '--forzar' => true])->assertSuccessful();
        $this->assertNotSame(['alojamientos/mia.jpg'], $alojamiento->refresh()->photos);

        $this->borrarOrigen('fotos-test');
    }

    public function test_falla_con_aviso_si_la_carpeta_no_existe(): void
    {
        $this->artisan('habbi:fotos-demo', ['--carpeta' => 'no-existe-esta'])->assertFailed();
    }

    public function test_avisa_claro_cuando_no_hay_ningun_alojamiento(): void
    {
        /*
         * Antes decía «todos los alojamientos ya tienen fotos» con la tabla
         * vacía, y uno se quedaba buscando qué pasó con las fotos que
         * acababa de subir al servidor.
         */
        Storage::fake('public');
        $this->prepararOrigen('fotos-test');

        $this->artisan('habbi:fotos-demo', ['--carpeta' => 'fotos-test'])
            ->expectsOutputToContain('No hay ningún alojamiento')
            ->assertFailed();

        $this->borrarOrigen('fotos-test');
    }

    public function test_el_seeder_de_demo_no_corre_en_produccion_por_defecto(): void
    {
        // Una plataforma con estudiantes de verdad no puede llenarse de
        // arrendadores y reseñas inventadas por un comando sin pensar.
        app()->detectEnvironment(fn () => 'production');
        config(['habbi.datos_demo' => false]);

        $this->artisan('db:seed', ['--class' => 'MonteriaSeeder', '--force' => true])
            ->expectsOutputToContain('omitido en producción')
            ->assertSuccessful();

        $this->assertSame(0, Alojamiento::count());
    }

    public function test_el_seeder_de_demo_corre_en_produccion_si_se_pide_a_proposito(): void
    {
        // El caso legítimo: un sitio recién publicado, sin publicaciones
        // reales todavía, que necesita algo que enseñar.
        app()->detectEnvironment(fn () => 'production');
        config(['habbi.datos_demo' => true]);

        $this->artisan('db:seed', ['--class' => 'MonteriaSeeder', '--force' => true])
            ->assertSuccessful();

        $this->assertSame(20, Alojamiento::count());
    }

    /* ===================== Optimizar lo ya subido ===================== */

    /** Deja una foto enorme guardada en el disco, como las de antes del arreglo. */
    private function conFotoPesada(int $ancho = 2400): Alojamiento
    {
        $img = imagecreatetruecolor($ancho, (int) ($ancho * 0.66));

        /*
         * Hace falta ruido: un lienzo de color plano se comprime a unos
         * pocos KB y el comando lo dejaría en paz por no ganar nada, con lo
         * que la prueba no probaría nada. Pintar píxel a píxel es lo bastante
         * aleatorio con pocas pasadas, y mucho más rápido que miles de
         * rectángulos: la prueba baja de 9 segundos a menos de uno.
         */
        $alto = (int) ($ancho * 0.66);

        for ($y = 0; $y < $alto; $y += 2) {
            for ($x = 0; $x < $ancho; $x += 2) {
                imagesetpixel($img, $x, $y, imagecolorallocate($img, ($x * 7) % 256, ($y * 13) % 256, ($x + $y) % 256));
            }
        }

        ob_start();
        imagejpeg($img, null, 100);
        imagedestroy($img);

        Storage::disk('public')->put('alojamientos/pesada.jpg', (string) ob_get_clean());

        $alojamiento = $this->alojamiento();
        $alojamiento->forceFill([
            'photos'     => ['alojamientos/pesada.jpg'],
            'cover_path' => 'alojamientos/pesada.jpg',
        ])->save();

        return $alojamiento;
    }

    public function test_reduce_las_fotos_que_ya_estaban_subidas(): void
    {
        Storage::fake('public');
        $alojamiento = $this->conFotoPesada();
        $antes = strlen(Storage::disk('public')->get('alojamientos/pesada.jpg'));

        $this->artisan('habbi:optimizar-fotos')->assertSuccessful();

        $ruta = $alojamiento->refresh()->photos[0];

        $this->assertNotSame('alojamientos/pesada.jpg', $ruta, 'Debió guardarse en una ruta nueva con .jpg.');
        $this->assertLessThan($antes, strlen(Storage::disk('public')->get($ruta)));
        $this->assertSame(1600, getimagesizefromstring(Storage::disk('public')->get($ruta))[0]);
    }

    public function test_al_optimizar_la_portada_se_actualiza(): void
    {
        // Si la portada sigue apuntando al archivo viejo, que se borra, la
        // publicación se queda sin imagen en el buscador.
        Storage::fake('public');
        $alojamiento = $this->conFotoPesada();

        $this->artisan('habbi:optimizar-fotos')->assertSuccessful();

        $alojamiento->refresh();

        $this->assertSame($alojamiento->photos[0], $alojamiento->cover_path);
        Storage::disk('public')->assertExists($alojamiento->cover_path);
        Storage::disk('public')->assertMissing('alojamientos/pesada.jpg');
    }

    public function test_probar_no_toca_nada(): void
    {
        Storage::fake('public');
        $alojamiento = $this->conFotoPesada();

        $this->artisan('habbi:optimizar-fotos', ['--probar' => true])->assertSuccessful();

        $this->assertSame(['alojamientos/pesada.jpg'], $alojamiento->refresh()->photos);
        Storage::disk('public')->assertExists('alojamientos/pesada.jpg');
    }

    public function test_pasarlo_dos_veces_no_vuelve_a_recomprimir(): void
    {
        // Recomprimir un JPEG una y otra vez solo le quita calidad.
        Storage::fake('public');
        $alojamiento = $this->conFotoPesada();

        $this->artisan('habbi:optimizar-fotos')->assertSuccessful();
        $ruta = $alojamiento->refresh()->photos[0];

        $this->artisan('habbi:optimizar-fotos')->assertSuccessful();

        $this->assertSame($ruta, $alojamiento->refresh()->photos[0], 'No debió tocarla otra vez.');
    }

    public function test_el_tono_del_avatar_es_estable_y_valido(): void
    {
        // Si el tono cambiara entre peticiones, el mismo usuario saldría de
        // un color en la lista y de otro en su perfil.
        $usuario = User::factory()->create(['name' => 'Dolores Arrieta']);

        $tono = $usuario->tonoAvatar();

        $this->assertSame($tono, $usuario->fresh()->tonoAvatar());
        $this->assertGreaterThanOrEqual(0, $tono);
        $this->assertLessThan(360, $tono);
    }

    public function test_dos_personas_distintas_no_comparten_tono(): void
    {
        $a = User::factory()->create(['name' => 'Dolores Arrieta']);
        $b = User::factory()->create(['name' => 'Rafael Berrocal']);

        $this->assertNotSame($a->tonoAvatar(), $b->tonoAvatar());
    }
}

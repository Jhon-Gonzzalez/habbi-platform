<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Console\Output\BufferedOutput;
use Tests\TestCase;

class ComandoEnlaceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Ejecuta el comando y devuelve [código, salida].
     *
     * Se pasa un búfer explícito en vez de usar Artisan::output(): ese se
     * vacía en cuanto se lee una vez, y dentro de un test no siempre queda
     * enganchado al comando. Así la salida es la del comando, sin dudas.
     */
    private function correr(array $args): array
    {
        // Sin esto la salida sale vacía: dentro de un test Laravel sustituye
        // la consola por un mock de Mockery en modo silencioso, así que
        // $this->line() no escribe en ninguna parte.
        $this->withoutMockingConsoleOutput();

        $buffer = new BufferedOutput();
        $codigo = Artisan::call('habbi:enlace', $args, $buffer);

        return [$codigo, $buffer->fetch()];
    }

    private function enlaceDe(string $correo): string
    {
        [, $salida] = $this->correr(['correo' => $correo]);

        $this->assertMatchesRegularExpression('#https?://\S+#', $salida,
            "El comando no imprimió ninguna URL. Salida:\n{$salida}");

        preg_match('#(https?://\S+)#', $salida, $m);

        return $m[1];
    }

    public function test_el_enlace_que_imprime_verifica_la_cuenta_de_verdad(): void
    {
        // Lo que importa no es que imprima una URL bonita, sino que esa URL
        // funcione igual que la que llegaría por correo.
        $usuario = User::factory()->create(['email_verified_at' => null]);

        $this->actingAs($usuario)->get($this->enlaceDe($usuario->email))->assertRedirect();

        $this->assertTrue($usuario->fresh()->hasVerifiedEmail());
    }

    public function test_la_opcion_verificar_marca_la_cuenta_sin_abrir_nada(): void
    {
        $usuario = User::factory()->create(['email_verified_at' => null]);

        $this->correr(['correo' => $usuario->email, '--verificar' => true]);

        $this->assertTrue($usuario->fresh()->hasVerifiedEmail());
    }

    public function test_avisa_si_el_correo_no_existe_y_sugiere_parecidos(): void
    {
        User::factory()->create(['email' => 'jhon.gonzalez@gmail.com']);

        [$codigo, $salida] = $this->correr(['correo' => 'jhon.gonzalez@hotmail.com']);

        $this->assertSame(1, $codigo);
        $this->assertStringContainsString('jhon.gonzalez@gmail.com', $salida);
    }

    public function test_no_regenera_el_enlace_si_ya_esta_verificado(): void
    {
        $usuario = User::factory()->create();

        [, $salida] = $this->correr(['correo' => $usuario->email]);

        $this->assertStringContainsString('ya está verificado', $salida);
    }

    public function test_el_enlace_caduca(): void
    {
        $usuario = User::factory()->create(['email_verified_at' => null]);
        $enlace  = $this->enlaceDe($usuario->email);

        // Una hora y pico después, la firma ya no vale.
        $this->travel(61)->minutes();

        $this->actingAs($usuario)->get($enlace)->assertForbidden();
        $this->assertFalse($usuario->fresh()->hasVerifiedEmail());
    }

    public function test_el_enlace_de_uno_no_sirve_para_verificar_a_otro(): void
    {
        $mio  = User::factory()->create(['email_verified_at' => null]);
        $otro = User::factory()->create(['email_verified_at' => null]);

        $this->actingAs($otro)->get($this->enlaceDe($mio->email))->assertForbidden();

        $this->assertFalse($otro->fresh()->hasVerifiedEmail());
        $this->assertFalse($mio->fresh()->hasVerifiedEmail());
    }
}

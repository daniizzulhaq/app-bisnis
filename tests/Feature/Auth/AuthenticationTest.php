<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Database\Seeders\AdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_halaman_login_bisa_dibuka(): void
    {
        $this->get('/login')->assertOk()->assertSee('Masuk');
    }

    public function test_tamu_diarahkan_ke_login(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
        $this->get('/supplier')->assertRedirect('/login');
    }

    public function test_admin_seeder_bisa_login(): void
    {
        $this->seed(AdminSeeder::class);

        $response = $this->post('/login', [
            'email' => 'admin@admin.com',
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_password_salah_ditolak(): void
    {
        $user = User::factory()->create();

        $this->post('/login', ['email' => $user->email, 'password' => 'salah'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_user_bisa_logout(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/logout')->assertRedirect('/login');

        $this->assertGuest();
    }

    public function test_url_asset_mengikuti_domain_https_ngrok(): void
    {
        $this->withHeaders([
            'X-Forwarded-Proto' => 'https',
            'X-Forwarded-Host' => 'contoh.ngrok-free.app',
            'X-Forwarded-Port' => '443',
        ])->get('/login')
            ->assertSee('https://contoh.ngrok-free.app/css/app.css', false)
            ->assertSee('action="https://contoh.ngrok-free.app/login"', false);
    }

    public function test_fitur_register_dimatikan(): void
    {
        $this->get('/register')->assertNotFound();
        $this->post('/register', [])->assertNotFound();
    }

    public function test_semua_menu_sidebar_bisa_dibuka(): void
    {
        $user = User::factory()->create(['name' => 'Admin Uji']);

        $urls = ['/dashboard', '/supplier', '/barang', '/klien', '/rekapan', '/laporan/klien',
            '/laporan/supplier', '/laporan/barang', '/pembayaran', '/cashflow', '/user'];

        foreach ($urls as $url) {
            $this->actingAs($user)->get($url)->assertOk()->assertSee('Admin Uji');
        }
    }
}

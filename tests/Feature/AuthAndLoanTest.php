<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Category;
use App\Models\Member;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthAndLoanTest extends TestCase
{
    use RefreshDatabase;

    protected function createAdmin(): User
    {
        return User::firstOrCreate(
            ['email' => 'admin@pens.ac.id'],
            [
                'name' => 'Admin Perpustakaan',
                'password' => Hash::make('password'),
                'role' => 'admin',
            ]
        );
    }

    protected function createPetugas(): User
    {
        return User::firstOrCreate(
            ['email' => 'petugas1@pens.ac.id'],
            [
                'name' => 'Petugas Satu',
                'password' => Hash::make('password'),
                'role' => 'petugas',
            ]
        );
    }

    /**
     * 1. Buka /books dalam kondisi belum login - pastikan otomatis redirect ke /login.
     */
    public function test_guest_is_redirected_to_login_when_accessing_books()
    {
        $response = $this->get('/books');
        $response->assertRedirect('/login');
    }

    /**
     * 2. Login dengan admin@pens.ac.id / password - pastikan redirect ke /books dengan flash message sukses,
     *    navbar menampilkan "Admin Perpustakaan (Admin)", dan menu Kategori muncul.
     */
    public function test_admin_login_redirects_to_books_with_flash_message_and_navbar_and_category_menu()
    {
        $admin = $this->createAdmin();

        $response = $this->post('/login', [
            'email' => 'admin@pens.ac.id',
            'password' => 'password',
        ]);

        $response->assertRedirect(route('books.index'));
        $response->assertSessionHas('success');
        $this->assertAuthenticatedAs($admin);

        $booksResponse = $this->actingAs($admin)->get(route('books.index'));
        $booksResponse->assertStatus(200);
        $booksResponse->assertSee('Admin Perpustakaan (Admin)');
        $booksResponse->assertSee(route('categories.index'));
    }

    /**
     * 3. Buka /categories sebagai admin - pastikan bisa diakses normal.
     */
    public function test_admin_can_access_categories()
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->get('/categories');
        $response->assertStatus(200);
    }

    /**
     * 4. Logout, lalu login dengan petugas1@pens.ac.id / password - pastikan menu Kategori tidak muncul di navbar.
     */
    public function test_petugas_login_does_not_show_category_menu_in_navbar()
    {
        $petugas = $this->createPetugas();

        $response = $this->post('/login', [
            'email' => 'petugas1@pens.ac.id',
            'password' => 'password',
        ]);

        $response->assertRedirect(route('books.index'));
        $this->assertAuthenticatedAs($petugas);

        $booksResponse = $this->actingAs($petugas)->get(route('books.index'));
        $booksResponse->assertStatus(200);
        $booksResponse->assertSee('Petugas Satu (Petugas)');
        $booksResponse->assertDontSee(route('categories.index'));
    }

    /**
     * 5. Coba akses /categories langsung lewat URL sebagai petugas - pastikan mendapat halaman 403 Forbidden.
     */
    public function test_petugas_cannot_access_categories_directly()
    {
        $petugas = $this->createPetugas();

        $response = $this->actingAs($petugas)->get('/categories');
        $response->assertStatus(403);
    }

    /**
     * 6. Buka /loans/create sebagai petugas - pastikan tidak ada dropdown Petugas, hanya teks nama user yang login.
     */
    public function test_loans_create_as_petugas_has_no_petugas_dropdown_only_logged_in_user_name()
    {
        $petugas = $this->createPetugas();

        $response = $this->actingAs($petugas)->get('/loans/create');
        $response->assertStatus(200);
        $response->assertSee($petugas->name);
        $response->assertDontSee('<select name="user_id"', false);
    }

    /**
     * 7. Submit form peminjaman baru - pastikan di /loans kolom "Petugas" pada baris baru terisi otomatis dengan nama user yang tadi login, tanpa perlu memilih apa pun.
     */
    public function test_submit_new_loan_automatically_sets_petugas_column()
    {
        $petugas = $this->createPetugas();

        $category = Category::firstOrCreate(['nama_kategori' => 'Teknologi']);
        $book = Book::firstOrCreate(
            ['judul' => 'Pemrograman PHP Modern'],
            [
                'penulis' => 'Penulis Contoh',
                'penerbit' => 'Penerbit IT',
                'tahun_terbit' => 2024,
                'stok' => 5,
                'category_id' => $category->id,
            ]
        );

        $member = Member::firstOrCreate(
            ['nim' => '123456789'],
            [
                'nama' => 'Mahasiswa Test',
                'email' => 'mahasiswa@example.com',
                'nomor_telepon' => '08123456789',
                'alamat' => 'Surabaya',
                'status' => 'aktif',
            ]
        );

        $response = $this->actingAs($petugas)->post('/loans', [
            'member_id' => $member->id,
            'tanggal_pinjam' => now()->toDateString(),
            'tanggal_kembali' => now()->addDays(7)->toDateString(),
            'book_ids' => [$book->id],
        ]);

        $response->assertRedirect(route('loans.index'));
        $this->assertDatabaseHas('loans', [
            'member_id' => $member->id,
            'user_id' => $petugas->id,
            'status' => 'dipinjam',
        ]);

        $loansIndexResponse = $this->actingAs($petugas)->get(route('loans.index'));
        $loansIndexResponse->assertStatus(200);
        $loansIndexResponse->assertSee($petugas->name);
    }

    /**
     * 8. Login dengan password salah - pastikan muncul pesan error "Email atau password salah." tanpa membocorkan apakah emailnya valid atau tidak.
     */
    public function test_login_with_wrong_password_shows_generic_error_message()
    {
        $this->createPetugas();

        // 1. Valid email, wrong password
        $responseValidEmailWrongPassword = $this->post('/login', [
            'email' => 'petugas1@pens.ac.id',
            'password' => 'wrongpassword',
        ]);
        $responseValidEmailWrongPassword->assertSessionHasErrors([
            'email' => 'Email atau password salah.',
        ]);
        $this->assertGuest();

        // 2. Non-existent email
        $responseNonExistentEmail = $this->post('/login', [
            'email' => 'unknown@pens.ac.id',
            'password' => 'wrongpassword',
        ]);
        $responseNonExistentEmail->assertSessionHasErrors([
            'email' => 'Email atau password salah.',
        ]);
        $this->assertGuest();
    }

    /**
     * 9. Klik Logout - pastikan kembali ke /login, dan mencoba membuka /books lagi sesudahnya kembali redirect ke login.
     */
    public function test_logout_redirects_to_login_and_subsequent_books_access_redirects_to_login()
    {
        $petugas = $this->createPetugas();

        $logoutResponse = $this->actingAs($petugas)->post('/logout');
        $logoutResponse->assertRedirect('/login');
        $this->assertGuest();

        $booksResponse = $this->get('/books');
        $booksResponse->assertRedirect('/login');
    }
}

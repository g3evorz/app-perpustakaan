<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Category;
use App\Models\Loan;
use App\Models\Member;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ApiTest extends TestCase
{
    use RefreshDatabase;

    private function makeCategory(string $nama = 'Teknologi'): Category
    {
        return Category::create([
            'nama_kategori' => $nama,
            'deskripsi' => 'Kategori '.$nama,
        ]);
    }

    private function makeBook(Category $category, array $overrides = []): Book
    {
        return Book::create(array_merge([
            'judul' => 'Buku '.Str::random(5),
            'penulis' => 'Penulis',
            'penerbit' => 'Penerbit',
            'tahun_terbit' => 2024,
            'isbn' => 'ISBN'.Str::random(8),
            'stok' => 5,
            'category_id' => $category->id,
        ], $overrides));
    }

    private function makeMember(array $overrides = []): Member
    {
        return Member::create(array_merge([
            'nama' => 'Member '.Str::random(5),
            'nim' => 'NIM'.Str::random(8),
            'email' => Str::random(8).'@example.com',
            'nomor_telepon' => '08123456789',
            'alamat' => 'Surabaya',
            'status' => 'aktif',
        ], $overrides));
    }

    private function makeUser(): User
    {
        return User::create([
            'name' => 'Petugas '.Str::random(5),
            'email' => Str::random(8).'@example.com',
            'password' => 'password',
            'role' => 'petugas',
        ]);
    }

    private function makeLoan(Member $member, User $user, array $books, string $status = 'dipinjam'): Loan
    {
        $loan = Loan::create([
            'member_id' => $member->id,
            'user_id' => $user->id,
            'tanggal_pinjam' => now()->toDateString(),
            'tanggal_kembali' => now()->addDays(7)->toDateString(),
            'status' => $status,
        ]);

        foreach ($books as $book) {
            $loan->loanItems()->create(['book_id' => $book->id]);
        }

        return $loan;
    }

    public function test_books_can_be_filtered_by_judul(): void
    {
        $category = $this->makeCategory();
        $this->makeBook($category, ['judul' => 'Laskar Pelangi']);
        $this->makeBook($category, ['judul' => 'Bumi Manusia']);

        $response = $this->getJson('/api/books?judul=laskar');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.judul', 'Laskar Pelangi')
            ->assertJsonStructure(['data', 'links', 'meta']);
    }

    public function test_books_can_be_filtered_by_category_id(): void
    {
        $teknologi = $this->makeCategory('Teknologi');
        $sastra = $this->makeCategory('Sastra');

        $this->makeBook($teknologi, ['judul' => 'Buku Teknologi']);
        $this->makeBook($sastra, ['judul' => 'Buku Sastra']);

        $response = $this->getJson('/api/books?category_id='.$sastra->id);

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.judul', 'Buku Sastra')
            ->assertJsonPath('data.0.category.id', $sastra->id);
    }

    public function test_book_can_be_created_and_appears_in_list(): void
    {
        $category = $this->makeCategory();

        $payload = [
            'judul' => 'Laskar Pelangi',
            'penulis' => 'Andrea Hirata',
            'penerbit' => 'Bentang Pustaka',
            'tahun_terbit' => 2005,
            'isbn' => '9789793062792',
            'stok' => 10,
            'category_id' => $category->id,
        ];

        $response = $this->postJson('/api/books', $payload);

        $response->assertCreated()
            ->assertJsonPath('data.judul', 'Laskar Pelangi')
            ->assertJsonPath('data.category.id', $category->id);

        $this->assertDatabaseHas('books', [
            'judul' => 'Laskar Pelangi',
            'category_id' => $category->id,
        ]);

        $this->getJson('/api/books')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.judul', 'Laskar Pelangi');
    }

    public function test_book_creation_with_empty_body_returns_validation_errors(): void
    {
        $response = $this->postJson('/api/books', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'judul',
                'penulis',
                'penerbit',
                'tahun_terbit',
                'stok',
                'category_id',
            ])
            ->assertJsonPath('errors.judul.0', 'Judul buku wajib diisi.');
    }

    public function test_book_can_be_updated_and_deleted(): void
    {
        $category = $this->makeCategory();
        $book = $this->makeBook($category);

        $payload = [
            'judul' => 'Judul Diperbarui',
            'penulis' => 'Penulis Baru',
            'penerbit' => 'Penerbit Baru',
            'tahun_terbit' => 2020,
            'isbn' => $book->isbn,
            'stok' => 2,
            'category_id' => $category->id,
        ];

        $this->putJson('/api/books/'.$book->id, $payload)
            ->assertOk()
            ->assertJsonPath('data.judul', 'Judul Diperbarui')
            ->assertJsonPath('data.stok', 2);

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'judul' => 'Judul Diperbarui',
            'stok' => 2,
        ]);

        $this->getJson('/api/books/'.$book->id)
            ->assertOk()
            ->assertJsonPath('data.judul', 'Judul Diperbarui');

        $this->deleteJson('/api/books/'.$book->id)->assertOk();

        $this->assertDatabaseMissing('books', ['id' => $book->id]);
        $this->getJson('/api/books/'.$book->id)->assertNotFound();
    }

    public function test_missing_book_returns_404(): void
    {
        $this->getJson('/api/books/999')->assertNotFound();
    }

    public function test_members_are_paginated_and_detail_is_correct(): void
    {
        $members = collect(range(1, 12))->map(fn () => $this->makeMember());
        $member = $members->first();

        $this->getJson('/api/members')
            ->assertOk()
            ->assertJsonCount(10, 'data')
            ->assertJsonStructure(['data', 'links', 'meta'])
            ->assertJsonPath('meta.per_page', 10)
            ->assertJsonPath('meta.total', 12);

        $this->getJson('/api/members/'.$member->id)
            ->assertOk()
            ->assertJsonPath('data.id', $member->id)
            ->assertJsonPath('data.nim', $member->nim)
            ->assertJsonPath('data.nama', $member->nama);
    }

    public function test_loans_list_shows_member_petugas_and_books(): void
    {
        $category = $this->makeCategory();
        $book = $this->makeBook($category);
        $member = $this->makeMember();
        $user = $this->makeUser();
        $this->makeLoan($member, $user, [$book]);

        $this->getJson('/api/loans')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.member.id', $member->id)
            ->assertJsonPath('data.0.member.nama', $member->nama)
            ->assertJsonPath('data.0.petugas.id', $user->id)
            ->assertJsonPath('data.0.petugas.name', $user->name)
            ->assertJsonPath('data.0.books.0.id', $book->id)
            ->assertJsonPath('data.0.books.0.judul', $book->judul)
            ->assertJsonPath('data.0.status', 'dipinjam');
    }

    public function test_loan_can_be_created_with_status_dipinjam(): void
    {
        $category = $this->makeCategory();
        $book = $this->makeBook($category);
        $member = $this->makeMember();
        $user = $this->makeUser();

        $response = $this->postJson('/api/loans', [
            'member_id' => $member->id,
            'user_id' => $user->id,
            'tanggal_pinjam' => now()->toDateString(),
            'tanggal_kembali' => now()->addDays(7)->toDateString(),
            'book_ids' => [$book->id],
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.status', 'dipinjam')
            ->assertJsonPath('data.tanggal_dikembalikan', null)
            ->assertJsonPath('data.books.0.id', $book->id);

        $this->assertDatabaseHas('loans', [
            'member_id' => $member->id,
            'user_id' => $user->id,
            'status' => 'dipinjam',
        ]);

        $this->assertDatabaseHas('loan_items', [
            'book_id' => $book->id,
        ]);
    }

    public function test_loan_can_be_returned(): void
    {
        $category = $this->makeCategory();
        $book = $this->makeBook($category);
        $member = $this->makeMember();
        $user = $this->makeUser();
        $loan = $this->makeLoan($member, $user, [$book]);

        $response = $this->putJson('/api/loans/'.$loan->id.'/kembalikan');

        $response->assertOk()
            ->assertJsonPath('data.status', 'dikembalikan')
            ->assertJsonPath('data.tanggal_dikembalikan', now()->toDateString());

        $this->assertDatabaseHas('loans', [
            'id' => $loan->id,
            'status' => 'dikembalikan',
            'tanggal_dikembalikan' => now()->toDateString(),
        ]);
    }

    public function test_stats_reflect_actual_database_counts(): void
    {
        $category = $this->makeCategory();
        $books = collect(range(1, 3))->map(fn () => $this->makeBook($category));
        $members = collect(range(1, 2))->map(fn () => $this->makeMember());
        $user = $this->makeUser();

        $this->makeLoan($members[0], $user, [$books[0]]);
        $this->makeLoan($members[1], $user, [$books[1]], 'dikembalikan');

        $this->getJson('/api/stats')
            ->assertOk()
            ->assertJson([
                'total_buku' => 3,
                'total_anggota' => 2,
                'peminjaman_aktif' => 1,
            ]);
    }
}

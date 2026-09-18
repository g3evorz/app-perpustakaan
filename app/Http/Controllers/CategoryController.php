<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCategoryRequest;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    private array $categories = [
        ['id' => 1, 'nama_kategori' => 'Fiksi', 'deskripsi' => 'Buku cerita rekaan seperti novel dan kumpulan cerpen.'],
        ['id' => 2, 'nama_kategori' => 'Teknologi', 'deskripsi' => 'Buku seputar teknologi, pemrograman, dan ilmu komputer.'],
        ['id' => 3, 'nama_kategori' => 'Sejarah', 'deskripsi' => 'Buku bertema sejarah dan biografi tokoh.'],
    ];

    public function index()
    {
        $categories = $this->categories;

        return view('categories.index', compact('categories'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('categories.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCategoryRequest $request)
    {
        $validated = $request->validated();

        return redirect()->route('categories.index')
            ->with('success', "Kategori \"{$validated['nama_kategori']}\" berhasil ditambahkan (data dummy, belum tersimpan di database).");
    }

    /**
     * Display the specified resource.
     */

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        return "CategoryController@edit, id: {$id}";
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        return "CategoryController@update, id: {$id}";
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        return "CategoryController@destroy, id: {$id}";
    }
}

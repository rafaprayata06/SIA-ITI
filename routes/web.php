
<?php

use Illuminate\Support\Facades\Route;
use App\Models\Mahasiswa;

Route::get('/mahasiswa', function () {
    $mahasiswa = Mahasiswa::latest()->get();

    return view('mahasiswa.index', compact('mahasiswa'));
})->name('mahasiswa.index');

Route::post('/mahasiswa', function () {
    $data = request()->validate([
        'nim' => 'required|string|unique:mahasiswa,nim',
        'nama' => 'required|string|max:255',
        'program_studi' => 'required|string|max:255',
    ]);

    Mahasiswa::create($data);

    return redirect()
        ->route('mahasiswa.index')
        ->with('success', 'Data mahasiswa berhasil ditambahkan!');
})->name('mahasiswa.store');

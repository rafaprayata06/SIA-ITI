
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Demo Data Mahasiswa</title>
</head>
<body>
    <h1>Demo Data Mahasiswa</h1>

    @if (session('success'))
        <p>{{ session('success') }}</p>
    @endif

    @if ($errors->any())
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    @endif

    <h2>Tambah Mahasiswa</h2>

    <form action="{{ route('mahasiswa.store') }}" method="POST">
        @csrf

        <div>
            <label>NIM</label>
            <input type="text" name="nim" value="{{ old('nim') }}" required>
        </div>

        <div>
            <label>Nama</label>
            <input type="text" name="nama" value="{{ old('nama') }}" required>
        </div>

        <div>
            <label>Program Studi</label>
            <input type="text" name="program_studi"
                   value="{{ old('program_studi') }}" required>
        </div>

        <button type="submit">Simpan</button>
    </form>

    <h2>Daftar Mahasiswa</h2>

    <table border="1" cellpadding="8">
        <thead>
            <tr>
                <th>NIM</th>
                <th>Nama</th>
                <th>Program Studi</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($mahasiswa as $mhs)
                <tr>
                    <td>{{ $mhs->nim }}</td>
                    <td>{{ $mhs->nama }}</td>
                    <td>{{ $mhs->program_studi }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="3">Belum ada data mahasiswa.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>

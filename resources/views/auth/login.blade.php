@extends('layouts.app')
@section('judul', 'Masuk')
@section('isi')
<div class="card" style="max-width:360px;margin:40px auto">
    <h2>Masuk</h2>
    <form method="POST" action="{{ route('login.proses') }}">
        @csrf
        <p><input name="username" value="{{ old('username') }}" placeholder="Username" style="width:100%" required></p>
        @error('username')<div class="err">{{ $message }}</div>@enderror
        <p><input type="password" name="password" placeholder="Password" style="width:100%" required></p>
        <button class="btn" type="submit">Masuk</button>
    </form>
</div>
@endsection

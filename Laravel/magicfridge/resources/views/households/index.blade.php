@extends('layouts.app')
@section('title','Háztartás – MagicFridge')

@section('content')
<div class="main-wrapper">
  <div class="card">
    <h2>Háztartás</h2>
    <p class="mt-2">Aktív: <strong>{{ $household->name }}</strong></p>

    @if(session('success'))
      <div class="success mt-3">{{ session('success') }}</div>
    @endif
    @if($errors->any())
      <div class="error mt-3">{{ $errors->first() }}</div>
    @endif

    <hr class="mt-4 mb-4">

    <h3>Meghívás email alapján</h3>
    <form method="post" action="{{ route('households.invite') }}" class="mt-2">
      @csrf
      <div class="form-group">
        <label>Email</label>
        <input type="email" name="email" required>
      </div>
      <button type="submit" class="btn btn-primary">Meghívás</button>
    </form>

    <hr class="mt-4 mb-4">

    <h3>Tagok</h3>
    <table class="mt-2" style="width:100%;">
      <thead>
        <tr>
          <th>Név</th>
          <th>Rang</th>
          <th style="text-align:right;">Művelet</th>
        </tr>
      </thead>
      <tbody>
        @foreach($members as $m)
          <tr>
            <td>{{ $m->full_name }}</td>
            <td>{{ $m->role }}</td>
            <td style="text-align:right;">
              @if((int)$household->owner_id === (int)session('user_id') && $m->role !== 'admin')
                <form method="post" action="{{ route('households.toggleRole') }}" style="display:inline;">
                  @csrf
                  <input type="hidden" name="hm_id" value="{{ $m->hm_id }}">
                  <button class="btn btn-secondary" type="submit">Rang váltás</button>
                </form>
              @else
                —
              @endif
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>

  </div>
</div>
@endsection

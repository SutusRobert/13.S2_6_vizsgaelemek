@extends('layouts.app')
@section('title','Üzenetek – MagicFridge')

@section('content')
<div class="main-wrapper">
  <div class="card">
    <h2>📩 Üzenetek</h2>

    @if(session('success'))
      <div class="success mt-3">{{ session('success') }}</div>
    @endif
    @if($errors->any())
      <div class="error mt-3">{{ $errors->first() }}</div>
    @endif

    @if(empty($messages))
      <p class="inv-muted mt-3">Nincs üzeneted.</p>
    @else
      <table class="inv-table mt-3" style="width:100%;">
        <thead>
          <tr>
            <th>Cím</th>
            <th>Üzenet</th>
            <th>Állapot</th>
            <th style="text-align:right;">Művelet</th>
          </tr>
        </thead>
        <tbody>
        @foreach($messages as $m)
          <tr>
            <td style="font-weight:700;">{{ $m->title ?? 'Üzenet' }}</td>
            <td>{{ $m->body ?? '' }}</td>
            <td>{{ (int)($m->is_read ?? 0) === 1 ? 'Olvasott' : 'Új' }}</td>
            <td style="text-align:right; white-space:nowrap;">
              @php
                $link = (string)($m->link_url ?? '');
                $isInvite = str_starts_with($link, 'invite:');
                $isUnread = (int)($m->is_read ?? 0) === 0;
              @endphp

              @if($isInvite && $isUnread)
                <form method="POST" action="{{ route('messages.respond') }}" style="display:inline-block;">
                  @csrf
                  <input type="hidden" name="id" value="{{ $m->id }}">
                  <input type="hidden" name="action" value="accept">
                  <button class="btn btn-primary">Elfogadás</button>
                </form>

                <form method="POST" action="{{ route('messages.respond') }}" style="display:inline-block;">
                  @csrf
                  <input type="hidden" name="id" value="{{ $m->id }}">
                  <input type="hidden" name="action" value="decline">
                  <button class="btn btn-danger">Elutasítás</button>
                </form>
              @else
                @if($isUnread)
                  <form method="POST" action="{{ route('messages.read') }}" style="display:inline-block;">
                    @csrf
                    <input type="hidden" name="id" value="{{ $m->id }}">
                    <button class="btn btn-secondary">Olvasott</button>
                  </form>
                @else
                  —
                @endif
              @endif
            </td>
          </tr>
        @endforeach
        </tbody>
      </table>
    @endif
  </div>
</div>
@endsection

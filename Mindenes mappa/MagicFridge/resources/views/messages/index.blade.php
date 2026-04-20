@extends('layouts.app')

@section('title', 'Üzenetek – MagicFridge')

@section('content')
  <div class="card">
    <h2>Üzenetek</h2>

    @if(session('success'))
      <div class="success mt-3">{{ session('success') }}</div>
    @endif

    @if($errors->any())
      <div class="error mt-3">{{ $errors->first() }}</div>
    @endif

    @if(empty($messages))
      <p class="mt-3">Nincs üzeneted.</p>
    @else
      <div class="mt-3">
        @foreach($messages as $m)
          @php
            $isRead = (int)($m->is_read ?? 0) === 1;
            $title = (string)($m->title ?? 'Üzenet');
            $body  = (string)($m->body ?? '');
            $link  = (string)($m->link_url ?? '');

            $isInvite = str_starts_with($link, 'invite:');
            $isInventory = str_starts_with($link, 'inventory:');
            $hid = $isInventory ? (int)substr($link, strlen('inventory:')) : 0;
          @endphp

          <div class="card mt-3" style="padding:16px;">
            <div style="display:flex; justify-content:space-between; gap:10px; align-items:center;">
              <div>
                <strong>{{ $title }}</strong>
                @if(!$isRead)
                  <span class="badge" style="margin-left:8px;">ÚJ</span>
                @endif
              </div>
              <div style="opacity:.7; font-size:12px;">
                {{ $m->created_at ?? '' }}
              </div>
            </div>

            @if($body !== '')
              <p class="mt-2">{{ $body }}</p>
            @endif

            @if($isInventory && $hid > 0)
              <div class="mt-2">
                <a class="btn" href="{{ route('inventory.list', ['hid' => $hid]) }}">Készlet megnyitása</a>
              </div>
            @endif

            <div class="mt-3" style="display:flex; gap:10px; flex-wrap:wrap;">
              {{-- Meghívó elfogadás/elutasítás --}}
              @if($isInvite)
                <form method="post" action="{{ route('messages.invite.respond') }}">
                  @csrf
                  <input type="hidden" name="id" value="{{ (int)$m->id }}">
                  <input type="hidden" name="action" value="accept">
                  <button class="btn" type="submit">Elfogadom</button>
                </form>

                <form method="post" action="{{ route('messages.invite.respond') }}">
                  @csrf
                  <input type="hidden" name="id" value="{{ (int)$m->id }}">
                  <input type="hidden" name="action" value="decline">
                  <button class="btn danger" type="submit">Elutasítom</button>
                </form>
              @endif

                <form method="post" action="{{ route('messages.delete') }}" style="margin:0; display:inline-block;">
                  @csrf
                  <input type="hidden" name="id" value="{{ (int)$m->id }}">
                  <button type="submit" class="btn btn-secondary">Eltüntetés</button>
                </form>

              {{-- Olvasott --}}
              @if(!$isRead)
                <form method="post" action="{{ route('messages.read') }}">
                  @csrf
                  <input type="hidden" name="id" value="{{ (int)$m->id }}">
                  <button class="btn" type="submit">Olvasott</button>
                </form>
              @endif
            </div>
          </div>
        @endforeach
      </div>
    @endif
  </div>
@endsection

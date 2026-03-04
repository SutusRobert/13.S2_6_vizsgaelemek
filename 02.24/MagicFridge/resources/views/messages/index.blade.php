@extends('layouts.app')

@section('title', 'Üzenetek – MagicFridge')

@section('content')
  <div class="card">
    <h2>Messages</h2>

    @if(session('success'))
      <div class="success mt-3">{{ session('success') }}</div>
    @endif

    @if($errors->any())
      <div class="error mt-3">{{ $errors->first() }}</div>
    @endif

    @if(empty($messages))
      <p class="mt-3">You have no messages.</p>
    @else
      <div class="mt-3">
        @foreach($messages as $m)
          @php
            $isRead = (int)($m->is_read ?? 0) === 1;
            $title = (string)($m->title ?? 'Message');
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
                  <span class="badge" style="margin-left:8px;">New</span>
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
                <a class="btn" href="{{ route('inventory.list', ['hid' => $hid]) }}">Open inventory</a>
              </div>
            @endif

            <div class="mt-3" style="display:flex; gap:10px; flex-wrap:wrap;">
              {{-- Accept / Decline invitation --}}
              @if($isInvite)
                <form method="post" action="{{ route('messages.invite.respond') }}">
                  @csrf
                  <input type="hidden" name="id" value="{{ (int)$m->id }}">
                  <input type="hidden" name="action" value="accept">
                  <button class="btn" type="submit">Accept</button>
                </form>

                <form method="post" action="{{ route('messages.invite.respond') }}">
                  @csrf
                  <input type="hidden" name="id" value="{{ (int)$m->id }}">
                  <input type="hidden" name="action" value="decline">
                  <button class="btn danger" type="submit">Decline</button>
                </form>
              @endif

                <form method="post" action="{{ route('messages.delete') }}" style="margin:0; display:inline-block;">
                  @csrf
                  <input type="hidden" name="id" value="{{ (int)$m->id }}">
                  <button type="submit" class="btn btn-secondary">Disappear</button>
                </form>

              {{-- Olvasott --}}
              @if(!$isRead)
                <form method="post" action="{{ route('messages.read') }}">
                  @csrf
                  <input type="hidden" name="id" value="{{ (int)$m->id }}">
                  
                </form>
              @endif
            </div>
          </div>
        @endforeach
      </div>
    @endif
  </div>
@endsection

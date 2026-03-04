@extends('layouts.app')
@section('title','Háztartás – MagicFridge')

@section('content')
<div class="main-wrapper">
  <div class="card" style="max-width: 980px; width:100%;">

    <h2>Household</h2>
    <div class="small mt-2" style="opacity:.8;">
     Household name <strong>{{ $household->name }}</strong>
    </div>

    @if(session('success'))
      <div class="success mt-3">{{ session('success') }}</div>
    @endif

    @if($errors->any())
      <div class="error mt-3">{{ $errors->first() }}</div>
    @endif

    <div class="mt-4">
      <h3>Invite member (from registered users)</h3>

      <form method="post" action="{{ route('households.invite') }}" class="mt-2">
        @csrf

        <div class="form-group">
          <label>Email (exactly as it was registered)</label>
          <input type="email" name="email" required>
        </div>

        <button type="submit" class="btn btn-primary">Send invitation</button>
      </form>
    </div>

    <div class="mt-4">
      <h3>Members</h3>

      <div class="mt-3" style="display:flex; flex-direction:column; gap:12px;">
        @foreach($members as $m)
          @php
            $isOwner = (int)$household->owner_id === (int)session('user_id');
            $canPromote = $isOwner && ((string)$m->role !== 'admin'); // a jelenlegi logikád ezt engedi
          @endphp

          <div style="
            display:flex;
            align-items:center;
            justify-content:space-between;
            gap:12px;
            padding:12px 14px;
            border-radius:14px;
            background: rgba(255,255,255,0.06);
            border: 1px solid rgba(255,255,255,0.08);
          ">
            <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
              <strong>{{ $m->full_name }}</strong>
              <span class="badge">{{ $m->role }}</span>
            </div>

            <div>
              @if($canPromote)
                <form method="post" action="{{ route('households.toggleRole') }}" style="margin:0;">
                  @csrf
                  <input type="hidden" name="hm_id" value="{{ $m->hm_id }}">
                  <button class="btn btn-secondary" type="submit">Add role</button>
                </form>
              @else
                {{-- ha nem tulaj vagy admin a tag: nincs gomb --}}
              @endif
            </div>
          </div>
        @endforeach
      </div>

    </div>

  </div>
</div>
@endsection

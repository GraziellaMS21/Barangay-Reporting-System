@extends('layouts.admin')

@section('title', 'Notifications')
@section('page-heading', 'Notifications')

@section('content')
<div class="page-heading">
  <h2>Admin notifications</h2>
  <p>In-app alerts for key events. Use the button below to send a test email.</p>

  <div style="display:flex;gap:.75rem;flex-wrap:wrap;margin-top:.5rem;">
    {{-- Send test email --}}
    <form method="POST" action="{{ route('admin.notifications.test-email') }}">
      @csrf
      <button type="submit" class="btn btn-outline btn-small">
        <i class="fa-solid fa-paper-plane" aria-hidden="true"></i> Send test email
      </button>
    </form>

    {{-- Mark all as read --}}
    @if($notifications->total() > 0)
      <form method="POST" action="{{ route('admin.notifications.mark-all-read') }}">
        @csrf
        <button type="submit" class="btn btn-outline btn-small">
          <i class="fa-solid fa-check-double" aria-hidden="true"></i> Mark all as read
        </button>
      </form>
    @endif
  </div>
</div>

@include('admin.settings.partials.validation-errors')

<section class="card">
  <h2 class="card-title">
    <i class="fa-solid fa-bell" aria-hidden="true"></i> Notification log
  </h2>
  <div class="table-wrap">
    <table class="data-table data-table-cards">
      <thead>
        <tr>
          <th scope="col">Type</th>
          <th scope="col">Message</th>
          <th scope="col">Received</th>
          <th scope="col">Status</th>
          <th scope="col">Actions</th>
        </tr>
      </thead>
      <tbody>
        @forelse($notifications as $notification)
          <tr @class(['opacity-50' => $notification->read])>
            <td data-label="Type">
              <span class="role-badge {{ $notification->type === 'test' ? 'role-staff' : 'role-admin' }}">
                {{ ucfirst($notification->type) }}
              </span>
            </td>
            <td data-label="Message">{{ $notification->message }}</td>
            <td data-label="Received">{{ $notification->created_at->format('M d, Y H:i') }}</td>
            <td data-label="Status">
              @if($notification->read)
                <span class="role-badge role-staff">Read</span>
              @else
                <span class="role-badge role-admin">Unread</span>
              @endif
            </td>
            <td data-label="Actions" style="display:flex;gap:.5rem;flex-wrap:wrap;">
              @unless($notification->read)
                <form method="POST" action="{{ route('admin.notifications.mark-read', $notification) }}">
                  @csrf @method('PATCH')
                  <button type="submit" class="btn btn-outline btn-small">Mark read</button>
                </form>
              @endunless
              <form method="POST" action="{{ route('admin.notifications.destroy', $notification) }}">
                @csrf @method('DELETE')
                <button type="submit" class="btn btn-outline btn-small"
                  onclick="return confirm('Delete this notification?')">
                  <i class="fa-solid fa-trash" aria-hidden="true"></i>
                </button>
              </form>
            </td>
          </tr>
        @empty
          <tr>
            <td class="table-empty" colspan="5">No notifications yet. Click "Send test email" to create one.</td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>

  {{-- Pagination --}}
  @if($notifications->hasPages())
    <div style="padding:1rem;">{{ $notifications->links() }}</div>
  @endif
</section>
@endsection

@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Servers</h2>
        <div class="d-flex gap-2">
            <form action="{{ route('admin.servers.index') }}" method="GET" class="d-flex">
                <input type="text" name="search" class="form-control form-control-sm" 
                       placeholder="Search..." value="{{ request('search') }}">
            </form>
            <form action="{{ route('admin.servers.sync') }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-sm btn-primary">
                    <i class="bi bi-arrow-clockwise"></i> Sync All
                </button>
            </form>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="table-responsive">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>User</th>
                    <th>Status</th>
                    <th>Type</th>
                    <th>Location</th>
                    <th>IPv4</th>
                    <th>Created</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($servers as $server)
                <tr>
                    <td>{{ $server->hetzner_id }}</td>
                    <td>
                        <strong>{{ $server->name }}</strong>
                        @if($server->hostname)
                            <br><small class="text-muted">{{ $server->hostname }}</small>
                        @endif
                    </td>
                    <td>
                        <a href="{{ route('admin.users.show', $server->user_id) }}">
                            {{ $server->user->name }}
                        </a>
                    </td>
                    <td>
                        <span class="badge bg-{{ 
                            $server->status === 'running' ? 'success' : 
                            ($server->status === 'stopped' ? 'danger' : 
                            ($server->status === 'error' ? 'danger' : 'warning')) 
                        }}">
                            {{ ucfirst($server->status) }}
                        </span>
                    </td>
                    <td>{{ $server->serverType->name ?? 'N/A' }}</td>
                    <td>{{ $server->datacenter->location ?? 'N/A' }}</td>
                    <td>{{ $server->ipv4_address ?? 'N/A' }}</td>
                    <td>{{ $server->created_at->format('Y-m-d H:i') }}</td>
                    <td>
                        <div class="btn-group btn-group-sm">
                            <a href="{{ route('admin.servers.show', $server) }}" 
                               class="btn btn-outline-primary">View</a>
                            @if(!$server->locked)
                            <button class="btn btn-outline-danger" 
                                    onclick="deleteServer({{ $server->id }})">Delete</button>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" class="text-center">No servers found</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $servers->withQueryString()->links() }}
</div>

<script>
function deleteServer(id) {
    if (!confirm('Delete this server from Hetzner?')) return;
    
    fetch(`/admin/servers/${id}`, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.error || 'Failed to delete server');
        }
    });
}
</script>
@endsection
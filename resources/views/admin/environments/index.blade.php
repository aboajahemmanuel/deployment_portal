@extends('layouts.deployment')

@section('title', 'Environment Management')

@section('content')
<div class="nk-block">
    <div class="nk-block-head nk-block-head-sm">
        <div class="nk-block-between">
            <div class="nk-block-head-content">
                <h3 class="nk-block-title page-title">Environment Management</h3>
                <p class="nk-block-des text-soft">Manage deployment environments for your projects</p>
            </div>
            <div class="nk-block-head-content">
                <a href="{{ route('admin.environments.create') }}" class="btn btn-primary">
                    <em class="icon ni ni-plus"></em>
                    <span>Add Environment</span>
                </a>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card card-bordered">
        <div class="card-inner">
            @if($environments->count() > 0)
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Order</th>
                                <th>Name</th>
                                <th>Slug</th>
                                <th>Server Path</th>
                                <th>Web URL</th>
                                <th>Status</th>
                                <th>Projects</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($environments as $environment)
                                <tr>
                                    <td>
                                        <span class="badge bg-secondary">{{ $environment->order }}</span>
                                    </td>
                                    <td>
                                        <strong>{{ $environment->name }}</strong>
                                        @if($environment->description)
                                            <br><small class="text-muted">{{ $environment->description }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        <code>{{ $environment->slug }}</code>
                                    </td>
                                    <td>
                                        <small class="text-muted">{{ $environment->server_base_path }}</small>
                                    </td>
                                    <td>
                                        <a href="{{ $environment->web_base_url }}" target="_blank" rel="noopener" class="text-primary">
                                            {{ $environment->web_base_url }}
                                            <em class="icon ni ni-external"></em>
                                        </a>
                                    </td>
                                    <td>
                                        <form action="{{ route('admin.environments.toggle-active', $environment) }}" method="POST" style="display: inline;">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="btn btn-sm btn-{{ $environment->is_active ? 'success' : 'secondary' }}">
                                                {{ $environment->is_active ? 'Active' : 'Inactive' }}
                                            </button>
                                        </form>
                                    </td>
                                    <td>
                                        <span class="badge bg-info">{{ $environment->projectEnvironments->count() }}</span>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="{{ route('admin.environments.show', $environment) }}" class="btn btn-sm btn-outline-primary" title="View Environment">
                                                <em class="icon ni ni-eye"></em>
                                            </a>
                                            <a href="{{ route('admin.environments.edit', $environment) }}" class="btn btn-sm btn-outline-secondary" title="Edit Environment">
                                                <em class="icon ni ni-edit"></em>
                                            </a>
                                            @if($environment->projectEnvironments->count() > 0)
                                                <button type="button" class="btn btn-sm btn-outline-danger" disabled 
                                                    title="Cannot delete environment with {{ $environment->projectEnvironments->count() }} project(s)" 
                                                    style="cursor: not-allowed;">
                                                    <em class="icon ni ni-trash"></em>
                                                </button>
                                            @else
                                                <button type="button" class="btn btn-sm btn-outline-danger" 
                                                    title="Delete Environment"
                                                    onclick="confirmDeleteEnvironment('{{ $environment->name }}', '{{ route('admin.environments.destroy', $environment) }}')">
                                                    <em class="icon ni ni-trash"></em>
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center py-5">
                    <em class="icon icon-lg ni ni-server text-muted mb-3"></em>
                    <h5>No environments found</h5>
                    <p class="text-muted">Get started by creating your first deployment environment.</p>
                    <a href="{{ route('admin.environments.create') }}" class="btn btn-primary">
                        <em class="icon ni ni-plus"></em>
                        <span>Create Environment</span>
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function confirmDeleteEnvironment(environmentName, deleteUrl) {
    Swal.fire({
        title: 'Delete Environment?',
        text: `Are you sure you want to delete the "${environmentName}" environment? This action cannot be undone.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#e85347',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, delete it!',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            // Create a form and submit it
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = deleteUrl;
            
            // Add CSRF token
            const csrfToken = document.createElement('input');
            csrfToken.type = 'hidden';
            csrfToken.name = '_token';
            csrfToken.value = '{{ csrf_token() }}';
            form.appendChild(csrfToken);
            
            // Add DELETE method
            const methodField = document.createElement('input');
            methodField.type = 'hidden';
            methodField.name = '_method';
            methodField.value = 'DELETE';
            form.appendChild(methodField);
            
            // Append to body and submit
            document.body.appendChild(form);
            form.submit();
        }
    });
}
</script>
@endpush

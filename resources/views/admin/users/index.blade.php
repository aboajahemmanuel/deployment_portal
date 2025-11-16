@extends('layouts.deployment')

@section('title', 'User Management | Deployment Manager')

@section('content')
<!-- content @s -->
<div class="nk-block">
    <div class="nk-block-head nk-block-head-sm">
        <div class="nk-block-between">
            <div class="nk-block-head-content">
                <h3 class="nk-block-title page-title">User Management</h3>
                <div class="nk-block-des text-soft">
                    <p>Manage users and onboard new team members</p>
                </div>
            </div><!-- .nk-block-head-content -->
            <div class="nk-block-head-content">
                <div class="toggle-wrap nk-block-tools-toggle">
                    <a href="#" class="btn btn-icon btn-trigger toggle-expand me-n1" data-target="pageMenu"><em class="icon ni ni-menu-alt-r"></em></a>
                    <div class="toggle-expand-content" data-content="pageMenu">
                        <ul class="nk-block-tools g-3">
                            <li class="nk-block-tools-opt">
                                <a href="#" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
                                    <em class="icon ni ni-plus"></em>
                                    <span>Add New User</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div><!-- .nk-block-head-content -->
        </div><!-- .nk-block-between -->
    </div><!-- .nk-block-head -->

    <!-- Search and Filter Section -->
    <div class="card card-bordered mb-3">
        <div class="card-inner">
            <form method="GET" action="{{ route('admin.users.index') }}" class="row g-3">
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="form-label">Search Users</label>
                        <input type="text" name="search" class="form-control" placeholder="Search by name or email..." value="{{ request('search') }}">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label class="form-label">Filter by Role</label>
                        <select name="role" class="form-select">
                            <option value="">All Roles</option>
                            @foreach($roles as $role)
                                <option value="{{ $role->name }}" {{ request('role') == $role->name ? 'selected' : '' }}>
                                    {{ ucfirst($role->name) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label class="form-label">Filter by Status</label>
                        <select name="status" class="form-select">
                            <option value="">All Status</option>
                            <option value="verified" {{ request('status') == 'verified' ? 'selected' : '' }}>Verified</option>
                            <option value="unverified" {{ request('status') == 'unverified' ? 'selected' : '' }}>Unverified</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label class="form-label">&nbsp;</label>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">Filter</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
    
    <div class="card card-bordered">
        <div class="card-inner">
            <div class="table-responsive">
                <table class="table table-lg">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Created At</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($users as $user)
                        <tr>
                            <td>
                                <div class="user-card">
                                    <div class="user-avatar bg-primary">
                                        <span>{{ strtoupper(substr($user->name, 0, 2)) }}</span>
                                    </div>
                                    <div class="user-info">
                                        <span class="tb-lead">{{ $user->name }}</span>
                                        <span class="fs-12px text-muted">ID: {{ $user->id }}</span>
                                    </div>
                                </div>
                            </td>
                            <td>{{ $user->email }}</td>
                            <td>
                                @forelse($user->roles as $role)
                                    <span class="badge badge-sm badge-dot has-bg bg-{{ $role->name === 'admin' ? 'danger' : ($role->name === 'developer' ? 'primary' : 'success') }} d-none d-sm-inline-flex">
                                        {{ ucfirst($role->name) }}
                                    </span>
                                @empty
                                    <span class="badge badge-sm badge-dot has-bg bg-gray d-none d-sm-inline-flex">No Role</span>
                                @endforelse
                            </td>
                            <td>
                                @if($user->email_verified_at)
                                    <span class="badge badge-sm badge-dot has-bg bg-success d-none d-sm-inline-flex">Verified</span>
                                @else
                                    <span class="badge badge-sm badge-dot has-bg bg-warning d-none d-sm-inline-flex">Unverified</span>
                                @endif
                            </td>
                            <td>{{ $user->created_at->format('M d, Y') }}</td>
                            <td class="text-end">
                                <div class="dropdown">
                                    <a href="#" class="dropdown-toggle btn btn-icon btn-trigger" data-bs-toggle="dropdown">
                                        <em class="icon ni ni-more-h"></em>
                                    </a>
                                    <div class="dropdown-menu dropdown-menu-end">
                                        <ul class="link-list-opt no-bdr">
                                            <li><a href="{{ route('admin.users.show', $user) }}"><em class="icon ni ni-eye"></em><span>View Details</span></a></li>
                                            <li><a href="{{ route('admin.users.edit', $user) }}"><em class="icon ni ni-edit"></em><span>Edit User</span></a></li>
                                            <li><a href="#" onclick="editUserRole({{ $user->id }}, '{{ $user->roles->pluck('name')->implode(',') }}')"><em class="icon ni ni-user-check"></em><span>Manage Roles</span></a></li>
                                            @if(!$user->email_verified_at)
                                                <li><a href="#" onclick="sendVerificationEmail({{ $user->id }})"><em class="icon ni ni-mail"></em><span>Send Verification</span></a></li>
                                            @endif
                                            <li><a href="#" onclick="resetUserPassword({{ $user->id }})"><em class="icon ni ni-lock"></em><span>Reset Password</span></a></li>
                                            @if($user->id !== auth()->id())
                                                <li class="divider"></li>
                                                <li><a href="#" onclick="confirmDelete({{ $user->id }})" class="text-danger"><em class="icon ni ni-trash"></em><span>Delete User</span></a></li>
                                            @endif
                                        </ul>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center">
                                <div class="nk-block-content py-5">
                                    <em class="icon icon-lg ni ni-users text-muted mb-3"></em>
                                    <p class="text-muted">No users found</p>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            @if($users->hasPages())
            <div class="card-inner">
                {{ $users->links() }}
            </div>
            @endif
        </div>
    </div>
</div>

<!-- Add User Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addUserModalLabel">Add New User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('admin.users.store') }}" method="POST" id="addUserForm">
                @csrf
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Full Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Email Address <span class="text-danger">*</span></label>
                                <input type="email" name="email" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="alert alert-info">
                                <em class="icon ni ni-info"></em>
                                <strong>Auto-Generated Password:</strong> A secure password will be automatically generated and sent to the user's email address along with their login credentials.
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Role <span class="text-danger">*</span></label>
                                <select name="role" class="form-select" required>
                                    <option value="">Select Role</option>
                                    @foreach($roles as $role)
                                        <option value="{{ $role->name }}">{{ ucfirst($role->name) }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                     
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create User</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Role Management Modal -->
<div class="modal fade" id="roleModal" tabindex="-1" aria-labelledby="roleModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="roleModalLabel">Manage User Roles</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="roleForm" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <div class="form-group">
                        <label class="form-label">Select Roles</label>
                        <div class="custom-control-group">
                            @foreach($roles as $role)
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input" id="role_{{ $role->id }}" name="roles[]" value="{{ $role->name }}">
                                    <label class="custom-control-label" for="role_{{ $role->id }}">{{ ucfirst($role->name) }}</label>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Roles</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Password Reset Modal -->
<div class="modal fade" id="passwordModal" tabindex="-1" aria-labelledby="passwordModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="passwordModalLabel">Reset Password</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="passwordForm" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="form-group">
                        <label class="form-label">New Password</label>
                        <input type="password" name="password" class="form-control" required minlength="8">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Confirm Password</label>
                        <input type="password" name="password_confirmation" class="form-control" required minlength="8">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Reset Password</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
function editUserRole(userId, currentRoles) {
    const form = document.getElementById('roleForm');
    form.action = `/admin/users/${userId}`;
    
    // Clear all checkboxes
    document.querySelectorAll('#roleModal input[type="checkbox"]').forEach(checkbox => {
        checkbox.checked = false;
    });
    
    // Check current roles
    if (currentRoles) {
        const roles = currentRoles.split(',');
        roles.forEach(role => {
            const checkbox = document.querySelector(`#roleModal input[value="${role.trim()}"]`);
            if (checkbox) {
                checkbox.checked = true;
            }
        });
    }
    
    new bootstrap.Modal(document.getElementById('roleModal')).show();
}

function resetUserPassword(userId) {
    const form = document.getElementById('passwordForm');
    form.action = `/admin/users/${userId}/reset-password`;
    
    new bootstrap.Modal(document.getElementById('passwordModal')).show();
}

function sendVerificationEmail(userId) {
    if (confirm('Send verification email to this user?')) {
        fetch(`/admin/users/${userId}/send-verification`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Content-Type': 'application/json',
            },
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Verification email sent successfully!');
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            alert('Error sending verification email');
        });
    }
}

function confirmDelete(userId) {
    if (confirm('Are you sure you want to delete this user? This action cannot be undone.')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `/admin/users/${userId}`;
        
        const csrfToken = document.createElement('input');
        csrfToken.type = 'hidden';
        csrfToken.name = '_token';
        csrfToken.value = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        
        const methodInput = document.createElement('input');
        methodInput.type = 'hidden';
        methodInput.name = '_method';
        methodInput.value = 'DELETE';
        
        form.appendChild(csrfToken);
        form.appendChild(methodInput);
        document.body.appendChild(form);
        form.submit();
    }
}

// Form validation - removed password validation since passwords are auto-generated

document.getElementById('passwordForm').addEventListener('submit', function(e) {
    const password = this.querySelector('input[name="password"]').value;
    const confirmPassword = this.querySelector('input[name="password_confirmation"]').value;
    
    if (password !== confirmPassword) {
        e.preventDefault();
        alert('Passwords do not match!');
        return false;
    }
});
</script>
@endpush
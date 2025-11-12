@extends('layouts.deployment')

@section('content')
<div class="nk-content">
  <div class="container-fluid">
    <div class="nk-content-inner">
      <div class="nk-content-body">
        <div class="nk-block-head nk-block-head-sm">
          <div class="nk-block-between">
            <div class="nk-block-head-content">
              <h3 class="nk-block-title page-title">Deployment Files</h3>
              <div class="nk-block-des text-soft">Browse, edit, and delete deployment files directly on the server.</div>
            </div>
            <div class="nk-block-head-content">
              <a href="{{ route('admin.deployment-files.create') }}" class="btn btn-primary"><em class="icon ni ni-plus"></em><span>Create New</span></a>
            </div>
          </div>
        </div>

        @if (session('status'))
          <div class="alert alert-success">{{ session('status') }}</div>
        @endif
        @if ($errors->any())
          <div class="alert alert-danger">
            <ul class="mb-0">
              @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
              @endforeach
            </ul>
          </div>
        @endif

        <div class="card card-bordered mb-3">
          <div class="card-inner">
            <form method="GET" action="{{ route('admin.deployment-files.index') }}" class="row g-2 align-items-end">
              <div class="col-md-8">
                <label class="form-label">Server UNC Path</label>
                <input type="text" name="server_path" class="form-control" value="{{ request('server_path', $serverPath) }}" placeholder="\\\\10.10.16.47\\c$\\wamp64\\www\\dep_env">
                <small class="text-soft">Change target directory if needed. Must be a valid UNC path you have access to.</small>
              </div>
              <div class="col-md-4">
                <button class="btn btn-secondary"><em class="icon ni ni-reload"></em><span>Refresh</span></button>
              </div>
            </form>
          </div>
        </div>

        <div class="card card-bordered">
          <div class="card-inner">
            <div class="nk-tb-list is-separate">
              <div class="nk-tb-item nk-tb-head">
                <div class="nk-tb-col"><span class="sub-text">Filename</span></div>
                <div class="nk-tb-col tb-col-md"><span class="sub-text">Size</span></div>
                <div class="nk-tb-col tb-col-md"><span class="sub-text">Modified</span></div>
                <div class="nk-tb-col tb-col-end"><span class="sub-text">Actions</span></div>
              </div>

              @forelse($files as $file)
                <div class="nk-tb-item">
                  <div class="nk-tb-col">
                    <span class="tb-lead">{{ $file['name'] }}</span>
                    <div class="sub-text text-soft small">{{ $file['path'] }}</div>
                  </div>
                  <div class="nk-tb-col tb-col-md">{{ number_format($file['size']) }} bytes</div>
                  <div class="nk-tb-col tb-col-md">{{ $file['modified'] ? date('Y-m-d H:i:s', $file['modified']) : '-' }}</div>
                  <div class="nk-tb-col tb-col-end">
                    <div class="btn-group">
                      <a class="btn btn-sm btn-primary" href="{{ route('admin.deployment-files.edit', ['file' => $file['token']]) }}"><em class="icon ni ni-edit"></em><span>Edit</span></a>
                      <form method="POST" action="{{ route('admin.deployment-files.destroy') }}" class="js-delete-form">
                        @csrf
                        @method('DELETE')
                        <input type="hidden" name="file" value="{{ $file['token'] }}">
                        <button type="submit" class="btn btn-sm btn-danger"><em class="icon ni ni-trash"></em><span>Delete</span></button>
                      </form>
                    </div>
                  </div>
                </div>
              @empty
                <div class="nk-tb-item">
                  <div class="nk-tb-col">No PHP deployment files found in this directory.</div>
                </div>
              @endforelse
            </div>
          </div>
        </div>

        <div class="alert alert-info mt-3">
          <em class="icon ni ni-info me-1"></em>
          Ensure the web server account has permission to read/write in {{ $serverPath }}.
        </div>

      </div>
    </div>
  </div>

      </div>
    </div>
  </div>
</div>

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
  (function(){
    // Success message
    @if (session('status'))
      Swal.fire({
        icon: 'success',
        title: 'Success',
        text: @json(session('status')),
        confirmButtonColor: '#3085d6'
      });
    @endif

    // Error messages
    @if ($errors->any())
      Swal.fire({
        icon: 'error',
        title: 'Error',
        html: @json('<ul class="text-start mb-0">' . collect($errors->all())->map(fn($e) => '<li>'.e($e).'</li>')->implode('') . '</ul>'),
        confirmButtonColor: '#d33'
      });
    @endif

    // Delete confirmation
    document.querySelectorAll('.js-delete-form').forEach(function(form){
      form.addEventListener('submit', function(e){
        e.preventDefault();
        Swal.fire({
          title: 'Delete this file?',
          text: 'This action cannot be undone.',
          icon: 'warning',
          showCancelButton: true,
          confirmButtonColor: '#d33',
          cancelButtonColor: '#6c757d',
          confirmButtonText: 'Yes, delete it'
        }).then((result) => {
          if (result.isConfirmed) {
            form.submit();
          }
        });
      });
    });
  })();
  </script>
@endsection

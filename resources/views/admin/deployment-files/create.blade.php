@extends('layouts.deployment')

@section('content')
<div class="nk-content">
  <div class="container-fluid">
    <div class="nk-content-inner">
      <div class="nk-content-body">
        <div class="nk-block-head nk-block-head-sm">
          <div class="nk-block-between">
            <div class="nk-block-head-content">
              <h3 class="nk-block-title page-title">Deployment File Generator</h3>
              <div class="nk-block-des text-soft">Create a PHP deployment file on a remote Windows server (UNC path).</div>
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

        <div class="card card-bordered">
          <div class="card-inner">
            <form method="POST" action="{{ route('admin.deployment-files.store') }}">
              @csrf
              <div class="row g-3">
                <div class="col-md-6">
                  <div class="form-group">
                    <label class="form-label">Server UNC Path</label>
                    <div class="form-control-wrap">
                      <input type="text" name="server_path" class="form-control" value="{{ old('server_path', $defaults['server_path'] ?? '') }}" placeholder="\\\\10.10.16.47\\c$\\wamp64\\www\\dep_env">
                    </div>
                    <small class="text-soft">Example: \\10.10.16.47\c$\wamp64\www\dep_env</small>
                  </div>
                </div>

                <div class="col-md-6">
                  <div class="form-group">
                    <label class="form-label">Filename</label>
                    <div class="form-control-wrap">
                      <input type="text" name="filename" class="form-control" value="{{ old('filename', $defaults['filename'] ?? 'example_deploy.php') }}" placeholder="example_deploy.php">
                    </div>
                  </div>
                </div>

                <div class="col-md-12">
                  <div class="form-group">
                    <label class="form-label">Project Path on Target Server</label>
                    <div class="form-control-wrap">
                      <input type="text" name="project_path" class="form-control" value="{{ old('project_path', $defaults['project_path'] ?? 'C:\\wamp64\\www\\com_cal_deploy') }}" placeholder="C:\\wamp64\\www\\com_cal_deploy">
                    </div>
                    <small class="text-soft">This will be used in the generated script's commands.</small>
                  </div>
                </div>

                <div class="col-12">
                  <button type="submit" class="btn btn-primary">Generate File</button>
                </div>
              </div>
            </form>
          </div>
        </div>

        <div class="alert alert-info mt-3">
          <div class="d-flex align-items-start">
            <em class="icon ni ni-info text-primary me-2"></em>
            <div>
              If the app cannot write directly to the UNC path, the file will download to your browser for manual placement. Ensure the web server service account has permission to write to the C$ admin share on the target server.
            </div>
          </div>
        </div>

      </div>
    </div>
  </div>
</div>
@endsection

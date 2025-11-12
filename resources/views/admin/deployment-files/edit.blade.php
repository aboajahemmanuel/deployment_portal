@extends('layouts.deployment')

@section('content')
<div class="nk-content">
  <div class="container-fluid">
    <div class="nk-content-inner">
      <div class="nk-content-body">
        <div class="nk-block-head nk-block-head-sm">
          <div class="nk-block-between">
            <div class="nk-block-head-content">
              <h3 class="nk-block-title page-title">Edit Deployment File</h3>
              <div class="nk-block-des text-soft">{{ $filePath }}</div>
            </div>
            <div class="nk-block-head-content">
              <a href="{{ route('admin.deployment-files.index', ['server_path' => dirname($filePath)]) }}" class="btn btn-light"><em class="icon ni ni-arrow-left"></em><span>Back to List</span></a>
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
            <form method="POST" action="{{ route('admin.deployment-files.update') }}">
              @csrf
              <input type="hidden" name="file" value="{{ $fileToken }}">
              <div class="form-group">
                <label class="form-label">File Content</label>
                <div class="form-control-wrap">
                  <textarea name="content" class="form-control" rows="24" spellcheck="false">{{ $content }}</textarea>
                </div>
              </div>
              <div class="mt-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary"><em class="icon ni ni-save"></em><span>Save Changes</span></button>
                <a href="{{ route('admin.deployment-files.index', ['server_path' => dirname($filePath)]) }}" class="btn btn-secondary">Cancel</a>
              </div>
            </form>
          </div>
        </div>

      </div>
    </div>
  </div>
</div>
@endsection

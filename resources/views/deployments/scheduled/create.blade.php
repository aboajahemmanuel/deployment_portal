@extends('layouts.deployment')

@section('title', 'Schedule Deployment')

@section('content')
<!-- content @s -->
<div class="nk-block">
    <div class="nk-block-head nk-block-head-sm">
        <div class="nk-block-between">
            <div class="nk-block-head-content">
                <h3 class="nk-block-title page-title">Schedule Deployment</h3>
            </div>
        </div>
    </div>

    <div class="card card-bordered">
        <div class="card-inner">
            <form action="{{ route('scheduled-deployments.store') }}" method="POST">
                @csrf
                
                @if($errors->any())
                    <div class="alert alert-danger">
                        <strong>Whoops!</strong> There were some problems with your input.
                        <ul class="mt-2 mb-0">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                
                <div class="row g-gs">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label" for="project_id">Project</label>
                            <div class="form-control-wrap">
                                <select class="form-control" id="project_id" name="project_id" required>
                                    <option value="">Select a project</option>
                                    @foreach($projects as $project)
                                        <option value="{{ $project->id }}" {{ old('project_id') == $project->id ? 'selected' : '' }}>
                                            {{ $project->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label" for="environment_id">Environment</label>
                            <div class="form-control-wrap">
                                <select class="form-control" id="environment_id" name="environment_id" required>
                                    <option value="">Select an environment</option>
                                    @foreach(\App\Models\Environment::active()->ordered()->get() as $environment)
                                        <option value="{{ $environment->id }}" {{ old('environment_id') == $environment->id ? 'selected' : '' }}>
                                            {{ $environment->name }} ({{ $environment->description }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label" for="scheduled_at">Scheduled Time</label>
                            <div class="form-control-wrap">
                                <input type="datetime-local" class="form-control" id="scheduled_at" name="scheduled_at" value="{{ old('scheduled_at') }}" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-12">
                        <div class="form-group">
                            <label class="form-label" for="description">Description</label>
                            <div class="form-control-wrap">
                                <textarea class="form-control" id="description" name="description" rows="3" placeholder="Enter a description for this scheduled deployment (optional)">{{ old('description') }}</textarea>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-12">
                        <div class="form-group">
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" name="is_recurring" id="is_recurring" value="1" {{ old('is_recurring') ? 'checked' : '' }}>
                                <label class="custom-control-label" for="is_recurring">Recurring Deployment</label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6" id="recurrence_pattern_field" style="display: none;">
                        <div class="form-group">
                            <label class="form-label" for="recurrence_pattern">Recurrence Pattern</label>
                            <div class="form-control-wrap">
                                <select class="form-control" id="recurrence_pattern" name="recurrence_pattern">
                                    <option value="">Select pattern</option>
                                    <option value="daily" {{ old('recurrence_pattern') == 'daily' ? 'selected' : '' }}>Daily</option>
                                    <option value="weekly" {{ old('recurrence_pattern') == 'weekly' ? 'selected' : '' }}>Weekly</option>
                                    <option value="monthly" {{ old('recurrence_pattern') == 'monthly' ? 'selected' : '' }}>Monthly</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-12 mt-4">
                        <div class="d-flex justify-content-between">
                            <a href="{{ route('scheduled-deployments.index') }}" class="btn btn-light">
                                <em class="icon ni ni-arrow-left"></em>
                                <span>Back</span>
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <em class="icon ni ni-calendar"></em>
                                <span>Schedule Deployment</span>
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('is_recurring').addEventListener('change', function() {
    const recurrenceField = document.getElementById('recurrence_pattern_field');
    if (this.checked) {
        recurrenceField.style.display = 'block';
    } else {
        recurrenceField.style.display = 'none';
    }
});
</script>
@endsection
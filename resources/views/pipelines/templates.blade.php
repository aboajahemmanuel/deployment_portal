@extends('layouts.app')

@section('content')
<div class="nk-content ">
    <div class="container-fluid">
        <div class="nk-content-inner">
            <div class="nk-content-body">
                <div class="nk-block-head nk-block-head-sm">
                    <div class="nk-block-between">
                        <div class="nk-block-head-content">
                            <h3 class="nk-block-title page-title">Pipeline Templates</h3>
                            <div class="nk-block-des text-soft">
                                <p>Choose from pre-configured pipeline templates for different project types</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pipeline Templates -->
                <div class="row g-gs">
                    @foreach($templates as $key => $template)
                    <div class="col-lg-6 col-xl-4">
                        <div class="card card-bordered h-100">
                            <div class="card-inner">
                                <div class="project-head">
                                    <div class="project-title">
                                        <h5 class="title">{{ $template['name'] }}</h5>
                                        <p class="text-soft">{{ $template['description'] }}</p>
                                    </div>
                                </div>
                                
                                <!-- Pipeline Preview -->
                                <div class="project-details mt-4">
                                    <div class="pipeline-preview">
                                        @foreach($template['stages'] as $index => $stage)
                                        <div class="stage-preview {{ $index === 0 ? 'first' : '' }} {{ $index === count($template['stages']) - 1 ? 'last' : '' }}">
                                            <div class="stage-dot"></div>
                                            <div class="stage-info">
                                                <h6 class="stage-name">{{ $stage['display_name'] }}</h6>
                                                <p class="stage-desc">{{ Str::limit($stage['description'], 50) }}</p>
                                            </div>
                                            @if($index < count($template['stages']) - 1)
                                            <div class="stage-connector"></div>
                                            @endif
                                        </div>
                                        @endforeach
                                    </div>
                                </div>
                                
                                <!-- Template Stats -->
                                <div class="project-meta mt-4">
                                    <ul class="project-meta-list">
                                        <li>
                                            <span class="meta-label">Stages</span>
                                            <span class="meta-value">{{ count($template['stages']) }}</span>
                                        </li>
                                        <li>
                                            <span class="meta-label">Type</span>
                                            <span class="meta-value">{{ ucfirst(str_replace('_', ' ', $key)) }}</span>
                                        </li>
                                    </ul>
                                </div>
                                
                                <!-- Actions -->
                                <div class="project-action mt-4">
                                    <button class="btn btn-primary btn-block" onclick="selectTemplate('{{ $key }}')">
                                        <em class="icon ni ni-check"></em>
                                        <span>Use This Template</span>
                                    </button>
                                    <button class="btn btn-outline-light btn-block mt-2" onclick="previewTemplate('{{ $key }}')">
                                        <em class="icon ni ni-eye"></em>
                                        <span>Preview Details</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>

                <!-- Template Preview Modal -->
                <div class="modal fade" id="templatePreviewModal" tabindex="-1">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Pipeline Template Preview</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <div id="templatePreviewContent">
                                    <!-- Content will be loaded here -->
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                <button type="button" class="btn btn-primary" id="useTemplateBtn">Use Template</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.pipeline-preview {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 20px;
    position: relative;
}

.stage-preview {
    display: flex;
    align-items: flex-start;
    margin-bottom: 15px;
    position: relative;
}

.stage-preview.last {
    margin-bottom: 0;
}

.stage-dot {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    background: #526484;
    margin-top: 4px;
    margin-right: 12px;
    flex-shrink: 0;
    position: relative;
    z-index: 2;
}

.stage-info {
    flex: 1;
}

.stage-name {
    font-size: 13px;
    font-weight: 600;
    margin-bottom: 2px;
    color: #364a63;
}

.stage-desc {
    font-size: 11px;
    color: #8094ae;
    margin: 0;
    line-height: 1.3;
}

.stage-connector {
    position: absolute;
    left: 5px;
    top: 16px;
    bottom: -15px;
    width: 2px;
    background: #e5e9f2;
    z-index: 1;
}

.stage-preview.last .stage-connector {
    display: none;
}

.project-meta-list {
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
    list-style: none;
    padding: 0;
    margin: 0;
}

.project-meta-list li {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
}

.meta-label {
    font-size: 11px;
    color: #8094ae;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 2px;
}

.meta-value {
    font-size: 14px;
    font-weight: 600;
    color: #364a63;
}

.project-action .btn {
    font-size: 13px;
    padding: 8px 16px;
}

.template-detail-stage {
    border: 1px solid #e5e9f2;
    border-radius: 6px;
    padding: 15px;
    margin-bottom: 10px;
    background: #fff;
}

.template-detail-stage:last-child {
    margin-bottom: 0;
}

.stage-detail-header {
    display: flex;
    align-items: center;
    margin-bottom: 8px;
}

.stage-detail-number {
    width: 24px;
    height: 24px;
    border-radius: 50%;
    background: #526484;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    font-weight: 600;
    margin-right: 10px;
}

.stage-detail-name {
    font-size: 14px;
    font-weight: 600;
    color: #364a63;
    margin: 0;
}

.stage-detail-description {
    font-size: 13px;
    color: #8094ae;
    margin: 0;
    line-height: 1.4;
}
</style>

<script>
const templates = @json($templates);
let selectedTemplateKey = null;

function selectTemplate(templateKey) {
    selectedTemplateKey = templateKey;
    
    // Here you would typically redirect to project creation with the selected template
    // or store the selection for use in project creation
    
    toastr.success(`Selected template: ${templates[templateKey].name}`);
    
    // Example: redirect to project creation with template parameter
    // window.location.href = `/deployments/create?template=${templateKey}`;
}

function previewTemplate(templateKey) {
    const template = templates[templateKey];
    selectedTemplateKey = templateKey;
    
    const content = document.getElementById('templatePreviewContent');
    
    let stagesHtml = '';
    template.stages.forEach((stage, index) => {
        stagesHtml += `
            <div class="template-detail-stage">
                <div class="stage-detail-header">
                    <div class="stage-detail-number">${index + 1}</div>
                    <h6 class="stage-detail-name">${stage.display_name}</h6>
                </div>
                <p class="stage-detail-description">${stage.description}</p>
            </div>
        `;
    });
    
    content.innerHTML = `
        <div class="row g-4">
            <div class="col-12">
                <div class="media-group">
                    <div class="media media-lg media-middle media-circle text-bg-primary">
                        <em class="icon ni ni-template-fill"></em>
                    </div>
                    <div class="media-text">
                        <h5>${template.name}</h5>
                        <p class="text-soft">${template.description}</p>
                    </div>
                </div>
            </div>
            
            <div class="col-12">
                <h6 class="title">Pipeline Stages (${template.stages.length})</h6>
                <div class="template-stages">
                    ${stagesHtml}
                </div>
            </div>
        </div>
    `;
    
    const modal = new bootstrap.Modal(document.getElementById('templatePreviewModal'));
    modal.show();
}

document.getElementById('useTemplateBtn').addEventListener('click', function() {
    if (selectedTemplateKey) {
        selectTemplate(selectedTemplateKey);
        bootstrap.Modal.getInstance(document.getElementById('templatePreviewModal')).hide();
    }
});
</script>
@endsection

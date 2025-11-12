@extends('layouts.deployment')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">Horizontal Deployment Steps Test</h5>
                </div>
                <div class="card-body">
                    <p>This page demonstrates the horizontal deployment steps layout.</p>
                    
                    <!-- Progress Steps -->
                    <div id="deploymentSteps" class="deployment-steps horizontal mt-4 w-100 mx-auto text-start">
                        <div class="step" id="step-initializing">
                            <div class="step-icon">🕒</div>
                            <div class="step-content">
                                <div class="step-title">Initializing</div>
                                <div class="step-description">Setting up environment</div>
                            </div>
                        </div>
                        <div class="step" id="step-pulling">
                            <div class="step-icon">📥</div>
                            <div class="step-content">
                                <div class="step-title">Pulling Code</div>
                                <div class="step-description">Fetching updates</div>
                            </div>
                        </div>
                        <div class="step" id="step-dependencies">
                            <div class="step-icon">📦</div>
                            <div class="step-content">
                                <div class="step-title">Dependencies</div>
                                <div class="step-description">Installing packages</div>
                            </div>
                        </div>
                        <div class="step" id="step-caching">
                            <div class="step-icon">⚡</div>
                            <div class="step-content">
                                <div class="step-title">Optimizing</div>
                                <div class="step-description">Clearing caches</div>
                            </div>
                        </div>
                        <div class="step" id="step-migrations">
                            <div class="step-icon">📊</div>
                            <div class="step-content">
                                <div class="step-title">Migrations</div>
                                <div class="step-description">Updating database</div>
                            </div>
                        </div>
                        <div class="step" id="step-completing">
                            <div class="step-icon">✅</div>
                            <div class="step-content">
                                <div class="step-title">Finalizing</div>
                                <div class="step-description">Completing process</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-4">
                        <button id="testProgress" class="btn btn-primary">Test Progress</button>
                        <button id="resetSteps" class="btn btn-secondary">Reset</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const testButton = document.getElementById('testProgress');
    const resetButton = document.getElementById('resetSteps');
    const steps = [
        'step-initializing',
        'step-pulling',
        'step-dependencies',
        'step-caching',
        'step-migrations',
        'step-completing'
    ];
    
    let currentStep = 0;
    let interval;
    
    testButton.addEventListener('click', function() {
        if (interval) {
            clearInterval(interval);
        }
        
        // Reset all steps
        resetProgressSteps();
        currentStep = 0;
        
        interval = setInterval(function() {
            if (currentStep < steps.length) {
                updateDeploymentStep(steps[currentStep]);
                currentStep++;
            } else {
                clearInterval(interval);
            }
        }, 1000);
    });
    
    resetButton.addEventListener('click', function() {
        if (interval) {
            clearInterval(interval);
        }
        resetProgressSteps();
        currentStep = 0;
    });
    
    // Reset all progress steps to initial state
    function resetProgressSteps() {
        const stepElements = document.querySelectorAll('.deployment-steps .step');
        stepElements.forEach(step => {
            step.classList.remove('active', 'completed');
        });
    }
    
    // Update progress steps display
    function updateDeploymentStep(stepId) {
        // Reset all steps
        resetProgressSteps();
        
        // Mark current and previous steps as completed
        const stepElements = document.querySelectorAll('.deployment-steps .step');
        let foundCurrent = false;
        
        stepElements.forEach(step => {
            if (step.id === stepId) {
                step.classList.add('active');
                foundCurrent = true;
            } else if (!foundCurrent) {
                step.classList.add('completed');
            }
        });
    }
});
</script>
@endsection
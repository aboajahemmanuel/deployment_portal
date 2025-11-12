<!DOCTYPE html>
<html>
<head>
    <title>Deployment Notification</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 20px auto;
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        .header {
            background-color: #4e73df;
            color: white;
            padding: 20px;
            text-align: center;
        }
        .content {
            padding: 30px;
        }
        .success {
            background-color: #28a745;
        }
        .failure {
            background-color: #dc3545;
        }
        .button {
            display: inline-block;
            padding: 12px 24px;
            background-color: #4e73df;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            margin: 20px 0;
            font-weight: bold;
        }
        .button:hover {
            background-color: #2e59d9;
        }
        .details {
            background-color: #f8f9fc;
            border-left: 4px solid #4e73df;
            padding: 15px;
            margin: 20px 0;
        }
        .footer {
            text-align: center;
            padding: 20px;
            font-size: 12px;
            color: #666;
            border-top: 1px solid #eee;
        }
        .status-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-weight: bold;
            color: white;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header {{ $type === 'success' ? 'success' : 'failure' }}">
            <h1>Deployment {{ $type === 'success' ? 'Successful' : 'Failed' }}</h1>
        </div>
        
        <div class="content">
            <h2>Hello {{ $notifiable->name }}!</h2>
            
            @if($type === 'success')
                <p>A deployment for the project <strong>{{ $project->name }}</strong> has completed successfully.</p>
            @else
                <p>A deployment for the project <strong>{{ $project->name }}</strong> has failed.</p>
            @endif
            
            <div class="details">
                <h3>Deployment Details:</h3>
                <ul>
                    <li><strong>Project:</strong> {{ $project->name }}</li>
                    <li><strong>Started by:</strong> {{ $user->name }}</li>
                    <li><strong>Branch:</strong> {{ $project->current_branch }}</li>
                    <li><strong>Status:</strong> 
                        <span class="status-badge {{ $type === 'success' ? 'success' : 'failure' }}">
                            {{ ucfirst($type) }}
                        </span>
                    </li>
                    <li><strong>Completed at:</strong> {{ $deployment->completed_at->format('M d, Y H:i:s') }}</li>
                </ul>
            </div>
            
            <div style="text-align: center;">
                <a href="{{ url('/deployments/' . $project->id) }}" class="button">View Deployment Details</a>
            </div>
            
            @if($type !== 'success')
                <p><strong>Please check the deployment logs for more information.</strong></p>
            @endif
            
            <p>Thank you for using our deployment management system!</p>
        </div>
        
        <div class="footer">
            <p>© {{ date('Y') }} Deployment Management System. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
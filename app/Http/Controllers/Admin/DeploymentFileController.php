<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DeploymentFileController extends Controller
{
    /**
     * List deployment files in a target UNC directory.
     */
    public function index(Request $request)
    {
        $base = $request->get('server_path', '\\\\10.10.16.47\\c$\\wamp64\\www\\dep_env');
        $base = $this->normalizeUnc($base);

        $files = [];
        try {
            if (@is_dir($base)) {
                foreach (@scandir($base) ?: [] as $f) {
                    if ($f === '.' || $f === '..') continue;
                    $full = $base . (str_ends_with($base, '\\') ? '' : '\\') . $f;
                    if (@is_file($full) && str_ends_with(strtolower($f), '.php')) {
                        $files[] = [
                            'name' => $f,
                            'path' => $full,
                            'size' => @filesize($full) ?: 0,
                            'modified' => @filemtime($full) ?: null,
                            'token' => base64_encode($full),
                        ];
                    }
                }
            }
        } catch (\Throwable $e) {
            // swallow, view will show message
        }

        return view('admin.deployment-files.index', [
            'serverPath' => $base,
            'files' => $files,
        ]);
    }

    /**
     * Show the form to generate a deployment file.
     */
    public function create()
    {
        $defaults = [
            'server_path' => '\\\\10.10.16.47\\c$\\wamp64\\www\\dep_env',
            'filename'    => 'example_deploy.php',
            'project_path'=> 'C:\\wamp64\\www\\com_cal_deploy',
        ];
        return view('admin.deployment-files.create', compact('defaults'));
    }

    /**
     * Generate the deployment file and attempt to write to UNC. If writing fails, download the file.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'server_path'  => ['required','string'], // e.g. \\10.10.16.47\c$\wamp64\www\dep_env
            'filename'     => ['required','string'], // e.g. example_deploy.php
            'project_path' => ['required','string'], // e.g. C:\\wamp64\\www\\com_cal_deploy
        ]);

        $serverPath = rtrim($data['server_path'], "\\/ ");
        // Normalize to UNC with backslashes for Windows shares
        $serverPath = str_replace('/', '\\', $serverPath);
        if (!str_starts_with($serverPath, '\\\\')) {
            $serverPath = '\\\\' . ltrim($serverPath, '\\\\');
        }

        $filename = $data['filename'];
        $projectPath = $data['project_path'];

        $content = $this->renderDeploymentPhp($projectPath);
        $target = $serverPath . (str_ends_with($serverPath, '\\') ? '' : '\\') . $filename;

        try {
            // Attempt to write directly to UNC path
            $bytes = @file_put_contents($target, $content);
            if ($bytes === false) {
                throw new \RuntimeException("Failed to write to UNC path: {$target}");
            }

            Log::info('Deployment file generated', [
                'target' => $target,
                'user_id' => $request->user()?->id,
            ]);

            return redirect()
                ->route('admin.deployment-files.index', ['server_path' => $serverPath])
                ->with('status', "Deployment file created at {$target}");
        } catch (\Throwable $e) {
            Log::warning('UNC write failed, offering download', [
                'error' => $e->getMessage(),
                'target' => $target,
            ]);

            $downloadName = $filename;
            return new StreamedResponse(function () use ($content) {
                echo $content;
            }, 200, [
                'Content-Type' => 'application/x-php',
                'Content-Disposition' => 'attachment; filename="' . $downloadName . '"',
            ]);
        }
    }

    /**
     * Show editor for a file.
     */
    public function edit(Request $request)
    {
        $token = $request->query('file');
        abort_unless($token, 404);
        $full = base64_decode($token);
        abort_unless($full && str_ends_with(strtolower($full), '.php'), 404);

        $dir = dirname($full);
        // Basic guard: must be under dep_env
        abort_unless(str_contains(strtolower($dir), strtolower('\\\\10.10.16.47\\c$\\wamp64\\www\\dep_env')), 403);

        $content = @file_get_contents($full);
        abort_unless($content !== false, 404);

        return view('admin.deployment-files.edit', [
            'filePath' => $full,
            'fileToken' => $token,
            'content' => $content,
        ]);
    }

    /**
     * Save edits to a file.
     */
    public function update(Request $request)
    {
        $data = $request->validate([
            'file' => ['required','string'],
            'content' => ['required','string'],
        ]);
        $full = base64_decode($data['file']);
        abort_unless($full && str_ends_with(strtolower($full), '.php'), 404);
        $dir = dirname($full);
        abort_unless(str_contains(strtolower($dir), strtolower('\\\\10.10.16.47\\c$\\wamp64\\www\\dep_env')), 403);

        $ok = @file_put_contents($full, $data['content']);
        if ($ok === false) {
            return back()->withErrors(['content' => 'Failed to save file. Check permissions.']);
        }
        return redirect()->route('admin.deployment-files.index', ['server_path' => $dir])
            ->with('status', 'File saved successfully');
    }

    /**
     * Delete a file.
     */
    public function destroy(Request $request)
    {
        $data = $request->validate([
            'file' => ['required','string'],
        ]);
        $full = base64_decode($data['file']);
        abort_unless($full && str_ends_with(strtolower($full), '.php'), 404);
        $dir = dirname($full);
        abort_unless(str_contains(strtolower($dir), strtolower('\\\\10.10.16.47\\c$\\wamp64\\www\\dep_env')), 403);

        $ok = @unlink($full);
        if (!$ok) {
            return back()->withErrors(['file' => 'Failed to delete file.']);
        }
        return redirect()->route('admin.deployment-files.index', ['server_path' => $dir])
            ->with('status', 'File deleted successfully');
    }

    private function normalizeUnc(string $path): string
    {
        $p = rtrim(str_replace('/', '\\', $path), "\\/ ");
        if (!str_starts_with($p, '\\\\')) {
            $p = '\\\\' . ltrim($p, '\\\\');
        }
        return $p;
    }

    private function renderDeploymentPhp(string $projectPath): string
    {
        $escapedPath = addslashes($projectPath);
        // Build the PHP script content exactly like requested
        $php = <<<PHP
<?php

\$projectPath = '{$escapedPath}';
\$logFile = __DIR__ . '/deploy-log.txt';

\$safeDir = str_replace('\\\\', '/', \$projectPath);

\$commands = [
    "git config --global --add safe.directory {\$safeDir}",
    "cd /d {\$projectPath} && git pull origin main",
    "cd /d {\$projectPath} && php artisan cache:clear",
    "cd /d {\$projectPath} && php artisan config:cache",
    "cd /d {\$projectPath} && php artisan route:cache",
    "cd /d {\$projectPath} && php artisan optimize:clear",
];

\$output = "\xF0\x9F\x9A\x80 Deployment started in {\$projectPath}\n\n";

foreach (\$commands as \$cmd) {
    \$output .= "> Running: {\$cmd}\n";
    \$result = shell_exec(\$cmd . ' 2>&1');
    \$output .= \$result . "\n";
}

file_put_contents(\$logFile, \$output, FILE_APPEND);
echo nl2br(\$output);
PHP;
        return $php;
    }
}

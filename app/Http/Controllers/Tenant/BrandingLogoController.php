<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Services\CentreSettingsService;
use Illuminate\Http\Request;

class BrandingLogoController extends Controller
{
    public function __construct(private readonly CentreSettingsService $settings) {}

    public function __invoke(Request $request, string $type)
    {
        $key = match($type) {
            'login'   => 'branding_login_logo_path',
            'sidebar' => 'branding_sidebar_logo_path',
            default   => null,
        };

        if (!$key) {
            abort(404);
        }

        $path = $this->settings->get($key);
        if (!$path) {
            abort(404);
        }

        $realPath    = storage_path('app/' . $path);
        $storageBase = realpath(storage_path('app'));
        $realResolved = realpath($realPath);

        if ($realResolved === false || !str_starts_with($realResolved, $storageBase . DIRECTORY_SEPARATOR)) {
            abort(404);
        }

        if (!file_exists($realResolved)) {
            abort(404);
        }

        $ext      = strtolower(pathinfo($realResolved, PATHINFO_EXTENSION));
        $mimeMap  = ['png' => 'image/png', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg',
                      'gif' => 'image/gif', 'svg' => 'image/svg+xml', 'webp' => 'image/webp'];
        $mimeType = $mimeMap[$ext] ?? 'image/png';

        return response()->file($realResolved, [
            'Content-Type'  => $mimeType,
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }
}

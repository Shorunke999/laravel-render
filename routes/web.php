<?php

use App\Http\Controllers\Auth\AuthController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

Route::get('/', [AuthController::class, 'Unauthorized']);

Route::get('/test-b2', function() {
    try {
        // Get the S3 client instance
        $client = Storage::disk('b2')->getClient();

        // Test connection by listing buckets
        $buckets = $client->listBuckets();

        dd([
            'success' => true,
            'buckets' => $buckets,
            'config' => config('filesystems.disks.b2')
        ]);

    } catch (\Exception $e) {
        dd([
            'error' => $e->getMessage(),
            'aws_error' => method_exists($e, 'getAwsErrorCode') ? $e->getAwsErrorCode() : null,
            'config' => config('filesystems.disks.b2')
        ]);
    }
});
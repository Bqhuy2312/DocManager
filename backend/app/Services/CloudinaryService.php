<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class CloudinaryService
{
    public function uploadDocument(UploadedFile $file): array
    {
        return $this->upload($file, 'raw', config('services.cloudinary.documents_folder'));
    }

    public function uploadAvatar(UploadedFile $file): array
    {
        return $this->upload($file, 'image', config('services.cloudinary.avatars_folder'));
    }

    public function destroyDocument(?string $publicId): void
    {
        $this->destroy($publicId, 'raw');
    }

    public function destroyAvatar(?string $publicId): void
    {
        $this->destroy($publicId, 'image');
    }

    private function upload(UploadedFile $file, string $resourceType, string $folder): array
    {
        $response = $this->request()
            ->attach('file', fopen($file->getRealPath(), 'r'), $file->getClientOriginalName())
            ->post($this->endpoint($resourceType, 'upload'), $this->signedParameters([
                'folder' => $folder,
                'timestamp' => time(),
                'use_filename' => 'true',
                'unique_filename' => 'true',
            ]));

        if ($response->failed()) {
            throw new RuntimeException($response->json('error.message', 'Cloudinary upload failed.'));
        }

        return [
            'url' => $response->json('secure_url'),
            'public_id' => $response->json('public_id'),
        ];
    }

    private function destroy(?string $publicId, string $resourceType): void
    {
        if (!$publicId) {
            return;
        }

        $response = $this->request()->post($this->endpoint($resourceType, 'destroy'), $this->signedParameters([
            'public_id' => $publicId,
            'timestamp' => time(),
        ]));

        if ($response->failed()) {
            throw new RuntimeException($response->json('error.message', 'Cloudinary delete failed.'));
        }
    }

    private function request(): PendingRequest
    {
        if (!config('services.cloudinary.cloud_name')
            || !config('services.cloudinary.api_key')
            || !config('services.cloudinary.api_secret')) {
            throw new RuntimeException('Cloudinary credentials are not configured.');
        }

        return Http::asMultipart()->timeout(120);
    }

    private function endpoint(string $resourceType, string $action): string
    {
        return sprintf(
            'https://api.cloudinary.com/v1_1/%s/%s/%s',
            config('services.cloudinary.cloud_name'),
            $resourceType,
            $action
        );
    }

    private function signedParameters(array $parameters): array
    {
        $parameters = array_filter($parameters, fn ($value) => $value !== null && $value !== '');
        ksort($parameters);

        $signature = sha1(
            urldecode(http_build_query($parameters))
            . config('services.cloudinary.api_secret')
        );

        return [
            ...$parameters,
            'api_key' => config('services.cloudinary.api_key'),
            'signature' => $signature,
        ];
    }
}

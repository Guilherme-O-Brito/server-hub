<?php

namespace App\Services\Kubernetes;

use Illuminate\Support\Facades\Http;
use Log;

class KubernetesClient
{
    protected string $baseUrl;
    protected string $token;

    public function __construct()
    {
        $this->baseUrl = 'https://kubernetes.default.svc';
        $this->token = trim(file_get_contents('/var/run/secrets/kubernetes.io/serviceaccount/token'));
    }

    protected function handleResponse($response, array $manifest): array
    {
        if ($response->failed()) {
            Log::error('Kubernetes API Error', [
                'status' => $response->status(),
                'body' => $response->body(),
                'manifest' => $manifest,
            ]);

            throw new \RuntimeException($response->body());
        }

        return $response->json();
    }

    protected function client()
    {
        return Http::withToken($this->token)->withoutVerifying();
    }

    public function createDeployment(array $manifest): array
    {
        $response =  $this->client()->post("{$this->baseUrl}/apis/apps/v1/namespaces/games/deployments", $manifest);

        return $this->handleResponse($response, $manifest);
    }

    public function deleteDeployment(string $name): void
    {
        $response = $this->client()->delete("{$this->baseUrl}/apis/apps/v1/namespaces/games/deployments/{$name}");
        
        if ($response->status() === 404) {
            return;
        }

        $this->handleResponse($response, []);
    }

    public function createPvc(array $manifest): array
    {
        $response = $this->client()->post("{$this->baseUrl}/api/v1/namespaces/games/persistentvolumeclaims", $manifest);

        return $this->handleResponse($response, $manifest);
    }

    public function deletePvc(string $name): void
    {
        $response = $this->client()->delete("{$this->baseUrl}/api/v1/namespaces/games/persistentvolumeclaims/{$name}");
        
        if ($response->status() === 404) {
            return;
        }

        $this->handleResponse($response, []);
    }

    public function createConfigMap(array $manifest): array
    {
        $response = $this->client()->post("{$this->baseUrl}/api/v1/namespaces/games/configmaps", $manifest);

        return $this->handleResponse($response, $manifest);
    }

    public function updateConfigMap(string $name, array $manifest): array
    {
        $response = $this->client()->put("{$this->baseUrl}/api/v1/namespaces/games/configmaps/{$name}", $manifest);

        return $this->handleResponse($response, $manifest);
    }

    public function deleteConfigMap(string $name): void
    {
        $response = $this->client()->delete("{$this->baseUrl}/api/v1/namespaces/games/configmaps/{$name}");
        
        if ($response->status() === 404) {
            return;
        }

        $this->handleResponse($response, []);
    }

    public function getPod(string $name): array
    {
        $response = $this->client()->get("{$this->baseUrl}/api/v1/namespaces/games/pods/{$name}");

        return $this->handleResponse($response, []);
    }

    public function createService(array $manifest): array
    {
        $response = $this->client()->post("{$this->baseUrl}/api/v1/namespaces/games/services", $manifest);

        return $this->handleResponse($response, $manifest);
    }

    public function updateService(string $name, array $manifest): array
    {
        $response = $this->client()->put("{$this->baseUrl}/api/v1/namespaces/games/services/{$name}", $manifest);
    
        return $this->handleResponse($response, $manifest);    
    }

    public function deleteService(string $name): void
    {
        $response = $this->client()->delete("{$this->baseUrl}/api/v1/namespaces/games/services/{$name}");

        if ($response->status() === 404) {
            return;
        }

        $this->handleResponse($response, []);
    }
    
}
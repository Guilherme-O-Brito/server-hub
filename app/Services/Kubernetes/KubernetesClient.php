<?php

namespace App\Services\Kubernetes;

use Illuminate\Support\Facades\Http;

class KubernetesClient
{
    protected string $baseUrl;
    protected string $token;

    public function __construct()
    {
        $this->baseUrl = 'https://kubernetes.default.svc';
        $this->token = trim(file_get_contents('/var/run/secrets/kubernetes.io/serviceaccount/token'));
    }

    protected function client()
    {
        return Http::withToken($this->token)->withoutVerifying();
    }

    public function createDeployment(array $manifest): array
    {
        return $this->client()->post("{$this->baseUrl}/apis/apps/v1/namespaces/games/deployments", $manifest)->throw()->json();
    }

    public function createPvc(array $manifest): array
    {
        return $this->client()->post("{$this->baseUrl}/api/v1/namespaces/games/persistentvolumeclaims", $manifest)->throw()->json();
    }

    public function createConfigMap(array $manifest): array
    {
        return $this->client()->post("{$this->baseUrl}/api/v1/namespaces/games/configmaps", $manifest)->throw()->json();
    }

    public function getPod(string $name): array
    {
        return $this->client()->get("{$this->baseUrl}/api/v1/namespaces/games/pods/{$name}")->throw()->json();
    }
    
}
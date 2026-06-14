<?php

namespace Tests\Unit\Services\Kubernetes;

use App\Services\Kubernetes\KubernetesClient;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class KubernetesClientTest extends TestCase
{
    public function test_create_config_map_sends_expected_post_request(): void
    {
        Http::fake([
            '*' => Http::response(['ok' => true], 201),
        ]);

        $client = $this->newClient();
        $manifest = ['kind' => 'ConfigMap'];

        $client->createConfigMap($manifest);

        Http::assertSent(function (HttpRequest $request) use ($manifest) {
            return $request->method() === 'POST'
                && $request->url() === 'https://kubernetes.default.svc/api/v1/namespaces/games/configmaps'
                && $request->data() === $manifest;
        });
    }

    public function test_create_pvc_sends_expected_post_request(): void
    {
        Http::fake([
            '*' => Http::response(['ok' => true], 201),
        ]);

        $client = $this->newClient();
        $manifest = ['kind' => 'PersistentVolumeClaim'];

        $client->createPvc($manifest);

        Http::assertSent(function (HttpRequest $request) use ($manifest) {
            return $request->method() === 'POST'
                && $request->url() === 'https://kubernetes.default.svc/api/v1/namespaces/games/persistentvolumeclaims'
                && $request->data() === $manifest;
        });
    }

    public function test_create_deployment_sends_expected_post_request(): void
    {
        Http::fake([
            '*' => Http::response(['ok' => true], 201),
        ]);

        $client = $this->newClient();
        $manifest = ['kind' => 'Deployment'];

        $client->createDeployment($manifest);

        Http::assertSent(function (HttpRequest $request) use ($manifest) {
            return $request->method() === 'POST'
                && $request->url() === 'https://kubernetes.default.svc/apis/apps/v1/namespaces/games/deployments'
                && $request->data() === $manifest;
        });
    }

    public function test_get_pod_sends_expected_get_request(): void
    {
        Http::fake([
            '*' => Http::response(['status' => ['phase' => 'Running']], 200),
        ]);

        $client = $this->newClient();

        $client->getPod('minecraft-1');

        Http::assertSent(function (HttpRequest $request) {
            return $request->method() === 'GET'
                && $request->url() === 'https://kubernetes.default.svc/api/v1/namespaces/games/pods/minecraft-1';
        });
    }

    private function newClient(): KubernetesClient
    {
        $client = new class extends KubernetesClient {
            public function __construct()
            {
            }

            public function setForTest(string $baseUrl, string $token): void
            {
                $this->baseUrl = $baseUrl;
                $this->token = $token;
            }
        };

        $client->setForTest('https://kubernetes.default.svc', 'test-token');

        return $client;
    }
}
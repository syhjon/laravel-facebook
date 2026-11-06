<?php

namespace Tests\Unit;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Tests\TestCase;

class LayerDependencyTest extends TestCase
{
    public function test_controlled_layers_do_not_skip_or_reverse_dependencies(): void
    {
        $rules = [
            'Http/Controllers' => [
                'App\\Checkers\\',
                'App\\CombinationManagers\\',
                'App\\Combinations\\',
                'App\\Repositories\\',
                'App\\Services\\',
                'App\\Models\\',
            ],
            'Http/Requests' => [
                'App\\CombinationManagers\\',
                'App\\Models\\',
                'App\\Repositories\\',
                'App\\ServiceManagers\\',
                'App\\Services\\',
            ],
            'CombinationManagers' => [
                'App\\Http\\',
                'App\\Models\\',
                'App\\Repositories\\',
                'App\\ServiceManagers\\',
                'App\\Checkers\\',
            ],
            'Services' => [
                'App\\Http\\',
                'App\\ServiceManagers\\',
                'App\\Providers\\',
            ],
            'Repositories' => [
                'App\\Http\\',
                'App\\Services\\',
                'App\\ServiceManagers\\',
                'App\\Combinations\\',
                'App\\CombinationManagers\\',
                'App\\Checkers\\',
            ],
            'Models' => [
                'App\\Http\\',
                'App\\Services\\',
                'App\\ServiceManagers\\',
                'App\\Repositories\\',
            ],
            'Checkers' => [
                'App\\Http\\',
                'App\\Services\\',
                'App\\Repositories\\',
                'App\\Models\\',
                'App\\ServiceManagers\\',
            ],
            'Validators' => [
                'App\\Http\\',
                'App\\Services\\',
                'App\\Repositories\\',
                'App\\Models\\',
                'App\\ServiceManagers\\',
            ],
            'ServiceManagers' => [
                'App\\Http\\',
                'App\\Repositories\\',
                'App\\Models\\',
            ],
            'Combinations' => [
                'App\\Http\\',
                'App\\Services\\',
                'App\\ServiceManagers\\',
                'App\\Repositories\\',
            ],
        ];

        foreach ($rules as $directory => $forbiddenNamespaces) {
            $layerFiles = $this->phpFiles(app_path($directory));
            $this->assertNotEmpty($layerFiles, "{$directory} 必須實際受分層檢查保護");

            foreach ($layerFiles as $file) {
                $contents = file_get_contents($file);

                foreach ($forbiddenNamespaces as $namespace) {
                    $pattern = '/^use\s+'.preg_quote($namespace, '/').'/m';

                    $this->assertDoesNotMatchRegularExpression(
                        $pattern,
                        $contents,
                        "{$file} 不得依賴 {$namespace}",
                    );
                }
            }
        }
    }

    public function test_controllers_delegate_response_creation_and_transactions(): void
    {
        foreach ($this->phpFiles(app_path('Http/Controllers')) as $file) {
            $contents = file_get_contents($file);

            $this->assertStringNotContainsString(
                'response()->json(',
                $contents,
                "{$file} 必須透過 ResponseMakerInterface 製造 JSON 回應",
            );
            $this->assertStringNotContainsString(
                'DB::transaction(',
                $contents,
                "{$file} 必須將 transaction 委派給 ServiceManage",
            );
            $this->assertDoesNotMatchRegularExpression(
                '/\$request->all\s*\(/',
                $contents,
                "{$file} 不得把未驗證的完整 Request 傳入應用層",
            );
            $this->assertDoesNotMatchRegularExpression(
                '/\$request\s*\[/',
                $contents,
                "{$file} 不得用陣列方式直接讀取 Request",
            );
        }
    }

    public function test_request_parameters_use_names_derived_from_their_concrete_types(): void
    {
        foreach ($this->phpFiles(app_path()) as $file) {
            $contents = file_get_contents($file);

            preg_match_all(
                '/\b([A-Z][A-Za-z0-9]*Request)\s+\$([A-Za-z][A-Za-z0-9]*)/',
                $contents,
                $requestParameters,
                PREG_SET_ORDER,
            );

            foreach ($requestParameters as $requestParameter) {
                $requestType = $requestParameter[1];
                $actualVariableName = $requestParameter[2];
                $expectedVariableName = $requestType === 'Request'
                    ? 'httpRequest'
                    : lcfirst($requestType);

                $this->assertSame(
                    $expectedVariableName,
                    $actualVariableName,
                    "{$file} 的 {$requestType} 參數必須命名為 \${$expectedVariableName}",
                );
            }
        }
    }

    public function test_service_managers_delegate_transactions_to_the_transaction_manager(): void
    {
        $layerFiles = $this->phpFiles(app_path('ServiceManagers'));
        $this->assertNotEmpty($layerFiles);

        foreach ($layerFiles as $file) {
            $contents = file_get_contents($file);

            $this->assertStringNotContainsString(
                'Illuminate\\Support\\Facades\\DB',
                $contents,
                "{$file} 必須透過 TransactionManagerInterface 控制交易",
            );
            $this->assertStringContainsString(
                'TransactionManagerInterface',
                $contents,
                "{$file} 必須保留寫入流程的交易邊界",
            );
        }
    }

    /**
     * @return array<int, string>
     */
    private function phpFiles(string $directory): array
    {
        if (! is_dir($directory)) {
            return [];
        }

        $files = [];
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory));

        /** @var SplFileInfo $file */
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $files[] = $file->getPathname();
            }
        }

        return $files;
    }
}

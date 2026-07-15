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
                'App\\Repositories\\',
                'App\\ServiceManagers\\',
                'App\\Services\\',
                'App\\Validators\\',
                'App\\Models\\',
            ],
            'Containers' => [
                'App\\Containers\\',
                'App\\Models\\',
                'App\\Repositories\\',
                'App\\Services\\',
                'App\\Validators\\',
            ],
            'Services' => [
                'App\\Http\\Controllers\\',
                'App\\ServiceManagers\\',
                'App\\Services\\',
            ],
            'Repositories' => [
                'App\\Services\\',
                'App\\ServiceManagers\\',
                'App\\Repositories\\',
            ],
            'Validators' => [
                'App\\Checkers\\',
                'App\\Validators\\',
            ],
            'Checkers' => [
                'App\\Checkers\\',
                'App\\Services\\',
                'App\\Repositories\\',
            ],
            'ServiceManagers' => [
                'App\\ServiceManagers\\',
                'App\\Repositories\\',
                'App\\Models\\',
            ],
            'CacheManagers' => [
                'App\\CacheManagers\\',
            ],
        ];

        foreach ($rules as $directory => $forbiddenNamespaces) {
            foreach ($this->phpFiles(app_path($directory)) as $file) {
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
                "{$file} 必須將 transaction 委派給入口 Container",
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

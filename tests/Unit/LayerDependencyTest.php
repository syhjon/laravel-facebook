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
                'App\\Repositories\\',
                'App\\Validators\\',
                'App\\Models\\',
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

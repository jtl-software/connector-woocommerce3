<?php

namespace JtlWooCommerceConnector\Tests;

class ClassInitializationTest extends AbstractTestCase
{
    public function testClassInitialization(): void
    {
        $srcPath = sprintf('%s/src', dirname(__DIR__, 2));
        $classList = $this->findClasses($srcPath);

        $classList = array_filter($classList, static function ($classPath) {
            return str_contains($classPath, 'Traits') === false;
        });

        foreach ($classList as $classPath) {
            $classFile = new \SplFileInfo($classPath);
            $className = str_replace(sprintf('.%s', $classFile->getExtension()), '', $classFile->getFilename());

            $namespace = str_replace([$srcPath, '/'], ['', '\\'], $classFile->getPath());

            $classNameWithNamespace = sprintf('JtlWooCommerceConnector%s\%s', $namespace, $className);

            $mock = $this->getMockBuilder($classNameWithNamespace)->disableOriginalConstructor()->getMock();

            $this->assertInstanceOf($classNameWithNamespace, $mock);
        }
    }

    protected function findClasses(string $srcPath): array
    {
        return glob(sprintf("%s/{,*/,*/*/,*/*/*/}*.php", $srcPath), GLOB_BRACE);
    }
}
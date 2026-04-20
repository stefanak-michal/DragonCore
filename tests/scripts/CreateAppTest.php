<?php

use PHPUnit\Framework\TestCase;

/**
 * CreateAppTest
 *
 * @author Michal Stefanak
 * @link https://github.com/stefanak-michal/DragonCore
 */
class CreateAppTest extends TestCase
{
    private static string $scriptPath;
    private string $tempDir;

    public static function setUpBeforeClass(): void
    {
        self::$scriptPath = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'scripts' . DIRECTORY_SEPARATOR . 'create-app.php';
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'dragon_test_' . uniqid();
    }

    protected function tearDown(): void
    {
        if (file_exists($this->tempDir)) {
            $this->removeDirectory($this->tempDir);
        }
        parent::tearDown();
    }

    private function removeDirectory(string $dir): void
    {
        if (!file_exists($dir)) {
            return;
        }
        $files = array_diff(scandir($dir), array('.', '..'));
        foreach ($files as $file) {
            (is_dir("$dir/$file")) ? $this->removeDirectory("$dir/$file") : unlink("$dir/$file");
        }
        rmdir($dir);
    }

    private function runScript(array $args): array
    {
        $escapedScript = escapeshellarg(self::$scriptPath);
        $escapedArgs = array_map('escapeshellarg', $args);
        $command = PHP_BINARY . ' ' . $escapedScript . ' ' . implode(' ', $escapedArgs);

        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = proc_open($command, $descriptors, $pipes);
        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exitCode = proc_close($process);

        return [
            'stdout' => $stdout,
            'stderr' => $stderr,
            'exitCode' => $exitCode,
        ];
    }

    public function testNoArgumentsPrintsUsage(): void
    {
        $result = $this->runScript([]);
        $this->assertStringContainsString('You have to enter target path', $result['stdout']);
    }

    public function testTooManyArgumentsPrintsUsage(): void
    {
        $result = $this->runScript([$this->tempDir, 'extra-arg']);
        $this->assertStringContainsString('You have to enter target path', $result['stdout']);
    }

    public function testExistingFileAsTargetFails(): void
    {
        $file = $this->tempDir . '_file.php';
        file_put_contents($file, '<?php');
        try {
            $result = $this->runScript([$file]);
            $this->assertStringContainsString('Target path has to be empty directory', $result['stdout']);
        } finally {
            unlink($file);
        }
    }

    public function testNonEmptyDirectoryFails(): void
    {
        mkdir($this->tempDir, 0777, true);
        file_put_contents($this->tempDir . DIRECTORY_SEPARATOR . 'existing.txt', 'data');

        $result = $this->runScript([$this->tempDir]);
        $this->assertStringContainsString('Target path has to be empty directory', $result['stdout']);
    }

    public function testCreatesDirectoryWhenNotExists(): void
    {
        $result = $this->runScript([$this->tempDir]);

        $this->assertEmpty($result['stdout'], 'Expected no error output, got: ' . $result['stdout']);
        $this->assertDirectoryExists($this->tempDir);
    }

    public function testCreatesAllExpectedSubdirectories(): void
    {
        $this->runScript([$this->tempDir]);

        $expectedDirs = [
            'assets',
            'assets' . DIRECTORY_SEPARATOR . 'css',
            'assets' . DIRECTORY_SEPARATOR . 'js',
            'assets' . DIRECTORY_SEPARATOR . 'img',
            'components',
            'config',
            'config' . DIRECTORY_SEPARATOR . 'production',
            'config' . DIRECTORY_SEPARATOR . 'development',
            'controllers',
            'helpers',
            'middleware',
            'models',
            'scripts',
            'vendor',
            'views',
            'views' . DIRECTORY_SEPARATOR . 'homepage',
        ];

        foreach ($expectedDirs as $dir) {
            $this->assertDirectoryExists(
                $this->tempDir . DIRECTORY_SEPARATOR . $dir,
                "Expected directory '$dir' to exist"
            );
        }
    }

    public function testCreatesIndexFile(): void
    {
        $this->runScript([$this->tempDir]);

        $indexPath = $this->tempDir . DIRECTORY_SEPARATOR . 'index.php';
        $this->assertFileExists($indexPath);

        $content = file_get_contents($indexPath);
        $this->assertStringContainsString('APP_PATH', $content);
        $this->assertStringContainsString('DRAGONCORE_PATH', $content);
        $this->assertStringContainsString('init.php', $content);
    }

    public function testCreatesMainConfigFile(): void
    {
        $this->runScript([$this->tempDir]);

        $configPath = $this->tempDir . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'main.cfg.php';
        $this->assertFileExists($configPath);

        $content = file_get_contents($configPath);
        $this->assertStringContainsString('defaultController', $content);
        $this->assertStringContainsString('defaultMethod', $content);
    }

    public function testCreatesRoutesConfigFile(): void
    {
        $this->runScript([$this->tempDir]);

        $routesPath = $this->tempDir . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'routes.cfg.php';
        $this->assertFileExists($routesPath);

        $content = file_get_contents($routesPath);
        $this->assertStringContainsString('routes', $content);
        $this->assertStringContainsString('controllers/Homepage/index', $content);
    }

    public function testCreatesEnvironmentConfigFiles(): void
    {
        $this->runScript([$this->tempDir]);

        $productionConfig = $this->tempDir . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'production' . DIRECTORY_SEPARATOR . 'main.cfg.php';
        $developmentConfig = $this->tempDir . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'development' . DIRECTORY_SEPARATOR . 'main.cfg.php';

        $this->assertFileExists($productionConfig);
        $this->assertFileExists($developmentConfig);

        $devContent = file_get_contents($developmentConfig);
        $this->assertStringContainsString('project_host', $devContent);
        $this->assertStringContainsString(basename($this->tempDir), $devContent);
    }

    public function testCreatesHomepageController(): void
    {
        $this->runScript([$this->tempDir]);

        $controllerPath = $this->tempDir . DIRECTORY_SEPARATOR . 'controllers' . DIRECTORY_SEPARATOR . 'Homepage.php';
        $this->assertFileExists($controllerPath);

        $content = file_get_contents($controllerPath);
        $this->assertStringContainsString('class Homepage', $content);
        $this->assertStringContainsString('IController', $content);
        $this->assertStringContainsString('index', $content);
        $this->assertStringContainsString(basename($this->tempDir), $content);
    }

    public function testCreatesHomepageView(): void
    {
        $this->runScript([$this->tempDir]);

        $viewPath = $this->tempDir . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'homepage' . DIRECTORY_SEPARATOR . 'index.phtml';
        $this->assertFileExists($viewPath);

        $content = file_get_contents($viewPath);
        $this->assertStringContainsString('$msg', $content);
        $this->assertStringContainsString('<html>', $content);
    }

    public function testCreatesGitignore(): void
    {
        $this->runScript([$this->tempDir]);

        $gitignorePath = $this->tempDir . DIRECTORY_SEPARATOR . '.gitignore';
        $this->assertFileExists($gitignorePath);

        $content = file_get_contents($gitignorePath);
        $this->assertStringContainsString('/tmp/', $content);
        $this->assertStringContainsString('/config/development/', $content);
    }

    public function testCreatesHtaccess(): void
    {
        $this->runScript([$this->tempDir]);

        $htaccessPath = $this->tempDir . DIRECTORY_SEPARATOR . '.htaccess';
        $this->assertFileExists($htaccessPath);

        $content = file_get_contents($htaccessPath);
        $this->assertStringContainsString('RewriteEngine On', $content);
        $this->assertStringContainsString(basename($this->tempDir), $content);
        $this->assertStringContainsString('index.php', $content);
    }

    public function testSucceedsWithExistingEmptyDirectory(): void
    {
        mkdir($this->tempDir, 0777, true);

        $result = $this->runScript([$this->tempDir]);

        $this->assertEmpty($result['stdout'], 'Expected no error output, got: ' . $result['stdout']);
        $this->assertDirectoryExists($this->tempDir . DIRECTORY_SEPARATOR . 'controllers');
    }
}

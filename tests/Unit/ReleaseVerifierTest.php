<?php

namespace Tests\Unit;

use App\Services\System\ReleaseVerifier;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/** Admin → System → "Verify files" against release-manifest.json. */
class ReleaseVerifierTest extends TestCase
{
    private string $dir;

    private string $originalBase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->originalBase = $this->app->basePath();
        $this->dir = sys_get_temp_dir().'/biofy-verifier-'.bin2hex(random_bytes(4));
        $files = [];

        foreach (['a.php', 'b.php', 'c/d.php'] as $i => $name) {
            File::ensureDirectoryExists(dirname($this->dir.'/'.$name));
            file_put_contents($this->dir.'/'.$name, "file {$i}");
            $files[$name] = hash('sha256', "file {$i}");
        }

        file_put_contents($this->dir.'/release-manifest.json', json_encode(['version' => '9.9.9', 'files' => $files]));
        $this->app->setBasePath($this->dir);
        config(['biofy.release_public_key' => '']);
    }

    protected function tearDown(): void
    {
        $this->app->setBasePath($this->originalBase);
        File::deleteDirectory($this->dir);

        parent::tearDown();
    }

    public function test_complete_check_of_an_unsigned_manifest(): void
    {
        $result = (new ReleaseVerifier)->verify();

        $this->assertSame('unsigned', $result['status']);
        $this->assertSame(3, $result['checked']);
        $this->assertSame(3, $result['total']);
    }

    public function test_modified_and_missing_files_are_reported(): void
    {
        file_put_contents($this->dir.'/a.php', 'changed');
        unlink($this->dir.'/c/d.php');

        $result = (new ReleaseVerifier)->verify();

        $this->assertSame('files_mismatch', $result['status']);
        $this->assertSame(['a.php'], $result['modified']);
        $this->assertSame(['c/d.php'], $result['missing']);
    }

    public function test_running_out_of_time_is_never_reported_as_ok(): void
    {
        $result = (new ReleaseVerifier)->verify(0);

        $this->assertSame('incomplete', $result['status']);
        $this->assertLessThan($result['total'], $result['checked']);
    }
}

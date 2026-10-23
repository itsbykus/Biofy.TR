<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Shared hosting layout C (the whole package inside the web root, docs/SHARED_HOSTING.md): every
 * application folder carries a "Require all denied" .htaccess that the web server applies before the
 * request is rewritten into public/. A URL whose first segment is such a folder never reaches Laravel.
 */
class SharedHostingLayoutTest extends TestCase
{
    /** Top-level folders of the release package (scripts/build-release.php), except public/. */
    private const PACKAGE_FOLDERS = ['app', 'bootstrap', 'config', 'database', 'docs', 'install', 'lang', 'resources', 'routes', 'storage', 'vendor'];

    public function test_no_route_starts_with_an_application_folder(): void
    {
        $clashes = [];

        foreach (Route::getRoutes() as $route) {
            if (in_array(explode('/', ltrim($route->uri(), '/'))[0], self::PACKAGE_FOLDERS, true)) {
                $clashes[] = $route->uri();
            }
        }

        $this->assertSame([], $clashes, 'These URLs answer 403 in layout C');
    }

    public function test_application_folders_cannot_be_page_addresses(): void
    {
        $this->assertSame([], array_values(array_diff(self::PACKAGE_FOLDERS, config('biofy.reserved_slugs'))));
    }

    public function test_folder_list_matches_the_build_script(): void
    {
        preg_match('/\$copy = \[(.*?)\];/s', (string) file_get_contents(base_path('scripts/build-release.php')), $m);
        preg_match_all("/^\s*'([a-z]+)' => /m", $m[1] ?? '', $entries);
        $shipped = array_values(array_filter($entries[1], fn ($name) => $name !== 'public' && is_dir(base_path($name))));

        // storage/ and vendor/ are added by the build itself (runtime folders, composer install).
        $this->assertEqualsCanonicalizing(array_values(array_diff(self::PACKAGE_FOLDERS, ['storage', 'vendor'])), $shipped);
    }
}

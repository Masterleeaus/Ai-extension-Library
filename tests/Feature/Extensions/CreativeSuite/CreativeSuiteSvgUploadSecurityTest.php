<?php

declare(strict_types=1);

use App\Extensions\CreativeSuite\System\CreativeSuiteServiceProvider;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    $this->app->register(CreativeSuiteServiceProvider::class);
    Storage::fake('uploads');
});

function creativeSuiteSvgUpload(string $contents): UploadedFile
{
    $path = tempnam(sys_get_temp_dir(), 'creative-suite-svg-');

    if ($path === false) {
        throw new RuntimeException('Unable to create SVG fixture.');
    }

    file_put_contents($path, $contents);

    return new UploadedFile(
        $path,
        'asset.svg',
        'image/svg+xml',
        null,
        true,
    );
}

test('CreativeSuite strips executable and external content from uploaded SVG', function (): void {
    $user = User::factory()->create();
    $svg = <<<'SVG'
<svg xmlns="http://www.w3.org/2000/svg" onload="alert(1)">
  <script>alert(1)</script>
  <foreignObject><body xmlns="http://www.w3.org/1999/xhtml">unsafe</body></foreignObject>
  <image href="http://169.254.169.254/latest/meta-data/" />
  <rect width="10" height="10" onclick="alert(1)" style="fill:red" />
</svg>
SVG;

    $response = $this->actingAs($user)
        ->post('/dashboard/user/creative-suite/image/upload', [
            'image' => creativeSuiteSvgUpload($svg),
        ])
        ->assertOk()
        ->assertJsonPath('data.path', fn (string $path): bool => str_ends_with($path, '.svg'));

    $storedPath = str($response->json('data.path'))->after('uploads/')->toString();
    $stored = Storage::disk('uploads')->get($storedPath);

    expect($stored)
        ->not->toContain('<script')
        ->not->toContain('<foreignObject')
        ->not->toContain('onload=')
        ->not->toContain('onclick=')
        ->not->toContain('style=')
        ->not->toContain('169.254.169.254');
});

test('CreativeSuite rejects DTD and entity declarations in uploaded SVG', function (): void {
    $user = User::factory()->create();
    $svg = <<<'SVG'
<!DOCTYPE svg [<!ENTITY xxe SYSTEM "file:///etc/passwd">]>
<svg xmlns="http://www.w3.org/2000/svg"><text>&xxe;</text></svg>
SVG;

    $this->actingAs($user)
        ->post('/dashboard/user/creative-suite/image/upload', [
            'image' => creativeSuiteSvgUpload($svg),
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('image');

    expect(Storage::disk('uploads')->allFiles())->toBeEmpty();
});

test('CreativeSuite preserves safe SVG geometry and fragment references', function (): void {
    $user = User::factory()->create();
    $svg = <<<'SVG'
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100">
  <defs>
    <linearGradient id="g"><stop offset="0" stop-color="#fff"/><stop offset="1" stop-color="#000"/></linearGradient>
    <symbol id="dot"><circle cx="5" cy="5" r="5"/></symbol>
  </defs>
  <rect width="100" height="100" fill="url(#g)"/>
  <use href="#dot" x="10" y="10"/>
</svg>
SVG;

    $response = $this->actingAs($user)
        ->post('/dashboard/user/creative-suite/image/upload', [
            'image' => creativeSuiteSvgUpload($svg),
        ])
        ->assertOk();

    $storedPath = str($response->json('data.path'))->after('uploads/')->toString();
    $stored = Storage::disk('uploads')->get($storedPath);

    expect($stored)
        ->toContain('<linearGradient')
        ->toContain('fill="url(#g)"')
        ->toContain('href="#dot"');
});

test('CreativeSuite keeps raster upload response compatibility', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post('/dashboard/user/creative-suite/image/upload', [
            'image' => UploadedFile::fake()->create('photo.png', 10, 'image/png'),
        ])
        ->assertOk()
        ->assertJsonStructure([
            'data' => ['path', 'url'],
        ]);
});

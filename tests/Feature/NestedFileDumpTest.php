<?php declare(strict_types=1);

use Bambamboole\LaravelTranslationDumper\DTO\Translation;
use Bambamboole\LaravelTranslationDumper\TranslationDumper;
use Illuminate\Filesystem\Filesystem;

beforeEach(function () {
    $this->fs = new Filesystem;
    $this->lang = sys_get_temp_dir().'/ltd-nested-'.uniqid('', true);
    $this->fs->ensureDirectoryExists($this->lang.'/en/entities');
});

afterEach(function () {
    $this->fs->deleteDirectory($this->lang);
});

it('writes a dotted key into an existing nested file instead of a flat one', function () {
    $this->fs->put(
        $this->lang.'/en/entities/salesOrder.php',
        "<?php declare(strict_types=1);\n\nreturn ['title' => 'Sales order'];\n",
    );

    (new TranslationDumper($this->fs, $this->lang, 'en'))->dump([
        new Translation('entities.salesOrder.status', 'x-entities.salesOrder.status'),
    ]);

    // It merged into the existing nested file ...
    expect(require $this->lang.'/en/entities/salesOrder.php')->toEqual([
        'status' => 'x-entities.salesOrder.status',
        'title' => 'Sales order',
    ]);
    // ... and did not create a flat entities.php alongside it.
    expect($this->fs->exists($this->lang.'/en/entities.php'))->toBeFalse();
});

it('still writes to a flat top level file when no nested file exists', function () {
    (new TranslationDumper($this->fs, $this->lang, 'en'))->dump([
        new Translation('foo.bar.baz', 'x-foo.bar.baz'),
    ]);

    expect(require $this->lang.'/en/foo.php')->toEqual([
        'bar' => ['baz' => 'x-foo.bar.baz'],
    ]);
    // No nested foo/ directory or file was created.
    expect($this->fs->exists($this->lang.'/en/foo'))->toBeFalse();
});

it('prefers the deepest existing nested file', function () {
    $this->fs->ensureDirectoryExists($this->lang.'/en/entities/salesOrder');
    $this->fs->put(
        $this->lang.'/en/entities/salesOrder/lines.php',
        "<?php declare(strict_types=1);\n\nreturn ['label' => 'Lines'];\n",
    );

    (new TranslationDumper($this->fs, $this->lang, 'en'))->dump([
        new Translation('entities.salesOrder.lines.total', 'x-entities.salesOrder.lines.total'),
    ]);

    expect(require $this->lang.'/en/entities/salesOrder/lines.php')->toEqual([
        'label' => 'Lines',
        'total' => 'x-entities.salesOrder.lines.total',
    ]);
    expect($this->fs->exists($this->lang.'/en/entities.php'))->toBeFalse();
    expect($this->fs->exists($this->lang.'/en/entities/salesOrder.php'))->toBeFalse();
});

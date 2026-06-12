<?php declare(strict_types=1);

use Bambamboole\LaravelTranslationDumper\DTO\Translation;
use Bambamboole\LaravelTranslationDumper\FileTranslationWriter;
use Bambamboole\LaravelTranslationDumper\TranslationDumper;
use Illuminate\Filesystem\Filesystem;

beforeEach(function () {
    $this->fs = new Filesystem;
    $this->lang = sys_get_temp_dir().'/ltd-json-'.uniqid('', true);
    $this->fs->ensureDirectoryExists($this->lang);
});

afterEach(function () {
    $this->fs->deleteDirectory($this->lang);
});

it('writes JSON translations with unescaped unicode and slashes', function () {
    (new TranslationDumper(new FileTranslationWriter($this->fs, $this->lang), 'de'))->dump([
        new Translation('Schöne Grüße', 'Schöne Grüße'),
        new Translation('Open the door', 'Tür auf/zu'),
    ]);

    $raw = $this->fs->get($this->lang.'/de.json');

    expect($raw)
        ->toContain('Schöne Grüße')
        ->toContain('Tür auf/zu')
        ->not->toContain('\\u00')
        ->not->toContain('\\/');
});

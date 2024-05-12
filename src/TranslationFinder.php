<?php declare(strict_types=1);

namespace Bambamboole\LaravelTranslationDumper;

use Illuminate\Support\Str;
use Symfony\Component\Finder\Finder;

class TranslationFinder
{
    public function __construct(private readonly string $basePath)
    {
    }

    public function findTranslations(): array
    {
        $groupKeys = [];
        $stringKeys = [];
        $functions = [
            'trans',
            'trans_choice',
            'Lang::get',
            'Lang::choice',
            'Lang::trans',
            'Lang::transChoice',
            '@lang',
            '@choice',
            '__',
        ];

        // The translation search part is based on the work of the awesome barryvdh in
        // https://github.com/barryvdh/laravel-translation-manager/blob/master/src/Manager.php
        $groupPattern =                          // See https://regex101.com/r/WEJqdL/6
            '[^\w|>]'.                          // Must not have an alphanum or _ or > before real method
            '('.implode('|', $functions).')'.  // Must start with one of the functions
            '\('.                               // Match opening parenthesis
            "[\'\"]".                           // Match " or '
            '('.                                // Start a new group to match:
            '[\/a-zA-Z0-9_-]+'.                 // Must start with group
            "([.](?! )[^\1)]+)+".               // Be followed by one or more items/keys
            ')'.                                // Close group
            "[\'\"]".                           // Closing quote
            '[\),]';                             // Close parentheses or new parameter

        $stringPattern =
            '[^\w]'.                                     // Must not have an alphanum before real method
            '('.implode('|', $functions).')'.             // Must start with one of the functions
            '\(\s*'.                                       // Match opening parenthesis
            "(?P<quote>['\"])".                            // Match " or ' and store in {quote}
            "(?P<string>(?:\\\k{quote}|(?!\k{quote}).)*)". // Match any string that can be {quote} escaped
            '\k{quote}'.                                   // Match " or ' previously matched
            '\s*[\),]';                                    // Close parentheses or new parameter

        // Find all PHP + Twig files in the app folder, except for storage
        $finder = new Finder();
        $files = $finder->in($this->basePath)
            ->exclude('storage')
            ->exclude('vendor')
            ->name('*.php')
            ->files();

        /** @var \Symfony\Component\Finder\SplFileInfo $file */
        foreach ($files as $file) {
            // Search the current file for the pattern
            if (preg_match_all("/$groupPattern/siU", $file->getContents(), $matches)) {
                // Get all matches
                foreach ($matches[2] as $key) {
                    $groupKeys[] = $key;
                }
            }

            if (preg_match_all("/$stringPattern/siU", $file->getContents(), $matches)) {
                foreach ($matches['string'] as $key) {
                    if (preg_match("/(^[\/a-zA-Z0-9_-]+([.][^\1)\ ]+)+$)/siU", $key, $groupMatches)) {
                        // group{.group}.key format, already in $groupKeys but also matched here
                        // do nothing, it has to be treated as a group
                        continue;
                    }

                    //TODO: This can probably be done in the regex, but I couldn't do it.
                    //skip keys which contain namespacing characters, unless they also contain a
                    //space, which makes it JSON.
                    if (! (Str::contains($key, '::') && Str::contains($key, '.'))
                        || Str::contains($key, ' ')) {
                        $stringKeys[] = $key;
                    }
                }
            }
        }
        // Remove duplicates
        $groupKeys = array_unique($groupKeys);
        $stringKeys = array_unique($stringKeys);
        // Merge and order them
        $keys = array_merge($groupKeys, $stringKeys);
        ksort($keys, SORT_NATURAL | SORT_FLAG_CASE);

        return $keys;
    }
}

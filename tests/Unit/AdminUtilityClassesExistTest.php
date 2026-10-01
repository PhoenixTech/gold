<?php

namespace Tests\Unit;

use Tests\TestCase;

/**
 * Guards the admin panel against dead utility classes.
 *
 * The admin blades use a fine-grained type scale (fs-11 .. fs-18) and half-step
 * spacing (gap-1.5, p-2.5, ...) that Bootstrap does not ship. Those classes used
 * to live only in client-custom/_utilities.scss, which the admin bundle never
 * imports -- so several hundred usages across the order board, invoice form,
 * printable invoice, stock list, product steps, deliveries and help topics
 * silently rendered at the inherited size.
 *
 * This test reads the compiled admin stylesheet and fails if any utility class
 * used by an admin Blade is missing from it, so the two cannot drift again.
 */
class AdminUtilityClassesExistTest extends TestCase
{
    /**
     * Utilities the admin bundle must define.
     *
     * Only classes Bootstrap 5 does NOT already ship; Bootstrap's own
     * fs-* / spacing scale is excluded so the test cannot fail on framework CSS.
     *
     * @var list<string>
     */
    private const REQUIRED = [
        'fs-11', 'fs-12', 'fs-13', 'fs-14', 'fs-15', 'fs-16', 'fs-18',
        // Used by the printable invoice's fine print; defined nowhere in SCSS.
        'fs-xs', 'fs-xxs',
        'gap-1.5', 'gap-2.5',
        'p-1.5', 'p-2.5', 'px-1.5', 'px-2.5', 'py-0.5', 'py-2.5',
        'mb-1.5', 'mb-2.5', 'mt-0.5', 'me-0.5',
    ];

    public function test_compiled_admin_stylesheet_defines_every_required_utility(): void
    {
        $css = $this->adminStylesheet();
        $missing = [];

        foreach (self::REQUIRED as $class) {
            if (! $this->defines($css, $class)) {
                $missing[] = $class;
            }
        }

        $this->assertSame(
            [],
            $missing,
            'These utilities are used by resources/views/admin but missing from the compiled admin CSS: '
                .implode(', ', $missing)
                .'. Add them to resources/sass/panel/_utilities.scss and rebuild assets.'
        );
    }

    public function test_no_admin_blade_uses_a_pseudo_fa_utility_that_is_never_defined(): void
    {
        $css = $this->adminStylesheet();
        $bladeDir = base_path('resources/views/admin');
        $missing = [];

        foreach ($this->bladeFiles($bladeDir) as $file) {
            $contents = (string) file_get_contents($file);

            foreach ($this->utilityClassesIn($contents) as $class) {
                if (! $this->defines($css, $class)) {
                    $missing[] = $this->relative($file).': '.$class;
                }
            }
        }

        $this->assertSame(
            [],
            array_values(array_unique($missing)),
            'Admin Blade files reference utility classes the compiled admin CSS does not define. '
                .'They render at the inherited size. Fix by adding them to resources/sass/panel/_utilities.scss.'
        );
    }

    public function test_admin_entrypoint_imports_the_utilities_partial(): void
    {
        // The original defect: the utilities existed in
        // client-custom/_utilities.scss, which app.scss never imported, so the
        // compiled admin bundle silently lacked every fs-* and half-step class.
        $appScss = (string) file_get_contents(base_path('resources/sass/app.scss'));

        $this->assertMatchesRegularExpression(
            '/@import\s+[\'"]panel\/utilities[\'"]/',
            $appScss,
            'resources/sass/app.scss must import panel/utilities, otherwise the admin bundle has no fs-* or half-step spacing utilities.'
        );
    }

    public function test_utilities_partial_does_not_shadow_bootstrap_spacing(): void
    {
        $partial = (string) file_get_contents(base_path('resources/sass/panel/_utilities.scss'));

        // client-custom/_utilities.scss redefines .gap-4 / .gap-5 / .p-5 with
        // !important, which would fight Bootstrap's own spacing scale across the
        // whole admin panel -- that is why the admin has its own partial.
        foreach (['gap-4', 'gap-5', 'p-5', 'm-5', 'p-3', 'gap-3'] as $bootstrapOwned) {
            $this->assertDoesNotMatchRegularExpression(
                '/\.'.preg_quote($bootstrapOwned, '/').'\s*\{/',
                $partial,
                "panel/_utilities.scss must not redefine Bootstrap's .{$bootstrapOwned}."
            );
        }

        // Nor may it consume client-only design tokens that the admin bundle
        // never defines -- an unresolved custom property silently degrades to
        // `unset` at computed-value time and kills the declaration.
        $this->assertStringNotContainsString(
            'var(--xshop-',
            $partial,
            'panel/_utilities.scss must not reference --xshop-* tokens; they only exist in the client bundle.'
        );
    }

    private function adminStylesheet(): string
    {
        $assets = base_path('public/build/assets');
        $candidates = glob($assets.'/app-*.css') ?: [];

        if ($candidates === []) {
            $this->markTestSkipped('Compiled admin stylesheet not found. Run `npm run build` first.');
        }

        usort($candidates, fn ($a, $b) => strlen((string) file_get_contents($b)) <=> strlen((string) file_get_contents($a)));

        // Sass escapes the dot in names like .p-1\.5; strip escapes so we can
        // match the semantic class name.
        return str_replace('\\', '', (string) file_get_contents($candidates[0]));
    }

    /**
     * @return list<string>
     */
    private function bladeFiles(string $directory): array
    {
        $files = [];
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directory));

        foreach ($iterator as $file) {
            if ($file->isFile() && str_ends_with($file->getFilename(), '.blade.php')) {
                $files[] = $file->getPathname();
            }
        }

        sort($files);

        return $files;
    }

    /**
     * Every utility-looking class name mentioned in class="..." attributes.
     *
     * @return list<string>
     */
    private function utilityClassesIn(string $contents): array
    {
        $found = [];

        if (preg_match_all('/class="([^"]*)"/', $contents, $matches)) {
            foreach ($matches[1] as $attribute) {
                // Drop Blade expressions so we only see literal class names.
                $attribute = preg_replace('/\{\{.*?\}\}|\{!!.*?!!\}|@lang\([^)]*\)|\S*@\w+\{?[^}]*\}?/s', ' ', $attribute);

                foreach (preg_split('/\s+/', (string) $attribute) as $token) {
                    $token = trim($token);
                    if ($token === '') {
                        continue;
                    }

                    if (preg_match('/^(fs-[a-z0-9]+|(?:p|px|py|m|mx|my|mt|mb|ms|me|gap)-\d+(?:\.\d+)?)$/', $token)) {
                        $found[] = $token;
                    }
                }
            }
        }

        return array_values(array_unique($found));
    }

    private function defines(string $css, string $class): bool
    {
        $quoted = preg_quote($class, '/');

        return (bool) preg_match('/\.'.$quoted.'(?=[\s,{:>+~.\[])/', $css);
    }

    private function relative(string $path): string
    {
        return str_replace(base_path().'/', '', $path);
    }
}

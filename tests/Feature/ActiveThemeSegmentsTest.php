<?php

namespace Tests\Feature;

use Tests\TestCase;

class ActiveThemeSegmentsTest extends TestCase
{
    /**
     * Required client plain MVC Blade views.
     *
     * @return list<string>
     */
    private function expectedClientViews(): array
    {
        return [
            'home.blade.php',
            'products/index.blade.php',
            'products/show.blade.php',
            'categories/show.blade.php',
            'posts/index.blade.php',
            'posts/show.blade.php',
            'posts/group.blade.php',
            'posts/tag.blade.php',
            'galleries/index.blade.php',
            'galleries/show.blade.php',
            'clips/index.blade.php',
            'clips/show.blade.php',
            'attachments/index.blade.php',
            'attachments/show.blade.php',
            'contact/index.blade.php',
            'compare/index.blade.php',
            'customer/profile.blade.php',
            'customer/invoice.blade.php',
            'customer/ticket.blade.php',
            'cart/index.blade.php',
            'auth/login.blade.php',
            'auth/register.blade.php',
            'partials/header.blade.php',
            'partials/footer.blade.php',
            'partials/product-card.blade.php',
            'partials/post-card.blade.php',
            'partials/breadcrumbs.blade.php',
            'partials/product-sidebar.blade.php',
            'partials/post-sidebar.blade.php',
        ];
    }

    public function test_all_plain_mvc_client_views_exist(): void
    {
        $base = resource_path('views/client');

        foreach ($this->expectedClientViews() as $viewPath) {
            $this->assertFileExists($base.'/'.$viewPath, "Expected view {$viewPath} to exist.");
        }
    }

    public function test_modular_client_assets_exist(): void
    {
        $this->assertFileExists(resource_path('sass/client/_header.scss'));
        $this->assertFileExists(resource_path('sass/client/_footer.scss'));
        $this->assertFileExists(resource_path('sass/client/_home.scss'));
        $this->assertFileExists(resource_path('sass/client/_products.scss'));
        $this->assertFileExists(resource_path('sass/client/_cart.scss'));
        $this->assertFileExists(resource_path('sass/client/_customer.scss'));
        $this->assertFileExists(resource_path('sass/client/_auth.scss'));
        $this->assertFileExists(resource_path('sass/client/_posts.scss'));
        $this->assertFileExists(resource_path('sass/client/_media.scss'));

        $this->assertFileExists(resource_path('js/client/header.js'));
        $this->assertFileExists(resource_path('js/client/home.js'));
        $this->assertFileExists(resource_path('js/client/products.js'));
        $this->assertFileExists(resource_path('js/client/customer.js'));
        $this->assertFileExists(resource_path('js/client/gallery.js'));
    }
}

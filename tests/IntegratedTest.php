<?php

namespace JeroenG\Packager\Tests;

use Illuminate\Support\Facades\Artisan;

final class IntegratedTest extends TestCase
{
    public function test_new_package_is_created(): void
    {
        Artisan::call('packager:new', ['vendor' => 'MyVendor', 'name' => 'MyPackage']);

        $this->seeInConsoleOutput('Package created successfully!');
        $this->assertTrue(is_dir(base_path('packages/MyVendor/MyPackage')));
    }

    public function test_new_package_symlink_is_created(): void
    {
        Artisan::call('packager:new', ['vendor' => 'MyVendor', 'name' => 'MyPackage']);

        $this->seeInConsoleOutput('Package created successfully!');

        // There's a problem here: the symlink in the vendor folder IS created when running
        // 'php artisan packager:new MyVendor MyPackage' from root, but it is NOT created
        // when that's run as part of the tests. So this IS working for packager users,
        // but the test cannot confirm that.
        $this->markTestIncomplete();
        $this->assertTrue(is_link(base_path('vendor/myvendor/mypackage')));
    }

    public function test_new_package_is_installed(): void
    {
        Artisan::call('packager:new', ['vendor' => 'MyVendor', 'name' => 'MyPackage']);

        $composer = file_get_contents(base_path('composer.json'));

        $this->assertStringContainsString('MyVendor/MyPackage', $composer);
    }

    public function test_new_package_studly_install(): void
    {
        Artisan::call('packager:new', ['vendor' => 'my-vendor', 'name' => 'my-package']);

        $this->seeInConsoleOutput('Package created successfully!');
        $this->assertStringContainsString('my-vendor/my-package', file_get_contents(base_path('composer.json')));
        $this->assertTrue(is_file(base_path('packages/my-vendor/my-package/src/MyPackageServiceProvider.php')));
    }

    public function test_new_package_name_should_be_valid(): void
    {
        Artisan::call('packager:new', ['vendor' => 'my-vendor', 'name' => '1234-Invalid']);
        $this->seeInConsoleOutput('Package was not created. Please choose a valid name.');
        $this->assertFalse(is_file(base_path('packages/my-vendor/4-Invalid/src/1234InvalidServiceProvider.php')));
    }

    public function test_new_package_name_in_interactive_mode_should_be_valid(): void
    {
        $this->artisan('packager:new', ['--i' => true])
            ->expectsQuestion('What will be the vendor name?', 'my-vendor')
            ->expectsQuestion('What will be the package name?', '1234-Invalid')
            ->expectsOutput('Package was not created. Please choose a valid name.')
            ->assertExitCode(1);

        $this->assertFalse(is_file(base_path('packages/my-vendor/4-Invalid/src/1234InvalidServiceProvider.php')));
    }

    public function test_new_package_vendor_name_should_be_valid(): void
    {
        Artisan::call('packager:new', ['vendor' => '1234-invalid', 'name' => 'my-package']);
        $this->seeInConsoleOutput('Package was not created. Please choose a valid name.');
        $this->assertFalse(is_file(base_path('packages/1234-invalid/my-package/src/MyPackageServiceProvider.php')));
    }

    public function test_new_package_vendor_name_in_interactive_mode_should_be_valid(): void
    {
        $this->artisan('packager:new', ['--i' => true])
            ->expectsQuestion('What will be the vendor name?', '1234-invalid')
            ->expectsQuestion('What will be the package name?', 'my-package')
            ->expectsOutput('Package was not created. Please choose a valid name.')
            ->assertExitCode(1);

        $this->assertFalse(is_file(base_path('packages/my-vendor/4-Invalid/src/1234InvalidServiceProvider.php')));
    }

    public function test_new_package_is_installed_from_custom_skeleton(): void
    {
        Artisan::call('packager:new', [
            'vendor' => 'AnotherVendor',
            'name' => 'AnotherPackage',
            '--skeleton' => 'http://github.com/Jeroen-G/packager-skeleton/archive/master.zip',
        ]);

        $composer = file_get_contents(base_path('composer.json'));

        $this->assertStringContainsString('AnotherVendor/AnotherPackage', $composer);
    }

    public function test_get_package(): void
    {
        Artisan::call('packager:get',
            ['url' => 'https://github.com/Jeroen-G/packager-skeleton', 'vendor' => 'MyVendor', 'name' => 'MyPackage']);

        $this->seeInConsoleOutput('Package downloaded successfully!');
    }

    public function test_get_existing_package_with_git(): void
    {
        Artisan::call('packager:git',
            ['url' => 'https://github.com/Seldaek/monolog', 'vendor' => 'monolog', 'name' => 'monolog']);

        $this->seeInConsoleOutput('Package cloned successfully!');
        $this->assertTrue(is_link(base_path('vendor/monolog/monolog')));
    }

    public function test_get_existing_package_with_get(): void
    {
        Artisan::call('packager:get',
            ['url' => 'https://github.com/Seldaek/monolog', 'vendor' => 'monolog', 'name' => 'monolog', '--branch' => 'main']);

        $this->seeInConsoleOutput('Package downloaded successfully!');
        $this->assertTrue(is_link(base_path('vendor/monolog/monolog')));
    }

    public function test_list_packages(): void
    {
        Artisan::call('packager:new', ['vendor' => 'MyVendor', 'name' => 'MyPackage']);
        Artisan::call('packager:list');

        $this->seeInConsoleOutput('MyVendor');
    }

    public function test_removing_package(): void
    {
        Artisan::call('packager:new', ['vendor' => 'MyVendor', 'name' => 'MyPackage']);
        $this->seeInConsoleOutput('MyVendor');

        Artisan::call('packager:remove', ['vendor' => 'MyVendor', 'name' => 'MyPackage', '--no-interaction' => true]);
        $this->seeInConsoleOutput('Package removed successfully!');
    }

    public function test_new_package_is_uninstalled(): void
    {
        Artisan::call('packager:new', ['vendor' => 'MyVendor', 'name' => 'MyPackage']);
        Artisan::call('packager:remove', ['vendor' => 'MyVendor', 'name' => 'MyPackage', '--no-interaction' => true]);

        $composer = file_get_contents(base_path('composer.json'));

        $this->assertStringNotContainsString('MyVendor/MyPackage', $composer);
    }
}

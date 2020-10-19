<?php

namespace JeroenG\Packager\Tests;

use Illuminate\Support\Facades\Artisan;
use JeroenG\Packager\PackageRepository;

class IntegratedTest extends TestCase
{
    public function test_url_parser()
    {
        $parser = new PackageRepository();

        $url = $parser->parse('jeroen-g/laravel-packager')->getZipUrl();
        $this->assertEquals('https://github.com/Jeroen-G/laravel-packager/archive/master.zip', $url);

        $expected = 'https://github.com/author/package/archive/dev.zip';
        $url = $parser->parse('https://github.com/author/package')->getZipUrl('dev');
        $this->assertEquals($expected, $url);
        $url = $parser->parse('git@github.com:author/package.git')->getZipUrl('dev');
        $this->assertEquals($expected, $url);

        $expected = 'https://gitlab.com/author/package/-/archive/dev/package-dev.zip';
        $url = $parser->parse('https://gitlab.com/author/package')->getZipUrl('dev');
        $this->assertEquals($expected, $url);
        $url = $parser->parse('git@gitlab.com:author/package.git')->getZipUrl('dev');
        $this->assertEquals($expected, $url);

        $expected = 'https://bitbucket.org/author/package/get/dev.zip';
        $url = $parser->parse('https://bitbucket.org/author/package')->getZipUrl('dev');
        $this->assertEquals($expected, $url);
        $url = $parser->parse('git@bitbucket.org:author/package.git')->getZipUrl('dev');
        $this->assertEquals($expected, $url);

        $this->app['config']->set('packager.repositories', [
            'my.repo.com' => 'https://:host/:vendor/:name/:branch.zip',
        ]);

        $expected = 'https://my.repo.com/author/package/dev.zip';
        $url = $parser->parse('https://my.repo.com/author/package')->getZipUrl('dev');
        $this->assertEquals($expected, $url);
        $url = $parser->parse('git@my.repo.com:author/package.git')->getZipUrl('dev');
        $this->assertEquals($expected, $url);
    }

    public function test_new_package_is_created()
    {
        Artisan::call('packager:new', ['vendor' => 'MyVendor', 'name' => 'MyPackage']);

        $this->seeInConsoleOutput('Package created successfully!');
        $this->assertTrue(is_link(base_path('vendor/myvendor/mypackage')));
    }

    public function test_new_package_is_installed()
    {
        Artisan::call('packager:new', ['vendor' => 'MyVendor', 'name' => 'MyPackage']);

        $composer = file_get_contents(base_path('composer.json'));

        $this->assertStringContainsString('MyVendor/MyPackage', $composer);
    }

    public function test_new_package_studly_install()
    {
        Artisan::call('packager:new', ['vendor' => 'my-vendor', 'name' => 'my-package']);

        $this->seeInConsoleOutput('Package created successfully!');
        $this->assertStringContainsString('my-vendor/my-package', file_get_contents(base_path('composer.json')));
        $this->assertTrue(is_file(base_path('packages/my-vendor/my-package/src/MyPackageServiceProvider.php')));
    }

    public function test_new_package_name_should_be_valid()
    {
        Artisan::call('packager:new', ['vendor' => 'my-vendor', 'name' => '1234-Invalid']);
        $this->seeInConsoleOutput('Package was not created. Please choose a valid name.');
        $this->assertFalse(is_file(base_path('packages/my-vendor/4-Invalid/src/1234InvalidServiceProvider.php')));
    }

    public function test_new_package_name_in_interactive_mode_should_be_valid()
    {
        $this->artisan('packager:new', ['--i' => true])
            ->expectsQuestion('What will be the vendor name?', 'my-vendor')
            ->expectsQuestion('What will be the package name?', '1234-Invalid')
            ->expectsOutput('Package was not created. Please choose a valid name.')
            ->assertExitCode(1);

        $this->assertFalse(is_file(base_path('packages/my-vendor/4-Invalid/src/1234InvalidServiceProvider.php')));
    }

    public function test_new_package_vendor_name_should_be_valid()
    {
        Artisan::call('packager:new', ['vendor' => '1234-invalid', 'name' => 'my-package']);
        $this->seeInConsoleOutput('Package was not created. Please choose a valid name.');
        $this->assertFalse(is_file(base_path('packages/1234-invalid/my-package/src/MyPackageServiceProvider.php')));
    }

    public function test_new_package_vendor_name_in_interactive_mode_should_be_valid()
    {
        $this->artisan('packager:new', ['--i' => true])
            ->expectsQuestion('What will be the vendor name?', '1234-invalid')
            ->expectsQuestion('What will be the package name?', 'my-package')
            ->expectsOutput('Package was not created. Please choose a valid name.')
            ->assertExitCode(1);

        $this->assertFalse(is_file(base_path('packages/my-vendor/4-Invalid/src/1234InvalidServiceProvider.php')));
    }

    public function test_new_package_is_installed_from_custom_skeleton()
    {
        Artisan::call('packager:new', [
            'vendor' => 'AnotherVendor',
            'name' => 'AnotherPackage',
            '--skeleton' => 'http://github.com/Jeroen-G/packager-skeleton/archive/master.zip',
        ]);

        $composer = file_get_contents(base_path('composer.json'));

        $this->assertStringContainsString('AnotherVendor/AnotherPackage', $composer);
    }

    public function test_get_package()
    {
        Artisan::call('packager:get',
            ['url' => 'https://github.com/Jeroen-G/packager-skeleton', 'vendor' => 'MyVendor', 'name' => 'MyPackage']);

        $this->seeInConsoleOutput('Package downloaded successfully!');

        Artisan::call('packager:get', ['url' => 'jeroen-g/laravel-packager']);

        $this->seeInConsoleOutput('Package downloaded successfully!');
    }

    public function test_get_existing_package_with_git()
    {
        Artisan::call('packager:git',
            ['url' => 'https://github.com/Seldaek/monolog', 'vendor' => 'monolog', 'name' => 'monolog']);

        $this->seeInConsoleOutput('Package cloned successfully!');
        $this->assertTrue(is_link(base_path('vendor/monolog/monolog')));
    }

    public function test_get_existing_package_with_get()
    {
        Artisan::call('packager:get',
            ['url' => 'https://github.com/Seldaek/monolog', 'vendor' => 'monolog', 'name' => 'monolog']);

        $this->seeInConsoleOutput('Package downloaded successfully!');
        $this->assertTrue(is_link(base_path('vendor/monolog/monolog')));
    }

    public function test_list_packages()
    {
        Artisan::call('packager:new', ['vendor' => 'MyVendor', 'name' => 'MyPackage']);
        Artisan::call('packager:list');

        $this->seeInConsoleOutput('MyVendor');
    }

    public function test_removing_package()
    {
        Artisan::call('packager:new', ['vendor' => 'MyVendor', 'name' => 'MyPackage']);
        $this->seeInConsoleOutput('MyVendor');

        Artisan::call('packager:remove', ['vendor' => 'MyVendor', 'name' => 'MyPackage', '--no-interaction' => true]);
        $this->seeInConsoleOutput('Package removed successfully!');
    }

    public function test_new_package_is_uninstalled()
    {
        Artisan::call('packager:new', ['vendor' => 'MyVendor', 'name' => 'MyPackage']);
        Artisan::call('packager:remove', ['vendor' => 'MyVendor', 'name' => 'MyPackage', '--no-interaction' => true]);

        $composer = file_get_contents(base_path('composer.json'));

        $this->assertStringNotContainsString('MyVendor/MyPackage', $composer);
    }
}

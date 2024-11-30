<?php

namespace JeroenG\Packager\Tests;

use Illuminate\Config\Repository;
use Illuminate\Support\Facades\Artisan;

final class SkeletonArchiveExtractorsTest extends TestCase
{
    public function test_new_package_is_created_with_tar_gz_skeleton(): void
    {
        Artisan::call('packager:new', ['vendor' => 'MyVendor', 'name' => 'MyPackage']);

        $this->seeInConsoleOutput('Package created successfully!');
    }

    protected function getEnvironmentSetUp($app)
    {
        /** @var Repository $config */
        $config = $app['config'];

        // Change the extension in github archive url form .zip to .tar.gz
        $originalZipUrl = $config->get('packager.skeleton');
        $tarGzUrl = str_replace('.zip', '.tar.gz', $originalZipUrl);

        $config->set('packager.skeleton', $tarGzUrl);
    }
}

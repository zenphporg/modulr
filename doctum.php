<?php

use Doctum\Doctum;
use Doctum\RemoteRepository\GitLabRemoteRepository;
use Doctum\Version\GitVersionCollection;
use Symfony\Component\Finder\Finder;

$dir = __DIR__.'/src';
$iterator = Finder::create()
  ->files()
  ->name('*.php')
  ->exclude('stubs')
  ->exclude('tests')
  ->exclude('vendor')
  ->in($dir);

$versions = GitVersionCollection::create($dir)
  ->addFromTags('v1.0.*', 'Modulr %version%')
  ->add('main', 'Modulr Main');

return new Doctum($iterator, [
  'versions' => $versions,
  'title' => 'Modulr API',
  'language' => 'en', // Could be 'fr'
  'build_dir' => __DIR__.'/api/%version%',
  'cache_dir' => __DIR__.'/cache/%version%',
  'source_dir' => dirname($dir).'/',
  'remote_repository' => new GitLabRemoteRepository('zenphp/modulr', $dir),
  'default_opened_level' => 2,
]);

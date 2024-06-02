<?php

use Doctum\Doctum;
use Doctum\RemoteRepository\GitLabRemoteRepository;
use Doctum\Version\GitVersionCollection;
use Symfony\Component\Finder\Finder;

$dir = __DIR__;
$iterator = Finder::create()
  ->files()
  ->name('*.php')
  ->exclude('stubs')
  ->exclude('tests')
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
  'default_opened_level' => 1,
  'footer_link' => [
    'href' => 'https://gitlab.com/zenphp/modulr',
    'rel' => 'noreferrer noopener',
    'target' => '_blank',
    'before_text' => 'You can edit the configuration',
    'link_text' => 'on this', // Required if the href key is set
    'after_text' => 'repository',
  ],
]);

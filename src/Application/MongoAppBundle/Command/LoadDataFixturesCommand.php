<?php

namespace Application\MongoAppBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\Output;
use Symfony\Component\Finder\Finder;
use Symfony\Bundle\FrameworkBundle\Util\Filesystem;
use Doctrine\Common\Cli\Configuration;
use Doctrine\Common\Cli\CliController as DoctrineCliController;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Internal\CommitOrderCalculator;

/*
 * This file is part of the Exercise.com framework.
 *
 * (c) Fabien Potencier <fabien.potencier@symfony-project.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

/**
 * Load data fixtures from bundles.
 *
 * @author     Fabien Potencier <fabien.potencier@symfony-project.com>
 * @author     Jonathan H. Wage <jonwage@gmail.com>
 * @author	   anon
 */
class LoadDataFixturesCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('app:data:load')
            ->setDescription('Load data fixtures to your database.')
            ->addOption('fixtures', null, InputOption::PARAMETER_OPTIONAL | InputOption::PARAMETER_IS_ARRAY, 'The directory or file to load data fixtures from.')
            ->addOption('append', null, InputOption::PARAMETER_OPTIONAL, 'Whether or not to append the data fixtures.', false)
            ->setHelp(<<<EOT
The <info>doctrine:data:load</info> command loads data fixtures from your bundles:

  <info>./symfony doctrine:data:load</info>

You can also optionally specify the path to fixtures with the <info>--fixtures</info> option:

  <info>./symfony doctrine:data:load --fixtures=/path/to/fixtures1 --fixtures=/path/to/fixtures2</info>

If you want to append the fixtures instead of flushing the database first you can use the <info>--append</info> option:

  <info>./symfony doctrine:data:load --append</info>
EOT
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $defaultDm = $this->container->getDoctrine_Odm_Mongodb_DefaultDocumentManagerService();
        $dirOrFile = $input->getOption('fixtures');
        if ($dirOrFile) {
            $paths = is_array($dirOrFile) ? $dirOrFile : array($dirOrFile);
        } else {
            $paths = array();
            $bundleDirs = $this->container->getKernelService()->getBundleDirs();
            foreach ($this->container->getKernelService()->getBundles() as $bundle) {
                $tmp = dirname(str_replace('\\', '/', get_class($bundle)));
                $namespace = str_replace('/', '\\', dirname($tmp));
                $class = basename($tmp);

                if (isset($bundleDirs[$namespace]) && is_dir($dir = $bundleDirs[$namespace].'/'.$class.'/Resources/data/fixtures/doctrine')) {
                    $paths[] = $dir;
                }
            }
        }

        $files = array();
        foreach ($paths as $path) {
            if (is_dir($path)) {
                $finder = new Finder();
                $found = iterator_to_array($finder
                    ->files()
                    ->name('*.php')
                    ->in($path));
            } else {
                $found = array($path);
            }
            $files = array_merge($files, $found);
        }

        $dm = array();
        $dmDocuments = array();
        $files = array_unique($files);
        $dmPurgeTracker = array();
        
        foreach ($files as $file) {
            $dm = $defaultDm;
            $output->writeln(sprintf('<info>Loading data fixtures from <comment>"%s"</comment></info>', $file));

            $before = array_keys(get_defined_vars());
            include($file);
            $after = array_keys(get_defined_vars());
            $new = array_diff($after, $before);
            $dmName = $dm->getMongo()->__toString();

            $dms[$dmName] = $dm;
            $dmDocuments[$dmName] = array();
            $variables = array_values($new);

            foreach ($variables as $variable) {
                $value = $$variable;
                if (!is_object($value) || $value instanceof \Doctrine\ORM\DocumentManager) {
                    continue;
                }
                $dmDocuments[$dmName][] = $value;
            }
            
            try {
	            foreach ($dms as $dmName => $dm) {
	                if (!$input->getOption('append')) {
	                    
	                    if (array_search($dmName, $dmPurgeTracker) === false) {
	                    	$output->writeln(sprintf('<info>Purging data from document manager named <comment>"%s"</comment></info>', $dmName));
	                    	$dmPurgeTracker[] = $dmName;
	                    	$this->purgeDocumentManager($dm);
	                    } else {
	                    	// It's been purged already
	                    	$output->writeln(sprintf('<info>No need to purging data from document manager named <comment>"%s"</comment></info>', $dmName));                    	
	                    }                    
	                }
	
	                $documents = $dmDocuments[$dmName];
	                $numDocuments = count($documents);
	                $output->writeln(sprintf('<info>Persisting "%s" '.($numDocuments > 1 ? 'documents' : 'document').'</info>', count($documents)));
	
	                foreach ($documents as $document) {
	                	try {
	                		$output->writeln(sprintf('<info>Persisting "%s" document:</info>', get_class($document)));
		                    $output->writeln('');
	                		$dm->persist($document);
	                		$dm->flush();
							//$output->writeln(var_dump($document));
		
	                	} catch (Exception $e) {
	                		$output->writeln('<info>Error:foreach documents</info>');
	                		$e = null;
	                	}
	                }
	                $output->writeln('<info>Flushing document manager</info>');
	                
	            }
            	
            } catch (Exception $e) {
	            $output->writeln('<info>Error:foreach dms</info>');
            	$e = null;
            }
        }
    }

    protected function purgeDocumentManager(DocumentManager $dm)
    {
        $classes = array();
        $metadatas = $dm->getMetadataFactory()->getAllMetadata();

        foreach ($metadatas as $metadata) {
            if (!$metadata->isMappedSuperclass) {
                $classes[] = $metadata;
            }
        }
        $cmf = $dm->getMetadataFactory();
        $classes = $this->getCommitOrder($dm, $classes);
        for ($i = count($classes) - 1; $i >= 0; --$i) {
            $class = $classes[$i];
            if ($cmf->hasMetadataFor($class->name)) {
                try {
                    $dm->query('remove '.$class->name)->execute();
                } catch (Exception $e) {}
            }
        }
    }

    protected function getCommitOrder(DocumentManager $dm, array $classes)
    {
        $calc = new CommitOrderCalculator;

        foreach ($classes as $class) {
            $calc->addClass($class);

            foreach ($class->fieldMappings as $field) {
                if(isset($field['reference']) || isset($field['embedded'])) {
                    $targetDocumentName = $field['targetDocument'];
                    $targetClass = $dm->getClassMetadata($targetDocumentName);

                    if (!$calc->hasClass($targetClass->name)) {
                        $calc->addClass($targetClass);
                    }

                    // add dependency ($targetClass before $class)
                    $calc->addDependency($targetClass, $class);
                }
            }
        }

        return $calc->getCommitOrder();
    }
}

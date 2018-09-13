<?php

namespace TheCocktail\EzThumbnailGeneratorBundle\Command;

use eZ\Publish\API\Repository\Repository;
use eZ\Publish\API\Repository\Values\Content\Content;
use eZ\Publish\API\Repository\Values\Content\Query;
use eZ\Publish\Core\Base\Exceptions\NotFoundException;
use eZ\Publish\Core\Repository\SearchService;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ThumbnailGeneratorCommand extends ContainerAwareCommand
{
    private $repository;

    public function __construct(Repository $repository, $name = null)
    {
        parent::__construct($name);
        $this->repository = $repository;
    }

    public function configure()
    {
        $this
            ->setName('tck:ez:generate-thumbnails')
            ->setDescription('Generate Thumbnails for the given contentType, field and alias')
            ->addArgument('contentType', InputArgument::REQUIRED, 'Enter contentType')
            ->addArgument('alias', InputArgument::REQUIRED, 'Alias to create, coma separated, no spaces')
            ->addArgument('fieldNames', InputArgument::IS_ARRAY, 'Enter field name to work with')
        ;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     * @throws \eZ\Publish\API\Repository\Exceptions\NotFoundException
     * @throws \Exception
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $contentType = $input->getArgument('contentType');

        if (!$this->checkContentType($contentType)) {
            $output->writeln("El contentType {$contentType} no existe.");
            exit;
        }

        $fieldNames = $input->getArgument('fieldNames');

        $alias = $input->getArgument('alias');

        $repository = $this->repository;

        $originalImages = $repository->sudo(function ($repository) use ($contentType, $fieldNames) {
            $query = new Query();
            $query->limit = PHP_INT_MAX;
            $query->filter = new Query\Criterion\ContentTypeIdentifier($contentType);

            /** @var SearchService $searchService */
            $searchService = $repository->getSearchService();

            $contents = $searchService->findContent($query);

            $originalImages = [];
            foreach ($contents->searchHits as $content) {
                /** @var Content $family */
                $family = $content->valueObject;
                foreach ($fieldNames as $fieldName) {
                    $originalImage = $family->getFieldValue($fieldName);
                    if ($originalImage->id) {
                        $originalImages[] = $originalImage->id;
                    }
                }
            }

            return $originalImages;
        });

        $liipCommand = $this->getApplication()->find('liip:imagine:cache:resolve');

        $arguments = array(
            'command' => 'liip:imagine:cache:resolve',
            'paths' => $originalImages,
            '--filters' => explode(',', $alias),
        );

        $liipCommandInput = new ArrayInput($arguments);
        $liipCommand->run($liipCommandInput, $output);
    }

    /**
     * @param $contentType
     * @return bool
     * @throws \eZ\Publish\API\Repository\Exceptions\NotFoundException
     */
    private function checkContentType($contentType)
    {
        try {
            $this->repository->getContentTypeService()->loadContentTypeByIdentifier($contentType);
        } catch (NotFoundException $exception) {
            return false;
        }

        return true;
    }
}

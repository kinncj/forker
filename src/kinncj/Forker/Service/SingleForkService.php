<?php
namespace kinncj\Forker\Service;

use kinncj\Forker\Repository\RepositoryInterface;

class SingleForkService extends CollectionForkService
{
    protected $repositoryName;

    public function __construct(RepositoryInterface $repository, $repositoryName)
    {
        parent::__construct($repository);

        $this->repositoryName = $repositoryName;
    }

    /**
     * (non-PHPdoc)
     * @see \kinncj\Forker\Service\CollectionForkService::getRepositoryList()
     */
    protected function getRepositoryList()
    {
        return array($this->repository->find($this->repositoryName));
    }
}

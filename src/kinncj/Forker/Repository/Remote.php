<?php
namespace kinncj\Forker\Repository;

use kinncj\Forker\Repository\RepositoryInterface;

use Github\Client;

class Remote implements RepositoryInterface
{
    protected $target;
    protected $client;

    /**
     *
     * @param string         $target
     * @param \Github\Client $client
     */
    public function __construct($target, Client $client)
    {
        $this->target = $target;
        $this->client = $client;
    }

    /**
     * (non-PHPdoc)
     * @see \kinncj\Forker\Repository\RepositoryInterface::find()
     */
    public function find($data)
    {
        return $this->client
            ->api('repository')
            ->show($this->target, $data);
    }

    /**
     * (non-PHPdoc)
     * @see \kinncj\Forker\Repository\RepositoryInterface::fork()
     */
    public function fork($data)
    {
        return $this->client
            ->api('repository')
            ->forks()
            ->create(
                $this->target,
                $data
            );
    }

    /**
     * (non-PHPdoc)
     * @see \kinncj\Forker\Repository\RepositoryInterface::findAll()
     */
    public function findAll()
    {
        return $this->client
            ->api('user')
            ->repositories($this->target);
    }
}

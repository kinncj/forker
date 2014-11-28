<?php
namespace kinncj\Forker\Repository;

interface RepositoryInterface
{
    /**
     * @return array
     */
    public function findAll();

    /**
     *
     * @param mixed $data
     *
     * @return array
     */
    public function find($data);

    /**
     *
     * @param mixed $data
     *
     * @return bool
     */
    public function fork($data);
}
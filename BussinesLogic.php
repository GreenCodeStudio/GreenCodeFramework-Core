<?php
/**
 * Created by PhpStorm.
 * User: matri
 * Date: 14.07.2018
 * Time: 14:35
 */

namespace Core;


class BussinesLogic
{
    /* @var Repository */
    protected $defaultDB = null;

    /**
     * @param int $id
     * @return |null
     * @throws \Exception
     */
    public function getById(int $id)
    {
        if ($this->defaultDB === null)
            throw new \Exception('not implemented');
        return $this->defaultDB->getById($id);
    }


}
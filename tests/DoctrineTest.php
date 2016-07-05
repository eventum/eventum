<?php

/*
 * This file is part of the Eventum (Issue Tracking System) package.
 *
 * @copyright (c) Eventum Team
 * @license GNU General Public License, version 2 or later (GPL-2+)
 *
 * For the full copyright and license information,
 * please see the COPYING and AUTHORS files
 * that were distributed with this source code.
 */

// bootstrap.php
use Eventum\Db\Doctrine;

class DoctrineTest extends PHPUnit_Framework_TestCase
{
    public function test1()
    {
        $productRepository = $this->getEntityManager()->getRepository(Eventum\Doctrine\Product::class);
        $products = $productRepository->findAll();

        /**
         * @var Eventum\Doctrine\Product $product
         */
        foreach ($products as $product) {
            echo sprintf("-%s\n", $product->getName());
        }
    }

    public function test2()
    {
        $em = $this->getEntityManager();
        $repo = $em->getRepository(\Eventum\Model\Entity\Commit::class);
        $items = $repo->findBy([], null, 10);

        /**
         * @var \Eventum\Model\Entity\Commit $item
         */
        foreach ($items as $item) {
            echo sprintf("* %s %s\n", $item->getId(), trim($item->getMessage()));
        }
    }

    private function getEntityManager()
    {
        return Doctrine::getEntityManager();
    }
}

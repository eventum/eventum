<?php

namespace Eventum\Model\Repository;

use Doctrine\ORM\EntityRepository;
use Eventum\Model\Entity;

class UserRepository extends EntityRepository
{
    /**
     * Method used to get the user ID associated with the given customer
     * contact ID.
     *
     * @param   int $customer_contact_id The customer contact ID
     * @return  int The user ID
     */
    public function findByContactId($customer_contact_id)
    {
        $repo = $this->getEntityManager()->getRepository(Entity\User::class);
        return $repo->findOneByCustomerContactId($customer_contact_id);
    }
}

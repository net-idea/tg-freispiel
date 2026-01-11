<?php

declare(strict_types=1);

namespace App\Tests\Repository;

use App\Entity\FormContactEntity;
use App\Entity\FormSubmissionMetaEntity;
use App\Tests\DatabaseTestCase;

class FormContactRepositoryTest extends DatabaseTestCase
{
    public function testPersistAndQueryOrderByCreatedAt(): void
    {
        $older = new FormContactEntity();
        $older->setName('Alice')->setEmailAddress('alice@example.com')->setMessage('Hi')->setConsent(true)->setCopy(true);
        $older->setMeta((new FormSubmissionMetaEntity())->setIp('1.2.3.4')->setUserAgent('UA1')->setTime(date('c'))->setHost('localhost'));

        // Sleep to ensure different createdAt
        usleep(50000);

        $newer = new FormContactEntity();
        $newer->setName('Bob')->setEmailAddress('bob@example.com')->setMessage('Hello')->setConsent(true)->setCopy(false);
        $newer->setMeta((new FormSubmissionMetaEntity())->setIp('5.6.7.8')->setUserAgent('UA2')->setTime(date('c'))->setHost('localhost'));

        $em = $this->getEntityManager();
        $em->persist($older);
        $em->persist($newer);
        $em->flush();

        $repo = $em->getRepository(FormContactEntity::class);
        $rows = $repo->createQueryBuilder('c')
            ->orderBy('c.createdAt', 'DESC')
            ->addOrderBy('c.id', 'DESC')
            ->getQuery()->getResult();

        self::assertCount(2, $rows);
        self::assertSame('Bob', $rows[0]->getName());
        self::assertSame('Alice', $rows[1]->getName());
    }
}

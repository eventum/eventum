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

namespace Monolog\Handler {
    function mail(): void
    {
        global $mail;
        $mail[] = func_get_args();
    }
}

namespace Eventum\Test\Mail {

    use Ds\Set;
    use Eventum\Monolog\MailHandler;
    use Eventum\ServiceContainer;
    use Eventum\Test\TestCase;
    use Monolog\Handler\HandlerInterface;
    use Psr\Log\LoggerInterface;

    /**
     * @group mail
     */
    class MailHandlerTest extends TestCase
    {
        public function testMailHandler(): void
        {
            $logger = $this->configureMailHandler('enabled');
            $logger->error('error');

            global $mail;
            $this->assertCount(1, $mail);
        }

        public function testMailHandlerDisabled(): void
        {
            $logger = $this->configureMailHandler('disabled');
            $logger->error('error');

            global $mail;
            $this->assertCount(0, $mail);
        }

        private function configureMailHandler(string $status): LoggerInterface
        {
            global $mail;
            $mail = [];

            $setup = ServiceContainer::getConfig();
            $setup['email_error']['status'] = $status;
            $setup['email_error']['addresses'] = 'root@localhost';
            $setup['smtp']['from'] = 'root@locahost';

            $container = ServiceContainer::getKernel()->getContainer();
            $logger = $container->get(LoggerInterface::class);

            $handlers = new Set($logger->getHandlers());
            /** @var MailHandler $handler */
            $handler = $handlers->filter(function (HandlerInterface $handler): bool {
                return $handler instanceof MailHandler;
            })->first();

            $handler->setTo($setup['email_error']['addresses']);

            return $logger;
        }
    }
}

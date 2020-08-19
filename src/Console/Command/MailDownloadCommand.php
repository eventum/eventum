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

namespace Eventum\Console\Command;

use Doctrine\ORM\EntityNotFoundException;
use Email_Account;
use Eventum\ConcurrentLock;
use Eventum\Db\Doctrine;
use Eventum\Logger\LoggerTrait;
use Eventum\Mail\Imap\ImapConnection;
use Eventum\Mail\MailDownLoader;
use Eventum\Mail\ProcessMailMessage;
use Eventum\Model\Entity\EmailAccount;
use InvalidArgumentException;
use LimitIterator;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

class MailDownloadCommand extends SymfonyCommand
{
    use LoggerTrait;

    public const DEFAULT_COMMAND = 'mail:download';
    public const USAGE = self::DEFAULT_COMMAND . ' [username] [hostname] [mailbox] [--limit=] [--no-lock]';

    protected static $defaultName = 'eventum:' . self::DEFAULT_COMMAND;

    /**
     * Limit amount of emails to process.
     * Default unlimited: 0
     *
     * @var int
     */
    private $limit = 0;

    public function __construct(LoggerInterface $logger = null)
    {
        $logger = $logger ?: $this->getLogger();
        $this->logger = $logger;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('username', InputArgument::REQUIRED)
            ->addArgument('hostname', InputArgument::REQUIRED)
            ->addArgument('mailbox', InputArgument::REQUIRED)
            ->addOption('no-lock', null, InputOption::VALUE_NONE, 'Skip application concurrency locking')
            ->addOption('limit', 'l', InputOption::VALUE_REQUIRED, 'Process at most LIMIT mail a time');
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $username = $input->getArgument('username');
        $hostname = $input->getArgument('hostname');
        $mailbox = $input->getArgument('mailbox');
        $noLock = $input->getOption('no-lock');
        $limit = $input->getOption('limit');

        $this($username, $hostname, $mailbox, $noLock, $limit);

        return 0;
    }

    public function __invoke(?string $username, ?string $hostname, ?string $mailbox, ?bool $noLock, ?int $limit): void
    {
        $account = $this->findAccount($hostname ?: '', $username ?: '', $mailbox);
        $account_id = $account->getId();
        $this->limit = $limit ?: 0;

        if (!$noLock) {
            $lock = new ConcurrentLock('download_emails_' . $account_id);
            $lock->synchronized(
                function () use ($account_id): void {
                    $this->processEmails($account_id);
                }
            );
        } else {
            $this->processEmails($account_id);
        }
    }

    private function processEmails(int $account_id): void
    {
        $account = Email_Account::getDetails($account_id, true);
        $mbox = new ImapConnection($account);
        $this->debug("Processing {$mbox}");

        $downloader = new MailDownloader($mbox, $account);
        $processor = new ProcessMailMessage($mbox, $this->logger);
        $it = new LimitIterator($downloader->getMails(), 0, $this->limit ?: -1);

        foreach ($it as $resource) {
            try {
                $processor->process($resource);
            } catch (Throwable $e) {
                $this->error($e->getMessage(), ['account' => (string)$mbox, 'resource' => $resource, 'class' => get_class($e), 'e' => $e]);
                continue;
            }
        }
    }

    /**
     * Get email account from the parameters
     *
     * @throws InvalidArgumentException
     */
    private function findAccount(string $hostname, string $username, ?string $mailbox): EmailAccount
    {
        $repo = Doctrine::getEmailAccountRepository();
        try {
            return $repo->findByDSN($hostname, $username, $mailbox);
        } catch (EntityNotFoundException $e) {
            throw new InvalidArgumentException(
                "Could not find a email account with the parameter provided.\n" .
                'Please verify your email account settings and try again.'
            );
        }
    }
}

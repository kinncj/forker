<?php
namespace kinncj\Forker\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use kinncj\Forker\Repository\Remote;
use kinncj\Forker\Service\CollectionForkService;
use kinncj\Forker\Service\SingleForkService;

use Github\Client;
use Github\Exception\RuntimeException as GithubRuntimeException;

class Fork extends Command
{
    /**
     * (non-PHPdoc)
     * @see \Symfony\Component\Console\Command\Command::configure()
     */
    protected function configure()
    {
        $this
        ->setName('forker:fork')
        ->setDescription('Fork github repositories')
        ->addOption('all', 'a', InputOption::VALUE_NONE, 'Clone all the repositories')
        ->addOption('username', 'u', InputOption::VALUE_OPTIONAL, 'Your github username')
        ->addOption('password', 'p', InputOption::VALUE_OPTIONAL, 'Your github password')
        ->addArgument('target', InputArgument::REQUIRED, 'The target username')
        ->addArgument('repository', InputArgument::OPTIONAL, 'Clone a specific repository', false);
    }

    /**
     * (non-PHPdoc)
     * @see \Symfony\Component\Console\Command\Command::execute()
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $dialog = $this->getHelper('dialog');

        $username  = $input->getOption('username');
        if (! $username) {
            $username = $dialog->ask($output, 'Please enter your GitHub username: ');
        }

        $password  = $input->getOption('password');
        if (! $password) {
            $password = $dialog->askHiddenResponse($output, 'Please enter your GitHub password: ');
        }

        try {
            $client    = new Client();

            $client->authenticate($username, $password, Client::AUTH_HTTP_PASSWORD);

            $forkService          = $this->getForkService($input, $client);
            $forkedRepositoryList = $forkService->fork();
            $message              = $this->formatMessage($forkedRepositoryList);

        } catch (\InvalidArgumentException $exception) {
            $message = $exception->getMessage();

        } catch (GithubRuntimeException $exception) {
            $message = $exception->getMessage();
            $message = "<error>{$message}</error>";

        } finally {
            $output->writeln($message);
        }
    }

    /**
     *
     * @param \Symfony\Component\Console\Input\InputInterface $input
     *
     * @return \kinncj\Forker\Service\ForkServiceInterface
     *
     * @throws \InvalidArgumentException
     */
    protected function getForkService(InputInterface $input, Client $client)
    {
        $forkAll    = $input->getOption('all');
        $repository = $input->getArgument('repository');
        $target     = $input->getArgument('target');
        $remote     = new Remote($target, $client);

        if ( ! $forkAll && ! $repository) {
            throw new \InvalidArgumentException("<comment>You must provide a repository OR the --all option</comment>");
        }

        return ($forkAll ? new CollectionForkService($remote) : new SingleForkService($remote, $repository));
    }

    /**
     *
     * @param array $data
     * @return string
     */
    protected function formatMessage(array $data = array())
    {
        $message = "";

        if (count($data["success"]) > 0) {
            $message .= "\n\n<comment>Forked repositories<comment>\n";
            $message .= "<info>".implode("</info>\n<info>", $data["success"])."</info>\n";
        }

        if (count($data["error"]) > 0) {
            $message .= "\n\n<comment>Non-forked repositories<comment>\n";
            $message .= "<error>".implode("</error>\n<error>", $data["error"])."</error>\n";
        }

        return $message;
    }
}

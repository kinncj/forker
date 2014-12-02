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
            $forkServiceReponse   = $forkService->fork();

            $this->displaySuccess($output, $forkServiceReponse['success']);
            $this->displayFailure($output, $forkServiceReponse['error']);
        } catch (GithubRuntimeException $githubRuntimeException) {
            $output->writeln('<error>' . $githubRuntimeException->getMessage() . '</error>');
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

        if (! $forkAll && ! $repository) {
            throw new \InvalidArgumentException("<comment>You must provide a repository OR the --all option</comment>");
        }

        return ($forkAll ? new CollectionForkService($remote) : new SingleForkService($remote, $repository));
    }

    /**
     * @param  OutputInterface $output
     * @param  array[]         $repositories
     * @return null
     */
    protected function displaySuccess(OutputInterface $output, array $repositories)
    {
        if (empty($repositories)) {
            return;
        }

        $output->writeln('<comment>Forked repositories</comment>');
        foreach ($repositories as $repositoryName => $repositoryInfo) {
            $output->writeln(
                sprintf(
                    '- %s: <info>%s</info>',
                    $repositoryName,
                    $repositoryInfo['full_name']
                )
            );
        }
    }

    /**
     * @param  OutputInterface $output
     * @param  Exception[]     $repositories
     * @return null
     */
    protected function displayFailure(OutputInterface $output, array $repositories)
    {
        if (empty($repositories)) {
            return;
        }

        $output->writeln('<comment>Non-forked repositories</comment>');
        foreach ($repositories as $repositoryName => $exception) {
            $output->writeln(
                sprintf(
                    '- %s: <error>%s</error>',
                    $repositoryName,
                    $exception->getMessage()
                )
            );
        }
    }
}

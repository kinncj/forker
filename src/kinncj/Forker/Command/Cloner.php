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

class Cloner extends Command
{
    /**
     * (non-PHPdoc)
     * @see \Symfony\Component\Console\Command\Command::configure()
     */
    protected function configure()
    {
        $this
        ->setName('forker:clone')
        ->setDescription('Clone github repositories')
        ->addOption('all', 'a', InputOption::VALUE_NONE, 'Fork all the repositories')
        ->addOption('username', 'u', InputOption::VALUE_OPTIONAL, 'Your github username')
        ->addOption('password', 'p', InputOption::VALUE_OPTIONAL, 'Your github password')
        ->addOption('url', 'url', InputOption::VALUE_OPTIONAL, 'url')
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

        $url = $input->getOption('url');

        try {
            $client = new Client();

            if ($url) {
                $client->setEnterpriseUrl($url);
            }

            $client->authenticate($username, $password, Client::AUTH_HTTP_PASSWORD);

            $repositoryList = $this->getRepositoryList($input, $client);

            $this->cloneRepositories($output, $repositoryList);
        } catch (GithubRuntimeException $githubRuntimeException) {
            $output->writeln('<error>' . $githubRuntimeException->getMessage() . '</error>');
        }
    }

    /**
     *
     * @param \Symfony\Component\Console\Input\InputInterface $input
     *
     * @return array
     *
     * @throws \InvalidArgumentException
     */
    protected function getRepositoryList(InputInterface $input, Client $client)
    {
        $forkAll    = $input->getOption('all');
        $repository = $input->getArgument('repository');
        $target     = $input->getArgument('target');
        $remote     = new Remote($target, $client);

        if (! $forkAll && ! $repository) {
            throw new \InvalidArgumentException("<comment>You must provide a repository OR the --all option</comment>");
        }

        return $forkAll ? $remote->findAll() : [$repository];
    }

    /**
     * @param  array[] $repositories
     * @return null
     */
    protected function cloneRepositories($output, array $repositories)
    {
        foreach ($repositories as $repositoryInfo) {

            $forkUrl        = $repositoryInfo['html_url'];
            $repositoryName = $repositoryInfo['name'];

            shell_exec(sprintf('git clone %s %s', escapeshellarg($forkUrl), escapeshellarg($repositoryName)));
            chdir(getcwd() . '/' . $repositoryName);
            chdir(getcwd() . '/..');
        }
    }
}

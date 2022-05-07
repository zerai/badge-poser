<?php

namespace App\Badge\Infrastructure\GitDataProvider;

use App\Badge\Exception\RepositoryDataNotValid;
use App\Badge\Exception\SourceClientNotFound;
use App\Badge\Service\DefaultBranchProviderInterface;
use App\Badge\ValueObject\Repository;
use Github\Api\Repo;
use Github\Client as GithubClient;

final class GitHubDataProvider implements DefaultBranchProviderInterface
{
    private const SUPPORTED_GIT_HOSTING_SERVICE = 'github.com';

    public function __construct(private GithubClient $githubClient)
    {
    }

    public function getDefaultBranch(Repository $repository): string
    {
        $defaultBranch = '';

        $username = $repository->getUsername();
        $repositoryName = $repository->getName();

        if (!$repository->isSupported()) {
            throw new SourceClientNotFound('Source Client '.$repository->getSource().' not found');
        }

        if ($repository->isGitHub()) {
            /** @var Repo $repoApi */
            $repoApi = $this->githubClient->api('repo');
            $repoGitHubData = $repoApi->show($username, $repositoryName);
            if (!$this->isValidGithubRepository($repoGitHubData)) {
                throw new RepositoryDataNotValid('Repository data not valid: '.\json_encode($repoGitHubData));
            }

            $defaultBranch = (string) $repoGitHubData['default_branch'];
        }

        return $defaultBranch;
    }

    /**
     * @param array<mixed> $repoGitHubData
     */
    private function isValidGithubRepository(array $repoGitHubData): bool
    {
        return !empty($repoGitHubData)
            && \array_key_exists('default_branch', $repoGitHubData)
            && \is_string($repoGitHubData['default_branch']);
    }

    public function supportedGitHostingService(): string
    {
        return self::SUPPORTED_GIT_HOSTING_SERVICE;
    }
}

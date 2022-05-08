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
        if (!$repository->isGitHub()) {
            //TODO ??? add or rename  exception UnsupportedGitService|UnsupportedGitHostingServiceProvider
            // it's a business/domain decision, SourceClientNotFound = tech/ifra exception
            throw new SourceClientNotFound('Source Client '.$repository->getSource().' not found');
        }

        /** @var Repo $repoApi */
        $repoApi = $this->githubClient->api('repo');
        $repoGitHubData = $repoApi->show($repository->getUsername(), $repository->getName());
        if (!$this->isValidRepository($repoGitHubData)) {
            throw new RepositoryDataNotValid('Repository data not valid: '.\json_encode($repoGitHubData));
        }

        return (string) $repoGitHubData['default_branch'];
    }

    /**
     * @param array<mixed> $repoGitHubData
     */
    private function isValidRepository(array $repoGitHubData): bool
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

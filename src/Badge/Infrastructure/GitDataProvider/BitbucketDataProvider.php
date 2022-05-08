<?php

namespace App\Badge\Infrastructure\GitDataProvider;

use App\Badge\Exception\RepositoryDataNotValid;
use App\Badge\Exception\SourceClientNotFound;
use App\Badge\Service\DefaultBranchProviderInterface;
use App\Badge\ValueObject\Repository;
use Bitbucket\Client as BitbucketClient;

final class BitbucketDataProvider implements DefaultBranchProviderInterface
{
    private const SUPPORTED_GIT_HOSTING_SERVICE = 'bitbucket.org';

    public function __construct(private BitbucketClient $bitbucketClient)
    {
    }

    public function getDefaultBranch(Repository $repository): string
    {
        if (!$repository->isBitbucket()) {
            //TODO ??? add or rename  exception UnsupportedGitService|UnsupportedGitHostingServiceProvider
            // it's a business/domain decision, SourceClientNotFound = tech/ifra exception
            throw new SourceClientNotFound('Source Client '.$repository->getSource().' not found');
        }

        $repoBitbucketData = $this->bitbucketClient
            ->repositories()
            ->workspaces($repository->getUsername())
            ->show($repository->getName());

        if (!$this->isValidRepository($repoBitbucketData)) {
            throw new RepositoryDataNotValid('Repository data not valid: '.\json_encode($repoBitbucketData));
        }

        return (string) $repoBitbucketData['mainbranch']['name'];
    }

    /**
     * @param array<mixed> $repoBitbucketData
     */
    private function isValidRepository(array $repoBitbucketData): bool
    {
        return !empty($repoBitbucketData)
            && \array_key_exists('mainbranch', $repoBitbucketData)
            && \array_key_exists('name', $repoBitbucketData['mainbranch'])
            && \is_string($repoBitbucketData['mainbranch']['name']);
    }

    public function supportedGitHostingService(): string
    {
        return self::SUPPORTED_GIT_HOSTING_SERVICE;
    }
}

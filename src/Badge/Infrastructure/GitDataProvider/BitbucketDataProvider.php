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
        // TODO: Implement getDefaultBranch() method.
        $defaultBranch = '';

        $username = $repository->getUsername();
        $repositoryName = $repository->getName();

        if (!$repository->isSupported()) {
            throw new SourceClientNotFound('Source Client '.$repository->getSource().' not found');
        }

        if ($repository->isBitbucket()) {
            $repoBitbucketData = $this->bitbucketClient
                ->repositories()
                ->workspaces($username)
                ->show($repositoryName);

            if (!$this->isValidBitbucketRepository($repoBitbucketData)) {
                throw new RepositoryDataNotValid('Repository data not valid: '.\json_encode($repoBitbucketData));
            }

            $defaultBranch = (string) $repoBitbucketData['mainbranch']['name'];
        }

        return $defaultBranch;
    }

    /**
     * @param array<mixed> $repoBitbucketData
     */
    private function isValidBitbucketRepository(array $repoBitbucketData): bool
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

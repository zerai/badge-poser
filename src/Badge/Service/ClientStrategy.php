<?php

declare(strict_types=1);

namespace App\Badge\Service;

use App\Badge\Exception\SourceClientNotFound;
use App\Badge\ValueObject\Repository;
use Http\Client\Exception;

final class ClientStrategy
{
    private const GITHUB_REPOSITORY_PREFIX = 'blob';
    private const BITBUCKET_REPOSITORY_PREFIX = 'src';

    private array $defaultBranchProviders;

    public function __construct(DefaultBranchProviderInterface ...$defaultBranchProviders)
    {
        $this->defaultBranchProviders = $defaultBranchProviders;
    }

    /**
     * @throws SourceClientNotFound
     * @throws Exception
     */
    public function getDefaultBranch(Repository $repository): string
    {
        if (!$repository->isSupported()) {
            //TODO ??? add or rename  exception UnsupportedGitService|UnsupportedGitHostingServiceProvider
            // it's a business/domain decision, SourceClientNotFound = tech/ifra exception
            throw new SourceClientNotFound('Source Client '.$repository->getSource().' not found');
        }

        $defaultBranch = '';

        foreach ($this->defaultBranchProviders as $provider) {
            if ($repository->getSource() === $provider->supportedGitHostingService()) {
                $defaultBranch = $provider->getDefaultBranch($repository);
                break;
            }
        }

        return $defaultBranch;
    }

    public function getRepositoryPrefix(Repository $repository, string $repoUrl): string
    {
        $repositoryPrefixUrl = '';

        if (!$repository->isSupported()) {
            throw new SourceClientNotFound('Source Client '.$repository->getSource().' not found');
        }

        if ($repository->isGitHub()) {
            $repositoryPrefixUrl = $repoUrl.'/'.self::GITHUB_REPOSITORY_PREFIX;
        }

        if ($repository->isBitbucket()) {
            $repositoryPrefixUrl = \str_replace(
                'https://bitbucket.org',
                'https://api.bitbucket.org/2.0/repositories',
                $repoUrl
            );

            $repositoryPrefixUrl .= '/'.self::BITBUCKET_REPOSITORY_PREFIX;
        }

        return $repositoryPrefixUrl;
    }
}

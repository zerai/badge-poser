<?php

declare(strict_types=1);

namespace App\Tests\Badge\Service;

use App\Badge\Exception\SourceClientNotFound;
use App\Badge\Infrastructure\GitDataProvider\BitbucketDataProvider;
use App\Badge\Infrastructure\GitDataProvider\GitHubDataProvider;
use App\Badge\Service\ClientStrategy;
use App\Badge\ValueObject\Repository;
use Bitbucket\Api\Repositories;
use Bitbucket\Api\Repositories\Workspaces;
use Bitbucket\Client as BitbucketClient;
use Github\Api\Repo;
use Github\Client as GithubClient;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class ClientStrategyTest extends TestCase
{
    /**
     * @var GithubClient|MockObject
     */
    private $githubClient;

    /**
     * @var BitbucketClient|MockObject
     */
    private $bitbucketClient;

    private ClientStrategy $clientStrategy;

    private string $username;

    private string $repositoryName;

    protected function setUp(): void
    {
        parent::setUp();
        $this->githubClient = $this->getMockBuilder(GithubClient::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->bitbucketClient = $this->getMockBuilder(BitbucketClient::class)
            ->disableOriginalConstructor()
            ->getMock();

        $githubDataProvider = new GitHubDataProvider($this->githubClient);

        $bitbucketDataProvider = new BitbucketDataProvider($this->bitbucketClient);

        $this->clientStrategy = new ClientStrategy($githubDataProvider, $bitbucketDataProvider);
        $this->username = 'username';
        $this->repositoryName = 'repositoryName';
    }

    public function testGetDefaultBranchMethodDelegationStrategyForGithub(): void
    {
        $expectedDefaultBranch = 'masterGithub';

        $apiInterface = $this->getMockBuilder(Repo::class)
            ->disableOriginalConstructor()
            ->getMock();
        $apiInterface->expects(self::once())
            ->method('show')
            ->with($this->username, $this->repositoryName)
            ->willReturn([
                'default_branch' => $expectedDefaultBranch,
            ]);

        $this->githubClient->expects(self::once())
            ->method('api')
            ->with('repo')
            ->willReturn($apiInterface);
        $source = 'github.com';

        $defaultBranch = $this->clientStrategy->getDefaultBranch(
            Repository::create($source, $this->username, $this->repositoryName)
        );

        self::assertSame($expectedDefaultBranch, $defaultBranch);
    }

    public function testGetDefaultBranchMethodDelegationStrategyForBitbucket(): void
    {
        $expectedDefaultBranch = 'masterBitbucket';

        $workspaces = $this->getMockBuilder(Workspaces::class)
            ->disableOriginalConstructor()
            ->getMock();
        $workspaces->expects(self::once())
            ->method('show')
            ->with($this->repositoryName)
            ->willReturn([
                'mainbranch' => [
                    'name' => $expectedDefaultBranch,
                ],
            ]);

        $repositories = $this->getMockBuilder(Repositories::class)
            ->disableOriginalConstructor()
            ->getMock();
        $repositories->expects(self::once())
            ->method('workspaces')
            ->with($this->username)
            ->willReturn($workspaces);

        $this->bitbucketClient->expects(self::once())
            ->method('repositories')
            ->willReturn($repositories);
        $source = 'bitbucket.org';
        $defaultBranch = $this->clientStrategy->getDefaultBranch(
            Repository::create($source, $this->username, $this->repositoryName)
        );

        self::assertSame($expectedDefaultBranch, $defaultBranch);
    }

    public function testThrowExceptionIfSourceClientIsNotFound(): void
    {
        $source = 'notManagedClient';

        $this->expectException(SourceClientNotFound::class);
        $this->expectExceptionMessage('Source Client notManagedClient not found');

        $this->clientStrategy->getDefaultBranch(
            Repository::create($source, $this->username, $this->repositoryName)
        );
    }

    public function testShouldGetGithubComposerLink(): void
    {
        $source = 'github.com';
        $repoUrl = 'https://github.com/user/repo';

        $composerLockLinkNormalized = $this->clientStrategy->getRepositoryPrefix(
            Repository::create($source, $this->username, $this->repositoryName),
            $repoUrl
        );

        self::assertEquals($repoUrl.'/blob', $composerLockLinkNormalized);
    }

    public function testShouldGetBitbucketComposerLink(): void
    {
        $source = 'bitbucket.org';
        $repoUrl = 'https://bitbucket.org/user/repo';

        $composerLockLinkNormalized = $this->clientStrategy->getRepositoryPrefix(
            Repository::create($source, $this->username, $this->repositoryName),
            $repoUrl
        );

        self::assertEquals('https://api.bitbucket.org/2.0/repositories/user/repo/src', $composerLockLinkNormalized);
    }

    public function testShouldThrowExceptionIfSourceNotFoundForGetComposerLockLinkNormalized(): void
    {
        $source = 'notManagedClient';
        $repoUrl = 'https://notManaged.com/user/repo';

        $this->expectException(SourceClientNotFound::class);
        $this->expectExceptionMessage('Source Client notManagedClient not found');

        $this->clientStrategy->getRepositoryPrefix(
            Repository::create($source, $this->username, $this->repositoryName),
            $repoUrl
        );
    }
}

<?php

namespace App\Tests\Badge\Infrastructure\GitDataProvider;

use App\Badge\Exception\RepositoryDataNotValid;
use App\Badge\Exception\SourceClientNotFound;
use App\Badge\Infrastructure\GitDataProvider\GitHubDataProvider;
use App\Badge\ValueObject\Repository;
use Github\Api\Repo;
use Github\Client as GithubClient;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class GitHubDataProviderTest extends TestCase
{
    /**
     * @var GithubClient|MockObject
     */
    private $githubClient;

    private GitHubDataProvider $gitHubDataProvider;

    private string $username;

    private string $repositoryName;

    protected function setUp(): void
    {
        parent::setUp();
        $this->githubClient = $this->getMockBuilder(GithubClient::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->gitHubDataProvider = new GitHubDataProvider($this->githubClient);
        $this->username = 'username';
        $this->repositoryName = 'repositoryName';
    }

    public function testSupportGithubAsHostingService(): void
    {
        self::assertSame('github.com', $this->gitHubDataProvider->supportedGitHostingService());
    }

    public function testGetDefaultBranchFromGithub(): void
    {
        $defaultBranch = 'masterGithub';

        $apiInterface = $this->getMockBuilder(Repo::class)
            ->disableOriginalConstructor()
            ->getMock();
        $apiInterface->expects(self::once())
            ->method('show')
            ->with($this->username, $this->repositoryName)
            ->willReturn([
                'default_branch' => $defaultBranch,
            ]);

        $this->githubClient->expects(self::once())
            ->method('api')
            ->with('repo')
            ->willReturn($apiInterface);
        $source = 'github.com';
        self::assertEquals($defaultBranch, $this->gitHubDataProvider->getDefaultBranch(
            Repository::create($source, $this->username, $this->repositoryName)
        ));
    }

    public function testThrowExceptionIfRepositoryIsNotGithub(): void
    {
        $source = 'notManagedService.com';

        $this->expectException(SourceClientNotFound::class);
        $this->expectExceptionMessage('Source Client notManagedService.com not found');

        $this->gitHubDataProvider->getDefaultBranch(
            Repository::create($source, $this->username, $this->repositoryName)
        );
    }

    public function testThrowExceptionIfEmptyGithubData(): void
    {
        $apiInterface = $this->getMockBuilder(Repo::class)
            ->disableOriginalConstructor()
            ->getMock();
        $apiInterface->expects(self::once())
            ->method('show')
            ->with($this->username, $this->repositoryName)->willReturn([]);

        $this->githubClient->expects(self::once())
            ->method('api')
            ->with('repo')
            ->willReturn($apiInterface);
        $source = 'github.com';

        $this->expectException(RepositoryDataNotValid::class);
        $this->expectExceptionMessage('Repository data not valid: []');

        $this->gitHubDataProvider->getDefaultBranch(
            Repository::create($source, $this->username, $this->repositoryName)
        );
    }

    public function testThrowExceptionIfNotExistDefaultBranchKeyIntoGithubRepository(): void
    {
        //self::markTestSkipped('Moved in GitHubDataProviderTest');
        $apiInterface = $this->getMockBuilder(Repo::class)
            ->disableOriginalConstructor()
            ->getMock();
        $apiInterface->expects(self::once())
            ->method('show')
            ->with($this->username, $this->repositoryName)
            ->willReturn([
                'foo' => 'bar',
            ]);

        $this->githubClient->expects(self::once())
            ->method('api')
            ->with('repo')
            ->willReturn($apiInterface);
        $source = 'github.com';

        $this->expectException(RepositoryDataNotValid::class);
        $this->expectExceptionMessage('Repository data not valid: {"foo":"bar"}');

        $this->gitHubDataProvider->getDefaultBranch(
            Repository::create($source, $this->username, $this->repositoryName)
        );
    }

    public function testThrowExceptionIfDefaultBranchKeyIsNotStringIntoGithubRepository(): void
    {
        $apiInterface = $this->getMockBuilder(Repo::class)
            ->disableOriginalConstructor()
            ->getMock();
        $apiInterface->expects(self::once())
            ->method('show')
            ->with($this->username, $this->repositoryName)
            ->willReturn([
                'foo' => ['bar'],
            ]);

        $this->githubClient->expects(self::once())
            ->method('api')
            ->with('repo')
            ->willReturn($apiInterface);
        $source = 'github.com';

        $this->expectException(RepositoryDataNotValid::class);
        $this->expectExceptionMessage('Repository data not valid: {"foo":["bar"]}');

        $this->gitHubDataProvider->getDefaultBranch(
            Repository::create($source, $this->username, $this->repositoryName)
        );
    }
}

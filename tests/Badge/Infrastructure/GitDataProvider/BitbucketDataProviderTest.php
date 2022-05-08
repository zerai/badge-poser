<?php

namespace App\Tests\Badge\Infrastructure\GitDataProvider;

use App\Badge\Exception\RepositoryDataNotValid;
use App\Badge\Infrastructure\GitDataProvider\BitbucketDataProvider;
use App\Badge\ValueObject\Repository;
use Bitbucket\Api\Repositories;
use Bitbucket\Api\Repositories\Workspaces;
use Bitbucket\Client as BitbucketClient;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class BitbucketDataProviderTest extends TestCase
{
    /**
     * @var BitbucketClient|MockObject
     */
    private $bitbucketClient;

    private BitbucketDataProvider $bitbucketDataProvider;

    private string $username;

    private string $repositoryName;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bitbucketClient = $this->getMockBuilder(BitbucketClient::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->bitbucketDataProvider = new BitbucketDataProvider($this->bitbucketClient);
        $this->username = 'username';
        $this->repositoryName = 'repositoryName';
    }

    public function testSupportGithubAsHostingService(): void
    {
        self::assertSame('bitbucket.org', $this->bitbucketDataProvider->supportedGitHostingService());
    }

    public function testGetDefaultBranchFromBitbucket(): void
    {
        $defaultBranch = 'masterBitbucket';

        $workspaces = $this->getMockBuilder(Workspaces::class)
            ->disableOriginalConstructor()
            ->getMock();
        $workspaces->expects(self::once())
            ->method('show')
            ->with($this->repositoryName)
            ->willReturn([
                'mainbranch' => [
                    'name' => $defaultBranch,
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
        self::assertEquals($defaultBranch, $this->bitbucketDataProvider->getDefaultBranch(
            Repository::create($source, $this->username, $this->repositoryName)
        ));
    }

    public function testThrowExceptionIfEmptyBitbucketData(): void
    {
        $workspaces = $this->getMockBuilder(Workspaces::class)
            ->disableOriginalConstructor()
            ->getMock();
        $workspaces->expects(self::once())
            ->method('show')
            ->with($this->repositoryName)
            ->willReturn([]);

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

        $this->expectException(RepositoryDataNotValid::class);
        $this->expectExceptionMessage('Repository data not valid: []');

        $this->bitbucketDataProvider->getDefaultBranch(
            Repository::create($source, $this->username, $this->repositoryName)
        );
    }

    public function testThrowExceptionIfThereIsNoKeyMainBranchBitbucketData(): void
    {
        $workspaces = $this->getMockBuilder(Workspaces::class)
            ->disableOriginalConstructor()
            ->getMock();
        $workspaces->method('show')
            ->with($this->repositoryName)
            ->willReturn([
                'foo' => 'bar',
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

        $this->expectException(RepositoryDataNotValid::class);
        $this->expectExceptionMessage('Repository data not valid: {"foo":"bar"}');

        $this->bitbucketDataProvider->getDefaultBranch(
            Repository::create($source, $this->username, $this->repositoryName)
        );
    }

    public function testThrowExceptionIfThereIsNoKeyNameBitbucketData(): void
    {
        $workspaces = $this->getMockBuilder(Workspaces::class)
            ->disableOriginalConstructor()
            ->getMock();
        $workspaces->expects(self::once())
            ->method('show')
            ->with($this->repositoryName)
            ->willReturn([
                'mainbranch' => ['bar'],
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

        $this->expectException(RepositoryDataNotValid::class);
        $this->expectExceptionMessage('Repository data not valid: {"mainbranch":["bar"]}');

        $this->bitbucketDataProvider->getDefaultBranch(
            Repository::create($source, $this->username, $this->repositoryName)
        );
    }

    public function testThrowExceptionIfThereIsNNameIsNotStringBitbucketData(): void
    {
        $workspaces = $this->getMockBuilder(Workspaces::class)
            ->disableOriginalConstructor()
            ->getMock();
        $workspaces->expects(self::once())
            ->method('show')
            ->with($this->repositoryName)
            ->willReturn([
                'mainbranch' => [
                    'name' => ['bar'],
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

        $this->expectException(RepositoryDataNotValid::class);
        $this->expectExceptionMessage('Repository data not valid: {"mainbranch":{"name":["bar"]}}');

        $this->bitbucketDataProvider->getDefaultBranch(
            Repository::create($source, $this->username, $this->repositoryName)
        );
    }
}

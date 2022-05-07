<?php

namespace App\Badge\Service;

use App\Badge\ValueObject\Repository;

interface DefaultBranchProviderInterface
{
    public function getDefaultBranch(Repository $repository): string;

    public function supportedGitHostingService(): string;
}

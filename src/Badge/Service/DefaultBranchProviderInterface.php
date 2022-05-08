<?php

namespace App\Badge\Service;

use App\Badge\ValueObject\Repository;

interface DefaultBranchProviderInterface
{
    // TODO trow exception
    public function getDefaultBranch(Repository $repository): string;

    // TODO trow exception
    public function supportedGitHostingService(): string;
}

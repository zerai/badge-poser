<?php

/*
 * This file is part of the badge-poser package.
 *
 * (c) PUGX <http://pugx.github.io/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller\Badge;

use App\Badge\Model\UseCase\CreateVersionBadge;
use App\Badge\Service\ImageFactory;
use PUGX\Poser\Poser;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class VersionController
 * Version action for badges.
 */
class VersionController extends AbstractBadgeController
{
    /**
     * Version action.
     *
     * @param string $repository repository
     * @param string $latest latest
     * @param string $format
     *
     * @throws \InvalidArgumentException
     */
    public function version(
        Request $request,
        Poser $poser,
        ImageFactory $imageFactory,
        CreateVersionBadge $createVersionBadge,
        $repository,
        $latest,
        $format = 'svg'
    ): Response
    {

        if (\in_array($request->query->get('format'), $poser->validStyles(), true)) {
            $format = $request->query->get('format');
        }

        $function = 'create' . \ucfirst($latest) . 'Badge';

        return $this->serveBadge(
            $imageFactory,
            $createVersionBadge->{$function}($repository, $format)
        );
    }
}

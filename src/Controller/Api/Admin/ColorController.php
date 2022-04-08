<?php

namespace App\Controller\Api\Admin;

use App\Controller\BaseController;
use FOS\RestBundle\Controller\Annotations as Rest;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Response;

/**
 * @IsGranted("ROLE_ADMIN")
 */
class ColorController extends BaseController
{
    /**
     * @Rest\Get("/colors")
     * @return Response
     */
    public function getColorsAction(): Response
    {
        $colors = $this->colorRepository->findBy(
            self::CONDITION_DEFAULT,
            ['name' => 'ASC']
        );
        $colors = $this->transferDataGroup($colors, 'getColorList');

        return $this->handleView($this->view($colors, Response::HTTP_OK));
    }
}

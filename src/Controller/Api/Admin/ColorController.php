<?php

namespace App\Controller\Api\Admin;

use App\Controller\BaseController;
use FOS\RestBundle\Controller\Annotations as Rest;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\SerializerBuilder;
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

    /**
     * @Rest\Get("/colors/{id}")
     * @param int $id
     * @return Response
     */
    public function getColorAction(int $id): Response
    {
        $color = $this->colorRepository->findOneBy(['id' => $id]);
        if (!$color) {
            return $this->handleView($this->view(
                ['error' => 'Category is not found.'],
                Response::HTTP_NOT_FOUND
            ));
        }
        $serializer = SerializerBuilder::create()->build();
        $convertToJson = $serializer->serialize($color, 'json', SerializationContext::create()->setGroups(array('getDetailColor')));
        $color = $serializer->deserialize($convertToJson, 'array', 'json');

        return $this->handleView($this->view($color, Response::HTTP_OK));
    }
}

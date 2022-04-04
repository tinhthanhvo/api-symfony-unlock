<?php

namespace App\Controller\Api\Admin;

use App\Repository\ColorRepository;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\SerializerBuilder;
use Symfony\Component\HttpFoundation\Response;
use FOS\RestBundle\Controller\Annotations as Rest;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

/**
 * @IsGranted("ROLE_ADMIN")
 */
class ColorController extends AbstractFOSRestController
{
    /**
     * @var ColorRepository
     */
    private $colorRepository;

    public function __construct(ColorRepository $colorRepository)
    {
        $this->colorRepository = $colorRepository;
    }

    /**
     * @Rest\Get("/colors")
     * @return Response
     */
    public function getColorsAction(): Response
    {
        $colors = $this->colorRepository->findBy(['deleteAt' => null], ['createAt' => 'DESC']);

        $serializer = SerializerBuilder::create()->build();
        $convertToJson = $serializer->serialize(
            $colors,
            'json',
            SerializationContext::create()->setGroups(array('getColorList'))
        );
        $transferData = $serializer->deserialize($convertToJson, 'array', 'json');
        return $this->handleView($this->view($transferData, Response::HTTP_OK));
    }
}

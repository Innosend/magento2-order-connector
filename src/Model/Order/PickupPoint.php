<?php
/**
 * Copyright (c) Falcon Media (info@falconmedia.nl)
 *
 * @author Falcon Media
 */

declare(strict_types=1);

namespace Innosend\OrderConnector\Model\Order;

use Innosend\PickupPoints\Model\Order\PickupPoint as PickupPointBase;

/**
 * Extended pickup point model with street, zipcode and city for order extension attributes.
 * Used when loading pickup point data in OrderRepositoryPlugin so all fields can be set.
 */
class PickupPoint extends PickupPointBase
{
    /**
     * @var string|null
     */
    private $pickupPointStreet;

    /**
     * @var string|null
     */
    private $pickupPointZipcode;

    /**
     * @var string|null
     */
    private $pickupPointCity;

    /**
     * @return string|null
     */
    public function getPickupPointStreet(): ?string
    {
        return $this->pickupPointStreet;
    }

    /**
     * @param string|null $street
     * @return \Innosend\PickupPoints\Api\Data\OrderPickupPointInterface
     */
    public function setPickupPointStreet(?string $street): \Innosend\PickupPoints\Api\Data\OrderPickupPointInterface
    {
        $this->pickupPointStreet = $street;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getPickupPointZipcode(): ?string
    {
        return $this->pickupPointZipcode;
    }

    /**
     * @param string|null $zipcode
     * @return \Innosend\PickupPoints\Api\Data\OrderPickupPointInterface
     */
    public function setPickupPointZipcode(?string $zipcode): \Innosend\PickupPoints\Api\Data\OrderPickupPointInterface
    {
        $this->pickupPointZipcode = $zipcode;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getPickupPointCity(): ?string
    {
        return $this->pickupPointCity;
    }

    /**
     * @param string|null $city
     * @return \Innosend\PickupPoints\Api\Data\OrderPickupPointInterface
     */
    public function setPickupPointCity(?string $city): \Innosend\PickupPoints\Api\Data\OrderPickupPointInterface
    {
        $this->pickupPointCity = $city;
        return $this;
    }
}

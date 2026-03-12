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
     * @return self
     */
    public function setPickupPointStreet(?string $street): self
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
     * @return self
     */
    public function setPickupPointZipcode(?string $zipcode): self
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
     * @return self
     */
    public function setPickupPointCity(?string $city): self
    {
        $this->pickupPointCity = $city;
        return $this;
    }
}

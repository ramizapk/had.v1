<?php

namespace App\Http\Controllers;

abstract class Controller
{
    protected $customerAvatarsPath;
    protected $deliveryAgentsPath;
    protected $categoriesPath;
    protected $advertisementsPath;
    protected $productImagesPath;

    protected $sectionsPath;
    protected $servicesPath;
    protected $serviceTypesPath;

    public function __construct()
    {
        $this->customerAvatarsPath = config('filesystems.customer_avatars');
        $this->deliveryAgentsPath = config('filesystems.delivery_agents');
        $this->advertisementsPath = config('filesystems.advertisements');
        $this->productImagesPath = config('filesystems.product_images');
        $this->sectionsPath = config('filesystems.sections');
        $this->servicesPath = config('filesystems.services');
        $this->serviceTypesPath = config('filesystems.service_types');

    }
}

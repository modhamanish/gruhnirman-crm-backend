<?php

namespace App\Http\Controllers\Api;

use OpenApi\Attributes as OA;

#[OA\Info(
    title: "Gruhnirman CRM API",
    version: "1.0.0",
    description: "API documentation for Gruhnirman CRM"
)]
#[OA\Server(
    url: "http://10.41.168.17:8000",
    description: "Local Server"
)]
#[OA\SecurityScheme(
    securityScheme: "bearerAuth",
    type: "http",
    scheme: "bearer",
    bearerFormat: "JWT"
)]
class Swagger {}

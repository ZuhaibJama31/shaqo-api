<?php

/**
 * @OA\Info(
 *      title="Shaqo API",
 *      version="1.0.0",
 *      description="Service Booking API for Clients, Workers and Admin"
 * )
 *
 * @OA\Server(
 *      url="http://127.0.0.1:8000/api",
 *      description="Local API Server"
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="sanctum",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT"
 * )
 */
class OpenApi {}
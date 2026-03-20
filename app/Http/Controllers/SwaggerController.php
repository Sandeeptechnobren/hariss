<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;

/**
 * @OA\Info(
 *     title="Riham V2 API",
 *     version="2.0.0",
 *     description="API documentation for Riham Version 2 project"
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT"
 * )
 *
 * @OA\Schema(
 *     schema="User",
 *     type="object",
 *     title="User",
 *     description="User model schema",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="firstname", type="string", example="Amit"),
 *     @OA\Property(property="lastname", type="string", example="Pathak"),
 *     @OA\Property(property="username", type="string", example="amit007"),
 *     @OA\Property(property="email", type="string", format="email", example="amit@example.com"),
 *     @OA\Property(property="role", type="integer", example=1),
 *     @OA\Property(property="status", type="integer", example=1)
 * )
 */
class SwaggerController extends Controller
{
   
}

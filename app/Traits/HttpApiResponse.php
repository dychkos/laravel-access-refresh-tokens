<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

trait HttpApiResponse
{

    public function successResponse($data = [], $message = 'Success.', $code = Response::HTTP_OK): JsonResponse
    {
        return response()->json([
            'status' => true,
            'data' => $data,
            'message' => $message
        ], $code);
    }

    public function errorResponse(
        $data = [],
        $message = 'Something went wrong.',
        $code = Response::HTTP_UNPROCESSABLE_ENTITY
    ): JsonResponse {
        return response()->json([
            'status' => false,
            'data' => $data,
            'message' => $message
        ], $code);
    }

    public function unauthorized(): JsonResponse
    {
        return $this->errorResponse(message: 'Unauthorized.', code: Response::HTTP_UNAUTHORIZED);
    }
}

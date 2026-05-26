<?php

namespace App\trait;

use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

trait ApiResponse
{
   public function SuccessResponse($data, $message, $status): JsonResponse
   {
       return response()->json([
           'data' => $data,
           'message' => $message,
           'status' => $status
       ], Response::HTTP_OK);
   }

   public function ErrorResponse($data, $message, $status):jsonResponse
   {
       return response()->json([
           'data' => $data,
           'message' => $message,
           'status' => $status
       ], Response::HTTP_INTERNAL_SERVER_ERROR);
   }

   public function AuthErrorResponse($data, $message, $status):jsonResponse
   {
       return response()->json([
           'data' => $data,
       ], $status);
   }
}

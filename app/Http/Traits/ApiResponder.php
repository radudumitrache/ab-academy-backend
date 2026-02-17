<?php

namespace App\Http\Traits;

trait ApiResponder
{
    /**
     * Return success response with data
     *
     * @param mixed $data
     * @param string $message
     * @param int $code
     * @return \Illuminate\Http\JsonResponse
     */
    protected function success($data = null, string $message = 'Operation successful', int $code = 200)
    {
        return response()->json([
            'message' => $message,
            'data' => $data,
        ], $code);
    }

    /**
     * Return error response
     *
     * @param string $message
     * @param int $code
     * @return \Illuminate\Http\JsonResponse
     */
    protected function error(string $message = 'An error occurred', int $code = 400)
    {
        return response()->json([
            'message' => $message,
        ], $code);
    }

    /**
     * Return not found response
     *
     * @param string $message
     * @return \Illuminate\Http\JsonResponse
     */
    protected function notFound(string $message = 'Resource not found')
    {
        return $this->error($message, 404);
    }

    /**
     * Return forbidden response
     *
     * @param string $message
     * @return \Illuminate\Http\JsonResponse
     */
    protected function forbidden(string $message = 'Access denied')
    {
        return $this->error($message, 403);
    }

    /**
     * Return validation error response
     * 
     * @param array $errors
     * @param string $message
     * @return \Illuminate\Http\JsonResponse
     */
    protected function validationError(array $errors, string $message = 'Validation failed')
    {
        return response()->json([
            'message' => $message,
            'errors' => $errors,
        ], 422);
    }
}

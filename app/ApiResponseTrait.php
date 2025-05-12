<?php

namespace App\Traits;

trait ApiResponseTrait
{
    /**
     * Generate a standardized success response
     *
     * Creates a consistent JSON response format for successful operations
     * with optional data, message customization, and HTTP status code.
     *
     * @param mixed $data Data to be returned in the response
     * @param string $message Success message to display to the user
     * @param int $statusCode HTTP status code (default 200)
     * @return \Illuminate\Http\JsonResponse
     */
    protected function successResponse($data = null, string $message = 'Operation successful', int $statusCode = 200)
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data
        ], $statusCode);
    }
    
    /**
     * Generate a standardized error response
     *
     * Creates a consistent JSON response format for failed operations
     * with detailed error information and appropriate HTTP status code.
     *
     * @param string $message Error message to display to the user
     * @param int $statusCode HTTP status code (default 400)
     * @param mixed $errors Additional error details (optional)
     * @return \Illuminate\Http\JsonResponse
     */
    protected function errorResponse(string $message = 'Operation failed', int $statusCode = 400, $errors = null)
    {
        $response = [
            'success' => false,
            'message' => $message
        ];
        
        if ($errors !== null) {
            $response['errors'] = $errors;
        }
        
        return response()->json($response, $statusCode);
    }
    
    /**
     * Handle exceptions in a standardized way
     *
     * Centralizes exception handling with appropriate status codes
     * and detailed error messages based on exception type.
     *
     * @param \Exception $e The exception to handle
     * @param string $defaultMessage Default message if none provided
     * @return \Illuminate\Http\JsonResponse
     */
    protected function handleException(\Exception $e, string $defaultMessage = 'An unexpected error occurred')
    {
        $statusCode = 500;
        $message = $e->getMessage() ?: $defaultMessage;
        $errors = null;
        
        // Determine appropriate status code based on exception type
        if ($e instanceof \Illuminate\Validation\ValidationException) {
            $statusCode = 422;
            $errors = $e->errors();
        } elseif ($e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException) {
            $statusCode = 404;
            $message = 'Resource not found';
        } elseif ($e instanceof \Illuminate\Auth\Access\AuthorizationException) {
            $statusCode = 403;
            $message = 'You are not authorized to perform this action';
        } elseif ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpException) {
            $statusCode = $e->getStatusCode();
        }
        
        // Log detailed error information for debugging
        if ($statusCode >= 500) {
            \Illuminate\Support\Facades\Log::error($e->getMessage(), [
                'exception' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
        }
        
        return $this->errorResponse($message, $statusCode, $errors);
    }
    
    /**
     * Generate a standardized validation error response
     *
     * Creates a consistent response format for validation failures
     * with detailed field-specific error messages.
     *
     * @param \Illuminate\Validation\Validator $validator The validator with errors
     * @return \Illuminate\Http\JsonResponse
     */
    protected function validationErrorResponse($validator)
    {
        return $this->errorResponse(
            'The given data was invalid',
            422,
            $validator->errors()
        );
    }
    
    /**
     * Generate a standardized not found response
     *
     * Creates a consistent response for resource not found errors
     * with customizable message.
     *
     * @param string $resource Type of resource that wasn't found
     * @return \Illuminate\Http\JsonResponse
     */
    protected function notFoundResponse(string $resource = 'Resource')
    {
        return $this->errorResponse(
            "{$resource} not found",
            404
        );
    }
    
    /**
     * Generate a standardized unauthorized response
     *
     * Creates a consistent response for authorization failures
     * with appropriate HTTP status code.
     *
     * @param string $message Custom unauthorized message
     * @return \Illuminate\Http\JsonResponse
     */
    protected function unauthorizedResponse(string $message = 'You are not authorized to perform this action')
    {
        return $this->errorResponse($message, 403);
    }
}
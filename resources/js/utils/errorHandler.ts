/**
 * Centralized error handler for API errors.
 *
 * Provides consistent error handling, user-friendly messages, and retry functionality
 * across all Vue components.
 */

export interface ApiErrorResponse {
    ok: false;
    code: string;
    status: number;
    message: string;
    data: unknown;
    meta: {
        source_status?: number;
        service?: string;
        error_message?: string;
        circuit_state?: string;
        retry_after?: number;
        [key: string]: unknown;
    };
}

export interface ApiSuccessResponse<T = unknown> {
    ok: true;
    code: string;
    status: number;
    message: string;
    data: T;
    meta: Record<string, unknown>;
}

export type ApiResponse<T = unknown> = ApiSuccessResponse<T> | ApiErrorResponse;

export interface ErrorHandlingOptions {
    /** Show error message to user (default: true) */
    showMessage?: boolean;
    /** Show retry button for transient errors (default: true) */
    showRetry?: boolean;
    /** Custom error message override */
    customMessage?: string;
    /** Callback when retry is requested */
    onRetry?: () => void;
    /** Callback after error is handled */
    onError?: (error: ApiErrorResponse) => void;
}

/**
 * Check if response is an error response.
 */
export function isErrorResponse(response: unknown): response is ApiErrorResponse {
    return (
        typeof response === 'object' &&
        response !== null &&
        'ok' in response &&
        response.ok === false
    );
}

/**
 * Check if error is a transient error that can be retried.
 */
export function isTransientError(error: ApiErrorResponse): boolean {
    const status = error.status;
    const sourceStatus = error.meta?.source_status;

    // Transient errors: 502, 503, 504, or 5xx from source
    return (
        status === 502 ||
        status === 503 ||
        status === 504 ||
        (typeof sourceStatus === 'number' && sourceStatus >= 500 && sourceStatus < 600)
    );
}

/**
 * Check if error is due to circuit breaker being open.
 */
export function isCircuitBreakerOpen(error: ApiErrorResponse): boolean {
    return (
        error.meta?.circuit_state === 'open' ||
        error.code.endsWith('.circuit_breaker_open')
    );
}

/**
 * Get user-friendly error message from error response.
 */
export function getUserFriendlyMessage(error: ApiErrorResponse, customMessage?: string): string {
    if (customMessage) {
        return customMessage;
    }

    // Circuit breaker open has specific message
    if (isCircuitBreakerOpen(error)) {
        return 'Service is temporarily unavailable. Please try again later.';
    }

    // Use message from API response if available
    if (error.message && error.message.length > 0) {
        return error.message;
    }

    // Fallback messages based on status code
    const status = error.status;
    switch (status) {
        case 502:
            return 'Unable to complete operation. The service may be temporarily unavailable.';
        case 503:
            return 'Service is temporarily unavailable. Please try again later.';
        case 504:
            return 'The request took too long to complete. Please try again.';
        case 401:
            return 'Authentication required. Please log in and try again.';
        case 403:
            return 'You do not have permission to perform this action.';
        case 404:
            return 'The requested resource was not found.';
        case 400:
            return 'Invalid request. Please check your input and try again.';
        default:
            return 'An error occurred while processing your request. Please try again.';
    }
}

/**
 * Extract error details from various error types.
 */
export function extractError(error: unknown): ApiErrorResponse | null {
    // Handle Axios error with response
    if (error && typeof error === 'object' && 'response' in error) {
        const axiosError = error as { response?: { data?: unknown } };
        if (axiosError.response?.data && isErrorResponse(axiosError.response.data)) {
            return axiosError.response.data;
        }
    }

    // Handle direct error response
    if (isErrorResponse(error)) {
        return error;
    }

    // Handle network errors (no response)
    if (error && typeof error === 'object' && 'message' in error) {
        const networkError = error as { message?: string };
        return {
            ok: false,
            code: 'network.error',
            status: 0,
            message: networkError.message || 'Network error occurred.',
            data: null,
            meta: {
                error_message: networkError.message || 'Network error',
            },
        };
    }

    // Unknown error format
    return null;
}

/**
 * Handle API error with user-friendly message and optional retry.
 *
 * @param error - Error object (Axios error, ApiErrorResponse, or unknown)
 * @param options - Error handling options
 * @returns Error details if successfully extracted, null otherwise
 */
export function handleApiError(
    error: unknown,
    options: ErrorHandlingOptions = {}
): ApiErrorResponse | null {
    const {
        showMessage = true,
        showRetry = true,
        customMessage,
        onRetry,
        onError,
    } = options;

    const errorResponse = extractError(error);

    if (!errorResponse) {
        console.error('Unable to extract error details:', error);
        return null;
    }

    const userMessage = getUserFriendlyMessage(errorResponse, customMessage);
    const isTransient = isTransientError(errorResponse);
    const isCircuitOpen = isCircuitBreakerOpen(errorResponse);

    // Log error for debugging
    console.error('API Error:', {
        code: errorResponse.code,
        status: errorResponse.status,
        message: userMessage,
        meta: errorResponse.meta,
    });

    // Call onError callback if provided
    if (onError) {
        onError(errorResponse);
    }

    // Show error message to user (if enabled)
    // Note: In a real implementation, this would use a toast/notification system
    // For now, we'll just log it and rely on components to display errors
    if (showMessage && userMessage) {
        // You can integrate with a toast library here
        // Example: toast.error(userMessage);
        console.warn('Error message (should show to user):', userMessage);
    }

    // Handle retry button display
    if (showRetry && isTransient && !isCircuitOpen && onRetry) {
        // Components should handle retry button display
        // This function just extracts and returns error details
    }

    return errorResponse;
}

/**
 * Create a retry function that wraps an async operation.
 */
export function createRetryFunction<T>(
    operation: () => Promise<T>,
    maxRetries: number = 3,
    delay: number = 1000
): () => Promise<T> {
    return async (): Promise<T> => {
        let lastError: unknown;

        for (let attempt = 1; attempt <= maxRetries; attempt++) {
            try {
                return await operation();
            } catch (error) {
                lastError = error;
                const errorResponse = extractError(error);

                // Don't retry if it's not a transient error
                if (errorResponse && !isTransientError(errorResponse)) {
                    throw error;
                }

                // Don't retry on last attempt
                if (attempt === maxRetries) {
                    break;
                }

                // Wait before retrying (exponential backoff)
                const waitTime = delay * Math.pow(2, attempt - 1);
                await new Promise((resolve) => setTimeout(resolve, waitTime));
            }
        }

        throw lastError;
    };
}


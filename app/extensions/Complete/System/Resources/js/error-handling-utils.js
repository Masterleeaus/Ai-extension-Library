/**
 * Error Handling Utilities for Promise Chains
 * Provides reusable error handling and logging for async operations
 */

const ErrorHandler = {
    /**
     * Default error handler for fetch operations
     * @param {Error} error - The error object
     * @param {string} context - Context for logging (e.g., 'User deletion')
     * @param {Object} options - Additional options
     */
    handleFetchError(error, context = 'API Request', options = {}) {
        const {
            showToast = true,
            logToConsole = true,
            logToServer = false,
            userMessage = 'An error occurred. Please try again.'
        } = options;

        if (logToConsole) {
            console.error(`[${context}] Error:`, error);
        }

        if (showToast && typeof toastr !== 'undefined') {
            toastr.error(userMessage);
        }

        if (logToServer) {
            this.logErrorToServer(error, context, options);
        }

        return error;
    },

    /**
     * Handle JSON parsing errors
     * @param {Error} error - The error object
     * @param {string} context - Context for logging
     */
    handleParseError(error, context = 'JSON Parse') {
        console.error(`[${context}] JSON parsing failed:`, error);

        if (typeof toastr !== 'undefined') {
            toastr.error('Failed to process server response. Please try again.');
        }

        return error;
    },

    /**
     * Handle validation errors from server
     * @param {Object} data - Response data containing validation errors
     * @param {string} context - Context for logging
     */
    handleValidationError(data, context = 'Validation') {
        console.warn(`[${context}] Validation failed:`, data);

        const message = data.message || 'Validation failed. Please check your input.';

        if (typeof toastr !== 'undefined') {
            toastr.error(message);
        }

        if (data.errors) {
            console.table(data.errors);
        }

        return data;
    },

    /**
     * Handle HTTP errors based on status code
     * @param {Response} response - Fetch response object
     * @param {string} context - Context for logging
     */
    handleHttpError(response, context = 'HTTP Request') {
        const status = response.status;
        let message = `HTTP ${status} Error`;

        if (status === 401) {
            message = 'Unauthorized. Please log in again.';
            // Optionally redirect to login
        } else if (status === 403) {
            message = 'Forbidden. You do not have permission to perform this action.';
        } else if (status === 404) {
            message = 'Resource not found.';
        } else if (status === 429) {
            message = 'Too many requests. Please try again later.';
        } else if (status >= 500) {
            message = 'Server error. Please try again later.';
        }

        console.error(`[${context}] ${message}`);

        if (typeof toastr !== 'undefined') {
            toastr.error(message);
        }

        return { status, message };
    },

    /**
     * Wrap a fetch call with automatic error handling
     * @param {string} url - URL to fetch
     * @param {Object} options - Fetch options
     * @returns {Promise}
     */
    async fetchWithErrorHandling(url, options = {}) {
        const {
            context = 'API Request',
            successMessage = null,
            ...fetchOptions
        } = options;

        try {
            const response = await fetch(url, fetchOptions);

            if (!response.ok) {
                this.handleHttpError(response, context);
                throw new Error(`HTTP ${response.status}`);
            }

            const data = await response.json();

            if (successMessage && typeof toastr !== 'undefined') {
                toastr.success(successMessage);
            }

            return data;
        } catch (error) {
            if (error instanceof SyntaxError) {
                this.handleParseError(error, context);
            } else {
                this.handleFetchError(error, context);
            }
            throw error;
        }
    },

    /**
     * Wrap async/await with timeout
     * @param {Promise} promise - Promise to wrap
     * @param {number} ms - Timeout in milliseconds
     */
    async withTimeout(promise, ms = 30000) {
        const timeoutPromise = new Promise((_, reject) =>
            setTimeout(() => reject(new Error('Operation timeout')), ms)
        );
        return Promise.race([promise, timeoutPromise]);
    },

    /**
     * Retry a failed operation
     * @param {Function} fn - Function to retry
     * @param {number} maxRetries - Maximum retry attempts
     * @param {number} delay - Delay between retries in ms
     */
    async retry(fn, maxRetries = 3, delay = 1000) {
        for (let i = 0; i < maxRetries; i++) {
            try {
                return await fn();
            } catch (error) {
                if (i === maxRetries - 1) throw error;

                console.warn(`Attempt ${i + 1} failed. Retrying in ${delay}ms...`);
                await new Promise(resolve => setTimeout(resolve, delay));
            }
        }
    },

    /**
     * Log error to server for monitoring
     * @param {Error} error - Error object
     * @param {string} context - Context for error
     * @param {Object} options - Additional data
     */
    logErrorToServer(error, context, options = {}) {
        const payload = {
            error: {
                message: error.message,
                stack: error.stack,
                name: error.name,
            },
            context,
            url: window.location.href,
            userAgent: navigator.userAgent,
            timestamp: new Date().toISOString(),
            ...options
        };

        // Send to error logging endpoint (if available)
        fetch('/api/errors/log', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
            },
            body: JSON.stringify(payload)
        }).catch(err => console.error('Failed to log error to server:', err));
    }
};

/**
 * Wrap fetch for convenient usage
 * Usage: safeFetch('/api/data').then(data => console.log(data))
 */
function safeFetch(url, options = {}) {
    return ErrorHandler.fetchWithErrorHandling(url, options);
}

// Export for use in different environments
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { ErrorHandler, safeFetch };
}

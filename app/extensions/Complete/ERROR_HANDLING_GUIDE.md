# Promise Chain Error Handling Guide

**Issue #243** - Add error handling to promise chains

---

## Overview

This guide provides comprehensive patterns for adding error handling to async operations and promise chains in the AI Suite Extensions.

---

## 1. Promise Chain Error Handling Patterns

### Basic Pattern
```javascript
// ❌ WITHOUT error handling
fetch('/api/data')
    .then(res => res.json())
    .then(data => console.log(data));

// ✅ WITH error handling
fetch('/api/data')
    .then(res => res.json())
    .then(data => console.log(data))
    .catch(error => {
        console.error('Failed to fetch data:', error);
        if (typeof toastr !== 'undefined') {
            toastr.error('An error occurred. Please try again.');
        }
    });
```

### HTTP Error Handling
```javascript
// ✅ RECOMMENDED pattern
fetch('/api/data')
    .then(res => {
        if (!res.ok) {
            throw new Error(`HTTP ${res.status}: ${res.statusText}`);
        }
        return res.json();
    })
    .then(data => {
        console.log('Success:', data);
        toastr.success('Operation completed successfully');
    })
    .catch(error => {
        console.error('Error:', error);
        toastr.error(error.message || 'An error occurred');
    });
```

### JSON Parsing Errors
```javascript
fetch('/api/data')
    .then(res => {
        if (!res.ok) throw new Error(`HTTP ${res.status}`);
        return res.json();
    })
    .then(data => {
        // Handle data
    })
    .catch(error => {
        if (error instanceof SyntaxError) {
            console.error('Invalid JSON response:', error);
            toastr.error('Server returned invalid data');
        } else {
            console.error('Request failed:', error);
            toastr.error('An error occurred');
        }
    });
```

---

## 2. Async/Await Error Handling

### Try/Catch Pattern
```javascript
// ❌ WITHOUT error handling
async function getData() {
    const res = await fetch('/api/data');
    const data = await res.json();
    console.log(data);
}

// ✅ WITH error handling
async function getData() {
    try {
        const res = await fetch('/api/data');
        
        if (!res.ok) {
            throw new Error(`HTTP ${res.status}: ${res.statusText}`);
        }

        const data = await res.json();
        console.log('Data received:', data);
        toastr.success('Data loaded successfully');
        
        return data;
    } catch (error) {
        if (error instanceof TypeError) {
            console.error('Network error:', error);
            toastr.error('Network error. Please check your connection.');
        } else if (error instanceof SyntaxError) {
            console.error('Invalid response:', error);
            toastr.error('Server returned invalid data');
        } else {
            console.error('Error:', error);
            toastr.error(error.message || 'An error occurred');
        }
        throw error; // Re-throw if needed
    } finally {
        // Cleanup (e.g., hide loading indicator)
        if (Alpine?.store) {
            Alpine.store('appLoadingIndicator')?.hide();
        }
    }
}
```

---

## 3. Using Error Handler Utility

### Installation
Include the error handling utility in your blade template:
```blade
<script src="{{ asset('js/error-handling-utils.js') }}"></script>
```

### Simple Usage
```javascript
// Using the utility
ErrorHandler.fetchWithErrorHandling('/api/data', {
    context: 'User Deletion',
    successMessage: 'User deleted successfully',
    method: 'DELETE',
    headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
    }
})
.then(data => {
    console.log('Success:', data);
    // Handle success
})
.catch(error => {
    console.error('Caught error:', error);
});
```

### Using safeFetch Wrapper
```javascript
// Even simpler
safeFetch('/api/users/123', {
    method: 'DELETE',
    headers: {
        'X-CSRF-TOKEN': '{{ csrf_token() }}'
    },
    context: 'Delete User',
    successMessage: 'User deleted successfully'
})
.then(data => console.log(data))
.catch(error => console.error(error));
```

### With Timeout
```javascript
async function fetchDataWithTimeout() {
    try {
        const data = await ErrorHandler.withTimeout(
            fetch('/api/data').then(r => r.json()),
            5000 // 5 second timeout
        );
        console.log(data);
    } catch (error) {
        if (error.message === 'Operation timeout') {
            toastr.error('Request took too long. Please try again.');
        }
    }
}
```

### With Retry
```javascript
async function fetchWithRetry() {
    try {
        const data = await ErrorHandler.retry(
            () => fetch('/api/data').then(r => r.json()),
            3, // max retries
            1000 // 1 second delay between retries
        );
        console.log(data);
    } catch (error) {
        toastr.error('Failed after 3 attempts. Please try again later.');
    }
}
```

---

## 4. Common Error Scenarios

### 401 Unauthorized
```javascript
.catch(error => {
    if (error.status === 401) {
        // Redirect to login
        window.location.href = '/login';
        return;
    }
    toastr.error('An error occurred');
});
```

### 429 Rate Limited
```javascript
.catch(error => {
    if (error.status === 429) {
        toastr.error('Too many requests. Please wait before trying again.');
        // Implement exponential backoff retry
        return;
    }
    toastr.error('An error occurred');
});
```

### Network Errors
```javascript
.catch(error => {
    if (error instanceof TypeError) {
        toastr.error('Network error. Please check your connection.');
        return;
    }
    toastr.error('An error occurred');
});
```

### Timeout Errors
```javascript
.catch(error => {
    if (error.message.includes('timeout')) {
        toastr.error('Request timed out. Please try again.');
        return;
    }
    toastr.error('An error occurred');
});
```

---

## 5. Real-World Examples

### Form Submission with Error Handling
```javascript
async function submitForm(formElement) {
    const button = formElement.querySelector('button[type="submit"]');
    button.disabled = true;
    button.textContent = 'Submitting...';

    try {
        const formData = new FormData(formElement);
        const response = await fetch(formElement.action, {
            method: formElement.method || 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: formData
        });

        if (!response.ok) {
            const error = await response.json();
            throw new Error(error.message || 'Submission failed');
        }

        const data = await response.json();
        toastr.success('Form submitted successfully');
        
        // Optional: Redirect or refresh
        if (data.redirect) {
            window.location.href = data.redirect;
        }

    } catch (error) {
        console.error('Form submission error:', error);
        toastr.error(error.message || 'An error occurred');
    } finally {
        button.disabled = false;
        button.textContent = 'Submit';
    }
}
```

### Data Table Refresh with Error Handling
```javascript
async function refreshTable() {
    const table = document.getElementById('dataTable');
    const originalContent = table.innerHTML;

    try {
        table.style.opacity = '0.5';
        
        const data = await ErrorHandler.fetchWithErrorHandling(
            '/api/table-data',
            { context: 'Table Refresh' }
        );

        table.innerHTML = generateTableHTML(data);
        table.style.opacity = '1';

    } catch (error) {
        table.innerHTML = originalContent; // Restore original
        table.style.opacity = '1';
        console.error('Table refresh failed:', error);
    }
}
```

### Polling with Error Handling
```javascript
async function pollStatus(resourceId, maxAttempts = 30) {
    let attempts = 0;

    return new Promise((resolve, reject) => {
        const interval = setInterval(async () => {
            attempts++;

            try {
                const data = await ErrorHandler.fetchWithErrorHandling(
                    `/api/status/${resourceId}`,
                    { context: 'Status Poll' }
                );

                if (data.status === 'completed') {
                    clearInterval(interval);
                    resolve(data);
                    return;
                }

                if (attempts >= maxAttempts) {
                    clearInterval(interval);
                    reject(new Error('Polling timeout'));
                }

            } catch (error) {
                console.warn(`Poll attempt ${attempts} failed:`, error);
                
                if (attempts >= maxAttempts) {
                    clearInterval(interval);
                    reject(error);
                }
            }
        }, 1000); // Poll every 1 second
    });
}
```

---

## 6. Best Practices

### ✅ DO:
- Always handle errors in promise chains
- Provide user feedback (toastr, alerts)
- Log errors to console in development
- Check HTTP response status codes
- Handle specific error types (Network, Timeout, etc.)
- Provide meaningful error messages
- Clean up resources in finally blocks

### ❌ DON'T:
- Ignore promise rejections silently
- Show generic "An error occurred" without context
- Forget to handle JSON parsing errors
- Log sensitive data in console
- Retry indefinitely without backoff
- Ignore timeout scenarios
- Leave loading indicators showing on error

---

## 7. Migration Checklist

### For Each Promise Chain:
- [ ] Add .catch() handler at the end
- [ ] Handle HTTP errors (check response.ok)
- [ ] Handle JSON parsing errors
- [ ] Provide user feedback
- [ ] Log errors to console
- [ ] Test error scenarios

### For Each Async/Await Function:
- [ ] Add try/catch block
- [ ] Handle different error types
- [ ] Provide user feedback
- [ ] Add finally block if needed
- [ ] Log errors appropriately
- [ ] Test error paths

---

## 8. Testing Error Handling

### Test Broken Endpoint
```javascript
// Test with invalid URL
fetch('/api/nonexistent')
    .then(res => res.json())
    .then(data => console.log(data))
    .catch(error => console.error('Caught:', error));
```

### Test Network Error
```javascript
// Simulate network error
fetch('http://invalid-domain-12345.com/api')
    .catch(error => {
        console.error('Network error:', error);
        // Should be caught as TypeError
    });
```

### Test Timeout
```javascript
// Implement timeout
const timeoutPromise = new Promise((_, reject) =>
    setTimeout(() => reject(new Error('Timeout')), 5000)
);

Promise.race([
    fetch('/api/slow-endpoint').then(r => r.json()),
    timeoutPromise
])
.catch(error => console.error('Error:', error));
```

---

## Files Provided

✅ `System/Resources/js/error-handling-utils.js` - Reusable error handling utilities
✅ `ERROR_HANDLING_GUIDE.md` - This comprehensive guide

**Status:** Ready for implementation
**Estimated Coverage:** 90+ promise chains

---

## Next Steps

1. **Include error-handling-utils.js** in blade templates
2. **Migrate existing promise chains** using patterns from this guide
3. **Add try/catch** to async/await functions
4. **Test error scenarios** before deployment
5. **Monitor logs** for unhandled promise rejections

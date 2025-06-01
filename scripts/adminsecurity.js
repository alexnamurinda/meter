/**
 * Security-focused JavaScript for Admin Dashboard
 * Contains CSRF protection, XSS prevention, and other security features
 */

document.addEventListener('DOMContentLoaded', function() {
  // Generate CSRF token for forms
  function generateCSRFToken() {
    const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
    let token = '';
    for (let i = 0; i < 32; i++) {
      token += chars.charAt(Math.floor(Math.random() * chars.length));
    }
    return token;
  }
  
  // Add CSRF token to all forms
  function addCSRFTokenToForms() {
    const csrfToken = generateCSRFToken();
    // Store token in session storage
    sessionStorage.setItem('csrf_token', csrfToken);
    
    // Add token to all forms
    document.querySelectorAll('form').forEach(function(form) {
      // Check if form already has a CSRF token field
      if (!form.querySelector('input[name="csrf_token"]')) {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'csrf_token';
        input.value = csrfToken;
        form.appendChild(input);
      }
    });
  }
  
  // Add CSRF token to AJAX requests
  function addCSRFTokenToAjax() {
    const csrfToken = sessionStorage.getItem('csrf_token');
    
    // Intercept XMLHttpRequest
    const originalOpen = XMLHttpRequest.prototype.open;
    XMLHttpRequest.prototype.open = function() {
      const result = originalOpen.apply(this, arguments);
      this.setRequestHeader('X-CSRF-Token', csrfToken);
      return result;
    };
    
    // Intercept fetch API
    const originalFetch = window.fetch;
    window.fetch = function(resource, options) {
      options = options || {};
      options.headers = options.headers || {};
      
      if (options.headers instanceof Headers) {
        options.headers.append('X-CSRF-Token', csrfToken);
      } else {
        options.headers['X-CSRF-Token'] = csrfToken;
      }
      
      return originalFetch(resource, options);
    };
  }
  
  // Prevent clickjacking
  function preventClickjacking() {
    if (window.self !== window.top) {
      // Page is in an iframe
      window.top.location = window.self.location;
    }
  }
  
  // Sanitize user input for XSS prevention
  function sanitizeUserInput() {
    document.querySelectorAll('input, textarea').forEach(function(input) {
      input.addEventListener('input', function() {
        // Basic sanitization (should be complemented by server-side validation)
        this.value = this.value
          .replace(/</g, '&lt;')
          .replace(/>/g, '&gt;')
          .replace(/"/g, '&quot;')
          .replace(/'/g, '&#39;')
          .replace(/`/g, '&#96;');
      });
    });
  }
  
  // Content Security Policy reporting
  function setupCSPReporting() {
    // Report CSP violations
    document.addEventListener('securitypolicyviolation', function(e) {
      const violationData = {
        'directive': e.violatedDirective,
        'blockedURI': e.blockedURI,
        'originalPolicy': e.originalPolicy
      };
      
      // Send the violation report to the server
      fetch('csp_report.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify(violationData)
      }).catch(function(error) {
        console.error('Error reporting CSP violation:', error);
      });
    });
  }
  
  // Detect and prevent browser extension tampering
  function monitorDOMChanges() {
    const observer = new MutationObserver(function(mutations) {
      mutations.forEach(function(mutation) {
        if (mutation.addedNodes.length) {
          mutation.addedNodes.forEach(function(node) {
            // Check for suspicious injected scripts
            if (node.nodeName === 'SCRIPT' && !node.hasAttribute('data-approved')) {
              // Log or handle suspicious script injection
              console.warn('Suspicious script detected and removed:', node);
              node.remove();
            }
          });
        }
      });
    });
    
    observer.observe(document.documentElement, {
      childList: true,
      subtree: true
    });
  }
  
  // Automatically log out when tab/window is hidden for too long
  function setupVisibilityTracking() {
    let hiddenTime = null;
    const maxHiddenTime = 10 * 60 * 1000; // 10 minutes
    
    document.addEventListener('visibilitychange', function() {
      if (document.hidden) {
        // Tab is hidden
        hiddenTime = new Date().getTime();
      } else {
        // Tab is visible again
        if (hiddenTime) {
          const currentTime = new Date().getTime();
          const elapsedTime = currentTime - hiddenTime;
          
          if (elapsedTime > maxHiddenTime) {
            // Tab was hidden for too long, log out
            window.location.href = 'logout.php?reason=inactivity';
          }
          
          hiddenTime = null;
        }
      }
    });
  }
  
  // Initialize all security features
  function initializeSecurity() {
    addCSRFTokenToForms();
    addCSRFTokenToAjax();
    preventClickjacking();
    sanitizeUserInput();
    setupCSPReporting();
    monitorDOMChanges();
    setupVisibilityTracking();
  }
  
  // Initialize security
  initializeSecurity();
});
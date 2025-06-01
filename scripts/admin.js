document.addEventListener('DOMContentLoaded', function() {
  // Sidebar toggle functionality
  const sidebarCollapse = document.getElementById('sidebarCollapse');
  const sidebar = document.getElementById('sidebar');
  
  if (sidebarCollapse) {
      sidebarCollapse.addEventListener('click', function() {
          sidebar.classList.toggle('active');
          
          // Save sidebar state in localStorage
          const isSidebarActive = sidebar.classList.contains('active');
          localStorage.setItem('sidebarState', isSidebarActive ? 'active' : 'inactive');
      });
  }
  
  // Check localStorage for saved sidebar state
  const savedSidebarState = localStorage.getItem('sidebarState');
  if (savedSidebarState === 'active') {
      sidebar.classList.add('active');
  } else if (savedSidebarState === 'inactive') {
      sidebar.classList.remove('active');
  }
  
  // Dropdown menu functionality
  document.querySelectorAll('.dropdown-toggle').forEach(function(element) {
      element.addEventListener('click', function(e) {
          const dropdownMenu = this.nextElementSibling;
          if (dropdownMenu && dropdownMenu.classList.contains('collapse')) {
              e.preventDefault();
              e.stopPropagation();
              dropdownMenu.classList.toggle('show');
          }
      });
  });
  
  // Close dropdowns when clicking outside
  document.addEventListener('click', function(e) {
      const dropdowns = document.querySelectorAll('.dropdown-menu.show');
      if (dropdowns.length > 0) {
          dropdowns.forEach(function(dropdown) {
              if (!dropdown.contains(e.target) && !dropdown.previousElementSibling.contains(e.target)) {
                  dropdown.classList.remove('show');
              }
          });
      }
  });
  
  // Active menu item highlighting
  const currentLocation = window.location.href;
  const menuItems = document.querySelectorAll('#sidebar ul li a');
  
  menuItems.forEach(function(item) {
      const itemUrl = item.getAttribute('href');
      if (currentLocation.includes(itemUrl) && itemUrl !== '#') {
          item.parentElement.classList.add('active');
          
          // If the item is inside a dropdown, expand the dropdown
          const dropdownParent = item.closest('ul.collapse');
          if (dropdownParent) {
              dropdownParent.classList.add('show');
              
              // Add active class to the parent dropdown toggle
              const dropdownToggle = dropdownParent.previousElementSibling;
              if (dropdownToggle) {
                  dropdownToggle.parentElement.classList.add('active');
              }
          }
      }
  });
  
  // Responsive checks for sidebar
  function checkWindowSize() {
      if (window.innerWidth < 992) {
          sidebar.classList.add('active');
      } else {
          if (savedSidebarState === null) {
              sidebar.classList.remove('active');
          }
      }
  }
  
  // Run on load and on window resize
  checkWindowSize();
  window.addEventListener('resize', checkWindowSize);
  
  // Add animation to stat cards on scroll
  const animateOnScroll = function() {
      const statCards = document.querySelectorAll('.stat-card');
      
      statCards.forEach(function(card, index) {
          const cardPosition = card.getBoundingClientRect().top;
          const screenPosition = window.innerHeight * 0.85;
          
          if (cardPosition < screenPosition) {
              setTimeout(function() {
                  card.classList.add('animate__fadeInUp');
                  card.style.visibility = 'visible';
              }, index * 100);
          }
      });
  };
  
  // Tooltip initialization
  const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
  tooltipTriggerList.map(function(tooltipTriggerEl) {
      return new bootstrap.Tooltip(tooltipTriggerEl);
  });
  
  // Sample charts initialization
  try {
      // This is a placeholder for chart initialization
      // You can add actual chart creation code here when needed
      console.log("Charts would be initialized here if needed");
  } catch (error) {
      console.error("Error initializing charts:", error);
  }
  
  // Notification system
  const createNotification = function(message, type = 'info') {
      const notification = document.createElement('div');
      notification.className = `notification notification-${type} animate__animated animate__fadeInRight`;
      notification.innerHTML = `
          <div class="notification-icon">
              <i class="fas ${type === 'success' ? 'fa-check-circle' : type === 'warning' ? 'fa-exclamation-triangle' : type === 'error' ? 'fa-times-circle' : 'fa-info-circle'}"></i>
          </div>
          <div class="notification-content">
              <p>${message}</p>
          </div>
          <div class="notification-close">
              <i class="fas fa-times"></i>
          </div>
      `;
      
      document.body.appendChild(notification);
      
      notification.querySelector('.notification-close').addEventListener('click', function() {
          notification.classList.replace('animate__fadeInRight', 'animate__fadeOutRight');
          setTimeout(function() {
              notification.remove();
          }, 500);
      });
      
      setTimeout(function() {
          notification.classList.replace('animate__fadeInRight', 'animate__fadeOutRight');
          setTimeout(function() {
              notification.remove();
          }, 500);
      }, 5000);
      
      return notification;
  };
  
  // Expose notification function to global scope
  window.createNotification = createNotification;
  
  // Demo notification on page load (commented out for production)
  // setTimeout(function() {
  //     createNotification('Welcome to Smart Meter Admin Dashboard!', 'success');
  // }, 1000);
  
  // Add fade-in animation to feature links
  const featureLinks = document.querySelectorAll('.feature-link');
  featureLinks.forEach(function(link, index) {
      link.style.transitionDelay = `${index * 0.05}s`;
      link.classList.add('animate__fadeIn');
  });
  
  // Enhanced interactive elements
  document.querySelectorAll('.feature-link, .btn, .activity-item').forEach(function(element) {
      element.addEventListener('mouseenter', function() {
          this.style.transform = 'translateY(-2px)';
          this.style.transition = 'transform 0.3s ease';
      });
      
      element.addEventListener('mouseleave', function() {
          this.style.transform = 'translateY(0)';
      });
  });
  
  // Add click ripple effect
  const addRippleEffect = function(event) {
      const button = event.currentTarget;
      
      const ripple = document.createElement('span');
      const rect = button.getBoundingClientRect();
      
      const size = Math.max(rect.width, rect.height);
      const x = event.clientX - rect.left - size / 2;
      const y = event.clientY - rect.top - size / 2;
      
      ripple.style.width = ripple.style.height = `${size}px`;
      ripple.style.left = `${x}px`;
      ripple.style.top = `${y}px`;
      ripple.className = 'ripple';
      
      button.appendChild(ripple);
      
      // Clean up
      setTimeout(() => {
          ripple.remove();
      }, 600);
  };
  
  document.querySelectorAll('.btn').forEach(button => {
      button.addEventListener('click', addRippleEffect);
  });
  
  // Add smooth scrolling for in-page links
  document.querySelectorAll('a[href^="#"]').forEach(anchor => {
      if (anchor.getAttribute('href') !== '#' && anchor.getAttribute('href') !== '#!') {
          anchor.addEventListener('click', function(e) {
              e.preventDefault();
              
              const targetId = this.getAttribute('href');
              const targetElement = document.querySelector(targetId);
              
              if (targetElement) {
                  targetElement.scrollIntoView({
                      behavior: 'smooth'
                  });
              }
          });
      }
  });
});

// Quick Actions functionality
document.addEventListener('DOMContentLoaded', function() {
  const quickActionButtons = document.querySelectorAll('.quick-actions .btn');
  
  quickActionButtons.forEach(function(button) {
      button.addEventListener('click', function() {
          const buttonText = this.textContent.trim();
          
          switch(buttonText) {
              case 'Add New Client':
                  window.location.href = 'add-client.php';
                  break;
              case 'Top Up Units':
                  window.location.href = '#'; // Add your top up page URL
                  break;
              case 'Download Reports':
                  // Create a demo notification
                  createNotification('Generating reports, please wait...', 'info');
                  // Simulate processing
                  setTimeout(function() {
                      createNotification('Reports ready for download!', 'success');
                  }, 2000);
                  break;
              case 'View Alerts':
                  window.location.href = '#'; // Add your alerts page URL
                  break;
              default:
                  // No action
                  break;
          }
      });
  });
});

// Add preload for page transitions
window.addEventListener('beforeunload', function() {
  document.body.classList.add('page-transition');
});
/*!
    * Start Bootstrap - Dispatch v2.0.5 (https://shop.startbootstrap.com/product/sb-admin-pro)
    * Copyright 2013-2023 Start Bootstrap
    * Licensed under SEE_LICENSE (https://github.com/StartBootstrap/sb-admin-pro/blob/master/LICENSE)
    */
    function initSidebarToggle() {
    const sidebarToggle = document.body.querySelector('#sidebarToggle');
    if (sidebarToggle) {
        console.log('Sidebar toggle handler attached');
        sidebarToggle.onclick = function(event) {
            event.preventDefault();
            document.body.classList.toggle('sidenav-toggled');
            localStorage.setItem('sb|sidebar-toggle', document.body.classList.contains('sidenav-toggled'));
            console.log('Sidebar toggled. sidenav-toggled:', document.body.classList.contains('sidenav-toggled'));
        };
    } else {
        console.log('Sidebar toggle button NOT found');
    }
}

window.addEventListener('DOMContentLoaded', event => {
    // Activate feather (guard if library not loaded)
    if (window.feather && typeof feather.replace === 'function') {
        feather.replace();
    }

    // Enable tooltips globally (guard if Bootstrap JS not loaded)
    if (window.bootstrap && typeof bootstrap.Tooltip === 'function') {
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    }

    // Enable popovers globally (guard if Bootstrap JS not loaded)
    if (window.bootstrap && typeof bootstrap.Popover === 'function') {
        var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
        var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
            return new bootstrap.Popover(popoverTriggerEl);
        });
    }

    // Initialize sidebar toggle (for static pages)
    initSidebarToggle();

    // Close side navigation when width < LG
    const sidenavContent = document.body.querySelector('#layoutSidenav_content');
    if (sidenavContent) {
        sidenavContent.addEventListener('click', event => {
            const BOOTSTRAP_LG_WIDTH = 992;
            if (window.innerWidth >= 992) {
                return;
            }
            if (document.body.classList.contains("sidenav-toggled")) {
                document.body.classList.toggle("sidenav-toggled");
            }
        });
    }

    // Add active state to sidbar nav links
    let activatedPath = window.location.pathname.match(/([\w-]+\.html)/, '$1');

    if (activatedPath) {
        activatedPath = activatedPath[0];
    } else {
        activatedPath = 'index.php';
    }

    const targetAnchors = document.body.querySelectorAll('[href="' + activatedPath + '"].nav-link');

    targetAnchors.forEach(targetAnchor => {
        let parentNode = targetAnchor.parentNode;
        while (parentNode !== null && parentNode !== document.documentElement) {
            if (parentNode.classList.contains('collapse')) {
                parentNode.classList.add('show');
                const parentNavLink = document.body.querySelector(
                    '[data-bs-target="#' + parentNode.id + '"]'
                );
                if (parentNavLink) {
                    parentNavLink.classList.remove('collapsed');
                    parentNavLink.classList.add('active');
                }
            }
            parentNode = parentNode.parentNode;
        }
        targetAnchor.classList.add('active');
    });
});

// Common email pattern (RFC 5322 simplified)
const EMAIL_PATTERN = /^[a-zA-Z0-9.!#$%&'*+\/=?^_`{|}~-]+@[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?(?:\.[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?)*$/;

// Validate all email fields with class 'email-pattern' in a form
function validateEmailFields(form) {
    let valid = true;
    const emailFields = form.querySelectorAll('.email-pattern');
    emailFields.forEach(function(field) {
        field.classList.remove('field-error');
        if (field.value && !EMAIL_PATTERN.test(field.value)) {
            field.classList.add('field-error');
            valid = false;
        }
    });
    return valid;
}

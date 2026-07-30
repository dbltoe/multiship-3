/* MultiShip v3.0.0 - undoes the core login focus and its scroll, only when multiship sent the customer here. Rationale in docs/ARCHITECTURE-3.0.0.md. Single quotes only: this is emitted into a body onload attribute. */
if (document.querySelector('.multishipLoginNotice') && document.loginForm && document.loginForm.email_address) { document.loginForm.email_address.blur(); window.scrollTo(0, 0); }

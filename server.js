// server.js (DEPRECATED)
// This project uses PHP built-in server for local development (see `package.json` start script).
// The old Express-based static server was optional and has been deprecated.
// Keeping a small stub here to avoid breaking scripts that reference server.js.

console.log('server.js is deprecated. Use the PHP built-in server:');
console.log('  php -S localhost:8000 -t public');
console.log('If you need a static file server for development, create a separate script that does not assume PHP execution.');

// Exit immediately so running `node server.js` has no side effects.
process.exit(0);

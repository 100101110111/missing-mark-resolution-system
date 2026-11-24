# missing-mark-resolution-system
best system

## Netlify deployment

The Netlify version uses Hono on Node.js 24. It installs dependencies with npm and runs `npm run build` from the repository root. The command publishes only the stylesheet and browser bundle in `dist/`; Netlify Server renders the pages and handles forms. The original PHP/XAMPP app remains in `Missing-marks-system_4.4/` and is not published.

Netlify Database applies the initial Postgres migration on deploy. This deployment starts with an empty database: it does not import existing MySQL accounts, reports, or records. Students sign in with a verified email through Netlify Identity, then enter their admission number and other profile details. Status updates appear in the student dashboard; case updates do not send email or SMS.

Staff access starts with one Netlify Identity account assigned the `admin` role. Open Manage and search for 'Identity'. Once that account exists, its owner can grant staff access from the staff directory. Staff use verified Identity accounts with the registered staff email addresses.

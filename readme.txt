KO WPDM Login Override & Redirect
Version: 1.1.1

Purpose:
- Forces /download/* to use /dashboard-access for login
- Redirects operational_user to /download/operational-documents after login
- Blocks operational_user from wp-admin
- Disables WPDM login form rendering to prevent captcha conflicts & loops

Dependencies / environment notes:
- Cloudflare cache bypass + WAF skip for /download* and /dashboard-access*
- LiteSpeed excludes for /download* and /dashboard-access*
- LiteSpeed Do Not Cache Roles: operational_user
- WPDM login captcha should remain OFF
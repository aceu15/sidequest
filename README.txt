
CORE FLOWS INCLUDED
- Registration/login/logout
- CSRF protection
- Password hashing
- Quest creation
- Quest discovery/search/filter/sort
- Quest detail / mission brief
- Join/request flow
- Creator accept/reject
- Quest completion confirmation
- XP and level calculation
- Level-based active quest capacity
- Achievements
- Leaderboard
-  reward ledger with configurable allocations
- Notifications
- Reports/moderation
- Admin control room/settings
- PHP/MySQL prepared statements
- Responsive dark premium UI

LOCATION
Quests can store latitude/longitude. The code includes Haversine distance calculation for privacy-conscious proximity logic. If coordinates are not provided, the site simply falls back to the displayed location text.

REWARD SYSTEM
This project deliberately does NOT pretend to process real money.
Paid quest amounts and platform allocations are stored in reward_transactions as a /internal ledger. A real payment provider can be integrated later.

IMPORTANT
The included project is designed for a school/ environment. Before any real-world deployment, add production-grade email verification, rate limiting, stronger moderation, audit logging, secure HTTPS cookies, secret management, and a real payment/escrow provider.


PROFILE VISIBILITY
Profiles are public by user ID and can be opened while logged out. Only the profile owner can edit their username, email, bio, password, or profile picture. Avatar and bio data are loaded from the users table and reused across leaderboard, quest creator/participant views, dashboard, and profile pages.

For an existing database, run profile_migration.sql once before using profile editing.

AVATAR STORAGE
Profile pictures are stored in SIDEQUEST_USER_DATA/avatars, outside the application folder, so replacing/updating the SIDEQUEST folder does not delete uploaded avatars. Do not delete that folder when updating the project.


COMPLETION FLOW
- Accepted participants can start a quest from the quest page.
- Participants can mark an in-progress quest complete.
- The creator receives a notification and confirms the completion from Manage Quest.
- Only after creator confirmation are XP, rewards, levels, and completion-based achievements awarded.
- My Quests now separates quests you joined from quests you created.
- Admin Control includes user management, quest moderation, report resolve/dismiss, and economy settings.
- Initial admin setup: after registering the first account on a fresh database, visit /admin/setup.php while signed in to enable administrator access. The setup page locks once an admin exists.

DATABASE / HOSTING
- config/config.php uses Railway-style MYSQLHOST, MYSQLPORT, MYSQLUSER, MYSQLPASSWORD, and MYSQLDATABASE environment variables when present, while retaining XAMPP localhost defaults.


CAPACITY LOGIC FIX
Active quest capacity only counts participant records whose parent quest is still active. Completed or cancelled quests no longer consume a player's capacity, even if an old participant status was not updated. Deleted quests are removed through the database cascade.

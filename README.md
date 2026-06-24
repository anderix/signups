# signups

A drop-in sign-up sheet for outings, built to be simpler than the systems that usually replace paper. A sheet has a name and two lists — Scouts and Scouters — with a driving checkbox for the adults. The people running sign-ups have logins; the kids and adults *on* a sheet do not. That one decision is the whole point: nobody needs an account to be signed up, so the paper sheet's "just write the name down" feel survives the move to a screen.

It is the same "drop a folder on the site and point it at [Axe](https://github.com/anderix/axe)" shape as [xcribe](https://github.com/anderix/xcribe) and [browse](https://github.com/anderix/browse), and it borrows xcribe's login. The difference is that **everything here is behind the login** — a sign-up sheet lists minors, so there are no public pages at all.

Nothing in the code is troop-specific; it ships generic and can run for any group that signs people up for trips.

## How it works

You sign in and see your sheets, newest first. Open one and you get two lists side by side. Type a name into either list and press Add — a native autocomplete offers names from the shared roster as you type, but you can type anyone in. Each scouter has a driving checkbox you can flip at any time; the list header keeps a running "3 driving" tally so you can see at a glance whether there are enough cars. Remove anyone with the ✕ next to their name. When the sheet is set, **Print** gives you a clean roster to carry to camp, where there's usually no signal.

```
config.php           The one file you edit: where signups lives on the site.
index.php            Front controller; ?page= selects a screen.
src/                 db, auth (local password, bcrypt), and the sheet + roster models.
templates/           Login, setup, the sheet list, a single sheet, roster, users, print.
db/                  SQLite: users, sheets, the two lists per sheet, and the rosters.
public/css/          The styling layered over Axe.
```

## Setup

Drop the directory onto a PHP host (PHP 8+, with the standard SQLite extension) and serve it. The first visit shows a one-time setup screen: name the site and create the first account. signups expects Axe deployed at the site root as `/axe/`, the usual convention, for styling and the light/dark toggle. If your install lives somewhere other than `/signups`, set `WEB_BASE` in `config.php`.

```bash
# Try it locally
php -S localhost:8000 -t signups/
```

## Logins

Permissions are flat on purpose: everyone with a login can run sheets, maintain the roster, and add or remove other users. The group is small and trusted, and a role hierarchy is exactly the kind of complexity that sinks tools like this. On the **Users** screen, enter a name and username and click **Add user** — signups generates a friendly one-time password (for example `mint-otter-sail-94`) and shows it once, for you to read off to the new person. They set a password only they know on first sign-in. The same screen resets a forgotten password the same way. Two guard rails prevent a lockout: you can't remove your own account, and you can't remove the last one.

## The roster

The **Roster** screen holds two master lists — scouts and scouters — that every sheet's add-a-name box autocompletes from. Maintain it once and stop retyping the same thirty kids for every trip. Names are unique case-insensitively, so the same person can't sit in the roster twice as `Sam R` and `sam r`. Typing a fresh name directly on a sheet does *not* add it to the roster, so the roster stays a deliberate, clean list rather than collecting every guest and one-off.

By convention, scouts are listed as first name plus last initial (`Sam R`) and scouters as first initial plus last name (`S Rivera`); the screens remind you, but nothing enforces it — names are free text.

## Attribution

Author: David M. Anderson. Built with AI assistance (Claude, Anthropic).
